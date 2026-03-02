<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require __DIR__.'/REST_Controller.php';
class Contacts extends REST_Controller {
	function __construct(){
		parent::__construct();
	}
	public function submit_data_post($datas){
		$datas["send_set_password_email"] = "on";
		$datas["permissions"] = 
		$this->form_validation->set_data($datas);
		$this->form_validation->set_rules('firstname', 'First Name', 'trim|required|max_length[255]');
		$this->form_validation->set_rules('lastname', 'Last Name', 'trim|required|max_length[255]');
		$this->form_validation->set_rules('email', 'Email', 'trim|required|max_length[255]|is_unique['.db_prefix().'contacts.email]',array('is_unique' => 'This %s is already exists'));
	    $this->form_validation->set_rules('customer_id', 'Customer Id', 'trim|required|numeric|callback_client_id_check');
		if ($this->form_validation->run() == false)
		{
			$message = array(
				'status' => false,
				'message' => $this->form_validation->error_array(),
			);
			echo json_encode( $message );
		}
		else{
			$customer_id = $datas['customer_id'];
			unset($datas['customer_id']);
			$this->load->model('authentication_model');	
			$id      = $this->clients_model->add_contact($datas, $customer_id,true);
			if($id > 0 && !empty($id)){
				// success
				$message = array(
				'status' => true,
				'id' => $id,
				'message' => 'Contact added successfully.'
				);
				echo json_encode( $message );
			}
			else{
				// error
				$message = array(
				'status' => false,
				'message' => 'Contact add fail.'
				);
				echo json_encode( $message );
			}
		}
	}

   public function client_id_check($customer_id){
        $this->form_validation->set_message('client_id_check', 'The {field} is Invalid');
        if (empty($customer_id)) {
            return FALSE;
        }
		$query = $this->db->get_where(db_prefix().'clients', array('userid' => $customer_id));
		return $query->num_rows() > 0;
	}
}
