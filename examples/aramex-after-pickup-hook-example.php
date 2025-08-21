<?php
/**
 * Example: How to use the "Aramex After Pickup Schedule" hook
 * 
 * This file demonstrates how to hook into the custom WordPress action
 * that is triggered after successful pickup scheduling with Aramex.
 * 
 * Place this code in your theme's functions.php file or in a custom plugin.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Example function that hooks into the Aramex After Pickup Schedule action
 * 
 * @param WC_Order $order The WooCommerce order object
 * @param string $tracking_number The Aramex tracking number
 * @param string $pickup_id The Aramex pickup ID
 */
function my_aramex_after_pickup_function($order, $tracking_number, $pickup_id) {
    // Get order information
    $order_id = $order->get_id();
    $customer_email = $order->get_billing_email();
    $customer_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
    
    // Example 1: Send custom notification to customer
    $subject = 'Your order pickup has been scheduled!';
    $message = "Dear {$customer_name},\n\n";
    $message .= "Great news! Your order #{$order_id} pickup has been scheduled with Aramex.\n";
    $message .= "Tracking Number: {$tracking_number}\n";
    $message .= "Pickup ID: {$pickup_id}\n\n";
    $message .= "We'll notify you once your package is picked up and on its way.\n\n";
    $message .= "Thank you for your business!\n";
    $message .= get_bloginfo('name');
    
    wp_mail($customer_email, $subject, $message);
    
    // Example 2: Update order meta with pickup information
    $order->update_meta_data('_pickup_scheduled_date', current_time('mysql'));
    $order->update_meta_data('_pickup_id', $pickup_id);
    $order->save();
    
    // Example 3: Add custom order note
    $order->add_order_note("Custom notification sent to customer after pickup scheduling. Pickup ID: {$pickup_id}");
    
    // Example 4: Trigger another custom action
    do_action('my_custom_after_pickup_action', $order_id, $tracking_number, $pickup_id);
    
    // Example 5: Log to custom log file
    $log_entry = date('Y-m-d H:i:s') . " - Order #{$order_id}: Pickup scheduled with ID {$pickup_id}\n";
    file_put_contents(WP_CONTENT_DIR . '/aramex-pickup-log.txt', $log_entry, FILE_APPEND | LOCK_EX);
}

/**
 * Hook into the Aramex After Pickup Schedule action
 * 
 * Priority 10 (default) - runs after the main pickup scheduling logic
 * Accepts 3 parameters: order, tracking_number, pickup_id
 */
add_action('aramex_after_pickup_schedule', 'my_aramex_after_pickup_function', 10, 3);

/**
 * Example of a function that runs with different priority
 * This will run after the main function above
 */
function my_aramex_after_pickup_function_late($order, $tracking_number, $pickup_id) {
    $order_id = $order->get_id();
    
    // Example: Update external system or send webhook
    $webhook_data = [
        'order_id' => $order_id,
        'tracking_number' => $tracking_number,
        'pickup_id' => $pickup_id,
        'timestamp' => current_time('mysql'),
        'action' => 'pickup_scheduled'
    ];
    
    // Send webhook to external system (example)
    wp_remote_post('https://your-external-system.com/webhook', [
        'body' => json_encode($webhook_data),
        'headers' => ['Content-Type' => 'application/json']
    ]);
}

// Hook with higher priority (runs later)
add_action('aramex_after_pickup_schedule', 'my_aramex_after_pickup_function_late', 20, 3);

/**
 * Example of a function that only runs for specific order statuses
 */
function my_aramex_after_pickup_function_conditional($order, $tracking_number, $pickup_id) {
    // Only run for orders with specific status
    if ($order->get_status() === 'processing') {
        $order_id = $order->get_id();
        
        // Example: Send SMS notification for processing orders
        $phone = $order->get_billing_phone();
        if ($phone) {
            // Your SMS sending logic here
            // send_sms($phone, "Order #{$order_id} pickup scheduled! Track: {$tracking_number}");
        }
    }
}

add_action('aramex_after_pickup_schedule', 'my_aramex_after_pickup_function_conditional', 15, 3);

/**
 * Example of a function that creates a custom cron job after pickup
 */
function my_aramex_after_pickup_cron_setup($order, $tracking_number, $pickup_id) {
    $order_id = $order->get_id();
    
    // Schedule a custom cron job to check pickup status after 2 hours
    $cron_time = time() + (2 * HOUR_IN_SECONDS);
    wp_schedule_single_event($cron_time, 'my_custom_pickup_status_check', [$order_id, $tracking_number, $pickup_id]);
}

add_action('aramex_after_pickup_schedule', 'my_aramex_after_pickup_cron_setup', 25, 3);

/**
 * Custom cron job handler for pickup status check
 */
function my_custom_pickup_status_check_handler($order_id, $tracking_number, $pickup_id) {
    // Your logic to check pickup status
    // This could involve calling Aramex API to check if pickup was completed
    
    $order = wc_get_order($order_id);
    if ($order) {
        $order->add_order_note("Custom cron job executed to check pickup status for pickup ID: {$pickup_id}");
        
        // Example: Update order status or send notifications based on pickup status
        // $pickup_status = check_aramex_pickup_status($pickup_id);
        // if ($pickup_status === 'completed') {
        //     $order->update_status('shipped', 'Pickup completed successfully');
        // }
    }
}

// Hook the custom cron job handler
add_action('my_custom_pickup_status_check', 'my_custom_pickup_status_check_handler', 10, 3);
