<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_105 extends App_module_migration
{
    public function up()
    {
        $CI = &get_instance();

        // 1. Backup data từ bảng topics
        $topics = $CI->db->get(db_prefix() . 'topics')->result_array();
        
        // 2. Thêm columns mới vào bảng topics
        $CI->db->query("ALTER TABLE `" . db_prefix() . "topics` 
            ADD COLUMN IF NOT EXISTS `action_type_code` VARCHAR(50) NULL,
            ADD COLUMN IF NOT EXISTS `action_state_code` VARCHAR(50) NULL");

        // 3. Update data từ action_type sang action_type_code
        foreach($topics as $topic) {
            if(!empty($topic['action_type'])) {
                $type = $CI->db->get_where(db_prefix() . 'topic_action_types', 
                    ['id' => $topic['action_type']])->row();
                if($type) {
                    $CI->db->where('id', $topic['id']);
                    $CI->db->update(db_prefix() . 'topics', 
                        ['action_type_code' => $type->action_type_code]);
                }
            }
            
            if(!empty($topic['action_state'])) {
                $state = $CI->db->get_where(db_prefix() . 'topic_action_states', 
                    ['id' => $topic['action_state']])->row();
                if($state) {
                    $CI->db->where('id', $topic['id']);
                    $CI->db->update(db_prefix() . 'topics', 
                        ['action_state_code' => $state->action_state_code]);
                }
            }
        }

        // 4. Drop old columns từ bảng topics
        $CI->db->query("ALTER TABLE `" . db_prefix() . "topics` 
            DROP COLUMN IF EXISTS `action_type`,
            DROP COLUMN IF EXISTS `action_state`");

        // 5. Thêm foreign keys mới vào bảng topics
        $CI->db->query("ALTER TABLE `" . db_prefix() . "topics` 
            ADD CONSTRAINT `topics_action_type_fk` 
            FOREIGN KEY (`action_type_code`) 
            REFERENCES `" . db_prefix() . "topic_action_types`(`action_type_code`) 
            ON DELETE SET NULL,
            ADD CONSTRAINT `topics_action_state_fk` 
            FOREIGN KEY (`action_state_code`) 
            REFERENCES `" . db_prefix() . "topic_action_states`(`action_state_code`) 
            ON DELETE SET NULL");

        // 6. Cập nhật bảng action_states
        $CI->db->query("ALTER TABLE `" . db_prefix() . "topic_action_states` 
            ADD COLUMN IF NOT EXISTS `action_type_code` VARCHAR(50) NULL");
            
        $states = $CI->db->get(db_prefix() . 'topic_action_states')->result_array();
        foreach($states as $state) {
            if(!empty($state['action_type_id'])) {
                $type = $CI->db->get_where(db_prefix() . 'topic_action_types', 
                    ['id' => $state['action_type_id']])->row();
                if($type) {
                    $CI->db->where('id', $state['id']);
                    $CI->db->update(db_prefix() . 'topic_action_states', 
                        ['action_type_code' => $type->action_type_code]);
                }
            }
        }

        // 7. Drop old column và thêm foreign key mới cho action_states
        $CI->db->query("ALTER TABLE `" . db_prefix() . "topic_action_states` 
            DROP COLUMN IF EXISTS `action_type_id`,
            ADD CONSTRAINT `states_action_type_fk` 
            FOREIGN KEY (`action_type_code`) 
            REFERENCES `" . db_prefix() . "topic_action_types`(`action_type_code`) 
            ON DELETE CASCADE");
    }

    public function down()
    {
        $CI = &get_instance();

        // Rollback changes if needed
        $CI->db->query("ALTER TABLE `" . db_prefix() . "topics` 
            DROP FOREIGN KEY IF EXISTS `topics_action_type_fk`,
            DROP FOREIGN KEY IF EXISTS `topics_action_state_fk`,
            DROP COLUMN IF EXISTS `action_type_code`,
            DROP COLUMN IF EXISTS `action_state_code`");

        $CI->db->query("ALTER TABLE `" . db_prefix() . "topic_action_states` 
            DROP FOREIGN KEY IF EXISTS `states_action_type_fk`,
            DROP COLUMN IF EXISTS `action_type_code`");
    }
} 