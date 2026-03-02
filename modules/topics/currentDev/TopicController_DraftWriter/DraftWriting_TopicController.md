# Tích Hợp Topic Controller Vào Draft Writing (Ultimate Editor)

> **Tài Liệu Liên Quan:**
> 1. Kế hoạch tổng thể: [topicControllerxDraftWrtingxUltimateEditorMasterPLan.md](../development_instructors/topicControllerxDraftWrtingxUltimateEditorMasterPLan.md)
> 2. Hướng dẫn Draft Writing: [displayDraftWriting.md](../development_instructors/displayDraftWriting.md) 
> 3. Mã nguồn Draft Writing: [displayDraftWriting-code.md](../development_instructors/displayDraftWriting-code.md)
> 4. Tích hợp TopicController và DraftWriting: [topicControllerxDraftWrtingxUltimateEditor.md](../development_instructors/topicControllerxDraftWrtingxUltimateEditor.md)
> 5. Phương án phát triển TopicController: [TopicController_ViewUpdate.md](../development_instructors/TopicController_ViewUpdate.md)

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
                        <div class="col-md-8">
                            <div class="form-group">
                                <label for="post-status"><?= _l('post_status'); ?></label>
                                <select id="post-status" class="form-control">
                                    <option value="draft"><?= _l('draft'); ?></option>
                                    <option value="pending"><?= _l('pending_review'); ?></option>
                                    <option value="publish"><?= _l('publish'); ?></option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
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

### 5.1. Tích Hợp Chức Năng Xuất Bản Vào Ultimate Editor

Tất cả chức năng xuất bản đã được tích hợp trực tiếp vào file `ultimate_editor.js`. Mô-đun này có các chức năng chính:

1. **Khởi tạo Tab Xuất Bản**:
   - `initPublishTab()`: Khởi tạo tab xuất bản với các event handler cần thiết
   - `loadTopicControllers()`: Tải danh sách Topic Controllers
   - `initDatepicker()`: Khởi tạo date picker cho tùy chọn hẹn giờ xuất bản

2. **Xử Lý Dữ Liệu Từ Platform**:
   - `loadCategories()`: Tải danh mục từ platform
   - `loadTags()`: Tải thẻ từ platform
   - `renderCategoryTree()`: Hiển thị cấu trúc phân cấp danh mục
   - `initTagsSelect()`: Khởi tạo Select2 cho thẻ
   - `renderPopularTags()`: Hiển thị các thẻ phổ biến

3. **Xuất Bản Nội Dung**:
   - `publishContent()`: Gửi dữ liệu bài viết đến platform qua API
   - `initPublishEventHandlers()`: Xử lý các sự kiện liên quan đến xuất bản

4. **Tích Hợp Với Editor Hiện Có**:
   - Chức năng publish được tích hợp vào hệ thống event handler hiện có
   - Sử dụng dữ liệu từ editor hiện có (nội dung, tiêu đề, mô tả)
   - Cập nhật giao diện người dùng khi hoàn tất xuất bản

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

Chức năng xuất bản đã được tích hợp trực tiếp vào `ultimate_editor.js`, do đó không cần thêm tham chiếu JavaScript riêng biệt nữa. Các chức năng sẽ tự động khởi tạo khi trang được tải.

## 10. Kế Hoạch Triển Khai

### 10.1. Bước Triển Khai
1. Cập nhật helper function `get_platform_tags()` để sử dụng phương thức `get_tags()` đã có trong connector
2. Tạo các endpoint API mới trong Ultimate Editor controller
3. Tạo tab Publish và các view liên quan
4. Cập nhật JavaScript bằng cách tích hợp mã vào `ultimate_editor.js`
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

## 11. Cấu Trúc Database và Mối Quan Hệ

### 11.1. Sơ Đồ Mối Quan Hệ Database

```
+----------------+       +---------------------+       +-------------------+
| tbltopics      |       | tbltopic_master     |       | tbltopic_controller|
+----------------+       +---------------------+       +-------------------+
| id (PK)        |       | id (PK)             |       | id (PK)           |
| topicid (FK)   |<----->| topicid (UNI)       |<----->| controller_id (FK)|
| topictitle     |       | topictitle          |       | topic_id (FK)     |
| data           |       | status              |       | staff_id          |
| ...            |       | controller_id (FK)  |------>| datecreated       |
+----------------+       +---------------------+       +-------------------+
                                   |                            |
                                   |                            |
                                   v                            v
                         +-------------------+         +-------------------+
                         | tbltopic_controllers|       | tbltopic_controller_categories|
                         +-------------------+         +-------------------+
                         | id (PK)           |         | id (PK)           |
                         | status            |         | controller_id (FK)|
                         | site              |         | category_id       |
                         | platform          |         | parent_id         |
                         | login_config      |         | name              |
                         | ...               |         | ...               |
                         +-------------------+         +-------------------+
                                  |
                                  |
                                  v
                         +-------------------+
                         | tbltopic_controller_tags|
                         +-------------------+
                         | id (PK)           |
                         | controller_id (FK)|
                         | name              |
                         | ...               |
                         +-------------------+
```

### 11.2. Giải Thích Mối Quan Hệ

1. **tbltopics** và **tbltopic_master**:
   - `tbltopics.topicid` liên kết với `tbltopic_master.topicid`
   - Một topic trong `tbltopic_master` có thể có nhiều phiên bản trong `tbltopics` với các dữ liệu, trạng thái và mục đích khác nhau

2. **tbltopic_master** và **tbltopic_controller**:
   - `tbltopic_master.id` liên kết với `tbltopic_controller.topic_id`
   - Quan hệ nhiều-nhiều: Một topic có thể thuộc về nhiều controller và một controller có thể chứa nhiều topic
   - `tbltopic_master` cũng có trường `controller_id` trực tiếp để hỗ trợ truy vấn nhanh (legacy hoặc tối ưu)

3. **tbltopic_controller** và **tbltopic_controllers**:
   - `tbltopic_controller.controller_id` liên kết với `tbltopic_controllers.id`
   - Bảng `tbltopic_controllers` lưu trữ thông tin đầy đủ về controller (platform, thông tin đăng nhập, API, v.v.)

4. **tbltopic_controllers** và **tbltopic_controller_categories**:
   - `tbltopic_controllers.id` liên kết với `tbltopic_controller_categories.controller_id`
   - Một controller có thể có nhiều danh mục
   - Danh mục có cấu trúc phân cấp thông qua trường `parent_id`

5. **tbltopic_controllers** và **tbltopic_controller_tags**:
   - `tbltopic_controllers.id` liên kết với `tbltopic_controller_tags.controller_id`
   - Một controller có thể có nhiều thẻ

### 11.3. Các Model Chính Làm Việc Với Cấu Trúc Này

1. **Topic_controller_model**:
   - `get_controller_by_topic($topic_id)`: Lấy thông tin controller cho một topic cụ thể
   - `topic_has_controller($topic_id)`: Kiểm tra một topic có thuộc controller nào không
   - `add_topics($controller_id, $topic_ids)`: Thêm nhiều topic vào một controller
   - `get_topic_ids_by_controller($controller_id)`: Lấy danh sách topic thuộc một controller
   - `remove_topics($controller_id, $topic_ids)`: Xóa topic khỏi controller
   - `get_tags($controller_id)`: Lấy danh sách thẻ từ một controller

2. **Topic_platform_helper**:
   - `get_platform_connector($platform)`: Lấy connector cho nền tảng cụ thể
   - `test_platform_connection($controller_id)`: Kiểm tra kết nối với nền tảng
   - `get_platform_categories($controller_id)`: Lấy danh sách danh mục từ nền tảng
   - `get_platform_tags($controller_id)`: Lấy danh sách thẻ từ nền tảng 
   - `publish_platform_post($controller_id, $post)`: Xuất bản bài viết lên nền tảng

### 11.4. Lưu Ý Quan Trọng Khi Phát Triển

- Thao tác với `tbltopic_controller` nên được thực hiện thông qua các phương thức của `Topic_controller_model`
- Khi lấy thông tin controller cho một topic, nên sử dụng `get_controller_by_topic()` thay vì truy vấn trực tiếp
- Dữ liệu đăng nhập và cấu hình API được lưu trữ trong `login_config` dưới dạng JSON và nên được giải mã trước khi sử dụng
- Khi xuất bản nội dung, cần phải chuyển đổi dữ liệu từ định dạng của Draft Writing sang định dạng của platform đích
