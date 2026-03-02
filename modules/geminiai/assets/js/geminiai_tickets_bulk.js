(function(){
  function onReady(fn){ if(document.readyState!=='loading'){fn();} else {document.addEventListener('DOMContentLoaded',fn);} }

  function isTicketsList(){
    return document.querySelector('.table-tickets') && document.getElementById('tickets_bulk_actions');
  }

  function insertBulkButton(){
    var modalBody = document.querySelector('#tickets_bulk_actions .modal-body');
    if(!modalBody || document.getElementById('geminiai-bulk-checkbox')) return;

    var wrapper = document.createElement('div');
    wrapper.className = 'checkbox checkbox-primary';
    wrapper.innerHTML = '<input type="checkbox" id="geminiai-bulk-checkbox">'
      + '<label for="geminiai-bulk-checkbox">Classify with Gemini</label>';
    modalBody.insertBefore(wrapper, modalBody.firstChild);
  }

  function collectSelectedTicketIds(){
    var rows = document.querySelectorAll('.table-tickets tbody tr');
    var ids = [];
    rows.forEach(function(row){
      var cb = row.querySelector('td input[type="checkbox"]');
      if(cb && cb.checked){ ids.push(cb.value); }
    });
    return ids;
  }

  function showModal(){
    $('#geminiai_classify_modal').one('shown.bs.modal', function(){
      // elevate backdrop behind our modal
      $('.modal-backdrop').addClass('geminiai-backdrop');
    }).modal('show');
    updateProgress(0, 1);
    document.getElementById('geminiai-results-body').innerHTML = '';
  }

  function updateProgress(done, total){
    var pct = total>0 ? Math.round(done*100/total) : 0;
    var bar = document.getElementById('geminiai-progress-bar');
    if(bar){ bar.style.width = pct+'%'; bar.textContent = pct+'%'; }
    var text = document.getElementById('geminiai-progress-text');
    if(text){ text.textContent = done + ' / ' + total; }
  }

  function appendResult(r){
    var tbody = document.getElementById('geminiai-results-body');
    if(!tbody) return;
    var tr = document.createElement('tr');
    var status = r.error ? ('Error: '+r.error) : 'OK';
    tr.innerHTML = '<td>'+ (r.id||'') +'</td>'
      + '<td>'+ (r.category||'') +'</td>'
      + '<td>'+ (r.priority||'') +'</td>'
      + '<td>'+ (typeof r.score!=='undefined' && r.score!==null ? r.score : '') +'</td>'
      + '<td>'+ status +'</td>';
    tbody.appendChild(tr);
  }

  function finishFlow(){
    // Close both modals if open and refresh the list
    try { $('#geminiai_classify_modal').modal('hide'); } catch(e){}
    try { $('#tickets_bulk_actions').modal('hide'); } catch(e){}
    // Give the modal a moment to close before reloading
    setTimeout(function(){
      // Prefer DataTables ajax reload if available, fallback to full reload
      var tbl = $('.table-tickets').DataTable ? $('.table-tickets').DataTable() : null;
      if(tbl && tbl.ajax){ try { tbl.ajax.reload(null, false); return; } catch(e){} }
      window.location.reload();
    }, 400);
  }

  function classify(ids){
    if(!ids || !ids.length){ return; }
    showModal();
    var total = ids.length; var done = 0;
    updateProgress(done,total);

    // Process in small batches to keep response snappy
    var batchSize = 5;
    function nextBatch(start){
      var slice = ids.slice(start, start+batchSize);
      if(slice.length===0){ return; }
      $.post(admin_url + 'geminiai/bulk_classify', { ids: slice })
        .done(function(resp){
          if(resp && resp.success === false && resp.message){
            slice.forEach(function(id){ appendResult({id:id, error:resp.message}); done++; updateProgress(done,total); });
          } else if(resp && resp.results){
            resp.results.forEach(function(r){ appendResult(r); done++; updateProgress(done,total); });
          } else {
            slice.forEach(function(id){ appendResult({id:id, error:'No response'}); done++; updateProgress(done,total); });
          }
          if(start + batchSize < ids.length){
            nextBatch(start + batchSize);
          } else if(done >= total){
            finishFlow();
          }
        })
        .fail(function(xhr){
          var msg = 'Request failed';
          try { if(xhr && xhr.responseText){ var j = JSON.parse(xhr.responseText); if(j && j.message){ msg = j.message; } } } catch(e){}
          slice.forEach(function(id){ appendResult({id:id, error:msg}); done++; updateProgress(done,total); });
          if(start + batchSize < ids.length){
            nextBatch(start + batchSize);
          } else if(done >= total){
            finishFlow();
          }
        });
    }
    nextBatch(0);
  }

  function hijackConfirmButton(){
    // Hook into the existing bulk action confirm
    if(typeof window.tickets_bulk_action !== 'function'){ return; }
    var orig = window.tickets_bulk_action;
    window.tickets_bulk_action = function(btn){
      var chk = document.getElementById('geminiai-bulk-checkbox');
      if(chk && chk.checked){
        var ids = collectSelectedTicketIds();
        if(ids.length===0){ return; }
        // prevent default core bulk submit, run our flow instead
        classify(ids);
        return false;
      }
      return orig.apply(this, arguments);
    }
  }

  onReady(function(){
    if(!isTicketsList()) return;
    insertBulkButton();
    hijackConfirmButton();
  });
})();

