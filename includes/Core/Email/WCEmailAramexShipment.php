<?php

namespace AramexAutomation\Core\Email;

if (!defined('ABSPATH')) {
    exit;
}

if (class_exists('\\WC_Email')) {
    class WCEmailAramexShipment extends \WC_Email
    {
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

            parent::__construct();

            $this->subject = __('Your order #{order_number} has been shipped', 'aramex-automation');
            $this->heading = __('Your order has been shipped!', 'aramex-automation');

            add_filter('woocommerce_email_enabled_' . $this->id, '__return_true');
        }

        /**
         * Get email subject.
         */
        public function get_default_subject()
        {
            return $this->subject;
        }

        /**
         * Get email heading.
         */
        public function get_default_heading()
        {
            return $this->heading;
        }

        /**
         * Get heading with bilingual support.
         */
        public function get_heading()
        {
            return apply_filters('woocommerce_email_heading_' . $this->id, $this->format_string($this->get_option('heading')), $this->object);
        }

        /**
         * Trigger the sending of this email.
         *
         * @param int $orderId
         * @param \WC_Order|false $order
         * @param string $trackingNumber
         */
        public function trigger($orderId, $order = false, $trackingNumber = '')
        {
            $this->setup_locale();

            if ($orderId && !is_a($order, '\\WC_Order')) {
                $order = wc_get_order($orderId);
            }

            if (is_a($order, '\\WC_Order')) {
                $this->object = $order;
                $this->recipient = $order->get_billing_email();
                $this->find['order-number'] = '{order_number}';
                $this->replace['order-number'] = $this->object->get_order_number();
                $this->find['order-date'] = '{order_date}';
                $this->replace['order-date'] = wc_format_datetime($this->object->get_date_created());
                $this->find['tracking-number'] = '{tracking_number}';
                $this->replace['tracking-number'] = $trackingNumber;

                if (empty($this->replace['tracking-number'])) {
                    $metaTracking = $this->object->get_meta('_aramex_tracking_number');
                    if (!empty($metaTracking)) {
                        $this->replace['tracking-number'] = $metaTracking;
                    }
                }
            }

            if (!$this->is_enabled() || !$this->get_recipient()) {
                $this->restore_locale();
                return;
            }

            add_filter('wp_mail_content_type', function () {
                return 'text/html';
            });

            $this->send($this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments());

            remove_all_filters('wp_mail_content_type');
            $this->restore_locale();
        }

        /**
         * Get content html.
         */
        public function get_content_html()
        {
            $trackingNumber = isset($this->replace['tracking-number'])
                ? $this->replace['tracking-number']
                : (is_object($this->object) ? $this->object->get_meta('_aramex_tracking_number') : '');

            return wc_get_template_html(
                $this->template_html,
                array(
                    'order' => $this->object,
                    'email_heading' => $this->get_heading(),
                    'email_heading_arabic' => $this->get_option('heading_arabic'),
                    'sent_to_admin' => false,
                    'plain_text' => false,
                    'email' => $this,
                    'tracking_number' => $trackingNumber,
                ),
                '',
                $this->template_base
            );
        }

        /**
         * Get content plain.
         */
        public function get_content_plain()
        {
            $trackingNumber = isset($this->replace['tracking-number'])
                ? $this->replace['tracking-number']
                : (is_object($this->object) ? $this->object->get_meta('_aramex_tracking_number') : '');

            return wc_get_template_html(
                $this->template_plain,
                array(
                    'order' => $this->object,
                    'email_heading' => $this->get_heading(),
                    'email_heading_arabic' => $this->get_option('heading_arabic'),
                    'sent_to_admin' => false,
                    'plain_text' => true,
                    'email' => $this,
                    'tracking_number' => $trackingNumber,
                ),
                '',
                $this->template_base
            );
        }

        /**
         * Ensure headers include HTML content type.
         */
        public function get_headers()
        {
            $headers = parent::get_headers();
            if (!is_array($headers)) {
                $headers = array();
            }
            $headers[] = 'Content-Type: text/html; charset=UTF-8';
            return $headers;
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
                'heading_arabic' => array(
                    'title' => __('Email heading (Arabic)', 'aramex-automation'),
                    'type' => 'text',
                    'description' => __('Arabic version of the email heading', 'aramex-automation'),
                    'placeholder' => __('Arabic heading here', 'aramex-automation'),
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
