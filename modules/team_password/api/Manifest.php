<?php
return [
    'label' => 'Team Password',
    'read_only' => true,
    'permissions' => [
        'm_team_password_records' => ['name' => 'Team Password', 'capabilities' => ['get' => 'List']],
    ],
    'resources' => [
        'records' => ['label' => 'Team Password', 'table' => 'tblteam_password'],
    ],
];
