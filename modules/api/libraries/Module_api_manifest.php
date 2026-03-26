<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Module_api_manifest
{
    public function load($module)
    {
        $module = trim((string) $module);
        if ($module === '') {
            return null;
        }

        $manifestPath = APPPATH . '../modules/' . $module . '/api/Manifest.php';
        if (!is_file($manifestPath)) {
            return null;
        }

        $manifest = include $manifestPath;
        if (!is_array($manifest)) {
            return null;
        }

        $manifest['module'] = $module;

        return $manifest;
    }
}
