<?php
return [
    'label' => 'Purchase',
    'read_only' => true,
    'permissions' => [
        'm_purchase_records' => ['name' => 'Purchase Records', 'capabilities' => ['get' => 'List']],
    ],
    'resources' => [
        'records' => ['label' => 'Purchase Records', 'table' => 'tblpur_orders'],
    ],
];
