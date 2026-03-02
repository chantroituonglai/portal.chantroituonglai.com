<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Icontext_list_model extends App_Model
{
    public function __construct() {
        parent::__construct();
        // $this->load->database();
    }

     // Function to save icontext list information to the tbl_icontext_list table
     public function save_icontext_list($icontext_list) {
        // Loop through each icontext and save its information to the tbl_icontext_list table
        foreach ($icontext_list as $icontext) {
            $data = array(
                'icon_text' => $icontext['icon_text'],
                'merchant' => $icontext['merchant'],
                'total_offer' => $icontext['total_offer']
            );
            $this->db->insert('tbl_icontext_list', $data);
        }
    }

    // Function to get the list of icontexts from the tbl_icontext_list table
    public function get_icontext_list() {
        $query = $this->db->get('tbl_icontext_list');
        return $query->result_array();
    }
}