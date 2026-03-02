<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="panel_s">
  <div class="panel-body">
    <h4 class="mb-3"><i class="fa fa-cogs"></i> Agent Actions</h4>
    <div class="table-responsive">
      <table class="table table-striped table-bordered">
        <thead>
          <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Description</th>
            <th>Risk</th>
            <th>Confirm</th>
            <th>Active</th>
            <th>Prompt Override</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($actions as $a): ?>
          <tr>
            <td><code><?php echo html_escape($a['action_id']); ?></code></td>
            <td><?php echo html_escape($a['name']); ?></td>
            <td class="small text-muted" style="max-width:380px; white-space:normal;"><?php echo html_escape($a['description']); ?></td>
            <td><span class="badge badge-<?php echo $a['risk_level']==='high'?'danger':($a['risk_level']==='medium'?'warning':'success'); ?>"><?php echo $a['risk_level']; ?></span></td>
            <td><?php echo !empty($a['requires_confirm']) ? '<i class="fa fa-check text-warning"></i>' : ''; ?></td>
            <td>
              <label class="switch">
                <input type="checkbox" class="js-toggle" data-id="<?php echo $a['action_id']; ?>" <?php echo !empty($a['is_active']) ? 'checked' : ''; ?>>
                <span class="slider round"></span>
              </label>
            </td>
            <td>
              <button class="btn btn-default btn-sm js-edit" data-id="<?php echo $a['action_id']; ?>" data-name="<?php echo html_escape($a['name']); ?>" data-prompt="<?php echo html_escape(isset($a['prompt_override'])?$a['prompt_override']:''); ?>">
                <i class="fa fa-pencil"></i> Edit
              </button>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
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
</style>

<!-- Modal -->
<div class="modal fade" id="actionPromptModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fa fa-pencil"></i> Edit Prompt Override</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-info small">This prompt is appended as a hint for the AI when suggesting/using this action.</div>
        <form id="actionPromptForm">
          <input type="hidden" id="ap_action_id" name="action_id" value="">
          <div class="form-group">
            <label>Action</label>
            <input type="text" class="form-control" id="ap_action_name" disabled>
          </div>
          <div class="form-group">
            <label>Prompt Override</label>
            <textarea class="form-control" id="ap_prompt" name="prompt_override" rows="6" placeholder="e.g., Use when user asks for billing overview; require project_id"></textarea>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" id="ap_save">Save</button>
      </div>
    </div>
  </div>
  </div>

<script>
(function(){
  var $ = window.jQuery;
  if(!$) return;
  $('.js-toggle').on('change', function(){
    var id = $(this).data('id');
    var on = $(this).is(':checked') ? 1 : 0;
    $.post(admin_url + 'project_agent/toggle_action', { action_id:id, is_active:on })
      .fail(function(){ alert('Failed to update'); });
  });
  $('.js-edit').on('click', function(){
    var id = $(this).data('id');
    var nm = $(this).data('name');
    var pr = $(this).data('prompt')||'';
    $('#ap_action_id').val(id);
    $('#ap_action_name').val(nm);
    $('#ap_prompt').val(pr);
    $('#actionPromptModal').modal('show');
  });
  $('#ap_save').on('click', function(){
    var data = {
      action_id: $('#ap_action_id').val(),
      prompt_override: $('#ap_prompt').val()
    };
    $.post(admin_url + 'project_agent/save_action_prompt', data)
      .done(function(resp){ if(resp && resp.success){ $('#actionPromptModal').modal('hide'); location.reload(); } else { alert('Save failed'); } })
      .fail(function(){ alert('Save failed'); });
  });
})();
</script>

