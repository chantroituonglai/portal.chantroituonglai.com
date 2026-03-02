<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Project Agent Model
 * Handles database operations for Project Agent module
 */

class Project_agent_model extends App_Model {
    
    public function __construct() {
        parent::__construct();
    }
    
    // Table name resolvers - use only canonical table names
    private function t_sessions() {
        $table = db_prefix() . 'project_agent_sessions';
        if (!$this->db->table_exists($table)) {
            $this->ensure_table_exists('sessions');
        }
        return $table;
    }
    private function t_memory_entries() {
        $table = db_prefix() . 'project_agent_memory_entries';
        if (!$this->db->table_exists($table)) {
            $this->ensure_table_exists('memory');
        }
        return $table;
    }
    private function t_action_logs() {
        $table = db_prefix() . 'project_agent_action_logs';
        if (!$this->db->table_exists($table)) {
            $this->ensure_table_exists('logs');
        }
        return $table;
    }
    private function t_actions() {
        $table = db_prefix() . 'project_agent_actions';
        if (!$this->db->table_exists($table)) {
            $this->ensure_table_exists('actions');
        }
        return $table;
    }
    private function t_memory_chains() {
        $table = db_prefix() . 'project_agent_memory_chains';
        return $table;
    }

    private function ensure_table_exists($which){
        $this->load->dbforge();
        switch ($which){
            case 'sessions':
                $fields = [
                    'session_id' => ['type'=>'INT','constraint'=>11,'auto_increment'=>TRUE],
                    'project_id' => ['type'=>'INT','constraint'=>11,'null'=>TRUE],
                    'user_id'    => ['type'=>'INT','constraint'=>11,'null'=>FALSE],
                    'title'      => ['type'=>'VARCHAR','constraint'=>255,'null'=>TRUE],
                    'created_at' => ['type'=>'DATETIME','null'=>FALSE],
                    'updated_at' => ['type'=>'DATETIME','null'=>FALSE],
                ];
                $this->dbforge->add_field($fields);
                $this->dbforge->add_key('session_id', TRUE);
                $this->dbforge->create_table(db_prefix().'project_agent_sessions', TRUE);
                break;
            case 'memory':
                $fields = [
                    'entry_id' => ['type'=>'INT','constraint'=>11,'auto_increment'=>TRUE],
                    'session_id'=> ['type'=>'INT','constraint'=>11,'null'=>FALSE],
                    'scope'     => ['type'=>'VARCHAR','constraint'=>16,'null'=>FALSE],
                    'kind'      => ['type'=>'VARCHAR','constraint'=>32,'null'=>FALSE],
                    'content_json'=> ['type'=>'TEXT','null'=>FALSE],
                    'created_at'=> ['type'=>'DATETIME','null'=>FALSE],
                    'project_id'=> ['type'=>'INT','constraint'=>11,'null'=>TRUE],
                    'customer_id'=> ['type'=>'INT','constraint'=>11,'null'=>TRUE],
                    'entity_refs'=> ['type'=>'TEXT','null'=>TRUE],
                ];
                $this->dbforge->add_field($fields);
                $this->dbforge->add_key('entry_id', TRUE);
                $this->dbforge->create_table(db_prefix().'project_agent_memory_entries', TRUE);
                break;
            case 'logs':
                $fields = [
                    'log_id' => ['type'=>'INT','constraint'=>11,'auto_increment'=>TRUE],
                    'session_id'=> ['type'=>'INT','constraint'=>11,'null'=>FALSE],
                    'plan_id'   => ['type'=>'VARCHAR','constraint'=>100,'null'=>FALSE],
                    'run_id'    => ['type'=>'VARCHAR','constraint'=>100,'null'=>FALSE],
                    'action_id' => ['type'=>'VARCHAR','constraint'=>100,'null'=>FALSE],
                    'params_json'=> ['type'=>'LONGTEXT','null'=>FALSE],
                    'result_json'=> ['type'=>'LONGTEXT','null'=>TRUE],
                    'status'    => ['type'=>'VARCHAR','constraint'=>16,'null'=>FALSE],
                    'error_message'=> ['type'=>'TEXT','null'=>TRUE],
                    'executed_at'=> ['type'=>'DATETIME','null'=>FALSE],
                    'executed_by'=> ['type'=>'INT','constraint'=>11,'null'=>FALSE],
                    'client_token'=> ['type'=>'VARCHAR','constraint'=>255,'null'=>TRUE],
                ];
                $this->dbforge->add_field($fields);
                $this->dbforge->add_key('log_id', TRUE);
                $this->dbforge->create_table(db_prefix().'project_agent_action_logs', TRUE);
                break;
            case 'actions':
                $fields = [
                    'action_id'=> ['type'=>'VARCHAR','constraint'=>100,'null'=>FALSE],
                    'name'     => ['type'=>'VARCHAR','constraint'=>255,'null'=>FALSE],
                    'description'=> ['type'=>'TEXT','null'=>TRUE],
                    'params_schema'=> ['type'=>'TEXT','null'=>FALSE],
                    'permissions' => ['type'=>'TEXT','null'=>FALSE],
                    'prompt_override'=> ['type'=>'TEXT','null'=>TRUE],
                    'risk_level'=> ['type'=>'ENUM','constraint'=>['low','medium','high'],'default'=>'low','null'=>FALSE],
                    'requires_confirm'=> ['type'=>'TINYINT','constraint'=>1,'default'=>0,'null'=>FALSE],
                    'is_active'=> ['type'=>'TINYINT','constraint'=>1,'default'=>1,'null'=>FALSE],
                ];
                $this->dbforge->add_field($fields);
                $this->dbforge->add_key('action_id', TRUE);
                $this->dbforge->create_table(db_prefix().'project_agent_actions', TRUE);
                break;
        }
    }
    
    // ========================================
    // SESSION MANAGEMENT
    // ========================================
    
    /**
     * Create new session with data array
     */
    public function create_session_with_data($data) {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->insert($this->t_sessions(), $data);
        return $this->db->insert_id();
    }
    
    /**
     * Get session by ID
     */
    public function get_session($session_id) {
        return $this->db->get_where($this->t_sessions(), ['session_id' => $session_id])->row();
    }
    
    /**
     * Get active session for project and user
     */
    public function get_active_session($project_id, $user_id) {
        return $this->db->get_where($this->t_sessions(), [
            'project_id' => $project_id,
            'user_id' => $user_id
        ])->row();
    }
    
    /**
     * Update session
     */
    public function update_session($session_id, $data) {
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->where('session_id', $session_id);
        return $this->db->update($this->t_sessions(), $data);
    }
    
    /**
     * Delete session
     */
    public function delete_session($session_id) {
        $this->db->where('session_id', $session_id);
        return $this->db->delete($this->t_sessions());
    }
    
    // ========================================
    // MEMORY ENTRIES
    // ========================================
    
    /**
     * Add memory entry
     */
    public function add_memory_entry($data) {
        $data['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert($this->t_memory_entries(), $data);
        return $this->db->insert_id();
    }
    
    /**
     * Get memory entries for session
     */
    public function get_memory_entries($session_id, $limit = 50, $offset = 0) {
        $this->db->select('*');
        $this->db->from($this->t_memory_entries());
        $this->db->where('session_id', $session_id);
        $this->db->order_by('created_at', 'DESC');
        $this->db->limit($limit, $offset);
        
        return $this->db->get()->result_array();
    }
    
    /**
     * Get memory entries by kind
     */
    public function get_memory_entries_by_kind($session_id, $kind, $limit = 50) {
        $this->db->select('*');
        $this->db->from($this->t_memory_entries());
        $this->db->where('session_id', $session_id);
        $this->db->where('kind', $kind);
        $this->db->order_by('created_at', 'DESC');
        $this->db->limit($limit);
        
        return $this->db->get()->result_array();
    }
    
    /**
     * Get latest memory entry by kind
     */
    public function get_latest_memory_entry($session_id, $kind) {
        $this->db->select('*');
        $this->db->from($this->t_memory_entries());
        $this->db->where('session_id', $session_id);
        $this->db->where('kind', $kind);
        $this->db->order_by('created_at', 'DESC');
        $this->db->limit(1);
        
        return $this->db->get()->row();
    }
    
    /**
     * Delete memory entry
     */
    public function delete_memory_entry($entry_id) {
        $this->db->where('entry_id', $entry_id);
        return $this->db->delete($this->t_memory_entries());
    }
    
    /**
     * Mark/unmark an entry as selected for Chain of Memory
     */
    public function set_memory_chain_selected($entry_id, $is_selected, $priority = null, $related_question_id = null) {
        $data = [ 'is_chain_selected' => (int)$is_selected ];
        if ($priority !== null) { $data['chain_priority'] = (int)$priority; }
        if ($related_question_id !== null) { $data['related_question_id'] = (string)$related_question_id; }
        $this->db->where('entry_id', (int)$entry_id);
        return $this->db->update($this->t_memory_entries(), $data);
    }

    /**
     * Get selected chain entries for a session
     */
    public function get_chain_selected_entries($session_id) {
        $this->db->from($this->t_memory_entries());
        $this->db->where('session_id', (int)$session_id);
        $this->db->where('is_chain_selected', 1);
        $this->db->order_by('chain_priority', 'DESC');
        $this->db->order_by('created_at', 'DESC');
        return $this->db->get()->result_array();
    }

    /**
     * Create a memory chain record (audit of a sent chain)
     */
    public function create_memory_chain_record($session_id, $question_id, array $memory_ids) {
        $table = $this->t_memory_chains();
        if (!$this->db->table_exists($table)) { return false; }
        $row = [
            'session_id' => (int)$session_id,
            'question_id' => (string)$question_id,
            'memory_ids' => json_encode(array_values(array_map('intval', $memory_ids))),
            'created_at' => date('Y-m-d H:i:s')
        ];
        $this->db->insert($table, $row);
        return $this->db->insert_id();
    }
    
    // ========================================
    // ACTION LOGS
    // ========================================
    
    /**
     * Add action log
     */
    public function add_action_log($data) {
        $data['executed_at'] = date('Y-m-d H:i:s');
        $this->db->insert($this->t_action_logs(), $data);
        return $this->db->insert_id();
    }
    
    /**
     * Get action logs for session
     */
    public function get_action_logs($session_id, $limit = 50, $status = null, $action_id = null) {
        $this->db->select('*');
        $this->db->from($this->t_action_logs());
        $this->db->where('session_id', $session_id);

        // Apply filters
        if ($status) {
            $this->db->where('status', $status);
        }
        if ($action_id) {
            $this->db->like('action_id', $action_id);
        }

        $this->db->order_by('executed_at', 'DESC');
        $this->db->limit($limit);

        return $this->db->get()->result_array();
    }
    
    /**
     * Get action log by ID
     */
    public function get_action_log($log_id) {
        return $this->db->get_where($this->t_action_logs(), ['log_id' => $log_id])->row();
    }

    /**
     * Get recent action logs from all sessions
     */
    public function get_recent_action_logs($limit = 50, $status = null, $action_id = null) {
        $this->db->select('*');
        $this->db->from($this->t_action_logs());

        // Apply filters
        if ($status) {
            $this->db->where('status', $status);
        }
        if ($action_id) {
            $this->db->like('action_id', $action_id);
        }

        // Only get recent logs (last 7 days)
        $this->db->where('executed_at >=', date('Y-m-d H:i:s', strtotime('-7 days')));

        $this->db->order_by('executed_at', 'DESC');
        $this->db->limit($limit);

        return $this->db->get()->result_array();
    }
    
    /**
     * Update action log status
     */
    public function update_action_log_status($log_id, $status, $result = null, $error_message = null) {
        $data = [
            'status' => $status,
            'executed_at' => date('Y-m-d H:i:s')
        ];
        
        if ($result !== null) {
            $data['result_json'] = json_encode($result);
        }
        
        if ($error_message !== null) {
            $data['error_message'] = $error_message;
        }
        
        $this->db->where('log_id', $log_id);
        return $this->db->update($this->t_action_logs(), $data);
    }
    
    // ========================================
    // ACTIONS
    // ========================================
    
    /**
     * Get all actions
     */
    public function get_actions($active_only = true) {
        $table = $this->t_actions();
        if (!$this->db->table_exists($table)) {
            return [];
        }
        $this->db->select('*');
        $this->db->from($table);
        
        if ($active_only) {
            $this->db->where('is_active', 1);
        }
        
        // Fully-qualify to avoid ambiguity in case of future joins
        $this->db->order_by($table . '.name', 'ASC');
        
        return $this->db->get()->result_array();
    }
    
    /**
     * Get action by ID
     */
    public function get_action($action_id) {
        $table = $this->t_actions();
        if (!$this->db->table_exists($table)) {
            return null;
        }
        return $this->db->get_where($table, ['action_id' => $action_id])->row();
    }
    
    /**
     * Add action
     */
    public function add_action($data) {
        $this->db->insert($this->t_actions(), $data);
        return $this->db->insert_id();
    }
    
    /**
     * Update action
     */
    public function update_action($action_id, $data) {
        $this->db->where('action_id', $action_id);
        return $this->db->update($this->t_actions(), $data);
    }
    
    /**
     * Delete action
     */
    public function delete_action($action_id) {
        $this->db->where('action_id', $action_id);
        return $this->db->delete($this->t_actions());
    }
    
    // ========================================
    // UTILITY METHODS
    // ========================================
    
    /**
     * Clean old memory entries
     */
    public function clean_old_memory_entries($days = 30) {
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        $this->db->where('created_at <', $cutoff_date);
        $this->db->where('scope', 'session'); // Only clean session-scoped entries
        
        return $this->db->delete($this->t_memory_entries());
    }
    
    /**
     * Get session statistics
     */
    public function get_session_stats($session_id) {
        // Count memory entries by kind
        $this->db->select('kind, COUNT(*) as count');
        $this->db->from($this->t_memory_entries());
        $this->db->where('session_id', $session_id);
        $this->db->group_by('kind');
        $memory_stats = $this->db->get()->result_array();
        
        // Count action logs by status
        $this->db->select('status, COUNT(*) as count');
        $this->db->from($this->t_action_logs());
        $this->db->where('session_id', $session_id);
        $this->db->group_by('status');
        $action_stats = $this->db->get()->result_array();
        
        return [
            'memory_entries' => $memory_stats,
            'action_logs' => $action_stats
        ];
    }
    
    /**
     * Get user sessions
     */
    public function get_user_sessions($user_id, $project_id = null, $limit = 20) {
        $this->db->select($this->t_sessions() . '.*, ' . db_prefix() . 'projects.name as project_name');
        $this->db->from($this->t_sessions());
        $this->db->join(db_prefix() . 'projects', db_prefix() . 'projects.id = ' . $this->t_sessions() . '.project_id', 'left');
        $this->db->where('user_id', $user_id);
        
        if ($project_id !== null) {
            $this->db->where('project_id', $project_id);
        }
        
        $this->db->order_by('created_at', 'DESC');
        $this->db->limit($limit);
        
        return $this->db->get()->result_array();
    }
    
    /**
     * Create new session
     */
    public function create_session($project_id = null, $user_id = null) {
        if (!$user_id) {
            $user_id = get_staff_user_id();
        }
        
        $data = [
            'user_id' => $user_id,
            'project_id' => $project_id,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $this->db->insert($this->t_sessions(), $data);
        return $this->db->insert_id();
    }
    
    /**
     * Get session with conversation summary
     */
    public function get_session_with_summary($session_id) {
        $session = $this->db->get_where($this->t_sessions(), ['session_id' => $session_id])->row_array();
        
        if (!$session) {
            return null;
        }
        
        // Get conversation summary
        $this->db->select('kind, COUNT(*) as count, MAX(created_at) as last_activity');
        $this->db->from($this->t_memory_entries());
        $this->db->where('session_id', $session_id);
        $this->db->group_by('kind');
        $summary = $this->db->get()->result_array();
        
        $session['conversation_summary'] = $summary;
        
        // Get last message
        $this->db->select('content_json, created_at');
        $this->db->from($this->t_memory_entries());
        $this->db->where('session_id', $session_id);
        $this->db->where_in('kind', ['input', 'ai_response']);
        $this->db->order_by('created_at', 'DESC');
        $this->db->limit(1);
        $last_message = $this->db->get()->row_array();
        
        if ($last_message) {
            $content = json_decode($last_message['content_json'], true);
            $session['last_message'] = [
                'text' => $content['text'] ?? '',
                'kind' => $last_message['kind'] ?? 'unknown',
                'created_at' => $last_message['created_at']
            ];
        }
        
        return $session;
    }
    
    /**
     * Get project sessions
     */
    public function get_project_sessions($project_id, $limit = 20) {
        $this->db->select($this->t_sessions() . '.*, ' . db_prefix() . 'staff.firstname, ' . db_prefix() . 'staff.lastname');
        $this->db->from($this->t_sessions());
        $this->db->join(db_prefix() . 'staff', db_prefix() . 'staff.staffid = ' . $this->t_sessions() . '.user_id');
        $this->db->where('project_id', $project_id);
        $this->db->order_by('created_at', 'DESC');
        $this->db->limit($limit);
        
        return $this->db->get()->result_array();
    }
}
