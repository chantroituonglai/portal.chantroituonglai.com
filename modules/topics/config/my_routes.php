<?php
defined('BASEPATH') or exit('No direct script access allowed');

// Admin Routes
$route['admin/topics']                     = 'topics/index';
$route['admin/topics/create']              = 'topics/create';
$route['admin/topics/edit/(:num)']         = 'topics/edit/$1';
$route['admin/topics/delete/(:num)']       = 'topics/delete/$1';
$route['admin/topics/detail/(:num)']       = 'topics/detail/$1';

// Action Types Routes
$route['admin/topics/action_types']                = 'topics/action_types';
$route['admin/topics/action_types/create']         = 'topics/action_types/create';
$route['admin/topics/action_types/edit/(:num)']    = 'topics/action_types/edit/$1';
$route['admin/topics/action_types/delete/(:num)']  = 'topics/action_types/delete/$1';

// Action States Routes
$route['admin/topics/action_states']               = 'topics/action_states';
$route['admin/topics/action_states/create']        = 'topics/action_states/create';
$route['admin/topics/action_states/edit/(:num)']   = 'topics/action_states/edit/$1';
$route['admin/topics/action_states/delete/(:num)'] = 'topics/action_states/delete/$1'; 


