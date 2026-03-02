<?php init_head(); ?>

<?php
$rel_type = '';
$rel_id = '';
if (isset($deals) || ($this->input->get('rel_id') && $this->input->get('rel_type'))) {
    $rel_id = isset($deals) ? $deals->rel_id : $this->input->get('rel_id');
    $rel_type = isset($deals) ? $deals->rel_type : $this->input->get('rel_type');
}
?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-8 col-md-offset-2">
                <h4 class="tw-mt-0 tw-font-semibold tw-text-lg tw-text-neutral-700">
                    <?php if (isset($deals)) {
                        echo _l('edit_deals');
                    } else {
                        echo _l('new_deals');
                    } ?>
                </h4>
                <div class="panel_s">
                    <div class="panel-body ">
                        <?php echo form_open(base_url('deals/save_deals/' . (!empty($deals) ? $deals->id : '')), array('id' => 'new_deals_form')); ?>
                        <div class="col-sm-6 tw-mb-4">
                            <?php echo render_input('title', 'title', isset($deals) ? $deals->title : ''); ?>
                        </div>

                        <div class="form-group">

                            <div class="col-sm-6 tw-mb-4">
                                <label for="field-1" class=" control-label"><?= _l('deal_value') ?> <span
                                            class="text-danger">*</span></label>

                                <input type="text" name="deal_value"
                                       value="<?= (!empty($deals->deal_value) ? $deals->deal_value : ''); ?>"
                                       class="form-control" required/>
                                <span class="text-muted"><small><?= _l('deals_value_example') ?></small></span>

                            </div>
                        </div>

                        <div class="form-group">

                            <div class="col-sm-6 tw-mb-4">
                                <?php
                                $selected = '';
                                if (isset($deals)) {
                                    $selected = $deals->source_id;
                                } else {
                                    $selected = get_option('default_source');
                                }
                                $select_attrs = ['data-width' => '100%'];
                                if (is_admin() || get_option('staff_members_create_inline_deal_source') == '1') {
                                    echo render_select_with_input_group('source_id', $sources, ['source_id', 'source_name'], _l('source'), $selected, '<div class="input-group-btn"><a href="#" class="btn btn-default" onclick="new_deal_source_inline();return false;" class="inline-field-new"><i class="fa fa-plus"></i></a></div>', $select_attrs);
                                } else {
                                    echo render_select('source_id', $sources, ['source_id', 'source_name'], _l('source'), $selected, $select_attrs);
                                }
                                ?>
                            </div>
                        </div>
                        <div class="form-group">

                            <div class="col-sm-6 tw-mb-4">
                                <?php
                                // next week date from today
                                $next_week = date('Y-m-d', strtotime('+1 week'));
                                $value = (isset($deals) ? _d($deals->days_to_close) : _d($next_week)); ?>
                                <?php echo render_date_input(
                                    'days_to_close',
                                    'expected_close_date',
                                    $value,
                                    isset($contract) && $contract->signed == 1 ? ['disabled' => true] : []
                                ); ?>
                            </div>
                        </div>


                        <div class="col-sm-6 tw-mb-4">
                            <?php
                            $selected = '';
                            if (isset($deals)) {
                                $selected = $deals->pipeline_id;
                            } else {
                                $selected = get_option('default_pipeline');
                            }
                            $attributes = array('onchange' => 'get_related_stages(this.value)', 'required' => true);
                            echo render_select('pipeline_id', $pipelines, ['pipeline_id', 'pipeline_name'], _l('pipeline'), $selected, $attributes);
                            ?>
                        </div>
                        <div class="col-sm-6 tw-mb-4">
                            <div id="pipelineStages">

                            </div>
                        </div>
                        <div class="form-group">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="rel_type"
                                           class="control-label"><?php echo _l('task_related_to'); ?></label>
                                    <select name="rel_type" class="selectpicker" id="rel_type" data-width="100%"
                                            data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>">
                                        <option value=""></option>
                                        <option value="customer" <?php if (isset($deals) || $this->input->get('rel_type')) {
                                            if ($rel_type == 'customer') {
                                                echo 'selected';
                                            }
                                        } ?>>
                                            <?php echo _l('client'); ?>
                                        </option>

                                        <option value="contract" <?php if (isset($deals) || $this->input->get('rel_type')) {
                                            if ($rel_type == 'contract') {
                                                echo 'selected';
                                            }
                                        } ?>>
                                            <?php echo _l('contract'); ?>
                                        </option>

                                        <option value="lead" <?php if (isset($deals) || $this->input->get('rel_type')) {
                                            if ($rel_type == 'lead') {
                                                echo 'selected';
                                            }
                                        } ?>>
                                            <?php echo _l('lead'); ?>
                                        </option>

                                        <option value="proposal" <?php if (isset($deals) || $this->input->get('rel_type')) {
                                            if ($rel_type == 'proposal') {
                                                echo 'selected';
                                            }
                                        } ?>>
                                            <?php echo _l('proposal'); ?>
                                        </option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group<?php if ($rel_id == '') {
                                    echo ' hide';
                                } ?>" id="rel_id_wrapper">
                                    <label for="rel_id" class="control-label"><span class="rel_id_label"></span></label>
                                    <div id="rel_id_select">
                                        <select name="rel_id" id="rel_id" class="ajax-sesarch" data-width="100%"
                                                data-live-search="true"
                                                data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>">
                                            <?php if ($rel_id != '' && $rel_type != '') {
                                                $rel_data = get_relation_data($rel_type, $rel_id);
                                                $rel_val = get_relation_values($rel_data, $rel_type);
                                                echo '<option value="' . $rel_val['id'] . '" selected>' . $rel_val['name'] . '</option>';
                                            } ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="col-sm-6 tw-mb-4">
                                <?php
                                $selected = '';
                                if (isset($deals)) {
                                    $selected = ($deals->default_deal_owner);
                                }
                                echo render_select('default_deal_owner', $staff, ['staffid', ['firstname', 'lastname']], 'deal_owner', $selected);
                                ?>

                            </div>

                        </div>

                        <div class="form-group">
                            <div class="col-sm-6 tw-mb-4">
                                <?php
                                $selected = '';
                                if (isset($deals)) {
                                    $selected = json_decode($deals->user_id);
                                }
                                echo render_select('user_id[]', $staff, ['staffid', ['firstname', 'lastname']], 'assigne', $selected, ['multiple' => true]);
                                ?>

                            </div>
                        </div>

                        <div class="form-group">

                            <div class="col-sm-6 tw-mb-4">
                                <label for="tags" class="control-label"><i class="fa fa-tag" aria-hidden="true"></i>
                                    <?php echo _l('tags'); ?></label>
                                <input type="text" class="tagsinput" id="tags" name="tags"
                                       value="<?php echo(isset($deals) ? prep_tags_input(get_tags_in($deals->id, 'deal')) : ''); ?>"
                                       data-role="tagsinput">
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="col-md-12">
                                <?php $rel_id = (isset($deals) ? $deals->id : false); ?>
                                <?php echo render_custom_fields('deals', $rel_id); ?>
                            </div>
                        </div>
                    </div>
                    <div class="btn-bottom-toolbar text-right">
                        <button type="submit"
                                class="btn btn-primary"
                        >
                            <?php echo _l('save_changes'); ?>
                        </button>
                    </div>
                    <?php echo form_close(); ?>
                </div>
            </div>
        </div>
    </div>
    <?php init_tail(); ?>

    <script>
        'use strict';
        $(document).ready(function () {
            // new_deals_form
            appValidateForm($('#new_deals_form'), {
                title: 'required',
                deal_value: 'required',
                days_to_close: 'required',
                pipeline_id: 'required',
                stage_id: 'required',
                // client_id: 'required',
                default_deal_owner: 'required',
            });
            let pipeline_id = $('select[name="pipeline_id"]').val();
            let stage_id = <?= (!empty($deals) ? $deals->stage_id : 'null'); ?>;
            get_related_stages(pipeline_id, stage_id)
        });


        function new_deal_source_inline() {
            _gen_deal_add_inline_on_select_field("source_id");
        }

        function _gen_deal_add_inline_on_select_field(type) {
            var html = "";
            if (
                $("body").hasClass("deals-email-integration") ||
                $("body").hasClass("web-to-deal-form")
            ) {
                type = "deal_" + type;
            }
            html =
                '<div id="new_deal_' +
                type +
                '_inline" class="form-group"><label for="new_' +
                type +
                '_name">' +
                $('label[for="' + type + '"]')
                    .html()
                    .trim() +
                '</label><div class="input-group"><input type="text" id="new_' +
                type +
                '_name" name="new_' +
                type +
                '_name" class="form-control"><div class="input-group-addon"><a href="#" onclick="deal_add_inline_select_submit(\'' +
                type +
                '\'); return false;" class="deal-add-inline-submit-' +
                type +
                '"><i class="fa fa-check"></i></a></div></div></div>';
            $(".form-group-select-input-" + type).after(html);
            $("body")
                .find("#new_" + type + "_name")
                .focus();
            $(
                '.deal-save-btn,#form_info button[type="submit"],#deals-email-integration button[type="submit"],.btn-import-submit'
            ).prop("disabled", true);
            $(".inline-field-new").addClass("disabled").css("opacity", 0.5);
            $(".form-group-select-input-" + type).addClass("hide");
        }


        function deal_add_inline_select_submit(type) {
            var val = $("#new_" + type + "_name")
                .val()
                .trim();
            if (val !== "") {
                var requestURI = type;
                if (type.indexOf("deal_") > -1) {
                    requestURI = requestURI.replace("deal_", "");
                }

                var data = {};
                data.name = val;
                data.inline = true;
                $.post(admin_url + "deals/" + requestURI, data).done(function (response) {
                    response = JSON.parse(response);
                    if (response.success === true || response.success == "true") {
                        var select = $("body").find("select#" + type);
                        select.append(
                            '<option value="' + response.id + '">' + val + "</option>"
                        );
                        select.selectpicker("val", response.id);
                        select.selectpicker("refresh");
                        select.parents(".form-group").removeClass("has-error");
                    }
                });
            }

            $("#new_deal_" + type + "_inline").remove();
            $(".form-group-select-input-" + type).removeClass("hide");
            $(
                '.deal-save-btn,#form_info button[type="submit"],#deals-email-integration button[type="submit"],.btn-import-submit'
            ).prop("disabled", false);
            $(".inline-field-new").removeClass("disabled").removeAttr("style");
        }


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

        var _rel_id = $('#rel_id'),
            _rel_type = $('#rel_type'),
            _rel_id_wrapper = $('#rel_id_wrapper'),
            _current_member = undefined,
            data = {};

        $(function () {

            $("body").off("change", "#rel_id");

            var inner_popover_template =
                '<div class="popover"><div class="arrow"></div><div class="popover-inner"><h3 class="popover-title"></h3><div class="popover-content"></div></div></div>';

            $('#_task_modal .task-menu-options .trigger').popover({
                html: true,
                placement: "bottom",
                trigger: 'click',
                title: "<?php echo _l('actions'); ?>",
                content: function () {
                    return $('body').find('#_task_modal .task-menu-options .content-menu').html();
                },
                template: inner_popover_template
            });

            custom_fields_hyperlink();


            $('.rel_id_label').html(_rel_type.find('option:selected').text());

            _rel_type.on('change', function () {

                var clonedSelect = _rel_id.html('').clone();
                _rel_id.selectpicker('destroy').remove();
                _rel_id = clonedSelect;
                $('#rel_id_select').append(clonedSelect);
                $('.rel_id_label').html(_rel_type.find('option:selected').text());

                task_rel_select();
                if ($(this).val() != '') {
                    _rel_id_wrapper.removeClass('hide');
                } else {
                    _rel_id_wrapper.addClass('hide');
                }
                init_project_details(_rel_type.val());
            });

            init_datepicker();
            init_color_pickers();
            init_selectpicker();
            task_rel_select();

            $('body').on('change', '#rel_id', function () {
                if ($(this).val() != '') {
                    reset_task_duedate_input();
                }
            });

            <?php if (!isset($deals) && $rel_id != '') { ?>
            _rel_id.change();
            <?php } ?>


        });

        function task_rel_select() {
            var serverData = {};
            serverData.rel_id = _rel_id.val();
            data.type = _rel_type.val();
            init_ajax_search(_rel_type.val(), _rel_id, serverData);
        }

    </script>