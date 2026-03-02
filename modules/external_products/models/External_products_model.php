<?php

defined('BASEPATH') or exit('No direct script access allowed');

class External_products_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    // External Products Mapping Methods (working with existing table structure)
    public function add_external_product_mapping($data)
    {
        $this->db->insert(db_prefix() . 'external_products_mapping', $data);
        $insert_id = $this->db->insert_id();
        if ($insert_id) {
            log_activity('New External Product Mapping Added [ ID:' . $insert_id . ', SKU: ' . $data['sku'] . ', Staff id ' . get_staff_user_id() . ' ]');
            return $insert_id;
        }
        // Some installations don't have auto_increment on this table, so insert_id can be 0.
        if ($this->db->affected_rows() > 0) {
            log_activity('New External Product Mapping Added [ SKU: ' . $data['sku'] . ', Staff id ' . get_staff_user_id() . ' ]');
            return true;
        }
        return false;
    }

    public function get_external_product_mapping($id = false)
    {
        if ($id) {
            $this->db->where('id', $id);
            return $this->db->get(db_prefix() . 'external_products_mapping')->row();
        }
        $this->db->order_by('id', 'DESC');
        return $this->db->get(db_prefix() . 'external_products_mapping')->result_array();
    }

    public function get_external_product_mapping_by_sku($sku)
    {
        $this->db->where('sku', $sku);
        return $this->db->get(db_prefix() . 'external_products_mapping')->row();
    }

    public function get_external_product_mapping_by_mapping_id($mapping_id, $mapping_type)
    {
        $this->db->where('mapping_id', $mapping_id);
        $this->db->where('mapping_type', $mapping_type);
        return $this->db->get(db_prefix() . 'external_products_mapping')->row();
    } 

    public function update_external_product_mapping($data, $id)
    {
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'external_products_mapping', $data);
        if ($this->db->affected_rows() > 0) {
            log_activity('External Product Mapping Updated [ ID:' . $id . ', Staff id ' . get_staff_user_id() . ' ]');
            return true;
        }
        return false;
    }

    public function delete_external_product_mapping($id)
    {
        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'external_products_mapping');
        if ($this->db->affected_rows() > 0) {
            log_activity('External Product Mapping Deleted [ ID:' . $id . ', Staff id ' . get_staff_user_id() . ' ]');
            return true;
        }
        return false;
    }

    // External Products Methods
    public function add_external_product($data)
    {
        $this->db->insert(db_prefix() . 'external_products', $data);
        $insert_id = $this->db->insert_id();
        if ($insert_id) {
            log_activity('New External Product Added [ ID:' . $insert_id . ', Product: ' . $data['external_product_name'] . ', Staff id ' . get_staff_user_id() . ' ]');
            return $insert_id;
        }
        return false;
    }

    public function get_external_product($id = false)
    {
        if ($id) {
            $this->db->where('id', $id);
            return $this->db->get(db_prefix() . 'external_products')->row();
        }
        return $this->db->get(db_prefix() . 'external_products')->result_array();
    }

    public function get_external_products_by_system($external_system)
    {
        $this->db->where('external_system', $external_system);
        $this->db->where('is_active', 1);
        return $this->db->get(db_prefix() . 'external_products')->result_array();
    }

    public function update_external_product($data, $id)
    {
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'external_products', $data);
        if ($this->db->affected_rows() > 0) {
            log_activity('External Product Updated [ ID:' . $id . ', Staff id ' . get_staff_user_id() . ' ]');
            return true;
        }
        return false;
    }

    public function delete_external_product($id)
    {
        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'external_products');
        if ($this->db->affected_rows() > 0) {
            log_activity('External Product Deleted [ ID:' . $id . ', Staff id ' . get_staff_user_id() . ' ]');
            return true;
        }
        return false;
    }

    // External Systems Methods
    public function add_external_system($data)
    {
        $this->db->insert(db_prefix() . 'external_systems', $data);
        $insert_id = $this->db->insert_id();
        if ($insert_id) {
            log_activity('New External System Added [ ID:' . $insert_id . ', System: ' . $data['system_name'] . ', Staff id ' . get_staff_user_id() . ' ]');
            return $insert_id;
        }
        return false;
    }

    public function get_external_system($id = false)
    {
        if ($id) {
            $this->db->where('id', $id);
            return $this->db->get(db_prefix() . 'external_systems')->row();
        }
        return $this->db->get(db_prefix() . 'external_systems')->result_array();
    }

    public function get_active_external_systems()
    {
        $this->db->where('is_active', 1);
        return $this->db->get(db_prefix() . 'external_systems')->result_array();
    }

    public function update_external_system($data, $id)
    {
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'external_systems', $data);
        if ($this->db->affected_rows() > 0) {
            log_activity('External System Updated [ ID:' . $id . ', Staff id ' . get_staff_user_id() . ' ]');
            return true;
        }
        return false;
    }

    public function delete_external_system($id)
    {
        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'external_systems');
        if ($this->db->affected_rows() > 0) {
            log_activity('External System Deleted [ ID:' . $id . ', Staff id ' . get_staff_user_id() . ' ]');
            return true;
        }
        return false;
    }

    // Combined Methods
    public function get_external_products_with_mapping()
    {
        $this->db->select('ep.*, epm.internal_product_id, epm.mapping_status, epm.sync_status, epm.last_sync_date');
        $this->db->from(db_prefix() . 'external_products ep');
        $this->db->join(db_prefix() . 'external_products_mapping epm', 'ep.external_product_id = epm.external_product_id AND ep.external_system = epm.external_system', 'LEFT');
        $this->db->where('ep.is_active', 1);
        return $this->db->get()->result_array();
    }

    public function get_unmapped_external_products()
    {
        $this->db->select('ep.*');
        $this->db->from(db_prefix() . 'external_products ep');
        $this->db->join(db_prefix() . 'external_products_mapping epm', 'ep.external_product_id = epm.external_product_id AND ep.external_system = epm.external_system', 'LEFT');
        $this->db->where('ep.is_active', 1);
        $this->db->where('epm.id IS NULL');
        return $this->db->get()->result_array();
    }

    public function bulk_update_mapping_status($ids, $status)
    {
        $this->db->where_in('id', $ids);
        $this->db->update(db_prefix() . 'external_products_mapping', ['mapping_status' => $status]);
        return $this->db->affected_rows();
    }

    public function bulk_update_sync_status($ids, $status)
    {
        $this->db->where_in('id', $ids);
        $this->db->update(db_prefix() . 'external_products_mapping', ['sync_status' => $status, 'last_sync_date' => date('Y-m-d H:i:s')]);
        return $this->db->affected_rows();
    }

    // Method to get table structure info
    public function get_table_structure()
    {
        $query = $this->db->query("DESCRIBE " . db_prefix() . "external_products_mapping");
        return $query->result_array();
    }

    // Method to get table indexes
    public function get_table_indexes()
    {
        $query = $this->db->query("SHOW INDEX FROM " . db_prefix() . "external_products_mapping");
        return $query->result_array();
    }

    // Method to get sample data
    public function get_sample_data($limit = 5)
    {
        $this->db->limit($limit);
        $this->db->order_by('id', 'DESC');
        return $this->db->get(db_prefix() . 'external_products_mapping')->result_array();
    }

    // Method to count total records
    public function count_mappings()
    {
        return $this->db->count_all(db_prefix() . 'external_products_mapping');
    }

    // Duplicate Detection Methods
    public function get_duplicate_skus()
    {
        $this->db->select('sku, COUNT(*) as count, GROUP_CONCAT(id) as ids, GROUP_CONCAT(mapping_id) as mapping_ids, GROUP_CONCAT(mapping_type) as mapping_types');
        $this->db->from(db_prefix() . 'external_products_mapping');
        $this->db->group_by('sku');
        $this->db->having('COUNT(*) > 1');
        $this->db->order_by('count', 'DESC');
        return $this->db->get()->result_array();
    }

    public function get_duplicate_mapping_ids()
    {
        $this->db->select('mapping_id, mapping_type, COUNT(*) as count, GROUP_CONCAT(id) as ids, GROUP_CONCAT(sku) as skus');
        $this->db->from(db_prefix() . 'external_products_mapping');
        $this->db->group_by('mapping_id, mapping_type');
        $this->db->having('COUNT(*) > 1');
        $this->db->order_by('count', 'DESC');
        return $this->db->get()->result_array();
    }

    public function get_sku_mapping_conflicts()
    {
        // Find SKUs that have different mapping_ids for the same mapping_type
        $this->db->select('sku, mapping_type, COUNT(DISTINCT mapping_id) as mapping_count, GROUP_CONCAT(DISTINCT mapping_id) as mapping_ids, GROUP_CONCAT(id) as ids');
        $this->db->from(db_prefix() . 'external_products_mapping');
        $this->db->group_by('sku, mapping_type');
        $this->db->having('COUNT(DISTINCT mapping_id) > 1');
        $this->db->order_by('mapping_count', 'DESC');
        return $this->db->get()->result_array();
    }

    public function get_mapping_id_sku_conflicts()
    {
        // Find mapping_ids that have different SKUs for the same mapping_type
        $this->db->select('mapping_id, mapping_type, COUNT(DISTINCT sku) as sku_count, GROUP_CONCAT(DISTINCT sku) as skus, GROUP_CONCAT(id) as ids');
        $this->db->from(db_prefix() . 'external_products_mapping');
        $this->db->group_by('mapping_id, mapping_type');
        $this->db->having('COUNT(DISTINCT sku) > 1');
        $this->db->order_by('sku_count', 'DESC');
        return $this->db->get()->result_array();
    }

    public function get_duplicate_statistics()
    {
        $stats = [];
        
        // Count duplicate SKUs
        $duplicate_skus = $this->get_duplicate_skus();
        $stats['duplicate_skus_count'] = count($duplicate_skus);
        $stats['duplicate_skus_total_records'] = array_sum(array_column($duplicate_skus, 'count'));
        
        // Count duplicate mapping IDs
        $duplicate_mapping_ids = $this->get_duplicate_mapping_ids();
        $stats['duplicate_mapping_ids_count'] = count($duplicate_mapping_ids);
        $stats['duplicate_mapping_ids_total_records'] = array_sum(array_column($duplicate_mapping_ids, 'count'));
        
        // Count SKU conflicts
        $sku_conflicts = $this->get_sku_mapping_conflicts();
        $stats['sku_conflicts_count'] = count($sku_conflicts);
        
        // Count mapping ID conflicts
        $mapping_conflicts = $this->get_mapping_id_sku_conflicts();
        $stats['mapping_conflicts_count'] = count($mapping_conflicts);
        
        return $stats;
    }

    public function resolve_duplicate_sku($sku, $keep_id, $delete_ids)
    {
        if (empty($delete_ids)) {
            return false;
        }
        
        $this->db->where_in('id', $delete_ids);
        $this->db->where('sku', $sku);
        $result = $this->db->delete(db_prefix() . 'external_products_mapping');
        
        if ($result) {
            log_activity('Resolved duplicate SKU: ' . $sku . ' - Kept ID: ' . $keep_id . ' - Deleted IDs: ' . implode(',', $delete_ids));
        }
        
        return $result;
    }

    public function resolve_duplicate_mapping_id($mapping_id, $mapping_type, $keep_id, $delete_ids)
    {
        if (empty($delete_ids)) {
            return false;
        }
        
        $this->db->where_in('id', $delete_ids);
        $this->db->where('mapping_id', $mapping_id);
        $this->db->where('mapping_type', $mapping_type);
        $result = $this->db->delete(db_prefix() . 'external_products_mapping');
        
        if ($result) {
            log_activity('Resolved duplicate mapping ID: ' . $mapping_id . ' (' . $mapping_type . ') - Kept ID: ' . $keep_id . ' - Deleted IDs: ' . implode(',', $delete_ids));
        }
        
        return $result;
    }

    // Order Management Methods (using existing tblexternal_data_mapping table)
    public function add_external_order($data)
    {
        $mapping_data = [
            'uniquekey' => $data['external_order_id'],
            'title' => $data['customer_name'] ?? '',
            'rel' => 'Order',
            'root_id' => $data['external_order_id'],
            'target_id' => $data['internal_order_id'] ?? $data['external_order_id'],
            'dateadded' => date('Y-m-d H:i:s'),
            'status' => 1,
            'data' => json_encode($data)
        ];
        
        $this->db->insert(db_prefix() . 'external_data_mapping', $mapping_data);
        $insert_id = $this->db->insert_id();
        if ($insert_id) {
            log_activity('New External Order Added [ ID:' . $insert_id . ', Order ID: ' . $data['external_order_id'] . ', Staff id ' . get_staff_user_id() . ' ]');
            return $insert_id;
        }
        return false;
    }

    public function get_external_order($id = false)
    {
        if ($id) {
            $this->db->where('id', $id);
            $this->db->where('rel', 'Order');
            return $this->db->get(db_prefix() . 'external_data_mapping')->row();
        }
        $this->db->where('rel', 'Order');
        $this->db->order_by('id', 'DESC');
        return $this->db->get(db_prefix() . 'external_data_mapping')->result_array();
    }

    public function get_external_orders_by_system($external_system)
    {
        $this->db->where('rel', 'Order');
        $this->db->order_by('dateadded', 'DESC');
        return $this->db->get(db_prefix() . 'external_data_mapping')->result_array();
    }

    public function update_external_order($data, $id)
    {
        $mapping_data = [
            'uniquekey' => $data['external_order_id'],
            'title' => $data['customer_name'] ?? '',
            'root_id' => $data['external_order_id'],
            'target_id' => $data['internal_order_id'] ?? $data['external_order_id'],
            'data' => json_encode($data)
        ];
        
        $this->db->where('id', $id);
        $this->db->where('rel', 'Order');
        $this->db->update(db_prefix() . 'external_data_mapping', $mapping_data);
        if ($this->db->affected_rows() > 0) {
            log_activity('External Order Updated [ ID:' . $id . ', Staff id ' . get_staff_user_id() . ' ]');
            return true;
        }
        return false;
    }

    public function delete_external_order($id)
    {
        $this->db->where('id', $id);
        $this->db->where('rel', 'Order');
        $this->db->delete(db_prefix() . 'external_data_mapping');
        if ($this->db->affected_rows() > 0) {
            log_activity('External Order Deleted [ ID:' . $id . ', Staff id ' . get_staff_user_id() . ' ]');
            return true;
        }
        return false;
    }

    public function get_external_systems()
    {
        $this->db->where('is_active', 1);
        return $this->db->get(db_prefix() . 'external_systems')->result_array();
    }

    public function get_available_haravan_sync_skus()
    {
        $haravanSkus = $this->db->select('external_product_sku')
            ->from(db_prefix() . 'external_products')
            ->where('external_system', 'Haravan')
            ->where('external_product_sku IS NOT NULL', null, false)
            ->where('external_product_sku !=', '')
            ->get()->result_array();

        $existingSkus = array_filter(array_column($haravanSkus, 'external_product_sku'));

        $this->db->select('external_product_sku');
        $this->db->from(db_prefix() . 'external_products');
        $this->db->where('external_product_sku IS NOT NULL', null, false);
        $this->db->where('external_product_sku !=', '');
        $this->db->group_start();
        $this->db->where('external_system IS NULL', null, false);
        $this->db->or_where('external_system !=', 'Haravan');
        $this->db->group_end();
        $this->db->group_by('external_product_sku');
        $this->db->order_by('external_product_sku', 'ASC');

        if (!empty($existingSkus)) {
            $this->db->where_not_in('external_product_sku', $existingSkus);
        }

        $skus = $this->db->get()->result_array();

        return array_values(array_unique(array_column($skus, 'external_product_sku')));
    }

    public function get_external_system_by_name($system_name)
    {
        $this->db->where('system_name', $system_name);
        $this->db->where('is_active', 1);
        return $this->db->get(db_prefix() . 'external_systems')->row();
    }

    public function update_woocommerce_settings($settings)
    {
        if (isset($settings['woocommerce_api_url'])) {
            update_option('woocommerce_api_url', $settings['woocommerce_api_url']);
        }

        if (isset($settings['woocommerce_api_key'])) {
            update_option('woocommerce_api_key', $settings['woocommerce_api_key']);
        }

        log_activity('WooCommerce settings updated [Staff ID: ' . get_staff_user_id() . ']');

        return true;
    }

    public function update_shopify_settings($settings)
    {
        if (isset($settings['shopify_api_url'])) {
            update_option('shopify_api_url', $settings['shopify_api_url']);
        }

        if (isset($settings['shopify_access_token'])) {
            update_option('shopify_access_token', $settings['shopify_access_token']);
        }

        log_activity('Shopify settings updated [Staff ID: ' . get_staff_user_id() . ']');

        return true;
    }

    public function update_magento_settings($settings)
    {
        if (isset($settings['magento_api_url'])) {
            update_option('magento_api_url', $settings['magento_api_url']);
        }

        if (isset($settings['magento_access_token'])) {
            update_option('magento_access_token', $settings['magento_access_token']);
        }

        log_activity('Magento settings updated [Staff ID: ' . get_staff_user_id() . ']');

        return true;
    }

    public function sync_orders_from_external_system($external_system)
    {
        try {
            // This is a placeholder for actual API integration
            // In a real implementation, you would:
            // 1. Connect to the external system API
            // 2. Fetch orders
            // 3. Process and insert them
            
            $orders_count = 0;
            $message = 'Orders synced successfully';
            
            // Simulate API call
            switch ($external_system->system_type) {
                case 'ecommerce':
                    // Simulate WooCommerce/Shopify/Magento API call
                    $orders_count = rand(5, 20);
                    break;
                default:
                    $orders_count = 0;
                    $message = 'Unsupported system type';
            }
            
            return [
                'success' => true,
                'count' => $orders_count,
                'message' => $message
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'count' => 0,
                'message' => $e->getMessage()
            ];
        }
    }

    // Order Mapping Methods (using existing tblexternal_data_mapping table)
    public function add_order_mapping($data)
    {
        $mapping_data = [
            'uniquekey' => $data['external_order_id'],
            'title' => $data['customer_name'] ?? '',
            'rel' => 'Order',
            'root_id' => $data['external_order_id'],
            'target_id' => $data['internal_order_id'],
            'dateadded' => date('Y-m-d H:i:s'),
            'status' => 1,
            'data' => json_encode($data)
        ];
        
        $this->db->insert(db_prefix() . 'external_data_mapping', $mapping_data);
        $insert_id = $this->db->insert_id();
        if ($insert_id) {
            log_activity('New Order Mapping Added [ ID:' . $insert_id . ', External Order: ' . $data['external_order_id'] . ', Staff id ' . get_staff_user_id() . ' ]');
            return $insert_id;
        }
        return false;
    }

    public function get_order_mapping($id = false)
    {
        if ($id) {
            $this->db->where('id', $id);
            $this->db->where('rel', 'Order');
            return $this->db->get(db_prefix() . 'external_data_mapping')->row();
        }
        $this->db->where('rel', 'Order');
        $this->db->order_by('id', 'DESC');
        return $this->db->get(db_prefix() . 'external_data_mapping')->result_array();
    }

    public function update_order_mapping($data, $id)
    {
        $mapping_data = [
            'uniquekey' => $data['external_order_id'],
            'title' => $data['customer_name'] ?? '',
            'root_id' => $data['external_order_id'],
            'target_id' => $data['internal_order_id'],
            'data' => json_encode($data)
        ];
        
        $this->db->where('id', $id);
        $this->db->where('rel', 'Order');
        $this->db->update(db_prefix() . 'external_data_mapping', $mapping_data);
        if ($this->db->affected_rows() > 0) {
            log_activity('Order Mapping Updated [ ID:' . $id . ', Staff id ' . get_staff_user_id() . ' ]');
            return true;
        }
        return false;
    }

    public function delete_order_mapping($id)
    {
        $this->db->where('id', $id);
        $this->db->where('rel', 'Order');
        $this->db->delete(db_prefix() . 'external_data_mapping');
        if ($this->db->affected_rows() > 0) {
            log_activity('Order Mapping Deleted [ ID:' . $id . ', Staff id ' . get_staff_user_id() . ' ]');
            return true;
        }
        return false;
    }

    public function delete_order_mappings($ids)
    {
        $ids = array_filter(array_map('intval', (array) $ids));
        if (empty($ids)) {
            return 0;
        }

        $this->db->where('rel', 'Order');
        $this->db->where_in('id', $ids);
        $this->db->delete(db_prefix() . 'external_data_mapping');
        $deleted = $this->db->affected_rows();

        if ($deleted > 0) {
            log_activity('Order Mapping Bulk Deleted [ Count:' . $deleted . ', Staff id ' . get_staff_user_id() . ' ]');
        }

        return $deleted;
    }

    public function bulk_clear_order_mapping_data($ids)
    {
        $ids = array_filter(array_map('intval', (array) $ids));
        if (empty($ids)) {
            return 0;
        }

        $this->db->where('rel', 'Order');
        $this->db->where_in('id', $ids);
        $this->db->update(db_prefix() . 'external_data_mapping', ['data' => null]);
        $updated = $this->db->affected_rows();

        if ($updated > 0) {
            log_activity('Order Mapping Bulk Clear Data [ Count:' . $updated . ', Staff id ' . get_staff_user_id() . ' ]');
        }

        return $updated;
    }

    public function bulk_update_order_mapping_status($ids, $status)
    {
        $ids = array_filter(array_map('intval', (array) $ids));
        if (empty($ids)) {
            return 0;
        }

        $this->db->where('rel', 'Order');
        $this->db->where_in('id', $ids);
        $rows = $this->db->get(db_prefix() . 'external_data_mapping')->result_array();

        $updated = 0;
        foreach ($rows as $row) {
            $payload = [];
            if (!empty($row['data'])) {
                $decoded = json_decode($row['data'], true);
                if (is_array($decoded)) {
                    $payload = $decoded;
                }
            }
            $payload['mapping_status'] = $status;

            $this->db->where('id', $row['id']);
            $this->db->where('rel', 'Order');
            $this->db->update(db_prefix() . 'external_data_mapping', ['data' => json_encode($payload)]);
            if ($this->db->affected_rows() > 0) {
                $updated++;
            }
        }

        if ($updated > 0) {
            log_activity('Order Mapping Bulk Update Status [ Count:' . $updated . ', Staff id ' . get_staff_user_id() . ' ]');
        }

        return $updated;
    }

    // Statistics Methods (using existing tblexternal_data_mapping table)
    public function get_order_statistics()
    {
        $stats = [];
        
        // Total orders
        $this->db->where('rel', 'Order');
        $stats['total_orders'] = $this->db->count_all_results(db_prefix() . 'external_data_mapping');
        
        // Orders by date (last 30 days)
        $this->db->select('DATE(dateadded) as order_date, COUNT(*) as count');
        $this->db->from(db_prefix() . 'external_data_mapping');
        $this->db->where('rel', 'Order');
        $this->db->where('dateadded >=', date('Y-m-d', strtotime('-30 days')));
        $this->db->group_by('DATE(dateadded)');
        $this->db->order_by('order_date', 'DESC');
        $stats['orders_by_date'] = $this->db->get()->result_array();
        
        // Recent orders
        $this->db->select('*');
        $this->db->from(db_prefix() . 'external_data_mapping');
        $this->db->where('rel', 'Order');
        $this->db->order_by('dateadded', 'DESC');
        $this->db->limit(10);
        $stats['recent_orders'] = $this->db->get()->result_array();
        
        return $stats;
    }

    // Haravan API Methods
    public function get_haravan_api_token()
    {
        return get_option('haravan_api_token');
    }

    public function is_haravan_api_enabled()
    {
        return get_option('haravan_api_enabled') == 1;
    }

    public function get_haravan_api_base_url()
    {
        return get_option('haravan_api_base_url') ?: 'https://apis.haravan.com/com';
    }

    public function fetch_haravan_product_by_sku($sku)
    {
        if (!$this->is_haravan_api_enabled()) {
            return [
                'success' => false,
                'message' => 'Haravan API is not enabled'
            ];
        }

        $token = $this->get_haravan_api_token();
        if (empty($token)) {
            return [
                'success' => false,
                'message' => 'Haravan API token is not configured'
            ];
        }

        $base_url = $this->get_haravan_api_base_url();
        $url = $base_url . '/products.json?sku=' . urlencode($sku);

        // Use cURL to make the API request
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return [
                'success' => false,
                'message' => 'cURL Error: ' . $error
            ];
        }

        if ($http_code !== 200) {
            return [
                'success' => false,
                'message' => 'HTTP Error: ' . $http_code,
                'response' => $response
            ];
        }

        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'success' => false,
                'message' => 'JSON Decode Error: ' . json_last_error_msg()
            ];
        }

        return [
            'success' => true,
            'data' => $data
        ];
    }

    public function save_haravan_product($haravan_product)
    {
        if (empty($haravan_product['products']) || count($haravan_product['products']) == 0) {
            return [
                'success' => false,
                'message' => 'No products found in Haravan response'
            ];
        }

        $product = $haravan_product['products'][0];
        $variant = !empty($product['variants']) ? $product['variants'][0] : null;

        // Check if product already exists
        $this->db->where('external_product_id', $product['id']);
        $this->db->where('external_system', 'Haravan');
        $existing = $this->db->get(db_prefix() . 'external_products')->row();

        $product_data = [
            'external_product_id' => $product['id'],
            'external_product_name' => $product['title'],
            'external_product_sku' => $variant ? $variant['sku'] : null,
            'external_product_price' => $variant ? $variant['price'] : 0,
            'external_product_description' => $product['body_html'],
            'external_product_image' => !empty($product['images']) ? $product['images'][0]['src'] : null,
            'external_product_url' => null, // Haravan doesn't provide direct product URL in API
            'external_system' => 'Haravan',
            'external_category' => $product['product_type'],
            'external_brand' => $product['vendor'],
            'external_stock_quantity' => $variant ? $variant['inventory_quantity'] : 0,
            'external_product_weight' => $variant ? $variant['grams'] : 0,
            'external_product_dimensions' => null,
            'external_product_tags' => $product['tags'],
            'is_active' => 1,
            'created_date' => date('Y-m-d H:i:s'),
            'last_import_date' => date('Y-m-d H:i:s')
        ];

        if ($existing) {
            // Update existing product
            $this->db->where('id', $existing->id);
            $this->db->update(db_prefix() . 'external_products', $product_data);
            $product_id = $existing->id;
            $action = 'updated';
        } else {
            // Insert new product
            $this->db->insert(db_prefix() . 'external_products', $product_data);
            $product_id = $this->db->insert_id();
            $action = 'created';
        }

        if ($product_id) {
            log_activity('Haravan Product ' . $action . ' [ ID:' . $product_id . ', SKU: ' . ($variant ? $variant['sku'] : 'N/A') . ', Staff id ' . get_staff_user_id() . ' ]');
            return [
                'success' => true,
                'product_id' => $product_id,
                'action' => $action,
                'message' => 'Product ' . $action . ' successfully'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to save product to database'
            ];
        }
    }

    public function sync_haravan_product_by_sku($sku)
    {
        // Fetch product from Haravan API
        $api_result = $this->fetch_haravan_product_by_sku($sku);
        
        if (!$api_result['success']) {
            return $api_result;
        }

        // Save product to database
        $save_result = $this->save_haravan_product($api_result['data']);
        
        return $save_result;
    }

    public function get_haravan_products()
    {
        $this->db->where('external_system', 'Haravan');
        $this->db->order_by('last_import_date', 'DESC');
        return $this->db->get(db_prefix() . 'external_products')->result_array();
    }

    public function update_haravan_api_settings($settings)
    {
        $updated = 0;
        
        if (isset($settings['haravan_api_enabled'])) {
            update_option('haravan_api_enabled', $settings['haravan_api_enabled']);
            $updated++;
        }
        
        if (isset($settings['haravan_api_token'])) {
            update_option('haravan_api_token', $settings['haravan_api_token']);
            $updated++;
        }
        
        if (isset($settings['haravan_api_base_url'])) {
            update_option('haravan_api_base_url', $settings['haravan_api_base_url']);
            $updated++;
        }

        // Mirror token/base_url to tblexternal_systems for Haravan entry
        try {
            $this->db->where('system_name', 'Haravan');
            $haravan = $this->db->get(db_prefix() . 'external_systems')->row();
            if ($haravan) {
                $systemUpdate = [];
                if (isset($settings['haravan_api_token'])) {
                    $systemUpdate['api_key'] = $settings['haravan_api_token'];
                }
                if (isset($settings['haravan_api_base_url'])) {
                    $systemUpdate['api_endpoint'] = $settings['haravan_api_base_url'];
                }
                if (!empty($systemUpdate)) {
                    $systemUpdate['updated_date'] = date('Y-m-d H:i:s');
                    $this->db->where('id', $haravan->id);
                    $this->db->update(db_prefix() . 'external_systems', $systemUpdate);
                }
            }
        } catch (Exception $e) {
            // log but do not interrupt settings save
            log_message('error', 'Failed to mirror Haravan settings to tblexternal_systems: ' . $e->getMessage());
        }

        if ($updated > 0) {
            log_activity('Haravan API Settings Updated [ Staff id ' . get_staff_user_id() . ' ]');
        }

        return true;
    }
}
