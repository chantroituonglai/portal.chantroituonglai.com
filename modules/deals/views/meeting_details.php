<div class="panel-heading">
    <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span
                class="sr-only">Close</span></button>
    <h4 class="modal-title" id="myModalLabel"><?= _l('meeting_details') ?></h4>
</div>
<div class="wrap-modal wrap">
    <div class="panel-body form-horizontal">
        <div class="col-md-12 notice-details-margin">
            <div class="col-sm-4 text-right">
                <label class="control-label"><strong><?= _l('subject') ?> :</strong></label>
            </div>
            <div class="col-sm-8">
                <p class="form-control-static"><?php if (!empty($details->meeting_subject)) echo $details->meeting_subject; ?></p>
            </div>
        </div>
        <div class="col-md-12 notice-details-margin">
            <div class="col-sm-4 text-right">
                <label class="control-label"><strong><?= _l('start_date') ?> :</strong></label>
            </div>
            <div class="col-sm-8">
                <p class="form-control-static"><?php if (!empty($details->start_date)) echo _dt($details->start_date); ?></p>
            </div>
        </div>
        <div class="col-md-12 notice-details-margin">
            <div class="col-sm-4 text-right">
                <label class="control-label"><strong><?= _l('end_date') ?> :</strong></label>
            </div>
            <div class="col-sm-8">
                <p class="form-control-static"><?php if (!empty($details->end_date)) echo _dt($details->end_date); ?></p>
            </div>
        </div>
        <div class="col-md-12 notice-details-margin">
            <div class="col-sm-4 text-right">
                <label class="control-label"><strong><?= _l('attendess') ?> :</strong></label>
            </div>
            <div class="col-sm-8">
                <p class="form-control-static"><?php
                    if (!empty($details->attendees)) {
                        $user_id = unserialize($details->attendees);
                        foreach ($user_id['attendees'] as $assding_id) {
                            echo fullname($assding_id) . '<br/>';
                        }
                    }
                    ?></p>
            </div>
        </div>
        <div class="col-md-12 notice-details-margin">
            <div class="col-sm-4 text-right">
                <label class="control-label"><strong><?= _l('responsible') ?> :</strong></label>
            </div>
            <div class="col-sm-8">
                <p class="form-control-static"><?php
                    if (!empty($details->user_id)) {
                        echo fullname($details->user_id);
                    }
                    ?></p>
            </div>
        </div>
        <div class="col-md-12 notice-details-margin">
            <div class="col-sm-4 text-right">
                <label class="control-label"><strong><?= _l('location') ?> :</strong></label>
            </div>
            <div class="col-sm-8">
                <p class="form-control-static"><?php
                    if (!empty($details->location)) {
                        echo $details->location;
                    }
                    ?></p>
            </div>
        </div>
        <div class="col-md-12 notice-details-margin">
            <div class="col-sm-4 text-right">
                <label class="control-label"><strong><?= _l('description') ?> :</strong></label>
            </div>
            <div class="col-sm-8">
                <p class=""><?php
                    if (!empty($details->description)) {
                        echo $details->description;
                    }
                    ?></p>
            </div>
        </div>
        <div class="col-md-12 notice-details-margin text-right">
            <button type="button" class="btn btn-default" data-dismiss="modal"><?= _l('close') ?></button>
        </div>
    </div>
</div>
