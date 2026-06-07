<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_102 extends App_module_migration
{
    public function up()
    {
        $CI = &get_instance();

        $this->renameLegacyModuleRow($CI);
        $this->migrateOptions();
        $this->migrateTicketLogs($CI);
        $this->createSalesDocRuns($CI);

        update_option('futurecrmagent_db_version', 102);
    }

    private function renameLegacyModuleRow($CI): void
    {
        $modules = db_prefix() . 'modules';
        if (!$CI->db->table_exists($modules)) {
            return;
        }

        $legacy = $CI->db->where('module_name', 'geminiai')->get($modules)->row();
        $current = $CI->db->where('module_name', 'futurecrmagent')->get($modules)->row();
        if ($legacy && !$current) {
            $CI->db->where('module_name', 'geminiai')->update($modules, [
                'module_name' => 'futurecrmagent',
                'installed_version' => '1.0.2',
            ]);
        }
    }

    private function migrateOptions(): void
    {
        $map = [
            'geminiai_ticket_classify_enabled' => 'futurecrmagent_ticket_classify_enabled',
            'geminiai_ticket_prompt' => 'futurecrmagent_ticket_prompt',
            'geminiai_db_version' => 'futurecrmagent_db_version',
            'geminiai_map_dept_technical_issue' => 'futurecrmagent_map_dept_technical_issue',
            'geminiai_map_dept_billing' => 'futurecrmagent_map_dept_billing',
            'geminiai_map_dept_sales' => 'futurecrmagent_map_dept_sales',
            'geminiai_map_dept_account' => 'futurecrmagent_map_dept_account',
            'geminiai_map_dept_feedback' => 'futurecrmagent_map_dept_feedback',
            'geminiai_map_dept_other' => 'futurecrmagent_map_dept_other',
            'geminiai_map_pri_low' => 'futurecrmagent_map_pri_low',
            'geminiai_map_pri_medium' => 'futurecrmagent_map_pri_medium',
            'geminiai_map_pri_high' => 'futurecrmagent_map_pri_high',
            'geminiai_map_pri_urgent' => 'futurecrmagent_map_pri_urgent',
            'geminiai_map_status_technical_issue' => 'futurecrmagent_map_status_technical_issue',
            'geminiai_map_status_billing' => 'futurecrmagent_map_status_billing',
            'geminiai_map_status_sales' => 'futurecrmagent_map_status_sales',
            'geminiai_map_status_account' => 'futurecrmagent_map_status_account',
            'geminiai_map_status_feedback' => 'futurecrmagent_map_status_feedback',
            'geminiai_map_status_other' => 'futurecrmagent_map_status_other',
        ];

        foreach ($map as $legacy => $current) {
            $legacyValue = get_option($legacy);
            if ($legacyValue !== null && $legacyValue !== '' && (get_option($current) === null || get_option($current) === '')) {
                add_option($current, $legacyValue);
                update_option($current, $legacyValue);
            } else {
                add_option($current);
            }
        }

        add_option('futurecrmagent_sales_doc_system_prompt', 'You convert CRM sales document instructions into safe Perfex CRM draft fields. Return JSON only.');
    }

    private function migrateTicketLogs($CI): void
    {
        $legacy = db_prefix() . 'geminiai_ticket_logs';
        $current = db_prefix() . 'futurecrmagent_ticket_logs';
        if ($CI->db->table_exists($legacy) && !$CI->db->table_exists($current)) {
            $CI->db->query('RENAME TABLE `' . $legacy . '` TO `' . $current . '`');
            return;
        }

        if ($CI->db->table_exists($legacy) && $CI->db->table_exists($current)) {
            $legacyRows = (int) $CI->db->count_all($legacy);
            $currentRows = (int) $CI->db->count_all($current);
            if ($legacyRows > 0 && $currentRows === 0) {
                $CI->db->query('INSERT INTO `' . $current . '` SELECT * FROM `' . $legacy . '`');
            }
        }

        if (!$CI->db->table_exists($current)) {
            $CI->db->query('CREATE TABLE `' . $current . '` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `source` VARCHAR(20) DEFAULT NULL,
                `email_from` VARCHAR(191) DEFAULT NULL,
                `subject` VARCHAR(255) DEFAULT NULL,
                `preview` TEXT DEFAULT NULL,
                `classification` VARCHAR(191) DEFAULT NULL,
                `score` DECIMAL(5,2) DEFAULT NULL,
                `ticket_id` INT(11) DEFAULT NULL,
                `raw` LONGTEXT DEFAULT NULL,
                `error` TEXT DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_ticket_id` (`ticket_id`),
                KEY `idx_created_at` (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=' . $CI->db->char_set);
        }
    }

    private function createSalesDocRuns($CI): void
    {
        $table = db_prefix() . 'futurecrmagent_sales_doc_runs';
        if ($CI->db->table_exists($table)) {
            return;
        }

        $CI->db->query('CREATE TABLE `' . $table . '` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `staffid` INT(11) DEFAULT NULL,
            `doc_type` VARCHAR(30) NOT NULL,
            `doc_id` INT(11) DEFAULT NULL,
            `source_text` TEXT DEFAULT NULL,
            `llm_request_json` LONGTEXT DEFAULT NULL,
            `llm_response_json` LONGTEXT DEFAULT NULL,
            `normalized_payload_json` LONGTEXT DEFAULT NULL,
            `status` VARCHAR(30) NOT NULL DEFAULT "planned",
            `error` TEXT DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `idx_doc` (`doc_type`, `doc_id`),
            KEY `idx_staff` (`staffid`),
            KEY `idx_status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=' . $CI->db->char_set);
    }
}
