<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-12">
        <div class="panel_s">
          <div class="panel-body">
            <div class="_buttons mbottom20">
              <h4 class="no-margin"><i class="fa fa-cogs"></i> <?php echo _l('settings_actions_by_module'); ?></h4>
            </div>
            <div class="row mtop10 mbot10">
              <div class="col-md-2 mtop10">
                <select id="filter-entity" class="selectpicker" data-width="100%">
                  <option value=""><?php echo _l('actions_all_modules'); ?></option>
                  <option value="project"><?php echo _l('actions_projects'); ?></option>
                  <option value="task"><?php echo _l('actions_tasks'); ?></option>
                  <option value="invoice"><?php echo _l('actions_invoices'); ?></option>
                  <option value="estimate"><?php echo _l('actions_estimates'); ?></option>
                  <option value="expense"><?php echo _l('actions_expenses'); ?></option>
                  <option value="timesheet"><?php echo _l('actions_timesheets'); ?></option>
                  <option value="customer"><?php echo _l('actions_customers'); ?></option>
                  <option value="reminder"><?php echo _l('actions_reminders'); ?></option>
                  <option value="other"><?php echo _l('actions_other'); ?></option>
                </select>
              </div>
              <div class="col-md-2 mtop10">
                <select id="filter-risk" class="selectpicker" data-width="100%">
                  <option value=""><?php echo _l('actions_all_risk_levels'); ?></option>
                  <option value="low"><?php echo _l('risk_low'); ?></option>
                  <option value="medium"><?php echo _l('risk_medium'); ?></option>
                  <option value="high"><?php echo _l('risk_high'); ?></option>
                </select>
              </div>
              <div class="col-md-2 mtop10">
                <select id="filter-active" class="selectpicker" data-width="100%">
                  <option value=""><?php echo _l('actions_all_status'); ?></option>
                  <option value="1"><?php echo _l('actions_active'); ?></option>
                  <option value="0"><?php echo _l('actions_inactive'); ?></option>
                </select>
              </div>
              <div class="col-md-4 mtop10">
                <input type="text" id="filter-search" class="form-control" placeholder="<?php echo _l('actions_search_placeholder'); ?>">
              </div>
            </div>

            <div class="table-responsive">
              <table class="table table-striped table-actions" id="pa-actions-table">
                <thead>
                  <tr>
                    <th style="width: 12%;"><?php echo _l('actions_id'); ?></th>
                    <th style="width: 18%;"><?php echo _l('actions_name'); ?></th>
                    <th style="width: 25%;"><?php echo _l('actions_description'); ?></th>
                    <th style="width: 6%;"><?php echo _l('actions_risk'); ?></th>
                    <th style="width: 6%;" class="text-center"><?php echo _l('actions_confirm'); ?></th>
                    <th style="width: 6%;" class="text-center"><?php echo _l('actions_active'); ?></th>
                    <th style="width: 8%;"><?php echo _l('actions_entity'); ?></th>
                    <th style="width: 15%;"><?php echo _l('actions_related_tables'); ?></th>
                    <th style="width: 10%;"><?php echo _l('actions_actions'); ?></th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (!empty($actions)) : 
                    // Group actions by entity type
                    $groupedActions = [];
                    foreach ($actions as $a) {
                      $entityType = isset($a['entity_type']) ? $a['entity_type'] : 'other';
                      if (!isset($groupedActions[$entityType])) {
                        $groupedActions[$entityType] = [];
                      }
                      $groupedActions[$entityType][] = $a;
                    }
                    
                    // Define group order and labels
                    $groupOrder = ['project', 'task', 'invoice', 'estimate', 'expense', 'timesheet', 'customer', 'reminder', 'other'];
                    $groupLabels = [
                      'project' => _l('actions_projects'),
                      'task' => _l('actions_tasks'), 
                      'invoice' => _l('actions_invoices'),
                      'estimate' => _l('actions_estimates'),
                      'expense' => _l('actions_expenses'),
                      'timesheet' => _l('actions_timesheets'),
                      'customer' => _l('actions_customers'),
                      'reminder' => _l('actions_reminders'),
                      'other' => _l('actions_other')
                    ];
                    
                    // Display grouped actions
                    foreach ($groupOrder as $groupKey):
                      if (!isset($groupedActions[$groupKey]) || empty($groupedActions[$groupKey])) {
                        continue;
                      }
                      
                      $groupLabel = isset($groupLabels[$groupKey]) ? $groupLabels[$groupKey] : ucfirst($groupKey);
                      $groupCount = count($groupedActions[$groupKey]);
                  ?>
                    <tr class="group-header" style="background-color: #f8f9fa; font-weight: bold;">
                      <td colspan="9" style="padding: 12px 8px; border-top: 2px solid #dee2e6;">
                        <i class="fa fa-folder-open text-primary"></i> 
                        <?php echo html_escape($groupLabel); ?> 
                        <span class="badge badge-secondary"><?php echo $groupCount; ?></span>
                      </td>
                    </tr>
                    <?php foreach ($groupedActions[$groupKey] as $a): ?>
                      <tr data-risk="<?php echo html_escape($a['risk_level']); ?>" data-active="<?php echo !empty($a['is_active']) ? '1' : '0'; ?>" data-entity="<?php echo html_escape($groupKey); ?>">
                        <td><code><?php echo html_escape($a['action_id']); ?></code></td>
                        <td><?php echo html_escape($a['name']); ?></td>
                        <td class="small text-muted" style="max-width:420px; white-space:normal;">
                          <?php echo html_escape($a['description']); ?>
                        </td>
                        <td>
                          <span class="badge badge-<?php echo $a['risk_level']==='high'?'danger':($a['risk_level']==='medium'?'warning':'success'); ?>">
                            <?php echo ucfirst($a['risk_level']); ?>
                          </span>
                        </td>
                        <td class="text-center"> <?php echo !empty($a['requires_confirm']) ? '<i class="fa fa-check text-warning"></i>' : '<i class="fa fa-times text-muted"></i>'; ?> </td>
                        <td class="text-center">
                          <label class="switch" title="Toggle active">
                            <input type="checkbox" class="js-toggle" data-id="<?php echo $a['action_id']; ?>" <?php echo !empty($a['is_active']) ? 'checked' : ''; ?>>
                            <span class="slider round"></span>
                          </label>
                        </td>
                        <td class="text-center">
                          <?php 
                            $entityType = isset($a['entity_type']) ? $a['entity_type'] : '';
                            if ($entityType): ?>
                              <span class="badge badge-info"><?php echo html_escape($entityType); ?></span>
                            <?php else: ?>
                              <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="small" style="max-width:360px; white-space:normal;">
                          <?php 
                            $relatedTables = isset($a['related_tables']) ? $a['related_tables'] : '';
                            $tables = [];
                            if (is_string($relatedTables) && $relatedTables !== '') {
                              $tmp = json_decode($relatedTables, true);
                              if (json_last_error() === JSON_ERROR_NONE && is_array($tmp)) { 
                                $tables = $tmp; 
                              }
                            } elseif (is_array($relatedTables)) {
                              $tables = $relatedTables;
                            }
                          ?>
                          <?php if (empty($tables)): ?>
                            <span class="text-muted">—</span>
                          <?php else: ?>
                            <?php 
                              $tableLabels = [];
                              foreach ($tables as $table) {
                                $tableLabels[] = '<span class="label label-default">' . html_escape($table) . '</span>';
                              }
                              echo implode(' ', $tableLabels);
                            ?>
                          <?php endif; ?>
                        </td>
                        <td>
                        <button class="btn btn-default btn-sm js-edit"
                                data-id="<?php echo $a['action_id']; ?>"
                                data-name="<?php echo html_escape($a['name']); ?>"
                                data-prompt="<?php echo html_escape(isset($a['prompt_override'])?$a['prompt_override']:''); ?>"
                                data-mapping='<?php echo html_escape(isset($a['param_mapping'])?$a['param_mapping']:'{}'); ?>'
                                data-entity="<?php echo html_escape(isset($a['entity_type'])?$a['entity_type']:''); ?>"
                                data-related='<?php echo html_escape(isset($a['related_tables'])?$a['related_tables']:'[]'); ?>'
                                data-ctxq='<?php echo html_escape(isset($a['context_queries'])?$a['context_queries']:''); ?>'>
                          <i class="fa fa-pencil"></i> <?php echo _l('actions_edit'); ?>
                        </button>
                        <button class="btn btn-success btn-sm js-learn" data-id="<?php echo $a['action_id']; ?>">
                          <i class="fa fa-graduation-cap"></i> <?php echo _l('actions_learn'); ?>
                        </button>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
            

          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
.switch { position: relative; display: inline-block; width: 42px; height: 22px; }
.switch input {opacity:0;width:0;height:0}
.slider { position:absolute; cursor:pointer; top:0; left:0; right:0; bottom:0; background:#d1d5db; transition:.2s; }
.slider:before{ position:absolute; content:""; height:18px;width:18px; left:2px; bottom:2px; background:white; transition:.2s; }
input:checked + .slider{ background:#0d6efd; }
input:checked + .slider:before{ transform: translateX(20px); }
.slider.round{ border-radius:22px; }
.slider.round:before{ border-radius:50%; }
.label { display: inline-block; padding: 2px 6px; font-size: 11px; font-weight: 500; line-height: 1; color: #fff; text-align: center; white-space: nowrap; vertical-align: baseline; border-radius: 3px; margin: 1px; }
.label-default { background-color: #6c757d; }
.badge { display: inline-block; padding: 4px 8px; font-size: 11px; font-weight: 500; line-height: 1; color: #fff; text-align: center; white-space: nowrap; vertical-align: baseline; border-radius: 4px; }
.badge-info { background-color: #17a2b8; }
.badge-secondary { background-color: #6c757d; }
.group-header { 
  background-color: #f8f9fa !important; 
  font-weight: bold !important; 
  border-top: 2px solid #dee2e6 !important;
}
.group-header td { 
  padding: 12px 8px !important; 
  border-top: 2px solid #dee2e6 !important;
  font-size: 14px;
}
.group-header i { margin-right: 8px; }
</style>

<!-- Prompt Modal -->
<div class="modal fade" id="actionPromptModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fa fa-pencil"></i> <?php echo _l('modal_edit_action_configuration'); ?></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      </div>
      <div class="modal-body">
        <form id="actionPromptForm">
          <input type="hidden" id="ap_action_id" name="action_id" value="">
          <div class="form-group">
            <label><?php echo _l('modal_action'); ?></label>
            <input type="text" class="form-control" id="ap_action_name" disabled>
          </div>
          <div class="form-group">
            <label><?php echo _l('modal_context_queries'); ?></label>
            <div class="small text-muted mtop5 mbot5"><?php echo _l('modal_context_queries_help'); ?></div>
            <pre id="ap_ctxq" class="bg-light p-2 small" style="max-height:240px; overflow:auto;"></pre>
          </div>
          <div class="row">
            <div class="col-sm-6">
              <div class="form-group">
                <label><?php echo _l('modal_entity_type'); ?></label>
                <select id="ap_entity_type" class="selectpicker" data-width="100%">
                  <option value=""><?php echo _l('modal_entity_none'); ?></option>
                  <option value="project"><?php echo _l('actions_projects'); ?></option>
                  <option value="task"><?php echo _l('actions_tasks'); ?></option>
                  <option value="invoice"><?php echo _l('actions_invoices'); ?></option>
                  <option value="estimate"><?php echo _l('actions_estimates'); ?></option>
                  <option value="expense"><?php echo _l('actions_expenses'); ?></option>
                  <option value="timesheet"><?php echo _l('actions_timesheets'); ?></option>
                  <option value="customer"><?php echo _l('actions_customers'); ?></option>
                  <option value="reminder"><?php echo _l('actions_reminders'); ?></option>
                  <option value="other"><?php echo _l('actions_other'); ?></option>
                </select>
              </div>
            </div>
            <div class="col-sm-6">
              <div class="form-group">
                <label><?php echo _l('modal_related_tables'); ?></label>
                <select id="ap_related_tables" class="selectpicker" data-width="100%" data-live-search="true" multiple data-actions-box="true">
                  <?php if (!empty($db_tables)): foreach ($db_tables as $t): ?>
                    <option value="<?php echo html_escape($t); ?>"><?php echo html_escape($t); ?></option>
                  <?php endforeach; endif; ?>
                </select>
              </div>
            </div>
          </div>
          <div class="form-group">
            <label><?php echo _l('modal_prompt_override'); ?></label>
            <textarea class="form-control" id="ap_prompt" name="prompt_override" rows="6" placeholder="<?php echo _l('modal_prompt_override_placeholder'); ?>"></textarea>
          </div>
          <div class="form-group">
            <label><?php echo _l('modal_parameter_mapping'); ?></label>
            <div class="small text-muted mtop5 mbot5"><?php echo _l('modal_parameter_mapping_help'); ?></div>
            <div id="ap_mapping_container" class="table-responsive" style="max-height:320px; overflow:auto; border:1px solid #e9ecef; border-radius:6px;">
              <table class="table table-sm table-striped mb-0">
                <thead>
                  <tr>
                    <th style="width:18%"><?php echo _l('modal_parameter'); ?></th>
                    <th style="width:10%"><?php echo _l('modal_type'); ?></th>
                    <th style="width:10%"><?php echo _l('actions_required'); ?></th>
                    <th style="width:22%"><?php echo _l('modal_source'); ?></th>
                    <th><?php echo _l('modal_default_value'); ?></th>
                  </tr>
                </thead>
                <tbody id="ap_mapping_rows"></tbody>
              </table>
            </div>
          </div>
          <div class="form-group">
            <label><?php echo _l('modal_mini_agent_learn'); ?></label>
            <div class="small text-muted mtop5 mbot5"><?php echo _l('modal_mini_agent_learn_help'); ?></div>
            <div class="tw-flex tw-items-center tw-gap-2 mtop5 mbot5">
              <div class="custom-control custom-checkbox" style="display:inline-block;">
                <input type="checkbox" id="ap_force_schema" class="custom-control-input">
                <label class="custom-control-label" for="ap_force_schema">Force overwrite schema</label>
              </div>
              <button type="button" class="btn btn-success btn-sm" id="ap_learn"><i class="fa fa-graduation-cap"></i> <?php echo _l('modal_learn_this_action'); ?></button>
            </div>
            <div id="ap_learn_out" class="mtop10"></div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal"><?php echo _l('modal_close'); ?></button>
        <button type="button" class="btn btn-primary" id="ap_save"><?php echo _l('modal_save'); ?></button>
      </div>
    </div>
  </div>
</div>

<?php init_tail(); ?>
<script>
(function(){
  var $ = window.jQuery; if(!$) return;
  // Filters
  function applyFilters(){
    var entity = ($('#filter-entity').val()||'').toLowerCase();
    var risk = ($('#filter-risk').val()||'').toLowerCase();
    var active = String($('#filter-active').val()||'');
    var q = ($('#filter-search').val()||'').toLowerCase();
    
    $('#pa-actions-table tbody tr').each(function(){
      var $tr = $(this);
      var ok = true;
      
      // Handle group headers
      if ($tr.hasClass('group-header')) {
        // Show group header if any of its children are visible
        var groupVisible = false;
        $tr.nextUntil('.group-header').each(function(){
          var $child = $(this);
          if ($child.hasClass('group-header')) return false; // stop at next group
          
          var childOk = true;
          if (entity && String($child.data('entity'))!==entity) childOk=false;
          if (risk && String($child.data('risk'))!==risk) childOk=false;
          if (active!=='' && String($child.data('active'))!==active) childOk=false;
          if (q){ var text = $child.text().toLowerCase(); if (text.indexOf(q)===-1) childOk=false; }
          
          if (childOk) groupVisible = true;
        });
        $tr.toggle(groupVisible);
        return;
      }
      
      // Handle action rows
      if (entity && String($tr.data('entity'))!==entity) ok=false;
      if (risk && String($tr.data('risk'))!==risk) ok=false;
      if (active!=='' && String($tr.data('active'))!==active) ok=false;
      if (q){ var text = $tr.text().toLowerCase(); if (text.indexOf(q)===-1) ok=false; }
      
      $tr.toggle(ok);
    });
  }
  $('#filter-entity,#filter-risk,#filter-active').on('changed.bs.select', applyFilters);
  $('#filter-search').on('input', applyFilters);

  // Toggle active
  $(document).on('change', '.js-toggle', function(){
    var id = $(this).data('id'); var on = $(this).is(':checked') ? 1 : 0;
    $.post('<?php echo admin_url('project_agent/toggle_action'); ?>', { action_id:id, is_active:on })
      .fail(function(){ alert('<?php echo _l('msg_update_failed'); ?>'); });
  });
  // Edit prompt
  $(document).on('click', '.js-edit', function(){
    var id = $(this).data('id'); var nm = $(this).data('name'); var pr = $(this).data('prompt')||'';
    $('#ap_action_id').val(id); $('#ap_action_name').val(nm); $('#ap_prompt').val(pr);
    // Prefill context queries (read-only)
    try {
      var ctx = $(this).attr('data-ctxq')||''; var pretty='';
      if (ctx) { var obj = JSON.parse(ctx); pretty = JSON.stringify(obj, null, 2); }
      $('#ap_ctxq').text(pretty);
    } catch(e){ $('#ap_ctxq').text(''); }
    // Prefill entity
    var ent = $(this).data('entity')||'';
    $('#ap_entity_type').selectpicker('val', ent);
    // Prefill related tables
    var rel = $(this).attr('data-related')||'[]';
    try { rel = JSON.parse(rel); } catch(e){ rel = []; }
    $('#ap_related_tables').selectpicker('val', rel);
    // Load schema for mapping UI
    $('#ap_mapping_rows').html('<tr><td colspan="5" class="text-center text-muted"><?php echo _l('schema_learning_progress'); ?></td></tr>');
    $.get('<?php echo admin_url('project_agent/get_action_schema'); ?>', { action_id: id })
      .done(function(resp){ try{ if(typeof resp==='string') resp=JSON.parse(resp);}catch(e){}
            if (resp && resp.success && resp.schema) { renderMappingRows(id, resp.schema); } else { $('#ap_mapping_rows').html('<tr><td colspan="5" class="text-danger"><?php echo _l('msg_failed_to_load_schema'); ?></td></tr>'); } })
      .fail(function(){ $('#ap_mapping_rows').html('<tr><td colspan="5" class="text-danger"><?php echo _l('msg_failed_to_load_schema'); ?></td></tr>'); });
    $('#actionPromptModal').modal('show');
  });

  // Learn (open modal and trigger learn for this action)
  $(document).on('click', '.js-learn', function(){
    var id = $(this).data('id');
    // Try to prefill modal using nearby edit button data
    var $row = $(this).closest('tr');
    var $edit = $row.find('.js-edit');
    if ($edit.length) { $edit.click(); }
    // After modal shows, trigger learn
    setTimeout(function(){
      triggerMiniAgentLearn(id);
    }, 300);
  });

  // Learn button inside modal
  $(document).on('click', '#ap_learn', function(){
    var id = $('#ap_action_id').val();
    if (!id) return;
    triggerMiniAgentLearn(id);
  });

  function triggerMiniAgentLearn(actionId){
    var $btn = $('#ap_learn'); var old = $btn.html();
    $btn.prop('disabled', true).html('<i class="fa fa-spin fa-spinner"></i>');
    $('#ap_learn_out').text('<?php echo _l('schema_learning_progress'); ?>');
    var payload = { action_ids: JSON.stringify([actionId]) };
    try { if ($('#ap_force_schema').is(':checked')) { payload.force_schema = 1; } } catch(e){}
    $.post('<?php echo admin_url('project_agent/learn_related_tables'); ?>', payload)
      .done(function(resp){
        var cfg = { action_ids:[actionId] };
        var prettyCfg = JSON.stringify(cfg, null, 2);
        var pretty = '';
        try { pretty = JSON.stringify(resp, null, 2); } catch(e) { pretty = String(resp); }
        var prompt = (resp && resp.learn && resp.learn.prompt) ? String(resp.learn.prompt) : '';
        var promptBlock = prompt ? ('<div class="small text-muted">Prompt</div><pre class="small" style="max-height:180px; overflow:auto;">'+$('<div>').text(prompt).html()+'</pre>') : '';
        $('#ap_learn_out').html('<div class="small text-muted">Config</div><pre class="small" style="max-height:120px; overflow:auto;">'+$('<div>').text(prettyCfg).html()+'</pre>'+
                                promptBlock +
                                '<div class="small text-muted">Response</div><pre class="small" style="max-height:220px; overflow:auto;">'+$('<div>').text(pretty).html()+'</pre>');
        // If response contains updated context_queries for this id, reflect in view block
        try {
          if (resp && resp.actions && resp.actions[actionId] && resp.actions[actionId].context_queries){
            $('#ap_ctxq').text(JSON.stringify(resp.actions[actionId].context_queries, null, 2));
          }
          // If response contains param mapping, update modal rows & inline data
          if (resp && resp.mappings && resp.mappings[actionId]){
            // Update the edit button's data-mapping attribute so re-open uses it
            var $btn = document.querySelector('.js-edit[data-id="'+actionId+'"]');
            if ($btn) { $btn.setAttribute('data-mapping', JSON.stringify(resp.mappings[actionId])); }
            // Rerender mapping rows with the same schema but new mapping
            $.get('<?php echo admin_url('project_agent/get_action_schema'); ?>', { action_id: actionId })
              .done(function(r){ try{ if(typeof r==='string') r=JSON.parse(r);}catch(e){}
                     if (r && r.success && r.schema) { renderMappingRows(actionId, r.schema); } });
          }
          // If a new schema returned, force re-render using it immediately
          if (resp && resp.schemas && resp.schemas[actionId]){
            renderMappingRows(actionId, resp.schemas[actionId]);
          }
        } catch(e){}
      })
      .fail(function(xhr){
        $('#ap_learn_out').text('<?php echo _l('msg_learning_failed'); ?>: ' + (xhr.responseText || xhr.status));
      })
      .always(function(){ $btn.prop('disabled', false).html(old); });
  }
  $('#ap_save').on('click', function(){
    var data = {
      action_id: $('#ap_action_id').val(),
      prompt_override: $('#ap_prompt').val(),
      param_mapping: JSON.stringify(collectMappingRows()),
      entity_type: $('#ap_entity_type').val() || '',
      related_tables: $('#ap_related_tables').val() || []
    };
    $.post('<?php echo admin_url('project_agent/save_action_config'); ?>', data)
      .done(function(resp){ try{ if(typeof resp==='string') resp=JSON.parse(resp);}catch(e){}
             if(resp && resp.success){ $('#actionPromptModal').modal('hide'); location.reload(); } else { alert('<?php echo _l('msg_save_failed'); ?>'); } })
      .fail(function(){ alert('<?php echo _l('msg_save_failed'); ?>'); });
  });

  function mappingSources(){
    return [
      {value:'', label:'<?php echo _l('modal_entity_none'); ?>'},
      {value:'context.project_id', label:'Context: Project ID'},
      {value:'context.user_id', label:'Context: User ID'},
      {value:'context.session_id', label:'Context: Session ID'},
      {value:'static', label:'Static value'},
      {value:'related.project', label:'Related: Project'},
      {value:'related.customer', label:'Related: Customer'},
      {value:'related.task', label:'Related: Task'}
    ];
  }

  // Render mapping rows from schema
  function renderMappingRows(actionId, schema){
    var rows = '';
    var props = (schema && schema.properties) ? schema.properties : {};
    var required = Array.isArray(schema.required) ? schema.required : [];
    // Try to fetch existing mapping for prefill
    var map = {};
    try {
      var btn = document.querySelector('.js-edit[data-id="'+actionId+'"]');
      var json = btn && btn.getAttribute('data-mapping');
      if (json) map = JSON.parse(json);
    } catch(e) {}
    Object.keys(props).forEach(function(name){
      var ps = props[name]||{}; var type = ps.type||'string'; var isReq = required.indexOf(name)!==-1;
      var cur = (map && map[name]) ? map[name] : {};
      var src = cur.source || (name==='project_id'?'context.project_id':'');
      var def = (cur.default!==undefined && cur.default!==null) ? cur.default : '';
      rows += '<tr>'
           +    '<td><code>'+escapeHtml(name)+'</code></td>'
           +    '<td>'+escapeHtml(type)+'</td>'
           +    '<td>'+(isReq?'<span class="badge badge-danger"><?php echo _l('actions_required'); ?></span>':'<span class="badge badge-secondary"><?php echo _l('actions_optional'); ?></span>')+'</td>'
           +    '<td>'+renderSourceSelect(name, src)+'</td>'
           +    '<td>'+renderDefaultInput(name, type, def)+'</td>'
           +  '</tr>';
    });
    $('#ap_mapping_rows').html(rows);
  }

  function renderSourceSelect(name, current){
    var opts = mappingSources();
    var html = '<select class="form-control form-control-sm ap-map-src" data-name="'+escapeHtml(name)+'">';
    for (var i=0;i<opts.length;i++){
      var o=opts[i]; html += '<option value="'+o.value+'"'+(o.value===current?' selected':'')+'>'+o.label+'</option>';
    }
    html += '</select>';
    return html;
  }
  function renderDefaultInput(name, type, def){
    var inputType = (type==='integer' || type==='number') ? 'number' : 'text';
    return '<input type="'+inputType+'" class="form-control form-control-sm ap-map-def" data-name="'+escapeHtml(name)+'" value="'+escapeHtml(def)+'" placeholder="<?php echo _l('modal_default_value'); ?>">';
  }
  function collectMappingRows(){
    var map = {};
    $('#ap_mapping_rows tr').each(function(){
      var $tr = $(this); var name = $tr.find('.ap-map-src').data('name');
      var src = $tr.find('.ap-map-src').val(); var def = $tr.find('.ap-map-def').val();
      if (src || (def!=='' && def!==null)) { map[name] = { source: src || '', default: def }; }
    });
    return map;
  }
})();
</script>
