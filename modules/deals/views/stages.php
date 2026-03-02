<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="tw-mb-2 sm:tw-mb-4">
                    <a href="#" onclick="new_stage(); return false;" class="btn btn-primary">
                        <i class="fa-regular fa-plus tw-mr-1"></i>
                        <?php echo _l('new_stage'); ?>
                    </a>
                </div>
                <div class="panel_s">
                    <div class="panel-body panel-table-full">
                        <?php if (count($stages) > 0) { ?>
                            <table class="table dt-table" data-order-col="3" data-order-type="asc">
                                <thead>
                                <th><?php echo _l('id'); ?></th>
                                <th><?php echo _l('deals_stages_table_name'); ?></th>
                                <th><?php echo _l('deals_pipelines_table_name'); ?></th>
                                <th><?php echo _l('leads_status_add_edit_order'); ?></th>
                                <th><?php echo _l('options'); ?></th>
                                </thead>
                                <tbody>
                                <?php foreach ($stages as $stage) { ?>
                                    <tr>
                                        <td><?php echo $stage['stage_id']; ?></td>
                                        <td><a href="#"
                                               onclick="edit_stage(this,<?php echo $stage['stage_id']; ?>); return false"
                                               data-name="<?php echo $stage['stage_name']; ?>"><?php echo $stage['stage_name']; ?></a><br/>
                                            <span class="text-muted">
                                            <?php echo _l('deals_table_total', total_rows(db_prefix() . '_deals', ['stage_id' => $stage['stage_id']])); ?>
                                        </span>
                                        </td>
                                        <td><?php echo $stage['pipeline_name']; ?></td>
                                        <td><?php echo $stage['stage_order']; ?></td>
                                        <td>
                                            <div class="tw-flex tw-items-center tw-space-x-3">
                                                <a href="#"
                                                   onclick="edit_stage(this,<?php echo $stage['stage_id']; ?>); return false"
                                                   data-pipeline="<?php echo $stage['pipeline_id']; ?>"
                                                   data-order="<?php echo $stage['stage_order']; ?>"
                                                   data-name="<?php echo $stage['stage_name']; ?>"
                                                   class="tw-text-neutral-500 hover:tw-text-neutral-700 focus:tw-text-neutral-700">
                                                    <i class="fa-regular fa-pen-to-square fa-lg"></i>
                                                </a>
                                                <a href="<?php echo admin_url('deals/delete_stages/' . $stage['stage_id']); ?>"
                                                   class="tw-mt-px tw-text-neutral-500 hover:tw-text-neutral-700 focus:tw-text-neutral-700 _delete">
                                                    <i class="fa-regular fa-trash-can fa-lg"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php } ?>
                                </tbody>
                            </table>
                        <?php } else { ?>
                            <p class="no-margin"><?php echo _l('deals_stages_not_found'); ?></p>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="stage" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <?php echo form_open(admin_url('deals/stage')); ?>
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                            aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">
                    <span class="edit-title"><?php echo _l('edit_stage'); ?></span>
                    <span class="add-title"><?php echo _l('new_stage'); ?></span>
                </h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <div id="additional"></div>
                        <?php echo render_input('name', 'deals_stage_add_edit_name'); ?>
                        <?php echo render_select('pipeline_id', $pipelines, ['pipeline_id', 'pipeline_name'], _l('pipelines')); ?>
                        <?php echo render_input('stage_order', 'leads_status_add_edit_order', total_rows(db_prefix() . '_deals_stages') + 1, 'number'); ?>
                    </div>
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
                <button type="submit" class="btn btn-primary"><?php echo _l('submit'); ?></button>
            </div>
        </div>
        <!-- /.modal-content -->
        <?php echo form_close(); ?>
    </div>
    <!-- /.modal-dialog -->
</div>
<!-- /.modal -->
<?php init_tail(); ?>
<script>
    'use strict';
    $(function () {
        appValidateForm($('form'), {
            name: 'required'
        }, manage_deals_stages);
        $('#stage').on('hidden.bs.modal', function (event) {
            $('#additional').html('');
            $('#stage input[name="name"]').val('');
            $('#stage input[name="stage_order"]').val('');
            $('#stage select[name="pipeline_id"]').val('');
            // update selctpikcer value
            $('#stage select[name="pipeline_id"]').selectpicker('refresh');

            $('.add-title').removeClass('hide');
            $('.edit-title').removeClass('hide');
            $('#stage input[name="stage_order"]').val($('table tbody tr').length + 1);
        });
    });

    function manage_deals_stages(form) {
        var data = $(form).serialize();
        var url = form.action;
        $.post(url, data).done(function (response) {
            window.location.reload();
        });
        return false;
    }

    function new_stage() {
        $('#stage').modal('show');
        $('.edit-title').addClass('hide');
    }

    function edit_stage(invoker, id) {
        let name = $(invoker).data('name');
        let pipeline = $(invoker).data('pipeline');
        let order = $(invoker).data('order');
        $('#additional').append(hidden_input('id', id));
        $('#stage input[name="name"]').val(name);
        $('#stage select[name="pipeline_id"]').val(pipeline);
        // update selctpikcer value
        $('#stage select[name="pipeline_id"]').selectpicker('refresh');


        $('#stage input[name="stage_order"]').val(order);
        $('#stage').modal('show');
        $('.add-title').addClass('hide');
    }
</script>
</body>

</html>