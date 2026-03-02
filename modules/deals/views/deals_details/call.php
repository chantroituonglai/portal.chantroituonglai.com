<?php
$sub_active = 1;
$calls_id = $this->uri->segment(6);
if ($calls_id) {
    $sub_active = 2;
    $call_info = get_deals_row('tbl_deal_calls', array('calls_id' => $calls_id));
}
$edited = has_permission('deals', '', 'edit');
if (!empty($call_info)) {
    $id = $call_info->calls_id;
} else {
    $id = null;
}
?>


<div class="nav-tabs-custom ">
    <!-- Tabs within a box -->
    <ul class="nav nav-tabs" style="margin-top: -20px; margin-bottom: 0px">
        <li class="<?= $sub_active == 1 ? 'active' : ''; ?>"><a href="#manage"
                                                                data-toggle="tab"><?= _l('all_call') ?></a>
        </li>
        <?php if (!empty($edited)) { ?>
            <li class="<?= $sub_active == 2 ? 'active' : ''; ?>"><a href="#create"
                                                                    data-toggle="tab"><?= _l('new_call') ?></a>
            </li>
        <?php } ?>
    </ul>
    <div class="tab-content bg-white">
        <!-- ************** general *************-->
        <div class="tab-pane <?= $sub_active == 1 ? 'active' : ''; ?>" id="manage">

            <div class="table-responsive">
                <table class="table table-striped " cellspacing="0" width="100%">
                    <thead>
                    <tr>
                        <th><?= _l('date') ?></th>
                        <th><?= _l('call_summary') ?></th>
                        <th><?= _l('contact') ?></th>
                        <th><?= _l('responsible') ?></th>
                        <th class="col-options no-sort"><?= _l('action') ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    $all_calls_info = get_deals_result('tbl_deal_calls', array('module_field_id' => $deals_details->id));
                    if (!empty($all_calls_info)) :
                        foreach ($all_calls_info as $v_calls) :
                            $user = $this->deals_model->check_deals_by(array('staffid' => $v_calls->user_id), 'tblstaff');
                            ?>
                            <tr id="leads_call_<?= $deals_details->id ?>">
                                <td><?= _d($v_calls->date) ?>
                                </td>
                                <td><?= $v_calls->call_summary ?></td>
                                <td>
                                    <?php
                                    if (!empty($deals_details->customers['name'])) {
                                        echo $deals_details->customers['name'];
                                    }
                                    ?></td>
                                <td><?= $user->firstname . ' ' . $user->lastname ?></td>
                                <td>
                                    <a href="<?= base_url('admin/deals/call_details/' . $v_calls->calls_id) ?>"
                                       class="btn btn-xs btn-info" data-placement="top" data-toggle="modal"
                                       data-target="#myModal">
                                        <i class="fa fa-list "></i></a>
                                    <?= btn_edit_deals('admin/deals/details/' . $deals_details->id . '/call/' . $v_calls->calls_id) ?>
                                    <a href="<?= base_url('admin/deals/delete_deals_call/' . $deals_details->id . '/' . $v_calls->calls_id) ?>"
                                       class="btn btn-xs btn-danger">
                                        <i class="fa fa-remove"></i></a>
                                </td>
                            </tr>
                        <?php
                        endforeach;
                    endif;
                    ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="tab-pane <?= $sub_active == 2 ? 'active' : ''; ?>" id="create">
            <?php echo form_open(base_url('admin/deals/saved_call/' . $deals_details->id . '/' . $id), array('id' => 'deals_calls_form', 'enctype' => 'multipart/form-data', 'data-parsley-validate' => '', 'role' => 'form')); ?>

            <div class="row">
                <div class="form-group mtop20">
                    <div class="col-md-6 mtop15">
                        <label class="control-label"><?= _l('date') ?><span class="text-danger">
                        *</span></label>
                        <div class="">
                            <div class="input-group">
                                <input type="text" required="" name="date" class="form-control datepicker" value="<?php
                                if (!empty($call_info->date)) {
                                    echo $call_info->date;
                                } else {
                                    echo date('Y-m-d');
                                }
                                ?>" data-date-format="<?= config_item('date_picker_format'); ?>">
                                <div class="input-group-addon">
                                    <a href="#"><i class="fa fa-calendar"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 mtop15">
                        <label class="control-label"><?= _l('call_type') ?></label>
                        <div class="">
                            <select name="call_type" class="form-control select_box" style="width: 100%">
                                <option value="outbound" <?php if (!empty($call_info->call_type) && $call_info->call_type == 'outbound') {
                                    echo 'selected';
                                } ?>><?= _l('outbound') ?></option>
                                <option value="inbound" <?php if (!empty($call_info->call_type) && $call_info->call_type == 'inbound') {
                                    echo 'selected';
                                } ?>><?= _l('inbound') ?></option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6 mtop15">
                        <label class="control-label"><?= _l('outcome') ?><span class="text-danger">*</span></label>
                        <div class="">
                            <?php
                            $all_outcomes = [
                                'left_voice_message' => _l('left_voice_message'),
                                'moved_conversion_forward' => _l('moved_conversion_forward'),
                                'no_answer' => _l('no_answer'),
                                'not_interested' => _l('not_interested'),
                                'busy' => _l('busy'),
                                'wrong_number' => _l('wrong_number'),
                                'switched_off' => _l('switched_off'),
                                'call_back' => _l('call_back'),
                                'other' => _l('other'),
                            ]
                            ?>
                            <select name="outcome" class="form-control selectpicker" style="width: 100%">
                                <?php foreach ($all_outcomes as $key => $value) { ?>
                                    <option value="<?= $key ?>" <?php if (!empty($call_info->outcome) && $call_info->outcome == $key) {
                                        echo 'selected';
                                    } ?>><?= $value ?></option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6 mtop15">
                        <label class="control-label"><?= _l('call_duration') ?></label>
                        <div class="">
                            <input type="text" name="duration" class="form-control" id="duration" placeholder="00:35:20"
                                   value="<?php if (!empty($call_info->duration)) {
                                       echo $call_info->duration;
                                   } ?>">
                        </div>

                    </div>

                    <div class="col-md-6 mtop15">
                        <label class="control-label"><?= _l('contact') ?></label>
                        <div class="">
                            <select name="client_id" class="form-control selectpicker" style="width: 100%">
                                <?php
                                if (!empty($deals_details->customers)) {
                                    ?>
                                    <option value="<?= $deals_details->customers['id'] ?>" <?php
                                    if (!empty($call_info) && $call_info->client_id == $deals_details->customers['id']) {
                                        echo 'selected';
                                    }
                                    ?>>
                                        <?= $deals_details->customers['name'] ?>
                                    </option>
                                    <?php
                                }
                                ?>
                            </select>
                        </div>

                    </div>
                    <div class="col-md-6 mtop15">
                        <label class="control-label"><?= _l('responsible') ?><span class="text-danger"> *</span></label>
                        <div class="">
                            <select name="user_id" class="form-control selectpicker" style="width: 100%" required="">
                                <option value=""><?= _l('responsible') ?></option>
                                <?php
                                if (!empty($staff)) {
                                    foreach ($staff as $key => $v_user) {
                                        ?>
                                        <option value="<?= $v_user['staffid'] ?>" <?php
                                        if (!empty($call_info) && $call_info->user_id == $v_user['staffid']) {
                                            echo 'selected';
                                        }
                                        ?>><?= $v_user['firstname'] . ' ' . $v_user['lastname'] ?>
                                        </option>
                                        <?php
                                    }
                                }
                                ?>
                            </select>
                        </div>

                    </div>

                    <div class="col-md-12 mtop15">
                        <!-- End discount Fields -->
                        <div class="form-group terms">
                            <label class="control-label"><?= _l('call_summary') ?><span
                                        class="text-danger"> *</span> </label>
                            <div class="">
                            <textarea name="call_summary" class="form-control tinymce" rows="5"><?php
                                if (!empty($call_info->call_summary)) {
                                    echo $call_info->call_summary;
                                }
                                ?></textarea>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-sm btn-primary pull-right"><?= _l('updates') ?></button>
            <?php echo form_close(); ?>
        </div>
    </div>
</div>