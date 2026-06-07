<?php

declare(strict_types=1);

define('BASEPATH', __DIR__);

function restore_merge_fields($content)
{
    return str_replace('CLIENT NAME', '{client_company}', $content);
}

function get_acceptance_info_array($clear = false)
{
    return [
        'acceptance_firstname' => null,
        'acceptance_lastname'  => null,
        'acceptance_email'     => null,
        'acceptance_date'      => null,
        'acceptance_ip'        => null,
    ];
}

require __DIR__ . '/../../application/helpers/contracts_helper.php';

$contract = (object) [
    'id'                    => 123,
    'subject'               => 'Original contract',
    'client'                => 45,
    'project_id'            => 67,
    'content'               => 'Hello CLIENT NAME',
    'trash'                 => 1,
    'isexpirynotified'      => 1,
    'signed'                => 1,
    'marked_as_signed'      => 1,
    'signature'             => 'signature.png',
    'contacts_sent_to'      => '{"contact_ids":[1],"cc":""}',
    'last_sent_at'          => '2026-05-01T10:00:00+07:00',
    'short_link'            => 'https://short.test/abc',
    'datestart'             => '2026-05-01',
    'dateend'               => '2026-05-31',
    'acceptance_firstname'  => 'Signed',
    'acceptance_lastname'   => 'Person',
    'acceptance_email'      => 'signed@example.com',
    'acceptance_date'       => '2026-05-02 10:00:00',
    'acceptance_ip'         => '127.0.0.1',
];

$fields = array_keys(get_object_vars($contract));

$copy = prepare_contract_copy_data($contract, $fields);

$expectations = [
    'id is removed'                  => !array_key_exists('id', $copy),
    'client is blank'                => $copy['client'] === 0,
    'project is blank'               => $copy['project_id'] === null,
    'content has merge fields reset' => $copy['content'] === 'Hello {client_company}',
    'trash is reset'                 => $copy['trash'] === 0,
    'signed is reset'                => $copy['signed'] === 0,
    'marked signed is reset'         => $copy['marked_as_signed'] === 0,
    'signature is cleared'           => $copy['signature'] === null,
    'sent contacts are cleared'      => $copy['contacts_sent_to'] === null,
    'last sent timestamp is cleared' => $copy['last_sent_at'] === null,
    'short link is cleared'          => $copy['short_link'] === null,
    'dateend keeps original span'    => $copy['dateend'] === date('Y-m-d', strtotime('+30 DAY')),
    'acceptance email is cleared'    => $copy['acceptance_email'] === null,
];

$failed = [];
foreach ($expectations as $message => $passed) {
    if (!$passed) {
        $failed[] = $message;
    }
}

if ($failed !== []) {
    fwrite(STDERR, "Contract copy data test failed:\n- " . implode("\n- ", $failed) . "\n");
    exit(1);
}

echo "Contract copy data test passed.\n";
