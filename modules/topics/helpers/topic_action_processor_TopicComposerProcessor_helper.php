<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Processor cho Topic Composer
 */
class TopicComposerProcessor extends BaseTopicActionProcessor {
    private const TARGET_TYPE = 'TOPIC_COMPOSER';
    private const _predefined_action_data = [
        // 'MappedColumn' => [
        //     [
        //         'field' => 'TopicComposer',
        //         'value' => 'Có'
        //     ]
        // ]
    ];

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
     * @param int $topic_id ID của topic
     * @param array $action_data Dữ liệu action
     * @return array Response data
     */
    public function process($topic_id, $action_data) {
        try {
            $step = $action_data['audit_step'] ?? 1;
            
            log_message('error', "Processing TopicComposer Step {$step}");
            
            switch ($step) {
                case 1:
                    return $this->processStep1($topic_id, $action_data);
                
                case 2:
                    if (!isset($action_data['changes_data'])) {
                        throw new Exception("Missing changes data for step 2");
                    }
                    return $this->processStep2($topic_id, $action_data['changes_data'], $action_data);
                
                case 5:
                    if (!isset($action_data['changes_data'])) {
                        throw new Exception("Missing changes data for step 5");
                    }
                    return $this->processStep5($topic_id, $action_data['changes_data'], $action_data);

                case 6:
                    if (!isset($action_data['changes_data']['image_url'])) {
                        throw new Exception("Missing image URL for step 6");
                    }
                    return $this->processStep6($topic_id, $action_data['changes_data']['image_url'], $action_data);
                
                default:
                    throw new Exception("Invalid step: {$step}");
            }
        } catch (Exception $e) {
            log_message('error', "Error in TopicComposer for topic {$topic_id}: " . $e->getMessage());
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

        // Merge với predefined data
        $action_data = array_merge_recursive($action_data, self::_predefined_action_data);
        $action_data['audit_step'] = 1;
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
                    // 'audit_step' => 1,
                    'response_text' => json_encode($result['data'])
                ]
            ];
        }

        throw new Exception($result['message'] ?? _l('workflow_execution_failed'));
    }

    private function processStep2($topic_id, $changes_data, $action_data) {
        // Get topic data
        $topic = $this->CI->db->get_where(db_prefix() . 'topics', ['id' => $topic_id])->row();
        if (!$topic) {
            throw new Exception(_l('topic_not_found'));
        }

        // Merge changes data vào workflow data
        $action_data = array_merge($action_data, [
            'changes_data' => $changes_data,
            'audit_step' => 2
        ]);

        // Prepare N8N data với changes
        $n8n_data = $this->prepareN8nData($topic, $action_data);

        // Gửi request tới N8N
        $result = send_to_n8n($action_data['workflow_id'], $n8n_data);

        if ($result['success']) {
            return [
                'success' => true,
                'message' => _l('changes_applied_successfully'),
                'data' => [
                    'response' => $result['data']['response'],
                    'http_code' => $result['data']['http_code'] ?? 200,
                    'clear_button' => true,
                    'audit_step' => 2,
                    'needs_polling' => true,
                    'reload' => true
                ]
            ];
        }

        throw new Exception($result['message'] ?? _l('error_applying_changes'));
    }

    private function processStep5($topic_id, $changes_data, $action_data) {
        // Get topic data
        $topic = $this->CI->db->get_where(db_prefix() . 'topics', ['id' => $topic_id])->row();
        if (!$topic) {
            throw new Exception(_l('topic_not_found'));
        }

        // Merge với predefined data
        $action_data = array_merge_recursive($action_data, self::_predefined_action_data);
        $action_data['audit_step'] = 5;

        // Prepare N8N data sử dụng hàm có sẵn
        $n8n_data = $this->prepareN8nData($topic, $action_data);

        // Send to N8N
        log_message('error', "Sending to N8N for topic {$topic_id} (Step 5)");
        $result = send_to_n8n($action_data['workflow_id'], $n8n_data);
        log_message('error', "N8N response for topic {$topic_id}: " . json_encode($result));

        // Kiểm tra response từ N8N - giống logic hiện có
        if ($result['success'] && isset($result['data']['response']['success']) && 
            $result['data']['response']['success'] === true) {
            
            return [
                'success' => true,
                'message' => _l('workflow_executed_successfully'),
                'data' => [
                    'response' => $result['data']['response'],
                    'http_code' => $result['data']['http_code'] ?? 200,
                    'clear_button' => false,
                    'audit_step' => 5,
                    'needs_polling' => false, // Không cần polling cho quick save
                    'workflow_id' => $action_data['workflow_id'],
                    'execution_id' => $result['data']['execution_id'] ?? null,
                    'response_text' => json_encode($result['data']),
                    'refresh_items' => true // Flag để frontend refresh list items
                ]
            ];
        }

        throw new Exception($result['message'] ?? _l('workflow_execution_failed'));
    }

    /**
     * Process Step 6 - Download image to server
     */
    private function processStep6($topic_id, $image_url, $action_data) {
        try {
            // Get topic data
            $topic = $this->CI->db->get_where(db_prefix() . 'topics', ['id' => $topic_id])->row();
            if (!$topic) {
                throw new Exception(_l('topic_not_found'));
            }

            // Get topic master data
            $this->CI->load->model('Topic_master_model');
            $topic_master = $this->CI->Topic_master_model->get_by_topicid($topic->topicid);
            if (!$topic_master) {
                throw new Exception(_l('topic_master_not_found'));
            }

            // Merge với predefined data
            $action_data = array_merge_recursive($action_data, self::_predefined_action_data);
            $action_data['audit_step'] = 6;
            
            // Add image data to workflow data
            $action_data['external_data'] = [
                'url' => $image_url,
                'rel_id' => md5($image_url),
                'rel_type' => 'image'
            ];

            // Prepare N8N data
            $n8n_data = $this->prepareN8nData($topic, $action_data);

            // Send to N8N
            log_message('error', "Sending to N8N for topic {$topic_id} (Step 6 - Download Image)");
            $result = send_to_n8n($action_data['workflow_id'], $n8n_data);
            log_message('error', "N8N response for topic {$topic_id}: " . json_encode($result));

            // Kiểm tra response từ N8N
            if ($result['success'] && isset($result['data']['response']['success']) && 
                $result['data']['response']['success'] === true) {
                log_message('error', "Processing Step 6 for topic {$topic_id} ");
                
                // Save external data
                $this->CI->load->model('Topic_external_data_model');
                $wordpress_data = $result['data']['response']['data'];
                log_message('error', "N8N response for topic {$topic_id}: " . json_encode($wordpress_data));
                
                $external_data = [
                    'topic_master_id' => $topic_master->id, // Use correct topic_master id
                    'rel_type' => 'image',
                    'rel_id' => md5($image_url),
                    'rel_data' => $wordpress_data['guid']['raw'], // URL của ảnh trên WordPress
                    'rel_data_raw' => json_encode($wordpress_data) // Lưu toàn bộ data
                ];
                
                $save_result = $this->CI->Topic_external_data_model->save($external_data);
                
                if (!$save_result['success']) {
                    throw new Exception('Failed to save external data: ' . $save_result['message']);
                }

                return [
                    'success' => true,
                    'message' => _l('image_downloaded_successfully'),
                    'data' => [
                        'response' => $result['data']['response'],
                        'http_code' => $result['data']['http_code'] ?? 200,
                        'clear_button' => false,
                        'audit_step' => 6,
                        'needs_polling' => false,
                        'workflow_id' => $action_data['workflow_id'],
                        'execution_id' => $result['data']['execution_id'] ?? null,
                        'response_text' => json_encode($result['data']),
                        'image_url' => $image_url,
                        'wordpress_url' => $wordpress_data['guid']['raw'],
                        'rel_id' => md5($image_url)
                    ]
                ];
            }

            throw new Exception($result['message'] ?? _l('error_downloading_image'));
        } catch (Exception $e) {
            log_message('error', "Error in processStep6: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Process và validate topic data
     */
    private function processTopicData($data) {
        if (!isset($data['data'])) {
            throw new Exception("Invalid data structure");
        }

        $topic_data = $data['data'];

        // Validate required fields
        foreach (self::REQUIRED_FIELDS as $field) {
            if (!isset($topic_data[$field])) {
                throw new Exception("Missing required field: {$field}");
            }
        }

        // Process images
        $images = [];
        if (!empty($topic_data['item_Pictures'])) {
            foreach ($topic_data['item_Pictures'] as $img) {
                if (isset($img['item_Pictures-src'])) {
                    $images[] = $img['item_Pictures-src'];
                }
            }
        }

        // Process full images
        $full_images = [];
        if (!empty($topic_data['item_Pictures_Full'])) {
            foreach ($topic_data['item_Pictures_Full'] as $img) {
                if (isset($img['item_Pictures_Full-large_src'])) {
                    $full_images[] = $img['item_Pictures_Full-large_src'];
                }
            }
        }

        // Add processed images
        $topic_data['processed_images'] = [
            'thumbnails' => $images,
            'full' => $full_images
        ];

        // Process keywords
        if (!empty($topic_data['TopicKeywords'])) {
            $topic_data['keywords'] = array_map('trim', explode(',', $topic_data['TopicKeywords']));
        }

        return $topic_data;
    }

    /**
     * Format content for display
     */
    private function formatContent($data) {
        log_message('error', "Formatting content for topic {$data['Topic-href']} , Item_Content: " . $data['Item_Content']);
        return [
            'title' => $data['Title'],
            'summary' => $data['Summary'],
            'main_content' => [
                'position' => $data['Item_Position'],
                'title' => $data['Item_Title'],
                'content' => $data['Item_Content']
            ],
            'images' => $data['processed_images'],
            'footer' => $data['Topic_footer'] ?? '',
            'keywords' => $data['keywords'] ?? [],
            'metadata' => [
                'scraper_order' => $data['web-scraper-order'],
                'source_url' => $data['web-scraper-start-url'],
                'topic_url' => $data['Topic-href']
            ]
        ];
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

        // Thêm changes data nếu có
        if (isset($action_data['changes_data'])) {
            $data['changes_data'] = $action_data['changes_data'];
        }

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

    /**
     * Update topic status
     */
    private function updateTopicStatus($topic_id, $action_data) {
        try {
            // Implement status update logic if needed
            return true;
        } catch (Exception $e) {
            throw new Exception("Error updating topic status: " . $e->getMessage());
        }
    }
}
