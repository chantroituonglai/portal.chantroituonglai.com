// Project Agent - Action execution helpers
(function(){
  function execute(actionId, params, sessionId){
    return $.post(admin_url + 'project_agent/execute_action', { action_id: actionId, params: JSON.stringify(params||{}), session_id: sessionId });
  }
  window.PA_AX = { execute: execute };
})();

