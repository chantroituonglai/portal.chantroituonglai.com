<?php

defined('BASEPATH') OR exit('No direct script access allowed');
require __DIR__.'/REST_Controller.php';
class clients extends REST_Controller {
	function __construct(){
		parent::__construct();	
	}
	
	public function submit_data_post($datas){
		$this->form_validation->set_data($datas);
		$this->form_validation->set_rules('company', 'Company', 'trim|required|max_length[600]', array('is_unique' => 'This %s already exists please enter another Company'));
		if ($this->form_validation->run() == false){
			$message = array(
				'status' => false,
				'message' => $this->form_validation->error_array(),
			);
			echo json_encode( $message );
		}
		else{
			$id      = $this->clients_model->add($datas);
			if($id > 0 && !empty($id)){
				// success
				$message = array(
				'status' => true,
				'id' => $id,
				'message' => 'clients added successfully'
				);
				echo json_encode( $message );
			}
			else{
				// error
				$message = array(
				'status' => false,
				'message' => 'clients add fail.'
				);
				echo json_encode( $message );
			}
		}
	} 
}
