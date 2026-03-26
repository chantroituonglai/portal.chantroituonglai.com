<?php

defined('BASEPATH') or exit('No direct script access allowed');

require __DIR__ . '/REST_Controller.php';

class Zapier extends REST_Controller
{
    public function data_get($id = '')
    {
        $records = [
            [
                'id' => 'health',
                'name' => 'Zapier Compatibility',
                'status' => 'available',
                'description' => 'Zapier compatibility surface restored for API catalog parity.',
            ],
        ];

        if ($id !== '') {
            foreach ($records as $record) {
                if ($record['id'] === (string) $id) {
                    return $this->response($record, REST_Controller::HTTP_OK);
                }
            }

            return $this->response(['status' => false, 'message' => 'No data were found'], REST_Controller::HTTP_NOT_FOUND);
        }

        return $this->response($records, REST_Controller::HTTP_OK);
    }
}
