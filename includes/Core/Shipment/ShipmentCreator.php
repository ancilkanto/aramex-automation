<?php

namespace AramexAutomation\Core\Shipment;

use AramexAutomation\Core\Shipment\Api\AramexApi;
use AramexAutomation\Core\Email\EmailManager;
use AramexAutomation\Core\Shipment\PickupScheduler;

/**
 * Shipment Creator
 */
class ShipmentCreator
{
    /**
     * Create shipment for an order
     */
    public function createShipment($order, $aramex_settings)
    {
        try {
            
            
            // Prepare shipment data
            $shipment_data = $this->prepareShipmentData($order, $aramex_settings);
            
            // Create shipment using Aramex API
            $api = new AramexApi();
            $result = $api->createShipment($shipment_data);
            
            // Update order with shipment information
            if ($result['success'] && $result['tracking'] !== '' && $result['tracking'] !== null) {
                $this->updateOrderShipment($order, $result['tracking'], $result['label_url']);
                
                // Send email to customer if enabled and trigger is set to 'creation'
                if (
                    get_option('aramex_automation_auto_email', '1') == '1'
                    && get_option('aramex_automation_email_trigger', 'creation') === 'creation'
                ) {
                    EmailManager::sendShipmentEmail($order, $result['tracking']);
                }
                
                // Schedule pickup if enabled
                if (get_option('aramex_automation_auto_schedule', '1') == '1') {
                    $scheduler = new PickupScheduler();
                    $scheduler->schedulePickup($order, $result['tracking']);
                }
                
                // Send admin notification for successful shipment creation
                EmailManager::sendAdminNotification(
                    'Shipment Created Successfully - Order #' . $order->get_id(),
                    sprintf(
                        '<p>A new Aramex shipment has been created successfully.</p>
                        <p><strong>Tracking Number:</strong> %s</p>%s',
                        $result['tracking'],
                        EmailManager::getLabelUrlHtml($order)
                    ),
                    $order
                );
                
            } 

            return $result;
            
        } catch (\Exception $e) {                        
            
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Prepare shipment data from order and settings
     */
    private function prepareShipmentData($order, $aramex_settings)
    {
        // Get order items
        $items = $order->get_items();
        $description_of_goods = '';
        $total_weight = 0;
        $total_items = 0;
        
        // Check if custom description is set
        $custom_description = get_option('aramex_automation_custom_description_goods', '');
        
        if (!empty($custom_description)) {
            // Use custom description if set
            $description_of_goods = substr($custom_description, 0, 65);
        } else {
            // Generate description from order items
            foreach ($items as $item) {
                $product = $item->get_product();
                $description_of_goods .= $product->get_id() . ' - ' . trim($item->get_name()) . ' ';
            }
            $description_of_goods = substr($description_of_goods, 0, 65);
        }
        
        // Calculate weight and items count
        foreach ($items as $item) {
            $product = $item->get_product();
            $product_weight = $product->get_weight();
            // Use default weight if product weight is not set
            if (empty($product_weight) || $product_weight <= 0) {
                $product_weight = 0.5; // Default 0.5 kg
            }
            $total_weight += $product_weight * $item->get_quantity();
            $total_items += $item->get_quantity();
        }
        
        // Ensure minimum weight
        if ($total_weight <= 0) {
            $total_weight = 0.5; // Minimum 0.5 kg
        }
        
        // Get shipping address
        $shipping_address = $order->get_address('shipping');
        
        // Validate shipping address
        if (empty($shipping_address['first_name']) && empty($shipping_address['last_name'])) {
            // Fallback to billing address if shipping address is empty
            $shipping_address = $order->get_address('billing');
        }
        
        // Ensure we have a valid name
        if (empty($shipping_address['first_name']) && empty($shipping_address['last_name'])) {
            $shipping_address['first_name'] = 'Customer';
            $shipping_address['last_name'] = 'Name';
        }
        
        // Determine if shipment is domestic or international
        // Compare shipper country (from Aramex settings) with receiver country (from order)
        $shipper_country = $aramex_settings['country'];
        $receiver_country = $shipping_address['country'];
        $is_domestic = ($shipper_country === $receiver_country);
        $product_group = $product_type = '';
        
        // Set product group and type based on domestic/international shipping
        if ($is_domestic) {
            // Domestic shipment - same country
            $product_group = 'DOM'; // Domestic
            $product_type = 'ONP'; // Overnight Parcel for domestic
        } else {
            // International shipment - different countries
            $product_group = 'EXP'; // Express for international
            // Get international product type from Aramex settings or use default
            $product_type = $this->getInternationalProductType($aramex_settings, 'PPX');
        }
        
        // Prepare shipment data
        $shipment_data = [
            // Account information
            'aramex_shipment_shipper_account_show' => 1,
            'aramex_shipment_shipper_account' => $aramex_settings['account_number'],
            'aramex_shipment_shipper_account_pin' => $aramex_settings['account_pin'],
            'aramex_shipment_info_billing_account' => 1,
            
            // Order reference
            'aramex_shipment_original_reference' => $order->get_id(),
            'aramex_shipment_shipper_reference' => $order->get_id(),
            'aramex_shipment_receiver_reference' => $order->get_id(),
            
            // Shipper details (from Aramex settings)
            'aramex_shipment_shipper_name' => $aramex_settings['name'],
            'aramex_shipment_shipper_email' => $aramex_settings['email_origin'],
            'aramex_shipment_shipper_company' => $aramex_settings['company'],
            'aramex_shipment_shipper_street' => $aramex_settings['address'],
            'aramex_shipment_shipper_country' => $aramex_settings['country'],
            'aramex_shipment_shipper_city' => $aramex_settings['city'],
            'aramex_shipment_shipper_postal' => $aramex_settings['postalcode'],
            'aramex_shipment_shipper_state' => $aramex_settings['state'],
            'aramex_shipment_shipper_taxidvat' => isset($aramex_settings['tax_id']) ? $aramex_settings['tax_id'] : '',
            'aramex_shipment_shipper_phone' => $aramex_settings['phone'] ?: '0000000000',
            
            // Receiver details (from order)
            'aramex_shipment_receiver_name' => $shipping_address['first_name'] . ' ' . $shipping_address['last_name'],
            'aramex_shipment_receiver_email' => $order->get_billing_email(),
            'aramex_shipment_receiver_company' => $shipping_address['company'] ?: $shipping_address['first_name'] . ' ' . $shipping_address['last_name'],
            'aramex_shipment_receiver_street' => $shipping_address['address_1'] . ($shipping_address['address_2'] ? ', ' . $shipping_address['address_2'] : ''),
            'aramex_shipment_receiver_country' => $shipping_address['country'],
            'aramex_shipment_receiver_city' => $shipping_address['city'],
            'aramex_shipment_receiver_postal' => $shipping_address['postcode'],
            'aramex_shipment_receiver_state' => $shipping_address['state'],
            'aramex_shipment_receiver_taxidvat' => '',
            'aramex_shipment_receiver_phone' => $order->get_billing_phone() ?: '0000000000',
            
            // Package information
            'order_weight' => $total_weight,
            'weight_unit' => get_option('woocommerce_weight_unit'),
            'number_pieces' => ceil($total_items / 8), // 1 piece per 8 items, rounded up
            'aramex_shipment_description' => $description_of_goods,
            
            // Shipment information
            'aramex_shipment_info_reference' => $order->get_id(),
            'aramex_shipment_info_product_group' => $product_group,
            'aramex_shipment_info_product_type' => $product_type,
            'aramex_shipment_info_payment_method' => $order->get_payment_method(),
            'aramex_shipment_info_payment_type' => 'P',
            'aramex_shipment_info_service_type' => [],
            
            // Additional information
            'aramex_shipment_info_comment' => 'Auto-generated shipment for order #' . $order->get_id(),
            'aramex_email_customer' => 'yes',
            'aramex_return_shipment_creation_date' => 'create',
            
            // Tax information
            'TaxPaid' => 1,
            'ExporterType' => 'UT',
            
            // Currency
            'aramex_shipment_currency_code_custom_hidden_item' => $order->get_currency(),
            
            // Items details
            'aramex_items' => [],
            'item_details' => ''
        ];

        if (!$is_domestic) {
            $shipment_data['CustomsValueAmount'] = array(
                'Value' => $order->get_total(),
                'CurrencyCode' => $order->get_currency()
            );
        }
        
        // Add items details
        foreach ($items as $item_id => $item) {
            $product = $item->get_product();
            $product_id = $product->get_id();
            
            $shipment_data['aramex_items'][$product_id] = $item->get_quantity();
            $shipment_data['p_' . $product_id] = $item->get_quantity();
            $shipment_data['aramex_items_Title_' . $product_id] = $item->get_name();
            $shipment_data['aramex_items_base_price_' . $product_id] = $product->get_price();
            $shipment_data['aramex_items_base_weight_' . $product_id] = $product->get_weight();
            $shipment_data['aramex_items_total_' . $product_id] = $item->get_quantity();
            
            if (empty($shipment_data['item_details'])) {
                $shipment_data['item_details'] = $product_id;
            }
        }
        
        return $shipment_data;
    }

    /**
     * Get product type from international settings, handling multi-select
     * 
     * This method handles the case where international_product_type is configured as a multi-select
     * field in the Aramex settings. When multiple values are selected, it uses the first one
     * and logs a warning to help with debugging.
     * 
     * @param array $aramex_settings Aramex settings array
     * @param string $default Default product type if none configured
     * @return string Selected product type
     */
    private function getInternationalProductType($aramex_settings, $default = 'PPX')
    {
        if (!isset($aramex_settings['international_product_type'])) {
            return $default;
        }
        
        // Handle multi-select field - if it's an array, take the first value
        if (is_array($aramex_settings['international_product_type'])) {
            if (!empty($aramex_settings['international_product_type'])) {
                $product_type = $aramex_settings['international_product_type'][0];
                // Log when multiple product types are available but using the first one
                if (count($aramex_settings['international_product_type']) > 1) {
                    error_log('Aramex Automation: Multiple international product types configured: ' . implode(', ', $aramex_settings['international_product_type']) . '. Using: ' . $product_type);
                }
                return $product_type;
            } else {
                return $default;
            }
        } else {
            return $aramex_settings['international_product_type'];
        }
    }

    /**
     * Update order with shipment information
     */
    private function updateOrderShipment($order, $tracking_number, $label_url = '')
    {
        // Add order note with tracking number (same format as original plugin)
        $note_content = "AWB No. " . $tracking_number . " - Order No. " . $order->get_id();
        $order->add_order_note($note_content);
        
        // Persist tracking number to order meta for later use (e.g., email on status change)
        $order->update_meta_data('_aramex_tracking_number', $tracking_number);
        
        // Store label URL if available
        if (!empty($label_url)) {
            $order->update_meta_data('_aramex_label_url', $label_url);
        }
        
        $order->save();
        
        // Don't change status here - it will be changed to "awaiting shipment" after pickup scheduling
        // The status will be managed by the PickupScheduler
    }
} 