<?php

defined('BASEPATH') or exit('No direct script access allowed');

$aColumns = [
    '(SELECT category_name FROM ' . db_prefix() . 'publishx_categories WHERE ' . db_prefix() . 'publishx_categories.id=' . db_prefix() . 'publishx_posts.category_id) as categoryName',
    'featured_image',
    'post_title',
    'post_slug',
    'author_id',
    'short_content',
    'status',
    'views',
    '(SELECT name FROM ' . db_prefix() . 'publishx_languages WHERE ' . db_prefix() . 'publishx_languages.id=' . db_prefix() . 'publishx_posts.language_id) as postLanguage',
    'created_at'
];

$sIndexColumn = 'id';
$sTable = db_prefix() . 'publishx_posts';

$join = [];
$where = [];

$result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, [
    'id'
]);

$output = $result['output'];
$rResult = $result['rResult'];


foreach ($rResult as $aRow) {
    $row = [];
    for ($i = 0; $i < count($aColumns); $i++) {

        $row[] = $aRow['id'];
        $row[] = '<img src="' . substr(module_dir_url('publishx/uploads/posts/' . $aRow['id'] . '/' . $aRow['featured_image']), 0, -1) . '" class="img img-responsive">';
        $row[] = $aRow['categoryName'];
        $row[] = $aRow['post_title'];
        $row[] = $aRow['short_content'];
        $row[] = $aRow['views'] ?? '0';
        $row[] = $aRow['postLanguage'];
        $row[] = $aRow['created_at'];
        $status = '';
        if ($aRow['status'] == 0) {
            $status = '<span class="label project-status-2" style="color:#2563eb;border:1px solid #a8c1f7;background: #f6f9fe;">' . _l('publishx_published') . '</span>';
        }
        if ($aRow['status'] == 1) {
            $status = '<span class="label project-status-5" style="color:#94a3b8;border:1px solid #d4dae3;background: #fbfcfc;">' . _l('publishx_draft') . '</span>';
        }
        if ($aRow['status'] == 2) {
            $status = '<span class="label project-status-5" style="color:#d78d59;border:1px solid #d2ac97;background: #fbfcfc;">' . _l('publishx_scheduled') . '</span>';
        }

        $row[] = $status;

        $row[] = '<a href="' . admin_url('staff/profile/' . $aRow['author_id']) . '">' . staff_profile_image($aRow['author_id'], [
                'staff-profile-image-small',
            ]) . '  ' . get_staff_full_name($aRow['author_id']) . '</a>';

        $options = '<div class="tw-flex tw-items-center tw-space-x-3">';
        $options .= '<a href="' . admin_url('publishx/post/' . $aRow['id']) . '" class="tw-text-neutral-500 hover:tw-text-neutral-700 focus:tw-text-neutral-700">
        <i class="fa-regular fa-pen-to-square fa-lg"></i>
    </a>';

        $options .= '<a href="' . admin_url('publishx/delete_post/' . $aRow['id']) . '"
    class="tw-mt-px tw-text-neutral-500 hover:tw-text-neutral-700 focus:tw-text-neutral-700 _delete">
        <i class="fa-regular fa-trash-can fa-lg"></i>
    </a>';

        $options .= '<a target="_blank" href="' . site_url('publishx/blog/post/' . $aRow['post_slug']) . '" class="tw-text-neutral-500 hover:tw-text-neutral-700 focus:tw-text-neutral-700">
        <i class="fa-regular fa-eye fa-lg"></i>
    </a>';

        $options .= '</div>';

        $row[] = $options;
    }

    $output['aaData'][] = $row;
}
