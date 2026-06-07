<?php

defined('BASEPATH') or define('BASEPATH', true);

if (!function_exists('item_sku_manager_normalize_text')) {
    function item_sku_manager_normalize_text($value)
    {
        $value = html_entity_decode(strip_tags((string) $value), ENT_QUOTES, 'UTF-8');
        $value = preg_replace('/\s+/u', ' ', trim($value));

        if (function_exists('transliterator_transliterate')) {
            $converted = transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $value);
            if ($converted !== false) {
                $value = $converted;
            }
        } elseif (function_exists('iconv')) {
            $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
            if ($converted !== false) {
                $value = $converted;
            }
        }

        $value = strtolower($value);
        $value = preg_replace('/[^a-z0-9]+/', ' ', $value);

        return trim(preg_replace('/\s+/', ' ', $value));
    }
}

if (!function_exists('item_sku_manager_price_equal')) {
    function item_sku_manager_price_equal($left, $right, $delta = 0.01)
    {
        if ($left === null || $right === null || $left === '' || $right === '') {
            return false;
        }

        return abs((float) $left - (float) $right) <= $delta;
    }
}

if (!function_exists('item_sku_manager_match_line')) {
    function item_sku_manager_match_line(array $line, array $masters, array $aliases = [])
    {
        $lineName = item_sku_manager_normalize_text($line['description'] ?? '');
        $lineUnit = item_sku_manager_normalize_text($line['unit'] ?? '');
        $lineRate = $line['rate'] ?? null;

        $mastersById = [];
        foreach ($masters as $master) {
            $masterId = (int) ($master['id'] ?? 0);
            if ($masterId > 0) {
                $mastersById[$masterId] = $master;
            }
        }

        $lineSku = trim((string) ($line['item_sku'] ?? ''));
        $lineMasterId = (int) ($line['item_master_id'] ?? 0);
        if ($lineSku !== '' || $lineMasterId > 0) {
            foreach ($masters as $master) {
                $masterId = (int) ($master['id'] ?? 0);
                $masterSku = trim((string) ($master['sku_code'] ?? ''));
                if (($lineSku !== '' && $masterSku !== '' && strcasecmp($lineSku, $masterSku) === 0)
                    || ($lineMasterId > 0 && $masterId === $lineMasterId)) {
                    return [
                        'matched'        => true,
                        'item_master_id' => $masterId,
                        'sku_code'       => $masterSku ?: ('ITEM-' . $masterId),
                        'confidence'     => 100,
                        'source'         => 'sku',
                        'master'         => $master,
                    ];
                }
            }
        }

        foreach ($masters as $master) {
            $masterName = item_sku_manager_normalize_text($master['description'] ?? '');
            $masterUnit = item_sku_manager_normalize_text($master['unit'] ?? '');
            if ($masterName !== '' && $masterName === $lineName) {
                $confidence = 92;
                if (item_sku_manager_price_equal($lineRate, $master['rate'] ?? null)) {
                    $confidence += 6;
                }
                if ($lineUnit !== '' && $masterUnit !== '' && $lineUnit === $masterUnit) {
                    $confidence += 2;
                }

                return [
                    'matched'        => true,
                    'item_master_id' => (int) $master['id'],
                    'sku_code'       => (string) ($master['sku_code'] ?: ('ITEM-' . (int) $master['id'])),
                    'confidence'     => min(100, $confidence),
                    'source'         => 'master',
                    'master'         => $master,
                ];
            }
        }

        foreach ($aliases as $alias) {
            if ((int) ($alias['active'] ?? 1) !== 1) {
                continue;
            }

            $aliasName = (string) ($alias['normalized_alias'] ?? '');
            if ($aliasName === '') {
                $aliasName = item_sku_manager_normalize_text($alias['alias_name'] ?? '');
            }

            if ($aliasName === '' || $aliasName !== $lineName) {
                continue;
            }

            $masterId = (int) ($alias['item_id'] ?? 0);
            $master   = $mastersById[$masterId] ?? [];

            return [
                'matched'        => true,
                'item_master_id' => $masterId,
                'sku_code'       => (string) ($alias['sku_code'] ?: ($master['sku_code'] ?? ('ITEM-' . $masterId))),
                'confidence'     => 88,
                'source'         => 'alias',
                'master'         => $master,
            ];
        }

        return [
            'matched'        => false,
            'item_master_id' => null,
            'sku_code'       => null,
            'confidence'     => 0,
            'source'         => 'unmatched',
            'master'         => [],
        ];
    }
}

if (!function_exists('item_sku_manager_build_snapshot')) {
    function item_sku_manager_build_snapshot(array $line, array $master, $source)
    {
        return [
            'source' => (string) $source,
            'line'   => [
                'description'      => (string) ($line['description'] ?? ''),
                'long_description' => (string) ($line['long_description'] ?? ''),
                'rate'             => (string) ($line['rate'] ?? ''),
                'qty'              => (string) ($line['qty'] ?? ''),
                'unit'             => (string) ($line['unit'] ?? ''),
                'rel_type'         => (string) ($line['rel_type'] ?? ''),
                'rel_id'           => (int) ($line['rel_id'] ?? 0),
            ],
            'master' => [
                'id'               => (int) ($master['id'] ?? 0),
                'sku_code'         => (string) ($master['sku_code'] ?? ''),
                'description'      => (string) ($master['description'] ?? ''),
                'long_description' => (string) ($master['long_description'] ?? ''),
                'rate'             => (string) ($master['rate'] ?? ''),
                'unit'             => (string) ($master['unit'] ?? ''),
                'tax'              => (string) ($master['tax'] ?? ''),
            ],
        ];
    }
}

if (!function_exists('item_sku_manager_snapshot_hash')) {
    function item_sku_manager_snapshot_hash(array $snapshot)
    {
        return hash('sha256', json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}

if (!function_exists('item_sku_manager_backfill_update_payload')) {
    function item_sku_manager_backfill_update_payload(array $line, array $match, array $snapshot, $matchedAt = null)
    {
        return [
            'item_sku'             => $match['sku_code'],
            'item_master_id'       => $match['item_master_id'],
            'item_snapshot_json'   => json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'item_snapshot_hash'   => item_sku_manager_snapshot_hash($snapshot),
            'sku_matched_at'       => $matchedAt ?: date('Y-m-d H:i:s'),
            'sku_match_confidence' => (int) $match['confidence'],
            'sku_match_source'     => (string) $match['source'],
        ];
    }
}

if (!function_exists('item_sku_manager_generate_sku')) {
    function item_sku_manager_generate_sku($description, $id)
    {
        $normalized = item_sku_manager_normalize_text($description);
        $parts      = array_filter(explode(' ', $normalized));
        $slug       = strtoupper(implode('-', array_slice($parts, 0, 5)));

        if ($slug === '') {
            $slug = 'ITEM';
        }

        return substr($slug . '-' . (int) $id, 0, 191);
    }
}
