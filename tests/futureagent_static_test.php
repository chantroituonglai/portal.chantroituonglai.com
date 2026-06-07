<?php

$root = dirname(__DIR__);

function fa_read($path)
{
    global $root;
    $full = $root . '/' . $path;
    if (!is_file($full)) {
        throw new RuntimeException('Missing file: ' . $path);
    }
    return file_get_contents($full);
}

function fa_assert($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$module = fa_read('modules/futureagent/futureagent.php');
$routes = fa_read('modules/futureagent/config/routes.php');
$install = fa_read('modules/futureagent/install.php');
$agentMigration = fa_read('modules/futureagent/migrations/012_version_012.php');
$projectAgentMigration = fa_read('modules/futureagent/migrations/013_version_013.php');
$paperclipAgentMigration = fa_read('modules/futureagent/migrations/014_version_014.php');
$moduleVersionMigration = fa_read('modules/futureagent/migrations/015_version_015.php');
$controllerButtonsMigration = fa_read('modules/futureagent/migrations/016_version_016.php');
$model = fa_read('modules/futureagent/models/Futureagent_model.php');
$llmBrain = fa_read('modules/futureagent/libraries/Futureagent_llm_brain.php');
$executor = fa_read('modules/futureagent/libraries/Futureagent_action_executor.php');
$api = fa_read('modules/futureagent/controllers/Futureagent_api.php');
$admin = fa_read('modules/futureagent/controllers/Futureagent.php');
$agentsView = fa_read('modules/futureagent/views/admin/agents.php');
$agentCreateView = fa_read('modules/futureagent/views/admin/agent_create.php');
$agentConfigView = fa_read('modules/futureagent/views/admin/agent_config.php');
$projectConfigView = fa_read('modules/futureagent/views/admin/project_config.php');
$companiesView = fa_read('modules/futureagent/views/admin/companies.php');
$projectsView = fa_read('modules/futureagent/views/admin/projects.php');
$issuesView = fa_read('modules/futureagent/views/admin/issues.php');
$approvalsView = fa_read('modules/futureagent/views/admin/approvals.php');
$settingsView = fa_read('modules/futureagent/views/admin/settings.php');
$dashboardView = fa_read('modules/futureagent/views/admin/dashboard.php');
$runsView = fa_read('modules/futureagent/views/admin/runs.php');
$genericView = fa_read('modules/futureagent/views/admin/generic.php');
$controllersView = fa_read('modules/futureagent/views/admin/controllers.php');
$controllerCreateView = fa_read('modules/futureagent/views/admin/controller_create.php');
$controllerEditView = fa_read('modules/futureagent/views/admin/controller_edit.php');
$controllerView = fa_read('modules/futureagent/views/admin/controller_view.php');
$actionTypesView = fa_read('modules/futureagent/views/admin/action_types.php');
$actionStatesView = fa_read('modules/futureagent/views/admin/action_states.php');
$actionButtonsView = fa_read('modules/futureagent/views/admin/action_buttons.php');
$helper = fa_read('modules/futureagent/helpers/futureagent_helper.php');
$js = fa_read('modules/futureagent/assets/js/futureagent.js');

fa_assert(strpos($module, 'Module Name: FutureAgent') !== false, 'FutureAgent module header is required.');
fa_assert(strpos($module, 'Version: 0.1.6') !== false, 'FutureAgent module must be bumped for controller/action button migration.');
fa_assert(strpos($module, "hooks()->add_action('after_cron_run', 'futureagent_cron')") !== false, 'FutureAgent scheduler must run from Perfex cron.');
fa_assert(strpos($module, 'register_staff_capabilities') === false, 'Use staff_permissions filter for compatibility.');
fa_assert(strpos($routes, 'futureagent/api/v1/agents/me') !== false, 'Agent identity route is required.');
fa_assert(strpos($routes, 'futureagent/api/v1/agents/(:any)/config') !== false, 'Agent config API route is required.');
fa_assert(strpos($routes, 'futureagent/api/v1/agents/(:any)/keys') !== false, 'Agent key API route is required.');
fa_assert(strpos($routes, 'futureagent/api/v1/agents/(:any)/status') !== false, 'Agent status API route is required.');
fa_assert(strpos($routes, 'futureagent/api/v1/issues/(:any)/checkout') !== false, 'Issue checkout route is required.');
fa_assert(strpos($routes, 'futureagent/api/v1/projects/(:num)/config') !== false, 'Project agent config route is required.');
fa_assert(strpos($routes, 'futureagent/api/v1/issues/(:any)/project-config') !== false, 'Issue project config route is required.');
fa_assert(strpos($routes, 'futureagent/api/v1/events') !== false, 'Polling event route is required.');
fa_assert(strpos($install, 'futureagent_proxy_base_url') !== false, '9Router proxy base URL option is required.');
fa_assert(strpos($install, 'futureagent_proxy_api_key') !== false, '9Router proxy API key option is required.');
fa_assert(strpos($install, 'futureagent_model_catalog_json') !== false, '9Router model catalog cache is required.');
fa_assert(strpos($install, 'ticketid') !== false, 'FutureAgent issue metadata must link Perfex tickets.');
fa_assert(strpos($install, 'futureagent_budget_links') !== false, 'Budget metadata must link estimates/proposals.');
fa_assert(strpos($install, '`company_id` CHAR(36) NULL') !== false, 'Agent metadata company scope must be nullable.');
fa_assert(strpos($install, '`client_id` INT NULL') !== false, 'Agent metadata must not require a client assignment.');
fa_assert(strpos($install, 'KEY `idx_fa_agents_staffid` (`staffid`)') !== false, 'Agent metadata must be indexed by staff reference.');
fa_assert(strpos($agentMigration, 'Migration_Version_012') !== false, 'Agent decoupling migration is required.');
fa_assert(strpos($agentMigration, 'MODIFY `company_id` CHAR(36) NULL') !== false, 'Agent company scope migration must allow NULL.');
fa_assert(strpos($agentMigration, 'MODIFY `client_id` INT NULL') !== false, 'Agent client scope migration must allow NULL.');
fa_assert(strpos($projectAgentMigration, 'Migration_Version_013') !== false, 'Project agent config migration is required.');
fa_assert(strpos($projectAgentMigration, 'futureagent_project_workspaces') !== false, 'Project workspace migration is required.');
fa_assert(strpos($projectAgentMigration, 'futureagent_project_env_vars') !== false, 'Project env migration is required.');
fa_assert(strpos($projectAgentMigration, 'futureagent_project_execution_policies') !== false, 'Project execution policy migration is required.');
fa_assert(strpos($paperclipAgentMigration, 'Migration_Version_014') !== false, 'Paperclip-style agent migration is required.');
fa_assert(strpos($paperclipAgentMigration, 'futureagent_agent_api_keys') !== false, 'Agent API key table migration is required.');
fa_assert(strpos($paperclipAgentMigration, 'futureagent_agent_instructions') !== false, 'Agent instructions table migration is required.');
fa_assert(strpos($paperclipAgentMigration, 'futureagent_agent_config_revisions') !== false, 'Agent config revisions table migration is required.');
fa_assert(strpos($moduleVersionMigration, 'Migration_Version_015') !== false, 'Module version migration 015 is required.');
fa_assert(strpos($controllerButtonsMigration, 'Migration_Version_016') !== false, 'FutureAgent controller/action button migration is required.');
fa_assert(strpos($controllerButtonsMigration, 'futureagent_action_types') !== false, 'FutureAgent action types migration is required.');
fa_assert(strpos($controllerButtonsMigration, 'futureagent_action_states') !== false, 'FutureAgent action states migration is required.');
fa_assert(strpos($controllerButtonsMigration, 'futureagent_controllers') !== false, 'FutureAgent controllers migration is required.');
fa_assert(strpos($controllerButtonsMigration, 'futureagent_action_buttons') !== false, 'FutureAgent action buttons migration is required.');
fa_assert(strpos($controllerButtonsMigration, 'futureagent_controller_action_buttons') !== false, 'FutureAgent controller/action button mapping migration is required.');

foreach ([
    'futureagent_companies',
    'futureagent_agents',
    'futureagent_projects',
    'futureagent_issues',
    'futureagent_comments',
    'futureagent_runs',
    'futureagent_run_events',
    'futureagent_approvals',
    'futureagent_cost_events',
    'futureagent_budget_policies',
    'futureagent_activity',
    'futureagent_routines',
    'futureagent_secrets',
    'futureagent_plugins',
    'futureagent_project_goals',
    'futureagent_project_env_vars',
    'futureagent_project_workspaces',
    'futureagent_project_workspace_runtime',
    'futureagent_project_execution_policies',
    'futureagent_agent_api_keys',
    'futureagent_agent_instructions',
    'futureagent_agent_config_revisions',
    'futureagent_action_types',
    'futureagent_action_states',
    'futureagent_controllers',
    'futureagent_action_buttons',
    'futureagent_controller_action_buttons',
] as $table) {
    fa_assert(strpos($install, $table) !== false, 'Missing table: ' . $table);
}
foreach (['target_date', 'color', 'archived_at', 'pause_reason', 'paused_at', 'description_override'] as $field) {
    fa_assert(strpos($install, '`' . $field . '`') !== false, 'Missing project metadata field: ' . $field);
}
foreach (['default_environment_id', 'instructions_file_path', 'instructions_bundle_id', 'prompt_template', 'bootstrap_prompt_template', 'session_state_json', 'last_error', 'last_run_id'] as $field) {
    fa_assert(strpos($install, '`' . $field . '`') !== false, 'Missing agent config field: ' . $field);
}

fa_assert(strpos($helper, "'in_progress' => 4") !== false, 'Canonical in_progress must map to Perfex In Progress.');
fa_assert(strpos($helper, "'done'        => 5") !== false, 'Canonical done must map to Perfex Complete.');
fa_assert(strpos($model, '$this->load->model(\'clients_model\')') !== false, 'Companies must use Perfex Clients_model.');
fa_assert(strpos($model, '$this->load->model(\'staff_model\')') !== false, 'Agents must use Perfex Staff_model.');
fa_assert(strpos($model, '$this->load->model(\'tickets_model\')') !== false, 'Issues must use Perfex Tickets_model.');
fa_assert(strpos($model, '$this->load->model(\'estimates_model\')') !== false, 'Budgets must use Perfex Estimates_model.');
fa_assert(strpos($model, '$this->load->model(\'proposals_model\')') !== false, 'Budgets must use Perfex Proposals_model.');
fa_assert(strpos($model, "db_prefix() . 'clients'") !== false, 'Companies page must read Perfex clients.');
fa_assert(strpos($model, "db_prefix() . 'customer_admins'") !== false, 'Staff client scopes must still use customer_admins.');
fa_assert(strpos($model, "\$customerAdmins = db_prefix() . 'customer_admins'") !== false && strpos($model, "\$this->db->join(\$agents") !== false, 'Companies page must only show clients with configured FutureAgent agents.');
fa_assert(strpos($model, "\$this->db->join(\$meta, \$meta . '.perfex_project_id = ' . \$projects . '.id', 'inner')") !== false && strpos($model, "\$this->db->join(\$agents, \$agents . '.id = ' . \$meta . '.lead_agent_id', 'inner')") !== false, 'Projects page must only show projects with configured Project Agent lead.');
fa_assert(strpos($model, "\$this->db->join(\$meta, \$meta . '.ticketid = ' . \$tickets . '.ticketid', 'inner')") !== false && strpos($model, "\$this->db->join(\$agents, \$agents . '.id = ' . \$meta . '.assignee_agent_id', 'inner')") !== false, 'Issues page must only show issues assigned to configured FutureAgent agents.');
fa_assert(strpos($model, 'public function list_agents_native') !== false, 'FutureAgent agent listing is required.');
fa_assert(strpos($model, "\$agents = db_prefix() . 'futureagent_agents'") !== false, 'Agent listing must use futureagent_agents as the source of truth.');
fa_assert(strpos($model, "\$this->db->get(\$agents)->result_array()") !== false, 'Agent listing must not read from staff as the primary table.');
fa_assert(strpos($model, "if (\$staffId > 0)") !== false, 'Agent creation must allow optional staff assignment.');
fa_assert(strpos($model, "'staffid' => \$staffId > 0 ? \$staffId : null") !== false, 'Agent rows must support unassigned staff references.');
fa_assert(strpos($model, "'client_id' => null") !== false, 'Agent config rows must not bind one staff agent to one client.');
fa_assert(strpos($model, 'public function get_agent_for_staff($staffId)') !== false, 'Agent config must resolve by staff reference.');
fa_assert(strpos($model, 'public function get_agent_config($agentIdOrStaffId)') !== false, 'Agent config loader is required.');
fa_assert(strpos($model, 'public function save_agent_config($agentId, $data)') !== false, 'Agent config saver is required.');
fa_assert(strpos($model, 'public function set_agent_status($agentId, $status') !== false, 'Agent status toggle is required.');
fa_assert(strpos($model, 'public function delete_agent($agentId)') !== false, 'Agent delete action is required.');
fa_assert(strpos($model, 'public function create_agent_key($agentId, $name') !== false, 'Agent API key creation is required.');
fa_assert(strpos($model, 'public function revoke_agent_key($keyId)') !== false, 'Agent API key revocation is required.');
fa_assert(strpos($model, 'private function normalize_agent_runtime_config') !== false, 'Agent runtime heartbeat normalization is required.');
fa_assert(strpos($model, 'private function normalize_agent_permissions') !== false, 'Agent permissions normalization is required.');
fa_assert(strpos($model, 'llmProxy') !== false, 'Agent config must normalize to LLM proxy brain metadata.');
fa_assert(strpos($model, 'private function create_agent_config_for_staff($staffId)') !== false, 'Missing staff-referenced agent config creation.');
fa_assert(strpos($model, 'public function staff_can_access_client($staffId, $clientId)') !== false, 'Client access must be checked separately from agent identity.');
fa_assert(strpos($model, 'public function staff_client_scopes($staffId)') !== false, 'Agent API must expose client scopes from staff assignments.');
fa_assert(strpos($model, "db_prefix() . 'tickets'") !== false, 'Issues page must read Perfex tickets.');
fa_assert(strpos($model, "db_prefix() . 'estimates'") !== false, 'Budgets page must read Perfex estimates.');
fa_assert(strpos($model, "db_prefix() . 'proposals'") !== false, 'Budgets page must read Perfex proposals.');
fa_assert(strpos($model, "where(\$tasks . '.recurring', 1)") !== false, 'Routines page must read recurring Perfex tasks.');
fa_assert(strpos($model, '$this->tickets_model->add') !== false, 'Issue creation must create Perfex tickets.');
fa_assert(strpos($model, '$this->tasks_model->add') !== false, 'Checkout may create a linked Perfex execution task.');
fa_assert(strpos($model, '$this->projects_model->add') !== false, 'Projects must wrap Perfex Projects_model.');
fa_assert(strpos($model, 'public function get_project_agent_config($perfexProjectId)') !== false, 'Project agent config loader is required.');
fa_assert(strpos($model, 'public function save_project_agent_config($perfexProjectId, $data)') !== false, 'Project agent config saver is required.');
fa_assert(strpos($model, 'public function list_project_workspaces($projectMetaId)') !== false, 'Project workspace listing is required.');
fa_assert(strpos($model, 'public function upsert_project_workspace($projectMetaId, $data)') !== false, 'Project workspace upsert is required.');
fa_assert(strpos($model, 'public function save_project_env($projectMetaId, $rows)') !== false, 'Project env saver is required.');
fa_assert(strpos($model, 'public function save_execution_policy($projectMetaId, $data)') !== false, 'Project execution policy saver is required.');
fa_assert(strpos($model, 'private function assert_valid_project_workspace') !== false, 'Workspace validation is required.');
fa_assert(strpos($model, 'primaryWorkspace') !== false, 'Project config API must expose primary workspace.');
fa_assert(strpos($model, 'executionWorkspacePolicy') !== false, 'Project config API must expose execution workspace policy.');
fa_assert(strpos($model, 'checkout_conflict') !== false, 'Atomic checkout conflict handling is required.');
fa_assert(strpos($model, 'futureagent_ticket_status_for_issue') !== false, 'Canonical issue status must update ticket status.');
fa_assert(strpos($model, 'approval_status_after_native_gates') !== false, 'Approvals must honor ticket/task native gates.');
fa_assert(strpos($model, 'futureagent_hash_token') !== false, 'Agent API key hash storage is required.');
fa_assert(strpos($api, 'Authorization') !== false && strpos($api, 'Bearer ') !== false, 'Bearer API auth is required.');
fa_assert(strpos($api, 'agent_config') !== false, 'Agent config API action is required.');
fa_assert(strpos($api, 'agent_keys') !== false, 'Agent key API action is required.');
fa_assert(strpos($api, 'agent_status') !== false, 'Agent status API action is required.');
fa_assert(strpos($api, 'project_config') !== false, 'Project config API action is required.');
fa_assert(strpos($api, 'issue_project_config') !== false, 'Issue project config API action is required.');
fa_assert(strpos($api, 'X-FutureAgent-Run-Id') !== false, 'FutureAgent run header is required.');
fa_assert(strpos($api, 'X-Paperclip-Run-Id') !== false, 'Paperclip run header compatibility is required.');
fa_assert(strpos($admin, 'create_company') === false, 'Companies page must not create Perfex clients from FutureAgent.');
fa_assert(strpos($admin, 'public function agent_create()') !== false, 'Standalone agent creation route is required.');
fa_assert(strpos($admin, '$this->futureagent_model->create_agent(') !== false, 'Agent create route must create FutureAgent agent records.');
fa_assert(strpos($admin, 'staff_model->add') === false, 'Agents page must not create Perfex staff from FutureAgent.');
fa_assert(strpos($admin, 'agent_config($staffIdOrAgentId)') !== false, 'Admin agent config route is required.');
fa_assert(strpos($admin, 'public function controllers()') !== false, 'FutureAgent Controllers admin route is required.');
fa_assert(strpos($admin, 'public function controller_create()') !== false, 'FutureAgent Controller create route is required.');
fa_assert(strpos($admin, 'public function controller_edit($id)') !== false, 'FutureAgent Controller edit route is required.');
fa_assert(strpos($admin, 'public function controller_view($id)') !== false, 'FutureAgent Controller view route is required.');
fa_assert(strpos($admin, 'public function action_types()') !== false, 'FutureAgent Action Types admin route is required.');
fa_assert(strpos($admin, 'public function action_states()') !== false, 'FutureAgent Action States admin route is required.');
fa_assert(strpos($admin, 'public function action_buttons()') !== false, 'FutureAgent Action Buttons admin route is required.');
fa_assert(strpos($admin, 'public function action_button_run()') !== false, 'FutureAgent Action Button run endpoint is required.');
fa_assert(strpos($agentsView, 'FutureAgent agent model') !== false, 'Agents view must present the FutureAgent agent model, not raw staff.');
fa_assert(strpos($agentsView, 'futureagent/agents/create') !== false, 'Agents view must link to the standalone create page.');
fa_assert(strpos($agentsView, 'agent_action') !== false && strpos($agentsView, 'Disable') !== false && strpos($agentsView, 'Delete') !== false, 'Agents view must expose disable/delete controls.');
fa_assert(strpos($agentsView, 'futureagent-create-agent-form') === false, 'Agents list must not embed the create form.');
fa_assert(strpos($agentsView, "\$row['metadata_id']") !== false, 'Agents view must link config by agent id, not staffid.');
fa_assert(strpos($agentsView, 'No FutureAgent agents found') !== false, 'Agents empty state must refer to FutureAgent agents.');
fa_assert(strpos($agentCreateView, 'Create Agent') !== false, 'Agent create view title is required.');
fa_assert(strpos($agentCreateView, 'Identity') !== false && strpos($agentCreateView, 'LLM Brain') !== false && strpos($agentCreateView, 'Runtime') !== false, 'Agent create view must expose LLM profile sections.');
fa_assert(strpos($agentCreateView, 'Unassigned agent') !== false, 'Agent create view must allow unassigned agents.');
fa_assert(strpos($agentCreateView, 'runtime_config[heartbeat][enabled]') !== false, 'Agent create view must configure heartbeat runtime.');
fa_assert(strpos($admin, 'proxy_models_for_agent_forms') !== false, 'Agent forms must load model catalog directly from proxy.');
fa_assert(strpos($agentCreateView, 'name="adapter_config[model]"') !== false, 'Agent create view must select model from proxy catalog.');
fa_assert(strpos($agentConfigView, 'name="adapter_config[model]"') !== false, 'Agent config view must select model from proxy catalog.');
fa_assert(strpos($agentCreateView, "render_input('adapter_config[model]'") === false, 'Agent create model must not be a free text input.');
fa_assert(strpos($agentConfigView, "render_input('adapter_config[model]'") === false, 'Agent config model must not be a free text input.');
fa_assert(strpos($agentConfigView, 'LLM config JSON') === false, 'Agent config should hide raw LLM JSON for better UX.');
fa_assert(strpos($agentConfigView, 'name="adapter_config_json"') === false, 'Agent config should not expose raw LLM JSON textarea.');
fa_assert(strpos($agentConfigView, 'Runtime config JSON') === false, 'Agent config should not expose raw runtime JSON.');
fa_assert(strpos($agentConfigView, 'futureagent-agent-config-shell') !== false, 'Agent config must use the new section shell layout.');
fa_assert(strpos($agentConfigView, 'data-fa-config-section') !== false, 'Agent config must expose section navigation anchors.');
foreach (['Định danh (Identity)', 'Bộ não (LLM Brain)', 'Vận hành (Operations)', 'Ngân sách (Budget)', 'Bảo mật (Security)', 'Runs'] as $agentConfigSection) {
    fa_assert(strpos($agentConfigView, $agentConfigSection) !== false, 'Agent config missing section: ' . $agentConfigSection);
}
fa_assert(strpos($agentConfigView, 'Back to Agents') !== false && strpos($agentConfigView, "admin_url('futureagent/agents')") !== false, 'Agent config must link back to Agents.');
fa_assert(strpos($agentConfigView, "admin_url('futureagent/runs')") !== false, 'Agent config must link to Runs.');
fa_assert(strpos($agentConfigView, "admin_url('futureagent/settings')") !== false, 'Agent config must link to Settings.');
fa_assert(strpos($admin, 'project_config($perfexProjectId)') !== false, 'Admin project config route is required.');
fa_assert(strpos($projectConfigView, 'Back to Projects') !== false && strpos($projectConfigView, "admin_url('futureagent/projects')") !== false, 'Project config must link back to Projects.');
fa_assert(strpos($projectConfigView, "admin_url('futureagent/issues')") !== false, 'Project config must link to Issues.');
fa_assert(strpos($projectConfigView, "admin_url('futureagent/settings')") !== false, 'Project config must link to Settings.');
fa_assert(strpos($issuesView, 'Create issue') === false && strpos($issuesView, 'form_open(admin_url(\'futureagent/issues\'))') === false, 'Issues page must not expose issue creation.');
fa_assert(strpos($agentCreateView, "admin_url('futureagent/settings')") !== false, 'Agent create must link to Settings.');
fa_assert(strpos($agentsView, "admin_url('futureagent')") !== false, 'Agents list must link to Dashboard.');
fa_assert(strpos($projectsView, "admin_url('futureagent')") !== false, 'Projects list must link to Dashboard.');
fa_assert(strpos($companiesView, "admin_url('futureagent')") !== false, 'Companies list must link to Dashboard.');
foreach ([
    'issues' => $issuesView,
    'runs' => $runsView,
    'approvals' => $approvalsView,
    'settings' => $settingsView,
    'generic' => $genericView,
] as $viewName => $viewSource) {
    fa_assert(strpos($viewSource, 'futureagent-pagebar') !== false, ucfirst($viewName) . ' view must use the FutureAgent pagebar.');
    fa_assert(strpos($viewSource, 'Dashboard') !== false && strpos($viewSource, "admin_url('futureagent')") !== false, ucfirst($viewName) . ' view must link to Dashboard.');
}
foreach ([
    'dashboard' => $dashboardView,
    'agents' => $agentsView,
    'agent_config' => $agentConfigView,
] as $viewName => $viewSource) {
    fa_assert(strpos($viewSource, 'futureagent-pagebar') !== false, $viewName . ' view must use the FutureAgent pagebar.');
    foreach ([
        'futureagent-dashboard-topnav',
        'futureagent-dashboard-side',
        'futureagent-agent-config-topbar',
        'futureagent-agent-config-rail',
        'AI Orchestration',
        'Agent Library',
        'Agent Hub',
        'Run History',
    ] as $forbiddenShellText) {
        fa_assert(strpos($viewSource, $forbiddenShellText) === false, $viewName . ' view must not render hardcoded shell navigation: ' . $forbiddenShellText);
    }
}
fa_assert(strpos($model, 'public function dashboard_data') !== false, 'Dashboard must use a dedicated data assembler.');
fa_assert(strpos($admin, '$this->futureagent_model->dashboard_data()') !== false, 'Admin dashboard must pass dashboard data to the view.');
foreach (['Tổng quan hệ thống', 'Tổng tác tử', 'Lượt chạy', 'Chờ phê duyệt', 'Chi phí LLM', 'Lượt chạy gần đây', 'Trạng thái hệ thống'] as $dashboardText) {
    fa_assert(strpos($dashboardView, $dashboardText) !== false, 'Dashboard missing section: ' . $dashboardText);
}
fa_assert(strpos($dashboardView, '$dashboard') !== false, 'Dashboard view must render real dashboard data.');
fa_assert(strpos($dashboardView, 'futureagent-dashboard-board') !== false, 'Dashboard must use the Perfex-embedded FutureAgent board layout.');
fa_assert(strpos($dashboardView, 'futureagent-dashboard-stat') !== false, 'Dashboard must render dashboard stat cards.');
fa_assert(strpos($llmBrain, 'class Futureagent_llm_brain') !== false, 'LLM brain service is required.');
fa_assert(strpos($llmBrain, "'/chat/completions'") !== false, 'LLM brain must call /v1/chat/completions.');
fa_assert(strpos($llmBrain, 'Authorization') !== false && strpos($llmBrain, 'Bearer ') !== false, 'LLM brain must use bearer auth.');
fa_assert(strpos($executor, 'class Futureagent_action_executor') !== false, 'Perfex action executor is required.');
fa_assert(strpos($executor, 'available_actions') !== false, 'Action executor must expose an action catalog.');
fa_assert(strpos($executor, 'requires_approval') !== false, 'Action executor must enforce approval gate metadata.');
fa_assert(strpos($executor, 'execute_approved_action') !== false, 'Approved actions must execute through the Perfex action executor.');
fa_assert(strpos($executor, 'staff_user_id') !== false && strpos($executor, 'staff_logged_in') !== false, 'Action executor must use FlexibleWA-style automator session.');
fa_assert(strpos($executor, 'tasks_model') !== false && strpos($executor, 'tickets_model') !== false, 'Action executor must use Perfex core models.');
fa_assert(strpos($model, 'execute_llm_run') !== false, 'Runs must execute through the LLM brain flow.');
fa_assert(strpos($model, 'public function list_action_types') !== false, 'FutureAgent Action Types model listing is required.');
fa_assert(strpos($model, 'public function save_action_type') !== false, 'FutureAgent Action Types model save is required.');
fa_assert(strpos($model, 'public function list_action_states') !== false, 'FutureAgent Action States model listing is required.');
fa_assert(strpos($model, 'public function save_action_state') !== false, 'FutureAgent Action States model save is required.');
fa_assert(strpos($model, 'public function list_agent_controllers') !== false, 'FutureAgent Controllers model listing is required.');
fa_assert(strpos($model, 'public function get_agent_controller($id)') !== false, 'FutureAgent Controller detail loader is required.');
fa_assert(strpos($model, 'public function controller_button_assignments($controllerId)') !== false, 'FutureAgent Controller assignment listing is required.');
fa_assert(strpos($model, 'public function save_agent_controller') !== false, 'FutureAgent Controllers model save is required.');
fa_assert(strpos($model, 'public function list_agent_action_buttons') !== false, 'FutureAgent Action Buttons model listing is required.');
fa_assert(strpos($model, 'public function save_agent_action_button') !== false, 'FutureAgent Action Buttons model save is required.');
fa_assert(strpos($model, 'public function assign_controller_button') !== false, 'Controller action button assignment is required.');
fa_assert(strpos($model, 'public function action_buttons_for_issue') !== false, 'Issue action button visibility resolver is required.');
fa_assert(strpos($model, 'public function create_action_button_run') !== false, 'Action button click must create a FutureAgent run.');
fa_assert(strpos($model, 'build_llm_run_context') !== false, 'LLM run context builder is required.');
fa_assert(strpos($model, 'actionButton') !== false, 'LLM context must include action button trigger metadata.');
fa_assert(strpos($model, 'workInstruction') !== false, 'LLM context must include operator work instructions.');
fa_assert(strpos($model, 'execute_approved_action') !== false, 'Approved approval-gated actions must be executed after approval.');
fa_assert(strpos($model, 'action.rejected') !== false, 'Rejected LLM actions must be logged.');
fa_assert(strpos($model, 'approval.created') !== false, 'Approval-gated actions must create approvals.');
fa_assert(strpos($model, 'discover_proxy_models') !== false && strpos($model, "'/models'") !== false, '9Router model discovery must use /v1/models.');
fa_assert(strpos($model, 'Authorization') !== false && strpos($model, 'Bearer ') !== false, '9Router model discovery must use bearer auth.');
fa_assert(strpos($settingsView, 'Local adapter allowlist') === false, 'Settings UI must not expose local adapter allowlists.');
fa_assert(strpos($agentConfigView, 'Adapter type') === false && strpos($agentCreateView, 'Adapter type') === false, 'Agent UI must not expose adapter selection.');
fa_assert(strpos($agentConfigView, 'LLM Brain') !== false, 'Agent config must use LLM Brain terminology.');
fa_assert(strpos($dashboardView, 'LLM brain via proxy, CRM execution via Perfex models') !== false, 'Dashboard must describe LLM brain and Perfex executor.');
fa_assert(strpos($approvalsView, 'futureagent-approvals-board') !== false, 'Approvals view must use the FutureAgent approvals gateway layout.');
fa_assert(strpos($approvalsView, 'Submit decision') === false, 'Approvals view must not require manual approval ID entry.');
fa_assert(strpos($approvalsView, 'Phê duyệt &amp; thực thi') !== false && strpos($approvalsView, 'Xem payload') !== false, 'Approvals view must expose direct decision and payload actions.');
fa_assert(strpos($runsView, 'Action plan') !== false, 'Runs page must expose action plan/execution results.');
fa_assert(strpos($runsView, 'action_button') !== false, 'Runs page must surface action_button trigger source.');
fa_assert(strpos($runsView, 'wake_issue_id') !== false && strpos($runsView, 'wake_instruction') !== false, 'Runs page must support issue-scoped work instructions.');
fa_assert(strpos($js, 'futureAgentInitialized') !== false, 'Admin JS must guard duplicate init.');
fa_assert(strpos($js, 'activateFutureAgentSidebar') !== false, 'Admin JS must activate the FutureAgent sidebar item for nested routes.');
fa_assert(strpos($js, 'menu-item-futureagent') !== false && strpos($js, 'sub-menu-item-futureagent_') !== false, 'Admin JS must target Perfex sidebar FutureAgent menu classes.');
fa_assert(strpos($js, "agents: 'futureagent_agents'") !== false && strpos($js, "controllers: 'futureagent_controllers'") !== false, 'Admin JS must map FutureAgent route sections to submenu slugs.');
fa_assert(strpos($module, 'futureagent_controllers') !== false, 'FutureAgent sidebar must include Controllers.');
fa_assert(strpos($module, 'futureagent_action_types') !== false, 'FutureAgent sidebar must include Action Types.');
fa_assert(strpos($module, 'futureagent_action_states') !== false, 'FutureAgent sidebar must include Action States.');
fa_assert(strpos($module, 'futureagent_action_buttons') !== false, 'FutureAgent sidebar must include Agent Action Buttons.');
foreach ([
    'controllers' => $controllersView,
    'controller_create' => $controllerCreateView,
    'controller_edit' => $controllerEditView,
    'controller_view' => $controllerView,
    'action_types' => $actionTypesView,
    'action_states' => $actionStatesView,
    'action_buttons' => $actionButtonsView,
] as $viewName => $viewSource) {
    fa_assert(strpos($viewSource, 'futureagent-pagebar') !== false, ucfirst($viewName) . ' view must use the FutureAgent pagebar.');
    fa_assert(strpos($viewSource, "admin_url('futureagent')") !== false, ucfirst($viewName) . ' view must link to Dashboard.');
}
fa_assert(strpos($controllersView, 'Create Controller') !== false && strpos($controllersView, "admin_url('futureagent/controllers/create')") !== false, 'Controllers manage view must link to create page.');
fa_assert(strpos($controllersView, "form_open(admin_url('futureagent/controllers'))") === false, 'Controllers manage view must not embed create/edit forms.');
fa_assert(strpos($controllersView, 'futureagent/controllers/view/') !== false, 'Controllers manage view must link to controller view page.');
fa_assert(strpos($controllersView, 'futureagent/controllers/edit/') !== false, 'Controllers manage view must link to controller edit page.');
fa_assert(strpos($controllerCreateView, "form_open(admin_url('futureagent/controllers/create')") !== false, 'Controller create view must post to create route.');
fa_assert(strpos($controllerEditView, "form_open(admin_url('futureagent/controllers/edit/'") !== false, 'Controller edit view must post to edit route.');
fa_assert(strpos($controllerView, 'Controller Action Buttons') !== false, 'Controller view must show action button assignments.');
fa_assert(strpos($controllerView, 'assign_button_id') !== false, 'Controller view must assign action buttons from detail page.');
fa_assert(strpos($issuesView, 'futureagent-action-button-strip') !== false, 'Issues view must render FutureAgent action button strip.');
fa_assert(strpos($issuesView, 'action_button_id') !== false, 'Issues view must submit action button runs.');

$all = $module . $routes . $install . $model . $api . $admin . $helper . $js . $llmBrain . $executor . $agentCreateView . $agentConfigView . $projectConfigView . $companiesView . $projectsView . $issuesView . $approvalsView . $settingsView . $dashboardView . $runsView . $genericView . $controllersView . $controllerCreateView . $controllerEditView . $controllerView . $actionTypesView . $actionStatesView . $actionButtonsView;
foreach (['shell_exec', 'passthru', 'system(', 'proc_open', 'codex_local', 'claude_local', 'gemini_local', 'opencode_local', 'pi_local', 'cursor_cloud', 'openclaw_gateway', 'adapter_binary_missing', 'adapter_not_enabled'] as $forbidden) {
    fa_assert(strpos($all, $forbidden) === false, 'Local execution is forbidden: ' . $forbidden);
}
fa_assert(!preg_match('/(?<!curl_)\\bexec\\s*\\(/', $all), 'Local execution is forbidden: exec()');

echo "FutureAgent static checks passed.\n";
