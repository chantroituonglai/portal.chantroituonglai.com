<?php
return [
    'label' => 'Warehouse',
    'read_only' => true,
    'permissions' => [
        'm_warehouse_records' => ['name' => 'Warehouse Records', 'capabilities' => ['get' => 'List']],
    ],
    'resources' => [
        'records' => ['label' => 'Warehouse Records', 'table' => 'tblware_unit_type_opening_stock'],
    ],
];
