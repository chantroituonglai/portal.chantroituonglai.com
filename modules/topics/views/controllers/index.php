<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>

<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <?php if (has_permission('topics', '', 'create')) { ?>
                        <div class="_buttons">
                            <a href="<?php echo admin_url('topics/controllers/create'); ?>" class="btn btn-info pull-left">
                                <?php echo _l('new_controller'); ?>
                            </a>
                        </div>
                        <div class="clearfix"></div>
                        <hr class="hr-panel-separator" />
                        <?php } ?>
                        
                        <?php render_datatable([
                            _l('id'),
                            _l('site'),
                            _l('platform'),
                            _l('status'),
                            _l('datecreated'),
                            _l('options')
                        ], 'controllers'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php init_tail(); ?>

<script>
$(function() {
    var controllersTable = $('.table-controllers').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: admin_url + 'topics/controllers/table',
            type: 'POST',
            beforeSend: function() {
                $('.table-controllers').addClass('dt-table-loading');
                $('.dataTables_processing').addClass('dt-loader');
            },
            complete: function() {
                $('.table-controllers').removeClass('dt-table-loading');
                $('.dataTables_processing').removeClass('dt-loader');
                $('.dataTables_wrapper').removeClass('table-loading');
            }
        },
        // order: [[5, 'desc']], // Order by date created
        pageLength: 25,
        responsive: true,
        autoWidth: true,
        columnDefs: [
            { targets: [5], orderable: false }, // Options column
            { width: "50px", targets: 0 },      // ID
            { width: "150px", targets: 1 },     // Site
            { width: "100px", targets: 2 },     // Platform
            { width: "80px", targets: 3 },      // Status
            { width: "120px", targets: 4 },     // Date
            { width: "100px", targets: 5 },     // Options
            { 
                targets: [1,2],
                className: "text-nowrap",
                overflow: "hidden",
                textOverflow: "ellipsis"
            }
        ]
    });
});
</script> 