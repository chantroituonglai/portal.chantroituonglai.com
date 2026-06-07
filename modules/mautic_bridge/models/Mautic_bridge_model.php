<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Mautic_bridge_model extends App_Model
{
    private $mapTable = 'mautic_bridge_maps';
    private $queueTable = 'mautic_bridge_queue';
    private $logTable = 'mautic_bridge_logs';
    private $campaignTable = 'mautic_bridge_campaigns';
    private $importJobTable = 'mautic_bridge_import_jobs';
    private $importRowTable = 'mautic_bridge_import_rows';
    private $projectMapTable = 'mautic_bridge_project_maps';
    private $projectObjectTable = 'mautic_bridge_project_objects';
    private $projectLogTable = 'mautic_bridge_project_sync_logs';
    private $projectTemplateTable = 'mautic_bridge_project_templates';

    public function __construct()
    {
        parent::__construct();
        $this->load->helper('tags');
        $this->load->helper('text');
        $this->ensure_import_tables();
        $this->ensure_project_hq_tables();
    }

    public function get_settings(): array
    {
        return [
            'enabled' => (int) get_option('mautic_bridge_enabled'),
            'dry_run' => (int) get_option('mautic_bridge_dry_run'),
            'base_url' => (string) get_option('mautic_bridge_base_url'),
            'oauth_client_id' => (string) get_option('mautic_bridge_oauth_client_id'),
            'oauth_client_secret' => (string) get_option('mautic_bridge_oauth_client_secret'),
            'webhook_secret' => (string) get_option('mautic_bridge_webhook_secret'),
            'default_lead_source' => (string) get_option('mautic_bridge_default_lead_source'),
            'default_lead_status' => (string) get_option('mautic_bridge_default_lead_status'),
            'default_assigned_staff' => (string) get_option('mautic_bridge_default_assigned_staff'),
            'timeout' => (int) get_option('mautic_bridge_timeout') ?: 30,
            'retry_max' => (int) get_option('mautic_bridge_retry_max') ?: 3,
            'delete_remote' => (int) get_option('mautic_bridge_delete_remote'),
            'pull_since' => (string) get_option('mautic_bridge_pull_since'),
        ];
    }

    public function save_settings(array $data): void
    {
        update_option('mautic_bridge_enabled', !empty($data['enabled']) ? 1 : 0);
        update_option('mautic_bridge_dry_run', !empty($data['dry_run']) ? 1 : 0);
        update_option('mautic_bridge_base_url', rtrim(trim((string) ($data['base_url'] ?? '')), '/'));
        update_option('mautic_bridge_oauth_client_id', trim((string) ($data['oauth_client_id'] ?? '')));
        if (array_key_exists('oauth_client_secret', $data) && trim((string) $data['oauth_client_secret']) !== '') {
            update_option('mautic_bridge_oauth_client_secret', trim((string) $data['oauth_client_secret']));
            update_option('mautic_bridge_oauth_access_token', '');
            update_option('mautic_bridge_oauth_expires_at', 0);
        }
        update_option('mautic_bridge_webhook_secret', trim((string) ($data['webhook_secret'] ?? '')));
        update_option('mautic_bridge_default_lead_source', trim((string) ($data['default_lead_source'] ?? '')));
        update_option('mautic_bridge_default_lead_status', trim((string) ($data['default_lead_status'] ?? '')));
        update_option('mautic_bridge_default_assigned_staff', trim((string) ($data['default_assigned_staff'] ?? '')));
        update_option('mautic_bridge_timeout', max(5, (int) ($data['timeout'] ?? 30)));
        update_option('mautic_bridge_retry_max', max(0, (int) ($data['retry_max'] ?? 3)));
        update_option('mautic_bridge_delete_remote', !empty($data['delete_remote']) ? 1 : 0);
    }

    public function verify_webhook_secret(?string $provided): bool
    {
        $secret = (string) get_option('mautic_bridge_webhook_secret');
        if ($secret === '') {
            return false;
        }

        return is_string($provided) && hash_equals($secret, $provided);
    }

    public function enqueue_inbound_contact(array $payload): int
    {
        $normalized = mautic_bridge_normalize_contact_payload($payload);
        if (empty($normalized['email'])) {
            $this->log('info', 'inbound_skipped_no_email', [
                'mautic_contact_id' => $normalized['mautic_contact_id'],
            ]);

            return 0;
        }

        return $this->enqueue([
            'direction' => 'inbound',
            'event_type' => 'contact_upsert',
            'mautic_contact_id' => $normalized['mautic_contact_id'],
            'email' => $normalized['email'],
            'payload_json' => json_encode($payload, JSON_UNESCAPED_UNICODE),
        ]);
    }

    public function handle_mautic_webhook_events(array $payload): array
    {
        $events = $this->extract_mautic_webhook_events($payload);
        $stats = [
            'received' => count($events),
            'queued_contact_sync' => 0,
            'contact_synced' => 0,
            'activity_logged' => 0,
            'points_updated' => 0,
            'tags_updated' => 0,
            'ignored' => 0,
            'unmatched' => 0,
        ];

        if (empty($events)) {
            $queueId = $this->enqueue_inbound_contact($payload);
            $stats['queued_contact_sync'] += $queueId > 0 ? 1 : 0;
            return $stats;
        }

        $this->ensure_mautic_event_custom_fields();

        foreach ($events as $event) {
            $eventKey = (string) $event['key'];
            $eventType = $this->mautic_event_type($eventKey);
            if ($eventType === 'delete') {
                $stats['ignored']++;
                continue;
            }

            $eventPayload = is_array($event['payload']) ? $event['payload'] : [];
            if (in_array($eventType, ['contact_upsert', 'contact_identified', 'company_updated'], true)) {
                $syncResult = (int) get_option('mautic_bridge_dry_run') === 1
                    ? ['ok' => false, 'retryable' => true, 'message' => 'dry run uses queue preview']
                    : $this->sync_mautic_contact_to_perfex($eventPayload);

                if (!empty($syncResult['ok'])) {
                    if (strpos((string) ($syncResult['message'] ?? ''), 'skipped:') === 0) {
                        $stats['ignored']++;
                        continue;
                    }
                    $stats['contact_synced']++;
                } else {
                    $queueId = $this->enqueue_inbound_contact($eventPayload);
                    $stats['queued_contact_sync'] += $queueId > 0 ? 1 : 0;
                    $this->log('warning', 'webhook_contact_sync_deferred', [
                        'event_key' => $eventKey,
                        'message' => $syncResult['message'] ?? 'unknown error',
                        'queue_id' => $queueId,
                    ]);
                }
            }

            $target = $this->resolve_perfex_target_from_mautic_event($eventPayload);
            if (!$target) {
                $stats['unmatched']++;
                $this->log('info', 'webhook_event_unmatched_perfex_record', [
                    'event_key' => $eventKey,
                    'event_type' => $eventType,
                    'contact' => mautic_bridge_normalize_contact_payload($eventPayload),
                ]);
                continue;
            }

            $message = $this->mautic_event_activity_message($eventType, $eventPayload);
            if ($target['rel_type'] === 'lead') {
                $this->log_mautic_lead_activity((int) $target['rel_id'], $message, [
                    'event_key' => $eventKey,
                    'event_type' => $eventType,
                    'mautic_contact_id' => $target['mautic_contact_id'] ?? null,
                    'summary' => $this->mautic_event_summary($eventPayload),
                ]);
                $this->update_lead_last_contact((int) $target['rel_id'], $this->event_datetime($eventPayload));
                $stats['activity_logged']++;
            } else {
                $this->log('info', 'mautic_event_for_customer_contact', [
                    'event_key' => $eventKey,
                    'event_type' => $eventType,
                    'contact_id' => (int) $target['rel_id'],
                    'message' => $message,
                ]);
            }

            $points = $this->extract_mautic_points($eventPayload);
            if ($target['rel_type'] === 'lead' && $points !== null) {
                $this->set_lead_custom_field_value((int) $target['rel_id'], 'leads_mautic_points', (string) $points);
                $this->save_tags_with_prefix_replace((int) $target['rel_id'], 'lead', [$this->score_tag((int) $points)], ['mautic:score:']);
                $stats['points_updated']++;
                $stats['tags_updated']++;
            }

            $tagResult = $this->apply_mautic_event_tags($target, $eventType, $eventPayload);
            if ($tagResult['changed']) {
                $stats['tags_updated']++;
            }
        }

        return $stats;
    }

    public function enqueue_perfex_change(string $relType, int $relId, string $eventType = 'upsert'): int
    {
        if ($relId <= 0 || !in_array($relType, ['lead', 'contact'], true)) {
            return 0;
        }

        return $this->enqueue([
            'direction' => 'outbound',
            'event_type' => $eventType === 'delete' ? 'contact_delete' : 'contact_upsert',
            'rel_type' => $relType,
            'rel_id' => $relId,
        ]);
    }

    public function enqueue(array $data): int
    {
        $now = date('Y-m-d H:i:s');
        $row = [
            'direction' => $data['direction'],
            'event_type' => $data['event_type'],
            'rel_type' => $data['rel_type'] ?? null,
            'rel_id' => $data['rel_id'] ?? null,
            'mautic_contact_id' => $data['mautic_contact_id'] ?? null,
            'email' => $data['email'] ?? null,
            'payload_json' => $data['payload_json'] ?? null,
            'status' => 'pending',
            'attempts' => 0,
            'next_attempt_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        $this->db->insert(db_prefix() . $this->queueTable, $row);
        $id = (int) $this->db->insert_id();
        $this->log('info', 'queued_' . $row['direction'], ['queue_id' => $id, 'event_type' => $row['event_type']]);

        return $id;
    }

    public function process_queue(int $limit = 25): array
    {
        $limit = max(1, min(100, $limit));
        $now = date('Y-m-d H:i:s');
        $rows = $this->db
            ->where_in('status', ['pending', 'retry'])
            ->group_start()
            ->where('next_attempt_at IS NULL', null, false)
            ->or_where('next_attempt_at <=', $now)
            ->group_end()
            ->order_by('id', 'ASC')
            ->limit($limit)
            ->get(db_prefix() . $this->queueTable)
            ->result_array();

        $stats = ['processed' => 0, 'success' => 0, 'failed' => 0];
        foreach ($rows as $row) {
            $stats['processed']++;
            $result = $this->process_queue_row($row);
            if ($result['ok']) {
                $stats['success']++;
                $this->mark_queue_done((int) $row['id']);
            } else {
                $stats['failed']++;
                $this->mark_queue_failed($row, $result['message'], !empty($result['retryable']));
            }
        }

        return $stats;
    }

    public function dry_run_preview(): array
    {
        $now = date('Y-m-d H:i:s');
        $queueTable = db_prefix() . $this->queueTable;
        $mapTable = db_prefix() . $this->mapTable;

        $queue = [
            'pending_retry_total' => (int) $this->db
                ->where_in('status', ['pending', 'retry'])
                ->count_all_results($queueTable),
            'due_now' => (int) $this->db
                ->where_in('status', ['pending', 'retry'])
                ->group_start()
                ->where('next_attempt_at IS NULL', null, false)
                ->or_where('next_attempt_at <=', $now)
                ->group_end()
                ->count_all_results($queueTable),
            'inbound_pending_retry' => (int) $this->db
                ->where('direction', 'inbound')
                ->where_in('status', ['pending', 'retry'])
                ->count_all_results($queueTable),
            'outbound_pending_retry' => (int) $this->db
                ->where('direction', 'outbound')
                ->where_in('status', ['pending', 'retry'])
                ->count_all_results($queueTable),
            'failed' => (int) $this->db->where('status', 'failed')->count_all_results($queueTable),
            'done' => (int) $this->db->where('status', 'done')->count_all_results($queueTable),
        ];

        $mappings = [
            'total' => (int) $this->db->count_all_results($mapTable),
            'lead' => (int) $this->db->where('perfex_rel_type', 'lead')->count_all_results($mapTable),
            'contact' => (int) $this->db->where('perfex_rel_type', 'contact')->count_all_results($mapTable),
        ];

        $leadTable = db_prefix() . 'leads';
        $contactTable = db_prefix() . 'contacts';
        $perfex = [
            'leads_with_email' => (int) $this->db
                ->where('email IS NOT NULL', null, false)
                ->where('email !=', '')
                ->count_all_results($leadTable),
            'contacts_with_email' => (int) $this->db
                ->where('email IS NOT NULL', null, false)
                ->where('email !=', '')
                ->count_all_results($contactTable),
        ];
        $perfex['unmapped_leads_with_email'] = max(0, $perfex['leads_with_email'] - $mappings['lead']);
        $perfex['unmapped_contacts_with_email'] = max(0, $perfex['contacts_with_email'] - $mappings['contact']);

        $mautic = [
            'ok' => false,
            'total_contacts' => null,
            'message' => null,
            'http_code' => null,
        ];
        $response = $this->call_mautic_api('contacts', 'GET', ['limit' => 1]);
        $mautic['ok'] = (bool) $response['ok'];
        $mautic['message'] = $response['message'];
        $mautic['http_code'] = $response['http_code'] ?? null;
        if (!empty($response['body']['total'])) {
            $mautic['total_contacts'] = (int) $response['body']['total'];
        }

        return [
            'enabled' => (int) get_option('mautic_bridge_enabled') === 1,
            'dry_run' => (int) get_option('mautic_bridge_dry_run') === 1,
            'queue' => $queue,
            'mappings' => $mappings,
            'perfex' => $perfex,
            'mautic' => $mautic,
            'notes' => [
                'queue_due_now_is_what_process_queue_will_try_immediately',
                'mautic_total_contacts_requires_reconcile_or_webhooks_to_enter_queue',
                'perfex_unmapped_counts_are_potential_outbound_backfill_scope_not_current_queue',
            ],
        ];
    }

    public function get_manage_summary(): array
    {
        $preview = $this->dry_run_preview();
        $queueTable = db_prefix() . $this->queueTable;
        $mapTable = db_prefix() . $this->mapTable;
        $campaignTable = db_prefix() . $this->campaignTable;
        $logTable = db_prefix() . $this->logTable;

        $queueByStatus = [];
        $queueRows = $this->db
            ->select('status, COUNT(*) as total')
            ->group_by('status')
            ->get($queueTable)
            ->result_array();
        foreach ($queueRows as $row) {
            $queueByStatus[(string) $row['status']] = (int) $row['total'];
        }

        $latestLog = $this->db
            ->order_by('id', 'DESC')
            ->get($logTable)
            ->row_array();

        return [
            'settings' => $this->get_settings(),
            'preview' => $preview,
            'queue_by_status' => $queueByStatus,
            'campaign_mappings' => (int) $this->db->count_all_results($campaignTable),
            'latest_log' => $latestLog ?: null,
            'last_mapping_sync' => $this->db
                ->select_max('last_synced_at')
                ->get($mapTable)
                ->row_array()['last_synced_at'] ?? null,
        ];
    }

    public function get_queue_datatable(): array
    {
        $table = db_prefix() . $this->queueTable;
        $columns = [
            'id',
            'id',
            'direction',
            'event_type',
            'status',
            'email',
            'rel_type',
            'rel_id',
            'mautic_contact_id',
            'attempts',
            'next_attempt_at',
            'last_error',
            'created_at',
            'updated_at',
        ];

        $where = [];
        $status = $this->input->post('status', true);
        $direction = $this->input->post('direction', true);
        if ($status !== null && $status !== '') {
            $where[] = 'AND status = ' . $this->db->escape($status);
        }
        if ($direction !== null && $direction !== '') {
            $where[] = 'AND direction = ' . $this->db->escape($direction);
        }

        $result = data_tables_init($columns, 'id', $table, [], $where);
        $output = $result['output'];
        $output['data'] = [];

        foreach ($result['rResult'] as $row) {
            $id = (int) $row['id'];
            $options = '<div class="btn-group">'
                . '<button type="button" class="btn btn-default btn-icon mautic-bridge-row-action" data-action="retry_queue" data-id="' . $id . '" title="Retry"><i class="fa fa-refresh"></i></button>'
                . '<button type="button" class="btn btn-danger btn-icon mautic-bridge-row-action mautic-bridge-dry-guard" data-action="delete_queue" data-id="' . $id . '" title="Delete"><i class="fa fa-trash"></i></button>'
                . '</div>';

            $output['data'][] = [
                '<div class="checkbox"><input type="checkbox" class="mautic-bridge-row-check" value="' . $id . '"><label></label></div>',
                $id,
                html_escape($row['direction']),
                html_escape($row['event_type']),
                $this->status_label((string) $row['status']),
                html_escape($row['email']),
                html_escape($row['rel_type']),
                html_escape($row['rel_id']),
                html_escape($row['mautic_contact_id']),
                (int) $row['attempts'],
                html_escape($row['next_attempt_at']),
                '<span title="' . html_escape($row['last_error']) . '">' . html_escape(character_limiter((string) $row['last_error'], 80)) . '</span>',
                html_escape($row['created_at']),
                html_escape($row['updated_at']),
                $options,
            ];
        }

        $output['aaData'] = $output['data'];

        return $output;
    }

    public function get_mappings_datatable(): array
    {
        $table = db_prefix() . $this->mapTable;
        $columns = [
            'id',
            'mautic_contact_id',
            'perfex_rel_type',
            'perfex_rel_id',
            'email',
            'mautic_modified_at',
            'perfex_modified_at',
            'last_synced_at',
            'created_at',
            'updated_at',
        ];

        $where = [];
        $relType = $this->input->post('rel_type', true);
        if ($relType !== null && $relType !== '') {
            $where[] = 'AND perfex_rel_type = ' . $this->db->escape($relType);
        }

        $result = data_tables_init($columns, 'id', $table, [], $where);
        $output = $result['output'];
        $output['data'] = [];

        foreach ($result['rResult'] as $row) {
            $id = (int) $row['id'];
            $options = '<div class="btn-group">'
                . '<button type="button" class="btn btn-default btn-icon mautic-bridge-row-action mautic-bridge-dry-guard" data-action="enqueue_resync" data-id="' . $id . '" title="Enqueue resync"><i class="fa fa-retweet"></i></button>'
                . '<button type="button" class="btn btn-danger btn-icon mautic-bridge-row-action mautic-bridge-dry-guard" data-action="reset_mapping" data-id="' . $id . '" title="Reset mapping"><i class="fa fa-chain-broken"></i></button>'
                . '</div>';

            $output['data'][] = [
                $id,
                html_escape($row['mautic_contact_id']),
                html_escape($row['perfex_rel_type']),
                html_escape($row['perfex_rel_id']),
                html_escape($row['email']),
                html_escape($row['mautic_modified_at']),
                html_escape($row['perfex_modified_at']),
                html_escape($row['last_synced_at']),
                html_escape($row['created_at']),
                html_escape($row['updated_at']),
                $options,
            ];
        }

        $output['aaData'] = $output['data'];

        return $output;
    }

    public function get_campaigns_datatable(): array
    {
        $table = db_prefix() . $this->campaignTable;
        $columns = ['mautic_campaign_id', 'campaign_alias', 'campaign_name', 'perfex_tag', 'synced_at'];
        $result = data_tables_init($columns, 'id', $table, [], [], ['id']);
        $output = $result['output'];
        $output['data'] = [];

        foreach ($result['rResult'] as $row) {
            $output['data'][] = [
                html_escape($row['mautic_campaign_id']),
                html_escape($row['campaign_alias']),
                html_escape($row['campaign_name']),
                html_escape($row['perfex_tag']),
                html_escape($row['synced_at']),
            ];
        }

        $output['aaData'] = $output['data'];

        return $output;
    }

    public function get_logs_datatable(): array
    {
        $table = db_prefix() . $this->logTable;
        $columns = ['id', 'level', 'message', 'context_json', 'created_at'];
        $where = [];
        $level = $this->input->post('level', true);
        if ($level !== null && $level !== '') {
            $where[] = 'AND level = ' . $this->db->escape($level);
        }

        $result = data_tables_init($columns, 'id', $table, [], $where);
        $output = $result['output'];
        $output['data'] = [];

        foreach ($result['rResult'] as $row) {
            $context = (string) $row['context_json'];
            $output['data'][] = [
                (int) $row['id'],
                $this->log_level_label((string) $row['level']),
                html_escape($row['message']),
                '<code title="' . html_escape($context) . '">' . html_escape(character_limiter($context, 160)) . '</code>',
                html_escape($row['created_at']),
            ];
        }

        $output['aaData'] = $output['data'];

        return $output;
    }

    public function retry_queue(array $ids): array
    {
        $ids = $this->sanitize_ids($ids);
        if (empty($ids)) {
            return ['success' => false, 'message' => 'No queue item selected.'];
        }

        $this->db->where_in('id', $ids)->update(db_prefix() . $this->queueTable, [
            'status' => 'pending',
            'next_attempt_at' => date('Y-m-d H:i:s'),
            'last_error' => null,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $affected = $this->db->affected_rows();
        $this->log('info', 'queue_retry_requested', ['ids' => $ids, 'affected' => $affected]);

        return ['success' => true, 'message' => 'Queue item(s) moved to pending.', 'affected' => $affected];
    }

    public function delete_queue(array $ids): array
    {
        $guard = $this->dry_run_guard();
        if (!$guard['success']) {
            return $guard;
        }

        $ids = $this->sanitize_ids($ids);
        if (empty($ids)) {
            return ['success' => false, 'message' => 'No queue item selected.'];
        }

        $this->db->where_in('id', $ids)->delete(db_prefix() . $this->queueTable);
        $affected = $this->db->affected_rows();
        $this->log('warning', 'queue_deleted', ['ids' => $ids, 'affected' => $affected]);

        return ['success' => true, 'message' => 'Queue item(s) deleted.', 'affected' => $affected];
    }

    public function reset_mapping(array $ids): array
    {
        $guard = $this->dry_run_guard();
        if (!$guard['success']) {
            return $guard;
        }

        $ids = $this->sanitize_ids($ids);
        if (empty($ids)) {
            return ['success' => false, 'message' => 'No mapping selected.'];
        }

        $this->db->where_in('id', $ids)->delete(db_prefix() . $this->mapTable);
        $affected = $this->db->affected_rows();
        $this->log('warning', 'mapping_reset', ['ids' => $ids, 'affected' => $affected]);

        return ['success' => true, 'message' => 'Mapping(s) reset.', 'affected' => $affected];
    }

    public function enqueue_resync(array $ids): array
    {
        $guard = $this->dry_run_guard();
        if (!$guard['success']) {
            return $guard;
        }

        $ids = $this->sanitize_ids($ids);
        if (empty($ids)) {
            return ['success' => false, 'message' => 'No mapping selected.'];
        }

        $rows = $this->db
            ->where_in('id', $ids)
            ->get(db_prefix() . $this->mapTable)
            ->result_array();
        $queued = 0;
        foreach ($rows as $row) {
            $queued += $this->enqueue_perfex_change((string) $row['perfex_rel_type'], (int) $row['perfex_rel_id'], 'upsert') > 0 ? 1 : 0;
        }

        return ['success' => true, 'message' => 'Resync job(s) queued.', 'queued' => $queued];
    }

    public function process_queue_ids(array $ids): array
    {
        $ids = $this->sanitize_ids($ids);
        if (empty($ids)) {
            return ['processed' => 0, 'success' => 0, 'failed' => 0, 'message' => 'No queue item selected.'];
        }

        $rows = $this->db
            ->where_in('id', $ids)
            ->where_in('status', ['pending', 'retry', 'failed'])
            ->order_by('id', 'ASC')
            ->get(db_prefix() . $this->queueTable)
            ->result_array();

        $stats = ['processed' => 0, 'success' => 0, 'failed' => 0];
        foreach ($rows as $row) {
            $stats['processed']++;
            $result = $this->process_queue_row($row);
            if ($result['ok']) {
                $stats['success']++;
                $this->mark_queue_done((int) $row['id']);
            } else {
                $stats['failed']++;
                $this->mark_queue_failed($row, $result['message'], !empty($result['retryable']));
            }
        }

        return $stats;
    }

    public function enqueue_backfill(string $direction, int $limit = 50, int $page = 1): array
    {
        $guard = $this->dry_run_guard();
        if (!$guard['success']) {
            return $guard;
        }

        $limit = max(1, min(200, $limit));
        $page = max(1, $page);

        if ($direction === 'inbound') {
            return $this->enqueue_inbound_backfill($limit, $page);
        }

        if ($direction === 'outbound_leads') {
            return $this->enqueue_outbound_backfill('lead', $limit);
        }

        if ($direction === 'outbound_contacts') {
            return $this->enqueue_outbound_backfill('contact', $limit);
        }

        return ['success' => false, 'message' => 'Invalid backfill direction.'];
    }

    private function enqueue_inbound_backfill(int $limit, int $page): array
    {
        $response = $this->call_mautic_api('contacts', 'GET', [
            'limit' => $limit,
            'start' => ($page - 1) * $limit,
            'orderBy' => 'id',
            'orderByDir' => 'ASC',
        ]);

        if (!$response['ok']) {
            return ['success' => false, 'message' => $response['message'], 'queued' => 0];
        }

        $contacts = $response['body']['contacts'] ?? [];
        $queued = 0;
        foreach ($contacts as $contact) {
            if (is_array($contact)) {
                $queued += $this->enqueue_inbound_contact(['contact' => $contact]) > 0 ? 1 : 0;
            }
        }

        return [
            'success' => true,
            'message' => 'Inbound Mautic contact backfill queued.',
            'queued' => $queued,
            'limit' => $limit,
            'page' => $page,
            'total' => isset($response['body']['total']) ? (int) $response['body']['total'] : null,
        ];
    }

    private function enqueue_outbound_backfill(string $relType, int $limit): array
    {
        $mapTable = db_prefix() . $this->mapTable;
        if ($relType === 'lead') {
            $rows = $this->db
                ->select('l.id')
                ->from(db_prefix() . 'leads as l')
                ->join($mapTable . ' as m', 'm.perfex_rel_type = "lead" AND m.perfex_rel_id = l.id', 'left')
                ->where('l.email IS NOT NULL', null, false)
                ->where('l.email !=', '')
                ->where('m.id IS NULL', null, false)
                ->order_by('l.id', 'ASC')
                ->limit($limit)
                ->get()
                ->result_array();
        } else {
            $rows = $this->db
                ->select('c.id')
                ->from(db_prefix() . 'contacts as c')
                ->join($mapTable . ' as m', 'm.perfex_rel_type = "contact" AND m.perfex_rel_id = c.id', 'left')
                ->where('c.email IS NOT NULL', null, false)
                ->where('c.email !=', '')
                ->where('m.id IS NULL', null, false)
                ->order_by('c.id', 'ASC')
                ->limit($limit)
                ->get()
                ->result_array();
        }

        $queued = 0;
        foreach ($rows as $row) {
            $queued += $this->enqueue_perfex_change($relType, (int) $row['id'], 'upsert') > 0 ? 1 : 0;
        }

        return [
            'success' => true,
            'message' => ucfirst($relType) . ' outbound backfill queued.',
            'queued' => $queued,
            'limit' => $limit,
        ];
    }

    private function dry_run_guard(): array
    {
        if ((int) get_option('mautic_bridge_dry_run') !== 1) {
            return [
                'success' => false,
                'message' => 'This action is locked unless Dry run only is enabled.',
            ];
        }

        return ['success' => true];
    }

    private function sanitize_ids(array $ids): array
    {
        $clean = [];
        foreach ($ids as $id) {
            $id = (int) $id;
            if ($id > 0) {
                $clean[] = $id;
            }
        }

        return array_values(array_unique($clean));
    }

    private function status_label(string $status): string
    {
        $class = 'default';
        if ($status === 'done') {
            $class = 'success';
        } elseif ($status === 'failed') {
            $class = 'danger';
        } elseif ($status === 'retry') {
            $class = 'warning';
        } elseif ($status === 'pending') {
            $class = 'info';
        }

        return '<span class="label label-' . $class . '">' . html_escape($status) . '</span>';
    }

    private function log_level_label(string $level): string
    {
        $class = $level === 'error' ? 'danger' : ($level === 'warning' ? 'warning' : 'default');
        if ($level === 'info') {
            $class = 'info';
        }

        return '<span class="label label-' . $class . '">' . html_escape($level) . '</span>';
    }

    private function process_queue_row(array $row): array
    {
        if ((int) get_option('mautic_bridge_enabled') !== 1) {
            return ['ok' => false, 'retryable' => false, 'message' => 'bridge disabled'];
        }

        if ($row['direction'] === 'inbound') {
            $payload = json_decode((string) $row['payload_json'], true);
            if (!is_array($payload)) {
                return ['ok' => false, 'retryable' => false, 'message' => 'invalid inbound payload'];
            }

            return $this->sync_mautic_contact_to_perfex($payload);
        }

        if ($row['direction'] === 'outbound') {
            return $this->sync_perfex_record_to_mautic((string) $row['rel_type'], (int) $row['rel_id'], (string) $row['event_type']);
        }

        return ['ok' => false, 'retryable' => false, 'message' => 'unknown queue direction'];
    }

    public function sync_mautic_contact_to_perfex(array $payload): array
    {
        $contact = mautic_bridge_normalize_contact_payload($payload);
        if (empty($contact['email'])) {
            $this->log('info', 'inbound_skipped_no_email', [
                'mautic_contact_id' => $contact['mautic_contact_id'],
            ]);

            return ['ok' => true, 'retryable' => false, 'message' => 'skipped: email is required'];
        }
        if (!$this->is_deliverable_email($contact['email'])) {
            $this->log('warning', 'inbound_skipped_invalid_email', [
                'mautic_contact_id' => $contact['mautic_contact_id'],
                'email' => $contact['email'],
            ]);

            return ['ok' => true, 'retryable' => false, 'message' => 'skipped: invalid email'];
        }

        $map = $this->find_map($contact['mautic_contact_id'], $contact['email']);
        if (!$map) {
            $map = $this->find_existing_perfex_map_by_email($contact);
        }
        if ($map && mautic_bridge_newer_side($contact['mautic_modified_at'], $map['perfex_modified_at']) === 'local') {
            $this->log('info', 'inbound_skipped_local_newer', ['email' => $contact['email'], 'map_id' => $map['id']]);
            return ['ok' => true, 'message' => 'local newer'];
        }

        if ((int) get_option('mautic_bridge_dry_run') === 1) {
            $this->log('info', 'dry_run_inbound', ['contact' => $contact]);
            return ['ok' => true, 'message' => 'dry run'];
        }

        if (!$this->has_required_perfex_defaults()) {
            return ['ok' => false, 'retryable' => false, 'message' => 'missing default lead source/status/assigned staff'];
        }

        return mautic_bridge_with_origin(MAUTIC_BRIDGE_MODULE_NAME, function () use ($contact, $map) {
            if ($map && $map['perfex_rel_type'] === 'contact') {
                return $this->update_perfex_contact_from_mautic($contact, $map);
            }

            if ($map && $map['perfex_rel_type'] === 'lead') {
                $converted = $this->converted_contact_for_lead((int) $map['perfex_rel_id']);
                if ($converted) {
                    $map['perfex_rel_type'] = 'contact';
                    $map['perfex_rel_id'] = (int) $converted['id'];
                    $this->upsert_map($map);
                    return $this->update_perfex_contact_from_mautic($contact, $map);
                }

                return $this->update_perfex_lead_from_mautic($contact, $map);
            }

            return $this->create_perfex_lead_from_mautic($contact);
        });
    }

    private function create_perfex_lead_from_mautic(array $contact): array
    {
        $this->load->model('leads_model');
        $leadData = $this->lead_data_from_contact($contact);
        $leadId = (int) $this->leads_model->add($leadData);
        if ($leadId <= 0) {
            return ['ok' => false, 'retryable' => true, 'message' => 'failed to create Perfex lead'];
        }

        $this->save_campaign_tags($leadId, 'lead', $contact['campaigns']);
        $this->upsert_map([
            'mautic_contact_id' => $contact['mautic_contact_id'],
            'perfex_rel_type' => 'lead',
            'perfex_rel_id' => $leadId,
            'email' => $contact['email'],
            'mautic_modified_at' => $contact['mautic_modified_at'],
            'perfex_modified_at' => date('Y-m-d H:i:s'),
            'last_synced_at' => date('Y-m-d H:i:s'),
        ]);

        return ['ok' => true, 'message' => 'lead created', 'rel_type' => 'lead', 'rel_id' => $leadId];
    }

    private function update_perfex_lead_from_mautic(array $contact, array $map): array
    {
        $this->load->model('leads_model');
        $lead = $this->leads_model->get((int) $map['perfex_rel_id']);
        if (!$lead) {
            return $this->create_perfex_lead_from_mautic($contact);
        }

        $data = $this->lead_data_from_contact($contact, $lead);
        $updated = $this->leads_model->update($data, (int) $map['perfex_rel_id']);
        $this->save_campaign_tags((int) $map['perfex_rel_id'], 'lead', $contact['campaigns']);
        $map['email'] = $contact['email'];
        $map['mautic_modified_at'] = $contact['mautic_modified_at'];
        $map['perfex_modified_at'] = date('Y-m-d H:i:s');
        $map['last_synced_at'] = date('Y-m-d H:i:s');
        $this->upsert_map($map);

        return ['ok' => true, 'message' => $updated ? 'lead updated' : 'lead unchanged'];
    }

    private function update_perfex_contact_from_mautic(array $contact, array $map): array
    {
        $this->load->model('clients_model');
        $existing = $this->clients_model->get_contact((int) $map['perfex_rel_id']);
        if (!$existing) {
            return $this->create_perfex_lead_from_mautic($contact);
        }

        $data = [
            'firstname' => $contact['firstname'] ?: $contact['name'],
            'lastname' => $contact['lastname'],
            'email' => $contact['email'],
            'phonenumber' => $contact['phonenumber'],
            'title' => '',
            'is_primary' => (int) ($existing->is_primary ?? 0) === 1 ? 1 : 0,
            'permissions' => [],
        ];
        $updated = $this->clients_model->update_contact($data, (int) $map['perfex_rel_id']);
        $this->save_campaign_tags((int) $map['perfex_rel_id'], 'contact', $contact['campaigns']);
        $map['email'] = $contact['email'];
        $map['mautic_modified_at'] = $contact['mautic_modified_at'];
        $map['perfex_modified_at'] = date('Y-m-d H:i:s');
        $map['last_synced_at'] = date('Y-m-d H:i:s');
        $this->upsert_map($map);

        return ['ok' => true, 'message' => $updated ? 'contact updated' : 'contact unchanged'];
    }

    private function lead_data_from_contact(array $contact, $existing = null): array
    {
        return [
            'source' => (string) get_option('mautic_bridge_default_lead_source'),
            'status' => (string) get_option('mautic_bridge_default_lead_status'),
            'assigned' => (string) get_option('mautic_bridge_default_assigned_staff'),
            'name' => $contact['name'] ?: $contact['email'],
            'email' => $contact['email'],
            'phonenumber' => $contact['phonenumber'],
            'company' => $contact['company'],
            'address' => $contact['address'] ?: (string) ($existing->address ?? ''),
            'city' => $contact['city'] ?: (string) ($existing->city ?? ''),
            'state' => $contact['state'] ?: (string) ($existing->state ?? ''),
            'zip' => $contact['zip'] ?: (string) ($existing->zip ?? ''),
            'country' => is_numeric($contact['country']) ? (int) $contact['country'] : (int) ($existing->country ?? 0),
            'description' => (string) ($existing->description ?? ''),
            'is_public' => (int) ($existing->is_public ?? 0),
            'tags' => implode(',', mautic_bridge_campaign_tags($contact['campaigns'])),
        ];
    }

    public function sync_perfex_record_to_mautic(string $relType, int $relId, string $eventType): array
    {
        if ($relId <= 0 || !in_array($relType, ['lead', 'contact'], true)) {
            return ['ok' => false, 'retryable' => false, 'message' => 'invalid rel'];
        }

        $map = $this->find_map_by_perfex($relType, $relId);
        if ($eventType === 'contact_delete') {
            return $this->delete_mautic_contact($map);
        }

        $record = $this->get_perfex_record($relType, $relId);
        if (!$record) {
            return ['ok' => false, 'retryable' => false, 'message' => 'Perfex record not found'];
        }

        $payload = $this->mautic_payload_from_perfex($relType, $record);
        if (empty($payload['email'])) {
            return ['ok' => false, 'retryable' => false, 'message' => 'email is required'];
        }

        if ((int) get_option('mautic_bridge_dry_run') === 1) {
            $this->log('info', 'dry_run_outbound', ['rel_type' => $relType, 'rel_id' => $relId, 'payload' => $payload]);
            return ['ok' => true, 'message' => 'dry run'];
        }

        $endpoint = 'contacts/new';
        $method = 'POST';
        if ($map && !empty($map['mautic_contact_id'])) {
            $endpoint = 'contacts/' . (int) $map['mautic_contact_id'] . '/edit';
            $method = 'PATCH';
        }

        $response = $this->call_mautic_api($endpoint, $method, $payload);
        if (!$response['ok']) {
            return ['ok' => false, 'retryable' => $response['retryable'], 'message' => $response['message']];
        }

        $mauticId = $map['mautic_contact_id'] ?? null;
        if (!$mauticId && isset($response['body']['contact']['id'])) {
            $mauticId = (int) $response['body']['contact']['id'];
        }

        $this->upsert_map([
            'mautic_contact_id' => $mauticId,
            'perfex_rel_type' => $relType,
            'perfex_rel_id' => $relId,
            'email' => $payload['email'],
            'mautic_modified_at' => date('Y-m-d H:i:s'),
            'perfex_modified_at' => date('Y-m-d H:i:s'),
            'last_synced_at' => date('Y-m-d H:i:s'),
        ]);

        if ($mauticId) {
            $this->sync_campaign_memberships_from_tags($relType, $relId, (int) $mauticId);
        }

        return ['ok' => true, 'message' => 'Mautic contact upserted'];
    }

    private function delete_mautic_contact($map): array
    {
        if (!$map || empty($map['mautic_contact_id'])) {
            return ['ok' => true, 'message' => 'no remote map'];
        }
        if ((int) get_option('mautic_bridge_delete_remote') !== 1) {
            $this->log('info', 'remote_delete_skipped', ['map_id' => $map['id']]);
            return ['ok' => true, 'message' => 'remote delete disabled'];
        }
        if ((int) get_option('mautic_bridge_dry_run') === 1) {
            $this->log('info', 'dry_run_delete_remote', ['map_id' => $map['id']]);
            return ['ok' => true, 'message' => 'dry run'];
        }

        $response = $this->call_mautic_api('contacts/' . (int) $map['mautic_contact_id'] . '/delete', 'DELETE');
        return $response['ok']
            ? ['ok' => true, 'message' => 'Mautic contact deleted']
            : ['ok' => false, 'retryable' => $response['retryable'], 'message' => $response['message']];
    }

    private function get_perfex_record(string $relType, int $relId)
    {
        if ($relType === 'lead') {
            $this->load->model('leads_model');
            return $this->leads_model->get($relId);
        }

        $this->load->model('clients_model');
        return $this->clients_model->get_contact($relId);
    }

    private function mautic_payload_from_perfex(string $relType, $record): array
    {
        if ($relType === 'lead') {
            $parts = preg_split('/\s+/', trim((string) $record->name), 2);
            return [
                'firstname' => $parts[0] ?? '',
                'lastname' => $parts[1] ?? '',
                'email' => trim((string) $record->email),
                'phone' => (string) $record->phonenumber,
                'company' => (string) $record->company,
            ];
        }

        $company = '';
        if (!empty($record->userid)) {
            $client = $this->clients_model->get((int) $record->userid);
            $company = $client ? (string) $client->company : '';
        }

        return [
            'firstname' => (string) $record->firstname,
            'lastname' => (string) $record->lastname,
            'email' => trim((string) $record->email),
            'phone' => (string) $record->phonenumber,
            'company' => $company,
        ];
    }

    public function call_mautic_api(string $endpoint, string $method = 'GET', array $payload = []): array
    {
        $settings = $this->get_settings();
        $baseUrl = rtrim($settings['base_url'], '/');
        if ($baseUrl === '') {
            return ['ok' => false, 'retryable' => false, 'message' => 'Mautic base URL missing', 'body' => null];
        }

        $token = $this->get_access_token();
        if ($token === '') {
            return ['ok' => false, 'retryable' => false, 'message' => 'Unable to get Mautic access token', 'body' => null];
        }

        $url = $baseUrl . '/api/' . ltrim($endpoint, '/');
        $method = strtoupper($method);
        if ($method === 'GET' && !empty($payload)) {
            $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($payload);
        }

        $ch = curl_init($url);
        $headers = [
            'Accept: application/json',
            'Authorization: Bearer ' . $token,
        ];
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => max(5, $settings['timeout']),
        ]);

        if (in_array($method, ['POST', 'PUT', 'PATCH'], true)) {
            $headers[] = 'Content-Type: application/x-www-form-urlencoded';
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
        }

        $raw = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno) {
            return ['ok' => false, 'retryable' => true, 'message' => 'curl_error: ' . $error, 'body' => null];
        }

        $body = json_decode((string) $raw, true);
        if (!is_array($body)) {
            $body = ['raw' => $raw];
        }

        if ($http >= 200 && $http < 300) {
            return ['ok' => true, 'retryable' => false, 'message' => 'ok', 'body' => $body, 'http_code' => $http];
        }

        return [
            'ok' => false,
            'retryable' => $http >= 500 || $http === 429,
            'message' => 'Mautic API HTTP ' . $http,
            'body' => $body,
            'http_code' => $http,
        ];
    }

    private function get_access_token(): string
    {
        $expiresAt = (int) get_option('mautic_bridge_oauth_expires_at');
        $token = (string) get_option('mautic_bridge_oauth_access_token');
        if ($token !== '' && $expiresAt > time() + 60) {
            return $token;
        }

        $settings = $this->get_settings();
        if ($settings['base_url'] === '' || $settings['oauth_client_id'] === '' || $settings['oauth_client_secret'] === '') {
            return '';
        }

        $url = rtrim($settings['base_url'], '/') . '/oauth/v2/token';
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'grant_type' => 'client_credentials',
                'client_id' => $settings['oauth_client_id'],
                'client_secret' => $settings['oauth_client_secret'],
            ]),
            CURLOPT_TIMEOUT => max(5, $settings['timeout']),
            CURLOPT_CONNECTTIMEOUT => 5,
        ]);
        $raw = curl_exec($ch);
        $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $body = json_decode((string) $raw, true);
        if ($http < 200 || $http >= 300 || !is_array($body) || empty($body['access_token'])) {
            $this->log('error', 'oauth_token_failed', ['http_code' => $http]);
            return '';
        }

        update_option('mautic_bridge_oauth_access_token', (string) $body['access_token']);
        update_option('mautic_bridge_oauth_expires_at', time() + (int) ($body['expires_in'] ?? 3600));

        return (string) $body['access_token'];
    }

    public function reconcile_contacts(int $limit = 50): array
    {
        $since = (string) get_option('mautic_bridge_pull_since');
        $search = $since !== '' ? 'dateModified:>' . $since : '';
        $response = $this->call_mautic_api('contacts', 'GET', [
            'search' => $search,
            'limit' => max(1, min(200, $limit)),
            'orderBy' => 'dateModified',
            'orderByDir' => 'ASC',
        ]);

        if (!$response['ok']) {
            return ['ok' => false, 'message' => $response['message'], 'queued' => 0];
        }

        $contacts = $response['body']['contacts'] ?? [];
        $queued = 0;
        $latest = $since;
        foreach ($contacts as $contact) {
            if (!is_array($contact)) {
                continue;
            }
            $this->enqueue_inbound_contact(['contact' => $contact]);
            $queued++;
            $modified = mautic_bridge_sql_datetime($contact['dateModified'] ?? null);
            if ($modified && ($latest === '' || strtotime($modified) > strtotime($latest))) {
                $latest = $modified;
            }
        }
        if ($latest !== '') {
            update_option('mautic_bridge_pull_since', $latest);
        }

        return ['ok' => true, 'queued' => $queued, 'message' => 'contacts queued'];
    }

    public function sync_campaign_catalog(): array
    {
        $response = $this->call_mautic_api('campaigns', 'GET', ['limit' => 200, 'minimal' => 1]);
        if (!$response['ok']) {
            return ['ok' => false, 'message' => $response['message'], 'synced' => 0];
        }

        $campaigns = $response['body']['campaigns'] ?? [];
        $synced = 0;
        foreach ($campaigns as $campaign) {
            if (!is_array($campaign) || empty($campaign['id'])) {
                continue;
            }
            $tag = mautic_bridge_campaign_tags([[
                'id' => (int) $campaign['id'],
                'alias' => $campaign['alias'] ?? '',
                'name' => $campaign['name'] ?? '',
            ]]);
            $this->db->replace(db_prefix() . $this->campaignTable, [
                'mautic_campaign_id' => (int) $campaign['id'],
                'campaign_alias' => $campaign['alias'] ?? null,
                'campaign_name' => $campaign['name'] ?? null,
                'perfex_tag' => $tag[0] ?? ('mautic:campaign:' . (int) $campaign['id']),
                'synced_at' => date('Y-m-d H:i:s'),
            ]);
            $synced++;
        }

        return ['ok' => true, 'synced' => $synced, 'message' => 'campaigns synced'];
    }

    public function mark_lead_converted(int $leadId, int $customerId): void
    {
        if ($leadId <= 0 || $customerId <= 0) {
            return;
        }
        $map = $this->find_map_by_perfex('lead', $leadId);
        if (!$map) {
            return;
        }
        $contact = $this->primary_contact_for_customer($customerId);
        if (!$contact) {
            return;
        }
        $map['perfex_rel_type'] = 'contact';
        $map['perfex_rel_id'] = (int) $contact['id'];
        $map['perfex_modified_at'] = date('Y-m-d H:i:s');
        $map['last_synced_at'] = date('Y-m-d H:i:s');
        $this->upsert_map($map);
        $this->enqueue_perfex_change('contact', (int) $contact['id'], 'upsert');
    }

    private function converted_contact_for_lead(int $leadId): ?array
    {
        $client = $this->db->where('leadid', $leadId)->get(db_prefix() . 'clients')->row_array();
        if (!$client) {
            return null;
        }

        return $this->primary_contact_for_customer((int) $client['userid']);
    }

    private function primary_contact_for_customer(int $customerId): ?array
    {
        $contact = $this->db
            ->where('userid', $customerId)
            ->order_by('is_primary', 'DESC')
            ->order_by('id', 'ASC')
            ->get(db_prefix() . 'contacts')
            ->row_array();

        return $contact ?: null;
    }

    private function save_campaign_tags(int $relId, string $relType, array $campaigns): void
    {
        $campaignTags = mautic_bridge_campaign_tags($campaigns);
        if (empty($campaignTags) || !function_exists('handle_tags_save')) {
            return;
        }
        $this->save_campaign_mappings($campaigns);
        $existing = function_exists('get_tags_in') ? get_tags_in($relId, $relType) : [];
        $existingNames = [];
        foreach ($existing as $tag) {
            if (is_array($tag) && isset($tag['name'])) {
                $existingNames[] = $tag['name'];
            } elseif (is_object($tag) && isset($tag->name)) {
                $existingNames[] = $tag->name;
            } elseif (is_string($tag)) {
                $existingNames[] = $tag;
            }
        }
        $merged = array_values(array_unique(array_merge($existingNames, $campaignTags)));
        handle_tags_save(implode(',', $merged), $relId, $relType);
    }

    private function save_campaign_mappings(array $campaigns): void
    {
        foreach ($campaigns as $campaign) {
            if (!is_array($campaign) || empty($campaign['id'])) {
                continue;
            }
            $tag = mautic_bridge_campaign_tags([$campaign]);
            $this->db->replace(db_prefix() . $this->campaignTable, [
                'mautic_campaign_id' => (int) $campaign['id'],
                'campaign_alias' => $campaign['alias'] ?? null,
                'campaign_name' => $campaign['name'] ?? null,
                'perfex_tag' => $tag[0] ?? ('mautic:campaign:' . (int) $campaign['id']),
                'synced_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    private function sync_campaign_memberships_from_tags(string $relType, int $relId, int $mauticContactId): void
    {
        if (!function_exists('get_tags_in')) {
            return;
        }

        $tagRows = get_tags_in($relId, $relType);
        $currentTags = [];
        foreach ($tagRows as $tag) {
            if (is_array($tag) && isset($tag['name'])) {
                $currentTags[] = $tag['name'];
            } elseif (is_object($tag) && isset($tag->name)) {
                $currentTags[] = $tag->name;
            } elseif (is_string($tag)) {
                $currentTags[] = $tag;
            }
        }

        $campaignRows = $this->db->get(db_prefix() . $this->campaignTable)->result_array();
        foreach ($campaignRows as $campaign) {
            $campaignId = (int) $campaign['mautic_campaign_id'];
            $tag = (string) $campaign['perfex_tag'];
            if ($campaignId <= 0 || $tag === '') {
                continue;
            }

            $action = in_array($tag, $currentTags, true) ? 'add' : 'remove';
            $response = $this->call_mautic_api(
                'campaigns/' . $campaignId . '/contact/' . $mauticContactId . '/' . $action,
                'POST'
            );
            if (!$response['ok']) {
                $this->log('warning', 'campaign_membership_' . $action . '_failed', [
                    'campaign_id' => $campaignId,
                    'mautic_contact_id' => $mauticContactId,
                    'message' => $response['message'],
                ]);
            }
        }
    }

    private function extract_mautic_webhook_events(array $payload): array
    {
        $events = [];
        foreach ($payload as $key => $eventPayload) {
            if (strpos((string) $key, 'mautic.') !== 0) {
                continue;
            }

            if (is_array($eventPayload) && isset($eventPayload[0]) && is_array($eventPayload[0])) {
                foreach ($eventPayload as $item) {
                    if (is_array($item)) {
                        $events[] = ['key' => (string) $key, 'payload' => $item];
                    }
                }
                continue;
            }

            if (is_array($eventPayload)) {
                $events[] = ['key' => (string) $key, 'payload' => $eventPayload];
            }
        }

        return $events;
    }

    private function mautic_event_type(string $eventKey): string
    {
        $key = strtolower($eventKey);
        if (strpos($key, 'delete') !== false || strpos($key, 'deleted') !== false) {
            return 'delete';
        }
        if (strpos($key, 'points') !== false || strpos($key, 'point') !== false) {
            return 'points_changed';
        }
        if (strpos($key, 'segment') !== false || strpos($key, 'list') !== false) {
            return 'segment_changed';
        }
        if (strpos($key, 'email') !== false && strpos($key, 'open') !== false) {
            return 'email_opened';
        }
        if (strpos($key, 'email') !== false && (strpos($key, 'send') !== false || strpos($key, 'sent') !== false)) {
            return 'email_sent';
        }
        if (strpos($key, 'form') !== false) {
            return 'form_submitted';
        }
        if (strpos($key, 'page') !== false || strpos($key, 'hit') !== false) {
            return 'page_hit';
        }
        if (strpos($key, 'text') !== false || strpos($key, 'sms') !== false) {
            return 'text_sent';
        }
        if (strpos($key, 'channel') !== false || strpos($key, 'subscription') !== false) {
            return 'subscription_changed';
        }
        if (strpos($key, 'company') !== false) {
            return 'company_updated';
        }
        if (strpos($key, 'identified') !== false) {
            return 'contact_identified';
        }
        if (strpos($key, 'lead') !== false || strpos($key, 'contact') !== false) {
            return 'contact_upsert';
        }

        return 'activity';
    }

    private function resolve_perfex_target_from_mautic_event(array $eventPayload): ?array
    {
        $contact = mautic_bridge_normalize_contact_payload($eventPayload);
        $map = $this->find_map($contact['mautic_contact_id'], $contact['email']);
        if ($map) {
            if ($map['perfex_rel_type'] === 'lead') {
                $converted = $this->converted_contact_for_lead((int) $map['perfex_rel_id']);
                if ($converted) {
                    $map['perfex_rel_type'] = 'contact';
                    $map['perfex_rel_id'] = (int) $converted['id'];
                    $this->upsert_map($map);
                }
            }

            return [
                'rel_type' => (string) $map['perfex_rel_type'],
                'rel_id' => (int) $map['perfex_rel_id'],
                'email' => (string) ($map['email'] ?: $contact['email']),
                'mautic_contact_id' => $contact['mautic_contact_id'] ?: (int) $map['mautic_contact_id'],
            ];
        }

        if ($contact['email'] === '') {
            return null;
        }

        $lead = $this->db
            ->where('email', $contact['email'])
            ->order_by('id', 'DESC')
            ->get(db_prefix() . 'leads')
            ->row_array();
        if ($lead) {
            if (!empty($contact['mautic_contact_id'])) {
                $this->upsert_map([
                    'mautic_contact_id' => $contact['mautic_contact_id'],
                    'perfex_rel_type' => 'lead',
                    'perfex_rel_id' => (int) $lead['id'],
                    'email' => $contact['email'],
                    'mautic_modified_at' => $contact['mautic_modified_at'],
                    'perfex_modified_at' => null,
                    'last_synced_at' => date('Y-m-d H:i:s'),
                ]);
            }

            return [
                'rel_type' => 'lead',
                'rel_id' => (int) $lead['id'],
                'email' => $contact['email'],
                'mautic_contact_id' => $contact['mautic_contact_id'],
            ];
        }

        $perfexContact = $this->db
            ->where('email', $contact['email'])
            ->order_by('id', 'DESC')
            ->get(db_prefix() . 'contacts')
            ->row_array();
        if ($perfexContact) {
            if (!empty($contact['mautic_contact_id'])) {
                $this->upsert_map([
                    'mautic_contact_id' => $contact['mautic_contact_id'],
                    'perfex_rel_type' => 'contact',
                    'perfex_rel_id' => (int) $perfexContact['id'],
                    'email' => $contact['email'],
                    'mautic_modified_at' => $contact['mautic_modified_at'],
                    'perfex_modified_at' => null,
                    'last_synced_at' => date('Y-m-d H:i:s'),
                ]);
            }

            return [
                'rel_type' => 'contact',
                'rel_id' => (int) $perfexContact['id'],
                'email' => $contact['email'],
                'mautic_contact_id' => $contact['mautic_contact_id'],
            ];
        }

        return null;
    }

    private function mautic_event_activity_message(string $eventType, array $eventPayload): string
    {
        $summary = $this->mautic_event_summary($eventPayload);
        $suffix = $summary !== '' ? ': ' . $summary : '';
        $labels = [
            'contact_upsert' => 'Mautic contact updated',
            'contact_identified' => 'Mautic contact identified',
            'company_updated' => 'Mautic company updated',
            'points_changed' => 'Mautic points changed',
            'segment_changed' => 'Mautic segment membership changed',
            'email_opened' => 'Mautic email opened',
            'email_sent' => 'Mautic email sent',
            'form_submitted' => 'Mautic form submitted',
            'page_hit' => 'Mautic page hit',
            'text_sent' => 'Mautic text sent',
            'subscription_changed' => 'Mautic subscription changed',
            'activity' => 'Mautic activity received',
        ];

        return ($labels[$eventType] ?? 'Mautic activity received') . $suffix;
    }

    private function mautic_event_summary(array $eventPayload): string
    {
        $candidates = [
            $this->extract_named_entity_label($eventPayload, ['email', 'form', 'page', 'asset', 'campaign', 'segment', 'list', 'company', 'channel']),
            $this->recursive_scalar_value($eventPayload, ['name', 'title', 'subject', 'url']),
        ];
        foreach ($candidates as $candidate) {
            $candidate = trim((string) $candidate);
            if ($candidate !== '') {
                return mb_substr(mautic_bridge_repair_mojibake($candidate), 0, 180);
            }
        }

        return '';
    }

    private function extract_mautic_points(array $eventPayload): ?int
    {
        if (isset($eventPayload['points']) && is_array($eventPayload['points'])) {
            foreach (['new_points', 'newPoints', 'new', 'points'] as $key) {
                if (isset($eventPayload['points'][$key]) && is_numeric($eventPayload['points'][$key])) {
                    return (int) $eventPayload['points'][$key];
                }
            }
        }

        $value = $this->recursive_scalar_value($eventPayload, ['new_points', 'newPoints', 'points']);
        return is_numeric($value) ? (int) $value : null;
    }

    private function apply_mautic_event_tags(array $target, string $eventType, array $eventPayload): array
    {
        $add = [];
        $remove = [];
        $changed = false;
        $relType = (string) $target['rel_type'];
        $relId = (int) $target['rel_id'];

        if ($eventType === 'segment_changed') {
            $segment = $this->extract_named_entity($eventPayload, ['segment', 'list', 'leadlist']);
            $tag = $this->entity_tag('mautic:segment:', $segment);
            if ($tag !== '') {
                if ($this->event_is_removal($eventPayload)) {
                    $remove[] = $tag;
                } else {
                    $add[] = $tag;
                }
            }
        }

        if ($eventType === 'subscription_changed') {
            $channel = $this->extract_named_entity($eventPayload, ['channel']);
            $channelName = $this->slug_from_value($this->entity_label($channel) ?: (string) $this->recursive_scalar_value($eventPayload, ['channel']));
            if ($channelName !== '') {
                $prefix = 'mautic:channel:' . $channelName . ':';
                $add[] = $prefix . ($this->event_is_removal($eventPayload) ? 'unsubscribed' : 'subscribed');
                $remove[] = $prefix . ($this->event_is_removal($eventPayload) ? 'subscribed' : 'unsubscribed');
            }
        }

        if ($eventType === 'company_updated') {
            $company = $this->extract_named_entity($eventPayload, ['company']);
            $tag = $this->entity_tag('mautic:company:', $company);
            if ($tag !== '') {
                $add[] = $tag;
            }
        }

        if (!empty($add) || !empty($remove)) {
            $this->save_tags_delta($relId, $relType, $add, $remove);
            $changed = true;
        }

        if ($relType === 'lead' && $eventType === 'segment_changed') {
            $this->set_lead_custom_field_value($relId, 'leads_mautic_segments', implode(', ', $this->current_tag_names($relId, $relType, 'mautic:segment:')));
        }

        return ['changed' => $changed];
    }

    private function ensure_mautic_event_custom_fields(): void
    {
        $fields = [
            'leads_mautic_points' => ['name' => 'Mautic Points', 'type' => 'number', 'show_on_table' => 1],
            'leads_mautic_segments' => ['name' => 'Mautic Segments', 'type' => 'textarea', 'show_on_table' => 0],
        ];

        foreach ($fields as $slug => $field) {
            $exists = $this->db
                ->where('fieldto', 'leads')
                ->where('slug', $slug)
                ->get(db_prefix() . 'customfields')
                ->row_array();
            if ($exists) {
                continue;
            }

            $maxOrder = (int) $this->db
                ->select_max('field_order')
                ->where('fieldto', 'leads')
                ->get(db_prefix() . 'customfields')
                ->row('field_order');
            $this->db->insert(db_prefix() . 'customfields', [
                'fieldto' => 'leads',
                'name' => $field['name'],
                'slug' => $slug,
                'required' => 0,
                'type' => $field['type'],
                'options' => '',
                'display_inline' => 0,
                'field_order' => $maxOrder + 1,
                'active' => 1,
                'show_on_pdf' => 0,
                'show_on_ticket_form' => 0,
                'only_admin' => 0,
                'show_on_table' => (int) $field['show_on_table'],
                'show_on_client_portal' => 0,
                'disalow_client_to_edit' => 1,
                'bs_column' => 12,
                'default_value' => '',
            ]);
        }
    }

    private function set_lead_custom_field_value(int $leadId, string $slug, string $value): void
    {
        if ($leadId <= 0) {
            return;
        }
        $field = $this->db
            ->where('fieldto', 'leads')
            ->where('slug', $slug)
            ->get(db_prefix() . 'customfields')
            ->row_array();
        if (!$field) {
            return;
        }

        $row = $this->db
            ->where('relid', $leadId)
            ->where('fieldid', (int) $field['id'])
            ->where('fieldto', 'leads')
            ->get(db_prefix() . 'customfieldsvalues')
            ->row_array();
        if ($row) {
            $this->db->where('id', (int) $row['id'])->update(db_prefix() . 'customfieldsvalues', ['value' => $value]);
            return;
        }

        if ($value !== '') {
            $this->db->insert(db_prefix() . 'customfieldsvalues', [
                'relid' => $leadId,
                'fieldid' => (int) $field['id'],
                'fieldto' => 'leads',
                'value' => $value,
            ]);
        }
    }

    private function log_mautic_lead_activity(int $leadId, string $message, array $context = []): void
    {
        $this->db->insert(db_prefix() . 'lead_activity_log', [
            'date' => date('Y-m-d H:i:s'),
            'description' => $message,
            'leadid' => $leadId,
            'staffid' => 0,
            'additional_data' => json_encode($context, JSON_UNESCAPED_UNICODE),
            'full_name' => '[CRON]',
            'custom_activity' => 1,
        ]);
    }

    private function update_lead_last_contact(int $leadId, ?string $date): void
    {
        if ($leadId <= 0) {
            return;
        }
        $this->db->where('id', $leadId)->update(db_prefix() . 'leads', [
            'lastcontact' => $date ?: date('Y-m-d H:i:s'),
        ]);
    }

    private function save_tags_delta(int $relId, string $relType, array $add, array $remove): void
    {
        if (!function_exists('handle_tags_save')) {
            return;
        }
        $current = $this->current_tag_names($relId, $relType);
        $remove = array_unique(array_filter($remove));
        $next = [];
        foreach ($current as $tag) {
            if (!in_array($tag, $remove, true)) {
                $next[] = $tag;
            }
        }
        $next = array_values(array_unique(array_merge($next, array_filter($add))));
        handle_tags_save(implode(',', $next), $relId, $relType);
    }

    private function save_tags_with_prefix_replace(int $relId, string $relType, array $add, array $prefixes): void
    {
        if (!function_exists('handle_tags_save')) {
            return;
        }
        $current = $this->current_tag_names($relId, $relType);
        $next = [];
        foreach ($current as $tag) {
            $matched = false;
            foreach ($prefixes as $prefix) {
                if (strpos($tag, $prefix) === 0) {
                    $matched = true;
                    break;
                }
            }
            if (!$matched) {
                $next[] = $tag;
            }
        }
        $next = array_values(array_unique(array_merge($next, array_filter($add))));
        handle_tags_save(implode(',', $next), $relId, $relType);
    }

    private function current_tag_names(int $relId, string $relType, string $prefix = ''): array
    {
        $tags = function_exists('get_tags_in') ? get_tags_in($relId, $relType) : [];
        $names = [];
        foreach ($tags as $tag) {
            if (is_array($tag) && isset($tag['name'])) {
                $name = (string) $tag['name'];
            } elseif (is_object($tag) && isset($tag->name)) {
                $name = (string) $tag->name;
            } elseif (is_string($tag)) {
                $name = $tag;
            } else {
                continue;
            }
            if ($prefix === '' || strpos($name, $prefix) === 0) {
                $names[] = $name;
            }
        }

        return array_values(array_unique($names));
    }

    private function score_tag(int $points): string
    {
        if ($points >= 30) {
            return 'mautic:score:hot';
        }
        if ($points >= 10) {
            return 'mautic:score:warm';
        }

        return 'mautic:score:cold';
    }

    private function event_is_removal(array $eventPayload): bool
    {
        $text = strtolower(json_encode($eventPayload, JSON_UNESCAPED_UNICODE));
        foreach (['remove', 'removed', 'unsubscribe', 'unsubscribed', 'false'] as $needle) {
            if (strpos($text, $needle) !== false) {
                return true;
            }
        }

        return false;
    }

    private function event_datetime(array $eventPayload): ?string
    {
        $value = $this->recursive_scalar_value($eventPayload, ['timestamp', 'dateTriggered', 'dateAdded', 'dateModified', 'date_modified']);
        return mautic_bridge_sql_datetime($value);
    }

    private function extract_named_entity(array $payload, array $keys): array
    {
        foreach ($keys as $key) {
            if (isset($payload[$key]) && is_array($payload[$key])) {
                return $payload[$key];
            }
        }
        foreach ($payload as $value) {
            if (is_array($value)) {
                $found = $this->extract_named_entity($value, $keys);
                if (!empty($found)) {
                    return $found;
                }
            }
        }

        return [];
    }

    private function extract_named_entity_label(array $payload, array $keys): string
    {
        $entity = $this->extract_named_entity($payload, $keys);
        return $this->entity_label($entity);
    }

    private function entity_label(array $entity): string
    {
        foreach (['name', 'title', 'subject', 'alias', 'id'] as $key) {
            if (isset($entity[$key]) && trim((string) $entity[$key]) !== '') {
                return (string) $entity[$key];
            }
        }

        return '';
    }

    private function entity_tag(string $prefix, array $entity): string
    {
        $label = $this->entity_label($entity);
        $slug = $this->slug_from_value($label);
        return $slug !== '' ? $prefix . $slug : '';
    }

    private function slug_from_value(string $value): string
    {
        $value = mautic_bridge_repair_mojibake(trim($value));
        if ($value === '') {
            return '';
        }
        if (function_exists('mb_strtolower')) {
            $value = mb_strtolower($value, 'UTF-8');
        } else {
            $value = strtolower($value);
        }
        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        if ($ascii !== false && $ascii !== '') {
            $value = $ascii;
        }
        $value = preg_replace('/[^a-z0-9_-]+/i', '-', $value);
        return trim((string) $value, '-');
    }

    private function recursive_scalar_value($data, array $keys)
    {
        if (!is_array($data)) {
            return null;
        }
        foreach ($keys as $key) {
            if (isset($data[$key]) && !is_array($data[$key]) && !is_object($data[$key])) {
                return $data[$key];
            }
        }
        foreach ($data as $value) {
            if (is_array($value)) {
                $found = $this->recursive_scalar_value($value, $keys);
                if ($found !== null && $found !== '') {
                    return $found;
                }
            }
        }

        return null;
    }

    private function has_required_perfex_defaults(): bool
    {
        return get_option('mautic_bridge_default_lead_source') !== ''
            && get_option('mautic_bridge_default_lead_status') !== ''
            && get_option('mautic_bridge_default_assigned_staff') !== '';
    }

    private function find_map($mauticContactId, string $email = ''): ?array
    {
        if (!empty($mauticContactId)) {
            $row = $this->db->where('mautic_contact_id', (int) $mauticContactId)->get(db_prefix() . $this->mapTable)->row_array();
            if ($row) {
                return $row;
            }
        }
        if ($email !== '') {
            $row = $this->db->where('email', $email)->get(db_prefix() . $this->mapTable)->row_array();
            if ($row) {
                return $row;
            }
        }

        return null;
    }

    private function find_map_by_perfex(string $relType, int $relId): ?array
    {
        $row = $this->db
            ->where('perfex_rel_type', $relType)
            ->where('perfex_rel_id', $relId)
            ->get(db_prefix() . $this->mapTable)
            ->row_array();

        return $row ?: null;
    }

    private function find_existing_perfex_map_by_email(array $contact): ?array
    {
        $email = strtolower(trim((string) ($contact['email'] ?? '')));
        if ($email === '') {
            return null;
        }

        $lead = $this->db
            ->where('LOWER(email) =', $email)
            ->order_by('id', 'ASC')
            ->get(db_prefix() . 'leads')
            ->row_array();
        if ($lead) {
            return [
                'mautic_contact_id' => $contact['mautic_contact_id'],
                'perfex_rel_type' => 'lead',
                'perfex_rel_id' => (int) $lead['id'],
                'email' => $email,
                'mautic_modified_at' => $contact['mautic_modified_at'],
                'perfex_modified_at' => null,
                'last_synced_at' => null,
            ];
        }

        $perfexContact = $this->db
            ->where('LOWER(email) =', $email)
            ->order_by('id', 'ASC')
            ->get(db_prefix() . 'contacts')
            ->row_array();
        if ($perfexContact) {
            return [
                'mautic_contact_id' => $contact['mautic_contact_id'],
                'perfex_rel_type' => 'contact',
                'perfex_rel_id' => (int) $perfexContact['id'],
                'email' => $email,
                'mautic_modified_at' => $contact['mautic_modified_at'],
                'perfex_modified_at' => null,
                'last_synced_at' => null,
            ];
        }

        return null;
    }

    private function is_deliverable_email(string $email): bool
    {
        $email = strtolower(trim($email));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $domain = substr(strrchr($email, '@') ?: '', 1);
        if ($domain === '' || strpos($domain, '.') === false) {
            return false;
        }

        if (preg_match('/(?:hotline|phone|tel|zalo|website|www|http|https)$/i', $domain)) {
            return false;
        }

        if (function_exists('checkdnsrr')) {
            return checkdnsrr($domain, 'MX') || checkdnsrr($domain, 'A');
        }

        return true;
    }

    private function upsert_map(array $data): void
    {
        $now = date('Y-m-d H:i:s');
        $row = [
            'mautic_contact_id' => !empty($data['mautic_contact_id']) ? (int) $data['mautic_contact_id'] : null,
            'perfex_rel_type' => $data['perfex_rel_type'],
            'perfex_rel_id' => (int) $data['perfex_rel_id'],
            'email' => $data['email'] ?? null,
            'mautic_modified_at' => $data['mautic_modified_at'] ?? null,
            'perfex_modified_at' => $data['perfex_modified_at'] ?? null,
            'last_synced_at' => $data['last_synced_at'] ?? $now,
            'updated_at' => $now,
        ];

        $existing = $this->find_map($row['mautic_contact_id'], (string) $row['email']);
        if (!$existing) {
            $existing = $this->find_map_by_perfex($row['perfex_rel_type'], $row['perfex_rel_id']);
        }

        if ($existing) {
            $this->db->where('id', (int) $existing['id'])->update(db_prefix() . $this->mapTable, $row);
            return;
        }

        $row['created_at'] = $now;
        $this->db->insert(db_prefix() . $this->mapTable, $row);
    }

    private function mark_queue_done(int $id): void
    {
        $this->db->where('id', $id)->update(db_prefix() . $this->queueTable, [
            'status' => 'done',
            'updated_at' => date('Y-m-d H:i:s'),
            'last_error' => null,
        ]);
    }

    private function mark_queue_failed(array $row, string $message, bool $retryable): void
    {
        $attempts = (int) $row['attempts'] + 1;
        $retryMax = (int) get_option('mautic_bridge_retry_max') ?: 3;
        $status = ($retryable && $attempts <= $retryMax) ? 'retry' : 'failed';
        $delay = min(3600, 60 * max(1, $attempts));
        $this->db->where('id', (int) $row['id'])->update(db_prefix() . $this->queueTable, [
            'status' => $status,
            'attempts' => $attempts,
            'next_attempt_at' => $status === 'retry' ? date('Y-m-d H:i:s', time() + $delay) : null,
            'last_error' => $message,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $this->log($status === 'retry' ? 'warning' : 'error', 'queue_' . $status, ['queue_id' => $row['id'], 'message' => $message]);
    }

    public function google_import_defaults_ok(): bool
    {
        return $this->has_required_perfex_defaults()
            && (string) get_option('mautic_bridge_base_url') !== ''
            && (string) get_option('mautic_bridge_oauth_client_id') !== ''
            && (string) get_option('mautic_bridge_oauth_client_secret') !== '';
    }

    public function create_google_import_job_from_upload(string $field): array
    {
        if (empty($_FILES[$field]) || !is_uploaded_file($_FILES[$field]['tmp_name'])) {
            return ['success' => false, 'message' => 'No import file uploaded.'];
        }

        $file = $_FILES[$field];
        if ((int) $file['size'] <= 0 || (int) $file['size'] > 20 * 1024 * 1024) {
            return ['success' => false, 'message' => 'File must be smaller than 20MB.'];
        }

        $originalName = (string) $file['name'];
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if (!in_array($ext, ['json', 'csv'], true)) {
            return ['success' => false, 'message' => 'Only JSON and CSV files are supported.'];
        }

        $raw = file_get_contents($file['tmp_name']);
        if ($raw === false || trim($raw) === '') {
            return ['success' => false, 'message' => 'Import file is empty.'];
        }

        $parsed = $ext === 'json' ? $this->parse_google_import_json($raw) : $this->parse_google_import_csv($raw);
        if (!$parsed['ok']) {
            return ['success' => false, 'message' => $parsed['message']];
        }

        $rows = $parsed['rows'];
        if (empty($rows)) {
            return ['success' => false, 'message' => 'No records found in file.'];
        }

        $columns = $this->google_import_columns($rows);
        $mapping = $this->suggest_google_import_mapping($columns);
        $now = date('Y-m-d H:i:s');
        $job = [
            'filename' => $originalName,
            'source_type' => $ext,
            'status' => 'uploaded',
            'mapping_json' => json_encode($mapping, JSON_UNESCAPED_UNICODE),
            'columns_json' => json_encode($columns, JSON_UNESCAPED_UNICODE),
            'stats_json' => null,
            'total_rows' => count($rows),
            'pending_rows' => 0,
            'processed_rows' => 0,
            'success_rows' => 0,
            'skipped_rows' => 0,
            'failed_rows' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ];
        $this->db->insert(db_prefix() . $this->importJobTable, $job);
        $jobId = (int) $this->db->insert_id();

        $seenEmails = [];
        $counts = [
            'total' => 0,
            'pending' => 0,
            'valid_email' => 0,
            'no_email' => 0,
            'duplicate_email' => 0,
        ];

        foreach ($rows as $index => $sourceRow) {
            $emails = $this->valid_google_emails($this->mapped_google_value($sourceRow, $mapping['emails'] ?? ''));
            if (empty($emails)) {
                $emails = [''];
            }

            foreach ($emails as $email) {
                $counts['total']++;
                $normalized = $this->normalize_google_import_row($sourceRow, $mapping, $email);
                $normalized['source_dataset'] = $originalName;
                $status = 'pending';
                $message = null;
                if ($normalized['email'] === '') {
                    $status = 'skipped';
                    $message = 'email is required';
                    $counts['no_email']++;
                } elseif (isset($seenEmails[$normalized['email']])) {
                    $status = 'skipped';
                    $message = 'duplicate email in file';
                    $counts['duplicate_email']++;
                } else {
                    $seenEmails[$normalized['email']] = true;
                    $counts['valid_email']++;
                    $counts['pending']++;
                }

                $this->db->insert(db_prefix() . $this->importRowTable, [
                    'job_id' => $jobId,
                    'row_number' => $index + 1,
                    'source_json' => json_encode($sourceRow, JSON_UNESCAPED_UNICODE),
                    'normalized_json' => json_encode($normalized, JSON_UNESCAPED_UNICODE),
                    'email' => $normalized['email'] ?: null,
                    'status' => $status,
                    'message' => $message,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        $this->db->where('id', $jobId)->update(db_prefix() . $this->importJobTable, [
            'status' => $counts['pending'] > 0 ? 'ready' : 'done',
            'total_rows' => $counts['total'],
            'pending_rows' => $counts['pending'],
            'skipped_rows' => $counts['no_email'] + $counts['duplicate_email'],
            'stats_json' => json_encode($counts, JSON_UNESCAPED_UNICODE),
            'updated_at' => $now,
        ]);

        $payload = $this->get_google_import_job_payload($jobId);
        $payload['message'] = 'Import file parsed.';

        return $payload;
    }

    public function get_google_import_job_payload(int $jobId): array
    {
        $job = $this->db->where('id', $jobId)->get(db_prefix() . $this->importJobTable)->row_array();
        if (!$job) {
            return ['success' => false, 'message' => 'Import job not found.'];
        }

        $previewRows = $this->db
            ->where('job_id', $jobId)
            ->order_by('row_number', 'ASC')
            ->limit(25)
            ->get(db_prefix() . $this->importRowTable)
            ->result_array();

        $preview = [];
        foreach ($previewRows as $row) {
            $normalized = json_decode((string) $row['normalized_json'], true);
            $preview[] = [
                'row_number' => (int) $row['row_number'],
                'status' => (string) $row['status'],
                'message' => (string) $row['message'],
                'email' => (string) $row['email'],
                'company' => (string) ($normalized['company'] ?? ''),
                'phone' => (string) ($normalized['phone'] ?? ''),
                'city' => (string) ($normalized['city'] ?? ''),
                'website' => (string) ($normalized['website'] ?? ''),
            ];
        }

        return [
            'success' => true,
            'job' => [
                'id' => (int) $job['id'],
                'filename' => (string) $job['filename'],
                'status' => (string) $job['status'],
                'total_rows' => (int) $job['total_rows'],
                'pending_rows' => (int) $job['pending_rows'],
                'processed_rows' => (int) $job['processed_rows'],
                'success_rows' => (int) $job['success_rows'],
                'skipped_rows' => (int) $job['skipped_rows'],
                'failed_rows' => (int) $job['failed_rows'],
                'mapping' => json_decode((string) $job['mapping_json'], true) ?: [],
                'columns' => json_decode((string) $job['columns_json'], true) ?: [],
                'stats' => json_decode((string) $job['stats_json'], true) ?: [],
            ],
            'preview' => $preview,
            'defaults_ok' => $this->google_import_defaults_ok(),
        ];
    }

    public function process_google_import_job(int $jobId, int $limit = 10): array
    {
        $limit = max(1, min(50, $limit));
        if (!$this->google_import_defaults_ok()) {
            return ['success' => false, 'message' => 'Missing Mautic credentials or default Perfex lead source/status/assigned staff.'];
        }

        $job = $this->db->where('id', $jobId)->get(db_prefix() . $this->importJobTable)->row_array();
        if (!$job) {
            return ['success' => false, 'message' => 'Import job not found.'];
        }

        $rows = $this->db
            ->where('job_id', $jobId)
            ->where('status', 'pending')
            ->order_by('row_number', 'ASC')
            ->limit($limit)
            ->get(db_prefix() . $this->importRowTable)
            ->result_array();

        $stats = ['processed' => 0, 'success' => 0, 'failed' => 0, 'skipped' => 0];
        foreach ($rows as $row) {
            $stats['processed']++;
            $normalized = json_decode((string) $row['normalized_json'], true);
            if (!is_array($normalized) || empty($normalized['email'])) {
                $this->mark_google_import_row((int) $row['id'], 'skipped', 'email is required');
                $stats['skipped']++;
                continue;
            }

            $result = $this->import_google_row($normalized);
            if ($result['ok']) {
                $this->mark_google_import_row((int) $row['id'], 'done', $result['message'], $result['mautic_contact_id'] ?? null, $result['perfex_lead_id'] ?? null);
                $stats['success']++;
            } else {
                $this->mark_google_import_row((int) $row['id'], 'failed', $result['message']);
                $stats['failed']++;
            }
        }

        $summary = $this->refresh_google_import_job_counts($jobId);
        return [
            'success' => $stats['failed'] === 0,
            'message' => $summary['pending_rows'] > 0 ? 'Batch processed.' : 'Import completed.',
            'batch' => $stats,
            'job' => $summary,
        ];
    }

    private function import_google_row(array $row): array
    {
        $existing = $this->find_mautic_contact_by_email($row['email']);
        $payload = $this->mautic_payload_from_google_row($row);
        $endpoint = $existing ? 'contacts/' . (int) $existing . '/edit' : 'contacts/new';
        $method = $existing ? 'PATCH' : 'POST';
        $response = $this->call_mautic_api($endpoint, $method, $payload);
        if (!$response['ok']) {
            return ['ok' => false, 'message' => $response['message']];
        }

        $mauticId = $existing;
        if (!$mauticId && isset($response['body']['contact']['id'])) {
            $mauticId = (int) $response['body']['contact']['id'];
        }

        $leadResult = $this->upsert_perfex_lead_from_google_row($row);
        if (!$leadResult['ok']) {
            return ['ok' => false, 'message' => $leadResult['message']];
        }

        if ($mauticId) {
            $this->upsert_map([
                'mautic_contact_id' => $mauticId,
                'perfex_rel_type' => 'lead',
                'perfex_rel_id' => (int) $leadResult['lead_id'],
                'email' => $row['email'],
                'mautic_modified_at' => date('Y-m-d H:i:s'),
                'perfex_modified_at' => date('Y-m-d H:i:s'),
                'last_synced_at' => date('Y-m-d H:i:s'),
            ]);
        }

        return [
            'ok' => true,
            'message' => ($existing ? 'updated' : 'created') . ' Mautic contact and Perfex lead',
            'mautic_contact_id' => $mauticId,
            'perfex_lead_id' => (int) $leadResult['lead_id'],
        ];
    }

    private function upsert_perfex_lead_from_google_row(array $row): array
    {
        $this->load->model('leads_model');
        $existing = $this->db
            ->where('email', $row['email'])
            ->get(db_prefix() . 'leads')
            ->row_array();

        $description = trim(implode("\n", array_filter([
            'Source: Google Maps import',
            $row['google_maps_url'] !== '' ? 'Google Maps: ' . $row['google_maps_url'] : '',
            $row['google_rating'] !== '' ? 'Google rating: ' . $row['google_rating'] : '',
            $row['google_reviews_count'] !== '' ? 'Google reviews: ' . $row['google_reviews_count'] : '',
            $row['source_dataset'] !== '' ? 'Dataset: ' . $row['source_dataset'] : '',
        ])));

        $data = [
            'source' => (string) get_option('mautic_bridge_default_lead_source'),
            'status' => (string) get_option('mautic_bridge_default_lead_status'),
            'assigned' => (string) get_option('mautic_bridge_default_assigned_staff'),
            'name' => $row['name'] ?: ($row['company'] ?: $row['email']),
            'email' => $row['email'],
            'phonenumber' => $row['phone'],
            'company' => $row['company'],
            'city' => $row['city'],
            'country' => 0,
            'website' => $row['website'],
            'description' => $description,
            'is_public' => 0,
            'tags' => $row['tags'],
        ];

        if ($existing) {
            $leadId = (int) $existing['id'];
            $updated = $this->leads_model->update($data, $leadId);
            return ['ok' => true, 'message' => $updated ? 'lead updated' : 'lead unchanged', 'lead_id' => $leadId];
        }

        $leadId = (int) $this->leads_model->add($data);
        if ($leadId <= 0) {
            return ['ok' => false, 'message' => 'failed to create Perfex lead'];
        }

        return ['ok' => true, 'message' => 'lead created', 'lead_id' => $leadId];
    }

    private function find_mautic_contact_by_email(string $email): int
    {
        $response = $this->call_mautic_api('contacts', 'GET', [
            'search' => 'email:' . $email,
            'limit' => 5,
        ]);
        if (!$response['ok']) {
            return 0;
        }

        $contacts = $response['body']['contacts'] ?? [];
        foreach ($contacts as $contact) {
            $contactEmail = $contact['fields']['core']['email']['value'] ?? ($contact['email'] ?? '');
            if (strtolower((string) $contactEmail) === strtolower($email)) {
                return (int) ($contact['id'] ?? 0);
            }
        }

        return 0;
    }

    private function mautic_payload_from_google_row(array $row): array
    {
        return array_filter([
            'firstname' => $row['firstname'],
            'lastname' => $row['lastname'] ?: $row['name'],
            'email' => $row['email'],
            'phone' => $row['phone'],
            'mobile' => $row['phone'],
            'company' => $row['company'],
            'city' => $row['city'],
            'country' => $row['country'],
            'website' => $row['website'],
            'facebook' => $row['facebook'],
            'instagram' => $row['instagram'],
            'linkedin' => $row['linkedin'],
            'twitter' => $row['twitter'],
            'tags' => array_values(array_filter(array_map('trim', explode(',', (string) $row['tags'])))),
        ], static function ($value) {
            return $value !== null && $value !== '' && $value !== [];
        });
    }

    private function mark_google_import_row(int $rowId, string $status, string $message = '', $mauticId = null, $perfexId = null): void
    {
        $this->db->where('id', $rowId)->update(db_prefix() . $this->importRowTable, [
            'status' => $status,
            'message' => $message,
            'mautic_contact_id' => $mauticId,
            'perfex_lead_id' => $perfexId,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function refresh_google_import_job_counts(int $jobId): array
    {
        $counts = [
            'pending_rows' => 0,
            'processed_rows' => 0,
            'success_rows' => 0,
            'skipped_rows' => 0,
            'failed_rows' => 0,
        ];
        $rows = $this->db
            ->select('status, COUNT(*) as total')
            ->where('job_id', $jobId)
            ->group_by('status')
            ->get(db_prefix() . $this->importRowTable)
            ->result_array();
        foreach ($rows as $row) {
            $status = (string) $row['status'];
            $total = (int) $row['total'];
            if ($status === 'pending') {
                $counts['pending_rows'] = $total;
            } elseif ($status === 'done') {
                $counts['success_rows'] = $total;
            } elseif ($status === 'skipped') {
                $counts['skipped_rows'] = $total;
            } elseif ($status === 'failed') {
                $counts['failed_rows'] = $total;
            }
        }
        $counts['processed_rows'] = $counts['success_rows'] + $counts['skipped_rows'] + $counts['failed_rows'];
        $status = $counts['pending_rows'] > 0 ? 'processing' : 'done';
        $this->db->where('id', $jobId)->update(db_prefix() . $this->importJobTable, $counts + [
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $job = $this->db->where('id', $jobId)->get(db_prefix() . $this->importJobTable)->row_array();
        return [
            'id' => $jobId,
            'status' => $status,
            'total_rows' => (int) ($job['total_rows'] ?? 0),
        ] + $counts;
    }

    private function parse_google_import_json(string $raw): array
    {
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            return ['ok' => false, 'message' => 'Invalid JSON file.'];
        }

        if (isset($data[0]) && is_array($data[0])) {
            return ['ok' => true, 'rows' => $data];
        }

        return ['ok' => true, 'rows' => [$data]];
    }

    private function parse_google_import_csv(string $raw): array
    {
        $handle = fopen('php://temp', 'r+');
        fwrite($handle, $raw);
        rewind($handle);
        $headers = fgetcsv($handle);
        if (!is_array($headers) || empty($headers)) {
            return ['ok' => false, 'message' => 'CSV header row not found.'];
        }

        $rows = [];
        while (($values = fgetcsv($handle)) !== false) {
            $row = [];
            foreach ($headers as $i => $header) {
                $row[(string) $header] = $values[$i] ?? '';
            }
            $rows[] = $row;
        }
        fclose($handle);

        return ['ok' => true, 'rows' => $rows];
    }

    private function google_import_columns(array $rows): array
    {
        $columns = [];
        foreach (array_slice($rows, 0, 50) as $row) {
            if (!is_array($row)) {
                continue;
            }
            foreach ($row as $key => $value) {
                if (!in_array((string) $key, $columns, true)) {
                    $columns[] = (string) $key;
                }
            }
        }

        sort($columns);
        return $columns;
    }

    private function suggest_google_import_mapping(array $columns): array
    {
        $aliases = [
            'name' => ['name', 'title', 'business_name', 'company'],
            'company' => ['company', 'companyname', 'title', 'business_name', 'name'],
            'emails' => ['emails', 'email', 'companyemail'],
            'phone' => ['phone', 'mobile', 'phonenumber', 'companyphone'],
            'city' => ['city', 'companycity'],
            'country' => ['country', 'companycountry'],
            'website' => ['website', 'companywebsite'],
            'facebook' => ['facebook', 'facebooks'],
            'instagram' => ['instagram', 'instagrams'],
            'linkedin' => ['linkedin', 'linkedins', 'linkedIns'],
            'twitter' => ['twitter', 'twitters'],
            'google_maps_url' => ['url', 'google_maps_url', 'maps_url'],
            'google_rating' => ['totalscore', 'totalScore', 'rating', 'google_rating'],
            'google_reviews_count' => ['reviewscount', 'reviewsCount', 'google_reviews_count'],
            'google_image_url' => ['imageurl', 'imageUrl', 'google_image_url'],
        ];

        $lower = [];
        foreach ($columns as $column) {
            $lower[strtolower($column)] = $column;
        }

        $mapping = [];
        foreach ($aliases as $target => $sourceAliases) {
            $mapping[$target] = '';
            foreach ($sourceAliases as $alias) {
                $key = strtolower($alias);
                if (isset($lower[$key])) {
                    $mapping[$target] = $lower[$key];
                    break;
                }
            }
        }

        return $mapping;
    }

    private function normalize_google_import_row(array $row, array $mapping, string $emailOverride = ''): array
    {
        $email = $emailOverride !== '' ? $emailOverride : $this->first_valid_email($this->mapped_google_value($row, $mapping['emails'] ?? ''));
        $name = $this->clean_google_scalar($this->mapped_google_value($row, $mapping['name'] ?? ''));
        $company = $this->clean_google_scalar($this->mapped_google_value($row, $mapping['company'] ?? ''));
        $phone = $this->clean_google_scalar($this->mapped_google_value($row, $mapping['phone'] ?? ''));
        $city = $this->clean_google_scalar($this->mapped_google_value($row, $mapping['city'] ?? ''));

        return [
            'email' => strtolower($email),
            'firstname' => '',
            'lastname' => $name ?: $company,
            'name' => $name ?: ($company ?: $email),
            'company' => $company ?: $name,
            'phone' => $phone,
            'city' => $city ?: 'Tây Ninh',
            'country' => $this->clean_google_scalar($this->mapped_google_value($row, $mapping['country'] ?? '')) ?: 'Vietnam',
            'website' => $this->short_mautic_text($this->clean_google_scalar($this->mapped_google_value($row, $mapping['website'] ?? ''))),
            'facebook' => $this->short_mautic_text($this->first_google_value($this->mapped_google_value($row, $mapping['facebook'] ?? ''))),
            'instagram' => $this->short_mautic_text($this->first_google_value($this->mapped_google_value($row, $mapping['instagram'] ?? ''))),
            'linkedin' => $this->short_mautic_text($this->first_google_value($this->mapped_google_value($row, $mapping['linkedin'] ?? ''))),
            'twitter' => $this->short_mautic_text($this->first_google_value($this->mapped_google_value($row, $mapping['twitter'] ?? ''))),
            'google_maps_url' => $this->clean_google_scalar($this->mapped_google_value($row, $mapping['google_maps_url'] ?? '')),
            'google_rating' => $this->clean_google_scalar($this->mapped_google_value($row, $mapping['google_rating'] ?? '')),
            'google_reviews_count' => $this->clean_google_scalar($this->mapped_google_value($row, $mapping['google_reviews_count'] ?? '')),
            'google_image_url' => $this->clean_google_scalar($this->mapped_google_value($row, $mapping['google_image_url'] ?? '')),
            'source_dataset' => '',
            'tags' => 'source:google_maps,location:tay_ninh',
        ];
    }

    private function mapped_google_value(array $row, string $column)
    {
        return $column !== '' && array_key_exists($column, $row) ? $row[$column] : '';
    }

    private function clean_google_scalar($value): string
    {
        if (is_array($value)) {
            $value = $this->first_google_value($value);
        }

        return trim((string) $value);
    }

    private function first_google_value($value): string
    {
        if (is_array($value)) {
            foreach ($value as $item) {
                $item = trim((string) $item);
                if ($item !== '') {
                    return $item;
                }
            }
            return '';
        }

        return trim((string) $value);
    }

    private function first_valid_email($value): string
    {
        $values = $this->valid_google_emails($value);
        return $values[0] ?? '';
    }

    private function valid_google_emails($value): array
    {
        $values = is_array($value) ? $value : preg_split('/[|,;\\s]+/', (string) $value);
        $emails = [];
        foreach ($values as $item) {
            $email = trim((string) $item);
            $lower = strtolower($email);
            if (preg_match('/^[^\s@<>\/]+@[^\s@<>\/]+\.[^\s@<>\/]+$/i', $email)
                && !in_array($lower, ['your@email.com', 'test@example.com'], true)
                && substr($lower, -7) !== 'hotline') {
                $emails[$lower] = $lower;
            }
        }

        return array_values($emails);
    }

    private function short_mautic_text(string $value): string
    {
        if (strlen($value) <= 64) {
            return $value;
        }

        $parts = parse_url($value);
        if (is_array($parts) && !empty($parts['host'])) {
            $short = ($parts['scheme'] ?? 'https') . '://' . $parts['host'] . '/';
            return strlen($short) <= 64 ? $short : '';
        }

        return mb_substr($value, 0, 64);
    }

    public function get_project_hq_summary(): array
    {
        $mapTable = db_prefix() . $this->projectMapTable;
        $objectTable = db_prefix() . $this->projectObjectTable;
        $logTable = db_prefix() . $this->projectLogTable;

        return [
            'projects' => (int) $this->db->count_all_results($mapTable),
            'objects' => (int) $this->db->count_all_results($objectTable),
            'open_issue_tasks' => (int) $this->db
                ->where('object_type', 'issue_task')
                ->count_all_results($objectTable),
            'latest_log' => $this->db
                ->order_by('id', 'DESC')
                ->get($logTable)
                ->row_array(),
        ];
    }

    public function get_project_hq_list(): array
    {
        return $this->db
            ->select('pm.*, p.name as perfex_project_name, p.status as perfex_project_status, p.progress as perfex_project_progress, c.company as customer_name')
            ->from(db_prefix() . $this->projectMapTable . ' as pm')
            ->join(db_prefix() . 'projects as p', 'p.id = pm.perfex_project_id', 'left')
            ->join(db_prefix() . 'clients as c', 'c.userid = p.clientid', 'left')
            ->order_by('pm.id', 'DESC')
            ->get()
            ->result_array();
    }

    public function get_project_hq_map(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $row = $this->db
            ->select('pm.*, p.name as perfex_project_name, p.status as perfex_project_status, p.progress as perfex_project_progress, p.clientid, p.start_date, p.deadline, c.company as customer_name')
            ->from(db_prefix() . $this->projectMapTable . ' as pm')
            ->join(db_prefix() . 'projects as p', 'p.id = pm.perfex_project_id', 'left')
            ->join(db_prefix() . 'clients as c', 'c.userid = p.clientid', 'left')
            ->where('pm.id', $id)
            ->get()
            ->row_array();

        return $row ?: null;
    }

    public function get_project_hq_for_perfex_project(int $projectId): array
    {
        if ($projectId <= 0) {
            return [];
        }

        return $this->db
            ->where('perfex_project_id', $projectId)
            ->order_by('id', 'DESC')
            ->get(db_prefix() . $this->projectMapTable)
            ->result_array();
    }

    public function get_project_hq_objects(int $mapId): array
    {
        return $this->db
            ->where('project_map_id', $mapId)
            ->order_by('object_type', 'ASC')
            ->order_by('object_name', 'ASC')
            ->get(db_prefix() . $this->projectObjectTable)
            ->result_array();
    }

    public function get_project_hq_logs(int $mapId, int $limit = 50): array
    {
        return $this->db
            ->where('project_map_id', $mapId)
            ->order_by('id', 'DESC')
            ->limit(max(1, min(200, $limit)))
            ->get(db_prefix() . $this->projectLogTable)
            ->result_array();
    }

    public function get_project_hq_templates(bool $activeOnly = false): array
    {
        if ($activeOnly) {
            $this->db->where('active', 1);
        }

        return $this->db
            ->order_by('is_default', 'DESC')
            ->order_by('name', 'ASC')
            ->get(db_prefix() . $this->projectTemplateTable)
            ->result_array();
    }

    public function get_project_hq_template(int $id = 0): ?array
    {
        if ($id > 0) {
            $template = $this->db
                ->where('id', $id)
                ->get(db_prefix() . $this->projectTemplateTable)
                ->row_array();
            if ($template) {
                return $template;
            }
        }

        $template = $this->db
            ->where('is_default', 1)
            ->where('active', 1)
            ->get(db_prefix() . $this->projectTemplateTable)
            ->row_array();

        return $template ?: null;
    }

    public function get_project_hq_form_options(): array
    {
        return [
            'clients' => $this->db
                ->select('userid, company')
                ->where('active', 1)
                ->order_by('company', 'ASC')
                ->get(db_prefix() . 'clients')
                ->result_array(),
            'projects' => $this->db
                ->select('id, name')
                ->order_by('id', 'DESC')
                ->limit(500)
                ->get(db_prefix() . 'projects')
                ->result_array(),
            'campaigns' => $this->get_mautic_campaign_choices(),
            'segments' => $this->get_mautic_segment_choices(),
            'templates' => $this->get_project_hq_templates(true),
            'staff' => $this->db
                ->select('staffid, firstname, lastname')
                ->where('active', 1)
                ->order_by('firstname', 'ASC')
                ->get(db_prefix() . 'staff')
                ->result_array(),
        ];
    }

    public function get_mautic_campaign_choices(): array
    {
        $response = $this->call_mautic_api('campaigns', 'GET', ['limit' => 200, 'minimal' => 1]);
        if (empty($response['ok'])) {
            return [];
        }

        $campaigns = $response['body']['campaigns'] ?? [];
        $choices = [];
        foreach ($campaigns as $campaign) {
            if (!is_array($campaign) || empty($campaign['id'])) {
                continue;
            }
            $choices[] = [
                'id' => (int) $campaign['id'],
                'name' => (string) ($campaign['name'] ?? ('Campaign #' . (int) $campaign['id'])),
                'alias' => (string) ($campaign['alias'] ?? ''),
            ];
        }

        return $choices;
    }

    public function get_mautic_segment_choices(): array
    {
        $response = $this->call_mautic_api('segments', 'GET', ['limit' => 200, 'minimal' => 1]);
        if (empty($response['ok'])) {
            return [];
        }

        $segments = $response['body']['lists'] ?? ($response['body']['segments'] ?? []);
        $choices = [];
        foreach ($segments as $segment) {
            if (!is_array($segment) || empty($segment['id'])) {
                continue;
            }
            $choices[] = [
                'id' => (int) $segment['id'],
                'name' => (string) ($segment['name'] ?? ('Segment #' . (int) $segment['id'])),
                'alias' => (string) ($segment['alias'] ?? ''),
            ];
        }

        return $choices;
    }

    public function create_project_hq(array $data): array
    {
        $mode = (string) ($data['project_mode'] ?? 'existing');
        $projectId = (int) ($data['perfex_project_id'] ?? 0);
        $campaignId = (int) ($data['mautic_campaign_id'] ?? 0);
        $segmentId = (int) ($data['mautic_segment_id'] ?? 0);
        $initiativeName = trim((string) ($data['initiative_name'] ?? ''));

        if ($initiativeName === '') {
            return ['success' => false, 'message' => 'Initiative name is required.'];
        }

        if ($mode === 'new') {
            $clientId = (int) ($data['clientid'] ?? 0);
            if ($clientId <= 0) {
                return ['success' => false, 'message' => 'Customer is required for a new project.'];
            }

            $this->load->model('projects_model');
            $projectId = (int) $this->projects_model->add([
                'name' => $initiativeName,
                'clientid' => $clientId,
                'billing_type' => 1,
                'status' => 1,
                'start_date' => $data['start_date'] ?? date('Y-m-d'),
                'deadline' => $data['deadline'] ?? '',
                'progress_from_tasks' => 1,
                'project_members' => !empty($data['project_members']) && is_array($data['project_members']) ? $data['project_members'] : [get_staff_user_id()],
                'settings' => $this->project_hq_default_project_settings(),
                'tags' => 'mautic-hq',
            ]);

            if ($projectId <= 0) {
                return ['success' => false, 'message' => 'Unable to create Perfex project.'];
            }
        }

        if ($projectId <= 0) {
            return ['success' => false, 'message' => 'Perfex project is required.'];
        }

        $campaign = $this->find_choice($this->get_mautic_campaign_choices(), $campaignId);
        $segment = $this->find_choice($this->get_mautic_segment_choices(), $segmentId);

        $existing = $this->find_project_hq_map($projectId, $campaignId, $segmentId);
        $now = date('Y-m-d H:i:s');
        $row = [
            'perfex_project_id' => $projectId,
            'mautic_campaign_id' => $campaignId > 0 ? $campaignId : null,
            'mautic_segment_id' => $segmentId > 0 ? $segmentId : null,
            'initiative_name' => $initiativeName,
            'mautic_campaign_name' => $campaign['name'] ?? null,
            'mautic_campaign_alias' => $campaign['alias'] ?? null,
            'mautic_segment_name' => $segment['name'] ?? null,
            'mautic_segment_alias' => $segment['alias'] ?? null,
            'status' => 'active',
            'updated_at' => $now,
        ];

        if ($existing) {
            $this->db->where('id', (int) $existing['id'])->update(db_prefix() . $this->projectMapTable, $row);
            $mapId = (int) $existing['id'];
            $this->project_hq_log($mapId, 'info', 'project_hq_updated', 'Project HQ link updated.', $row);
        } else {
            $row['created_at'] = $now;
            $this->db->insert(db_prefix() . $this->projectMapTable, $row);
            $mapId = (int) $this->db->insert_id();
            $this->project_hq_log($mapId, 'info', 'project_hq_created', 'Project HQ link created.', $row);
        }

        return ['success' => true, 'message' => 'Project HQ saved.', 'project_map_id' => $mapId, 'project_id' => $projectId];
    }

    public function refresh_project_hq_snapshot(int $mapId): array
    {
        $map = $this->get_project_hq_map($mapId);
        if (!$map) {
            return ['success' => false, 'message' => 'Project HQ map not found.'];
        }

        $warnings = [];
        $snapshot = [
            'campaign' => null,
            'segment' => null,
            'catalog_counts' => [],
            'refreshed_at' => date('Y-m-d H:i:s'),
        ];

        if (!empty($map['mautic_campaign_id'])) {
            $response = $this->call_mautic_api('campaigns/' . (int) $map['mautic_campaign_id'], 'GET');
            if (!empty($response['ok'])) {
                $campaign = $response['body']['campaign'] ?? $response['body'];
                $snapshot['campaign'] = is_array($campaign) ? $campaign : null;
                if (is_array($campaign)) {
                    $this->upsert_project_object($mapId, (int) $map['perfex_project_id'], 'campaign', (string) $map['mautic_campaign_id'], (string) ($campaign['name'] ?? $map['mautic_campaign_name']), $this->mautic_object_url('campaign', (int) $map['mautic_campaign_id']), $campaign);
                }
            } else {
                $warnings[] = 'Campaign API: ' . $response['message'];
            }
        }

        if (!empty($map['mautic_segment_id'])) {
            $response = $this->call_mautic_api('segments/' . (int) $map['mautic_segment_id'], 'GET');
            if (!empty($response['ok'])) {
                $segment = $response['body']['list'] ?? ($response['body']['segment'] ?? $response['body']);
                $snapshot['segment'] = is_array($segment) ? $segment : null;
                if (is_array($segment)) {
                    $this->upsert_project_object($mapId, (int) $map['perfex_project_id'], 'segment', (string) $map['mautic_segment_id'], (string) ($segment['name'] ?? $map['mautic_segment_name']), $this->mautic_object_url('segment', (int) $map['mautic_segment_id']), $segment);
                }
            } else {
                $warnings[] = 'Segment API: ' . $response['message'];
            }
        }

        foreach ($this->project_hq_catalog_endpoints() as $type => $config) {
            $response = $this->call_mautic_api($config['endpoint'], 'GET', ['limit' => 200, 'minimal' => 1]);
            if (empty($response['ok'])) {
                $warnings[] = ucfirst($type) . ' API: ' . $response['message'];
                continue;
            }
            $items = $response['body'][$config['body_key']] ?? [];
            $snapshot['catalog_counts'][$type] = is_array($items) ? count($items) : 0;
            foreach ($items as $item) {
                if (!is_array($item) || empty($item['id'])) {
                    continue;
                }
                $this->upsert_project_object($mapId, (int) $map['perfex_project_id'], $type, (string) $item['id'], (string) ($item['name'] ?? ($item['title'] ?? ucfirst($type) . ' #' . (int) $item['id'])), $this->mautic_object_url($type, (int) $item['id']), $item);
            }
        }

        $this->db->where('id', $mapId)->update(db_prefix() . $this->projectMapTable, [
            'snapshot_json' => json_encode($snapshot, JSON_UNESCAPED_UNICODE),
            'last_synced_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $this->project_hq_log($mapId, empty($warnings) ? 'info' : 'warning', 'snapshot_refreshed', 'Mautic snapshot refreshed.', ['warnings' => $warnings]);

        return ['success' => true, 'message' => empty($warnings) ? 'Snapshot refreshed.' : 'Snapshot refreshed with warnings.', 'warnings' => $warnings];
    }

    public function generate_project_hq_tasks(int $mapId, int $templateId = 0): array
    {
        $map = $this->get_project_hq_map($mapId);
        if (!$map) {
            return ['success' => false, 'message' => 'Project HQ map not found.'];
        }

        $template = $this->get_project_hq_template($templateId);
        if (!$template) {
            return ['success' => false, 'message' => 'No active Project HQ template found.'];
        }

        $payload = json_decode((string) $template['payload_json'], true);
        if (!is_array($payload)) {
            return ['success' => false, 'message' => 'Template payload is invalid.'];
        }

        $this->load->model('projects_model');
        $this->load->model('tasks_model');

        $projectId = (int) $map['perfex_project_id'];
        $milestoneIds = [];
        $created = 0;
        $updated = 0;
        $checklists = 0;

        foreach (($payload['milestones'] ?? []) as $milestone) {
            if (empty($milestone['key']) || empty($milestone['name'])) {
                continue;
            }
            $milestoneId = $this->ensure_project_hq_milestone($mapId, $projectId, $milestone);
            if ($milestoneId > 0) {
                $milestoneIds[(string) $milestone['key']] = $milestoneId;
            }
        }

        foreach (($payload['tasks'] ?? []) as $task) {
            if (empty($task['key']) || empty($task['name'])) {
                continue;
            }
            $externalKey = 'mautic_project:' . $mapId . ':task:' . (string) $task['key'];
            $existing = $this->get_project_object_by_key($externalKey);
            $milestoneId = $milestoneIds[(string) ($task['milestone'] ?? '')] ?? 0;
            $taskData = [
                'name' => (string) $task['name'],
                'description' => $this->project_hq_task_description($map, $task),
                'startdate' => $this->offset_date((int) ($task['offset_days'] ?? 0), $map['start_date'] ?? null),
                'duedate' => $this->offset_date((int) ($task['due_offset_days'] ?? 3), $map['start_date'] ?? null),
                'rel_type' => 'project',
                'rel_id' => $projectId,
                'priority' => (int) ($task['priority'] ?? 2),
                'milestone' => $milestoneId,
                'visible_to_client' => 0,
                'billable' => 0,
                'withDefaultAssignee' => false,
                'tags' => 'mautic-hq',
            ];
            if (!empty($task['assignees']) && is_array($task['assignees'])) {
                $taskData['assignees'] = $task['assignees'];
            }

            if ($existing && !empty($existing['object_id']) && $this->task_exists((int) $existing['object_id'])) {
                $this->update_project_hq_task((int) $existing['object_id'], $taskData);
                $taskId = (int) $existing['object_id'];
                $updated++;
            } else {
                $taskId = (int) $this->tasks_model->add($taskData);
                if ($taskId <= 0) {
                    continue;
                }
                $created++;
                $this->upsert_project_object($mapId, $projectId, 'perfex_task', (string) $taskId, (string) $task['name'], admin_url('tasks/view/' . $taskId), ['external_key' => $externalKey], $externalKey);
            }

            $checklists += $this->ensure_project_hq_checklist($taskId, $task['checklist'] ?? []);
        }

        $this->project_hq_log($mapId, 'info', 'tasks_generated', 'Rollout tasks generated from template.', [
            'template_id' => (int) $template['id'],
            'created' => $created,
            'updated' => $updated,
            'checklists' => $checklists,
        ]);

        return ['success' => true, 'message' => 'Rollout tasks generated.', 'created' => $created, 'updated' => $updated, 'checklists' => $checklists];
    }

    public function create_project_hq_issue_task(int $mapId, string $issueKey, string $title, string $description = ''): array
    {
        $map = $this->get_project_hq_map($mapId);
        if (!$map) {
            return ['success' => false, 'message' => 'Project HQ map not found.'];
        }

        $issueKey = preg_replace('/[^a-zA-Z0-9_:-]+/', '_', trim($issueKey));
        if ($issueKey === '' || trim($title) === '') {
            return ['success' => false, 'message' => 'Issue key and title are required.'];
        }

        $externalKey = 'mautic_project:' . $mapId . ':issue:' . $issueKey;
        $existing = $this->get_project_object_by_key($externalKey);
        if ($existing && !empty($existing['object_id']) && $this->task_exists((int) $existing['object_id'])) {
            return ['success' => true, 'message' => 'Issue task already exists.', 'task_id' => (int) $existing['object_id'], 'duplicate' => true];
        }

        $this->load->model('tasks_model');
        $taskId = (int) $this->tasks_model->add([
            'name' => '[Mautic Issue] ' . $title,
            'description' => $description,
            'startdate' => date('Y-m-d'),
            'duedate' => date('Y-m-d', strtotime('+2 days')),
            'rel_type' => 'project',
            'rel_id' => (int) $map['perfex_project_id'],
            'priority' => 3,
            'visible_to_client' => 0,
            'billable' => 0,
            'withDefaultAssignee' => false,
            'tags' => 'mautic-hq,mautic-issue',
        ]);

        if ($taskId <= 0) {
            return ['success' => false, 'message' => 'Unable to create issue task.'];
        }

        $this->upsert_project_object($mapId, (int) $map['perfex_project_id'], 'issue_task', (string) $taskId, '[Mautic Issue] ' . $title, admin_url('tasks/view/' . $taskId), ['external_key' => $externalKey, 'issue_key' => $issueKey], $externalKey);
        $this->project_hq_log($mapId, 'warning', 'issue_task_created', 'Manual Mautic issue task created.', ['task_id' => $taskId, 'issue_key' => $issueKey]);

        return ['success' => true, 'message' => 'Issue task created.', 'task_id' => $taskId];
    }

    private function ensure_project_hq_tables(): void
    {
        $mapTable = db_prefix() . $this->projectMapTable;
        if (!$this->db->table_exists($mapTable)) {
            $this->db->query('CREATE TABLE `' . $mapTable . '` (
              `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
              `perfex_project_id` BIGINT UNSIGNED NOT NULL,
              `mautic_campaign_id` BIGINT UNSIGNED NULL,
              `mautic_segment_id` BIGINT UNSIGNED NULL,
              `initiative_name` VARCHAR(191) NOT NULL,
              `mautic_campaign_name` VARCHAR(191) NULL,
              `mautic_campaign_alias` VARCHAR(191) NULL,
              `mautic_segment_name` VARCHAR(191) NULL,
              `mautic_segment_alias` VARCHAR(191) NULL,
              `status` VARCHAR(30) NOT NULL DEFAULT "active",
              `snapshot_json` LONGTEXT NULL,
              `last_synced_at` DATETIME NULL,
              `created_at` DATETIME NOT NULL,
              `updated_at` DATETIME NOT NULL,
              PRIMARY KEY (`id`),
              KEY `idx_mb_project_perfex` (`perfex_project_id`),
              KEY `idx_mb_project_campaign` (`mautic_campaign_id`),
              KEY `idx_mb_project_segment` (`mautic_segment_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;');
        }

        $objectTable = db_prefix() . $this->projectObjectTable;
        if (!$this->db->table_exists($objectTable)) {
            $this->db->query('CREATE TABLE `' . $objectTable . '` (
              `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
              `project_map_id` BIGINT UNSIGNED NOT NULL,
              `perfex_project_id` BIGINT UNSIGNED NOT NULL,
              `object_type` VARCHAR(40) NOT NULL,
              `object_id` VARCHAR(64) NULL,
              `object_name` VARCHAR(191) NULL,
              `external_key` VARCHAR(191) NOT NULL,
              `object_url` TEXT NULL,
              `status` VARCHAR(30) NULL,
              `payload_json` LONGTEXT NULL,
              `last_seen_at` DATETIME NULL,
              `created_at` DATETIME NOT NULL,
              `updated_at` DATETIME NOT NULL,
              PRIMARY KEY (`id`),
              UNIQUE KEY `uniq_mb_project_object_key` (`external_key`),
              KEY `idx_mb_project_object_map` (`project_map_id`),
              KEY `idx_mb_project_object_type` (`object_type`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;');
        }

        $logTable = db_prefix() . $this->projectLogTable;
        if (!$this->db->table_exists($logTable)) {
            $this->db->query('CREATE TABLE `' . $logTable . '` (
              `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
              `project_map_id` BIGINT UNSIGNED NULL,
              `level` VARCHAR(20) NOT NULL,
              `event_type` VARCHAR(60) NOT NULL,
              `message` VARCHAR(255) NOT NULL,
              `context_json` LONGTEXT NULL,
              `created_at` DATETIME NOT NULL,
              PRIMARY KEY (`id`),
              KEY `idx_mb_project_log_map` (`project_map_id`),
              KEY `idx_mb_project_log_level` (`level`),
              KEY `idx_mb_project_log_created` (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;');
        }

        $templateTable = db_prefix() . $this->projectTemplateTable;
        if (!$this->db->table_exists($templateTable)) {
            $this->db->query('CREATE TABLE `' . $templateTable . '` (
              `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
              `template_key` VARCHAR(80) NOT NULL,
              `name` VARCHAR(191) NOT NULL,
              `description` TEXT NULL,
              `payload_json` LONGTEXT NOT NULL,
              `is_default` TINYINT(1) NOT NULL DEFAULT 0,
              `active` TINYINT(1) NOT NULL DEFAULT 1,
              `created_at` DATETIME NOT NULL,
              `updated_at` DATETIME NOT NULL,
              PRIMARY KEY (`id`),
              UNIQUE KEY `uniq_mb_project_template_key` (`template_key`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;');
        }

        $this->seed_project_hq_templates();
    }

    private function seed_project_hq_templates(): void
    {
        $table = db_prefix() . $this->projectTemplateTable;
        $exists = $this->db
            ->where('template_key', 'default_mautic_rollout')
            ->get($table)
            ->row_array();

        $payload = $this->project_hq_default_template_payload();
        $row = [
            'template_key' => 'default_mautic_rollout',
            'name' => 'Default Mautic Campaign Rollout',
            'description' => 'Default milestones, tasks, and QA checklist for Mautic campaign implementation.',
            'payload_json' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            'is_default' => 1,
            'active' => 1,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if ($exists) {
            $this->db->where('id', (int) $exists['id'])->update($table, $row);
            return;
        }

        $row['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert($table, $row);
    }

    private function project_hq_default_template_payload(): array
    {
        return [
            'milestones' => [
                ['key' => 'strategy', 'name' => 'Strategy & Scope', 'offset_days' => 0, 'due_offset_days' => 2, 'color' => '#2563eb'],
                ['key' => 'setup', 'name' => 'Setup & Build', 'offset_days' => 2, 'due_offset_days' => 7, 'color' => '#16a34a'],
                ['key' => 'qa_launch', 'name' => 'QA & Launch', 'offset_days' => 7, 'due_offset_days' => 10, 'color' => '#f59e0b'],
                ['key' => 'monitoring', 'name' => 'Monitoring & Reporting', 'offset_days' => 10, 'due_offset_days' => 17, 'color' => '#7c3aed'],
            ],
            'tasks' => [
                [
                    'key' => 'strategy_scope',
                    'name' => 'Strategy & scope',
                    'milestone' => 'strategy',
                    'offset_days' => 0,
                    'due_offset_days' => 2,
                    'checklist' => ['Define business objective', 'Confirm target segments', 'Confirm offer/message', 'Confirm launch owner'],
                ],
                [
                    'key' => 'segment_setup',
                    'name' => 'Segment setup',
                    'milestone' => 'setup',
                    'offset_days' => 1,
                    'due_offset_days' => 4,
                    'checklist' => ['Create or verify segment in Mautic', 'Check segment filters', 'Verify contact count', 'Attach segment link in Project HQ'],
                ],
                [
                    'key' => 'asset_form_page_setup',
                    'name' => 'Asset, form, landing page setup',
                    'milestone' => 'setup',
                    'offset_days' => 2,
                    'due_offset_days' => 6,
                    'checklist' => ['Prepare assets', 'Create or verify forms', 'Create or verify landing pages', 'Run submit test'],
                ],
                [
                    'key' => 'email_channel_setup',
                    'name' => 'Email/channel setup',
                    'milestone' => 'setup',
                    'offset_days' => 3,
                    'due_offset_days' => 7,
                    'checklist' => ['Prepare email content', 'Check sender/reply-to', 'Send test email', 'Verify tracked links'],
                ],
                [
                    'key' => 'campaign_flow_build',
                    'name' => 'Campaign flow build',
                    'milestone' => 'setup',
                    'offset_days' => 4,
                    'due_offset_days' => 8,
                    'checklist' => ['Build Mautic campaign flow', 'Attach segment/source', 'Configure actions/decisions', 'Keep campaign unpublished until QA'],
                ],
                [
                    'key' => 'qa_tracking_webhook_api',
                    'name' => 'QA tracking, webhook, API',
                    'milestone' => 'qa_launch',
                    'offset_days' => 7,
                    'due_offset_days' => 9,
                    'checklist' => ['Verify tracking script', 'Verify webhook events', 'Verify API connection', 'Confirm Perfex activity receives events'],
                ],
                [
                    'key' => 'launch_checklist',
                    'name' => 'Launch checklist',
                    'milestone' => 'qa_launch',
                    'offset_days' => 9,
                    'due_offset_days' => 10,
                    'checklist' => ['Final QA passed', 'Campaign published', 'First contacts entering flow', 'Owner confirms go-live'],
                ],
                [
                    'key' => 'monitoring_reporting',
                    'name' => 'Monitoring/reporting',
                    'milestone' => 'monitoring',
                    'offset_days' => 10,
                    'due_offset_days' => 17,
                    'checklist' => ['Review segment growth', 'Review email sent/open/click', 'Review form submissions/page hits', 'Create follow-up issue tasks if needed'],
                ],
            ],
        ];
    }

    private function project_hq_default_project_settings(): array
    {
        return [
            'view_tasks' => 1,
            'view_milestones' => 1,
            'view_gantt' => 1,
            'view_timesheets' => 1,
            'view_activity_log' => 1,
            'view_team_members' => 1,
            'available_features' => [
                'project_overview',
                'project_tasks',
                'project_milestones',
                'project_gantt',
                'project_activity',
                'project_team_members',
                'mautic_hq',
            ],
        ];
    }

    private function find_choice(array $choices, int $id): ?array
    {
        foreach ($choices as $choice) {
            if ((int) ($choice['id'] ?? 0) === $id) {
                return $choice;
            }
        }

        return null;
    }

    private function find_project_hq_map(int $projectId, int $campaignId, int $segmentId): ?array
    {
        $this->db->where('perfex_project_id', $projectId);
        if ($campaignId > 0) {
            $this->db->where('mautic_campaign_id', $campaignId);
        } else {
            $this->db->where('mautic_campaign_id IS NULL', null, false);
        }
        if ($segmentId > 0) {
            $this->db->where('mautic_segment_id', $segmentId);
        } else {
            $this->db->where('mautic_segment_id IS NULL', null, false);
        }

        $row = $this->db->get(db_prefix() . $this->projectMapTable)->row_array();
        return $row ?: null;
    }

    private function project_hq_catalog_endpoints(): array
    {
        return [
            'asset' => ['endpoint' => 'assets', 'body_key' => 'assets'],
            'form' => ['endpoint' => 'forms', 'body_key' => 'forms'],
            'page' => ['endpoint' => 'pages', 'body_key' => 'pages'],
            'email' => ['endpoint' => 'emails', 'body_key' => 'emails'],
            'dwc' => ['endpoint' => 'dynamiccontents', 'body_key' => 'dynamicContents'],
        ];
    }

    private function mautic_object_url(string $type, int $id): string
    {
        $base = rtrim((string) get_option('mautic_bridge_base_url'), '/');
        if ($base === '' || $id <= 0) {
            return '';
        }

        $paths = [
            'campaign' => '/s/campaigns/view/',
            'segment' => '/s/segments/view/',
            'asset' => '/s/assets/view/',
            'form' => '/s/forms/view/',
            'page' => '/s/pages/view/',
            'email' => '/s/emails/view/',
            'dwc' => '/s/dwc/view/',
        ];

        return $base . ($paths[$type] ?? '/s/') . $id;
    }

    private function upsert_project_object(int $mapId, int $projectId, string $type, string $objectId, string $name, string $url, array $payload = [], string $externalKey = ''): void
    {
        $externalKey = $externalKey !== '' ? $externalKey : 'mautic_project:' . $mapId . ':' . $type . ':' . $objectId;
        $now = date('Y-m-d H:i:s');
        $row = [
            'project_map_id' => $mapId,
            'perfex_project_id' => $projectId,
            'object_type' => $type,
            'object_id' => $objectId,
            'object_name' => $name,
            'external_key' => $externalKey,
            'object_url' => $url,
            'status' => $this->project_object_status($payload),
            'payload_json' => !empty($payload) ? json_encode($payload, JSON_UNESCAPED_UNICODE) : null,
            'last_seen_at' => $now,
            'updated_at' => $now,
        ];

        $existing = $this->get_project_object_by_key($externalKey);
        if ($existing) {
            $this->db->where('id', (int) $existing['id'])->update(db_prefix() . $this->projectObjectTable, $row);
            return;
        }

        $row['created_at'] = $now;
        $this->db->insert(db_prefix() . $this->projectObjectTable, $row);
    }

    private function get_project_object_by_key(string $externalKey): ?array
    {
        $row = $this->db
            ->where('external_key', $externalKey)
            ->get(db_prefix() . $this->projectObjectTable)
            ->row_array();

        return $row ?: null;
    }

    private function project_object_status(array $payload): ?string
    {
        foreach (['isPublished', 'published', 'status'] as $key) {
            if (array_key_exists($key, $payload)) {
                if (is_bool($payload[$key])) {
                    return $payload[$key] ? 'published' : 'unpublished';
                }
                return (string) $payload[$key];
            }
        }

        return null;
    }

    private function ensure_project_hq_milestone(int $mapId, int $projectId, array $milestone): int
    {
        $externalKey = 'mautic_project:' . $mapId . ':milestone:' . (string) $milestone['key'];
        $existing = $this->get_project_object_by_key($externalKey);
        if ($existing && !empty($existing['object_id'])) {
            $milestoneId = (int) $existing['object_id'];
            $row = $this->db->where('id', $milestoneId)->get(db_prefix() . 'milestones')->row_array();
            if ($row) {
                return $milestoneId;
            }
        }

        $milestoneId = (int) $this->projects_model->add_milestone([
            'name' => (string) $milestone['name'],
            'description' => 'Generated by Mautic Project HQ.',
            'start_date' => $this->offset_date((int) ($milestone['offset_days'] ?? 0)),
            'due_date' => $this->offset_date((int) ($milestone['due_offset_days'] ?? 3)),
            'project_id' => $projectId,
            'milestone_order' => 0,
            'color' => (string) ($milestone['color'] ?? '#2563eb'),
            'hide_from_customer' => 1,
        ]);

        if ($milestoneId > 0) {
            $this->upsert_project_object($mapId, $projectId, 'perfex_milestone', (string) $milestoneId, (string) $milestone['name'], admin_url('projects/view/' . $projectId . '?group=project_milestones'), ['external_key' => $externalKey], $externalKey);
        }

        return $milestoneId;
    }

    private function project_hq_task_description(array $map, array $task): string
    {
        $lines = [
            'Generated by Mautic Project HQ.',
            '',
            'Initiative: ' . (string) $map['initiative_name'],
        ];
        if (!empty($map['mautic_campaign_id'])) {
            $lines[] = 'Mautic campaign: ' . (string) $map['mautic_campaign_name'] . ' (#' . (int) $map['mautic_campaign_id'] . ')';
        }
        if (!empty($map['mautic_segment_id'])) {
            $lines[] = 'Mautic segment: ' . (string) $map['mautic_segment_name'] . ' (#' . (int) $map['mautic_segment_id'] . ')';
        }
        if (!empty($task['description'])) {
            $lines[] = '';
            $lines[] = (string) $task['description'];
        }

        return implode("\n", $lines);
    }

    private function update_project_hq_task(int $taskId, array $taskData): void
    {
        $existing = $this->db->where('id', $taskId)->get(db_prefix() . 'tasks')->row_array();
        if (!$existing || (int) $existing['status'] === 5) {
            return;
        }

        $this->db->where('id', $taskId)->update(db_prefix() . 'tasks', [
            'name' => $taskData['name'],
            'description' => $taskData['description'],
            'startdate' => to_sql_date($taskData['startdate']),
            'duedate' => to_sql_date($taskData['duedate']),
            'milestone' => (int) $taskData['milestone'],
            'priority' => (int) $taskData['priority'],
        ]);
    }

    private function ensure_project_hq_checklist(int $taskId, array $items): int
    {
        $added = 0;
        $order = (int) $this->db
            ->select_max('list_order')
            ->where('taskid', $taskId)
            ->get(db_prefix() . 'task_checklist_items')
            ->row('list_order');

        foreach ($items as $item) {
            $description = trim((string) $item);
            if ($description === '') {
                continue;
            }
            $exists = $this->db
                ->where('taskid', $taskId)
                ->where('description', $description)
                ->get(db_prefix() . 'task_checklist_items')
                ->row_array();
            if ($exists) {
                continue;
            }
            $order++;
            $this->db->insert(db_prefix() . 'task_checklist_items', [
                'taskid' => $taskId,
                'description' => $description,
                'finished' => 0,
                'dateadded' => date('Y-m-d H:i:s'),
                'addedfrom' => get_staff_user_id(),
                'list_order' => $order,
                'assigned' => null,
            ]);
            $added++;
        }

        return $added;
    }

    private function task_exists(int $taskId): bool
    {
        return $taskId > 0 && total_rows(db_prefix() . 'tasks', ['id' => $taskId]) > 0;
    }

    private function offset_date(int $days, ?string $baseDate = null): string
    {
        $base = $baseDate && strtotime($baseDate) ? $baseDate : date('Y-m-d');
        return date('Y-m-d', strtotime($base . ' +' . max(0, $days) . ' days'));
    }

    private function project_hq_log(?int $mapId, string $level, string $eventType, string $message, array $context = []): void
    {
        $this->db->insert(db_prefix() . $this->projectLogTable, [
            'project_map_id' => $mapId,
            'level' => $level,
            'event_type' => $eventType,
            'message' => $message,
            'context_json' => !empty($context) ? json_encode($context, JSON_UNESCAPED_UNICODE) : null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function ensure_import_tables(): void
    {
        $jobTable = db_prefix() . $this->importJobTable;
        if (!$this->db->table_exists($jobTable)) {
            $this->db->query('CREATE TABLE `' . $jobTable . '` (
              `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
              `filename` VARCHAR(255) NOT NULL,
              `source_type` VARCHAR(20) NOT NULL,
              `status` VARCHAR(30) NOT NULL,
              `mapping_json` LONGTEXT NULL,
              `columns_json` LONGTEXT NULL,
              `stats_json` LONGTEXT NULL,
              `total_rows` INT NOT NULL DEFAULT 0,
              `pending_rows` INT NOT NULL DEFAULT 0,
              `processed_rows` INT NOT NULL DEFAULT 0,
              `success_rows` INT NOT NULL DEFAULT 0,
              `skipped_rows` INT NOT NULL DEFAULT 0,
              `failed_rows` INT NOT NULL DEFAULT 0,
              `created_at` DATETIME NOT NULL,
              `updated_at` DATETIME NOT NULL,
              PRIMARY KEY (`id`),
              KEY `idx_mb_import_jobs_status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;');
        }

        $rowTable = db_prefix() . $this->importRowTable;
        if (!$this->db->table_exists($rowTable)) {
            $this->db->query('CREATE TABLE `' . $rowTable . '` (
              `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
              `job_id` BIGINT UNSIGNED NOT NULL,
              `row_number` INT NOT NULL,
              `source_json` LONGTEXT NULL,
              `normalized_json` LONGTEXT NULL,
              `email` VARCHAR(191) NULL,
              `status` VARCHAR(30) NOT NULL,
              `message` TEXT NULL,
              `mautic_contact_id` BIGINT UNSIGNED NULL,
              `perfex_lead_id` BIGINT UNSIGNED NULL,
              `created_at` DATETIME NOT NULL,
              `updated_at` DATETIME NOT NULL,
              PRIMARY KEY (`id`),
              KEY `idx_mb_import_rows_job_status` (`job_id`, `status`),
              KEY `idx_mb_import_rows_email` (`email`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;');
        }
    }

    public function log(string $level, string $message, array $context = []): void
    {
        $this->db->insert(db_prefix() . $this->logTable, [
            'level' => $level,
            'message' => $message,
            'context_json' => !empty($context) ? json_encode($context, JSON_UNESCAPED_UNICODE) : null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
