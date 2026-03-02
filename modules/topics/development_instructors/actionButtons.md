# Action Buttons: Kiến trúc và Hướng dẫn Triển khai

## 1. Tổng quan

Action Buttons là một hệ thống cho phép quản lý các hành động được định nghĩa trước (predefined actions) trong module Topics của Perfex CRM. Hệ thống này cho phép tạo các nút bấm linh hoạt liên kết với các quy trình xử lý (workflows) cụ thể, giúp người dùng thực hiện các hành động phức tạp thông qua giao diện đồ họa đơn giản.

## 2. Cấu trúc Cơ sở Dữ liệu

### 2.1. Bảng Dữ liệu Chính

#### tbltopic_action_buttons
Lưu trữ thông tin về các nút hành động:
- `id`: Khóa chính
- `name`: Tên nút hiển thị
- `button_type`: Loại nút (primary, info, warning, danger...)
- `workflow_id`: ID của workflow N8N sẽ được gọi
- `trigger_type`: Kiểu kích hoạt (webhook, native)
- `action_command`: Lệnh hành động (tùy chọn)
- `target_action_type`: Loại hành động cần đạt được (liên kết đến action_type_code)
- `ignore_types`: Danh sách JSON các loại hành động sẽ bỏ qua (không hiển thị nút)
- `target_action_state`: Trạng thái hành động cần đạt được (liên kết đến action_state_code)
- `ignore_states`: Danh sách JSON các trạng thái sẽ bỏ qua (không hiển thị nút)
- `description`: Mô tả nút
- `settings`: Cài đặt bổ sung (JSON)
- `status`: Trạng thái hoạt động (0: Vô hiệu hóa, 1: Kích hoạt)
- `order`: Thứ tự hiển thị

#### tbltopic_action_types
Định nghĩa các loại hành động:
- `id`: Khóa chính
- `name`: Tên loại hành động
- `action_type_code`: Mã loại hành động (duy nhất, sử dụng trong mã nguồn)
- `datecreated`: Ngày tạo
- `dateupdated`: Ngày cập nhật
- `position`: Vị trí hiển thị
- `parent_id`: ID của loại hành động cha (NULL nếu là loại hành động gốc)

#### tbltopic_action_states
Định nghĩa các trạng thái cho mỗi loại hành động:
- `id`: Khóa chính
- `name`: Tên trạng thái
- `action_state_code`: Mã trạng thái (duy nhất, sử dụng trong mã nguồn)
- `action_type_code`: Mã loại hành động liên kết
- `datecreated`: Ngày tạo
- `dateupdated`: Ngày cập nhật
- `color`: Mã màu hiển thị (#HEX)
- `position`: Vị trí hiển thị
- `valid_data`: Trạng thái có dữ liệu hợp lệ (0: Không, 1: Có)

#### tbltopic_automation_logs
Theo dõi quá trình thực thi các automation:
- `id`: Khóa chính
- `topic_id`: ID của topic
- `automation_id`: ID của phiên thực thi N8N
- `workflow_id`: ID của workflow N8N
- `status`: Trạng thái thực thi (started, completed, failed)
- `datecreated`: Ngày tạo
- `dateupdated`: Ngày cập nhật

### 2.2. Mối Quan hệ Dữ liệu

1. **Action Types và States**:
   - Mỗi action_type_code trong tbltopic_action_types có thể có nhiều action_state_code trong tbltopic_action_states
   - Quan hệ 1-n giữa action_type_code và action_state_code

2. **Action Buttons và Types/States**:
   - Mỗi nút trỏ đến một cặp target_action_type và target_action_state
   - Mỗi nút có thể bỏ qua các types và states thông qua ignore_types và ignore_states

3. **Phân cấp Action Types**:
   - Action types có cấu trúc phân cấp (parent_id), cho phép tổ chức theo nhóm

4. **Automation Logs**:
   - Lưu trữ lịch sử thực thi cho mỗi topic khi Action Button được kích hoạt

## 3. Cách Hoạt động

### 3.1. Quy Trình Hoạt động của Action Button

1. **Hiển thị Action Buttons**:
   - Truy vấn các nút từ tbltopic_action_buttons
   - Lọc nút dựa trên trạng thái hiện tại của topic và các điều kiện ignore_types/ignore_states
   - Hiển thị nút trên giao diện người dùng

2. **Xử lý Hành Động khi Người Dùng Bấm Nút**:
   - Lấy workflow_id và trigger_type từ cấu hình nút
   - Chuẩn bị dữ liệu topic gửi đến workflow
   - Khởi tạo ghi log trong tbltopic_automation_logs
   - Gọi webhook hoặc native function để kích hoạt workflow

3. **Xử lý Kết quả từ Workflow**:
   - Nhận kết quả từ workflow (webhook callback hoặc trực tiếp)
   - Cập nhật trạng thái topic dựa trên target_action_type và target_action_state
   - Cập nhật log trong tbltopic_automation_logs
   - Hiển thị thông báo thành công/thất bại cho người dùng

4. **Kiểm tra Trạng thái Thực thi (Polling)**:
   - Đối với các workflow dài, sử dụng cơ chế polling để kiểm tra trạng thái
   - Cập nhật UI với trạng thái hiện tại
   - Khi hoàn thành, lấy kết quả và cập nhật dữ liệu

### 3.2. Ví dụ Quy trình

#### Khởi động từ Google Sheet:
1. Người dùng bấm nút "Khởi động (Google Sheet)"
2. Hệ thống gửi dữ liệu topic đến workflow N8N
3. N8N xử lý dữ liệu, lấy thông tin từ Google Sheet
4. Kết quả được trả về và cập nhật vào topic
5. Trạng thái topic chuyển thành init:success
6. UI hiển thị thông báo thành công và các nút tiếp theo

#### Xây dựng Cấu trúc Bài viết:
1. Người dùng bấm nút "Khởi động (Native)"
2. Hệ thống gửi dữ liệu topic đến workflow xử lý
3. Workflow phân tích và xây dựng cấu trúc bài viết
4. Kết quả được trả về và cập nhật vào topic
5. Trạng thái topic chuyển thành BuildPostStructure_Success
6. UI hiển thị thông báo thành công và các nút tiếp theo

## 4. Công nghệ và Tích hợp

### 4.1. Tích hợp với N8N

Action Buttons có thể gọi workflows từ nền tảng automation N8N thông qua:
- **Webhook**: Gửi HTTP request đến endpoint được định nghĩa trong N8N
- **Native Function**: Gọi trực tiếp các hàm PHP xử lý trong hệ thống

### 4.2. Tùy chỉnh Hiển thị Nút

Action Buttons hỗ trợ cơ chế tùy chỉnh hiển thị nút:
- **Ignore Types**: Bỏ qua hiển thị nút khi topic ở các loại hành động nhất định
- **Ignore States**: Bỏ qua hiển thị nút khi topic ở các trạng thái nhất định
- **Button Type**: Đa dạng kiểu nút (primary, info, warning, danger, v.v.)
- **Order**: Kiểm soát thứ tự hiển thị của các nút

### 4.3. Xử lý Trạng thái

Hệ thống Action Buttons quản lý trạng thái của topics thông qua:
- **Target Action Type**: Loại hành động cần đạt được
- **Target Action State**: Trạng thái cần đạt được
- **Status Polling**: Cơ chế kiểm tra trạng thái thực thi
- **Automation Logs**: Lưu vết quá trình thực thi

## 5. Hướng dẫn Triển khai

### 5.1. Tạo mới Action Button

```php
$data = [
    'name' => 'Tên Nút',
    'button_type' => 'primary',
    'workflow_id' => 'id-workflow-n8n',
    'trigger_type' => 'webhook',
    'target_action_type' => 'BuildPostStructure',
    'target_action_state' => 'BuildPostStructure_Success',
    'ignore_types' => json_encode(['ExecutionTag_ExecWriting']),
    'ignore_states' => json_encode(['ExecutionTag_ExecWriting_Complete']),
    'description' => 'Mô tả nút',
    'status' => 1,
    'order' => 1
];

$this->db->insert('tbltopic_action_buttons', $data);
```

### 5.2. Tạo mới Action Type & State

```php
// Tạo Action Type
$type_data = [
    'name' => 'Tên Loại Hành Động',
    'action_type_code' => 'NewActionType',
    'parent_id' => null
];

$this->db->insert('tbltopic_action_types', $type_data);

// Tạo Action State
$state_data = [
    'name' => 'Tên Trạng Thái',
    'action_state_code' => 'NewActionType_Success',
    'action_type_code' => 'NewActionType',
    'color' => '#28a745'
];

$this->db->insert('tbltopic_action_states', $state_data);
```

### 5.3. Cập nhật Trạng thái Topic

```php
$this->db->update('tbltopics', [
    'action_type_code' => 'NewActionType',
    'action_state_code' => 'NewActionType_Success'
], ['id' => $topic_id]);
```

### 5.4. Xử lý Callback từ Workflow

```php
public function workflow_callback() {
    $execution_id = $this->input->post('execution_id');
    $result = $this->input->post('result');
    $topic_id = $this->input->post('topic_id');
    
    // Cập nhật log
    $this->db->update('tbltopic_automation_logs', [
        'status' => 'completed',
        'dateupdated' => date('Y-m-d H:i:s')
    ], ['automation_id' => $execution_id]);
    
    // Cập nhật topic
    $this->db->update('tbltopics', [
        'action_type_code' => $this->input->post('target_action_type'),
        'action_state_code' => $this->input->post('target_action_state'),
        'data' => json_encode($result)
    ], ['id' => $topic_id]);
    
    echo json_encode(['success' => true]);
}
```

## 6. Tối ưu hóa và Mở rộng

### 6.1. Tối ưu hóa Hiệu suất

- **Caching**: Cache các action types, states và buttons để giảm truy vấn DB
- **Batch Processing**: Xử lý hàng loạt khi cần thực hiện nhiều hành động
- **Asynchronous Execution**: Sử dụng cơ chế thực thi bất đồng bộ cho các tác vụ dài

### 6.2. Hướng mở rộng

1. **Action Group-based Display**:
   - Nhóm các nút thành các nhóm hành động cho dễ quản lý
   - Hiển thị theo tab hoặc accordion

2. **Conditional Button Visibility**:
   - Mở rộng logic hiển thị nút dựa trên nhiều điều kiện phức tạp hơn
   - Cho phép biểu thức điều kiện tùy chỉnh

3. **Workflow Templates**:
   - Lưu trữ templates cho các tham số workflow
   - Cho phép tái sử dụng cấu hình workflow giữa các nút

4. **User-based Permissions**:
   - Kiểm soát quyền truy cập nút dựa trên vai trò người dùng
   - Ghi log hoạt động người dùng chi tiết hơn

5. **Smart Suggestions**:
   - Đề xuất hành động tiếp theo dựa trên mẫu sử dụng
   - Tự động hóa quy trình dựa trên lịch sử

## 7. Ví dụ thực tế

### 7.1. Quy trình xử lý nội dung website

1. **Khởi động (Google Sheet)**
   - Gửi dữ liệu đến workflow xử lý Google Sheet
   - Workflow lấy dữ liệu từ sheet và trả về
   - Topic chuyển trạng thái thành init:success

2. **Khởi động (Native)**
   - Gửi dữ liệu đến workflow xử lý cấu trúc
   - Workflow phân tích và xây dựng cấu trúc bài viết
   - Topic chuyển trạng thái thành BuildPostStructure_Success

3. **Tìm hình minh họa**
   - Gửi dữ liệu đến workflow tìm hình
   - Workflow tìm và trả về các hình ảnh phù hợp
   - Topic chuyển trạng thái thành SearchImageToggle:Yes

4. **Tạo ảnh bìa**
   - Gửi dữ liệu đến workflow tạo ảnh
   - Workflow sử dụng AI để tạo ảnh bìa
   - Topic chuyển trạng thái thành ImageGenerateToggle:Success

5. **Đăng bài lên WordPress**
   - Gửi dữ liệu đến workflow WordPress
   - Workflow tạo bài viết trên WordPress
   - Topic chuyển trạng thái thành ExecutionTag_ExecWriting_PostCreated

### 7.2. Quy trình phân phối nội dung

1. **Xem bài viết**
   - Mở liên kết đến bài viết WordPress
   - Hiển thị trong tab mới hoặc iframe

2. **Post Fanpage**
   - Gửi dữ liệu đến workflow đăng Facebook
   - Workflow đăng bài lên Fanpage
   - Topic chuyển trạng thái thành ExecutionTag_ExecAudit_SocialAuditCompleted

## 8. An toàn và Bảo mật

### 8.1. Kiểm soát Truy cập

- Kiểm tra quyền hạn người dùng trước khi hiển thị Action Buttons
- Xác thực người dùng trước khi thực thi workflow
- Giới hạn loại hành động theo vai trò người dùng

### 8.2. Xác thực Webhook

- Sử dụng API token để xác thực gọi webhook
- Mã hóa dữ liệu nhạy cảm
- Kiểm tra nguồn gốc yêu cầu

### 8.3. Ghi nhật ký và Kiểm toán

- Lưu log đầy đủ mọi hành động trong tbltopic_automation_logs
- Thiết lập cơ chế theo dõi hoạt động của người dùng
- Cho phép khôi phục trạng thái trước đó nếu cần

## 9. Khắc phục Sự cố

### 9.1. Vấn đề Kết nối N8N

- Kiểm tra cài đặt N8N trong admin settings
- Xác minh endpoint và workflow ID
- Kiểm tra kết nối mạng và firewall

### 9.2. Lỗi Hiển thị Button

- Kiểm tra target_action_type và target_action_state
- Xác minh ignore_types và ignore_states
- Xem log lỗi JavaScript trong console trình duyệt

### 9.3. Lỗi Xử lý Workflow

- Kiểm tra logs trong N8N
- Xác minh dữ liệu được gửi đến workflow
- Kiểm tra cập nhật trạng thái topic

## 10. Phát triển Mở rộng

### 10.1. Tích hợp với API Khác

```php
// Processor tùy chỉnh cho API bên thứ ba
class CustomAPIProcessor extends BaseTopicActionProcessor {
    public function process($topic_id, $action_data) {
        try {
            // Chuẩn bị dữ liệu
            $topic = $this->CI->db->get_where('tbltopics', ['id' => $topic_id])->row();
            $api_data = $this->prepareApiData($topic, $action_data);
            
            // Gọi API
            $response = $this->callExternalApi($api_data);
            
            // Xử lý kết quả
            $this->processResponse($topic_id, $response);
            
            return [
                'success' => true,
                'message' => 'API call successful',
                'data' => $response
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    // Methods tùy chỉnh khác...
}
```

### 10.2. Tạo Quy trình Tùy chỉnh

```php
// Trong controller
public function custom_workflow() {
    // Lấy dữ liệu
    $topic_id = $this->input->post('topic_id');
    $custom_data = $this->input->post('custom_data');
    
    // Thiết lập quy trình tùy chỉnh
    $workflow = [
        ['action' => 'process_data', 'params' => ['type' => 'BuildPostStructure']],
        ['action' => 'generate_images', 'params' => ['count' => 3]],
        ['action' => 'publish_content', 'params' => ['platform' => 'wordpress']]
    ];
    
    // Thực thi tuần tự
    foreach ($workflow as $step) {
        $processor = $this->get_data_processor($step['action']);
        $result = $processor->process($topic_id, $step['params']);
        
        if (!$result['success']) {
            echo json_encode(['success' => false, 'message' => $result['message']]);
            return;
        }
    }
    
    echo json_encode(['success' => true, 'message' => 'Custom workflow completed']);
}
```

### 10.3. Mở rộng Trạng thái

```php
// Thêm Action Types và States mới cho quy trình phức tạp
$complex_process = [
    'type' => [
        'name' => 'Quy trình phức tạp',
        'code' => 'ComplexProcess',
        'states' => [
            ['name' => 'Bắt đầu', 'code' => 'ComplexProcess_Started', 'color' => '#007bff'],
            ['name' => 'Đang xử lý', 'code' => 'ComplexProcess_Processing', 'color' => '#ffc107'],
            ['name' => 'Chờ đánh giá', 'code' => 'ComplexProcess_WaitingReview', 'color' => '#17a2b8'],
            ['name' => 'Hoàn thành', 'code' => 'ComplexProcess_Completed', 'color' => '#28a745'],
            ['name' => 'Lỗi', 'code' => 'ComplexProcess_Error', 'color' => '#dc3545']
        ]
    ]
];

// Thêm vào database
$this->db->insert('tbltopic_action_types', [
    'name' => $complex_process['type']['name'],
    'action_type_code' => $complex_process['type']['code']
]);

foreach ($complex_process['type']['states'] as $state) {
    $this->db->insert('tbltopic_action_states', [
        'name' => $state['name'],
        'action_state_code' => $state['code'],
        'action_type_code' => $complex_process['type']['code'],
        'color' => $state['color']
    ]);
}
```

## 11. Kết luận

Action Buttons cung cấp một cơ chế mạnh mẽ và linh hoạt để quản lý quy trình xử lý topic trong hệ thống. Bằng cách tách biệt logic giao diện người dùng với logic nghiệp vụ thông qua các workflows, hệ thống giúp:

1. **Tăng tính mô-đun**: Dễ dàng thêm, sửa, xóa quy trình mà không cần thay đổi mã nguồn
2. **Linh hoạt trong triển khai**: Tích hợp với nhiều dịch vụ bên ngoài thông qua N8N
3. **UX tốt hơn**: Cung cấp giao diện trực quan để thực hiện các tác vụ phức tạp
4. **Kiểm soát quy trình**: Theo dõi và quản lý trạng thái xử lý topic

Qua việc tiếp tục mở rộng và tối ưu hóa hệ thống Action Buttons, các quy trình nghiệp vụ phức tạp có thể được tự động hóa và đơn giản hóa, nâng cao hiệu quả và trải nghiệm người dùng cho toàn bộ hệ thống.
