<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Chatpion_bridge_task_model extends App_Model
{
    private $linksTable = 'chatpion_bridge_task_links';

    public function get_task_link($taskId)
    {
        $taskId = (int) $taskId;
        if ($taskId <= 0) {
            return null;
        }

        $row = $this->db->select('*')
            ->from(db_prefix() . $this->linksTable)
            ->where('task_id', $taskId)
            ->get()
            ->row_array();

        if (! $row) {
            return null;
        }

        if (! empty($row['workspace_json'])) {
            $decoded = json_decode($row['workspace_json'], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $row['workspace'] = $decoded;
            }
        }

        return $row;
    }

    public function upsert_task_link($taskId, array $payload): bool
    {
        $taskId = (int) $taskId;
        if ($taskId <= 0) {
            return false;
        }

        $payload['task_id'] = $taskId;
        $payload['updated_at'] = date('Y-m-d H:i:s');

        if (isset($payload['workspace']) && is_array($payload['workspace'])) {
            $payload['workspace_json'] = json_encode($payload['workspace'], JSON_UNESCAPED_UNICODE);
            unset($payload['workspace']);
        }

        $existing = $this->get_task_link($taskId);

        if ($existing) {
            $this->db->where('task_id', $taskId)->update(db_prefix() . $this->linksTable, $payload);

            return $this->db->affected_rows() >= 0;
        }

        $payload['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert(db_prefix() . $this->linksTable, $payload);

        return $this->db->affected_rows() > 0;
    }

    public function delete_task_link($taskId): bool
    {
        $taskId = (int) $taskId;
        if ($taskId <= 0) {
            return false;
        }

        $this->db->where('task_id', $taskId)->delete(db_prefix() . $this->linksTable);

        return $this->db->affected_rows() > 0;
    }

    public function update_workspace_only($taskId, $workspace): bool
    {
        $taskId = (int) $taskId;
        if ($taskId <= 0) {
            return false;
        }

        $payload = [
            'updated_at'     => date('Y-m-d H:i:s'),
            'workspace_json' => is_array($workspace)
                ? json_encode($workspace, JSON_UNESCAPED_UNICODE)
                : (string) $workspace,
        ];

        $existing = $this->get_task_link($taskId);
        if ($existing) {
            $this->db->where('task_id', $taskId)
                ->update(db_prefix() . $this->linksTable, $payload);

            return $this->db->affected_rows() >= 0;
        }

        $payload['task_id'] = $taskId;
        $payload['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert(db_prefix() . $this->linksTable, $payload);

        return $this->db->affected_rows() > 0;
    }
}

