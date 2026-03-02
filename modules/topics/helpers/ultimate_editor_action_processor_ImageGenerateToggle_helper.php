<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Ultimate Editor Image Generate Toggle Processor
 * 
 * This processor handles image generation actions specifically for the ImageGenerateToggle type
 * with GenImgCompleted state and ImageHires command.
 */
class UltimateEditorImageGenerateToggleProcessor extends BaseUltimateEditorActionProcessor {
    protected $CI;
    private const TARGET_TYPE = 'ImageGenerateToggle';
    
    // Define states
    private const STATE_COMPLETED = 'GenImgCompleted';
    private const STATE_FAILED = 'GenImgFailed';
    private const STATE_PROCESSING = 'GenImgProcessing';

    public function __construct() {
        parent::__construct();
        $this->CI = &get_instance();
        
        // Load required models
        $this->CI->load->model('Topics_model');
        $this->CI->load->model('Topic_editor_draft_model');
    }

    /**
     * Process the image generation action
     * 
     * @param int|null $topic_id Topic ID (optional)
     * @param array $action_data Action data
     * @return array Response data
     */
    public function process($topic_id = null, $action_data) {
        try {
            // Validate inputs
            if (!$this->validate($topic_id, $action_data)) {
                return [
                    'success' => false,
                    'message' => implode('; ', $this->getErrors()),
                    'data' => [
                        'clear_button' => true
                    ]
                ];
            }

            // Get topic data if topic_id is provided
            $topic = null;
            if ($topic_id !== null) {
                $topic = $this->CI->db->get_where(db_prefix() . 'topics', ['topicid' => $topic_id])->row();
                if (!$topic) {
                    throw new Exception(_l('topic_not_found'));
                }
            }

            // Check if this is step 2 (after image generation)
            $is_step_2 = isset($action_data['audit_step']) && $action_data['audit_step'] == 2;

            if (!$is_step_2) {
                // Step 1: Initial request to generate image
                $this->logActivity('Initiating image generation', $topic_id);
                log_message('debug', 'Ultimate Editor Image Generate action data: ' . json_encode($action_data));
                
                // Set initial state to processing
                $action_data['target_state'] = self::STATE_PROCESSING;
                $action_data['audit_step'] = 1;
            } else {
                // Step 2: Save the generated image
                $this->logActivity('Processing generated image', $topic_id);
                
                // Set state to completed
                $action_data['target_state'] = self::STATE_COMPLETED;
            }

            // Prepare data for n8n
            $n8n_data = $this->prepareN8nData($topic, $action_data);

            // Send to n8n
            if ($topic_id !== null) {
                log_message('debug', "Ultimate Editor Image Generate: Sending to N8N for topic {$topic_id}");
            } else {
                log_message('debug', "Ultimate Editor Image Generate: Sending to N8N (no specific topic)");
            }
            
            $result = send_to_n8n_from_ultimate_editor($action_data['workflow_id'], $n8n_data);
            
            if ($topic_id !== null) {
                log_message('debug', "Ultimate Editor Image Generate: N8N response for topic {$topic_id}: " . json_encode($result));
            } else {
                log_message('debug', "Ultimate Editor Image Generate: N8N response (no specific topic): " . json_encode($result));
            }

            // If successful n8n request
            if ($result['success']) {
                if ($action_data['audit_step'] == 1) {
                    // Get execution_id if available
                    $execution_id = null;
                    $workflow_id = $action_data['workflow_id'];
                    
                    if (isset($result['data']['response']['execution_id'])) {
                        $execution_id = $result['data']['response']['execution_id'];
                    } elseif (isset($result['data']['execution_id'])) {
                        $execution_id = $result['data']['execution_id'];
                    }

                    // Get workflow details
                    $workflow_details = $this->getWorkflowDetails($workflow_id, $execution_id);
                    
                    // Determine message based on workflow status
                    $message = $workflow_details['needs_polling'] 
                        ? _l('image_generation_listening') 
                        : _l('image_generation_initiated');

                    log_message('debug', "Step 1 success response for topic {$topic_id}: " . json_encode([
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
                    // Step 2: Process and save the feature image
                    $image_saved = false;
                    $image_url = '';
                    
                    // Try to extract image URL from response
                    if (isset($result['data']['response']['image_url'])) {
                        $image_url = $result['data']['response']['image_url'];
                    } elseif (isset($result['data']['response']['data']['image_url'])) {
                        $image_url = $result['data']['response']['data']['image_url'];
                    } elseif (isset($result['data']['response']['result']['image_url'])) {
                        $image_url = $result['data']['response']['result']['image_url'];
                    }
                    
                    // Save feature image if URL is available and topic exists
                    if (!empty($image_url) && $topic_id !== null) {
                        $image_saved = $this->saveFeatureImage($topic_id, $image_url);
                        log_message('debug', "Feature image saving result for topic {$topic_id}: " . ($image_saved ? 'Success' : 'Failed'));
                    }

                    return [
                        'success' => true,
                        'message' => $image_saved ? _l('feature_image_saved') : _l('image_generation_completed'),
                        'data' => [
                            'response' => $result['data']['response'],
                            'http_code' => $result['data']['http_code'] ?? 200,
                            'clear_button' => true,
                            'audit_step' => 2,
                            'image_url' => $image_url,
                            'image_saved' => $image_saved
                        ]
                    ];
                }
            } else {
                // Return the error from n8n
                return [
                    'success' => false,
                    'message' => $result['message'] ?? _l('image_generation_failed'),
                    'data' => [
                        'error_details' => $result['data']['error'] ?? null,
                        'http_code' => $result['data']['http_code'] ?? null,
                        'clear_button' => true
                    ]
                ];
            }
        } catch (Exception $e) {
            $topic_info = $topic_id !== null ? "for topic {$topic_id}" : "(no specific topic)";
            log_message('error', "Ultimate Editor Image Generate: Error {$topic_info}: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => [
                    'error_details' => $e->getMessage(),
                    'clear_button' => true
                ]
            ];
        }
    }

    /**
     * Get workflow execution details
     * 
     * @param string $workflow_id Workflow ID
     * @param string $execution_id Execution ID
     * @return array Workflow details
     */
    public function getWorkflowDetails($workflow_id, $execution_id) {
        try {
            log_message('debug', "Getting workflow details for workflow_id: " . $workflow_id . " and execution_id: " . $execution_id);
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
                log_message('debug', "Workflow details response: " . json_encode($data));
                
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
     * 
     * @param string $workflow_id Workflow ID
     * @param string $execution_id Execution ID
     * @return array Status result
     */
    public function checkWorkflowStatus($workflow_id, $execution_id) {
        $result = $this->getWorkflowDetails($workflow_id, $execution_id);
        
        // Add finished status to response
        $result['finished'] = isset($result['needs_polling']) ? !$result['needs_polling'] : false;
        
        return $result;
    }

    /**
     * Save feature image for a topic
     * 
     * @param int $topic_id Topic ID
     * @param string $image_url Image URL to save
     * @return bool Success status
     */
    private function saveFeatureImage($topic_id, $image_url) {
        try {
            if (empty($topic_id) || empty($image_url)) {
                log_message('error', "Cannot save feature image: Missing topic_id or image_url");
                return false;
            }

            log_message('debug', "Attempting to save feature image for topic {$topic_id} from URL: {$image_url}");
            
            // Load required models
            $this->CI->load->model('Topics_model');
            
            // Get topic by ID
            $topic = $this->CI->Topics_model->get_topic_by_topicid($topic_id);
            if (!$topic) {
                log_message('error', "Topic not found when saving feature image: {$topic_id}");
                return false;
            }
            
            // Update topic with feature image URL
            $update_data = [
                'feature_image' => $image_url,
                'dateupdated' => date('Y-m-d H:i:s')
            ];
            
            $updated = $this->CI->Topics_model->update_topic($topic->id, $update_data);
            
            if ($updated) {
                log_message('debug', "Feature image saved successfully for topic {$topic_id}");
                
                // Also update active draft if exists
                $this->CI->load->model('Topic_editor_draft_model');
                $draft = $this->CI->Topic_editor_draft_model->get_active_draft($topic->id);
                
                if ($draft) {
                    $draft_data = [
                        'feature_image' => $image_url,
                        'dateupdated' => date('Y-m-d H:i:s')
                    ];
                    
                    $this->CI->Topic_editor_draft_model->update($draft->id, $draft_data);
                    log_message('debug', "Feature image also updated in topic draft for topic {$topic_id}");
                }
                
                return true;
            } else {
                log_message('error', "Failed to update topic with feature image for topic {$topic_id}");
                return false;
            }

        } catch (Exception $e) {
            log_message('error', "Error saving feature image: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Prepare data for n8n
     * 
     * @param object|null $topic Topic object (optional)
     * @param array $action_data Action data
     * @return array Prepared data
     */
    private function prepareN8nData($topic = null, $action_data) {
        try {
            // Initialize result structure
            $result = [
                'workflow_data' => $action_data,
                'execution_data' => [
                    'timestamp' => date('Y-m-d H:i:s'),
                    'user_id' => get_staff_user_id(),
                    'source' => 'ultimate_editor_image_generate',
                    'target_type' => $action_data['target_type'] ?? self::TARGET_TYPE,
                    'target_state' => $action_data['target_state'] ?? self::STATE_COMPLETED,
                    'action_command' => $action_data['action_command'] ?? 'ImageHires',
                    'audit_step' => $action_data['audit_step'] ?? 1
                ]
            ];
            
            // Add topic data if topic is provided
            if ($topic !== null) {
                // Get base topic data
                $topic_array = (array)$topic;
                $topic_data = array_filter($topic_array, function($value) {
                    return !is_null($value);
                });
                
                $result['topic'] = $topic_array;
                $result['topic_data'] = $topic_data;
                
                // Get draft data
                $draft = $this->CI->Topic_editor_draft_model->get_active_draft($topic->id);
                if ($draft) {
                    $result['draft_data'] = (array)$draft;
                }
            }

            // Get controller data if controller_id is provided
            if (!empty($action_data['controller_id'])) {
                $this->CI->load->model('Topic_controller_model');
                $controller = $this->CI->Topic_controller_model->get($action_data['controller_id']);
                if ($controller) {
                    $result['controller_data'] = (array)$controller;
                }
            }
            
            // Add content data if available
            if (!empty($action_data['content'])) {
                $result['content_data'] = $action_data['content'];
            }

            return $result;
        } catch (Exception $e) {
            throw new Exception("Error preparing workflow data: " . $e->getMessage());
        }
    }

    /**
     * Validate inputs
     * 
     * @param int|null $topic_id Topic ID (optional)
     * @param array $action_data Action data
     * @return bool
     */
    public function validate($topic_id = null, $action_data) {
        try {
            // Check if topic exists only if topic_id is provided
            if ($topic_id !== null && !$this->checkTopicExists($topic_id)) {
                $this->addError(_l('topic_not_found'));
                return false;
            }

            // Check n8n settings
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

            // Validate image generation specific requirements (only for step 1)
            if (!isset($action_data['audit_step']) || $action_data['audit_step'] != 2) {
                if (!isset($action_data['target_type']) || $action_data['target_type'] !== self::TARGET_TYPE) {
                    $this->addError(_l('invalid_target_type'));
                    return false;
                }
            }

            return true;
        } catch (Exception $e) {
            $this->addError("Validation error: " . $e->getMessage());
            return false;
        }
    }
} 