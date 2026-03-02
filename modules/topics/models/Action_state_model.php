<?php defined('BASEPATH') or exit('No direct script access allowed');

class Action_state_model extends App_Model
{
    protected $table;

    public function __construct()
    {
        parent::__construct();
        $this->table = db_prefix() . 'topic_action_states';
    }

    public function get_all_action_states()
    {
        $this->db->select([
            $this->table . '.id',
            $this->table . '.name',
            $this->table . '.action_state_code',
            $this->table . '.action_type_code',
            $this->table . '.position',
            $this->table . '.datecreated',
            $this->table . '.dateupdated',
            db_prefix() . 'topic_action_types.name as action_type_name'
        ]);
        
        $this->db->from($this->table);
        $this->db->join(
            db_prefix() . 'topic_action_types',
            db_prefix() . 'topic_action_types.action_type_code = ' . $this->table . '.action_type_code',
            'left'
        );
        
        $this->db->order_by('position', 'asc');
        return $this->db->get()->result();
    }

    public function get_action_state($id)
    {
        $this->db->select([
            $this->table . '.*',
            db_prefix() . 'topic_action_types.name as action_type_name'
        ]);
        
        $this->db->from($this->table);
        $this->db->join(
            db_prefix() . 'topic_action_types',
            db_prefix() . 'topic_action_types.action_type_code = ' . $this->table . '.action_type_code',
            'left'
        );
        $this->db->where($this->table . '.id', $id);
        
        return $this->db->get()->row();
    }

    public function add_action_state($data)
    {
        // Get max position
        $this->db->select_max('position');
        $query = $this->db->get($this->table);
        $max_position = $query->row()->position;
        
        // Set new position
        $data['position'] = $max_position + 1;

        $insert_data = [
            'name' => $data['name'],
            'action_state_code' => $data['action_state_code'],
            'action_type_code' => $data['action_type_code'],
            'position' => $data['position'],
            'datecreated' => date('Y-m-d H:i:s'),
            'dateupdated' => date('Y-m-d H:i:s')
        ];

        $this->db->insert($this->table, $insert_data);
        return [
            'success' => true,
            'id' => $this->db->insert_id()
        ];
    }

    public function update_action_state($id, $data)
    {
        // Validate unique code if code is changed
        $current_state = $this->get_action_state($id);
        if ($data['action_state_code'] !== $current_state->action_state_code) {
            $this->db->where('action_state_code', $data['action_state_code']);
            if ($this->db->get($this->table)->num_rows() > 0) {
                return false;
            }
        }

        $update_data = [
            'name' => $data['name'],
            'action_state_code' => $data['action_state_code'],
            'action_type_code' => $data['action_type_code'],
            'color' => $data['color'],
            'dateupdated' => date('Y-m-d H:i:s')
        ];

        $this->db->where('id', $id);
        return $this->db->update($this->table, $update_data);
    }

    public function delete_action_state($id)
    {
        $this->db->where('id', $id);
        return $this->db->delete($this->table);
    }

    public function get_states_by_type($action_type_code)
    {
        $this->db->where('action_type_code', $action_type_code);
        return $this->db->get($this->table)->result_array();
    }

    public function get_states_by_type_code($type_code)
    {
        $this->db->where('action_type_code', $type_code);
        return $this->db->get(db_prefix() . 'topic_action_states')->result_array();
    }

    public function get_state_by_code($state_code)
    {
        $this->db->where('action_state_code', $state_code);
        return $this->db->get(db_prefix() . 'topic_action_states')->row_array();
    }

    public function reorder_positions($positions) {
        foreach($positions as $id => $position) {
            $this->db->where('id', $id);
            $this->db->update($this->table, ['position' => $position]);
        }
        return true;
    }

    public function get_states_by_type_id($type_id)
    {
        $type = $this->db->get_where(db_prefix() . 'topic_action_types', ['id' => $type_id])->row();
        if (!$type) {
            return [];
        }

        $this->db->select([
            $this->table . '.id',
            $this->table . '.name',
            $this->table . '.action_state_code',
            $this->table . '.position',
            $this->table . '.action_type_code',
            $this->table . '.valid_data'
        ]);
        
        $this->db->where('action_type_code', $type->action_type_code);
        $this->db->order_by('position', 'asc');
        
        $results = $this->db->get($this->table)->result_array();
        
        // Ensure all required fields exist
        foreach ($results as &$row) {
            $row['id'] = (int)$row['id'];
            $row['position'] = (int)$row['position'];
            // Ensure strings are properly encoded
            $row['name'] = html_escape($row['name']);
            $row['action_state_code'] = html_escape($row['action_state_code']);
            $row['action_type_code'] = html_escape($row['action_type_code']);
            $row['valid_data'] = $row['valid_data'];
        }
        
        return $results;
    }

    public function toggle_valid_data($id)
    {
        // Check if record exists and get current state
        $this->db->where('id', $id);
        $state = $this->db->get($this->table)->row();
        
        if ($state) {
            // Get current valid_data value (ensure it's treated as boolean)
            $current_valid_data = (bool)$state->valid_data;
            
            $this->db->where('id', $id);
            $success = $this->db->update($this->table, [
                'valid_data' => !$current_valid_data,
                'dateupdated' => date('Y-m-d H:i:s')
            ]);

            if ($success) {
                log_activity('Action State Valid Data Changed [ID: ' . $id . ', New Value: ' . (!$current_valid_data ? '1' : '0') . ']');
            }

            return $success;
        }

        return false;
    }

    public function get_states_by_action_type($action_type_code)
    {
        $this->db->select([
            'id',
            'name',
            'action_state_code',
            'action_type_code'
        ]);
        $this->db->where('action_type_code', $action_type_code);
        $this->db->order_by('name', 'ASC');
        return $this->db->get(db_prefix() . 'topic_action_states')->result_array();
    }

    /**
     * Check if action state is in use by any topics
     * @param int $id Action state ID
     * @return bool True if in use, false otherwise
     */
    public function is_action_state_in_use($id)
    {
        // Get the action_state_code first
        $this->db->select('action_state_code');
        $this->db->where('id', $id);
        $state = $this->db->get($this->table)->row();

        if (!$state) {
            return false;
        }

        // Check if any topics are using this action state
        $this->db->where('action_state_code', $state->action_state_code);
        $count = $this->db->count_all_results(db_prefix() . 'topics');

        return $count > 0;
    }
} 