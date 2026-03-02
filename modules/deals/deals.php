<?php defined('BASEPATH') or exit('No direct script access allowed');
/*
Module Name: Deals
Description: Deals module for Perfex CRM will allow you to manage your deals and proposals.
Version: 1.0.3
Requires at least: 2.3.*
*/

define('DEALS_MODULE', 'deals');

$CI = &get_instance();
register_language_files(DEALS_MODULE, [DEALS_MODULE]);
$CI->load->helper(DEALS_MODULE . '/deals');
hooks()->add_action('admin_init', 'deal_init_menu_items');
register_activation_hook(DEALS_MODULE, 'deal_activation_hook');
register_deactivation_hook(DEALS_MODULE, 'deal_deactivation_hook');
register_uninstall_hook(DEALS_MODULE, 'deal_uninstall_hook');
hooks()->add_action('admin_init', 'deal_permissions');

hooks()->add_action('task_modal_rel_type_select', 'deal_task_modal_rel_type_select');
hooks()->add_action('task_related_to_select', 'deal_related_to_select');
hooks()->add_filter('init_relation_options', 'deal_init_relation_options');
hooks()->add_filter('relation_values', 'deal_relation_values');
hooks()->add_filter('before_return_relation_data', 'deal_relation_data', 10, 4); // old
hooks()->add_filter('get_relation_data', 'deal_get_relation_data', 10, 4); // new
hooks()->add_filter('tasks_table_row_data', 'deal_add_table_row', 10, 3);

hooks()->add_filter('global_search_result_output', 'deals_global_search_result_output', 10, 2);
hooks()->add_filter('global_search_result_query', 'deals_global_search_result_query', 10, 3);

hooks()->add_action('after_custom_fields_select_options', 'init_deals_custom_fields');


register_merge_fields('deals/merge_fields/deal_merge_fields');


function deal_relation_values($data)
{

    $CI = &get_instance();
    $task_id = $CI->uri->segment(4);
    $rel_type = '';
    $rel_id = '';
    if ($CI->input->get('rel_id') && $CI->input->get('rel_type')) {
        $rel_id = $CI->input->get('rel_id');
        $rel_type = $CI->input->get('rel_type');
    }

    // get id from uri segment
    if ($data['type'] == 'deals') {
        if ($task_id != '') {
            $task = $CI->tasks_model->get($task_id);
            $rel_id = $task->rel_id;
            $rel_type = $task->rel_type;
        }
        if ($rel_type == 'deals') {
            $CI->db->from('tbl_deals');
            $CI->db->where('id', $rel_id);

            $deal = $CI->db->get()->row();
            $data = [
                'id' => $deal->id,
                'name' => $deal->title,
                'link' => admin_url('deals/deal/' . $deal->id),
                'addedfrom' => get_staff_user_id(),
                'subtext' => '',
                'type' => 'deals',
            ];
        }
    }


    return $data;
}

function deal_init_relation_options($data)
{
    $CI = &get_instance();
    $type = $CI->input->post('type');
    $rel_id = $CI->input->post('rel_id');
    $q = $CI->input->post('q');
    if ($type == 'deals') {
        $CI->db->select('id, title');
        $CI->db->from('tbl_deals');
        $CI->db->where('title LIKE "%' . $q . '%"');
        if ($rel_id != '') {
            $CI->db->where('id != ' . $rel_id);
        }
        $deals = $CI->db->get()->result_array();
        $data = [];
        foreach ($deals as $deal) {
            $data[] = [
                'id' => $deal['id'],
                'name' => $deal['title'],
                'link' => admin_url('deals/deal/' . $deal['id']),
                'addedfrom' => 0,
                'subtext' => '',
                'type' => 'deals',
            ];
        }
    }
    return $data;

}


function deal_task_modal_rel_type_select($task)
{
    $type = $task['rel_type'];
    echo ' <option value="deals" 
    ' . ($type == 'deals' ? 'selected' : '') . '
 >
    ' . _l('deals') . '
                    </option>';
}

function deal_deactivation_hook()
{
    require_once(__DIR__ . '/deactive.php');
}

function deal_uninstall_hook()
{
    require_once(__DIR__ . '/uninstall.php');
}

function deal_activation_hook()
{
    require_once(__DIR__ . '/install.php');
}

function deal_init_menu_items()
{
    /**
     * If the logged in user is administrator, add custom menu in Setup
     */
    $CI = &get_instance();
    $CI->app_menu->add_sidebar_menu_item('deals', [
        'name' => '<span class="text-white">' . _l('deals') . '</span>',
        'position' => 4,
        'icon' => 'fa-solid fa-receipt menu-icon',
        'href' => admin_url('deals'),
    ]);
    $CI->app_menu->add_setup_menu_item('deals', [
        'collapse' => true,
        'name' => _l('deals'),
        'position' => 21,
        'badge' => [],
    ]);
    $CI->app_menu->add_setup_children_item('deals', [
        'slug' => 'deals-sources',
        'name' => _l('acs_leads_sources_submenu'),
        'href' => admin_url('deals/sources'),
        'position' => 5,
        'badge' => [],
    ]);
    $CI->app_menu->add_setup_children_item('deals', [
        'slug' => 'deals-pipelines',
        'name' => _l('deals_pipelines'),
        'href' => admin_url('deals/pipelines'),
        'position' => 10,
        'badge' => [],
    ]);
    $CI->app_menu->add_setup_children_item('deals', [
        'slug' => 'deals-stages',
        'name' => _l('deals_stages'),
        'href' => admin_url('deals/stages'),
        'position' => 10,
        'badge' => [],
    ]);
    $CI->app_menu->add_setup_children_item('deals', [
        'slug' => 'deals-settings',
        'name' => _l('deals_settings'),
        'href' => admin_url('deals/settings'),
        'position' => 10,
        'badge' => [],
    ]);
}

function deal_permissions()
{
    $capabilities = [];

    $capabilities['capabilities'] = [
        'view' => _l('permission_view') . '(' . _l('permission_global') . ')',
        'create' => _l('permission_create'),
        'edit' => _l('permission_edit'),
        'delete' => _l('permission_delete'),
    ];

    register_staff_capabilities('deals', $capabilities, _l('deals'));
}






// ALTER TABLE `tbltask_comments` ADD `dealsid` INT(11) NOT NULL DEFAULT '0' AFTER `taskid`;


