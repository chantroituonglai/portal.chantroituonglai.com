<?php
return [
    'label' => 'HRM',
    'read_only' => true,
    'permissions' => [
        'm_hrm_staff' => ['name' => 'HRM Staff', 'capabilities' => ['get' => 'List']],
        'm_hrm_contracts' => ['name' => 'HRM Contracts', 'capabilities' => ['get' => 'List']],
    ],
    'resources' => [
        'staff' => ['label' => 'HRM Staff', 'table' => 'tblstaff'],
        'contracts' => ['label' => 'HRM Contracts', 'table' => 'tblhrm_contract'],
    ],
];
