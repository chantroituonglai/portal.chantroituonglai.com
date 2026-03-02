<?php
defined('BASEPATH') or exit('No direct script access allowed');
class Ticket extends ClientsController
{
	public function __construct()
	{
		parent::__construct(); 
		$this->load->model('tickets_model');
		if (!is_client_logged_in()) {
			access_denied(_l('support_print'));
		}
	}
	
	public function export_ticket($id)
	{
		if (!has_contact_permission('support')) {
            set_alert('warning', _l('access_denied'));
            redirect(site_url());
        }

        if (!$id) {
            redirect(site_url());
        }

        $data['ticket'] = $this->tickets_model->get_ticket_by_id($id, get_client_user_id());
        if (!$data['ticket'] || $data['ticket']->userid != get_client_user_id()) {
            show_404();
        }
		$ticket = $this->tickets_model->get_ticket_by_id($id,get_client_user_id());

		if (!$ticket || $ticket->userid != get_client_user_id()) {
			show_404();
		}
		
		$pdf = app_pdf('support-print-ticket', APP_MODULES_PATH.SUPPORT_PRINT_MODULE_NAME.'/libraries/pdf/Support_print_ticket_pdf', $id);
		
		$type = 'I';
		if ($this->input->get('download')) {
			$type = 'D';
		}
		$pdf->output('#Ticket_' . $ticket->ticketid . '_' . $ticket->subject . '_' . _d(date('Y-m-d')) . '.pdf', $type);
		
	}
}
