<?php

namespace AramexAutomation\Core\Email;

/**
 * Email Manager
 */
class EmailManager
{
    /**
     * Constructor
     */
    public function __construct()
    {
        // Add the email class directly to WooCommerce email classes
        add_filter('woocommerce_email_classes', [$this, 'addEmailClass']);
        
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
            
            if (isset($emails['aramex_shipment'])) {
                wp_die('SUCCESS: aramex_shipment email class found in mailer');
            } else {
                wp_die('ERROR: aramex_shipment email class NOT found in mailer');
            }
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
        
        try {
            $emailClasses['aramex_shipment'] = new WCEmailAramexShipment();
        } catch (\Throwable $e) {
            // Keep failure log only
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
} 