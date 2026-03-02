<?php defined('BASEPATH') or exit('No direct script access allowed');


function deals_display_money($amount, $currency = null)
{
    return app_format_money($amount, get_base_currency());
}

function deals_default_currency()
{
    return get_base_currency()->name;
}

function deals_display_time($value, $no_str = null)
{
    return _dt($value);
}

function join_data($table, $select = '*', $where = null, $join = null, $row = null, $order = null)
{
    $CI = &get_instance();
    if ($select == '*') {
        $CI->db->select('*', false);
    } else {
        $CI->db->select("$select", false);
    }
    $CI->db->from($table);
    if (!empty($join)) {
        foreach ($join as $tbl => $wh) {
            $CI->db->join($tbl, $wh, 'left');
        }
    }
    if (!empty($where)) {
        $CI->db->where($where);
    }
    if (!empty($order)) {
        // is array
        if (is_array($order)) {
            foreach ($order as $key => $value) {
                $CI->db->order_by($key, $value);
            }
        } else {
            $CI->db->order_by($order);
        }
    }

    $query = $CI->db->get();
    if (!empty($row) && $row === 'array') {
        $result = $query->result_array();
    } else if (!empty($row) && $row === 'object') {
        $result = $query->result();
    } else {
        $result = $query->row();
    }
    return $result;
}

function get_deals_row($table, $where, $fields = null)
{
    $CI = &get_instance();
    $query = $CI->db->where($where)->get($table);
    if ($query->num_rows() > 0) {
        $row = $query->row();
        if (!empty($fields)) {
            return $row->$fields;
        } else {
            return $row;
        }
    }
}

function my_id()
{
    $CI = &get_instance();
    return $CI->session->userdata('user_id');
}

function deals_render_table($data, $where = null, $where_in = null)
{

    $CI = &get_instance();
    $CI->load->model('datatabless');
    $output = array(
        "draw" => intval($_POST["draw"]),
        "iTotalRecords" => $CI->datatabless->get_all_data($where, $where_in),
        "iTotalDisplayRecords" => $CI->datatabless->get_filtered_data($where, $where_in),
        "aaData" => $data
    );
    echo json_encode($output);
    exit();
}


function make_deals_datatables($where = null, $where_in = null, $old = null)
{
    $CI = &get_instance();
    $CI->load->model('datatabless');
    $CI->datatabless->make_deals_query();
    if (!empty($where)) {
        $CI->db->where($where);
    }
    $company = $CI->input->post('company');
    if (!empty($company)) {
        $CI->db->group_start();
        if ($CI->db->version() >= 8) {
            $sq = $CI->db->escape('\\b' . ($company) . '\\b');
        } else {
            $sq = $CI->db->escape('[[:<:]]' . ($company) . '[[:>:]]');
        }
        $CI->db->where('tbl_deals.client_id REGEXP', $sq, false);
        $CI->db->group_end();
    }
    if (!empty($where_in)) {
        $CI->db->where_in($where_in[0], $where_in[1]);
    }
    if ($_POST["length"] != -1) {
        $CI->db->limit($_POST['length'], $_POST['start']);
    }
    $query = $CI->db->get();
    return $query->result();
}

function get_deals_staff_details($user_id = null, $type = null, $where = null)
{
    $CI = &get_instance();
    $CI->db->select('tblstaff.*', FALSE);
    $CI->db->from('tblstaff');
    if (!empty($where)) {
        $CI->db->where($where);
    }
    if (!empty($user_id)) {
        $CI->db->where('tblstaff.staffid', $user_id);
        $query_result = $CI->db->get();
        $result = $query_result->row();
    } else {
        $CI->db->where('tblstaff.role !=', 2);
        $CI->db->where('tblstaff.activated', 1);
        $query_result = $CI->db->get();
        if (!empty($type)) {
            $result = $query_result->result_array();
        } else {
            $result = $query_result->result();
        }
    }
    return $result;
}

function get_deals_order_by($tbl, $where = null, $order_by = null, $ASC = null, $limit = null, $type = null)
{

    $CI = &get_instance();
    $CI->db->from($tbl);
    if (!empty($where) && $where != 0) {
        $CI->db->where($where);
    }
    if (!empty($ASC)) {
        $order = 'ASC';
    } else {
        $order = 'DESC';
    }
    $CI->db->order_by($order_by, $order);
    if (!empty($limit)) {
        $CI->db->limit($limit);
    }
    $query_result = $CI->db->get();
    if (!empty($type) && $type == 'array') {
        $result = $query_result->result_array();
    } else if (!empty($type)) {
        $result = $query_result->row();
    } else {
        $result = $query_result->result();
    }
    return $result;
}

function get_deals_result($tbl, $where = null, $type = null)
{
    $CI = &get_instance();
    $CI->db->select('*');
    $CI->db->from($tbl);
    if (!empty($where) && $where != 0) {
        $CI->db->where($where);
    }
    if (!empty($_POST["length"]) && $_POST["length"] != -1) {
        $CI->db->limit($_POST['length'], $_POST['start']);
    }
    $query_result = $CI->db->get();
    if (!empty($type) && $type == 'array') {
        $result = $query_result->result_array();
    } else if (!empty($type)) {
        $result = $query_result->row();
    } else {
        $result = $query_result->result();
    }
    return $result;
}

function client_name($client_id = null)
{
    $CI = &get_instance();
    if (empty($client_id)) {
        $client_id = $CI->session->userdata('client_id');
    }
    if (is_numeric($client_id)) {
        $clientInfo = $CI->db->where('userid', $client_id)->get('tblclients')->row();
    }
    if (!empty($clientInfo)) {
        return $clientInfo->company;
    } else {
        return lang('undefined_client');
    }
}

function fullname($user_id = null)
{
    $CI = &get_instance();
    if (empty($user_id)) {
        $user_id = $CI->session->userdata('staffid');
    }

    $userInfo = $CI->db->where('staffid', $user_id)->get('tblstaff')->row();
    if (!empty($userInfo)) {
        return $userInfo->firstname . ' ' . $userInfo->lastname;
    } else {
        return 'Undefined user';
    }
}

function deals_move_temp_file($file_name, $target_path, $related_to = "", $source_path = NULL, $static_file_name = "")
{
    $new_filename = unique_filename($target_path, $file_name);
    //if not provide any source path we'll fi   nd the default path
    if (!$source_path) {
        $source_path = getcwd() . "/uploads/temp/" . $file_name;
    }

    //check destination directory. if not found try to create a new one
    if (!is_dir($target_path)) {
        if (!mkdir($target_path, 0777, true)) {
            die('Failed to create file folders.');
        }
    }

    //overwrite extisting logic and use static file name
    if ($static_file_name) {
        $new_filename = $static_file_name;
    }

    //check the file type is data or file. then copy to destination and remove temp file
    if (starts_deals_with($source_path, "data")) {
        copy_text_based_deals_image($source_path, $target_path . $new_filename);
        return $new_filename;
    } else {
        if (file_exists($source_path)) {
            copy($source_path, $target_path . $new_filename);
            unlink($source_path);
            return $new_filename;
        }
    }
    return false;
}

function copy_text_based_deals_image($image)
{
    $images_extentions = array("jpg", "JPG", "jpeg", "JPEG", "png", "PNG", "gif", "GIF", "bmp", "BMP");
    $image_parts = explode(".", $image);
    $image_end_part = end($image_parts);

    if (in_array($image_end_part, $images_extentions) == true) {
        return 1;
    } else {
        return 0;
    }
}

function check_image_deals_extension($image)
{
    $images_extentions = array("jpg", "JPG", "jpeg", "JPEG", "png", "PNG", "gif", "GIF", "bmp", "BMP");
    $image_parts = explode(".", $image);
    $image_end_part = end($image_parts);

    if (in_array($image_end_part, $images_extentions) == true) {
        return 1;
    } else {
        return 0;
    }
}

function starts_deals_with($string, $needle)
{
    $string = $string;
    return $needle === "" || strrpos($string, $needle, -strlen($string)) !== false;
}

function copy_text_based_image($source_path, $target_path)
{
    $buffer_size = 3145728;
    $byte_number = 0;
    $file_open = fopen($source_path, "rb");
    $file_wirte = fopen($target_path, "w");
    while (!feof($file_open)) {
        $byte_number += fwrite($file_wirte, fread($file_open, $buffer_size));
    }
    fclose($file_open);
    fclose($file_wirte);
    return $byte_number;
}

function send_deals_later($params)
{
    $emails = array(
        'sent_to' => $params['recipient'],
        // 'sent_cc' => $params['cc'],
        'sent_from' => config_item('company_email') . ' ' . config_item('company_name'),
        'subject' => $params['subject'],
        'message' => $params['message']
    );
    $CI = &get_instance();
    $CI->db->insert('tbl_outgoing_emails', $emails);
    return TRUE;
}

function deals_details_tabs($id)
{
    // make details tab array and assign order,name,url,count
    $url = 'admin/deals/details/';
    $tabs = array(
        'details' => [
            'position' => 1,
            'name' => 'details',
            'url' => $url . $id,
            'count' => '',
            'view' => 'deals/deals_details/index'
        ],
        'call' => [
            'position' => 2,
            'name' => 'call',
            'url' => $url . $id . '/call',
            'count' => total_rows('tbl_deal_calls', array('module' => "deals", 'module_field_id' => $id)),
            'view' => 'deals/deals_details/call'
        ],
        'comments' => [
            'position' => 3,
            'name' => 'comments',
            'url' => $url . $id . '/comments',
            'count' => total_rows('tbl_deals_comments', array('deal_id' => $id)),
            'view' => 'deals/deals_details/comments'
        ],
        'attachments' => [
            'position' => 4,
            'name' => 'attachments',
            'url' => $url . $id . '/attachments',
            'count' => total_rows(db_prefix() . 'files', array('rel_type' => 'deal', 'rel_id' => $id)),
            'view' => 'deals/deals_details/attachments'
        ],
        'notes' => [
            'position' => 5,
            'name' => 'notes',
            'url' => $url . $id . '/notes',
            'count' => '',
            'view' => 'deals/deals_details/notes'
        ],

        'tasks' => [
            'position' => 6,
            'name' => 'tasks',
            'url' => $url . $id . '/tasks',
            'count' => total_rows(db_prefix() . 'tasks', array('rel_id' => $id, 'rel_type' => 'deals')),
            'view' => 'deals/deals_details/tasks'
        ],
        'mettings' => array(
            'position' => 7,
            'name' => 'mettings',
            'url' => $url . $id . '/mettings',
            'count' => total_rows('tbl_deals_mettings', array('module_field_id' => $id)),
            'view' => 'deals/deals_details/mettings',
        ),
        'email' => array(
            'position' => 8,
            'name' => 'email',
            'url' => $url . $id . '/email',
            'count' => total_rows('tbl_deals_email', array('deals_id' => $id)),
            'view' => 'deals/deals_details/email',
        ),
        'products' => array(
            'position' => 9,
            'name' => 'products',
            'url' => $url . $id . '/products',
            'count' => total_rows('tbl_deals_items', array('deals_id' => $id)),
            'view' => 'deals/deals_details/deals_items_details',
        ),
        'activites' => [
            'position' => 10,
            'name' => 'activites',
            'url' => $url . $id . '/activites',
            'count' => total_rows('tbl_deal_activity_log', array('deal_id' => $id)),
            'view' => 'deals/deals_details/activites'
        ]
    );
    return apply_deals_filters('deals_details_tabs', $tabs);
}

function handle_deals_attachments($deal_id, $index_name = 'file', $form_activity = false): bool
{

    $uploaded_files = [];
    $path = get_upload_path_for_deal() . $deal_id . '/';
    $CI = &get_instance();
    $CI->load->model('deals_model');
    if (
        isset($_FILES[$index_name]['name'])
        && ($_FILES[$index_name]['name'] != ''
            || is_array($_FILES[$index_name]['name']) && count($_FILES[$index_name]['name']) > 0)
    ) {
        if (!is_array($_FILES[$index_name]['name'])) {
            $_FILES[$index_name]['name'] = [$_FILES[$index_name]['name']];
            $_FILES[$index_name]['type'] = [$_FILES[$index_name]['type']];
            $_FILES[$index_name]['tmp_name'] = [$_FILES[$index_name]['tmp_name']];
            $_FILES[$index_name]['error'] = [$_FILES[$index_name]['error']];
            $_FILES[$index_name]['size'] = [$_FILES[$index_name]['size']];
        }

        _file_attachments_index_fix($index_name);

        for ($i = 0; $i < count($_FILES[$index_name]['name']); $i++) {
            // Get the temp file path
            $tmpFilePath = $_FILES[$index_name]['tmp_name'][$i];

            // Make sure we have a filepath
            if (!empty($tmpFilePath) && $tmpFilePath != '') {
                if (
                    _perfex_upload_error($_FILES[$index_name]['error'][$i])
                    || !_upload_extension_allowed($_FILES[$index_name]['name'][$i])
                ) {
                    continue;
                }

                _maybe_create_upload_path($path);
                $filename = unique_filename($path, $_FILES[$index_name]['name'][$i]);

                $newFilePath = $path . $filename;

                if (move_uploaded_file($tmpFilePath, $newFilePath)) {
                    $CI->deals_model->add_attachment_deals_database($deal_id, [[
                        'file_name' => $filename,
                        'filetype' => $_FILES[$index_name]['type'][$i],
                    ]], false, $form_activity);
                }
            }
        }
    }

    return true;
}

function _get_file_extension($file_name)
{
    return substr(strrchr($file_name, '.'), 1);
}

if (!function_exists('validate_post_file')) {

    function validate_post_file($file_name = "")
    {
        if (is_valid_file_to_upload($file_name)) {
            echo json_encode(array("success" => true));
            exit();
        } else {
            echo json_encode(array("success" => false, 'message' => lang('invalid_file_type') . " ($file_name)"));
            exit();
        }
    }
}
if (!function_exists('is_valid_file_to_upload')) {

    function is_valid_file_to_upload($file_name = "")
    {

        if (!$file_name)
            return false;

        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        $file_formates = explode('|', config_item('allowed_files'));
        if (in_array($file_ext, $file_formates)) {
            return true;
        }
    }
}

if (!function_exists('upload_file_to_temp')) {

    function upload_file_to_temp()
    {
        if (!empty($_FILES)) {
            $temp_file = $_FILES['file']['tmp_name'];
            $file_name = $_FILES['file']['name'];

            if (!is_valid_file_to_upload($file_name))
                return false;

            $target_path = getcwd() . '/uploads/temp/';
            if (!is_dir($target_path)) {
                if (!mkdir($target_path, 0777, true)) {
                    die('Failed to create file folders.');
                }
            }
            $target_file = $target_path . $file_name;
            copy($temp_file, $target_file);
        }
    }
}
if (!function_exists('move_temp_file')) {

    function move_temp_file($file_name, $target_path, $related_to = "", $source_path = NULL, $static_file_name = "")
    {
        $new_filename = unique_filename($target_path, $file_name);
        //if not provide any source path we'll fi   nd the default path
        if (!$source_path) {
            $source_path = getcwd() . "/uploads/temp/" . $file_name;
        }

        //check destination directory. if not found try to create a new one
        if (!is_dir($target_path)) {
            if (!mkdir($target_path, 0777, true)) {
                die('Failed to create file folders.');
            }
        }

        //overwrite extisting logic and use static file name
        if ($static_file_name) {
            $new_filename = $static_file_name;
        }

        //check the file type is data or file. then copy to destination and remove temp file
        if (starts_with($source_path, "data")) {
            copy_text_based_image($source_path, $target_path . $new_filename);
            return $new_filename;
        } else {
            if (file_exists($source_path)) {
                copy($source_path, $target_path . $new_filename);
                unlink($source_path);
                return $new_filename;
            }
        }
        return false;
    }
}
if (!function_exists('starts_with')) {

    function starts_with($string, $needle)
    {
        $string = $string;
        return $needle === "" || strrpos($string, $needle, -strlen($string)) !== false;
    }
}
function check_image_extension($image)
{
    $images_extentions = array("jpg", "JPG", "jpeg", "JPEG", "png", "PNG", "gif", "GIF", "bmp", "BMP");
    $image_parts = explode(".", $image);
    $image_end_part = end($image_parts);

    if (in_array($image_end_part, $images_extentions) == true) {
        return 1;
    } else {
        return 0;
    }
}

function _mime_content_type($filename)
{
    if (function_exists('mime_content_type'))
        return mime_content_type($filename);
    else if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME);
        $mimetype = finfo_file($finfo, $filename);
        return $mimetype;
    } else
        return get_mime_by_extension($filename);
}

function apply_deals_filters($hook_name, $value)
{
    return hooks()->apply_filters($hook_name, $value);
}

function btn_edit_deals($uri)
{
    return anchor($uri, 'Edit', array('class' => "btn btn-primary btn-xs", 'title' => 'Edit', 'data-toggle' => 'tooltip', 'data-placement' => 'top'));
}

function btn_update_deals()
{
    return "<button data-toggle='tooltip' title=" . lang('update') . " data-placement='top' type='submit'  class='btn btn-xs btn-success'><i class='fa fa-check'></i></button>";
}

function btn_cancel_deals($uri)
{
    return anchor($uri, '<i class="fa fa-times"></i>', array('class' => "btn btn-danger btn-xs", 'title' => lang('cancel'), 'data-toggle' => 'tooltip', 'data-placement' => 'top'));
}

function btn_add_deals()
{
    return "<button type='submit' name='add' value='1' class='btn btn-info'>" . lang('add') . "</button>";
}


function ajax_deals_anchor($url, $title = '', $attributes = '')
{
    $attributes["data-act"] = "ajax-request";
    $attributes["data-action-url"] = $url;
    return js_deals_anchor($title, $attributes);
}


function js_deals_anchor($title = '', $attributes = '')
{
    $title = (string)$title;

    $html_attributes = "";
    if (is_array($attributes)) {
        foreach ($attributes as $key => $value) {
            $html_attributes .= ' ' . $key . '="' . $value . '"';
        }
    }
    return '<strong data-toggle="tooltip" data-placement="top" style="cursor:pointer"' . $html_attributes . '>' . $title . '</strong>';
}


function force_download($filename = '', $data = '', $set_mime = FALSE)
{
    if ($filename === '' or $data === '') {
        return;
    } elseif ($data === NULL) {
        if (!@is_file($filename) or ($filesize = @filesize($filename)) === FALSE) {
            return;
        }

        $filepath = $filename;
        $filename = explode('/', str_replace(DIRECTORY_SEPARATOR, '/', $filename));
        $filename = end($filename);
    } else {
        $filesize = strlen($data);
    }

    // Set the default MIME type to send
    $mime = 'application/octet-stream';

    $x = explode('.', $filename);
    $extension = end($x);

    if ($set_mime === TRUE) {
        if (count($x) === 1 or $extension === '') {
            /* If we're going to detect the MIME type,
             * we'll need a file extension.
             */
            return;
        }

        // Load the mime types
        $mimes =& get_mimes();

        // Only change the default MIME if we can find one
        if (isset($mimes[$extension])) {
            $mime = is_array($mimes[$extension]) ? $mimes[$extension][0] : $mimes[$extension];
        }
    }

    /* It was reported that browsers on Android 2.1 (and possibly older as well)
     * need to have the filename extension upper-cased in order to be able to
     * download it.
     *
     * Reference: http://digiblog.de/2011/04/19/android-and-the-download-file-headers/
     */
    if (count($x) !== 1 && isset($_SERVER['HTTP_USER_AGENT']) && preg_match('/Android\s(1|2\.[01])/', $_SERVER['HTTP_USER_AGENT'])) {
        $x[count($x) - 1] = strtoupper($extension);
        $filename = implode('.', $x);
    }

    if ($data === NULL && ($fp = @fopen($filepath, 'rb')) === FALSE) {
        return;
    }

    // Clean output buffer
    if (ob_get_level() !== 0 && @ob_end_clean() === FALSE) {
        @ob_clean();
    }

    // Generate the server headers
    header('Content-Type: ' . $mime);
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Expires: 0');
    header('Content-Transfer-Encoding: binary');
    header('Content-Length: ' . $filesize);
    header('Cache-Control: private, no-transform, no-store, must-revalidate');

    // If we have raw data - just dump it
    if ($data !== NULL) {
        exit($data);
    }

    // Flush 1MB chunks of data
    while (!feof($fp) && ($data = fread($fp, 1048576)) !== FALSE) {
        echo $data;
    }

    fclose($fp);
    exit;
}


/**
 * Task attachments upload array
 * Multiple task attachments can be upload if input type is array or dropzone plugin is used
 * @param mixed $deal_id task id
 * @param string $index_name attachments index, in different forms different index name is used
 * @return mixed
 */

function get_upload_path_for_deal()
{
    $dir = FCPATH . 'uploads/deals/';
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    return $dir;
}

function handle_deal_attachments_array($deal_id, $index_name = 'attachments')
{
    $uploaded_files = [];

    $path = get_upload_path_for_deal() . $deal_id . '/';

    if (isset($_FILES[$index_name]['name'])
        && ($_FILES[$index_name]['name'] != '' || is_array($_FILES[$index_name]['name']) && count($_FILES[$index_name]['name']) > 0)) {
        if (!is_array($_FILES[$index_name]['name'])) {
            $_FILES[$index_name]['name'] = [$_FILES[$index_name]['name']];
            $_FILES[$index_name]['type'] = [$_FILES[$index_name]['type']];
            $_FILES[$index_name]['tmp_name'] = [$_FILES[$index_name]['tmp_name']];
            $_FILES[$index_name]['error'] = [$_FILES[$index_name]['error']];
            $_FILES[$index_name]['size'] = [$_FILES[$index_name]['size']];
        }

        _file_attachments_index_fix($index_name);
        for ($i = 0; $i < count($_FILES[$index_name]['name']); $i++) {
            // Get the temp file path
            $tmpFilePath = $_FILES[$index_name]['tmp_name'][$i];

            // Make sure we have a filepath
            if (!empty($tmpFilePath) && $tmpFilePath != '') {
                if (_perfex_upload_error($_FILES[$index_name]['error'][$i])
                    || !_upload_extension_allowed($_FILES[$index_name]['name'][$i])) {
                    continue;
                }

                _maybe_create_upload_path($path);
                $filename = unique_filename($path, $_FILES[$index_name]['name'][$i]);
                $newFilePath = $path . $filename;

                // Upload the file into the temp dir
                if (move_uploaded_file($tmpFilePath, $newFilePath)) {
                    array_push($uploaded_files, [
                        'file_name' => $filename,
                        'filetype' => $_FILES[$index_name]['type'][$i],
                    ]);

                    if (is_image($newFilePath)) {
                        create_img_thumb($path, $filename);
                    }
                }
            }
        }
    }

    if (count($uploaded_files) > 0) {
        return $uploaded_files;
    }

    return false;
}


function btn_view_deals($uri)
{
    return anchor($uri, '<span class="fa fa-list-alt"></span>', array('class' => "btn btn-info btn-xs", 'data-toggle' => 'tooltip', 'data-placement' => 'top', 'title' => 'View'));
}


function btn_delete_deals($uri, $text = null, $icon = null)
{
    $icons = '<i class="fa fa-trash-o"></i>';
    $title = _l('delete');
    $btn = 'btn';
    if (!empty($text) && empty($icon)) {
        $icons = '';
        $title = $text;
        $btn = 'text';
    }
    if (!empty($icon) && empty($text)) {
        $title = '';
    }
    return anchor($uri, $icons . ' ' . $title, array(
        'class' => "btn $btn-danger btn-xs deleteBtn", 'title' => $text, 'data-toggle' => 'tooltip', 'data-placement' => 'top', 'onclick' => "return confirm('" . _l('delete_alert') . "');"
    ));
}


function tab_load_view_deals($all_tab, $active)
{
    $tab = array_filter($all_tab, function ($key) use ($active) {
        return $key == $active;
    }, ARRAY_FILTER_USE_KEY);
    if (count(array($tab)) > 0) {
        return $tab[$active]['view'];
    } else {
        return false;
    }
}


/**
 * Initializes the vendor customfield.
 *
 * @param string $custom_field The custom field
 */
function init_deals_custom_fields($custom_field = '')
{
    $select = '';
    if ($custom_field != '') {
        if ($custom_field->fieldto == 'deals') {
            $select = 'selected';
        }
    }

    $html = '<option value="deals" ' . $select . '>' . _l('deals') . '</option>';

    echo html_entity_decode($html);
}


/**
 * PO add table row
 * @param string $row
 * @param string $aRow
 * @return [type]
 */
function deal_add_table_row($row, $aRow)
{
    $CI = &get_instance();
    if ($aRow['rel_type'] == 'deals') {
        $deal = get_deals_row('tbl_deals', array('id' => $aRow['rel_id']));
        if ($deal) {
            $str = '<span class="hide"> - </span><a class="text-muted task-table-related" data-toggle="tooltip" title="' . _l('task_related_to') . '" href="' . admin_url('deals/details/' . $deal->id) . '">' . $deal->title . '</a><br />';
            $row[2] = $row[2] . $str;
        }
    }
    return $row;
}


function deal_get_relation_data($data, $obj)
{
    $type = $obj['type'];
    $rel_id = $obj['rel_id'];
    if ($type == 'deals') {
        if ($rel_id != '') {
            $data = get_deals_row('tbl_deals', array('id' => $rel_id));
        } else {
            $data = [];
        }
    }
    return $data;
}

/**
 * PO relation data
 * @param array $data
 * @param string $type
 * @param id $rel_id
 * @param array $q
 * @return array
 */
function deal_relation_data($data, $type, $rel_id, $q)
{

    if ($type == 'deals') {
        if ($rel_id != '') {
            $data = get_deals_row('tbl_deals', array('id' => $rel_id));
        } else {
            $data = [];
        }
    }
    return $data;
}

/**
 * task related to select
 * @param string $value
 * @return string
 */
function deal_related_to_select($value)
{

    $selected = '';
    if ($value == 'deals') {
        $selected = 'selected';
    }
    echo "<option value='deals' $selected>" . _l('deals') . '</option>';

}

function deals_global_search_result_output($output, $data)
{
    if ($data['type'] == 'deals') {
        $output = '<a href="' . admin_url('deals/details/' . $data['result']['id']) . '">' . $data['result']['title'] . '</a>';
    }

    return $output;
}

function deals_global_search_result_query($result, $q, $limit)
{
    $CI = &get_instance();
    if (has_permission('deals', '', 'view')) {
        // Goals
        $CI->db->select()->from('tbl_deals')->like('title', $q)->or_like('notes', $q)->limit($limit);
        $CI->db->order_by('title', 'ASC');

        $result[] = [
            'result' => $CI->db->get()->result_array(),
            'type' => 'deals',
            'search_heading' => _l('deals'),
        ];
    }

    return $result;
}