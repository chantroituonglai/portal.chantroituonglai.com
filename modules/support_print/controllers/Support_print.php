<?php
defined('BASEPATH') or exit('No direct script access allowed');
class Support_print extends AdminController
{
	public function __construct()
	{
		parent::__construct(); 
		$this->load->model('tickets_model');
		if (!is_admin() && !has_permission('support_print', '', 'view')) {
			access_denied(_l('support_print'));
		}
	}
	
	public function export_ticket($id)
	{
		if (has_permission('support_print', '', 'view')) {
			$ticket = $this->tickets_model->get_ticket_by_id($id);

			if (!$ticket) {
				show_404();
			}
			if (get_option('staff_access_only_assigned_departments') == 1) {
				if (!is_admin()) {
					$this->load->model('departments_model');
					$staff_departments = $this->departments_model->get_staff_departments(get_staff_user_id(), true);
					if (!in_array($ticket->department, $staff_departments)) {
						set_alert('danger', _l('ticket_access_by_department_denied'));
						redirect(admin_url('access_denied'));
					}
				}
			}
			$pdf = app_pdf('support-print-ticket', APP_MODULES_PATH.SUPPORT_PRINT_MODULE_NAME.'/libraries/pdf/Support_print_ticket_pdf', $id);
			
			$type = 'I';
			if ($this->input->get('download')) {
				$type = 'D';
			}
			$pdf->output('#Ticket_' . $ticket->ticketid . '_' . $ticket->subject . '_' . _d(date('Y-m-d')) . '.pdf', $type);
		}
	}
	
	function mail_ticket($ticketid)
	{
		$email_send_pdf = get_option(SUPPORT_PRINT_MODULE_NAME.'_email_send_pdf');
		
		if($email_send_pdf){
			$this->load->model('emails_model');
			set_mailing_constant();
			$pdf = app_pdf('support-print-ticket', APP_MODULES_PATH.SUPPORT_PRINT_MODULE_NAME.'/libraries/pdf/Support_print_ticket_pdf', $ticketid);
			$ticket = $this->tickets_model->get_ticket_by_id($ticketid);
	
			if (!$ticket) {
				show_404();
			}
			$filename = '#Ticket_' . $ticket->ticketid . '_' . $ticket->subject . '_' . _d(date('Y-m-d')) . '.pdf';
			$attach = $pdf->output($filename, 'S');
	
			$email = $ticket->email;
			if($ticket->contactid > 0)
			{
				$contact = $this->clients_model->get_contact($ticket->contactid);
				$email = $contact->email;
			}
			$cc = '';
			$_attachments = $this->tickets_model->get_ticket_attachments($ticketid);//ticket other attachments
			$admin = get_staff_user_id();
			
			$template = mail_template('ticket_created_to_customer',$ticket, $email, $admin == null ? [] : $_attachments, $cc);
			$template->active = 1;
			$template->add_attachment([
					'attachment' => $attach,
					'filename'   => $filename,
					'type'       => 'application/pdf',
				]);
	
			$this->emails_model->mark_as('new-ticket-opened-admin',1);//enabled template slug
			if ($template->send()) {
				$sent = true;
				set_alert('success', _l('send_to_email')._l('sp_alert_success'));
			}
			else
				set_alert('warning', _l('sp_alert_error')._l('send_to_email'));
			$this->emails_model->mark_as('new-ticket-opened-admin',0);//disable template slug again
			redirect(admin_url('tickets/ticket/'.$ticketid));
		}
	}
}
