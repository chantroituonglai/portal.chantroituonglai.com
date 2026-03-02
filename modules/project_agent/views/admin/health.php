<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-12">
        <div class="panel_s">
          <div class="panel-body">
            <h4 class="no-margin"><i class="fa fa-heartbeat"></i> <?php echo _l('project_agent_health_check'); ?></h4>
            <p class="text-muted mtop5">Check the health status of Project Agent module and its dependencies.</p>
            <hr class="hr-panel-heading"/>
            
            <!-- Database Tables Status -->
            <div class="row">
              <div class="col-md-6">
                <h5 class="mbot10"><?php echo _l('health_check_database_tables'); ?></h5>
                <div class="table-responsive">
                  <table class="table table-striped">
                    <thead>
                      <tr>
                        <th>Table</th>
                        <th>Status</th>
                        <th>Records</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($tables as $key => $table): ?>
                      <tr>
                        <td><?php echo $table['table']; ?></td>
                        <td>
                          <?php if ($table['exists']): ?>
                            <span class="label label-success">Exists</span>
                          <?php else: ?>
                            <span class="label label-danger">Missing</span>
                          <?php endif; ?>
                        </td>
                        <td>
                          <?php if ($table['exists'] && $table['count'] !== null): ?>
                            <?php echo $table['count']; ?>
                          <?php else: ?>
                            -
                          <?php endif; ?>
                        </td>
                      </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              </div>
              
              <div class="col-md-6">
                <h5 class="mbot10"><?php echo _l('health_check_module_status'); ?></h5>
                <div class="table-responsive">
                  <table class="table table-striped">
                    <thead>
                      <tr>
                        <th>Module</th>
                        <th>Status</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($modules as $module => $active): ?>
                      <tr>
                        <td><?php echo ucfirst($module); ?></td>
                        <td>
                          <?php if ($active): ?>
                            <span class="label label-success">Active</span>
                          <?php else: ?>
                            <span class="label label-warning">Inactive</span>
                          <?php endif; ?>
                        </td>
                      </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
            
            <!-- AI Provider Status -->
            <div class="row">
              <div class="col-md-12">
                <h5 class="mbot10"><?php echo _l('health_check_ai_provider'); ?></h5>
                <div class="alert <?php echo $ai_available ? 'alert-success' : 'alert-warning'; ?>">
                  <?php if ($ai_available): ?>
                    <i class="fa fa-check"></i> AI Provider is available
                  <?php else: ?>
                    <i class="fa fa-exclamation-triangle"></i> No AI Provider available
                    <?php if (isset($ai_error)): ?>
                      <br><small>Error: <?php echo $ai_error; ?></small>
                    <?php endif; ?>
                  <?php endif; ?>
                </div>
                
                <?php if (!empty($ai_providers)): ?>
                <div class="table-responsive">
                  <table class="table table-striped">
                    <thead>
                      <tr>
                        <th>Provider</th>
                        <th>Name</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($ai_providers as $provider): ?>
                      <tr>
                        <td><?php echo $provider['id']; ?></td>
                        <td><?php echo $provider['name']; ?></td>
                      </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
                <?php endif; ?>
              </div>
            </div>
            
            <!-- Database Version -->
            <div class="row">
              <div class="col-md-12">
                <h5 class="mbot10"><?php echo _l('health_check_database_version'); ?></h5>
                <p>Current version: <strong><?php echo $db_version; ?></strong></p>
              </div>
            </div>
            
            <!-- Actions -->
            <div class="row">
              <div class="col-md-12">
                <h5 class="mbot10"><?php echo _l('health_check_actions'); ?></h5>
                <button class="btn btn-primary" onclick="refreshHealthCheck()">
                  <i class="fa fa-refresh"></i> <?php echo _l('health_check_refresh'); ?>
                </button>
                <button class="btn btn-success" onclick="createMissingTables()">
                  <i class="fa fa-plus"></i> <?php echo _l('health_check_create_tables'); ?>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
function refreshHealthCheck() {
    location.reload();
}

function createMissingTables() {
    if (confirm('This will create any missing database tables. Continue?')) {
        $.post('<?php echo admin_url('project_agent/health_ajax'); ?>', function(response) {
            if (response.success) {
                alert('Tables checked successfully');
                location.reload();
            } else {
                alert('Error: ' + response.error);
            }
        }).fail(function() {
            alert('Failed to create tables');
        });
    }
}
</script>
<?php init_tail(); ?>
