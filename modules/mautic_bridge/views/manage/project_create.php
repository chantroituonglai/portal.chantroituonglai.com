<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php $this->load->view('mautic_bridge/manage/_header'); ?>
<?php $options = $form_options ?? []; ?>
<h4 class="no-margin"><?php echo _l('mautic_bridge_create_project_hq'); ?></h4>
<hr class="hr-panel-heading" />
<?php echo form_open(admin_url('mautic_bridge_manage/projects/create')); ?>
<div class="row">
    <div class="col-md-4">
        <div class="form-group">
            <label for="project_mode"><?php echo _l('mautic_bridge_project_mode'); ?></label>
            <select name="project_mode" id="project_mode" class="selectpicker" data-width="100%">
                <option value="existing"><?php echo _l('mautic_bridge_link_existing_project'); ?></option>
                <option value="new"><?php echo _l('mautic_bridge_create_new_project'); ?></option>
            </select>
        </div>
    </div>
    <div class="col-md-8">
        <div class="form-group">
            <label for="initiative_name"><?php echo _l('mautic_bridge_initiative'); ?></label>
            <input type="text" class="form-control" id="initiative_name" name="initiative_name" required>
        </div>
    </div>
</div>
<div class="row" id="mautic-bridge-existing-project-row">
    <div class="col-md-12">
        <div class="form-group">
            <label for="perfex_project_id"><?php echo _l('project'); ?></label>
            <select name="perfex_project_id" id="perfex_project_id" class="selectpicker" data-width="100%" data-live-search="true">
                <option value=""></option>
                <?php foreach (($options['projects'] ?? []) as $project) { ?>
                    <option value="<?php echo (int) $project['id']; ?>"><?php echo html_escape($project['name']); ?></option>
                <?php } ?>
            </select>
        </div>
    </div>
</div>
<div class="row hide" id="mautic-bridge-new-project-row">
    <div class="col-md-6">
        <div class="form-group">
            <label for="clientid"><?php echo _l('client'); ?></label>
            <select name="clientid" id="clientid" class="selectpicker" data-width="100%" data-live-search="true">
                <option value=""></option>
                <?php foreach (($options['clients'] ?? []) as $client) { ?>
                    <option value="<?php echo (int) $client['userid']; ?>"><?php echo html_escape($client['company']); ?></option>
                <?php } ?>
            </select>
        </div>
    </div>
    <div class="col-md-3">
        <?php echo render_date_input('start_date', 'project_start_date', date('Y-m-d')); ?>
    </div>
    <div class="col-md-3">
        <?php echo render_date_input('deadline', 'project_deadline'); ?>
    </div>
</div>
<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label for="mautic_campaign_id"><?php echo _l('mautic_bridge_campaign_name'); ?></label>
            <select name="mautic_campaign_id" id="mautic_campaign_id" class="selectpicker" data-width="100%" data-live-search="true">
                <option value=""></option>
                <?php foreach (($options['campaigns'] ?? []) as $campaign) { ?>
                    <option value="<?php echo (int) $campaign['id']; ?>"><?php echo html_escape($campaign['name']); ?> (#<?php echo (int) $campaign['id']; ?>)</option>
                <?php } ?>
            </select>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label for="mautic_segment_id"><?php echo _l('mautic_bridge_segment'); ?></label>
            <select name="mautic_segment_id" id="mautic_segment_id" class="selectpicker" data-width="100%" data-live-search="true">
                <option value=""></option>
                <?php foreach (($options['segments'] ?? []) as $segment) { ?>
                    <option value="<?php echo (int) $segment['id']; ?>"><?php echo html_escape($segment['name']); ?> (#<?php echo (int) $segment['id']; ?>)</option>
                <?php } ?>
            </select>
        </div>
    </div>
</div>
<button type="submit" class="btn btn-primary"><?php echo _l('submit'); ?></button>
<a href="<?php echo admin_url('mautic_bridge_manage/projects'); ?>" class="btn btn-default"><?php echo _l('back'); ?></a>
<?php echo form_close(); ?>
<script>
    $(function() {
        $('#project_mode').on('changed.bs.select change', function() {
            var isNew = $(this).val() === 'new';
            $('#mautic-bridge-new-project-row').toggleClass('hide', !isNew);
            $('#mautic-bridge-existing-project-row').toggleClass('hide', isNew);
        });
    });
</script>
<?php $this->load->view('mautic_bridge/manage/_footer'); ?>
