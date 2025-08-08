<?php

namespace AramexAutomation\Core\Shipment;

use AramexAutomation\Core\Shipment\Api\AramexApi;
use AramexAutomation\Core\Email\EmailManager;

/**
 * Pickup Scheduler
 */
class PickupScheduler
{
    /**
     * Schedule pickup for an order
     */
    public function schedulePickup($order, $tracking_number)
    {
        try {
            $api = new AramexApi();
            $result = $api->schedulePickup($order, $tracking_number);
            
            if ($result['success']) {
                // Add pickup reference to order
                $pickup_note = "Pickup scheduled successfully. Pickup ID: " . $result['pickup_id'];
                $order->add_order_note($pickup_note);
                
                // If email trigger is set to status_change, proactively send email here as well (guarded by transient)
                if (
                    get_option('aramex_automation_auto_email', '1') == '1' &&
                    get_option('aramex_automation_email_trigger', 'creation') === 'status_change'
                ) {
                    EmailManager::sendShipmentEmail($order, $tracking_number);
                }

                // Change order status to "awaiting shipment" or fallback to "processing"
                $this->updateOrderStatusToAwaitingShipment($order);
                
            } else {
                // Log pickup failure
                error_log('Aramex Automation: Pickup scheduling failed for order #' . $order->get_id() . ' - ' . $result['message']);
                
                // Add failure note to order
                $failure_note = "Pickup scheduling failed: " . $result['message'];
                $order->add_order_note($failure_note);
            }
            
            return $result;
            
        } catch (\Exception $e) {
            error_log('Aramex Automation: Pickup scheduling error for order #' . $order->get_id() . ' - ' . $e->getMessage());
            
            // Add error note to order
            $error_note = "Pickup scheduling error: " . $e->getMessage();
            $order->add_order_note($error_note);
            
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Update order status to "awaiting shipment" with proper fallback
     */
    private function updateOrderStatusToAwaitingShipment($order)
    {
        // Get available order statuses
        $order_statuses = wc_get_order_statuses();
        
        // Try to use "awaiting-shipment" status if it exists
        if (isset($order_statuses['wc-awaiting-shipment'])) {
            $order->update_status('awaiting-shipment', __('Pickup scheduled successfully. Order awaiting shipment.', 'aramex-automation'));
            $status_note = "Order status changed to 'Awaiting Shipment' after successful pickup scheduling.";
        } 
        // Fallback to "processing" status (most common for orders ready for shipment)
        else {
            $order->update_status('processing', __('Pickup scheduled successfully. Order ready for shipment.', 'aramex-automation'));
            $status_note = "Order status changed to 'Processing' after successful pickup scheduling.";
        }
        
        // Add order note about status change
        $order->add_order_note($status_note);
    }
} 