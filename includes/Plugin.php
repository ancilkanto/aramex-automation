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
        // Check if the plugin file exists
        $plugin_file = 'aramex-shipping-woocommerce/aramex-shipping.php';
        $plugin_path = WP_PLUGIN_DIR . '/' . $plugin_file;
        
        if (!file_exists($plugin_path)) {
            add_action('admin_notices', [$this, 'adminNotice']);
            return false;
        }
        
        // Check if plugin is active
        if (!is_plugin_active($plugin_file)) {
            add_action('admin_notices', [$this, 'adminNotice']);
            return false;
        }
        
        return true;
    }
    
    /**
     * Register custom order status for awaiting shipment
     */
    public function registerCustomOrderStatus()
    {
        // Register the "awaiting-shipment" status
        register_post_status('awaiting-shipment', array(
            'label' => _x('Awaiting Shipment', 'Order status', 'aramex-automation'),
            'public' => true,
            'exclude_from_search' => false,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => true,
            'label_count' => _n_noop('Awaiting Shipment <span class="count">(%s)</span>', 'Awaiting Shipment <span class="count">(%s)</span>', 'aramex-automation')
        ));
    }
    
    /**
     * Add custom order status to WooCommerce order statuses list
     */
    public function addCustomOrderStatus($order_statuses)
    {
        $order_statuses['wc-awaiting-shipment'] = _x('Awaiting Shipment', 'Order status', 'aramex-automation');
        return $order_statuses;
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
        // Initialize core components
        new Core\DependencyChecker();
        new Core\Assets();
        new Core\Admin\AdminMenu();
        new Core\Admin\AdminPage();
        new Core\Shipment\ShipmentCreator();
        new Core\Shipment\PickupScheduler();
        new Core\Email\EmailManager();
        new Core\Logging\ShipmentLogger();
        new Core\Cron\CronAutomation();
        
        // Register custom order status
        add_action('init', [$this, 'registerCustomOrderStatus']);
        add_filter('wc_order_statuses', [$this, 'addCustomOrderStatus']);
    }
} 