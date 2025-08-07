<?php

namespace AramexAutomation\Core\Shipment;

use AramexAutomation\Core\Shipment\Api\AramexApi;

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
            } else {
                // Log pickup failure
                error_log('Aramex Automation: Pickup scheduling failed for order #' . $order->get_id() . ' - ' . $result['message']);
            }
            
            return $result;
            
        } catch (\Exception $e) {
            error_log('Aramex Automation: Pickup scheduling error for order #' . $order->get_id() . ' - ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
} 