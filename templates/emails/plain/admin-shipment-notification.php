<?php
/**
 * Admin Shipment Notification Email Template (Plain Text)
 *
 * This template follows WooCommerce email standards and structure.
 *
 * @package AramexAutomation
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html( wp_strip_all_tags( $email_heading ) );
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

if ( $order ) {
    printf( esc_html__( 'A shipment notification has been generated for order #%s:', 'aramex-automation' ), esc_html( $order->get_order_number() ) );
    echo "\n\n";

    if ( $shipment_details ) {
        echo esc_html__( 'Shipment Information:', 'aramex-automation' ) . "\n";
        echo esc_html( wp_strip_all_tags( $shipment_details ) ) . "\n\n";
    }

    if ( $tracking_number ) {
        echo esc_html__( 'Tracking Details:', 'aramex-automation' ) . "\n";
        echo esc_html__( 'Tracking Number:', 'aramex-automation' ) . ' ' . esc_html( $tracking_number ) . "\n\n";
    }

    if ( $label_url ) {
        echo esc_html__( 'Shipping Label:', 'aramex-automation' ) . "\n";
        echo "========================================\n";
        echo esc_html__( 'Download URL:', 'aramex-automation' ) . ' ' . esc_url( $label_url ) . "\n";
        echo "========================================\n\n";
    }

    echo esc_html__( 'Order Summary:', 'aramex-automation' ) . "\n";
    echo esc_html__( 'Order:', 'aramex-automation' ) . ' #' . esc_html( $order->get_order_number() ) . "\n";
    echo esc_html__( 'Date:', 'aramex-automation' ) . ' ' . esc_html( wc_format_datetime( $order->get_date_created() ) ) . "\n";
    echo esc_html__( 'Total:', 'aramex-automation' ) . ' ' . wp_strip_all_tags( $order->get_formatted_order_total() ) . "\n";
    echo esc_html__( 'Payment method:', 'aramex-automation' ) . ' ' . wp_strip_all_tags( $order->get_payment_method_title() ) . "\n\n";

    echo esc_html__( 'Customer Details:', 'aramex-automation' ) . "\n";
    echo esc_html__( 'Name:', 'aramex-automation' ) . ' ' . esc_html( $order->get_formatted_billing_full_name() ) . "\n";
    echo esc_html__( 'Email:', 'aramex-automation' ) . ' ' . esc_html( $order->get_billing_email() ) . "\n";
    echo esc_html__( 'Phone:', 'aramex-automation' ) . ' ' . esc_html( $order->get_billing_phone() ) . "\n\n";

    if ( $additional_content ) {
        echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) ) . "\n\n";
    }
} else {
    echo esc_html( wp_strip_all_tags( $shipment_details ) ) . "\n\n";
}

echo "\n----------------------------------------\n\n";

echo esc_html( wp_strip_all_tags( $email_footer_text ) ) . "\n\n";
echo esc_html( wp_strip_all_tags( $email_footer_text ) );
