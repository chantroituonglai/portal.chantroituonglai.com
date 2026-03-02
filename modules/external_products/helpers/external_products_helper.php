<?php

defined('BASEPATH') or exit('No direct script access allowed');

if (!function_exists('get_external_product_mapping')) {
    /**
     * Get external product mapping by ID
     * @param  int $id
     * @return object
     */
    function get_external_product_mapping($id)
    {
        $CI = &get_instance();
        $CI->load->model('external_products/external_products_model');
        return $CI->external_products_model->get_external_product_mapping($id);
    }
}

if (!function_exists('get_external_product_mapping_by_sku')) {
    /**
     * Get external product mapping by SKU
     * @param  string $sku
     * @return object
     */
    function get_external_product_mapping_by_sku($sku)
    {
        $CI = &get_instance();
        $CI->load->model('external_products/external_products_model');
        return $CI->external_products_model->get_external_product_mapping_by_sku($sku);
    }
}

if (!function_exists('get_external_product_mapping_by_mapping_id')) {
    /**
     * Get external product mapping by mapping ID and type
     * @param  string $mapping_id
     * @param  string $mapping_type
     * @return object
     */
    function get_external_product_mapping_by_mapping_id($mapping_id, $mapping_type)
    {
        $CI = &get_instance();
        $CI->load->model('external_products/external_products_model');
        return $CI->external_products_model->get_external_product_mapping_by_mapping_id($mapping_id, $mapping_type);
    }
}

if (!function_exists('format_mapping_type')) {
    /**
     * Format mapping type for display
     * @param  string $type
     * @return string
     */
    function format_mapping_type($type)
    {
        $types = [
            'fast_barco' => 'Fast Barco',
            'aeon_sku' => 'AEON SKU',
            'emart' => 'Emart',
            'emart_sku' => 'Emart SKU',
            'woo' => 'WooCommerce',
            'shopify' => 'Shopify',
            'magento' => 'Magento',
            'amazon' => 'Amazon',
            'ebay' => 'eBay',
            'other' => 'Other'
        ];
        
        return isset($types[$type]) ? $types[$type] : ucfirst($type);
    }
}

if (!function_exists('get_mapping_type_badge')) {
    /**
     * Get mapping type badge HTML
     * @param  string $type
     * @return string
     */
    function get_mapping_type_badge($type)
    {
        $formatted_type = format_mapping_type($type);
        return '<span class="mapping-type-badge ' . $type . '">' . $formatted_type . '</span>';
    }
}

if (!function_exists('is_external_product_mapped')) {
    /**
     * Check if external product is mapped
     * @param  string $mapping_id
     * @param  string $mapping_type
     * @return bool
     */
    function is_external_product_mapped($mapping_id, $mapping_type)
    {
        $mapping = get_external_product_mapping_by_mapping_id($mapping_id, $mapping_type);
        return $mapping ? true : false;
    }
}

if (!function_exists('get_external_products_count')) {
    /**
     * Get total count of external products
     * @return int
     */
    function get_external_products_count()
    {
        $CI = &get_instance();
        $CI->load->model('external_products/external_products_model');
        return count($CI->external_products_model->get_external_product_mapping());
    }
}

if (!function_exists('get_mapped_products_count')) {
    /**
     * Get count of mapped products
     * @return int
     */
    function get_mapped_products_count()
    {
        $CI = &get_instance();
        $CI->load->model('external_products/external_products_model');
        $mappings = $CI->external_products_model->get_external_product_mapping();
        $count = 0;
        foreach ($mappings as $mapping) {
            if (!empty($mapping['sku'])) {
                $count++;
            }
        }
        return $count;
    }
}

if (!function_exists('get_unmapped_products_count')) {
    /**
     * Get count of unmapped products
     * @return int
     */
    function get_unmapped_products_count()
    {
        return get_external_products_count() - get_mapped_products_count();
    }
}

if (!function_exists('external_products_module_url')) {
    /**
     * Get external products module URL
     * @param  string $path
     * @return string
     */
    function external_products_module_url($path = '')
    {
        return admin_url('external_products' . ($path ? '/' . $path : ''));
    }
}

if (!function_exists('external_products_module_dir')) {
    /**
     * Get external products module directory path
     * @param  string $path
     * @return string
     */
    function external_products_module_dir($path = '')
    {
        return module_dir_path('external_products', $path);
    }
}

if (!function_exists('external_products_module_url_assets')) {
    /**
     * Get external products module assets URL
     * @param  string $path
     * @return string
     */
    function external_products_module_url_assets($path = '')
    {
        return module_dir_url('external_products', 'assets/' . $path);
    }
}

if (!function_exists('get_external_products_count')) {
    /**
     * Get total count of external products
     * @return int
     */
    function get_external_products_count()
    {
        $CI = &get_instance();
        $CI->load->model('external_products/external_products_model');
        return count($CI->external_products_model->get_external_product_mapping());
    }
}

if (!function_exists('get_mapped_products_count')) {
    /**
     * Get count of mapped products
     * @return int
     */
    function get_mapped_products_count()
    {
        $CI = &get_instance();
        $CI->load->model('external_products/external_products_model');
        $mappings = $CI->external_products_model->get_external_product_mapping();
        $unique_skus = array_unique(array_column($mappings, 'sku'));
        return count($unique_skus);
    }
}

if (!function_exists('get_unmapped_products_count')) {
    /**
     * Get count of unmapped products
     * @return int
     */
    function get_unmapped_products_count()
    {
        $CI = &get_instance();
        $CI->load->model('products_model');
        $total_products = count($CI->products_model->get_by_id_product());
        $mapped_products = get_mapped_products_count();
        return max(0, $total_products - $mapped_products);
    }
}
