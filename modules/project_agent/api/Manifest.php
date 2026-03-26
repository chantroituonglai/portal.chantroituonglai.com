<?php
return [
    'label' => 'Project Agent',
    'read_only' => true,
    'permissions' => [
        'm_project_agent_records' => ['name' => 'Project Agent', 'capabilities' => ['get' => 'List']],
    ],
    'resources' => [
        'records' => ['label' => 'Project Agent', 'table' => 'tblproject_agent_sessions'],
    ],
];
