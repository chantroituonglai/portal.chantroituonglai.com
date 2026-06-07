<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Item_sku_manager extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        if (!is_admin()) {
            access_denied('item_sku_manager');
        }

        $this->load->model('item_sku_manager/Item_sku_manager_model', 'itemSkuManagerModel');
        $this->load->language('item_sku_manager/item_sku_manager');
    }

    public function index()
    {
        $data['title']   = _l('item_sku_manager');
        $data['summary'] = $this->itemSkuManagerModel->get_summary();
        $data['logs']    = $this->itemSkuManagerModel->get_recent_logs(20);
        $this->load->view('item_sku_manager/manage', $data);
    }

    public function items($id = null, $action = null)
    {
        if ($id === 'create') {
            return $this->item_create();
        }

        if ($id === 'store') {
            return $this->item_store();
        }

        $itemId = (int) $id;
        if ($itemId <= 0) {
            redirect(admin_url('invoice_items'));
        }

        if ($action === 'save') {
            return $this->item_save($itemId);
        }

        if ($action === 'archive') {
            return $this->item_archive($itemId);
        }

        if ($action === 'create_version') {
            return $this->item_create_version($itemId);
        }

        if ($action === 'linked_records') {
            return $this->json([
                'success' => true,
                'records' => $this->itemSkuManagerModel->get_linked_records($itemId, 100),
            ]);
        }

        if ($action === 'trace_logs') {
            return $this->json([
                'success' => true,
                'logs'    => $this->itemSkuManagerModel->get_trace_logs($itemId, 100),
            ]);
        }

        return $this->item_edit($itemId);
    }

    public function search_replacement_sku()
    {
        if (!$this->input->is_ajax_request()) {
            access_denied('item_sku_manager');
        }

        $q         = (string) $this->input->get('q', true);
        $excludeId = (int) $this->input->get('exclude_id');

        return $this->json([
            'results' => $this->itemSkuManagerModel->search_replacement_sku($q, $excludeId),
        ]);
    }

    public function run_backfill()
    {
        $mode   = (string) $this->input->post('mode', true);
        $limit  = (int) $this->input->post('limit');
        $offset = (int) $this->input->post('offset');
        $force  = (int) $this->input->post('force') === 1;

        if ($mode === 'write' && (string) $this->input->post('confirm', true) !== 'WRITE_SKU_METADATA') {
            $this->json([
                'success' => false,
                'message' => 'Missing confirmation token for write mode.',
            ]);
        }

        $this->json($this->itemSkuManagerModel->run_backfill($mode ?: 'dry_run', $limit ?: 100, $offset, $force));
    }

    public function summary()
    {
        $this->json([
            'success' => true,
            'summary' => $this->itemSkuManagerModel->get_summary(),
        ]);
    }

    private function item_create()
    {
        if (staff_cant('create', 'items')) {
            access_denied('Create Item');
        }

        $data = $this->item_form_data(null);
        $data['title']       = _l('new_invoice_item');
        $data['mode']        = 'create';
        $data['item']        = null;
        $data['meta']        = $this->itemSkuManagerModel->get_item_meta(0);
        $data['usage_stats'] = [
            'active_proposals'      => 0,
            'draft_invoices'        => 0,
            'total_historical_uses' => 0,
        ];
        $data['linked_records'] = [];
        $data['trace_logs']     = [];
        $data['form_action']    = admin_url('item_sku_manager/items/store' . $this->context_query());

        $this->load->view('item_sku_manager/item_detail', $data);
    }

    private function item_edit($itemId)
    {
        if (staff_cant('view', 'items')) {
            access_denied('Invoice Items');
        }

        $this->load->model('invoice_items_model');
        $item = $this->invoice_items_model->get($itemId);
        if (!$item) {
            set_alert('warning', 'Item not found.');
            redirect(admin_url('invoice_items'));
        }
        $rawItem = $this->itemSkuManagerModel->get_item_row($itemId);
        if ($rawItem) {
            foreach ($rawItem as $field => $value) {
                if (!property_exists($item, $field)) {
                    $item->{$field} = $value;
                }
            }
        }

        $data = $this->item_form_data($itemId);
        $data['title']          = $item->description;
        $data['mode']           = 'edit';
        $data['item']           = $item;
        $data['meta']           = $this->itemSkuManagerModel->get_item_meta($itemId);
        $data['usage_stats']    = $this->itemSkuManagerModel->get_usage_stats($itemId);
        $data['linked_records'] = $this->itemSkuManagerModel->get_linked_records($itemId, 20);
        $data['trace_logs']     = $this->itemSkuManagerModel->get_trace_logs($itemId, 50);
        $data['form_action']    = admin_url('item_sku_manager/items/' . $itemId . '/save' . $this->context_query());

        $this->load->view('item_sku_manager/item_detail', $data);
    }

    private function item_store()
    {
        if (staff_cant('create', 'items')) {
            access_denied('Create Item');
        }

        if (!$this->input->post()) {
            redirect(admin_url('item_sku_manager/items/create' . $this->context_query()));
        }

        $itemData = $this->extract_item_post();
        $metaData = $this->extract_meta_post();

        if (empty($itemData['description']) || $itemData['rate'] === '') {
            set_alert('warning', 'Description and rate are required.');
            redirect(admin_url('item_sku_manager/items/create' . $this->context_query()));
        }

        if (!empty($itemData['sku_code']) && $this->itemSkuManagerModel->sku_exists($itemData['sku_code'])) {
            set_alert('warning', 'SKU already exists. Please choose another SKU code.');
            redirect(admin_url('item_sku_manager/items/create' . $this->context_query()));
        }

        $id = $this->itemSkuManagerModel->create_item($itemData, $metaData);
        if ($id) {
            $this->handle_warehouse_item_image($id);
            set_alert('success', _l('added_successfully', _l('sales_item')));
            redirect(admin_url('item_sku_manager/items/' . $id . $this->context_query()));
        }

        set_alert('danger', 'Could not add item.');
        redirect(admin_url('item_sku_manager/items/create' . $this->context_query()));
    }

    private function item_save($itemId)
    {
        if (staff_cant('edit', 'items')) {
            access_denied('Edit Item');
        }

        if (!$this->input->post()) {
            redirect(admin_url('item_sku_manager/items/' . $itemId . $this->context_query()));
        }

        $itemData = $this->extract_item_post();
        $metaData = $this->extract_meta_post();

        if (empty($itemData['description']) || $itemData['rate'] === '') {
            set_alert('warning', 'Description and rate are required.');
            redirect(admin_url('item_sku_manager/items/' . $itemId . $this->context_query()));
        }

        if (!empty($itemData['sku_code']) && $this->itemSkuManagerModel->sku_exists($itemData['sku_code'], $itemId)) {
            set_alert('warning', 'SKU already exists. Please choose another SKU code.');
            redirect(admin_url('item_sku_manager/items/' . $itemId . $this->context_query()));
        }

        $success = $this->itemSkuManagerModel->update_item($itemId, $itemData, $metaData);
        $this->handle_warehouse_item_image($itemId);
        set_alert($success ? 'success' : 'warning', $success ? _l('updated_successfully', _l('sales_item')) : 'No item changes were detected.');
        redirect(admin_url('item_sku_manager/items/' . $itemId . $this->context_query()));
    }

    private function item_archive($itemId)
    {
        if (staff_cant('edit', 'items')) {
            access_denied('Edit Item');
        }

        $this->itemSkuManagerModel->archive_item($itemId);
        set_alert('success', 'SKU archived. It will be hidden from new sales documents.');
        redirect(admin_url('item_sku_manager/items/' . $itemId . $this->context_query()));
    }

    private function item_create_version($itemId)
    {
        if (staff_cant('create', 'items')) {
            access_denied('Create Item');
        }

        $newId = $this->itemSkuManagerModel->create_version($itemId);
        if ($newId) {
            set_alert('success', 'New SKU version created.');
            redirect(admin_url('item_sku_manager/items/' . $newId . $this->context_query()));
        }

        set_alert('danger', 'Could not create SKU version.');
        redirect(admin_url('item_sku_manager/items/' . $itemId . $this->context_query()));
    }

    private function item_form_data($itemId = null)
    {
        $this->load->model('taxes_model');
        $this->load->model('invoice_items_model');
        $this->load->model('currencies_model');
        $warehouseData = [
            'warehouse_context' => $this->input->get('context', true) === 'warehouse',
            'warehouses'        => [],
            'warehouse_units'   => [],
            'commodity_types'   => [],
            'sub_groups'        => [],
            'styles'            => [],
            'models'            => [],
            'sizes'             => [],
            'colors'            => [],
            'item_tags_value'   => '',
            'warehouse_images'  => [],
            'inventory_rows'    => [],
            'inventory_total'   => 0,
            'inventory_min'     => null,
        ];

        if (file_exists(APP_MODULES_PATH . 'warehouse/models/Warehouse_model.php')) {
            $this->load->model('warehouse/warehouse_model');
            $warehouseData['warehouses']      = $this->warehouse_model->get_warehouse();
            $warehouseData['warehouse_units'] = $this->warehouse_model->get_unit_add_commodity();
            $warehouseData['commodity_types'] = $this->warehouse_model->get_commodity_type_add_commodity();
            $warehouseData['sub_groups']      = $this->warehouse_model->get_sub_group();
            $warehouseData['styles']          = $this->warehouse_model->get_style_add_commodity();
            $warehouseData['models']          = $this->warehouse_model->get_body_add_commodity();
            $warehouseData['sizes']           = $this->warehouse_model->get_size_add_commodity();
            $warehouseData['colors']          = $this->warehouse_model->get_color_add_commodity();

            if ($itemId) {
                $warehouseData['item_tags_value']  = function_exists('prep_tags_input') ? prep_tags_input(get_tags_in($itemId, 'item_tags')) : '';
                $warehouseData['warehouse_images'] = $this->warehouse_model->get_warehourse_attachments($itemId);
                $warehouseData['inventory_rows']   = $this->warehouse_model->get_inventory_commodity($itemId);
                $totalInventory = $this->warehouse_model->get_inventory_by_commodity($itemId);
                $warehouseData['inventory_total'] = $totalInventory ? (float) $totalInventory->inventory_number : 0;
                $warehouseData['inventory_min'] = $this->db
                    ->where('commodity_id', (int) $itemId)
                    ->get(db_prefix() . 'inventory_commodity_min')
                    ->row();
            }
        }

        return array_merge([
            'taxes'              => $this->taxes_model->get(),
            'items_groups'       => $this->invoice_items_model->get_groups(),
            'currencies'         => $this->currencies_model->get(),
            'base_currency'      => $this->currencies_model->get_base_currency(),
            'custom_fields_html' => render_custom_fields('items', $itemId ?: false, [], $itemId ? ['items_pr' => true] : []),
        ], $warehouseData);
    }

    private function extract_item_post()
    {
        $data   = $this->input->post(null, false);
        $fields = [
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
            'inventory_number_min',
            'inventory_number_max',
            'tags',
            'custom_fields',
        ];
        $itemData = [];

        foreach ($fields as $field) {
            if (array_key_exists($field, $data)) {
                $itemData[$field] = $data[$field];
            }
        }

        foreach ($data as $field => $value) {
            if (strpos($field, 'rate_currency_') === 0) {
                $itemData[$field] = $value;
            }
        }

        $itemData['without_checking_warehouse'] = isset($data['without_checking_warehouse']) ? (int) $data['without_checking_warehouse'] : 0;
        $itemData['active'] = isset($data['active']) ? (int) $data['active'] : 1;

        return $itemData;
    }

    private function handle_warehouse_item_image($itemId)
    {
        if (!file_exists(APP_MODULES_PATH . 'warehouse/helpers/warehouse_helper.php')) {
            return;
        }

        $this->load->helper('warehouse/warehouse');
        if (function_exists('handle_commodity_list_add_edit_file')) {
            handle_commodity_list_add_edit_file((int) $itemId);
        }
    }

    private function context_query()
    {
        return $this->input->get('context', true) === 'warehouse' ? '?context=warehouse' : '';
    }

    private function extract_meta_post()
    {
        $data = $this->input->post(null, true);

        return [
            'sku_version'         => $data['sku_version'] ?? 'v1.0',
            'sku_status'          => $data['sku_status'] ?? 'active',
            'effective_from'      => $data['effective_from'] ?? null,
            'effective_to'        => $data['effective_to'] ?? null,
            'replaced_by_item_id' => $data['replaced_by_item_id'] ?? null,
        ];
    }

    private function json($data)
    {
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}
