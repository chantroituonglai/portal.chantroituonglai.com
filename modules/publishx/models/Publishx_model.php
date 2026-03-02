<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Publishx_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    public function addPost($data)
    {
        $this->db->insert(db_prefix() . 'publishx_posts', $data);
        $insert_id = $this->db->insert_id();

        if ($insert_id) {
            return $insert_id;
        }

        return false;
    }

    public function getPost($id)
    {
        $this->db->where('id', $id);
        return $this->db->get(db_prefix() . 'publishx_posts')->row();
    }

    public function getPostBasedOnSlug($slug)
    {
        $this->db->where('post_slug', $slug);
        return $this->db->get(db_prefix() . 'publishx_posts')->row();
    }

    public function getPosts($status = '0')
    {
        $this->db->where('status', $status);
        $this->db->order_by('created_at', 'desc');
        return $this->db->get(db_prefix() . 'publishx_posts')->result_array();
    }

    public function updatePost($id, $data)
    {
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'publishx_posts', $data);

        return $this->db->affected_rows() > 0;
    }

    public function updatePostViews($id)
    {
        $this->db->set('views', 'views+1', FALSE);
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'publishx_posts');

        return $this->db->affected_rows() > 0;
    }

    public function searchPosts($searchTerm, $page = 1)
    {
        $this->db->where('status', '0');
        $this->db->order_by('created_at', 'desc');
        $this->db->like('post_title', $searchTerm);
        $this->db->from(db_prefix() . 'publishx_posts');
        $this->db->limit(10, ($page - 1) * 10);

        return $this->db->get()->result_array();
    }

    public function getPostsBasedOnCategory($category_id)
    {
        $this->db->where('status', '0');
        $this->db->where('category_id', $category_id);
        $this->db->order_by('created_at', 'desc');
        return $this->db->get(db_prefix() . 'publishx_posts')->result_array();
    }

    public function getPostsBasedOnLanguage($language_id)
    {
        $this->db->where('status', '0');
        $this->db->where('language_id', $language_id);
        $this->db->order_by('created_at', 'desc');
        return $this->db->get(db_prefix() . 'publishx_posts')->result_array();
    }

    public function deletePost($id)
    {
        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'publishx_posts');

        if ($this->db->affected_rows() > 0) {
            return true;
        }

        return false;
    }

    public function addCategory($data)
    {
        $this->db->insert(db_prefix() . 'publishx_categories', $data);
        $insert_id = $this->db->insert_id();

        if ($insert_id) {
            return $insert_id;
        }

        return false;
    }

    public function getCategory($category_id)
    {
        $this->db->where('id', $category_id);
        return $this->db->get(db_prefix() . 'publishx_categories')->row();
    }

    public function getCategories()
    {
        return $this->db->get(db_prefix() . 'publishx_categories')->result_array();
    }

    public function updateCategory($id, $data)
    {
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'publishx_categories', $data);

        return $this->db->affected_rows() > 0;
    }

    public function deleteCategory($category_id)
    {

        if (is_reference_in_table('category_id', db_prefix() . 'publishx_posts', $category_id)) {
            return [
                'referenced' => true,
            ];
        }

        $this->db->where('id', $category_id);
        $this->db->delete(db_prefix() . 'publishx_categories');

        if ($this->db->affected_rows() > 0) {
            return true;
        }

        return false;
    }

    public function addLanguage($data)
    {
        $this->db->insert(db_prefix() . 'publishx_languages', $data);
        $insert_id = $this->db->insert_id();

        if ($insert_id) {
            return $insert_id;
        }

        return false;
    }

    public function getLanguage($id)
    {
        $this->db->where('id', $id);
        return $this->db->get(db_prefix() . 'publishx_languages')->row();
    }

    public function getLanguages()
    {
        return $this->db->get(db_prefix() . 'publishx_languages')->result_array();
    }

    public function updateLanguage($id, $data)
    {
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'publishx_languages', $data);

        return $this->db->affected_rows() > 0;
    }

    public function deleteLanguage($id)
    {

        if (is_reference_in_table('language_id', db_prefix() . 'publishx_posts', $id)) {
            return [
                'referenced' => true,
            ];
        }

        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'publishx_languages');

        if ($this->db->affected_rows() > 0) {
            return true;
        }

        return false;
    }

}
