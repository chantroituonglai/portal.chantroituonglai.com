<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Interface for Ultimate Editor action processors
 */
interface UltimateEditorActionProcessorInterface {
    /**
     * Process the action
     * @param int|null $topic_id ID of the topic (optional)
     * @param array $action_data Action data
     * @return array Response data
     */
    public function process($topic_id = null, $action_data);
    
    /**
     * Validate conditions before execution
     * @param int|null $topic_id ID of the topic (optional)
     * @param array $action_data Action data
     * @return bool
     */
    public function validate($topic_id = null, $action_data);
}

/**
 * Base class for Ultimate Editor action processors
 */
abstract class BaseUltimateEditorActionProcessor implements UltimateEditorActionProcessorInterface {
    protected $CI;
    protected $errors = [];

    public function __construct() {
        $this->CI = &get_instance();
    }

    /**
     * Get errors list
     * @return array
     */
    public function getErrors() {
        return $this->errors;
    }

    /**
     * Add error
     * @param string $error
     */
    protected function addError($error) {
        $this->errors[] = $error;
    }

    /**
     * Check if topic exists
     * @param int|null $topic_id
     * @return bool
     */
    protected function checkTopicExists($topic_id = null) {
        // If topic_id is null, skip the check and return true
        if ($topic_id === null) {
            return true;
        }
        
        $this->CI->db->where('topicid', $topic_id);
        return $this->CI->db->get(db_prefix() . 'topics')->row() !== null;
    }

    /**
     * Log activity
     * @param string $description
     * @param int|null $topic_id
     */
    protected function logActivity($description, $topic_id = null) {
        if ($topic_id !== null) {
            log_activity($description . ' [Topic ID: ' . $topic_id . ']');
        } else {
            log_activity($description . ' [No specific topic]');
        }
    }
}

/**
 * Factory class to create appropriate action processor
 */
class UltimateEditorActionProcessorFactory {
    /**
     * Create processor based on action type, state and command
     * @param string $action_type
     * @param string $action_state
     * @param string|null $action_command
     * @return UltimateEditorActionProcessorInterface|null
     */
    public static function create($action_type, $action_state, $action_command = null) {
        // Log the factory call for debugging
        log_message('debug', 'UltimateEditorActionProcessorFactory: Creating processor for Type: ' . $action_type . ', State: ' . $action_state . ', Command: ' . $action_command);
        
        switch ($action_type) {
            case 'ImageGenerateToggle':
                if ($action_state === 'GenImgCompleted') {
                    return new UltimateEditorImageGenerateToggleProcessor();
                }
                break;
                
            case 'InitGooglesheetRawItem':
                return new UltimateEditorInitGooglesheetRawItemProcessor();
                
            case 'TopicComposer':
                return new UltimateEditorTopicComposerProcessor();
                
            case 'WordPressPost':
                return new UltimateEditorWordPressPostProcessor();
                
            case 'WordPressPostSelection':
                return new UltimateEditorWordPressPostSelectionProcessor();
                
            case 'SocialMediaPost':
                return new UltimateEditorSocialMediaPostProcessor();
                
            case 'DraftWriting':
                if ($action_state === 'BuildPostStructure_B_Begin') {
                    return new UltimateEditorDraftWritingProcessor();
                }
                break;
                
            default:
                // If no specific processor is found, use generic processor
                log_activity('No specific processor found for action type: ' . $action_type);
                return new UltimateEditorGenericProcessor();
        }

        // If no specific processor for action state, use generic processor
        return new UltimateEditorGenericProcessor();
    }
}

/**
 * Helper function to process an action
 * @param int|null $topic_id Optional topic ID
 * @param array $action_data Action data
 * @return array Response data
 */
function process_ultimate_editor_action($topic_id = null, $action_data) {
    // Log action details
    if ($topic_id !== null) {
        log_activity('Processing Ultimate Editor action for topic: ' . $topic_id);
    } else {
        log_activity('Processing Ultimate Editor action (no specific topic)');
    }
    log_message('debug', 'Ultimate Editor action data: ' . json_encode($action_data));

    if (empty($action_data['target_type'])) {
        return [
            'success' => false,
            'message' => _l('missing_action_type'),
            'data' => [
                'reload' => false,
                'clear_button' => true
            ]
        ];
    }

    $processor = UltimateEditorActionProcessorFactory::create(
        $action_data['target_type'], 
        $action_data['target_state'], 
        $action_data['action_command'] ?? null
    );
    
    if (!$processor) {
        return [
            'success' => false,
            'message' => _l('invalid_action_type'),
            'data' => [
                'reload' => false,
                'clear_button' => true
            ]
        ];
    }

    if (!$processor->validate($topic_id, $action_data)) {
        return [
            'success' => false,
            'message' => implode(', ', $processor->getErrors()),
            'data' => [
                'reload' => false,
                'clear_button' => true
            ]
        ];
    }

    try {
        $result = $processor->process($topic_id, $action_data);
        
        if ($topic_id !== null) {
            log_message('debug', 'Ultimate Editor processor result for topic ' . $topic_id . ': ' . json_encode($result));
        } else {
            log_message('debug', 'Ultimate Editor processor result (no specific topic): ' . json_encode($result));
        }
        
        // Add clear_button to response if there's an error
        if (!$result['success']) {
            $result['data'] = array_merge(
                $result['data'] ?? [],
                ['clear_button' => true]
            );
        }

        return $result;
    } catch (Exception $e) {
        log_message('error', 'Ultimate Editor action processing error: ' . $e->getMessage());
        return [
            'success' => false,
            'message' => _l('action_processing_error'),
            'data' => [
                'reload' => false,
                'clear_button' => true,
                'error_details' => $e->getMessage()
            ]
        ];
    }
}

/**
 * Execute workflow for Ultimate Editor action
 * @param array $workflow_data Workflow data including all necessary parameters
 * @return array Response with success status and message
 */
function execute_ultimate_editor_workflow($workflow_data) {
    $CI = &get_instance();
    $step = 1;
    
    try {
        // Step 1: Validate required fields
        $required_fields = ['workflow_id'];
        
        // Add topic_id to required fields only if not in controller context
        if (!isset($workflow_data['controller_id'])) {
            $required_fields[] = 'topic_id';
        }
        
        // Add other conditional required fields
        if (isset($workflow_data['target_type'])) {
            $required_fields[] = 'target_state';
        }
        
        $missing_fields = array_filter($required_fields, function($field) use ($workflow_data) {
            return empty($workflow_data[$field]);
        });

        if (!empty($missing_fields)) {
            return [
                'success' => false,
                'message' => "Step {$step} - " . _l('missing_required_fields'),
                'missing_fields' => $missing_fields,
                'prompt_action' => 'configure_workflow',
                'current_data' => $workflow_data
            ];
        }

        // Step 2: Get topic data if topic_id is provided
        $step++;
        $topic = null;
        
        if (!empty($workflow_data['topic_id'])) {
            $CI->load->model('Topics_model');
            $topic = $CI->Topics_model->get_topic_by_topicid($workflow_data['topic_id']);
            if (!$topic) {
                throw new Exception("Step {$step} - " . _l('topic_not_found'));
            }
        }
        
        // Step 3: Prepare workflow data
        $step++;
        $n8n_data = prepare_ultimate_editor_workflow_data($topic, $workflow_data);
        
        // Step 4: Send to n8n
        $step++;
        $response = send_to_n8n_from_ultimate_editor($workflow_data['workflow_id'], $n8n_data);
        
        // Step 5: Log successful execution
        $step++;
        $log_msg = "Step {$step} - Ultimate Editor workflow executed successfully [Workflow: {$workflow_data['workflow_id']}";
        
        if (!empty($workflow_data['topic_id'])) {
            $log_msg .= ", Topic ID: {$workflow_data['topic_id']}";
        }
        
        if (!empty($workflow_data['controller_id'])) {
            $log_msg .= ", Controller ID: {$workflow_data['controller_id']}";
        }
        
        $log_msg .= "]";
        log_activity($log_msg);
        
        return [
            'success' => true,
            'message' => "Step {$step} - " . _l('workflow_executed_successfully'),
            'data' => $response
        ];
        
    } catch (Exception $e) {
        // Log error with current step
        $topic_info = !empty($workflow_data['topic_id']) ? "Topic ID: " . $workflow_data['topic_id'] : "No specific topic";
        $controller_info = !empty($workflow_data['controller_id']) ? ", Controller ID: " . $workflow_data['controller_id'] : "";
        
        log_activity(
            "Step {$step} - Ultimate Editor workflow execution failed [{$topic_info}{$controller_info}" . 
            ", Error: " . $e->getMessage() . "]"
        );
        
        return [
            'success' => false,
            'message' => $e->getMessage(),
            'step' => $step
        ];
    }
}

/**
 * Prepare workflow data for n8n
 * @param object|null $topic Topic object (optional)
 * @param array $workflow_data Workflow data
 * @return array Prepared data for n8n
 */
function prepare_ultimate_editor_workflow_data($topic = null, $workflow_data) {
    $CI = &get_instance();
    
    // Initialize base data structure
    $result = [
        'workflow_data' => $workflow_data,
        'execution_data' => [
            'timestamp' => date('Y-m-d H:i:s'),
            'user_id' => get_staff_user_id(),
            'source' => 'ultimate_editor_action_button'
        ]
    ];
    
    // Add topic data if topic is provided
    if ($topic) {
        // Get full topic data
        $topic_full = $CI->db->get_where(db_prefix() . 'topics', ['id' => $topic->id])->row();
        
        // Convert topic object to array
        $topic_array = $topic_full ? (array)$topic_full : [];
        
        // Filter out null values
        $topic_data = array_filter($topic_array, function($value) {
            return !is_null($value);
        });

        // Decode log data if available
        $log_data = [];
        if (!empty($topic_full->log)) {
            $decoded_log = json_decode($topic_full->log, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $log_data = $decoded_log;
            }
        }
        
        // Add topic-related data to result
        $result['topic_data'] = $topic_data;
        $result['log_data'] = $log_data;
        
        // Get draft data if available
        $CI->load->model('Topic_editor_draft_model');
        $draft = $CI->Topic_editor_draft_model->get_active_draft($topic->id);
        if ($draft) {
            $result['draft_data'] = (array)$draft;
        }
    }
    
    // Get controller data if available
    if (!empty($workflow_data['controller_id'])) {
        $CI->load->model('Topic_controller_model');
        $controller = $CI->Topic_controller_model->get($workflow_data['controller_id']);
        if ($controller) {
            $result['controller_data'] = (array)$controller;
        }
    }
    
    return $result;
}

/**
 * Send data to n8n from Ultimate Editor
 * @param string $workflow_id Workflow ID
 * @param array $data Data to send
 * @return array Response data
 */
function send_to_n8n_from_ultimate_editor($workflow_id, $data) {
    // Log what workflow we're sending to
    if (isset($data['topic_data']) && isset($data['topic_data']['id'])) {
        log_activity("Sending to N8N from Ultimate Editor for workflow {$workflow_id} with topic ID {$data['topic_data']['id']}");
    } else {
        log_activity("Sending to N8N from Ultimate Editor for workflow {$workflow_id} (no specific topic)");
    }
    
    log_message('debug', "Ultimate Editor N8N request data: " . json_encode($data));
    
    $step = 1;
    try {
        // Step 1: Validate n8n settings
        $n8n_host = get_option('topics_n8n_host');
        $n8n_webhook_url = get_option('topics_n8n_webhook_url');
        
        if (empty($n8n_host) && empty($n8n_webhook_url)) {
            throw new Exception("Step {$step} - " . _l('n8n_settings_missing'));
        }

        // Step 2: Prepare webhook URL
        $step++;
        $workflow_id = $workflow_id ?? $data['workflow_id'] ?? null;
        if (empty($workflow_id)) {
            throw new Exception("Step {$step} - Workflow ID is missing");
        }

        $webhook_url = $n8n_webhook_url ? 
                      rtrim($n8n_webhook_url, '/') . '/' . $workflow_id :
                      rtrim($n8n_host, '/') . '/webhook/' . $workflow_id;

        // Log the URL being called
        log_activity("Step {$step} - Ultimate Editor attempting to call N8N URL: {$webhook_url}");

        // Step 3: Initialize curl
        $step++;
        $ch = curl_init($webhook_url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

        // Step 4: Execute request
        $step++;
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        if ($curl_error || ($http_code < 200 || $http_code >= 300)) {
            $error_context = [
                'workflow_id' => $workflow_id,
                'url' => $webhook_url,
                'http_code' => $http_code,
                'curl_error' => $curl_error,
                'response' => substr($response, 0, 1000)
            ];
            log_activity("Ultimate Editor N8N request failed: " . json_encode($error_context));
        }

        // Parse response
        $parsed_response = null;
        if ($response) {
            log_message('debug', "Ultimate Editor N8N raw response: " . $response);
            try {
                $parsed_response = json_decode($response, true);
                log_message('debug', "Ultimate Editor N8N parsed response: " . json_encode($parsed_response));
            } catch (Exception $e) {
                log_message('error', "Error parsing Ultimate Editor N8N response: " . $e->getMessage());
            }
        }

        // Return response with details
        return [
            'success' => ($http_code >= 200 && $http_code < 300),
            'message' => $curl_error ? $curl_error : ($http_code >= 200 && $http_code < 300 ? 'Success' : 'HTTP Error'),
            'data' => [
                'response' => $parsed_response,
                'http_code' => $http_code,
                'url' => $webhook_url,
                'error' => $curl_error,
                'clear_button' => ($http_code < 200 || $http_code >= 300),
                'execution_id' => $parsed_response['execution_id'] ?? null
            ]
        ];

    } catch (Exception $e) {
        log_activity("Ultimate Editor N8N Request Exception: " . $e->getMessage());
        
        return [
            'success' => false,
            'message' => "Error executing N8N request: " . $e->getMessage(),
            'data' => [
                'error_details' => $e->getMessage(),
                'step' => $step,
                'clear_button' => true
            ]
        ];
    }
}