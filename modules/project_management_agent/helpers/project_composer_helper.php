<?php
defined('BASEPATH') or exit('No direct script access allowed');

class ProjectComposerHelper
{
    protected $CI;

    public function __construct()
    {
        $this->CI = &get_instance();
        $this->CI->load->model('projects_model');
    }

    public function collectProjectData(int $project_id): array
    {
        $data = [];
        $proj = $this->CI->projects_model->get($project_id);
        if (!$proj) { return []; }
        $data['project'] = $proj;
        
        // Comprehensive project data (best-effort with fallbacks)
        try { $data['tasks'] = $this->CI->projects_model->get_tasks($project_id); } catch (Throwable $e) { $data['tasks'] = []; }
        try { $data['milestones'] = $this->CI->projects_model->get_milestones($project_id); } catch (Throwable $e) { $data['milestones'] = []; }
        try { $data['files'] = $this->CI->projects_model->get_files($project_id); } catch (Throwable $e) { $data['files'] = []; }
        try { $data['members'] = $this->CI->projects_model->get_project_members($project_id, true); } catch (Throwable $e) { $data['members'] = []; }
        try { $data['discussions'] = $this->CI->projects_model->get_discussions($project_id); } catch (Throwable $e) { $data['discussions'] = []; }
        try { $data['settings'] = $this->CI->projects_model->get_project_settings($project_id); } catch (Throwable $e) { $data['settings'] = []; }
        return $data;
    }

    public function sendToAI(array $composition): array
    {
        if (!function_exists('module_dir_path')) { return ['success' => false, 'error' => 'Module API unavailable']; }
        if (!pma_is_project_agent_available()) {
            return ['success' => false, 'error' => 'Project Agent module is required'];
        }
        // Attempt to use Project Agent AI integration
        $helperPath = module_dir_path('project_agent', 'helpers/project_agent_ai_integration_helper.php');
        if (!is_file($helperPath)) {
            return ['success' => false, 'error' => 'AI integration helper not found'];
        }
        require_once $helperPath;

        try {
            $ai = new ProjectAgentAiIntegration();
            if (!$ai->isAiAvailable()) {
                return ['success' => false, 'error' => 'AI provider is not available'];
            }
            $prompt = 'Analyze the provided project data and create a detailed breakdown including:\n'
                . '1. Project overview and objectives\n'
                . '2. Task analysis with dependencies and timeline\n'
                . '3. Resource requirements and team structure\n'
                . '4. Risk assessment and mitigation strategies\n'
                . '5. Proposed timeline adjustments for a new project starting today\n'
                . '6. Recommendations for improvement\n\n'
                . 'Return a detailed JSON response with structured analysis.';
            $context = [
                'project_id' => isset($composition['project']->id) ? (int)$composition['project']->id : null,
                'available_actions' => [],
            ];
            $res = $ai->generateResponse($prompt . "\n\nPROJECT_JSON:\n" . json_encode($composition), $context);
            return ['success' => true, 'data' => $res];
        } catch (Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
