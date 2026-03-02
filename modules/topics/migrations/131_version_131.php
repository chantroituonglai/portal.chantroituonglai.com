<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_131 extends App_module_migration
{
    public function up()
    {
        $CI = &get_instance();
        
        // Check if columns exist before attempting to remove them
        $table = db_prefix() . 'topic_controllers';
        
        // Get all column values to preserve them as custom fields before dropping
        if ($CI->db->field_exists('emails', $table) || 
            $CI->db->field_exists('api_token', $table) || 
            $CI->db->field_exists('seo_task_sheet_id', $table)) {
            
            // Get all controllers and save their values
            $controllers = $CI->db->get($table)->result_array();
            
            // Load the custom fields model
            $CI->load->model('custom_fields_model');
            
            foreach ($controllers as $controller) {
                // Save values to custom fields (that were created in version_130)
                if (!empty($controller['emails'])) {
                    $email_field_id = $CI->db->select('id')
                                     ->where('slug', 'topic_controller_email_addresses')
                                     ->get(db_prefix() . 'customfields')
                                     ->row();
                    
                    if ($email_field_id) {
                        $CI->db->insert(db_prefix() . 'customfieldsvalues', [
                            'relid'   => $controller['id'],
                            'fieldid' => $email_field_id->id,
                            'fieldto' => 'topic_controller',
                            'value'   => $controller['emails']
                        ]);
                    }
                }
                
                if (!empty($controller['api_token'])) {
                    $api_field_id = $CI->db->select('id')
                                   ->where('slug', 'topic_controller_api_token')
                                   ->get(db_prefix() . 'customfields')
                                   ->row();
                    
                    if ($api_field_id) {
                        $CI->db->insert(db_prefix() . 'customfieldsvalues', [
                            'relid'   => $controller['id'],
                            'fieldid' => $api_field_id->id,
                            'fieldto' => 'topic_controller',
                            'value'   => $controller['api_token']
                        ]);
                    }
                }
                
                if (!empty($controller['seo_task_sheet_id'])) {
                    $seo_field_id = $CI->db->select('id')
                                   ->where('slug', 'topic_controller_seo_task_sheet_id')
                                   ->get(db_prefix() . 'customfields')
                                   ->row();
                    
                    if ($seo_field_id) {
                        $CI->db->insert(db_prefix() . 'customfieldsvalues', [
                            'relid'   => $controller['id'],
                            'fieldid' => $seo_field_id->id,
                            'fieldto' => 'topic_controller',
                            'value'   => $controller['seo_task_sheet_id']
                        ]);
                    }
                }
            }
        }
        
        // Remove the columns
        if ($CI->db->field_exists('emails', $table)) {
            $CI->db->query("ALTER TABLE {$table} DROP COLUMN emails");
            log_activity('Migration 131: Removed emails column from ' . $table);
        }
        
        if ($CI->db->field_exists('api_token', $table)) {
            $CI->db->query("ALTER TABLE {$table} DROP COLUMN api_token");
            log_activity('Migration 131: Removed api_token column from ' . $table);
        }
        
        if ($CI->db->field_exists('seo_task_sheet_id', $table)) {
            $CI->db->query("ALTER TABLE {$table} DROP COLUMN seo_task_sheet_id");
            log_activity('Migration 131: Removed seo_task_sheet_id column from ' . $table);
        }
        
        // Update tables schema version
        update_option('topics_version', '1.3.1');
        
        // Log the migration activity
        log_activity('Migration 131: Removed deprecated columns from tbltopic_controllers');
    }

    public function down()
    {
        $CI = &get_instance();
        $table = db_prefix() . 'topic_controllers';
        
        // Add back the columns
        if (!$CI->db->field_exists('emails', $table)) {
            $CI->db->query("ALTER TABLE {$table} ADD COLUMN emails TEXT NULL COMMENT ''");
        }
        
        if (!$CI->db->field_exists('api_token', $table)) {
            $CI->db->query("ALTER TABLE {$table} ADD COLUMN api_token VARCHAR(255) NULL COMMENT ''");
        }
        
        if (!$CI->db->field_exists('seo_task_sheet_id', $table)) {
            $CI->db->query("ALTER TABLE {$table} ADD COLUMN seo_task_sheet_id VARCHAR(100) NULL COMMENT ''");
        }
        
        // Restore custom field values to these columns
        $CI->load->model('custom_fields_model');
        $controllers = $CI->db->get($table)->result_array();
        
        foreach ($controllers as $controller) {
            // Get custom field values
            $email_field = $CI->db->select('id')
                                  ->where('slug', 'topic_controller_email_addresses')
                                  ->get(db_prefix() . 'customfields')
                                  ->row();
                                  
            $api_token_field = $CI->db->select('id')
                                      ->where('slug', 'topic_controller_api_token')
                                      ->get(db_prefix() . 'customfields')
                                      ->row();
                                      
            $seo_task_field = $CI->db->select('id')
                                     ->where('slug', 'topic_controller_seo_task_sheet_id')
                                     ->get(db_prefix() . 'customfields')
                                     ->row();
                                     
            if ($email_field) {
                $email_val = $CI->db->where('relid', $controller['id'])
                                    ->where('fieldid', $email_field->id)
                                    ->get(db_prefix() . 'customfieldsvalues')
                                    ->row();
                if ($email_val) {
                    $CI->db->where('id', $controller['id'])
                          ->update($table, ['emails' => $email_val->value]);
                }
            }
            
            if ($api_token_field) {
                $token_val = $CI->db->where('relid', $controller['id'])
                                   ->where('fieldid', $api_token_field->id)
                                   ->get(db_prefix() . 'customfieldsvalues')
                                   ->row();
                if ($token_val) {
                    $CI->db->where('id', $controller['id'])
                          ->update($table, ['api_token' => $token_val->value]);
                }
            }
            
            if ($seo_task_field) {
                $seo_val = $CI->db->where('relid', $controller['id'])
                                 ->where('fieldid', $seo_task_field->id)
                                 ->get(db_prefix() . 'customfieldsvalues')
                                 ->row();
                if ($seo_val) {
                    $CI->db->where('id', $controller['id'])
                          ->update($table, ['seo_task_sheet_id' => $seo_val->value]);
                }
            }
        }
        
        // Log the rollback activity
        log_activity('Migration 131 Rollback: Restored deprecated columns to tbltopic_controllers');
    }
} 