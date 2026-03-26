<?php
return [
    'label' => 'Mailbox',
    'read_only' => true,
    'permissions' => [
        'm_mailbox_records' => ['name' => 'Mailbox', 'capabilities' => ['get' => 'List']],
    ],
    'resources' => [
        'records' => ['label' => 'Mailbox', 'table' => 'tblmailbox_emails'],
    ],
];
