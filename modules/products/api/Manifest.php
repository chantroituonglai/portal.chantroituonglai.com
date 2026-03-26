<?php
return [
    'label' => 'Products',
    'read_only' => true,
    'permissions' => [
        'm_products_records' => ['name' => 'Products Records', 'capabilities' => ['get' => 'List']],
    ],
    'resources' => [
        'records' => ['label' => 'Products Records', 'table' => 'tblproducts'],
    ],
];
