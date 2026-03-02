<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Publishx extends AdminController
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

    public function posts()
    {

        if (!has_permission('publishx_posts', '', 'view')) {
            access_denied('publishx');
        }

        if ($this->input->is_ajax_request()) {
            $this->app->get_table_data(module_views_path('publishx', 'tables/posts'));
        }

        $data['title'] = _l('publishx') . ' - ' . _l('publishx_posts');
        $this->load->view('posts', $data);

    }

    public function post($id = '')
    {

        if (!has_permission('publishx_posts', '', 'create')) {
            access_denied('publishx');
        }

        if ($this->input->post() && $id === '') {

            $data = $this->input->post();

            $timestamp = strtotime($data['created_at']);
            $data['created_at'] = date("Y-m-d H:i:s", $timestamp);

            if (!empty($data['post_title'])) {
                $data['post_slug'] = slug_it($data['post_title']);
            }

            $post_id = $this->publishx_model->addPost($data + ['author_id' => get_staff_user_id(), 'views' => 0]);

            $uploadResponse = publishx_handle_post_feature_image_upload($post_id);

            if ($uploadResponse['success'] === true) {
                $this->publishx_model->updatePost($post_id, ['featured_image' => $uploadResponse['file_name']]);
            }

            if (is_numeric($post_id)) {
                set_alert('success', _l('added_successfully', _l('publishx_posts')));
                redirect(admin_url('publishx/post/' . $post_id));
            } else {
                set_alert('warning', _l('predix_template_category_failed_to_create'));
                redirect(admin_url('publishx/post'));
            }

        } elseif ($this->input->post() && $id !== '') {

            $data = $this->input->post();

            $timestamp = strtotime($data['created_at']);
            $data['created_at'] = date("Y-m-d H:i:s", $timestamp);

            if (!empty($data['post_title'])) {
                $data['post_slug'] = slug_it($data['post_title']);
            }

            $uploadResponse = publishx_handle_post_feature_image_upload($id);

            if ($uploadResponse['success'] === true) {
                $data['featured_image'] = $uploadResponse['file_name'];
            }

            $response = $this->publishx_model->updatePost($id, $data);

            if ($response) {
                set_alert('success', _l('updated_successfully', _l('publishx_posts')));
                redirect(admin_url('publishx/post/' . $id));
            } else {
                set_alert('warning', _l('predix_template_category_failed_to_create'));
                redirect(admin_url('publishx/post/' . $id));
            }
        }

        $data['title'] = _l('publishx') . ' - ' . _l('publishx_posts');
        if ($id) {
            $data['post_data'] = $this->publishx_model->getPost($id);
        }
        $data['post_categories'] = $this->publishx_model->getCategories();
        $data['post_languages'] = $this->publishx_model->getLanguages();

        $this->load->view('create_post', $data);

    }

    public function delete_post($id = '')
    {

        if (!has_permission('publishx_posts', '', 'delete')) {
            access_denied('publishx');
        }

        if (!$id) {
            redirect(admin_url('publishx/posts'));
        }

        $response = $this->publishx_model->deletePost($id);

        if ($response == true) {
            set_alert('success', _l('deleted', _l('publishx_posts')));
        } else {
            set_alert('warning', _l('problem_deleting', _l('publishx_posts')));
        }

        redirect(admin_url('publishx/posts'));

    }

    public function remove_post_featured_image($post_id)
    {
        $postData = $this->publishx_model->getPost($post_id);

        $path = FCPATH . 'modules/publishx/uploads/posts/' . $post_id . '/' . $postData->featured_image;
        if (file_exists($path)) {
            unlink($path);
        }
        $this->publishx_model->updatePost($post_id, ['featured_image' => '']);
        redirect($_SERVER['HTTP_REFERER']);
    }

    public function categories()
    {

        if (!has_permission('publishx_categories', '', 'view')) {
            access_denied('publishx');
        }

        if ($this->input->is_ajax_request()) {
            $this->app->get_table_data(module_views_path('publishx', 'tables/categories'));
        }

        $data['title'] = _l('publishx') . ' - ' . _l('publishx_categories');
        $this->load->view('categories', $data);

    }

    public function category($id = '')
    {
        if (!has_permission('publishx_categories', '', 'create')) {
            access_denied('publishx');
        }

        if ($this->input->post() && $id === '') {

            $response = $this->publishx_model->addCategory($this->input->post() + ['created_at' => date('Y-m-d H:i:s')]);

            if ($response == true) {
                set_alert('success', _l('publishx_category_created_successfully'));
            } else {
                set_alert('warning', _l('publishx_category_not_created_successfully'));
            }

            redirect(admin_url('publishx/categories'));

        } elseif ($this->input->post() && $id !== '') {
            $response = $this->publishx_model->updateCategory($id, $this->input->post());

            if ($response == true) {
                set_alert('success', _l('publishx_category_updated_successfully'));
            } else {
                set_alert('warning', _l('publishx_category_not_updated_successfully'));
            }

            redirect(admin_url('publishx/categories'));
        }

        $data['title'] = _l('publishx') . ' - ' . _l('publishx_categories');
        if ($id) {
            $data['category_data'] = $this->publishx_model->getCategory($id);
        }

        $this->load->view('create_category', $data);

    }

    public function delete_category($id = '')
    {

        if (!has_permission('publishx_categories', '', 'delete')) {
            access_denied('publishx');
        }

        if (!$id) {
            redirect(admin_url('publishx/categories'));
        }

        $response = $this->publishx_model->deleteCategory($id);

        if (is_array($response) && isset($response['referenced'])) {
            set_alert('warning', _l('is_referenced', _l('publishx_categories')));
        } elseif ($response == true) {
            set_alert('success', _l('deleted', _l('publishx_categories')));
        } else {
            set_alert('warning', _l('problem_deleting', _l('publishx_categories')));
        }

        redirect(admin_url('publishx/categories'));

    }

    public function languages()
    {

        if (!has_permission('publishx_languages', '', 'view')) {
            access_denied('publishx');
        }

        if ($this->input->is_ajax_request()) {
            $this->app->get_table_data(module_views_path('publishx', 'tables/languages'));
        }

        $data['title'] = _l('publishx') . ' - ' . _l('publishx_languages');
        $this->load->view('languages', $data);

    }

    public function language($id = '')
    {
        if (!has_permission('publishx_languages', '', 'create')) {
            access_denied('publishx');
        }

        if ($this->input->post() && $id === '') {

            $response = $this->publishx_model->addLanguage($this->input->post() + ['created_at' => date('Y-m-d H:i:s')]);

            if ($response == true) {
                set_alert('success', _l('publishx_language_created_successfully'));
            } else {
                set_alert('warning', _l('publishx_language_not_created_successfully'));
            }

            redirect(admin_url('publishx/languages'));

        } elseif ($this->input->post() && $id !== '') {
            $response = $this->publishx_model->updateLanguage($id, $this->input->post());

            if ($response == true) {
                set_alert('success', _l('publishx_language_updated_successfully'));
            } else {
                set_alert('warning', _l('publishx_language_not_updated_successfully'));
            }

            redirect(admin_url('publishx/languages'));
        }

        $data['title'] = _l('publishx') . ' - ' . _l('publishx_languages');
        if ($id) {
            $data['language_data'] = $this->publishx_model->getLanguage($id);
        }

        $this->load->view('create_language', $data);
    }

    public function delete_language($id = '')
    {

        if (!has_permission('publishx_languages', '', 'delete')) {
            access_denied('publishx');
        }

        if (!$id) {
            redirect(admin_url('publishx/languages'));
        }

        $response = $this->publishx_model->deleteLanguage($id);

        if (is_array($response) && isset($response['referenced'])) {
            set_alert('warning', _l('is_referenced', _l('publishx_languages')));
        } elseif ($response == true) {
            set_alert('success', _l('deleted', _l('publishx_languages')));
        } else {
            set_alert('warning', _l('problem_deleting', _l('publishx_languages')));
        }

        redirect(admin_url('publishx/languages'));

    }

    public function settings()
    {
        if (!is_admin()) {
            access_denied('publishx');
        }

        if ($this->input->post()) {
            if (!is_admin()) {
                access_denied('settings');
            }
            $this->load->model('payment_modes_model');
            $this->load->model('settings_model');

            publishx_handle_company_logo_upload();
            publishx_handle_company_favicon_upload();

            $post_data = $this->input->post();
            $tmpData = $this->input->post(null, false);

            if (isset($post_data['settings']['email_header'])) {
                $post_data['settings']['email_header'] = $tmpData['settings']['email_header'];
            }

            if (isset($post_data['settings']['email_footer'])) {
                $post_data['settings']['email_footer'] = $tmpData['settings']['email_footer'];
            }

            if (isset($post_data['settings']['email_signature'])) {
                $post_data['settings']['email_signature'] = $tmpData['settings']['email_signature'];
            }

            if (isset($post_data['settings']['smtp_password'])) {
                $post_data['settings']['smtp_password'] = $tmpData['settings']['smtp_password'];
            }

            $success = $this->settings_model->update($post_data);

            if ($success > 0) {
                set_alert('success', _l('settings_updated'));
            }

            redirect(admin_url('publishx/settings'), 'refresh');
        }

        $data['title'] = _l('publishx') . ' - ' . _l('settings');
        $this->load->view('settings', $data);
    }

    public function remove_blog_logo()
    {
        $logoName = get_option('publishx_blog_logo');

        $path = FCPATH . 'modules/publishx/uploads/' . $logoName;
        if (file_exists($path)) {
            unlink($path);
        }

        update_option('publishx_blog_logo', '');
        redirect($_SERVER['HTTP_REFERER']);
    }

    public function remove_blog_favicon_logo()
    {
        $logoName = get_option('publishx_blog_favicon_logo');

        $path = FCPATH . 'modules/publishx/uploads/' . $logoName;
        if (file_exists($path)) {
            unlink($path);
        }

        update_option('publishx_blog_favicon_logo', '');
        redirect($_SERVER['HTTP_REFERER']);
    }

    public function themes()
    {
        if (!has_permission('publishx_themes', '', 'view')) {
            access_denied('publishx');
        }

        $data['title'] = _l('publishx') . ' - ' . _l('publishx_themes');
        $this->load->view('themes', $data);
    }

    public function activate_theme($theme)
    {
        update_option('publishx_selected_blog_theme', $theme);
        redirect($_SERVER['HTTP_REFERER']);
    }

    public function postAI()
    {

        if (empty(get_option('publishx_openai_key'))) {
            echo json_encode([
                'message' => _l('publishx_missing_openai_key'),
                'status' => 'not'
            ]);
            die;
        }

        $aiType = $this->input->post('type');
        $postTitle = $this->input->post('post_title');
        $postLanguage = $this->input->post('post_language');

        $postShort = $this->input->post('post_short');
        $postFull = $this->input->post('post_full');

        if (empty($postTitle)) {
            echo json_encode([
                'message' => _l('publishx_ai_missing_blog_title'),
                'status' => 'not'
            ]);
            die;
        }

        $prompt = '';
        // lấy Post data
      
        if ($aiType === 'short_content') {
            if ($postShort && strlen($postShort) > 0) {
                $prompt = 'This is the opening paragraph, just continue from this paragraph, without changing it" '.$postShort.'. Write a short content about a blog post wih title :'. $postTitle . ' it should contain a maximum of 160 characters; Write in Language: ' .  $postLanguage  ;
     
            } else {
                $prompt = 'Write a short content about a blog post wih title :'. $postTitle . ' it should contain a maximum of 160 characters; Write in Language: ' .  $postLanguage  ;
     
            }
        }
        if ($aiType === 'full_content') {
            if ($postFull && strlen($postFull) > 0) {
                $prompt = 'This is the opening paragraph, just continue from this paragraph, without changing it" '.$postFull.'. Write an continued part of seo friendly and professional blog post for the title :'. $postTitle. ' ; Please annotate appropriate illustrations in important passages. For example: <Insert picture with ABC theme>; Write in Language: ' .  $postLanguage ;
            } else {
                $prompt = 'Write a seo friendly and professional blog post for the title :'. $postTitle. ' ; Please annotate appropriate illustrations in important passages. For example: <Insert picture with ABC theme>; Write in Language: ' .  $postLanguage ;
            }
        }

        $openAi = new PublishxOpenAI(get_option('publishx_openai_key'));

        $result = $openAi->completion([
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'temperature' => 1.0,
            'max_tokens' => 2000,
            'frequency_penalty' => 0,
            'presence_penalty' => 0
        ]);

        $result = json_decode($result);

        $text = '';
        foreach ($result->choices as $choice):
            $text .= $choice->message->content;
        endforeach;

        echo json_encode([
            'ai_generated' => nl2br($text),
            'status' => 'ok'
        ]);
        die;
    }

}
