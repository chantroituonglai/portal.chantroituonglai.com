<?php
return [
    'label' => 'Deals',
    'read_only' => true,
    'permissions' => [
        'm_deals_records' => ['name' => 'Deals Records', 'capabilities' => ['get' => 'List']],
    ],
    'resources' => [
        'records' => ['label' => 'Deals Records', 'table' => 'tbldeals'],
    ],
];
