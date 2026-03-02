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
                                <h4 class="no-margin"><?php echo _l('order_mapping'); ?></h4>
                            </div>
                            <div class="col-md-6 text-right">
                                <button type="button" class="btn btn-info btn-sm" id="bulk_update_order_mapping" style="display:none;">
                                    <i class="fa fa-sliders"></i> Bulk update
                                </button>
                                <button type="button" class="btn btn-danger btn-sm" id="bulk_delete_order_mapping" style="display:none;">
                                    <i class="fa fa-trash"></i> <?php echo _l('delete'); ?>
                                </button>
                            </div>
                        </div>
                        <hr class="hr-panel-heading" />
                        <div class="alert alert-info">
                            <i class="fa fa-info-circle"></i> <?php echo _l('order_mapping_help'); ?>
                        </div>
                        <?php
                        $table_data = [
                            '<div class="checkbox"><input type="checkbox" id="select_all_order_mapping"><label></label></div>',
                            _l('id'),
                            _l('external_order_id'),
                            _l('internal_order_id'),
                            _l('external_system'),
                            _l('mapping_status'),
                            _l('created_date'),
                            _l('options'),
                        ];
                        render_datatable($table_data, 'order-mapping');
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="bulkUpdateOrderMappingModal" tabindex="-1" role="dialog" aria-labelledby="bulkUpdateOrderMappingLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="bulkUpdateOrderMappingLabel">Bulk update order mapping</h4>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="bulk_update_action">Action</label>
                    <select id="bulk_update_action" class="form-control">
                        <option value="set_mapping_status">Set mapping status</option>
                        <option value="clear_data">Clear data</option>
                    </select>
                </div>
                <div class="form-group" id="bulk_mapping_status_group">
                    <label for="bulk_mapping_status">Mapping status</label>
                    <input type="text" id="bulk_mapping_status" class="form-control" placeholder="e.g. NOT_FOUND, ACTIVE, INACTIVE">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('cancel'); ?></button>
                <button type="button" class="btn btn-primary" id="bulk_update_order_mapping_submit">Apply</button>
            </div>
        </div>
    </div>
</div>

<?php init_tail(); ?>
<script>
(function ($) {
    'use strict';

    var table = initDataTable('.table-order-mapping', '<?php echo admin_url('external_products/order_mapping'); ?>', [0, 7], [0, 7], undefined, [1, 'desc']);

    function toggleBulkDeleteButton() {
        var checkedCount = $('.table-order-mapping tbody input.order-mapping-select:checked').length;
        if (checkedCount > 0) {
            $('#bulk_delete_order_mapping').show();
            $('#bulk_update_order_mapping').show();
        } else {
            $('#bulk_delete_order_mapping').hide();
            $('#bulk_update_order_mapping').hide();
        }
    }

    $('#select_all_order_mapping').on('change', function () {
        var checked = $(this).prop('checked');
        $('.table-order-mapping tbody input.order-mapping-select').prop('checked', checked);
        toggleBulkDeleteButton();
    });

    $('body').on('change', '.table-order-mapping tbody input.order-mapping-select', function () {
        var total = $('.table-order-mapping tbody input.order-mapping-select').length;
        var checked = $('.table-order-mapping tbody input.order-mapping-select:checked').length;
        $('#select_all_order_mapping').prop('checked', total > 0 && total === checked);
        toggleBulkDeleteButton();
    });

    $('#bulk_delete_order_mapping').on('click', function () {
        if (!confirm_delete()) {
            return;
        }

        var ids = [];
        $('.table-order-mapping tbody input.order-mapping-select:checked').each(function () {
            ids.push($(this).val());
        });

        if (ids.length === 0) {
            return;
        }

        var data = { ids: ids };
        if (typeof csrfData !== 'undefined') {
            data[csrfData.token_name] = csrfData.hash;
        }

        $.post('<?php echo admin_url('external_products/delete_order_mapping_bulk'); ?>', data).done(function (response) {
            var result = {};
            try {
                result = JSON.parse(response);
            } catch (e) {
                result = { success: false, message: app.lang.something_went_wrong };
            }

            if (result.success) {
                alert_float('success', result.message);
                $('#select_all_order_mapping').prop('checked', false);
                $('#bulk_delete_order_mapping').hide();
                table.ajax.reload(null, false);
            } else {
                alert_float('danger', result.message || app.lang.something_went_wrong);
            }
        });
    });

    $('#bulk_update_order_mapping').on('click', function () {
        $('#bulkUpdateOrderMappingModal').modal('show');
    });

    $('#bulk_update_action').on('change', function () {
        var action = $(this).val();
        if (action === 'set_mapping_status') {
            $('#bulk_mapping_status_group').show();
        } else {
            $('#bulk_mapping_status_group').hide();
        }
    }).trigger('change');

    $('#bulk_update_order_mapping_submit').on('click', function () {
        var ids = [];
        $('.table-order-mapping tbody input.order-mapping-select:checked').each(function () {
            ids.push($(this).val());
        });
        if (ids.length === 0) {
            alert_float('warning', app.lang.no_items_selected || 'No items selected');
            return;
        }

        var action = $('#bulk_update_action').val();
        var mappingStatus = $('#bulk_mapping_status').val().trim();
        if (action === 'set_mapping_status' && mappingStatus === '') {
            alert_float('warning', 'Please enter mapping status');
            return;
        }

        var data = { ids: ids, action: action, mapping_status: mappingStatus };
        if (typeof csrfData !== 'undefined') {
            data[csrfData.token_name] = csrfData.hash;
        }

        $.post('<?php echo admin_url('external_products/bulk_update_order_mapping'); ?>', data).done(function (response) {
            var result = {};
            try {
                result = JSON.parse(response);
            } catch (e) {
                result = { success: false, message: app.lang.something_went_wrong };
            }

            if (result.success) {
                alert_float('success', result.message || 'Updated');
                $('#bulkUpdateOrderMappingModal').modal('hide');
                table.ajax.reload(null, false);
            } else {
                alert_float('danger', result.message || app.lang.something_went_wrong);
            }
        });
    });

    $('body').on('click', '.table-order-mapping ._delete', function (e) {
        e.preventDefault();
        e.stopImmediatePropagation();

        if (!confirm_delete()) {
            return false;
        }

        var url = $(this).attr('href');
        $.get(url).done(function () {
            if (table && table.ajax) {
                table.ajax.reload(null, false);
            }
        });

        return false;
    });
})(jQuery);
</script>
