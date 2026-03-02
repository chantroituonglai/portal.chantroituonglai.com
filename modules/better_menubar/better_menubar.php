<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: Better Menubar
Description: Improve sidebar (#side-menu) & setup menu scrolling and layout without editing core files.
Version: 1.0.0
Requires at least: 2.3.*
*/

define('BETTER_MENUBAR_MODULE', 'better_menubar');

$CI = &get_instance();

// Register activation hook to create options
register_activation_hook(BETTER_MENUBAR_MODULE, 'better_menubar_activate');
function better_menubar_activate()
{
    add_option('better_menubar_enabled', '1');
    add_option('better_menubar_sidebar_mode', 'fixed');
    add_option('better_menubar_header_offset', '1');
    add_option('better_menubar_pinned_enabled', '1');
    add_option('better_menubar_header_fixed', '0');
}

// Inject CSS/JS in admin
hooks()->add_action('app_admin_head', 'better_menubar_admin_css');
hooks()->add_action('app_admin_footer', 'better_menubar_admin_js');

// Add simple settings entry under Setup
hooks()->add_action('admin_init', 'better_menubar_add_settings');
hooks()->add_filter('module_better_menubar_action_links', 'better_menubar_action_links');

function better_menubar_action_links($actions)
{
    $actions[] = '<a href="' . admin_url('better_menubar') . '">' . _l('settings') . '</a>';
    return $actions;
}

function better_menubar_add_settings()
{
    if (!is_admin()) {
        return;
    }
    $CI = &get_instance();
    // Add a settings parent
    $CI->app_menu->add_setup_menu_item('better-menubar', [
        'name'     => 'Better Menubar',
        'collapse' => true,
        'position' => 59, // before Menu Builder (60)
    ]);

    $CI->app_menu->add_setup_children_item('better-menubar', [
        'slug'     => 'better-menubar-settings',
        'name'     => _l('settings'),
        'href'     => admin_url('better_menubar'),
        'position' => 1,
    ]);
}

function better_menubar_admin_css()
{
    if (get_option('better_menubar_enabled') !== '1') {
        return;
    }

    echo '<link rel="stylesheet" type="text/css" href="' . module_dir_url(BETTER_MENUBAR_MODULE, 'assets/css/better_menubar.css') . '?v=' . uniqid() . '">';
}

function better_menubar_admin_js()
{
    if (get_option('better_menubar_enabled') !== '1') {
        return;
    }

    $config = [
        'mode'         => get_option('better_menubar_sidebar_mode') ?: 'fixed',
        'headerOffset' => get_option('better_menubar_header_offset') === '1',
        'pinnedPanel'  => get_option('better_menubar_pinned_enabled') === '1',
        'fixHeader'    => get_option('better_menubar_header_fixed') === '1',
    ];
    echo '<script>window.BM_CONFIG = ' . json_encode($config) . ';</script>';
    echo '<script src="' . module_dir_url(BETTER_MENUBAR_MODULE, 'assets/js/better_menubar.js') . '?v=' . uniqid() . '"></script>';
}
