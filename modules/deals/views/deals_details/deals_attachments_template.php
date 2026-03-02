<?php defined('BASEPATH') or exit('No direct script access allowed');
$data = '<div class="row">';
foreach($attachments as $attachment) {
    $attachment_url = site_url('admin/deals/file_download/deals_attachment/'.$attachment['id']);
    if(!empty($attachment['external'])){
        $attachment_url = $attachment['external_link'];
    }
    $data .= '<div class="display-block lead-attachment-wrapper">';
    $data .= '<div class="col-md-10">';
    $data .= '<div class="pull-left"><i class="'.get_mime_class($attachment['filetype']).'"></i></div>';
    $data .= '<a href="'.$attachment_url.'" target="_blank">'.$attachment['file_name'].'</a>';
    $data .= '<p class="text-muted">'.$attachment["filetype"].'</p>';
    $data .= '</div>';
    $data .= '<div class="col-md-2 text-right">';
    if($attachment['staffid'] == get_staff_user_id() || is_admin()){
    $data .= '<a href="'.base_url().'admin/deals/delete_attachment/'.$attachment['id'].'/'.$attachment['rel_id'].'" class="text-danger" "><i class="fa fa fa-times"></i></a>';
    }
    $data .= '</div>';
    $data .= '<div class="clearfix"></div><hr/>';
    $data .= '</div>';
}
$data .= '</div>';
echo $data;
