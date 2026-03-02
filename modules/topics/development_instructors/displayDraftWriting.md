# Draft Writing Feature Implementation

## Overview
Chức năng Draft Writing sẽ hiển thị một trình soạn thảo nâng cao với bố cục 2 cột, cho phép người dùng soạn thảo bài viết với các công cụ AI hỗ trợ và phân tích nội dung theo thời gian thực.

## Layout Structure
- **Bố cục 2 cột**: Cột trái (2/3 màn hình) và cột phải (1/3 màn hình)
- **Modal toàn màn hình**: Sử dụng modal fullscreen để tối đa hóa không gian làm việc

## Cột Trái (2/3 màn hình) - Editor Section
1. **Tiêu đề**:
   - Input field với nút Quick Save
   - Nút AI Edit để tối ưu tiêu đề

2. **Description/Meta Description**:
   - Textarea với nút Quick Save
   - Nút AI Edit để tối ưu description

3. **Content Editor**:
   - TinyMCE editor với đầy đủ chức năng
   - Tự động format nội dung từ các item thành bài viết hoàn chỉnh
   - Thanh công cụ tương tự WordPress editor
   - Hỗ trợ chèn hình ảnh, định dạng văn bản, tạo liên kết

4. **Thanh công cụ AI**:
   - Nút AI Edit cho toàn bộ nội dung
   - Nút AI Search để tìm kiếm thông tin liên quan
   - Nút AI Improve để cải thiện đoạn văn được chọn

## Cột Phải (1/3 màn hình) - Analysis Section
1. **Outline Bài Viết**:
   - Tự động tạo outline dựa trên các heading trong nội dung
   - Cập nhật theo thời gian thực khi người dùng thay đổi nội dung
   - Có thể click vào outline để di chuyển đến vị trí tương ứng trong editor

2. **Phân Tích Nội Dung**:
   - Số lượng từ, số đoạn văn, thời gian đọc ước tính
   - Phân tích mật độ từ khóa
   - Keyword cloud hiển thị trực quan các từ khóa chính

3. **Công Cụ Phân Tích Đoạn Văn**:
   - Khi người dùng tô đậm một đoạn văn bản, hiển thị bảng công cụ với:
     - Nút "Rewrite" để mở popup AI selector (tương tự Topic Composer)
     - Nút "Improve" để cải thiện đoạn văn
     - Nút "Fact Check" để kiểm tra thông tin
     - Nút "Expand" để mở rộng nội dung đoạn văn

4. **SEO Suggestions**:
   - Đề xuất cải thiện SEO theo thời gian thực
   - Đánh giá độ dài tiêu đề, meta description
   - Kiểm tra cấu trúc heading (H1, H2, H3)

## Chức Năng Lưu và Xuất Bản
1. **Auto Save**:
   - Tự động lưu nội dung theo định kỳ
   - Hiển thị thời gian lưu gần nhất

2. **Nút Lưu Nháp**:
   - Lưu nội dung hiện tại vào cơ sở dữ liệu
   - Hiển thị thông báo thành công/thất bại

3. **Nút Xuất Bản**:
   - Chuyển bài viết sang trạng thái xuất bản
   - Tùy chọn xuất bản ngay hoặc hẹn giờ

## Tích Hợp AI
1. **AI Edit**:
   - Sử dụng modal prompt selection tương tự Topic Composer
   - Hỗ trợ nhiều style viết khác nhau

2. **AI Search**:
   - Tìm kiếm thông tin liên quan đến nội dung
   - Hiển thị kết quả trong modal có thể chèn vào bài viết

3. **AI Improve**:
   - Cải thiện đoạn văn được chọn
   - Đề xuất các cách viết thay thế

## Xử Lý Dữ Liệu
1. **Step 1**: 
   - Nhận dữ liệu từ workflow
   - Hiển thị loading và polling
   - Khi hoàn tất, hiển thị editor với nội dung đã được format

2. **Step 2**:
   - Gửi dữ liệu đã chỉnh sửa lên server
   - Xử lý response và hiển thị thông báo thành công/thất bại

## Responsive Design
- Thiết kế responsive cho các kích thước màn hình
- Trên màn hình nhỏ, chuyển sang bố cục 1 cột với tab để chuyển đổi giữa editor và analysis

## Codebase Implementation
### Files cần cập nhật:

1. **Core Files**:
   - `views/includes/topic_detail_action_buttons_display_script_displayDraftWritingResult.php`: Cập nhật hàm chính xử lý hiển thị Draft Writer
   - `views/includes/topic_detail_action_buttons.php`: Đảm bảo action command "WRITE_DRAFT" được xử lý đúng
   - `assets/js/writing.js`: Thêm các hàm helper cho Draft Writer
   - Các bản draft sẽ sử dụng Local Storage để lưu nội dung và quản lý phiên bản
   

2. **CSS Files**:
   - `assets/css/topics.css`: Thêm styles cho Draft Writer UI
   - `assets/css/draft_writer.css`: Tạo file CSS riêng cho Draft Writer

3. **New Files**:
   - `views/includes/draftWriter/draft_writer_modal.php`: Template cho modal Draft Writer
   - `views/includes/draftWriter/draft_writer_analysis_panel.php`: Template cho phần phân tích
   - `assets/js/draft_writer.js`: JavaScript riêng cho Draft Writer, xử lý tất cả tương tác người dùng và quản lý local storage
   - `controllers/Writing.php`: Controller mới để xử lý các logic liên quan đến Draft Writer

4. **Dependencies**:
   - Tox Editor theo chuẩn Perfex CRM

   Thêm các thư viện hoặc js/css trên cdn vào views/detail.php
   - jQCloud (cho keyword cloud) 
   - Chart.js (cho biểu đồ phân tích)

5. **Language Files**:
   - `language/vietnamese/topics_lang.php`: Thêm các chuỗi dịch cho Draft Writer
   - `language/english/topics_lang.php`: Thêm các chuỗi dịch tiếng Anh

6. **Backend Support**:
   - `controllers/writing.php`

7. **Integration Points**:
   - `helpers/topic_action_processor_DraftWritingProcessor_helper.php`:
     + Cập nhật xử lý workflow cho Draft Writing
     + Thêm hàm format nội dung cho Draft Writer

## Danh Sách Chi Tiết Các File Cần Tạo và Chỉnh Sửa

### File Mới Cần Tạo
1. **File 1**: `views/includes/draftWriter/draft_writer_modal.php` - Template chính cho modal Draft Writer, bao gồm cấu trúc 2 cột và các thành phần UI
2. **File 2**: `views/includes/draftWriter/draft_writer_analysis_panel.php` - Template cho phần phân tích nội dung ở cột phải
3. **File 3**: `views/includes/draftWriter/draft_writer_editor_panel.php` - Template cho phần soạn thảo ở cột trái
4. **File 4**: `views/includes/draftWriter/draft_writer_toolbar.php` - Template cho thanh công cụ của editor
5. **File 5**: `assets/js/draft_writer.js` - File JavaScript chính cho Draft Writer, xử lý tất cả tương tác người dùng và quản lý local storage
6. **File 6**: `assets/js/draft_writer_analysis.js` - File JavaScript xử lý phân tích nội dung theo thời gian thực
7. **File 7**: `assets/js/draft_writer_ai.js` - File JavaScript xử lý tích hợp AI và các chức năng liên quan
8. **File 8**: `assets/css/draft_writer.css` - File CSS chính cho Draft Writer (đã tạo)
9. **File 9**: `controllers/Writing.php` - Controller mới để xử lý các logic liên quan đến Draft Writer

### File Cần Chỉnh Sửa
10. **File 10**: `views/includes/topic_detail_action_buttons.php` - Thêm xử lý cho action "WRITE_DRAFT"
11. **File 11**: `views/includes/topic_detail_action_buttons_display_script_displayDraftWritingResult.php` - Cập nhật hàm hiển thị kết quả Draft Writing
12. **File 12**: `assets/js/writing.js` - Bổ sung các hàm helper cho Draft Writer
13. **File 13**: `views/detail.php` - Thêm các thư viện và dependencies cần thiết
14. **File 14**: `helpers/topic_action_processor_DraftWritingProcessor_helper.php` - Cập nhật xử lý workflow
15. **File 15**: `language/vietnamese/topics_lang.php` - Thêm các chuỗi dịch tiếng Việt
16. **File 16**: `language/english/topics_lang.php` - Thêm các chuỗi dịch tiếng Anh
17. **File 17**: `assets/css/topics.css` - Bổ sung styles cho Draft Writer UI

### Quản Lý Dữ Liệu Bằng Local Storage
- Tất cả các bản nháp sẽ được lưu trữ trong local storage của trình duyệt
- Cấu trúc dữ liệu trong local storage:
  ```javascript
  {
    "draft_[topic_id]": {
      "title": "Tiêu đề bài viết",
      "description": "Mô tả bài viết",
      "content": "Nội dung HTML của bài viết",
      "last_saved": "Timestamp",
      "version": "Phiên bản",
      "auto_save_enabled": true/false
    }
  }
  ```
- Các chức năng quản lý local storage sẽ được triển khai trong `assets/js/draft_writer.js`:
  - `saveDraftToLocalStorage(topic_id, data)` - Lưu bản nháp vào local storage
  - `getDraftFromLocalStorage(topic_id)` - Lấy bản nháp từ local storage
  - `clearDraftFromLocalStorage(topic_id)` - Xóa bản nháp khỏi local storage
  - `enableAutoSave(topic_id)` - Bật chế độ tự động lưu
  - `disableAutoSave(topic_id)` - Tắt chế độ tự động lưu

### API Endpoints trong Writing Controller
18. **File 18**: Tạo mới `controllers/Writing.php` với các endpoints:
    - `index` - Hiển thị trang chính của Draft Writer
    - `publish_draft` - Xuất bản bài viết từ bản nháp
    - `ai_improve_content` - Cải thiện nội dung bằng AI
    - `ai_fact_check` - Kiểm tra thông tin bằng AI
    - `ai_search` - Tìm kiếm thông tin liên quan
    - `get_keyword_analysis` - Phân tích từ khóa trong nội dung
    - `get_seo_suggestions` - Đề xuất cải thiện SEO


<!-- Cấu trúc JSON trả về ở Step 1 khi thực hiện:
 {
  "success": "Trạng thái thực hiện workflow, giá trị true nếu thành công, false nếu thất bại.",
  "message": "Thông báo kết quả thực hiện workflow.",
  "data": {
    "response": {
      "success": "Trạng thái thực hiện yêu cầu trong response, true nếu thành công.",
      "data": [
        {
          "row_number": "Số thứ tự của bản ghi.",
          "web-scraper-order": "Mã định danh đơn hàng của web scraper.",
          "web-scraper-start-url": "URL bắt đầu quá trình scraping.",
          "Paging": "URL phân trang tiếp theo.",
          "Topic": "Chủ đề của danh sách hiển thị trên trang web.",
          "Topic-href": "Đường dẫn đến trang chi tiết của chủ đề.",
          "Title": "Tiêu đề bài viết hoặc danh sách trên trang web.",
          "Summary": "Tóm tắt nội dung chính của bài viết hoặc danh sách.",
          "Item_Position": "Vị trí của mục trong danh sách (nếu có).",
          "Item_Title": "Tiêu đề chi tiết của mục trong danh sách.",
          "Item_Content": "Nội dung chi tiết của mục, có thể chứa HTML đã được mã hóa.",
          "item_Pictures": "Danh sách các hình ảnh đính kèm trong mục dưới dạng mảng JSON.",
          "item_Pictures-src": "Nguồn (URL) của hình ảnh chính trong mục.",
          "item_Pictures_Full": "Danh sách đầy đủ các hình ảnh kèm link nguồn lớn (large_src).",
          "item_Pictures_Full-large_src": "Nguồn (URL) của hình ảnh đầy đủ kích thước.",
          "Topic_footer": "Phần ghi chú hoặc nội dung ở cuối bài viết hoặc danh sách.",
          "TopicKeywords": "Các từ khóa liên quan đến chủ đề."
        }
      ]
    },
    "http_code": "Mã HTTP trả về từ server, ví dụ 200 cho thành công.",
    "clear_button": "Trạng thái nút xóa (nếu có trong giao diện người dùng).",
    "audit_step": "Bước kiểm tra trong workflow, thể hiện quy trình thực hiện.",
    "needs_polling": "Xác định xem có cần đợi (polling) để nhận kết quả tiếp theo hay không.",
    "workflow_id": "Mã định danh của workflow đang thực hiện.",
    "execution_id": "Mã định danh của phiên thực hiện workflow (có thể null nếu chưa có).",
    "response_text": "Chuỗi JSON chứa thông tin chi tiết của response."
  }
}

-->

### Cấu Trúc Controller Writing.php
```php
<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Writing extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('topics_model');
    }

    /**
     * Index method - hiển thị Draft Writer
     */
    public function index($topic_id = null)
    {
        // Logic hiển thị Draft Writer
    }

    /**
     * Publish draft - xuất bản bài viết từ bản nháp
     */
    public function publish_draft()
    {
        // Logic xuất bản bài viết
    }

    /**
     * AI improve content - cải thiện nội dung bằng AI
     */
    public function ai_improve_content()
    {
        // Logic cải thiện nội dung
    }

    /**
     * AI fact check - kiểm tra thông tin bằng AI
     */
    public function ai_fact_check()
    {
        // Logic kiểm tra thông tin
    }

    /**
     * AI search - tìm kiếm thông tin liên quan
     */
    public function ai_search()
    {
        // Logic tìm kiếm thông tin
    }

    /**
     * Get keyword analysis - phân tích từ khóa trong nội dung
     */
    public function get_keyword_analysis()
    {
        // Logic phân tích từ khóa
    }

    /**
     * Get SEO suggestions - đề xuất cải thiện SEO
     */
    public function get_seo_suggestions()
    {
        // Logic đề xuất SEO
    }
}
```
