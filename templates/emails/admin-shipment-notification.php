<?php
/**
 * Admin Shipment Notification Email Template
 *
 * This template follows WooCommerce email standards and structure.
 *
 * @package AramexAutomation
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/*
 * @hooked WC_Emails::email_header() Output the email header
 */
do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<style>
.aramex-download-button {
    display: inline-block !important;
    background-color: #ef722f !important;
    color: white !important;
    text-decoration: none !important;
    padding: 12px 24px !important;
    border-radius: 6px !important;
    font-weight: bold !important;
    text-align: center !important;
    border: 2px solid #ef722f !important;
    font-size: 14px !important;
    line-height: 1.4 !important;
    min-width: 200px !important;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1) !important;
    transition: all 0.3s ease !important;
    margin: 10px 0 !important;
}

.aramex-download-button:hover {
    background-color: #d65a1f !important;
    border-color: #d65a1f !important;
    box-shadow: 0 4px 8px rgba(0,0,0,0.15) !important;
    color: white !important;
}

.aramex-download-button:active {
    background-color: #c04a0f !important;
    border-color: #c04a0f !important;
    transform: translateY(1px) !important;
}

/* Fallback for email clients that don't support CSS */
.aramex-download-button[style*="background"] {
    background-color: #ef722f !important;
    color: white !important;
    border: 2px solid #ef722f !important;
    border-radius: 6px !important;
    padding: 12px 24px !important;
    font-weight: bold !important;
    text-decoration: none !important;
    display: inline-block !important;
    min-width: 200px !important;
    text-align: center !important;
}
</style>

<?php if ( $order ) : ?>
    <div class="email-introduction">
        <p><?php printf( esc_html__( 'A shipment notification has been generated for order #%s.', 'aramex-automation' ), esc_html( $order->get_order_number() ) ); ?></p>
    </div>
    <h2><?php esc_html_e( 'Shipment Information', 'aramex-automation' ); ?></h2>
    <p><strong><?php esc_html_e( 'Tracking Number:', 'aramex-automation' ); ?></strong> <?php echo esc_html( $tracking_number?$tracking_number:'' ); ?></p>
    <p><a href="<?php echo esc_url( $label_url?$label_url:'#' ); ?>" target="_blank" class="aramex-download-button" style="display: inline-block; background-color: #ef722f; color: white; text-decoration: none; padding: 12px 24px; border-radius: 6px; font-weight: bold; text-align: center; border: 2px solid #ef722f; font-size: 14px; line-height: 1.4; min-width: 200px; margin: 10px 0;"><?php esc_html_e( 'Download Shipping Label', 'aramex-automation' ); ?></a></p>
    

    <div class="order-summary">
        <h3><?php esc_html_e( 'Order Summary', 'aramex-automation' ); ?></h3>
        <table class="order-details" cellspacing="0" cellpadding="6" style="width: 100%; border: 1px solid #eee;">
            <tr>
                <th scope="row" style="text-align: left; border: 1px solid #eee;"><?php esc_html_e( 'Order:', 'aramex-automation' ); ?></th>
                <td style="text-align: left; border: 1px solid #eee;">#<?php echo esc_html( $order->get_order_number() ); ?></td>
            </tr>
            <tr>
                <th scope="row" style="text-align: left; border: 1px solid #eee;"><?php esc_html_e( 'Date:', 'aramex-automation' ); ?></th>
                <td style="text-align: left; border: 1px solid #eee;"><?php echo esc_html( wc_format_datetime( $order->get_date_created() ) ); ?></td>
            </tr>
            <tr>
                <th scope="row" style="text-align: left; border: 1px solid #eee;"><?php esc_html_e( 'Total:', 'aramex-automation' ); ?></th>
                <td style="text-align: left; border: 1px solid #eee;"><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></td>
            </tr>
            <tr>
                <th scope="row" style="text-align: left; border: 1px solid #eee;"><?php esc_html_e( 'Payment method:', 'aramex-automation' ); ?></th>
                <td style="text-align: left; border: 1px solid #eee;"><?php echo wp_kses_post( $order->get_payment_method_title() ); ?></td>
            </tr>
        </table>
    </div>

    <div class="customer-details">
        <h3><?php esc_html_e( 'Customer Details', 'aramex-automation' ); ?></h3>
        <table class="customer-details" cellspacing="0" cellpadding="6" style="width: 100%; border: 1px solid #eee;">
            <tr>
                <th scope="row" style="text-align: left; border: 1px solid #eee;"><?php esc_html_e( 'Name:', 'aramex-automation' ); ?></th>
                <td style="text-align: left; border: 1px solid #eee;"><?php echo esc_html( $order->get_formatted_billing_full_name() ); ?></td>
            </tr>
            <tr>
                <th scope="row" style="text-align: left; border: 1px solid #eee;"><?php esc_html_e( 'Email:', 'aramex-automation' ); ?></th>
                <td style="text-align: left; border: 1px solid #eee;"><?php echo esc_html( $order->get_billing_email() ); ?></td>
            </tr>
            <tr>
                <th scope="row" style="text-align: left; border: 1px solid #eee;"><?php esc_html_e( 'Phone:', 'aramex-automation' ); ?></th>
                <td style="text-align: left; border: 1px solid #eee;"><?php echo esc_html( $order->get_billing_phone() ); ?></td>
            </tr>
        </table>
    </div>

    <?php if ( $additional_content ) : ?>
        <div class="additional-content">
            <?php echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) ); ?>
        </div>
    <?php endif; ?>

<?php else : ?>
    <div class="general-notification">
        <?php echo wp_kses_post( $shipment_details ); ?>
    </div>
<?php endif; ?>

<?php
/*
 * @hooked WC_Emails::email_footer() Output the email footer
 */
do_action( 'woocommerce_email_footer', $email );
?>
