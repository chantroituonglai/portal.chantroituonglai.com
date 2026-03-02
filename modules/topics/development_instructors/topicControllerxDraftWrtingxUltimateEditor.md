<think>I need to develop a comprehensive plan for integrating Draft Writing with the Topic Controller to create a unified content editing system. Let me first understand what resources we have from the provided files and then formulate a detailed plan.

Looking at the attached directory, I can see several relevant markdown files:
- topicControllerxDraftWrtingxUltimateEditor.md (empty file)
- TopicController_vi_updated.md
- TopicController_ViewUpdate.md
- displayDraftWriting.md
- displayDraftWriting-code.md
- displayTopicComposer.md
- topicComposerFuncUpdate.md

Additionally, the PHP file `helpers/topics_data_processor_helper.php` appears to contain code for processing topic data, including a form renderer (`BuildPostStructureProcessor` class).

Before crafting a detailed plan, I should examine some of these files to understand the current functionality better. Let's start by exploring a few key files to get a better understanding of the existing components.</think># Kế hoạch tích hợp Controller Draft Writing - Ultimate Editor

Sau khi phân tích cấu trúc và chức năng hiện tại của hệ thống, tôi sẽ đề xuất một kế hoạch chi tiết để tích hợp Topic Controller và Draft Writing thành một trình soạn thảo hợp nhất với đầy đủ các tính năng yêu cầu.

## I. Phân tích hiện trạng

Read file: development_instructors/TopicController_vi.md
Cần đọc thêm thông tin về các thành phần hiện có:

Read file: development_instructors/displayDraftWriting.md

Read file: development_instructors/displayTopicComposer.md

Read file: development_instructors/TopicControllerDetailUpgraded.md
Dựa trên thông tin đã thu thập, tôi sẽ xây dựng kế hoạch tích hợp chi tiết:

## II. Kiến trúc của Ultimate Editor

### 1. Tổng quan kiến trúc

Ultimate Editor sẽ là một hệ thống soạn thảo nội dung tích hợp, kết hợp các tính năng từ Topic Composer và Draft Writing, cùng với khả năng quản lý từ khóa SEO và công cụ AI trợ giúp. Kiến trúc hệ thống bao gồm:

- **Frontend**: Giao diện người dùng với trình soạn thảo nâng cao, phân tích nội dung và tích hợp AI
- **Backend**: Xử lý dữ liệu, tích hợp với N8N workflows và các API bên ngoài
- **Lưu trữ**: Kết hợp LocalStorage và cơ sở dữ liệu MySQL

### 2. Cấu trúc thư mục

```
topics/
├── controllers/
│   ├── UltimateEditor.php       # Controller chính xử lý các request
│   └── Topics.php               # Controller hiện có (cập nhật)
├── models/
│   └── Ultimate_editor_model.php # Model xử lý dữ liệu
├── views/
│   ├── ultimate_editor/
│   │   ├── index.php            # Màn hình chính của Ultimate Editor
│   │   ├── panels/
│   │   │   ├── editor_panel.php  # Panel soạn thảo chính
│   │   │   ├── keyword_panel.php # Panel quản lý từ khóa
│   │   │   ├── analysis_panel.php # Panel phân tích nội dung
│   │   │   └── publish_panel.php  # Panel xuất bản
│   │   ├── modals/
│   │   │   ├── ai_prompt_modal.php  # Modal chọn prompt AI
│   │   │   ├── publish_modal.php    # Modal xuất bản
│   │   │   └── seo_modal.php        # Modal phân tích SEO
│   │   └── partials/
│   │       ├── editor_toolbar.php   # Thanh công cụ editor
│   │       ├── ai_toolbar.php       # Thanh công cụ AI
│   │       └── section_editor.php   # Trình soạn thảo theo từng phần
│   └── includes/                  # Cập nhật các file hiện có
├── assets/
│   ├── js/
│   │   ├── ultimate_editor/
│   │   │   ├── main.js           # JavaScript chính
│   │   │   ├── editor.js         # Xử lý trình soạn thảo
│   │   │   ├── ai_integration.js # Tích hợp AI
│   │   │   ├── seo_tools.js      # Công cụ SEO
│   │   │   ├── section_editor.js # Soạn thảo theo từng phần
│   │   │   └── publish.js        # Xử lý xuất bản
│   │   ├── draft_writer.js       # Cập nhật file hiện có
│   │   └── topic_composer.js     # Cập nhật file hiện có
│   └── css/
│       ├── ultimate_editor.css   # CSS cho Ultimate Editor
│       └── section_editor.css    # CSS cho trình soạn thảo theo từng phần
└── helpers/
    ├── ultimate_editor_processor_helper.php  # Processor mới cho Ultimate Editor
    └── topics_data_processor_helper.php      # Cập nhật file hiện có
```

## III. Phân tích chức năng chi tiết

### 1. Quản lý từ khóa SEO

#### Mô tả
- Cho phép người dùng nhập danh sách từ khóa mục tiêu
- Phân tích mật độ từ khóa trong nội dung
- Đề xuất cải thiện sử dụng từ khóa

#### Triển khai
- Panel riêng để quản lý từ khóa
- Hiển thị thống kê và đề xuất trong thời gian thực
- Tích hợp với công cụ phân tích SEO

### 2. Trích xuất và quản lý thông tin bài viết

#### Mô tả
- Trích xuất thông tin bài viết từ Topic Composer
- Tự động tạo cấu trúc bài viết từ các mục
- Quản lý tiêu đề, mô tả và nội dung đầy đủ

#### Triển khai
- Tích hợp API để truy xuất dữ liệu từ Topic Composer
- Chuyển đổi định dạng dữ liệu giữa các hệ thống
- Lưu trữ và quản lý phiên bản bài viết

### 3. Trình soạn thảo với khả năng chỉnh sửa theo từng phần

#### Mô tả
- Trình soạn thảo văn bản nâng cao với khả năng chỉnh sửa theo đoạn
- Đánh dấu từng phần để có thể chọn và chỉnh sửa riêng biệt
- Tích hợp với công cụ AI để cải thiện từng phần

#### Triển khai
- Phát triển component Section Editor
- Đánh dấu các đoạn văn bản với ID duy nhất
- Tạo giao diện chọn và chỉnh sửa theo đoạn

### 4. Tích hợp AI

#### Mô tả
- Hỗ trợ chỉnh sửa nội dung bằng AI
- Tìm kiếm thông tin liên quan
- Kiểm tra thông tin và sự kiện
- Đề xuất cải thiện nội dung

#### Triển khai
- Tích hợp với API của AI models
- Tạo modal chọn prompt và style
- Hiển thị kết quả AI trong editor

### 5. Công cụ xuất bản

#### Mô tả
- Xuất bản bài viết lên các nền tảng khác nhau
- Tùy chọn lập lịch xuất bản
- Quản lý trạng thái xuất bản

#### Triển khai
- Tích hợp với TopicController
- Tạo giao diện xuất bản với nhiều tùy chọn
- Quản lý trạng thái và lịch sử xuất bản

## IV. Kế hoạch triển khai

### Giai đoạn 1: Thiết lập cơ sở

#### Nhiệm vụ
1. Tạo cấu trúc thư mục và các file cơ bản
2. Phát triển controller và model cho Ultimate Editor
3. Xây dựng giao diện cơ bản với các panel chính

#### Triển khai
- Tạo UltimateEditor controller mới
- Tạo các view cơ bản cho từng panel
- Xây dựng CSS và JS ban đầu

### Giai đoạn 2: Tích hợp Topic Composer

#### Nhiệm vụ
1. Tạo API để lấy dữ liệu từ Topic Composer
2. Chuyển đổi định dạng dữ liệu
3. Hiển thị dữ liệu trong Editor

#### Triển khai
- Phát triển API endpoints mới
- Tạo hàm xử lý chuyển đổi dữ liệu
- Xây dựng giao diện hiển thị nội dung từ Topic Composer

### Giai đoạn 3: Phát triển Section Editor

#### Nhiệm vụ
1. Phát triển trình soạn thảo theo từng phần
2. Tạo hệ thống đánh dấu các phần nội dung
3. Xây dựng toolbar chỉnh sửa cho từng phần

#### Triển khai
- Tạo component SectionEditor
- Xây dựng hệ thống nhận dạng và đánh dấu đoạn văn
- Phát triển JS quản lý trạng thái và tương tác

### Giai đoạn 4: Tích hợp AI và phân tích SEO

#### Nhiệm vụ
1. Tích hợp với API của AI
2. Phát triển công cụ phân tích SEO
3. Tạo hệ thống đề xuất cải thiện nội dung

#### Triển khai
- Phát triển module tích hợp AI
- Xây dựng công cụ phân tích từ khóa và SEO
- Tạo giao diện đề xuất cải thiện

### Giai đoạn 5: Công cụ xuất bản và hoàn thiện

#### Nhiệm vụ
1. Tích hợp với TopicController
2. Phát triển công cụ xuất bản
3. Hoàn thiện UI/UX và testing

#### Triển khai
- Tạo API endpoints cho việc xuất bản
- Xây dựng giao diện xuất bản
- Kiểm thử và tối ưu hóa

## V. Chi tiết triển khai

### 1. UltimateEditor Controller

```php
<?php
defined('BASEPATH') or exit('No direct script access allowed');

class UltimateEditor extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('topics_model');
    }

    /**
     * Màn hình chính của Ultimate Editor
     */
    public function index($topic_id = null)
    {
        if (!has_permission('topics', '', 'view')) {
            access_denied('topics');
        }

        if (empty($topic_id)) {
            redirect(admin_url('topics'));
        }

        $data = [];
        $data['title'] = _l('ultimate_editor');
        $data['topic_id'] = $topic_id;
        
        // Lấy thông tin topic
        $topic = $this->topics_model->get($topic_id);
        if (!$topic) {
            show_404();
        }
        $data['topic'] = $topic;
        
        // Lấy dữ liệu từ Topic Composer nếu có
        $topic_composer_data = $this->get_topic_composer_data($topic_id);
        $data['topic_composer_data'] = $topic_composer_data;
        
        // Lấy danh sách controllers cho phần xuất bản
        $this->load->model('topic_controller_model');
        $data['controllers'] = $this->topic_controller_model->get_topic_controllers();
        
        // Render view
        $this->load->view('ultimate_editor/index', $data);
    }
    
    /**
     * API lấy dữ liệu từ Topic Composer
     */
    public function get_composer_data($topic_id)
    {
        if (!has_permission('topics', '', 'view')) {
            echo json_encode(['success' => false, 'message' => _l('access_denied')]);
            die();
        }
        
        $topic_composer_data = $this->get_topic_composer_data($topic_id);
        echo json_encode(['success' => true, 'data' => $topic_composer_data]);
    }
    
    /**
     * API lưu bài viết
     */
    public function save_draft($topic_id)
    {
        if (!has_permission('topics', '', 'edit')) {
            echo json_encode(['success' => false, 'message' => _l('access_denied')]);
            die();
        }
        
        $post_data = $this->input->post();
        
        // Xử lý lưu dữ liệu
        $this->load->helper('ultimate_editor_processor_helper');
        $processor = new UltimateEditorProcessor();
        $result = $processor->save_draft($topic_id, $post_data);
        
        echo json_encode($result);
    }
    
    /**
     * API xuất bản bài viết
     */
    public function publish($topic_id)
    {
        if (!has_permission('topics', '', 'edit')) {
            echo json_encode(['success' => false, 'message' => _l('access_denied')]);
            die();
        }
        
        $post_data = $this->input->post();
        
        // Xử lý xuất bản
        $this->load->helper('ultimate_editor_processor_helper');
        $processor = new UltimateEditorProcessor();
        $result = $processor->publish($topic_id, $post_data);
        
        echo json_encode($result);
    }
    
    /**
     * API phân tích SEO
     */
    public function analyze_seo()
    {
        $post_data = $this->input->post();
        
        // Xử lý phân tích SEO
        $this->load->helper('ultimate_editor_processor_helper');
        $processor = new UltimateEditorProcessor();
        $result = $processor->analyze_seo($post_data);
        
        echo json_encode($result);
    }
    
    /**
     * API tích hợp AI
     */
    public function ai_process()
    {
        $post_data = $this->input->post();
        
        // Xử lý yêu cầu AI
        $this->load->helper('ultimate_editor_processor_helper');
        $processor = new UltimateEditorProcessor();
        $result = $processor->process_ai_request($post_data);
        
        echo json_encode($result);
    }
    
    /**
     * Hàm nội bộ lấy dữ liệu từ Topic Composer
     */
    private function get_topic_composer_data($topic_id)
    {
        // Lấy dữ liệu từ database hoặc cache
        $CI = &get_instance();
        
        // Thực hiện logic lấy dữ liệu từ Topic Composer
        // ...
        
        return $data;
    }
}
```

### 2. UltimateEditorProcessor

```php
<?php defined('BASEPATH') or exit('No direct script access allowed');

class UltimateEditorProcessor {
    
    public function __construct()
    {
        $this->CI = &get_instance();
        $this->CI->load->model('topics_model');
    }
    
    /**
     * Lưu bản nháp bài viết
     */
    public function save_draft($topic_id, $data)
    {
        try {
            // Kiểm tra topic_id
            $topic = $this->CI->topics_model->get($topic_id);
            if (!$topic) {
                return [
                    'success' => false,
                    'message' => 'Topic không tồn tại'
                ];
            }
            
            // Xử lý lưu dữ liệu
            $draft_data = [
                'title' => $data['title'] ?? '',
                'description' => $data['description'] ?? '',
                'content' => $data['content'] ?? '',
                'keywords' => $data['keywords'] ?? [],
                'sections' => $data['sections'] ?? [],
                'meta' => [
                    'last_updated' => date('Y-m-d H:i:s'),
                    'last_updated_by' => get_staff_user_id(),
                    'version' => $data['version'] ?? 1
                ]
            ];
            
            // Lưu dữ liệu vào cơ sở dữ liệu (cần tạo bảng mới hoặc sử dụng topic_target)
            $this->CI->db->where('id', $topic_id);
            $this->CI->db->update(db_prefix() . 'topics', [
                'data' => json_encode($draft_data)
            ]);
            
            // Lưu history
            $this->save_draft_history($topic_id, $draft_data);
            
            return [
                'success' => true,
                'message' => 'Đã lưu bản nháp thành công',
                'data' => [
                    'draft_id' => $topic_id,
                    'last_updated' => date('Y-m-d H:i:s')
                ]
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Lỗi khi lưu bản nháp: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Xuất bản bài viết lên các nền tảng
     */
    public function publish($topic_id, $data)
    {
        try {
            // Kiểm tra topic_id
            $topic = $this->CI->topics_model->get($topic_id);
            if (!$topic) {
                return [
                    'success' => false,
                    'message' => 'Topic không tồn tại'
                ];
            }
            
            // Lấy thông tin controller
            $controller_id = $data['controller_id'] ?? null;
            if (!$controller_id) {
                return [
                    'success' => false,
                    'message' => 'Vui lòng chọn controller để xuất bản'
                ];
            }
            
            $this->CI->load->model('topic_controller_model');
            $controller = $this->CI->topic_controller_model->get($controller_id);
            if (!$controller) {
                return [
                    'success' => false,
                    'message' => 'Controller không tồn tại'
                ];
            }
            
            // Xử lý xuất bản dựa trên platform
            $platform = $controller->platform;
            $result = $this->publish_to_platform($topic_id, $controller_id, $data);
            
            return $result;
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Lỗi khi xuất bản: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Phân tích SEO
     */
    public function analyze_seo($data)
    {
        try {
            $content = $data['content'] ?? '';
            $title = $data['title'] ?? '';
            $description = $data['description'] ?? '';
            $keywords = $data['keywords'] ?? [];
            
            // Phân tích SEO
            $result = [
                'title_length' => strlen($title),
                'title_score' => $this->analyze_title($title, $keywords),
                'description_length' => strlen($description),
                'description_score' => $this->analyze_description($description, $keywords),
                'content_length' => strlen(strip_tags($content)),
                'content_score' => $this->analyze_content($content, $keywords),
                'keyword_density' => $this->calculate_keyword_density($content, $keywords),
                'headings' => $this->analyze_headings($content),
                'suggestions' => $this->generate_seo_suggestions($title, $description, $content, $keywords)
            ];
            
            return [
                'success' => true,
                'data' => $result
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Lỗi khi phân tích SEO: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Xử lý yêu cầu AI
     */
    public function process_ai_request($data)
    {
        try {
            $type = $data['type'] ?? '';
            $content = $data['content'] ?? '';
            $prompt = $data['prompt'] ?? '';
            $options = $data['options'] ?? [];
            
            // Xử lý dựa trên loại yêu cầu
            switch ($type) {
                case 'edit':
                    $result = $this->ai_edit_content($content, $prompt, $options);
                    break;
                case 'improve':
                    $result = $this->ai_improve_content($content, $prompt, $options);
                    break;
                case 'fact_check':
                    $result = $this->ai_fact_check($content, $options);
                    break;
                case 'search':
                    $result = $this->ai_search($content, $options);
                    break;
                default:
                    return [
                        'success' => false,
                        'message' => 'Loại yêu cầu AI không hợp lệ'
                    ];
            }
            
            return $result;
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Lỗi khi xử lý yêu cầu AI: ' . $e->getMessage()
            ];
        }
    }
    
    // Các hàm xử lý nội bộ
    private function save_draft_history($topic_id, $draft_data)
    {
        // Lưu lịch sử bản nháp
    }
    
    private function publish_to_platform($topic_id, $controller_id, $data)
    {
        // Xuất bản lên platform cụ thể
    }
    
    // Các hàm phân tích SEO
    private function analyze_title($title, $keywords)
    {
        // Phân tích tiêu đề
    }
    
    private function analyze_description($description, $keywords)
    {
        // Phân tích mô tả
    }
    
    private function analyze_content($content, $keywords)
    {
        // Phân tích nội dung
    }
    
    private function calculate_keyword_density($content, $keywords)
    {
        // Tính mật độ từ khóa
    }
    
    private function analyze_headings($content)
    {
        // Phân tích cấu trúc heading
    }
    
    private function generate_seo_suggestions($title, $description, $content, $keywords)
    {
        // Tạo đề xuất SEO
    }
    
    // Các hàm xử lý AI
    private function ai_edit_content($content, $prompt, $options)
    {
        // Gọi API AI để chỉnh sửa nội dung
    }
    
    private function ai_improve_content($content, $prompt, $options)
    {
        // Gọi API AI để cải thiện nội dung
    }
    
    private function ai_fact_check($content, $options)
    {
        // Gọi API AI để kiểm tra thông tin
    }
    
    private function ai_search($content, $options)
    {
        // Gọi API AI để tìm kiếm thông tin
    }
}
```

### 3. Ultimate Editor Main UI (index.php)

```html
<div id="ultimate-editor" class="ultimate-editor-container">
    <!-- Header -->
    <div class="ultimate-editor-header">
        <div class="row">
            <div class="col-md-6">
                <h4><?php echo _l('ultimate_editor'); ?> - <?php echo $topic['title']; ?></h4>
            </div>
            <div class="col-md-6 text-right">
                <div class="btn-group">
                    <button type="button" class="btn btn-info save-draft-btn">
                        <i class="fa fa-save"></i> <?php echo _l('save_draft'); ?>
                    </button>
                    <button type="button" class="btn btn-success publish-btn">
                        <i class="fa fa-paper-plane"></i> <?php echo _l('publish'); ?>
                    </button>
                    <button type="button" class="btn btn-default close-editor-btn">
                        <i class="fa fa-times"></i> <?php echo _l('close'); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Main content -->
    <div class="ultimate-editor-content">
        <div class="row">
            <!-- Editor Panel - 2/3 width -->
            <div class="col-md-8">
                <?php $this->load->view('ultimate_editor/panels/editor_panel'); ?>
            </div>
            
            <!-- Sidebar Panel - 1/3 width -->
            <div class="col-md-4">
                <!-- Keywords Panel -->
                <?php $this->load->view('ultimate_editor/panels/keyword_panel'); ?>
                
                <!-- Analysis Panel -->
                <?php $this->load->view('ultimate_editor/panels/analysis_panel'); ?>
                
                <!-- Publish Panel -->
                <?php $this->load->view('ultimate_editor/panels/publish_panel'); ?>
            </div>
        </div>
    </div>
    
    <!-- Modals -->
    <?php $this->load->view('ultimate_editor/modals/ai_prompt_modal'); ?>
    <?php $this->load->view('ultimate_editor/modals/publish_modal'); ?>
    <?php $this->load->view('ultimate_editor/modals/seo_modal'); ?>
</div>

<!-- Initialize JavaScript -->
<script>
    $(function() {
        // Khởi tạo Ultimate Editor
        window.UltimateEditor.init({
            topic_id: <?php echo $topic_id; ?>,
            composer_data: <?php echo json_encode($topic_composer_data); ?>,
            controllers: <?php echo json_encode($controllers); ?>,
            admin_url: '<?php echo admin_url(); ?>'
        });
    });
</script>
```

### 4. Main JavaScript (main.js)

```javascript
// UltimateEditor namespace
window.UltimateEditor = (function() {
    // Private variables
    var config = {};
    var state = {
        initialized: false,
        changed: false,
        lastSaved: null,
        content: {},
        keywords: [],
        sections: []
    };
    
    // Initialize the editor
    function init(options) {
        config = options;
        
        // Initialize components
        initEditor();
        initKeywordTools();
        initAnalysisTools();
        initPublishTools();
        initEventListeners();
        
        // Load data
        if (config.composer_data) {
            loadComposerData(config.composer_data);
        } else {
            loadDraftFromLocalStorage();
        }
        
        state.initialized = true;
        
        // Start auto-save timer
        startAutoSave();
    }
    
    // Initialize the main editor
    function initEditor() {
        // Initialize TinyMCE
        tinymce.init({
            selector: '#content-editor',
            height: 500,
            plugins: 'advlist autolink lists link image charmap preview anchor searchreplace visualblocks code fullscreen insertdatetime media table paste code help wordcount',
            toolbar: 'undo redo | formatselect | bold italic underline strikethrough | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image | removeformat | help',
            setup: function(editor) {
                // Setup listeners
                editor.on('Change', function(e) {
                    markAsChanged();
                    updateAnalysis();
                });
                
                // Add custom buttons
                editor.ui.registry.addButton('aiEdit', {
                    text: 'AI Edit',
                    tooltip: 'Edit content with AI',
                    onAction: function() {
                        showAIPromptModal(editor.selection.getContent(), 'edit');
                    }
                });
                
                // Add custom context menu for section editing
                editor.ui.registry.addContextMenu('sectionEdit', {
                    update: function(element) {
                        // Check if we're in a paragraph or section
                        if (element.nodeName === 'P' || element.closest('[data-section-id]')) {
                            return 'aiimprove aifactcheck sectionsplit';
                        }
                        return '';
                    }
                });
                
                editor.ui.registry.addMenuItem('aiimprove', {
                    text: 'AI Improve',
                    icon: 'edit-block',
                    onAction: function() {
                        showAIPromptModal(editor.selection.getContent(), 'improve');
                    }
                });
                
                editor.ui.registry.addMenuItem('aifactcheck', {
                    text: 'AI Fact Check',
                    icon: 'checkmark',
                    onAction: function() {
                        factCheckContent(editor.selection.getContent());
                    }
                });
                
                editor.ui.registry.addMenuItem('sectionsplit', {
                    text: 'Split into Section',
                    icon: 'splitvertical',
                    onAction: function() {
                        splitIntoSection(editor);
                    }
                });
            }
        });
        
        // Initialize Section Editor
        window.SectionEditor.init({
            container: '#section-editor-container',
            onChange: function() {
                markAsChanged();
                updateAnalysis();
            }
        });
    }
    
    // Initialize keyword tools
    function initKeywordTools() {
        // Keyword input handler
        $('#keyword-input').on('keyup', function(e) {
            if (e.key === 'Enter' || e.keyCode === 13) {
                addKeyword($(this).val().trim());
                $(this).val('');
            }
        });
        
        // Add keyword button
        $('#add-keyword-btn').on('click', function() {
            addKeyword($('#keyword-input').val().trim());
            $('#keyword-input').val('');
        });
    }
    
    // Initialize analysis tools
    function initAnalysisTools() {
        // Run initial analysis when editor is loaded
        setTimeout(function() {
            updateAnalysis();
        }, 1000);
    }
    
    // Initialize publish tools
    function initPublishTools() {
        // Initialize publish modal
        $('#publish-btn').on('click', function() {
            $('#publish-modal').modal('show');
        });
        
        // Initialize controller selection
        $('#controller-select').on('change', function() {
            var controllerId = $(this).val();
            if (controllerId) {
                loadControllerSettings(controllerId);
            }
        });
    }
    
    // Main event listeners
    function initEventListeners() {
        // Save draft button
        $('.save-draft-btn').on('click', function() {
            saveDraft();
        });
        
        // Close editor button
        $('.close-editor-btn').on('click', function() {
            if (state.changed) {
                if (confirm('You have unsaved changes. Do you want to save before closing?')) {
                    saveDraft(function() {
                        window.location.href = admin_url + 'topics/detail/' + config.topic_id;
                    });
                } else {
                    window.location.href = admin_url + 'topics/detail/' + config.topic_id;
                }
            } else {
                window.location.href = admin_url + 'topics/detail/' + config.topic_id;
            }
        });
        
        // SEO analysis button
        $('#analyze-seo-btn').on('click', function() {
            runSEOAnalysis();
        });
        
        // Window beforeunload
        $(window).on('beforeunload', function() {
            if (state.changed) {
                return "You have unsaved changes. Are you sure you want to leave?";
            }
        });
    }
    
    // Load data from Topic Composer
    function loadComposerData(data) {
        if (!data || !data.items) {
            return;
        }
        
        var title = '';
        var description = '';
        var content = '';
        var sections = [];
        
        // Process data from Topic Composer
        data.items.forEach(function(item, index) {
            if (index === 0) {
                // Use first item for title and description
                title = item.title || '';
                description = item.summary || '';
            }
            
            // Add to sections
            sections.push({
                id: 'section-' + index,
                title: item.title || '',
                content: item.content || '',
                position: index + 1
            });
            
            // Build content
            content += '<h2>' + (item.title || '') + '</h2>';
            content += item.content || '';
            content += '<p>&nbsp;</p>';
        });
        
        // Set data in form
        $('#title-input').val(title);
        $('#description-input').val(description);
        
        // Set content in editor
        if (tinymce.get('content-editor')) {
            tinymce.get('content-editor').setContent(content);
        }
        
        // Set sections
        state.sections = sections;
        window.SectionEditor.setSections(sections);
        
        // Update state
        state.content = {
            title: title,
            description: description,
            content: content
        };
        
        // Run analysis
        updateAnalysis();
    }
    
    // Save draft to server and local storage
    function saveDraft(callback) {
        var content = tinymce.get('content-editor').getContent();
        var title = $('#title-input').val();
        var description = $('#description-input').val();
        
        var data = {
            title: title,
            description: description,
            content: content,
            keywords: state.keywords,
            sections: state.sections,
            version: state.content.version || 1
        };
        
        // Save to local storage first
        saveToLocalStorage(data);
        
        // Save to server
        $.ajax({
            url: admin_url + 'ultimate_editor/save_draft/' + config.topic_id,
            type: 'POST',
            data: data,
            success: function(response) {
                try {
                    var result = JSON.parse(response);
                    if (result.success) {
                        alert_float('success', result.message);
                        state.changed = false;
                        state.lastSaved = new Date();
                        updateLastSavedTime();
                        
                        if (typeof callback === 'function') {
                            callback();
                        }
                    } else {
                        alert_float('danger', result.message);
                    }
                } catch (e) {
                    alert_float('danger', 'Error parsing response');
                    console.error(e);
                }
            },
            error: function(xhr, status, error) {
                alert_float('danger', 'Error saving draft: ' + error);
            }
        });
    }
    
    // Mark content as changed
    function markAsChanged() {
        state.changed = true;
        $('.save-indicators').addClass('unsaved');
        $('.save-draft-btn').addClass('btn-warning').removeClass('btn-info');
    }
    
    // Add a keyword
    function addKeyword(keyword) {
        if (!keyword) return;
        
        // Check if keyword already exists
        if (state.keywords.indexOf(keyword) !== -1) {
            alert_float('warning', 'Keyword already exists');
            return;
        }
        
        // Add to state
        state.keywords.push(keyword);
        
        // Add to UI
        var $keywordItem = $('<div class="keyword-item"></div>')
            .text(keyword)
            .append($('<span class="remove-keyword">&times;</span>').on('click', function() {
                removeKeyword(keyword);
                $(this).parent().remove();
            }));
        
        $('#keywords-container').append($keywordItem);
        
        // Update analysis
        markAsChanged();
        updateAnalysis();
    }
    
    // Remove a keyword
    function removeKeyword(keyword) {
        var index = state.keywords.indexOf(keyword);
        if (index !== -1) {
            state.keywords.splice(index, 1);
            markAsChanged();
            updateAnalysis();
        }
    }
    
    // Update SEO and content analysis
    function updateAnalysis() {
        var content = tinymce.get('content-editor').getContent();
        var title = $('#title-input').val();
        var description = $('#description-input').val();
        
        // Basic stats
        updateBasicStats(content, title, description);
        
        // Update outline
        updateOutline(content);
        
        // Update keyword density
        updateKeywordDensity(content);
        
        // Update SEO score if keywords exist
        if (state.keywords.length > 0) {
            updateSEOScore(content, title, description);
        }
    }
    
    // Update basic stats like word count, etc.
    function updateBasicStats(content, title, description) {
        var wordCount = countWords(content);
        var readingTime = Math.ceil(wordCount / 200); // Assume 200 words per minute
        var paragraphCount = (content.match(/<p>/g) || []).length;
        
        $('#word-count').text(wordCount);
        $('#reading-time').text(readingTime + ' min');
        $('#paragraph-count').text(paragraphCount);
    }
    
    // Update document outline based on headings
    function updateOutline(content) {
        var $outlineContainer = $('#outline-container');
        $outlineContainer.empty();
        
        // Extract headings
        var headings = [];
        var headingRegex = /<h([1-6])[^>]*>(.*?)<\/
