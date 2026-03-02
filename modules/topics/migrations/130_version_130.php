<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_130 extends App_module_migration
{
    public function up()
    {
        $CI = &get_instance();
        
        // Load the custom fields model
        $CI->load->model('custom_fields_model');
        
        // Register topic_controller as custom field type if it doesn't exist already
        if (!in_array('topic_controller', get_registered_custom_fields_types())) {
            register_custom_field_type('topic_controller', 'Topic Controller', 400);
        }
        
        // Add Email Addresses Custom Field (formerly emails field)
        $email_field = [
            'fieldto'            => 'topic_controller',
            'name'               => 'Email Addresses',
            'slug'               => 'topic_controller_email_addresses',
            'required'           => 0,
            'type'               => 'textarea',
            'options'            => '',
            'display_inline'     => 0,
            'field_order'        => 1,
            'active'             => 1,
            'show_on_pdf'        => 0,
            'show_on_ticket_form'=> 0,
            'only_admin'         => 0,
            'show_on_table'      => 0,
            'show_on_client_portal' => 0,
            'disalow_client_to_edit' => 0,
            'bs_column'          => 6
        ];
        $CI->custom_fields_model->add($email_field);
        
        // Add API Token Custom Field (formerly api_token field)
        $api_token_field = [
            'fieldto'            => 'topic_controller',
            'name'               => 'API Token',
            'slug'               => 'topic_controller_api_token',
            'required'           => 0,
            'type'               => 'text',
            'options'            => '',
            'display_inline'     => 0,
            'field_order'        => 2,
            'active'             => 1,
            'show_on_pdf'        => 0,
            'show_on_ticket_form'=> 0,
            'only_admin'         => 0,
            'show_on_table'      => 0,
            'show_on_client_portal' => 0,
            'disalow_client_to_edit' => 0,
            'bs_column'          => 6
        ];
        $CI->custom_fields_model->add($api_token_field);
        
        // Add SEO Task Sheet ID Custom Field (formerly seo_task_sheet_id field)
        $seo_task_sheet_field = [
            'fieldto'            => 'topic_controller',
            'name'               => 'SEO Task Sheet ID',
            'slug'               => 'topic_controller_seo_task_sheet_id',
            'required'           => 0,
            'type'               => 'text',
            'options'            => '',
            'display_inline'     => 0,
            'field_order'        => 3,
            'active'             => 1,
            'show_on_pdf'        => 0,
            'show_on_ticket_form'=> 0,
            'only_admin'         => 0,
            'show_on_table'      => 0,
            'show_on_client_portal' => 0,
            'disalow_client_to_edit' => 0,
            'bs_column'          => 6
        ];
        $CI->custom_fields_model->add($seo_task_sheet_field);
        
        // Update tables schema version
        update_option('topics_version', '1.3.0');
        
        // Log the migration activity
        log_activity('Migration 130: Added custom fields for topic_controller');
    }

    public function down()
    {
        $CI = &get_instance();
        
        // Load the custom fields model
        $CI->load->model('custom_fields_model');
        
        // Remove the custom fields for topic_controller
        $fields = $CI->db->where('fieldto', 'topic_controller')
                        ->get(db_prefix() . 'customfields')
                        ->result_array();
        
        foreach ($fields as $field) {
            $CI->custom_fields_model->delete($field['id']);
        }
        
        // Log the rollback activity
        log_activity('Migration 130 Rollback: Removed custom fields for topic_controller');
    }
} 