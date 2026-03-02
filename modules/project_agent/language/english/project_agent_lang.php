<?php

# Module
$lang['project_agent'] = 'Project Agent';
$lang['project_agent_module_name'] = 'Project Agent';
$lang['project_agent_description'] = 'AI Assistant for project management with chat-to-operate interface';

# Permissions
$lang['permission_view'] = 'View';
$lang['permission_execute_safe'] = 'Execute Safe Actions';
$lang['permission_execute_financial'] = 'Execute Financial Actions';
$lang['permission_admin'] = 'Admin';

# AI Room
$lang['ai_room'] = 'AI Room';
$lang['ai_room_title'] = 'AI Room - Project Management Assistant';
$lang['ai_room_subtitle'] = 'Chat with AI to manage your projects';
$lang['ai_room_placeholder'] = 'Type your request or paste content here...';
$lang['ai_room_send'] = 'Send';
$lang['ai_room_dry_run'] = 'Dry Run';
$lang['ai_room_run_safe'] = 'Run Safe Only';
$lang['ai_room_run_all'] = 'Run All';

# Quick Actions
$lang['quick_action_create_tasks'] = 'Create Tasks from Text';
$lang['quick_action_check_billing'] = 'Check Billing';
$lang['quick_action_work_remaining'] = 'Work Remaining';
$lang['quick_action_check_links'] = 'Check Data Links';

# Context Panel
$lang['context_project_overview'] = 'Project Overview';
$lang['context_milestones'] = 'Milestones';
$lang['context_billing_snapshot'] = 'Billing Snapshot';
$lang['context_health_indicators'] = 'Health Indicators';
$lang['context_unbilled_hours'] = 'Unbilled Hours';
$lang['context_unbilled_expenses'] = 'Unbilled Expenses';
$lang['context_overdue_invoices'] = 'Overdue Invoices';

# Health Check
$lang['project_agent_health_check'] = 'Health Check';
$lang['health_check_title'] = 'Project Agent Health Check';
$lang['health_check_database_tables'] = 'Database Tables';
$lang['health_check_module_status'] = 'Module Status';
$lang['health_check_ai_provider'] = 'AI Provider Status';
$lang['health_check_database_version'] = 'Database Version';
$lang['health_check_actions'] = 'Actions';
$lang['health_check_refresh'] = 'Refresh';
$lang['health_check_create_tables'] = 'Create Missing Tables';

# Memory Timeline
$lang['memory_timeline'] = 'Memory Timeline';
$lang['memory_input'] = 'Input';
$lang['memory_analysis'] = 'Analysis';
$lang['memory_plan'] = 'Plan';
$lang['memory_action'] = 'Action';
$lang['memory_result'] = 'Result';
$lang['memory_observation'] = 'Observation';
$lang['memory_warning'] = 'Warning';
$lang['memory_next_step'] = 'Next Step';

# Plan Drawer
$lang['plan_drawer'] = 'Plan Drawer';
$lang['plan_reasoning'] = 'Reasoning';
$lang['plan_steps'] = 'Steps';
$lang['plan_diff_preview'] = 'Diff Preview';
$lang['plan_goal'] = 'Goal';
$lang['plan_assumptions'] = 'Assumptions';
$lang['plan_risks'] = 'Risks';
$lang['plan_confidence'] = 'Confidence';

# Execution Console
$lang['execution_console'] = 'Execution Console';
$lang['execution_queued'] = 'Queued';
$lang['execution_running'] = 'Running';
$lang['execution_success'] = 'Success';
$lang['execution_failed'] = 'Failed';
$lang['execution_needs_confirm'] = 'Needs Confirm';
$lang['execution_retry'] = 'Retry';
$lang['execution_skip'] = 'Skip';

# Actions
$lang['action_create_project'] = 'Create Project';
$lang['action_create_task'] = 'Create Task';
$lang['action_check_billing'] = 'Check Billing Status';
$lang['action_summarize_work'] = 'Summarize Work Remaining';
$lang['action_invoice_timesheets'] = 'Invoice Timesheets';
$lang['action_invoice_expenses'] = 'Invoice Expenses';
$lang['action_create_estimate'] = 'Create Estimate';
$lang['action_create_invoice'] = 'Create Invoice';

# Risk Levels
$lang['risk_low'] = 'Low';
$lang['risk_medium'] = 'Medium';
$lang['risk_high'] = 'High';

# Status Messages
$lang['status_connected'] = 'Connected';
$lang['status_disconnected'] = 'Disconnected';
$lang['status_processing'] = 'Processing...';
$lang['status_completed'] = 'Completed';
$lang['status_error'] = 'Error';

# Notifications
$lang['notification_action_completed'] = 'Action completed successfully';
$lang['notification_action_failed'] = 'Action failed';
$lang['notification_plan_created'] = 'Plan created';
$lang['notification_memory_updated'] = 'Memory updated';

# Errors
$lang['error_permission_denied'] = 'Permission denied';
$lang['error_invalid_action'] = 'Invalid action';
$lang['error_missing_params'] = 'Missing required parameters';
$lang['error_action_failed'] = 'Action execution failed';
$lang['error_session_not_found'] = 'Session not found';
$lang['error_project_not_found'] = 'Project not found';

# Success Messages
$lang['success_action_executed'] = 'Action executed successfully';
$lang['success_plan_created'] = 'Plan created successfully';
$lang['success_memory_saved'] = 'Memory saved successfully';
$lang['success_session_created'] = 'Session created successfully';

# Settings
$lang['settings_ai_room_enabled'] = 'Enable AI Room';
$lang['settings_auto_confirm_threshold'] = 'Auto Confirm Threshold';
$lang['settings_memory_retention_days'] = 'Memory Retention (Days)';
$lang['settings_max_concurrent_sessions'] = 'Max Concurrent Sessions';
$lang['settings_default_risk_level'] = 'Default Risk Level';
$lang['settings_debug_enabled'] = 'Enable Debug Mode';

# Help
$lang['help_ai_room'] = 'AI Room allows you to interact with the system using natural language. You can ask questions, request actions, and get intelligent responses.';
$lang['help_quick_actions'] = 'Quick actions provide common tasks that you can execute with a single click.';
$lang['help_plan_drawer'] = 'The plan drawer shows the AI\'s reasoning and planned actions before execution.';
$lang['help_execution_console'] = 'The execution console shows the real-time status of action execution.';

# Overview Page
$lang['overview_title'] = 'Project Agent';
$lang['overview_description'] = 'Smart AI assistant to help manage projects, tasks, invoices and more inside Perfex CRM.';
$lang['overview_db_version'] = 'DB Version';
$lang['overview_ai_provider'] = 'AI Provider';
$lang['overview_ai_available'] = 'Available';
$lang['overview_ai_not_available'] = 'Not available';
$lang['overview_open_ai_room'] = 'Open AI Room';
$lang['overview_projects'] = 'Projects';
$lang['overview_actions'] = 'Actions';
$lang['overview_general_configuration'] = 'General Configuration';
$lang['overview_enable_ai_room'] = 'Enable AI Room';
$lang['overview_ai_provider_label'] = 'AI Provider';
$lang['overview_system_prompt'] = 'System Prompt';
$lang['overview_system_prompt_placeholder'] = 'Base prompt for the assistant';
$lang['overview_auto_confirm_threshold'] = 'Auto-confirm Threshold';
$lang['overview_memory_retention_days'] = 'Memory Retention (days)';
$lang['overview_max_concurrent_sessions'] = 'Max Concurrent Sessions';
$lang['overview_default_risk_level'] = 'Default Risk Level';
$lang['overview_context_tasks_limit'] = 'Context Tasks Limit';
$lang['overview_context_tasks_limit_help'] = 'Maximum number of tasks loaded into AI context per project (lower if projects are huge).';
$lang['overview_context_milestones_limit'] = 'Context Milestones Limit';
$lang['overview_context_milestones_limit_help'] = 'Maximum milestones to include in AI context per project.';
$lang['overview_context_activities_limit'] = 'Context Activities Limit';
$lang['overview_context_activities_limit_help'] = 'Maximum recent activities (derived) to include.';
$lang['overview_debug_logging'] = 'Debug Logging';
$lang['overview_debug_logging_help'] = 'When On, the module logs extra AI prompt/response debug details to application logs.';
$lang['overview_db_query_trace'] = 'DB Query Trace (heavy queries)';
$lang['overview_db_query_trace_help'] = 'Logs slow/large queries with memory delta to temp/pa_dbtrace.log and application logs. Disable when done.';
$lang['overview_schema_learning'] = 'Schema Learning (Mini Agent)';
$lang['overview_schema_learning_description'] = 'Chọn các bảng từ Perfex CRM để Mini Agent phân tích và đề xuất cột tối ưu (select/order). Danh sách bên dưới đã loại bỏ tiền tố bảng.';
$lang['overview_select_tables'] = 'Chọn bảng';
$lang['overview_no_tables'] = 'Không đọc được danh sách bảng.';
$lang['overview_run_learning'] = 'Chạy học';
$lang['overview_results'] = 'Kết quả';
$lang['overview_error_explainer'] = 'Error Explainer (Gemini Child Agent)';
$lang['overview_error_explainer_description'] = 'When enabled, errors are explained in a friendly way to users. Admins also get actionable steps. You can provide a separate Gemini API key for this child agent.';
$lang['overview_enable_error_explainer'] = 'Enable Error Explainer';
$lang['overview_gemini_child_api_key'] = 'Gemini Child API Key';
$lang['overview_gemini_child_api_key_placeholder'] = 'Enter Gemini API key (optional)';
$lang['overview_save_settings'] = 'Save Settings';
$lang['overview_view_only_access'] = 'You have view-only access. Contact admin to change settings.';
$lang['overview_quick_links'] = 'Quick Links';
$lang['overview_ai_room_link'] = 'AI Room';
$lang['overview_manage_actions_link'] = 'Manage Actions';
$lang['overview_projects_link'] = 'Projects';
$lang['overview_module_health_link'] = 'Module Health';
$lang['overview_status'] = 'Status';
$lang['overview_ai_available_status'] = 'AI Available';
$lang['overview_provider_option'] = 'Provider Option';
$lang['overview_select_at_least_one_table'] = 'Chọn ít nhất 1 bảng';
$lang['overview_learning'] = 'Learning…';
$lang['overview_search_tables_placeholder'] = 'Search tables or models...';
$lang['overview_models_label'] = 'Models';
$lang['overview_schema_analysis'] = 'Schema Analysis';
$lang['overview_optimization_suggestions'] = 'Optimization Suggestions';
$lang['overview_table_analysis'] = 'Table Analysis';
$lang['overview_column_recommendations'] = 'Column Recommendations';
$lang['overview_analysis_complete'] = 'Analysis Complete';
$lang['overview_analysis_failed'] = 'Analysis Failed';
$lang['overview_no_results'] = 'No results found';
$lang['overview_processing'] = 'Processing...';
$lang['overview_ready'] = 'Ready';
$lang['overview_table_name'] = 'Table Name';

# Settings Page
$lang['settings_title'] = 'Project Agent Settings';
$lang['settings_general'] = 'General Settings';
$lang['settings_actions_by_module'] = 'Actions by Module';
$lang['settings_schema_learning'] = 'Schema Learning';
$lang['settings_health_check'] = 'Health Check';
$lang['settings_ai_room_enabled'] = 'AI Room Enabled';
$lang['settings_ai_room_enabled_help'] = 'Enable AI Room interface';
$lang['settings_ai_provider'] = 'AI Provider';
$lang['settings_system_prompt'] = 'System Prompt';
$lang['settings_system_prompt_placeholder'] = 'Enter system prompt for AI behavior...';
$lang['settings_debug_enabled'] = 'Debug Mode';
$lang['settings_debug_enabled_help'] = 'Enable debug logging for troubleshooting';
$lang['settings_error_explainer_enabled'] = 'Error Explainer';
$lang['settings_error_explainer_enabled_help'] = 'Enable AI-powered error explanation';
$lang['settings_error_explainer_api_key'] = 'Error Explainer API Key';
$lang['settings_error_explainer_api_key_placeholder'] = 'Enter API key for error explainer service';
$lang['settings_auto_confirm_threshold'] = 'Auto Confirm Threshold';
$lang['settings_auto_confirm_threshold_help'] = 'Confidence threshold for auto-confirming actions';
$lang['settings_memory_retention_days'] = 'Memory Retention (days)';
$lang['settings_memory_retention_days_help'] = 'Number of days to retain conversation memory';
$lang['settings_max_concurrent_sessions'] = 'Max Concurrent Sessions';
$lang['settings_max_concurrent_sessions_help'] = 'Maximum number of concurrent AI sessions';
$lang['settings_default_risk_level'] = 'Default Risk Level';
$lang['settings_default_risk_level_help'] = 'Default risk level for new actions';
$lang['settings_context_task_limit'] = 'Context Task Limit';
$lang['settings_context_task_limit_help'] = 'Maximum number of tasks to include in context';
$lang['settings_context_milestone_limit'] = 'Context Milestone Limit';
$lang['settings_context_milestone_limit_help'] = 'Maximum number of milestones to include in context';
$lang['settings_context_activity_limit'] = 'Context Activity Limit';
$lang['settings_context_activity_limit_help'] = 'Maximum number of activities to include in context';
$lang['settings_save_settings'] = 'Save Settings';
$lang['settings_ai_status'] = 'AI Status';
$lang['settings_ai_provider_connected'] = 'AI Provider Connected';
$lang['settings_ai_provider_not_available'] = 'AI Provider Not Available';
$lang['settings_version'] = 'Version';
$lang['settings_database_version'] = 'Database Version';
$lang['settings_unknown'] = 'Unknown';

# Actions by Module
$lang['actions_all_modules'] = 'All modules';
$lang['actions_projects'] = 'Projects';
$lang['actions_tasks'] = 'Tasks';
$lang['actions_invoices'] = 'Invoices';
$lang['actions_estimates'] = 'Estimates';
$lang['actions_expenses'] = 'Expenses';
$lang['actions_timesheets'] = 'Timesheets';
$lang['actions_customers'] = 'Customers';
$lang['actions_reminders'] = 'Reminders';
$lang['actions_other'] = 'Other';
$lang['actions_all_risk_levels'] = 'All risk levels';
$lang['actions_all_status'] = 'All status';
$lang['actions_active'] = 'Active';
$lang['actions_inactive'] = 'Inactive';
$lang['actions_search_placeholder'] = 'Search actions...';
$lang['actions_learn_visible'] = 'Learn Visible';
$lang['actions_learn_visible_title'] = 'Learn schema for visible actions';
$lang['actions_id'] = 'ID';
$lang['actions_name'] = 'Name';
$lang['actions_description'] = 'Description';
$lang['actions_risk'] = 'Risk';
$lang['actions_confirm'] = 'Confirm';
$lang['actions_entity'] = 'Entity';
$lang['actions_related_tables'] = 'Related Tables';
$lang['actions_actions'] = 'Actions';
$lang['actions_edit'] = 'Edit';
$lang['actions_learn'] = 'Learn';
$lang['actions_required'] = 'Required';
$lang['actions_optional'] = 'Optional';

# Schema Learning
$lang['schema_learning_title'] = 'Schema Learning (Mini Agent)';
$lang['schema_learning_description'] = 'Use the Mini Agent to learn schema based on Actions. Pick actions and the agent will learn only their related tables.';
$lang['schema_select_actions'] = 'Select Actions';
$lang['schema_all_active_actions'] = 'All active actions if none selected';
$lang['schema_learn_selected_actions'] = 'Learn Selected Actions';
$lang['schema_learn_all_active_actions'] = 'Learn All Active Actions';
$lang['schema_learning_progress'] = 'Learning schema...';
$lang['schema_learning_complete'] = 'Schema Learning Complete';
$lang['schema_learning_info'] = 'Schema Learning Info';
$lang['schema_available_tables'] = 'Available Tables';
$lang['schema_actions_with_mappings'] = 'Actions with Mappings';
$lang['schema_how_it_works'] = 'How it works:';
$lang['schema_step1'] = 'Select actions to analyze';
$lang['schema_step2'] = 'Mini Agent resolves related tables and examines table structure';
$lang['schema_step3'] = 'AI learns relationships and patterns';
$lang['schema_step4'] = 'Improves action parameter mapping';

# Health Check
$lang['health_system_health_check'] = 'System Health Check';
$lang['health_run_health_check'] = 'Run Health Check';
$lang['health_database_tables'] = 'Database Tables';
$lang['health_ai_providers'] = 'AI Providers';
$lang['health_quick_actions'] = 'Quick Actions';
$lang['health_toggle_db_trace'] = 'Toggle DB Trace';
$lang['health_test_context'] = 'Test Context';
$lang['health_refresh_status'] = 'Refresh Status';

# Edit Action Modal
$lang['modal_edit_action_configuration'] = 'Edit Action Configuration';
$lang['modal_action'] = 'Action';
$lang['modal_context_queries'] = 'Context Queries';
$lang['modal_context_queries_help'] = 'Read-only; generated after learning. Columns are alias-qualified to avoid ambiguity.';
$lang['modal_entity_type'] = 'Entity Type';
$lang['modal_entity_none'] = '— None —';
$lang['modal_related_tables'] = 'Related Tables';
$lang['modal_prompt_override'] = 'Prompt Override';
$lang['modal_prompt_override_placeholder'] = 'e.g., Use when user asks for billing overview; require project_id';
$lang['modal_parameter_mapping'] = 'Parameter Mapping';
$lang['modal_parameter_mapping_help'] = 'Map action parameters to Perfex context/core fields or provide a default value. Required fields should be mapped or have defaults.';
$lang['modal_parameter'] = 'Parameter';
$lang['modal_type'] = 'Type';
$lang['modal_source'] = 'Source';
$lang['modal_default_value'] = 'Default/Value';
$lang['modal_mini_agent_learn'] = 'Mini Agent – Learn';
$lang['modal_mini_agent_learn_help'] = 'Trigger the Mini Agent to learn schema for this action\'s related tables. The request config and response will appear below.';
$lang['modal_learn_this_action'] = 'Learn This Action';
$lang['modal_close'] = 'Close';
$lang['modal_save'] = 'Save';

# Messages
$lang['msg_no_visible_actions'] = 'No visible actions to learn. Adjust filters.';
$lang['msg_learning_failed'] = 'Learning failed';
$lang['msg_learning_failed_unknown'] = 'Learning failed: Unknown error';
$lang['msg_update_failed'] = 'Failed to update';
$lang['msg_failed_to_load_schema'] = 'Failed to load schema';
$lang['msg_save_failed'] = 'Save failed';
$lang['msg_select_at_least_one_action'] = 'Select at least one action or use "Learn All Active Actions"';
$lang['msg_successfully_learned_visible'] = 'Successfully learned related tables for {count} visible actions';
$lang['msg_successfully_learned_selected'] = 'Successfully learned related tables for {count} selected actions';
$lang['msg_successfully_learned_all'] = 'Successfully learned related tables for all active actions';
$lang['msg_health_check_failed'] = 'Health check failed';
$lang['msg_context_test_successful'] = 'Context test successful. Keys: {keys}';
$lang['msg_context_test_failed'] = 'Context test failed: {error}';
$lang['msg_db_trace_enabled'] = 'DB trace enabled';
$lang['msg_db_trace_disabled'] = 'DB trace disabled';

# Common
$lang['common_yes'] = 'Yes';
$lang['common_no'] = 'No';
$lang['common_ok'] = 'OK';
$lang['common_cancel'] = 'Cancel';
$lang['common_confirm'] = 'Confirm';
$lang['common_close'] = 'Close';
$lang['common_save'] = 'Save';
$lang['common_edit'] = 'Edit';
$lang['common_delete'] = 'Delete';
$lang['common_view'] = 'View';
$lang['common_create'] = 'Create';
$lang['common_update'] = 'Update';
$lang['common_search'] = 'Search';
$lang['common_filter'] = 'Filter';
$lang['common_export'] = 'Export';
$lang['common_import'] = 'Import';

# Session Management
$lang['session_history'] = 'Session History';
$lang['conversation_history'] = 'Conversation History';
$lang['new_conversation'] = 'New Conversation';
$lang['start_new_conversation'] = 'Start New Conversation';
$lang['no_conversations_found'] = 'No conversations found';
$lang['start_conversation_message'] = 'Start a new conversation in the AI Room to see your history here.';
$lang['session'] = 'Session';
$lang['no_messages_yet'] = 'No messages yet';
$lang['entries'] = 'entries';
$lang['new_session'] = 'New Session';
$lang['reset_chat'] = 'Reset Chat';
$lang['load_session'] = 'Load Session';
$lang['session_created'] = 'Session created successfully';
$lang['session_loaded'] = 'Session loaded successfully';
$lang['no_sessions_found'] = 'No sessions found';
$lang['session_id'] = 'Session ID';
$lang['last_message'] = 'Last Message';
$lang['session_created_at'] = 'Created At';
$lang['session_entries'] = 'Entries';
$lang['confirm_reset_chat'] = 'Are you sure you want to reset the chat? This will start a new conversation.';
$lang['loading_sessions'] = 'Loading sessions...';
$lang['failed_to_load_sessions'] = 'Failed to load sessions';
$lang['failed_to_create_session'] = 'Failed to create session';
$lang['failed_to_load_history'] = 'Failed to load conversation history';

# Action Logs
$lang['project_agent_action_logs'] = 'Action Logs';
$lang['project_agent_action_name'] = 'Action Name';
$lang['project_agent_status'] = 'Status';
$lang['project_agent_executed_at'] = 'Executed At';
$lang['project_agent_executed_by'] = 'Executed By';
$lang['project_agent_actions'] = 'Actions';
$lang['project_agent_action_details'] = 'Action Details';
$lang['project_agent_action_info'] = 'Action Information';
$lang['project_agent_action_id'] = 'Action ID';
$lang['project_agent_parameters'] = 'Parameters';
$lang['project_agent_results'] = 'Results';
$lang['project_agent_error_message'] = 'Error Message';
$lang['project_agent_parameter'] = 'Parameter';
$lang['project_agent_value'] = 'Value';
$lang['project_agent_field'] = 'Field';
$lang['project_agent_status_success'] = 'Success';
$lang['project_agent_status_failed'] = 'Failed';
$lang['project_agent_status_running'] = 'Running';
$lang['project_agent_status_queued'] = 'Queued';
$lang['view_details'] = 'View Details';
$lang['no_parameters'] = 'No Parameters';
$lang['no_results'] = 'No Results';
$lang['no_data_found'] = 'No Data Found';
