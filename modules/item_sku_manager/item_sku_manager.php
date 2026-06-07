<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: Item SKU Manager
Description: Stable SKU metadata and immutable sales-line snapshots for Perfex sales records.
Version: 0.1.0
Requires at least: 3.0.*
*/

define('ITEM_SKU_MANAGER_MODULE_NAME', 'item_sku_manager');

require_once __DIR__ . '/helpers/item_sku_manager_helper.php';

register_activation_hook(ITEM_SKU_MANAGER_MODULE_NAME, 'item_sku_manager_activation_hook');
register_language_files(ITEM_SKU_MANAGER_MODULE_NAME, [ITEM_SKU_MANAGER_MODULE_NAME]);

hooks()->add_action('admin_init', 'item_sku_manager_init_menu');
hooks()->add_action('app_admin_footer', 'item_sku_manager_admin_footer_assets');
hooks()->add_action('after_sales_item_added', 'item_sku_manager_after_sales_item_added');
hooks()->add_action('after_sales_item_updated', 'item_sku_manager_after_sales_item_updated');

function item_sku_manager_activation_hook()
{
    require_once __DIR__ . '/install.php';
}

function item_sku_manager_init_menu()
{
    if (!is_admin()) {
        return;
    }

    $CI = &get_instance();
    $CI->app_menu->add_setup_menu_item('item_sku_manager', [
        'name'     => _l('item_sku_manager'),
        'href'     => admin_url('item_sku_manager'),
        'position' => 78,
        'icon'     => 'fa fa-barcode',
    ]);
}

function item_sku_manager_model()
{
    $CI = &get_instance();
    $CI->load->model('item_sku_manager/Item_sku_manager_model', 'itemSkuManagerModel');

    return $CI->itemSkuManagerModel;
}

function item_sku_manager_admin_footer_assets()
{
    echo '<script src="' . module_dir_url(ITEM_SKU_MANAGER_MODULE_NAME, 'assets/js/sales_item_sku.js') . '?v=102"></script>';
}

function item_sku_manager_after_sales_item_added($payload)
{
    if (!is_array($payload) || empty($payload['item_id'])) {
        return;
    }

    try {
        item_sku_manager_model()->snapshot_sales_item((int) $payload['item_id'], 'sales_item_added');
    } catch (Throwable $e) {
        log_message('error', 'Item SKU Manager add snapshot failed: ' . $e->getMessage());
    }
}

function item_sku_manager_after_sales_item_updated($payload)
{
    if (!is_array($payload) || empty($payload['item_id'])) {
        return;
    }

    try {
        item_sku_manager_model()->snapshot_sales_item((int) $payload['item_id'], 'sales_item_updated');
    } catch (Throwable $e) {
        log_message('error', 'Item SKU Manager update snapshot failed: ' . $e->getMessage());
    }
}
