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
            // Load the email class if not already loaded
            if (!class_exists('WC_Email_Aramex_Shipment')) {
                require_once ARAMEX_AUTOMATION_PLUGIN_PATH . 'includes/Core/Email/class-wc-email-aramex-shipment.php';
            }
            
            if (class_exists('WC_Email_Aramex_Shipment')) {
                try {
                    $mailer->emails['aramex_shipment'] = new \WC_Email_Aramex_Shipment();
                } catch (Exception $e) {
                    // Silent fail - email class will be added later if needed
                }
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
            return $emailClasses;
        }
        
        // Check if WC_Email class exists
        if (!class_exists('WC_Email')) {
            return $emailClasses;
        }
        
        // Load the email class if not already loaded
        if (!class_exists('WC_Email_Aramex_Shipment')) {
            require_once ARAMEX_AUTOMATION_PLUGIN_PATH . 'includes/Core/Email/class-wc-email-aramex-shipment.php';
        }
        
        if (class_exists('WC_Email_Aramex_Shipment')) {
            try {
                $emailClasses['aramex_shipment'] = new \WC_Email_Aramex_Shipment();
            } catch (Exception $e) {
                // Silent fail - email class will be added later if needed
            }
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
            // Ensure the email class is loaded
            if (!class_exists('WC_Email_Aramex_Shipment')) {
                require_once ARAMEX_AUTOMATION_PLUGIN_PATH . 'includes/Core/Email/class-wc-email-aramex-shipment.php';
            }

            // Get the email instance
            $mailer = WC()->mailer();
            $email = $mailer->get_emails()['aramex_shipment'] ?? null;

            if ($email) {
                // Trigger the email
                $email->trigger($order->get_id(), $order, $tracking_number);
                
                // Set transient to prevent duplicate emails (expires in 1 hour)
                set_transient($email_sent_key, true, HOUR_IN_SECONDS);
                
                $order->add_order_note('Tracking information email sent to customer');
                
                return true;
            } else {
                error_log('Aramex Automation: Email class not found in mailer');
                return false;
            }
        } catch (\Exception $e) {
            error_log('Aramex Automation: Email error for order #' . $order->get_id() . ' - ' . $e->getMessage());
            return false;
        }
    }
} 