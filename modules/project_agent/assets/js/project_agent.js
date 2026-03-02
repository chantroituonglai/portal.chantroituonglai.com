// Project Agent - common helpers
(function(){
  window.PA = window.PA || {};
  PA.log = function(){ try { console.log.apply(console, ['[PA]'].concat([].slice.call(arguments))); } catch(e){} };
  PA.toast = function(type, msg){
    if (typeof alert_float === 'function') { alert_float(type, msg); return; }
    try { $.notify({ message: msg }, { type: type }); } catch(e) { alert(msg); }
  };
})();

