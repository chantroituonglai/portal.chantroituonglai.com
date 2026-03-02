<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-12">
        <div class="panel_s">
          <div class="panel-body">
            <div class="_buttons mbottom20">
              <h4 class="no-margin"><i class="fa fa-cogs"></i> <?php echo _l('settings_title'); ?></h4>
            </div>

            <!-- Tab Navigation -->
            <ul class="nav nav-tabs nav-tabs-horizontal" id="pa-settings-tabs" role="tablist">
              <li role="presentation" class="active">
                <a href="#general" aria-controls="general" role="tab" data-toggle="tab">
                  <i class="fa fa-cog"></i> <?php echo _l('settings_general'); ?>
                </a>
              </li>
              <li role="presentation">
                <a href="#actions" aria-controls="actions" role="tab" data-toggle="tab">
                  <i class="fa fa-list"></i> <?php echo _l('settings_actions_by_module'); ?>
                </a>
              </li>
              <li role="presentation">
                <a href="#schema" aria-controls="schema" role="tab" data-toggle="tab">
                  <i class="fa fa-database"></i> <?php echo _l('settings_schema_learning'); ?>
                </a>
              </li>
              <li role="presentation">
                <a href="#health" aria-controls="health" role="tab" data-toggle="tab">
                  <i class="fa fa-heartbeat"></i> <?php echo _l('settings_health_check'); ?>
                </a>
              </li>
            </ul>

            <!-- Tab Content -->
            <div class="tab-content" id="pa-settings-content">
              
              <!-- General Settings Tab -->
              <div role="tabpanel" class="tab-pane active" id="general">
                <div class="row mtop20">
                  <div class="col-md-8">
                    <form method="post" action="<?php echo admin_url('project_agent/settings'); ?>">
                      <div class="form-group">
                        <label><?php echo _l('settings_ai_room_enabled'); ?></label>
                        <div class="checkbox">
                          <input type="checkbox" name="project_agent_ai_room_enabled" value="1" 
                                 <?php echo !empty($opts['project_agent_ai_room_enabled']) ? 'checked' : ''; ?>>
                          <label><?php echo _l('settings_ai_room_enabled_help'); ?></label>
                        </div>
                      </div>
                      
                      <div class="form-group">
                        <label><?php echo _l('settings_ai_provider'); ?></label>
                        <select name="project_agent_ai_provider" class="form-control">
                          <?php foreach ($providers as $provider): ?>
                            <option value="<?php echo html_escape($provider); ?>" 
                                    <?php echo $opts['project_agent_ai_provider'] === $provider ? 'selected' : ''; ?>>
                              <?php echo html_escape(ucfirst($provider)); ?>
                            </option>
                          <?php endforeach; ?>
                        </select>
                      </div>

                      <div class="form-group">
                        <label><?php echo _l('settings_system_prompt'); ?></label>
                        <textarea name="project_agent_system_prompt" class="form-control" rows="6" 
                                  placeholder="<?php echo _l('settings_system_prompt_placeholder'); ?>"><?php echo html_escape($opts['project_agent_system_prompt']); ?></textarea>
                      </div>

                      <div class="form-group">
                        <label><?php echo _l('settings_debug_enabled'); ?></label>
                        <div class="checkbox">
                          <input type="checkbox" name="project_agent_debug_enabled" value="1" 
                                 <?php echo !empty($opts['project_agent_debug_enabled']) ? 'checked' : ''; ?>>
                          <label><?php echo _l('settings_debug_enabled_help'); ?></label>
                        </div>
                      </div>

                      <div class="form-group">
                        <label><?php echo _l('settings_error_explainer_enabled'); ?></label>
                        <div class="checkbox">
                          <input type="checkbox" name="project_agent_error_explainer_enabled" value="1" 
                                 <?php echo !empty($opts['project_agent_error_explainer_enabled']) ? 'checked' : ''; ?>>
                          <label><?php echo _l('settings_error_explainer_enabled_help'); ?></label>
                        </div>
                      </div>

                      <div class="form-group">
                        <label><?php echo _l('settings_error_explainer_api_key'); ?></label>
                        <input type="text" name="project_agent_error_explainer_api_key" class="form-control" 
                               value="<?php echo html_escape($opts['project_agent_error_explainer_api_key']); ?>"
                               placeholder="<?php echo _l('settings_error_explainer_api_key_placeholder'); ?>">
                      </div>

                      <div class="row">
                        <div class="col-md-6">
                          <div class="form-group">
                            <label><?php echo _l('settings_auto_confirm_threshold'); ?></label>
                            <input type="number" name="project_agent_auto_confirm_threshold" class="form-control" 
                                   value="<?php echo html_escape($opts['project_agent_auto_confirm_threshold']); ?>">
                            <small class="text-muted"><?php echo _l('settings_auto_confirm_threshold_help'); ?></small>
                          </div>
                        </div>
                        <div class="col-md-6">
                          <div class="form-group">
                            <label><?php echo _l('settings_memory_retention_days'); ?></label>
                            <input type="number" name="project_agent_memory_retention_days" class="form-control" 
                                   value="<?php echo html_escape($opts['project_agent_memory_retention_days']); ?>">
                            <small class="text-muted"><?php echo _l('settings_memory_retention_days_help'); ?></small>
                          </div>
                        </div>
                      </div>

                      <div class="row">
                        <div class="col-md-6">
                          <div class="form-group">
                            <label><?php echo _l('settings_max_concurrent_sessions'); ?></label>
                            <input type="number" name="project_agent_max_concurrent_sessions" class="form-control" 
                                   value="<?php echo html_escape($opts['project_agent_max_concurrent_sessions']); ?>">
                            <small class="text-muted"><?php echo _l('settings_max_concurrent_sessions_help'); ?></small>
                          </div>
                        </div>
                        <div class="col-md-6">
                          <div class="form-group">
                            <label><?php echo _l('settings_default_risk_level'); ?></label>
                            <select name="project_agent_default_risk_level" class="form-control">
                              <option value="low" <?php echo $opts['project_agent_default_risk_level'] === 'low' ? 'selected' : ''; ?>><?php echo _l('risk_low'); ?></option>
                              <option value="medium" <?php echo $opts['project_agent_default_risk_level'] === 'medium' ? 'selected' : ''; ?>><?php echo _l('risk_medium'); ?></option>
                              <option value="high" <?php echo $opts['project_agent_default_risk_level'] === 'high' ? 'selected' : ''; ?>><?php echo _l('risk_high'); ?></option>
                            </select>
                            <small class="text-muted"><?php echo _l('settings_default_risk_level_help'); ?></small>
                          </div>
                        </div>
                      </div>

                      <div class="row">
                        <div class="col-md-4">
                          <div class="form-group">
                            <label><?php echo _l('settings_context_task_limit'); ?></label>
                            <input type="number" name="project_agent_context_task_limit" class="form-control" 
                                   value="<?php echo html_escape($opts['project_agent_context_task_limit']); ?>">
                            <small class="text-muted"><?php echo _l('settings_context_task_limit_help'); ?></small>
                          </div>
                        </div>
                        <div class="col-md-4">
                          <div class="form-group">
                            <label><?php echo _l('settings_context_milestone_limit'); ?></label>
                            <input type="number" name="project_agent_context_milestone_limit" class="form-control" 
                                   value="<?php echo html_escape($opts['project_agent_context_milestone_limit']); ?>">
                            <small class="text-muted"><?php echo _l('settings_context_milestone_limit_help'); ?></small>
                          </div>
                        </div>
                        <div class="col-md-4">
                          <div class="form-group">
                            <label><?php echo _l('settings_context_activity_limit'); ?></label>
                            <input type="number" name="project_agent_context_activity_limit" class="form-control" 
                                   value="<?php echo html_escape($opts['project_agent_context_activity_limit']); ?>">
                            <small class="text-muted"><?php echo _l('settings_context_activity_limit_help'); ?></small>
                          </div>
                        </div>
                      </div>

              <div class="form-group">
                        <button type="submit" class="btn btn-primary">
                          <i class="fa fa-save"></i> <?php echo _l('settings_save_settings'); ?>
                        </button>
                      </div>
                    </form>
                  </div>
                  
                  <div class="col-md-4">
                    <div class="panel panel-info">
                      <div class="panel-heading">
                        <h4><i class="fa fa-info-circle"></i> <?php echo _l('settings_ai_status'); ?></h4>
                      </div>
                      <div class="panel-body">
                        <?php if ($ai_available): ?>
                          <div class="alert alert-success">
                            <i class="fa fa-check-circle"></i> <?php echo _l('settings_ai_provider_connected'); ?>
                          </div>
                        <?php else: ?>
                          <div class="alert alert-warning">
                            <i class="fa fa-exclamation-triangle"></i> <?php echo _l('settings_ai_provider_not_available'); ?>
                          </div>
                        <?php endif; ?>
                        
                        <p><strong><?php echo _l('settings_version'); ?>:</strong> <?php echo $version ?: _l('settings_unknown'); ?></p>
                        <p><strong><?php echo _l('settings_database_version'); ?>:</strong> <?php echo $opts['project_agent_db_version'] ?: _l('settings_unknown'); ?></p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Actions by Module Tab -->
              <div role="tabpanel" class="tab-pane" id="actions">
                <div class="mtop20">
                  <div class="row mbot10">
                    <div class="col-md-3">
                      <select id="filter-entity" class="selectpicker" data-width="100%">
                        <option value=""><?php echo _l('actions_all_modules'); ?></option>
                        <option value="project"><?php echo _l('actions_projects'); ?></option>
                        <option value="task"><?php echo _l('actions_tasks'); ?></option>
                        <option value="invoice"><?php echo _l('actions_invoices'); ?></option>
                        <option value="estimate"><?php echo _l('actions_estimates'); ?></option>
                        <option value="expense"><?php echo _l('actions_expenses'); ?></option>
                        <option value="timesheet"><?php echo _l('actions_timesheets'); ?></option>
                        <option value="customer"><?php echo _l('actions_customers'); ?></option>
                        <option value="reminder"><?php echo _l('actions_reminders'); ?></option>
                        <option value="other"><?php echo _l('actions_other'); ?></option>
                      </select>
                    </div>
                    <div class="col-md-3">
                      <select id="filter-risk" class="selectpicker" data-width="100%">
                        <option value=""><?php echo _l('actions_all_risk_levels'); ?></option>
                        <option value="low"><?php echo _l('risk_low'); ?></option>
                        <option value="medium"><?php echo _l('risk_medium'); ?></option>
                        <option value="high"><?php echo _l('risk_high'); ?></option>
                      </select>
                    </div>
                    <div class="col-md-3">
                      <select id="filter-active" class="selectpicker" data-width="100%">
                        <option value=""><?php echo _l('actions_all_status'); ?></option>
                        <option value="1"><?php echo _l('actions_active'); ?></option>
                        <option value="0"><?php echo _l('actions_inactive'); ?></option>
                </select>
                    </div>
                    <div class="col-md-3">
                      <input type="text" id="filter-search" class="form-control" placeholder="<?php echo _l('actions_search_placeholder'); ?>">
                    </div>
                  </div>

                  <div class="table-responsive">
                    <table class="table table-striped table-actions" id="pa-actions-table">
                      <thead>
                        <tr>
                          <th style="width: 12%;"><?php echo _l('actions_id'); ?></th>
                          <th style="width: 18%;"><?php echo _l('actions_name'); ?></th>
                          <th style="width: 25%;"><?php echo _l('actions_description'); ?></th>
                          <th style="width: 6%;"><?php echo _l('actions_risk'); ?></th>
                          <th style="width: 6%;" class="text-center"><?php echo _l('actions_confirm'); ?></th>
                          <th style="width: 6%;" class="text-center"><?php echo _l('actions_active'); ?></th>
                          <th style="width: 8%;"><?php echo _l('actions_entity'); ?></th>
                          <th style="width: 15%;"><?php echo _l('actions_related_tables'); ?></th>
                          <th style="width: 10%;"><?php echo _l('actions_actions'); ?></th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php if (!empty($actions)) : 
                          // Group actions by entity type
                          $groupedActions = [];
                          foreach ($actions as $a) {
                            $entityType = isset($a['entity_type']) ? $a['entity_type'] : 'other';
                            if (!isset($groupedActions[$entityType])) {
                              $groupedActions[$entityType] = [];
                            }
                            $groupedActions[$entityType][] = $a;
                          }
                          
                          // Define group order and labels
                          $groupOrder = ['project', 'task', 'invoice', 'estimate', 'expense', 'timesheet', 'customer', 'reminder', 'other'];
                          $groupLabels = [
                            'project' => _l('actions_projects'),
                            'task' => _l('actions_tasks'), 
                            'invoice' => _l('actions_invoices'),
                            'estimate' => _l('actions_estimates'),
                            'expense' => _l('actions_expenses'),
                            'timesheet' => _l('actions_timesheets'),
                            'customer' => _l('actions_customers'),
                            'reminder' => _l('actions_reminders'),
                            'other' => _l('actions_other')
                          ];
                          
                          // Display grouped actions
                          foreach ($groupOrder as $groupKey):
                            if (!isset($groupedActions[$groupKey]) || empty($groupedActions[$groupKey])) {
                              continue;
                            }
                            
                            $groupLabel = isset($groupLabels[$groupKey]) ? $groupLabels[$groupKey] : ucfirst($groupKey);
                            $groupCount = count($groupedActions[$groupKey]);
                        ?>
                          <tr class="group-header" style="background-color: #f8f9fa; font-weight: bold;">
                            <td colspan="9" style="padding: 12px 8px; border-top: 2px solid #dee2e6;">
                              <i class="fa fa-folder-open text-primary"></i> 
                              <?php echo html_escape($groupLabel); ?> 
                              <span class="badge badge-secondary"><?php echo $groupCount; ?></span>
                            </td>
                          </tr>
                          <?php foreach ($groupedActions[$groupKey] as $a): ?>
                            <tr data-risk="<?php echo html_escape($a['risk_level']); ?>" data-active="<?php echo !empty($a['is_active']) ? '1' : '0'; ?>" data-entity="<?php echo html_escape($groupKey); ?>">
                              <td><code><?php echo html_escape($a['action_id']); ?></code></td>
                              <td><?php echo html_escape($a['name']); ?></td>
                              <td class="small text-muted" style="max-width:420px; white-space:normal;">
                                <?php echo html_escape($a['description']); ?>
                              </td>
                              <td>
                                <span class="badge badge-<?php echo $a['risk_level']==='high'?'danger':($a['risk_level']==='medium'?'warning':'success'); ?>">
                                  <?php echo ucfirst($a['risk_level']); ?>
                                </span>
                              </td>
                              <td class="text-center"> <?php echo !empty($a['requires_confirm']) ? '<i class="fa fa-check text-warning"></i>' : '<i class="fa fa-times text-muted"></i>'; ?> </td>
                              <td class="text-center">
                                <label class="switch" title="Toggle active">
                                  <input type="checkbox" class="js-toggle" data-id="<?php echo $a['action_id']; ?>" <?php echo !empty($a['is_active']) ? 'checked' : ''; ?>>
                                  <span class="slider round"></span>
                                </label>
                              </td>
                              <td class="text-center">
                                <?php 
                                  $entityType = isset($a['entity_type']) ? $a['entity_type'] : '';
                                  if ($entityType): ?>
                                    <span class="badge badge-info"><?php echo html_escape($entityType); ?></span>
                                  <?php else: ?>
                                    <span class="text-muted">—</span>
                                  <?php endif; ?>
                              </td>
                              <td class="small" style="max-width:360px; white-space:normal;">
                                <?php 
                                  $relatedTables = isset($a['related_tables']) ? $a['related_tables'] : '';
                                  $tables = [];
                                  if (is_string($relatedTables) && $relatedTables !== '') {
                                    $tmp = json_decode($relatedTables, true);
                                    if (json_last_error() === JSON_ERROR_NONE && is_array($tmp)) { 
                                      $tables = $tmp; 
                                    }
                                  } elseif (is_array($relatedTables)) {
                                    $tables = $relatedTables;
                                  }
                                ?>
                                <?php if (empty($tables)): ?>
                                  <span class="text-muted">—</span>
                                <?php else: ?>
                                  <?php 
                                    $tableLabels = [];
                                    foreach ($tables as $table) {
                                      $tableLabels[] = '<span class="label label-default">' . html_escape($table) . '</span>';
                                    }
                                    echo implode(' ', $tableLabels);
                                  ?>
                                <?php endif; ?>
                              </td>
                              <td>
                                <button class="btn btn-default btn-sm js-edit"
                                        data-id="<?php echo $a['action_id']; ?>"
                                        data-name="<?php echo html_escape($a['name']); ?>"
                                        data-prompt="<?php echo html_escape(isset($a['prompt_override'])?$a['prompt_override']:''); ?>"
                                        data-mapping='<?php echo html_escape(isset($a['param_mapping'])?$a['param_mapping']:'{}'); ?>'
                                        data-entity="<?php echo html_escape(isset($a['entity_type'])?$a['entity_type']:''); ?>"
                                        data-related='<?php echo html_escape(isset($a['related_tables'])?$a['related_tables']:'[]'); ?>'>
                                  <i class="fa fa-pencil"></i> <?php echo _l('actions_edit'); ?>
                                </button>
                                <button class="btn btn-success btn-sm js-learn" data-id="<?php echo $a['action_id']; ?>">
                                  <i class="fa fa-graduation-cap"></i> Learn
                                </button>
                              </td>
                            </tr>
                          <?php endforeach; ?>
                        <?php endforeach; ?>
                        <?php endif; ?>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>

              <!-- Schema Learning Tab -->
              <div role="tabpanel" class="tab-pane" id="schema">
                <div class="mtop20">
              <div class="row">
                    <div class="col-md-8">
                      <div class="panel panel-primary">
                        <div class="panel-heading">
                          <h4><i class="fa fa-database"></i> <?php echo _l('schema_learning_title'); ?></h4>
                        </div>
                        <div class="panel-body">
                          <p class="text-muted"><?php echo _l('schema_learning_description'); ?></p>

                  <div class="form-group">
                            <label><?php echo _l('schema_select_actions'); ?></label>
                            <select id="schema-actions" class="selectpicker" data-width="100%" data-live-search="true" multiple data-actions-box="true" title="<?php echo _l('schema_all_active_actions'); ?>">
                              <?php if (!empty($actions)): foreach ($actions as $a): ?>
                                <option value="<?php echo html_escape($a['action_id']); ?>"><?php echo html_escape($a['name']); ?> (<?php echo html_escape($a['action_id']); ?>)</option>
                              <?php endforeach; endif; ?>
                    </select>
                          </div>

                          <div class="form-group">
                            <div class="custom-control custom-checkbox" style="display:inline-block; margin-right:10px;">
                              <input type="checkbox" id="force-schema-overwrite" class="custom-control-input">
                              <label class="custom-control-label" for="force-schema-overwrite">Force overwrite schema</label>
                            </div>
                            <button type="button" class="btn btn-primary" id="learn-actions-btn">
                              <i class="fa fa-graduation-cap"></i> <?php echo _l('schema_learn_selected_actions'); ?>
                            </button>
                            <button type="button" class="btn btn-info" id="learn-related-btn">
                              <i class="fa fa-link"></i> <?php echo _l('schema_learn_all_active_actions'); ?>
                            </button>
                          </div>

                          <div id="schema-learning-progress" class="hidden">
                            <div class="progress">
                              <div class="progress-bar progress-bar-striped active" role="progressbar" style="width: 0%"></div>
                            </div>
                            <p class="text-center text-muted"><?php echo _l('schema_learning_progress'); ?></p>
                          </div>

                          <div id="schema-learning-result" class="hidden">
                            <div class="alert alert-success">
                              <h5><i class="fa fa-check-circle"></i> <?php echo _l('schema_learning_complete'); ?></h5>
                              <div id="learning-summary"></div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                    
                    <div class="col-md-4">
                      <div class="panel panel-info">
                        <div class="panel-heading">
                          <h4><i class="fa fa-info-circle"></i> <?php echo _l('schema_learning_info'); ?></h4>
                        </div>
                        <div class="panel-body">
                          <p><strong><?php echo _l('schema_available_tables'); ?>:</strong> <?php echo count($db_tables); ?></p>
                          <p><strong><?php echo _l('schema_actions_with_mappings'); ?>:</strong> <span id="mapped-actions-count"><?php 
                            $mappedCount = 0;
                            if (!empty($actions)) {
                              foreach ($actions as $a) {
                                if (!empty($a['related_tables']) && $a['related_tables'] !== '[]' && $a['related_tables'] !== 'null') {
                                  $mappedCount++;
                                }
                              }
                            }
                            echo $mappedCount;
                          ?></span></p>
                          
                          <hr>
                          
                          <h6><?php echo _l('schema_how_it_works'); ?></h6>
                          <ol class="small">
                            <li><?php echo _l('schema_step1'); ?></li>
                            <li><?php echo _l('schema_step2'); ?></li>
                            <li><?php echo _l('schema_step3'); ?></li>
                            <li><?php echo _l('schema_step4'); ?></li>
                          </ol>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Health Check Tab -->
              <div role="tabpanel" class="tab-pane" id="health">
                <div class="mtop20">
                  <div class="row">
                    <div class="col-md-8">
                      <div class="panel panel-default">
                        <div class="panel-heading">
                          <h4><i class="fa fa-heartbeat"></i> <?php echo _l('health_system_health_check'); ?></h4>
                        </div>
                        <div class="panel-body">
                          <button type="button" class="btn btn-primary" id="run-health-check">
                            <i class="fa fa-play"></i> <?php echo _l('health_run_health_check'); ?>
                          </button>
                          
                          <div id="health-check-results" class="mtop20 hidden">
                            <div class="row">
                              <div class="col-md-6">
                                <h5><?php echo _l('health_database_tables'); ?></h5>
                                <div id="health-tables"></div>
                              </div>
                              <div class="col-md-6">
                                <h5><?php echo _l('health_ai_providers'); ?></h5>
                                <div id="health-ai"></div>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                    
                    <div class="col-md-4">
                      <div class="panel panel-warning">
                        <div class="panel-heading">
                          <h4><i class="fa fa-warning"></i> <?php echo _l('health_quick_actions'); ?></h4>
                        </div>
                        <div class="panel-body">
                          <button type="button" class="btn btn-warning btn-block" id="toggle-db-trace">
                            <i class="fa fa-bug"></i> <?php echo _l('health_toggle_db_trace'); ?>
                          </button>
                          <button type="button" class="btn btn-info btn-block" id="test-context">
                            <i class="fa fa-flask"></i> <?php echo _l('health_test_context'); ?>
                          </button>
                          <button type="button" class="btn btn-success btn-block" id="refresh-status">
                            <i class="fa fa-refresh"></i> <?php echo _l('health_refresh_status'); ?>
                          </button>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Prompt Modal -->
<div class="modal fade" id="actionPromptModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fa fa-pencil"></i> <?php echo _l('modal_edit_action_configuration'); ?></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      </div>
      <div class="modal-body">
        <form id="actionPromptForm">
          <input type="hidden" id="ap_action_id" name="action_id" value="">
          <div class="form-group">
            <label><?php echo _l('modal_action'); ?></label>
            <input type="text" class="form-control" id="ap_action_name" disabled>
          </div>
          <div class="row">
            <div class="col-sm-6">
              <div class="form-group">
                <label><?php echo _l('modal_entity_type'); ?></label>
                <select id="ap_entity_type" class="selectpicker" data-width="100%">
                  <option value=""><?php echo _l('modal_entity_none'); ?></option>
                  <option value="project"><?php echo _l('actions_projects'); ?></option>
                  <option value="task"><?php echo _l('actions_tasks'); ?></option>
                  <option value="invoice"><?php echo _l('actions_invoices'); ?></option>
                  <option value="estimate"><?php echo _l('actions_estimates'); ?></option>
                  <option value="expense"><?php echo _l('actions_expenses'); ?></option>
                  <option value="timesheet"><?php echo _l('actions_timesheets'); ?></option>
                  <option value="customer"><?php echo _l('actions_customers'); ?></option>
                  <option value="reminder"><?php echo _l('actions_reminders'); ?></option>
                  <option value="other"><?php echo _l('actions_other'); ?></option>
                </select>
              </div>
            </div>
            <div class="col-sm-6">
              <div class="form-group">
                <label><?php echo _l('modal_related_tables'); ?></label>
                <select id="ap_related_tables" class="selectpicker" data-width="100%" data-live-search="true" multiple data-actions-box="true">
                  <?php if (!empty($db_tables)): foreach ($db_tables as $t): ?>
                    <option value="<?php echo html_escape($t); ?>"><?php echo html_escape($t); ?></option>
                  <?php endforeach; endif; ?>
                </select>
              </div>
            </div>
          </div>
          <div class="form-group">
            <label><?php echo _l('modal_prompt_override'); ?></label>
            <textarea class="form-control" id="ap_prompt" name="prompt_override" rows="6" placeholder="<?php echo _l('modal_prompt_override_placeholder'); ?>"></textarea>
          </div>
          <div class="form-group">
            <label><?php echo _l('modal_parameter_mapping'); ?></label>
            <div class="small text-muted mtop5 mbot5"><?php echo _l('modal_parameter_mapping_help'); ?></div>
            <div id="ap_mapping_container" class="table-responsive" style="max-height:320px; overflow:auto; border:1px solid #e9ecef; border-radius:6px;">
              <table class="table table-sm table-striped mb-0">
                <thead>
                  <tr>
                    <th style="width:18%"><?php echo _l('modal_parameter'); ?></th>
                    <th style="width:10%"><?php echo _l('modal_type'); ?></th>
                    <th style="width:10%"><?php echo _l('actions_required'); ?></th>
                    <th style="width:22%"><?php echo _l('modal_source'); ?></th>
                    <th><?php echo _l('modal_default_value'); ?></th>
                  </tr>
                </thead>
                <tbody id="ap_mapping_rows"></tbody>
              </table>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal"><?php echo _l('modal_close'); ?></button>
        <button type="button" class="btn btn-primary" id="ap_save"><?php echo _l('modal_save'); ?></button>
      </div>
    </div>
  </div>
</div>

<style>
.switch { position: relative; display: inline-block; width: 42px; height: 22px; }
.switch input {opacity:0;width:0;height:0}
.slider { position:absolute; cursor:pointer; top:0; left:0; right:0; bottom:0; background:#d1d5db; transition:.2s; }
.slider:before{ position:absolute; content:""; height:18px;width:18px; left:2px; bottom:2px; background:white; transition:.2s; }
input:checked + .slider{ background:#0d6efd; }
input:checked + .slider:before{ transform: translateX(20px); }
.slider.round{ border-radius:22px; }
.slider.round:before{ border-radius:50%; }
.label { display: inline-block; padding: 2px 6px; font-size: 11px; font-weight: 500; line-height: 1; color: #fff; text-align: center; white-space: nowrap; vertical-align: baseline; border-radius: 3px; margin: 1px; }
.label-default { background-color: #6c757d; }
.badge { display: inline-block; padding: 4px 8px; font-size: 11px; font-weight: 500; line-height: 1; color: #fff; text-align: center; white-space: nowrap; vertical-align: baseline; border-radius: 4px; }
.badge-info { background-color: #17a2b8; }
.badge-secondary { background-color: #6c757d; }
.group-header { 
  background-color: #f8f9fa !important; 
  font-weight: bold !important; 
  border-top: 2px solid #dee2e6 !important;
}
.group-header td { 
  padding: 12px 8px !important; 
  border-top: 2px solid #dee2e6 !important;
  font-size: 14px;
}
.group-header i { margin-right: 8px; }
.nav-tabs .nav-link { border-radius: 0; }
.nav-tabs .nav-link.active { background-color: #f8f9fa; border-color: #dee2e6 #dee2e6 #f8f9fa; }
</style>

<?php init_tail(); ?>
<script>
(function(){
  var $ = window.jQuery; if(!$) return;
  
  // Tab switching
  $('#pa-settings-tabs a').on('click', function (e) {
    e.preventDefault();
    $(this).tab('show');
  });

  // Actions filtering (same as in actions view)
  function applyFilters(){
    var entity = ($('#filter-entity').val()||'').toLowerCase();
    var risk = ($('#filter-risk').val()||'').toLowerCase();
    var active = String($('#filter-active').val()||'');
    var q = ($('#filter-search').val()||'').toLowerCase();
    
    $('#pa-actions-table tbody tr').each(function(){
      var $tr = $(this);
      var ok = true;
      
      if ($tr.hasClass('group-header')) {
        var groupVisible = false;
        $tr.nextUntil('.group-header').each(function(){
          var $child = $(this);
          if ($child.hasClass('group-header')) return false;
          
          var childOk = true;
          if (entity && String($child.data('entity'))!==entity) childOk=false;
          if (risk && String($child.data('risk'))!==risk) childOk=false;
          if (active!=='' && String($child.data('active'))!==active) childOk=false;
          if (q){ var text = $child.text().toLowerCase(); if (text.indexOf(q)===-1) childOk=false; }
          
          if (childOk) groupVisible = true;
        });
        $tr.toggle(groupVisible);
        return;
      }
      
      if (entity && String($tr.data('entity'))!==entity) ok=false;
      if (risk && String($tr.data('risk'))!==risk) ok=false;
      if (active!=='' && String($tr.data('active'))!==active) ok=false;
      if (q){ var text = $tr.text().toLowerCase(); if (text.indexOf(q)===-1) ok=false; }
      
      $tr.toggle(ok);
    });
  }
  $('#filter-entity,#filter-risk,#filter-active').on('changed.bs.select', applyFilters);
  $('#filter-search').on('input', applyFilters);

  // Learn for single action (row button)
  $(document).on('click', '.js-learn', function(){
    var id = $(this).data('id');
    if (!id) return;
    var $btn = $(this); var old = $btn.html();
    $btn.prop('disabled', true).html('<i class="fa fa-spin fa-spinner"></i>');
    $('#schema-learning-progress').removeClass('hidden');
    $('#schema-learning-result').addClass('hidden');
    $.post('<?php echo admin_url('project_agent/learn_related_tables'); ?>', { action_ids: JSON.stringify([id]), force_schema: ($('#force-schema-overwrite').is(':checked')?1:0) })
      .done(function(resp){
        $('#schema-learning-progress').addClass('hidden');
        if (resp && resp.success) {
          var pretty = '';
          try { pretty = JSON.stringify(resp, null, 2); } catch(e) { pretty = String(resp); }
          $('#learning-summary').html('<p>Learned related tables for action: <code>'+id+'</code></p><pre class="small" style="max-height:260px; overflow:auto;">'+$('<div>').text(pretty).html()+'</pre>');
          $('#schema-learning-result').removeClass('hidden');
          updateMappedActionsCount();
          // If mapping was returned, update the in-modal mapping rows & inline data
          try {
            if (resp.mappings && resp.mappings[id]){
              var btn = document.querySelector('.js-edit[data-id="'+id+'"]');
              if (btn) { btn.setAttribute('data-mapping', JSON.stringify(resp.mappings[id])); }
              if ($('#actionPromptModal').is(':visible')){
                $.get('<?php echo admin_url('project_agent/get_action_schema'); ?>', { action_id: id })
                  .done(function(r){ try{ if(typeof r==='string') r=JSON.parse(r);}catch(e){}
                         if (r && r.success && r.schema) { renderMappingRows(id, r.schema); } });
              }
            }
          } catch(e){}
          // If schema was returned, rerender immediately with it
          try {
            if (resp.schemas && resp.schemas[id]){
              renderMappingRows(id, resp.schemas[id]);
            }
          } catch(e){}
        } else {
          alert('<?php echo _l('msg_learning_failed'); ?>: ' + (resp && (resp.error || resp.message) || '<?php echo _l('msg_learning_failed_unknown'); ?>'));
        }
      })
      .fail(function(){
        $('#schema-learning-progress').addClass('hidden');
        alert('<?php echo _l('msg_learning_failed'); ?>');
      })
      .always(function(){ $btn.prop('disabled', false).html(old); });
  });

  // Toggle active
  $(document).on('change', '.js-toggle', function(){
    var id = $(this).data('id'); var on = $(this).is(':checked') ? 1 : 0;
    $.post('<?php echo admin_url('project_agent/toggle_action'); ?>', { action_id:id, is_active:on })
      .done(function(){
        // Update mapped actions count after toggle
        updateMappedActionsCount();
      })
      .fail(function(){ alert('<?php echo _l('msg_update_failed'); ?>'); });
  });

  // Learn Selected Actions -> related tables
  $('#learn-actions-btn').on('click', function(){
    var selected = $('#schema-actions').val() || [];
    if (selected.length === 0) {
      alert('<?php echo _l('msg_select_at_least_one_action'); ?>');
      return;
    }
    $('#schema-learning-progress').removeClass('hidden');
    $('#schema-learning-result').addClass('hidden');
    $.post('<?php echo admin_url('project_agent/learn_related_tables'); ?>', { action_ids: JSON.stringify(selected), force_schema: ($('#force-schema-overwrite').is(':checked')?1:0) })
      .done(function(resp){
        $('#schema-learning-progress').addClass('hidden');
        if (resp.success) {
          var pretty = '';
          try { pretty = JSON.stringify(resp, null, 2); } catch(e) { pretty = String(resp); }
          $('#learning-summary').html('<p>Successfully learned related tables for '+selected.length+' selected actions</p><pre class="small" style="max-height:260px; overflow:auto;">'+$('<div>').text(pretty).html()+'</pre>');
          $('#schema-learning-result').removeClass('hidden');
          updateMappedActionsCount();
        } else {
          alert('Learning failed: ' + (resp.error || 'Unknown error'));
        }
      })
      .fail(function(){
        $('#schema-learning-progress').addClass('hidden');
        alert('<?php echo _l('msg_learning_failed'); ?>');
      });
  });

  // Learn Related Tables for all active actions
  $('#learn-related-btn').on('click', function(){
    $('#schema-learning-progress').removeClass('hidden');
    $('#schema-learning-result').addClass('hidden');
    
    $.post('<?php echo admin_url('project_agent/learn_related_tables'); ?>', { action_ids: [], force_schema: ($('#force-schema-overwrite').is(':checked')?1:0) })
      .done(function(resp){
        $('#schema-learning-progress').addClass('hidden');
        if (resp.success) {
          var pretty = '';
          try { pretty = JSON.stringify(resp, null, 2); } catch(e) { pretty = String(resp); }
          $('#learning-summary').html('<p>Successfully learned related tables for all active actions</p><pre class="small" style="max-height:260px; overflow:auto;">'+$('<div>').text(pretty).html()+'</pre>');
          $('#schema-learning-result').removeClass('hidden');
          // Update mapped actions count
          updateMappedActionsCount();
        } else {
          alert('Related tables learning failed: ' + (resp.error || 'Unknown error'));
        }
      })
      .fail(function(){
        $('#schema-learning-progress').addClass('hidden');
        alert('Related tables learning failed');
      });
  });

  // Function to update mapped actions count
  function updateMappedActionsCount() {
    var count = 0;
    $('#pa-actions-table tbody tr').each(function(){
      var $tr = $(this);
      if (!$tr.hasClass('group-header')) {
        var relatedTables = $tr.find('td:nth-child(8)').text();
        if (relatedTables && relatedTables.trim() !== '—') {
          count++;
        }
      }
    });
    $('#mapped-actions-count').text(count);
  }

  // Edit prompt
  $(document).on('click', '.js-edit', function(){
    var id = $(this).data('id'); var nm = $(this).data('name'); var pr = $(this).data('prompt')||'';
    $('#ap_action_id').val(id); $('#ap_action_name').val(nm); $('#ap_prompt').val(pr);
    // Prefill context queries
    try {
      var ctx = $(this).attr('data-ctxq')||''; var pretty='';
      if (ctx) { var obj = JSON.parse(ctx); pretty = JSON.stringify(obj, null, 2); }
      $('#ap_ctxq').text(pretty);
    } catch(e){ $('#ap_ctxq').text(''); }
    // Prefill entity
    var ent = $(this).data('entity')||'';
    $('#ap_entity_type').selectpicker('val', ent);
    // Prefill related tables
    var rel = $(this).attr('data-related')||'[]';
    try { rel = JSON.parse(rel); } catch(e){ rel = []; }
    $('#ap_related_tables').selectpicker('val', rel);
    // Load schema for mapping UI
    $('#ap_mapping_rows').html('<tr><td colspan="5" class="text-center text-muted">Loading schema…</td></tr>');
    $.get('<?php echo admin_url('project_agent/get_action_schema'); ?>', { action_id: id })
      .done(function(resp){ try{ if(typeof resp==='string') resp=JSON.parse(resp);}catch(e){}
            if (resp && resp.success && resp.schema) { renderMappingRows(id, resp.schema); } else { $('#ap_mapping_rows').html('<tr><td colspan="5" class="text-danger">Failed to load schema</td></tr>'); } })
      .fail(function(){ $('#ap_mapping_rows').html('<tr><td colspan="5" class="text-danger">Failed to load schema</td></tr>'); });
    $('#actionPromptModal').modal('show');
  });
  $('#ap_save').on('click', function(){
    var data = {
      action_id: $('#ap_action_id').val(),
      prompt_override: $('#ap_prompt').val(),
      param_mapping: JSON.stringify(collectMappingRows()),
      entity_type: $('#ap_entity_type').val() || '',
      related_tables: $('#ap_related_tables').val() || []
    };
    $.post('<?php echo admin_url('project_agent/save_action_config'); ?>', data)
      .done(function(resp){ try{ if(typeof resp==='string') resp=JSON.parse(resp);}catch(e){}
             if(resp && resp.success){ $('#actionPromptModal').modal('hide'); location.reload(); } else { alert('<?php echo _l('msg_save_failed'); ?>'); } })
      .fail(function(){ alert('<?php echo _l('msg_save_failed'); ?>'); });
  });

  function mappingSources(){
    return [
      {value:'', label:'— None —'},
      {value:'context.project_id', label:'Context: Project ID'},
      {value:'context.user_id', label:'Context: User ID'},
      {value:'context.session_id', label:'Context: Session ID'},
      {value:'static', label:'Static value'},
      {value:'related.project', label:'Related: Project'},
      {value:'related.customer', label:'Related: Customer'},
      {value:'related.task', label:'Related: Task'}
    ];
  }

  // Render mapping rows from schema
  function renderMappingRows(actionId, schema){
    var rows = '';
    var props = (schema && schema.properties) ? schema.properties : {};
    var required = Array.isArray(schema.required) ? schema.required : [];
    // Try to fetch existing mapping for prefill
    var map = {};
    try {
      var btn = document.querySelector('.js-edit[data-id="'+actionId+'"]');
      var json = btn && btn.getAttribute('data-mapping');
      if (json) map = JSON.parse(json);
    } catch(e) {}
    Object.keys(props).forEach(function(name){
      var ps = props[name]||{}; var type = ps.type||'string'; var isReq = required.indexOf(name)!==-1;
      var cur = (map && map[name]) ? map[name] : {};
      var src = cur.source || (name==='project_id'?'context.project_id':'');
      var def = (cur.default!==undefined && cur.default!==null) ? cur.default : '';
      rows += '<tr>'
           +    '<td><code>'+escapeHtml(name)+'</code></td>'
           +    '<td>'+escapeHtml(type)+'</td>'
           +    '<td>'+(isReq?'<span class="badge badge-danger">Required</span>':'<span class="badge badge-secondary">Optional</span>')+'</td>'
           +    '<td>'+renderSourceSelect(name, src)+'</td>'
           +    '<td>'+renderDefaultInput(name, type, def)+'</td>'
           +  '</tr>';
    });
    $('#ap_mapping_rows').html(rows);
  }

  function renderSourceSelect(name, current){
    var opts = mappingSources();
    var html = '<select class="form-control form-control-sm ap-map-src" data-name="'+escapeHtml(name)+'">';
    for (var i=0;i<opts.length;i++){
      var o=opts[i]; html += '<option value="'+o.value+'"'+(o.value===current?' selected':'')+'>'+o.label+'</option>';
    }
    html += '</select>';
    return html;
  }
  function renderDefaultInput(name, type, def){
    var inputType = (type==='integer' || type==='number') ? 'number' : 'text';
    return '<input type="'+inputType+'" class="form-control form-control-sm ap-map-def" data-name="'+escapeHtml(name)+'" value="'+escapeHtml(def)+'" placeholder="Default or value">';
  }
  function collectMappingRows(){
    var map = {};
    $('#ap_mapping_rows tr').each(function(){
      var $tr = $(this); var name = $tr.find('.ap-map-src').data('name');
      var src = $tr.find('.ap-map-src').val(); var def = $tr.find('.ap-map-def').val();
      if (src || (def!=='' && def!==null)) { map[name] = { source: src || '', default: def }; }
    });
    return map;
  }

  function escapeHtml(text) {
    var map = {
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, function(m) { return map[m]; });
  }

  // Health Check
  $('#run-health-check').on('click', function(){
    $.get('<?php echo admin_url('project_agent/health_ajax'); ?>')
      .done(function(resp){
        if (resp.success) {
          var tablesHtml = '';
          for (var key in resp.tables) {
            var table = resp.tables[key];
            var status = table.exists ? 'success' : 'danger';
            var icon = table.exists ? 'check' : 'times';
            tablesHtml += '<div class="alert alert-' + status + ' alert-sm">' +
                         '<i class="fa fa-' + icon + '"></i> ' + table.table + 
                         (table.count !== null ? ' (' + table.count + ' records)' : '') +
                         '</div>';
          }
          $('#health-tables').html(tablesHtml);
          $('#health-check-results').removeClass('hidden');
        }
      })
      .fail(function(){
        alert('<?php echo _l('msg_health_check_failed'); ?>');
      });
  });

  // Quick Actions
  $('#toggle-db-trace').on('click', function(){
    $.post('<?php echo admin_url('project_agent/db_trace_toggle'); ?>', { mode: 'toggle' })
      .done(function(resp){
        alert('DB trace ' + (resp.enabled ? '<?php echo _l('msg_db_trace_enabled'); ?>' : '<?php echo _l('msg_db_trace_disabled'); ?>'));
      });
  });

  $('#test-context').on('click', function(){
    $.post('<?php echo admin_url('project_agent/test_context'); ?>')
      .done(function(resp){
        if (resp.success) {
          alert('<?php echo _l('msg_context_test_successful'); ?>'.replace('{keys}', resp.context_keys.join(', ')));
        } else {
          alert('<?php echo _l('msg_context_test_failed'); ?>'.replace('{error}', resp.error));
        }
      });
  });

  $('#refresh-status').on('click', function(){
    location.reload();
  });

})();
</script>
