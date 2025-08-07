<?php

/**
 * Aramex Shipment Email
 *
 * An email sent to the customer when a shipment is created.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Customer Aramex Shipment Email
 */
if (class_exists('WC_Email')) {
    class WC_Email_Aramex_Shipment extends WC_Email {
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->id = 'aramex_shipment';
        $this->customer_email = true;
        $this->title = __('Aramex Shipment', 'aramex-automation');
        $this->description = __('This email is sent to customers when an Aramex shipment is created.', 'aramex-automation');
        $this->template_html = 'emails/aramex-shipment.php';
        $this->template_plain = 'emails/plain/aramex-shipment.php';
        $this->template_base = ARAMEX_AUTOMATION_PLUGIN_PATH . 'templates/';

        // Call parent constructor
        parent::__construct();

        // Other settings
        $this->subject = __('Your order #{order_number} has been shipped', 'aramex-automation');
        $this->heading = __('Your order has been shipped!', 'aramex-automation');
    }

    /**
     * Get email subject.
     *
     * @return string
     */
    public function get_default_subject()
    {
        return $this->subject;
    }

    /**
     * Get email heading.
     *
     * @return string
     */
    public function get_default_heading()
    {
        return $this->heading;
    }

    /**
     * Trigger the sending of this email.
     *
     * @param int $order_id The order ID.
     * @param WC_Order $order Order object.
     * @param string $tracking_number The tracking number.
     */
    public function trigger($order_id, $order = false, $tracking_number = '')
    {
        $this->setup_locale();

        if ($order_id && !is_a($order, 'WC_Order')) {
            $order = wc_get_order($order_id);
        }

        if (is_a($order, 'WC_Order')) {
            $this->object = $order;
            $this->find['order-number'] = '{order_number}';
            $this->replace['order-number'] = $this->object->get_order_number();
            $this->find['order-date'] = '{order_date}';
            $this->replace['order-date'] = wc_format_datetime($this->object->get_date_created());
            $this->find['tracking-number'] = '{tracking_number}';
            $this->replace['tracking-number'] = $tracking_number;
        }

        if (!$this->is_enabled() || !$this->get_recipient()) {
            return;
        }

        $this->send($this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments());

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
                'order' => $this->object,
                'email_heading' => $this->get_heading(),
                'sent_to_admin' => false,
                'plain_text' => false,
                'email' => $this,
                'tracking_number' => $this->replace['tracking-number'],
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
                'order' => $this->object,
                'email_heading' => $this->get_heading(),
                'sent_to_admin' => false,
                'plain_text' => true,
                'email' => $this,
                'tracking_number' => $this->replace['tracking-number'],
            ),
            '',
            $this->template_base
        );
    }

    /**
     * Initialise settings form fields.
     */
    public function init_form_fields()
    {
        $this->form_fields = array(
            'enabled' => array(
                'title' => __('Enable/Disable', 'woocommerce'),
                'type' => 'checkbox',
                'label' => __('Enable this email notification', 'woocommerce'),
                'default' => 'yes',
            ),
            'subject' => array(
                'title' => __('Subject', 'woocommerce'),
                'type' => 'text',
                'description' => sprintf(__('Available placeholders: %s', 'woocommerce'), '<code>{order_number}, {order_date}, {tracking_number}</code>'),
                'placeholder' => $this->get_default_subject(),
                'default' => '',
            ),
            'heading' => array(
                'title' => __('Email heading', 'woocommerce'),
                'type' => 'text',
                'description' => sprintf(__('Available placeholders: %s', 'woocommerce'), '<code>{order_number}, {order_date}, {tracking_number}</code>'),
                'placeholder' => $this->get_default_heading(),
                'default' => '',
            ),
            'email_type' => array(
                'title' => __('Email type', 'woocommerce'),
                'type' => 'select',
                'description' => __('Choose which format of email to send.', 'woocommerce'),
                'default' => 'html',
                'class' => 'email_type wc-enhanced-select',
                'options' => $this->get_email_type_options(),
            ),
        );
    }
}
} 