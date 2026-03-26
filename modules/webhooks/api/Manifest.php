<?php
return [
    'label' => 'Webhooks',
    'read_only' => true,
    'permissions' => [
        'm_webhooks_records' => ['name' => 'Webhooks', 'capabilities' => ['get' => 'List']],
    ],
    'resources' => [
        'records' => ['label' => 'Webhooks', 'table' => 'tblwebhooks'],
    ],
];
