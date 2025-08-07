<?php

namespace AramexAutomation\Core\Logging;

/**
 * Shipment Logger
 */
class ShipmentLogger
{
    /**
     * Log shipment attempt
     */
    public function logShipment($order_id, $result)
    {
        $logs = get_option('aramex_automation_shipment_logs', []);
        
        $log_entry = [
            'order_id' => $order_id,
            'success' => $result['success'],
            'message' => $result['message'],
            'tracking' => isset($result['tracking']) ? $result['tracking'] : '',
            'timestamp' => current_time('mysql'),
            'user_id' => get_current_user_id()
        ];
        
        // Add to beginning of array (most recent first)
        array_unshift($logs, $log_entry);
        
        // Keep only last 50 entries
        if (count($logs) > 50) {
            $logs = array_slice($logs, 0, 50);
        }
        
        update_option('aramex_automation_shipment_logs', $logs);
    }

    /**
     * Get recent shipments
     */
    public function getRecentShipments($limit = 10)
    {
        $logs = get_option('aramex_automation_shipment_logs', []);
        return array_slice($logs, 0, $limit);
    }

    /**
     * Clear logs
     */
    public function clearLogs()
    {
        delete_option('aramex_automation_shipment_logs');
    }
} 