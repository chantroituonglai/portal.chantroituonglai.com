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
    'overview' => 'Gateway Overview',
    'bridge_settings' => 'Bridge Settings',
    'scope_settings' => 'Scope Control',
    'pipeline_logs' => 'Pipeline Logs',
    'bridge_queue' => 'Bridge Queue',
    'gateway_logs' => 'Gateway Logs',
];

$pageDescriptionMap = [
    'overview' => 'One surface for bridge health, recent alerts, queue flow, and gateway activity.',
    'bridge_settings' => 'Tune endpoint delivery, retry behavior, payload policy, and downstream notification routing.',
    'scope_settings' => 'Control which domains emit events, what patterns pass through, and how payload fields are trimmed.',
    'pipeline_logs' => 'Trace inbound and outbound traffic across the OpenClaw and Perfex pipeline.',
    'bridge_queue' => 'Inspect queued bridge deliveries, retry attempts, HTTP responses, and delivery failures.',
    'gateway_logs' => 'Inspect request routing, action IDs, request traces, status drift, and API-side failures.',
];

$pageIconMap = [
    'overview' => 'fa fa-superpowers',
    'bridge_settings' => 'fa fa-sliders',
    'scope_settings' => 'fa fa-sitemap',
    'pipeline_logs' => 'fa fa-random',
    'bridge_queue' => 'fa fa-exchange',
    'gateway_logs' => 'fa fa-terminal',
];

$pipelineWindow = $overview_stats['window']['pipeline'] ?? [];
$bridgeWindow = $overview_stats['window']['bridge'] ?? [];
$gatewayWindow = $overview_stats['window']['gateway'] ?? [];
$gatewayAvgLatency = isset($gatewayWindow['avg_latency']) && $gatewayWindow['avg_latency'] !== null ? (int) $gatewayWindow['avg_latency'] : null;
$bridgeEnabled = !empty($bridge_settings['openclaw_bridge_enabled']);
$includePayload = !empty($bridge_settings['openclaw_bridge_include_payload']);
$payloadPolicy = $bridge_settings['openclaw_bridge_payload_policy'] ?? 'full';
$navItems = [
    [
        'key' => 'overview',
        'label' => 'Overview',
        'href' => admin_url('openclaw_gateway/openclaw_gateway_admin'),
        'icon' => 'fa fa-compass',
        'meta' => ((int) ($pipelineWindow['total'] ?? 0)) . ' events',
    ],
    [
        'key' => 'bridge_settings',
        'label' => 'Bridge Settings',
        'href' => admin_url('openclaw_gateway/openclaw_gateway_admin/bridge_settings'),
        'icon' => 'fa fa-sliders',
        'meta' => $bridgeEnabled ? 'Bridge live' : 'Bridge paused',
    ],
    [
        'key' => 'scope_settings',
        'label' => 'Scopes',
        'href' => admin_url('openclaw_gateway/openclaw_gateway_admin/scope_settings'),
        'icon' => 'fa fa-sitemap',
        'meta' => $activeScopeCount . ' active',
    ],
    [
        'key' => 'pipeline_logs',
        'label' => 'Pipeline Logs',
        'href' => admin_url('openclaw_gateway/openclaw_gateway_admin/pipeline_logs'),
        'icon' => 'fa fa-random',
        'meta' => ((int) ($pipelineWindow['failed'] ?? 0)) . ' failed',
    ],
    [
        'key' => 'bridge_queue',
        'label' => 'Bridge Queue',
        'href' => admin_url('openclaw_gateway/openclaw_gateway_admin/bridge_queue'),
        'icon' => 'fa fa-exchange',
        'meta' => ((int) ($bridgeWindow['failed'] ?? 0)) . ' failed',
    ],
    [
        'key' => 'gateway_logs',
        'label' => 'Gateway Logs',
        'href' => admin_url('openclaw_gateway/openclaw_gateway_admin/gateway_logs'),
        'icon' => 'fa fa-terminal',
        'meta' => ((int) ($gatewayWindow['failed'] ?? 0)) . ' failed',
    ],
];

$activeLogLabel = $showPipelinePage ? 'Pipeline Logs' : ($showBridgePage ? 'Bridge Queue' : 'Gateway Logs');
$activeLogLead = $showPipelinePage
    ? 'Search by request trace, traffic direction, agent, and event name before drilling into payloads.'
    : ($showBridgePage
        ? 'Review delivery attempts, module actions, and retry failures before opening raw event detail.'
        : 'Search gateway requests, route actions, and API-side failures before opening full request detail.');
?>
<?php init_head(); ?>
<style>
  .ocg-shell {
    --ocg-ink: #172033;
    --ocg-muted: #6f7787;
    --ocg-line: rgba(24, 34, 51, 0.10);
    --ocg-surface: #ffffff;
    --ocg-soft: #f5f3ee;
    --ocg-tint: #eef4f1;
    --ocg-accent: #1d6b52;
    --ocg-accent-strong: #174f3d;
    --ocg-alert: #8f3b28;
    --ocg-shadow: 0 20px 40px rgba(18, 24, 38, 0.08);
    color: var(--ocg-ink);
  }

  .ocg-shell .panel_s {
    border: 1px solid var(--ocg-line);
    border-radius: 26px;
    box-shadow: var(--ocg-shadow);
    overflow: hidden;
  }

  .ocg-shell .panel_s .panel-body {
    padding: 28px 30px;
  }

  .ocg-hero {
    position: relative;
    overflow: hidden;
    background:
      radial-gradient(circle at top right, rgba(29, 107, 82, 0.18), transparent 34%),
      linear-gradient(140deg, #faf8f2 0%, #eef4f1 45%, #f8fbfd 100%);
  }

  .ocg-hero:before {
    content: "";
    position: absolute;
    inset: auto -8% -30px auto;
    width: 320px;
    height: 320px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(143, 59, 40, 0.12) 0%, rgba(143, 59, 40, 0) 70%);
    pointer-events: none;
  }

  .ocg-hero-copy {
    position: relative;
    z-index: 1;
    max-width: 760px;
  }

  .ocg-kicker {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 14px;
    padding: 7px 12px;
    border-radius: 999px;
    background: rgba(23, 32, 51, 0.06);
    color: var(--ocg-accent-strong);
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.18em;
    text-transform: uppercase;
  }

  .ocg-hero-title {
    margin: 0;
    font-size: 38px;
    line-height: 1.04;
    font-weight: 700;
    letter-spacing: -0.04em;
  }

  .ocg-hero-desc {
    max-width: 690px;
    margin: 14px 0 0;
    color: var(--ocg-muted);
    font-size: 17px;
    line-height: 1.7;
  }

  .ocg-stat-band {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 14px;
    margin-top: 28px;
  }

  .ocg-stat-tile {
    min-height: 132px;
    padding: 18px 18px 16px;
    border: 1px solid rgba(23, 32, 51, 0.08);
    border-radius: 22px;
    background: rgba(255, 255, 255, 0.72);
    backdrop-filter: blur(8px);
  }

  .ocg-stat-label {
    color: var(--ocg-muted);
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.16em;
    text-transform: uppercase;
  }

  .ocg-stat-value {
    margin-top: 16px;
    font-size: 32px;
    line-height: 1;
    font-weight: 700;
    letter-spacing: -0.05em;
  }

  .ocg-stat-copy {
    margin-top: 10px;
    color: var(--ocg-muted);
    font-size: 13px;
    line-height: 1.6;
  }

  .ocg-nav-shell {
    background: linear-gradient(180deg, #fff 0%, #fbfcfd 100%);
  }

  .ocg-nav-grid {
    display: grid;
    grid-template-columns: repeat(6, minmax(0, 1fr));
    gap: 12px;
  }

  .ocg-tab {
    display: block;
    padding: 16px 18px;
    border: 1px solid var(--ocg-line);
    border-radius: 20px;
    text-decoration: none !important;
    background: var(--ocg-surface);
    transition: transform 0.18s ease, border-color 0.18s ease, box-shadow 0.18s ease, background 0.18s ease;
  }

  .ocg-tab:hover,
  .ocg-tab:focus {
    transform: translateY(-1px);
    border-color: rgba(29, 107, 82, 0.3);
    box-shadow: 0 16px 24px rgba(18, 24, 38, 0.06);
  }

  .ocg-tab.is-active {
    border-color: rgba(29, 107, 82, 0.35);
    background: linear-gradient(180deg, #f4faf7 0%, #eef6f2 100%);
  }

  .ocg-tab-head {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 10px;
    color: var(--ocg-ink);
    font-size: 15px;
    font-weight: 700;
  }

  .ocg-tab-meta {
    color: var(--ocg-muted);
    font-size: 12px;
    line-height: 1.5;
  }

  .ocg-surface {
    background: linear-gradient(180deg, #fff 0%, #fcfcfb 100%);
  }

  .ocg-section-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 18px;
    margin-bottom: 24px;
  }

  .ocg-section-title {
    margin: 0;
    font-size: 28px;
    line-height: 1.05;
    letter-spacing: -0.03em;
  }

  .ocg-section-copy {
    margin: 10px 0 0;
    color: var(--ocg-muted);
    font-size: 15px;
    line-height: 1.7;
  }

  .ocg-chip {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 9px 12px;
    border-radius: 999px;
    background: var(--ocg-soft);
    color: var(--ocg-accent-strong);
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.03em;
  }

  .ocg-overview-grid,
  .ocg-settings-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.4fr) minmax(320px, 0.8fr);
    gap: 24px;
  }

  .ocg-card-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 14px;
  }

  .ocg-mini-card {
    padding: 18px;
    border: 1px solid var(--ocg-line);
    border-radius: 20px;
    background: var(--ocg-surface);
  }

  .ocg-mini-card h5 {
    margin: 0 0 12px;
    color: var(--ocg-muted);
    font-size: 11px;
    letter-spacing: 0.18em;
    text-transform: uppercase;
    font-weight: 700;
  }

  .ocg-mini-card strong {
    display: block;
    font-size: 32px;
    line-height: 1;
    letter-spacing: -0.05em;
  }

  .ocg-mini-card p {
    margin: 10px 0 0;
    color: var(--ocg-muted);
    line-height: 1.7;
  }

  .ocg-timeline {
    margin: 0;
    padding: 0;
    list-style: none;
  }

  .ocg-timeline li {
    position: relative;
    padding: 0 0 18px 18px;
    border-left: 1px solid var(--ocg-line);
  }

  .ocg-timeline li:last-child {
    padding-bottom: 0;
  }

  .ocg-timeline li:before {
    content: "";
    position: absolute;
    left: -5px;
    top: 6px;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: var(--ocg-accent);
    box-shadow: 0 0 0 4px rgba(29, 107, 82, 0.12);
  }

  .ocg-alert-title {
    margin: 0;
    font-size: 15px;
    font-weight: 700;
  }

  .ocg-alert-copy,
  .ocg-alert-meta {
    margin-top: 6px;
    color: var(--ocg-muted);
    line-height: 1.6;
  }

  .ocg-stream-list {
    display: grid;
    gap: 12px;
  }

  .ocg-stream-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 18px;
    padding: 15px 16px;
    border-radius: 18px;
    background: linear-gradient(180deg, #fafafa 0%, #f4f6f8 100%);
    border: 1px solid var(--ocg-line);
  }

  .ocg-stream-item strong {
    display: block;
    font-size: 15px;
  }

  .ocg-stream-item span {
    display: block;
    margin-top: 4px;
    color: var(--ocg-muted);
    font-size: 12px;
  }

  .ocg-stream-total {
    font-size: 24px;
    font-weight: 700;
    letter-spacing: -0.05em;
  }

  .ocg-notice {
    padding: 18px 18px 16px;
    border-radius: 22px;
    background: linear-gradient(180deg, #fff8f4 0%, #fff 100%);
    border: 1px solid rgba(143, 59, 40, 0.12);
  }

  .ocg-notice strong {
    display: block;
    color: var(--ocg-alert);
    font-size: 13px;
    letter-spacing: 0.12em;
    text-transform: uppercase;
  }

  .ocg-notice p {
    margin: 12px 0 0;
    color: var(--ocg-muted);
    line-height: 1.7;
  }

  .ocg-form-shell {
    display: grid;
    gap: 18px;
  }

  .ocg-form-block {
    padding: 20px;
    border: 1px solid var(--ocg-line);
    border-radius: 22px;
    background: linear-gradient(180deg, #ffffff 0%, #fafcfd 100%);
  }

  .ocg-form-block h5 {
    margin: 0;
    font-size: 17px;
    font-weight: 700;
  }

  .ocg-form-block p {
    margin: 8px 0 18px;
    color: var(--ocg-muted);
    line-height: 1.7;
  }

  .ocg-scope-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 12px;
    margin-bottom: 22px;
  }

  .ocg-scope-tile {
    min-height: 84px;
    padding: 14px 16px;
    border: 1px solid var(--ocg-line);
    border-radius: 18px;
    background: var(--ocg-surface);
  }

  .ocg-scope-tile .checkbox {
    margin: 0;
  }

  .ocg-scope-caption {
    margin-top: 8px;
    color: var(--ocg-muted);
    font-size: 12px;
    line-height: 1.5;
  }

  .ocg-log-shell {
    background:
      linear-gradient(180deg, rgba(238, 244, 241, 0.9) 0%, rgba(255, 255, 255, 0) 58%),
      linear-gradient(180deg, #ffffff 0%, #fbfbfa 100%);
  }

  .ocg-filter-grid {
    display: grid;
    grid-template-columns: 1.3fr repeat(4, minmax(0, 1fr));
    gap: 14px;
  }

  .ocg-filter-grid .ocg-filter-date {
    grid-column: span 1;
  }

  .ocg-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    margin-top: 18px;
    flex-wrap: wrap;
  }

  .ocg-toolbar-meta {
    color: var(--ocg-muted);
    font-size: 13px;
    line-height: 1.6;
  }

  .ocg-toolbar .btn {
    min-width: 168px;
    border-radius: 14px;
    font-weight: 700;
    box-shadow: none;
  }

  .ocg-toolbar .btn-default {
    border-color: rgba(23, 32, 51, 0.14);
  }

  .ocg-log-table-wrap .panel-body {
    padding-top: 18px;
  }

  .ocg-log-table-wrap .table.dataTable {
    margin-top: 0 !important;
  }

  .ocg-log-table-wrap .dataTables_wrapper .dataTables_filter,
  .ocg-log-table-wrap .dataTables_wrapper .dataTables_length {
    margin-bottom: 14px;
  }

  .ocg-log-table-wrap table.dataTable thead th {
    border-bottom: 1px solid var(--ocg-line) !important;
    color: var(--ocg-muted);
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.16em;
    text-transform: uppercase;
  }

  .ocg-log-table-wrap table.dataTable tbody td {
    padding-top: 14px !important;
    padding-bottom: 14px !important;
    border-top: 1px solid rgba(23, 32, 51, 0.06) !important;
    vertical-align: middle;
  }

  .ocg-row-strong {
    color: var(--ocg-ink);
    font-weight: 700;
    letter-spacing: -0.01em;
  }

  .ocg-row-muted {
    color: var(--ocg-muted);
    font-size: 12px;
    line-height: 1.5;
  }

  .ocg-pill {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 28px;
    padding: 4px 11px;
    border-radius: 999px;
    border: 1px solid transparent;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    white-space: nowrap;
  }

  .ocg-pill-status-success,
  .ocg-pill-status-ok,
  .ocg-pill-status-completed {
    background: rgba(29, 107, 82, 0.10);
    color: var(--ocg-accent-strong);
    border-color: rgba(29, 107, 82, 0.16);
  }

  .ocg-pill-status-failed,
  .ocg-pill-status-error,
  .ocg-pill-status-upstream_error,
  .ocg-pill-status-validation_error {
    background: rgba(143, 59, 40, 0.10);
    color: var(--ocg-alert);
    border-color: rgba(143, 59, 40, 0.18);
  }

  .ocg-pill-status-pending,
  .ocg-pill-status-queued,
  .ocg-pill-status-processing,
  .ocg-pill-status-retry {
    background: rgba(190, 145, 44, 0.12);
    color: #7f5c13;
    border-color: rgba(190, 145, 44, 0.18);
  }

  .ocg-pill-http {
    background: rgba(23, 32, 51, 0.06);
    color: var(--ocg-ink);
    border-color: rgba(23, 32, 51, 0.10);
  }

  .ocg-pill-http-2 {
    background: rgba(29, 107, 82, 0.10);
    color: var(--ocg-accent-strong);
    border-color: rgba(29, 107, 82, 0.16);
  }

  .ocg-pill-http-4,
  .ocg-pill-http-5 {
    background: rgba(143, 59, 40, 0.10);
    color: var(--ocg-alert);
    border-color: rgba(143, 59, 40, 0.18);
  }

  .ocg-pill-direction-inbound {
    background: rgba(54, 95, 166, 0.10);
    color: #294d8f;
    border-color: rgba(54, 95, 166, 0.18);
  }

  .ocg-pill-direction-outbound {
    background: rgba(29, 107, 82, 0.10);
    color: var(--ocg-accent-strong);
    border-color: rgba(29, 107, 82, 0.16);
  }

  .ocg-pill-direction-system {
    background: rgba(23, 32, 51, 0.06);
    color: var(--ocg-ink);
    border-color: rgba(23, 32, 51, 0.10);
  }

  .ocg-detail-trigger {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 34px;
    height: 34px;
    border-radius: 50%;
    border: 1px solid var(--ocg-line);
    color: var(--ocg-accent-strong);
    background: linear-gradient(180deg, #fff 0%, #f4f7f6 100%);
  }

  .ocg-shell .form-control {
    height: 48px;
    border-radius: 14px;
    border-color: rgba(23, 32, 51, 0.12);
    box-shadow: none;
    font-size: 15px;
  }

  .ocg-shell textarea.form-control {
    height: auto;
    min-height: 140px;
    resize: vertical;
  }

  .ocg-shell label {
    color: var(--ocg-ink);
    font-size: 13px;
    font-weight: 700;
    letter-spacing: 0.01em;
  }

  .ocg-shell .checkbox label {
    font-weight: 700;
  }

  .ocg-shell .checkbox-primary input[type=checkbox]:checked + label:before {
    background-color: var(--ocg-accent);
    border-color: var(--ocg-accent);
  }

  #ocg-detail-modal .modal-content {
    border-radius: 24px;
    overflow: hidden;
  }

  #ocg-detail-modal .modal-header {
    padding: 22px 24px 14px;
    border-bottom: 1px solid var(--ocg-line);
    background: linear-gradient(180deg, #f8faf9 0%, #fff 100%);
  }

  #ocg-detail-modal .modal-body {
    padding: 20px 24px;
  }

  #ocg-detail-modal .nav-tabs {
    border-bottom: 1px solid var(--ocg-line);
  }

  .ocg-detail-summary {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 12px;
    margin-bottom: 18px;
  }

  .ocg-detail-chip {
    min-height: 82px;
    padding: 14px 15px;
    border: 1px solid var(--ocg-line);
    border-radius: 18px;
    background: linear-gradient(180deg, #fff 0%, #f8faf9 100%);
  }

  .ocg-detail-chip-label {
    color: var(--ocg-muted);
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.16em;
    text-transform: uppercase;
  }

  .ocg-detail-chip-value {
    margin-top: 10px;
    color: var(--ocg-ink);
    font-size: 18px;
    font-weight: 700;
    line-height: 1.35;
    word-break: break-word;
  }

  .ocg-detail-overview-grid {
    display: grid;
    grid-template-columns: 1.2fr 1fr;
    gap: 16px;
  }

  .ocg-detail-card {
    padding: 16px;
    border: 1px solid var(--ocg-line);
    border-radius: 18px;
    background: linear-gradient(180deg, #fff 0%, #fbfbfb 100%);
  }

  .ocg-detail-card h5 {
    margin: 0 0 14px;
    color: var(--ocg-muted);
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.16em;
    text-transform: uppercase;
  }

  .ocg-kv {
    display: grid;
    grid-template-columns: 120px 1fr;
    gap: 8px 12px;
  }

  .ocg-kv dt {
    color: var(--ocg-muted);
    font-weight: 700;
  }

  .ocg-kv dd {
    margin: 0;
    color: var(--ocg-ink);
    word-break: break-word;
  }

  #ocg-detail-modal .nav-tabs > li > a {
    border: 0;
    border-bottom: 2px solid transparent;
    color: var(--ocg-muted);
    font-weight: 700;
  }

  #ocg-detail-modal .nav-tabs > li.active > a,
  #ocg-detail-modal .nav-tabs > li.active > a:focus,
  #ocg-detail-modal .nav-tabs > li.active > a:hover {
    color: var(--ocg-accent-strong);
    border: 0;
    border-bottom: 2px solid var(--ocg-accent);
    background: transparent;
  }

  #ocg-detail-modal pre {
    border-radius: 16px;
    border: 1px solid var(--ocg-line);
    background: #0f1724;
    color: #f3f6fb;
    padding: 18px;
  }

  @media (max-width: 1199px) {
    .ocg-nav-grid,
    .ocg-stat-band,
    .ocg-card-grid,
    .ocg-filter-grid,
    .ocg-scope-grid,
    .ocg-overview-grid,
    .ocg-settings-grid,
    .ocg-detail-summary,
    .ocg-detail-overview-grid {
      grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .ocg-filter-grid > div:first-child {
      grid-column: span 2;
    }
  }

  @media (max-width: 767px) {
    .ocg-shell .panel_s .panel-body {
      padding: 22px 18px;
    }

    .ocg-hero-title {
      font-size: 30px;
    }

    .ocg-nav-grid,
    .ocg-stat-band,
    .ocg-card-grid,
    .ocg-filter-grid,
    .ocg-scope-grid,
    .ocg-overview-grid,
    .ocg-settings-grid,
    .ocg-detail-summary,
    .ocg-detail-overview-grid {
      grid-template-columns: minmax(0, 1fr);
    }

    .ocg-filter-grid > div:first-child {
      grid-column: span 1;
    }

    .ocg-section-head,
    .ocg-toolbar {
      flex-direction: column;
      align-items: flex-start;
    }

    .ocg-toolbar .btn {
      width: 100%;
      min-width: 0;
    }
  }
</style>
<div id="wrapper">
  <div class="content ocg-shell">
    <div class="panel_s ocg-hero">
      <div class="panel-body">
        <div class="ocg-hero-copy">
          <div class="ocg-kicker">
            <i class="<?php echo html_escape($pageIconMap[$pageMode] ?? 'fa fa-superpowers'); ?>"></i>
            <span>OpenClaw Control Surface</span>
          </div>
          <h1 class="ocg-hero-title"><?php echo html_escape($pageTitleMap[$pageMode] ?? 'OpenClaw Gateway'); ?></h1>
          <p class="ocg-hero-desc"><?php echo html_escape($pageDescriptionMap[$pageMode] ?? ''); ?></p>
        </div>

        <div class="ocg-stat-band">
          <div class="ocg-stat-tile">
            <div class="ocg-stat-label">24h Pipeline</div>
            <div class="ocg-stat-value"><?php echo (int) ($pipelineWindow['total'] ?? 0); ?></div>
            <div class="ocg-stat-copy">Inbound <?php echo (int) ($pipelineWindow['inbound'] ?? 0); ?>, outbound <?php echo (int) ($pipelineWindow['outbound'] ?? 0); ?>, failed <?php echo (int) ($pipelineWindow['failed'] ?? 0); ?>.</div>
          </div>
          <div class="ocg-stat-tile">
            <div class="ocg-stat-label">Bridge Queue</div>
            <div class="ocg-stat-value"><?php echo (int) ($bridgeWindow['total'] ?? 0); ?></div>
            <div class="ocg-stat-copy"><?php echo $bridgeEnabled ? 'Bridge delivery is enabled.' : 'Bridge delivery is currently paused.'; ?> Failed items: <?php echo (int) ($bridgeWindow['failed'] ?? 0); ?>.</div>
          </div>
          <div class="ocg-stat-tile">
            <div class="ocg-stat-label">Gateway</div>
            <div class="ocg-stat-value"><?php echo (int) ($gatewayWindow['total'] ?? 0); ?></div>
            <div class="ocg-stat-copy">Failed requests <?php echo (int) ($gatewayWindow['failed'] ?? 0); ?><?php if ($gatewayAvgLatency !== null): ?>, average latency <?php echo $gatewayAvgLatency; ?> ms<?php endif; ?>.</div>
          </div>
          <div class="ocg-stat-tile">
            <div class="ocg-stat-label">Active Scope</div>
            <div class="ocg-stat-value"><?php echo $activeScopeCount; ?>/<?php echo count($scope_keys ?? []); ?></div>
            <div class="ocg-stat-copy">Payload policy <?php echo html_escape($payloadPolicy); ?>. Payload body <?php echo $includePayload ? 'included' : 'trimmed'; ?> in deliveries.</div>
          </div>
        </div>
      </div>
    </div>

    <div class="panel_s ocg-nav-shell">
      <div class="panel-body">
        <div class="ocg-nav-grid">
          <?php foreach ($navItems as $navItem): ?>
            <a class="ocg-tab <?php echo $pageMode === $navItem['key'] ? 'is-active' : ''; ?>" href="<?php echo $navItem['href']; ?>">
              <div class="ocg-tab-head">
                <i class="<?php echo html_escape($navItem['icon']); ?>"></i>
                <span><?php echo html_escape($navItem['label']); ?></span>
              </div>
              <div class="ocg-tab-meta"><?php echo html_escape($navItem['meta']); ?></div>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <?php if ($isOverviewPage): ?>
      <div class="ocg-overview-grid">
        <div class="panel_s ocg-surface">
          <div class="panel-body">
            <div class="ocg-section-head">
              <div>
                <h3 class="ocg-section-title">System Snapshot</h3>
                <p class="ocg-section-copy">This view compresses queue pressure, gateway traffic, and recent operator-visible issues into one glanceable workspace.</p>
              </div>
              <div class="ocg-chip"><i class="fa fa-bolt"></i> Live in last 24h</div>
            </div>

            <div class="ocg-card-grid">
              <div class="ocg-mini-card">
                <h5>Pipeline Mix</h5>
                <strong><?php echo (int) ($pipelineWindow['inbound'] ?? 0); ?>/<?php echo (int) ($pipelineWindow['outbound'] ?? 0); ?></strong>
                <p>Inbound versus outbound flow helps expose whether the backlog is upstream or delivery-side.</p>
              </div>
              <div class="ocg-mini-card">
                <h5>Bridge Failures</h5>
                <strong><?php echo (int) ($bridgeWindow['failed'] ?? 0); ?></strong>
                <p>Use bridge queue when failures rise or when endpoint retries start clustering around a module.</p>
              </div>
              <div class="ocg-mini-card">
                <h5>Gateway Failures</h5>
                <strong><?php echo (int) ($gatewayWindow['failed'] ?? 0); ?></strong>
                <p>Gateway log failures usually mean auth drift, route mismatch, or upstream API behavior changes.</p>
              </div>
            </div>
          </div>
        </div>

        <div class="panel_s ocg-surface">
          <div class="panel-body">
            <div class="ocg-section-head">
              <div>
                <h3 class="ocg-section-title">Bridge Posture</h3>
                <p class="ocg-section-copy">Current policy and activation state for event delivery.</p>
              </div>
              <div class="ocg-chip"><i class="fa fa-shield"></i> Policy</div>
            </div>
            <div class="ocg-stream-list">
              <div class="ocg-stream-item">
                <div>
                  <strong>Bridge Enabled</strong>
                  <span><?php echo $bridgeEnabled ? 'Outbound delivery is active.' : 'Outbound delivery is disabled.'; ?></span>
                </div>
                <div class="ocg-stream-total"><?php echo $bridgeEnabled ? 'On' : 'Off'; ?></div>
              </div>
              <div class="ocg-stream-item">
                <div>
                  <strong>Payload Strategy</strong>
                  <span>Controls how much event body is shipped downstream.</span>
                </div>
                <div class="ocg-stream-total"><?php echo html_escape($payloadPolicy); ?></div>
              </div>
              <div class="ocg-stream-item">
                <div>
                  <strong>Scope Coverage</strong>
                  <span><?php echo $activeScopeCount; ?> domains currently allowed to emit events.</span>
                </div>
                <div class="ocg-stream-total"><?php echo (int) $activeScopeCount; ?></div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="ocg-overview-grid mtop20">
        <div class="panel_s ocg-surface">
          <div class="panel-body">
            <div class="ocg-section-head">
              <div>
                <h3 class="ocg-section-title">Recent Alerts</h3>
                <p class="ocg-section-copy">Newest notable events across pipeline, queue, and gateway logs.</p>
              </div>
            </div>
            <?php if (!empty($recent_alerts)): ?>
              <ul class="ocg-timeline">
                <?php foreach ($recent_alerts as $alert): ?>
                  <li>
                    <p class="ocg-alert-title"><?php echo html_escape($alert['title'] ?? 'Activity'); ?></p>
                    <div class="ocg-alert-copy"><?php echo html_escape($alert['message'] ?? ''); ?></div>
                    <div class="ocg-alert-meta"><?php echo html_escape(($alert['stream'] ?? '') . ' • ' . ($alert['created_at'] ?? '')); ?></div>
                  </li>
                <?php endforeach; ?>
              </ul>
            <?php else: ?>
              <div class="ocg-notice">
                <strong>No recent alerts</strong>
                <p>The system has not recorded any recent notable activity in the current alert window.</p>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <div class="panel_s ocg-surface">
          <div class="panel-body">
            <div class="ocg-section-head">
              <div>
                <h3 class="ocg-section-title">Stream Summary</h3>
                <p class="ocg-section-copy">Latest record count and freshness per stream.</p>
              </div>
            </div>
            <div class="ocg-stream-list">
              <?php foreach (($overview_stats['streams'] ?? []) as $stream): ?>
                <div class="ocg-stream-item">
                  <div>
                    <strong><?php echo html_escape($stream['label'] ?? 'Stream'); ?></strong>
                    <span>Latest activity <?php echo html_escape($stream['latest_at'] ?? 'N/A'); ?></span>
                  </div>
                  <div class="ocg-stream-total"><?php echo (int) ($stream['total'] ?? 0); ?></div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <?php if ($showSettingsPage): ?>
      <div class="ocg-settings-grid">
        <div class="panel_s ocg-surface">
          <div class="panel-body">
            <div class="ocg-section-head">
              <div>
                <h3 class="ocg-section-title">Bridge Delivery Control</h3>
                <p class="ocg-section-copy">Tune the downstream webhook, routing identity, retry timing, and payload behavior without leaving the gateway surface.</p>
              </div>
              <div class="ocg-chip"><i class="fa fa-paper-plane"></i> Delivery policy</div>
            </div>

            <form method="post" action="<?php echo admin_url('openclaw_gateway/openclaw_gateway_admin/bridge_settings'); ?>" class="ocg-form-shell">
              <input type="hidden" name="ocg_settings_submit" value="1" />
              <input type="hidden" name="ocg_settings_section" value="bridge" />

              <div class="ocg-form-block">
                <h5>Runtime switches</h5>
                <p>Turn bridge delivery on or off, and decide whether the full payload body should be sent downstream.</p>
                <div class="row">
                  <div class="col-md-4">
                    <div class="checkbox checkbox-primary">
                      <input type="checkbox" id="openclaw_bridge_enabled" name="openclaw_bridge_enabled" value="1" <?php echo !empty($bridge_settings['openclaw_bridge_enabled']) ? 'checked' : ''; ?>>
                      <label for="openclaw_bridge_enabled">Enable bridge delivery</label>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="checkbox checkbox-primary">
                      <input type="checkbox" id="openclaw_bridge_include_payload" name="openclaw_bridge_include_payload" value="1" <?php echo !empty($bridge_settings['openclaw_bridge_include_payload']) ? 'checked' : ''; ?>>
                      <label for="openclaw_bridge_include_payload">Include payload body</label>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="form-group">
                      <label for="openclaw_bridge_payload_policy">Payload policy</label>
                      <select class="form-control" id="openclaw_bridge_payload_policy" name="openclaw_bridge_payload_policy">
                        <option value="full" <?php echo $payloadPolicy === 'full' ? 'selected' : ''; ?>>full</option>
                        <option value="summary" <?php echo $payloadPolicy === 'summary' ? 'selected' : ''; ?>>summary</option>
                        <option value="off" <?php echo $payloadPolicy === 'off' ? 'selected' : ''; ?>>off</option>
                      </select>
                    </div>
                  </div>
                </div>
              </div>

              <div class="ocg-form-block">
                <h5>Destination and agent routing</h5>
                <p>Keep the webhook destination and the OpenClaw agent identifiers aligned so retries land on the right worker path.</p>
                <div class="row">
                  <div class="col-md-12"><?php echo render_input('openclaw_bridge_endpoint', 'OpenClaw webhook endpoint', $bridge_settings['openclaw_bridge_endpoint'] ?? ''); ?></div>
                  <div class="col-md-4"><?php echo render_input('openclaw_bridge_agent', 'Target agent', $bridge_settings['openclaw_bridge_agent'] ?? ''); ?></div>
                  <div class="col-md-4"><?php echo render_input('openclaw_bridge_notify_agent', 'Notify agent', $bridge_settings['openclaw_bridge_notify_agent'] ?? ''); ?></div>
                  <div class="col-md-4"><?php echo render_input('openclaw_bridge_group_id', 'Queue group id (optional)', $bridge_settings['openclaw_bridge_group_id'] ?? ''); ?></div>
                </div>
              </div>

              <div class="ocg-form-block">
                <h5>Retry and authentication</h5>
                <p>Use conservative timeouts and clear auth secrets to avoid duplicate queue pressure or downstream authorization churn.</p>
                <div class="row">
                  <div class="col-md-4"><?php echo render_input('openclaw_bridge_timeout_ms', 'Timeout (ms)', (string) ($bridge_settings['openclaw_bridge_timeout_ms'] ?? 12000), 'number'); ?></div>
                  <div class="col-md-4"><?php echo render_input('openclaw_bridge_retry_max', 'Retry max', (string) ($bridge_settings['openclaw_bridge_retry_max'] ?? 3), 'number'); ?></div>
                  <div class="col-md-4"><?php echo render_input('openclaw_bridge_retry_delay_sec', 'Retry delay (sec)', (string) ($bridge_settings['openclaw_bridge_retry_delay_sec'] ?? 60), 'number'); ?></div>
                  <div class="col-md-6"><?php echo render_input('openclaw_bridge_auth_token', 'Bridge auth token (Bearer)', $bridge_settings['openclaw_bridge_auth_token'] ?? ''); ?></div>
                  <div class="col-md-6"><?php echo render_input('openclaw_bridge_hmac_secret', 'Bridge HMAC secret (optional)', $bridge_settings['openclaw_bridge_hmac_secret'] ?? ''); ?></div>
                </div>
              </div>

              <div class="_buttons">
                <button type="submit" class="btn btn-primary">Save Bridge Settings</button>
              </div>
            </form>
          </div>
        </div>

        <div class="panel_s ocg-surface">
          <div class="panel-body">
            <div class="ocg-section-head">
              <div>
                <h3 class="ocg-section-title">Operator Notes</h3>
                <p class="ocg-section-copy">Reference points that matter before changing delivery behavior.</p>
              </div>
            </div>

            <div class="ocg-stream-list">
              <div class="ocg-stream-item">
                <div>
                  <strong>Bridge state</strong>
                  <span><?php echo $bridgeEnabled ? 'OpenClaw delivery is active for new events.' : 'Events will stay local until delivery is re-enabled.'; ?></span>
                </div>
                <div class="ocg-stream-total"><?php echo $bridgeEnabled ? 'Live' : 'Hold'; ?></div>
              </div>
              <div class="ocg-stream-item">
                <div>
                  <strong>Payload body</strong>
                  <span><?php echo $includePayload ? 'Request payloads are included in outbound bridge deliveries.' : 'Request payloads are omitted from outbound bridge deliveries.'; ?></span>
                </div>
                <div class="ocg-stream-total"><?php echo $includePayload ? 'On' : 'Off'; ?></div>
              </div>
            </div>

            <div class="ocg-notice mtop20">
              <strong>Routing discipline</strong>
              <p>Changing timeout, retry, or endpoint values can shift queue behavior immediately. Verify the downstream receiver before turning retries up.</p>
            </div>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <?php if ($showScopePage): ?>
      <div class="ocg-settings-grid">
        <div class="panel_s ocg-surface">
          <div class="panel-body">
            <div class="ocg-section-head">
              <div>
                <h3 class="ocg-section-title">Scope Control Matrix</h3>
                <p class="ocg-section-copy">Decide which domains emit bridge events and shape the payload before it leaves Perfex.</p>
              </div>
              <div class="ocg-chip"><i class="fa fa-filter"></i> <?php echo $activeScopeCount; ?> active scopes</div>
            </div>

            <form method="post" action="<?php echo admin_url('openclaw_gateway/openclaw_gateway_admin/scope_settings'); ?>" class="ocg-form-shell">
              <input type="hidden" name="ocg_settings_submit" value="1" />
              <input type="hidden" name="ocg_settings_section" value="scopes" />

              <div class="ocg-form-block">
                <h5>Module scopes</h5>
                <p>Use these toggles as the first safety boundary for what becomes visible to the bridge.</p>
                <div class="ocg-scope-grid">
                  <?php foreach (($scope_keys ?? []) as $scopeKey): ?>
                    <?php $scopeOpt = 'openclaw_bridge_scope_' . $scopeKey; ?>
                    <div class="ocg-scope-tile">
                      <div class="checkbox checkbox-primary">
                        <input type="checkbox" id="<?php echo $scopeOpt; ?>" name="<?php echo $scopeOpt; ?>" value="1" <?php echo !empty($bridge_settings[$scopeOpt]) ? 'checked' : ''; ?>>
                        <label for="<?php echo $scopeOpt; ?>"><?php echo ucfirst($scopeKey); ?></label>
                      </div>
                      <div class="ocg-scope-caption">Allow events from the <?php echo strtolower($scopeKey); ?> domain to leave the local queue.</div>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>

              <div class="ocg-form-block">
                <h5>Pattern gating</h5>
                <p>Use allow patterns to narrow the stream and deny patterns to suppress noisy or risky event classes.</p>
                <div class="row">
                  <div class="col-md-6">
                    <div class="form-group">
                      <label for="openclaw_bridge_event_allow_patterns">Event allow patterns</label>
                      <textarea class="form-control" rows="6" id="openclaw_bridge_event_allow_patterns" name="openclaw_bridge_event_allow_patterns"><?php echo html_escape($bridge_settings['openclaw_bridge_event_allow_patterns'] ?? ''); ?></textarea>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label for="openclaw_bridge_event_deny_patterns">Event deny patterns</label>
                      <textarea class="form-control" rows="6" id="openclaw_bridge_event_deny_patterns" name="openclaw_bridge_event_deny_patterns"><?php echo html_escape($bridge_settings['openclaw_bridge_event_deny_patterns'] ?? ''); ?></textarea>
                    </div>
                  </div>
                </div>
              </div>

              <div class="ocg-form-block">
                <h5>Payload field map</h5>
                <p>Provide a JSON map when only a reduced subset of record fields should move downstream.</p>
                <div class="form-group">
                  <label for="openclaw_bridge_payload_fields_json">Payload fields map (JSON)</label>
                  <textarea class="form-control" rows="8" id="openclaw_bridge_payload_fields_json" name="openclaw_bridge_payload_fields_json"><?php echo html_escape($bridge_settings['openclaw_bridge_payload_fields_json'] ?? ''); ?></textarea>
                </div>
              </div>

              <div class="_buttons">
                <button type="submit" class="btn btn-primary"><?php echo _l('openclaw_gateway_save_scope_settings'); ?></button>
              </div>
            </form>
          </div>
        </div>

        <div class="panel_s ocg-surface">
          <div class="panel-body">
            <div class="ocg-section-head">
              <div>
                <h3 class="ocg-section-title">Scope Guidance</h3>
                <p class="ocg-section-copy">Keep the bridge narrow when debugging or when downstream systems are unstable.</p>
              </div>
            </div>

            <div class="ocg-notice">
              <strong>Recommended approach</strong>
              <p>Start by enabling only the domains you actively consume in OpenClaw. Then tighten event patterns before enabling full payload delivery.</p>
            </div>

            <div class="ocg-stream-list mtop20">
              <div class="ocg-stream-item">
                <div>
                  <strong>Active scopes</strong>
                  <span><?php echo $activeScopeCount; ?> of <?php echo count($scope_keys ?? []); ?> domains are currently enabled.</span>
                </div>
                <div class="ocg-stream-total"><?php echo $activeScopeCount; ?></div>
              </div>
              <div class="ocg-stream-item">
                <div>
                  <strong>Payload policy</strong>
                  <span><?php echo html_escape($payloadPolicy); ?> controls how much shape survives downstream after scope filtering.</span>
                </div>
                <div class="ocg-stream-total"><?php echo html_escape($payloadPolicy); ?></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <?php if ($isLogPage): ?>
      <div class="panel_s ocg-log-shell">
        <div class="panel-body">
          <div class="ocg-section-head">
            <div>
              <h3 class="ocg-section-title"><?php echo $activeLogLabel; ?></h3>
              <p class="ocg-section-copy"><?php echo html_escape($activeLogLead); ?></p>
            </div>
            <div class="ocg-chip"><i class="fa fa-search"></i> Precision filtering</div>
          </div>

          <div class="ocg-filter-grid">
            <div class="form-group">
              <label for="ocg_filter_request_id">Search request ID</label>
              <input type="text" id="ocg_filter_request_id" class="form-control" value="<?php echo html_escape($filters['request_id'] ?? ''); ?>" placeholder="Search request_id or trace">
            </div>
            <div class="form-group">
              <label for="ocg_filter_direction">Direction</label>
              <select id="ocg_filter_direction" class="form-control">
                <option value="" <?php echo empty($filters['direction']) ? 'selected' : ''; ?>>All</option>
                <option value="inbound" <?php echo (($filters['direction'] ?? '') === 'inbound') ? 'selected' : ''; ?>>inbound</option>
                <option value="outbound" <?php echo (($filters['direction'] ?? '') === 'outbound') ? 'selected' : ''; ?>>outbound</option>
                <option value="system" <?php echo (($filters['direction'] ?? '') === 'system') ? 'selected' : ''; ?>>system</option>
              </select>
            </div>
            <div class="form-group">
              <label for="ocg_filter_status">Status</label>
              <input type="text" id="ocg_filter_status" class="form-control" value="<?php echo html_escape($filters['status'] ?? ''); ?>" placeholder="Status">
            </div>
            <div class="form-group">
              <label for="ocg_filter_agent_id">Agent</label>
              <input type="text" id="ocg_filter_agent_id" class="form-control" value="<?php echo html_escape($filters['agent_id'] ?? ''); ?>" placeholder="Agent">
            </div>
            <div class="form-group">
              <label for="ocg_filter_event_name">Event / Action</label>
              <input type="text" id="ocg_filter_event_name" class="form-control" value="<?php echo html_escape($filters['event_name'] ?? ''); ?>" placeholder="Event or action">
            </div>
            <div class="form-group ocg-filter-date">
              <label for="ocg_filter_from">From</label>
              <input type="date" id="ocg_filter_from" class="form-control" value="<?php echo html_escape($filters['from'] ?? ''); ?>">
            </div>
            <div class="form-group ocg-filter-date">
              <label for="ocg_filter_to">To</label>
              <input type="date" id="ocg_filter_to" class="form-control" value="<?php echo html_escape($filters['to'] ?? ''); ?>">
            </div>
          </div>

          <div class="ocg-toolbar">
            <div class="ocg-toolbar-meta">Apply narrow filters before opening row detail. This keeps the table readable when request volume spikes.</div>
            <div>
              <button class="btn btn-default" id="ocg-apply-filters" type="button"><i class="fa fa-filter"></i> Apply Filters</button>
              <button class="btn btn-default" id="ocg-reset-filters" type="button"><i class="fa fa-refresh"></i> Reset View</button>
            </div>
          </div>
        </div>
      </div>

      <div class="panel_s ocg-surface ocg-log-table-wrap mtop20">
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

    function buildPill(text, kind, extraClass) {
      var classes = ['ocg-pill'];
      if (kind) classes.push(kind);
      if (extraClass) classes.push(extraClass);
      return '<span class="' + classes.join(' ') + '">' + escapeHtml(text || 'N/A') + '</span>';
    }

    function toPillClass(prefix, value) {
      var safe = String(value || 'n-a').toLowerCase().replace(/[^a-z0-9]+/g, '_');
      return prefix + safe;
    }

    function decorateOcgTables() {
      $('.table-ocg-pipeline-logs tbody tr, .table-ocg-bridge-logs tbody tr, .table-ocg-gateway-logs tbody tr').each(function() {
        var $cells = $(this).children('td');
        if (!$cells.length) return;

        if ($(this).closest('.table-ocg-pipeline-logs').length) {
          var directionText = $.trim($cells.eq(2).text());
          var statusText = $.trim($cells.eq(3).text());
          var agentText = $.trim($cells.eq(4).text());
          var eventText = $.trim($cells.eq(5).text());
          var requestText = $.trim($cells.eq(7).text());
          var summaryText = $.trim($cells.eq(8).text());
          $cells.eq(2).html(buildPill(directionText, toPillClass('ocg-pill-direction-', directionText)));
          $cells.eq(3).html(buildPill(statusText, toPillClass('ocg-pill-status-', statusText)));
          $cells.eq(4).html('<div class="ocg-row-strong">' + escapeHtml(agentText || 'N/A') + '</div>');
          $cells.eq(5).html('<div class="ocg-row-strong">' + escapeHtml(eventText || 'N/A') + '</div>');
          $cells.eq(7).html('<div class="ocg-row-strong">' + escapeHtml(requestText || 'N/A') + '</div>');
          $cells.eq(8).html('<div class="ocg-row-muted">' + escapeHtml(summaryText || 'No summary') + '</div>');
        }

        if ($(this).closest('.table-ocg-bridge-logs').length) {
          var bridgeStatus = $.trim($cells.eq(5).text());
          var bridgeHttp = $.trim($cells.eq(7).text());
          var bridgeError = $.trim($cells.eq(8).text());
          $cells.eq(2).html('<div class="ocg-row-strong">' + escapeHtml($.trim($cells.eq(2).text()) || 'N/A') + '</div>');
          $cells.eq(3).html('<div class="ocg-row-strong">' + escapeHtml($.trim($cells.eq(3).text()) || 'N/A') + '</div>');
          $cells.eq(4).html('<div class="ocg-row-muted">' + escapeHtml($.trim($cells.eq(4).text()) || 'N/A') + '</div>');
          $cells.eq(5).html(buildPill(bridgeStatus, toPillClass('ocg-pill-status-', bridgeStatus)));
          $cells.eq(7).html(buildPill(bridgeHttp || '0', 'ocg-pill-http ' + toPillClass('ocg-pill-http-', (bridgeHttp || '0').charAt(0))));
          $cells.eq(8).html('<div class="ocg-row-muted">' + escapeHtml(bridgeError || 'No error text') + '</div>');
        }

        if ($(this).closest('.table-ocg-gateway-logs').length) {
          var methodText = $.trim($cells.eq(2).text());
          var gatewayStatus = $.trim($cells.eq(4).text());
          var gatewayHttp = $.trim($cells.eq(5).text());
          var actionText = $.trim($cells.eq(6).text());
          var requestId = $.trim($cells.eq(7).text());
          var errorText = $.trim($cells.eq(8).text());
          $cells.eq(2).html(buildPill(methodText || 'N/A', 'ocg-pill-http'));
          $cells.eq(3).html('<div class="ocg-row-muted">' + escapeHtml($.trim($cells.eq(3).text()) || 'N/A') + '</div>');
          $cells.eq(4).html(buildPill(gatewayStatus, toPillClass('ocg-pill-status-', gatewayStatus)));
          $cells.eq(5).html(buildPill(gatewayHttp || '0', 'ocg-pill-http ' + toPillClass('ocg-pill-http-', (gatewayHttp || '0').charAt(0))));
          $cells.eq(6).html('<div class="ocg-row-strong">' + escapeHtml(actionText || 'N/A') + '</div>');
          $cells.eq(7).html('<div class="ocg-row-strong">' + escapeHtml(requestId || 'N/A') + '</div>');
          $cells.eq(8).html('<div class="ocg-row-muted">' + escapeHtml(errorText || 'No error text') + '</div>');
        }

        var $detailLink = $(this).find('.ocg-open-detail');
        if ($detailLink.length) {
          $detailLink.addClass('ocg-detail-trigger').html('<i class="fa fa-arrow-right"></i>');
        }
      });
    }

    function renderDetailOverview(res) {
      var row = res.row || {};
      var decoded = res.decoded || {};
      var status = row.status || row.http_method || 'N/A';
      var primaryCode = row.http_code || row.id || 'N/A';
      var requestId = row.request_id || 'N/A';
      var actionId = row.action_id || row.event_name || row.module_name || 'N/A';

      var summary = '' +
        '<div class="ocg-detail-summary">' +
          '<div class="ocg-detail-chip"><div class="ocg-detail-chip-label">Type</div><div class="ocg-detail-chip-value">' + escapeHtml(res.type || 'log') + '</div></div>' +
          '<div class="ocg-detail-chip"><div class="ocg-detail-chip-label">Status</div><div class="ocg-detail-chip-value">' + buildPill(status, row.status ? toPillClass('ocg-pill-status-', row.status) : 'ocg-pill-http') + '</div></div>' +
          '<div class="ocg-detail-chip"><div class="ocg-detail-chip-label">Code</div><div class="ocg-detail-chip-value">' + escapeHtml(primaryCode) + '</div></div>' +
          '<div class="ocg-detail-chip"><div class="ocg-detail-chip-label">Request</div><div class="ocg-detail-chip-value">' + escapeHtml(requestId) + '</div></div>' +
        '</div>';

      var detail = '' +
        '<div class="ocg-detail-overview-grid">' +
          '<div class="ocg-detail-card">' +
            '<h5>Record summary</h5>' +
            '<dl class="ocg-kv">' +
              '<dt>Action</dt><dd>' + escapeHtml(actionId) + '</dd>' +
              '<dt>Path</dt><dd>' + escapeHtml(row.path || row.event_name || 'N/A') + '</dd>' +
              '<dt>Agent</dt><dd>' + escapeHtml(row.agent_id || row.module_name || row.principal_id || 'N/A') + '</dd>' +
              '<dt>Created</dt><dd>' + escapeHtml(row.created_at || 'N/A') + '</dd>' +
              '<dt>Message</dt><dd>' + escapeHtml(row.error_message || row.last_error || row.summary || 'No summary available') + '</dd>' +
            '</dl>' +
          '</div>' +
          '<div class="ocg-detail-card">' +
            '<h5>Decoded payload presence</h5>' +
            '<dl class="ocg-kv">' +
              '<dt>Payload</dt><dd>' + escapeHtml(decoded.payload ? 'Available' : 'None') + '</dd>' +
              '<dt>Meta</dt><dd>' + escapeHtml(decoded.meta ? 'Available' : 'None') + '</dd>' +
              '<dt>Params</dt><dd>' + escapeHtml(decoded.params_masked ? 'Available' : 'None') + '</dd>' +
              '<dt>HTTP</dt><dd>' + escapeHtml(row.http_code || row.http_method || 'N/A') + '</dd>' +
              '<dt>ID</dt><dd>' + escapeHtml(res.id || 'N/A') + '</dd>' +
            '</dl>' +
          '</div>' +
        '</div>';

      return summary + detail;
    }

    $('.table-ocg-pipeline-logs, .table-ocg-bridge-logs, .table-ocg-gateway-logs').on('draw.dt', decorateOcgTables);
    decorateOcgTables();

    $('body').on('click', '.ocg-open-detail', function(e) {
      e.preventDefault();
      var id = $(this).data('id');
      var type = $(this).data('type');
      $.getJSON('<?= admin_url('openclaw_gateway/openclaw_gateway_admin/log_detail'); ?>/' + encodeURIComponent(type) + '/' + encodeURIComponent(id))
        .done(function(res) {
          if (!res || !res.ok) return;
          $('#ocg-detail-title').text('Log Detail - ' + (res.type || '') + ' #' + (res.id || ''));
          $('#ocg-detail-overview').html(renderDetailOverview(res));
          $('#ocg-detail-payload').html(prettyJson((res.decoded || {}).payload));
          $('#ocg-detail-meta').html(prettyJson((res.decoded || {}).meta));
          $('#ocg-detail-raw').html(prettyJson(res.row || {}));
          $('#ocg-detail-modal').modal('show');
        });
    });
  });
</script>
