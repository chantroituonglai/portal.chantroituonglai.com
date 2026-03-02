<?php defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();

$aColumns = [
    'id',
    'uniquekey',
    'target_id',
    'title',
    'status',
    'dateadded',
];

$sIndexColumn = 'id';
$sTable       = db_prefix() . 'external_data_mapping';

$join  = [];
$where = ['AND rel = "Order"'];

$additionalSelect = ['data'];

$result  = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, $additionalSelect);
$output  = $result['output'];
$rResult = $result['rResult'];

foreach ($rResult as $aRow) {
    $orderData = [];
    if (!empty($aRow['data'])) {
        $decoded = json_decode($aRow['data'], true);
        if (is_array($decoded)) {
            $orderData = $decoded;
        }
    }

    $row = [];

    $row[] = $aRow['id'];
    $row[] = html_escape($aRow['uniquekey']);
    $row[] = html_escape($orderData['order_number'] ?? $aRow['target_id']);
    $row[] = html_escape($orderData['customer_name'] ?? $aRow['title']);
    $row[] = html_escape($orderData['customer_email'] ?? '—');

    $isActive = (int) $aRow['status'] === 1;
    $statusLabel = $isActive ? _l('active') : _l('inactive');
    $statusClass = $isActive ? 'label-success' : 'label-default';
    $row[] = '<span class="label ' . $statusClass . '">' . $statusLabel . '</span>';

    if (isset($orderData['total_amount'])) {
        $amount   = (float) $orderData['total_amount'];
        $currency = $orderData['currency'] ?? null;
        if (!empty($currency)) {
            $row[] = format_money($amount, strtoupper($currency));
        } else {
            $row[] = app_format_number($amount);
        }
    } else {
        $row[] = '—';
    }

    $row[] = $aRow['dateadded'] ? _dt($aRow['dateadded']) : '—';

    $options  = '<div class="row-options">';
    $options .= '<a href="' . admin_url('external_products/edit_order/' . $aRow['id']) . '" class="text-success">' . _l('edit') . '</a>';
    $options .= ' | <a href="' . admin_url('external_products/delete_order/' . $aRow['id']) . '" class="text-danger _delete">' . _l('delete') . '</a>';
    $options .= '</div>';

    $row[] = $options;

    $row['DT_RowId']    = 'external_order_' . $aRow['id'];
    $row['DT_RowClass'] = 'has-row-options';

    $output['aaData'][] = $row;
}
