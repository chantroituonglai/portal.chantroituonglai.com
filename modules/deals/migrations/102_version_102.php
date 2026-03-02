<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_102 extends App_module_migration
{
    /**
     * @throws Exception
     */
    public function up()
    {
        $CI = &get_instance();
        // check if column exists in table
        if (!$CI->db->field_exists('rel_type', 'tbl_deals')) {
            $CI->db->query("ALTER TABLE `tbl_deals` ADD `rel_type` VARCHAR(30) NULL DEFAULT NULL AFTER `client_id`, ADD `rel_id` INT(11) NULL DEFAULT NULL AFTER `rel_type`;");
        }
    }

}
