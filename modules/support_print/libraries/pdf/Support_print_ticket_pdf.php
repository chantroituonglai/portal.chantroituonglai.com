<?php
defined('BASEPATH') or exit('No direct script access allowed');
include_once(LIBSPATH . 'pdf/App_pdf.php');

class Support_print_ticket_pdf extends App_pdf
{
	protected $ticket_id;
	
	public function __construct($ticket_id)
	{
		parent::__construct();
		$this->ticket_id = $ticket_id;
	}
	
	 public function Footer() {
     	if (get_option(SUPPORT_PRINT_MODULE_NAME.'_print_footer') !== ''){
			$footer_text = get_option(SUPPORT_PRINT_MODULE_NAME.'_print_footer');
			$this->SetY($this->footerY);
			$this->SetFont($this->get_font_name(), '', $this->get_font_size());
			$this->SetFont($this->get_font_name(), 'B', 8);
			$this->SetTextColor(142, 142, 142);
			$this->Cell(0, 5, $footer_text, 0, false, 'C', 0, '', 0, false, 'T', 'M');
		}
        parent::Footer();
         
     }
	
	public function prepare()
	{
		$ticket = $this->ci->tickets_model->get_ticket_by_id($this->ticket_id);

        if (!$ticket) {
            show_404();
        }
		if (get_option('staff_access_only_assigned_departments') == 1) {
			if (!is_admin()) {
				$this->ci->load->model('departments_model');
				$staff_departments = $this->ci->departments_model->get_staff_departments(get_staff_user_id(), true);
				if (!in_array($ticket->department, $staff_departments)) {
					set_alert('danger', _l('ticket_access_by_department_denied'));
					redirect(admin_url('access_denied'));
				}
			}
		}
		
		$this->SetTitle($ticket->subject);
		
		$data['ticket'] = $ticket;
		$data['contact'] = $ticket->name;
		$data['contact_email'] = $ticket->email;
		$data['contact_phone'] = '';
		if($ticket->contactid > 0)
		{
			$contact = $this->ci->clients_model->get_contact($ticket->contactid);
			$data['contact'] = $contact->firstname.' '.$contact->lastname;
			$data['contact_email'] = $contact->email;
			$data['contact_phone'] = $contact->phonenumber;
			$data['client'] = $this->ci->clients_model->get($ticket->userid);	
		}	
		if($ticket->assigned)
			$data['staff'] = get_staff($ticket->assigned);
	
		$this->set_view_vars($data);
		return $this->build();
	}
	
	protected function type()
	{
		return 'support-print-ticket';
	}
	
	protected function file_path()
	{
		$customPath = APP_MODULES_PATH.SUPPORT_PRINT_MODULE_NAME . '/views/my_support_ticket_pdf.php';
		$actualPath = APP_MODULES_PATH.SUPPORT_PRINT_MODULE_NAME . '/views/support_ticket_pdf.php';
	
		if (file_exists($customPath)) {
			$actualPath = $customPath;
		}
	
		return $actualPath;
	}
	
	public function get_format_array()
	{
		return  [
			'orientation' => (get_option(SUPPORT_PRINT_MODULE_NAME.'_print_orientation') == 'L'?'L':'P'),
			'format'      => (get_option(SUPPORT_PRINT_MODULE_NAME.'_print_orientation') == 'L'?'Landscape':'Portrait'),
		];
	}
}
