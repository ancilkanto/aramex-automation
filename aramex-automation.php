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
    // Debug: Log plugin loading attempt
    file_put_contents(plugin_dir_path(__FILE__) . 'debug-api.log', 
        date('Y-m-d H:i:s') . ' - Main plugin file: plugins_loaded action triggered' . "\n", 
        FILE_APPEND);
    
    if (class_exists('AramexAutomation\\Plugin')) {
        file_put_contents(plugin_dir_path(__FILE__) . 'debug-api.log', 
            date('Y-m-d H:i:s') . ' - Main plugin file: Plugin class found, initializing...' . "\n", 
            FILE_APPEND);
        new AramexAutomation\Plugin();
    } else {
        file_put_contents(plugin_dir_path(__FILE__) . 'debug-api.log', 
            date('Y-m-d H:i:s') . ' - Main plugin file: Plugin class NOT found!' . "\n", 
            FILE_APPEND);
    }
}); 