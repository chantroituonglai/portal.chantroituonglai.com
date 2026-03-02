<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_115 extends App_module_migration
{
    public function up()
    {
        $CI = &get_instance();

        // 1. Add trigger_type column to topic_action_buttons
        $CI->db->query("ALTER TABLE `" . db_prefix() . "topic_action_buttons` 
            ADD COLUMN IF NOT EXISTS `trigger_type` ENUM('webhook', 'native') NOT NULL DEFAULT 'webhook' AFTER `workflow_id`,
            ADD COLUMN IF NOT EXISTS `settings` text NULL AFTER `description`,
            ADD INDEX IF NOT EXISTS `trigger_type` (`trigger_type`)");

        // 2. Update existing buttons to use webhook by default
        $CI->db->update(db_prefix() . 'topic_action_buttons', [
            'trigger_type' => 'webhook'
        ]);
    }

    public function down()
    {
        $CI = &get_instance();

        // Remove trigger_type column and settings
        $CI->db->query("ALTER TABLE `" . db_prefix() . "topic_action_buttons` 
            DROP COLUMN IF EXISTS `trigger_type`,
            DROP COLUMN IF EXISTS `settings`,
            DROP INDEX IF EXISTS `trigger_type`");
    }
} 