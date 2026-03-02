<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Interface cho các action processor
 */
interface TopicActionProcessorInterface {
    /**
     * Xử lý action
     * @param int $topic_id ID của topic
     * @param array $action_data Dữ liệu action
     * @return array Response data
     */
    public function process($topic_id, $action_data);
    
    /**
     * Kiểm tra điều kiện trước khi thực thi
     * @param int $topic_id ID của topic
     * @param array $action_data Dữ liệu action
     * @return bool
     */
    public function validate($topic_id, $action_data);
}

/**
 * Class cơ sở cho các action processor
 */
abstract class BaseTopicActionProcessor implements TopicActionProcessorInterface {
    protected $CI;
    protected $errors = [];

    public function __construct() {
        $this->CI = &get_instance();
    }

    /**
     * Lấy danh sách lỗi
     * @return array
     */
    public function getErrors() {
        return $this->errors;
    }

    /**
     * Thêm lỗi
     * @param string $error
     */
    protected function addError($error) {
        $this->errors[] = $error;
    }

    /**
     * Kiểm tra topic có tồn tại
     * @param int $topic_id
     * @return bool
     */
    protected function checkTopicExists($topic_id) {
        $this->CI->db->where('id', $topic_id);
        return $this->CI->db->get(db_prefix() . 'topics')->row() !== null;
    }

    /**
     * Tạo backup topic trước khi xử lý
     * @param int $topic_id
     * @return int|bool ID của backup hoặc false nếu thất bại
     */
    protected function createBackup($topic_id) {
        // $this->CI->db->where('id', $topic_id);
        // $current_topic = $this->CI->db->get(db_prefix() . 'topics')->row();
        
        // if ($current_topic) {
        //     $backup_topic = (array)$current_topic;
        //     unset($backup_topic['id']);
            
        //     $backup_topic['status'] = 0;
        //     $backup_topic['target_id'] = 4; // Backup target ID
        //     $backup_topic['log'] = json_encode([
        //         'backup_date' => date('Y-m-d H:i:s'),
        //         'backup_reason' => 'action_button_click',
        //         'backup_from_id' => $topic_id
        //     ]);
            
        //     $this->CI->db->insert(db_prefix() . 'topics', $backup_topic);
        //     return $this->CI->db->insert_id();
        // }
        return false;
    }

    /**
     * Log hoạt động
     * @param string $description
     * @param int $topic_id
     */
    protected function logActivity($description, $topic_id) {
        log_activity($description . ' [Topic ID: ' . $topic_id . ']');
    }
}

/**
 * Factory class để tạo action processor phù hợp
 */
class TopicActionProcessorFactory {
    /**
     * Tạo processor phù hợp dựa trên action type, state và command
     * @param string $action_type
     * @param string $action_state
     * @param string|null $action_command
     * @return TopicActionProcessorInterface|null
     */
    public static function create($action_type, $action_state, $action_command = null) {
        // Giữ nguyên logic phân cấp type -> state -> command
        switch ($action_type) {
            case 'init':
                if ($action_state === 'success') {
                    // Kiểm tra command sau khi đã match type và state
                    if ($action_command === 'TopicComposer') {
                        log_message('error', 'Creating TopicComposerProcessor for command: ' . $action_command);
                        return new TopicComposerProcessor();
                    }
                    // Fallback về processor mặc định cho init/success
                    return new InitGooglesheetRawItemProcessor();
                }
                return new InitGooglesheetRawItemProcessor();

            case 'ExecutionTag_ExecWriting':
                if ($action_state === 'ExecutionTag_ExecWriting_PostCreated') {
                    return new WordPressPostActionProcessor();
                }
                if ($action_state === 'ExecutionTag_ExecWriting_Complete') { 
                    return new WordPressPostSelectionProcessor();
                }
                break;

            case 'ExecutionTag_ExecAudit':
                if ($action_state === 'ExecutionTag_ExecAudit_SocialAuditCompleted') {
                    return new SocialMediaPostActionProcessor();
                }
                break;

            case 'ImageGenerateToggle':
                if ($action_state === 'GenImgCompleted') {
                    return new ImageGenerateToggleProcessor();
                }
                break;
            case 'BuildPostStructure':
                if ($action_state === 'BuildPostStructure_B_Begin') {
                    return new DraftWritingProcessor();
                }
                break;
            default:
                log_activity('No specific processor found for action type: ' . $action_type);
                return null;
        }

        return null;
    }
}

/**
 * Helper function để xử lý action
 * @param int $topic_id
 * @param array $action_data
 * @return array Response data
 */
function process_topic_action($topic_id, $action_data) {
    // Log nhỏ vẫn dùng log_activity
    log_activity('Processing topic action for topic: ' . $topic_id);
    // Data lớn dùng log_message
    log_message('error', 'Topic action data: ' . json_encode($action_data));

    if (empty($action_data['target_type'])) {
        return [
            'success' => false,
            'message' => _l('missing_action_type'),
            'data' => [
                'reload' => false,  // Không reload page
                'clear_button' => true // Signal để clear button state
            ]
        ];
    }

    $processor = TopicActionProcessorFactory::create($action_data['target_type'], $action_data['target_state'], $action_data['action_command']);
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
        // Log kết quả lớn với log_message
        log_message('error', 'Processor result for topic ' . $topic_id . ': ' . json_encode($result));
        
        // After successful processing, sync to Project/Task if linked
        if (!empty($result['success'])) {
            $action_type_code = $action_data['target_type'] ?? null;
            $action_state_code = $action_data['target_state'] ?? null;
            if ($action_type_code && $action_state_code) {
                try {
                    $syncRes = topics_sync_topic_to_project($topic_id, $action_type_code, $action_state_code);
                    log_message('error', 'Project sync result for topic ' . $topic_id . ': ' . json_encode($syncRes));
                } catch (Exception $e) {
                    log_message('error', 'Project sync error: ' . $e->getMessage());
                }
            }
        }
        
        // Thêm clear_button vào response nếu có lỗi
        if (!$result['success']) {
            $result['data'] = array_merge(
                $result['data'] ?? [],
                ['clear_button' => true]
            );
        }

        if ($button['trigger_type'] === 'native' && !empty($button['action_command'])) {
            // Thực thi command
            exec($button['action_command'], $output, $return_var);
            // ... xử lý kết quả
        }

        return $result;
    } catch (Exception $e) {
        log_message('error', 'Action processing error: ' . $e->getMessage());
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
 * Execute workflow for topic action
 * @param array $workflow_data Workflow data including all necessary parameters
 * @return array Response with success status and message
 */
function execute_topic_workflow($workflow_data) {
    $CI = &get_instance();
    $step = 1;
    
    try {
        // Step 1: Validate and auto-complete missing data
        $required_fields = ['workflow_id', 'topic_id', 'target_type', 'target_state', 'button_id'];
        $missing_fields = array_filter($required_fields, function($field) use ($workflow_data) {
            return empty($workflow_data[$field]);
        });

        // Nếu thiếu button_id nhưng có các thông tin khác, thử tìm button phù hợp
        if (in_array('button_id', $missing_fields) && !empty($workflow_data['workflow_id'])) {
            $CI->load->model('Topic_action_button_model');
            $matching_button = $CI->Topic_action_button_model->get_by_workflow_id($workflow_data['workflow_id']);
            
            if ($matching_button) {
                $workflow_data['button_id'] = $matching_button['id'];
                $missing_fields = array_diff($missing_fields, ['button_id']);
                
                // Tự động điền thêm các thông tin khác từ button nếu thiếu
                if (empty($workflow_data['target_type'])) {
                    $workflow_data['target_type'] = $matching_button['target_action_type'];
                }
                if (empty($workflow_data['target_state'])) {
                    $workflow_data['target_state'] = $matching_button['target_action_state'];
                }
            }
        }

        // Nếu vẫn còn thiếu thông tin
        if (!empty($missing_fields)) {
            return [
                'success' => false,
                'message' => "Step {$step} - " . _l('missing_required_fields'),
                'missing_fields' => $missing_fields,
                'prompt_action' => 'configure_button', // Frontend sẽ hiển thị form cấu hình
                'current_data' => $workflow_data // Gửi lại data hiện tại để điền sẵn form
            ];
        }

        // Step 2: Get topic data
        $step++;
        $CI->load->model('Topics_model');
        $topic = $CI->Topics_model->get_topic($workflow_data['topic_id']);
        if (!$topic) {
            throw new Exception("Step {$step} - " . _l('topic_not_found'));
        }
        
        // Step 3: Prepare workflow data
        $step++;
        $n8n_data = prepare_workflow_data($topic, $workflow_data);
        
        // Step 4: Send to n8n
        $step++;
        $response = send_to_n8n($workflow_data['workflow_id'], $n8n_data);
        
        // Step 5: Log successful execution
        $step++;
        log_activity(
            "Step {$step} - Workflow executed successfully [Topic ID: {$workflow_data['topic_id']}, " . 
            "Workflow: {$workflow_data['workflow_id']}]"
        );
        
        return [
            'success' => true,
            'message' => "Step {$step} - " . _l('workflow_executed_successfully'),
            'data' => $response
        ];
        
    } catch (Exception $e) {
        // Log error with current step
        log_activity(
            "Step {$step} - Workflow execution failed [Topic ID: " . ($workflow_data['topic_id'] ?? 'unknown') . 
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
 * @param object $topic Topic object
 * @param array $workflow_data Workflow data
 * @return array Prepared data for n8n
 */
function prepare_workflow_data($topic, $workflow_data) {
    // Lấy đầy đủ thông tin topic
    $CI = &get_instance();
    $topic_full = $CI->db->get_where(db_prefix() . 'topics', ['id' => $topic->id])->row();
    
    // Convert topic object to array để map động
    $topic_array = $topic_full ? (array)$topic_full : [];
    
    // Lọc bỏ các giá trị null từ topic data
    $topic_data = array_filter($topic_array, function($value) {
        return !is_null($value);
    });

    // Decode log data từ topic nếu có
    $log_data = [];
    if (!empty($topic_full->log)) {
        $decoded_log = json_decode($topic_full->log, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $log_data = $decoded_log;
        }
    }

    // Combine tất cả dữ liệu
    return array_merge(
        $workflow_data,
        [
            'topic_data' => $topic_data,
            'log_data' => $log_data,
            'execution_data' => [
                'timestamp' => date('Y-m-d H:i:s'),
                'user_id' => get_staff_user_id(),
                'source' => 'topic_action_button'
            ]
        ]
    );
}

/**
 * Common N8N communication functions
 */
function send_to_n8n($workflow_id, $data) {
    log_activity("Sending to N8N for workflow {$workflow_id}");
    // Log request data lớn với log_message
    log_message('debug', "N8N request data: " . json_encode($data));
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
        log_activity("Step {$step} - Attempting to call N8N URL: {$webhook_url}");

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
        
        // Thêm timeout để tránh treo
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        
        // Thêm SSL verify options
        // curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        // curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

        // Step 4: Execute request
        $step++;
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        // Log chi tiết hơn khi có lỗi
        if ($curl_error || ($http_code < 200 || $http_code >= 300)) {
            $error_context = [
                'workflow_id' => $workflow_id,
                'url' => $webhook_url,
                'http_code' => $http_code,
                'curl_error' => $curl_error,
                'response' => substr($response, 0, 1000)
            ];
            log_activity("N8N request failed: " . json_encode($error_context));
        }

        // Log response
        if ($response) {
            // Log raw response lớn
            log_message('error', "N8N raw response: " . $response);
            try {
                $parsed_response = json_decode($response, true);
                // Log parsed response lớn
                log_message('error', "N8N parsed response: " . json_encode($parsed_response));
            } catch (Exception $e) {
                log_message('error', "Error parsing N8N response: " . $e->getMessage());
            }
        }

        // Trả về response với thông tin chi tiết hơn
        return [
            'success' => ($http_code >= 200 && $http_code < 300),
            'message' => $curl_error ? $curl_error : ($http_code >= 200 && $http_code < 300 ? 'Success' : 'HTTP Error'),
            'data' => [
                'response' => $parsed_response,
                'http_code' => $http_code,
                'url' => $webhook_url,
                'error' => $curl_error,
                'clear_button' => ($http_code < 200 || $http_code >= 300)
            ]
        ];

    } catch (Exception $e) {
        log_activity("N8N Request Exception: " . $e->getMessage());
        
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
/**
 * Helper function to get WordPress Post ID from topic history
 * @param int $topic_id Topic ID from tbltopics
 * @return int|null WordPress Post ID or null if not found
 */
function get_wordpress_post_id($topic_id) {
    $CI = &get_instance();
    
    try {
        // Get topicid from topics table first
        $CI->db->select('topicid');
        $CI->db->where('id', $topic_id);
        $topic = $CI->db->get(db_prefix() . 'topics')->row();
        
        if (!$topic) {
            log_message('error', "[WordPress ID] Topic not found for ID {$topic_id}");
            return null;
        }

        // Get latest WordPress post data from topics table
        $CI->db->where('topicid', $topic->topicid);
        $CI->db->where('action_type_code', 'ExecutionTag_ExecWriting');
        $CI->db->where('action_state_code', 'ExecutionTag_ExecWriting_PostCreated');
        $CI->db->order_by('dateupdated', 'DESC');
        $CI->db->limit(1);
        
        $history = $CI->db->get(db_prefix() . 'topics')->row();
        
        // Log history query result
        log_message('error', "[WordPress ID] History query for topic {$topic_id} (topicid: {$topic->topicid}): " . ($history ? 'Found' : 'Not found'));
        
        if (!$history || empty($history->data)) {
            log_message('error', "[WordPress ID] No data found for topic {$topic_id}");
            return null;
        }

        // Try to parse data JSON
        $valid_data = json_decode($history->data, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            log_message('error', "[WordPress ID] Error parsing data for topic {$topic_id}: " . json_last_error_msg());
            log_message('error', "[WordPress ID] Raw data: " . substr($history->data, 0, 1000));
            return null;
        }

        // Log parsed data structure
        log_message('error', "[WordPress ID] Parsed data structure for topic {$topic_id}: " . json_encode(array_keys($valid_data)));

        // Check different possible locations of WordPress Post ID
        $post_id = null;

        // Check in response data first (like WordPressPostSelectionProcessor)
        if (isset($valid_data['response']['data'])) {
            $response_data = $valid_data['response']['data'];
            
            // Check for ID in response data
            if (isset($response_data['id'])) {
                $post_id = $response_data['id'];
                log_message('error', "[WordPress ID] Found ID in response.data.id: {$post_id}");
            }
        }
        
        // If not found in response, check other locations
        if (!$post_id) {
            // Check direct id field
            if (isset($valid_data['id'])) {
                $post_id = $valid_data['id'];
                log_message('error', "[WordPress ID] Found ID in direct id field: {$post_id}");
            }
            // Check in data.options.id
            elseif (isset($valid_data['data']['options']['id'])) {
                $post_id = $valid_data['data']['options']['id'];
                log_message('error', "[WordPress ID] Found ID in data.options.id: {$post_id}");
            }
        }

        // Validate post ID is numeric
        if ($post_id && is_numeric($post_id)) {
            log_message('error', "[WordPress ID] Valid ID found for topic {$topic_id}: {$post_id}");
            return (int)$post_id;
        } else {
            log_message('error', "[WordPress ID] Invalid or no ID found for topic {$topic_id}");
        }

        return null;

    } catch (Exception $e) {
        log_message('error', "[WordPress ID] Error getting WordPress Post ID for topic {$topic_id}: " . $e->getMessage());
        log_message('error', "[WordPress ID] Error trace: " . $e->getTraceAsString());
        return null;
    }
}

/**
 * Helper function to get WordPress Post URL from topic history
 * @param int $topic_id Topic ID from tbltopics
 * @return string|null WordPress Post URL or null if not found
 */
function get_wordpress_post_url($topic_id) {
    $CI = &get_instance();
    
    try {
        // Get topicid from topics table first
        $CI->db->select('topicid');
        $CI->db->where('id', $topic_id);
        $topic = $CI->db->get(db_prefix() . 'topics')->row();
        
        if (!$topic) {
            log_message('error', "[WordPress URL] Topic not found for ID {$topic_id}");
            return null;
        }

        // Get latest WordPress post data from topics table
        $CI->db->where('topicid', $topic->topicid);
        $CI->db->where('action_type_code', 'ExecutionTag_ExecWriting');
        $CI->db->where('action_state_code', 'ExecutionTag_ExecWriting_PostCreated');
        $CI->db->order_by('dateupdated', 'DESC');
        $CI->db->limit(1);
        
        $history = $CI->db->get(db_prefix() . 'topics')->row();
        
        // Log history query result
        log_message('error', "[WordPress URL] History query for topic {$topic_id} (topicid: {$topic->topicid}): " . ($history ? 'Found' : 'Not found'));
        
        if (!$history || empty($history->data)) {
            log_message('error', "[WordPress URL] No data found for topic {$topic_id}");
            return null;
        }

        // Try to parse data JSON
        $valid_data = json_decode($history->data, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            log_message('error', "[WordPress URL] Error parsing data for topic {$topic_id}: " . json_last_error_msg());
            log_message('error', "[WordPress URL] Raw data: " . substr($history->data, 0, 1000));
            return null;
        }

        // Log parsed data structure
        log_message('error', "[WordPress URL] Parsed data structure for topic {$topic_id}: " . json_encode(array_keys($valid_data)));

        // Check different possible locations of WordPress Post URL
        $post_url = null;

        // Check in response data first (like WordPressPostSelectionProcessor)
        if (isset($valid_data['response']['data'])) {
            $response_data = $valid_data['response']['data'];
            
            // Check for link in response data
            if (isset($response_data['link'])) {
                $post_url = $response_data['link'];
                log_message('error', "[WordPress URL] Found URL in response.data.link: {$post_url}");
            }
            // Check for guid in response data
            elseif (isset($response_data['guid']['rendered'])) {
                $post_url = $response_data['guid']['rendered'];
                log_message('error', "[WordPress URL] Found URL in response.data.guid.rendered: {$post_url}");
            }
        }
        
        // If not found in response, check other locations
        if (!$post_url) {
            // Check direct link field
            if (isset($valid_data['link'])) {
                $post_url = $valid_data['link'];
                log_message('error', "[WordPress URL] Found URL in direct link field: {$post_url}");
            }
            // Check direct guid
            elseif (isset($valid_data['guid']['rendered'])) {
                $post_url = $valid_data['guid']['rendered'];
                log_message('error', "[WordPress URL] Found URL in guid.rendered: {$post_url}");
            }
        }

        // Validate URL
        if ($post_url) {
            if (filter_var($post_url, FILTER_VALIDATE_URL)) {
                log_message('error', "[WordPress URL] Valid URL found for topic {$topic_id}: {$post_url}");
                return $post_url;
            } else {
                log_message('error', "[WordPress URL] Invalid URL format for topic {$topic_id}: {$post_url}");
            }
        } else {
            log_message('error', "[WordPress URL] No URL found in any location for topic {$topic_id}");
        }

        return null;

    } catch (Exception $e) {
        log_message('error', "[WordPress URL] Error getting WordPress Post URL for topic {$topic_id}: " . $e->getMessage());
        log_message('error', "[WordPress URL] Error trace: " . $e->getTraceAsString());
        return null;
    }
} 

/**
 * Helper function to get all WordPress Post IDs from topic history
 * @param string $topicid Topic ID (not the record ID)
 * @return array Array of WordPress post data
 */
function get_wordpress_posts_from_history($topicid) {
    $CI = &get_instance();
    $posts = [];
    
    try {
        // Get all WordPress post records from topics table
        $CI->db->where('topicid', $topicid);
        $CI->db->where('action_type_code', 'ExecutionTag_ExecWriting');
        $CI->db->where('action_state_code', 'ExecutionTag_ExecWriting_PostCreated');
        $CI->db->order_by('dateupdated', 'DESC');
        
        $history = $CI->db->get(db_prefix() . 'topics')->result();
        
        foreach ($history as $record) {
            if (empty($record->data)) continue;

            // Parse data JSON
            $valid_data = json_decode($record->data, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                continue;
            }

            $post_data = [];

            // Check in response data first
            if (isset($valid_data['response']['data'])) {
                $response_data = $valid_data['response']['data'];
                
                if (isset($response_data['id'])) {
                    $post_data['id'] = $response_data['id'];
                    // Decode HTML entities trong title
                    $post_data['title'] = isset($response_data['title']['rendered']) ? 
                        html_entity_decode($response_data['title']['rendered'], ENT_QUOTES | ENT_HTML5, 'UTF-8') : '';
                    $post_data['link'] = $response_data['link'] ?? '';
                    $post_data['date'] = $record->dateupdated;
                    $posts[] = $post_data;
                }
            }
            // Check other possible locations
            elseif (isset($valid_data['id'])) {
                $post_data['id'] = $valid_data['id'];
                // Decode HTML entities trong title
                $post_data['title'] = isset($valid_data['title']['rendered']) ? 
                    html_entity_decode($valid_data['title']['rendered'], ENT_QUOTES | ENT_HTML5, 'UTF-8') : '';
                $post_data['link'] = $valid_data['link'] ?? '';
                $post_data['date'] = $record->dateupdated;
                $posts[] = $post_data;
            }
        }

        // Remove duplicates based on post ID
        $unique_posts = array_reduce($posts, function($carry, $item) {
            if (!isset($carry[$item['id']])) {
                $carry[$item['id']] = $item;
            }
            return $carry;
        }, []);

        return array_values($unique_posts);

    } catch (Exception $e) {
        log_message('error', "[WordPress Posts] Error getting WordPress Posts for topic {$topicid}: " . $e->getMessage());
        return [];
    }
} 