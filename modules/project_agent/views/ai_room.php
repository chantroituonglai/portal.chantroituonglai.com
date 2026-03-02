<?php
defined('BASEPATH') or exit('No direct script access allowed');

// Get project data
$project = isset($project) ? $project : null;
$session = isset($session) ? $session : null;
$actions = isset($actions) ? $actions : [];
$ai_available = isset($ai_available) ? $ai_available : false;
$memory_entries = isset($memory_entries) ? $memory_entries : [];
$project_context = isset($project_context) ? $project_context : null;
// Derive progress and task count robustly from context
$__pa_progress = 0;
$__pa_tasks_count = 0;
if ($project_context) {
	if (isset($project_context['progress'])) {
		$__pa_progress = (int)$project_context['progress'];
	} elseif (isset($project_context['project']) && isset($project_context['project']->progress)) {
		$__pa_progress = (int)$project_context['project']->progress;
	}
	if (isset($project_context['tasks_count'])) {
		$__pa_tasks_count = (int)$project_context['tasks_count'];
	} elseif (isset($project_context['tasks']) && is_array($project_context['tasks'])) {
		$__pa_tasks_count = count($project_context['tasks']);
	}
}
?>

<!-- SimpleMDE Markdown Editor CDN -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/simplemde/latest/simplemde.min.css">
<script src="https://cdn.jsdelivr.net/simplemde/latest/simplemde.min.js"></script>
<!-- Robust parsing/sanitizing/highlighting -->
<script src="https://cdn.jsdelivr.net/npm/dompurify@3.0.6/dist/purify.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/marked@12.0.1/marked.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/github.min.css" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>

<div class="project-agent-ai-room">
<script>
  window.PA_USER_ID = <?php echo (int)get_staff_user_id(); ?>;
  <?php if (isset($project) && isset($project->id)): ?>
  window.PA_PROJECT_ID = <?php echo (int)$project->id; ?>;
  <?php else: ?>
  window.PA_PROJECT_ID = null;
  <?php endif; ?>
</script>

<script>
// Safe helpers to defer jQuery usage when this view loads before footer
window.BM_jqReady = function(cb){
  if (window.jQuery) { try { window.jQuery(cb); } catch(e){ cb(window.jQuery); } return; }
  var tries = 0; (function wait(){ if (window.jQuery){ try { window.jQuery(cb); } catch(e){ cb(window.jQuery);} return;} if (tries++<120) setTimeout(wait,50); })();
};
window.BM_click = function(sel){ try { if (window.jQuery) window.jQuery(sel).trigger('click'); else { var el=document.querySelector(sel); if (el) el.click(); } } catch(e){} };
window.BM_modal = function(sel, action){ try { if (window.jQuery && window.jQuery.fn && window.jQuery.fn.modal) window.jQuery(sel).modal(action); else { var el=document.querySelector(sel); if (!el) return; el.style.display = (action==='show')?'block':'none'; } } catch(e){} };
// Provide a default chat state object to avoid ReferenceError before core binds
window.PA_CHAT_STATE = window.PA_CHAT_STATE || { isProcessing: false };
// Server active session cache
window.PA_SERVER_SESSION_ID = window.PA_SERVER_SESSION_ID || null;

// Fallback: ensure send button works even if jQuery handlers aren't bound yet
document.addEventListener('click', function(ev){
  var t = ev.target;
  if (!t) return;
  if (t.id === 'send-button' || (t.closest && t.closest('#send-button'))) {
    if (window.PA_CHAT_STATE && window.PA_CHAT_STATE.isProcessing) { ev.preventDefault(); return; }
    if (typeof window.sendMessage === 'function') {
      ev.preventDefault();
      console.log('[PA][send][fallback] Clicked send button');
      try { window.sendMessage(); } catch(e){}
    }
  }
}, true);

  // Fallback for Context Progress button when PA_IR is not ready
document.addEventListener('click', function(e){
  var btn = e.target && e.target.closest ? e.target.closest('#paOpenContextBtn') : null;
  if (!btn) return;
  // If runtime API exists, let it handle
  if (window.PA_IR && typeof PA_IR.openContextProgress === 'function') {
    return; // runtime will handle via onclick
  }
  e.preventDefault();
  var modalId = 'paContextFallback';
  var m = document.getElementById(modalId);
  if (!m) {
    m = document.createElement('div');
    m.id = modalId;
    m.className = 'modal fade';
    m.innerHTML = '<div class="modal-dialog modal-lg"><div class="modal-content">\
      <div class="modal-header">\
        <h5 class="modal-title"><i class="fa fa-database"></i> Context Progress</h5>\
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>\
      </div>\
      <div class="modal-body"><div id="paContextList" class="small text-muted">Đang tải...</div></div>\
    </div></div>';
    document.body.appendChild(m);
  }
  // Show modal
  try { if (window.jQuery && jQuery.fn && jQuery.fn.modal) { jQuery(m).modal('show'); } else { m.style.display = 'block'; } } catch(e){}

  var token = window.PA_LAST_CLIENT_TOKEN;
  if (!token) {
    var list = document.getElementById('paContextList');
    if (list) list.innerHTML = 'Chưa có tiến trình. Hãy gửi một tin nhắn để khởi tạo.';
    return;
  }
  var base = (typeof admin_url !== 'undefined' ? admin_url : '/admin/');
  fetch(base + 'project_agent/context_progress', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
    body: 'client_token=' + encodeURIComponent(token)
  }).then(function(r){ return r.json(); }).then(function(r){
    var list = document.getElementById('paContextList');
    if (!list) return;
    if (!r || !r.success) { list.innerHTML = 'Không tải được tiến trình.'; return; }
    var html = '<ul class="list-unstyled">';
    (r.events||[]).forEach(function(ev){ html += '<li><strong>' + (ev.event||'event') + ':</strong> ' + (ev.message||'') + '</li>'; });
    html += '</ul>';
    list.innerHTML = (r.events && r.events.length) ? html : 'Chưa có sự kiện.';
  }).catch(function(){ var list = document.getElementById('paContextList'); if (list) list.innerHTML = 'Lỗi mạng khi tải tiến trình.'; });
}, true);
// Fallback: Enter to send for textarea and CodeMirror even if jQuery not bound yet
document.addEventListener('keydown', function(ev){
  var t = ev.target;
  if (!t) return;
  var isComposerTextarea = (t.id === 'chat-input');
  var isCodeMirror = (t.classList && t.classList.contains('CodeMirror')) || (t.closest && t.closest('.CodeMirror'));
  if ((isComposerTextarea || isCodeMirror) && ev.key === 'Enter' && !ev.shiftKey) {
    if (window.PA_CHAT_STATE && window.PA_CHAT_STATE.isProcessing) { ev.preventDefault(); return; }
    if (typeof window.sendMessage === 'function') {
      ev.preventDefault();
      console.log('[PA][input][fallback] Enter pressed in composer');
      try { window.sendMessage(); } catch(e){}
    }
  }
}, true);
</script>

<script>
// Fallback: define global loadSessionHistory if assets not yet bound it
if (typeof window.loadSessionHistory !== 'function') {
  window.loadSessionHistory = function(sessionId){
    try {
      if (window.PA_IR && typeof window.PA_IR.loadSessionHistory === 'function') {
        return window.PA_IR.loadSessionHistory(sessionId);
      }
    } catch(e){}
    try {
      // Minimal inline loader as last resort (no jQuery dependency)
      var urlBase = (typeof admin_url !== 'undefined' ? admin_url : '/admin/');
      fetch(urlBase + 'project_agent/load_session_history', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
        body: 'session_id=' + encodeURIComponent(sessionId)
      }).then(function(r){ return r.json(); }).then(function(response){
        if (response && response.success) {
          var msgs = '';
          (response.conversation||[]).forEach(function(entry){
            var isUser = entry.kind === 'input';
            var avatar = isUser ? 'You' : 'AI';
            var bubbleClass = isUser ? 'user' : 'ai';
            var time = entry.created_at ? new Date(entry.created_at).toLocaleTimeString() : '';
            msgs += '<div class="message ' + bubbleClass + ' mb-2">'
                 +    '<div class="avatar">' + avatar + '</div>'
                 +    '<div class="bubble ' + bubbleClass + '">'
                 +      '<div class="message-content">' + (entry.text||'') + '</div>'
                 +      '<div class="msg-meta">' + time + '</div>'
                 +    '</div>'
                 +  '</div>';
          });
          var cm = document.getElementById('chat-messages'); if (cm) cm.innerHTML = msgs;
          try { document.querySelectorAll('.composer').forEach(function(el){ el.classList.remove('composer-hidden'); }); } catch(e){}
          // Set active session to the restored one and update badge
          try { 
            window.PA_DEBUG = window.PA_DEBUG || {}; 
            PA_DEBUG.session_id = sessionId; 
            var sEl = document.getElementById('composer-session'); 
            if (sEl) sEl.textContent = 'S#' + sessionId; 
          } catch(e){}
        }
      }).catch(function(){});
    } catch(e){}
  };
}
</script>
<!-- AI Assistant Status Banner - Only show when there's a meaningful status -->
<?php 
$ai_status = isset($ai_status) ? $ai_status : [];
$show_banner = false;
$banner_type = 'info';
$banner_message = '';

// Check if user has dismissed the banner
$banner_dismissed = isset($_COOKIE['pa_ai_banner_dismissed']) && $_COOKIE['pa_ai_banner_dismissed'] === '1';

// Only show when we have explicit status info
if (!$banner_dismissed && is_array($ai_status) && !empty($ai_status) && array_key_exists('installed', $ai_status)) {
    // Only show if we have meaningful status information
    $installed_status = $ai_status['installed'];
    if ($installed_status === true || $installed_status === 'true' || $installed_status === 1 || $installed_status === '1') {
        $show_banner = true;
        $banner_type = 'success';
        $banner_message = 'Connected to AI Provider';
    } elseif ($installed_status === false || $installed_status === 'false' || $installed_status === 0 || $installed_status === '0' || $installed_status === null || $installed_status === '') {
        $show_banner = true;
        $banner_type = 'warning';
        $banner_message = !empty($ai_status['message']) ? $ai_status['message'] : 'AI Provider not available';
    }
    // If $installed_status has any other value (like empty array, object, etc.), don't show banner
}

if ($show_banner && false): ?>
<div class="alert alert-<?php echo $banner_type; ?> alert-dismissible fade hide mb-3" role="alert">
    <div class="d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center">
            <i class="fa fa-<?php echo $banner_type === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> mr-2"></i>
            <strong>AI Assistant Status:</strong> 
            <span class="ml-1"><?php echo $banner_message; ?></span>
        </div>
        <div class="d-flex align-items-center">
            <button type="button" class="btn btn-sm btn-outline-<?php echo $banner_type; ?> mr-2" onclick="refreshAIStatus()">
                <i class="fa fa-refresh"></i> Refresh
            </button>
            <button type="button" class="close" data-dismiss="alert" onclick="dismissAIBanner()">
                <span>&times;</span>
            </button>

        </div>
    </div>
 </div>
<?php endif; ?>

<!-- Main Layout with Sliding Panel -->
<div class="chat-layout-container panel-hidden">
    <!-- Left Column: Chat Module -->
    <div class="chat-column">
        <div class="card chat-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">
                    <i class="fa fa-comments"></i> AI Assistant Chat
                </h6>
                <div class="btn-group">
                    <button class="btn btn-sm btn-outline-primary" id="restore-last-btn" title="Restore Last Session">
                        <i class="fa fa-undo"></i> Restore
                    </button>
                    <button class="btn btn-sm btn-outline-info" id="session-history-btn" title="Session History">
                        <i class="fa fa-history"></i> History
                    </button>
                    <button class="btn btn-sm btn-outline-success" id="action-logs-btn" title="Action Logs" data-session-id="<?php echo isset($session) && isset($session->session_id) ? $session->session_id : ''; ?>">
                        <i class="fa fa-list-alt"></i> Action Logs
                    </button>
                    <button class="btn btn-sm btn-outline-secondary" id="clear-chat-btn" title="Clear/Reset Chat">
                        <i class="fa fa-refresh"></i> Reset
                    </button>
                    <button class="btn btn-sm btn-outline-dark" id="toggle-panel-btn" title="Toggle Side Panel">
                        <i class="fa fa-bars"></i> Panel
                    </button>
                </div>
            </div>
            <div class="card-body p-0 chat-container">
                <div class="chat-messages" id="chat-messages">
                    <div class="welcome-message text-center text-muted py-3">
                        <i class="fa fa-comments fa-2x mb-2" style="color:#2563eb"></i>
                        <h6 class="mb-1" style="color:#0f172a;font-weight:600">Project Agent AI</h6>
                        <p class="mb-3 small" style="color:#475569">Ask me anything about your project</p>
                        <!-- Omnibox + Pills (scoped) -->
                        <div class="pa-omni">
                          <div class="pa-tip" id="paTip" style="background:#e0f2fe;color:#0c4a6e;border-color:#7dd3fc">
                            <strong style="color:#0369a1">New</strong> Dùng Agent để search, phân tích, tạo task, báo cáo.
                            <a href="#" onclick="return false;" style="color:#0369a1;text-decoration:underline">Learn more</a>
                            <button class="pa-tip-close" onclick="document.getElementById('paTip').remove()">×</button>
                            <span class="pa-tip-caret"></span>
                          </div>

                          <div class="pa-box">
                            <button class="pa-mini" title="Actions"><i class="fa fa-bolt"></i></button>
                            <input id="paOmniInput" class="pa-input" type="text"
                                   placeholder="Bạn muốn mình giúp gì cho dự án này?">
                          </div>

                          <!-- cta row removed to avoid duplication -->

                          <div class="pa-suggest-row">
                            <button id="paOpenContextBtn" class="pa-chip" onclick="PA_IR.openContextProgress();return false;"><i class="fa fa-database"></i> Context progress</button>
                            <button class="pa-chip" onclick="quickAction('search')"><i class="fa fa-search"></i> Quick search</button>
                            <button class="pa-chip" onclick="quickAction('summary')"><i class="fa fa-chart-line"></i> Summarize work</button>
                            <button class="pa-chip" onclick="quickAction('billing')"><i class="fa fa-file-invoice-dollar"></i> Billing overview</button>
                            <button class="pa-chip" onclick="quickAction('task')"><i class="fa fa-plus"></i> Create task</button>
                            <button class="pa-chip" onclick="quickAction('plan')"><i class="fa fa-map"></i> Make a plan</button>
                          </div>
                        </div>
                        <!-- quick-actions removed to avoid duplication with pa-cta-row; keep a single primary action row -->
                    </div>
                        </div>
                    </div>
                </div>
                <!-- Fixed Bottom Chat Composer -->
            <!-- Premium Chat Composer - Elon Musk Standard Design -->
            <div class="chat-composer composer composer-hidden">
                <div class="composer-container">
                    <!-- Composer Header with Quick Actions -->
                    <div class="composer-header">
                        <div class="composer-actions">
                            <button class="composer-action-btn" id="composer-attach-btn" title="Attach file" disabled>
                                    <i class="fa fa-paperclip"></i>
                                </button>
                            <button class="composer-action-btn" id="composer-voice-btn" title="Voice input">
                                <i class="fa fa-microphone"></i>
                                </button>
                            </div>
                        <div class="composer-status">
                            <span class="status-indicator" id="composer-status">
                                <i class="fa fa-circle"></i>
                                Ready
                            </span>
                        </div>
                    </div>

                    <!-- Main Input Area -->
                    <div class="composer-input-area">
                        <div class="input-wrapper">
            <textarea
                class="composer-input markdown-editor"
                id="chat-input"
                placeholder="Ask me anything about your project..."
                rows="1"
                maxlength="4000"
                <?php echo !$ai_available ? 'disabled' : ''; ?>
            ></textarea>
                            <div class="input-actions">
                                <button class="input-action-btn" id="composer-clear-btn" title="Clear">
                                    <i class="fa fa-times"></i>
                                </button>
                                <div class="character-count">
                                    <span id="char-count">0</span>/4000
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Composer Footer -->
                    <div class="composer-footer">
                        <div class="composer-suggestions" id="composer-suggestions">
                            <button class="suggestion-chip" data-action="billing">
                                <i class="fa fa-search"></i>
                                Search
                            </button>
                            <button class="suggestion-chip" data-action="summary">
                                <i class="fa fa-chart-line"></i>
                                Analyze
                            </button>
                            <button class="suggestion-chip" data-action="task">
                                <i class="fa fa-plus"></i>
                                Create Task
                            </button>
                            <button class="suggestion-chip" data-action="plan">
                                <i class="fa fa-map"></i>
                                Plan
                            </button>
                        </div>

                        <div class="composer-controls">
                            <button class="composer-control-btn composer-stop-btn" id="stop-button" style="display: none;">
                                    <i class="fa fa-stop"></i>
                                <span>Stop</span>
                                </button>
                            <button type="button" class="composer-control-btn composer-send-btn" id="send-button" <?php echo !$ai_available ? 'disabled' : ''; ?>>
                                <i class="fa fa-paper-plane"></i>
                                <span>Send</span>
                                </button>
                            </div>
                        </div>
                        </div>

                <!-- Typing Indicator -->
                <div id="typing" class="composer-typing" style="display:none;">
                    <div class="typing-indicator">
                        <span class="typing-dot"></span>
                        <span class="typing-dot"></span>
                        <span class="typing-dot"></span>
                    </div>
                    <span class="typing-text">AI is thinking...</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Sliding Panel (Context | Actions | Memory) -->
    <div class="side-panel" id="side-panel">
        <div class="panel_s">
            <div class="panel-body">
                <ul class="nav nav-tabs pa-tabs" role="tablist">
                    <li role="presentation" class="active"><a href="#pa-tab-context" aria-controls="pa-tab-context" role="tab" data-toggle="tab"><i class="fa fa-info-circle"></i> Context</a></li>
                    <li role="presentation"><a href="#pa-tab-actions" aria-controls="pa-tab-actions" role="tab" data-toggle="tab"><i class="fa fa-cogs"></i> Actions</a></li>
                    <li role="presentation"><a href="#pa-tab-memory" aria-controls="pa-tab-memory" role="tab" data-toggle="tab"><i class="fa fa-history"></i> Memory</a></li>
                </ul>
                <div class="tab-content pa-tab-content">
                    <!-- Context -->
                    <div role="tabpanel" class="tab-pane active" id="pa-tab-context">
                        <div class="tw-flex tw-justify-between tw-items-center mb-2">
                            <h4 class="mb-0">Project Context</h4>
                            <button id="context-toggle-btn" class="btn btn-default btn-sm pull-right" onclick="toggleContextDetails()" aria-label="Toggle context details"><i id="context-toggle-icon" class="fa fa-chevron-down"></i></button>
                        </div>
                        <?php if ($project): ?>
                        <div class="project-info">
                            <h6 class="text-primary mb-2"><?php echo $project->name; ?></h6>
                            <div class="progress mb-2" style="height: 6px;">
                                <div class="progress-bar" role="progressbar" style="width: <?php echo $__pa_progress; ?>%"></div>
                            </div>
                            <small class="text-muted">Progress: <?php echo $__pa_progress; ?>%</small>
                            <div id="context-details" class="mt-3" style="display: none;">
                                <hr class="my-2">
                                <div class="context-section">
                                    <strong>Project Details:</strong>
                                    <ul class="list-unstyled small mt-1">
                                        <li><i class="fa fa-calendar"></i> Start: <?php echo isset($project->start_date) ? _d($project->start_date) : 'Not set'; ?></li>
                                        <li><i class="fa fa-flag-checkered"></i> Deadline: <?php echo isset($project->deadline) ? _d($project->deadline) : 'Not set'; ?></li>
                                        <li><i class="fa fa-user"></i> Manager: <?php echo isset($project->project_manager) ? get_staff_full_name($project->project_manager) : (isset($project->teamleader) ? get_staff_full_name($project->teamleader) : 'Not assigned'); ?></li>
                                        <li><i class="fa fa-tasks"></i> Tasks: <?php echo $__pa_tasks_count; ?></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="text-center text-muted">
                            <i class="fa fa-folder-open fa-2x mb-2"></i>
                            <p class="small">No project selected</p>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Actions -->
                    <div role="tabpanel" class="tab-pane" id="pa-tab-actions">
                        <h4 class="mb-3">Available Actions</h4>
                        <div class="action-search mb-3">
                            <div class="input-group input-group-sm">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fa fa-search"></i></span>
                                </div>
                                <input type="text" id="action-filter" class="form-control" placeholder="Search actions...">
                            </div>
                        </div>
                        <div class="action-list">
                            <?php if (empty($actions)): ?>
                                <div class="text-muted small p-2">No actions available for your permissions.</div>
                            <?php else: foreach ($actions as $action_id => $action): ?>
                                <div class="action-item mb-2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="flex-grow-1">
                                            <div class="action-name small font-weight-bold"><?php echo $action['name']; ?></div>
                                            <div class="action-desc small text-muted">
                                                <?php echo $action['description']; ?>
                                                <?php $ok = !isset($action['permission_ok']) || $action['permission_ok']; if (!$ok): ?>
                                                    <span class="badge badge-light ml-1" title="Insufficient permission">No permission</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <button class="btn btn-sm btn-outline-success ml-2" aria-label="Run <?php echo htmlspecialchars($action['name']); ?>" onclick="executeAction('<?php echo $action_id; ?>')" title="Run <?php echo $action['name']; ?>" <?php echo (!$ok ? 'disabled' : ''); ?>>
                                            <i class="fa fa-play"></i>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; endif; ?>
                        </div>
                    </div>

                    <!-- Memory -->
                    <div role="tabpanel" class="tab-pane" id="pa-tab-memory">
                        <!-- Memory Header -->
                        <div class="memory-header">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <h5 class="mb-1">
                                        <i class="fa fa-brain text-primary mr-2"></i>
                                        Memory Timeline
                                    </h5>
                                    <small class="text-muted">Conversation history and context</small>
                        </div>
                                <div class="memory-actions">
                                    <button id="memory-refresh" type="button" class="btn btn-sm btn-outline-secondary" title="Refresh">
                                        <i class="fa fa-refresh"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Memory Controls -->
                        <div class="memory-controls">
                            <!-- Top Row: Search + Settings -->
                            <div class="controls-row controls-top">
                                <!-- Search Section -->
                                <div class="search-section">
                                    <div class="search-input-wrapper">
                                        <div class="search-icon">
                                            <i class="fa fa-search"></i>
                                        </div>
                                        <input id="memory-search" type="text"
                                               class="search-input"
                                               placeholder="Search memories...">
                                        <button class="filter-toggle-btn" type="button"
                                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            <i class="fa fa-sliders-h"></i>
                                        </button>
                                        <div class="filter-dropdown-menu">
                                            <div class="filter-header">
                                                <span class="filter-title">Filter by Type</span>
                                            </div>
                                            <div class="filter-options">
                                                <button class="filter-option active" data-filter="all" onclick="setMemoryFilter('all')">
                                                    <i class="fa fa-list"></i>
                                                    <span>All Types</span>
                                                </button>
                                                <button class="filter-option" data-filter="input" onclick="setMemoryFilter('input')">
                                                    <i class="fa fa-user"></i>
                                                    <span>User Input</span>
                                                </button>
                                                <button class="filter-option" data-filter="ai_response" onclick="setMemoryFilter('ai_response')">
                                                    <i class="fa fa-robot"></i>
                                                    <span>AI Response</span>
                                                </button>
                                                <button class="filter-option" data-filter="action_call" onclick="setMemoryFilter('action_call')">
                                                    <i class="fa fa-play-circle"></i>
                                                    <span>Action Call</span>
                                                </button>
                                                <button class="filter-option" data-filter="action_result" onclick="setMemoryFilter('action_result')">
                                                    <i class="fa fa-check-circle"></i>
                                                    <span>Action Result</span>
                                                </button>
                                                <button class="filter-option" data-filter="note" onclick="setMemoryFilter('note')">
                                                    <i class="fa fa-sticky-note"></i>
                                                    <span>Notes</span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Settings Section -->
                                <div class="settings-section">
                                    <div class="setting-group">
                                        <label class="setting-label">Auto Refresh</label>
                                        <select id="memory-auto" class="setting-select">
                                            <option value="0">Off</option>
                                            <option value="15000">15s</option>
                                            <option value="30000" selected>30s</option>
                                    </select>
                                </div>
                                    <div class="setting-group">
                                        <label class="setting-label">Sort</label>
                                        <select id="memory-sort" class="setting-select">
                                            <option value="desc" selected>Newest</option>
                                            <option value="asc">Oldest</option>
                                    </select>
                                </div>
                                </div>
                            </div>

                            <!-- Bottom Row: Actions -->
                            <div class="controls-row controls-bottom">
                                <div class="actions-section">
                                    <!-- Selection Actions -->
                                    <div class="action-group selection-actions">
                                        <button id="select-all-memory" type="button" class="action-btn action-btn-secondary">
                                            <i class="fa fa-check-square"></i>
                                            <span>Select All</span>
                                        </button>
                                        <button id="clear-memory-selection" type="button" class="action-btn action-btn-secondary">
                                            <i class="fa fa-square"></i>
                                            <span>Clear</span>
                                        </button>
                                </div>

                                    <!-- Primary Action -->
                                    <div class="action-group primary-action">
                                        <button id="send-memory-chain" type="button" class="action-btn action-btn-primary">
                                            <i class="fa fa-paper-plane"></i>
                                            <span>Send Chain</span>
                                        </button>
                            </div>

                                    <!-- Utility Actions -->
                                    <div class="action-group utility-actions">
                                        <button id="memory-refresh" type="button" class="action-btn action-btn-ghost">
                                            <i class="fa fa-sync-alt"></i>
                                        </button>
                        </div>
                        </div>
                        </div>

                            <!-- Hidden filter input for compatibility -->
                            <input type="hidden" id="memory-filter" value="all">
                        </div>

                        <!-- Memory Timeline -->
                        <div class="memory-timeline-container">
                            <div id="memory-timeline" class="memory-timeline"></div>

                            <!-- Empty State -->
                            <div id="memory-empty" class="memory-empty-state" style="display:none;">
                                <div class="empty-state-content">
                                    <div class="empty-icon">
                                        <i class="fa fa-brain"></i>
                    </div>
                                    <h6>No Memories Yet</h6>
                                    <p class="text-muted small">Start a conversation to build your memory timeline</p>
                                    <div class="empty-actions">
                                        <button class="btn btn-sm btn-outline-primary" onclick="createNewSession(); BM_click('#panel-overlay');">
                                            <i class="fa fa-plus mr-1"></i>Start New Chat
                                        </button>
                </div>
            </div>
        </div>

                            <!-- Loading State -->
                            <div id="memory-loading" class="memory-loading-state" style="display:none;">
                                <div class="loading-content">
                                    <div class="loading-spinner">
                                        <i class="fa fa-circle-o-notch fa-spin"></i>
    </div>
                                    <p class="text-muted small">Loading memories...</p>
                                </div>
                            </div>

                            <!-- Error State -->
                            <div id="memory-error" class="memory-error-state" style="display:none;">
                                <div class="error-content">
                                    <div class="error-icon">
                                        <i class="fa fa-exclamation-triangle text-warning"></i>
                                    </div>
                                    <h6>Failed to Load</h6>
                                    <p class="text-muted small">Unable to load memory entries</p>
                                    <button id="memory-retry" class="btn btn-sm btn-outline-secondary">
                                        <i class="fa fa-refresh mr-1"></i>Try Again
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Memory Stats -->
                        <div class="memory-stats mt-3 pt-3 border-top">
                            <div class="stats-grid">
                                <div class="stat-item">
                                    <div class="stat-value" id="memory-count">0</div>
                                    <div class="stat-label small text-muted">Total</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-value" id="memory-selected">0</div>
                                    <div class="stat-label small text-muted">Selected</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-value" id="memory-recent">0</div>
                                    <div class="stat-label small text-muted">Last 24h</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Panel Overlay -->
    <div class="panel-overlay" id="panel-overlay"></div>
</div>

<!-- Parameter Editor Modal -->
<div class="modal fade" id="parameterEditorModal" tabindex="-1" role="dialog" aria-labelledby="parameterEditorModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="parameterEditorModalLabel">
                    <i class="fa fa-cogs"></i> Configure Action Parameters
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="fa fa-info-circle"></i> 
                    <strong>Action:</strong> <span id="action-name-display"></span>
                </div>
                <div class="alert alert-warning" id="parameter-warnings" style="display: none;">
                    <i class="fa fa-exclamation-triangle"></i> 
                    <span id="warning-message"></span>
                </div>
                
                <form id="parameter-form">
                    <div id="dynamic-form-container">
                        <!-- Dynamic form fields will be rendered here -->
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fa fa-times"></i> Cancel
                </button>
                <button type="button" class="btn btn-primary" id="execute-with-params">
                    <i class="fa fa-play"></i> Execute Action
                </button>
            </div>
        </div>
    </div>
</div>

<script>
(function bindWhenJqueryReady(){
    setTimeout(initializeChatLayout, 100);
    if (!window.jQuery) { setTimeout(bindWhenJqueryReady, 60); return; }
    var $ = window.jQuery;
    
    // Initialize chat functionality
    initializeChat();
    
    // Action search filter
    $('#action-filter').on('input', function(){
        var q = ($(this).val()||'').toLowerCase();
        $('.action-list .action-item').each(function(){
            var text = $(this).text().toLowerCase();
            $(this).toggle(text.indexOf(q) !== -1);
        });
    });
    
    // Load initial data
    loadMemory();
    // Initialize auto-refresh based on current selection
    try { setMemoryAuto($('#memory-auto').val()); } catch(e) {}

    // Tab show/hide: only auto-refresh memory when Memory tab is active
    $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
        var target = $(e.target).attr('href');
        if (target === '#pa-tab-memory') {
            try { setMemoryAuto($('#memory-auto').val()); } catch(e) {}
            loadMemory();
        } else {
            try { setMemoryAuto(0); } catch(e) {}
        }
    });

    // Debug: expose server-provided context to console
    try {
        window.PA_DEBUG = window.PA_DEBUG || {};
        PA_DEBUG.project = <?php echo isset($project) ? json_encode($project) : 'null'; ?>;
        PA_DEBUG.project_context = <?php echo isset($project_context) ? json_encode($project_context) : 'null'; ?>;
        PA_DEBUG.actions_count = <?php echo isset($actions) ? count($actions) : 0; ?>;
        PA_DEBUG.ai_available = <?php echo $ai_available ? 'true' : 'false'; ?>;
        PA_DEBUG.session_id = '<?php echo isset($session) ? $session->session_id : ''; ?>';
        PA_DEBUG.session_object = <?php echo isset($session) ? json_encode($session) : 'null'; ?>;

        // Store current session ID in localStorage for Action Logs modal
        if (PA_DEBUG.session_id) {
            try {
                localStorage.setItem('pa_recent_session_id', PA_DEBUG.session_id);
                console.log('AI Room - Stored session ID in localStorage:', PA_DEBUG.session_id);
            } catch(e) {
                console.log('AI Room - Could not store session ID in localStorage');
            }
        }
        console.log('[PA][Context] Server-provided context:', PA_DEBUG);
        console.log('[PA][Context] Session object:', PA_DEBUG.session_object);
        console.log('[PA][Context] Session ID from object:', PA_DEBUG.session_id);
    } catch (e) { console.warn('[PA] Failed to expose debug context', e); }

    // Define toggleContextDetails function within jQuery scope
    window.toggleContextDetails = function() {
        $('#context-details').slideToggle();
        $('#context-toggle-icon').toggleClass('fa-chevron-down fa-chevron-up');
    };

    // Initialize composer session badge
    try {
        var initSid = (window.PA_DEBUG && PA_DEBUG.session_id) ? PA_DEBUG.session_id : '<?php echo isset($session) ? $session->session_id : '' ;?>';
        if (initSid) { var el = document.getElementById('composer-session'); if (el) el.textContent = 'S#' + initSid; }
    } catch(e){}
    // Fetch server active session to sync
    try {
      var base = (typeof admin_url !== 'undefined' ? admin_url : '/admin/');
      fetch(base + 'project_agent/get_active_session').then(r=>r.json()).then(function(r){
        if (r && r.success && r.session_id) {
          window.PA_SERVER_SESSION_ID = r.session_id;
          window.PA_DEBUG = window.PA_DEBUG || {}; PA_DEBUG.session_id = PA_DEBUG.session_id || r.session_id;
          var el = document.getElementById('composer-session'); if (el) el.textContent = 'S#' + (PA_DEBUG.session_id||r.session_id);
        }
      }).catch(function(){});
    } catch(e){}

    // Handle execute with parameters
    $(document).on('click', '#execute-with-params', function() {
        var actionId = $('#parameterEditorModal').data('action-id');
        var params = collectFormData();
        console.log('[PA][execute]', actionId, params);
        
        $('#parameterEditorModal').modal('hide');
        
        // Execute action with parameters
        $.ajax({
            url: '<?php echo admin_url("project_agent/execute_action"); ?>',
            type: 'POST',
            data: { 
                action_id: actionId,
                params: JSON.stringify(params),
                session_id: (window.PA_DEBUG && PA_DEBUG.session_id) ? PA_DEBUG.session_id : '<?php echo isset($session) ? $session->session_id : ""; ?>'
            },
            success: function(response) {
                console.log('[PA][execute][success]', response);
                if (response.success) {
                    addMessage('ai', 'Action executed: ' + response.result);
                } else {
                    addMessage('error', 'Action failed: ' + response.error);
                }
            },
            error: function(xhr) {
                console.error('[PA][execute][error]', xhr);
                addMessage('error', 'Failed to execute action');
            }
        });
    });

    // Memory filter change handler
    $(document).on('change', '#memory-filter', function(){
        var f = $(this).val();
        if (f === 'all') { $('.memory-item').show(); return; }
        $('.memory-item').each(function(){ $(this).toggle($(this).data('kind') === f || (f==='note' && (!$(this).data('kind')))); });
    updateMemoryStats();
    });

    // Memory refresh handler
    $(document).on('click', '#memory-refresh', function(){ loadMemory(); });

    // Memory auto refresh handler
    $(document).on('change', '#memory-auto', function(){ setMemoryAuto($(this).val()); });

    // Memory retry handler
    $(document).on('click', '#memory-retry', function(){ loadMemory(); });

    // Memory search handler
    $(document).on('input', '#memory-search', function(){
        var q = ($(this).val()||'').toLowerCase();
        $('.memory-item').each(function(){
            var txt = $(this).text().toLowerCase();
            $(this).toggle(txt.indexOf(q) !== -1);
        });
    updateMemoryStats();
    });

    // Memory sort handler
    $(document).on('change', '#memory-sort', function(){
        var dir = $(this).val();
        var items = $('.memory-item').toArray();
        items.sort(function(a,b){
        var ta = $(a).find('.memory-time').first().text();
        var tb = $(b).find('.memory-time').first().text();
            return (dir==='asc') ? ta.localeCompare(tb) : tb.localeCompare(ta);
        });
        $('#memory-timeline').empty().append(items);
    updateMemoryStats();
        
    // Omnibox -> wire Enter to existing chat send
    (function(){
    var input = document.getElementById('paOmniInput');
    if(!input) return;
    input.addEventListener('keydown', function(e){
        if(e.key === 'Enter'){
        var v = input.value.trim();
        if(!v) return;
        var mainInput = document.getElementById('chat-input');
        if (mainInput) { mainInput.value = v; }
        if (typeof sendMessage === 'function') sendMessage();
        input.value = '';
        }
    });
    })();
});


    // Handle filter dropdown toggle
    $(document).on('click', '.filter-toggle-btn', function(e) {
        e.stopPropagation();
        var $dropdown = $(this).siblings('.filter-dropdown-menu');
        $('.filter-dropdown-menu').not($dropdown).removeClass('show');
        $dropdown.toggleClass('show');
    });

    // Close dropdown when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.search-input-wrapper').length) {
            $('.filter-dropdown-menu').removeClass('show');
        }
    });


    // Update stats when memory loads
    $(document).on('memory-loaded', function() {
        setTimeout(updateMemoryStats, 100);
    });

    // Update stats when selection changes
    $(document).on('change', '.memory-chain-checkbox', function() {
        setTimeout(updateMemoryStats, 50);
    });

// Update stats when memory is refreshed
$(document).on('click', '#memory-refresh', function() {
    setTimeout(function() {
        // Stats will be updated when memory loads
    }, 100);
    });

    // Expand/collapse for memory items
    $(document).on('click', '.memory-item [data-toggle="expand"], .memory-item', function(e){
        if ($(e.target).closest('button, a, input, select, textarea').length) return;
        var $it = $(this).closest('.memory-item');
        $it.toggleClass('is-expanded');
    });

    // Quick run again for action_call
    $(document).on('click', '.memory-item .js-run-again', function(e){
        e.preventDefault();
        var actionId = $(this).data('action');
        var params = $(this).data('params') || '{}';
        try { params = JSON.parse(params); } catch(ex){ params = {}; }
        $('#parameterEditorModal').data('action-id', actionId);
        renderDynamicForm({ properties:{} });
        $('#parameterEditorModal').modal('show');
    });

    // Chain-Of-Memory: UI bindings
    $(document).on('change', '.memory-chain-checkbox', function() {
        var memoryId = $(this).data('memory-id');
        var isSelected = $(this).is(':checked');
        $(this).closest('.memory-item').toggleClass('chain-selected', isSelected);
        $.ajax({
            url: '<?php echo admin_url("project_agent/update_memory_chain"); ?>',
            type: 'POST',
            data: {
                memory_id: memoryId,
                is_selected: isSelected ? 1 : 0,
            }
        });
    });

    $(document).on('click', '#select-all-memory', function() {
        $('.memory-chain-checkbox').prop('checked', true).trigger('change');
    });

    $(document).on('click', '#clear-memory-selection', function() {
        $('.memory-chain-checkbox').prop('checked', false).trigger('change');
    });

    $(document).on('click', '#send-memory-chain', function() {
        var ids = [];
        $('.memory-chain-checkbox:checked').each(function(){ ids.push($(this).data('memory-id')); });
        if (!ids.length) { alert('Please select at least one memory to send.'); return; }
        var q = window.PA_LAST_QUESTION || $('#chat-input').val() || '';
        if (!q) { q = prompt('Enter question to send with selected memories:') || ''; }
        if (!q) return;
        sendMemoryChainToAgent(ids, q);
    });

      // Cache initial welcome HTML to restore on reset
      try {
      if ($('#chat-messages .welcome-message').length) {
        window.PA_WELCOME_HTML = $('#chat-messages').html();
      }
    } catch(e){}
    // On project page, try auto-restore latest session
    try {
      if (window.PA_PROJECT_ID) {
        $.post(admin_url + 'project_agent/get_user_sessions', { project_id: window.PA_PROJECT_ID, limit: 1 })
          .done(function(r){
            if (r && r.success && r.sessions && r.sessions.length) {
              var sid = r.sessions[0].session_id;
              if (sid) { loadSessionHistory(sid); }
            } else {
              // If no session yet, create a new one for this project
              createNewSession();
            }
          })
          .fail(function(){ /* ignore */ });
      }
    } catch(e) {}

    // Session History button
    $('#session-history-btn').on('click', function() {
      $('#sessionHistoryModal').modal('show');
      getUserSessions();
    });
    
    // Action Logs button
    $('#action-logs-btn').on('click', function() {
      var sessionId = $(this).data('session-id') || (window.PA_DEBUG && PA_DEBUG.session_id);
      console.log('Action Logs Button - Current session ID:', sessionId);
      console.log('Action Logs Button - PA_DEBUG:', window.PA_DEBUG);

      if (sessionId) {
        // Store session ID in localStorage as backup
        try {
          localStorage.setItem('pa_recent_session_id', sessionId);
        } catch(e) {}

        try { $('#actionLogsModal').data('session-id', sessionId); } catch(e){}
        console.log('Action Logs Button - Set modal session-id to:', sessionId);
        $('#actionLogsModal').modal('show');
      } else {
        console.log('Action Logs Button - No active session found');
        alert('No active session found');
      }
    });
    
    // Clear/Reset button
    $('#clear-chat-btn').on('click', function() {
      if (confirm(window.lang_confirm_reset_chat || 'Are you sure you want to clear the chat? This will start a new conversation.')) {
        clearChat();
      }
    });
    
    // Stop button
    $('#stop-button').on('click', function() {
      stopCurrentProcessing();
    });

    // Restore last session button
    $('#restore-last-btn').on('click', function(){ restoreLastSession(); });

    // Toggle Panel Button
    $('#toggle-panel-btn').on('click', function() {
        toggleSidePanel();
    });

    // Close panel when clicking overlay
    $('#panel-overlay').on('click', function() {
        hideSidePanel();
    });

    // Search in history modal
    $(document).on('input', '#session-search', function(){ filterSessionList($(this).val()); });
    
    // Project filter in history modal
    $(document).on('change', '#project-filter', function(){ filterSessionList($('#session-search').val()); });
    
    // Prevent sending when processing + trace clicks
    $('#send-button').on('click', function() {
      if (PA_CHAT_STATE.isProcessing) {
        console.log('[PA][send] Ignoring send request - processing in progress');
        return false;
      } else {
        console.log('[PA][send] Clicked send button');
      }
    });
    
    // Prevent Enter key when processing + trace Enter
    $('#chat-input').on('keypress', function(e) {
      if (e.which === 13 && !e.shiftKey) {
        if (PA_CHAT_STATE.isProcessing) {
          console.log('[PA][input] Ignoring Enter key - processing in progress');
          e.preventDefault();
          return false;
        } else {
          console.log('[PA][input] Enter pressed in composer');
        }
      }
    });

    // Premium Composer Functionality - Elon Musk Standard
    initializeComposer();

    // Initialize layout on page load - moved inside bindWhenJqueryReady
    setTimeout(initializeChatLayout, 100);

    // Initialize chat functionality - moved inside bindWhenJqueryReady
    setTimeout(initializeChat, 200);
})();

function initializeComposer() {
        const charCount = $('#char-count');
        const clearBtn = $('#composer-clear-btn');
        const sendBtn = $('#send-button');
        const statusIndicator = $('#composer-status');
        const composerInput = $('#chat-input');

        // Initialize SimpleMDE Markdown Editor
        let simplemde;
        function initializeMarkdownEditor() {
            if (typeof SimpleMDE !== 'undefined' && !simplemde) {
                const textarea = document.getElementById('chat-input');
                if (!textarea) return null;

                simplemde = new SimpleMDE({
                    element: textarea,
                    spellChecker: false,
                    renderingConfig: {
                        singleLineBreaks: false,
                        codeSyntaxHighlighting: true,
                    },
                    toolbar: false, // Hide toolbar for cleaner look
                    status: false, // Hide status bar
                    autofocus: false,
                    placeholder: 'Ask me anything about your project...',
                    initialValue: '',
                    previewRender: function(plainText) {
                        // Custom preview render for better markdown support
                        return this.parent.markdown(plainText);
                    },
                    shortcuts: {
                        toggleBold: null,
                        toggleItalic: null,
                        drawLink: null,
                        toggleHeadingSmaller: null,
                        toggleHeadingBigger: null,
                        toggleHeading1: null,
                        toggleHeading2: null,
                        toggleHeading3: null,
                        toggleCodeBlock: null,
                        toggleBlockquote: null,
                        toggleUnorderedList: null,
                        toggleOrderedList: null,
                        cleanBlock: null,
                        drawImage: null,
                        drawTable: null,
                        drawHorizontalRule: null,
                        togglePreview: "Ctrl+P",
                        toggleSideBySide: null,
                        toggleFullScreen: null
                    }
                });

                // Auto-resize based on content with smart height calculation
                simplemde.codemirror.on('change', function() {
                    updateCharCount();
                    autoResizeEditor();
                });

                // Handle focus events
                simplemde.codemirror.on('focus', function() {
                    updateStatus('ready', 'Typing...');
                });

                simplemde.codemirror.on('blur', function() {
                    if (simplemde.value().length === 0) {
                        updateStatus('ready', 'Ready');
                    }
                });

                return simplemde;
            }
            return null;
        }

        // Smart auto-resize based on content
        function autoResizeEditor() {
            if (!simplemde) return;

            const cm = simplemde.codemirror;
            const lineCount = cm.lineCount();
            const lineHeight = cm.defaultTextHeight();
            const padding = 20; // Account for padding
            const minHeight = 44; // Minimum height for single line
            const windowWidth = $(window).width();
            const hasContent = simplemde.value().trim().length > 0;

            // If no content, keep minimal height
            if (!hasContent || lineCount <= 1) {
                cm.setSize(null, minHeight);
                return;
            }

            // Adjust max height based on screen size
            let maxHeight = 150; // Default for desktop
            if (windowWidth < 768) {
                maxHeight = 120; // Smaller on mobile
            } else if (windowWidth < 480) {
                maxHeight = 100; // Even smaller on very small screens
            }

            // Calculate height based on content with smart algorithm
            let calculatedHeight = (lineCount * lineHeight) + padding;

            // Smart height scaling - grow faster for initial lines, slower for many lines
            if (lineCount <= 3) {
                calculatedHeight = Math.max(minHeight, calculatedHeight * 1.2); // Grow faster initially
            } else if (lineCount <= 10) {
                calculatedHeight = Math.max(minHeight, calculatedHeight * 1.1); // Moderate growth
            } else {
                calculatedHeight = Math.max(minHeight, calculatedHeight * 1.05); // Slow growth for long content
            }

            // Apply bounds
            calculatedHeight = Math.max(minHeight, Math.min(maxHeight, calculatedHeight));

            // Smooth height transition
            cm.setSize(null, calculatedHeight);
        }

        // Enhanced character count with markdown awareness
        function updateCharCount() {
            if (!simplemde) return;

            const text = simplemde.value();
            const count = text.length;
            charCount.text(count);

            // Update colors based on character count with better thresholds for markdown
            charCount.removeClass('warning danger');
            if (count > 3500) {
                charCount.addClass('danger');
            } else if (count > 3000) {
                charCount.addClass('warning');
            }

            // Show/hide clear button
            clearBtn.toggle(count > 0);
        }

        // Status updates
        function updateStatus(status, message) {
            const statusEl = statusIndicator.find('i');
            statusEl.removeClass('fa-circle fa-spinner fa-spin fa-check');

            switch(status) {
                case 'ready':
                    statusEl.addClass('fa-circle').css('color', '#28a745');
                    break;
                case 'typing':
                    statusEl.addClass('fa-spinner fa-spin').css('color', '#007bff');
                    break;
                case 'sending':
                    statusEl.addClass('fa-check').css('color', '#28a745');
                    break;
            }

            statusIndicator.find('span:last-child').text(message);
        }

        // Initialize SimpleMDE after a short delay to ensure DOM is ready
        setTimeout(() => {
            simplemde = initializeMarkdownEditor();
            // Ensure editor starts with correct size when empty
            if (simplemde && simplemde.value().trim().length === 0) {
                setTimeout(() => {
                    simplemde.codemirror.setSize(null, 44);
                }, 50);
            }
        }, 100);

        // Clear button
        clearBtn.on('click', function() {
            if (simplemde) {
                simplemde.value('');
                simplemde.codemirror.focus();
                // Reset height after clearing
                setTimeout(() => {
                    if (simplemde && simplemde.codemirror) {
                        simplemde.codemirror.setSize(null, 44);
                    }
                }, 50);
                updateCharCount();
                updateStatus('ready', 'Ready');
            } else {
                composerInput.val('');
            }
        });

        // Send button enhancements
        sendBtn.on('click', function() {
            // Check if there's content to send
            let hasContent = false;
            if (simplemde) {
                hasContent = simplemde.value().trim().length > 0;
            } else {
                hasContent = composerInput.val().trim().length > 0;
            }

            if (hasContent) {
                updateStatus('sending', 'Sending...');
                // The actual sending is handled by the existing sendMessage function
                sendMessage();
            }
        });

        // Suggestion chips with markdown support
        $('.suggestion-chip').on('click', function() {
            const action = $(this).data('action');
            let suggestionText = '';

            switch(action) {
                case 'billing':
                    suggestionText = 'Show me the billing overview for this project';
                    break;
                case 'summary':
                    suggestionText = 'Give me a summary of recent project activities';
                    break;
                case 'task':
                    suggestionText = 'Help me create a new task for this project';
                    break;
                case 'plan':
                    suggestionText = 'Help me create a project plan';
                    break;
            }

            // Set value in SimpleMDE or fallback to textarea
            if (simplemde) {
                simplemde.value(suggestionText);
                simplemde.codemirror.focus();
                simplemde.codemirror.setCursor(simplemde.codemirror.lineCount(), 0);
                updateCharCount();
                autoResizeEditor();
            } else {
                composerInput.val(suggestionText).focus();
            }

            // Highlight send button briefly
            sendBtn.addClass('highlight');
            setTimeout(() => sendBtn.removeClass('highlight'), 1000);
        });

        // Smart Enter key handling with markdown support
        $(document).on('keydown', '.CodeMirror', function(e) {
            if (!simplemde) return;

            if (e.key === 'Enter') {
                if (e.shiftKey) {
                    // Shift+Enter for new line in markdown
                    return true;
                } else {
                    // Enter to send
                    e.preventDefault();
                    const value = simplemde.value().trim();
                    if (value && !sendBtn.prop('disabled')) {
                        sendBtn.click();
                    }
                    return false;
                }
            }

            // Escape to clear
            if (e.key === 'Escape') {
                clearBtn.click();
            }
        });

        // Also handle keydown on the textarea element itself (fallback)
        composerInput.on('keydown', function(e) {
            if (e.key === 'Enter') {
                if (e.shiftKey) {
                    // Shift+Enter for new line
                    return true;
                } else {
                    // Enter to send
                    e.preventDefault();
                    if (composerInput.val().trim() && !sendBtn.prop('disabled')) {
                        sendBtn.click();
                    }
                    return false;
                }
            }

            // Escape to clear
            if (e.key === 'Escape') {
                clearBtn.click();
            }
        });

        // Voice input (placeholder for future)
        $('#composer-voice-btn').on('click', function() {
            updateStatus('ready', 'Voice input not available yet');
            setTimeout(() => updateStatus('ready', 'Ready'), 2000);
        });

        // Initial setup
        updateStatus('ready', 'Ready');

        // Export functions for external use
        window.ComposerAPI = {
            updateStatus: updateStatus,
            focusInput: () => simplemde && simplemde.codemirror.focus(),
            clearInput: () => clearBtn.click(),
            getValue: () => simplemde ? simplemde.value() : '',
            setValue: (text) => simplemde && simplemde.value(text)
        };

        // Make SimpleMDE globally accessible for sendMessage function
        window.simplemde = simplemde;
    }

    // Smart chat layout sizing with JavaScript - moved inside bindWhenJqueryReady
function updateChatLayout() {
    // Use vanilla JS to avoid requiring jQuery
    var windowWidth = window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth;
    var windowHeight = window.innerHeight || document.documentElement.clientHeight || document.body.clientHeight;

    var headerEl = document.querySelector('.chat-card .card-header');
    var headerHeight = headerEl ? headerEl.offsetHeight : 60;
    var composerEl = document.querySelector('.chat-composer');
    var composerHeight = composerEl ? composerEl.offsetHeight : 80;
    var layout = document.querySelector('.chat-layout-container');
    var panelHidden = layout ? layout.classList.contains('panel-hidden') : false;
    var panelWidth = 0;

    if (windowWidth >= 1200) {
        panelWidth = panelHidden ? 0 : 350;
    } else if (windowWidth >= 992) {
        panelWidth = panelHidden ? 0 : 320;
    }

    var availableHeight = windowHeight - 100;
    var chatContentHeight = availableHeight - headerHeight - composerHeight;
    var minChatHeight = Math.min(300, chatContentHeight * 0.8);
    var maxChatHeight = Math.max(600, chatContentHeight);
    var chatHeight = Math.max(minChatHeight, Math.min(maxChatHeight, chatContentHeight));

    if (layout) layout.style.height = availableHeight + 'px';
    var chatColumn = document.querySelector('.chat-column');
    if (chatColumn) {
        chatColumn.style.height = '100%';
        chatColumn.style.width = 'calc(100% - ' + panelWidth + 'px)';
    }
    var chatCard = document.querySelector('.chat-card');
    if (chatCard) chatCard.style.height = '100%';
    var chatContainer = document.querySelector('.chat-container');
    if (chatContainer) chatContainer.style.height = 'calc(100% - ' + headerHeight + 'px)';

    var hasWelcome = !!document.querySelector('.welcome-message');
    if (layout) layout.classList.toggle('has-welcome-message', hasWelcome);
    var chatMessages = document.querySelector('.chat-messages');
    if (chatMessages) {
        if (hasWelcome) {
            chatMessages.style.height = 'auto';
            chatMessages.style.maxHeight = 'none';
        } else {
            chatMessages.style.height = chatHeight + 'px';
            chatMessages.style.maxHeight = chatHeight + 'px';
        }

        if (windowWidth < 768 && !hasWelcome) {
            var hcalc = 'calc(100vh - ' + (headerHeight + composerHeight + 140) + 'px)';
            chatMessages.style.height = hcalc;
            chatMessages.style.maxHeight = hcalc;
        }

        if (windowHeight < 600 && !hasWelcome) {
            chatMessages.style.height = '300px';
            chatMessages.style.maxHeight = '300px';
        }
    }

    setTimeout(function(){
        if (typeof scrollToBottom === 'function') scrollToBottom();
    }, 50);
}

    function initializeChatLayout() {
        updateChatLayout();

        // Handle window resize with debouncing
        var resizeTimeout;
        $(window).on('resize', function() {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(updateChatLayout, 150);
        });

        // Handle orientation change on mobile
        $(window).on('orientationchange', function() {
            setTimeout(updateChatLayout, 300);
        });

        // Handle panel toggle
        $('#toggle-panel-btn').on('click', function() {
            setTimeout(updateChatLayout, 350); // Wait for animation
        });

        // Handle content changes with throttling
        var contentChangeTimeout;
        var observer = new MutationObserver(function(mutations) {
            clearTimeout(contentChangeTimeout);
            contentChangeTimeout = setTimeout(updateChatLayout, 100);
        });

        var chatMessages = document.getElementById('chat-messages');
        if (chatMessages) {
            observer.observe(chatMessages, {
                childList: true,
                subtree: true,
                attributes: true,
                attributeFilter: ['style']
            });
        }

        // Handle keyboard show/hide on mobile
        var viewportHeight = window.innerHeight;
        $(window).on('resize', function() {
            var currentHeight = window.innerHeight;
            var heightDiff = viewportHeight - currentHeight;

            // If height difference is significant (keyboard), update layout
            if (Math.abs(heightDiff) > 150) {
                viewportHeight = currentHeight;
                setTimeout(updateChatLayout, 200);
            }
        });
    }

    function initializeChat() {
        // Send button click handler (SimpleMDE compatible)
        $('#send-button').click(function() {
            sendMessage();
        });

        // Fallback Enter handler for textarea (in case SimpleMDE not loaded)
        $('#chat-input').on('keydown', function(e){
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });

        // Auto-resize textarea fallback
        $('#chat-input').on('input', function(){
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 120) + 'px';
        });
        // Start chat from omnibox (welcome)
        $('#paOmniInput').on('keydown', function(e){
            if (e.key === 'Enter') {
                e.preventDefault();
                var v = ($(this).val()||'').trim();
                if (!v) return;
                try { $('.composer').removeClass('composer-hidden'); } catch(e) {}

                // Set value in SimpleMDE or fallback to textarea
                if (window.simplemde) {
                    window.simplemde.value(v);
                    // Focus and position cursor at end
                    setTimeout(() => {
                        if (window.simplemde && window.simplemde.codemirror) {
                            window.simplemde.codemirror.focus();
                            window.simplemde.codemirror.setCursor(window.simplemde.codemirror.lineCount(), 0);
                        }
                    }, 100);
                } else {
                    $('#chat-input').val(v);
                }

                $(this).val('');
                sendMessage();
            }
        });
    }

function initializeChat() {
    // Send button click handler (SimpleMDE compatible)
    $('#send-button').click(function() {
        sendMessage();
    });

    // Fallback Enter handler for textarea (in case SimpleMDE not loaded)
    $('#chat-input').on('keydown', function(e){
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });

    // Auto-resize textarea fallback
    $('#chat-input').on('input', function(){
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 120) + 'px';
    });
    // Start chat from omnibox (welcome)
    $('#paOmniInput').on('keydown', function(e){
        if (e.key === 'Enter') {
            e.preventDefault();
            var v = ($(this).val()||'').trim();
            if (!v) return;
            try { $('.composer').removeClass('composer-hidden'); } catch(e) {}

            // Set value in SimpleMDE or fallback to textarea
            if (window.simplemde) {
                window.simplemde.value(v);
                // Focus and position cursor at end
                setTimeout(() => {
                    if (window.simplemde && window.simplemde.codemirror) {
                        window.simplemde.codemirror.focus();
                        window.simplemde.codemirror.setCursor(window.simplemde.codemirror.lineCount(), 0);
                    }
                }, 100);
            } else {
            $('#chat-input').val(v);
            }

            $(this).val('');
            sendMessage();
        }
    });
}

function refreshAIStatus() {
    $.ajax({
        url: '<?php echo admin_url("project_agent/get_ai_status"); ?>',
        type: 'GET',
        success: function(response) {
            if (response.success) {
                location.reload();
            }
        }
    });
}

function dismissAIBanner() {
    try {
        // Set a cookie to hide the banner for this browser
        var d = new Date();
        d.setTime(d.getTime() + (24*60*60*1000)); // 1 day
        document.cookie = 'pa_ai_banner_dismissed=1; expires=' + d.toUTCString() + '; path=/';
        var $alert = $('.project-agent-ai-room .alert');
        if ($alert.length) { $alert.alert('close'); }
    } catch(e) { /* ignore */ }
}



function getComposerValue(){
    try {
        if (window.simplemde) {
            var v = '';
            try { v = String(window.simplemde.value()||''); } catch(e){}
            if (v.trim().length === 0 && window.simplemde.codemirror && typeof window.simplemde.codemirror.getValue === 'function') {
                v = String(window.simplemde.codemirror.getValue()||'');
            }
            if (v && v.trim().length) { console.log('[PA][composer] Source: SimpleMDE'); return v; }
        }
        var cmWrap = document.querySelector('.CodeMirror');
        if (cmWrap && cmWrap.CodeMirror && typeof cmWrap.CodeMirror.getValue === 'function') {
            var v2 = String(cmWrap.CodeMirror.getValue()||'');
            if (v2 && v2.trim().length) { console.log('[PA][composer] Source: CodeMirror wrapper'); return v2; }
        }
        var ta = document.getElementById('chat-input');
        if (ta) { var v3 = String(ta.value||''); if (v3 && v3.trim().length){ console.log('[PA][composer] Source: textarea'); return v3; } }
    } catch(e) { console.warn('[PA][composer] read error', e); }
    return '';
}

function sendMessage() {
    var message = getComposerValue().trim();
    if (!message) {
        console.warn('[PA][send] Abort: empty message');
        return;
    }
    
    // Check if already processing
    if (window.PA_CHAT_STATE && window.PA_CHAT_STATE.isProcessing) {
        console.log('[PA][send] Ignoring send request - processing in progress');
        return;
    }
    
    // Add user message to chat
    try { $('.composer').removeClass('composer-hidden'); } catch(e) {}
    addMessage('user', message);

    // Clear input using SimpleMDE or fallback to textarea/CodeMirror
    if (window.simplemde) {
        window.simplemde.value('');
        // Reset height after clearing
        setTimeout(() => {
            if (window.simplemde && window.simplemde.codemirror) {
                window.simplemde.codemirror.setSize(null, 44);
            }
        }, 50);
    } else {
        try {
            var cmWrap = document.querySelector('.CodeMirror');
            if (cmWrap && cmWrap.CodeMirror) { cmWrap.CodeMirror.setValue(''); }
            var ta = document.getElementById('chat-input'); if (ta) ta.value = '';
        } catch(e){}
    }
    
    // Stash last question for auto-suggest
    try { window.PA_LAST_QUESTION = message; } catch(e){}

    // Send to AI
    $('#typing').show();
    var __token = makeClientToken(message);
    var sessionId = (window.PA_DEBUG && PA_DEBUG.session_id) ? PA_DEBUG.session_id : (window.PA_SERVER_SESSION_ID || '<?php echo isset($session) ? $session->session_id : ""; ?>');
    var projectId = '<?php echo isset($project) ? $project->id : ""; ?>';
    
    console.log('[PA][chat] Starting chat with context:');
    console.log('[PA][chat] - message (len):', message.length);
    console.log('[PA][chat] - sessionId:', sessionId);
    console.log('[PA][chat] - projectId:', projectId);
    console.log('[PA][chat] - token:', __token);
    
    // If runtime API available, use it; otherwise fallback to direct POST
    if (window.PA_IR && typeof PA_IR.startChatWithContext === 'function') {
    console.log('[PA][chat] Route: PA_IR.startChatWithContext');
    PA_IR.startChatWithContext(
        message,
        sessionId,
        projectId,
        __token
    ).done(function(response){
            $('#typing').hide();
            console.log('[PA][chat][async][success]', response);
            try {
                if (typeof response === 'string') {
                    response = JSON.parse(response);
                }
            } catch (e) {}
            if (response && (response.success || response.final || response.response)) {
                var aiText = '';
                try {
                    if (response.final) { aiText = String(response.final); }
                    else if (typeof response.response === 'string') { aiText = response.response; }
                    else if (response.response && typeof response.response === 'object' && response.response.final) { aiText = String(response.response.final); }
                } catch(e) {}
                if (!aiText) { aiText = '✓ Context ready. AI responded.'; }
                addMessageHtml('ai', aiText);
                // Popup notices removed - error explainer responses are shown inline
                try {
                    // Render action tasks under this response, if any
                    if (response.actions && response.actions.length) {
                        var container = renderActionTasks(response.run_id, response.actions);
                        $('#chat-messages').append(container);
                        // Save to backend
                        $.ajax({
                            url: '<?php echo admin_url("project_agent/save_response_actions"); ?>',
                            type: 'POST',
                            data: {
                                response_id: response.run_id,
                                session_id: (window.PA_DEBUG && PA_DEBUG.session_id) ? PA_DEBUG.session_id : '<?php echo isset($session) ? $session->session_id : ""; ?>',
                                actions: JSON.stringify(response.actions)
                            }
                        });
                    }
                } catch(e) { console.warn('[PA] action tasks render failed', e); }
                // Update session id and refresh memory timeline
                if (response.session_id) {
                    try { PA_DEBUG.session_id = response.session_id; } catch(e){}
                    loadMemory();
                    try { if (window.PA_LAST_QUESTION) autoSuggestMemoryChain(window.PA_LAST_QUESTION); } catch(e){}
                }
            } else {
                var err = (response && response.error) ? response.error : 'Unknown error';
                // Console logging for developer diagnostics
                if (response && response.error) {
                    console.error('[PA][chat][async][error]', response.error, response);
                } else {
                    console.warn('[PA][chat][async] unexpected response', response);
                }
                
                // Check if user is admin and show technical details
                var isAdmin = <?php echo has_permission('project_agent', '', 'admin') ? 'true' : 'false'; ?>;
                if (isAdmin && response && response.technical_details) {
                    var techDetails = response.technical_details;
                    var techHtml = '<div class="technical-error-details">';
                    techHtml += '<h6><i class="fa fa-bug text-warning"></i> Technical Details (Admin Only)</h6>';
                    techHtml += '<ul class="list-unstyled small">';
                    techHtml += '<li><strong>Provider:</strong> ' + (techDetails.provider || 'unknown') + '</li>';
                    techHtml += '<li><strong>Timestamp:</strong> ' + (techDetails.timestamp || 'unknown') + '</li>';
                    if (techDetails.debug_info) {
                        techHtml += '<li><strong>PHP Version:</strong> ' + (techDetails.debug_info.php_version || 'unknown') + '</li>';
                        techHtml += '<li><strong>Memory Usage:</strong> ' + formatBytes(techDetails.debug_info.memory_usage || 0) + '</li>';
                        techHtml += '<li><strong>Database:</strong> ' + (techDetails.debug_info.database_status || 'unknown') + '</li>';
                    }
                    techHtml += '</ul>';
                    techHtml += '</div>';
                    addMessageHtml('error', 'Error: ' + err + '<br>' + techHtml);
                } else {
                    addMessage('error', 'Error: ' + err);
                }
            }
    }).fail(function(xhr){
            $('#typing').hide();
            console.error('[PA][chat][error]', xhr);
            addMessage('error', 'Network error occurred');
    });
    } else {
        console.warn('[PA][chat] Route: fallback fetch POST (PA_IR not ready)');
        var urlBase = (typeof admin_url !== 'undefined' ? admin_url : '/admin/');
        fetch(urlBase + 'project_agent/chat', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
          body: 'message=' + encodeURIComponent(message) + '&session_id=' + encodeURIComponent(sessionId) + '&project_id=' + encodeURIComponent(projectId) + '&client_token=' + encodeURIComponent(__token) + '&async=1'
        }).then(function(r){ return r.json(); }).then(function(response){
          $('#typing').hide();
          console.log('[PA][chat][fallback][success]', response);
          if (response && (response.success || response.final || response.response)) {
            var aiText = '';
            try {
              if (response.final) { aiText = String(response.final); }
              else if (typeof response.response === 'string') { aiText = response.response; }
              else if (response.response && typeof response.response === 'object' && response.response.final) { aiText = String(response.response.final); }
            } catch(e) {}
            if (!aiText) { aiText = '✓ Context queued.'; }
            addMessageHtml('ai', aiText);
            // Sync active session with server if present in response
            try {
              if (response.session_id) {
                window.PA_DEBUG = window.PA_DEBUG || {}; 
                PA_DEBUG.session_id = response.session_id; 
                window.PA_SERVER_SESSION_ID = response.session_id;
                var base = (typeof admin_url !== 'undefined' ? admin_url : '/admin/');
                fetch(base + 'project_agent/set_active_session', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8'}, body:'session_id=' + encodeURIComponent(response.session_id)})
                  .then(function(){ var el = document.getElementById('composer-session'); if (el) el.textContent = 'S#' + response.session_id; })
                  .catch(function(){});
              }
            } catch(e){}
          } else if (response && response.error) {
            console.error('[PA][chat][fallback][error]', response.error, response);
            addMessage('error', 'Error: ' + response.error);
          } else {
             console.warn('[PA][chat][fallback] Unexpected response shape');
          }
        }).catch(function(err){
          $('#typing').hide();
          console.error('[PA][chat][fallback][error]', err);
          addMessage('error', 'Network error occurred');
        });
    }
}

// Simple client token based on session + message to avoid duplicates
function makeClientToken(message){
    var sid = (window.PA_DEBUG && PA_DEBUG.session_id) ? String(PA_DEBUG.session_id) : '';
    var s = sid + '|' + String(message || '');
    var h = 0; for (var i=0;i<s.length;i++){ h = ((h<<5)-h) + s.charCodeAt(i); h |= 0; }
    return 'ct-' + (h >>> 0).toString(16);
}

function nowTime(){ var d=new Date(); return d.toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'}); }

function addMessage(type, content) {
    // Enhanced content processing for HTML and Markdown support
    var processedContent = processContent(content);

    var cls = type === 'user' ? 'user' : (type==='ai' ? 'ai' : 'system');
    if (type === 'ai-mini') { cls = 'ai mini'; }
    var avatar = '<div class="avatar">'+(cls==='user'?'ME':'AI')+'</div>';
    var bubbleCls = 'bubble ' + (cls==='user'?'user':'ai') + ' enhanced-bubble';
    if (cls==='ai mini') bubbleCls += ' mini';
    var meta = '<div class="msg-meta">'+nowTime()+'</div>';
    var inner = '<div><div class="'+bubbleCls+'">'+processedContent+'</div>'+meta+'</div>';
    var html = '<div class="message '+cls+' mb-2">'+ (cls==='user'? (inner+avatar) : (avatar+inner)) +'</div>';

    // Hide welcome block when first real message arrives
    try {
        if (document.querySelector('#chat-messages .welcome-message')) {
            $('#chat-messages .welcome-message').remove();
            $('.chat-layout-container').removeClass('has-welcome-message');
        }
    } catch(e){}
    try { $('.pa-cta-row, .pa-suggest-row, .quick-actions').remove(); if($('#paTip').length) $('#paTip').remove(); } catch(e){}
    $('#chat-messages').append(html);

    // Apply syntax highlighting to code blocks after rendering
    setTimeout(function() {
        applyCodeHighlighting();
    }, 50);

    // Enhanced scroll to bottom with smooth animation
    scrollToBottom();
    // Ensure composer is visible
    ensureComposerVisible();
}

function addMessageHtml(type, html) {
    // Enhanced HTML processing for mixed content support
    var processedContent = processHtmlContent(html);

    var cls = type === 'user' ? 'user' : (type==='ai' ? 'ai' : (type==='ai-mini'?'ai mini':'system'));
    var avatar = '<div class="avatar">'+(cls==='user'?'ME':'AI')+'</div>';
    var bubbleCls = 'bubble ' + (cls.indexOf('user')!==-1?'user':'ai') + ' enhanced-bubble';
    if (cls.indexOf('mini')!==-1) bubbleCls += ' mini';
    var meta = '<div class="msg-meta">'+nowTime()+'</div>';
    var inner = '<div><div class="'+bubbleCls+'">'+processedContent+'</div>'+meta+'</div>';
    var block = '<div class="message '+cls+' mb-2">'+ (cls==='user'? (inner+avatar) : (avatar+inner)) +'</div>';

    try {
        if (document.querySelector('#chat-messages .welcome-message')) {
            $('#chat-messages .welcome-message').remove();
            $('.chat-layout-container').removeClass('has-welcome-message');
        }
    } catch(e){}
    try { $('.pa-cta-row, .pa-suggest-row, .quick-actions').remove(); if($('#paTip').length) $('#paTip').remove(); } catch(e){}
    $('#chat-messages').append(block);

    // Apply syntax highlighting to code blocks after rendering
    setTimeout(function() {
        applyCodeHighlighting();
    }, 50);

    // Enhanced scroll to bottom with smooth animation
    scrollToBottom();
    // Ensure composer is visible
    ensureComposerVisible();
}

// Super intelligent content processor for HTML and Markdown
function processContent(content) {
    if (!content || typeof content !== 'string') return '';

    // Check if content contains HTML tags
    var hasHtml = /<[^>]*>/.test(content);

    if (hasHtml) {
        // If it has HTML, process it safely
        return processHtmlContent(content);
    } else {
        // If it's plain text/markdown, process as markdown
        return processMarkdownContent(content);
    }
}

// Process HTML content safely
function processHtmlContent(html) {
    if (!html || typeof html !== 'string') return '';
    // Prefer DOMPurify when available
    if (window.DOMPurify && typeof DOMPurify.sanitize === 'function') {
        try {
            var clean = DOMPurify.sanitize(html, {USE_PROFILES: {html: true}});
            return stripDanglingFencesFromHtml(clean);
        } catch(e){}
    }
    // Fallback manual scrub
    var tempDiv = document.createElement('div');
    tempDiv.innerHTML = html;

    // Remove potentially dangerous elements and attributes
    var dangerousElements = ['script', 'style', 'iframe', 'object', 'embed'];
    dangerousElements.forEach(function(tag) {
        var elements = tempDiv.querySelectorAll(tag);
        elements.forEach(function(el) {
            el.parentNode.removeChild(el);
        });
    });

    // Remove dangerous attributes
    var dangerousAttrs = ['onload', 'onerror', 'onclick', 'onmouseover', 'onmouseout'];
    var allElements = tempDiv.querySelectorAll('*');
    allElements.forEach(function(el) {
        dangerousAttrs.forEach(function(attr) {
            el.removeAttribute(attr);
        });
    });

    // Process any markdown within HTML content
    var processedHtml = tempDiv.innerHTML;

    // Look for code blocks and pre elements that might contain markdown
    var codeBlocks = tempDiv.querySelectorAll('pre, code');
    codeBlocks.forEach(function(block) {
        if (block.textContent && !block.querySelector('code')) {
            // This is likely a markdown code block
            var language = detectCodeLanguage(block.textContent);
            block.innerHTML = '<code class="language-' + language + '">' + escapeHtml(block.textContent) + '</code>';
            block.classList.add('code-block');
        }
    });

    return stripDanglingFencesFromHtml(processedHtml);
}

// Process Markdown content using SimpleMDE
function processMarkdownContent(markdown) {
    if (!markdown || typeof markdown !== 'string') return '';

    try {
        // Prefer marked + DOMPurify if available for robust parsing
        if (window.marked) {
            // Configure marked once
            try {
                marked.setOptions({
                    gfm: true,
                    breaks: true,
                    smartypants: true,
                    highlight: function(code, lang){ try { return window.hljs ? hljs.highlight(code, {language: lang || 'plaintext'}).value : code; } catch(e){ return code; } }
                });
            } catch(e) {}
            var html = marked.parse(markdown);
            // Sanitize
            if (window.DOMPurify) { html = DOMPurify.sanitize(html, {USE_PROFILES: {html: true}}); }
            // Cleanup dangling ``` rendered as <p>```</p>
            html = stripDanglingFencesFromHtml(html);
            return enhanceMarkdownHtml(html);
        }
        // Fallback to SimpleMDE parser if present
        if (window.SimpleMDE && typeof SimpleMDE.markdown === 'function') {
            var html = SimpleMDE.markdown(markdown);
            if (window.DOMPurify) { html = DOMPurify.sanitize(html, {USE_PROFILES: {html: true}}); }
            html = stripDanglingFencesFromHtml(html);
            return enhanceMarkdownHtml(html);
        }
        // Final fallback: basic
        var html = basicMarkdownToHtml(markdown);
        if (window.DOMPurify) { html = DOMPurify.sanitize(html, {USE_PROFILES: {html: true}}); }
        return stripDanglingFencesFromHtml(html);
    } catch (e) {
        console.warn('Markdown processing failed:', e);
        return escapeHtml(markdown);
    }
}

// Enhance generated markdown HTML
function enhanceMarkdownHtml(html) {
    if (!html) return '';

    // Add classes and enhance elements
    var tempDiv = document.createElement('div');
    tempDiv.innerHTML = html;

    // Enhance code blocks
    var codeBlocks = tempDiv.querySelectorAll('pre code');
    codeBlocks.forEach(function(code) {
        code.classList.add('code-block-content');
        var language = detectCodeLanguage(code.textContent);
        code.classList.add('language-' + language);

        // Add copy button
        var pre = code.parentNode;
        pre.classList.add('code-block');

        var copyBtn = document.createElement('button');
        copyBtn.className = 'code-copy-btn';
        copyBtn.innerHTML = '<i class="fa fa-copy"></i>';
        copyBtn.onclick = function() {
            copyToClipboard(code.textContent);
            this.innerHTML = '<i class="fa fa-check"></i>';
            setTimeout(() => { this.innerHTML = '<i class="fa fa-copy"></i>'; }, 2000);
        };
        pre.appendChild(copyBtn);
    });

    // Enhance inline code
    var inlineCodes = tempDiv.querySelectorAll('code:not(pre code)');
    inlineCodes.forEach(function(code) {
        code.classList.add('inline-code');
    });

    // Enhance tables
    var tables = tempDiv.querySelectorAll('table');
    tables.forEach(function(table) {
        table.classList.add('markdown-table');
    });

    // Enhance blockquotes
    var blockquotes = tempDiv.querySelectorAll('blockquote');
    blockquotes.forEach(function(bq) {
        bq.classList.add('markdown-quote');
    });

    return tempDiv.innerHTML;
}

// Basic markdown to HTML converter (fallback)
function basicMarkdownToHtml(markdown) {
    if (!markdown) return '';

    // Simple markdown processing
    var html = escapeHtml(markdown);

    // Headers
    html = html.replace(/^### (.*$)/gim, '<h3>$1</h3>');
    html = html.replace(/^## (.*$)/gim, '<h2>$1</h2>');
    html = html.replace(/^# (.*$)/gim, '<h1>$1</h1>');

    // Bold and italic
    html = html.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
    html = html.replace(/\*(.*?)\*/g, '<em>$1</em>');

    // Code blocks
    html = html.replace(/```([\s\S]*?)```/g, function(match, code) {
        var language = detectCodeLanguage(code);
        return '<pre class="code-block"><code class="language-' + language + '">' + escapeHtml(code.trim()) + '</code></pre>';
    });
    // Remove any remaining unmatched backticks lines
    html = html.replace(/(^|\n)\s*```\s*($|\n)/g, '$1$2');

    // Inline code
    html = html.replace(/`([^`]+)`/g, '<code class="inline-code">$1</code>');

    // Links
    html = html.replace(/\[([^\]]+)\]\(([^)]+)\)/g, '<a href="$2" target="_blank" rel="noopener">$1</a>');

    // Line breaks
    html = html.replace(/\n/g, '<br>');

    return html;
}

// Remove <p>```</p> or stray ``` at the end of HTML
function stripDanglingFencesFromHtml(html){
    try {
        var temp = document.createElement('div');
        temp.innerHTML = html;
        // Remove <p>```</p>
        Array.prototype.slice.call(temp.querySelectorAll('p')).forEach(function(p){
            if (p.textContent && p.textContent.trim() === '```') {
                p.parentNode.removeChild(p);
            }
        });
        // Also remove trailing text nodes that consist of only backticks
        function cleanNode(node){
            for (var i=node.childNodes.length-1; i>=0; i--) {
                var ch = node.childNodes[i];
                if (ch.nodeType === 3) { // text
                    if (ch.nodeValue && ch.nodeValue.trim() === '```') {
                        node.removeChild(ch);
                        continue;
                    }
                } else if (ch.nodeType === 1) {
                    cleanNode(ch);
                }
                // break after first non-empty from end
            }
        }
        cleanNode(temp);
        return temp.innerHTML;
    } catch(e) { return html; }
}

// Detect programming language from code content
function detectCodeLanguage(code) {
    if (!code) return 'text';

    var firstLine = code.split('\n')[0].toLowerCase();

    // Check for language hints
    if (firstLine.includes('javascript') || firstLine.includes('js')) return 'javascript';
    if (firstLine.includes('python') || firstLine.includes('py')) return 'python';
    if (firstLine.includes('php')) return 'php';
    if (firstLine.includes('html')) return 'html';
    if (firstLine.includes('css')) return 'css';
    if (firstLine.includes('sql')) return 'sql';
    if (firstLine.includes('bash') || firstLine.includes('shell')) return 'bash';
    if (firstLine.includes('json')) return 'json';
    if (firstLine.includes('xml')) return 'xml';
    if (firstLine.includes('yaml') || firstLine.includes('yml')) return 'yaml';

    // Detect based on content patterns
    if (/function\s+\w+\s*\(/.test(code) && /[{};=]/.test(code)) return 'javascript';
    if (/def\s+\w+\s*\(/.test(code) && /:\s*$/m.test(code)) return 'python';
    if (/<\?php/.test(code)) return 'php';
    if (/<[^>]+>/.test(code) && /<\/[^>]+>/.test(code)) return 'html';
    if (/{\s*\w+\s*:\s*[^}]+}/.test(code)) return 'css';

    return 'text';
}

// Apply syntax highlighting to code blocks
function applyCodeHighlighting() {
    // This would integrate with a syntax highlighter like Prism.js or Highlight.js
    // For now, we'll add basic styling
    $('.code-block code').each(function() {
        var code = $(this);
        if (!code.hasClass('highlighted')) {
            code.addClass('highlighted');
            // Add line numbers if it's a multi-line code block
            var lines = code.text().split('\n');
            if (lines.length > 1) {
                var numberedCode = lines.map(function(line, index) {
                    return '<span class="line-number">' + (index + 1) + '</span><span class="line-content">' + line + '</span>';
                }).join('\n');
                code.html(numberedCode);
            }
        }
    });
}

// Copy text to clipboard
function copyToClipboard(text) {
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text);
    } else {
        // Fallback for older browsers
        var textArea = document.createElement('textarea');
        textArea.value = text;
        textArea.style.position = 'fixed';
        textArea.style.left = '-999999px';
        textArea.style.top = '-999999px';
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();

        try {
            document.execCommand('copy');
        } catch (err) {
            console.error('Fallback: Oops, unable to copy', err);
        }

        textArea.remove();
    }
}

// Escape HTML entities
function escapeHtml(text) {
    var map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}

// Enhanced scroll to bottom function
function scrollToBottom() {
    setTimeout(function() {
    var chatMessages = $('#chat-messages')[0];
    if (chatMessages && chatMessages.scrollHeight !== undefined) {
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
    }, 50); // Faster response for better UX
}

// Ensure composer is visible when messages are present
function ensureComposerVisible() {
    if (!$('.composer').hasClass('composer-hidden')) return;
    $('.composer').removeClass('composer-hidden');
}

// Smart chat layout sizing with JavaScript
function initializeChatLayout() {
    updateChatLayout();

    // Handle window resize with debouncing
    var resizeTimeout;
    $(window).on('resize', function() {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(updateChatLayout, 150);
    });

    // Handle orientation change on mobile
    $(window).on('orientationchange', function() {
        setTimeout(updateChatLayout, 300);
    });

    // Handle panel toggle
    $('#toggle-panel-btn').on('click', function() {
        setTimeout(updateChatLayout, 350); // Wait for animation
    });

    // Handle content changes with throttling
    var contentChangeTimeout;
    var observer = new MutationObserver(function(mutations) {
        clearTimeout(contentChangeTimeout);
        contentChangeTimeout = setTimeout(updateChatLayout, 100);
    });

    var chatMessages = document.getElementById('chat-messages');
    if (chatMessages) {
        observer.observe(chatMessages, {
            childList: true,
            subtree: true,
            attributes: true,
            attributeFilter: ['style']
        });
    }

    // Handle keyboard show/hide on mobile
    var viewportHeight = window.innerHeight;
    $(window).on('resize', function() {
        var currentHeight = window.innerHeight;
        var heightDiff = viewportHeight - currentHeight;

        // If height difference is significant (keyboard), update layout
        if (Math.abs(heightDiff) > 150) {
            viewportHeight = currentHeight;
            setTimeout(updateChatLayout, 200);
        }
    });
}

function updateChatLayout() {
    var windowWidth = $(window).width();
    var windowHeight = $(window).height();

    // Get actual element heights
    var headerHeight = $('.chat-card .card-header').outerHeight() || 60;
    var composerHeight = $('.chat-composer').outerHeight() || 80;
    var panelWidth = 0;

    // Calculate panel width based on screen size
    if (windowWidth >= 1200) {
        panelWidth = $('.chat-layout-container').hasClass('panel-hidden') ? 0 : 350;
    } else if (windowWidth >= 992) {
        panelWidth = $('.chat-layout-container').hasClass('panel-hidden') ? 0 : 320;
    }

    // Smart height calculations
    var availableHeight = windowHeight - 100; // Account for top/bottom margins
    var chatContentHeight = availableHeight - headerHeight - composerHeight;
    var minChatHeight = Math.min(300, chatContentHeight * 0.8); // Adaptive minimum
    var maxChatHeight = Math.max(600, chatContentHeight); // Adaptive maximum

    // Ensure chat height is within bounds
    var chatHeight = Math.max(minChatHeight, Math.min(maxChatHeight, chatContentHeight));

    // Apply dimensions with intelligent calculations
    $('.chat-layout-container').css('height', availableHeight + 'px');
    $('.chat-column').css({
        'height': '100%',
        'width': 'calc(100% - ' + panelWidth + 'px)'
    });
    $('.chat-card').css('height', '100%');
    $('.chat-container').css('height', 'calc(100% - ' + headerHeight + 'px)');
    // Manage welcome message presence class
    if ($('.welcome-message').length) {
        $('.chat-layout-container').addClass('has-welcome-message');
        $('.chat-messages').css({
            'height': 'auto',
            'max-height': 'none'
        });
    } else {
        $('.chat-layout-container').removeClass('has-welcome-message');
        $('.chat-messages').css({
            'height': chatHeight + 'px',
            'max-height': chatHeight + 'px'
        });
    }

    // SimpleMDE height is now handled by CSS based on content presence

    // Handle mobile layouts
    if (windowWidth < 768) {
        if (!$('.welcome-message').length) {
            $('.chat-messages').css({
                'height': 'calc(100vh - ' + (headerHeight + composerHeight + 140) + 'px)',
                'max-height': 'calc(100vh - ' + (headerHeight + composerHeight + 140) + 'px)'
            });
        }
        // CSS will handle welcome message layout automatically
    }

    // Handle very small screens
    if (windowHeight < 600) {
        if (!$('.welcome-message').length) {
            $('.chat-messages').css({
                'height': '300px',
                'max-height': '300px'
            });
        }
        // CSS will handle welcome message layout automatically
    }

    // Ensure proper scrolling after layout update
    setTimeout(scrollToBottom, 50);
}

// Advanced scroll to bottom with smart timing
function scrollToBottom() {
    var chatMessages = $('#chat-messages')[0];
    if (!chatMessages) return;

    // Use requestAnimationFrame for smoother scrolling
    requestAnimationFrame(function() {
        chatMessages.scrollTop = chatMessages.scrollHeight;
    });
}

// Side Panel Functions
function toggleSidePanel() {
    var $panel = $('#side-panel');
    var $overlay = $('#panel-overlay');
    var $container = $('.chat-layout-container');
    var $toggleBtn = $('#toggle-panel-btn');

    if ($panel.hasClass('show')) {
        hideSidePanel();
    } else {
        showSidePanel();
    }
}

function showSidePanel() {
    var $panel = $('#side-panel');
    var $overlay = $('#panel-overlay');
    var $container = $('.chat-layout-container');
    var $toggleBtn = $('#toggle-panel-btn');

    $panel.addClass('show');
    $overlay.addClass('show');
    $container.removeClass('panel-hidden');
    $toggleBtn.addClass('active');

    // Prevent body scroll when panel is open
    $('body').addClass('panel-open');
}

function hideSidePanel() {
    var $panel = $('#side-panel');
    var $overlay = $('#panel-overlay');
    var $container = $('.chat-layout-container');
    var $toggleBtn = $('#toggle-panel-btn');

    $panel.removeClass('show');
    $overlay.removeClass('show');
    $container.addClass('panel-hidden');
    $toggleBtn.removeClass('active');

    // Restore body scroll
    $('body').removeClass('panel-open');
}

function quickAction(action) {
    var message = '';
    switch(action) {
        case 'billing': message = 'Show me the current billing status and outstanding invoices for this project.'; break;
        case 'summary': message = 'Give me a summary of work completed and remaining tasks.'; break;
        case 'task': message = 'Help me create a new task for this project.'; break;
    }
    $('#chat-input').val(message);
    sendMessage();
}

function loadMemory() {
    $.ajax({
        url: '<?php echo admin_url("project_agent/get_memory_entries"); ?>',
        type: 'GET',
        data: { 
            session_id: (window.PA_DEBUG && PA_DEBUG.session_id) ? PA_DEBUG.session_id : '<?php echo isset($session) ? $session->session_id : ""; ?>',
            project_id: '<?php echo isset($project) ? $project->id : ""; ?>'
        },
        success: function(response) {
            console.log('[PA][memory][success]', response);
            if (response.success) {
                if (response.session_id) { try { PA_DEBUG.session_id = response.session_id; } catch(e){} }
                // Prefer client-side rendering when entries are present
                if (response.entries && response.entries.length) {
                    renderMemoryItems(response.entries);
                    return;
                }
                var html = response.html || '';
                var $tl = $('#memory-timeline');
                if ($tl.length) {
                    $tl.stop(true, true).hide().html(html).fadeIn(120);
                }
                // If session established but no HTML yet, retry shortly once
                if ((!html || html.length === 0) && response.session_id) {
                    setTimeout(function(){
                        $.ajax({
                            url: '<?php echo admin_url("project_agent/get_memory_entries"); ?>',
                            type: 'GET',
                            data: { session_id: response.session_id },
                            success: function(r2){
                                console.log('[PA][memory][retry]', r2);
                                if (r2.success) {
                                    if (r2.entries && r2.entries.length) {
                                        renderMemoryItems(r2.entries);
                                    } else if (r2.html) {
                                        $('#memory-timeline').stop(true,true).hide().html(r2.html).fadeIn(120);
                                    }
                                }
                            }
                        });
                    }, 300);
                }
            }
        }
    });
}

// Parameter Editor Functions
function openParameterEditor(actionId, actionName, schema = null) {
    $('#action-name-display').text(actionName);
    $('#parameterEditorModal').data('action-id', actionId);
    
    if (schema) {
        renderDynamicForm(schema);
    } else {
        // Load schema from server
        loadActionSchema(actionId);
    }
    
    $('#parameterEditorModal').modal('show');
}

function loadActionSchema(actionId) {
    $.ajax({
        url: '<?php echo admin_url("project_agent/get_action_schema"); ?>',
        type: 'GET',
        data: { action_id: actionId },
        success: function(response) {
            console.log('[PA][schema][success]', actionId, response);
            if (response.success && response.schema) {
                renderDynamicForm(response.schema);
            } else {
                renderDefaultForm();
            }
        },
        error: function(xhr) {
            console.error('[PA][schema][error]', actionId, xhr);
            renderDefaultForm();
        }
    });
}

function renderDynamicForm(schema) {
    var container = $('#dynamic-form-container');
    container.empty();
    
    if (!schema || !schema.properties) {
        renderDefaultForm();
        return;
    }
    
    var warnings = [];
    
    Object.keys(schema.properties).forEach(function(fieldName) {
        var field = schema.properties[fieldName];
        var required = schema.required && schema.required.includes(fieldName);
        
        var fieldHtml = createFormField(fieldName, field, required);
        container.append(fieldHtml);
        
        // Check for warnings
        if (field.deprecated) {
            warnings.push(fieldName + ' is deprecated');
        }
    });
    
    // Show warnings if any
    if (warnings.length > 0) {
        $('#warning-message').text('Warning: ' + warnings.join(', '));
        $('#parameter-warnings').show();
    } else {
        $('#parameter-warnings').hide();
    }
}

function createFormField(fieldName, field, required) {
    var fieldHtml = '<div class="form-group">';
    fieldHtml += '<label for="' + fieldName + '">' + (field.title || fieldName) + '</label>';
    
    if (required) {
        fieldHtml += ' <span class="text-danger">*</span>';
    }
    
    if (field.description) {
        fieldHtml += '<small class="form-text text-muted">' + field.description + '</small>';
    }
    
    fieldHtml += createInputElement(fieldName, field);
    fieldHtml += '</div>';
    
    return fieldHtml;
}

function createInputElement(fieldName, field) {
    var inputHtml = '';
    var inputClass = 'form-control';
    var inputType = 'text';
    var inputValue = field.default || '';
    
    // Determine input type based on schema
    if (field.type === 'boolean') {
        inputType = 'checkbox';
        inputClass = 'form-check-input';
    } else if (field.type === 'number' || field.type === 'integer') {
        inputType = 'number';
    } else if (field.type === 'textarea') {
        inputType = 'textarea';
    } else if (field.enum) {
        inputType = 'select';
    }
    
    // Create input element
    if (inputType === 'checkbox') {
        inputHtml = '<div class="form-check">';
        inputHtml += '<input type="checkbox" class="' + inputClass + '" id="' + fieldName + '" name="' + fieldName + '"';
        if (inputValue) inputHtml += ' checked';
        inputHtml += '>';
        inputHtml += '<label class="form-check-label" for="' + fieldName + '">' + (field.title || fieldName) + '</label>';
        inputHtml += '</div>';
    } else if (inputType === 'select') {
        inputHtml = '<select class="' + inputClass + '" id="' + fieldName + '" name="' + fieldName + '">';
        field.enum.forEach(function(option) {
            inputHtml += '<option value="' + option + '"';
            if (option === inputValue) inputHtml += ' selected';
            inputHtml += '>' + option + '</option>';
        });
        inputHtml += '</select>';
    } else if (inputType === 'textarea') {
        inputHtml = '<textarea class="' + inputClass + '" id="' + fieldName + '" name="' + fieldName + '" rows="3">' + inputValue + '</textarea>';
    } else {
        inputHtml = '<input type="' + inputType + '" class="' + inputClass + '" id="' + fieldName + '" name="' + fieldName + '" value="' + inputValue + '">';
    }
    
    return inputHtml;
}

function renderDefaultForm() {
    var container = $('#dynamic-form-container');
    container.html(`
        <div class="form-group">
            <label for="default_param">Parameter</label>
            <input type="text" class="form-control" id="default_param" name="default_param" placeholder="Enter parameter value">
        </div>
    `);
}

function collectFormData() {
    var formData = {};
    $('#parameter-form').serializeArray().forEach(function(item) {
        if (item.value) {
            formData[item.name] = item.value;
        }
    });
    
    // Handle checkboxes
    $('#parameter-form input[type="checkbox"]').each(function() {
        formData[$(this).attr('name')] = $(this).is(':checked');
    });
    
    return formData;
}

// Update executeAction to use parameter editor
function executeAction(actionId) {
    // If this action exists in the Action Tasks list (under AI response), execute directly with its parameters
    var $taskItem = $('.action-task-item[data-action-id="'+actionId+'"]').first();
    if ($taskItem.length) {
        var paramsText = ($taskItem.find('.parameters-json').text()||'').trim();
        var params = {};
        try { params = paramsText ? JSON.parse(paramsText) : {}; } catch(e){ params = {}; }
        // Mark as executing
        updateActionStatus(actionId, 'executing');
        // Execute via backend
        $.ajax({
            url: '<?php echo admin_url("project_agent/execute_action"); ?>',
            type: 'POST',
            dataType: 'json',
            data: {
                action_id: actionId,
                params: JSON.stringify(params),
                session_id: (window.PA_DEBUG && PA_DEBUG.session_id) ? PA_DEBUG.session_id : '<?php echo isset($session) ? $session->session_id : ""; ?>'
            },
            success: function(resp){
                if (resp && resp.success) {
                    updateActionStatus(actionId, 'completed');
                    showActionResult(actionId, resp.result || resp);
                } else {
                    updateActionStatus(actionId, 'failed');
                    var err = (resp && resp.error) ? resp.error : 'Unknown error';
                    showActionError(actionId, err);
                }
            },
            error: function(xhr){
                updateActionStatus(actionId, 'failed');
                showActionError(actionId, 'Network error');
            }
        });
        return;
    }

    // Fallback: Open parameter editor for actions listed in Actions tab
    var actionName = '';
    $('.action-item').each(function() {
        var btn = $(this).find('button[onclick*="' + actionId + '"]');
        if (btn.length > 0) {
            actionName = $(this).find('.action-name').text();
            return false;
        }
    });
    openParameterEditor(actionId, actionName);
}

function showActionResult(actionId, result){
    try {
        var $item = $('.action-task-item[data-action-id="'+actionId+'"]').first();
        var $details = $item.find('.action-task-details');
        var html = '<div class="action-result mtop10">'
                 +   '<h6>Execution Result:</h6>'
                 +   '<pre class="result-json">'+escapeHtml(JSON.stringify(result, null, 2))+'</pre>'
                 + '</div>';
        // Remove previous result if exists
        $details.find('.action-result').remove();
        $details.append(html);
        if (!$item.hasClass('expanded')) { toggleActionDetails(actionId); }
    } catch(e) { console.warn('[PA] showActionResult failed', e); }
}
function showActionError(actionId, error){
    try {
        var $item = $('.action-task-item[data-action-id="'+actionId+'"]').first();
        var $details = $item.find('.action-task-details');
        var html = '<div class="action-error mtop10">'
                 +   '<h6>Execution Error:</h6>'
                 +   '<div class="alert alert-danger">'+escapeHtml(String(error||'Error'))+'</div>'
                 + '</div>';
        $details.find('.action-error').remove();
        $details.append(html);
        if (!$item.hasClass('expanded')) { toggleActionDetails(actionId); }
    } catch(e) { console.warn('[PA] showActionError failed', e); }
}

// Client-side render helpers for Memory Timeline
function renderMemoryItems(entries) {
    var html = '';
    var kindToType = function(kind){
        var types = {
            'input': { label: 'User Input', class: 'input' },
            'ai_response': { label: 'AI Response', class: 'ai_response' },
            'action_call': { label: 'Action Call', class: 'action_call' },
            'action_result': { label: 'Action Result', class: 'action_result' },
            'note': { label: 'Note', class: 'note' }
        };
        return types[kind] || types['note'];
    };
    var fmt = function(dt){
        try {
            var d = new Date(dt.replace(' ','T'));
            var now = new Date();
            var diff = now - d;
            var hours = diff / (1000 * 60 * 60);

            if (hours < 1) return 'Just now';
            if (hours < 24) return Math.floor(hours) + 'h ago';
            if (hours < 48) return 'Yesterday';
            return d.toLocaleDateString();
        } catch(e){ return ''; }
    };

    (entries || []).forEach(function(e){
        var kind = (e.kind && e.kind.length) ? e.kind : 'note';
        var typeInfo = kindToType(kind);
        var text = '';
        try {
            var c = (typeof e.content_json === 'string') ? JSON.parse(e.content_json) : e.content_json;
            text = (c && c.text) ? c.text : (c ? JSON.stringify(c) : '');
        } catch(ex) {}

        if (text && text.length > 200) text = text.substring(0, 200) + '...';
        var time = e.created_at ? fmt(e.created_at) : '';
        var checked = (e.is_chain_selected && String(e.is_chain_selected) !== '0') ? 'checked' : '';
        var selCls = checked ? ' selected' : '';

        html += '<div class="memory-item' + selCls + '" data-kind="' + kind + '" data-entry-id="' + (e.entry_id || '') + '">'
             +   '<div class="memory-header">'
             +     '<div class="form-check memory-checkbox">'
             +       '<input type="checkbox" class="form-check-input memory-chain-checkbox" data-memory-id="' + (e.entry_id || '') + '" ' + checked + '>'
             +     '</div>'
             +     '<span class="memory-type ' + typeInfo.class + '">' + typeInfo.label + '</span>'
             +     '<span class="memory-time">' + time + '</span>'
              +   '</div>'
             +   '<div class="memory-content">' + (text || 'No content') + '</div>'
              + '</div>';
    });

    $('#memory-timeline').stop(true,true).hide().html(html).fadeIn(120);

    // Trigger memory loaded event for stats update
    $(document).trigger('memory-loaded');
}

// ===== Action Tasks Rendering =====
function renderActionTasks(responseId, actions){
    if (!actions || !actions.length) return '';
    var html = '';
    html += '<div class="action-tasks-container" data-response-id="'+escapeHtml(responseId)+'">'
         +    '<div class="action-tasks-header">'
         +      '<h6><i class="fa fa-tasks"></i> Action Tasks</h6>'
         +      '<span class="badge badge-info">'+actions.length+' actions</span>'
         +    '</div>'
         +    '<div class="action-tasks-list">';
    for (var i=0;i<actions.length;i++){ html += renderActionTaskItem(actions[i]); }
    html +=   '</div>'
         +  '</div>';
    return html;
}
function renderActionTaskItem(action){
    var st = (action.status||'pending');
    var statusClass = getStatusClass(st);
    var statusIcon = getStatusIcon(st);
    var paramsJson = '';
    try { paramsJson = JSON.stringify(action.parameters||{}, null, 2); } catch(e){ paramsJson = '{}'; }
    var aid = escapeHtml(action.action_id||'');
    var html = '';
    html += '<div class="action-task-item" data-action-id="'+aid+'">'
         +    '<div class="action-task-header" onclick="toggleActionDetails(\''+aid+'\')">'
         +      '<div class="action-task-info">'
         +        '<span class="action-name">'+escapeHtml(action.action_name||aid)+'</span>'
         +        '<span class="action-id text-muted">'+aid+'</span>'
         +      '</div>'
         +      '<div class="action-task-status">'
         +        '<span class="badge badge-'+statusClass+'"><i class="fa fa-'+statusIcon+'"></i> '+st+'</span>'
         +        '<i class="fa fa-chevron-down action-toggle-icon"></i>'
         +      '</div>'
         +    '</div>'
         +    '<div class="action-task-details" id="details-'+aid+'" style="display:none;">'
         +      '<div class="action-parameters">'
         +        '<h6>Parameters:</h6>'
         +        '<pre class="parameters-json">'+escapeHtml(paramsJson)+'</pre>'
         +      '</div>'
         +      '<div class="action-actions">'
         +        '<button class="btn btn-sm btn-primary" onclick="executeAction(\''+aid+'\')"><i class="fa fa-play"></i> Execute</button>'
         +        '<button class="btn btn-sm btn-info" onclick="showActionLogDetails(\''+aid+'\')"><i class="fa fa-eye"></i> View Logs</button>'
         +      '</div>'
         +    '</div>'
         +  '</div>';
    return html;
}
function toggleActionDetails(actionId){
    var $details = $('#details-'+actionId);
    var $item = $('.action-task-item[data-action-id="'+actionId+'"]').first();
    if (!$details.length) return;
    if ($details.is(':visible')){ $details.slideUp(); $item.removeClass('expanded'); }
    else { $details.slideDown(); $item.addClass('expanded'); }
}
function getStatusClass(status){
    switch(String(status||'').toLowerCase()){
        case 'pending': return 'secondary';
        case 'executing': return 'warning';
        case 'completed': return 'success';
        case 'failed': return 'danger';
    }
    return 'secondary';
}
function getStatusIcon(status){
    switch(String(status||'').toLowerCase()){
        case 'pending': return 'clock-o';
        case 'executing': return 'spinner fa-spin';
        case 'completed': return 'check';
        case 'failed': return 'times';
    }
    return 'question';
}
function updateActionStatus(actionId, status){
    var $item = $('.action-task-item[data-action-id="'+actionId+'"]').first();
    var $badge = $item.find('.action-task-status .badge');
    $badge.removeClass('badge-secondary badge-warning badge-success badge-danger');
    $badge.addClass('badge-'+getStatusClass(status));
    $badge.html('<i class="fa fa-'+getStatusIcon(status)+'"></i> '+status);
    $.post('<?php echo admin_url("project_agent/update_action_status"); ?>', { action_id: actionId, status: status });
}

function showActionLogDetails(actionId) {
    // Get current session ID
    var sessionId = window.PA_DEBUG && window.PA_DEBUG.session_id ? window.PA_DEBUG.session_id : '<?php echo isset($session) ? $session->session_id : ""; ?>';
    
    if (!sessionId) {
        alert('No active session found');
        return;
    }
    
    // Load action logs for this session and filter by action ID
    $.ajax({
        url: '<?php echo admin_url("project_agent/get_action_logs"); ?>',
        type: 'GET',
        data: { session_id: sessionId },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Filter logs by action ID
                var filteredLogs = response.logs.filter(function(log) {
                    return log.action_id === actionId;
                });
                
                if (filteredLogs.length > 0) {
                    // Show the most recent log for this action
                    var latestLog = filteredLogs[0];
                    loadActionDetails(latestLog.log_id);
                } else {
                    alert('No execution logs found for this action');
                }
            } else {
                alert('Failed to load action logs: ' + (response.error || 'Unknown error'));
            }
        },
        error: function() {
            alert('Error loading action logs');
        }
    });
}
function escapeHtml(s){ try { return String(s).replace(/[&<>"']/g, function(c){ return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;','\'':'&#39;'}[c]); }); } catch(e){ return s; } }

var __pa_memory_auto = null;

function setMemoryAuto(ms){
    if (__pa_memory_auto) { clearInterval(__pa_memory_auto); __pa_memory_auto = null; }
    if (ms && Number(ms) > 0) {
        __pa_memory_auto = setInterval(loadMemory, Number(ms));
    }
}


function sendMemoryChainToAgent(selectedIds, question){
    $.ajax({
        url: '<?php echo admin_url("project_agent/send_memory_chain"); ?>',
        type: 'POST',
        dataType: 'json',
        data: { session_id: (window.PA_DEBUG && PA_DEBUG.session_id) ? PA_DEBUG.session_id : '', memory_ids: JSON.stringify(selectedIds), question: question },
        success: function(res){
            console.log('[PA][chain][success]', res);
            if (res && res.success) {
                var aiText = '';
                try {
                    if (res.response && typeof res.response === 'object' && res.response.final) {
                        aiText = String(res.response.final);
                    } else if (typeof res.response === 'string') { aiText = res.response; }
                } catch(e){}
                addMessage('ai', aiText || 'AI responded.');
            } else {
                addMessage('error', 'Failed to send memory chain.');
            }
        },
        error: function(xhr){ console.error('[PA][chain][error]', xhr); addMessage('error','Network error'); }
    });
}

// Auto-suggest related memories based on question
function autoSuggestMemoryChain(question){
    try {
        var keywords = extractKeywords(question||'');
        $('.memory-item').each(function(){
            var $it = $(this);
            var text = $it.find('.mt-1').text().toLowerCase();
            var hit = keywords.some(function(k){ return text.indexOf(k.toLowerCase()) !== -1; });
            if (hit) { var cb = $it.find('.memory-chain-checkbox'); cb.prop('checked', true); if(!$it.hasClass('chain-selected')) cb.trigger('change'); $it.addClass('auto-suggested'); }
        });
    } catch(e){}
}

function extractKeywords(text){
    var words = String(text||'').toLowerCase().split(/\s+/);
    var stop = {'the':1,'and':1,'for':1,'with':1,'this':1,'that':1,'have':1,'from':1,'your':1,'about':1};
    var res = [];
    for (var i=0;i<words.length;i++){ var w=words[i]; if (w && w.length>3 && !stop[w]) res.push(w); }
    return res;
}

function formatBytes(bytes) {
    if (bytes === 0) return '0 Bytes';
    var k = 1024;
    var sizes = ['Bytes', 'KB', 'MB', 'GB'];
    var i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// Memory filter dropdown handler
function setMemoryFilter(filterType) {
    $('#memory-filter').val(filterType);

    // Update active state in dropdown
    $('.filter-option').removeClass('active');
    $('.filter-option[data-filter="' + filterType + '"]').addClass('active');

    // Trigger existing filter logic
    var f = filterType;
    if (f === 'all') { $('.memory-item').show(); return; }
    $('.memory-item').each(function(){
        var kind = $(this).data('kind') || 'note';
        $(this).toggle(kind === f || (f==='note' && (!kind)));
    });
    updateMemoryStats();
}


// Update memory stats
function updateMemoryStats() {
    var totalItems = $('.memory-item').length;
    var selectedItems = $('.memory-item .memory-chain-checkbox:checked').length;
    var recentItems = $('.memory-item').filter(function() {
        var timeText = $(this).find('.memory-time').text();
        // Simple check for recent items (within last 24h)
        return timeText.includes('Today') || timeText.includes('hour') || timeText.includes('minute');
    }).length;

    $('#memory-count').text(totalItems);
    $('#memory-selected').text(selectedItems);
    $('#memory-recent').text(recentItems);
}

// Pusher config for Project Agent realtime
window.PA_PUSHER_ENABLED = <?php echo (int) get_option('pusher_realtime_notifications') === 1 ? 'true' : 'false'; ?>;
window.PA_PUSHER_KEY = '<?php echo html_escape((string)get_option('pusher_app_key')); ?>';
window.PA_PUSHER_OPTIONS = <?php 
  $opts = hooks()->apply_filters('pusher_options', [['disableStats' => true]]);
  if (!isset($opts['cluster']) && get_option('pusher_cluster') != '') { $opts['cluster'] = get_option('pusher_cluster'); }
  echo json_encode($opts);
?>;

</script>

<!-- Chat Layout and Session History Modal CSS -->
<link rel="stylesheet" href="<?php echo module_dir_url('project_agent', 'assets/css/session_history_modal.css?v='.time()); ?>">

<!-- Session History Modal -->
<div class="modal fade" id="sessionHistoryModal" tabindex="-1" role="dialog" aria-labelledby="sessionHistoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content session-history-modal">
            <div class="modal-header">
                <h5 class="modal-title d-flex align-items-center" id="sessionHistoryModalLabel">
                    <i class="fa fa-history mr-2"></i> 
                    <span>Session History</span>
                    <span class="badge badge-secondary ml-2" id="session-count-badge">0</span>
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-0">
                <!-- Search and Filter Bar -->
                <div class="bg-light border-bottom p-3">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text bg-white border-right-0">
                                        <i class="fa fa-search text-muted"></i>
                                    </span>
                                </div>
                                <input type="text" id="session-search" class="form-control border-left-0" 
                                       placeholder="Search by title, project, or last message...">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <select class="form-control" id="project-filter">
                                <option value="">All Projects</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Session List Container -->
                <div id="session-history-container" class="session-list-container">
                    <div class="text-center text-muted py-5">
                        <div class="spinner-border text-primary mb-3" role="status">
                            <span class="sr-only">Loading...</span>
                        </div>
                        <h6>Loading sessions...</h6>
                        <small>Please wait while we fetch your conversation history</small>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light border-top">
                <div class="d-flex justify-content-between w-100">
                    <div class="text-muted small d-flex align-items-center">
                        <i class="fa fa-info-circle mr-1"></i>
                        <span>Click on a session to restore it</span>
                    </div>
                    <div>
                        <button type="button" class="btn btn-outline-secondary mr-2" data-dismiss="modal">
                            <i class="fa fa-times mr-1"></i> Close
                        </button>
                        <button type="button" class="btn btn-primary" onclick="createNewSession(); BM_modal('#sessionHistoryModal','hide');">
                            <i class="fa fa-plus mr-1"></i> New Session
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Include Action Logs Modal -->
<?php $this->load->view('action_logs_modal'); ?>

</div>
