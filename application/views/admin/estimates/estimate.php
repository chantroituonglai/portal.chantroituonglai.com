<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <?php
            $estimateFormAttrs = ['id' => 'estimate-form', 'class' => '_transaction_form estimate-form'];
            if (isset($estimate) && (int) $estimate->status === 1) {
                $estimateFormAttrs['data-autosave-enabled'] = '1';
                $estimateFormAttrs['data-autosave-url']     = admin_url('estimates/autosave_draft/' . $estimate->id);
            }
            echo form_open($this->uri->uri_string(), $estimateFormAttrs);
            if (isset($estimate)) {
                echo form_hidden('isedit');
            }
            ?>
            <div class="col-md-12">
                <h4
                    class="tw-mt-0 tw-font-bold tw-text-lg tw-text-neutral-700 tw-flex tw-items-center tw-space-x-2">
                    <span>
                        <?php echo e( isset($estimate) ? format_estimate_number($estimate) : _l('create_new_estimate')); ?>
                    </span>
                    <?php echo isset($estimate) ? format_estimate_status($estimate->status) : ''; ?>
                </h4>
                <span class="sales-draft-autosave-status text-muted mleft5"></span>
                <?php $this->load->view('admin/estimates/estimate_template'); ?>
            </div>
            <?php echo form_close(); ?>
            <?php $this->load->view('admin/invoice_items/item'); ?>
        </div>
    </div>
</div>
</div>
<?php init_tail(); ?>
<script src="<?php echo base_url('assets/js/sales_draft_autosave.js'); ?>"></script>
<script>
$(function() {
    validate_estimate_form();
    // Init accountacy currency symbol
    init_currency();
    // Project ajax search
    init_ajax_project_search_by_customer_id();
    // Maybe items ajax search
    init_ajax_search('items', '#item_select.ajax-search', undefined, admin_url + 'items/search');
});
</script>
</body>

</html>
