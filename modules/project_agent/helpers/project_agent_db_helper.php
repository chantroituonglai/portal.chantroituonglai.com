<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Project Agent DB Helper
 * Small utilities to qualify column names and resolve ambiguous columns
 */

if (!function_exists('pa_db_get_columns')) {
    /**
     * Get column names for a table (cached).
     * @param string $table Canonical table name with db_prefix already applied
     * @return array<string,bool> map of column => true
     */
    function pa_db_get_columns($table) {
        static $cache = [];
        if (isset($cache[$table])) { return $cache[$table]; }
        $CI = &get_instance();
        $cols = [];
        try {
            // Prefer DESCRIBE for performance/compatibility
            $res = $CI->db->query('DESCRIBE `'.$table.'`');
            foreach ($res->result_array() as $row) {
                if (isset($row['Field'])) { $cols[$row['Field']] = true; }
            }
        } catch (Throwable $e) {
            try {
                // Fallback to INFORMATION_SCHEMA
                $db = $CI->db->database;
                $sql = 'SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?';
                $res = $CI->db->query($sql, [$db, $table]);
                foreach ($res->result_array() as $row) {
                    if (isset($row['COLUMN_NAME'])) { $cols[$row['COLUMN_NAME']] = true; }
                }
            } catch (Throwable $e2) { /* ignore */ }
        }
        $cache[$table] = $cols;
        return $cols;
    }
}

if (!function_exists('pa_db_infer_table_for_column')) {
    /**
     * Given a bare column and a list of tables, find the most likely table that contains the column.
     * Returns table name or null if ambiguous/unknown.
     * @param string $column
     * @param array<string> $tables list of canonical table names (with db_prefix)
     */
    function pa_db_infer_table_for_column($column, array $tables) {
        $hits = [];
        foreach ($tables as $t) {
            $cols = pa_db_get_columns($t);
            if (isset($cols[$column])) { $hits[] = $t; }
        }
        if (count($hits) === 1) { return $hits[0]; }
        return null; // ambiguous or not found
    }
}

if (!function_exists('pa_db_qualify')) {
    /**
     * Qualify a column with a table if needed. If column already qualified or is an expression, returns as-is.
     * @param string $column e.g., 'name' or 't.name'
     * @param array<string> $tables ordered by priority (first is base table)
     * @return string qualified column
     */
    function pa_db_qualify($column, array $tables) {
        $c = trim($column);
        // Expressions or placeholders: leave them
        if ($c === '*' || strpos($c, '.') !== false || preg_match('/\(|\)|\s/', $c)) {
            return $column;
        }
        $table = pa_db_infer_table_for_column($c, $tables);
        if ($table) { return $table . '.' . $c; }
        // Fallback to the first table
        return (count($tables) ? $tables[0] : '') . ($tables ? '.' : '') . $c;
    }
}

if (!function_exists('pa_db_qualify_list')) {
    /**
     * Qualify multiple columns.
     * @param array<string> $columns
     * @param array<string> $tables
     * @return array<string>
     */
    function pa_db_qualify_list(array $columns, array $tables) {
        $out = [];
        foreach ($columns as $c) { $out[] = pa_db_qualify($c, $tables); }
        return $out;
    }
}

