<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content mautic-bridge-manage" data-dry-run="<?php echo !empty($settings['dry_run']) ? '1' : '0'; ?>">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <div class="tw-flex tw-items-center tw-justify-between tw-gap-2">
                            <div>
                                <h4 class="no-margin"><?php echo _l('mautic_bridge_sync_manager'); ?></h4>
                                <p class="text-muted mtop5 mbot0">
                                    <?php echo !empty($settings['enabled']) ? _l('mautic_bridge_enabled') : _l('mautic_bridge_disabled'); ?>
                                    &middot;
                                    <?php echo !empty($settings['dry_run']) ? _l('mautic_bridge_dry_run') : _l('mautic_bridge_live_write_mode'); ?>
                                </p>
                            </div>
                            <div class="btn-group">
                                <a href="<?php echo admin_url('mautic_bridge'); ?>" class="btn btn-default">
                                    <i class="fa fa-cog"></i> <?php echo _l('settings'); ?>
                                </a>
                            </div>
                        </div>
                        <hr class="hr-panel-heading" />
                        <ul class="nav nav-tabs">
                            <?php
                            $tabs = [
                                'dashboard' => [admin_url('mautic_bridge_manage'), _l('mautic_bridge_dashboard')],
                                'queue' => [admin_url('mautic_bridge_manage/queue'), _l('mautic_bridge_queue')],
                                'mappings' => [admin_url('mautic_bridge_manage/mappings'), _l('mautic_bridge_mappings')],
                                'campaigns' => [admin_url('mautic_bridge_manage/campaigns'), _l('mautic_bridge_campaign_tags')],
                                'logs' => [admin_url('mautic_bridge_manage/logs'), _l('mautic_bridge_logs')],
                                'backfill' => [admin_url('mautic_bridge_manage/backfill'), _l('mautic_bridge_backfill')],
                                'google_import' => [admin_url('mautic_bridge_manage/google_import'), _l('mautic_bridge_google_import')],
                                'projects' => [admin_url('mautic_bridge_manage/projects'), _l('mautic_bridge_project_hq')],
                                'project_templates' => [admin_url('mautic_bridge_manage/project_templates'), _l('mautic_bridge_project_templates')],
                            ];
                            foreach ($tabs as $key => $tab) { ?>
                                <li class="<?php echo $active_tab === $key ? 'active' : ''; ?>">
                                    <a href="<?php echo $tab[0]; ?>"><?php echo $tab[1]; ?></a>
                                </li>
                            <?php } ?>
                        </ul>
                        <div class="mtop20">
