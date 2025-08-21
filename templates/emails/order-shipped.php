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
// First header with Arabic heading - COMMENTED OUT
/*
if (!empty($email_heading_arabic)) {
    do_action('woocommerce_email_header', $email_heading_arabic, $email);
}
*/
// Use English heading instead
do_action('woocommerce_email_header', $email_heading, $email);
?>


<link rel="stylesheet" href="<?php echo esc_url(plugins_url('assets/css/bilingual-email.css', dirname(dirname(__FILE__)))); ?>" type="text/css" />

<!-- Arabic Section First - HIDDEN -->
<div class="arabic-section" style="display: none;">
    <div class="arabic-content">
        <p><?php printf(esc_html__('مرحباً %s،', 'aramex-automation'), esc_html($order->get_billing_first_name())); ?></p>
        <p><?php printf(esc_html__('أخبار رائعة! طلبك رقم #%s تم شحنه وهو في طريقه إليك.', 'aramex-automation'), $order->get_order_number()); ?></p>
    </div>

    <div class="arabic-heading">
        <h2><?php esc_html_e('معلومات التتبع', 'aramex-automation'); ?></h2>
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
                <?php echo wc_format_datetime($order->get_date_created()); ?>
            </td>
        </tr>
    </tbody>
</table>

<div class="arabic-content">
    <p><?php esc_html_e('يمكنك تتبع شحنتك باستخدام رقم التتبع أعلاه من خلال موقع أرامكس.', 'aramex-automation'); ?></p>
    <p><?php esc_html_e('شكراً لك على طلبك!', 'aramex-automation'); ?></p>
</div>
</div> <!-- End Arabic Section -->




<!-- English Section Second -->
<div class="english-section">
    
    <!-- English Email Heading - HIDDEN -->
    <div style="background-color: #ef722f; padding: 20px; text-align: center; margin-bottom: 20px; display: none;">
        <h1 style="color: white; margin: 0; font-size: 24px;"><?php echo esc_html($email_heading); ?></h1>
    </div>
    
    <div class="english-content">
        <p><?php printf(esc_html__('Hi %s,', 'woocommerce'), esc_html($order->get_billing_first_name())); ?></p>
        <p><?php printf(esc_html__('Great news! Your order #%s has been shipped and is on its way to you.', 'aramex-automation'), $order->get_order_number()); ?></p>
        <p><?php esc_html_e('You can track your shipment using the tracking number via Aramex website.', 'aramex-automation'); ?></p>        
    </div>

    <div class="english-heading">
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

    <div class="english-content">
       <p><?php esc_html_e('Thank you for your order!', 'aramex-automation'); ?></p>
    </div>
</div> <!-- End English Section -->

<?php
/*
 * @hooked WC_Emails::email_footer() Output the email footer
 */
do_action('woocommerce_email_footer', $email);
?>
