<?php defined('BASEPATH') or exit('No direct script access allowed');


/**
 * Processor để xử lý việc mở link WordPress post
 */
class WordPressPostActionProcessor extends BaseTopicActionProcessor {
    protected $CI;

    public function __construct() {
        parent::__construct();
        $this->CI = &get_instance();
    }

    public function process($topic_id, $action_data) {
        try {
            // Get current topic to get topicid
            $current_topic = $this->CI->db->get_where(db_prefix() . 'topics', ['id' => $topic_id])->row();
            if (!$current_topic) {
                throw new Exception(_l('topic_not_found'));
            }

            // Get WordPress post topic with the same topicid
            $this->CI->db->select('topics.*');
            $this->CI->db->from(db_prefix() . 'topics topics');
            $this->CI->db->join(db_prefix() . 'topic_target target', 'topics.target_id = target.id', 'left');
            $this->CI->db->where([
                'topics.topicid' => $current_topic->topicid,
                'target.target_type' => 'WORDPRESS_POST',
                'topics.action_type_code' => 'ExecutionTag_ExecWriting',
                'topics.action_state_code' => 'ExecutionTag_ExecWriting_PostCompleted'
            ]);
            $wordpress_topic = $this->CI->db->get()->row();

            if (!$wordpress_topic) {
                return [
                    'success' => false,
                    'message' => _l('wordpress_post_not_found'),
                    'data' => [
                        'clear_button' => true,
                        'current_topic' => $current_topic
                    ]
                ];
            }

            // Extract WordPress URL from topic data
            $wordpress_url = $this->extractWordPressUrl($wordpress_topic);
            
            if (!$wordpress_url) {
                return [
                    'success' => false,
                    'message' => _l('wordpress_url_not_found'),
                    'data' => [
                        'clear_button' => true,
                        'wordpress_topic' => $wordpress_topic
                    ]
                ];
            }

            // Return success with URL to open
            return [
                'success' => true,
                'message' => _l('wordpress_url_found'),
                'data' => [
                    'url' => $wordpress_url,
                    'open_url' => true, // Signal frontend to open URL
                    'clear_button' => false
                ]
            ];

        } catch (Exception $e) {
            log_activity("Error processing WordPress post for topic {$topic_id}: " . $e->getMessage());
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

    private function extractWordPressUrl($topic) {
        try {
            if (!empty($topic->data)) {
                $data = json_decode(repair_json($topic->data), true);
                
                // Check different possible locations of the URL
                // 1. Check in guid.rendered
                if (!empty($data['guid']['rendered'])) {
                    return $data['guid']['rendered'];
                }
                
                // 2. Check direct link
                if (!empty($data['link'])) {
                    return $data['link'];
                }
                
                // Fallback: Check in wordpress_post object if exists
                if (!empty($data['wordpress_post']['link'])) {
                    return $data['wordpress_post']['link'];
                }
            }

            // Try to get URL from topic log as fallback
            if (!empty($topic->log)) {
                $log_data = json_decode(repair_json($topic->log), true);
                if (!empty($log_data['wordpress_url'])) {
                    return $log_data['wordpress_url'];
                }
            }

            return null;
        } catch (Exception $e) {
            log_activity("Error extracting WordPress URL: " . $e->getMessage());
            return null;
        }
    }

    public function validate($topic_id, $action_data) {
        if (!$this->checkTopicExists($topic_id)) {
            $this->addError(_l('topic_not_found'));
            return false;
        }
        return true;
    }
}
