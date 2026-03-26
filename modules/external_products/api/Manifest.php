<?php
return [
    'label' => 'External Products',
    'read_only' => true,
    'permissions' => [
        'm_external_products_records' => ['name' => 'External Products', 'capabilities' => ['get' => 'List']],
    ],
    'resources' => [
        'records' => ['label' => 'External Products', 'table' => 'tblexternal_products'],
    ],
];
