<?php defined('BASEPATH') or exit('No direct script access allowed');

$aColumns = [
    db_prefix() . 'topic_action_states.id as id',
    db_prefix() . 'topic_action_states.name as name', 
    db_prefix() . 'topic_action_states.action_state_code as action_state_code',
    db_prefix() . 'topic_action_types.name as action_type_name',
    db_prefix() . 'topic_action_states.valid_data as valid_data',
    db_prefix() . 'topic_action_states.datecreated as created_date'
];

$sIndexColumn = 'id';
$sTable = db_prefix() . 'topic_action_states';

$join = [
    'LEFT JOIN ' . db_prefix() . 'topic_action_types ON ' . db_prefix() . 'topic_action_types.action_type_code =' . db_prefix() . 'topic_action_states.action_type_code'
];

$additionalSelect = [
    db_prefix() . 'topic_action_types.name as action_type_name'
];

$result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, [], $additionalSelect);
$output = $result['output'];
$rResult = $result['rResult'];

$output['aaData'] = [];

foreach ($rResult as $aRow) {
    $row = [];
    
    // ID
    $row[] = $aRow['id'];
    
    // Name with edit link
    $nameRow = '<a href="' . admin_url('topics/action_states/edit/' . $aRow['id']) . '">' 
             . $aRow['name'] . '</a>';
    $row[] = $nameRow;
    
    // Action State Code
    $row[] = $aRow['action_state_code'];
    
    // Action Type Name
    $row[] = $aRow['action_type_name'] ?? _l('none');
    
    // Valid Data Toggle
    $checked = $aRow['valid_data'] == 1 ? 'checked' : '';
    $toggleHtml = '<div class="onoffswitch">
        <input type="checkbox" 
            data-switch-url="' . admin_url('topics/action_states/toggle_valid_data/' . $aRow['id']) . '" 
            class="onoffswitch-checkbox" 
            id="valid_data_' . $aRow['id'] . '" 
            data-id="' . $aRow['id'] . '"
            ' . $checked . '>
        <label class="onoffswitch-label" for="valid_data_' . $aRow['id'] . '"></label>
    </div>';
    $row[] = $toggleHtml;
    
    // Created Date
    $row[] = _dt($aRow['created_date']);
     
    // Options
    $options = '<div class="tw-flex tw-items-center tw-space-x-3">';
    
    // Edit button
    $options .= '<a href="' . admin_url('topics/action_states/edit/' . $aRow['id']) 
              . '" class="tw-text-neutral-500 hover:tw-text-neutral-700 focus:tw-text-neutral-700">'
              . '<i class="fa fa-pen-to-square fa-lg"></i>'
              . '</a>';
    
    // Delete button
    if (has_permission('topics', '', 'delete')) {
        $options .= '<a href="' . admin_url('topics/action_states/delete/' . $aRow['id']) 
                  . '" class="tw-mt-px tw-text-neutral-500 hover:tw-text-neutral-700 focus:tw-text-neutral-700 _delete">'
                  . '<i class="fa fa-trash-can fa-lg"></i>'
                  . '</a>';
    }
    
    $options .= '</div>';
    $row[] = $options;

    $output['aaData'][] = $row;
}

echo json_encode($output);
die();