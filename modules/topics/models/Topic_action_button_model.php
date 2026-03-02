<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Topic_action_button_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->table = db_prefix() . 'topic_action_buttons';
    }

    public function get($id = '')
    {
        if ($id) {
            $this->db->where('id', $id);
            $result = $this->db->get($this->table)->row_array();
            
            // Decode JSON fields
            if ($result) {
                $result['ignore_types'] = !empty($result['ignore_types']) ? 
                    json_decode($result['ignore_types'], true) : [];
                $result['ignore_states'] = !empty($result['ignore_states']) ? 
                    json_decode($result['ignore_states'], true) : [];
            }
            return $result;
        }
        
        $this->db->order_by('order', 'asc');
        $results = $this->db->get($this->table)->result_array();
        
        // Decode JSON fields for all results
        foreach ($results as &$result) {
            $result['ignore_types'] = !empty($result['ignore_types']) ? 
                json_decode($result['ignore_types'], true) : [];
            $result['ignore_states'] = !empty($result['ignore_states']) ? 
                json_decode($result['ignore_states'], true) : [];
        }
        
        return $results;
    }

    public function add($data)
    {
        // Ensure trigger_type has valid value
        if (!isset($data['trigger_type']) || !in_array($data['trigger_type'], ['webhook', 'native'])) {
            $data['trigger_type'] = 'webhook';
        }

        $insert_data = [
            'name' => $data['name'],
            'button_type' => $data['button_type'],
            'workflow_id' => $data['workflow_id'],
            'trigger_type' => $data['trigger_type'],
            'target_action_type' => $data['target_action_type'],
            'target_action_state' => $data['target_action_state'],
            'description' => $data['description'],
            'status' => $data['status'],
            'controller_only' => isset($data['controller_only']) ? (int)$data['controller_only'] : 0,
            'order' => $data['order'] ?? 0,
            'ignore_types' => isset($data['ignore_types']) ? json_encode($data['ignore_types']) : null,
            'ignore_states' => isset($data['ignore_states']) ? json_encode($data['ignore_states']) : null,
            'action_command' => $data['action_command'] ?? null,
        ];

        $this->db->insert($this->table, $insert_data);
        return $this->db->insert_id();
    }

    public function update($data, $id)
    {
        // Ensure trigger_type has valid value
        if (!isset($data['trigger_type']) || !in_array($data['trigger_type'], ['webhook', 'native'])) {
            $data['trigger_type'] = 'webhook';
        }

        $update_data = [
            'name' => $data['name'],
            'button_type' => $data['button_type'],
            'workflow_id' => $data['workflow_id'],
            'trigger_type' => $data['trigger_type'],
            'target_action_type' => $data['target_action_type'],
            'target_action_state' => $data['target_action_state'],
            'description' => $data['description'],
            'status' => $data['status'],
            'controller_only' => isset($data['controller_only']) ? (int)$data['controller_only'] : 0,
            'order' => $data['order'] ?? 0,
            'ignore_types' => isset($data['ignore_types']) ? json_encode($data['ignore_types']) : null,
            'ignore_states' => isset($data['ignore_states']) ? json_encode($data['ignore_states']) : null,
            'action_command' => $data['action_command'] ?? null,
        ];

        $this->db->where('id', $id);
        return $this->db->update($this->table, $update_data);
    }

    public function delete($id)
    {
        $this->db->where('id', $id);
        return $this->db->delete($this->table);
    }

    public function get_active_buttons()
    {
        $this->db->select('id, name, button_type, workflow_id, target_action_type, target_action_state, ignore_types, ignore_states, action_command');
        $this->db->where('status', 1);
        $this->db->order_by('order', 'ASC');
        $results = $this->db->get($this->table)->result_array();
        
        // Decode JSON fields for all results
        foreach ($results as &$result) {
            $result['ignore_types'] = !empty($result['ignore_types']) ? 
                json_decode($result['ignore_types'], true) : [];
            $result['ignore_states'] = !empty($result['ignore_states']) ? 
                json_decode($result['ignore_states'], true) : [];
        }
        
        return $results;
    }

    public function change_status($id, $status)
    {
        $this->db->where('id', $id);
        return $this->db->update($this->table, ['status' => $status]);
    }

    public function get_by_workflow_id($workflow_id)
    {
        $this->db->where('workflow_id', $workflow_id);
        $this->db->where('status', 1);
        return $this->db->get($this->table)->row_array();
    }

    public function get_buttons_for_topic($topic_id)
    {
        $this->db->select('tab.*, t.action_type_code, t.action_state_code');
        $this->db->from(db_prefix() . 'topic_action_buttons tab');
        $this->db->join(db_prefix() . 'topics t', 't.id = ' . $topic_id);
        $buttons = $this->db->get()->result_array();
        
        $filtered_buttons = [];
        foreach ($buttons as $button) {
            // Check if button should be ignored based on type
            if (!empty($button['ignore_types'])) {
                $ignore_types = json_decode($button['ignore_types'], true);
                // Ensure ignore_types is an array before using in_array
                if (is_array($ignore_types) && in_array($button['action_type_code'], $ignore_types)) {
                    continue;
                }
            }
            
            // Check if button should be ignored based on state
            if (!empty($button['ignore_states'])) {
                $ignore_states = json_decode($button['ignore_states'], true);
                // Ensure ignore_states is an array before using in_array
                if (is_array($ignore_states) && in_array($button['action_state_code'], $ignore_states)) {
                    continue;
                }
            }
            
            $filtered_buttons[] = $button;
        }
        
        return $filtered_buttons;
    }

    public function save($data, $id = null)
    {
        // Handle ignore types and states arrays
        if (isset($data['ignore_types'])) {
            $data['ignore_types'] = json_encode($data['ignore_types']);
        }
        
        if (isset($data['ignore_states'])) {
            $data['ignore_states'] = json_encode($data['ignore_states']);
        }
        
        if ($id) {
            $this->db->where('id', $id);
            $this->db->update(db_prefix() . 'topic_action_buttons', $data);
            return $id;
        }
        
        $this->db->insert(db_prefix() . 'topic_action_buttons', $data);
        return $this->db->insert_id();
    }

    public function get_for_sorting()
    {
        $this->db->select('id, name, `order`');
        $this->db->from(db_prefix() . 'topic_action_buttons');
        $this->db->order_by('`order`', 'asc');
        
        $query = $this->db->get();
        return $query->result_array();
    }

    public function update_order($orders)
    {
        $this->db->trans_start();
        
        foreach ($orders as $item) {
            $this->db->where('id', $item['id']);
            $this->db->update(db_prefix() . 'topic_action_buttons', [
                'order' => $item['order']
            ]);
        }
        
        $this->db->trans_complete();
        
        return $this->db->trans_status();
    }

    public function get_by_target($target_type, $target_state)
    {
        $this->db->where('target_action_type', $target_type);
        $this->db->where('target_action_state', $target_state);
        return $this->db->get($this->table)->result_array();
    }

    /**
     * Get buttons for a topic, excluding controller_only buttons
     * This is used for topic detail view
     * 
     * @param int $topic_id Topic ID
     * @return array Filtered buttons for the topic
     */
    public function get_non_controller_only_buttons_for_topic($topic_id)
    {
        $this->db->select('tab.*, t.action_type_code, t.action_state_code');
        $this->db->from(db_prefix() . 'topic_action_buttons tab');
        $this->db->join(db_prefix() . 'topics t', 't.id = ' . $topic_id);
        $this->db->where('tab.controller_only', 0); // Only include buttons that are not controller_only
        $buttons = $this->db->get()->result_array();
        
        $filtered_buttons = [];
        foreach ($buttons as $button) {
            // Check if button should be ignored based on type
            if (!empty($button['ignore_types'])) {
                $ignore_types = json_decode($button['ignore_types'], true);
                // Ensure ignore_types is an array before using in_array
                if (is_array($ignore_types) && in_array($button['action_type_code'], $ignore_types)) {
                    continue;
                }
            }
            
            // Check if button should be ignored based on state
            if (!empty($button['ignore_states'])) {
                $ignore_states = json_decode($button['ignore_states'], true);
                // Ensure ignore_states is an array before using in_array
                if (is_array($ignore_states) && in_array($button['action_state_code'], $ignore_states)) {
                    continue;
                }
            }
            
            $filtered_buttons[] = $button;
        }
        
        return $filtered_buttons;
    }
} 