<?php
/**
 * Admin Page Template
 */

// Check if user has permission
if (!current_user_can('manage_woocommerce')) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
}

// Display admin notices
$notices = get_option('aramex_automation_admin_notices', []);
if (!empty($notices)) {
    foreach ($notices as $notice) {
        $class = 'notice notice-' . $notice['type'];
        $message = esc_html($notice['message']);
        echo "<div class='$class'><p>$message</p></div>";
    }
    delete_option('aramex_automation_admin_notices');
}

// Debug: Show recent shipment results
$recent_result = get_transient('aramex_automation_result');
if ($recent_result) {
    $class = $recent_result['success'] ? 'notice-success' : 'notice-error';
    $message = esc_html($recent_result['message']);
    echo "<div class='notice $class'><p><strong>Recent Result:</strong> $message</p></div>";
    delete_transient('aramex_automation_result');
}
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="aramex-automation-container">
        <!-- Tab Navigation -->
        <nav class="nav-tab-wrapper">
            <a href="<?php echo admin_url('admin.php?page=aramex-shipment-automation&tab=settings'); ?>" 
               class="nav-tab <?php echo ($current_tab === 'settings') ? 'nav-tab-active' : ''; ?>">Settings</a>
            <a href="<?php echo admin_url('admin.php?page=aramex-shipment-automation&tab=quick-shipment'); ?>" 
               class="nav-tab <?php echo ($current_tab === 'quick-shipment') ? 'nav-tab-active' : ''; ?>">Quick Shipment Creation</a>
            <a href="<?php echo admin_url('admin.php?page=aramex-shipment-automation&tab=bulk-operations'); ?>" 
               class="nav-tab <?php echo ($current_tab === 'bulk-operations') ? 'nav-tab-active' : ''; ?>">Bulk Operations</a>
            <a href="<?php echo admin_url('admin.php?page=aramex-shipment-automation&tab=recent-shipments'); ?>" 
               class="nav-tab <?php echo ($current_tab === 'recent-shipments') ? 'nav-tab-active' : ''; ?>">Recent Shipments</a>
        </nav>
        
        <!-- Settings Tab -->
        <div id="settings" class="tab-content <?php echo ($current_tab === 'settings') ? 'active' : ''; ?>">
            <div class="aramex-automation-section">
                <h2>Settings</h2>
                <p>Configure automation settings and preferences.</p>
                
                <form method="post" action="">
                    <?php wp_nonce_field('aramex_automation_settings_nonce', 'aramex_automation_settings_nonce'); ?>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="auto_email_customer">Auto Email Customer</label>
                            </th>
                            <td>
                                <input type="checkbox" id="auto_email_customer" name="auto_email_customer" value="1" 
                                       <?php checked(get_option('aramex_automation_auto_email', '1'), '1'); ?> />
                                <label for="auto_email_customer">Send tracking information to customer automatically</label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="default_weight_unit">Default Weight Unit</label>
                            </th>
                            <td>
                                <select id="default_weight_unit" name="default_weight_unit">
                                    <option value="kg" <?php selected(get_option('aramex_automation_weight_unit', 'kg'), 'kg'); ?>>Kilograms (kg)</option>
                                    <option value="lb" <?php selected(get_option('aramex_automation_weight_unit', 'kg'), 'lb'); ?>>Pounds (lb)</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="auto_schedule">Auto Schedule Pickup</label>
                            </th>
                            <td>
                                <input type="checkbox" id="auto_schedule" name="auto_schedule" value="1" 
                                       <?php checked(get_option('aramex_automation_auto_schedule', '1'), '1'); ?> />
                                <label for="auto_schedule">Automatically schedule pickup after creating shipment</label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="pickup_date">Pickup Date</label>
                            </th>
                            <td>
                                <select id="pickup_date" name="pickup_date">
                                    <option value="today" <?php selected(get_option('aramex_automation_pickup_date', 'tomorrow'), 'today'); ?>>Same Day</option>
                                    <option value="tomorrow" <?php selected(get_option('aramex_automation_pickup_date', 'tomorrow'), 'tomorrow'); ?>>Next Day</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="ready_hour">Ready Time</label>
                            </th>
                            <td>
                                <select id="ready_hour" name="ready_hour">
                                    <?php for ($i = 8; $i <= 18; $i++): ?>
                                        <option value="<?php echo $i; ?>" <?php selected(get_option('aramex_automation_ready_hour', '9'), $i); ?>><?php echo $i; ?>:00</option>
                                    <?php endfor; ?>
                                </select>
                                <select id="ready_minute" name="ready_minute">
                                    <option value="0" <?php selected(get_option('aramex_automation_ready_minute', '0'), '0'); ?>>:00</option>
                                    <option value="30" <?php selected(get_option('aramex_automation_ready_minute', '0'), '30'); ?>>:30</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="latest_hour">Latest Pickup Time</label>
                            </th>
                            <td>
                                <select id="latest_hour" name="latest_hour">
                                    <?php for ($i = 14; $i <= 20; $i++): ?>
                                        <option value="<?php echo $i; ?>" <?php selected(get_option('aramex_automation_latest_hour', '17'), $i); ?>><?php echo $i; ?>:00</option>
                                    <?php endfor; ?>
                                </select>
                                <select id="latest_minute" name="latest_minute">
                                    <option value="0" <?php selected(get_option('aramex_automation_latest_minute', '0'), '0'); ?>>:00</option>
                                    <option value="30" <?php selected(get_option('aramex_automation_latest_minute', '0'), '30'); ?>>:30</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="pickup_location">Pickup Location</label>
                            </th>
                            <td>
                                <input type="text" id="pickup_location" name="pickup_location" value="<?php echo esc_attr(get_option('aramex_automation_pickup_location', 'Reception')); ?>" class="regular-text" />
                                <p class="description">Location where Aramex will pick up the shipment</p>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <input type="submit" name="aramex_automation_settings_action" class="button-primary" 
                               value="Save Settings" />
                    </p>
                </form>
            </div>
        </div>
        
        <!-- Quick Shipment Creation Tab -->
        <div id="quick-shipment" class="tab-content <?php echo ($current_tab === 'quick-shipment') ? 'active' : ''; ?>">
            <div class="aramex-automation-section">
                <h2>Quick Shipment Creation</h2>
                <p>Enter an order ID to automatically create an Aramex shipment.</p>
                
                <form method="post" action="">
                    <?php wp_nonce_field('aramex_automation_nonce', 'aramex_automation_nonce'); ?>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="order_id">Order ID</label>
                            </th>
                            <td>
                                <input type="number" id="order_id" name="order_id" class="regular-text" required 
                                       placeholder="e.g., 1069" min="1" />
                                <p class="description">Enter the WooCommerce order ID to create shipment for.</p>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <input type="submit" name="aramex_automation_action" class="button-primary" 
                               value="Create Shipment" />
                    </p>
                </form>
            </div>
        </div>
        
        <!-- Bulk Operations Tab -->
        <div id="bulk-operations" class="tab-content <?php echo ($current_tab === 'bulk-operations') ? 'active' : ''; ?>">
            <div class="aramex-automation-section">
                <h2>Bulk Operations</h2>
                <p>Create shipments for multiple orders at once.</p>
                
                <form method="post" action="">
                    <?php wp_nonce_field('aramex_automation_bulk_nonce', 'aramex_automation_bulk_nonce'); ?>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="bulk_order_ids">Order IDs</label>
                            </th>
                            <td>
                                <textarea id="bulk_order_ids" name="bulk_order_ids" rows="5" cols="50" 
                                          placeholder="Enter order IDs separated by commas or new lines&#10;e.g., 1069, 1070, 1071"></textarea>
                                <p class="description">Enter multiple order IDs separated by commas or new lines.</p>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <input type="submit" name="aramex_automation_bulk_action" class="button-primary" 
                               value="Create Bulk Shipments" />
                    </p>
                </form>
            </div>
        </div>
        
        <!-- Recent Shipments Tab -->
        <div id="recent-shipments" class="tab-content <?php echo ($current_tab === 'recent-shipments') ? 'active' : ''; ?>">
            <div class="aramex-automation-section">
                <h2>Recent Shipments</h2>
                <div id="recent-shipments">
                    <?php
                    $logger = new \AramexAutomation\Core\Logging\ShipmentLogger();
                    $recent_shipments = $logger->getRecentShipments(20);
                    
                    if (empty($recent_shipments)) {
                        echo '<p>No recent shipments found.</p>';
                    } else {
                        echo '<div class="shipment-list">';
                        foreach ($recent_shipments as $shipment) {
                            $class = $shipment['success'] ? 'shipment-success' : 'shipment-error';
                            $status = $shipment['success'] ? 'Success' : 'Error';
                            $tracking_info = $shipment['tracking'] ? ' - Tracking: ' . $shipment['tracking'] : '';
                            
                            echo '<div class="shipment-item ' . $class . '">';
                            echo '<strong>Order #' . $shipment['order_id'] . '</strong> - ' . $status . $tracking_info . '<br>';
                            echo '<small>' . $shipment['message'] . '</small><br>';
                            echo '<small>Date: ' . $shipment['timestamp'] . '</small>';
                            echo '</div>';
                        }
                        echo '</div>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .aramex-automation-container {
        max-width: 1200px;
    }
    .aramex-automation-section {
        background: #fff;
        border: 1px solid #ccd0d4;
        border-radius: 4px;
        padding: 20px;
        margin-bottom: 20px;
    }
    .aramex-automation-section h2 {
        margin-top: 0;
        color: #23282d;
    }
    .aramex-automation-section p {
        color: #666;
    }
    .shipment-item {
        background: #f9f9f9;
        border: 1px solid #ddd;
        padding: 10px;
        margin-bottom: 10px;
        border-radius: 3px;
    }
    .shipment-success {
        border-left: 4px solid #46b450;
    }
    .shipment-error {
        border-left: 4px solid #dc3232;
    }
    .tab-content {
        display: none;
    }
    .tab-content.active {
        display: block;
    }
    .nav-tab-active {
        background: #0073aa;
        color: #fff;
        border-color: #0073aa;
    }
    .nav-tab-wrapper {
        margin-bottom: 20px;
    }
</style>

 