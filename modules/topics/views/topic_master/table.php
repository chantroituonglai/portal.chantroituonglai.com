<?php defined('BASEPATH') or exit('No direct script access allowed');

$aColumns = [
    'id',
    'topicid',
    'topictitle',
    'status',
    'datecreated',
    'dateupdated'
];

$sIndexColumn = 'id';
$sTable = db_prefix() . 'topic_master';

$result = data_tables_init($aColumns, $sIndexColumn, $sTable);
$output = $result['output'];
$rResult = $result['rResult'];

foreach ($rResult as $aRow) {
    $row = [];
    
    // Topic ID
    $row[] = '<a href="' . admin_url('topics/detail/' . $aRow['id']) . '">' 
           . html_escape($aRow['topicid']) . '</a>';
    
    // Title
    $row[] = html_escape($aRow['topictitle']);
    
    // Status
    $checked = $aRow['status'] ? 'checked' : '';
    $row[] = '<div class="onoffswitch">
                <input type="checkbox" data-switch-url="' . admin_url() . 'topics/topic_master/change_status" 
                       name="onoffswitch" class="onoffswitch-checkbox" id="' . $aRow['id'] . '" 
                       data-id="' . $aRow['id'] . '" ' . $checked . '>
                <label class="onoffswitch-label" for="' . $aRow['id'] . '"></label>
            </div>';
    
    // Created Date
    $row[] = _dt($aRow['datecreated']);
    
    // Updated Date  
    $row[] = _dt($aRow['dateupdated']);
    
    // Options
    $options = '<div class="tw-flex tw-items-center tw-space-x-3">';
    
    if (has_permission('topics', '', 'view')) {
        $options .= '<a href="' . admin_url('topics/detail/' . $aRow['id']) . '" 
                       class="tw-text-neutral-500 hover:tw-text-neutral-700 focus:tw-text-neutral-700">
                       <i class="fa fa-eye fa-lg"></i>
                    </a>';
    }
    
    if (has_permission('topics', '', 'edit')) {
        $options .= '<a href="' . admin_url('topics/topic_master/edit/' . $aRow['id']) . '" 
                       class="tw-text-neutral-500 hover:tw-text-neutral-700 focus:tw-text-neutral-700">
                       <i class="fa fa-pen-to-square fa-lg"></i>
                    </a>';
    }
    
    $options .= '</div>';
    
    $row[] = $options;

    $output['aaData'][] = $row;
}

echo json_encode($output);
die(); 