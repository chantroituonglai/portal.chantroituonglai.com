<?php
return [
    'label' => 'Commission',
    'read_only' => true,
    'permissions' => [
        'm_commission_records' => ['name' => 'Commission', 'capabilities' => ['get' => 'List']],
    ],
    'resources' => [
        'records' => ['label' => 'Commission', 'table' => 'tblcommission'],
    ],
];
