# Kế Hoạch Tổng Thể Phát Triển Ultimate Editor

## Danh Sách Task Cần Thực Hiện

- [x] **1. Kiến Trúc Tổng Thể và Phân Tích Yêu Cầu**
  - [x] 1.1. Mục Tiêu và Phạm Vi (@topicControllerxDraftWrtingxUltimateEditor.md, dòng 1-30)
  - [x] 1.2. Phân Tích Thành Phần (@topicControllerxDraftWrtingxUltimateEditorEnhanced.md, dòng 1-50)
  - [x] 1.3. Yêu Cầu Kỹ Thuật (@topicControllerxDraftWrtingxUltimateEditor.md, dòng 30-60)
  - [x] 1.4. Phân Tích Hiện Trạng Module
    - [x] 1.4.1. Phân tích chi tiết controllers hiện có (Topics.php, Writing.php)
    - [x] 1.4.2. Phân tích chi tiết models hiện có (Topic_controller_model.php, Topic_sync_log_model.php)
    - [x] 1.4.3. Phân tích các action processors hiện có (DraftWritingProcessor, TopicComposerProcessor)
    - [x] 1.4.4. Xác định điểm tích hợp tối ưu với cấu trúc hiện tại

- [x] **2. Cấu Trúc Hệ Thống**
  - [x] 2.1. Cấu Trúc Thư Mục (@topicControllerxDraftWrtingxUltimateEditor.md, dòng 41-80)
  - [x] 2.2. Thành Phần Chính (@topicControllerxDraftWrtingxUltimateEditorEnhanced.md, dòng 50-100)
  - [x] 2.3. Luồng Dữ Liệu (@topicControllerxDraftWrtingxUltimateEditorEnhanced.md, dòng 100-150)

- [x] **3. Tích Hợp Các Thành Phần**
  - [x] 3.1. Tích Hợp Topic Composer (@topicControllerxDraftWrtingxUltimateEditorEnhanced.md, dòng 150-200)
  - [x] 3.2. Tích Hợp Draft Writing (@topicControllerxDraftWrtingxUltimateEditorEnhanced.md, dòng 200-250)
  - [x] 3.3. Tích Hợp Topic Controller (@topicControllerxDraftWrtingxUltimateEditorEnhanced.md, dòng 250-300)
  - [x] 3.4. Giao Diện Tích Hợp (@topicControllerxDraftWrtingxUltimateEditor.md, dòng 80-120)
  - [x] 3.5. Cập Nhật Cơ Sở Dữ Liệu
    - [x] 3.5.1. Xác định nhu cầu bổ sung/chỉnh sửa cấu trúc bảng
      - Phân tích các yêu cầu lưu trữ dữ liệu cho Draft và các tính năng nâng cao
      - Xác định mối quan hệ với bảng tbltopics và tbltopic_sync_logs
      - Thiết kế schema cho các bảng mới (topic_editor_drafts)
    - [x] 3.5.2. Thiết kế migration scripts
      - Tạo migration cho version 1.2.7 (127_version_127.php) cho cấu trúc Draft
      - Tạo migration cho version 1.2.8 (128_version_128.php) cho SEO và Metadata
      - Tạo migration cho version 1.2.9 (129_version_129.php) cho tích hợp thông báo
      - Chuẩn bị migration cho version 1.3.0 (130_version_130.php) cho full Ultimate Editor
    - [x] 3.5.3. Bảo đảm tương thích ngược với dữ liệu hiện có
      - Viết logic chuyển đổi dữ liệu từ cấu trúc cũ sang mới
      - Cung cấp phương thức rollback an toàn
      - Kiểm thử toàn diện trước khi triển khai

- [x] **4. Cơ Chế SyncLogMethod và Resume**
  - [x] 4.1. Thiết Kế Cơ Chế SyncLogMethod (@topicControllerxDraftWrtingxUltimateEditorEnhanced.md, dòng 50-70)
  - [x] 4.2. Triển Khai Resume Mechanism (@topicControllerxDraftWrtingxUltimateEditorEnhanced.md, dòng 70-100)
  - [x] 4.3. Tích Hợp Vào Quy Trình Xuất Bản (@topicControllerxDraftWrtingxUltimateEditorEnhanced.md, dòng 300-350)

- [x] **5. Phát Triển Section Editor**
  - [x] 5.1. Thiết Kế Giao Diện
  - [x] 5.2. Xử Lý Sections
  - [x] 5.3. Tích Hợp Với Content Editor
  - [x] 5.4. Quản Lý Trạng Thái

- [x] **6. SEO và Quản Lý Tags**
  - [x] 6.1. Thiết Kế Tab SEO (@topicControllerxDraftWrtingxUltimateEditor.md, dòng 120-160)
  - [x] 6.2. Quản Lý Tags
  - [x] 6.3. Phân Tích SEO (@topicControllerxDraftWrtingxUltimateEditor.md, dòng 160-200)
  - [x] 6.4. Đề Xuất Cải Thiện (@topicControllerxDraftWrtingxUltimateEditor.md, dòng 200-240)

- [ ] **7. Tích Hợp AI**
  - [ ] 7.1. API Integration (@topicControllerxDraftWrtingxUltimateEditor.md, dòng 240-280)
  - [ ] 7.2. Xử Lý Prompt (@topicControllerxDraftWrtingxUltimateEditor.md, dòng 280-320)
  - [ ] 7.3. AI Content Enhancement (@topicControllerxDraftWrtingxUltimateEditor.md, dòng 320-360)
  - [ ] 7.4. Tối Ưu Hóa SEO Với AI (@topicControllerxDraftWrtingxUltimateEditor.md, dòng 360-400)

- [x] **8. Quy Trình Xuất Bản**
  - [x] 8.1. Thiết Kế Tab Xuất Bản (@topicControllerxDraftWrtingxUltimateEditorEnhanced.md, dòng 350-400)
  - [x] 8.2. Tích Hợp Với Platform Connectors (@topicControllerxDraftWrtingxUltimateEditorEnhanced.md, dòng 400-450)
  - [x] 8.3. Ghi Log Xuất Bản (@topicControllerxDraftWrtingxUltimateEditorEnhanced.md, dòng 450-500)
  - [x] 8.4. Hiển Thị Tiến Trình (@topicControllerxDraftWrtingxUltimateEditorEnhanced.md, dòng 500-550)

- [x] **9. Phát Triển Backend**
  - [x] 9.1. Controller (@topicControllerxDraftWrtingxUltimateEditor.md, dòng 400-450)
  - [x] 9.2. Model (@topicControllerxDraftWrtingxUltimateEditor.md, dòng 450-500)
  - [x] 9.3. Xử Lý Dữ Liệu (@topicControllerxDraftWrtingxUltimateEditor.md, dòng 500-550)
  - [x] 9.4. API Endpoints (@topicControllerxDraftWrtingxUltimateEditor.md, dòng 550-600)

- [x] **10. Phát Triển Frontend**
  - [x] 10.1. HTML/CSS (@topicControllerxDraftWrtingxUltimateEditor.md, dòng 600-650)
  - [x] 10.2. JavaScript Chính (@topicControllerxDraftWrtingxUltimateEditor.md, dòng 650-700)
  - [x] 10.3. JavaScript Modules
  - [x] 10.4. Responsive Design (@topicControllerxDraftWrtingxUltimateEditor.md, dòng 700-750)
  - [x] 10.5. Tích Hợp API
    - [x] 10.5.1. Mở rộng API endpoints hiện có
    - [x] 10.5.2. Đảm bảo tương thích với formats response hiện tại
    - [x] 10.5.3. Cập nhật API documentation

- [x] **11. Quản Lý Lịch Sử Xuất Bản**
  - [x] 11.1. Thiết Kế Tab Lịch Sử
  - [x] 11.2. Xem Chi Tiết Phiên
  - [x] 11.3. Tiếp Tục Phiên Gián Đoạn
  - [x] 11.4. Báo Cáo Xuất Bản

- [x] **12. Tự Động Lưu và Khôi Phục**
  - [x] 12.1. Cơ Chế Lưu Tự Động (@topicControllerxDraftWrtingxUltimateEditor.md, dòng 750-800)
  - [x] 12.2. Khôi Phục Phiên (@topicControllerxDraftWrtingxUltimateEditor.md, dòng 800-850)
  - [x] 12.3. Xử Lý Xung Đột (@topicControllerxDraftWrtingxUltimateEditor.md, dòng 850-900)

- [ ] **13. Mở Rộng và Bảo Trì**
  - [ ] 13.1. Tính Năng Tùy Chỉnh (@topicControllerxDraftWrtingxUltimateEditorEnhanced.md, dòng 550-600)
  - [ ] 13.2. Mở Rộng Platform (@topicControllerxDraftWrtingxUltimateEditorEnhanced.md, dòng 600-650)
  - [ ] 13.3. Hướng Dẫn Phát Triển (@topicControllerxDraftWrtingxUltimateEditorEnhanced.md, dòng 650-700)

- [ ] **14. Kiểm Thử và Triển Khai**
  - [ ] 14.1. Kế Hoạch Kiểm Thử (@topicControllerxDraftWrtingxUltimateEditor.md, dòng 900-950)
  - [ ] 14.2. Quy Trình Triển Khai (@topicControllerxDraftWrtingxUltimateEditor.md, dòng 950-1000)
  - [ ] 14.3. Backup và Rollback (@topicControllerxDraftWrtingxUltimateEditor.md, dòng 1000-1050)

## Ưu Tiên và Phụ Thuộc

### Các Task Ưu Tiên Cao (P0)
- **1.1, 1.2, 1.3, 1.4**: Phân tích yêu cầu, hiện trạng và thiết kế tổng thể (Tiên quyết cho tất cả các task khác)
- **2.1, 2.2**: Cấu trúc thư mục và thành phần chính (Nền tảng cho phát triển)
- **4.1, 4.2**: Cơ chế SyncLogMethod và Resume (Quan trọng cho tính liên tục)

### Các Task Ưu Tiên Trung Bình (P1)
- **3.1, 3.2, 3.3, 3.5**: Tích hợp các thành phần và cập nhật cơ sở dữ liệu (Phụ thuộc vào 1.x, 2.x)
- **5.1, 5.2**: Phát triển Section Editor
- **9.1, 9.2**: Phát triển Backend (Phụ thuộc vào 2.x, 3.x)
- **10.1, 10.2, 10.5**: Phát triển Frontend cơ bản và tích hợp API (Phụ thuộc vào 9.x)

### Các Task Ưu Tiên Thấp (P2)
- **7.x**: SEO và Quản lý Tags (Phụ thuộc vào 3.x, 6.x)
- **8.x**: Tích hợp AI (Phụ thuộc vào 6.x, 10.x)
- **9.x**: Quy trình xuất bản (Phụ thuộc vào 3.x, 4.x)
- **11.x, 12.x, 13.x, 14.x**: Các tính năng mở rộng (Phụ thuộc vào các task cơ bản)

### Biểu Đồ Phụ Thuộc Chính
```
1.x → 2.x → 3.x → [6.x, 8.x, 9.x]
1.4.x → [2.x, 3.x, 4.x]  // Phân tích hiện trạng là tiên quyết cho các giai đoạn tiếp theo
2.x → 4.x → 8.x
2.x → 5.x → [6.x, 7.x]
3.5.x → 9.x  // Database updates ảnh hưởng tới backend
9.x → 10.x → [11.x, 12.x]
10.5.x → 13.x  // API integration là tiên quyết cho mở rộng
[6.x, 7.x, 8.x, 10.x] → 13.x → 14.x
```

## Chi Tiết Các Giai Đoạn Phát Triển

### Giai Đoạn 1: Phân Tích và Thiết Kế
- Phân tích yêu cầu và thành phần hiện có
- Thiết kế kiến trúc tổng thể
- Xác định luồng dữ liệu và tương tác giữa các thành phần

### Giai Đoạn 2: Phát Triển Cơ Sở
- Tạo cấu trúc thư mục và file cơ bản
- Phát triển các thành phần backend core
- Thiết lập giao diện người dùng cơ bản

### Giai Đoạn 3: Tích Hợp Thành Phần
- Tích hợp Topic Composer
- Tích hợp Draft Writing
- Tích hợp Topic Controller
- Phát triển cơ chế SyncLogMethod và Resume

### Giai Đoạn 4: Phát Triển Tính Năng Nâng Cao
- Phát triển Section Editor
- Tích hợp AI và SEO
- Phát triển quản lý lịch sử xuất bản

### Giai Đoạn 5: Hoàn Thiện và Kiểm Thử
- Hoàn thiện giao diện người dùng
- Kiểm thử đầy đủ các tính năng
- Sửa lỗi và tối ưu hóa
- Triển khai phiên bản cuối cùng

## Quy Tắc Coding

Tuân thủ các quy tắc từ @projectImportantNotes.md:

1. **Quản Lý Mã Nguồn**: Tổ chức file logic và có cấu trúc rõ ràng
2. **Tìm Kiếm Trước Khi Tạo**: Kiểm tra sự tồn tại của file/thư mục trước khi tạo mới
3. **Không Xóa File**: Tránh xóa file, thay vào đó là cập nhật hoặc tạo bản sao
4. **Đường Dẫn Module**: Sử dụng `module_dir_path()` và `module_dir_url()` cho đường dẫn
5. **Error Handling**: Đảm bảo xử lý lỗi đầy đủ và thông báo rõ ràng
6. **Responsive Design**: Đảm bảo giao diện hoạt động tốt trên mọi thiết bị
7. **AI Integration**: Tối ưu việc sử dụng API AI để cải thiện nội dung
8. **Bảo Mật**: Luôn kiểm tra và làm sạch dữ liệu đầu vào

## Quy Trình Migration Database

### Tuân Thủ Version Và Đặt Tên File

1. **Quy Tắc Đặt Tên File**:
   - Format: `XXX_version_XXX.php` trong thư mục `/migrations/`
   - XXX là số version 3 chữ số, bắt đầu từ file hiện có (127 sẽ là số tiếp theo sau 126)
   - Ví dụ: `127_version_127.php`, `128_version_128.php`, v.v.

2. **Quy Tắc Version Trong topics.php**:
   - Version hiện tại: 1.2.6 (từ file topics.php)
   - Tăng số thứ ba cho minor updates (1.2.6 → 1.2.7, 1.2.8, v.v.)
   - Tăng số thứ hai cho major features (1.2.9 → 1.3.0)
   - Luôn cập nhật version trong cả file topics.php và trong migration

### Cấu Trúc Migration Chuẩn

```php
<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_127 extends App_module_migration
{
    public function up()
    {
        $CI = &get_instance();
        
        // Logic migration ở đây
        
        update_option('topics_version', '1.2.7');
    }

    public function down()
    {
        $CI = &get_instance();
        
        // Logic rollback ở đây
    }
}
```

### Kế Hoạch Migration Cho Ultimate Editor

1. **Migration 127 (v1.2.7) - Draft Editor**:
   ```php
   // Tạo bảng tbltopic_editor_drafts
   if (!$CI->db->table_exists(db_prefix() . 'topic_editor_drafts')) {
       $CI->db->query("CREATE TABLE `" . db_prefix() . "topic_editor_drafts` (
           `id` int(11) NOT NULL AUTO_INCREMENT,
           `topic_id` int(11) NOT NULL,
           `draft_title` varchar(191) NOT NULL,
           `draft_content` longtext NOT NULL,
           `status` varchar(50) NOT NULL DEFAULT 'draft',
           `created_by` int(11) DEFAULT NULL,
           `last_edited_by` int(11) DEFAULT NULL,
           `created_at` datetime DEFAULT current_timestamp(),
           `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
           PRIMARY KEY (`id`),
           KEY `topic_id` (`topic_id`),
           KEY `status` (`status`),
           CONSTRAINT `fk_topic_editor_drafts_topic` FOREIGN KEY (`topic_id`) REFERENCES `" . db_prefix() . "topics` (`id`) ON DELETE CASCADE
       ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
       
       log_activity('Migration 127: Created topic_editor_drafts table');
   }
   ```

2. **Migration 128 (v1.2.8) - SEO Metadata**:
   - Tạo bảng `tbltopic_editor_metadata`
   - Thêm cột `seo_status` vào bảng `tbltopics`

3. **Migration 129 (v1.2.9) - Notification Integration**:
   - Thêm các trường thông báo mới
   - Cập nhật cấu hình Pusher nếu cần

4. **Migration 130 (v1.3.0) - Full Ultimate Editor Release**:
   - Cập nhật version lên major version (1.3.0)
   - Tạo bảng thống kê và hiệu suất nếu cần

### Thực Hành Tốt Nhất

1. **Kiểm Tra Tồn Tại Trước Khi Tạo**:
   ```php
   if (!$CI->db->table_exists(db_prefix() . 'table_name')) {
       // Tạo bảng
   }
   ```

2. **Kiểm Tra Cột Trước Khi Thêm**:
   ```php
   $fields = $CI->db->field_data(db_prefix() . 'table_name');
   $has_column = false;
   
   foreach ($fields as $field) {
       if ($field->name == 'column_name') {
           $has_column = true;
           break;
       }
   }
   
   if (!$has_column) {
       // Thêm cột
   }
   ```

3. **Ghi Log Mỗi Thay Đổi**:
   ```php
   log_activity('Migration XXX: Action description');
   ```

4. **Cập Nhật Version**:
   ```php
   update_option('topics_version', '1.2.7');
   ```

5. **Sử Dụng Transaction**:
   ```php
   $CI->db->trans_begin();
   
   // Thực hiện các thay đổi
   
   if ($CI->db->trans_status() === FALSE) {
       $CI->db->trans_rollback();
       log_activity('Migration XXX: Failed - transaction rolled back');
   } else {
       $CI->db->trans_commit();
       log_activity('Migration XXX: Successful');
   }
   ```

### Quy Trình Triển Khai Migration

1. **Tạo File Migration**:
   - Đặt tên theo quy ước (XXX_version_XXX.php)
   - Sử dụng template chuẩn

2. **Viết Code Migration**:
   - Triển khai phương thức `up()` với logic tạo bảng/thêm cột
   - Triển khai phương thức `down()` với logic rollback
   - Sử dụng foreign keys cho tính toàn vẹn dữ liệu

3. **Cập Nhật Version Module**:
   - Cập nhật trong file migration (`update_option()`)
   - Cập nhật trong file topics.php (define module version)

4. **Kiểm Thử Migration**:
   - Chạy migration trên môi trường test
   - Kiểm tra tính toàn vẹn dữ liệu
   - Xác nhận cấu trúc bảng đã đúng

5. **Cập Nhật Documentation**:
   - Cập nhật README.md với các thay đổi cơ sở dữ liệu
   - Ghi chú vào CHANGELOG.md
   - Cập nhật mô tả database trong tài liệu

6. **Triển Khai Production**:
   - Sao lưu database trước khi thực hiện
   - Chạy migration
   - Xác nhận không có lỗi phát sinh

## Hướng Dẫn Cho AI Agent

### Nguyên Tắc Thiết Kế Liền Mạch

1. **Ưu Tiên Đọc Trước Khi Viết**
   - Đọc toàn bộ tệp đích trước khi bắt đầu chỉnh sửa
   - Hiểu rõ cấu trúc và mục đích của tệp hiện tại
   - Xác định vị trí chính xác để thêm/sửa mã

2. **Xây Dựng Bản Đồ Phụ Thuộc**
   - Tạo và duy trì bản đồ phụ thuộc giữa các tệp
   - Ghi chú tất cả các import/require/include giữa các tệp
   - Kiểm tra ảnh hưởng lan tỏa khi thay đổi một tệp

3. **Tổ Chức Mã Nguồn Hiệu Quả**
   - Chia tệp lớn thành nhiều module/class với chức năng rõ ràng
   - Mỗi module phải là một đơn vị chức năng hoàn chỉnh
   - Tạo chú thích rõ ràng về mục đích và phạm vi của mỗi module

### Kỹ Thuật Phòng Ngừa Lỗi

1. **Template Mã An Toàn**
   - Sử dụng template cho các cấu trúc mã phổ biến
   - Đảm bảo tất cả các template đều xử lý lỗi và kiểm tra biên
   - Kiểm tra template với dữ liệu đầu vào cực đại và cực tiểu

2. **Quy Tắc Cắt/Ghép Mã**
   - Không bao giờ cắt mã mà không hiểu toàn bộ ngữ cảnh
   - Luôn bao gồm cặp đóng/mở đầy đủ (ví dụ: {}, (), [])
   - Đánh dấu ranh giới cắt với comment định dạng chuẩn: `// --- CUT BEGIN: [description] ---` và `// --- CUT END: [description] ---`

3. **Quy Trình Phục Hồi Lỗi**
   - Lưu trữ phiên bản sao lưu trước mỗi lần chỉnh sửa lớn
   - Tạo các điểm khôi phục (savepoint) với tên mô tả
   - Xác định trước các quy trình rollback cho mỗi thay đổi

### Kỹ Thuật Giữ Trạng Thái Nhất Quán

1. **Model Ngữ Cảnh**
   - Duy trì một mô hình ngữ cảnh (context model) cho dự án
   - Cập nhật mô hình này khi đọc hoặc sửa đổi mã
   - Sử dụng mô hình để kiểm tra tính nhất quán giữa các tệp

2. **Kiểm Soát API Nội Bộ**
   - Định nghĩa rõ ràng API giữa các module
   - Ghi chú tất cả các thay đổi API và cập nhật tất cả người gọi
   - Kiểm tra tính tương thích của API khi sửa đổi module

3. **Quản Lý Đồng Bộ Hóa Dữ Liệu**
   - Xác định nguồn dữ liệu chính (source of truth) cho mỗi loại dữ liệu
   - Thiết kế quy trình đồng bộ hóa giữa các bộ phận
   - Ghi log tất cả các hoạt động đồng bộ hóa

### Các Mẫu Code An Toàn

1. **Mẫu Khởi Tạo Đối Tượng**
```javascript
// Mẫu khởi tạo đối tượng an toàn
function createObject(config = {}) {
    // Đảm bảo config là object
    if (typeof config !== 'object' || config === null) {
        config = {};
    }
    
    // Thiết lập các giá trị mặc định
    const defaultConfig = {
        property1: 'default1',
        property2: 'default2',
        // Các thuộc tính khác...
    };
    
    // Kết hợp config với giá trị mặc định
    return { ...defaultConfig, ...config };
}
```

2. **Mẫu Xử Lý Dữ Liệu Bất Đồng Bộ**
```javascript
// Mẫu xử lý dữ liệu bất đồng bộ an toàn
async function fetchAndProcessData(url, options = {}) {
    try {
        // Bắt đầu với trạng thái tải
        showLoading(true);
        
        // Thực hiện request
        const response = await fetch(url, options);
        
        // Kiểm tra response
        if (!response.ok) {
            throw new Error(`HTTP error ${response.status}: ${response.statusText}`);
        }
        
        // Xử lý dữ liệu
        const data = await response.json();
        return processData(data);
    } catch (error) {
        // Xử lý lỗi
        console.error('Data fetching error:', error);
        showError(error.message);
        return null;
    } finally {
        // Dọn dẹp
        showLoading(false);
    }
}
```

3. **Mẫu Chunking Nội Dung**
```javascript
// Mẫu chunking nội dung an toàn
function chunkContent(content, maxChunkSize = 5000) {
    // Nếu nội dung nhỏ hơn kích thước chunk, trả về nguyên vẹn
    if (!content || content.length <= maxChunkSize) {
        return [content];
    }
    
    const chunks = [];
    let startPos = 0;
    
    while (startPos < content.length) {
        // Tìm điểm kết thúc phù hợp
        let endPos = Math.min(startPos + maxChunkSize, content.length);
        
        // Nếu không phải cuối cùng, tìm vị trí kết thúc phù hợp (kết thúc câu/đoạn)
        if (endPos < content.length) {
            // Tìm dấu kết thúc câu gần nhất
            const periodPos = content.lastIndexOf('.', endPos);
            const lineBreakPos = content.lastIndexOf('\n', endPos);
            
            // Chọn vị trí kết thúc phù hợp nhất
            if (periodPos > startPos && periodPos > lineBreakPos) {
                endPos = periodPos + 1; // +1 để bao gồm dấu chấm
            } else if (lineBreakPos > startPos) {
                endPos = lineBreakPos + 1; // +1 để bao gồm dấu xuống dòng
            }
        }
        
        // Thêm chunk vào danh sách
        chunks.push(content.substring(startPos, endPos));
        startPos = endPos;
    }
    
    return chunks;
}
```

3. **Mẫu Xử Lý Nội Dung Lớn**
```javascript
// Mẫu xử lý nội dung lớn an toàn
function processLargeContent(content) {
    // Kiểm tra nội dung đầu vào
    if (!content) {
        return '';
    }
    
    // Xử lý nội dung theo từng đoạn
    const paragraphs = content.split('\n\n');
    let processedContent = '';
    
    for (const paragraph of paragraphs) {
        // Xử lý từng đoạn
        const processed = processParagraph(paragraph);
        processedContent += processed + '\n\n';
    }
    
    return processedContent.trim();
}

function processParagraph(paragraph) {
    // Xử lý từng đoạn văn
    // Thêm định dạng, liên kết, v.v.
    return paragraph;
}
```

### Danh Sách Kiểm Tra Trước Khi Thực Hiện

1. **Trước Khi Đọc File**
   - [ ] Xác định chính xác đường dẫn file
   - [ ] Kiểm tra sự tồn tại của file
   - [ ] Xác định định dạng và encoding của file

2. **Trước Khi Sửa File**
   - [ ] Đọc toàn bộ nội dung file hiện tại
   - [ ] Xác định các phần phụ thuộc trong file
   - [ ] Tạo bản sao lưu của file
   - [ ] Kiểm tra tác động của thay đổi đến các file khác

3. **Trước Khi Tạo File Mới**
   - [ ] Kiểm tra file đã tồn tại chưa
   - [ ] Xác định đúng cấu trúc thư mục
   - [ ] Đảm bảo tuân thủ quy ước đặt tên
   - [ ] Chuẩn bị template với header và khai báo đầy đủ

6. **Phòng Ngừa Sự Cố Tương Lai**
   - **Tạo điểm khôi phục** tại các cột mốc quan trọng trong quá trình phát triển
   - **Duy trì bản sao lưu** của tất cả các tệp quan trọng trước khi sửa đổi
   - **Tăng cường ghi chú** và tài liệu cho các phần phức tạp
   - **Áp dụng mẫu thiết kế chống sự cố**, ví dụ như Circuit Breaker, Bulkhead, và Timeout
   - **Cập nhật thường xuyên các file trạng thái** để giảm thiểu mất dữ liệu khi sự cố xảy ra