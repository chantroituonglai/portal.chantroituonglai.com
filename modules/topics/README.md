# Topics Management Module

## Overview
Topics Management là một module quản lý topics với khả năng theo dõi các hành động và trạng thái. Module này được phát triển cho hệ thống Perfex CRM.

## Features
- Quản lý Topics với thông tin chi tiết
- Theo dõi lịch sử tương tác của từng topic
- Quản lý Action Types (Loại hành động) với cấu trúc phân cấp
- Quản lý Action States (Trạng thái hành động) với mã màu tùy chỉnh
- Quản lý Topic Master và Topic Targets
- Theo dõi trạng thái online của nhân viên trong topics
- Phân quyền chi tiết cho từng chức năng
- API tích hợp
- Sao chép nhanh Topic ID vào clipboard
- Bảng điều khiển trực quan với thống kê và bộ lọc
- Hỗ trợ đa ngôn ngữ (Tiếng Anh, Tiếng Việt)
- Thao tác hàng loạt (bulk actions)
- Giao diện người dùng hiện đại với Tailwind CSS
- Cache busting tự động cho assets
- Tích hợp tự động hóa với N8N Workflow
- Hệ thống thông báo thời gian thực
- Hiển thị dữ liệu đã xử lý (Processed Data)
- Theo dõi trạng thái kết nối và cập nhật
- Logging chi tiết và xử lý lỗi nâng cao
- Hiển thị Action Code trong chi tiết Topic
- Tối ưu hóa hiển thị nội dung gốc và Google content

## System Requirements
- PHP >= 7.4
- MySQL/MariaDB
- Perfex CRM latest version

## Installation
1. Copy toàn bộ thư mục module vào `modules/topics`
2. Truy cập Admin Panel -> Setup -> Modules
3. Tìm "Topics Management" và click Install
4. Module sẽ tự động cài đặt database và các thành phần cần thiết

## Structure
```
topics/
├── assets/
│   ├── css/
│   │   └── topics.css                # Main stylesheet for the module
│   └── js/
│       ├── topics.js                 # Main JavaScript functionality
│       ├── topic_detail.js           # Topic detail page functionality
│       ├── topic_dashboard.js        # Dashboard functionality
│       └── processors/               # JavaScript processors for various actions
├── controllers/
│   ├── Topics.php                    # Main topic controller
│   ├── Topics_api.php                # API endpoints for topics
│   ├── Writing.php                   # Controller for writing features
│   ├── Action_types.php              # Controller for action types
│   ├── Action_states.php             # Controller for action states
│   └── Topic_master.php              # Controller for topic master
├── helpers/
│   ├── api_helper.php                # Helper functions for API
│   ├── json_fixer_helper.php         # JSON validation and fixing
│   ├── logs_viewers_helper.php       # Functions for viewing logs
│   ├── topic_platform_helper.php     # Platform-specific helper functions
│   ├── topics_data_processor_helper.php # Data processing functions
│   ├── topics_display_processor_helper.php # Display processing functions
│   ├── topics_setup_helper.php       # Setup and configuration helpers
│   └── topic_action_processor_helpers/ # Action processor helpers
│       ├── topic_action_processor_helper.php          # Base processor
│       ├── topic_action_processor_DraftWritingProcessor_helper.php
│       ├── topic_action_processor_TopicComposerProcessor_helper.php
│       ├── topic_action_processor_ImageGenerateToggleProcessor_helper.php
│       ├── topic_action_processor_WordPressPostSelectionProcessor_helper.php
│       ├── topic_action_processor_WordPressPostActionProcessor_helper.php
│       ├── topic_action_processor_InitGooglesheetRawItemProcessor_helper.php
│       └── topic_action_processor_SocialMediaPostActionProcessor_helper.php
├── language/
│   ├── english/
│   │   └── topics_lang.php          # English language variables
│   └── vietnamese/
│       └── topics_lang.php          # Vietnamese language variables
├── migrations/
│   ├── 100_version_100.php         # Initial migration
│   ├── 101_version_101.php         # Version 1.0.1 migration
│   └── ...                         # Additional version migrations
├── models/
│   ├── Topics_model.php            # Main topics model
│   ├── Action_type_model.php       # Model for action types
│   ├── Action_state_model.php      # Model for action states
│   ├── Topic_master_model.php      # Model for topic master
│   ├── Topic_target_model.php      # Model for topic targets
│   ├── Topic_online_status_model.php # Model for online status tracking
│   ├── Topic_automation_log_model.php # Model for automation logs
│   ├── Topic_controller_model.php  # Controller model for topics
│   ├── Topic_sync_log_model.php    # Model for synchronization logs
│   ├── Topic_external_data_model.php # Model for external data
│   └── Topic_action_button_model.php # Model for action buttons
├── views/
│   ├── index.php                   # Main index view
│   ├── create.php                  # Create topic view
│   ├── edit.php                    # Edit topic view
│   ├── detail.php                  # Topic detail view
│   ├── dashboard.php               # Dashboard view
│   ├── topics_table.php            # Topics table view
│   ├── action_types/               # Views for action types
│   │   ├── manage.php              # Manage action types
│   │   ├── create.php              # Create action type
│   │   └── edit.php                # Edit action type
│   ├── action_states/              # Views for action states
│   │   ├── manage.php              # Manage action states
│   │   ├── create.php              # Create action state
│   │   └── edit.php                # Edit action state
│   ├── action_buttons/             # Views for action buttons
│   │   ├── manage.php              # Manage action buttons
│   │   ├── create.php              # Create action button
│   │   └── edit.php                # Edit action button
│   ├── topic_master/               # Views for topic master
│   │   ├── manage.php              # Manage topic master
│   │   ├── create.php              # Create topic master
│   │   └── edit.php                # Edit topic master
│   ├── settings/                   # Views for settings
│   │   └── index.php               # Settings index
│   ├── includes/                   # Included view components
│   │   ├── modals/                 # Modal views
│   │   └── partials/               # Partial views
│   ├── controllers/                # Views for the controllers
│   └── topics/                     # Additional topic-related views
├── config/
│   ├── routes.php                  # Custom routes
│   └── topics.php                  # Module configuration
├── install.php                     # Installation script
├── uninstall.php                   # Uninstallation script
├── hooks.php                       # Hooks integration
└── topics.php                      # Module definition file
```

## Database Tables
- `tbltopics` - Lưu trữ thông tin topics
- `tbltopic_action_types` - Quản lý các loại hành động
- `tbltopic_action_states` - Quản lý các trạng thái
- `tbltopic_master` - Quản lý topic master
- `tbltopic_target` - Quản lý topic targets  
- `tblstaff_online_status` - Theo dõi trạng thái online
- `tbltopic_automation_logs` - Lưu trữ lịch sử tự động hóa N8N
- `tbltopic_notifications` - Quản lý thông báo topics

## Database Structure & Relationships

### Table: `tbltopics`
```sql
CREATE TABLE `tbltopics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `topicid` varchar(255) NOT NULL,
  `topictitle` varchar(255) NOT NULL,
  `position` int(11) DEFAULT 0,
  `data` longtext DEFAULT NULL,
  `log` text NOT NULL,
  `action_type_code` varchar(50) DEFAULT NULL,
  `action_state_code` varchar(50) DEFAULT NULL,
  `target_id` int(11) DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1,
  `datecreated` datetime DEFAULT current_timestamp(),
  `dateupdated` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `automation_id` varchar(250) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `topicid` (`topicid`),
  KEY `idx_target_id` (`target_id`),
  KEY `idx_status` (`status`),
  KEY `action_type_code` (`action_type_code`),
  KEY `action_state_code` (`action_state_code`),
  KEY `idx_automation_id` (`automation_id`),
  CONSTRAINT `fk_topic_action_state` FOREIGN KEY (`action_state_code`) REFERENCES `tbltopic_action_states` (`action_state_code`) ON DELETE SET NULL,
  CONSTRAINT `fk_topic_action_type` FOREIGN KEY (`action_type_code`) REFERENCES `tbltopic_action_types` (`action_type_code`) ON DELETE SET NULL,
  CONSTRAINT `fk_topic_master` FOREIGN KEY (`topicid`) REFERENCES `tbltopic_master` (`topicid`) ON DELETE CASCADE,
  CONSTRAINT `fk_topic_target` FOREIGN KEY (`target_id`) REFERENCES `tbltopic_target` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

### Table: `tbltopic_action_types`
```sql
CREATE TABLE `tbltopic_action_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `action_type_code` varchar(50) NOT NULL,
  `datecreated` datetime DEFAULT current_timestamp(),
  `dateupdated` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `position` int(11) DEFAULT 0,
  `parent_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `action_type_code` (`action_type_code`),
  KEY `parent_id` (`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

### Table: `tbltopic_action_states`
```sql
CREATE TABLE `tbltopic_action_states` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `action_state_code` varchar(50) NOT NULL,
  `action_type_code` varchar(50) NOT NULL,
  `datecreated` datetime DEFAULT current_timestamp(),
  `dateupdated` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `color` varchar(7) DEFAULT '#000000',
  `position` int(11) DEFAULT 0,
  `valid_data` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `action_state_code` (`action_state_code`),
  KEY `action_type_code` (`action_type_code`),
  CONSTRAINT `fk_state_action_type` FOREIGN KEY (`action_type_code`) REFERENCES `tbltopic_action_types` (`action_type_code`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

### Table: `tbltopic_master`
```sql
CREATE TABLE `tbltopic_master` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `topicid` varchar(255) NOT NULL,
  `topictitle` varchar(255) DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1,
  `datecreated` datetime DEFAULT current_timestamp(),
  `dateupdated` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `controller_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `topicid` (`topicid`),
  KEY `controller_id` (`controller_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

### Table: `tbltopic_target`
```sql
CREATE TABLE `tbltopic_target` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `target_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `target_type` varchar(50) NOT NULL,
  `status` tinyint(1) DEFAULT 1,
  `datecreated` datetime DEFAULT current_timestamp(),
  `dateupdated` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `target_id` (`target_id`),
  KEY `target_type` (`target_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

### Table: `tbltopic_automation_logs`
```sql
CREATE TABLE `tbltopic_automation_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `topic_id` varchar(255) NOT NULL,
  `automation_id` varchar(250) NOT NULL,
  `workflow_id` varchar(250) NOT NULL,
  `status` varchar(50) DEFAULT 'pending',
  `response_data` text DEFAULT NULL,
  `datecreated` datetime DEFAULT current_timestamp(),
  `dateupdated` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `topic_id` (`topic_id`),
  KEY `automation_id` (`automation_id`),
  KEY `workflow_id` (`workflow_id`),
  KEY `status` (`status`),
  CONSTRAINT `fk_automation_topic` FOREIGN KEY (`topic_id`) REFERENCES `tbltopic_master` (`topicid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

### Table: `tbltopic_controller`
```sql
CREATE TABLE `tbltopic_controllers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `platform_type` varchar(50) NOT NULL,
  `api_credentials` text DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1,
  `datecreated` datetime DEFAULT current_timestamp(),
  `dateupdated` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `platform_type` (`platform_type`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

### Table: `tbltopic_external_data`
```sql
CREATE TABLE `tbltopic_external_data` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `topic_master_id` int(11) NOT NULL,
  `rel_type` varchar(100) NOT NULL,
  `rel_id` varchar(255) NOT NULL,
  `rel_data` text DEFAULT NULL,
  `rel_data_raw` longtext DEFAULT NULL,
  `datecreated` datetime DEFAULT current_timestamp(),
  `dateupdated` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_topic_rel` (`topic_master_id`, `rel_type`, `rel_id`),
  KEY `topic_master_id` (`topic_master_id`),
  KEY `rel_type` (`rel_type`),
  KEY `rel_id` (`rel_id`),
  CONSTRAINT `fk_external_data_topic_master` FOREIGN KEY (`topic_master_id`) REFERENCES `tbltopic_master` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

### Table: `tbltopic_sync_logs`
```sql
CREATE TABLE `tbltopic_sync_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `controller_id` int(11) NOT NULL,
  `session_id` varchar(100) NOT NULL,
  `rel_type` varchar(50) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'in_progress',
  `summary_data` longtext DEFAULT NULL,
  `log_data` longtext DEFAULT NULL,
  `start_time` datetime DEFAULT NULL,
  `end_time` datetime DEFAULT NULL,
  `datecreated` datetime NOT NULL,
  `dateupdated` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `controller_id` (`controller_id`),
  KEY `session_id` (`session_id`),
  KEY `rel_type` (`rel_type`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
```

### Database Relationships

1. **Topics & Action Types**
   - `tbltopics.action_type_code` → `tbltopic_action_types.action_type_code`
   - Mỗi topic có thể có một action type
   - Một action type có thể có nhiều topics
   - Foreign key với ON DELETE SET NULL

2. **Topics & Action States**
   - `tbltopics.action_state_code` → `tbltopic_action_states.action_state_code`
   - Mỗi topic có thể có một action state
   - Một action state có thể có nhiều topics
   - Foreign key với ON DELETE SET NULL

3. **Topics & Topic Master**
   - `tbltopics.topicid` → `tbltopic_master.topicid`
   - Mỗi topic phải thuộc về một topic master
   - Một topic master có thể có nhiều topics
   - Foreign key với ON DELETE CASCADE

4. **Topics & Topic Targets**
   - `tbltopics.target_id` → `tbltopic_target.id`
   - Một topic có thể có một target
   - Một target có thể được sử dụng bởi nhiều topics
   - Foreign key với ON DELETE SET NULL

5. **Action Types & Action States**
   - `tbltopic_action_states.action_type_code` → `tbltopic_action_types.action_type_code`
   - Một action type có thể có nhiều action states
   - Mỗi action state phải thuộc về một action type
   - Foreign key với ON DELETE CASCADE

6. **Action Types Self-Referential**
   - `tbltopic_action_types.parent_id` → `tbltopic_action_types.id`
   - Một action type có thể có một parent action type (hierarchical structure)
   - Một action type có thể có nhiều child action types

7. **Topic Automation Logs & Topic Master**
   - `tbltopic_automation_logs.topic_id` → `tbltopic_master.topicid`
   - Một topic master có thể có nhiều automation logs
   - Mỗi automation log phải thuộc về một topic master
   - Foreign key với ON DELETE CASCADE

8. **Topic Master & Controllers**
   - `tbltopic_master.controller_id` → `tbltopic_controllers.id`
   - Một topic master có thể thuộc về một controller
   - Một controller có thể quản lý nhiều topic masters

9. **External Data & Topic Master**
   - `tbltopic_external_data.topic_master_id` → `tbltopic_master.id`
   - Một topic master có thể có nhiều external data records
   - Mỗi external data record phải thuộc về một topic master
   - Foreign key với ON DELETE CASCADE

### Cấu Trúc Key Và Mối Quan Hệ

```
     +----------------+        +-------------------+       +-----------------+
     | tbltopic_target |<------| tbltopics         |------>| tbltopic_master |
     +----------------+        +-------------------+       +-----------------+
                               |                   |               |
                               |                   |               |
                       +-------v-------+   +-------v-------+       |
                       | action_types  |   | action_states |       |
                       +---------------+   +---------------+       |
                               ^                   ^               |
                               |                   |               |
                               +-------------------+               |
                                                                   |
                                                                   v
     +------------------+     +-------------------+      +-------------------+
     | tbltopic_         |     | tbltopic_         |      | tbltopic_         |
     | controllers      |<----| external_data     |<-----| automation_logs   |
     +------------------+     +-------------------+      +-------------------+
                |
                |
                v
     +------------------+
     | tbltopic_        |
     | sync_logs        |
     +------------------+
```

### Indexes & Performance Considerations

1. **Primary Keys**
   - Tất cả các bảng đều có primary key là `id` (AUTO_INCREMENT)
   - `tbltopic_master.topicid` có UNIQUE constraint

2. **Foreign Keys**
   - Tất cả các foreign keys đều được đánh index
   - Các liên kết sử dụng các options ON DELETE như CASCADE và SET NULL tùy theo mối quan hệ

3. **Additional Indexes**
   - Fields dùng cho tìm kiếm và sắp xếp được đánh index: status, position, target_id
   - Các fields được sử dụng trong JOIN operations

4. **Performance Optimizations**
   - InnoDB engine được sử dụng cho tất cả các bảng
   - UTF8MB4 character set đảm bảo hỗ trợ đầy đủ Unicode
   - AUTO_UPDATE timestamps cho việc theo dõi thay đổi
   - Soft delete pattern qua field status

5. **Data Integrity**
   - Constraints đảm bảo tính toàn vẹn dữ liệu
   - Foreign key relationships đảm bảo dữ liệu liên kết không bị mất

## Permissions
Module sử dụng các permission sau:
- View Topics
- Create Topics
- Edit Topics 
- Delete Topics
- Manage Action Types
- Manage Action States
- Manage Topic Master
- Manage Topic Targets

## Usage
### Topics
- Tạo mới topic với title, action type và action state
- Theo dõi lịch sử thay đổi của topic
- Xem chi tiết và chỉnh sửa thông tin topic
- Sao chép nhanh Topic ID vào clipboard
- Thực hiện các thao tác hàng loạt
- Lọc và tìm kiếm topics
- Xem dữ liệu đã xử lý trong modal
- Nhận thông báo thời gian thực khi có cập nhật
- Theo dõi trạng thái kết nối
- Xem Action Code trong chi tiết Topic

### Action Types
- Quản lý các loại hành động có thể thực hiện với topic
- Hỗ trợ cấu trúc phân cấp (parent-child)
- Sắp xếp vị trí hiển thị
- Thêm/sửa/xóa action types
- Mỗi action type có code riêng biệt

### Action States
- Quản lý các trạng thái có thể có của một action type
- Tùy chỉnh màu sắc cho từng trạng thái
- Sắp xếp vị trí hiển thị
- Thêm/sửa/xóa action states
- Liên kết states với action types

### Topic Master
- Quản lý topic master
- Theo dõi tiến độ của topics
- Xem lịch sử thay đổi
- Quản lý topic targets

### Online Tracking
- Theo dõi trạng thái online của nhân viên trong topics
- Tự động cập nhật trạng thái
- API để tích hợp với các hệ thống khác

### Dashboard
- Xem thống kê tổng quan về topics
- Theo dõi topics theo trạng thái
- Bộ lọc nâng cao
- Xuất dữ liệu

### N8N Integration
- Tích hợp tự động hóa workflow với N8N
- Cấu hình host và API key N8N
- Theo dõi thực thi workflow
- Xem lịch sử tự động hóa
- Liên kết đến N8N workflow và executions

### Notifications
- Thông báo thời gian thực qua Pusher
- Cập nhật trạng thái topic tự động
- Hiển thị thông báo cho người dùng liên quan
- Theo dõi trạng thái kết nối

### Processed Data
- Xem dữ liệu đã xử lý trong modal
- Hiển thị nội dung gốc và Google content
- Tối ưu hiển thị với iframe
- Kiểm tra tính hợp lệ của dữ liệu

## Settings
Cài đặt mới:
- N8N Host URL
- N8N API Key
- Thời gian timeout cho online tracking
- Cấu hình Pusher cho notifications
- Tùy chọn hiển thị processed data

## API Integration

### Authentication
Tất cả các API endpoints đều yêu cầu authentication thông qua:
- API key trong header: `X-API-KEY: your_api_key`

### Topics API Endpoints

#### Get All Topics
```
GET /admin/topics/topics_api/index
Header: X-API-KEY: {your_api_key}
```

#### Get Topic Detail
```
GET /admin/topics/topics_api/get/{topic_id}
Header: X-API-KEY: {your_api_key}
```

#### Create Topic
```
POST /admin/topics/topics_api/create
Header: 
  - X-API-KEY: {your_api_key}
  - Content-Type: application/x-www-form-urlencoded
Body:
  - topictitle: string
  - action_type: integer
  - action_state: integer  
  - log: string
```

#### Update Topic
```
POST /admin/topics/topics_api/update/{topic_id}
Header:
  - X-API-KEY: {your_api_key}
  - Content-Type: application/x-www-form-urlencoded
Body:
  - topictitle: string
  - action_type: integer
  - action_state: integer
  - log: string
```

#### Delete Topic
```
POST /admin/topics/topics_api/delete/{topic_id}
Header: X-API-KEY: {your_api_key}
```

#### Process Data
```
POST /admin/topics/topics_api/process_data
Header: X-API-KEY: {your_api_key}
```

#### Get Processed Data
```
GET /admin/topics/topics_api/get_processed_data/{topic_id}
Header: X-API-KEY: {your_api_key}
```

#### Update Online Status
```
POST /admin/topics/topics_api/update_online_status
Header: X-API-KEY: {your_api_key}
```

### Response Format
```json
{
    "success": true/false,
    "message": "Message description",
    "data": {}
}
```

### Variables
- base_url: Your domain URL (e.g. https://your-domain.com)
- topic_id: ID of the topic
- authtoken: Your API authentication token

## Error Handling
- Logging chi tiết cho bulk actions
- Xử lý lỗi JSON parsing
- Thông báo lỗi cụ thể cho người dùng
- Kiểm tra tính hợp lệ của dữ liệu
- Xử lý lỗi database transaction

## Contributing
Vui lòng đọc [CONTRIBUTING.md](CONTRIBUTING.md) để biết thêm chi tiết về quy trình đóng góp code.

## Version History
- 1.1.0 - Thêm tính năng N8N, notifications và processed data
- 1.0.5 - Thêm tính năng copy ID và cải thiện UI
- 1.0.4 - Latest stable version
- 1.0.3 - Bug fixes and improvements
- 1.0.0 - Initial release

## Authors
- FHC

## License
This project is licensed under the MIT License - see the [LICENSE.md](LICENSE.md) file for details

----
Bạn sẽ viết Module cho Perfex CRM nên hãy luôn luôn tuân thủ các nguyên tắc sau:

1. Coding Standards:
- Tuân thủ chặt chẽ coding standards của Perfex CRM và CodeIgniter 3
- Sử dụng các helper và library có sẵn của Perfex CRM
- Đặt tên biến, hàm theo chuẩn camelCase cho methods và snake_case cho biến
- Luôn comment code rõ ràng bằng tiếng Anh

2. Bảo toàn code:
- KHÔNG bao giờ xóa hoặc thay đổi code core của Perfex CRM
- Khi cần sửa đổi, ưu tiên extend/override thay vì modify trực tiếp
- Luôn backup file trước khi thực hiện thay đổi
- Kiểm tra kỹ các dependencies trước khi thêm/sửa code

3. Cấu trúc Module:
- Tuân thủ cấu trúc thư mục chuẩn của Perfex Module
- Sử dụng hooks và filters đúng cách
- Tách biệt rõ ràng logic business và presentation
- Đảm bảo tương thích với multiple versions của Perfex

4. Quy trình làm việc:
- Luôn hỏi và xác nhận vị trí file cần cập nhật
- Thông báo rõ các thay đổi sẽ thực hiện
- Cung cấp hướng dẫn test sau mỗi thay đổi
- Đề xuất phương án backup/rollback nếu cần

5. Security:
- Validate và sanitize tất cả input
- Kiểm tra permissions và role access
- Sử dụng prepared statements cho queries
- Tránh SQL injection và XSS

6. Performance:
- Tối ưu queries database
- Cache data khi cần thiết
- Minimize số lượng requests
- Tối ưu load time

7. Khi cập nhật code:
- Chỉ hiển thị phần code được thay đổi với comment rõ ràng (Không cố gắng viết lại Comment còn nếu thay đổi thì không để lỗi utf-8)
- Giữ nguyên các phần code không liên quan
- Đánh dấu rõ vị trí thay đổi bằng comments
- Cung cấp giải thích cho mỗi thay đổi