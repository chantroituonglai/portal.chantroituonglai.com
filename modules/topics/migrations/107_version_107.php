<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_107 extends App_module_migration
{
    public function up()
    {
        $CI = &get_instance();

        // Add position column to action_types
        $CI->db->query("ALTER TABLE `" . db_prefix() . "topic_action_types` 
            ADD COLUMN IF NOT EXISTS `position` INT DEFAULT 0");

        // Add position column to action_states  
        $CI->db->query("ALTER TABLE `" . db_prefix() . "topic_action_states`
            ADD COLUMN IF NOT EXISTS `position` INT DEFAULT 0");

        // Update existing positions based on ID order for action_types
        $CI->db->query("SET @pos := 0");
        $CI->db->query("UPDATE `" . db_prefix() . "topic_action_types` 
            SET position = (@pos := @pos + 1) 
            ORDER BY id ASC");

        // Update existing positions based on ID order for action_states
        $CI->db->query("SET @pos := 0"); 
        $CI->db->query("UPDATE `" . db_prefix() . "topic_action_states` 
            SET position = (@pos := @pos + 1)
            ORDER BY id ASC");
    }

    public function down()
    {
        $CI = &get_instance();

        $CI->db->query("ALTER TABLE `" . db_prefix() . "topic_action_types` 
            DROP COLUMN IF EXISTS `position`");

        $CI->db->query("ALTER TABLE `" . db_prefix() . "topic_action_states` 
            DROP COLUMN IF EXISTS `position`");
    }
} 