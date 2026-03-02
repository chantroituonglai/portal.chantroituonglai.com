#Cách sử dụng Bảng External Data để ghi nhận lịch sử đồng bộ# Phương án ghi nhật ký hành trình đồng bộ tags và hiển thị trên giao diện

Dựa trên phân tích mã nguồn, tôi đề xuất phương án sau để ghi nhật ký hành trình đồng bộ tags và hiển thị chúng trên giao diện người dùng:

## 1. Cấu trúc dữ liệu trong bảng `tbltopic_external_data`

Sẽ sử dụng bảng `tbltopic_external_data` với cấu trúc như sau để lưu trữ nhật ký đồng bộ:

- `topic_master_id`: ID của controller
- `rel_type`: Loại quan hệ, sẽ sử dụng "tag_sync_log"
- `rel_id`: ID của phiên đồng bộ (timestamp + random string)
- `rel_data`: Thông tin tóm tắt về phiên đồng bộ (JSON)
- `rel_data_raw`: Nhật ký chi tiết của quá trình đồng bộ (JSON)

## 2. Cấu trúc JSON trong `rel_data`

```json
{
  "status": "in_progress|completed|failed",
  "start_time": "2025-03-16 10:00:00",
  "end_time": "2025-03-16 10:05:00",
  "total_pages": 5,
  "current_page": 3,
  "total_tags": 150,
  "tags_processed": 90,
  "success_count": 85,
  "error_count": 5,
  "last_update": "2025-03-16 10:03:30"
}
```

## 3. Cấu trúc JSON trong `rel_data_raw`

```json
{
  "logs": [
    {
      "timestamp": "2025-03-16 10:00:00",
      "message": "Bắt đầu đồng bộ tags",
      "type": "info"
    },
    {
      "timestamp": "2025-03-16 10:01:00",
      "message": "Đang xử lý trang 1/5",
      "type": "info",
      "details": {
        "page": 1,
        "total_pages": 5,
        "api_url": "https://example.com/wp-json/wp/v2/tags?page=1",
        "http_code": 200,
        "tags_count": 30
      }
    },
    {
      "timestamp": "2025-03-16 10:02:00",
      "message": "Đã lưu 30 tags từ trang 1",
      "type": "success"
    },
    {
      "timestamp": "2025-03-16 10:03:00",
      "message": "Lỗi khi xử lý tag ID 123",
      "type": "error",
      "details": {
        "tag_id": 123,
        "error": "Lỗi kết nối database"
      }
    }
  ]
}
```

## 4. Các bước thực hiện

### 4.1. Backend - Cập nhật phương thức `sync_tags` trong `Controllers.php`

1. Tạo phiên đồng bộ mới khi bắt đầu đồng bộ (trang đầu tiên)
2. Cập nhật trạng thái đồng bộ sau mỗi trang
3. Hoàn thành phiên đồng bộ khi đã xử lý tất cả các trang

### 4.2. Backend - Thêm phương thức mới để lấy nhật ký đồng bộ

1. Thêm phương thức `get_sync_logs` để lấy nhật ký đồng bộ gần nhất
2. Thêm phương thức `get_sync_log_details` để lấy chi tiết của một phiên đồng bộ cụ thể

### 4.3. Frontend - Cập nhật giao diện hiển thị nhật ký

1. Thêm tab hoặc phần hiển thị nhật ký đồng bộ trong giao diện tags
2. Hiển thị tiến trình đồng bộ hiện tại (nếu đang diễn ra)
3. Hiển thị lịch sử đồng bộ gần đây
4. Cho phép xem chi tiết của từng phiên đồng bộ

### 4.4. Frontend - Cập nhật JavaScript để polling nhật ký

1. Thêm hàm JavaScript để polling nhật ký đồng bộ hiện tại
2. Cập nhật giao diện theo thời gian thực khi có dữ liệu mới
3. Dừng polling khi đồng bộ hoàn tất

## 5. Chi tiết triển khai

### 5.1. Cập nhật phương thức `sync_tags` trong `Controllers.php`

```php
public function sync_tags($id)
{
    // Tạo hoặc cập nhật phiên đồng bộ
    $sync_session_id = $this->_manage_tags_sync_session($id);
    
    // Xử lý đồng bộ tags
    // ...
    
    // Cập nhật nhật ký đồng bộ
    $this->_update_tags_sync_log($sync_session_id, [
        'current_page' => $page,
        'tags_processed' => count($tags),
        'message' => sprintf('Đã xử lý %d tags từ trang %d', count($tags), $page)
    ]);
    
    // Khi hoàn thành tất cả các trang
    if ($is_last_page) {
        $this->_complete_tags_sync_session($sync_session_id);
    }
}
```

### 5.2. Thêm phương thức để quản lý phiên đồng bộ

```php
private function _manage_tags_sync_session($controller_id, $page = 1)
{
    $this->load->model('Topic_external_data_model');
    
    // Nếu là trang đầu tiên, tạo phiên mới
    if ($page == 1) {
        $session_id = 'tag_sync_' . time() . '_' . substr(md5(rand()), 0, 6);
        
        $data = [
            'topic_master_id' => $controller_id,
            'rel_type' => 'tag_sync_log',
            'rel_id' => $session_id,
            'rel_data' => json_encode([
                'status' => 'in_progress',
                'start_time' => date('Y-m-d H:i:s'),
                'total_pages' => 1, // Sẽ cập nhật sau
                'current_page' => 1,
                'total_tags' => 0,
                'tags_processed' => 0,
                'success_count' => 0,
                'error_count' => 0,
                'last_update' => date('Y-m-d H:i:s')
            ]),
            'rel_data_raw' => json_encode([
                'logs' => [
                    [
                        'timestamp' => date('Y-m-d H:i:s'),
                        'message' => 'Bắt đầu đồng bộ tags',
                        'type' => 'info'
                    ]
                ]
            ])
        ];
        
        $this->Topic_external_data_model->save($data);
        return $session_id;
    } else {
        // Tìm phiên đồng bộ hiện tại
        $current_sessions = $this->db->where('topic_master_id', $controller_id)
            ->where('rel_type', 'tag_sync_log')
            ->where("JSON_EXTRACT(rel_data, '$.status') = 'in_progress'")
            ->order_by('datecreated', 'DESC')
            ->limit(1)
            ->get(db_prefix() . 'topic_external_data')
            ->row();
            
        if ($current_sessions) {
            return $current_sessions->rel_id;
        } else {
            // Nếu không tìm thấy, tạo phiên mới
            return $this->_manage_tags_sync_session($controller_id, 1);
        }
    }
}
```

### 5.3. Thêm phương thức để cập nhật nhật ký đồng bộ

```php
private function _update_tags_sync_log($session_id, $data)
{
    $this->load->model('Topic_external_data_model');
    
    // Lấy dữ liệu hiện tại
    $current_data = $this->db->where('rel_id', $session_id)
        ->where('rel_type', 'tag_sync_log')
        ->get(db_prefix() . 'topic_external_data')
        ->row();
    
    if (!$current_data) {
        return false;
    }
    
    // Cập nhật rel_data
    $rel_data = json_decode($current_data->rel_data, true);
    foreach ($data as $key => $value) {
        if ($key == 'message') continue;
        $rel_data[$key] = $value;
    }
    $rel_data['last_update'] = date('Y-m-d H:i:s');
    
    // Cập nhật rel_data_raw
    $rel_data_raw = json_decode($current_data->rel_data_raw, true);
    $rel_data_raw['logs'][] = [
        'timestamp' => date('Y-m-d H:i:s'),
        'message' => $data['message'] ?? 'Cập nhật trạng thái',
        'type' => $data['type'] ?? 'info',
        'details' => $data['details'] ?? null
    ];
    
    // Lưu dữ liệu
    $update_data = [
        'topic_master_id' => $current_data->topic_master_id,
        'rel_type' => 'tag_sync_log',
        'rel_id' => $session_id,
        'rel_data' => json_encode($rel_data),
        'rel_data_raw' => json_encode($rel_data_raw)
    ];
    
    return $this->Topic_external_data_model->save($update_data);
}
```

### 5.4. Thêm phương thức để hoàn thành phiên đồng bộ

```php
private function _complete_tags_sync_session($session_id)
{
    $this->load->model('Topic_external_data_model');
    
    // Lấy dữ liệu hiện tại
    $current_data = $this->db->where('rel_id', $session_id)
        ->where('rel_type', 'tag_sync_log')
        ->get(db_prefix() . 'topic_external_data')
        ->row();
    
    if (!$current_data) {
        return false;
    }
    
    // Cập nhật rel_data
    $rel_data = json_decode($current_data->rel_data, true);
    $rel_data['status'] = 'completed';
    $rel_data['end_time'] = date('Y-m-d H:i:s');
    $rel_data['last_update'] = date('Y-m-d H:i:s');
    
    // Cập nhật rel_data_raw
    $rel_data_raw = json_decode($current_data->rel_data_raw, true);
    $rel_data_raw['logs'][] = [
        'timestamp' => date('Y-m-d H:i:s'),
        'message' => 'Hoàn thành đồng bộ tags',
        'type' => 'success',
        'details' => [
            'total_tags' => $rel_data['total_tags'],
            'success_count' => $rel_data['success_count'],
            'error_count' => $rel_data['error_count']
        ]
    ];
    
    // Lưu dữ liệu
    $update_data = [
        'topic_master_id' => $current_data->topic_master_id,
        'rel_type' => 'tag_sync_log',
        'rel_id' => $session_id,
        'rel_data' => json_encode($rel_data),
        'rel_data_raw' => json_encode($rel_data_raw)
    ];
    
    return $this->Topic_external_data_model->save($update_data);
}
```

### 5.5. Thêm phương thức để lấy nhật ký đồng bộ

```php
public function get_tags_sync_logs($controller_id)
{
    $this->load->model('Topic_external_data_model');
    
    // Lấy 10 phiên đồng bộ gần nhất
    $logs = $this->db->where('topic_master_id', $controller_id)
        ->where('rel_type', 'tag_sync_log')
        ->order_by('datecreated', 'DESC')
        ->limit(10)
        ->get(db_prefix() . 'topic_external_data')
        ->result_array();
    
    $formatted_logs = [];
    foreach ($logs as $log) {
        $rel_data = json_decode($log['rel_data'], true);
        $formatted_logs[] = [
            'session_id' => $log['rel_id'],
            'status' => $rel_data['status'],
            'start_time' => $rel_data['start_time'],
            'end_time' => $rel_data['end_time'] ?? null,
            'total_tags' => $rel_data['total_tags'],
            'success_count' => $rel_data['success_count'],
            'error_count' => $rel_data['error_count'],
            'last_update' => $rel_data['last_update']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $formatted_logs
    ]);
}
```

### 5.6. Cập nhật JavaScript trong `views/controllers/tabs/tags.php`

```javascript
// Biến toàn cục để lưu trữ ID phiên đồng bộ hiện tại
var currentSyncSessionId = null;
var syncPollingInterval = null;

// Hàm để bắt đầu polling nhật ký đồng bộ
function startSyncLogPolling(controllerId) {
    // Dừng polling hiện tại nếu có
    stopSyncLogPolling();
    
    // Bắt đầu polling mới
    syncPollingInterval = setInterval(function() {
        $.ajax({
            url: admin_url + 'topics/controllers/get_tags_sync_logs/' + controllerId,
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success && response.data.length > 0) {
                    // Lấy phiên đồng bộ mới nhất
                    var latestSession = response.data[0];
                    
                    // Cập nhật giao diện
                    updateSyncLogUI(latestSession);
                    
                    // Nếu phiên đồng bộ đã hoàn thành, dừng polling
                    if (latestSession.status !== 'in_progress') {
                        stopSyncLogPolling();
                    }
                }
            }
        });
    }, 3000); // Polling mỗi 3 giây
}

// Hàm để dừng polling
function stopSyncLogPolling() {
    if (syncPollingInterval) {
        clearInterval(syncPollingInterval);
        syncPollingInterval = null;
    }
}

// Hàm để cập nhật giao diện nhật ký đồng bộ
function updateSyncLogUI(sessionData) {
    // Cập nhật tiến trình
    var progressPercent = 0;
    if (sessionData.total_tags > 0) {
        progressPercent = Math.round((sessionData.tags_processed / sessionData.total_tags) * 100);
    }
    
    $('#tags_loading_progress').css('width', progressPercent + '%');
    
    // Cập nhật thông tin trạng thái
    var statusText = '';
    if (sessionData.status === 'in_progress') {
        statusText = '<?php echo _l('tags_sync_in_progress'); ?>';
    } else if (sessionData.status === 'completed') {
        statusText = '<?php echo _l('tags_sync_complete'); ?>';
    } else {
        statusText = 'Đồng bộ thất bại';
    }
    
    $('#tags_loading p').text(statusText);
    
    // Cập nhật chi tiết
    var detailsHtml = '';
    detailsHtml += '<div>Tổng số tags: ' + sessionData.total_tags + '</div>';
    detailsHtml += '<div>Đã xử lý: ' + sessionData.tags_processed + '</div>';
    detailsHtml += '<div>Thành công: ' + sessionData.success_count + '</div>';
    detailsHtml += '<div>Lỗi: ' + sessionData.error_count + '</div>';
    detailsHtml += '<div>Cập nhật lần cuối: ' + sessionData.last_update + '</div>';
    
    $('#tags_sync_details').html(detailsHtml);
}

// Cập nhật hàm syncTags để bắt đầu polling
function syncTags(controllerId) {
    // ... Mã hiện tại ...
    
    // Bắt đầu polling nhật ký đồng bộ
    startSyncLogPolling(controllerId);
}
```

## 6. Lợi ích của phương án

1. **Theo dõi chi tiết**: Ghi lại mọi bước trong quá trình đồng bộ, giúp dễ dàng debug khi có lỗi
2. **Cập nhật theo thời gian thực**: Người dùng có thể theo dõi tiến trình đồng bộ theo thời gian thực
3. **Lịch sử đồng bộ**: Lưu trữ lịch sử các phiên đồng bộ để tham khảo sau này
4. **Khả năng mở rộng**: Cấu trúc dữ liệu linh hoạt, có thể mở rộng để lưu trữ thêm thông tin nếu cần

## 7. Kết luận

Phương án này sẽ cung cấp một hệ thống ghi nhật ký đồng bộ tags toàn diện, giúp người dùng theo dõi quá trình đồng bộ một cách chi tiết và trực quan. Việc sử dụng bảng `tbltopic_external_data` hiện có giúp tận dụng cơ sở hạ tầng sẵn có mà không cần tạo thêm bảng mới.
