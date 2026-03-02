<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Geminiai Model
 * - Encapsulates DB operations for logs and ticket mappings.
 */
class Geminiai_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Insert a classification log row.
     */
    public function insert_log(array $log): void
    {
        $table = db_prefix() . 'geminiai_ticket_logs';
        $insert = [
            'created_at'    => date('Y-m-d H:i:s'),
            'source'        => $log['source'] ?? null,
            'email_from'    => $log['email_from'] ?? null,
            'subject'       => $log['subject'] ?? null,
            'preview'       => $log['preview'] ?? null,
            'classification'=> $log['classification'] ?? null,
            'score'         => $log['score'] ?? null,
            'ticket_id'     => $log['ticket_id'] ?? null,
            'raw'           => isset($log['raw']) ? (is_string($log['raw']) ? $log['raw'] : json_encode($log['raw'])) : null,
            'error'         => $log['error'] ?? null,
        ];
        $this->db->insert($table, $insert);
    }

    /**
     * Get latest history items (latest per ticket + recent unlinked), newest first.
     */
    public function get_history_latest(int $limit = 50): array
    {
        $table = db_prefix() . 'geminiai_ticket_logs';
        $sql = "SELECT * FROM ("
             . " SELECT l.* FROM {$table} l"
             . " INNER JOIN (SELECT MAX(id) AS id FROM {$table} WHERE ticket_id IS NOT NULL GROUP BY ticket_id) t ON t.id = l.id"
             . " UNION ALL"
             . " SELECT l.* FROM {$table} l WHERE l.ticket_id IS NULL"
             . ") x ORDER BY x.id DESC LIMIT " . (int) $limit;
        return $this->db->query($sql)->result();
    }

    /**
     * Link the most recent unmatched log to a ticket and apply mapping.
     */
    public function link_latest_log_to_ticket(int $ticketId): void
    {
        $this->db->where('ticketid', $ticketId);
        $t = $this->db->get(db_prefix() . 'tickets')->row();
        if (!$t) { return; }

        $from    = (string) ($t->email ?? '');
        $subject = (string) ($t->subject ?? '');

        $table = db_prefix() . 'geminiai_ticket_logs';
        $this->db->where('ticket_id IS NULL', null, false);
        $this->db->where('email_from', $from);
        $this->db->where('subject', $subject);
        $this->db->where('created_at >=', date('Y-m-d H:i:s', time() - 600));
        $this->db->order_by('id', 'DESC');
        $this->db->limit(1);
        $row = $this->db->get($table)->row();
        if ($row) {
            $this->db->where('id', $row->id);
            $this->db->update($table, ['ticket_id' => $ticketId]);
            log_message('error', '[GEMINIAI] link_latest_log_to_ticket id=' . $ticketId . ' log_id=' . $row->id);

            $cat = null; $pri = null;
            if (!empty($row->classification) && strpos($row->classification, '/') !== false) {
                $parts = array_map('trim', explode('/', $row->classification, 2));
                $cat = $parts[0] ?? null;
                $pri = $parts[1] ?? null;
            }
            if ($cat || $pri) {
                $this->apply_mapping($ticketId, $cat, $pri);
            }
        } else {
            log_message('error', '[GEMINIAI] link_latest_log_to_ticket no log found for id=' . $ticketId);
        }
    }

    /**
     * Apply department/priority (and status) mapping to a ticket
     */
    public function apply_mapping(int $ticketId, $category, $priority): void
    {
        $normCat = function_exists('geminiai_normalize_category') ? geminiai_normalize_category($category) : $category;
        $normPri = function_exists('geminiai_normalize_priority') ? geminiai_normalize_priority($priority) : $priority;

        $updates = [];
        $deptId = 0; $statusId = 0; $priId = 0;
        if ($normCat) {
            $optKey = null;
            switch ($normCat) {
                case 'Technical Issue': $optKey = 'geminiai_map_dept_technical_issue'; break;
                case 'Billing': $optKey = 'geminiai_map_dept_billing'; break;
                case 'Sales': $optKey = 'geminiai_map_dept_sales'; break;
                case 'Account': $optKey = 'geminiai_map_dept_account'; break;
                case 'Feedback': $optKey = 'geminiai_map_dept_feedback'; break;
                case 'Other': $optKey = 'geminiai_map_dept_other'; break;
            }
            $deptId = $optKey ? (int) get_option($optKey) : 0;
            if ($deptId > 0) { $updates['department'] = $deptId; }

            $statusOpt = null;
            switch ($normCat) {
                case 'Technical Issue': $statusOpt = 'geminiai_map_status_technical_issue'; break;
                case 'Billing': $statusOpt = 'geminiai_map_status_billing'; break;
                case 'Sales': $statusOpt = 'geminiai_map_status_sales'; break;
                case 'Account': $statusOpt = 'geminiai_map_status_account'; break;
                case 'Feedback': $statusOpt = 'geminiai_map_status_feedback'; break;
                case 'Other': $statusOpt = 'geminiai_map_status_other'; break;
            }
            $statusId = $statusOpt ? (int) get_option($statusOpt) : 0;
            if ($statusId > 0) { $updates['status'] = $statusId; }
        }

        if ($normPri) {
            $optKey = null;
            switch ($normPri) {
                case 'Low': $optKey = 'geminiai_map_pri_low'; break;
                case 'Medium': $optKey = 'geminiai_map_pri_medium'; break;
                case 'High': $optKey = 'geminiai_map_pri_high'; break;
                case 'Urgent': $optKey = 'geminiai_map_pri_urgent'; break;
            }
            $priId = $optKey ? (int) get_option($optKey) : 0;
            if ($priId > 0) { $updates['priority'] = $priId; }
        }

        log_message('error', '[GEMINIAI] apply_mapping id=' . $ticketId . ' cat=' . $normCat . ' pri=' . $normPri . ' deptId=' . $deptId . ' priId=' . $priId . ' statusId=' . $statusId);

        if (!empty($updates)) {
            $this->db->where('ticketid', $ticketId);
            $this->db->update(db_prefix() . 'tickets', $updates);
            log_message('error', '[GEMINIAI] apply_mapping updated rows=' . $this->db->affected_rows());
        } else {
            log_message('error', '[GEMINIAI] apply_mapping no updates for id=' . $ticketId);
        }
    }
}

