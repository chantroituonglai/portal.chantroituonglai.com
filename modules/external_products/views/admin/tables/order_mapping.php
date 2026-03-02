<?php defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();

$aColumns = [
    'id',
    'id',
    'uniquekey',
    'target_id',
    'data',
    'status',
    'dateadded',
    'id',
];

$sIndexColumn = 'id';
$sTable       = db_prefix() . 'external_data_mapping';

$join  = [];
$where = ['AND rel = "Order"'];

$additionalSelect = [];

$result  = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, $additionalSelect);
$output  = $result['output'];
$rResult = $result['rResult'];

foreach ($rResult as $aRow) {
    $mappingData = [];
    if (!empty($aRow['data'])) {
        $decoded = json_decode($aRow['data'], true);
        if (is_array($decoded)) {
            $mappingData = $decoded;
        } elseif (is_string($aRow['data'])) {
            $mappingData = [
                'external_system' => $aRow['data'],
                'mapping_status' => $aRow['data'],
            ];
        }
    }

    $row = [];

    $row[] = '<div class="checkbox"><input type="checkbox" class="order-mapping-select" value="' . $aRow['id'] . '"><label></label></div>';
    $row[] = $aRow['id'];
    $row[] = html_escape($aRow['uniquekey']);
    $row[] = html_escape($mappingData['internal_order_id'] ?? $aRow['target_id']);
    $row[] = html_escape($mappingData['external_system'] ?? '—');

    $isActive = (int) $aRow['status'] === 1;
    $statusLabel = $mappingData['mapping_status'] ?? ($isActive ? _l('active') : _l('inactive'));
    $statusClass = $isActive ? 'label-success' : 'label-default';
    $row[] = '<span class="label ' . $statusClass . '">' . html_escape($statusLabel) . '</span>';

    $row[] = $aRow['dateadded'] ? _dt($aRow['dateadded']) : '—';

    $options  = '<div class="row-options">';
    $options .= '<a href="' . admin_url('external_products/edit_order_mapping/' . $aRow['id']) . '" class="text-success">' . _l('edit') . '</a>';
    $options .= ' | <a href="' . admin_url('external_products/delete_order_mapping/' . $aRow['id']) . '" class="text-danger _delete">' . _l('delete') . '</a>';
    $options .= '</div>';

    $row[] = $options;

    $row['DT_RowId']    = 'order_mapping_' . $aRow['id'];
    $row['DT_RowClass'] = 'has-row-options';

    $output['aaData'][] = $row;
}
