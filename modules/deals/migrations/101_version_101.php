<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_101 extends App_module_migration
{
    /**
     * @throws Exception
     */
    public function up()
    {
        $CI = &get_instance();
        // check if column exists in table
        if (!$CI->db->field_exists('deal_comment_id', db_prefix() . 'files')) {
            $CI->db->query("ALTER TABLE " . db_prefix() . "files ADD `deal_comment_id` INT NULL DEFAULT NULL AFTER `task_comment_id`;");
        }
    }

}
