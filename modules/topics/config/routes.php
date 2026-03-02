<?php
defined('BASEPATH') or exit('No direct script access allowed');

// BEGIN MODIFICATION: Provide a valid route array for Perfex/CodeIgniter module router
// Initialize $route as array
$route = isset($route) && is_array($route) ? $route : [];

// Load module routes definitions
$module_routes_file = __DIR__ . '/my_routes.php';
if (file_exists($module_routes_file)) {
	require $module_routes_file; // Will append to $route
}

// Ensure essential routes exist
if (!isset($route['admin/topics/overview'])) {
	$route['admin/topics/overview'] = 'overview/index';
}
if (!isset($route['admin/topics/dashboard'])) {
	$route['admin/topics/dashboard'] = 'topics/dashboard';
}

// Return the route array as required by Perfex module loader
return $route;
// END MODIFICATION


