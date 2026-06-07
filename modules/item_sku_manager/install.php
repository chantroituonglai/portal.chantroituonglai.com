<?php

defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();
require_once __DIR__ . '/helpers/item_sku_manager_helper.php';

$itemable = db_prefix() . 'itemable';
$aliases  = db_prefix() . 'item_sku_aliases';
$logs     = db_prefix() . 'itemable_sku_backfill_logs';
$meta     = db_prefix() . 'item_sku_metadata';
$traces   = db_prefix() . 'item_sku_trace_logs';

if (!function_exists('item_sku_manager_seed_alias')) {
    function item_sku_manager_seed_alias($CI, $itemId, $sku, $alias, $source)
    {
        $normalized = item_sku_manager_normalize_text($alias);
        if ($normalized === '') {
            return;
        }

        $table = db_prefix() . 'item_sku_aliases';
        $CI->db->where('item_id', $itemId);
        $CI->db->where('normalized_alias', $normalized);
        $exists = $CI->db->get($table)->row_array();
        $now    = date('Y-m-d H:i:s');

        $data = [
            'item_id'          => $itemId,
            'sku_code'         => $sku,
            'alias_name'       => $alias,
            'normalized_alias' => $normalized,
            'source'           => $source,
            'active'           => 1,
            'updated_at'       => $now,
        ];

        if ($exists) {
            $CI->db->where('id', (int) $exists['id'])->update($table, $data);
            return;
        }

        $data['created_at'] = $now;
        $CI->db->insert($table, $data);
    }
}

$columns = [
    'item_sku'             => "ALTER TABLE `{$itemable}` ADD `item_sku` VARCHAR(191) NULL AFTER `unit`",
    'item_master_id'       => "ALTER TABLE `{$itemable}` ADD `item_master_id` INT(11) NULL AFTER `item_sku`",
    'item_snapshot_json'   => "ALTER TABLE `{$itemable}` ADD `item_snapshot_json` LONGTEXT NULL AFTER `item_master_id`",
    'item_snapshot_hash'   => "ALTER TABLE `{$itemable}` ADD `item_snapshot_hash` VARCHAR(64) NULL AFTER `item_snapshot_json`",
    'sku_matched_at'       => "ALTER TABLE `{$itemable}` ADD `sku_matched_at` DATETIME NULL AFTER `item_snapshot_hash`",
    'sku_match_confidence' => "ALTER TABLE `{$itemable}` ADD `sku_match_confidence` INT(3) NULL AFTER `sku_matched_at`",
    'sku_match_source'     => "ALTER TABLE `{$itemable}` ADD `sku_match_source` VARCHAR(40) NULL AFTER `sku_match_confidence`",
];

foreach ($columns as $column => $sql) {
    if (!$CI->db->field_exists($column, $itemable)) {
        $CI->db->query($sql);
    }
}

if (!$CI->db->table_exists($aliases)) {
    $CI->db->query("CREATE TABLE `{$aliases}` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `item_id` INT(11) NOT NULL,
        `sku_code` VARCHAR(191) NOT NULL,
        `alias_name` VARCHAR(191) NOT NULL,
        `normalized_alias` VARCHAR(191) NOT NULL,
        `source` VARCHAR(40) NOT NULL DEFAULT 'manual',
        `active` TINYINT(1) NOT NULL DEFAULT 1,
        `created_at` DATETIME NOT NULL,
        `updated_at` DATETIME NOT NULL,
        PRIMARY KEY (`id`),
        KEY `item_id` (`item_id`),
        KEY `sku_code` (`sku_code`),
        KEY `normalized_alias` (`normalized_alias`)
    ) ENGINE=InnoDB DEFAULT CHARSET={$CI->db->char_set};");
}

if (!$CI->db->table_exists($logs)) {
    $CI->db->query("CREATE TABLE `{$logs}` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `itemable_id` INT(11) NOT NULL,
        `rel_type` VARCHAR(40) NOT NULL,
        `rel_id` INT(11) NOT NULL,
        `old_description` TEXT NULL,
        `matched_sku` VARCHAR(191) NULL,
        `matched_item_id` INT(11) NULL,
        `confidence` INT(3) NOT NULL DEFAULT 0,
        `status` VARCHAR(30) NOT NULL,
        `message` TEXT NULL,
        `created_at` DATETIME NOT NULL,
        PRIMARY KEY (`id`),
        KEY `itemable_id` (`itemable_id`),
        KEY `status` (`status`),
        KEY `matched_sku` (`matched_sku`)
    ) ENGINE=InnoDB DEFAULT CHARSET={$CI->db->char_set};");
}

if (!$CI->db->table_exists($meta)) {
    $CI->db->query("CREATE TABLE `{$meta}` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `item_id` INT(11) NOT NULL,
        `sku_version` VARCHAR(40) NULL,
        `sku_status` VARCHAR(30) NOT NULL DEFAULT 'active',
        `effective_from` DATE NULL,
        `effective_to` DATE NULL,
        `replaced_by_item_id` INT(11) NULL,
        `archived_at` DATETIME NULL,
        `archived_by` INT(11) NULL,
        `created_at` DATETIME NOT NULL,
        `updated_at` DATETIME NOT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `item_id` (`item_id`),
        KEY `sku_status` (`sku_status`),
        KEY `replaced_by_item_id` (`replaced_by_item_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET={$CI->db->char_set};");
}

if (!$CI->db->table_exists($traces)) {
    $CI->db->query("CREATE TABLE `{$traces}` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `item_id` INT(11) NOT NULL,
        `event_type` VARCHAR(40) NOT NULL,
        `field_name` VARCHAR(191) NULL,
        `old_value` LONGTEXT NULL,
        `new_value` LONGTEXT NULL,
        `staff_id` INT(11) NULL,
        `context` LONGTEXT NULL,
        `created_at` DATETIME NOT NULL,
        PRIMARY KEY (`id`),
        KEY `item_id` (`item_id`),
        KEY `event_type` (`event_type`),
        KEY `staff_id` (`staff_id`),
        KEY `created_at` (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET={$CI->db->char_set};");
}

$serviceSkus = [
    1  => 'VPS-MANAGED-PERFORMANCE',
    2  => 'LP-FINANCE-CONVERSION',
    4  => 'HOSTING-STARTER',
    5  => 'HOSTING-GROWTH',
    6  => 'HOSTING-COMMERCE',
    7  => 'WEB-BIZ-STARTER',
    8  => 'WEB-SHOP-STARTER',
    9  => 'WEB-ECOM-PRO',
    10 => 'SEO-CONTENT-SPRINT-30',
    11 => 'SEO-CONTENT-STRATEGY-30',
    12 => 'SEO-CONTENT-GROWTH-60',
    13 => 'SEO-CONTENT-SCALE-100',
    14 => 'SEO-CONTENT-STRATEGY-60',
    15 => 'SEO-CONTENT-STRATEGY-100',
    18 => 'WEB-BIZ-GROWTH',
    19 => 'WEB-SHOP-GROWTH',
    20 => 'ONSITE-CONSULTING-HOUR',
    21 => 'ELEMENTOR-PRO-ACTIVATION',
    23 => 'BRAND-IDENTITY-STARTER',
    32 => 'SSL-SECURITY-CERT',
    74 => 'ZOHO-MAIL-LITE-5GB-MANAGED',
    75 => 'ZOHO-MAIL-FREE-SETUP',
    80 => 'WEBSITE-CARE-HOSTING-OPS',
    81 => 'WEB-LOCAL-STARTER',
    82 => 'WEB-LOCAL-GROWTH',
    83 => 'WEB-SALES-ENGINE-PRO',
    84 => 'WEBSITE-CARE-MONTHLY',
    85 => 'LOCAL-SEO-GBP',
    86 => 'BUSINESS-EMAIL-CARE',
];

foreach ($serviceSkus as $id => $sku) {
    $CI->db->where('id', $id);
    $CI->db->where("(sku_code IS NULL OR sku_code = '')", null, false);
    $CI->db->update(db_prefix() . 'items', ['sku_code' => $sku, 'sku_name' => $sku]);
}

$CI->db->select('id, description, sku_code, commodity_code');
$items = $CI->db->get(db_prefix() . 'items')->result_array();
foreach ($items as $item) {
    $sku = $item['sku_code'] ?: $item['commodity_code'];
    if (!$sku) {
        $sku = item_sku_manager_generate_sku($item['description'], $item['id']);
        $CI->db->where('id', (int) $item['id'])->update(db_prefix() . 'items', [
            'sku_code' => $sku,
            'sku_name' => $sku,
        ]);
    }

    item_sku_manager_seed_alias($CI, (int) $item['id'], $sku, $item['description'], 'master');
}

$legacyAliases = [
    4  => ['Gói Hosting Cơ bản'],
    5  => ['Gói Hosting Doanh nghiệp'],
    6  => ['Gói Hosting Bán hàng/TMĐT'],
    7  => ['Thiết kế website giới thiệu doanh nghiệp (Gói A)', 'Website giới thiệu doanh nghiệp gói A'],
    8  => ['Thiết kế website bán hàng (Gói B)', 'Website bán hàng gói B'],
    9  => ['Thiết kế website TMDT (Gói C)', 'Website thương mại điện tử gói C'],
    18 => ['Thiết kế website giới thiệu doanh nghiệp (Gói PREMIUM-A)', 'Website doanh nghiệp premium A'],
    19 => ['Thiết kế website bán hàng (Gói PREMIUM B)', 'Website bán hàng premium B'],
    74 => ['MAIL LITE - 5GB/1User', 'Mail Basic', 'Business Mail Standard'],
    75 => ['Zoho Mail Free setup', 'Business Mail Starter'],
];

foreach ($legacyAliases as $itemId => $names) {
    $CI->db->select('sku_code');
    $CI->db->where('id', $itemId);
    $row = $CI->db->get(db_prefix() . 'items')->row_array();
    if (!$row || !$row['sku_code']) {
        continue;
    }

    foreach ($names as $name) {
        item_sku_manager_seed_alias($CI, (int) $itemId, $row['sku_code'], $name, 'legacy');
    }
}

if (function_exists('update_option')) {
    update_option('item_sku_manager_schema_version', '110');
}
