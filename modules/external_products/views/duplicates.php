<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <?php $this->load->view('admin/includes/aside'); ?>
    <div class="content-wrapper">
        <?php $this->load->view('admin/includes/header'); ?>
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
                            
                            <!-- Statistics Cards -->
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="panel_s">
                                        <div class="panel-body text-center">
                                            <h3 class="text-warning"><?php echo $duplicate_stats['duplicate_skus_count']; ?></h3>
                                            <p class="text-muted"><?php echo _l('duplicate_skus'); ?></p>
                                            <small class="text-muted"><?php echo $duplicate_stats['duplicate_skus_total_records']; ?> records affected</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="panel_s">
                                        <div class="panel-body text-center">
                                            <h3 class="text-danger"><?php echo $duplicate_stats['duplicate_mapping_ids_count']; ?></h3>
                                            <p class="text-muted"><?php echo _l('duplicate_mapping_ids'); ?></p>
                                            <small class="text-muted"><?php echo $duplicate_stats['duplicate_mapping_ids_total_records']; ?> records affected</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="panel_s">
                                        <div class="panel-body text-center">
                                            <h3 class="text-info"><?php echo $duplicate_stats['sku_conflicts_count']; ?></h3>
                                            <p class="text-muted"><?php echo _l('sku_conflicts'); ?></p>
                                            <small class="text-muted"><?php echo _l('sku_mapping_conflicts'); ?></small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="panel_s">
                                        <div class="panel-body text-center">
                                            <h3 class="text-primary"><?php echo $duplicate_stats['mapping_conflicts_count']; ?></h3>
                                            <p class="text-muted"><?php echo _l('mapping_conflicts'); ?></p>
                                            <small class="text-muted"><?php echo _l('mapping_id_sku_conflicts'); ?></small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Duplicate SKUs Section -->
                            <div class="row">
                                <div class="col-md-12">
                                    <h5><?php echo _l('duplicate_skus'); ?> <span class="badge badge-warning"><?php echo count($duplicate_skus); ?></span></h5>
                                    <hr />
                                    <?php if (!empty($duplicate_skus)) { ?>
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th><?php echo _l('sku'); ?></th>
                                                    <th><?php echo _l('duplicate_count'); ?></th>
                                                    <th><?php echo _l('mapping_ids'); ?></th>
                                                    <th><?php echo _l('mapping_types'); ?></th>
                                                    <th><?php echo _l('actions'); ?></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($duplicate_skus as $duplicate) { ?>
                                                <tr>
                                                    <td><strong><?php echo $duplicate['sku']; ?></strong></td>
                                                    <td><span class="badge badge-warning"><?php echo $duplicate['count']; ?></span></td>
                                                    <td><?php echo $duplicate['mapping_ids']; ?></td>
                                                    <td><?php echo $duplicate['mapping_types']; ?></td>
                                                    <td>
                                                        <button class="btn btn-sm btn-info view-duplicates" 
                                                                data-type="sku" 
                                                                data-identifier="<?php echo $duplicate['sku']; ?>">
                                                            <i class="fa fa-eye"></i> <?php echo _l('view_details'); ?>
                                                        </button>
                                                    </td>
                                                </tr>
                                                <?php } ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <?php } else { ?>
                                    <div class="alert alert-success">
                                        <i class="fa fa-check-circle"></i> <?php echo _l('no_duplicate_skus_found'); ?>
                                    </div>
                                    <?php } ?>
                                </div>
                            </div>

                            <!-- Duplicate Mapping IDs Section -->
                            <div class="row">
                                <div class="col-md-12">
                                    <h5><?php echo _l('duplicate_mapping_ids'); ?> <span class="badge badge-danger"><?php echo count($duplicate_mapping_ids); ?></span></h5>
                                    <hr />
                                    <?php if (!empty($duplicate_mapping_ids)) { ?>
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th><?php echo _l('mapping_id'); ?></th>
                                                    <th><?php echo _l('mapping_type'); ?></th>
                                                    <th><?php echo _l('duplicate_count'); ?></th>
                                                    <th><?php echo _l('skus'); ?></th>
                                                    <th><?php echo _l('actions'); ?></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($duplicate_mapping_ids as $duplicate) { ?>
                                                <tr>
                                                    <td><strong><?php echo $duplicate['mapping_id']; ?></strong></td>
                                                    <td><span class="mapping-type-badge <?php echo $duplicate['mapping_type']; ?>"><?php echo format_mapping_type($duplicate['mapping_type']); ?></span></td>
                                                    <td><span class="badge badge-danger"><?php echo $duplicate['count']; ?></span></td>
                                                    <td><?php echo $duplicate['skus']; ?></td>
                                                    <td>
                                                        <button class="btn btn-sm btn-info view-duplicates" 
                                                                data-type="mapping_id" 
                                                                data-identifier="<?php echo $duplicate['mapping_id']; ?>"
                                                                data-mapping-type="<?php echo $duplicate['mapping_type']; ?>">
                                                            <i class="fa fa-eye"></i> <?php echo _l('view_details'); ?>
                                                        </button>
                                                    </td>
                                                </tr>
                                                <?php } ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <?php } else { ?>
                                    <div class="alert alert-success">
                                        <i class="fa fa-check-circle"></i> <?php echo _l('no_duplicate_mapping_ids_found'); ?>
                                    </div>
                                    <?php } ?>
                                </div>
                            </div>

                            <!-- SKU Conflicts Section -->
                            <div class="row">
                                <div class="col-md-12">
                                    <h5><?php echo _l('sku_conflicts'); ?> <span class="badge badge-info"><?php echo count($sku_conflicts); ?></span></h5>
                                    <hr />
                                    <?php if (!empty($sku_conflicts)) { ?>
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th><?php echo _l('sku'); ?></th>
                                                    <th><?php echo _l('mapping_type'); ?></th>
                                                    <th><?php echo _l('conflict_count'); ?></th>
                                                    <th><?php echo _l('mapping_ids'); ?></th>
                                                    <th><?php echo _l('actions'); ?></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($sku_conflicts as $conflict) { ?>
                                                <tr>
                                                    <td><strong><?php echo $conflict['sku']; ?></strong></td>
                                                    <td><span class="mapping-type-badge <?php echo $conflict['mapping_type']; ?>"><?php echo format_mapping_type($conflict['mapping_type']); ?></span></td>
                                                    <td><span class="badge badge-info"><?php echo $conflict['mapping_count']; ?></span></td>
                                                    <td><?php echo $conflict['mapping_ids']; ?></td>
                                                    <td>
                                                        <button class="btn btn-sm btn-warning view-conflicts" 
                                                                data-type="sku_conflict" 
                                                                data-sku="<?php echo $conflict['sku']; ?>"
                                                                data-mapping-type="<?php echo $conflict['mapping_type']; ?>">
                                                            <i class="fa fa-exclamation-triangle"></i> <?php echo _l('resolve_conflict'); ?>
                                                        </button>
                                                    </td>
                                                </tr>
                                                <?php } ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <?php } else { ?>
                                    <div class="alert alert-success">
                                        <i class="fa fa-check-circle"></i> <?php echo _l('no_sku_conflicts_found'); ?>
                                    </div>
                                    <?php } ?>
                                </div>
                            </div>

                            <!-- Mapping ID Conflicts Section -->
                            <div class="row">
                                <div class="col-md-12">
                                    <h5><?php echo _l('mapping_conflicts'); ?> <span class="badge badge-primary"><?php echo count($mapping_conflicts); ?></span></h5>
                                    <hr />
                                    <?php if (!empty($mapping_conflicts)) { ?>
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th><?php echo _l('mapping_id'); ?></th>
                                                    <th><?php echo _l('mapping_type'); ?></th>
                                                    <th><?php echo _l('conflict_count'); ?></th>
                                                    <th><?php echo _l('skus'); ?></th>
                                                    <th><?php echo _l('actions'); ?></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($mapping_conflicts as $conflict) { ?>
                                                <tr>
                                                    <td><strong><?php echo $conflict['mapping_id']; ?></strong></td>
                                                    <td><span class="mapping-type-badge <?php echo $conflict['mapping_type']; ?>"><?php echo format_mapping_type($conflict['mapping_type']); ?></span></td>
                                                    <td><span class="badge badge-primary"><?php echo $conflict['sku_count']; ?></span></td>
                                                    <td><?php echo $conflict['skus']; ?></td>
                                                    <td>
                                                        <button class="btn btn-sm btn-warning view-conflicts" 
                                                                data-type="mapping_conflict" 
                                                                data-mapping-id="<?php echo $conflict['mapping_id']; ?>"
                                                                data-mapping-type="<?php echo $conflict['mapping_type']; ?>">
                                                            <i class="fa fa-exclamation-triangle"></i> <?php echo _l('resolve_conflict'); ?>
                                                        </button>
                                                    </td>
                                                </tr>
                                                <?php } ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <?php } else { ?>
                                    <div class="alert alert-success">
                                        <i class="fa fa-check-circle"></i> <?php echo _l('no_mapping_conflicts_found'); ?>
                                    </div>
                                    <?php } ?>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <a href="<?php echo admin_url('external_products/mapping'); ?>" class="btn btn-info">
                                            <i class="fa fa-list"></i> <?php echo _l('view_all_mappings'); ?>
                                        </a>
                                        <a href="<?php echo admin_url('external_products/statistics'); ?>" class="btn btn-default">
                                            <i class="fa fa-bar-chart"></i> <?php echo _l('mapping_statistics'); ?>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php $this->load->view('admin/includes/footer'); ?>
    </div>
</div>

<!-- Duplicate Details Modal -->
<div class="modal fade" id="duplicateDetailsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
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
                        <tbody>
                            <!-- Data will be loaded via AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" id="resolveDuplicates">
                    <i class="fa fa-check"></i> <?php echo _l('resolve_selected'); ?>
                </button>
                <button type="button" class="btn btn-default" data-dismiss="modal">
                    <?php echo _l('close'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<?php init_tail(); ?>
<script>
$(function() {
    var currentDuplicateType = '';
    var currentIdentifier = '';
    var currentMappingType = '';
    var keepRecordId = '';

    // View duplicate details
    $('.view-duplicates').on('click', function() {
        currentDuplicateType = $(this).data('type');
        currentIdentifier = $(this).data('identifier');
        currentMappingType = $(this).data('mapping-type');
        
        var url = '<?php echo admin_url('external_products/get_duplicate_details'); ?>';
        var params = {
            type: currentDuplicateType,
            identifier: currentIdentifier
        };
        
        if (currentMappingType) {
            params.mapping_type = currentMappingType;
        }
        
        $.ajax({
            url: url,
            type: 'GET',
            data: params,
            success: function(response) {
                var data = JSON.parse(response);
                var tbody = $('#duplicateDetailsTable tbody');
                tbody.empty();
                
                data.data.forEach(function(row) {
                    tbody.append('<tr>' + row.join('') + '</tr>');
                });
                
                $('#duplicateDetailsModal').modal('show');
            }
        });
    });

    // Keep record functionality
    $(document).on('click', '.keep-record', function() {
        var id = $(this).data('id');
        keepRecordId = id;
        
        // Uncheck all checkboxes
        $('.duplicate-checkbox').prop('checked', false);
        
        // Check the keep record checkbox
        $('.duplicate-checkbox[value="' + id + '"]').prop('checked', true);
        
        // Disable the keep button for this record
        $('.keep-record').prop('disabled', true);
        $(this).prop('disabled', false).removeClass('btn-success').addClass('btn-warning').text('<?php echo _l('keeping'); ?>');
    });

    // Resolve duplicates
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
        
        if (confirm('<?php echo _l('are_you_sure_resolve_duplicates'); ?>')) {
            var url = '';
            var data = {
                keep_id: keepRecordId,
                delete_ids: deleteIds
            };
            
            if (currentDuplicateType === 'sku') {
                url = '<?php echo admin_url('external_products/resolve_duplicate_sku'); ?>';
                data.sku = currentIdentifier;
            } else if (currentDuplicateType === 'mapping_id') {
                url = '<?php echo admin_url('external_products/resolve_duplicate_mapping_id'); ?>';
                data.mapping_id = currentIdentifier;
                data.mapping_type = currentMappingType;
            }
            
            $.ajax({
                url: url,
                type: 'POST',
                data: data,
                success: function(response) {
                    var result = JSON.parse(response);
                    if (result.success) {
                        alert_float('success', result.message);
                        $('#duplicateDetailsModal').modal('hide');
                        location.reload();
                    } else {
                        alert_float('danger', result.message);
                    }
                }
            });
        }
    });
});
</script>
</body>
</html>
