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

// Email Headings
echo "= " . $email_heading . " =\n";
if (!empty($email_heading_arabic)) {
    echo "= " . $email_heading_arabic . " =\n";
}
echo "\n";

echo sprintf(__('مرحباً %s،', 'aramex-automation'), $order->get_billing_first_name()) . "\n";
echo sprintf(__('أخبار رائعة! طلبك رقم #%s تم شحنه وهو في طريقه إليك.', 'aramex-automation'), $order->get_order_number()) . "\n\n";

echo "= " . __('معلومات التتبع', 'aramex-automation') . " =\n\n";

echo __('رقم التتبع:', 'aramex-automation') . " " . $tracking_number . "\n";
echo __('إجمالي الطلب:', 'aramex-automation') . " " . $order->get_formatted_order_total() . "\n";
echo __('تاريخ الطلب:', 'aramex-automation') . " " . wc_format_datetime($order->get_date_created()) . "\n\n";

echo __('يمكنك تتبع شحنتك باستخدام رقم التتبع أعلاه من خلال موقع أرامكس.', 'aramex-automation') . "\n";
echo __('شكراً لك على طلبك!', 'aramex-automation') . "\n\n";

// English Section Second
echo "--- " . __('English Version', 'aramex-automation') . " ---\n\n";

echo "= " . $email_heading . " =\n\n";

echo sprintf(__('Hi %s,', 'woocommerce'), $order->get_billing_first_name()) . "\n";
echo sprintf(__('Thank you for your order! A tracking number has been assigned for your order #%s and you can track it via the Aramex website.', 'aramex-automation'), $order->get_order_number()) . "\n\n";

echo "= " . __('Tracking Information', 'aramex-automation') . " =\n\n";

echo __('Tracking Number:', 'aramex-automation') . " " . $tracking_number . "\n";
echo __('Order Total:', 'woocommerce') . " " . $order->get_formatted_order_total() . "\n";
echo __('Order Date:', 'woocommerce') . " " . wc_format_datetime($order->get_date_created()) . "\n\n";


echo apply_filters('woocommerce_email_footer_text', get_option('woocommerce_email_footer_text')); 