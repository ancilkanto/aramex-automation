<?php

namespace AramexAutomation\Core\Cron;

use AramexAutomation\Core\Shipment\ShipmentCreator;
use AramexAutomation\Core\Shipment\Api\AramexApi;
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
        if (get_option('aramex_automation_auto_cron_enabled', '0') === '1') {
            
            // Schedule main automation cron
            if (!wp_next_scheduled('aramex_automation_daily_cron')) {
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
        
        // Send admin notification for cron automation summary
        if ($processed_count > 0) {
            $subject = 'Cron Automation Summary - ' . date('Y-m-d H:i:s');
            $message = sprintf(
                '<p><strong>Cron Automation Summary</strong></p>
                <p><strong>Processed Orders:</strong> %d</p>
                <p><strong>Successful:</strong> %d</p>
                <p><strong>Failed:</strong> %d</p>',
                $processed_count,
                $success_count,
                $error_count
            );
            
            if (!empty($errors)) {
                $message .= '<p><strong>Errors:</strong></p><ul>';
                foreach (array_slice($errors, 0, 5) as $error) { // Limit to first 5 errors
                    $message .= '<li>' . esc_html($error) . '</li>';
                }
                if (count($errors) > 5) {
                    $message .= '<li>... and ' . (count($errors) - 5) . ' more errors</li>';
                }
                $message .= '</ul>';
            }
            
            \AramexAutomation\Core\Email\EmailManager::sendAdminNotification($subject, $message);
        }
        
        // After processing orders, check shipment statuses
        $this->checkShipmentStatuses();
    }

    /**
     * Check shipment statuses and update order statuses automatically
     */
    public function checkShipmentStatuses()
    {
        // Check if automation is enabled
        if (get_option('aramex_automation_auto_cron_enabled', '0') !== '1') {
            error_log('Aramex Automation: Cron automation is disabled');
            return;
        }

        // Get Aramex settings
        $aramex_settings = get_option('woocommerce_aramex_settings');
        if (!$aramex_settings) {
            error_log('Aramex Automation: Aramex settings not found for shipment status checking');
            return;
        }

        $api = new AramexApi();
        $processed_count = 0;
        $status_updates = 0;
        $errors = [];

        // Process 1: Check "Awaiting Shipment" orders and update to "Shipped" if in transit
        $this->processAwaitingShipmentOrders($api, $aramex_settings, $processed_count, $status_updates, $errors);

        // Process 2: Check "Shipped" orders and update to "Completed" if delivered
        $this->processShippedOrders($api, $aramex_settings, $processed_count, $status_updates, $errors);

        // Log summary
        $summary = "Shipment Status Check Summary: Processed {$processed_count} orders, {$status_updates} status updates";
        if (!empty($errors)) {
            $summary .= ", " . count($errors) . " errors";
        }

        // Store results for admin display
        $status_check_results = [
            'timestamp' => current_time('mysql'),
            'processed_count' => $processed_count,
            'status_updates' => $status_updates,
            'errors' => $errors,
            'summary' => $summary
        ];
        
        set_transient('aramex_automation_status_check_results', $status_check_results, DAY_IN_SECONDS);
        
        // Send admin notification for shipment status check summary
        if ($processed_count > 0) {
            $subject = 'Shipment Status Check Summary - ' . date('Y-m-d H:i:s');
            $message = sprintf(
                '<p><strong>Shipment Status Check Summary</strong></p>
                <p><strong>Orders Checked:</strong> %d</p>
                <p><strong>Status Updates:</strong> %d</p>',
                $processed_count,
                $status_updates
            );
            
            if (!empty($errors)) {
                $message .= '<p><strong>Errors:</strong></p><ul>';
                foreach (array_slice($errors, 0, 5) as $error) { // Limit to first 5 errors
                    $message .= '<li>' . esc_html($error) . '</li>';
                }
                if (count($errors) > 5) {
                    $message .= '<li>... and ' . (count($errors) - 5) . ' more errors</li>';
                }
                $message .= '</ul>';
            }
            
            \AramexAutomation\Core\Email\EmailManager::sendAdminNotification($subject, $message);
        }
        
        error_log('Aramex Automation: ' . $summary);
    }

    /**
     * Process orders with "Awaiting Shipment" status
     */
    private function processAwaitingShipmentOrders($api, $aramex_settings, &$processed_count, &$status_updates, &$errors)
    {
        // Get orders with "awaiting-shipment" status
        $orders = wc_get_orders([
            'status' => 'awaiting-shipment',
            'limit' => -1,
            'date_created' => '>' . date('Y-m-d', strtotime('-30 days')) // Process orders from last 30 days
        ]);

        if (empty($orders)) {
            return;
        }

        foreach ($orders as $order) {
            $order_id = $order->get_id();
            $processed_count++;

            try {
                // Get tracking number
                $tracking_number = $order->get_meta('_aramex_tracking_number');
                if (!$tracking_number) {
                    continue; // Skip orders without tracking numbers
                }

                // Check shipment status with Aramex
                $tracking_result = $api->trackShipment($tracking_number, $aramex_settings);
                
                if ($tracking_result['success']) {
                    $shipment_stage = $tracking_result['shipment_stage'];
                    $status_description = $tracking_result['status'];
                    
                    // Update to "Shipped" if in transit
                    if ($shipment_stage === 'in_transit') {
                        $order->update_status('shipped', sprintf(
                            'Order automatically updated to "Shipped" based on Aramex tracking status: %s',
                            $status_description
                        ));
                        
                        $order->add_order_note(sprintf(
                            'Shipment status checked via Aramex API. Status: %s. Order automatically updated to "Shipped".',
                            $status_description
                        ));
                        
                        $status_updates++;
                        error_log("Aramex Automation: Order #{$order_id} status updated to 'Shipped' based on tracking status: {$status_description}");
                    } else {
                        // Add note about current status
                        $order->add_order_note(sprintf(
                            'Shipment status checked via Aramex API. Current status: %s. No status update needed.',
                            $status_description
                        ));
                    }
                } else {
                    $errors[] = "Order #{$order_id}: " . $tracking_result['message'];
                    error_log("Aramex Automation: Failed to check shipment status for order #{$order_id}: " . $tracking_result['message']);
                }

            } catch (\Exception $e) {
                $errors[] = "Order #{$order_id}: " . $e->getMessage();
                error_log("Aramex Automation: Exception checking shipment status for order #{$order_id}: " . $e->getMessage());
            }
        }
    }

    /**
     * Process orders with "Shipped" status
     */
    private function processShippedOrders($api, $aramex_settings, &$processed_count, &$status_updates, &$errors)
    {
        // Get orders with "shipped" status
        $orders = wc_get_orders([
            'status' => 'shipped',
            'limit' => -1,
            'date_created' => '>' . date('Y-m-d', strtotime('-30 days')) // Process orders from last 30 days
        ]);

        if (empty($orders)) {
            return;
        }

        foreach ($orders as $order) {
            $order_id = $order->get_id();
            $processed_count++;

            try {
                // Get tracking number
                $tracking_number = $order->get_meta('_aramex_tracking_number');
                if (!$tracking_number) {
                    continue; // Skip orders without tracking numbers
                }

                // Check shipment status with Aramex
                $tracking_result = $api->trackShipment($tracking_number, $aramex_settings);
                
                if ($tracking_result['success']) {
                    $shipment_stage = $tracking_result['shipment_stage'];
                    $status_description = $tracking_result['status'];
                    
                    // Update to "Completed" if delivered
                    if ($shipment_stage === 'delivered') {
                        $order->update_status('completed', sprintf(
                            'Order automatically updated to "Completed" based on Aramex tracking status: %s',
                            $status_description
                        ));
                        
                        $order->add_order_note(sprintf(
                            'Shipment status checked via Aramex API. Status: %s. Order automatically updated to "Completed".',
                            $status_description
                        ));
                        
                        $status_updates++;
                        error_log("Aramex Automation: Order #{$order_id} status updated to 'Completed' based on tracking status: {$status_description}");
                    } else {
                        // Add note about current status
                        $order->add_order_note(sprintf(
                            'Shipment status checked via Aramex API. Current status: %s. No status update needed.',
                            $status_description
                        ));
                    }
                } else {
                    $errors[] = "Order #{$order_id}: " . $tracking_result['message'];
                    error_log("Aramex Automation: Failed to check shipment status for order #{$order_id}: " . $tracking_result['message']);
                }

            } catch (\Exception $e) {
                $errors[] = "Aramex Automation: Exception checking shipment status for order #{$order_id}: " . $e->getMessage();
                error_log("Aramex Automation: Exception checking shipment status for order #{$order_id}: " . $e->getMessage());
            }
        }
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
            'message' => 'Cron job triggered manually (includes shipment status checking)'
        ];
    }

    /**
     * Manually trigger the shipment status check (for testing)
     */
    public static function triggerStatusCheckManually()
    {
        $instance = new self();
        $instance->checkShipmentStatuses();
        
        return [
            'success' => true,
            'message' => 'Shipment status check triggered manually'
        ];
    }
} 