<?php
return [
    'label' => 'Topics',
    'read_only' => true,
    'permissions' => [
        'm_topics_records' => ['name' => 'Topics', 'capabilities' => ['get' => 'List']],
    ],
    'resources' => [
        'records' => ['label' => 'Topics', 'table' => 'tbltopics'],
    ],
];
