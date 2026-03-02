<?php

defined('BASEPATH') or exit('No direct script access allowed');

add_option('publishx_posts_per_page', '10');
add_option('publishx_blog_title', 'Your Blog');
add_option('publishx_blog_description', 'This is our blog');
add_option('publishx_show_on_client_side', '1');
add_option('publishx_display_on_post_author', '1');
add_option('publishx_google_analytics_code', '');
add_option('publishx_blog_logo', '');
add_option('publishx_blog_favicon_logo', '');
add_option('publishx_facebook_social_media_url', '');
add_option('publishx_instagram_social_media_url', '');
add_option('publishx_x_social_media_url', '');
add_option('publishx_youtube_social_media_url', '');
add_option('publishx_selected_blog_theme', 'clean_blog');
add_option('publishx_openai_key', '');

if (!$CI->db->table_exists(db_prefix() . 'publishx_categories')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . "publishx_categories` (
  `id` int(11) NOT NULL,
  `category_name` text,
  `created_at` datetime
) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');

    $CI->db->query('ALTER TABLE `' . db_prefix() . 'publishx_categories`
  ADD PRIMARY KEY (`id`);');

    $CI->db->query('ALTER TABLE `' . db_prefix() . 'publishx_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1');
}

//Status can be 0 - published 1- draft and 2-scheduled
//post parent id is used to edit same post in all languages
if (!$CI->db->table_exists(db_prefix() . 'publishx_posts')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . "publishx_posts` (
  `id` int(11) NOT NULL,
  `category_id` int(11),
  `author_id` int(11),
  `post_title` text,
  `post_slug` text,
  `short_content` text,
  `full_content` text,
  `meta_title` text,
  `meta_description` text,
  `meta_keywords` text,
  `featured_image` text,
  `language_id` int(11),
  `post_parent_id` int(11),
  `views` int(11),
  `status` int default 0,
  `scheduled` datetime,
  `created_at` datetime,
  `updated_at` datetime
) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');

    $CI->db->query('ALTER TABLE `' . db_prefix() . 'publishx_posts`
  ADD PRIMARY KEY (`id`);');

    $CI->db->query('ALTER TABLE `' . db_prefix() . 'publishx_posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1');
}

if (!$CI->db->table_exists(db_prefix() . 'publishx_languages')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . "publishx_languages` (
  `id` int(11) NOT NULL,
  `name` text,
  `created_at` datetime
) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');

    $CI->db->query('ALTER TABLE `' . db_prefix() . 'publishx_languages`
  ADD PRIMARY KEY (`id`);');

    $CI->db->query('ALTER TABLE `' . db_prefix() . 'publishx_languages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1');
}
