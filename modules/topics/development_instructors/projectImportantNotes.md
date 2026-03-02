#### Hướng Dẫn Hoạt Động Cho AI
- **Tìm Kiếm Trước Khi Hành Động**:
  - Trước khi tạo bất kỳ thư mục hoặc file mới nào, **tìm kiếm toàn bộ cây thư mục hiện tại** để kiểm tra xem tài nguyên liên quan (thư mục hoặc file) đã tồn tại chưa.
  - Nếu tài nguyên đã tồn tại, **đọc nội dung** và **sử dụng nó** thay vì tạo mới.
  - Nếu không tìm thấy, **đề xuất tạo mới** nhưng **không tự động thực hiện** mà chờ xác nhận từ người dùng.
  - **Ví dụ**:
    ```
    Tôi không tìm thấy file 'helpers/my_helper.php'. Tôi đề xuất tạo mới tại đường dẫn 'modules/module_name/helpers/my_helper.php'. Bạn có đồng ý không? (Có/Không)
    ```

- **Tuyệt Đối Không Xóa**:
  - Không bao giờ sử dụng lệnh xóa (`rm`, `del`, hoặc tương tự) đối với thư mục hoặc file, kể cả khi chúng không mong muốn. Thay vào đó, thông báo cho người dùng và đề xuất cách xử lý (ví dụ: tái sử dụng, bỏ qua, hoặc di chuyển).
  - **Ví dụ**:
    ```
    Tôi tìm thấy file 'old_config.php' không còn cần thiết. Tôi đề xuất bỏ qua hoặc di chuyển nó thay vì xóa. Bạn muốn xử lý thế nào?
    ```

- **Thông Báo và Xác Nhận**:
  - Khi phát hiện tài nguyên hiện có, thông báo cho người dùng và hỏi ý kiến:
    ```
    Tôi tìm thấy 'helpers/platform_connectors.php'. Tôi sẽ sử dụng nó để tiếp tục. Bạn có đồng ý không? (Có/Không)
    ```
  - Nếu phát hiện nhiều tài nguyên tương tự, liệt kê tất cả và yêu cầu người dùng chọn:
    ```
    Tôi tìm thấy các file tương tự:
    1. 'helpers/platform_connectors.php'
    2. 'includes/platform_connectors.php'
    Bạn muốn sử dụng file nào?
    ```

- **Tối Ưu Hóa (Nếu Có MCP)**:
  - Nếu dự án tích hợp MCP (Model Context Protocol), sử dụng các hàm như `list_files` hoặc `get_directory_structure` để kiểm tra cây thư mục một cách an toàn và hiệu quả.
  - **Ví dụ**:
    ```
    Tôi đã sử dụng 'list_files' và tìm thấy 'modules/module_name/config.php'. Tôi sẽ sử dụng file này.
    ```

    ## Xử Lý File Lớn và Chia Nhỏ (Chunking) – *Điểm Bổ Sung Chính*
Khi có thể cần tạo (generating) một file dài, **tìm mọi cách để chia nhỏ (chunking)** thành các phần nhỏ hơn và cấy ghép chúng vào bộ nhớ/context tiếp theo một cách liên tục.

### Cách Thực Hiện:
1. **Chia nhỏ nội dung**: Phân chia file thành các phần logic (ví dụ: theo hàm, lớp, hoặc khối tính năng) để mỗi phần dễ quản lý và không làm quá tải hệ thống.
2. **Lưu trữ từng phần**: Đưa các phần đã chia vào bộ nhớ hoặc context để AI có thể truy cập và xử lý liên tục trong các bước tiếp theo.
3. **Đảm bảo tính liên tục**: Kết nối các phần một cách hợp lý để khi ghép lại, file vẫn hoạt động đúng như mong đợi.

**Ví dụ**:  
Nếu cần tạo một file controller lớn trong Perfex CRM, chẳng hạn `My_controller.php`:
- Chia nhỏ thành các hàm riêng biệt (ví dụ: `index()`, `create()`, `update()`).
- Lưu từng hàm vào context với chú thích rõ ràng:
  > Phần 1: Hàm `index()` - Xử lý hiển thị danh sách  
  > Phần 2: Hàm `create()` - Xử lý tạo mới bản ghi  
  > Phần 3: Hàm `update()` - Xử lý cập nhật bản ghi  
- Sau khi xử lý từng phần, ghép lại thành file hoàn chỉnh:

```php
<?php
class My_controller extends App_Controller {
    public function index() { /* Phần 1 */ }
    public function create() { /* Phần 2 */ }
    public function update() { /* Phần 3 */ }
}

#### #AI - Agent: How to Think and Operate
- **Luôn Tải và Xem File/Folder/Database Thực Tế Trước Khi Chỉnh Sửa**:
  - **Xem File Cần Sửa**: Tải file về và đọc mã nguồn để nắm rõ logic, cấu trúc, và trạng thái hiện tại.
  - **Kiểm Tra Cây Thư Mục**: Xem xét cấu trúc thư mục của dự án để xác định chính xác đường dẫn file hoặc các file liên quan.
  - **Kiểm Tra Cơ Sở Dữ Liệu (qua MCP)**: Nếu thay đổi liên quan đến database, sử dụng các công cụ MCP như `query` hoặc `describe_table` để kiểm tra schema hoặc dữ liệu hiện tại.
  - **Phân Tích Trước Khi Sửa**: Chỉ bắt đầu đề xuất hoặc chỉnh sửa sau khi đã hiểu rõ file, thư mục, hoặc dữ liệu liên quan.

- **File Size Management**:
  - Nếu file quá lớn (ví dụ: hơn 10,000 dòng), chia nhỏ thành các phần dễ quản lý (như hàm, lớp, hoặc khối tính năng) và xử lý từng phần để đảm bảo độ chính xác.

- **Code Understanding**:
  - Phân tích mã nguồn theo ngữ cảnh, hiểu cấu trúc dự án, mối quan hệ giữa các file, và tuân thủ quy ước của Perfex CRM. Đảm bảo mọi thay đổi phù hợp với kiến trúc module và tiêu chuẩn mã hóa của Perfex CRM.

- **Error Prevention**:
  - Kiểm tra kỹ đường dẫn file, phạm vi biến, và các hàm đặc thù của Perfex CRM trước khi thay đổi. Nếu không chắc chắn, yêu cầu làm rõ hoặc đề xuất cách tiếp cận an toàn hơn.

- **Change Proposals**:
  - Khi đề xuất chỉnh sửa, cung cấp hướng dẫn rõ ràng, từng bước, bao gồm:
    - Tên file chính xác và đường dẫn.
    - Số dòng hoặc đoạn mã cần chỉnh sửa (nếu có).
    - Mã nguồn đề xuất kèm chú thích giải thích mục đích.
  - **Ví dụ**:
    ```
    File: 'modules/module_name/controllers/My_controller.php'
    Dòng: 45
    Đề xuất thay đổi:
    // Trước: $data = array();
    // Sau: $data = $this->db->get('tblclients')->result_array(); // Lấy danh sách khách hàng từ database
    ```

- **MCP Utilization**:
  - Sử dụng MCP để mở rộng khả năng bằng cách kết nối với công cụ và dữ liệu bên ngoài:
    - **mysql server**: Dùng các công cụ như `query` để truy xuất dữ liệu từ cơ sở dữ liệu Perfex CRM (ví dụ: lấy danh sách khách hàng). Đảm bảo người dùng MySQL chỉ có quyền đọc (`SELECT`).
    - **browser-tools server**: Dùng các công cụ như `takeScreenshot` hoặc `getConsoleErrors` để kiểm tra và gỡ lỗi giao diện front-end của module.
    - **Security Measures**: Luôn bật "Delete file protection" và "MCP tools protection" để ngăn xóa file hoặc thực thi công cụ không mong muốn. Thêm lệnh nguy hiểm vào "Command denylist" (ví dụ: `rm -rf /`, `DROP TABLE`).

#### #Project Important Notes
##### ##Project Structure
- Dự án là một thư mục nằm trong môi trường hosting, hoạt động như một module tùy chỉnh cho Perfex CRM – một hệ thống CRM dựa trên PHP.
- **Quy trình phát triển**:
  - Sao chép thư mục module về máy cục bộ để phát triển.
  - Thực hiện thay đổi cục bộ, sau đó đồng bộ lại với server hosting bằng **ftp-sync plugin** trong VS Code.
- Module phải tuân thủ tiêu chuẩn của Perfex CRM, bao gồm quy ước đặt tên, cấu trúc file, và các điểm tích hợp (ví dụ: hooks, libraries, controllers).

##### ##Path Considerations
- **FCPATH Awareness**: Hằng số `FCPATH` trỏ đến thư mục gốc của Perfex CRM, không phải thư mục module. Tránh dùng đường dẫn cứng liên quan đến thư mục gốc module trừ khi cần thiết.
- **Module Path Functions**: Khi tham chiếu file trong module, dùng các hàm đường dẫn của Perfex CRM như `module_dir_path('module_name')` hoặc `module_libs_path('module_name')` để đảm bảo tương thích trên các môi trường server.
- **Direct File Includes**: Nếu cần bao gồm file trực tiếp (ví dụ: `include`, `require`), xây dựng đường dẫn cẩn thận:
  - *Ví dụ*: Để thêm file `helpers/my_helper.php` trong module, dùng `FCPATH . 'modules/module_name/helpers/my_helper.php'`.
  - Kiểm tra đường dẫn để tránh lỗi "file not found", đặc biệt khi triển khai trên các server khác nhau.
- **Asset Paths**: Với tài nguyên front-end (CSS, JS), dùng `module_dir_url('module_name')` để tạo URL chính xác, đảm bảo hoạt động tốt bất kể cấu hình server.

##### ##MCP Integration
- **mysql server**: Tương tác an toàn với cơ sở dữ liệu Perfex CRM:
  - **Công cụ**: `connect_db`, `query`, `list_tables`, `describe_table`.
  - **Ví dụ**: Lấy 10 khách hàng bằng `query("SELECT * FROM tblclients LIMIT 10")`.
  - **Bảo mật**: Cấu hình người dùng MySQL với quyền chỉ đọc để ngăn sửa đổi dữ liệu.
- **browser-tools server**: Kiểm tra và gỡ lỗi front-end của module:
  - **Công cụ**: `takeScreenshot`, `getConsoleLogs`, `getConsoleErrors`, `runAccessibilityAudit`.
  - **Ví dụ**: Chụp ảnh màn hình dashboard bằng `takeScreenshot("module_dashboard")`.
  - **Bảo mật**: Bật "MCP tools protection" để ngăn thực thi công cụ không được phép.
- **General MCP Security**:
  - Bật "Delete file protection" để ngăn xóa file ngẫu nhiên.
  - Thêm lệnh nguy hiểm vào "Command denylist" (ví dụ: `DROP TABLE`, `DELETE FROM`, `rm -rf`).
  - Theo dõi log MCP server để giám sát hoạt động của AI.

##### ##Testing
- **Syncing Changes**: Sau khi thay đổi cục bộ, đồng bộ với server hosting bằng **ftp-sync plugin** trong VS Code. Đảm bảo plugin được cấu hình đúng với thông tin đăng nhập và đường dẫn server.
- **Server Testing**: Kiểm thử mọi thay đổi trên server hosting, vì module phụ thuộc vào môi trường Perfex CRM. Kiểm tra lỗi trong log của Perfex CRM (`application/logs/log-*.php`).
- **Local Testing (Optional)**: Nếu cần kiểm thử cục bộ, cài đặt Perfex CRM trên máy, sao chép module vào `modules/`, và cấu hình database tương ứng.

#### Hướng Dẫn Sử Dụng Platform Connectors

Để đảm bảo tính nhất quán và dễ bảo trì khi tương tác với các nền tảng bên ngoài (WordPress, Haravan, v.v.), hãy tuân thủ các quy tắc sau:

### 1. Cấu Trúc Standard Khi Sử Dụng Connectors

- **Luôn Sử Dụng Interface**: Mọi connector phải triển khai interface `PlatformConnectorInterface` để đảm bảo tính nhất quán.
- **Định Dạng Dữ Liệu Chuẩn**: Các phương thức trong connector phải trả về dữ liệu theo định dạng chuẩn:
  ```php
  // Ví dụ cho phương thức get_tags:
  return [
      'data' => [...],           // Mảng các tag
      'total_pages' => int,      // Tổng số trang
      'total_items' => int,      // Tổng số item
      'http_code' => int         // Mã HTTP của API request
  ];
  ```
- **Quy Trình Xử Lý Lỗi**: Luôn kiểm tra và xử lý lỗi, trả về `false` nếu có lỗi và ghi log đầy đủ.

### 2. Sử Dụng Connector Trong Controller

- **Lấy Connector Đúng Cách**:
  ```php
  $platform = $controller->platform;
  $connector = get_platform_connector($platform);
  if (!$connector) {
      // Xử lý lỗi khi không tìm thấy connector
  }
  ```
- **Truyền Tham Số Đúng Chuẩn**:
  ```php
  $result = $connector->get_tags($login_config, $controller->blog_id, [
      'per_page' => 20,
      'page' => $page
  ]);
  ```
- **Xử Lý Kết Quả Nhất Quán**:
  ```php
  if ($result === false) {
      // Xử lý lỗi
  } else {
      $tags = $result['data'];
      $total_pages = $result['total_pages'];
      // Xử lý dữ liệu thành công
  }
  ```

### 3. Phát Triển Connector Mới

Khi phát triển connector cho nền tảng mới:

- **Tạo File Riêng**: Đặt connector trong thư mục `includes/platform_connectors` với tên `platform_name_connector.php`.
- **Triển Khai Interface Đầy Đủ**: Phải triển khai tất cả các phương thức trong interface.
- **Xử Lý Authentication**: Cung cấp xử lý xác thực phù hợp với nền tảng.
- **Ghi Log Chi Tiết**: Sử dụng `log_message()` để ghi lại thông tin chi tiết về quá trình xử lý.
- **Chuẩn Hóa Response**: Luôn chuyển đổi dữ liệu từ API thành định dạng chuẩn mà hệ thống có thể xử lý.

### 4. Đồng Bộ Dữ Liệu

- **Phân Trang**: Luôn phân trang khi đồng bộ dữ liệu lớn, với số lượng chuẩn là 20 items/trang.
- **Quản Lý Phiên Đồng Bộ**: Sử dụng `Topic_sync_log_model` để quản lý tiến trình đồng bộ.
- **Xử Lý Gián Đoạn**: Thiết kế các phương thức để tiếp tục đồng bộ sau khi bị gián đoạn.
- **Thông Báo Tiến Trình**: Luôn cập nhật và hiển thị tiến trình đồng bộ cho người dùng.

### 5. Tránh Các Lỗi Phổ Biến

- **Không Hard Code URL**: Không hard code URL API, luôn lấy từ cấu hình.
- **Không Bỏ Qua Lỗi**: Không bỏ qua lỗi API, luôn ghi log và xử lý phù hợp.
- **Kiểm Tra Response**: Luôn kiểm tra cấu trúc response trước khi xử lý để tránh lỗi.
- **Không Lưu Trữ Credential**: Không lưu trữ thông tin đăng nhập trong code, sử dụng cấu hình từ database.

### 6. Ví Dụ Sử Dụng Platform Connector

```php
// Lấy connector
$platform = $controller->platform;
$connector = get_platform_connector($platform);

// Lấy cấu hình đăng nhập
$login_config = $this->Topic_controller_model->get_login_config($controller_id);
$login_config['platform'] = $platform;

// Lấy tags từ nền tảng
$result = $connector->get_tags($login_config, $controller->blog_id, [
    'per_page' => 20,
    'page' => $page
]);

// Xử lý kết quả
if ($result === false) {
    // Xử lý lỗi
    log_message('error', 'Failed to get tags from platform');
} else {
    // Xử lý dữ liệu
    foreach ($result['data'] as $tag) {
        // Lưu tag vào database
    }
    
    // Kiểm tra hoàn thành
    $is_complete = ($page >= $result['total_pages']);
}
```

Tuân thủ các quy tắc trên sẽ giúp đảm bảo tính nhất quán, dễ bảo trì, và ổn định khi làm việc với các nền tảng bên ngoài.