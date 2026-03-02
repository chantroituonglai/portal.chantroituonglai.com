<?php echo form_open('admin/deals/add_deals_attachment', ['class' => 'dropzone mtop15 mbot15', 'id' => 'deals-attachment-upload']); ?>
<input type="hidden" name="id" value="<?= $deals_details->id ?>">
<?php echo form_close(); ?>

<?php if (get_option('dropbox_app_key') != '') { ?>
    <hr/>
    <div class=" pull-left">
        <?php if (count($deals_details->attachments) > 0) { ?>
            <a href="<?php echo admin_url('deals/download_files/' . $deals_details->id); ?>" class="bold">
                <?php echo _l('download_all'); ?> (.zip)
            </a>
        <?php } ?>
    </div>
    <div class="tw-flex tw-justify-end tw-items-center tw-space-x-2" id="deals-modal">
        <button class="gpicker">
            <i class="fa-brands fa-google" aria-hidden="true"></i>
            <?php echo _l('choose_from_google_drive'); ?>
        </button>
        <div id="dropbox-chooser-deals"></div>
    </div>
    <div class=" clearfix"></div>
<?php } ?>
<?php
if (count($deals_details->attachments) > 0) { ?>
    <div class="mtop20" id="deals_attachments">
        <?php $this->load->view('deals/deals_details/deals_attachments_template', ['attachments' => $deals_details->attachments]); ?>
    </div>
<?php } ?>

<script type="text/javascript">
    taskAttachmentDropzone = new Dropzone("#deals-attachment-upload", appCreateDropzoneOptions({
        uploadMultiple: true,
        parallelUploads: 20,
        maxFiles: 20,
        paramName: 'file',
        sending: function (file, xhr, formData) {
            formData.append("deal_id", '<?php echo $deals_details->id; ?>');
        },
        success: function (files, response) {
            window.location.reload();
        }
    }));
</script>
