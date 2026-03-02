<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h4 class="no-margin"><?php echo _l('haravan_products'); ?></h4>
                            </div>
                            <div class="col-md-6 text-right">
                                <button type="button" class="btn btn-primary mright5" data-toggle="modal" data-target="#syncExternalSkuModal" <?php echo empty($available_skus) ? 'disabled' : ''; ?>>
                                    <i class="fa fa-download"></i> <?php echo _l('sync_from_external_products'); ?>
                                </button>
                                <a href="<?php echo admin_url('external_products/external_system_settings?tab=haravan'); ?>" class="btn btn-info mright5">
                                    <i class="fa fa-cog"></i> <?php echo _l('haravan_api_settings'); ?>
                                </a>
                                <a href="<?php echo admin_url('external_products'); ?>" class="btn btn-default">
                                    <i class="fa fa-arrow-left"></i> <?php echo _l('back_to_external_products'); ?>
                                </a>
                            </div>
                        </div>
                        <hr class="hr-panel-heading" />
                        <?php
                        $table_data = [
                            _l('id'),
                            _l('external_product_id'),
                            _l('external_product_name'),
                            _l('external_product_sku'),
                            _l('external_product_price'),
                            _l('external_brand'),
                            _l('external_category'),
                            _l('external_stock_quantity'),
                            _l('last_synced'),
                            _l('options'),
                        ];
                        render_datatable($table_data, 'haravan-products');
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="syncExternalSkuModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><?php echo _l('sync_from_external_products'); ?></h4>
            </div>
            <div class="modal-body">
                <?php if (!empty($available_skus)) { ?>
                    <form id="sync-external-sku-form">
                        <div class="form-group">
                            <label for="external-sync-sku" class="control-label"><?php echo _l('select_external_product_sku'); ?></label>
                            <select id="external-sync-sku" name="external_sync_sku" class="selectpicker" data-width="100%" data-live-search="true" title="<?php echo _l('select_external_product_sku'); ?>">
                                <?php foreach ($available_skus as $sku) { ?>
                                    <option value="<?php echo html_escape($sku); ?>"><?php echo html_escape($sku); ?></option>
                                <?php } ?>
                            </select>
                        </div>
                    </form>
                <?php } else { ?>
                    <p class="text-muted no-mbot"><?php echo _l('no_available_external_skus'); ?></p>
                <?php } ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('cancel'); ?></button>
                <?php if (!empty($available_skus)) { ?>
                    <button type="submit" form="sync-external-sku-form" class="btn btn-primary" id="sync-external-sku-submit">
                        <i class="fa fa-refresh"></i> <?php echo _l('sync_now'); ?>
                    </button>
                <?php } ?>
            </div>
        </div>
    </div>
</div>
</div>
<?php init_tail(); ?>
<script>
$(function () {
    initDataTable('.table-haravan-products', '<?php echo admin_url('external_products/haravan_products'); ?>', [], [], undefined, [0, 'desc']);
    init_selectpicker();

    $('#sync-external-sku-form').on('submit', function (e) {
        e.preventDefault();

        var $select = $('#external-sync-sku');
        var sku = $select.val();

        if (!sku) {
            alert_float('danger', '<?php echo _l('select_external_product_sku'); ?>');
            return;
        }

        var $submitBtn = $('#sync-external-sku-submit');
        $submitBtn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> <?php echo _l('processing'); ?>');

        $.post('<?php echo admin_url('external_products/sync_haravan_product'); ?>', { sku: sku }, function (response) {
            if (response && response.success) {
                alert_float('success', response.message);
                $('.table-haravan-products').DataTable().ajax.reload();
                $select.find('option[value="' + sku.replace(/(["\\])/g, '\\$1') + '"]').remove();
                $select.selectpicker('refresh');
                if ($select.find('option').length === 0) {
                    $('#syncExternalSkuModal').modal('hide');
                    $('[data-target="#syncExternalSkuModal"]').prop('disabled', true);
                } else {
                    $select.selectpicker('val', '');
                }
            } else {
                var message = response && response.message ? response.message : '<?php echo _l('error_processing_request'); ?>';
                alert_float('danger', message);
            }
        }, 'json').fail(function () {
            alert_float('danger', '<?php echo _l('error_processing_request'); ?>');
        }).always(function () {
            $submitBtn.prop('disabled', false).html('<i class="fa fa-refresh"></i> <?php echo _l('sync_now'); ?>');
        });
    });
});
</script>
