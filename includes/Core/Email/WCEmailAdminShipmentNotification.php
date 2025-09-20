<?php

namespace AramexAutomation\Core\Email;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Admin Shipment Notification Email
 *
 * An email sent to admin when a shipment is created or updated.
 *
 * @class       WCEmailAdminShipmentNotification
 * @extends     WC_Email
 * @package     AramexAutomation
 * @version     1.0.0
 */
class WCEmailAdminShipmentNotification extends \WC_Email
{
    /**
     * Email properties
     */
    public $tracking_number;
    public $label_url;
    public $shipment_details;
    public $additional_content;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->id             = 'aramex_admin_shipment_notification';
        $this->customer_email = false;
        $this->title          = __( 'Aramex Admin Shipment Notification', 'aramex-automation' );
        $this->description    = __( 'This email is sent to admin users when Aramex shipments are created or updated.', 'aramex-automation' );
        $this->template_html  = 'emails/admin-shipment-notification.php';
        $this->template_plain = 'emails/plain/admin-shipment-notification.php';
        $this->template_base  = ARAMEX_AUTOMATION_PLUGIN_PATH . 'templates/';

        // Call parent constructor
        parent::__construct();

        // Set default recipient
        $this->recipient = $this->get_option( 'recipient', get_option( 'admin_email' ) );
    }

    /**
     * Get email subject.
     *
     * @return string
     */
    public function get_default_subject()
    {
        return __( '[{site_title}] Aramex Shipment Notification - Order #{order_number}', 'aramex-automation' );
    }

    /**
     * Get email heading.
     *
     * @return string
     */
    public function get_default_heading()
    {
        return __( 'Aramex Shipment Notification', 'aramex-automation' );
    }

    /**
     * Trigger the sending of this email.
     *
     * @param int    $order_id Order ID.
     * @param object $order Order object.
     * @param string $tracking_number Tracking number.
     * @param string $label_url Label URL.
     * @param string $shipment_details Shipment details.
     * @param string $additional_content Additional content.
     */
    public function trigger( $order_id, $order = null, $tracking_number = '', $label_url = '', $shipment_details = '', $additional_content = '' )
    {
        $this->setup_locale();

        if ( $order_id && ! is_a( $order, 'WC_Order' ) ) {
            $order = wc_get_order( $order_id );
        }

        if ( is_a( $order, 'WC_Order' ) ) {
            $this->object                         = $order;
            $this->find['order-number']           = '{order_number}';
            $this->replace['order-number']        = $order->get_order_number();
            $this->find['order-date']             = '{order_date}';
            $this->replace['order-date']          = wc_format_datetime( $order->get_date_created() );
            $this->find['order-total']            = '{order_total}';
            $this->replace['order-total']         = $order->get_formatted_order_total();
        }

        // Set email content variables
        $this->tracking_number     = $tracking_number;
        $this->label_url          = $label_url;
        $this->shipment_details   = $shipment_details;
        $this->additional_content = $additional_content;

        if ( ! $this->is_enabled() || ! $this->get_recipient() || $this->tracking_number == '' || $this->label_url == '' ) {
            return;
        }
        
        $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );

        $this->restore_locale();
    }

    /**
     * Get content html.
     *
     * @return string
     */
    public function get_content_html()
    {
        return wc_get_template_html(
            $this->template_html,
            array(
                'order'              => $this->object,
                'email_heading'      => $this->get_heading(),
                'sent_to_admin'      => true,
                'plain_text'         => false,
                'email'              => $this,
                'tracking_number'    => $this->tracking_number,
                'label_url'          => $this->label_url,
                'shipment_details'   => $this->shipment_details,
                'additional_content' => $this->additional_content,
                'email_footer_text'  => $this->get_footer_text(),
            ),
            '',
            $this->template_base
        );
    }

    /**
     * Get content plain.
     *
     * @return string
     */
    public function get_content_plain()
    {
        return wc_get_template_html(
            $this->template_plain,
            array(
                'order'              => $this->object,
                'email_heading'      => $this->get_heading(),
                'sent_to_admin'      => true,
                'plain_text'         => true,
                'email'              => $this,
                'tracking_number'    => $this->tracking_number,
                'label_url'          => $this->label_url,
                'shipment_details'   => $this->shipment_details,
                'additional_content' => $this->additional_content,
                'email_footer_text'  => $this->get_footer_text(),
            ),
            '',
            $this->template_base
        );
    }

    /**
     * Initialize settings form fields.
     */
    public function init_form_fields()
    {
        $this->form_fields = array(
            'enabled'    => array(
                'title'   => __( 'Enable/Disable', 'aramex-automation' ),
                'type'    => 'checkbox',
                'label'   => __( 'Enable this email notification', 'aramex-automation' ),
                'default' => 'yes',
            ),
            'recipient'  => array(
                'title'       => __( 'Recipient(s)', 'aramex-automation' ),
                'type'        => 'text',
                'description' => sprintf( __( 'Enter recipients (comma separated) for this email. Defaults to <code>%s</code>.', 'aramex-automation' ), esc_html( get_option( 'admin_email' ) ) ),
                'placeholder' => '',
                'default'     => '',
                'desc_tip'    => true,
            ),
            'subject'    => array(
                'title'       => __( 'Subject', 'aramex-automation' ),
                'type'        => 'text',
                'description' => sprintf( __( 'Available placeholders: %s', 'aramex-automation' ), '<code>{site_title}, {order_number}, {order_date}, {order_total}</code>' ),
                'placeholder' => $this->get_default_subject(),
                'default'     => '',
                'desc_tip'    => true,
            ),
            'heading'    => array(
                'title'       => __( 'Email Heading', 'aramex-automation' ),
                'type'        => 'text',
                'description' => sprintf( __( 'Available placeholders: %s', 'aramex-automation' ), '<code>{site_title}</code>' ),
                'placeholder' => $this->get_default_heading(),
                'default'     => '',
                'desc_tip'    => true,
            ),
            'email_type' => array(
                'title'       => __( 'Email type', 'aramex-automation' ),
                'type'        => 'select',
                'description' => __( 'Choose which format of email to send.', 'aramex-automation' ),
                'default'     => 'html',
                'class'       => 'email_type wc-enhanced-select',
                'options'     => $this->get_email_type_options(),
                'desc_tip'    => true,
            ),
        );
    }

    /**
     * Get footer text.
     *
     * @return string
     */
    public function get_footer_text()
    {
        return get_option( 'woocommerce_email_footer_text', '' );
    }
}
