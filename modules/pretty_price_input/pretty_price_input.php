<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: Pretty Price Input
Description: Beautify and normalize price/number inputs on sales documents (invoices, estimates, proposals) while preserving correct calculations and submission values.
Version: 1.0.1
Requires at least: 2.3.*
Author: FHC
Author URI: https://codecanyon.net/user/fhc
*/

// System name
define('PRETTY_PRICE_INPUT_MODULE', 'pretty_price_input');

$CI = &get_instance();

// Module DB schema version
define('PRETTY_PRICE_INPUT_DB_VERSION', 101);

// Register language files (placeholder for future translations)
register_language_files(PRETTY_PRICE_INPUT_MODULE, [PRETTY_PRICE_INPUT_MODULE]);

// Activation hook – set options; migrations will create/alter tables
register_activation_hook(PRETTY_PRICE_INPUT_MODULE, 'pretty_price_input_module_activate');
function pretty_price_input_module_activate()
{
    add_option('ppi_db_version', 0);
}

// Run module migrations similar to geminiai
hooks()->add_action('admin_init', 'pretty_price_input_run_migrations', 2);
function pretty_price_input_run_migrations(): void
{
    $installed = (int) get_option('ppi_db_version');
    if ($installed >= PRETTY_PRICE_INPUT_DB_VERSION) { return; }
    $migrations_dir = __DIR__ . '/migrations';
    if (is_dir($migrations_dir)) {
        $files = glob($migrations_dir . '/*_version_*.php');
        sort($files, SORT_NATURAL);
        foreach ($files as $file) { include $file; }
    }
}

// Inject assets
hooks()->add_action('app_admin_head', 'pretty_price_input_admin_head');
function pretty_price_input_admin_head()
{
    $coreVersion = get_instance()->app_scripts->core_version();
    echo '<link href="' . module_dir_url(PRETTY_PRICE_INPUT_MODULE, 'assets/css/pretty_price_input.css') . '?v=' . microtime(true) . '" rel="stylesheet" type="text/css" />';
    echo '<link href="' . module_dir_url(PRETTY_PRICE_INPUT_MODULE, 'assets/css/ppi_line_discount.css') . '?v=' . microtime(true) . '" rel="stylesheet" type="text/css" />';
}

hooks()->add_action('app_admin_footer', 'pretty_price_input_admin_footer');
function pretty_price_input_admin_footer()
{
    echo '<script src="' . module_dir_url(PRETTY_PRICE_INPUT_MODULE, 'assets/js/pretty_price_input.js') . '?v=' . microtime(true) . '"></script>';
    echo '<script src="' . module_dir_url(PRETTY_PRICE_INPUT_MODULE, 'assets/js/ppi_mini_calc.js') . '?v=' . microtime(true) . '"></script>';
    echo '<script src="' . module_dir_url(PRETTY_PRICE_INPUT_MODULE, 'assets/js/ppi_line_discount.js') . '?v=' . microtime(true) . '"></script>';
}


// Persistence helpers
function ppi_upsert_line_discount($itemId, $relType, $data)
{
    $CI = &get_instance();
    $percent = isset($data['percent']) ? (float) $data['percent'] : 0.0;
    $amount  = isset($data['amount']) ? (float) $data['amount'] : 0.0;
    $type    = isset($data['type']) ? $data['type'] : 'percent';
    $mode    = isset($data['tax_mode']) ? $data['tax_mode'] : 'before_tax';
    if ($percent < 0) { $percent = 0; } if ($percent > 100) { $percent = 100; }
    if ($amount < 0) { $amount = 0; }
    if ($type !== 'percent' && $type !== 'amount') { $type = 'percent'; }
    if ($mode !== 'before_tax' && $mode !== 'after_tax') { $mode = 'before_tax'; }
    $now = date('Y-m-d H:i:s');

    $row = $CI->db->where('itemid', $itemId)->get(db_prefix() . 'ppi_line_discounts')->row();
    if ($row) {
        $CI->db->where('id', $row->id)->update(db_prefix() . 'ppi_line_discounts', [
            'discount_percent' => $percent,
            'discount_type'    => $type,
            'discount_amount'  => $amount,
            'tax_mode'         => $mode,
            'rel_type'         => $relType,
            'updated_at'       => $now,
        ]);
    } else {
        $CI->db->insert(db_prefix() . 'ppi_line_discounts', [
            'itemid'           => $itemId,
            'rel_type'         => $relType,
            'discount_percent' => $percent,
            'discount_type'    => $type,
            'discount_amount'  => $amount,
            'tax_mode'         => $mode,
            'created_at'       => $now,
            'updated_at'       => $now,
        ]);
    }
}

function ppi_delete_discounts_by_item_ids($itemIds)
{
    if (empty($itemIds)) { return; }
    $CI = &get_instance();
    $CI->db->where_in('itemid', $itemIds)->delete(db_prefix() . 'ppi_line_discounts');
}

function ppi_fetch_item_ids_for_sale($relId, $relType)
{
    $CI = &get_instance();
    $rows = $CI->db->where('rel_id', $relId)->where('rel_type', $relType)->get(db_prefix() . 'itemable')->result_array();
    return array_map(function($r){ return (int)$r['id']; }, $rows);
}

function ppi_fetch_discounts_for_item_ids($itemIds)
{
    if (empty($itemIds)) { return []; }
    $CI = &get_instance();
    $rows = $CI->db->where_in('itemid', $itemIds)->get(db_prefix() . 'ppi_line_discounts')->result_array();
    $map = [];
    foreach ($rows as $r) {
        $map[(int)$r['itemid']] = [
            'percent'   => isset($r['discount_percent']) ? (float)$r['discount_percent'] : 0.0,
            'type'      => isset($r['discount_type']) ? $r['discount_type'] : 'percent',
            'amount'    => isset($r['discount_amount']) ? (float)$r['discount_amount'] : 0.0,
            'tax_mode'  => isset($r['tax_mode']) ? $r['tax_mode'] : 'before_tax',
        ];
    }
    return $map;
}

// Save/update hooks
hooks()->add_action('after_invoice_added', function($invoiceId){ ppi_handle_sale_save('invoice', $invoiceId); });
hooks()->add_action('invoice_updated', function($hookData){ if (is_array($hookData) && isset($hookData['id'])) { ppi_handle_sale_save('invoice', (int)$hookData['id']); } });
hooks()->add_action('after_estimate_added', function($estimateId){ ppi_handle_sale_save('estimate', $estimateId); });
hooks()->add_action('after_estimate_updated', function($estimateId){ ppi_handle_sale_save('estimate', $estimateId); });
hooks()->add_action('proposal_created', function($proposalId){ ppi_handle_sale_save('proposal', $proposalId); });
hooks()->add_action('after_proposal_updated', function($proposalId){ ppi_handle_sale_save('proposal', $proposalId); });

// Cleanup hooks
hooks()->add_action('before_invoice_deleted', function($invoiceId){ ppi_delete_discounts_by_item_ids(ppi_fetch_item_ids_for_sale($invoiceId, 'invoice')); });
hooks()->add_action('before_estimate_deleted', function($estimateId){ ppi_delete_discounts_by_item_ids(ppi_fetch_item_ids_for_sale($estimateId, 'estimate')); });
hooks()->add_action('before_proposal_deleted', function($proposalId){ ppi_delete_discounts_by_item_ids(ppi_fetch_item_ids_for_sale($proposalId, 'proposal')); });

function ppi_handle_sale_save($relType, $relId)
{
    $CI = &get_instance();
    $post = $CI->input->post();
    if (!$post) { return; }

    if (isset($post['items']) && is_array($post['items'])) {
        foreach ($post['items'] as $itemId => $data) {
            $hasPercent = isset($data['ppi_discount_percent']) && $data['ppi_discount_percent'] !== '';
            $hasAmount  = isset($data['ppi_discount_amount']) && $data['ppi_discount_amount'] !== '';
            $type       = isset($data['ppi_discount_type']) ? $data['ppi_discount_type'] : ($hasAmount ? 'amount' : 'percent');
            $mode       = isset($data['ppi_tax_mode']) ? $data['ppi_tax_mode'] : 'before_tax';
            if ($hasPercent || $hasAmount) {
                ppi_upsert_line_discount((int)$itemId, $relType, [
                    'percent'  => $hasPercent ? (float)$data['ppi_discount_percent'] : 0.0,
                    'amount'   => $hasAmount ? (float)$data['ppi_discount_amount'] : 0.0,
                    'type'     => $type,
                    'tax_mode' => $mode,
                ]);
            }
        }
    }

    if (isset($post['newitems']) && is_array($post['newitems'])) {
        $newByOrder = [];
        foreach ($post['newitems'] as $key => $ni) {
            if (!isset($ni['order'])) { continue; }
            $newByOrder[(int)$ni['order']] = $ni;
        }
        if (!empty($newByOrder)) {
            $items = $CI->db->where('rel_id', $relId)->where('rel_type', $relType)->order_by('item_order', 'asc')->get(db_prefix() . 'itemable')->result_array();
            foreach ($items as $row) {
                $order = (int)$row['item_order'];
                if (isset($newByOrder[$order])) {
                    $ni = $newByOrder[$order];
                    $hasPercent = isset($ni['ppi_discount_percent']) && $ni['ppi_discount_percent'] !== '';
                    $hasAmount  = isset($ni['ppi_discount_amount']) && $ni['ppi_discount_amount'] !== '';
                    $type       = isset($ni['ppi_discount_type']) ? $ni['ppi_discount_type'] : ($hasAmount ? 'amount' : 'percent');
                    $mode       = isset($ni['ppi_tax_mode']) ? $ni['ppi_tax_mode'] : 'before_tax';
                    if ($hasPercent || $hasAmount) {
                        ppi_upsert_line_discount((int)$row['id'], $relType, [
                            'percent'  => $hasPercent ? (float)$ni['ppi_discount_percent'] : 0.0,
                            'amount'   => $hasAmount ? (float)$ni['ppi_discount_amount'] : 0.0,
                            'type'     => $type,
                            'tax_mode' => $mode,
                        ]);
                    }
                }
            }
        }
    }

    if (isset($post['removed_items']) && is_array($post['removed_items'])) {
        $removed = array_map('intval', $post['removed_items']);
        ppi_delete_discounts_by_item_ids($removed);
    }
}

// Filters for preview display (HTML/PDF)
hooks()->add_filter('item_preview_rate', function($display, $context){
    if (!is_array($context) || !isset($context['item']) || !isset($context['transaction'])) { return $display; }
    $item = $context['item'];
    $dmap = ppi_fetch_discounts_for_item_ids([(int)$item['id']]);
    $d = isset($dmap[(int)$item['id']]) ? $dmap[(int)$item['id']] : null;
    if (!$d) { return $display; }
    if ($d['type'] === 'percent' && $d['tax_mode'] === 'before_tax' && $d['percent'] > 0) {
        return $display . ' <small>(' . app_format_number($d['percent']) . '% off)</small>';
    }
    return $display;
}, 10, 2);

hooks()->add_filter('item_preview_amount_with_currency', function($display){
    $args = func_get_args();
    // Core passes: ($display, $item, $transaction, $exclude_currency)
    $item = isset($args[1]) ? $args[1] : null;
    $transaction = isset($args[2]) ? $args[2] : null;
    if (!$item || !$transaction) { return $display; }
    $dmap = ppi_fetch_discounts_for_item_ids([(int)$item['id']]);
    $d = isset($dmap[(int)$item['id']]) ? $dmap[(int)$item['id']] : null;
    if (!$d) { return $display; }
    $qty = isset($item['qty']) ? (float)$item['qty'] : 0.0;
    $rate = isset($item['rate']) ? (float)$item['rate'] : 0.0;
    $subtotal = $qty * $rate;
    $taxesTotal = 0.0;
    if (isset($item['taxes']) && is_array($item['taxes'])) {
        foreach ($item['taxes'] as $tax) {
            $taxRate = isset($tax['taxrate']) ? (float)$tax['taxrate'] : 0.0;
            $taxesTotal += ($subtotal / 100.0) * $taxRate;
        }
    }
    $lineWithTax = $subtotal + $taxesTotal;

    $discVal = 0.0;
    if ($d['type'] === 'percent') {
        $base = ($d['tax_mode'] === 'after_tax') ? $lineWithTax : $subtotal;
        $discVal = $base * ($d['percent'] / 100.0);
    } else { // amount
        $base = ($d['tax_mode'] === 'after_tax') ? $lineWithTax : $subtotal;
        $discVal = min($d['amount'], $base);
    }
    if ($discVal <= 0) { return $display; }
    $formatted = app_format_money($discVal, $transaction->currency_name, false);
    $modeLabel = $d['tax_mode'] === 'after_tax' ? _l('ppi_mode_after_tax') : _l('ppi_mode_before_tax');
    if ($d['type'] === 'percent') {
        return $display . '<br><span style="color:#777; font-size:85%">' . _l('ppi_line_discount_word') . ' ' . app_format_number($d['percent']) . '% (' . e($modeLabel) . ') (-' . $formatted . ')</span>';
    }
    return $display . '<br><span style="color:#777; font-size:85%">' . _l('ppi_line_discount_word') . ' ' . $formatted . ' (' . e($modeLabel) . ')</span>';
}, 10, 4);


