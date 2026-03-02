(function(){
  $(function(){ if (typeof admin_url === 'undefined') { return; } bindPMA(); });

  window.pma_open_project_composer = function(projectId){
    $('#project_composer_modal').modal('show');
    $('#modal_content').load(admin_url + 'project_management_agent?project_id=' + projectId);
  };
  window.pma_start_composition_from_project = function(projectId){
    try { $('#project_composer_modal').modal('show'); } catch(e){}
    try { $('#modal_content').load(admin_url + 'project_management_agent?project_id=' + projectId, function(){ bindPMA(); $('#source_project').val(projectId).selectpicker('refresh'); startComposition(); }); } catch(e){}
  };

  function bindPMA(){
    $(document).off('click', '#btn_start_composition').on('click', '#btn_start_composition', startComposition);
    $(document).off('click', '#btn_proceed_to_composer').on('click', '#btn_proceed_to_composer', proceedToComposer);
    $(document).off('click', '#btn_back_to_breakdown').on('click', '#btn_back_to_breakdown', backToBreakdown);
    $(document).off('click', '#btn_execute_clone').on('click', '#btn_execute_clone', executeClone);
    $(document).off('change', '#new_start_date, #new_deadline').on('change', '#new_start_date, #new_deadline', updateTimelinePreview);
    $(document).off('click', '.task-row').on('click', '.task-row', showTaskDetails);
  }

  function startComposition(){
    var projectId = $('#source_project').val();
    if (!projectId) { 
      if (typeof alert_float !== 'undefined') { alert_float('warning', 'Please select a project'); } 
      return; 
    }
    
    console.log('Starting composition for project:', projectId); // Debug log
    
    showStep('step_composition_progress');
    updateProgress(10, 'Starting composition...');
    
    $.post(admin_url + 'project_management_agent/compose_project', { project_id: projectId }, function(resp){
      console.log('Composition response:', resp); // Debug log
      
      if (!resp || !resp.success){ 
        failProgress(resp && resp.message ? resp.message : 'Failed to create composition'); 
        return; 
      }
      
      window.currentCompositionId = resp.composition_id;
      updateProgress(50, 'Running AI breakdown...');
      
      // Run AI breakdown directly instead of polling
      runAIBreakdown(resp.composition_id);
      
    }, 'json').fail(function(xhr, status, error){
      console.error('Composition failed:', xhr, status, error); // Debug log
      failProgress('Server error during composition: ' + error);
    });
  }

  function pollBreakdownStatus(compositionId) {
    var pollCount = 0;
    var maxPolls = 30; // 30 seconds max
    
    var poll = function() {
      pollCount++;
      
      $.get(admin_url + 'project_management_agent/get_breakdown_status/' + compositionId, function(resp) {
        console.log('Breakdown status:', resp); // Debug log
        
        if (resp && resp.success) {
          if (resp.status === 'ready') {
            updateProgress(100, 'AI breakdown completed');
            showBreakdownResults(resp.breakdown);
            return;
          } else if (resp.status === 'failed') {
            failProgress('AI breakdown failed: ' + (resp.message || 'Unknown error'));
            return;
          }
        }
        
        // Continue polling if not ready and not failed
        if (pollCount < maxPolls) {
          setTimeout(poll, 1000);
        } else {
          failProgress('AI breakdown timeout');
        }
      }, 'json').fail(function() {
        if (pollCount < maxPolls) {
          setTimeout(poll, 1000);
        } else {
          failProgress('Failed to get breakdown status');
        }
      });
    };
    
    poll();
  }

  function runAIBreakdown(compositionId){
    $.post(admin_url + 'project_management_agent/ai_breakdown', { composition_id: compositionId }, function(resp){
      console.log('AI breakdown response:', resp); // Debug log
      
      if (!resp || !resp.success){ 
        failProgress(resp && resp.message ? resp.message : 'AI breakdown failed'); 
        return; 
      }
      
      updateProgress(100, 'AI breakdown completed');
      
      // Display breakdown results
      var html = '<div class="alert alert-success">';
      html += '<h6><i class="fa fa-check-circle"></i> AI Analysis Results</h6>';
      html += '<p>AI breakdown completed successfully.</p>';
      html += '</div>';
      
      if (resp.breakdown && resp.breakdown.html) {
        html += resp.breakdown.html;
      } else if (resp.breakdown && resp.breakdown.details) {
        html += '<div class="panel panel-default">';
        html += '<div class="panel-heading"><h6>Project Details</h6></div>';
        html += '<div class="panel-body">';
        html += resp.breakdown.details;
        html += '</div></div>';
      } else {
        html += '<div class="panel panel-default">';
        html += '<div class="panel-heading"><h6>Project Analysis</h6></div>';
        html += '<div class="panel-body">';
        html += '<p>Project data has been analyzed and is ready for cloning.</p>';
        html += '</div></div>';
      }
      
      $('#breakdown_content').html(html);
      showStep('step_ai_breakdown');
    }, 'json').fail(function(xhr, status, error){
      console.error('AI breakdown failed:', xhr, status, error); // Debug log
      failProgress('Server error during AI breakdown: ' + error);
    });
  }

  function showBreakdownResults(breakdown) {
    var html = '<div class="alert alert-success">';
    html += '<h6><i class="fa fa-check-circle"></i> AI Analysis Results</h6>';
    html += '<p>' + (breakdown.summary || 'AI breakdown completed.') + '</p>';
    html += '</div>';
    
    if (breakdown.details) {
      html += '<div class="panel panel-default">';
      html += '<div class="panel-heading"><h6>Project Details</h6></div>';
      html += '<div class="panel-body">';
      html += breakdown.details;
      html += '</div></div>';
    }
    
    $('#breakdown_content').html(html);
    showStep('step_ai_breakdown');
  }

  function proceedToComposer(){
    showStep('step_project_composer');
    init_ajax_search('customer', '#new_client.ajax-search');
    updateTimelinePreview();
  }
  
  function backToBreakdown(){ 
    showStep('step_ai_breakdown'); 
  }

  function executeClone(){
    if (!validateCloneForm()) { return; }
    var cfg = {
      new_project_name: $('#new_project_name').val(),
      new_client: $('#new_client').val(),
      new_start_date: $('#new_start_date').val(),
      new_deadline: $('#new_deadline').val(),
      new_budget: $('#new_budget').val(),
      new_status: $('#new_status').val(),
      new_description: $('#new_description').val(),
      clone_tasks: $('#clone_tasks').is(':checked'),
      clone_milestones: $('#clone_milestones').is(':checked'),
      clone_files: $('#clone_files').is(':checked'),
      clone_discussions: $('#clone_discussions').is(':checked'),
      clone_team: $('#clone_team').is(':checked'),
      clone_notes: $('#clone_notes').is(':checked'),
      settings: $('#clone_settings').is(':checked'),
      adjust_timeline: $('#adjust_timeline').is(':checked')
    };
    showStep('step_clone_progress');
    updateCloneProgress(10, 'Starting clone...');
    $.post(admin_url + 'project_management_agent/clone_project', { composition_id: window.currentCompositionId, clone_config: JSON.stringify(cfg) }, function(resp){
      if (!resp || !resp.success){ failClone(resp && resp.message ? resp.message : 'Clone failed'); return; }
      updateCloneProgress(100, 'Clone completed successfully');
      if (resp.new_project_id){ setTimeout(function(){ window.location.href = admin_url + 'projects/view/' + resp.new_project_id; }, 1500); }
    }, 'json').fail(function(){ failClone('Server error during clone'); });
  }

  function showStep(stepId){ 
    // Hide all steps
    $('#step_composition_progress, #step_ai_breakdown, #step_project_composer, #step_clone_progress').addClass('hidden');
    // Show the requested step
    $('#' + stepId).removeClass('hidden');
    console.log('Showing step:', stepId); // Debug log
  }

  function validateCloneForm(){
    if (!$('#new_project_name').val()){ alert_float('warning','Please enter a project name'); $('#new_project_name').focus(); return false; }
    if (!$('#new_client').val()){ alert_float('warning','Please select a client'); $('#new_client').focus(); return false; }
    if (!$('#new_start_date').val()){ alert_float('warning','Please select a start date'); $('#new_start_date').focus(); return false; }
    return true;
  }



  function updateTimelinePreview(){
    var startDate = $('#new_start_date').val();
    var deadline = $('#new_deadline').val();
    if (startDate){
      var preview = '<p><strong>New Timeline:</strong></p><p>Start Date: ' + startDate + '</p>';
      if (deadline){
        var start = new Date(startDate), end = new Date(deadline);
        var diffDays = Math.ceil(Math.abs(end - start) / (1000 * 60 * 60 * 24));
        preview += '<p>Deadline: ' + deadline + '</p><p>Duration: ' + diffDays + ' days</p>';
      }
      $('#timeline_preview').html(preview);
    }
  }

  function updateProgress(percent, msg){ 
    var $bar = $('#step_composition_progress .progress-bar'); 
    $bar.css('width', percent+'%').attr('aria-valuenow', percent); 
    $bar.find('.sr-only').text(percent + '% Complete'); 
    $('#composition_status').html('<i class="fa fa-info-circle"></i> ' + msg); 
  }
  
  function failProgress(msg){ 
    updateProgress(100, msg); 
    if (typeof alert_float !== 'undefined'){ alert_float('danger', msg); } 
  }
  
  function updateCloneProgress(percent, msg){ 
    var $bar = $('#step_clone_progress .progress-bar'); 
    $bar.css('width', percent+'%').attr('aria-valuenow', percent); 
    $bar.find('.sr-only').text(percent + '% Complete'); 
    $('#clone_status').html('<i class="fa fa-info-circle"></i> ' + msg); 
  }
  
  function failClone(msg){ 
    updateCloneProgress(100, msg); 
    if (typeof alert_float !== 'undefined'){ alert_float('danger', msg); } 
  }

  function showTaskDetails(e){
    var $row = $(this);
    var taskId = $row.data('task-id');
    
    if (!taskId || taskId <= 0) {
      if (typeof alert_float !== 'undefined') {
        alert_float('warning', 'Task ID not found');
      }
      return;
    }
    
    // Open task details in new window/tab (Perfex CRM standard)
    var taskUrl = admin_url + 'tasks/view/' + taskId;
    window.open(taskUrl, '_blank');
  }
})();
