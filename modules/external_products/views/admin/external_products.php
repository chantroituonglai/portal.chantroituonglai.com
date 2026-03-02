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
                                <h4 class="no-margin"><?php echo _l('external_products'); ?></h4>
                            </div>
                            <div class="col-md-6 text-right">
                                <a href="<?php echo admin_url('importsync/csv_mappings'); ?>" class="btn btn-default pull-right display-block mright5">
                                    <i class="fa fa-upload"></i> Import
                                </a>
                                <a href="<?php echo admin_url('external_products/add_mapping'); ?>" id="btn-add-external-product" data-url="<?php echo admin_url('external_products/add_mapping'); ?>" class="btn btn-info pull-right display-block mright5">
                                    <i class="fa fa-plus"></i> <?php echo _l('add_external_product'); ?>
                                </a>
                            </div>
                        </div>
                        <hr class="hr-panel-heading" />
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="mapping_type_filter"><?php echo _l('mapping_type'); ?></label>
                                    <select id="mapping_type_filter" name="mapping_type_filter" class="selectpicker" data-width="200px" data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>">
                                        <option value=""></option>
                                        <option value="fast_barco">Fast Barco</option>
                                        <option value="aeon_sku">AEON SKU</option>
                                        <option value="emart">Emart</option>
                                        <option value="emart_sku">Emart SKU</option>
                                        <option value="woo">WooCommerce</option>
                                        <option value="shopify">Shopify</option>
                                        <option value="magento">Magento</option>
                                        <option value="amazon">Amazon</option>
                                        <option value="ebay">eBay</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <?php
                        $table_data = [
                            _l('id'),
                            _l('sku'),
                            _l('mapping_id'),
                            _l('mapping_type'),
                            _l('options'),
                        ];
                        render_datatable($table_data, 'external-products');
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
<div class="modal fade" id="externalProductModal" tabindex="-1" role="dialog" aria-labelledby="externalProductModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="externalProductModalLabel"><?php echo _l('add_external_product'); ?></h4>
            </div>
            <div class="modal-body">
                <div id="external-product-form-container"></div>
            </div>
        </div>
    </div>
    </div>
<script>
$(function() {
    init_selectpicker();

    var serverParams = {
        mapping_type: '[name="mapping_type_filter"]'
    };

    var externalProductsTable = initDataTable('.table-external-products', '<?php echo admin_url('external_products'); ?>', false, false, serverParams, [0, 'desc']);

    $('#mapping_type_filter').on('changed.bs.select clear.bs.select', function() {
        externalProductsTable.ajax.reload();
    });

    $('#btn-add-external-product').on('click', function(e) {
        e.preventDefault();
        var url = $(this).data('url') || $(this).attr('href');
        // Load the form HTML and extract only the form to avoid executing page scripts
        $.get(url, function(html) {
            var $html = $('<div>').html(html);
            var $form = $html.find('#add_mapping_form');
            if ($form.length === 0) {
                alert_float('danger', '<?php echo _l('problem_processing_request'); ?>');
                return;
            }
            $('#external-product-form-container').html($form);

            // Re-init any selectpickers inside the modal
            if (typeof init_selectpicker === 'function') {
                init_selectpicker();
            }

            // Bind submit handler for AJAX submit
            $('#external-product-form-container').find('#add_mapping_form').off('submit').on('submit', function(ev) {
                ev.preventDefault();
                var $submitBtn = $(this).find('button[type="submit"]');
                $.post($(this).attr('action'), $(this).serialize())
                    .done(function(response) {
                        var result;
                        try { result = JSON.parse(response); } catch (e) { result = { success:false, message:'<?php echo _l('problem_processing_request'); ?>' }; }
                        if (result.success) {
                            alert_float('success', result.message);
                            $('#externalProductModal').modal('hide');
                            if (externalProductsTable && externalProductsTable.ajax) {
                                externalProductsTable.ajax.reload();
                            }
                        } else {
                            alert_float('danger', result.message || '<?php echo _l('problem_processing_request'); ?>');
                        }
                    })
                    .fail(function() {
                        alert_float('danger', '<?php echo _l('problem_processing_request'); ?>');
                    })
                    .always(function() {
                        if ($submitBtn.length) {
                            $submitBtn.prop('disabled', false);
                        }
                    });
            });

            $('#externalProductModal').modal('show');
        }).fail(function() {
            alert_float('danger', '<?php echo _l('problem_processing_request'); ?>');
        });
    });
});
</script>
