<?php

namespace AramexAutomation\Core\Email;

use AramexAutomation\Core\Email\WCEmailAramexShipment;
use AramexAutomation\Core\Email\WCEmailOrderShipped;
use AramexAutomation\Core\Email\WCEmailAdminShipmentNotification;

/**
 * Email Manager
 */
class EmailManager
{
    /**
     * Singleton instance
     */
    private static $instance = null;
    
    /**
     * Track if filter has been registered
     */
    private static $filter_registered = false;

    /**
     * Get singleton instance
     */
    public static function getInstance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct()
    {
        // Only register the filter once
        if (!self::$filter_registered) {
            add_filter('woocommerce_email_classes', [$this, 'addEmailClass']);
            self::$filter_registered = true;
        }
        
        // Also try adding it on woocommerce_init to ensure WooCommerce is loaded
        add_action('woocommerce_init', [$this, 'ensureEmailClassAdded']);
        
        // Add a test action to verify email class is working
        add_action('admin_init', [$this, 'testEmailClass']);
    }


    
        /**
     * Test method to verify email class is working
     */
    public function testEmailClass()
    {
        if (isset($_GET['test_aramex_email']) && current_user_can('manage_options')) {
            $mailer = WC()->mailer();
            $emails = $mailer->get_emails();
            
            $aramex_shipment = isset($emails['aramex_shipment']) ? 'FOUND' : 'NOT FOUND';
            $order_shipped = isset($emails['order_shipped']) ? 'FOUND' : 'NOT FOUND';
            
            wp_die("Email Classes Status:<br>
                    aramex_shipment: {$aramex_shipment}<br>
                    order_shipped: {$order_shipped}");
        }
    }
    
    /**
     * Ensure email class is added after WooCommerce is fully loaded
     */
    public function ensureEmailClassAdded()
    {
        // Check if the email class is already in the mailer
        $mailer = WC()->mailer();
        $emails = $mailer->get_emails();
        
        if (!isset($emails['aramex_shipment'])) {
            try {
                $mailer->emails['aramex_shipment'] = new WCEmailAramexShipment();
            } catch (\Throwable $e) {
                // Silent fail - email class will be added later if needed
            }
        }
        
        if (!isset($emails['order_shipped'])) {
            try {
                $mailer->emails['order_shipped'] = new WCEmailOrderShipped();
            } catch (\Throwable $e) {
                // Silent fail - email class will be added later if needed
            }
        }
        
        if (!isset($emails['aramex_admin_shipment_notification'])) {
            try {
                $mailer->emails['aramex_admin_shipment_notification'] = new WCEmailAdminShipmentNotification();
            } catch (\Throwable $e) {
                // Silent fail - email class will be added later if needed
            }
        }
    }

    /**
     * Add email class to WooCommerce
     */
    public function addEmailClass($emailClasses)
    {
        // Ensure WooCommerce and WC_Email are available
        if (!class_exists('WooCommerce') || !class_exists('WC_Email')) {
            return $emailClasses;
        }
        
        // Check if email classes are already added to avoid repeated instantiation
        static $classes_added = false;
        if ($classes_added) {
            return $emailClasses;
        }
        
        try {
            $emailClasses['aramex_shipment'] = new WCEmailAramexShipment();
            $emailClasses['order_shipped'] = new WCEmailOrderShipped();
            $emailClasses['aramex_admin_shipment_notification'] = new WCEmailAdminShipmentNotification();
            
            $classes_added = true;
        } catch (\Throwable $e) {
            // Log only actual failures
            error_log('Aramex Automation: Failed to add email class: ' . $e->getMessage());
        }
        return $emailClasses;
    }

    /**
     * Send Aramex shipment email
     */
    public static function sendShipmentEmail($order, $trackingNumber)
    {
        // Check if email was already sent for this tracking number
        $emailSentKey = 'aramex_email_sent_' . $order->get_id() . '_' . $trackingNumber;
        if (get_transient($emailSentKey)) {
            return true;
        }

        try {
            // Get the email instance
            $mailer = WC()->mailer();
            $emails = $mailer->get_emails();
            $email = $emails['aramex_shipment'] ?? null;

            if ($email) {
                // Trigger the email
                $email->trigger($order->get_id(), $order, $trackingNumber);
                
                // Set transient to prevent duplicate emails (expires in 1 hour)
                set_transient($emailSentKey, true, HOUR_IN_SECONDS);
                
                $order->add_order_note('Tracking information email sent to customer');
                return true;
            } else {
                error_log('Aramex Automation: Email class not found in mailer. Available emails: ' . implode(', ', array_keys($emails)));
                
                // Fallback: Try using the simple CustomerEmail class
                $customerEmail = new CustomerEmail();
                $result = $customerEmail->sendCustomerEmail($order, $trackingNumber);
                
                if ($result) {
                    return true;
                } else {
                    error_log('Aramex Automation: Fallback email also failed');
                    return false;
                }
            }
        } catch (\Exception $e) {
            error_log('Aramex Automation: Email error for order #' . $order->get_id() . ' - ' . $e->getMessage());
            error_log('Aramex Automation: Email error stack trace: ' . $e->getTraceAsString());
            return false;
        }
    }
    
    /**
     * Send Order Shipped email
     */
    public static function sendOrderShippedEmail($order, $trackingNumber)
    {
        // Check if email was already sent for this tracking number
        $emailSentKey = 'order_shipped_email_sent_' . $order->get_id() . '_' . $trackingNumber;
        if (get_transient($emailSentKey)) {
            return true;
        }

        try {
            // Get the email instance
            $mailer = WC()->mailer();
            $emails = $mailer->get_emails();
            $email = $emails['order_shipped'] ?? null;

            if ($email) {
                // Trigger the email
                $email->trigger($order->get_id(), $order, $trackingNumber);
                
                // Set transient to prevent duplicate emails (expires in 1 hour)
                set_transient($emailSentKey, true, HOUR_IN_SECONDS);
                
                $order->add_order_note('Order shipped email sent to customer');
                return true;
            } else {
                error_log('Aramex Automation: Order Shipped email class not found in mailer. Available emails: ' . implode(', ', array_keys($emails)));
                return false;
            }
        } catch (\Exception $e) {
            error_log('Aramex Automation: Order Shipped email error for order #' . $order->get_id() . ' - ' . $e->getMessage());
            error_log('Aramex Automation: Order Shipped email error stack trace: ' . $e->getTraceAsString());
            return false;
        }
    }

    /**
     * Send admin notification email using WooCommerce email system
     * 
     * @param string $subject Email subject
     * @param string $message Email message
     * @param WC_Order $order Optional order object for additional context
     * @return bool Success status
     */
    public static function sendAdminNotification($subject, $message, $order = null)
    {
        $admin_emails = \AramexAutomation\Plugin::getAdminNotificationEmails();
        
        if (empty($admin_emails)) {
            return false; // No admin emails configured
        }

        // Check for duplicate prevention if order is provided
        if ($order) {
            $order_id = $order->get_id();
            $tracking_number = $order->get_meta('_aramex_tracking_number');
            
            // For successful shipment notifications, only send if we have tracking details
            $is_successful_shipment_notification = strpos($subject, 'Shipment Created Successfully') !== false;
            if ($is_successful_shipment_notification && empty($tracking_number)) {
                error_log('Aramex Automation: Skipping admin notification for order #' . $order_id . ' - no tracking number available yet');
                return true; // Skip notification without tracking details
            }
            
            // Create a unique key for this admin notification
            $admin_notification_key = 'aramex_admin_notification_sent_' . $order_id . '_' . md5($subject . $message);
            
            // Check if this specific admin notification was already sent
            if (get_transient($admin_notification_key)) {
                error_log('Aramex Automation: Admin notification already sent for order #' . $order_id . ' with subject: ' . $subject);
                return true; // Already sent, return success
            }
        }

        try {
            // Try to use WooCommerce email system first
            if (class_exists('WooCommerce') && WC()->mailer()) {
                $mailer = WC()->mailer();
                $emails = $mailer->get_emails();
                
                if (isset($emails['aramex_admin_shipment_notification'])) {
                    $email = $emails['aramex_admin_shipment_notification'];
                    
                    // Extract tracking number and label URL from message if order is available
                    $tracking_number = '';
                    $label_url = '';
                    
                    if ($order) {
                        $tracking_number = $order->get_meta('_aramex_tracking_number');
                        $label_url = $order->get_meta('_aramex_label_url');
                    }
                    
                    // Send email to each admin email address
                    $sent_count = 0;
                    foreach ($admin_emails as $admin_email) {
                        try {
                            // Temporarily set the recipient for this email
                            $email->recipient = $admin_email;
                            
                            // Send email using WooCommerce system
                            $email->trigger($order ? $order->get_id() : 0, $order, $tracking_number, $label_url, $message, '');
                            $sent_count++;
                        } catch (\Exception $e) {
                            error_log('Aramex Automation: WooCommerce email error for ' . $admin_email . ' - ' . $e->getMessage());
                            // Continue with other emails
                        }
                    }
                    
                    if ($sent_count > 0 && $order) {
                        // Set transient to prevent duplicate admin notifications (expires in 24 hours)
                        set_transient($admin_notification_key, true, DAY_IN_SECONDS);
                    }
                    return $sent_count > 0;
                } else {
                    error_log('Aramex Automation: Admin shipment notification email class not found in WooCommerce mailer');
                }
            } else {
                error_log('Aramex Automation: WooCommerce or mailer not available');
            }
            
            // Fallback to wp_mail if WooCommerce email system is not available
            $headers = ['Content-Type: text/html; charset=UTF-8'];
            
            // Add order context if available
            if ($order) {
                $order_info = sprintf(
                    '<p><strong>Order Details:</strong><br>
                    Order ID: #%s<br>
                    Customer: %s %s<br>
                    Total: %s</p>',
                    $order->get_id(),
                    $order->get_billing_first_name(),
                    $order->get_billing_last_name(),
                    $order->get_formatted_order_total()
                );
                $message = $order_info . $message;
            }

            $sent_count = 0;
            foreach ($admin_emails as $email) {
                $result = wp_mail($email, $subject, $message, $headers);
                if ($result) {
                    $sent_count++;
                }
            }

            if ($sent_count > 0 && $order) {
                // Set transient to prevent duplicate admin notifications (expires in 24 hours)
                set_transient($admin_notification_key, true, DAY_IN_SECONDS);
            }

            return $sent_count > 0;
        } catch (\Exception $e) {
            error_log('Aramex Automation: Admin notification email error - ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get label URL HTML for admin notifications
     * 
     * @param WC_Order $order Order object
     * @return string HTML string with label URL if available
     */
    public static function getLabelUrlHtml($order)
    {
        $label_url = $order->get_meta('_aramex_label_url');
        if (!empty($label_url)) {
            return sprintf(
                '<p><strong>Label URL:</strong> <a href="%s" target="_blank" class="aramex-download-button" style="display: inline-block; background-color: #ef722f; color: white; text-decoration: none; padding: 12px 24px; border-radius: 6px; font-weight: bold; text-align: center; border: 2px solid #ef722f; font-size: 14px; line-height: 1.4; min-width: 200px; margin: 10px 0;">Download Shipping Label</a></p>',
                esc_url($label_url)
            );
        }
        return '';
    }
} 