(function () {
  if (typeof window.jQuery === 'undefined') { return; }
  var $ = window.jQuery;
  var PPI_DECIMALS = 0; // force no decimals for display and submission

  function getSeparators() {
    var decimal = (window.app && app.options && app.options.decimal_separator) || '.';
    var thousand = (window.app && app.options && app.options.thousand_separator) || ',';
    return { decimal: decimal, thousand: thousand };
  }

  function normalizeNumericInput(value) {
    var seps = getSeparators();
    if (value == null) { return ''; }
    value = (value + '').trim();
    if (value === '') { return ''; }
    // Remove all non-digits except decimal separator and minus sign
    var regexNonDigit = new RegExp('[^0-9\\' + seps.decimal + '\\-]', 'g');
    value = value.replace(regexNonDigit, '');
    // Keep only one leading minus sign
    var isNegative = value.indexOf('-') !== -1;
    value = value.replace(/-/g, '');
    if (isNegative) { value = '-' + value; }
    // Replace locale decimal with dot for JS parse
    if (seps.decimal !== '.') {
      var r = new RegExp('\\' + seps.decimal, 'g');
      value = value.replace(r, '.');
    }
    // Keep only first decimal point then trim precision according to PPI_DECIMALS
    var firstDot = value.indexOf('.');
    if (firstDot !== -1) {
      var left = value.slice(0, firstDot);
      var right = value.slice(firstDot + 1).replace(/[.]/g, '');
      if (PPI_DECIMALS <= 0) {
        value = left;
      } else {
        right = right.substring(0, PPI_DECIMALS);
        value = left + '.' + right;
      }
    }
    return value;
  }

  function formatPretty(value) {
    var seps = getSeparators();
    if (value === '' || isNaN(value)) { return ''; }
    var num = parseFloat(value);
    if (typeof accounting !== 'undefined') {
      // Use accounting number formatting with app defaults
      var keep = { thousand: accounting.settings.number.thousand, decimal: accounting.settings.number.decimal };
      accounting.settings.number.thousand = seps.thousand;
      accounting.settings.number.decimal = seps.decimal;
      var out = accounting.formatNumber(num, PPI_DECIMALS);
      accounting.settings.number.thousand = keep.thousand;
      accounting.settings.number.decimal = keep.decimal;
      return out;
    }
    // Fallback simple formatting
    var negative = num < 0;
    var abs = PPI_DECIMALS <= 0 ? Math.round(Math.abs(num)) : Math.abs(num).toFixed(PPI_DECIMALS);
    var parts = (abs + '').split('.');
    parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, seps.thousand);
    var formatted = PPI_DECIMALS <= 0 ? parts[0] : parts.join(seps.decimal);
    return negative ? ('-' + formatted) : formatted;
  }

  function bindInputs(context) {
    var $ctx = context ? $(context) : $(document);
    // Target: line rate, quantity, discount, adjustment and any number text/number inputs in accounting templates
    var $inputs = $ctx.find('.accounting-template input[type="number"], .accounting-template td.rate input, .accounting-template [data-quantity], input[name="discount_total"], input[name="discount_percent"], input[name="adjustment"], .ppi-target');

    $inputs.each(function () {
      var $input = $(this);
      if ($input.data('ppi-bound')) { return; }
      $input.attr('inputmode', 'decimal');
      $input.addClass('ppi-input');
      $input.data('ppi-bound', true);

      // Always use text during editing to allow locale characters (e.g., comma) and avoid browser number restrictions
      if ($input.attr('type') === 'number') {
        $input.attr('data-ppi-prev-type', 'number');
        $input.attr('type', 'text');
      }

      // Only prettify after user finishes typing (on blur). Do not prettify while typing.
      $input.on('blur', function () {
        var raw = $input.val();
        var normalized = normalizeNumericInput(raw);
        var pretty = formatPretty(normalized);
        if (pretty !== '') { $input.val(pretty); }
        // Trigger total recalculation
        setTimeout(function () { $input.trigger('change'); }, 0);
      });

      // On focus, keep text type but show raw normalized for easier editing
      $input.on('focus', function () {
        var normalized = normalizeNumericInput($input.val());
        if (normalized !== '') { $input.val(normalized); }
      });
    });
  }

  function onDocumentReady() {
    // Force accounting to 0 precision globally for numbers and currency on sales pages
    if (typeof accounting !== 'undefined') {
      accounting.settings.number.precision = PPI_DECIMALS;
      accounting.settings.currency.precision = PPI_DECIMALS;
    }
    bindInputs(document);
    // Re-bind when new row/item added dynamically
    $(document).on('click', '.add_item_btn, .btn-add-to-invoice', function () {
      setTimeout(function () { bindInputs(document); }, 50);
    });
    // When currency changed, reformat
    $(document).on('sales-total-calculated', function () { bindInputs(document); });

    // Normalize on form submit to ensure server receives proper numbers
    $(document).on('submit', 'form', function () {
      var $form = $(this);
      if (!$form.find('.accounting-template').length) { return; }
      var selectors = '.accounting-template td.rate input, .accounting-template [data-quantity], input[name="discount_total"], input[name="discount_percent"], input[name="adjustment"]';
      $(selectors).each(function(){
        var $el = $(this);
        var normalized = normalizeNumericInput($el.val());
        if (normalized !== '') { $el.val(normalized); }
      });
    });

    // Discount warning: show if value entered and no discount_type selected (Perfex applies discount only when type chosen)
    function showDiscountWarning(){
      var $tmpl = $('.accounting-template');
      if (!$tmpl.length) return;
      var $type = $tmpl.find('select[name="discount_type"]');
      var hasType = $type.length && ($type.val() || '') !== '';
      var $input = $tmpl.find('input[name="discount_total"]');
      var val = parseFloat(normalizeNumericInput($input.val() || '0')) || 0;
      var $existing = $tmpl.find('.ppi-discount-warning');
      if (val !== 0 && !hasType){
        if (!$existing.length){
          $input.closest('.form-group, td').append('<div class="ppi-discount-warning">Please select discount type (Before/After tax) to apply the discount.</div>');
        }
      } else {
        $existing.remove();
      }
    }
    $(document).on('change keyup blur', 'input[name="discount_total"], select[name="discount_type"]', showDiscountWarning);
    showDiscountWarning();

    // Prefill handling: when an item is selected from the items dropdown, the rate is populated programmatically.
    // Format it immediately (no decimals) without waiting for blur.
    function ppiFormatPrefillRate(){
      // Try main preview row input first (exists right under the add item controls)
      var $targets = $('.accounting-template .main input[name="rate"]');
      console.log('ppiFormatPrefillRate', $targets);
      if ($targets.length === 0) {
        $targets = $('.accounting-template td.rate input');
      }
      $targets.each(function(){
        var $el = $(this);
        console.log('ppiFormatPrefillRate', $el);
        var normalized = normalizeNumericInput($el.val());
        if (normalized === '') { return; }
        $el.val(formatPretty(normalized)).trigger('change');
      });
    }
    // Run after core fills preview values
    $(document).on('item-added-to-preview', function(){ setTimeout(ppiFormatPrefillRate, 1); });
    // Fallbacks if event missed
    $(document).on('changed.bs.select', 'select[name="item_select"]', function () { setTimeout(ppiFormatPrefillRate, 50); });
    $(document).on('change', 'select[name="item_select"]', function () { setTimeout(ppiFormatPrefillRate, 50); });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', onDocumentReady);
  } else {
    onDocumentReady();
  }

  // Normalize inputs just before core total calculation, then restore pretty display afterwards
  function ppiWrapCalculateTotal() {
    if (!window.calculate_total || window.calculate_total.__ppiWrapped) { return; }
    var original = window.calculate_total;
    var selectors = '.accounting-template td.rate input, .accounting-template [data-quantity], input[name="discount_total"], input[name="discount_percent"], input[name="adjustment"]';
    function preNormalize() {
      $(selectors).each(function () {
        var $el = $(this);
        var raw = $el.val();
        $el.data('ppi-display', raw);
        var normalized = normalizeNumericInput(raw);
        if (normalized !== '') {
          $el.val(normalized); // use dot decimal for JS math
        }
      });
    }
    function postRestore() {
      $(selectors).each(function () {
        var $el = $(this);
        var display = $el.data('ppi-display');
        if (typeof display !== 'undefined') {
          var pretty = formatPretty(normalizeNumericInput(display));
          $el.val(pretty || display);
        } else {
          var normalized = normalizeNumericInput($el.val());
          var pretty = formatPretty(normalized);
          if (pretty !== '') { $el.val(pretty); }
        }
      });
    }
    window.calculate_total = function () {
      preNormalize();
      try {
        return original.apply(this, arguments);
      } finally {
        postRestore();
      }
    };
    window.calculate_total.__ppiWrapped = true;
  }

  // try to wrap immediately and also after scripts load
  if (typeof window.calculate_total === 'function') { ppiWrapCalculateTotal(); }
  var wrapInterval = setInterval(function(){
    if (typeof window.calculate_total === 'function') { ppiWrapCalculateTotal(); clearInterval(wrapInterval); }
  }, 300);

  // Ensure preview values used when adding item are normalized (rate/qty) so core add_item_to_table does not zero them
  function ppiWrapGetItemPreviewValues(){
    if (!window.get_item_preview_values || window.get_item_preview_values.__ppiWrapped) { return; }
    var original = window.get_item_preview_values;
    window.get_item_preview_values = function(){
      var res = original.apply(this, arguments) || {};
      if (res && typeof res.rate !== 'undefined') {
        var n = normalizeNumericInput(res.rate);
        if (n !== '') { res.rate = n; }
      }
      if (res && typeof res.quantity !== 'undefined') {
        var q = normalizeNumericInput(res.quantity);
        if (q !== '') { res.quantity = q; }
      }
      return res;
    };
    window.get_item_preview_values.__ppiWrapped = true;
  }
  if (typeof window.get_item_preview_values === 'function') { ppiWrapGetItemPreviewValues(); }
  var wrapPrevInterval = setInterval(function(){
    if (typeof window.get_item_preview_values === 'function') { ppiWrapGetItemPreviewValues(); clearInterval(wrapPrevInterval); }
  }, 300);
})();


