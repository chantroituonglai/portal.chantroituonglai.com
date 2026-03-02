<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Blog extends App_Controller
{

    public $selectedTheme = 'clean_blog';

    public function __construct()
    {
        hooks()->do_action('after_clients_area_init', $this);

        parent::__construct();

        $this->load->model('publishx_model');
        $this->selectedTheme = get_option('publishx_selected_blog_theme');
    }

    public function index()
    {

        $data = [];

        $categoryQueryString = $_GET['category'] ?? '';
        $searchQueryString = $_GET['q'] ?? '';
        $languageQueryString = $_GET['l'] ?? '';

        $categoryQueryString = $this->security->xss_clean($categoryQueryString);
        $searchQueryString = $this->security->xss_clean($searchQueryString);
        $languageQueryString = $this->security->xss_clean($languageQueryString);

        if (!empty($categoryQueryString)) {
            $data['posts'] = $this->publishx_model->getPostsBasedOnCategory($categoryQueryString);
        } elseif (!empty($searchQueryString)) {
            $data['posts'] = $this->publishx_model->searchPosts($searchQueryString);
        } elseif (!empty($languageQueryString)) {
            $data['posts'] = $this->publishx_model->getPostsBasedOnLanguage($languageQueryString);
        } else {
            $data['posts'] = $this->publishx_model->getPosts();
        }

        $data['post_categories'] = $this->publishx_model->getCategories();
        $data['post_languages'] = $this->publishx_model->getLanguages();

        $this->load->view('themes/'.$this->selectedTheme.'/index', $data);
    }

    public function post($slug)
    {
        $data = [];

        $data['post'] = $this->publishx_model->getPostBasedOnSlug($slug);
        $data['post_categories'] = $this->publishx_model->getCategories();

        $this->publishx_model->updatePostViews($data['post']->id);

        $this->load->view('themes/'.$this->selectedTheme.'/post', $data);
    }

}
