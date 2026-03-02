<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Topic_master_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    public function get($id = '')
    {
        if (is_numeric($id)) {
            $this->db->where('id', $id);
            return $this->db->get(db_prefix() . 'topic_master')->row();
        }
        return $this->db->get(db_prefix() . 'topic_master')->result_array();
    }

    public function get_by_topicid($topicid)
    {
        $this->db->where('topicid', $topicid);
        return $this->db->get(db_prefix() . 'topic_master')->row();
    }

    public function add($data)
    {
        $this->db->insert(db_prefix() . 'topic_master', [
            'topicid' => $data['topicid'],
            'topictitle' => $data['topictitle'],
            'status' => isset($data['status']) ? 1 : 0
        ]);

        $insert_id = $this->db->insert_id();

        if ($insert_id) {
            log_activity('New Topic Master Added [ID: ' . $insert_id . ']');
        }

        return $insert_id;
    }

    public function update($id, $data)
    {
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'topic_master', [
            'topictitle' => $data['topictitle'],
            'status' => isset($data['status']) ? 1 : 0
        ]);

        if ($this->db->affected_rows() > 0) {
            log_activity('Topic Master Updated [ID: ' . $id . ']');
            return true;
        }

        return false;
    }

    public function change_status($id, $status)
    {
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'topic_master', [
            'status' => $status
        ]);

        return $this->db->affected_rows() > 0;
    }

    public function get_topic_progress($topicid)
    {
        $this->db->select('action_type_code, action_state_code, COUNT(*) as count');
        $this->db->where('topicid', $topicid);
        $this->db->group_by('action_type_code, action_state_code');
        return $this->db->get(db_prefix() . 'topics')->result_array();
    }

    public function get_topic_history($topicid)
    {
        $this->db->select([
            't.*',  // Tất cả các trường từ bảng topics
            'tas.name as action_state_name', 
            'tas.color as state_color',
            'tat.name as action_type_name', 
            'tat.action_type_code',
            'tt.target_type',
            'tt.order as order'
        ]);
        
        $this->db->from(db_prefix() . 'topics t');
        
        $this->db->join(
            db_prefix() . 'topic_action_states tas', 
            't.action_state_code = tas.action_state_code', 
            'left'
        );
        
        $this->db->join(
            db_prefix() . 'topic_action_types tat',
            't.action_type_code = tat.action_type_code', 
            'left'
        );
        
        $this->db->join(
            db_prefix() . 'topic_target tt',
            't.target_id = tt.id', 
            'left'
        );
        
        if ($this->db->field_exists('automation_id', db_prefix() . 'topics')) {
            $this->db->join(
                db_prefix() . 'topic_automation_logs tal',
                't.topicid = tal.topic_id AND t.automation_id = tal.automation_id', 
                'left'
            );
            
            if ($this->db->field_exists('execution_id', db_prefix() . 'topic_automation_logs')) {
                $this->db->select('tal.execution_id');
            }
            if ($this->db->field_exists('workflow_id', db_prefix() . 'topic_automation_logs')) {
                $this->db->select('tal.workflow_id');
            }
        }
        
        $this->db->where('t.topicid', $topicid);
        $this->db->order_by('t.datecreated', 'DESC');
        
        return $this->db->get()->result();
    }

    public function count_all()
    {
        return $this->db->from(db_prefix() . 'topic_master')
                        ->count_all_results();
    }

    public function count_active()
    {
        return $this->db->where('status', 1)
                        ->from(db_prefix() . 'topic_master')
                        ->count_all_results();
    }

    /**
     * Đếm số lượng topics theo action type của trạng thái cuối cùng
     * @param string $action_type_code Mã loại hành động
     * @return int
     */
    public function count_by_latest_action_type($action_type_code)
    {
        if ($action_type_code === 'ExecutionTag_ExecWriting') {
            // Lấy danh sách các trạng thái viết bài (ngoại trừ PostCompleted)
            $writing_states = [
                'ExecChooseStyle',
                'ExecutionTag_ExecWriting_Start',
                'ExecutionTag_ExecWriting_Partial',
                'ExecutionTag_ExecWriting_Complete',
                'ExecutionTag_ExecWriting_PostCreated',
                'ExecutionTag_ExecWriting_Upload'
            ];

            // Đếm các topic có trạng thái cuối cùng là viết bài và đang active
            $this->db->select('COUNT(DISTINCT t1.topicid) as count');
            $this->db->from(db_prefix() . 'topics t1');
            $this->db->join(db_prefix() . 'topic_master tm', 't1.topicid = tm.topicid');
            $this->db->where('t1.action_type_code', $action_type_code);
            $this->db->where_in('t1.action_state_code', $writing_states);
            $this->db->where('tm.status', 1); // Chỉ đếm active masters
            $this->db->where('t1.id = (
                SELECT MAX(t2.id) 
                FROM ' . db_prefix() . 'topics t2 
                WHERE t2.topicid = t1.topicid
            )');
            
            return $this->db->get()->row()->count;
        } else if ($action_type_code === 'ExecutionTag_ScheduledSocial') {
            // Đếm các topic có bất kỳ trạng thái social nào trong lịch sử
            $this->db->select('COUNT(DISTINCT t1.topicid) as count');
            $this->db->from(db_prefix() . 'topics t1');
            $this->db->join(db_prefix() . 'topic_master tm', 't1.topicid = tm.topicid');
            $this->db->where('t1.action_type_code', $action_type_code);
            $this->db->where('tm.status', 1); // Chỉ đếm active masters
            
            return $this->db->get()->row()->count;
        }

        // Xử lý các action type khác như cũ
        return $this->db->where('action_type_code', $action_type_code)
                        ->from(db_prefix() . 'topics')
                        ->count_all_results();
    }

    public function update_all_status($status)
    {
        $this->db->trans_start();

        // Update all records in topic_master
        $this->db->update(db_prefix() . 'topic_master', [
            'status' => $status,
            'dateupdated' => date('Y-m-d H:i:s')
        ]);

        // Update all records in topics table
        $this->db->update(db_prefix() . 'topics', [
            'status' => $status,
            'dateupdated' => date('Y-m-d H:i:s')
        ]);

        $this->db->trans_complete();

        return $this->db->trans_status();
    }

    public function bulk_update_status($ids, $status)
    {
        // Debug log
        log_message('debug', 'Model bulk_update_status - Status: ' . $status . ', IDs: ' . json_encode($ids));

        try {
            $this->db->trans_start();

            // Update topic_master table
            $this->db->where_in('id', $ids);
            $success = $this->db->update(db_prefix() . 'topic_master', [
                'status' => $status,
                'dateupdated' => date('Y-m-d H:i:s')
            ]);
            
            if ($success) {
                // Get affected topicids
                $this->db->select('topicid');
                $this->db->where_in('id', $ids);
                $topic_masters = $this->db->get(db_prefix() . 'topic_master')->result();
                
                $topicids = array_column($topic_masters, 'topicid');
                
                // Update corresponding topics
                if (!empty($topicids)) {
                    $this->db->where_in('topicid', $topicids);
                    $this->db->update(db_prefix() . 'topics', [
                        'status' => $status,
                        'dateupdated' => date('Y-m-d H:i:s')
                    ]);
                }
            }

            $this->db->trans_complete();
            
            // Debug log
            log_message('debug', 'Update result: ' . ($success ? 'true' : 'false') . 
                               ', Affected rows: ' . $this->db->affected_rows());
            
            return $this->db->trans_status();
            
        } catch (Exception $e) {
            log_message('error', 'bulk_update_status error: ' . $e->getMessage());
            return false;
        }
    }

    public function assign_controller($topic_ids, $controller_id)
    {
        if (!is_array($topic_ids)) {
            $topic_ids = [$topic_ids];
        }
        
        $this->db->where_in('id', $topic_ids);
        $this->db->update($this->table, ['controller_id' => $controller_id]);
        
        return $this->db->affected_rows() > 0;
    }

    public function get_controller($topic_id)
    {
        $this->db->select('c.*');
        $this->db->from($this->table . ' tm');
        $this->db->join(db_prefix() . 'topic_controllers c', 'c.id = tm.controller_id', 'left');
        $this->db->where('tm.id', $topic_id);
        return $this->db->get()->row();
    }

    public function get_topic_from_master($id)
    {
        // Get topic master record first using proper Perfex CRM query
        $master = $this->db->select('*')
                          ->from(db_prefix() . 'topic_master')
                          ->where('id', $id)
                          ->get()
                          ->row();
                          
        if (!$master) {
            log_activity('Topic Master not found [ID: ' . $id . ']');
            return null;
        }

        // First try: Get topic with all required values
        $this->db->select([
            't.*',
            'tas.name as action_state_name',
            'tas.color as state_color', 
            'tat.name as action_type_name',
            'tt.target_type'
        ]);
        
        $this->db->from(db_prefix() . 'topics t');
        $this->db->join(
            db_prefix() . 'topic_action_states tas',
            't.action_state_code = tas.action_state_code',
            'left'
        );
        $this->db->join(
            db_prefix() . 'topic_action_types tat',
            't.action_type_code = tat.action_type_code',
            'left'
        );
        $this->db->join(
            db_prefix() . 'topic_target tt',
            't.target_id = tt.id',
            'left'
        );
        
        // Add conditions for required fields
        $this->db->where('t.topicid', $master->topicid);
        $this->db->where('t.action_type_code IS NOT NULL');
        $this->db->where('t.action_state_code IS NOT NULL');
        $this->db->where('t.target_id IS NOT NULL');
        $this->db->where('t.data IS NOT NULL');
        
        $this->db->order_by('t.id', 'DESC');
        $this->db->limit(1);
        
        $result = $this->db->get()->row();
        
        // If no result found with all required values, get the latest topic
        if (!$result) {
            $this->db->select([
                '*'
            ]);
            
            $this->db->from(db_prefix() . 'topics');
            
            $this->db->where('topicid', $master->topicid);
            $this->db->order_by('id', 'DESC');
            $this->db->limit(1);
            
            $result = $this->db->get()->row();
        }

        return $result;
    }

    /**
     * Update topic status
     * @param string $topicid Topic ID
     * @param int $status New status (0 or 1)
     * @return boolean
     */
    public function update_status($topicid, $status)
    {
        // Validate status
        $status = ($status == 1) ? 1 : 0;
        
        // Update in topic_master table
        $this->db->where('topicid', $topicid);
        $success = $this->db->update(db_prefix() . 'topic_master', [
            'status' => $status,
            'dateupdated' => date('Y-m-d H:i:s')
        ]);
        
        if ($success) {
            // Also update all related records in topics table
            $this->db->where('topicid', $topicid);
            $this->db->update(db_prefix() . 'topics', [
                'status' => $status,
                'dateupdated' => date('Y-m-d H:i:s')
            ]);
            
            // Log the change using Perfex's built-in log_activity helper
            log_activity('Topic status changed to ' . ($status ? 'Active' : 'Inactive') . ' [TopicID: ' . $topicid . ']');
            
            return true;
        }
        
        return false;
    }

    /**
     * Đếm số lượng Fail Topics (có trạng thái cuối cùng là fail/error)
     * @return int
     */
    public function count_failed()
    {
        // Lấy tất cả các mã trạng thái thất bại
        $this->db->select('action_state_code');
        $this->db->from(db_prefix() . 'topic_action_states');
        $this->db->group_start()
            ->like('name', 'fail', 'both')
            ->or_like('name', 'error', 'both')
            ->or_like('name', 'failed', 'both')
            ->or_like('action_state_code', 'fail', 'both')
            ->or_like('action_state_code', 'error', 'both')
            ->or_like('action_state_code', 'failed', 'both')
        ->group_end();
        $query = $this->db->get();
        $fail_states = array_column($query->result_array(), 'action_state_code');

        if (empty($fail_states)) {
            return 0;
        }

        // Đếm các topic có trạng thái cuối cùng là fail và topic master đang active
        $this->db->select('COUNT(DISTINCT t1.topicid) as count');
        $this->db->from(db_prefix() . 'topics t1');
        $this->db->join(db_prefix() . 'topic_master tm', 't1.topicid = tm.topicid');
        $this->db->where_in('t1.action_state_code', $fail_states);
        $this->db->where('tm.status', 1); // Chỉ đếm active masters
        $this->db->where('t1.id = (
            SELECT MAX(t2.id) 
            FROM ' . db_prefix() . 'topics t2 
            WHERE t2.topicid = t1.topicid
        )');
        
        return $this->db->get()->row()->count;
    }

    /**
     * Đếm số lượng topics có chứa action type trong lịch sử
     * @param string $action_type_code Mã loại hành động
     * @return int
     */
    public function count_by_action_type_history($action_type_code)
    {
        if ($action_type_code === 'ExecutionTag_ScheduledSocial') {
            // Đếm các topic có liên quan đến social media
            $this->db->select('COUNT(DISTINCT t1.topicid) as count');
            $this->db->from(db_prefix() . 'topics t1');
            $this->db->join(db_prefix() . 'topic_master tm', 't1.topicid = tm.topicid');
            $this->db->where('tm.status', 1); // Chỉ đếm active masters
            $this->db->group_start();
                $this->db->where('t1.action_type_code', 'ExecutionTag_ExecSocialMedia')
                    ->or_where_in('t1.action_state_code', [
                        'ExecSocialPost',
                        'ExecSocialScheduled',
                        'ExecSocialPosted',
                        'ExecPendingSocialPost'
                    ]);
            $this->db->group_end();
            
            return $this->db->get()->row()->count;
        }
        else if ($action_type_code === 'ExecutionTag_SocialAudit') {
            // Đếm các topic có liên quan đến social audit
            $this->db->select('COUNT(DISTINCT t1.topicid) as count');
            $this->db->from(db_prefix() . 'topics t1');
            $this->db->join(db_prefix() . 'topic_master tm', 't1.topicid = tm.topicid');
            $this->db->where('tm.status', 1); // Chỉ đếm active masters
            $this->db->where_in('t1.action_state_code', [
                'ExecutionTag_ExecAudit_SocialAuditStart',
                'ExecutionTag_ExecAudit_SocialAuditCompleted',
                'ExecutionTag_ExecAudit_SocialAuditPartial'
            ]);
            
            return $this->db->get()->row()->count;
        }

        // Xử lý các action type khác
        $this->db->select('COUNT(DISTINCT t1.topicid) as count');
        $this->db->from(db_prefix() . 'topics t1');
        $this->db->join(db_prefix() . 'topic_master tm', 't1.topicid = tm.topicid');
        $this->db->where('t1.action_type_code', $action_type_code);
        $this->db->where('tm.status', 1);
        
        return $this->db->get()->row()->count;
    }
} 