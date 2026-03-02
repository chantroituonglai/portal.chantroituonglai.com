<?php
/**
 * Update staff online status for topic
 * @param int $staff_id
 * @param string $topic_id
 * @return bool
 */
function update_staff_topic_online_status($staff_id, $topic_id) 
{
    $CI = & get_instance();
    
    if (!$CI->load->is_loaded('Topic_online_status_model')) {
        $CI->load->model('Topic_online_status_model');
    }
    
    return $CI->Topic_online_status_model->update_staff_online_status($staff_id, $topic_id);
}

/**
 * Get online staff for topic
 * @param string $topic_id
 * @param int $timeout Optional timeout in seconds
 * @return array
 */
function get_topic_online_staff($topic_id, $timeout = null) 
{
    $CI = & get_instance();
    
    if (!$CI->load->is_loaded('Topic_online_status_model')) {
        $CI->load->model('Topic_online_status_model');
    }
    
    return $CI->Topic_online_status_model->get_online_staff_for_topic($topic_id, $timeout);
}

/**
 * Remove staff online status
 * @param int $staff_id
 * @param string $topic_id Optional topic_id
 * @return bool
 */
function remove_staff_topic_online_status($staff_id, $topic_id = null)
{
    $CI = & get_instance();
    
    if (!$CI->load->is_loaded('Topic_online_status_model')) {
        $CI->load->model('Topic_online_status_model');
    }
    
    return $CI->Topic_online_status_model->remove_staff_online_status($staff_id, $topic_id);
}

/**
 * Check if staff is online in topic
 * @param int $staff_id
 * @param string $topic_id
 * @return bool
 */
function is_staff_online_in_topic($staff_id, $topic_id)
{
    $CI = & get_instance();
    
    if (!$CI->load->is_loaded('Topic_online_status_model')) {
        $CI->load->model('Topic_online_status_model');
    }
    
    $online_staff = $CI->Topic_online_status_model->get_online_staff_for_topic($topic_id);
    
    foreach ($online_staff as $staff) {
        if ($staff['staffid'] == $staff_id) {
            return true;
        }
    }
    
    return false;
}
