<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="page-header">
                    <div class="row">
                        <div class="col-md-6">
                            <h4 class="page-title">
                                <i class="fa fa-history"></i> <?php echo _l('conversation_history'); ?>
                                <?php if ($project_id): ?>
                                    <small class="text-muted">- <?php echo _l('project'); ?> #<?php echo $project_id; ?></small>
                                <?php endif; ?>
                            </h4>
                        </div>
                        <div class="col-md-6 text-right">
                            <a href="<?php echo admin_url('project_agent/ai_room'); ?>" class="btn btn-primary">
                                <i class="fa fa-plus"></i> <?php echo _l('new_conversation'); ?>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="panel_s">
                    <div class="panel-body">
                        <?php if (empty($sessions)): ?>
                            <div class="text-center py-5">
                                <div class="mb-4">
                                    <i class="fa fa-comments fa-4x text-muted"></i>
                                </div>
                                <h4 class="text-muted"><?php echo _l('no_conversations_found'); ?></h4>
                                <p class="text-muted"><?php echo _l('start_conversation_message'); ?></p>
                                <a href="<?php echo admin_url('project_agent/ai_room'); ?>" class="btn btn-primary">
                                    <i class="fa fa-plus"></i> <?php echo _l('start_new_conversation'); ?>
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($sessions as $session): ?>
                                    <div class="col-md-6 col-lg-4 mb-4">
                                        <div class="card session-card h-100" onclick="loadSessionHistory(<?php echo $session['session_id']; ?>)">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-start mb-3">
                                                    <h6 class="card-title mb-0">
                                                        <?php echo _l('session'); ?> #<?php echo $session['session_id']; ?>
                                                    </h6>
                                                    <small class="text-muted">
                                                        <?php echo _d(date('Y-m-d', strtotime($session['created_at']))); ?>
                                                    </small>
                                                </div>

                                                <?php if (!empty($session['project_name'])): ?>
                                                    <div class="mb-2">
                                                        <small class="text-info">
                                                            <i class="fa fa-folder"></i> <?php echo html_escape($session['project_name']); ?>
                                                        </small>
                                                    </div>
                                                <?php endif; ?>

                                                <?php if (isset($session['last_message']) && !empty($session['last_message']['text'])): ?>
                                                    <div class="mb-3">
                                                        <small class="text-muted">
                                                            <?php echo html_escape(substr($session['last_message']['text'], 0, 100)); ?>
                                                            <?php if (strlen($session['last_message']['text']) > 100): ?>...<?php endif; ?>
                                                        </small>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="mb-3">
                                                        <small class="text-muted"><?php echo _l('no_messages_yet'); ?></small>
                                                    </div>
                                                <?php endif; ?>

                                                <div class="d-flex justify-content-between align-items-center">
                                                    <small class="text-muted">
                                                        <i class="fa fa-clock-o"></i>
                                                        <?php echo date('H:i', strtotime($session['created_at'])); ?>
                                                    </small>
                                                    <span class="badge badge-light">
                                                        <?php echo isset($session['conversation_summary']) ? count($session['conversation_summary']) : 0; ?> <?php echo _l('entries'); ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.session-card {
    cursor: pointer;
    transition: all 0.2s ease;
    border: 1px solid #e3e6f0;
    border-radius: 0.375rem;
}

.session-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    border-color: #007bff;
}

.session-card .card-body {
    padding: 1.25rem;
}

.session-card .card-title {
    color: #5a5c69;
    font-size: 0.9rem;
    font-weight: 600;
}

.session-card .card-text {
    font-size: 0.8rem;
    line-height: 1.4;
}
</style>

<script>
function loadSessionHistory(sessionId) {
    // Redirect to AI Room with session ID
    var url = admin_url + 'project_agent/ai_room';
    if (<?php echo $project_id ? 'true' : 'false'; ?>) {
        url += '?project_id=<?php echo $project_id; ?>';
    }
    url += (url.indexOf('?') > -1 ? '&' : '?') + 'session_id=' + sessionId;

    window.location.href = url;
}
</script>

<?php init_tail(); ?>
