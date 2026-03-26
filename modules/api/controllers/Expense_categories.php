<?php

defined('BASEPATH') or exit('No direct script access allowed');

require __DIR__ . '/REST_Controller.php';

class Expense_categories extends REST_Controller
{
    public function data_get($id = '')
    {
        $this->load->model('expenses_model');
        $data = $this->expenses_model->get_category();

        if ($id !== '') {
            $data = array_values(array_filter((array) $data, function ($row) use ($id) {
                return isset($row['id']) && (string) $row['id'] === (string) $id;
            }));
            $data = empty($data) ? [] : $data[0];
        }

        if (empty($data)) {
            return $this->response(['status' => false, 'message' => 'No data were found'], REST_Controller::HTTP_NOT_FOUND);
        }

        return $this->response($data, REST_Controller::HTTP_OK);
    }
}
