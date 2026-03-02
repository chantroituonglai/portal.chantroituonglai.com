<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="no-margin"><?php echo _l('mapping_statistics'); ?></h4>
                        <hr class="hr-panel-heading" />
                        <div class="row">
                            <div class="col-md-12">
                                <div class="panel_s">
                                    <div class="panel-body text-center">
                                        <h2 class="text-primary"><?php echo number_format($total_mappings); ?></h2>
                                        <p class="text-muted"><?php echo _l('total_external_products'); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <h5><?php echo _l('statistics_by_mapping_type'); ?></h5>
                                <hr />
                            </div>
                        </div>
                        <div class="row">
                            <?php foreach ($statistics as $stat) { ?>
                                <div class="col-md-3 col-sm-6">
                                    <div class="panel_s">
                                        <div class="panel-body text-center">
                                            <h3 class="text-info"><?php echo number_format($stat['count']); ?></h3>
                                            <p class="text-muted">
                                                <span class="mapping-type-badge <?php echo html_escape($stat['mapping_type']); ?>">
                                                    <?php echo format_mapping_type($stat['mapping_type']); ?>
                                                </span>
                                            </p>
                                            <small class="text-muted">
                                                <?php echo round(($stat['count'] / max($total_mappings, 1)) * 100, 1); ?>% of total
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th><?php echo _l('mapping_type'); ?></th>
                                                <th><?php echo _l('count'); ?></th>
                                                <th><?php echo _l('percentage'); ?></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($statistics as $stat) { ?>
                                                <?php $percentage = round(($stat['count'] / max($total_mappings, 1)) * 100, 1); ?>
                                                <tr>
                                                    <td>
                                                        <span class="mapping-type-badge <?php echo html_escape($stat['mapping_type']); ?>">
                                                            <?php echo format_mapping_type($stat['mapping_type']); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo number_format($stat['count']); ?></td>
                                                    <td>
                                                        <div class="progress" style="height: 20px;">
                                                            <div class="progress-bar" role="progressbar" style="width: <?php echo $percentage; ?>%" aria-valuenow="<?php echo $percentage; ?>" aria-valuemin="0" aria-valuemax="100">
                                                                <?php echo $percentage; ?>%
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <a href="<?php echo admin_url('external_products/mapping'); ?>" class="btn btn-info">
                                    <i class="fa fa-list"></i> <?php echo _l('view_all_mappings'); ?>
                                </a>
                                <a href="<?php echo admin_url('external_products'); ?>" class="btn btn-default">
                                    <i class="fa fa-arrow-left"></i> <?php echo _l('back_to_external_products'); ?>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
