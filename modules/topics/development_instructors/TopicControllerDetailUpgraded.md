# Kế Hoạch Nâng Cấp Chức Năng Topic Controller trong Perfex CRM

## 1. Tổng Quan
- Mục tiêu: Bổ sung các tính năng mới cho Topic Controller với platform predefined, login config, và writing style
- Scope: Cập nhật cơ sở dữ liệu, model, và giao diện người dùng

## 2. Cập Nhật Cơ Sở Dữ liệu
- Thiết lập nền tảng predefined cho platform (WordPress, Haravan)
- Thêm cột Login Config để lưu thông tin đăng nhập dạng JSON
- Cải tiến cột Writing Style để hỗ trợ nhiều tiêu chí

## 3. Cập Nhật Model
- Cập nhật Topic_controller_model.php để hỗ trợ các tính năng mới
- Thêm các phương thức xử lý login config và writing style
- Tạo helper functions cho platform

## 4. Giao Diện Người Dùng
- Trang controllers/create: Test login và quản lý thông tin đăng nhập
- Trang controllers/edit: Quản lý và kiểm tra thông tin controller
- Triển khai UI cho chọn writing style

## 5. Kế Hoạch Thực Hiện
- Giai đoạn 1: Cập nhật cơ sở dữ liệu
- Giai đoạn 2: Cập nhật model và controllers
- Giai đoạn 3: Triển khai giao diện người dùng
- Giai đoạn 4: Testing và tối ưu hóa

### 5.1 Giai Đoạn 1: Cập Nhật Cơ Sở Dữ liệu

#### 5.1.1 Tạo Migration File Mới

Tạo file migration mới để cập nhật cấu trúc bảng `tbltopic_controllers`:

```php
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
                MODIFY `platform` VARCHAR(50) NULL");
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
```

#### 5.1.2 Cấu Trúc JSON cho Login Config

Mỗi platform sẽ có cấu trúc JSON khác nhau tùy theo yêu cầu đăng nhập:

**WordPress:**
```json
{
  "url": "https://example.com/wp-json",
  "username": "admin",
  "password": "password123",
  "application_password": "abcd 1234 efgh 5678",
  "auth_method": "application_password" // hoặc "basic"
}
```

**Haravan:**
```json
{
  "shop_url": "https://mystore.haravan.com",
  "api_key": "key_12345",
  "password": "shppa_12345"
}
```

**Platform khác:**
```json
{
  "url": "https://example.com",
  "api_key": "key_12345",
  "username": "username",
  "password": "password",
  "custom_fields": {
    "field1": "value1",
    "field2": "value2"
  }
}
```

#### 5.1.3 Cấu Trúc JSON cho Writing Style

Writing Style mới sẽ được lưu dưới định dạng JSON với nhiều tiêu chí:

```json
{
  "style": "formal",
  "tone": "professional",
  "language": "vietnamese",
  "criteria": [
    "seo_friendly",
    "easy_to_understand",
    "include_examples"
  ],
  "custom_instructions": "Thêm nhiều ví dụ thực tế, tránh sử dụng thuật ngữ chuyên ngành phức tạp",
  "word_count": {
    "min": 500,
    "max": 1500
  },
  "target_audience": "beginners"
}
```

#### 5.1.4 Thiết Lập Enum cho Platform

Danh sách platform được hỗ trợ:

| Giá trị | Tên hiển thị | Mô tả |
|---------|--------------|-------|
| wordpress | WordPress | Nền tảng blog phổ biến |
| haravan | Haravan | Nền tảng thương mại điện tử Việt Nam |
| prestashop | PrestaShop | Nền tảng thương mại điện tử mã nguồn mở |
| shopify | Shopify | Nền tảng thương mại điện tử quốc tế |
| other | Other | Các nền tảng khác |

#### 5.1.5 Tạo Script Cập Nhật

Tạo file cập nhật tự động để thực thi migration:

```php
// File: update_script.php

<?php
defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();

// Chạy migration
$CI->load->library('migration');
$CI->migration->version(121);

// Kiểm tra kết quả
if ($CI->migration->error_string()) {
    echo 'Error running migration: ' . $CI->migration->error_string();
} else {
    echo 'Migration completed successfully!';
}
```

#### 5.1.6 Chuẩn Bị Dữ Liệu Mẫu

Chuẩn bị dữ liệu mẫu cho testing:

```php
// File: sample_data.php

<?php
defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();

// WordPress controller example
$wordpress_controller = [
    'site' => 'example-wordpress.com',
    'platform' => 'wordpress',
    'status' => 1,
    'slogan' => 'WordPress Example Site',
    'logo_url' => 'https://example.com/logo.png',
    'login_config' => json_encode([
        'url' => 'https://example-wordpress.com/wp-json',
        'username' => 'admin',
        'password' => 'password123',
        'application_password' => 'xxxx xxxx xxxx xxxx',
        'auth_method' => 'application_password'
    ]),
    'writing_style' => json_encode([
        'style' => 'conversational',
        'tone' => 'friendly',
        'language' => 'vietnamese',
        'criteria' => ['seo_friendly', 'include_examples'],
        'custom_instructions' => 'Viết với giọng thân thiện, hài hước'
    ])
];

// Haravan controller example
$haravan_controller = [
    'site' => 'example-haravan.com',
    'platform' => 'haravan',
    'status' => 1,
    'slogan' => 'Haravan Example Store',
    'logo_url' => 'https://example.com/haravan-logo.png',
    'login_config' => json_encode([
        'shop_url' => 'https://example-haravan.com',
        'api_key' => 'key_12345',
        'password' => 'shppa_12345'
    ]),
    'writing_style' => json_encode([
        'style' => 'persuasive',
        'tone' => 'enthusiastic',
        'language' => 'vietnamese',
        'criteria' => ['product_focused', 'call_to_action'],
        'custom_instructions' => 'Tập trung vào lợi ích sản phẩm, kết thúc với call-to-action mạnh'
    ])
];

// Insert sample data
$CI->db->insert(db_prefix() . 'topic_controllers', $wordpress_controller);
$CI->db->insert(db_prefix() . 'topic_controllers', $haravan_controller);

echo 'Sample data inserted successfully!';
```

#### 5.1.7 Kiểm Tra Tính Tương Thích

Trước khi triển khai rộng rãi, cần kiểm tra tính tương thích với các modules khác:

1. Xác định các modules khác có sử dụng dữ liệu từ bảng `tbltopic_controllers`
2. Kiểm tra xem các modules này có bị ảnh hưởng bởi thay đổi cấu trúc
3. Cập nhật modules liên quan nếu cần

#### 5.1.8 Backup Dữ Liệu

Trước khi triển khai migration, cần thực hiện các bước sau:

1. Backup toàn bộ database
2. Dump riêng bảng `tbltopic_controllers` để phục hồi nhanh nếu cần
3. Tạo bản sao của module để có thể rollback toàn bộ code

### 5.2 Giai Đoạn 2: Cập Nhật Model và Controllers

#### 5.2.1 Cập Nhật Topic_controller_model.php

Cập nhật model để hỗ trợ các tính năng mới:

##### A. Phương Thức Xử Lý Login Config

```php
class Topic_controller_model extends App_Model
{
    // Lấy thông tin login config
    public function get_login_config($controller_id)
    {
        $this->db->select('login_config');
        $this->db->where('id', $controller_id);
        $result = $this->db->get(db_prefix() . 'topic_controllers')->row();
        return $result ? json_decode($result->login_config, true) : null;
    }

    // Cập nhật login config
    public function update_login_config($controller_id, $config_data)
    {
        $this->db->where('id', $controller_id);
        return $this->db->update(db_prefix() . 'topic_controllers', [
            'login_config' => json_encode($config_data),
            'last_login' => null,
            'login_status' => 0
        ]);
    }

    // Kiểm tra kết nối
    public function test_connection($controller_id)
    {
        $controller = $this->get($controller_id);
        if (!$controller) return false;

        $platform_helper = new Topic_platform_helper();
        $result = $platform_helper->test_connection(
            $controller->platform,
            json_decode($controller->login_config, true)
        );

        // Cập nhật trạng thái đăng nhập
        $this->update_login_status($controller_id, $result['success']);

        return $result;
    }

    // Cập nhật trạng thái đăng nhập
    public function update_login_status($controller_id, $success)
    {
        $this->db->where('id', $controller_id);
        return $this->db->update(db_prefix() . 'topic_controllers', [
            'last_login' => date('Y-m-d H:i:s'),
            'login_status' => $success ? 1 : 2
        ]);
    }
}
```

##### B. Phương Thức Xử Lý Writing Style

```php
class Topic_controller_model extends App_Model
{
    // Lấy writing style
    public function get_writing_style($controller_id)
    {
        $this->db->select('writing_style');
        $this->db->where('id', $controller_id);
        $result = $this->db->get(db_prefix() . 'topic_controllers')->row();
        return $result ? json_decode($result->writing_style, true) : null;
    }

    // Cập nhật writing style
    public function update_writing_style($controller_id, $style_data)
    {
        $this->db->where('id', $controller_id);
        return $this->db->update(db_prefix() . 'topic_controllers', [
            'writing_style' => json_encode($style_data)
        ]);
    }

    // Lấy danh sách writing styles có sẵn
    public function get_available_writing_styles()
    {
        return json_decode(get_option('topic_controller_writing_styles'), true);
    }

    // Lấy danh sách tones có sẵn
    public function get_available_tones()
    {
        return json_decode(get_option('topic_controller_writing_tones'), true);
    }

    // Lấy danh sách tiêu chí writing
    public function get_writing_criteria()
    {
        return [
            'seo_friendly' => 'Tối ưu SEO',
            'easy_to_understand' => 'Dễ hiểu',
            'include_examples' => 'Có ví dụ minh họa',
            'product_focused' => 'Tập trung vào sản phẩm',
            'call_to_action' => 'Có call-to-action',
            'technical_details' => 'Chi tiết kỹ thuật',
            'storytelling' => 'Kể chuyện',
            'data_driven' => 'Dựa trên dữ liệu'
        ];
    }
}
```

##### C. Phương Thức Xử Lý Platform

```php
class Topic_controller_model extends App_Model
{
    // Lấy danh sách platforms
    public function get_platforms()
    {
        return json_decode(get_option('topic_controller_platforms'), true);
    }

    // Lấy thông tin chi tiết về platform
    public function get_platform_info($platform_key)
    {
        $platforms = $this->get_platforms();
        return isset($platforms[$platform_key]) ? $platforms[$platform_key] : null;
    }

    // Lấy danh sách trường login của platform
    public function get_platform_login_fields($platform_key)
    {
        $platform_info = $this->get_platform_info($platform_key);
        return $platform_info ? $platform_info['login_fields'] : [];
    }

    // Lấy danh sách controllers theo platform
    public function get_controllers_by_platform($platform)
    {
        $this->db->where('platform', $platform);
        return $this->db->get(db_prefix() . 'topic_controllers')->result_array();
    }

    // Validate login config theo platform
    public function validate_platform_login_config($platform, $config)
    {
        $required_fields = $this->get_platform_login_fields($platform);
        foreach ($required_fields as $field) {
            if (!isset($config[$field]) || empty($config[$field])) {
                return false;
            }
        }
        return true;
    }
}
```

##### D. Phương Thức Utility và Helpers

```php
class Topic_controller_model extends App_Model
{
    // Format writing style cho hiển thị
    public function format_writing_style_for_display($writing_style)
    {
        if (empty($writing_style)) return '';
        
        $style_data = is_string($writing_style) ? 
            json_decode($writing_style, true) : $writing_style;
            
        if (!$style_data) return '';

        $styles = $this->get_available_writing_styles();
        $tones = $this->get_available_tones();
        
        $display = [];
        if (isset($style_data['style']) && isset($styles[$style_data['style']])) {
            $display[] = $styles[$style_data['style']];
        }
        if (isset($style_data['tone']) && isset($tones[$style_data['tone']])) {
            $display[] = $tones[$style_data['tone']];
        }
        
        return implode(', ', $display);
    }

    // Format login status cho hiển thị
    public function format_login_status($status, $last_login)
    {
        $statuses = [
            0 => ['class' => 'warning', 'text' => 'Chưa đăng nhập'],
            1 => ['class' => 'success', 'text' => 'Đã đăng nhập'],
            2 => ['class' => 'danger', 'text' => 'Lỗi đăng nhập']
        ];
        
        $result = $statuses[$status] ?? $statuses[0];
        
        if ($status == 1 && $last_login) {
            $result['text'] .= ' (' . time_ago($last_login) . ')';
        }
        
        return $result;
    }

    // Kiểm tra và làm sạch dữ liệu trước khi lưu
    public function sanitize_controller_data($data)
    {
        // Làm sạch URL
        if (isset($data['site'])) {
            $data['site'] = prep_url($data['site']);
        }
        
        // Validate platform
        if (isset($data['platform'])) {
            $platforms = array_keys($this->get_platforms());
            if (!in_array($data['platform'], $platforms)) {
                $data['platform'] = 'other';
            }
        }
        
        // Encode JSON fields
        foreach (['login_config', 'writing_style'] as $field) {
            if (isset($data[$field]) && is_array($data[$field])) {
                $data[$field] = json_encode($data[$field]);
            }
        }
        
        return $data;
    }
}
```

Các phương thức trên được thiết kế để:

1. **Xử lý Login Config**
   - Quản lý thông tin đăng nhập cho từng platform
   - Kiểm tra và cập nhật trạng thái kết nối
   - Lưu trữ lịch sử đăng nhập

2. **Xử lý Writing Style**
   - Quản lý các style viết và tone giọng điệu
   - Cung cấp danh sách các tiêu chí viết
   - Format dữ liệu cho hiển thị

3. **Xử lý Platform**
   - Quản lý thông tin các platform được hỗ trợ
   - Validate dữ liệu theo yêu cầu của từng platform
   - Lọc và tìm kiếm controllers theo platform

4. **Utility và Helpers**
   - Format dữ liệu cho hiển thị
   - Sanitize dữ liệu trước khi lưu
   - Xử lý các tác vụ phụ trợ

#### 5.2.2 Tạo Platform Helper

File helper này sẽ cung cấp các phương thức để kết nối và tương tác với các nền tảng website khác nhau.

##### A. File helpers/topic_platform_helper.php

```php
<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Topic Platform Helper
 * 
 * Cung cấp các phương thức để kết nối và tương tác với các nền tảng website
 */
class Topic_platform_helper {
    /**
     * Các nền tảng được hỗ trợ
     * 
     * @var array
     */
    protected $supported_platforms = ['wordpress', 'haravan', 'prestashop', 'shopify', 'other'];
    
    /**
     * Các connector cho từng nền tảng
     * 
     * @var array
     */
    protected $connectors = [];
    
    /**
     * Khởi tạo helper
     */
    public function __construct() {
        // Đăng ký các connector
        $this->register_connectors();
    }
    
    /**
     * Đăng ký connector cho từng platform
     */
    protected function register_connectors() {
        // Load các class connector
        require_once(module_dir_path('draft_writer') . 'includes/platform_connectors/platform_connector_interface.php');
        require_once(module_dir_path('draft_writer') . 'includes/platform_connectors/wordpress_connector.php');
        require_once(module_dir_path('draft_writer') . 'includes/platform_connectors/haravan_connector.php');
        require_once(module_dir_path('draft_writer') . 'includes/platform_connectors/prestashop_connector.php');
        require_once(module_dir_path('draft_writer') . 'includes/platform_connectors/shopify_connector.php');
        require_once(module_dir_path('draft_writer') . 'includes/platform_connectors/other_connector.php');
        
        // Khởi tạo các connector
        $this->connectors = [
            'wordpress' => new WordPressConnector(),
            'haravan' => new HaravanConnector(),
            'prestashop' => new PrestashopConnector(),
            'shopify' => new ShopifyConnector(),
            'other' => new OtherConnector(),
        ];
    }
    
    /**
     * Kiểm tra kết nối đến platform
     * 
     * @param string $platform Platform cần kiểm tra
     * @param array $config Thông tin cấu hình đăng nhập
     * @return array Kết quả kiểm tra
     */
    public function test_connection($platform, $config) {
        if (!$this->is_platform_supported($platform)) {
            return [
                'success' => false,
                'message' => 'Platform không được hỗ trợ'
            ];
        }
        
        // Lấy connector tương ứng
        $connector = $this->get_connector($platform);
        
        // Kiểm tra kết nối
        return $connector->test_connection($config);
    }
    
    /**
     * Lấy danh sách categories từ platform
     * 
     * @param string $platform Platform cần lấy categories
     * @param array $config Thông tin cấu hình đăng nhập
     * @return array Danh sách categories
     */
    public function get_categories($platform, $config) {
        if (!$this->is_platform_supported($platform)) {
            return [
                'success' => false,
                'message' => 'Platform không được hỗ trợ',
                'categories' => []
            ];
        }
        
        // Lấy connector tương ứng
        $connector = $this->get_connector($platform);
        
        // Lấy danh sách categories
        return $connector->get_categories($config);
    }
    
    /**
     * Đăng bài lên platform
     * 
     * @param string $platform Platform cần đăng bài
     * @param array $config Thông tin cấu hình đăng nhập
     * @param array $post_data Dữ liệu bài viết
     * @return array Kết quả đăng bài
     */
    public function publish_post($platform, $config, $post_data) {
        if (!$this->is_platform_supported($platform)) {
            return [
                'success' => false,
                'message' => 'Platform không được hỗ trợ',
                'post_id' => null
            ];
        }
        
        // Lấy connector tương ứng
        $connector = $this->get_connector($platform);
        
        // Đăng bài
        return $connector->publish_post($config, $post_data);
    }
    
    /**
     * Kiểm tra platform có được hỗ trợ không
     * 
     * @param string $platform Platform cần kiểm tra
     * @return bool True nếu được hỗ trợ, false nếu không
     */
    protected function is_platform_supported($platform) {
        return in_array($platform, $this->supported_platforms);
    }
    
    /**
     * Lấy connector cho platform
     * 
     * @param string $platform Platform cần lấy connector
     * @return PlatformConnectorInterface Connector tương ứng
     */
    protected function get_connector($platform) {
        return isset($this->connectors[$platform]) ? 
            $this->connectors[$platform] : $this->connectors['other'];
    }
    
    /**
     * Lấy thông tin chi tiết về platform
     * 
     * @param string $platform Platform cần lấy thông tin
     * @return array Thông tin chi tiết về platform
     */
    public function get_platform_info($platform) {
        $CI = &get_instance();
        $CI->load->model('draft_writer/topic_controller_model');
        return $CI->topic_controller_model->get_platform_info($platform);
    }
    
    /**
     * Lấy danh sách trường login của platform
     * 
     * @param string $platform Platform cần lấy danh sách trường
     * @return array Danh sách trường cần thiết
     */
    public function get_login_fields($platform) {
        $CI = &get_instance();
        $CI->load->model('draft_writer/topic_controller_model');
        return $CI->topic_controller_model->get_platform_login_fields($platform);
    }
}
```

##### B. Interface PlatformConnectorInterface

```php
<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Interface cho các platform connector
 */
interface PlatformConnectorInterface {
    /**
     * Kiểm tra kết nối đến platform
     * 
     * @param array $config Thông tin cấu hình đăng nhập
     * @return array Kết quả kiểm tra
     */
    public function test_connection($config);
    
    /**
     * Lấy danh sách categories từ platform
     * 
     * @param array $config Thông tin cấu hình đăng nhập
     * @return array Danh sách categories
     */
    public function get_categories($config);
    
    /**
     * Đăng bài lên platform
     * 
     * @param array $config Thông tin cấu hình đăng nhập
     * @param array $post_data Dữ liệu bài viết
     * @return array Kết quả đăng bài
     */
    public function publish_post($config, $post_data);
    
    /**
     * Kiểm tra config có đầy đủ các trường cần thiết không
     * 
     * @param array $config Thông tin cấu hình đăng nhập
     * @return bool True nếu đầy đủ, false nếu không
     */
    public function validate_config($config);
}
```

##### C. Class WordPressConnector

```php
<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * WordPress Connector
 * 
 * Kết nối và tương tác với WordPress thông qua WP REST API
 */
class WordPressConnector implements PlatformConnectorInterface {
    /**
     * API endpoints
     */
    protected $api_endpoints = [
        'test' => '/wp/v2',
        'categories' => '/wp/v2/categories',
        'posts' => '/wp/v2/posts',
        'users' => '/wp/v2/users/me'
    ];
    
    /**
     * Kiểm tra kết nối đến WordPress site
     * 
     * @param array $config Thông tin cấu hình đăng nhập
     * @return array Kết quả kiểm tra
     */
    public function test_connection($config) {
        if (!$this->validate_config($config)) {
            return [
                'success' => false,
                'message' => 'Thông tin đăng nhập không đầy đủ'
            ];
        }
        
        $endpoint = $this->get_api_url($config, 'users');
        $auth_headers = $this->get_auth_headers($config);
        
        // Thực hiện request để kiểm tra kết nối
        $response = $this->make_request('GET', $endpoint, null, $auth_headers);
        
        if ($response['success']) {
            $user_data = json_decode($response['data'], true);
            return [
                'success' => true,
                'message' => 'Kết nối thành công với tài khoản: ' . $user_data['name'],
                'user_info' => $user_data
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Không thể kết nối: ' . $response['message'],
                'error_code' => $response['status_code']
            ];
        }
    }
    
    /**
     * Lấy danh sách categories từ WordPress
     * 
     * @param array $config Thông tin cấu hình đăng nhập
     * @return array Danh sách categories
     */
    public function get_categories($config) {
        if (!$this->validate_config($config)) {
            return [
                'success' => false,
                'message' => 'Thông tin đăng nhập không đầy đủ',
                'categories' => []
            ];
        }
        
        $endpoint = $this->get_api_url($config, 'categories') . '?per_page=100';
        $auth_headers = $this->get_auth_headers($config);
        
        // Thực hiện request để lấy danh sách categories
        $response = $this->make_request('GET', $endpoint, null, $auth_headers);
        
        if ($response['success']) {
            $categories_data = json_decode($response['data'], true);
            $categories = [];
            
            foreach ($categories_data as $category) {
                $categories[] = [
                    'id' => $category['id'],
                    'name' => $category['name'],
                    'slug' => $category['slug'],
                    'count' => $category['count'],
                    'parent' => $category['parent']
                ];
            }
            
            return [
                'success' => true,
                'message' => 'Lấy danh sách categories thành công',
                'categories' => $categories
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Không thể lấy danh sách categories: ' . $response['message'],
                'categories' => []
            ];
        }
    }
    
    /**
     * Đăng bài lên WordPress
     * 
     * @param array $config Thông tin cấu hình đăng nhập
     * @param array $post_data Dữ liệu bài viết
     * @return array Kết quả đăng bài
     */
    public function publish_post($config, $post_data) {
        if (!$this->validate_config($config)) {
            return [
                'success' => false,
                'message' => 'Thông tin đăng nhập không đầy đủ',
                'post_id' => null
            ];
        }
        
        // Chuẩn bị dữ liệu bài viết
        $wp_post_data = [
            'title' => $post_data['title'] ?? '',
            'content' => $post_data['content'] ?? '',
            'status' => $post_data['status'] ?? 'draft',
            'categories' => $post_data['categories'] ?? [],
            'tags' => $post_data['tags'] ?? [],
            'featured_media' => $post_data['featured_image'] ?? 0,
            'excerpt' => $post_data['excerpt'] ?? ''
        ];
        
        $endpoint = $this->get_api_url($config, 'posts');
        $auth_headers = $this->get_auth_headers($config);
        
        // Thực hiện request để đăng bài
        $response = $this->make_request('POST', $endpoint, $wp_post_data, $auth_headers);
        
        if ($response['success']) {
            $post_result = json_decode($response['data'], true);
            return [
                'success' => true,
                'message' => 'Đăng bài thành công',
                'post_id' => $post_result['id'],
                'post_url' => $post_result['link']
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Không thể đăng bài: ' . $response['message'],
                'post_id' => null
            ];
        }
    }
    
    /**
     * Kiểm tra config có đầy đủ các trường cần thiết không
     * 
     * @param array $config Thông tin cấu hình đăng nhập
     * @return bool True nếu đầy đủ, false nếu không
     */
    public function validate_config($config) {
        // Trường bắt buộc: url
        if (!isset($config['url']) || empty($config['url'])) {
            return false;
        }
        
        // Kiểm tra phương thức xác thực
        $auth_method = $config['auth_method'] ?? 'application_password';
        
        if ($auth_method == 'application_password') {
            // Cần username và application_password
            if (!isset($config['username']) || empty($config['username']) || 
                !isset($config['application_password']) || empty($config['application_password'])) {
                return false;
            }
        } else {
            // Cần username và password
            if (!isset($config['username']) || empty($config['username']) || 
                !isset($config['password']) || empty($config['password'])) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Lấy URL API dựa trên endpoint
     * 
     * @param array $config Thông tin cấu hình đăng nhập
     * @param string $endpoint_key Key của endpoint
     * @return string URL đầy đủ của API
     */
    protected function get_api_url($config, $endpoint_key) {
        $base_url = rtrim($config['url'], '/');
        
        // Kiểm tra xem URL đã có /wp-json chưa
        if (strpos($base_url, '/wp-json') === false) {
            $base_url .= '/wp-json';
        }
        
        return $base_url . $this->api_endpoints[$endpoint_key];
    }
    
    /**
     * Lấy headers xác thực
     * 
     * @param array $config Thông tin cấu hình đăng nhập
     * @return array Headers xác thực
     */
    protected function get_auth_headers($config) {
        $auth_method = $config['auth_method'] ?? 'application_password';
        $headers = [
            'Content-Type' => 'application/json'
        ];
        
        if ($auth_method == 'application_password') {
            // Sử dụng Application Password
            $auth_string = base64_encode($config['username'] . ':' . $config['application_password']);
            $headers['Authorization'] = 'Basic ' . $auth_string;
        } else {
            // Sử dụng Basic Auth
            $auth_string = base64_encode($config['username'] . ':' . $config['password']);
            $headers['Authorization'] = 'Basic ' . $auth_string;
        }
        
        return $headers;
    }
    
    /**
     * Thực hiện HTTP request
     * 
     * @param string $method Phương thức (GET, POST, PUT, DELETE)
     * @param string $url URL đích
     * @param array|null $data Dữ liệu gửi đi
     * @param array $headers Headers
     * @return array Kết quả request
     */
    protected function make_request($method, $url, $data = null, $headers = []) {
        $method = strtoupper($method);
        $curl_headers = [];
        
        // Chuyển đổi headers thành format cho cURL
        foreach ($headers as $key => $value) {
            $curl_headers[] = $key . ': ' . $value;
        }
        
        // Khởi tạo cURL
        $curl = curl_init();
        
        // Thiết lập các options
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $curl_headers);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        
        // Thiết lập method và data
        if ($method == 'POST') {
            curl_setopt($curl, CURLOPT_POST, true);
            if ($data) {
                curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
            }
        } elseif ($method == 'PUT') {
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
            if ($data) {
                curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
            }
        } elseif ($method == 'DELETE') {
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }
        
        // Thực hiện request
        $response = curl_exec($curl);
        $status_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        
        curl_close($curl);
        
        // Kiểm tra kết quả
        if ($error) {
            return [
                'success' => false,
                'message' => 'cURL Error: ' . $error,
                'status_code' => 0,
                'data' => null
            ];
        }
        
        // Kiểm tra status code
        if ($status_code >= 200 && $status_code < 300) {
            return [
                'success' => true,
                'message' => 'Request thành công',
                'status_code' => $status_code,
                'data' => $response
            ];
        } else {
            return [
                'success' => false,
                'message' => 'HTTP Error: ' . $status_code,
                'status_code' => $status_code,
                'data' => $response
            ];
        }
    }
}
```

##### D. Class HaravanConnector

```php
<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Haravan Connector
 * 
 * Kết nối và tương tác với Haravan thông qua API
 */
class HaravanConnector implements PlatformConnectorInterface {
    /**
     * API endpoints
     */
    protected $api_endpoints = [
        'test' => '/admin/shop.json',
        'categories' => '/admin/blogs/{blog_id}/articles/tags.json',
        'blogs' => '/admin/blogs.json',
        'articles' => '/admin/blogs/{blog_id}/articles.json'
    ];
    
    /**
     * Kiểm tra kết nối đến Haravan shop
     * 
     * @param array $config Thông tin cấu hình đăng nhập
     * @return array Kết quả kiểm tra
     */
    public function test_connection($config) {
        if (!$this->validate_config($config)) {
            return [
                'success' => false,
                'message' => 'Thông tin đăng nhập không đầy đủ'
            ];
        }
        
        $endpoint = $this->get_api_url($config, 'test');
        $headers = $this->get_auth_headers($config);
        
        // Thực hiện request để kiểm tra kết nối
        $response = $this->make_request('GET', $endpoint, null, $headers);
        
        if ($response['success']) {
            $shop_data = json_decode($response['data'], true);
            return [
                'success' => true,
                'message' => 'Kết nối thành công với shop: ' . $shop_data['shop']['name'],
                'shop_info' => $shop_data['shop']
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Không thể kết nối: ' . $response['message'],
                'error_code' => $response['status_code']
            ];
        }
    }
    
    /**
     * Lấy danh sách categories (tags) từ Haravan
     * 
     * @param array $config Thông tin cấu hình đăng nhập
     * @return array Danh sách categories
     */
    public function get_categories($config) {
        if (!$this->validate_config($config)) {
            return [
                'success' => false,
                'message' => 'Thông tin đăng nhập không đầy đủ',
                'categories' => []
            ];
        }
        
        // Trước tiên lấy danh sách blogs
        $blogs_endpoint = $this->get_api_url($config, 'blogs');
        $headers = $this->get_auth_headers($config);
        
        $blogs_response = $this->make_request('GET', $blogs_endpoint, null, $headers);
        
        if (!$blogs_response['success']) {
            return [
                'success' => false,
                'message' => 'Không thể lấy danh sách blogs: ' . $blogs_response['message'],
                'categories' => []
            ];
        }
        
        $blogs_data = json_decode($blogs_response['data'], true);
        $blogs = $blogs_data['blogs'] ?? [];
        
        if (empty($blogs)) {
            return [
                'success' => false,
                'message' => 'Không tìm thấy blog nào',
                'categories' => []
            ];
        }
        
        // Lấy blog đầu tiên để lấy tags
        $first_blog_id = $blogs[0]['id'];
        
        // Thay thế {blog_id} trong endpoint
        $tags_endpoint = str_replace('{blog_id}', $first_blog_id, $this->get_api_url($config, 'categories'));
        
        // Lấy danh sách tags
        $tags_response = $this->make_request('GET', $tags_endpoint, null, $headers);
        
        if (!$tags_response['success']) {
            return [
                'success' => false,
                'message' => 'Không thể lấy danh sách tags: ' . $tags_response['message'],
                'categories' => []
            ];
        }
        
        $tags_data = json_decode($tags_response['data'], true);
        $tags = $tags_data['tags'] ?? [];
        
        // Format lại thành categories
        $categories = [];
        foreach ($tags as $tag) {
            $categories[] = [
                'id' => md5($tag), // Tags không có ID nên tạo ID từ tên
                'name' => $tag,
                'slug' => $this->create_slug($tag),
                'count' => 0,
                'parent' => 0
            ];
        }
        
        // Thêm danh sách blogs như là categories cha
        foreach ($blogs as $blog) {
            $categories[] = [
                'id' => 'blog_' . $blog['id'],
                'name' => $blog['title'],
                'slug' => $blog['handle'],
                'count' => $blog['articles_count'],
                'parent' => 0,
                'is_blog' => true
            ];
        }
        
        return [
            'success' => true,
            'message' => 'Lấy danh sách categories thành công',
            'categories' => $categories,
            'blogs' => $blogs
        ];
    }
    
    /**
     * Đăng bài lên Haravan
     * 
     * @param array $config Thông tin cấu hình đăng nhập
     * @param array $post_data Dữ liệu bài viết
     * @return array Kết quả đăng bài
     */
    public function publish_post($config, $post_data) {
        if (!$this->validate_config($config)) {
            return [
                'success' => false,
                'message' => 'Thông tin đăng nhập không đầy đủ',
                'post_id' => null
            ];
        }
        
        // Cần blog_id để đăng bài
        $blog_id = $post_data['blog_id'] ?? null;
        
        if (!$blog_id) {
            // Tự động lấy blog đầu tiên nếu không có
            $blogs_endpoint = $this->get_api_url($config, 'blogs');
            $headers = $this->get_auth_headers($config);
            
            $blogs_response = $this->make_request('GET', $blogs_endpoint, null, $headers);
            
            if (!$blogs_response['success']) {
                return [
                    'success' => false,
                    'message' => 'Không thể lấy danh sách blogs: ' . $blogs_response['message'],
                    'post_id' => null
                ];
            }
            
            $blogs_data = json_decode($blogs_response['data'], true);
            $blogs = $blogs_data['blogs'] ?? [];
            
            if (empty($blogs)) {
                return [
                    'success' => false,
                    'message' => 'Không tìm thấy blog nào để đăng bài',
                    'post_id' => null
                ];
            }
            
            $blog_id = $blogs[0]['id'];
        }
        
        // Chuẩn bị dữ liệu bài viết
        $article_data = [
            'article' => [
                'title' => $post_data['title'] ?? '',
                'body_html' => $post_data['content'] ?? '',
                'published' => $post_data['status'] == 'publish',
                'tags' => implode(', ', $post_data['categories'] ?? []),
                'summary_html' => $post_data['excerpt'] ?? ''
            ]
        ];
        
        // Thay thế {blog_id} trong endpoint
        $articles_endpoint = str_replace('{blog_id}', $blog_id, $this->get_api_url($config, 'articles'));
        $headers = $this->get_auth_headers($config);
        
        // Thực hiện request để đăng bài
        $response = $this->make_request('POST', $articles_endpoint, $article_data, $headers);
        
        if ($response['success']) {
            $article_result = json_decode($response['data'], true);
            return [
                'success' => true,
                'message' => 'Đăng bài thành công',
                'post_id' => $article_result['article']['id'],
                'post_url' => $article_result['article']['url']
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Không thể đăng bài: ' . $response['message'],
                'post_id' => null
            ];
        }
    }
    
    /**
     * Kiểm tra config có đầy đủ các trường cần thiết không
     * 
     * @param array $config Thông tin cấu hình đăng nhập
     * @return bool True nếu đầy đủ, false nếu không
     */
    public function validate_config($config) {
        // Các trường bắt buộc
        $required_fields = ['shop_url', 'api_key', 'password'];
        
        foreach ($required_fields as $field) {
            if (!isset($config[$field]) || empty($config[$field])) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Lấy URL API đầy đủ
     * 
     * @param array $config Thông tin cấu hình đăng nhập
     * @param string $endpoint_key Key của endpoint
     * @return string URL đầy đủ của API
     */
    protected function get_api_url($config, $endpoint_key) {
        $shop_url = $config['shop_url'];
        
        // Đảm bảo URL hợp lệ
        if (!preg_match('/^https?:\/\//', $shop_url)) {
            $shop_url = 'https://' . $shop_url;
        }
        
        // Đảm bảo URL không có dấu / ở cuối
        $shop_url = rtrim($shop_url, '/');
        
        return $shop_url . $this->api_endpoints[$endpoint_key];
    }
    
    /**
     * Lấy headers xác thực
     * 
     * @param array $config Thông tin cấu hình đăng nhập
     * @return array Headers xác thực
     */
    protected function get_auth_headers($config) {
        return [
            'Content-Type' => 'application/json',
            'X-Haravan-Access-Token' => $config['password']
        ];
    }
    
    /**
     * Tạo slug từ chuỗi
     * 
     * @param string $string Chuỗi cần tạo slug
     * @return string Slug
     */
    protected function create_slug($string) {
        $string = preg_replace('/[^a-zA-Z0-9 -]/', '', $string);
        $string = strtolower($string);
        $string = str_replace(' ', '-', $string);
        return $string;
    }
    
    /**
     * Thực hiện HTTP request
     * 
     * @param string $method Phương thức (GET, POST, PUT, DELETE)
     * @param string $url URL đích
     * @param array|null $data Dữ liệu gửi đi
     * @param array $headers Headers
     * @return array Kết quả request
     */
    protected function make_request($method, $url, $data = null, $headers = []) {
        $method = strtoupper($method);
        $curl_headers = [];
        
        // Chuyển đổi headers thành format cho cURL
        foreach ($headers as $key => $value) {
            $curl_headers[] = $key . ': ' . $value;
        }
        
        // Khởi tạo cURL
        $curl = curl_init();
        
        // Thiết lập các options
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $curl_headers);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        
        // Thiết lập method và data
        if ($method == 'POST') {
            curl_setopt($curl, CURLOPT_POST, true);
            if ($data) {
                curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
            }
        } elseif ($method == 'PUT') {
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
            if ($data) {
                curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
            }
        } elseif ($method == 'DELETE') {
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }
        
        // Thực hiện request
        $response = curl_exec($curl);
        $status_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        
        curl_close($curl);
        
        // Kiểm tra kết quả
        if ($error) {
            return [
                'success' => false,
                'message' => 'cURL Error: ' . $error,
                'status_code' => 0,
                'data' => null
            ];
        }
        
        // Kiểm tra status code
        if ($status_code >= 200 && $status_code < 300) {
            return [
                'success' => true,
                'message' => 'Request thành công',
                'status_code' => $status_code,
                'data' => $response
            ];
        } else {
            return [
                'success' => false,
                'message' => 'HTTP Error: ' . $status_code,
                'status_code' => $status_code,
                'data' => $response
            ];
        }
    }
}
```

#### 5.2.3 Cập Nhật Controllers.php

Để hỗ trợ các tính năng mới, cần cập nhật file controllers/Controllers.php với các phương thức xử lý platform, login config và writing style:

```php
<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Controllers extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('draft_writer/topic_controller_model');
        
        // Load thêm helper mới
        $this->load->helper('draft_writer/topic_platform');
    }
    
    /* Phương thức chính hiện có */
    
    /**
     * Tạo controller mới
     */
    public function create()
    {
        // Kiểm tra quyền truy cập
        if (!has_permission('draft_writer', '', 'create')) {
            access_denied('draft_writer');
        }
        
        // Khởi tạo validation
        $this->load->library('form_validation');
        
        // Thiết lập các quy tắc
        $this->form_validation->set_rules('site', _l('controller_site'), 'required');
        $this->form_validation->set_rules('platform', _l('controller_platform'), 'required');
        
        if ($this->form_validation->run()) {
            $data = $this->input->post();
            
            // Xử lý login_config
            if (isset($data['login_fields'])) {
                $login_config = [];
                foreach ($data['login_fields'] as $field => $value) {
                    $login_config[$field] = $value;
                }
                $data['login_config'] = $login_config;
                unset($data['login_fields']);
            }
            
            // Xử lý writing_style
            if (isset($data['writing_style_options'])) {
                $writing_style = [
                    'style' => $data['writing_style_options']['style'] ?? '',
                    'tone' => $data['writing_style_options']['tone'] ?? '',
                    'language' => $data['writing_style_options']['language'] ?? 'vietnamese',
                    'criteria' => $data['writing_style_options']['criteria'] ?? [],
                    'custom_instructions' => $data['writing_style_options']['custom_instructions'] ?? ''
                ];
                $data['writing_style'] = $writing_style;
                unset($data['writing_style_options']);
            }
            
            // Làm sạch dữ liệu trước khi lưu
            $data = $this->topic_controller_model->sanitize_controller_data($data);
            
            // Thêm controller mới
            $id = $this->topic_controller_model->add($data);
            
            if ($id) {
                // Kiểm tra kết nối nếu có thông tin đăng nhập
                if (!empty($data['login_config'])) {
                    $this->topic_controller_model->test_connection($id);
                }
                
                set_alert('success', _l('added_successfully', _l('controller')));
                redirect(admin_url('draft_writer/controllers'));
            }
        }
        
        // Lấy danh sách platform
        $data['platforms'] = $this->topic_controller_model->get_platforms();
        $data['writing_styles'] = $this->topic_controller_model->get_available_writing_styles();
        $data['writing_tones'] = $this->topic_controller_model->get_available_tones();
        $data['writing_criteria'] = $this->topic_controller_model->get_writing_criteria();
        
        $data['title'] = _l('add_new', _l('controller'));
        $this->load->view('draft_writer/controllers/create', $data);
    }
    
    /**
     * Cập nhật controller
     * 
     * @param int $id ID của controller
     */
    public function edit($id)
    {
        // Kiểm tra quyền truy cập
        if (!has_permission('draft_writer', '', 'edit')) {
            access_denied('draft_writer');
        }
        
        if ($this->input->post()) {
            $data = $this->input->post();
            
            // Xử lý login_config
            if (isset($data['login_fields'])) {
                $login_config = [];
                foreach ($data['login_fields'] as $field => $value) {
                    $login_config[$field] = $value;
                }
                $data['login_config'] = $login_config;
                unset($data['login_fields']);
            }
            
            // Xử lý writing_style
            if (isset($data['writing_style_options'])) {
                $writing_style = [
                    'style' => $data['writing_style_options']['style'] ?? '',
                    'tone' => $data['writing_style_options']['tone'] ?? '',
                    'language' => $data['writing_style_options']['language'] ?? 'vietnamese',
                    'criteria' => $data['writing_style_options']['criteria'] ?? [],
                    'custom_instructions' => $data['writing_style_options']['custom_instructions'] ?? ''
                ];
                $data['writing_style'] = $writing_style;
                unset($data['writing_style_options']);
            }
            
            // Làm sạch dữ liệu trước khi lưu
            $data = $this->topic_controller_model->sanitize_controller_data($data);
            
            // Cập nhật controller
            $success = $this->topic_controller_model->update($id, $data);
            
            if ($success) {
                // Kiểm tra kết nối nếu login_config thay đổi
                if (isset($data['login_config'])) {
                    $this->topic_controller_model->test_connection($id);
                }
                
                set_alert('success', _l('updated_successfully', _l('controller')));
            }
            
            redirect(admin_url('draft_writer/controllers/edit/' . $id));
        }
        
        // Lấy thông tin controller
        $controller = $this->topic_controller_model->get($id);
        
        if (!$controller) {
            show_404();
        }
        
        // Chuẩn bị dữ liệu cho form
        $data['controller'] = $controller;
        $data['platforms'] = $this->topic_controller_model->get_platforms();
        $data['login_config'] = json_decode($controller->login_config, true);
        $data['writing_style'] = json_decode($controller->writing_style, true);
        $data['writing_styles'] = $this->topic_controller_model->get_available_writing_styles();
        $data['writing_tones'] = $this->topic_controller_model->get_available_tones();
        $data['writing_criteria'] = $this->topic_controller_model->get_writing_criteria();
        $data['login_status'] = $this->topic_controller_model->format_login_status(
            $controller->login_status, 
            $controller->last_login
        );
        
        $data['title'] = _l('edit', _l('controller'));
        $this->load->view('draft_writer/controllers/edit', $data);
    }
    
    /**
     * Kiểm tra kết nối đến platform
     * 
     * @param int $id ID của controller
     * @return Response JSON
     */
    public function test_connection($id)
    {
        // Kiểm tra quyền truy cập
        if (!has_permission('draft_writer', '', 'view')) {
            echo json_encode([
                'success' => false,
                'message' => _l('access_denied')
            ]);
            die();
        }
        
        $result = $this->topic_controller_model->test_connection($id);
        
        // Trả về kết quả dạng JSON
        echo json_encode($result);
    }
    
    /**
     * Lấy danh sách trường login cho platform
     * 
     * @return Response JSON
     */
    public function get_platform_fields()
    {
        // Kiểm tra quyền truy cập
        if (!has_permission('draft_writer', '', 'view')) {
            echo json_encode([
                'success' => false,
                'message' => _l('access_denied')
            ]);
            die();
        }
        
        $platform = $this->input->post('platform');
        
        if (!$platform) {
            echo json_encode([
                'success' => false,
                'message' => 'Missing platform parameter'
            ]);
            die();
        }
        
        $platform_info = $this->topic_controller_model->get_platform_info($platform);
        $login_fields = $this->topic_controller_model->get_platform_login_fields($platform);
        
        echo json_encode([
            'success' => true,
            'platform_info' => $platform_info,
            'login_fields' => $login_fields
        ]);
    }
    
    /**
     * Lấy danh sách categories từ platform
     * 
     * @param int $id ID của controller
     * @return Response JSON
     */
    public function get_platform_categories($id)
    {
        // Kiểm tra quyền truy cập
        if (!has_permission('draft_writer', '', 'view')) {
            echo json_encode([
                'success' => false,
                'message' => _l('access_denied')
            ]);
            die();
        }
        
        $controller = $this->topic_controller_model->get($id);
        
        if (!$controller) {
            echo json_encode([
                'success' => false,
                'message' => 'Controller not found'
            ]);
            die();
        }
        
        $platform = $controller->platform;
        $login_config = json_decode($controller->login_config, true);
        
        $platform_helper = new Topic_platform_helper();
        $result = $platform_helper->get_categories($platform, $login_config);
        
        echo json_encode($result);
    }
    
    /**
     * Lấy danh sách writing styles
     * 
     * @return Response JSON
     */
    public function get_writing_styles()
    {
        // Kiểm tra quyền truy cập
        if (!has_permission('draft_writer', '', 'view')) {
            echo json_encode([
                'success' => false,
                'message' => _l('access_denied')
            ]);
            die();
        }
        
        $styles = $this->topic_controller_model->get_available_writing_styles();
        
        echo json_encode([
            'success' => true,
            'styles' => $styles
        ]);
    }
    
    /**
     * Lấy danh sách writing tones
     * 
     * @return Response JSON
     */
    public function get_writing_tones()
    {
        // Kiểm tra quyền truy cập
        if (!has_permission('draft_writer', '', 'view')) {
            echo json_encode([
                'success' => false,
                'message' => _l('access_denied')
            ]);
            die();
        }
        
        $tones = $this->topic_controller_model->get_available_tones();
        
        echo json_encode([
            'success' => true,
            'tones' => $tones
        ]);
    }
    
    /**
     * Lấy danh sách writing criteria
     * 
     * @return Response JSON
     */
    public function get_writing_criteria()
    {
        // Kiểm tra quyền truy cập
        if (!has_permission('draft_writer', '', 'view')) {
            echo json_encode([
                'success' => false,
                'message' => _l('access_denied')
            ]);
            die();
        }
        
        $criteria = $this->topic_controller_model->get_writing_criteria();
        
        echo json_encode([
            'success' => true,
            'criteria' => $criteria
        ]);
    }
}
```

Các thay đổi chính trong Controllers.php bao gồm:

1. **Phương thức create() và edit()**:
   - Bổ sung xử lý cho `login_config` từ form input
   - Bổ sung xử lý cho `writing_style` với nhiều tiêu chí
   - Thêm sanitize data trước khi lưu
   - Thêm kiểm tra kết nối sau khi lưu

2. **Phương thức test_connection()**:
   - Gọi đến model để kiểm tra kết nối
   - Trả về kết quả dạng JSON

3. **Phương thức get_platform_fields()**:
   - Lấy danh sách trường cần thiết cho platform
   - Trả về thông tin chi tiết về platform

4. **Phương thức get_platform_categories()**:
   - Lấy danh sách categories từ platform
   - Sử dụng platform helper để tương tác với API

5. **Phương thức get_writing_styles(), get_writing_tones(), get_writing_criteria()**:
   - Lấy danh sách styles, tones và criteria cho writing style
   - Trả về dạng JSON cho AJAX requests

#### 5.2.4 Tạo AJAX Endpoints

Để hỗ trợ tương tác không đồng bộ cho các tính năng mới, cần tạo các AJAX endpoints trong JavaScript:

##### A. Endpoints cho Platform

```javascript
// File: assets/js/draft_writer/controllers.js

/**
 * Các hàm xử lý AJAX cho platform
 */

// Lấy danh sách trường login khi chọn platform
function getPlatformFields(platform) {
    var formData = new FormData();
    formData.append('platform', platform);
    
    $.ajax({
        url: admin_url + 'draft_writer/controllers/get_platform_fields',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            try {
                var data = JSON.parse(response);
                if (data.success) {
                    // Hiển thị các trường login
                    renderLoginFields(data.login_fields, data.platform_info);
                } else {
                    alert('Error: ' + data.message);
                }
            } catch (e) {
                console.error('Error parsing JSON response:', e);
            }
        },
        error: function(xhr, status, error) {
            alert('AJAX Error: ' + error);
        }
    });
}

// Tạo UI cho login fields dựa trên platform
function renderLoginFields(fields, platformInfo) {
    var container = $('#login_fields_container');
    container.empty();
    
    // Hiển thị thông tin platform
    var platformHeader = $('<div class="platform-info"></div>');
    platformHeader.append('<h4><i class="fa ' + platformInfo.icon + '" style="color: ' + platformInfo.color + '"></i> ' + platformInfo.name + ' Login</h4>');
    container.append(platformHeader);
    
    // Tạo form cho từng trường
    fields.forEach(function(field) {
        var fieldType = field.includes('password') ? 'password' : 'text';
        var fieldLabel = field.replace('_', ' ').replace(/\b\w/g, function(l) { return l.toUpperCase(); });
        
        var formGroup = $('<div class="form-group"></div>');
        formGroup.append('<label for="login_field_' + field + '">' + fieldLabel + '</label>');
        
        var inputGroup = $('<div class="input-group"></div>');
        inputGroup.append('<input type="' + fieldType + '" id="login_field_' + field + '" name="login_fields[' + field + ']" class="form-control" placeholder="' + fieldLabel + '">');
        
        if (fieldType === 'password') {
            var buttonSpan = $('<span class="input-group-btn"></span>');
            buttonSpan.append('<button class="btn btn-default toggle-password" type="button"><i class="fa fa-eye"></i></button>');
            inputGroup.append(buttonSpan);
        }
        
        formGroup.append(inputGroup);
        container.append(formGroup);
    });
    
    // Thêm button kiểm tra kết nối
    container.append('<button type="button" id="test_connection_btn" class="btn btn-info">Test Connection</button>');
    
    // Hiển thị container
    container.show();
    
    // Init toggle password buttons
    initTogglePassword();
}

// Kiểm tra kết nối
function testConnection(controllerId) {
    $.ajax({
        url: admin_url + 'draft_writer/controllers/test_connection/' + controllerId,
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                alert_float('success', response.message);
            } else {
                alert_float('danger', response.message);
            }
        },
        error: function(xhr, status, error) {
            alert_float('danger', 'AJAX Error: ' + error);
        }
    });
}

// Lấy danh sách categories từ platform
function getPlatformCategories(controllerId, callback) {
    $.ajax({
        url: admin_url + 'draft_writer/controllers/get_platform_categories/' + controllerId,
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                if (typeof callback === 'function') {
                    callback(response.categories);
                }
            } else {
                alert_float('danger', response.message);
            }
        },
        error: function(xhr, status, error) {
            alert_float('danger', 'AJAX Error: ' + error);
        }
    });
}
```

##### B. Endpoints cho Writing Style

```javascript
// File: assets/js/draft_writer/controllers.js

/**
 * Các hàm xử lý AJAX cho writing style
 */

// Lấy danh sách writing styles
function getWritingStyles(callback) {
    $.ajax({
        url: admin_url + 'draft_writer/controllers/get_writing_styles',
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                if (typeof callback === 'function') {
                    callback(response.styles);
                }
            } else {
                alert_float('danger', response.message);
            }
        },
        error: function(xhr, status, error) {
            alert_float('danger', 'AJAX Error: ' + error);
        }
    });
}

// Lấy danh sách writing tones
function getWritingTones(callback) {
    $.ajax({
        url: admin_url + 'draft_writer/controllers/get_writing_tones',
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                if (typeof callback === 'function') {
                    callback(response.tones);
                }
            } else {
                alert_float('danger', response.message);
            }
        },
        error: function(xhr, status, error) {
            alert_float('danger', 'AJAX Error: ' + error);
        }
    });
}

// Lấy danh sách writing criteria
function getWritingCriteria(callback) {
    $.ajax({
        url: admin_url + 'draft_writer/controllers/get_writing_criteria',
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                if (typeof callback === 'function') {
                    callback(response.criteria);
                }
            } else {
                alert_float('danger', response.message);
            }
        },
        error: function(xhr, status, error) {
            alert_float('danger', 'AJAX Error: ' + error);
        }
    });
}

// Tạo UI cho writing style
function initWritingStyleUI() {
    // Lấy dữ liệu
    getWritingStyles(function(styles) {
        renderWritingStyles(styles);
    });
    
    getWritingTones(function(tones) {
        renderWritingTones(tones);
    });
    
    getWritingCriteria(function(criteria) {
        renderWritingCriteria(criteria);
    });
}

// Render writing styles
function renderWritingStyles(styles) {
    var select = $('#writing_style_select');
    select.empty();
    
    select.append('<option value="">-- Select Style --</option>');
    
    Object.keys(styles).forEach(function(key) {
        select.append('<option value="' + key + '">' + styles[key] + '</option>');
    });
}

// Render writing tones
function renderWritingTones(tones) {
    var select = $('#writing_tone_select');
    select.empty();
    
    select.append('<option value="">-- Select Tone --</option>');
    
    Object.keys(tones).forEach(function(key) {
        select.append('<option value="' + key + '">' + tones[key] + '</option>');
    });
}

// Render writing criteria
function renderWritingCriteria(criteria) {
    var container = $('#writing_criteria_container');
    container.empty();
    
    Object.keys(criteria).forEach(function(key) {
        var checkbox = $('<div class="checkbox"></div>');
        checkbox.append('<label><input type="checkbox" name="writing_style_options[criteria][]" value="' + key + '"> ' + criteria[key] + '</label>');
        container.append(checkbox);
    });
}
```

##### C. Các Event Handlers

```javascript
// File: assets/js/draft_writer/controllers.js

/**
 * Init handlers khi document sẵn sàng
 */
$(document).ready(function() {
    // Handler cho thay đổi platform
    $('#platform_select').change(function() {
        var platform = $(this).val();
        if (platform) {
            getPlatformFields(platform);
        } else {
            $('#login_fields_container').empty().hide();
        }
    });
    
    // Handler cho nút test connection
    $(document).on('click', '#test_connection_btn', function() {
        // Trong trang create, chỉ có thể test sau khi submit form
        alert('Kết nối sẽ được kiểm tra sau khi lưu.');
    });
    
    // Handler cho nút test connection trong trang edit
    $('#test_connection_edit_btn').click(function() {
        var controllerId = $(this).data('controller-id');
        testConnection(controllerId);
    });
    
    // Init writing style UI
    initWritingStyleUI();
    
    // Nếu đang ở trang edit và đã có platform, hiển thị form login
    var existingPlatform = $('#platform_select').val();
    if (existingPlatform && $('#login_fields_container').length > 0) {
        getPlatformFields(existingPlatform);
    }
});

// Toggle password visibility
function initTogglePassword() {
    $('.toggle-password').click(function() {
        var input = $(this).closest('.input-group').find('input');
        var type = input.attr('type');
        
        if (type === 'password') {
            input.attr('type', 'text');
            $(this).find('i').removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            input.attr('type', 'password');
            $(this).find('i').removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });
}
```

## 6. Phụ Lục
- Cấu trúc JSON cho login config
- Danh sách các tiêu chí writing style
- Danh sách các platform được hỗ trợ

## 7. Danh Sách File Cần Chỉnh Sửa và Tạo Mới

### 7.1 File Cần Tạo Mới

1. **Migration File**
   - `modules/draft_writer/migrations/121_version_121.php`
   - Mục đích: Cập nhật cấu trúc bảng `tbltopic_controllers`

2. **Platform Helper**
   - `modules/draft_writer/helpers/topic_platform_helper.php`
   - Mục đích: Cung cấp các hàm hỗ trợ kết nối đến các nền tảng khác nhau

3. **Platform Connectors**
   - `modules/draft_writer/includes/platform_connectors/platform_connector_interface.php`
   - `modules/draft_writer/includes/platform_connectors/wordpress_connector.php`
   - `modules/draft_writer/includes/platform_connectors/haravan_connector.php`
   - `modules/draft_writer/includes/platform_connectors/prestashop_connector.php`
   - `modules/draft_writer/includes/platform_connectors/shopify_connector.php`
   - `modules/draft_writer/includes/platform_connectors/other_connector.php`
   - Mục đích: Xử lý kết nối đến từng loại nền tảng cụ thể

4. **JavaScript File**
   - `modules/draft_writer/assets/js/controllers.js`
   - Mục đích: Xử lý AJAX và tương tác người dùng cho các tính năng mới

### 7.2 File Cần Chỉnh Sửa

1. **Model File**
   - `modules/draft_writer/models/Topic_controller_model.php`
   - Các thay đổi:
     - Bổ sung phương thức xử lý login_config
     - Bổ sung phương thức xử lý writing_style
     - Bổ sung phương thức xử lý platform
     - Bổ sung các utility và helper function

2. **Controller File**
   - `modules/draft_writer/controllers/Controllers.php`
   - Các thay đổi:
     - Cập nhật phương thức create() và edit() để xử lý thông tin mới
     - Thêm phương thức test_connection()
     - Thêm các AJAX endpoints cho platform và writing style

3. **View Files**
   - `modules/draft_writer/views/controllers/create.php`
   - `modules/draft_writer/views/controllers/edit.php`
   - Các thay đổi:
     - Thêm UI cho chọn platform
     - Thêm UI cho nhập thông tin đăng nhập
     - Thêm UI cho writing style với nhiều tiêu chí
     - Thêm UI hiển thị trạng thái kết nối

4. **Module Init File**
   - `modules/draft_writer/draft_writer.php`
   - Các thay đổi:
     - Đăng ký helper mới
     - Cập nhật version module

### 7.3 Thứ Tự Triển Khai

1. Tạo migration file và chạy migration để cập nhật cơ sở dữ liệu
2. Tạo interface và các connector classes
3. Tạo platform helper
4. Cập nhật model
5. Cập nhật controller và thêm AJAX endpoints
6. Cập nhật view files
7. Tạo file JavaScript
8. Cập nhật module init file