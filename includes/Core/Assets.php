<?php

namespace AramexAutomation\Core;

/**
 * Assets Manager
 */
class Assets
{
    /**
     * Constructor
     */
    public function __construct()
    {
        add_action('wp_enqueue_scripts', [$this, 'enqueueScripts']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueScripts']);
    }

    /**
     * Enqueue scripts and styles
     */
    public function enqueueScripts()
    {
        // Only enqueue if dependencies are met
        if (!is_plugin_active('aramex-shipping-woocommerce/aramex-shipping.php')) {
            return;
        }

        // Enqueue main.js
        wp_enqueue_script(
            'aramex-automation-main',
            ARAMEX_AUTOMATION_PLUGIN_URL . 'assets/js/main.js',
            ['jquery'],
            ARAMEX_AUTOMATION_VERSION,
            true
        );

        // Localize script for AJAX
        wp_localize_script('aramex-automation-main', 'aramexAutomation', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aramex_automation_nonce'),
            'strings' => [
                'error' => __('An error occurred', 'aramex-automation'),
                'success' => __('Operation completed successfully', 'aramex-automation')
            ]
        ]);
    }
} 