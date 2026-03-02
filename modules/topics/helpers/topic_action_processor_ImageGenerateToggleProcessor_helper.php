<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Processor to handle image generation toggle actions
 */
class ImageGenerateToggleProcessor extends BaseTopicActionProcessor {
    protected $CI;
    private const TARGET_TYPE = 'ImageGenerateToggle';
    
    // Define states
    private const STATE_YES = 'GenImgYes';
    private const STATE_NO = 'GenImgNo';
    private const STATE_COMPLETED = 'GenImgCompleted';
    private const STATE_FAILED = 'GenImgFailed';
    private const STATE_PROCESSING = 'GenImgProcessing';

    public function __construct() {
        parent::__construct();
        $this->CI = &get_instance();
        
        // Load required models
        $this->CI->load->model('Topics_model');
        $this->CI->load->model('Topic_master_model');
    }

    public function process($topic_id, $action_data) {
        try {
            // Validate input
            if (!$this->validate($topic_id, $action_data)) {
                return [
                    'success' => false,
                    'message' => implode('; ', $this->getErrors())
                ];
            }

            // Get topic data
            $topic = $this->CI->db->get_where(db_prefix() . 'topics', ['id' => $topic_id])->row();
            if (!$topic) {
                throw new Exception(_l('topic_not_found'));
            }

            // Check if this is step 2 (after image generation)
            $is_step_2 = isset($action_data['audit_step']) && $action_data['audit_step'] == 2;
            
            if (!$is_step_2) {
                // Step 1: Check controller assignment before starting
                $this->CI->load->model('Topic_controller_model');
                $has_controller = $this->CI->Topic_controller_model->topic_has_controller($topic_id);
                
                if (!$has_controller) {
                    return [
                        'success' => false,
                        'message' => _l('topic_needs_controller'),
                        'data' => [
                            'topic_id' => $topic_id,
                            'needs_controller' => true,
                            'clear_button' => true,
                            'error_details' => 'Topic needs to be assigned to a controller'
                        ]
                    ];
                }

                // Set initial state and prepare N8N data
                $action_data['target_state'] = self::STATE_PROCESSING;
                $action_data['audit_step'] = 1;
            } else {
                // Step 2: Update state to completed
                $action_data['target_state'] = self::STATE_COMPLETED;
            }

            // Prepare N8N data
            try {
                $n8n_data = $this->prepareN8nData($topic, $action_data);
            } catch (Exception $e) {
                throw new Exception("Error preparing workflow data: " . $e->getMessage());
            }

            // Send to N8N
            try {
                log_message('error', "Sending to N8N for topic {$topic_id}. Step: " . $action_data['audit_step']);
                $result = send_to_n8n($action_data['workflow_id'], $n8n_data);
                
                log_message('error', "N8N response for topic {$topic_id}: " . json_encode($result));

                if ($result['success']) {
                    if ($action_data['audit_step'] == 1) {
                        // Get workflow_id and execution_id from the correct path in response
                        $response_data = $result['data']['response'] ?? [];
                        $workflow_id = $response_data['workflow_id'] ?? null;
                        $execution_id = $response_data['execution_id'] ?? null;

                        // Try to get additional workflow details
                        $workflow_details = $this->getWorkflowDetails($workflow_id, $execution_id);

                        // Determine message based on workflow status
                        $message = $workflow_details['needs_polling'] 
                            ? _l('image_generation_listening') 
                            : _l('image_generation_initiated');

                        // Log response data
                        log_message('error', "Step 1 success response for topic {$topic_id}: " . json_encode([
                            'success' => true,
                            'message' => $message,
                            'data' => [
                                'response' => $workflow_details['data'] ?? null,
                                'http_code' => $result['data']['http_code'] ?? 200,
                                'clear_button' => false,
                                'audit_step' => 1,
                                'needs_polling' => $workflow_details['needs_polling'] ?? false,
                                'status' => $workflow_details['status'] ?? null,
                                'workflow_id' => $workflow_id,
                                'execution_id' => $execution_id
                            ]
                        ]));

                        // Return success for step 1 with enhanced data if available
                        return [
                            'success' => true,
                            'message' => $message,
                            'data' => [
                                'response' => $workflow_details['data'] ?? null,
                                'workflow_id' => $workflow_id,
                                'execution_id' => $execution_id,
                                'http_code' => $result['data']['http_code'] ?? 200,
                                'clear_button' => false,
                                'audit_step' => 1,
                                'needs_polling' => $workflow_details['needs_polling'] ?? false,
                                'status' => $workflow_details['status'] ?? null
                            ]
                        ];
                    } else {
                        // Return success for step 2
                        return [
                            'success' => true,
                            'message' => _l('image_generation_completed'),
                            'data' => [
                                'response' => $result['data']['response'],
                                'http_code' => $result['data']['http_code'] ?? 200,
                                'clear_button' => true,
                                'audit_step' => 2
                            ]
                        ];
                    }
                }
                
                throw new Exception($result['message'] ?? _l('workflow_execution_failed'));
            } catch (Exception $e) {
                log_message('error', "N8N communication error for topic {$topic_id}: " . $e->getMessage());
                throw new Exception("N8N communication error: " . $e->getMessage());
            }
        } catch (Exception $e) {
            log_message('error', "Error executing workflow for topic {$topic_id}: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => [
                    'clear_button' => true
                ]
            ];
        }
    }

    /**
     * Get workflow execution details
     * Made public to be accessible from controller
     */
    public function getWorkflowDetails($workflow_id, $execution_id) {
        try {
            log_message('error', "Getting workflow details for workflow_id: " . $workflow_id . " and execution_id: " . $execution_id);
            if (!$workflow_id || !$execution_id) {
                return ['success' => false];
            }

            $url = 'https://automate.chantroituonglai.com/webhook/ACTION_BUTTONS_GET_WORKFLOWS';
            
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                'workflow_id' => $workflow_id,
                'execution_id' => $execution_id
            ]));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Accept: application/json'
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($http_code == 200) {
                $data = json_decode($response, true);
                log_message('error', "Workflow details response: " . json_encode($data));
                
                // Return polling info if workflow is not finished
                if (isset($data['finished']) && !$data['finished']) {
                    return [
                        'success' => true,
                        'workflow_id' => $workflow_id,
                        'execution_id' => $execution_id,
                        'needs_polling' => true,
                        'status' => $data['status'] ?? 'running',
                        'data' => null,
                        'message' => _l('image_generation_listening')
                    ];
                }
                
                // If workflow is finished and has data
                if (isset($data['executionData'])) {
                    return [
                        'success' => true,
                        'workflow_id' => $workflow_id,
                        'execution_id' => $execution_id,
                        'data' => $data['executionData'],
                        'needs_polling' => false,
                        'message' => _l('image_generation_completed')
                    ];
                }

                // If workflow is finished but no data
                return [
                    'success' => true,
                    'workflow_id' => $workflow_id,
                    'execution_id' => $execution_id,
                    'needs_polling' => false,
                    'data' => null,
                    'message' => _l('image_generation_completed_no_data')
                ];
            }

            log_message('error', "Workflow details request failed. HTTP Code: " . $http_code);
            return [
                'success' => false,
                'data' => null
            ];

        } catch (Exception $e) {
            log_message('error', "Error getting workflow details: " . $e->getMessage());
            return [
                'success' => false,
                'data' => null
            ];
        }
    }

    /**
     * Check workflow status
     * Public method for controller to use
     */
    public function checkWorkflowStatus($workflow_id, $execution_id) {
        $result = $this->getWorkflowDetails($workflow_id, $execution_id);
        
        // Add finished status to response
        $result['finished'] = isset($result['needs_polling']) ? !$result['needs_polling'] : false;
        
        return $result;
    }

    private function prepareN8nData($topic, $action_data) {
        try {
            // Get base topic data
            $topic_array = (array)$topic;
            $topic_data = array_filter($topic_array, function($value) {
                return !is_null($value);
            });

            // Get controller data
            $controller_data = [];
            $this->CI->load->model('Topic_controller_model');
            $controller = $this->CI->Topic_controller_model->get_controller_by_topic($topic->id);
            
            if ($controller) {
                // Group controller fields into controller_data
                $controller_fields = [
                    'site', 'platform', 'blog_id', 'logo_url', 'project_id',
                     'page_mapping',
                    'slogan', 'writing_style', 'action_1', 'action_2'
                ];

                foreach ($controller_fields as $field) {
                    if (isset($controller->$field)) {
                        $controller_data[$field] = $controller->$field;
                    }
                }
            }

            log_message('error', "Controller data: " . json_encode($controller_data));

            // Get search keyword from completed GenerateSearchKeyword topic
            $search_keyword = $this->CI->db->select('data')
                ->from(db_prefix() . 'topics')
                ->where('topicid', $topic->topicid)
                ->where('action_type_code', 'GenerateSearchKeyword')
                ->where_in('action_state_code', ['Completed'])
                ->order_by('dateupdated', 'DESC')
                ->limit(1)
                ->get()
                ->row();

            // Parse search keyword data
            $keyword_data = null;
            if ($search_keyword && !empty($search_keyword->data)) {
                try {
                    if (is_string($search_keyword->data) && strpos($search_keyword->data, '{') !== 0) {
                        $keyword_data = trim($search_keyword->data);
                    } else {
                        $decoded_data = json_decode($search_keyword->data, true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            if (isset($decoded_data['keyword'])) {
                                $keyword_data = $decoded_data['keyword'];
                            } else {
                                $keyword_data = is_string($decoded_data) ? $decoded_data : null;
                            }
                        }
                    }
                } catch (Exception $e) {
                    // log_activity("Warning: Error decoding search keyword data: " . $e->getMessage());
                }
            }

            // Prepare final data
            return [
                'workflow_data' => $action_data,
                'topic' => $topic_array,
                'topic_data' => $topic_data,
                'controller_data' => $controller_data,
                'search_keyword' => $keyword_data,
                'execution_data' => [
                    'timestamp' => date('Y-m-d H:i:s'),
                    'user_id' => get_staff_user_id(),
                    'source' => self::TARGET_TYPE,
                    'audit_step' => $action_data['audit_step'] ?? 1
                ]
            ];
        } catch (Exception $e) {
            throw new Exception("Error preparing workflow data: " . $e->getMessage());
        }
    }

    public function validate($topic_id, $action_data) {
        try {
            if (!$this->checkTopicExists($topic_id)) {
                $this->addError(_l('topic_not_found'));
                return false;
            }

            // Check N8N settings
            $n8n_host = get_option('topics_n8n_host');
            $n8n_webhook_url = get_option('topics_n8n_webhook_url');
            
            if (empty($n8n_host) && empty($n8n_webhook_url)) {
                $this->addError(_l('n8n_settings_missing'));
                return false;
            }

            // Check workflow_id
            if (empty($action_data['workflow_id'])) {
                $this->addError(_l('workflow_id_missing'));
                return false;
            }

            return true;
        } catch (Exception $e) {
            $this->addError("Validation error: " . $e->getMessage());
            return false;
        }
    }
} 