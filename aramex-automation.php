<?php
/*
Plugin Name: Aramex Automation
Plugin URI: https://github.com/ancilkanto/aramex-automation
Description: Automation plugin for Aramex shipping functionality. Requires aramex-shipping-woocommerce plugin to be active.
Version: 1.0.0
Author: Ancil K Anto
Author URI: https://github.com/ancilkanto
License: GPL3
License URI: https://www.gnu.org/licenses/gpl-3.0.html
Text Domain: aramex-automation
Domain Path: /languages
*/

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('ARAMEX_AUTOMATION_VERSION', '1.0.0');
define('ARAMEX_AUTOMATION_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ARAMEX_AUTOMATION_PLUGIN_PATH', plugin_dir_path(__FILE__));

// Autoloader
require_once ARAMEX_AUTOMATION_PLUGIN_PATH . 'vendor/autoload.php';

// Initialize the plugin
add_action('plugins_loaded', function() {
    if (class_exists('AramexAutomation\\Plugin')) {
        new AramexAutomation\Plugin();
    }
});

// Activation hook
register_activation_hook(__FILE__, function() {
    // Schedule cron job if automation is enabled
    if (get_option('aramex_automation_auto_cron_enabled', '0') === '1') {
        $cron_hour = get_option('aramex_automation_cron_hour', '9');
        $cron_minute = get_option('aramex_automation_cron_minute', '0');
        
        $next_run = strtotime("today {$cron_hour}:{$cron_minute}:00");
        if ($next_run <= time()) {
            $next_run = strtotime("tomorrow {$cron_hour}:{$cron_minute}:00");
        }
        
        wp_schedule_event($next_run, 'daily', 'aramex_automation_daily_cron');
    }
});

// Deactivation hook
register_deactivation_hook(__FILE__, function() {
    // Clear scheduled cron job
    wp_clear_scheduled_hook('aramex_automation_daily_cron');
}); 