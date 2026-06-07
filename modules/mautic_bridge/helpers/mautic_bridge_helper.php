<?php

if (!defined('BASEPATH')) {
    if (PHP_SAPI !== 'cli') {
        exit('No direct script access allowed');
    }
    define('BASEPATH', true);
}

if (!defined('MAUTIC_BRIDGE_MODULE_NAME')) {
    define('MAUTIC_BRIDGE_MODULE_NAME', 'mautic_bridge');
}

if (!function_exists('mautic_bridge_normalize_contact_payload')) {
    function mautic_bridge_normalize_contact_payload(array $payload): array
    {
        if (!isset($payload['contact'])) {
            foreach ($payload as $key => $eventPayload) {
                if (strpos((string) $key, 'mautic.') !== 0 || !is_array($eventPayload)) {
                    continue;
                }
                if (isset($eventPayload[0]) && is_array($eventPayload[0])) {
                    $payload = $eventPayload[0];
                    break;
                }
                if (isset($eventPayload['contact'])) {
                    $payload = $eventPayload;
                    break;
                }
            }
        }

        if (!isset($payload['contact'])) {
            $nestedContact = mautic_bridge_find_nested_contact_payload($payload);
            if (is_array($nestedContact)) {
                $payload = array_merge($payload, ['contact' => $nestedContact]);
            }
        }

        $contact = isset($payload['contact']) && is_array($payload['contact']) ? $payload['contact'] : $payload;
        $fields = isset($contact['fields']['core']) && is_array($contact['fields']['core']) ? $contact['fields']['core'] : [];

        $read = static function (string $key) use ($contact, $fields) {
            if (array_key_exists($key, $contact)) {
                return $contact[$key];
            }
            if (isset($fields[$key]['value'])) {
                return $fields[$key]['value'];
            }
            if (isset($fields[$key]) && !is_array($fields[$key])) {
                return $fields[$key];
            }
            return null;
        };

        $email = strtolower(trim((string) $read('email')));
        $firstname = mautic_bridge_repair_mojibake(trim((string) $read('firstname')));
        $lastname = mautic_bridge_repair_mojibake(trim((string) $read('lastname')));
        $name = trim($firstname . ' ' . $lastname);
        if ($name === '') {
            $name = mautic_bridge_repair_mojibake(trim((string) ($read('name') ?: $email)));
        }

        $modified = $contact['dateModified'] ?? $contact['date_modified'] ?? $contact['modified_at'] ?? $payload['timestamp'] ?? null;

        return [
            'mautic_contact_id' => isset($contact['id']) ? (int) $contact['id'] : null,
            'email' => $email,
            'firstname' => $firstname,
            'lastname' => $lastname,
            'name' => $name,
            'phonenumber' => trim((string) ($read('phone') ?: $read('mobile') ?: $read('phonenumber'))),
            'company' => mautic_bridge_repair_mojibake(trim((string) ($read('company') ?: $read('companyname')))),
            'address' => mautic_bridge_repair_mojibake(trim((string) $read('address1'))),
            'city' => mautic_bridge_repair_mojibake(trim((string) $read('city'))),
            'state' => mautic_bridge_repair_mojibake(trim((string) $read('state'))),
            'zip' => trim((string) $read('zipcode')),
            'country' => mautic_bridge_repair_mojibake(trim((string) $read('country'))),
            'mautic_modified_at' => mautic_bridge_sql_datetime($modified),
            'campaigns' => mautic_bridge_extract_campaigns($payload),
            'raw' => $payload,
        ];
    }
}

if (!function_exists('mautic_bridge_find_nested_contact_payload')) {
    function mautic_bridge_find_nested_contact_payload(array $payload): ?array
    {
        foreach (['contact', 'lead'] as $key) {
            if (isset($payload[$key]) && is_array($payload[$key])) {
                return $payload[$key];
            }
        }

        $looksLikeContact = isset($payload['fields']['core'])
            || isset($payload['email'])
            || isset($payload['firstname'])
            || isset($payload['lastname']);
        if ($looksLikeContact && isset($payload['id'])) {
            return $payload;
        }

        foreach ($payload as $value) {
            if (!is_array($value)) {
                continue;
            }
            $found = mautic_bridge_find_nested_contact_payload($value);
            if (is_array($found)) {
                return $found;
            }
        }

        return null;
    }
}

if (!function_exists('mautic_bridge_repair_mojibake')) {
    function mautic_bridge_repair_mojibake(string $value): string
    {
        if ($value === '' || !preg_match('/(?:Ã|Â|â[€™€œ€“€”¢€¦]|Ä[\\x80-\\xBF]|Æ[\\x80-\\xBF])/u', $value)) {
            return $value;
        }

        $bytes = '';
        $windows1252Map = [
            0x20AC => 0x80, 0x201A => 0x82, 0x0192 => 0x83, 0x201E => 0x84,
            0x2026 => 0x85, 0x2020 => 0x86, 0x2021 => 0x87, 0x02C6 => 0x88,
            0x2030 => 0x89, 0x0160 => 0x8A, 0x2039 => 0x8B, 0x0152 => 0x8C,
            0x017D => 0x8E, 0x2018 => 0x91, 0x2019 => 0x92, 0x201C => 0x93,
            0x201D => 0x94, 0x2022 => 0x95, 0x2013 => 0x96, 0x2014 => 0x97,
            0x02DC => 0x98, 0x2122 => 0x99, 0x0161 => 0x9A, 0x203A => 0x9B,
            0x0153 => 0x9C, 0x017E => 0x9E, 0x0178 => 0x9F,
        ];
        $chars = preg_split('//u', $value, -1, PREG_SPLIT_NO_EMPTY);
        if ($chars === false || !function_exists('mb_ord')) {
            return $value;
        }

        foreach ($chars as $char) {
            $codepoint = mb_ord($char, 'UTF-8');
            if ($codepoint <= 255) {
                $bytes .= chr($codepoint);
                continue;
            }
            if (isset($windows1252Map[$codepoint])) {
                $bytes .= chr($windows1252Map[$codepoint]);
                continue;
            }

            return $value;
        }

        if ($bytes === '' || (function_exists('mb_check_encoding') && !mb_check_encoding($bytes, 'UTF-8'))) {
            return $value;
        }

        return $bytes;
    }
}

if (!function_exists('mautic_bridge_extract_campaigns')) {
    function mautic_bridge_extract_campaigns(array $payload): array
    {
        $campaigns = [];
        foreach (['campaigns', 'campaign'] as $key) {
            if (!isset($payload[$key])) {
                continue;
            }
            $items = $payload[$key];
            if ($key === 'campaign' && is_array($items) && isset($items['id'])) {
                $items = [$items];
            }
            if (!is_array($items)) {
                continue;
            }
            foreach ($items as $item) {
                if (!is_array($item)) {
                    continue;
                }
                $id = isset($item['id']) ? (int) $item['id'] : null;
                if (!$id) {
                    continue;
                }
                $campaigns[] = [
                    'id' => $id,
                    'alias' => isset($item['alias']) ? trim((string) $item['alias']) : '',
                    'name' => isset($item['name']) ? trim((string) $item['name']) : '',
                ];
            }
        }

        return $campaigns;
    }
}

if (!function_exists('mautic_bridge_campaign_tags')) {
    function mautic_bridge_campaign_tags(array $campaigns): array
    {
        $tags = [];
        foreach ($campaigns as $campaign) {
            if (!is_array($campaign) || empty($campaign['id'])) {
                continue;
            }
            $slug = !empty($campaign['alias']) ? $campaign['alias'] : (string) $campaign['id'];
            $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9_-]+/', '-', (string) $slug), '-'));
            if ($slug !== '') {
                $tags[] = 'mautic:campaign:' . $slug;
            }
        }

        return array_values(array_unique($tags));
    }
}

if (!function_exists('mautic_bridge_sql_datetime')) {
    function mautic_bridge_sql_datetime($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_numeric($value)) {
            return gmdate('Y-m-d H:i:s', (int) $value);
        }
        $timestamp = strtotime((string) $value);
        if ($timestamp === false) {
            return null;
        }

        return gmdate('Y-m-d H:i:s', $timestamp);
    }
}

if (!function_exists('mautic_bridge_newer_side')) {
    function mautic_bridge_newer_side($incoming, $local): string
    {
        $incomingTs = $incoming ? strtotime((string) $incoming) : 0;
        $localTs = $local ? strtotime((string) $local) : 0;
        if ($incomingTs > $localTs) {
            return 'incoming';
        }
        if ($localTs > $incomingTs) {
            return 'local';
        }

        return 'equal';
    }
}

if (!function_exists('mautic_bridge_set_origin')) {
    function mautic_bridge_set_origin(?string $origin): void
    {
        $GLOBALS['mautic_bridge_sync_origin'] = $origin;
    }
}

if (!function_exists('mautic_bridge_get_origin')) {
    function mautic_bridge_get_origin(): ?string
    {
        return $GLOBALS['mautic_bridge_sync_origin'] ?? null;
    }
}

if (!function_exists('mautic_bridge_with_origin')) {
    function mautic_bridge_with_origin(string $origin, callable $callback)
    {
        $previous = mautic_bridge_get_origin();
        mautic_bridge_set_origin($origin);
        try {
            return $callback();
        } finally {
            mautic_bridge_set_origin($previous);
        }
    }
}

if (!function_exists('mautic_bridge_json_response')) {
    function mautic_bridge_json_response(array $payload, int $status = 200): void
    {
        if (function_exists('http_response_code')) {
            http_response_code($status);
        }
        header('Content-Type: application/json');
        echo json_encode($payload);
    }
}
