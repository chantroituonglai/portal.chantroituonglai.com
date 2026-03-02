<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Category_model extends App_Model
{   
    /**
     * Save category data to tbl_categories table
     * @param int $coupon_id
     * @param string $category_name
     * @return bool
     */
    public function save_category($coupon_id, $category_name) {
        $data = array(
            'coupon_id' => $coupon_id,
            'category_name' => $category_name
        );
        return $this->db->insert('tbl_categories', $data);
    }

    /**
     * Get all categories for a coupon
     * @param int $coupon_id
     * @return array
     */
    public function get_categories_by_coupon_id($coupon_id) {
        $this->db->where('coupon_id', $coupon_id);
        $query = $this->db->get('tbl_categories');
        return $query->result_array();
    }
    
    /**
     * Delete all categories for a coupon
     * @param int $coupon_id
     * @return bool
     */
    public function delete_categories_by_coupon_id($coupon_id) {
        $this->db->where('coupon_id', $coupon_id);
        return $this->db->delete('tbl_categories');
    }
}