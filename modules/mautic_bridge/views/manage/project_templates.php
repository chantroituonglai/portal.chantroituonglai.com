<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php $this->load->view('mautic_bridge/manage/_header'); ?>
<h4 class="no-margin"><?php echo _l('mautic_bridge_project_templates'); ?></h4>
<hr class="hr-panel-heading" />
<?php foreach ($templates as $template) { ?>
    <?php $payload = json_decode((string) $template['payload_json'], true); ?>
    <div class="panel_s">
        <div class="panel-body">
            <h4 class="no-margin"><?php echo html_escape($template['name']); ?></h4>
            <p class="text-muted mtop5"><?php echo html_escape($template['description']); ?></p>
            <div class="row">
                <div class="col-md-6">
                    <h5><?php echo _l('project_milestones'); ?></h5>
                    <ul class="list-unstyled">
                        <?php foreach (($payload['milestones'] ?? []) as $milestone) { ?>
                            <li><i class="fa fa-flag text-muted"></i> <?php echo html_escape($milestone['name'] ?? ''); ?></li>
                        <?php } ?>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h5><?php echo _l('tasks'); ?></h5>
                    <ul class="list-unstyled">
                        <?php foreach (($payload['tasks'] ?? []) as $task) { ?>
                            <li><i class="fa fa-check-circle text-muted"></i> <?php echo html_escape($task['name'] ?? ''); ?></li>
                        <?php } ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
<?php } ?>
<?php $this->load->view('mautic_bridge/manage/_footer'); ?>
