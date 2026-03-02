<?php
defined('BASEPATH') or exit('No direct script access allowed');

$config = array(
    'name'        => 'Topics',
    'description' => 'Module quản lý Topics với nhật ký và trạng thái hành động.',
    'version'     => '1.0.3',
    'author'      => 'FHC',
    'requires'    => array(),
);

// Version 1.0.3 changes:
// - Fixed menu initialization after module activation
// - Consolidated menu code in hooks.php
// - Removed duplicate menu registration
// - Added proper menu structure with Action Types and States

// Version 1.0.2 changes:
// - Added Action Types management
// - Added Action States management
// - Added foreign key relationships
// - Added form validation
// - Added detailed views
