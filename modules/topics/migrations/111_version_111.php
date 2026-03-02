<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_111 extends App_module_migration
{
    public function up()
    {
        $CI = &get_instance();

        // Add position and data columns to topics table
        $CI->db->query("ALTER TABLE `" . db_prefix() . "topics` 
            ADD COLUMN IF NOT EXISTS `position` INT DEFAULT 0,
            ADD COLUMN IF NOT EXISTS `data` LONGTEXT NULL");

        // Add valid_data column to topic_action_states table
        $CI->db->query("ALTER TABLE `" . db_prefix() . "topic_action_states` 
            ADD COLUMN IF NOT EXISTS `valid_data` TINYINT(1) DEFAULT 0");
    }
} 