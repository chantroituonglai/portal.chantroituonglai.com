<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_106 extends App_module_migration
{
    public function up()
    {
        $CI = &get_instance();
        $table = db_prefix() . 'project_agent_actions';

        if ($CI->db->table_exists($table)) {
            $map = [
                'create_project' => ['entity' => 'project', 'tables' => ['projects','project_members','milestones','tasks','clients']],
                'update_project' => ['entity' => 'project', 'tables' => ['projects','project_members']],
                'get_project_overview' => ['entity' => 'project', 'tables' => ['projects','milestones','tasks','taskstimers','invoices','estimates','expenses','clients']],
                'summarize_project_work_remaining' => ['entity' => 'project', 'tables' => ['tasks','milestones','taskstimers']],
                'add_project_member' => ['entity' => 'project', 'tables' => ['project_members','staff','projects']],
                'create_task' => ['entity' => 'task', 'tables' => ['tasks','task_status','taskstimers','project_members','milestones']],
                'update_task' => ['entity' => 'task', 'tables' => ['tasks','task_status','taskstimers','task_assigned','task_followers']],
                'list_project_tasks' => ['entity' => 'task', 'tables' => ['tasks','task_status','milestones','task_assigned']],
                'check_billing_status' => ['entity' => 'project', 'tables' => ['taskstimers','tasks','invoices','expenses','projects']],
                'list_overdue_invoices' => ['entity' => 'invoice', 'tables' => ['invoices','clients','invoicepaymentrecords']],
                'create_estimate' => ['entity' => 'estimate', 'tables' => ['estimates','estimate_items','projects','clients','currencies']],
                'convert_estimate_to_invoice' => ['entity' => 'invoice', 'tables' => ['estimates','invoices','estimate_items','invoice_items','clients']],
                'convert_estimate_to_project' => ['entity' => 'project', 'tables' => ['estimates','projects','clients']],
                'create_invoice' => ['entity' => 'invoice', 'tables' => ['invoices','invoice_items','projects','clients','currencies']],
                'set_recurring_invoice' => ['entity' => 'invoice', 'tables' => ['invoices','clients']],
                'record_invoice_payment' => ['entity' => 'invoice', 'tables' => ['invoicepaymentrecords','invoices','payment_modes']],
                'send_invoice_email' => ['entity' => 'invoice', 'tables' => ['invoices','clients']],
                'invoice_project_expenses' => ['entity' => 'invoice', 'tables' => ['expenses','invoices','projects','clients']],
                'invoice_timesheets' => ['entity' => 'invoice', 'tables' => ['taskstimers','tasks','invoices','projects','clients']],
                'record_expense' => ['entity' => 'expense', 'tables' => ['expenses','projects','clients','expenses_categories','payment_modes']],
                'create_reminder' => ['entity' => 'reminder', 'tables' => ['reminders','staff']],
                'find_unlinked_entities' => ['entity' => 'other', 'tables' => ['tasks','expenses','taskstimers','invoices','estimates']],
                'autolink_entities' => ['entity' => 'project', 'tables' => ['tasks','expenses','taskstimers','projects']],
                'bill_tasks_to_invoice' => ['entity' => 'invoice', 'tables' => ['tasks','taskstimers','invoices','projects','clients']],
                'add_milestone' => ['entity' => 'project', 'tables' => ['milestones','projects']],
            ];

            foreach ($map as $actionId => $config) {
                $CI->db->where('action_id', $actionId);
                $row = $CI->db->get($table)->row_array();
                if (!$row) {
                    continue;
                }

                $update = [];
                $currentRelated = $row['related_tables'] ?? '';
                if ($currentRelated === null || $currentRelated === '' || strtolower(trim((string) $currentRelated)) === 'null') {
                    $update['related_tables'] = json_encode(array_values(array_unique($config['tables'])));
                }

                $currentEntity = $row['entity_type'] ?? '';
                if ($currentEntity === null || $currentEntity === '') {
                    $update['entity_type'] = $config['entity'];
                }

                if (!empty($update)) {
                    $CI->db->where('action_id', $actionId);
                    $CI->db->update($table, $update);
                }
            }
        }

        update_option('project_agent_db_version', 106);
    }
}
