<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Publishx_client extends ClientsController
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('publishx_model');
    }

    public function index()
    {
        show_404();
    }

    public function blog()
    {

        $accessMenu = false;

        if (get_option('publishx_show_on_client_side') == '1') {
            $accessMenu = true;
        }

        if (!$accessMenu) {
            redirect(site_url('knowledge-base'));
        }

        $data = [];

        $data['title'] = _l('publishx_blog');

        $data['categories_list'] = $this->publishx_model->getCategories();
        $data['posts_list'] = $this->publishx_model->getPosts();

        $this->data($data);
        $this->view('client/posts');
        $this->layout();
    }

}