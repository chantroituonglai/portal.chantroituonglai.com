<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 *Select Post Type
* Available Options
* Write Content Create new content for the post
* Write Social & Meta Create social media content and meta descriptions
 */
class WordPressPostSelectionProcessor extends BaseTopicActionProcessor {
    private const TARGET_TYPE = 'WORDPRESS_POST';

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

            // Check if this is a selection response
            $is_from_selection = isset($action_data['from_selection']) && $action_data['from_selection'];
            
            if ($is_from_selection) {
                if (empty($action_data['selected_option'])) {
                    throw new Exception(_l('missing_required_fields'));
                }
                $action_data['step'] = 2;

                // Check controller assignment for step 2
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
            } else {
                $action_data['step'] = 1;
            }

            // Prepare N8N data
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

                if ($result['success']) {
                    if ($action_data['step'] == 1) {
                        // Return options for selection
                        if (isset($result['data']['response']['data']) && 
                            is_array($result['data']['response']['data'])) {
                            return [
                                'success' => true,
                                'message' => _l('select_wordpress_post_options'),
                                'data' => [
                                    'options' => $result['data']['response']['data'],
                                    'show_selection' => true,
                                    'clear_button' => false,
                                    'step' => 1
                                ]
                            ];
                        }
                    } else {
                        // Step 2: Post completed successfully
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
        try {
            $topic_array = (array)$topic;
            $topic_data = array_filter($topic_array, function($value) {
                return !is_null($value);
            });

            // Group controller data into a single object
            $controller_data = [];
            $controller_fields = [
                'site', 'platform', 'blog_id', 'logo_url', 'project_id',
                 'page_mapping',
                'slogan', 'writing_style', 'action_1', 'action_2'
            ];

            foreach ($controller_fields as $field) {
                $key = 'controller_' . $field;
                if (isset($action_data[$key])) {
                    $controller_data[$field] = $action_data[$key];
                    unset($action_data[$key]); // Remove from main workflow data
                }
            }

            return [
                'workflow_data' => $action_data,
                'topic' => $topic_array,
                'topic_data' => $topic_data,
                'controller_data' => $controller_data,
                'execution_data' => [
                    'timestamp' => date('Y-m-d H:i:s'),
                    'user_id' => get_staff_user_id(),
                    'source' => self::TARGET_TYPE,
                    'action' => 'wordpress_post'
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