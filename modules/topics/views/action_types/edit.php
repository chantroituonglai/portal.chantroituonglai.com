<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="tw-mb-6">
                    <div class="tw-flex tw-items-center tw-justify-between">
                        <h4 class="tw-mt-0 tw-font-semibold tw-text-lg tw-text-neutral-700">
                            <?php echo _l('edit_action_type'); ?>
                        </h4>
                    </div>
                </div>
                <?php echo form_open(admin_url('topics/action_types/edit/' . $action_type->id)); ?>
                <div class="panel_s">
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-12">
                                <?php 
                                echo render_input('name', 'action_type_name', $action_type->name, 'text', [
                                    'required' => true
                                ]); 
                                echo render_input('action_type_code', 'action_type_code', $action_type->action_type_code, 'text', [
                                    'required' => true
                                ]); 
                                $selected = $action_type->parent_id ?? '';
                                $parent_types = $this->Action_type_model->get_available_parents($action_type->id);
                                echo render_select('parent_id', 
                                    $parent_types,
                                    ['id', 'name'],
                                    'parent_action_type',
                                    $selected,
                                    ['data-width' => '100%',
                                     'data-none-selected-text' => _l('dropdown_non_selected_tex'),
                                     'data-live-search' => true]
                                );
                                echo render_input('position', 'position', $action_type->position, 'number', [
                                    'min' => 0
                                ]); 
                                ?>
                            </div>
                        </div>
                        <hr class="hr-panel-separator" />
                        <div class="btn-bottom-toolbar text-right">
                            <a href="<?php echo admin_url('topics/action_types'); ?>" class="btn btn-default pull-left">
                                <i class="fa fa-circle-left tw-mr-1"></i>
                                <?php echo _l('back'); ?>
                            </a>
                            
                            <?php if (has_permission('topics', '', 'delete')) { ?>
                                <a href="<?php echo admin_url('topics/action_types/delete/'.$action_type->id); ?>" 
                                   class="btn btn-danger _delete">
                                    <i class="fa fa-remove"></i> <?php echo _l('delete'); ?>
                                </a>
                            <?php } ?>
                            <button type="submit" class="btn btn-primary"><?php echo _l('submit'); ?></button>
                        </div>
                    </div>
                </div>
                <?php echo form_close(); ?>

                <!-- Action States Section -->
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="tw-font-semibold tw-text-lg tw-text-neutral-700 tw-mb-4">
                            <?php echo _l('action_states'); ?>
                        </h4>
                        <div class="table-responsive">
                            <table class="table table-action-states">
                                <thead>
                                    <tr>
                                        <th></th>
                                        <th><?php echo _l('action_state_name'); ?></th>
                                        <th><?php echo _l('action_state_code'); ?></th>
                                        <th><?php echo _l('position'); ?></th>
                                        <th><?php echo _l('valid_data'); ?></th>
                                        <th><?php echo _l('options'); ?></th>
                                    </tr>
                                </thead>
                                <tbody class="ui-sortable"></tbody>
                            </table>
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
    initActionStatesTable();
});

function initActionStatesTable() {
    var actionStatesTable = $('.table-action-states');
    
    // Add loading overlay
    var overlay = $('<div class="dt-loader"></div>').hide();
    actionStatesTable.before(overlay);
    
    var table = actionStatesTable.DataTable({
        autoWidth: false,
        processing: true,
        serverSide: false,
        ajax: {
            url: admin_url + 'topics/action_states/get_type_states/' + <?php echo $action_type->id; ?>,
            type: 'GET',
            beforeSend: function() {
                overlay.show();
                actionStatesTable.closest('.dataTables_wrapper').addClass('table-loading');
            },
            complete: function() {
                overlay.fadeOut('slow');
                actionStatesTable.closest('.dataTables_wrapper').removeClass('table-loading');
            }
        },
        columns: [
            {
                data: null,
                width: '5%',
                orderable: false,
                render: function() {
                    return '<i class="fa fa-bars drag-handle"></i>';
                }
            },
            {
                data: 'name',
                render: function(data, type, row) {
                    return '<a href="' + admin_url + 'topics/action_states/edit/' + row.id + '">' + data + '</a>';
                }
            },
            {data: 'action_state_code'},
            {data: 'position'},
            {
                data: 'valid_data',
                render: function(data, type, row) {
                    console.log('valid_data', data);
                    var checked = data == 1 ? 'checked' : '';
                    var html = '<div class="tw-flex tw-items-center tw-space-x-3">';
                    
                    // Fixed toggle switch - removed duplicate checked attribute
                    html += '<div class="onoffswitch">' +
                            '<input type="checkbox" data-mswitch-url="' + admin_url + 'topics/action_states/toggle_valid_data/' + row.id + '" ' +
                            'class="onoffswitch-checkbox" id="valid_data_' + row.id + '" ' + checked + '>' +
                            '<label class="onoffswitch-label" for="valid_data_' + row.id + '"></label>' +
                            '</div>';
                    
                    html += '</div>';
                    return html;
                }
            },
            {
                data: null,
                orderable: false,
                render: function(data) {
                    return '<div class="tw-flex tw-items-center tw-space-x-3">' +
                           '<a href="' + admin_url + 'topics/action_states/edit/' + data.id + 
                           '" class="tw-text-neutral-500 hover:tw-text-neutral-700">' +
                           '<i class="fa fa-pen-to-square fa-lg"></i></a>' +
                           '</div>';
                }
            }
        ],
        drawCallback: function(settings) {
            initSortable(this);
            overlay.fadeOut('slow');
            actionStatesTable.closest('.dataTables_wrapper').removeClass('table-loading');
        },
        rowCallback: function(row, data) {
            $(row).attr('data-state-id', data.id);
        },
        initComplete: function() {
            overlay.fadeOut('slow');
            actionStatesTable.closest('.dataTables_wrapper').removeClass('table-loading');
        }
    });

    // Handle toggle switch changes
    $(document).on('change', '.onoffswitch-checkbox', function() {
        var switch_url = $(this).data('mswitch-url');
        var switch_element = $(this);
        console.log(switch_url)
        $.get(switch_url).done(function(response) {
            response = JSON.parse(response);
            if (response.success) {
                alert_float('success', response.message);
            } else {
                alert_float('warning', response.message);
                switch_element.prop('checked', !switch_element.prop('checked'));
            }
        });
    });
}

function initSortable(table) {
    var fixHelper = function(e, ui) {
        ui.children().each(function() {
            $(this).width($(this).width());
        });
        return ui;
    };

    var $tbody = $(table).find('tbody');
    $tbody.sortable({
        helper: fixHelper,
        handle: '.drag-handle',
        placeholder: 'ui-state-highlight',
        axis: 'y',
        update: function(event, ui) {
            var positions = {};
            $tbody.find('tr').each(function(index) {
                var id = $(this).attr('data-state-id');
                if (id) {
                    positions[id] = index + 1;
                }
            });

            // Save new positions via AJAX
            $.post(admin_url + 'topics/action_states/reorder', {
                positions: positions
            }).done(function(response) {
                // Parse response if it's a string
                if (typeof response === 'string') {
                    try {
                        response = JSON.parse(response);
                    } catch (e) {
                        console.error('Failed to parse response:', e);
                    }
                }
                
                // Check if response has success property and it's true
                if (response && response.success === true) {
                    alert_float('success', response.message || 'Positions updated successfully');
                } else {
                    alert_float('warning', response.message || 'Error updating positions');
                }
            });
        }
    });
}
</script>

<style>
.drag-handle {
    cursor: move;
    padding: 5px;
}
.ui-sortable-helper {
    display: table;
    background: #fff;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
.ui-state-highlight {
    height: 50px;
    background: #f9f9f9;
    border: 1px dashed #ddd;
}
</style> 