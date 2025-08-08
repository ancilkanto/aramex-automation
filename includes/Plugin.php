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

        // Hook: send shipment email when status changes to awaiting-shipment (configurable)
        add_action('woocommerce_order_status_changed', function ($order_id, $old_status, $new_status, $order) {
            try {
                // Only proceed if auto email is enabled and trigger is set to 'status_change'
                if (
                    get_option('aramex_automation_auto_email', '1') !== '1' ||
                    get_option('aramex_automation_email_trigger', 'creation') !== 'status_change'
                ) {
                    return;
                }

                // Normalize status slugs
                $old_status = ltrim((string)$old_status, 'wc-');
                $new_status = ltrim((string)$new_status, 'wc-');

                // Send when new status becomes awaiting-shipment regardless of previous
                if ($new_status === 'awaiting-shipment' && $old_status !== $new_status) {
                    $order = wc_get_order($order_id);
                    if (!$order) {
                        return;
                    }
                    $tracking_number = $order->get_meta('_aramex_tracking_number');
                    if (!$tracking_number) {
                        // Try to hydrate tracking from order notes (e.g., "AWB No. 123 - Order No. 456")
                        if (function_exists('wc_get_order_notes')) {
                            $notes = wc_get_order_notes(['order_id' => $order_id]);
                            foreach ($notes as $note) {
                                $content = is_object($note) && isset($note->content) ? $note->content : (string)$note;
                                if (preg_match('/AWB\s*No\.\s*(\d+)/i', $content, $m)) {
                                    $tracking_number = $m[1];
                                    $order->update_meta_data('_aramex_tracking_number', $tracking_number);
                                    $order->save();
                                    break;
                                }
                            }
                        }
                    }
                    if ($tracking_number) {
                        \AramexAutomation\Core\Email\EmailManager::sendShipmentEmail($order, $tracking_number);
                    } else {
                        // If tracking not found, do nothing; shipment might not have been created
                    }
                }
            } catch (\Throwable $e) {
                // Silent fail; avoid breaking status transitions
            }
        }, 10, 4);

        // Direct hook on status transition target for reliability
        add_action('woocommerce_order_status_awaiting-shipment', function ($order_id) {
            try {
                if (
                    get_option('aramex_automation_auto_email', '1') !== '1' ||
                    get_option('aramex_automation_email_trigger', 'creation') !== 'status_change'
                ) {
                    return;
                }
                $order = wc_get_order($order_id);
                if (!$order) {
                    return;
                }
                $tracking_number = $order->get_meta('_aramex_tracking_number');
                if (!$tracking_number) {
                    if (function_exists('wc_get_order_notes')) {
                        $notes = wc_get_order_notes(['order_id' => $order_id]);
                        foreach ($notes as $note) {
                            $content = is_object($note) && isset($note->content) ? $note->content : (string)$note;
                            if (preg_match('/AWB\s*No\.\s*(\d+)/i', $content, $m)) {
                                $tracking_number = $m[1];
                                $order->update_meta_data('_aramex_tracking_number', $tracking_number);
                                $order->save();
                                break;
                            }
                        }
                    }
                }
                if ($tracking_number) {
                    \AramexAutomation\Core\Email\EmailManager::sendShipmentEmail($order, $tracking_number);
                }
            } catch (\Throwable $e) {
                // Silent fail
            }
        });
    }
} 