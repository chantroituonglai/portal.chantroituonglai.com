<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_104 extends App_module_migration
{
    public function up()
    {
        $CI = &get_instance();
        
        // Thêm column action_type_code vào bảng action_types
        $CI->db->query("ALTER TABLE `" . db_prefix() . "topic_action_types` 
            ADD COLUMN IF NOT EXISTS `action_type_code` VARCHAR(50) NULL");

        // Generate codes cho các action types hiện có
        $types = $CI->db->get(db_prefix() . 'topic_action_types')->result_array();
        foreach($types as $type) {
            $code = strtoupper(preg_replace('/[^A-Za-z0-9]/', '_', $type['name']));
            $CI->db->where('id', $type['id']);
            $CI->db->update(db_prefix() . 'topic_action_types', 
                ['action_type_code' => $code]);
        }

        // Thêm unique constraint
        $CI->db->query("ALTER TABLE `" . db_prefix() . "topic_action_types` 
            ADD UNIQUE INDEX `action_type_code_unique` (`action_type_code`)");
    }
} 