<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>

<script>
    var controllerId = <?php echo $controller->id; ?>;
</script>
<link href="<?php echo module_dir_url('topics', 'assets/css/controller.css?ver=' . time()); ?>" rel="stylesheet">
<link href="<?php echo module_dir_url('topics', 'assets/css/draft_writer.css?ver=' . time()); ?>" rel="stylesheet">
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <!-- Add hidden field for platform to be accessible by JavaScript -->
                        <input type="hidden" name="platform" id="platform" value="<?php echo html_escape($controller->platform); ?>">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <h4 class="no-margin"><?php echo html_escape($controller->site); ?></h4>
                            </div>
                            <div class="col-md-6 text-right">
                                <a href="<?php echo admin_url('topics/controllers/get_action_buttons/' . $controller->id); ?>" class="btn btn-primary btn-sm">
                                    <i class="fa fa-bolt"></i> <?php echo _l('controller_action_buttons'); ?>
                                </a>
                                
                                <?php if (has_permission('topics', '', 'edit')) { ?>
                                    <a href="<?php echo admin_url('topics/controllers/edit/' . $controller->id); ?>" class="btn btn-default btn-sm">
                                        <i class="fa fa-pencil-square-o"></i> <?php echo _l('edit_controller'); ?>
                                    </a>
                                <?php } ?>
                                
                                <?php if (has_permission('topics', '', 'create')) { ?>
                                    <button type="button" class="btn btn-info btn-sm" id="test_connection_btn" data-controller-id="<?php echo $controller->id; ?>">
                                        <i class="fa fa-check"></i> <?php echo _l('test_connection'); ?>
                                    </button>
                                <?php } ?>
                                
                                <a href="<?php echo admin_url('topics/controllers'); ?>" class="btn btn-default btn-sm">
                                    <i class="fa fa-list"></i> <?php echo _l('go_back'); ?>
                                </a>
                            </div>
                        </div>
                        
                        <hr class="mt-1 mb-2">
                        
                        <!-- Connection Status Container -->
                        <div id="connection_status_container" class="mtop10" style="display:none;"></div>
                        
                        <div class="row">
                            <div class="col-md-12">
                                <ul class="nav nav-tabs" role="tablist">
                                    <li role="presentation" class="active">
                                        <a href="#tab_overview" aria-controls="tab_overview" role="tab" data-toggle="tab">
                                            <?php echo _l('overview'); ?>
                                        </a>
                                    </li>
                            
                                    <li role="presentation">
                                        <a href="#tab_action_buttons" aria-controls="tab_action_buttons" role="tab" data-toggle="tab">
                                            <?php echo _l('action_buttons'); ?>
                                        </a>
                                    </li>
                                </ul>
                                
                                <div class="tab-content">
                                    <div role="tabpanel" class="tab-pane active" id="tab_overview">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <h4 class="no-margin">
                                                    <?php echo _l('controller_details'); ?>
                                                    <?php if (has_permission('topics', '', 'edit')) { ?>
                                                    <div class="pull-right">
                                                        <button type="button" class="btn btn-info btn-sm" onclick="showAddTopicsModal()">
                                                            <i class="fa fa-plus"></i> <?php echo _l('add_topics'); ?>
                                                        </button>
                                                        <a href="<?php echo admin_url('topics/controllers/edit/'.$controller->id); ?>" class="btn btn-default btn-sm">
                                                            <i class="fa fa-pencil-square-o"></i> <?php echo _l('edit'); ?>
                                                        </a>
                                                    </div>
                                                    <?php } ?>
                                                </h4>
                                                <hr class="hr-panel-separator" />
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <table class="table table-striped">
                                                    <tr>
                                                        <td class="bold"><?php echo _l('site'); ?></td>
                                                        <td><?php echo $controller->site; ?></td>
                                                    </tr>
                                                    <tr>
                                                        <td class="bold"><?php echo _l('platform'); ?></td>
                                                        <td><?php echo $controller->platform; ?></td>
                                                    </tr>
                                                    <tr>
                                                        <td class="bold"><?php echo _l('blog_id'); ?></td>
                                                        <td><?php echo $controller->blog_id; ?></td>
                                                    </tr>
                                                    <tr>
                                                        <td class="bold"><?php echo _l('logo_url'); ?></td>
                                                        <td>
                                                            <?php if (!empty($controller->logo_url)) { ?>
                                                                <div class="logo-preview">
                                                                    <img src="<?php echo html_escape($controller->logo_url); ?>" 
                                                                         alt="<?php echo _l('site_logo'); ?>"
                                                                         class="img-responsive"
                                                                         onerror="this.onerror=null;this.src='<?php echo site_url('assets/images/placeholder.png'); ?>';">
                                                                    <a href="<?php echo html_escape($controller->logo_url); ?>" 
                                                                       target="_blank" 
                                                                       class="view-original-link">
                                                                        <i class="fa fa-external-link"></i> <?php echo _l('view_original'); ?>
                                                                    </a>
                                                                </div>
                                                            <?php } else { ?>
                                                                <span class="text-muted"><?php echo _l('no_logo_provided'); ?></span>
                                                            <?php } ?>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td class="bold"><?php echo _l('status'); ?></td>
                                                        <td>
                                                            <?php
                                                            $status_badge = $controller->status == 1 ? 'success' : 'danger';
                                                            $status_text = $controller->status == 1 ? _l('active') : _l('inactive');
                                                            ?>
                                                            <span class="badge badge-<?php echo $status_badge; ?>"><?php echo $status_text; ?></span>
                                                        </td>
                                                    </tr>
                                                  
                                                </table>
                                            </div>
                                            <div class="col-md-6">
                                                <table class="table table-striped">
                                                    <tr>
                                                        <td class="bold"><?php echo _l('project_id'); ?></td>
                                                        <td><?php echo $controller->project_id; ?></td>
                                                    </tr>
                                                    <tr>
                                                        <td class="bold"><?php echo _l('seo_task_sheet_id'); ?></td>
                                                        <td><?php echo $controller->seo_task_sheet_id; ?></td>
                                                    </tr>
                                                    <tr>
                                                        <td class="bold"><?php echo _l('datecreated'); ?></td>
                                                        <td><?php echo _dt($controller->datecreated); ?></td>
                                                    </tr>
                                                    <tr>
                                                        <td class="bold"><?php echo _l('dateupdated'); ?></td>
                                                        <td><?php echo _dt($controller->dateupdated); ?></td>
                                                    </tr>
                                                </table>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-12">
                                                <h4 class="bold"><?php echo _l('writing_style'); ?></h4>
                                                <p><?php echo $controller->writing_style; ?></p>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-12">
                                                <h4 class="bold"><?php echo _l('slogan'); ?></h4>
                                                <p><?php echo $controller->slogan; ?></p>
                                            </div>
                                        </div>

                                        <!-- Actors Section -->
                                        <div class="row mtop15">
                                            <div class="col-md-12">
                                                <h4 class="bold"><?php echo _l('actors'); ?></h4>
                                                
                                                <?php 
                                                // Load the Topic_controller_actor_model
                                                $CI = &get_instance();
                                                $CI->load->model('Topic_controller_actor_model');
                                                
                                                // Get actors for this controller
                                                $actors = $CI->Topic_controller_actor_model->get_actors_by_controller($controller->id);
                                                
                                                if (!empty($actors)) {
                                                    echo '<div class="row">';
                                                    foreach ($actors as $actor) {
                                                        ?>
                                                        <div class="col-md-6">
                                                            <div class="panel panel-default actor-view-panel">
                                                                <div class="panel-heading">
                                                                    <h5 class="panel-title">
                                                                        <i class="fa fa-user"></i> <?php echo $actor['name']; ?>
                                                                        <?php if ($actor['active']) { ?>
                                                                            <span class="label label-success pull-right"><?php echo _l('active'); ?></span>
                                                                        <?php } else { ?>
                                                                            <span class="label label-default pull-right"><?php echo _l('inactive'); ?></span>
                                                                        <?php } ?>
                                                                    </h5>
                                                                </div>
                                                                <div class="panel-body">
                                                                    <div class="actor-description">
                                                                        <?php echo $actor['description']; ?>
                                                                    </div>
                                                                    <div class="mtop10">
                                                                        <span class="label label-default"><?php echo _l('priority'); ?>: <?php echo $actor['priority']; ?></span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <?php
                                                    }
                                                    echo '</div>';
                                                } else {
                                                    ?>
                                                    <div class="alert alert-info">
                                                        <i class="fa fa-info-circle"></i> <?php echo _l('no_actors_yet'); ?>
                                                        <?php if (has_permission('topics', '', 'edit')) { ?>
                                                            <a href="<?php echo admin_url('topics/controllers/edit/' . $controller->id); ?>" class="alert-link">
                                                                <?php echo _l('add_actor'); ?>
                                                            </a>
                                                        <?php } ?>
                                                    </div>
                                                    <?php
                                                }
                                                ?>
                                            </div>
                                        </div>

                                        <!-- Tabs Section -->
                                        <div class="row mtop20">
                                            <div class="col-md-12">
                                                <ul class="nav nav-tabs" role="tablist">
                                                    <li role="presentation" class="active">
                                                        <a href="#topics" aria-controls="topics" role="tab" data-toggle="tab">
                                                            <?php echo _l('related_topics'); ?>
                                                        </a>
                                                    </li>
                                                    <li role="presentation">
                                                        <a href="#categories" aria-controls="categories" role="tab" data-toggle="tab">
                                                            <?php echo _l('controller_categories_tab'); ?>
                                                        </a>
                                                    </li>
                                                    <li role="presentation">
                                                        <a href="#blogs" aria-controls="blogs" role="tab" data-toggle="tab">
                                                            <?php echo _l('controller_blogs_tab'); ?>
                                                        </a>
                                                    </li>
                                                    <li role="presentation">
                                                        <a href="#tags" aria-controls="tags" role="tab" data-toggle="tab">
                                                            <?php echo _l('controller_tags_tab'); ?>
                                                        </a>
                                                    </li>
                                                </ul>
                                                
                                                <div class="tab-content">
                                                    <!-- Topics Tab -->
                                                    <div role="tabpanel" class="tab-pane active" id="topics">
                                                        <div class="row mtop20">
                                                            <div class="col-md-12">
                                                                <h4 class="bold"><?php echo _l('related_topics'); ?></h4>
                                                                <?php render_datatable([
                                                                    '<input type="checkbox" id="select_all_related_topics" />',
                                                                    _l('topic_id'),
                                                                    _l('topic_title'),
                                                                    _l('status'),
                                                                    _l('assigned_date'),
                                                                    _l('options')
                                                                ], 'related-topics'); ?>
                                                                <div class="mtop10">
                                                                    <button class="btn btn-danger btn-sm" id="remove-selected-topics" style="display:none;">
                                                                        <i class="fa fa-remove"></i> <?php echo _l('remove_selected'); ?>
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- Categories Tab -->
                                                    <div role="tabpanel" class="tab-pane" id="categories">
                                                        <?php $this->load->view('controllers/tabs/categories'); ?>
                                                    </div>
                                                    
                                                    <!-- Blogs Tab -->
                                                    <div role="tabpanel" class="tab-pane" id="blogs">
                                                        <?php $this->load->view('controllers/tabs/blogs'); ?>
                                                    </div>
                                                    
                                                    <!-- Tags Tab -->
                                                    <div role="tabpanel" class="tab-pane" id="tags">
                                                        <?php $this->load->view('controllers/tabs/tags'); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div role="tabpanel" class="tab-pane" id="tab_action_buttons">
                                        <div class="row mtop15">
                                            <div class="col-md-12">
                                                <!-- Action Buttons section -->
                                                <div class="panel_s">
                                                    <div class="panel-body">
                                                        <div class="row">
                                                            <div class="col-md-8">
                                                                <h4 class="no-margin">
                                                                    <?php echo _l('action_buttons'); ?>
                                                                </h4>
                                                            </div>
                                                            <div class="col-md-4 text-right">
                                                                <a href="<?php echo admin_url('topics/controllers/get_action_buttons/' . $controller->id); ?>" class="btn btn-info">
                                                                    <i class="fa fa-cog"></i> <?php echo _l('manage_action_buttons'); ?>
                                                                </a>
                                                            </div>
                                                        </div>
                                                        <hr>
                                                        
                                                        <?php
                                                        // Load controller action buttons
                                                        $CI = &get_instance();
                                                        $CI->load->model('Topic_controller_action_button_model');
                                                        $action_buttons = $CI->Topic_controller_action_button_model->get_action_buttons_by_controller($controller->id);
                                                        
                                                        if (!empty($action_buttons)) {
                                                            echo '<div class="row">';
                                                            foreach ($action_buttons as $button) {
                                                                echo '<div class="col-md-3 col-sm-6 text-center" style="margin-bottom: 15px;">';
                                                                echo '<div class="well" style="min-height: 120px;">';
                                                                echo '<h4 class="bold">' . html_escape($button['name']) . '</h4>';
                                                                echo '<span class="label label-' . html_escape($button['button_type']) . '">' . 
                                                                      ucfirst(html_escape($button['button_type'])) . '</span>';
                                                                if (!empty($button['action_command'])) {
                                                                    echo '<br><br><small class="text-muted">Command: ' . html_escape($button['action_command']) . '</small>';
                                                                }
                                                                echo '</div>';
                                                                echo '</div>';
                                                            }
                                                            echo '</div>';
                                                        } else {
                                                            echo '<div class="alert alert-info">';
                                                            echo _l('no_action_buttons_assigned');
                                                            echo '</div>';
                                                            
                                                            // Show Add button
                                                            if (has_permission('topics', '', 'create')) {
                                                                echo '<div class="text-center">';
                                                                echo '<a href="' . admin_url('topics/controllers/get_action_buttons/' . $controller->id) . '" class="btn btn-primary">';
                                                                echo '<i class="fa fa-plus"></i> ' . _l('add_action_buttons');
                                                                echo '</a>';
                                                                echo '</div>';
                                                            }
                                                        }
                                                        ?>
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
</div>

<!-- Add Topics Modal -->
<div class="modal fade" id="addTopicsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><?php echo _l('add_topics_to_controller'); ?></h4>
            </div>
            <div class="modal-body">
                <!-- Search Field -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <div class="input-group">
                                <input type="text" class="form-control" id="topics-search" placeholder="<?php echo _l('search_topics'); ?>">
                                <span class="input-group-btn">
                                    <button class="btn btn-default" type="button" onclick="searchTopics()">
                                        <i class="fa fa-search"></i>
                                    </button>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="selected-topics-count pull-left mtop5">
                            <span class="label label-info"><?php echo _l('no_topics_selected'); ?></span>
                        </div>
                        <button class="btn btn-info pull-right" onclick="saveSelectedTopics()" id="saveTopicsBtn" disabled>
                            <?php echo _l('save'); ?>
                        </button>
                    </div>
                </div>
                <div class="clearfix mtop20"></div>
                <table class="table table-topics-selection">
                    <thead>
                        <tr>
                            <th style="width: 40px;"><input type="checkbox" id="select_all_topics" /></th>
                            <th><?php echo _l('topic_id'); ?></th>
                            <th><?php echo _l('topic_title'); ?></th>
                            <th><?php echo _l('status'); ?></th>
                            <th><?php echo _l('datecreated'); ?></th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
window.onload = function() { 

        // Toggle API Token visibility
        $('.toggle-token').on('click', function() {
        var $wrapper = $(this).closest('.api-token-wrapper');
        var $mask = $wrapper.find('.api-token-mask');
        var $full = $wrapper.find('.api-token-full');
        var $icon = $(this).find('i');
        
        if ($mask.is(':visible')) {
            $mask.hide();
            $full.show();
            $icon.removeClass('fa-eye').addClass('fa-eye-slash');
            $(this).html('<i class="fa fa-eye-slash"></i> ' + "<?php echo _l('hide_token'); ?>");
        } else {
            $mask.show();
            $full.hide();
            $icon.removeClass('fa-eye-slash').addClass('fa-eye');
            $(this).html('<i class="fa fa-eye"></i> ' + "<?php echo _l('view_token'); ?>");
        }
    });
};
</script>

<?php init_tail(); ?>
<script src="<?php echo module_dir_url('topics', 'assets/js/controllers/detail.js?ver=' . time()); ?>"></script>
<script src="<?php echo module_dir_url('topics', 'assets/js/draft_writer/controllers.js?ver=' . time()); ?>"></script>
