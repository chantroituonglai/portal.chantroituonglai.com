<?php

defined('BASEPATH') or exit('No direct script access allowed');

require_once APP_MODULES_PATH . 'item_sku_manager/helpers/item_sku_manager_helper.php';

class Item_sku_manager_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->ensure_schema();
    }

    public function ensure_schema()
    {
        if (
            function_exists('get_option')
            && get_option('item_sku_manager_schema_version') === '110'
            && $this->db->field_exists('item_sku', db_prefix() . 'itemable')
            && $this->db->table_exists(db_prefix() . 'item_sku_aliases')
            && $this->db->table_exists(db_prefix() . 'itemable_sku_backfill_logs')
            && $this->db->table_exists(db_prefix() . 'item_sku_metadata')
            && $this->db->table_exists(db_prefix() . 'item_sku_trace_logs')
        ) {
            return;
        }

        require APP_MODULES_PATH . 'item_sku_manager/install.php';

        if (function_exists('update_option')) {
            update_option('item_sku_manager_schema_version', '110');
        }
    }

    public function get_summary()
    {
        $itemable = db_prefix() . 'itemable';
        $items    = db_prefix() . 'items';
        $logs     = db_prefix() . 'itemable_sku_backfill_logs';

        return [
            'master_items'        => (int) $this->db->count_all_results($items),
            'master_items_no_sku' => (int) $this->db->where("(sku_code IS NULL OR sku_code = '')", null, false)->count_all_results($items),
            'sales_lines'         => (int) $this->db->count_all_results($itemable),
            'sales_lines_mapped'  => (int) $this->db->where("item_sku IS NOT NULL AND item_sku <> ''", null, false)->count_all_results($itemable),
            'sales_lines_pending' => (int) $this->db->where("(item_sku IS NULL OR item_sku = '')", null, false)->count_all_results($itemable),
            'backfill_logs'       => $this->db->table_exists($logs) ? (int) $this->db->count_all_results($logs) : 0,
        ];
    }

    public function get_recent_logs($limit = 25)
    {
        $this->db->order_by('id', 'DESC');
        $this->db->limit((int) $limit);

        return $this->db->get(db_prefix() . 'itemable_sku_backfill_logs')->result_array();
    }

    public function get_master_items()
    {
        $this->db->select('id, description, long_description, rate, unit, tax, sku_code, commodity_code');
        $rows = $this->db->get(db_prefix() . 'items')->result_array();

        foreach ($rows as &$row) {
            if (empty($row['sku_code'])) {
                $row['sku_code'] = $row['commodity_code'] ?: item_sku_manager_generate_sku($row['description'], $row['id']);
            }
        }

        return $rows;
    }

    public function get_aliases()
    {
        return $this->db->where('active', 1)->get(db_prefix() . 'item_sku_aliases')->result_array();
    }

    public function snapshot_sales_item($itemableId, $source = 'runtime')
    {
        $line = $this->get_sales_line((int) $itemableId);
        if (!$line) {
            return ['success' => false, 'message' => 'Sales line not found'];
        }

        $match = item_sku_manager_match_line($line, $this->get_master_items(), $this->get_aliases());
        if (empty($match['matched'])) {
            $this->log_backfill($line, $match, 'unmatched', 'No matching SKU found');
            return ['success' => false, 'message' => 'No matching SKU found'];
        }

        $snapshot = item_sku_manager_build_snapshot($line, $match['master'] ?? [], $source);
        $payload  = item_sku_manager_backfill_update_payload($line, $match, $snapshot);

        $this->db->where('id', (int) $itemableId);
        $this->db->update(db_prefix() . 'itemable', $payload);
        $this->log_backfill($line, $match, 'mapped', $source);

        return ['success' => true, 'sku' => $match['sku_code'], 'itemable_id' => (int) $itemableId];
    }

    public function run_backfill($mode = 'dry_run', $limit = 100, $offset = 0, $force = false)
    {
        $mode   = $mode === 'write' ? 'write' : 'dry_run';
        $limit  = max(1, min(500, (int) $limit));
        $offset = max(0, (int) $offset);

        $this->db->from(db_prefix() . 'itemable');
        if (!$force) {
            $this->db->where("(item_sku IS NULL OR item_sku = '')", null, false);
        }
        $totalPending = (int) $this->db->count_all_results();

        $this->db->from(db_prefix() . 'itemable');
        if (!$force) {
            $this->db->where("(item_sku IS NULL OR item_sku = '')", null, false);
        }
        $this->db->order_by('id', 'ASC');
        $this->db->limit($limit, $offset);
        $lines = $this->db->get()->result_array();

        $masters = $this->get_master_items();
        $aliases = $this->get_aliases();
        $result  = [
            'success'       => true,
            'mode'          => $mode,
            'limit'         => $limit,
            'offset'        => $offset,
            'total_pending' => $totalPending,
            'processed'     => 0,
            'mapped'        => 0,
            'unmatched'     => 0,
            'written'       => 0,
            'next_offset'   => $offset + count($lines),
            'done'          => ($offset + count($lines)) >= $totalPending,
            'preview'       => [],
        ];

        foreach ($lines as $line) {
            $result['processed']++;
            $match = item_sku_manager_match_line($line, $masters, $aliases);

            if (empty($match['matched'])) {
                $result['unmatched']++;
                if ($mode === 'write') {
                    $this->log_backfill($line, $match, 'unmatched', 'No matching SKU found');
                }
                $result['preview'][] = $this->preview_row($line, $match);
                continue;
            }

            $result['mapped']++;
            $snapshot = item_sku_manager_build_snapshot($line, $match['master'] ?? [], 'backfill');
            $payload  = item_sku_manager_backfill_update_payload($line, $match, $snapshot);

            if ($mode === 'write') {
                $this->db->where('id', (int) $line['id']);
                $this->db->update(db_prefix() . 'itemable', $payload);
                $this->log_backfill($line, $match, 'mapped', 'backfill write');
                $result['written']++;
            }

            $result['preview'][] = $this->preview_row($line, $match);
        }

        return $result;
    }

    public function get_sales_line($itemableId)
    {
        return $this->db->where('id', (int) $itemableId)->get(db_prefix() . 'itemable')->row_array();
    }

    public function get_item_row($itemId)
    {
        return $this->db->where('id', (int) $itemId)->get(db_prefix() . 'items')->row_array();
    }

    public function get_item_meta($itemId)
    {
        $meta = $this->db->where('item_id', (int) $itemId)->get(db_prefix() . 'item_sku_metadata')->row_array();
        if ($meta) {
            return $meta;
        }

        return [
            'item_id'             => (int) $itemId,
            'sku_version'         => 'v1.0',
            'sku_status'          => 'active',
            'effective_from'      => null,
            'effective_to'        => null,
            'replaced_by_item_id' => null,
            'archived_at'         => null,
            'archived_by'         => null,
        ];
    }

    public function save_item_meta($itemId, array $data)
    {
        $itemId = (int) $itemId;
        $now    = date('Y-m-d H:i:s');
        $fields = [
            'sku_version',
            'sku_status',
            'effective_from',
            'effective_to',
            'replaced_by_item_id',
            'archived_at',
            'archived_by',
        ];

        $payload = [];
        foreach ($fields as $field) {
            if (array_key_exists($field, $data)) {
                $payload[$field] = $data[$field] === '' ? null : $data[$field];
            }
        }
        $payload['updated_at'] = $now;

        $existing = $this->db->where('item_id', $itemId)->get(db_prefix() . 'item_sku_metadata')->row_array();
        if ($existing) {
            $this->db->where('item_id', $itemId)->update(db_prefix() . 'item_sku_metadata', $payload);
            return $this->db->affected_rows() > 0;
        }

        $payload['item_id']    = $itemId;
        $payload['created_at'] = $now;
        $this->db->insert(db_prefix() . 'item_sku_metadata', $payload);

        return $this->db->insert_id() > 0;
    }

    public function sanitize_item_payload(array $data)
    {
        $columns = $this->db->list_fields(db_prefix() . 'items');
        $allowed = [
            'description',
            'long_description',
            'rate',
            'tax',
            'tax2',
            'group_id',
            'unit',
            'sku_code',
            'sku_name',
            'commodity_code',
            'commodity_barcode',
            'commodity_type',
            'warehouse_id',
            'origin',
            'color_id',
            'color',
            'style_id',
            'model_id',
            'size_id',
            'unit_id',
            'purchase_price',
            'sub_group',
            'active',
            'long_descriptions',
            'without_checking_warehouse',
            'attributes',
            'parent_attributes',
            'profif_ratio',
            'guarantee',
        ];

        foreach ($data as $field => $value) {
            if (strpos($field, 'rate_currency_') === 0) {
                $allowed[] = $field;
            }
        }

        $payload = [];
        foreach ($allowed as $field) {
            if (array_key_exists($field, $data) && in_array($field, $columns)) {
                $payload[$field] = is_string($data[$field]) ? trim($data[$field]) : $data[$field];
            }
        }

        foreach (['rate', 'purchase_price'] as $moneyField) {
            if (isset($payload[$moneyField])) {
                $payload[$moneyField] = $this->normalize_money_value($payload[$moneyField]);
            }
        }

        foreach (['group_id', 'warehouse_id', 'commodity_type', 'unit_id', 'sub_group', 'style_id', 'model_id', 'size_id', 'color', 'color_id'] as $intField) {
            if (isset($payload[$intField]) && $payload[$intField] === '') {
                $payload[$intField] = null;
            }
        }

        if (array_key_exists('without_checking_warehouse', $payload)) {
            $payload['without_checking_warehouse'] = (int) $payload['without_checking_warehouse'];
        }

        if (array_key_exists('active', $payload)) {
            $payload['active'] = (int) $payload['active'];
        } elseif (in_array('active', $columns)) {
            $payload['active'] = 1;
        }

        if (isset($data['custom_fields'])) {
            $payload['custom_fields'] = $data['custom_fields'];
        }

        if (isset($payload['group_id']) && $payload['group_id'] === '') {
            $payload['group_id'] = 0;
        }

        foreach (['tax', 'tax2'] as $taxField) {
            if (isset($payload[$taxField]) && $payload[$taxField] === '') {
                $payload[$taxField] = null;
            }
        }

        if (!empty($payload['sku_code'])) {
            $payload['sku_code'] = strtoupper(preg_replace('/[^A-Z0-9._-]+/', '-', $payload['sku_code']));
            $payload['sku_code'] = trim($payload['sku_code'], '-');
        }

        if (empty($payload['sku_code']) && in_array('sku_code', $columns)) {
            $payload['sku_code'] = $this->generate_warehouse_sku($payload);
        }

        if (!empty($payload['sku_code']) && empty($payload['sku_name']) && in_array('sku_name', $columns)) {
            $payload['sku_name'] = $payload['sku_code'];
        }

        if (!empty($payload['sku_code']) && empty($payload['commodity_code']) && in_array('commodity_code', $columns)) {
            $payload['commodity_code'] = $payload['sku_code'];
        }

        if (!empty($payload['unit_id']) && in_array('unit', $columns) && empty($payload['unit'])) {
            $payload['unit'] = $this->warehouse_unit_label($payload['unit_id']);
        }

        if (function_exists('get_warehouse_option') && get_warehouse_option('barcode_with_sku_code') == 1 && !empty($payload['sku_code']) && in_array('commodity_barcode', $columns)) {
            $payload['commodity_barcode'] = $payload['sku_code'];
        }

        return $payload;
    }

    public function sku_exists($sku, $excludeItemId = null)
    {
        $sku = trim((string) $sku);
        if ($sku === '') {
            return false;
        }

        $this->db->where('sku_code', $sku);
        if ($excludeItemId) {
            $this->db->where('id !=', (int) $excludeItemId);
        }

        return (int) $this->db->count_all_results(db_prefix() . 'items') > 0;
    }

    public function create_item(array $itemData, array $metaData = [])
    {
        $this->load->model('invoice_items_model');
        $sidecarData = [
            'tags'                 => $itemData['tags'] ?? null,
            'inventory_number_min' => $itemData['inventory_number_min'] ?? null,
            'inventory_number_max' => $itemData['inventory_number_max'] ?? null,
        ];
        $itemData = $this->sanitize_item_payload($itemData);

        $id = $this->invoice_items_model->add($itemData);
        if (!$id) {
            return false;
        }

        $meta = $this->normalize_meta_payload($metaData);
        if (empty($meta['sku_version'])) {
            $meta['sku_version'] = 'v1.0';
        }
        if (empty($meta['sku_status'])) {
            $meta['sku_status'] = 'active';
        }
        $this->save_item_meta($id, $meta);
        $this->record_trace($id, 'created', null, null, $itemData['description'] ?? '', ['source' => 'item_detail']);
        $this->seed_alias_for_item($id);
        $this->sync_warehouse_sidecars($id, $sidecarData, 'created');

        return $id;
    }

    public function update_item($itemId, array $itemData, array $metaData = [])
    {
        $this->load->model('invoice_items_model');
        $itemId    = (int) $itemId;
        $oldItem   = $this->get_item_row($itemId);
        $oldMeta   = $this->get_item_meta($itemId);
        $sidecarData = [
            'tags'                 => $itemData['tags'] ?? null,
            'inventory_number_min' => $itemData['inventory_number_min'] ?? null,
            'inventory_number_max' => $itemData['inventory_number_max'] ?? null,
        ];
        $itemData  = $this->sanitize_item_payload($itemData);
        $metaData  = $this->normalize_meta_payload($metaData);
        $editData  = $itemData;
        $editData['itemid'] = $itemId;

        $updated = $this->invoice_items_model->edit($editData);
        $this->save_item_meta($itemId, $metaData);
        $newItem = $this->get_item_row($itemId);
        $newMeta = $this->get_item_meta($itemId);

        $this->record_diff($itemId, $oldItem ?: [], $newItem ?: [], [
            'description',
            'long_description',
            'rate',
            'tax',
            'tax2',
            'group_id',
            'unit',
            'sku_code',
            'sku_name',
            'commodity_code',
            'commodity_barcode',
            'commodity_type',
            'warehouse_id',
            'origin',
            'color_id',
            'color',
            'style_id',
            'model_id',
            'size_id',
            'unit_id',
            'purchase_price',
            'sub_group',
            'active',
            'long_descriptions',
            'without_checking_warehouse',
            'attributes',
            'parent_attributes',
            'profif_ratio',
            'guarantee',
        ], 'updated');
        $this->record_diff($itemId, $oldMeta ?: [], $newMeta ?: [], [
            'sku_version',
            'sku_status',
            'effective_from',
            'effective_to',
            'replaced_by_item_id',
        ], 'metadata_updated');
        $this->seed_alias_for_item($itemId);
        $this->sync_warehouse_sidecars($itemId, $sidecarData, 'updated');

        return $updated || $oldMeta != $newMeta;
    }

    public function archive_item($itemId)
    {
        $itemId = (int) $itemId;
        $old    = $this->get_item_meta($itemId);
        $this->save_item_meta($itemId, [
            'sku_status'  => 'archived',
            'archived_at' => date('Y-m-d H:i:s'),
            'archived_by' => get_staff_user_id(),
        ]);
        $this->record_trace($itemId, 'archived', 'sku_status', $old['sku_status'] ?? null, 'archived', []);
        if ($this->db->field_exists('active', db_prefix() . 'items')) {
            $this->db->where('id', $itemId)->update(db_prefix() . 'items', ['active' => 0]);
            $this->record_trace($itemId, 'warehouse_sync', 'active', 1, 0, ['source' => 'archive']);
        }
        log_activity('Invoice Item SKU Archived [ID: ' . $itemId . ']');

        return true;
    }

    public function create_version($itemId)
    {
        $this->load->model('invoice_items_model');
        $itemId  = (int) $itemId;
        $oldItem = $this->get_item_row($itemId);
        if (!$oldItem) {
            return false;
        }

        $oldMeta     = $this->get_item_meta($itemId);
        $newVersion  = $this->next_version($oldMeta['sku_version'] ?? 'v1.0');
        $baseSku     = $oldItem['sku_code'] ?: ($oldItem['commodity_code'] ?: item_sku_manager_generate_sku($oldItem['description'], $itemId));
        $newSku      = $this->unique_version_sku($baseSku, $newVersion);
        $custom      = $this->get_item_custom_fields_payload($itemId);
        $cloneFields = [
            'description',
            'long_description',
            'rate',
            'tax',
            'tax2',
            'group_id',
            'unit',
            'commodity_barcode',
            'commodity_type',
            'warehouse_id',
            'origin',
            'color_id',
            'color',
            'style_id',
            'model_id',
            'size_id',
            'unit_id',
            'purchase_price',
            'sub_group',
            'active',
            'long_descriptions',
            'without_checking_warehouse',
            'attributes',
            'parent_attributes',
            'profif_ratio',
            'guarantee',
        ];

        $data = [];
        foreach ($cloneFields as $field) {
            if (array_key_exists($field, $oldItem)) {
                $data[$field] = $oldItem[$field];
            }
        }

        foreach ($oldItem as $field => $value) {
            if (strpos($field, 'rate_currency_') === 0) {
                $data[$field] = $value;
            }
        }

        $data['sku_code']       = $newSku;
        $data['sku_name']       = $newSku;
        $data['commodity_code'] = $newSku;
        $data['commodity_barcode'] = function_exists('get_warehouse_option') && get_warehouse_option('barcode_with_sku_code') == 1 ? $newSku : ($data['commodity_barcode'] ?? '');
        if (!empty($custom)) {
            $data['custom_fields'] = ['items' => $custom];
        }

        $newId = $this->invoice_items_model->add($data);
        if (!$newId) {
            return false;
        }

        $this->save_item_meta($newId, [
            'sku_version'    => $newVersion,
            'sku_status'     => 'active',
            'effective_from' => date('Y-m-d'),
            'effective_to'   => null,
        ]);
        $this->save_item_meta($itemId, [
            'sku_status'          => 'deprecated',
            'effective_to'        => date('Y-m-d'),
            'replaced_by_item_id' => $newId,
        ]);

        $this->seed_alias_for_item($newId);
        $this->copy_item_tags($itemId, $newId);
        $this->sync_warehouse_sidecars($newId, $data, 'version_created');
        $this->record_trace($itemId, 'version_created', 'replaced_by_item_id', null, $newId, [
            'new_version' => $newVersion,
            'new_sku'     => $newSku,
        ]);
        $this->record_trace($newId, 'created_from_version', null, null, $itemId, [
            'source_item_id'  => $itemId,
            'source_sku_code' => $baseSku,
        ]);
        log_activity('Invoice Item SKU Version Created [From ID: ' . $itemId . ', New ID: ' . $newId . ']');

        return $newId;
    }

    public function get_usage_stats($itemId)
    {
        $itemId = (int) $itemId;
        $stats  = [
            'active_proposals'      => 0,
            'draft_invoices'        => 0,
            'total_historical_uses' => 0,
        ];

        $this->db->where('item_master_id', $itemId);
        $stats['total_historical_uses'] = (int) $this->db->count_all_results(db_prefix() . 'itemable');

        $this->db->from(db_prefix() . 'itemable i');
        $this->db->join(db_prefix() . 'proposals p', 'p.id = i.rel_id AND i.rel_type = "proposal"', 'inner');
        $this->db->where('i.item_master_id', $itemId);
        $this->db->where('p.status !=', 3);
        $stats['active_proposals'] = (int) $this->db->count_all_results();

        $this->db->from(db_prefix() . 'itemable i');
        $this->db->join(db_prefix() . 'invoices inv', 'inv.id = i.rel_id AND i.rel_type = "invoice"', 'inner');
        $this->db->where('i.item_master_id', $itemId);
        $this->db->where('inv.status', 6);
        $stats['draft_invoices'] = (int) $this->db->count_all_results();

        return $stats;
    }

    public function get_linked_records($itemId, $limit = 50)
    {
        $this->db->where('item_master_id', (int) $itemId);
        $this->db->order_by('id', 'DESC');
        $this->db->limit((int) $limit);
        $rows = $this->db->get(db_prefix() . 'itemable')->result_array();

        foreach ($rows as &$row) {
            $row['admin_url'] = $this->linked_record_url($row['rel_type'], (int) $row['rel_id']);
        }

        return $rows;
    }

    public function get_trace_logs($itemId, $limit = 100)
    {
        $this->db->select('l.*, s.firstname, s.lastname, s.profile_image');
        $this->db->from(db_prefix() . 'item_sku_trace_logs l');
        $this->db->join(db_prefix() . 'staff s', 's.staffid = l.staff_id', 'left');
        $this->db->where('l.item_id', (int) $itemId);
        $this->db->order_by('l.id', 'DESC');
        $this->db->limit((int) $limit);

        return $this->db->get()->result_array();
    }

    public function search_replacement_sku($q, $excludeItemId = null)
    {
        $this->db->select('i.id, i.description, i.sku_code, i.commodity_code, m.sku_version, m.sku_status');
        $this->db->from(db_prefix() . 'items i');
        $this->db->join(db_prefix() . 'item_sku_metadata m', 'm.item_id = i.id', 'left');
        if ($excludeItemId) {
            $this->db->where('i.id !=', (int) $excludeItemId);
        }
        $this->db->group_start();
        $this->db->like('i.description', $q);
        $this->db->or_like('i.sku_code', $q);
        $this->db->or_like('i.commodity_code', $q);
        $this->db->group_end();
        $this->db->order_by('i.description', 'ASC');
        $this->db->limit(20);
        $rows = $this->db->get()->result_array();
        $result = [];

        foreach ($rows as $row) {
            $sku = $row['sku_code'] ?: $row['commodity_code'];
            $result[] = [
                'id'   => (int) $row['id'],
                'text' => ($sku ? '[' . $sku . '] ' : '') . $row['description'] . (!empty($row['sku_version']) ? ' ' . $row['sku_version'] : ''),
            ];
        }

        return $result;
    }

    public function record_trace($itemId, $eventType, $fieldName = null, $oldValue = null, $newValue = null, array $context = [])
    {
        $this->db->insert(db_prefix() . 'item_sku_trace_logs', [
            'item_id'    => (int) $itemId,
            'event_type' => (string) $eventType,
            'field_name' => $fieldName,
            'old_value'  => $this->trace_value($oldValue),
            'new_value'  => $this->trace_value($newValue),
            'staff_id'   => function_exists('get_staff_user_id') ? get_staff_user_id() : null,
            'context'    => !empty($context) ? json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->db->insert_id();
    }

    private function preview_row(array $line, array $match)
    {
        return [
            'itemable_id' => (int) ($line['id'] ?? 0),
            'rel_type'    => (string) ($line['rel_type'] ?? ''),
            'rel_id'      => (int) ($line['rel_id'] ?? 0),
            'description' => (string) ($line['description'] ?? ''),
            'rate'        => (string) ($line['rate'] ?? ''),
            'matched'     => !empty($match['matched']),
            'sku'         => $match['sku_code'] ?? null,
            'master_id'   => $match['item_master_id'] ?? null,
            'confidence'  => $match['confidence'] ?? 0,
            'source'      => $match['source'] ?? 'unmatched',
        ];
    }

    private function log_backfill(array $line, array $match, $status, $message)
    {
        $this->db->insert(db_prefix() . 'itemable_sku_backfill_logs', [
            'itemable_id'     => (int) ($line['id'] ?? 0),
            'rel_type'        => (string) ($line['rel_type'] ?? ''),
            'rel_id'          => (int) ($line['rel_id'] ?? 0),
            'old_description' => (string) ($line['description'] ?? ''),
            'matched_sku'     => $match['sku_code'] ?? null,
            'matched_item_id' => $match['item_master_id'] ?? null,
            'confidence'      => (int) ($match['confidence'] ?? 0),
            'status'          => (string) $status,
            'message'         => (string) $message,
            'created_at'      => date('Y-m-d H:i:s'),
        ]);
    }

    private function normalize_meta_payload(array $data)
    {
        $payload = [];
        foreach (['sku_version', 'sku_status', 'effective_from', 'effective_to', 'replaced_by_item_id'] as $field) {
            if (!array_key_exists($field, $data)) {
                continue;
            }
            $payload[$field] = is_string($data[$field]) ? trim($data[$field]) : $data[$field];
        }

        foreach (['effective_from', 'effective_to'] as $dateField) {
            if (!empty($payload[$dateField])) {
                $payload[$dateField] = to_sql_date($payload[$dateField]);
            }
        }

        if (empty($payload['sku_status'])) {
            $payload['sku_status'] = 'active';
        }

        return $payload;
    }

    private function record_diff($itemId, array $old, array $new, array $fields, $eventType)
    {
        foreach ($fields as $field) {
            $oldValue = array_key_exists($field, $old) ? (string) $old[$field] : '';
            $newValue = array_key_exists($field, $new) ? (string) $new[$field] : '';
            if ($oldValue === $newValue) {
                continue;
            }
            $this->record_trace($itemId, $eventType, $field, $oldValue, $newValue, []);
        }
    }

    private function trace_value($value)
    {
        if (is_array($value) || is_object($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return $value === null ? null : (string) $value;
    }

    private function normalize_money_value($value)
    {
        if ($value === '' || $value === null) {
            return $value;
        }

        if (function_exists('reformat_currency_j') && is_string($value) && preg_match('/[, ]/', $value)) {
            return reformat_currency_j($value);
        }

        return $value;
    }

    private function generate_warehouse_sku(array $payload)
    {
        if (!function_exists('get_warehouse_option')) {
            return '';
        }

        $sku = '';
        if (class_exists('Warehouse_model') || file_exists(APP_MODULES_PATH . 'warehouse/models/Warehouse_model.php')) {
            $this->load->model('warehouse/warehouse_model');
            if (method_exists($this->warehouse_model, 'create_sku_code')) {
                $sku = $this->warehouse_model->create_sku_code($payload['group_id'] ?? '', $payload['sub_group'] ?? '');
            }
        }

        $prefix = (string) get_warehouse_option('item_sku_prefix');
        if ($sku !== '' && $prefix !== '' && strpos($sku, $prefix) !== 0) {
            $sku = $prefix . $sku;
        }

        return strtoupper(trim((string) $sku));
    }

    private function warehouse_unit_label($unitId)
    {
        if (!function_exists('get_unit_type')) {
            return '';
        }

        $unit = get_unit_type($unitId);
        if (!$unit) {
            return '';
        }

        return $unit->unit_symbol ?: $unit->unit_name;
    }

    private function sync_warehouse_sidecars($itemId, array $itemData, $event)
    {
        $this->sync_item_tags($itemId, $itemData['tags'] ?? null);
        $this->sync_inventory_commodity_min($itemId, $itemData);
        $this->record_trace($itemId, 'warehouse_sync', null, null, 'ok', ['event' => $event]);
    }

    private function sync_item_tags($itemId, $tags)
    {
        if ($tags === null || !function_exists('handle_tags_save')) {
            return;
        }

        handle_tags_save($tags, (int) $itemId, 'item_tags');
    }

    private function copy_item_tags($fromItemId, $toItemId)
    {
        if (!function_exists('get_tags_in') || !function_exists('prep_tags_input')) {
            return;
        }

        $tags = prep_tags_input(get_tags_in((int) $fromItemId, 'item_tags'));
        if ($tags !== '') {
            $this->sync_item_tags($toItemId, $tags);
        }
    }

    private function sync_inventory_commodity_min($itemId, array $sidecarData = [])
    {
        if (!$this->db->table_exists(db_prefix() . 'inventory_commodity_min')) {
            return;
        }

        $item = $this->get_item_row($itemId);
        if (!$item) {
            return;
        }

        $payload = [
            'commodity_id'   => (int) $itemId,
            'commodity_code' => $item['commodity_code'] ?: ($item['sku_code'] ?? ''),
            'commodity_name' => $item['description'] ?? '',
        ];

        if (array_key_exists('inventory_number_min', $sidecarData) && $sidecarData['inventory_number_min'] !== null && $sidecarData['inventory_number_min'] !== '') {
            $payload['inventory_number_min'] = $this->normalize_money_value($sidecarData['inventory_number_min']);
        }
        if (
            $this->db->field_exists('inventory_number_max', db_prefix() . 'inventory_commodity_min')
            && array_key_exists('inventory_number_max', $sidecarData)
            && $sidecarData['inventory_number_max'] !== null
            && $sidecarData['inventory_number_max'] !== ''
        ) {
            $payload['inventory_number_max'] = $this->normalize_money_value($sidecarData['inventory_number_max']);
        }

        $this->db->where('commodity_id', (int) $itemId);
        $existing = $this->db->get(db_prefix() . 'inventory_commodity_min')->row_array();

        if ($existing) {
            $this->db->where('commodity_id', (int) $itemId)->update(db_prefix() . 'inventory_commodity_min', $payload);
            return;
        }

        if (!isset($payload['inventory_number_min'])) {
            $payload['inventory_number_min'] = 0;
        }
        if ($this->db->field_exists('inventory_number_max', db_prefix() . 'inventory_commodity_min')) {
            $payload['inventory_number_max'] = 0;
        }
        $this->db->insert(db_prefix() . 'inventory_commodity_min', $payload);
    }

    private function seed_alias_for_item($itemId)
    {
        $item = $this->get_item_row($itemId);
        if (!$item || empty($item['description'])) {
            return;
        }

        $sku = $item['sku_code'] ?: ($item['commodity_code'] ?: '');
        if (!$sku) {
            return;
        }

        $normalized = item_sku_manager_normalize_text($item['description']);
        if ($normalized === '') {
            return;
        }

        $table = db_prefix() . 'item_sku_aliases';
        $this->db->where('item_id', (int) $itemId);
        $this->db->where('normalized_alias', $normalized);
        $exists = $this->db->get($table)->row_array();
        $now = date('Y-m-d H:i:s');
        $payload = [
            'item_id'          => (int) $itemId,
            'sku_code'         => $sku,
            'alias_name'       => $item['description'],
            'normalized_alias' => $normalized,
            'source'           => 'master',
            'active'           => 1,
            'updated_at'       => $now,
        ];

        if ($exists) {
            $this->db->where('id', (int) $exists['id'])->update($table, $payload);
            return;
        }

        $payload['created_at'] = $now;
        $this->db->insert($table, $payload);
    }

    private function get_item_custom_fields_payload($itemId)
    {
        $payload = [];
        foreach (get_custom_fields('items') as $field) {
            $payload[$field['id']] = get_custom_field_value($itemId, $field['id'], 'items_pr', false);
        }

        return $payload;
    }

    private function next_version($version)
    {
        if (preg_match('/^v?(\d+)\.(\d+)$/i', (string) $version, $matches)) {
            return 'v' . (int) $matches[1] . '.' . ((int) $matches[2] + 1);
        }

        return 'v1.1';
    }

    private function unique_version_sku($baseSku, $version)
    {
        $suffix = strtoupper(str_replace('.', '-', trim($version)));
        $base   = strtoupper(preg_replace('/[^A-Z0-9._-]+/', '-', $baseSku));
        $sku    = trim($base . '-' . $suffix, '-');
        $try    = $sku;
        $i      = 2;

        while ($this->sku_exists($try)) {
            $try = $sku . '-' . $i;
            $i++;
        }

        return $try;
    }

    private function linked_record_url($relType, $relId)
    {
        if ($relType === 'invoice') {
            return admin_url('invoices/list_invoices/' . $relId);
        }
        if ($relType === 'estimate') {
            return admin_url('estimates/list_estimates/' . $relId);
        }
        if ($relType === 'proposal') {
            return admin_url('proposals/list_proposals/' . $relId);
        }
        if ($relType === 'credit_note') {
            return admin_url('credit_notes/list_credit_notes/' . $relId);
        }

        return '#';
    }
}
