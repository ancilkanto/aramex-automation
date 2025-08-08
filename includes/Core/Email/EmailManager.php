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
        
        // Log that EmailManager is being initialized
        error_log('Aramex Automation: EmailManager initialized');
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
                $mailer->emails['aramex_shipment'] = new WC_Email_Aramex_Shipment();
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
        // Check if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            error_log('Aramex Automation: WooCommerce not active when adding email class');
            return $emailClasses;
        }
        
        // Check if WC_Email class exists
        if (!class_exists('WC_Email')) {
            error_log('Aramex Automation: WC_Email class not found when adding email class');
            return $emailClasses;
        }
        
        try {
            $emailClasses['aramex_shipment'] = new WC_Email_Aramex_Shipment();
            error_log('Aramex Automation: Email class added to WooCommerce successfully');
        } catch (\Throwable $e) {
            error_log('Aramex Automation: Failed to add email class: ' . $e->getMessage());
        }
        return $emailClasses;
    }

    /**
     * Send Aramex shipment email
     */
    public static function sendShipmentEmail($order, $tracking_number)
    {
        // Check if email was already sent for this tracking number
        $email_sent_key = 'aramex_email_sent_' . $order->get_id() . '_' . $tracking_number;
        if (get_transient($email_sent_key)) {
            error_log('Aramex Automation: Email already sent for order #' . $order->get_id() . ' with tracking #' . $tracking_number);
            return true;
        }

        try {
            // Log that we're attempting to send email
            error_log('Aramex Automation: Attempting to send email for order #' . $order->get_id() . ' with tracking #' . $tracking_number);
            
            // Get the email instance
            $mailer = WC()->mailer();
            $emails = $mailer->get_emails();
            $email = $emails['aramex_shipment'] ?? null;

            if ($email) {
                error_log('Aramex Automation: Email class found, triggering email for order #' . $order->get_id());
                
                // Trigger the email
                $email->trigger($order->get_id(), $order, $tracking_number);
                
                // Set transient to prevent duplicate emails (expires in 1 hour)
                set_transient($email_sent_key, true, HOUR_IN_SECONDS);
                
                $order->add_order_note('Tracking information email sent to customer');
                
                error_log('Aramex Automation: Email sent successfully for order #' . $order->get_id());
                return true;
            } else {
                error_log('Aramex Automation: Email class not found in mailer. Available emails: ' . implode(', ', array_keys($emails)));
                
                // Fallback: Try using the simple CustomerEmail class
                error_log('Aramex Automation: Trying fallback email method');
                $customer_email = new CustomerEmail();
                $result = $customer_email->sendCustomerEmail($order, $tracking_number);
                
                if ($result) {
                    error_log('Aramex Automation: Fallback email sent successfully');
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