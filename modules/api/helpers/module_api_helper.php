<?php

defined('BASEPATH') or exit('No direct script access allowed');

function module_api_envelope($status, $message, $data = null, $meta = [], $errors = [])
{
    return [
        'status' => $status,
        'message' => $message,
        'data' => $data,
        'meta' => $meta,
        'errors' => $errors,
    ];
}

function module_api_permissions_from_manifest($manifest)
{
    $permissions = [];
    if (!is_array($manifest) || empty($manifest['permissions']) || !is_array($manifest['permissions'])) {
        return $permissions;
    }

    foreach ($manifest['permissions'] as $feature => $permission) {
        $permissions[$feature] = $permission;
    }

    return $permissions;
}
