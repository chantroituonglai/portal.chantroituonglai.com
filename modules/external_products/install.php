<?php

defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();

// The external_products_mapping table already exists with structure:
// id (int, primary key, auto_increment)
// sku (varchar(100), not null)
// mapping_id (varchar(100), not null) 
// mapping_type (varchar(10), not null)
// 
// Current data shows these mapping types:
// - fast_barco (8,585 records) - Main system
// - aeon_sku (143 records) - AEON system
// - emart/emart_sku (20 records) - Emart system
// - mapping_ty (6 records) - Data entry error
// 
// We'll work with this existing structure and add necessary indexes
if ($CI->db->table_exists(db_prefix() . 'external_products_mapping')) {
    // Check if indexes exist and add them if they don't
    $indexes = $CI->db->query("SHOW INDEX FROM " . db_prefix() . "external_products_mapping")->result_array();
    $existing_indexes = array_column($indexes, 'Key_name');
    
    // Add unique index for mapping_id and mapping_type combination if it doesn't exist
    if (!in_array('unique_mapping', $existing_indexes)) {
        try {
            $CI->db->query("ALTER TABLE " . db_prefix() . "external_products_mapping ADD UNIQUE KEY unique_mapping (mapping_id, mapping_type)");
        } catch (Exception $e) {
            // Index might already exist or there might be duplicate data
            log_message('error', 'Could not add unique index: ' . $e->getMessage());
        }
    }
    
    // Add index for sku if it doesn't exist
    if (!in_array('idx_sku', $existing_indexes)) {
        try {
            $CI->db->query("ALTER TABLE " . db_prefix() . "external_products_mapping ADD KEY idx_sku (sku)");
        } catch (Exception $e) {
            log_message('error', 'Could not add sku index: ' . $e->getMessage());
        }
    }
    
    // Add index for mapping_type if it doesn't exist
    if (!in_array('idx_mapping_type', $existing_indexes)) {
        try {
            $CI->db->query("ALTER TABLE " . db_prefix() . "external_products_mapping ADD KEY idx_mapping_type (mapping_type)");
        } catch (Exception $e) {
            log_message('error', 'Could not add mapping_type index: ' . $e->getMessage());
        }
    }
}

// Create external products table for storing external product details
if (!$CI->db->table_exists(db_prefix() . 'external_products')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . 'external_products` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `external_product_id` varchar(255) NOT NULL,
        `external_product_name` varchar(255) NOT NULL,
        `external_product_sku` varchar(255) DEFAULT NULL,
        `external_product_price` decimal(15,2) DEFAULT NULL,
        `external_product_description` text,
        `external_product_image` varchar(255) DEFAULT NULL,
        `external_product_url` varchar(500) DEFAULT NULL,
        `external_system` varchar(100) NOT NULL,
        `external_category` varchar(255) DEFAULT NULL,
        `external_brand` varchar(255) DEFAULT NULL,
        `external_stock_quantity` int(11) DEFAULT NULL,
        `external_product_weight` decimal(10,3) DEFAULT NULL,
        `external_product_dimensions` varchar(100) DEFAULT NULL,
        `external_product_tags` text,
        `is_active` tinyint(1) DEFAULT 1,
        `created_date` datetime NOT NULL,
        `updated_date` datetime DEFAULT NULL,
        `last_import_date` datetime DEFAULT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `external_product_unique` (`external_product_id`, `external_system`),
        KEY `external_system` (`external_system`),
        KEY `is_active` (`is_active`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;');
}

// Create external systems table for managing different external systems
if (!$CI->db->table_exists(db_prefix() . 'external_systems')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . 'external_systems` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `system_name` varchar(100) NOT NULL,
        `system_type` varchar(50) NOT NULL,
        `api_endpoint` varchar(500) DEFAULT NULL,
        `api_key` varchar(255) DEFAULT NULL,
        `api_secret` varchar(255) DEFAULT NULL,
        `api_username` varchar(100) DEFAULT NULL,
        `api_password` varchar(255) DEFAULT NULL,
        `sync_frequency` int(11) DEFAULT 3600,
        `last_sync_date` datetime DEFAULT NULL,
        `is_active` tinyint(1) DEFAULT 1,
        `created_date` datetime NOT NULL,
        `updated_date` datetime DEFAULT NULL,
        `created_by` int(11) NOT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `system_name` (`system_name`),
        KEY `system_type` (`system_type`),
        KEY `is_active` (`is_active`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;');
}

// Insert default external systems
$default_systems = [
    [
        'system_name' => 'WooCommerce',
        'system_type' => 'ecommerce',
        'api_endpoint' => '',
        'sync_frequency' => 3600,
        'is_active' => 1,
        'created_date' => date('Y-m-d H:i:s'),
        'created_by' => get_staff_user_id()
    ],
    [
        'system_name' => 'Shopify',
        'system_type' => 'ecommerce',
        'api_endpoint' => '',
        'sync_frequency' => 3600,
        'is_active' => 1,
        'created_date' => date('Y-m-d H:i:s'),
        'created_by' => get_staff_user_id()
    ],
    [
        'system_name' => 'Magento',
        'system_type' => 'ecommerce',
        'api_endpoint' => '',
        'sync_frequency' => 3600,
        'is_active' => 1,
        'created_date' => date('Y-m-d H:i:s'),
        'created_by' => get_staff_user_id()
    ],
    [
        'system_name' => 'Haravan',
        'system_type' => 'ecommerce',
        'api_endpoint' => 'https://apis.haravan.com/com',
        'sync_frequency' => 3600,
        'is_active' => 1,
        'created_date' => date('Y-m-d H:i:s'),
        'created_by' => get_staff_user_id()
    ]
];

foreach ($default_systems as $system) {
    $CI->db->where('system_name', $system['system_name']);
    if (!$CI->db->get(db_prefix() . 'external_systems')->row()) {
        $CI->db->insert(db_prefix() . 'external_systems', $system);
    }
}

// Add module options
add_option('external_products_enabled', 1);
add_option('external_products_auto_sync', 0);
add_option('external_products_sync_interval', 3600);
add_option('external_products_default_mapping_status', 'pending');

// Haravan API Settings
add_option('haravan_api_enabled', 0);
add_option('haravan_api_token', '');
add_option('haravan_api_base_url', 'https://apis.haravan.com/com');

// Log installation
log_activity('External Products Management Module Installed');
