<?php
defined('BASEPATH') or exit('No direct script access allowed');

// Instrumentation wrapper for CI_DB_mysqli_driver to trace heavy queries and memory spikes
class MY_DB_mysqli_driver extends CI_DB_mysqli_driver
{
    protected static $pa_trace_enabled = null;
    protected static $pa_trace_file = null;

    protected function pa_trace_enabled()
    {
        if (self::$pa_trace_enabled === null) {
            $flag = FCPATH . 'temp' . DIRECTORY_SEPARATOR . 'pa_db_trace.on';
            self::$pa_trace_file = $flag;
            self::$pa_trace_enabled = file_exists($flag);
        }
        return self::$pa_trace_enabled;
    }

    protected function pa_trace_log($data)
    {
        try {
            $dir = FCPATH . 'temp';
            if (!is_dir($dir)) { @mkdir($dir, 0777, true); }
            $path = $dir . DIRECTORY_SEPARATOR . 'pa_dbtrace.log';
            @file_put_contents($path, json_encode($data) . "\n", FILE_APPEND);
        } catch (\Throwable $e) {
            // ignore
        }
        // Also output via CI logger (compact)
        $msg = '[DBTRACE] ms=' . (isset($data['ms'])?$data['ms']:'?') . ' rows=' . (isset($data['rows'])?$data['rows']:'?') . ' memKB=' . (isset($data['mem_kb'])?$data['mem_kb']:'?') . ' sql="' . (isset($data['sql_snippet'])?$data['sql_snippet']:'') . '" @ ' . (isset($data['caller'])?$data['caller']:'?');
        log_message('error', $msg);
    }

    public function query($sql, $binds = false, $return_object = true)
    {
        $trace = $this->pa_trace_enabled();
        $mem_before = $trace ? memory_get_usage(true) : 0;
        $t0 = $trace ? microtime(true) : 0;

        $result = parent::query($sql, $binds, $return_object);

        if ($trace) {
            $mem_after = memory_get_usage(true);
            $delta = $mem_after - $mem_before;
            $ms = round((microtime(true) - $t0) * 1000, 1);
            $rows = null;
            try {
                if ($return_object && is_object($result) && method_exists($result, 'num_rows')) {
                    $rows = @$result->num_rows();
                }
            } catch (\Throwable $e) {}

            // Build caller info (first frame outside system/)
            $caller = '';
            foreach (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10) as $fr) {
                if (!isset($fr['file'])) continue;
                if (strpos($fr['file'], DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR) !== false) continue;
                $caller = $fr['file'] . ':' . (isset($fr['line']) ? $fr['line'] : '?');
                break;
            }

            // Only log when heavy
            $heavy = ($delta > 5 * 1024 * 1024) || ($rows !== null && $rows > 1000) || ($ms > 1000);
            if ($heavy) {
                $this->pa_trace_log([
                    'ts' => date('c'),
                    'ms' => $ms,
                    'rows' => $rows,
                    'mem_kb' => (int)round($delta / 1024),
                    'sql_snippet' => substr(is_string($sql) ? preg_replace('/\s+/', ' ', $sql) : '', 0, 300),
                    'caller' => $caller,
                ]);
            }
        }

        return $result;
    }
}

