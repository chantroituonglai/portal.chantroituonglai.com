<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php $this->load->view('mautic_bridge/manage/_header'); ?>
<?php $summary = $summary ?? []; ?>
<div class="row">
    <div class="col-md-3 col-sm-6">
        <div class="mautic-bridge-stat">
            <div class="text-muted"><?php echo _l('mautic_bridge_project_hq_projects'); ?></div>
            <div class="stat-value"><?php echo (int) ($summary['projects'] ?? 0); ?></div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="mautic-bridge-stat">
            <div class="text-muted"><?php echo _l('mautic_bridge_project_hq_objects'); ?></div>
            <div class="stat-value"><?php echo (int) ($summary['objects'] ?? 0); ?></div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="mautic-bridge-stat">
            <div class="text-muted"><?php echo _l('mautic_bridge_project_hq_open_issues'); ?></div>
            <div class="stat-value"><?php echo (int) ($summary['open_issue_tasks'] ?? 0); ?></div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="mautic-bridge-stat">
            <div class="text-muted"><?php echo _l('mautic_bridge_latest_log'); ?></div>
            <div><?php echo html_escape($summary['latest_log']['message'] ?? '-'); ?></div>
        </div>
    </div>
</div>

<div class="tw-flex tw-items-center tw-justify-between mtop20 mbot15">
    <h4 class="no-margin"><?php echo _l('mautic_bridge_project_hq'); ?></h4>
    <a href="<?php echo admin_url('mautic_bridge_manage/projects/create'); ?>" class="btn btn-primary">
        <i class="fa fa-plus"></i> <?php echo _l('mautic_bridge_create_project_hq'); ?>
    </a>
</div>

<div class="table-responsive">
    <table class="table table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th><?php echo _l('project'); ?></th>
                <th><?php echo _l('client'); ?></th>
                <th><?php echo _l('mautic_bridge_initiative'); ?></th>
                <th><?php echo _l('mautic_bridge_campaign_name'); ?></th>
                <th><?php echo _l('mautic_bridge_segment'); ?></th>
                <th><?php echo _l('mautic_bridge_last_synced_at'); ?></th>
                <th><?php echo _l('options'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($projects as $project) { ?>
                <tr>
                    <td><?php echo (int) $project['id']; ?></td>
                    <td>
                        <a href="<?php echo admin_url('projects/view/' . (int) $project['perfex_project_id']); ?>">
                            <?php echo html_escape($project['perfex_project_name'] ?? '-'); ?>
                        </a>
                    </td>
                    <td><?php echo html_escape($project['customer_name'] ?? '-'); ?></td>
                    <td><?php echo html_escape($project['initiative_name']); ?></td>
                    <td><?php echo html_escape($project['mautic_campaign_name'] ?? '-'); ?></td>
                    <td><?php echo html_escape($project['mautic_segment_name'] ?? '-'); ?></td>
                    <td><?php echo html_escape($project['last_synced_at'] ?? '-'); ?></td>
                    <td>
                        <a href="<?php echo admin_url('mautic_bridge_manage/projects/' . (int) $project['id']); ?>" class="btn btn-default btn-icon">
                            <i class="fa fa-eye"></i>
                        </a>
                    </td>
                </tr>
            <?php } ?>
            <?php if (empty($projects)) { ?>
                <tr><td colspan="8" class="text-center text-muted"><?php echo _l('no_records_found'); ?></td></tr>
            <?php } ?>
        </tbody>
    </table>
</div>
<?php $this->load->view('mautic_bridge/manage/_footer'); ?>
