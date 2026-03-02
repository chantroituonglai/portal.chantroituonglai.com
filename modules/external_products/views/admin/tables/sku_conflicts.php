<?php defined('BASEPATH') or exit('No direct script access allowed');

$subQuery = '(
    SELECT sku,
           mapping_type,
           COUNT(DISTINCT mapping_id) AS mapping_count,
           GROUP_CONCAT(DISTINCT mapping_id ORDER BY mapping_id) AS mapping_ids,
           GROUP_CONCAT(id ORDER BY id) AS record_ids
    FROM ' . db_prefix() . 'external_products_mapping
    GROUP BY sku, mapping_type
    HAVING COUNT(DISTINCT mapping_id) > 1
) sku_conflicts';

$aColumns = [
    'sku',
    'mapping_type',
    'mapping_count',
    'mapping_ids',
];

$sIndexColumn     = 'sku';
$sTable           = $subQuery;
$join             = [];
$where            = [];
$additionalSelect = ['record_ids'];

$result  = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, $additionalSelect);
$output  = $result['output'];
$rResult = $result['rResult'];

foreach ($rResult as $aRow) {
    $row   = [];
    $row[] = '<strong>' . html_escape($aRow['sku']) . '</strong>';
    $row[] = '<span class="mapping-type-badge ' . html_escape($aRow['mapping_type']) . '">' . format_mapping_type($aRow['mapping_type']) . '</span>';
    $row[] = '<span class="badge badge-info">' . (int) $aRow['mapping_count'] . '</span>';

    $mappingIds = array_filter(array_map('trim', explode(',', (string) $aRow['mapping_ids'])));
    $row[]      = html_escape(implode(', ', $mappingIds));

    $row[] = '<button type="button" class="btn btn-sm btn-warning view-duplicates" data-type="sku_conflict" data-identifier="' . html_escape($aRow['sku']) . '" data-mapping-type="' . html_escape($aRow['mapping_type']) . '"><i class="fa fa-exclamation-triangle"></i> ' . _l('view_details') . '</button>';

    $row['DT_RowClass'] = 'has-row-options';

    $output['aaData'][] = $row;
}
