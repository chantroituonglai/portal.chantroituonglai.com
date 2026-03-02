# Hướng Dẫn Tạo File Bằng Template Và Generate Nội Dung

AI Agent cần tuân thủ quy trình sau khi làm việc với file lớn để tránh crash:

## 1. Quy Trình Template-Based File Generation

### Bước 1: Xác định và sử dụng template có sẵn
- **KHÔNG** trực tiếp tạo file lớn rỗi rồi generate nội dung
- **LUÔN** sử dụng template file có sẵn trong thư mục `TEMPLATES/`
- Template chứa cấu trúc file, placeholder và metadata cần thiết

```bash
# Kiểm tra các template có sẵn
ls -la TEMPLATES/
```

### Bước 2: Sao chép template vào file mới
- Sử dụng lệnh terminal để sao chép template vào file đích
- Đảm bảo đường dẫn chính xác và thư mục đích tồn tại

```bash
# Tạo file mới từ template 
cp TEMPLATES/ai_memory_manager_template.md path/to/NEW_FILE_NAME.md
```

### Bước 3: Generate nội dung với script hỗ trợ
- Sử dụng script Python để phân tích và điền nội dung vào các placeholder
- KHÔNG generate trực tiếp toàn bộ nội dung trong prompt AI

```bash
# Generate nội dung vào file mới
python scripts/content_generator.py --input=path/to/NEW_FILE_NAME.md --output=path/to/NEW_FILE_NAME.md
```

## 2. Giải Pháp Chống Crash Khi Generate File Lớn

### Nguyên tắc quan trọng:
1. **Phân nhỏ quá trình generate**: Chia nhỏ quá trình sinh nội dung thành nhiều bước
2. **Xử lý theo section**: Generate từng section một, không làm toàn bộ file cùng lúc
3. **Checkpoint thường xuyên**: Lưu trạng thái sau mỗi bước quan trọng
4. **Sử dụng external tool**: Luôn dùng script để hỗ trợ việc generate thay vì xử lý trực tiếp

### Các loại file thường sử dụng:
- **Template file**: Khung cấu trúc cơ bản (ví dụ: TEMPLATES/ai_memory_manager_template.md)
- **Content spec file**: Định nghĩa nội dung cần điền (ví dụ: CONTENT_SPECS/memory_manager_content_spec.json)
- **Helper scripts**: Script hỗ trợ generate (ví dụ: scripts/content_generator.py)

## 3. Scripts Hỗ Trợ (Không generate trong prompt)

### Script Copy Template
Sử dụng script sau để tạo file mới từ template:

```python
# scripts/create_from_template.py
import os
import sys
import shutil
import argparse
from datetime import datetime

def create_from_template(template_path, output_path, metadata=None):
    """
    Tạo file mới từ template với metadata cơ bản
    """
    # Kiểm tra template tồn tại
    if not os.path.exists(template_path):
        print(f"Lỗi: Template {template_path} không tồn tại!")
        return False
        
    # Tạo thư mục chứa nếu chưa tồn tại
    output_dir = os.path.dirname(output_path)
    if output_dir and not os.path.exists(output_dir):
        os.makedirs(output_dir)
    
    # Sao chép template
    shutil.copy2(template_path, output_path)
    print(f"Đã tạo file mới từ template: {output_path}")
    
    # Nếu có metadata, cập nhật metadata cơ bản
    if metadata:
        with open(output_path, 'r', encoding='utf-8') as f:
            content = f.read()
            
        # Thay thế các placeholder cơ bản
        content = content.replace('{{CREATION_DATE}}', datetime.now().strftime('%Y-%m-%d'))
        content = content.replace('{{AUTHOR}}', metadata.get('author', 'AI Assistant'))
        content = content.replace('{{VERSION}}', metadata.get('version', '1.0.0'))
        
        with open(output_path, 'w', encoding='utf-8') as f:
            f.write(content)
        
    return True

if __name__ == "__main__":
    parser = argparse.ArgumentParser(description='Tạo file mới từ template')
    parser.add_argument('--template', required=True, help='Đường dẫn đến template')
    parser.add_argument('--output', required=True, help='Đường dẫn đến file output')
    parser.add_argument('--author', default='AI Assistant', help='Tác giả')
    parser.add_argument('--version', default='1.0.0', help='Phiên bản')
    
    args = parser.parse_args()
    
    metadata = {
        'author': args.author,
        'version': args.version
    }
    
    create_from_template(args.template, args.output, metadata)
```

### Script Generate Nội Dung
Sử dụng script sau để điền nội dung chi tiết vào file từ template:

```python
# scripts/content_generator.py
import os
import sys
import json
import re
import argparse
from datetime import datetime

def load_content_spec(spec_path):
    """
    Tải nội dung chi tiết từ file spec
    """
    with open(spec_path, 'r', encoding='utf-8') as f:
        return json.load(f)

def process_file(input_path, output_path, content_spec_path=None, content_data=None):
    """
    Xử lý file và điền nội dung vào các placeholder
    """
    # Đọc nội dung file input
    with open(input_path, 'r', encoding='utf-8') as f:
        content = f.read()
    
    # Nếu có file content spec, tải nó
    if content_spec_path and os.path.exists(content_spec_path):
        content_data = load_content_spec(content_spec_path)
    
    # Nếu không có content_data, không thể tiếp tục
    if not content_data:
        print("Lỗi: Không có dữ liệu nội dung để điền!")
        return False
    
    # Tìm tất cả các placeholder trong file
    placeholders = re.findall(r'\[\[PLACEHOLDER:([\w]+)\]\]', content)
    
    # Thay thế từng placeholder với nội dung tương ứng
    for placeholder in placeholders:
        if placeholder in content_data:
            content = content.replace(f'[[PLACEHOLDER:{placeholder}]]', content_data[placeholder])
        else:
            print(f"Cảnh báo: Không tìm thấy nội dung cho placeholder {placeholder}")
    
    # Thêm thông tin thời gian generate
    content = content.replace('{{GENERATION_DATE}}', datetime.now().strftime('%Y-%m-%d %H:%M:%S'))
    
    # Ghi nội dung đã xử lý vào file output
    with open(output_path, 'w', encoding='utf-8') as f:
        f.write(content)
    
    print(f"Đã generate nội dung vào file: {output_path}")
    return True

def process_file_in_sections(input_path, output_path, content_spec_path=None, content_data=None):
    """
    Xử lý file theo từng section để tránh quá tải bộ nhớ
    """
    # Đọc nội dung file input
    with open(input_path, 'r', encoding='utf-8') as f:
        content = f.read()
    
    # Nếu có file content spec, tải nó
    if content_spec_path and os.path.exists(content_spec_path):
        content_data = load_content_spec(content_spec_path)
    
    # Nếu không có content_data, không thể tiếp tục
    if not content_data:
        print("Lỗi: Không có dữ liệu nội dung để điền!")
        return False
    
    # Chia file thành các section dựa trên heading
    sections = re.split(r'(#+\s+.*\n)', content)
    processed_content = ""
    
    # Xử lý từng section
    for i, section in enumerate(sections):
        # Tìm tất cả các placeholder trong section
        placeholders = re.findall(r'\[\[PLACEHOLDER:([\w]+)\]\]', section)
        section_processed = section
        
        # Thay thế từng placeholder với nội dung tương ứng
        for placeholder in placeholders:
            if placeholder in content_data:
                section_processed = section_processed.replace(
                    f'[[PLACEHOLDER:{placeholder}]]', 
                    content_data[placeholder]
                )
            else:
                print(f"Cảnh báo: Không tìm thấy nội dung cho placeholder {placeholder}")
        
        # Thêm vào nội dung đã xử lý
        processed_content += section_processed
        
        # Lưu checkpoint sau mỗi 5 section hoặc section cuối
        if (i > 0 and i % 5 == 0) or i == len(sections) - 1:
            with open(output_path, 'w', encoding='utf-8') as f:
                f.write(processed_content)
            print(f"Checkpoint: Đã xử lý {i+1}/{len(sections)} sections")
    
    # Thêm thông tin thời gian generate
    processed_content = processed_content.replace(
        '{{GENERATION_DATE}}', 
        datetime.now().strftime('%Y-%m-%d %H:%M:%S')
    )
    
    # Ghi nội dung đã xử lý vào file output
    with open(output_path, 'w', encoding='utf-8') as f:
        f.write(processed_content)
    
    print(f"Đã generate nội dung vào file: {output_path}")
    return True

if __name__ == "__main__":
    parser = argparse.ArgumentParser(description='Generate nội dung vào file từ template')
    parser.add_argument('--input', required=True, help='Đường dẫn đến file input')
    parser.add_argument('--output', required=True, help='Đường dẫn đến file output')
    parser.add_argument('--spec', help='Đường dẫn đến file content spec (JSON)')
    parser.add_argument('--sections', action='store_true', help='Xử lý theo từng section')
    
    args = parser.parse_args()
    
    if args.sections:
        process_file_in_sections(args.input, args.output, args.spec)
    else:
        process_file(args.input, args.output, args.spec)
```

## 4. Ví Dụ Quy Trình Hoàn Chỉnh

### Tạo và cài đặt môi trường
```bash
# Tạo các thư mục cần thiết
mkdir -p TEMPLATES CONTENT_SPECS scripts

# Cài đặt quyền thực thi cho scripts
chmod +x scripts/create_from_template.py
chmod +x scripts/content_generator.py
```

### Ví dụ tạo file AI Memory Manager
```bash
# Bước 1: Tạo file mới từ template
python scripts/create_from_template.py --template=TEMPLATES/ai_memory_manager_template.md --output=docs/AI_MEMORY_PLAN.md --author="AI Assistant" --version="1.0.0"

# Bước 2: Generate nội dung theo từng section
python scripts/content_generator.py --input=docs/AI_MEMORY_PLAN.md --output=docs/AI_MEMORY_PLAN.md --spec=CONTENT_SPECS/memory_manager_content.json --sections
```

## 5. Lưu ý Quan Trọng Khi Làm Việc Với File Lớn

1. **KHÔNG BAO GIỜ** trực tiếp generate toàn bộ nội dung file lớn trong prompt AI
2. **LUÔN** sử dụng quy trình từng bước: tạo từ template -> generate từng phần
3. **ƯU TIÊN** sử dụng các công cụ và script thay vì xử lý trực tiếp
4. **PHÂN ĐOẠN** quá trình tạo nội dung thành nhiều phần nhỏ
5. **LƯU CHECKPOINT** thường xuyên để tránh mất dữ liệu khi xảy ra sự cố

Tuân thủ quy trình này sẽ giúp AI Agent tránh được các sự cố crash khi làm việc với file lớn và phức tạp.
