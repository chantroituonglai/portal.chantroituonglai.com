<?php
return [
    'label' => 'Project Roadmap',
    'read_only' => true,
    'permissions' => [
        'm_project_roadmap_records' => ['name' => 'Project Roadmap', 'capabilities' => ['get' => 'List']],
    ],
    'resources' => [
        'records' => ['label' => 'Project Roadmap', 'table' => 'tblproject_roadmap_items'],
    ],
];
