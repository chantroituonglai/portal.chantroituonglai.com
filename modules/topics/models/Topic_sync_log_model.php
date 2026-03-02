<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Topic Sync Log Model
 * Handles all sync log operations without foreign key constraints
 */
class Topic_sync_log_model extends App_Model
{
    protected $table;

    public function __construct()
    {
        parent::__construct();
        $this->table = db_prefix() . 'topic_sync_logs';
    }

    /**
     * Create a new sync session
     * @param int $controller_id Controller ID
     * @param string $rel_type Relation type (e.g., 'tag_sync')
     * @param array $initial_data Initial data for the session
     * @return string Session ID
     */
    public function create_session($controller_id, $rel_type, $initial_data = [])
    {
        // Generate a unique session ID
        $session_id = 'sync_' . time() . '_' . substr(md5(rand()), 0, 6);
        
        // Prepare initial summary data
        $summary_data = array_merge([
            'status' => 'in_progress',
            'total_pages' => 1,
            'current_page' => 1,
            'total_items' => 0,
            'items_processed' => 0,
            'success_count' => 0,
            'error_count' => 0,
            'last_update' => date('Y-m-d H:i:s')
        ], $initial_data);
        
        // Prepare initial log data
        $log_data = [
            'logs' => [
                [
                    'timestamp' => date('Y-m-d H:i:s'),
                    'message' => 'Bắt đầu đồng bộ ' . $rel_type,
                    'type' => 'info'
                ]
            ]
        ];
        
        // Insert the session
        $this->db->insert($this->table, [
            'controller_id' => $controller_id,
            'session_id' => $session_id,
            'rel_type' => $rel_type,
            'status' => 'in_progress',
            'summary_data' => json_encode($summary_data),
            'log_data' => json_encode($log_data),
            'start_time' => date('Y-m-d H:i:s'),
            'datecreated' => date('Y-m-d H:i:s'),
            'dateupdated' => date('Y-m-d H:i:s')
        ]);
        
        return $session_id;
    }
    
    /**
     * Get a sync session by ID
     * @param string $session_id Session ID
     * @return object|null Session data
     */
    public function get_session($session_id)
    {
        log_message('debug', 'Looking for sync session with ID: ' . $session_id);
        
        $this->db->where('session_id', $session_id);
        $query = $this->db->get($this->table);
        
        $result = $query->row();
        
        if ($result) {
            log_message('debug', 'Sync session found with ID: ' . $session_id);
        } else {
            // Thử tìm kiếm một phần của session_id (trường hợp session_id có prefix)
            $this->db->like('session_id', $session_id, 'both');
            $query = $this->db->get($this->table);
            $result = $query->row();
            
            if ($result) {
                log_message('debug', 'Sync session found with partial match. Requested: ' . $session_id . ', Found: ' . $result->session_id);
            } else {
                log_message('debug', 'No sync session found for ID: ' . $session_id);
            }
        }
        
        return $result;
    }
    
    /**
     * Get the current active session for a controller and relation type
     * @param int $controller_id Controller ID
     * @param string $rel_type Relation type
     * @return object|null Session data
     */
    public function get_active_session($controller_id, $rel_type)
    {
        return $this->db->where([
                'controller_id' => $controller_id,
                'rel_type' => $rel_type,
                'status' => 'in_progress'
            ])
            ->order_by('datecreated', 'DESC')
            ->limit(1)
            ->get($this->table)
            ->row();
    }
    
    /**
     * Update a sync session
     * @param string $session_id Session ID
     * @param array $summary_updates Summary data updates
     * @param array|string $log_entry Log entry to add (optional)
     * @return bool Success
     */
    public function update_session($session_id, $summary_updates = [], $log_entry = null)
    {
        // Get the current session
        $session = $this->get_session($session_id);
        if (!$session) {
            return false;
        }
        
        // Update summary data
        $summary_data = json_decode($session->summary_data, true);
        foreach ($summary_updates as $key => $value) {
            $summary_data[$key] = $value;
        }
        $summary_data['last_update'] = date('Y-m-d H:i:s');
        
        // Update log data if a new entry is provided
        $log_data = json_decode($session->log_data, true);
        if ($log_entry) {
            // Đảm bảo log_entry là một mảng
            if (!is_array($log_entry)) {
                $log_entry = ['message' => (string)$log_entry, 'type' => 'info'];
            }
            
            if (!isset($log_entry['timestamp'])) {
                $log_entry['timestamp'] = date('Y-m-d H:i:s');
            }
            
            // Đảm bảo details cũng là một mảng hoặc chuỗi
            if (isset($log_entry['details']) && !is_array($log_entry['details']) && !is_string($log_entry['details'])) {
                $log_entry['details'] = (string)$log_entry['details'];
            }
            
            $log_data['logs'][] = $log_entry;
        }
        
        // Cập nhật trường processed_count nếu có cập nhật số lượng items đã xử lý
        $update_data = [
            'summary_data' => json_encode($summary_data),
            'log_data' => json_encode($log_data),
            'dateupdated' => date('Y-m-d H:i:s')
        ];
        
        // Cập nhật processed_count nếu có trong summary_updates
        if (isset($summary_updates['processed_tags']) || isset($summary_updates['items_processed'])) {
            // Lấy số tags đã xử lý từ dữ liệu cập nhật hoặc dữ liệu hiện tại
            $processed_tags = isset($summary_updates['processed_tags']) ? 
                intval($summary_updates['processed_tags']) : 
                (isset($summary_data['processed_tags']) ? intval($summary_data['processed_tags']) : 0);
                
            if ($processed_tags > 0) {
                // Cộng thêm vào processed_count hiện tại
                $this->db->set('processed_count', "processed_count + {$processed_tags}", false);
            }
        }
        
        // Update the session
        $this->db->where('session_id', $session_id);
        $this->db->update($this->table, $update_data);
        
        return $this->db->affected_rows() > 0;
    }
    
    /**
     * Complete a sync session
     * @param string $session_id Session ID
     * @param string $status Final status (completed, failed, cancelled)
     * @param array $summary_updates Final updates to the summary data
     * @param array $log_entry Final log entry to add
     * @return bool Success or failure
     */
    public function complete_session($session_id, $status = 'completed', $summary_updates = [], $log_entry = null)
    {
        // Get the current session
        $session = $this->get_session($session_id);
        if (!$session) {
            return false;
        }
        
        // Update summary data
        $summary_data = json_decode($session->summary_data, true);
        foreach ($summary_updates as $key => $value) {
            $summary_data[$key] = $value;
        }
        $summary_data['status'] = $status;
        $summary_data['last_update'] = date('Y-m-d H:i:s');
        
        // Update log data if a new entry is provided
        $log_data = json_decode($session->log_data, true);
        if ($log_entry) {
            if (!isset($log_entry['timestamp'])) {
                $log_entry['timestamp'] = date('Y-m-d H:i:s');
            }
            $log_data['logs'][] = $log_entry;
        } else {
            // Add a default completion log entry
            $message = '';
            $type = '';
            
            if ($status === 'completed') {
                $message = 'Hoàn thành đồng bộ thành công';
                $type = 'success';
            } else if ($status === 'cancelled') {
                $message = 'Đồng bộ đã bị hủy bởi người dùng';
                $type = 'warning';
            } else {
                $message = 'Hoàn thành đồng bộ với trạng thái: ' . $status;
                $type = $status === 'completed' ? 'success' : 'error';
            }
            
            $log_data['logs'][] = [
                'timestamp' => date('Y-m-d H:i:s'),
                'message' => $message,
                'type' => $type
            ];
        }
        
        // Update the session
        $this->db->where('session_id', $session_id)
                ->update($this->table, [
                    'status' => $status,
                    'summary_data' => json_encode($summary_data),
                    'log_data' => json_encode($log_data),
                    'end_time' => date('Y-m-d H:i:s'),
                    'dateupdated' => date('Y-m-d H:i:s')
                ]);
        
        return $this->db->affected_rows() > 0;
    }
    
    /**
     * Get recent sync sessions for a controller
     * @param int $controller_id Controller ID
     * @param string $rel_type Relation type (optional)
     * @param int $limit Number of sessions to return
     * @return array Sessions
     */
    public function get_recent_sessions($controller_id, $rel_type = null, $limit = 10)
    {
        $this->db->where('controller_id', $controller_id);
        if ($rel_type) {
            $this->db->where('rel_type', $rel_type);
        }
        
        return $this->db->order_by('datecreated', 'DESC')
                       ->limit($limit)
                       ->get($this->table)
                       ->result_array();
    }
    
    /**
     * Format session data for API response
     * @param array $session Session data
     * @return array Formatted session data
     */
    public function format_session_for_response($session)
    {
        $summary_data = json_decode($session['summary_data'], true);
        
        // Ensure consistency between status and end_time
        $status = $session['status'];
        $end_time = $session['end_time'];
        
        // If status is in_progress, ensure end_time is null to avoid UI confusion
        if ($status === 'in_progress') {
            $end_time = null;
        }
        
        // Ưu tiên sử dụng processed_count từ cơ sở dữ liệu nếu có
        $processed_count = isset($session['processed_count']) ? $session['processed_count'] : 0;
        
        // Lấy số lượng tags thực tế từ database nếu session đang chạy hoặc pending
        if (($status === 'in_progress' || $status === 'pending') && !empty($session['session_id'])) {
            $processed_count = $this->get_processed_tags_count($session['session_id']);
        }
        
        // Tổng số tags (ưu tiên từ summary_data['total_tags'])
        $total_tags = $summary_data['total_tags'] ?? $summary_data['total_items'] ?? 0;
        
        return [
            'session_id' => $session['session_id'],
            'status' => $status,
            'start_time' => $session['start_time'],
            'end_time' => $end_time,
            'total_items' => $total_tags,
            'items_processed' => $processed_count > 0 ? $processed_count : ($summary_data['processed_tags'] ?? $summary_data['items_processed'] ?? 0),
            'success_count' => $summary_data['success_count'] ?? 0,
            'error_count' => $summary_data['error_count'] ?? 0,
            'current_page' => $summary_data['current_page'] ?? 1,
            'total_pages' => $summary_data['total_pages'] ?? 1,
            'last_update' => $summary_data['last_update'] ?? $session['dateupdated'],
            // Thêm các key này để đảm bảo tương thích với frontend
            'total_tags' => $total_tags,
            'processed_tags' => $processed_count > 0 ? $processed_count : ($summary_data['processed_tags'] ?? $summary_data['items_processed'] ?? 0)
        ];
    }

    /**
     * Add a log entry to a sync session (alias for update_session with only log entry)
     * @param string $session_id Session ID
     * @param array $log_entry Log entry to add
     * @return bool Success or failure
     */
    public function add_log($session_id, $log_entry)
    {
        return $this->update_session($session_id, [], $log_entry);
    }

    /**
     * Get pending sync sessions for a controller
     * @param int $controller_id Controller ID (optional, if null returns all pending sessions)
     * @param string $rel_type Relation type (optional)
     * @param int $limit Number of sessions to return (default is 10)
     * @return array Pending sessions
     */
    public function get_pending_tags_sync_sessions($controller_id = null, $rel_type = 'tags_sync', $limit = 10)
    {
        $this->db->where('status', 'pending');
        if ($rel_type) {
            $this->db->where('rel_type', $rel_type);
        }
        if ($controller_id) {
            $this->db->where('controller_id', $controller_id);
        }
        
        return $this->db->order_by('dateupdated', 'DESC')
                       ->limit($limit)
                       ->get($this->table)
                       ->result_array();
    }

    /**
     * Thêm vào số lượng tags đã xử lý cho một phiên đồng bộ
     * @param string $session_id Session ID
     * @param int $count Số lượng tags đã xử lý
     * @return bool Success
     */
    public function increment_processed_count($session_id, $count = 1)
    {
        if (empty($session_id) || $count <= 0) {
            return false;
        }
        
        $this->db->where('session_id', $session_id);
        $this->db->set('processed_count', "processed_count + {$count}", false);
        $this->db->update($this->table);
        
        return $this->db->affected_rows() > 0;
    }

    /**
     * Lấy số lượng tags đã xử lý dựa trên session_id từ bảng topic_controller_tags
     * @param string $session_id Session ID
     * @return int Số lượng tags đã xử lý
     */
    public function get_processed_tags_count($session_id)
    {
        if (empty($session_id)) {
            return 0;
        }
        
        $this->db->where('sync_session_id', $session_id);
        $count = $this->db->count_all_results(db_prefix() . 'topic_controller_tags');
        
        return $count;
    }

    /**
     * Cập nhật số lượng tags đã xử lý dựa trên số thực tế trong database
     * @param string $session_id Session ID
     * @return bool Success
     */
    public function update_processed_count_from_db($session_id)
    {
        if (empty($session_id)) {
            return false;
        }
        
        // Lấy số lượng thực tế từ database
        $count = $this->get_processed_tags_count($session_id);
        
        // Cập nhật vào processed_count
        $this->db->where('session_id', $session_id);
        $this->db->update($this->table, ['processed_count' => $count]);
        
        return $this->db->affected_rows() > 0;
    }
} 