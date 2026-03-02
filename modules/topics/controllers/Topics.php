<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Topics extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Topics_model');
        $this->load->model('Topic_master_model');
    }

    public function execute_workflow()
    {
        try {
            if (!$this->input->post()) {
                throw new Exception('Invalid request method');
            }

            $workflow_data = $this->input->post();
            
            if (empty($workflow_data['workflow_id'])) {
                throw new Exception('Missing workflow ID');
            }

            // Get WordPress Post ID from history if not provided
            if (!isset($workflow_data['wordpress_post_id']) && isset($workflow_data['topic_id'])) {
                $wordpress_post_id = get_wordpress_post_id($workflow_data['topic_id']);
                if ($wordpress_post_id) {
                    $workflow_data['wordpress_post_id'] = $wordpress_post_id;
                }
            }
            

            // Prepare action data with all necessary parameters
            $action_data = [
                'action_type' => $workflow_data['target_type'],
                'workflow_id' => $workflow_data['workflow_id'],
                'target_type' => $workflow_data['target_type'],
                'target_state' => $workflow_data['target_state'],
                'from_selection' => $workflow_data['from_selection'] ?? false,
                'selected_option' => $workflow_data['selected_option'] ?? null,
                'audit_type' => $workflow_data['audit_type'] ?? null,
                'audit_step' => $workflow_data['audit_step'] ?? null,
                'selected_page_id' => $workflow_data['selected_page_id'] ?? null,
                'action_command' => $workflow_data['action_command'] ?? null,
                'selected_page_internal_id' => $workflow_data['selected_page_internal_id'] ?? null,
                'post_type' => $workflow_data['post_type'] ?? null,
                'wordpress_post_id' => $workflow_data['wordpress_post_id'] ?? null,
                'wordpress_post_url' => get_wordpress_post_url($workflow_data['topic_id']) ?? null
            ];

            // Pass through any controller_* fields
            foreach ($workflow_data as $key => $value) {
                if (strpos($key, 'controller_') === 0) {
                    $action_data[$key] = $value;
                }

                if (strpos($key, 'changes_data') === 0) {
                    $action_data[$key] = $value;
                }
            }

            // Log workflow data lớn với log_message
            log_message('error', 'Execute workflow with data: ' . json_encode($workflow_data));
            
            $result = process_topic_action(
                $workflow_data['topic_id'],
                $action_data
            );
            
            // Log result lớn
            log_message('error', 'Workflow execution result: ' . json_encode($result));
            
            echo json_encode($result);

        } catch (Exception $e) {
            // Log error
            log_message('error', 'Workflow execution error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
                  
            // Return detailed error response
            $error_response = [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                    'code' => $e->getCode()
                ]
            ];
            
            // Set proper error header
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode($error_response);
        }
        die();
    }

    public function index()
    {
        if (!has_permission('topics', '', 'view')) {
            access_denied('topics');
        }

        // Load data cho cả Topic Master và Topics
        $data['title'] = _l('topics');
        $data['total_topics'] = $this->Topic_master_model->count_all();
        $data['active_topics'] = $this->Topic_master_model->count_active();
        
        // Đếm topics theo trạng thái cuối cùng cho writing
        $data['writing_topics'] = $this->Topic_master_model->count_by_latest_action_type('ExecutionTag_ExecWriting');
        
        // Đếm topics có liên quan đến social audit trong lịch sử
        $data['social_audit_topics'] = $this->Topic_master_model->count_by_action_type_history('ExecutionTag_SocialAudit');
        
        // Đếm topics có liên quan đến social media trong lịch sử
        $data['scheduled_social_topics'] = $this->Topic_master_model->count_by_action_type_history('ExecutionTag_ScheduledSocial');
        
        // Đếm topics theo trạng thái cuối cùng cho post audit gallery
        $data['post_audit_gallery_topics'] = $this->Topic_master_model->count_by_latest_action_type('ExecutionTag_PostAuditGallery');

        // Đếm fail topics
        $data['fail_topics'] = $this->Topic_master_model->count_failed();

        $this->load->view('topics/index', $data);
    }

    // Xử lý table cho Topic Master
    public function topic_master_table() 
    {
        if (!has_permission('topics', '', 'view')) {
            ajax_access_denied();
        }

        $this->app->get_table_data(module_views_path('topics', 'includes/topic_master_table'));
    }

    public function detail($id, $is_from_master = 0)
    {
        
        if (!has_permission('topics', '', 'view')) {
            access_denied('topics');
        }
       
        if($is_from_master == 1 || $this->input->get('is_from_master') == 1) {
            $topic = $this->Topic_master_model->get_topic_from_master($id);
        } else {
            $topic = $this->Topics_model->get_topic($id);
        }
        if (!$topic) {
            show_404();
        }
        
        // Lấy toàn bộ lịch sử của topic này
        $history = $this->Topics_model->get_topic_history($topic->topicid);
        
        // Format lại dữ liệu lịch sử giống như trong AJAX
        $formatted_history = array_map(function($item) {
            // log_message('error', 'Item: ' . json_encode($item));
            $workflow_url = '';
            $execution_url = '';
            if (!empty($item->workflow_id)) {
                $workflow_url = get_n8n_workflow_url($item->workflow_id);
            }
            if (!empty($item->execution_id)) {
                $execution_url = get_n8n_execution_url($item->execution_id, $item->workflow_id);
            }
            
            $execution_html = '';
            if (!empty($workflow_url)) {
                $execution_html .= '<div class="btn-group btn-group-xs">';
                if ($execution_url) {
                    $execution_html .= '<a href="'.$execution_url.'" target="_blank" class="btn btn-info">'.
                        '<i class="fa fa-external-link"></i> '._l('view_execution').
                    '</a>';
                }
                $execution_html .= '<a href="'.$workflow_url.'" target="_blank" class="btn btn-default">'.
                    '<i class="fa fa-sitemap"></i> '._l('view_workflow').
                '</a>';
                $execution_html .= '</div>';
            }

            return [
                'id' => $item->id,
                'topicid' => $item->topicid,
                'action_type_name' => $item->action_type_name,
                'action_state_name' => $item->action_state_name,
                'action_type_code' => $item->action_type_code,
                'action_state_code' => $item->action_state_code,
                'state_color' => $item->state_color,
                'dateupdated' => $item->dateupdated,
                'valid_data' => $item->valid_data,
                'execution_html' => $execution_html,    
                'workflow_url' => $workflow_url,
                'execution_url' => $execution_url,
            ];
        }, $history);
        
        $data = [
            'title' => _l('topic_detail'),
            'topic' => $topic,
            'topic_history' => $formatted_history,
            'topic_steps' => $this->Topics_model->get_topic_steps($topic->topicid)
        ];
        
        // Load Topic online status model
        $this->load->model('Topic_online_status_model');
        
        // Update online status
        $this->Topic_online_status_model->update_staff_online_status(
            get_staff_user_id(), 
            $id
        );
        
        // Get online staff
        $data['online_staff'] = $this->Topic_online_status_model->get_online_staff_for_topic($id);
        
        // Add this code to load action buttons
        $this->load->model('Topic_action_button_model');
        $action_buttons = $this->Topic_action_button_model->get_non_controller_only_buttons_for_topic($id);
        
        // Debug log để kiểm tra action_command
        log_message('error', 'Action buttons data: ' . json_encode($action_buttons));
        
        $data['action_buttons'] = $action_buttons;
        
        $this->load->view('topics/detail', $data);
    }

    public function create()
    {
        if (!has_permission('topics', '', 'create')) {
            access_denied('topics');
        }

        $this->load->model('Topics_model');
        $this->load->model('Topic_master_model');
        $this->load->model('Action_type_model');
        $this->load->model('Action_state_model');
        $this->load->model('Topic_controller_model');

        if ($this->input->post()) {
            $data = $this->input->post();
            
            // Start transaction
            $this->db->trans_start();
            
            try {
                // Generate a unique topic ID if not provided
                if (empty($data['topicid'])) {
                    $existing_ids = $this->Topics_model->get_all_topic_ids();
                    $data['topicid'] = $this->generate_unique_topic_id($existing_ids);
                }
                
                // Create a new topic master record
                $topic_master_data = [
                    'topicid' => $data['topicid'],
                    'topictitle' => $data['topictitle'],
                    'status' => isset($data['status']) ? 1 : 0
                ];
                
                // Add controller_id to topic_master if provided
                if (!empty($data['controller_id'])) {
                    $topic_master_data['controller_id'] = $data['controller_id'];
                }
                
                $topic_master_id = $this->Topic_master_model->add($topic_master_data);
                
                // Create a new topic record
                $topic_data = [
                    'topicid' => $data['topicid'],
                    'topictitle' => $data['topictitle'],
                    'action_type_code' => $data['action_type_code'],
                    'action_state_code' => $data['action_state_code'],
                    'log' => $data['log'],
                    'status' => isset($data['status']) ? 1 : 0
                ];
                
                $topic_id = $this->Topics_model->add_topic($topic_data);
                
                // If controller is specified, create relationship in topic_controller table
                if (!empty($data['controller_id'])) {
                    $this->Topic_controller_model->add_topic_to_controller(
                        $data['controller_id'],
                        $data['topicid'],
                        get_staff_user_id()
                    );
                }
                
                // Complete transaction
                $this->db->trans_complete();
                
                if ($this->db->trans_status() === FALSE) {
                    // Transaction failed
                    set_alert('danger', _l('problem_creating_topic'));
                } else {
                    // Success
                    set_alert('success', _l('new_topic_added_successfully'));
                    
                    // Log activity
                    log_activity('New Topic Created [ID: ' . $topic_id . ', Master ID: ' . $topic_master_id . ']');
                    
                    // Redirect to topic detail page
                    redirect(admin_url('topics/detail/' . $topic_master_id . '/1')); // 1 indicates coming from master
                }
            } catch (Exception $e) {
                // Handle any exceptions
                $this->db->trans_rollback();
                set_alert('danger', _l('problem_creating_topic') . ': ' . $e->getMessage());
                log_activity('Topic Creation Failed: ' . $e->getMessage());
            }
        }

        // Load available controllers for dropdown
        $data['controllers'] = $this->Topic_controller_model->get_all_controllers();
        
        // Load action types and states for dropdowns
        $data['existing_ids'] = $this->Topics_model->get_all_topic_ids();
        $data['action_types'] = $this->Action_type_model->get_all_action_types();
        $data['action_states'] = $this->Action_state_model->get_all_action_states();
        
        $data['title'] = _l('add_topic');
        $this->load->view('create', $data);
    }
    
    private function generate_unique_topic_id($existing_ids)
    {
        $new_id = '';
        do {
            $new_id = 'TOPIC-' . strtoupper(bin2hex(random_bytes(4)));
        } while (in_array($new_id, $existing_ids));
        return $new_id;
    }
    

    public function edit($id)
    {
        if (!has_permission('topics', '', 'edit')) {
            access_denied('topics');
        }

        $this->load->model('Topics_model');
        $this->load->model('Action_type_model');
        $this->load->model('Action_state_model');

        $data['topic'] = $this->Topics_model->get_topic($id);
        if (!$data['topic']) {
            show_404();
        }

        // Load action types and states for dropdowns
        $data['action_types'] = $this->Action_type_model->get_all_action_types();
        $data['action_states'] = $this->Action_state_model->get_all_action_states();

        if ($this->input->post()) {
            $update_data = $this->input->post();
            $this->Topics_model->update_topic($id, $update_data);
            set_alert('success', _l('updated_successfully'));
            redirect(admin_url('topics'));
        }

        $data['title'] = _l('edit_topic');
        $this->load->view('topics/edit', $data);
    }

    public function delete($id)
    {
        $this->Topics_model->delete_topic($id);
        redirect(admin_url('topics'));
    }

    // Thêm phương thức table để xử lý AJAX
    public function table()
    {
        if (!has_permission('topics', '', 'view')) {
            ajax_access_denied();
        }

        $filters = $this->input->post('filters');
        $search_mode = $this->input->post('search_mode');

        $this->app->get_table_data(module_views_path('topics', 'topics_table'), [
            'filters' => $filters,
            'search_mode' => $search_mode
        ]);
    }

    public function get_topic_history_ajax()
    {
        if (!has_permission('topics', '', 'view')) {
            ajax_access_denied();
        }
        
        $topicid = $this->input->post('topicid');
        $action_type = $this->input->post('action_type');
        
        if (!$topicid) {
            echo json_encode(['success' => false, 'message' => 'Topic ID is required']);
            die();
        }
        
        $history = $this->Topics_model->get_topic_history($topicid, $action_type);
        
        // Format lại dữ liệu để khớp với cấu trúc bảng trong view
        $formatted_history = array_map(function($item) {
            $data = [
                'action_type_name' => $item->action_type_name,
                'action_type_code' => $item->action_type_code,
                'action_state_name' => $item->action_state_name,
                'action_state_code' => $item->action_state_code,
                'state_color' => $item->state_color,
                'log' => $item->log,
                'dateupdated' => $item->dateupdated,
                'topicid' => $item->topicid,
                'id' => $item->id,
                'valid_data' => $item->valid_data,
            ];

            // Thêm URLs nếu có workflow_id và execution_id
            $workflow_url = '';
            $execution_url = '';
            if (!empty($item->workflow_id)) {
                $workflow_url = get_n8n_workflow_url($item->workflow_id);
            }
            if (!empty($item->execution_id)) {
                $execution_url = get_n8n_execution_url($item->execution_id, $item->workflow_id);
            }

            $execution_html = '';
            if (!empty($workflow_url)) {
                $execution_html .= '<div class="btn-group btn-group-xs">';
                if ($execution_url) {
                    $execution_html .= '<a href="'.$execution_url.'" target="_blank" class="btn btn-info">'.
                        '<i class="fa fa-external-link"></i> '._l('view_execution').
                    '</a>';
                }
                $execution_html .= '<a href="'.$workflow_url.'" target="_blank" class="btn btn-default">'.
                    '<i class="fa fa-sitemap"></i> '._l('view_workflow').
                '</a>';
                $execution_html .= '</div>';
            }

            $data['workflow_url'] = $workflow_url;
            $data['execution_url'] = $execution_url;
            $data['execution_html'] = $execution_html;

            return $data;
        }, $history);

        echo json_encode([
            'success' => true,
            'data' => $formatted_history
        ]);
        die();
    }

    public function get_log_data()
    {
        if (!has_permission('topics', '', 'view')) {
            ajax_access_denied();
        }
        
        $topicid = $this->input->post('topicid');
        $id = $this->input->post('id');
        
        if (!$topicid || !$id) {
            echo json_encode(['success' => false, 'message' => 'Required parameters missing']);
            die();
        }
        
        $log_data = $this->Topics_model->get_log_data($id, $topicid);
        if ($log_data) {
            echo json_encode(['success' => true, 'data' => $log_data]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Log data not found']);
        }
        die();
    }

    public function dashboard()
    {
        if (!has_permission('topics', '', 'view')) {
            access_denied('topics');
        }
        
        $this->load->model('Topics_model');
        
        $data['title'] = _l('topics_dashboard');

        // Cập nhật cách đếm theo Topic Master
        $data['total_topics'] = $this->Topic_master_model->count_all(); // Tổng số Topic Master
        $data['active_topics'] = $this->Topic_master_model->count_active(); // Tổng số Topic Master đang active

        // Đếm topics theo trạng thái của topic mới nhất
        $data['writing_topics'] = $this->Topic_master_model->count_by_latest_action_type('ExecutionTag_ExecWriting');
        $data['social_audit_topics'] = $this->Topic_master_model->count_by_latest_action_type('ExecutionTag_SocialAudit');
        $data['scheduled_social_topics'] = $this->Topic_master_model->count_by_latest_action_type('ExecutionTag_ScheduledSocial');
        $data['post_audit_gallery_topics'] = $this->Topic_master_model->count_by_latest_action_type('ExecutionTag_PostAuditGallery');

        $this->load->view('topics/dashboard', $data);
    }

    public function search()
    {
        if (!has_permission('topics', '', 'view')) {
            ajax_access_denied();
        }

        $q = $this->input->get('q');
        $limit = $this->input->get('limit') ? $this->input->get('limit') : 20;
        
        if (empty($q)) {
            echo json_encode([]);
            die();
        }
        
        $results = $this->Topics_model->search_topics($q, $limit);
        
        // Format results for select picker
        foreach ($results as &$result) {
            // Ensure we have either title or ID for display
            if (empty($result['topictitle'])) {
                $result['topictitle'] = $result['topicid'];
            }
            
            // Clean the data
            $result['topictitle'] = html_escape($result['topictitle']);
            $result['topicid'] = html_escape($result['topicid']);
        }
        
        echo json_encode($results);
        die();
    }

    public function filter($state = '')
    {
        if (!has_permission('topics', '', 'view')) {
            access_denied('topics');
        }

        $data['title'] = _l('topics_list');
        
        // Filter topics based on state if provided
        if (!empty($state)) {
            $data['topics'] = $this->Topics_model->get_topics_by_state($state);
        } else {
            $data['topics'] = $this->Topics_model->get_all_topics();
        }

        $this->load->view('topics/index', $data);
    }

    public function get_table_data() 
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        $this->load->model('Topics_model');

        $filters = $this->input->post('filters') ?? [];
        $start = $this->input->post('start') ? $this->input->post('start') : 0;
        $length = $this->input->post('length') ? $this->input->post('length') : 25;
        $draw = $this->input->post('draw');
        $search = $this->input->post('search');
        $order = $this->input->post('order');
        $columns = $this->input->post('columns');

        // Handle quick filters
        $quick_filter = $this->input->post('quick_filter');
        if ($quick_filter) {
            switch ($quick_filter) {
                case 'writing':
                    $filters['action_type'] = 'ExecutionTag_ExecWriting';
                    break;
                case 'social_audit':
                    $filters['action_type'] = 'ExecutionTag_SocialAudit';
                    break;
                case 'scheduled_social':
                    $filters['action_type'] = 'ExecutionTag_ScheduledSocial';
                    break;
                case 'post_audit_gallery':
                    $filters['action_type'] = 'ExecutionTag_PostAuditGallery';
                    break;
                case 'fail':
                    $filters['action_state_code'] = 'fail'; // Thêm điều kiện cho 'fail'
                    break;
                case 'active':
                    $filters['is_active'] = 1;
                    break;
            }
        }

        // Get data from model with filters and pagination
        $result = $this->Topics_model->get_filtered_topics(
            $filters,
            $start,
            $length,
            $search['value'] ?? '',
            $order ?? [],
            $columns ?? []
        );
        
        // Format data for DataTables
        $data = [];
        if (!empty($result['data'])) {
            foreach ($result['data'] as $row) {
                $data[] = [
                    $row['topicid'],
                    '<div class="tw-max-w-[300px] tw-truncate" data-toggle="tooltip" title="'.html_escape($row['topictitle']).'">'.
                        '<a href="'.admin_url('topics/detail/'.$row['id']).'">'.html_escape($row['topictitle']).'</a>'.
                    '</div>',
                    '<span class="label label-info">'.html_escape($row['action_type_name']).'</span>',
                    '<span class="label" style="background-color: '.html_escape($row['state_color']).'">'.
                        html_escape($row['action_state_name']).
                    '</span>',
                    _dt($row['dateupdated']),
                    '<div class="tw-flex tw-items-center tw-space-x-3">'.
                        '<a href="'.admin_url('topics/detail/'.$row['id']).'" class="tw-text-neutral-500 hover:tw-text-neutral-700">'.
                            '<i class="fa fa-eye fa-lg"></i>'.
                        '</a>'.
                    '</div>'
                ];
            }
        }

        $response = [
            'draw' => intval($draw),
            'recordsTotal' => intval($result['recordsTotal']),
            'recordsFiltered' => intval($result['recordsFiltered']),
            'data' => $data
        ];

        header('Content-Type: application/json');
        echo json_encode($response);
        die();
    }

    public function dashboard_table()
    {
        if (!has_permission('topics', '', 'view')) {
            ajax_access_denied();
        }

        $this->load->model('Topics_model');
        
        // Sa đường dẫn đến file view
        $this->app->get_table_data(module_views_path('topics', 'includes/dashboard_table'), [
            'model' => $this->Topics_model
        ]);
    }

    public function toggle_active($id) {
        if (!has_permission('topics', '', 'edit')) {
            ajax_access_denied();
        }
        
        $success = $this->Topics_model->toggle_active($id);
        
        echo json_encode([
            'success' => $success
        ]);
    }

    public function bulk_action()
    {
        if (!has_permission('topics', '', 'edit')) {
            ajax_access_denied();
        }

        $response = [
            'success' => false,
            'message' => ''
        ];

        $action = $this->input->post('action');
        $ids = $this->input->post('ids');

        // Log bulk action attempt
        log_activity('Bulk action initiated - Action: ' . $action . ' on Topics: ' . json_encode($ids));

        // Validate action
        if (!in_array($action, ['activate', 'deactivate', 'activate_all', 'deactivate_all'])) {
            $response['message'] = _l('invalid_action');
            echo json_encode($response);
            die();
        }

        // Load required models
        $this->load->model('Topic_master_model');
        $this->load->model('Topics_model');

        // Handle all actions
        if ($action === 'activate_all' || $action === 'deactivate_all') {
            $status = ($action === 'activate_all') ? 1 : 0;
            $success = $this->Topic_master_model->update_all_status($status);
            
            $response['success'] = $success;
            $response['message'] = $success ? 
                _l($action === 'activate_all' ? 'all_topics_activated' : 'all_topics_deactivated') : 
                _l('bulk_action_failed');

            echo json_encode($response);
            die();
        }

        // Handle individual selections
        if (empty($ids)) {
            $response['message'] = _l('no_items_selected');
            echo json_encode($response);
            die();
        }

        // Ensure ids is array
        if (!is_array($ids)) {
            $ids = [$ids];
        }

        $this->db->trans_start();

        try {
            $status = ($action === 'activate') ? 1 : 0;
            
            // Log attempt to update status
            log_activity('Attempting bulk update - Status: ' . $status . ' for Topics: ' . json_encode($ids));
            
            $success = $this->Topic_master_model->bulk_update_status($ids, $status);

            if ($success) {
                $this->db->trans_commit();
                $response['success'] = true;
                $response['message'] = _l($action === 'activate' ? 
                    'selected_topics_activated' : 'selected_topics_deactivated');

                // Log successful action
                log_activity($action === 'activate' ? 
                    'Topics activated: ' . implode(', ', $ids) : 
                    'Topics deactivated: ' . implode(', ', $ids));
            } else {
                $this->db->trans_rollback();
                $response['message'] = _l('bulk_action_failed');
                // Log failure
                log_activity('Bulk action failed for Topics: ' . json_encode($ids));
            }

        } catch (Exception $e) {
            $this->db->trans_rollback();
            // Log error
            log_activity('Bulk action error for Topics: ' . $e->getMessage());
            $response['message'] = $e->getMessage();
        }

        echo json_encode($response);
    }

    public function process_data($id, $action_type_code) {
        if (!has_permission('topics', '', 'edit')) {
            access_denied('topics');
        }

        $topic = $this->Topics_model->get_topic($id);
        if (!$topic) {
            show_404();
        }

        // Load các model cần thiết
        $this->load->model('Topic_target_model');
        
        $data['topic'] = $topic;
        $data['Topics_model'] = $this->Topics_model;
        $data['processor'] = $this->get_data_processor($action_type_code);
        
        $data['title'] = _l('process_data');
        
        // Thêm điều kiện lọc theo topicid
        $where = [
            't.topicid' => $topic->topicid  // Lọc theo topicid của topic hiện tại
        ];
        $topic_items = $this->Topic_target_model->get_table_data([], $where, $action_type_code);
        $data['topic_items'] = $topic_items;

        if ($this->input->post()) {
            $processed_data = $this->input->post();
            $success = $this->processor->process($id, $processed_data);
            
            if ($success) {
                set_alert('success', _l('data_processed_successfully'));
            } else {
                set_alert('danger', _l('data_processing_failed'));
            }
            redirect(admin_url('topics/detail/' . $id));
        }

        $this->load->view('topics/includes/process_data', $data);
    }

    private function get_data_processor($action_type_code) 
    {
        $this->load->helper('topics_data_processor');
        
        switch ($action_type_code) {
            case 'BuildPostStructure':
                return new BuildPostStructureProcessor();
            // Add more processors as needed
            default:
                return null;
        }
    }

    public function reset_process_data($topic_id) 
    {
        // Kiểm tra quyền truy cập
        if (!has_permission('topics', '', 'edit')) {
            ajax_access_denied();
        }

        $success = false;
        $message = '';
        
        // Lấy topic hiện tại
        $current_topic = $this->Topics_model->get_topic($topic_id);
        if (!$current_topic) {
            echo json_encode([
                'success' => false,
                'message' => 'Topic not found'
            ]);
            return;
        }

        // Tạo bản backup từ topic hiện tại
        $backup_topic = (array)$current_topic;
        unset($backup_topic['id']); // Bỏ ID để tạo bản ghi mới
        
        // Set các thng tin backup
        $backup_topic['status'] = 0; // Set status = 0 cho bản backup
        $backup_topic['target_id'] = 4; // Backup target ID
        $backup_topic['log'] = json_encode([
            'backup_date' => date('Y-m-d H:i:s'),
            'backup_reason' => 'reset_process',
            'backup_from_id' => $topic_id
        ]);
        
        // Thêm bản backup vào database
        $backup_id = $this->Topics_model->add_topic($backup_topic);
        
        if ($backup_id) {
            $success = true;
            $message = _l('process_data_reset_success');
            
            // Log hoạt động
            log_activity('Topic backup created before reset. Topic ID: ' . $topic_id . ', Backup ID: ' . $backup_id);
        }

        echo json_encode([
            'success' => $success,
            'message' => $message
        ]);
    }

    public function quick_save_item() {
        if (!has_permission('topics', '', 'edit')) {
            ajax_access_denied();
        }

        try {
            $topic_id = $this->input->post('topic_id');
            $position = $this->input->post('position');
            
            if (!$topic_id) {
                throw new Exception(_l('topic_id_required'));
            }

            $data = json_decode($this->input->post('data'), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception(_l('invalid_json_data'));
            }

            // Log quick save attempt
            log_activity('Quick save initiated for Topic [ID: ' . $topic_id . ']');

            // Tạo backup trước khi update
            $backup_topic = $this->Topics_model->get_topic($topic_id);
            if (!$backup_topic) {
                throw new Exception(_l('topic_not_found'));
            }

            // Chuyển đổi object thành array và chun bị dữ liệu backup
            $backup_data = [
                'topicid' => $backup_topic->topicid,
                'topictitle' => $backup_topic->topictitle,
                'position' => $backup_topic->position,
                'data' => $backup_topic->data,
                'action_type_code' => $backup_topic->action_type_code,
                'action_state_code' => $backup_topic->action_state_code,
                'target_id' => 4, // Backup target ID
                'status' => 0, // Backup status
                'log' => json_encode([
                    'backup_date' => date('Y-m-d H:i:s'),
                    'backup_reason' => 'quick_save',
                    'backup_from_id' => $topic_id
                ])
            ];
            
            $this->db->trans_start();
            
            // Insert backup record
            $this->db->insert(db_prefix() . 'topics', $backup_data);
            
           
            $update_data = [
                'data' => json_encode($data),
                'dateupdated' => date('Y-m-d H:i:s')
            ];

             // Update topic với dữ liu mới
            if ($position) {
                $update_data['position'] = $position;
            }
            
            $this->db->where('id', $topic_id);
            $this->db->update(db_prefix() . 'topics', $update_data);
            
            $this->db->trans_complete();
            
            if ($this->db->trans_status() === FALSE) {
                throw new Exception(_l('database_transaction_failed'));
            }

            // Log successful save
            log_activity('Topic quick saved successfully [ID: ' . $topic_id . ']');

            echo json_encode([
                'success' => true,
                'message' => _l('item_saved_successfully') . ' topicid: ' . $topic_id
            ]);

        } catch (Exception $e) {
            // Log error
            log_activity('Topic quick save failed [ID: ' . $topic_id . '] - Error: ' . $e->getMessage());
            
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function update_positions() {
        if (!has_permission('topics', '', 'edit')) {
            ajax_access_denied();
        }

        $positions = $this->input->post('positions');
        $success = true;
        $message = '';

        if (!empty($positions)) {
            $this->db->trans_start();

            try {
                foreach ($positions as $item) {
                    $this->db->where('id', $item['topic_id']);
                    $this->db->update(db_prefix() . 'topics', ['position' => $item['position']]);
                }

                $this->db->trans_complete();

                if ($this->db->trans_status() === FALSE) {
                    throw new Exception(_l('error_saving_positions'));
                }

                $message = _l('positions_saved');
            } catch (Exception $e) {
                $this->db->trans_rollback();
                $success = false;
                $message = $e->getMessage();
            }
        }

        echo json_encode([
            'success' => $success,
            'message' => $message
        ]);
    }

    public function get_processed_data() {
        if (!has_permission('topics', '', 'view')) {
            ajax_access_denied();
        }

        $topicid = $this->input->post('topicid');
        $id = $this->input->post('id');
        
        $topic = $this->Topics_model->get_topic_by_id($id);
        
        if (!$topic) {
            log_activity('Failed to retrieve topic data [ID: ' . $id . ']');
            echo '<div class="alert alert-danger">' . _l('topic_not_found') . '</div>';
            return;
        }

        try {
            // Load JSON helper
            if (!function_exists('repair_json')) {
                $this->load->helper('json_fixer');
            }
            
            // Kiểm tra và lấy dữ liệu từ data hoặc log
            $raw_data = !empty($topic->data) ? $topic->data : $topic->log;
            
            // Parse JSON data
            $data = json_decode(repair_json($raw_data), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Failed to parse topic data: ' . json_last_error_msg());
            }

            // Log successful data retrieval
            log_activity('Processing topic data [ID: ' . $id . 
                        ', Type: ' . $topic->target_type . 
                        ', Action: ' . $topic->action_type_code . 
                        ', State: ' . $topic->action_state_code . 
                        ', Using: ' . (!empty($topic->data) ? 'data' : 'log') . ']');
            
            // Create appropriate processor
            $processor = TopicDisplayProcessorFactory::create(
                $topic->target_type,
                $topic->action_type_code,
                $topic->action_state_code
            );

            // Display processed data
            echo $processor->display($topic, $data);

        } catch (Exception $e) {
            log_activity('Error processing topic data [ID: ' . $id . ']: ' . $e->getMessage());
            echo '<div class="alert alert-danger">' . 
                 _l('error_processing_topic_data') . ': ' . $e->getMessage() . 
                 '</div>';
            
            // Debug info in non-production environment
            if (ENVIRONMENT !== 'production') {
                echo '<div class="alert alert-info">Debug Info:<br>';
                echo 'Data: ' . htmlspecialchars($topic->data) . '<br>';
                echo 'Log: ' . htmlspecialchars($topic->log) . '</div>';
            }
        }
    }

    public function update_online_status()
    {
        $topicid = $this->input->post('topicid');
        if (!$topicid) {
            return;
        }
        
        // Load Topic online status model
        $this->load->model('Topic_online_status_model');
        
        // Update status cho staff hiện tại
        $this->Topic_online_status_model->update_staff_online_status(
            get_staff_user_id(), 
            $topicid
        );
        
        // Lấy danh sách staff online trong 15 phút gần đây
        $online_staff = $this->Topic_online_status_model->get_online_staff_for_topic(
            $topicid,
            15 * 60 // 15 phút * 60 giây
        );
        
        echo json_encode([
            'success' => true, 
            'online_staff' => $online_staff
        ]);
    }

    public function remove_online_status()
    {
        $topicid = $this->input->post('topicid');
        if (!$topicid) {
            return;
        }
        
        // Load Topic online status model
        $this->load->model('Topic_online_status_model');
        
        // Remove status của staff hiện tại
        $this->Topic_online_status_model->remove_staff_online_status(
            get_staff_user_id(), 
            $topicid
        );
        
        // Lấy danh sách staff còn online trong 15 phút gần đây
        $online_staff = $this->Topic_online_status_model->get_online_staff_for_topic(
            $topicid,
            15 * 60 // 15 phút * 60 giây
        );
        
        echo json_encode([
            'success' => true,
            'online_staff' => $online_staff
        ]);
    }

    public function get_execution_details($execution_id)
    {
        if (!has_permission('topics', '', 'view')) {
            ajax_access_denied();
        }

        // Join các bảng để lấy thông tin đầy đủ
        $this->db->select([
            'tal.*',  // automation logs
            't.topictitle',
            't.action_type_code',
            't.action_state_code',
            'at.name as action_type_name',
            'ast.name as action_state_name',
            'ast.color as state_color'
        ]);
        
        $this->db->from(db_prefix() . 'topic_automation_logs tal');
        $this->db->join(
            db_prefix() . 'topics t', 
            't.topicid = tal.topic_id AND t.automation_id = tal.automation_id',
            'left'
        );
        $this->db->join(
            db_prefix() . 'topic_action_types at',
            'at.action_type_code = t.action_type_code',
            'left'
        );
        $this->db->join(
            db_prefix() . 'topic_action_states ast',
            'ast.action_state_code = t.action_state_code AND ast.action_type_code = t.action_type_code',
            'left'
        );
        
        $this->db->where('tal.workflow_id', $execution_id);
        // Hoặc nếu execution_id là một trường riêng:
        // $this->db->where('tal.execution_id', $execution_id);
        
        $execution = $this->db->get()->row();

        if ($execution) {
            // Parse response_data nếu là JSON
            if (!empty($execution->response_data)) {
                try {
                    $execution->response_data = json_decode($execution->response_data);
                } catch (Exception $e) {
                    // Giữ nguyên dạng string nếu không parse được JSON
                }
            }
            
            echo json_encode([
                'success' => true,
                'data' => $execution
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Execution not found'
            ]);
        }
    }

 

    public function settings()
    {
        if (!has_permission('settings', '', 'view')) {
            access_denied('settings');
        }

        if ($this->input->post()) {
            if (!has_permission('settings', '', 'edit')) {
                access_denied('settings');
            }
            
            $success = $this->save_settings();
            if ($success) {
                set_alert('success', _l('settings_updated'));
            }
        }

        $this->load->model('Topic_action_button_model');
        $data['action_buttons'] = $this->Topic_action_button_model->get();
        $this->load->model(['Action_type_model', 'Action_state_model']); 
        $data['action_types'] = $this->Action_type_model->get_all_action_types();
        $data['action_states'] = $this->Action_state_model->get_all_action_states();
        
        $data['title'] = _l('topics_settings');
        $this->load->view('topics/settings/index', $data);
    }

    public function save_action_buttons()
    {
        if (!has_permission('settings', '', 'edit')) {
            ajax_access_denied();
        }

        $buttons = $this->input->post('buttons');
        $success = $this->Topic_action_button_model->save_buttons($buttons);

        echo json_encode([
            'success' => $success,
            'message' => $success ? _l('settings_updated') : _l('failed_to_update_settings')
        ]);
    }

    public function action_buttons()
    {
        if (!has_permission('topics', '', 'view')) {
            access_denied('topics');
        }

        if ($this->input->is_ajax_request()) {
            $this->load->model('Topic_action_button_model');
            $action_buttons = $this->Topic_action_button_model->get();

            $data = [];
            foreach ($action_buttons as $button) {
                $row = [];
                
                // Name
                $row[] = '<a href="#" onclick="edit_action_button(' . $button['id'] . '); return false;" class="tw-text-neutral-500 hover:tw-text-neutral-700 focus:tw-text-neutral-700">' . html_escape($button['name']) . '</a>';
                
                // Button Type
                $row[] = '<span class="label label-' . html_escape($button['button_type']) . '">' . 
                         ucfirst(html_escape($button['button_type'])) . '</span>';
                
                // Workflow ID
                $row[] = '<span class="text-nowrap">' . html_escape($button['workflow_id']) . '</span>';
                
                // Trigger Type
                $row[] = '<span class="label label-info">' . 
                         ucfirst(html_escape($button['trigger_type'])) . '</span>';
                
                // Target Action Type
                $row[] = '<span class="text-nowrap">' . 
                         ($button['target_action_type'] ? html_escape($button['target_action_type']) : '-') . '</span>';
                
                // Target Action State
                $row[] = '<span class="text-nowrap">' . 
                         ($button['target_action_state'] ? html_escape($button['target_action_state']) : '-') . '</span>';
                
                // Description
                $row[] = '<span class="text-wrap">' . html_escape($button['description']) . '</span>';
                
                // Status toggle switch
                $row[] = '<div class="onoffswitch">
                            <input type="checkbox" data-switch-url="' . admin_url('topics/change_button_status') . '" 
                                   name="onoffswitch" class="onoffswitch-checkbox" 
                                   id="status_' . $button['id'] . '" 
                                   data-id="' . $button['id'] . '" ' . 
                                   ($button['status'] == 1 ? 'checked' : '') . '>
                            <label class="onoffswitch-label" for="status_' . $button['id'] . '"></label>
                          </div>';
                
                // Order
                $row[] = '<span class="text-nowrap">' . html_escape($button['order']) . '</span>';
                
                // Options column
                $options = '';
                if (has_permission('topics', '', 'edit')) {
                    $options .= '<a href="#" onclick="edit_action_button(' . $button['id'] . '); return false;" 
                                  class="tw-text-neutral-500 hover:tw-text-neutral-700 focus:tw-text-neutral-700">
                                  <i class="fa-regular fa-pen-to-square fa-lg"></i>
                               </a>';
                }
                if (has_permission('topics', '', 'delete')) {
                    // $options .= ' <a href="' . admin_url('topics/delete_action_button/' . $button['id']) . '" 
                    //               class="tw-text-neutral-500 hover:tw-text-neutral-700 focus:tw-text-neutral-700 _delete">
                    //               <i class="fa-regular fa-trash-can fa-lg"></i>
                    //            </a>';
                }
                $row[] = '<div class="tw-flex tw-items-center tw-space-x-3">' . $options . '</div>';
                
                $data[] = $row;
            }

            echo json_encode(['data' => $data]);
            return;
        }

        // Load view data
        $this->load->model('Topic_action_button_model');
        $data['action_buttons'] = $this->Topic_action_button_model->get();
        $this->load->model(['Action_type_model', 'Action_state_model']);
        $data['action_types'] = $this->Action_type_model->get_all_action_types();
        $data['action_states'] = $this->Action_state_model->get_all_action_states();
        $data['title'] = _l('topic_action_buttons');
        
        $this->load->view('topics/action_buttons/manage', $data);
    }

    public function action_button($id = '')
    {
        if (!has_permission('topics', '', 'view')) {
            access_denied('topics');
        }

        $this->load->model('Topic_action_button_model');

        if ($this->input->post()) {
            $post_data = $this->input->post();
            
            // Convert status and controller_only from checkbox
            $post_data['status'] = isset($post_data['status']) ? 1 : 0;
            $post_data['controller_only'] = isset($post_data['controller_only']) ? 1 : 0;

            // Thêm validation cho action_command khi có button trùng target
            $existing_buttons = $this->Topic_action_button_model->get_by_target(
                $post_data['target_action_type'], 
                $post_data['target_action_state']
            );

            if (count($existing_buttons) > 0 && empty($post_data['action_command'])) {
                echo json_encode([
                    'success' => false,
                    'message' => _l('action_command_required_for_duplicate_target')
                ]);
                return;
            }

            // Validate required fields
            $required_fields = ['name', 'button_type', 'workflow_id', 'trigger_type'];
            foreach ($required_fields as $field) {
                if (empty($post_data[$field])) {
                    echo json_encode([
                        'success' => false,
                        'message' => _l('missing_required_fields')
                    ]);
                    return;
                }
            }

            // Handle ignore arrays
            $post_data['ignore_types'] = !empty($post_data['ignore_types']) ? 
                json_encode($post_data['ignore_types']) : null;
            $post_data['ignore_states'] = !empty($post_data['ignore_states']) ? 
                json_encode($post_data['ignore_states']) : null;

            // Kiểm tra xem có ID trong post data không
            $button_id = $post_data['id'] ?? '';
            unset($post_data['id']); // Xóa id khỏi post_data để tránh conflict

            if ($button_id) {
                // Update
                if (!has_permission('topics', '', 'edit')) {
                    access_denied('topics');
                }
                $success = $this->Topic_action_button_model->update($post_data, $button_id);
                $message = _l('updated_successfully', _l('action_button'));
            } else {
                // Create
                if (!has_permission('topics', '', 'create')) {
                    access_denied('topics');
                }
                $success = $this->Topic_action_button_model->add($post_data);
                $message = _l('added_successfully', _l('action_button'));
            }

            if ($success) {
                echo json_encode([
                    'success' => true,
                    'message' => $message
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => _l('error_adding_updating_action_button')
                ]);
            }
            return;
        }

        // Load required data for the form
        $this->load->model(['Action_type_model', 'Action_state_model']);
        $data['action_types'] = $this->Action_type_model->get_all_action_types();
        $data['action_states'] = $this->Action_state_model->get_all_action_states();
        
        if ($id) {
            $data['action_button'] = $this->Topic_action_button_model->get($id);
            if (!$data['action_button']) {
                show_404();
            }
            // Đảm bảo dữ liệu được load đúng
            log_activity('Loading action button data: ' . json_encode($data['action_button']));
            $data['title'] = _l('edit_action_button');
        } else {
            $data['title'] = _l('new_action_button');
        }

        // If editing and has target_action_type, load corresponding states
        if ($id != '' && !empty($data['action_button']['target_action_type'])) {
            $data['target_states'] = $this->Action_state_model->get_states_by_action_type(
                $data['action_button']['target_action_type']
            );
        }

        // Decode ignore arrays for form
        if (!empty($data['action_button']['ignore_types'])) {
            $data['action_button']['ignore_types'] = json_decode($data['action_button']['ignore_types'], true);
        }
        if (!empty($data['action_button']['ignore_states'])) {
            $data['action_button']['ignore_states'] = json_decode($data['action_button']['ignore_states'], true);
        }

        echo json_encode([
            'success' => true,
            'data' => $this->load->view('topics/action_buttons/modal', $data, true)
        ]);
    }

    public function get_action_states()
    {
        if (!has_permission('topics', '', 'view')) {
            ajax_access_denied();
        }
        
        $action_type_code = $this->input->post('action_type_code');
        
        if (!$action_type_code) {
            echo json_encode([
                'success' => false,
                'message' => 'Action type code is required'
            ]);
            die();
        }
        
        $this->load->model('Action_state_model');
        $action_states = $this->Action_state_model->get_states_by_type($action_type_code);
        
        echo json_encode([
            'success' => true,
            'data' => $action_states
        ]);
        die();
    }

    public function get_history_processed_values($id, $history_id) {
        if (!has_permission('topics', '', 'view')) {
            ajax_access_denied();
        }
        
        $topic = $this->Topics_model->get_topic($id);
        if (!$topic) {
            echo json_encode(['success' => false, 'message' => 'Topic not found']);
            return;
        }
        
        $history_data = $this->Topics_model->get_log_data($history_id, $topic->topicid);
        if (!$history_data) {
            echo json_encode(['success' => false, 'message' => 'History data not found']);
            return;
        }
        
        try {
            $processed_data = json_decode($history_data, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Invalid JSON data');
            }
            
            echo json_encode([
                'success' => true,
                'data' => $processed_data
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function change_button_status($id, $status)
    {
        if (!has_permission('topics', '', 'edit')) {
            ajax_access_denied();
        }

        $this->load->model('Topic_action_button_model');
        
        // Validate status
        $status = intval($status);
        if ($status !== 0 && $status !== 1) {
            echo json_encode([
                'success' => false,
                'message' => _l('invalid_status')
            ]);
            return;
        }

        // Validate button exists
        $button = $this->Topic_action_button_model->get($id);
        if (!$button) {
            echo json_encode([
                'success' => false,
                'message' => _l('action_button_not_found')
            ]);
            return;
        }

        // Update only status
        $success = $this->Topic_action_button_model->change_status($id, $status);

        echo json_encode([
            'success' => $success,
            'message' => $success ? _l('settings_updated') : _l('error_updating_status')
        ]);
    }

    public function get_progress_steps()
    {
        if (!has_permission('topics', '', 'view')) {
            ajax_access_denied();
        }

        $topicid = $this->input->post('topicid');
        if (!$topicid) {
            echo json_encode(['success' => false, 'message' => 'Topic ID is required']);
            die();
        }

        $topic_steps = $this->Topics_model->get_topic_steps($topicid);
        $data['topic_steps'] = $topic_steps;

        $this->load->view('includes/progress_steps', $data);
    }

    public function get_available_topics($controller_id)
    {
        if (!has_permission('topics', '', 'view')) {
            ajax_access_denied();
        }

        $this->load->model('Topic_master_model');
        
        // Get data from model
        $this->app->get_table_data(module_views_path('topics', 'includes/available_topics_table'), [
            'controller_id' => $controller_id
        ]);
    }

    public function add_topics($controller_id) 
    {
        if (!has_permission('topics', '', 'edit')) {
            ajax_access_denied();
        }

        $topic_ids = $this->input->post('topic_ids');
        
        if (empty($topic_ids)) {
            echo json_encode([
                'success' => false,
                'message' => _l('no_topics_selected')
            ]);
            die();
        }

        $this->load->model('Topic_controller_model');
        $success = $this->Topic_controller_model->add_topics($controller_id, $topic_ids);

        echo json_encode([
            'success' => $success,
            'message' => $success ? _l('topics_added_successfully') : _l('topics_add_failed')
        ]);
    }

    public function toggle_topic_status()
    {
        if (!has_permission('topics', '', 'edit')) {
            ajax_access_denied();
        }

        $response = [
            'success' => false,
            'message' => '',
            'refresh' => false
        ];

        try {
            $topicid = $this->input->post('topicid');
            if (!$topicid) {
                throw new Exception(_l('topic_id_required'));
            }

            // Load Topic Master model
            $this->load->model('Topic_master_model');
            
            // Get current status
            $topic = $this->Topic_master_model->get_by_topicid($topicid);
            if (!$topic) {
                throw new Exception(_l('topic_not_found'));
            }

            // Toggle status
            $new_status = $topic->status == 1 ? 0 : 1;
            $success = $this->Topic_master_model->update_status($topicid, $new_status);

            if ($success) {
                $response['success'] = true;
                $response['message'] = _l($new_status == 1 ? 'topic_activated' : 'topic_deactivated');
                $response['refresh'] = true;
                
                // Log activity
                log_activity(
                    sprintf(
                        'Topic status changed to %s [TopicID: %s]', 
                        ($new_status == 1 ? 'Active' : 'Inactive'),
                        $topicid
                    )
                );
            } else {
                throw new Exception(_l('status_update_failed'));
            }

        } catch (Exception $e) {
            $response['message'] = $e->getMessage();
        }

        echo json_encode($response);
        die();
    }

    public function get_buttons_for_sorting() {
        if (!has_permission('topics', '', 'edit')) {
            ajax_access_denied();
        }
        
        $this->load->model('Topic_action_button_model');
        $buttons = $this->Topic_action_button_model->get_for_sorting();
        
        echo json_encode([
            'success' => true,
            'buttons' => $buttons
        ]);
    }

    public function save_button_order() {
        if (!has_permission('topics', '', 'edit')) {
            ajax_access_denied();
        }

        $this->load->model('Topic_action_button_model');
        $orders = $this->input->post('orders');
        
        $this->db->trans_start();
        
        try {
            $success = $this->Topic_action_button_model->update_order($orders);
            
            if ($this->db->trans_status() === FALSE) {
                throw new Exception(_l('error_updating_positions'));
            }
            
            $this->db->trans_complete();
            $message = _l('positions_updated');
            
        } catch (Exception $e) {
            $this->db->trans_rollback();
            $success = false;
            $message = $e->getMessage();
        }

        echo json_encode([
            'success' => $success,
            'message' => $message
        ]);
    }

    /**
     * Get available controllers for topic
     */
    public function get_available_controllers()
    {
        if (!has_permission('topics', '', 'view')) {
            ajax_access_denied();
        }

        $topic_id = $this->input->get('topic_id');
        if (!$topic_id) {
            // Return empty array instead of access denied
            echo json_encode([
                'success' => false,
                'message' => 'Missing topic ID',
                'data' => [
                    'controllers' => []
                ]
            ]);
            return;
        }

        $this->load->model('Topic_controller_model');
        $controllers = $this->Topic_controller_model->get_active_controllers();

        echo json_encode([
            'success' => true,
            'data' => [
                'controllers' => $controllers
            ]
        ]);
    }

    /**
     * Add topic to controller
     */
    public function add_topic_to_controller()
    {
        if (!has_permission('topics', '', 'edit')) {
            ajax_access_denied();
        }

        $topic_id = $this->input->post('topic_id');
        $controller_id = $this->input->post('controller_id');

        if (!$topic_id || !$controller_id) {
            echo json_encode([
                'success' => false,
                'message' => _l('missing_required_fields')
            ]);
            die();
        }

        $this->load->model('Topic_controller_model');
        $success = $this->Topic_controller_model->add_topics($controller_id, [$topic_id]);

        echo json_encode([
            'success' => $success,
            'message' => $success ? _l('topic_added_to_controller_success') : _l('topic_added_to_controller_failed')
        ]);
    }

    /**
     * Get controller info for topic
     */
    public function get_topic_controller()
    {
        if (!has_permission('topics', '', 'view')) {
            ajax_access_denied();
        }

        $topic_id = $this->input->get('topic_id');
        if (!$topic_id) {
            ajax_access_denied('Missing topic ID');
        }

        $this->load->model('Topic_controller_model');
        $controller = $this->Topic_controller_model->get_controller_by_topic($topic_id);

        echo json_encode([
            'success' => true,
            'data' => [
                'controller' => $controller
            ]
        ]);
    }

    public function search_controllers()
    {
        if (!has_permission('topics', '', 'view')) {
            ajax_access_denied();
        }

        $search = $this->input->get('search');
        
        $this->load->model('Topic_controller_model');
        $controllers = $this->Topic_controller_model->search_controllers($search);

        echo json_encode([
            'success' => true,
            'data' => $controllers
        ]);
    }

    public function check_workflow_status() {
        if (!has_permission('topics', '', 'view')) {
            ajax_access_denied();
        }

        $workflow_id = $this->input->post('workflow_id');
        $execution_id = $this->input->post('execution_id');

        if (!$workflow_id || !$execution_id) {
            echo json_encode(['success' => false, 'message' => 'Missing parameters']);
            return;
        }

        // Create processor and check workflow status
        $processor = new ImageGenerateToggleProcessor();
        $result = $processor->checkWorkflowStatus($workflow_id, $execution_id);

        echo json_encode($result);
    }

    public function save_composed_topic() {
        // Validate input
        // Save changes
        // Return response
    }

    /**
     * Check if image exists in external data
     */
    public function check_image_external_data()
    {
        if (!has_permission('topics', '', 'view')) {
            ajax_access_denied();
        }

        $topic_master_id = $this->input->post('topic_master_id');
        $rel_id = $this->input->post('rel_id'); 
        $rel_type = $this->input->post('rel_type');

        if (!$topic_master_id || !$rel_id || !$rel_type) {
            echo json_encode([
                'success' => false,
                'message' => 'Missing required parameters',
                'exists' => false
            ]);
            return;
        }

        // Load Topic External Data model
        $this->load->model('Topic_external_data_model');

        try {
            // Get external data if exists
            $external_data = $this->Topic_external_data_model->get_by_rel(
                $topic_master_id,
                $rel_type,
                $rel_id
            );

            if ($external_data) {
                echo json_encode([
                    'success' => true,
                    'exists' => true,
                    'rel_data' => $external_data->rel_data // Just return WordPress URL
                ]);
            } else {
                echo json_encode([
                    'success' => true,
                    'exists' => false
                ]);
            }

        } catch (Exception $e) {
            log_activity('Error checking image external data: ' . $e->getMessage());
            
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage(),
                'exists' => false
            ]);
        }
    }

    /**
     * Save external data
     */
    public function save_external_data()
    {
        if (!has_permission('topics', '', 'edit')) {
            ajax_access_denied();
        }

        $data = $this->input->post();
        
        // Kiểm tra xem có topic_id không, nếu có thì chuyển đổi thành topic_master_id
        if (isset($data['topic_id']) && !isset($data['topic_master_id'])) {
            // Sử dụng helper function từ topics_setup_helper.php
            $data['topic_master_id'] = get_topic_master_id($data['topic_id']);
            unset($data['topic_id']); // Xóa topic_id sau khi chuyển đổi
            
            if (!$data['topic_master_id']) {
                echo json_encode([
                    'success' => false,
                    'message' => _l('topic_master_not_found')
                ]);
                return;
            }
        }
        
        if (!isset($data['topic_master_id']) || !isset($data['rel_type']) || !isset($data['rel_id'])) {
            echo json_encode([
                'success' => false,
                'message' => _l('missing_required_fields')
            ]);
            return;
        }

        $this->load->model('Topic_external_data_model');
        $result = $this->Topic_external_data_model->save($data);

        echo json_encode($result);
    }

    /**
     * Delete external data
     */
    public function delete_external_data()
    {
        if (!has_permission('topics', '', 'edit')) {
            ajax_access_denied();
        }

        // Kiểm tra xem có topic_id không, nếu có thì chuyển đổi thành topic_master_id
        $topic_id = $this->input->post('topic_id');
        $topic_master_id = $this->input->post('topic_master_id');
        
        if ($topic_id && !$topic_master_id) {
            // Sử dụng helper function từ topics_setup_helper.php
            $topic_master_id = get_topic_master_id($topic_id);
            
            if (!$topic_master_id) {
                echo json_encode([
                    'success' => false,
                    'message' => _l('topic_master_not_found')
                ]);
                return;
            }
        }
        
        $rel_type = $this->input->post('rel_type');
        $rel_id = $this->input->post('rel_id');
        
        if (!$topic_master_id || !$rel_type || !$rel_id) {
            echo json_encode([
                'success' => false,
                'message' => _l('missing_required_fields')
            ]);
            return;
        }

        $this->load->model('Topic_external_data_model');
        $result = $this->Topic_external_data_model->delete_by_rel($topic_master_id, $rel_type, $rel_id);

        echo json_encode([
            'success' => (bool)$result,
            'message' => $result ? _l('external_data_deleted_successfully') : _l('external_data_deletion_failed')
        ]);
    }

    /**
     * Get log files list
     */
    public function get_log_files()
    {
        // Check if debug panel is enabled
        if (get_option('topics_debug_panel_enabled') != 1) {
            echo json_encode([]);
            return;
        }

        $logs_path = APPPATH . 'logs/';
        $files = [];
        
        if ($handle = opendir($logs_path)) {
            while (false !== ($entry = readdir($handle))) {
                if ($entry != "." && $entry != ".." && strpos($entry, 'log-') === 0) {
                    $filepath = $logs_path . $entry;
                    $files[] = [
                        'name' => $entry,
                        'size' => $this->format_file_size(filesize($filepath)),
                        'date' => filemtime($filepath)
                    ];
                }
            }
            closedir($handle);
        }

        // Sort by date descending
        usort($files, function($a, $b) {
            return $b['date'] - $a['date'];
        });

        echo json_encode($files);
    }

    /**
     * Format log content for better readability
     */
    private function format_log_content($content) {
        if (empty($content)) {
            return '';
        }

        $lines = explode("\n", $content);
        $formatted = [];
        $currentGroup = null;
        $rawContent = ''; // Giữ nội dung gốc
        
        foreach ($lines as $line) {
            if (empty(trim($line))) continue;
            
            // Thêm vào nội dung gốc
            $rawContent .= $line . "\n";
            
            // Parse log line including WARNING
            if (preg_match('/^(ERROR|DEBUG|INFO)\s+-\s+(\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2})\s+-->\s+(.*)$/', $line, $matches)) {
                $level = $matches[1];
                $timestamp = $matches[2];
                $message = $matches[3];
                
                // Try to parse JSON in message
                if (strpos($message, '{') !== false) {
                    $jsonStart = strpos($message, '{');
                    $prefix = substr($message, 0, $jsonStart);
                    $jsonStr = substr($message, $jsonStart);
                    
                    try {
                        $json = json_decode($jsonStr, true);
                        if ($json) {
                            $message = $prefix . '<pre class="json">' . 
                                      json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . 
                                      '</pre>';
                        }
                    } catch (Exception $e) {
                        // Keep original message if JSON parsing fails
                    }
                }
                
                // Group by timestamp and level
                $groupKey = $timestamp . '_' . $level;
                if (!isset($formatted[$groupKey])) {
                    $formatted[$groupKey] = [
                        'timestamp' => $timestamp,
                        'level' => $level,
                        'messages' => []
                    ];
                }
                
                $formatted[$groupKey]['messages'][] = $message;
            } 
            // Parse PHP Warning lines
            else if (preg_match('/^(Severity:\s+Warning)\s+-->\s+(.*)$/', $line, $matches)) {
                $timestamp = date('Y-m-d H:i:s'); // Current time for grouping
                $groupKey = $timestamp . '_WARNING';
                
                if (!isset($formatted[$groupKey])) {
                    $formatted[$groupKey] = [
                        'timestamp' => $timestamp,
                        'level' => 'WARNING',
                        'messages' => []
                    ];
                }
                
                $formatted[$groupKey]['messages'][] = $matches[2];
            }
        }
        
        // Build HTML output
        $html = '<div class="log-formatted">';
        foreach (array_reverse($formatted) as $group) {
            $levelClass = strtolower($group['level']);
            $isWarning = $levelClass === 'warning';
            
            $html .= "<div class='log-group {$levelClass}" . ($isWarning ? " collapsible collapsed" : " collapsible") . "'>";
            $html .= "<div class='log-header" . ($isWarning ? " warning-header" : " collapsible-header") . "'>";
            $html .= "<span class='log-level'>{$group['level']}</span>";
            $html .= "<span class='log-timestamp'>{$group['timestamp']}</span>";
            // Thêm icon collapse cho tất cả các loại
            $html .= "<span class='collapse-icon'><i class='fa fa-chevron-down'></i></span>";
            $html .= "<button class='btn-copy-log' title='Copy to clipboard'><i class='fa fa-copy'></i></button>";
            $html .= "</div>";
            $html .= "<div class='log-messages" . ($isWarning ? " warning-content" : " collapsible-content") . "'>";

            // Tạo một div ẩn chứa nội dung gốc để copy
            $rawGroupContent = '';
            foreach ($group['messages'] as $msg) {
                $rawGroupContent .= strip_tags($msg) . "\n";
            }
            $html .= "<div class='log-raw-content' style='display:none;'>" . htmlspecialchars($rawGroupContent) . "</div>";

            foreach ($group['messages'] as $message) {
                $html .= "<div class='log-message'>{$message}</div>";
            }
            $html .= "</div>";
            $html .= "</div>";
        }
        $html .= '</div>';
        
        // Add raw content in hidden div for search functionality
        $html .= '<div class="log-raw" style="display:none;">' . htmlspecialchars($rawContent) . '</div>';
        
        return $html;
    }

    /**
     * Get log file content
     */
    public function get_log_content()
    {
        // Check if debug panel is enabled
        if (get_option('topics_debug_panel_enabled') != 1) {
            echo '';
            return;
        }

        $file = $this->input->get('file');
        
        // Basic security check
        if (strpos($file, 'log-') !== 0 || strpos($file, '..') !== false) {
            echo 'Invalid file';
            return;
        }

        $filepath = APPPATH . 'logs/' . $file;
        
        if (file_exists($filepath)) {
            $content = file_get_contents($filepath);
            echo $this->format_log_content($content);
        } else {
            echo 'File not found';
        }
    }

    /**
     * Format file size
     */
    private function format_file_size($size)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            $i++;
        }
        return round($size, 2) . ' ' . $units[$i];
    }

    /**
     * Delete log file
     */
    public function delete_log_file()
    {
        if (!has_permission('topics', '', 'delete')) {
            ajax_access_denied();
        }

        // Check if debug panel is enabled
        if (get_option('topics_debug_panel_enabled') != 1) {
            echo json_encode([
                'success' => false,
                'message' => 'Debug panel is disabled'
            ]);
            return;
        }

        $file = $this->input->post('file');
        
        // Basic security check
        if (strpos($file, 'log-') !== 0 || strpos($file, '..') !== false) {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid file'
            ]);
            return;
        }

        $filepath = APPPATH . 'logs/' . $file;
        
        if (file_exists($filepath)) {
            try {
                // Create backup before delete
                $backup_path = APPPATH . 'logs/backup/';
                if (!is_dir($backup_path)) {
                    mkdir($backup_path, 0755, true);
                }
                
                // Copy to backup with timestamp
                $backup_file = $backup_path . date('Y-m-d_H-i-s_') . $file;
                copy($filepath, $backup_file);
                
                // Delete original file
                unlink($filepath);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Log file deleted successfully'
                ]);
            } catch (Exception $e) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Error deleting file: ' . $e->getMessage()
                ]);
            }
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'File not found'
            ]);
        }
    }

    /**
     * Clean log file content
     */
    public function clean_log_file()
    {
        if (!has_permission('topics', '', 'delete')) {
            ajax_access_denied();
        }

        // Check if debug panel is enabled
        if (get_option('topics_debug_panel_enabled') != 1) {
            echo json_encode([
                'success' => false,
                'message' => 'Debug panel is disabled'
            ]);
            return;
        }

        $file = $this->input->post('file');
        
        // Basic security check
        if (strpos($file, 'log-') !== 0 || strpos($file, '..') !== false) {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid file'
            ]);
            return;
        }

        $filepath = APPPATH . 'logs/' . $file;
        
        if (file_exists($filepath)) {
            try {
                // Create backup before cleaning
                $backup_path = APPPATH . 'logs/backup/';
                if (!is_dir($backup_path)) {
                    mkdir($backup_path, 0755, true);
                }
                
                // Copy to backup with timestamp
                $backup_file = $backup_path . date('Y-m-d_H-i-s_') . $file;
                copy($filepath, $backup_file);
                
                // Clean file content
                file_put_contents($filepath, '');
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Log file cleaned successfully'
                ]);
            } catch (Exception $e) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Error cleaning file: ' . $e->getMessage()
                ]);
            }
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'File not found'
            ]);
        }
    }

    /**
     * Get controller information by ID
     * Returns detailed information about a specific controller
     */
    public function get_controller_info()
    {
        if (!has_permission('topics', '', 'view')) {
            ajax_access_denied();
        }

        $controller_id = $this->input->get('controller_id');
        if (!$controller_id) {
            echo json_encode([
                'success' => false,
                'message' => 'Missing controller ID'
            ]);
            return;
        }

        $this->load->model('Topic_controller_model');
        $controller = $this->Topic_controller_model->get($controller_id);

        if (!$controller) {
            echo json_encode([
                'success' => false,
                'message' => 'Controller not found'
            ]);
            return;
        }

        echo json_encode([
            'success' => true,
            'data' => $controller
        ]);
    }

    /**
     * Get external data
     * JSON endpoint để lấy dữ liệu từ topic_external_data
     */
    public function get_external_data()
    {
        if (!has_permission('topics', '', 'view')) {
            ajax_access_denied();
        }

        // Kiểm tra xem có topic_id không, nếu có thì chuyển đổi thành topic_master_id
        $topic_id = $this->input->post('topic_id') ? $this->input->post('topic_id') : $this->input->get('topic_id');
        $topic_master_id = $this->input->post('topic_master_id') ? $this->input->post('topic_master_id') : $this->input->get('topic_master_id');
        
        if ($topic_id && !$topic_master_id) {
            // Sử dụng helper function từ topics_setup_helper.php
            $topic_master_id = get_topic_master_id($topic_id);
            
            if (!$topic_master_id) {
                echo json_encode([
                    'success' => false,
                    'message' => _l('topic_master_not_found')
                ]);
                return;
            }
        }
        
        $rel_type = $this->input->post('rel_type') ? $this->input->post('rel_type') : $this->input->get('rel_type');
        $rel_id = $this->input->post('rel_id') ? $this->input->post('rel_id') : $this->input->get('rel_id');
        
        if (!$topic_master_id || !$rel_type || !$rel_id) {
            echo json_encode([
                'success' => false,
                'message' => _l('missing_required_fields'),
                'fields' => [
                    'topic_master_id' => $topic_master_id,
                    'rel_type' => $rel_type,
                    'rel_id' => $rel_id
                ]
            ]);
            return;
        }

        $this->load->model('Topic_external_data_model');
        $data = $this->Topic_external_data_model->get_by_rel($topic_master_id, $rel_type, $rel_id);

        if ($data) {
            echo json_encode([
                'success' => true,
                'data' => $data
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => _l('no_external_data_found')
            ]);
        }
    }

    /**
     * Get topic_master_id from topic_id
     * Sử dụng helper function get_topic_master_id từ topics_setup_helper.php
     */
    public function get_topic_master_id()
    {
        if (!has_permission('topics', '', 'view')) {
            ajax_access_denied();
        }

        $topic_id = $this->input->get('topic_id') ? $this->input->get('topic_id') : $this->input->post('topic_id');
        
        if (!$topic_id) {
            echo json_encode([
                'success' => false,
                'message' => _l('missing_required_fields'),
                'topic_master_id' => null
            ]);
            return;
        }

        // Sử dụng helper function từ topics_setup_helper.php
        $topic_master_id = get_topic_master_id($topic_id);

        if ($topic_master_id) {
            echo json_encode([
                'success' => true,
                'topic_master_id' => $topic_master_id
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => _l('topic_master_not_found'),
                'topic_master_id' => null
            ]);
        }
    }

    /**
     * Get external data by type
     * JSON endpoint để lấy tất cả dữ liệu external data theo rel_type
     */
    public function get_external_data_by_type()
    {
        if (!has_permission('topics', '', 'view')) {
            ajax_access_denied();
        }

        // Kiểm tra xem có topic_id không, nếu có thì chuyển đổi thành topic_master_id
        $topic_id = $this->input->post('topic_id') ? $this->input->post('topic_id') : $this->input->get('topic_id');
        $topic_master_id = $this->input->post('topic_master_id') ? $this->input->post('topic_master_id') : $this->input->get('topic_master_id');
        
        if ($topic_id && !$topic_master_id) {
            // Sử dụng helper function từ topics_setup_helper.php
            $topic_master_id = get_topic_master_id($topic_id);
            
            if (!$topic_master_id) {
                echo json_encode([
                    'success' => false,
                    'message' => _l('topic_master_not_found')
                ]);
                return;
            }
        }
        
        $rel_type = $this->input->post('rel_type') ? $this->input->post('rel_type') : $this->input->get('rel_type');
        
        if (!$topic_master_id || !$rel_type) {
            echo json_encode([
                'success' => false,
                'message' => _l('missing_required_fields')
            ]);
            return;
        }

        $this->load->model('Topic_external_data_model');
        $data = $this->Topic_external_data_model->get_for_topic($topic_master_id, $rel_type);

        if ($data && !empty($data)) {
            echo json_encode([
                'success' => true,
                'data' => $data
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => _l('no_external_data_found')
            ]);
        }
    }
}
