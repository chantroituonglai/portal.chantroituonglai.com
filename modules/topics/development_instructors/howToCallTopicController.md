# Hướng dẫn Cấu trúc và Sử dụng Topic Controller

## Tổng quan

Topic Controller (`Topics.php`) là một thành phần trung tâm trong module Topics của Perfex CRM, quản lý tất cả các hành động liên quan đến topic thông qua các processors chuyên biệt. Tài liệu này giải thích cách hoạt động của controller và hướng dẫn sử dụng đúng cách.

## Mục đích chính của Topic Controller

Topic Controller được tạo ra với các mục đích chính:

1. **Quản lý cấu hình website đích**: Là nơi lưu trữ cấu hình của các website đăng tải nội dung sau khi được xử lý (như WordPress, Haravan, PrestaShop...) thông qua tên miền và các API key để kết nối.

2. **Cung cấp thông tin cho N8N Workflows**: Chuyển dữ liệu cấu hình đến N8N để thực hiện các tác vụ như truy xuất danh mục bài viết, danh mục chủ đề, và các thao tác tích hợp khác.

3. **Lưu trữ hướng dẫn cá nhân hóa nội dung**: Mỗi controller chứa các mô tả viết bài trong trường `action_1` và `action_2`, giúp cá nhân hóa nội dung theo từng website.

4. **Quản lý tài nguyên đa phương tiện**: Lưu trữ logo và các hình ảnh được sử dụng trong việc thiết kế banner cho website đích.

Nhờ Topic Controller, khi truy cập vào một topic để thực hiện các tác vụ đặc thù như viết bài, tạo ảnh, hoặc các use case khác, người dùng tiết kiệm được thời gian nhập liệu các thông tin cấu hình, bố cục và phong cách.

## Cấu trúc Controller

Topic Controller tuân theo kiến trúc MVC của CodeIgniter và bao gồm các loại phương thức sau:

1. **Các thao tác CRUD cơ bản**:
   - `index()`: Hiển thị tất cả các topic
   - `create()`: Tạo mới topic
   - `edit($id)`: Chỉnh sửa topic
   - `delete($id)`: Xóa topic
   - `table()`: Tạo bảng dữ liệu

2. **Phương thức xử lý hành động**:
   - `process_data($id, $action_type_code)`: Phương thức chính xử lý các hành động topic
   - `execute_workflow()`: Thực thi N8N workflows
   - `get_data_processor($action_type_code)`: Phương thức factory để lấy processor phù hợp

3. **Phương thức AJAX**:
   - `get_processed_data()`: Lấy dữ liệu đã xử lý cho topic
   - `check_workflow_status()`: Kiểm tra trạng thái của workflow
   - `get_execution_details($execution_id)`: Lấy chi tiết của việc thực thi workflow

4. **Phương thức tiện ích**:
   - `toggle_active($id)`: Chuyển đổi trạng thái kích hoạt topic
   - `get_topic_history_ajax()`: Lấy dữ liệu lịch sử topic
   - `save_composed_topic()`: Lưu topic đã soạn thảo
   - `get_log_data()`: Lấy dữ liệu nhật ký

## Cấu trúc và Quan hệ Cơ sở dữ liệu

Topic Controller tương tác với một số bảng cơ sở dữ liệu chính:

1. **tbltopic_controllers**: Lưu trữ cấu hình theo nền tảng
   - Trường chính: id, status, site, platform, blog_id, v.v.
   - Chứa dữ liệu cấu hình như logo_url, slogan, api_token
   - Lưu trữ hướng dẫn nội dung và chỉ thị trong các trường action_1 và action_2
   - Lưu thông tin về phong cách viết bài, thông tin liên hệ (emails)
   - Các thông tin kết nối như API token và dữ liệu project

2. **tbltopic_master**: Bảng chủ đề chính liên kết đến controllers
   - Trường chính: id, topicid, topictitle, status
   - Khóa ngoại: controller_id (liên kết đến tbltopic_controllers)
   - Quản lý thông tin cốt lõi của topic

3. **tbltopics**: Lưu trữ thông tin chi tiết topic và trạng thái xử lý
   - Trường chính: id, topicid, topictitle, position, data (JSON)
   - Theo dõi quá trình: action_type_code, action_state_code
   - Lưu trữ nội dung: Dữ liệu JSON có cấu trúc trong trường data
   - Ghi nhật ký: Nhật ký xử lý chi tiết trong trường log

Mối quan hệ:
- Một controller có thể có nhiều topics (quan hệ 1:N từ controllers đến master)
- Một master topic có thể có nhiều topic detail (quan hệ 1:N)

## Tương tác với Processor

Topic Controller tương tác với các lớp processor thông qua phương thức `process_data()` và mẫu factory:

```php
public function process_data($id, $action_type_code) {
    // Kiểm tra quyền hạn
    if (!has_permission('topics', '', 'edit')) {
        access_denied('topics');
    }

    // Lấy dữ liệu topic
    $topic = $this->Topics_model->get_topic($id);
    if (!$topic) {
        show_404();
    }
    
    // Lấy processor phù hợp sử dụng phương thức factory
    $data['processor'] = $this->get_data_processor($action_type_code);
    
    // Nếu form được gửi, xử lý dữ liệu
    if ($this->input->post()) {
        $processed_data = $this->input->post();
        $success = $this->processor->process($id, $processed_data);
        
        // Đặt thông báo và chuyển hướng
        if ($success) {
            set_alert('success', _l('data_processed_successfully'));
        } else {
            set_alert('danger', _l('data_processing_failed'));
        }
        redirect(admin_url('topics/detail/' . $id));
    }

    // Tải view
    $this->load->view('topics/includes/process_data', $data);
}

private function get_data_processor($action_type_code) {
    $this->load->helper('topics_data_processor');
    
    // Trả về lớp processor phù hợp dựa trên loại hành động
    switch ($action_type_code) {
        case 'ImageGeneration':
            return new ImageGenerateToggleProcessor();
        case 'WORDPRESS_POST':
            return new WordPressPostSelectionProcessor();
        case 'BuildPostStructure':
            return new BuildPostStructureProcessor();
        case 'ContentGeneration':
            return new ContentGenerationProcessor();
        case 'TopicComposer':
            return new TopicComposerProcessor();
        case 'DraftWriting':
            return new DraftWritingProcessor();
        // Thêm processors khác khi cần
        default:
            return null;
    }
}
```

## Cách gọi Topic Controller

### 1. Từ PHP Controller/View

Để gọi một phương thức từ controller hoặc view khác:

```php
// Gọi phương thức chuẩn
$this->load->model('Topics_model');
$topics = $this->Topics_model->get_all_topics();

// Chuyển hướng đến một phương thức controller
redirect(admin_url('topics/detail/' . $topic_id));

// Bao gồm một view
$this->load->view('topics/detail', ['topic_id' => $topic_id]);

// Tạo một topic mới với liên kết controller
$topic_data = [
    'topicid' => $topic_url,
    'topictitle' => $topic_title,
    'controller_id' => $controller_id,
    'status' => 1
];
$topic_id = $this->Topics_model->add_topic($topic_data);

// Cập nhật topic với dữ liệu mới
$updated_data = [
    'topictitle' => $new_title,
    'status' => $new_status
];
$this->Topics_model->update_topic($topic_id, $updated_data);

// Lấy thông tin controller cho một topic
function getControllerForTopic($topic_id) {
    // Lấy instance của CI
    $CI = &get_instance();
    
    // Lấy dữ liệu topic
    $topic = $CI->db->get_where('tbltopics', ['id' => $topic_id])->row();
    if (!$topic) return null;
    
    // Lấy dữ liệu master topic
    $master = $CI->db->get_where('tbltopic_master', ['topicid' => $topic->topicid])->row();
    if (!$master || !$master->controller_id) return null;
    
    // Lấy dữ liệu controller
    $controller = $CI->db->get_where('tbltopic_controllers', ['id' => $master->controller_id])->row();
    return $controller;
}
```

### 2. Từ JavaScript/AJAX

Để gọi AJAX đến Topic Controller:

```javascript
// Ví dụ: Thực thi một workflow
$.ajax({
    url: admin_url + 'topics/execute_workflow',
    type: 'POST',
    dataType: 'json',
    data: {
        topic_id: topicId,
        workflow_id: workflowId,
        action_type_code: actionTypeCode,
        // Các tham số bổ sung
    },
    success: function(response) {
        if (response.success) {
            // Xử lý thành công
            if (response.data.needs_polling) {
                // Bắt đầu kiểm tra trạng thái workflow
                pollWorkflowStatus(response.data.workflow_id, response.data.execution_id);
            } else {
                // Quá trình hoàn thành ngay lập tức
                alert_float('success', response.message);
                // Cập nhật UI với dữ liệu mới
                updateTopicData(topicId, response.data.response);
            }
        } else {
            // Xử lý lỗi
            alert_float('danger', response.message);
            console.error('Lỗi thực thi workflow:', response.message);
        }
    },
    error: function(xhr, status, error) {
        // Xử lý lỗi AJAX
        alert_float('danger', 'Lỗi kết nối đến máy chủ: ' + error);
        console.error('Lỗi AJAX:', xhr.responseText);
    }
});

// Ví dụ: Kiểm tra trạng thái workflow
function pollWorkflowStatus(workflowId, executionId) {
    $.ajax({
        url: admin_url + 'topics/check_workflow_status',
        type: 'POST',
        dataType: 'json',
        data: {
            workflow_id: workflowId,
            execution_id: executionId
        },
        success: function(response) {
            if (response.success) {
                if (response.data.status === 'completed') {
                    // Workflow hoàn thành
                    getExecutionDetails(executionId);
                } else if (response.data.status === 'failed') {
                    // Workflow thất bại
                    alert_float('danger', 'Thực thi workflow thất bại');
                    showErrorDetails(response.data.error);
                } else {
                    // Tiếp tục kiểm tra (đang xử lý hoặc đang chờ)
                    $('#workflow-status').text('Trạng thái: ' + response.data.status);
                    setTimeout(function() {
                        pollWorkflowStatus(workflowId, executionId);
                    }, 2000);
                }
            } else {
                // Lỗi kiểm tra
                alert_float('warning', 'Lỗi kiểm tra trạng thái workflow: ' + response.message);
                // Thử lại sau khoảng thời gian dài hơn
                setTimeout(function() {
                    pollWorkflowStatus(workflowId, executionId);
                }, 5000);
            }
        },
        error: function(xhr, status, error) {
            console.error('Lỗi kiểm tra trạng thái:', error);
            // Lỗi mạng, thử lại sau một khoảng thời gian
            setTimeout(function() {
                pollWorkflowStatus(workflowId, executionId);
            }, 5000);
        }
    });
}
```

### 3. Từ các lớp Processor

Các lớp Processor tương tác với Topic Controller thông qua các mẫu thiết lập:

```php
class SomeProcessor extends BaseTopicActionProcessor {
    public function process($topic_id, $action_data) {
        try {
            // Xác thực đầu vào
            if (!$this->validate($topic_id, $action_data)) {
                return [
                    'success' => false,
                    'message' => implode('; ', $this->getErrors())
                ];
            }
            
            // Lấy dữ liệu topic sử dụng instance CI
            $topic = $this->CI->db->get_where(db_prefix() . 'topics', ['id' => $topic_id])->row();
            
            // Lấy thông tin controller nếu cần
            $topic_master = $this->CI->db->get_where(db_prefix() . 'tbltopic_master', ['topicid' => $topic->topicid])->row();
            $controller = null;
            if ($topic_master && $topic_master->controller_id) {
                $controller = $this->CI->db->get_where(db_prefix() . 'tbltopic_controllers', ['id' => $topic_master->controller_id])->row();
                
                // Sử dụng thông tin controller - ví dụ áp dụng hướng dẫn viết bài
                $writing_instructions = $controller->action_1;
                $promotional_content = $controller->action_2;
            }
            
            // Chuẩn bị dữ liệu cho N8N
            $n8n_data = $this->prepareN8nData($topic, $action_data, $controller);
            
            // Gọi N8N workflow
            $result = send_to_n8n($action_data['workflow_id'], $n8n_data);
            
            // Xử lý phản hồi và trả về kết quả
            return [
                'success' => true,
                'message' => _l('workflow_executed_successfully'),
                'data' => [
                    'response' => $result['data']['response'],
                    'execution_id' => $result['data']['execution_id'] ?? null,
                    'needs_polling' => true
                ]
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    // Phương thức chuẩn bị dữ liệu cho N8N với thông tin controller
    private function prepareN8nData($topic, $action_data, $controller = null) {
        $data = [
            'topic_id' => $topic->id,
            'topic_title' => $topic->topictitle,
            'topic_data' => json_decode($topic->data, true),
            // Các thông tin khác từ topic
        ];
        
        // Thêm thông tin controller nếu có
        if ($controller) {
            $data['controller'] = [
                'site' => $controller->site,
                'platform' => $controller->platform,
                'logo_url' => $controller->logo_url,
                'slogan' => $controller->slogan,
                'writing_style' => $controller->writing_style,
                'action_1' => $controller->action_1,
                'action_2' => $controller->action_2,
            ];
        }
        
        return $data;
    }
}
```

## Các mẫu sử dụng phổ biến

### 1. Mẫu thực thi Workflow

```
Yêu cầu Client → Topics Controller → Lấy Processor → Processor chuẩn bị dữ liệu → 
Gửi đến N8N → N8N xử lý → Trả về phản hồi ban đầu → 
Client kiểm tra trạng thái → Topics Controller kiểm tra trạng thái → 
Trả về kết quả cuối cùng → Client hiển thị kết quả
```

### 2. Mẫu xử lý dữ liệu

```
Gửi form → Topics Controller process_data() → Lấy Processor phù hợp → 
Processor xác thực → Processor xử lý → Trả về kết quả → 
Controller đặt thông báo → Chuyển hướng
```

### 3. Mẫu tạo và quản lý Topic

```
Tạo Controller Entry → Tạo Master Topic → Liên kết qua controller_id →
Xử lý nội dung Topic → Lưu vào tbltopics → Cập nhật trạng thái và States →
Hiển thị kết quả cho người dùng
```

### 4. Mẫu xử lý cấu trúc nội dung

```
Thu thập nội dung → Phân tích cấu trúc → Tạo các phần Topic →
Áp dụng định dạng đặc thù của Controller → Tạo nội dung đầu ra →
Lưu kết quả vào cơ sở dữ liệu → Cập nhật trạng thái xử lý
```

## Áp dụng cấu hình Controller vào nội dung

Khi tích hợp với Topic Controller, bạn thường cần truy cập và sử dụng cấu hình controller:

```php
// Áp dụng định dạng controller vào nội dung
function applyControllerFormatting($content, $controller) {
    if (!$controller) return $content;
    
    // Áp dụng định dạng theo trang cụ thể từ cài đặt controller
    if (!empty($controller->action_1)) {
        // Phân tích hướng dẫn action_1 và áp dụng định dạng
        $writing_instructions = $controller->action_1;
        
        // Ví dụ: Thêm nhãn hiệu trang web vào nội dung
        if (strpos($content, '{SITE_NAME}') !== false) {
            $content = str_replace('{SITE_NAME}', $controller->site, $content);
        }
        
        // Ví dụ: Thêm slogan vào phần đầu nội dung
        if (!empty($controller->slogan)) {
            $content = '<p><em>' . $controller->slogan . '</em></p>' . $content;
        }
        
        // Áp dụng phong cách viết nếu có định nghĩa
        if (!empty($controller->writing_style)) {
            // Phân tích và áp dụng phong cách viết
            // Đây có thể là gọi API AI để định dạng lại nội dung
            // $content = applyWritingStyle($content, $controller->writing_style);
        }
    }
    
    // Thêm nội dung quảng cáo nếu cần
    if (!empty($controller->action_2)) {
        $promotional_content = $controller->action_2;
        // Thêm nội dung quảng cáo từ action_2
        $content .= "\n\n" . $promotional_content;
    }
    
    return $content;
}

// Tạo ảnh bìa với logo của controller
function generateCoverWithControllerLogo($topic_title, $controller) {
    if (!$controller || empty($controller->logo_url)) {
        // Sử dụng logo mặc định
        $logo_url = base_url('assets/images/default_logo.png');
    } else {
        $logo_url = $controller->logo_url;
    }
    
    // Gọi service tạo ảnh với logo và tiêu đề
    $cover_params = [
        'title' => $topic_title,
        'logo_url' => $logo_url,
        'slogan' => $controller->slogan ?? '',
        'site_name' => $controller->site ?? ''
    ];
    
    // Trả về URL hình ảnh đã tạo
    return callImageGenerationService($cover_params);
}
```

## Triển khai các use case phổ biến

### 1. Quá trình viết bài với thông tin controller

```php
class DraftWritingProcessor extends BaseTopicActionProcessor {
    public function process($topic_id, $action_data) {
        try {
            // Lấy dữ liệu topic
            $topic = $this->CI->db->get_where(db_prefix() . 'topics', ['id' => $topic_id])->row();
            
            // Lấy controller cho topic này
            $topic_master = $this->CI->db->get_where(db_prefix() . 'tbltopic_master', ['topicid' => $topic->topicid])->row();
            $controller = null;
            if ($topic_master && $topic_master->controller_id) {
                $controller = $this->CI->db->get_where(db_prefix() . 'tbltopic_controllers', ['id' => $topic_master->controller_id])->row();
            }
            
            // Chuẩn bị dữ liệu cho việc viết bài
            $draft_data = [
                'topic_title' => $topic->topictitle,
                'topic_data' => json_decode($topic->data, true),
                'writing_instructions' => $controller ? $controller->action_1 : null,
                'promotional_content' => $controller ? $controller->action_2 : null,
                'website_style' => $controller ? [
                    'site' => $controller->site,
                    'platform' => $controller->platform,
                    'slogan' => $controller->slogan,
                    'writing_style' => $controller->writing_style
                ] : null
            ];
            
            // Gọi workflow N8N để viết bài
            $result = send_to_n8n($action_data['workflow_id'], $draft_data);
            
            // Cập nhật topic với bài viết đã tạo
            $this->CI->db->update(db_prefix() . 'topics', [
                'action_type_code' => 'DraftWriting',
                'action_state_code' => 'DraftWriting_Completed',
                'draft_content' => $result['data']['draft_content'] ?? null
            ], ['id' => $topic_id]);
            
            return [
                'success' => true,
                'message' => 'Bài viết đã được tạo thành công',
                'data' => $result['data']
            ];
        } catch (Exception $e) {
            // Cập nhật trạng thái lỗi
            $this->CI->db->update(db_prefix() . 'topics', [
                'action_state_code' => 'DraftWriting_Failed'
            ], ['id' => $topic_id]);
            
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}
```

### 2. Đăng bài lên WordPress với thông tin controller

```php
class WordPressPostProcessor extends BaseTopicActionProcessor {
    public function process($topic_id, $action_data) {
        try {
            // Lấy dữ liệu topic
            $topic = $this->CI->db->get_where(db_prefix() . 'topics', ['id' => $topic_id])->row();
            
            // Lấy controller để biết thông tin WordPress
            $topic_master = $this->CI->db->get_where(db_prefix() . 'tbltopic_master', ['topicid' => $topic->topicid])->row();
            $controller = null;
            if ($topic_master && $topic_master->controller_id) {
                $controller = $this->CI->db->get_where(db_prefix() . 'tbltopic_controllers', ['id' => $topic_master->controller_id])->row();
            }
            
            if (!$controller || empty($controller->site) || $controller->platform != 'wordpress') {
                throw new Exception("Controller không hợp lệ hoặc không phải WordPress");
            }
            
            // Dữ liệu đăng bài WordPress
            $post_data = [
                'title' => $topic->topictitle,
                'content' => $topic->draft_content,
                'wordpress_site' => $controller->site,
                'api_token' => $controller->api_token,
                // Các thông tin khác
            ];
            
            // Gọi workflow N8N để đăng bài
            $result = send_to_n8n($action_data['workflow_id'], $post_data);
            
            // Cập nhật topic với thông tin bài đã đăng
            $this->CI->db->update(db_prefix() . 'topics', [
                'action_type_code' => 'WORDPRESS_POST',
                'action_state_code' => 'WORDPRESS_POST_Published',
                'post_url' => $result['data']['post_url'] ?? null,
                'post_id' => $result['data']['post_id'] ?? null
            ], ['id' => $topic_id]);
            
            return [
                'success' => true,
                'message' => 'Bài viết đã được đăng lên WordPress',
                'data' => $result['data']
            ];
        } catch (Exception $e) {
            // Cập nhật trạng thái lỗi
            $this->CI->db->update(db_prefix() . 'topics', [
                'action_state_code' => 'WORDPRESS_POST_Failed'
            ], ['id' => $topic_id]);
            
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}
```

## Thực hành tốt nhất

1. **Sử dụng xử lý lỗi phù hợp**:
   - Luôn bọc logic processor trong try-catch
   - Trả về phản hồi lỗi có cấu trúc
   - Ghi log lỗi để gỡ lỗi
   - Cập nhật action_state_code để phản ánh lỗi

2. **Xác thực đầu vào**:
   - Luôn xác thực tất cả đầu vào trước khi xử lý
   - Trả về thông báo lỗi xác thực rõ ràng
   - Kiểm tra cấu trúc JSON phù hợp trong các trường dữ liệu

3. **Tuân theo cấu trúc phản hồi**:
   - Sử dụng cấu trúc phản hồi nhất quán:
     ```php
     [
         'success' => true/false,
         'message' => 'Thông báo đọc được',
         'data' => [
             // Dữ liệu phản hồi
         ]
     ]
     ```

4. **Sử dụng polling cho các tác vụ dài**:
   - Đặt cờ `needs_polling` cho các hoạt động cần kiểm tra trạng thái
   - Triển khai xử lý timeout phù hợp
   - Bao gồm thông tin tiến trình khi có thể
   - Cung cấp cơ chế dự phòng cho các gián đoạn

5. **Duy trì tách biệt mối quan tâm**:
   - Controllers xử lý yêu cầu và phản hồi
   - Processors xử lý logic nghiệp vụ
   - Models xử lý truy cập dữ liệu
   - Giữ cập nhật UI tách biệt với xử lý dữ liệu

6. **Sử dụng cấu hình Controller**:
   - Luôn kiểm tra xem controller có tồn tại trước khi sử dụng cài đặt
   - Áp dụng định dạng cụ thể theo site và platform
   - Sử dụng cài đặt email controller cho thông báo
   - Tuân theo hướng dẫn phong cách viết từ controller

7. **Hoạt động cơ sở dữ liệu**:
   - Sử dụng transactions cho các hoạt động nhiều bước
   - Giữ nhật ký xử lý trong trường log
   - Duy trì định dạng dữ liệu nhất quán trong các trường JSON
   - Cập nhật timestamps một cách phù hợp

8. **Quản lý Workflow**:
   - Theo dõi ID thực thi workflow
   - Triển khai cơ chế thử lại phù hợp
   - Xử lý gián đoạn quá trình một cách thanh nhã
   - Lưu kết quả trung gian khi có thể

## Kết luận

Topic Controller là thành phần trung tâm trong hệ thống quản lý topic, cung cấp cơ chế linh hoạt để cấu hình và tùy chỉnh quy trình xử lý nội dung theo từng website đích. Bằng cách lưu trữ thông tin cấu hình, hướng dẫn viết bài, và tài nguyên như logo trong các controller, hệ thống giúp tiết kiệm thời gian nhập liệu và đảm bảo tính nhất quán trong các tác vụ đặc thù như viết bài, tạo ảnh và đăng tải nội dung.

Việc hiểu rõ cấu trúc và cách sử dụng Topic Controller sẽ giúp bạn phát triển và mở rộng module Topics một cách hiệu quả, đồng thời tận dụng tối đa khả năng cá nhân hóa theo từng website đích.
