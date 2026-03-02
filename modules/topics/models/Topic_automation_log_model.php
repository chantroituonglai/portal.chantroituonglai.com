<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Topic_automation_log_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Add new automation log
     * @param array $data Log data
     * @return int|bool
     */
    public function add($data)
    {
        // Validate required fields
        $required_fields = ['topic_id', 'automation_id'];
        foreach ($required_fields as $field) {
            if (!isset($data[$field])) {
                log_activity('Missing required field: ' . $field);
                return false;
            }
        }

        // Add workflow_id if provided
        $insert_data = [
            'topic_id' => $data['topic_id'],
            'automation_id' => $data['automation_id'],
            'status' => $data['status'] ?? 'pending',
            'datecreated' => date('Y-m-d H:i:s')
        ];

        if (isset($data['workflow_id'])) {
            $insert_data['workflow_id'] = $data['workflow_id'];
        }

        if (isset($data['execution_id'])) {
            $insert_data['execution_id'] = $data['execution_id']; 
        }

        $this->db->insert(db_prefix() . 'topic_automation_logs', $insert_data);
        $insert_id = $this->db->insert_id();

        if ($insert_id) {
            // Update automation_id and workflow_id in topics table
            $update_data = ['automation_id' => $data['automation_id']];
            if (isset($data['workflow_id'])) {
                $update_data['workflow_id'] = $data['workflow_id'];
            }
            
            $this->db->where('topicid', $data['topic_id']);
            $this->db->update(db_prefix() . 'topics', $update_data);

            log_activity('New Topic Automation Log Added [ID: ' . $insert_id . ']');
            return $insert_id;
        }

        return false;
    }

    /**
     * Update automation log
     * @param array $data Log data
     * @param int $id Log ID
     * @return bool
     */
    public function update($data, $id)
    {
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'topic_automation_logs', $data);
        
        return $this->db->affected_rows() > 0;
    }

    /**
     * Get automation log by ID
     * @param int $id Log ID
     * @return object
     */
    public function get($id)
    {
        $this->db->where('id', $id);
        return $this->db->get(db_prefix() . 'topic_automation_logs')->row();
    }

    /**
     * Get automation logs by topic ID
     * @param string $topic_id Topic ID
     * @return array
     */
    public function get_by_topic($topic_id)
    {
        $this->db->where('topic_id', $topic_id);
        $this->db->order_by('datecreated', 'desc');
        return $this->db->get(db_prefix() . 'topic_automation_logs')->result_array();
    }

    /**
     * Update automation status
     * @param string $automation_id Automation ID
     * @param string $status New status
     * @param array $response_data Optional response data
     * @return bool
     */
    public function update_status($automation_id, $status, $response_data = null)
    {
        $data = [
            'status' => $status,
            'dateupdated' => date('Y-m-d H:i:s')
        ];

        if ($response_data) {
            $data['response_data'] = is_array($response_data) ? 
                json_encode($response_data) : $response_data;
        }

        $this->db->where('automation_id', $automation_id);
        $this->db->update(db_prefix() . 'topic_automation_logs', $data);

        return $this->db->affected_rows() > 0;
    }

    /**
     * Get logs by workflow ID
     * @param string $workflow_id Workflow ID
     * @return array
     */
    public function get_logs_by_workflow($workflow_id)
    {
        $this->db->where('workflow_id', $workflow_id);
        $this->db->order_by('datecreated', 'desc');
        return $this->db->get(db_prefix() . 'topic_automation_logs')->result_array();
    }

    /**
     * Get logs by execution ID
     * @param string $execution_id Execution ID
     * @return array  
     */
    public function get_logs_by_execution($execution_id)
    {
        $this->db->where('execution_id', $execution_id);
        $this->db->order_by('datecreated', 'desc');
        return $this->db->get(db_prefix() . 'topic_automation_logs')->result_array();
    }
} 