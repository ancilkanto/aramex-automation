<?php

namespace AramexAutomation;

/**
 * Main Plugin Class
 */
class Plugin
{
    /**
     * Plugin instance
     */
    private static $instance = null;

    /**
     * Get plugin instance
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
    public function __construct()
    {
        $this->init();
    }

    /**
     * Initialize the plugin
     */
    private function init()
    {
        // Check dependencies first
        if (!$this->checkDependencies()) {
            return;
        }

        // Load text domain
        load_plugin_textdomain('aramex-automation', false, dirname(plugin_basename(ARAMEX_AUTOMATION_PLUGIN_PATH)) . '/languages');

        // Initialize components
        $this->initComponents();
    }

    /**
     * Check if required plugins are active
     */
    private function checkDependencies()
    {
        if (!is_plugin_active('aramex-shipping-woocommerce/aramex-shipping.php')) {
            add_action('admin_notices', [$this, 'adminNotice']);
            return false;
        }
        return true;
    }

    /**
     * Admin notice for missing dependency
     */
    public function adminNotice()
    {
        ?>
        <div class="notice notice-error">
            <p><?php _e('Aramex Automation requires the Aramex Shipping WooCommerce plugin to be installed and activated.', 'aramex-automation'); ?></p>
        </div>
        <?php
    }

    /**
     * Initialize plugin components
     */
    private function initComponents()
    {
        // Debug: Log plugin initialization
        error_log('Aramex Automation: Plugin initializing...');
        
        // Initialize core components
        new Core\DependencyChecker();
        new Core\Assets();
        new Core\Admin\AdminMenu();
        new Core\Admin\AdminPage();
        new Core\Shipment\ShipmentCreator();
        new Core\Shipment\PickupScheduler();
        new Core\Email\CustomerEmail();
        new Core\Logging\ShipmentLogger();
        
        error_log('Aramex Automation: Plugin initialized successfully');
    }
} 