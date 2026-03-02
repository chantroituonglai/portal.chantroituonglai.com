<?php
defined('BASEPATH') or exit('No direct script access allowed');

// Optional: light dependency check
if (function_exists('log_message')) {
    if (!is_dir(FCPATH . 'modules/project_agent')) {
        log_message('error', 'Project Management Agent: Project Agent module is not found. Some features will be disabled.');
    }
}

// Tables are created via migrations from main module file
return true;

