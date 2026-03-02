# Hướng Dẫn Sử Dụng Các Phương Thức Đồng Bộ Log

## Giới Thiệu

Tài liệu này mô tả cách sử dụng các phương thức đồng bộ log trong module Topics của Perfex CRM. Các phương thức này được thiết kế để theo dõi và ghi lại quá trình đồng bộ dữ liệu (như tags, categories) từ các nền tảng bên ngoài (như WordPress) vào hệ thống.

## Cấu Trúc Dữ Liệu

### Bảng `tbltopic_sync_logs`

Bảng này lưu trữ thông tin về các phiên đồng bộ:

- `id`: ID tự động tăng
- `controller_id`: ID của controller liên quan
- `session_id`: ID phiên đồng bộ
- `rel_type`: Loại quan hệ (ví dụ: 'tag_sync', 'category_sync')
- `status`: Trạng thái đồng bộ ('in_progress', 'completed', 'failed', 'cancelled')
- `summary_data`: Dữ liệu tóm tắt dạng JSON
- `log_data`: Dữ liệu log chi tiết dạng JSON
- `start_time`: Thời gian bắt đầu
- `end_time`: Thời gian kết thúc
- `datecreated`: Thời gian tạo bản ghi
- `dateupdated`: Thời gian cập nhật bản ghi

### Biến `tags_sync_session_id`

Biến `tags_sync_session_id` là một trường được lưu trữ trong bảng `topic_controllers` và có các mục đích sau:

1. **Lưu trữ session ID hiện tại**: Lưu trữ ID phiên đồng bộ tags đang hoạt động trong cơ sở dữ liệu để có thể truy cập giữa các request.
2. **Khôi phục phiên đồng bộ**: Cho phép hệ thống khôi phục hoặc tiếp tục phiên đồng bộ đã bị gián đoạn giữa chừng.
3. **Theo dõi trạng thái đồng bộ**: Giúp kiểm tra xem một controller có đang trong quá trình đồng bộ tags hay không.
4. **Liên kết giữa controller và session**: Tạo mối liên kết giữa một controller cụ thể và phiên đồng bộ tags tương ứng.

Khi bắt đầu một phiên đồng bộ mới, `tags_sync_session_id` sẽ được cập nhật với session ID mới được tạo. Khi phiên đồng bộ hoàn thành, giá trị này vẫn được giữ lại để tham chiếu đến phiên đồng bộ gần nhất.

**Ví dụ sử dụng**:
```php
// Kiểm tra xem controller có đang trong phiên đồng bộ không
if ($controller && !empty($controller->tags_sync_session_id)) {
    // Lấy thông tin phiên đồng bộ hiện tại
    $existing_session = $this->Topic_sync_log_model->get_session($controller->tags_sync_session_id);
    
    // Nếu phiên đang hoạt động, sử dụng lại
    if ($existing_session && $existing_session->status == 'in_progress') {
        log_message('debug', 'Using existing active session ID: ' . $controller->tags_sync_session_id);
        return $controller->tags_sync_session_id;
    }
}

// Tạo phiên đồng bộ mới và lưu vào controller
$session_id = $this->Topic_sync_log_model->create_session($controller_id, 'tag_sync', [...]);
$this->Topic_controller_model->update($controller_id, [
    'tags_sync_session_id' => $session_id
]);
```

## Các Phương Thức Chính

### 1. Tạo Phiên Đồng Bộ Mới

```php
$this->load->model('Topic_sync_log_model');
$session_id = $this->Topic_sync_log_model->create_session($controller_id, 'tag_sync', [
    'controller_id' => $controller_id,
    'status' => 'in_progress',
    'start_time' => date('Y-m-d H:i:s'),
    'total_pages' => 1,
    'current_page' => 1,
    'total_items' => 0,
    'items_processed' => 0,
    'success_count' => 0,
    'error_count' => 0,
    'last_update' => date('Y-m-d H:i:s')
]);
```

### 2. Cập Nhật Phiên Đồng Bộ

```php
$this->load->model('Topic_sync_log_model');

// Cập nhật thông tin tóm tắt
$summary_updates = [
    'current_page' => 2,
    'items_processed' => 50,
    'success_count' => 45,
    'error_count' => 5
];

// Thêm một bản ghi log
$log_entry = [
    'message' => 'Đã xử lý 50 items',
    'type' => 'info',
    'details' => [
        'page' => 2,
        'items' => 50
    ]
];

$this->Topic_sync_log_model->update_session($session_id, $summary_updates, $log_entry);
```

### 3. Hoàn Thành Phiên Đồng Bộ

```php
$this->load->model('Topic_sync_log_model');
$this->Topic_sync_log_model->complete_session($session_id, 'completed', [
    'total_items' => 100,
    'items_processed' => 100,
    'success_count' => 95,
    'error_count' => 5
]);
```

### 4. Lấy Danh Sách Phiên Đồng Bộ Gần Đây

```php
$this->load->model('Topic_sync_log_model');
$logs = $this->Topic_sync_log_model->get_recent_sessions($controller_id, 'tag_sync', 10);
```

### 5. Lấy Chi Tiết Phiên Đồng Bộ

```php
$this->load->model('Topic_sync_log_model');
$log = $this->Topic_sync_log_model->get_session($session_id);
$summary_data = json_decode($log->summary_data, true);
$log_data = json_decode($log->log_data, true);
```

### 6. Tiếp Tục Phiên Đồng Bộ Đã Bị Gián Đoạn

```php
// Cập nhật trạng thái phiên đồng bộ
$summary_data = json_decode($session->summary_data, true);
$summary_data['status'] = 'in_progress';
$summary_data['resume_time'] = date('Y-m-d H:i:s');
$summary_data['end_time'] = null;

// Thêm log về việc tiếp tục phiên
$log_entry = [
    'message' => 'Tiếp tục đồng bộ từ trang ' . $page,
    'type' => 'info',
    'details' => [
        'page' => $page,
        'resumed_at' => date('Y-m-d H:i:s')
    ]
];

// Cập nhật session
$this->Topic_sync_log_model->update_session($session_id, $summary_data, $log_entry);
```

## Quy Trình Đồng Bộ Hoàn Chỉnh

1. **Bắt đầu phiên đồng bộ**:
   - Tạo phiên đồng bộ mới với `create_session()`
   - Lưu session_id vào controller để tham chiếu sau này

2. **Trong quá trình đồng bộ**:
   - Cập nhật tiến trình với `update_session()`
   - Thêm các bản ghi log cho mỗi bước quan trọng

3. **Kết thúc phiên đồng bộ**:
   - Hoàn thành phiên với `complete_session()`
   - Cập nhật thời gian đồng bộ cuối cùng trong bảng controller

4. **Hiển thị thông tin đồng bộ**:
   - Lấy danh sách phiên đồng bộ gần đây với `get_recent_sessions()`
   - Hiển thị chi tiết phiên đồng bộ với `get_session()`

5. **Tiếp tục phiên đồng bộ gián đoạn**:
   - Kiểm tra trạng thái phiên đồng bộ
   - Cập nhật trạng thái thành 'in_progress' và xóa thời gian kết thúc
   - Tiếp tục từ trang tiếp theo

## Hướng Dẫn Phát Triển Chức Năng Sync

### 1. Chuẩn Hóa Kết Nối Với Nền Tảng

Khi phát triển chức năng đồng bộ với nền tảng mới, hãy tuân thủ cấu trúc interface của `PlatformConnectorInterface`:

```php
// Tạo connector mới cho nền tảng
class New_Platform_Connector implements PlatformConnectorInterface {
    
    // Triển khai phương thức get_tags với định dạng chuẩn
    public function get_tags($config, $blog_id, $options = []) {
        // ...
        return [
            'data' => $tags,
            'total_pages' => $total_pages,
            'total_items' => $total_items,
            'http_code' => $http_code
        ];
    }
    
    // Triển khai các phương thức khác...
}
```

### 2. Quản Lý Phiên Đồng Bộ

Luôn sử dụng các phương thức có sẵn trong `Topic_sync_log_model` để quản lý trạng thái đồng bộ:

```php
// Khởi tạo phiên đồng bộ
$sync_session_id = $this->_manage_tags_sync_session($controller_id, $page);

// Cập nhật trạng thái đồng bộ
$this->_update_tags_sync_log($sync_session_id, [
    'message' => 'Đang xử lý dữ liệu',
    'type' => 'info'
]);

// Hoàn thành phiên đồng bộ
$this->_complete_tags_sync_session($sync_session_id);
```

### 3. Xử Lý Đồng Bộ Từng Phần

Để tránh quá tải hệ thống, nên phân trang dữ liệu khi đồng bộ:

```php
// Thiết lập pagination
$per_page = 20;
$page = $this->input->get('page') ? (int)$this->input->get('page') : 1;

// Lấy dữ liệu từ API với pagination
$result = $connector->get_tags($config, $blog_id, [
    'per_page' => $per_page,
    'page' => $page
]);

// Xử lý từng trang dữ liệu
foreach ($result['data'] as $item) {
    // Xử lý từng item
}

// Kiểm tra xem đã hoàn thành chưa
$is_complete = ($page >= $result['total_pages']);
```

### 4. Xử Lý Lỗi Và Ghi Log

Luôn xử lý lỗi cẩn thận và ghi log chi tiết:

```php
try {
    // Thực hiện đồng bộ
} catch (Exception $e) {
    // Ghi log lỗi
    $this->_update_tags_sync_log($sync_session_id, [
        'error_count' => 1,
        'message' => 'Lỗi: ' . $e->getMessage(),
        'type' => 'error',
        'details' => [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]
    ]);
    
    // Trả về thông báo lỗi
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
```

### 5. Tính Năng Tiếp Tục Đồng Bộ (Resume)

Cài đặt tính năng tiếp tục đồng bộ để xử lý các phiên bị gián đoạn:

```php
// Định nghĩa phương thức resume_sync
public function resume_tags_sync($id) {
    // Lấy thông tin phiên đồng bộ
    $session = $this->Topic_sync_log_model->get_session($session_id);
    
    // Cập nhật trạng thái
    $summary_data = json_decode($session->summary_data, true);
    $summary_data['status'] = 'in_progress';
    $summary_data['current_page'] = $page;
    $summary_data['resume_time'] = date('Y-m-d H:i:s');
    
    // Xử lý việc tiếp tục đồng bộ
    // ...
}
```

## Các Loại Log

Sử dụng các loại log sau để phân loại thông báo:

- `info`: Thông tin chung
- `success`: Thành công
- `warning`: Cảnh báo
- `error`: Lỗi

## Lưu Ý

- Đảm bảo luôn load model `Topic_sync_log_model` trước khi sử dụng các phương thức.
- Cấu trúc dữ liệu JSON trong `summary_data` và `log_data` có thể tùy chỉnh theo nhu cầu.
- Xử lý lỗi cẩn thận để tránh phiên đồng bộ bị treo.
- Sử dụng các connector chuẩn để đảm bảo tính nhất quán trong dữ liệu.
- Xây dựng chức năng resume để xử lý các phiên đồng bộ bị gián đoạn.

## Ví Dụ Thực Tế

Xem các phương thức `sync_tags()`, `_manage_tags_sync_session()`, `_update_tags_sync_log()`, và `_complete_tags_sync_session()` trong file `controllers/Controllers.php` để thấy cách sử dụng các phương thức này trong thực tế.

## Mở Rộng Chức Năng

Khi cần mở rộng chức năng đồng bộ cho các loại dữ liệu mới (ngoài tags và categories), hãy làm theo các bước sau:

1. **Tạo rel_type mới** trong `Topic_sync_log_model` (ví dụ: 'post_sync', 'product_sync')
2. **Tạo phương thức quản lý phiên đồng bộ** tương tự như `_manage_tags_sync_session()`
3. **Triển khai phương thức đồng bộ** trong connector tương ứng
4. **Xây dựng UI hiển thị** tiến trình đồng bộ tương tự như đã làm với tags

Tất cả đều nên tuân theo quy trình và cấu trúc chuẩn để đảm bảo tính nhất quán và dễ bảo trì.
