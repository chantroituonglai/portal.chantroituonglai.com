<?php init_head(); ?>

<style>
    .sorting-drag-drop tr.ui-placeholder {
        padding: 24px;
        background-color: #ffffcc;
        border: 1px dotted #ccc;
        cursor: move;
        margin-top: 12px;
    }
</style>

<?php
$created = has_permission('deals', '', 'create');
$edited = has_permission('deals', '', 'edit');
$deleted = has_permission('deals', '', 'delete');

if (!empty($created) || !empty($edited)) {
    ?>
    <div id="wrapper">
        <div class="content">
            <div class="row">
                <div class="col-md-12">
                    <div class="_buttons tw-mb-2 sm:tw-mb-4">
                        <a href="<?= base_url('admin/deals/deals_setting') ?>"
                           class="btn btn-primary mright5 pull-left display-block">
                            <?= _l('deals_settings') ?>
                        </a>
                        <a href="<?= base_url() ?>admin/deals/new_stages"
                           class="btn btn-primary mright5 pull-left display-block hidden-xs">
                            <?= ' ' . _l('new_stages') ?>
                        </a>
                        <a href="<?= base_url() ?>admin/deals/sales_pipelines"
                           class="btn btn-primary pull-left display-block hidden-xs">
                            <?= ' ' . _l('sales_pipelines') ?>
                        </a>
                        <div class="clearfix"></div>
                    </div>
                    <div class="tab-content bg-white">
                        <div class="tab-pane active" id="group">
                            <div class="panel-body tw-pt-0">

                                <div class="table-responsive">
                                    <table class="table table-striped ">
                                        <thead>
                                        <tr>
                                            <th><?= _l('pipeline') ?></th>
                                            <?php if (!empty($edited) || !empty($deleted)) { ?>
                                                <th><?= _l('action') ?></th>
                                            <?php } ?>
                                        </tr>
                                        </thead>
                                        <tbody class="sorting-drag-drop sortable">
                                        <?php
                                        $all_pipelines = $this->db->order_by('order', 'ASC')->get('tbl_deals_pipelines')->result();
                                        if (!empty($all_pipelines)) {
                                            foreach ($all_pipelines as $pipelines) {

                                                ?>
                                                <tr id="<?= $pipelines->pipeline_id ?>">

                                                    <td><?php
                                                        $id = $this->uri->segment(4);

                                                        if (!empty($id) && $id == $pipelines->pipeline_id) { ?>
                                                            <?php

                                                            echo form_open(base_url('deals/saved_pipelines/' . $id), array('id' => 'new_deals_form'));
                                                            ?>

                                                            <input type="text" name="pipeline_name" value="<?php
                                                            if (!empty($pipelines)) {
                                                                echo $pipelines->pipeline_name;
                                                            }
                                                            ?>" class="form-control"
                                                                   placeholder="<?= _l('new_pipeline') ?>" required>

                                                        <?php } else {
                                                            echo $pipelines->pipeline_name;
                                                        }
                                                        ?>
                                                    </td>

                                                    <td>
                                                        <?php
                                                        $id = $this->uri->segment(4);
                                                        if (!empty($id) && $id == $pipelines->pipeline_id) { ?>
                                                            <?= btn_update_deals() ?>
                                                            <?php echo form_close(); ?>

                                                            <?= btn_cancel_deals('admin/deals/sales_pipelines') ?>
                                                        <?php } else { ?>
                                                        <?php if (!empty($edited)) { ?>
                                                            <?= btn_edit_deals('admin/deals/sales_pipelines/' . $pipelines->pipeline_id) ?>
                                                            <?php if (!empty($deleted)) { ?>
                                                                <a href="<?= base_url('admin/deals/delete_pipeline/' . $pipelines->pipeline_id) ?>"
                                                                   class="btn btn-xs btn-danger">
                                                                    <?= _l('delete') ?></a>
                                                            <?php }
                                                        } ?>
                                                    </td>

                                                    <?php } ?>
                                                </tr>
                                            <?php }
                                        } ?>

                                        <?php echo form_open(base_url('deals/saved_pipelines/'), array('id' => 'new_deals_form')); ?>

                                        <tr>
                                            <td><input required type="text" name="pipeline_name" class="form-control"
                                                       placeholder="<?= _l('pipeline_name') ?>">
                                            </td>

                                            <td><?= btn_add_deals() ?></td>
                                        </tr>
                                        <?php echo form_close(); ?>

                                        </tbody>
                                    </table>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php } ?>
<?php init_tail(); ?>
<?php echo csrf_jquery_token() ?>
<script>
    'use strict';
    $(document).ready(function () {
        $('.sorting-drag-drop').sortable({
            stop: function () {
                var page_id_array = '';
                $('.sortable tr').each(function () {
                    var id = $(this).attr('id');
                    if (page_id_array == '') {
                        page_id_array = id;
                    } else {
                        page_id_array = page_id_array + ',' + id;

                    }
                });
                $.ajax({
                    url: "save_sorting_pipelines",
                    method: 'POST',
                    data: 'page_id_array=' + page_id_array,
                    success: function (data) {

                    }
                })
            }
        });
    })
</script>