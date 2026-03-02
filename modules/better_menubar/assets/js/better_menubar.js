/**
 * Better Menubar JS: robust JS controller to size/position admin sidebar & setup panel.
 * - Avoids content height following sidebar height
 * - Adapts on core changes via observers
 */
(function () {
  var docEl = document.documentElement;
  var observers = { resize: null, mutMenu: null, mutSetup: null, mutWrapper: null, hdrResize: null, mutBody: null };
  var options = {
    mode: 'fixed', // default to fixed to keep wrapper from shifting
    headerSelector: '#header',
    sidebarSelector: '#menu',
    sideMenuListSelector: '#side-menu',
    setupSelector: '#setup-menu-wrapper',
    wrapperSelector: '#wrapper',
    applySetup: true,
    headerOffset: true,
    pinnedPanel: true,
    fixHeader: false,
    reapplyDelay: 80 // ms
  };

  function getHeaderHeight() {
    var $h = $(options.headerSelector);
    var h = $h.length ? $h.outerHeight() : 57;
    return (typeof h === 'number' && h > 0) ? h : 57;
  }

  function setCSSVar(name, value) {
    try { docEl.style.setProperty(name, value); } catch (e) {}
  }

  function isHeaderFixed() {
    try {
      var el = document.querySelector(options.headerSelector);
      if (!el) return false;
      var cs = window.getComputedStyle(el);
      return cs && (cs.position === 'fixed' || cs.position === 'sticky');
    } catch (e) { return false; }
  }

  function applyBetterLayout() {
    // Fix header if configured
    try {
      var $header = $(options.headerSelector);
      if (options.fixHeader && $header.length) {
        $header.css({ position: 'fixed', top: 0, left: 0, right: 0, width: '100%', zIndex: 1000 });
      } else if ($header.length) {
        // remove inline to fallback to core CSS
        $header.attr('style','');
      }
    } catch (e) {}

    var HH = getHeaderHeight();
    setCSSVar('--bm-header-h', HH + 'px');
    try {
      var $menu = $(options.sidebarSelector);
      var $setup = $(options.setupSelector);
      var $wrapper = $(options.wrapperSelector);

      var menuPos = (options.mode === 'fixed') ? 'fixed' : 'sticky';

      // Sidebar independent scroll area
      $menu.css({
        position: menuPos,
        top: HH + 'px',
        left: '0',
        height: 'calc(100vh - ' + HH + 'px)',
        maxHeight: 'calc(100vh - ' + HH + 'px)',
        minHeight: '0',
        overflowY: 'auto',
        overflowX: 'hidden'
      });

      if (options.applySetup && $setup.length) {
        // Setup independent scroll area
        $setup.css({
          position: 'fixed',
          top: HH + 'px',
          height: 'calc(100vh - ' + HH + 'px)',
          maxHeight: 'calc(100vh - ' + HH + 'px)',
          minHeight: '0',
          overflowY: 'auto'
        });
      }

      // Content wrapper height baseline
      $wrapper.css({
        minHeight: 'calc(100vh - ' + HH + 'px)',
        height: 'auto'
      });

      // If header is fixed/sticky, offset content so it won't go under header
      if (options.headerOffset && isHeaderFixed()) {
        $wrapper.css('padding-top', HH + 'px');
      } else {
        $wrapper.css('padding-top', '');
      }

      // Ensure pinned projects UI is placed at bottom and independent from menu scroll
      if (options.pinnedPanel) { setupPinnedPanel(HH); } else { restorePinnedToMenu(); }
    } catch (e) {
      // no-op
    }
  }

  // Throttled refresh
  var refreshTO = null;
  function refreshSoon() {
    if (refreshTO) clearTimeout(refreshTO);
    refreshTO = setTimeout(applyBetterLayout, options.reapplyDelay);
  }

  // Override core height fix to prevent content stretching
  if (typeof window.mainWrapperHeightFix === 'function') {
    window.mainWrapperHeightFix = function () { refreshSoon(); };
  }

  function connectObservers() {
    $(window).on('resize orientationchange', refreshSoon);

    // Header ResizeObserver (if available)
    try {
      var header = document.querySelector(options.headerSelector);
      if (window.ResizeObserver && header) {
        observers.hdrResize = new ResizeObserver(refreshSoon);
        observers.hdrResize.observe(header);
      }
    } catch (e) {}

    // Elements where core may set styles
    try {
      var menuEl = document.querySelector(options.sidebarSelector);
      var setupEl = document.querySelector(options.setupSelector);
      var wrapperEl = document.querySelector(options.wrapperSelector);
      if (window.MutationObserver) {
        if (menuEl) {
          observers.mutMenu = new MutationObserver(refreshSoon);
          observers.mutMenu.observe(menuEl, { attributes: true, attributeFilter: ['style', 'class'] });
        }
        if (setupEl) {
          observers.mutSetup = new MutationObserver(refreshSoon);
          observers.mutSetup.observe(setupEl, { attributes: true, attributeFilter: ['style', 'class'] });
        }
        if (wrapperEl) {
          observers.mutWrapper = new MutationObserver(refreshSoon);
          observers.mutWrapper.observe(wrapperEl, { attributes: true, attributeFilter: ['style', 'class'] });
        }
        observers.mutBody = new MutationObserver(refreshSoon);
        observers.mutBody.observe(document.body, { attributes: true, attributeFilter: ['class'] });
      }
    } catch (e) {}
  }

  function disconnectObservers() {
    $(window).off('resize orientationchange', refreshSoon);
    if (observers.hdrResize) { try { observers.hdrResize.disconnect(); } catch (e) {} observers.hdrResize = null; }
    ['mutMenu','mutSetup','mutWrapper','mutBody'].forEach(function(k){ if (observers[k]) { try { observers[k].disconnect(); } catch (e) {} observers[k] = null; }});
  }

  // Public API
  window.BetterMenubar = {
    refresh: function(){ applyBetterLayout(); },
    setOptions: function (opts) { options = $.extend({}, options, opts || {}); refreshSoon(); },
    enable: function(){ connectObservers(); applyBetterLayout(); },
    disable: function(){ disconnectObservers(); }
  };

  // Init
  $(function () {
    // Merge config from server if available
    try { if (window.BM_CONFIG) { options = $.extend({}, options, window.BM_CONFIG); } } catch (e) {}
    connectObservers();
    applyBetterLayout();
    setTimeout(applyBetterLayout, 120);
  });
  
  // ----- Pinned Projects Floating Panel -----
  var pinnedBuilt = false;
  function setupPinnedPanel(HH){
    var $sideMenu = $('#side-menu');
    if ($sideMenu.length === 0) return;
    var $pinned = $sideMenu.children('li.pinned_project');
    if ($pinned.length === 0) {
      // Nothing pinned; hide toggle/panel if exist
      $('#bm-pinned-toggle, #bm-pinned-panel').remove();
      pinnedBuilt = false;
      return;
    }
    if (!pinnedBuilt) {
      var $toggle = $('<div id="bm-pinned-toggle" class="bm-pinned-toggle"><span class="bm-label">Pinned</span> <span class="bm-count">('+ $pinned.length +')</span></div>');
      var $panel = $('<div id="bm-pinned-panel" class="bm-pinned-panel"><ul class="bm-list"></ul></div>');
      $('body').append($toggle).append($panel);
      $('#menu').addClass('bm-has-pinned');
      // Move items into panel list by cloning (keep original menu clean)
      var $list = $panel.find('.bm-list');
      // Hide originals in inner scroll to avoid double
      $sideMenu.children('li.pinned-separator').addClass('bm-hidden').hide();
      $pinned.each(function(){
        var $li = $(this).clone(true, true);
        $list.append($li);
        $(this).addClass('bm-hidden').hide();
      });
      pinnedBuilt = true;

      // Toggle events
      $toggle.on('click', function(){ $('#bm-pinned-panel').toggleClass('open'); });
      // Close when clicking outside
      $(document).on('click.bmPinned', function(e){
        var $t = $(e.target);
        if (!$t.closest('#bm-pinned-panel, #bm-pinned-toggle').length) {
          $('#bm-pinned-panel').removeClass('open');
        }
      });
    } else {
      // Update count if changed
      $('#bm-pinned-toggle .bm-count').text('(' + $pinned.length + ')');
      // Make sure originals stay hidden on rerender
      $sideMenu.children('li.pinned-separator').addClass('bm-hidden').hide();
      $pinned.addClass('bm-hidden').hide();
    }

    // Position and width sync with sidebar
    try {
      var $menu = $('#menu');
      var left = $menu.offset().left;
      var width = $menu.outerWidth();
      $('#bm-pinned-toggle, #bm-pinned-panel').css({ left: left + 'px', width: width + 'px' });
      $('#bm-pinned-panel').css({ bottom: $('#bm-pinned-toggle').outerHeight() + 'px' });
      // Hide elements when sidebar hidden
      if ($('body').hasClass('hide-sidebar') && !$('body').hasClass('show-sidebar')) {
        $('#bm-pinned-toggle, #bm-pinned-panel').hide();
      } else {
        $('#bm-pinned-toggle').show();
      }
    } catch (e) {}
  }

  function restorePinnedToMenu(){
    // Remove floating UI and show originals back
    $('#bm-pinned-toggle, #bm-pinned-panel').remove();
    $(document).off('click.bmPinned');
    var $sideMenu = $('#side-menu');
    $sideMenu.find('li.pinned-separator.bm-hidden').removeClass('bm-hidden').show();
    $sideMenu.find('li.pinned_project.bm-hidden').removeClass('bm-hidden').show();
    $('#menu').removeClass('bm-has-pinned');
    pinnedBuilt = false;
  }
})();
