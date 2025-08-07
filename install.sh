#!/bin/bash

# Aramex Automation Plugin Installation Script

echo "🚀 Installing Aramex Automation Plugin..."

# Check if composer is installed
if ! command -v composer &> /dev/null; then
    echo "❌ Composer is not installed. Please install Composer first."
    echo "Visit: https://getcomposer.org/download/"
    exit 1
fi

# Install dependencies
echo "📦 Installing Composer dependencies..."
composer install --no-dev --optimize-autoloader

# Set proper permissions
echo "🔐 Setting file permissions..."
chmod 644 *.php
chmod 644 includes/*.php
chmod 644 includes/Core/*.php
chmod 644 includes/Core/Admin/*.php
chmod 644 includes/Core/Admin/Handlers/*.php
chmod 644 includes/Core/Shipment/*.php
chmod 644 includes/Core/Shipment/Api/*.php
chmod 644 includes/Core/Email/*.php
chmod 644 includes/Core/Logging/*.php
chmod 644 templates/admin/*.php
chmod 644 assets/js/*.js

echo "✅ Installation completed!"
echo ""
echo "📋 Next steps:"
echo "1. Activate the plugin in WordPress admin"
echo "2. Ensure Aramex Shipping WooCommerce plugin is active"
echo "3. Configure settings under WooCommerce > Aramex Shipment Automation"
echo ""
echo "📚 For more information, see README.md" 