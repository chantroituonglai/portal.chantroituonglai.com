# Tích Hợp Topic Controller Vào Draft Writing (Ultimate Editor)

## 1. Tổng Quan

### 1.1. Mục Tiêu
Phát triển tính năng cho phép người dùng chọn Topic Controller khi mở Draft Writing (Ultimate Editor), từ đó tải các danh mục (Categories) và thẻ (Tags) từ website liên quan để chuẩn bị cho việc xuất bản bài viết.

### 1.2. Lợi Ích
- **Quy Trình Liền Mạch**: Kết nối trực tiếp giữa việc soạn thảo nội dung và xuất bản
- **Dữ Liệu Thực Tế**: Hiển thị danh mục và thẻ thực tế từ website đích
- **Tiết Kiệm Thời Gian**: Không cần chuyển đổi giữa nhiều giao diện để xuất bản nội dung

## 2. Yêu Cầu Kỹ Thuật

### 2.1. Các Thành Phần Cần Tích Hợp
- **Draft Writing / Ultimate Editor**: Giao diện soạn thảo nội dung hiện có
- **Topic Controller**: Hệ thống quản lý kết nối với các nền tảng bên ngoài
- **Platform Connectors**: Các kết nối hiện có (WordPress, Haravan, v.v.)

### 2.2. Luồng Dữ Liệu
1. Người dùng mở Draft Writing để soạn thảo nội dung
2. Chọn Topic Controller từ dropdown
3. Hệ thống truy vấn danh mục và thẻ từ nền tảng liên quan
4. Hiển thị dữ liệu trong giao diện Draft Writing
5. Người dùng chọn danh mục/thẻ và hoàn tất xuất bản

## 3. Thiết Kế Giao Diện

### 3.1. Tab "Publish" Trong Draft Writing
Thêm tab "Publish" vào giao diện Draft Writing với:

1. **Chọn Topic Controller**:
   - Dropdown hiển thị danh sách Topic Controllers có sẵn
   - Thông tin nền tảng kết nối (WordPress, Haravan...)
   - Trạng thái kết nối (đã kết nối/chưa kết nối)

2. **Chọn Danh Mục (Categories)**:
   - Hiển thị cấu trúc phân cấp danh mục từ website
   - Hỗ trợ tìm kiếm và lọc danh mục
   - Checkbox cho phép chọn nhiều danh mục (nếu nền tảng hỗ trợ)

3. **Chọn Thẻ (Tags)**:
   - Input với auto-complete từ danh sách thẻ có sẵn
   - Hiển thị các thẻ phổ biến để chọn nhanh
   - Cho phép thêm thẻ mới

4. **Tùy Chọn Xuất Bản**:
   - Trạng thái xuất bản (nháp, chờ duyệt, công khai...)
   - Hẹn giờ xuất bản
   - Các tùy chọn SEO (canonical URL, noindex...)

### 3.2. Thiết Kế Modal và Panels
```html
<!-- Tab "Publish" trong Draft Writing -->
<div id="tab-publish" class="tab-pane">
    <div class="row">
        <!-- Topic Controller Selection -->
        <div class="col-md-12">
            <div class="form-group">
                <label for="topic-controller-select"><?= _l('select_topic_controller'); ?></label>
                <select id="topic-controller-select" class="form-control">
                    <option value=""><?= _l('select_topic_controller'); ?></option>
                    <!-- Dynamic options loaded here -->
                </select>
                <div id="controller-info" class="mtop10 hide">
                    <span class="label label-info">
                        <i class="fa fa-globe"></i> <span id="platform-name"></span>
                    </span>
                    <span class="label label-success">
                        <i class="fa fa-check"></i> <?= _l('connected'); ?>
                    </span>
                </div>
            </div>
        </div>
        
        <!-- Categories & Tags Panels -->
        <div class="col-md-6">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h4 class="panel-title"><?= _l('categories'); ?></h4>
                </div>
                <div class="panel-body">
                    <div class="category-tree-container">
                        <div class="input-group">
                            <input type="text" class="form-control" placeholder="<?= _l('search_categories'); ?>">
                            <span class="input-group-btn">
                                <button class="btn btn-default" type="button"><i class="fa fa-search"></i></button>
                            </span>
                        </div>
                        <div id="categories-tree" class="mtop10">
                            <!-- Categories tree loaded here -->
                            <div class="loading-categories hide">
                                <i class="fa fa-spinner fa-spin"></i> <?= _l('loading_categories'); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h4 class="panel-title"><?= _l('tags'); ?></h4>
                </div>
                <div class="panel-body">
                    <div class="tags-container">
                        <select id="tags-select" class="form-control" multiple="multiple">
                            <!-- Tags loaded here -->
                        </select>
                        <div class="popular-tags mtop10">
                            <label><?= _l('popular_tags'); ?></label>
                            <div id="popular-tags-list">
                                <!-- Popular tags loaded here -->
                                <div class="loading-tags hide">
                                    <i class="fa fa-spinner fa-spin"></i> <?= _l('loading_tags'); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Publish Options -->
        <div class="col-md-12 mtop20">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h4 class="panel-title"><?= _l('publish_options'); ?></h4>
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
                            <div class="form-group">
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
                    
                    <!-- Publish Button -->
                    <div class="row">
                        <div class="col-md-12">
                            <button id="publish-content" class="btn btn-info pull-right">
                                <i class="fa fa-globe"></i> <?= _l('publish'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
```

## 4. Cập Nhật Backend

### 4.1. Chỉnh Sửa Controller và Model

#### 4.1.1. Ultimate_editor.php Controller
Thêm method để lấy danh sách Topic Controllers và dữ liệu liên quan:

```php
/**
 * Get Topic Controllers
 * 
 * @return json
 */
public function get_topic_controllers()
{
    $controllers = $this->topic_controller_model->get();
    $result = [];
    
    foreach ($controllers as $controller) {
        $result[] = [
            'id' => $controller['id'],
            'name' => $controller['controller_name'],
            'platform' => $controller['platform'],
            'connected' => $this->topic_platform_helper->test_platform_connection($controller['id'])
        ];
    }
    
    echo json_encode(['success' => true, 'data' => $result]);
}

/**
 * Get Categories From Platform
 * 
 * @param int $controller_id
 * @return json
 */
public function get_platform_categories($controller_id)
{
    $categories = $this->topic_platform_helper->get_platform_categories($controller_id);
    echo json_encode(['success' => true, 'data' => $categories]);
}

/**
 * Get Tags From Platform
 * 
 * @param int $controller_id
 * @return json
 */
public function get_platform_tags($controller_id)
{
    $tags = $this->topic_platform_helper->get_platform_tags($controller_id);
    echo json_encode(['success' => true, 'data' => $tags]);
}
```

### 4.2. Cập Nhật Helper Functions

Thêm function `get_platform_tags()` vào topic_platform_helper.php (nếu chưa có):

```php
/**
 * Get tags from platform
 * 
 * @param int $controller_id
 * @return array
 */
function get_platform_tags($controller_id)
{
    $CI = &get_instance();
    $CI->load->model('topics/topic_controller_model');
    
    $controller = $CI->topic_controller_model->get($controller_id);
    if (!$controller) {
        return [];
    }
    
    $connector = get_platform_connector($controller->platform);
    if (!$connector) {
        return [];
    }
    
    $config = [
        'url' => $controller->site_url,
        'username' => $controller->api_username,
        'password' => $controller->api_password,
        'api_key' => $controller->api_key
    ];
    
    return $connector->get_tags($config, $controller->blog_id);
}
```

## 5. Cập Nhật Frontend JavaScript

### 5.1. Tạo File draft_writer_publish.js

```javascript
/**
 * Draft Writer Publish Functionality
 */
(function($) {
    'use strict';
    
    // Global state for publish functionality
    window.DraftWriter = window.DraftWriter || {};
    window.DraftWriter.publish = {
        selectedController: null,
        categories: [],
        tags: [],
        publishOptions: {
            status: 'draft',
            scheduleTime: null
        }
    };
    
    // Init function
    function initPublishTab() {
        loadTopicControllers();
        initEventHandlers();
    }
    
    // Load Topic Controllers
    function loadTopicControllers() {
        $.get(admin_url + 'topics/ultimate_editor/get_topic_controllers')
            .done(function(response) {
                if (response.success) {
                    populateControllersDropdown(response.data);
                } else {
                    alert_float('danger', 'Failed to load topic controllers');
                }
            })
            .fail(function() {
                alert_float('danger', 'Network error while loading controllers');
            });
    }
    
    // Populate controllers dropdown
    function populateControllersDropdown(controllers) {
        var $select = $('#topic-controller-select');
        $select.find('option:not(:first)').remove();
        
        controllers.forEach(function(controller) {
            $select.append(
                $('<option>', {
                    value: controller.id,
                    text: controller.name + ' (' + controller.platform + ')',
                    'data-platform': controller.platform,
                    'data-connected': controller.connected
                })
            );
        });
    }
    
    // Load categories from selected controller
    function loadCategories(controllerId) {
        $('#categories-tree').html('');
        $('.loading-categories').removeClass('hide');
        
        $.get(admin_url + 'topics/ultimate_editor/get_platform_categories/' + controllerId)
            .done(function(response) {
                $('.loading-categories').addClass('hide');
                if (response.success) {
                    window.DraftWriter.publish.categories = response.data;
                    renderCategoryTree(response.data);
                } else {
                    alert_float('danger', 'Failed to load categories');
                }
            })
            .fail(function() {
                $('.loading-categories').addClass('hide');
                alert_float('danger', 'Network error while loading categories');
            });
    }
    
    // Load tags from selected controller
    function loadTags(controllerId) {
        $('#tags-select').html('').select2('destroy');
        $('.loading-tags').removeClass('hide');
        
        $.get(admin_url + 'topics/ultimate_editor/get_platform_tags/' + controllerId)
            .done(function(response) {
                $('.loading-tags').addClass('hide');
                if (response.success) {
                    window.DraftWriter.publish.tags = response.data;
                    initTagsSelect(response.data);
                    renderPopularTags(response.data);
                } else {
                    alert_float('danger', 'Failed to load tags');
                }
            })
            .fail(function() {
                $('.loading-tags').addClass('hide');
                alert_float('danger', 'Network error while loading tags');
            });
    }
    
    // Render category tree
    function renderCategoryTree(categories) {
        var $container = $('#categories-tree');
        
        // Build hierarchical structure
        var categoryMap = {};
        categories.forEach(function(category) {
            categoryMap[category.id] = {
                id: category.id,
                name: category.name,
                parent: category.parent_id,
                children: []
            };
        });
        
        // Build tree
        var rootCategories = [];
        Object.values(categoryMap).forEach(function(category) {
            if (category.parent && categoryMap[category.parent]) {
                categoryMap[category.parent].children.push(category);
            } else {
                rootCategories.push(category);
            }
        });
        
        // Render tree
        function renderNode(category) {
            var $node = $('<div class="category-node"></div>');
            var $checkbox = $('<input type="checkbox" name="category[]" value="' + category.id + '">');
            var $label = $('<label>' + category.name + '</label>');
            
            $node.append($checkbox).append($label);
            
            if (category.children && category.children.length > 0) {
                var $children = $('<div class="category-children"></div>');
                category.children.forEach(function(child) {
                    $children.append(renderNode(child));
                });
                $node.append($children);
            }
            
            return $node;
        }
        
        rootCategories.forEach(function(category) {
            $container.append(renderNode(category));
        });
    }
    
    // Initialize tags select2
    function initTagsSelect(tags) {
        var $select = $('#tags-select');
        
        var data = tags.map(function(tag) {
            return {
                id: tag.id,
                text: tag.name
            };
        });
        
        $select.select2({
            placeholder: 'Select or create tags',
            data: data,
            tags: true,
            tokenSeparators: [',']
        });
    }
    
    // Render popular tags
    function renderPopularTags(tags) {
        var $container = $('#popular-tags-list');
        $container.html('');
        
        // Sort by count and get top 10
        var popularTags = tags
            .sort(function(a, b) { return b.count - a.count; })
            .slice(0, 10);
            
        popularTags.forEach(function(tag) {
            var $tag = $('<span class="label label-info popular-tag" data-id="' + tag.id + '">' + 
                tag.name + ' (' + tag.count + ')</span>');
            $container.append($tag);
        });
    }
    
    // Initialize event handlers
    function initEventHandlers() {
        // Controller selection change
        $('#topic-controller-select').on('change', function() {
            var controllerId = $(this).val();
            var $selected = $(this).find('option:selected');
            
            if (controllerId) {
                window.DraftWriter.publish.selectedController = {
                    id: controllerId,
                    platform: $selected.data('platform'),
                    connected: $selected.data('connected')
                };
                
                $('#controller-info').removeClass('hide');
                $('#platform-name').text($selected.data('platform'));
                
                loadCategories(controllerId);
                loadTags(controllerId);
            } else {
                window.DraftWriter.publish.selectedController = null;
                $('#controller-info').addClass('hide');
                $('#categories-tree').html('');
                $('#tags-select').html('').select2('destroy');
                $('#popular-tags-list').html('');
            }
        });
        
        // Click on popular tag
        $(document).on('click', '.popular-tag', function() {
            var tagId = $(this).data('id');
            var tagText = $(this).text().split(' (')[0];
            
            // Check if tag is already selected
            var existingOption = $('#tags-select').find('option[value="' + tagId + '"]');
            
            if (existingOption.length === 0) {
                // Add new option and select it
                var newOption = new Option(tagText, tagId, true, true);
                $('#tags-select').append(newOption);
            }
            
            // Trigger change event to refresh Select2
            $('#tags-select').trigger('change');
        });
        
        // Publish button click
        $('#publish-content').on('click', function() {
            publishContent();
        });
        
        // Post status change
        $('#post-status').on('change', function() {
            window.DraftWriter.publish.publishOptions.status = $(this).val();
            
            // Enable/disable schedule time based on status
            if ($(this).val() === 'publish') {
                $('#schedule-time').closest('.form-group').removeClass('hide');
            } else {
                $('#schedule-time').closest('.form-group').addClass('hide');
                window.DraftWriter.publish.publishOptions.scheduleTime = null;
            }
        });
        
        // Schedule time change
        $('#schedule-time').on('change', function() {
            window.DraftWriter.publish.publishOptions.scheduleTime = $(this).val();
        });
    }
    
    // Publish content to platform
    function publishContent() {
        if (!window.DraftWriter.publish.selectedController) {
            alert_float('warning', 'Please select a topic controller');
            return;
        }
        
        // Get selected categories
        var selectedCategories = [];
        $('#categories-tree input:checked').each(function() {
            selectedCategories.push($(this).val());
        });
        
        // Get selected tags
        var selectedTags = $('#tags-select').val() || [];
        
        // Get editor content
        var content = window.DraftWriter.editor ? window.DraftWriter.editor.getContent() : '';
        var title = $('#draft-title').val();
        var description = $('#draft-description').val();
        
        if (!title || !content) {
            alert_float('warning', 'Title and content are required');
            return;
        }
        
        // Prepare publish data
        var publishData = {
            controller_id: window.DraftWriter.publish.selectedController.id,
            topic_id: window.DraftWriter.metadata.topic_id,
            title: title,
            content: content,
            excerpt: description,
            categories: selectedCategories,
            tags: selectedTags,
            status: window.DraftWriter.publish.publishOptions.status,
            schedule_time: window.DraftWriter.publish.publishOptions.scheduleTime
        };
        
        // Show loading
        var $btn = $('#publish-content');
        var btnHtml = $btn.html();
        $btn.html('<i class="fa fa-spinner fa-spin"></i> Publishing...').prop('disabled', true);
        
        // Send publish request
        $.ajax({
            url: admin_url + 'topics/ultimate_editor/publish_to_platform',
            type: 'POST',
            data: publishData,
            dataType: 'json',
            success: function(response) {
                $btn.html(btnHtml).prop('disabled', false);
                
                if (response.success) {
                    alert_float('success', response.message || 'Content published successfully');
                    
                    // If post was published, show link
                    if (response.data && response.data.permalink) {
                        var resultHtml = '<div class="alert alert-success">' +
                            'Published successfully. <a href="' + response.data.permalink + 
                            '" target="_blank">View post</a>' +
                            '</div>';
                        $('#publish-result').html(resultHtml);
                    }
                } else {
                    alert_float('danger', response.message || 'Failed to publish content');
                }
            },
            error: function() {
                $btn.html(btnHtml).prop('disabled', false);
                alert_float('danger', 'Network error while publishing');
            }
        });
    }
    
    // Initialize on document ready
    $(function() {
        // Add tab to draft writer UI
        var $tabContainer = $('.draft-writer-tabs');
        var $contentContainer = $('.tab-content');
        
        if ($tabContainer.length) {
            // Add tab nav item
            $tabContainer.append(
                '<li>' +
                '<a href="#tab-publish" data-toggle="tab">' +
                '<i class="fa fa-globe"></i> Publish' +
                '</a>' +
                '</li>'
            );
            
            // Add tab content container (will be populated from template)
            $contentContainer.append('<div id="tab-publish" class="tab-pane"></div>');
            
            // Load template
            $.get(site_url + 'modules/topics/views/topics/ultimate_editor/includes/tab_publish.php')
                .done(function(html) {
                    $('#tab-publish').html(html);
                    initPublishTab();
                })
                .fail(function() {
                    $('#tab-publish').html('<div class="alert alert-danger">Failed to load publish tab</div>');
                });
        }
    });
    
})(jQuery);
```

## 6. Tạo File View Tab Publish

Tạo file `views/topics/ultimate_editor/includes/tab_publish.php`:

```php
<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="row">
    <!-- Topic Controller Selection -->
    <div class="col-md-12">
        <div class="form-group">
            <label for="topic-controller-select"><?= _l('select_topic_controller'); ?></label>
            <select id="topic-controller-select" class="form-control">
                <option value=""><?= _l('select_topic_controller'); ?></option>
                <!-- Dynamic options loaded here -->
            </select>
            <div id="controller-info" class="mtop10 hide">
                <span class="label label-info">
                    <i class="fa fa-globe"></i> <span id="platform-name"></span>
                </span>
                <span class="label label-success">
                    <i class="fa fa-check"></i> <?= _l('connected'); ?>
                </span>
            </div>
        </div>
    </div>
    
    <!-- Categories & Tags Panels -->
    <div class="col-md-6">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h4 class="panel-title"><?= _l('categories'); ?></h4>
            </div>
            <div class="panel-body">
                <div class="category-tree-container">
                    <div class="input-group">
                        <input type="text" class="form-control" placeholder="<?= _l('search_categories'); ?>">
                        <span class="input-group-btn">
                            <button class="btn btn-default" type="button"><i class="fa fa-search"></i></button>
                        </span>
                    </div>
                    <div id="categories-tree" class="mtop10">
                        <!-- Categories tree loaded here -->
                        <div class="loading-categories hide">
                            <i class="fa fa-spinner fa-spin"></i> <?= _l('loading_categories'); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h4 class="panel-title"><?= _l('tags'); ?></h4>
            </div>
            <div class="panel-body">
                <div class="tags-container">
                    <select id="tags-select" class="form-control" multiple="multiple">
                        <!-- Tags loaded here -->
                    </select>
                    <div class="popular-tags mtop10">
                        <label><?= _l('popular_tags'); ?></label>
                        <div id="popular-tags-list">
                            <!-- Popular tags loaded here -->
                            <div class="loading-tags hide">
                                <i class="fa fa-spinner fa-spin"></i> <?= _l('loading_tags'); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Publish Options -->
    <div class="col-md-12 mtop20">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h4 class="panel-title"><?= _l('publish_options'); ?></h4>
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
                        <div class="form-group">
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
                
                <div id="publish-result"></div>
                
                <!-- Publish Button -->
                <div class="row">
                    <div class="col-md-12">
                        <button id="publish-content" class="btn btn-info pull-right">
                            <i class="fa fa-globe"></i> <?= _l('publish'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
```

## 7. Cập Nhật CSS

Thêm vào file `assets/css/draft_writer.css`:

```css
/* Publish Tab Styles */
#tab-publish .category-tree-container {
    max-height: 300px;
    overflow-y: auto;
}

#tab-publish .category-node {
    margin-bottom: 5px;
}

#tab-publish .category-children {
    margin-left: 20px;
    border-left: 1px solid #ddd;
    padding-left: 10px;
}

#tab-publish .popular-tag {
    display: inline-block;
    margin-right: 5px;
    margin-bottom: 5px;
    cursor: pointer;
}

#tab-publish .popular-tag:hover {
    background-color: #18ACFE;
}

#tab-publish #publish-result {
    margin: 10px 0;
}

#tab-publish #controller-info .label {
    display: inline-block;
    margin-right: 5px;
    font-size: 11px;
}
```

## 8. Cập Nhật Ngôn Ngữ

### 8.1. English
Thêm vào file `language/english/topics_lang.php` hoặc `language/english/ultimate_editor_lang.php`:

```php
$lang['select_topic_controller'] = 'Select Topic Controller';
$lang['connected'] = 'Connected';
$lang['categories'] = 'Categories';
$lang['tags'] = 'Tags';
$lang['search_categories'] = 'Search categories';
$lang['loading_categories'] = 'Loading categories...';
$lang['loading_tags'] = 'Loading tags...';
$lang['popular_tags'] = 'Popular Tags';
$lang['publish_options'] = 'Publish Options';
$lang['post_status'] = 'Post Status';
$lang['draft'] = 'Draft';
$lang['pending_review'] = 'Pending Review';
$lang['publish'] = 'Publish';
$lang['schedule_time'] = 'Schedule Time';
```

### 8.2. Vietnamese
Thêm vào file `language/vietnamese/topics_lang.php` hoặc `language/vietnamese/ultimate_editor_lang.php`:

```php
$lang['select_topic_controller'] = 'Chọn Topic Controller';
$lang['connected'] = 'Đã kết nối';
$lang['categories'] = 'Danh mục';
$lang['tags'] = 'Thẻ';
$lang['search_categories'] = 'Tìm kiếm danh mục';
$lang['loading_categories'] = 'Đang tải danh mục...';
$lang['loading_tags'] = 'Đang tải thẻ...';
$lang['popular_tags'] = 'Thẻ Phổ Biến';
$lang['publish_options'] = 'Tùy Chọn Xuất Bản';
$lang['post_status'] = 'Trạng Thái Bài Viết';
$lang['draft'] = 'Bản Nháp';
$lang['pending_review'] = 'Chờ Duyệt';
$lang['publish'] = 'Xuất Bản';
$lang['schedule_time'] = 'Hẹn Giờ Xuất Bản';
```

## 9. Tích Hợp Vào Ultimate Editor

### 9.1. Cập Nhật views/topics/ultimate_editor/index.php
Thêm reference đến script JavaScript mới:

```php
<!-- Include Publish functionality -->
<?php echo app_script(module_dir_url('topics') . 'assets/js/draft_writer_publish.js'); ?>
```

## 10. Kế Hoạch Triển Khai

### 10.1. Bước Triển Khai
1. Cập nhật helper function `get_platform_tags()` để sử dụng phương thức `get_tags()` đã có trong connector
2. Tạo các endpoint API mới trong Ultimate Editor controller
3. Tạo tab Publish và các view liên quan
4. Phát triển JavaScript và CSS
5. Cập nhật file ngôn ngữ
6. Kiểm thử và sửa lỗi

### 10.2. Kiểm Thử
- Kiểm tra kết nối với các loại Topic Controller khác nhau
- Kiểm tra hiển thị danh mục đúng cấu trúc phân cấp
- Kiểm tra tìm kiếm và chọn thẻ
- Kiểm tra quy trình xuất bản với các trạng thái khác nhau

### 10.3. Các Vấn Đề Cần Lưu Ý
- Đảm bảo xử lý lỗi kết nối đúng cách
- Đảm bảo hiển thị thông báo rõ ràng cho người dùng
- Hỗ trợ đa ngôn ngữ cho tất cả các chuỗi mới
- Xử lý tốt các trường hợp không có dữ liệu
