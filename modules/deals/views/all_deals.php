<?php init_head();

?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="_buttons tw-mb-2 sm:tw-mb-4">
                    <a href="<?php echo admin_url('deals/new_deal'); ?>"
                       class="btn btn-primary mright5 pull-left display-block">
                        <i class="fa-regular fa-plus tw-mr-1"></i>
                        <?php echo _l('new_deal'); ?>
                    </a>
                    <div class="row">
                        <div class="col-sm-5 ">
                            <a href="#" class="btn btn-default btn-with-tooltip" data-toggle="tooltip"
                               data-title="<?php echo _l('deals_summary'); ?>" data-placement="top"
                               onclick="slideToggle('.deals-overview'); return false;"><i
                                        class="fa fa-bar-chart"></i></a>
                            <a href="<?php echo admin_url('deals/switch_kanban/' . $switch_kanban); ?>"
                               class="btn btn-default mleft5 hidden-xs" data-toggle="tooltip" data-placement="top"
                               data-title="<?php echo $switch_kanban == 1 ? _l('deals_switch_to_kanban') : _l('switch_to_list_view'); ?>">
                                <?php if ($switch_kanban == 1) { ?>
                                    <i class="fa-solid fa-grip-vertical"></i>
                                <?php } else { ?>
                                    <i class="fa-solid fa-table-list"></i>
                                <?php }; ?>
                            </a>
                        </div>
                        <?php if ($this->session->userdata('deals_kanban_view') == 'true') { ?>
                            <div class="tw-flex ">
                                <div class="col-sm-6 col-xs-12 pull-right deals-search">
                                    <div data-toggle="tooltip" data-placement="top"
                                         data-title="<?php echo _l('search_by_tags'); ?>">
                                        <?php echo render_input('search', '', '', 'search', ['data-name' => 'search', 'onkeyup' => 'deals_kanban();', 'placeholder' => _l('deals_search')], [], 'no-margin') ?>


                                    </div>
                                </div>
                                <div id="kanban-params">
                                    <?php echo render_input('pipeline_id', '', $default_pipeline, 'hidden', ['data-name' => 'pipeline', 'id' => 'pipeline'], [], 'no-margin') ?>
                                </div>
                                <div class="col-sm-6 col-xs-12 pull-right deals-search">
                                    <div data-toggle="tooltip" data-placement="top"
                                         data-title="<?php echo _l('pipeline'); ?>">
                                        <?php echo render_select('pipeline_id', $piplines, ['pipeline_id', 'pipeline_name'], '', $default_pipeline, ['data-name' => 'select', 'onchange' => 'deals_pipeline_kanban();', 'data-width' => '100%', 'data-none-selected-text' => _l('pipeline')], [], 'no-mbot'); ?>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>
                        <?php echo form_hidden('sort_type'); ?>
                        <?php echo form_hidden('sort', (get_option('default_deals_kanban_sort') != '' ? get_option('default_deals_kanban_sort_type') : '')); ?>
                    </div>
                    <div class="clearfix"></div>
                    <div class="hide deals-overview tw-mt-2 sm:tw-mt-4 tw-mb-4 sm:tw-mb-0">
                        <div id="deal_state_report_div">
                            <div class="row">
                                <div class="col-lg-3">
                                    <!-- START widget-->
                                    <div class="panel widget">
                                        <div class="panel-body pl-sm pr-sm pt-sm pb0 text-center">
                                            <h3 class="mt0 mb0">
                                                <strong>
                                                    <?php
                                                    // total deals value from tbl_deals
                                                    $total_deals = $this->db->select_sum('deal_value')->get('tbl_deals')->row();
                                                    echo deals_display_money($total_deals->deal_value, deals_default_currency());
                                                    ?>
                                                </strong>
                                            </h3>
                                            <p class="text-warning m0"><?= _l('total') . ' ' . _l('deals') ?></p>
                                        </div>
                                    </div>
                                </div>
                                <!-- END widget-->

                                <div class="col-lg-3">
                                    <!-- START widget-->
                                    <div class="panel widget">
                                        <div class="panel-body pl-sm pr-sm pt-sm pb0 text-center">
                                            <h3 class="mt0 mb0">
                                                <strong>
                                                    <?php
                                                    // this_months deals value from tbl_deals where created_at
                                                    $where = array('MONTH(created_at)' => date('m'), 'YEAR(created_at)' => date('Y'));
                                                    $this_months_deals = $this->db->where($where)->select_sum('deal_value')->get('tbl_deals')->row();
                                                    echo deals_display_money($this_months_deals->deal_value, deals_default_currency());
                                                    ?>
                                                </strong>

                                            </h3>
                                            <p class="text-primary m0"><?= _l('this_months') . ' ' . _l('deals') ?></p>
                                        </div>
                                    </div>
                                    <!-- END widget-->
                                </div>
                                <div class="col-lg-3">
                                    <!-- START widget-->
                                    <div class="panel widget">
                                        <div class="panel-body pl-sm pr-sm pt-sm pb0 text-center">
                                            <h3 class="mt0 mb0">
                                                <?php
                                                // this_weeks deals value from tbl_deals where created_at
                                                $where = array('WEEK(created_at)' => date('W'), 'YEAR(created_at)' => date('Y'));
                                                $this_weeks_deals = $this->db->where($where)->select_sum('deal_value')->get('tbl_deals')->row();
                                                echo deals_display_money($this_weeks_deals->deal_value, deals_default_currency());
                                                ?>
                                            </h3>
                                            <p class="text-danger m0"><?= _l('this_weeks') . ' ' . _l('deals') ?></p>
                                        </div>
                                    </div>
                                    <!-- END widget-->
                                </div>
                                <div class="col-lg-3">
                                    <!-- START widget-->
                                    <div class="panel widget">
                                        <div class="panel-body pl-sm pr-sm pt-sm pb0 text-center">
                                            <h3 class="mt0 mb0">
                                                <?php
                                                // last 30 days deals value from tbl_deals where created_at
                                                $where = array('created_at >=' => date('Y-m-d', strtotime('-30 days')));
                                                $last_30_days_deals = $this->db->where($where)->select_sum('deal_value')->get('tbl_deals')->row();
                                                echo deals_display_money($last_30_days_deals->deal_value, deals_default_currency());
                                                ?>
                                            </h3>
                                            <p class="text-success m0"><?= _l('last_30_days') . ' ' . _l('deals') ?></p>
                                        </div>
                                    </div>
                                    <!-- END widget-->
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
                <div class="<?php echo $isKanBan ? '' : 'panel_s'; ?>">
                    <div class="<?php echo $isKanBan ? '' : 'panel-body'; ?>">
                        <div class="tab-content">
                            <?php
                            if ($isKanBan) { ?>
                                <div class="active kan-ban-tab" id="kan-ban-tab" style="overflow:auto;">
                                    <div class="kanban-leads-sort">
                                        <span class="bold"><?php echo _l('leads_sort_by'); ?>: </span>
                                        <a href="#" onclick="deals_kanban_sort('created_at'); return false"
                                           class="dateadded">
                                            <?php if (get_option('default_leads_kanban_sort') == 'created_at') {
                                                echo '<i class="kanban-sort-icon fa fa-sort-amount-' . strtolower(get_option('default_leads_kanban_sort_type')) . '"></i> ';
                                            } ?><?php echo _l('leads_sort_by_datecreated'); ?>
                                        </a>
                                        |
                                        <a href="#" onclick="deals_kanban_sort('dealorder');return false;"
                                           class="leadorder">
                                            <?php if (get_option('default_leads_kanban_sort') == 'dealorder') {
                                                echo '<i class="kanban-sort-icon fa fa-sort-amount-' . strtolower(get_option('default_leads_kanban_sort_type')) . '"></i> ';
                                            } ?><?php echo _l('leads_sort_by_kanban_order'); ?>
                                        </a>
                                        |
                                        <a href="#" onclick="deals_kanban_sort('deal_value');return false;"
                                           class="lastcontact">
                                            <?php if (get_option('default_leads_kanban_sort') == 'deal_value') {
                                                echo '<i class="kanban-sort-icon fa fa-sort-amount-' . strtolower(get_option('default_leads_kanban_sort_type')) . '"></i> ';
                                            } ?><?php echo _l('leads_sort_by_deal_value'); ?>
                                        </a>
                                    </div>
                                    <div class="row">
                                        <div class="container-fluid leads-kan-ban">
                                            <div id="kan-ban"></div>
                                        </div>
                                    </div>
                                </div>
                            <?php } else { ?>
                                <div class="row" id="deals-table">
                                    <div class="col-md-12">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <p class="bold"><?php echo _l('filter_by'); ?></p>
                                            </div>

                                            <div class="col-md-2 leads-filter-column">
                                                <?php echo render_select('deal_owner', $staff, ['staffid', ['firstname', 'lastname']], '', '', ['data-width' => '100%', 'data-none-selected-text' => _l('deal_owner')], [], 'no-mbot'); ?>
                                            </div>
                                            <div class="col-md-2 leads-filter-column">
                                                <?php
                                                echo '<div id="leads-filter-status">';
                                                echo render_select('view_pipeline', $piplines, ['pipeline_id', 'pipeline_name'], '', '', ['data-width' => '100%', 'data-none-selected-text' => _l('pipelines')], [], 'no-mbot');
                                                echo '</div>';
                                                ?>
                                            </div>
                                            <div class="col-md-2 leads-filter-column">
                                                <?php
                                                echo render_select('view_source', $sources, ['source_id', 'source_name'], '', '', ['data-width' => '100%', 'data-none-selected-text' => _l('deal_source')], [], 'no-mbot');
                                                ?>
                                            </div>
                                            <div class="col-md-2 leads-filter-column">
                                                <div class="select-placeholder">
                                                    <select name="custom_view"
                                                            title="<?php echo _l('additional_filters'); ?>"
                                                            id="custom_view"
                                                            class="selectpicker" data-width="100%">
                                                        <option value=""></option>
                                                        <option value="customer"><?php echo _l('customer'); ?></option>
                                                        <option value="contract"><?php echo _l('contract'); ?></option>
                                                        <option value="lead"><?php echo _l('lead'); ?></option>
                                                        <option value="proposal"><?php echo _l('proposal'); ?></option>
                                                        <option value="open"><?php echo _l('open'); ?></option>
                                                        <option value="lost"><?php echo _l('lead_lost'); ?></option>
                                                        <option value="won"><?php echo _l('won'); ?></option>
                                                        <option value="created_today"><?php echo _l('created_today'); ?></option>
                                                        <option value="created_this_week"><?php echo _l('created_this_week'); ?></option>
                                                        <option value="created_last_week"><?php echo _l('created_last_week'); ?></option>
                                                        <option value="created_this_month"><?php echo _l('created_this_month'); ?></option>
                                                        <option value="created_last_month"><?php echo _l('created_last_month'); ?></option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <hr class="hr-panel-separator"/>
                                    </div>
                                    <div class="clearfix"></div>

                                    <div class="col-md-12">
                                        <a href="#" data-toggle="modal" data-table=".table-leads"
                                           data-target="#leads_bulk_actions"
                                           class="hide bulk-actions-btn table-btn"><?php echo _l('bulk_actions'); ?></a>
                                        <div class="modal fade bulk_actions" id="leads_bulk_actions" tabindex="-1"
                                             role="dialog">
                                            <div class="modal-dialog" role="document">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <button type="button" class="close" data-dismiss="modal"
                                                                aria-label="Close"><span
                                                                    aria-hidden="true">&times;</span></button>
                                                        <h4 class="modal-title"><?php echo _l('bulk_actions'); ?></h4>
                                                    </div>
                                                    <div class="modal-body">
                                                        <?php if (has_permission('leads', '', 'delete')) { ?>
                                                            <div class="checkbox checkbox-danger">
                                                                <input type="checkbox" name="mass_delete"
                                                                       id="mass_delete">
                                                                <label
                                                                        for="mass_delete"><?php echo _l('mass_delete'); ?></label>
                                                            </div>
                                                            <hr class="mass_delete_separator"/>
                                                        <?php } ?>
                                                        <div id="bulk_change">
                                                            <div class="form-group">
                                                                <div class="checkbox checkbox-primary checkbox-inline">
                                                                    <input type="checkbox" name="leads_bulk_mark_lost"
                                                                           id="leads_bulk_mark_lost" value="1">
                                                                    <label for="leads_bulk_mark_lost">
                                                                        <?php echo _l('lead_mark_as_lost'); ?>
                                                                    </label>
                                                                </div>
                                                            </div>
                                                            <?php echo render_select('move_to_status_leads_bulk', $statuses, ['id', 'name'], 'ticket_single_change_status'); ?>
                                                            <?php
                                                            echo render_select('move_to_source_leads_bulk', $sources, ['id', 'name'], 'lead_source');
                                                            echo render_datetime_input('leads_bulk_last_contact', 'leads_dt_last_contact');
                                                            echo render_select('assign_to_leads_bulk', $staff, ['staffid', ['firstname', 'lastname']], 'leads_dt_assigned');
                                                            ?>
                                                            <div class="form-group">
                                                                <?php echo '<p><b><i class="fa fa-tag" aria-hidden="true"></i> ' . _l('tags') . ':</b></p>'; ?>
                                                                <input type="text" class="tagsinput" id="tags_bulk"
                                                                       name="tags_bulk" value="" data-role="tagsinput">
                                                            </div>
                                                            <hr/>
                                                            <div class="form-group no-mbot">
                                                                <div class="radio radio-primary radio-inline">
                                                                    <input type="radio" name="leads_bulk_visibility"
                                                                           id="leads_bulk_public" value="public">
                                                                    <label for="leads_bulk_public">
                                                                        <?php echo _l('lead_public'); ?>
                                                                    </label>
                                                                </div>
                                                                <div class="radio radio-primary radio-inline">
                                                                    <input type="radio" name="leads_bulk_visibility"
                                                                           id="leads_bulk_private" value="private">
                                                                    <label for="leads_bulk_private">
                                                                        <?php echo _l('private'); ?>
                                                                    </label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-default"
                                                                data-dismiss="modal"><?php echo _l('close'); ?></button>
                                                        <a href="#" class="btn btn-primary"
                                                           onclick="leads_bulk_action(this); return false;"><?php echo _l('confirm'); ?></a>
                                                    </div>
                                                </div>
                                                <!-- /.modal-content -->
                                            </div>
                                            <!-- /.modal-dialog -->
                                        </div>

                                        <div class="table-responsive">
                                            <table class="table table-striped DataTables " id="DataTables" width="100%">
                                                <thead>
                                                <tr>
                                                    <th><?= _l('title') ?></th>
                                                    <th><?= _l('deal_value') ?></th>
                                                    <th><?= _l('tags') ?></th>
                                                    <th><?= _l('stage') ?></th>
                                                    <th><?= _l('close') . ' ' . _l('date') ?></th>
                                                    <th><?= _l('status') ?></th>
                                                    <?php
                                                    $custom_fields = get_table_custom_fields('deals');
                                                    foreach ($custom_fields as $field) {
                                                        echo '<th>' . $field['name'] . '</th>';
                                                    }
                                                    ?>
                                                    <th><?= _l('action') ?></th>
                                                </tr>
                                                </thead>
                                                <tbody id="deals_table">

                                                </tbody>
                                            </table>
                                            <script type="text/javascript">
                                                list = "<?= admin_url() ?>deals/dealsList";
                                            </script>
                                        </div>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php init_tail(); ?>
<script>

    deals_kanban();

    function deals_kanban_sort(type) {
        kan_ban_sort(type, deals_kanban);
    }

    function deals_pipeline_kanban() {
        let pipeline_id = $('select[name="pipeline_id"]').val();
        // update the value of pipeline id
        $('input[name="pipeline_id"]').val(pipeline_id);
        deals_kanban(pipeline_id);
    }

    // Init the leads kanban
    function deals_kanban(search) {
        init_kanban(
            "deals/kanban",
            deals_kanban_update,
            ".leads-status",
            290,
            360,
            init_deals_stages_sortable
        );
    }

    function deals_kanban_update(ui, object) {
        if (object !== ui.item.parent()[0]) {
            return;
        }

        var data = {
            status: $(ui.item.parent()[0]).attr("data-lead-status-id"),
            leadid: $(ui.item).attr("data-lead-id"),
            order: [],
        };

        $.each($(ui.item).parents(".leads-status").find("li"), function (idx, el) {
            var id = $(el).attr("data-lead-id");
            if (id) {
                data.order.push([id, idx + 1]);
            }
        });

        setTimeout(function () {
            $.post(admin_url + "deals/update_deal_satges", data).done(function (
                response
            ) {
                update_kan_ban_total_when_moving(ui, data.status);
                deals_kanban();
            });
        }, 200);
    }

    function init_deals_stages_sortable() {
        $("#kan-ban").sortable({
            helper: "clone",
            item: ".kan-ban-col",
            update: function (event, ui) {
                data = {
                    order: [],
                };

                $.each($(".kan-ban-col"), function (idx, el) {
                    data.order.push([$(el).attr("data-col-status-id"), idx + 1]);
                });

                $.post(admin_url + "deals/update_stage_order", data);
            },
        });
    }

    // check list is have veriable or not
    if (typeof list !== 'undefined') {

        const DealsServerParams = {
            custom_view: "[name='custom_view']",
            deal_owner: "[name='deal_owner']",
            pipeline: "[name='view_pipeline']",
            source: "[name='view_source']",
            company: "[name='company_id']",
        };
        $(function () {
            'use strict';
            initDataTable('#DataTables', list, undefined, undefined, DealsServerParams);
        });
        const table_deals = $("table#DataTables");

        $.each(DealsServerParams, function (i, obj) {
            let params = {};
            $("select" + obj).on("change", function () {
                $("[name='view_status[]']")
                    .prop("disabled", $(this).val() == "lost" || $(this).val() == "junk")
                    .selectpicker("refresh");
                params[i] = $(this).val();
            });
            $("select" + obj).on("change", function () {
                table_deals.DataTable().ajax.reload();
            });
        });
    }
</script>