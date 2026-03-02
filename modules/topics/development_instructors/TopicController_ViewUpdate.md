# Phương Án Phát Triển Chức Năng TopicController
Làm xong Task nào thì check vào dòng đó, đồng thời remind lại yêu cầu đọc checklist và đánh dấu mỗi prompt
## Danh Sách Kiểm Tra (Checklist)

- [ ] **1. Tổng Quan** (dòng 24)
  - [ ] 1.1. Mục Tiêu (dòng 26)
  - [ ] 1.2. Phạm Vi (dòng 44)
  - [ ] 1.3. Cân Nhắc Về Đường Dẫn và Tích Hợp (dòng 62)
  - [ ] 1.4. Tích Hợp Với Platform Connectors Hiện Có (dòng 95)

- [x] **2. Cấu Trúc Cơ Sở Dữ Liệu** (dòng 116)
  - [x] 2.1. Bảng Dữ Liệu Mới (dòng 118)
  - [x] 2.2. Mối Quan Hệ Dữ Liệu (dòng 226)
  - [x] 2.3. Cập Nhật Module Version (dòng 252)

- [x] **3. Phát Triển Backend** (dòng 267)
  - [x] 3.1. Model (dòng 269)
  - [x] 3.2. Controller (dòng 273)
  - [x] 3.3. API Integration (dòng 277)
  - [ ] 3.4. Tích Hợp MCP (dòng 281) -> Bỏ qua

- [x] **4. Phát Triển Frontend** (dòng 399)
  - [x] 4.1. View (dòng 401)
  - [x] 4.2. JavaScript (dòng 405)
  - [x] 4.3. CSS (dòng 409)

- [x] **5. Quy Trình Triển Khai** (dòng 413)
  - [x] 5.1. Migration (dòng 415)
  - [ ] 5.2. Testing (dòng 419)
  - [ ] 5.3. Deployment (dòng 423)

- [ ] **6. Chi Tiết Kỹ Thuật** (dòng 432)
  - [x] 6.1. Cấu Trúc Bảng Dữ Liệu (dòng 434)
  - [x] 6.2. API Endpoints (dòng 438)
  - [x] 6.3. Xử Lý Dữ Liệu (dòng 442)

- [ ] **7. Tính Năng Mở Rộng** (dòng 446)
  - [ ] 7.1. Đồng Bộ Hóa Tự Động (dòng 448)
  - [ ] 7.2. Tùy Chỉnh Hiển Thị (dòng 452)

- [ ] **8. Quy Trình Phát Triển và Triển Khai** (dòng 456)
  - [ ] 8.1. Quy Trình Phát Triển (dòng 458)
  - [ ] 8.2. Hướng Dẫn Đề Xuất Thay Đổi (dòng 486)
  - [ ] 8.3. Quy Trình Kiểm Tra và Triển Khai (dòng 529)

- [ ] **9. Kết Luận và Biện Pháp Bảo Mật** (dòng 547)
  - [ ] 9.1. Biện Pháp Bảo Mật (dòng 553)

- [x] **10. Cập Nhật Ngôn Ngữ** (dòng 659)
  - [x] 10.1. Tổ Chức File Ngôn Ngữ (dòng 661)
  - [x] 10.2. Đăng Ký File Ngôn Ngữ Mới (dòng 690)
  - [x] 10.3. Quy Ước Đặt Tên Chuỗi Ngôn Ngữ (dòng 706)
  - [x] 10.4. Tạo File Ngôn Ngữ Mới (dòng 733)

- [x] **11. Tổ Chức View Con** (dòng 761)
  - [x] 11.1. Cấu Trúc Thư Mục View Con (dòng 763)
  - [x] 11.2. Tích Hợp View Con Vào View Chính (dòng 788)
  - [x] 11.3. Ví Dụ View Con: Tab Categories (dòng 819)

- [x] **12. Ưu Tiên Xây Dựng WordPress Connector** (dòng 869)
  - [x] 12.1. Mở Rộng WordPress Connector Hiện Có (dòng 871)
  - [x] 12.2. Cập Nhật WordPress Connector (dòng 879)
  - [x] 12.3. Cập Nhật Helper Functions (dòng 1112)
  - [x] 12.4. Cập Nhật Model (dòng 1311)

- [x] **13. Ví Dụ Triển Khai Tab Categories** (dòng 1474)
  - [x] 13.1. Tạo File View Tab Categories (dòng 1476)
  - [x] 13.2. Thêm JavaScript Xử Lý Tab Categories (dòng 1526)
  - [x] 13.3. Thêm CSS Cho Tab Categories (dòng 1734)
  - [x] 13.4. Cập Nhật Controller (dòng 1815)

- [x] **14. Tích Hợp Với Platform Connectors Hiện Có** (dòng 1925)
  - [x] 14.1. Tổng Quan Về Platform Connectors (dòng 1927)
  - [x] 14.2. Sử Dụng Helper Functions (dòng 1991)
  - [x] 14.3. Tạo Connector Mới (dòng 2090)
  - [x] 14.4. Ví Dụ: WordPress Connector (dòng 2330)
  - [x] 14.5. Xử Lý Lỗi và Bảo Mật (dòng 2509)
  - [x] 14.6. Mở Rộng Chức Năng (dòng 2583)

- [ ] **15. Triển Khai Tab Tags** (dòng 2650)
  - [ ] 15.1. Tạo File View Tab Tags (dòng 2652)
  - [ ] 15.2. Thêm JavaScript Xử Lý Tab Tags (dòng 2702)
  - [ ] 15.3. Thêm CSS Cho Tab Tags (dòng 2910)
  - [ ] 15.4. Cập Nhật Controller (dòng 2980)
  - [ ] 15.5. Mối Quan Hệ Giữa Blog và Tags (dòng 3100)

## 1. Tổng Quan

### 1.1. Mục Tiêu

Dự án này nhằm mở rộng chức năng của module TopicController trong Perfex CRM để có thể:

1. **Tải và hiển thị dữ liệu từ nền tảng bên ngoài**:
   - Danh mục bài viết (Categories): Hiển thị cấu trúc phân cấp các danh mục của website
   - Danh sách thẻ (Tags): Hiển thị các thẻ được sử dụng trong các bài viết
   - Danh sách bài viết (Blogs): Hiển thị tổng quan các bài viết (không bao gồm nội dung chi tiết)

2. **Tích hợp vào giao diện hiện có**: 
   - Hiển thị dữ liệu trong trang `/controllers/view/{id}` 
   - Thêm các tab mới bên cạnh tab "Related Topics" hiện có
   - Thiết kế giao diện phù hợp với thiết kế chung của Perfex CRM

3. **Lưu trữ dữ liệu**:
   - Tạo cơ chế lưu trữ dữ liệu từ các nền tảng bên ngoài vào database
   - Tuân thủ chuẩn của Perfex CRM về migration và cấu trúc database

### 1.2. Phạm Vi

1. **Hỗ trợ đa nền tảng**:
   - **WordPress**: Sử dụng WordPress REST API để lấy dữ liệu về categories, tags và posts
   - **Haravan**: Sử dụng Haravan API để lấy dữ liệu về collections, tags và articles
   - **Shopify**: Sử dụng Shopify API để lấy dữ liệu về collections, tags và articles/blogs
   - **Nền tảng khác**: Thiết kế hệ thống có khả năng mở rộng để hỗ trợ thêm các nền tảng trong tương lai

2. **Xử lý dữ liệu theo đặc thù của từng nền tảng**:
   - Mỗi nền tảng có cấu trúc dữ liệu khác nhau, cần xây dựng bộ adapter để chuẩn hóa dữ liệu
   - Hiển thị dữ liệu phù hợp với đặc thù của từng nền tảng (ví dụ: WordPress có categories và tags, trong khi Shopify có collections và tags)

3. **Giao diện người dùng**:
   - Thiết kế giao diện trực quan, dễ sử dụng
   - Hỗ trợ tính năng tìm kiếm và lọc dữ liệu
   - Hiển thị thông tin theo dạng phân cấp (đặc biệt là với danh mục)
   - Tích hợp nút "Save State" để lưu lại trạng thái hiện tại của dữ liệu

### 1.3. Cân Nhắc Về Đường Dẫn và Tích Hợp

1. **Nhận Thức Về FCPATH**:
   - FCPATH là hằng số tham chiếu đến thư mục gốc của cài đặt Perfex CRM, không phải thư mục module.
   - Tránh hardcode đường dẫn tương đối từ thư mục gốc module trừ khi cần thiết.

2. **Sử Dụng Hàm Đường Dẫn Module**:
   - Khi tham chiếu đến các file trong module, sử dụng các hàm đường dẫn module của Perfex CRM:
     ```php
     module_dir_path('topics') // Đường dẫn đến thư mục module
     module_libs_path('topics') // Đường dẫn đến thư mục libs của module
     ```
   - Các hàm này đảm bảo tính tương thích trên các môi trường server khác nhau.

3. **Include File Trực Tiếp**:
   - Nếu cần include file trực tiếp, xây dựng đường dẫn cẩn thận:
     ```php
     // Kiểm tra function_exists để đảm bảo tương thích
     if (function_exists('module_dir_path')) {
         require_once(module_dir_path('topics') . 'includes/platform_connectors/wordpress_connector.php');
     } else {
         require_once(FCPATH . 'modules/topics/includes/platform_connectors/wordpress_connector.php');
     }
     ```
   - Luôn kiểm tra đường dẫn để tránh lỗi "file not found", đặc biệt khi module được triển khai trên các server khác nhau.

4. **Đường Dẫn Asset**:
   - Đối với asset front-end (CSS, JS), sử dụng `module_dir_url('topics')` để tạo URL chính xác:
     ```php
     $css_url = module_dir_url('topics') . 'assets/css/draft_writer.css';
     ```
   - Đảm bảo URL hoạt động bất kể cấu hình server.

### 1.4. Tích Hợp Với Platform Connectors Hiện Có

1. **Sử Dụng Interface Hiện Có**:
   - Module đã có sẵn interface `PlatformConnectorInterface` trong `includes/platform_connectors/platform_connector_interface.php`.
   - Các connector hiện tại (WordPress, Haravan) đã implement interface này.
   - Khi mở rộng, tuân thủ interface để đảm bảo tính nhất quán.

2. **Các Method Cần Implement**:
   - `testConnection(array $config)`: Kiểm tra kết nối đến platform
   - `getCategories(array $config)`: Lấy danh sách categories từ platform
   - `publishPost(array $config, array $post)`: Đăng bài viết lên platform
   - `getLoginFields()`: Lấy danh sách các trường đăng nhập cần thiết
   - `validateConfig(array $config)`: Xác thực cấu hình đăng nhập

3. **Sử Dụng Helper Functions**:
   - Sử dụng các helper function trong `helpers/topic_platform_helper.php`:
     - `get_platform_connector($platform)`: Lấy instance của connector
     - `test_platform_connection($controller_id)`: Kiểm tra kết nối
     - `get_platform_categories($controller_id)`: Lấy categories từ platform
     - `publish_platform_post($controller_id, $post)`: Đăng bài viết

## 2. Cấu Trúc Cơ Sở Dữ Liệu

### 2.1. Bảng Dữ Liệu Mới

#### 2.1.1. `tbltopic_controller_categories`

Bảng này lưu trữ thông tin về các danh mục bài viết từ các nền tảng bên ngoài.

```sql
CREATE TABLE `tbltopic_controller_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `controller_id` int(11) NOT NULL,
  `category_id` varchar(100) NOT NULL COMMENT 'ID của danh mục trên nền tảng gốc',
  `parent_id` varchar(100) DEFAULT NULL COMMENT 'ID của danh mục cha trên nền tảng gốc',
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `count` int(11) DEFAULT 0 COMMENT 'Số lượng bài viết trong danh mục',
  `url` varchar(255) DEFAULT NULL COMMENT 'URL của danh mục trên website',
  `image_url` text DEFAULT NULL COMMENT 'URL hình ảnh đại diện (nếu có)',
  `raw_data` longtext DEFAULT NULL COMMENT 'Dữ liệu gốc dạng JSON',
  `last_sync` datetime DEFAULT NULL COMMENT 'Thời gian đồng bộ cuối cùng',
  `datecreated` datetime DEFAULT current_timestamp(),
  `dateupdated` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `controller_id` (`controller_id`),
  KEY `category_id` (`category_id`),
  KEY `parent_id` (`parent_id`),
  CONSTRAINT `tbltopic_controller_categories_ibfk_1` FOREIGN KEY (`controller_id`) REFERENCES `tbltopic_controllers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
```

#### 2.1.2. `tbltopic_controller_tags`

Bảng này lưu trữ thông tin về các thẻ từ các nền tảng bên ngoài.

```sql
CREATE TABLE `tbltopic_controller_tags` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `controller_id` int(11) NOT NULL,
  `tag_id` varchar(100) NOT NULL COMMENT 'ID của thẻ trên nền tảng gốc',
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `count` int(11) DEFAULT 0 COMMENT 'Số lượng bài viết có gắn thẻ này',
  `url` varchar(255) DEFAULT NULL COMMENT 'URL của thẻ trên website',
  `raw_data` longtext DEFAULT NULL COMMENT 'Dữ liệu gốc dạng JSON',
  `last_sync` datetime DEFAULT NULL COMMENT 'Thời gian đồng bộ cuối cùng',
  `datecreated` datetime DEFAULT current_timestamp(),
  `dateupdated` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `controller_id` (`controller_id`),
  KEY `tag_id` (`tag_id`),
  CONSTRAINT `tbltopic_controller_tags_ibfk_1` FOREIGN KEY (`controller_id`) REFERENCES `tbltopic_controllers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
```

#### 2.1.3. `tbltopic_controller_blogs`

Bảng này lưu trữ thông tin về các bài viết từ các nền tảng bên ngoài.

```sql
CREATE TABLE `tbltopic_controller_blogs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `controller_id` int(11) NOT NULL,
  `blog_id` varchar(100) NOT NULL COMMENT 'ID của bài viết trên nền tảng gốc',
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `excerpt` text DEFAULT NULL COMMENT 'Tóm tắt bài viết',
  `status` varchar(20) DEFAULT 'publish' COMMENT 'Trạng thái bài viết (publish, draft, v.v.)',
  `author` varchar(255) DEFAULT NULL COMMENT 'Tác giả bài viết',
  `featured_image` text DEFAULT NULL COMMENT 'URL hình ảnh đại diện',
  `url` varchar(255) DEFAULT NULL COMMENT 'URL của bài viết trên website',
  `date_published` datetime DEFAULT NULL,
  `date_modified` datetime DEFAULT NULL,
  `comment_count` int(11) DEFAULT 0,
  `view_count` int(11) DEFAULT 0 COMMENT 'Số lượt xem (nếu có)',
  `raw_data` longtext DEFAULT NULL COMMENT 'Dữ liệu gốc dạng JSON',
  `last_sync` datetime DEFAULT NULL COMMENT 'Thời gian đồng bộ cuối cùng',
  `datecreated` datetime DEFAULT current_timestamp(),
  `dateupdated` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `controller_id` (`controller_id`),
  KEY `blog_id` (`blog_id`),
  KEY `status` (`status`),
  CONSTRAINT `tbltopic_controller_blogs_ibfk_1` FOREIGN KEY (`controller_id`) REFERENCES `tbltopic_controllers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
```

#### 2.1.4. `tbltopic_controller_blog_relationships`

Bảng này lưu trữ mối quan hệ giữa bài viết với danh mục và thẻ.

```sql
CREATE TABLE `tbltopic_controller_blog_relationships` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `controller_id` int(11) NOT NULL,
  `blog_id` varchar(100) NOT NULL COMMENT 'ID của bài viết trên nền tảng gốc',
  `type` enum('category','tag') NOT NULL COMMENT 'Loại quan hệ',
  `term_id` varchar(100) NOT NULL COMMENT 'ID của danh mục hoặc thẻ',
  `datecreated` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_relationship` (`controller_id`,`blog_id`,`type`,`term_id`),
  KEY `controller_id` (`controller_id`),
  KEY `blog_id` (`blog_id`),
  KEY `term_id` (`term_id`),
  CONSTRAINT `tbltopic_controller_blog_relationships_ibfk_1` FOREIGN KEY (`controller_id`) REFERENCES `tbltopic_controllers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
```

### 2.2. Mối Quan Hệ Dữ Liệu

#### 2.2.1. Quan hệ giữa Controller và Categories/Tags/Blogs

- Mỗi controller (đại diện cho một website) có thể có nhiều categories, tags và blogs
- Mối quan hệ này được thiết lập thông qua trường `controller_id` trong các bảng dữ liệu mới
- Khi xóa một controller, tất cả categories, tags và blogs liên quan cũng sẽ bị xóa (CASCADE DELETE)

#### 2.2.2. Mối quan hệ giữa Blogs, Categories và Tags

- Mỗi blog có thể thuộc về nhiều categories và có nhiều tags
- Mối quan hệ này được lưu trữ trong bảng `tbltopic_controller_blog_relationships`
- Quan hệ được phân biệt bằng trường `type` (category hoặc tag)

#### 2.2.3. Cấu trúc phân cấp của Categories

- Categories có thể có cấu trúc phân cấp (categories cha-con)
- Quan hệ này được thể hiện thông qua trường `parent_id` trong bảng `tbltopic_controller_categories`
- Nếu `parent_id` là NULL, category đó là category gốc (root category)

#### 2.2.4. Dữ liệu gốc từ các nền tảng

- Mỗi bảng đều có trường `raw_data` để lưu trữ dữ liệu gốc dạng JSON
- Điều này giúp bảo toàn tất cả thông tin từ nền tảng gốc mà không bị mất mát
- Khi cần hiển thị thông tin đặc thù của từng nền tảng, có thể trích xuất từ trường này

### 2.3. Cập Nhật Module Version

Khi thêm các bảng mới, cần cập nhật version của module trong file `topics.php`:

```php
/*
Module Name: Topics Management
Description: Module for managing topics with action types and states tracking
Version: 1.3.0 // Cập nhật từ 1.2.1 lên 1.3.0
Author: FHC
Author URI: https://chantroituonglai.com
Requires at least: 2.3.*
*/
```

## 3. Phát Triển Backend

### 3.1. Model
- Cập nhật `Topic_controller_model.php`
- Tạo các phương thức CRUD cho categories, tags, blogs

### 3.2. Controller
- Cập nhật `Controllers.php`
- Thêm các phương thức xử lý API và dữ liệu

### 3.3. API Integration
- Xây dựng các connector cho từng nền tảng
- Xử lý dữ liệu theo cấu trúc riêng của từng nền tảng

### 3.4. Tích Hợp MCP

#### 3.4.1. Tích Hợp MySQL Server

Sử dụng MCP để tương tác an toàn với cơ sở dữ liệu Perfex CRM:

```php
/**
 * Lấy dữ liệu từ database thông qua MCP
 * @param int $controller_id ID của controller
 * @return array Dữ liệu từ database
 */
function get_controller_data_from_mcp($controller_id) {
    // Kiểm tra MCP có sẵn không
    if (!function_exists('mcp_query')) {
        log_activity('MCP not available for database query');
        return null;
    }
    
    // Sử dụng MCP để query database
    $result = mcp_query(
        "SELECT c.*, COUNT(cc.id) as categories_count, COUNT(ct.id) as tags_count, COUNT(cb.id) as blogs_count 
         FROM tbltopic_controllers c
         LEFT JOIN tbltopic_controller_categories cc ON c.id = cc.controller_id
         LEFT JOIN tbltopic_controller_tags ct ON c.id = ct.controller_id
         LEFT JOIN tbltopic_controller_blogs cb ON c.id = cb.controller_id
         WHERE c.id = ?
         GROUP BY c.id",
        [$controller_id]
    );
    
    if (!$result['success']) {
        log_activity('MCP database query failed: ' . $result['message']);
        return null;
    }
    
    return $result['data'][0] ?? null;
}
```

**Biện pháp bảo mật:**
- Sử dụng prepared statements để tránh SQL injection
- Giới hạn quyền của user MySQL chỉ đọc (`SELECT`)
- Ghi log mọi truy vấn để theo dõi

#### 3.4.2. Tích Hợp Browser Tools

Sử dụng MCP Browser Tools để kiểm tra và gỡ lỗi giao diện frontend:

```php
/**
 * Chụp ảnh màn hình giao diện controller
 * @param int $controller_id ID của controller
 * @return array Kết quả chụp ảnh
 */
function capture_controller_screenshot($controller_id) {
    // Kiểm tra MCP có sẵn không
    if (!function_exists('mcp_take_screenshot')) {
        log_activity('MCP not available for screenshot');
        return [
            'success' => false,
            'message' => 'MCP not available for screenshot'
        ];
    }
    
    // URL của trang controller
    $url = admin_url('topics/controllers/view/' . $controller_id);
    
    // Sử dụng MCP để chụp ảnh màn hình
    $result = mcp_take_screenshot($url, [
        'fullPage' => true,
        'filename' => 'controller_' . $controller_id . '_' . date('Ymd_His') . '.png',
        'path' => 'uploads/topics/screenshots/'
    ]);
    
    if (!$result['success']) {
        log_activity('MCP screenshot failed: ' . $result['message']);
    }
    
    return $result;
}

/**
 * Kiểm tra lỗi console trên giao diện controller
 * @param int $controller_id ID của controller
 * @return array Danh sách lỗi console
 */
function check_controller_console_errors($controller_id) {
    // Kiểm tra MCP có sẵn không
    if (!function_exists('mcp_get_console_errors')) {
        log_activity('MCP not available for console errors');
        return [
            'success' => false,
            'message' => 'MCP not available for console errors'
        ];
    }
    
    // URL của trang controller
    $url = admin_url('topics/controllers/view/' . $controller_id);
    
    // Sử dụng MCP để lấy lỗi console
    $result = mcp_get_console_errors($url);
    
    if (!$result['success']) {
        log_activity('MCP get console errors failed: ' . $result['message']);
    } else if (empty($result['data'])) {
        $result['message'] = 'No console errors found';
    }
    
    return $result;
}
```

**Trường hợp sử dụng:**
- Chụp ảnh màn hình để kiểm tra giao diện trên các môi trường khác nhau
- Kiểm tra lỗi console để phát hiện vấn đề JavaScript
- Kiểm tra tính tiếp cận (accessibility) của giao diện

## 4. Phát Triển Frontend

### 4.1. View
- Cập nhật view `controllers/view.php`
- Thêm các tab mới: Website Categories, Website Blogs, Website Tags

### 4.2. JavaScript
- Cập nhật `controllers.js`
- Xử lý tương tác người dùng và AJAX

### 4.3. CSS
- Cập nhật `draft_writer.css`
- Tạo style cho các thành phần mới

## 5. Quy Trình Triển Khai

### 5.1. Migration
- Tạo migration script cho các bảng mới
- Đảm bảo tuân thủ chuẩn Perfex CRM

### 5.2. Testing
- Kiểm thử với các nền tảng khác nhau
- Xác nhận tính đúng đắn của dữ liệu

### 5.3. Deployment

- **Chuẩn bị môi trường**: Đảm bảo môi trường production đã được backup và sẵn sàng cho việc triển khai.
- **Áp dụng migration**: Chạy migration script để tạo bảng dữ liệu mới trên môi trường production.
- **Cập nhật code**: Copy code module đã được test lên môi trường production.
- **Kiểm tra sau triển khai**: Kiểm tra lại các chức năng chính sau khi triển khai để đảm bảo không có lỗi xảy ra.
- **Hướng dẫn triển khai**: Cung cấp hướng dẫn chi tiết về quy trình triển khai cho người quản trị hệ thống.
- **Cập nhật documentation**: Cập nhật tài liệu hướng dẫn sử dụng module TopicController với các chức năng mới.

## 6. Chi Tiết Kỹ Thuật

### 6.1. Cấu Trúc Bảng Dữ Liệu
- Chi tiết các trường dữ liệu
- Các ràng buộc và index

### 6.2. API Endpoints
- Các endpoint cần thiết cho từng nền tảng
- Cấu trúc request/response

### 6.3. Xử Lý Dữ Liệu
- Chuẩn hóa dữ liệu từ các nền tảng khác nhau
- Lưu trữ và cập nhật dữ liệu

## 7. Tính Năng Mở Rộng

### 7.1. Đồng Bộ Hóa Tự Động
- Cơ chế cập nhật dữ liệu định kỳ
- Xử lý thay đổi từ nền tảng bên ngoài

### 7.2. Tùy Chỉnh Hiển Thị
- Tùy chọn hiển thị/ẩn các tab
- Tùy chỉnh số lượng item hiển thị

## 8. Quy Trình Triển Khai

### 8.1. Quy Trình Phát Triển

1. **Phân Tích Yêu Cầu**:
   - Xác định rõ các yêu cầu chức năng và phi chức năng
   - Xác định các nền tảng cần hỗ trợ (WordPress, Haravan, Shopify)
   - Xác định các loại dữ liệu cần đồng bộ (categories, tags, blogs)

2. **Thiết Kế**:
   - Thiết kế cơ sở dữ liệu (các bảng mới, quan hệ)
   - Thiết kế API endpoints
   - Thiết kế giao diện người dùng

3. **Phát Triển**:
   - Tuân thủ các tiêu chuẩn coding của Perfex CRM
   - Sử dụng các công cụ và thư viện có sẵn
   - Viết unit tests cho các chức năng quan trọng

4. **Kiểm Thử**:
   - Kiểm thử đơn vị (unit testing)
   - Kiểm thử tích hợp (integration testing)
   - Kiểm thử chấp nhận (acceptance testing)

5. **Triển Khai**:
   - Tạo migration script
   - Backup dữ liệu hiện có
   - Triển khai code mới
   - Kiểm tra sau triển khai

### 8.2. Hướng Dẫn Đề Xuất Thay Đổi

Khi đề xuất thay đổi, cung cấp thông tin chi tiết:

```
File: 'modules/topics/controllers/Controllers.php'
Dòng: 123-145
Đề xuất thay đổi:

// Trước:
public function get_categories($id)
{
    // Code cũ
}

// Sau:
public function get_categories($id)
{
    // Code mới với chức năng cải tiến
    if (!has_permission('topics', '', 'view')) {
        ajax_access_denied();
    }
    
    // Kiểm tra controller tồn tại
    $controller = $this->Topic_controller_model->get($id);
    if (!$controller) {
        echo json_encode([
            'success' => false,
            'message' => 'Controller not found'
        ]);
        return;
    }
    
    // Lấy categories
    $categories = $this->Topic_controller_model->get_controller_categories($id);
    
    echo json_encode([
        'success' => true,
        'data' => $categories
    ]);
}
```

### 8.3. Quy Trình Kiểm Tra và Triển Khai

1. **Kiểm Tra Trước Triển Khai**:
   - Kiểm tra tất cả các chức năng trên môi trường development
   - Kiểm tra tương thích với các phiên bản Perfex CRM khác nhau
   - Kiểm tra tương thích với các trình duyệt khác nhau

2. **Triển Khai**:
   - Backup dữ liệu hiện có
   - Chạy migration script
   - Copy code mới lên server
   - Kiểm tra sau triển khai

3. **Giám Sát và Bảo Trì**:
   - Giám sát log để phát hiện lỗi
   - Cập nhật khi có thay đổi từ các nền tảng bên ngoài
   - Cải tiến dựa trên phản hồi của người dùng

## 9. Kết Luận

Phương án phát triển chức năng TopicController này tập trung vào việc mở rộng khả năng tích hợp với các nền tảng bên ngoài, cung cấp giao diện quản lý dữ liệu trực quan và dễ sử dụng, đồng thời đảm bảo tính ổn định, hiệu suất và khả năng mở rộng của module.

Việc tuân thủ các chuẩn mực phát triển của Perfex CRM, áp dụng các best practices về coding, testing và deployment sẽ giúp đảm bảo chất lượng và thành công của dự án.

### 9.1. Biện Pháp Bảo Mật

#### 9.1.1. Bảo Vệ Dữ Liệu

- **Sử dụng Prepared Statements**:
  ```php
  // Thay vì:
  $this->db->query("SELECT * FROM tbltopic_controller_categories WHERE controller_id = " . $controller_id);
  
  // Sử dụng:
  $this->db->where('controller_id', $controller_id);
  $this->db->get('tbltopic_controller_categories');
  
  // Hoặc với query phức tạp:
  $this->db->query("SELECT * FROM tbltopic_controller_categories WHERE controller_id = ?", [$controller_id]);
  ```

- **Xác Thực Đầu Vào**:
  ```php
  // Xác thực ID
  $controller_id = (int)$this->input->get('id');
  if ($controller_id <= 0) {
      show_error('Invalid controller ID');
  }
  
  // Xác thực dữ liệu JSON
  $data = $this->input->post('data');
  $json_data = json_decode($data, true);
  if (json_last_error() !== JSON_ERROR_NONE) {
      set_alert('danger', 'Invalid JSON data: ' . json_last_error_msg());
      redirect(admin_url('topics/controllers'));
  }
  ```

#### 9.1.2. Kiểm Soát Truy Cập

- **Kiểm Tra Quyền Hạn**:
  ```php
  if (!has_permission('topics', '', 'view')) {
      access_denied('topics');
  }
  ```

- **Kiểm Tra Quyền Sở Hữu**:
  ```php
  $controller = $this->Topic_controller_model->get($id);
  if (!$controller || ($controller->staff_id != get_staff_user_id() && !is_admin())) {
      set_alert('danger', _l('access_denied'));
      redirect(admin_url('topics/controllers'));
  }
  ```

#### 9.1.3. Xử Lý Lỗi An Toàn

- **Ghi Log Lỗi**:
  ```php
  try {
      // Code có thể gây lỗi
      $result = $connector->syncData($controller_id);
  } catch (Exception $e) {
      log_activity('Error syncing data for controller [ID: ' . $controller_id . ']: ' . $e->getMessage());
      set_alert('danger', 'Error syncing data: ' . $e->getMessage());
  }
  ```

- **Không Hiển Thị Thông Tin Nhạy Cảm**:
  ```php
  // Thay vì:
  set_alert('danger', 'Database error: ' . $this->db->_error_message());
  
  // Sử dụng:
  log_activity('Database error: ' . $this->db->_error_message());
  set_alert('danger', 'An error occurred. Please check the logs.');
  ```

#### 9.1.4. Bảo Mật Khi Triển Khai

- **Backup Trước Khi Triển Khai**:
  ```sql
  -- Backup các bảng hiện có
  CREATE TABLE tbltopic_controllers_backup AS SELECT * FROM tbltopic_controllers;
  CREATE TABLE tbltopic_controller_backup AS SELECT * FROM tbltopic_controller;
  ```

- **Kiểm Tra Quyền File**:
  ```bash
  # Đảm bảo quyền file phù hợp
  find /path/to/module -type f -exec chmod 644 {} \;
  find /path/to/module -type d -exec chmod 755 {} \;
  ```

#### 9.1.5. Kiểm Tra Bảo Mật

- **Kiểm Tra SQL Injection**:
  - Kiểm tra tất cả các input từ người dùng
  - Sử dụng prepared statements cho mọi truy vấn
  - Kiểm tra các trường hợp đặc biệt (ví dụ: ORDER BY, LIMIT)

- **Kiểm Tra XSS**:
  - Sử dụng `html_escape()` khi hiển thị dữ liệu người dùng
  - Kiểm tra các trường hợp đặc biệt (ví dụ: JavaScript events, CSS)

- **Kiểm Tra CSRF**:
  - Sử dụng CSRF token cho tất cả các form
  - Kiểm tra CSRF token cho tất cả các request POST

## 10. Cập Nhật Ngôn Ngữ

### 10.1. Tổ Chức File Ngôn Ngữ

Để tránh việc file `topics_lang.php` trở nên quá lớn và khó quản lý, chúng ta sẽ tổ chức các chuỗi ngôn ngữ theo module con:

1. **Tạo File Ngôn Ngữ Riêng Cho Mỗi Module Con**:
   - `controllers_lang.php`: Chứa các chuỗi liên quan đến controller
   - `categories_lang.php`: Chứa các chuỗi liên quan đến categories
   - `blogs_lang.php`: Chứa các chuỗi liên quan đến blogs
   - `tags_lang.php`: Chứa các chuỗi liên quan đến tags

2. **Cấu Trúc Thư Mục**:
   ```
   language/
   ├── english/
   │   ├── topics_lang.php           # File ngôn ngữ chính (giữ nguyên)
   │   ├── draft_writer_lang.php     # File ngôn ngữ hiện có
   │   ├── controllers_lang.php      # File mới cho controllers
   │   ├── categories_lang.php       # File mới cho categories
   │   ├── blogs_lang.php            # File mới cho blogs
   │   └── tags_lang.php             # File mới cho tags
   └── vietnamese/
       ├── topics_lang.php           # File ngôn ngữ chính (giữ nguyên)
       ├── draft_writer_lang.php     # File ngôn ngữ hiện có
       ├── controllers_lang.php      # File mới cho controllers
       ├── categories_lang.php       # File mới cho categories
       ├── blogs_lang.php            # File mới cho blogs
       └── tags_lang.php             # File mới cho tags
   ```

### 10.2. Đăng Ký File Ngôn Ngữ Mới

Để đăng ký các file ngôn ngữ mới, cần cập nhật hàm `init_topics_module()` trong file `topics.php`:

```php
// Đăng ký ngôn ngữ
register_language_files(TOPICS_MODULE_NAME, [
    TOPICS_MODULE_NAME,
    'draft_writer',
    'controllers',
    'categories',
    'blogs',
    'tags'
]);
```

### 10.3. Quy Ước Đặt Tên Chuỗi Ngôn Ngữ

Để tránh xung đột và dễ quản lý, áp dụng quy ước đặt tên sau:

1. **Prefix Theo Module Con**:
   - Controllers: `controller_*`
   - Categories: `category_*`
   - Blogs: `blog_*`
   - Tags: `tag_*`

2. **Ví Dụ**:
   ```php
   // Trong file controllers_lang.php
   $lang['controller_categories_tab'] = 'Danh Mục Website';
   $lang['controller_blogs_tab'] = 'Bài Viết Website';
   $lang['controller_tags_tab'] = 'Thẻ Website';
   $lang['controller_sync_data'] = 'Đồng Bộ Dữ Liệu';
   $lang['controller_last_sync'] = 'Lần Đồng Bộ Cuối';
   
   // Trong file categories_lang.php
   $lang['category_name'] = 'Tên Danh Mục';
   $lang['category_slug'] = 'Slug';
   $lang['category_description'] = 'Mô Tả';
   $lang['category_parent'] = 'Danh Mục Cha';
   $lang['category_count'] = 'Số Bài Viết';
   ```

### 10.4. Tạo File Ngôn Ngữ Mới

Ví dụ về nội dung file `controllers_lang.php`:

```php
<?php
defined('BASEPATH') or exit('No direct script access allowed');

// Controllers Module Language Lines
$lang['controller_categories_tab'] = 'Danh Mục Website';
$lang['controller_blogs_tab'] = 'Bài Viết Website';
$lang['controller_tags_tab'] = 'Thẻ Website';
$lang['controller_sync_data'] = 'Đồng Bộ Dữ Liệu';
$lang['controller_last_sync'] = 'Lần Đồng Bộ Cuối';
$lang['controller_sync_now'] = 'Đồng Bộ Ngay';
$lang['controller_sync_success'] = 'Đồng bộ dữ liệu thành công';
$lang['controller_sync_error'] = 'Đồng bộ dữ liệu thất bại';
$lang['controller_no_data'] = 'Không có dữ liệu';
$lang['controller_loading_data'] = 'Đang tải dữ liệu...';
$lang['controller_view_on_site'] = 'Xem trên website';
$lang['controller_category_hierarchy'] = 'Cấu Trúc Phân Cấp Danh Mục';
$lang['controller_blog_list'] = 'Danh Sách Bài Viết';
$lang['controller_tag_list'] = 'Danh Sách Thẻ';
$lang['controller_save_state'] = 'Lưu Trạng Thái';
$lang['controller_state_saved'] = 'Đã lưu trạng thái';
$lang['controller_state_save_error'] = 'Lỗi khi lưu trạng thái';
```

## 11. Tổ Chức View Con

### 11.1. Cấu Trúc Thư Mục View Con

Để tổ chức các view con một cách hợp lý, chúng ta sẽ tạo các thư mục con trong `views/controllers`:

```
views/
└── controllers/
    ├── index.php              # Danh sách controllers
    ├── create.php             # Form tạo controller
    ├── edit.php               # Form chỉnh sửa controller
    ├── detail.php             # Trang chi tiết controller
    ├── partials/              # Các view partial
    │   ├── tabs.php           # Tab navigation
    │   ├── connection_info.php # Thông tin kết nối
    │   └── action_buttons.php # Các nút hành động
    ├── tabs/                  # Các tab view
    │   ├── related_topics.php # Tab related topics (hiện có)
    │   ├── categories.php     # Tab danh mục website
    │   ├── blogs.php          # Tab bài viết website
    │   └── tags.php           # Tab thẻ website
    └── modals/                # Các modal dialog
        ├── sync_confirm.php   # Xác nhận đồng bộ
        └── save_state.php     # Lưu trạng thái
```

### 11.2. Tích Hợp View Con Vào View Chính

Trong file `detail.php`, sử dụng `$this->load->view()` để tải các view con:

```php
<!-- Tab navigation -->
<?php $this->load->view('controllers/partials/tabs', ['controller' => $controller]); ?>

<div class="tab-content">
    <!-- Tab Related Topics -->
    <div role="tabpanel" class="tab-pane active" id="related_topics">
        <?php $this->load->view('controllers/tabs/related_topics', ['controller' => $controller]); ?>
    </div>
    
    <!-- Tab Categories -->
    <div role="tabpanel" class="tab-pane" id="categories">
        <?php $this->load->view('controllers/tabs/categories', ['controller' => $controller]); ?>
    </div>
    
    <!-- Tab Blogs -->
    <div role="tabpanel" class="tab-pane" id="blogs">
        <?php $this->load->view('controllers/tabs/blogs', ['controller' => $controller]); ?>
    </div>
    
    <!-- Tab Tags -->
    <div role="tabpanel" class="tab-pane" id="tags">
        <?php $this->load->view('controllers/tabs/tags', ['controller' => $controller]); ?>
    </div>
</div>
```

### 11.3. Ví Dụ View Con: Tab Categories

File `views/controllers/tabs/categories.php`:

```php
<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="categories-container">
    <div class="row mbot15">
        <div class="col-md-6">
            <h4><?php echo _l('controller_category_hierarchy'); ?></h4>
        </div>
        <div class="col-md-6 text-right">
            <button type="button" class="btn btn-info" id="sync_categories">
                <i class="fa fa-refresh"></i> <?php echo _l('controller_sync_data'); ?>
            </button>
            <button type="button" class="btn btn-success" id="save_categories_state">
                <i class="fa fa-save"></i> <?php echo _l('controller_save_state'); ?>
            </button>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-12">
            <div id="categories_loading" class="text-center mtop20 mbot20">
                <i class="fa fa-spinner fa-spin fa-2x"></i>
                <p><?php echo _l('controller_loading_data'); ?></p>
            </div>
            
            <div id="categories_tree" class="mtop20" style="display: none;">
                <!-- Categories tree will be loaded here via JavaScript -->
            </div>
            
            <div id="categories_empty" class="alert alert-info mtop20" style="display: none;">
                <?php echo _l('controller_no_data'); ?>
            </div>
        </div>
    </div>
    
    <div class="row mtop20">
        <div class="col-md-12">
            <small class="text-muted">
                <?php echo _l('controller_last_sync'); ?>: 
                <span id="categories_last_sync">-</span>
            </small>
        </div>
    </div>
</div>
```

## 12. Ưu Tiên Xây Dựng WordPress Connector

### 12.1. Mở Rộng WordPress Connector Hiện Có

WordPress Connector hiện tại đã có các chức năng cơ bản như kiểm tra kết nối và đăng bài. Chúng ta sẽ mở rộng connector này để hỗ trợ các chức năng mới:

1. **Lấy Danh Mục (Categories)**
2. **Lấy Thẻ (Tags)**
3. **Lấy Bài Viết (Posts/Blogs)**

### 12.2. Cập Nhật WordPress Connector

File `includes/platform_connectors/wordpress_connector.php` cần được cập nhật với các phương thức mới:

```php
/**
 * Get categories from WordPress
 * 
 * @param array $config Login configuration
 * @param array $args Additional arguments
 * @return array ['success' => bool, 'data' => array, 'message' => string]
 */
public function getCategories(array $config, array $args = [])
{
    // Validate config
    $validation = $this->validateConfig($config);
    if (!$validation['success']) {
        return $validation;
    }
    
    // Default arguments
    $default_args = [
        'per_page' => 100,
        'page' => 1,
        'hide_empty' => false
    ];
    
    // Merge with user arguments
    $args = array_merge($default_args, $args);
    
    // Build request URL
    $url = rtrim($config['site_url'], '/') . '/wp-json/wp/v2/categories';
    $url = add_query_arg($args, $url);
    
    // Make request
    $response = $this->makeRequest('GET', $url, null, $config);
    
    if (!$response['success']) {
        return $response;
    }
    
    // Process categories
    $categories = [];
    foreach ($response['data'] as $category) {
        $categories[] = [
            'id' => $category['id'],
            'name' => $category['name'],
            'slug' => $category['slug'],
            'description' => $category['description'],
            'parent_id' => $category['parent'],
            'count' => $category['count'],
            'url' => $category['link'],
            'raw_data' => json_encode($category)
        ];
    }
    
    return [
        'success' => true,
        'data' => $categories,
        'message' => 'Categories retrieved successfully'
    ];
}

/**
 * Get tags from WordPress
 * 
 * @param array $config Login configuration
 * @param array $args Additional arguments
 * @return array ['success' => bool, 'data' => array, 'message' => string]
 */
public function getTags(array $config, array $args = [])
{
    // Validate config
    $validation = $this->validateConfig($config);
    if (!$validation['success']) {
        return $validation;
    }
    
    // Default arguments
    $default_args = [
        'per_page' => 100,
        'page' => 1,
        'hide_empty' => false
    ];
    
    // Merge with user arguments
    $args = array_merge($default_args, $args);
    
    // Build request URL
    $url = rtrim($config['site_url'], '/') . '/wp-json/wp/v2/tags';
    $url = add_query_arg($args, $url);
    
    // Make request
    $response = $this->makeRequest('GET', $url, null, $config);
    
    if (!$response['success']) {
        return $response;
    }
    
    // Process tags
    $tags = [];
    foreach ($response['data'] as $tag) {
        $tags[] = [
            'id' => $tag['id'],
            'name' => $tag['name'],
            'slug' => $tag['slug'],
            'description' => $tag['description'],
            'count' => $tag['count'],
            'url' => $tag['link'],
            'raw_data' => json_encode($tag)
        ];
    }
    
    return [
        'success' => true,
        'data' => $tags,
        'message' => 'Tags retrieved successfully'
    ];
}

/**
 * Get posts/blogs from WordPress
 * 
 * @param array $config Login configuration
 * @param array $args Additional arguments
 * @return array ['success' => bool, 'data' => array, 'message' => string]
 */
public function getBlogs(array $config, array $args = [])
{
    // Validate config
    $validation = $this->validateConfig($config);
    if (!$validation['success']) {
        return $validation;
    }
    
    // Default arguments
    $default_args = [
        'per_page' => 20,
        'page' => 1,
        'status' => 'publish'
    ];
    
    // Merge with user arguments
    $args = array_merge($default_args, $args);
    
    // Build request URL
    $url = rtrim($config['site_url'], '/') . '/wp-json/wp/v2/posts';
    $url = add_query_arg($args, $url);
    
    // Make request
    $response = $this->makeRequest('GET', $url, null, $config);
    
    if (!$response['success']) {
        return $response;
    }
    
    // Process posts
    $blogs = [];
    foreach ($response['data'] as $post) {
        // Get featured image URL if available
        $featured_image = null;
        if (!empty($post['featured_media'])) {
            $media_url = rtrim($config['site_url'], '/') . '/wp-json/wp/v2/media/' . $post['featured_media'];
            $media_response = $this->makeRequest('GET', $media_url, null, $config);
            if ($media_response['success'] && !empty($media_response['data']['source_url'])) {
                $featured_image = $media_response['data']['source_url'];
            }
        }
        
        $blogs[] = [
            'id' => $post['id'],
            'title' => $post['title']['rendered'],
            'slug' => $post['slug'],
            'excerpt' => $post['excerpt']['rendered'],
            'status' => $post['status'],
            'author' => $post['author'],
            'featured_image' => $featured_image,
            'url' => $post['link'],
            'date_published' => $post['date'],
            'date_modified' => $post['modified'],
            'comment_count' => $post['comment_count'] ?? 0,
            'categories' => $post['categories'] ?? [],
            'tags' => $post['tags'] ?? [],
            'raw_data' => json_encode($post)
        ];
    }
    
    return [
        'success' => true,
        'data' => $blogs,
        'message' => 'Blogs retrieved successfully'
    ];
}

/**
 * Get blog relationships (categories and tags)
 * 
 * @param array $config Login configuration
 * @param int $blog_id Blog ID
 * @return array ['success' => bool, 'data' => array, 'message' => string]
 */
public function getBlogRelationships(array $config, $blog_id)
{
    // Validate config
    $validation = $this->validateConfig($config);
    if (!$validation['success']) {
        return $validation;
    }
    
    // Build request URL
    $url = rtrim($config['site_url'], '/') . '/wp-json/wp/v2/posts/' . $blog_id;
    
    // Make request
    $response = $this->makeRequest('GET', $url, null, $config);
    
    if (!$response['success']) {
        return $response;
    }
    
    // Extract relationships
    $relationships = [
        'categories' => $response['data']['categories'] ?? [],
        'tags' => $response['data']['tags'] ?? []
    ];
    
    return [
        'success' => true,
        'data' => $relationships,
        'message' => 'Blog relationships retrieved successfully'
    ];
}
```

### 12.3. Cập Nhật Helper Functions

File `helpers/topic_platform_helper.php` cần được cập nhật với các hàm helper mới:

```php
/**
 * Get categories from platform
 * 
 * @param int $controller_id Controller ID
 * @param array $args Additional arguments
 * @return array Categories data
 */
function get_platform_categories($controller_id, $args = [])
{
    $CI = &get_instance();
    $controller = $CI->Topic_controller_model->get($controller_id);
    
    if (!$controller) {
        return [
            'success' => false,
            'message' => 'Controller not found'
        ];
    }
    
    // Get platform connector
    $connector = get_platform_connector($controller->platform);
    
    if (!$connector) {
        return [
            'success' => false,
            'message' => 'Platform connector not found: ' . $controller->platform
        ];
    }
    
    // Get login config
    $login_config = json_decode($controller->login_config, true);
    
    if (!$login_config) {
        return [
            'success' => false,
            'message' => 'Invalid login configuration'
        ];
    }
    
    // Add site URL to config
    $login_config['site_url'] = $controller->site;
    
    // Get categories from platform
    $result = $connector->getCategories($login_config, $args);
    
    if ($result['success']) {
        // Update last sync time
        $CI->Topic_controller_model->update($controller_id, [
            'last_sync' => date('Y-m-d H:i:s')
        ]);
        
        // Save categories to database
        foreach ($result['data'] as $category) {
            $CI->Topic_controller_model->save_category($controller_id, $category);
        }
    }
    
    return $result;
}

/**
 * Get tags from platform
 * 
 * @param int $controller_id Controller ID
 * @param array $args Additional arguments
 * @return array Tags data
 */
function get_platform_tags($controller_id, $args = [])
{
    $CI = &get_instance();
    $controller = $CI->Topic_controller_model->get($controller_id);
    
    if (!$controller) {
        return [
            'success' => false,
            'message' => 'Controller not found'
        ];
    }
    
    // Get platform connector
    $connector = get_platform_connector($controller->platform);
    
    if (!$connector) {
        return [
            'success' => false,
            'message' => 'Platform connector not found: ' . $controller->platform
        ];
    }
    
    // Get login config
    $login_config = json_decode($controller->login_config, true);
    
    if (!$login_config) {
        return [
            'success' => false,
            'message' => 'Invalid login configuration'
        ];
    }
    
    // Add site URL to config
    $login_config['site_url'] = $controller->site;
    
    // Get tags from platform
    $result = $connector->getTags($login_config, $args);
    
    if ($result['success']) {
        // Update last sync time
        $CI->Topic_controller_model->update($controller_id, [
            'last_sync' => date('Y-m-d H:i:s')
        ]);
        
        // Save tags to database
        foreach ($result['data'] as $tag) {
            $CI->Topic_controller_model->save_tag($controller_id, $tag);
        }
    }
    
    return $result;
}

/**
 * Get blogs from platform
 * 
 * @param int $controller_id Controller ID
 * @param array $args Additional arguments
 * @return array Blogs data
 */
function get_platform_blogs($controller_id, $args = [])
{
    $CI = &get_instance();
    $controller = $CI->Topic_controller_model->get($controller_id);
    
    if (!$controller) {
        return [
            'success' => false,
            'message' => 'Controller not found'
        ];
    }
    
    // Get platform connector
    $connector = get_platform_connector($controller->platform);
    
    if (!$connector) {
        return [
            'success' => false,
            'message' => 'Platform connector not found: ' . $controller->platform
        ];
    }
    
    // Get login config
    $login_config = json_decode($controller->login_config, true);
    
    if (!$login_config) {
        return [
            'success' => false,
            'message' => 'Invalid login configuration'
        ];
    }
    
    // Add site URL to config
    $login_config['site_url'] = $controller->site;
    
    // Get blogs from platform
    $result = $connector->getBlogs($login_config, $args);
    
    if ($result['success']) {
        // Update last sync time
        $CI->Topic_controller_model->update($controller_id, [
            'last_sync' => date('Y-m-d H:i:s')
        ]);
        
        // Save blogs to database
        foreach ($result['data'] as $blog) {
            $blog_id = $CI->Topic_controller_model->save_blog($controller_id, $blog);
            
            // Save relationships
            if ($blog_id && !empty($blog['categories'])) {
                foreach ($blog['categories'] as $category_id) {
                    $CI->Topic_controller_model->save_blog_relationship($controller_id, $blog['id'], 'category', $category_id);
                }
            }
            
            if ($blog_id && !empty($blog['tags'])) {
                foreach ($blog['tags'] as $tag_id) {
                    $CI->Topic_controller_model->save_blog_relationship($controller_id, $blog['id'], 'tag', $tag_id);
                }
            }
        }
    }
    
    return $result;
}
```

### 12.4. Cập Nhật Model

File `models/Topic_controller_model.php` cần được cập nhật với các phương thức mới:

```php
/**
 * Save category to database
 * 
 * @param int $controller_id Controller ID
 * @param array $category Category data
 * @return int|bool Category ID or false on failure
 */
public function save_category($controller_id, $category)
{
    // Check if category exists
    $this->db->where('controller_id', $controller_id);
    $this->db->where('category_id', $category['id']);
    $existing = $this->db->get(db_prefix() . 'topic_controller_categories')->row();
    
    $data = [
        'controller_id' => $controller_id,
        'category_id' => $category['id'],
        'parent_id' => $category['parent_id'] ?? null,
        'name' => $category['name'],
        'slug' => $category['slug'] ?? null,
        'description' => $category['description'] ?? null,
        'count' => $category['count'] ?? 0,
        'url' => $category['url'] ?? null,
        'image_url' => $category['image_url'] ?? null,
        'raw_data' => $category['raw_data'] ?? null,
        'last_sync' => date('Y-m-d H:i:s')
    ];
    
    if ($existing) {
        // Update existing category
        $this->db->where('id', $existing->id);
        $this->db->update(db_prefix() . 'topic_controller_categories', $data);
        return $existing->id;
    } else {
        // Insert new category
        $this->db->insert(db_prefix() . 'topic_controller_categories', $data);
        return $this->db->insert_id();
    }
}

/**
 * Save tag to database
 * 
 * @param int $controller_id Controller ID
 * @param array $tag Tag data
 * @return int|bool Tag ID or false on failure
 */
public function save_tag($controller_id, $tag)
{
    // Check if tag exists
    $this->db->where('controller_id', $controller_id);
    $this->db->where('tag_id', $tag['id']);
    $existing = $this->db->get(db_prefix() . 'topic_controller_tags')->row();
    
    $data = [
        'controller_id' => $controller_id,
        'tag_id' => $tag['id'],
        'name' => $tag['name'],
        'slug' => $tag['slug'] ?? null,
        'description' => $tag['description'] ?? null,
        'count' => $tag['count'] ?? 0,
        'url' => $tag['url'] ?? null,
        'raw_data' => $tag['raw_data'] ?? null,
        'last_sync' => date('Y-m-d H:i:s')
    ];
    
    if ($existing) {
        // Update existing tag
        $this->db->where('id', $existing->id);
        $this->db->update(db_prefix() . 'topic_controller_tags', $data);
        return $existing->id;
    } else {
        // Insert new tag
        $this->db->insert(db_prefix() . 'topic_controller_tags', $data);
        return $this->db->insert_id();
    }
}

/**
 * Save blog to database
 * 
 * @param int $controller_id Controller ID
 * @param array $blog Blog data
 * @return int|bool Blog ID or false on failure
 */
public function save_blog($controller_id, $blog)
{
    // Check if blog exists
    $this->db->where('controller_id', $controller_id);
    $this->db->where('blog_id', $blog['id']);
    $existing = $this->db->get(db_prefix() . 'topic_controller_blogs')->row();
    
    $data = [
        'controller_id' => $controller_id,
        'blog_id' => $blog['id'],
        'title' => $blog['title'],
        'slug' => $blog['slug'] ?? null,
        'excerpt' => $blog['excerpt'] ?? null,
        'status' => $blog['status'] ?? 'publish',
        'author' => $blog['author'] ?? null,
        'featured_image' => $blog['featured_image'] ?? null,
        'url' => $blog['url'] ?? null,
        'date_published' => $blog['date_published'] ? date('Y-m-d H:i:s', strtotime($blog['date_published'])) : null,
        'date_modified' => $blog['date_modified'] ? date('Y-m-d H:i:s', strtotime($blog['date_modified'])) : null,
        'comment_count' => $blog['comment_count'] ?? 0,
        'view_count' => $blog['view_count'] ?? 0,
        'raw_data' => $blog['raw_data'] ?? null,
        'last_sync' => date('Y-m-d H:i:s')
    ];
    
    if ($existing) {
        // Update existing blog
        $this->db->where('id', $existing->id);
        $this->db->update(db_prefix() . 'topic_controller_blogs', $data);
        return $existing->id;
    } else {
        // Insert new blog
        $this->db->insert(db_prefix() . 'topic_controller_blogs', $data);
        return $this->db->insert_id();
    }
}

/**
 * Save blog relationship to database
 * 
 * @param int $controller_id Controller ID
 * @param string $blog_id Blog ID
 * @param string $type Relationship type (category or tag)
 * @param string $term_id Term ID
 * @return int|bool Relationship ID or false on failure
 */
public function save_blog_relationship($controller_id, $blog_id, $type, $term_id)
{
    // Check if relationship exists
    $this->db->where('controller_id', $controller_id);
    $this->db->where('blog_id', $blog_id);
    $this->db->where('type', $type);
    $this->db->where('term_id', $term_id);
    $existing = $this->db->get(db_prefix() . 'topic_controller_blog_relationships')->row();
    
    if ($existing) {
        // Relationship already exists
        return $existing->id;
    } else {
        // Insert new relationship
        $data = [
            'controller_id' => $controller_id,
            'blog_id' => $blog_id,
            'type' => $type,
            'term_id' => $term_id
        ];
        
        $this->db->insert(db_prefix() . 'topic_controller_blog_relationships', $data);
        return $this->db->insert_id();
    }
}
```

## 13. Ví Dụ Triển Khai Tab Categories

### 13.1. Tạo File View Tab Categories

File `views/controllers/tabs/categories.php`:

```php
<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="categories-container">
    <div class="row mbot15">
        <div class="col-md-6">
            <h4><?php echo _l('controller_category_hierarchy'); ?></h4>
        </div>
        <div class="col-md-6 text-right">
            <button type="button" class="btn btn-info" id="sync_categories">
                <i class="fa fa-refresh"></i> <?php echo _l('controller_sync_data'); ?>
            </button>
            <button type="button" class="btn btn-success" id="save_categories_state">
                <i class="fa fa-save"></i> <?php echo _l('controller_save_state'); ?>
            </button>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-12">
            <div id="categories_loading" class="text-center mtop20 mbot20">
                <i class="fa fa-spinner fa-spin fa-2x"></i>
                <p><?php echo _l('controller_loading_data'); ?></p>
            </div>
            
            <div id="categories_tree" class="mtop20" style="display: none;">
                <!-- Categories tree will be loaded here via JavaScript -->
            </div>
            
            <div id="categories_empty" class="alert alert-info mtop20" style="display: none;">
                <?php echo _l('controller_no_data'); ?>
            </div>
        </div>
    </div>
    
    <div class="row mtop20">
        <div class="col-md-12">
            <small class="text-muted">
                <?php echo _l('controller_last_sync'); ?>: 
                <span id="categories_last_sync">-</span>
            </small>
        </div>
    </div>
</div>
```

### 13.2. Thêm JavaScript Xử Lý Tab Categories

Thêm đoạn code sau vào file `assets/js/controllers.js`:

```javascript
// Categories Tab Functions
function loadCategories(controllerId) {
    if (!controllerId) {
        return;
    }
    
    // Show loading
    $('#categories_tree').hide();
    $('#categories_empty').hide();
    $('#categories_loading').show();
    
    // Load categories from server
    $.ajax({
        url: admin_url + 'topics/controllers/get_categories/' + controllerId,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            $('#categories_loading').hide();
            
            if (response.success && response.data && response.data.length > 0) {
                // Render categories tree
                renderCategoriesTree(response.data);
                $('#categories_tree').show();
                
                // Update last sync time
                if (response.last_sync) {
                    $('#categories_last_sync').text(response.last_sync);
                }
            } else {
                // Show empty message
                $('#categories_empty').show();
            }
        },
        error: function() {
            $('#categories_loading').hide();
            $('#categories_empty').show();
            alert_float('danger', controller_sync_error);
        }
    });
}

function renderCategoriesTree(categories) {
    // Build categories hierarchy
    var categoriesMap = {};
    var rootCategories = [];
    
    // First pass: create map of categories
    categories.forEach(function(category) {
        categoriesMap[category.category_id] = {
            ...category,
            children: []
        };
    });
    
    // Second pass: build hierarchy
    categories.forEach(function(category) {
        if (category.parent_id && categoriesMap[category.parent_id]) {
            // Add to parent's children
            categoriesMap[category.parent_id].children.push(categoriesMap[category.category_id]);
        } else {
            // Root category
            rootCategories.push(categoriesMap[category.category_id]);
        }
    });
    
    // Render tree
    var html = '<ul class="categories-tree">';
    rootCategories.forEach(function(category) {
        html += renderCategoryNode(category);
    });
    html += '</ul>';
    
    $('#categories_tree').html(html);
    
    // Add click handlers for expand/collapse
    $('.category-toggle').on('click', function(e) {
        e.preventDefault();
        $(this).closest('.category-item').toggleClass('expanded');
    });
}

function renderCategoryNode(category) {
    var hasChildren = category.children && category.children.length > 0;
    var html = '<li class="category-item">';
    
    // Category header
    html += '<div class="category-header">';
    
    // Toggle button (if has children)
    if (hasChildren) {
        html += '<a href="#" class="category-toggle"><i class="fa fa-caret-right"></i></a>';
    } else {
        html += '<span class="category-toggle-placeholder"></span>';
    }
    
    // Category name and count
    html += '<span class="category-name">' + category.name + '</span>';
    html += '<span class="category-count">(' + category.count + ')</span>';
    
    // View on site link
    if (category.url) {
        html += '<a href="' + category.url + '" target="_blank" class="category-link"><i class="fa fa-external-link"></i></a>';
    }
    
    html += '</div>';
    
    // Children (if any)
    if (hasChildren) {
        html += '<ul class="category-children">';
        category.children.forEach(function(child) {
            html += renderCategoryNode(child);
        });
        html += '</ul>';
    }
    
    html += '</li>';
    return html;
}

function syncCategories(controllerId) {
    if (!controllerId) {
        return;
    }
    
    // Show loading
    $('#categories_tree').hide();
    $('#categories_empty').hide();
    $('#categories_loading').show();
    
    // Disable sync button
    $('#sync_categories').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> ' + controller_sync_data);
    
    // Sync categories from server
    $.ajax({
        url: admin_url + 'topics/controllers/sync_categories/' + controllerId,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            // Re-enable sync button
            $('#sync_categories').prop('disabled', false).html('<i class="fa fa-refresh"></i> ' + controller_sync_data);
            
            if (response.success) {
                // Show success message
                alert_float('success', controller_sync_success);
                
                // Reload categories
                loadCategories(controllerId);
            } else {
                // Show error message
                $('#categories_loading').hide();
                alert_float('danger', response.message || controller_sync_error);
            }
        },
        error: function() {
            // Re-enable sync button
            $('#sync_categories').prop('disabled', false).html('<i class="fa fa-refresh"></i> ' + controller_sync_data);
            
            // Show error message
            $('#categories_loading').hide();
            alert_float('danger', controller_sync_error);
        }
    });
}

function saveCategoriesState(controllerId) {
    if (!controllerId) {
        return;
    }
    
    // Get expanded categories
    var expandedCategories = [];
    $('.category-item.expanded').each(function() {
        var categoryId = $(this).data('category-id');
        if (categoryId) {
            expandedCategories.push(categoryId);
        }
    });
    
    // Save state
    $.ajax({
        url: admin_url + 'topics/controllers/save_categories_state/' + controllerId,
        type: 'POST',
        data: {
            expanded_categories: expandedCategories
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Show success message
                alert_float('success', controller_state_saved);
            } else {
                // Show error message
                alert_float('danger', response.message || controller_state_save_error);
            }
        },
        error: function() {
            // Show error message
            alert_float('danger', controller_state_save_error);
        }
    });
}
```

### 13.3. Thêm CSS Cho Tab Categories

Thêm đoạn code sau vào file `assets/css/draft_writer.css`:

```css
/* Categories Tree Styles */
.categories-tree {
    list-style: none;
    padding: 0;
    margin: 0;
}

.category-item {
    margin-bottom: 5px;
}

.category-header {
    display: flex;
    align-items: center;
    padding: 8px 10px;
    background-color: #f9f9f9;
    border-radius: 4px;
    border: 1px solid #e0e0e0;
    transition: background-color 0.2s ease;
}

.category-header:hover {
    background-color: #f0f0f0;
}

.category-toggle,
.category-toggle-placeholder {
    margin-right: 10px;
    width: 15px;
    text-align: center;
}

.category-toggle .fa {
    transition: transform 0.2s ease;
}

.category-item.expanded > .category-header .category-toggle .fa {
    transform: rotate(90deg);
}

.category-name {
    flex-grow: 1;
    font-weight: 500;
}

.category-count {
    margin: 0 10px;
    color: #777;
}

.category-link {
    color: #03a9f4;
    margin-left: 5px;
}

.category-children {
    list-style: none;
    padding-left: 25px;
    margin-top: 5px;
    display: none;
}

.category-item.expanded > .category-children {
    display: block;
}

/* Loading Indicator */
#categories_loading {
    color: #777;
}

#categories_loading .fa-spin {
    margin-bottom: 10px;
}
```

### 13.4. Cập Nhật Controller

Thêm các phương thức sau vào file `controllers/Controllers.php`:

```php
/**
 * Get categories for a controller
 * 
 * @param int $id Controller ID
 * @return void
 */
public function get_categories($id)
{
    if (!has_permission('topics', '', 'view')) {
        ajax_access_denied();
    }
    
    // Check if controller exists
    $controller = $this->Topic_controller_model->get($id);
    if (!$controller) {
        echo json_encode([
            'success' => false,
            'message' => 'Controller not found'
        ]);
        return;
    }
    
    // Get categories from database
    $categories = $this->Topic_controller_model->get_controller_categories($id);
    
    // Get last sync time
    $last_sync = null;
    if (!empty($categories)) {
        $last_sync = max(array_map(function($category) {
            return $category['last_sync'];
        }, $categories));
    }
    
    echo json_encode([
        'success' => true,
        'data' => $categories,
        'last_sync' => $last_sync ? date('d/m/Y H:i:s', strtotime($last_sync)) : null
    ]);
}

/**
 * Sync categories for a controller
 * 
 * @param int $id Controller ID
 * @return void
 */
public function sync_categories($id)
{
    if (!has_permission('topics', '', 'edit')) {
        ajax_access_denied();
    }
    
    // Check if controller exists
    $controller = $this->Topic_controller_model->get($id);
    if (!$controller) {
        echo json_encode([
            'success' => false,
            'message' => 'Controller not found'
        ]);
        return;
    }
    
    // Sync categories from platform
    $result = get_platform_categories($id);
    
    echo json_encode($result);
}

/**
 * Save categories state for a controller
 * 
 * @param int $id Controller ID
 * @return void
 */
public function save_categories_state($id)
{
    if (!has_permission('topics', '', 'edit')) {
        ajax_access_denied();
    }
    
    // Check if controller exists
    $controller = $this->Topic_controller_model->get($id);
    if (!$controller) {
        echo json_encode([
            'success' => false,
            'message' => 'Controller not found'
        ]);
        return;
    }
    
    // Get expanded categories
    $expanded_categories = $this->input->post('expanded_categories');
    
    // Save state
    $success = $this->Topic_controller_model->save_controller_state($id, [
        'expanded_categories' => $expanded_categories
    ]);
    
    echo json_encode([
        'success' => $success,
        'message' => $success ? 'State saved successfully' : 'Failed to save state'
    ]);
}
```

## 14. Tích Hợp Với Platform Connectors Hiện Có

### 14.1. Tổng Quan Về Platform Connectors

Module TopicController sử dụng hệ thống connector để kết nối với các nền tảng bên ngoài như WordPress, Haravan, Shopify. Hệ thống này được thiết kế theo mô hình interface để đảm bảo tính nhất quán và dễ mở rộng.

#### 14.1.1. Cấu Trúc Thư Mục

```
includes/
└── platform_connectors/
    ├── platform_connector_interface.php  # Interface chung cho tất cả connector
    ├── wordpress_connector.php           # Connector cho WordPress
    ├── haravan_connector.php             # Connector cho Haravan
    └── shopify_connector.php             # Connector cho Shopify (sẽ phát triển)
```

#### 14.1.2. Interface Chung

Tất cả các connector phải implement interface `PlatformConnectorInterface` với các method sau:

```php
interface PlatformConnectorInterface
{
    /**
     * Test connection to the platform
     * 
     * @param array $config Login configuration
     * @return array ['success' => bool, 'message' => string]
     */
    public function testConnection(array $config);
    
    /**
     * Get categories from the platform
     * 
     * @param array $config Login configuration
     * @return array ['success' => bool, 'categories' => array, 'message' => string]
     */
    public function getCategories(array $config);
    
    /**
     * Publish a post to the platform
     * 
     * @param array $config Login configuration
     * @param array $post Post data
     * @return array ['success' => bool, 'post_id' => int|string, 'post_url' => string, 'message' => string]
     */
    public function publishPost(array $config, array $post);
    
    /**
     * Get required login fields for this platform
     * 
     * @return array List of required fields
     */
    public function getLoginFields();
    
    /**
     * Validate login configuration
     * 
     * @param array $config Login configuration
     * @return array ['success' => bool, 'message' => string]
     */
    public function validateConfig(array $config);
}
```

### 14.2. Sử Dụng Helper Functions

Module cung cấp các helper function để làm việc với connector:

#### 14.2.1. Lấy Connector Instance

```php
/**
 * Lấy instance của connector dựa trên tên platform
 * 
 * @param string $platform Tên platform (wordpress, haravan, shopify)
 * @return PlatformConnectorInterface|null Instance của connector hoặc null nếu không tìm thấy
 */
$connector = get_platform_connector($platform);
```

#### 14.2.2. Kiểm Tra Kết Nối

```php
/**
 * Kiểm tra kết nối đến platform
 * 
 * @param int $controller_id ID của controller
 * @return array Kết quả kiểm tra kết nối
 */
$result = test_platform_connection($controller_id);

// Kết quả trả về
// [
//     'success' => true/false,
//     'message' => 'Thông báo kết quả',
//     'site_info' => [
//         'title' => 'Tên trang web',
//         'description' => 'Mô tả trang web',
//         'url' => 'URL trang web',
//         'logo' => 'URL logo'
//     ]
// ]
```

#### 14.2.3. Lấy Categories

```php
/**
 * Lấy danh sách categories từ platform
 * 
 * @param int $controller_id ID của controller
 * @return array Danh sách categories
 */
$categories = get_platform_categories($controller_id);

// Kết quả trả về
// [
//     'success' => true/false,
//     'categories' => [
//         [
//             'id' => 'ID category',
//             'name' => 'Tên category',
//             'slug' => 'Slug category',
//             'description' => 'Mô tả category',
//             'parent' => 'ID category cha',
//             'count' => 'Số bài viết trong category'
//         ],
//         ...
//     ],
//     'message' => 'Thông báo kết quả'
// ]
```

#### 14.2.4. Đăng Bài Viết

```php
/**
 * Đăng bài viết lên platform
 * 
 * @param int $controller_id ID của controller
 * @param array $post Dữ liệu bài viết
 * @return array Kết quả đăng bài
 */
$post = [
    'title' => 'Tiêu đề bài viết',
    'content' => 'Nội dung bài viết',
    'excerpt' => 'Tóm tắt bài viết',
    'status' => 'publish',
    'categories' => [1, 2, 3],
    'tags' => [4, 5, 6],
    'featured_image' => 'URL hình ảnh đại diện'
];
$result = publish_platform_post($controller_id, $post);

// Kết quả trả về
// [
//     'success' => true/false,
//     'post_id' => 'ID bài viết đã đăng',
//     'post_url' => 'URL bài viết đã đăng',
//     'message' => 'Thông báo kết quả'
// ]
```

### 14.3. Tạo Connector Mới

Để tạo connector mới cho một platform, cần thực hiện các bước sau:

#### 14.3.1. Tạo File Connector

Tạo file `includes/platform_connectors/{platform}_connector.php` với nội dung:

```php
<?php
defined('BASEPATH') or exit('No direct script access allowed');

// Load interface
if (function_exists('module_dir_path')) {
    require_once(module_dir_path('topics') . 'includes/platform_connectors/platform_connector_interface.php');
} else {
    require_once(FCPATH . 'modules/topics/includes/platform_connectors/platform_connector_interface.php');
}

/**
 * {Platform} Connector
 * 
 * Connector for {Platform} platform
 */
class {Platform}Connector implements PlatformConnectorInterface
{
    /**
     * Test connection to {Platform}
     * 
     * @param array $config Login configuration
     * @return array ['success' => bool, 'message' => string]
     */
    public function testConnection(array $config)
    {
        // Validate config
        $validation = $this->validateConfig($config);
        if (!$validation['success']) {
            return $validation;
        }
        
        // Implement connection test logic here
        // ...
        
        return [
            'success' => true,
            'message' => 'Connection successful',
            'site_info' => [
                'title' => 'Site Title',
                'description' => 'Site Description',
                'url' => 'Site URL',
                'logo' => 'Logo URL'
            ]
        ];
    }
    
    /**
     * Get categories from {Platform}
     * 
     * @param array $config Login configuration
     * @return array ['success' => bool, 'categories' => array, 'message' => string]
     */
    public function getCategories(array $config)
    {
        // Implement get categories logic here
        // ...
        
        return [
            'success' => true,
            'categories' => [],
            'message' => 'Categories retrieved successfully'
        ];
    }
    
    /**
     * Publish a post to {Platform}
     * 
     * @param array $config Login configuration
     * @param array $post Post data
     * @return array ['success' => bool, 'post_id' => int|string, 'post_url' => string, 'message' => string]
     */
    public function publishPost(array $config, array $post)
    {
        // Implement publish post logic here
        // ...
        
        return [
            'success' => true,
            'post_id' => '123',
            'post_url' => 'https://example.com/post/123',
            'message' => 'Post published successfully'
        ];
    }
    
    /**
     * Get required login fields for {Platform}
     * 
     * @return array List of required fields
     */
    public function getLoginFields()
    {
        return ['url', 'api_key', 'api_secret'];
    }
    
    /**
     * Validate {Platform} login configuration
     * 
     * @param array $config Login configuration
     * @return array ['success' => bool, 'message' => string]
     */
    public function validateConfig(array $config)
    {
        $requiredFields = $this->getLoginFields();
        
        foreach ($requiredFields as $field) {
            if (!isset($config[$field]) || empty($config[$field])) {
                return [
                    'success' => false,
                    'message' => 'Missing required field: ' . $field
                ];
            }
        }
        
        return [
            'success' => true,
            'message' => 'Configuration is valid'
        ];
    }
}
```

#### 14.3.2. Cập Nhật Danh Sách Platform

Cập nhật danh sách platform trong cài đặt:

```php
// Trong file controllers/Settings.php
public function update_platforms()
{
    if (!has_permission('settings', '', 'edit')) {
        access_denied('settings');
    }
    
    $platforms = [
        'wordpress' => [
            'name' => 'WordPress',
            'description' => 'WordPress CMS',
            'icon' => 'fab fa-wordpress',
            'login_fields' => [
                'url' => [
                    'type' => 'text',
                    'label' => 'Website URL',
                    'placeholder' => 'https://example.com',
                    'required' => true
                ],
                'username' => [
                    'type' => 'text',
                    'label' => 'Username',
                    'placeholder' => 'admin',
                    'required' => true
                ],
                'application_password' => [
                    'type' => 'password',
                    'label' => 'Application Password',
                    'placeholder' => 'xxxx xxxx xxxx xxxx',
                    'required' => false
                ],
                'password' => [
                    'type' => 'password',
                    'label' => 'Password',
                    'placeholder' => '********',
                    'required' => false
                ]
            ]
        ],
        'haravan' => [
            'name' => 'Haravan',
            'description' => 'Haravan e-commerce platform',
            'icon' => 'fas fa-shopping-cart',
            'login_fields' => [
                'url' => [
                    'type' => 'text',
                    'label' => 'Shop URL',
                    'placeholder' => 'https://example.myharavan.com',
                    'required' => true
                ],
                'api_key' => [
                    'type' => 'text',
                    'label' => 'API Key',
                    'placeholder' => 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
                    'required' => true
                ],
                'api_secret' => [
                    'type' => 'password',
                    'label' => 'API Secret',
                    'placeholder' => '********',
                    'required' => true
                ]
            ]
        ],
        // Thêm platform mới ở đây
        'shopify' => [
            'name' => 'Shopify',
            'description' => 'Shopify e-commerce platform',
            'icon' => 'fab fa-shopify',
            'login_fields' => [
                'url' => [
                    'type' => 'text',
                    'label' => 'Shop URL',
                    'placeholder' => 'https://example.myshopify.com',
                    'required' => true
                ],
                'api_key' => [
                    'type' => 'text',
                    'label' => 'API Key',
                    'placeholder' => 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
                    'required' => true
                ],
                'api_secret' => [
                    'type' => 'password',
                    'label' => 'API Secret',
                    'placeholder' => '********',
                    'required' => true
                ],
                'access_token' => [
                    'type' => 'password',
                    'label' => 'Access Token',
                    'placeholder' => '********',
                    'required' => true
                ]
            ]
        ]
    ];
    
    update_option('topic_controller_platforms', json_encode($platforms));
    
    set_alert('success', _l('settings_updated'));
    redirect(admin_url('topics/settings'));
}
```

### 14.4. Ví Dụ: WordPress Connector

WordPress Connector là connector đã được implement đầy đủ và có thể được sử dụng làm mẫu cho các connector khác.

#### 14.4.1. Kiểm Tra Kết Nối

```php
public function testConnection(array $config)
{
    // Validate config
    $validation = $this->validateConfig($config);
    if (!$validation['success']) {
        return $validation;
    }
    
    // Prepare request URL
    $url = rtrim($config['url'], '/') . '/wp-json/wp/v2/categories';
    
    // Set up authentication - prioritize application password if available
    if (isset($config['application_password']) && !empty($config['application_password'])) {
        $auth = base64_encode($config['username'] . ':' . $config['application_password']);
    } else {
        $auth = base64_encode($config['username'] . ':' . $config['password']);
    }
    
    // Set up cURL request
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Basic ' . $auth,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    // Execute request
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    // Check for errors
    if ($error) {
        return [
            'success' => false,
            'message' => 'Connection error: ' . $error
        ];
    }
    
    // Check HTTP status code
    if ($httpCode >= 200 && $httpCode < 300) {
        // Fetch site information
        $siteInfo = $this->getSiteInfo($config);
        
        return [
            'success' => true,
            'message' => 'Connection successful',
            'site_info' => $siteInfo
        ];
    } else {
        $errorMessage = 'HTTP Error ' . $httpCode;
        
        // Try to parse error message from response
        if ($response) {
            $responseData = json_decode($response, true);
            if (isset($responseData['message'])) {
                $errorMessage .= ': ' . $responseData['message'];
            }
        }
        
        return [
            'success' => false,
            'message' => $errorMessage
        ];
    }
}
```

#### 14.4.2. Lấy Categories

```php
public function getCategories(array $config)
{
    // Validate config
    $validation = $this->validateConfig($config);
    if (!$validation['success']) {
        return [
            'success' => false,
            'categories' => [],
            'message' => $validation['message']
        ];
    }
    
    // Prepare request URL
    $url = rtrim($config['url'], '/') . '/wp-json/wp/v2/categories?per_page=100';
    
    // Set up authentication
    if (isset($config['application_password']) && !empty($config['application_password'])) {
        $auth = base64_encode($config['username'] . ':' . $config['application_password']);
    } else {
        $auth = base64_encode($config['username'] . ':' . $config['password']);
    }
    
    // Set up cURL request
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Basic ' . $auth,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    // Execute request
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    // Check for errors
    if ($error) {
        return [
            'success' => false,
            'categories' => [],
            'message' => 'Connection error: ' . $error
        ];
    }
    
    // Check HTTP status code
    if ($httpCode >= 200 && $httpCode < 300) {
        $categories = json_decode($response, true);
        
        if (!is_array($categories)) {
            return [
                'success' => false,
                'categories' => [],
                'message' => 'Invalid response format'
            ];
        }
        
        // Format categories
        $formattedCategories = [];
        foreach ($categories as $category) {
            $formattedCategories[] = [
                'id' => $category['id'],
                'name' => $category['name'],
                'slug' => $category['slug'],
                'description' => $category['description'],
                'parent' => $category['parent'],
                'count' => $category['count'],
                'link' => $category['link']
            ];
        }
        
        return [
            'success' => true,
            'categories' => $formattedCategories,
            'message' => 'Categories retrieved successfully'
        ];
    } else {
        $errorMessage = 'HTTP Error ' . $httpCode;
        
        // Try to parse error message from response
        if ($response) {
            $responseData = json_decode($response, true);
            if (isset($responseData['message'])) {
                $errorMessage .= ': ' . $responseData['message'];
            }
        }
        
        return [
            'success' => false,
            'categories' => [],
            'message' => $errorMessage
        ];
    }
}
```

### 14.5. Xử Lý Lỗi và Bảo Mật

Khi làm việc với các connector, cần chú ý các vấn đề sau:

#### 14.5.1. Xử Lý Lỗi

- Luôn kiểm tra và xử lý lỗi khi gọi API
- Ghi log lỗi để dễ dàng debug
- Hiển thị thông báo lỗi rõ ràng cho người dùng

```php
// Ví dụ: Xử lý lỗi khi gọi API
try {
    $result = $connector->testConnection($config);
    if (!$result['success']) {
        log_activity('API Error: ' . $result['message']);
        set_alert('danger', $result['message']);
        redirect(admin_url('topics/controllers/edit/' . $id));
    }
} catch (Exception $e) {
    log_activity('Exception: ' . $e->getMessage());
    set_alert('danger', 'An unexpected error occurred: ' . $e->getMessage());
    redirect(admin_url('topics/controllers/edit/' . $id));
}
```

#### 14.5.2. Bảo Mật

- Không lưu trữ mật khẩu dưới dạng plain text
- Sử dụng HTTPS cho tất cả các kết nối
- Kiểm tra và xác thực tất cả các input từ người dùng
- Sử dụng token thay vì mật khẩu khi có thể

```php
// Ví dụ: Xác thực input từ người dùng
public function validateConfig(array $config)
{
    $requiredFields = $this->getLoginFields();
    
    foreach ($requiredFields as $field) {
        if (!isset($config[$field]) || empty($config[$field])) {
            return [
                'success' => false,
                'message' => 'Missing required field: ' . $field
            ];
        }
    }
    
    // Kiểm tra URL
    if (isset($config['url'])) {
        $url = filter_var($config['url'], FILTER_VALIDATE_URL);
        if (!$url) {
            return [
                'success' => false,
                'message' => 'Invalid URL format'
            ];
        }
        
        // Kiểm tra HTTPS
        if (strpos($url, 'https://') !== 0) {
            return [
                'success' => false,
                'message' => 'URL must use HTTPS'
            ];
        }
    }
    
    return [
        'success' => true,
        'message' => 'Configuration is valid'
    ];
}
```

### 14.6. Mở Rộng Chức Năng

Để mở rộng chức năng của connector, có thể thêm các method mới vào interface và implement chúng trong các connector:

```php
// Thêm vào interface
/**
 * Get posts from the platform
 * 
 * @param array $config Login configuration
 * @param array $args Additional arguments
 * @return array ['success' => bool, 'posts' => array, 'message' => string]
 */
public function getPosts(array $config, array $args = []);

/**
 * Get tags from the platform
 * 
 * @param array $config Login configuration
 * @return array ['success' => bool, 'tags' => array, 'message' => string]
 */
public function getTags(array $config);

/**
 * Get media from the platform
 * 
 * @param array $config Login configuration
 * @param array $args Additional arguments
 * @return array ['success' => bool, 'media' => array, 'message' => string]
 */
public function getMedia(array $config, array $args = []);
```

Sau đó, cập nhật các helper function tương ứng:

```php
/**
 * Get posts from a platform
 * 
 * @param int $controller_id Controller ID
 * @param array $args Additional arguments
 * @return array Posts
 */
function get_platform_posts($controller_id, $args = [])
{
    $CI = &get_instance();
    $CI->load->model('Topic_controller_model');
    
    // Get controller data
    $controller = $CI->Topic_controller_model->get($controller_id);
    
    if (!$controller) {
        return [
            'success' => false,
            'posts' => [],
            'message' => 'Controller not found'
        ];
    }
    
    // Get platform and login config
    $platform = $controller->platform;
    $login_config = json_decode($controller->login_config, true);
    
    if (!$platform || !$login_config) {
        return [
            'success' => false,
            'posts' => [],
            'message' => 'Missing platform or login configuration'
        ];
    }
    
    // Get platform connector
    $connector = get_platform_connector($platform);
    
    if (!$connector) {
        return [
            'success' => false,
            'posts' => [],
            'message' => 'Platform connector not found: ' . $platform
        ];
    }
    
    // Check if connector supports getPosts method
    if (!method_exists($connector, 'getPosts')) {
        return [
            'success' => false,
            'posts' => [],
            'message' => 'Platform does not support getting posts'
        ];
    }
    
    // Get posts
    return $connector->getPosts($login_config, $args);
}
```

## 15. Triển Khai Tab Tags

### 15.1. Tạo File View Tab Tags

Tạo file `views/controllers/tabs/tags.php`:

```php
<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="tags-container">
    <div class="row mbot15">
        <div class="col-md-6">
            <h4><?php echo _l('controller_tag_list'); ?></h4>
        </div>
        <div class="col-md-6 text-right">
            <button type="button" class="btn btn-info" id="sync_tags">
                <i class="fa fa-refresh"></i> <?php echo _l('controller_sync_data'); ?>
            </button>
            <button type="button" class="btn btn-success" id="save_tags_state">
                <i class="fa fa-save"></i> <?php echo _l('controller_save_state'); ?>
            </button>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-12">
            <div id="tags_loading" class="text-center mtop20 mbot20">
                <i class="fa fa-spinner fa-spin fa-2x"></i>
                <p><?php echo _l('controller_loading_data'); ?></p>
            </div>
            
            <div id="tags_list" class="mtop20" style="display: none;">
                <!-- Tags list will be loaded here via JavaScript -->
            </div>
            
            <div id="tags_empty" class="alert alert-info mtop20" style="display: none;">
                <?php echo _l('controller_no_data'); ?>
            </div>
        </div>
    </div>
    
    <div class="row mtop20">
        <div class="col-md-12">
            <small class="text-muted">
                <?php echo _l('controller_last_sync'); ?>: 
                <span id="tags_last_sync">-</span>
            </small>
        </div>
    </div>
</div>
```

### 15.2. Thêm JavaScript Xử Lý Tab Tags

Thêm đoạn code sau vào file `assets/js/controllers.js`:

```javascript
// Tags Tab Functions
function loadTags(controllerId) {
    if (!controllerId) {
        return;
    }
    
    // Show loading
    $('#tags_list').hide();
    $('#tags_empty').hide();
    $('#tags_loading').show();
    
    // Load tags from server
    $.ajax({
        url: admin_url + 'topics/controllers/get_tags/' + controllerId,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            $('#tags_loading').hide();
            
            if (response.success && response.data && response.data.length > 0) {
                // Render tags list
                renderTagsList(response.data);
                $('#tags_list').show();
                
                // Update last sync time
                if (response.last_sync) {
                    $('#tags_last_sync').text(response.last_sync);
                }
            } else {
                // Show empty message
                $('#tags_empty').show();
            }
        },
        error: function() {
            $('#tags_loading').hide();
            $('#tags_empty').show();
            alert_float('danger', controller_sync_error);
        }
    });
}

function renderTagsList(tags) {
    // Build tags hierarchy
    var tagsMap = {};
    var rootTags = [];
    
    // First pass: create map of tags
    tags.forEach(function(tag) {
        tagsMap[tag.tag_id] = {
            ...tag,
            children: []
        };
    });
    
    // Second pass: build hierarchy
    tags.forEach(function(tag) {
        if (tag.parent_id && tagsMap[tag.parent_id]) {
            // Add to parent's children
            tagsMap[tag.parent_id].children.push(tagsMap[tag.tag_id]);
        } else {
            // Root tag
            rootTags.push(tagsMap[tag.tag_id]);
        }
    });
    
    // Render list
    var html = '<ul class="tags-list">';
    rootTags.forEach(function(tag) {
        html += renderTagNode(tag);
    });
    html += '</ul>';
    
    $('#tags_list').html(html);
    
    // Add click handlers for expand/collapse
    $('.tag-toggle').on('click', function(e) {
        e.preventDefault();
        $(this).closest('.tag-item').toggleClass('expanded');
    });
}

function renderTagNode(tag) {
    var hasChildren = tag.children && tag.children.length > 0;
    var html = '<li class="tag-item">';
    
    // Tag header
    html += '<div class="tag-header">';
    
    // Toggle button (if has children)
    if (hasChildren) {
        html += '<a href="#" class="tag-toggle"><i class="fa fa-caret-right"></i></a>';
    } else {
        html += '<span class="tag-toggle-placeholder"></span>';
    }
    
    // Tag name and count
    html += '<span class="tag-name">' + tag.name + '</span>';
    html += '<span class="tag-count">(' + tag.count + ')</span>';
    
    // View on site link
    if (tag.url) {
        html += '<a href="' + tag.url + '" target="_blank" class="tag-link"><i class="fa fa-external-link"></i></a>';
    }
    
    html += '</div>';
    
    // Children (if any)
    if (hasChildren) {
        html += '<ul class="tag-children">';
        tag.children.forEach(function(child) {
            html += renderTagNode(child);
        });
        html += '</ul>';
    }
    
    html += '</li>';
    return html;
}

function syncTags(controllerId) {
    if (!controllerId) {
        return;
    }
    
    // Show loading
    $('#tags_list').hide();
    $('#tags_empty').hide();
    $('#tags_loading').show();
    
    // Disable sync button
    $('#sync_tags').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> ' + controller_sync_data);
    
    // Sync tags from server
    $.ajax({
        url: admin_url + 'topics/controllers/sync_tags/' + controllerId,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            // Re-enable sync button
            $('#sync_tags').prop('disabled', false).html('<i class="fa fa-refresh"></i> ' + controller_sync_data);
            
            if (response.success) {
                // Show success message
                alert_float('success', controller_sync_success);
                
                // Reload tags
                loadTags(controllerId);
            } else {
                // Show error message
                $('#tags_loading').hide();
                alert_float('danger', response.message || controller_sync_error);
            }
        },
        error: function() {
            // Re-enable sync button
            $('#sync_tags').prop('disabled', false).html('<i class="fa fa-refresh"></i> ' + controller_sync_data);
            
            // Show error message
            $('#tags_loading').hide();
            alert_float('danger', controller_sync_error);
        }
    });
}

function saveTagsState(controllerId) {
    if (!controllerId) {
        return;
    }
    
    // Get expanded tags
    var expandedTags = [];
    $('.tag-item.expanded').each(function() {
        var tagId = $(this).data('tag-id');
        if (tagId) {
            expandedTags.push(tagId);
        }
    });
    
    // Save state
    $.ajax({
        url: admin_url + 'topics/controllers/save_tags_state/' + controllerId,
        type: 'POST',
        data: {
            expanded_tags: expandedTags
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Show success message
                alert_float('success', controller_state_saved);
            } else {
                // Show error message
                alert_float('danger', response.message || controller_state_save_error);
            }
        },
        error: function() {
            // Show error message
            alert_float('danger', controller_state_save_error);
        }
    });
}
```

### 15.3. Thêm CSS Cho Tab Tags

Thêm đoạn code sau vào file `assets/css/draft_writer.css`:

```css
/* Tags List Styles */
.tags-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.tag-item {
    margin-bottom: 5px;
}

.tag-header {
    display: flex;
    align-items: center;
    padding: 8px 10px;
    background-color: #f9f9f9;
    border-radius: 4px;
    border: 1px solid #e0e0e0;
    transition: background-color 0.2s ease;
}

.tag-header:hover {
    background-color: #f0f0f0;
}

.tag-toggle,
.tag-toggle-placeholder {
    margin-right: 10px;
    width: 15px;
    text-align: center;
}

.tag-toggle .fa {
    transition: transform 0.2s ease;
}

.tag-item.expanded > .tag-header .tag-toggle .fa {
    transform: rotate(90deg);
}

.tag-name {
    flex-grow: 1;
    font-weight: 500;
}

.tag-count {
    margin: 0 10px;
    color: #777;
}

.tag-link {
    color: #03a9f4;
    margin-left: 5px;
}

.tag-children {
    list-style: none;
    padding-left: 25px;
    margin-top: 5px;
    display: none;
}

.tag-item.expanded > .tag-children {
    display: block;
}

/* Loading Indicator */
#tags_loading {
    color: #777;
}

#tags_loading .fa-spin {
    margin-bottom: 10px;
}
```

### 15.4. Cập Nhật Controller

Thêm các phương thức sau vào file `controllers/Controllers.php`:

```php
/**
 * Get tags for a controller
 * 
 * @param int $id Controller ID
 * @return void
 */
public function get_tags($id)
{
    if (!has_permission('topics', '', 'view')) {
        ajax_access_denied();
    }
    
    // Check if controller exists
    $controller = $this->Topic_controller_model->get($id);
    if (!$controller) {
        echo json_encode([
            'success' => false,
            'message' => 'Controller not found'
        ]);
        return;
    }
    
    // Get tags from database
    $tags = $this->Topic_controller_model->get_controller_tags($id);
    
    // Get last sync time
    $last_sync = null;
    if (!empty($tags)) {
        $last_sync = max(array_map(function($tag) {
            return $tag['last_sync'];
        }, $tags));
    }
    
    echo json_encode([
        'success' => true,
        'data' => $tags,
        'last_sync' => $last_sync ? date('d/m/Y H:i:s', strtotime($last_sync)) : null
    ]);
}

/**
 * Sync tags for a controller
 * 
 * @param int $id Controller ID
 * @return void
 */
public function sync_tags($id)
{
    if (!has_permission('topics', '', 'edit')) {
        ajax_access_denied();
    }
    
    // Check if controller exists
    $controller = $this->Topic_controller_model->get($id);
    if (!$controller) {
        echo json_encode([
            'success' => false,
            'message' => 'Controller not found'
        ]);
        return;
    }
    
    // Sync tags from platform
    $result = get_platform_tags($id);
    
    echo json_encode($result);
}

/**
 * Save tags state for a controller
 * 
 * @param int $id Controller ID
 * @return void
 */
public function save_tags_state($id)
{
    if (!has_permission('topics', '', 'edit')) {
        ajax_access_denied();
    }
    
    // Check if controller exists
    $controller = $this->Topic_controller_model->get($id);
    if (!$controller) {
        echo json_encode([
            'success' => false,
            'message' => 'Controller not found'
        ]);
        return;
    }
    
    // Get expanded tags
    $expanded_tags = $this->input->post('expanded_tags');
    
    // Save state
    $success = $this->Topic_controller_model->save_controller_state($id, [
        'expanded_tags' => $expanded_tags
    ]);
    
    echo json_encode([
        'success' => $success,
        'message' => $success ? 'State saved successfully' : 'Failed to save state'
    ]);
}
```

### 15.5. Mối Quan Hệ Giữa Blog và Tags

Mối quan hệ giữa blog và tags được lưu trữ trong bảng `tbltopic_controller_blog_relationships`.

```php
/**
 * Get blog relationships for a controller
 * 
 * @param int $id Controller ID
 * @return void
 */
public function get_blog_relationships($id)
{
    if (!has_permission('topics', '', 'view')) {
        ajax_access_denied();
    }
    
    // Check if controller exists
    $controller = $this->Topic_controller_model->get($id);
    if (!$controller) {
        echo json_encode([
            'success' => false,
            'message' => 'Controller not found'
        ]);
        return;
    }
    
    // Get blog relationships from database
    $relationships = $this->Topic_controller_model->get_blog_relationships($id);
    
    echo json_encode([
        'success' => true,
        'data' => $relationships,
        'last_sync' => $relationships ? date('d/m/Y H:i:s', strtotime($relationships['last_sync'])) : null
    ]);
}

/**
 * Save blog relationships for a controller
 * 
 * @param int $id Controller ID
 * @return void
 */
public function save_blog_relationships($id)
{
    if (!has_permission('topics', '', 'edit')) {
        ajax_access_denied();
    }
    
    // Check if controller exists
    $controller = $this->Topic_controller_model->get($id);
    if (!$controller) {
        echo json_encode([
            'success' => false,
            'message' => 'Controller not found'
        ]);
        return;
    }
    
    // Get blog relationships data
    $relationships = $this->input->post('relationships');
    
    // Save relationships
    $success = $this->Topic_controller_model->save_blog_relationships($id, $relationships);
    
    echo json_encode([
        'success' => $success,
        'message' => $success ? 'Relationships saved successfully' : 'Failed to save relationships'
    ]);
}
```

## Tổng Kết Quá Trình Nâng Cấp Chức Năng Topic Controller

### Các Phần Đã Hoàn Thành

1. **Cấu Trúc Cơ Sở Dữ Liệu**
   - Đã tạo các bảng dữ liệu mới: `tbltopic_controller_categories`, `tbltopic_controller_tags`, `tbltopic_controller_blogs`, `tbltopic_controller_blog_relationships`
   - Đã thiết lập mối quan hệ giữa các bảng dữ liệu
   - Đã cập nhật phiên bản module

2. **Phát Triển Backend**
   - Đã cập nhật Model để xử lý dữ liệu từ các bảng mới
   - Đã cập nhật Controller với các phương thức mới
   - Đã tích hợp với API của các nền tảng bên ngoài

3. **Phát Triển Frontend**
   - Đã cập nhật View để hiển thị dữ liệu từ các bảng mới
   - Đã thêm JavaScript để xử lý tương tác người dùng
   - Đã thêm CSS để tạo giao diện người dùng

4. **Tổ Chức View Con**
   - Đã tạo cấu trúc thư mục view con
   - Đã tích hợp view con vào view chính
   - Đã triển khai ví dụ view con: Tab Categories

5. **WordPress Connector**
   - Đã mở rộng WordPress Connector hiện có
   - Đã cập nhật các helper functions
   - Đã cập nhật model để xử lý dữ liệu từ WordPress

6. **Tích Hợp Với Platform Connectors**
   - Đã tổng quan về Platform Connectors
   - Đã sử dụng Helper Functions
   - Đã tạo connector mới
   - Đã xử lý lỗi và bảo mật

### Kế Hoạch Triển Khai Tab Tags

1. **Tạo File View Tab Tags**
   - Tạo file `views/controllers/tabs/tags.php` với nội dung đã được định nghĩa
   - Đảm bảo file có cấu trúc tương tự như Tab Categories
   - Thêm các phần tử UI cần thiết: danh sách tags, nút refresh, nút save state

2. **Thêm JavaScript Xử Lý Tab Tags**
   - Thêm các hàm JavaScript vào file `assets/js/controllers.js`
   - Triển khai các chức năng: load tags, render tags, xử lý sự kiện click, lưu trạng thái
   - Đảm bảo tương thích với các chức năng JavaScript hiện có

3. **Thêm CSS Cho Tab Tags**
   - Thêm CSS vào file `assets/css/draft_writer.css`
   - Tạo style cho tags list, tag item, và các phần tử UI khác
   - Đảm bảo responsive design

4. **Cập Nhật Controller**
   - Thêm các phương thức mới vào file `controllers/Controllers.php`
   - Triển khai các API endpoints: get_tags, sync_tags, save_tags_state
   - Cập nhật phương thức view() để tích hợp tab Tags

5. **Xây Dựng Mối Quan Hệ Giữa Blog và Tags**
   - Sử dụng bảng `tbltopic_controller_blog_relationships` để lưu trữ mối quan hệ
   - Triển khai các phương thức để lấy và lưu mối quan hệ
   - Hiển thị các bài viết liên quan khi chọn một tag

### Kiểm Tra Database

Qua kiểm tra database, chúng ta đã xác nhận:

1. Các bảng dữ liệu đã được tạo đúng cấu trúc:
   - `tbltopic_controller_categories`
   - `tbltopic_controller_tags`
   - `tbltopic_controller_blogs`
   - `tbltopic_controller_blog_relationships`

2. Bảng `tbltopic_controller_categories` đã có dữ liệu (52 bản ghi)
3. Bảng `tbltopic_controller_tags` chưa có dữ liệu
4. Hiện tại có 1 controller với platform là WordPress

### Các Bước Tiếp Theo

1. **Triển Khai Tab Tags**
   - Tạo file view, thêm JavaScript và CSS
   - Cập nhật controller với các phương thức mới
   - Xây dựng mối quan hệ giữa blog và tags

2. **Testing**
   - Kiểm thử chức năng load tags từ database
   - Kiểm thử chức năng sync tags từ API
   - Kiểm thử chức năng lưu trạng thái
   - Kiểm thử hiển thị các bài viết liên quan

3. **Deployment**
   - Đảm bảo tất cả các file đã được cập nhật
   - Kiểm tra lại cấu trúc database
   - Triển khai lên môi trường production

4. **Documentation**
   - Cập nhật tài liệu hướng dẫn sử dụng
   - Thêm hướng dẫn về Tab Tags
   - Thêm hướng dẫn về mối quan hệ giữa blog và tags
