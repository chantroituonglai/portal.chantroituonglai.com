# QUY TRÌNH TẠO FILE LỚN

> **⚠️ CẢNH BÁO QUAN TRỌNG ⚠️**  
> 
> AI Agent **PHẢI TUÂN THỦ** các nguyên tắc sau:
> 1. **CHỈ SỬ DỤNG** các file Python có sẵn:
>    - `scripts/create_from_template.py` (**ĐÃ TỒN TẠI** - KHÔNG TẠO MỚI)
>    - `scripts/content_generator.py` (**ĐÃ TỒN TẠI** - KHÔNG TẠO MỚI)
> 2. **KHÔNG** tạo file Python mới trong bất kỳ trường hợp nào
> 3. Tất cả scripts đã được triển khai sẵn - chỉ cần gọi với tham số phù hợp
> 4. Nếu gặp lỗi với script, báo cáo lỗi - **KHÔNG** viết script thay thế
>
> Quy trình này đã được thiết kế để tránh crash do giới hạn token khi xử lý file lớn!

## QUY TRÌNH BẮT BUỘC CHO AI AGENT

> **🔍 3 BƯỚC BẮT BUỘC TRƯỚC MỖI THAO TÁC:**
>
> **1. KIỂM TRA HƯỚNG DẪN & MEMORY:** 
>    ```bash
>    # Luôn đọc ultimateInstruction.md đầu tiên
>    cat development_instructors/ultimateInstruction.md | head -30
>    
>    # Kiểm tra memory gần nhất bằng cách hiểu rõ context từ các message trước
>    ```
>
> **2. KIỂM TRA TASK FILE:** 
>    ```bash
>    # Luôn đọc task file khi được đề cập trong prompt (ký hiệu @ là dấu hiệu file task)
>    # Ví dụ khi thấy @topicControllerxDraftWrtingxUltimateEditorMasterPLan.md:
>    cat development_instructors/topicControllerxDraftWrtingxUltimateEditorMasterPLan.md | head -50
>    ```
>
> **3. KIỂM TRA & ĐÁNH DẤU SAU KHI HOÀN THÀNH:**
>    ```bash
>    # Sau khi tạo/sửa file, kiểm tra lại kết quả
>    cat file_vua_tao_hoac_sua.php
>    
>    # Quay lại đánh dấu vào checklist trong file task nếu áp dụng
>    cat development_instructors/FILE_TASK.md | grep -A10 "Danh Sách Task"
>    ```
>
> **⚠️ LUÔN THỰC HIỆN ĐỦ 3 BƯỚC ⚠️**
> - **NGỪNG NGAY LẬP TỨC** nếu trong quá trình "thought" phát hiện đang thiếu bất kỳ bước nào
> - **DỪNG NGAY** và hỏi lại người dùng trước khi tiếp tục nếu không chắc chắn đã thực hiện đầy đủ
> - **KHÔNG TIẾP TỤC** nếu chưa hoàn thành bất kỳ bước nào trong 3 bước bắt buộc
> - Nếu bỏ qua bất kỳ bước nào = RỦI RO CAO về việc vi phạm hướng dẫn
> - Khi không chắc chắn, LUÔN đọc file hướng dẫn và task trước khi hành động
> - Các file task LUÔN có checklist ở đầu file để đánh dấu tiến độ

## HIỂU LẦM THƯỜNG GẶP - ĐỌC KỸ

> **❌ HIỂU LẦM THƯỜNG GẶP:**  
> - KHÔNG cần tạo file `create_from_template.py` - file này đã tồn tại
> - KHÔNG cần tạo file `content_generator.py` - file này đã tồn tại
> - Các file cần được tạo và chỉnh sửa là template, content spec, và file output
>
> **✅ CÁCH ĐÚNG:**
> - Sử dụng các lệnh với script có sẵn: `python scripts/create_from_template.py ...`
> - Sử dụng các lệnh với script có sẵn: `python scripts/content_generator.py ...`
> - Tạo file template và spec trong thư mục tương ứng (`TEMPLATES/` và `CONTENT_SPECS/`)

## CHECKLIST BẮT BUỘC TRƯỚC KHI BẮT ĐẦU

> **🔍 LUÔN THỰC HIỆN CÁC BƯỚC SAU TRƯỚC KHI BẮT ĐẦU:**
> 
> - [ ] **ĐỌC TÀI LIỆU**: Đọc ultimateInstruction.md và các file hướng dẫn được đề cập trong prompt
> - [ ] **KIỂM TRA SCRIPTS**: Xác nhận các scripts Python đã tồn tại
> - [ ] **KIỂM TRA TEMPLATES**: Xác nhận các file template cần thiết đã tồn tại
> - [ ] **HIỂU RÕ TASK**: Đảm bảo hiểu đúng yêu cầu trước khi thực hiện
>
> **⚡ LUÔN CHẠY CÁC LỆNH TERMINAL SAU ĐẦU TIÊN:**
> ```bash
> # 1. Đọc hướng dẫn quy trình (BƯỚC BẮT BUỘC)
> cat development_instructors/ultimateInstruction.md | head -50
> 
> # 2. Đọc các file hướng dẫn khác được đề cập trong prompt (BƯỚC BẮT BUỘC)
> # Ví dụ: Nếu prompt nhắc đến @topicControllerxDraftWrtingxUltimateEditorMasterPLan.md
> cat development_instructors/topicControllerxDraftWrtingxUltimateEditorMasterPLan.md | head -50
> 
> # 3. Kiểm tra các script và template có sẵn (BƯỚC BẮT BUỘC)
> ls -la scripts/
> ls -la TEMPLATES/
> ```

## QUY TRÌNH LÀM VIỆC AN TOÀN 

> **🔄 TUÂN THỦ QUY TRÌNH 7 BƯỚC NÀY ĐỂ TRÁNH CRASH:**
>
> 1. **ĐỌC TÀI LIỆU** (TERMINAL):
>    ```bash
>    cat development_instructors/ultimateInstruction.md
>    cat development_instructors/FILE_HƯỚNG_DẪN_LIÊN_QUAN.md
>    ```
>
> 2. **KIỂM TRA CÁC THÀNH PHẦN CÓ SẴN** (TERMINAL):
>    ```bash
>    ls -la scripts/
>    ls -la TEMPLATES/
>    ls -la CONTENT_SPECS/
>    ```
>
> 3. **TẠO THƯ MỤC NẾU CẦN** (TERMINAL):
>    ```bash
>    mkdir -p CONTENT_SPECS/ten_module
>    mkdir -p views/ten_module
>    ```
>
> 4. **TẠO TEMPLATE** (TERMINAL):
>    ```bash
>    python scripts/create_from_template.py --template=TEMPLATES/php_view_template.php --output=views/ten_module/ten_view.php --author="Ten_tac_gia" --version="1.0.0"
>    ```
>
> 5. **TẠO CONTENT SPEC** (TERMINAL):
>    ```bash
>    # Tạo file JSON content spec bằng lệnh cat hoặc tool edit_file
>    cat > CONTENT_SPECS/ten_module/ten_view_content.json << 'EOL'
>    {
>      "sections": [
>        {"name": "section1", "description": "Mô tả section 1"},
>        {"name": "section2", "description": "Mô tả section 2"}
>      ]
>    }
>    EOL
>    ```
>
> 6. **SINH NỘI DUNG THEO SECTION** (TERMINAL):
>    ```bash
>    python scripts/content_generator.py --input=views/ten_module/ten_view.php --output=views/ten_module/ten_view.php --spec=CONTENT_SPECS/ten_module/ten_view_content.json --sections
>    ```
>
> 7. **KIỂM TRA KẾT QUẢ** (TERMINAL):
>    ```bash
>    cat views/ten_module/ten_view.php
>    ```

## HƯỚNG DẪN XỬ LÝ GIAO DIỆN PHP

### QUY TRÌNH XỬ LÝ VIEWS
1. **Tạo Template Trước**
   ```bash
   # Template cho view PHP
   python scripts/create_from_template.py --template=TEMPLATES/php_view_template.php --output=views/ten_module/ten_view.php --author="Ten_tac_gia" --version="1.0.0"
   ```

2. **Phân Chia Section**
   ```json
   // Tạo file: CONTENT_SPECS/ten_module/ten_view_content.json
   {
     "sections": [
       {
         "name": "header",
         "description": "Phần khai báo PHP và HTML header"
       },
       {
         "name": "navbar",
         "description": "Thanh điều hướng"
       },
       {
         "name": "main_content",
         "description": "Nội dung chính của trang"
       },
       {
         "name": "sidebar",
         "description": "Sidebar nếu có"
       },
       {
         "name": "footer",
         "description": "Phần cuối và JavaScript"
       }
     ]
   }
   ```

3. **Tạo Nội Dung Theo Section**
   ```bash
   python scripts/content_generator.py --input=views/ten_module/ten_view.php --output=views/ten_module/ten_view.php --spec=CONTENT_SPECS/ten_module/ten_view_content.json --sections
   ```

### VÍ DỤ CHO ULTIMATE EDITOR
```bash
# Bước 1: Tạo template
python scripts/create_from_template.py --template=TEMPLATES/php_view_template.php --output=views/topics/ultimate_editor/index.php --author="Developer" --version="1.0.0"

# Bước 2: Chuẩn bị spec
mkdir -p CONTENT_SPECS/ultimate_editor
touch CONTENT_SPECS/ultimate_editor/index_content.json

# Bước 3: Định nghĩa content spec (nội dung file JSON)
# {
#   "sections": [
#     {"name": "header", "description": "PHP declarations and HTML head"},
#     {"name": "editor_toolbar", "description": "Toolbar chứa các nút chức năng"},
#     {"name": "editor_container", "description": "Container chính của editor"},
#     {"name": "sidebar_panel", "description": "Panel điều khiển bên cạnh"},
#     {"name": "modals", "description": "Các modal dialog"},
#     {"name": "scripts", "description": "JavaScript imports và initialization"}
#   ]
# }

# Bước 4: Sinh nội dung từng section
python scripts/content_generator.py --input=views/topics/ultimate_editor/index.php --output=views/topics/ultimate_editor/index.php --spec=CONTENT_SPECS/ultimate_editor/index_content.json --sections
```

### LỢI ÍCH CỦA PHÂN ĐOẠN
- **Tránh crash do giới hạn token**: Xử lý từng phần nhỏ
- **Kiểm soát tốt hơn**: Tập trung vào từng phần chức năng
- **Dễ dàng sửa lỗi**: Chỉ cần tái tạo section có vấn đề
- **Quản lý bộ nhớ hiệu quả**: Tránh tràn bộ nhớ trong quá trình "thought"
- **Quy trình có cấu trúc**: Dễ dàng theo dõi và quản lý các thành phần giao diện phức tạp

## CHUẨN BỊ
```bash
mkdir -p TEMPLATES CONTENT_SPECS docs
chmod +x scripts/*.py
```

## TẠO FILE MỚI
```bash
python scripts/create_from_template.py --template=TEMPLATES/ten_template.md --output=duong_dan/ten_file.md --author="Ten_tac_gia" --version="1.0.0"
```

## SINH NỘI DUNG
```bash
python scripts/content_generator.py --input=duong_dan/ten_file.md --output=duong_dan/ten_file.md --spec=CONTENT_SPECS/ten_file_content.json --sections
```

## LƯU Ý
- KHÔNG generate nội dung trực tiếp trong prompt
- LUÔN tạo file qua template trước
- LUÔN xử lý theo từng section (--sections)
- CHỈ SỬ DỤNG các file Python có sẵn (create_from_template.py, content_generator.py), KHÔNG TẠO FILE PYTHON MỚI
- Các scripts Python đã được triển khai sẵn, AI Agent chỉ cần gọi các scripts này với tham số phù hợp
- Nếu gặp lỗi khi chạy script, báo cáo lỗi và yêu cầu hỗ trợ thay vì cố gắng tạo script mới

## NHẬN DIỆN VÀ TRÁNH CRASH

> **🚨 DẤU HIỆU SẮP CRASH - DỪNG NGAY LẬP TỨC NẾU:**
>
> 1. **Bạn đang viết mã PHP/HTML trực tiếp trong prompt** thay vì sử dụng script content_generator.py
> 2. **Bạn đang cố gắng tạo file Python mới** thay vì sử dụng scripts có sẵn
> 3. **Bạn đang cố gắng sinh một file lớn hoàn chỉnh** thay vì chia nhỏ theo sections
> 4. **Bạn đang nhúng code trực tiếp vào prompt** thay vì sử dụng quy trình theo bước
> 5. **Bạn đang định chỉnh sửa file trực tiếp khi gặp lỗi** thay vì sử dụng scripts để sửa
>
> **✅ BIỆN PHÁP KHẮC PHỤC NGAY LẬP TỨC:**
>
> 1. **DỪNG** việc viết code trực tiếp
> 2. **CHẠY** lệnh terminal để xem lại hướng dẫn: `cat development_instructors/ultimateInstruction.md`
> 3. **TUÂN THỦ** quy trình 7 bước trong phần "QUY TRÌNH LÀM VIỆC AN TOÀN"
> 4. **SỬ DỤNG** các script có sẵn thay vì viết code trực tiếp
>
> ```bash
> # Nếu đã viết một nửa file PHP/HTML và nhận ra có thể sắp crash:
> # 1. Lưu nội dung đã viết vào một file tạm thời
> cat > temp_content.txt << 'EOL'
> [Nội dung đã viết đến thời điểm này]
> EOL
>
> # 2. Tạo template đúng cách
> python scripts/create_from_template.py --template=TEMPLATES/php_view_template.php --output=views/ten_module/ten_view.php --author="Ten_tac_gia" --version="1.0.0"
>
> # 3. Tiếp tục với quy trình an toàn
> ```
>
> **LUÔN NHỚ:** Khi có nghi ngờ, sử dụng terminal để thực hiện từng bước, KHÔNG viết code trực tiếp!

## XỬ LÝ LỖI ĐÚNG CÁCH

> **⚠️ TUYỆT ĐỐI KHÔNG CHỈNH SỬA FILE TRỰC TIẾP KHI GẶP LỖI ⚠️**
>
> Khi gặp lỗi trong quá trình sử dụng scripts, KHÔNG BAO GIỜ resort to manually editing the file. Thay vào đó:
>
> **✅ CÁCH XỬ LÝ LỖI ĐÚNG:**
>
> 1. **Kiểm tra cấu trúc template và content spec**:
>    ```bash
>    # Xem template hiện tại
>    cat TEMPLATES/php_view_template.php
>    
>    # Xem content spec
>    cat CONTENT_SPECS/ten_module/ten_view_content.json
>    ```
>
> 2. **Cập nhật content spec nếu cần**:
>    ```bash
>    # Chỉnh sửa content spec để đảm bảo đúng cấu trúc
>    cat > CONTENT_SPECS/ten_module/ten_view_content.json << 'EOL'
>    {
>      "sections": [
>        {"name": "section1", "description": "Mô tả chi tiết section 1"},
>        {"name": "section2", "description": "Mô tả chi tiết section 2"}
>      ]
>    }
>    EOL
>    ```
>
> 3. **Chạy lại script với flag verbose hoặc debug nếu có**:
>    ```bash
>    # Ví dụ chạy script với thêm thông tin debug
>    python scripts/content_generator.py --input=views/ten_module/ten_view.php --output=views/ten_module/ten_view.php --spec=CONTENT_SPECS/ten_module/ten_view_content.json --sections --verbose
>    ```
>
> 4. **Báo cáo lỗi chi tiết** nếu script tiếp tục gặp vấn đề:
>    - Copy đầy đủ output lỗi
>    - Mô tả chi tiết các bước đã thực hiện
>    - Không tự ý sửa script hoặc chỉnh sửa file đầu ra trực tiếp
>
> **❌ NHỮNG VIỆC KHÔNG NÊN LÀM:**
>
> - **KHÔNG** chỉnh sửa file PHP/HTML trực tiếp
> - **KHÔNG** tạo scripts mới để thay thế scripts hiện có
> - **KHÔNG** thay đổi cấu trúc của template nếu không cần thiết
> - **KHÔNG** bỏ qua việc sử dụng các script có sẵn vì gặp lỗi
>
> **💡 GỢI Ý SỬA LỖI THƯỜNG GẶP:**
>
> 1. **Lỗi "Section không tồn tại"**:
>    - Đảm bảo tên section trong content spec khớp với tên section trong template
>    - Kiểm tra xem template có comment đánh dấu section không: `<!-- SECTION: tên_section -->`
>
> 2. **Lỗi "File không tồn tại"**:
>    - Kiểm tra đường dẫn file
>    - Đảm bảo các thư mục cần thiết đã được tạo: `mkdir -p thư_mục/con`
>
> 3. **Lỗi "Phân tích JSON"**:
>    - Kiểm tra cấu trúc JSON trong content spec
>    - Đảm bảo không có dấu phẩy ở cuối phần tử cuối cùng trong mảng
