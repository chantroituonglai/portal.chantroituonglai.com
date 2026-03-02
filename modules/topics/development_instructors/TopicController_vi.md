# Hướng Dẫn Nâng Cấp và Điều Chỉnh TopicController

## Giới Thiệu

Tài liệu này cung cấp hướng dẫn chi tiết về cách nâng cấp và điều chỉnh module TopicController trong hệ thống Perfex CRM. Module này quản lý kết nối giữa các chủ đề (topics) và các nền tảng bên ngoài như WordPress, Haravan, Shopify, và các nền tảng khác.

## Cấu Trúc Module

### Cơ Sở Dữ Liệu

Module sử dụng các bảng sau:

1. **tbltopic_controllers**: Lưu trữ thông tin về các controller, bao gồm:
   - Thông tin cơ bản (id, status, site, platform)
   - Cấu hình đăng nhập (login_config)
   - Phong cách viết (writing_style)
   - Trạng thái kết nối (login_status, last_login)

2. **tbltopic_controller**: Lưu trữ mối quan hệ giữa controller và topic:
   - controller_id: ID của controller
   - topic_id: ID của topic master
   - staff_id: ID của nhân viên tạo mối quan hệ
   - datecreated: Ngày tạo mối quan hệ

### Cấu Trúc Thư Mục

```
topics/
├── controllers/
│   └── Controllers.php       # Controller chính xử lý các request
├── models/
│   └── Topic_controller_model.php  # Model xử lý dữ liệu
├── views/
│   └── controllers/
│       ├── create.php        # Form tạo controller mới
│       ├── edit.php          # Form chỉnh sửa controller
│       ├── index.php         # Danh sách controller
│       └── detail.php        # Chi tiết controller
├── assets/
│   ├── js/
│   │   └── draft_writer/
│   │       └── controllers.js # JavaScript xử lý form và AJAX
│   │
│   └── css/
│       └── draft_writer.css   # CSS cho giao diện
└── language/
    ├── english/
    │   └── draft_writer_lang.php  # File ngôn ngữ tiếng Anh
    └── vietnamese/
        └── draft_writer_lang.php  # File ngôn ngữ tiếng Việt
```

## Quy Trình Nâng Cấp

Khi nâng cấp module TopicController, hãy tuân theo các bước sau để đảm bảo tính nhất quán và tránh lỗi:

### 1. Sao Lưu Dữ Liệu

Trước khi thực hiện bất kỳ thay đổi nào, hãy sao lưu các bảng sau:
- tbltopic_controllers
- tbltopic_controller

```sql
-- Lệnh sao lưu
CREATE TABLE tbltopic_controllers_backup AS SELECT * FROM tbltopic_controllers;
CREATE TABLE tbltopic_controller_backup AS SELECT * FROM tbltopic_controller;
```

### 2. Cập Nhật Cấu Trúc Cơ Sở Dữ Liệu

Nếu cần thay đổi cấu trúc cơ sở dữ liệu, hãy sử dụng các lệnh ALTER TABLE:

```sql
-- Ví dụ: Thêm cột mới
ALTER TABLE tbltopic_controllers ADD COLUMN new_column VARCHAR(255) DEFAULT NULL;
```

### 3. Cập Nhật Mã Nguồn

Khi cập nhật mã nguồn, hãy tuân theo các nguyên tắc sau:

#### a. Không Sửa Đổi Core

Không sửa đổi trực tiếp các file core của Perfex CRM. Thay vào đó, sử dụng hooks và filters để mở rộng chức năng.

#### b. Tuân Thủ Quy Ước Đặt Tên

- **Methods**: Sử dụng `camelCase`
- **Variables**: Sử dụng `snake_case`
- **Classes**: Sử dụng `PascalCase`

#### c. Xử Lý Lỗi Đúng Cách

Luôn kiểm tra và xử lý lỗi một cách rõ ràng, đặc biệt là khi làm việc với API bên ngoài.

```php
// Ví dụ: Xử lý lỗi khi gọi API
try {
    $result = $this->some_api_call();
    if (!$result['success']) {
        log_activity('API Error: ' . json_encode($result));
        return false;
    }
    return $result['data'];
} catch (Exception $e) {
    log_activity('Exception: ' . $e->getMessage());
    return false;
}
```

### 4. Cập Nhật JavaScript

Khi cập nhật file `controllers.js`, hãy chú ý các điểm sau:

#### a. Xử Lý Form

File `controllers.js` xử lý các tương tác form, bao gồm:
- Thay đổi platform
- Kiểm tra kết nối
- Lưu nhanh thông tin đăng nhập

```javascript
// Ví dụ: Cập nhật hàm testConnection
function testConnection(controllerId) {
    // Hiển thị trạng thái đang tải
    $('#connection_status_container').html('<div class="connection-status warning"><div class="loading-spinner"></div> ' + lang('testing_connection', 'Đang kiểm tra kết nối...') + '</div>');
    $('#connection_status_container').slideDown('fast');
    
    // Vô hiệu hóa nút và hiển thị trạng thái đang tải
    $('#test_connection_edit_btn').prop('disabled', true).html('<div class="loading-spinner"></div> ' + lang('testing', 'Đang kiểm tra') + '...');
    
    // Gọi AJAX để kiểm tra kết nối
    $.ajax({
        url: admin_url + 'topics/controllers/test_connection/' + controllerId,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            // Xử lý kết quả
            // ...
        },
        error: function() {
            // Xử lý lỗi
            // ...
        }
    });
}
```

#### b. Bảo Vệ Giá Trị Hiện Có

Khi chỉnh sửa controller, cần đảm bảo các giá trị hiện có không bị mất khi người dùng thay đổi platform:

```javascript
// Lưu trữ giá trị đăng nhập hiện có
var storedLoginValues = {};

// Hàm lưu trữ giá trị đăng nhập hiện có
function preserveExistingLoginValues() {
    // Chỉ chạy trên trang chỉnh sửa
    if ($('#controller-form input[name="id"]').length === 0) {
        return;
    }
    
    // Lưu trữ tất cả giá trị trường đăng nhập hiện có
    $('.login-field').each(function() {
        var name = $(this).attr('name');
        var matches = name.match(/login_config\[(.*?)\]/);
        if (matches && matches[1]) {
            storedLoginValues[matches[1]] = $(this).val();
        }
    });
}
```

### 5. Cập Nhật CSS

Khi cập nhật file `draft_writer.css`, hãy thêm các style mới vào cuối file để tránh ghi đè các style hiện có:

```css
/* Thêm style mới vào cuối file */
.login-fields-container {
    background-color: #f9f9f9;
    border: 1px solid #e0e0e0;
    border-radius: 5px;
    padding: 15px;
    margin-bottom: 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    transition: box-shadow 0.3s ease;
}

.login-fields-container:hover {
    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
}
```

### 6. Cập Nhật File Ngôn Ngữ

Khi thêm chuỗi mới vào file ngôn ngữ, hãy thêm vào cuối file để dễ theo dõi:

```php
// Thêm vào cuối file draft_writer_lang.php
$lang['edit_mode'] = 'Chế Độ Chỉnh Sửa';
$lang['configuration_loaded'] = 'Đã Tải Cấu Hình';
$lang['quick_save_login'] = 'Lưu Nhanh Đăng Nhập';
$lang['save_login_credentials_help'] = 'Lưu thông tin đăng nhập mà không cần gửi toàn bộ biểu mẫu';
```

## Nâng Cấp Giao Diện Người Dùng

### 1. Cải Thiện Form Đăng Nhập

Form đăng nhập đã được cải thiện với các tính năng sau:

- Hiển thị rõ ràng các trường đăng nhập
- Nút "Kiểm Tra Kết Nối" để xác minh thông tin đăng nhập
- Nút "Lưu Nhanh Đăng Nhập" xuất hiện sau khi kết nối thành công
- Hiển thị thông tin trang web sau khi kết nối thành công

### 2. Bảo Vệ Giá Trị Hiện Có

Khi chỉnh sửa controller, các giá trị đăng nhập hiện có được bảo vệ bằng cách:

- Lưu trữ giá trị khi trang được tải
- Sử dụng giá trị đã lưu trữ khi người dùng thay đổi platform
- Không tự động kích hoạt sự kiện thay đổi platform nếu đã có các trường đăng nhập

### 3. Cải Thiện Hiển Thị

CSS đã được cập nhật để cải thiện hiển thị:

- Container trường đăng nhập có nền và viền rõ ràng
- Hiệu ứng hover để cải thiện tương tác
- Chỉ báo chế độ chỉnh sửa để người dùng biết họ đang ở chế độ chỉnh sửa
- Hiển thị rõ ràng trạng thái kết nối

## Phòng Ngừa Lỗi

### 1. Xử Lý Trường Hợp Đặc Biệt

Khi làm việc với module TopicController, hãy chú ý các trường hợp đặc biệt sau:

#### a. Mất Giá Trị Đăng Nhập

Vấn đề: Khi chỉnh sửa controller, các giá trị đăng nhập có thể bị mất khi người dùng thay đổi platform.

Giải pháp: Sử dụng biến `storedLoginValues` để lưu trữ và khôi phục giá trị.

```javascript
// Trong hàm getPlatformFields
function getPlatformFields(platform, existingValues) {
    // Nếu không có giá trị hiện có, sử dụng giá trị đã lưu trữ
    if (!existingValues) {
        existingValues = storedLoginValues;
    }
    
    // Tiếp tục với giá trị hiện có
    // ...
}
```

#### b. Lỗi Kết Nối

Vấn đề: Kết nối có thể thất bại vì nhiều lý do khác nhau.

Giải pháp: Hiển thị thông báo lỗi rõ ràng và ghi log để debug.

```php
// Trong hàm test_connection
public function test_connection($id = null)
{
    // ...
    
    // Xử lý lỗi kết nối
    if (!$result['success']) {
        log_activity('Connection Test Failed [ID: ' . $id . '] - ' . $result['message']);
        echo json_encode([
            'success' => false,
            'message' => $result['message']
        ]);
        return;
    }
    
    // ...
}
```

#### c. Xử Lý JSON

Vấn đề: Dữ liệu JSON có thể không hợp lệ.

Giải pháp: Luôn kiểm tra và xử lý lỗi khi làm việc với JSON.

```php
// Trong hàm handle_empty_fields
private function handle_empty_fields($data)
{
    // ...
    
    // Xử lý login_config
    if (isset($data['login_config'])) {
        if (is_string($data['login_config'])) {
            // Nếu là chuỗi, thử phân tích JSON
            $login_config = json_decode($data['login_config'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                // Lỗi phân tích JSON
                log_activity('JSON Parse Error: ' . json_last_error_msg());
                $data['login_config'] = null;
            }
        } else if (!is_array($data['login_config'])) {
            // Nếu không phải chuỗi hoặc mảng, đặt thành null
            $data['login_config'] = null;
        }
    }
    
    // ...
}
```

### 2. Ghi Log

Luôn ghi log các hoạt động quan trọng để dễ dàng debug:

```php
// Ví dụ: Ghi log khi cập nhật controller
public function update($id, $data)
{
    // Ghi log trước khi xử lý
    log_activity('Controller Update Model Before Processing [ID: ' . $id . '] - ' . json_encode($data));
    
    $data = $this->handle_empty_fields($data);
    
    // Ghi log sau khi xử lý
    log_activity('Controller Update Model After Processing [ID: ' . $id . '] - ' . json_encode($data));
    
    // Tiếp tục cập nhật
    // ...
}
```

## Ví Dụ Thực Tế

### 1. Tạo Controller Mới

```php
// Trong Controllers.php
public function create()
{
    if (!has_permission('topics', '', 'create')) {
        access_denied('topics');
    }

    if ($this->input->post()) {
        $data = $this->input->post();
        
        // Xử lý login_config
        if (isset($data['login_config']) && is_array($data['login_config'])) {
            $login_config = [];
            foreach ($data['login_config'] as $key => $value) {
                $login_config[$key] = $this->input->post('login_config[' . $key . ']', false);
            }
            $data['login_config'] = json_encode($login_config);
        }
        
        // Xử lý writing_style
        if (isset($data['writing_style_options']) && is_array($data['writing_style_options'])) {
            $writing_style = [
                'style' => $data['writing_style_options']['style'] ?? '',
                'tone' => $data['writing_style_options']['tone'] ?? '',
                'language' => $data['writing_style_options']['language'] ?? 'vietnamese',
                'criteria' => $data['writing_style_options']['criteria'] ?? []
            ];
            $data['writing_style'] = json_encode($writing_style);
            unset($data['writing_style_options']);
        }
        
        $id = $this->Topic_controller_model->add($data);
        
        if ($id) {
            // Kiểm tra kết nối nếu có platform và login_config
            if (!empty($data['platform']) && !empty($data['login_config'])) {
                test_platform_connection($id);
            }
            
            set_alert('success', _l('added_successfully', _l('controller')));
            redirect(admin_url('topics/controllers'));
        }
    }

    $data['title'] = _l('new_controller');
    $data['platforms'] = $this->Topic_controller_model->get_platforms();
    $data['writing_styles'] = get_writing_styles();
    $data['writing_tones'] = get_writing_tones();
    $data['writing_criteria'] = get_writing_criteria();
    $this->load->view('controllers/create', $data);
}
```

### 2. Kiểm Tra Kết Nối

```php
// Trong Controllers.php
public function test_connection($id = null)
{
    if (!has_permission('topics', '', 'view')) {
        ajax_access_denied();
    }
    
    // Xử lý yêu cầu POST cho kiểm tra kết nối tạm thời (form tạo)
    if ($this->input->server('REQUEST_METHOD') === 'POST') {
        $platform = $this->input->post('platform');
        $login_fields = $this->input->post();
        
        // Xóa platform khỏi login_fields
        unset($login_fields['platform']);
        
        if (!$platform) {
            echo json_encode([
                'success' => false,
                'message' => 'Platform not specified'
            ]);
            return;
        }
        
        // Lấy connector platform
        $connector = get_platform_connector($platform);
        
        if (!$connector) {
            echo json_encode([
                'success' => false,
                'message' => 'Platform connector not found: ' . $platform
            ]);
            return;
        }
        
        // Kiểm tra kết nối
        $result = $connector->testConnection($login_fields);
        
        echo json_encode($result);
        return;
    }
    
    // Xử lý yêu cầu GET cho controller hiện có
    if (!$id) {
        echo json_encode([
            'success' => false,
            'message' => 'Controller ID not specified'
        ]);
        return;
    }
    
    $result = test_platform_connection($id);
    
    echo json_encode($result);
}
```

### 3. Lưu Nhanh Đăng Nhập

```php
// Trong Controllers.php
public function quick_save_login($id)
{
    if (!has_permission('topics', '', 'edit')) {
        ajax_access_denied();
    }
    
    // Xác thực controller ID
    $controller = $this->Topic_controller_model->get($id);
    if (!$controller) {
        echo json_encode([
            'success' => false,
            'message' => 'Controller not found'
        ]);
        return;
    }
    
    // Xử lý cấu hình đăng nhập
    $login_fields = $this->input->post('login_config');
    $platform = $this->input->post('platform');
    
    if (!is_array($login_fields) || empty($login_fields)) {
        echo json_encode([
            'success' => false,
            'message' => 'No login configuration provided'
        ]);
        return;
    }
    
    // Ghi log hành động để debug
    log_activity('Quick Save Login [ID: ' . $id . '] - Login fields: ' . json_encode($login_fields));
    
    // Cập nhật cấu hình đăng nhập
    $success = $this->Topic_controller_model->set_login_config($id, $login_fields);
    
    if ($success) {
        // Kiểm tra kết nối với thông tin đăng nhập mới
        $result = test_platform_connection($id);
        $connection_success = $result['success'] ?? false;
        
        echo json_encode([
            'success' => true,
            'message' => 'Login credentials saved successfully',
            'connection_success' => $connection_success,
            'connection_message' => $result['message'] ?? ''
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to save login credentials'
        ]);
    }
}
```

## Kết Luận

Module TopicController là một phần quan trọng của hệ thống, cho phép kết nối với các nền tảng bên ngoài. Khi nâng cấp và điều chỉnh module này, hãy tuân theo các hướng dẫn trong tài liệu này để đảm bảo tính nhất quán và tránh lỗi.

Luôn nhớ sao lưu dữ liệu trước khi thực hiện bất kỳ thay đổi nào, và ghi log đầy đủ để dễ dàng debug khi cần thiết.
