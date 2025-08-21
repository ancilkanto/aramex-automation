# Custom WordPress Hooks Documentation

This document describes the custom WordPress hooks provided by the Aramex Automation plugin.

## Overview

The plugin provides several custom WordPress hooks that allow developers to extend functionality and integrate with other systems. These hooks are triggered at specific points in the shipment and pickup process.

## Available Hooks

### 1. `aramex_after_pickup_schedule`

**Description**: Triggered after successful pickup scheduling with Aramex.

**When it fires**: After a pickup has been successfully scheduled via the Aramex API.

**Parameters**:
- `$order` (WC_Order) - The WooCommerce order object
- `$tracking_number` (string) - The Aramex tracking number
- `$pickup_id` (string) - The Aramex pickup ID

**Use Cases**:
- Send custom notifications to customers
- Update external systems or CRMs
- Create follow-up tasks or reminders
- Log pickup information to custom systems
- Trigger additional business processes
- Send webhooks to third-party services

**Example Usage**:
```php
add_action('aramex_after_pickup_schedule', 'my_custom_function', 10, 3);

function my_custom_function($order, $tracking_number, $pickup_id) {
    $order_id = $order->get_id();
    $customer_email = $order->get_billing_email();
    
    // Send custom email
    $subject = 'Pickup Scheduled Successfully!';
    $message = "Your order #{$order_id} pickup has been scheduled.";
    wp_mail($customer_email, $subject, $message);
    
    // Update external system
    update_external_system($order_id, $tracking_number, $pickup_id);
}
```

**Priority and Execution Order**:
- Default priority is 10
- Lower numbers execute first
- Higher numbers execute last
- Use priorities to control execution order when multiple functions are hooked

### 2. `aramex_automation_daily_cron`

**Description**: Triggered daily via cron job to process orders automatically.

**When it fires**: When the daily cron job runs to process orders with the specified status.

**Parameters**: None

**Use Cases**:
- Custom order processing logic
- Integration with external systems
- Custom reporting and analytics
- Additional automation workflows

**Example Usage**:
```php
add_action('aramex_automation_daily_cron', 'my_custom_cron_function');

function my_custom_cron_function() {
    // Your custom cron logic here
    process_custom_workflow();
    send_daily_reports();
}
```

## Hook Execution Flow

### Pickup Scheduling Flow
1. Shipment is created successfully
2. Pickup scheduling is initiated
3. Aramex API call for pickup scheduling
4. If successful:
   - Order status is updated
   - Order notes are added
   - **`aramex_after_pickup_schedule` hook is triggered**
   - Email notifications are sent (if configured)
5. If failed:
   - Error is logged
   - Order note is added
   - Hook is NOT triggered

### Cron Processing Flow
1. Daily cron job is triggered
2. Orders with specified status are retrieved
3. Each order is processed for shipment creation
4. **`aramex_automation_daily_cron` hook is triggered**
5. Results are logged and stored

## Best Practices

### 1. Hook Priority
- Use appropriate priorities to control execution order
- Default priority (10) is usually sufficient
- Use lower priorities (1-9) for critical functions that must run first
- Use higher priorities (11-20) for functions that depend on others

### 2. Error Handling
- Always check if parameters are valid
- Use try-catch blocks for critical operations
- Log errors appropriately
- Don't let hook failures break the main process

### 3. Performance
- Keep hook functions lightweight
- Avoid heavy database operations
- Use transients for caching when appropriate
- Consider using async operations for heavy tasks

### 4. Security
- Validate all input data
- Check user capabilities when needed
- Sanitize output data
- Use nonces for form submissions

## Common Use Cases

### 1. Customer Notifications
```php
add_action('aramex_after_pickup_schedule', 'send_custom_notification', 10, 3);

function send_custom_notification($order, $tracking_number, $pickup_id) {
    $customer_email = $order->get_billing_email();
    $customer_name = $order->get_billing_first_name();
    
    $subject = 'Your Order Pickup is Scheduled!';
    $message = "Hi {$customer_name},\n\nYour order pickup has been scheduled successfully.";
    
    wp_mail($customer_email, $subject, $message);
}
```

### 2. External System Integration
```php
add_action('aramex_after_pickup_schedule', 'update_external_system', 15, 3);

function update_external_system($order, $tracking_number, $pickup_id) {
    $webhook_url = 'https://your-system.com/webhook';
    $data = [
        'order_id' => $order->get_id(),
        'tracking_number' => $tracking_number,
        'pickup_id' => $pickup_id,
        'timestamp' => current_time('mysql')
    ];
    
    wp_remote_post($webhook_url, [
        'body' => json_encode($data),
        'headers' => ['Content-Type' => 'application/json']
    ]);
}
```

### 3. Custom Order Status Updates
```php
add_action('aramex_after_pickup_schedule', 'update_custom_status', 20, 3);

function update_custom_status($order, $tracking_number, $pickup_id) {
    // Only update if order is in specific status
    if ($order->get_status() === 'processing') {
        $order->update_meta_data('_pickup_scheduled', current_time('mysql'));
        $order->save();
    }
}
```

### 4. Logging and Analytics
```php
add_action('aramex_after_pickup_schedule', 'log_pickup_event', 25, 3);

function log_pickup_event($order, $tracking_number, $pickup_id) {
    $log_entry = [
        'order_id' => $order->get_id(),
        'tracking_number' => $tracking_number,
        'pickup_id' => $pickup_id,
        'timestamp' => current_time('mysql'),
        'customer_email' => $order->get_billing_email()
    ];
    
    // Log to custom file or database
    error_log('Pickup Event: ' . json_encode($log_entry));
}
```

## Testing Hooks

Use the provided test file (`test-hook.php`) to verify that your hooks are working correctly:

1. Copy the test file to your theme's functions.php or a custom plugin
2. Trigger a pickup scheduling process
3. Check the WordPress debug log for test messages
4. Verify that your custom functions are executed
5. Remove the test code after verification

## Troubleshooting

### Hook Not Firing
- Check if the hook is added with the correct action name
- Verify the function is properly defined
- Check WordPress debug log for errors
- Ensure the plugin is active and functioning

### Parameters Not Received
- Verify the number of parameters in your function signature
- Check the hook priority and execution order
- Ensure the main process completed successfully

### Performance Issues
- Check if your hook function is too heavy
- Consider using transients for caching
- Use async operations for heavy tasks
- Monitor execution time

## Support

For questions about custom hooks or development assistance, please refer to the main plugin documentation or contact the plugin author.

## Custom Order Statuses

The plugin provides two custom order statuses:

### 1. **Awaiting Shipment** (`awaiting-shipment`)
- **Description**: Order has been processed and pickup has been scheduled
- **Email**: "Awaiting Shipment" email is sent when status changes to this
- **Use Case**: Intermediate status between processing and shipping

### 2. **Shipped** (`shipped`)
- **Description**: Order has been shipped and is in transit
- **Email**: "Order Shipped" email is sent when status changes to this
- **Use Case**: Final status indicating the order is on its way to the customer

## Custom Emails

### 1. **Awaiting Shipment Email**
- **Triggered**: When order status changes to "awaiting-shipment"
- **Content**: Tracking information and pickup confirmation
- **Template**: `emails/awaiting-shipment.php`
- **Plain Text**: `emails/plain/awaiting-shipment.php`

### 2. **Order Shipped Email**
- **Triggered**: When order status changes to "shipped"
- **Content**: Shipment confirmation and tracking information
- **Template**: `emails/order-shipped.php`
- **Plain Text**: `emails/plain/order-shipped.php`
