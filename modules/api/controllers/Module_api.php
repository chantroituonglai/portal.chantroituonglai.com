<?php

defined('BASEPATH') or exit('No direct script access allowed');

require __DIR__ . '/REST_Controller.php';

class Module_api extends REST_Controller
{
    protected $manifestLoader;

    public function __construct()
    {
        parent::__construct();
        require_once APPPATH . '../modules/api/helpers/module_api_helper.php';
        $this->load->library('Module_api_manifest');
        $this->manifestLoader = $this->module_api_manifest;
    }

    public function index($module = '')
    {
        $manifest = $this->manifestLoader->load($module);
        if (!$manifest) {
            return $this->response(module_api_envelope(false, 'Module manifest not found', null, ['module' => $module]), REST_Controller::HTTP_NOT_FOUND);
        }

        return $this->response(module_api_envelope(true, 'Module manifest loaded', [
            'module' => $module,
            'label' => $manifest['label'] ?? $module,
            'resources' => array_keys($manifest['resources'] ?? []),
            'read_only' => (bool) ($manifest['read_only'] ?? true),
        ]), REST_Controller::HTTP_OK);
    }

    public function resource($module = '', $resource = '', $id = null)
    {
        $manifest = $this->manifestLoader->load($module);
        if (!$manifest) {
            return $this->response(module_api_envelope(false, 'Module manifest not found', null, ['module' => $module]), REST_Controller::HTTP_NOT_FOUND);
        }

        $resources = $manifest['resources'] ?? [];
        if (!isset($resources[$resource]) || !is_array($resources[$resource])) {
            return $this->response(module_api_envelope(false, 'Resource not found', null, ['module' => $module, 'resource' => $resource]), REST_Controller::HTTP_NOT_FOUND);
        }

        $config = $resources[$resource];
        $method = strtolower((string) $this->input->server('REQUEST_METHOD'));

        if ($method === 'get') {
            $payload = [
                'module' => $module,
                'resource' => $resource,
                'id' => $id,
                'config' => $config,
            ];

            if (!empty($config['table']) && $this->db->table_exists($config['table'])) {
                $primaryKey = $config['primary_key'] ?? 'id';
                if ($id !== null) {
                    $payload['records'] = $this->db->where($primaryKey, $id)->get($config['table'])->row_array();
                } else {
                    $limit = isset($config['default_limit']) ? (int) $config['default_limit'] : 25;
                    $payload['records'] = $this->db->limit(max(1, min(100, $limit)))->get($config['table'])->result_array();
                }
            }

            return $this->response(module_api_envelope(true, 'Module API resource metadata loaded', $payload), REST_Controller::HTTP_OK);
        }

        return $this->response(module_api_envelope(false, 'Write operations are disabled for generic module API resources until a module-specific adapter is added', null, [
            'module' => $module,
            'resource' => $resource,
            'method' => strtoupper($method),
        ]), REST_Controller::HTTP_NOT_IMPLEMENTED);
    }
}
