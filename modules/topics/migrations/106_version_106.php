<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_106 extends App_module_migration
{
    public function up()
    {
        $CI = &get_instance();

        // 1. Add color column to action_states
        $CI->db->query("ALTER TABLE `" . db_prefix() . "topic_action_states` 
            ADD COLUMN IF NOT EXISTS `color` VARCHAR(7) DEFAULT '#000000'");

        // 2. Add master_record and status columns to topics
        $CI->db->query("ALTER TABLE `" . db_prefix() . "topics` 
            ADD COLUMN IF NOT EXISTS `master_record` BOOLEAN DEFAULT FALSE,
            ADD COLUMN IF NOT EXISTS `status` BOOLEAN DEFAULT TRUE");

        // 3. Update existing topics to set first record as master for each topicid
        $CI->db->query("
            UPDATE `" . db_prefix() . "topics` t1
            JOIN (
                SELECT topicid, MIN(id) as first_id
                FROM `" . db_prefix() . "topics`
                GROUP BY topicid
            ) t2 ON t1.topicid = t2.topicid AND t1.id = t2.first_id
            SET t1.master_record = TRUE
        ");

        // 4. Create index for performance
        $CI->db->query("ALTER TABLE `" . db_prefix() . "topics` 
            ADD INDEX `idx_status` (`status`),
            ADD INDEX `idx_master_record` (`master_record`)");
    }

    public function down()
    {
        $CI = &get_instance();

        // Remove added columns
        $CI->db->query("ALTER TABLE `" . db_prefix() . "topic_action_states` 
            DROP COLUMN IF EXISTS `color`");

        $CI->db->query("ALTER TABLE `" . db_prefix() . "topics` 
            DROP COLUMN IF EXISTS `master_record`,
            DROP COLUMN IF EXISTS `status`,
            DROP INDEX IF EXISTS `idx_status`,
            DROP INDEX IF EXISTS `idx_master_record`");
    }
} 