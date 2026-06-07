<?php

declare(strict_types=1);

$view = file_get_contents(__DIR__ . '/../../application/views/admin/contracts/contract.php');

$expectations = [
    'back button links to contracts list' => strpos($view, "admin_url('contracts')") !== false,
    'back button uses arrow icon'         => strpos($view, 'fa-arrow-left') !== false,
    'back button has localized label'     => strpos($view, "_l('go_back')") !== false,
];

$failed = [];
foreach ($expectations as $message => $passed) {
    if (!$passed) {
        $failed[] = $message;
    }
}

if ($failed !== []) {
    fwrite(STDERR, "Contract back button view test failed:\n- " . implode("\n- ", $failed) . "\n");
    exit(1);
}

echo "Contract back button view test passed.\n";
