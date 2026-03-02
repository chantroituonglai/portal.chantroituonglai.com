<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Project synchronization utilities for Topics module
 * - Ensure milestones per action_type per project
 * - Create/link tasks per topic under a controller's project
 * - Sync task status/content when topic state changes
 */

if (!function_exists('topics_ensure_links_table')) {
	function topics_ensure_links_table() {
		$CI = &get_instance();
		$table = db_prefix() . 'topics_task_links';
		$CI->db->query('CREATE TABLE IF NOT EXISTS `' . $table . '` (
			`id` int(11) NOT NULL AUTO_INCREMENT,
			`topic_id` int(11) DEFAULT NULL,
			`topic_master_id` int(11) DEFAULT NULL,
			`topic_master_topicid` varchar(255) DEFAULT NULL,
			`task_id` int(11) NOT NULL,
			`controller_id` int(11) DEFAULT NULL,
			`project_id` int(11) DEFAULT NULL,
			`datecreated` datetime DEFAULT current_timestamp(),
			PRIMARY KEY (`id`),
			UNIQUE KEY `uniq_master_topicid` (`topic_master_topicid`),
			KEY `topic_id` (`topic_id`),
			KEY `topic_master_id` (`topic_master_id`),
			KEY `task_id` (`task_id`),
			KEY `controller_id` (`controller_id`),
			KEY `project_id` (`project_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;');

		// Backward compatible schema upgrades
		$CI->db->query('ALTER TABLE `'.$table.'` ADD COLUMN IF NOT EXISTS `topic_master_id` int(11) DEFAULT NULL');
		$CI->db->query('ALTER TABLE `'.$table.'` ADD COLUMN IF NOT EXISTS `topic_master_topicid` varchar(255) DEFAULT NULL');
		// Add new unique index if not exists
		$indexes = $CI->db->query('SHOW INDEX FROM `'.$table.'` WHERE Key_name = "uniq_master_topicid"')->result();
		if (empty($indexes)) {
			$CI->db->query('ALTER TABLE `'.$table.'` ADD UNIQUE KEY `uniq_master_topicid` (`topic_master_topicid`)');
		}
	}
}

if (!function_exists('topics_get_controller_for_topic')) {
	function topics_get_controller_for_topic($topic_id) {
		$CI = &get_instance();
		$CI->db->select('tm.id as topic_master_id, tm.topicid as topic_master_topicid, tm.controller_id, tc.project_id');
		$CI->db->from(db_prefix().'topics t');
		$CI->db->join(db_prefix().'topic_master tm', 'tm.topicid = t.topicid', 'left');
		$CI->db->join(db_prefix().'topic_controllers tc', 'tc.id = tm.controller_id', 'left');
		$CI->db->where('t.id', $topic_id);
		return $CI->db->get()->row();
	}
}

if (!function_exists('topics_ensure_project_milestones_for_controller')) {
	function topics_ensure_project_milestones_for_controller($controller_id) {
		$CI = &get_instance();
		$controller = $CI->db->where('id', $controller_id)->get(db_prefix().'topic_controllers')->row();
		if (!$controller || empty($controller->project_id)) { return []; }
		$project_id = (int)$controller->project_id;

		$CI->load->model('projects_model');
		$types = $CI->db->order_by('position','ASC')->get(db_prefix().'topic_action_types')->result();
		$map = [];
		foreach ($types as $type) {
			// Milestone key name to avoid duplicates
			$name = 'TOPICS:' . $type->action_type_code;
			$exists = $CI->db->where(['project_id' => $project_id, 'name' => $name])->get(db_prefix().'milestones')->row();
			if ($exists) {
				$map[$type->action_type_code] = (int)$exists->id;
				continue;
			}
			$data = [
				'name'        => $name,
				'description' => 'Auto-created for Topics action type ' . $type->action_type_code,
				'project_id'  => $project_id,
				'color'       => '#475569',
				'order'       => (int)$type->position,
				'datecreated' => date('Y-m-d'),
				'hide_from_customer' => 0,
				// dates - keep minimal
				'start_date'  => _d(date('Y-m-d')),
				'due_date'    => _d(date('Y-m-d', strtotime('+30 days'))),
			];
			// Normalize to Projects_model expected field names
			$data['start_date'] = $data['start_date'];
			$data['due_date'] = $data['due_date'];
			$milestone_id = $CI->projects_model->add_milestone($data);
			if ($milestone_id) {
				$map[$type->action_type_code] = (int)$milestone_id;
			}
		}
		return $map;
	}
}

if (!function_exists('topics_get_or_create_task_for_topic')) {
	function topics_get_or_create_task_for_topic($topic_id, $controller_id, $action_type_code = null) {
		$CI = &get_instance();
		topics_ensure_links_table();
		$meta = topics_get_controller_for_topic($topic_id);
		if (!$meta || empty($meta->project_id)) { return 0; }
		$project_id = (int)$meta->project_id;

		// Prefer linking by Topic Master to ensure 1 Task per Master across many Topics
		$topic_master_id = (int)($meta->topic_master_id ?? 0);
		$topic_master_topicid = (string)($meta->topic_master_topicid ?? '');

		// Try find existing link by master topicid first
		if (!empty($topic_master_topicid)) {
			$link = $CI->db->where('topic_master_topicid', $topic_master_topicid)->get(db_prefix().'topics_task_links')->row();
			if ($link) { return (int)$link->task_id; }
		}

		// Fallback by master id
		if (!empty($topic_master_id)) {
			$link = $CI->db->where('topic_master_id', $topic_master_id)->get(db_prefix().'topics_task_links')->row();
			if ($link) { return (int)$link->task_id; }
		}

		// Ensure milestones and map by action_type_code
		$map = topics_ensure_project_milestones_for_controller($controller_id);
		$milestone_id = 0;
		if ($action_type_code && isset($map[$action_type_code])) {
			$milestone_id = (int)$map[$action_type_code];
		}

		// Load topic for title and master display
		$topic = $CI->db->where('id', $topic_id)->get(db_prefix().'topics')->row();
		if (!$topic) { return 0; }
		$display_name = !empty($topic->topictitle) ? $topic->topictitle : ($topic_master_topicid ?: $topic->topicid);

		$CI->load->model('tasks_model');
		$taskData = [
			'name'        => '[Topic Master] ' . $display_name,
			'description' => 'Auto-synced from Topics module. Topic Master: ' . ($topic_master_topicid ?: $topic->topicid),
			'priority'    => 2,
			'project_id'  => $project_id,
			'rel_type'    => 'project',
			'rel_id'      => $project_id,
			'milestone'   => $milestone_id,
			'visible_to_client' => 0,
			'billable'    => 0,
			'kanban_order'=> 1,
			'custom_fields' => [],
			'startdate' => _d(date('Y-m-d')),
			'duedate'   => '',
		];
		$task_id = $CI->tasks_model->add($taskData, false);
		if ($task_id) {
			$CI->db->insert(db_prefix().'topics_task_links', [
				'topic_id' => $topic_id,
				'topic_master_id' => $topic_master_id ?: null,
				'topic_master_topicid' => $topic_master_topicid ?: $topic->topicid,
				'task_id' => $task_id,
				'controller_id' => $controller_id,
				'project_id' => $project_id,
				'datecreated' => date('Y-m-d H:i:s'),
			]);
		}
		return (int)$task_id;
	}
}

if (!function_exists('topics_map_state_to_task_status')) {
	function topics_map_state_to_task_status($action_type_code, $action_state_code) {
		// Simple heuristic mapping; can be extended per action_type_code
		$state = strtolower((string)$action_state_code);
		if (strpos($state, 'completed') !== false || $state === 'success') return Tasks_model::STATUS_COMPLETE; // 5
		if (strpos($state, 'processing') !== false || strpos($state, 'in_progress') !== false) return Tasks_model::STATUS_IN_PROGRESS; // 4
		if (strpos($state, 'pending') !== false || strpos($state, 'await') !== false) return Tasks_model::STATUS_AWAITING_FEEDBACK; // 2
		if (strpos($state, 'fail') !== false || strpos($state, 'error') !== false) return Tasks_model::STATUS_TESTING; // 3 (use Testing for fails)
		return Tasks_model::STATUS_NOT_STARTED; // 1
	}
}

if (!function_exists('topics_sync_topic_to_project')) {
	function topics_sync_topic_to_project($topic_id, $action_type_code, $action_state_code) {
		$CI = &get_instance();
		$meta = topics_get_controller_for_topic($topic_id);
		if (!$meta || empty($meta->controller_id) || empty($meta->project_id)) {
			return ['success' => false, 'message' => 'No controller/project linked'];
		}
		$controller_id = (int)$meta->controller_id;
		$task_id = topics_get_or_create_task_for_topic($topic_id, $controller_id, $action_type_code);
		if (!$task_id) { return ['success' => false, 'message' => 'Cannot create/link task']; }

		$status = topics_map_state_to_task_status($action_type_code, $action_state_code);
		$CI->db->where('id', $task_id)->update(db_prefix().'tasks', ['status' => $status]);

		// Optional: update task description summary
		$topic = $CI->db->where('id',$topic_id)->get(db_prefix().'topics')->row();
		if ($topic) {
			$summary = 'TopicID: ' . $topic->topicid . "\nType: $action_type_code\nState: $action_state_code\nUpdated: " . date('Y-m-d H:i:s');
			$CI->db->where('id', $task_id)->update(db_prefix().'tasks', ['description' => $summary]);
		}
		return ['success' => true, 'task_id' => $task_id];
	}
}

if (!function_exists('topics_sync_all_topics_for_controller')) {
	function topics_sync_all_topics_for_controller($controller_id) {
		$CI = &get_instance();
		$controller = $CI->db->where('id', $controller_id)->get(db_prefix().'topic_controllers')->row();
		if (!$controller || empty($controller->project_id)) { return 0; }
		// Get topics via master by controller
		$CI->db->select('t.id, t.action_type_code, t.action_state_code');
		$CI->db->from(db_prefix().'topics t');
		$CI->db->join(db_prefix().'topic_master tm', 'tm.topicid = t.topicid');
		$CI->db->where('tm.controller_id', $controller_id);
		$rows = $CI->db->get()->result();
		$count = 0;
		foreach ($rows as $row) {
			$res = topics_sync_topic_to_project((int)$row->id, $row->action_type_code, $row->action_state_code);
			if (!empty($res['success'])) { $count++; }
		}
		return $count;
	}
}

