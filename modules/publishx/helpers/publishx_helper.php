<?php

function publishx_post_statuses()
{
    return [
        [
            'value' => '0',
            'name' => _l('publishx_published')
        ],
        [
            'value' => '1',
            'name' => _l('publishx_draft')
        ],
        [
            'value' => '2',
            'name' => _l('publishx_scheduled')
        ]
    ];
}

function publishx_handle_post_feature_image_upload($post_id)
{

    $success = [
        'success' => false
    ];
    $index = 'featured_image';

    if (isset($_FILES[$index]) && !empty($_FILES[$index]['name']) && _perfex_upload_error($_FILES[$index]['error'])) {
        set_alert('warning', _perfex_upload_error($_FILES[$index]['error']));
        return  [
            'success' => false
        ];
    }
    if (isset($_FILES[$index]['name']) && $_FILES[$index]['name'] != '') {
        $path = FCPATH . 'modules/publishx/uploads/posts/' . $post_id . '/';
        // Get the temp file path
        $tmpFilePath = $_FILES[$index]['tmp_name'];
        // Make sure we have a filepath
        if (!empty($tmpFilePath) && $tmpFilePath != '') {
            // Getting file extension
            $extension = strtolower(pathinfo($_FILES[$index]['name'], PATHINFO_EXTENSION));
            $allowed_extensions = [
                'jpg',
                'jpeg',
                'png',
                'gif',
                'svg',
            ];
            if (!in_array($extension, $allowed_extensions)) {
                set_alert('warning', 'Image extension not allowed.');
                return [
                    'success' => false
                ];
            }

            // Setup our new file path
            $filename = $_FILES[$index]['name'];
            $newFilePath = $path . $filename;
            _maybe_create_upload_path($path);
            // Upload the file into the company uploads dir
            if (move_uploaded_file($tmpFilePath, $newFilePath)) {
                update_option($index, $filename);
                $success = [
                    'success' => true,
                    'file_name' => $filename
                ];
            }
        }
    }

    return $success;
}

function publishx_handle_company_logo_upload()
{
    $logoIndex = ['blog_logo'];
    $success = false;

    foreach ($logoIndex as $logo) {
        $index = $logo;

        if (isset($_FILES[$index]) && !empty($_FILES[$index]['name']) && _perfex_upload_error($_FILES[$index]['error'])) {
            set_alert('warning', _perfex_upload_error($_FILES[$index]['error']));

            return false;
        }
        if (isset($_FILES[$index]['name']) && $_FILES[$index]['name'] != '') {
            $path = FCPATH . 'modules/publishx/uploads/';
            // Get the temp file path
            $tmpFilePath = $_FILES[$index]['tmp_name'];
            // Make sure we have a filepath
            if (!empty($tmpFilePath) && $tmpFilePath != '') {
                // Getting file extension
                $extension = strtolower(pathinfo($_FILES[$index]['name'], PATHINFO_EXTENSION));
                $allowed_extensions = [
                    'jpg',
                    'jpeg',
                    'png',
                    'gif',
                    'svg',
                ];

                if (!in_array($extension, $allowed_extensions)) {
                    set_alert('warning', 'Image extension not allowed.');

                    continue;
                }

                // Setup our new file path
                $filename = md5($logo . time()) . '.' . $extension;
                $newFilePath = $path . $filename;
                _maybe_create_upload_path($path);
                // Upload the file into the company uploads dir
                if (move_uploaded_file($tmpFilePath, $newFilePath)) {
                    update_option('publishx_blog_logo', $filename);
                    $success = true;
                }
            }
        }
    }


    return $success;
}

function publishx_handle_company_favicon_upload()
{
    $logoIndex = ['favicon_logo'];
    $success = false;

    foreach ($logoIndex as $logo) {
        $index = $logo;

        if (isset($_FILES[$index]) && !empty($_FILES[$index]['name']) && _perfex_upload_error($_FILES[$index]['error'])) {
            set_alert('warning', _perfex_upload_error($_FILES[$index]['error']));

            return false;
        }
        if (isset($_FILES[$index]['name']) && $_FILES[$index]['name'] != '') {
            $path = FCPATH . 'modules/publishx/uploads/';
            // Get the temp file path
            $tmpFilePath = $_FILES[$index]['tmp_name'];
            // Make sure we have a filepath
            if (!empty($tmpFilePath) && $tmpFilePath != '') {
                // Getting file extension
                $extension = strtolower(pathinfo($_FILES[$index]['name'], PATHINFO_EXTENSION));
                $allowed_extensions = [
                    'jpg',
                    'jpeg',
                    'png',
                    'gif',
                    'svg',
                    'ico'
                ];

                if (!in_array($extension, $allowed_extensions)) {
                    set_alert('warning', 'Image extension not allowed.');

                    continue;
                }

                // Setup our new file path
                $filename = md5($logo . time()) . '.' . $extension;
                $newFilePath = $path . $filename;
                _maybe_create_upload_path($path);
                // Upload the file into the company uploads dir
                if (move_uploaded_file($tmpFilePath, $newFilePath)) {
                    update_option('publishx_blog_favicon_logo', $filename);
                    $success = true;
                }
            }
        }
    }


    return $success;
}

function publishx_supported_blog_themes()
{
    return [
        [
            'id' => 'clean_blog',
            'title' => 'Clean Blog',
            'thumbnail' => substr(module_dir_url('publishx/uploads/cleanblog_thumbnail.png'), 0, -1)
        ],
        [
            'id' => 'mundana',
            'title' => 'Mundana',
            'thumbnail' => substr(module_dir_url('publishx/uploads/mundana-thumbnail.png'), 0, -1)
        ],
    ];
}