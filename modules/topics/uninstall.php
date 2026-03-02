<?php
defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();

// Xóa các bảng theo thứ tự để tránh lỗi khóa ngoại
// $CI->db->query("DROP TABLE IF EXISTS `" . db_prefix() . "topic_action_states`");
// $CI->db->query("DROP TABLE IF EXISTS `" . db_prefix() . "topic_action_types`");

// Xóa các options
delete_option('topics_is_installed');

// Remove routes
// topics_unrequire_in_file(APPPATH . 'config/my_routes.php', "FCPATH.'modules/topics/config/my_routes.php'");

// Drop topic_online_status table
if ($CI->db->table_exists(db_prefix() . 'topic_online_status')) {
    // $CI->db->query('DROP TABLE `' . db_prefix() . 'topic_online_status`');
}

// Remove module settings
delete_option('topics_online_tracking_enabled');
delete_option('topics_online_timeout');
delete_option('topics_debug_panel_enabled');
