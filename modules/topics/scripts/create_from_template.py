#!/usr/bin/env python3
# -*- coding: utf-8 -*-

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