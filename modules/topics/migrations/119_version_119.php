<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_119 extends App_module_migration
{
    public function up()
    {
        $CI = &get_instance();
        
        // Add action_command column to topic_action_buttons
        if (!$CI->db->field_exists('action_command', db_prefix() . 'topic_action_buttons')) {
            $CI->db->query('ALTER TABLE `' . db_prefix() . 'topic_action_buttons` 
                ADD COLUMN `action_command` VARCHAR(255) NULL DEFAULT NULL 
                AFTER `trigger_type`');
        }
    }

    public function down()
    {
        $CI = &get_instance();
        
        // Remove action_command column if exists
        if ($CI->db->field_exists('action_command', db_prefix() . 'topic_action_buttons')) {
            $CI->db->query('ALTER TABLE `' . db_prefix() . 'topic_action_buttons` 
                DROP COLUMN `action_command`');
        }
    }
} 