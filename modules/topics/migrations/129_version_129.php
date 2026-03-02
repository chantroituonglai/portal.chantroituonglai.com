<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_129 extends App_module_migration
{
    public function up()
    {
        $CI = &get_instance();
        
        // Add controller_only column to topic_action_buttons table
        $CI->db->query('ALTER TABLE ' . db_prefix() . 'topic_action_buttons ADD controller_only TINYINT(1) DEFAULT 0 AFTER status');

        // Update tables schema version
        update_option('topics_version', '1.2.9');
        
        // Log the migration activity
        log_activity('Migration 129: Added controller_only column to topic_action_buttons table');
    }

    public function down()
    {
        $CI = &get_instance();
        
        // Remove controller_only column from topic_action_buttons table if migration needs to be rolled back
        $CI->db->query('ALTER TABLE ' . db_prefix() . 'topic_action_buttons DROP COLUMN controller_only');
        
        // Log the rollback activity
        log_activity('Migration 129 Rollback: Removed controller_only column from topic_action_buttons table');
    }
} 