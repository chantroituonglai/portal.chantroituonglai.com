<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Project_management_agent_model extends App_Model
{
    protected $compositions;
    protected $clones;

    public function __construct()
    {
        parent::__construct();
        // Ensure DB is available before resolving table names
        $this->load->database();
        // Resolve table names robustly
        $this->compositions = $this->resolve_table_name('project_management_agent_compositions');
        $this->clones       = $this->resolve_table_name('project_management_agent_clones');
        $this->load->model('projects_model');
        $this->load->helper('project_management_agent');
        $this->load->helper('url');
    }

    public function create_composition(int $project_id)
    {
        $this->load->helper('project_management_agent');
        $this->load->helper('url');
        $this->load->library('session');
        require_once module_dir_path('project_management_agent', 'helpers/project_composer_helper.php');
        $composer = new ProjectComposerHelper();

        $data = $composer->collectProjectData($project_id);
        if (empty($data)) {
            throw new Exception('Project not found or no data collected');
        }

        $row = [
            'source_project_id' => $project_id,
            'user_id'           => (function_exists('get_staff_user_id') ? (int) get_staff_user_id() : 0),
            'composition_data'  => json_encode($data),
            'ai_breakdown'      => null,
            'status'            => 'analyzing',
            'created_at'        => pma_now(),
            'updated_at'        => pma_now(),
        ];
        $this->db->insert($this->compositions, $row);
        return (int) $this->db->insert_id();
    }

    public function get_composition(int $composition_id)
    {
        $row = $this->db->where('composition_id', $composition_id)->get($this->compositions)->row_array();
        return $row ?: null;
    }

    public function run_ai_breakdown(int $composition_id)
    {
        $row = $this->db->where('composition_id', $composition_id)->get($this->compositions)->row_array();
        if (!$row) { throw new Exception('Composition not found'); }
        $comp = json_decode($row['composition_data'], true);
        if (!is_array($comp)) { $comp = []; }

        require_once module_dir_path('project_management_agent', 'helpers/project_composer_helper.php');
        $composer = new ProjectComposerHelper();

        $res = $composer->sendToAI($comp);
        $html = $this->generateDetailedBreakdownHTML($comp, $res);

        // Persist breakdown (store raw response best-effort)
        $json = json_encode($res, JSON_PARTIAL_OUTPUT_ON_ERROR);
        if ($json === false) { $json = 'null'; }
        $this->db->where('composition_id', $composition_id)->update($this->compositions, [
            'ai_breakdown' => $json,
            'status'       => 'ready',
            'updated_at'   => pma_now(),
        ]);

        return ['html' => $html, 'raw' => $res];
    }

    private function generateDetailedBreakdownHTML(array $comp, array $aiResponse): string
    {
        $html = '';
        // AI status
        if (is_array($aiResponse) && !empty($aiResponse['success'])) {
            $html .= '<div class="alert alert-success"><i class="fa fa-check"></i> AI breakdown completed successfully.</div>';
        } else {
            $err = is_array($aiResponse) && isset($aiResponse['error']) ? $aiResponse['error'] : 'AI analysis completed with limited data.';
            $html .= '<div class="alert alert-warning"><i class="fa fa-exclamation-triangle"></i> ' . htmlspecialchars((string)$err) . '</div>';
        }

        // Project overview
        $project = $comp['project'] ?? null;
        if ($project) {
            // Convert object to array if needed
            if (is_object($project)) {
                $project = (array) $project;
            }
            
            $html .= '<div class="panel panel-default">';
            $html .= '<div class="panel-heading"><h6><i class="fa fa-info-circle"></i> Project Overview</h6></div>';
            $html .= '<div class="panel-body">';
            $html .= '<div class="row">';
            $html .= '<div class="col-md-6">';
            $html .= '<table class="table table-borderless">';
            $html .= '<tr><td><strong>Name:</strong></td><td>' . htmlspecialchars($project['name'] ?? 'N/A') . '</td></tr>';
            $clientName = 'N/A';
            try {
                if (isset($project['client_data']) && is_array($project['client_data']) && isset($project['client_data']['company'])) {
                    $clientName = (string) $project['client_data']['company'];
                } elseif (isset($project['clientid'])) {
                    $this->load->model('clients_model');
                    $c = $this->clients_model->get($project['clientid']);
                    if ($c && isset($c->company)) { $clientName = (string) $c->company; }
                }
            } catch (\Throwable $e) { /* ignore */ }
            $html .= '<tr><td><strong>Client:</strong></td><td>' . htmlspecialchars($clientName) . '</td></tr>';
            $html .= '<tr><td><strong>Start Date:</strong></td><td>' . _d($project['start_date'] ?? '') . '</td></tr>';
            $html .= '<tr><td><strong>Deadline:</strong></td><td>' . _d($project['deadline'] ?? '') . '</td></tr>';
            $html .= '</table>';
            $html .= '</div>';
            $html .= '<div class="col-md-6">';
            $html .= '<table class="table table-borderless">';
            $html .= '<tr><td><strong>Status:</strong></td><td>' . $this->getProjectStatusBadge($project['status'] ?? 0) . '</td></tr>';
            $html .= '<tr><td><strong>Progress:</strong></td><td>' . (isset($project['progress']) ? (int)$project['progress'] . '%' : 'N/A') . '</td></tr>';
            $html .= '</table>';
            $html .= '</div>';
            $html .= '</div>';
            $html .= '</div>';
            $html .= '</div>';
            
            // Separate Description Panel
            $desc = isset($project['description']) ? (string)$project['description'] : '';
            if ($desc !== '') {
                // Decode HTML entities and clean up
                $desc = html_entity_decode($desc, ENT_QUOTES, 'UTF-8');
                $desc = strip_tags($desc);
                $desc = trim($desc);
                
                if ($desc !== '') {
                    $html .= '<div class="panel panel-default">';
                    $html .= '<div class="panel-heading"><h6><i class="fa fa-file-text-o"></i> Project Description</h6></div>';
                    $html .= '<div class="panel-body">';
                    $html .= '<div class="well" style="max-height: 200px; overflow-y: auto;">';
                    $html .= '<p>' . nl2br(htmlspecialchars($desc)) . '</p>';
                    $html .= '</div>';
                    $html .= '</div>';
                    $html .= '</div>';
                }
            }
        }

        // Tasks analysis
        $tasks = isset($comp['tasks']) && is_array($comp['tasks']) ? $comp['tasks'] : [];
        $html .= '<div class="panel panel-default">';
        $html .= '<div class="panel-heading"><h6><i class="fa fa-tasks"></i> Tasks Analysis (' . count($tasks) . ')</h6></div>';
        $html .= '<div class="panel-body">';
        if (!empty($tasks)) {
            $html .= '<div class="table-responsive"><table class="table table-striped">';
            $html .= '<thead><tr><th>Task</th><th>Status</th><th>Start</th><th>Due</th><th>Priority</th><th>Assignees</th></tr></thead><tbody>';
            foreach ($tasks as $task) {
                $taskId = isset($task['id']) ? (int)$task['id'] : 0;
                $html .= '<tr class="task-row" data-task-id="' . $taskId . '" style="cursor: pointer;">';
                $tname = isset($task['name']) ? (string)$task['name'] : 'N/A';
                $tdesc = isset($task['description']) ? strip_tags((string)$task['description']) : '';
                $html .= '<td><strong>' . htmlspecialchars($tname) . '</strong>' . ($tdesc ? '<br><small class="text-muted">' . htmlspecialchars(mb_strimwidth($tdesc, 0, 100, '...')) . '</small>' : '') . '</td>';
                $html .= '<td>' . $this->getTaskStatusBadge($task['status'] ?? 0) . '</td>';
                $html .= '<td>' . _d($task['startdate'] ?? '') . '</td>';
                $html .= '<td>' . _d($task['duedate'] ?? '') . '</td>';
                $html .= '<td>' . $this->getTaskPriorityBadge($task['priority'] ?? 0) . '</td>';
                $assignees = isset($task['assignees_ids']) ? (string)$task['assignees_ids'] : '';
                $html .= '<td>' . htmlspecialchars($assignees ?: '-') . '</td>';
                $html .= '</tr>';
            }
            $html .= '</tbody></table></div>';
            // Stats
            $html .= '<div class="row">';
            $html .= '<div class="col-md-3"><div class="info-box"><div class="info-box-content"><span class="info-box-text">Total Tasks</span><span class="info-box-number">' . count($tasks) . '</span></div></div></div>';
            $html .= '<div class="col-md-3"><div class="info-box"><div class="info-box-content"><span class="info-box-text">Completed</span><span class="info-box-number">' . $this->countTasksByStatus($tasks, 5) . '</span></div></div></div>';
            $html .= '<div class="col-md-3"><div class="info-box"><div class="info-box-content"><span class="info-box-text">In Progress</span><span class="info-box-number">' . $this->countTasksByStatus($tasks, 4) . '</span></div></div></div>';
            $html .= '<div class="col-md-3"><div class="info-box"><div class="info-box-content"><span class="info-box-text">Not Started</span><span class="info-box-number">' . $this->countTasksByStatus($tasks, 1) . '</span></div></div></div>';
            $html .= '</div>';
        } else {
            $html .= '<p class="text-muted">No tasks found in this project.</p>';
        }
        $html .= '</div></div>';

        // Milestones
        $milestones = isset($comp['milestones']) && is_array($comp['milestones']) ? $comp['milestones'] : [];
        $html .= '<div class="panel panel-default">';
        $html .= '<div class="panel-heading"><h6><i class="fa fa-flag"></i> Milestones (' . count($milestones) . ')</h6></div>';
        $html .= '<div class="panel-body">';
        if (!empty($milestones)) {
            foreach ($milestones as $m) {
                $html .= '<div class="milestone-item" style="border-left:4px solid #007bff;padding-left:15px;margin-bottom:12px;">';
                $html .= '<h6>' . htmlspecialchars($m['name'] ?? 'Unnamed Milestone') . '</h6>';
                $html .= '<p><strong>Due Date:</strong> ' . _d($m['due_date'] ?? '') . '</p>';
                if (!empty($m['description'])) { $html .= '<p>' . htmlspecialchars(strip_tags($m['description'])) . '</p>'; }
                $html .= '</div>';
            }
        } else {
            $html .= '<p class="text-muted">No milestones found in this project.</p>';
        }
        $html .= '</div></div>';

        // Team
        $members = isset($comp['members']) && is_array($comp['members']) ? $comp['members'] : [];
        if (!empty($members)) {
            $html .= '<div class="panel panel-default">';
            $html .= '<div class="panel-heading"><h6><i class="fa fa-users"></i> Project Team (' . count($members) . ')</h6></div>';
            $html .= '<div class="panel-body"><div class="row">';
            foreach ($members as $mem) {
                $fn = isset($mem['firstname']) ? $mem['firstname'] : '';
                $ln = isset($mem['lastname']) ? $mem['lastname'] : '';
                $html .= '<div class="col-md-4 col-sm-6"><div class="team-member" style="text-align:center;padding:10px;border:1px solid #eee;border-radius:4px;margin-bottom:10px;">';
                $html .= '<h6>' . htmlspecialchars(trim($fn . ' ' . $ln)) . '</h6>';
                $html .= '<small class="text-muted">ID: ' . htmlspecialchars((string)($mem['staff_id'] ?? '')) . '</small>';
                $html .= '</div></div>';
            }
            $html .= '</div></div></div>';
        }

        // Files
        $files = isset($comp['files']) && is_array($comp['files']) ? $comp['files'] : [];
        if (!empty($files)) {
            $html .= '<div class="panel panel-default">';
            $html .= '<div class="panel-heading"><h6><i class="fa fa-files-o"></i> Project Files (' . count($files) . ')</h6></div>';
            $html .= '<div class="panel-body"><ul class="list-unstyled">';
            foreach ($files as $f) {
                $name = isset($f['original_file_name']) ? $f['original_file_name'] : (isset($f['file_name']) ? $f['file_name'] : 'Unknown file');
                $html .= '<li><i class="fa fa-file-o"></i> ' . htmlspecialchars((string)$name) . '</li>';
            }
            $html .= '</ul></div></div>';
        }

        // AI Recommendations placeholder when AI responded
        if (is_array($aiResponse) && isset($aiResponse['data'])) {
            $html .= '<div class="panel panel-info">';
            $html .= '<div class="panel-heading"><h6><i class="fa fa-lightbulb-o"></i> AI Recommendations</h6></div>';
            $html .= '<div class="panel-body">';
            $html .= '<div class="alert alert-info">';
            $html .= '<p><strong>Timeline Adjustment:</strong> The AI suggests starting the new project today with adjusted dates based on the original project timeline.</p>';
            $html .= '<p><strong>Recommendations:</strong></p>';
            $html .= '<ul><li>Review and update task dependencies</li><li>Assign team members based on skills and availability</li><li>Set realistic deadlines considering current workload</li><li>Establish clear milestones and checkpoints</li></ul>';
            $html .= '</div></div></div>';
        }

        return $html;
    }

    private function getProjectStatusBadge($status): string
    {
        $badges = [
            1 => '<span class="label label-info">Not Started</span>',
            2 => '<span class="label label-warning">In Progress</span>',
            3 => '<span class="label label-success">On Hold</span>',
            4 => '<span class="label label-success">Completed</span>',
            5 => '<span class="label label-danger">Cancelled</span>',
        ];
        return $badges[(int)$status] ?? '<span class="label label-default">Unknown</span>';
    }

    private function getTaskStatusBadge($status): string
    {
        $badges = [
            1 => '<span class="label label-default">Not Started</span>',
            2 => '<span class="label label-info">Awaiting Feedback</span>',
            3 => '<span class="label label-warning">Testing</span>',
            4 => '<span class="label label-primary">In Progress</span>',
            5 => '<span class="label label-success">Completed</span>',
        ];
        return $badges[(int)$status] ?? '<span class="label label-default">Unknown</span>';
    }

    private function getTaskPriorityBadge($priority): string
    {
        $badges = [
            1 => '<span class="label label-default">Low</span>',
            2 => '<span class="label label-info">Medium</span>',
            3 => '<span class="label label-warning">High</span>',
            4 => '<span class="label label-danger">Urgent</span>',
        ];
        return $badges[(int)$priority] ?? '<span class="label label-default">Normal</span>';
    }

    private function countTasksByStatus(array $tasks, int $status): int
    {
        $count = 0;
        foreach ($tasks as $t) {
            if (isset($t['status']) && (int)$t['status'] === $status) { $count++; }
        }
        return $count;
    }

    private function resolve_table_name(string $base): string
    {
        $candidates = [
            db_prefix() . $base,
            'tbl' . $base,
            'tbltbl' . $base,
            $base,
        ];
        foreach ($candidates as $name) {
            try { if ($this->db->table_exists($name)) { return $name; } } catch (\Throwable $e) {}
        }
        return db_prefix() . $base;
    }

    public function execute_clone(int $composition_id, array $clone_config)
    {
        // Create clone log row
        $row = $this->db->where('composition_id', $composition_id)->get($this->compositions)->row_array();
        if (!$row) { throw new Exception('Composition not found'); }
        $source_project_id = (int) $row['source_project_id'];

        $cloneRow = [
            'composition_id'      => $composition_id,
            'source_project_id'   => $source_project_id,
            'new_project_id'      => null,
            'clone_config'        => json_encode($clone_config),
            'timeline_adjustments'=> null,
            'status'              => 'processing',
            'progress'            => 10,
            'error_message'       => null,
            'created_at'          => pma_now(),
            'completed_at'        => null,
        ];
        $this->db->insert($this->clones, $cloneRow);
        $clone_id = (int) $this->db->insert_id();

        require_once module_dir_path('project_management_agent', 'helpers/project_clone_helper.php');
        $cloner = new ProjectCloneHelper();
        $cfg = array_merge($clone_config, ['source_project_id' => $source_project_id, 'composition_id' => $composition_id, 'clone_id' => $clone_id]);
        $res = $cloner->executeClone($cfg);

        if (is_array($res) && !empty($res['success'])) {
            $newId = isset($res['new_project_id']) ? (int) $res['new_project_id'] : 0;
            $this->db->where('clone_id', $clone_id)->update($this->clones, [
                'status'        => 'completed',
                'progress'      => 100,
                'new_project_id'=> $newId > 0 ? $newId : null,
                'completed_at'  => pma_now(),
            ]);
            return $newId;
        }

        $err = is_array($res) && isset($res['error']) ? $res['error'] : 'Clone failed';
        $this->db->where('clone_id', $clone_id)->update($this->clones, [
            'status'        => 'failed',
            'progress'      => 100,
            'error_message' => $err,
            'completed_at'  => pma_now(),
        ]);
        throw new Exception($err);
    }
}
