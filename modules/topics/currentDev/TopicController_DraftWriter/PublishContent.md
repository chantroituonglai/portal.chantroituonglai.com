# Cập Nhật Giao Diện Tab Publish trong Ultimate Editor

## Tổng Quan

Tài liệu này mô tả kế hoạch cập nhật giao diện Tab Publish trong Ultimate Editor để cải thiện quy trình xuất bản nội dung. Các cập nhật bao gồm việc tổ chức lại các phần tử giao diện người dùng và tích hợp chức năng quản lý thẻ (tags) với hệ thống quản lý bản nháp (draft management).

## Thay Đổi Chính

### 1. Di Chuyển Phần "Publish Options" 

**Mục Tiêu:**
- Di chuyển phần "Publish Options" từ cột bên trái (Content Preview) sang cột bên phải (Controller Settings)
- Đặt phần này giữa phần "Select Topic Controller" và phần "Categories"

**Lý Do:**
- Nhóm tất cả các tùy chọn liên quan đến xuất bản vào một khu vực chung
- Cải thiện luồng công việc của người dùng khi chuẩn bị xuất bản nội dung
- Tạo không gian rộng hơn cho phần xem trước nội dung (Content Preview)

### 2. Nâng Cao Chức Năng "Popular Tags"

**Mục Tiêu:**
- Tích hợp các thẻ đã được thiết lập trong draft vào phần "Popular Tags"
- Tận dụng cải tiến quản lý bản nháp từ bản cập nhật gần đây

**Chi Tiết Triển Khai:**
- Lấy dữ liệu thẻ từ trường `draft_tags` trong dữ liệu bản nháp
- Hiển thị thẻ từ dự thảo cùng với các thẻ phổ biến
- Cho phép người dùng chọn nhanh các thẻ này khi xuất bản

### 3. Cập Nhật Trải Nghiệm Người Dùng

**Cải Tiến:**
- Thêm thông báo trực quan khi tùy chọn xuất bản thay đổi
- Cải thiện tính nhất quán của giao diện người dùng giữa các phần
- Tối ưu hóa không gian hiển thị để đảm bảo tất cả các tùy chọn dễ tiếp cận

## Triển Khai Kỹ Thuật

### 1. Cập Nhật File Giao Diện (tab_publish.php)

```php
<!-- XÓA phần "Publish Options" từ cột bên trái (dòng ~60-90) -->

<!-- THÊM phần "Publish Options" vào cột bên phải, sau phần Topic Controller và trước Categories -->
<div class="panel panel-default mtop20">
    <div class="panel-heading">
        <h4 class="panel-title"><i class="fa fa-cog"></i> <?= _l('publish_options'); ?></h4>
    </div>
    <div class="panel-body">
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="post-status"><?= _l('post_status'); ?></label>
                    <select id="post-status" class="form-control">
                        <option value="draft"><?= _l('draft'); ?></option>
                        <option value="pending"><?= _l('pending_review'); ?></option>
                        <option value="publish"><?= _l('publish'); ?></option>
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group schedule-time-group hide">
                    <label for="schedule-time"><?= _l('schedule_time'); ?></label>
                    <div class="input-group date">
                        <input id="schedule-time" type="text" class="form-control datepicker">
                        <div class="input-group-addon">
                            <i class="fa fa-calendar calendar-icon"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- SEO Options -->
        <div class="form-group mtop20">
            <label for="permalink-slug"><?= _l('permalink_slug'); ?></label>
            <div class="input-group">
                <span class="input-group-addon" id="permalink-prefix"></span>
                <input type="text" id="permalink-slug" class="form-control" placeholder="<?= _l('enter_slug'); ?>">
            </div>
            <small class="text-muted"><?= _l('permalink_slug_help'); ?></small>
        </div>
    </div>
</div>
```

### 2. Cập Nhật JavaScript để Quản Lý Tags từ Draft

```javascript
/**
 * Tải và hiển thị các thẻ từ dữ liệu draft
 */
function loadDraftTagsToPopularTags() {
    console.log('Loading draft tags to popular tags section');
    
    // Lấy dữ liệu tags từ draft
    const draftTags = getDraftTags();
    if (!draftTags || draftTags.length === 0) {
        console.log('No draft tags found');
        return;
    }
    
    console.log('Found draft tags:', draftTags);
    
    // Hiển thị các thẻ từ draft trong phần "Popular Tags"
    const popularTagsContainer = $('#popular-tags-list');
    
    // Thêm tiêu đề cho phần tags từ draft
    const draftTagsTitle = $('<div class="draft-tags-title">').text('Draft Tags:');
    popularTagsContainer.append(draftTagsTitle);
    
    // Tạo container cho các draft tags
    const draftTagsContainer = $('<div class="draft-tags-container">');
    
    // Thêm từng tag vào container
    draftTags.forEach(tag => {
        const tagElement = $('<span class="label label-tag">')
            .text(tag)
            .click(function() {
                // Thêm tag vào tags-select khi được click
                addTagToSelection(tag);
            });
        draftTagsContainer.append(tagElement);
    });
    
    popularTagsContainer.append(draftTagsContainer);
}

/**
 * Lấy danh sách tags từ dữ liệu draft
 * @returns {Array} Danh sách các tags
 */
function getDraftTags() {
    // Kiểm tra dữ liệu draft trong sessionStorage
    const fullDraftData = $('#full-draft-data').val() || sessionStorage.getItem('full_draft_data');
    if (!fullDraftData) {
        return [];
    }
    
    try {
        const parsedDraft = JSON.parse(fullDraftData);
        if (parsedDraft.draft_tags) {
            // Nếu draft_tags là chuỗi, chuyển thành mảng
            if (typeof parsedDraft.draft_tags === 'string') {
                return parsedDraft.draft_tags.split(',').map(tag => tag.trim()).filter(tag => tag !== '');
            }
            // Nếu đã là mảng
            if (Array.isArray(parsedDraft.draft_tags)) {
                return parsedDraft.draft_tags.filter(tag => tag !== '');
            }
        }
    } catch (e) {
        console.error('Error parsing draft data for tags:', e);
    }
    
    return [];
}

/**
 * Thêm tag vào tags-select
 * @param {string} tag - Tag cần thêm
 */
function addTagToSelection(tag) {
    // Kiểm tra xem tag đã được chọn chưa
    const existingTags = $('#tags-select').val() || [];
    if (existingTags.includes(tag)) {
        return;
    }
    
    // Kiểm tra xem tag đã có trong danh sách options chưa
    let optionExists = false;
    $('#tags-select option').each(function() {
        if ($(this).val() === tag) {
            optionExists = true;
            return false; // break the loop
        }
    });
    
    // Nếu chưa có, thêm option mới
    if (!optionExists) {
        $('#tags-select').append(new Option(tag, tag, true, true));
    }
    
    // Thêm tag vào selection
    const updatedTags = [...existingTags, tag];
    $('#tags-select').val(updatedTags).trigger('change');
}
```

### 3. Cập Nhật CSS

```css
/* Styling cho phần Draft Tags */
.draft-tags-title {
    font-weight: bold;
    margin-top: 10px;
    margin-bottom: 5px;
}

.draft-tags-container {
    margin-bottom: 15px;
}

.label-tag {
    background-color: #f1f1f1;
    color: #333;
    margin-right: 5px;
    margin-bottom: 5px;
    display: inline-block;
    padding: 5px 8px;
    border-radius: 3px;
    cursor: pointer;
    transition: background-color 0.2s;
}

.label-tag:hover {
    background-color: #e1e1e1;
}
```


## Tổ Chức Mã Nguồn

Để cải thiện khả năng bảo trì và mở rộng, chúng ta sẽ tổ chức lại cấu trúc mã nguồn cho chức năng xuất bản như sau:

### 1. Tách File JavaScript Riêng

**Tạo file `ultimate_editor_publish.js`:**
- Tách các chức năng liên quan đến xuất bản ra khỏi file `ultimate_editor.js` chính
- Giúp code dễ quản lý và giảm kích thước file chính
- Tối ưu hóa thời gian tải trang

```javascript
/**
 * Ultimate Editor - Publish Functions
 * 
 * File này chứa tất cả các chức năng liên quan đến quy trình xuất bản nội dung
 * từ Ultimate Editor lên các nền tảng bên ngoài thông qua Topic Controller.
 * 
 * @package TopicController
 * @subpackage UltimateEditor
 */

// Namespace cho publish module
var UltimateEditorPublish = {};

/**
 * Khởi tạo module xuất bản
 */
UltimateEditorPublish.init = function() {
    'use strict';
    
    console.log('Initializing Publish module...');
    
    // Lưu trữ dữ liệu
    this.data = {
        selectedController: null,
        categories: [],
        tags: [],
        draft: null
    };
    
    // Khởi tạo UI components
    this.initUI();
    
    // Đăng ký các sự kiện
    this.bindEvents();
    
    return this;
};

/**
 * Khởi tạo UI cho tab publish
 */
UltimateEditorPublish.initUI = function() {
    // Khởi tạo select2 cho tags
    if ($.fn.select2) {
        $('#tags-select').select2({
            tags: true,
            placeholder: app.lang.select_or_add_tags,
            allowClear: true
        });
    }
    
    // Khởi tạo datepicker cho lịch xuất bản
    if ($.fn.datetimepicker) {
        $('#schedule-time').datetimepicker({
            format: 'Y-m-d H:i:s',
            step: 30,
            minDate: 0,
            defaultTime: '12:00'
        });
    }
    
    // Tải dữ liệu ban đầu
    this.loadControllers();
    
    return this;
};

/**
 * Đăng ký các sự kiện
 */
UltimateEditorPublish.bindEvents = function() {
    // Topic Controller change event
    $('#topic-controller-select').on('change', function() {
        const controllerId = $(this).val();
        if (controllerId) {
            UltimateEditorPublish.controllerSelected(controllerId);
        } else {
            UltimateEditorPublish.clearControllerData();
        }
    });
    
    // Post status change event
    $('#post-status').on('change', function() {
        const status = $(this).val();
        if (status === 'schedule') {
            $('.schedule-time-group').removeClass('hide');
        } else {
            $('.schedule-time-group').addClass('hide');
        }
    });
    
    // Featured image selection
    $('#select-feature-image').on('click', function(e) {
        e.preventDefault();
        UltimateEditorPublish.openMediaLibrary();
    });
    
    // Remove featured image
    $('#remove-feature-image').on('click', function(e) {
        e.preventDefault();
        UltimateEditorPublish.removeFeaturedImage();
    });
    
    // Publish button click
    $('#modal-publish-content').on('click', function(e) {
        e.preventDefault();
        UltimateEditorPublish.publishContent();
    });
    
    // Khi tab publish được chọn
    $('a[href="#tab_publish"]').on('shown.bs.tab', function() {
        UltimateEditorPublish.refreshData();
    });
    
    return this;
};

/**
 * Tải danh sách controllers
 */
UltimateEditorPublish.loadControllers = function() {
    // Hiển thị loading
    $('#topic-controller-select').html('<option value="">' + app.lang.loading + '...</option>');
    
    // Gọi API lấy danh sách controllers
    $.ajax({
        url: admin_url + 'topics/ultimate_editor/get_topic_controllers',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                UltimateEditorPublish.renderControllers(response.data);
            } else {
                alert_float('danger', response.message || app.lang.error_loading_controllers);
            }
        },
        error: function(xhr) {
            alert_float('danger', app.lang.error_loading_controllers);
            console.error('Error loading controllers:', xhr);
        }
    });
    
    return this;
};

/**
 * Hiển thị danh sách controllers
 */
UltimateEditorPublish.renderControllers = function(controllers) {
    const $select = $('#topic-controller-select');
    
    // Reset và thêm option mặc định
    $select.html('<option value="">' + app.lang.select_topic_controller + '</option>');
    
    // Thêm các controllers vào dropdown
    if (controllers && controllers.length) {
        controllers.forEach(function(controller) {
            const $option = $('<option></option>')
                .val(controller.id)
                .text(controller.name)
                .data('platform', controller.platform)
                .data('connected', controller.connected ? 'true' : 'false');
                
            $select.append($option);
        });
        
        // Kiểm tra xem có controller đã chọn trong draft hay không
        this.restoreSelectedController();
    } else {
        // Hiển thị thông báo nếu không có controller
        $select.html('<option value="">' + app.lang.no_controllers_available + '</option>');
    }
    
    return this;
};

// Thêm các phương thức khác cho module UltimateEditorPublish
// ...

// Khởi tạo module khi document ready
$(document).ready(function() {
    'use strict';
    
    // Khởi tạo module publish nếu tab publish tồn tại
    if ($('#tab_publish').length || $('#publish-modal').length) {
        UltimateEditorPublish.init();
    }
});
```

**Cập nhật HTML để include file mới:**

```php
<!-- Include trong views/topics/ultimate_editor/index.php -->
<?php echo app_script_tags(module_dir_url(TOPICS_MODULE_NAME, 'assets/js/ultimate_editor.js')); ?>
<?php echo app_script_tags(module_dir_url(TOPICS_MODULE_NAME, 'assets/js/ultimate_editor_fn.js')); ?>
<?php echo app_script_tags(module_dir_url(TOPICS_MODULE_NAME, 'assets/js/ultimate_editor_presents.js')); ?>
<?php echo app_script_tags(module_dir_url(TOPICS_MODULE_NAME, 'assets/js/ultimate_editor_exec.js')); ?>
<?php echo app_script_tags(module_dir_url(TOPICS_MODULE_NAME, 'assets/js/ultimate_editor_publish.js')); ?> <!-- File mới thêm -->
```

### 2. CSS Riêng Cho Tab Publish

**Tạo file `publish_tab.css`:**
- Chứa các quy tắc CSS riêng cho tab Publish
- Giúp cô lập và quản lý kiểu dáng hiển thị riêng biệt
- Dễ dàng tùy chỉnh giao diện Publish

```css
/**
 * CSS cho Tab Publish trong Ultimate Editor
 * 
 * File này chứa các quy tắc CSS riêng cho tab Publish
 * giúp tạo giao diện phù hợp với quy trình xuất bản nội dung
 */

/* Layout chung cho Tab Publish */
#tab_publish, #publish-modal {
    font-family: 'Roboto', sans-serif;
}

/* Hiệu ứng hover cho các panel */
#tab_publish .panel, #publish-modal .panel {
    transition: box-shadow 0.3s ease;
}

#tab_publish .panel:hover, #publish-modal .panel:hover {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

/* Tiêu đề panel */
#tab_publish .panel-title, #publish-modal .panel-title {
    font-size: 15px;
    font-weight: 600;
    display: flex;
    align-items: center;
}

#tab_publish .panel-title i, #publish-modal .panel-title i {
    margin-right: 8px;
    color: #03a9f4;
}

/* Controller Selection */
#topic-controller-select {
    border-radius: 4px;
    padding: 8px 12px;
    border: 1px solid #dce0e6;
}

#controller-info .label {
    display: inline-block;
    margin-right: 6px;
    padding: 5px 8px;
    border-radius: 3px;
    font-size: 11px;
}

/* Categories Tree */
.category-tree-container {
    max-height: 320px;
    overflow-y: auto;
    padding-right: 5px;
}

.category-node {
    margin-bottom: 6px;
}

.category-children {
    margin-left: 20px;
    border-left: 1px solid #e0e0e0;
    padding-left: 10px;
}

.category-checkbox {
    margin-right: 5px !important;
}

/* Tags Section */
#tags-select {
    width: 100%;
}

.select2-container--default .select2-selection--multiple {
    border-color: #dce0e6;
}

.popular-tags {
    margin-top: 15px;
}

.popular-tag {
    display: inline-block;
    margin-right: 6px;
    margin-bottom: 6px;
    padding: 4px 8px;
    background-color: #f5f5f5;
    border-radius: 3px;
    font-size: 12px;
    cursor: pointer;
    transition: background-color 0.2s ease;
}

.popular-tag:hover {
    background-color: #e0e0e0;
}

/* Draft Tags */
.draft-tags-title {
    font-weight: 600;
    margin-top: 12px;
    margin-bottom: 6px;
    font-size: 13px;
    color: #424242;
}

.draft-tags-container {
    margin-bottom: 15px;
}

.label-tag {
    background-color: #f1f1f1;
    color: #333;
    margin-right: 5px;
    margin-bottom: 5px;
    display: inline-block;
    padding: 5px 8px;
    border-radius: 3px;
    cursor: pointer;
    transition: background-color 0.2s;
    font-size: 12px;
}

.label-tag:hover {
    background-color: #e1e1e1;
}

/* Publish Options */
.schedule-time-group {
    transition: opacity 0.3s ease;
}

.schedule-time-group.hide {
    display: none;
    opacity: 0;
}

.character-count {
    text-align: right;
    font-size: 11px;
    color: #757575;
    margin-top: 2px;
}

/* Feature Image */
.feature-image-preview-container {
    text-align: center;
    margin-bottom: 20px;
}

#feature-image-preview {
    border: 1px solid #dce0e6;
    padding: 5px;
    background-color: #f9f9f9;
    border-radius: 4px;
    margin-bottom: 10px;
}

#feature-image {
    max-height: 200px;
    max-width: 100%;
}

/* Preview Content */
.preview-post-title {
    margin-top: 0;
    margin-bottom: 15px;
    font-weight: 600;
    color: #2c3e50;
}

.preview-meta-data {
    margin-bottom: 15px;
    font-size: 12px;
    color: #757575;
}

.preview-meta-data span {
    display: inline-block;
    margin-right: 15px;
}

.preview-meta-data i {
    margin-right: 5px;
}

.preview-post-content {
    font-size: 14px;
    line-height: 1.6;
    color: #333;
}

/* Publish Button */
#modal-publish-content {
    padding: 10px 20px;
    font-size: 16px;
    font-weight: 500;
    letter-spacing: 0.5px;
    transition: all 0.3s ease;
}

#modal-publish-content:hover {
    background-color: #0288d1;
}

#publish-status-message {
    transition: all 0.3s ease;
}

/* WordPress Plugin Integration */
#wordpress-plugins-support {
    margin-top: 20px;
}

.troubleshoot-steps {
    background-color: #f9f9f9;
    padding: 10px 15px;
    border-radius: 4px;
    margin-top: 10px;
    font-size: 13px;
}

/* Responsive Adjustments */
@media (max-width: 768px) {
    .preview-meta-data span {
        display: block;
        margin-bottom: 5px;
    }
    
    #modal-publish-content {
        margin-top: 10px;
    }
}
```

**Cập nhật HTML để include CSS mới:**

```php
<!-- Include trong views/topics/ultimate_editor/index.php -->
<?php echo app_stylesheet_tags(module_dir_url(TOPICS_MODULE_NAME, 'assets/css/draft_writer.css')); ?>
<?php echo app_stylesheet_tags(module_dir_url(TOPICS_MODULE_NAME, 'assets/css/publish_tab.css')); ?> <!-- File CSS mới -->
```

### 3. Files Tham Khảo Quan Trọng

Để phát triển và bảo trì chức năng xuất bản nội dung, cần tham khảo các file sau:

#### 3.1. Controllers

```
modules/topics/controllers/Ultimate_editor.php
modules/topics/controllers/Topic_controller.php
modules/topics/controllers/Topic_composer.php
```

**Ultimate_editor.php:**
- Controller chính quản lý Ultimate Editor
- Chứa các endpoints xử lý draft và xuất bản
- Hàm `publish_content()` và `get_topic_controllers()` đặc biệt quan trọng

**Topic_controller.php:**
- Quản lý kết nối đến các nền tảng bên ngoài
- Cung cấp giao diện quản lý các nền tảng
- Cần hiểu cấu trúc dữ liệu và quy trình xác thực

**Topic_composer.php:**
- Liên quan đến tạo và quản lý topic compositions
- Tích hợp với Ultimate Editor để chọn và sử dụng nội dung

#### 3.2. Models

```
modules/topics/models/Ultimate_editor_model.php
modules/topics/models/Topic_controller_model.php
modules/topics/models/Draft_model.php
```

**Ultimate_editor_model.php:**
- Xử lý dữ liệu liên quan đến Ultimate Editor
- Lưu trữ và tải nội dung editor
- Quản lý cài đặt và tùy chọn editor

**Topic_controller_model.php:**
- Quản lý dữ liệu topic controllers
- CRUD cho kết nối với các nền tảng
- Lưu trữ thông tin xác thực và cấu hình API

**Draft_model.php:**
- Quản lý thông tin bản nháp
- Lưu trữ và tải nội dung bản nháp
- Phương thức để lưu bản nháp và xuất bản

#### 3.3. Helpers & Libraries

```
modules/topics/helpers/topic_platform_helper.php
modules/topics/libraries/connectors/Wordpress_connector.php
modules/topics/libraries/connectors/Connector_interface.php
```

**topic_platform_helper.php:**
- Cung cấp các hàm tiện ích để làm việc với platform
- Hỗ trợ các chức năng chung cho nhiều platform
- Xử lý dữ liệu và chuyển đổi giữa các định dạng

**Wordpress_connector.php:**
- Kết nối với WordPress thông qua REST API
- Xử lý xác thực và gọi các phương thức API WordPress
- Quản lý danh mục, thẻ và đăng tải bài viết

**Connector_interface.php:**
- Định nghĩa interface cho các connector
- Đảm bảo tính nhất quán giữa các loại connector
- Cung cấp cấu trúc chuẩn để triển khai connector mới

#### 3.4. JavaScript Files

```
modules/topics/assets/js/ultimate_editor.js
modules/topics/assets/js/ultimate_editor_fn.js
modules/topics/assets/js/ultimate_editor_presents.js
modules/topics/assets/js/ultimate_editor_exec.js
```

**ultimate_editor.js:**
- File JavaScript chính cho Ultimate Editor
- Quản lý khởi tạo và các chức năng cơ bản
- Cung cấp cấu trúc cơ bản cho các module khác

**ultimate_editor_fn.js:**
- Chứa các hàm hỗ trợ và tiện ích
- Xử lý các tác vụ phổ biến và tái sử dụng
- Cung cấp API cho các module khác

**ultimate_editor_presents.js:**
- Quản lý giao diện người dùng và hiển thị
- Xử lý render UI và hiệu ứng
- Tập trung vào trải nghiệm người dùng

**ultimate_editor_exec.js:**
- Xử lý workflow và lệnh thực thi
- Quản lý gọi AJAX và xử lý dữ liệu
- Tập trung vào logic nghiệp vụ và xử lý workflow

## Lưu Ý Quan Trọng Khi Triển Khai

1. **Tuân thủ cấu trúc mã nguồn Perfex CRM**:
   - Sử dụng đúng quy ước đặt tên và cấu trúc từ Perfex
   - Tận dụng các helper và functions có sẵn trong framework
   - Đảm bảo tương thích với các phiên bản Perfex CRM

2. **Tối ưu hiệu suất**:
   - Tải mã JavaScript và CSS có điều kiện để giảm thời gian tải trang
   - Sử dụng cache khi phù hợp để giảm gọi API không cần thiết
   - Thiết kế UI để giảm thiểu việc render lại khi không cần thiết

3. **Bảo mật**:
   - Xác thực tất cả dữ liệu người dùng trước khi xử lý
   - Lưu trữ thông tin xác thực API an toàn
   - Xử lý các trường hợp lỗi và ngoại lệ đúng cách

4. **Đa ngôn ngữ**:
   - Sử dụng `_l()` cho tất cả văn bản hiển thị
   - Đảm bảo cập nhật các file ngôn ngữ với các chuỗi mới
   - Tránh hardcode văn bản trực tiếp trong mã

## Bổ Sung Tính Năng WordPress

Để hoàn thiện tính năng đăng tải bài viết lên WordPress thông qua Topic Controller, cần bổ sung các chức năng sau:

### 1. Tối Ưu Đặc Tính WordPress

**Các trường dữ liệu bổ sung:**
- **Featured Image**: Thêm chức năng tải lên và quản lý ảnh đại diện
- **Excerpt**: Trường tóm tắt ngắn gọn cho bài viết
- **Custom Fields**: Hỗ trợ trường tùy chỉnh của WordPress (meta data)
- **SEO Options**: Tích hợp với các plugin SEO phổ biến (Yoast SEO, Rank Math)

```php
<!-- Thêm vào phần Publish Options -->
<div class="form-group mtop20">
    <label for="post-excerpt"><?= _l('excerpt'); ?></label>
    <textarea id="post-excerpt" class="form-control" rows="3" placeholder="<?= _l('enter_excerpt'); ?>"></textarea>
    <small class="text-muted"><?= _l('excerpt_help'); ?></small>
</div>

<!-- Thêm phần SEO Options -->
<div class="panel-heading">
    <h4 class="panel-title"><i class="fa fa-search"></i> <?= _l('seo_options'); ?></h4>
</div>
<div class="panel-body">
    <div class="form-group">
        <label for="seo-title"><?= _l('seo_title'); ?></label>
        <input type="text" id="seo-title" class="form-control" placeholder="<?= _l('enter_seo_title'); ?>">
        <div class="character-count"><span id="seo-title-count">0</span>/60</div>
    </div>
    <div class="form-group">
        <label for="meta-description"><?= _l('meta_description'); ?></label>
        <textarea id="meta-description" class="form-control" rows="3" placeholder="<?= _l('enter_meta_description'); ?>"></textarea>
        <div class="character-count"><span id="meta-desc-count">0</span>/160</div>
    </div>
    <div class="form-group">
        <label for="focus-keyword"><?= _l('focus_keyword'); ?></label>
        <input type="text" id="focus-keyword" class="form-control" placeholder="<?= _l('enter_focus_keyword'); ?>">
    </div>
</div>
```

### 2. Tích Hợp Với Quản Lý Media

**Chức năng Media Library:**
- Tích hợp với WordPress Media Library
- Cho phép chọn ảnh đã tải lên trước đó
- Hỗ trợ drag-drop tải ảnh mới

```javascript
/**
 * Khởi tạo tính năng quản lý ảnh đại diện
 */
function initFeaturedImageSelector() {
    // Xử lý nút chọn ảnh đại diện
    $('#select-feature-image').on('click', function() {
        const controller_id = $('#topic-controller-select').val();
        
        if (!controller_id) {
            alert_float('warning', app.lang.please_select_topic_controller);
            return;
        }
        
        // Mở modal chọn ảnh từ WordPress Media Library
        $.ajax({
            url: admin_url + 'topics/ultimate_editor/get_media_library',
            type: 'POST',
            data: {
                controller_id: controller_id
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Hiển thị modal với danh sách media
                    $('#media-library-modal').html(response.html).modal('show');
                    
                    // Khởi tạo chức năng chọn ảnh
                    initMediaSelector();
                } else {
                    alert_float('danger', response.message || app.lang.error_loading_media);
                }
            },
            error: function(xhr, status, error) {
                alert_float('danger', app.lang.error_loading_media + ': ' + error);
            }
        });
    });
    
    // Xử lý nút xóa ảnh đại diện
    $('#remove-feature-image').on('click', function() {
        $('#feature-image').attr('src', app.site_url + 'modules/topics/assets/img/placeholder-image.jpg');
        $('#featured-image-id').val('');
        $(this).addClass('hide');
    });
}
```

### 3. Đồng Bộ Dữ Liệu Bản Nháp với WordPress

**Cải tiến cơ chế đồng bộ:**
- Lưu trữ thông tin WordPress (categories, tags) trong dữ liệu draft
- Đồng bộ hai chiều giữa Perfex CRM và WordPress
- Theo dõi trạng thái xuất bản của bài viết

```javascript
/**
 * Cập nhật dữ liệu bản nháp với thông tin WordPress
 */
function updateDraftWithWordPressData() {
    // Lấy dữ liệu hiện tại từ form
    const draftData = getCurrentDraftData();
    
    // Bổ sung thông tin WordPress
    draftData.wordpress = {
        post_status: $('#post-status').val(),
        schedule_time: $('#schedule-time').val(),
        permalink_slug: $('#permalink-slug').val(),
        categories: $('#categories-tree input:checked').map(function() {
            return $(this).val();
        }).get(),
        tags: $('#tags-select').val(),
        seo_title: $('#seo-title').val(),
        meta_description: $('#meta-description').val(),
        focus_keyword: $('#focus-keyword').val(),
        featured_image_id: $('#featured-image-id').val()
    };
    
    // Lưu lại vào bản nháp
    saveDraftWithData(draftData);
}

/**
 * Tải dữ liệu WordPress từ bản nháp
 */
function loadWordPressDataFromDraft() {
    const fullDraftData = $('#full-draft-data').val() || sessionStorage.getItem('full_draft_data');
    if (!fullDraftData) {
        return;
    }
    
    try {
        const parsedDraft = JSON.parse(fullDraftData);
        if (parsedDraft.wordpress) {
            // Điền thông tin vào form
            $('#post-status').val(parsedDraft.wordpress.post_status || 'draft');
            $('#permalink-slug').val(parsedDraft.wordpress.permalink_slug || '');
            $('#seo-title').val(parsedDraft.wordpress.seo_title || '');
            $('#meta-description').val(parsedDraft.wordpress.meta_description || '');
            $('#focus-keyword').val(parsedDraft.wordpress.focus_keyword || '');
            
            // Xử lý thời gian hẹn giờ nếu có
            if (parsedDraft.wordpress.schedule_time) {
                $('#schedule-time').val(parsedDraft.wordpress.schedule_time);
                if ($('#post-status').val() === 'future') {
                    $('.schedule-time-group').removeClass('hide');
                }
            }
            
            // Xử lý ảnh đại diện nếu có
            if (parsedDraft.wordpress.featured_image_id) {
                $('#featured-image-id').val(parsedDraft.wordpress.featured_image_id);
                loadFeaturedImagePreview(parsedDraft.wordpress.featured_image_id);
            }
        }
    } catch (e) {
        console.error('Error parsing draft data for WordPress info:', e);
    }
}
```

### 4. Cải Tiến Xử Lý Lỗi và Thông Báo

**Tăng cường UX:**
- Thêm thông báo trạng thái chi tiết trong quá trình xuất bản
- Cung cấp hướng dẫn khắc phục khi gặp lỗi
- Lưu lại lịch sử xuất bản và lỗi để tham khảo sau

```javascript
/**
 * Xử lý lỗi xuất bản với WordPress
 * @param {Object} error - Đối tượng lỗi từ WordPress API
 */
function handleWordPressPublishError(error) {
    console.error('WordPress publish error:', error);
    
    // Container hiển thị lỗi
    const errorContainer = $('#publish-error-details');
    errorContainer.empty();
    
    // Thông báo mặc định
    let errorMessage = app.lang.error_publishing_to_wordpress;
    
    // Phân tích lỗi từ WordPress
    if (error.responseJSON && error.responseJSON.code) {
        const wpError = error.responseJSON;
        
        // Thêm chi tiết lỗi dựa trên mã lỗi
        switch(wpError.code) {
            case 'rest_cannot_create':
                errorMessage = app.lang.error_wp_permission;
                errorContainer.append(`
                    <div class="alert alert-warning">
                        <strong>${app.lang.permission_issue}:</strong> ${app.lang.error_wp_permission_detail}
                    </div>
                    <div class="troubleshoot-steps">
                        <p><strong>${app.lang.troubleshooting}:</strong></p>
                        <ol>
                            <li>${app.lang.check_wp_credentials}</li>
                            <li>${app.lang.verify_user_capabilities}</li>
                            <li>${app.lang.check_rest_api_enabled}</li>
                        </ol>
                    </div>
                `);
                break;
                
            case 'rest_invalid_param':
                errorMessage = app.lang.error_wp_invalid_data;
                // Hiển thị trường dữ liệu không hợp lệ
                if (wpError.data && wpError.data.params) {
                    const invalidFields = Object.keys(wpError.data.params);
                    errorContainer.append(`
                        <div class="alert alert-warning">
                            <strong>${app.lang.invalid_fields}:</strong> ${invalidFields.join(', ')}
                        </div>
                    `);
                }
                break;
                
            default:
                errorMessage = wpError.message || errorMessage;
                break;
        }
    }
    
    // Hiển thị thông báo lỗi chính
    alert_float('danger', errorMessage);
    
    // Lưu lại lịch sử lỗi
    logPublishError({
        time: new Date().toISOString(),
        message: errorMessage,
        details: error.responseText || JSON.stringify(error),
        draft_id: $('#current-draft-id').val()
    });
}
```

### 5. Tích Hợp với Các Plugin WordPress Phổ Biến

**Hỗ trợ plugin:**
- Yoast SEO / Rank Math
- Advanced Custom Fields
- Elementor / WPBakery Page Builder
- Multilingual plugins (WPML, Polylang)

```javascript
/**
 * Xác định và hỗ trợ các plugin WordPress
 * @param {Array} plugins - Danh sách plugins đã cài đặt trên WordPress
 */
function supportWordPressPlugins(plugins) {
    const pluginContainer = $('#wordpress-plugins-support');
    pluginContainer.empty();
    
    if (!plugins || !plugins.length) {
        return;
    }
    
    // Tạo panel cho cài đặt plugin
    const pluginPanel = $(`
        <div class="panel panel-default mtop20">
            <div class="panel-heading">
                <h4 class="panel-title"><i class="fa fa-plug"></i> ${app.lang.wp_plugin_integration}</h4>
            </div>
            <div class="panel-body" id="wp-plugins-options">
                <!-- Plugin options will be added here -->
            </div>
        </div>
    `);
    
    const optionsContainer = pluginPanel.find('#wp-plugins-options');
    
    // Xử lý từng plugin được phát hiện
    plugins.forEach(plugin => {
        switch(plugin.slug) {
            case 'wordpress-seo': // Yoast SEO
                optionsContainer.append(createYoastSeoFields());
                break;
            case 'seo-by-rank-math':
                optionsContainer.append(createRankMathFields());
                break;
            case 'advanced-custom-fields':
            case 'advanced-custom-fields-pro':
                loadAndCreateACFFields(plugin);
                break;
            case 'wpml-multilingual-cms':
                optionsContainer.append(createWPMLFields());
                break;
            // Thêm các plugin khác nếu cần
        }
    });
    
    pluginContainer.append(pluginPanel);
}
```

### 6. Cập Nhật Ngôn Ngữ Cho Tính Năng WordPress

**Thêm chuỗi ngôn ngữ mới:**

```php
// English
$lang['excerpt'] = 'Excerpt';
$lang['enter_excerpt'] = 'Enter a short excerpt for your post';
$lang['excerpt_help'] = 'A short summary of your post that appears in archives and search results';
$lang['seo_options'] = 'SEO Options';
$lang['seo_title'] = 'SEO Title';
$lang['enter_seo_title'] = 'Enter SEO title';
$lang['meta_description'] = 'Meta Description';
$lang['enter_meta_description'] = 'Enter meta description';
$lang['focus_keyword'] = 'Focus Keyword';
$lang['enter_focus_keyword'] = 'Enter focus keyword';
$lang['error_publishing_to_wordpress'] = 'Error publishing to WordPress';
$lang['error_wp_permission'] = 'WordPress permission issue';
$lang['error_wp_permission_detail'] = 'Your user does not have sufficient permissions to create posts';
$lang['error_wp_invalid_data'] = 'Invalid data for WordPress';
$lang['troubleshooting'] = 'Troubleshooting';
$lang['check_wp_credentials'] = 'Check your WordPress credentials';
$lang['verify_user_capabilities'] = 'Verify your user capabilities';
$lang['check_rest_api_enabled'] = 'Make sure REST API is enabled';
$lang['invalid_fields'] = 'Invalid fields';
$lang['wp_plugin_integration'] = 'WordPress Plugin Integration';

// Vietnamese
$lang['excerpt'] = 'Tóm tắt';
$lang['enter_excerpt'] = 'Nhập tóm tắt ngắn cho bài viết';
$lang['excerpt_help'] = 'Tóm tắt ngắn cho bài viết, hiển thị trong trang lưu trữ và kết quả tìm kiếm';
$lang['seo_options'] = 'Tùy chọn SEO';
$lang['seo_title'] = 'Tiêu đề SEO';
$lang['enter_seo_title'] = 'Nhập tiêu đề SEO';
$lang['meta_description'] = 'Mô tả Meta';
$lang['enter_meta_description'] = 'Nhập mô tả meta';
$lang['focus_keyword'] = 'Từ khóa chính';
$lang['enter_focus_keyword'] = 'Nhập từ khóa chính';
$lang['error_publishing_to_wordpress'] = 'Lỗi khi xuất bản lên WordPress';
$lang['error_wp_permission'] = 'Vấn đề quyền hạn WordPress';
$lang['error_wp_permission_detail'] = 'Người dùng của bạn không có đủ quyền để tạo bài viết';
$lang['error_wp_invalid_data'] = 'Dữ liệu không hợp lệ cho WordPress';
$lang['troubleshooting'] = 'Khắc phục sự cố';
$lang['check_wp_credentials'] = 'Kiểm tra thông tin đăng nhập WordPress';
$lang['verify_user_capabilities'] = 'Xác minh quyền hạn người dùng';
$lang['check_rest_api_enabled'] = 'Đảm bảo REST API được bật';
$lang['invalid_fields'] = 'Trường không hợp lệ';
$lang['wp_plugin_integration'] = 'Tích hợp Plugin WordPress';
```
