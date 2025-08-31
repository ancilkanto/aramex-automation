# Admin Notifications

The Aramex Automation plugin now includes comprehensive admin notification functionality to keep administrators informed about shipment operations.

## Configuration

### Setting Up Admin Notification Emails

1. Go to **WooCommerce > Aramex Shipment Automation > Settings**
2. In the **Admin Notification Emails** field, enter email addresses separated by commas
3. Example: `admin@example.com, manager@example.com, shipping@example.com`
4. Click **Save Settings**

**Note:** Leave this field empty to disable admin notifications completely.

### WooCommerce Email Settings

The admin notification emails are fully integrated with WooCommerce's email system:

1. Go to **WooCommerce > Settings > Emails**
2. Find **"Aramex Admin Shipment Notification"** in the list
3. Customize:
   - **Enable/Disable** the email
   - **Recipient(s)** for notifications
   - **Subject** line with placeholders
   - **Email heading** 
   - **Email type** (HTML or Plain text)
4. Click **Save changes**

**Available placeholders:** `{site_title}`, `{order_number}`, `{order_date}`, `{order_total}`

## What Triggers Admin Notifications

### 1. Shipment Creation
- **Success**: When a shipment is created successfully (includes label URL if available)
- **Failure**: When shipment creation fails (includes label URL if available)
- **Exception**: When an exception occurs during shipment creation (includes label URL if available)

### 2. Automated Processing (Cron)
- **Daily Summary**: Summary of all orders processed by the daily automation
- **Status Check Summary**: Summary of shipment status updates

**Note**: Pickup scheduling notifications have been removed. Only shipment creation notifications are sent to reduce email clutter.

## Email Content

Each admin notification includes:
- **Subject Line**: Clear description of the event
- **Order Details**: Order ID, customer name, and total amount
- **Event Information**: Specific details about what happened
- **Error Details**: If applicable, error messages and context
- **Label URL**: Direct link to download the shipping label (when available)

## Testing

Use the **Test Admin Email** button in the settings to verify that:
1. Admin notification emails are configured correctly
2. Email delivery is working
3. The email format looks good

## Email Format

Admin notifications are sent using the **standard WooCommerce email system** with:
- **Professional WooCommerce styling** and layout
- **Consistent branding** with your store's email templates
- **Responsive design** that works on all devices
- **Customizable settings** through WooCommerce email settings
- **Order context** when available
- **Error details** when applicable
- **Label URL**: Direct download link to shipping labels (when available)

## Label URL Feature

### What It Includes
- **Direct Access**: Clickable link to download shipping labels
- **Automatic Detection**: Only shows when label URL is available
- **Secure Links**: URLs are properly escaped for security
- **New Window**: Links open in new tab for convenience

### When Available
- After successful shipment creation
- In all shipment creation admin notifications for orders with labels

### Benefits
- **Quick Access**: Admins can immediately download labels
- **No Manual Lookup**: Direct access from notification emails
- **Efficient Workflow**: Streamlines shipping label management
- **Professional Appearance**: Uses your store's email branding
- **Consistent Experience**: Matches other WooCommerce emails
- **Easy Customization**: Modify through WooCommerce settings
- **Mobile Friendly**: Responsive design for all devices

## Troubleshooting

### No Emails Received
1. Check that admin notification emails are configured
2. Verify email addresses are valid
3. Check server email configuration
4. Look for error logs in WordPress debug log

### Too Many Emails
1. Consider reducing the number of admin email addresses
2. Review automation settings to reduce unnecessary operations
3. Use the test button to verify configuration before going live

## Security

- Admin notifications are only sent to configured email addresses
- Email addresses are validated before saving
- Notifications respect WordPress user capabilities
- No sensitive order information is exposed beyond what's necessary

## Customization

Developers can customize admin notifications by:
- Hooking into the `aramex_after_pickup_schedule` action
- Using the `EmailManager::sendAdminNotification()` method
- Modifying email templates in the `templates/emails/` directory
