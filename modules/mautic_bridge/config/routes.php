<?php

defined('BASEPATH') or exit('No direct script access allowed');

$route = isset($route) && is_array($route) ? $route : [];

$route['mautic_bridge/webhook/contact'] = 'mautic_bridge_webhook/contact';
$route['mautic_bridge/cron/process'] = 'mautic_bridge_webhook/process';
$route['admin/mautic_bridge_manage'] = 'mautic_bridge/manage_index';
$route['admin/mautic_bridge_manage/queue'] = 'mautic_bridge/manage_queue';
$route['admin/mautic_bridge_manage/mappings'] = 'mautic_bridge/manage_mappings';
$route['admin/mautic_bridge_manage/campaigns'] = 'mautic_bridge/manage_campaigns';
$route['admin/mautic_bridge_manage/logs'] = 'mautic_bridge/manage_logs';
$route['admin/mautic_bridge_manage/backfill'] = 'mautic_bridge/manage_backfill';
$route['admin/mautic_bridge_manage/google_import'] = 'mautic_bridge/manage_google_import';
$route['admin/mautic_bridge_manage/projects'] = 'mautic_bridge_manage/projects';
$route['admin/mautic_bridge_manage/projects/create'] = 'mautic_bridge_manage/projects/create';
$route['admin/mautic_bridge_manage/projects/(:num)'] = 'mautic_bridge_manage/projects/$1';
$route['admin/mautic_bridge_manage/project_templates'] = 'mautic_bridge_manage/project_templates';
$route['admin/mautic_bridge_manage/google_import_upload'] = 'mautic_bridge/manage_google_import_upload';
$route['admin/mautic_bridge_manage/google_import_job/(:num)'] = 'mautic_bridge/manage_google_import_job/$1';
$route['admin/mautic_bridge_manage/google_import_process'] = 'mautic_bridge/manage_google_import_process';
$route['admin/mautic_bridge_manage/process_queue'] = 'mautic_bridge/manage_process_queue';
$route['admin/mautic_bridge_manage/retry_queue'] = 'mautic_bridge/manage_retry_queue';
$route['admin/mautic_bridge_manage/delete_queue'] = 'mautic_bridge/manage_delete_queue';
$route['admin/mautic_bridge_manage/reset_mapping'] = 'mautic_bridge/manage_reset_mapping';
$route['admin/mautic_bridge_manage/enqueue_resync'] = 'mautic_bridge/manage_enqueue_resync';
$route['admin/mautic_bridge_manage/sync_campaigns'] = 'mautic_bridge/manage_sync_campaigns';
$route['admin/mautic_bridge_manage/enqueue_backfill'] = 'mautic_bridge/manage_enqueue_backfill';
$route['admin/mautic_bridge_manage/project_refresh'] = 'mautic_bridge_manage/project_refresh';
$route['admin/mautic_bridge_manage/project_generate_tasks'] = 'mautic_bridge_manage/project_generate_tasks';
$route['admin/mautic_bridge_manage/project_create_issue'] = 'mautic_bridge_manage/project_create_issue';

return $route;
