<?php
defined('BASEPATH') or exit('No direct script access allowed');

# Menu items
$lang['topics'] = 'Chủ đề';
$lang['topics_title'] = 'Quản Lý Topics';
$lang['topics_list'] = 'Danh Sách Topics';
$lang['action_types'] = 'Loại Hành Động';
$lang['action_states'] = 'Trạng Thái';
$lang['topics_management'] = 'Topics';
$lang['topic_list'] = 'DS Topics';
$lang['topics_dashboard'] = 'Nhật ký thực thi';
$lang['topic_master_record'] = 'Bản ghi chính';
$lang['topic_status'] = 'Trạng thái';
$lang['topic_state_color'] = 'Màu sắc';
$lang['active_topics'] = 'Topics đang hoạt động';

# Tổng quan
$lang['topics_overview'] = 'Tổng quan Topics';
$lang['topics_overview_subtitle'] = 'Trang giới thiệu tổng quan tính năng, mô hình dữ liệu, quan hệ và tích hợp của module.';
$lang['topics_overview_scope'] = 'Phạm vi';
$lang['topics_overview_scope_lifecycle'] = 'Quản lý toàn bộ vòng đời: Topic Master → Topic Instances → Action Types/States → Targets → Controllers → Logs/External Data';
$lang['topics_overview_scope_automation'] = 'Thiết kế ưu tiên tự động hóa với N8N, Action Buttons (webhook/native), logs và settings';
$lang['topics_overview_scope_realtime'] = 'Trải nghiệm realtime: theo dõi online nhân viên và thông báo Pusher';
$lang['topics_overview_scope_ui'] = 'Giao diện quản trị: danh sách, chi tiết, dashboard, lọc, thao tác hàng loạt, copy Topic ID';
$lang['topics_overview_features'] = 'Tính năng chính';
$lang['topics_overview_features_topics'] = 'Bản ghi topic phong phú với data/log, gắn loại/trạng thái, target, automation id';
$lang['topics_overview_features_types_states'] = 'Action Types phân cấp; Action States có màu/thứ tự/tính hợp lệ';
$lang['topics_overview_features_buttons'] = 'Action Buttons cấu hình để kích hoạt workflow hoặc tác vụ native, có luật include/ignore';
$lang['topics_overview_features_online'] = 'Theo dõi online theo topic và chấm trạng thái trên avatar';
$lang['topics_overview_features_notifications'] = 'Thông báo realtime qua Pusher';
$lang['topics_overview_features_processors'] = 'Bộ xử lý mở rộng: WordPress, Google Sheets raw, Social post, Draft writing, Image generation, Topic composer';
$lang['topics_overview_features_dashboard'] = 'Dashboard vận hành và lịch sử với bộ lọc và hiển thị processed-data';
$lang['topics_overview_features_assets'] = 'Tối ưu tài nguyên và sửa JSON tự động';
$lang['topics_overview_data_model'] = 'Mô hình dữ liệu & Quan hệ';
$lang['topics_overview_integrations'] = 'Tích hợp';
$lang['topics_overview_integrations_n8n'] = 'N8N: webhook/host URLs, link workflow/execution, seed workflow mặc định';
$lang['topics_overview_integrations_pusher'] = 'Pusher: kênh thông báo theo nhân viên';
$lang['topics_overview_integrations_wp_social'] = 'WordPress & Social qua processors và credentials của Controllers';
$lang['topics_overview_permissions_settings'] = 'Phân quyền & Cài đặt';
$lang['topics_overview_permissions'] = 'Quyền: topics, topics_automation, topic_action_buttons, topic_controllers';
$lang['topics_overview_settings'] = 'Tùy chọn: online tracking/timeout, debug panel, N8N host/webhook/api key, enable buttons/controllers, default workflow';
$lang['topics_overview_navigation'] = 'Điều hướng';
$lang['topics_overview_nav_pages'] = 'Trang: list, detail, dashboard, CRUD types/states/buttons, topic master, controllers';
$lang['topics_overview_nav_assets'] = 'Tài nguyên: topics.css, jsonrepair.min.js, preload language, guard circleProgress';
$lang['topics_overview_notes'] = 'Lưu ý: Có chênh lệch schema (ví dụ: valid_data ở action states xuất hiện trong reference SQL).';

# Topics
$lang['topic'] = 'Chủ đề';
$lang['new_topic'] = 'Thêm chủ đề mới';
$lang['edit_topic'] = 'Sửa chủ đề';
$lang['delete_topic'] = 'Xóa chủ đề';
$lang['topic_id'] = 'Mã chủ đề';
$lang['topic_title'] = 'Tiêu đề';
$lang['topic_detail'] = 'Chi tiết chủ đề';
$lang['add_topic'] = 'Thêm Mới Topic';
$lang['log'] = 'Ghi chú';

# Action Types
$lang['action_type'] = 'Loại hành động';
$lang['new_action_type'] = 'Thêm Loại Hành Động';
$lang['edit_action_type'] = 'Sửa Hành Động';
$lang['delete_action_type'] = 'Xóa Loại Hành Động';
$lang['action_type_name'] = 'Tên Loại Hành Động';
$lang['action_type_id'] = 'Mã Loại Hành Động';
$lang['add_new_action_type'] = 'Thêm Loại Hành Động Mới';
$lang['action_type_detail'] = 'Chi Tiết Loại Hành Động';

# Action States
$lang['action_state'] = 'Trạng thái';
$lang['new_action_state'] = 'Thêm Trạng Thái';
$lang['edit_action_state'] = 'Sửa Trạng Thái';
$lang['delete_action_state'] = 'Xóa Trạng Thái';
$lang['action_state_name'] = 'Tên Trạng Thái';
$lang['action_state_id'] = 'Mã Trạng Thái';
$lang['add_new_action_state'] = 'Thêm Trạng Thái Mới';
$lang['action_state_detail'] = 'Chi Tiết Trạng Thái';
$lang['action_state_color'] = 'Màu sắc trạng thái';
$lang['valid_data'] = 'Dữ liệu chuẩn';
$lang['standard_data'] = 'Dữ liệu chuẩn';

# Others
$lang['select_action_type'] = 'Chọn Loại Hành Động';
$lang['select_action_state'] = 'Chọn Trạng Thái';
$lang['none'] = 'Không có';
$lang['options'] = 'Tùy chọn';
$lang['back'] = 'Quay Lại';
$lang['created_date'] = 'Ngày Tạo';
$lang['updated_date'] = 'Ngày Cập Nhật';
$lang['submit'] = 'Lưu Lại';
$lang['cancel'] = 'Hủy Bỏ';
$lang['dropdown_non_selected_tex'] = 'Chưa chọn';

# Messages
$lang['topic_added'] = 'Thêm Topic thành công';
$lang['topic_updated'] = 'Cập nhật Topic thành công';
$lang['topic_deleted'] = 'Xóa Topic thành công';
$lang['action_type_added'] = 'Thêm Loại Hành Động thành công';
$lang['action_type_updated'] = 'Cập nhật Loại Hành Động thành công';
$lang['action_type_deleted'] = 'Xóa Loại Hành Động thành công';
$lang['action_state_added'] = 'Thêm Trạng Thái thành công';
$lang['action_state_updated'] = 'Cập nhật Trạng Thái thành công';
$lang['action_state_deleted'] = 'Xóa Trạng Thái thành công'; 

$lang['action_type_code'] = 'Mã loại hành động';
$lang['action_state_code'] = 'Mã trạng thái';
$lang['back_to_list'] = 'Quay lại danh sách';

# Add these new strings
$lang['ngày_tạo'] = 'Ngày tạo';
$lang['ngày_cập_nhật'] = 'Ngày cập nhật';
$lang['action_states_list'] = 'Danh sách trạng thái';
$lang['action_types_list'] = 'Danh sách loại hành động';
$lang['action_state_states'] = 'Các trạng thái';
$lang['action_type_states'] = 'Các trạng thái của loại hành động';
$lang['action_state_updated'] = 'Cập nhật trạng thái thành công';
$lang['action_type_updated'] = 'Cập nhật loại hành động thành công';

$lang['search_topics'] = 'Tìm kiếm chủ đề...';
$lang['type_to_search'] = 'Nhp để tìm kiếm...';
$lang['log_details'] = 'Chi tiết ghi chú';
$lang['view_log'] = 'Xem ghi chú';
$lang['positions_updated'] = 'Vị trí cập nhật thành công';

# Dashboard Stats
$lang['total_topics'] = 'Tổng số Topics';
$lang['writing_topics'] = 'Topics đang viết';
$lang['social_audit_topics'] = 'Topics Social Audit';
$lang['scheduled_social_topics'] = 'Topics đã lên lịch Social';
$lang['post_audit_gallery_topics'] = 'Topics Post Audit Gallery';
$lang['last_update'] = 'Cập nhật cuối';
$lang['quick_stats'] = 'Thống kê nhanh';
$lang['topics_by_state'] = 'Topics theo trạng thái';
$lang['view_all'] = 'Xem tất cả';
$lang['filter_by_state'] = 'Lọc theo trạng thái';

# States  
$lang['state_writing'] = 'Đang viết';
$lang['state_social_audit'] = 'Social Audit';
$lang['state_scheduled_social'] = 'Đã lên lịch Social';
$lang['state_post_audit_gallery'] = 'Post Audit Gallery';

# Thêm các chuỗi còn thiếu
$lang['clear_filters'] = 'Xóa bộ lọc';
$lang['export'] = 'Xuất dữ liệu';
$lang['active_filters'] = 'Bộ lọc đang áp dụng';
$lang['all'] = 'Tất cả';
$lang['options'] = 'Tùy chọn';
$lang['active'] = 'Hoạt động';
$lang['inactive'] = 'Không hoạt động';
$lang['toggle_active'] = 'Chuyển trng thái';
$lang['quick_actions'] = 'Thao tác nhanh';
$lang['quick_activate'] = 'Kích hoạt nhanh';
$lang['quick_deactivate'] = 'Vô hiệu hóa nhanh';
$lang['selected_items'] = 'mục đã chọn';
$lang['bulk_action_success'] = 'Các chủ đề đã được cập nhật thành công';
$lang['bulk_action_failed'] = 'Không thể cập nhật các chủ đề đã chọn';
$lang['no_items_selected'] = 'Chưa chọn mục nào';
$lang['invalid_request'] = 'Yêu cầu không hợp lệ';
$lang['confirm_action'] = 'Bạn có chắc chắn muốn thực hiện hành động này?';
$lang['invalid_action'] = 'Hành động không hợp lệ';
$lang['invalid_data'] = 'Định dạng dữ liệu không hợp lệ';
$lang['activate'] = 'Kích hoạt';
$lang['deactivate'] = 'Vô hiệu hóa';
$lang['bulk_action_confirmation'] = 'Bạn có chắc chắn muốn thực hiện hành động này với các mục đã chọn?';
$lang['confirm'] = 'Xác nhận';
$lang['close'] = 'Đóng';
$lang['copied_to_clipboard'] = 'Đã sao chép vào clipboard';

# Thêm các strings mới
$lang['parent_action_type'] = 'Loại Hành Động Cha';
$lang['action_type_cannot_be_own_parent'] = 'Không thể chọn chính nó làm loại cha';
$lang['action_type_circular_reference'] = 'Không thể tạo tham chiếu vòng trong quan hệ cha-con';
$lang['no_parent'] = 'Không Có Loại Cha';
$lang['parent_type'] = 'Loại Cha';
$lang['child_types'] = 'Loại Con';
$lang['has_children'] = 'Có Loại Con';
$lang['is_child_of'] = 'Là Con Của';
$lang['parent_details'] = 'Chi Tiết Loại Cha';
$lang['child_details'] = 'Chi Tiết Loại Con';
$lang['select_parent_type'] = 'Chọn Loại Cha';
$lang['parent_type_changed'] = 'Đã thay đổi loại cha thành công';
$lang['parent_type_remove'] = 'Xóa Loại Cha';
$lang['confirm_remove_parent'] = 'Bạn có chắc chắn muốn xóa loại cha?';
$lang['parent_type_removed'] = 'Đã xóa loại cha thành công';
$lang['invalid_parent_type'] = 'Lựa chọn loại cha không hợp lệ';
$lang['parent_child_relation'] = 'Quan Hệ Cha-Con';

$lang['reposition'] = 'Reposition';
$lang['save_positions'] = 'Lưu vị trí';
$lang['positions_updated'] = 'Vị trí cập nhật thành công';
$lang['error_updating_positions'] = 'Lỗi cập nhật vị trí';

# Filter related
$lang['filter_by_state'] = 'Lọc theo trạng thái';
$lang['filter_by_type'] = 'Lọc theo loại';
$lang['search_mode'] = 'Chế độ tìm kiếm';
$lang['search_mode_or'] = 'HOẶC';
$lang['search_mode_and'] = 'VÀ';
$lang['no_filters_applied'] = 'Chưa áp dụng bộ lọc';
$lang['filter_applied'] = 'Đã áp dụng bộ lọc';
$lang['filters_applied'] = 'Đã áp dụng các bộ lọc';

# Topic Target
$lang['topic_targets'] = 'Mục tiêu Topic';
$lang['topic_target'] = 'Mục tiêu';
$lang['new_topic_target'] = 'Thêm mục tiêu mới';
$lang['edit_topic_target'] = 'Sửa mục tiêu';
$lang['delete_topic_target'] = 'Xóa mục tiêu';
$lang['topic_target_name'] = 'Tên mục tiêu';
$lang['topic_target_description'] = 'Mô tả';
$lang['topic_target_value'] = 'Giá trị';
$lang['topic_target_type'] = 'Loại mục tiêu';
$lang['topic_target_status'] = 'Trạng thái';
$lang['existing_targets'] = 'Danh sách mục tiêu';
$lang['topic_target_type_help'] = 'Nhập loại mục tiêu bằng chữ in hoa, ví dụ: CONTENT, SOCIAL';
$lang['topic_target_exists'] = 'Loại mục tiêu này đã tồn tại';
$lang['topic_target_added'] = 'Thêm mục tiêu thành công';
$lang['topic_target_updated'] = 'Cập nhật mục tiêu thành công';
$lang['topic_target_deleted'] = 'Xóa mục tiêu thành công';
$lang['confirm_edit_target'] = 'Mục tiêu này đã tồn tại. Bạn có muốn chỉnh sửa không?';

$lang['standard_data'] = 'Dữ liệu chuẩn';

# Thêm vào cuối file
$lang['total_topic_masters'] = 'Tổng số Topic Master';
$lang['active_topic_masters'] = 'Topic Master đang hoạt động';
$lang['active_masters'] = 'Topic Master đang hoạt động';

# Từ views/action_types/edit.php
$lang['positions_updated_successfully'] = 'Vị trí đã được cập nhật thành công';
$lang['error_loading_log_data'] = 'Lỗi tải dữ liệu log';
$lang['failed_to_load_log_data'] = 'Không thể tải dữ liệu log';
$lang['collapse_all'] = 'Thu gọn tất cả';
$lang['expand_all'] = 'Mở rộng tất cả';

# Từ views/detail.php
$lang['error_parsing_response'] = 'Lỗi xử lý phản hồi';
$lang['error_loading_log'] = 'Lỗi tải log';

# Từ views/action_types/index.php
$lang['reposition_items'] = 'Sắp xếp lại vị trí';
$lang['save_positions'] = 'Lưu vị trí';
$lang['no_history_found'] = 'Không tìm thấy lịch sử';
$lang['view_log'] = 'Xem log';
$lang['log_details'] = 'Chi tiết log';
$lang['bulk_action_confirmation_msg'] = 'Bạn có chắc chắn muốn thực hiện hành động này với các mục đã chọn?';
$lang['activate_all'] = 'Kích hoạt tất cả';
$lang['deactivate_all'] = 'Vô hiệu hóa tất cả';
$lang['topic_histories'] = 'Lịch sử chủ đề';

# Process Data Related
$lang['process_data'] = 'Xử lý dữ liệu';
$lang['last_backup'] = 'Sao lưu gần nhất';
$lang['quick_save'] = 'Lưu nhanh';
$lang['save'] = 'Lưu';
$lang['status'] = 'Trạng thái';
$lang['position'] = 'Vị trí';
$lang['title'] = 'Tiêu đề';
$lang['content'] = 'Nội dung';
$lang['data_processed_successfully'] = 'Xử lý dữ liệu thành công';
$lang['data_processing_failed'] = 'Xử lý dữ liệu thất bại';
$lang['no_processor_found'] = 'Không tìm thấy bộ xử lý dữ liệu cho loại hành động này';
$lang['backup_creation_failed'] = 'Tạo bản sao lưu thất bại';
$lang['no_data_to_process'] = 'Không có dữ liệu để xử lý';
$lang['missing_required_fields'] = 'Thiếu các trường bắt buộc';

# Quick Save Related
$lang['item_saved_successfully'] = 'Lưu mục thành công';
$lang['save_failed'] = 'Lưu thất bại';
$lang['ajax_error'] = 'Lỗi Ajax';

$lang['sort_items'] = 'Sắp xếp mục';
$lang['sort_items_title'] = 'Sắp xếp các mục Topic';
$lang['positions_saved'] = 'Đã lưu vị trí thành công';
$lang['error_saving_positions'] = 'Lỗi khi lưu vị trí';

# Process Data Related
$lang['view_processed_data'] = 'Xem dữ liệu đã xử lý';
$lang['view_execution'] = 'Xem thực thi';
$lang['view_workflow'] = 'Xem Workflow';
$lang['processed_data_details'] = 'Chi tiết dữ liệu đã xử lý';
$lang['loading'] = 'Đang tải...';
$lang['no_specific_handler'] = 'Không có trình xử lý cho loại mục tiêu:';
$lang['open_in_new_tab'] = 'Mở trong tab mới';
$lang['no_image_data'] = 'Không tìm thấy dữ liệu hình ảnh';
$lang['refresh'] = 'Làm mới';

# Log Related
$lang['log_data_not_found'] = 'Không tìm thấy dữ liệu log';
$lang['all_topics_activated'] = 'Tất cả các topic đã được kích hoạt';
$lang['all_topics_deactivated'] = 'Tất cả các topic đã bị vô hiệu hóa';
$lang['selected_topics_activated'] = 'Các topic đã chọn đã được kích hoạt';
$lang['selected_topics_deactivated'] = 'Các topic đã chọn đã bị vô hiệu hóa';
$lang['process_data_reset_success'] = 'Đặt lại dữ liệu xử lý thành công';
$lang['failed_to_load_processed_data'] = 'Không thể tải dữ liệu đã xử lý';
$lang['topic_not_found'] = 'Không tìm thấy topic';
$lang['failed_to_parse_data'] = 'Không thể phân tích dữ liệu JSON';

# Thêm các chuỗi mới
$lang['error_processing_topic_data'] = 'Lỗi xử lý dữ liệu topic';
$lang['topic_id_required'] = 'Yêu cầu ID topic';
$lang['invalid_json_data'] = 'Định dạng dữ liệu JSON không hợp lệ';
$lang['topic_data_updated'] = 'Dữ liệu topic đã được cập nhật';
$lang['enable_online_tracking'] = 'Kích hoạt theo dõi online';
$lang['online_timeout_seconds'] = 'Thời gian timeout online (giây)';
$lang['settings_updated'] = 'Cập nhật cài đặt thành công';

# Settings
$lang['topics_settings'] = 'Cài đặt Topics';
$lang['topics_online_tracking_enabled'] = 'Kích hoạt theo dõi online';
$lang['topics_online_timeout'] = 'Thời gian timeout online (giây)';
$lang['topics_online_timeout_desc'] = 'Thời gian trong giây trước khi người dùng được xem là offline (mặc định: 900 giây = 15 phút)';

# N8n Settings
$lang['settings_n8n_host']           = 'URL máy chủ N8n';
$lang['settings_n8n_host_info']      = 'URL cơ sở của máy chủ n8n (ví dụ: https://n8n.yourdomain.com)';
$lang['settings_n8n_api_key']        = 'API Key của N8n';
$lang['settings_n8n_api_key_info']   = 'API key để truy cập trực tiếp vào API của n8n';
$lang['settings_enable_automation']   = 'Bật tự động hóa Workflow';
$lang['settings_enable_automation_info'] = 'Bật/tắt tính năng tự động hóa workflow';

# Add new language items
$lang['topic_action_buttons'] = 'Nút hành động Topic';
$lang['button_type'] = 'Loại nút';
$lang['workflow_id'] = 'ID Workflow';
$lang['target_action_type'] = 'Loại hành động mục tiêu';
$lang['target_action_state'] = 'Target Action State';
$lang['add_button'] = 'Add Button';
$lang['workflow_executed_successfully'] = 'Workflow thực thi thành công';
$lang['workflow_execution_failed'] = 'Không thể thực thi Workflow';
$lang['select_an_option'] = 'Chọn một tùy chọn';
$lang['new_action_button'] = 'Thêm nút hành động';
$lang['add_action_button'] = 'Thêm nút hành động';
$lang['processed_values'] = 'Các giá trị đã xử lý';
$lang['action_buttons'] = 'Nút hành động';
$lang['load_from_history'] = 'Tải từ lịch sử';

# Action Buttons
$lang['action_buttons'] = 'Nút hành động';
$lang['action_button'] = 'Nút hành động';
$lang['edit_action_button'] = 'Sửa nút hành động';
$lang['delete_action_button'] = 'Xóa nút hành động';
$lang['button_name'] = 'Tên nút';
$lang['button_description'] = 'Mô tả';
$lang['button_order'] = 'Thứ tự';
$lang['button_status'] = 'Trạng thái';
$lang['trigger_type'] = 'Loại kích hoạt';

# Action Button Messages
$lang['action_button_added'] = 'Thêm nút hành động thành công';
$lang['action_button_updated'] = 'Cập nhật nút hành động thành công';
$lang['action_button_deleted'] = 'Xóa nút hành động thành công';
$lang['action_button_exists'] = 'Nút hành động với tên này đã tồn tại';
$lang['confirm_delete_action_button'] = 'Bạn có chắc chắn muốn xóa nút hành động này?';

# Action Button Types
$lang['button_type_workflow'] = 'Workflow';
$lang['button_type_custom'] = 'Tùy chỉnh';
$lang['button_type_api'] = 'API';

# Action Button States
$lang['button_enabled'] = 'Đã kích hoạt';
$lang['button_disabled'] = 'Đã vô hiệu hóa';
$lang['deactivated_buttons'] = 'Các nút đã vô hiệu hóa';
$lang['no_execution_results'] = 'Không có kết quả thực thi';
$lang['error_loading_history'] = 'Lỗi khi tải lịch sử';
$lang['error_parsing_log_data'] = 'Lỗi khi phân tích dữ liệu log';
$lang['save_and_execute'] = 'Lưu & Thực thi';
$lang['execution_results'] = 'Kết quả thực thi';
$lang['select_processed_values'] = 'Chọn giá trị đã xử lý';
$lang['ignore_types'] = 'Bỏ qua loại hành động';
$lang['ignore_states'] = 'Bỏ qua trạng thái';

# Workflow Execution
$lang['execution_successful'] = 'Thực thi thành công';
$lang['execution_failed'] = 'Thực thi thất bại';
$lang['executing_workflow'] = 'Đang thực thi workflow...';
$lang['execution_results'] = 'Kết quả thực thi';
$lang['execution_results_help'] = 'Hiển thị kết quả thực thi workflow gần nhất';
$lang['no_execution_results'] = 'Không có kết quả thực thi nào';
$lang['step'] = 'Bước';
$lang['clear'] = 'Xóa';

# N8N Integration Messages
$lang['n8n_request_successful'] = 'Yêu cầu N8N thực thi thành công';
$lang['n8n_request_failed'] = 'Không thể thực thi yêu cầu N8N';
$lang['n8n_settings_missing'] = 'Thiếu cài đặt tích hợp N8N';
$lang['workflow_id_missing'] = 'Thiếu ID workflow';
$lang['no_history_data_found'] = 'Không tìm thấy dữ liệu lịch sử';
$lang['link_not_found_in_history'] = 'Không tìm thấy liên kết trong dữ liệu lịch sử';
$lang['action_processing_error'] = 'Đã xảy ra lỗi khi xử lý hành động';
$lang['show_topic_id'] = 'Hiển Topic ID';
$lang['hide_topic_id'] = 'Ẩn Topic ID';
$lang['progress_overview'] = 'Tiến độ tổng quát';
$lang['wordpress_url_found'] = 'Đã tìm thấy URL WordPress';
$lang['wordpress_url_not_found'] = 'Không tìm thấy URL WordPress';

# Thêm các strings mới
$lang['select_fanpage'] = 'Chọn Fanpage';
$lang['select_fanpage_and_post_type'] = 'Chọn Fanpage và loại bài đăng';
$lang['post_type'] = 'Loại bài đăng';
$lang['post_type_image'] = 'Bài đăng hình ảnh';
$lang['post_type_link'] = 'Bài đăng liên kết';
$lang['post_type_carousel'] = 'Bài đăng carousel';
$lang['post_type_slider'] = 'Bài đăng slider';
$lang['error_executing_workflow'] = 'Lỗi thực thi workflow';
$lang['error_loading_history'] = 'Lỗi tải lịch sử';
$lang['wordpress_post_created'] = 'Đã tạo bài viết WordPress';

# Controllers
$lang['topic_controllers'] = 'Bộ điều khiển';
$lang['new_controller'] = 'Thêm mới bộ điều khiển';
$lang['controller_details'] = 'Chi tiết bộ điều khiển';
$lang['controller'] = 'Bộ điều khiển';

# Controller Fields
$lang['site'] = 'Trang web';
$lang['platform'] = 'Nền tảng';
$lang['blog_id'] = 'ID Blog';
$lang['logo_url'] = 'URL Logo';
$lang['slogan'] = 'Khẩu hiệu';
$lang['writing_style'] = 'Phong cách viết';
$lang['emails'] = 'Emails';
$lang['api_token'] = 'Token API';
$lang['project_id'] = 'ID Dự án';
$lang['seo_task_sheet_id'] = 'ID Sheet nhiệm vụ SEO';
$lang['action_1'] = 'Hành động 1';
$lang['action_2'] = 'Hành động 2';
$lang['page_mapping'] = 'Ánh xạ trang';

# Related Topics
$lang['related_topics'] = 'Chủ đề liên quan';
$lang['no_related_topics'] = 'Không tìm thấy chủ đề liên quan';

# Controllers
$lang['edit_controller'] = 'Sửa bộ điều khiển';
$lang['controller_added'] = 'Thêm bộ điều khiển thành công';
$lang['controller_updated'] = 'Cập nhật bộ điều khiển thành công';
$lang['controller_deleted'] = 'Xóa bộ điều khiển thành công';
$lang['confirm_delete_controller'] = 'Bạn có chắc chắn muốn xóa bộ điều khiển này?';

$lang['no_topics_selected'] = 'No topics selected';
$lang['topic_selected'] = 'topic đã chọn';
$lang['topics_selected'] = 'topics đã chọn';


$lang['topics_added_successfully'] = 'Thêm topic thành công';
$lang['topics_add_failed'] = 'Không thể thêm topic';
$lang['no_topics_selected'] = 'Không có topic nào được chọn';
$lang['confirm_remove_topics'] = 'Bạn có chắc chắn muốn xóa các topic đã chọn?';
$lang['topics_removed_successfully'] = 'Xóa topic thành công';
$lang['topics_remove_failed'] = 'Không thể xóa topic'; 
$lang['add_topics'] = 'Thêm topic'; 

# Controllers Additional Strings
$lang['controller_name'] = 'Tên bộ điều khiển';
$lang['controller_description'] = 'Mô tả bộ điều khiển';
$lang['controller_type'] = 'Loại bộ điều khiển';
$lang['controller_settings'] = 'Cài đặt bộ điều khiển';
$lang['controller_status'] = 'Trạng thái bộ điều khiển';
$lang['manage_topics'] = 'Quản lý topics';
$lang['assign_topics'] = 'Gán topics';
$lang['remove_topics'] = 'Xóa topics';
$lang['no_available_topics'] = 'Không có topics khả dụng';
$lang['search_available_topics'] = 'Tìm topics khả dụng...';
$lang['topics_assignment'] = 'Gán topics';
$lang['confirm_topic_assignment'] = 'Xác nhận gán topics';
$lang['topics_assignment_success'] = 'Gán topics thành công';
$lang['topics_assignment_failed'] = 'Gán topics thất bại';
$lang['controller_not_found'] = 'Không tìm thấy bộ điều khiển';
$lang['invalid_controller'] = 'Bộ điều khiển không hợp lệ';

$lang['remove_selected_topics'] = 'Xóa topics đã chọn';

# Confirm Dialog Messages
$lang['confirm_title'] = 'Xác nhận';
$lang['confirm_remove_topics'] = 'Bạn có chắc chắn muốn xóa các topic đã chọn khỏi bộ điều khiển này?';
$lang['confirm_add_topics'] = 'Bạn có chắc chắn muốn thêm các topic đã chọn vào bộ điều khiển này?';
$lang['confirm_delete_controller'] = 'Bạn có chắc chắn muốn xóa bộ điều khiển này?';
$lang['confirm_reset_controller'] = 'Bạn có chắc chắn muốn đặt lại bộ điều khiển về trạng thái ban đầu?';
$lang['confirm_bulk_action'] = 'Bạn có chắc chắn muốn thực hiện hành động này với {count} topic đã chọn?';

$lang['topic_activated'] = 'Topic activated';
$lang['topic_deactivated'] = 'Topic deactivated';
$lang['add_topics_to_controller'] = 'Thêm topic vào bộ điều khiển';

$lang['ignore_action_types'] = 'Bỏ qua loại hành động';
$lang['ignore_action_states'] = 'Bỏ qua trạng thái hành động';

$lang['fail_topics'] = 'Fail Topics';

# Post Type Selection
$lang['select_post_type'] = 'Chọn loại bài viết';
$lang['available_options'] = 'Tùy chọn khả dụng';
$lang['top_list_post'] = 'Bài viết dạng danh sách';
$lang['top_list_post_desc'] = 'Tạo bài viết với định dạng danh sách có đánh số';
$lang['article_post'] = 'Bài viết dạng bài báo';
$lang['article_post_desc'] = 'Tạo bài viết với định dạng bài báo tiêu chuẩn';
$lang['please_select_option'] = 'Vui lòng chọn một tùy chọn';

# Post Options
$lang['audit_post'] = 'Xem & Audit bài viết';
$lang['audit_post_desc'] = 'Xem xét và kiểm tra nội dung bài viết hiện có';
$lang['write_content'] = 'Viết nội dung';
$lang['write_content_desc'] = 'Tạo nội dung mới cho bài viết';
$lang['write_social'] = 'Viết Social & Meta';
$lang['write_social_desc'] = 'Tạo nội dung mạng xã hội và mô tả meta';

# Add new language strings
$lang['topic_needs_controller'] = 'Topic cần được gán vào một controller trước khi tiếp tục';
$lang['select_controller'] = 'Chọn Controller';
$lang['add_to_controller'] = 'Thêm vào Controller';
$lang['topic_added_to_controller_success'] = 'Đã thêm topic vào controller thành công';
$lang['topic_added_to_controller_failed'] = 'Không thể thêm topic vào controller';

$lang['controller_info'] = 'Thông tin Controller';

$lang['view_controller'] = 'Xem Controller';
$lang['toggle_details'] = 'Ẩn/Hiện Chi Tiết';

# Controller related strings
$lang['add_controller'] = 'Thêm Controller';
$lang['search_controller'] = 'Tìm Controller';
$lang['no_controller_assigned'] = 'Topic chưa được gán controller';
$lang['select'] = 'Chọn';
$lang['active'] = 'Hoạt động';
$lang['inactive'] = 'Không hoạt động';
$lang['site'] = 'Website';
$lang['platform'] = 'Nền tảng';
$lang['blog_id'] = 'Blog ID';
$lang['logo_url'] = 'URL Logo';
$lang['project_id'] = 'ID Dự án';
$lang['seo_task_sheet_id'] = 'ID Sheet SEO';
$lang['emails'] = 'Emails';
$lang['api_token'] = 'Token API';
$lang['page_mapping'] = 'Mapping trang';
$lang['datecreated'] = 'Ngày tạo';
$lang['dateupdated'] = 'Ngày cập nhật';
$lang['slogan'] = 'Slogan';
$lang['writing_style'] = 'Phong cách viết';
$lang['action_1'] = 'Hành động 1';
$lang['action_2'] = 'Hành động 2';

$lang['select_wordpress_post_options'] = 'Chọn các tùy chọn bài viết WordPress';

$lang['preview_post'] = 'Xem trước bài viết';
$lang['post_id'] = 'ID bài viết';

$lang['generate_images'] = 'Tạo hình ảnh';
$lang['generating'] = 'Đang tạo hình ảnh...';
$lang['image_generation_started'] = 'Quá trình tạo hình ảnh đã được khởi động';
$lang['image_generation_failed'] = 'Không thể khởi động quá trình tạo hình ảnh';

$lang['image_generation_listening'] = 'Đang lắng nghe kết quả tạo ảnh...';
$lang['image_generation_initiated'] = 'Đã bắt đầu tạo ảnh';
$lang['image_generation_completed'] = 'Hoàn thành tạo ảnh';
$lang['image_generation_completed_no_data'] = 'Hoàn thành tạo ảnh nhưng không nhận được dữ liệu';

$lang['workflow_check_failed'] = 'Không thể kiểm tra trạng thái workflow';
$lang['workflow_info_missing'] = 'Thiếu thông tin workflow';

$lang['workflow_data_missing'] = 'Thiếu dữ liệu workflow';
$lang['image_selection_failed'] = 'Không thể xử lý lựa chọn hình ảnh';

$lang['change_controller'] = 'Đổi Controller';

$lang['api_token_help_text'] = 'API Token dùng để xác thực các request API';
$lang['view_token'] = 'Xem token';
$lang['hide_token'] = 'Ẩn token';
$lang['generate'] = 'Tạo mới';

$lang['action_command'] = 'Lệnh CLI';
$lang['action_command_help_text'] = 'Lệnh hệ thống để thực thi (cho trigger loại native)';

# Topic Composer strings
$lang['topic_composer'] = 'Soạn thảo Topic';
$lang['items_list'] = 'Danh sách mục';
$lang['editor'] = 'Trình soạn thảo';
$lang['select_item_to_edit'] = 'Chọn mục để chỉnh sửa';
$lang['save_all'] = 'Lưu tất cả';
$lang['saving'] = 'Đang lưu...';
$lang['position'] = 'Vị trí';
$lang['content'] = 'Nội dung';
$lang['close'] = 'Đóng';
$lang['confirm_close'] = 'Bạn có chắc chắn muốn đóng? Các thay đổi chưa lưu sẽ bị mất.';
$lang['error_saving_changes'] = 'Lỗi khi lưu thay đổi';
$lang['processing_topic'] = 'Đang xử lý topic...';
$lang['time_remaining'] = 'Thời gian còn lại';
$lang['polling_timeout'] = 'Hết thời gian chờ';
$lang['polling_error'] = 'Lỗi khi kiểm tra trạng thái';

# Topic Composer Editor
$lang['confirm_switch_item'] = 'Bạn có thay đổi chưa lưu. Bạn có muốn chuyển sang mục khác mà không lưu không?';
$lang['reset_changes'] = 'Đặt lại thay đổi';
$lang['save_item'] = 'Lưu mục';
$lang['confirm_reset_changes'] = 'Bạn có chắc chắn muốn đặt lại tất cả thay đổi?';
$lang['item_saved'] = 'Đã lưu mục thành công';
$lang['save_all_items'] = 'Lưu tất cả mục';
$lang['saving_items'] = 'Đang lưu các mục...';
$lang['items_saved'] = 'Đã lưu tất cả mục thành công';
$lang['error_saving_items'] = 'Lỗi khi lưu các mục';
$lang['preview_items'] = 'Xem trước các mục';
$lang['apply_changes'] = 'Áp dụng thay đổi';
$lang['applying_changes'] = 'Đang áp dụng thay đổi...';
$lang['changes_applied'] = 'Đã áp dụng thay đổi thành công';
$lang['error_applying_changes'] = 'Lỗi khi áp dụng thay đổi';
$lang['unsaved_changes'] = 'Thay đổi chưa lưu';
$lang['save_before_close'] = 'Bạn có muốn lưu thay đổi trước khi đóng không?';
$lang['discard_changes'] = 'Hủy thay đổi';
$lang['keep_editing'] = 'Tiếp tục chỉnh sửa';

# Editor Toolbar
$lang['format'] = 'Định dạng';
$lang['font_size'] = 'Cỡ chữ';
$lang['bold'] = 'Đậm';
$lang['italic'] = 'Nghiêng';
$lang['underline'] = 'Gạch chân';
$lang['strikethrough'] = 'Gạch ngang';
$lang['align_left'] = 'Căn trái';
$lang['align_center'] = 'Căn giữa';
$lang['align_right'] = 'Căn phải';
$lang['align_justify'] = 'Căn đều';
$lang['bullet_list'] = 'Danh sách dấu chấm';
$lang['number_list'] = 'Danh sách số';
$lang['decrease_indent'] = 'Giảm thụt lề';
$lang['increase_indent'] = 'Tăng thụt lề';
$lang['insert_link'] = 'Chèn liên kết';
$lang['insert_image'] = 'Chèn ảnh';
$lang['insert_media'] = 'Chèn media';
$lang['remove_formatting'] = 'Xóa định dạng';
$lang['help'] = 'Trợ giúp';

# Editor Messages
$lang['content_copied'] = 'Đã sao chép nội dung vào clipboard';
$lang['paste_as_text'] = 'Dán dưới dạng văn bản thuần';
$lang['word_count'] = 'Số từ';
$lang['character_count'] = 'Số ký tự';

# AI Writing Styles
$lang['select_writing_style'] = 'Chọn phong cách viết';
$lang['enter_custom_prompt'] = 'Nhập hướng dẫn viết tùy chỉnh...';

# Writing Styles
$lang['write_detailed'] = 'Viết chi tiết';
$lang['write_detailed_desc'] = 'Mở rộng nội dung với ví dụ và giải thích cụ thể';

$lang['write_concise'] = 'Viết ngắn gọn';
$lang['write_concise_desc'] = 'Tóm tắt và giữ lại các điểm chính quan trọng';

$lang['write_engaging'] = 'Viết hấp dẫn';
$lang['write_engaging_desc'] = 'Làm nội dung thú vị và cuốn hút hơn';

$lang['write_academic'] = 'Viết học thuật';
$lang['write_academic_desc'] = 'Sử dụng ngôn ngữ học thuật và trích dẫn chuyên nghiệp';

$lang['write_simple'] = 'Viết đơn giản';
$lang['write_simple_desc'] = 'Dùng ngôn ngữ đơn giản ai cũng hiểu được';

$lang['write_storytelling'] = 'Viết dạng kể chuyện';
$lang['write_storytelling_desc'] = 'Chuyển nội dung thành câu chuyện lôi cuốn';

$lang['write_professional'] = 'Viết chuyên nghiệp';
$lang['write_professional_desc'] = 'Sử dụng giọng điệu và ngôn ngữ phù hợp doanh nghiệp';

$lang['write_persuasive'] = 'Viết thuyết phục';
$lang['write_persuasive_desc'] = 'Tạo nội dung thuyết phục, thúc đẩy hành động';

$lang['write_friendly'] = 'Viết thân thiện';
$lang['write_friendly_desc'] = 'Dùng giọng điệu thân mật, như trò chuyện';

$lang['write_seo'] = 'Viết cho SEO';
$lang['write_seo_desc'] = 'Tối ưu nội dung cho công cụ tìm kiếm';

$lang['write_creative'] = 'Viết sáng tạo';
$lang['write_creative_desc'] = 'Thêm các yếu tố độc đáo và sáng tạo';

$lang['write_technical'] = 'Viết kỹ thuật';
$lang['write_technical_desc'] = 'Tập trung vào chi tiết và thông số kỹ thuật';

$lang['write_emotional'] = 'Viết cảm xúc';
$lang['write_emotional_desc'] = 'Tạo nội dung gây đồng cảm và kết nối';

$lang['write_journalistic'] = 'Viết báo chí';
$lang['write_journalistic_desc'] = 'Dùng phong cách khách quan, kiểu tin tức';

$lang['write_custom'] = 'Tùy chỉnh';
$lang['write_custom_desc'] = 'Viết hướng dẫn tùy chỉnh của bạn';

# AI Messages
$lang['please_enter_custom_prompt'] = 'Vui lòng nhập hướng dẫn viết tùy chỉnh';
$lang['processing_content'] = 'Đang xử lý nội dung...';
$lang['generating_variations'] = 'Đang tạo các biến thể...';
$lang['ai_edit_success'] = 'Đã cập nhật nội dung thành công';
$lang['ai_edit_error'] = 'Không thể cập nhật nội dung';
$lang['ai_search_error'] = 'Tìm kiếm thất bại';
$lang['ai_service_error'] = 'Dịch vụ AI hiện không khả dụng';

$lang['max_word_limit'] = 'Giới hạn số từ tối đa: ';
$lang['words'] = 'Từ';

$lang['ai_edit_success'] = 'Đã cập nhật nội dung thành công';
$lang['ai_edit_error'] = 'Không thể cập nhật nội dung';
$lang['ai_search_error'] = 'Tìm kiếm thất bại';
$lang['ai_service_error'] = 'Dịch vụ AI hiện không khả dụng';

# AI Search Questions
$lang['sample_questions'] = 'Câu hỏi mẫu';

$lang['verify_content'] = 'Xác minh nội dung';
$lang['verify_content_desc'] = 'Kiểm tra tính chính xác và sự thật của nội dung này';

$lang['fact_check'] = 'Kiểm tra sự thật';
$lang['fact_check_desc'] = 'Check the accuracy of specific facts and claims';

$lang['find_source'] = 'Tìm nguồn';
$lang['find_source_desc'] = 'Tìm các nguồn và tài liệu tham khảo đáng tin cậy';

$lang['find_similar'] = 'Tìm nội dung tương tự';
$lang['find_similar_desc'] = 'Find similar or related content on this topic';

$lang['find_references'] = 'Tìm tài liệu tham khảo';
$lang['find_references_desc'] = 'Search for academic or authoritative references';

$lang['expert_opinion'] = 'Ý kiến chuyên gia';
$lang['expert_opinion_desc'] = 'Tìm phân tích và ý kiến chuyên gia về chủ đề này';

$lang['custom_question'] = 'Câu hỏi tùy chỉnh';
$lang['custom_question_desc'] = 'Đặt câu hỏi riêng về nội dung này';

# AI Search Related
$lang['enter_custom_question'] = 'Nhập câu hỏi của bạn...';
$lang['use_this_question'] = 'Sử dụng câu hỏi này';
$lang['searching'] = 'Đang tìm kiếm...';
$lang['please_enter_question'] = 'Vui lòng nhập câu hỏi của bạn';
$lang['search_results'] = 'Kết quả tìm kiếm';
$lang['use_this'] = 'Sử dụng';
$lang['content_updated'] = 'Đã cập nhật nội dung';

$lang['ai_edit'] = 'Sửa bằng AI';
$lang['ai_search'] = 'Tìm kiếm bằng AI';

$lang['generate_from_content'] = 'Tạo từ nội dung';
$lang['generate_from_content_desc'] = 'Tạo nội dung mới dựa trên văn bản hiện có và giữ các điểm chính';

$lang['confirm_delete_selected_items'] = 'Bạn có chắc chắn muốn xóa các mục đã chọn?';
$lang['delete_selected'] = 'Xóa các mục đã chọn';

$lang['images'] = 'Hình ảnh';
$lang['keywords'] = 'Từ khóa';
$lang['save_item'] = 'Lưu mục';
$lang['reset_changes'] = 'Đặt lại thay đổi';
$lang['save_all'] = 'Lưu tất cả';

$lang['select_empty'] = 'Chọn trống';
$lang['select_empty_items'] = 'Chọn nhanh các mục trống';
$lang['found_empty_items'] = 'Tìm thấy các mục trống';
$lang['no_empty_items_found'] = 'Không tìm thấy các mục trống';

# Topic Config related
$lang['topic_config'] = 'Cấu hình Topic';
$lang['title_required'] = 'Tiêu đề là bắt buộc';
$lang['topic_required'] = 'Topic là bắt buộc';
$lang['please_fill_required_fields'] = 'Vui lòng điền đầy đủ thông tin bắt buộc';
$lang['saved_successfully'] = 'Đã lưu thành công';
$lang['save_failed'] = 'Lưu thất bại';
$lang['quick_save'] = 'Lưu nhanh';
$lang['quick_save_desc'] = 'Lưu thay đổi ngay lập tức';
$lang['quick_save_success'] = 'Lưu nhanh thành công';
$lang['quick_save_error'] = 'Lưu nhanh thất bại';
$lang['processing_save'] = 'Đang xử lý lưu...';
$lang['config_updated'] = 'Đã cập nhật cấu hình';
$lang['config_update_failed'] = 'Cập nhật cấu hình thất bại';

$lang['neutralize_content'] = 'Trung lập hóa nội dung';
$lang['neutralize_content_desc'] = 'Viết lại nội dung trung lập, loại bỏ đề cập thương hiệu nhưng giữ giá trị thông tin';

$lang['quick_set_position'] = 'Đặt vị trí nhanh';
$lang['position_updated'] = 'Đã cập nhật vị trí';

$lang['default_commands'] = 'Lệnh mặc định';
$lang['always_return_html'] = 'Luôn trả về HTML';
$lang['no_markdown_return'] = 'Không trả về Markdown';
$lang['no_json_return'] = 'Chỉ trả về kết quả (không JSON)';

$lang['external_data'] = 'Dữ liệu ngoài';
$lang['external_data_saved'] = 'Lưu dữ liệu ngoài thành công';
$lang['external_data_deleted'] = 'Xóa dữ liệu ngoài thành công';
$lang['external_data_not_found'] = 'Không tìm thấy dữ liệu ngoài';
$lang['external_data_exists'] = 'Dữ liệu ngoài đã tồn tại';
$lang['external_data_failed'] = 'Lưu dữ liệu ngoài thất bại';

$lang['find_similar_images'] = 'Tìm ảnh tương tự';
$lang['download_to_server'] = 'Tải về máy chủ';
$lang['image_already_downloaded'] = 'Ảnh đã được tải về máy chủ';
$lang['finding_similar_images'] = 'Đang tìm ảnh tương tự';
$lang['please_wait'] = 'Vui lòng đợi...';
$lang['error_finding_similar_images'] = 'Lỗi khi tìm ảnh tương tự';
$lang['similar_images'] = 'Ảnh tương tự';
$lang['image_downloaded_successfully'] = 'Tải ảnh thành công';
$lang['error_downloading_image'] = 'Lỗi khi tải ảnh';
$lang['download'] = 'Tải về';

$lang['copy_image_url'] = 'Sao chép URL Ảnh';
$lang['url_copied_to_clipboard'] = 'Đã sao chép URL vào clipboard';
$lang['failed_to_copy_url'] = 'Không thể sao chép URL';

$lang['image_downloaded_from_server'] = 'Ảnh đã được tải về máy chủ';
$lang['image_downloaded_from_server_desc'] = 'Ảnh đã được tải về máy chủ';

$lang['include_uploaded_images'] = 'Thêm Ảnh Đã Tải';
$lang['select_images_to_include'] = 'Chọn ảnh để thêm vào nội dung được tạo';
$lang['loading_images'] = 'Đang tải ảnh...';
$lang['no_uploaded_images'] = 'Không có ảnh đã tải';

$lang['url_override'] = 'Ghi đè URL';
$lang['url_override_desc'] = 'Ghi đè các URL trong nội dung';
$lang['original_url'] = 'URL gốc';
$lang['override_url'] = 'URL ghi đè';
$lang['enter_override_url'] = 'Nhập URL để ghi đè';
$lang['scanning_urls'] = 'Đang quét URL...';
$lang['no_urls_found'] = 'Không tìm thấy URL trong nội dung';

$lang['shorten_url'] = 'Rút gọn URL';
$lang['shortened'] = 'Đã rút gọn';
$lang['shortening_failed'] = 'Rút gọn URL thất bại';
$lang['actions'] = 'Thao tác';

$lang['checking_urls'] = 'Đang kiểm tra URLs...';

$lang['total_items'] = 'Tổng số mục';
$lang['position_changes'] = 'Số lượng thay đổi vị trí';
$lang['title_changes'] = 'Số lượng thay đổi tiêu đề';
$lang['content_changes'] = 'Số lượng thay đổi nội dung';
$lang['added_items'] = 'Số lượng mục thêm';
$lang['deleted_items'] = 'Số lượng mục xóa';

# Debug Panel Settings
$lang['topics_debug_settings'] = 'Cài đặt Debug Panel';
$lang['topics_enable_debug_panel'] = 'Bật Debug Panel';
$lang['topics_debug_panel_enabled'] = 'Debug Panel';

/* Draft Writer Language Strings */
$lang['draft_writer'] = 'Trình Soạn Thảo Bản Nháp';
$lang['error_loading_draft_writer'] = 'Lỗi khi tải Trình Soạn Thảo Bản Nháp';
$lang['saved_draft_found'] = 'Đã Tìm Thấy Bản Nháp';
$lang['saved_draft_found_message'] = 'Đã tìm thấy một bản nháp đã lưu trước đó cho chủ đề này. Bạn muốn sử dụng nó hay bắt đầu lại?';
$lang['no_title'] = 'Không Có Tiêu Đề';
$lang['no_description'] = 'Không Có Mô Tả';
$lang['no_content'] = 'Không Có Nội Dung';
$lang['preview_old_saved'] = 'Xem Trước Bản Nháp Đã Lưu';
$lang['reuse'] = 'Sử Dụng Bản Nháp Đã Lưu';
$lang['reload'] = 'Bắt Đầu Lại';
$lang['preview_saved_draft'] = 'Xem Trước Bản Nháp Đã Lưu';
$lang['use_this_draft'] = 'Sử Dụng Bản Nháp Này';
$lang['use_new_content'] = 'Sử Dụng Nội Dung Mới';
$lang['unknown'] = 'Không xác định';
$lang['seconds_ago'] = 'giây trước';
$lang['minutes_ago'] = 'phút trước';
$lang['hours_ago'] = 'giờ trước';
$lang['days_ago'] = 'ngày trước';
$lang['draft_loaded_from_local_storage'] = 'Bản nháp đã được tải từ bộ nhớ cục bộ';
$lang['draft_saved'] = 'Bản nháp đã được lưu thành công';
$lang['error_saving_draft'] = 'Lỗi khi lưu bản nháp';
$lang['editor_not_initialized'] = 'Trình soạn thảo chưa được khởi tạo';
$lang['title_required'] = 'Tiêu đề là bắt buộc';
$lang['content_required'] = 'Nội dung là bắt buộc';
$lang['confirm_publish_draft'] = 'Bạn có chắc chắn muốn xuất bản bản nháp này?';
$lang['error_publishing_draft'] = 'Lỗi khi xuất bản bản nháp';
$lang['invalid_content_type'] = 'Loại nội dung không hợp lệ';
$lang['no_content_to_improve'] = 'Không có nội dung để cải thiện';
$lang['content_improved'] = 'Nội dung đã được cải thiện thành công';
$lang['original_length'] = 'Độ dài ban đầu';
$lang['improved_length'] = 'Độ dài đã cải thiện';
$lang['error_improving_content'] = 'Lỗi khi cải thiện nội dung';
$lang['enter_search_query'] = 'Nhập truy vấn tìm kiếm';
$lang['select_result_to_insert'] = 'Vui lòng chọn một kết quả để chèn';
$lang['search_failed'] = 'Tìm kiếm thất bại';
$lang['no_results_found'] = 'Không tìm thấy kết quả';
$lang['no_seo_suggestions_available'] = 'Không có đề xuất SEO';
$lang['title_preview'] = 'Tiêu đề của bạn sẽ xuất hiện ở đây';
$lang['description_preview'] = 'Mô tả meta của bạn sẽ xuất hiện ở đây. Nó nên có từ 120-160 ký tự để tối ưu SEO.';
$lang['characters'] = 'ký tự';
$lang['detailed_stats'] = 'Thống Kê Chi Tiết';
$lang['word_count'] = 'Số Từ';
$lang['title_length'] = 'Độ Dài Tiêu Đề';
$lang['description_length'] = 'Độ Dài Mô Tả';
$lang['keyword_density'] = 'Mật Độ Từ Khóa';
$lang['image_count'] = 'Số Lượng Hình Ảnh';
$lang['internal_links'] = 'Liên Kết Nội Bộ';
$lang['heading_structure'] = 'Cấu Trúc Tiêu Đề';
$lang['auto_save'] = 'Tự Động Lưu';
$lang['never_saved'] = 'Chưa bao giờ lưu';
$lang['save_draft'] = 'Lưu Bản Nháp';
$lang['publish'] = 'Xuất Bản';
$lang['close'] = 'Đóng';
$lang['ai_search'] = 'Tìm Kiếm AI';
$lang['search'] = 'Tìm Kiếm';
$lang['insert_selected'] = 'Chèn Đã Chọn';

/* SEO Analysis Strings */
$lang['title_missing'] = 'Thiếu tiêu đề';
$lang['title_too_short'] = 'Tiêu đề quá ngắn (ít hơn 30 ký tự)';
$lang['title_too_long'] = 'Tiêu đề quá dài (nhiều hơn 60 ký tự)';
$lang['title_good_length'] = 'Độ dài tiêu đề tốt (từ 30-60 ký tự)';
$lang['description_missing'] = 'Thiếu mô tả meta';
$lang['description_too_short'] = 'Mô tả meta quá ngắn (ít hơn 120 ký tự)';
$lang['description_too_long'] = 'Mô tả meta quá dài (nhiều hơn 160 ký tự)';
$lang['description_good_length'] = 'Độ dài mô tả meta tốt (từ 120-160 ký tự)';
$lang['content_too_short'] = 'Nội dung quá ngắn (ít hơn 300 từ)';
$lang['content_good_length'] = 'Độ dài nội dung tốt (nhiều hơn 300 từ)';
$lang['keyword_density_too_low'] = 'Mật độ từ khóa quá thấp (ít hơn 0.5%)';
$lang['keyword_density_too_high'] = 'Mật độ từ khóa quá cao (nhiều hơn 3%)';
$lang['keyword_density_good'] = 'Mật độ từ khóa tốt (từ 0.5-3%)';
$lang['keyword_in_title'] = 'Từ khóa mục tiêu có trong tiêu đề';
$lang['keyword_not_in_title'] = 'Từ khóa mục tiêu không có trong tiêu đề';
$lang['keyword_in_description'] = 'Từ khóa mục tiêu có trong mô tả meta';
$lang['keyword_not_in_description'] = 'Từ khóa mục tiêu không có trong mô tả meta';
$lang['no_target_keyword'] = 'Không có từ khóa mục tiêu được chỉ định';

/* Draft Writer UI Strings */
$lang['outline'] = 'Dàn Bài';
$lang['stats'] = 'Thống Kê';
$lang['keywords'] = 'Từ Khóa';
$lang['seo'] = 'SEO';
$lang['content_outline'] = 'Dàn Bài Nội Dung';
$lang['no_headings_yet'] = 'Chưa có tiêu đề';
$lang['content_statistics'] = 'Thống Kê Nội Dung';
$lang['reading_time'] = 'Thời Gian Đọc';
$lang['heading_count'] = 'Số Lượng Tiêu Đề';
$lang['sentence_count'] = 'Số Lượng Câu';
$lang['avg_sentence_length'] = 'Độ Dài Câu Trung Bình';
$lang['readability'] = 'Khả Năng Đọc';
$lang['readability_score'] = 'Điểm Khả Năng Đọc';
$lang['flesch_reading_ease'] = 'Độ Dễ Đọc Flesch';
$lang['flesch_kincaid_grade'] = 'Cấp Độ Flesch-Kincaid';
$lang['keyword_cloud'] = 'Đám Mây Từ Khóa';
$lang['keyword_density'] = 'Mật Độ Từ Khóa';
$lang['seo_suggestions'] = 'Đề Xuất SEO';
$lang['seo_score'] = 'Điểm SEO';
$lang['seo_checklist'] = 'Danh Sách Kiểm Tra SEO';
$lang['search_preview'] = 'Xem Trước Tìm Kiếm';
$lang['seo_status'] = 'Trạng Thái SEO';
$lang['too_short'] = 'Quá Ngắn';
$lang['too_long'] = 'Quá Dài';
$lang['good'] = 'Tốt';
$lang['analyzing_content'] = 'Đang phân tích nội dung...';

/* SEO Error Messages */
$lang['seo_error_no_title'] = 'Chưa đặt tiêu đề';
$lang['seo_warning_title_short'] = 'Tiêu đề quá ngắn (ít hơn 30 ký tự)';
$lang['seo_warning_title_long'] = 'Tiêu đề quá dài (nhiều hơn 60 ký tự)';
$lang['seo_good_title_length'] = 'Độ dài tiêu đề tốt';
$lang['seo_error_no_description'] = 'Chưa đặt mô tả meta';
$lang['seo_warning_description_short'] = 'Mô tả meta quá ngắn (ít hơn 120 ký tự)';
$lang['seo_warning_description_long'] = 'Mô tả meta quá dài (nhiều hơn 160 ký tự)';
$lang['seo_good_description_length'] = 'Độ dài mô tả meta tốt';
$lang['seo_error_content_short'] = 'Nội dung quá ngắn (ít hơn 300 từ)';
$lang['seo_warning_content_medium'] = 'Nội dung có thể dài hơn (ít hơn 600 từ)';
$lang['seo_good_content_length'] = 'Độ dài nội dung tốt';
$lang['seo_error_no_headings'] = 'Không tìm thấy tiêu đề trong nội dung';
$lang['seo_warning_no_h1'] = 'Không tìm thấy tiêu đề H1 trong nội dung';
$lang['seo_warning_no_h2'] = 'Không tìm thấy tiêu đề H2 trong nội dung';
$lang['seo_good_heading_structure'] = 'Cấu trúc tiêu đề tốt';
$lang['seo_warning_keyword_density_low'] = 'Mật độ từ khóa quá thấp';
$lang['seo_warning_keyword_density_high'] = 'Mật độ từ khóa quá cao';
$lang['seo_good_keyword_density'] = 'Mật độ từ khóa tốt';
$lang['seo_warning_no_images'] = 'Không tìm thấy hình ảnh trong nội dung';
$lang['seo_warning_images_missing_alt'] = 'Một số hình ảnh thiếu văn bản thay thế';
$lang['seo_good_images_with_alt'] = 'Tất cả hình ảnh đều có văn bản thay thế';
$lang['seo_warning_no_links'] = 'Không tìm thấy liên kết nội bộ trong nội dung';
$lang['seo_good_keyword_in_title'] = 'Từ khóa mục tiêu có trong tiêu đề';
$lang['seo_warning_keyword_not_in_title'] = 'Từ khóa mục tiêu không có trong tiêu đề';
$lang['seo_good_keyword_in_description'] = 'Từ khóa mục tiêu có trong mô tả meta';
$lang['seo_warning_keyword_not_in_description'] = 'Từ khóa mục tiêu không có trong mô tả meta';

/* Draft Writer Editor Strings */
$lang['post_title'] = 'Tiêu Đề Bài Viết';
$lang['enter_title'] = 'Nhập tiêu đề';
$lang['ai_edit_title'] = 'Chỉnh Sửa Tiêu Đề Bằng AI';
$lang['ai_edit'] = 'Chỉnh Sửa AI';
$lang['meta_description'] = 'Mô Tả Meta';
$lang['enter_meta_description'] = 'Nhập mô tả meta';
$lang['ai_edit_description'] = 'Chỉnh Sửa Mô Tả Bằng AI';
$lang['content'] = 'Nội Dung';
$lang['ai_edit_content'] = 'Chỉnh Sửa Nội Dung Bằng AI';
$lang['ai_search'] = 'Tìm Kiếm AI';
$lang['ai_improve'] = 'Cải Thiện Bằng AI';
$lang['tags'] = 'Thẻ';
$lang['enter_tags'] = 'Nhập thẻ (phân cách bằng dấu phẩy)';
$lang['separate_tags_with_commas'] = 'Phân cách thẻ bằng dấu phẩy';
$lang['ai_suggest_tags'] = 'Đề Xuất Thẻ Bằng AI';
$lang['suggest'] = 'Đề Xuất';
$lang['category'] = 'Danh Mục';
$lang['select_category'] = 'Chọn danh mục';
$lang['featured_image'] = 'Hình Ảnh Nổi Bật';
$lang['click_to_upload_image'] = 'Nhấp để tải lên hình ảnh';
$lang['remove'] = 'Xóa';
$lang['quick_save'] = 'Lưu Nhanh';
$lang['last_saved'] = 'Lần lưu cuối';
$lang['toggle_fullscreen'] = 'Chuyển Đổi Toàn Màn Hình';
$lang['toggle_html'] = 'Chuyển Đổi HTML';
$lang['insert_image'] = 'Chèn Hình Ảnh';
$lang['insert_table'] = 'Chèn Bảng';
$lang['insert_link'] = 'Chèn Liên Kết';
$lang['rewrite_selection'] = 'Viết Lại Phần Đã Chọn';
$lang['improve_selection'] = 'Cải Thiện Phần Đã Chọn';
$lang['fact_check_selection'] = 'Kiểm Tra Sự Thật Phần Đã Chọn';
$lang['expand_selection'] = 'Mở Rộng Phần Đã Chọn';
$lang['rewrite'] = 'Viết Lại';
$lang['improve'] = 'Cải Thiện';
$lang['fact_check'] = 'Kiểm Tra Sự Thật';
$lang['expand'] = 'Mở Rộng';
$lang['bold'] = 'Đậm';
$lang['italic'] = 'Nghiêng';
$lang['underline'] = 'Gạch Chân';
$lang['strikethrough'] = 'Gạch Ngang';
$lang['heading'] = 'Tiêu Đề';
$lang['heading_1'] = 'Tiêu Đề 1';
$lang['heading_2'] = 'Tiêu Đề 2';
$lang['heading_3'] = 'Tiêu Đề 3';
$lang['heading_4'] = 'Tiêu Đề 4';
$lang['heading_5'] = 'Tiêu Đề 5';
$lang['heading_6'] = 'Tiêu Đề 6';
$lang['paragraph'] = 'Đoạn Văn';
$lang['align_left'] = 'Căn Trái';
$lang['align_center'] = 'Căn Giữa';
$lang['align_right'] = 'Căn Phải';
$lang['align_justify'] = 'Căn Đều';
$lang['bullet_list'] = 'Danh Sách Dấu Đầu Dòng';
$lang['number_list'] = 'Danh Sách Số';
$lang['decrease_indent'] = 'Giảm Thụt Lề';
$lang['increase_indent'] = 'Tăng Thụt Lề';
$lang['insert_horizontal_rule'] = 'Chèn Đường Kẻ Ngang';
$lang['ai_rewrite'] = 'Viết Lại Bằng AI';
$lang['more_ai_options'] = 'Thêm Tùy Chọn AI';
$lang['shorten'] = 'Rút Gọn';
$lang['simplify'] = 'Đơn Giản Hóa';
$lang['make_professional'] = 'Làm Chuyên Nghiệp';
$lang['make_casual'] = 'Làm Thân Mật';
$lang['link'] = 'Liên Kết';
$lang['image'] = 'Hình Ảnh';
$lang['table'] = 'Bảng';

/* WRITE_DRAFT Action Command */
$lang['write_draft'] = 'Viết Bản Nháp';
$lang['write_draft_desc'] = 'Tạo bản nháp từ nội dung chủ đề';

# Draft Writer - Keyword Analysis
$lang['words'] = 'Từ';
$lang['min_read'] = 'phút đọc';
$lang['seo_score'] = 'Điểm SEO';
$lang['keyword'] = 'Từ khóa';
$lang['count'] = 'Số lần';
$lang['density'] = 'Mật độ';
$lang['score'] = 'Điểm';
$lang['distribution'] = 'Phân bố';
$lang['recommendations'] = 'Đề xuất';
$lang['no_keywords_found'] = 'Không tìm thấy từ khóa';
$lang['keyword_analysis_info'] = 'Phân tích mật độ từ khóa theo tiêu chuẩn Semrush';
$lang['optimal_density'] = 'Mật độ tối ưu';
$lang['low_density'] = 'Mật độ thấp';
$lang['very_low_density'] = 'Mật độ rất thấp';
$lang['no_recommendations'] = 'Không cần đề xuất';
$lang['suggestions'] = 'đề xuất';

# Keyword Analysis Recommendations
$lang['keyword_density_too_low'] = 'Mật độ từ khóa quá thấp (dưới 0.5%)';
$lang['keyword_density_too_high'] = 'Mật độ từ khóa quá cao (trên 3%)';
$lang['add_keyword_to_title'] = 'Thêm từ khóa vào tiêu đề';
$lang['add_keyword_to_first_paragraph'] = 'Thêm từ khóa vào đoạn đầu tiên';
$lang['add_keyword_to_headings'] = 'Thêm từ khóa vào một hoặc nhiều tiêu đề';
$lang['add_keyword_to_beginning'] = 'Thêm từ khóa vào phần đầu nội dung';
$lang['improve_keyword_distribution'] = 'Improve keyword distribution throughout content';

# SEO Analysis
$lang['detailed_stats'] = 'Thống kê chi tiết';
$lang['title_length'] = 'Độ dài tiêu đề';
$lang['description_length'] = 'Độ dài mô tả';
$lang['characters'] = 'ký tự';
$lang['image_count'] = 'Số Lượng Hình Ảnh';
$lang['internal_links'] = 'Liên kết nội bộ';
$lang['heading_structure'] = 'Cấu trúc tiêu đề';

# Status Messages
$lang['analyzing_content'] = 'Đang phân tích nội dung...';
$lang['found_in_title'] = 'Có trong tiêu đề';
$lang['found_in_first_paragraph'] = 'Có trong đoạn đầu';
$lang['found_in_headings'] = 'Có trong tiêu đề';
$lang['section'] = 'Phần';
$lang['occurrences'] = 'lần xuất hiện';

# Keyword Analysis
$lang['keyword_analysis'] = 'Phân tích từ khóa';

# Batch Title Generator
$lang['batch_generate_titles'] = 'Tạo Tiêu Đề Hàng Loạt';
$lang['word_limit'] = 'Giới Hạn Số Từ';
$lang['recommended_between_5_20'] = 'Khuyến nghị từ 5-20 từ';
$lang['return_html'] = 'Trả Về HTML';
$lang['items_to_process'] = 'Mục Để Xử Lý';
$lang['all_items'] = 'Tất Cả Mục';
$lang['selected_items'] = 'Mục Đã Chọn';
$lang['batch_titles_info'] = 'Điều này sẽ tạo tiêu đề cho nhiều mục bằng AI. Quá trình có thể mất một khoảng thời gian tùy thuộc vào số lượng mục.';
$lang['start_generation'] = 'Bắt Đầu Tạo Tiêu Đề';
$lang['generating_titles'] = 'Đang Tạo Tiêu Đề...';
$lang['cancel'] = 'Hủy Bỏ';
$lang['confirm_cancel_batch_generation'] = 'Bạn có chắc chắn muốn hủy tạo tiêu đề hàng loạt?';
$lang['batch_generation_cancelled'] = 'Tạo tiêu đề hàng loạt đã bị hủy bỏ';
$lang['batch_generation_completed'] = 'Tạo tiêu đề hàng loạt đã hoàn tất';
$lang['processed_items'] = 'Mục Đã Xử Lý';
$lang['dismiss'] = 'Bỏ Qua';
$lang['batch_title_generation_completed'] = 'Tạo tiêu đề hàng loạt đã hoàn tất thành công';
$lang['please_select_at_least_one_item'] = 'Vui lòng chọn ít nhất một mục';


# Auto Reposition
$lang['auto_reposition'] = 'Tự Động Sắp Xếp';
$lang['repositioning_completed'] = 'Sắp xếp đã hoàn tất';
$lang['repositioning_items'] = 'Đang sắp xếp mục...';
$lang['confirm_auto_reposition'] = 'Điều này sẽ đánh số thứ tự tất cả các mục theo thứ tự tuần tự. Tiếp tục?';

# Bulk Edit Content
$lang['bulk_edit_content'] = 'Chỉnh Sửa Nội Dung Hàng Loạt';
$lang['bulk_edit_content_desc'] = 'Điều này sẽ xử lý nội dung của nhiều mục bằng AI.';
$lang['content_style'] = 'Kiểu Nội Dung';
$lang['detailed'] = 'Chi Tiết';
$lang['concise'] = 'Tóm Tắt';
$lang['creative'] = 'Tạo Hình';
$lang['professional'] = 'Chuyên Nghiệp';
$lang['generating_content'] = 'Đang tạo nội dung...';
$lang['batch_content_generation_completed'] = 'Tạo nội dung hàng loạt đã hoàn tất thành công';
$lang['content_improvement_info'] = 'Chọn một kiểu để cải thiện nội dung của các mục đã chọn. Quá trình có thể mất một khoảng thời gian tùy thuộc vào số lượng mục.';

# Custom Instructions and Optimization Options
$lang['custom_instructions'] = 'Hướng Dẫn Tùy Chỉnh';
$lang['enter_custom_instructions'] = 'Nhập hướng dẫn tùy chỉnh cho việc tạo nội dung...';
$lang['custom_instructions_desc'] = 'Hướng dẫn tùy chỉnh sẽ được thêm vào prompt của AI để đáp ứng yêu cầu nội dung cụ thể hơn.';
$lang['optimization_options'] = 'Tùy Chọn Tối Ưu Hóa';
$lang['optimize_for_seo'] = 'Tối ưu hóa cho SEO';
$lang['remove_external_links'] = 'Xóa tất cả các liên kết và điều hướng';
$lang['optimize_paragraph_length'] = 'Tối ưu độ dài đoạn văn cho người đọc web';
$lang['add_subheadings'] = 'Thêm tiêu đề phụ (h2, h3)';
$lang['add_call_to_action'] = 'Thêm call-to-action ở cuối bài viết';
$lang['insert_images_to_item'] = 'Chèn hình ảnh đã tải vào nội dung';
$lang['modified_fields'] = 'Các trường đã sửa đổi';


$lang['controller_selection'] = 'Chọn Controller';
$lang['select_controller_to_preview'] = 'Chọn controller để xem';
$lang['controller_preview'] = 'Xem Controller';
$lang['controller_selected'] = 'Controller đã chọn';
$lang['no_controllers_available_for_this_topic'] = 'Không có controller nào có sẵn cho chủ đề này';
$lang['error_loading_controllers'] = 'Lỗi tải controller';
$lang['error_parsing_server_response'] = 'Lỗi phân tích phản hồi server';

# Add strings for controller form and connection status
$lang['edit_mode']                      = 'Chế Độ Chỉnh Sửa';
$lang['configuration_loaded']           = 'Đã Tải Cấu Hình';
$lang['login_configuration']            = 'Cấu Hình Đăng Nhập';
$lang['site_url']                       = 'URL Trang Web';
$lang['description']                    = 'Mô Tả';
$lang['categories']                     = 'Danh Mục';
$lang['posts']                          = 'Bài Viết';
$lang['pages']                          = 'Trang';
$lang['wordpress_info']                 = 'Thông Tin WordPress';
$lang['haravan_info']                   = 'Thông Tin Haravan';
$lang['site_information']               = 'Thông Tin Trang Web';
$lang['quick_save_login']               = 'Lưu Nhanh Đăng Nhập';
$lang['save_login_credentials_help']    = 'Lưu thông tin đăng nhập mà không cần gửi toàn bộ biểu mẫu';
$lang['testing_connection']             = 'Đang kiểm tra kết nối';
$lang['testing']                        = 'Đang kiểm tra';
$lang['test_connection']                = 'Kiểm Tra Kết Nối';
$lang['connection_successful']          = 'Kết nối thành công';
$lang['connection_failed']              = 'Kết nối thất bại';
$lang['error_testing_connection']       = 'Lỗi kiểm tra kết nối';
$lang['username']                       = 'Tên Đăng Nhập';
$lang['password']                       = 'Mật Khẩu';
$lang['application_password']           = 'Mật Khẩu Ứng Dụng';
$lang['successfully_saved']             = 'Đã lưu thành công';
$lang['error_saving']                   = 'Lỗi khi lưu';

# Tags và Publish related
$lang['draft_tags'] = 'Tags từ bản nháp';
$lang['new_tags'] = 'Tags mới';
$lang['enter_new_tag'] = 'Nhập tag mới';
$lang['popular_tags'] = 'Tags phổ biến';
$lang['no_tags_available'] = 'Không có tags nào';
$lang['enter_tags_comma_separated'] = 'Nhập tags, phân cách bằng dấu phẩy';
$lang['separate_tags_with_commas'] = 'Phân cách các tags bằng dấu phẩy';
$lang['add'] = 'Thêm';
$lang['error_loading_tags'] = 'Lỗi khi tải tags';

# Action Button Controller Only Option
$lang['controller_only'] = 'Chỉ Hiển Thị Controller';
$lang['controller_only_help_text'] = 'Nếu được chọn, nút này sẽ chỉ hiển thị trong Controllers và Ultimate Editor, không hiển thị trong topic detail view.';

# Bulk Download Images
$lang['bulk_download_images'] = 'Tải Nhiều Hình Ảnh';
$lang['downloading_images'] = 'Đang tải hình ảnh...';
$lang['download_image'] = 'Tải Hình Ảnh';
$lang['downloading_image'] = 'Đang tải hình ảnh...';
$lang['downloading_image_success'] = 'Tải hình ảnh thành công';
$lang['downloading_image_error'] = 'Lỗi khi tải hình ảnh';
$lang['no_images_to_download'] = 'Không có hình ảnh nào để tải';
$lang['found_images_to_download'] = 'Tìm thấy %s hình ảnh để tải';
$lang['all_images_already_downloaded'] = 'Tất cả hình ảnh đã được tải';
$lang['images_downloading_completed'] = 'Hoàn tất tải hình ảnh';
$lang['images_processed'] = 'Hình ảnh đã xử lý';
$lang['images_downloaded'] = 'Hình ảnh đã tải';
$lang['images_failed'] = 'Hình ảnh thất bại';
$lang['downloading_progress'] = 'Tiến trình tải về';
$lang['bulk_download_images_downloaded'] = 'Đã tải hình ảnh hàng loạt';

$lang['detailed'] = 'Chi Tiết';
$lang['concise'] = 'Tóm Tắt';
$lang['creative'] = 'Tạo Hình';
$lang['professional'] = 'Chuyên Nghiệp';
$lang['conversational'] = 'Hội Thoại';
$lang['storytelling'] = 'Kể Chuyện';
$lang['technical'] = 'Kỹ Thuật';
$lang['academic'] = 'Học Thuật';
$lang['persuasive'] = 'Thuyết Phục';
$lang['instructional'] = 'Hướng Dẫn';

# Auto Position with Custom Prefix
$lang['select_position_prefix'] = 'Chọn Tiền Tố Vị Trí';
$lang['common_prefixes'] = 'Tiền Tố Phổ Biến';
$lang['custom_prefix'] = 'Tiền Tố Tùy Chỉnh';
$lang['enter_custom_prefix'] = 'Nhập tiền tố tùy chỉnh';
$lang['preview'] = 'Xem Trước';
$lang['advanced_options'] = 'Tùy Chọn Nâng Cao';
$lang['add_space_after_prefix'] = 'Thêm khoảng trắng sau tiền tố';
$lang['apply'] = 'Áp Dụng';

// Clone controller functionality
$lang['clone'] = 'Sao chép';
$lang['controller_cloned_successfully'] = 'Đã sao chép controller thành công';
$lang['controller_clone_failed'] = 'Sao chép controller thất bại';

// Category tree search
$lang['search_categories'] = 'Tìm kiếm danh mục...';

# Topic Updated Notification
$lang['topic_updated_notification'] = 'Topic đã cập nhật:';