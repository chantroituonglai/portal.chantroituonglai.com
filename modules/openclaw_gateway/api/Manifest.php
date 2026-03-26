<?php
return [
    'label' => 'OpenClaw Gateway',
    'read_only' => true,
    'permissions' => [
        'm_openclaw_gateway_logs' => ['name' => 'OpenClaw Gateway Logs', 'capabilities' => ['get' => 'List']],
    ],
    'resources' => [
        'gateway_logs' => ['label' => 'Gateway Logs', 'table' => 'tblopenclaw_gateway_logs'],
        'bridge_events' => ['label' => 'Bridge Events', 'table' => 'tblopenclaw_bridge_events'],
    ],
];
