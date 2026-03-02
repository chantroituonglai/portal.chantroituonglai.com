<?php
defined('BASEPATH') or exit('No direct script access allowed');

function ocg_now()
{
    return date('Y-m-d H:i:s');
}

function ocg_request_id()
{
    $hdr = function_exists('apache_request_headers') ? apache_request_headers() : [];
    if (isset($hdr['X-Request-Id']) && trim($hdr['X-Request-Id']) !== '') {
        return preg_replace('/[^a-zA-Z0-9\-_.:]/', '', (string) $hdr['X-Request-Id']);
    }
    return 'ocg-' . bin2hex(random_bytes(8)) . '-' . time();
}

function ocg_mask_sensitive($data)
{
    if ((int) get_option('openclaw_gateway_mask_sensitive') !== 1) {
        return $data;
    }

    $sensitiveKeys = ['token', 'api_key', 'apikey', 'authorization', 'password', 'secret', 'auth'];

    if (is_array($data)) {
        $masked = [];
        foreach ($data as $k => $v) {
            $lk = strtolower((string) $k);
            $shouldMask = false;
            foreach ($sensitiveKeys as $needle) {
                if (strpos($lk, $needle) !== false) {
                    $shouldMask = true;
                    break;
                }
            }
            $masked[$k] = $shouldMask ? '***MASKED***' : ocg_mask_sensitive($v);
        }
        return $masked;
    }

    if (is_object($data)) {
        return ocg_mask_sensitive((array) $data);
    }

    return $data;
}

function ocg_decode_json_body()
{
    $raw = file_get_contents('php://input');
    if (!$raw) {
        return [];
    }
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

function ocg_is_write_verb($verb)
{
    return in_array(strtolower((string) $verb), ['post', 'put', 'delete', 'patch'], true);
}

function ocg_error_code($status)
{
    $map = [
        'unauthorized' => 'PORTAL_AUTH_FAILED',
        'scope_denied' => 'PORTAL_SCOPE_DENIED',
        'validation_error' => 'PORTAL_VALIDATION_ERROR',
        'upstream_error' => 'PORTAL_UPSTREAM_ERROR',
        'timeout' => 'PORTAL_TIMEOUT',
    ];
    return isset($map[$status]) ? $map[$status] : 'PORTAL_UPSTREAM_ERROR';
}
