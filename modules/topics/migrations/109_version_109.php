<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_109 extends App_module_migration
{
    public function up()
    {
        $CI = &get_instance();

        // 1. Drop unique constraint unique_state_per_type nếu tồn tại
        $CI->db->query("ALTER TABLE `" . db_prefix() . "topic_action_states` 
            DROP INDEX IF EXISTS `unique_state_per_type`");

        // 2. Drop unique constraint name trên action_types nếu tồn tại
        $CI->db->query("ALTER TABLE `" . db_prefix() . "topic_action_types` 
            DROP INDEX IF EXISTS `name`");

        // 3. Chỉ giữ lại unique constraint cho action_state_code và action_type_code
        $CI->db->query("ALTER TABLE `" . db_prefix() . "topic_action_states` 
            DROP INDEX IF EXISTS `action_state_code`,
            ADD UNIQUE KEY `action_state_code` (`action_state_code`)");

        $CI->db->query("ALTER TABLE `" . db_prefix() . "topic_action_types` 
            DROP INDEX IF EXISTS `action_type_code`,
            ADD UNIQUE KEY `action_type_code` (`action_type_code`)");
    }

    public function down()
    {
        $CI = &get_instance();

        // Khôi phục lại các unique constraints nếu cần rollback
        $CI->db->query("ALTER TABLE `" . db_prefix() . "topic_action_states` 
            ADD UNIQUE KEY `unique_state_per_type` (`name`, `action_type_code`)");

        $CI->db->query("ALTER TABLE `" . db_prefix() . "topic_action_types` 
            ADD UNIQUE KEY `name` (`name`)");
    }
} 