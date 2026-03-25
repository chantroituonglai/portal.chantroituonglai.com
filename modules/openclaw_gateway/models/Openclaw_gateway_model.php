<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Openclaw_gateway_model extends App_Model
{
    private function t_logs()
    {
        return db_prefix() . 'openclaw_gateway_logs';
    }

    private function t_idem()
    {
        return db_prefix() . 'openclaw_gateway_idempotency';
    }

    private function t_pipeline()
    {
        return db_prefix() . 'openclaw_agent_pipeline_logs';
    }

    private function t_bridge()
    {
        return db_prefix() . 'openclaw_bridge_events';
    }

    public function log_request($row)
    {
        $this->db->insert($this->t_logs(), $row);
        return $this->db->insert_id();
    }

    public function get_audit($requestId)
    {
        return $this->db->where('request_id', $requestId)->order_by('id', 'DESC')->get($this->t_logs())->result_array();
    }

    public function idempotency_get($idempotencyKey, $principalHash, $actionId)
    {
        return $this->db
            ->where('idempotency_key', $idempotencyKey)
            ->where('principal_hash', $principalHash)
            ->where('action_id', $actionId)
            ->get($this->t_idem())
            ->row_array();
    }

    public function idempotency_store($row)
    {
        $this->db->insert($this->t_idem(), $row);
        return $this->db->insert_id();
    }

    public function stats($hours = 24)
    {
        $since = date('Y-m-d H:i:s', time() - ((int) $hours * 3600));
        $rows = $this->db
            ->select('status, COUNT(*) AS total, AVG(latency_ms) AS avg_latency, MAX(latency_ms) AS max_latency')
            ->where('created_at >=', $since)
            ->group_by('status')
            ->get($this->t_logs())
            ->result_array();

        $byAction = $this->db
            ->select('action_id, COUNT(*) AS total, SUM(CASE WHEN status = "success" THEN 1 ELSE 0 END) AS success_total')
            ->where('created_at >=', $since)
            ->group_by('action_id')
            ->order_by('total', 'DESC')
            ->limit(20)
            ->get($this->t_logs())
            ->result_array();

        return ['summary' => $rows, 'top_actions' => $byAction];
    }

    public function pipeline_counts_24h()
    {
        $tbl = $this->t_pipeline();
        if (!$this->db->table_exists($tbl)) {
            return ['total' => 0, 'inbound' => 0, 'outbound' => 0, 'failed' => 0];
        }

        $since = date('Y-m-d H:i:s', time() - 86400);
        $row = $this->db
            ->select('COUNT(*) AS total, SUM(CASE WHEN direction = "inbound" THEN 1 ELSE 0 END) AS inbound, SUM(CASE WHEN direction = "outbound" THEN 1 ELSE 0 END) AS outbound, SUM(CASE WHEN status IN ("failed","error") THEN 1 ELSE 0 END) AS failed', false)
            ->where('created_at >=', $since)
            ->get($tbl)
            ->row_array();

        return [
            'total' => (int) ($row['total'] ?? 0),
            'inbound' => (int) ($row['inbound'] ?? 0),
            'outbound' => (int) ($row['outbound'] ?? 0),
            'failed' => (int) ($row['failed'] ?? 0),
        ];
    }

    public function overview_stats($hours = 24)
    {
        $hours = max(1, (int) $hours);
        $since = date('Y-m-d H:i:s', time() - ($hours * 3600));

        $pipeline24 = $this->pipeline_counts_24h();

        $bridge24 = ['total' => 0, 'failed' => 0];
        if ($this->db->table_exists($this->t_bridge())) {
            $row = $this->db
                ->select('COUNT(*) AS total, SUM(CASE WHEN status IN ("failed","error") THEN 1 ELSE 0 END) AS failed', false)
                ->where('created_at >=', $since)
                ->get($this->t_bridge())
                ->row_array();
            $bridge24['total'] = (int) ($row['total'] ?? 0);
            $bridge24['failed'] = (int) ($row['failed'] ?? 0);
        }

        $gateway24 = ['total' => 0, 'failed' => 0, 'avg_latency' => null];
        if ($this->db->table_exists($this->t_logs())) {
            $row = $this->db
                ->select('COUNT(*) AS total, SUM(CASE WHEN status IN ("failed","error","upstream_error","validation_error") THEN 1 ELSE 0 END) AS failed, AVG(latency_ms) AS avg_latency', false)
                ->where('created_at >=', $since)
                ->get($this->t_logs())
                ->row_array();
            $gateway24['total'] = (int) ($row['total'] ?? 0);
            $gateway24['failed'] = (int) ($row['failed'] ?? 0);
            $gateway24['avg_latency'] = isset($row['avg_latency']) && $row['avg_latency'] !== null ? (float) $row['avg_latency'] : null;
        }

        return [
            'window_hours' => $hours,
            'window' => [
                'pipeline' => $pipeline24,
                'bridge' => $bridge24,
                'gateway' => $gateway24,
                'has_activity' => ((int) $pipeline24['total'] + (int) $bridge24['total'] + (int) $gateway24['total']) > 0,
            ],
            'streams' => [
                'pipeline' => $this->stream_snapshot($this->t_pipeline(), 'OpenClaw / Perfex Pipeline'),
                'bridge' => $this->stream_snapshot($this->t_bridge(), 'Bridge Delivery Queue'),
                'gateway' => $this->stream_snapshot($this->t_logs(), 'Gateway API Logs'),
            ],
        ];
    }

    public function recent_alerts($limit = 6)
    {
        $limit = max(1, min(12, (int) $limit));
        $alerts = [];

        if ($this->db->table_exists($this->t_pipeline())) {
            $rows = $this->db
                ->select('created_at, status, event_name, summary')
                ->order_by('created_at', 'DESC')
                ->limit($limit)
                ->get($this->t_pipeline())
                ->result_array();

            foreach ($rows as $row) {
                $alerts[] = [
                    'stream' => 'pipeline',
                    'title' => !empty($row['event_name']) ? (string) $row['event_name'] : 'Pipeline event',
                    'message' => !empty($row['summary']) ? (string) $row['summary'] : 'Pipeline activity detected',
                    'severity' => $this->normalize_alert_severity($row['status'] ?? ''),
                    'created_at' => (string) ($row['created_at'] ?? ''),
                ];
            }
        }

        if ($this->db->table_exists($this->t_bridge())) {
            $rows = $this->db
                ->select('created_at, status, event_name, module_name, action_name, last_error')
                ->order_by('created_at', 'DESC')
                ->limit($limit)
                ->get($this->t_bridge())
                ->result_array();

            foreach ($rows as $row) {
                $message = trim(implode(' | ', array_filter([
                    $row['module_name'] ?? null,
                    $row['action_name'] ?? null,
                    $row['last_error'] ?? null,
                ])));
                $alerts[] = [
                    'stream' => 'bridge',
                    'title' => !empty($row['event_name']) ? (string) $row['event_name'] : 'Bridge event',
                    'message' => $message !== '' ? $message : 'Bridge queue activity detected',
                    'severity' => $this->normalize_alert_severity($row['status'] ?? ''),
                    'created_at' => (string) ($row['created_at'] ?? ''),
                ];
            }
        }

        if ($this->db->table_exists($this->t_logs())) {
            $rows = $this->db
                ->select('created_at, status, action_id, path, error_message, latency_ms')
                ->order_by('created_at', 'DESC')
                ->limit($limit)
                ->get($this->t_logs())
                ->result_array();

            foreach ($rows as $row) {
                $message = trim(implode(' | ', array_filter([
                    $row['path'] ?? null,
                    !empty($row['latency_ms']) ? ((string) $row['latency_ms']) . 'ms' : null,
                    $row['error_message'] ?? null,
                ])));
                $alerts[] = [
                    'stream' => 'gateway',
                    'title' => !empty($row['action_id']) ? (string) $row['action_id'] : 'Gateway action',
                    'message' => $message !== '' ? $message : 'Gateway activity detected',
                    'severity' => $this->normalize_alert_severity($row['status'] ?? ''),
                    'created_at' => (string) ($row['created_at'] ?? ''),
                ];
            }
        }

        usort($alerts, function ($left, $right) {
            return strcmp((string) ($right['created_at'] ?? ''), (string) ($left['created_at'] ?? ''));
        });

        return array_slice($alerts, 0, $limit);
    }

    private function stream_snapshot($table, $label)
    {
        $snapshot = [
            'label' => $label,
            'exists' => false,
            'total' => 0,
            'latest_at' => null,
        ];

        if (!$this->db->table_exists($table)) {
            return $snapshot;
        }

        $snapshot['exists'] = true;
        $row = $this->db
            ->select('COUNT(*) AS total, MAX(created_at) AS latest_at', false)
            ->get($table)
            ->row_array();

        $snapshot['total'] = (int) ($row['total'] ?? 0);
        $snapshot['latest_at'] = !empty($row['latest_at']) ? (string) $row['latest_at'] : null;

        return $snapshot;
    }

    private function normalize_alert_severity($status)
    {
        $status = strtolower(trim((string) $status));
        if (in_array($status, ['failed', 'error', 'upstream_error', 'validation_error'], true)) {
            return 'critical';
        }
        if (in_array($status, ['pending', 'queued', 'retry', 'processing'], true)) {
            return 'warning';
        }

        return 'info';
    }
}
