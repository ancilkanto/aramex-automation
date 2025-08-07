<?php

namespace AramexAutomation\Core\Admin\Handlers;

/**
 * Settings Handler
 */
class SettingsHandler
{
    /**
     * Constructor
     */
    public function __construct()
    {
        add_action('admin_init', [$this, 'handleSettingsSubmission']);
    }

    /**
     * Handle settings form submission
     */
    public function handleSettingsSubmission()
    {
        if (!isset($_POST['aramex_automation_settings_action'])) {
            return;
        }

        if (!wp_verify_nonce($_POST['aramex_automation_settings_nonce'], 'aramex_automation_settings_nonce')) {
            wp_die('Security check failed');
        }

        // Save email settings
        if (isset($_POST['auto_email_customer'])) {
            update_option('aramex_automation_auto_email', '1');
        } else {
            update_option('aramex_automation_auto_email', '0');
        }

        // Save weight unit
        if (isset($_POST['default_weight_unit'])) {
            update_option('aramex_automation_weight_unit', sanitize_text_field($_POST['default_weight_unit']));
        }

        // Save pickup settings
        if (isset($_POST['auto_schedule'])) {
            update_option('aramex_automation_auto_schedule', '1');
        } else {
            update_option('aramex_automation_auto_schedule', '0');
        }

        if (isset($_POST['pickup_date'])) {
            update_option('aramex_automation_pickup_date', sanitize_text_field($_POST['pickup_date']));
        }

        if (isset($_POST['ready_hour'])) {
            update_option('aramex_automation_ready_hour', sanitize_text_field($_POST['ready_hour']));
        }

        if (isset($_POST['ready_minute'])) {
            update_option('aramex_automation_ready_minute', sanitize_text_field($_POST['ready_minute']));
        }

        if (isset($_POST['latest_hour'])) {
            update_option('aramex_automation_latest_hour', sanitize_text_field($_POST['latest_hour']));
        }

        if (isset($_POST['latest_minute'])) {
            update_option('aramex_automation_latest_minute', sanitize_text_field($_POST['latest_minute']));
        }

        if (isset($_POST['pickup_location'])) {
            update_option('aramex_automation_pickup_location', sanitize_text_field($_POST['pickup_location']));
        }

        $this->addAdminNotice('Settings saved successfully', 'success');
        
        // Redirect back to settings tab
        wp_redirect(admin_url('admin.php?page=aramex-shipment-automation&tab=settings'));
        exit;
    }

    /**
     * Add admin notice
     */
    private function addAdminNotice($message, $type = 'info')
    {
        $notices = get_option('aramex_automation_admin_notices', []);
        $notices[] = [
            'message' => $message,
            'type' => $type,
            'timestamp' => time()
        ];
        update_option('aramex_automation_admin_notices', $notices);
    }
} 