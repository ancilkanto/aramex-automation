<?php
/**
 * Order Shipped email
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/order-shipped.php.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package AramexAutomation
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<?php
/*
 * @hooked WC_Emails::email_header() Output the email header
 */
// First header with Arabic heading
if (!empty($email_heading_arabic)) {
    do_action('woocommerce_email_header', $email_heading_arabic, $email);
} else {
    // Fallback to English heading if Arabic is not available
    do_action('woocommerce_email_header', $email_heading, $email);
}
?>


<style type="text/css">
    /* Inline styles for email compatibility */
    .arabic-section {
        text-align: right !important;
        direction: rtl;
        font-family: 'Arial', 'Tahoma', sans-serif;
        margin-bottom: 40px;
    }
    .english-section {
        text-align: left;
        padding-top: 30px;
        border-top: 3px solid #d0d0d0;
    }
    .arabic-heading {
        text-align: right !important;
        direction: rtl;
        font-family: 'Arial', 'Tahoma', sans-serif;
        font-size: 1.1em;
        margin-bottom: 20px;
    }
    .arabic-heading h2 {
        text-align: right !important;
        direction: rtl;
    }
    .arabic-section h2,
    .arabic-section h3,
    .arabic-section p,
    .arabic-section div {
        text-align: right !important;
        direction: rtl;
    }
    .arabic-section .td {
        text-align: right !important;
    }
    .english-heading {
        text-align: left;
        margin-bottom: 20px;
    }
    .arabic-content {
        text-align: right;
        direction: rtl;
        font-family: 'Arial', 'Tahoma', sans-serif;
        font-size: 0.95em;
        margin-bottom: 15px;
    }
    .english-content {
        text-align: left;
        margin-bottom: 15px;
    }
</style>

<!-- Arabic Section First -->
<div class="arabic-section" style="text-align: right !important; direction: rtl; font-family: 'Arial', 'Tahoma', sans-serif; margin-bottom: 40px;">
    <div class="arabic-content" style="text-align: right; direction: rtl; font-family: 'Arial', 'Tahoma', sans-serif; font-size: 0.95em; margin-bottom: 15px;">
        <p style="text-align: right !important; direction: rtl;"><?php printf(esc_html__('مرحباً %s،', 'aramex-automation'), esc_html($order->get_billing_first_name())); ?></p>
        <p style="text-align: right !important; direction: rtl;"><?php printf(esc_html__('أخبار رائعة! تم شحن طلبك رقم  #%s وهو الآن في الطريق اليك   ', 'aramex-automation'), $order->get_order_number()); ?></p>

        <p style="text-align: right !important; direction: rtl;"><?php esc_html_e(' يمكنك تتبع شحنتك باستخدام رقم التتبع عبر موقع أرامكس.    ', 'aramex-automation'); ?></p>
    </div>

    <div class="arabic-heading" style="text-align: right !important; direction: rtl; font-family: 'Arial', 'Tahoma', sans-serif; font-size: 1.1em; margin-bottom: 20px;">
        <h2 style="text-align: right !important; direction: rtl;"><?php esc_html_e('معلومات التتبع', 'aramex-automation'); ?></h2>
    </div>

<table class="td" cellspacing="0" cellpadding="6" style="width: 100%; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; margin-bottom: 40px;" border="1">
    <tbody>
        <tr>
            <td class="td" scope="row" style="text-align: right; vertical-align: middle; border: 1px solid #eee; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; word-wrap: break-word; color: #636363; padding: 12px;">
                <strong><?php esc_html_e('رقم التتبع:', 'aramex-automation'); ?></strong>
            </td>
            <td class="td" scope="row" style="text-align: right; vertical-align: middle; border: 1px solid #eee; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; word-wrap: break-word; color: #636363; padding: 12px;">
                <a href="<?php echo esc_url( 'https://www.aramex.com/us/en/track/results?source=aramex&ShipmentNumber=' . urlencode((string) $tracking_number) ); ?>" target="_blank"><?php echo esc_html($tracking_number); ?></a>
            </td>
        </tr>
        <tr>
            <td class="td" scope="row" style="text-align: right; vertical-align: middle; border: 1px solid #eee; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; word-wrap: break-word; color: #636363; padding: 12px;">
                <strong><?php esc_html_e('إجمالي الطلب:', 'aramex-automation'); ?></strong>
            </td>
            <td class="td" scope="row" style="text-align: right; vertical-align: middle; border: 1px solid #eee; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; word-wrap: break-word; color: #636363; padding: 12px;">
                <?php echo $order->get_formatted_order_total(); ?>
            </td>
        </tr>
        <tr>
            <td class="td" scope="row" style="text-align: right; vertical-align: middle; border: 1px solid #eee; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; word-wrap: break-word; color: #636363; padding: 12px;">
                <strong><?php esc_html_e('تاريخ الطلب:', 'aramex-automation'); ?></strong>
            </td>
            <td class="td" scope="row" style="text-align: right; vertical-align: middle; border: 1px solid #eee; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; word-wrap: break-word; color: #636363; padding: 12px;">
                <?php echo windrose_get_arabic_date(wc_format_datetime($order->get_date_created())); ?>
            </td>
        </tr>
    </tbody>
</table>

<div class="arabic-content" style="text-align: right; direction: rtl; font-family: 'Arial', 'Tahoma', sans-serif; font-size: 0.95em; margin-bottom: 15px;">
    
    <p style="text-align: right !important; direction: rtl;"><?php esc_html_e('شكراً لك على طلبك!', 'aramex-automation'); ?></p>
</div>
</div> <!-- End Arabic Section -->




<!-- English Section Second -->
<div class="english-section" style="text-align: left; padding-top: 30px; border-top: 3px solid #d0d0d0;">
    
    <!-- English Email Heading -->
    <div style="background-color: #ef722f; padding: 20px; text-align: center; margin-bottom: 20px;">
        <h1 style="color: white; margin: 0; font-size: 24px;"><?php echo esc_html($email_heading); ?></h1>
    </div>
    
    <div class="english-content" style="text-align: left; margin-bottom: 15px;">
        <p><?php printf(esc_html__('Hi %s,', 'woocommerce'), esc_html($order->get_billing_first_name())); ?></p>
        <p><?php printf(esc_html__('Great news! Your order #%s has been shipped and is on its way to you.', 'aramex-automation'), $order->get_order_number()); ?></p>
        <p><?php esc_html_e('You can track your shipment using the tracking number via Aramex website.', 'aramex-automation'); ?></p>        
    </div>

    <div class="english-heading" style="text-align: left; margin-bottom: 20px;">
        <h2><?php esc_html_e('Tracking Information', 'aramex-automation'); ?></h2>
    </div>

    <table class="td" cellspacing="0" cellpadding="6" style="width: 100%; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; margin-bottom: 40px;" border="1">
        <tbody>
            <tr>
                <td class="td" scope="row" style="text-align: left; vertical-align: middle; border: 1px solid #eee; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; word-wrap: break-word; color: #636363; padding: 12px;">
                    <strong><?php esc_html_e('Tracking Number:', 'aramex-automation'); ?></strong>
                </td>
                <td class="td" scope="row" style="text-align: left; vertical-align: middle; border: 1px solid #eee; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; word-wrap: break-word; color: #636363; padding: 12px;">
                    <a href="<?php echo esc_url( 'https://www.aramex.com/us/en/track/results?source=aramex&ShipmentNumber=' . urlencode((string) $tracking_number) ); ?>" target="_blank"><?php echo esc_html($tracking_number); ?></a>
                </td>
            </tr>
            <tr>
                <td class="td" scope="row" style="text-align: left; vertical-align: middle; border: 1px solid #eee; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; word-wrap: break-word; color: #636363; padding: 12px;">
                    <strong><?php esc_html_e('Order Total:', 'woocommerce'); ?></strong>
                </td>
                <td class="td" scope="row" style="text-align: left; vertical-align: middle; border: 1px solid #eee; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; word-wrap: break-word; color: #636363; padding: 12px;">
                    <?php echo $order->get_formatted_order_total(); ?>
                </td>
            </tr>
            <tr>
                <td class="td" scope="row" style="text-align: left; vertical-align: middle; border: 1px solid #eee; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; word-wrap: break-word; color: #636363; padding: 12px;">
                    <strong><?php esc_html_e('Order Date:', 'woocommerce'); ?></strong>
                </td>
                <td class="td" scope="row" style="text-align: left; vertical-align: middle; border: 1px solid #eee; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; word-wrap: break-word; color: #636363; padding: 12px;">
                    <?php echo wc_format_datetime($order->get_date_created()); ?>
                </td>
            </tr>
        </tbody>
    </table>

    <div class="english-content" style="text-align: left; margin-bottom: 15px;">
       <p><?php esc_html_e('Thank you for your order!', 'aramex-automation'); ?></p>
    </div>
</div> <!-- End English Section -->

<?php
/*
 * @hooked WC_Emails::email_footer() Output the email footer
 */
do_action('woocommerce_email_footer', $email);
?>
