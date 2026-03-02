<?php defined('BASEPATH') or exit('No direct script access allowed');


/**
 * Processor cho action init với GOOGLESHEET_RAW_ITEM
 */
class InitGooglesheetRawItemProcessor extends BaseTopicActionProcessor {
    private const TARGET_TYPE = 'GOOGLESHEET_RAW_ITEM';
    private const _predefined_action_data = [
        'MappedColumn' => [
            [
                'field' => 'GenerateSearchKeyword',
                'value' => 'Có'
            ]
        ]
    ];

    /**
     * Xử lý action
     * @param int $topic_id ID của topic
     * @param array $action_data Dữ liệu action
     * @return array Response data
     */
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

            // append predefined action data
            $action_data = array_merge_recursive($action_data, self::_predefined_action_data);

            // Prepare N8N data safely
            try {
                $n8n_data = $this->prepareN8nData($topic, $action_data);
            } catch (Exception $e) {
                log_activity("Error preparing N8N data for topic {$topic_id}: " . $e->getMessage());
                throw new Exception("Error preparing workflow data: " . $e->getMessage());
            }

            // Send to N8N
            try {
                log_activity("Sending to N8N for topic {$topic_id}");
                $result = send_to_n8n($action_data['workflow_id'], $n8n_data);
                log_activity("N8N response for topic {$topic_id}: " . json_encode($result));

                // Kiểm tra response từ N8N
                if ($result['success'] && 
                    isset($result['data']['response']['success']) && 
                    $result['data']['response']['success'] === true) {
                    
                    try {
                        // Update topic status
                        $this->updateTopicStatus($topic_id, $action_data);
                        
                        // Get topic master record using topic ID
                        $_topic = $this->CI->db->get_where(db_prefix() . 'topics', ['id' => $topic_id])->row();
                        
                        if ($_topic) {
                            // Update topic master active status = 1 using the correct ID
                            $this->CI->db->where('topicid', $_topic->topicid);
                            $this->CI->db->update(db_prefix() . 'topic_master', ['status' => 1]);
                        } 
                        
                        // Log successful execution
                        log_activity("Workflow executed successfully for topic {$topic_id}");

                        return [
                            'success' => true,
                            'message' => _l('workflow_executed_successfully'),
                            'data' => [
                                'response' => $result['data']['response'],
                                'http_code' => $result['data']['http_code'] ?? 200,
                                'clear_button' => $result['data']['clear_button'] ?? false
                            ]
                        ];
                    } catch (Exception $e) {
                        throw new Exception("Post-execution error: " . $e->getMessage());
                    }
                } else {
                    // Update topic master active status = 0 when failed
                    // Try both ID and topicid
                    $_topic = $this->CI->db->get_where(db_prefix() . 'topics', ['id' => $topic_id])->row();
                    if ($_topic) {
                        $this->CI->db->where('topicid', $_topic->topicid);
                    } 
                    $this->CI->db->update(db_prefix() . 'topic_master', ['status' => 0]);
                    
                    $error_message = isset($result['data']['error']) && !empty($result['data']['error']) 
                        ? $result['data']['error'] 
                        : ($result['message'] ?? _l('workflow_execution_failed'));
                    
                    throw new Exception($error_message);
                }
            } catch (Exception $e) {
                log_activity("N8N communication error for topic {$topic_id}: " . $e->getMessage());
                throw new Exception("N8N communication error: " . $e->getMessage());
            }
        } catch (Exception $e) {
            // Log error with details
            log_activity("Error executing workflow for topic {$topic_id}: " . $e->getMessage());
            
            // Update topic master active status = 0 when exception occurs
            // Try both ID and topicid
            $_topic = $this->CI->db->get_where(db_prefix() . 'topics', ['id' => $topic_id])->row();
            if ($_topic) {
                $this->CI->db->where('topicid', $_topic->topicid);
            } 
            $this->CI->db->update(db_prefix() . 'topic_master', ['status' => 0]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => [
                    'topic_id' => $topic_id,
                    'error_details' => $e->getMessage(),
                    'clear_button' => true
                ]
            ];
        }
    }

    private function prepareN8nData($topic, $action_data) {
        try {
            // Convert topic object to array safely
            $topic_array = (array)$topic;
            
            // Filter null values
            $topic_data = array_filter($topic_array, function($value) {
                return !is_null($value);
            });

            // Safely decode log data
            $log_data = [];
            if (!empty($topic->log)) {
                try {
                    $decoded_log = json_decode(repair_json($topic->log), true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $log_data = $decoded_log;
                    } else {
                        log_activity("Warning: Invalid JSON in topic log for topic {$topic->id}");
                        // Continue with empty log data instead of throwing exception
                    }
                } catch (Exception $e) {
                    log_activity("Warning: Error decoding topic log: " . $e->getMessage());
                    // Continue with empty log data
                }
            }

            // Safely decode topic data if exists
            $topic_data = [];
            if (!empty($topic->data)) {
                try {
                    $decoded_data = json_decode(repair_json($topic->data), true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $topic_data = $decoded_data;
                    } else {
                        log_activity("Warning: Invalid JSON in topic data for topic {$topic->id}");
                    }
                } catch (Exception $e) {
                    log_activity("Warning: Error decoding topic data: " . $e->getMessage());
                }
            }

            return [
                'workflow_data' => $action_data,
                'topic' => $topic_array,
                'topic_data' => $topic_data,
                'log_data' => $log_data,
                'execution_data' => [
                    'timestamp' => date('Y-m-d H:i:s'),
                    'user_id' => get_staff_user_id(),
                    'source' => self::TARGET_TYPE,
                    'action' => 'init'
                ]
            ];
        } catch (Exception $e) {
            log_activity("Error preparing N8N data for topic {$topic_id}: " . $e->getMessage());
            throw new Exception("Error preparing workflow data: " . $e->getMessage());
        }
    }

    /**
     * Update topic status (We will write this function in the future)
     * @param int $topic_id
     * @param array $action_data
     */
    private function updateTopicStatus($topic_id, $action_data) {
        try {
            $update_data = [
                // 'action_type_code' => $action_data['target_type'],
                // 'action_state_code' => $action_data['target_state'],
                // 'datemodified' => date('Y-m-d H:i:s'),
                // 'modified_by' => get_staff_user_id()
            ];

            // $this->CI->db->where('id', $topic_id);
            // if (!$this->CI->db->update(db_prefix() . 'topics', $update_data)) {
            //     throw new Exception("Failed to update topic status");
            // }

            return true;
        } catch (Exception $e) {
            throw new Exception("Error updating topic status: " . $e->getMessage());
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