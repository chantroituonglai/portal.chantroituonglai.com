<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$pageMode = $page_mode ?? 'overview';
$isOverviewPage = $pageMode === 'overview';
$showSettingsPage = $pageMode === 'bridge_settings';
$showScopePage = $pageMode === 'scope_settings';
$showPipelinePage = $pageMode === 'pipeline_logs';
$showBridgePage = $pageMode === 'bridge_queue';
$showGatewayPage = $pageMode === 'gateway_logs';
$isLogPage = $showPipelinePage || $showBridgePage || $showGatewayPage;
$activeScopeCount = 0;
foreach (($scope_keys ?? []) as $scopeKey) {
    if (!empty($bridge_settings['openclaw_bridge_scope_' . $scopeKey])) {
        $activeScopeCount++;
    }
}

$pageTitleMap = [
    'overview' => 'OpenClaw Gateway',
    'bridge_settings' => 'Bridge Settings',
    'scope_settings' => 'Scope Settings',
    'pipeline_logs' => 'Pipeline Logs',
    'bridge_queue' => 'Bridge Queue',
    'gateway_logs' => 'Gateway Logs',
];

$pageDescriptionMap = [
    'overview' => 'Overview of bridge activity, queue health, recent alerts, and stream summary.',
    'bridge_settings' => 'Configure endpoint, retry policy, auth token, and payload behavior for OpenClaw delivery.',
    'scope_settings' => 'Manage active module scopes, allow and deny patterns, and payload field mapping.',
    'pipeline_logs' => 'Inspect two-way OpenClaw and Perfex pipeline traffic with filters and detail modal.',
    'bridge_queue' => 'Review queued bridge deliveries, retry attempts, HTTP responses, and recent errors.',
    'gateway_logs' => 'Inspect gateway requests, action routing, request IDs, status, and API errors.',
];
?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-12">
        <h4 class="tw-mt-0 tw-font-semibold tw-text-lg"><?php echo html_escape($pageTitleMap[$pageMode] ?? 'OpenClaw Gateway'); ?></h4>
        <p class="text-muted mtop5"><?php echo html_escape($pageDescriptionMap[$pageMode] ?? ''); ?></p>
      </div>
    </div>

    <div class="panel_s">
      <div class="panel-body">
        <ul class="nav nav-tabs nav-tabs-horizontal" role="tablist">
          <li role="presentation" class="<?php echo $isOverviewPage ? 'active' : ''; ?>">
            <a href="<?php echo admin_url('openclaw_gateway/openclaw_gateway_admin'); ?>">Overview</a>
          </li>
          <li role="presentation" class="<?php echo $showSettingsPage ? 'active' : ''; ?>">
            <a href="<?php echo admin_url('openclaw_gateway/openclaw_gateway_admin/bridge_settings'); ?>">Bridge Settings</a>
          </li>
          <li role="presentation" class="<?php echo $showScopePage ? 'active' : ''; ?>">
            <a href="<?php echo admin_url('openclaw_gateway/openclaw_gateway_admin/scope_settings'); ?>">Scopes</a>
          </li>
          <li role="presentation" class="<?php echo $showPipelinePage ? 'active' : ''; ?>">
            <a href="<?php echo admin_url('openclaw_gateway/openclaw_gateway_admin/pipeline_logs'); ?>">Pipeline Logs</a>
          </li>
          <li role="presentation" class="<?php echo $showBridgePage ? 'active' : ''; ?>">
            <a href="<?php echo admin_url('openclaw_gateway/openclaw_gateway_admin/bridge_queue'); ?>">Bridge Queue</a>
          </li>
          <li role="presentation" class="<?php echo $showGatewayPage ? 'active' : ''; ?>">
            <a href="<?php echo admin_url('openclaw_gateway/openclaw_gateway_admin/gateway_logs'); ?>">Gateway Logs</a>
          </li>
        </ul>
      </div>
    </div>

    <?php if ($isOverviewPage): ?>
    <div class="row">
      <div class="col-md-12">
        <div class="panel_s">
          <div class="panel-body">
            <h4 class="no-margin">System Snapshot</h4>
            <p class="text-muted mtop5">Quick health summary from pipeline, queue, and gateway activity in the last 24 hours.</p>
          </div>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-md-4">
        <div class="panel_s">
          <div class="panel-body">
            <h5 class="text-uppercase text-muted no-margin">24h Pipeline</h5>
            <h3 class="no-margin"><?php echo (int) (($overview_stats['window']['pipeline']['total'] ?? 0)); ?></h3>
            <p class="text-muted mtop10">Inbound: <?php echo (int) (($overview_stats['window']['pipeline']['inbound'] ?? 0)); ?>, Outbound: <?php echo (int) (($overview_stats['window']['pipeline']['outbound'] ?? 0)); ?>, Failed: <?php echo (int) (($overview_stats['window']['pipeline']['failed'] ?? 0)); ?></p>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="panel_s">
          <div class="panel-body">
            <h5 class="text-uppercase text-muted no-margin">24h Bridge Queue</h5>
            <h3 class="no-margin"><?php echo (int) (($overview_stats['window']['bridge']['total'] ?? 0)); ?></h3>
            <p class="text-muted mtop10">Failed: <?php echo (int) (($overview_stats['window']['bridge']['failed'] ?? 0)); ?></p>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="panel_s">
          <div class="panel-body">
            <h5 class="text-uppercase text-muted no-margin">24h Gateway</h5>
            <h3 class="no-margin"><?php echo (int) (($overview_stats['window']['gateway']['total'] ?? 0)); ?></h3>
            <p class="text-muted mtop10">Failed: <?php echo (int) (($overview_stats['window']['gateway']['failed'] ?? 0)); ?><?php if (!empty($overview_stats['window']['gateway']['avg_latency'])): ?>, Avg latency: <?php echo (int) $overview_stats['window']['gateway']['avg_latency']; ?>ms<?php endif; ?></p>
          </div>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-md-6">
        <div class="panel_s">
          <div class="panel-body">
            <h4 class="no-margin">Recent Alerts</h4>
            <hr class="hr-panel-heading" />
            <?php if (!empty($recent_alerts)): ?>
              <ul class="list-unstyled no-margin">
                <?php foreach ($recent_alerts as $alert): ?>
                <li class="mbot10">
                  <strong><?php echo html_escape($alert['title'] ?? 'Activity'); ?></strong>
                  <div class="text-muted"><?php echo html_escape($alert['message'] ?? ''); ?></div>
                  <small class="text-muted"><?php echo html_escape(($alert['stream'] ?? '') . ' - ' . ($alert['created_at'] ?? '')); ?></small>
                </li>
                <?php endforeach; ?>
              </ul>
            <?php else: ?>
              <p class="text-muted no-margin">No recent alerts.</p>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="panel_s">
          <div class="panel-body">
            <h4 class="no-margin">Stream Summary</h4>
            <hr class="hr-panel-heading" />
            <?php foreach (($overview_stats['streams'] ?? []) as $stream): ?>
            <div class="mbot10">
              <strong><?php echo html_escape($stream['label'] ?? 'Stream'); ?></strong>
              <div class="text-muted">Total records: <?php echo (int) ($stream['total'] ?? 0); ?></div>
              <small class="text-muted">Latest activity: <?php echo html_escape($stream['latest_at'] ?? 'N/A'); ?></small>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <?php if ($showSettingsPage): ?>
    <div class="row">
      <div class="col-md-12">
        <div class="panel_s">
          <div class="panel-body">
            <h4 class="no-margin">Bridge Settings</h4>
            <p class="text-muted mtop5">Endpoint, retry policy, authentication token, and payload delivery behavior.</p>
            <hr class="hr-panel-heading" />
            <form method="post" action="<?php echo admin_url('openclaw_gateway/openclaw_gateway_admin/bridge_settings'); ?>">
              <input type="hidden" name="ocg_settings_submit" value="1" />
              <input type="hidden" name="ocg_settings_section" value="bridge" />
              <div class="row">
                <div class="col-md-3">
                  <div class="checkbox checkbox-primary">
                    <input type="checkbox" id="openclaw_bridge_enabled" name="openclaw_bridge_enabled" value="1" <?php echo !empty($bridge_settings['openclaw_bridge_enabled']) ? 'checked' : ''; ?>>
                    <label for="openclaw_bridge_enabled"><strong>Enable bridge</strong></label>
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="checkbox checkbox-primary">
                    <input type="checkbox" id="openclaw_bridge_include_payload" name="openclaw_bridge_include_payload" value="1" <?php echo !empty($bridge_settings['openclaw_bridge_include_payload']) ? 'checked' : ''; ?>>
                    <label for="openclaw_bridge_include_payload"><strong>Include payload</strong></label>
                  </div>
                </div>
                <div class="col-md-6"><?php echo render_input('openclaw_bridge_endpoint', 'OpenClaw webhook endpoint', $bridge_settings['openclaw_bridge_endpoint'] ?? ''); ?></div>
                <div class="col-md-3"><?php echo render_input('openclaw_bridge_agent', 'Target agent', $bridge_settings['openclaw_bridge_agent'] ?? ''); ?></div>
                <div class="col-md-3"><?php echo render_input('openclaw_bridge_notify_agent', 'Notify agent', $bridge_settings['openclaw_bridge_notify_agent'] ?? ''); ?></div>
                <div class="col-md-3"><?php echo render_input('openclaw_bridge_timeout_ms', 'Timeout (ms)', (string) ($bridge_settings['openclaw_bridge_timeout_ms'] ?? 12000), 'number'); ?></div>
                <div class="col-md-3"><?php echo render_input('openclaw_bridge_retry_max', 'Retry max', (string) ($bridge_settings['openclaw_bridge_retry_max'] ?? 3), 'number'); ?></div>
                <div class="col-md-3"><?php echo render_input('openclaw_bridge_retry_delay_sec', 'Retry delay (sec)', (string) ($bridge_settings['openclaw_bridge_retry_delay_sec'] ?? 60), 'number'); ?></div>
                <div class="col-md-3"><?php echo render_input('openclaw_bridge_group_id', 'Queue group id (optional)', $bridge_settings['openclaw_bridge_group_id'] ?? ''); ?></div>
                <div class="col-md-6"><?php echo render_input('openclaw_bridge_auth_token', 'Bridge auth token (Bearer)', $bridge_settings['openclaw_bridge_auth_token'] ?? ''); ?></div>
                <div class="col-md-6"><?php echo render_input('openclaw_bridge_hmac_secret', 'Bridge HMAC secret (optional)', $bridge_settings['openclaw_bridge_hmac_secret'] ?? ''); ?></div>
              </div>
              <div class="row">
                <div class="col-md-3">
                  <div class="form-group">
                    <label for="openclaw_bridge_payload_policy">Payload policy</label>
                    <select class="form-control" id="openclaw_bridge_payload_policy" name="openclaw_bridge_payload_policy">
                      <?php $payloadPolicy = $bridge_settings['openclaw_bridge_payload_policy'] ?? 'full'; ?>
                      <option value="full" <?php echo $payloadPolicy === 'full' ? 'selected' : ''; ?>>full</option>
                      <option value="summary" <?php echo $payloadPolicy === 'summary' ? 'selected' : ''; ?>>summary</option>
                      <option value="off" <?php echo $payloadPolicy === 'off' ? 'selected' : ''; ?>>off</option>
                    </select>
                  </div>
                </div>
              </div>
              <div class="_buttons mtop15">
                <button type="submit" class="btn btn-primary">Save Bridge Settings</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <?php if ($showScopePage): ?>
    <div class="row">
      <div class="col-md-12">
        <div class="panel_s">
          <div class="panel-body">
            <h4 class="no-margin">Scope Settings</h4>
            <p class="text-muted mtop5">Module scopes, allow and deny patterns, and payload field mapping.</p>
            <hr class="hr-panel-heading" />
            <form method="post" action="<?php echo admin_url('openclaw_gateway/openclaw_gateway_admin/scope_settings'); ?>">
              <input type="hidden" name="ocg_settings_submit" value="1" />
              <input type="hidden" name="ocg_settings_section" value="scopes" />
              <div class="row">
                <div class="col-md-12">
                  <strong>Module scopes (toggle per domain)</strong>
                </div>
                <?php foreach (($scope_keys ?? []) as $scopeKey): ?>
                  <div class="col-md-2 mtop10">
                    <div class="checkbox checkbox-primary">
                      <?php $scopeOpt = 'openclaw_bridge_scope_' . $scopeKey; ?>
                      <input type="checkbox" id="<?php echo $scopeOpt; ?>" name="<?php echo $scopeOpt; ?>" value="1" <?php echo !empty($bridge_settings[$scopeOpt]) ? 'checked' : ''; ?>>
                      <label for="<?php echo $scopeOpt; ?>"><?php echo ucfirst($scopeKey); ?></label>
                    </div>
                  </div>
                <?php endforeach; ?>
                <div class="col-md-6">
                  <div class="form-group">
                    <label for="openclaw_bridge_event_allow_patterns">Event Allow Patterns</label>
                    <textarea class="form-control" rows="4" id="openclaw_bridge_event_allow_patterns" name="openclaw_bridge_event_allow_patterns"><?php echo html_escape($bridge_settings['openclaw_bridge_event_allow_patterns'] ?? ''); ?></textarea>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label for="openclaw_bridge_event_deny_patterns">Event Deny Patterns</label>
                    <textarea class="form-control" rows="4" id="openclaw_bridge_event_deny_patterns" name="openclaw_bridge_event_deny_patterns"><?php echo html_escape($bridge_settings['openclaw_bridge_event_deny_patterns'] ?? ''); ?></textarea>
                  </div>
                </div>
                <div class="col-md-12">
                  <div class="form-group">
                    <label for="openclaw_bridge_payload_fields_json">Payload Fields Map (JSON)</label>
                    <textarea class="form-control" rows="6" id="openclaw_bridge_payload_fields_json" name="openclaw_bridge_payload_fields_json"><?php echo html_escape($bridge_settings['openclaw_bridge_payload_fields_json'] ?? ''); ?></textarea>
                  </div>
                </div>
              </div>
              <div class="_buttons mtop15">
                <button type="submit" class="btn btn-primary"><?php echo _l('openclaw_gateway_save_scope_settings'); ?></button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <?php if ($isLogPage): ?>
    <div class="row">
      <div class="col-md-12">
        <div class="panel_s">
          <div class="panel-body">
            <h4 class="no-margin">
              <?php if ($showPipelinePage): ?>
                Pipeline Logs
              <?php elseif ($showBridgePage): ?>
                Bridge Queue
              <?php else: ?>
                Gateway Logs
              <?php endif; ?>
            </h4>
            <p class="text-muted mtop5">Use filters below to narrow results before opening row details.</p>
            <hr class="hr-panel-heading" />
            <div class="row">
              <div class="col-md-3">
                <div class="form-group">
                  <label for="ocg_filter_request_id">Search Request ID</label>
                  <input type="text" id="ocg_filter_request_id" class="form-control" value="<?php echo html_escape($filters['request_id'] ?? ''); ?>" placeholder="Search request_id or trace">
                </div>
              </div>
              <div class="col-md-2">
                <div class="form-group">
                  <label for="ocg_filter_direction">Direction</label>
                  <select id="ocg_filter_direction" class="form-control">
                    <option value="" <?php echo empty($filters['direction']) ? 'selected' : ''; ?>>All</option>
                    <option value="inbound" <?php echo (($filters['direction'] ?? '') === 'inbound') ? 'selected' : ''; ?>>inbound</option>
                    <option value="outbound" <?php echo (($filters['direction'] ?? '') === 'outbound') ? 'selected' : ''; ?>>outbound</option>
                    <option value="system" <?php echo (($filters['direction'] ?? '') === 'system') ? 'selected' : ''; ?>>system</option>
                  </select>
                </div>
              </div>
              <div class="col-md-2">
                <div class="form-group">
                  <label for="ocg_filter_status">Status</label>
                  <input type="text" id="ocg_filter_status" class="form-control" value="<?php echo html_escape($filters['status'] ?? ''); ?>" placeholder="Status">
                </div>
              </div>
              <div class="col-md-2">
                <div class="form-group">
                  <label for="ocg_filter_agent_id">Agent</label>
                  <input type="text" id="ocg_filter_agent_id" class="form-control" value="<?php echo html_escape($filters['agent_id'] ?? ''); ?>" placeholder="Agent">
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-group">
                  <label for="ocg_filter_event_name">Event / Action</label>
                  <input type="text" id="ocg_filter_event_name" class="form-control" value="<?php echo html_escape($filters['event_name'] ?? ''); ?>" placeholder="Event or action">
                </div>
              </div>
              <div class="col-md-2">
                <div class="form-group">
                  <label for="ocg_filter_from">From</label>
                  <input type="date" id="ocg_filter_from" class="form-control" value="<?php echo html_escape($filters['from'] ?? ''); ?>">
                </div>
              </div>
              <div class="col-md-2">
                <div class="form-group">
                  <label for="ocg_filter_to">To</label>
                  <input type="date" id="ocg_filter_to" class="form-control" value="<?php echo html_escape($filters['to'] ?? ''); ?>">
                </div>
              </div>
            </div>
            <div class="_buttons mtop15">
              <button class="btn btn-default" id="ocg-apply-filters" type="button"><i class="fa fa-filter"></i> Apply Filters</button>
              <button class="btn btn-default" id="ocg-reset-filters" type="button"><i class="fa fa-refresh"></i> Reset View</button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-md-12">
        <div class="panel_s">
          <div class="panel-body">
            <?php if ($showPipelinePage): ?>
              <?php render_datatable(['#', 'Time', 'Direction', 'Status', 'Agent', 'Event', 'Entity', 'Request', 'Summary', 'Details'], 'ocg-pipeline-logs'); ?>
            <?php elseif ($showBridgePage): ?>
              <?php render_datatable(['#', 'Created', 'Event', 'Module', 'Action', 'Status', 'Attempts', 'HTTP', 'Error', 'Details'], 'ocg-bridge-logs'); ?>
            <?php else: ?>
              <?php render_datatable(['#', 'Created', 'Method', 'Path', 'Status', 'HTTP', 'Action', 'Request', 'Error', 'Details'], 'ocg-gateway-logs'); ?>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>

<div class="modal fade" id="ocg-detail-modal" tabindex="-1" role="dialog" aria-labelledby="ocg-detail-title">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="ocg-detail-title">Log Detail</h4>
      </div>
      <div class="modal-body">
        <ul class="nav nav-tabs" role="tablist">
          <li role="presentation" class="active"><a href="#ocg-detail-overview" aria-controls="ocg-detail-overview" role="tab" data-toggle="tab">Overview</a></li>
          <li role="presentation"><a href="#ocg-detail-payload" aria-controls="ocg-detail-payload" role="tab" data-toggle="tab">Payload</a></li>
          <li role="presentation"><a href="#ocg-detail-meta" aria-controls="ocg-detail-meta" role="tab" data-toggle="tab">Meta</a></li>
          <li role="presentation"><a href="#ocg-detail-raw" aria-controls="ocg-detail-raw" role="tab" data-toggle="tab">Raw</a></li>
        </ul>
        <div class="tab-content mtop15">
          <div role="tabpanel" class="tab-pane active" id="ocg-detail-overview"></div>
          <div role="tabpanel" class="tab-pane" id="ocg-detail-payload"></div>
          <div role="tabpanel" class="tab-pane" id="ocg-detail-meta"></div>
          <div role="tabpanel" class="tab-pane" id="ocg-detail-raw"></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
<?php init_tail(); ?>
<script>
  $(function() {
    var serverParams = {
      direction: '#ocg_filter_direction',
      status: '#ocg_filter_status',
      agent_id: '#ocg_filter_agent_id',
      request_id: '#ocg_filter_request_id',
      event_name: '#ocg_filter_event_name',
      from: '#ocg_filter_from',
      to: '#ocg_filter_to'
    };

    var pipelineTable = $('.table-ocg-pipeline-logs').length ? initDataTable(
      '.table-ocg-pipeline-logs',
      '<?= admin_url('openclaw_gateway/openclaw_gateway_admin/pipeline_table'); ?>',
      [0, 9],
      [0, 9],
      serverParams,
      [0, 'desc']
    ) : null;
    var bridgeTable = $('.table-ocg-bridge-logs').length ? initDataTable(
      '.table-ocg-bridge-logs',
      '<?= admin_url('openclaw_gateway/openclaw_gateway_admin/bridge_table'); ?>',
      [0, 9],
      [0, 9],
      serverParams,
      [0, 'desc']
    ) : null;
    var gatewayTable = $('.table-ocg-gateway-logs').length ? initDataTable(
      '.table-ocg-gateway-logs',
      '<?= admin_url('openclaw_gateway/openclaw_gateway_admin/gateway_table'); ?>',
      [0, 9],
      [0, 9],
      serverParams,
      [0, 'desc']
    ) : null;

    function reloadAllOcgTables() {
      if (pipelineTable) pipelineTable.ajax.reload(null, false);
      if (bridgeTable) bridgeTable.ajax.reload(null, false);
      if (gatewayTable) gatewayTable.ajax.reload(null, false);
    }

    $('#ocg-apply-filters').on('click', reloadAllOcgTables);
    $('#ocg-reset-filters').on('click', function() {
      $('#ocg_filter_direction,#ocg_filter_status,#ocg_filter_agent_id,#ocg_filter_request_id,#ocg_filter_event_name,#ocg_filter_from,#ocg_filter_to').val('');
      reloadAllOcgTables();
    });

    function escapeHtml(text) {
      return String(text || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
    }

    function prettyJson(value) {
      if (value === null || typeof value === 'undefined' || value === '') {
        return '<div class="text-muted">No data</div>';
      }
      try {
        value = JSON.stringify(value, null, 2);
      } catch (e) {}
      return '<pre style="max-height:420px;overflow:auto;white-space:pre-wrap;word-break:break-word;">' + escapeHtml(value) + '</pre>';
    }

    $('body').on('click', '.ocg-open-detail', function(e) {
      e.preventDefault();
      var id = $(this).data('id');
      var type = $(this).data('type');
      $.getJSON('<?= admin_url('openclaw_gateway/openclaw_gateway_admin/log_detail'); ?>/' + encodeURIComponent(type) + '/' + encodeURIComponent(id))
        .done(function(res) {
          if (!res || !res.ok) return;
          $('#ocg-detail-title').text('Log Detail - ' + (res.type || '') + ' #' + (res.id || ''));
          $('#ocg-detail-overview').html(prettyJson(res.row || {}));
          $('#ocg-detail-payload').html(prettyJson((res.decoded || {}).payload));
          $('#ocg-detail-meta').html(prettyJson((res.decoded || {}).meta));
          $('#ocg-detail-raw').html(prettyJson(res.row || {}));
          $('#ocg-detail-modal').modal('show');
        });
    });
  });
</script>
