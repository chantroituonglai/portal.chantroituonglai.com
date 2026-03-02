(function(){
  if (typeof window.jQuery === 'undefined') { return; }
  var $ = window.jQuery;

  function closeCalc(){
    $('.ppi-mini-calc, .ppi-overlay').remove();
  }

  function t(key){
    if (window.app && app.lang && app.lang[key]) return app.lang[key];
    // fallback English
    var dict = {
      'ppi_adjustment_calculator':'Adjustment Calculator',
      'ppi_current_subtotal':'Current Subtotal',
      'ppi_desired_total':'Desired Total',
      'ppi_discount_optional':'Discount (optional)',
      'ppi_adjustment':'Adjustment',
      'ppi_recalculate':'Recalculate',
      'ppi_close':'Close',
      'ppi_calc_tooltip':'Adjustment calculator'
    };
    return dict[key] || key;
  }

  function renderCalc(targetInput){
    var $overlay = $('<div class="ppi-overlay"></div>').appendTo('body').on('click', closeCalc);
    var $calc = $('<div class="ppi-mini-calc"></div>').appendTo('body');
    $calc.append('<h4>'+t('ppi_adjustment_calculator')+'</h4>');

    // Grid layout: left calculator, right inputs
    var $grid = $('<div class="ppi-grid"></div>').appendTo($calc);
    var $left = $('<div class="ppi-left"></div>').appendTo($grid);
    var $right = $('<div class="ppi-right"></div>').appendTo($grid);

    // Left: simple calculator UI
    var $display = $('<input type="text" class="ppi-calc-display" value="0" readonly />');
    var $clearKey = $('<button type="button" class="ppi-clear-btn">C</button>');
    var $displayRow = $('<div class="ppi-display-row"></div>').append($display).append($clearKey);
    var $pad = $('<div class="ppi-pad"></div>');
    // Arrange keys grid row-major (4 cols). '=' added separately to occupy right side.
    var keys = ['7','8','9','/','4','5','6','*','1','2','3','-','0','00','.','+'];
    keys.forEach(function(k){
      var cls = /[\/*\-+]/.test(k) ? 'op' : '';
      $pad.append('<button type="button" class="'+cls+'" data-k="'+k+'">'+k+'</button>');
    });
    $pad.append('<button type="button" class="eq" data-k="=">=</button>');
    $left.append($displayRow).append($pad);

    // Right: business inputs
    var $subtotal = $('<input type="text" class="form-control" placeholder="'+t('ppi_current_subtotal')+'" />');
    var $desired = $('<input type="text" class="form-control" placeholder="'+t('ppi_desired_total')+'" />');
    var $discount = $('<input type="text" class="form-control" placeholder="'+t('ppi_discount_optional')+'" />');

    var $row1 = $('<div class="ppi-row"></div>').append('<label>'+t('ppi_current_subtotal')+'</label>').append($subtotal);
    var $row2 = $('<div class="ppi-row"></div>').append('<label>'+t('ppi_desired_total')+'</label>').append($desired);
    var $row3 = $('<div class="ppi-row"></div>').append('<label>'+t('ppi_discount_optional')+'</label>').append($discount);
    var $result = $('<div class="ppi-row"></div>').append('<label>'+t('ppi_adjustment')+'</label>').append('<input type="text" class="form-control ppi-result" readonly />');

    var $actions = $('<div class="ppi-actions"></div>');
    var $apply = $('<button type="button" class="btn btn-primary">'+t('ppi_recalculate')+'</button>');
    var $close = $('<button type="button" class="btn btn-default">'+t('ppi_close')+'</button>');
    $actions.append($apply).append($close);

    $right.append($row1,$row2,$row3,$result,$actions);

    function formatPretty(n){
      var dec = (window.app && app.options && app.options.decimal_separator) || '.';
      var th = (window.app && app.options && app.options.thousand_separator) || ',';
      var s = Math.round(n).toString();
      return s.replace(/\B(?=(\d{3})+(?!\d))/g, th);
    }

    function parseNum(v){
      v = (v||'').toString();
      v = v.replace(/[^0-9.,-]/g,'');
      var dec = (window.app && app.options && app.options.decimal_separator) || '.';
      var th = (window.app && app.options && app.options.thousand_separator) || ',';
      // remove thousand
      if (th) { v = v.split(th).join(''); }
      // convert local decimal to dot
      if (dec !== '.') { v = v.split(dec).join('.'); }
      var n = parseFloat(v);
      if (isNaN(n)) return 0; return n;
    }

    function recalc(){
      var subtotal = parseNum($subtotal.val());
      var desired = parseNum($desired.val());
      var discount = parseNum($discount.val());
      var adjustment = desired - (subtotal - discount);
      var noDecimals = Math.round(adjustment);
      console.log('recalc', subtotal, desired, discount, adjustment, noDecimals);
      $calc.find('.ppi-result').val(formatPretty(noDecimals));
      $display.val(formatPretty(noDecimals));
    }

    $subtotal.on('input', recalc); $desired.on('input', recalc); $discount.on('input', recalc);
    recalc();

    $apply.on('click', function(){
      var raw = $calc.find('.ppi-result').val();
      var val = parseNum(raw); // parseNum preserves minus
      if (targetInput && val !== '') {
        var $t = $(targetInput);
        console.log('apply', raw, val, $t.val());
        $t.val(val).trigger('change').trigger('blur');
      }
      closeCalc();
    });
    // Clear functionality (display key)
    $clearKey.on('click', function(){
      // Clear calculator only (do not change form inputs)
      $calc.find('.ppi-result').val('0');
      $display.val('0');
      expr = '0';
    });
    $close.on('click', closeCalc);

    // Calculator interactions
    var expr = '';
    function updateDisplay(v){ $display.val(v); }
    $pad.on('click','button', function(){
      var k = $(this).data('k');
      if (k === '='){
        try { expr = (Function('return '+expr.replace(/[^0-9.+\-*/()]/g,''))()).toString(); } catch(e){ expr = '0'; }
        updateDisplay(expr);
        $desired.val(formatPretty(parseNum(expr))).trigger('input');
      } else {
        if (expr === '0') expr = '';
        expr += k.toString();
        updateDisplay(expr);
      }
    });

    // Prefill subtotal from current form if available
    var subHidden = $('input[name="subtotal"]').last().val();
    if (subHidden) { $subtotal.val(formatPretty(parseFloat(subHidden) || 0)); }

    // Formatting behavior like pretty_price_input: show raw on focus, pretty on blur
    function bindPretty($el){
      $el.on('focus', function(){ var v = parseNum($(this).val()); if (!isNaN(v)) $(this).val(v); });
      $el.on('blur', function(){ var v = parseNum($(this).val()); $(this).val(formatPretty(v)); recalc(); });
    }
    bindPretty($subtotal); bindPretty($desired); bindPretty($discount);
    // Initial calculation
    recalc();
  }

  function ensureButtons(){
    var $templates = $('.accounting-template');
    if (!$templates.length) return;
    $templates.each(function(){
      var $adjInput = $(this).find('input[name="adjustment"]');
      if (!$adjInput.length || $adjInput.data('ppi-has-calc')) return;
      var $btn = $('<button type="button" class="btn btn-default ppi-adjust-btn" title="'+t('ppi_calc_tooltip')+'"><i class="fa-solid fa-calculator"></i></button>');
      $adjInput.after($btn).data('ppi-has-calc', true);
      $btn.on('click', function(){ renderCalc($adjInput); });
    });
  }

  function ready(){ ensureButtons(); }
  if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', ready); } else { ready(); }
  $(document).on('sales-total-calculated', ensureButtons);

  // Observe DOM changes to re-inject button when form re-renders
  try {
    var debounceTimer;
    var obs = new MutationObserver(function(){
      clearTimeout(debounceTimer);
      debounceTimer = setTimeout(ensureButtons, 100);
    });
    obs.observe(document.body, { childList: true, subtree: true });
  } catch(e) {}
})();


