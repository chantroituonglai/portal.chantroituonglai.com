<?php defined('BASEPATH') or exit('No direct script access allowed');
class Action_type_model extends App_Model
{
    protected $table;

    public function __construct()
    {
        parent::__construct();
        $this->table = db_prefix() . 'topic_action_types';
    }

    public function get_all_action_types()
    {
        $this->db->order_by('position', 'asc');
        return $this->db->get($this->table)->result_array();
    }

    /**
     * Get action type by ID or code
     * @param mixed $identifier ID or action_type_code
     * @return object|null
     */
    public function get_action_type($identifier)
    {
        // If numeric, assume it's an ID
        if (is_numeric($identifier)) {
            $this->db->where('id', $identifier);
        } else {
            // Otherwise treat as action_type_code
            $this->db->where('action_type_code', $identifier);
        }
        return $this->db->get($this->table)->row();
    }

    public function add_action_type($data)
    {
        // Get max position
        $this->db->select_max('position');
        $query = $this->db->get($this->table);
        $max_position = $query->row()->position;
        
        // Set new position
        $data['position'] = $max_position + 1;
        
        // Validate unique code
        $this->db->where('action_type_code', $data['action_type_code']);
        if ($this->db->get($this->table)->num_rows() > 0) {
            return [
                'success' => false,
                'message' => 'Action type code must be unique'
            ];
        }

        $insert_data = [
            'name' => $data['name'],
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

    /**
     * Update action type by ID or code
     * @param mixed $identifier ID or action_type_code
     * @param array $data Update data
     * @return bool
     */
    public function update_action_type($identifier, $data)
    {
        $current = $this->get_action_type($identifier);
        $old_parent_id = $current->parent_id;
        $new_parent_id = !empty($data['parent_id']) ? $data['parent_id'] : null;
        $original_position = $current->position;

        // Tính toán position mới dựa trên parent_id
        if ($old_parent_id !== $new_parent_id) {
            if ($new_parent_id === null) {
                // Nếu bỏ parent_id -> về lại position gốc
                $data['position'] = $original_position;
            } else {
                // Nếu chọn parent mới -> lấy position của parent + 1
                $parent = $this->get_action_type($new_parent_id);
                $data['position'] = $parent->position + 1;
                
                // Dịch chuyển các items có position >= position mới
                $this->db->set('position', 'position + 1', false)
                         ->where('position >=', $data['position'])
                         ->where('id !=', $current->id)
                         ->update($this->table);
            }
        }

        $update_data = [
            'name' => $data['name'],
            'action_type_code' => $data['action_type_code'],
            'parent_id' => $new_parent_id,
            'position' => $data['position'],
            'dateupdated' => date('Y-m-d H:i:s')
        ];

        // Bắt đầu transaction
        $this->db->trans_start();

        // Update action type
        if (is_numeric($identifier)) {
            $this->db->where('id', $identifier);
        } else {
            $this->db->where('action_type_code', $identifier);
        }
        $this->db->update($this->table, $update_data);

        // Reposition tất cả sau khi update
        $this->reposition_all_types();

        $this->db->trans_complete();

        return $this->db->trans_status();
    }

    /**
     * Reposition all types
     */
    private function reposition_all_types()
    {
        // Lấy tất cả root types (không có parent)
        $this->db->where('parent_id IS NULL');
        $this->db->order_by('position', 'ASC');
        $root_types = $this->db->get($this->table)->result();

        $position = 1;
        
        // Reposition root types và children của chúng
        foreach ($root_types as $root) {
            // Update position cho root
            $this->db->where('id', $root->id);
            $this->db->update($this->table, ['position' => $position]);
            $position++;

            // Lấy và update position cho children
            $this->db->where('parent_id', $root->id);
            $this->db->order_by('position', 'ASC');
            $children = $this->db->get($this->table)->result();

            foreach ($children as $child) {
                $this->db->where('id', $child->id);
                $this->db->update($this->table, ['position' => $position]);
                $position++;
            }
        }
    }

    /**
     * Get next available position for parent
     */
    private function get_next_position_for_parent($parent_id) 
    {
        if ($parent_id === null) {
            // For root level, get max position
            $this->db->select_max('position');
            $this->db->where('parent_id IS NULL');
        } else {
            // For child level, get position after parent
            $parent = $this->get_action_type($parent_id);
            if ($parent) {
                return $parent->position + 1;
            }
            return 1;
        }
        
        $query = $this->db->get($this->table);
        $result = $query->row();
        return ($result->position ?? 0) + 1;
    }

    /**
     * Get all items with same parent
     */
    public function get_items_by_parent($parent_id = null)
    {
        $this->db->where('parent_id', $parent_id);
        $this->db->order_by('position', 'ASC');
        return $this->db->get($this->table)->result();
    }

    /**
     * Delete action type by ID or code
     * @param mixed $identifier ID or action_type_code
     * @return bool
     */
    public function delete_action_type($identifier)
    {
        // If numeric, delete by ID
        if (is_numeric($identifier)) {
            $this->db->where('id', $identifier);
        } else {
            // Otherwise delete by action_type_code
            $this->db->where('action_type_code', $identifier);
        }
        return $this->db->delete($this->table);
    }

    public function get_action_types_with_states() {
        $this->db->select([
            db_prefix() . 'topic_action_types.*',
            '(SELECT COUNT(*) FROM ' . db_prefix() . 'topic_action_states 
              WHERE action_type_code = ' . db_prefix() . 'topic_action_types.action_type_code) as states_count'
        ]);
        $this->db->from(db_prefix() . 'topic_action_types');
        $this->db->order_by('position', 'asc');
        
        return $this->db->get()->result_array();
    }

    public function get_states_for_type($action_type_code) {
        // Debug query
        log_activity('Fetching states for type: ' . $action_type_code);
        
        $this->db->select([
            db_prefix() . 'topic_action_states.id',
            db_prefix() . 'topic_action_states.name',
            db_prefix() . 'topic_action_states.action_state_code',
            db_prefix() . 'topic_action_states.action_type_code',
            db_prefix() . 'topic_action_states.datecreated',
            db_prefix() . 'topic_action_types.name as action_type_name',
            db_prefix() . 'topic_action_states.valid_data as valid_data'
        ]);
        
        $this->db->from(db_prefix() . 'topic_action_states');
        $this->db->join(
            db_prefix() . 'topic_action_types',
            db_prefix() . 'topic_action_types.action_type_code = ' . 
            db_prefix() . 'topic_action_states.action_type_code',
            'left'
        );
        $this->db->where(db_prefix() . 'topic_action_states.action_type_code', $action_type_code);
        
        // Debug SQL
        $query = $this->db->get();
        log_activity('SQL: ' . $this->db->last_query());
        
        return $query->result_array();
    }

    public function reorder_positions($positions) 
    {
        $this->db->trans_start();

        // Update các positions mới
        foreach($positions as $id => $position) {
            $this->db->where('id', $id);
            $this->db->update($this->table, ['position' => $position]);
        }

        // Reposition lại toàn bộ để đảm bảo thứ tự đúng
        $this->reposition_all_types();

        $this->db->trans_complete();

        return $this->db->trans_status();
    }

    /**
     * Get available parent types for selection
     * Exclude current type and its children to avoid circular reference
     */
    public function get_available_parents($current_id = null) 
    {
        // Get all children of current type
        $children = [];
        if ($current_id) {
            $children = $this->get_all_children($current_id);
        }

        // Exclude current id and all its children
        $exclude_ids = array_merge([$current_id], $children);
        
        $this->db->select('id, name, action_type_code');
        $this->db->from($this->table);
        if (!empty($exclude_ids)) {
            $this->db->where_not_in('id', $exclude_ids);
        }
        $this->db->order_by('position', 'ASC');
        $this->db->order_by('name', 'ASC');
        
        return $this->db->get()->result_array();
    }

    /**
     * Check if one type is a child (direct or indirect) of another type
     * @param int $parent_id The potential parent ID
     * @param int $child_id The potential child ID
     * @return bool True if child_id is a child of parent_id
     */
    public function is_child($parent_id, $child_id) 
    {
        // Get all children of the potential parent
        $children = $this->get_all_children($parent_id);
        
        // Check if child_id is in the children array
        return in_array($child_id, $children);
    }

    /**
     * Get all children IDs recursively
     * @param int $parent_id Parent ID to get children for
     * @return array Array of child IDs
     */
    private function get_all_children($parent_id) 
    {
        $children = [];
        
        // Get direct children
        $this->db->select('id');
        $this->db->from($this->table);
        $this->db->where('parent_id', $parent_id);
        $direct_children = $this->db->get()->result_array();
        
        foreach ($direct_children as $child) {
            $children[] = $child['id'];
            // Get children of this child recursively
            $sub_children = $this->get_all_children($child['id']);
            $children = array_merge($children, $sub_children);
        }
        
        return $children;
    }

    public function get_ordered_action_types() {
        // Lấy root types (không có parent) theo position
        $this->db->where('parent_id IS NULL');
        $this->db->order_by('position', 'ASC');
        $root_types = $this->db->get($this->table)->result_array();
        
        $ordered_types = [];
        foreach($root_types as $root) {
            // Thêm root type
            $ordered_types[] = $root;
            
            // Lấy children của root type theo position
            $this->db->where('parent_id', $root['id']);
            $this->db->order_by('position', 'ASC'); 
            $children = $this->db->get($this->table)->result_array();
            
            foreach($children as $child) {
                $ordered_types[] = $child;
            }
        }
        
        return $ordered_types;
    }
} 