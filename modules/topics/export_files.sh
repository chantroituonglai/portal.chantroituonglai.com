#!/bin/bash

# Thiết lập các thư mục cần loại trừ
EXCLUDE_DIRS=(
    "/null"
)

# Thiết lập các thư mục cần bao gồm (dữ liệu tham chiếu)
INCLUDE_FOLDERS=(
    # "/Users/macbook/Documents/DEV/Portal/topics4/views"
    # "/Users/macbook/Documents/DEV/Portal/affiliate_management"
    # "/Users/macbook/Documents/DEV/Portal/api"
)  # Thay thế bằng các đường dẫn thực tế

# Tạo pattern loại trừ cho lệnh find
EXCLUDE_PATTERN=""
for DIR in "${EXCLUDE_DIRS[@]}"; do
  EXCLUDE_PATTERN+=" -path '$DIR' -o -path '$DIR/*' -o"
done
EXCLUDE_PATTERN=${EXCLUDE_PATTERN% -o}

# Đặt tên file output (loại bỏ khoảng trắng)
OUTPUT_FILE="$(basename "$PWD" | tr -d '[:space:]')_code_feed.txt"
rm -f "$OUTPUT_FILE"

# Hàm để ghi cấu trúc thư mục và mã nguồn vào file
process_directory() {
  local LABEL="$1"
  local SEARCH_PATH="$2"
  local DISPLAY_PREFIX="$3"

  echo "=== $LABEL ===" >> "$OUTPUT_FILE"
  echo "$LABEL STRUCTURE:" >> "$OUTPUT_FILE"

  # Sử dụng find để quét thư mục
  find "$SEARCH_PATH" -type d \( -path './.*' -o -path './node_modules' -o \( $EXCLUDE_PATTERN \) \) -prune -o -print | \
    awk -F/ '{
      depth = NF - 1
      indent = ""
      for(i=1; i<depth; i++) indent = indent "    "
      printf "%s├── %s\n", indent, $NF
    }' >> "$OUTPUT_FILE"

  echo "$LABEL CODE:" >> "$OUTPUT_FILE"

  # Tìm và xử lý các file mã nguồn
  find "$SEARCH_PATH" -type f \( \
      -iname "*.php" -o -iname "*.html" -o -iname "*.py" -o -iname "*.java" \
      -o -iname "*.c" -o -iname "*.cpp" -o -iname "*.cs" -o -iname "*.rb" \
      -o -iname "*.go" -o -iname "*.ts" -o -iname "*.xml" -o -iname "*.json" \
      -o -iname "*.yml" -o -iname "*.yaml" -o -iname "*.sh" \
  \) ! -iname "$(basename "$0")" ! -iname "ftp-sync.json" \
  ! \( $EXCLUDE_PATTERN \) -print0 | while IFS= read -r -d '' FILE; do
      echo "Đang xử lý: $FILE"
      # Loại bỏ đường dẫn trước thư mục gốc hoặc include_folder
      if [[ "$SEARCH_PATH" == "." ]]; then
        DISPLAY_FILE="${FILE#./}"
      else
        DISPLAY_FILE="${FILE#"$SEARCH_PATH/"}"
      fi
      echo "@${DISPLAY_FILE}>" >> "$OUTPUT_FILE"

      # Kiểm tra nếu file là SQL, không xóa nội dung
      if [[ "$FILE" == *.sql ]]; then
        cat "$FILE" >> "$OUTPUT_FILE"
      else
        tr -d '\n\r\t' < "$FILE" | \
        sed -E \
          -e 's|//.*$||' \
          -e 's|/\*[^*]*\*/||g' \
          -e 's|#.*$||' \
          -e 's|<!--[^>]*-->||g' \
          -e '/^[[:space:]]*$/d' \
          -e 's|[[:space:]]{2,}| |g' \
          -e 's|> +|>|g' \
          -e 's| +<|<|g' \
          -e 's|[[:space:]]+| |g' \
          -e 's| ,|,|g' \
          -e 's| ;|;|g' \
          -e 's| :|:|g' \
          -e 's|= |=|g' \
          -e 's| - |- |g' \
          -e 's|[[:space:]]+$||' >> "$OUTPUT_FILE"
      fi

      echo "|" >> "$OUTPUT_FILE"
  done
}

# Ghi phần Code Base cần review (tránh lặp)
process_directory "Code Base cần review" "." "Code Base"

# Ghi phần Reference Data nếu INCLUDE_FOLDERS có dữ liệu
for FOLDER in "${INCLUDE_FOLDERS[@]}"; do
  if [ -d "$FOLDER" ]; then
    echo "" >> "$OUTPUT_FILE"
    echo "=== Reference Data ===" >> "$OUTPUT_FILE"
    process_directory "Reference Data from $FOLDER" "$FOLDER" "Reference Data"
  else
    echo "" >> "$OUTPUT_FILE"
    echo "=== Reference Data ===" >> "$OUTPUT_FILE"
    echo "Thư mục bao gồm '$FOLDER' không tồn tại. Không có dữ liệu tham chiếu." >> "$OUTPUT_FILE"
  fi
done

# Tối ưu cuối cùng
echo -e "\n=== Tối ưu hóa kết quả ===" >> "$OUTPUT_FILE"
sed -i.bak 's/|[[:space:]]*|/|/g' "$OUTPUT_FILE" && rm "$OUTPUT_FILE.bak"

echo "=== Hoàn thành ===" >> "$OUTPUT_FILE"
echo "Kết quả đã được lưu vào: ${OUTPUT_FILE}"

echo "=== Script đã hoàn tất ==="
