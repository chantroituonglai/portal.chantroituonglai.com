<?php defined('BASEPATH') or exit('No direct script access allowed');

$subQuery = '(
    SELECT mapping_id,
           mapping_type,
           COUNT(*) AS duplicate_count,
           GROUP_CONCAT(sku ORDER BY id) AS skus,
           GROUP_CONCAT(id ORDER BY id) AS record_ids
    FROM ' . db_prefix() . 'external_products_mapping
    GROUP BY mapping_id, mapping_type
    HAVING COUNT(*) > 1
) duplicate_mapping_ids';

$aColumns = [
    'mapping_id',
    'mapping_type',
    'duplicate_count',
    'skus',
];

$sIndexColumn      = 'mapping_id';
$sTable            = $subQuery;
$join              = [];
$where             = [];
$additionalSelect  = ['record_ids'];

$result  = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, $additionalSelect);
$output  = $result['output'];
$rResult = $result['rResult'];

foreach ($rResult as $aRow) {
    $row   = [];
    $row[] = '<strong>' . html_escape($aRow['mapping_id']) . '</strong>';
    $row[] = '<span class="mapping-type-badge ' . html_escape($aRow['mapping_type']) . '">' . format_mapping_type($aRow['mapping_type']) . '</span>';
    $row[] = '<span class="badge badge-danger">' . (int) $aRow['duplicate_count'] . '</span>';

    $skus = array_filter(array_map('trim', explode(',', (string) $aRow['skus'])));
    $row[] = html_escape(implode(', ', $skus));

    $row[] = '<button type="button" class="btn btn-sm btn-info view-duplicates" data-type="mapping_id" data-identifier="' . html_escape($aRow['mapping_id']) . '" data-mapping-type="' . html_escape($aRow['mapping_type']) . '"><i class="fa fa-eye"></i> ' . _l('view_details') . '</button>';

    $row['DT_RowClass'] = 'has-row-options';

    $output['aaData'][] = $row;
}
