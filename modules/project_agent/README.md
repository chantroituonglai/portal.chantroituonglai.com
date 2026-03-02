# Project Agent Module for Perfex CRM

## Overview
Project Agent là một module AI Assistant tập trung vào quản lý dự án, tạo ra trải nghiệm "chat-to-operate" cho chuỗi nghiệp vụ **Project → Task → Estimate/Invoice** trong Perfex CRM.

## Features

### 🎯 Core Features
- **AI Room Interface**: Chat-to-operate cho quản lý dự án
- **Action Registry**: 36 predefined actions an toàn và kiểm soát
- **Memory System**: Lưu trữ context và lịch sử tương tác
- **Context Builder**: Tự động thu thập ngữ cảnh dự án
- **Planner/Executor**: Lập kế hoạch và thực thi hành động
- **Dry-run Mode**: Mô phỏng kết quả trước khi thực thi

### 🔧 Technical Features
- **Integration Layer**: Tích hợp sâu với Perfex Core
- **Permission System**: Kiểm soát quyền truy cập chi tiết
- **Real-time Updates**: Cập nhật trạng thái thời gian thực
- **Multi-language Support**: Hỗ trợ tiếng Anh và tiếng Việt
- **Responsive Design**: Giao diện tương thích mọi thiết bị

## Memory Timeline (Panel)

- **Chức năng**: Hiển thị các "memory entries" mới nhất của phiên AI cho dự án hiện tại: câu hỏi của người dùng (input), trả lời của AI (ai_response), lời gọi hành động (action_call), kết quả hành động (action_result), và ghi chú hệ thống (system_note/note).
- **Mục đích**:
  - **Giữ ngữ cảnh** để AI trả lời chính xác hơn (AI có thể tham chiếu các input/decision trước đó).
  - **Audit/Debug**: xem lại đã hỏi gì và agent đã thực hiện gì.
  - **Tốc độ**: nhanh chóng rà soát tương tác gần nhất gắn với dự án này.
- **Phạm vi & Lưu trữ**:
  - **Theo user + project session** (bản ghi trong DB), **không phải tạm thời**.
  - Bảng: `tblproject_agent_sessions` (hoặc biến thể có tiền tố như `tbltblproject_agent_sessions`) và `tblproject_agent_memory_entries`.
  - Module tự động nhận diện tên bảng có tiền tố khác nhau (tbl/tbltbl) để tương thích nhiều cài đặt.
- **Cách populate**:
  - Khi **gửi chat**: lưu `kind = input` và `kind = ai_response`.
  - Khi **thực thi action**: lưu `kind = action_call` và `kind = action_result` (nếu có), kèm `params/result` trong `content_json`.
  - Mặc định hiển thị **mới nhất trước** (desc theo `created_at`).
- **Kiểu dữ liệu entry (khuyến nghị)**:
  - `input` — `{ text }`
  - `ai_response` — `{ text }`
  - `action_call` — `{ action_id, params }`
  - `action_result` — `{ action_id, success, result?, error? }`
  - `note|system_note` — `{ text }`

Gợi ý: nếu dùng trong trang Project (`admin/projects/view/{id}?group=project_agent`), panel sẽ tự tạo session nếu chưa có, sau khi gửi tin nhắn đầu tiên timeline sẽ hiển thị.

## System Requirements
- PHP >= 7.4
- MySQL/MariaDB
- Perfex CRM >= 2.3.0
- JavaScript enabled browser

### AI Provider Requirements
- **Recommended**: GeminiAI Module (by FHC) for best AI quality
- **Fallback**: OpenAI provider (Perfex CRM default)
- **Minimum**: Any AI provider registered in Perfex CRM AI system

## Installation

### 1. Copy Module
```bash
# Copy module to Perfex CRM modules directory
cp -r project_agent/ modules/
```

### 2. Install via Admin Panel
1. Truy cập Admin Panel → Setup → Modules
2. Tìm "Project Agent" và click Install
3. Module sẽ tự động cài đặt database và các thành phần cần thiết

### 3. Configure Permissions
1. Vào Setup → Staff → Roles
2. Cấu hình quyền cho Project Agent module
3. Phân quyền cho từng action theo role

### 4. Install GeminiAI Module (Recommended)
1. Download GeminiAI module from FHC
2. Copy to `modules/geminiai/` directory
3. Install via Admin Panel → Setup → Modules
4. Configure GeminiAI API key in Settings → AI
5. Project Agent will automatically detect and use GeminiAI

## Module Structure

```
modules/project_agent/
├── project_agent.php              # Main module file
├── install.php                    # Installation script
├── uninstall.php                  # Uninstall script
├── README.md                      # This file
├── controllers/
│   ├── Project_agent.php          # Main controller
│   ├── Project_agent_api.php      # API endpoints
│   └── Project_agent_actions.php  # Action handlers
├── models/
│   ├── Project_agent_model.php    # Main model
│   ├── Project_agent_memory_model.php
│   ├── Project_agent_action_model.php
│   └── Project_agent_session_model.php
├── views/
│   ├── ai_room.php               # Main AI Room interface
│   ├── plan_drawer.php           # Plan execution panel
│   ├── memory_timeline.php       # Memory/chat timeline
│   ├── context_panel.php         # Project context
│   └── execution_console.php     # Execution status
├── assets/
│   ├── css/
│   │   └── project_agent.css
│   └── js/
│       ├── project_agent.js
│       ├── ai_room.js
│       └── action_executor.js
├── helpers/
│   ├── project_agent_action_registry_helper.php
│   ├── project_agent_memory_helper.php
│   ├── project_agent_context_helper.php
│   └── project_agent_planner_helper.php
├── language/
│   ├── english/
│   │   └── project_agent_lang.php
│   └── vietnamese/
│       └── project_agent_lang.php
└── migrations/
    └── 001_create_project_agent_tables.php
```

## Database Schema

### Core Tables

#### AI Memory Sessions
```sql
CREATE TABLE `tblproject_agent_sessions` (
  `session_id` bigint PRIMARY KEY AUTO_INCREMENT,
  `project_id` bigint NULL,
  `user_id` bigint NOT NULL,
  `title` varchar(255) NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

#### Memory Entries (append-only, newest-first)
```sql
CREATE TABLE `tblproject_agent_memory_entries` (
  `entry_id` bigint PRIMARY KEY AUTO_INCREMENT,
  `session_id` bigint NOT NULL,
  `scope` ENUM('session','project','user','global') NOT NULL,
  `kind` ENUM(
    'input','context_snapshot','fact','assumption',
    'analysis_summary','decision','plan','action_call',
    'action_result','observation','warning','next_step',
    'state_summary','system_note'
  ) NOT NULL,
  `content_json` JSON NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `project_id` bigint NULL,
  `customer_id` bigint NULL,
  `entity_refs` JSON NULL,
  INDEX `idx_session_time_desc` (`session_id`, `created_at` DESC)
);
```

#### Action Registry
```sql
CREATE TABLE `tblproject_agent_actions` (
  `action_id` varchar(100) PRIMARY KEY,
  `name` varchar(255) NOT NULL,
  `description` text,
  `params_schema` JSON NOT NULL,
  `permissions` JSON NOT NULL,
  `risk_level` ENUM('low','medium','high') DEFAULT 'low',
  `requires_confirm` boolean DEFAULT FALSE,
  `is_active` boolean DEFAULT TRUE
);
```

#### Action Execution Logs
```sql
CREATE TABLE `tblproject_agent_action_logs` (
  `log_id` bigint PRIMARY KEY AUTO_INCREMENT,
  `session_id` bigint NOT NULL,
  `plan_id` varchar(100) NOT NULL,
  `run_id` varchar(100) NOT NULL,
  `action_id` varchar(100) NOT NULL,
  `params_json` JSON NOT NULL,
  `result_json` JSON NULL,
  `status` ENUM('queued','running','success','failed') NOT NULL,
  `error_message` text NULL,
  `executed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `executed_by` bigint NOT NULL,
  `client_token` varchar(255) NULL
);
```

## Action Registry (36 Predefined Actions)

### Project Management (7 actions)
1. `create_project` - Tạo dự án mới
2. `update_project` - Cập nhật thông tin dự án
3. `add_project_member` - Thêm thành viên dự án
4. `add_milestone` - Thêm mốc tiến độ
5. `link_invoice_to_project` - Liên kết hóa đơn với dự án
6. `get_project_finance_overview` - Tổng quan tài chính dự án
7. `invoice_project` - Lập hóa đơn cho dự án

### Task Management (7 actions)
8. `create_task` - Tạo task mới
9. `update_task` - Cập nhật task
10. `link_task_to` - Liên kết task với entity
11. `add_task_follower` - Thêm follower cho task
12. `start_task_timer` - Bắt đầu timer
13. `add_task_timesheet` - Thêm timesheet cho task
14. `bill_non_project_tasks_to_invoice` - Bill tasks không thuộc dự án

### Timesheet & Expense (5 actions)
15. `list_project_timesheets` - Danh sách timesheets
16. `invoice_timesheets` - Lập hóa đơn timesheets
17. `record_expense` - Ghi nhận chi phí
18. `set_recurring_expense` - Thiết lập chi phí định kỳ
19. `convert_expense_to_invoice` - Chuyển expense thành invoice

### Estimate & Invoice (11 actions)
20. `create_estimate` - Tạo báo giá
21. `send_estimate_email` - Gửi báo giá qua email
22. `convert_estimate_to_invoice` - Chuyển báo giá thành hóa đơn
23. `convert_estimate_to_project` - Tạo dự án từ báo giá
24. `create_estimate_request_form` - Tạo form yêu cầu báo giá
25. `create_invoice` - Tạo hóa đơn
26. `send_invoice_email` - Gửi hóa đơn qua email
27. `set_recurring_invoice` - Thiết lập hóa đơn định kỳ
28. `record_invoice_payment` - Ghi nhận thanh toán
29. `list_overdue_invoices` - Danh sách hóa đơn quá hạn
30. `invoice_project_expenses` - Lập hóa đơn chi phí dự án

### Analysis & Health Check (6 actions)
31. `check_billing_status` - Kiểm tra trạng thái billing
32. `find_unlinked_entities` - Tìm entities chưa liên kết
33. `autolink_entities` - Tự động liên kết entities
34. `bill_tasks_to_invoice` - Bill tasks vào hóa đơn
35. `create_reminder` - Tạo nhắc nhở
36. `summarize_project_work_remaining` - Tóm tắt công việc còn lại

## Usage Examples

### 1. Tạo Tasks từ Văn bản
```
User: "Dán yêu cầu khách hàng: làm landing page + blog, deadline tuần này"
AI: Phân tích → Tạo 6 tasks → Liên kết dependencies → Check billing
```

### 2. Kiểm tra Billing
```
User: "Check billing cho project ACME"
AI: Tổng hợp unbilled hours/expenses → Đề xuất tạo invoice
```

### 3. Nhắc việc còn thiếu
```
User: "Nhắc còn gì cần làm"
AI: Liệt kê overdue tasks, missing timesheets, upcoming milestones
```

## UI/UX Design

### AI Room Interface
- **3-column layout**: Context Panel | Conversation | Plan Drawer
- **Responsive design**: Tabs trên mobile
- **Real-time updates**: WebSocket cho live status
- **Dark/Light theme**: Tương thích theme Perfex

### Key Components
- **Context Panel**: Project overview, milestones, billing snapshot
- **Memory Timeline**: Chat history với filtering
- **Plan Drawer**: Action steps, diff preview, execution controls
- **Execution Console**: Progress tracking, retry/skip options

## Security & Permissions

### Permission Matrix
- **View**: Xem AI Room và memory entries
- **Execute Safe**: Thực thi actions không ghi tiền
- **Execute Financial**: Thực thi actions ghi tiền (cần confirm)
- **Admin**: Quản lý action registry và settings

### Security Features
- **Input validation**: JSON schema validation cho tất cả actions
- **Permission checks**: Kiểm tra quyền trước mỗi action
- **Audit logging**: Ghi log đầy đủ mọi thao tác
- **Data masking**: Ẩn thông tin nhạy cảm trong logs

## Configuration

### Settings
```php
// project_agent_settings table
- ai_room_enabled: boolean
- auto_confirm_threshold: integer (amount)
- memory_retention_days: integer
- max_concurrent_sessions: integer
- default_risk_level: enum
```

### Environment Variables
```bash
PROJECT_AGENT_DEBUG=false
PROJECT_AGENT_LOG_LEVEL=info
PROJECT_AGENT_MAX_MEMORY_ENTRIES=1000
```

## API Endpoints

### REST API
```
GET  /api/project_agent/sessions
POST /api/project_agent/sessions
GET  /api/project_agent/sessions/{id}/entries
POST /api/project_agent/sessions/{id}/entries
POST /api/project_agent/actions/{action_id}/execute
GET  /api/project_agent/actions
```

### WebSocket Events
```javascript
// Client events
'project_agent:join_session'
'project_agent:send_message'
'project_agent:execute_action'

// Server events
'project_agent:message_received'
'project_agent:action_completed'
'project_agent:status_update'
```

## Development Roadmap

### Phase 1: Foundation (2-3 weeks)
- [x] Module structure và database schema
- [x] Action Registry với 10 actions cơ bản
- [x] AI Room UI cơ bản
- [x] Integration với project view
- [x] AI Provider integration (GeminiAI + OpenAI fallback)

### Phase 2: Core Actions (3-4 weeks)
- [x] Implement 20 actions còn lại (37 total actions)
- [x] Memory system và context builder
- [x] Planner/Executor với dry-run
- [x] Execution console và status tracking

### Phase 3: Advanced Features (2-3 weeks)
- [ ] Advanced UI (diff viewer, parameter editor)
- [ ] Memory browser và filtering
- [ ] Bulk operations và templates
- [ ] Performance optimization

### Phase 4: Polish & Testing (1-2 weeks)
- [ ] Comprehensive testing
- [ ] Documentation
- [ ] Performance tuning
- [ ] Security audit

## Troubleshooting

### Common Issues

#### 1. Actions không thực thi
```bash
# Kiểm tra permissions
- Vào Setup → Staff → Roles
- Đảm bảo role có quyền "Execute" cho action

# Kiểm tra logs
tail -f /var/log/perfex/project_agent.log
```

#### 2. Memory entries không hiển thị
```sql
-- Kiểm tra database
SELECT COUNT(*) FROM tblproject_agent_memory_entries WHERE session_id = ?;
```

#### 3. Performance issues
```php
// Tăng memory limit
ini_set('memory_limit', '512M');

// Tối ưu database queries
EXPLAIN SELECT * FROM tblproject_agent_memory_entries;
```

## Contributing

### Development Setup
1. Fork repository
2. Create feature branch: `git checkout -b feature/new-action`
3. Implement changes
4. Add tests
5. Submit pull request

### Code Standards
- PSR-4 autoloading
- PSR-12 coding style
- PHPDoc comments
- Unit tests cho actions
- Integration tests cho UI

## License
This module is licensed under the same license as Perfex CRM.

## Support
- **Documentation**: [Wiki](https://github.com/your-repo/project-agent/wiki)
- **Issues**: [GitHub Issues](https://github.com/your-repo/project-agent/issues)
- **Discussions**: [GitHub Discussions](https://github.com/your-repo/project-agent/discussions)

## Changelog

### v1.0.0 (Planned)
- Initial release
- 36 predefined actions
- AI Room interface
- Memory system
- Action registry
- Integration với Perfex core

---

**Project Agent** - Transform Perfex CRM into an intelligent project management platform with AI-powered assistance.
