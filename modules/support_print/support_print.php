<?php
defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: BecomeDigital Print Support Ticket Module
Description: Module will Print details of support ticket as PDF and attach this pdf when ticket is created.
Author: <a href="https://becomedigital.in" target="_blank">Become Digital (An Initiative of DSU INFOTECH&reg;)</a>
Version: 1.0.0
Requires at least: 1.0.*
*/

define('SUPPORT_PRINT_MODULE_NAME', 'support_print');

$CI = &get_instance();

hooks()->add_action('admin_init', 'support_print_admin_init_hook');
hooks()->add_filter('module_'.SUPPORT_PRINT_MODULE_NAME.'_action_links', 'module_support_print_action_links');
hooks()->add_action('ticket_admin_single_page_loaded','sp_hook_ticket_admin_single_page_loaded');
hooks()->add_filter('ticket_created','sp_hook_ticket_created');
//client side
if(is_client_logged_in())
	hooks()->add_action('customers_content_container_start','sp_hook_customers_content_container_start');
	

/**
 * Add additional settings for this module in the module list area
 * @param  array $actions current actions
 * @return array
 */
function module_support_print_action_links($actions)
{
	$actions[] = '<a href="' . admin_url('settings?group=support_print_settings') . '">' . _l('settings') . '</a>';
	return $actions;
}

/**
* Register activation module hook
*/
register_activation_hook(SUPPORT_PRINT_MODULE_NAME, 'support_print_activation_hook');

function support_print_activation_hook()
{
	$CI = &get_instance();
	require_once(__DIR__ . '/install.php');
}

/**
* Register language files, must be registered if the module is using languages
*/
register_language_files(SUPPORT_PRINT_MODULE_NAME, [SUPPORT_PRINT_MODULE_NAME]);

/**
*	Admin Init Hook for module
*/
function support_print_admin_init_hook()
{
	$CI = &get_instance();
	/*Add customer permissions */
	$capabilities = [];

	$capabilities['capabilities'] = [
		'view'   => _l('permission_view') . '(' . _l('permission_global') . ')',
	];
	register_staff_capabilities('support_print', $capabilities, _l('support_print'));
	
	/**  Add Tab In Settings Tab of Setup **/
	if (is_admin()) {
		$CI->app_tabs->add_settings_tab('support_print_settings', [
			'name'     => _l('support_print_settings'),
			'view'     => 'support_print/support_print_settings',
			'position' => 100,
		]);
	}
}	
	
function sp_hook_ticket_admin_single_page_loaded()
{
	if (has_permission('support_print', '', 'view')) {
		$CI = &get_instance();
		$ticket_id = $CI->uri->segment(4);
		$script = '<script>$(".single-ticket-status-label").after(\'<a href="'.base_url('support_print/export_ticket/'.$ticket_id.'?download=1').'"  class="btn btn-info btn-with-tooltip mleft15 mright5 pull-left" data-toggle="tooltip" title="'._l('view_pdf').'" data-placement="bottom"><i class="fa fa-file-pdf-o"></i></a> <a href="'.base_url('support_print/export_ticket/'.$ticket_id).'"  class="btn btn-primary btn-sm btn-with-tooltip mleft15  mright5 pull-left" data-toggle="tooltip" title="'._l('print').'" data-placement="bottom" target="_blank"><i class="fa fa-print"></i></a> <a href="'.base_url('support_print/mail_ticket/'.$ticket_id).'"  class="btn btn-primary btn-sm btn-with-tooltip mleft15  mright5 pull-left" data-toggle="tooltip" title="'._l('send_to_email').'" data-placement="bottom"><i class="fa fa-envelope"></i></a>\');</script>';
		echo $script;
	}
	
}

function sp_hook_customers_content_container_start()
{
	$CI = &get_instance();
	
	if(strpos($_SERVER['REQUEST_URI'],'/clients/ticket/')){ 
		$ticket_id = $CI->uri->segment(3);
		$url_pdf = '<a href="'.base_url('support_print/ticket/export_ticket/'.$ticket_id.'?download=1').'"  class="btn btn-info btn-with-tooltip mleft15 mright5 pull-left" data-toggle="tooltip" title="'._l('view_pdf').'" data-placement="bottom"><i class="fa fa-file-pdf-o"></i></a> ';
		$url_print = '<a href="'.base_url('support_print/ticket/export_ticket/'.$ticket_id).'"  class="btn btn-primary btn-sm btn-with-tooltip mleft15  mright5 pull-left" data-toggle="tooltip" title="'._l('print').'" data-placement="bottom" target="_blank"><i class="fa fa-print"></i></a>';
		if(is_numeric($ticket_id)){
			echo $url_pdf;
			echo $url_print;
		}	
	}
}
/** hook after ticket is created**/
function sp_hook_ticket_created($ticketid)
{
	$email_send_pdf = get_option(SUPPORT_PRINT_MODULE_NAME.'_email_send_pdf');
	
	if($email_send_pdf){
		$CI = &get_instance();
		$CI->load->model('tickets_model');
		$CI->load->model('emails_model');
		set_mailing_constant();
		$pdf = app_pdf('support-print-ticket', APP_MODULES_PATH.SUPPORT_PRINT_MODULE_NAME.'/libraries/pdf/Support_print_ticket_pdf', $ticketid);
		$ticket = $CI->tickets_model->get_ticket_by_id($ticketid);

		if (!$ticket) {
			show_404();
		}
		$filename = '#Ticket_' . $ticket->ticketid . '_' . $ticket->subject . '_' . _d(date('Y-m-d')) . '.pdf';
		$attach = $pdf->output($filename, 'S');

		$email = $ticket->email;
		if($ticket->contactid > 0)
		{
			$contact = $CI->clients_model->get_contact($ticket->contactid);
			$email = $contact->email;
		}
		$cc = '';
		$_attachments = $CI->tickets_model->get_ticket_attachments($ticketid);//ticket other attachments
		$admin = get_staff_user_id();
		
		$template = mail_template('ticket_created_to_customer',$ticket, $email, $admin == null ? [] : $_attachments, $cc);
		$template->active = 1;
		$template->add_attachment([
				'attachment' => $attach,
				'filename'   => $filename,
				'type'       => 'application/pdf',
			]);

		$CI->emails_model->mark_as('new-ticket-opened-admin',1);//enabled template slug
		if ($template->send()) {
			$sent = true;
		}
		$CI->emails_model->mark_as('new-ticket-opened-admin',0);//disable template slug again
	}
}

