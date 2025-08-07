# Aramex Automation Plugin

A WordPress plugin for automating Aramex shipment creation in WooCommerce.

## Features

- **PSR-4 Compliant**: Modern PHP structure with proper autoloading
- **Tabbed Admin Interface**: Clean, organized admin panel
- **Single & Bulk Operations**: Create shipments for individual or multiple orders
- **Automatic Pickup Scheduling**: Schedule pickups after shipment creation
- **Customer Email Notifications**: Send tracking information to customers
- **Comprehensive Logging**: Track all shipment attempts and results

## Requirements

- WordPress 5.0+
- WooCommerce 3.0+
- PHP 7.4+
- Aramex Shipping WooCommerce Plugin (must be active)

## Installation

1. **Install Composer Dependencies**:
   ```bash
   composer install
   ```

2. **Activate the Plugin**:
   - Upload the plugin to `/wp-content/plugins/aramex-automation/`
   - Activate through the WordPress admin panel
   - Ensure the Aramex Shipping WooCommerce plugin is active

## Plugin Structure

```
aramex-automation/
├── composer.json                 # Composer configuration
├── aramex-automation.php         # Main plugin file
├── includes/                     # PSR-4 autoloaded classes
│   ├── Plugin.php               # Main plugin class
│   └── Core/                    # Core functionality
│       ├── DependencyChecker.php
│       ├── Assets.php
│       ├── Admin/               # Admin functionality
│       │   ├── AdminMenu.php
│       │   ├── AdminPage.php
│       │   └── Handlers/        # Form handlers
│       │       ├── FormHandler.php
│       │       ├── BulkHandler.php
│       │       └── SettingsHandler.php
│       ├── Shipment/            # Shipment functionality
│       │   ├── ShipmentCreator.php
│       │   ├── PickupScheduler.php
│       │   └── Api/             # API integration
│       │       └── AramexApi.php
│       ├── Email/               # Email functionality
│       │   └── CustomerEmail.php
│       └── Logging/             # Logging functionality
│           └── ShipmentLogger.php
├── templates/                   # Template files
│   └── admin/
│       └── admin-page.php
├── assets/                      # Frontend assets
│   └── js/
│       └── main.js
└── vendor/                      # Composer dependencies
```

## Usage

### Admin Interface

The plugin provides a tabbed admin interface under **WooCommerce > Aramex Shipment Automation**:

1. **Settings Tab**: Configure automation preferences
2. **Quick Shipment Creation**: Create shipments for individual orders
3. **Bulk Operations**: Process multiple orders at once
4. **Recent Shipments**: View shipment history and logs

### URL Automation

You can trigger shipment creation via URL:
```
http://your-site.com/wp-admin/admin.php?page=aramex-shipment-automation&create-shipment=true&order-id=1234
```

## Configuration

### Settings

- **Auto Email Customer**: Send tracking information automatically
- **Default Weight Unit**: Set default weight unit (kg/lb)
- **Auto Schedule Pickup**: Automatically schedule pickups
- **Pickup Date**: Same day or next day pickup
- **Ready Time**: When shipments will be ready
- **Latest Pickup Time**: Latest time for pickup
- **Pickup Location**: Location for pickup

### Dependencies

The plugin requires the **Aramex Shipping WooCommerce** plugin to be active. It will display an admin notice if the dependency is missing.

## Development

### Adding New Features

1. Create new classes in the appropriate namespace under `includes/`
2. Follow PSR-4 naming conventions
3. Use dependency injection where appropriate
4. Add proper error handling and logging

### Code Standards

- Follow PSR-12 coding standards
- Use proper namespacing
- Include PHPDoc comments
- Handle errors gracefully
- Log important events

## Troubleshooting

### Common Issues

1. **"Aramex settings not found"**: Ensure the Aramex plugin is configured
2. **"Order not found"**: Verify the order ID exists
3. **API errors**: Check Aramex API credentials and network connectivity

### Debugging

Enable WordPress debug logging to see detailed error messages:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## License

GPL v2 or later

## Support

For support and feature requests, please contact the plugin author. 