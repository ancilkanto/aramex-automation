<?php

namespace AramexAutomation\Core\Admin\Handlers;

use AramexAutomation\Core\Shipment\ShipmentCreator;
use AramexAutomation\Core\Logging\ShipmentLogger;

/**
 * Bulk Handler for Multiple Shipment Creation
 */
class BulkHandler
{
    /**
     * Constructor
     */
    public function __construct()
    {
        add_action('admin_init', [$this, 'handleBulkSubmission']);
    }

    /**
     * Handle bulk form submission
     */
    public function handleBulkSubmission()
    {
        if (!isset($_POST['aramex_automation_bulk_action'])) {
            return;
        }

        if (!wp_verify_nonce($_POST['aramex_automation_bulk_nonce'], 'aramex_automation_bulk_nonce')) {
            wp_die('Security check failed');
        }

        if (isset($_POST['bulk_order_ids']) && !empty($_POST['bulk_order_ids'])) {
            $order_ids_text = sanitize_textarea_field($_POST['bulk_order_ids']);
            $order_ids = array_map('intval', array_filter(explode(',', str_replace("\n", ',', $order_ids_text))));
            
            $results = [];
            $aramex_settings = get_option('woocommerce_aramex_settings');
            
            $shipment_creator = new ShipmentCreator();
            $logger = new ShipmentLogger();
            
            foreach ($order_ids as $order_id) {
                $order = wc_get_order($order_id);
                if ($order) {
                    $result = $shipment_creator->createShipment($order, $aramex_settings);
                    $results[$order_id] = $result;
                    $logger->logShipment($order_id, $result);
                }
            }
            
            set_transient('aramex_automation_bulk_results', $results, 60);
            
            // Redirect back to bulk operations tab
            wp_redirect(admin_url('admin.php?page=aramex-shipment-automation&tab=bulk-operations'));
            exit;
        }
    }
} 