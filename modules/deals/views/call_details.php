<?php

if (!empty($details->module_field_id) && !empty($details->module) && $details->module == 'deals') {
    $client_info = $this->deals_model->get_deal_customers($details->module_field_id);
    $client_info['company'] = $client_info['name'];
    // array to object
    $client_info = (object)$client_info;
}
$user = $this->deals_model->check_deals_by(array('staffid' => $details->user_id), db_prefix() . 'staff');
?>

<div class="panel-heading">
    <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span
                class="sr-only">Close</span></button>
    <h4 class="modal-title" id="myModalLabel"><?= $title ?></h4>
</div>
<div class="modal-body wrap-modal wrap">
    <div class="panel-body form-horizontal">
        <div class="col-md-12 notice-details-margin">
            <div class="col-sm-4 text-right">
                <label class="control-label"><strong><?= _l('contact_with') ?> :</strong></label>
            </div>
            <div class="col-sm-8">
                <p class="form-control-static"><?php if (!empty($client_info->company)) echo $client_info->company; ?></p>
            </div>
        </div>
        <div class="col-md-12 notice-details-margin">
            <div class="col-sm-4 text-right">
                <label class="control-label"><strong><?= _l('date') ?> :</strong></label>
            </div>
            <div class="col-sm-8">
                <p class="form-control-static"><?php if (!empty($details->date)) echo _d($details->date) ?></p>
            </div>
        </div>
        <div class="col-md-12 notice-details-margin">
            <div class="col-sm-4 text-right">
                <label class="control-label"><strong><?= _l('responsible') ?> :</strong></label>
            </div>
            <div class="col-sm-8">
                <p class="form-control-static"><?php if (!empty($user->firstname)) echo $user->firstname . ' ' . $user->lastname; ?></p>
            </div>
        </div>
        <div class="col-md-12 notice-details-margin">
            <div class="col-sm-4 text-right">
                <label class="control-label"><strong><?= _l('call_type') ?> :</strong></label>
            </div>
            <div class="col-sm-8">
                <p class="form-control-static"><?php if (!empty($details->call_type)) echo _l($details->call_type); ?></p>
            </div>
        </div>
        <div class="col-md-12 notice-details-margin">
            <div class="col-sm-4 text-right">
                <label class="control-label"><strong><?= _l('outcome') ?> :</strong></label>
            </div>
            <div class="col-sm-8">
                <p class="form-control-static"><?php if (!empty($details->outcome)) echo _l($details->outcome); ?></p>
            </div>
        </div>
        <div class="col-md-12 notice-details-margin">
            <div class="col-sm-4 text-right">
                <label class="control-label"><strong><?= _l('duration') ?> :</strong></label>
            </div>
            <div class="col-sm-8">
                <p class="form-control-static"><?php if (!empty($details->duration)) echo $details->duration; ?></p>
            </div>
        </div>

        <div class="col-md-12 notice-details-margin">
            <div class="col-sm-4 text-right">
                <label class="control-label"><strong><?= _l('call_summary') ?> :</strong></label>
            </div>
            <div class="col-sm-8">
                <p class=""><?php if (!empty($details->call_summary)) echo $details->call_summary; ?></p>
            </div>
        </div>
        <div class="col-md-12 notice-details-margin text-right">
            <button type="button" class="btn btn-default" data-dismiss="modal"><?= _l('close') ?></button>
        </div>
    </div>
</div>