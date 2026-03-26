<?php
return [
    'label' => 'Services',
    'read_only' => true,
    'permissions' => [
        'm_services_records' => ['name' => 'Services Records', 'capabilities' => ['get' => 'List']],
    ],
    'resources' => [
        'records' => ['label' => 'Services Records', 'table' => 'tblservices'],
    ],
];
