<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Topic_controller_action_button_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->table = db_prefix() . 'topic_controller_action_buttons';
    }

    /**
     * Lấy tất cả mối quan hệ hoặc theo controller_id hoặc action_button_id cụ thể
     *
     * @param integer $controller_id Optional - ID của controller
     * @param integer $action_button_id Optional - ID của action button
     * @return array
     */
    public function get($controller_id = null, $action_button_id = null)
    {
        if ($controller_id) {
            $this->db->where('controller_id', $controller_id);
        }
        
        if ($action_button_id) {
            $this->db->where('action_button_id', $action_button_id);
        }
        
        $this->db->order_by('order', 'asc');
        return $this->db->get($this->table)->result_array();
    }

    /**
     * Lấy chi tiết một mối quan hệ cụ thể
     *
     * @param integer $id ID của mối quan hệ
     * @return array
     */
    public function get_by_id($id)
    {
        $this->db->where('id', $id);
        return $this->db->get($this->table)->row_array();
    }

    /**
     * Thêm mới mối quan hệ
     *
     * @param array $data
     * @return integer
     */
    public function add($data)
    {
        // Kiểm tra xem mối quan hệ đã tồn tại chưa
        $this->db->where('controller_id', $data['controller_id']);
        $this->db->where('action_button_id', $data['action_button_id']);
        $exists = $this->db->get($this->table)->row();
        if ($exists) {
            return false; // Trả về false nếu đã tồn tại
        }

        $this->db->insert($this->table, [
            'controller_id' => $data['controller_id'],
            'action_button_id' => $data['action_button_id'],
            'status' => isset($data['status']) ? $data['status'] : 1,
            'order' => isset($data['order']) ? $data['order'] : 0
        ]);

        return $this->db->insert_id();
    }

    /**
     * Cập nhật mối quan hệ
     *
     * @param array $data
     * @param integer $id
     * @return boolean
     */
    public function update($data, $id)
    {
        $this->db->where('id', $id);
        return $this->db->update($this->table, [
            'status' => isset($data['status']) ? $data['status'] : 1,
            'order' => isset($data['order']) ? $data['order'] : 0
        ]);
    }

    /**
     * Xóa mối quan hệ
     *
     * @param integer $id
     * @return boolean
     */
    public function delete($id)
    {
        $this->db->where('id', $id);
        return $this->db->delete($this->table);
    }

    /**
     * Xóa tất cả mối quan hệ của một controller
     *
     * @param integer $controller_id
     * @return boolean
     */
    public function delete_by_controller($controller_id)
    {
        $this->db->where('controller_id', $controller_id);
        return $this->db->delete($this->table);
    }

    /**
     * Xóa tất cả mối quan hệ của một action button
     *
     * @param integer $action_button_id
     * @return boolean
     */
    public function delete_by_action_button($action_button_id)
    {
        $this->db->where('action_button_id', $action_button_id);
        return $this->db->delete($this->table);
    }

    /**
     * Thay đổi trạng thái của mối quan hệ
     *
     * @param integer $id
     * @param integer $status
     * @return boolean
     */
    public function change_status($id, $status)
    {
        $this->db->where('id', $id);
        return $this->db->update($this->table, ['status' => $status]);
    }

    /**
     * Cập nhật thứ tự của các mối quan hệ
     *
     * @param array $orders Mảng các mối quan hệ với ID và order
     * @return boolean
     */
    public function update_order($orders)
    {
        $this->db->trans_start();
        
        foreach ($orders as $item) {
            $this->db->where('id', $item['id']);
            $this->db->update($this->table, [
                'order' => $item['order']
            ]);
        }
        
        $this->db->trans_complete();
        
        return $this->db->trans_status();
    }

    /**
     * Lấy Action Buttons theo Controller ID với chi tiết đầy đủ
     *
     * @param integer $controller_id
     * @return array
     */
    public function get_action_buttons_by_controller($controller_id)
    {
        $this->db->select('cab.id, cab.controller_id, cab.action_button_id, cab.status, cab.order, 
                          ab.name, ab.button_type, ab.workflow_id, ab.trigger_type, 
                          ab.target_action_type, ab.target_action_state, ab.description, 
                          ab.ignore_types, ab.ignore_states, ab.action_command');
        $this->db->from($this->table . ' cab');
        $this->db->join(db_prefix() . 'topic_action_buttons ab', 'cab.action_button_id = ab.id');
        $this->db->where('cab.controller_id', $controller_id);
        $this->db->where('cab.status', 1);
        $this->db->where('ab.status', 1);
        $this->db->order_by('cab.order', 'asc');
        
        $results = $this->db->get()->result_array();
        
        // Decode JSON fields
        foreach ($results as &$result) {
            $result['ignore_types'] = !empty($result['ignore_types']) ? 
                json_decode($result['ignore_types'], true) : [];
                
            $result['ignore_states'] = !empty($result['ignore_states']) ? 
                json_decode($result['ignore_states'], true) : [];
        }
        
        return $results;
    }
    
    /**
     * Lấy danh sách Controllers sử dụng một Action Button cụ thể
     *
     * @param integer $action_button_id
     * @return array
     */
    public function get_controllers_by_action_button($action_button_id)
    {
        $this->db->select('cab.id, cab.controller_id, cab.action_button_id, cab.status, cab.order, c.*');
        $this->db->from($this->table . ' cab');
        $this->db->join(db_prefix() . 'topic_controllers c', 'cab.controller_id = c.id');
        $this->db->where('cab.action_button_id', $action_button_id);
        $this->db->where('cab.status', 1);
        $this->db->order_by('c.name', 'asc');
        
        return $this->db->get()->result_array();
    }
} 