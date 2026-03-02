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
                                <h4 class="no-margin"><?php echo _l('external_orders'); ?></h4>
                            </div>
                            <div class="col-md-6 text-right">
                                <a href="<?php echo admin_url('external_products/add_order'); ?>" class="btn btn-success mright5">
                                    <i class="fa fa-plus"></i> <?php echo _l('add_external_order'); ?>
                                </a>
                                <button type="button" class="btn btn-info" onclick="syncOrders();">
                                    <i class="fa fa-refresh"></i> <?php echo _l('sync_orders'); ?>
                                </button>
                            </div>
                        </div>
                        <hr class="hr-panel-heading" />
                        <?php
                        $table_data = [
                            _l('id'),
                            _l('external_order_id'),
                            _l('order_number'),
                            _l('customer_name'),
                            _l('customer_email'),
                            _l('order_status'),
                            _l('total_amount'),
                            _l('order_date'),
                            _l('options'),
                        ];
                        render_datatable($table_data, 'external-orders');
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Sync Orders Modal -->
<div class="modal fade" id="syncOrdersModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title"><?php echo _l('sync_orders_from_system'); ?></h4>
            </div>
            <div class="modal-body">
                <form id="syncOrdersForm">
                    <div class="form-group">
                        <label for="system"><?php echo _l('select_system_to_sync'); ?></label>
                        <select class="form-control" id="system" name="system" required>
                            <option value=""><?php echo _l('select_option'); ?></option>
                            <?php foreach ($external_systems as $system): ?>
                                <option value="<?php echo $system['system_name']; ?>"><?php echo $system['system_name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <p class="text-muted"><?php echo _l('sync_orders_help'); ?></p>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('cancel'); ?></button>
                <button type="button" class="btn btn-primary" onclick="performSync()"><?php echo _l('sync_orders'); ?></button>
            </div>
        </div>
    </div>
</div>

<?php init_tail(); ?>
<script>
(function ($) {
    'use strict';

    initDataTable('.table-external-orders', '<?php echo admin_url('external_products/orders'); ?>', [], [], undefined, [0, 'desc']);

    window.syncOrders = function syncOrders() {
        $('#syncOrdersModal').modal('show');
    };

    window.performSync = function performSync() {
        var system = $('#system').val();
        if (!system) {
            alert_float('danger', '<?php echo _l('select_system_to_sync'); ?>');
            return;
        }

        $.ajax({
            url: '<?php echo admin_url('external_products/sync_orders'); ?>',
            type: 'POST',
            data: { system: system },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert_float('success', response.message);
                    $('#syncOrdersModal').modal('hide');
                    $('.table-external-orders').DataTable().ajax.reload();
                } else {
                    alert_float('danger', response.message);
                }
            },
            error: function() {
                alert_float('danger', '<?php echo _l('problem_processing_request'); ?>');
            }
        });
    };
})(jQuery);
</script>
