<?php
/**
 * Aramex Shipment email
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/aramex-shipment.php.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package AramexAutomation
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/*
 * @hooked WC_Emails::email_header() Output the email header
 */
do_action('woocommerce_email_header', $email_heading, $email);
?>

<p><?php printf(esc_html__('Hi %s,', 'woocommerce'), esc_html($order->get_billing_first_name())); ?></p>

<p><?php printf(esc_html__('Great news! Your order #%s has been shipped and is on its way to you.', 'aramex-automation'), $order->get_order_number()); ?></p>

<h2><?php esc_html_e('Tracking Information', 'aramex-automation'); ?></h2>

<table class="td" cellspacing="0" cellpadding="6" style="width: 100%; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; margin-bottom: 40px;" border="1">
    <tbody>
        <tr>
            <td class="td" scope="row" style="text-align: left; vertical-align: middle; border: 1px solid #eee; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; word-wrap: break-word; color: #636363; padding: 12px;">
                <strong><?php esc_html_e('Tracking Number:', 'aramex-automation'); ?></strong>
            </td>
            <td class="td" scope="row" style="text-align: left; vertical-align: middle; border: 1px solid #eee; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; word-wrap: break-word; color: #636363; padding: 12px;">
                <a href="https://www.aramex.com/track/<?php echo esc_attr($tracking_number); ?>" target="_blank"><?php echo esc_html($tracking_number); ?></a>
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

<p><?php esc_html_e('You can track your shipment using the tracking number above through the Aramex website.', 'aramex-automation'); ?></p>

<p><?php esc_html_e('Thank you for your order!', 'woocommerce'); ?></p>

<?php
/*
 * @hooked WC_Emails::email_footer() Output the email footer
 */
do_action('woocommerce_email_footer', $email);
?> 