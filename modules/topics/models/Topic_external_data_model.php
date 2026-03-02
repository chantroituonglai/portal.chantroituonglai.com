<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Topic_external_data_model extends App_Model
{
    protected $table;

    public function __construct()
    {
        parent::__construct();
        $this->table = db_prefix() . 'topic_external_data';
    }

    /**
     * Add or update external data
     * @param array $data Data to save
     * @return mixed
     */
    public function save($data)
    {
        // Validate required fields
        $required = ['topic_master_id', 'rel_type', 'rel_id'];
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                return [
                    'success' => false,
                    'message' => 'Missing required field: ' . $field
                ];
            }
        }

        // Check if record exists
        $existing = $this->get_by_rel(
            $data['topic_master_id'],
            $data['rel_type'],
            $data['rel_id']
        );

        if ($existing) {
            // Update existing record
            $this->db->where('id', $existing->id);
            $update_data = [
                'rel_data' => $data['rel_data'] ?? $existing->rel_data,
                'rel_data_raw' => $data['rel_data_raw'] ?? $existing->rel_data_raw,
                'dateupdated' => date('Y-m-d H:i:s')
            ];
            
            $success = $this->db->update($this->table, $update_data);
            $id = $existing->id;
        } else {
            // Insert new record
            $insert_data = [
                'topic_master_id' => $data['topic_master_id'],
                'rel_type' => $data['rel_type'],
                'rel_id' => $data['rel_id'],
                'rel_data' => $data['rel_data'] ?? null,
                'rel_data_raw' => $data['rel_data_raw'] ?? null,
                'datecreated' => date('Y-m-d H:i:s'),
                'dateupdated' => date('Y-m-d H:i:s')
            ];
            
            $success = $this->db->insert($this->table, $insert_data);
            $id = $this->db->insert_id();
        }

        if ($success) {
            return [
                'success' => true,
                'id' => $id
            ];
        }

        return [
            'success' => false,
            'message' => 'Failed to save external data'
        ];
    }

    /**
     * Get external data by ID
     * @param int $id
     * @return object|null
     */
    public function get($id)
    {
        return $this->db->where('id', $id)
                       ->get($this->table)
                       ->row();
    }

    /**
     * Get external data by relation
     * @param int $topic_master_id
     * @param string $rel_type
     * @param string $rel_id
     * @return object|null
     */
    public function get_by_rel($topic_master_id, $rel_type, $rel_id)
    {
        return $this->db->where([
                'topic_master_id' => $topic_master_id,
                'rel_type' => $rel_type,
                'rel_id' => $rel_id
            ])
            ->get($this->table)
            ->row();
    }

    /**
     * Get all external data for a topic
     * @param int $topic_master_id
     * @param string $rel_type Optional
     * @return array
     */
    public function get_for_topic($topic_master_id, $rel_type = null)
    {
        $this->db->where('topic_master_id', $topic_master_id);
        if ($rel_type) {
            $this->db->where('rel_type', $rel_type);
        }
        return $this->db->get($this->table)->result_array();
    }

    /**
     * Delete external data
     * @param int $id
     * @return bool
     */
    public function delete($id)
    {
        $this->db->where('id', $id);
        return $this->db->delete($this->table);
    }

    /**
     * Delete by relation
     * @param int $topic_master_id
     * @param string $rel_type
     * @param string $rel_id
     * @return bool
     */
    public function delete_by_rel($topic_master_id, $rel_type, $rel_id)
    {
        return $this->db->where([
            'topic_master_id' => $topic_master_id,
            'rel_type' => $rel_type,
            'rel_id' => $rel_id
        ])->delete($this->table);
    }

    /**
     * Check if external data exists
     * @param int $topic_master_id
     * @param string $rel_type
     * @param string $rel_id
     * @return bool
     */
    public function exists($topic_master_id, $rel_type, $rel_id)
    {
        return $this->db->where([
            'topic_master_id' => $topic_master_id,
            'rel_type' => $rel_type,
            'rel_id' => $rel_id
        ])->count_all_results($this->table) > 0;
    }
} 