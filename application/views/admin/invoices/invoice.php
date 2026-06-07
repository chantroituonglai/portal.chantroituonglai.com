<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <?php
            $invoiceFormAttrs = ['id' => 'invoice-form', 'class' => '_transaction_form invoice-form'];
            if (isset($invoice) && (int) $invoice->status === Invoices_model::STATUS_DRAFT) {
                $invoiceFormAttrs['data-autosave-enabled'] = '1';
                $invoiceFormAttrs['data-autosave-url']     = admin_url('invoices/autosave_draft/' . $invoice->id);
            }
            ?>
            <?= form_open($this->uri->uri_string(), $invoiceFormAttrs); ?>
            <?php if (isset($invoice)) {
                echo form_hidden('isedit');
            } ?>
            <div class="col-md-12">
                <h4 class="tw-mt-0 tw-font-bold tw-text-lg tw-text-neutral-700 tw-flex tw-items-center tw-space-x-2">
                    <span>
                        <?= e(isset($invoice) ? format_invoice_number($invoice) : _l('create_new_invoice')); ?>
                    </span>
                    <?= isset($invoice) ? format_invoice_status($invoice->status) : ''; ?>
                </h4>
                <span class="sales-draft-autosave-status text-muted mleft5"></span>
                <?php $this->load->view('admin/invoices/invoice_template'); ?>
            </div>
            <?= form_close(); ?>
            <?php $this->load->view('admin/invoice_items/item'); ?>
        </div>
    </div>
</div>
<?php init_tail(); ?>
<script src="<?= base_url('assets/js/sales_draft_autosave.js'); ?>"></script>
<script>
    $(function() {
        validate_invoice_form();
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
