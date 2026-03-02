<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_118 extends App_module_migration
{
    public function up()
    {
        $CI = &get_instance();
        
        // Add ignore_types and ignore_states columns to topic_action_buttons
        $CI->db->query('ALTER TABLE `' . db_prefix() . 'topic_action_buttons` 
            ADD COLUMN `ignore_types` TEXT NULL AFTER `target_action_type`,
            ADD COLUMN `ignore_states` TEXT NULL AFTER `target_action_state`');
    }
}