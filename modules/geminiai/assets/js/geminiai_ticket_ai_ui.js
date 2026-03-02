(function(){
  function onReady(fn){ if(document.readyState!=='loading'){fn();} else {document.addEventListener('DOMContentLoaded',fn);} }

  function buildLangMenu(){
    return [
      {id:'auto',name:'Auto'},
      {id:'vi',name:'Tiếng Việt'},
      {id:'en',name:'English'},
      {id:'ja',name:'日本語'},
      {id:'ko',name:'한국어'},
      {id:'zh',name:'中文'},
      {id:'fr',name:'Français'},
      {id:'de',name:'Deutsch'},
      {id:'es',name:'Español'}
    ];
  }

  function addLanguageSwitch(){
    console.log('addLanguageSwitch');
    var modal = document.getElementById('ai-summary-modal');
    if(!modal) return;
    var footer = modal.querySelector('.modal-footer');
    if(!footer || document.getElementById('geminiai-summary-lang-btn')) return;

    var group = document.createElement('div');
    group.className = 'btn-group dropup pull-left';

    var btn = document.createElement('button');
    btn.type = 'button';
    btn.id = 'geminiai-summary-lang-btn';
    btn.className = 'btn btn-default dropdown-toggle';
    btn.setAttribute('data-toggle','dropdown');
    btn.innerHTML = 'Chọn ngôn ngữ khác <span class="caret"></span>';

    var menu = document.createElement('ul');
    menu.className = 'dropdown-menu';

    buildLangMenu().forEach(function(o){
      var li = document.createElement('li');
      var a = document.createElement('a');
      a.href = '#';
      a.textContent = o.name;
      a.setAttribute('data-lang', o.id);
      li.appendChild(a);
      menu.appendChild(li);
    });

    group.appendChild(btn);
    group.appendChild(menu);
    footer.insertBefore(group, footer.firstChild);

    menu.addEventListener('click', function(e){
      var target = e.target;
      if(target && target.getAttribute('data-lang')){
        e.preventDefault();
        var lang = target.getAttribute('data-lang');
        var modalBody = modal.querySelector('.modal-body');
        var ticketId = $('input[name="ticketid"]').val();
        if(!ticketId){ return; }
        modalBody.innerHTML = '<p class="text-muted">Translating summary...</p>';
        $.get(admin_url + 'ai_tickets/summarize_ticket/' + ticketId + '?lang=' + encodeURIComponent(lang))
          .done(function(resp){
            try{ var j = typeof resp==='string' ? JSON.parse(resp) : resp; if(j && j.success){ modalBody.innerHTML = j.message; } else { modalBody.innerHTML = '<p class="text-danger">Failed to load summary</p>'; } }
            catch(e){ modalBody.innerHTML = resp; }
          })
          .fail(function(){ modalBody.innerHTML = '<p class="text-danger">Request failed</p>'; });
      }
    });
  }

  onReady(function(){
    $(document).on('shown.bs.modal', '#ai-summary-modal', function(){
      addLanguageSwitch();
    });
  });
})();