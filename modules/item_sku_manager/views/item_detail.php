<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<?php
$isEdit  = $mode === 'edit';
$itemId  = $isEdit && $item ? (int) $item->itemid : 0;
$skuCode = $isEdit ? ($item->sku_code ?: $item->commodity_code) : '';
$status  = $meta['sku_status'] ?? 'active';
$version = $meta['sku_version'] ?? 'v1.0';
$backUrl = !empty($warehouse_context) ? admin_url('warehouse/commodity_list') : admin_url('invoice_items');
$itemValue = static function ($field, $default = '') use ($isEdit, $item) {
    return $isEdit && isset($item->{$field}) ? $item->{$field} : $default;
};
?>
<style>
    .sku-detail-header {
        background: #fff;
        border-bottom: 1px solid #dfe3e8;
        margin: -20px -20px 0;
        padding: 16px 24px 12px;
        position: sticky;
        top: 0;
        z-index: 20;
    }
    .sku-topbar {
        align-items: center;
        display: flex;
        gap: 14px;
        justify-content: space-between;
    }
    .sku-title-wrap {
        align-items: flex-start;
        display: flex;
        flex: 1;
        gap: 14px;
        min-width: 0;
    }
    .sku-product-name-label {
        color: #334155;
        display: block;
        font-size: 12px;
        font-weight: 600;
        margin-bottom: 2px;
    }
    .sku-product-name {
        border: 0;
        box-shadow: none;
        color: #334155;
        font-size: 28px;
        font-weight: 400;
        height: 42px;
        line-height: 38px;
        max-width: 760px;
        padding: 0;
        width: 100%;
    }
    .sku-product-name:focus {
        border: 0;
        border-bottom: 1px solid #0b4f8a;
        box-shadow: none;
        outline: 0;
    }
    .sku-detail-subtitle {
        color: #6b7280;
        font-size: 12px;
        margin-top: 2px;
    }
    .sku-flags {
        display: flex;
        gap: 20px;
        margin: 14px 0 0 46px;
    }
    .sku-flags label {
        font-weight: 600;
        margin: 0;
    }
    .sku-card {
        background: #fff;
        border: 1px solid #dfe3e8;
        border-radius: 8px;
        box-shadow: 0 3px 10px rgba(15, 23, 42, .04);
        margin-bottom: 16px;
    }
    .sku-card .sku-card-heading {
        border-bottom: 1px solid #eef1f4;
        font-size: 16px;
        font-weight: 600;
        padding: 16px 18px 12px;
    }
    .sku-card .sku-card-body {
        padding: 18px;
    }
    .sku-tabs-wrap {
        background: #fff;
        border-bottom: 1px solid #dfe3e8;
        margin: 0 -20px 20px;
        padding: 0 24px;
    }
    .sku-tabs-wrap .nav-tabs {
        border-bottom: 0;
    }
    .sku-tabs-wrap .nav-tabs > li > a {
        border-radius: 0;
        color: #475569;
        padding: 12px 16px;
    }
    .sku-tabs-wrap .nav-tabs > li.active > a,
    .sku-tabs-wrap .nav-tabs > li.active > a:focus,
    .sku-tabs-wrap .nav-tabs > li.active > a:hover {
        border-top: 2px solid #0b4f8a;
        color: #0f172a;
        font-weight: 600;
    }
    .sku-policy {
        background: #eef8ff;
        border-color: #b7d8ef;
    }
    .sku-stat-row,
    .sku-linked-row {
        border-bottom: 1px solid #edf0f2;
        display: flex;
        gap: 12px;
        justify-content: space-between;
        padding: 9px 0;
    }
    .sku-stat-row:last-child,
    .sku-linked-row:last-child {
        border-bottom: 0;
    }
    .sku-trace-item {
        border-left: 2px solid #d8dee7;
        margin-left: 8px;
        padding: 0 0 14px 18px;
        position: relative;
    }
    .sku-trace-item:before {
        background: #0b4f8a;
        border: 2px solid #fff;
        border-radius: 50%;
        box-shadow: 0 0 0 1px #d8dee7;
        content: '';
        height: 10px;
        left: -6px;
        position: absolute;
        top: 4px;
        width: 10px;
    }
    .sku-trace-meta,
    .sku-muted-note {
        color: #6b7280;
        font-size: 12px;
    }
    .sku-replacement-results {
        background: #fff;
        border: 1px solid #dfe3e8;
        display: none;
        max-height: 220px;
        overflow-y: auto;
        position: absolute;
        width: 100%;
        z-index: 30;
    }
    .sku-replacement-results a {
        display: block;
        padding: 8px 10px;
    }
    .sku-replacement-results a:hover {
        background: #f4f7fb;
        text-decoration: none;
    }
    .sku-empty-tab {
        background: #f8fafc;
        border: 1px dashed #cbd5e1;
        border-radius: 6px;
        color: #64748b;
        padding: 16px;
    }
    .sku-image-grid {
        display: grid;
        gap: 10px;
        grid-template-columns: repeat(auto-fill, minmax(90px, 1fr));
    }
    .sku-image-grid img {
        border: 1px solid #dfe3e8;
        border-radius: 6px;
        height: 82px;
        object-fit: cover;
        width: 100%;
    }
    .sku-stock-table td,
    .sku-stock-table th {
        vertical-align: middle !important;
    }
</style>
<div id="wrapper">
    <div class="content">
        <?php if ($isEdit) { ?>
            <?= form_open(admin_url('item_sku_manager/items/' . $itemId . '/archive' . (!empty($warehouse_context) ? '?context=warehouse' : '')), ['id' => 'sku_archive_form']); ?>
            <?= form_close(); ?>
            <?= form_open(admin_url('item_sku_manager/items/' . $itemId . '/create_version' . (!empty($warehouse_context) ? '?context=warehouse' : '')), ['id' => 'sku_create_version_form']); ?>
            <?= form_close(); ?>
        <?php } ?>

        <?= form_open_multipart($form_action, ['id' => 'item_sku_detail_form']); ?>
        <div class="sku-detail-header">
            <div class="sku-topbar">
                <div class="sku-title-wrap">
                    <a href="<?= e($backUrl); ?>" class="btn btn-default btn-icon" data-toggle="tooltip" title="Back to items">
                        <i class="fa fa-arrow-left"></i>
                    </a>
                    <div class="tw-w-full">
                        <label class="sku-product-name-label" for="description">Product Name</label>
                        <input type="text" id="description" name="description" class="form-control sku-product-name" value="<?= e($isEdit ? $item->description : ''); ?>" placeholder="e.g. Website Doanh Nghiệp Starter">
                        <div class="sku-detail-subtitle">
                            SKU:
                            <strong><?= e($skuCode ?: 'New SKU'); ?></strong>
                            <span class="text-muted">/ <?= e($version); ?></span>
                            <?php if ($status !== 'active') { ?>
                                <span class="label label-warning mleft5"><?= e(ucfirst($status)); ?></span>
                            <?php } ?>
                        </div>
                    </div>
                </div>
                <div class="tw-flex tw-items-center tw-gap-2">
                    <?php if ($isEdit && staff_can('edit', 'items')) { ?>
                    <button type="submit" form="sku_archive_form" class="btn btn-default" onclick="return confirm('Archive this SKU and hide it from new sales documents?');">
                        <i class="fa fa-box-archive tw-mr-1"></i> Archive SKU
                    </button>
                    <?php } ?>
                    <?php if ($isEdit && staff_can('create', 'items')) { ?>
                    <button type="submit" form="sku_create_version_form" class="btn btn-default" onclick="return confirm('Create a new item/SKU version from this item?');">
                        <i class="fa fa-code-branch tw-mr-1"></i> Create Version
                    </button>
                    <?php } ?>
                    <?php if (($isEdit && staff_can('edit', 'items')) || (!$isEdit && staff_can('create', 'items'))) { ?>
                    <button type="submit" form="item_sku_detail_form" class="btn btn-primary">
                        <i class="fa fa-save tw-mr-1"></i> Save Changes
                    </button>
                    <?php } ?>
                </div>
            </div>
            <div class="sku-flags">
                <div class="checkbox checkbox-primary">
                    <input type="checkbox" id="can_be_sold" <?= (int) $itemValue('active', 1) === 1 ? 'checked' : ''; ?> disabled>
                    <label for="can_be_sold">Can be Sold</label>
                </div>
                <div class="checkbox checkbox-primary">
                    <input type="checkbox" id="can_be_purchased" <?= $itemValue('purchase_price') !== '' ? 'checked' : ''; ?> disabled>
                    <label for="can_be_purchased">Can be Purchased</label>
                </div>
            </div>
        </div>

        <div class="sku-tabs-wrap">
            <ul class="nav nav-tabs" role="tablist">
                <li role="presentation" class="active"><a href="#sku_tab_general" aria-controls="sku_tab_general" role="tab" data-toggle="tab">General Information</a></li>
                <li role="presentation"><a href="#sku_tab_attributes" aria-controls="sku_tab_attributes" role="tab" data-toggle="tab">Attributes & Variants</a></li>
                <li role="presentation"><a href="#sku_tab_sales" aria-controls="sku_tab_sales" role="tab" data-toggle="tab">Sales</a></li>
                <li role="presentation"><a href="#sku_tab_purchase" aria-controls="sku_tab_purchase" role="tab" data-toggle="tab">Purchase</a></li>
                <li role="presentation"><a href="#sku_tab_inventory" aria-controls="sku_tab_inventory" role="tab" data-toggle="tab">Inventory</a></li>
                <li role="presentation"><a href="#sku_tab_accounting" aria-controls="sku_tab_accounting" role="tab" data-toggle="tab">Accounting</a></li>
                <li role="presentation"><a href="#sku_tab_sku" aria-controls="sku_tab_sku" role="tab" data-toggle="tab">SKU & Versioning</a></li>
                <li role="presentation"><a href="#sku_tab_trace" aria-controls="sku_tab_trace" role="tab" data-toggle="tab">Trace Log</a></li>
            </ul>
        </div>

        <div class="row">
            <div class="col-md-8">
                <div class="tab-content">
                    <div role="tabpanel" class="tab-pane active" id="sku_tab_general">
                        <div class="sku-card">
                            <div class="sku-card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <?= render_input('commodity_code', _l('commodity_code'), $itemValue('commodity_code')); ?>
                                    </div>
                                    <div class="col-md-6">
                                        <?= render_input('commodity_barcode', _l('commodity_barcode'), $itemValue('commodity_barcode')); ?>
                                    </div>
                                </div>
                                <?= render_textarea('long_description', 'invoice_item_long_description', $isEdit ? clear_textarea_breaks($item->long_description) : '', ['rows' => 5]); ?>
                                <div class="row">
                                    <div class="col-md-6">
                                        <?= render_select('unit_id', $warehouse_units, ['unit_type_id', 'unit_name'], _l('unit_id'), $itemValue('unit_id'), [], [], '', '', false); ?>
                                    </div>
                                    <div class="col-md-6">
                                        <?= render_input('unit', 'unit', $itemValue('unit')); ?>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <?= render_select('group_id', $items_groups, ['id', 'name'], 'item_group', $itemValue('group_id')); ?>
                                    </div>
                                    <div class="col-md-6">
                                        <?= render_select('sub_group', $sub_groups, ['id', 'sub_group_name'], _l('sub_group'), $itemValue('sub_group'), [], [], '', '', false); ?>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <?= render_select('commodity_type', $commodity_types, ['commodity_type_id', 'commondity_name'], _l('commodity_type'), $itemValue('commodity_type'), [], [], '', '', false); ?>
                                    </div>
                                    <div class="col-md-6">
                                        <?= render_input('tags', _l('tags'), $item_tags_value, 'text', ['data-role' => 'tagsinput']); ?>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="checkbox checkbox-primary">
                                            <input type="hidden" name="active" value="0">
                                            <input type="checkbox" id="active" name="active" value="1" <?= (int) $itemValue('active', 1) === 1 ? 'checked' : ''; ?>>
                                            <label for="active"><?= _l('active'); ?></label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <?= render_input('cd_avar', _l('item_image'), '', 'file'); ?>
                                    </div>
                                </div>
                                <?php if (!empty($warehouse_images)) { ?>
                                    <div class="form-group">
                                        <label class="control-label"><?= _l('attachments'); ?></label>
                                        <div class="sku-image-grid">
                                            <?php foreach ($warehouse_images as $image) {
                                                $imageUrl = file_exists(WAREHOUSE_ITEM_UPLOAD . $image['rel_id'] . '/' . $image['file_name'])
                                                    ? site_url('modules/warehouse/uploads/item_img/' . $image['rel_id'] . '/' . $image['file_name'])
                                                    : site_url('modules/purchase/uploads/item_img/' . $image['rel_id'] . '/' . $image['file_name']); ?>
                                                <a href="<?= e($imageUrl); ?>" target="_blank" rel="noopener">
                                                    <img src="<?= e($imageUrl); ?>" alt="<?= e($image['file_name']); ?>">
                                                </a>
                                            <?php } ?>
                                        </div>
                                    </div>
                                <?php } ?>
                                <div id="custom_fields_items">
                                    <?= $custom_fields_html; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div role="tabpanel" class="tab-pane" id="sku_tab_attributes">
                        <div class="sku-card">
                            <div class="sku-card-heading">Attributes & Variants</div>
                            <div class="sku-card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <?= render_input('origin', _l('origin'), $itemValue('origin')); ?>
                                    </div>
                                    <div class="col-md-6">
                                        <?= render_select('style_id', $styles, ['style_type_id', 'style_name'], _l('styles'), $itemValue('style_id'), [], [], '', '', false); ?>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <?= render_select('model_id', $models, ['body_type_id', 'body_name'], _l('model_id'), $itemValue('model_id'), [], [], '', '', false); ?>
                                    </div>
                                    <div class="col-md-6">
                                        <?= render_select('size_id', $sizes, ['size_type_id', 'size_name'], _l('size_id'), $itemValue('size_id'), [], [], '', '', false); ?>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <?= render_select('color', $colors, ['color_id', 'color_name'], _l('colors'), $itemValue('color'), [], [], '', '', false); ?>
                                    </div>
                                    <div class="col-md-6">
                                        <?= render_input('guarantee', _l('guarantee'), $itemValue('guarantee'), 'number', ['min' => 0]); ?>
                                    </div>
                                </div>
                                <?= render_textarea('parent_attributes', 'Parent Attributes', $itemValue('parent_attributes'), ['rows' => 3]); ?>
                                <?= render_textarea('long_descriptions', _l('long_description'), $itemValue('long_descriptions'), ['rows' => 4]); ?>
                            </div>
                        </div>
                    </div>

                    <div role="tabpanel" class="tab-pane" id="sku_tab_sales">
                        <div class="sku-card">
                            <div class="sku-card-heading">Sales Pricing</div>
                            <div class="sku-card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <?= render_input('rate', _l('invoice_item_add_edit_rate_currency', e($base_currency->name)), $isEdit ? $item->rate : '', 'number', ['step' => 'any']); ?>
                                    </div>
                                    <div class="col-md-6">
                                        <?= render_input('profif_ratio', _l('_profit_rate_p'), $itemValue('profif_ratio'), 'number', ['step' => 'any']); ?>
                                    </div>
                                </div>
                                <?php foreach ($currencies as $currency) {
                                    if ($currency['isdefault'] == 0 && total_rows(db_prefix() . 'clients', ['default_currency' => $currency['id']]) > 0) {
                                        $field = 'rate_currency_' . $currency['id']; ?>
                                        <?= render_input($field, _l('invoice_item_add_edit_rate_currency', $currency['name']), $isEdit && isset($item->{$field}) ? $item->{$field} : '', 'number', ['step' => 'any']); ?>
                                    <?php }
                                } ?>
                            </div>
                        </div>
                        <div class="sku-card">
                            <div class="sku-card-heading">Sales Snapshot Policy</div>
                            <div class="sku-card-body">
                                <p class="text-muted no-margin">
                                    Changes here update the master item only. Historical proposals, estimates, invoices and credit notes keep their saved item snapshot.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div role="tabpanel" class="tab-pane" id="sku_tab_purchase">
                        <div class="sku-card">
                            <div class="sku-card-heading">Purchase</div>
                            <div class="sku-card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <?= render_input('purchase_price', _l('purchase_price'), $itemValue('purchase_price'), 'number', ['step' => 'any']); ?>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="checkbox checkbox-primary">
                                            <input type="checkbox" id="can_be_purchased_display" <?= $itemValue('purchase_price') !== '' ? 'checked' : ''; ?> disabled>
                                            <label for="can_be_purchased_display">Can be Purchased</label>
                                        </div>
                                    </div>
                                </div>
                                <p class="sku-muted-note no-margin">Purchase documents keep their own line snapshots. Stock receipts and vendor operations remain in Warehouse/Purchase modules.</p>
                            </div>
                        </div>
                    </div>

                    <div role="tabpanel" class="tab-pane" id="sku_tab_inventory">
                        <div class="sku-card">
                            <div class="sku-card-heading">Inventory</div>
                            <div class="sku-card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <?= render_select('warehouse_id', $warehouses, ['warehouse_id', 'warehouse_name'], _l('_warehouse'), $itemValue('warehouse_id'), [], [], '', '', false); ?>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="checkbox checkbox-primary mtop25">
                                            <input type="hidden" name="without_checking_warehouse" value="0">
                                            <input type="checkbox" id="without_checking_warehouse" name="without_checking_warehouse" value="1" <?= (int) $itemValue('without_checking_warehouse') === 1 ? 'checked' : ''; ?>>
                                            <label for="without_checking_warehouse"><?= _l('without_checking_warehouse'); ?></label>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="sku-stat-row"><span>Current Stock</span><strong><?= e(app_format_number($inventory_total)); ?></strong></div>
                                    </div>
                                    <div class="col-md-4">
                                        <?= render_input('inventory_number_min', 'Minimum Stock', $inventory_min ? $inventory_min->inventory_number_min : '0', 'number', ['step' => 'any']); ?>
                                    </div>
                                    <div class="col-md-4">
                                        <?= render_input('inventory_number_max', 'Maximum Stock', $inventory_min && isset($inventory_min->inventory_number_max) ? $inventory_min->inventory_number_max : '0', 'number', ['step' => 'any']); ?>
                                    </div>
                                </div>
                                <?php if (!empty($inventory_rows)) { ?>
                                    <table class="table table-striped sku-stock-table mtop15">
                                        <thead>
                                            <tr>
                                                <th><?= _l('_warehouse'); ?></th>
                                                <th class="text-right"><?= _l('quantity'); ?></th>
                                                <th><?= _l('unit_id'); ?></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($inventory_rows as $stockRow) { ?>
                                                <tr>
                                                    <td><?= e($stockRow['warehouse_code']); ?></td>
                                                    <td class="text-right"><?= e(app_format_number($stockRow['inventory_number'])); ?></td>
                                                    <td><?= e($stockRow['unit_name']); ?></td>
                                                </tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                <?php } else { ?>
                                    <div class="sku-empty-tab mtop15">No stock movement has been recorded for this item.</div>
                                <?php } ?>
                            </div>
                        </div>
                    </div>

                    <div role="tabpanel" class="tab-pane" id="sku_tab_accounting">
                        <div class="sku-card">
                            <div class="sku-card-heading">Accounting</div>
                            <div class="sku-card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="tax" class="control-label"><?= _l('tax_1'); ?></label>
                                            <select class="selectpicker display-block" data-width="100%" name="tax" data-none-selected-text="<?= _l('no_tax'); ?>">
                                                <option value=""></option>
                                                <?php foreach ($taxes as $tax) { ?>
                                                    <option value="<?= e($tax['id']); ?>" data-subtext="<?= e($tax['name']); ?>" <?= $isEdit && (int) $item->taxid === (int) $tax['id'] ? 'selected' : ''; ?>>
                                                        <?= e($tax['taxrate']); ?>%
                                                    </option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="tax2" class="control-label"><?= _l('tax_2'); ?></label>
                                            <select class="selectpicker display-block" data-width="100%" name="tax2" data-none-selected-text="<?= _l('no_tax'); ?>">
                                                <option value=""></option>
                                                <?php foreach ($taxes as $tax) { ?>
                                                    <option value="<?= e($tax['id']); ?>" data-subtext="<?= e($tax['name']); ?>" <?= $isEdit && (int) $item->taxid_2 === (int) $tax['id'] ? 'selected' : ''; ?>>
                                                        <?= e($tax['taxrate']); ?>%
                                                    </option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div role="tabpanel" class="tab-pane" id="sku_tab_sku">
                        <div class="sku-card">
                            <div class="sku-card-heading">SKU Management</div>
                            <div class="sku-card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <?= render_input('sku_code', 'SKU Code', $skuCode, 'text', $isEdit && $usage_stats['total_historical_uses'] > 0 ? ['readonly' => true] : []); ?>
                                        <?php if ($isEdit && $usage_stats['total_historical_uses'] > 0) { ?>
                                            <p class="sku-muted-note">SKU code is locked after sales usage. Use Create Version for a new code.</p>
                                        <?php } ?>
                                    </div>
                                    <div class="col-md-6">
                                        <?= render_input('sku_name', 'SKU Name', $isEdit ? $item->sku_name : ''); ?>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <?= render_input('commodity_code_display', 'Commodity Code', $itemValue('commodity_code'), 'text', ['readonly' => true]); ?>
                                    </div>
                                    <div class="col-md-6">
                                        <?= render_input('sku_version', 'SKU Version', $version); ?>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="sku_status" class="control-label">Status</label>
                                            <select class="selectpicker display-block" data-width="100%" name="sku_status">
                                                <?php foreach (['active', 'inactive', 'deprecated', 'archived'] as $option) { ?>
                                                    <option value="<?= e($option); ?>" <?= $status === $option ? 'selected' : ''; ?>><?= e(ucfirst($option)); ?></option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group" style="position:relative;">
                                            <label class="control-label" for="replacement_sku_search">Replaced By SKU</label>
                                            <input type="hidden" name="replaced_by_item_id" id="replaced_by_item_id" value="<?= e($meta['replaced_by_item_id'] ?? ''); ?>">
                                            <input type="text" id="replacement_sku_search" class="form-control" placeholder="Search SKU..." autocomplete="off">
                                            <div id="replacement_sku_results" class="sku-replacement-results"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <?= render_date_input('effective_from', 'Effective From', $meta['effective_from'] ?? ''); ?>
                                    </div>
                                    <div class="col-md-6">
                                        <?= render_date_input('effective_to', 'Effective To', $meta['effective_to'] ?? ''); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div role="tabpanel" class="tab-pane" id="sku_tab_trace">
                        <div class="sku-card">
                            <div class="sku-card-heading">Trace Log</div>
                            <div class="sku-card-body">
                                <?php if (empty($trace_logs)) { ?>
                                    <p class="text-muted no-margin">No trace log yet.</p>
                                <?php } ?>
                                <?php foreach ($trace_logs as $log) { ?>
                                    <div class="sku-trace-item">
                                        <strong><?= e(ucwords(str_replace('_', ' ', $log['event_type']))); ?></strong>
                                        <?php if (!empty($log['field_name'])) { ?>
                                            <div>
                                                <span class="text-muted"><?= e($log['field_name']); ?>:</span>
                                                <span><?= e($log['old_value']); ?></span>
                                                <i class="fa fa-arrow-right text-muted"></i>
                                                <span><?= e($log['new_value']); ?></span>
                                            </div>
                                        <?php } ?>
                                        <div class="sku-trace-meta">
                                            <?= e(trim(($log['firstname'] ?? '') . ' ' . ($log['lastname'] ?? '')) ?: 'System'); ?>
                                            / <?= e(_dt($log['created_at'])); ?>
                                        </div>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="sku-card sku-policy">
                    <div class="sku-card-body">
                        <h4 class="tw-mt-0"><i class="fa fa-circle-info tw-mr-2"></i> Sales Snapshot Policy</h4>
                        <p class="text-muted">
                            Sales records capture a static snapshot of rate, tax, description and SKU at creation time.
                        </p>
                    </div>
                </div>

                <div class="sku-card">
                    <div class="sku-card-heading"><i class="fa fa-chart-line tw-mr-1"></i> Usage Statistics</div>
                    <div class="sku-card-body">
                        <div class="sku-stat-row"><span>Active Proposals</span><strong><?= e((int) $usage_stats['active_proposals']); ?></strong></div>
                        <div class="sku-stat-row"><span>Draft Invoices</span><strong><?= e((int) $usage_stats['draft_invoices']); ?></strong></div>
                        <div class="sku-stat-row"><span>Total Historical Uses</span><strong><?= e((int) $usage_stats['total_historical_uses']); ?></strong></div>
                    </div>
                </div>

                <div class="sku-card">
                    <div class="sku-card-heading">Linked Records</div>
                    <div class="sku-card-body">
                        <?php if (empty($linked_records)) { ?>
                            <p class="text-muted no-margin">No linked sales records yet.</p>
                        <?php } ?>
                        <?php foreach ($linked_records as $record) { ?>
                            <div class="sku-linked-row">
                                <span><?= e(ucfirst($record['rel_type'])); ?> #<?= e($record['rel_id']); ?></span>
                                <a href="<?= e($record['admin_url']); ?>">Open</a>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
        <?= form_close(); ?>
    </div>
</div>
<?php init_tail(); ?>
<script>
$(function() {
    appValidateForm($('#item_sku_detail_form'), {
        description: 'required',
        rate: 'required'
    });

    var replacementTimer = null;
    $('#replacement_sku_search').on('keyup', function() {
        var q = $(this).val();
        clearTimeout(replacementTimer);
        if (q.length < 2) {
            $('#replacement_sku_results').hide().empty();
            return;
        }
        replacementTimer = setTimeout(function() {
            $.getJSON(admin_url + 'item_sku_manager/search_replacement_sku', {
                q: q,
                exclude_id: <?= (int) $itemId; ?>
            }).done(function(response) {
                var $box = $('#replacement_sku_results').empty();
                $.each(response.results || [], function(_, row) {
                    $('<a href="#" />')
                        .text(row.text)
                        .attr('data-id', row.id)
                        .appendTo($box);
                });
                $box.toggle($box.children().length > 0);
            });
        }, 250);
    });

    $('#replacement_sku_results').on('click', 'a', function(e) {
        e.preventDefault();
        $('#replaced_by_item_id').val($(this).data('id'));
        $('#replacement_sku_search').val($(this).text());
        $('#replacement_sku_results').hide();
    });
});
</script>
</body>
</html>
