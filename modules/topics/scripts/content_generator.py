#!/usr/bin/env python3
# -*- coding: utf-8 -*-

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