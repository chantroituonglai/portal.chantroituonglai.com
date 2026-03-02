<?php
defined('BASEPATH') or exit('No direct script access allowed');

$sessions = isset($sessions) ? $sessions : [];
$project_id = isset($project_id) ? $project_id : null;
?>

<div class="project-agent-conversation-history">
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fa fa-history"></i> Conversation History
                <?php if ($project_id): ?>
                    <small class="text-muted">- Project #<?php echo $project_id; ?></small>
                <?php endif; ?>
            </h5>
        </div>
        <div class="card-body">
            <?php if (empty($sessions)): ?>
                <div class="text-center text-muted py-4">
                    <i class="fa fa-comments fa-3x mb-3"></i>
                    <h6>No conversations found</h6>
                    <p>Start a new conversation in the AI Room to see your history here.</p>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($sessions as $session): ?>
                        <div class="col-md-6 col-lg-4 mb-3">
                            <div class="card session-card h-100" onclick="loadSessionHistory(<?php echo $session['session_id']; ?>)">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h6 class="card-title mb-0">
                                            Session #<?php echo $session['session_id']; ?>
                                        </h6>
                                        <small class="text-muted">
                                            <?php echo date('M j, Y', strtotime($session['created_at'])); ?>
                                        </small>
                                    </div>
                                    
                                    <?php if (!empty($session['project_name'])): ?>
                                        <p class="card-text">
                                            <small class="text-info">
                                                <i class="fa fa-folder"></i> <?php echo html_escape($session['project_name']); ?>
                                            </small>
                                        </p>
                                    <?php endif; ?>
                                    
                                    <?php if (isset($session['last_message']) && !empty($session['last_message']['text'])): ?>
                                        <p class="card-text">
                                            <small class="text-muted">
                                                <?php echo html_escape(substr($session['last_message']['text'], 0, 100)); ?>
                                                <?php if (strlen($session['last_message']['text']) > 100): ?>...<?php endif; ?>
                                            </small>
                                        </p>
                                    <?php else: ?>
                                        <p class="card-text">
                                            <small class="text-muted">No messages yet</small>
                                        </p>
                                    <?php endif; ?>
                                    
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">
                                            <i class="fa fa-clock-o"></i> 
                                            <?php echo date('H:i', strtotime($session['created_at'])); ?>
                                        </small>
                                        <span class="badge badge-light">
                                            <?php echo isset($session['conversation_summary']) ? count($session['conversation_summary']) : 0; ?> entries
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

<style>
.session-card {
    cursor: pointer;
    transition: all 0.2s ease;
    border: 1px solid #e3e6f0;
}

.session-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    border-color: #007bff;
}

.session-card .card-body {
    padding: 1rem;
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
    if (window.PA_PROJECT_ID) {
        url += '?project_id=' + window.PA_PROJECT_ID;
    }
    url += '&session_id=' + sessionId;
    
    window.location.href = url;
}
</script>
