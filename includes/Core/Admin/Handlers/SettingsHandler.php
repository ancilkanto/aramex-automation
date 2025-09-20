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
        // Handle manual cron trigger
        if (isset($_GET['trigger_cron']) && $_GET['trigger_cron'] === '1') {
            if (current_user_can('manage_woocommerce')) {
                $result = \AramexAutomation\Core\Cron\CronAutomation::triggerCronManually();
                $this->addAdminNotice($result['message'], 'success');
                
                // Redirect back to settings tab
                wp_redirect(admin_url('admin.php?page=aramex-shipment-automation&tab=settings'));
                exit;
            }
        }

        // Handle manual shipment status check
        if (isset($_GET['check_status']) && $_GET['check_status'] === '1') {
            if (current_user_can('manage_woocommerce')) {
                $result = \AramexAutomation\Core\Cron\CronAutomation::triggerStatusCheckManually();
                $this->addAdminNotice($result['message'], 'success');
                
                // Redirect back to settings tab
                wp_redirect(admin_url('admin.php?page=aramex-shipment-automation&tab=settings'));
                exit;
            }
        }

        // Handle test admin email
        if (isset($_GET['test_admin_email']) && $_GET['test_admin_email'] === '1') {
            if (current_user_can('manage_woocommerce')) {
                $admin_emails = \AramexAutomation\Plugin::getAdminNotificationEmails();
                
                if (empty($admin_emails)) {
                    $this->addAdminNotice('No admin notification emails configured. Please add email addresses in the settings above.', 'warning');
                } else {
                    $result = \AramexAutomation\Core\Email\EmailManager::sendAdminNotification(
                        'Test Admin Notification - Aramex Automation',
                        '<p>This is a test email to verify that admin notifications are working correctly.</p>
                         <p><strong>Plugin:</strong> Aramex Automation</p>
                         <p><strong>Time:</strong> ' . current_time('Y-m-d H:i:s') . '</p>
                         <p>If you receive this email, admin notifications are configured and working properly.</p>'
                    );
                    
                    if ($result) {
                        $this->addAdminNotice('Test admin notification email sent successfully using WooCommerce email system.', 'success');
                    } else {
                        $this->addAdminNotice('Failed to send test admin notification email. Please check your email configuration.', 'error');
                    }
                }
                
                // Redirect back to settings tab
                wp_redirect(admin_url('admin.php?page=aramex-shipment-automation&tab=settings'));
                exit;
            }
        }

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

        // Save custom description of goods
        if (isset($_POST['custom_description_goods'])) {
            update_option('aramex_automation_custom_description_goods', sanitize_textarea_field($_POST['custom_description_goods']));
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

        // Save email trigger
        if (isset($_POST['email_trigger'])) {
            $allowed = ['creation', 'status_change'];
            $value = sanitize_text_field($_POST['email_trigger']);
            if (!in_array($value, $allowed, true)) {
                $value = 'creation';
            }
            update_option('aramex_automation_email_trigger', $value);
        }

        // Save admin notification emails
        if (isset($_POST['admin_notification_emails'])) {
            $emails = sanitize_text_field($_POST['admin_notification_emails']);
            // Validate email format (basic validation for comma-separated emails)
            $email_array = array_map('trim', explode(',', $emails));
            $valid_emails = [];
            foreach ($email_array as $email) {
                if (!empty($email) && is_email($email)) {
                    $valid_emails[] = $email;
                }
            }
            update_option('aramex_automation_admin_notification_emails', implode(',', $valid_emails));
        }

        // Save non-working days settings
        $non_working_days = [];
        $days_of_week = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        
        foreach ($days_of_week as $day) {
            if (isset($_POST['non_working_days']) && in_array($day, $_POST['non_working_days'])) {
                $non_working_days[] = $day;
            }
        }
        update_option('aramex_automation_non_working_days', $non_working_days);

        // Save cron automation settings
        if (isset($_POST['auto_cron_enabled'])) {
            update_option('aramex_automation_auto_cron_enabled', '1');
        } else {
            update_option('aramex_automation_auto_cron_enabled', '0');
        }

        if (isset($_POST['cron_hour'])) {
            update_option('aramex_automation_cron_hour', sanitize_text_field($_POST['cron_hour']));
        }

        if (isset($_POST['cron_minute'])) {
            update_option('aramex_automation_cron_minute', sanitize_text_field($_POST['cron_minute']));
        }

        if (isset($_POST['cron_order_status'])) {
            update_option('aramex_automation_cron_order_status', sanitize_text_field($_POST['cron_order_status']));
        }

        // Schedule or unschedule the cron job based on settings
        $this->manageCronSchedule();

        $this->addAdminNotice('Settings saved successfully', 'success');
        
        // Redirect back to settings tab
        wp_redirect(admin_url('admin.php?page=aramex-shipment-automation&tab=settings'));
        exit;
    }

    /**
     * Manage cron schedule based on settings
     */
    private function manageCronSchedule()
    {
        $cron_enabled = get_option('aramex_automation_auto_cron_enabled', '0');
        
        // Always clear existing cron job first to ensure timing updates are applied
        wp_clear_scheduled_hook('aramex_automation_daily_cron');
        
        if ($cron_enabled === '1') {
            // Schedule the cron job with new timing
            $cron_hour = get_option('aramex_automation_cron_hour', '9');
            $cron_minute = get_option('aramex_automation_cron_minute', '0');
            
            // Calculate the next occurrence of the specified time
            $next_run = strtotime("today {$cron_hour}:{$cron_minute}:00");
            
            // If the time has already passed today, schedule for tomorrow
            if ($next_run <= time()) {
                $next_run = strtotime("tomorrow {$cron_hour}:{$cron_minute}:00");
            }
            
            wp_schedule_event($next_run, 'daily', 'aramex_automation_daily_cron');
        }
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