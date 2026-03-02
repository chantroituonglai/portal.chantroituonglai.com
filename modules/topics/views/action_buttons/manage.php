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
                            <a href="#" class="btn btn-info pull-left" onclick="new_action_button(); return false;">
                                <?php echo _l('new_action_button'); ?>
                            </a>
                            <button id="toggle-reposition" class="btn btn-default pull-right">
                                <?php echo _l('reposition'); ?>
                            </button>
                            <button id="save-positions" class="btn btn-info pull-right hide">
                                <?php echo _l('save_positions'); ?>
                            </button>
                        </div>
                        <div class="clearfix"></div>
                        <hr class="hr-panel-separator" />
                        <?php } ?>
                        
                        <?php render_datatable([
                            _l('name'),
                            _l('button_type'),
                            _l('workflow_id'),
                            _l('trigger_type'),
                            _l('target_action_type'),
                            _l('target_action_state'),
                            _l('description'),
                            _l('status'),
                            _l('order'),
                            _l('options')
                        ], 'action-buttons'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Sort Modal -->
<div class="modal fade" id="sort-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title"><?php echo _l('reorder_action_buttons'); ?></h4>
            </div>
            <div class="modal-body">
                <ul id="sortable" class="list-group">
                    <!-- Items will be populated dynamically -->
                </ul>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
                <button type="button" class="btn btn-info" id="save-sort"><?php echo _l('save'); ?></button>
            </div>
        </div>
    </div>
</div>

<?php $this->load->view('topics/action_buttons/modal'); ?>
<?php init_tail(); ?>

<script>
$(function() {
    var actionButtonsTable = initDataTable('.table-action-buttons', window.location.href, [9], [9], undefined, [8, 'asc']);
    
    // Customize column widths and classes
    actionButtonsTable.on('draw', function() {
        // Name column
        $(this).find('td:nth-child(1)').addClass('text-nowrap');
        
        // Button Type column - keep compact
        $(this).find('td:nth-child(2)').addClass('text-nowrap min-width-150');
        
        // Workflow ID column
        $(this).find('td:nth-child(3)').addClass('text-nowrap min-width-150');
        
        // Trigger Type column
        $(this).find('td:nth-child(4)').addClass('text-nowrap min-width-100');
        
        // Target Action Type column
        $(this).find('td:nth-child(5)').addClass('text-nowrap min-width-150');
        
        // Target Action State column
        $(this).find('td:nth-child(6)').addClass('text-nowrap min-width-150');
        
        // Description column - allow wrapping
        $(this).find('td:nth-child(7)').addClass('text-wrap min-width-200');
        
        // Status column - keep compact
        $(this).find('td:nth-child(8)').addClass('text-center min-width-100');
        
        // Order column - keep compact
        $(this).find('td:nth-child(9)').addClass('text-center min-width-50');
        
        // Options column - keep compact
        $(this).find('td:nth-child(10)').addClass('text-right min-width-100');
    });

    // Initialize sortable
    var isRepositioning = false;
    
    $('#toggle-reposition').on('click', function() {
        // Load buttons for sorting
        $.get(admin_url + 'topics/get_buttons_for_sorting', function(response) {
            response = JSON.parse(response);
            if (response.success) {
                var $sortable = $('#sortable');
                $sortable.empty();
             
                response.buttons.forEach(function(button) {
                    $sortable.append(
                        `<li class="list-group-item" data-id="${button.id}">
                            <i class="fa fa-bars handle mr-2"></i>
                            <span>${button.name}</span>
                            <input type="hidden" name="order[]" value="${button.id}">
                        </li>`
                    );
                });
                
                // Initialize jQuery UI sortable
                $sortable.sortable({
                    handle: '.handle',
                    axis: 'y',
                    update: function(event, ui) {
                        // Optional: Update hidden inputs with new order
                    }
                });
                
                $('#sort-modal').modal('show');
            }
        });
    });
    
    // Handle save sort
    $('#save-sort').on('click', function() {
        var orders = [];
        $('#sortable li').each(function(index) {
            orders.push({
                id: $(this).data('id'),
                order: index + 1
            });
        });
        
        $.ajax({
            url: admin_url + 'topics/save_button_order',
            type: 'POST',
            data: { orders: orders },
            success: function(response) {
                response = JSON.parse(response);
                if (response.success) {
                    alert_float('success', response.message);
                    $('#sort-modal').modal('hide');
                    // Refresh table
                    actionButtonsTable.ajax.reload();
                } else {
                    alert_float('danger', response.message);
                }
            },
            error: function() {
                alert_float('danger', 'Error saving order');
            }
        });
    });

    // Sửa lại phần xử lý khi modal được mở
    $(document).on('shown.bs.modal', '#action_button_modal', function() {
        // Refresh selectpicker trước
        $('.selectpicker').selectpicker('refresh');
        
        // Lấy giá trị hiện tại của action type và state
        var currentType = $('select[name="target_action_type"]').val();
        var currentState = $('select[name="target_action_state"]').val();
        
        console.log('Modal opened - Current Type:', currentType, 'Current State:', currentState);
        
        // Nếu có action type, load states tương ứng
        if (currentType) {
            $.ajax({
                url: admin_url + 'topics/get_action_states',
                type: 'POST',
                data: {
                    action_type_code: currentType
                },
                dataType: 'json',
                success: function(response) {
                    var state_select = $('select[name="target_action_state"]');
                    state_select.empty();
                    state_select.append('<option value="">' + 
                        app.lang.dropdown_non_selected_tex + '</option>');
                    
                    if (Array.isArray(response.data)) {
                        $.each(response.data, function(i, state) {
                            var selected = (state.action_state_code == currentState) ? 'selected' : '';
                            state_select.append('<option value="' + 
                                state.action_state_code + '" ' + selected + '>' + 
                                state.name + ' - ' + state.action_state_code + '</option>');
                        });
                    }
                    
                    state_select.selectpicker('refresh');
                    
                    // Nếu có state đã chọn trước đó, set lại giá trị
                    if (currentState) {
                        state_select.val(currentState);
                        state_select.selectpicker('refresh');
                    }
                }
            });
        }
    });

    // Sửa lại phần xử lý khi action type thay đổi
    $(document).on('change', 'select[name="target_action_type"]', function() {
        var action_type_code = $(this).val();
        var state_select = $('select[name="target_action_state"]');
        // var current_state = state_select.val();
        
        // console.log('Action Type Changed:', action_type_code, 'Current State:', current_state);
        
        if (action_type_code) {
            $.ajax({
                url: admin_url + 'topics/get_action_states',
                type: 'POST',
                data: {
                    action_type_code: action_type_code
                },
                dataType: 'json',
                success: function(response) {
                    console.log('Response:',state_select,  response);
                    state_select.empty();
                    state_select.append('<option value="">' + 
                        app.lang.dropdown_non_selected_tex + '</option>');
                    
                    if (Array.isArray(response.data)) {
                        $.each(response.data, function(i, state) {
                            state_select.append('<option value="' + 
                                state.action_state_code + '">' + 
                                state.name + ' - ' + state.action_state_code + '</option>');
                        });
                    }
                    
                    state_select.selectpicker('refresh');
                    console.log('State Select:', state_select.val());
                }
            });
        } else {
            state_select.empty();
            state_select.append('<option value="">' + 
                app.lang.dropdown_non_selected_tex + '</option>');
            state_select.selectpicker('refresh');
        }
    });

    // Handle form submission
    $(document).on('submit', '#action-button-form', function(e) {
        e.preventDefault();
        
        var form = $(this);
        var data = form.serialize();
        var submitBtn = form.find('[type="submit"]');
        var originalBtnText = submitBtn.html();
        
        submitBtn.prop('disabled', true).html('Saving...');
        
        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: data,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert_float('success', response.message || 'Action button saved successfully');
                    $('#action_button_modal').modal('hide');
                    // Refresh datatable
                    $('.table-action-buttons').DataTable().ajax.reload(null, false);
                } else {
                    alert_float('danger', response.message || 'Error saving action button');
                }
            },
            error: function(xhr, status, error) {
                alert_float('danger', 'Error saving action button');
                console.error(error);
            },
            complete: function() {
                submitBtn.prop('disabled', false).html(originalBtnText);
            }
        });
    });

    // Handle status toggle
    $(document).on('change', 'input[data-switch-url]', function() {
        var switch_url = $(this).data('switch-url');
        var id = $(this).data('id');
        var status = $(this).prop('checked') ? 1 : 0;
        
        $.get(switch_url + '/' + id + '/' + status, function(response) {
            var data = {};
            if (typeof response === 'string') {
                try {
                    data = JSON.parse(response);
                } catch (e) {
                    data = { success: false, message: 'Invalid response' };
                }
            } else {
                data = response;
            }
            
            if (data.success) {
                alert_float('success', data.message);
                // Refresh table to show updated status
                $('.table-action-buttons').DataTable().ajax.reload(null, false);
            } else {
                alert_float('danger', data.message);
                // Revert switch if update failed
                $(this).prop('checked', !status);
            }
        }).fail(function(xhr, textStatus, errorThrown) {
            alert_float('danger', 'Error updating status');
            console.error(errorThrown);
            // Revert switch if request failed
            $(this).prop('checked', !status);
        });
    });
});

   // Hàm edit action button
   function edit_action_button(id) {
        // Destroy existing modal if it exists
        if ($('#action_button_modal').data('bs.modal')) {
            $('#action_button_modal').modal('dispose');
        }
        
        $.ajax({
            url: admin_url + 'topics/action_button/' + id,
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Inject HTML vào modal
                    $('#action_button_modal').remove(); // Remove existing modal
                    $('body').append(response.data); // Add new modal HTML
                    
                    // Initialize new modal
                    $('#action_button_modal').modal({
                        backdrop: 'static',
                        keyboard: false
                    });
                    
                    // Show modal
                    $('#action_button_modal').modal('show');
                    
                    // Initialize select pickers after modal is shown
                    $('#action_button_modal').on('shown.bs.modal', function() {
                        $('.selectpicker').selectpicker({
                            style: 'btn-default',
                            size: 8
                        });
                    });
                }
            },
            error: function(xhr, status, error) {
                alert_float('danger', 'Error loading action button data');
                console.error(error);
            }
        });
    }

    function deleteActionButton(id) {
        if (confirm(_l('confirm_action'))) {
            $.post(admin_url + 'topics/delete_action_button/' + id)
                .done(function(response) {
                    response = JSON.parse(response);
                    if (response.success) {
                        alert_float('success', response.message);
                        $('#action_button_modal').modal('hide');
                        $('.table-action-buttons').DataTable().ajax.reload();
                    } else {
                        alert_float('danger', response.message);
                    }
                })
                .fail(function() {
                    alert_float('danger', _l('error_deleting'));
                });
        }
    }

    function new_action_button() {
        // Destroy existing modal if it exists
        if ($('#action_button_modal').data('bs.modal')) {
            $('#action_button_modal').modal('dispose');
        }
        
        $.ajax({
            url: admin_url + 'topics/action_button',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Remove existing modal
                    $('#action_button_modal').remove();
                    
                    // Append new empty modal
                    $('body').append(response.data);
                    
                    // Initialize new modal
                    $('#action_button_modal').modal({
                        backdrop: 'static',
                        keyboard: false
                    });
                    
                    // Show modal
                    $('#action_button_modal').modal('show');
                    
                    // Initialize select pickers after modal is shown
                    $('#action_button_modal').on('shown.bs.modal', function() {
                        $('.selectpicker').selectpicker({
                            style: 'btn-default',
                            size: 8
                        });
                    });
                }
            },
            error: function(xhr, status, error) {
                alert_float('danger', 'Error loading action button form');
                console.error(error);
            }
        });
    }
</script>

<style>
/* Add custom styles for the table */
.min-width-50 { min-width: 50px; }
.min-width-100 { min-width: 100px; }
.min-width-150 { min-width: 150px; }
.min-width-200 { min-width: 200px; }

.table-action-buttons td {
    vertical-align: middle !important;
}

/* Style for labels */
.label {
    display: inline-block;
    padding: 4px 8px;
    font-size: 12px;
    font-weight: 600;
    line-height: 1;
    text-align: center;
    white-space: nowrap;
    vertical-align: baseline;
    border-radius: 3px;
}

/* Ensure description text wraps properly */
.text-wrap {
    white-space: normal !important;
    word-wrap: break-word !important;
}

/* Add styles for sorting */
#sortable .handle {
    cursor: move;
    color: #666;
}

#sortable .list-group-item {
    display: flex;
    align-items: center;
    padding: 10px 15px;
    margin-bottom: 5px;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 3px;
}

#sortable .list-group-item:hover {
    background: #f9f9f9;
}

.ui-sortable-helper {
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
</style> 