<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$CI = &get_instance();
$CI->load->model('mautic_bridge/Mautic_bridge_model', 'mauticBridgeModel');
$projectId = isset($project) && isset($project->id) ? (int) $project->id : 0;
$maps = $CI->mauticBridgeModel->get_project_hq_for_perfex_project($projectId);
?>
<div class="row">
    <div class="col-md-12">
        <div class="tw-flex tw-items-center tw-justify-between tw-gap-2 mbot15">
            <h4 class="no-margin"><?php echo _l('mautic_bridge_project_hq'); ?></h4>
            <a href="<?php echo admin_url('mautic_bridge_manage/projects/create'); ?>" class="btn btn-default">
                <i class="fa fa-link"></i> <?php echo _l('mautic_bridge_link_campaign_segment'); ?>
            </a>
        </div>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th><?php echo _l('mautic_bridge_initiative'); ?></th>
                        <th><?php echo _l('mautic_bridge_campaign_name'); ?></th>
                        <th><?php echo _l('mautic_bridge_segment'); ?></th>
                        <th><?php echo _l('mautic_bridge_last_synced_at'); ?></th>
                        <th><?php echo _l('options'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($maps as $map) { ?>
                        <tr>
                            <td><?php echo html_escape($map['initiative_name']); ?></td>
                            <td><?php echo html_escape($map['mautic_campaign_name'] ?? '-'); ?></td>
                            <td><?php echo html_escape($map['mautic_segment_name'] ?? '-'); ?></td>
                            <td><?php echo html_escape($map['last_synced_at'] ?? '-'); ?></td>
                            <td>
                                <a href="<?php echo admin_url('mautic_bridge_manage/projects/' . (int) $map['id']); ?>" class="btn btn-default btn-icon">
                                    <i class="fa fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                    <?php } ?>
                    <?php if (empty($maps)) { ?>
                        <tr><td colspan="5" class="text-center text-muted"><?php echo _l('no_records_found'); ?></td></tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
