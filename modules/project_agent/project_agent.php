<?php
defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: Project Agent
Description: AI Assistant module for project management with chat-to-operate interface
Version: 1.0.4
Author: FHC
Author URI: https://chantroituonglai.com
Requires at least: 2.3.*
*/

define('PROJECT_AGENT_MODULE_NAME', 'project_agent');
define('PROJECT_AGENT_DB_VERSION', 106);

// Register language files early to ensure keys are available when menus/hooks run
register_language_files(PROJECT_AGENT_MODULE_NAME, [
    PROJECT_AGENT_MODULE_NAME,
    'controllers',
    'actions',
]);

/**
 * Register module assets
 */
function register_project_agent_module_assets() {
    $CI = &get_instance();
    // Only load on Project Agent pages or when viewing a project (admin/projects/view)
    if (!is_project_agent_asset_page()) { return; }
    
    // Add module's common CSS file with cache-busting version parameter
    hooks()->add_action('app_admin_head', function(){
        $version = '?v=' . uniqid();
        echo '<link href="' . module_dir_url(PROJECT_AGENT_MODULE_NAME, 'assets/css/project_agent.css') . $version . '" rel="stylesheet" type="text/css" />';
    });

    // Add JavaScript files to footer
    hooks()->add_action('app_admin_footer', function(){
        $version = '?v=' . uniqid();
        echo '<script src="' . module_dir_url(PROJECT_AGENT_MODULE_NAME, 'assets/js/project_agent.js') . $version . '"></script>';
        echo '<script src="' . module_dir_url(PROJECT_AGENT_MODULE_NAME, 'assets/js/ai_room.js') . $version . '"></script>';
        echo '<script src="' . module_dir_url(PROJECT_AGENT_MODULE_NAME, 'assets/js/action_executor.js') . $version . '"></script>';
        
        // Load all language strings into JavaScript
        load_project_agent_language_js();
    });
}

/**
 * Check if current page is project_agent module page
 */
function is_project_agent_module_page() {
    $CI = &get_instance();
    $class = $CI->router->fetch_class();
    $module_pages = ['project_agent', 'project_agent_api', 'project_agent_actions'];
    return in_array($class, $module_pages);
}

function is_project_agent_asset_page() {
    $uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
    $admin = get_admin_uri();
    $onProjectView = strpos($uri, $admin . '/projects/view') !== false;
    return is_project_agent_module_page() || $onProjectView;
}

/**
 * Initialize module
 */
function init_project_agent_module() {
    $CI = &get_instance();
    // Ensure required tables exist as early as possible to avoid crashes
    try { 
        project_agent_ensure_all_tables($CI); 
    } catch (\Throwable $e) { 
        log_message('error', 'Project Agent: early ensure tables failed - '.$e->getMessage());
        // Don't let table creation failures crash the module
    }
    
    // Load helpers
    require_once(__DIR__ . '/helpers/project_agent_action_registry_helper.php');
    require_once(__DIR__ . '/helpers/project_agent_memory_helper.php');
    require_once(__DIR__ . '/helpers/project_agent_context_helper.php');
    require_once(__DIR__ . '/helpers/project_agent_db_helper.php');
    require_once(__DIR__ . '/helpers/project_agent_planner_helper.php');
    require_once(__DIR__ . '/helpers/project_agent_ai_integration_helper.php');
    require_once(__DIR__ . '/helpers/project_agent_progress_helper.php');
    require_once(__DIR__ . '/helpers/project_agent_schema_helper.php');
    
    // Register assets
    register_project_agent_module_assets();
    
    // Run migrations
    hooks()->add_action('admin_init', 'project_agent_module_activation_hook');
    hooks()->add_action('admin_init', 'project_agent_run_migrations', 2);

    // Ensure required tables exist even if something loads early
    if (!$CI->db->table_exists(db_prefix() . 'project_agent_actions')) {
        project_agent_module_activation_hook();
        project_agent_run_migrations();
    }

    // Register language files
    register_language_files(PROJECT_AGENT_MODULE_NAME, [
        PROJECT_AGENT_MODULE_NAME,
        'controllers',
        'actions'
    ]);

    // Ensure language keys available immediately for early hooks/permissions
    $language = $CI->session->userdata('language');
    if (!$language) { $language = 'english'; }
    $CI->lang->load('project_agent/project_agent', $language);
    $CI->lang->load('project_agent/controllers', $language);
    $CI->lang->load('project_agent/actions', $language);
    if ($language !== 'english') {
        $CI->lang->load('project_agent/project_agent', 'english');
        $CI->lang->load('project_agent/controllers', 'english');
        $CI->lang->load('project_agent/actions', 'english');
    }
    
    // Register hooks
    register_project_agent_hooks();
    
    // Register permissions
    $capabilities = [
        'view'   => _l('permission_view') . '(' . _l('permission_global') . ')',
        'execute_safe' => _l('permission_execute_safe'),
        'execute_financial' => _l('permission_execute_financial'),
        'admin' => _l('permission_admin'),
    ];
    
    register_staff_capabilities(PROJECT_AGENT_MODULE_NAME, $capabilities, _l('project_agent'));
}



/**
 * Register hooks
 */
function register_project_agent_hooks() {
    // Remove any existing hooks first to prevent duplicates
    hooks()->remove_action('admin_init', 'project_agent_admin_init_menu');
    
    // Hook for admin menu
    hooks()->add_action('admin_init', 'project_agent_admin_init_menu', 5);
    
    // Inject Project tabs via standard filter (compatible with core Project module)
    hooks()->add_filter('project_tabs', 'project_agent_project_tabs');
    
    // Hook for cron job
    hooks()->add_action('after_cron_run', 'project_agent_module_cron_job');
    
    // AI Integration hooks
    hooks()->add_action('after_geminiai_provider_registered', 'project_agent_register_ai_integration');
    hooks()->add_filter('project_agent_ai_provider_selection', 'project_agent_filter_ai_provider');
}

/**
 * Universal table checker and creator - uses only canonical table names
 * Compatible with migration system and removes legacy tables
 */
function project_agent_ensure_all_tables($CI = null) {
    if (!$CI) {
        $CI = &get_instance();
    }
    
    // Cache check to avoid repeated execution
    static $tables_checked = false;
    if ($tables_checked) {
        return;
    }
    
    $CI->load->database();
    $CI->load->dbforge();
    
    // Define all required tables with their schemas
    $tables = [
        'project_agent_actions' => [
            'fields' => [
                'action_id' => ['type'=>'VARCHAR','constraint'=>100,'null'=>FALSE],
                'name' => ['type'=>'VARCHAR','constraint'=>255,'null'=>FALSE],
                'description' => ['type'=>'TEXT','null'=>TRUE],
                'params_schema' => ['type'=>'TEXT','null'=>FALSE],
                'permissions' => ['type'=>'TEXT','null'=>FALSE],
                'prompt_override' => ['type'=>'TEXT','null'=>TRUE],
                'risk_level' => ['type'=>'ENUM','constraint'=>['low','medium','high'],'default'=>'low','null'=>FALSE],
                'requires_confirm' => ['type'=>'TINYINT','constraint'=>1,'default'=>0,'null'=>FALSE],
                'is_active' => ['type'=>'TINYINT','constraint'=>1,'default'=>1,'null'=>FALSE],
            ],
            'keys' => ['action_id' => TRUE],
            'data' => 'project_agent_get_default_actions'
        ],
        'project_agent_sessions' => [
            'fields' => [
                'session_id' => ['type'=>'INT','constraint'=>11,'auto_increment'=>TRUE],
                'project_id' => ['type'=>'INT','constraint'=>11,'null'=>TRUE],
                'user_id' => ['type'=>'INT','constraint'=>11,'null'=>FALSE],
                'title' => ['type'=>'VARCHAR','constraint'=>255,'null'=>TRUE],
                'created_at' => ['type'=>'DATETIME','null'=>FALSE],
                'updated_at' => ['type'=>'DATETIME','null'=>FALSE],
            ],
            'keys' => ['session_id' => TRUE, 'project_id' => FALSE, 'user_id' => FALSE]
        ],
        'project_agent_memory_entries' => [
            'fields' => [
                'entry_id' => ['type'=>'INT','constraint'=>11,'auto_increment'=>TRUE],
                'session_id' => ['type'=>'INT','constraint'=>11,'null'=>FALSE],
                'scope' => ['type'=>'VARCHAR','constraint'=>16,'null'=>FALSE],
                'kind' => ['type'=>'VARCHAR','constraint'=>32,'null'=>FALSE],
                'content_json' => ['type'=>'TEXT','null'=>FALSE],
                'created_at' => ['type'=>'DATETIME','null'=>FALSE],
                'project_id' => ['type'=>'INT','constraint'=>11,'null'=>TRUE],
                'customer_id' => ['type'=>'INT','constraint'=>11,'null'=>TRUE],
                'entity_refs' => ['type'=>'TEXT','null'=>TRUE],
            ],
            'keys' => ['entry_id' => TRUE, 'session_id' => FALSE]
        ],
        'project_agent_action_logs' => [
            'fields' => [
                'log_id' => ['type'=>'INT','constraint'=>11,'auto_increment'=>TRUE],
                'session_id' => ['type'=>'INT','constraint'=>11,'null'=>FALSE],
                'plan_id' => ['type'=>'VARCHAR','constraint'=>100,'null'=>FALSE],
                'run_id' => ['type'=>'VARCHAR','constraint'=>100,'null'=>FALSE],
                'action_id' => ['type'=>'VARCHAR','constraint'=>100,'null'=>FALSE],
                'params_json' => ['type'=>'LONGTEXT','null'=>FALSE],
                'result_json' => ['type'=>'LONGTEXT','null'=>TRUE],
                'status' => ['type'=>'VARCHAR','constraint'=>16,'null'=>FALSE],
                'error_message' => ['type'=>'TEXT','null'=>TRUE],
                'executed_at' => ['type'=>'DATETIME','null'=>FALSE],
                'executed_by' => ['type'=>'INT','constraint'=>11,'null'=>FALSE],
                'client_token' => ['type'=>'VARCHAR','constraint'=>255,'null'=>TRUE],
            ],
            'keys' => ['log_id' => TRUE]
        ]
    ];
    
    foreach ($tables as $table_name => $config) {
        $canonical_table = db_prefix() . $table_name;
        $legacy_table = db_prefix() . 'tbl' . $table_name;
        
        // Check if canonical table exists
        if ($CI->db->table_exists($canonical_table)) {
            log_message('debug', "Project Agent: Table {$canonical_table} exists, skipping creation");
            continue;
        }
        
        // Check if legacy table exists and migrate data
        if ($CI->db->table_exists($legacy_table)) {
            log_message('info', "Project Agent: Migrating data from {$legacy_table} to {$canonical_table}");
            
            // Create canonical table with same structure
            $CI->db->query("CREATE TABLE `{$canonical_table}` LIKE `{$legacy_table}`");
            
            // Copy data
            $CI->db->query("INSERT INTO `{$canonical_table}` SELECT * FROM `{$legacy_table}`");
            
            // DROP legacy table after successful migration
            $CI->db->query("DROP TABLE `{$legacy_table}`");
            
            log_message('info', "Project Agent: Successfully migrated and removed {$legacy_table}");
            continue;
        }
        
        // Create new table only if it doesn't exist
        log_message('info', "Project Agent: Creating new table {$canonical_table}");
        
        $CI->dbforge->add_field($config['fields']);
        
        // Add keys
        foreach ($config['keys'] as $key => $is_primary) {
            $CI->dbforge->add_key($key, $is_primary);
        }
        
        // Use TRUE for if_not_exists to prevent errors if table already exists
        try {
            $CI->dbforge->create_table($canonical_table, TRUE);
        } catch (Exception $e) {
            log_message('error', "Project Agent: Failed to create table {$canonical_table}: " . $e->getMessage());
            // Continue with other tables even if one fails
            continue;
        }
        
        // Insert default data if specified and table was just created
        if (isset($config['data']) && function_exists($config['data'])) {
            $default_data = $config['data']();
            if (!empty($default_data)) {
                foreach ($default_data as $row) {
                    // Use INSERT IGNORE to prevent duplicate key errors
                    try {
                        $CI->db->query("INSERT IGNORE INTO `{$canonical_table}` (" . implode(',', array_keys($row)) . ") VALUES (" . implode(',', array_fill(0, count($row), '?')) . ")", array_values($row));
                    } catch (Exception $e) {
                        log_message('error', "Project Agent: Failed to insert default data into {$canonical_table}: " . $e->getMessage());
                    }
                }
                log_message('info', "Project Agent: Inserted default data into {$canonical_table}");
            }
        }
    }
    
    // Clean up any remaining legacy tables
    project_agent_cleanup_legacy_tables($CI);
    
    // Mark as checked to prevent repeated execution
    $tables_checked = true;
    
    log_message('info', 'Project Agent: All tables ensured successfully with canonical naming');
}

/**
 * Clean up any remaining legacy tables
 */
function project_agent_cleanup_legacy_tables($CI) {
    $legacy_tables = [
        'tblproject_agent_actions',
        'tblproject_agent_sessions', 
        'tblproject_agent_memory_entries',
        'tblproject_agent_action_logs'
    ];
    
    foreach ($legacy_tables as $legacy_table) {
        $full_legacy_name = db_prefix() . $legacy_table;
        if ($CI->db->table_exists($full_legacy_name)) {
            log_message('info', "Project Agent: Removing legacy table {$full_legacy_name}");
            $CI->db->query("DROP TABLE `{$full_legacy_name}`");
        }
    }
}

/**
 * Get default actions data
 */
function project_agent_get_default_actions() {
    return [
        [
            'action_id' => 'check_billing_status',
            'name' => 'Check Billing Status',
            'description' => 'Check current billing status for project',
            'params_schema' => json_encode(['type'=>'object','properties'=>['project_id'=>['type'=>'integer']], 'required'=>['project_id']]),
            'permissions' => json_encode(['projects:view','invoices:view']),
            'risk_level' => 'low',
            'requires_confirm' => 0,
            'is_active' => 1
        ],
        [
            'action_id' => 'list_overdue_invoices',
            'name' => 'List Overdue Invoices',
            'description' => 'List overdue invoices for project',
            'params_schema' => json_encode(['type'=>'object','properties'=>['project_id'=>['type'=>'integer']], 'required'=>['project_id']]),
            'permissions' => json_encode(['invoices:view']),
            'risk_level' => 'low',
            'requires_confirm' => 0,
            'is_active' => 1
        ],
        [
            'action_id' => 'create_task',
            'name' => 'Create Task',
            'description' => 'Create new task for project',
            'params_schema' => json_encode(['type'=>'object','properties'=>['project_id'=>['type'=>'integer'],'title'=>['type'=>'string'],'description'=>['type'=>'string']], 'required'=>['project_id','title']]),
            'permissions' => json_encode(['tasks:create']),
            'risk_level' => 'low',
            'requires_confirm' => 0,
            'is_active' => 1
        ],
        [
            'action_id' => 'summarize_project_work_remaining',
            'name' => 'Summarize Project Work',
            'description' => 'Get summary of remaining work for project',
            'params_schema' => json_encode(['type'=>'object','properties'=>['project_id'=>['type'=>'integer']], 'required'=>['project_id']]),
            'permissions' => json_encode(['projects:view']),
            'risk_level' => 'low',
            'requires_confirm' => 0,
            'is_active' => 1
        ],
        [
            'action_id' => 'create_estimate',
            'name' => 'Create Estimate',
            'description' => 'Create new estimate for project',
            'params_schema' => json_encode(['type'=>'object','properties'=>['project_id'=>['type'=>'integer'],'name'=>['type'=>'string']], 'required'=>['project_id','name']]),
            'permissions' => json_encode(['estimates:create']),
            'risk_level' => 'low',
            'requires_confirm' => 0,
            'is_active' => 1
        ],
        [
            'action_id' => 'create_invoice',
            'name' => 'Create Invoice',
            'description' => 'Create new invoice for project',
            'params_schema' => json_encode(['type'=>'object','properties'=>['project_id'=>['type'=>'integer'],'name'=>['type'=>'string']], 'required'=>['project_id','name']]),
            'permissions' => json_encode(['invoices:create']),
            'risk_level' => 'medium',
            'requires_confirm' => 1,
            'is_active' => 1
        ],
        [
            'action_id' => 'update_project',
            'name' => 'Update Project',
            'description' => 'Update project details',
            'params_schema' => json_encode(['type'=>'object','properties'=>['project_id'=>['type'=>'integer'],'name'=>['type'=>'string'],'description'=>['type'=>'string']], 'required'=>['project_id']]),
            'permissions' => json_encode(['projects:edit']),
            'risk_level' => 'low',
            'requires_confirm' => 0,
            'is_active' => 1
        ],
        [
            'action_id' => 'add_project_member',
            'name' => 'Add Project Member',
            'description' => 'Add member to project',
            'params_schema' => json_encode(['type'=>'object','properties'=>['project_id'=>['type'=>'integer'],'staff_id'=>['type'=>'integer']], 'required'=>['project_id','staff_id']]),
            'permissions' => json_encode(['projects:edit']),
            'risk_level' => 'low',
            'requires_confirm' => 0,
            'is_active' => 1
        ],
        [
            'action_id' => 'add_milestone',
            'name' => 'Add Milestone',
            'description' => 'Add milestone to project',
            'params_schema' => json_encode([
                'type'=>'object',
                'properties'=>[
                    'project_id'=>['type'=>'integer','minimum'=>1],
                    'name'=>['type'=>'string','minLength'=>1],
                    'description'=>['type'=>'string'],
                    'due_date'=>['type'=>'string','format'=>'date']
                ],
                'required'=>['project_id','name','due_date']
            ]),
            'permissions' => json_encode(['projects:edit']),
            'risk_level' => 'low',
            'requires_confirm' => 0,
            'is_active' => 1
        ],
        [
            'action_id' => 'record_expense',
            'name' => 'Record Expense',
            'description' => 'Record expense for project',
            'params_schema' => json_encode(['type'=>'object','properties'=>['project_id'=>['type'=>'integer'],'amount'=>['type'=>'number'],'description'=>['type'=>'string']], 'required'=>['project_id','amount','description']]),
            'permissions' => json_encode(['expenses:create']),
            'risk_level' => 'medium',
            'requires_confirm' => 1,
            'is_active' => 1
        ]
    ];
}

/**
 * Admin menu registration callback
 */
function project_agent_admin_init_menu() {
    $CI = &get_instance();
    if (!is_staff_logged_in()) {
        return;
    }
    // Add sidebar menu item if API available (Perfex >= certain version)
    if (isset($CI->app_menu) && method_exists($CI->app_menu, 'add_sidebar_menu_item')) {
        $CI->app_menu->add_sidebar_menu_item('project-agent', [
            'slug'     => 'project-agent',
            'name'     => 'Project Agent',
            'href'     => admin_url('project_agent'),
            'icon'     => 'fa fa-robot',
            'position' => 25,
        ]);
        // Child: AI Room
        $CI->app_menu->add_sidebar_children_item('project-agent', [
            'slug'     => 'project-agent-ai',
            'name'     => 'AI Room',
            'href'     => admin_url('project_agent/ai'),
            'position' => 1,
        ]);
        // Child: Conversation History
        $CI->app_menu->add_sidebar_children_item('project-agent', [
            'slug'     => 'project-agent-history',
            'name'     => 'Conversation History',
            'href'     => admin_url('project_agent/conversation_history'),
            'position' => 2,
        ]);
        // Management page for Actions (admin only)
        if (has_permission('project_agent', '', 'admin')) {
            $CI->app_menu->add_sidebar_children_item('project-agent', [
                'slug'     => 'project-agent-actions',
                'name'     => 'Agent Actions',
                'href'     => admin_url('project_agent/actions'),
                'position' => 3,
            ]);
            $CI->app_menu->add_sidebar_children_item('project-agent', [
                'slug'     => 'project-agent-settings',
                'name'     => 'Settings',
                'href'     => admin_url('project_agent/settings'),
                'position' => 4,
            ]);
            $CI->app_menu->add_sidebar_children_item('project-agent', [
                'slug'     => 'project-agent-health',
                'name'     => 'Health Check',
                'href'     => admin_url('project_agent/health'),
                'position' => 5,
            ]);
        }
    }
}

/**
 * Add Project Agent tab to Project view (admin/projects/view/...)
 */
function project_agent_project_tabs($tabs) {
    // Add top-level tab at a later position to avoid conflicting with core
    $tabs['project_agent'] = [
        'slug'     => 'project_agent',
        'name'     => 'AI Assistant',
        'icon'     => 'fa fa-robot',
        'view'     => 'project_agent/project_ai_on_project',
        // Place the tab right after "Overview"
        'position' => 2,
        'visible'  => has_permission('project_agent', '', 'view'),
    ];
    return $tabs;
}

/**
 * Hook for cron job
 */
function project_agent_module_cron_job() {
    $CI = &get_instance();
    
    // Perform cron tasks
    // - Clean old memory entries
    // - Process recurring actions
    // - Send notifications
}

/**
 * Loads all language strings from the language files and outputs them as JavaScript variables
 */
function load_project_agent_language_js() {
    $CI = &get_instance();
    $language_files = [
        'project_agent_lang',
        'controllers_lang',
        'actions_lang'
    ];
    
    // Start the JavaScript output
    echo '<script>
    // Initialize app.lang if it doesn\'t exist
    if (typeof app === "undefined") {
        window.app = {};
    }
    if (!app.lang) {
        app.lang = {};
    }
    
    // Load all language strings into app.lang
';
    
    // Loop through each language file
    foreach ($language_files as $file) {
        $CI = &get_instance();
        $language = $CI->session->userdata('language'); 
        // Get the language file path
        $lang_path = FCPATH . 'modules/' . PROJECT_AGENT_MODULE_NAME . '/language/' . $language . '/' . $file . '.php';
        $en_lang_path = FCPATH . 'modules/' . PROJECT_AGENT_MODULE_NAME . '/language/english/' . $file . '.php';
        
        // Initialize the $lang array to store language strings
        $lang = [];
        
        // Try to load the current language file, fall back to English if not found
        if (file_exists($lang_path)) {
            include $lang_path;
        } elseif (file_exists($en_lang_path)) {
            include $en_lang_path;
        }
        
        // Output each language string as a JavaScript variable
        if (!empty($lang)) {
            foreach ($lang as $key => $value) {
                // Escape any quotes to avoid JavaScript errors
                $escaped_value = addslashes($value);
                echo '    app.lang.' . $key . ' = "' . $escaped_value . '";' . PHP_EOL;
            }
        }
    }
    
    // Close the JavaScript tag
    echo '</script>';
}

// Initialize module
init_project_agent_module();

// Register settings
    add_option('project_agent_ai_room_enabled', 1);
    add_option('project_agent_auto_confirm_threshold', 1000);
    add_option('project_agent_memory_retention_days', 30);
    add_option('project_agent_max_concurrent_sessions', 10);
    add_option('project_agent_default_risk_level', 'low');
    add_option('project_agent_debug_enabled', 0);
    add_option('project_agent_system_prompt', 'You are a Project Management AI Assistant for Perfex CRM. Help users manage projects, tasks, estimates, and invoices through natural language interaction.');
    add_option('project_agent_ai_provider', 'geminiai');
    // Context size guard
    add_option('project_agent_context_task_limit', 200);
    add_option('project_agent_context_milestone_limit', 100);
    add_option('project_agent_context_activity_limit', 50);
    // Error explainer child agent (Gemini) settings
    add_option('project_agent_error_explainer_enabled', 0);
    add_option('project_agent_error_explainer_api_key', '');

function project_agent_module_activation_hook(): void
{
    add_option('project_agent_db_version', 0);
}

function project_agent_run_migrations(): void
{
    $CI = &get_instance();

    // Guard: ensure actions table exists early to avoid crashes on first load
    try {
        project_agent_ensure_all_tables($CI);
    } catch (\Throwable $e) {
        log_message('error', 'Project Agent: Early guard table creation failed - ' . $e->getMessage());
        // Continue with migration even if table creation fails
    }

    $installed = (int) get_option('project_agent_db_version');
    if ($installed >= PROJECT_AGENT_DB_VERSION) {
        return;
    }

    if (isset($CI->app_modules)) {
        $result = $CI->app_modules->upgrade_database(PROJECT_AGENT_MODULE_NAME);
        if ($result !== true) {
            log_message('error', 'Project Agent: Migration failed - ' . print_r($result, true));
            return;
        }
    }

    update_option('project_agent_db_version', PROJECT_AGENT_DB_VERSION);
    log_message('info', 'Project Agent: Migration completed, version set to ' . PROJECT_AGENT_DB_VERSION);
}

/**
 * Handle async job execution via Perfex CRM hooks
 */
function project_agent_handle_async_job($token) {
    log_message('error', '[PA][hook] Async job hook triggered for token: ' . $token);
    
    try {
        $CI = &get_instance();
        $CI->load->library('Project_agent');
        $CI->Project_agent->run_ai_job($token);
        log_message('error', '[PA][hook] Async job completed successfully via hook');
    } catch (\Throwable $e) {
        log_message('error', '[PA][hook] Async job failed via hook: ' . $e->getMessage());
    }
}

// Register the hook handler
hooks()->add_action('project_agent_async_job', 'project_agent_handle_async_job');
