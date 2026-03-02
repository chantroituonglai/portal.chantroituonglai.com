<?php defined('BASEPATH') or exit('No direct script access allowed');

// Load models
$CI = &get_instance();
$CI->load->model('topics/Action_type_model');
$CI->load->model('topics/Action_state_model');

// Get table prefix
$tablePrefix = db_prefix();

$aColumns = [
    $tablePrefix . 'topic_action_types.position as position',
    $tablePrefix . 'topic_action_types.id as id',
    $tablePrefix . 'topic_action_types.name as name',
    $tablePrefix . 'topic_action_types.action_type_code as action_type_code',
    $tablePrefix . 'topic_action_types.datecreated as created_date'
];

$sIndexColumn = 'id';
$sTable = $tablePrefix . 'topic_action_types';

$join = [
    'LEFT JOIN ' . $tablePrefix . 'topic_action_types parent ON parent.id = ' . $tablePrefix . 'topic_action_types.parent_id'
];

$additionalSelect = [
    'parent.name as parent_name',
    $tablePrefix . 'topic_action_types.parent_id as parent_id'
];

// Simple GROUP BY using table name
$CI->db->group_by('id');

$result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, [], $additionalSelect);

$output = $result['output'];
$rResult = $result['rResult'];

foreach ($rResult as $aRow) {
    $row = [];
    
    $indent = '';
    $childClass = '';
    if ($aRow['parent_id']) {
        $indent = '<span class="tw-ml-8"></span>';
        $childClass = ' action-type-row child-of-' . $aRow['parent_id'];
    }

    $row[] = '<span class="position-value" data-type-id="' . $aRow['id'] . '">' . $aRow['position'] . '</span>';

    $row[] = $indent . '<a href="#" class="toggle-states" data-type-id="'.$aRow['id'].'">
                <i class="fa fa-plus-square"></i>
              </a>' . $aRow['id'];
    
    $nameRow = $indent . '<a href="' . admin_url('topics/action_types/edit/' . $aRow['id']) . '"><span class="label label-warning tw-text-neutral-500">Action Type</span> ' 
             . html_escape($aRow['name']) . '</a>';
    $row[] = $nameRow;
    
    $row[] = $indent . html_escape($aRow['action_type_code']);
    
    $row[] = _dt($aRow['created_date']);

    $validDataHtml = '<div class="tw-flex tw-items-center tw-space-x-3"></div>';
    $row[] = $validDataHtml;

    $options = '<div class="tw-flex tw-items-center tw-space-x-3">';
    if (has_permission('topics', '', 'edit')) {
        $options .= '<a href="' . admin_url('topics/action_types/edit/' . $aRow['id']) . '" 
                       class="tw-text-neutral-500 hover:tw-text-neutral-700 focus:tw-text-neutral-700">
                       <i class="fa fa-pen-to-square fa-lg"></i>
                    </a>';
    }
    if (has_permission('topics', '', 'delete')) {
        $options .= '<a href="' . admin_url('topics/action_types/delete/' . $aRow['id']) . '" 
                       class="tw-mt-px tw-text-neutral-500 hover:tw-text-neutral-700 focus:tw-text-neutral-700 _delete">
                       <i class="fa fa-trash-can fa-lg"></i>
                    </a>';
    }
    $options .= '</div>';
    $row[] = $options;

    $row['DT_RowClass'] = 'action-type-row';
    $row['DT_RowAttrs'] = [
        'data-type-id' => $aRow['id']
    ];
    
    $output['aaData'][] = $row;

    // Get states using table prefix
    $states = $CI->Action_type_model->get_states_for_type($aRow['action_type_code']);
    foreach ($states as $state) {
        $childRow = [];
        $childRow[] = '';
        $childRow[] = '';
        
        $stateNameHtml = '<div class="child-content">';
        $stateNameHtml .= '<span class="label label-info tw-text-neutral-500">Action State</span> ' ;
      
        $stateNameHtml .= html_escape($state['name']) . '</div>';
        $childRow[] = $stateNameHtml;

        $stateCodeHtml = '<div class="child-content">' . html_escape($state['action_state_code']);
        if ($state['valid_data'] == 1) {
            $stateCodeHtml .= ' <span class="label label-warning">' . _l('standard_data') . '</span>';
        }
        $stateCodeHtml .= '</div>';
        $childRow[] = $stateCodeHtml;

        $childRow[] = _dt($state['datecreated']);
        
        $stateOptions = '<div class="tw-flex tw-items-center tw-space-x-3">';
        if (has_permission('topics', '', 'edit')) {
            $stateOptions .= '<a href="' . admin_url('topics/action_states/edit/' . $state['id']) . '" 
                               class="tw-text-neutral-500 hover:tw-text-neutral-700 focus:tw-text-neutral-700">
                               <i class="fa fa-pen-to-square fa-lg"></i>
                            </a>';
        }

        if (has_permission('topics', '', 'delete')) {
            $stateOptions .= '<a href="' . admin_url('topics/action_states/delete/' . $state['id']) . '" 
                               class="tw-mt-px tw-text-neutral-500 hover:tw-text-neutral-700 focus:tw-text-neutral-700 _delete">
                               <i class="fa fa-trash-can fa-lg"></i>
                            </a>';
        }
        $stateOptions .= '</div>';
        $childRow[] = $stateOptions;

        $output['aaData'][] = array_merge(
            ['DT_RowClass' => 'child-row hidden child-of-'.$aRow['id']], $childRow);
    }
}

echo json_encode($output);
die(); 