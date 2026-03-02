<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Coupons_model extends App_Model
{  
    // Save a coupon to the database
    public function save_coupon($coupon_data) {
        unset($coupon_data['id']);
        $coupon_key = $coupon_data['coupon_key'];
      
        // Check if the coupon key already exists in the database
        $existing_coupon = $this->db->get_where('tbl_coupon', array('coupon_key' => $coupon_key))->row_array();

        if (!empty($existing_coupon)) {
            // Update the existing coupon
            $this->db->where('coupon_key', $coupon_key);
            $this->db->update('tbl_coupon', $coupon_data);
            return $this->db->affected_rows();
        } else {
            // Insert a new coupon
            $this->db->insert('tbl_coupon', $coupon_data);
            return $this->db->affected_rows();
        }
    }


    // Save a banner to the database
    public function save_banner($banner_data) {
        $this->db->insert('tbl_banners', $banner_data);
        return $this->db->insert_id();
    }

    // Save a category to the database
    public function save_category($category_data) {
        $this->db->insert('tbl_categories', $category_data);
        return $this->db->insert_id();
    }

    // Save additional coupon details to the database
    public function save_coupon_details($coupon_details_data) {
        $this->db->insert('tbl_coupons', $coupon_details_data);
        return $this->db->insert_id();
    }

    // Save a list of coupons to the database
    public function save_coupons($coupons_data) {
        $coupon_ids = array();

        // Loop through each coupon in the list and save it to the database
        foreach ($coupons_data as $coupon) {
            // Save the coupon to the tbl_coupon table
            $coupon_id = $this->save_coupon($coupon['coupon']);

            // Save the banners associated with the coupon to the tbl_banners table
            foreach ($coupon['banners'] as $banner) {
                $banner['coupon_id'] = $coupon_id;
                $this->save_banner($banner);
            }

            // Save the categories associated with the coupon to the tbl_categories table
            foreach ($coupon['categories'] as $category) {
                $category['coupon_id'] = $coupon_id;
                $this->save_category($category);
            }

            // Save the additional details associated with the coupon to the tbl_coupons table
            $coupon_details = $coupon['coupon_details'];
            $coupon_details['coupon_id'] = $coupon_id;
            $this->save_coupon_details($coupon_details);

            $coupon_ids[] = $coupon_id;
        }

        return $coupon_ids;
    }

    // Update existing coupons in the database with new data
    public function update_coupons($coupons_data) {
        $updated_coupon_ids = array();

        // Loop through each coupon in the list and update it in the database if it already exists
        foreach ($coupons_data as $coupon) {
            // Check if the coupon already exists in the database
            $existing_coupon = $this->db->get_where('tbl_coupon', array('id' => $coupon['coupon']['id']))->row_array();
            if ($existing_coupon) {
                // Update the coupon details in the tbl_coupon table
                $this->db->where('id', $coupon['coupon']['id']);
                $this->db->update('tbl_coupon', $coupon['coupon']);

                // Update the banners associated with the coupon in the tbl_banners table
                $this->db->where('coupon_id', $coupon['coupon']['id']);
                $this->db->delete('tbl_banners');
                foreach ($coupon['banners'] as $banner) {
                    $banner['coupon_id'] = $coupon['coupon']['id'];
                    $this->save_banner($banner);
                }

                // Update the categories associated with the coupon in the tbl_categories table
                $this->db->where('coupon_id', $coupon['coupon']['id']);
                $this->db->delete('tbl_categories');
                foreach ($coupon['categories'] as $category) {
                    $category['coupon_id'] = $coupon['coupon']['id'];
                    $this->save_category($category);
                }
                // Update the coupon details in the tbl_coupons table
            $this->db->where('coupon_id', $coupon['coupon']['id']);
            $this->db->update('tbl_coupons', $coupon['coupon_details']);

            // Add the updated coupon ID to the list of updated coupons
            $updated_coupon_ids[] = $coupon['coupon']['id'];
          }
        }

        return $updated_coupon_ids;
    }
}
