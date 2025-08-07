<?php

namespace AramexAutomation\Core;

/**
 * Dependency Checker
 */
class DependencyChecker
{
    /**
     * Constructor
     */
    public function __construct()
    {
        add_action('admin_init', [$this, 'checkDependencies']);
    }

    /**
     * Check dependencies
     */
    public function checkDependencies()
    {
        if (!is_plugin_active('aramex-shipping-woocommerce/aramex-shipping.php')) {
            add_action('admin_notices', [$this, 'adminNotice']);
        }
    }

    /**
     * Admin notice
     */
    public function adminNotice()
    {
        ?>
        <div class="notice notice-error">
            <p><?php _e('Aramex Automation requires the Aramex Shipping WooCommerce plugin to be installed and activated.', 'aramex-automation'); ?></p>
        </div>
        <?php
    }
} 