<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Processor cho action social media post
 */
class SocialMediaPostActionProcessor extends BaseTopicActionProcessor {
    private const TARGET_TYPE = 'SOCIAL_MEDIA_POST';

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
            log_activity("Topic data: " . json_encode($action_data));
            // Kiểm tra xem có phải request từ form chọn page không
            $is_from_selection = isset($action_data['from_selection']) && $action_data['from_selection'];
            
            // Nếu là request từ form chọn page (step 2)
            if ($is_from_selection) {
                if (empty($action_data['selected_page_id']) || empty($action_data['post_type'])) {
                    throw new Exception(_l('missing_required_fields'));
                }
                $action_data['step'] = 2;
            } else {
                // Đây là request đầu tiên (step 1)
                $action_data['step'] = 1;
            }

            // Prepare N8N data safely
            try {
                $n8n_data = $this->prepareN8nData($topic, $action_data);
            } catch (Exception $e) {
                log_activity("Error preparing N8N data for topic {$topic_id}: " . $e->getMessage());
                throw new Exception("Error preparing workflow data: " . $e->getMessage());
            }

            // Send to N8N
            try {
                log_activity("Sending to N8N for topic {$topic_id}. Step: " . $action_data['step']);
                $result = send_to_n8n($action_data['workflow_id'], $n8n_data);
                log_activity("N8N response for topic {$topic_id}: " . json_encode($result));

                // Kiểm tra response từ N8N
                if ($result['success']) {
                    if ($action_data['step'] == 1) {
                        // Step 1: Trả về danh sách pages để chọn
                        if (isset($result['data']['response']['data']) && 
                            is_array($result['data']['response']['data'])) {
                            return [
                                'success' => true,
                                'message' => _l('select_fanpage_and_post_type'),
                                'data' => [
                                    'pages' => $result['data']['response']['data'],
                                    'show_selection' => true,
                                    'clear_button' => false,
                                    'step' => 1
                                ]
                            ];
                        }
                    } else {
                        // Step 2: Đã post thành công
                        return [
                            'success' => true,
                            'message' => _l('workflow_executed_successfully'),
                            'data' => [
                                'response' => $result['data']['response'],
                                'http_code' => $result['data']['http_code'] ?? 200,
                                'clear_button' => true,
                                'step' => 2,
                                'lock_selection' => true
                            ]
                        ];
                    }
                }
                
                throw new Exception($result['message'] ?? _l('workflow_execution_failed'));
            } catch (Exception $e) {
                log_activity("N8N communication error for topic {$topic_id}: " . $e->getMessage());
                throw new Exception("N8N communication error: " . $e->getMessage());
            }
        } catch (Exception $e) {
            log_activity("Error executing workflow for topic {$topic_id}: " . $e->getMessage());
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
        // Tương tự như InitGooglesheetRawItemProcessor
        try {
            $topic_array = (array)$topic;
            $topic_data = array_filter($topic_array, function($value) {
                return !is_null($value);
            });

            $log_data = [];
            if (!empty($topic->log)) {
                try {
                    $decoded_log = json_decode(repair_json($topic->log), true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $log_data = $decoded_log;
                    }
                } catch (Exception $e) {
                    log_activity("Warning: Error decoding topic log: " . $e->getMessage());
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
                    'action' => 'social_media_post'
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
