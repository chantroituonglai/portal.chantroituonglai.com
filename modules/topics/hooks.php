<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Khởi tạo menu admin
 */
function topics_admin_init_menu() {
    if (!is_admin()) {
        return;
    }
    
    $CI = &get_instance();

    // Ensure module language files are loaded before using _l()
    $language = $CI->session->userdata('language');
    if (!$language) {
        $language = 'english';
    }
    // Try to load current language, then fallback to english
    $CI->lang->load('topics/topics', $language);
    $CI->lang->load('topics/controllers', $language);
    $CI->lang->load('topics/draft_writer', $language);
    $CI->lang->load('topics/ultimate_editor', $language);
    if ($language !== 'english') {
        $CI->lang->load('topics/topics', 'english');
        $CI->lang->load('topics/controllers', 'english');
        $CI->lang->load('topics/draft_writer', 'english');
        $CI->lang->load('topics/ultimate_editor', 'english');
    }
    
    if (!has_permission('topics', '', 'view')) {
        return;
    }

    // Add main menu item
    $CI->app_menu->add_sidebar_menu_item('topics-menu', [
        'name'     => _l('topics'),
        'position' => 30,
        'icon'     => 'fa fa-comments',
        'slug'     => 'topics'
    ]);

    if (has_permission('topics', '', 'view')) {
        // Topic List
        $CI->app_menu->add_sidebar_children_item('topics-menu', [
            'icon'     => 'fa fa-list',
            'slug'     => 'topic-list',
            'name'     => _l('topic_list'),
            'href'     => admin_url('topics'),
            'position' => 5,
        ]);

        // Topics History (Dashboard)
        $CI->app_menu->add_sidebar_children_item('topics-menu', [
            'icon'     => 'fa fa-history',
            'slug'     => 'topics-dashboard',
            'name'     => _l('topics_dashboard'),
            'href'     => admin_url('topics/dashboard'),
            'position' => 6
        ]);

        // Overview
        $CI->app_menu->add_sidebar_children_item('topics-menu', [
            'icon'     => 'fa fa-info-circle',
            'slug'     => 'topics-overview',
            'name'     => _l('topics_overview'),
            'href'     => admin_url('topics/overview'),
            'position' => 4,
        ]);

        // Controllers
        $CI->app_menu->add_sidebar_children_item('topics-menu', [
            'icon'     => 'fa fa-cogs',
            'slug'     => 'topics-controllers',
            'name'     => _l('controllers'),
            'href'     => admin_url('topics/controllers'),
            'position' => 7,
        ]);

        // Action Types
        $CI->app_menu->add_sidebar_children_item('topics-menu', [
            'icon'     => 'fa fa-1',
            'slug'     => 'action-types',
            'name'     => _l('action_types'),
            'href'     => admin_url('topics/action_types'),
            'position' => 10,
        ]);

        // Action States
        $CI->app_menu->add_sidebar_children_item('topics-menu', [
            'icon'     => 'fa fa-2',
            'slug'     => 'action-states',
            'name'     => _l('action_states'),
            'href'     => admin_url('topics/action_states'),
            'position' => 15,
        ]);

        // Action Buttons
        $CI->app_menu->add_sidebar_children_item('topics-menu', [
            'icon'     => 'fa fa-play-circle',
            'slug'     => 'action-buttons',
            'name'     => _l('topic_action_buttons'),
            'href'     => admin_url('topics/action_buttons'),
            'position' => 20,
        ]);
    }
}

/**
 * Register topics settings tab
 * @return void
 */
function topics_settings_tab()
{
    $CI = &get_instance();
    
    // admin/settings/includes/tags
    $CI->app_tabs->add_settings_tab('topics', [
        'name'     => _l('topics_settings'),
        'view'     => 'topics/settings/topics',
        'position' => 0,
    ]);
}

/**
 * Get N8N workflow URL
 * @param string $workflow_id
 * @return string|null
 */
function get_n8n_workflow_url($workflow_id) {
    // Lấy base URL từ settings
    $n8n_host = get_option('topics_n8n_host');
    
    if (!empty($n8n_host)) {
        // Trim trailing slash from host
        $n8n_host = rtrim($n8n_host, '/');
        return $n8n_host . '/workflow/' . $workflow_id;
    }
    
    // Fallback to old webhook_url if n8n_host is not set
    $webhook_url = get_option('topics_n8n_webhook_url');
    if (!empty($webhook_url)) {
        return str_replace('/webhook/', '/workflow/', $webhook_url);
    }
    
    return null;
}

/**
 * Get N8N execution URL
 * @param string $execution_id
 * @param string $workflow_id
 * @return string|null
 */
function get_n8n_execution_url($execution_id, $workflow_id = null) {
    // Try to get URL from n8n_host first
    $n8n_host = get_option('topics_n8n_host');
    
    if (!empty($n8n_host)) {
        // Trim trailing slash from host
        $n8n_host = rtrim($n8n_host, '/');
        
        // If workflow_id is provided, use the new URL format
        if ($workflow_id) {
            return $n8n_host . '/workflow/' . $workflow_id . '/executions/' . $execution_id;
        }
        
        // Otherwise use the simple execution URL
        return $n8n_host . '/execution/' . $execution_id;
    }
    
    // Fallback to old webhook_url if n8n_host is not set
    $webhook_url = get_option('topics_n8n_webhook_url');
    if (!empty($webhook_url) && !empty($execution_id)) {
        return str_replace('/webhook/', '/execution/', $webhook_url) . '/' . $execution_id;
    }
    
    return null;
}

// Remove any existing hooks first
hooks()->remove_action('admin_init', 'topics_admin_init_menu');

// Register the hook once
hooks()->add_action('admin_init', 'topics_admin_init_menu', 5);

// Đăng ký hook cho settings tab
hooks()->add_action('admin_init', 'topics_settings_tab');

// Đăng ký helper functions
hooks()->add_filter('get_n8n_workflow_url', 'get_n8n_workflow_url');


/**
 * File to register topic_controller custom field type
 * 
 * To use this:
 * 1. Upload this file to your Perfex CRM root directory
 * 2. Add the following line to your application/config/hooks.php file:
 *    require_once(FCPATH . 'register_custom_field_type.php');
 * 3. Refresh your browser
 */

// Add option to select field for topic_controller
hooks()->add_action('after_custom_fields_select_options', function($custom_field) {
    echo '<option value="topic_controller" ' . (isset($custom_field) && $custom_field->fieldto == 'topic_controller' ? 'selected' : '') . '>Topic Controller</option>';
});

// Register the custom field type with Perfex CRM
hooks()->add_filter('custom_fields_available_types', function($types) {
    $types[] = 'topic_controller';
    return $types;
});

// Make sure our custom fields are loaded
hooks()->add_filter('before_get_custom_fields', function($where) {
    return $where;
}); 