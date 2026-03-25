# Branching And Deploy

## Branch Model

- `main`: nhánh tích hợp chính. Mọi thay đổi hoàn chỉnh sẽ được merge vào đây trước.
- `release/live`: nhánh phát hành production. Mỗi push lên nhánh này sẽ tự động chạy GitHub Actions và deploy lên live.
- `feat/*`: tính năng mới.
- `fix/*`: sửa lỗi.
- `chore/*`: công việc hạ tầng, CI, docs, housekeeping.
- `codex/*`: nhánh làm việc ngắn hạn do agent tạo.

## Flow Chuẩn

1. Tạo nhánh làm việc từ `main`.
2. Hoàn tất code, lint, smoke check.
3. Merge vào `main`.
4. Fast-forward hoặc merge chọn lọc từ `main` sang `release/live`.
5. Push `release/live` để GitHub Actions tự deploy production.

## Deploy Guardrails

- Không deploy production trực tiếp từ `main`.
- Không deploy production bằng FTP mirror.
- Production chỉ nhận code qua GitHub Actions trên `release/live`.
- `uploads`, `media`, `application/logs`, và `application/config/app-config.php` là dữ liệu/environment-owned, không bị ghi đè trong deploy.

## GitHub Secrets Cần Có

- `PROD_SSH_HOST`
- `PROD_SSH_USER`
- `PROD_SSH_KEY`
- `PROD_APP_ROOT`
- `PROD_BASE_URL`

## Ghi Chú Vận Hành

- Workflow build sẽ cài Composer dependencies trước khi đóng gói artifact.
- Workflow deploy sẽ upload artifact, giải nén vào thư mục tạm trên server, rồi `rsync` vào live root.
- Sau deploy luôn chạy smoke check với login page và admin redirect để bắt lỗi `500` sớm.
