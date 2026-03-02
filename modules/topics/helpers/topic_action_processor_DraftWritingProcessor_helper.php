<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Processor cho Viết nháp
 */
class DraftWritingProcessor extends BaseTopicActionProcessor {
    private const TARGET_TYPE = 'BuildPostStructure';
    private const TARGET_STATE = 'BuildPostStructure_B_Begin';

    // Định nghĩa cấu trúc dữ liệu cần thiết
    private const REQUIRED_FIELDS = [
        'web-scraper-order',
        'Topic-href',
        'Topic',
        'Title',
        'Summary',
        'Item_Position',
        'Item_Title', 
        'Item_Content'
    ];

    /**
     * Xử lý action
     */
    public function process($topic_id, $action_data) {
        try {
            $step = $action_data['audit_step'] ?? 1;
            
            log_message('error', "Processing Draft Writing Step {$step}");
            if ($step == 1) {
                $action_data['audit_step'] = 1;
                return $this->processStep1($topic_id, $action_data);
            }
            
            throw new Exception("Invalid step: {$step}");
        } catch (Exception $e) {
            log_message('error', "Error in Draft Writing for topic {$topic_id}: " . $e->getMessage());
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

    private function processStep1($topic_id, $action_data) {
        // Get topic data
        $topic = $this->CI->db->get_where(db_prefix() . 'topics', ['id' => $topic_id])->row();
        if (!$topic) {
            throw new Exception(_l('topic_not_found'));
        }
        // Prepare N8N data
        $n8n_data = $this->prepareN8nData($topic, $action_data);

        // Send to N8N
        log_message('error', "Sending to N8N for topic {$topic_id} (Step 1)");
        $result = send_to_n8n($action_data['workflow_id'], $n8n_data);
        log_message('error', "N8N response for topic {$topic_id}: " . json_encode($result));

        // Kiểm tra response từ N8N
        if ($result['success'] && isset($result['data']['response']['success']) && 
            $result['data']['response']['success'] === true) {
            
            return [
                'success' => true,
                'message' => _l('workflow_executed_successfully'),
                'data' => [
                    'response' => $result['data']['response'],
                    'http_code' => $result['data']['http_code'] ?? 200,
                    'clear_button' => false,
                    'audit_step' => 1,
                    'needs_polling' => true,
                    'workflow_id' => $action_data['workflow_id'],
                    'execution_id' => $result['data']['execution_id'] ?? null,
                    'response_text' => json_encode($result['data'])
                ]
            ];
        }

        throw new Exception($result['message'] ?? _l('workflow_execution_failed'));
    }

    /**
     * Prepare data for N8N
     */
    private function prepareN8nData($topic, $action_data) {
        $data = [
            'workflow_data' => $action_data,
            'topic' => (array)$topic,
            'execution_data' => [
                'timestamp' => date('Y-m-d H:i:s'),
                'user_id' => get_staff_user_id(),
                'source' => self::TARGET_TYPE,
                'audit_step' => $action_data['audit_step']
            ]
        ];

        return $data;
    }

    /**
     * Validate input data
     */
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