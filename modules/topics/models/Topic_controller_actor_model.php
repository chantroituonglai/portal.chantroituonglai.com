<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Topic Controller Actor Model
 * Handles all operations for controller actors
 */
class Topic_controller_actor_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get all actors or a specific actor by ID
     * 
     * @param int $id Actor ID (optional)
     * @param int $controller_id Controller ID (optional)
     * @return array|object Actor(s) data
     */
    public function get($id = null, $controller_id = null)
    {
        if (is_numeric($id)) {
            $this->db->where('id', $id);
            return $this->db->get(db_prefix() . 'topic_controllers_actors')->row();
        }

        if (is_numeric($controller_id)) {
            $this->db->where('controller_id', $controller_id);
            $this->db->order_by('priority', 'ASC');
        }

        return $this->db->get(db_prefix() . 'topic_controllers_actors')->result_array();
    }

    /**
     * Add a new actor
     * 
     * @param array $data Actor data
     * @return int|boolean The new actor ID if added successfully, false otherwise
     */
    public function add($data)
    {
        // Set default values if not provided
        if (!isset($data['active'])) {
            $data['active'] = 1;
        }

        if (!isset($data['priority'])) {
            // Get the highest priority for this controller and add 1
            $this->db->select_max('priority');
            $this->db->where('controller_id', $data['controller_id']);
            $result = $this->db->get(db_prefix() . 'topic_controllers_actors')->row();
            $data['priority'] = (is_null($result->priority) ? 0 : $result->priority) + 1;
        }

        // Set timestamps
        $data['datecreated'] = date('Y-m-d H:i:s');
        $data['dateupdated'] = date('Y-m-d H:i:s');

        $this->db->insert(db_prefix() . 'topic_controllers_actors', $data);
        $insert_id = $this->db->insert_id();

        if ($insert_id) {
            log_activity('New Controller Actor Added [ID: ' . $insert_id . ', Controller ID: ' . $data['controller_id'] . ', Name: ' . $data['name'] . ']');
            return $insert_id;
        }

        return false;
    }

    /**
     * Update an existing actor
     * 
     * @param array $data Actor data
     * @param int $id Actor ID
     * @return boolean True if updated successfully, false otherwise
     */
    public function update($data, $id)
    {
        // Ensure data is an array before trying to unset fields
        if (is_array($data)) {
            // Remove unwanted fields
            if (isset($data['datecreated'])) {
                unset($data['datecreated']);
            }
            
            // Set updated timestamp
            $data['dateupdated'] = date('Y-m-d H:i:s');
        } else if (is_string($data) || is_numeric($data)) {
            // If called with a simple value (e.g. for status update), convert to proper array
            $field_name = 'active'; // Default field for simpler updates
            $data = [$field_name => $data];
            $data['dateupdated'] = date('Y-m-d H:i:s');
        }

        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'topic_controllers_actors', $data);

        if ($this->db->affected_rows() > 0) {
            $name = isset($data['name']) ? $data['name'] : '';
            log_activity('Controller Actor Updated [ID: ' . $id . ($name ? ', Name: ' . $name : '') . ']');
            return true;
        }

        return false;
    }

    /**
     * Delete an actor
     * 
     * @param int $id Actor ID
     * @return boolean True if deleted successfully, false otherwise
     */
    public function delete($id)
    {
        // Get actor details for logging
        $actor = $this->get($id);
        if (!$actor) {
            return false;
        }

        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'topic_controllers_actors');

        if ($this->db->affected_rows() > 0) {
            // Re-order priorities for remaining actors in this controller
            $this->reorder_priorities($actor->controller_id);
            
            log_activity('Controller Actor Deleted [ID: ' . $id . ', Controller ID: ' . $actor->controller_id . ', Name: ' . $actor->name . ']');
            return true;
        }

        return false;
    }

    /**
     * Update the active status of an actor
     * 
     * @param int $id Actor ID
     * @param int $status New status (0 or 1)
     * @return boolean True if updated successfully, false otherwise
     */
    public function change_status($id, $status)
    {
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'topic_controllers_actors', ['active' => $status]);

        if ($this->db->affected_rows() > 0) {
            log_activity('Controller Actor Status Changed [ID: ' . $id . ', Status: ' . ($status ? 'Active' : 'Inactive') . ']');
            return true;
        }

        return false;
    }

    /**
     * Update the priority of actors for a controller
     * 
     * @param int $controller_id Controller ID
     * @param array $priorities Array of actor IDs in order of priority
     * @return boolean True if updated successfully, false otherwise
     */
    public function update_priorities($controller_id, $priorities)
    {
        if (empty($priorities)) {
            return false;
        }

        $this->db->trans_start();

        $priority = 1;
        foreach ($priorities as $actor_id) {
            $this->db->where('id', $actor_id);
            $this->db->where('controller_id', $controller_id);
            $this->db->update(db_prefix() . 'topic_controllers_actors', ['priority' => $priority]);
            $priority++;
        }

        $this->db->trans_complete();

        if ($this->db->trans_status() !== FALSE) {
            log_activity('Actor Priorities Updated for Controller [ID: ' . $controller_id . ']');
            return true;
        }

        return false;
    }

    /**
     * Re-order priorities for actors in a controller after deletion
     * 
     * @param int $controller_id Controller ID
     * @return boolean True if reordered successfully, false otherwise
     */
    private function reorder_priorities($controller_id)
    {
        // Get all actors for this controller
        $this->db->where('controller_id', $controller_id);
        $this->db->order_by('priority', 'ASC');
        $actors = $this->db->get(db_prefix() . 'topic_controllers_actors')->result_array();

        if (empty($actors)) {
            return true;
        }

        $this->db->trans_start();

        $priority = 1;
        foreach ($actors as $actor) {
            $this->db->where('id', $actor['id']);
            $this->db->update(db_prefix() . 'topic_controllers_actors', ['priority' => $priority]);
            $priority++;
        }

        $this->db->trans_complete();

        return ($this->db->trans_status() !== FALSE);
    }

    /**
     * Get actors for a specific controller with status filter
     * 
     * @param int $controller_id Controller ID
     * @param int|null $status Status filter (0, 1, or null for all)
     * @return array Array of actors
     */
    public function get_actors_by_controller($controller_id, $status = null)
    {
        $this->db->where('controller_id', $controller_id);
        
        if ($status !== null) {
            $this->db->where('active', $status);
        }
        
        $this->db->order_by('priority', 'ASC');
        return $this->db->get(db_prefix() . 'topic_controllers_actors')->result_array();
    }
} 