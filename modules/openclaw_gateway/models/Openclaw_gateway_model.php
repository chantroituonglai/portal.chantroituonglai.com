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
}
