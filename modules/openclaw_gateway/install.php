<?php
defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->dbforge();

$tblLogs = db_prefix() . 'openclaw_gateway_logs';
if (!$CI->db->table_exists($tblLogs)) {
    $CI->db->query('CREATE TABLE `' . $tblLogs . '` (
      `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      `request_id` VARCHAR(64) NOT NULL,
      `principal_type` VARCHAR(20) NOT NULL,
      `principal_id` VARCHAR(64) NULL,
      `action_id` VARCHAR(160) NULL,
      `http_method` VARCHAR(10) NOT NULL,
      `path` VARCHAR(255) NOT NULL,
      `params_masked` LONGTEXT NULL,
      `status` VARCHAR(20) NOT NULL,
      `http_code` INT NOT NULL,
      `error_code` VARCHAR(80) NULL,
      `error_message` TEXT NULL,
      `latency_ms` INT NOT NULL DEFAULT 0,
      `meta_json` LONGTEXT NULL,
      `created_at` DATETIME NOT NULL,
      PRIMARY KEY (`id`),
      KEY `idx_ocg_request_id` (`request_id`),
      KEY `idx_ocg_action_status` (`action_id`, `status`),
      KEY `idx_ocg_created_at` (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;');
}

$tblIdem = db_prefix() . 'openclaw_gateway_idempotency';
if (!$CI->db->table_exists($tblIdem)) {
    $CI->db->query('CREATE TABLE `' . $tblIdem . '` (
      `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      `idempotency_key` VARCHAR(128) NOT NULL,
      `principal_hash` VARCHAR(128) NOT NULL,
      `action_id` VARCHAR(160) NOT NULL,
      `request_hash` VARCHAR(128) NOT NULL,
      `response_json` LONGTEXT NOT NULL,
      `created_at` DATETIME NOT NULL,
      PRIMARY KEY (`id`),
      UNIQUE KEY `uniq_ocg_idem` (`idempotency_key`, `principal_hash`, `action_id`),
      KEY `idx_ocg_idem_created` (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;');
}

if (get_option('openclaw_gateway_enabled') === '') {
    add_option('openclaw_gateway_enabled', 1);
}
if (get_option('openclaw_gateway_auth_mode') === '') {
    add_option('openclaw_gateway_auth_mode', 'dual');
}
if (get_option('openclaw_gateway_read_only') === '') {
    add_option('openclaw_gateway_read_only', 0);
}
if (get_option('openclaw_gateway_request_timeout_ms') === '') {
    add_option('openclaw_gateway_request_timeout_ms', 12000);
}
if (get_option('openclaw_gateway_retry_max') === '') {
    add_option('openclaw_gateway_retry_max', 1);
}
if (get_option('openclaw_gateway_mask_sensitive') === '') {
    add_option('openclaw_gateway_mask_sensitive', 1);
}
if (get_option('openclaw_gateway_db_version') === '') {
    add_option('openclaw_gateway_db_version', OPENCLAW_GATEWAY_DB_VERSION);
} else {
    update_option('openclaw_gateway_db_version', OPENCLAW_GATEWAY_DB_VERSION);
}
