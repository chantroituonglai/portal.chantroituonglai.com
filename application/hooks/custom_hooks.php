<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Custom Hooks for Perfex CRM
 */

 /**
  * Do Not Remove This Hook
     * TODO: Khi cập nhật core, hãy kiểm tra và thêm lại hook `filter_log_messages`
     * Mục đích: Sử dụng hook để lọc và xử lý các thông điệp log tùy chỉnh.
     * 
     * Hook đã thêm:
     * if (function_exists('hooks')) {
     *     hooks()->add_filter('filter_log_messages', 'filter_log_messages');
     * }
     *
     * Hàm filter_log_messages:
     * if (!function_exists('filter_log_messages')) {
     *     function filter_log_messages()
     *     {
     *         // Lấy đối tượng log
     *         $CI =& get_instance();
     *         $CI->load->library('Log');
     * 
     *         // Override lại phương thức write_log
     *         $CI->log->write_log = function ($level, $msg, $php_error = false) {
     *             // Chặn lỗi "Could not find the language line"
     *             if (strpos($msg, 'Could not find the language line') !== false) {
     *                 return true; // Không ghi log
     *             }
     * 
     *             // Gọi hàm ghi log gốc
     *             return log_message($level, $msg);
     *         };
     *     }
     * }
     */

// /**
//  * Hàm filter_log_messages
//  *
//  * Mục đích: Lọc và xử lý các thông điệp log trước khi chúng được ghi vào file log.
//  *
//  * @param string $msg 	Thông điệp log
//  * @param string $level 	Cấp độ log
//  * @return mixed 		Thông điệp log mới hoặc FALSE để bỏ qua
//  */
// function filter_log_messages($msg, $level)
// {
//     // Chặn log chứa chuỗi "Could not find the language line"
//     if (strpos($msg, 'Could not find the language line') !== false) {
//         return FALSE; // Bỏ qua việc ghi log
//     }

//     // Bạn có thể thêm các điều kiện lọc khác tại đây

//     return $msg; // Ghi log bình thường
// }

// /**
//  * Hàm initialize_custom_hooks
//  *
//  * Mục đích: Đăng ký các hook tùy chỉnh với hệ thống hooks của Perfex CRM.
//  */
// function initialize_custom_hooks()
// {
//     $CI =& get_instance();

//     if (method_exists($CI, 'hooks')) {
//         // Đăng ký filter 'filter_log_messages' với hàm 'filter_log_messages'
//         $CI->hooks->add_filter('filter_log_messages', 'filter_log_messages');
//     }
// }

