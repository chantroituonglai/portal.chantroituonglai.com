<?php defined('BASEPATH') or exit('No direct script access allowed');

$subQuery = '(
    SELECT sku,
           COUNT(*) AS duplicate_count,
           GROUP_CONCAT(mapping_id ORDER BY id) AS mapping_ids,
           GROUP_CONCAT(mapping_type ORDER BY id) AS mapping_types,
           GROUP_CONCAT(id ORDER BY id) AS record_ids
    FROM ' . db_prefix() . 'external_products_mapping
    GROUP BY sku
    HAVING COUNT(*) > 1
) duplicate_skus';

$aColumns = [
    'sku',
    'duplicate_count',
    'mapping_ids',
    'mapping_types',
];

$sIndexColumn = 'sku';
$sTable       = $subQuery;
$join         = [];
$where        = [];
$additionalSelect = ['record_ids'];

$result  = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, $additionalSelect);
$output  = $result['output'];
$rResult = $result['rResult'];

foreach ($rResult as $aRow) {
    $row    = [];
    $row[]  = '<strong>' . html_escape($aRow['sku']) . '</strong>';
    $row[]  = '<span class="badge badge-warning">' . (int) $aRow['duplicate_count'] . '</span>';

    $mappingIds = array_filter(array_map('trim', explode(',', (string) $aRow['mapping_ids'])));
    $row[]      = html_escape(implode(', ', $mappingIds));

    $mappingTypes = array_unique(array_filter(array_map('trim', explode(',', (string) $aRow['mapping_types']))));
    $badges       = [];
    foreach ($mappingTypes as $type) {
        $badges[] = '<span class="badge badge-secondary mapping-type-badge ' . html_escape($type) . '">' . format_mapping_type($type) . '</span>';
    }
    $row[] = implode(' ', $badges);

    $row[] = '<button type="button" class="btn btn-sm btn-info view-duplicates" data-type="sku" data-identifier="' . html_escape($aRow['sku']) . '"><i class="fa fa-eye"></i> ' . _l('view_details') . '</button>';

    $row['DT_RowClass'] = 'has-row-options';

    $output['aaData'][] = $row;
}
