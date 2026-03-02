# Phần 1: Triển khai cơ chế Chunking cho Ultimate Editor

## 1. Phát triển hệ thống Chunking nội dung

### 1.1. Cấu trúc dữ liệu cho Chunking

Phân tích và thiết kế cấu trúc dữ liệu để lưu trữ và quản lý các phần nhỏ (chunks) của nội dung:

```javascript
{
  "topic_id": "123",
  "draft_id": "draft_123",
  "metadata": {
    "title": "Tiêu đề bài viết",
    "description": "Mô tả bài viết",
    "version": 1,
    "total_chunks": 5,
    "created_at": "2023-03-16T10:00:00Z",
    "updated_at": "2023-03-16T11:30:00Z",
    "author_id": 1
  },
  "chunks": [
    {
      "id": "chunk_1",
      "type": "heading",
      "content": "<h2>Phần giới thiệu</h2>",
      "position": 0
    },
    {
      "id": "chunk_2",
      "type": "paragraph",
      "content": "<p>Nội dung đoạn văn đầu tiên...</p>",
      "position": 1
    },
    // Các chunk khác
  ],
  "seo_data": {
    "keywords": ["từ khóa 1", "từ khóa 2"],
    "focus_keyword": "từ khóa chính"
  }
}
```

### 1.2. Thiết kế SectionEditor Component

Tạo component SectionEditor để xử lý từng phần nội dung:

```javascript
// section_editor.js
window.SectionEditor = (function() {
    var sections = [];
    var activeSection = null;
    var container = null;
    var callbacks = {};
    
    function init(options) {
        container = options.container;
        
        if (options.onChange) {
            callbacks.onChange = options.onChange;
        }
        
        // Khởi tạo sự kiện
        bindEvents();
        
        return {
            setSections: setSections,
            getSections: getSections,
            addSection: addSection,
            removeSection: removeSection,
            editSection: editSection
        };
    }
    
    function setSections(newSections) {
        sections = newSections;
        renderSections();
    }
    
    function getSections() {
        return sections;
    }
    
    function addSection(section) {
        sections.push(section);
        renderSections();
        if (callbacks.onChange) {
            callbacks.onChange('add', section);
        }
    }
    
    function removeSection(sectionId) {
        sections = sections.filter(function(section) {
            return section.id !== sectionId;
        });
        renderSections();
        if (callbacks.onChange) {
            callbacks.onChange('remove', { id: sectionId });
        }
    }
    
    function editSection(sectionId, content) {
        var index = sections.findIndex(function(section) {
            return section.id === sectionId;
        });
        
        if (index !== -1) {
            sections[index].content = content;
            renderSections();
            if (callbacks.onChange) {
                callbacks.onChange('edit', sections[index]);
            }
        }
    }
    
    function renderSections() {
        if (!container) return;
        
        var html = '';
        
        sections.forEach(function(section) {
            html += createSectionHtml(section);
        });
        
        $(container).html(html);
        
        // Khởi tạo lại sự kiện cho các section mới
        initSectionEvents();
    }
    
    function createSectionHtml(section) {
        var html = '<div class="section-item" data-section-id="' + section.id + '">';
        html += '<div class="section-header">';
        html += '<span class="section-title">' + (section.title || 'Section ' + section.position) + '</span>';
        html += '<div class="section-actions">';
        html += '<button class="btn btn-xs btn-info section-edit-btn" title="Edit"><i class="fa fa-pencil"></i></button>';
        html += '<button class="btn btn-xs btn-danger section-delete-btn" title="Delete"><i class="fa fa-trash"></i></button>';
        html += '</div>';
        html += '</div>';
        html += '<div class="section-content">' + section.content + '</div>';
        html += '</div>';
        
        return html;
    }
    
    function initSectionEvents() {
        // Edit button click
        $('.section-edit-btn').on('click', function(e) {
            e.preventDefault();
            var sectionId = $(this).closest('.section-item').data('section-id');
            activateEditorForSection(sectionId);
        });
        
        // Delete button click
        $('.section-delete-btn').on('click', function(e) {
            e.preventDefault();
            var sectionId = $(this).closest('.section-item').data('section-id');
            if (confirm('Are you sure you want to delete this section?')) {
                removeSection(sectionId);
            }
        });
    }
    
    function activateEditorForSection(sectionId) {
        // Tìm section trong danh sách
        var section = sections.find(function(s) {
            return s.id === sectionId;
        });
        
        if (!section) return;
        
        // Set active section
        activeSection = sectionId;
        
        // Hiển thị editor
        showSectionEditor(section);
    }
    
    function showSectionEditor(section) {
        // Tạo modal editor
        var modalHtml = '<div class="modal fade" id="section-editor-modal">';
        modalHtml += '<div class="modal-dialog modal-lg">';
        modalHtml += '<div class="modal-content">';
        modalHtml += '<div class="modal-header">';
        modalHtml += '<button type="button" class="close" data-dismiss="modal">&times;</button>';
        modalHtml += '<h4 class="modal-title">Edit Section: ' + (section.title || 'Section ' + section.position) + '</h4>';
        modalHtml += '</div>';
        modalHtml += '<div class="modal-body">';
        modalHtml += '<textarea id="section-editor-content" class="form-control">' + section.content + '</textarea>';
        modalHtml += '</div>';
        modalHtml += '<div class="modal-footer">';
        modalHtml += '<button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>';
        modalHtml += '<button type="button" class="btn btn-primary" id="save-section-btn">Save</button>';
        modalHtml += '</div>';
        modalHtml += '</div>';
        modalHtml += '</div>';
        modalHtml += '</div>';
        
        // Thêm modal vào body
        $('body').append(modalHtml);
        
        // Khởi tạo TinyMCE
        tinymce.init({
            selector: '#section-editor-content',
            height: 400,
            plugins: 'advlist autolink lists link image charmap preview anchor searchreplace visualblocks code fullscreen insertdatetime media table paste code help wordcount',
            toolbar: 'undo redo | formatselect | bold italic underline strikethrough | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image | removeformat | help'
        });
        
        // Hiển thị modal
        $('#section-editor-modal').modal('show');
        
        // Xử lý sự kiện lưu
        $('#save-section-btn').on('click', function() {
            var content = tinymce.get('section-editor-content').getContent();
            editSection(section.id, content);
            $('#section-editor-modal').modal('hide');
        });
        
        // Xử lý sự kiện đóng modal
        $('#section-editor-modal').on('hidden.bs.modal', function() {
            // Xóa editor
            tinymce.remove('#section-editor-content');
            // Xóa modal
            $(this).remove();
        });
    }
    
    function bindEvents() {
        // Global events
    }
    
    return {
        init: init
    };
})();
```

### 1.3. Cập nhật Processor để hỗ trợ Chunking

Cập nhật UltimateEditorProcessor để xử lý content chunking:

```php
/**
 * Xử lý lưu dữ liệu phân tán
 */
private function process_chunked_content($topic_id, $content_data) {
    // Kiểm tra xem nội dung có cần chia nhỏ không
    if (strlen($content_data['content']) > 50000) { // Khoảng 50KB
        $chunks = $this->split_content_into_chunks($content_data['content']);
        
        // Lưu metadata
        $metadata = [
            'title' => $content_data['title'],
            'description' => $content_data['description'],
            'version' => $content_data['version'] ?? 1,
            'total_chunks' => count($chunks),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'author_id' => get_staff_user_id()
        ];
        
        // Lưu metadata vào database
        $this->CI->db->where('id', $topic_id);
        $this->CI->db->update(db_prefix() . 'topics', [
            'metadata' => json_encode($metadata)
        ]);
        
        // Lưu từng chunk
        foreach ($chunks as $index => $chunk) {
            $chunk_id = 'chunk_' . $topic_id . '_' . $index;
            
            // Lưu chunk vào database hoặc storage
            $this->save_content_chunk($topic_id, $chunk_id, $chunk, $index);
        }
        
        return true;
    } else {
        // Nội dung nhỏ, lưu trực tiếp
        return $this->save_regular_content($topic_id, $content_data);
    }
}

/**
 * Chia nội dung thành các phần nhỏ
 */
private function split_content_into_chunks($content) {
    $chunks = [];
    
    // Tìm các thẻ heading để chia theo cấu trúc
    preg_match_all('/<h[1-6][^>]*>.*?<\/h[1-6]>|<p>.*?<\/p>|<div[^>]*>.*?<\/div>/si', $content, $matches);
    
    if (!empty($matches[0])) {
        $current_chunk = '';
        $chunk_size = 0;
        $max_chunk_size = 10000; // Khoảng 10KB mỗi chunk
        
        foreach ($matches[0] as $element) {
            $element_size = strlen($element);
            
            // Nếu phần tử lớn hơn max_chunk_size, chia nhỏ hơn nữa
            if ($element_size > $max_chunk_size) {
                // Thêm chunk hiện tại nếu có
                if ($chunk_size > 0) {
                    $chunks[] = $current_chunk;
                    $current_chunk = '';
                    $chunk_size = 0;
                }
                
                // Chia phần tử lớn thành nhiều phần
                $sub_elements = $this->split_large_element($element);
                foreach ($sub_elements as $sub_element) {
                    $chunks[] = $sub_element;
                }
            }
            // Nếu thêm phần tử mới sẽ vượt quá max_chunk_size, tạo chunk mới
            elseif ($chunk_size + $element_size > $max_chunk_size) {
                $chunks[] = $current_chunk;
                $current_chunk = $element;
                $chunk_size = $element_size;
            }
            // Ngược lại, thêm vào chunk hiện tại
            else {
                $current_chunk .= $element;
                $chunk_size += $element_size;
            }
        }
        
        // Thêm chunk cuối cùng nếu có
        if ($chunk_size > 0) {
            $chunks[] = $current_chunk;
        }
    } else {
        // Nếu không tìm thấy cấu trúc HTML, chia theo độ dài
        $chunks = str_split($content, 10000);
    }
    
    return $chunks;
}

/**
 * Lưu một chunk nội dung
 */
private function save_content_chunk($topic_id, $chunk_id, $chunk_content, $position) {
    // Xác định loại chunk
    $type = 'text';
    if (preg_match('/<h[1-6][^>]*>/i', $chunk_content)) {
        $type = 'heading';
    } elseif (preg_match('/<img/i', $chunk_content)) {
        $type = 'image';
    }
    
    // Tạo dữ liệu chunk
    $chunk_data = [
        'topic_id' => $topic_id,
        'chunk_id' => $chunk_id,
        'content' => $chunk_content,
        'type' => $type,
        'position' => $position,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    // Lưu vào database
    $this->CI->db->insert(db_prefix() . 'topic_content_chunks', $chunk_data);
    
    return $this->CI->db->insert_id();
}
```

## 2. Tích hợp SyncLogMethod với Ultimate Editor

### 2.1. Xây dựng Sync Manager cho Ultimate Editor

```php
class UltimateEditorSyncManager {
    private $CI;
    private $topic_id;
    private $session_id;
    
    public function __construct($topic_id = null) {
        $this->CI = &get_instance();
        $this->CI->load->model('Topic_sync_log_model');
        
        if ($topic_id) {
            $this->topic_id = $topic_id;
        }
    }
    
    /**
     * Bắt đầu phiên xuất bản mới
     */
    public function start_publishing_session($controller_id, $data = []) {
        // Khởi tạo thông tin session
        $summary_data = [
            'status' => 'in_progress',
            'start_time' => date('Y-m-d H:i:s'),
            'controller_id' => $controller_id,
            'platform' => $data['platform'] ?? '',
            'total_chunks' => $data['total_chunks'] ?? 0,
            'chunks_processed' => 0,
            'success_count' => 0,
            'error_count' => 0
        ];
        
        // Tạo session mới
        $this->session_id = $this->CI->Topic_sync_log_model->create_session(
            $controller_id,
            'ultimate_editor_publish',
            $summary_data
        );
        
        // Cập nhật controller với session_id
        $this->CI->db->where('id', $controller_id);
        $this->CI->db->update(db_prefix() . 'topic_controllers', [
            'publishing_session_id' => $this->session_id
        ]);
        
        return $this->session_id;
    }
    
    /**
     * Cập nhật tiến trình xuất bản
     */
    public function update_publishing_progress($chunk_index, $success = true, $message = '') {
        if (!$this->session_id) {
            return false;
        }
        
        // Lấy thông tin session hiện tại
        $session = $this->CI->Topic_sync_log_model->get_session($this->session_id);
        if (!$session) {
            return false;
        }
        
        // Cập nhật thông tin
        $summary_data = json_decode($session->summary_data, true);
        $summary_data['chunks_processed'] = $chunk_index + 1;
        
        if ($success) {
            $summary_data['success_count']++;
        } else {
            $summary_data['error_count']++;
        }
        
        $summary_data['progress_percentage'] = ($summary_data['chunks_processed'] / $summary_data['total_chunks']) * 100;
        $summary_data['last_update'] = date('Y-m-d H:i:s');
        
        // Tạo log entry
        $log_entry = [
            'message' => $success ? 'Processed chunk ' . ($chunk_index + 1) : 'Error processing chunk ' . ($chunk_index + 1),
            'type' => $success ? 'info' : 'error',
            'details' => [
                'chunk_index' => $chunk_index,
                'time' => date('Y-m-d H:i:s'),
                'message' => $message
            ]
        ];
        
        // Cập nhật session
        return $this->CI->Topic_sync_log_model->update_session(
            $this->session_id,
            $summary_data,
            $log_entry
        );
    }
    
    /**
     * Hoàn thành phiên xuất bản
     */
    public function complete_publishing_session($success = true, $message = '') {
        if (!$this->session_id) {
            return false;
        }
        
        // Cập nhật trạng thái
        $status = $success ? 'completed' : 'failed';
        
        // Hoàn thành session
        return $this->CI->Topic_sync_log_model->complete_session(
            $this->session_id,
            $status,
            [
                'end_time' => date('Y-m-d H:i:s'),
                'completion_message' => $message
            ]
        );
    }
    
    /**
     * Phục hồi phiên xuất bản
     */
    public function resume_publishing_session($session_id) {
        $this->session_id = $session_id;
        
        // Lấy thông tin session
        $session = $this->CI->Topic_sync_log_model->get_session($session_id);
        if (!$session || ($session->status !== 'in_progress' && $session->status !== 'interrupted')) {
            return false;
        }
        
        // Cập nhật thông tin
        $summary_data = json_decode($session->summary_data, true);
        $summary_data['status'] = 'in_progress';
        $summary_data['resume_time'] = date('Y-m-d H:i:s');
        
        // Tạo log entry
        $log_entry = [
            'message' => 'Resumed publishing session',
            'type' => 'info',
            'details' => [
                'time' => date('Y-m-d H:i:s'),
                'chunks_processed' => $summary_data['chunks_processed'],
                'remaining_chunks' => $summary_data['total_chunks'] - $summary_data['chunks_processed']
            ]
        ];
        
        // Cập nhật session
        return $this->CI->Topic_sync_log_model->update_session(
            $session_id,
            $summary_data,
            $log_entry
        );
    }
    
    /**
     * Lấy thông tin tiến trình xuất bản
     */
    public function get_publishing_progress($session_id = null) {
        $session_id = $session_id ?: $this->session_id;
        
        if (!$session_id) {
            return false;
        }
        
        // Lấy thông tin session
        $session = $this->CI->Topic_sync_log_model->get_session($session_id);
        if (!$session) {
            return false;
        }
        
        // Trả về thông tin
        $summary_data = json_decode($session->summary_data, true);
        $log_data = json_decode($session->log_data, true);
        
        return [
            'session_id' => $session_id,
            'status' => $session->status,
            'summary' => $summary_data,
            'logs' => $log_data
        ];
    }
}
```

### 2.2. Tích hợp với JavaScript để hiển thị tiến trình

```javascript
// publish.js
window.UltimateEditor.publish = (function() {
    var publishingSessionId = null;
    var pollingInterval = null;
    var publishingStatus = 'idle'; // idle, in_progress, completed, failed
    
    function startPublishing(topicId, controllerId) {
        var content = window.UltimateEditor.getContent();
        
        // Hiển thị dialog tiến trình
        showPublishingProgressDialog();
        
        // Bắt đầu xuất bản
        $.ajax({
            url: admin_url + 'ultimate_editor/start_publishing',
            type: 'POST',
            data: {
                topic_id: topicId,
                controller_id: controllerId,
                content: JSON.stringify(content)
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    publishingSessionId = response.session_id;
                    publishingStatus = 'in_progress';
                    
                    // Cập nhật UI
                    updatePublishingProgress(response.progress);
                    
                    // Bắt đầu polling
                    startProgressPolling(topicId);
                } else {
                    publishingStatus = 'failed';
                    showPublishingError(response.message);
                }
            },
            error: function(xhr, status, error) {
                publishingStatus = 'failed';
                showPublishingError('Error starting publishing: ' + error);
            }
        });
    }
    
    function startProgressPolling(topicId) {
        // Dừng polling hiện tại nếu có
        stopProgressPolling();
        
        // Khởi tạo polling mới
        pollingInterval = setInterval(function() {
            checkPublishingProgress(topicId);
        }, 2000); // 2 giây
    }
    
    function stopProgressPolling() {
        if (pollingInterval) {
            clearInterval(pollingInterval);
            pollingInterval = null;
        }
    }
    
    function checkPublishingProgress(topicId) {
        if (!publishingSessionId) return;
        
        $.ajax({
            url: admin_url + 'ultimate_editor/check_publishing_progress',
            type: 'POST',
            data: {
                topic_id: topicId,
                session_id: publishingSessionId
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    updatePublishingProgress(response.progress);
                    
                    // Kiểm tra trạng thái
                    if (response.progress.status === 'completed') {
                        publishingStatus = 'completed';
                        stopProgressPolling();
                        showPublishingComplete(response.progress);
                    } else if (response.progress.status === 'failed') {
                        publishingStatus = 'failed';
                        stopProgressPolling();
                        showPublishingError('Publishing failed: ' + response.progress.summary.completion_message);
                    }
                } else {
                    // Có lỗi khi kiểm tra tiến trình
                    console.error('Error checking progress:', response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error checking publishing progress:', error);
            }
        });
    }
    
    function resumePublishing(topicId, sessionId) {
        $.ajax({
            url: admin_url + 'ultimate_editor/resume_publishing',
            type: 'POST',
            data: {
                topic_id: topicId,
                session_id: sessionId
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    publishingSessionId = sessionId;
                    publishingStatus = 'in_progress';
                    
                    // Hiển thị dialog tiến trình
                    showPublishingProgressDialog();
                    
                    // Cập nhật UI
                    updatePublishingProgress(response.progress);
                    
                    // Bắt đầu polling
                    startProgressPolling(topicId);
                } else {
                    alert_float('danger', response.message);
                }
            },
            error: function(xhr, status, error) {
                alert_float('danger', 'Error resuming publishing: ' + error);
            }
        });
    }
    
    function showPublishingProgressDialog() {
        // Hiển thị dialog tiến trình
        var html = '<div class="modal fade" id="publishing-progress-modal" tabindex="-1" role="dialog">';
        html += '<div class="modal-dialog" role="document">';
        html += '<div class="modal-content">';
        html += '<div class="modal-header">';
        html += '<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>';
        html += '<h4 class="modal-title">Publishing Progress</h4>';
        html += '</div>';
        html += '<div class="modal-body">';
        html += '<div class="progress-container">';
        html += '<div class="progress">';
        html += '<div class="progress-bar progress-bar-success" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>';
        html += '</div>';
        html += '<div class="progress-status">Initializing...</div>';
        html += '</div>';
        html += '<div class="progress-logs mtop15">';
        html += '<h5>Activity Log</h5>';
        html += '<div class="progress-log-container"></div>';
        html += '</div>';
        html += '</div>';
        html += '<div class="modal-footer">';
        html += '<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>';
        html += '<button type="button" class="btn btn-danger" id="cancel-publishing-btn">Cancel Publishing</button>';
        html += '</div>';
        html += '</div>';
        html += '</div>';
        html += '</div>';
        
        // Thêm modal vào body
        $('body').append(html);
        
        // Hiển thị modal
        $('#publishing-progress-modal').modal({
            backdrop: 'static',
            keyboard: false
        });
        
        // Xử lý sự kiện hủy xuất bản
        $('#cancel-publishing-btn').on('click', function() {
            if (confirm('Are you sure you want to cancel the publishing process?')) {
                cancelPublishing();
            }
        });
    }
    
    function updatePublishingProgress(progress) {
        if (!progress) return;
        
        var percentage = progress.summary.progress_percentage || 0;
        percentage = Math.round(percentage);
        
        // Cập nhật progress bar
        $('.progress-bar').css('width', percentage + '%').attr('aria-valuenow', percentage).text(percentage + '%');
        
        // Cập nhật trạng thái
        var statusText = 'Processing...';
        if (progress.status === 'completed') {
            statusText = 'Publishing completed successfully!';
        } else if (progress.status === 'failed') {
            statusText = 'Publishing failed: ' + (progress.summary.completion_message || '');
        } else {
            statusText = 'Processed ' + progress.summary.chunks_processed + ' of ' + progress.summary.total_chunks + ' chunks';
        }
        
        $('.progress-status').text(statusText);
        
        // Cập nhật logs
        if (progress.logs && progress.logs.length) {
            var logsHtml = '';
            
            // Hiển thị 10 log gần nhất
            var recentLogs = progress.logs.slice(-10);
            
            recentLogs.forEach(function(log) {
                var logClass = log.type === 'error' ? 'text-danger' : log.type === 'warning' ? 'text-warning' : 'text-info';
                logsHtml += '<div class="log-entry ' + logClass + '">';
                logsHtml += '<span class="log-time">' + (log.details.time || '') + '</span> ';
                logsHtml += '<span class="log-message">' + log.message + '</span>';
                logsHtml += '</div>';
            });
            
            $('.progress-log-container').html(logsHtml);
        }
    }
    
    function showPublishingComplete(progress) {
        // Cập nhật UI để hiển thị hoàn thành
        $('.progress-bar').addClass('progress-bar-success').removeClass('progress-bar-striped active');
        
        // Thay đổi nút Cancel thành View
        $('#cancel-publishing-btn').text('View Result').removeClass('btn-danger').addClass('btn-success').off('click').on('click', function() {
            // Mở trang kết quả
            if (progress.summary.result_url) {
                window.open(progress.summary.result_url, '_blank');
            }
        });
        
        // Dừng polling
        stopProgressPolling();
        
        // Hiển thị thông báo
        alert_float('success', 'Publishing completed successfully!');
    }
    
    function showPublishingError(message) {
        // Cập nhật UI để hiển thị lỗi
        $('.progress-bar').addClass('progress-bar-danger').removeClass('progress-bar-striped active');
        $('.progress-status').addClass('text-danger').text('Error: ' + message);
        
        // Dừng polling
        stopProgressPolling();
        
        // Thay đổi nút Cancel thành Try Again
        $('#cancel-publishing-btn').text('Try Again').removeClass('btn-danger').addClass('btn-warning');
        
        // Hiển thị thông báo
        alert_float('danger', 'Publishing failed: ' + message);
    }
    
    function cancelPublishing() {
        if (!publishingSessionId) return;
        
        $.ajax({
            url: admin_url + 'ultimate_editor/cancel_publishing',
            type: 'POST',
            data: {
                session_id: publishingSessionId
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    publishingStatus = 'idle';
                    stopProgressPolling();
                    $('#publishing-progress-modal').modal('hide');
                    alert_float('success', 'Publishing cancelled successfully.');
                } else {
                    alert_float('warning', response.message);
                }
            },
            error: function(xhr, status, error) {
                alert_float('danger', 'Error cancelling publishing: ' + error);
            }
        });
    }
    
    return {
        startPublishing: startPublishing,
        resumePublishing: resumePublishing,
        cancelPublishing: cancelPublishing,
        getPublishingStatus: function() { return publishingStatus; }
    };
})();
```

## 3. Tích hợp Tags Management từ ChunkingMemory.md

### 3.1. Triển khai Tab Tags trong Ultimate Editor

```php
/**
 * Tab Tags cho Ultimate Editor
 */
private function render_tags_tab() {
    $html = '<div class="tab-pane" id="editor_tags">
        <div class="row mtop15">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-8">
                                <h4 class="no-margin">' . _l('topic_keywords_and_tags') . '</h4>
                            </div>
                            <div class="col-md-4 text-right">
                                <button type="button" class="btn btn-info" id="refresh_tags">
                                    <i class="fa fa-refresh"></i> ' . _l('refresh') . '
                                </button>
                                <button type="button" class="btn btn-success" id="save_tags_state">
                                    <i class="fa fa-save"></i> ' . _l('save') . '
                                </button>
                            </div>
                        </div>
                        <hr class="hr-panel-separator" />
                        
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <input type="text" class="form-control" id="tags_search" placeholder="' . _l('search_tags') . '">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-12">
                                <div id="tags_container" class="tags-container">
                                    <div class="alert alert-info mtop15">
                                        ' . _l('loading_data') . '
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- SEO Impact Section -->
                        <div class="row mtop20">
                            <div class="col-md-12">
                                <h4>' . _l('seo_impact') . '</h4>
                                <div id="seo_impact_container">
                                    <div class="alert alert-info">
                                        ' . _l('select_tag_to_view_impact') . '
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>';
    
    return $html;
}
```

### 3.2 JavaScript xử lý Tags cho Ultimate Editor

```javascript
// tags.js
window.UltimateEditor.tags = (function() {
    var selectedTags = [];
    var availableTags = [];
    
    function init() {
        // Khởi tạo tags tab
        initializeTagsTab();
        
        // Khởi tạo đề xuất tags từ nội dung
        initializeSuggestionSystem();
    }
    
    function initializeTagsTab() {
        // Load tags khi tab được kích hoạt
        $('a[href="#editor_tags"]').on('click', function() {
            loadTags();
        });
        
        // Refresh tags button
        $('#refresh_tags').on('click', function() {
            loadTags(true); // Force refresh
        });
        
        // Save tags state button
        $('#save_tags_state').on('click', function() {
            saveTagsState();
        });
        
        // Initialize search
        initializeTagSearch();
    }
    
    function loadTags(forceRefresh) {
        var topicId = window.UltimateEditor.getTopicId();
        var controllerId = $('#controller-select').val();
        
        if (!topicId) {
            alert_float('danger', 'Topic ID not found');
            return;
        }
        
        $('#tags_container').html('<div class="alert alert-info mtop15">' + app.lang.loading_data + '</div>');
        
        $.ajax({
            url: admin_url + 'ultimate_editor/get_tags',
            type: 'POST',
            data: {
                topic_id: topicId,
                controller_id: controllerId,
                refresh: forceRefresh
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    availableTags = response.tags;
                    renderTags(response.tags);
                    
                    // Kiểm tra nếu có từ khóa được đề xuất từ nội dung
                    analyzeContentForTags();
                } else {
                    $('#tags_container').html('<div class="alert alert-danger mtop15">' + response.message + '</div>');
                }
            },
            error: function(xhr, status, error) {
                $('#tags_container').html('<div class="alert alert-danger mtop15">Error loading tags: ' + error + '</div>');
            }
        });
    }
    
    function renderTags(tags) {
        if (!tags || tags.length === 0) {
            $('#tags_container').html('<div class="alert alert-warning mtop15">' + app.lang.no_tags_found + '</div>');
            return;
        }
        
        // Nhóm tags theo phân loại
        var tagGroups = {
            'keyword': [],
            'category': [],
            'general': []
        };
        
        // Phân loại tags
        $.each(tags, function(index, tag) {
            if (tag.type === 'keyword') {
                tagGroups.keyword.push(tag);
            } else if (tag.type === 'category') {
                tagGroups.category.push(tag);
            } else {
                tagGroups.general.push(tag);
            }
        });
        
        var html = '';
        
        // Render Keywords
        if (tagGroups.keyword.length > 0) {
            html += '<div class="tag-group"><h5>' + app.lang.keywords + '</h5><div class="tags-list keyword-tags">';
            $.each(tagGroups.keyword, function(index, tag) {
                html += createTagItemHtml(tag);
            });
            html += '</div></div>';
        }
        
        // Render Categories
        if (tagGroups.category.length > 0) {
            html += '<div class="tag-group"><h5>' + app.lang.categories + '</h5><div class="tags-list category-tags">';
            $.each(tagGroups.category, function(index, tag) {
                html += createTagItemHtml(tag);
            });
            html += '</div></div>';
        }
        
        // Render General Tags
        if (tagGroups.general.length > 0) {
            html += '<div class="tag-group"><h5>' + app.lang.general_tags + '</h5><div class="tags-list general-tags">';
            $.each(tagGroups.general, function(index, tag) {
                html += createTagItemHtml(tag);
            });
            html += '</div></div>';
        }
        
        $('#tags_container').html(html);
        
        // Initialize tag click handlers
        initializeTagHandlers();
        
        // Restore selected tags
        restoreSelectedTags();
    }
    
    function createTagItemHtml(tag) {
        var lastSyncText = '';
        if (tag.last_sync) {
            var lastSyncDate = new Date(tag.last_sync);
            lastSyncText = ' <small class="text-muted">(Last sync: ' + lastSyncDate.toLocaleString() + ')</small>';
        }
        
        var countText = '';
        if (tag.count !== undefined) {
            countText = ' <span class="badge">' + tag.count + '</span>';
        }
        
        var relevanceClass = '';
        if (tag.relevance) {
            relevanceClass = tag.relevance > 80 ? 'high-relevance' : 
                            tag.relevance > 50 ? 'medium-relevance' : 'low-relevance';
        }
        
        var html = '<div class="tag-item ' + relevanceClass + '" data-tag-id="' + tag.tag_id + '" data-tag-name="' + tag.name + '" data-tag-type="' + tag.type + '">';
        html += '<span class="tag-badge">' + tag.name + countText + lastSyncText + '</span>';
        if (tag.relevance) {
            html += '<span class="tag-relevance" title="Relevance score: ' + tag.relevance + '%">' + tag.relevance + '%</span>';
        }
        html += '</div>';
        
        return html;
    }
    
    function initializeTagHandlers() {
        // Tag click handler
        $('.tag-badge').on('click', function() {
            var tagItem = $(this).closest('.tag-item');
            var tagId = tagItem.data('tag-id');
            var tagName = tagItem.data('tag-name');
            var tagType = tagItem.data('tag-type');
            
            // Toggle selection
            if (tagItem.hasClass('selected')) {
                tagItem.removeClass('selected');
                // Remove from selected tags
                removeSelectedTag(tagId);
                // Update SEO impact
                updateSeoImpact();
            } else {
                tagItem.addClass('selected');
                // Add to selected tags
                addSelectedTag(tagId, tagName, tagType);
                // Update SEO impact
                updateSeoImpact();
            }
        });
    }
    
    function addSelectedTag(tagId, tagName, tagType) {
        // Check if already exists
        var exists = selectedTags.some(function(tag) {
            return tag.id === tagId;
        });
        
        if (!exists) {
            selectedTags.push({
                id: tagId,
                name: tagName,
                type: tagType
            });
            
            // Update keyword list in main editor
            window.UltimateEditor.keywords.addKeyword(tagName);
            
            // Update UI
            updateSelectedTagsUI();
        }
    }
    
    function removeSelectedTag(tagId) {
        var removedTag = selectedTags.find(function(tag) {
            return tag.id === tagId;
        });
        
        if (removedTag) {
            // Remove from selected tags
            selectedTags = selectedTags.filter(function(tag) {
                return tag.id !== tagId;
            });
            
            // Remove from keywords in main editor
            window.UltimateEditor.keywords.removeKeyword(removedTag.name);
            
            // Update UI
            updateSelectedTagsUI();
        }
    }
    
    function updateSelectedTagsUI() {
        // Update tag counter
        var keywordCount = selectedTags.filter(function(tag) {
            return tag.type === 'keyword';
        }).length;
        
        var categoryCount = selectedTags.filter(function(tag) {
            return tag.type === 'category';
        }).length;
        
        var generalCount = selectedTags.filter(function(tag) {
            return tag.type !== 'keyword' && tag.type !== 'category';
        }).length;
        
        // Update tab badge
        var totalCount = selectedTags.length;
        $('a[href="#editor_tags"] .tag-count').text(totalCount > 0 ? totalCount : '');
    }
    
    function initializeTagSearch() {
        $('#tags_search').on('keyup', function() {
            var searchTerm = $(this).val().toLowerCase();
            
            if (searchTerm === '') {
                // Show all tags
                $('.tag-item').show();
                return;
            }
            
            // Filter tags
            $('.tag-item').each(function() {
                var tagName = $(this).data('tag-name').toLowerCase();
                
                if (tagName.indexOf(searchTerm) > -1) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        });
    }
    
    function saveTagsState() {
        var topicId = window.UltimateEditor.getTopicId();
        
        if (!topicId) {
            alert_float('danger', 'Topic ID not found');
            return;
        }
        
        $.ajax({
            url: admin_url + 'ultimate_editor/save_tags',
            type: 'POST',
            data: {
                topic_id: topicId,
                selected_tags: JSON.stringify(selectedTags)
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert_float('success', response.message);
                } else {
                    alert_float('danger', response.message);
                }
            },
            error: function(xhr, status, error) {
                alert_float('danger', 'Error saving tags: ' + error);
            }
        });
    }
    
    function analyzeContentForTags() {
        var content = window.UltimateEditor.getContent();
        if (!content || !content.content) return;
        
        // Phân tích nội dung để tìm từ khóa
        $.ajax({
            url: admin_url + 'ultimate_editor/analyze_content_for_tags',
            type: 'POST',
            data: {
                content: content.content,
                title: content.title,
                description: content.description
            },
            dataType: 'json',
            success: function(response) {
                if (response.success && response.suggested_tags) {
                    // Đánh dấu các tag được đề xuất
                    highlightSuggestedTags(response.suggested_tags);
                }
            }
        });
    }
    
    function highlightSuggestedTags(suggestedTags) {
        // Đánh dấu các tag được đề xuất trong danh sách
        $.each(suggestedTags, function(index, tag) {
            var $tagElement = $('.tag-item[data-tag-name="' + tag.name + '"]');
            if ($tagElement.length) {
                $tagElement.addClass('suggested-tag');
                // Thêm thông tin relevance
                $tagElement.attr('data-relevance', tag.relevance);
                $tagElement.find('.tag-relevance').text(tag.relevance + '%');
            }
        });
    }
    
    function updateSeoImpact() {
        if (selectedTags.length === 0) {
            $('#seo_impact_container').html('<div class="alert alert-info">' + app.lang.select_tag_to_view_impact + '</div>');
            return;
        }
        
        // Phân tích tác động SEO của các tag đã chọn
        var content = window.UltimateEditor.getContent();
        
        $.ajax({
            url: admin_url + 'ultimate_editor/analyze_seo_impact',
            type: 'POST',
            data: {
                content: content.content,
                title: content.title,
                description: content.description,
                selected_tags: JSON.stringify(selectedTags)
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    renderSeoImpact(response.impact);
                } else {
                    $('#seo_impact_container').html('<div class="alert alert-danger">' + response.message + '</div>');
                }
            },
            error: function(xhr, status, error) {
                $('#seo_impact_container').html('<div class="alert alert-danger">Error analyzing SEO impact: ' + error + '</div>');
            }
        });
    }
    
    function renderSeoImpact(impact) {
        var html = '<div class="seo-impact-summary">';
        
        // Overall score
        html += '<div class="seo-score-container">';
        html += '<div class="seo-score ' + getSeoScoreClass(impact.overall_score) + '">' + impact.overall_score + '</div>';
        html += '<div class="seo-score-label">Overall SEO Score</div>';
        html += '</div>';
        
        // Impact details
        html += '<div class="seo-impact-details">';
        
        // Keyword density
        html += '<div class="seo-impact-item">';
        html += '<div class="impact-label">Keyword Density</div>';
        html += '<div class="impact-value ' + getImpactValueClass(impact.keyword_density.score) + '">' + impact.keyword_density.value + '%</div>';
        html += '<div class="impact-description">' + impact.keyword_density.message + '</div>';
        html += '</div>';
        
        // Title optimization
        html += '<div class="seo-impact-item">';
        html += '<div class="impact-label">Title Optimization</div>';
        html += '<div class="impact-value ' + getImpactValueClass(impact.title_optimization.score) + '">' + impact.title_optimization.score + '/100</div>';
        html += '<div class="impact-description">' + impact.title_optimization.message + '</div>';
        html += '</div>';
        
        // Description optimization
        html += '<div class="seo-impact-item">';
        html += '<div class="impact-label">Description Optimization</div>';
        html += '<div class="impact-value ' + getImpactValueClass(impact.description_optimization.score) + '">' + impact.description_optimization.score + '/100</div>';
        html += '<div class="impact-description">' + impact.description_optimization.message + '</div>';
        html += '</div>';
        
        // Content structure
        html += '<div class="seo-impact-item">';
        html += '<div class="impact-label">Content Structure</div>';
        html += '<div class="impact-value ' + getImpactValueClass(impact.content_structure.score) + '">' + impact.content_structure.score + '/100</div>';
        html += '<div class="impact-description">' + impact.content_structure.message + '</div>';
        html += '</div>';
        
        html += '</div>'; // End of seo-impact-details
        html += '</div>'; // End of seo-impact-summary
        
        // Suggestions
        if (impact.suggestions && impact.suggestions.length > 0) {
            html += '<div class="seo-suggestions">';
            html += '<h5>Improvement Suggestions</h5>';
            html += '<ul>';
            
            $.each(impact.suggestions, function(index, suggestion) {
                html += '<li class="' + suggestion.priority + '-priority">' + suggestion.message + '</li>';
            });
            
            html += '</ul>';
            html += '</div>';
        }
        
        $('#seo_impact_container').html(html);
    }
    
    function getSeoScoreClass(score) {
        if (score >= 80) return 'score-excellent';
        if (score >= 60) return 'score-good';
        if (score >= 40) return 'score-average';
        return 'score-poor';
    }
    
    function getImpactValueClass(score) {
        if (score >= 80) return 'impact-excellent';
        if (score >= 60) return 'impact-good';
        if (score >= 40) return 'impact-average';
        return 'impact-poor';
    }
    
    function restoreSelectedTags() {
        // Restore selected tags UI
        $.each(selectedTags, function(index, tag) {
            $('.tag-item[data-tag-id="' + tag.id + '"]').addClass('selected');
        });
    }
    
    function getSelectedTags() {
        return selectedTags;
    }
    
    return {
        init: init,
        loadTags: loadTags,
        addSelectedTag: addSelectedTag,
        removeSelectedTag: removeSelectedTag,
        getSelectedTags: getSelectedTags,
        analyzeContentForTags: analyzeContentForTags,
        updateSeoImpact: updateSeoImpact
    };
})();
```

### 3.3 Thiết kế CSS cho Tags Management

```css
/* Tags styling */
.tags-container {
    margin-top: 15px;
}

.tag-group {
    margin-bottom: 20px;
}

.tag-group h5 {
    margin-bottom: 10px;
    font-weight: 600;
    border-bottom: 1px solid #eee;
    padding-bottom: 5px;
}

.tags-list {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 15px;
}

.tag-item {
    display: inline-block;
    margin-bottom: 10px;
    transition: all 0.3s ease;
    position: relative;
}

.tag-badge {
    display: inline-block;
    padding: 6px 12px;
    background-color: #f5f5f5;
    border-radius: 30px;
    color: #333;
    font-size: 13px;
    cursor: pointer;
    transition: all 0.3s ease;
    border: 1px solid #ddd;
}

.tag-badge:hover {
    background-color: #e9e9e9;
    border-color: #ccc;
}

.tag-badge .badge {
    background-color: #03a9f4;
    margin-left: 5px;
}

.tag-item.selected .tag-badge {
    background-color: #03a9f4;
    color: #fff;
    border-color: #0286c2;
}

.tag-item.selected .tag-badge .badge {
    background-color: #fff;
    color: #03a9f4;
}

.tag-item.selected .tag-badge .text-muted {
    color: #e6e6e6;
}

.tag-relevance {
    position: absolute;
    top: -8px;
    right: -8px;
    background-color: #4caf50;
    color: white;
    border-radius: 50%;
    padding: 2px 5px;
    font-size: 10px;
    font-weight: bold;
}

.tag-item.high-relevance .tag-badge {
    border-color: #4caf50;
}

.tag-item.medium-relevance .tag-badge {
    border-color: #ff9800;
}

.tag-item.low-relevance .tag-badge {
    border-color: #f44336;
}

.tag-item.suggested-tag .tag-badge {
    border-style: dashed;
    border-width: 2px;
}

/* SEO Impact styling */
.seo-impact-summary {
    display: flex;
    margin-bottom: 20px;
}

.seo-score-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 15px;
    border-right: 1px solid #eee;
    min-width: 150px;
}

.seo-score {
    font-size: 36px;
    font-weight: bold;
    width: 70px;
    height: 70px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    margin-bottom: 10px;
}

.score-excellent {
    background-color: #4caf50;
    color: white;
}

.score-good {
    background-color: #8bc34a;
    color: white;
}

.score-average {
    background-color: #ff9800;
    color: white;
}

.score-poor {
    background-color: #f44336;
    color: white;
}

.seo-impact-details {
    flex: 1;
    padding: 15px;
}

.seo-impact-item {
    margin-bottom: 15px;
    border-bottom: 1px solid #f5f5f5;
    padding-bottom: 10px;
}

.impact-label {
    font-weight: 600;
    margin-bottom: 5px;
}

.impact-value {
    font-weight: bold;
    display: inline-block;
    padding: 2px 8px;
    border-radius: 4px;
    margin-bottom: 5px;
}

.impact-excellent {
    background-color: #e8f5e9;
    color: #2e7d32;
}

.impact-good {
    background-color: #f1f8e9;
    color: #558b2f;
}

.impact-average {
    background-color: #fff3e0;
    color: #ef6c00;
}

.impact-poor {
    background-color: #ffebee;
    color: #c62828;
}

.seo-suggestions {
    background-color: #f9f9f9;
    padding: 15px;
    border-radius: 4px;
}

.seo-suggestions h5 {
    margin-top: 0;
    margin-bottom: 10px;
    font-weight: 600;
}

.seo-suggestions ul {
    margin: 0;
    padding-left: 20px;
}

.seo-suggestions li {
    margin-bottom: 8px;
}

.high-priority {
    color: #c62828;
}

.medium-priority {
    color: #ef6c00;
}

.low-priority {
    color: #558b2f;
}
```

## 4. Cải thiện Feedback Visualizer cho SEO và Content Quality

### 4.1 Thêm visualizer cho phân tích SEO và chất lượng nội dung

```javascript
// seo_tools.js
window.UltimateEditor.seoTools = (function() {
    var analysisData = null;
    var contentData = {
        title: '',
        description: '',
        content: '',
        keywords: []
    };
    
    function init() {
        // Khởi tạo sự kiện
        initEvents();
        
        // Khởi tạo visualizer
        initVisualizer();
    }
    
    function initEvents() {
        // Cập nhật phân tích khi nội dung thay đổi
        window.UltimateEditor.addEventListener('contentChanged', function(data) {
            contentData = {
                title: data.title || '',
                description: data.description || '',
                content: data.content || '',
                keywords: window.UltimateEditor.keywords.getKeywords() || []
            };
            
            // Delay analysis to avoid too many requests
            debouncedAnalyzeContent();
        });
        
        // Cập nhật phân tích khi từ khóa thay đổi
        window.UltimateEditor.addEventListener('keywordsChanged', function(keywords) {
            contentData.keywords = keywords;
            debouncedAnalyzeContent();
        });
        
        // Nút phân tích thủ công
        $('#analyze-seo-btn').on('click', function() {
            analyzeContent();
        });
    }
    
    // Debounce function to limit API calls
    var debouncedAnalyzeContent = debounce(function() {
        analyzeContent();
    }, 1000);
    
    function debounce(func, wait) {
        var timeout;
        return function() {
            var context = this, args = arguments;
            clearTimeout(timeout);
            timeout = setTimeout(function() {
                func.apply(context, args);
            }, wait);
        };
    }
    
    function analyzeContent() {
        if (!contentData.content) return;
        
        // Hiển thị indicator loading
        showAnalysisLoading();
        
        $.ajax({
            url: admin_url + 'ultimate_editor/analyze_content',
            type: 'POST',
            data: contentData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    analysisData = response.data;
                    updateVisualizer(analysisData);
                } else {
                    showAnalysisError(response.message);
                }
            },
            error: function(xhr, status, error) {
                showAnalysisError('Error analyzing content: ' + error);
            }
        });
    }
    
    function initVisualizer() {
        // Tạo container cho visualizer
        var html = '<div class="seo-visualizer-container">';
        
        // SEO Score
        html += '<div class="seo-score-widget">';
        html += '<div class="widget-title">SEO Score</div>';
        html += '<div class="seo-score-circle" id="seo-score-circle">';
        html += '<div class="score-value">?</div>';
        html += '</div>';
        html += '<div class="score-label">Analyzing...</div>';
        html += '</div>';
        
        // Readability Score
        html += '<div class="readability-widget">';
        html += '<div class="widget-title">Readability</div>';
        html += '<div class="readability-meter" id="readability-meter">';
        html += '<div class="meter-value"></div>';
        html += '</div>';
        html += '<div class="readability-label">Analyzing...</div>';
        html += '</div>';
        
        // Main issues panel
        html += '<div class="issues-panel">';
        html += '<div class="panel-title">Issues <span class="issues-count">(0)</span></div>';
        html += '<div class="issues-list" id="issues-list">';
        html += '<div class="loading-issues">Analyzing content...</div>';
        html += '</div>';
        html += '</div>';
        
        html += '</div>'; // End container
        
        // Append to sidebar
        $('#seo-analysis-container').html(html);
    }
    
    function showAnalysisLoading() {
        $('.seo-score-circle .score-value').text('...');
        $('.score-label').text('Analyzing...');
        $('.readability-label').text('Analyzing...');
        $('#issues-list').html('<div class="loading-issues">Analyzing content...</div>');
    }
    
    function showAnalysisError(message) {
        $('.seo-score-circle .score-value').text('!');
        $('.score-label').text('Analysis failed');
        $('.readability-label').text('Analysis failed');
        $('#issues-list').html('<div class="analysis-error">' + message + '</div>');
    }
    
    function updateVisualizer(data) {
        // Update SEO Score
        $('.seo-score-circle').attr('class', 'seo-score-circle score-' + getScoreClass(data.seo_score));
        $('.seo-score-circle .score-value').text(data.seo_score);
        $('.score-label').text(getScoreLabel(data.seo_score));
        
        // Update Readability
        var readabilityClass = getReadabilityClass(data.readability.score);
        $('.readability-meter').attr('class', 'readability-meter ' + readabilityClass);
        $('.readability-meter .meter-value').css('width', data.readability.score + '%');
        $('.readability-label').text(data.readability.label);
        
        // Update Issues
        updateIssues(data.issues);
    }
    
    function updateIssues(issues) {
        if (!issues || issues.length === 0) {
            $('#issues-list').html('<div class="no-issues">No issues found. Great job!</div>');
            $('.issues-count').text('(0)');
            return;
        }
        
        // Sort issues by severity
        issues.sort(function(a, b) {
            var severityOrder = { 'critical': 0, 'error': 1, 'warning': 2, 'info': 3 };
            return severityOrder[a.severity] - severityOrder[b.severity];
        });
        
        var html = '';
        
        $.each(issues, function(index, issue) {
            html += '<div class="issue-item ' + issue.severity + '">';
            html += '<div class="issue-severity">' + getSeverityIcon(issue.severity) + '</div>';
            html += '<div class="issue-content">';
            html += '<div class="issue-title">' + issue.title + '</div>';
            html += '<div class="issue-description">' + issue.description + '</div>';
            
            if (issue.fix_suggestion) {
                html += '<div class="issue-suggestion">';
                html += '<span class="suggestion-label">Suggestion:</span> ';
                html += issue.fix_suggestion;
                
                if (issue.can_auto_fix) {
                    html += '<button class="btn btn-xs btn-primary auto-fix-btn" data-issue-id="' + issue.id + '">Auto Fix</button>';
                }
                
                html += '</div>';
            }
            
            html += '</div>'; // End issue-content
            html += '</div>'; // End issue-item
        });
        
        $('#issues-list').html(html);
        $('.issues-count').text('(' + issues.length + ')');
        
        // Initialize auto fix buttons
        initAutoFixButtons();
    }
    
    function initAutoFixButtons() {
        $('.auto-fix-btn').on('click', function() {
            var issueId = $(this).data('issue-id');
            autoFixIssue(issueId);
        });
    }
    
    function autoFixIssue(issueId) {
        // Find the issue
        var issue = analysisData.issues.find(function(issue) {
            return issue.id === issueId;
        });
        
        if (!issue || !issue.can_auto_fix) return;
        
        $.ajax({
            url: admin_url + 'ultimate_editor/auto_fix_issue',
            type: 'POST',
            data: {
                issue_id: issueId,
                content: contentData.content,
                title: contentData.title,
                description: contentData.description
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Apply the fix
                    if (response.data.fixed_title) {
                        $('#title-input').val(response.data.fixed_title);
                    }
                    
                    if (response.data.fixed_description) {
                        $('#description-input').val(response.data.fixed_description);
                    }
                    
                    if (response.data.fixed_content) {
                        tinymce.get('content-editor').setContent(response.data.fixed_content);
                    }
                    
                    // Trigger content changed event
                    window.UltimateEditor.triggerEvent('contentChanged', {
                        title: $('#title-input').val(),
                        description: $('#description-input').val(),
                        content: tinymce.get('content-editor').getContent()
                    });
                    
                    // Show success message
                    alert_float('success', 'Issue fixed successfully!');
                } else {
                    alert_float('warning', response.message);
                }
            },
            error: function(xhr, status, error) {
                alert_float('danger', 'Error fixing issue: ' + error);
            }
        });
    }
    
    function getScoreClass(score) {
        if (score >= 80) return 'excellent';
        if (score >= 60) return 'good';
        if (score >= 40) return 'average';
        return 'poor';
    }
    
    function getScoreLabel(score) {
        if (score >= 80) return 'Excellent';
        if (score >= 60) return 'Good';
        if (score >= 40) return 'Average';
        return 'Poor';
    }
    
    function getReadabilityClass(score) {
        if (score >= 80) return 'readability-excellent';
        if (score >= 60) return 'readability-good';
        if (score >= 40) return 'readability-average';
        return 'readability-poor';
    }
    
    function getSeverityIcon(severity) {
        switch (severity) {
            case 'critical':
                return '<i class="fa fa-times-circle"></i>';
            case 'error':
                return '<i class="fa fa-exclamation-circle"></i>';
            case 'warning':
                return '<i class="fa fa-exclamation-triangle"></i>';
            case 'info':
                return '<i class="fa fa-info-circle"></i>';
            default:
                return '<i class="fa fa-info-circle"></i>';
        }
    }
    
    return {
        init: init,
        analyzeContent: analyzeContent,
        getAnalysisData: function() { return analysisData; }
    };
})();
```

### 4.2 CSS cho SEO và Content Quality Visualizer (tiếp)

```css
.issue-item {
    padding: 10px 15px;
    border-bottom: 1px solid #eee;
    display: flex;
}

.issue-item:last-child {
    border-bottom: none;
}

.issue-severity {
    padding-right: 15px;
    display: flex;
    align-items: flex-start;
    padding-top: 3px;
}

.issue-content {
    flex: 1;
}

.issue-title {
    font-weight: 600;
    margin-bottom: 5px;
}

.issue-description {
    color: #666;
    margin-bottom: 10px;
}

.issue-suggestion {
    background-color: #f5f7f9;
    padding: 8px;
    border-radius: 4px;
    font-size: 13px;
    position: relative;
}

.suggestion-label {
    font-weight: 600;
    color: #555;
}

.auto-fix-btn {
    position: absolute;
    right: 8px;
    top: 6px;
}

.loading-issues, .no-issues, .analysis-error {
    padding: 15px;
    text-align: center;
    color: #666;
}

.no-issues {
    color: #4caf50;
}

.analysis-error {
    color: #f44336;
}

/* Severity styles */
.issue-item.critical .issue-severity i {
    color: #f44336;
    font-size: 18px;
}

.issue-item.error .issue-severity i {
    color: #ff5722;
    font-size: 16px;
}

.issue-item.warning .issue-severity i {
    color: #ff9800;
    font-size: 16px;
}

.issue-item.info .issue-severity i {
    color: #2196f3;
    font-size: 16px;
}
```

## 5. Tích hợp Publish History và Resume Publishing

### 5.1 Thêm Tab Publish History

```php
/**
 * Render publish history tab
 */
private function render_publish_history_tab() {
    $html = '<div class="tab-pane" id="publish_history">
        <div class="row mtop15">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-8">
                                <h4 class="no-margin">' . _l('publish_history') . '</h4>
                            </div>
                            <div class="col-md-4 text-right">
                                <button type="button" class="btn btn-info" id="refresh_publish_history">
                                    <i class="fa fa-refresh"></i> ' . _l('refresh') . '
                                </button>
                            </div>
                        </div>
                        <hr class="hr-panel-separator" />
                        
                        <div class="row">
                            <div class="col-md-12">
                                <div id="publish_history_container">
                                    <div class="alert alert-info mtop15">
                                        ' . _l('loading_data') . '
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>';
    
    return $html;
}
```

### 5.2 JavaScript cho Publish History và Resume

```javascript
// publish_history.js
window.UltimateEditor.publishHistory = (function() {
    function init() {
        // Khởi tạo tab history
        initializeHistoryTab();
    }
    
    function initializeHistoryTab() {
        // Load history khi tab được kích hoạt
        $('a[href="#publish_history"]').on('click', function() {
            loadPublishHistory();
        });
        
        // Refresh history button
        $('#refresh_publish_history').on('click', function() {
            loadPublishHistory();
        });
    }
    
    function loadPublishHistory() {
        var topicId = window.UltimateEditor.getTopicId();
        
        if (!topicId) {
            alert_float('danger', 'Topic ID not found');
            return;
        }
        
        $('#publish_history_container').html('<div class="alert alert-info mtop15">' + app.lang.loading_data + '</div>');
        
        $.ajax({
            url: admin_url + 'ultimate_editor/get_publish_history',
            type: 'POST',
            data: {
                topic_id: topicId
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    renderPublishHistory(response.history);
                } else {
                    $('#publish_history_container').html('<div class="alert alert-danger mtop15">' + response.message + '</div>');
                }
            },
            error: function(xhr, status, error) {
                $('#publish_history_container').html('<div class="alert alert-danger mtop15">Error loading publish history: ' + error + '</div>');
            }
        });
    }
    
    function renderPublishHistory(history) {
        if (!history || history.length === 0) {
            $('#publish_history_container').html('<div class="alert alert-warning mtop15">' + app.lang.no_publish_history_found + '</div>');
            return;
        }
        
        var html = '<div class="table-responsive"><table class="table table-striped table-publish-history">';
        html += '<thead><tr>';
        html += '<th>' + app.lang.date + '</th>';
        html += '<th>' + app.lang.platform + '</th>';
        html += '<th>' + app.lang.status + '</th>';
        html += '<th>' + app.lang.progress + '</th>';
        html += '<th>' + app.lang.actions + '</th>';
        html += '</tr></thead>';
        html += '<tbody>';
        
        $.each(history, function(index, item) {
            html += '<tr>';
            html += '<td>' + item.date + '</td>';
            html += '<td>' + item.platform + '</td>';
            html += '<td>' + item.status + '</td>';
            html += '<td>' + item.progress + '</td>';
            html += '<td>';
            html += '<button class="btn btn-xs btn-primary" data-issue-id="' + item.id + '">View</button>';
            html += '</td>';
            html += '</tr>';
        });
        
        html += '</tbody>';
        html += '</table>';
        html += '</div>';
        
        $('#publish_history_container').html(html);
    }
    
    return {
        init: init
    };
})();
```

### 5.3 CSS cho Publish History

```css
/* Publish History Styling */
.table-publish-history {
    margin-bottom: 0;
}

.table-publish-history th {
    background-color: #f5f5f5;
}

.table-publish-history .progress {
    margin-bottom: 0;
    height: 10px;
}

/* Publish Details Modal */
.publish-details-summary {
    margin-bottom: 20px;
}

.publish-details-summary h5 {
    font-weight: 600;
    margin-bottom: 15px;
    border-bottom: 1px solid #eee;
    padding-bottom: 5px;
}

.publish-details-progress {
    margin-bottom: 20px;
}

.publish-details-progress h5 {
    font-weight: 600;
    margin-bottom: 15px;
    border-bottom: 1px solid #eee;
    padding-bottom: 5px;
}

.publish-details-logs {
    margin-bottom: 20px;
}

.publish-details-logs h5 {
    font-weight: 600;
    margin-bottom: 15px;
    border-bottom: 1px solid #eee;
    padding-bottom: 5px;
}

.table-logs {
    font-size: 13px;
}

.table-logs th {
    background-color: #f5f5f5;
}
```

## 6. Áp dụng Chunking Memory và công cụ Section Editor nâng cao

### 6.1 Cải tiến Section Editor với hỗ trợ Chunking

```javascript
// section_editor_enhanced.js
window.UltimateEditor.sectionEditor = (function() {
    var sections = [];
    var originalSections = [];
    var activeSection = null;
    var container = null;
    var callbacks = {};
    var chunkingEnabled = true;
    var maxChunkSize = 10000; // 10KB
    
    function init(options) {
        container = options.container;
        
        if (options.onChange) {
            callbacks.onChange = options.onChange;
        }
        
        if (options.onSectionClick) {
            callbacks.onSectionClick = options.onSectionClick;
        }
        
        if (options.hasOwnProperty('chunkingEnabled')) {
            chunkingEnabled = options.chunkingEnabled;
        }
        
        if (options.maxChunkSize) {
            maxChunkSize = options.maxChunkSize;
        }
        
        // Khởi tạo sự kiện
        bindEvents();
        
        return {
            setSections: setSections,
            getSections: getSections,
            getOriginalSections: getOriginalSections,
            addSection: addSection,
            removeSection: removeSection,
            editSection: editSection,
            splitSection: splitSection,
            mergeSections: mergeSections,
            applyChunking: applyChunking,
            createContentFromSections: createContentFromSections
        };
    }
    
    function setSections(newSections) {
        sections = newSections;
        // Lưu bản sao của sections gốc để so sánh thay đổi
        originalSections = JSON.parse(JSON.stringify(newSections));
        renderSections();
    }
    
    function getSections() {
        return sections;
    }
    
    function getOriginalSections() {
        return originalSections;
    }
    
    function addSection(section, position) {
        if (position !== undefined && position >= 0 && position <= sections.length) {
            // Thêm vào vị trí cụ thể
            sections.splice(position, 0, section);
        } else {
            // Thêm vào cuối
            sections.push(section);
        }
        
        // Cập nhật position cho các section
        updateSectionPositions();
        
        renderSections();
        
        if (callbacks.onChange) {
            callbacks.onChange('add', section);
        }
    }
    
    function removeSection(sectionId) {
        var index = findSectionIndex(sectionId);
        
        if (index !== -1) {
            var removedSection = sections.splice(index, 1)[0];
            
            // Cập nhật position cho các section
            updateSectionPositions();
            
            renderSections();
            
            if (callbacks.onChange) {
                callbacks.onChange('remove', removedSection);
            }
        }
    }
    
    function editSection(sectionId, content, title) {
        var index = findSectionIndex(sectionId);
        
        if (index !== -1) {
            if (content !== undefined) {
                sections[index].content = content;
            }
            
            if (title !== undefined) {
                sections[index].title = title;
            }
            
            renderSections();
            
            if (callbacks.onChange) {
                callbacks.onChange('edit', sections[index]);
            }
        }
    }
    
    function splitSection(sectionId, splitPosition) {
        var index = findSectionIndex(sectionId);
        
        if (index !== -1) {
            var section = sections[index];
            var content = section.content;
            
            // Nếu không có splitPosition, chia đôi section
            if (splitPosition === undefined) {
                splitPosition = Math.floor(content.length / 2);
            }
            
            // Tạo hai section mới
            var firstContent = content.substring(0, splitPosition);
            var secondContent = content.substring(splitPosition);
            
            var firstSection = {
                id: section.id,
                title: section.title,
                content: firstContent,
                position: section.position,
                type: section.type
            };
            
            var secondSection = {
                id: generateSectionId(),
                title: section.title + ' (Part 2)',
                content: secondContent,
                position: section.position + 1,
                type: section.type
            };
            
            // Thay thế section cũ bằng hai section mới
            sections.splice(index, 1, firstSection, secondSection);
            
            // Cập nhật position cho các section
            updateSectionPositions();
            
            renderSections();
            
            if (callbacks.onChange) {
                callbacks.onChange('split', [firstSection, secondSection]);
            }
        }
    }
    
    function mergeSections(firstSectionId, secondSectionId) {
        var firstIndex = findSectionIndex(firstSectionId);
        var secondIndex = findSectionIndex(secondSectionId);
        
        if (firstIndex !== -1 && secondIndex !== -1) {
            var firstSection = sections[firstIndex];
            var secondSection = sections[secondIndex];
            
            // Tạo section mới từ hai section
            var mergedSection = {
                id: firstSection.id,
                title: firstSection.title,
                content: firstSection.content + '\n' + secondSection.content,
                position: firstSection.position,
                type: firstSection.type
            };
            
            // Nếu áp dụng chunking và kích thước section mới vượt quá giới hạn
            if (chunkingEnabled && mergedSection.content.length > maxChunkSize) {
                // Thông báo lỗi và không thực hiện merge
                alert_float('warning', 'Cannot merge sections. The resulting section would be too large.');
                return false;
            }
            
            // Xóa hai section cũ và thêm section mới
            if (firstIndex < secondIndex) {
                sections.splice(secondIndex, 1);
                sections.splice(firstIndex, 1, mergedSection);
            } else {
                sections.splice(firstIndex, 1);
                sections.splice(secondIndex, 1, mergedSection);
            }
            
            // Cập nhật position cho các section
            updateSectionPositions();
            
            renderSections();
            
            if (callbacks.onChange) {
                callbacks.onChange('merge', mergedSection);
            }
            
            return true;
        }
        
        return false;
    }
    
    function applyChunking() {
        if (!chunkingEnabled) return;
        
        var newSections = [];
        
        // Xử lý từng section
        sections.forEach(function(section) {
            // Kiểm tra kích thước section
            if (section.content.length > maxChunkSize) {
                // Chia nhỏ section
                var chunks = chunkSectionContent(section);
                
                // Thêm các chunk vào danh sách sections mới
                chunks.forEach(function(chunk) {
                    newSections.push(chunk);
                });
            } else {
                // Giữ nguyên section
                newSections.push(section);
            }
        });
        
        // Cập nhật danh sách sections
        sections = newSections;
        
        // Cập nhật position cho các section
        updateSectionPositions();
        
        renderSections();
        
        if (callbacks.onChange) {
            callbacks.onChange('chunk', sections);
        }
    }
    
    function chunkSectionContent(section) {
        var chunks = [];
        var content = section.content;
        
        // Tìm các điểm chia phù hợp (ưu tiên chia tại thẻ HTML đóng)
        var positions = findChunkPositions(content, maxChunkSize);
        
        var startPos = 0;
        
        // Tạo các chunk dựa trên các vị trí chia
        for (var i = 0; i < positions.length; i++) {
            var endPos = positions[i];
            
            // Tạo chunk mới
            var chunkContent = content.substring(startPos, endPos);
            
            var chunk = {
                id: i === 0 ? section.id : generateSectionId(),
                title: i === 0 ? section.title : section.title + ' (Part ' + (i + 1) + ')',
                content: chunkContent,
                position: section.position + i,
                type: section.type
            };
            
            chunks.push(chunk);
            
            startPos = endPos;
        }
        
        // Thêm phần còn lại (nếu có)
        if (startPos < content.length) {
            var lastChunk = {
                id: generateSectionId(),
                title: section.title + ' (Part ' + (chunks.length + 1) + ')',
                content: content.substring(startPos),
                position: section.position + chunks.length,
                type: section.type
            };
            
            chunks.push(lastChunk);
        }
        
        return chunks;
    }
    
    function findChunkPositions(content, maxSize) {
        var positions = [];
        var currentPos = 0;
        
        while (currentPos + maxSize < content.length) {
            // Tìm vị trí kết thúc thẻ HTML gần nhất trong khoảng (currentPos + maxSize/2, currentPos + maxSize)
            var minSearchPos = currentPos + Math.floor(maxSize / 2);
            var maxSearchPos = currentPos + maxSize;
            
            // Ưu tiên tìm thẻ </p>, </div>, </h1>-</h6>, etc.
            var closingTagPos = findClosingTagPosition(content, minSearchPos, maxSearchPos);
            
            if (closingTagPos !== -1) {
                // Nếu tìm thấy thẻ đóng
                positions.push(closingTagPos);
                currentPos = closingTagPos;
            } else {
                // Nếu không tìm thấy thẻ đóng, tìm dấu chấm câu (., !, ?)
                var punctuationPos = findPunctuationPosition(content, minSearchPos, maxSearchPos);
                
                if (punctuationPos !== -1) {
                    // Nếu tìm thấy dấu chấm câu
                    positions.push(punctuationPos);
                    currentPos = punctuationPos;
                } else {
                    // Nếu không tìm thấy gì, chia tại vị trí khoảng trắng gần nhất
                    var spacePos = findSpacePosition(content, minSearchPos, maxSearchPos);
                    
                    if (spacePos !== -1) {
                        // Nếu tìm thấy khoảng trắng
                        positions.push(spacePos);
                        currentPos = spacePos;
                    } else {
                        // Nếu không tìm thấy gì, chia tại vị trí tối đa
                        positions.push(maxSearchPos);
                        currentPos = maxSearchPos;
                    }
                }
            }
        }
        
        return positions;
    }
    
    function findClosingTagPosition(content, startPos, endPos) {
        // Danh sách các thẻ đóng phổ biến
        var closingTags = ['</p>', '</div>', '</h1>', '</h2>', '</h3>', '</h4>', '</h5>', '</h6>', '</li>', '</ul>', '</ol>', '</table>', '</tr>', '</td>', '</th>'];
        
        // Chuyển về chữ thường để tìm kiếm không phân biệt hoa thường
        var lowerContent = content.toLowerCase();
        
        var bestPos = -1;
        
        // Tìm thẻ đóng trong khoảng (startPos, endPos)
        for (var i = 0; i < closingTags.length; i++) {
            var tag = closingTags[i];
            var pos = lowerContent.lastIndexOf(tag, endPos);
            
            if (pos !== -1 && pos >= startPos && pos > bestPos) {
                bestPos = pos + tag.length;
            }
        }
        
        return bestPos;
    }
    
    function findPunctuationPosition(content, startPos, endPos) {
        // Tìm vị trí dấu chấm câu cuối cùng trong khoảng (startPos, endPos)
        for (var pos = endPos; pos >= startPos; pos--) {
            var char = content.charAt(pos);
            if (char === '.' || char === '!' || char === '?') {
                return pos + 1;
            }
        }
        
        return -1;
    }
    
    function findSpacePosition(content, startPos, endPos) {
        // Tìm vị trí khoảng trắng cuối cùng trong khoảng (startPos, endPos)
        for (var pos = endPos; pos >= startPos; pos--) {
            if (content.charAt(pos) === ' ') {
                return pos + 1;
            }
        }
        
        return -1;
    }
    
    function createContentFromSections() {
        var content = '';
        
        // Tạo nội dung từ các section theo thứ tự position
        sections.sort(function(a, b) {
            return a.position - b.position;
        }).forEach(function(section) {
            content += section.content;
        });
        
        return content;
    }
    
    function updateSectionPositions() {
        // Cập nhật position dựa trên thứ tự trong mảng
        sections.forEach(function(section, index) {
            section.position = index;
        });
    }
    
    function renderSections() {
        if (!container) return;
        
        var html = '';
        
        // Sắp xếp sections theo position
        var sortedSections = sections.slice().sort(function(a, b) {
            return a.position - b.position;
        });
        
        sortedSections.forEach(function(section) {
            html += createSectionHtml(section);
        });
        
        $(container).html(html);
        
        // Khởi tạo lại sự kiện cho các section mới
        initSectionEvents();
    }
    
    function createSectionHtml(section) {
        var sectionTypeClass = 'section-type-' + (section.type || 'default');
        var contentPreview = stripHtmlTags(section.content).substring(0, 100) + (section.content.length > 100 ? '...' : '');
        
        var html = '<div class="section-item ' + sectionTypeClass + '" data-section-id="' + section.id + '">';
        html += '<div class="section-header">';
        html += '<div class="section-drag-handle"><i class="fa fa-bars"></i></div>';
        html += '<span class="section-title">' + (section.title || 'Section ' + (section.position + 1)) + '</span>';
        html += '<div class="section-size">' + formatSize(section.content.length) + '</div>';
        html += '<div class="section-actions">';
        html += '<button class="btn btn-xs btn-info section-edit-btn" title="Edit"><i class="fa fa-pencil"></i></button>';
        html += '<button class="btn btn-xs btn-default section-split-btn" title="Split"><i class="fa fa-scissors"></i></button>';
        html += '<button class="btn btn-xs btn-danger section-delete-btn" title="Delete"><i class="fa fa-trash"></i></button>';
        html += '</div>';
        html += '</div>';
        html += '<div class="section-preview">' + contentPreview + '</div>';
        html += '<div class="section-content hidden">' + section.content + '</div>';
        html += '</div>';
        
        return html;
    }
    
    function initSectionEvents() {
        // Click trên section
        $('.section-item').on('click', function(e) {
            if (!$(e.target).closest('.section-actions').length) {
                var sectionId = $(this).data('section-id');
                
                // Đánh dấu section đang active
                $('.section-item').removeClass('active');
                $(this).addClass('active');
                
                activeSection = sectionId;
                
                // Gọi callback nếu có
                if (callbacks.onSectionClick) {
                    var section = findSection(sectionId);
                    if (section) {
                        callbacks.onSectionClick(section);
                    }
                }
            }
        });
        
        // Edit button click
        $('.section-edit-btn').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            var sectionId = $(this).closest('.section-item').data('section-id');
            activateEditorForSection(sectionId);
        });
        
        // Split button click
        $('.section-split-btn').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            var sectionId = $(this).closest('.section-item').data('section-id');
            showSplitSectionDialog(sectionId);
        });
        
        // Delete button click
        $('.section-delete-btn').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            var sectionId = $(this).closest('.section-item').data('section-id');
            if (confirm('Are you sure you want to delete this section?')) {
                removeSection(sectionId);
            }
        });
        
        // Khởi tạo drag and drop
        initDragAndDrop();
    }
    
    function findSection(sectionId) {
        return sections.find(function(section) {
            return section.id === sectionId;
        });
    }
    
    function findSectionIndex(sectionId) {
        return sections.findIndex(function(section) {
            return section.id === sectionId;
        });
    }
    
    return {
        init: init,
        setSections: setSections,
        getSections: getSections,
        getOriginalSections: getOriginalSections,
        addSection: addSection,
        removeSection: removeSection,
        editSection: editSection,
        splitSection: splitSection,
        mergeSections: mergeSections,
        applyChunking: applyChunking,
        createContentFromSections: createContentFromSections
    };
})();