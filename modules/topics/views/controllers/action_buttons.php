<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <!-- Header -->
                        <div class="row">
                            <div class="col-md-8">
                                <h4 class="no-margin">
                                    <?php echo _l('action_buttons_for_controller'); ?>: 
                                    <a href="<?php echo admin_url('topics/controllers/view/' . $controller_id); ?>">
                                        <?php echo html_escape($controller->site); ?>
                                    </a>
                                </h4>
                            </div>
                            <div class="col-md-4 text-right">
                                <?php if (has_permission('topics', '', 'create')) { ?>
                                <a href="#" class="btn btn-info" onclick="add_action_button_to_controller(); return false;">
                                    <i class="fa fa-plus"></i> <?php echo _l('add_action_button'); ?>
                                </a>
                                <?php } ?>
                                <a href="<?php echo admin_url('topics/controllers/view/' . $controller_id); ?>" class="btn btn-default">
                                    <i class="fa fa-arrow-left"></i> <?php echo _l('back_to_controller'); ?>
                                </a>
                            </div>
                        </div>
                        <hr class="hr-panel-separator" />
                        
                        <!-- Buttons Table -->
                        <div class="row">
                            <div class="col-md-12">
                                <?php
                                $table_data = [
                                    _l('name'),
                                    _l('button_type'),
                                    _l('workflow_id'),
                                    _l('action_command'),
                                    _l('target_action_type'),
                                    _l('target_action_state'),
                                    _l('status'),
                                    _l('order'),
                                    _l('options')
                                ];
                                render_datatable($table_data, 'controller-action-buttons');
                                ?>
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
        // Initialize datatable
        var controllerId = <?php echo $controller_id; ?>;
        var ActionButtonsServerParams = {};
        
        initDataTable('.table-controller-action-buttons', 
            admin_url + 'topics/controllers/get_action_buttons/' + controllerId, 
            [1, 2, 3, 4, 5, 6, 7], // searchable columns
            [1, 2, 3, 4, 5, 6, 7], // orderable columns
            ActionButtonsServerParams, 
            []
        );
        
        // Initialize sortable (dopo il caricamento della tabella)
        var orderUpdateUrl = admin_url + 'topics/controllers/update_button_assignment_order';
        
        init_action_buttons_sortable(orderUpdateUrl);
    });

    // Initialize action buttons sortable
    function init_action_buttons_sortable(orderUpdateUrl) {
        // Make sure the table is loaded
        var table = $('.table-controller-action-buttons tbody');
        if (table.length === 0) {
            setTimeout(function() {
                init_action_buttons_sortable(orderUpdateUrl);
            }, 500);
            return;
        }
        
        table.sortable({
            helper: fixHelperTableSortable,
            handle: '.order-handle',
            placeholder: 'ui-placeholder',
            axis: 'y',
            cursor: 'move',
            items: 'tr',
            update: function(event, ui) {
                var orders = [];
                var _order = 1;
                
                // Get the sorted items
                table.find('tr').each(function() {
                    var id = $(this).find('a').first().attr('onclick');
                    if (id) {
                        id = id.match(/edit_action_button_assignment\((\d+)\)/);
                        if (id && id[1]) {
                            orders.push({
                                id: id[1],
                                order: _order
                            });
                            _order++;
                        }
                    }
                });
                
                // Update the orders via AJAX
                $.post(orderUpdateUrl, {
                    orders: orders
                }).done(function(response) {
                    var result = JSON.parse(response);
                    if (result.success) {
                        // Refresh the table to reflect the new order
                        $('.table-controller-action-buttons').DataTable().ajax.reload(null, false);
                        alert_float('success', result.message);
                    } else {
                        alert_float('danger', result.message);
                    }
                });
            }
        });
    }
    
    // Helper for sortable table
    function fixHelperTableSortable(e, ui) {
        ui.children().each(function() {
            $(this).width($(this).width());
        });
        return ui;
    }
    
    // Add action button to controller
    function add_action_button_to_controller() {
        var controllerId = <?php echo $controller_id; ?>;
        var url = admin_url + 'topics/controllers/add_action_button_to_controller/' + controllerId;
        
        $.get(url, function(response) {
            $('#action_button_modal').remove();
            $('body').append(response);
            $('#action_button_modal').modal('show');
        });
    }
    
    // Edit action button assignment
    function edit_action_button_assignment(id) {
        var url = admin_url + 'topics/controllers/edit_action_button_assignment/' + id;
        
        $.get(url, function(response) {
            $('#action_button_modal').remove();
            $('body').append(response);
            $('#action_button_modal').modal('show');
        });
    }
</script> 