<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-8">
        <div class="panel_s">
          <div class="panel-body">
            <h4 class="no-margin"><i class="fa fa-robot"></i> <?php echo _l('overview_title'); ?></h4>
            <p class="text-muted mtop5"><?php echo _l('overview_description'); ?></p>
            <hr class="hr-panel-heading"/>
            <div class="row">
              <div class="col-sm-4">
                <div class="well">
                  <div class="h3 no-margin">v<?php echo html_escape((string)$version); ?></div>
                  <div class="text-muted"><?php echo _l('overview_db_version'); ?></div>
                </div>
              </div>
              <div class="col-sm-8">
                <div class="alert <?php echo $ai_available?'alert-success':'alert-warning'; ?>">
                  <i class="fa fa-microchip"></i> <?php echo _l('overview_ai_provider'); ?>: <?php echo $ai_available ? _l('overview_ai_available') : _l('overview_ai_not_available'); ?>
                  <?php if(isset($ai_status['message'])): ?>
                    <div class="small mtop5">Hint: <?php echo html_escape($ai_status['message']); ?></div>
                  <?php endif; ?>
                </div>
                <div class="mbot10">
                  <a class="btn btn-primary" href="<?php echo admin_url('project_agent/ai'); ?>"><i class="fa fa-comments"></i> <?php echo _l('overview_open_ai_room'); ?></a>
                  <a class="btn btn-default" href="<?php echo admin_url('projects'); ?>"><i class="fa fa-folder-open"></i> <?php echo _l('overview_projects'); ?></a>
                  <a class="btn btn-default" href="<?php echo admin_url('project_agent/actions'); ?>"><i class="fa fa-cogs"></i> <?php echo _l('overview_actions'); ?></a>
                </div>
              </div>
            </div>
            <hr/>
            <h5 class="mbot10"><?php echo _l('overview_general_configuration'); ?></h5>
            <form method="post" action="">
              <div class="row">
                <div class="col-md-6">
                  <div class="form-group">
                    <label><?php echo _l('overview_enable_ai_room'); ?></label>
                    <select name="project_agent_ai_room_enabled" class="form-control">
                      <option value="1" <?php echo !empty($opts['project_agent_ai_room_enabled'])?'selected':''; ?>>On</option>
                      <option value="0" <?php echo empty($opts['project_agent_ai_room_enabled'])?'selected':''; ?>>Off</option>
                    </select>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label><?php echo _l('overview_ai_provider_label'); ?></label>
                    <select name="project_agent_ai_provider" class="form-control">
                      <?php foreach(($providers?:[]) as $prov): ?>
                        <option value="<?php echo html_escape($prov); ?>" <?php echo ($opts['project_agent_ai_provider']===$prov)?'selected':''; ?>><?php echo strtoupper($prov); ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                </div>
              </div>
              <div class="form-group">
                <label><?php echo _l('overview_system_prompt'); ?></label>
                <textarea name="project_agent_system_prompt" class="form-control" rows="4" placeholder="<?php echo _l('overview_system_prompt_placeholder'); ?>"><?php echo html_escape((string)$opts['project_agent_system_prompt']); ?></textarea>
              </div>
              <div class="row">
                <div class="col-sm-6">
                  <label><?php echo _l('overview_auto_confirm_threshold'); ?></label>
                  <input type="number" class="form-control" name="project_agent_auto_confirm_threshold" value="<?php echo (int)$opts['project_agent_auto_confirm_threshold']; ?>" min="0">
                </div>
                <div class="col-sm-6">
                  <label><?php echo _l('overview_memory_retention_days'); ?></label>
                  <input type="number" class="form-control" name="project_agent_memory_retention_days" value="<?php echo (int)$opts['project_agent_memory_retention_days']; ?>" min="1">
                </div>
              </div>
              <div class="row mtop10">
                <div class="col-sm-6">
                <label><?php echo _l('overview_max_concurrent_sessions'); ?></label>
                <label><?php echo _l('overview_max_concurrent_sessions'); ?></label>
                  <input type="number" class="form-control" name="project_agent_max_concurrent_sessions" value="<?php echo (int)$opts['project_agent_max_concurrent_sessions']; ?>" min="1">
                </div>
                <div class="col-sm-6">
                  <label><?php echo _l('overview_default_risk_level'); ?></label>
                  <select name="project_agent_default_risk_level" class="form-control">
                    <?php foreach(['low','medium','high'] as $risk): ?>
                    <option value="<?php echo $risk; ?>" <?php echo ($opts['project_agent_default_risk_level']===$risk)?'selected':''; ?>><?php echo ucfirst($risk); ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>
              <div class="row mtop10">
                <div class="col-sm-6">
                  <label><?php echo _l('overview_context_tasks_limit'); ?></label>
                  <input type="number" class="form-control" name="project_agent_context_task_limit" value="<?php echo (int)($opts['project_agent_context_task_limit'] ?: 200); ?>" min="10">
                  <small class="text-muted"><?php echo _l('overview_context_tasks_limit_help'); ?></small>
                </div>
                <div class="col-sm-6">
                  <label><?php echo _l('overview_context_milestones_limit'); ?></label>
                  <input type="number" class="form-control" name="project_agent_context_milestone_limit" value="<?php echo (int)($opts['project_agent_context_milestone_limit'] ?: 100); ?>" min="10">
                  <small class="text-muted"><?php echo _l('overview_context_milestones_limit_help'); ?></small>
                </div>
              </div>
              <div class="row mtop10">
                <div class="col-sm-6">
                  <label><?php echo _l('overview_context_activities_limit'); ?></label>
                  <input type="number" class="form-control" name="project_agent_context_activity_limit" value="<?php echo (int)($opts['project_agent_context_activity_limit'] ?: 50); ?>" min="5">
                  <small class="text-muted"><?php echo _l('overview_context_activities_limit_help'); ?></small>
                </div>
              </div>
              <div class="form-group mtop10">
                <label><?php echo _l('overview_debug_logging'); ?></label>
                <select name="project_agent_debug_enabled" class="form-control">
                  <option value="0" <?php echo empty($opts['project_agent_debug_enabled'])?'selected':''; ?>>Off</option>
                  <option value="1" <?php echo !empty($opts['project_agent_debug_enabled'])?'selected':''; ?>>On</option>
                </select>
                <small class="text-muted"><?php echo _l('overview_debug_logging_help'); ?></small>
              </div>
              <div class="form-group">
                <label><?php echo _l('overview_db_query_trace'); ?></label>
                <select name="project_agent_db_trace_enabled" class="form-control">
                  <option value="0" <?php echo empty($opts['project_agent_db_trace_enabled'])?'selected':''; ?>>Off</option>
                  <option value="1" <?php echo !empty($opts['project_agent_db_trace_enabled'])?'selected':''; ?>>On</option>
                </select>
                <small class="text-muted"><?php echo _l('overview_db_query_trace_help'); ?></small>
              </div>

              <hr/>
              <h5 class="mbot10"><?php echo _l('overview_schema_learning'); ?></h5>
              <div class="alert alert-info small"><?php echo _l('overview_schema_learning_description'); ?></div>
              <div class="row mb-2">
                <div class="col-md-7">
                  <label class="small text-muted"><?php echo _l('overview_select_tables'); ?></label>
                  <div class="row mtop10">
                    <div class="col-sm-6">
                      <select id="pa-action-filter" class="selectpicker" data-width="100%" data-live-search="true">
                        <option value="">All actions</option>
                        <?php if (!empty($db_action_meta)): foreach ($db_action_meta as $aid => $an): ?>
                          <option value="<?php echo html_escape($aid); ?>"><?php echo html_escape($an); ?></option>
                        <?php endforeach; endif; ?>
                      </select>
                    </div>
                  </div>
                  <table class="table table-striped table-bordered table-pa-schema" id="pa-schema-table">
                    <thead>
                      <tr>
                        <th class="not-export" style="width:40px;">
                          <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="pa-schema-select-all" value="">
                            <label class="custom-control-label" for="pa-schema-select-all"></label>
                          </div>
                        </th>
                        <th><?php echo _l('overview_table_name'); ?></th>
                        <th><?php echo _l('overview_models_label'); ?></th>
                        <th>Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php if (!empty($db_tables)): foreach ($db_tables as $tbl): ?>
                        <?php $models = isset($db_table_models[$tbl]) ? $db_table_models[$tbl] : []; ?>
                        <tr>
                          <td class="not-export">
                            <div class="custom-control custom-checkbox">
                              <input type="checkbox" class="custom-control-input pa-schema-cb" id="pa-t-<?php echo html_escape($tbl); ?>" value="<?php echo html_escape($tbl); ?>">
                              <label class="custom-control-label" for="pa-t-<?php echo html_escape($tbl); ?>"></label>
                            </div>
                          </td>
                          <td><code><?php echo html_escape($tbl); ?></code></td>
                          <td>
                            <?php if (!empty($models)): foreach ($models as $mf): ?>
                              <span class="badge badge-light" title="<?php echo html_escape($mf); ?>"><?php echo html_escape(basename($mf,'.php')); ?></span>
                            <?php endforeach; else: ?>
                              <span class="text-muted small">—</span>
                            <?php endif; ?>
                          </td>
                          <td>
                            <?php 
                              $acts = isset($db_table_actions[$tbl]) ? (array)$db_table_actions[$tbl] : [];
                            ?>
                            <?php if (!empty($acts)): foreach ($acts as $aid): ?>
                              <?php $an = isset($db_action_meta[$aid]) ? $db_action_meta[$aid] : $aid; ?>
                              <a class="badge badge-info pa-action-badge" data-action-id="<?php echo html_escape($aid); ?>" href="<?php echo admin_url('project_agent/actions'); ?>" title="<?php echo html_escape($aid); ?>"><?php echo html_escape($an); ?></a>
                            <?php endforeach; else: ?>
                              <span class="text-muted small">—</span>
                            <?php endif; ?>
                          </td>
                        </tr>
                      <?php endforeach; else: ?>
                        <tr><td colspan="4" class="text-muted small"><?php echo _l('overview_no_tables'); ?></td></tr>
                      <?php endif; ?>
                    </tbody>
                  </table>
                  <button id="pa-schema-run" type="button" class="btn btn-primary mt-2"><i class="fa fa-magic"></i> <?php echo _l('overview_run_learning'); ?></button>
                  <div class="row mtop10">
                    <div class="col-sm-8">
                      <select id="pa-learn-related-actions" class="selectpicker" data-width="100%" data-live-search="true" multiple data-actions-box="true" title="Select actions (optional)">
                        <?php if (!empty($db_action_meta)): foreach ($db_action_meta as $aid => $an): ?>
                          <option value="<?php echo html_escape($aid); ?>"><?php echo html_escape($an); ?></option>
                        <?php endforeach; endif; ?>
                      </select>
                    </div>
                    <div class="col-sm-4">
                      <button id="pa-learn-related-run" type="button" class="btn btn-default"><i class="fa fa-graduation-cap"></i> Learn Related Tables</button>
                    </div>
                  </div>
                </div>
                <div class="col-md-5">
                  <label class="small text-muted"><?php echo _l('overview_results'); ?></label>
                  <pre id="pa-schema-output" class="bg-light p-2 small" style="max-height:420px; min-height:180px; overflow:auto; display:none;"></pre>
                </div>
              </div>

              <div class="row mtop20">
                <div class="col-md-12">
                  <h5 class="mbot10">Tables Grouped by Action</h5>
                  <div class="alert alert-info small">Danh sách action và các bảng liên quan (suy luận từ permissions/tên/schema).</div>
                  <table class="table table-striped table-bordered" id="pa-actions-group-table">
                    <thead>
                      <tr>
                        <th style="width:22%;">Action</th>
                        <th>Name</th>
                        <th style="width:56%;">Tables</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php if (!empty($db_action_tables)): foreach ($db_action_tables as $row): ?>
                        <tr>
                          <td><code><?php echo html_escape($row['action_id']); ?></code></td>
                          <td><?php echo html_escape($row['name']); ?></td>
                          <td>
                            <?php if (!empty($row['tables'])): foreach ($row['tables'] as $t): ?>
                              <span class="badge badge-light" title="<?php echo html_escape($t); ?>"><?php echo html_escape($t); ?></span>
                            <?php endforeach; else: ?>
                              <span class="text-muted small">—</span>
                            <?php endif; ?>
                          </td>
                        </tr>
                      <?php endforeach; else: ?>
                        <tr><td colspan="3" class="text-muted small">No actions mapped.</td></tr>
                      <?php endif; ?>
                    </tbody>
                  </table>
                </div>
              </div>
              
              <hr/>
              <h5 class="mbot10"><?php echo _l('overview_error_explainer'); ?></h5>
              <div class="alert alert-info small"><?php echo _l('overview_error_explainer_description'); ?></div>
              <div class="row">
                <div class="col-sm-6">
                  <label><?php echo _l('overview_enable_error_explainer'); ?></label>
                  <select name="project_agent_error_explainer_enabled" class="form-control">
                    <option value="0" <?php echo empty($opts['project_agent_error_explainer_enabled'])?'selected':''; ?>>Off</option>
                    <option value="1" <?php echo !empty($opts['project_agent_error_explainer_enabled'])?'selected':''; ?>>On</option>
                  </select>
                </div>
                <div class="col-sm-6">
                  <label><?php echo _l('overview_gemini_child_api_key'); ?></label>
                  <input type="password" class="form-control" name="project_agent_error_explainer_api_key" value="<?php echo html_escape((string)$opts['project_agent_error_explainer_api_key']); ?>" placeholder="<?php echo _l('overview_gemini_child_api_key_placeholder'); ?>">
                </div>
              </div>
              <?php if (has_permission('project_agent', '', 'admin')): ?>
              <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> <?php echo _l('overview_save_settings'); ?></button>
              <?php else: ?>
              <div class="alert alert-info mtop10"><?php echo _l('overview_view_only_access'); ?></div>
              <?php endif; ?>
            </form>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="panel_s">
          <div class="panel-body">
            <h5 class="no-margin"><?php echo _l('overview_quick_links'); ?></h5>
            <ul class="list-unstyled mtop10">
              <li><a href="<?php echo admin_url('project_agent/ai'); ?>"><i class="fa fa-comments"></i> <?php echo _l('overview_ai_room_link'); ?></a></li>
              <li><a href="<?php echo admin_url('project_agent/actions'); ?>"><i class="fa fa-cogs"></i> <?php echo _l('overview_manage_actions_link'); ?></a></li>
              <li><a href="<?php echo admin_url('projects'); ?>"><i class="fa fa-folder-open"></i> <?php echo _l('overview_projects_link'); ?></a></li>
              <li><a href="<?php echo admin_url('project_agent/health'); ?>"><i class="fa fa-heartbeat"></i> <?php echo _l('overview_module_health_link'); ?></a></li>
            </ul>
            <hr/>
            <h5 class="no-margin"><?php echo _l('overview_status'); ?></h5>
            <div class="small mtop10">
              <div><?php echo _l('overview_ai_available_status'); ?>: <strong><?php echo $ai_available? _l('common_yes') : _l('common_no'); ?></strong></div>
              <div><?php echo _l('overview_provider_option'); ?>: <strong><?php echo html_escape((string)$opts['project_agent_ai_provider']); ?></strong></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php init_tail(); ?>
<script>
(function(){
  // Helper function to hide table loading indicators
  function hideTableLoading(tableSelector) {
    var $table = $(tableSelector);
    if ($table.length) {
      var $scope = $table.closest('.table-responsive, .panel-body, .content, .row');

      // Remove the Perfex loading class from any wrappers
      $table.closest('.dataTables_wrapper.table-loading').removeClass('table-loading');
      $table.parents('.table-loading').removeClass('table-loading');

      // Hide various loading indicators
      $scope.find('.table-loading-overlay, .loading, .spinner, .fa-spinner, .loading-text').hide();

      // Remove global DT loader if present
      $('body').find('.dt-loader').remove();

      // Ensure table and wrapper are visible
      $table.removeClass('hide').css({ visibility: 'visible', opacity: 1 }).show();
      $table.closest('.dataTables_wrapper').removeClass('hide').css({ visibility: 'visible', opacity: 1 }).show();
      $scope.filter(':hidden').show();
    }
  }

  var $btn = document.getElementById('pa-schema-run');
  if ($btn) {
    $btn.addEventListener('click', function(){
      var cbs = document.querySelectorAll('#pa-schema-table .pa-schema-cb:checked');
      var $out = document.getElementById('pa-schema-output');
      var sel = [];
      for (var i=0;i<cbs.length;i++){ sel.push(cbs[i].value); }
      if (!sel.length) { alert('<?php echo _l('overview_select_at_least_one_table'); ?>'); return; }
      var tables = sel.join(',');
      $btn.disabled = true; $btn.innerHTML = '<i class="fa fa-spin fa-spinner"></i> <?php echo _l('overview_learning'); ?>';
      $.post(admin_url + 'project_agent/learn_schema', { tables: tables })
        .done(function(r){
          try { if (typeof r !== 'string') r = JSON.stringify(r, null, 2); } catch(e){}
          $out.style.display = 'block';
          $out.textContent = r;
        })
        .fail(function(xhr){
          $out.style.display = 'block';
          $out.textContent = 'Error: ' + (xhr.responseText || xhr.status);
        })
        .always(function(){
          $btn.disabled = false; $btn.innerHTML = '<i class="fa fa-magic"></i> <?php echo _l('overview_run_learning'); ?>';
        });
    });
  }
  // Learn Related Tables (by selected actions or all active)
  var $btnRelated = document.getElementById('pa-learn-related-run');
  if ($btnRelated) {
    $btnRelated.addEventListener('click', function(){
      var sel = $('#pa-learn-related-actions').val() || [];
      $btnRelated.disabled = true; var old = $btnRelated.innerHTML; $btnRelated.innerHTML = '<i class="fa fa-spin fa-spinner"></i> Learning...';
      $.post(admin_url + 'project_agent/learn_related_tables', { action_ids: JSON.stringify(sel) })
        .done(function(r){ try { if (typeof r !== 'string') r = JSON.stringify(r, null, 2); } catch(e){}
               var $out = document.getElementById('pa-schema-output'); $out.style.display='block'; $out.textContent = r; })
        .fail(function(xhr){ var $out = document.getElementById('pa-schema-output'); $out.style.display='block'; $out.textContent = 'Error: ' + (xhr.responseText||xhr.status); })
        .always(function(){ $btnRelated.disabled=false; $btnRelated.innerHTML = old; });
    });
  }
  // Select all toggle
  var $all = document.getElementById('pa-schema-select-all');
  if ($all) {
    $all.addEventListener('change', function(){
      var cbs = document.querySelectorAll('#pa-schema-table .pa-schema-cb');
      for (var i=0;i<cbs.length;i++){ cbs[i].checked = $all.checked; }
    });
  }
  // Enhance with Perfex DataTables helper if available
  try {
    // Check if table exists and has content
    var $table = $('#pa-schema-table');
    if (!$table.length || $table.find('tbody tr').length === 0) {
      console.log('Table not found or empty, skipping DataTable initialization');
      hideTableLoading('#pa-schema-table');
      return;
    }
    
    // Ensure all input elements have proper values to avoid toLowerCase errors
    $table.find('input[type="checkbox"]').each(function() {
      var $input = $(this);
      if (!$input.attr('value') || $input.attr('value') === undefined) {
        $input.attr('value', '');
      }
      // Also ensure the input has a proper name attribute
      if (!$input.attr('name')) {
        $input.attr('name', 'checkbox_' + Math.random().toString(36).substr(2, 9));
      }
    });
    
    // Ensure all select elements have proper values
    $table.find('select').each(function() {
      var $select = $(this);
      if (!$select.attr('value') || $select.attr('value') === undefined) {
        $select.attr('value', '');
      }
    });
    
    // Ensure all text inputs have proper values
    $table.find('input[type="text"], input[type="number"], textarea').each(function() {
      var $input = $(this);
      if (!$input.attr('value') || $input.attr('value') === undefined) {
        $input.attr('value', '');
      }
    });
    
    // Additional safety: ensure all table elements are properly initialized
    $table.find('td, th').each(function() {
      var $cell = $(this);
      if (!$cell.text() && !$cell.find('input, select, textarea').length) {
        $cell.text(' ');
      }
    });
    
    // Check if DataTable is available and not already initialized
    if (typeof $.fn.DataTable === 'function' && !$.fn.DataTable.isDataTable('#pa-schema-table')) {
      // Always use a plain DataTable here to avoid Perfex wrappers
      // that expect AJAX URLs and may read undefined values.
      try {
        var paSchemaTable = $('#pa-schema-table').DataTable({
          pageLength: 100,
          order: [[1,'asc']],
          columnDefs: [
            { orderable:false, targets: 0 },
            { orderable:false, targets: 2 },
            { orderable:false, targets: 3 }
          ],
          searching: false,
          lengthChange: false,
          processing: false,
          autoWidth: false,
          deferRender: true,
          dom: 'rtip',
          language: { emptyTable: 'No data available in table' },
          initComplete: function() {
            hideTableLoading('#pa-schema-table');
          }
        });
        window.paSchemaTable = paSchemaTable;
      } catch (dtError) {
        console.warn('DataTable initialization failed:', dtError);
        hideTableLoading('#pa-schema-table');
      }
    } else if (typeof $.fn.DataTable !== 'function') {
      // DataTable is not available, just hide loading
      console.log('DataTable not available, skipping initialization');
      hideTableLoading('#pa-schema-table');
    } else {
      // If DataTable is already initialized, hide loading immediately
      hideTableLoading('#pa-schema-table');
    }

    // Init grouping table by action if present
    if (typeof $.fn.DataTable === 'function' && !$.fn.DataTable.isDataTable('#pa-actions-group-table')) {
      try {
        $('#pa-actions-group-table').DataTable({
          pageLength: 25,
          order: [[1,'asc']],
          columnDefs: [
            { orderable:false, targets: 2 }
          ],
          searching: true,
          lengthChange: false,
          autoWidth: false,
          deferRender: true,
          dom: 'rtip',
          language: { emptyTable: 'No data available' }
        });
      } catch (e2) { console.warn('Actions group table init failed:', e2); }
    }

    // Perfex-style select filter for schema table by action
    var selectedActionFilter = '';
    if (typeof $.fn.dataTable !== 'undefined') {
      // Register custom filter once
      $.fn.dataTable.ext.search.push(function(settings, searchData, index, rowData, counter){
        if (!selectedActionFilter) return true;
        if (!settings || !settings.nTable || settings.nTable.id !== 'pa-schema-table') return true;
        try {
          var rowNode = settings.aoData[index].nTr;
          return $(rowNode).find('.pa-action-badge[data-action-id="'+selectedActionFilter+'"]').length > 0;
        } catch(e){ return true; }
      });
    }
    $('#pa-action-filter').on('changed.bs.select', function(){
      selectedActionFilter = $(this).val() || '';
      if (window.paSchemaTable) { window.paSchemaTable.draw(); }
    });
  } catch(e){
    console.warn('2. DataTable initialization failed:', e);
    console.log('Error details:', {
      message: e.message,
      stack: e.stack,
      name: e.name
    });
    // Hide loading even if initialization fails
    try {
      hideTableLoading('#pa-schema-table');
    } catch (hideError) {
      console.warn('Failed to hide table loading:', hideError);
    }
  }
})();
</script>
