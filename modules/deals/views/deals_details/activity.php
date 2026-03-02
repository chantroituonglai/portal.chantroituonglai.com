<?php
$activity_log = $this->leads_model->get_lead_activity_log($id);

?>
<div>
    <div class="activity-feed">
        <?php foreach ($activity_log as $log) { ?>
            <div class="feed-item">
                <div class="date">
                    <span class="text-has-action" data-toggle="tooltip" data-title="<?php echo _dt($log['date']); ?>">
                        <?php echo time_ago($log['date']); ?>
                    </span>
                </div>
                <div class="text">
                    <?php if ($log['staffid'] != 0) { ?>
                        <a href="<?php echo admin_url('profile/' . $log['staffid']); ?>">
                            <?php echo staff_profile_image($log['staffid'], ['staff-profile-xs-image pull-left mright5']);
                            ?>
                        </a>
                        <?php
                    }
                    $additional_data = '';
                    if (!empty($log['additional_data'])) {
                        $additional_data = unserialize($log['additional_data']);
                        echo ($log['staffid'] == 0) ? _l($log['description'], $additional_data) : $log['full_name'] . ' - ' . _l($log['description'], $additional_data);
                    } else {
                        echo $log['full_name'] . ' - ';
                        if ($log['custom_activity'] == 0) {
                            echo _l($log['description']);
                        } else {
                            echo _l($log['description'], '', false);
                        }
                    }
                    ?>
                </div>
            </div>
        <?php } ?>
    </div>
    <div class="col-md-12">
        <?php echo render_textarea('lead_activity_textarea', '', '', ['placeholder' => _l('enter_activity')], [], 'mtop15'); ?>
        <div class="text-right">
            <button id="lead_enter_activity" class="btn btn-primary"><?php echo _l('submit'); ?></button>
        </div>
    </div>
    <div class="clearfix"></div>
</div>