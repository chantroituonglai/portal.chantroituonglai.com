<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require __DIR__.'/REST_Controller.php';
class Staffs extends REST_Controller {
	function __construct(){
		parent::__construct();	
	}
	public function submit_data_post($datas){
		$this->form_validation->set_data($datas);
		$this->form_validation->set_rules('firstname', 'First Name', 'trim|required|max_length[600]', array('is_unique' => 'This %s already exists please enter another Staff First Name'));
		$this->form_validation->set_rules('lastname', 'Last Name', 'trim|required|max_length[600]', array('is_unique' => 'This %s already exists please enter another Staff Last Name'));
        $this->form_validation->set_rules('email', 'Email', 'trim|required|valid_email', array('is_unique' => 'This %s already exists please enter another Staff Email'));
        $this->form_validation->set_rules('password', 'Password', 'trim|required', array('is_unique' => 'This %s already exists please enter another Staff password'));
		if ($this->form_validation->run() == false){
			$message = array(
				'status' => false,
				'message' => $this->form_validation->error_array(),
			);
			echo json_encode( $message );
		}else{
			 $this->load->model('staff_model');
            $id = $this->staff_model->add($datas);
            
			if($id > 0 && !empty($id)){
				// success
				$message = array(
				'status' => true,
				'id' => $id,
				'message' => 'Staff added successfully.'
				);
				echo json_encode( $message );
			}
			else{
				// error
				$message = array(
				'status' => false,
				'message' => 'Staff add fail.'
				);
				echo json_encode( $message );
			}
		}
	}

   
}
