<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-12">
        <div class="panel_s">
          <div class="panel-body">
            <h4 class="mbot15"><i class="fa fa-copy"></i> <?php echo _l('pma_title_project_composer'); ?></h4>

            <?php if (!pma_is_project_agent_available()): ?>
              <div class="alert alert-warning"><i class="fa fa-exclamation-triangle"></i> <?php echo _l('pma_warn_project_agent_required'); ?></div>
            <?php else: ?>

            <div class="row">
              <div class="col-md-8">
                <div class="form-group">
                  <label for="source_project" class="control-label"><?php echo _l('pma_label_source_project'); ?></label>
                  <select class="form-control selectpicker" id="source_project" name="source_project" data-live-search="true">
                    <option value=""><?php echo _l('pma_select_project'); ?></option>
                    <?php if (!empty($projects)) { foreach ($projects as $p) { ?>
                      <option value="<?php echo (int)$p['id']; ?>" <?php echo (!empty($project_id) && $project_id==(int)$p['id'])?'selected':''; ?>><?php echo e($p['name']); ?> (ID: <?php echo (int)$p['id']; ?>)</option>
                    <?php } } ?>
                  </select>
                </div>
              </div>
              <div class="col-md-4">
                <label class="control-label">&nbsp;</label>
                <button type="button" class="btn btn-primary btn-block" id="btn_start_composition"><i class="fa fa-magic"></i> <?php echo _l('pma_btn_start_composition'); ?></button>
              </div>
            </div>

            <div id="step_composition_progress" class="pma-step hidden">
              <hr>
              <h5><i class="fa fa-cogs"></i> <?php echo _l('pma_title_composition_progress'); ?></h5>
              <div class="progress"><div class="progress-bar progress-bar-striped active" role="progressbar" style="width:0%"><span class="sr-only">0% Complete</span></div></div>
              <div id="composition_status" class="mtop10"></div>
            </div>

            <div id="step_ai_breakdown" class="pma-step hidden">
              <hr>
              <h5><i class="fa fa-robot"></i> <?php echo _l('pma_title_ai_breakdown'); ?></h5>
              <div id="breakdown_content"></div>
              <div class="text-right mtop15">
                <button type="button" class="btn btn-success" id="btn_proceed_to_composer"><i class="fa fa-copy"></i> <?php echo _l('pma_btn_proceed_clone'); ?></button>
              </div>
            </div>

            <div id="step_project_composer" class="pma-step hidden">
              <hr>
              <h5><i class="fa fa-cog"></i> <?php echo _l('pma_title_clone_configuration'); ?></h5>
              <div class="row">
                <div class="col-md-6">
                  <div class="form-group"><label for="new_project_name" class="control-label"><?php echo _l('pma_label_new_project_name'); ?></label><input type="text" class="form-control" id="new_project_name" name="new_project_name"></div>
                </div>
                <div class="col-md-6">
                  <div class="form-group select-placeholder"><label for="new_client" class="control-label"><?php echo _l('pma_label_new_client'); ?></label><select class="form-control ajax-search" id="new_client" name="new_client" data-live-search="true" data-width="100%" data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>"><option value=""><?php echo _l('pma_select_client'); ?></option></select></div>
                </div>
              </div>
              <div class="row">
                <div class="col-md-6"><div class="form-group"><label for="new_start_date" class="control-label"><?php echo _l('pma_label_new_start_date'); ?></label><input type="date" class="form-control" id="new_start_date" name="new_start_date" value="<?php echo date('Y-m-d'); ?>"></div></div>
                <div class="col-md-6"><div class="form-group"><label for="new_deadline" class="control-label"><?php echo _l('pma_label_new_deadline'); ?></label><input type="date" class="form-control" id="new_deadline" name="new_deadline"></div></div>
              </div>
              <div class="text-right"><button type="button" class="btn btn-default" id="btn_back_to_breakdown"><i class="fa fa-arrow-left"></i> <?php echo _l('back'); ?></button> <button type="button" class="btn btn-success" id="btn_execute_clone"><i class="fa fa-copy"></i> <?php echo _l('pma_btn_execute_clone'); ?></button></div>
            </div>

            <div id="step_clone_progress" class="pma-step hidden">
              <hr>
              <h5><i class="fa fa-copy"></i> <?php echo _l('pma_title_clone_progress'); ?></h5>
              <div class="progress"><div class="progress-bar progress-bar-striped active" role="progressbar" style="width:0%"><span class="sr-only">0% Complete</span></div></div>
              <div id="clone_status" class="mtop10"></div>
            </div>

            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Project Composer Modal -->
<div class="modal fade" id="project_composer_modal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><i class="fa fa-copy"></i> <?php echo _l('pma_title_project_composer'); ?></h4>
      </div>
      <div class="modal-body">
        <div id="modal_content"></div>
      </div>
    </div>
  </div>
</div>

<?php init_tail(); ?>
