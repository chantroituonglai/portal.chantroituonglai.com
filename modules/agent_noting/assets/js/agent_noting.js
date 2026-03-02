;(function(){
  function adminUrl(path){
    try { return (typeof admin_url !== 'undefined') ? admin_url + path : window.location.origin + '/admin/' + path; } catch(e){ return '/admin/' + path; }
  }

  function $(sel, root){ return (root||document).querySelector(sel); }
  function $all(sel, root){ return Array.prototype.slice.call((root||document).querySelectorAll(sel)); }

  function addButton(nextToEl, onClick){
    if (!nextToEl) return;
    var btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'btn btn-default mleft5 agent-noting-btn';
    var t = (window.AGENT_NOTING_LANG && window.AGENT_NOTING_LANG.add_ai_note) || 'Add AI Note';
    btn.innerHTML = '<i class="fa fa-magic"></i> ' + t;
    btn.addEventListener('click', onClick);
    nextToEl.parentNode.insertBefore(btn, nextToEl.nextSibling);
    return btn;
  }

  function addLanguageSelector(anchorEl){
    var wrapper = document.createElement('span');
    wrapper.className = 'agent-noting-lang mleft5';
    var select = document.createElement('select');
    select.className = 'selectpicker';
    select.style.maxWidth = '140px';
    var L = (window.AGENT_NOTING_LANG && window.AGENT_NOTING_LANG.lang) || {};
    var options = [
      {v:'auto', t: L.auto || 'Auto'},
      {v:'vi', t:   L.vi   || 'Tiếng Việt'},
      {v:'en', t:   L.en   || 'English'},
      {v:'ja', t:   L.ja   || '日本語'},
      {v:'ko', t:   L.ko   || '한국어'},
      {v:'zh', t:   L.zh   || '中文'},
      {v:'fr', t:   L.fr   || 'Français'},
      {v:'de', t:   L.de   || 'Deutsch'},
      {v:'es', t:   L.es   || 'Español'}
    ];
    options.forEach(function(o){ var opt = document.createElement('option'); opt.value=o.v; opt.textContent=o.t; select.appendChild(opt); });
    wrapper.appendChild(select);
    // Insert BEFORE the anchor button, so layout keeps together on right-aligned toolbars
    anchorEl.parentNode.insertBefore(wrapper, anchorEl);
    return select;
  }

  function getEntityFromUrl(){
    // crude detector from URL path e.g. /admin/estimates/list_estimate/123?tab=tab_notes
    var p = window.location.pathname;
    var m = p.match(/\/admin\/(tickets|estimates|invoices|contracts|proposals|projects|tasks|delivery_notes|credit_notes)\/([a-zA-Z_]+)?\/?(\d+)?/);
    if (!m) return { type:null, id:null };
    var t = m[1];
    var id = m[3] ? parseInt(m[3],10) : null;
    return { type:t, id:id };
  }

  function post(url, data){
    return fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
        'X-Requested-With': 'XMLHttpRequest'
      },
      credentials: 'same-origin',
      body: Object.keys(data).map(function(k){ return encodeURIComponent(k)+'='+encodeURIComponent(data[k]); }).join('&')
    }).then(function(r){ return r.json(); });
  }

  function attachForSalesNoteForm(form){
    if (!form || form._agentNotingAttached) return;
    var submitBtn = form.querySelector('button[type="submit"]');
    var textarea = form.querySelector('textarea[name="description"]');
    if (!submitBtn || !textarea) return;
    // Prefer deriving entity from form action (more reliable in split-view)
    var info = { type: null, id: null };
    try {
      var action = form.getAttribute('action') || '';
      // Match: /admin/{entity}/add_note/{id}
      var m = action.match(/\/admin\/([a-z_]+)\/add_note\/(\d+)/);
      if (m) { info.type = m[1]; info.id = parseInt(m[2],10); }
    } catch(e) {}
    if (!info.type || !info.id) {
      // Fallback to URL guesser
      info = getEntityFromUrl();
    }
    var langSelect = addLanguageSelector(submitBtn);
    addButton(submitBtn, function(){
      var originalHtml = submitBtn.innerHTML;
      submitBtn.disabled = true;
      var aiBtn = this; aiBtn.disabled = true; var gen = (window.AGENT_NOTING_LANG && window.AGENT_NOTING_LANG.generating) || 'Generating...'; aiBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> ' + gen;
      post(adminUrl('agent_noting/generate_note'), {
        entity_type: info.type || 'sales',
        entity_id: info.id || '',
        draft_text: textarea.value || '',
        language: (langSelect && langSelect.value) || 'auto'
      }).then(function(resp){
        var errT = (window.AGENT_NOTING_LANG && window.AGENT_NOTING_LANG.ai_error) || 'AI error';
        if (resp && resp.success && resp.note){ textarea.value = resp.note; }
        else if (resp && resp.error){ alert(errT + ': ' + resp.error); }
      }).catch(function(){ var m = (window.AGENT_NOTING_LANG && window.AGENT_NOTING_LANG.failed_contact) || 'Failed to contact AI service'; alert(m); })
      .finally(function(){ submitBtn.disabled = false; aiBtn.disabled = false; var t = (window.AGENT_NOTING_LANG && window.AGENT_NOTING_LANG.add_ai_note) || 'Add AI Note'; aiBtn.innerHTML = '<i class="fa fa-magic"></i> ' + t; });
    });
    form._agentNotingAttached = true;
  }

  function attachForTicketNotes(container){
    if (!container || container._agentNotingAttached) return;
    var addBtn = container.querySelector('a.add_note_ticket');
    var textarea = container.querySelector('textarea[name="note_description"]');
    if (!addBtn || !textarea) return;
    var info = getEntityFromUrl();
    var langSelect = addLanguageSelector(addBtn);
    addButton(addBtn, function(){
      var aiBtn = this; aiBtn.disabled = true; var gen = (window.AGENT_NOTING_LANG && window.AGENT_NOTING_LANG.generating) || 'Generating...'; aiBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> ' + gen;
      post(adminUrl('agent_noting/generate_note'), {
        entity_type: 'tickets',
        entity_id: info.id || '',
        draft_text: textarea.value || '',
        language: (langSelect && langSelect.value) || 'auto'
      }).then(function(resp){
        var errT = (window.AGENT_NOTING_LANG && window.AGENT_NOTING_LANG.ai_error) || 'AI error';
        if (resp && resp.success && resp.note){ textarea.value = resp.note; }
        else if (resp && resp.error){ alert(errT + ': ' + resp.error); }
      }).catch(function(){ var m = (window.AGENT_NOTING_LANG && window.AGENT_NOTING_LANG.failed_contact) || 'Failed to contact AI service'; alert(m); })
      .finally(function(){ aiBtn.disabled = false; var t = (window.AGENT_NOTING_LANG && window.AGENT_NOTING_LANG.add_ai_note) || 'Add AI Note'; aiBtn.innerHTML = '<i class="fa fa-magic"></i> ' + t; });
    });
    container._agentNotingAttached = true;
  }

  function init(){
    // Run initial scan
    scanAndAttach();

    // Re-scan when tabs switch (content may be lazy-rendered)
    try {
      document.addEventListener('shown.bs.tab', function(){ setTimeout(scanAndAttach, 50); });
    } catch(e) {}
    // Also re-scan on click to Notes tab links
    $all('a[href="#tab_notes"], a[data-toggle="tab"]').forEach(function(a){ a.addEventListener('click', function(){ setTimeout(scanAndAttach, 100); }); });

    // Observe DOM changes to catch dynamically injected forms
    try {
      var mo = new MutationObserver(function(){ scanAndAttach(); });
      mo.observe(document.body, { childList: true, subtree: true });
    } catch(e) {}

    // jQuery AJAX loads (Perfex loads preview panes via .load / DataTables)
    try {
      if (window.jQuery) {
        jQuery(document).ajaxComplete(function(){ setTimeout(scanAndAttach, 50); });
      }
    } catch(e) {}

    // Fallback periodic scan for a short time after load
    var attempts = 0; var t = setInterval(function(){
      scanAndAttach(); attempts++; if (attempts > 40) clearInterval(t); // ~4s
    }, 100);
  }

  function scanAndAttach(){
    // Sales-like note forms: estimates, invoices, proposals, contracts, delivery notes
    $all('form.estimate-notes-form, form.invoice-notes-form, form.proposal-notes-form, form.contract-notes-form, form.delivery_note-notes-form').forEach(attachForSalesNoteForm);
    // Tickets tab note panel
    $all('#note').forEach(attachForTicketNotes);
    // Admin note fields on edit pages (invoices/estimates/credit_notes/delivery_notes)
    $all('textarea[name="adminnote"]').forEach(function(textarea){
      if (textarea._agentNotingAttached) return;
      // Insert controls after textarea
      var container = document.createElement('div');
      container.style.marginTop = '6px';
      textarea.parentNode.insertBefore(container, textarea.nextSibling);
      // Derive entity from nearest form action or URL
      var info = getEntityFromUrl();
      try {
        var f = textarea.closest('form');
        if (f && f.getAttribute('action')) {
          var action = f.getAttribute('action');
          // Matches /admin/{entity}/{entity_singular}/{id}
          var m1 = action.match(/\/admin\/(estimates|invoices|credit_notes|delivery_notes)\/(estimate|invoice|credit_note|delivery_note)\/(\d+)/);
          if (m1) { info = { type: m1[1], id: parseInt(m1[3],10) }; }
        } else {
          // Fallback parse from current location
          var p = window.location.pathname;
          var m2 = p.match(/\/admin\/(estimates|invoices|credit_notes|delivery_notes)\/(estimate|invoice|credit_note|delivery_note)\/(\d+)/);
          if (m2) { info = { type: m2[1], id: parseInt(m2[3],10) }; }
        }
      } catch(e) {}
      var langSelect = addLanguageSelector(container);
      addButton(container, function(){
        var btn = this; btn.disabled = true; var gen = (window.AGENT_NOTING_LANG && window.AGENT_NOTING_LANG.generating) || 'Generating...'; btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> ' + gen;
        post(adminUrl('agent_noting/generate_note'), {
          entity_type: info.type || 'sales',
          entity_id: info.id || '',
          draft_text: textarea.value || '',
          language: (langSelect && langSelect.value) || 'auto'
        }).then(function(resp){
          var errT = (window.AGENT_NOTING_LANG && window.AGENT_NOTING_LANG.ai_error) || 'AI error';
          if (resp && resp.success && resp.note){ textarea.value = resp.note; }
          else if (resp && resp.error){ alert(errT + ': ' + resp.error); }
        }).catch(function(){ var m = (window.AGENT_NOTING_LANG && window.AGENT_NOTING_LANG.failed_contact) || 'Failed to contact AI service'; alert(m); })
        .finally(function(){ btn.disabled = false; var t = (window.AGENT_NOTING_LANG && window.AGENT_NOTING_LANG.add_ai_note) || 'Add AI Note'; btn.innerHTML = '<i class="fa fa-magic"></i> ' + t; });
      });
      textarea._agentNotingAttached = true;
    });
  }

  if (document.readyState === 'complete' || document.readyState === 'interactive') {
    setTimeout(init, 0);
  } else {
    document.addEventListener('DOMContentLoaded', init);
  }
})();
