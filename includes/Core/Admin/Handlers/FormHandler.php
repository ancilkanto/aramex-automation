<?php

namespace AramexAutomation\Core\Admin\Handlers;

use AramexAutomation\Core\Shipment\ShipmentCreator;
use AramexAutomation\Core\Logging\ShipmentLogger;

/**
 * Form Handler for Single Shipment Creation
 */
class FormHandler
{
    /**
     * Constructor
     */
    public function __construct()
    {
        add_action('admin_init', [$this, 'handleFormSubmission']);
    }

    /**
     * Handle form submission
     */
    public function handleFormSubmission()
    {
        // Debug: Log form submission attempt
        file_put_contents(ARAMEX_AUTOMATION_PLUGIN_PATH . 'debug-api.log', 
            date('Y-m-d H:i:s') . ' - FormHandler: Checking for form submission...' . "\n", 
            FILE_APPEND);
        
        if (!isset($_POST['aramex_automation_action'])) {
            file_put_contents(ARAMEX_AUTOMATION_PLUGIN_PATH . 'debug-api.log', 
                date('Y-m-d H:i:s') . ' - FormHandler: No action found in POST data' . "\n", 
                FILE_APPEND);
            return;
        }

        // Check if this form submission has already been processed
        $form_key = 'aramex_form_processed_' . md5(serialize($_POST));
        if (get_transient($form_key)) {
            file_put_contents(ARAMEX_AUTOMATION_PLUGIN_PATH . 'debug-api.log', 
                date('Y-m-d H:i:s') . ' - FormHandler: Form already processed, skipping...' . "\n", 
                FILE_APPEND);
            return;
        }
        
        // Mark this form submission as processed (expires in 60 seconds)
        set_transient($form_key, true, 60);

        if (!wp_verify_nonce($_POST['aramex_automation_nonce'], 'aramex_automation_nonce')) {
            wp_die('Security check failed');
        }

        if (isset($_POST['order_id']) && !empty($_POST['order_id'])) {
            $order_id = intval($_POST['order_id']);
            
            // Get Aramex settings
            $aramex_settings = get_option('woocommerce_aramex_settings');
            if (!$aramex_settings) {
                $this->addAdminNotice('Aramex settings not found', 'error');
                return;
            }
            
            // Get order
            $order = wc_get_order($order_id);
            if (!$order) {
                $this->addAdminNotice('Order not found: ' . $order_id, 'error');
                return;
            }
            
            // Create shipment
            $shipment_creator = new ShipmentCreator();
            $result = $shipment_creator->createShipment($order, $aramex_settings);
            
            // Debug: Log the result
            error_log('Aramex Automation FormHandler Result: ' . print_r($result, true));
            
            // Store result for display
            set_transient('aramex_automation_result', $result, 60);
            
            // Log the shipment attempt
            $logger = new ShipmentLogger();
            $logger->logShipment($order_id, $result);
            
            // Redirect back to quick shipment tab
            wp_redirect(admin_url('admin.php?page=aramex-shipment-automation&tab=quick-shipment'));
            exit;
        }
    }

    /**
     * Add admin notice
     */
    private function addAdminNotice($message, $type = 'info')
    {
        $notices = get_option('aramex_automation_admin_notices', []);
        $notices[] = [
            'message' => $message,
            'type' => $type,
            'timestamp' => time()
        ];
        update_option('aramex_automation_admin_notices', $notices);
    }
} 