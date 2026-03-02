<?php defined('BASEPATH') or exit('No direct script access allowed');

$aColumns = [
    'tm.id',
    'tm.topicid',
    'tm.topictitle',
    'tm.status',
    'tm.datecreated'
];

$sIndexColumn = 'tm.id';
$sTable = db_prefix() . 'topic_master tm';
$join = [
    'JOIN ' . db_prefix() . 'topic_controller tc ON tc.topic_id = tm.id AND tc.controller_id = ' . $controller_id
];
$where = [];

$result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where);

$output = $result['output'];
$rResult = $result['rResult'];

foreach ($rResult as $aRow) {
    $row = [];
    
    $row[] = '<input type="checkbox" class="related-topic-select" value="' . $aRow['id'] . '">';
    $row[] = '<div class="tw-truncate">' . html_escape($aRow['topicid']) . '</div>';
    $row[] = '<div class="tw-truncate"><a href="' . admin_url('topics/detail/' . $aRow['id'] . '?is_from_master=1') . '">' . html_escape($aRow['topictitle']) . '</a></div>';
    $row[] = '<span class="label ' . ($aRow['status'] ? 'label-success' : 'label-danger') . '">' . _l($aRow['status'] ? 'active' : 'inactive') . '</span>';
    $row[] = _dt($aRow['datecreated']);
    $row[] = '<div class="text-right"><a href="' . admin_url('topics/detail/' . $aRow['id'] . '?is_from_master=1') . '" class="btn btn-default btn-icon"><i class="fa fa-eye"></i></a></div>';

    $output['data'][] = $row;
}

echo json_encode($output);
die(); 