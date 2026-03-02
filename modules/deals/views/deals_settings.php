<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-8 col-md-offset-2">
                <div class="row">
                    <!-- Start Form test -->
                    <div class="col-lg-12 col-offset-4">
                        <?php echo form_open(base_url('admin/deals/save_settings'), array('id' => 'new_deals_form')); ?>
                        <div class="panel panel-custom">
                            <div class="panel-body">
                                <div class="col-sm-6 tw-mb-4">
                                    <?php echo render_input('deals_kanban_limit', 'settings_deals_kanban_limit', get_option('deals_kanban_limit'), 'number'); ?>
                                </div>

                                <div class="col-sm-6 tw-mb-4">
                                    <?php
                                    $selected = get_option('default_source');
                                    echo render_select('default_deal_source', $sources, ['source_id', 'source_name'], _l('default_source'), $selected);
                                    ?>
                                </div>
                                <div class="col-sm-6 tw-mb-4">
                                    <?php
                                    $selected = get_option('default_pipeline');
                                    $attributes = array('onchange' => 'get_related_stages(this.value)');
                                    echo render_select('default_pipeline', $pipelines, ['pipeline_id', 'pipeline_name'], _l('default_pipeline'), $selected, $attributes);
                                    ?>
                                </div>
                                <div class="col-sm-6 tw-mb-4">
                                    <?php
                                    $selected = get_option('default_deal_owner');
                                    foreach ($staff as $member) {
                                        if (isset($invoice)) {
                                            if ($invoice->sale_agent == $member['staffid']) {
                                                $selected = $member['staffid'];
                                            }
                                        }
                                    }
                                    echo render_select('default_deal_owner', $staff, ['staffid', ['firstname', 'lastname']], 'default_deal_owner', $selected);
                                    ?>
                                </div>

                                <div class="col-sm-6 tw-mb-4">
                                    <div class="form-group" id="pipelineStages">

                                    </div>
                                </div>
                                <div class="">
                                    <div class="col-md-7">
                                        <label for="default_leads_kanban_sort"
                                               class="control-label"><?php echo _l('default_deals_kanban_sort'); ?></label>
                                        <select name="default_deals_kanban_sort_type"
                                                id="default_deals_kanban_sort_type" class="selectpicker"
                                                data-width="100%"
                                                data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>">
                                            <option value="created_at" <?php if (get_option('default_deals_kanban_sort_type') == 'dateadded') {
                                                echo 'selected';
                                            } ?>><?php echo _l('leads_sort_by_datecreated'); ?></option>
                                            <option value="dealorder" <?php if (get_option('default_deals_kanban_sort_type') == 'dealorder') {
                                                echo 'selected';
                                            } ?>><?php echo _l('leads_sort_by_kanban_order'); ?></option>
                                            <option value="deal_value" <?php if (get_option('default_deals_kanban_sort_type') == 'deal_value') {
                                                echo 'selected';
                                            } ?>><?php echo _l('leads_sort_by_deal_value'); ?></option>
                                        </select>
                                    </div>
                                    <div class="col-md-5">
                                        <div class="mtop30 text-right">
                                            <div class="radio radio-inline radio-primary">
                                                <input type="radio" id="k_desc" name="default_deals_kanban_sort_by"
                                                       value="asc" <?php if (get_option('default_deals_kanban_sort_by') == 'asc') {
                                                    echo 'checked';
                                                } ?>>
                                                <label for="k_desc"><?php echo _l('order_ascending'); ?></label>
                                            </div>
                                            <div class="radio radio-inline radio-primary">
                                                <input type="radio" id="k_asc" name="default_deals_kanban_sort_by"
                                                       value="desc" <?php if (get_option('default_deals_kanban_sort_by') == 'desc') {
                                                    echo 'checked';
                                                } ?>>
                                                <label for="k_asc"><?php echo _l('order_descending'); ?></label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="clearfix"></div>
                                </div>
                            </div>
                            <div class="panel-footer text-right">
                                <button type="submit"
                                        class="btn btn-primary"
                                >
                                    <?php echo _l('save_changes'); ?>
                                </button>
                            </div>
                        </div>
                        <?php echo form_close(); ?>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>

<script>
    'use strict';
    $(document).ready(function () {
        let pipeline_id = $('select[name="default_pipeline"]').val();
        let stage_id = null;
        get_related_stages(pipeline_id, stage_id)
    });

    function get_related_stages(id, stage_id = null) {
        $.ajax({
            async: false,
            url: "<?= admin_url() ?>" + "deals/getStateByID/" + id + '/' + stage_id,
            type: 'get',
            dataType: "json",
            success: function (data) {
                $('#pipelineStages').html(data);
                init_selectpicker();

                if (id == 0) {
                    $('.pipelineStages').hide(data);
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                console.log(textStatus, errorThrown);
            }
        });
    }

    function init_selectpicker() {

        $('body').find('select.selectpicker').not('.ajax-search').selectpicker({
            showSubtext: true,
        });

    }
</script>
