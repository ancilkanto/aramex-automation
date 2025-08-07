<?php
/**
 * Aramex Shipment email (plain text)
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/plain/aramex-shipment.php.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package AramexAutomation
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

echo "= " . $email_heading . " =\n\n";

echo sprintf(__('Hi %s,', 'woocommerce'), $order->get_billing_first_name()) . "\n\n";

echo sprintf(__('Great news! Your order #%s has been shipped and is on its way to you.', 'aramex-automation'), $order->get_order_number()) . "\n\n";

echo "= " . __('Tracking Information', 'aramex-automation') . " =\n\n";

echo __('Tracking Number:', 'aramex-automation') . " " . $tracking_number . "\n";
echo __('Order Total:', 'woocommerce') . " " . $order->get_formatted_order_total() . "\n";
echo __('Order Date:', 'woocommerce') . " " . wc_format_datetime($order->get_date_created()) . "\n\n";

echo __('You can track your shipment using the tracking number above through the Aramex website.', 'aramex-automation') . "\n\n";

echo __('Thank you for your order!', 'woocommerce') . "\n\n";

echo apply_filters('woocommerce_email_footer_text', get_option('woocommerce_email_footer_text')); 