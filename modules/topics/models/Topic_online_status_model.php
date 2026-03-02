<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Topic_online_status_model extends App_Model
{
    protected $table;

    public function __construct()
    {
        parent::__construct();
        $this->table = db_prefix() . 'staff_online_status';
    }

    /**
     * Update staff online status for topic
     * @param int $staff_id
     * @param string $topicid
     * @return bool
     */
    public function update_staff_online_status($staff_id, $topicid) 
    {
        // Cleanup old records first
        $this->cleanup_old_records();

        // Check existing record
        $this->db->where('staff_id', $staff_id);
        $this->db->where('topic_id', $topicid);
        $existing = $this->db->get($this->table)->row();

        if ($existing) {
            // Update last activity
            $this->db->where('id', $existing->id);
            return $this->db->update($this->table, [
                'last_activity' => date('Y-m-d H:i:s'),
                'dateupdated' => date('Y-m-d H:i:s')
            ]);
        }

        // Insert new record
        return $this->db->insert($this->table, [
            'staff_id' => $staff_id,
            'topic_id' => $topicid,
            'last_activity' => date('Y-m-d H:i:s'),
            'datecreated' => date('Y-m-d H:i:s'),
            'dateupdated' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Get online staff for topic
     * @param string $topicid
     * @return array
     */
    public function get_online_staff_for_topic($topicid)
    {
        $timeout = get_option('topics_online_timeout') ?: 900; // Default 15 minutes

        $this->db->select([
            's.staffid',
            's.firstname',
            's.lastname', 
            's.profile_image',
            'sos.last_activity'
        ]);
        $this->db->from($this->table . ' sos');
        $this->db->join(db_prefix() . 'staff s', 's.staffid = sos.staff_id');
        $this->db->where('sos.topic_id', $topicid);
        $this->db->where('sos.last_activity > DATE_SUB(NOW(), INTERVAL ' . $timeout . ' SECOND)');
        
        return $this->db->get()->result_array();
    }

    /**
     * Remove staff online status
     * @param int $staff_id
     * @param string $topicid optional
     * @return bool
     */
    public function remove_staff_online_status($staff_id, $topicid = null)
    {
        $this->db->where('staff_id', $staff_id);
        if ($topicid) {
            $this->db->where('topic_id', $topicid);
        }
        return $this->db->delete($this->table);
    }

    /**
     * Cleanup old records
     * @return void
     */
    private function cleanup_old_records()
    {
        $timeout = get_option('topics_online_timeout') ?: 900;
        $this->db->where('last_activity < DATE_SUB(NOW(), INTERVAL ' . $timeout . ' SECOND)');
        $this->db->delete($this->table);
    }
} 