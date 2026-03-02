<header class="panel-heading ">
    <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span
                class="sr-only">Close</span></button>
    <h4 class="modal-title" id="myModalLabel"><strong><?= _l('new_items') ?></strong></h4>
</header>

<div class="wrap-modal wrap">
    <div class="panel-body form-horizontal">
        <?php echo form_open(base_url('admin/deals/itemAddedManualy'), array('id' => '', 'enctype' => 'multipart/form-data', 'data-parsley-validate' => '', 'role' => 'form')); ?>
        <div class="col-sm-12 ">
            <div class="form-group">
                <label class="col-lg-3 control-label"><?= _l('item_name') ?> <span class="text-danger">*</span></label>
                <div class="col-md-8">
                    <input type="text" class="form-control"
                           value="<?= (!empty($items_info->item_name) ? $items_info->item_name : ''); ?>"
                           name="item_name" required="">
                </div>
            </div>
            <div class="form-group">
                <label class="col-lg-3 control-label"><?= _l('description') ?></label>
                <div class="col-md-8">
                    <textarea class="form-control"
                              name="item_desc"> <?= (!empty($items_info->item_desc) ? $items_info->item_desc : ''); ?></textarea>
                </div>
            </div>

            <div class="form-group">
                <label class="col-lg-3 col-md-3 col-sm-3 control-label"><?= _l('quantity') ?> <span class="text-danger">*</span></label>
                <div class="col-md-8">
                    <input type="text" data-parsley-type="number" class="form-control"
                           value="<?= (!empty($items_info->quantity) ? $items_info->quantity : 1); ?>" name="quantity"
                           required="">
                </div>
            </div>
            <div class="form-group">
                <label class="col-lg-3 col-md-3 col-sm-3 control-label"><?= _l('price') ?> <span
                            class="text-danger">*</span></label>
                <div class="col-md-8">
                    <input type="text" data-parsley-type="number" class="form-control"
                           value="<?= (!empty($items_info->unit_cost) ? $items_info->unit_cost : ''); ?>"
                           name="unit_cost" required="">
                </div>
            </div>

            <div class="form-group">
                <label class="col-lg-3 control-label"><?= _l('unit') . ' ' . _l('type') ?></label>
                <div class="col-md-8">
                    <input type="text" class="form-control"
                           value="<?= (!empty($items_info->unit) ? $items_info->unit : ''); ?>"
                           placeholder="<?= _l('unit_type_example') ?>" name="unit">
                </div>
            </div>
            <input type="hidden" class="form-control" value="<?= $deals_id ?>" name="deals_id">
            <?php
            if (!empty($items_info)) { ?>
                <input type="hidden" class="form-control" value="<?= $items_info->items_id ?>" name="items_id">
            <?php } ?>
            <div class="form-group tw-mb-4">
                <label class="col-lg-3 control-label"><?= _l('tax') ?></label>
                <div class="col-md-8">

                    <?php
                    $taxes = $this->db->order_by('id', 'ASC')->get(db_prefix() . 'taxes')->result();
                    $select = '<select class="form-control selectpicker" multiple data-width="100%" name="tax_rates_id[]" data-none-selected-text="' . _l('no_tax') . '">';
                    $select .= '<option value=""></option>';
                    foreach ($taxes as $tax) {
                        $selected = '';
                        if (!empty($items_info->tax_rates_id)) {
                            $tax_rates_id = json_decode($items_info->tax_rates_id);
                            if (in_array($tax->id, $tax_rates_id)) {
                                $selected = ' selected ';
                            }
                        }
                        $select .= '<option value="' . $tax->id . '"' . $selected . 'data-taxrate="' . $tax->taxrate . '" data-taxname="' . $tax->name . '" data-subtext="' . $tax->name . '">' . $tax->taxrate . '%</option>';
                    }
                    $select .= '</select>';
                    echo $select;
                    ?>
                </div>
            </div>
            <div class="form-group mt-lg">
                <label class="col-lg-3 control-label"></label>
                <div class="col-md-8">
                    <?php
                    if (!empty($items_info)) { ?>
                        <button type="submit" class="btn btn-sm btn-primary"><?= _l('updates') ?></button>
                        <button type="button" onclick="goBack()"
                                class="btn btn-sm btn-danger"><?= _l('cancel') ?></button>
                    <?php } else {
                        ?>

                        <button type="submit" id=""
                                class="btn btn-sm btn-primary"><?= _l('added') . ' ' . _l('manually') ?></button>
                        <button type="button" class="btn btn-secondary pull-right" data-dismiss="modal">Close
                        </button>
                    <?php }
                    ?>
                </div>
            </div>
        </div>
        <?php echo form_close(); ?>
    </div>

</div>
<script type="text/javascript">
    init_selectpicker();
</script>
