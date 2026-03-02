<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Overview extends AdminController
{
	public function __construct()
	{
		parent::__construct();
		if (!has_permission('topics', '', 'view')) {
			access_denied('topics');
		}
	}

	public function index()
	{
		$data = [];
		$data['title'] = _l('topics_overview');

		// BEGIN MODIFICATION: provide table counts and human notes for ER diagram
		$tables = [
			'tbltopic_action_types'     => 'Defines high-level action categories; supports hierarchy via parent_id to group similar actions.',
			'tbltopic_action_states'    => 'Enumerates possible action states per type; used to drive workflows and UI state.',
			'tbltopic_master'           => 'Master records for unique topics (topicid); connects outward to logs, externals, controllers.',
			'tbltopics'                 => 'Concrete topic instances (per run/change) tied to a master topicid.',
			'tbltopic_target'           => 'Targets/goals a topic instance relates to (e.g. category, campaign, platform target).',
			'tbltopic_automation_logs'  => 'Automation execution logs for a topicid (workflow status, responses).',
			'tbltopic_external_data'    => 'External related data (images, posts, references) bound to a topic master.',
			'tbltopic_controllers'      => 'Controller configurations per site/platform (login, mapping, categories).',
			'tbltopic_controller'       => 'Link table between controllers and topic masters with staff attribution.',
			'tbltopic_sync_logs'        => 'Bulk sync session logs for controllers (processing summary/progress).',
			'tbltopic_editor_drafts'    => 'Editor drafts content generated/edited for a topic instance.',
		];
		$tableCounts = [];
		foreach ($tables as $t => $_) {
			// Count with db prefix detection
			$prefixed = $t; // already prefixed in schema dump
			if (!$this->db->table_exists($prefixed)) {
				// Try without tbl prefix or with CI prefix helper
				$alt = db_prefix().substr($t, 3); // convert tbltopic_* -> {db_prefix()}topic_*
				$prefixed = $this->db->table_exists($alt) ? $alt : $t;
			}
			$tableCounts[$t] = $this->db->table_exists($prefixed) ? (int)$this->db->count_all($prefixed) : 0;
		}
		$data['topics_table_counts'] = $tableCounts;
		$data['topics_table_notes']  = $tables;
		// END MODIFICATION

		$this->load->view('topics/overview', $data); 
	}

	public function inspect()
	{
		if (!has_permission('topics', '', 'view')) {
			access_denied('topics');
		}

		$topicMasterId = $this->input->get('topic_master_id');
		$topicId       = $this->input->get('topic_id');

		$result = [
			'success' => true,
			'filters' => [
				'topic_master_id' => $topicMasterId,
				'topic_id'        => $topicId,
			],
			'nodes'   => [],
			'links'   => [],
			'raw'     => [],
		];

		// Resolve topic_master by either id or topic_id
		if ($topicMasterId) {
			$tm = $this->db->get_where(db_prefix().'topic_master', ['id' => (int)$topicMasterId])->row_array();
		} elseif ($topicId) {
			$tm = $this->db->get_where(db_prefix().'topic_master', ['topicid' => $topicId])->row_array();
		} else {
			$tm = null;
		}

		if ($tm) {
			$result['raw']['topic_master'] = $tm;
			$topicid = $tm['topicid'];

			// Related topics (instances)
			$topics = $this->db->get_where(db_prefix().'topics', ['topicid' => $topicid])->result_array();
			$result['raw']['topics'] = $topics;

			// Action history logs
			$logs = $this->db->get_where(db_prefix().'topic_automation_logs', ['topic_id' => $topicid])->result_array();
			$result['raw']['automation_logs'] = $logs;

			// External data
			$ext = $this->db->get_where(db_prefix().'topic_external_data', ['topic_master_id' => $tm['id']])->result_array();
			$result['raw']['external_data'] = $ext;

			// Controller relationships
			$controllerRel = $this->db->get_where(db_prefix().'topic_controller', ['topic_id' => $tm['id']])->result_array();
			$result['raw']['controller_links'] = $controllerRel;

			$controller = null;
			if (!empty($tm['controller_id'])) {
				$controller = $this->db->get_where(db_prefix().'topic_controllers', ['id' => (int)$tm['controller_id']])->row_array();
				if ($controller) {
					$result['raw']['controller'] = $controller;
				}
			}

			// Build nodes
			$nodeId = 0;
			$ids = [];
			$addNode = function($key, $label, $data = [], $meta = []) use (&$result, &$nodeId, &$ids) {
				$nid = $key.'#'.(++$nodeId);
				$ids[$key] = $nid;
				$result['nodes'][] = [
					'id'    => $nid,
					'label' => $label,
					'data'  => $data,
					'meta'  => $meta,
				];
				return $nid;
			};

			$tmNode = $addNode('topic_master', 'Topic Master: '.$topicid, $tm, [
				'table' => db_prefix().'topic_master',
				'keys'  => ['id' => (int)$tm['id'], 'topicid' => $tm['topicid']],
			]);
			// Representative single path per relation type
			if (!empty($topics)) {
				$t = $topics[0];
				$topicNodeId = $addNode('topic_'.$t['id'], 'Topic #'.$t['id'], $t, [
					'table' => db_prefix().'topics',
					'keys'  => ['id' => (int)$t['id'], 'topicid' => $t['topicid']],
				]);
				$result['links'][] = [ 'source' => $tmNode, 'target' => $topicNodeId, 'type' => 'has' ];

				// Dive into topic -> target (if any)
				if (!empty($t['target_id']) && (int)$t['target_id'] > 0) {
					$target = $this->db->get_where(db_prefix().'topic_target', ['id' => (int)$t['target_id']])->row_array();
					if ($target) {
						$targetNodeId = $addNode('target_'.$target['id'], 'Target #'.$target['id'], $target, [
							'table' => db_prefix().'topic_target',
							'keys'  => ['id' => (int)$target['id']],
						]);
						$result['links'][] = [ 'source' => $topicNodeId, 'target' => $targetNodeId, 'type' => 'target' ];
					}
				}

				// Dive into topic -> latest draft (if any)
				$draft = $this->db
					->order_by('updated_at', 'DESC')
					->get_where(db_prefix().'topic_editor_drafts', ['topic_id' => (int)$t['id']])
					->row_array();
				if ($draft) {
					$draftNodeId = $addNode('draft_'.$draft['id'], 'Draft #'.$draft['id'], $draft, [
						'table' => db_prefix().'topic_editor_drafts',
						'keys'  => ['id' => (int)$draft['id']],
					]);
					$result['links'][] = [ 'source' => $topicNodeId, 'target' => $draftNodeId, 'type' => 'draft' ];
				}
			}

			if ($controller) {
				$ctrlNode = $addNode('controller', 'Controller #'.$controller['id'], $controller, [
					'table' => db_prefix().'topic_controllers',
					'keys'  => ['id' => (int)$controller['id']],
				]);
				$result['links'][] = [ 'source' => $tmNode, 'target' => $ctrlNode, 'type' => 'controls' ];

				// Dive into controller -> latest sync log (if any)
				$sync = $this->db
					->order_by('datecreated', 'DESC')
					->get_where(db_prefix().'topic_sync_logs', ['controller_id' => (int)$controller['id']])
					->row_array();
				if ($sync) {
					$syncNodeId = $addNode('sync_'.$sync['id'], 'SyncLog #'.$sync['id'], $sync, [
						'table' => db_prefix().'topic_sync_logs',
						'keys'  => ['id' => (int)$sync['id']],
					]);
					$result['links'][] = [ 'source' => $ctrlNode, 'target' => $syncNodeId, 'type' => 'sync' ];
				}
			}

			if (!empty($ext)) {
				$row = $ext[0];
				$extNode = $addNode('ext_'.$row['id'], 'External '.$row['rel_type'].'#'.$row['rel_id'], $row, [
					'table' => db_prefix().'topic_external_data',
					'keys'  => ['id' => (int)$row['id']],
				]);
				$result['links'][] = [ 'source' => $tmNode, 'target' => $extNode, 'type' => 'external' ];
			}

			if (!empty($logs)) {
				$lg = $logs[0];
				$logNode = $addNode('log_'.$lg['id'], 'Log #'.$lg['id'], $lg, [
					'table' => db_prefix().'topic_automation_logs',
					'keys'  => ['id' => (int)$lg['id']],
				]);
				$result['links'][] = [ 'source' => $tmNode, 'target' => $logNode, 'type' => 'log' ];
			}

			$result['stats'] = [
				'topic_nodes_total' => count($topics),
				'external_nodes_total' => count($ext),
				'log_nodes_total' => count($logs),
				'truncated' => true,
				'mode' => 'representative_paths',
			];

			// BEGIN MODIFICATION: Provide payloads for client-side expand button
			$result['more'] = [
				'topic_master' => [
					'topics'   => array_slice($topics, 1),
					'external' => array_slice($ext, 1),
					'logs'     => array_slice($logs, 1),
				],
			];
			// END MODIFICATION
		}

		header('Content-Type: application/json');
		echo json_encode($result);
	}

	// BEGIN MODIFICATION: generic expansion endpoint using schema introspection
	public function expand()
	{
		if (!has_permission('topics', '', 'view')) {
			ajax_access_denied();
		}

		$table = $this->input->get('table');
		$keysJson = $this->input->get('keys');
		$keys = [];
		if ($keysJson) {
			$decoded = json_decode(base64_decode($keysJson), true);
			if (is_array($decoded)) { $keys = $decoded; }
		}

		$result = ['success' => true, 'nodes' => []];

		// Helper closure to create node structure
		$makeNode = function($t, $row) {
			$label = ucfirst(str_replace(db_prefix(), '', $t)).' #'.(isset($row['id']) ? $row['id'] : '');
			return [
				'id'    => $t.'#'.(isset($row['id']) ? $row['id'] : uniqid()),
				'label' => $label,
				'data'  => $row,
				'meta'  => [
					'table' => $t,
					'keys'  => array_filter([
						'id' => isset($row['id']) ? (int)$row['id'] : null,
						'topicid' => isset($row['topicid']) ? $row['topicid'] : null,
					]),
				],
			];
		};

		// Limit scope to topic* tables to avoid heavy scans
		$tables = array_filter($this->db->list_tables(), function($t){ return strpos($t, db_prefix().'topic') === 0; });

		// Fetch the current row (optional) to discover outgoing references
		$currentRow = null;
		if ($table && !empty($keys)) {
			$selector = [];
			foreach ($keys as $k => $v) { if ($v !== '' && $v !== null) { $selector[$k] = $v; } }
			if (!empty($selector)) {
				$currentRow = $this->db->limit(1)->get_where($table, $selector)->row_array();
			}
		}

		$nodesAdded = [];
		$addIfNew = function($node) use (&$result, &$nodesAdded) {
			$key = $node['meta']['table'].'#'.($node['meta']['keys']['id'] ?? $node['id']);
			if (isset($nodesAdded[$key])) { return; }
			$nodesAdded[$key] = true;
			$result['nodes'][] = $node;
		};

		// Incoming references: find rows that reference our identifiers
		if (!empty($keys)) {
			foreach ($tables as $t) {
				$fields = $this->db->list_fields($t);
				foreach ($keys as $k => $v) {
					if ($v === '' || $v === null) continue;
					if (in_array($k, $fields, true)) {
						$rows = $this->db->limit(20)->get_where($t, [$k => $v])->result_array();
						foreach ($rows as $row) { $addIfNew($makeNode($t, $row)); }
					}
				}
			}
		}

		// Outgoing references: from current row columns *_id or topicid
		if (is_array($currentRow)) {
			foreach ($currentRow as $col => $val) {
				if ($val === '' || $val === null) continue;
				$searchById = (substr($col, -3) === '_id');
				$searchByTopicId = ($col === 'topicid');
				if (!$searchById && !$searchByTopicId) continue;
				foreach ($tables as $t) {
					$fields = $this->db->list_fields($t);
					$cond = [];
					if ($searchById && in_array('id', $fields, true)) { $cond['id'] = $val; }
					if ($searchByTopicId && in_array('topicid', $fields, true)) { $cond['topicid'] = $val; }
					if (!empty($cond)) {
						$rows = $this->db->limit(20)->get_where($t, $cond)->result_array();
						foreach ($rows as $row) { $addIfNew($makeNode($t, $row)); }
					}
				}
			}
		}

		header('Content-Type: application/json');
		echo json_encode($result);
	}
	// END MODIFICATION
}

