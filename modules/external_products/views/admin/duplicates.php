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
                                <h4 class="no-margin"><?php echo _l('duplicate_mappings'); ?></h4>
                                <hr class="hr-panel-heading" />
                            </div>
                        </div>

                        <div class="row text-center mtop10">
                            <div class="col-md-3 col-sm-6 mtop15">
                                <div class="panel_s">
                                    <div class="panel-body">
                                        <h3 class="text-warning no-mtop"><?php echo (int) $duplicate_stats['duplicate_skus_count']; ?></h3>
                                        <p class="text-muted"><?php echo _l('duplicate_skus'); ?></p>
                                        <small class="text-muted"><?php echo (int) $duplicate_stats['duplicate_skus_total_records']; ?> records affected</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-6 mtop15">
                                <div class="panel_s">
                                    <div class="panel-body">
                                        <h3 class="text-danger no-mtop"><?php echo (int) $duplicate_stats['duplicate_mapping_ids_count']; ?></h3>
                                        <p class="text-muted"><?php echo _l('duplicate_mapping_ids'); ?></p>
                                        <small class="text-muted"><?php echo (int) $duplicate_stats['duplicate_mapping_ids_total_records']; ?> records affected</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-6 mtop15">
                                <div class="panel_s">
                                    <div class="panel-body">
                                        <h3 class="text-info no-mtop"><?php echo (int) $duplicate_stats['sku_conflicts_count']; ?></h3>
                                        <p class="text-muted"><?php echo _l('sku_conflicts'); ?></p>
                                        <small class="text-muted"><?php echo _l('sku_mapping_conflicts'); ?></small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-6 mtop15">
                                <div class="panel_s">
                                    <div class="panel-body">
                                        <h3 class="text-primary no-mtop"><?php echo (int) $duplicate_stats['mapping_conflicts_count']; ?></h3>
                                        <p class="text-muted"><?php echo _l('mapping_conflicts'); ?></p>
                                        <small class="text-muted"><?php echo _l('mapping_id_sku_conflicts'); ?></small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mtop20">
                            <div class="col-md-12">
                                <h5 class="no-margin-bottom"><?php echo _l('duplicate_skus'); ?></h5>
                                <hr />
                                <?php
                                    $table_data = [
                                        _l('sku'),
                                        _l('duplicate_count'),
                                        _l('mapping_ids'),
                                        _l('mapping_types'),
                                        _l('options'),
                                    ];
                                    render_datatable($table_data, 'duplicate-skus');
                                ?>
                            </div>
                        </div>

                        <div class="row mtop30">
                            <div class="col-md-12">
                                <h5 class="no-margin-bottom"><?php echo _l('duplicate_mapping_ids'); ?></h5>
                                <hr />
                                <?php
                                    $table_data = [
                                        _l('mapping_id'),
                                        _l('mapping_type'),
                                        _l('duplicate_count'),
                                        _l('skus'),
                                        _l('options'),
                                    ];
                                    render_datatable($table_data, 'duplicate-mapping-ids');
                                ?>
                            </div>
                        </div>

                        <div class="row mtop30">
                            <div class="col-md-12">
                                <h5 class="no-margin-bottom"><?php echo _l('sku_conflicts'); ?></h5>
                                <hr />
                                <?php
                                    $table_data = [
                                        _l('sku'),
                                        _l('mapping_type'),
                                        _l('conflict_count'),
                                        _l('mapping_ids'),
                                        _l('options'),
                                    ];
                                    render_datatable($table_data, 'sku-conflicts');
                                ?>
                            </div>
                        </div>

                        <div class="row mtop30">
                            <div class="col-md-12">
                                <h5 class="no-margin-bottom"><?php echo _l('mapping_conflicts'); ?></h5>
                                <hr />
                                <?php
                                    $table_data = [
                                        _l('mapping_id'),
                                        _l('mapping_type'),
                                        _l('conflict_count'),
                                        _l('skus'),
                                        _l('options'),
                                    ];
                                    render_datatable($table_data, 'mapping-conflicts');
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="duplicateDetailsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title"><?php echo _l('duplicate_details'); ?></h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-striped" id="duplicateDetailsTable">
                        <thead>
                            <tr>
                                <th><?php echo _l('select'); ?></th>
                                <th><?php echo _l('id'); ?></th>
                                <th><?php echo _l('sku'); ?></th>
                                <th><?php echo _l('mapping_id'); ?></th>
                                <th><?php echo _l('mapping_type'); ?></th>
                                <th><?php echo _l('action'); ?></th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" id="resolveDuplicates">
                    <i class="fa fa-check"></i> <?php echo _l('resolve_selected'); ?>
                </button>
                <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
            </div>
        </div>
    </div>
</div>

<?php init_tail(); ?>
<script>
(function($) {
    "use strict";

    var duplicateTables = {
        skus: initDataTable('.table-duplicate-skus', '<?php echo admin_url('external_products/duplicate_skus_table'); ?>', [], [], undefined, [1, 'desc']),
        mapping: initDataTable('.table-duplicate-mapping-ids', '<?php echo admin_url('external_products/duplicate_mapping_ids_table'); ?>', [], [], undefined, [2, 'desc']),
        skuConflicts: initDataTable('.table-sku-conflicts', '<?php echo admin_url('external_products/sku_conflicts_table'); ?>', [], [], undefined, [2, 'desc']),
        mappingConflicts: initDataTable('.table-mapping-conflicts', '<?php echo admin_url('external_products/mapping_conflicts_table'); ?>', [], [], undefined, [2, 'desc'])
    };

    var currentDuplicateType = '';
    var currentIdentifier = '';
    var currentMappingType = '';
    var keepRecordId = '';

    $(document).on('click', '.view-duplicates', function() {
        currentDuplicateType = $(this).data('type');
        currentIdentifier = $(this).data('identifier');
        currentMappingType = $(this).data('mapping-type') || '';
        keepRecordId = '';

        var params = {
            type: currentDuplicateType,
            identifier: currentIdentifier
        };

        if (currentMappingType !== '') {
            params.mapping_type = currentMappingType;
        }

        $.get('<?php echo admin_url('external_products/get_duplicate_details'); ?>', params).done(function(response) {
            var parsed = {};
            try {
                parsed = JSON.parse(response);
            } catch (e) {
                parsed = {data: []};
            }

            var tbody = $('#duplicateDetailsTable tbody');
            tbody.empty();

            (parsed.data || []).forEach(function(row) {
                tbody.append('<tr>' + row.join('') + '</tr>');
            });

            $('#duplicateDetailsTable').find('.keep-record').addClass('btn-success').removeClass('btn-warning').prop('disabled', false).text('<?php echo _l('keep_this'); ?>');
            $('#duplicateDetailsModal').modal('show');
        });
    });

    $(document).on('click', '.keep-record', function() {
        var id = $(this).data('id');
        keepRecordId = id;

        $('.duplicate-checkbox').prop('checked', false);
        $('.duplicate-checkbox[value="' + id + '"]').prop('checked', true);

        $('.keep-record').removeClass('btn-warning').addClass('btn-success').prop('disabled', false).text('<?php echo _l('keep_this'); ?>');
        $(this).removeClass('btn-success').addClass('btn-warning').prop('disabled', true).text('<?php echo _l('keeping'); ?>');
    });

    $('#resolveDuplicates').on('click', function() {
        var selectedIds = [];
        $('.duplicate-checkbox:checked').each(function() {
            selectedIds.push($(this).val());
        });

        if (selectedIds.length === 0) {
            alert_float('warning', '<?php echo _l('please_select_records_to_delete'); ?>');
            return;
        }

        if (keepRecordId && selectedIds.indexOf(keepRecordId) === -1) {
            alert_float('warning', '<?php echo _l('please_select_a_record_to_keep'); ?>');
            return;
        }

        var deleteIds = selectedIds.filter(function(id) {
            return id !== keepRecordId;
        });

        if (deleteIds.length === 0) {
            alert_float('info', '<?php echo _l('no_records_to_delete'); ?>');
            return;
        }

        if (!confirm('<?php echo _l('are_you_sure_resolve_duplicates'); ?>')) {
            return;
        }

        var url;
        var payload = {
            keep_id: keepRecordId,
            delete_ids: deleteIds
        };

        if (currentDuplicateType === 'sku' || currentDuplicateType === 'sku_conflict') {
            url = '<?php echo admin_url('external_products/resolve_duplicate_sku'); ?>';
            payload.sku = currentIdentifier;
        } else {
            url = '<?php echo admin_url('external_products/resolve_duplicate_mapping_id'); ?>';
            payload.mapping_id = currentIdentifier;
            payload.mapping_type = currentMappingType;
        }

        $.post(url, payload).done(function(response) {
            var result = {};
            try {
                result = JSON.parse(response);
            } catch (e) {
                result = {};
            }

            if (result.success) {
                alert_float('success', result.message);
                $('#duplicateDetailsModal').modal('hide');
                setTimeout(function() { location.reload(); }, 800);
            } else {
                alert_float('danger', result.message || '<?php echo _l('problem_processing_request'); ?>');
            }
        });
    });

    $('#duplicateDetailsModal').on('hidden.bs.modal', function() {
        keepRecordId = '';
        $('#duplicateDetailsTable tbody').empty();
    });
})(jQuery);
</script>
