<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_110 extends App_module_migration
{
    public function up()
    {
        $CI = &get_instance();

        // 1. Kiểm tra và tạo bảng topic_target nếu chưa tồn tại
        if (!$CI->db->table_exists(db_prefix() . 'topic_target')) {
            $CI->db->query('CREATE TABLE `' . db_prefix() . 'topic_target` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `target_id` int(11) NOT NULL,
                `title` varchar(255) NOT NULL,
                `description` text DEFAULT NULL,
                `value` int(11) NOT NULL DEFAULT 0,
                `target_type` varchar(50) NOT NULL,
                `type` ENUM("daily", "weekly", "monthly") NOT NULL DEFAULT "daily",
                `status` tinyint(1) DEFAULT 1,
                `datecreated` datetime DEFAULT current_timestamp(),
                `dateupdated` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                PRIMARY KEY (`id`),
                KEY `target_type` (`target_type`),
                KEY `target_id` (`target_id`),
                KEY `idx_type` (`type`),
                KEY `idx_value` (`value`),
                KEY `idx_status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;');
        }

        // 2. Migrate data từ topics sang topic_target
        $topics = $CI->db->get(db_prefix() . 'topics')->result_array();
        foreach ($topics as $topic) {
            try {
                if (!empty($topic['target_id'])) {
                    $target_exists = $CI->db->where('target_id', $topic['target_id'])
                                          ->get(db_prefix() . 'topic_target')
                                          ->num_rows();
                    
                    if (!$target_exists) {
                        $CI->db->insert(db_prefix() . 'topic_target', [
                            'target_id' => $topic['target_id'],
                            'title' => 'Target ' . $topic['target_id'], // Default title
                            'target_type' => 'CONTENT',
                            'type' => 'daily', // Default type
                            'value' => 1, // Default value
                            'status' => 1,
                            'datecreated' => $topic['datecreated'],
                            'dateupdated' => $topic['dateupdated']
                        ]);
                    }
                }
            } catch (Exception $e) {
                log_activity('Migration error for topic ' . $topic['topicid'] . ': ' . $e->getMessage());
                continue;
            }
        }
    }

    public function down()
    {
        $CI = &get_instance();
        
        // Drop bảng nếu tồn tại
        if ($CI->db->table_exists(db_prefix() . 'topic_target')) {
            // $CI->db->query('DROP TABLE IF EXISTS `' . db_prefix() . 'topic_target`');
        }
    }
} 