# Bilingual Email Template for Aramex Automation

## Overview
The Aramex Automation plugin now supports bilingual email templates with English and Arabic text. The English text is left-aligned, and the Arabic text is right-aligned with proper RTL (right-to-left) direction.

## Features
- **Bilingual Content**: Both English and Arabic versions of email content
- **Sequential Layout**: Arabic section first (right-aligned), English section second (left-aligned)
- **Clear Separation**: Visual divider between the two language sections
- **RTL Support**: Arabic text displays with proper right-to-left direction
- **Responsive Design**: Mobile-friendly layout that maintains clear separation
- **Customizable**: Arabic content can be edited in WooCommerce email settings

## Setup

### 1. Email Settings
1. Go to **WooCommerce > Settings > Emails**
2. Click on **Awaiting Shipment** email
3. You'll see two new fields:
   - **Email heading**: English version (existing)
   - **Email heading (Arabic)**: Arabic version (new)

### 2. Arabic Content
Fill in the Arabic versions of your email content:
- **Email heading (Arabic)**: Arabic version of the email heading
- The body content is automatically translated in the template

## Template Structure

### HTML Email Template
The HTML email template (`templates/emails/aramex-shipment.php`) includes:
- **Bilingual Email Header**: Both English and Arabic headings displayed prominently
- **Arabic Section First**: Complete Arabic content with right-aligned text
- **Visual Divider**: Clear border line separating the two sections
- **English Section Second**: Complete English content with left-aligned text
- Each section contains: greeting, message, heading, table, and closing

### Plain Text Template
The plain text template (`templates/emails/plain/aramex-shipment.php`) includes:
- **Arabic Section First**: Complete Arabic content
- Clear divider line with "--- English Version ---"
- **English Section Second**: Complete English content below
- Maintains readability in plain text format

### CSS Styling
The CSS file (`assets/css/bilingual-email.css`) provides:
- Sequential layout with Arabic section first, English section second
- Clear visual separators with borders and spacing
- Proper RTL support for Arabic text
- Responsive design for mobile devices
- Consistent spacing and typography

## Customization

### Adding New Bilingual Content
To add new bilingual content:

1. **In the email class** (`WCEmailAramexShipment.php`):
   ```php
   'new_field_arabic' => array(
       'title' => __('New Field (Arabic)', 'aramex-automation'),
       'type' => 'text',
       'description' => __('Arabic version', 'aramex-automation'),
       'default' => '',
   ),
   ```

2. **In the template**:
   ```php
   <div class="bilingual-content">
       <div class="english-content">
           <p><?php esc_html_e('English text', 'aramex-automation'); ?></p>
       </div>
       <div class="arabic-content">
           <p><?php esc_html_e('Arabic text', 'aramex-automation'); ?></p>
       </div>
   </div>
   ```

### Styling Modifications
Edit `assets/css/bilingual-email.css` to customize:
- Font sizes and families
- Spacing and margins
- Colors and borders
- Responsive breakpoints

## Language Support

### Arabic Translations
The plugin includes Arabic translations for:
- Email headings
- Table headers
- Greeting messages
- Content paragraphs
- Closing messages

### Adding More Languages
To add support for additional languages:
1. Create new `.po` files in the `languages/` directory
2. Translate the strings
3. Compile to `.mo` files
4. Update the template to include the new language

## Technical Details

### CSS Classes Used
- `.bilingual-heading`: Container for bilingual headings
- `.bilingual-content`: Container for bilingual content
- `.english-heading` / `.english-content`: English text containers
- `.arabic-heading` / `.arabic-content`: Arabic text containers

### Responsive Behavior
- **Desktop**: Sequential layout with Arabic section first, English section second
- **Mobile**: Maintains sequential layout with centered text alignment for better readability
- **Breakpoint**: 600px width

### Browser Compatibility
- Modern browsers with CSS Flexbox support
- RTL support for Arabic text
- Fallback to standard layout for older browsers

## Troubleshooting

### Common Issues
1. **Arabic text not displaying properly**: Ensure the CSS file is loading correctly
2. **Layout broken on mobile**: Check responsive CSS rules
3. **RTL not working**: Verify the `direction: rtl` CSS property

### Debug Mode
Enable WordPress debug mode to see any PHP errors:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## Support
For issues or questions about the bilingual email template:
1. Check the WordPress debug log
2. Verify CSS file is loading
3. Test with different email clients
4. Check browser developer tools for CSS issues
