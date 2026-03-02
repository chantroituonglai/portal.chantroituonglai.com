<?php
defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: Project Management Agent
Description: Extends Project Agent with AI-powered project cloning & composition.
Version: 1.0.0
Author: FHC
Requires at least: 2.3.*
*/

define('PROJECT_MANAGEMENT_AGENT_MODULE_NAME', 'project_management_agent');
define('PROJECT_MANAGEMENT_AGENT_DB_VERSION', 1);

// Load language files early
register_language_files(PROJECT_MANAGEMENT_AGENT_MODULE_NAME, [
    PROJECT_MANAGEMENT_AGENT_MODULE_NAME,
]);

// Ensure helper functions are available early for hooks/filters
$pma_helper = __DIR__ . '/helpers/project_management_agent_helper.php';
if (file_exists($pma_helper)) {
    require_once $pma_helper;
}

hooks()->add_action('admin_init', 'pma_module_activation_hook');
hooks()->add_action('admin_init', 'pma_run_migrations', 2);
hooks()->add_action('admin_init', 'pma_register_menu', 5);
hooks()->add_action('admin_init', 'pma_register_hooks', 10);
hooks()->add_action('app_admin_head', 'pma_register_assets_head');
hooks()->add_action('app_admin_footer', 'pma_register_assets_footer');

function pma_module_activation_hook(): void
{
    // Optional: warn if dependency missing
    if (function_exists('is_module_installed')) {
        try {
            if (!is_module_installed('project_agent')) {
                if (function_exists('set_alert')) {
                    set_alert('warning', 'Project Management Agent requires Project Agent module to be installed first.');
                }
            }
        } catch (\Throwable $e) { /* ignore */ }
    }
    // Track DB version
    add_option('project_management_agent_db_version', 0);
}

function pma_run_migrations(): void
{
    $installed = (int) get_option('project_management_agent_db_version');
    if ($installed >= PROJECT_MANAGEMENT_AGENT_DB_VERSION) {
        return;
    }

    $CI = &get_instance();
    if (isset($CI->app_modules)) {
        $result = $CI->app_modules->upgrade_database(PROJECT_MANAGEMENT_AGENT_MODULE_NAME);
        if ($result !== true) {
            log_message('error', 'Project Management Agent migration failed: ' . print_r($result, true));
            return;
        }
    }

    update_option('project_management_agent_db_version', PROJECT_MANAGEMENT_AGENT_DB_VERSION);
}

function pma_migration_001(): void
{
    $CI = &get_instance();
    $CI->load->dbforge();

    // Table: project_management_agent_compositions
    $tblCompositions = db_prefix() . 'project_management_agent_compositions';
    if (!$CI->db->table_exists($tblCompositions)) {
        $CI->dbforge->add_field([
            'composition_id' => [
                'type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true,
            ],
            'source_project_id' => [
                'type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => false,
            ],
            'user_id' => [
                'type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => false,
            ],
            'composition_data' => [
                'type' => 'LONGTEXT', 'null' => false,
            ],
            'ai_breakdown' => [
                'type' => 'LONGTEXT', 'null' => true,
            ],
            'input_data' => [
                'type' => 'LONGTEXT', 'null' => true,
            ],
            'status' => [
                'type' => 'ENUM', 'constraint' => ['collecting','analyzing','ready','cloning','completed','failed'], 'default' => 'collecting', 'null' => false,
            ],
            'status_message' => [
                'type' => 'TEXT', 'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME', 'null' => false,
            ],
            'updated_at' => [
                'type' => 'DATETIME', 'null' => false,
            ],
        ]);
        $CI->dbforge->add_key('composition_id', true);
        $CI->dbforge->add_key('source_project_id');
        $CI->dbforge->add_key('user_id');
        $CI->dbforge->add_key('status');
        $CI->dbforge->create_table($tblCompositions, true);
    }

    // Table: project_management_agent_clones
    $tblClones = db_prefix() . 'project_management_agent_clones';
    if (!$CI->db->table_exists($tblClones)) {
        $CI->dbforge->add_field([
            'clone_id' => [
                'type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true,
            ],
            'composition_id' => [
                'type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => false,
            ],
            'source_project_id' => [
                'type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => false,
            ],
            'new_project_id' => [
                'type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true,
            ],
            'clone_config' => [
                'type' => 'LONGTEXT', 'null' => false,
            ],
            'timeline_adjustments' => [
                'type' => 'LONGTEXT', 'null' => true,
            ],
            'status' => [
                'type' => 'ENUM', 'constraint' => ['pending','processing','completed','failed'], 'default' => 'pending', 'null' => false,
            ],
            'progress' => [
                'type' => 'INT', 'constraint' => 3, 'default' => 0, 'null' => false,
            ],
            'error_message' => [
                'type' => 'TEXT', 'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME', 'null' => false,
            ],
            'completed_at' => [
                'type' => 'DATETIME', 'null' => true,
            ],
        ]);
        $CI->dbforge->add_key('clone_id', true);
        $CI->dbforge->add_key('composition_id');
        $CI->dbforge->add_key('source_project_id');
        $CI->dbforge->add_key('new_project_id');
        $CI->dbforge->add_key('status');
        $CI->dbforge->create_table($tblClones, true);
    }
}

function pma_register_menu(): void
{
    $CI = &get_instance();
    if (!is_staff_logged_in()) { return; }

    // If Project Agent is active, add as child under its menu for cohesive UX
    $parentSlug = 'project-agent';
    if (isset($CI->app_menu) && method_exists($CI->app_menu, 'add_sidebar_children_item')) {
        $CI->app_menu->add_sidebar_children_item($parentSlug, [
            'slug'     => 'project-management-agent-composer',
            'name'     => _l('pma_menu_project_composer'),
            'href'     => admin_url('project_management_agent'),
            'position' => 10,
        ]);
        return;
    }

    // Fallback top-level menu if parent is unavailable
    if (isset($CI->app_menu) && method_exists($CI->app_menu, 'add_sidebar_menu_item')) {
        $CI->app_menu->add_sidebar_menu_item('project-management-agent', [
            'slug'     => 'project-management-agent',
            'name'     => 'Project Management Agent',
            'href'     => admin_url('project_management_agent'),
            'icon'     => 'fa fa-copy',
            'position' => 26,
        ]);
    }
}

function pma_register_hooks(): void
{
    // Project view tab
    hooks()->add_filter('project_tabs', 'pma_project_tabs');
    // Project view action injection
    hooks()->add_action('before_render_project_view', 'pma_add_clone_button');
}

/**
 * Add Project Management Agent tab to Project view
 */
function pma_project_tabs($tabs) {
    // Ensure $tabs is an array
    if (!is_array($tabs)) {
        $tabs = [];
    }
    
    if (!function_exists('pma_is_project_agent_available') || !pma_is_project_agent_available()) { 
        return $tabs; 
    }
    
    if (!has_permission('projects', '', 'create')) {
        return $tabs;
    }
    
    $tabs['project_management_agent'] = [
        'slug'     => 'project_management_agent',
        'name'     => _l('pma_tab_project_composer'),
        'icon'     => 'fa fa-copy',
        'view'     => 'project_management_agent/project_composer_tab',
        'position' => 15,
        'visible'  => has_permission('projects', '', 'create'),
    ];
    return $tabs;
}

/**
 * Add a button in project actions dropdown to open Project Composer
 */
function pma_add_clone_button($project_id)
{
    if (!has_permission('projects', '', 'create')) { return; }
    $url = admin_url('project_management_agent') . '?project_id=' . (int)$project_id;
    $label = _l('pma_btn_project_composer');
    echo '<script>(function(){var add=function(){var m=$("ul.project-actions");if(!m.length){m=$(".project-actions .dropdown-menu");}if(!m.length){m=$("#project_view_options .dropdown-menu");}if(!m.length){return false;}if(m.find(".pma-open-composer").length){return true;}var li=$("<li class=\\"pma-open-composer\\"><a href=\\"' . html_escape($url) . '\\"><i class=\\"fa fa-copy\\"></i> ' . html_escape($label) . '</a></li>");m.append(li);return true;};var t=0,i=setInterval(function(){t++;if(add()||t>20){clearInterval(i);}},400);})();</script>';
}

function pma_is_module_page(): bool
{
    $CI = &get_instance();
    $class = $CI->router->fetch_class();
    return $class === 'project_management_agent';
}

function pma_is_project_view_page(): bool
{
    $uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
    $admin = get_admin_uri();
    return strpos($uri, $admin . '/projects/view') !== false;
}

function pma_register_assets_head(): void
{
    if (!pma_is_module_page() && !pma_is_project_view_page()) { return; }
    $version = '?v=' . uniqid();
    echo '<link href="' . module_dir_url(PROJECT_MANAGEMENT_AGENT_MODULE_NAME, 'assets/css/project_management_agent.css') . $version . '" rel="stylesheet" type="text/css" />';
}

function pma_register_assets_footer(): void
{
    if (!pma_is_module_page() && !pma_is_project_view_page()) { return; }
    $version = '?v=' . uniqid();
    echo '<script src="' . module_dir_url(PROJECT_MANAGEMENT_AGENT_MODULE_NAME, 'assets/js/project_management_agent.js') . $version . '"></script>';
}
