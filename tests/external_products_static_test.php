<?php

$root = dirname(__DIR__);

function read_project_file($path)
{
    global $root;
    $fullPath = $root . '/' . $path;
    if (!is_file($fullPath)) {
        throw new RuntimeException('Missing file: ' . $path);
    }

    return file_get_contents($fullPath);
}

function assert_true($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$module = read_project_file('modules/external_products/external_products.php');
$js     = read_project_file('modules/external_products/assets/js/external_products.js');
$ctrl   = read_project_file('modules/external_products/controllers/External_products.php');
$model  = read_project_file('modules/external_products/models/External_products_model.php');
$mig    = read_project_file('modules/external_products/migrations/200_version_200.php');
$mig201 = read_project_file('modules/external_products/migrations/201_version_201.php');
$dupes  = read_project_file('modules/external_products/views/admin/duplicates.php');

assert_true(substr_count($module, 'assets/js/external_products.js') === 1, 'external_products.js must be enqueued exactly once.');
assert_true(strpos($module, "time()") === false, 'external_products.js must not use time() cache busting.');
assert_true(strpos($js, 'window.onload') === false, 'external_products.js must not replace window.onload.');
assert_true(strpos($js, '.externalProducts') !== false, 'external_products.js events must be namespaced.');
assert_true(strpos($js, 'externalProductsInitialized') !== false, 'external_products.js must guard against duplicate initialization.');
assert_true(strpos($module, "'href'     => admin_url('external_products'),") === false, 'External Products child menu should be hidden after product mapping consolidation.');
assert_true(strpos($ctrl, 'public function index()') !== false && strpos($ctrl, "redirect(admin_url('external_products/mapping'))") !== false, 'index() must redirect to product mapping for legacy compatibility.');
assert_true(strpos($module, "'external_orders'") === false, 'External Orders sidebar item should be hidden after order mapping consolidation.');
assert_true(strpos($ctrl, 'public function orders()') !== false && strpos($ctrl, "redirect(admin_url('external_products/order_mapping'))") !== false, 'orders() must redirect to order_mapping for legacy compatibility.');
assert_true(strpos($model, 'rand(5, 20)') === false, 'sync_orders_from_external_system() must not report fake random success.');
assert_true(strpos($model, "'success' => false") !== false && strpos($model, 'not implemented') !== false, 'sync_orders_from_external_system() must clearly report unsupported connectors.');
assert_true(strpos($mig, 'lotte_crawl_log') !== false, 'Migration must create lotte_crawl_log.');
assert_true(strpos($module, 'Version: 2.0.1') !== false, 'Module version must bump so live can run database upgrade.');
assert_true(strpos($mig201, 'lotte_crawl_log') !== false, '2.0.1 migration must create lotte_crawl_log for existing installs.');
assert_true(strpos($ctrl, "count_all_results('external_records')") === false, 'LOTTE status must not query external_records.');
assert_true(strpos($ctrl, "count_all_results(db_prefix() . 'external_data_mapping')") !== false, 'LOTTE status must query prefixed external_data_mapping.');
assert_true(strpos($ctrl, "db_prefix() . 'lotte_crawl_log'") !== false, 'LOTTE endpoints must use db_prefix() for lotte_crawl_log.');
assert_true(strpos($dupes, 'please_select_a_record_to_keep') !== false, 'Duplicate resolver must require a keep record.');
assert_true(strpos($dupes, "selectedIds.indexOf(keepRecordId) !== -1") === false, 'Duplicate resolver must not require keep record to be selected for deletion.');

echo "External products static checks passed.\n";
