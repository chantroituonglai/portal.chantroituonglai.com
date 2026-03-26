<?php

defined('BASEPATH') or exit('No direct script access allowed');

require __DIR__ . '/REST_Controller.php';

class Thirdparty extends REST_Controller
{
    public function data_get($id = '')
    {
        $records = [
            [
                'id' => 'customers',
                'name' => 'Customers',
                'source' => 'customers',
                'description' => 'Thirdparty API compatibility points to customer records in this workspace.',
            ],
            [
                'id' => 'contacts',
                'name' => 'Contacts',
                'source' => 'contacts',
                'description' => 'Thirdparty API compatibility points to contact records in this workspace.',
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

    public function data_post()
    {
        return $this->response(['status' => false, 'message' => 'Thirdparty write operations are not configured in this workspace'], 501);
    }

    public function data_put($id = '')
    {
        return $this->response(['status' => false, 'message' => 'Thirdparty write operations are not configured in this workspace'], 501);
    }

    public function data_delete($id = '')
    {
        return $this->response(['status' => false, 'message' => 'Thirdparty delete operations are not configured in this workspace'], 501);
    }
}
