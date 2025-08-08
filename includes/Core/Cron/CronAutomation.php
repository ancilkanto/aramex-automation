<?php

namespace AramexAutomation\Core\Cron;

use AramexAutomation\Core\Shipment\ShipmentCreator;
use AramexAutomation\Core\Logging\ShipmentLogger;

/**
 * Cron Automation Handler
 */
class CronAutomation
{
    /**
     * Constructor
     */
    public function __construct()
    {
        add_action('aramex_automation_daily_cron', [$this, 'processOrdersAutomatically']);
        add_action('init', [$this, 'scheduleCronOnActivation']);
    }

    /**
     * Schedule cron job on plugin activation
     */
    public function scheduleCronOnActivation()
    {
        // Only schedule if not already scheduled and automation is enabled
        if (!wp_next_scheduled('aramex_automation_daily_cron') && 
            get_option('aramex_automation_auto_cron_enabled', '0') === '1') {
            
            $cron_hour = get_option('aramex_automation_cron_hour', '9');
            $cron_minute = get_option('aramex_automation_cron_minute', '0');
            
            // Calculate the next occurrence of the specified time
            $next_run = strtotime("today {$cron_hour}:{$cron_minute}:00");
            
            // If the time has already passed today, schedule for tomorrow
            if ($next_run <= time()) {
                $next_run = strtotime("tomorrow {$cron_hour}:{$cron_minute}:00");
            }
            
            wp_schedule_event($next_run, 'daily', 'aramex_automation_daily_cron');
        }
    }

    /**
     * Process orders automatically via cron
     */
    public function processOrdersAutomatically()
    {
        // Check if automation is enabled
        if (get_option('aramex_automation_auto_cron_enabled', '0') !== '1') {
            error_log('Aramex Automation: Cron automation is disabled');
            return;
        }

        // Get Aramex settings
        $aramex_settings = get_option('woocommerce_aramex_settings');
        if (!$aramex_settings) {
            error_log('Aramex Automation: Aramex settings not found for cron automation');
            return;
        }

        // Get order status to process
        $order_status = get_option('aramex_automation_cron_order_status', 'processing');

        // Get orders with the specified status
        $orders = wc_get_orders([
            'status' => $order_status,
            'limit' => -1,
            'date_created' => '>' . date('Y-m-d', strtotime('-7 days')) // Only process orders from last 7 days
        ]);

        if (empty($orders)) {
            error_log('Aramex Automation: No orders found with status "' . $order_status . '" for cron automation');
            return;
        }

        $processed_count = 0;
        $success_count = 0;
        $error_count = 0;
        $errors = [];

        $shipment_creator = new ShipmentCreator();
        $logger = new ShipmentLogger();

        foreach ($orders as $order) {
            $order_id = $order->get_id();
            
            // Skip if order already has a tracking number (already processed)
            $order_notes = $order->get_customer_note();
            if (strpos($order_notes, 'AWB No.') !== false) {
                continue;
            }

            // Check if shipment creation is already in progress for this order
            $shipment_key = 'aramex_shipment_in_progress_' . $order_id;
            if (get_transient($shipment_key)) {
                error_log('Aramex Automation: Shipment creation already in progress for order #' . $order_id . ' (cron)');
                continue;
            }

            try {
                // Create shipment
                $result = $shipment_creator->createShipment($order, $aramex_settings);
                
                // Log the shipment attempt
                $logger->logShipment($order_id, $result);
                
                if ($result['success']) {
                    $success_count++;
                    
                } else {
                    $error_count++;
                    $errors[] = "Order #{$order_id}: " . $result['message'];
                    error_log('Aramex Automation: Cron failed to create shipment for order #' . $order_id . ' - ' . $result['message']);
                }
                
                $processed_count++;
                
            } catch (\Exception $e) {
                $error_count++;
                $errors[] = "Order #{$order_id}: " . $e->getMessage();
                error_log('Aramex Automation: Cron exception for order #' . $order_id . ' - ' . $e->getMessage());
            }
        }

        // Log summary
        $summary = "Cron Automation Summary: Processed {$processed_count} orders, {$success_count} successful, {$error_count} failed";

        // Store results for admin display
        $cron_results = [
            'timestamp' => current_time('mysql'),
            'processed_count' => $processed_count,
            'success_count' => $success_count,
            'error_count' => $error_count,
            'errors' => $errors,
            'summary' => $summary
        ];
        
        set_transient('aramex_automation_cron_results', $cron_results, DAY_IN_SECONDS);
    }

    /**
     * Get cron status information
     */
    public static function getCronStatus()
    {
        $next_scheduled = wp_next_scheduled('aramex_automation_daily_cron');
        $is_enabled = get_option('aramex_automation_auto_cron_enabled', '0') === '1';
        
        return [
            'enabled' => $is_enabled,
            'next_run' => $next_scheduled ? date('Y-m-d H:i:s', $next_scheduled) : 'Not scheduled',
            'next_run_timestamp' => $next_scheduled,
            'cron_hour' => get_option('aramex_automation_cron_hour', '9'),
            'cron_minute' => get_option('aramex_automation_cron_minute', '0'),
            'order_status' => get_option('aramex_automation_cron_order_status', 'processing')
        ];
    }

    /**
     * Manually trigger the cron job (for testing)
     */
    public static function triggerCronManually()
    {
        $instance = new self();
        $instance->processOrdersAutomatically();
        
        return [
            'success' => true,
            'message' => 'Cron job triggered manually'
        ];
    }
} 