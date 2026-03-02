## Hướng Dẫn Phát Triển: Tích Hợp Controller Selection cho Topic Composer và Draft Writer

### 1. Tổng Quan
Tài liệu này hướng dẫn cách thêm tính năng chọn Controller vào hai module chính:
- Topic Composer: Cho phép chọn Controller khi tạo nội dung từ AI
- Draft Writer: Cho phép chọn Controller khi viết bài nháp

### 2. Các File Hiện Có Cần Chỉnh Sửa

Thay vì tạo file mới, chúng ta sẽ chỉnh sửa các file hiện có:

```
/topics/
├── views/
│   ├── includes/
│   │   ├── displayTopicComposerResult/
│   │   │   ├── topic_detail_action_buttons_display_script_displayTopicComposerResult_showPromptSelectionModal.php
│   │   │   └── topic_detail_action_buttons_display_script_scriptHandlers.php
│   │   ├── displayDraftWriter/
│   │   │   ├── topic_detail_action_buttons_display_script_displayDraftWriter_modal.php
│   │   │   └── topic_detail_action_buttons_display_script_displayDraftWriter_ai.php
│   │   └── topic_detail_action_buttons_display_script_displayDraftWritingResult.php
├── helpers/
│   ├── topics_display_processor_helper.php
│   └── topic_action_processor_DraftWritingProcessor_helper.php
├── assets/
│   ├── css/
│   │   └── topics.css
│   └── js/
│       └── topics/
│           └── topic_actions.js
└── language/
    ├── english/
    │   └── topics_lang.php
    └── vietnamese/
        └── topics_lang.php
```

### 3. Thực Hiện cho Topic Composer

#### 3.1. Cập Nhật Prompt Selection Modal

Chỉnh sửa file `views/includes/displayTopicComposerResult/topic_detail_action_buttons_display_script_displayTopicComposerResult_showPromptSelectionModal.php`:

```javascript
// Tìm đoạn code tạo modal heading & body (khoảng dòng 50-70)
// sau phần const modalHeading =.... 
// Thêm section chọn Controller vào phần modal body trước các prompt selection

const controllerSection = `
<div class="form-group controller-selection">
    <label><?php echo _l('select_controller'); ?></label>
                <div class="input-group">
        <select class="form-control" id="ai-controller-select">
                        <option value=""><?php echo _l('select_controller'); ?></option>
                    </select>
                    <span class="input-group-btn">
            <button type="button" class="btn btn-info" onclick="refreshControllers()">
                            <i class="fa fa-refresh"></i>
                        </button>
                    </span>
                </div>
    <div id="controller-info" class="mtop10" style="display:none;">
        <!-- Controller info will be displayed here -->
            </div>
        </div>
`;

// Tìm đoạn code tạo modalBody (khoảng dòng 100-120)
const modalBody = `
    ${controllerSection}
    <div class="row">
        <!-- Existing prompt selection content -->
`;
```

#### 3.2. Cập Nhật Script Handlers

Chỉnh sửa file `views/includes/displayTopicComposerResult/topic_detail_action_buttons_display_script_scriptHandlers.php`:

```javascript
// Tìm hàm callAIEditAPI và cập nhật
function callAIEditAPI(content, type, prompt, callback, config = {}) {
    // Thêm đoạn code sau vào đầu hàm
    
    // Lấy thông tin controller nếu đã chọn
    const controllerId = $('#ai-controller-select').val();
    if (controllerId) {
        const $selectedController = $('#ai-controller-select option:selected');
        
        // Thêm thông tin controller vào config
        config.controller = {
            id: controllerId,
            writing_style: $selectedController.data('writing-style'),
            platform: $selectedController.data('platform')
        };
        
        // Cập nhật prompt với thông tin controller
        prompt = enhancePromptWithController(prompt, config.controller);
    }
    
    // Phần còn lại của hàm giữ nguyên
    // ...
}

// Thêm hàm sau vào cuối file trước </script>
function enhancePromptWithController(prompt, controller) {
    if (!controller || !controller.id) return prompt;
    
    // Thêm hướng dẫn từ controller vào prompt
    let enhancedPrompt = prompt + '\n\n';
    
    if (controller.writing_style) {
        enhancedPrompt += `Follow this writing style: ${controller.writing_style}\n`;
    }
    
    if (controller.platform) {
        enhancedPrompt += `Optimize for platform: ${controller.platform}\n`;
    }
    
    return enhancedPrompt;
}
```

### 4. Thực Hiện cho Draft Writer

#### 4.1. Cập Nhật Modal Draft Writer

Chỉnh sửa file `views/includes/displayDraftWriter/topic_detail_action_buttons_display_script_displayDraftWriter_modal.php`:

```javascript
// Tìm phần tạo modal body (khoảng dòng 80-120)
// Thêm section controller vào modal body trước các tabs hoặc form elements hiện có

// Thêm section controller trong 2 cột UI
// Thêm sau dòng có `<div class="row">` và trước các phần tử nội dung
const controllerSection = `
<div class="col-md-12 mbot15">
    <div class="panel panel-default">
        <div class="panel-heading">
            <h4 class="panel-title">
                <i class="fa fa-cog"></i> <?php echo _l('controller_settings'); ?>
            </h4>
        </div>
        <div class="panel-body">
                    <div class="form-group">
                        <label><?php echo _l('select_controller'); ?></label>
                        <div class="input-group">
                            <select class="form-control" id="draft-controller-select">
                                <option value=""><?php echo _l('select_controller'); ?></option>
                            </select>
                            <span class="input-group-btn">
                                <button type="button" class="btn btn-info" onclick="loadDraftControllers()">
                                    <i class="fa fa-refresh"></i>
                                </button>
                            </span>
                        </div>
                    </div>
            <div id="draft-controller-info" class="mtop10" style="display:none;">
                <!-- Controller info will be displayed here -->
            </div>
        </div>
    </div>
</div>
`;

// Thêm hàm sau vào cuối file trước </script>
function loadDraftControllers() {
    $.ajax({
        url: admin_url + 'topics/get_available_controllers',
        type: 'GET',
        success: function(response) {
            if (response.success) {
                const select = $('#draft-controller-select');
                select.empty();
                select.append('<option value=""><?php echo _l("select_controller"); ?></option>');
                
                response.data.forEach(controller => {
                    select.append(`<option value="${controller.id}" 
                        data-writing-style="${controller.writing_style}"
                        data-platform="${controller.platform}"
                        data-slogan="${controller.slogan}">
                        ${controller.site}
                    </option>`);
                });
            }
        }
    });
}

function showDraftControllerInfo(data) {
    const safeData = {
        site: _.escape(data.site),
        platform: _.escape(data.platform),
        writing_style: _.escape(data.writing_style),
        slogan: _.escape(data.slogan || '')
    };
    
    $('#draft-controller-info').html(`
        <div class="controller-info-content">
            <p><strong>Site:</strong> ${safeData.site}</p>
            <p><strong>Platform:</strong> <span class="platform-badge platform-${safeData.platform.toLowerCase()}">${safeData.platform}</span></p>
            ${safeData.slogan ? `<p><strong>Slogan:</strong> ${safeData.slogan}</p>` : ''}
            <div class="writing-style-preview">
                <strong>Writing Style:</strong><br>
                ${safeData.writing_style}
            </div>
        </div>
    `).show();
}

// Thêm vào hàm initDraftWriterUI (hoặc thêm vào onload)
function initDraftWriterUI() {
    // Các code hiện có
    // ...
    
    // Thêm các đoạn sau
    // Load controllers khi modal mở
    loadDraftControllers();
    
    // Bind event handler cho controller selection
    $('#draft-controller-select').on('change', function() {
        const controllerId = $(this).val();
        if (controllerId) {
    $.ajax({
        url: admin_url + 'topics/get_controller_info',
        type: 'GET',
        data: { id: controllerId },
        success: function(response) {
            if (response.success) {
                        showDraftControllerInfo(response.data);
            }
                }
            });
        } else {
            $('#draft-controller-info').hide();
        }
    });
}
```

#### 4.2. Cập Nhật AI Integration cho Draft Writer

Chỉnh sửa file `views/includes/displayDraftWriter/topic_detail_action_buttons_display_script_displayDraftWriter_ai.php`:

```javascript
// Tìm hàm ai.generate hoặc generateDraftContent hoặc tương tự
// Cập nhật hàm generate để sử dụng thông tin controller

window.DraftWriter.ai = {
    // Các code hiện có
    
    generate: function(prompt, options = {}) {
        // Thêm đoạn này vào đầu hàm
        const controllerId = $('#draft-controller-select').val();
        if (controllerId) {
            const $selectedController = $('#draft-controller-select option:selected');
            
            // Thêm thông tin controller vào options
            options.controller = {
                id: controllerId,
                writing_style: $selectedController.data('writing-style'),
                platform: $selectedController.data('platform'),
                slogan: $selectedController.data('slogan')
            };
            
            // Cập nhật prompt với thông tin controller
            prompt = this.enhancePromptWithController(prompt, options.controller);
        }
        
        // Phần còn lại của hàm giữ nguyên
        // ...
    },
    
    // Thêm hàm mới
    enhancePromptWithController: function(prompt, controller) {
        if (!controller || !controller.id) return prompt;
        
        // Thêm hướng dẫn từ controller vào prompt
        let enhancedPrompt = prompt + '\n\n';
        
        if (controller.writing_style) {
            enhancedPrompt += `Follow this writing style: ${controller.writing_style}\n`;
        }
        
        if (controller.platform) {
            enhancedPrompt += `Optimize for platform: ${controller.platform}\n`;
        }
        
        if (controller.slogan) {
            enhancedPrompt += `Include brand message: ${controller.slogan}\n`;
        }
        
        return enhancedPrompt;
    }
};
```

#### 4.3. Cập Nhật Draft Writing Result

Chỉnh sửa file `views/includes/topic_detail_action_buttons_display_script_displayDraftWritingResult.php`:

```javascript
// Tìm hàm loadContentFromWorkflowResponse
// Cập nhật để lưu controller_id nếu có trong response

function loadContentFromWorkflowResponse(data) {
    console.log('Loading content from workflow response', data);
    
    // Các code hiện có
    // ...
    
    // Thêm đoạn code sau để lưu controller_id
    // Tìm controller_id trong response nếu có
    if (data && data.data && data.data.response && data.data.response.controller_id) {
        const controllerId = data.data.response.controller_id;
        
        // Lưu vào draft để sử dụng sau
        if (typeof savedDraft !== 'undefined') {
            savedDraft.controller_id = controllerId;
            saveDraftToLocalStorage();
        }
        
        // Chọn controller trong dropdown nếu có
        setTimeout(function() {
            $('#draft-controller-select').val(controllerId).trigger('change');
        }, 500);
    }
}

// Tìm hàm saveDraftToLocalStorage
function saveDraftToLocalStorage() {
    // Thêm controller_id vào dữ liệu lưu trữ
    const controllerId = $('#draft-controller-select').val();
    if (controllerId) {
        savedDraft.controller_id = controllerId;
    }
    
    // Các code hiện có
    // ...
}

// Tìm hàm loadDraftFromLocalStorage
function loadDraftFromLocalStorage() {
    // Các code hiện có
    // ...
    
    // Thêm đoạn code load controller sau khi load các trường khác
    if (savedDraft.controller_id) {
        setTimeout(function() {
            $('#draft-controller-select').val(savedDraft.controller_id).trigger('change');
        }, 500);
    }
}
```

### 5. Cập Nhật Backend

#### 5.1. Cập Nhật Topics Controller

Cần thêm 2 endpoint vào controller Topics.php:

```php
/**
 * Get available controllers
 * @return json
 */
public function get_available_controllers() {
    if (!has_permission('topics', '', 'view')) {
        echo json_encode([
            'success' => false,
            'message' => _l('access_denied')
        ]);
        die;
    }
    
    $this->db->select('id, site, platform, writing_style, slogan');
    $this->db->from('tbltopic_controllers');
    $this->db->where('status', 1);
    $controllers = $this->db->get()->result_array();
    
    echo json_encode([
        'success' => true,
        'data' => $controllers
    ]);
}

/**
 * Get controller info
 * @return json
 */
public function get_controller_info() {
    if (!has_permission('topics', '', 'view')) {
        echo json_encode([
            'success' => false,
            'message' => _l('access_denied')
        ]);
        die;
    }
    
    $id = $this->input->get('id');
    
    $this->db->select('*');
    $this->db->from('tbltopic_controllers');
    $this->db->where('id', $id);
    $controller = $this->db->get()->row_array();
    
    if (!$controller) {
        echo json_encode([
            'success' => false,
            'message' => _l('controller_not_found')
        ]);
        die;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $controller
    ]);
}
```

#### 5.2. Cập Nhật Processor Helper

Chỉnh sửa file `helpers/topics_display_processor_helper.php`:

```php
/**
 * Get available controllers for a topic
 * @param int $topic_id
 * @return array
 */
function get_available_controllers($topic_id) {
    $CI =& get_instance();
    $CI->db->select('*');
    $CI->db->from('tbltopic_controllers');
    $CI->db->where('status', 1);
    
    return $CI->db->get()->result_array();
}

/**
 * Apply controller style to content
 * @param string $content
 * @param array $controller_data
 * @return string
 */
function apply_controller_style($content, $controller_data) {
    // Logic to apply controller style to content
    $writing_style = $controller_data['writing_style'];
    $platform = $controller_data['platform'];
    
    // Apply transformations based on controller settings
    // ...
    
    return $content;
}

/**
 * Process AI content with controller guidelines
 * @param string $content Original content
 * @param array $config Configuration including controller info
 * @return string Processed content
 */
function process_content_with_controller($content, $config) {
    // Get controller data if provided
    if (isset($config['controller']['id'])) {
        $CI =& get_instance();
        $controller = $CI->db->get_where('tbltopic_controllers', 
            ['id' => $config['controller']['id']])->row();
        
        if ($controller) {
            // Apply controller's writing style
            $content = apply_writing_style($content, $controller->writing_style);
            
            // Apply platform-specific formatting
            $content = format_for_platform($content, $controller->platform);
            
            // Apply any additional controller-specific processing
            if ($controller->action_1) {
                $content = apply_custom_actions($content, $controller->action_1);
            }
        }
    }
    
    return $content;
}

/**
 * Apply writing style guidelines to content
 */
function apply_writing_style($content, $style) {
    if (empty($style)) return $content;
    
    // Basic formatting based on writing style keywords
    if (stripos($style, 'formal') !== false) {
        $content = str_replace('don\'t', 'do not', $content);
        $content = str_replace('can\'t', 'cannot', $content);
        $content = str_replace('won\'t', 'will not', $content);
        // Add more formal replacements as needed
    }
    
    if (stripos($style, 'casual') !== false) {
        // Apply casual styling
    }
    
    return $content;
}

/**
 * Format content for specific platform
 */
function format_for_platform($content, $platform) {
    switch (strtolower($platform)) {
        case 'wordpress':
            // WordPress specific formatting
            return format_for_wordpress($content);
        case 'haravan':
            // Haravan specific formatting
            return format_for_haravan($content);
        default:
            return $content;
    }
}

/**
 * Format content for WordPress
 */
function format_for_wordpress($content) {
    // Convert certain HTML elements to WordPress blocks if needed
    return $content;
}

/**
 * Format content for Haravan
 */
function format_for_haravan($content) {
    // Apply Haravan specific formatting
    return $content;
}

/**
 * Apply custom actions from controller configuration
 */
function apply_custom_actions($content, $action_config) {
    if (empty($action_config)) return $content;
    
    // Apply custom actions based on action_config
    // This could be JSON parsed or simple text instructions
    
    return $content;
}
```

#### 5.3. Cập Nhật Draft Writing Processor

Chỉnh sửa file `helpers/topic_action_processor_DraftWritingProcessor_helper.php`:

```php
// Tìm phương thức process hoặc processStep1 và cập nhật
// Thêm code để xử lý controller_id trong workflow

function processStep1($topic_id, $action_data) {
    // Các code hiện có
    // ...
    
    // Thêm controller_id vào data response nếu có
    if (isset($action_data['controller_id'])) {
        $response_data['controller_id'] = $action_data['controller_id'];
    }
    
    // Hoặc lấy controller_id từ topic nếu có liên kết
    if (!isset($response_data['controller_id'])) {
    $CI =& get_instance();
    
        // Lấy topicid từ topic hiện tại
        $topic = $CI->db->get_where('tbltopics', ['id' => $topic_id])->row();
        if ($topic) {
            // Lấy controller_id từ topic_master
            $master = $CI->db->get_where('tbltopic_master', ['topicid' => $topic->topicid])->row();
            if ($master && $master->controller_id) {
                $response_data['controller_id'] = $master->controller_id;
            }
        }
    }
    
    // Tiếp tục xử lý và trả về response
    // ...
}
```

### 6. Cập Nhật CSS Styles

Chỉnh sửa file `assets/css/topics.css`:

```css
/* Thêm CSS styles vào cuối file */

/* Controller Selection Styles */
.controller-selection {
    margin-bottom: 20px;
    padding: 15px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.controller-selection label,
#draft-controller-select-label {
    font-weight: 600;
    color: #333;
}

#controller-info,
#draft-controller-info {
    background: #f9f9f9;
    padding: 10px;
    border: 1px solid #eee;
    border-radius: 4px;
}

.writing-style-preview {
    max-height: 100px;
    overflow-y: auto;
    margin-top: 10px;
    padding: 10px;
    background: #fff;
    border: 1px solid #eee;
}

/* Platform Badge Styles */
.platform-badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: 600;
    margin-right: 5px;
}

.platform-wordpress {
    background: #21759b;
    color: #fff;
}

.platform-haravan {
    background: #7fba00;
    color: #fff;
}
```

### 7. Cập Nhật Language Files

Chỉnh sửa file `language/english/topics_lang.php` và `language/vietnamese/topics_lang.php`:

```php
// Thêm vào cuối file
$lang['select_controller'] = 'Select Controller';
$lang['controller_settings'] = 'Controller Settings';
$lang['please_select_controller'] = 'Please select a controller';
$lang['controller_info'] = 'Controller Information';
$lang['writing_style'] = 'Writing Style';
$lang['platform'] = 'Platform';
$lang['controller_updated'] = 'Controller updated successfully';
$lang['controller_error'] = 'Error updating controller';
$lang['controller_not_found'] = 'Controller not found';
```

### 8. Testing Checklist

1. **Topic Composer Testing**:
   - [ ] Controller selection hiển thị trong prompt modal
   - [ ] Load danh sách controllers thành công
   - [ ] Hiển thị thông tin controller khi chọn
   - [ ] AI generation áp dụng đúng writing style
   - [ ] Nội dung được format theo platform

2. **Draft Writer Testing**:
   - [ ] Controller selection hiển thị trong modal
   - [ ] Load và hiển thị thông tin controller
   - [ ] AI generation tích hợp thông tin controller
   - [ ] Writing style được áp dụng cho toàn bộ bài viết
   - [ ] Controller được lưu vào local storage draft

3. **API Testing**:
   - [ ] get_available_controllers endpoint hoạt động đúng
   - [ ] get_controller_info endpoint hoạt động đúng
   - [ ] Phân quyền hoạt động đúng

4. **Performance Testing**:
   - [ ] Load controllers không gây chậm UI
   - [ ] Xử lý controller data không ảnh hưởng hiệu suất

### 9. Các Lưu Ý Khi Triển Khai

1. **Backup Trước Khi Chỉnh Sửa**:
   - Backup tất cả các file sẽ chỉnh sửa
   - Backup database

2. **Thứ Tự Cập Nhật**:
   - Backend (Controllers, Helpers) trước
   - Frontend (Views, JS, CSS) sau
   - Language files và CSS cuối cùng

3. **Kiểm Tra Tương Thích**:
   - Test trên nhiều trình duyệt: Chrome, Firefox, Safari
   - Test trên mobile/tablet view

4. **Phiên Bản CSS/JS**:
   - Thêm version parameter để tránh cache: `?v=<?php echo time(); ?>`
   - Hoặc clear cache browser sau khi deploy
   - Thêm CSS styles vào cuối file
```

## 16. Phương Án Tích Hợp Vào Files Hiện Có

Thay vì tạo các file mới, chúng ta sẽ cập nhật các file hiện có. Dưới đây là danh sách các file cần chỉnh sửa và hướng dẫn chi tiết.

### 16.1. Các File Cần Cập Nhật

```
/topics/
├── views/
│   ├── includes/
│   │   ├── displayTopicComposerResult/
│   │   │   ├── topic_detail_action_buttons_display_script_displayTopicComposerResult_showPromptSelectionModal.php
│   │   │   └── topic_detail_action_buttons_display_script_scriptHandlers.php
│   │   ├── displayDraftWriter/
│   │   │   ├── topic_detail_action_buttons_display_script_displayDraftWriter_modal.php
│   │   │   └── topic_detail_action_buttons_display_script_displayDraftWriter_ai.php
│   │   └── topic_detail_action_buttons_display_script_displayDraftWritingResult.php
├── helpers/
│   ├── topics_display_processor_helper.php
│   └── topic_action_processor_DraftWritingProcessor_helper.php
├── assets/
│   ├── css/
│   │   └── topics.css
│   └── js/
│       └── topics/
│           └── topic_actions.js
└── language/
    ├── english/
    │   └── topics_lang.php
    └── vietnamese/
        └── topics_lang.php
```

### 16.2. Cập Nhật Topic Composer

#### 16.2.1. Cập Nhật Prompt Selection Modal

Chỉnh sửa file `views/includes/displayTopicComposerResult/topic_detail_action_buttons_display_script_displayTopicComposerResult_showPromptSelectionModal.php`:

```javascript
// Tìm đoạn code tạo modal heading & body (khoảng dòng 50-70)
// sau phần const modalHeading =.... 
// Thêm section chọn Controller vào phần modal body trước các prompt selection

const controllerSection = `
<div class="form-group controller-selection">
    <label><?php echo _l('select_controller'); ?></label>
    <div class="input-group">
        <select class="form-control" id="ai-controller-select">
            <option value=""><?php echo _l('select_controller'); ?></option>
        </select>
        <span class="input-group-btn">
            <button type="button" class="btn btn-info" onclick="refreshControllers()">
                <i class="fa fa-refresh"></i>
            </button>
        </span>
    </div>
    <div id="controller-info" class="mtop10" style="display:none;">
        <!-- Controller info will be displayed here -->
    </div>
</div>
`;

// Tìm đoạn code tạo modalBody (khoảng dòng 100-120)
const modalBody = `
    ${controllerSection}
    <div class="row">
        <!-- Existing prompt selection content -->
`;
```

Thêm các hàm mới vào cuối file (trước `</script>`):

```javascript
function refreshControllers() {
    $.ajax({
        url: admin_url + 'topics/get_available_controllers',
        type: 'GET',
        success: function(response) {
            if (response.success) {
                const select = $('#ai-controller-select');
                select.empty();
                select.append('<option value=""><?php echo _l("select_controller"); ?></option>');
                
                response.data.forEach(controller => {
                    select.append(`<option value="${controller.id}" 
                        data-writing-style="${controller.writing_style}"
                        data-platform="${controller.platform}">
                        ${controller.site}
                    </option>`);
                });
            }
        }
    });
}

function showControllerInfo(data) {
    const safeData = {
        site: _.escape(data.site),
        platform: _.escape(data.platform),
        writing_style: _.escape(data.writing_style)
    };
    
    $('#controller-info').html(`
        <div class="controller-info-content">
            <p><strong>Site:</strong> ${safeData.site}</p>
            <p><strong>Platform:</strong> <span class="platform-badge platform-${safeData.platform.toLowerCase()}">${safeData.platform}</span></p>
            <div class="writing-style-preview">
                <strong>Writing Style:</strong><br>
                ${safeData.writing_style}
            </div>
        </div>
    `).show();
}

// Event handler khi modal được tạo
$(document).on('shown.bs.modal', '#prompt-selection-modal', function() {
    // Refresh controllers list khi modal mở
    refreshControllers();
    
    // Handler cho controller selection
    $('#ai-controller-select').on('change', function() {
        const controllerId = $(this).val();
        if (controllerId) {
            $.ajax({
                url: admin_url + 'topics/get_controller_info',
                type: 'GET',
                data: { id: controllerId },
                success: function(response) {
                    if (response.success) {
                        showControllerInfo(response.data);
                    }
                }
            });
        } else {
            $('#controller-info').hide();
        }
    });
});
```

#### 16.2.2. Cập Nhật Script Handlers

Chỉnh sửa file `views/includes/displayTopicComposerResult/topic_detail_action_buttons_display_script_scriptHandlers.php`:

```javascript
// Tìm hàm callAIEditAPI và cập nhật
function callAIEditAPI(content, type, prompt, callback, config = {}) {
    // Thêm đoạn code sau vào đầu hàm
    
    // Lấy thông tin controller nếu đã chọn
    const controllerId = $('#ai-controller-select').val();
    if (controllerId) {
        const $selectedController = $('#ai-controller-select option:selected');
        
        // Thêm thông tin controller vào config
        config.controller = {
            id: controllerId,
            writing_style: $selectedController.data('writing-style'),
            platform: $selectedController.data('platform')
        };
        
        // Cập nhật prompt với thông tin controller
        prompt = enhancePromptWithController(prompt, config.controller);
    }
    
    // Phần còn lại của hàm giữ nguyên
    // ...
}

// Thêm hàm sau vào cuối file trước </script>
function enhancePromptWithController(prompt, controller) {
    if (!controller || !controller.id) return prompt;
    
    // Thêm hướng dẫn từ controller vào prompt
    let enhancedPrompt = prompt + '\n\n';
    
    if (controller.writing_style) {
        enhancedPrompt += `Follow this writing style: ${controller.writing_style}\n`;
    }
    
    if (controller.platform) {
        enhancedPrompt += `Optimize for platform: ${controller.platform}\n`;
    }
    
    return enhancedPrompt;
}
```

### 16.3. Cập Nhật Draft Writer

#### 16.3.1. Cập Nhật Modal Draft Writer

Chỉnh sửa file `views/includes/displayDraftWriter/topic_detail_action_buttons_display_script_displayDraftWriter_modal.php`:

```javascript
// Tìm phần tạo modal body (khoảng dòng 80-120)
// Thêm section controller vào modal body trước các tabs hoặc form elements hiện có

// Thêm section controller trong 2 cột UI
// Thêm sau dòng có `<div class="row">` và trước các phần tử nội dung
const controllerSection = `
<div class="col-md-12 mbot15">
    <div class="panel panel-default">
        <div class="panel-heading">
            <h4 class="panel-title">
                <i class="fa fa-cog"></i> <?php echo _l('controller_settings'); ?>
            </h4>
        </div>
        <div class="panel-body">
            <div class="form-group">
                <label><?php echo _l('select_controller'); ?></label>
                <div class="input-group">
                    <select class="form-control" id="draft-controller-select">
                        <option value=""><?php echo _l('select_controller'); ?></option>
                    </select>
                    <span class="input-group-btn">
                        <button type="button" class="btn btn-info" onclick="loadDraftControllers()">
                            <i class="fa fa-refresh"></i>
                        </button>
                    </span>
                </div>
            </div>
            <div id="draft-controller-info" class="mtop10" style="display:none;">
                <!-- Controller info will be displayed here -->
            </div>
        </div>
    </div>
</div>
`;
```

Thêm các hàm sau vào cuối file (trước `</script>`):

```javascript
function loadDraftControllers() {
    $.ajax({
        url: admin_url + 'topics/get_available_controllers',
        type: 'GET',
        success: function(response) {
            if (response.success) {
                const select = $('#draft-controller-select');
                select.empty();
                select.append('<option value=""><?php echo _l("select_controller"); ?></option>');
                
                response.data.forEach(controller => {
                    select.append(`<option value="${controller.id}" 
                        data-writing-style="${controller.writing_style}"
                        data-platform="${controller.platform}"
                        data-slogan="${controller.slogan}">
                        ${controller.site}
                    </option>`);
                });
            }
        }
    });
}

function showDraftControllerInfo(data) {
    const safeData = {
        site: _.escape(data.site),
        platform: _.escape(data.platform),
        writing_style: _.escape(data.writing_style),
        slogan: _.escape(data.slogan || '')
    };
    
    $('#draft-controller-info').html(`
        <div class="controller-info-content">
            <p><strong>Site:</strong> ${safeData.site}</p>
            <p><strong>Platform:</strong> <span class="platform-badge platform-${safeData.platform.toLowerCase()}">${safeData.platform}</span></p>
            ${safeData.slogan ? `<p><strong>Slogan:</strong> ${safeData.slogan}</p>` : ''}
            <div class="writing-style-preview">
                <strong>Writing Style:</strong><br>
                ${safeData.writing_style}
            </div>
        </div>
    `).show();
}
```

Tìm hàm `initDraftWriterUI` hoặc `$(document).ready` và thêm:

```javascript
// Load controllers khi modal mở
loadDraftControllers();

// Bind event handler cho controller selection
$('#draft-controller-select').on('change', function() {
    const controllerId = $(this).val();
    if (controllerId) {
        $.ajax({
            url: admin_url + 'topics/get_controller_info',
            type: 'GET',
            data: { id: controllerId },
            success: function(response) {
                if (response.success) {
                    showDraftControllerInfo(response.data);
                    
                    // Lưu controller đã chọn vào global state
                    window.DraftWriter = window.DraftWriter || {};
                    window.DraftWriter.selectedController = {
                        id: controllerId,
                        writing_style: response.data.writing_style,
                        platform: response.data.platform,
                        site: response.data.site,
                        slogan: response.data.slogan
                    };
                    
                    // Cập nhật nội dung đã biên soạn nếu cần
                    updateDraftContentWithController();
                }
            }
        });
    } else {
        $('#draft-controller-info').hide();
        // Xóa controller đã chọn
        if (window.DraftWriter) {
            window.DraftWriter.selectedController = null;
        }
    }
});
```

#### 16.3.2. Cập Nhật AI Integration

Chỉnh sửa file `views/includes/displayDraftWriter/topic_detail_action_buttons_display_script_displayDraftWriter_ai.php`:

```javascript
// Tìm hàm ai.generate hoặc generateDraftContent
window.DraftWriter.ai = {
    // Các hàm hiện có
    
    generate: function(prompt, options = {}) {
        // Thêm đoạn này vào đầu hàm
        const controllerId = $('#draft-controller-select').val();
        if (controllerId) {
            const $selectedController = $('#draft-controller-select option:selected');
            
            // Thêm thông tin controller vào options
            options.controller = {
                id: controllerId,
                writing_style: $selectedController.data('writing-style'),
                platform: $selectedController.data('platform'),
                slogan: $selectedController.data('slogan')
            };
            
            // Cập nhật prompt với thông tin controller
            prompt = this.enhancePromptWithController(prompt, options.controller);
        }
        
        // Phần còn lại của hàm giữ nguyên
        // ...
    },
    
    // Thêm hàm mới
    enhancePromptWithController: function(prompt, controller) {
        if (!controller || !controller.id) return prompt;
        
        // Thêm hướng dẫn từ controller vào prompt
        let enhancedPrompt = prompt + '\n\n';
        
        if (controller.writing_style) {
            enhancedPrompt += `Follow this writing style: ${controller.writing_style}\n`;
        }
        
        if (controller.platform) {
            enhancedPrompt += `Optimize for platform: ${controller.platform}\n`;
        }
        
        if (controller.slogan) {
            enhancedPrompt += `Include brand message: ${controller.slogan}\n`;
        }
        
        return enhancedPrompt;
    }
};
```

#### 16.3.3. Cập Nhật Draft Writing Result

Chỉnh sửa file `views/includes/topic_detail_action_buttons_display_script_displayDraftWritingResult.php`:

```javascript
// Tìm hàm loadContentFromWorkflowResponse
// Cập nhật để lưu controller_id nếu có trong response

function loadContentFromWorkflowResponse(data) {
    console.log('Loading content from workflow response', data);
    
    // Các code hiện có
    // ...
    
    // Thêm đoạn code sau để lưu controller_id
    // Tìm controller_id trong response nếu có
    if (data && data.data && data.data.response && data.data.response.controller_id) {
        const controllerId = data.data.response.controller_id;
        
        // Lưu vào draft để sử dụng sau
        if (typeof savedDraft !== 'undefined') {
            savedDraft.controller_id = controllerId;
            saveDraftToLocalStorage();
        }
        
        // Chọn controller trong dropdown chính của modal
        setTimeout(function() {
            $('#draft-controller-select').val(controllerId).trigger('change');
        }, 500);
    }
}

// Tìm hàm saveDraftToLocalStorage
function saveDraftToLocalStorage() {
    // Lấy controller_id từ global state
    if (window.DraftWriter && window.DraftWriter.selectedController) {
        savedDraft.controller_id = window.DraftWriter.selectedController.id;
    }
    
    // Các code hiện có
    // ...
}

// Tìm hàm loadDraftFromLocalStorage
function loadDraftFromLocalStorage() {
    // Các code hiện có
    // ...
    
    // Cập nhật controller từ local storage
    if (savedDraft.controller_id) {
        setTimeout(function() {
            $('#draft-controller-select').val(savedDraft.controller_id).trigger('change');
        }, 500);
    }
}
```

### 16.4. Cập Nhật Language Files

Thêm các chuỗi mới vào `language/english/topics_lang.php` và `language/vietnamese/topics_lang.php`:

```php
$lang['select_controller'] = 'Select Controller';
$lang['controller_settings'] = 'Controller Settings';
$lang['please_select_controller'] = 'Please select a controller';
$lang['controller_info'] = 'Controller Information';
$lang['writing_style'] = 'Writing Style';
$lang['platform'] = 'Platform';
$lang['controller_updated'] = 'Controller updated successfully';
$lang['controller_error'] = 'Error updating controller';
$lang['controller_not_found'] = 'Controller not found';
```

### 17. Phương Án Hiển Thị Controller Selection Ở Modal Chính

Theo yêu cầu mới, Controller Selection sẽ được hiển thị ở modal chính thay vì trong modal prompt selection, và controller được chọn sẽ áp dụng cho tất cả chỉnh sửa trong modal đó.

#### 17.1. Điều Chỉnh cho Topic Composer

#### 17.1.1. Cập Nhật Modal Chính của Topic Composer

Chỉnh sửa file `views/includes/displayTopicComposerResult/topic_detail_action_buttons_display_script_displayTopicComposerResult_1.php`:

```javascript
// Tìm phần khởi tạo modal chính (thường nằm trong phần function displayTopicComposerResult)
// Thêm phần Controller Selection vào đầu modal content, trước các tab hoặc panel

// Tạo section Controller Selection
const controllerSelectionSection = `
<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default mbot15">
            <div class="panel-heading">
                <h4 class="panel-title">
                    <i class="fa fa-cog"></i> <?php echo _l('controller_selection'); ?>
                </h4>
            </div>
            <div class="panel-body">
                <div class="form-group">
                    <label for="topic-composer-controller-select"><?php echo _l('select_controller'); ?></label>
                    <div class="input-group">
                        <select class="form-control" id="topic-composer-controller-select">
                            <option value=""><?php echo _l('select_controller'); ?></option>
                        </select>
                        <span class="input-group-btn">
                            <button type="button" class="btn btn-info" onclick="loadTopicComposerControllers()">
                                <i class="fa fa-refresh"></i>
                            </button>
                        </span>
                    </div>
                </div>
                <div id="topic-composer-controller-info" class="mtop10" style="display:none;">
                    <!-- Controller info will be displayed here -->
                </div>
            </div>
        </div>
    </div>
</div>
`;

// Thêm section vào đầu modal content (sau .modal-header nhưng trước nội dung chính)
// Tìm dòng code tạo modal content và thêm controllerSelectionSection vào

// Thêm các hàm xử lý Controllers vào cuối file, trước </script>
function loadTopicComposerControllers() {
    $.ajax({
        url: admin_url + 'topics/get_available_controllers',
        type: 'GET',
        success: function(response) {
            if (response.success) {
                const select = $('#topic-composer-controller-select');
                select.empty();
                select.append('<option value=""><?php echo _l("select_controller"); ?></option>');
                
                response.data.forEach(controller => {
                    select.append(`<option value="${controller.id}" 
                        data-writing-style="${controller.writing_style}"
                        data-platform="${controller.platform}">
                        ${controller.site}
                    </option>`);
                });
            }
        }
    });
}

function showTopicComposerControllerInfo(data) {
    const safeData = {
        site: _.escape(data.site),
        platform: _.escape(data.platform),
        writing_style: _.escape(data.writing_style)
    };
    
    $('#topic-composer-controller-info').html(`
        <div class="controller-info-content">
            <p><strong>Site:</strong> ${safeData.site}</p>
            <p><strong>Platform:</strong> <span class="platform-badge platform-${safeData.platform.toLowerCase()}">${safeData.platform}</span></p>
            <div class="writing-style-preview">
                <strong>Writing Style:</strong><br>
                ${safeData.writing_style}
            </div>
        </div>
    `).show();
}

// Thêm event handler vào phần khởi tạo
$(document).ready(function() {
    // Code hiện có
    // ...
    
    // Thêm event handler cho controller selection
    loadTopicComposerControllers();
    
    $('#topic-composer-controller-select').on('change', function() {
        const controllerId = $(this).val();
        if (controllerId) {
            $.ajax({
                url: admin_url + 'topics/get_controller_info',
                type: 'GET',
                data: { id: controllerId },
                success: function(response) {
                    if (response.success) {
                        showTopicComposerControllerInfo(response.data);
                        
                        // Lưu controller đã chọn vào global state
                        window.TopicComposer = window.TopicComposer || {};
                        window.TopicComposer.selectedController = {
                            id: controllerId,
                            writing_style: response.data.writing_style,
                            platform: response.data.platform,
                            site: response.data.site,
                            slogan: response.data.slogan
                        };
                    }
                }
            });
        } else {
            $('#topic-composer-controller-info').hide();
            // Xóa controller đã chọn
            if (window.TopicComposer) {
                window.TopicComposer.selectedController = null;
            }
        }
    });
});
```

#### 17.1.2. Cập Nhật Prompt Selection Modal

Chỉnh sửa file `views/includes/displayTopicComposerResult/topic_detail_action_buttons_display_script_displayTopicComposerResult_showPromptSelectionModal.php`:

```javascript
// Xóa phần Controller Selection trong modal này
// Thay vào đó, cập nhật hàm callAIEditAPI để sử dụng controller từ modal chính

function showPromptSelectionModal(content, field, onComplete, target) {
    // ... existing code ...
    
    // Thêm thông báo về controller được chọn (nếu có)
    let controllerInfoHtml = '';
    if (window.TopicComposer && window.TopicComposer.selectedController) {
        const controller = window.TopicComposer.selectedController;
        controllerInfoHtml = `
            <div class="alert alert-info">
                <strong><?php echo _l('using_controller'); ?>:</strong> ${controller.site}
                <small>(${controller.platform})</small>
            </div>
        `;
    }
    
    // Thêm thông tin controller vào modal body
    const modalBody = `
        ${controllerInfoHtml}
        <div class="row">
            <!-- Existing prompt selection content -->
        </div>
    `;
}
```

#### 17.1.3. Cập Nhật Script Handlers

Chỉnh sửa file `views/includes/displayTopicComposerResult/topic_detail_action_buttons_display_script_scriptHandlers.php`:

```javascript
// Cập nhật hàm callAIEditAPI để lấy controller từ global state
function callAIEditAPI(content, type, prompt, callback, config = {}) {
    // Lấy controller từ global state thay vì từ modal prompt selection
    if (window.TopicComposer && window.TopicComposer.selectedController) {
        // Thêm thông tin controller vào config
        config.controller = window.TopicComposer.selectedController;
        
        // Cập nhật prompt với thông tin controller
        prompt = enhancePromptWithController(prompt, config.controller);
    }
    
    // Phần còn lại của hàm giữ nguyên
    // ...
}
```

### 17.2. Điều Chỉnh cho Draft Writer

#### 17.2.1. Cập Nhật Modal Chính của Draft Writer

Chỉnh sửa file `views/includes/displayDraftWriter/topic_detail_action_buttons_display_script_displayDraftWriter_modal.php`:

```javascript
// Tìm phần khởi tạo modal chính
// Thêm phần Controller Selection vào đầu modal content, trước phần editor chính

// Thêm section Controller Selection ngay sau row div trong modal body nhưng trước các tab hoặc editor
// Đặt trước các phần nội dung hiện có ở đầu modal
const controllerSelectionSection = `
<div class="col-md-12">
    <div class="panel panel-default mbot15">
        <div class="panel-heading">
            <h4 class="panel-title">
                <i class="fa fa-cog"></i> <?php echo _l('controller_settings'); ?>
            </h4>
        </div>
        <div class="panel-body">
            <div class="form-group">
                <label for="draft-writer-controller-select"><?php echo _l('select_controller'); ?></label>
                <div class="input-group">
                    <select class="form-control" id="draft-writer-controller-select">
                        <option value=""><?php echo _l('select_controller'); ?></option>
                    </select>
                    <span class="input-group-btn">
                        <button type="button" class="btn btn-info" onclick="loadDraftWriterControllers()">
                            <i class="fa fa-refresh"></i>
                        </button>
                    </span>
                </div>
            </div>
            <div id="draft-writer-controller-info" class="mtop10" style="display:none;">
                <!-- Controller info will be displayed here -->
            </div>
        </div>
    </div>
</div>
`;

// Thêm các hàm xử lý Controllers
function loadDraftWriterControllers() {
    $.ajax({
        url: admin_url + 'topics/get_available_controllers',
        type: 'GET',
        success: function(response) {
            if (response.success) {
                const select = $('#draft-writer-controller-select');
                select.empty();
                select.append('<option value=""><?php echo _l("select_controller"); ?></option>');
                
                response.data.forEach(controller => {
                    select.append(`<option value="${controller.id}" 
                        data-writing-style="${controller.writing_style}"
                        data-platform="${controller.platform}"
                        data-slogan="${controller.slogan}">
                        ${controller.site}
                    </option>`);
                });
            }
        }
    });
}

function showDraftWriterControllerInfo(data) {
    const safeData = {
        site: _.escape(data.site),
        platform: _.escape(data.platform),
        writing_style: _.escape(data.writing_style),
        slogan: _.escape(data.slogan || '')
    };
    
    $('#draft-writer-controller-info').html(`
        <div class="controller-info-content">
            <p><strong>Site:</strong> ${safeData.site}</p>
            <p><strong>Platform:</strong> <span class="platform-badge platform-${safeData.platform.toLowerCase()}">${safeData.platform}</span></p>
            ${safeData.slogan ? `<p><strong>Slogan:</strong> ${safeData.slogan}</p>` : ''}
            <div class="writing-style-preview">
                <strong>Writing Style:</strong><br>
                ${safeData.writing_style}
            </div>
        </div>
    `).show();
}

// Trong hàm initDraftWriterUI hoặc document.ready, thêm:
// Load controllers và thiết lập event handlers
loadDraftWriterControllers();

$('#draft-writer-controller-select').on('change', function() {
    const controllerId = $(this).val();
    if (controllerId) {
        $.ajax({
            url: admin_url + 'topics/get_controller_info',
            type: 'GET',
            data: { id: controllerId },
            success: function(response) {
                if (response.success) {
                    showDraftWriterControllerInfo(response.data);
                    
                    // Lưu controller đã chọn vào global state
                    window.DraftWriter = window.DraftWriter || {};
                    window.DraftWriter.selectedController = {
                        id: controllerId,
                        writing_style: response.data.writing_style,
                        platform: response.data.platform,
                        site: response.data.site,
                        slogan: response.data.slogan
                    };
                    
                    // Cập nhật nội dung đã biên soạn nếu cần
                    updateDraftContentWithController();
                }
            }
        });
    } else {
        $('#draft-writer-controller-info').hide();
        // Xóa controller đã chọn
        if (window.DraftWriter) {
            window.DraftWriter.selectedController = null;
        }
    }
});
```

#### 17.2.2. Cập Nhật AI Integration cho Draft Writer

Chỉnh sửa file `views/includes/displayDraftWriter/topic_detail_action_buttons_display_script_displayDraftWriter_ai.php`:

```javascript
// Cập nhật hàm generate để lấy controller từ global state
window.DraftWriter.ai = {
    // Các hàm hiện có
    
    generate: function(prompt, options = {}) {
        // Thêm đoạn này vào đầu hàm
        const controllerId = $('#draft-controller-select').val();
        if (controllerId) {
            const $selectedController = $('#draft-controller-select option:selected');
            
            // Thêm thông tin controller vào options
            options.controller = {
                id: controllerId,
                writing_style: $selectedController.data('writing-style'),
                platform: $selectedController.data('platform'),
                slogan: $selectedController.data('slogan')
            };
            
            // Cập nhật prompt với thông tin controller
            prompt = this.enhancePromptWithController(prompt, options.controller);
        }
        
        // Phần còn lại của hàm giữ nguyên
        // ...
    },
    
    // Thêm hàm mới
    enhancePromptWithController: function(prompt, controller) {
        if (!controller || !controller.id) return prompt;
        
        // Thêm hướng dẫn từ controller vào prompt
        let enhancedPrompt = prompt + '\n\n';
        
        if (controller.writing_style) {
            enhancedPrompt += `Follow this writing style: ${controller.writing_style}\n`;
        }
        
        if (controller.platform) {
            enhancedPrompt += `Optimize for platform: ${controller.platform}\n`;
        }
        
        if (controller.slogan) {
            enhancedPrompt += `Include brand message: ${controller.slogan}\n`;
        }
        
        return enhancedPrompt;
    }
};
```

#### 17.2.3. Cập Nhật Draft Writing Result

Chỉnh sửa file `views/includes/topic_detail_action_buttons_display_script_displayDraftWritingResult.php`:

```javascript
// Cập nhật hàm loadContentFromWorkflowResponse để sử dụng controller từ global state
function loadContentFromWorkflowResponse(data) {
    console.log('Loading content from workflow response', data);
    
    // Các code hiện có
    // ...
    
    // Thêm đoạn code sau để lưu controller_id
    // Tìm controller_id trong response nếu có
    if (data && data.data && data.data.response && data.data.response.controller_id) {
        const controllerId = data.data.response.controller_id;
        
        // Lưu vào draft để sử dụng sau
        if (typeof savedDraft !== 'undefined') {
            savedDraft.controller_id = controllerId;
            saveDraftToLocalStorage();
        }
        
        // Chọn controller trong dropdown chính của modal
        setTimeout(function() {
            $('#draft-writer-controller-select').val(controllerId).trigger('change');
        }, 500);
    }
}

// Cập nhật hàm saveDraftToLocalStorage để lưu controller từ global state
function saveDraftToLocalStorage() {
    // Lấy controller_id từ global state
    if (window.DraftWriter && window.DraftWriter.selectedController) {
        savedDraft.controller_id = window.DraftWriter.selectedController.id;
    }
    
    // Các code hiện có
    // ...
}

// Cập nhật hàm loadDraftFromLocalStorage
function loadDraftFromLocalStorage() {
    // Các code hiện có
    // ...
    
    // Thêm đoạn code load controller sau khi load các trường khác
    if (savedDraft.controller_id) {
        setTimeout(function() {
            $('#draft-writer-controller-select').val(savedDraft.controller_id).trigger('change');
        }, 500);
    }
}
```

### 17.3. Cập Nhật Language Files

Thêm các chuỗi mới vào `language/english/topics_lang.php` và `language/vietnamese/topics_lang.php`:

```php
$lang['controller_selection'] = 'Controller Selection';
$lang['using_controller'] = 'Using Controller';
$lang['controller_changed_notice'] = 'Controller has been changed. This will affect future AI generations.';
```

### 17.4. Điểm Khác Biệt So Với Phương Án Trước

#### 17.4.1. Ưu Điểm

1. **Giao diện đơn giản hơn**: Controller selection hiển thị chỉ một lần ở đầu modal chính thay vì trong mỗi modal prompt selection

2. **Tính nhất quán cao hơn**: Một controller được áp dụng cho tất cả các chỉnh sửa trong cùng một phiên làm việc

3. **Trải nghiệm người dùng tốt hơn**: Người dùng chỉ cần chọn controller một lần, giảm số lần nhập liệu

4. **Dễ quản lý state hơn**: Thông tin controller được lưu trong global state (window.TopicComposer/window.DraftWriter)

#### 17.4.2. Thay Đổi Cấu Trúc

1. **Di chuyển UI**: Controller selection được di chuyển từ modal prompt selection sang modal chính

2. **Cập nhật Flow dữ liệu**: Thay vì lấy controller từ modal prompt selection, các hàm AI sẽ lấy từ global state

3. **Đơn giản hóa Prompt Selection Modal**: Không hiển thị controller selection trong prompt modal, chỉ hiển thị thông tin controller đã chọn (nếu có)

### 17.5. Testing Checklist

1. **Topic Composer Testing**:
   - [ ] Controller selection hiển thị ở đầu modal chính
   - [ ] Chỉ hiển thị thông tin controller đã chọn trong prompt modal
   - [ ] AI generation áp dụng controller đã chọn từ modal chính cho tất cả chỉnh sửa

2. **Draft Writer Testing**:
   - [ ] Controller selection hiển thị ở đầu modal chính
   - [ ] Tất cả các chức năng AI sử dụng controller đã chọn
   - [ ] Thông tin controller được lưu trong local storage và load lại khi mở modal