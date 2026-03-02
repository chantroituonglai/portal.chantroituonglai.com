<?php

defined('BASEPATH') or exit('No direct script access allowed');

$aColumns = [
    'id',
    'topicid',
    'topictitle',
    'status',
    'created_at',
];

$sIndexColumn = 'id';
$sTable       = db_prefix() . 'topic_master';

$result = data_tables_init($aColumns, $sIndexColumn, $sTable, [], [], [
    'id',
    'topicid',
    'topictitle',
    'status',
]);

$output  = $result['output'];
$rResult = $result['rResult'];

foreach ($rResult as $aRow) {
    $row = [];

    // ID
    $row[] = $aRow['id'];
    
    // Topic ID
    $row[] = $aRow['topicid'];
    
    // Topic Title with link
    $title = '<a href="' . admin_url('topics/detail/' . $aRow['id']) . '">' . $aRow['topictitle'] . '</a>';
    $row[] = $title;
    
    // Status
    $status_html = '<div class="onoffswitch">';
    $status_html .= '<input type="checkbox" data-switch-url="' . admin_url('topics/topic_master/change_status') . '" name="onoffswitch" class="onoffswitch-checkbox" id="' . $aRow['id'] . '" data-id="' . $aRow['id'] . '" ' . ($aRow['status'] == 1 ? 'checked' : '') . '>';
    $status_html .= '<label class="onoffswitch-label" for="' . $aRow['id'] . '"></label>';
    $status_html .= '</div>';
    $row[] = $status_html;
    
    // Created Date
    $row[] = _dt($aRow['created_at']);
    
    // Options/Actions
    $options = '';
    if (has_permission('topics', '', 'view')) {
        $options .= '<a href="' . admin_url('topics/detail/' . $aRow['id']) . '" class="btn btn-default btn-icon"><i class="fa fa-eye"></i></a> ';
    }
    if (has_permission('topics', '', 'edit')) {
        $options .= '<a href="' . admin_url('topics/topic_master/edit/' . $aRow['id']) . '" class="btn btn-default btn-icon"><i class="fa fa-pen-to-square"></i></a> ';
    }
    $row[] = $options;

    $output['aaData'][] = $row;
}
