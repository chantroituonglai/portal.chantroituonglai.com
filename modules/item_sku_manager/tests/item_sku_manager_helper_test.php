<?php

$helper = dirname(__DIR__) . '/helpers/item_sku_manager_helper.php';
if (!file_exists($helper)) {
    fwrite(STDERR, "Missing helper: {$helper}\n");
    exit(1);
}

require_once $helper;

function assert_same($expected, $actual, $message)
{
    if ($expected !== $actual) {
        fwrite(STDERR, $message . "\nExpected: " . var_export($expected, true) . "\nActual: " . var_export($actual, true) . "\n");
        exit(1);
    }
}

function assert_true($condition, $message)
{
    if (!$condition) {
        fwrite(STDERR, $message . "\n");
        exit(1);
    }
}

$line = [
    'id'               => 501,
    'description'      => 'Thiết kế website giới thiệu doanh nghiệp (Gói A)',
    'long_description' => 'Nội dung đã chốt trên báo giá',
    'rate'             => '8500000.00',
    'qty'              => '1.00',
    'unit'             => 'gói',
    'rel_type'         => 'estimate',
    'rel_id'           => 77,
];

$masters = [
    [
        'id'               => 7,
        'description'      => 'Website Doanh Nghiệp Starter',
        'long_description' => 'Landing doanh nghiệp cơ bản',
        'rate'             => '12325000.00',
        'unit'             => 'gói',
        'sku_code'         => 'WEB-BIZ-STARTER',
        'tax'              => 2,
    ],
];

$aliases = [
    [
        'item_id'          => 7,
        'sku_code'         => 'WEB-BIZ-STARTER',
        'alias_name'       => 'Thiết kế website giới thiệu doanh nghiệp (Gói A)',
        'normalized_alias' => item_sku_manager_normalize_text('Thiết kế website giới thiệu doanh nghiệp (Gói A)'),
        'active'           => 1,
    ],
];

$match = item_sku_manager_match_line($line, $masters, $aliases);
assert_same('WEB-BIZ-STARTER', $match['sku_code'], 'Old item name should match the new stable SKU through aliases.');
assert_same(7, (int) $match['item_master_id'], 'Matched master item id should be retained as metadata.');
assert_same('alias', $match['source'], 'Expected alias match source.');

$skuLine = $line;
$skuLine['description'] = 'Tên đã chỉnh thủ công trên báo giá';
$skuLine['item_sku'] = 'WEB-BIZ-STARTER';
$skuLine['item_master_id'] = 7;
$skuMatch = item_sku_manager_match_line($skuLine, $masters, $aliases);
assert_same('WEB-BIZ-STARTER', $skuMatch['sku_code'], 'Existing sales-line SKU should be authoritative over edited description.');
assert_same('sku', $skuMatch['source'], 'Expected direct SKU match source.');

$snapshot = item_sku_manager_build_snapshot($line, $masters[0], 'backfill');
$payload  = item_sku_manager_backfill_update_payload($line, $match, $snapshot, '2026-06-06 15:00:00');

assert_same([
    'item_sku',
    'item_master_id',
    'item_snapshot_json',
    'item_snapshot_hash',
    'sku_matched_at',
    'sku_match_confidence',
    'sku_match_source',
], array_keys($payload), 'Backfill payload must only include SKU metadata fields.');

assert_true(strpos($payload['item_snapshot_json'], 'Thiết kế website giới thiệu doanh nghiệp') !== false, 'Snapshot should preserve historical sales-line text.');
assert_true(strlen($payload['item_snapshot_hash']) === 64, 'Snapshot hash should be sha256.');

echo "item_sku_manager_helper_test passed\n";
