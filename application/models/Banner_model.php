<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Banner_model extends App_Model
{  
    public function __construct() {
        parent::__construct();
        // $this->load->database();
    }

    // Function to insert a new banner into the tbl_banners table
    public function insert_banner($coupon_id, $link, $width=0, $height=0) {
        $data = array(
            'coupon_id' => $coupon_id,
            'link' => $link,
            'width' => $width,
            'height' => $height
        );
        $this->db->insert('tbl_banners', $data);
        return $this->db->insert_id();
    }

    // Function to update an existing banner in the tbl_banners table
    public function update_banner($id, $coupon_id, $link, $width=0, $height=0) {
        $data = array(
            'coupon_id' => $coupon_id,
            'link' => $link,
            'width' => $width,
            'height' => $height
        );
        $this->db->where('id', $id);
        $this->db->update('tbl_banners', $data);
    }

    // Function to delete a banner from the tbl_banners table
    public function delete_banner($id) {
        $this->db->where('id', $id);
        $this->db->delete('tbl_banners');
    }
}