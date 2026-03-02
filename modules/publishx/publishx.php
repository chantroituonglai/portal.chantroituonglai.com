<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: PublishX
Description: A powerful blogging module for Perfex CRM. Create, manage, and optimize captivating blog posts. Boost engagement and strengthen your online presence effortlessly.
Version: 1.0.0
Author: LenzCreative
Author URI: https://codecanyon.net/user/lenzcreativee/portfolio
Requires at least: 1.0.*
*/

define('PUBLISHX_MODULE_NAME', 'publishx');

hooks()->add_action('admin_init', 'publishx_module_init_menu_items');
hooks()->add_action('admin_init', 'publishx_permissions');
hooks()->add_action('clients_init', 'publishx_module_clients_init_menu_items');
hooks()->add_action('before_cron_run', 'publishx_scheduled_posts');
/**
 * Load the module helper
 */
$CI = &get_instance();
$CI->load->helper(PUBLISHX_MODULE_NAME . '/publishx'); //on module main file

require_once(__DIR__ . '/libraries/PublishxOpenAI.php');

function publishx_permissions()
{
    $capabilities = [];

    $capabilities['capabilities'] = [
        'view' => _l('permission_view') . '(' . _l('permission_global') . ')',
        'create' => _l('permission_create'),
        'edit' => _l('permission_edit'),
        'delete' => _l('permission_delete'),
    ];
    register_staff_capabilities('publishx_posts', $capabilities, _l('publishx_posts'));

    $capabilities = [];
    $capabilities['capabilities'] = [
        'view' => _l('permission_view') . '(' . _l('permission_global') . ')',
        'create' => _l('permission_create'),
        'edit' => _l('permission_edit'),
        'delete' => _l('permission_delete'),
    ];
    register_staff_capabilities('publishx_categories', $capabilities, _l('publishx_categories'));

    $capabilities = [];
    $capabilities['capabilities'] = [
        'view' => _l('permission_view') . '(' . _l('permission_global') . ')',
        'create' => _l('permission_create'),
        'edit' => _l('permission_edit'),
        'delete' => _l('permission_delete'),
    ];
    register_staff_capabilities('publishx_languages', $capabilities, _l('publishx_languages'));

    $capabilities = [];
    $capabilities['capabilities'] = [
        'view' => _l('permission_view') . '(' . _l('permission_global') . ')',
        'create' => _l('permission_create'),
        'edit' => _l('permission_edit'),
        'delete' => _l('permission_delete'),
    ];
    register_staff_capabilities('publishx_themes', $capabilities, _l('publishx_themes'));

}

/**
 * Register activation module hook
 */
register_activation_hook(PUBLISHX_MODULE_NAME, 'publishx_module_activation_hook');

function publishx_module_activation_hook()
{
    $CI = &get_instance();
    require_once(__DIR__ . '/install.php');
}

/**
 * Register language files, must be registered if the module is using languages
 */
register_language_files(PUBLISHX_MODULE_NAME, [PUBLISHX_MODULE_NAME]);

/**
 * Init module menu items in setup in admin_init hook
 * @return null
 */
function publishx_module_init_menu_items()
{
    $CI = &get_instance();

    if (has_permission('publishx', '', 'view')) {
        $CI->app_menu->add_sidebar_menu_item('publishx', [
            'slug' => 'publishx',
            'name' => _l('publishx'),
            'position' => 6,
            'icon' => 'fa fa-pencil-alt'
        ]);
    }

    if (has_permission('publishx_posts', '', 'view')) {
        $CI->app_menu->add_sidebar_children_item('publishx', [
            'slug' => 'publishx-posts',
            'name' => _l('publishx_posts'),
            'position' => 6,
            'icon' => 'fas fa-file-text',
            'href' => admin_url('publishx/posts')
        ]);
    }

    if (has_permission('publishx_categories', '', 'view')) {
        $CI->app_menu->add_sidebar_children_item('publishx', [
            'slug' => 'publishx-categories',
            'name' => _l('publishx_categories'),
            'position' => 6,
            'icon' => 'fas fa-tags',
            'href' => admin_url('publishx/categories')
        ]);
    }

    if (has_permission('publishx_languages', '', 'view')) {
        $CI->app_menu->add_sidebar_children_item('publishx', [
            'slug' => 'publishx-languages',
            'name' => _l('publishx_languages'),
            'position' => 6,
            'icon' => 'fas fa-globe',
            'href' => admin_url('publishx/languages')
        ]);
    }

    if (has_permission('publishx_themes', '', 'view')) {
        $CI->app_menu->add_sidebar_children_item('publishx', [
            'slug' => 'publishx-themes',
            'name' => _l('publishx_themes'),
            'position' => 6,
            'icon' => 'fas fa-paint-brush',
            'href' => admin_url('publishx/themes')
        ]);
    }

    if (is_admin()) {

        $CI->app_menu->add_sidebar_children_item('publishx', [
            'slug' => 'publishx-settings',
            'name' => _l('settings'),
            'position' => 6,
            'icon' => 'fa-solid fa-cog',
            'href' => admin_url('publishx/settings')
        ]);

    }

}

function publishx_module_clients_init_menu_items()
{

    $CI = &get_instance();

    $accessMenu = false;

    if (get_option('publishx_show_on_client_side') == '1') {
        $accessMenu = true;
    }

    if ($accessMenu) {
        $CI->app_menu->add_theme_item('publishx', [
            'name' => _l('publishx_blog'),
            'href' => site_url('publishx/publishx_client/blog'),
            'position' => 10,
        ]);
    }

}

function publishx_scheduled_posts()
{
    $CI = &get_instance();
    $CI->load->model('publishx/publishx_model');

    $scheduledPosts = $CI->publishx_model->getPosts('2');

    foreach ($scheduledPosts as $post) {

        $dateToBePosted = $post['created_at'];

        if ($dateToBePosted == date('Y-m-d H:i:s') || $dateToBePosted < date('Y-m-d H:i:s')) {
            $CI->publishx_model->updatePost($post['id'], ['status' => '0']);
        }

    }
}