<?php

defined('BASEPATH') or exit('No direct script access allowed');

class External_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    public function add_record($data)
    {

        $data['dateadded'] = date('Y-m-d H:i:s');
        $this->db->insert(db_prefix() . 'external_data_mapping', $data);
        
        return $this->db->insert_id();
    }

    public function get_record($uniquekey)
    {

        $this->db->select('*');
        $this->db->where('uniquekey', $uniquekey);

        return $this->db->get(db_prefix() . 'external_data_mapping')->row();
    }

    public function update_record($uniquekey, $data)
    {

        $this->db->where('uniquekey', $uniquekey);
        $this->db->update(db_prefix() . 'external_data_mapping', $data);

        $updated = $this->db->affected_rows() > 0;
    
        return $updated;
    }

    /**
     * Update a record with additional validation for target_id
     * Ensures target_id is properly formatted and valid
     * 
     * @param string $uniquekey The unique key of the record to update
     * @param array $data The data to update
     * @param bool $validate Whether to validate the target_id
     * @return bool Whether the update was successful
     */
    public function update_record_with_validation($uniquekey, $data, $validate = true)
    {
        // Validate target_id if present and validation is enabled
        if ($validate && isset($data['target_id'])) {
            $target_id = $data['target_id'];
            
            // Ensure target_id is not empty or invalid
            if (empty($target_id) || $target_id === '""' || $target_id === "''" || $target_id === '0') {
                return false;
            }
            
            // Ensure target_id is numeric or a valid Haravan ID format
            if (!is_numeric($target_id) && !preg_match('/^\d+$/', $target_id)) {
                return false;
            }
        }
        
        $this->db->where('uniquekey', $uniquekey);
        $this->db->update(db_prefix() . 'external_data_mapping', $data);
        
        return $this->db->affected_rows() > 0;
    }

    public function get_sku_from_barcode($mapping_id)
    {

        $this->db->select('*');
        $this->db->where('mapping_id', ''.$mapping_id);
        $this->db->where('mapping_type', 'fast_barco');
        $result =  $this->db->get(db_prefix() . 'external_products_mapping')->row_array();
        return $result;
    }
   
    public function get_sku_from_barcode_v2($mapping_id, $mapping_type)
    {

        $this->db->select('*');
        $this->db->where('mapping_id', $mapping_id);
        $this->db->where('mapping_type', $mapping_type);
        $result =  $this->db->get(db_prefix() . 'external_products_mapping')->row_array();
        return $result;
    }

     /**
     * Lấy danh sách đơn hàng Big C
     * @param array $opts (limit, offset)
     * @return array
     */
    public function get_bigc_orders(array $opts = [])
    {
        $this->db->select('id, uniquekey, rel, root_id, target_id, dateadded, status, data');
        $this->db->from(db_prefix() . 'external_data_mapping');
        $this->db->like('uniquekey', 'BIGC_PO_', 'after');       // match B2BBIGC_%
        $this->db->where('rel', 'Order');

        if (isset($opts['limit'])) {
            $offset = isset($opts['offset']) ? (int)$opts['offset'] : 0;
            $this->db->limit((int)$opts['limit'], $offset);
        }
        $this->db->order_by('id', 'DESC');

        return $this->db->get()->result_array();
    }

    /**
     * Lấy danh sách đơn hàng Coop
     * @param array $opts (limit, offset)
     * @return array
     */
    public function get_coop_orders(array $opts = [])
    {
        $this->db->select('id, uniquekey, rel, root_id, target_id, dateadded, status, data');
        $this->db->from(db_prefix() . 'external_data_mapping');
        $this->db->like('uniquekey', 'B2BCOOPMART', 'after');       // match B2BBIGC_%
        $this->db->where('rel', 'Order');

        if (isset($opts['limit'])) {
            $offset = isset($opts['offset']) ? (int)$opts['offset'] : 0;
            $this->db->limit((int)$opts['limit'], $offset);
        }
        $this->db->order_by('id', 'DESC');

        return $this->db->get()->result_array();
    }

    /**
     * Lấy 1 bản ghi theo ID (dùng cho /order/{id})
     */
    public function get_record_by_id($id)
    {
        return $this->db->get_where(
            db_prefix() . 'external_data_mapping',
            ['id' => (int)$id]
        )->row();
    }

    /**
     * "Ẩn" đơn Big C: đổi uniquekey thành DELETE_B2BBIGC_xxx(_n)
     * @return string|false  chuỗi uniquekey mới hoặc false nếu không đổi
     */
    public function trash_bigc_order(int $id)
    {
        $tbl = db_prefix() . 'external_data_mapping';

        // 1) lấy bản ghi
        $row = $this->db->get_where($tbl, ['id' => $id])->row();
        if (
            !$row ||
            strpos($row->uniquekey, 'BIGC_PO_') !== 0 ||       // không phải Big C
            !empty($row->target_id)                            // đã có target_id
        ){
            return false;
        }

        // 2) tạo tên mới duy nhất
        $base = 'DELETE_' . $row->uniquekey;   // ví dụ: DELETE_B2BBIGC_2509...
        $new  = $base;
        $n    = 1;

        do {
            $exists = $this->db->where('uniquekey', $new)
                            ->count_all_results($tbl);
            if ($exists) {
                $n++;
                $new = $base . '_' . $n;       // …_2, …_3 …
            }
        } while ($exists);

        // 3) cập nhật
        $this->db->where('id', $id)
                ->update($tbl, ['uniquekey' => $new]);

        return $this->db->affected_rows() ? $new : false;
    }

    /**
     * "Ẩn" đơn COOP: đổi uniquekey thành DELETE_B2BCOOPMART_xxx(_n)
     * @return string|false  chuỗi uniquekey mới hoặc false nếu không đổi
     */
    public function trash_coop_order(int $id)
    {
        $tbl = db_prefix() . 'external_data_mapping';

        // 1) lấy bản ghi
        $row = $this->db->get_where($tbl, ['id' => $id])->row();
        if (
            !$row ||
            strpos($row->uniquekey, 'B2BCOOPMART_') !== 0 ||   // không phải COOP
            !empty($row->target_id)                            // đã có target_id
        ){
            return false;
        }

        // 2) tạo tên mới duy nhất
        $base = 'DELETE_' . $row->uniquekey;   // ví dụ: DELETE_B2BCOOPMART_93192432-00...
        $new  = $base;
        $n    = 1;

        do {
            $exists = $this->db->where('uniquekey', $new)
                            ->count_all_results($tbl);
            if ($exists) {
                $n++;
                $new = $base . '_' . $n;       // …_2, …_3 …
            }
        } while ($exists);

        // 3) cập nhật
        $this->db->where('id', $id)
                ->update($tbl, ['uniquekey' => $new]);

        return $this->db->affected_rows() ? $new : false;
    }

    /**
     * "Ẩn" đơn hàng từ email COOP: đổi uniquekey thành DELETE_EMAIL_xxx
     * @param int $id - ID của email order cần xóa
     * @return string|false - chuỗi uniquekey mới hoặc false nếu không đổi
     */
    public function trash_email_order(int $id)
    {
        $tbl = db_prefix() . 'external_data_mapping';

        // 1) lấy bản ghi
        $row = $this->db->get_where($tbl, ['id' => $id])->row();
        if (
            !$row ||
            $row->rel !== 'EMAILORDER'     // không phải email order
        ){
            return false;
        }

        // 2) tạo tên mới duy nhất
        $base = 'DELETE_EMAIL_' . $id;    // Sử dụng ID vì uniquekey của email có thể dài và phức tạp
        $new  = $base;
        $n    = 1;

        do {
            $exists = $this->db->where('uniquekey', $new)
                            ->count_all_results($tbl);
            if ($exists) {
                $n++;
                $new = $base . '_' . $n;       // …_2, …_3 …
            }
        } while ($exists);

        // 3) cập nhật
        $this->db->where('id', $id)
                ->update($tbl, ['uniquekey' => $new]);

        return $this->db->affected_rows() ? $new : false;
    }

    /**
     * Lấy danh sách đơn hàng Lotte
     * @param array $opts (limit, offset)
     * @return array
     */
    public function get_lotte_orders(array $opts = [])
    {
        $this->db->select('id, uniquekey, rel, root_id, target_id, dateadded, status, data');
        $this->db->from(db_prefix() . 'external_data_mapping');
        $this->db->like('uniquekey', 'LOTTE', 'after');
        $this->db->where('rel', 'Order');

        if (isset($opts['limit'])) {
            $offset = isset($opts['offset']) ? (int)$opts['offset'] : 0;
            $this->db->limit((int)$opts['limit'], $offset);
        }
        $this->db->order_by('id', 'DESC');

        return $this->db->get()->result_array();
    }
    
    /**
     * "Ẩn" đơn Lotte: đổi uniquekey thành DELETE_LOTTE_xxx(_n)
     * @return string|false  chuỗi uniquekey mới hoặc false nếu không đổi
     */
    public function trash_lotte_order(int $id)
    {
        $tbl = db_prefix() . 'external_data_mapping';

        // 1) lấy bản ghi
        $row = $this->db->get_where($tbl, ['id' => $id])->row();
        if (
            !$row ||
            strpos($row->uniquekey, 'LOTTE') !== 0 ||   // không phải Lotte
            !empty($row->target_id)                     // đã có target_id
        ){
            return false;
        }

        // 2) tạo tên mới duy nhất
        $base = 'DELETE_' . $row->uniquekey;   // ví dụ: DELETE_LOTTE250516...
        $new  = $base;
        $n    = 1;

        do {
            $exists = $this->db->where('uniquekey', $new)
                            ->count_all_results($tbl);
            if ($exists) {
                $n++;
                $new = $base . '_' . $n;       // …_2, …_3 …
            }
        } while ($exists);

        // 3) cập nhật
        $this->db->where('id', $id)
                ->update($tbl, ['uniquekey' => $new]);

        return $this->db->affected_rows() ? $new : false;
    }

}