<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="tw-mb-2 sm:tw-mb-4">
                    <a href="<?php echo admin_url('topics/action_states/create'); ?>" class="btn btn-primary">
                        <i class="fa fa-plus tw-mr-1"></i>
                        <?php echo _l('add_new_action_state'); ?>
                    </a>
                </div>

                <div class="panel_s">
                    <div class="panel-body panel-table-full">
                        <?php
                        $table_data = [
                            _l('action_state_id'),
                            _l('action_state_name'),
                            _l('action_state_code'),
                            _l('action_type'),
                            _l('valid_data'),
                            _l('created_date'),
                            _l('options')
                        ];
                        render_datatable($table_data, 'action_states', [], [
                            'order' => [[3, 'asc'], [0, 'asc']]
                        ]);
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>

<script>
$(function() {
    initDataTable('.table-action_states', 
        window.location.href + '/table', 
        undefined, 
        undefined, 
        undefined, 
        [0, 'asc']  // Sort by first column (ID) ascending
    );

    // $('body').on('change', 'input[data-switch-url]', function() {
    //     var switch_url = $(this).data('switch-url');
    //     var checkbox = $(this);
        
    //     $.post(switch_url, {}, function(response) {
    //         response = JSON.parse(response);
    //         if (response.success) {
    //             alert_float('success', response.message);
    //         } else {
    //             checkbox.prop('checked', !checkbox.prop('checked'));
    //             alert_float('danger', response.message);
    //         }
    //     });
    // });
});

$(function() {
    // Remove any existing event handlers
    $('body').off('change', '.js-switch');
    
    // Add new event handler
    $('body').on('change', '.js-switch', function(e) {
        var switchElement = $(this);
        var url = switchElement.data('switch-url');
        
        // Prevent multiple clicks
        if (switchElement.data('processing')) {
            e.preventDefault();
            return false;
        }
        
        switchElement.data('processing', true);
        
        $.get(url).done(function(response) {
            if (response.success) {
                alert_float('success', response.message);
            } else {
                alert_float('warning', response.message);
                // Revert switch if failed
                switchElement.prop('checked', !switchElement.prop('checked'));
            }
        }).fail(function(data) {
            alert_float('danger', data.responseText);
            // Revert switch if failed
            switchElement.prop('checked', !switchElement.prop('checked'));
        }).always(function() {
            switchElement.data('processing', false);
        });
    });
});
</script> 