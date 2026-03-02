<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="modal fade" id="actionLogsModal" tabindex="-1" role="dialog" aria-labelledby="actionLogsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="actionLogsModalLabel">
                    <i class="fa fa-list-alt"></i> <?php echo _l('project_agent_action_logs'); ?>
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Loading indicator -->
                <div id="actionLogsLoading" class="text-center" style="display: none;">
                    <i class="fa fa-spinner fa-spin"></i> <?php echo _l('loading'); ?>...
                </div>

                <!-- Error message -->
                <div id="actionLogsError" class="alert alert-danger" style="display: none;">
                    <i class="fa fa-exclamation-triangle"></i>
                    <span id="actionLogsErrorMessage"></span>
                </div>

                <!-- Filter controls -->
                <div class="row mb-3" id="actionLogsFilters">
                    <div class="col-md-4">
                        <select class="form-control" id="statusFilter">
                            <option value=""><?php echo _l('all'); ?> <?php echo _l('project_agent_status'); ?></option>
                            <option value="success"><?php echo _l('project_agent_status_success'); ?></option>
                            <option value="failed"><?php echo _l('project_agent_status_failed'); ?></option>
                            <option value="running"><?php echo _l('project_agent_status_running'); ?></option>
                            <option value="queued"><?php echo _l('project_agent_status_queued'); ?></option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <input type="text" class="form-control" id="actionIdFilter" placeholder="<?php echo _l('project_agent_action_id'); ?>">
                    </div>
                    <div class="col-md-4">
                        <button class="btn btn-primary btn-sm" id="applyFilters">
                            <i class="fa fa-filter"></i> <?php echo _l('filter'); ?>
                        </button>
                        <button class="btn btn-secondary btn-sm" id="clearFilters">
                            <i class="fa fa-times"></i> <?php echo _l('clear'); ?>
                        </button>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered" id="actionLogsTable">
                                <thead>
                                    <tr>
                                        <th><?php echo _l('project_agent_action_id'); ?></th>
                                        <th><?php echo _l('project_agent_status'); ?></th>
                                        <th><?php echo _l('project_agent_executed_at'); ?></th>
                                        <th><?php echo _l('project_agent_executed_by'); ?></th>
                                        <th><?php echo _l('project_agent_actions'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Data will be loaded via AJAX -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <?php echo _l('close'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Action Details Modal -->
<div class="modal fade" id="actionDetailsModal" tabindex="-1" role="dialog" aria-labelledby="actionDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="actionDetailsModalLabel">
                    <i class="fa fa-info-circle"></i> <?php echo _l('project_agent_action_details'); ?>
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <!-- Action Info -->
                        <div class="panel panel-info">
                            <div class="panel-heading">
                                <h4 class="panel-title">
                                    <i class="fa fa-cog"></i> <?php echo _l('project_agent_action_info'); ?>
                                </h4>
                            </div>
                            <div class="panel-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <strong><?php echo _l('project_agent_action_id'); ?>:</strong>
                                        <span id="detailActionId">-</span>
                                    </div>
                                    <div class="col-md-6">
                                        <strong><?php echo _l('project_agent_status'); ?>:</strong>
                                        <span id="detailStatus" class="label label-info">-</span>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <strong><?php echo _l('project_agent_executed_at'); ?>:</strong>
                                        <span id="detailExecutedAt">-</span>
                                    </div>
                                    <div class="col-md-6">
                                        <strong><?php echo _l('project_agent_executed_by'); ?>:</strong>
                                        <span id="detailExecutedBy">-</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Parameters -->
                        <div class="panel panel-primary">
                            <div class="panel-heading">
                                <h4 class="panel-title">
                                    <i class="fa fa-list"></i> <?php echo _l('project_agent_parameters'); ?>
                                </h4>
                            </div>
                            <div class="panel-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped" id="parametersTable">
                                        <thead>
                                            <tr>
                                                <th><?php echo _l('project_agent_parameter'); ?></th>
                                                <th><?php echo _l('project_agent_value'); ?></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <!-- Parameters will be loaded here -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Results -->
                        <div class="panel panel-success">
                            <div class="panel-heading">
                                <h4 class="panel-title">
                                    <i class="fa fa-check-circle"></i> <?php echo _l('project_agent_results'); ?>
                                </h4>
                            </div>
                            <div class="panel-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped" id="resultsTable">
                                        <thead>
                                            <tr>
                                                <th><?php echo _l('project_agent_field'); ?></th>
                                                <th><?php echo _l('project_agent_value'); ?></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <!-- Results will be loaded here -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Error Message (if any) -->
                        <div class="panel panel-danger" id="errorPanel" style="display: none;">
                            <div class="panel-heading">
                                <h4 class="panel-title">
                                    <i class="fa fa-exclamation-triangle"></i> <?php echo _l('project_agent_error_message'); ?>
                                </h4>
                            </div>
                            <div class="panel-body">
                                <div id="errorMessage" class="alert alert-danger">
                                    <!-- Error message will be displayed here -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <?php echo _l('close'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
(function(){
  function bindHandlers($){
    try {
      $('#actionLogsModal').off('show.bs.modal.__pa').on('show.bs.modal.__pa', function (e) {
        var sessionId = null;
        try { sessionId = $(e.relatedTarget).data('session-id'); } catch(ex){}
        if (!sessionId) { try { sessionId = $('#actionLogsModal').data('session-id'); } catch(ex){} }
        if (!sessionId && window.PA_DEBUG && PA_DEBUG.session_id) { sessionId = PA_DEBUG.session_id; }

        // Fallback: try to get latest session from localStorage or find recent session
        if (!sessionId) {
          try {
            // Check if there's a recent session stored
            var recentSession = localStorage.getItem('pa_recent_session_id');
            if (recentSession && parseInt(recentSession) > 3) { // Only use if it's a newer session
              sessionId = recentSession;
              console.log('Action Logs Modal - Using recent session from localStorage:', sessionId);
            } else {
              // If no recent session, try to load the latest session from database
              console.log('Action Logs Modal - No recent session found, will load latest available');
              // We'll load latest session in loadActionLogs function
            }
          } catch(ex) {
            console.log('Action Logs Modal - Could not get recent session from localStorage');
          }
        }

        console.log('Action Logs Modal - Loading session ID:', sessionId);
        console.log('Action Logs Modal - Event relatedTarget:', e.relatedTarget);
        console.log('Action Logs Modal - Modal data session-id:', $('#actionLogsModal').data('session-id'));
        console.log('Action Logs Modal - PA_DEBUG session_id:', window.PA_DEBUG ? window.PA_DEBUG.session_id : 'none');

        if (sessionId) {
          loadActionLogs(sessionId);
        } else {
          console.log('Action Logs Modal - No session ID found, showing error');
          showActionLogsError('No active session found. Please refresh the page and try again.');
        }
      });

      $(document).off('click.__pa', '.btn-action-details').on('click.__pa', '.btn-action-details', function(){
        var logId = $(this).data('log-id');
        if (logId) { loadActionDetails(logId); }
      });

      // Filter functionality
      $(document).off('click.__pa', '#applyFilters').on('click.__pa', '#applyFilters', function(){
        var sessionId = $('#actionLogsModal').data('session-id') || (window.PA_DEBUG && PA_DEBUG.session_id);
        var statusFilter = $('#statusFilter').val();
        var actionIdFilter = $('#actionIdFilter').val();
        if (sessionId) { loadActionLogs(sessionId, statusFilter, actionIdFilter); }
      });

      $(document).off('click.__pa', '#clearFilters').on('click.__pa', '#clearFilters', function(){
        $('#statusFilter').val('');
        $('#actionIdFilter').val('');
        var sessionId = $('#actionLogsModal').data('session-id') || (window.PA_DEBUG && PA_DEBUG.session_id);
        if (sessionId) { loadActionLogs(sessionId); }
      });

      // Enter key support for action ID filter
      $(document).off('keypress.__pa', '#actionIdFilter').on('keypress.__pa', '#actionIdFilter', function(e){
        if (e.which === 13) { // Enter key
          $('#applyFilters').click();
        }
      });

    } catch(e) { /* ignore */ }
  }
  function whenJQReady(cb){
    if (window.jQuery) { cb(window.jQuery); return; }
    var tries = 0; var t = setInterval(function(){
      if (window.jQuery){ clearInterval(t); cb(window.jQuery); }
      else if (++tries>200){ clearInterval(t); }
    }, 50);
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function(){ whenJQReady(bindHandlers); });
  } else { whenJQReady(bindHandlers); }
})();

function loadActionLogs(sessionId, statusFilter = '', actionIdFilter = '') {
    var $ = window.jQuery || window.$;
    if (!$) { return; }

    // Show loading indicator
    $('#actionLogsLoading').show();
    $('#actionLogsError').hide();
    $('#actionLogsFilters').hide();

    var data = {};
    if (sessionId) {
        data.session_id = sessionId;
    } else {
        // If no session ID, load recent logs from all sessions (last 24 hours)
        data.limit = 20; // Load more records when no specific session
        console.log('Action Logs - Loading recent logs from all sessions (no session specified)');
    }

    if (statusFilter) data.status = statusFilter;
    if (actionIdFilter) data.action_id = actionIdFilter;

    $.ajax({
        url: '<?php echo admin_url("project_agent/get_action_logs"); ?>',
        type: 'GET',
        data: data,
        dataType: 'json',
        success: function(response) {
            $('#actionLogsLoading').hide();

            if (response.success) {
                renderActionLogsTable(response.logs);
                $('#actionLogsFilters').show();

                if (!sessionId && response.logs.length > 0) {
                    console.log('Action Logs - Loaded recent logs from all sessions, found', response.logs.length, 'records');
                }
            } else {
                showActionLogsError(response.error || 'Failed to load action logs');
            }
        },
        error: function(xhr, status, error) {
            $('#actionLogsLoading').hide();
            showActionLogsError('Error loading action logs: ' + error);
        }
    });
}

function showActionLogsError(message) {
    var $ = window.jQuery || window.$;
    if (!$) { return; }

    $('#actionLogsErrorMessage').text(message);
    $('#actionLogsError').show();
    $('#actionLogsTable tbody').empty();
    $('#actionLogsFilters').hide();
}

function renderActionLogsTable(logs) {
    var $ = window.jQuery || window.$;
    if (!$) { return; }
    var tbody = $('#actionLogsTable tbody');
    tbody.empty();
    
    if (logs.length === 0) {
        tbody.append('<tr><td colspan="5" class="text-center"><?php echo _l("no_data_found"); ?></td></tr>');
        return;
    }
    
    logs.forEach(function(log) {
        var statusClass = getStatusClass(log.status);
        var statusText = getStatusText(log.status);
        var executedBy = log.executed_by || '-';
        var executedAt = log.executed_at || '-';
        
        var row = '<tr>' +
            '<td>' + (log.action_id || '-') + '</td>' +
            '<td><span class="label ' + statusClass + '">' + statusText + '</span></td>' +
            '<td>' + executedAt + '</td>' +
            '<td>' + executedBy + '</td>' +
            '<td>' +
                '<button class="btn btn-info btn-xs btn-action-details" data-log-id="' + log.log_id + '">' +
                    '<i class="fa fa-eye"></i> <?php echo _l("view_details"); ?>' +
                '</button>' +
            '</td>' +
        '</tr>';
        
        tbody.append(row);
    });
}

function loadActionDetails(logId) {
    var $ = window.jQuery || window.$;
    if (!$) { return; }
    $.ajax({
        url: '<?php echo admin_url("project_agent/get_action_log_details"); ?>',
        type: 'GET',
        data: { log_id: logId },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                renderActionDetails(response.log);
                $('#actionDetailsModal').modal('show');
            } else {
                alert_float('danger', response.error || 'Failed to load action details');
            }
        },
        error: function() {
            alert_float('danger', 'Error loading action details');
        }
    });
}

function renderActionDetails(log) {
    var $ = window.jQuery || window.$;
    if (!$) { return; }
    // Update action info
    $('#detailActionId').text(log.action_id || '-');
    $('#detailStatus').text(getStatusText(log.status)).removeClass().addClass('label ' + getStatusClass(log.status));
    $('#detailExecutedAt').text(log.executed_at || '-');
    $('#detailExecutedBy').text(log.executed_by || '-');
    
    // Render parameters
    var paramsTbody = $('#parametersTable tbody');
    paramsTbody.empty();
    
    if (log.params && typeof log.params === 'object') {
        Object.keys(log.params).forEach(function(key) {
            var value = log.params[key];
            var displayValue = value;

            if (typeof value === 'object') {
                displayValue = '<pre>' + JSON.stringify(value, null, 2) + '</pre>';
            } else if (typeof value === 'string' && value.length > 50) {
                displayValue = '<span title="' + value + '">' + value.substring(0, 50) + '...</span>';
            }

            paramsTbody.append('<tr><td><strong>' + key + '</strong></td><td>' + displayValue + '</td></tr>');
        });
    } else {
        paramsTbody.append('<tr><td colspan="2" class="text-center"><?php echo _l("no_parameters"); ?></td></tr>');
    }
    
    // Render results
    var resultsTbody = $('#resultsTable tbody');
    resultsTbody.empty();
    
    if (log.result && typeof log.result === 'object') {
        Object.keys(log.result).forEach(function(key) {
            var value = log.result[key];
            var displayValue = value;

            if (typeof value === 'object') {
                displayValue = '<pre>' + JSON.stringify(value, null, 2) + '</pre>';
            } else if (typeof value === 'string' && value.length > 50) {
                displayValue = '<span title="' + value + '">' + value.substring(0, 50) + '...</span>';
            }

            resultsTbody.append('<tr><td><strong>' + key + '</strong></td><td>' + displayValue + '</td></tr>');
        });
    } else {
        resultsTbody.append('<tr><td colspan="2" class="text-center"><?php echo _l("no_results"); ?></td></tr>');
    }
    
    // Show/hide error panel
    if (log.error_message) {
        $('#errorMessage').text(log.error_message);
        $('#errorPanel').show();
    } else {
        $('#errorPanel').hide();
    }
}

function getStatusClass(status) {
    switch(status) {
        case 'success': return 'label-success';
        case 'failed': return 'label-danger';
        case 'running': return 'label-warning';
        case 'queued': return 'label-info';
        default: return 'label-default';
    }
}

function getStatusText(status) {
    switch(status) {
        case 'success': return '<?php echo _l("project_agent_status_success"); ?>';
        case 'failed': return '<?php echo _l("project_agent_status_failed"); ?>';
        case 'running': return '<?php echo _l("project_agent_status_running"); ?>';
        case 'queued': return '<?php echo _l("project_agent_status_queued"); ?>';
        default: return status;
    }
}
</script>
