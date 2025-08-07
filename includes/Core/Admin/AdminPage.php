<?php

namespace AramexAutomation\Core\Admin;

use AramexAutomation\Core\Admin\Handlers\FormHandler;
use AramexAutomation\Core\Admin\Handlers\BulkHandler;
use AramexAutomation\Core\Admin\Handlers\SettingsHandler;

/**
 * Admin Page Handler
 */
class AdminPage
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->initHandlers();
    }

    /**
     * Initialize form handlers
     */
    private function initHandlers()
    {
        new FormHandler();
        new BulkHandler();
        new SettingsHandler();
    }

    /**
     * Render admin page
     */
    public function render()
    {
        // Check if user has permission
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        // Determine current tab based on URL parameter
        $current_tab = $_GET['tab'] ?? 'settings';

        // Include the admin page template
        include ARAMEX_AUTOMATION_PLUGIN_PATH . 'templates/admin/admin-page.php';
    }
} 