<?php
defined('BASEPATH') or exit('No direct script access allowed');

$route = isset($route) && is_array($route) ? $route : [];

$route['api/openclaw/v1/capabilities'] = 'openclaw_gateway/capabilities';
$route['api/openclaw/v1/actions/invoke'] = 'openclaw_gateway/invoke';
$route['api/openclaw/v1/actions/batch'] = 'openclaw_gateway/batch';
$route['api/openclaw/v1/health'] = 'openclaw_gateway/health';
$route['api/openclaw/v1/stats'] = 'openclaw_gateway/stats';
$route['api/openclaw/v1/audit/(:any)'] = 'openclaw_gateway/audit/$1';

return $route;
