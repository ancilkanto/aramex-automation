<?php

namespace AramexAutomation\Core\Admin;

/**
 * Admin Menu Handler
 */
class AdminMenu
{
    /**
     * Constructor
     */
    public function __construct()
    {
        add_action('admin_menu', [$this, 'addAdminMenu']);
    }

    /**
     * Add admin menu page
     */
    public function addAdminMenu()
    {
        add_submenu_page(
            'woocommerce', // Parent slug (WooCommerce)
            'Aramex Shipment Automation', // Page title
            'Aramex Shipment Automation', // Menu title
            'manage_woocommerce', // Capability
            'aramex-shipment-automation', // Menu slug
            [new AdminPage(), 'render'] // Callback function
        );
    }
} 