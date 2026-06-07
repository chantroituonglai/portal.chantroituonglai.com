<?php

$root = dirname(__DIR__);

$checks = [
    'application/controllers/admin/Estimates.php' => [
        'create_empty_draft(',
        'autosave_draft(',
    ],
    'application/controllers/admin/Invoices.php' => [
        'create_empty_draft(',
        'autosave_draft(',
    ],
    'application/controllers/admin/Proposals.php' => [
        'create_empty_draft(',
        'autosave_draft(',
    ],
    'application/controllers/admin/Credit_notes.php' => [
        'create_empty_draft(',
        'autosave_draft(',
    ],
    'application/models/Estimates_model.php' => [
        'function create_empty_draft(',
    ],
    'application/models/Invoices_model.php' => [
        'function create_empty_draft(',
    ],
    'application/models/Proposals_model.php' => [
        'function create_empty_draft(',
    ],
    'application/models/Credit_notes_model.php' => [
        'STATUS_DRAFT = 4',
        'function create_empty_draft(',
    ],
    'application/views/admin/estimates/estimate.php' => [
        'data-autosave-url',
        'sales-draft-autosave-status',
    ],
    'application/views/admin/invoices/invoice.php' => [
        'data-autosave-url',
        'sales-draft-autosave-status',
    ],
    'application/views/admin/proposals/proposal.php' => [
        'data-autosave-url',
        'sales-draft-autosave-status',
    ],
    'application/views/admin/credit_notes/credit_note.php' => [
        'data-autosave-url',
        'sales-draft-autosave-status',
    ],
    'assets/js/sales_draft_autosave.js' => [
        'sales-draft-autosave',
        'navigator.sendBeacon',
        'beforeunload',
    ],
];

$failures = [];

foreach ($checks as $file => $needles) {
    $path = $root . '/' . $file;
    if (!is_file($path)) {
        $failures[] = $file . ' missing';
        continue;
    }

    $contents = file_get_contents($path);
    foreach ($needles as $needle) {
        if (strpos($contents, $needle) === false) {
            $failures[] = $file . ' missing "' . $needle . '"';
        }
    }
}

if ($failures) {
    echo "Sales draft static test failed:\n";
    foreach ($failures as $failure) {
        echo ' - ' . $failure . "\n";
    }
    exit(1);
}

echo "Sales draft static test passed.\n";
