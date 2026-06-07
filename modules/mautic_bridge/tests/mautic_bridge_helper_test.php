<?php

declare(strict_types=1);

$helper = __DIR__ . '/../helpers/mautic_bridge_helper.php';
if (!is_file($helper)) {
    fwrite(STDERR, "Missing helper: {$helper}\n");
    exit(1);
}

require_once $helper;

function assert_same($expected, $actual, string $message): void
{
    if ($expected !== $actual) {
        fwrite(STDERR, $message . "\nExpected: " . var_export($expected, true) . "\nActual: " . var_export($actual, true) . "\n");
        exit(1);
    }
}

$payload = [
    'contact' => [
        'id' => 123,
        'fields' => [
            'core' => [
                'email' => ['value' => ' Person@Example.COM '],
                'firstname' => ['value' => ' Linh '],
                'lastname' => ['value' => ' Nguyen '],
                'phone' => ['value' => ' 0909 '],
                'company' => ['value' => ' Future Horizon '],
            ],
        ],
        'dateModified' => '2026-06-05T04:00:00+00:00',
    ],
    'campaigns' => [
        ['id' => 9, 'alias' => 'welcome-flow', 'name' => 'Welcome Flow'],
        ['id' => 10, 'name' => 'No Alias'],
    ],
];

$normalized = mautic_bridge_normalize_contact_payload($payload);
assert_same(123, $normalized['mautic_contact_id'], 'normalizes Mautic contact id');
assert_same('person@example.com', $normalized['email'], 'normalizes email');
assert_same('Linh Nguyen', $normalized['name'], 'builds lead name');
assert_same('0909', $normalized['phonenumber'], 'normalizes phone');
assert_same('Future Horizon', $normalized['company'], 'normalizes company');
assert_same('2026-06-05 04:00:00', $normalized['mautic_modified_at'], 'normalizes modified timestamp to UTC SQL format');
assert_same(['mautic:campaign:welcome-flow', 'mautic:campaign:10'], mautic_bridge_campaign_tags($normalized['campaigns']), 'builds campaign tags');

$mauticWebhookPayload = [
    'mautic.lead_post_save_new' => [
        $payload,
    ],
];
$normalizedWebhook = mautic_bridge_normalize_contact_payload($mauticWebhookPayload);
assert_same(123, $normalizedWebhook['mautic_contact_id'], 'unwraps Mautic webhook event payload');
assert_same('person@example.com', $normalizedWebhook['email'], 'normalizes email from Mautic webhook event payload');

$mojibakePayload = [
    'contact' => [
        'id' => 456,
        'fields' => [
            'core' => [
                'email' => ['value' => 'viet@example.com'],
                'company' => ['value' => 'CÃ´ng Ty TNHH TÆ° Váº¥n Du Há»c VÃ  Dá»ch Vá»¥ ÄÃ o Táº¡o ANT'],
            ],
        ],
    ],
];
$normalizedMojibake = mautic_bridge_normalize_contact_payload($mojibakePayload);
assert_same('Công Ty TNHH Tư Vấn Du Học Và Dịch Vụ Đào Tạo ANT', $normalizedMojibake['company'], 'repairs mojibake company text');

assert_same('incoming', mautic_bridge_newer_side('2026-06-05 04:00:01', '2026-06-05 04:00:00'), 'incoming timestamp wins');
assert_same('local', mautic_bridge_newer_side('2026-06-05 04:00:00', '2026-06-05 04:00:01'), 'local timestamp wins');
assert_same('equal', mautic_bridge_newer_side('2026-06-05 04:00:00', '2026-06-05 04:00:00'), 'equal timestamp is equal');

echo "mautic_bridge_helper_test passed\n";
