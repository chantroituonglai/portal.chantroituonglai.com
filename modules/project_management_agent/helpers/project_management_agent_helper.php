<?php
defined('BASEPATH') or exit('No direct script access allowed');

if (!function_exists('pma_is_project_agent_available')) {
    function pma_is_project_agent_available(): bool
    {
        $CI = &get_instance();
        try {
            if (!isset($CI->app_modules)) { return false; }
            return (bool) $CI->app_modules->is_active('project_agent');
        } catch (Throwable $e) {
            return false;
        }
    }
}

if (!function_exists('pma_now')) {
    function pma_now(): string
    {
        return date('Y-m-d H:i:s');
    }
}

