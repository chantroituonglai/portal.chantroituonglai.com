<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_202 extends App_module_migration
{
    public function up()
    {
        $CI = &get_instance();
        $table = db_prefix() . 'external_data_mapping';

        if (!$CI->db->table_exists($table)) {
            return;
        }

        $indexes = $CI->db->query('SHOW INDEX FROM ' . $table)->result_array();
        $existingIndexes = array_column($indexes, 'Key_name');

        $this->addIndexIfMissing($CI, $table, $existingIndexes, 'idx_ext_data_rel', 'ADD KEY `idx_ext_data_rel` (`rel`)');
        $this->addIndexIfMissing($CI, $table, $existingIndexes, 'idx_ext_data_rel_date_id', 'ADD KEY `idx_ext_data_rel_date_id` (`rel`, `dateadded`, `id`)');
        $this->addIndexIfMissing($CI, $table, $existingIndexes, 'idx_ext_data_rel_status', 'ADD KEY `idx_ext_data_rel_status` (`rel`, `status`)');
        $this->addIndexIfMissing($CI, $table, $existingIndexes, 'idx_ext_data_rel_uniquekey', 'ADD KEY `idx_ext_data_rel_uniquekey` (`rel`, `uniquekey`(191))');
        $this->addIndexIfMissing($CI, $table, $existingIndexes, 'idx_ext_data_rel_target', 'ADD KEY `idx_ext_data_rel_target` (`rel`, `target_id`(191))');

        log_activity('External Products module migrated to version 2.0.2');
    }

    private function addIndexIfMissing($CI, string $table, array $existingIndexes, string $indexName, string $alterClause): void
    {
        if (in_array($indexName, $existingIndexes, true)) {
            return;
        }

        try {
            $CI->db->query('ALTER TABLE ' . $table . ' ' . $alterClause);
        } catch (Exception $e) {
            log_message('error', 'external_products migration: ' . $indexName . ' index failed - ' . $e->getMessage());
        }
    }
}
