<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: Mautic Bridge
Description: Bidirectional Mautic contact/campaign sync for Perfex CRM leads and contacts.
Version: 0.1.0
Requires at least: 3.0.*
*/

define('MAUTIC_BRIDGE_MODULE_NAME', 'mautic_bridge');
define('MAUTIC_BRIDGE_DB_VERSION', 100);

require_once __DIR__ . '/helpers/mautic_bridge_helper.php';

$CI = &get_instance();
$CI->load->helper(MAUTIC_BRIDGE_MODULE_NAME . '/mautic_bridge_cron');

register_activation_hook(MAUTIC_BRIDGE_MODULE_NAME, 'mautic_bridge_activation_hook');
register_deactivation_hook(MAUTIC_BRIDGE_MODULE_NAME, 'mautic_bridge_deactivation_hook');
register_language_files(MAUTIC_BRIDGE_MODULE_NAME, [MAUTIC_BRIDGE_MODULE_NAME]);

hooks()->add_action('admin_init', 'mautic_bridge_init_menu');
hooks()->add_action('lead_created', 'mautic_bridge_lead_changed');
hooks()->add_action('after_lead_updated', 'mautic_bridge_lead_changed');
hooks()->add_action('after_lead_deleted', 'mautic_bridge_lead_deleted');
hooks()->add_action('contact_created', 'mautic_bridge_contact_changed');
hooks()->add_action('contact_updated', 'mautic_bridge_contact_changed');
hooks()->add_action('contact_deleted', 'mautic_bridge_contact_deleted', 10, 2);
hooks()->add_action('lead_converted_to_customer', 'mautic_bridge_lead_converted');
hooks()->add_filter('project_tabs', 'mautic_bridge_project_tabs');

function mautic_bridge_activation_hook(): void
{
    require_once __DIR__ . '/install.php';
}

function mautic_bridge_deactivation_hook(): void
{
    update_option('mautic_bridge_enabled', 0);
}

function mautic_bridge_init_menu(): void
{
    if (!is_admin()) {
        return;
    }

    $CI = &get_instance();
    $CI->app_menu->add_setup_menu_item('mautic_bridge', [
        'collapse' => true,
        'name'     => _l('mautic_bridge'),
        'position' => 77,
    ]);
    $CI->app_menu->add_setup_children_item('mautic_bridge', [
        'slug'     => 'mautic-bridge-sync-manager',
        'name'     => _l('mautic_bridge_sync_manager'),
        'href'     => admin_url('mautic_bridge_manage'),
        'position' => 5,
    ]);
    $CI->app_menu->add_setup_children_item('mautic_bridge', [
        'slug'     => 'mautic-bridge-project-hq',
        'name'     => _l('mautic_bridge_project_hq'),
        'href'     => admin_url('mautic_bridge_manage/projects'),
        'position' => 6,
    ]);
    $CI->app_menu->add_setup_children_item('mautic_bridge', [
        'slug'     => 'mautic-bridge-google-import',
        'name'     => _l('mautic_bridge_google_import'),
        'href'     => admin_url('mautic_bridge_manage/google_import'),
        'position' => 7,
    ]);
    $CI->app_menu->add_setup_children_item('mautic_bridge', [
        'slug'     => 'mautic-bridge-settings',
        'name'     => _l('mautic_bridge_settings'),
        'href'     => admin_url('mautic_bridge'),
        'position' => 10,
    ]);
}

function mautic_bridge_model()
{
    $CI = &get_instance();
    $CI->load->model('mautic_bridge/Mautic_bridge_model', 'mauticBridgeModel');

    return $CI->mauticBridgeModel;
}

function mautic_bridge_should_enqueue(): bool
{
    return get_option('mautic_bridge_enabled') === '1' && mautic_bridge_get_origin() !== MAUTIC_BRIDGE_MODULE_NAME;
}

function mautic_bridge_lead_changed($leadId): void
{
    if (!mautic_bridge_should_enqueue()) {
        return;
    }

    mautic_bridge_model()->enqueue_perfex_change('lead', (int) $leadId, 'upsert');
}

function mautic_bridge_lead_deleted($leadId): void
{
    if (!mautic_bridge_should_enqueue()) {
        return;
    }

    mautic_bridge_model()->enqueue_perfex_change('lead', (int) $leadId, 'delete');
}

function mautic_bridge_contact_changed($contactId): void
{
    if (!mautic_bridge_should_enqueue()) {
        return;
    }

    mautic_bridge_model()->enqueue_perfex_change('contact', (int) $contactId, 'upsert');
}

function mautic_bridge_contact_deleted($contactId, $result = null): void
{
    if (!$result || !mautic_bridge_should_enqueue()) {
        return;
    }

    mautic_bridge_model()->enqueue_perfex_change('contact', (int) $contactId, 'delete');
}

function mautic_bridge_lead_converted($data): void
{
    if (!is_array($data)) {
        return;
    }

    mautic_bridge_model()->mark_lead_converted((int) ($data['lead_id'] ?? 0), (int) ($data['customer_id'] ?? 0));
}

function mautic_bridge_project_tabs($tabs)
{
    if (!is_array($tabs)) {
        $tabs = [];
    }

    if (!is_admin()) {
        return $tabs;
    }

    $tabs['mautic_hq'] = [
        'slug'     => 'mautic_hq',
        'name'     => _l('mautic_bridge_project_hq'),
        'icon'     => 'fa fa-bullhorn',
        'view'     => 'mautic_bridge/project_tabs/mautic_hq',
        'position' => 16,
        'visible'  => has_permission('projects', '', 'view') || has_permission('projects', '', 'view_own'),
    ];

    return $tabs;
}
