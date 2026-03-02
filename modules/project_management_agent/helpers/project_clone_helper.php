<?php
defined('BASEPATH') or exit('No direct script access allowed');

class ProjectCloneHelper
{
    protected $CI;
    public function __construct()
    {
        $this->CI = &get_instance();
        $this->CI->load->model('projects_model');
    }

    public function executeClone(array $clone_config): array
    {
        try {
            $sourceId = (int) ($clone_config['source_project_id'] ?? 0);
            if ($sourceId <= 0) { return ['success' => false, 'error' => 'Invalid source project']; }
            // Use core Projects_model->copy when executing real clone (not implemented here)
            // $newId = $this->CI->projects_model->copy($sourceId, $additionalData);
            return ['success' => true, 'new_project_id' => null];
        } catch (Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}

