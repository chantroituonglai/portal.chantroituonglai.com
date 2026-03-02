<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_121 extends App_module_migration
{
    public function up()
    {
        $CI = &get_instance();
        
        // 1. Cập nhật cột platform hiện tại để thêm enum constraints
        if ($CI->db->field_exists('platform', db_prefix() . 'topic_controllers')) {
            $CI->db->query("ALTER TABLE `" . db_prefix() . "topic_controllers` 
                MODIFY `platform` ENUM('wordpress', 'haravan', 'prestashop', 'shopify', 'other') 
                DEFAULT 'wordpress' COMMENT 'Nền tảng của website'");
        }
        
        // 2. Thêm cột login_config
        if (!$CI->db->field_exists('login_config', db_prefix() . 'topic_controllers')) {
            $CI->db->query("ALTER TABLE `" . db_prefix() . "topic_controllers` 
                ADD COLUMN `login_config` JSON NULL 
                COMMENT 'Cấu hình đăng nhập dạng JSON'");
        }
        
        // 3. Cập nhật cột writing_style
        if ($CI->db->field_exists('writing_style', db_prefix() . 'topic_controllers')) {
            // Lưu trữ dữ liệu hiện tại để chuyển đổi
            $controllers = $CI->db->get(db_prefix() . 'topic_controllers')->result_array();
            
            // Sửa kiểu dữ liệu
            $CI->db->query("ALTER TABLE `" . db_prefix() . "topic_controllers` 
                MODIFY `writing_style` JSON NULL 
                COMMENT 'Phong cách viết với nhiều tiêu chí'");
            
            // Chuyển đổi dữ liệu cũ sang định dạng JSON mới
            foreach ($controllers as $controller) {
                if (!empty($controller['writing_style'])) {
                    $writing_style_json = json_encode([
                        'style' => $controller['writing_style'],
                        'tone' => '',
                        'language' => 'vietnamese',
                        'criteria' => []
                    ]);
                    
                    $CI->db->where('id', $controller['id']);
                    $CI->db->update(db_prefix() . 'topic_controllers', [
                        'writing_style' => $writing_style_json
                    ]);
                }
            }
        }
        
        // 4. Thêm cột last_login và login_status
        if (!$CI->db->field_exists('last_login', db_prefix() . 'topic_controllers')) {
            $CI->db->query("ALTER TABLE `" . db_prefix() . "topic_controllers` 
                ADD COLUMN `last_login` DATETIME NULL 
                COMMENT 'Thời gian đăng nhập gần nhất'");
        }
        
        if (!$CI->db->field_exists('login_status', db_prefix() . 'topic_controllers')) {
            $CI->db->query("ALTER TABLE `" . db_prefix() . "topic_controllers` 
                ADD COLUMN `login_status` TINYINT(1) DEFAULT 0 
                COMMENT 'Trạng thái đăng nhập: 0=Chưa đăng nhập, 1=Đã đăng nhập, 2=Lỗi'");
        }
        
        // 5. Thêm indexes
        $CI->db->query("ALTER TABLE `" . db_prefix() . "topic_controllers` 
            ADD INDEX `idx_platform` (`platform`), 
            ADD INDEX `idx_login_status` (`login_status`)");
            
        // 6. Thêm module settings
        add_option('topic_controller_platforms', json_encode([
            'wordpress' => [
                'name' => 'WordPress',
                'icon' => 'fa-wordpress',
                'color' => '#21759b',
                'login_fields' => ['url', 'username', 'password', 'application_password']
            ],
            'haravan' => [
                'name' => 'Haravan',
                'icon' => 'fa-shopping-cart',
                'color' => '#7fba00',
                'login_fields' => ['shop_url', 'api_key', 'password']
            ],
            'prestashop' => [
                'name' => 'PrestaShop',
                'icon' => 'fa-shopping-bag',
                'color' => '#df0067',
                'login_fields' => ['shop_url', 'webservice_key']
            ],
            'shopify' => [
                'name' => 'Shopify',
                'icon' => 'fa-shopping-bag',
                'color' => '#96bf48',
                'login_fields' => ['shop_url', 'api_key', 'api_password']
            ],
            'other' => [
                'name' => 'Other',
                'icon' => 'fa-globe',
                'color' => '#333333',
                'login_fields' => ['url', 'username', 'password', 'api_key']
            ]
        ]));
        
        add_option('topic_controller_writing_styles', json_encode([
            'formal' => 'Trang trọng, học thuật',
            'casual' => 'Thân thiện, gần gũi',
            'creative' => 'Sáng tạo, độc đáo',
            'persuasive' => 'Thuyết phục, quảng cáo',
            'informative' => 'Thông tin, giáo dục',
            'narrative' => 'Kể chuyện, tường thuật',
            'technical' => 'Kỹ thuật, chuyên ngành',
            'conversational' => 'Trò chuyện, đối thoại'
        ]));
        
        add_option('topic_controller_writing_tones', json_encode([
            'professional' => 'Chuyên nghiệp',
            'friendly' => 'Thân thiện',
            'humorous' => 'Hài hước',
            'serious' => 'Nghiêm túc',
            'enthusiastic' => 'Nhiệt tình',
            'respectful' => 'Tôn trọng',
            'authoritative' => 'Có thẩm quyền',
            'empathetic' => 'Đồng cảm'
        ]));
    }

    public function down()
    {
        $CI = &get_instance();
        
        // Khôi phục cột platform về kiểu VARCHAR
        if ($CI->db->field_exists('platform', db_prefix() . 'topic_controllers')) {
            $CI->db->query("ALTER TABLE `" . db_prefix() . "topic_controllers` 
                MODIFY `platform` VARCHAR(255) NULL");
        }
        
        // Khôi phục cột writing_style về kiểu TEXT
        if ($CI->db->field_exists('writing_style', db_prefix() . 'topic_controllers')) {
            // Lưu trữ dữ liệu hiện tại để chuyển đổi
            $controllers = $CI->db->get(db_prefix() . 'topic_controllers')->result_array();
            
            $CI->db->query("ALTER TABLE `" . db_prefix() . "topic_controllers` 
                MODIFY `writing_style` TEXT NULL");
            
            // Chuyển đổi dữ liệu JSON về định dạng TEXT
            foreach ($controllers as $controller) {
                if (!empty($controller['writing_style'])) {
                    $writing_style_data = json_decode($controller['writing_style'], true);
                    $writing_style_text = isset($writing_style_data['style']) ? $writing_style_data['style'] : '';
                    
                    $CI->db->where('id', $controller['id']);
                    $CI->db->update(db_prefix() . 'topic_controllers', [
                        'writing_style' => $writing_style_text
                    ]);
                }
            }
        }
        
        // Xóa các cột đã thêm
        if ($CI->db->field_exists('login_config', db_prefix() . 'topic_controllers')) {
            $CI->db->query("ALTER TABLE `" . db_prefix() . "topic_controllers` 
                DROP COLUMN `login_config`");
        }
        
        if ($CI->db->field_exists('last_login', db_prefix() . 'topic_controllers')) {
            $CI->db->query("ALTER TABLE `" . db_prefix() . "topic_controllers` 
                DROP COLUMN `last_login`");
        }
        
        if ($CI->db->field_exists('login_status', db_prefix() . 'topic_controllers')) {
            $CI->db->query("ALTER TABLE `" . db_prefix() . "topic_controllers` 
                DROP COLUMN `login_status`");
        }
        
        // Xóa indexes
        $CI->db->query("ALTER TABLE `" . db_prefix() . "topic_controllers` 
            DROP INDEX `idx_platform`, 
            DROP INDEX `idx_login_status`");
            
        // Xóa module settings
        delete_option('topic_controller_platforms');
        delete_option('topic_controller_writing_styles');
        delete_option('topic_controller_writing_tones');
    }
} 