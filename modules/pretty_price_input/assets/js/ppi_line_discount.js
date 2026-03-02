(function(){
  'use strict';

  var lastPreview = { type: 'percent', percent: '', amount: '', tax_mode: 'before_tax' };

  function clampPercent(v){ v = parseFloat(v); if (isNaN(v)) return 0; if (v<0) return 0; if (v>100) return 100; return v; }
  function clampAmount(v){ v = parseFloat((v+'').replace(/[^0-9.\-]/g,'')); if (isNaN(v) || v<0) return 0; return v; }

  function el(tag, cls, text){ var e=document.createElement(tag); if(cls) e.className=cls; if(text!=null) e.textContent=text; return e; }

  function formatMoney(val){
    var n = parseFloat(val); if (isNaN(n)) n = 0;
    if (typeof accounting !== 'undefined') {
      var cur = (window.app && app.options && app.options.currency) || { symbol: app && app.options ? app.options.currency_symbol : '$', placement: app && app.options ? app.options.currency_placement : 'before', decimal: app && app.options ? app.options.decimal_separator : '.', thousand: app && app.options ? app.options.thousand_separator : ',' };
      var keep = { thousand: accounting.settings.number.thousand, decimal: accounting.settings.number.decimal, precision: accounting.settings.currency.precision };
      accounting.settings.number.thousand = cur.thousand || ',';
      accounting.settings.number.decimal = cur.decimal || '.';
      accounting.settings.currency.precision = app && app.options ? app.options.decimal_places : 2;
      var out = accounting.formatMoney(n);
      accounting.settings.number.thousand = keep.thousand; accounting.settings.number.decimal = keep.decimal; accounting.settings.currency.precision = keep.precision;
      return out;
    }
    return (n<0?'-':'') + '$' + Math.abs(n).toFixed(2);
  }

  function buildControls(nameBase, isPreview){
    var wrap = el('div','ppi-line-discount-wrapper');

    var sel = el('select','ppi-discount-type form-control input-sm');
    var optP = el('option',null, (window.app && app.lang && app.lang.ppi_discount_type_percent) || 'Percent'); optP.value='percent';
    var optA = el('option',null, (window.app && app.lang && app.lang.ppi_discount_type_amount) || 'Amount'); optA.value='amount';
    sel.appendChild(optP); sel.appendChild(optA);

    var inputPct = el('input','ppi-line-discount form-control input-sm');
    inputPct.type='number'; inputPct.min='0'; inputPct.max='100'; inputPct.step='0.01';
    if (!isPreview && nameBase) inputPct.name = nameBase+'[ppi_discount_percent]';
    inputPct.placeholder='';

    var inputAmt = el('input','ppi-line-discount-amount form-control input-sm');
    inputAmt.type='number'; inputAmt.min='0'; inputAmt.step='0.01';
    if (!isPreview && nameBase) inputAmt.name = nameBase+'[ppi_discount_amount]';
    inputAmt.placeholder=''; inputAmt.style.display='none';

    var mode = el('select','ppi-tax-mode form-control input-sm');
    var m1 = el('option',null,(window.app && app.lang && app.lang.ppi_mode_before_tax)||'before tax'); m1.value='before_tax';
    var m2 = el('option',null,(window.app && app.lang && app.lang.ppi_mode_after_tax)||'after tax'); m2.value='after_tax';
    if (!isPreview && nameBase) mode.name = nameBase+'[ppi_tax_mode]';
    mode.appendChild(m1); mode.appendChild(m2);

    var note = el('div','ppi-line-discount-note');

    function sync(){
      var t = sel.value;
      inputPct.style.display = t==='percent' ? '' : 'none';
      inputAmt.style.display = t==='amount' ? '' : 'none';
      if (isPreview) {
        lastPreview.type = t;
        lastPreview.percent = inputPct.value;
        lastPreview.amount = inputAmt.value;
        lastPreview.tax_mode = mode.value;
      }
      if (typeof window.calculate_total === 'function') window.calculate_total();
    }

    sel.addEventListener('change', sync);
    inputPct.addEventListener('change', function(){ inputPct.value = clampPercent(inputPct.value); sync(); });
    inputAmt.addEventListener('change', function(){ inputAmt.value = clampAmount(inputAmt.value); sync(); });
    mode.addEventListener('change', sync);

    // Desired order: Input -> Type -> Tax Mode -> Note
    wrap.appendChild(inputPct);
    wrap.appendChild(inputAmt);
    wrap.appendChild(sel);
    wrap.appendChild(mode);
    wrap.appendChild(note);

    return { wrap: wrap, type: sel, percent: inputPct, amount: inputAmt, taxMode: mode, note: note };
  }

  function findDescInputName(row){
    var el = row.querySelector('input[name^="items"][name$="[description]"]'); if (el) return el.name;
    el = row.querySelector('input[name^="newitems"][name$="[description]"]'); if (el) return el.name;
    return null;
  }
  function parseItemRefFromName(descName){
    if (!descName) return null;
    var m = descName.match(/^(items)\[(\d+)\]\[description\]$/); if (m) return { kind:'items', id:m[2] };
    var n = descName.match(/^(newitems)\[([^\]]+)\]\[description\]$/); if (n) return { kind:'newitems', key:n[2] };
    return null;
  }

  function ensureDiscountHeader(){
    var table = document.querySelector('.accounting-template table.items'); if (!table) return;
    var thead = table.querySelector('thead tr'); if (!thead) return;
    if (thead.querySelector('th.ppi-discount-col')) return;

    // Prefer placing before Tax column when found, otherwise after Rate
    var headers = Array.prototype.slice.call(thead.children);
    var taxIdx = headers.findIndex(function(th){ var t=(th.innerText||'').trim().toLowerCase(); return t==='tax' || t.indexOf('tax')!==-1; });
    var rateIdx = headers.findIndex(function(th){ var t=(th.innerText||'').trim().toLowerCase(); return t==='rate' || t.indexOf('rate')!==-1; });

    // shrink tax column and mark body cells
    if (taxIdx !== -1 && thead.children[taxIdx]) {
      thead.children[taxIdx].classList.add('ppi-tax-col');
      var bodyRows = thead.parentNode.parentNode.querySelectorAll('tbody tr');
      bodyRows.forEach(function(r){ if (r.children[taxIdx]) r.children[taxIdx].classList.add('ppi-tax-col'); });
    }

    var label = 'Item discount';
    var th = el('th','ppi-discount-col', label); th.style.textAlign='right';
    if (taxIdx !== -1 && thead.children[taxIdx]) {
      thead.insertBefore(th, thead.children[taxIdx]);
    } else if (rateIdx !== -1 && thead.children[rateIdx] && thead.children[rateIdx].nextSibling) {
      thead.insertBefore(th, thead.children[rateIdx].nextSibling);
    } else {
      thead.appendChild(th);
    }
  }

  function insertDiscountCell(row){
    if (row.querySelector('td.ppi-discount-col')) return null;
    var table = row.closest('table');
    var rateHeaderIdx = -1;
    if (table) {
      var ths = table.querySelectorAll('thead tr th');
      for (var i=0;i<ths.length;i++){
        var txt = (ths[i].innerText||'').trim().toLowerCase();
        if (txt === 'rate' || txt.indexOf('rate') !== -1){ rateHeaderIdx = i; break; }
      }
    }
    var tds = row.children; var rateIdx = -1;
    for (var j=0;j<tds.length;j++){ var hasClass = (tds[j].className||'').indexOf('rate')!==-1; if (hasClass) { rateIdx = j; break; } }
    if (rateIdx === -1 && rateHeaderIdx !== -1 && rateHeaderIdx < tds.length){ rateIdx = rateHeaderIdx; }
    var td = el('td','ppi-discount-col'); td.style.textAlign='right';
    if (rateIdx>=0 && tds[rateIdx] && tds[rateIdx].nextSibling) { row.insertBefore(td, tds[rateIdx].nextSibling); } else { row.appendChild(td); }
    return td;
  }

  function addControlsToRows(){
    ensureDiscountHeader();
    var rows = document.querySelectorAll('.accounting-template table.items tbody tr.item');
    rows.forEach(function(row){
      if (row.classList.contains('main')) return;
      if (row.querySelector('.ppi-line-discount-wrapper')) return;
      var descName = findDescInputName(row);
      var ref = parseItemRefFromName(descName);
      var nameBase = '';
      if (ref) nameBase = ref.kind==='items' ? ('items['+ref.id+']') : ('newitems['+ref.key+']');
      var cell = insertDiscountCell(row); if (!cell) return;
      var c = buildControls(nameBase, false);
      if (ref && ref.kind==='newitems'){
        c.type.value = lastPreview.type || 'percent';
        c.percent.value = lastPreview.percent || '';
        c.amount.value = lastPreview.amount || '';
        c.taxMode.value = lastPreview.tax_mode || 'before_tax';
      }
      cell.appendChild(c.wrap);
      c.type.dispatchEvent(new Event('change'));
    });
  }

  function addControlsToPreview(){
    ensureDiscountHeader();
    var preview = document.querySelector('.accounting-template .main');
    if (!preview) return; if (preview.querySelector('.ppi-line-discount-wrapper')) return;
    var cell = insertDiscountCell(preview); if (!cell) return;
    var c = buildControls('', true);
    cell.appendChild(c.wrap);
  }

  function computeRowDiscount(row){
    var cwrap = row.querySelector('.ppi-line-discount-wrapper'); if (!cwrap) return 0;
    var typeEl = cwrap.querySelector('.ppi-discount-type'); var pctEl = cwrap.querySelector('.ppi-line-discount'); var amtEl = cwrap.querySelector('.ppi-line-discount-amount'); var modeEl = cwrap.querySelector('.ppi-tax-mode');
    if (!typeEl || !modeEl) return 0;
    var qtyEl = row.querySelector('[data-quantity], input[name*="[qty]"]');
    var rateEl = row.querySelector('td.rate input[name*="[rate]"]');
    var qty = qtyEl ? parseFloat((qtyEl.value+'').replace(/[^0-9.\-]/g,'')) : 0;
    var rate = rateEl ? parseFloat((rateEl.value+'').replace(/[^0-9.\-]/g,'')) : 0;
    if (isNaN(qty)) qty = 0; if (isNaN(rate)) rate = 0;
    var subtotal = qty * rate;
    var taxes = 0;
    var taxSelects = row.querySelectorAll('select.tax');
    taxSelects.forEach(function(sel){ var parts = (sel.value||'').split('|'); var r = parseFloat(parts[1]); if(!isNaN(r)) taxes += (subtotal/100)*r; });
    var base = modeEl.value === 'after_tax' ? (subtotal + taxes) : subtotal;
    var disc = 0;
    if (typeEl.value === 'percent') { var p = clampPercent(pctEl.value); disc = base * (p/100); }
    else { var a = clampAmount(amtEl.value); disc = Math.min(a, base); }
    return disc;
  }

  function updatePerRowNotes(){
    var rows = document.querySelectorAll('.accounting-template table.items tbody tr');
    rows.forEach(function(row){
      var cwrap = row.querySelector('.ppi-line-discount-wrapper'); if (!cwrap) return;
      var note = cwrap.querySelector('.ppi-line-discount-note'); if (!note) return;
      var val = computeRowDiscount(row);
      note.textContent = val>0 ? formatMoney(val) : '';
    });
  }

  function applyAfterTaxAdjustment(){
    var rows = document.querySelectorAll('.accounting-template table.items tbody tr');
    var afterTaxTotal = 0;
    rows.forEach(function(row){
      var cwrap = row.querySelector('.ppi-line-discount-wrapper'); if (!cwrap) return;
      var modeEl = cwrap.querySelector('.ppi-tax-mode'); if (!modeEl || modeEl.value !== 'after_tax') return;
      afterTaxTotal += computeRowDiscount(row);
    });

    var adjInput = document.querySelector('input[name="adjustment"]'); if (!adjInput) return;
    var base = adjInput.getAttribute('data-ppi-user-adjustment');
    if (base == null) { adjInput.setAttribute('data-ppi-user-adjustment', adjInput.value||''); base = adjInput.value||''; }
    var baseVal = parseFloat((base+'').replace(/[^0-9.\-]/g,'')); if (isNaN(baseVal)) baseVal = 0;
    var finalAdj = baseVal - afterTaxTotal;
    adjInput.value = (Math.round(finalAdj*100)/100).toFixed(2);
  }

  // Aggregate BEFORE TAX line discounts into document Discount (fixed, before_tax)
  function applyBeforeTaxAsDocumentDiscount(){
    var rows = document.querySelectorAll('.accounting-template table.items tbody tr');
    var beforeTaxTotal = 0;
    rows.forEach(function(row){
      var cwrap = row.querySelector('.ppi-line-discount-wrapper'); if (!cwrap) return;
      var modeEl = cwrap.querySelector('.ppi-tax-mode'); if (!modeEl || modeEl.value !== 'before_tax') return;
      beforeTaxTotal += computeRowDiscount(row);
    });
    var discountTotalInput = document.querySelector('input[name="discount_total"]');
    var discountTypeSelect = document.querySelector('select[name="discount_type"]');
    if (discountTotalInput) {
      var dp = (window.app && app.options ? app.options.decimal_places : 2);
      discountTotalInput.value = (Math.round(beforeTaxTotal * Math.pow(10,dp))/Math.pow(10,dp)).toFixed(dp);
    }
    if (discountTypeSelect) {
      discountTypeSelect.value = beforeTaxTotal > 0 ? 'before_tax' : (discountTypeSelect.value || '');
    }
    if (typeof window.jQuery !== 'undefined') {
      var $ = window.jQuery;
      var $fixed = $('.discount-type-fixed');
      var $percent = $('.discount-type-percent');
      if ($fixed.length) { $fixed.addClass('selected'); }
      if ($percent.length) { $percent.removeClass('selected'); }
    }
  }

  function wrapCalculateTotal(){
    if (!window.calculate_total || window.calculate_total.__ppiLineWrappedV2) return;
    var original = window.calculate_total;
    function pre(){
      // Do not touch item rate inputs; instead drive document discounts/adjustment
      applyBeforeTaxAsDocumentDiscount();
      applyAfterTaxAdjustment();
    }
    function post(){
      // After-tax already applied in pre; just refresh row notes
      updatePerRowNotes();
    }
    window.calculate_total = function(){ pre(); try { return original.apply(this, arguments); } finally { post(); } };
    window.calculate_total.__ppiLineWrappedV2 = true;
  }

  function prefillExisting(){
    if (typeof window.jQuery === 'undefined') return; var $ = window.jQuery; if (typeof window.admin_url === 'undefined') return;
    var ids = [];
    document.querySelectorAll('.accounting-template table.items tbody tr').forEach(function(row){ var name = findDescInputName(row); var ref = parseItemRefFromName(name); if (ref && ref.kind==='items') ids.push(parseInt(ref.id,10)); });
    if (!ids.length) return;
    $.post(window.admin_url + 'pretty_price_input/get_discounts', { ids: ids })
      .done(function(resp){ try{ if (typeof resp==='string') resp = JSON.parse(resp);}catch(e){}
        if (!resp || resp.success!==true || !resp.data) return;
        document.querySelectorAll('.accounting-template table.items tbody tr').forEach(function(row){
          var name = findDescInputName(row); var ref = parseItemRefFromName(name); if (!ref || ref.kind!=='items') return;
          var cwrap = row.querySelector('.ppi-line-discount-wrapper'); if (!cwrap) return;
          var typeEl = cwrap.querySelector('.ppi-discount-type'); var pctEl = cwrap.querySelector('.ppi-line-discount'); var amtEl = cwrap.querySelector('.ppi-line-discount-amount'); var modeEl = cwrap.querySelector('.ppi-tax-mode');
          var d = resp.data[ref.id]; if (!d) return;
          if (d.type) typeEl.value = d.type;
          if (d.percent!=null) pctEl.value = d.percent;
          if (d.amount!=null) amtEl.value = d.amount;
          if (d.tax_mode) modeEl.value = d.tax_mode;
          typeEl.dispatchEvent(new Event('change'));
        });
        updatePerRowNotes();
      });
  }

  function init(){
    addControlsToRows();
    addControlsToPreview();
    prefillExisting();
    wrapCalculateTotal();

    // Perfex event bindings similar to reference implementation
    if (typeof window.jQuery !== 'undefined') {
      var $ = window.jQuery;
      $(document).on('item-added-to-table', function(){ setTimeout(function(){ addControlsToRows(); if (window.calculate_total) window.calculate_total(); }, 0); });
      $(document).on('sales-total-calculated', function(){ setTimeout(function(){ updatePerRowNotes(); }, 100); });
      $(document).on('change keyup blur', '.ppi-line-discount, .ppi-line-discount-amount, .ppi-discount-type, .ppi-tax-mode', function(){ if (window.calculate_total) window.calculate_total(); });
    }

    document.addEventListener('item-added-to-preview', function(){ setTimeout(function(){ addControlsToPreview(); }, 1); });
    var observer = new MutationObserver(function(){ addControlsToRows(); });
    var table = document.querySelector('.accounting-template table.items tbody'); if (table) observer.observe(table, { childList:true, subtree:true });
  }

  if (document.readyState==='loading') document.addEventListener('DOMContentLoaded', init); else init();
})();
