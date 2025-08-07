<?php

namespace AramexAutomation\Core\Shipment;

use AramexAutomation\Core\Shipment\Api\AramexApi;
use AramexAutomation\Core\Email\CustomerEmail;
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
            // Check if shipment creation is already in progress for this order
            $shipment_key = 'aramex_shipment_in_progress_' . $order->get_id();
            if (get_transient($shipment_key)) {
                error_log('Aramex Automation: Shipment creation already in progress for order #' . $order->get_id());
                return [
                    'success' => false,
                    'message' => 'Shipment creation already in progress for this order. Please wait.'
                ];
            }
            
            // Set transient to prevent duplicate creation (expires in 30 seconds)
            set_transient($shipment_key, true, 30);
            
            // Prepare shipment data
            $shipment_data = $this->prepareShipmentData($order, $aramex_settings);
            
            // Create shipment using Aramex API
            $api = new AramexApi();
            $result = $api->createShipment($shipment_data);
            
            // Update order with shipment information
            if ($result['success']) {
                $this->updateOrderShipment($order, $result['tracking']);
                
                // Send email to customer if enabled
                if (get_option('aramex_automation_auto_email', '1') == '1') {
                    $email = new CustomerEmail();
                    $email->sendCustomerEmail($order, $result['tracking']);
                }
                
                // Schedule pickup if enabled
                if (get_option('aramex_automation_auto_schedule', '1') == '1') {
                    $scheduler = new PickupScheduler();
                    $scheduler->schedulePickup($order, $result['tracking']);
                }
            }
            
            // Clean up the transient
            delete_transient($shipment_key);
            
            return $result;
            
        } catch (\Exception $e) {
            // Clean up the transient on error too
            if (isset($shipment_key)) {
                delete_transient($shipment_key);
            }
            
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
        
        foreach ($items as $item) {
            $product = $item->get_product();
            $description_of_goods .= $product->get_id() . ' - ' . trim($item->get_name()) . ' ';
            $product_weight = $product->get_weight();
            // Use default weight if product weight is not set
            if (empty($product_weight) || $product_weight <= 0) {
                $product_weight = 0.5; // Default 0.5 kg
            }
            $total_weight += $product_weight * $item->get_quantity();
            $total_items += $item->get_quantity();
        }
        $description_of_goods = substr($description_of_goods, 0, 65);
        
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
            'number_pieces' => $total_items,
            'aramex_shipment_description' => $description_of_goods,
            
            // Shipment information
            'aramex_shipment_info_reference' => $order->get_id(),
            'aramex_shipment_info_product_group' => 'DOM',
            'aramex_shipment_info_product_type' => 'ONP',
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
     * Update order with shipment information
     */
    private function updateOrderShipment($order, $tracking_number)
    {
        // Add order note with tracking number (same format as original plugin)
        $note_content = "AWB No. " . $tracking_number . " - Order No. " . $order->get_id();
        $order->add_order_note($note_content);
        
        // Update order status to "on-hold" (same as original plugin)
        $order->update_status('on-hold', __('Aramex shipment created.', 'aramex-automation'));
    }
} 