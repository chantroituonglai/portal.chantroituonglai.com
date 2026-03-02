<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_200 extends App_module_migration
{
    public function up()
    {
        $CI = &get_instance();

        $mappingTable = db_prefix() . 'external_products_mapping';
        if ($CI->db->table_exists($mappingTable)) {
            $indexes         = $CI->db->query('SHOW INDEX FROM ' . $mappingTable)->result_array();
            $existingIndexes = array_column($indexes, 'Key_name');

            if (!in_array('unique_mapping', $existingIndexes)) {
                try {
                    $CI->db->query('ALTER TABLE ' . $mappingTable . ' ADD UNIQUE KEY unique_mapping (mapping_id, mapping_type)');
                } catch (Exception $e) {
                    log_message('error', 'external_products migration: unique_mapping index failed - ' . $e->getMessage());
                }
            }

            if (!in_array('idx_sku', $existingIndexes)) {
                try {
                    $CI->db->query('ALTER TABLE ' . $mappingTable . ' ADD KEY idx_sku (sku)');
                } catch (Exception $e) {
                    log_message('error', 'external_products migration: idx_sku index failed - ' . $e->getMessage());
                }
            }

            if (!in_array('idx_mapping_type', $existingIndexes)) {
                try {
                    $CI->db->query('ALTER TABLE ' . $mappingTable . ' ADD KEY idx_mapping_type (mapping_type)');
                } catch (Exception $e) {
                    log_message('error', 'external_products migration: idx_mapping_type index failed - ' . $e->getMessage());
                }
            }

            $fields = $CI->db->list_fields($mappingTable);

            if (!in_array('internal_product_id', $fields)) {
                $CI->db->query('ALTER TABLE ' . $mappingTable . ' ADD COLUMN internal_product_id INT(11) NULL AFTER mapping_type');
            }

            if (!in_array('external_system', $fields)) {
                $CI->db->query('ALTER TABLE ' . $mappingTable . " ADD COLUMN external_system VARCHAR(100) NULL AFTER internal_product_id");
            }

            if (!in_array('mapping_status', $fields)) {
                $CI->db->query('ALTER TABLE ' . $mappingTable . " ADD COLUMN mapping_status VARCHAR(50) NOT NULL DEFAULT 'pending' AFTER external_system");
            }

            if (!in_array('sync_status', $fields)) {
                $CI->db->query('ALTER TABLE ' . $mappingTable . " ADD COLUMN sync_status VARCHAR(50) NOT NULL DEFAULT 'never' AFTER mapping_status");
            }

            if (!in_array('last_sync_date', $fields)) {
                $CI->db->query('ALTER TABLE ' . $mappingTable . ' ADD COLUMN last_sync_date DATETIME NULL AFTER sync_status');
            }

            if (!in_array('created_at', $fields)) {
                $CI->db->query('ALTER TABLE ' . $mappingTable . ' ADD COLUMN created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER last_sync_date');
            }

            if (!in_array('updated_at', $fields)) {
                $CI->db->query('ALTER TABLE ' . $mappingTable . ' ADD COLUMN updated_at DATETIME NULL AFTER created_at');
            }
        }

        $productsTable = db_prefix() . 'external_products';
        if (!$CI->db->table_exists($productsTable)) {
            $CI->db->query('CREATE TABLE `' . $productsTable . '` (
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
        } else {
            $productFields = $CI->db->list_fields($productsTable);

            if (!in_array('external_product_dimensions', $productFields)) {
                $CI->db->query('ALTER TABLE ' . $productsTable . ' ADD COLUMN external_product_dimensions VARCHAR(100) DEFAULT NULL AFTER external_product_weight');
            }

            if (!in_array('external_product_tags', $productFields)) {
                $CI->db->query('ALTER TABLE ' . $productsTable . ' ADD COLUMN external_product_tags TEXT AFTER external_product_dimensions');
            }

            if (!in_array('last_import_date', $productFields)) {
                $CI->db->query('ALTER TABLE ' . $productsTable . ' ADD COLUMN last_import_date DATETIME DEFAULT NULL AFTER updated_date');
            }
        }

        $systemsTable = db_prefix() . 'external_systems';
        if (!$CI->db->table_exists($systemsTable)) {
            $CI->db->query('CREATE TABLE `' . $systemsTable . '` (
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
        } else {
            $systemFields = $CI->db->list_fields($systemsTable);

            if (!in_array('api_secret', $systemFields)) {
                $CI->db->query('ALTER TABLE ' . $systemsTable . ' ADD COLUMN api_secret VARCHAR(255) DEFAULT NULL AFTER api_key');
            }

            if (!in_array('api_username', $systemFields)) {
                $CI->db->query('ALTER TABLE ' . $systemsTable . ' ADD COLUMN api_username VARCHAR(100) DEFAULT NULL AFTER api_secret');
            }

            if (!in_array('api_password', $systemFields)) {
                $CI->db->query('ALTER TABLE ' . $systemsTable . ' ADD COLUMN api_password VARCHAR(255) DEFAULT NULL AFTER api_username');
            }

            if (!in_array('sync_frequency', $systemFields)) {
                $CI->db->query('ALTER TABLE ' . $systemsTable . ' ADD COLUMN sync_frequency INT(11) DEFAULT 3600 AFTER api_password');
            }

            if (!in_array('last_sync_date', $systemFields)) {
                $CI->db->query('ALTER TABLE ' . $systemsTable . ' ADD COLUMN last_sync_date DATETIME DEFAULT NULL AFTER sync_frequency');
            }

            if (!in_array('created_by', $systemFields)) {
                $CI->db->query('ALTER TABLE ' . $systemsTable . ' ADD COLUMN created_by INT(11) NOT NULL AFTER updated_date');
            }
        }

        $defaultSystems = [
            [
                'system_name' => 'WooCommerce',
                'system_type' => 'ecommerce',
                'api_endpoint' => '',
                'sync_frequency' => 3600,
                'is_active' => 1,
                'created_date' => date('Y-m-d H:i:s'),
                'created_by' => get_staff_user_id() ?: 1
            ],
            [
                'system_name' => 'Shopify',
                'system_type' => 'ecommerce',
                'api_endpoint' => '',
                'sync_frequency' => 3600,
                'is_active' => 1,
                'created_date' => date('Y-m-d H:i:s'),
                'created_by' => get_staff_user_id() ?: 1
            ],
            [
                'system_name' => 'Magento',
                'system_type' => 'ecommerce',
                'api_endpoint' => '',
                'sync_frequency' => 3600,
                'is_active' => 1,
                'created_date' => date('Y-m-d H:i:s'),
                'created_by' => get_staff_user_id() ?: 1
            ],
            [
                'system_name' => 'Haravan',
                'system_type' => 'ecommerce',
                'api_endpoint' => 'https://apis.haravan.com/com',
                'sync_frequency' => 3600,
                'is_active' => 1,
                'created_date' => date('Y-m-d H:i:s'),
                'created_by' => get_staff_user_id() ?: 1
            ],
        ];

        foreach ($defaultSystems as $system) {
            $CI->db->where('system_name', $system['system_name']);
            if (!$CI->db->get($systemsTable)->row()) {
                $CI->db->insert($systemsTable, $system);
            }
        }

        add_option('external_products_enabled', 1);
        add_option('external_products_auto_sync', 0);
        add_option('external_products_sync_interval', 3600);
        add_option('external_products_default_mapping_status', 'pending');
        add_option('haravan_api_enabled', 0);
        add_option('haravan_api_token', '');
        add_option('haravan_api_base_url', 'https://apis.haravan.com/com');

        log_activity('External Products module migrated to version 2.0.0');
    }
}
