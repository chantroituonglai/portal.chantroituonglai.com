<?php defined('BASEPATH') or exit('No direct script access allowed');

$aColumns = [
    'tm.id',
    'tm.topicid',
    'tm.topictitle',
    'tm.status',
    'tm.datecreated',
    '(SELECT t.id FROM ' . db_prefix() . 'topics t 
      WHERE t.topicid = tm.topicid 
      ORDER BY t.dateupdated DESC 
      LIMIT 1) as latest_topic_id'
];

$sIndexColumn = 'id';
$sTable = db_prefix() . 'topic_master tm';

$join = [];
$where = [];
$additionalSelect = [
    '(SELECT t.action_type_code FROM ' . db_prefix() . 'topics t 
      WHERE t.topicid = tm.topicid 
      ORDER BY t.dateupdated DESC 
      LIMIT 1) as latest_action_type',
    '(SELECT tas.name FROM ' . db_prefix() . 'topic_action_states tas 
      JOIN ' . db_prefix() . 'topics t ON tas.action_state_code = t.action_state_code 
      WHERE t.topicid = tm.topicid 
      ORDER BY t.dateupdated DESC 
      LIMIT 1) as latest_state_name',
    '(SELECT tas.color FROM ' . db_prefix() . 'topic_action_states tas 
      JOIN ' . db_prefix() . 'topics t ON tas.action_state_code = t.action_state_code 
      WHERE t.topicid = tm.topicid 
      ORDER BY t.dateupdated DESC 
      LIMIT 1) as latest_state_color'
];

// Handle filters from POST data
$filters = $this->ci->input->post('filters');
$search_mode = $this->ci->input->post('search_mode');

// Đảm bảo filters luôn là array và có giá trị mặc định là 'total'
if (empty($filters)) {
    $filters = ['total'];
}
if (!is_array($filters)) {
    $filters = [$filters];
}

// Xử lý các filters
if (in_array('total', $filters)) {
    // Không áp dụng filter nếu là total
    $where = [];
} else {
    $filter_conditions = [];
    $combined_conditions = [];
    
    foreach ($filters as $filter) {
        $condition = '';
        switch ($filter) {
            case 'writing':
                // Lấy danh sách các trạng thái viết bài (ngoại trừ PostCompleted)
                $writing_states = [
                    'ExecChooseStyle',
                    'ExecutionTag_ExecWriting_Start',
                    'ExecutionTag_ExecWriting_Partial',
                    'ExecutionTag_ExecWriting_Complete',
                    'ExecutionTag_ExecWriting_PostCreated',
                    'ExecutionTag_ExecWriting_Upload'
                ];
                
                $writing_states_str = "'" . implode("','", $writing_states) . "'";
                $condition = "EXISTS (
                    SELECT 1 FROM " . db_prefix() . "topics t1 
                    WHERE t1.topicid = tm.topicid 
                    AND t1.action_type_code = 'ExecutionTag_ExecWriting'
                    AND t1.action_state_code IN ($writing_states_str)
                    AND t1.id = (
                        SELECT MAX(t2.id) 
                        FROM " . db_prefix() . "topics t2 
                        WHERE t2.topicid = t1.topicid
                    )
                ) AND tm.status = 1";
                break;
                
            case 'fail':
                // Lấy danh sách các mã trạng thái thất bại
                $this->ci->db->select('action_state_code');
                $this->ci->db->from(db_prefix() . 'topic_action_states');
                $this->ci->db->group_start()
                    ->like('name', 'fail', 'both')
                    ->or_like('name', 'error', 'both')
                    ->or_like('name', 'failed', 'both')
                    ->or_like('action_state_code', 'fail', 'both')
                    ->or_like('action_state_code', 'error', 'both')
                    ->or_like('action_state_code', 'failed', 'both')
                ->group_end();
                $query = $this->ci->db->get();
                $fail_states = array_column($query->result_array(), 'action_state_code');

                if (!empty($fail_states)) {
                    $fail_states_str = "'" . implode("','", $fail_states) . "'";
                    $condition = "EXISTS (
                        SELECT 1 FROM " . db_prefix() . "topics t1 
                        WHERE t1.topicid = tm.topicid 
                        AND t1.action_state_code IN ($fail_states_str)
                        AND t1.id = (
                            SELECT MAX(t2.id) 
                            FROM " . db_prefix() . "topics t2 
                            WHERE t2.topicid = t1.topicid
                        )
                    ) AND tm.status = 1"; // Chỉ lấy active masters
                }
                break;
            case 'active':
                $condition = "tm.status = 1"; // Lọc theo trạng thái active của topic_master
                break;
            case 'scheduled_social':
                // Lọc các topic có liên quan đến social media
                $condition = "EXISTS (
                    SELECT 1 FROM " . db_prefix() . "topics t1 
                    WHERE t1.topicid = tm.topicid 
                    AND (
                        t1.action_type_code = 'ExecutionTag_ExecSocialMedia'
                        OR t1.action_state_code IN (
                            'ExecSocialPost',
                            'ExecSocialScheduled', 
                            'ExecSocialPosted',
                            'ExecPendingSocialPost'
                        )
                    )
                ) AND tm.status = 1"; // Chỉ lấy active masters
                break;
            case 'social_audit':
                // Lọc các topic có liên quan đến social audit
                $condition = "EXISTS (
                    SELECT 1 FROM " . db_prefix() . "topics t1 
                    WHERE t1.topicid = tm.topicid 
                    AND t1.action_state_code IN (
                        'ExecutionTag_ExecAudit_SocialAuditStart',
                        'ExecutionTag_ExecAudit_SocialAuditCompleted',
                        'ExecutionTag_ExecAudit_SocialAuditPartial'
                    )
                ) AND tm.status = 1"; // Chỉ lấy active masters
                break;
            // ... other cases ...
        }
        
        if (!empty($condition)) {
            if ($search_mode === 'and') {
                $combined_conditions[] = $condition;
            } else {
                $filter_conditions[] = $condition;
            }
        }
    }
    
    if (!empty($filter_conditions) || !empty($combined_conditions)) {
        if ($search_mode === 'and' && !empty($combined_conditions)) {
            // Combine conditions with AND
            $where[] = 'WHERE (' . implode(') AND (', $combined_conditions) . ')';
        } else {
            // Combine conditions with OR (default)
            if (!empty($where)) {
                $where[] = 'AND (' . implode(' OR ', $filter_conditions) . ')';
            } else {
                $where[] = 'WHERE (' . implode(' OR ', $filter_conditions) . ')';
            }
        }
    }
}

// Add debug logging
log_activity("Processing filters: " . json_encode([
    'received_filters' => $filters,
    'where_conditions' => $where
]));

// Thêm logging để debug
if (in_array('fail', $filters)) {
    $this->ci->db->select('action_state_code');
    $this->ci->db->from(db_prefix() . 'topic_action_states');
    $this->ci->db->like('name', 'fail', 'both');
    $this->ci->db->or_like('action_state_code', 'fail', 'both');
    $query = $this->ci->db->get();
    $fail_states = array_column($query->result_array(), 'action_state_code');
    
    log_activity("Fail states found: " . json_encode($fail_states));
    log_activity("Generated SQL condition: " . end($where));
}

// Enable query logging before data_tables_init
try {
    $this->ci->db->db_debug = TRUE;
    $this->ci->db->save_queries = TRUE;

    log_activity("Starting data_tables_init with params: " . json_encode([
        'where' => $where,
        'join' => $join
    ]));

    $result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, $additionalSelect);

} catch (Exception $e) {
    // Get the last executed query if available
    $last_query = isset($this->ci->db->queries) ? end($this->ci->db->queries) : 'Query not available';
    
    log_activity("Error in topics_table: " . $e->getMessage() . "\nFailed Query: " . $last_query);
    
    // Prepare error message - include query for admins only
    $error_message = 'Database Error: ' . htmlspecialchars($e->getMessage());
    if (is_admin()) {
        $error_message .= '<br><small class="text-muted">Query: ' . htmlspecialchars($last_query) . '</small>';
    }
    
    // Create a single row response with error details matching the column structure
    $output = [
        'aaData' => [[
            '0',  // id for checkbox column
            '<div class="topic-id-container tw-w-[200px]">
                <div class="topic-id-content tw-flex tw-items-center">
                    <span class="text-danger"><i class="fa fa-exclamation-triangle"></i></span>
                </div>
            </div>',  // topic id column with error icon
            '<div class="tw-max-w-[300px] tw-truncate text-danger">
                ' . $error_message . '
            </div>', // title column with error message
            '<span class="label label-danger">Error</span>', // status column
            _dt(date('Y-m-d H:i:s')), // date column with current timestamp
            '<div class="tw-flex tw-items-center tw-space-x-3">
                <span class="text-danger"><i class="fa fa-exclamation-circle"></i></span>
            </div>'  // options column with error icon
        ]]
    ];
    
    echo json_encode($output);
    die();
}

$output = $result['output'];
$rResult = $result['rResult'];

foreach ($rResult as $aRow) {
    $row = [];
    
    // Add checkbox column
    $row[] = $aRow['id'];
    
    // Topic ID with expand/collapse button
    $row[] = '<div class="topic-id-container tw-w-[200px]">
                <div class="topic-id-content tw-flex tw-items-center">
                    <a href="#" class="toggle-subtable tw-mr-2 tw-flex tw-items-center" data-topicid="'.$aRow['id'].'">
                        <i class="fa fa-plus-square fa-lg tw-text-neutral-500 hover:tw-text-neutral-700"></i>
                    </a>
                    <div class="tw-flex-1">
                        <span class="topic-id-text" style="display: none;" onclick="copyToClipboard(\''.html_escape($aRow['topicid']).'\')">'.html_escape($aRow['topicid']).'</span>
                        <a href="#" class="toggle-topic-id btn btn-link" data-topic-id="'.html_escape($aRow['topicid']).'">
                            <i class="fa fa-eye"></i> '._l('show_topic_id').'
                        </a>
                    </div>
                    <button type="button" class="btn-copy" onclick="copyToClipboard(\''.html_escape($aRow['topicid']).'\')">
                        <i class="fa fa-copy"></i>
                    </button>
                </div>
              </div>';
    
    $row[] = '<div class="tw-max-w-[300px] tw-truncate" data-toggle="tooltip" title="'.html_escape($aRow['topictitle']).'">
              <a href="'.admin_url('topics/detail/'.$aRow['latest_topic_id']).'" class="tw-text-neutral-500 hover:tw-text-neutral-700" target="_blank">
                  '.html_escape($aRow['topictitle']).'
              </a>
              </div>';
    
    // Status column
    $status = $aRow['status'] ? 
        '<span class="label label-success">'._l('active').'</span>' : 
        '<span class="label label-danger">'._l('inactive').'</span>';
    $row[] = $status;
    
    // Created date
    $row[] = _dt($aRow['datecreated']);
    
    // Options
    $options = '<div class="tw-flex tw-items-center tw-space-x-3">';
    if (has_permission('topics', '', 'view')) {
        $options .= '<a href="'.admin_url('topics/detail/'.$aRow['latest_topic_id']).'" class="tw-text-neutral-500 hover:tw-text-neutral-700">
                        <i class="fa fa-eye fa-lg"></i>
                    </a>';
    }
    if (has_permission('topics', '', 'edit')) {
        $options .= '<a href="'.admin_url('topics/edit/'.$aRow['id']).'" class="tw-text-neutral-500 hover:tw-text-neutral-700">
                        <i class="fa fa-pen-to-square fa-lg"></i>
                    </a>';
    }
    if (has_permission('topics', '', 'delete')) {
        $options .= '<a href="'.admin_url('topics/delete/'.$aRow['id']).'" class="tw-text-neutral-500 hover:tw-text-neutral-700 _delete">
                        <i class="fa fa-trash-can fa-lg"></i>
                    </a>';
    }
    $options .= '</div>';
    $row[] = $options;

    $output['aaData'][] = $row;
}

echo json_encode($output);
die();
?>