// Project Agent - AI Room behaviors
(function(){
  // Inline progress blocks per token
  var PA_PROGRESS_BLOCKS = {};
  
  // Chat state management
  var PA_CHAT_STATE = {
    isProcessing: false,
    currentToken: null,
    currentSessionId: null
  };

  function ensureInlineProgress(token){
    var id = 'pa-progress-'+token;
    if (document.getElementById(id)) return $('#'+id);
    var html = ''+
      '<div id="'+id+'" class="message ai mini mb-1">'+
      '  <div class="avatar">AI</div>'+
      '  <div><div class="bubble ai mini" style="padding: 6px 10px;">'+
      '    <div class="small text-muted mb-0"><i class="fa fa-spinner fa-spin"></i> Working…</div>'+
      '    <div class="pa-progress-summary small text-muted mb-0" style="display:none;"></div>'+
      '    <ul class="list-unstyled small mb-0 pa-lines" style="display:none; margin-top: 4px;"></ul>'+
      '  </div><div class="msg-meta">'+nowTime()+'</div></div>'+
      '</div>';
    $('#chat-messages').append(html);

    // Safely scroll to bottom - check if element exists
    var chatMessages = $('#chat-messages')[0];
    if (chatMessages && chatMessages.scrollHeight !== undefined) {
      $('#chat-messages').scrollTop(chatMessages.scrollHeight);
    }
    var $block = $('#'+id);
    PA_PROGRESS_BLOCKS[token] = $block;
    
    // Add click handler to toggle details
    $block.find('.bubble').on('click', function() {
      var $lines = $block.find('.pa-lines');
      var $summary = $block.find('.pa-progress-summary');
      
      if ($lines.is(':visible')) {
        $lines.slideUp(200);
        $summary.slideDown(200);
      } else {
        $lines.slideDown(200);
        $summary.slideUp(200);
      }
    });
    
    return $block;
  }
  function sendChat(message, sessionId, projectId, clientToken){
    var payload = { message: message, session_id: sessionId, project_id: projectId };
    if (clientToken) payload.client_token = clientToken;
    return $.post(admin_url + 'project_agent/chat', payload);
  }
  
  function createNewSession() {
    var projectId = window.PA_PROJECT_ID || null;
    $.post(admin_url + 'project_agent/create_new_session', { project_id: projectId })
      .done(function(response) {
        if (response.success) {
          PA_CHAT_STATE.currentSessionId = response.session_id;
          console.log('New session created:', response.session_id);
          try { 
            // Clear UI welcome and show hint
            if ($('#chat-messages .welcome-message').length) {
              $('#chat-messages .welcome-message .mb-0').text('New session started.');
            }
          } catch(e){}
        } else {
          console.error('Failed to create new session:', response.error);
        }
      })
      .fail(function() {
        console.error('Error creating new session');
      });
  }
  
  function loadSessionHistory(sessionId) {
    $.post(admin_url + 'project_agent/load_session_history', { session_id: sessionId })
      .done(function(response) {
        if (response.success) {
          displayConversationHistory(response.conversation);
          PA_CHAT_STATE.currentSessionId = sessionId;
          try { $('#sessionHistoryModal').modal('hide'); } catch(e){}
          try { $('.composer').removeClass('composer-hidden'); } catch(e){}
          console.log('[PA][history] Loaded session', sessionId, 'with', (response.conversation||[]).length, 'entries');
        } else {
          console.error('Failed to load session history:', response.error);
        }
      })
      .fail(function() {
        console.error('Error loading session history');
      });
  }
  
  function displayConversationHistory(conversation) {
    var messagesHtml = '';

    conversation.forEach(function(entry) {
      var isUser = entry.kind === 'input';
      var avatar = isUser ? 'You' : 'AI';
      var bubbleClass = isUser ? 'user' : 'ai';
      var time = new Date(entry.created_at).toLocaleTimeString();

      messagesHtml += '<div class="message ' + bubbleClass + ' mb-2">' +
        '<div class="avatar">' + avatar + '</div>' +
        '<div class="bubble ' + bubbleClass + '">' +
        '<div class="message-content">' + escapeHtml(entry.text) + '</div>' +
        '<div class="msg-meta">' + time + '</div>' +
        '</div>' +
        '</div>';
    });

    $('#chat-messages').html(messagesHtml);

    // Safely scroll to bottom - check if element exists
    var chatMessages = $('#chat-messages')[0];
    if (chatMessages && chatMessages.scrollHeight !== undefined) {
      $('#chat-messages').scrollTop(chatMessages.scrollHeight);
    }

    try { if (conversation && conversation.length) { $('.composer').removeClass('composer-hidden'); } } catch(e){}
  }
  
  function getUserSessions() {
    var projectId = window.PA_PROJECT_ID || null;
    $.post(admin_url + 'project_agent/get_user_sessions', { project_id: projectId, limit: 20 })
      .done(function(response) {
        if (response.success) {
          displaySessionList(response.sessions);
        } else {
          console.error('Failed to load sessions:', response.error);
        }
      })
      .fail(function() {
        console.error('Error loading sessions');
      });
  }
  
  function displaySessionList(sessions) {
    try { window.PA_SESSIONS_CACHE = (sessions||[]).slice(0); } catch(e){}
    
    // Update session count badge
    $('#session-count-badge').text((sessions||[]).length);
    
    if (!sessions || sessions.length === 0) {
      var emptyHtml = ''+
        '<div class="text-center py-5">'+
        '  <div class="mb-4">'+
        '    <i class="fa fa-comments fa-4x text-muted"></i>'+
        '  </div>'+
        '  <h5 class="text-muted mb-2">No Sessions Found</h5>'+
        '  <p class="text-muted mb-4">Start a new conversation to see your session history here.</p>'+
        '  <button class="btn btn-primary" onclick="createNewSession(); $(\'#sessionHistoryModal\').modal(\'hide\');">'+
        '    <i class="fa fa-plus mr-1"></i> Start New Session'+
        '  </button>'+
        '</div>';
      $('#session-history-container').html(emptyHtml);
      return;
    }
    
    var sessionsHtml = '<div class="session-list">';
    
    (sessions||[]).forEach(function(session, index) {
      var projectName = session.project_name || 'General';
      var title = session.title && session.title.trim() ? session.title : ('Session #' + session.session_id);
      var lastText = (session.last_message && session.last_message.text) ? String(session.last_message.text) : '';
      var lastMessage = lastText ? (lastText.length>120 ? lastText.substring(0,120) + '…' : lastText) : 'No messages';
      var time = new Date(session.created_at);
      var timeStr = time.toLocaleDateString() + ' ' + time.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
      var sid = session.session_id;
      var isRecent = (Date.now() - time.getTime()) < (24 * 60 * 60 * 1000); // Last 24 hours
      
      sessionsHtml += ''+
        '<div class="session-item" data-session-id="'+sid+'" data-project="'+escapeHtml(projectName)+'">'+
        '  <div class="session-card">'+
        '    <div class="session-header">'+
        '      <div class="session-title">'+
        '        <h6 class="mb-1">'+escapeHtml(title)+'</h6>'+
        '        <div class="session-meta">'+
        '          <span class="project-badge">'+escapeHtml(projectName)+'</span>'+
        '          <span class="session-time">'+timeStr+'</span>'+
        (isRecent ? '          <span class="badge badge-success badge-sm">Recent</span>' : '')+
        '        </div>'+
        '      </div>'+
        '      <div class="session-actions">'+
        '        <button class="btn btn-sm btn-outline-primary" title="Restore Session" onclick="loadSessionHistory('+sid+')">'+
        '          <i class="fa fa-play"></i>'+
        '        </button>'+
        '        <button class="btn btn-sm btn-outline-secondary" title="Rename Session" onclick="renameSession('+sid+')">'+
        '          <i class="fa fa-edit"></i>'+
        '        </button>'+
        '        <button class="btn btn-sm btn-outline-danger" title="Delete Session" onclick="deleteSession('+sid+')">'+
        '          <i class="fa fa-trash"></i>'+
        '        </button>'+
        '      </div>'+
        '    </div>'+
        '    <div class="session-preview">'+
        '      <p class="mb-0 text-muted">'+escapeHtml(lastMessage)+'</p>'+
        '    </div>'+
        '  </div>'+
        '</div>';
    });
    sessionsHtml += '</div>';
    
    var $ctr = $('#session-history-container');
    $ctr.find('.text-center').remove();
    $ctr.find('.session-list').remove();
    $ctr.append(sessionsHtml);
    
    // Update project filter
    updateProjectFilter();
  }

  function filterSessionList(q){
    q = String(q||'').toLowerCase();
    var projectFilter = $('#project-filter').val();
    
    var arr = (window.PA_SESSIONS_CACHE||[]).filter(function(s){
      var title = (s.title||('Session #'+s.session_id)).toLowerCase();
      var last = (s.last_message && s.last_message.text ? String(s.last_message.text) : '').toLowerCase();
      var project = (s.project_name || 'General').toLowerCase();
      
      var matchesSearch = !q || title.indexOf(q)!==-1 || last.indexOf(q)!==-1 || project.indexOf(q)!==-1;
      var matchesProject = !projectFilter || project === projectFilter.toLowerCase();
      
      return matchesSearch && matchesProject;
    });
    displaySessionList(arr);
  }
  
  function updateProjectFilter() {
    var projects = [];
    (window.PA_SESSIONS_CACHE||[]).forEach(function(session) {
      var projectName = session.project_name || 'General';
      if (projects.indexOf(projectName) === -1) {
        projects.push(projectName);
      }
    });
    
    var $filter = $('#project-filter');
    var currentValue = $filter.val();
    $filter.empty().append('<option value="">All Projects</option>');
    
    projects.sort().forEach(function(project) {
      $filter.append('<option value="'+escapeHtml(project)+'">'+escapeHtml(project)+'</option>');
    });
    
    if (currentValue && projects.indexOf(currentValue) !== -1) {
      $filter.val(currentValue);
    }
  }

  function renameSession(sessionId){
    var currentTitle = '';
    try {
      var s = (window.PA_SESSIONS_CACHE||[]).find(function(x){ return x.session_id===sessionId; });
      currentTitle = s && s.title ? s.title : '';
    } catch(e){}
    var t = prompt('Enter new title', currentTitle||('Session #'+sessionId));
    if (t===null) return;
    t = (t||'').trim(); if (!t) return;
    $.post(admin_url + 'project_agent/rename_session', { session_id: sessionId, title: t })
      .done(function(r){
        if (r && r.success){ getUserSessions(); }
        else { alert('Rename failed: '+(r.error||'unknown')); }
      })
      .fail(function(){ alert('Network error'); });
  }

  function deleteSession(sessionId){
    if (!confirm('Delete this session? This cannot be undone.')) return;
    $.post(admin_url + 'project_agent/delete_session', { session_id: sessionId })
      .done(function(r){
        if (r && r.success){
          try {
            window.PA_SESSIONS_CACHE = (window.PA_SESSIONS_CACHE||[]).filter(function(x){ return x.session_id!==sessionId; });
          } catch(e){}
          displaySessionList(window.PA_SESSIONS_CACHE||[]);
        } else {
          alert('Delete failed: '+(r.error||'unknown'));
        }
      })
      .fail(function(){ alert('Network error'); });
  }

  function restoreLastSession(){
    var projectId = window.PA_PROJECT_ID || null;
    $.post(admin_url + 'project_agent/get_user_sessions', { project_id: projectId, limit: 1 })
      .done(function(r){
        if (r && r.success && r.sessions && r.sessions.length){
          loadSessionHistory(r.sessions[0].session_id);
        } else {
          createNewSession();
        }
      });
  }
  
  // UI State Management
  function setChatProcessingState(isProcessing, token) {
    PA_CHAT_STATE.isProcessing = isProcessing;
    PA_CHAT_STATE.currentToken = token;
    
    var $input = $('#chat-input');
    var $sendBtn = $('#send-button');
    var $stopBtn = $('#stop-button');
    var $attachBtn = $('.btn[title="Attach"]');
    
    if (isProcessing) {
      // Disable input and show stop button
      $input.prop('disabled', true);
      $sendBtn.hide();
      $stopBtn.show();
      $attachBtn.prop('disabled', true);
      $input.attr('placeholder', 'AI is processing... Please wait');
    } else {
      // Enable input and show send button
      $input.prop('disabled', false);
      $sendBtn.show();
      $stopBtn.hide();
      $attachBtn.prop('disabled', false);
      $input.attr('placeholder', 'Type your message... Press Enter to send, Shift+Enter for new line');
    }
  }
  
  function clearChat() {
    // Restore cached Welcome screen if present, otherwise fallback minimal
    if (window.PA_WELCOME_HTML && typeof window.PA_WELCOME_HTML === 'string') {
      $('#chat-messages').html(window.PA_WELCOME_HTML);
    } else {
      $('#chat-messages').html('<div class="welcome-message text-center text-muted py-3">' +
        '<i class="fa fa-robot fa-2x mb-2 text-primary"></i>' +
        '<h6 class="mb-2">Project Agent AI</h6>' +
        '<p class="mb-0">Start a new conversation by typing your message below.</p>' +
        '</div>');
    }
    
    // Reset state
    PA_CHAT_STATE.isProcessing = false;
    PA_CHAT_STATE.currentToken = null;
    PA_CHAT_STATE.currentSessionId = null;
    
    // Clear progress blocks
    PA_PROGRESS_BLOCKS = {};
    
    // Create new session
    createNewSession();
    
    // Reset UI & hide composer like first-load
    setChatProcessingState(false);
    try { $('.composer').addClass('composer-hidden'); } catch(e){}
    
    // Clear input
    $('#chat-input').val('');
    
    console.log('[PA][clear] Chat cleared and reset to new state');
  }
  
  function stopCurrentProcessing() {
    if (!PA_CHAT_STATE.isProcessing || !PA_CHAT_STATE.currentToken) {
      console.log('[PA][stop] No active processing to stop');
      return;
    }
    
    console.log('[PA][stop] Stopping processing for token: ' + PA_CHAT_STATE.currentToken);
    
    // Add stop message to chat
    var stopMessage = '<div class="message user mb-2">' +
      '<div class="avatar">You</div>' +
      '<div class="bubble user">' +
      '<div class="text-muted small"><i class="fa fa-stop"></i> Processing stopped by user</div>' +
      '</div><div class="msg-meta">' + nowTime() + '</div></div>';
    $('#chat-messages').append(stopMessage);

    // Safely scroll to bottom - check if element exists
    var chatMessages = $('#chat-messages')[0];
    if (chatMessages && chatMessages.scrollHeight !== undefined) {
      $('#chat-messages').scrollTop(chatMessages.scrollHeight);
    }
    
    // Reset state
    setChatProcessingState(false);
    
    // Note: We can't actually stop the server-side processing, but we stop the UI updates
    console.log('[PA][stop] Processing stopped (UI only)');
  }

  // Legacy modal helpers kept for compatibility (unused when inline enabled)
  function ensureContextModal(){}
  function showContextModal(){}
  function hideContextModal(){}
  
  // --- Pusher realtime (no polling) ---
  var paPusher = null;
  function ensurePusher(){
    try {
      console.log('[PA][pusher] ensurePusher called');
      console.log('[PA][pusher] PA_PUSHER_ENABLED:', window.PA_PUSHER_ENABLED);
      console.log('[PA][pusher] PA_PUSHER_KEY:', window.PA_PUSHER_KEY ? 'SET' : 'NOT SET');
      console.log('[PA][pusher] Pusher available:', typeof Pusher !== 'undefined');
      
      if (!window.PA_PUSHER_ENABLED) { 
        console.warn('[PA][pusher] Pusher disabled in config');
        return null; 
      }
      if (paPusher) { 
        console.log('[PA][pusher] Using existing Pusher instance');
        return paPusher; 
      }
      if (typeof Pusher === 'undefined') { 
        console.error('[PA][pusher] Pusher JS not loaded'); 
        return null; 
      }
      
      try { Pusher.logToConsole = true; } catch(e){}
      console.log('[PA][pusher] Initializing Pusher with key:', (window.PA_PUSHER_KEY||'').slice(0,4)+'***', 'options:', window.PA_PUSHER_OPTIONS);
      
      paPusher = new Pusher(window.PA_PUSHER_KEY || '', window.PA_PUSHER_OPTIONS || {});
      
      try {
        paPusher.connection.bind('state_change', function(states){ 
          console.log('[PA][pusher] Connection state changed:', states); 
        });
        paPusher.connection.bind('connected', function(){ 
          console.log('[PA][pusher] Connected successfully'); 
        });
        paPusher.connection.bind('disconnected', function(){ 
          console.log('[PA][pusher] Disconnected'); 
        });
        paPusher.connection.bind('error', function(err){ 
          console.error('[PA][pusher] Connection error:', err); 
        });
        
      } catch(e){
        console.error('[PA][pusher] Error binding connection events:', e);
      }
      
      console.log('[PA][pusher] Pusher instance created successfully');
      return paPusher;
    } catch(e){ 
      console.error('[PA][pusher] Error creating Pusher instance:', e);
      return null; 
    }
  }

  function subscribeProgress(token, onFinal){
    console.log('[PA][pusher] subscribeProgress called with token:', token);
    var p = ensurePusher();
    if (!p) { 
      console.error('[PA][pusher] Cannot subscribe - Pusher not available');
      return false; 
    }
    try {
      // Subscribe to global test channel
      // console.log('[PA][pusher] Subscribing to global test channel: global_test');
      // var globalCh = p.subscribe('global_test');
      // globalCh.bind('global_event', function(data) {
      //   console.log('[PA][pusher] ===== GLOBAL TEST EVENT RECEIVED =====');
      //   console.log('[PA][pusher] Global event data:', data);
      //   console.log('[PA][pusher] Original event:', data.original_event);
      //   console.log('[PA][pusher] Original token:', data.original_token);
      //   console.log('[PA][pusher] Message:', data.message);
      //   console.log('[PA][pusher] Timestamp:', data.timestamp);
      //   console.log('[PA][pusher] ======================================');
      // });
      
      var chName = 'project-agent-' + String(token).replace(/[^a-zA-Z0-9_\-]/g,'_');
      console.log('[PA][pusher] Subscribing to channel:', chName);
      var ch = p.subscribe(chName);
      
      ch.bind('pusher:subscription_succeeded', function(){ 
        console.log('[PA][pusher] Successfully subscribed to channel:', chName);
        // Create inline progress block
        ensureInlineProgress(token);
        // Test ping immediately after subscription to verify connectivity (optional)
        try { 
          console.log('[PA][pusher] Sending test ping to verify connectivity');
          $.post(admin_url + 'project_agent/pusher_ping', { client_token: token })
            .done(function(r){ console.log('[PA][pusher] Ping response:', r); })
            .fail(function(e){ console.error('[PA][pusher] Ping failed:', e); });
        } catch(e){
          console.error('[PA][pusher] Error sending ping:', e);
        }
      });
      
      ch.bind('pusher:subscription_error', function(err){ 
        console.error('[PA][pusher] Subscription error for channel:', chName, err); 
      });
      if (!window.PA_CHANNEL_STATE) window.PA_CHANNEL_STATE = {};
      window.PA_CHANNEL_STATE[token] = { started:false };
      var addLine = function(msg){ 
        var $blk = PA_PROGRESS_BLOCKS[token] || ensureInlineProgress(token);
        var $summary = $blk.find('.pa-progress-summary');
        var $lines = $blk.find('.pa-lines');
        
        // Show detailed lines only for important events
        if (msg.includes('failed') || msg.includes('error') || msg.includes('Action')) {
          $lines.show().append('<li class="mb-1">'+msg+'</li>');
        }
        
        // Update summary with current status
        var summaryText = msg.replace(/^.*?:\s*/, '').replace(/\.\.\.$/, '');
        $summary.text(summaryText).show();

        // Safely scroll to bottom - check if element exists
        var chatMessages = $('#chat-messages')[0];
        if (chatMessages && chatMessages.scrollHeight !== undefined) {
          $('#chat-messages').scrollTop(chatMessages.scrollHeight);
        }
      };
      ch.bind('context_begin', function(ev){ 
        console.log('[PA][pusher] context_begin event received', ev);
        addLine('Starting…'); 
      });
      ch.bind('session_loaded', function(ev){ 
        console.log('[PA][pusher] session_loaded event received', ev);
        addLine('Session loaded'); 
      });
      ch.bind('ai_started', function(ev){ 
        console.log('[PA][pusher] ai_started event received', ev);
        try { window.PA_CHANNEL_STATE[token].started = true; } catch(e){} 
      });
      ch.bind('project_loading', function(ev){ 
        console.log('[PA][pusher] project_loading event received', ev);
        addLine('Loading project…'); 
      });
      ch.bind('project_loaded', function(ev){ 
        console.log('[PA][pusher] project_loaded event received', ev);
        var d = ev && ev.data ? ev.data : {}; 
        addLine('Project context: '+(d.tasks||0)+' tasks, '+(d.milestones||0)+' milestones, '+(d.activities||0)+' activities'); 
      });
      ch.bind('queued', function(ev){
        console.log('[PA][pusher] queued event received', ev);
        addLine('Queued job…');
      });
      ch.bind('user_loading', function(ev){ 
        console.log('[PA][pusher] user_loading event received', ev);
        addLine('Loading user…'); 
      });
      ch.bind('user_loaded', function(ev){ 
        console.log('[PA][pusher] user_loaded event received', ev);
        addLine('User context ready'); 
      });
      ch.bind('temporal_loaded', function(ev){ 
        console.log('[PA][pusher] temporal_loaded event received', ev);
        addLine('Time context ready'); 
      });
      ch.bind('organization_loaded', function(ev){ 
        console.log('[PA][pusher] organization_loaded event received', ev);
        addLine('Organization context ready'); 
      });
      ch.bind('memory_loaded', function(ev){ 
        console.log('[PA][pusher] memory_loaded event received', ev);
        addLine('Recent memory ready'); 
      });
      ch.bind('system_loaded', function(ev){ 
        console.log('[PA][pusher] system_loaded event received', ev);
        addLine('System context ready'); 
      });
      ch.bind('complete', function(ev){ 
        console.log('[PA][pusher] complete event received', ev);
        $('#paOpenContextBtn').show(); /* keep modal state as-is; user controls */ 
      });
      ch.bind('ai_phase', function(ev){
        console.log('[PA][pusher] ai_phase event received', ev);
        var step = ev && ev.data ? ev.data.step : '';
        if (step==='extract_start') addLine('Planning actions…');
        else if (step==='extract_retry') addLine('Planning retry (compact)…');
        else if (step==='extract_done') addLine('Plan ready');
        else if (step==='finalize_start') addLine('Composing final answer…');
        else if (step==='finalize_done') addLine('Finalized answer');
      });
      ch.bind('action_start', function(ev){ 
        console.log('[PA][pusher] action_start event received', ev);
        var a = ev && ev.data ? ev.data : {}; 
        addLine('Executing action: '+(a.action_id||'')); 
      });
      ch.bind('action_done', function(ev){ 
        console.log('[PA][pusher] action_done event received', ev);
        var a = ev && ev.data ? ev.data : {}; 
        var line = 'Action '+(a.action_id||'')+': '+(a.ok?'ok':'failed');
        if (!a.ok && a.error) { line += ' – '+a.error; }
        addLine(line); 
      });
      ch.bind('pa_ping', function(ev){ 
        console.log('[PA][pusher] pa_ping event received', ev); 
      });
      ch.bind_global(function(event, data){ 
        console.log('[PA][pusher] global event received:', event, data); 
      });
      ch.bind('ai_final', function(ev){ 
        console.log('[PA][pusher] ai_final event received', ev);
        // Mark block done and collapse
        try { 
          var $blk = PA_PROGRESS_BLOCKS[token]; 
          if ($blk) { 
            $blk.find('.small.text-muted').html('<i class="fa fa-check text-success"></i> Done');
            $blk.find('.pa-lines').slideUp(300);
            $blk.find('.pa-progress-summary').text('Completed successfully').addClass('text-success');
            
            // Auto-collapse after 2 seconds
            setTimeout(function() {
              $blk.find('.pa-progress-summary').slideUp(300);
            }, 2000);
          }
        } catch(e){}
        try { $('.composer').removeClass('composer-hidden'); } catch(e){}
        if (onFinal) onFinal(ev && ev.data ? ev.data : ev); 
      });
      ch.bind('ai_final_ready', function(ev){
        console.log('[PA][pusher] ai_final_ready signal received', ev);
        try { 
          var $blk = PA_PROGRESS_BLOCKS[token]; 
          if ($blk) { 
            $blk.find('.small.text-muted').html('<i class="fa fa-check text-success"></i> Done');
            $blk.find('.pa-lines').slideUp(300);
            $blk.find('.pa-progress-summary').text('Completed successfully').addClass('text-success');
          }
        } catch(e){}
        try { $('.composer').removeClass('composer-hidden'); } catch(e){}
        $.post(admin_url + 'project_agent/chat_result', { client_token: token })
          .done(function(r){ if (r && r.success && r.result && onFinal) onFinal(r.result); })
          .fail(function(e){ console.error('[PA][chat] fetch final failed', e); });
      });
      ch.bind('ai_error', function(ev){ 
        console.log('[PA][pusher] ai_error event received', ev);
        if (onFinal) onFinal({ success:false, error: (ev && ev.data && ev.data.message) || 'AI error' }); 
      });
      return true;
    } catch(e){ 
      console.error('[PA][pusher] subscription error:', e);
      return false; 
    }
  }

  function pollContext(token){
    console.log('pollContext', token);
    if (!token) return;
    var $list = $('#paContextList');
    var seen = {};
      var addLine = function(msg){ $list.append('<li class="mb-1">'+msg+'</li>'); };
    var timer = setInterval(function(){
      $.post(admin_url + 'project_agent/context_progress', { client_token: token })
        .done(function(r){
          if (!r || !r.success) return;
          (r.events||[]).forEach(function(ev){
            var key = ev.ts + '-' + ev.event;
            if (seen[key]) return; seen[key]=1;
            switch(ev.event){
              case 'context_begin': addLine('Starting…'); break;
              case 'session_loaded': addLine('Session loaded'); break;
              case 'project_loading': addLine('Loading project…'); break;
              case 'project_loaded': addLine('Project context: '+(ev.data.tasks||0)+' tasks, '+(ev.data.milestones||0)+' milestones, '+(ev.data.activities||0)+' activities'); break;
              case 'user_loading': addLine('Loading user…'); break;
              case 'user_loaded': addLine('User context ready'); break;
              case 'temporal_loaded': addLine('Time context ready'); break;
              case 'organization_loaded': addLine('Organization context ready'); break;
              case 'memory_loaded': addLine('Recent memory ready'); break;
              case 'system_loaded': addLine('System context ready'); break;
              case 'ai_phase':
                var step = ev.data && ev.data.step;
                if (step==='extract_start') addLine('Planning actions…');
                else if (step==='extract_retry') addLine('Planning retry (compact)…');
                else if (step==='extract_done') addLine('Plan ready');
                else if (step==='finalize_start') addLine('Composing final answer…');
                else if (step==='finalize_done') addLine('Finalized answer');
                break;
              case 'action_start':
                addLine('Executing action: '+(ev.data && ev.data.action_id || ''));
                break;
              case 'action_done':
                addLine('Action '+(ev.data && ev.data.action_id || '')+': '+((ev.data && ev.data.ok)?'ok':'failed'));
                break;
              case 'complete': addLine('Context ready'); break;
            }
          });
          if (r.done){ clearInterval(timer); $('#paOpenContextBtn').show(); }
      })
      .fail(function(){ /* ignore */ });
    }, 600);
  }

  function triggerRun(token){
    if (!token) return $.Deferred().reject().promise();
    return $.post(admin_url + 'project_agent/chat_run', { client_token: token });
  }

  function pollChat(token, onDone){
    var started = false;
    var t = setInterval(function(){
      $.post(admin_url + 'project_agent/chat_progress', { client_token: token })
        .done(function(r){
          if (!r || !r.success) return;
          if (!started) { started = true; triggerRun(token); }
          if (r.status === 'done') {
            clearInterval(t);
            if (onDone && r.result) { onDone(r.result); }
          }
          if (r.status === 'error') {
            clearInterval(t);
            if (onDone) { onDone({ success:false, response:'', final:'', error: (r.result && r.result.message) || 'AI error' }); }
          }
        });
    }, 800);
  }

  // Helper to orchestrate context progress + chat
  function startChatWithContext(message, sessionId, projectId, token){
    var t = token || ('ct-' + Date.now() + '-' + Math.floor(Math.random()*100000));
    console.log('[PA][chat] startChatWithContext called with token:', t);
    window.PA_LAST_CLIENT_TOKEN = t;
    
    // Set processing state
    setChatProcessingState(true, t);
    PA_CHAT_STATE.currentSessionId = sessionId;
    
    var d = $.Deferred();
    
    console.log('[PA][chat] Attempting to subscribe to progress events');
    var usingPusher = subscribeProgress(t, function(result){ 
      console.log('[PA][chat] Final result received:', result);
      // Reset processing state when done
      setChatProcessingState(false);
      d.resolve(result); 
    });
    // Subscribe to per-user progress channel to sync across tabs/devices
    try { subscribeUserChannel(); } catch(e){}
    
    console.log('[PA][chat] Pusher subscription result:', usingPusher);
    
    // No polling fallback – rely only on Pusher as requested
    console.log('[PA][chat] Sending chat request to server');
    $.post(admin_url + 'project_agent/chat', { message: message, session_id: sessionId, project_id: projectId, client_token: t, async: 1 })
      .done(function(response){
        console.log('[PA][chat] Chat request successful:', response);
        if (!usingPusher) {
          console.warn('[PA][chat] Pusher not available - showing warning');
          // Inform user that realtime is required
          ensureInlineProgress(t).find('.small.text-muted').html('<i class="fa fa-exclamation-triangle text-warning"></i> Realtime disabled. Please enable Pusher.');
        } else {
          console.log('[PA][chat] Pusher available - waiting for realtime events');
        }
      })
      .fail(function(err){ 
        console.error('[PA][chat] Chat request failed:', err);
        d.reject(err); 
      });
    return d.promise();
  }

  function openContextProgress(){ ensureContextModal(); $('#paContextModal .modal-title').text('Context Progress'); $('#paContextModal').modal('show'); }

  // Context summary open
  function openContextReviewed(){
    var token = window.PA_LAST_CLIENT_TOKEN;
    if (!token){ alert('No context available yet.'); return; }
    ensureContextModal();
    $('#paContextList').empty();
    $('#paContextModal .modal-title').text('Context Summary');
    $('#paContextModal').modal('show');
    $.post(admin_url + 'project_agent/context_progress', { client_token: token })
      .done(function(r){
        var $list = $('#paContextList');
        if (!r || !r.success){ $list.append('<li>Unable to load context.</li>'); return; }
        var last = null;
        (r.events||[]).forEach(function(ev){ if (ev.event==='complete') last = ev; });
        if (last && last.data && last.data.summary){
          var s = last.data.summary;
          $list.append('<li><strong>Project:</strong> ' + (s.project_name || 'N/A') + '</li>');
          $list.append('<li><strong>Tasks:</strong> ' + (s.tasks||0) + '</li>');
          $list.append('<li><strong>Milestones:</strong> ' + (s.milestones||0) + '</li>');
          $list.append('<li><strong>Activities:</strong> ' + (s.activities||0) + '</li>');
        } else {
          $list.append('<li>Context not finalized yet.</li>');
        }
      });
  }
  
  // Dismiss AI banner and set cookie
  function dismissAIBanner() {
    // Set cookie to remember dismissal (expires in 30 days)
    var expires = new Date();
    expires.setTime(expires.getTime() + (30 * 24 * 60 * 60 * 1000));
    document.cookie = "pa_ai_banner_dismissed=1; expires=" + expires.toUTCString() + "; path=/";
    
    // Hide the banner
    $('.alert[role="alert"]').fadeOut();
  }
  
  // Refresh AI status
  function refreshAIStatus() {
    $.get(admin_url + 'project_agent/get_ai_status')
      .done(function(response) {
        if (response.success) {
          // Reload page to update status
          location.reload();
        }
      })
      .fail(function() {
        alert('Failed to refresh AI status');
      });
  }
  
  window.PA_IR = {
    send: startChatWithContext,
    // Deprecated modal/polling methods kept for compat (no-op / internal)
    showContextModal: showContextModal,
    pollContext: function(){},
    pollChat: function(){},
    startChatWithContext: startChatWithContext,
    openContextReviewed: openContextReviewed,
    openContextProgress: openContextProgress,
    pingPusher: function(){
      var t = window.PA_LAST_CLIENT_TOKEN; if (!t) { alert('No token yet'); return; }
      $.post(admin_url + 'project_agent/pusher_ping', { client_token: t }).done(function(r){ console.log('[PA][pusher] ping request sent', r); });
    },
    testPusher: function(){
      var t = window.PA_LAST_CLIENT_TOKEN; 
      if (!t) { 
        alert('No token yet - send a message first'); 
        return; 
      }
      console.log('[PA][pusher] Testing Pusher with token:', t);
      var p = ensurePusher();
      if (!p) {
        alert('Pusher not available');
        return;
      }
      var chName = 'project-agent-' + String(t).replace(/[^a-zA-Z0-9_\-]/g,'_');
      console.log('[PA][pusher] Channel name:', chName);
      var ch = p.subscribe(chName);
      ch.bind('pusher:subscription_succeeded', function(){ 
        console.log('[PA][pusher] Test subscription successful');
        // Send test ping
        $.post(admin_url + 'project_agent/pusher_ping', { client_token: t })
          .done(function(r){ console.log('[PA][pusher] Test ping sent', r); });
      });
      ch.bind('pa_ping', function(ev){ 
        console.log('[PA][pusher] Test ping received!', ev);
        alert('Pusher test successful! Check console for details.');
      });
    },
    testContext: function(){
      var t = window.PA_LAST_CLIENT_TOKEN || 'test-' + Date.now();
      console.log('[PA][context] Testing context building with token:', t);
      
      // Subscribe to events first
      var p = ensurePusher();
      if (p) {
        var chName = 'project-agent-' + String(t).replace(/[^a-zA-Z0-9_\-]/g,'_');
        var ch = p.subscribe(chName);
        ch.bind('pusher:subscription_succeeded', function(){ 
          console.log('[PA][context] Subscribed to context test channel');
          // Now trigger context building
          $.post(admin_url + 'project_agent/test_context', { client_token: t })
            .done(function(r){ 
              console.log('[PA][context] Context test result:', r);
              if (r.success) {
                alert('Context test completed! Check console for events.');
              } else {
                alert('Context test failed: ' + r.error);
              }
            });
        });
      } else {
        // Fallback without Pusher
        $.post(admin_url + 'project_agent/test_context', { client_token: t })
          .done(function(r){ 
            console.log('[PA][context] Context test result:', r);
            alert('Context test completed (no Pusher)! Check console.');
          });
      }
    }
  };
  
  // Global functions
  window.dismissAIBanner = dismissAIBanner;
  window.refreshAIStatus = refreshAIStatus;
  window.clearChat = clearChat;
  window.stopCurrentProcessing = stopCurrentProcessing;
  // Expose session management helpers for inline handlers
  window.loadSessionHistory = loadSessionHistory;
  window.createNewSession = createNewSession;
  // Optional expose for debugging
  window.getUserSessions = getUserSessions;
  window.filterSessionList = filterSessionList;
  window.updateProjectFilter = updateProjectFilter;
  window.renameSession = renameSession;
  window.deleteSession = deleteSession;
  window.restoreLastSession = restoreLastSession;
})();
