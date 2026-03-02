<?php defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();

$aColumns = [
    'id',
    'external_product_id',
    'external_product_name',
    'external_product_sku',
    'external_product_price',
    'external_brand',
    'external_category',
    'external_stock_quantity',
    'last_import_date',
];

$sIndexColumn = 'id';
$sTable       = db_prefix() . 'external_products';

$join  = [];
$where = ['AND external_system = "Haravan"'];

$result  = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, ['id']);
$output  = $result['output'];
$rResult = $result['rResult'];

$baseCurrency = get_base_currency();

foreach ($rResult as $aRow) {
    $row   = [];

    $row[] = $aRow['id'];
    $row[] = html_escape($aRow['external_product_id']);
    $row[] = html_escape($aRow['external_product_name']);
    $row[] = html_escape($aRow['external_product_sku']);

$price = $aRow['external_product_price'];
if ($price !== null) {
    $numericPrice = (float) $price;
    if ($baseCurrency) {
        $row[] = app_format_money($numericPrice, $baseCurrency);
    } else {
        $row[] = app_format_number($numericPrice);
    }
} else {
    $row[] = '—';
}

    $row[] = html_escape($aRow['external_brand']);
    $row[] = html_escape($aRow['external_category']);
    $row[] = app_format_number($aRow['external_stock_quantity']);
    $row[] = $aRow['last_import_date'] ? _dt($aRow['last_import_date']) : '—';

    $options  = '<div class="row-options">';
    $options .= '<a href="' . admin_url('external_products/sync_haravan_product/' . urlencode($aRow['external_product_sku'])) . '" class="text-info" title="' . _l('sync_now') . '"><i class="fa fa-refresh"></i></a>';
    $options .= ' | <a href="' . admin_url('external_products/delete_haravan_product/' . $aRow['id']) . '" class="text-danger _delete">' . _l('delete') . '</a>';
    $options .= '</div>';

    $row[] = $options;

    $row['DT_RowId']    = 'haravan_product_' . $aRow['id'];
    $row['DT_RowClass'] = 'has-row-options';

    $output['aaData'][] = $row;
}
