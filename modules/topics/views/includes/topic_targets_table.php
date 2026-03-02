<?php defined('BASEPATH') or exit('No direct script access allowed');

$aColumns = [
    'id',
    'title', 
    'target_type',
    'status',
    'datecreated'
];

$sIndexColumn = 'id';
$sTable = db_prefix() . 'topic_target';

$result = data_tables_init($aColumns, $sIndexColumn, $sTable);
$output = $result['output'];
$rResult = $result['rResult'];

foreach ($rResult as $aRow) {
    $row = [];
    
    // ID column
    $row[] = '<span class="text-muted">#' . $aRow['id'] . '</span>';
    
    // Title column with edit link
    $row[] = '<a href="' . admin_url('topics/topic_master/target_edit/' . $aRow['id']) . '">' . $aRow['title'] . '</a>';
    
    // Target type column
    $row[] = '<span class="label label-info">' . $aRow['target_type'] . '</span>';
    
    // Status toggle column
    $toggleUrl = admin_url('topics/topic_master/target_change_status/' . $aRow['id']);
    $checked = $aRow['status'] ? 'checked' : '';
    $status = '<div class="onoffswitch">
        <input type="checkbox" data-switch-url="' . $toggleUrl . '" name="onoffswitch" class="onoffswitch-checkbox" id="status_' . $aRow['id'] . '" ' . $checked . '>
        <label class="onoffswitch-label" for="status_' . $aRow['id'] . '"></label>
    </div>';
    $row[] = $status;
    
    // Date column
    $row[] = _dt($aRow['datecreated']);
    
    // Options column
    $options = '';
    if (has_permission('topics', '', 'edit')) {
        $options .= '<a href="' . admin_url('topics/topic_master/target_edit/' . $aRow['id']) . '" 
            class="btn btn-default btn-icon"><i class="fa fa-pen-to-square"></i></a>';
    }
    if (has_permission('topics', '', 'delete')) {
        $options .= ' <a href="' . admin_url('topics/topic_master/target_delete/' . $aRow['id']) . '" 
            class="btn btn-danger btn-icon _delete"><i class="fa fa-remove"></i></a>';
    }
    $row[] = $options;
    
    $output['aaData'][] = $row;
} 