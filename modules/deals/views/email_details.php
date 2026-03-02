<div class="panel-heading">
    <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span
                class="sr-only">Close</span></button>
    <h4 class="modal-title" id="myModalLabel"><?= $title ?></h4>
</div>
<div class="modal-body wrap-modal wrap">
    <div class="panel-body form-horizontal">
        <div class="col-md-12 notice-details-margin">
            <div class="col-sm-4 text-right">
                <label class="control-label"><strong><?= _l('mail_to') ?> :</strong></label>
            </div>
            <div class="col-sm-8">
                <p class="form-control-static"><?php
                    $file_ext = explode(";", $details->email_to);
                    foreach ($file_ext as $key => $email) {
                        echo $email . "<br>";
                    }
                    ?></p>
            </div>
        </div>
        <div class="col-md-12 notice-details-margin">
            <div class="col-sm-4 text-right">
                <label class="control-label"><strong><?= _l('subject') ?> :</strong></label>
            </div>
            <div class="col-sm-8">
                <p class="form-control-static"><?php if (!empty($details->subject)) echo $details->subject; ?></p>
            </div>
        </div>
        <div class="col-md-12 notice-details-margin">
            <div class="col-sm-4 text-right">
                <label class="control-label"><strong><?= _l('mail_from') ?> :</strong></label>
            </div>
            <div class="col-sm-8">
                <p class="form-control-static"><?php if (!empty($details->email_from)) echo $details->email_from; ?></p>
            </div>
        </div>
        <div class="col-md-12 notice-details-margin">
            <div class="col-sm-4 text-right">
                <label class="control-label"><strong><?= _l('message_time') ?> :</strong></label>
            </div>
            <div class="col-sm-8">
                <p class="form-control-static"><?php if (!empty($details->message_time)) echo _dt($details->message_time); ?></p>
            </div>
        </div>

        <div class="col-md-12 notice-details-margin">
            <div class="col-sm-4 text-right">
                <label class="control-label"><strong><?= _l('message_body') ?> :</strong></label>
            </div>
            <div class="col-sm-8">
                <p><?php
                    if (!empty($details->message_body)) {
                        echo $details->message_body;
                    }
                    ?></p>
            </div>
        </div>
        <div class="col-md-12 notice-details-margin">
            <div class="col-sm-4 text-right">
                <label class="control-label"><strong><?= _l('attach_file') ?> :</strong></label>
            </div>
            <div class="col-sm-8">

                <?php
                if (!empty($details->attach_file)) {
                    $uploaded_file = json_decode($details->attach_file);
                    $data = '<div class="row">';
                    if (!empty($uploaded_file)) {
                        foreach ($uploaded_file as $attachment) {
                            $attachment_url = site_url('admin/deals/file_download/deals_email/' . $details->id . '/' . $attachment->file_name);
                            $data .= '<div class="display-block lead-attachment-wrapper">';
                            $data .= '<div class="col-md-10">';
                            $data .= '<div class="pull-left"><i class="' . get_mime_class($attachment->filetype) . '"></i></div>';
                            $data .= '<a href="' . $attachment_url . '" target="_blank">' . $attachment->file_name . '</a>';
                            $data .= '<p class="text-muted">' . $attachment->filetype . '</p>';
                            $data .= '</div>';
                            $data .= '<div class="col-md-2 text-right">';
                            if (is_admin()) {
                                $data .= '<a href="' . base_url() . 'admin/deals/delete_email_attachment/' . $details->id . '/' . $details->file_name . '" class="text-danger" "><i class="fa fa fa-times"></i></a>';
                            }
                            $data .= '</div>';
                            $data .= '<div class="clearfix"></div><hr/>';
                            $data .= '</div>';
                        }
                        $data .= '</div>';
                        echo $data;
                    }
                }
                ?>

            </div>
        </div>

        <div class="col-md-12 notice-details-margin text-right">
            <button type="button" class="btn btn-default" data-dismiss="modal"><?= _l('close') ?></button>
        </div>
    </div>
</div>