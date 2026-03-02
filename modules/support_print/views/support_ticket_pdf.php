<?php
defined('BASEPATH') or exit('No direct script access allowed');

$dimensions    = $pdf->getPageDimensions();
$custom_fields = get_custom_fields('tickets');

$main_heading = get_option(SUPPORT_PRINT_MODULE_NAME.'_print_heading_text');
$heading_color = get_option(SUPPORT_PRINT_MODULE_NAME.'_print_heading_color');


//company logo
// Add logo
$info_left_column = format_organization_info();
$info_right_column = pdf_logo_url();

// Write top right logo and left column info/text
pdf_multi_row($info_left_column, $info_right_column, $pdf, ($dimensions['wk'] / 2) - $dimensions['lm']);
	
$html = '<h1 style="color:'.$heading_color.';" align="center">' . $main_heading .'</h1><hr />';
$pdf->writeHTML($html, true, false, false, false, '');

$html = '';
$html .= '<table width="100%" border="1" cellspacing="0" cellpadding="5" style="font-size:' . ($font_size + 4) . 'px">';
$html .= '<tr><td><b>'._l('sp_ticket_no').'</b> : #'.$ticket->ticketid.'</td><td><b>'._l('sp_ticket_priority').'</b> : '.(!empty($ticket->priority_name)?ticket_priority_translate($ticket->priority):'').'</td><td><b>'._l('sp_ticket_date').'</b> : '._d(date('Y-m-d H:i:s',strtotime($ticket->date))).'</td></tr>';
if(!empty($client)){
	$html .= '<tr><td colspan="3"><b>'._l('sp_customer_name').'</b> : '.$client->company.'</td></tr>';
	$html .= '<tr><td colspan="3"><b>'._l('sp_customer_address').'</b> : '.$client->address.'</td></tr>';
	$html .= '<tr><td><b>'._l('sp_customer_city').'</b> : '.$client->city.'</td><td><b>'._l('sp_customer_state').'</b> : '.$client->state.'</td><td><b>'._l('sp_customer_zipcode').'</b> : '.$client->zip.'</td></tr>';
	
}
else{
	$html .= '<tr><td colspan="2"><b>'._l('sp_customer_name').'</b> : '.$contact;
	if(!empty($contact_phone)) {
		$html .= '<br/><b>' . _l('sp_customer_phone') . ' : </b>' . $contact_phone;
	}
	if(!empty($contact_email)){
	$html .=  '<br/><b>' . _l('sp_customer_email') . ' : </b>'.$contact_email . '<br/>';
	}
	$html .= '</td></tr>';
}
if ($ticket->project_id != 0) {
    $html .= '<tr><td colspan="3"><b>'._l('project').'</b> : '. get_project_name_by_id($ticket->project_id).'</td></tr>';
}
$html .= '<tr><td><b>'._l('sp_ticket_status').'</b> : '.ticket_status_translate($ticket->status).'</td><td colspan="2"><b>'._l('sp_ticket_department').' :</b> '.$ticket->department_name.' </td></tr>';
$html .= '<tr><td colspan="3"><b>'._l('sp_ticket_service').'</b> : '.$ticket->service_name.'</td></tr>';
$html .= '</table>';

$pdf->writeHTML($html, true, false, false, false, '');

//Nature of problem
$html = '';
$html .= '<h3 align="left"><b style="color:'.$heading_color.';">' . _l('sp_custom_field_heading') . '</b></h3>';
$html .='<table border="1" width="100%" bgcolor="#fff" cellspacing="0" cellpadding="5" style="font-size:' . ($font_size + 4) . 'px"><tbody><tr><td><b>'._l('sp_problem_reported').' : </b>'.$ticket->subject.'<br/><br/></td></tr></tbody></table>';
// Check for custom fields
if (count($custom_fields) > 0){
	
	$html .='<table border="1" width="100%" bgcolor="#fff" cellspacing="0" cellpadding="5" style="font-size:' . ($font_size + 4) . 'px"><tbody><tr>';
	$width=0;
	$total_cols=12;
	foreach ($custom_fields as $field){
	
		if(is_admin() || !$field['only_admin'])
		{
			if($width>=$total_cols)//new line if cols width is 12
			{
				$html .="</tr><tr>";
				$width=0;
			}
			$width += $field['bs_column'];
		
				$value = get_custom_field_value($ticket->ticketid, $field['id'], 'tickets');
				$value = $value === '' ? '-' : $value;
				$html .= '<td width="'.(100 / ($total_cols/$field['bs_column'])).'%"><b>' . ucfirst($field['name']) . ' </b> : ' . $value . '</td>';
		}	
	}
	$html .='</tr></tbody></table>';
	$pdf->writeHTML($html, true, false, false, false, '');
}
//service details
$html = '<h3 align="left"><b style="color:'.$heading_color.';">' . _l('sp_service_details_heading') . '</b></h3>';
$html .= '<table border="1" width="100%" cellspacing="0" cellpadding="5" style="font-size:' . ($font_size + 4) . 'px">';
$html .= '<tr><td width="66%"><b>'._l('sp_engineer_remark').' :</b><br/><br/><br/><br/><br/></td>';
$html .= '<td width="34%"><b>'._l('sp_status_after_service').' :</b><br/>'._l('sp_please_circle').'<br/>'._l('sp_statuses_after_service').'</td></tr>';
$html .= '<tr><td width="33%"><b>'._l('sp_ticket_assigned').' : </b>'.get_staff_full_name($ticket->assigned).'</td>';
$html .= '<td width="33%"><b>'._l('sp_ticket_assigned_mobile').' : </b>'.(!empty($staff)?$staff->phonenumber:'').'</td>';
$html .= '<td width="34%"><b>'._l('sp_ticket_assigned_sign').' :</b></td></tr>';
$html .='</table>';
$pdf->writeHTML($html, true, false, false, false, '');

//customer feedback
$html = '<h3 align="left"><b style="color:'.$heading_color.';">' . _l('sp_feedback_heading') . '</b></h3>';
$html .= '<table border="1" width="100%" cellspacing="0" cellpadding="5" style="font-size:' . ($font_size + 4) . 'px">';
$html .= '<tr><td colspan="3"><b>'._l('sp_rating_heading').' </b> : &nbsp;&nbsp;';
$html .= ' [ &nbsp;] '._l('sp_rating_1').'&nbsp;&nbsp; [ &nbsp;] '._l('sp_rating_2').'&nbsp;&nbsp; [ &nbsp;] '._l('sp_rating_3').'&nbsp;&nbsp; [ &nbsp;] '._l('sp_rating_4').'</td></tr>';
$html .= '<tr><td width="50%"><b>'._l('sp_customer_email').' :</b></td>';
$html .= '<td width="25%"><b>'._l('sp_ticket_date').' :</b></td>';
$html .= '<td width="25%"><b>'._l('sp_customer_place').' :</b></td></tr>';
$html .= '<tr><td><b>'._l('sp_customer_name_and_sign').' :</b><br/><br/><b>'._l('sp_customer_mobile').' :</b></td>';
$html .= '<td colspan="2"><b>'._l('sp_customer_suggestion').' :</b></td></tr>';
$html .='</table>';
$pdf->writeHTML($html, true, false, false, false, '');

//bank details 
$html = '<table border="0" width="100%" cellspacing="0" cellpadding="5" style="font-size:' . ($font_size + 4) . 'px">';
$html .= '<tr><td>'.nl2br(get_option(SUPPORT_PRINT_MODULE_NAME.'_print_bank_details')).'</td></tr>';
$html .='</table>';
$pdf->writeHTML($html, true, false, false, false, '');

if(ob_get_length() > 0 && ENVIRONMENT == 'production'){
	ob_end_clean();
}
