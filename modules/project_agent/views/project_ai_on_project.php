<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$CI = &get_instance();
$project_context = null;
$actions = [];
$ai_available = false;
$ai_status = [];

// Build project context safely if project is available
if (isset($project) && isset($project->id)) {
	$ctx = new ProjectAgentContextHelper();
	$project_context = $ctx->buildProjectContext($project->id);
}

// Load available actions (best-effort)
try {
	$registry = new ProjectAgentActionRegistry();
	$all = $registry->getAllActions();
	foreach ($all as $action_id => $action) {
		if ($registry->checkPermissions($action_id, get_staff_user_id())) {
			$actions[$action_id] = $action;
		}
	}
} catch (Exception $e) {}

// AI provider status (best-effort)
try {
	$ai = new ProjectAgentAiIntegration();
	$ai_status = $ai->getGeminiaiRecommendation();
	$ai_available = $ai->isAiAvailable();
} catch (Exception $e) {}

$view_data = [
	'project' => isset($project) ? $project : null,
	'project_context' => $project_context,
	'actions' => $actions,
	'ai_status' => $ai_status,
	'ai_available' => $ai_available,
	'session' => null,
];

$CI->load->view('project_agent/ai_room', $view_data);
?>

