<?php
defined('BASEPATH') or exit('No direct script access allowed');

# Menu items
$lang['topics'] = 'Topics';
$lang['controllers'] = 'Controllers';
$lang['topics_title'] = 'Topics Management';
$lang['topics_list'] = 'Topics List';
$lang['action_types'] = 'Action Types';
$lang['action_states'] = 'Action States';
$lang['topics_management'] = 'Topics';
$lang['topic_list'] = 'Topics List';
$lang['topics_dashboard'] = 'Topics History';
$lang['topic_master_record'] = 'Master Record';
$lang['topic_status'] = 'Status';
$lang['topic_state_color'] = 'State Color';
$lang['active_topics'] = 'Active Topics';

# Overview
$lang['topics_overview'] = 'Topics Overview';
$lang['topics_overview_subtitle'] = 'High-level documentation of module features, data model, relationships, and integrations.';
$lang['topics_overview_scope'] = 'Scope';
$lang['topics_overview_scope_lifecycle'] = 'Manage full lifecycle: Topic Master → Topic Instances → Action Types/States → Targets → Controllers → Logs/External Data';
$lang['topics_overview_scope_automation'] = 'Automation-first with N8N workflows, action buttons (webhook/native), logs, and settings';
$lang['topics_overview_scope_realtime'] = 'Realtime UX: staff online tracking and Pusher notifications';
$lang['topics_overview_scope_ui'] = 'Admin UI with lists, detail, dashboard, filters, bulk actions, copy Topic ID';
$lang['topics_overview_features'] = 'Key Features';
$lang['topics_overview_features_topics'] = 'Rich topic records with data/logs, type/state tags, targets, automation id';
$lang['topics_overview_features_types_states'] = 'Hierarchical Action Types; typed Action States with color/order/validity';
$lang['topics_overview_features_buttons'] = 'Configurable Action Buttons to trigger workflows or native actions with include/ignore rules';
$lang['topics_overview_features_online'] = 'Online presence tracking per topic and dot indicator on avatars';
$lang['topics_overview_features_notifications'] = 'Realtime notifications via Pusher';
$lang['topics_overview_features_processors'] = 'Extensible processors: WordPress, Google Sheets raw, Social post, Draft writing, Image generation, Topic composer';
$lang['topics_overview_features_dashboard'] = 'Operational dashboard and history with filters and processed-data views';
$lang['topics_overview_features_assets'] = 'Cache-busted assets and JSON repair helpers';
$lang['topics_overview_data_model'] = 'Data Model & Relationships';
$lang['topics_overview_integrations'] = 'Integrations';
$lang['topics_overview_integrations_n8n'] = 'N8N: webhook/host URLs, workflow/execution links, default workflow seeding';
$lang['topics_overview_integrations_pusher'] = 'Pusher: per-staff notification channels';
$lang['topics_overview_integrations_wp_social'] = 'WordPress & Social via processors and controller credentials';
$lang['topics_overview_permissions_settings'] = 'Permissions & Settings';
$lang['topics_overview_permissions'] = 'Capabilities: topics, topics_automation, topic_action_buttons, topic_controllers';
$lang['topics_overview_settings'] = 'Options: online tracking/timeouts, debug panel, N8N host/webhook/api key, enable buttons/controllers, default workflow';
$lang['topics_overview_navigation'] = 'Navigation';
$lang['topics_overview_nav_pages'] = 'Pages: list, detail, dashboard, CRUD for types/states/buttons, topic master, controllers';
$lang['topics_overview_nav_assets'] = 'Assets: topics.css, jsonrepair.min.js, preloaded language, circleProgress guard';
$lang['topics_overview_notes'] = 'Note: Schema drift to watch (e.g., valid_data on action states appears in reference SQL).';

# Topics
$lang['topic'] = 'Topic';
$lang['new_topic'] = 'New Topic';
$lang['edit_topic'] = 'Edit Topic';
$lang['delete_topic'] = 'Delete Topic';
$lang['topic_id'] = 'Topic ID';
$lang['topic_title'] = 'Title';
$lang['topic_detail'] = 'Topic Detail - Magic Writter';
$lang['add_topic'] = 'Add New Topic';
$lang['log'] = 'Log';

# Action Types
$lang['action_type'] = 'Action Type';
$lang['new_action_type'] = 'New Action Type';
$lang['edit_action_type'] = 'Edit Action Type';
$lang['delete_action_type'] = 'Delete Action Type';
$lang['action_type_name'] = 'Action Type Name';
$lang['action_type_id'] = 'Action Type ID';
$lang['add_new_action_type'] = 'Add New Action Type';
$lang['action_type_detail'] = 'Action Type Detail';
$lang['action_type_code'] = 'Action Type Code';
$lang['action_types_list'] = 'Action Types List';
$lang['action_type_states'] = 'Action Type States';

# Action States
$lang['action_state'] = 'Action State';
$lang['new_action_state'] = 'New Action State';
$lang['edit_action_state'] = 'Edit Action State';
$lang['delete_action_state'] = 'Delete Action State';
$lang['action_state_name'] = 'Action State Name';
$lang['action_state_id'] = 'Action State ID';
$lang['add_new_action_state'] = 'Add New Action State';
$lang['action_state_detail'] = 'Action State Detail';
$lang['action_state_color'] = 'Action State Color';
$lang['action_state_code'] = 'Action State Code';
$lang['action_states_list'] = 'Action States List';
$lang['action_state_states'] = 'Action States';

# Others
$lang['select_action_type'] = 'Select Action Type';
$lang['select_action_state'] = 'Select Action State';
$lang['none'] = 'None';
$lang['options'] = 'Options';
$lang['back'] = 'Back';
$lang['created_date'] = 'Created Date';
$lang['updated_date'] = 'Updated Date';
$lang['submit'] = 'Submit';
$lang['cancel'] = 'Cancel';
$lang['dropdown_non_selected_tex'] = 'None selected';
$lang['back_to_list'] = 'Back to List';

# Messages
$lang['topic_added'] = 'Topic added successfully';
$lang['topic_updated'] = 'Topic updated successfully';
$lang['topic_deleted'] = 'Topic deleted successfully';
$lang['action_type_added'] = 'Action Type added successfully';
$lang['action_type_updated'] = 'Action Type updated successfully';
$lang['action_type_deleted'] = 'Action Type deleted successfully';
$lang['action_state_added'] = 'Action State added successfully';
$lang['action_state_updated'] = 'Action State updated successfully';
$lang['action_state_deleted'] = 'Action State deleted successfully';
$lang['search_topics'] = 'Search topics...';
$lang['type_to_search'] = 'Type to search...';
$lang['log_details'] = 'Log Details';
$lang['view_log'] = 'View Log';
$lang['positions_updated'] = 'Positions updated successfully';

# Dashboard Stats
$lang['total_topics'] = 'Total Topics';
$lang['writing_topics'] = 'Writing Topics';
$lang['social_audit_topics'] = 'Social Audit Topics';
$lang['scheduled_social_topics'] = 'Scheduled Social Topics';
$lang['post_audit_gallery_topics'] = 'Post Audit Gallery Topics';
$lang['last_update'] = 'Last Update';
$lang['quick_stats'] = 'Quick Stats';
$lang['topics_by_state'] = 'Topics by State';
$lang['view_all'] = 'View All';
$lang['filter_by_state'] = 'Filter by State';

# States
$lang['state_writing'] = 'Writing';
$lang['state_social_audit'] = 'Social Audit';
$lang['state_scheduled_social'] = 'Scheduled Social';
$lang['state_post_audit_gallery'] = 'Post Audit Gallery';

# Thêm các chuỗi còn thiếu
$lang['clear_filters'] = 'Xóa bộ lọc';
$lang['export'] = 'Export';
$lang['active_filters'] = 'Active Filters';
$lang['all'] = 'All';
$lang['options'] = 'Options';
$lang['active'] = 'Active';
$lang['inactive'] = 'Inactive';
$lang['toggle_active'] = 'Toggle status';
$lang['quick_actions'] = 'Quick Actions';
$lang['quick_activate'] = 'Quick Activate';
$lang['quick_deactivate'] = 'Quick Deactivate';
$lang['selected_items'] = 'selected items';
$lang['bulk_action_success'] = 'Selected topics have been updated successfully';
$lang['bulk_action_failed'] = 'Failed to update selected topics';
$lang['no_items_selected'] = 'No items selected';
$lang['invalid_request'] = 'Invalid request';
$lang['confirm_action'] = 'Are you sure you want to perform this action?';
$lang['invalid_action'] = 'Invalid action';
$lang['invalid_data'] = 'Invalid data format';
$lang['activate'] = 'Activate';
$lang['deactivate'] = 'Deactivate';
$lang['bulk_action_confirmation'] = 'Are you sure you want to perform this action with the selected items?';
$lang['confirm'] = 'Confirm';
$lang['close'] = 'Close';
$lang['copied_to_clipboard'] = 'Copied to clipboard';

# Thêm các strings mới
$lang['parent_action_type'] = 'Parent Action Type';
$lang['action_type_cannot_be_own_parent'] = 'Action type cannot be its own parent';
$lang['action_type_circular_reference'] = 'Cannot create circular reference in parent-child relationship';
$lang['no_parent'] = 'No Parent';
$lang['parent_type'] = 'Parent Type';
$lang['child_types'] = 'Child Types';
$lang['has_children'] = 'Has Child Types';
$lang['is_child_of'] = 'Is Child of';
$lang['parent_details'] = 'Parent Details';
$lang['child_details'] = 'Child Details';
$lang['select_parent_type'] = 'Select Parent Type';
$lang['parent_type_changed'] = 'Parent type changed successfully';
$lang['parent_type_remove'] = 'Remove Parent Type';
$lang['confirm_remove_parent'] = 'Are you sure you want to remove the parent type?';
$lang['parent_type_removed'] = 'Parent type removed successfully';
$lang['invalid_parent_type'] = 'Invalid parent type selection';
$lang['parent_child_relation'] = 'Parent-Child Relation';

$lang['reposition'] = 'Reposition';
$lang['save_positions'] = 'Save Positions';
$lang['positions_updated'] = 'Positions updated successfully';
$lang['error_updating_positions'] = 'Error updating positions';

# Filter related
$lang['filter_by_state'] = 'Filter by State';
$lang['filter_by_type'] = 'Filter by Type';
$lang['search_mode'] = 'Search Mode';
$lang['search_mode_or'] = 'OR';
$lang['search_mode_and'] = 'AND';
$lang['no_filters_applied'] = 'No filters applied';
$lang['filter_applied'] = 'Filter applied';
$lang['filters_applied'] = 'Filters applied';

# Topic Target
$lang['topic_targets'] = 'Topic Targets';
$lang['topic_target'] = 'Target';
$lang['new_topic_target'] = 'Add New Target';
$lang['edit_topic_target'] = 'Edit Target';
$lang['delete_topic_target'] = 'Delete Target';
$lang['topic_target_name'] = 'Target Name';
$lang['topic_target_description'] = 'Description';
$lang['topic_target_value'] = 'Value';
$lang['topic_target_type'] = 'Target Type';
$lang['topic_target_status'] = 'Status';
$lang['existing_targets'] = 'Existing Targets';
$lang['topic_target_type_help'] = 'Enter target type in uppercase, e.g. CONTENT, SOCIAL';
$lang['topic_target_exists'] = 'This target type already exists';
$lang['topic_target_added'] = 'Target added successfully';
$lang['topic_target_updated'] = 'Target updated successfully';
$lang['topic_target_deleted'] = 'Target deleted successfully';
$lang['confirm_edit_target'] = 'This target already exists. Would you like to edit it instead?';

$lang['standard_data'] = 'Standard Data';

# Thêm vào cuối file
$lang['total_topic_masters'] = 'Total Topic Masters';
$lang['active_topic_masters'] = 'Active Topic Masters';
$lang['active_masters'] = 'Active Masters';

# From views/action_types/edit.php  
$lang['positions_updated_successfully'] = 'Positions updated successfully';
$lang['error_loading_log_data'] = 'Error loading log data';
$lang['failed_to_load_log_data'] = 'Failed to load log data';
$lang['collapse_all'] = 'Collapse All';
$lang['expand_all'] = 'Expand All';
$lang['website_info_updated'] = 'Website information (slogan and logo) has been updated';

# From views/detail.php
$lang['error_parsing_response'] = 'Error parsing response';
$lang['error_loading_log'] = 'Error loading log';

# From views/action_types/index.php
$lang['reposition_items'] = 'Reposition Items';
$lang['save_positions'] = 'Save Positions';
$lang['no_history_found'] = 'No history found';
$lang['bulk_action_confirmation_msg'] = 'Are you sure you want to perform this action with the selected items?';
$lang['activate_all'] = 'Activate All';
$lang['deactivate_all'] = 'Deactivate All';
$lang['topic_histories'] = 'Topic Histories';

$lang['process_data'] = 'Process Data';
$lang['data_processed_successfully'] = 'Data processed successfully';
$lang['data_processing_failed'] = 'Data processing failed';
$lang['no_processor_found'] = 'No data processor found for this action type';
$lang['position'] = 'Position';
$lang['title'] = 'Title';
$lang['content'] = 'Content';

# Process Data Related
$lang['no_data_to_process'] = 'No data available to process';
$lang['missing_required_fields'] = 'Missing required fields';
$lang['no_data_available'] = 'No data available';
$lang['back_to_topic'] = 'Back to Topic';
$lang['confirm_process_data'] = 'Are you sure you want to process this data?';
$lang['save_changes'] = 'Save Changes';

# Process Data Additional
$lang['total_targets'] = 'Total Targets';
$lang['reset_to_original'] = 'Reset to Original';
$lang['confirm_reset_data'] = 'Are you sure you want to reset to original data? All changes will be lost.';
$lang['backup_creation_failed'] = 'Failed to create backup';
$lang['data_reset_success'] = 'Data has been reset successfully';
$lang['data_reset_failed'] = 'Failed to reset data';
$lang['last_backup'] = 'Last Backup';
$lang['quick_save'] = 'Quick Save';
$lang['save'] = 'Save';
$lang['status'] = 'Status';
$lang['position'] = 'Position';
$lang['title'] = 'Title';
$lang['content'] = 'Content';
$lang['data_processed_successfully'] = 'Data processed successfully';
$lang['data_processing_failed'] = 'Data processing failed';
$lang['no_processor_found'] = 'No data processor found for this action type';
$lang['backup_creation_failed'] = 'Failed to create backup';
$lang['no_data_to_process'] = 'No data available to process';
$lang['missing_required_fields'] = 'Missing required fields';

# Quick Save Related
$lang['item_saved_successfully'] = 'Item saved successfully';
$lang['save_failed'] = 'Save failed';
$lang['error_processing_response'] = 'Error processing response';
$lang['ajax_error'] = 'Ajax error occurred';

$lang['sort_items'] = 'Sort Items';
$lang['sort_items_title'] = 'Sort Topic Items';
$lang['positions_saved'] = 'Positions saved successfully';
$lang['error_saving_positions'] = 'Error saving positions';

# Process Data Related
$lang['view_processed_data'] = 'View Processed Data';
$lang['view_execution'] = 'View Execution';
$lang['view_workflow'] = 'View Workflow';
$lang['processed_data_details'] = 'Processed Data Details';
$lang['loading'] = 'Loading...';
$lang['no_specific_handler'] = 'No specific handler for target type:';
$lang['open_in_new_tab'] = 'Open in new tab';
$lang['no_image_data'] = 'No image data found';
$lang['refresh'] = 'Refresh';

# Log Related
$lang['log_data_not_found'] = 'Log data not found';
$lang['database_transaction_failed'] = 'Database transaction failed';
$lang['all_topics_activated'] = 'All topics have been activated';
$lang['all_topics_deactivated'] = 'All topics have been deactivated';
$lang['selected_topics_activated'] = 'Selected topics have been activated';
$lang['selected_topics_deactivated'] = 'Selected topics have been deactivated';
$lang['process_data_reset_success'] = 'Process data has been reset successfully';
$lang['failed_to_load_processed_data'] = 'Failed to load processed data';
$lang['topic_not_found'] = 'Topic not found';
$lang['failed_to_parse_data'] = 'Failed to parse JSON data';

# Thêm các chuỗi mới
$lang['error_processing_topic_data'] = 'Error processing topic data';
$lang['topic_id_required'] = 'Topic ID is required';
$lang['invalid_json_data'] = 'Invalid JSON data format';
$lang['item_saved_successfully'] = 'Item saved successfully';
$lang['database_transaction_failed'] = 'Database transaction failed';
$lang['no_data_available'] = 'No data available';
$lang['back_to_topic'] = 'Back to Topic';
$lang['confirm_process_data'] = 'Are you sure you want to process this data?';
$lang['save_changes'] = 'Save Changes';
$lang['reset_to_original'] = 'Reset to Original';
$lang['confirm_reset_data'] = 'Are you sure you want to reset to original data? All changes will be lost.';
$lang['show_debug_data'] = 'Show Debug Data';
$lang['field'] = 'Field';
$lang['value'] = 'Value';
$lang['keywords'] = 'Keywords';
$lang['topic_footer'] = 'Topic Footer';
$lang['valid_data'] = 'Valid Data';
$lang['topic_data_updated'] = 'Topic data has been updated';
$lang['enable_online_tracking'] = 'Enable Online Tracking';
$lang['online_timeout_seconds'] = 'Online Timeout (seconds)';
$lang['settings_updated'] = 'Settings updated successfully';

# Settings
$lang['topics_settings'] = 'Topics Settings';

# Online Tracking Settings
$lang['topics_online_tracking']          = 'Online Tracking';
$lang['topics_online_tracking_enabled']  = 'Enable Online Tracking';
$lang['topics_online_timeout']          = 'Online Timeout (seconds)';
$lang['topics_online_timeout_desc']     = 'Time in seconds before a user is considered offline';

# N8N Integration
$lang['topics_n8n_integration']         = 'N8N Integration';
$lang['topics_n8n_host']               = 'N8N Host URL';
$lang['topics_n8n_webhook_url']        = 'N8N Webhook URL';
$lang['topics_n8n_host_desc']          = 'Enter your N8N host URL (e.g. https://n8n.yourdomain.com)';
$lang['topics_n8n_api_key']            = 'N8N API Key';
$lang['topics_n8n_api_key_desc']       = 'Enter your N8N API key';

# Add new language items
$lang['topic_action_buttons'] = 'Topic Action Buttons';
$lang['button_type'] = 'Button Type';
$lang['workflow_id'] = 'Workflow ID';
$lang['target_action_type'] = 'Target Action Type';
$lang['target_action_state'] = 'Target Action State';
$lang['add_button'] = 'Add Button';
$lang['workflow_executed_successfully'] = 'Workflow executed successfully';
$lang['workflow_execution_failed'] = 'Failed to execute workflow';
$lang['missing_required_fields'] = 'Missing required fields';
$lang['select_an_option'] = 'Select an option';
$lang['new_action_button'] = 'New Action Button';
$lang['add_action_button'] = 'Add Action Button';
$lang['processed_values'] = 'Processed Values';
$lang['action_buttons'] = 'Action Buttons';
$lang['load_from_history'] = 'Load from History';

# Action Buttons
$lang['action_buttons'] = 'Action Buttons';
$lang['action_button'] = 'Action Button';
$lang['new_action_button'] = 'New Action Button';
$lang['edit_action_button'] = 'Edit Action Button';
$lang['delete_action_button'] = 'Delete Action Button';
$lang['button_name'] = 'Button Name';
$lang['button_description'] = 'Description';
$lang['button_order'] = 'Order';
$lang['button_status'] = 'Status';
$lang['trigger_type'] = 'Trigger Type';

# Action Button Messages
$lang['action_button_added'] = 'Action button added successfully';
$lang['action_button_updated'] = 'Action button updated successfully';
$lang['action_button_deleted'] = 'Action button deleted successfully';
$lang['action_button_exists'] = 'An action button with this name already exists';
$lang['confirm_delete_action_button'] = 'Are you sure you want to delete this action button?';

# Action Button Types
$lang['button_type_workflow'] = 'Workflow';
$lang['button_type_custom'] = 'Custom';
$lang['button_type_api'] = 'API';

# Action Button States
$lang['button_enabled'] = 'Enabled';
$lang['button_disabled'] = 'Disabled';
$lang['deactivated_buttons'] = 'Deactivated Buttons';
$lang['no_execution_results'] = 'No execution results available';
$lang['error_loading_history'] = 'Error loading history';
$lang['error_parsing_log_data'] = 'Error parsing log data';
$lang['save_and_execute'] = 'Save & Execute';
$lang['execution_results'] = 'Execution Results';
$lang['select_processed_values'] = 'Select Processed Values';
$lang['ignore_types'] = 'Ignore Action Types';
$lang['ignore_states'] = 'Ignore Action States';

# Workflow Execution
$lang['execution_successful'] = 'Execution Successful';
$lang['execution_failed'] = 'Execution Failed';
$lang['executing_workflow'] = 'Executing workflow...';
$lang['execution_results'] = 'Execution Results'; 
$lang['execution_results_help'] = 'Shows the latest workflow execution results';
$lang['no_execution_results'] = 'No execution results to display';
$lang['step'] = 'Step';
$lang['clear'] = 'Clear';

# N8N Integration Messages
$lang['n8n_request_successful'] = 'N8N request executed successfully';
$lang['n8n_request_failed'] = 'Failed to execute N8N request';
$lang['n8n_settings_missing'] = 'N8N integration settings are missing';
$lang['workflow_id_missing'] = 'Workflow ID is missing';
$lang['no_history_data_found'] = 'No history data found';
$lang['link_not_found_in_history'] = 'Link not found in history data';
$lang['action_processing_error'] = 'Error occurred while processing action';
$lang['show_topic_id'] = 'Show Topic ID';
$lang['hide_topic_id'] = 'Hide Topic ID';
$lang['progress_overview'] = 'Progress Overview';
$lang['wordpress_url_found'] = 'WordPress URL found';
$lang['wordpress_url_not_found'] = 'WordPress URL not found';

# Thêm các strings mới
$lang['select_fanpage'] = 'Select Fanpage';
$lang['select_fanpage_and_post_type'] = 'Select Fanpage and Post Type';
$lang['post_type'] = 'Post Type';
$lang['post_type_image'] = 'Image Post';
$lang['post_type_link'] = 'Link Post';
$lang['post_type_carousel'] = 'Carousel Post';
$lang['post_type_slider'] = 'Slider Post';
$lang['error_executing_workflow'] = 'Error executing workflow';
$lang['error_loading_history'] = 'Error loading history';
$lang['error_processing_response'] = 'Error processing response';
$lang['wordpress_post_created'] = 'WordPress Post Created';

$lang['new_controller'] = 'New Controller';
$lang['site'] = 'Site';
$lang['api_url'] = 'API URL';
$lang['api_key'] = 'API Key';

# Controllers
$lang['topic_controllers'] = 'Controllers';
$lang['new_controller'] = 'New Controller';
$lang['controller_details'] = 'Controller Details';
$lang['controller'] = 'Controller';

# Controller Fields
$lang['site'] = 'Site';
$lang['platform'] = 'Platform';
$lang['blog_id'] = 'Blog ID';
$lang['logo_url'] = 'Logo URL';
$lang['slogan'] = 'Slogan';
$lang['writing_style'] = 'Writing Style';
$lang['emails'] = 'Emails';
$lang['api_token'] = 'API Token';
$lang['project_id'] = 'Project ID';
$lang['seo_task_sheet_id'] = 'SEO Task Sheet ID';
$lang['action_1'] = 'Action 1';
$lang['action_2'] = 'Action 2';
$lang['page_mapping'] = 'Page Mapping';

# Related Topics
$lang['related_topics'] = 'Related Topics';
$lang['no_related_topics'] = 'No Related Topics Found';

# Controllers
$lang['edit_controller'] = 'Edit Controller';
$lang['controller_added'] = 'Controller added successfully';
$lang['controller_updated'] = 'Controller updated successfully';
$lang['controller_deleted'] = 'Controller deleted successfully';
$lang['confirm_delete_controller'] = 'Are you sure you want to delete this controller?';

$lang['no_topics_selected'] = 'No topics selected';
$lang['topic_selected'] = 'topic selected';
$lang['topics_selected'] = 'topics selected';

$lang['topics_added_successfully'] = 'Topics added successfully';
$lang['topics_add_failed'] = 'Failed to add topics';
$lang['no_topics_selected'] = 'No topics selected';
$lang['confirm_remove_topics'] = 'Are you sure you want to remove selected topics?';
$lang['topics_removed_successfully'] = 'Topics removed successfully';
$lang['topics_remove_failed'] = 'Failed to remove topics';
$lang['add_topics'] = 'Add Topics';

# Controllers Additional Strings  
$lang['controller_name'] = 'Controller Name';
$lang['controller_description'] = 'Controller Description';
$lang['controller_type'] = 'Controller Type';
$lang['controller_settings'] = 'Controller Settings';
$lang['controller_status'] = 'Controller Status';
$lang['manage_topics'] = 'Manage Topics';
$lang['assign_topics'] = 'Assign Topics';
$lang['remove_topics'] = 'Remove Topics';
$lang['no_available_topics'] = 'No available topics';
$lang['search_available_topics'] = 'Search available topics...';
$lang['topics_assignment'] = 'Topics Assignment';
$lang['confirm_topic_assignment'] = 'Confirm Topics Assignment';
$lang['topics_assignment_success'] = 'Topics assigned successfully';
$lang['topics_assignment_failed'] = 'Failed to assign topics';
$lang['controller_not_found'] = 'Controller not found';
$lang['invalid_controller'] = 'Invalid controller';
$lang['remove_selected'] = 'Remove Selected Topics';

# Confirm Dialog Messages  
$lang['confirm_title'] = 'Confirm';
$lang['confirm_remove_topics'] = 'Are you sure you want to remove selected topics from this controller?';
$lang['confirm_add_topics'] = 'Are you sure you want to add selected topics to this controller?';
$lang['confirm_delete_controller'] = 'Are you sure you want to delete this controller?';
$lang['confirm_reset_controller'] = 'Are you sure you want to reset this controller to its initial state?';
$lang['confirm_bulk_action'] = 'Are you sure you want to perform this action on {count} selected topics?';

$lang['topic_activated'] = 'Topic activated';
$lang['topic_deactivated'] = 'Topic deactivated';

$lang['ignore_action_types'] = 'Ignore Action Types';
$lang['ignore_action_states'] = 'Ignore Action States';
$lang['add_topics_to_controller'] = 'Add Topics to Controller';

$lang['fail_topics'] = 'Fail Topics';
$lang['fail_topics_not_found'] = 'Fail to find topics';

# Post Type Selection
$lang['select_post_type'] = 'Select Post Type';
$lang['available_options'] = 'Available Options';
$lang['top_list_post'] = 'Top List Post';
$lang['top_list_post_desc'] = 'Create a post with numbered list format';
$lang['article_post'] = 'Article Post';
$lang['article_post_desc'] = 'Create a standard article format post';
$lang['please_select_option'] = 'Please select an option';

# Post Options
$lang['audit_post'] = 'Review & Audit Post';
$lang['audit_post_desc'] = 'Review and audit existing post content';
$lang['write_content'] = 'Write Content';
$lang['write_content_desc'] = 'Create new content for the post';
$lang['write_social'] = 'Write Social & Meta';
$lang['write_social_desc'] = 'Create social media content and meta descriptions';

# Add new language strings
$lang['topic_needs_controller'] = 'This topic needs to be assigned to a controller before proceeding';
$lang['select_controller'] = 'Select Controller';
$lang['add_to_controller'] = 'Add to Controller';
$lang['topic_added_to_controller_success'] = 'Topic successfully added to controller';
$lang['topic_added_to_controller_failed'] = 'Failed to add topic to controller';
$lang['controller_info'] = 'Controller Info';

$lang['view_controller'] = 'View Controller';
$lang['toggle_details'] = 'Toggle Details';

# Controller related strings
$lang['add_controller'] = 'Add Controller';
$lang['search_controller'] = 'Search Controller';
$lang['no_controller_assigned'] = 'No controller assigned to this topic';
$lang['select'] = 'Select';
$lang['active'] = 'Active';
$lang['inactive'] = 'Inactive';
$lang['site'] = 'Site';
$lang['platform'] = 'Platform';
$lang['blog_id'] = 'Blog ID';
$lang['logo_url'] = 'Logo URL';
$lang['project_id'] = 'Project ID';
$lang['seo_task_sheet_id'] = 'SEO Task Sheet ID';
$lang['emails'] = 'Emails';
$lang['api_token'] = 'API Token';
$lang['page_mapping'] = 'Page Mapping';
$lang['datecreated'] = 'Date Created';
$lang['dateupdated'] = 'Date Updated';
$lang['slogan'] = 'Slogan';
$lang['writing_style'] = 'Writing Style';
$lang['action_1'] = 'Action 1';
$lang['action_2'] = 'Action 2';

$lang['select_wordpress_post_options'] = 'Select WordPress Post Options';

$lang['preview_post'] = 'Preview Post';
$lang['post_id'] = 'Post ID';

$lang['generate_images'] = 'Generate Images';
$lang['generating'] = 'Generating...';
$lang['image_generation_started'] = 'Image generation process started';
$lang['image_generation_failed'] = 'Failed to start image generation';

$lang['image_generation_listening'] = 'Image generation is listening for results...';
$lang['image_generation_initiated'] = 'Image generation initiated';
$lang['image_generation_completed'] = 'Image generation completed';
$lang['image_generation_completed_no_data'] = 'Image generation completed but no data received';

$lang['workflow_check_failed'] = 'Failed to check workflow status';

$lang['please_select_image'] = 'Please select an image to use';
$lang['image_selection_completed'] = 'Image selection completed';
$lang['no_images_found'] = 'No images were found';

$lang['workflow_info_missing'] = 'Workflow information is missing';

$lang['workflow_data_missing'] = 'Workflow data is missing';
$lang['image_selection_failed'] = 'Failed to process image selection';

$lang['change_controller'] = 'Change Controller';

$lang['api_token_help_text'] = 'API Token dùng để xác thực các request API';
$lang['view_token'] = 'Xem token';
$lang['hide_token'] = 'Ẩn token';
$lang['generate'] = 'Tạo mới';

$lang['action_command'] = 'CLI Command';
$lang['action_command_help_text'] = 'System command to execute (for native trigger type)';

$lang['action_command_required_for_duplicate_target'] = 'Action command is required when multiple buttons share the same Target Action Type and State';

# Topic Composer strings
$lang['topic_composer'] = 'Topic Composer';
$lang['items_list'] = 'Items List';
$lang['editor'] = 'Editor';
$lang['select_item_to_edit'] = 'Select an item to edit';
$lang['save_all'] = 'Save All';
$lang['saving'] = 'Saving...';
$lang['position'] = 'Position';
$lang['content'] = 'Content';
$lang['close'] = 'Close';
$lang['confirm_close'] = 'Are you sure you want to close? Unsaved changes will be lost.';
$lang['error_saving_changes'] = 'Error saving changes';
$lang['processing_topic'] = 'Processing topic...';
$lang['time_remaining'] = 'Time remaining';
$lang['polling_timeout'] = 'Polling timeout';
$lang['polling_error'] = 'Error checking status';

# Changes Summary
$lang['changes_ready_to_apply'] = 'Changes are ready to apply';
$lang['changes_summary'] = 'Changes Summary';
$lang['preview_changes'] = 'Preview Changes';
$lang['apply_changes'] = 'Apply Changes';
$lang['applying'] = 'Applying...';
$lang['content_modified'] = 'Content has been modified';
$lang['error_applying_changes'] = 'Error applying changes';

# Preview Modal
$lang['preview_title'] = 'Preview Changes';
$lang['section_title'] = 'Section Title';
$lang['section_content'] = 'Section Content';
$lang['add_section'] = 'Add Section';
$lang['upload_image'] = 'Upload Image';

# Changes Overview
$lang['items_count'] = 'Items Count';
$lang['modified_items'] = 'Modified Items';
$lang['position_changes'] = 'Position Changes';
$lang['title_changes'] = 'Title Changes';
$lang['content_changes'] = 'Content Changes';
$lang['from'] = 'From';
$lang['to'] = 'To';

# Step 2
$lang['step_2_title'] = 'Step 2: Apply Changes';
$lang['changes_applied'] = 'Changes have been applied successfully';
$lang['error_in_step_2'] = 'Error in step 2';
$lang['processing_changes'] = 'Processing changes...';

# Topic Composer Editor
$lang['confirm_switch_item'] = 'You have unsaved changes. Do you want to switch items without saving?';
$lang['reset_changes'] = 'Reset Changes';
$lang['save_item'] = 'Save Item';
$lang['confirm_reset_changes'] = 'Are you sure you want to reset all changes?';
$lang['item_saved'] = 'Item saved successfully';
$lang['save_all_items'] = 'Save All Items';
$lang['saving_items'] = 'Saving items...';
$lang['items_saved'] = 'All items saved successfully';
$lang['error_saving_items'] = 'Error saving items';
$lang['preview_items'] = 'Preview Items';
$lang['apply_changes'] = 'Apply Changes';
$lang['applying_changes'] = 'Applying changes...';
$lang['changes_applied'] = 'Changes applied successfully';
$lang['error_applying_changes'] = 'Error applying changes';
$lang['unsaved_changes'] = 'Unsaved Changes';
$lang['save_before_close'] = 'Do you want to save changes before closing?';
$lang['discard_changes'] = 'Discard Changes';
$lang['keep_editing'] = 'Keep Editing';

# Editor Toolbar
$lang['format'] = 'Format';
$lang['font_size'] = 'Font Size';
$lang['bold'] = 'Bold';
$lang['italic'] = 'Italic';
$lang['underline'] = 'Underline';
$lang['strikethrough'] = 'Strikethrough';
$lang['align_left'] = 'Align Left';
$lang['align_center'] = 'Align Center';
$lang['align_right'] = 'Align Right';
$lang['align_justify'] = 'Justify';
$lang['bullet_list'] = 'Bullet List';
$lang['number_list'] = 'Number List';
$lang['decrease_indent'] = 'Decrease Indent';
$lang['increase_indent'] = 'Increase Indent';
$lang['insert_link'] = 'Insert Link';
$lang['insert_image'] = 'Insert Image';
$lang['insert_media'] = 'Insert Media';
$lang['remove_formatting'] = 'Remove Formatting';
$lang['help'] = 'Help';

# Editor Messages
$lang['content_copied'] = 'Content copied to clipboard';
$lang['paste_as_text'] = 'Paste as text';
$lang['word_count'] = 'Word Count';
$lang['character_count'] = 'Character Count';

# AI Writing Styles
$lang['select_writing_style'] = 'Select Writing Style';
$lang['enter_custom_prompt'] = 'Enter your custom writing instruction...';

# Writing Styles
$lang['write_detailed'] = 'Write Detailed';
$lang['write_detailed_desc'] = 'Expand content with examples and detailed explanations';

$lang['write_concise'] = 'Write Concise';
$lang['write_concise_desc'] = 'Summarize and keep only key important points';

$lang['write_engaging'] = 'Write Engaging';
$lang['write_engaging_desc'] = 'Make content more interesting and captivating';

$lang['write_academic'] = 'Write Academic';
$lang['write_academic_desc'] = 'Use scholarly language and professional citations';

$lang['write_simple'] = 'Write Simple';
$lang['write_simple_desc'] = 'Use simple language that everyone can understand';

$lang['write_storytelling'] = 'Write as Story';
$lang['write_storytelling_desc'] = 'Transform content into an engaging narrative';

$lang['write_professional'] = 'Write Professional';
$lang['write_professional_desc'] = 'Use business-appropriate tone and language';

$lang['write_persuasive'] = 'Write Persuasive';
$lang['write_persuasive_desc'] = 'Create compelling content that drives action';

$lang['write_friendly'] = 'Write Friendly';
$lang['write_friendly_desc'] = 'Use casual, conversational tone';

$lang['write_seo'] = 'Write for SEO';
$lang['write_seo_desc'] = 'Optimize content for search engines';

$lang['write_creative'] = 'Write Creative';
$lang['write_creative_desc'] = 'Add unique and imaginative elements';

$lang['write_technical'] = 'Write Technical';
$lang['write_technical_desc'] = 'Focus on technical details and specifications';

$lang['write_emotional'] = 'Write Emotional';
$lang['write_emotional_desc'] = 'Create content that connects emotionally';

$lang['write_journalistic'] = 'Write Journalistic';
$lang['write_journalistic_desc'] = 'Use objective, news-style reporting';

$lang['write_custom'] = 'Custom Style';
$lang['write_custom_desc'] = 'Write your own custom instructions';

# AI Messages
$lang['please_enter_custom_prompt'] = 'Please enter your custom writing instructions';
$lang['processing_content'] = 'Processing content...';
$lang['generating_variations'] = 'Generating variations...';
$lang['ai_edit_success'] = 'Content updated successfully';
$lang['ai_edit_error'] = 'Failed to update content';
$lang['ai_search_error'] = 'Search failed';
$lang['ai_service_error'] = 'AI service is currently unavailable';

$lang['max_word_limit'] = 'Maximum Word Limit: ';
$lang['words'] = 'Words';

$lang['ai_edit_success'] = 'Content updated successfully';
$lang['ai_edit_error'] = 'Failed to update content';
$lang['ai_search_error'] = 'Search failed';
$lang['ai_service_error'] = 'AI service is currently unavailable';

# AI Search Questions
$lang['sample_questions'] = 'Sample Questions';

$lang['verify_content'] = 'Verify Content';
$lang['verify_content_desc'] = 'Verify if this content is accurate and factual';

$lang['fact_check'] = 'Fact Check';
$lang['fact_check_desc'] = 'Check the accuracy of specific facts and claims';

$lang['find_source'] = 'Find Sources';
$lang['find_source_desc'] = 'Find reliable sources and references for this content';

$lang['find_similar'] = 'Find Similar Content';
$lang['find_similar_desc'] = 'Find similar or related content on this topic';

$lang['find_references'] = 'Find References';
$lang['find_references_desc'] = 'Search for academic or authoritative references';

$lang['expert_opinion'] = 'Expert Opinion';
$lang['expert_opinion_desc'] = 'Find expert analysis and opinions on this topic';

$lang['custom_question'] = 'Custom Question';
$lang['custom_question_desc'] = 'Ask your own specific question about this content';

# AI Search Related
$lang['enter_custom_question'] = 'Enter your custom question...';
$lang['use_this_question'] = 'Use This Question';
$lang['searching'] = 'Searching...';
$lang['please_enter_question'] = 'Please enter your question';
$lang['search_results'] = 'Search Results';
$lang['use_this'] = 'Use This';
$lang['content_updated'] = 'Content has been updated';


$lang['ai_edit'] = 'AI Edit';
$lang['ai_search'] = 'AI Search';

$lang['generate_from_content'] = 'Generate from Content';
$lang['generate_from_content_desc'] = 'Generate new content based on existing text while maintaining key points';

$lang['confirm_delete_selected_items'] = 'Are you sure you want to delete the selected items?';
$lang['delete_selected'] = 'Delete Selected Items';


$lang['images'] = 'Images';
$lang['keywords'] = 'Keywords';
$lang['save_item'] = 'Save Item';
$lang['reset_changes'] = 'Reset Changes';
$lang['save_all'] = 'Save All';

$lang['select_empty'] = 'Select Empty';
$lang['select_empty_items'] = 'Quick select empty items';
$lang['found_empty_items'] = 'Found empty items';
$lang['no_empty_items_found'] = 'No empty items found';

# Topic Config related
$lang['topic_config'] = 'Topic Configuration';
$lang['title_required'] = 'Title is required';
$lang['topic_required'] = 'Topic is required';
$lang['please_fill_required_fields'] = 'Please fill in all required fields';
$lang['saved_successfully'] = 'Saved successfully';
$lang['save_failed'] = 'Save failed';
$lang['quick_save'] = 'Quick Save';
$lang['quick_save_desc'] = 'Save changes immediately';
$lang['quick_save_success'] = 'Quick save successful';
$lang['quick_save_error'] = 'Quick save failed';
$lang['processing_save'] = 'Processing save...';
$lang['config_updated'] = 'Configuration updated';
$lang['config_update_failed'] = 'Failed to update configuration';

$lang['neutralize_content'] = 'Neutralize Content';
$lang['neutralize_content_desc'] = 'Rewrite content in a neutral way, removing brand mentions while preserving value';

$lang['quick_set_position'] = 'Quick set position';
$lang['position_updated'] = 'Position updated';

$lang['default_commands'] = 'Default Commands';
$lang['always_return_html'] = 'Always return HTML';
$lang['no_markdown_return'] = 'Do not return Markdown';
$lang['no_json_return'] = 'Return exact result only (no JSON)';

$lang['external_data'] = 'External Data';
$lang['external_data_saved'] = 'External data saved successfully';
$lang['external_data_deleted'] = 'External data deleted successfully';
$lang['external_data_not_found'] = 'External data not found';
$lang['external_data_exists'] = 'External data already exists';
$lang['external_data_failed'] = 'Failed to save external data';

$lang['find_similar_images'] = 'Find Similar Images';
$lang['download_to_server'] = 'Download to Server';
$lang['image_already_downloaded'] = 'Image already downloaded to server';
$lang['finding_similar_images'] = 'Finding Similar Images';
$lang['please_wait'] = 'Please wait...';
$lang['error_finding_similar_images'] = 'Error finding similar images';
$lang['similar_images'] = 'Similar Images';
$lang['image_downloaded_successfully'] = 'Image downloaded successfully';
$lang['error_downloading_image'] = 'Error downloading image';
$lang['download'] = 'Download';

$lang['copy_image_url'] = 'Copy Image URL';
$lang['url_copied_to_clipboard'] = 'URL copied to clipboard';
$lang['failed_to_copy_url'] = 'Failed to copy URL';

$lang['image_downloaded_from_server'] = 'Image downloaded from server';
$lang['image_downloaded_from_server_desc'] = 'Image downloaded from server';

$lang['include_uploaded_images'] = 'Include Uploaded Images';
$lang['select_images_to_include'] = 'Select images to include in the generated content';
$lang['loading_images'] = 'Loading images...';
$lang['no_uploaded_images'] = 'No uploaded images available';

$lang['url_override'] = 'URL Override';
$lang['url_override_desc'] = 'Override URLs found in content';
$lang['original_url'] = 'Original URL';
$lang['override_url'] = 'Override URL';
$lang['enter_override_url'] = 'Enter URL to override with';
$lang['scanning_urls'] = 'Scanning URLs...';
$lang['no_urls_found'] = 'No URLs found in content';

$lang['shorten_url'] = 'Shorten URL';
$lang['shortened'] = 'Shortened';
$lang['shortening_failed'] = 'URL shortening failed';
$lang['actions'] = 'Actions';

$lang['checking_urls'] = 'Checking URLs...';

$lang['total_items'] = 'Total Items';
$lang['position_changes'] = 'Position Changes';
$lang['title_changes'] = 'Title Changes';
$lang['content_changes'] = 'Content Changes';
$lang['added_items'] = 'Added Items';
$lang['deleted_items'] = 'Deleted Items';

# Debug Panel Settings
$lang['topics_debug_settings'] = 'Debug Panel Settings';
$lang['topics_enable_debug_panel'] = 'Enable Debug Panel';
$lang['topics_debug_panel_enabled'] = 'Debug Panel';

/* Draft Writer Language Strings */
$lang['draft_writer'] = 'Draft Writer';
$lang['error_loading_draft_writer'] = 'Error loading Draft Writer';
$lang['saved_draft_found'] = 'Saved Draft Found';
$lang['saved_draft_found_message'] = 'A previously saved draft was found for this topic. Would you like to use it or start fresh?';
$lang['no_title'] = 'No Title';
$lang['no_description'] = 'No Description';
$lang['no_content'] = 'No Content';
$lang['preview_old_saved'] = 'Preview Saved Draft';
$lang['reuse'] = 'Use Saved Draft';
$lang['reload'] = 'Start Fresh';
$lang['preview_saved_draft'] = 'Preview Saved Draft';
$lang['use_this_draft'] = 'Use This Draft';
$lang['use_new_content'] = 'Use New Content';
$lang['unknown'] = 'Unknown';
$lang['seconds_ago'] = 'seconds ago';
$lang['minutes_ago'] = 'minutes ago';
$lang['hours_ago'] = 'hours ago';
$lang['days_ago'] = 'days ago';
$lang['draft_loaded_from_local_storage'] = 'Draft loaded from local storage';
$lang['draft_saved'] = 'Draft saved successfully';
$lang['error_saving_draft'] = 'Error saving draft';
$lang['editor_not_initialized'] = 'Editor not initialized';
$lang['title_required'] = 'Title is required';
$lang['content_required'] = 'Content is required';
$lang['confirm_publish_draft'] = 'Are you sure you want to publish this draft?';
$lang['error_publishing_draft'] = 'Error publishing draft';
$lang['invalid_content_type'] = 'Invalid content type';
$lang['no_content_to_improve'] = 'No content to improve';
$lang['content_improved'] = 'Content improved successfully';
$lang['original_length'] = 'Original length';
$lang['improved_length'] = 'Improved length';
$lang['error_improving_content'] = 'Error improving content';
$lang['enter_search_query'] = 'Enter search query';
$lang['select_result_to_insert'] = 'Please select a result to insert';
$lang['search_failed'] = 'Search failed';
$lang['no_results_found'] = 'No results found';
$lang['no_seo_suggestions_available'] = 'No SEO suggestions available';
$lang['title_preview'] = 'Your Title Will Appear Here';
$lang['description_preview'] = 'Your meta description will appear here. It should be between 120-160 characters for optimal SEO performance.';
$lang['characters'] = 'characters';
$lang['detailed_stats'] = 'Detailed Statistics';
$lang['word_count'] = 'Word Count';
$lang['title_length'] = 'Title Length';
$lang['description_length'] = 'Description Length';
$lang['keyword_density'] = 'Keyword Density';
$lang['image_count'] = 'Image Count';
$lang['internal_links'] = 'Internal Links';
$lang['heading_structure'] = 'Heading Structure';
$lang['auto_save'] = 'Auto Save';
$lang['never_saved'] = 'Never saved';
$lang['save_draft'] = 'Save Draft';
$lang['publish'] = 'Publish';
$lang['close'] = 'Close';
$lang['ai_search'] = 'AI Search';
$lang['search'] = 'Search';
$lang['insert_selected'] = 'Insert Selected';

/* SEO Analysis Strings */
$lang['title_missing'] = 'Title is missing';
$lang['title_too_short'] = 'Title is too short (less than 30 characters)';
$lang['title_too_long'] = 'Title is too long (more than 60 characters)';
$lang['title_good_length'] = 'Title length is good (between 30-60 characters)';
$lang['description_missing'] = 'Meta description is missing';
$lang['description_too_short'] = 'Meta description is too short (less than 120 characters)';
$lang['description_too_long'] = 'Meta description is too long (more than 160 characters)';
$lang['description_good_length'] = 'Meta description length is good (between 120-160 characters)';
$lang['content_too_short'] = 'Content is too short (less than 300 words)';
$lang['content_good_length'] = 'Content length is good (more than 300 words)';
$lang['keyword_density_too_low'] = 'Keyword density is too low (less than 0.5%)';
$lang['keyword_density_too_high'] = 'Keyword density is too high (more than 3%)';
$lang['keyword_density_good'] = 'Keyword density is good (between 0.5-3%)';
$lang['keyword_in_title'] = 'Target keyword found in title';
$lang['keyword_not_in_title'] = 'Target keyword not found in title';
$lang['keyword_in_description'] = 'Target keyword found in meta description';
$lang['keyword_not_in_description'] = 'Target keyword not found in meta description';
$lang['no_target_keyword'] = 'No target keyword specified';

/* Draft Writer UI Strings */
$lang['outline'] = 'Outline';
$lang['stats'] = 'Statistics';
$lang['keywords'] = 'Keywords';
$lang['seo'] = 'SEO';
$lang['content_outline'] = 'Content Outline';
$lang['no_headings_yet'] = 'No headings yet';
$lang['content_statistics'] = 'Content Statistics';
$lang['reading_time'] = 'Reading Time';
$lang['heading_count'] = 'Heading Count';
$lang['sentence_count'] = 'Sentence Count';
$lang['avg_sentence_length'] = 'Avg. Sentence Length';
$lang['readability'] = 'Readability';
$lang['readability_score'] = 'Readability Score';
$lang['flesch_reading_ease'] = 'Flesch Reading Ease';
$lang['flesch_kincaid_grade'] = 'Flesch-Kincaid Grade';
$lang['keyword_cloud'] = 'Keyword Cloud';
$lang['keyword_density'] = 'Keyword Density';
$lang['seo_suggestions'] = 'SEO Suggestions';
$lang['seo_score'] = 'SEO Score';
$lang['seo_checklist'] = 'SEO Checklist';
$lang['search_preview'] = 'Search Preview';
$lang['seo_status'] = 'SEO Status';
$lang['too_short'] = 'Too Short';
$lang['too_long'] = 'Too Long';
$lang['good'] = 'Good';
$lang['analyzing_content'] = 'Analyzing content...';

/* SEO Error Messages */
$lang['seo_error_no_title'] = 'No title has been set';
$lang['seo_warning_title_short'] = 'Title is too short (less than 30 characters)';
$lang['seo_warning_title_long'] = 'Title is too long (more than 60 characters)';
$lang['seo_good_title_length'] = 'Title length is good';
$lang['seo_error_no_description'] = 'No meta description has been set';
$lang['seo_warning_description_short'] = 'Meta description is too short (less than 120 characters)';
$lang['seo_warning_description_long'] = 'Meta description is too long (more than 160 characters)';
$lang['seo_good_description_length'] = 'Meta description length is good';
$lang['seo_error_content_short'] = 'Content is too short (less than 300 words)';
$lang['seo_warning_content_medium'] = 'Content could be longer (less than 600 words)';
$lang['seo_good_content_length'] = 'Content length is good';
$lang['seo_error_no_headings'] = 'No headings found in content';
$lang['seo_warning_no_h1'] = 'No H1 heading found in content';
$lang['seo_warning_no_h2'] = 'No H2 headings found in content';
$lang['seo_good_heading_structure'] = 'Good heading structure';
$lang['seo_warning_keyword_density_low'] = 'Keyword density is too low';
$lang['seo_warning_keyword_density_high'] = 'Keyword density is too high';
$lang['seo_good_keyword_density'] = 'Keyword density is good';
$lang['seo_warning_no_images'] = 'No images found in content';
$lang['seo_warning_images_missing_alt'] = 'Some images are missing alt text';
$lang['seo_good_images_with_alt'] = 'All images have alt text';
$lang['seo_warning_no_links'] = 'No internal links found in content';
$lang['seo_good_keyword_in_title'] = 'Target keyword found in title';
$lang['seo_warning_keyword_not_in_title'] = 'Target keyword not found in title';
$lang['seo_good_keyword_in_description'] = 'Target keyword found in meta description';
$lang['seo_warning_keyword_not_in_description'] = 'Target keyword not found in meta description';

/* Draft Writer Editor Strings */
$lang['post_title'] = 'Post Title';
$lang['enter_title'] = 'Enter title';
$lang['ai_edit_title'] = 'AI Edit Title';
$lang['ai_edit'] = 'AI Edit';
$lang['meta_description'] = 'Meta Description';
$lang['enter_meta_description'] = 'Enter meta description';
$lang['ai_edit_description'] = 'AI Edit Description';
$lang['content'] = 'Content';
$lang['ai_edit_content'] = 'AI Edit Content';
$lang['ai_search'] = 'AI Search';
$lang['ai_improve'] = 'AI Improve';
$lang['tags'] = 'Tags';
$lang['enter_tags'] = 'Enter tags (comma separated)';
$lang['separate_tags_with_commas'] = 'Separate tags with commas';
$lang['ai_suggest_tags'] = 'AI Suggest Tags';
$lang['suggest'] = 'Suggest';
$lang['category'] = 'Category';
$lang['select_category'] = 'Select category';
$lang['featured_image'] = 'Featured Image';
$lang['click_to_upload_image'] = 'Click to upload image';
$lang['remove'] = 'Remove';
$lang['quick_save'] = 'Quick Save';
$lang['last_saved'] = 'Last saved';
$lang['toggle_fullscreen'] = 'Toggle Fullscreen';
$lang['toggle_html'] = 'Toggle HTML';
$lang['insert_image'] = 'Insert Image';
$lang['insert_table'] = 'Insert Table';
$lang['insert_link'] = 'Insert Link';
$lang['rewrite_selection'] = 'Rewrite Selection';
$lang['improve_selection'] = 'Improve Selection';
$lang['fact_check_selection'] = 'Fact Check Selection';
$lang['expand_selection'] = 'Expand Selection';
$lang['rewrite'] = 'Rewrite';
$lang['improve'] = 'Improve';
$lang['fact_check'] = 'Fact Check';
$lang['expand'] = 'Expand';
$lang['bold'] = 'Bold';
$lang['italic'] = 'Italic';
$lang['underline'] = 'Underline';
$lang['strikethrough'] = 'Strikethrough';
$lang['heading'] = 'Heading';
$lang['heading_1'] = 'Heading 1';
$lang['heading_2'] = 'Heading 2';
$lang['heading_3'] = 'Heading 3';
$lang['heading_4'] = 'Heading 4';
$lang['heading_5'] = 'Heading 5';
$lang['heading_6'] = 'Heading 6';
$lang['paragraph'] = 'Paragraph';
$lang['align_left'] = 'Align Left';
$lang['align_center'] = 'Align Center';
$lang['align_right'] = 'Align Right';
$lang['align_justify'] = 'Align Justify';
$lang['bullet_list'] = 'Bullet List';
$lang['number_list'] = 'Number List';
$lang['decrease_indent'] = 'Decrease Indent';
$lang['increase_indent'] = 'Increase Indent';
$lang['insert_horizontal_rule'] = 'Insert Horizontal Rule';
$lang['ai_rewrite'] = 'AI Rewrite';
$lang['more_ai_options'] = 'More AI Options';
$lang['shorten'] = 'Shorten';
$lang['simplify'] = 'Simplify';
$lang['make_professional'] = 'Make Professional';
$lang['make_casual'] = 'Make Casual';
$lang['link'] = 'Link';
$lang['image'] = 'Image';
$lang['table'] = 'Table';

/* WRITE_DRAFT Action Command */
$lang['write_draft'] = 'Write Draft';
$lang['write_draft_desc'] = 'Create a draft from the topic content';

/* Draft Writer - Keyword Analysis */
$lang['min_read'] = 'min read';
$lang['count'] = 'Count';
$lang['density'] = 'Density';
$lang['score'] = 'SEO Score';
$lang['distribution'] = 'Distribution';
$lang['recommendations'] = 'Recommendations';
$lang['no_keywords_found'] = 'No keywords found';
$lang['keyword_analysis_info'] = 'Keyword density analysis based on Semrush standards';
$lang['optimal_density'] = 'Optimal density';
$lang['low_density'] = 'Low density';
$lang['very_low_density'] = 'Very low density';
$lang['no_recommendations'] = 'No recommendations needed';
$lang['suggestions'] = 'suggestions';

/* Keyword Analysis Recommendations */
$lang['keyword_density_too_low'] = 'Keyword density is too low (below 0.5%)';
$lang['keyword_density_too_high'] = 'Keyword density is too high (above 3%)';
$lang['add_keyword_to_title'] = 'Add keyword to the title';
$lang['add_keyword_to_first_paragraph'] = 'Add keyword to the first paragraph';
$lang['add_keyword_to_headings'] = 'Add keyword to one or more headings';
$lang['add_keyword_to_beginning'] = 'Add keyword near the beginning of content';
$lang['improve_keyword_distribution'] = 'Improve keyword distribution throughout content';

/* SEO Analysis */
$lang['detailed_stats'] = 'Detailed Statistics';
$lang['title_length'] = 'Title Length';
$lang['description_length'] = 'Description Length';
$lang['image_count'] = 'Image Count';
$lang['internal_links'] = 'Internal Links';
$lang['heading_structure'] = 'Heading Structure';

/* Status Messages */
$lang['analyzing_content'] = 'Analyzing content...';
$lang['found_in_title'] = 'Found in title';
$lang['found_in_first_paragraph'] = 'Found in first paragraph';
$lang['found_in_headings'] = 'Found in headings';
$lang['section'] = 'Section';
$lang['occurrences'] = 'occurrences';

$lang['keyword_analysis'] = 'Keyword Analysis';

# Batch Title Generator
$lang['batch_generate_titles'] = 'Batch Generate Titles';
$lang['word_limit'] = 'Word Limit';
$lang['recommended_between_5_20'] = 'Recommended between 5-20 words';
$lang['return_html'] = 'Return HTML';
$lang['items_to_process'] = 'Items to Process';
$lang['all_items'] = 'All Items';
$lang['selected_items'] = 'Selected Items';
$lang['batch_titles_info'] = 'This will generate titles for multiple items using AI. The process may take some time depending on the number of items.';
$lang['start_generation'] = 'Start Generation';
$lang['generating_titles'] = 'Generating Titles...';
$lang['cancel'] = 'Cancel';
$lang['confirm_cancel_batch_generation'] = 'Are you sure you want to cancel the batch generation?';
$lang['batch_generation_cancelled'] = 'Batch generation cancelled';
$lang['batch_generation_completed'] = 'Batch Generation Completed';
$lang['processed_items'] = 'Processed Items';
$lang['dismiss'] = 'Dismiss';
$lang['batch_title_generation_completed'] = 'Batch title generation completed successfully';
$lang['please_select_at_least_one_item'] = 'Please select at least one item';
$lang['changes_saved_successfully'] = 'Changes saved successfully';
$lang['saving_changes'] = 'Saving changes...';

# Auto Reposition
$lang['auto_reposition'] = 'Auto Reposition';
$lang['repositioning_completed'] = 'Repositioning completed';
$lang['repositioning_items'] = 'Repositioning items...';
$lang['confirm_auto_reposition'] = 'This will renumber all items in sequential order. Continue?';

# Bulk Edit Content
$lang['bulk_edit_content'] = 'Bulk Edit Content';
$lang['bulk_edit_content_desc'] = 'This will process the content of multiple items using AI.';
$lang['content_style'] = 'Content Style';
$lang['detailed'] = 'Detailed';
$lang['concise'] = 'Concise';
$lang['creative'] = 'Creative';
$lang['professional'] = 'Professional';
$lang['generating_content'] = 'Generating Content...';
$lang['batch_content_generation_completed'] = 'Batch content generation completed successfully';
$lang['content_improvement_info'] = 'Select a style to improve the content of selected items. The process may take some time depending on the number of items.';

# Custom Instructions and Optimization Options
$lang['custom_instructions'] = 'Custom Instructions';
$lang['enter_custom_instructions'] = 'Enter additional instructions for content generation...';
$lang['custom_instructions_desc'] = 'Custom instructions will be added to the AI prompt for more specific content requirements.';
$lang['optimization_options'] = 'Optimization Options';
$lang['optimize_for_seo'] = 'Optimize for SEO';
$lang['remove_external_links'] = 'Remove external links and navigation';
$lang['optimize_paragraph_length'] = 'Optimize paragraph length for web readers';
$lang['add_subheadings'] = 'Add logical subheadings (h2, h3)';
$lang['add_call_to_action'] = 'Add call-to-action at the end';
$lang['insert_images_to_item'] = 'Insert downloaded images to content';
$lang['modified_fields'] = 'Modified Fields';

$lang['controller_selection'] = 'Controller Selection';
$lang['select_controller_to_preview'] = 'Select a controller to preview';
$lang['controller_preview'] = 'Controller Preview';
$lang['controller_selected'] = 'Controller Selected';
$lang['no_controllers_available_for_this_topic'] = 'No controllers available for this topic';
$lang['error_loading_controllers'] = 'Error loading controllers';
$lang['error_parsing_server_response'] = 'Error parsing server response';

# Add missing strings for controller form and connection status
$lang['edit_mode']                      = 'Edit Mode';
$lang['configuration_loaded']           = 'Configuration Loaded';
$lang['login_configuration']            = 'Login Configuration';
$lang['site_url']                       = 'Site URL';
$lang['description']                    = 'Description';
$lang['categories']                     = 'Categories';
$lang['posts']                          = 'Posts';
$lang['pages']                          = 'Pages';
$lang['wordpress_info']                 = 'WordPress Information';
$lang['haravan_info']                   = 'Haravan Information';
$lang['site_information']               = 'Site Information';
$lang['quick_save_login']               = 'Quick Save Login';
$lang['save_login_credentials_help']    = 'Save login credentials without submitting the entire form';
$lang['testing_connection']             = 'Testing connection';
$lang['testing']                        = 'Testing';
$lang['test_connection']                = 'Test Connection';
$lang['connection_successful']          = 'Connection successful';
$lang['connection_failed']              = 'Connection failed';
$lang['error_testing_connection']       = 'Error testing connection';
$lang['username']                       = 'Username';
$lang['password']                       = 'Password';
$lang['application_password']           = 'Application Password';
$lang['successfully_saved']             = 'Successfully saved';
$lang['error_saving']                   = 'Error saving';

# Tags and Publish related
$lang['draft_tags'] = 'Draft Tags';
$lang['new_tags'] = 'New Tags';
$lang['enter_new_tag'] = 'Enter new tag';
$lang['popular_tags'] = 'Popular Tags';
$lang['no_tags_available'] = 'No tags available';
$lang['enter_tags_comma_separated'] = 'Enter tags, separated by commas';
$lang['separate_tags_with_commas'] = 'Separate tags with commas';
$lang['add'] = 'Add';
$lang['error_loading_tags'] = 'Error loading tags';

# Action Button Controller Only Option
$lang['controller_only'] = 'Controller Only';
$lang['controller_only_help_text'] = 'If checked, this button will only be shown in Controllers and Ultimate Editor, not in topic detail view.';

# Bulk Download Images
$lang['bulk_download_images'] = 'Bulk Download Images';
$lang['downloading_images'] = 'Downloading images...';
$lang['download_image'] = 'Download Image';
$lang['downloading_image'] = 'Downloading image...';
$lang['downloading_image_success'] = 'Image downloaded successfully';
$lang['downloading_image_error'] = 'Error downloading image';
$lang['no_images_to_download'] = 'No images to download';
$lang['found_images_to_download'] = 'Found %s images to download';
$lang['all_images_already_downloaded'] = 'All images have already been downloaded';
$lang['bulk_download_images_downloaded'] = 'Bulk images downloaded';
$lang['images_downloading_completed'] = 'Images downloading completed';
$lang['images_processed'] = 'Images processed';
$lang['images_downloaded'] = 'Images downloaded';
$lang['images_failed'] = 'Images failed';
$lang['downloading_progress'] = 'Downloading progress';

$lang['detailed'] = 'Detailed';
$lang['concise'] = 'Concise';
$lang['creative'] = 'Creative';
$lang['professional'] = 'Professional';
$lang['conversational'] = 'Conversational';
$lang['storytelling'] = 'Storytelling';
$lang['technical'] = 'Technical';
$lang['academic'] = 'Academic';
$lang['persuasive'] = 'Persuasive';
$lang['instructional'] = 'Instructional';

# Auto Position with Custom Prefix
$lang['select_position_prefix'] = 'Select Position Prefix';
$lang['common_prefixes'] = 'Common Prefixes';
$lang['custom_prefix'] = 'Custom Prefix';
$lang['enter_custom_prefix'] = 'Enter your custom prefix';
$lang['preview'] = 'Preview';
$lang['advanced_options'] = 'Advanced Options';
$lang['add_space_after_prefix'] = 'Add space after prefix';
$lang['apply'] = 'Apply';

# Clone controller functionality
$lang['clone'] = 'Clone';
$lang['controller_cloned_successfully'] = 'Controller cloned successfully';
$lang['controller_clone_failed'] = 'Failed to clone controller';

# Category tree search
$lang['search_categories'] = 'Search categories...';

# Topic Updated Notification
$lang['topic_updated_notification'] = 'Topic Updated:';