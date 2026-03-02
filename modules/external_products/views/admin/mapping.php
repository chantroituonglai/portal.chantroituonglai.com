<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-12">
                                <h4 class="no-margin"><?php echo _l('external_products_mapping'); ?></h4>
                                <hr class="hr-panel-heading" />
                            </div>
                        </div>
                        <div class="row" id="duplicateAlert" style="display: none;">
                            <div class="col-md-12">
                                <div class="alert alert-warning">
                                    <i class="fa fa-exclamation-triangle"></i>
                                    <strong><?php echo _l('duplicate_warning'); ?></strong>
                                    <span id="duplicateAlertText"></span>
                                    <a href="<?php echo admin_url('external_products/duplicates'); ?>" class="btn btn-sm btn-warning pull-right">
                                        <i class="fa fa-eye"></i> <?php echo _l('view_duplicates'); ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="row mtop15">
                            <div class="col-md-12">
                                <div class="btn-group pull-right btn-with-tooltip-group _filter_data" data-toggle="tooltip" data-title="<?php echo _l('filter_by'); ?>">
                                    <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        <i class="fa fa-filter" aria-hidden="true"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-right" style="width:300px;">
                                        <li class="active">
                                            <a href="#" data-cview="all" onclick="dt_custom_view('', '.table-external-products-mapping', ''); return false;">
                                                <?php echo _l('view_all'); ?>
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                                <a href="<?php echo admin_url('external_products/add_mapping'); ?>" class="btn btn-info pull-right display-block mright5">
                                    <i class="fa fa-plus"></i> <?php echo _l('add_external_product'); ?>
                                </a>
                            </div>
                        </div>
                        <div class="table-responsive mtop25">
                            <table class="table dt-table table-external-products-mapping" data-order-col="1" data-order-type="desc">
                                <thead>
                                    <tr>
                                        <th>
                                            <div class="checkbox">
                                                <input type="checkbox" id="select_all">
                                                <label for="select_all"></label>
                                            </div>
                                        </th>
                                        <th><?php echo _l('id'); ?></th>
                                        <th><?php echo _l('sku'); ?></th>
                                        <th><?php echo _l('mapping_id'); ?></th>
                                        <th><?php echo _l('mapping_type'); ?></th>
                                        <th><?php echo _l('options'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Data loaded via AJAX -->
                                </tbody>
                            </table>
                        </div>
                        <div class="row mtop15">
                            <div class="col-md-12 text-right">
                                <button type="button" class="btn btn-danger" id="bulk_delete" style="display: none;">
                                    <i class="fa fa-trash"></i> <?php echo _l('delete_selected'); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
<script>
    $(function() {
        checkForDuplicates();

        var table = initDataTable('.table-external-products-mapping', '<?php echo admin_url('external_products/get_mapping_data'); ?>', [5], [5]);

        $('#select_all').on('change', function() {
            $('tbody input[type="checkbox"]').prop('checked', this.checked);
            toggleBulkActions();
        });

        $(document).on('change', 'tbody input[type="checkbox"]', function() {
            toggleBulkActions();
        });

        $('#bulk_delete').on('click', function() {
            var selectedIds = [];
            $('tbody input[type="checkbox"]:checked').each(function() {
                selectedIds.push($(this).val());
            });

            if (selectedIds.length === 0) {
                alert_float('warning', '<?php echo _l('bulk_actions_select_at_least_one_item'); ?>');
                return;
            }

            if (confirm('<?php echo _l('confirm_action_prompt'); ?>')) {
                $.post('<?php echo admin_url('external_products/bulk_action'); ?>', {
                    action: 'delete',
                    ids: selectedIds
                }).done(function(response) {
                    var result = JSON.parse(response);
                    if (result.success) {
                        alert_float('success', result.message);
                        table.ajax.reload();
                    } else {
                        alert_float('danger', result.message);
                    }
                });
            }
        });

        function toggleBulkActions() {
            var checkedCount = $('tbody input[type="checkbox"]:checked').length;
            $('#bulk_delete').toggle(checkedCount > 0);
        }

        function checkForDuplicates() {
            $.get('<?php echo admin_url('external_products/duplicates'); ?>').done(function(response) {
                var duplicateStats = $(response).find('.text-warning, .text-danger, .text-info, .text-primary');
                var hasDuplicates = false;
                var alertText = '';

                duplicateStats.each(function() {
                    var count = parseInt($(this).text(), 10);
                    if (count > 0) {
                        hasDuplicates = true;
                        var type = $(this).parent().find('p').text().toLowerCase();
                        alertText += (alertText ? ', ' : '') + count + ' ' + type;
                    }
                });

                if (hasDuplicates) {
                    $('#duplicateAlertText').text('Found: ' + alertText);
                    $('#duplicateAlert').show();
                }
            });
        }
    });
</script>
