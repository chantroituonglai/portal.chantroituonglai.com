<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Ultimate Editor Generic Processor
 * 
 * This processor is a simple pass-through to n8n without any specialized processing.
 * It sends the request to n8n and returns the response directly.
 */
class UltimateEditorGenericProcessor extends BaseUltimateEditorActionProcessor {
    protected $CI;

    public function __construct() {
        parent::__construct();
        $this->CI = &get_instance();
        
        // Load required models
        $this->CI->load->model('Topics_model');
        $this->CI->load->model('Topic_editor_draft_model');
    }

    /**
     * Process the action by sending to n8n and returning the response
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

            // Log the action
            $this->logActivity('Processing generic Ultimate Editor action', $topic_id);
            log_message('error', 'Ultimate Editor Generic action data: ' . json_encode($action_data));

            // Prepare data for n8n
            $n8n_data = $this->prepareN8nData($topic, $action_data);

            // Send to n8n
            if ($topic_id !== null) {
                log_message('error', "Ultimate Editor Generic: Sending to N8N for topic {$topic_id}");
            } else {
                log_message('error', "Ultimate Editor Generic: Sending to N8N (no specific topic)");
            }
            
            $result = send_to_n8n_from_ultimate_editor($action_data['workflow_id'], $n8n_data);
            
            if ($topic_id !== null) {
                log_message('error', "Ultimate Editor Generic: N8N response for topic {$topic_id}: " . json_encode($result));
            } else {
                log_message('error', "Ultimate Editor Generic: N8N response (no specific topic): " . json_encode($result));
            }

            // If successful n8n request
            if ($result['success']) {
                // Get execution_id if available
                $execution_id = null;
                if (isset($result['data']['response']['execution_id'])) {
                    $execution_id = $result['data']['response']['execution_id'];
                } elseif (isset($result['data']['execution_id'])) {
                    $execution_id = $result['data']['execution_id'];
                }

                // Determine if polling is needed based on execution_id
                $needs_polling = !empty($execution_id);

                return [
                    'success' => true,
                    'message' => $needs_polling ? _l('workflow_execution_started') : _l('action_processed_successfully'),
                    'data' => [
                        'response' => $result['data']['response'] ?? null,
                        'workflow_id' => $action_data['workflow_id'],
                        'execution_id' => $execution_id,
                        'http_code' => $result['data']['http_code'] ?? 200,
                        'clear_button' => !$needs_polling,
                        'needs_polling' => $needs_polling
                    ]
                ];
            } else {
                // Return the error from n8n
                return [
                    'success' => false,
                    'message' => $result['message'] ?? _l('workflow_execution_failed'),
                    'data' => [
                        'error_details' => $result['data']['error'] ?? null,
                        'http_code' => $result['data']['http_code'] ?? null,
                        'clear_button' => true
                    ]
                ];
            }
        } catch (Exception $e) {
            $topic_info = $topic_id !== null ? "for topic {$topic_id}" : "(no specific topic)";
            log_message('error', "Ultimate Editor Generic: Error {$topic_info}: " . $e->getMessage());
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
                    'source' => 'ultimate_editor_generic_action',
                    'target_type' => $action_data['target_type'] ?? 'unknown',
                    'target_state' => $action_data['target_state'] ?? 'unknown'
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
            
            // Add thumbnail data if available
            if (!empty($action_data['thumbnail'])) {
                $result['thumbnail_data'] = $action_data['thumbnail'];
                
                // For backward compatibility, also add thumbnail info to content
                if (isset($result['content_data']) && is_array($result['content_data'])) {
                    $result['content_data']['thumbnail_url'] = $action_data['thumbnail']['url'] ?? '';
                    $result['content_data']['thumbnail_id'] = $action_data['thumbnail']['id'] ?? '';
                }
                
                log_message('debug', 'Ultimate Editor: Added thumbnail data to n8n payload: ' . json_encode($action_data['thumbnail']));
            } else {
                log_message('debug', 'Ultimate Editor: No thumbnail data found in action data');
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

            return true;
        } catch (Exception $e) {
            $this->addError("Validation error: " . $e->getMessage());
            return false;
        }
    }
} 