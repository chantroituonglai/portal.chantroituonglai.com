<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_132 extends App_module_migration
{
    public function up()
    {
        $CI = &get_instance();
        $db_name = $CI->db->database;
        
        // Create new table for topic_controllers_actors
        if (!$CI->db->table_exists(db_prefix() . 'topic_controllers_actors')) {
            $CI->db->query("
                CREATE TABLE `" . db_prefix() . "topic_controllers_actors` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `controller_id` int(11) NOT NULL,
                  `name` varchar(255) NOT NULL COMMENT 'Actor name',
                  `description` text DEFAULT NULL COMMENT 'Actor description',
                  `priority` int(11) DEFAULT 0 COMMENT 'Actor priority level',
                  `active` tinyint(1) DEFAULT 1 COMMENT 'Actor status',
                  `datecreated` datetime DEFAULT CURRENT_TIMESTAMP,
                  `dateupdated` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                  PRIMARY KEY (`id`),
                  KEY `controller_id` (`controller_id`),
                  KEY `active` (`active`),
                  KEY `priority` (`priority`),
                  CONSTRAINT `" . db_prefix() . "topic_controllers_actors_ibfk_1` FOREIGN KEY (`controller_id`) REFERENCES `" . db_prefix() . "topic_controllers` (`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
            ");
            
            log_activity('Migration 132: Created new table ' . db_prefix() . 'topic_controllers_actors');
        }
        
        // Check if action_1 and action_2 columns exist in the topic_controllers table
        $topic_controllers_table = db_prefix() . 'topic_controllers';
        
        // Migrate existing data from action_1 and action_2 to the new actors table
        if ($CI->db->field_exists('action_1', $topic_controllers_table) || 
            $CI->db->field_exists('action_2', $topic_controllers_table)) {
            
            // Get all controllers with action data
            $controllers = $CI->db->get($topic_controllers_table)->result_array();
            
            foreach ($controllers as $controller) {
                // If action_1 exists and has data, create an actor record
                if (!empty($controller['action_1'])) {
                    $CI->db->insert(db_prefix() . 'topic_controllers_actors', [
                        'controller_id' => $controller['id'],
                        'name' => 'Actor 1',
                        'description' => $controller['action_1'],
                        'priority' => 1,
                        'active' => 1,
                        'datecreated' => date('Y-m-d H:i:s'),
                        'dateupdated' => date('Y-m-d H:i:s')
                    ]);
                    
                    log_activity('Migration 132: Created Actor 1 from action_1 for controller ID ' . $controller['id']);
                }
                
                // If action_2 exists and has data, create an actor record
                if (!empty($controller['action_2'])) {
                    $CI->db->insert(db_prefix() . 'topic_controllers_actors', [
                        'controller_id' => $controller['id'],
                        'name' => 'Actor 2',
                        'description' => $controller['action_2'],
                        'priority' => 2,
                        'active' => 1,
                        'datecreated' => date('Y-m-d H:i:s'),
                        'dateupdated' => date('Y-m-d H:i:s')
                    ]);
                    
                    log_activity('Migration 132: Created Actor 2 from action_2 for controller ID ' . $controller['id']);
                }
            }
        }
        
        // Now drop the action_1 and action_2 columns after migrating their data
        if ($CI->db->field_exists('action_1', $topic_controllers_table)) {
            $CI->db->query("ALTER TABLE {$topic_controllers_table} DROP COLUMN action_1");
            log_activity('Migration 132: Dropped action_1 column from ' . $topic_controllers_table);
        }
        
        if ($CI->db->field_exists('action_2', $topic_controllers_table)) {
            $CI->db->query("ALTER TABLE {$topic_controllers_table} DROP COLUMN action_2");
            log_activity('Migration 132: Dropped action_2 column from ' . $topic_controllers_table);
        }
        
        // Update tables schema version
        update_option('topics_version', '1.3.2');
        
        // Log the migration activity
        log_activity('Migration 132: Added actor functionality to replace action_1 and action_2');
    }

    public function down()
    {
        $CI = &get_instance();
        $db_name = $CI->db->database;
        $topic_controllers_table = db_prefix() . 'topic_controllers';
        
        // Check if the columns action_1 and action_2 don't exist, add them back
        if (!$CI->db->field_exists('action_1', $topic_controllers_table)) {
            $CI->db->query("ALTER TABLE {$topic_controllers_table} ADD COLUMN action_1 TEXT NULL");
            log_activity('Migration 132 Rollback: Added back action_1 column to ' . $topic_controllers_table);
        }
        
        if (!$CI->db->field_exists('action_2', $topic_controllers_table)) {
            $CI->db->query("ALTER TABLE {$topic_controllers_table} ADD COLUMN action_2 TEXT NULL");
            log_activity('Migration 132 Rollback: Added back action_2 column to ' . $topic_controllers_table);
        }
        
        // If the actors table exists, restore the data back to action_1 and action_2
        if ($CI->db->table_exists(db_prefix() . 'topic_controllers_actors')) {
            // Get all the actors grouped by controller_id
            $CI->db->select('controller_id, id, name, description, priority')
                 ->from(db_prefix() . 'topic_controllers_actors')
                 ->order_by('controller_id, priority');
            $actors = $CI->db->get()->result_array();
            
            // Group actors by controller_id
            $controllers_actors = [];
            foreach ($actors as $actor) {
                if (!isset($controllers_actors[$actor['controller_id']])) {
                    $controllers_actors[$actor['controller_id']] = [];
                }
                $controllers_actors[$actor['controller_id']][] = $actor;
            }
            
            // Update action_1 and action_2 columns for each controller
            foreach ($controllers_actors as $controller_id => $controller_actors) {
                $update_data = [];
                
                if (isset($controller_actors[0])) {
                    $update_data['action_1'] = $controller_actors[0]['description'];
                }
                
                if (isset($controller_actors[1])) {
                    $update_data['action_2'] = $controller_actors[1]['description'];
                }
                
                if (!empty($update_data)) {
                    $CI->db->where('id', $controller_id)
                          ->update($topic_controllers_table, $update_data);
                    log_activity('Migration 132 Rollback: Restored actor data to action_1/action_2 for controller ID ' . $controller_id);
                }
            }
            
            // Drop the actors table
            $CI->db->query("DROP TABLE IF EXISTS " . db_prefix() . "topic_controllers_actors");
            log_activity('Migration 132 Rollback: Dropped ' . db_prefix() . 'topic_controllers_actors table');
        }
        
        // Log the rollback activity
        log_activity('Migration 132 Rollback: Reverted actor functionality to action_1 and action_2');
    }
} 