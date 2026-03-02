<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_103 extends App_module_migration
{
    /**
     * @throws Exception
     */
    public function up()
    {
        $deal_send_email = [
            'type' => 'deal',
            'slug' => 'deal_send_email',
            'name' => 'Deal Send Email',
            'subject' => '{subject}',
            'message' => '{message}'
        ];
        create_email_template($deal_send_email['subject'], $deal_send_email['message'], $deal_send_email['type'], $deal_send_email['name'], $deal_send_email['slug']);

        $CI = &get_instance();
        // check if column exists in table
        if (!$CI->db->field_exists('rel_type', 'tbl_deals')) {
            $CI->db->query("ALTER TABLE `tbl_deals` ADD `rel_type` VARCHAR(30) NULL DEFAULT NULL AFTER `client_id`, ADD `rel_id` INT(11) NULL DEFAULT NULL AFTER `rel_type`;");
        }
    }

}
