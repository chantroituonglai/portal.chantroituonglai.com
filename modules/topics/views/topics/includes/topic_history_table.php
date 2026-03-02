<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="table-responsive">
    <table class="table table-striped table-bordered" id="topic-history-table">
        <thead>
            <tr>
                <th><?php echo _l('action_type'); ?></th>
                <th><?php echo _l('action_state'); ?></th>
                <th><?php echo _l('log'); ?></th>
                <th><?php echo _l('updated_date'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($topic_history as $history_item) { 
                // echo $history_item['execution_html'];
            ?>
            <tr>
                <td><?php echo html_escape($history_item['action_type_name']); ?> - <span style="font-size: 9px;"><?php echo html_escape($history_item['action_type_code']); ?></span></td>
                <td>
                    <span class="label" style="background-color: <?php echo html_escape($history_item['state_color']); ?>">
                        <?php echo html_escape($history_item['action_state_name']); ?>
                    </span>
                    <span style="font-size: 9px;"><?php echo html_escape($history_item['action_state_code']); ?></span>
                </td>
                <td class="action-links">
                    <a href="#" class="show-log-popup" 
                       data-toggle="modal" 
                       data-target="#logModal"
                       data-topicid="<?php echo $history_item['topicid']; ?>"
                       data-id="<?php echo $history_item['id']; ?>">
                        <i class="fa fa-file-text-o"></i> <?php echo _l('view_log'); ?>
                    </a>
                    <?php if (isset($history_item['valid_data']) && $history_item['valid_data'] == 1): ?>
                    <a href="#" class="show-processed-data ml-2" 
                       data-toggle="modal" 
                       data-target="#processedDataModal"
                       data-topicid="<?php echo $history_item['topicid']; ?>"
                       data-id="<?php echo $history_item['id']; ?>">
                        <i class="fa fa-database"></i> <?php echo _l('view_processed_data'); ?>
                    </a>
                    <?php endif; ?>
                    <?php if ($history_item['execution_html']): ?>
                        <?php echo $history_item['execution_html']; ?>
                    <?php endif; ?>
                </td>
                <td><?php echo _dt($history_item['dateupdated']); ?></td>
            </tr>
            <?php } ?>
        </tbody>
    </table>
</div> 