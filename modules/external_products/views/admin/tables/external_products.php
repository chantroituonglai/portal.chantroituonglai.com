<?php defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();

$aColumns = [
    'id',
    'sku',
    'mapping_id',
    'mapping_type',
];

$sIndexColumn = 'id';
$sTable       = db_prefix() . 'external_products_mapping';

$join  = [];
$where = [];

$mappingType = $CI->input->post('mapping_type');
if (!empty($mappingType)) {
    $where[] = 'AND mapping_type = ' . $CI->db->escape($mappingType);
}

$result  = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, ['id']);
$output  = $result['output'];
$rResult = $result['rResult'];

foreach ($rResult as $aRow) {
    $row   = [];
    $row[] = $aRow['id'];
    $row[] = '<a href="' . admin_url('external_products/edit_mapping/' . $aRow['id']) . '">' . html_escape($aRow['sku']) . '</a>';
    $row[] = html_escape($aRow['mapping_id']);
    $row[] = '<span class="mapping-type-badge ' . html_escape($aRow['mapping_type']) . '">' . format_mapping_type($aRow['mapping_type']) . '</span>';

    $options  = '<div class="row-options">';
    $options .= '<a href="' . admin_url('external_products/edit_mapping/' . $aRow['id']) . '">' . _l('edit') . '</a>';
    $options .= ' | <a href="' . admin_url('external_products/delete_mapping/' . $aRow['id']) . '" class="text-danger _delete">' . _l('delete') . '</a>';
    $options .= '</div>';

    $row[] = $options;

    $row['DT_RowId']    = 'external_product_' . $aRow['id'];
    $row['DT_RowClass'] = 'has-row-options';

    $output['aaData'][] = $row;
}
