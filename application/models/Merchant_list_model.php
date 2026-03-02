<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Merchant_list_model extends App_Model
{
    public function __construct() {
        parent::__construct();
        // $this->load->database();
    }
    
    /**
     * Save merchant list information from the Merchant_list model to the tbl_merchant_list table in your database.
     *
     * @param array $data An array of merchant data to be saved. Each element in the array should be an associative array representing a single merchant with keys 'id', 'display_name', 'login_name', 'logo' and 'total_offer'.
     * @return bool Returns true if data was saved successfully, false otherwise.
     */
    public function save_merchant_list($data) {
        // Start a transaction
        $this->db->trans_start();

       // Check if merchant already exists in database
       $query = $this->db->get_where('tbl_merchant_list', array('merchant_key' => $data['merchant_key']));
       if ($query->num_rows() > 0) {
           // Update existing merchant
           $data['last_updated'] =  date('Y-m-d H:i:s');
           $this->db->where('merchant_key', $merchant['merchant_key']);
           $this->db->update('tbl_merchant_list', $data);
       } else {
           // Insert new merchant
           $this->db->insert('tbl_merchant_list', $data);
       }
        // Complete transaction
        $this->db->trans_complete();

        return $this->db->trans_status();
    }

    public function get_merchant_list(){
        $this->db->select('*');
        $this->db->from('tbl_merchant_list');
        $this->db->order_by('display_name', 'ASC');
        $query = $this->db->get();
        return $query->result_array();
    }
}
