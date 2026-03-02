<?php defined('BASEPATH') or exit('No direct script access allowed');

$aColumns = [
    'tm.id as id',
    'tm.topicid as topicid',
    'tm.topictitle as topictitle',
    'tm.status as status',
    'tm.datecreated as datecreated',
    'tm.id as options'
];

$sIndexColumn = 'tm.id';
$sTable = db_prefix() . 'topic_master tm';

// Get CI instance
$CI = &get_instance();
$CI->load->model('Topic_controller_model');

// Build query based on table type
$where = [];
$join = [];

// Combine conditions
$search = $CI->input->post('search');

if (isset($table_type) && $table_type === 'related') {
    // For related topics table
    $join[] = 'JOIN ' . db_prefix() . 'topic_controller tc ON tc.topic_id = tm.id AND tc.controller_id = ' . intval($controller_id);
    $aColumns[4] = 'tc.datecreated as assigned_date'; // Use tc.datecreated for related topics
} else {
    // For available topics table  $where = ['AND (staff = ' . get_staff_user_id() . ' OR creator=' . get_staff_user_id() . ')'];
    // $where[] = 'AND status = 1';
  
    $existing_topic_ids = $CI->Topic_controller_model->get_topic_ids_by_controller($controller_id);
    if (!empty($existing_topic_ids)) {
        $where[] = 'AND id NOT IN (' . implode(',', array_map('intval', $existing_topic_ids)) . ')';
    }
}

if (isset($search['value']) && !empty($search['value'])) {
    // $where = !empty($where) ? [' ' . implode(' AND ', $where)] : [];
} else {
    // $where = !empty($where) ? ['' . implode(' AND ', $where)] : [];
}
 

try {
    $CI->db->db_debug = TRUE;
    $CI->db->save_queries = TRUE;

    log_activity("Starting data_tables_init for topics table with params: " . json_encode([
        'where' => $where,
        'join' => $join,
        'controller_id' => $controller_id,
        'table_type' => $table_type ?? 'available'
    ]));

    $result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where);
    
    $output = $result['output'];
    $rResult = $result['rResult'];
    $output['data'] = [];

    foreach ($rResult as $aRow) {
        $row = [];
        
        // Checkbox column
        $checkbox_class = isset($table_type) && $table_type === 'related' ? 'related-topic-select' : 'topic-select';
        $row[] = '<input type="checkbox" class="' . $checkbox_class . '" value="' . $aRow['id'] . '">';
        
        // Topic ID
        $row[] = '<div class="tw-truncate">' . html_escape($aRow['topicid']) . '</div>';
        
        // Title with link
        $title_link = '<div class="tw-truncate" data-toggle="tooltip" title="' . html_escape($aRow['topictitle']) . '">';
        $title_link .= '<a href="' . admin_url('topics/detail/' . $aRow['id'] . '?is_from_master=1') . '">';
        if (!isset($table_type) || $table_type !== 'related') {
            $title_link .= '<i class="fa fa-external-link"></i> ';
        }
        $title_link .= html_escape($aRow['topictitle']) . '</a></div>';
        $row[] = $title_link;
        
        // Status
        $status_class = $aRow['status'] ? 'success' : 'danger';
        $status_text = $aRow['status'] ? 'active' : 'inactive';
        $row[] = '<span class="label label-' . $status_class . '">' . _l($status_text) . '</span>';
        
        // Date created
        if (isset($table_type) && $table_type === 'related') {
            $row[] = '<div class="text-nowrap">' . _dt($aRow['assigned_date']) . '</div>';
        } else {
            $row[] = '<div class="text-nowrap">' . _dt($aRow['datecreated']) . '</div>';
        }
        
        // Options column
        if (isset($table_type) && $table_type === 'related') {
            $options = '';
            if (has_permission('topics', '', 'view')) {
                $options .= '<a href="' . admin_url('topics/detail/' . $aRow['id'] . '?is_from_master=1') . '" 
                              class="tw-text-neutral-500 hover:tw-text-neutral-700 focus:tw-text-neutral-700">
                              <i class="fa fa-eye fa-lg"></i>
                           </a>';
            }
            if (has_permission('topics', '', 'delete')) {
                $options .= ' <a href="#" onclick="removeRelatedTopic(' . $aRow['id'] . '); return false;" 
                              class="tw-text-neutral-500 hover:tw-text-neutral-700 focus:tw-text-neutral-700">
                              <i class="fa fa-times fa-lg"></i>
                           </a>';
            }
            $row[] = '<div class="tw-flex tw-items-center tw-space-x-3">' . $options . '</div>';
        } else {
            $row[] = ''; // Empty column for available topics table
        }
        
        $output['data'][] = $row;
    }

    unset($output['aaData']);
    echo json_encode($output);
    die();

} catch (Exception $e) {
    // Get the last executed query if available
    $last_query = isset($CI->db->queries) ? end($CI->db->queries) : 'Query not available';
    
    log_activity("Error in available_topics_table: " . $e->getMessage() . "\nFailed Query: " . $last_query);
    
    // Prepare error message with copy button for query
    $error_message = 'Database Error: ' . htmlspecialchars($e->getMessage());
    if (is_admin()) {
        $error_message .= '<br><small class="text-muted query-container">' . 
                         'Query: <span class="query-text">' . htmlspecialchars($last_query) . '</span>' .
                         '<button class="btn btn-xs btn-default copy-query" data-query="' . htmlspecialchars($last_query) . '">' .
                         '<i class="fa fa-copy"></i></button></small>';
    }
    
    $output = [
        'hasError' => true,
        'draw' => isset($_POST['draw']) ? intval($_POST['draw']) : 0,
        'recordsTotal' => 1,
        'recordsFiltered' => 1,
        'data' => [[
            '<input type="checkbox" disabled>',
            '<span class="text-danger"><i class="fa fa-exclamation-triangle"></i></span>',
            '<div class="tw-max-w-[300px] tw-truncate text-danger">' . $error_message . '</div>',
            '<span class="label label-danger">Error</span>',
            _dt(date('Y-m-d H:i:s'))
        ]]
    ];
    
    echo json_encode($output);
    die();
} 