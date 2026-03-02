<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Topic_target_model extends App_Model
{
    protected $table;

    public function __construct()
    {
        parent::__construct();
        $this->table = db_prefix() . 'topic_target';
    }

    public function get($id)
    {
        $this->db->where('id', $id);
        return $this->db->get($this->table)->row();
    }

    public function get_by_type($target_type)
    {
        $this->db->where('UPPER(target_type)', strtoupper($target_type));
        $this->db->where('status', 1);
        return $this->db->get($this->table)->row();
    }


    public function get_by_targetid($target_id)
    {
        $this->db->where('target_id', strtoupper($target_id));
        $this->db->where('status', 1);
        return $this->db->get($this->table)->row();
    }


    public function get_all($status = null)
    {
        $this->db->select('id, title, target_type, target_type, status, datecreated');
        if ($status !== null) {
            $this->db->where('status', $status);
        }
        return $this->db->get($this->table)->result();
    }

    public function add($data)
    {
        // Generate target_id if not provided
        if (empty($data['target_id'])) {
            $data['target_id'] = $this->generate_target_id();
        }

        // Format target_type to uppercase
        if (!empty($data['target_type'])) {
            $data['target_type'] = strtoupper($data['target_type']);
        }

        $data['datecreated'] = date('Y-m-d H:i:s');
        $data['dateupdated'] = date('Y-m-d H:i:s');
        
        $this->db->insert($this->table, $data);
        return $this->db->insert_id();
    }

    public function update($id, $data)
    {
        // Format target_type to uppercase
        if (!empty($data['target_type'])) {
            $data['target_type'] = strtoupper($data['target_type']);
        }

        $data['dateupdated'] = date('Y-m-d H:i:s');
        
        $this->db->where('id', $id);
        return $this->db->update($this->table, $data);
    }

    public function delete($id)
    {
        // Check if target is being used
        $this->db->where('target_id', $id);
        $used = $this->db->get(db_prefix() . 'topics')->num_rows() > 0;

        if ($used) {
            return false;
        }

        $this->db->where('id', $id);
        return $this->db->delete($this->table);
    }

    public function change_status($id, $status)
    {
        $this->db->where('id', $id);
        return $this->db->update($this->table, ['status' => $status]);
    }

    private function generate_target_id()
    {
        $this->db->select_max('target_id');
        $result = $this->db->get($this->table)->row();
        return ($result->target_id ?? 0) + 1;
    }

    // Get target statistics
    public function get_target_stats($target_id, $type = 'daily')
    {
        $this->db->select('COUNT(*) as total');
        $this->db->where('target_id', $target_id);
        
        switch($type) {
            case 'daily':
                $this->db->where('DATE(datecreated)', date('Y-m-d'));
                break;
            case 'weekly':
                $this->db->where('YEARWEEK(datecreated)', date('oW'));
                break;
            case 'monthly':
                $this->db->where('MONTH(datecreated)', date('m'));
                $this->db->where('YEAR(datecreated)', date('Y'));
                break;
        }
        
        return $this->db->get(db_prefix() . 'topics')->row()->total;
    }

    public function get_table_data($select = [], $where = [], $action_type_code = null)
    {
        $this->db->select([
            $this->table . '.*',
            't.data as data',  // Lấy data từ topics
            't.position as position',  // Lấy position từ topics
            't.id as topic_id',  // Lấy data từ topics
            'tas.valid_data',        // Lấy valid_data từ action_states
            'tas.action_type_code'   // Lấy action_type_code từ action_states
        ]);
        
        // Join với bảng topics để lấy data và action_state
        $this->db->join(
            db_prefix() . 'topics t',
            't.target_id = ' . $this->table . '.id',
            'left'
        );
        
        // Join với action_states thông qua topics
        $this->db->join(
            db_prefix() . 'topic_action_states tas', 
            'tas.action_state_code = t.action_state_code',
            'left'
        );
        
        if (!empty($select)) {
            $this->db->select($select);
        }
        
        if (!empty($where)) {
            $this->db->where($where);
        }
        
        // Chỉ lấy các target có trạng thái active
        $this->db->where($this->table . '.status', 1);
        
        // Lọc theo action_type_code được truyền vào và valid_data
        if ($action_type_code) {
            $this->db->where('t.action_type_code', $action_type_code);
            $this->db->where('tas.valid_data', 1);
        }
        
        // Add ordering by position
        $this->db->order_by('t.position', 'ASC');
        
        return $this->db->get($this->table)->result_array();
    }
} 