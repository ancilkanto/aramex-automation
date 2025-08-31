# Aramex Automation - Label URL Storage

This document explains how the label URL is now stored and retrieved from order meta when creating shipments.

## Overview

When a shipment is created successfully through the Aramex API, the system now automatically stores the label URL in the order meta data. This allows you to access the shipping label PDF URL for any order that has been processed.

## Storage

The label URL is stored in the order meta with the key `_aramex_label_url`. This happens automatically when:

1. A shipment is created successfully via the Aramex API
2. The API response contains a `ShipmentLabel.LabelURL` field
3. The order is updated with shipment information

## Retrieval

### Method 1: Using Utility Methods

The easiest way to retrieve the label URL is using the static utility methods:

```php
use AramexAutomation\Core\Shipment\Api\AramexApi;

// Get label URL by order ID
$label_url = AramexApi::getOrderLabelUrl(123);

// Get label URL by order object
$order = wc_get_order(123);
$label_url = AramexApi::getOrderLabelUrl($order);

// Get tracking number
$tracking = AramexApi::getOrderTrackingNumber(123);
```

### Method 2: Direct Meta Access

You can also access the meta directly:

```php
$order = wc_get_order(123);
$label_url = $order->get_meta('_aramex_label_url');
$tracking = $order->get_meta('_aramex_tracking_number');
```

## Usage Examples

### Display Label Link in Admin

```php
add_action('woocommerce_admin_order_data_after_shipping_address', function($order) {
    $label_url = AramexApi::getOrderLabelUrl($order);
    if ($label_url) {
        echo '<p><strong>Shipping Label:</strong> <a href="' . esc_url($label_url) . '" target="_blank">Download Label</a></p>';
    }
});
```

### Add to Order Emails

```php
add_action('woocommerce_email_order_details', function($order, $sent_to_admin, $plain_text) {
    $label_url = AramexApi::getOrderLabelUrl($order);
    if ($label_url) {
        echo '<p><a href="' . esc_url($label_url) . '">Download Shipping Label</a></p>';
    }
}, 10, 3);
```

### Custom API Endpoint

```php
add_action('rest_api_init', function() {
    register_rest_route('aramex/v1', '/order/(?P<id>\d+)/label', [
        'methods' => 'GET',
        'callback' => function($request) {
            $order_id = $request['id'];
            $label_url = AramexApi::getOrderLabelUrl($order_id);
            
            if ($label_url) {
                return ['label_url' => $label_url];
            } else {
                return new WP_Error('no_label', 'No label found for this order', ['status' => 404]);
            }
        }
    ]);
});
```

## Data Structure

The label URL is stored as a simple string in the order meta. Example:

```
_aramex_label_url: "https://ws.aramex.net/ShippingAPI.V2/rpt_cache/78b649d2a3df4564ba2295f5139f4ebc.pdf"
_aramex_tracking_number: "48920807600"
```

## Notes

- The label URL is only stored when a shipment is created successfully
- If no label URL is returned from the API, the meta field will not be set
- The label URL typically points to a PDF file that can be downloaded or displayed
- The URL is provided by Aramex and may expire after a certain time period
- Always validate and sanitize the URL before displaying it to users

## Troubleshooting

If you're not getting label URLs:

1. Check that the Aramex API is returning `ShipmentLabel.LabelURL` in the response
2. Verify that shipments are being created successfully
3. Check the order meta data to see if `_aramex_label_url` exists
4. Review the API response logs for any errors
