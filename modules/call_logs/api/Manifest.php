<?php
return [
    'label' => 'Call Logs',
    'read_only' => true,
    'permissions' => [
        'm_call_logs_records' => ['name' => 'Call Logs', 'capabilities' => ['get' => 'List']],
    ],
    'resources' => [
        'records' => ['label' => 'Call Logs', 'table' => 'tblcall_logs'],
    ],
];
