<?php

defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();

add_option('mautic_bridge_enabled', 0);
add_option('mautic_bridge_dry_run', 1);
add_option('mautic_bridge_base_url', '');
add_option('mautic_bridge_oauth_client_id', '');
add_option('mautic_bridge_oauth_client_secret', '');
add_option('mautic_bridge_oauth_access_token', '');
add_option('mautic_bridge_oauth_expires_at', 0);
add_option('mautic_bridge_webhook_secret', '');
add_option('mautic_bridge_default_lead_source', '');
add_option('mautic_bridge_default_lead_status', '');
add_option('mautic_bridge_default_assigned_staff', '');
add_option('mautic_bridge_timeout', 30);
add_option('mautic_bridge_retry_max', 3);
add_option('mautic_bridge_delete_remote', 0);
add_option('mautic_bridge_pull_since', '');
add_option('mautic_bridge_db_version', MAUTIC_BRIDGE_DB_VERSION);

$mapTable = db_prefix() . 'mautic_bridge_maps';
if (!$CI->db->table_exists($mapTable)) {
    $CI->db->query('CREATE TABLE `' . $mapTable . '` (
      `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      `mautic_contact_id` BIGINT UNSIGNED NULL,
      `perfex_rel_type` VARCHAR(20) NOT NULL,
      `perfex_rel_id` BIGINT UNSIGNED NOT NULL,
      `email` VARCHAR(191) NULL,
      `mautic_modified_at` DATETIME NULL,
      `perfex_modified_at` DATETIME NULL,
      `last_synced_at` DATETIME NULL,
      `created_at` DATETIME NOT NULL,
      `updated_at` DATETIME NOT NULL,
      PRIMARY KEY (`id`),
      UNIQUE KEY `uniq_mb_mautic_contact` (`mautic_contact_id`),
      UNIQUE KEY `uniq_mb_perfex_rel` (`perfex_rel_type`, `perfex_rel_id`),
      KEY `idx_mb_email` (`email`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;');
}

$queueTable = db_prefix() . 'mautic_bridge_queue';
if (!$CI->db->table_exists($queueTable)) {
    $CI->db->query('CREATE TABLE `' . $queueTable . '` (
      `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      `direction` VARCHAR(20) NOT NULL,
      `event_type` VARCHAR(40) NOT NULL,
      `rel_type` VARCHAR(20) NULL,
      `rel_id` BIGINT UNSIGNED NULL,
      `mautic_contact_id` BIGINT UNSIGNED NULL,
      `email` VARCHAR(191) NULL,
      `payload_json` LONGTEXT NULL,
      `status` VARCHAR(20) NOT NULL DEFAULT "pending",
      `attempts` INT NOT NULL DEFAULT 0,
      `next_attempt_at` DATETIME NULL,
      `last_error` TEXT NULL,
      `created_at` DATETIME NOT NULL,
      `updated_at` DATETIME NOT NULL,
      PRIMARY KEY (`id`),
      KEY `idx_mb_queue_due` (`status`, `next_attempt_at`),
      KEY `idx_mb_queue_rel` (`rel_type`, `rel_id`),
      KEY `idx_mb_queue_mautic` (`mautic_contact_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;');
}

$logTable = db_prefix() . 'mautic_bridge_logs';
if (!$CI->db->table_exists($logTable)) {
    $CI->db->query('CREATE TABLE `' . $logTable . '` (
      `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      `level` VARCHAR(20) NOT NULL,
      `message` VARCHAR(191) NOT NULL,
      `context_json` LONGTEXT NULL,
      `created_at` DATETIME NOT NULL,
      PRIMARY KEY (`id`),
      KEY `idx_mb_logs_created` (`created_at`),
      KEY `idx_mb_logs_level` (`level`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;');
}

$campaignTable = db_prefix() . 'mautic_bridge_campaigns';
if (!$CI->db->table_exists($campaignTable)) {
    $CI->db->query('CREATE TABLE `' . $campaignTable . '` (
      `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      `mautic_campaign_id` BIGINT UNSIGNED NOT NULL,
      `campaign_alias` VARCHAR(191) NULL,
      `campaign_name` VARCHAR(191) NULL,
      `perfex_tag` VARCHAR(191) NOT NULL,
      `synced_at` DATETIME NULL,
      PRIMARY KEY (`id`),
      UNIQUE KEY `uniq_mb_campaign` (`mautic_campaign_id`),
      KEY `idx_mb_campaign_tag` (`perfex_tag`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;');
}

$importJobTable = db_prefix() . 'mautic_bridge_import_jobs';
if (!$CI->db->table_exists($importJobTable)) {
    $CI->db->query('CREATE TABLE `' . $importJobTable . '` (
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

$importRowTable = db_prefix() . 'mautic_bridge_import_rows';
if (!$CI->db->table_exists($importRowTable)) {
    $CI->db->query('CREATE TABLE `' . $importRowTable . '` (
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

$eventFields = [
    'leads_mautic_points' => ['name' => 'Mautic Points', 'type' => 'number', 'show_on_table' => 1],
    'leads_mautic_segments' => ['name' => 'Mautic Segments', 'type' => 'textarea', 'show_on_table' => 0],
];
foreach ($eventFields as $slug => $field) {
    $exists = $CI->db
        ->where('fieldto', 'leads')
        ->where('slug', $slug)
        ->get(db_prefix() . 'customfields')
        ->row_array();
    if ($exists) {
        continue;
    }

    $maxOrder = (int) $CI->db
        ->select_max('field_order')
        ->where('fieldto', 'leads')
        ->get(db_prefix() . 'customfields')
        ->row('field_order');
    $CI->db->insert(db_prefix() . 'customfields', [
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
