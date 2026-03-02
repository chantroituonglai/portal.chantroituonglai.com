<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="panel_s">
  <div class="panel-body">
    <h4 class="mbot15"><i class="fa fa-copy"></i> <?php echo _l('pma_tab_project_composer'); ?></h4>
    <div class="alert alert-info"><i class="fa fa-info-circle"></i> <?php echo _l('pma_tab_description'); ?></div>
    <div class="row">
      <div class="col-md-8">
        <p><?php echo _l('pma_tab_current_project_info'); ?></p>
        <ul class="list-unstyled">
          <li><strong><?php echo _l('project_name'); ?>:</strong> <?php echo e($project->name); ?></li>
          <li><strong><?php echo _l('client'); ?>:</strong> <?php echo e($project->client_data->company); ?></li>
          <li><strong><?php echo _l('start_date'); ?>:</strong> <?php echo _d($project->start_date); ?></li>
          <li><strong><?php echo _l('deadline'); ?>:</strong> <?php echo _d($project->deadline); ?></li>
        </ul>
      </div>
      <div class="col-md-4 text-right">
        <button type="button" class="btn btn-primary btn-lg" onclick="pma_start_composition_from_project(<?php echo (int)$project->id; ?>)"><i class="fa fa-magic"></i> <?php echo _l('pma_btn_start_composition'); ?></button>
      </div>
    </div>
    <hr>
    <div class="row">
      <div class="col-md-12">
        <h5><?php echo _l('pma_title_what_will_be_cloned'); ?></h5>
        <div class="row">
          <div class="col-md-3"><div class="checkbox"><input type="checkbox" id="clone_tasks" checked disabled> <label for="clone_tasks"><?php echo _l('tasks'); ?></label></div></div>
          <div class="col-md-3"><div class="checkbox"><input type="checkbox" id="clone_milestones" checked disabled> <label for="clone_milestones"><?php echo _l('milestones'); ?></label></div></div>
          <div class="col-md-3"><div class="checkbox"><input type="checkbox" id="clone_files" checked disabled> <label for="clone_files"><?php echo _l('project_files'); ?></label></div></div>
          <div class="col-md-3"><div class="checkbox"><input type="checkbox" id="clone_discussions" checked disabled> <label for="clone_discussions"><?php echo _l('discussions'); ?></label></div></div>
        </div>
      </div>
    </div>
  </div>
</div>

