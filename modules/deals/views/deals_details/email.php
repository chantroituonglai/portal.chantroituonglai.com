<?php
$sub_active = 1;
$task_timer_id = $this->uri->segment(6);
if ($task_timer_id) {
    $sub_active = 2;
    $deals_email_details = get_deals_row('tbl_deals_email', array('id' => $task_timer_id));
    $username_email_details = get_deals_row('tblstaff', array('staffid' => $deals_email_details->user_id));
}
$edited = has_permission('deals', '', 'edit');

?>
<div class="nav-tabs-custom ">
    <!-- Tabs within a box -->
    <ul class="nav nav-tabs" style="margin-top: -20px; margin-bottom: 0px">
        <li class="<?= $sub_active == 1 ? 'active' : ''; ?>"><a href="#all_email"
                                                                data-toggle="tab"><?= _l('all_email') ?></a>
        </li>
        <?php if (!empty($edited)) { ?>
            <li class="<?= $sub_active == 2 ? 'active' : ''; ?>"><a href="#new_email"
                                                                    data-toggle="tab"><?= _l('new_email') ?></a>
            </li>
        <?php } ?>
    </ul>
    <div class="tab-content bg-white">
        <!-- ************** general *************-->
        <div class="tab-pane <?= $sub_active == 1 ? 'active' : ''; ?>" id="all_email">
            <div class="table-responsive">
                <table class="table table-striped " cellspacing="0" width="100%">
                    <thead>
                    <tr>
                        <th><?= _l('mail_to') ?></th>
                        <th><?= _l('subject') ?></th>
                        <th><?= _l('mail_from') ?></th>
                        <th><?= _l('message_time') ?></th>
                        <th class="col-options no-sort"><?= _l('action') ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    $all_deals_email = get_deals_result('tbl_deals_email', array('deals_id' => $deals_details->id));

                    if (!empty($all_deals_email)) :
                        foreach ($all_deals_email as $v_emails) :
                            ?>
                            <tr id="table-meeting-<?= $v_emails->id ?>">
                                <td>
                                    <?php
                                    $file_ext = explode(";", $v_emails->email_to);
                                    foreach ($file_ext as $key => $item) {
                                        echo $item . "<br>";
                                    }
                                    ?>
                                </td>
                                <td><?= $v_emails->subject ?></td>
                                <td><?= $v_emails->email_from ?></td>
                                <td><?= _d($v_emails->message_time) ?>

                                </td>
                                <td>
                                    <a href="<?= base_url('admin/deals/email_details/' . $v_emails->id) ?>"
                                       class="btn btn-xs btn-info" data-placement="top" data-toggle="modal"
                                       data-target="#myModal">
                                        <i class="fa fa-list "></i></a>
                                    <?= btn_edit_deals('admin/deals/details/' . $deals_details->id . '/email/' . $v_emails->id) ?>
                                    <a href="<?= base_url('admin/deals/delete_deals_email/' . $deals_details->id . '/' . $v_emails->id) ?>"
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
        <?php
        if (!empty($deals_email_details)) {
            $id = $deals_email_details->id;
        } else {
            $id = null;
        }
        ?>
        <div class="tab-pane <?= $sub_active == 2 ? 'active' : ''; ?>" id="new_email">
            <div class="panel-body">

                <?php echo form_open(base_url('admin/deals/send_mail/' . $id), array('id' => 'deals_email_form', 'enctype' => 'multipart/form-data', 'data-parsley-validate' => '', 'role' => 'form')); ?>


                <input type="hidden" name="deals_id" class="form-control" value="<?= $deals_details->id ?>">
                <div class="box box-primary">
                    <div class="box-body row">
                        <div class="form-group col-md-12">
                            <input class="form-control" value="<?php
                            if (!empty($deals_email_details->email_to)) {
                                echo $deals_email_details->email_to;
                            }
                            ?>" type="text" required="" name="email_to"
                                   placeholder="<?= _l('you_can_sent_multiple_mail_semicolon_separated') ?>"/>
                        </div>
                        <div class="form-group col-md-12">
                            <input class="form-control" value="<?php
                            if (!empty($deals_email_details->email_cc)) {
                                echo $deals_email_details->email_cc;
                            }
                            ?>" type="text" name="email_cc"
                                   placeholder="<?= _l('add_cc') ?>"/>
                        </div>


                        <div class="form-group col-md-12">
                            <input class="form-control" value="<?php
                            if (!empty($deals_email_details->subject)) {
                                echo $deals_email_details->subject;
                            }
                            ?>" type="text" required="" name="subject" placeholder="Subject:"/>
                        </div>

                        <div class="form-group col-md-12">

                            <textarea name="message_body" class="form-control tinymce" rows="3"><?php
                                if (!empty($deals_email_details->message_body)) {
                                    echo html_escape($deals_email_details->message_body);
                                } ?></textarea>

                        </div>
                        <div class="form-group col-md-12">
                            <div class="attachments_area">
                                <div class="row attachments">
                                    <div class="attachment">
                                        <div class="col-md-4  mtop10">
                                            <div class="form-group">
                                                <label for="attachment"
                                                       class="control-label"><?php echo _l('ticket_add_attachments'); ?></label>
                                                <div class="input-group">
                                                    <input type="file"
                                                           extension="<?php echo str_replace(['.', ' '], '', get_option('ticket_attachments_file_extensions')); ?>"
                                                           filesize="<?php echo file_upload_max_size(); ?>"
                                                           class="form-control" name="attachments[0]"
                                                           accept="<?php echo get_ticket_form_accepted_mimes(); ?>">
                                                    <span class="input-group-btn">
                                                            <button class="btn btn-default add_more_attachments"
                                                                    data-max="<?php echo get_option('maximum_allowed_ticket_attachments'); ?>"
                                                                    type="button"><i class="fa fa-plus"></i></button>
                                                        </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div><!-- /.box-body -->
                    <div class="box-footer row">
                        <div class="col-md-12">
                            <div class="pull-right">
                                <button type="submit" class="btn btn-primary"><i
                                            class="fa fa-envelope-o"></i> <?= _l('send') ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </div><!-- /. box -->

                <?php echo form_close(); ?>


            </div>


        </div>

    </div>
</div>