<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Topics_api extends CI_Controller
{
    private $user_id;

    public function __construct()
    {
        parent::__construct();
        
        // Load necessary models
        $this->load->model('Topics_model');
        $this->load->model('Action_type_model'); 
        $this->load->model('Action_state_model');
        $this->load->model('Topic_online_status_model');

        // Load form validation library
        $this->load->library('form_validation');

        // Authenticate API key
        $this->authenticate_request();
    }

    /**
     * Authenticate API key from request header
     */
    private function authenticate_request()
    {
        // Get API key from header or request parameters
        $api_key = $this->input->get_request_header('X-API-KEY');
        if (!$api_key) {
            $api_key = $this->input->get('key');  // Check GET parameter
        }
        if (!$api_key) {
            $api_key = $this->input->post('key'); // Check POST parameter
        }
        
        if (!$api_key) {
            $this->output
                ->set_content_type('application/json')
                ->set_status_header(401)
                ->set_output(json_encode([
                    'status' => false,
                    'message' => 'API key not provided'
                ]));
            exit;
        }

        // Check API key in database
        $this->db->where('token', $api_key);
        $this->db->where('permission_enable', 1);
        
        // Check expiration date
        $this->db->where('expiration_date > NOW()');
        
        $api_user = $this->db->get(db_prefix() . 'user_api')->row();

        if (!$api_user) {
            $this->output
                ->set_content_type('application/json')
                ->set_status_header(401)
                ->set_output(json_encode([
                    'status' => false,
                    'message' => 'Invalid or expired API key'
                ]));
            exit;
        }

        // Store user info for later use
        $this->user_id = $api_user->id;
    }

    /**
     * GET /topics
     * Get all topics
     */
    public function index()
    {
        $topics = $this->Topics_model->get_all_topics();
        
        $this->output
            ->set_content_type('application/json')
            ->set_status_header(200)
            ->set_output(json_encode([
                'status' => true,
                'data' => $topics
            ]));
    }

    /**
     * GET /topics/{id} 
     * Get topic by ID
     */
    public function get($id)
    {
        $topic = $this->Topics_model->get_topic($id);

        if (!$topic) {
            $this->output
                ->set_content_type('application/json')
                ->set_status_header(404)
                ->set_output(json_encode([
                    'status' => false,
                    'message' => 'Topic not found'
                ]));
            return;
        }

        $this->output
            ->set_content_type('application/json')
            ->set_status_header(200)
            ->set_output(json_encode([
                'status' => true,
                'data' => $topic
            ]));
    }

    /**
     * POST /topics
     * Create new topic
     */
    public function create()
    {
        $data = [
            'topictitle'        => $this->input->post('topictitle'),
            'topicid'          => $this->input->post('topicid'),
            'action_type_code' => $this->input->post('action_type_code'),
            'action_state_code' => $this->input->post('action_state_code'),
            'log'              => $this->input->post('log'),
            'target_id'        => 0,  // Default target_id to 0
            'position'         => $this->input->post('position'), // Add position
            'data'            => $this->input->post('data') // Add data field
        ];

        // Xử lý automation
        $workflow_id = $this->input->post('workflow_id');
        $execution_id = $this->input->post('execution_id');

        if ($workflow_id && $execution_id) {
            // Kiểm tra automation với cả 3 thông số
            $this->db->where('topic_id', $data['topicid']);
            $this->db->where('workflow_id', $workflow_id);
            $this->db->where('automation_id', $execution_id);
            $existing_automation = $this->db->get(db_prefix() . 'topic_automation_logs')->row();

            if ($existing_automation) {
                // Nếu đã tồn tại, chỉ update thời gian
                $this->db->where('id', $existing_automation->id);
                $this->db->update(db_prefix() . 'topic_automation_logs', [
                    'dateupdated' => date('Y-m-d H:i:s')
                ]);
                
                // Set automation_id là ID trong database
                $data['automation_id'] = $existing_automation->id;
            } else {
                // Tạo mới automation log
                $automation_log = [
                    'topic_id' => $data['topicid'],
                    'automation_id' => $execution_id,
                    'workflow_id' => $workflow_id,
                    'status' => 'pending',
                    'datecreated' => date('Y-m-d H:i:s'),
                    'dateupdated' => date('Y-m-d H:i:s')
                ];
                
                $this->db->insert(db_prefix() . 'topic_automation_logs', $automation_log);
                
                // Set automation_id là ID vừa tạo trong database
                $data['automation_id'] = $this->db->insert_id();
            }
        }

        // Validate required fields
        $this->form_validation->set_data($data);
        $this->form_validation->set_rules('topicid', 'TopicId', 'required');
        $this->form_validation->set_rules('topictitle', 'Title', '');
        $this->form_validation->set_rules('action_type_code', 'Action Type Code', 'required');
        $this->form_validation->set_rules('action_state_code', 'Action State Code', 'required');
        $this->form_validation->set_rules('log', 'Log', '');

        if ($this->form_validation->run() === FALSE) {
            $this->output
                ->set_content_type('application/json')
                ->set_status_header(400)
                ->set_output(json_encode([
                    'status' => false,
                    'message' => validation_errors()
                ]));
            return;
        }

        // Add target validation
        $target_id = $this->input->post('target_id');
        $target_type = $this->input->post('target_type');

        // Load models
        $this->load->model('Topic_target_model');
        $this->load->model('Topic_master_model');

        // If target_type is provided, try to find corresponding target_id
        if ($target_type) {
            $target = $this->Topic_target_model->get_by_type($target_type);
            if ($target) {
                $data['target_id'] = $target->id;
            }
        }

        // If target_id is provided, verify it exists
        if ($target_id) {
            $target = $this->Topic_target_model->get_by_targetid($target_id);
            if ($target) {
                $data['target_id'] = $target->id;
            }
        }

        // Add position validation
        if (!empty($data['position'])) {
            // Check if position and target_id already exists
            $this->db->where('topicid', $data['topicid']);
            $this->db->where('position', $data['position']);
            $this->db->where('target_id', $data['target_id']); // Add target_id condition
            $existing_topic = $this->db->get(db_prefix() . 'topics')->row();
            
            if ($existing_topic) {
                // Update existing topic at this position and target_id
                $update_data = [
                    'topictitle' => $data['topictitle'],
                    'action_type_code' => $data['action_type_code'],
                    'action_state_code' => $data['action_state_code'],
                    'log' => $data['log'],
                    'data' => $data['data']
                ];
                
                $updated = $this->Topics_model->update_topic($existing_topic->id, $update_data);
                
                if ($updated) {
                    $this->output
                        ->set_content_type('application/json')
                        ->set_status_header(200)
                        ->set_output(json_encode([
                            'status' => true,
                            'message' => 'Topic updated at existing position and target_id successfully',
                            'id' => $existing_topic->id,
                            'position' => $data['position'],
                            'target_id' => $data['target_id']
                        ]));
                    return;
                }
            }
            // If no matching record found with both position and target_id, 
            // continue to create new topic
        }

        // Start transaction
        $this->db->trans_start();

        // Check if topic master exists
        $topic_master = $this->Topic_master_model->get_by_topicid($data['topicid']);
        
        if (!$topic_master) {
            // Create new topic master
            $master_data = [
                'topicid' => $data['topicid'],
                'topictitle' => $data['topictitle'],
                // 'status' => 1
            ];
            
            $master_id = $this->Topic_master_model->add($master_data);
            
            if (!$master_id) {
                $this->db->trans_rollback();
                $this->output
                    ->set_content_type('application/json')
                    ->set_status_header(500)
                    ->set_output(json_encode([
                        'status' => false,
                        'message' => 'Failed to create topic master'
                    ]));
                return;
            }
        }

        // Validate action_type_code exists
        $action_type = $this->Action_type_model->get_action_type($data['action_type_code']);
        if (!$action_type) {
            $this->db->trans_rollback();
            $this->output
                ->set_content_type('application/json')
                ->set_status_header(400)
                ->set_output(json_encode([
                    'status' => false,
                    'message' => 'Invalid action_type_code'
                ]));
            return;
        }

        // Validate action_state_code exists and belongs to the action type
        $this->db->where('action_state_code', $data['action_state_code']);
        $this->db->where('action_type_code', $data['action_type_code']);
        $action_state = $this->db->get(db_prefix() . 'topic_action_states')->row();
        
        if (!$action_state) {
            $this->db->trans_rollback();
            $this->output
                ->set_content_type('application/json')
                ->set_status_header(400)
                ->set_output(json_encode([
                    'status' => false,
                    'message' => 'Invalid action_state_code or action_state_code does not belong to the specified action_type_code'
                ]));
            return;
        }

        // Create topic
        $id = $this->Topics_model->add_topic($data);
        
        if ($id) {
            $this->db->trans_commit();
            
            try {
                $topicid = $data['topicid'];
                $online_staff = $this->_get_topic_online_staff($topicid);
                $notifiedUsers = [];

                foreach ($online_staff as $member) {
                    log_activity('Notified Userid ' . $member['staffid'] . ' ' . $member['firstname'] . ' ' . $member['lastname'] . ' online');
                    try {
                        // Get action type name
                        $action_type = $this->Action_type_model->get_action_type($data['action_type_code']);
                        $action_type_name = $action_type ? $action_type->name : $data['action_type_code'];

                        // Get action state name
                        $this->db->where('action_state_code', $data['action_state_code']);
                        $this->db->where('action_type_code', $data['action_type_code']);
                        $action_state = $this->db->get(db_prefix() . 'topic_action_states')->row();
                        $action_state_name = $action_state ? $action_state->name : $data['action_state_code'];

                        // Create notification with names instead of codes
                        $notification = [
                            'description'     => sprintf(
                                '%s %s [Type: %s, State: %s]',
                                _l('topic_updated_notification'),
                                $data['topictitle'],
                                $action_type_name,
                                $action_state_name
                            ),
                            'touserid'       => $member['staffid'],
                            'fromcompany'    => false,
                            'fromclientid'  => 0,
                            'from_fullname' => "Automator(Bot) Workflow",
                            'fromuserid'     => 8,
                            'link'           => 'topics/detail/' . $id,
                            'additional_data' => serialize([
                                'id' => $id,
                                'topic_master_id' => $topic_master ? $topic_master->id : $master_id,
                                'action_type_code' => $data['action_type_code'],
                                'action_state_code' => $data['action_state_code'],
                                'action_type_name' => $action_type_name,
                                'action_state_name' => $action_state_name,
                                'topicid' => $data['topicid']
                            ])
                        ];

                        $notification_id = $this->_add_notification($notification);
                        
                        if ($notification_id) {
                            array_push($notifiedUsers, $member['staffid']);
                            
                            // Prepare pusher notification data
                            $pusher_data = [
                                'notification_id' => $notification_id,
                                'from_user_id' => 8, // Automator Bot ID
                                'to_user_id' => $member['staffid'],
                                'description' => $notification['description'],
                                'link' => $notification['link'],
                                'date' => date('Y-m-d H:i:s'),
                                'additional_data' => unserialize($notification['additional_data'])
                            ];
                            
                            // Trigger notification with data
                            $this->new_pusher_trigger_notification([$member['staffid']], $pusher_data);
                        }

                    } catch (Exception $e) {
                        log_activity('Failed to send notification to staff ID ' . $member['staffid'] . ': ' . $e->getMessage());
                        continue;
                    }
                }

                
            } catch (Exception $e) {
                log_activity('Failed to process notifications: ' . $e->getMessage());
            }

            $this->output
                ->set_content_type('application/json')
                ->set_status_header(201)
                ->set_output(json_encode([
                    'status' => true,
                    'message' => 'Topic created successfully',
                    'id' => $id,
                    'target_id' => $data['target_id'],
                    'topic_master_id' => $topic_master ? $topic_master->id : $master_id
                ]));
        } else {
            $this->db->trans_rollback();
            $this->output
                ->set_content_type('application/json')
                ->set_status_header(500)
                ->set_output(json_encode([
                    'status' => false,
                    'message' => 'Failed to create topic'
                ]));
        }
    }

    /**
     * PUT api/topics/{id}
     * Update an existing topic.
     *
     * @param int $id
     */
    public function topic_put($id)
    {
        $data = [
            'topictitle'   => $this->put('topictitle'),
            'action_type'  => $this->put('action_type'), 
            'action_state' => $this->put('action_state'),
            'log'          => $this->put('log')
        ];

        // Validate input data
        $this->form_validation->set_data($data);
        $this->form_validation->set_rules('topictitle', 'Title', 'required');
        $this->form_validation->set_rules('action_type', 'Action Type', 'required|integer');
        $this->form_validation->set_rules('action_state', 'Action State', 'required|integer');
        $this->form_validation->set_rules('log', 'Log', '');

        if ($this->form_validation->run() === FALSE) {
            $this->response([
                'status' => FALSE,
                'message' => validation_errors()
            ], REST_Controller::HTTP_BAD_REQUEST);
        }

        if ($this->Topics_model->update_topic($id, $data)) {
            $this->response([
                'status'  => TRUE,
                'message' => 'Topic updated successfully'
            ], REST_Controller::HTTP_OK);
        } else {
            $this->response([
                'status'  => FALSE,
                'message' => 'Failed to update topic'
            ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * DELETE api/topics/{id}
     * Delete a topic by ID.
     *
     * @param int $id
     */
    public function topic_delete($id)
    {
        if ($this->Topics_model->delete_topic($id)) {
            $this->response([
                'status'  => TRUE,
                'message' => 'Topic deleted successfully'
            ], REST_Controller::HTTP_OK);
        } else {
            $this->response([
                'status'  => FALSE, 
                'message' => 'Failed to delete topic'
            ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * POST /topics/sync_topic_master
     * Sync topic master data
     */
    public function sync_topic_master()
    {
        // Validate input data
        $data = [
            'topicid' => $this->input->post('topicid'),
            'topictitle' => $this->input->post('topictitle')
        ];

        $this->form_validation->set_data($data);
        $this->form_validation->set_rules('topicid', 'Topic ID', 'required');
        $this->form_validation->set_rules('topictitle', 'Topic Title', 'required');

        if ($this->form_validation->run() === FALSE) {
            $this->output
                ->set_content_type('application/json')
                ->set_status_header(400)
                ->set_output(json_encode([
                    'status' => false,
                    'message' => validation_errors()
                ]));
            return;
        }

        // Load Topic Master model
        $this->load->model('Topic_master_model');
        $this->load->model('Topics_model');

        // Start transaction
        $this->db->trans_start();

        // Check if topic master exists
        $topic_master = $this->Topic_master_model->get_by_topicid($data['topicid']);
        $master_id = null;

        if (!$topic_master) {
            // Check if topic exists
            $this->db->where('topicid', $data['topicid']);
            $existing_topic = $this->db->get(db_prefix() . 'topics')->row();

            // Create new topic master
            $master_data = [
                'topicid' => $data['topicid'],
                'topictitle' => $data['topictitle'],
                'status' => 1
            ];
            
            $master_id = $this->Topic_master_model->add($master_data);
            
            if (!$master_id) {
                $this->db->trans_rollback();
                $this->output
                    ->set_content_type('application/json')
                    ->set_status_header(500)
                    ->set_output(json_encode([
                        'status' => false,
                        'message' => 'Failed to create topic master'
                    ]));
                return;
            }
        } else {
            $master_id = $topic_master->id;
        }

        $this->db->trans_commit();

        $this->output
            ->set_content_type('application/json')
            ->set_status_header(200)
            ->set_output(json_encode([
                'status' => true,
                'message' => 'Topic master synced successfully',
                'data' => [
                    'id' => $master_id,
                    'topicid' => $data['topicid'],
                    'topictitle' => $data['topictitle']
                ]
            ]));
    }

    /**
     * GET /topics/process_data
     * Get process data list by topicid and action_type_code
     */
    public function get_process_data()
    {
        // Validate required parameters
        $topicid = $this->input->get('topicid');
        $action_type_code = $this->input->get('action_type_code');

        if (!$topicid || !$action_type_code) {
            $this->output
                ->set_content_type('application/json')
                ->set_status_header(400)
                ->set_output(json_encode([
                    'status' => false,
                    'message' => 'Missing required parameters: topicid and action_type_code'
                ]));
            return;
        }

        // Load required models
        $this->load->model('Topics_model');
        $this->load->model('Topic_target_model');

        // Get topic by topicid
        $this->db->where('topicid', $topicid);
        $topic = $this->db->get(db_prefix() . 'topics')->row();

        if (!$topic) {
            $this->output
                ->set_content_type('application/json')
                ->set_status_header(404)
                ->set_output(json_encode([
                    'status' => false,
                    'message' => 'Topic not found'
                ]));
            return;
        }

        try {
            // Get topic items with filter conditions
            $where = [
                't.topicid' => $topicid
            ];
            
            // Get raw data from database
            $topic_items = $this->Topic_target_model->get_table_data([], $where, $action_type_code);
            
            // Format topic data
            $topic_data = [
                'id' => $topic->id,
                'topicid' => $topic->topicid,
                'topictitle' => $topic->topictitle,
                'action_type_code' => $topic->action_type_code,
                'action_state_code' => $topic->action_state_code,
                'position' => $topic->position,
                'target_id' => $topic->target_id,
                'data' => json_decode($topic->data),
                'datecreated' => $topic->datecreated,
                'dateupdated' => $topic->dateupdated
            ];

            // Format topic items data
            $formatted_items = [];
            foreach ($topic_items as $item) {
                $formatted_items[] = [
                    'id' => $item['id'],
                    'target_id' => $item['target_id'],
                    'target_type' => $item['target_type'],
                    'title' => $item['title'],
                    'status' => $item['status'],
                    'data' => json_decode($item['data']),
                    'datecreated' => $item['datecreated'],
                    'dateupdated' => $item['dateupdated']
                ];
            }

            // Return response
            $this->output
                ->set_content_type('application/json')
                ->set_status_header(200)
                ->set_output(json_encode([
                    'status' => true,
                    'data' => [
                        'topic' => $topic_data,
                        'items' => $formatted_items,
                        'action_type_code' => $action_type_code,
                        'total_items' => count($formatted_items)
                    ]
                ]));

        } catch (Exception $e) {
            $this->output
                ->set_content_type('application/json')
                ->set_status_header(500)
                ->set_output(json_encode([
                    'status' => false,
                    'message' => $e->getMessage()
                ]));
        }
    }

    /**
     * Update staff online status for topic
     * @param int $staff_id
     * @param string $topic_id
     * @return bool
     */
    private function _update_staff_topic_online_status($staff_id, $topic_id) 
    {
        return $this->Topic_online_status_model->update_staff_online_status($staff_id, $topic_id);
    }

    /**
     * Get online staff for topic
     * @param string $topic_id
     * @param int $timeout Optional timeout in seconds
     * @return array
     */
    private function _get_topic_online_staff($topic_id, $timeout = null) 
    {
        return $this->Topic_online_status_model->get_online_staff_for_topic($topic_id, $timeout);
    }

    /**
     * Remove staff online status
     * @param int $staff_id
     * @param string $topic_id Optional topic_id
     * @return bool
     */
    private function _remove_staff_topic_online_status($staff_id, $topic_id = null)
    {
        return $this->Topic_online_status_model->remove_staff_online_status($staff_id, $topic_id);
    }

    /**
     * Check if staff is online in topic
     * @param int $staff_id
     * @param string $topic_id
     * @return bool
     */
    private function _is_staff_online_in_topic($staff_id, $topic_id)
    {
        $online_staff = $this->Topic_online_status_model->get_online_staff_for_topic($topic_id);
        
        foreach ($online_staff as $staff) {
            if ($staff['staffid'] == $staff_id) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Add notification internally
     * @param array $values Notification data
     * @return bool
     */
    private function _add_notification($values)
    {
        // log_activity('Adding notification: ' . json_encode($values)); 
        // Initialize data array
        $data = [];
        
        // Map values to data array
        foreach ($values as $key => $value) {
            $data[$key] = $value;
        }

        // Set sender information based on login type
        $data['fromuserid']    = $values['fromuserid'];
        $data['fromclientid']  = $values['fromclientid'];
        $data['from_fullname'] = $values['from_fullname'];

        // Set notification date
        $data['date'] = date('Y-m-d H:i:s');
        
        // Apply notification data filters
        $data = hooks()->apply_filters('notification_data', $data);

        // Validate recipient user
        if (isset($data['touserid']) && $data['touserid'] != 0) {
            $this->db->where('staffid', $data['touserid']);
            $user = $this->db->get(db_prefix() . 'staff')->row();
            
            // Skip if user doesn't exist or is inactive
            if (!$user || ($user && $user->active == 0)) {
                log_activity('Notification error: User does not exist or is inactive. User ID: ' . $data['touserid']);
                return false;
            }
        }

        // Insert notification
        if (!$this->db->insert(db_prefix() . 'notifications', $data)) {
            log_activity('Notification error: Failed to insert notification for User ID: ' . $data['touserid']);
            return false;
        }

        // Get notification ID and trigger hook
        if ($notification_id = $this->db->insert_id()) {
            hooks()->do_action('notification_created', $notification_id);
            return $notification_id;
        } else {
            log_activity('Notification error: Failed to retrieve notification ID after insert for User ID: ' . $data['touserid']);
        }

        return false;
    }

    /**
     * Trigger Pusher notification with detailed data
     *
     * @param array $users Array of user IDs to notify
     * @param array $notification_data Notification data to send
     * @return bool
     */
    private function new_pusher_trigger_notification($users = [], $notification_data = [])
    {
        hooks()->do_action('before_pusher_trigger_notification', $users);

        if (get_option('pusher_realtime_notifications') == 0) {
            return false;
        }

        if (!is_array($users) || count($users) == 0) {
            return false;
        }

        $channels = [];
        foreach ($users as $id) {
            array_push($channels, 'notifications-channel-' . $id);
        }

        $channels = array_unique($channels);

        $this->load->library('app_pusher');

        try {
            if (!empty($notification_data)) {
                // Ensure all required fields are present
                $default_data = [
                    'notification_id' => null,
                    'from_user_id' => null,
                    'to_user_id' => null,
                    'description' => '',
                    'link' => '',
                    'date' => date('Y-m-d H:i:s'),
                    'additional_data' => []
                ];

                // Merge default data with provided data
                $notification_data = array_merge($default_data, $notification_data);

                // Log for debugging
                log_activity('Pushing notification with data: ' . json_encode($notification_data));

                // Trigger with notification data
                $this->app_pusher->trigger($channels, 'notification', $notification_data);
            } else {
                // Log warning about empty notification data
                log_activity('Warning: Empty notification data for users: ' . implode(',', $users));
                $this->app_pusher->trigger($channels, 'notification', []);
            }

        } catch (\Exception $e) {
            // Log error
            log_activity('Pusher notification error: ' . $e->getMessage());

            // Disable pusher notifications on error
            update_option('pusher_realtime_notifications', '0');

            return false;
        }

        return true;
    }

    /**
     * POST /topics/toggle_topic_master_status
     * Toggle topic master status (enable/disable)
     */
    public function toggle_topic_master_status()
    {
        // Validate input data
        $data = [
            'topicid' => $this->input->post('topicid'), // Sử dụng topicid thay vì topic_master_id
            'status' => $this->input->post('status')
        ];

        $this->form_validation->set_data($data);
        $this->form_validation->set_rules('topicid', 'Topic ID', 'required|trim'); // Validate topicid là string
        $this->form_validation->set_rules('status', 'Status', 'required|in_list[0,1]');

        if ($this->form_validation->run() === FALSE) {
            $this->output
                ->set_content_type('application/json')
                ->set_status_header(400)
                ->set_output(json_encode([
                    'status' => false,
                    'message' => validation_errors()
                ]));
            return;
        }

        // Load Topic Master model
        $this->load->model('Topic_master_model');

        // Check if topic master exists
        $topic_master = $this->Topic_master_model->get_by_topicid($data['topicid']);
        if (!$topic_master) {
            $this->output
                ->set_content_type('application/json')
                ->set_status_header(404)
                ->set_output(json_encode([
                    'status' => false,
                    'message' => 'Topic master not found'
                ]));
            return;
        }

        // Start transaction
        $this->db->trans_start();

        // Update status
        $update_data = [
            'status' => $data['status'],
            'dateupdated' => date('Y-m-d H:i:s')
        ];

        $this->db->where('topicid', $data['topicid']);
        $success = $this->db->update(db_prefix() . 'topic_master', $update_data);

        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            $this->output
                ->set_content_type('application/json')
                ->set_status_header(500)
                ->set_output(json_encode([
                    'status' => false,
                    'message' => 'Failed to update topic master status'
                ]));
            return;
        }

        $this->db->trans_commit();

        // Log activity
        $status_text = $data['status'] == 1 ? 'enabled' : 'disabled';
        log_activity('Topic Master ' . $status_text . ' [Topic ID: ' . $data['topicid'] . ']');

        $this->output
            ->set_content_type('application/json')
            ->set_status_header(200)
            ->set_output(json_encode([
                'status' => true,
                'message' => 'Topic master status updated successfully',
                'data' => [
                    'topicid' => $data['topicid'],
                    'status' => (int)$data['status'],
                    'dateupdated' => $update_data['dateupdated']
                ]
            ]));
    }

    /**
     * Start workflow execution for a topic
     * POST /topics/start_workflow
     */
    public function start_workflow()
    {
        // Validate input data
        $data = [
            'topicid' => $this->input->post('topicid'),
            'workflow_id' => $this->input->post('workflow_id'),
            'execution_id' => $this->input->post('execution_id') // Nhận execution_id từ n8n
        ];

        $this->form_validation->set_data($data);
        $this->form_validation->set_rules('topicid', 'Topic ID', 'required|trim');
        $this->form_validation->set_rules('workflow_id', 'Workflow ID', 'required|trim');
        $this->form_validation->set_rules('execution_id', 'Execution ID', 'required|trim');

        if ($this->form_validation->run() === FALSE) {
            $this->output
                ->set_content_type('application/json')
                ->set_status_header(400)
                ->set_output(json_encode([
                    'status' => false,
                    'message' => validation_errors()
                ]));
            return;
        }

        // Load required models
        $this->load->model('Topic_automation_log_model');
        $this->load->model('Topic_master_model');

        // Check if topic exists
        $topic = $this->Topic_master_model->get_by_topicid($data['topicid']);
        if (!$topic) {
            $this->output
                ->set_content_type('application/json')
                ->set_status_header(404)
                ->set_output(json_encode([
                    'status' => false,
                    'message' => 'Topic not found'
                ]));
            return;
        }

        try {
            // Prepare log data using execution_id from n8n
            $log_data = [
                'topic_id' => $data['topicid'],
                'automation_id' => $data['execution_id'], // Sử dụng execution_id từ n8n
                'workflow_id' => $data['workflow_id'],
                'status' => 'pending'
            ];

            // Start transaction
            $this->db->trans_start();

            // Add automation log
            $log_id = $this->Topic_automation_log_model->add($log_data);

            if (!$log_id) {
                throw new Exception('Failed to create automation log');
            }

            if ($this->db->trans_status() === FALSE) {
                throw new Exception('Database transaction failed');
            }

            $this->db->trans_commit();

            // Log activity
            log_activity('Started workflow execution [Topic ID: ' . $data['topicid'] . ', Execution ID: ' . $data['execution_id'] . ']');

            $this->output
                ->set_content_type('application/json')
                ->set_status_header(200)
                ->set_output(json_encode([
                    'status' => true,
                    'message' => 'Workflow started successfully',
                    'data' => [
                        'automation_id' => $data['execution_id'],
                        'workflow_id' => $data['workflow_id'],
                        'log_id' => $log_id
                    ]
                ]));

        } catch (Exception $e) {
            $this->db->trans_rollback();

            $this->output
                ->set_content_type('application/json')
                ->set_status_header(500)
                ->set_output(json_encode([
                    'status' => false,
                    'message' => $e->getMessage()
                ]));
        }
    }

    /**
     * Update workflow execution status
     * POST /topics/update_workflow_status
     */
    public function update_workflow_status()
    {
        // Validate input
        $data = [
            'execution_id' => $this->input->post('execution_id'), // Sử dụng execution_id thay vì automation_id
            'status' => $this->input->post('status'),
            'response_data' => $this->input->post('response_data')
        ];

        $this->form_validation->set_data($data);
        $this->form_validation->set_rules('execution_id', 'Execution ID', 'required|trim');
        $this->form_validation->set_rules('status', 'Status', 'required|in_list[pending,started,completed,failed]');

        if ($this->form_validation->run() === FALSE) {
            $this->output
                ->set_content_type('application/json')
                ->set_status_header(400)
                ->set_output(json_encode([
                    'status' => false,
                    'message' => validation_errors()
                ]));
            return;
        }

        $this->load->model('Topic_automation_log_model');

        // Update status using execution_id
        $success = $this->Topic_automation_log_model->update_status(
            $data['execution_id'],
            $data['status'],
            $data['response_data']
        );

        if ($success) {
            log_activity('Updated workflow status [Execution ID: ' . $data['execution_id'] . ', Status: ' . $data['status'] . ']');

            $this->output
                ->set_content_type('application/json')
                ->set_status_header(200)
                ->set_output(json_encode([
                    'status' => true,
                    'message' => 'Workflow status updated successfully'
                ]));
        } else {
            $this->output
                ->set_content_type('application/json')
                ->set_status_header(500)
                ->set_output(json_encode([
                    'status' => false,
                    'message' => 'Failed to update workflow status'
                ]));
        }
    }
} 