<?php defined('BASEPATH') or exit('No direct script access allowed');

$aColumns = [
    db_prefix() . 'topics.id',
    db_prefix() . 'topics.topicid as topicid',
    db_prefix() . 'topics.topictitle as topictitle',
    db_prefix() . 'topics.action_type_code as action_type_code',
    db_prefix() . 'topics.action_state_code as action_state_code',
    db_prefix() . 'topics.dateupdated as dateupdated'
];

$sIndexColumn = 'id';
$sTable = db_prefix() . 'topics';

$join = [
    'LEFT JOIN ' . db_prefix() . 'topic_action_types ON ' . db_prefix() . 'topic_action_types.action_type_code = ' . db_prefix() . 'topics.action_type_code',
    'LEFT JOIN ' . db_prefix() . 'topic_action_states ON ' . db_prefix() . 'topic_action_states.action_state_code = ' . db_prefix() . 'topics.action_state_code'
];

$additionalSelect = [
    db_prefix() . 'topics.id as id',
    db_prefix() . 'topic_action_types.name as action_type_name',
    db_prefix() . 'topic_action_states.name as action_state_name',
    db_prefix() . 'topic_action_states.color as state_color'
];

$result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, [], $additionalSelect);

$output = $result['output'];
$rResult = $result['rResult'];

$output['aaData'] = [];
foreach ($rResult as $aRow) {
    $row = [];
    
    $row[] = '<input type="checkbox" class="row-checkbox" value="'.$aRow['id'].'">';
    $row[] = '<div class="topic-id-container tw-w-[200px]">
                <div class="topic-id-content">
                    <span class="topic-id-text">'.html_escape($aRow['topicid']).'</span>
                    <button type="button" class="btn-copy" onclick="copyToClipboard(\''.$aRow['topicid'].'\')">
                        <i class="fa fa-copy"></i>
                    </button>
                </div>
              </div>';
    $row[] = '<div class="tw-max-w-[200px] tw-truncate" data-toggle="tooltip" title="'.html_escape($aRow['topictitle']).'">'.
                '<a href="'.admin_url('topics/detail/'.$aRow['id']).'">'.html_escape($aRow['topictitle']).'</a>'.
             '</div>';
    $row[] = '<span class="label label-info">'.html_escape($aRow['action_type_name']).'</span>';
    $row[] = '<span class="label" style="background-color: '.html_escape($aRow['state_color']).'">'.
                html_escape($aRow['action_state_name']).
             '</span>';
    $row[] = _dt($aRow['dateupdated']);
    $row[] = '<div class="tw-flex tw-items-center tw-space-x-3">'.
                '<a href="'.admin_url('topics/detail/'.$aRow['id']).'" class="tw-text-neutral-500 hover:tw-text-neutral-700">'.
                    '<i class="fa fa-eye fa-lg"></i>'.
                '</a>'.
             '</div>';

    $output['aaData'][] = $row;
} 