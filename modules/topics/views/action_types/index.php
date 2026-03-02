<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="tw-mb-2 sm:tw-mb-4">
                    <?php if (has_permission('topics', '', 'create')) { ?>
                    <a href="<?php echo admin_url('topics/action_types/create'); ?>" class="btn btn-primary">
                        <i class="fa fa-plus tw-mr-1"></i>
                        <?php echo _l('add_new_action_type'); ?>
                    </a>
                    <?php } ?>
                    
                    <button id="toggle-reposition" class="btn btn-info">
                        <i class="fa fa-sort-numeric-asc tw-mr-1"></i>
                        <?php echo _l('reposition'); ?>
                    </button>

                    <button id="toggle-expand-all" class="btn btn-info">
                        <i class="fa fa-plus-square tw-mr-1"></i>
                        <span>Expand All</span>
                    </button>

                    <button id="save-positions" class="btn btn-success" style="display:none;">
                        <i class="fa fa-save tw-mr-1"></i>
                        <?php echo _l('save_positions'); ?>
                    </button>
                </div>

                <div class="panel_s">
                    <div class="panel-body panel-table-full">
                        <?php
                        $table_data = [
                            ['name' => '<i class="fa fa-list-ol"></i>', 'th_attrs' => ['class' => 'toggleable']], 
                            _l('action_type_id'),
                            _l('action_type_name'),
                            _l('action_type_code'),
                            _l('created_date'),
                            _l('options')
                        ];
                        render_datatable($table_data, 'action_types', [], [
                            'order' => [[0, 'asc']]
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
    var isExpanded = false;

    var table = initDataTable('.table-action_types', 
        window.location.href + '/table', 
        undefined, 
        undefined, 
        undefined,
        [ [0, 'asc'], [1, 'asc']],
        {
            drawCallback: function() {
                updateExpandCollapseState();
            }
        }
    );

    $('.dataTables_wrapper .btn-dt-reload').on('click', function() {
        isExpanded = false;
        updateExpandCollapseState();
    });

    // Override DataTable's built-in refresh function
    var originalRefresh = $.fn.dataTable.ext.oApi.fnRefresh;
    $.fn.dataTable.ext.oApi.fnRefresh = function(oSettings) {
        // Reset expand state before reloading
        isExpanded = false;
        
        // Call original refresh with our custom callback
        this.api().ajax.reload(function() {
            updateExpandCollapseState();
        }, true);
    };

    function updateExpandCollapseState() {
        console.log('updateExpandCollapseState', isExpanded);
        var $button = $('#toggle-expand-all');
        var $icon = $button.find('i');
        var $text = $button.find('span');
                
        if (isExpanded) {
            $('.child-row').removeClass('hidden').fadeIn(100);
            $('.toggle-states i').removeClass('fa-plus-square').addClass('fa-minus-square');
            $icon.removeClass('fa-plus-square').addClass('fa-minus-square');
            $text.text('Collapse All');
        } else {
            $('.child-row').addClass('hidden');
            $('.toggle-states i').removeClass('fa-minus-square').addClass('fa-plus-square');
            $icon.removeClass('fa-minus-square').addClass('fa-plus-square');
            $text.text('Expand All');
        }
    }

    // Xóa event handler cũ của nút refresh
    // $('.dataTables_wrapper .dataTables_refresh').on('click', function() {...});

    // Handle expand/collapse for nested states
    $('body').on('click', '.toggle-states', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        var $icon = $(this).find('i');
        var typeId = $(this).data('type-id');
        var $childRows = $('.child-of-' + typeId);
        
        if ($childRows.hasClass('hidden')) {
            $childRows.removeClass('hidden').fadeIn(100);
            $icon.removeClass('fa-plus-square').addClass('fa-minus-square');
        } else {
            $childRows.addClass('hidden').fadeOut(100);
            $icon.removeClass('fa-minus-square').addClass('fa-plus-square');
        }
    });

    var isRepositioning = false;

    $('#toggle-reposition').on('click', function() {
        isRepositioning = !isRepositioning;
        if(isRepositioning) {
            $(this).hide();
            $('#save-positions').show();
            $('.position-value').each(function() {
                var value = $(this).text().trim();
                $(this).html('<input type="number" class="position-input form-control input-sm" value="' + value + '" min="1">');
            });
        }
    });

    $('#save-positions').on('click', function() {
        var data = {};
        $('.table-action_types tbody tr.action-type-row').each(function() {
          
            var position = $(this).find('.position-input').val();
            var id = $(this).find('.position-value').data('type-id');
            console.log(id, position);
            if(id && position) {
                data['positions[' + id + ']'] = position;
            }
        });

        $.post(admin_url + 'topics/action_types/reorder', data).done(function(response) {
            response = JSON.parse(response);
            if(response.success) {
                alert_float('success', response.message);
                // Reload table để cập nhật dữ liệu
                $('.table-action_types').DataTable().ajax.reload();
                // Reset UI
                $('#toggle-reposition').show();
                $('#save-positions').hide();
                isRepositioning = false;
            } else {
                alert_float('warning', response.message);
            }
        });
    });

    $('#toggle-expand-all').on('click', function() {
        isExpanded = !isExpanded;
        updateExpandCollapseState();
    });
});
</script>

<style>
.position-input {
    width: 60px !important;
    display: inline-block !important;
    height: 28px !important;
    padding: 3px 6px !important;
}

.position-value {
    min-width: 30px;
    display: inline-block;
}
</style> 