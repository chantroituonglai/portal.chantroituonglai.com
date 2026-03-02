<h4 class="tw-font-semibold tw-text-lg tw-text-neutral-700 tw-mb-4">
    <i class="fa fa-chart-line"></i> <?php echo _l('progress_overview'); ?>
</h4>
<div class="progress-line">
    <div class="progress-line-fill" style="width: <?php 
        $completed = 0;
        foreach($topic_steps as $step) {
            if($step['state_color']) $completed++;
        }
        echo ($completed / count($topic_steps) * 100) . '%';
    ?>"></div>
</div>
    
<div id="progress-steps-container" class="progress-steps">
    <div class="progress-steps-container">
        <?php 
        $parent_count = count(array_filter($topic_steps, function($step) { 
            return !$step['parent_id']; 
        }));
        ?>
        
        <div class="progress-steps-grid" style="grid-template-columns: repeat(<?php echo $parent_count; ?>, 1fr);">
            <?php foreach($topic_steps as $index => $step): ?>
                <?php if (!$step['parent_id']): ?>
                    <div class="progress-column">
                        <!-- Parent Step -->
                        <div class="progress-step parent-step <?php echo $step['state_color'] ? 'completed' : ''; ?>">
                            <div class="step-content">
                                <!-- Phần tiêu đề -->
                                <div class="step-title">
                                    <?php echo $step['name']; ?>
                                </div>
                                
                                <!-- Phần trạng thái -->
                                <?php if($step['state_name']): ?>
                                    <div class="step-status" style="background-color: <?php echo $step['state_color'] ?: '#f1f1f1'; ?>">
                                        <span class="status-text">
                                            <?php echo $step['state_name']; ?>
                                        </span>
                                        <div class="line"></div>
                                        <?php if($step['state_color']): ?>
                                            <div class="step-actions">
                                                <a href="#" class="show-log-popup" 
                                                   data-toggle="modal" 
                                                   data-target="#logModal"
                                                   data-topicid="<?php echo $topic->topicid; ?>"
                                                   data-action-type="<?php echo $step['action_type_code']; ?>">
                                                    <i class="fa fa-file-text-o"></i> <?php echo _l('log'); ?>
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Child Steps Container -->
                        <div class="child-steps-container">
                            <?php 
                            $children = array_filter($topic_steps, function($child) use ($step) {
                                return $child['parent_id'] == $step['id'];
                            });
                            foreach($children as $child_index => $child): ?>
                                <div class="progress-step child-step <?php 
                                    if($child['state_color']) {
                                        echo 'completed';
                                    } else if($child_index == $completed) {
                                        echo 'in-progress';
                                    }
                                ?>">
                                    <div class="child-connector"></div>
                                    <div class="step-content">
                                        <div class="progress-step-icon" style="<?php 
                                            if($child['state_color']) {
                                                echo 'background-color: ' . $child['state_color'];
                                            }
                                        ?>">
                                            <?php if($child['state_color']): ?>
                                                <i class="fa fa-check"></i>
                                            <?php else: ?>
                                                <?php echo $child_index + 1; ?>
                                            <?php endif; ?>
                                        </div>
                                        <div class="progress-step-details">
                                            <div class="progress-step-label">
                                                <span class="child-indicator">└─</span>
                                                <?php echo $child['name']; ?>
                                            </div>
                                            <?php if($child['state_name']): ?>
                                                <div class="progress-step-status">
                                                    <span class="status-label" style="background-color: <?php echo $child['state_color'] ?: '#f1f1f1'; ?>">
                                                        <?php echo $child['state_name']; ?>
                                                    </span>
                                                </div>
                                            <?php endif; ?>
                                            <?php if($child['state_color']): ?>
                                                <div class="progress-step-actions">
                                                    <a href="#" class="show-log-popup" 
                                                       data-toggle="modal" 
                                                       data-target="#logModal"
                                                       data-topicid="<?php echo $topic->topicid; ?>"
                                                       data-action-type="<?php echo $child['action_type_code']; ?>">
                                                        <i class="fa fa-file-text-o"></i> <?php echo _l('log'); ?>
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                            <?php if($child['valid_data']): ?>
                                                <div class="step-actions">
                                                    <a href="<?php echo admin_url('topics/process_data/'. $topic->id.'/'.$child['action_type_code']); ?>" 
                                                    class="process-data-btn"
                                                    data-toggle="tooltip"
                                                    title="<?php echo _l('process_data'); ?>">
                                                        <i class="fa fa-cogs"></i> <?php echo _l('process_data'); ?>
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
function getContrastColor(hexcolor) {
    // Remove # if present
    hexcolor = hexcolor.replace("#", "");
    
    // Convert to RGB
    var r = parseInt(hexcolor.substr(0,2),16);
    var g = parseInt(hexcolor.substr(2,2),16);
    var b = parseInt(hexcolor.substr(4,2),16);
    
    // Calculate luminance
    var yiq = ((r*299)+(g*587)+(b*114))/1000;
    
    // Return black or white depending on background
    return (yiq >= 128) ? '#000000' : '#ffffff';
}

// Áp dụng màu text tự động cho tất cả step status
document.querySelectorAll('.parent-step .step-status').forEach(function(statusEl) {
    var bgColor = statusEl.style.backgroundColor;
    var textColor = getContrastColor(bgColor);
    statusEl.style.color = textColor;
    
    // Áp dụng màu cho cả action link
    var actionLink = statusEl.querySelector('.step-actions a');
    if (actionLink) {
        actionLink.style.color = textColor;
    }
});
</script>