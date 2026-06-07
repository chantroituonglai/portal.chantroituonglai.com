<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div
    class="form-group mbot25 items-wrapper select-placeholder<?= staff_can('create', 'items') ? ' input-group-select' : ''; ?>">
    <div
        class="<?= staff_can('create', 'items') ? 'input-group input-group-select' : ''; ?>">
        <div class="items-select-wrapper">
            <select name="item_select"
                class="selectpicker no-margin<?= $ajaxItems == true ? ' ajax-search' : ''; ?><?= staff_can('create', 'items') ? ' _select_input_group' : ''; ?>"
                data-width="false" id="item_select"
                data-none-selected-text="<?= _l('add_item'); ?>"
                data-live-search="true">
                <option value=""></option>
                <?php foreach ($items as $group_id => $_items) { ?>
                <optgroup data-group-id="<?= e($group_id); ?>"
                    label="<?= $_items[0]['group_name']; ?>">
                    <?php foreach ($_items as $item) { ?>
                    <?php $sku = $item['sku_code'] ?? ($item['commodity_code'] ?? ''); ?>
                    <option
                        value="<?= e($item['id']); ?>"
                        data-subtext="<?= e(($sku ? 'SKU: ' . $sku . ' | ' : '') . strip_tags(mb_substr($item['long_description'], 0, 200)) . '...'); ?>">
                        <?= $sku ? '[' . e($sku) . ']' : ''; ?>
                        (<?= e(app_format_number($item['rate'])); ?>)
                        <?= e($item['description']); ?>
                    </option>
                    <?php } ?>
                </optgroup>
                <?php } ?>
            </select>
        </div>
        <?php if (staff_can('create', 'items')) { ?>
        <div class="input-group-btn">
            <a href="<?= admin_url('item_sku_manager/items/create'); ?>" class="btn btn-default" target="_blank" data-toggle="tooltip" title="<?= e(_l('new_invoice_item')); ?>">
                <i class="fa fa-plus"></i>
            </a>
        </div>
        <?php } ?>
    </div>
</div>
