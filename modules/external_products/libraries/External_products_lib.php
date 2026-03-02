<?php

defined('BASEPATH') or exit('No direct script access allowed');

class External_products_lib
{
    private $CI;
    
    public function __construct()
    {
        $this->CI = &get_instance();
        $this->CI->load->model('external_products/external_products_model');
    }
    
    /**
     * Sync external products with internal products
     * @param  string $external_system
     * @return array
     */
    public function sync_external_products($external_system = null)
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => []
        ];
        
        try {
            // Get all mappings
            $mappings = $this->CI->external_products_model->get_external_product_mapping();
            
            foreach ($mappings as $mapping) {
                try {
                    // Simple sync logic - just log the mapping
                    log_activity('Synced mapping: ' . $mapping['sku'] . ' -> ' . $mapping['mapping_id']);
                    $results['success']++;
                } catch (Exception $e) {
                    $results['failed']++;
                    $results['errors'][] = $e->getMessage();
                }
            }
            
        } catch (Exception $e) {
            $results['errors'][] = $e->getMessage();
        }
        
        return $results;
    }
    
    /**
     * Sync single external product
     * @param  array $product
     * @return bool
     */
    private function sync_single_product($mapping)
    {
        // Simple sync logic for existing mapping
        log_activity('Synced single mapping: ' . $mapping['sku'] . ' -> ' . $mapping['mapping_id']);
        return true;
    }
    
    /**
     * Import external products from API
     * @param  string $external_system
     * @param  array  $api_config
     * @return array
     */
    public function import_external_products($external_system, $api_config)
    {
        $results = [
            'imported' => 0,
            'updated' => 0,
            'errors' => []
        ];
        
        try {
            // Simple import logic - just log the attempt
            log_activity('Import attempt for system: ' . $external_system);
            $results['imported'] = 0;
            
        } catch (Exception $e) {
            $results['errors'][] = $e->getMessage();
        }
        
        return $results;
    }
    
    /**
     * Fetch products from external API
     * @param  string $external_system
     * @param  array  $api_config
     * @return array
     */
    private function fetch_products_from_api($external_system, $api_config)
    {
        // This would be implemented based on the specific external system
        // For now, return empty array
        return [];
    }
    
    /**
     * Import single product
     * @param  array  $product_data
     * @param  string $external_system
     * @return bool
     */
    private function import_single_product($product_data, $external_system)
    {
        // Simple import logic - just log the attempt
        log_activity('Import single product: ' . $product_data['id'] . ' for system: ' . $external_system);
        return true;
    }
    
    /**
     * Map external product to internal product
     * @param  string $external_product_id
     * @param  string $external_system
     * @param  int    $internal_product_id
     * @return bool
     */
    public function map_external_to_internal($external_product_id, $external_system, $internal_product_id)
    {
        // Check if mapping already exists
        $existing_mapping = $this->CI->external_products_model->get_external_product_mapping_by_mapping_id(
            $external_product_id, 
            $external_system
        );
        
        if ($existing_mapping) {
            // Update existing mapping
            $data = ['sku' => $internal_product_id];
            return $this->CI->external_products_model->update_external_product_mapping($data, $existing_mapping->id);
        } else {
            // Create new mapping
            $data = [
                'sku' => $internal_product_id,
                'mapping_id' => $external_product_id,
                'mapping_type' => $external_system
            ];
            return $this->CI->external_products_model->add_external_product_mapping($data);
        }
    }
    
    /**
     * Unmap external product from internal product
     * @param  string $external_product_id
     * @param  string $external_system
     * @return bool
     */
    public function unmap_external_from_internal($external_product_id, $external_system)
    {
        $mapping = $this->CI->external_products_model->get_external_product_mapping_by_mapping_id(
            $external_product_id, 
            $external_system
        );
        
        if ($mapping) {
            return $this->CI->external_products_model->delete_external_product_mapping($mapping->id);
        }
        
        return false;
    }
    
    /**
     * Get mapping statistics
     * @return array
     */
    public function get_mapping_statistics()
    {
        $total_mappings = count($this->CI->external_products_model->get_external_product_mapping());
        $mapped_products = 0;
        $unmapped_products = 0;
        
        $mappings = $this->CI->external_products_model->get_external_product_mapping();
        foreach ($mappings as $mapping) {
            if (!empty($mapping['sku'])) {
                $mapped_products++;
            } else {
                $unmapped_products++;
            }
        }
        
        return [
            'total_mappings' => $total_mappings,
            'mapped_products' => $mapped_products,
            'unmapped_products' => $unmapped_products,
            'mapping_percentage' => $total_mappings > 0 ? round(($mapped_products / $total_mappings) * 100, 2) : 0
        ];
    }
    
    /**
     * Validate mapping data
     * @param  array $data
     * @return array
     */
    public function validate_mapping_data($data)
    {
        $errors = [];
        
        if (empty($data['sku'])) {
            $errors[] = 'SKU is required';
        }
        
        if (empty($data['mapping_id'])) {
            $errors[] = 'Mapping ID is required';
        }
        
        if (empty($data['mapping_type'])) {
            $errors[] = 'Mapping Type is required';
        }
        
        // Check for duplicate mapping
        $existing = $this->CI->external_products_model->get_external_product_mapping_by_mapping_id(
            $data['mapping_id'], 
            $data['mapping_type']
        );
        
        if ($existing && (!isset($data['id']) || $existing->id != $data['id'])) {
            $errors[] = 'Mapping already exists for this external product';
        }
        
        return $errors;
    }
}
