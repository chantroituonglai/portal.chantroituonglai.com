<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: External Mapping Management
Description: Manage external products, orders and their mapping with internal systems
Version: 2.0.0
Author: Future Horizon Ltd Company
Author URI: https://www.chantroituonglai.com
Requires at least: 2.3.*
*/

//Module name
define('EXTERNAL_PRODUCTS_MODULE_NAME', 'external_products');

// Define upload folder location
define('EXTERNAL_PRODUCTS_MODULE_UPLOAD_FOLDER', module_dir_path(EXTERNAL_PRODUCTS_MODULE_NAME, 'uploads/'));

// Get codeigniter instance
$CI = &get_instance();

// Register activation module hook
register_activation_hook(EXTERNAL_PRODUCTS_MODULE_NAME, 'external_products_module_activation_hook');
function external_products_module_activation_hook()
{
    $CI = &get_instance();
    require_once __DIR__.'/install.php';
}


// Register language files, must be registered if the module is using languages
register_language_files(EXTERNAL_PRODUCTS_MODULE_NAME, [EXTERNAL_PRODUCTS_MODULE_NAME]);

// Load module helper file
$CI->load->helper(EXTERNAL_PRODUCTS_MODULE_NAME.'/external_products');

// Load module Library file
$CI->load->library(EXTERNAL_PRODUCTS_MODULE_NAME.'/'.'external_products_lib');

// Inject css file for external products module
hooks()->add_action('app_admin_head', 'external_products_add_head_components');
function external_products_add_head_components()
{
    // Check module is enable or not (refer install.php)
    if ('1' == get_option('external_products_enabled')) {
        $CI = &get_instance();
        echo '<link href="'.module_dir_url('external_products', 'assets/css/external_products.css').'?v='.$CI->app_scripts->core_version().'"  rel="stylesheet" type="text/css" />';
        echo '<script src="'.module_dir_url('external_products', 'assets/js/external_products.js').'?v='.time().'"></script>';
    }
}

// Inject Javascript file for external products module
hooks()->add_action('app_admin_footer', 'external_products_load_js');
function external_products_load_js()
{
    if ('1' == get_option('external_products_enabled')) {
        $CI = &get_instance();
        echo '<script src="'.module_dir_url('external_products', 'assets/js/external_products.js').'?v='.time().'"></script>';
    }
}

//inject permissions Feature and Capabilities for external products module
hooks()->add_filter('staff_permissions', 'external_products_module_permissions_for_staff');
function external_products_module_permissions_for_staff($permissions)
{
    $viewGlobalName      = _l('permission_view').'('._l('permission_global').')';
    $allPermissionsArray = [
        'view'     => $viewGlobalName,
        'create'   => _l('permission_create'),
        'edit'     => _l('permission_edit'),
        'delete'   => _l('permission_delete'),
    ];
    $permissions['external_products'] = [
                'name'         => _l('external_products'),
                'capabilities' => $allPermissionsArray,
            ];

    return $permissions;
}

// Inject sidebar menu and links for external products module
hooks()->add_action('admin_init', 'external_products_module_init_menu_items');
function external_products_module_init_menu_items()
{
    $CI = &get_instance();
    if (has_permission('external_products', '', 'view')) {
        $CI->app_menu->add_sidebar_menu_item('external_products', [
            'slug'     => 'External Mapping Management',
            'name'     => _l('external_mapping_management'),
            'icon'     => 'fa fa-exchange',
            'href'     => '#',
            'position' => 35,
        ]);
    }

    if (has_permission('external_products', '', 'view')) {
        $CI->app_menu->add_sidebar_children_item('external_products', [
            'slug'     => 'External Products',
            'name'     => _l('external_products'),
            'href'     => admin_url('external_products'),
            'position' => 1,
        ]);
    }

    if (has_permission('external_products', '', 'view')) {
        $CI->app_menu->add_sidebar_children_item('external_products', [
            'slug'     => 'external_products_mapping',
            'name'     => _l('external_products_mapping'),
            'href'     => admin_url('external_products/mapping'),
            'position' => 2,
        ]);
    }

    if (has_permission('external_products', '', 'view')) {
        $CI->app_menu->add_sidebar_children_item('external_products', [
            'slug'     => 'external_products_statistics',
            'name'     => _l('mapping_statistics'),
            'href'     => admin_url('external_products/statistics'),
            'position' => 3,
        ]);
    }

    if (has_permission('external_products', '', 'view')) {
        $CI->app_menu->add_sidebar_children_item('external_products', [
            'slug'     => 'external_products_duplicates',
            'name'     => _l('duplicate_mappings'),
            'href'     => admin_url('external_products/duplicates'),
            'position' => 4,
        ]);
    }

    // Order Management
    if (has_permission('external_products', '', 'view')) {
        $CI->app_menu->add_sidebar_children_item('external_products', [
            'slug'     => 'external_orders',
            'name'     => _l('external_orders'),
            'href'     => admin_url('external_products/orders'),
            'position' => 5,
        ]);
    }

    if (has_permission('external_products', '', 'view')) {
        $CI->app_menu->add_sidebar_children_item('external_products', [
            'slug'     => 'order_mapping',
            'name'     => _l('order_mapping'),
            'href'     => admin_url('external_products/order_mapping'),
            'position' => 6,
        ]);
    }

    // Haravan Integration
    if (has_permission('external_products', '', 'view')) {
        $CI->app_menu->add_sidebar_children_item('external_products', [
            'slug'     => 'haravan_products',
            'name'     => _l('haravan_products'),
            'href'     => admin_url('external_products/haravan_products'),
            'position' => 10,
        ]);
    }
}

// Add settings menu(tab menu) In Admin Side
$CI->app_tabs->add_settings_tab('external_products', [
    'name'     => 'External Mapping Management',
    'view'     => 'external_products/admin/settings',
    'position' => 65,
]);

// Inject upload folder location for external products module
hooks()->add_filter('get_upload_path_by_type', 'external_products_upload_folder', 10, 2);
function external_products_upload_folder($path, $type)
{
    if ('external_products' == $type) {
        return EXTERNAL_PRODUCTS_MODULE_UPLOAD_FOLDER;
    }

    return $path;
}
