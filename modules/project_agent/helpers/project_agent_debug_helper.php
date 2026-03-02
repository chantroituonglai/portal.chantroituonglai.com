<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Project Agent Debug Helper
 * 
 * Provides debug logging functionality that respects the debug_enabled setting
 */

if (!function_exists('pa_debug_log')) {
    /**
     * Log debug message only if debug is enabled
     * 
     * @param string $message The debug message
     * @param string $level Log level (error, info, debug)
     * @param array $context Additional context data
     */
    function pa_debug_log($message, $level = 'error', $context = []) {
        // Check if debug is enabled
        if (!pa_is_debug_enabled()) {
            return;
        }
        
        // Add context to message if provided
        if (!empty($context)) {
            $message .= ' | Context: ' . json_encode($context);
        }
        
        // Log the message
        log_message($level, '[PA][debug] ' . $message);
    }
}

if (!function_exists('pa_is_debug_enabled')) {
    /**
     * Check if debug mode is enabled
     * 
     * @return bool True if debug is enabled
     */
    function pa_is_debug_enabled() {
        static $debug_enabled = null;
        
        if ($debug_enabled === null) {
            $CI = &get_instance();
            $CI->load->database();
            
            // Use safe query to avoid ambiguous column errors
            $query = $CI->db->select('value')
                           ->from(db_prefix() . 'options')
                           ->where('name', 'project_agent_debug_enabled')
                           ->limit(1)
                           ->get();
            
            if ($query && $query->num_rows() > 0) {
                $row = $query->row();
                $debug_enabled = !empty($row->value) && $row->value !== '0';
            } else {
                $debug_enabled = false;
            }
        }
        
        return $debug_enabled;
    }
}

if (!function_exists('pa_log_error')) {
    /**
     * Log error message (always logged, regardless of debug setting)
     * 
     * @param string $message The error message
     * @param array $context Additional context data
     */
    function pa_log_error($message, $context = []) {
        // Add context to message if provided
        if (!empty($context)) {
            $message .= ' | Context: ' . json_encode($context);
        }
        
        // Always log errors
        log_message('error', '[PA][error] ' . $message);
    }
}

if (!function_exists('pa_log_info')) {
    /**
     * Log info message only if debug is enabled
     * 
     * @param string $message The info message
     * @param array $context Additional context data
     */
    function pa_log_info($message, $context = []) {
        pa_debug_log($message, 'info', $context);
    }
}

if (!function_exists('pa_log_debug')) {
    /**
     * Log debug message only if debug is enabled
     * 
     * @param string $message The debug message
     * @param array $context Additional context data
     */
    function pa_log_debug($message, $context = []) {
        pa_debug_log($message, 'debug', $context);
    }
}
