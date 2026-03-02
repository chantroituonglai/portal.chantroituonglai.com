# External Mapping Management Module

## Overview
The External Mapping Management module for Perfex CRM allows you to manage external product mappings, order management, and synchronize products from various external systems including WooCommerce, Shopify, Magento, Amazon, eBay, and Haravan.

## Features

### Core Functionality
- **Product Mapping**: Map external products to internal products using SKU, Mapping ID, and Mapping Type
- **Order Management**: Manage external orders with mapping to internal orders
- **Multiple System Support**: Support for WooCommerce, Shopify, Magento, Amazon, eBay, Haravan, and custom systems
- **Haravan API Integration**: Full integration with Haravan API for product synchronization
- **Bulk Operations**: Bulk delete, sync, and mapping operations
- **Settings Management**: Configure external system APIs and sync settings
- **Statistics Dashboard**: View mapping statistics and completion rates
- **Database Upgrade System**: Automatic database migration and upgrade functionality

### Database Structure
The module works with the existing `tblexternal_products_mapping` table:
- `id` (int, primary key, auto_increment)
- `sku` (varchar(100), not null) - Internal product SKU
- `mapping_id` (varchar(100), not null) - External product ID
- `mapping_type` (varchar(10), not null) - External system type

## Installation

1. Copy the `external_products` folder to your Perfex CRM `modules` directory
2. The module will automatically create necessary database tables and settings on activation
3. Configure the module settings in Admin → Settings → External Products

## Upgrade System

The module includes a comprehensive upgrade system:

### Version 2.0.0 Features
- **Haravan API Integration**: Full support for Haravan product synchronization
- **Order Management**: Complete order management with mapping functionality
- **Database Migration**: Automatic database schema updates
- **Enhanced Security**: Improved API authentication and error handling

### Upgrade Process
1. **Automatic Upgrade**: The module automatically detects and runs necessary database migrations
2. **Manual Upgrade**: Use the "Upgrade Database" button in the module management interface
3. **Version Tracking**: Database version is tracked to ensure proper upgrade sequence

### Database Versions
- **Version 100**: Initial module setup (v1.0.0)
- **Version 200**: Haravan API integration and order management (v2.0.0)

## Usage

### Accessing Settings
1. Go to **Admin → Settings → External Mapping Management** to configure:
   - Enable/disable module
   - Configure sync settings
   - **Configure Haravan API settings** (token, base URL)
   - Test Haravan connection
   - Sync products from Haravan

### Adding Product Mappings
1. Navigate to External Mapping Management → Product Mapping
2. Click "Add External Product"
3. Fill in the required fields:
   - SKU: Internal product SKU
   - Mapping ID: External product identifier
   - Mapping Type: External system (woo, shopify, magento, amazon, ebay, other)

### Managing Mappings
- View all mappings in the mapping table
- Edit existing mappings
- Delete individual or bulk delete mappings
- Filter mappings by system type

### Order Management
- Manage external orders with mapping to internal orders
- View order statistics and status
- Sync orders from external systems

### Settings Configuration
1. Go to **Admin → Settings → External Mapping Management** to configure:
   - Module enable/disable
   - Sync intervals and settings
   - External system API configurations
   - **Haravan API settings** (token, base URL, test connection)
2. Configure external system APIs:
   - WooCommerce API URL and Key
   - Shopify API URL and Access Token
3. Set sync preferences:
   - Enable/disable auto sync
   - Set sync interval
   - Set default mapping status

## API Integration

The module provides comprehensive integration with external systems:

### Haravan Integration (Featured)
- **API URL**: `https://apis.haravan.com/com/`
- **Authentication**: Bearer Token authentication
- **Product Sync**: Sync products by SKU automatically
- **Data Mapping**: Automatic mapping of product data to database
- **Settings**: Configure in Admin → Settings → External Mapping Management
- **Test Connection**: Test API connectivity before syncing
- **Real-time Sync**: Sync individual products or bulk operations
- **Complete Product Data**: Includes name, SKU, price, description, category, brand, stock, etc.

### WooCommerce Integration
- API URL: `https://yourstore.com/wp-json/wc/v3/`
- Requires Consumer Key and Consumer Secret

### Shopify Integration
- API URL: `https://yourstore.myshopify.com/admin/api/2023-01/`
- Requires Access Token

### Custom Integration
- Extend the `External_products_lib` class
- Implement API-specific methods in the library

## File Structure

```
external_products/
├── controllers/
│   ├── External_products.php        # Main controller
│   └── Env_ver.php                  # Environment/version controller
├── models/
│   └── External_products_model.php  # Database models and API integrations
├── views/
│   └── admin/
│       ├── external_products.php    # Main view
│       ├── mapping.php              # Product mapping view
│       ├── orders.php               # Order management view
│       ├── order_mapping.php        # Order mapping view
│       ├── haravan_products.php     # Haravan products view
│       ├── settings.php             # Settings view (includes Haravan API)
│       └── tables/                  # DataTables for all views
├── language/
│   └── english/
│       └── external_products_lang.php
├── external_products.php            # Main module file
├── install.php                      # Installation script
├── CHANGELOG.md                     # Change log
└── README.md                        # This file
```

## Permissions

The module includes the following permissions:
- `external_products` - View (Access to all module features)
- `external_products` - Create (Add new mappings and orders)
- `external_products` - Edit (Edit existing mappings and orders)
- `external_products` - Delete (Delete mappings and orders)

## Module Features

### ✅ **Complete Feature Set**
- **Product Mapping**: Map external products to internal products
- **Order Management**: Manage external orders with internal mapping
- **Haravan API Integration**: Full API integration for product sync
- **Multi-System Support**: WooCommerce, Shopify, Magento, Amazon, eBay, Haravan
- **Bulk Operations**: Bulk delete, sync, and mapping operations
- **Settings Management**: Comprehensive settings in Admin → Settings
- **Statistics Dashboard**: Real-time statistics and analytics
- **Database Management**: Proper installation and upgrade system

## Helper Functions

The module provides several helper functions:

- `get_external_product_mapping($id)` - Get mapping by ID
- `get_external_product_mapping_by_sku($sku)` - Get mapping by SKU
- `get_external_product_mapping_by_mapping_id($mapping_id, $mapping_type)` - Get mapping by external ID
- `format_mapping_type($type)` - Format mapping type for display
- `get_mapping_type_badge($type)` - Get HTML badge for mapping type
- `is_external_product_mapped($mapping_id, $mapping_type)` - Check if product is mapped
- `get_external_products_count()` - Get total count of external products
- `get_mapped_products_count()` - Get count of mapped products
- `get_unmapped_products_count()` - Get count of unmapped products

## Customization

### Adding New External Systems
1. Add the system type to the mapping type dropdown in views
2. Add API configuration fields in settings
3. Extend the library class with system-specific methods

### Styling
- Customize the module appearance by modifying `assets/css/external_products.css`
- Add custom JavaScript functionality in `assets/js/external_products.js`

## Troubleshooting

### Common Issues
1. **Module not loading**: Check file permissions and ensure all files are properly uploaded
2. **Database errors**: Verify database connection and table structure
3. **Permission errors**: Ensure user has proper permissions for the module

### Debug Mode
Enable debug mode in Perfex CRM to see detailed error messages during development.

## Support

For support and customization requests, please contact the development team.

## Version History

- **v1.0.0** - Initial release with basic mapping functionality
  - Product mapping management
  - Multiple external system support
  - Settings configuration
  - Statistics dashboard
