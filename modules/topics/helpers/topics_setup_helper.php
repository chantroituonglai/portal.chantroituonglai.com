<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Generates a regular expression pattern to match the signature for requiring a file.
 */
function topics_require_signature($file)
{
    $basename = str_ireplace(['"', "'"], '', basename($file));
    return '#//topics:start:' . $basename . '([\s\S]*)//topics:end:' . $basename . '#';
}

/**
 * Generates the template for requiring a file in Topics module
 */
function topics_require_in_file_template($path)
{
    $template = "//topics:start:#filename\n//dont remove/change above line\nrequire_once(#path);\n//dont remove/change below line\n//topics:end:#filename";

    $template = str_ireplace('#filename', str_ireplace(['"', "'"], '', basename($path)), $template);
    $template = str_ireplace('#path', $path, $template);
    return $template;
}

/**
 * Writes content to a file
 */
function topics_file_put_contents($path, $content)
{
    @chmod($path, FILE_WRITE_MODE);
    if (!$fp = fopen($path, FOPEN_WRITE_CREATE_DESTRUCTIVE)) {
        return false;
    }
    flock($fp, LOCK_EX);
    fwrite($fp, $content);
    flock($fp, LOCK_UN);
    fclose($fp);
    @chmod($path, FILE_READ_MODE);
    return true;
}

/**
 * Requires a file into another file
 */
function topics_require_in_file($dest, $requirePath, $force = false, $position = false)
{
    if (!file_exists($dest)) {
        topics_file_put_contents($dest, "<?php defined('BASEPATH') or exit('No direct script access allowed');\n");
    }

    if (file_exists($dest)) {
        $content = file_get_contents($dest);

        $template = topics_require_in_file_template($requirePath);

        $exist = preg_match(topics_require_signature($requirePath), $content);
        if ($exist && !$force) {
            return;
        }
        $content = topics_unrequire_in_file($dest, $requirePath);

        if ($position !== false) {
            $content = substr_replace($content, $template . "\n", $position, 0);
        } else {
            $content = $content . $template;
        }

        topics_file_put_contents($dest, $content);
    }
}

/**
 * Removes the require statement of a file
 */
function topics_unrequire_in_file($dest, $requirePath)
{
    if (file_exists($dest)) {
        $content = file_get_contents($dest);
        $content = preg_replace(topics_require_signature($requirePath), '', $content);
        topics_file_put_contents($dest, $content);
        return $content;
    }
}

/**
 * Get topic master id from topic id
 * @param int $topic_id Topic ID
 * @return int|null Returns topic master id or null if not found
 */
function get_topic_master_id($topic_id) 
{
    $CI = &get_instance();
    
    if (!$topic_id) {
        return null;
    }

    // Get topic data first
    $topic = $CI->db->select('topicid')
                    ->where('id', $topic_id)
                    ->get(db_prefix() . 'topics')
                    ->row();

    if (!$topic) {
        return null;
    }

    // Get topic master data
    $topic_master = $CI->db->select('id')
                          ->where('topicid', $topic->topicid)
                          ->get(db_prefix() . 'topic_master')
                          ->row();

    return $topic_master ? $topic_master->id : null;
}

/**
 * Get full topic master data from topic id
 * @param int $topic_id Topic ID
 * @return object|null Returns topic master object or null if not found
 */
function get_topic_master_data($topic_id)
{
    $CI = &get_instance();
    
    if (!$topic_id) {
        return null;
    }

    // Get topic data first
    $topic = $CI->db->select('topicid')
                    ->where('id', $topic_id)
                    ->get(db_prefix() . 'topics')
                    ->row();

    if (!$topic) {
        return null;
    }

    // Get full topic master data
    $topic_master = $CI->db->where('topicid', $topic->topicid)
                          ->get(db_prefix() . 'topic_master')
                          ->row();

    if ($topic_master) {
        // Add additional data if needed
        $topic_master->topic_id = $topic_id;
        $topic_master->original_topicid = $topic->topicid;
    }

    return $topic_master;
}

/**
 * Check if topic has master data
 * @param int $topic_id Topic ID
 * @return bool Returns true if topic has master data
 */
function has_topic_master_data($topic_id)
{
    return get_topic_master_id($topic_id) !== null;
}
