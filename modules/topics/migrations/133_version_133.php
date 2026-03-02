<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_133 extends App_module_migration
{
    public function up()
    {
        $CI = &get_instance();
        $db_name = $CI->db->database;
        
        // Add selected_categories column to topic_controllers table
        $topic_controllers_table = db_prefix() . 'topic_controllers';
        
        if (!$CI->db->field_exists('selected_categories', $topic_controllers_table)) {
            $CI->db->query("ALTER TABLE {$topic_controllers_table} ADD COLUMN selected_categories TEXT NULL COMMENT 'Selected categories saved as JSON array'");
            log_activity('Migration 133: Added selected_categories column to ' . $topic_controllers_table);
        }
        
        // Remove blog_id column as it's no longer needed
        if ($CI->db->field_exists('blog_id', $topic_controllers_table)) {
            // First save any existing blog_id values that might be needed
            $controllers = $CI->db->get($topic_controllers_table)->result_array();
            foreach ($controllers as $controller) {
                if (!empty($controller['blog_id'])) {
                    // Store the blog_id in the controller state or another field if needed
                    $categories_state = json_decode($controller['categories_state'], true) ?: [];
                    $categories_state['blog_id'] = $controller['blog_id'];
                    
                    $CI->db->where('id', $controller['id']);
                    $CI->db->update($topic_controllers_table, [
                        'categories_state' => json_encode($categories_state)
                    ]);
                    
                    log_activity('Migration 133: Saved blog_id value to categories_state for controller ID ' . $controller['id']);
                }
            }
            
            // Now drop the column
            $CI->db->query("ALTER TABLE {$topic_controllers_table} DROP COLUMN blog_id");
            log_activity('Migration 133: Removed blog_id column from ' . $topic_controllers_table);
        }
        
        // Update tables schema version
        update_option('topics_version', '1.3.3');
        
        // Log the migration activity
        log_activity('Migration 133: Added selected_categories column and removed blog_id column from topic_controllers table');
    }

    public function down()
    {
        $CI = &get_instance();
        $db_name = $CI->db->database;
        $topic_controllers_table = db_prefix() . 'topic_controllers';
        
        // Drop the selected_categories column if it exists
        if ($CI->db->field_exists('selected_categories', $topic_controllers_table)) {
            $CI->db->query("ALTER TABLE {$topic_controllers_table} DROP COLUMN selected_categories");
            log_activity('Migration 133 Rollback: Dropped selected_categories column from ' . $topic_controllers_table);
        }
        
        // Add back blog_id column if it doesn't exist
        if (!$CI->db->field_exists('blog_id', $topic_controllers_table)) {
            $CI->db->query("ALTER TABLE {$topic_controllers_table} ADD COLUMN blog_id VARCHAR(100) NULL");
            log_activity('Migration 133 Rollback: Added back blog_id column to ' . $topic_controllers_table);
            
            // Try to restore blog_id values from categories_state if available
            $controllers = $CI->db->get($topic_controllers_table)->result_array();
            foreach ($controllers as $controller) {
                if (!empty($controller['categories_state'])) {
                    $categories_state = json_decode($controller['categories_state'], true) ?: [];
                    
                    if (isset($categories_state['blog_id'])) {
                        $CI->db->where('id', $controller['id']);
                        $CI->db->update($topic_controllers_table, [
                            'blog_id' => $categories_state['blog_id']
                        ]);
                        
                        log_activity('Migration 133 Rollback: Restored blog_id value for controller ID ' . $controller['id']);
                    }
                }
            }
        }
        
        // Log the rollback activity
        log_activity('Migration 133 Rollback: Reversed changes - added back blog_id column and removed selected_categories column');
    }

    /**
     * Run this method to fix any controllers that might have been missed
     * by the original migration
     */
    public function fix_remaining_blog_ids()
    {
        $CI = &get_instance();
        $topic_controllers_table = db_prefix() . 'topic_controllers';
        
        // Check if the blog_id field still exists (it shouldn't)
        if ($CI->db->field_exists('blog_id', $topic_controllers_table)) {
            log_activity('Warning: blog_id column still exists in ' . $topic_controllers_table);
            return;
        }
        
        // Find all controllers that have a $_POST reference to blog_id
        $result = $CI->db->query("
            SELECT id, categories_state 
            FROM {$topic_controllers_table} 
            WHERE categories_state IS NOT NULL
        ")->result();
        
        foreach ($result as $controller) {
            if ($controller->categories_state) {
                $categories_state = json_decode($controller->categories_state, true);
                
                // If we find a controller that doesn't have blog_id in categories_state,
                // check if there's a _POST reference to blog_id and use that
                // This is a safety measure for any controllers that might have been missed
                
                if (!isset($categories_state['blog_id']) && isset($_POST['blog_id'])) {
                    $categories_state['blog_id'] = $_POST['blog_id'];
                    
                    $CI->db->where('id', $controller->id);
                    $CI->db->update($topic_controllers_table, [
                        'categories_state' => json_encode($categories_state)
                    ]);
                    
                    log_activity('Migration 133 Fix: Updated categories_state with blog_id for controller ID ' . $controller->id);
                }
            }
        }
        
        log_activity('Migration 133 Fix: Completed check for missing blog_id values in categories_state');
    }
} 