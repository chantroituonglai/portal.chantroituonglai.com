<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="row">
    <!-- LEFT COLUMN: Content Preview -->
    <div class="col-md-8">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h4 class="panel-title"><i class="fa fa-eye"></i> <?= _l('content_preview'); ?></h4>
            </div>
            <div class="panel-body">
                <!-- Post Preview -->
                <div id="post-preview-container">
                    <!-- Featured Image Preview -->
                    <div class="feature-image-preview-container">
                        <div id="feature-image-preview">
                            <img id="feature-image" src="<?= module_dir_url(TOPICS_MODULE_NAME, 'assets/img/placeholder-image.jpg'); ?>" class="img-responsive" alt="Featured Image">
                        </div>
                        <div class="mtop10">
                            <button id="select-feature-image" class="btn btn-sm btn-default" onclick="window.selectFeatureImage(); return false;">
                                <i class="fa fa-image"></i> <?= _l('select_featured_image'); ?>
                            </button>
                            <button id="remove-feature-image" class="btn btn-sm btn-danger hide" onclick="window.removeFeatureImage(); return false;">
                                <i class="fa fa-times"></i> <?= _l('remove'); ?>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Post Content Preview -->
                    <div class="post-content-preview mtop20">
                        <h3 id="preview-title" class="preview-post-title"></h3>
                        <!-- Post Existence Check Result -->
                        <div id="post-existence-check" class="alert hide mtop20"></div>

                        <div class="preview-meta-data">
                            <span class="preview-date"><i class="fa fa-calendar"></i> <span id="preview-date"></span></span>
                            <span class="preview-author"><i class="fa fa-user"></i> <span id="preview-author"></span></span>
                            <span class="preview-categories"><i class="fa fa-folder"></i> <span id="preview-categories"></span></span>
                        </div>
                        
                        <div id="preview-description" class="post-description"></div>
                     
                        <div id="preview-content" class="preview-post-content mtop10"></div>
                    </div>
                  
                </div>
            </div>
        </div>
    </div>
    
    <!-- RIGHT COLUMN: Controller Settings -->
    <div class="col-md-4">
        <!-- Topic Controller Selection -->
        <div class="panel panel-default">
            <div class="panel-heading">
                <h4 class="panel-title"><i class="fa fa-link"></i> <?= _l('select_topic_controller'); ?></h4>
            </div>
            <div class="panel-body">
                <div class="form-group">
                    <select id="topic-controller-select" class="form-control">
                        <option value=""><?= _l('select_topic_controller'); ?></option>
                        <!-- Dynamic options loaded here -->
                    </select>
                    <div id="controller-info" class="mtop10 hide">
                        <span class="label label-info">
                            <i class="fa fa-globe"></i> <span id="platform-name"></span>
                        </span>
                        <span class="label label-success">
                            <i class="fa fa-check"></i> <?= _l('connected'); ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Controller Action Buttons -->
        <div class="panel panel-default mtop20" id="controller-action-buttons-panel">
            <div class="panel-heading">
                <h4 class="panel-title"><i class="fa fa-bolt"></i> <?= _l('action_buttons'); ?></h4>
            </div>
            <div class="panel-body">
                <div id="action-buttons-container">
                    <div class="text-center text-muted">
                        <p><?= _l('select_controller_to_view_actions'); ?></p>
                    </div>
                </div>
            </div>
        </div>


        <!-- Publish Button and Status -->
        <div class="panel panel-default mtop20" id="publish-status-panel">
            <div class="panel-heading">
                <h4 class="panel-title"><i class="fa fa-paper-plane"></i> <?= _l('publish_status'); ?></h4>
            </div>
            <div class="panel-body">
                <div id="publish-result" class="mtop10"></div>
                <div id="publish-status-message" class="mtop10"></div>
            </div>
        </div>
        
        <!-- Publish Options (Moved from left column) -->
        <div class="panel panel-default mtop20" data-panel="publish-options">
            <div class="panel-heading">
                <h4 class="panel-title"><i class="fa fa-cog"></i> <?= _l('publish_options'); ?></h4>
            </div>
            <div class="panel-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="post-status"><?= _l('post_status'); ?></label>
                            <select id="post-status" class="form-control">
                                <option value="draft"><?= _l('draft'); ?></option>
                                <option value="pending"><?= _l('pending_review'); ?></option>
                                <option value="publish"><?= _l('publish'); ?></option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group schedule-time-group hide">
                            <label for="schedule-time"><?= _l('schedule_time'); ?></label>
                            <div class="input-group date">
                                <input id="schedule-time" type="text" class="form-control datepicker">
                                <div class="input-group-addon">
                                    <i class="fa fa-calendar calendar-icon"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- SEO Options -->
                <div class="form-group mtop20">
                    <label for="permalink-slug"><?= _l('permalink_slug'); ?></label>
                    <div class="input-group">
                        <span class="input-group-addon" id="permalink-prefix"></span>
                        <input type="text" id="permalink-slug" class="form-control" placeholder="<?= _l('enter_slug'); ?>">
                    </div>
                    <small class="text-muted"><?= _l('permalink_slug_help'); ?></small>
                </div>
            </div>
        </div>
        
        <!-- Categories Panel -->
        <?php $this->load->view('topics/ultimate_editor/includes/category_panel'); ?>
        
        <!-- Tags Panel -->
        <div class="panel panel-default mtop20" data-panel="tags-panel">
            <div class="panel-heading">
                <h4 class="panel-title"><i class="fa fa-tags"></i> <?= _l('tags'); ?></h4>
            </div>
            <div class="panel-body" style="position: relative;">
                <div class="tags-container">
                    <!-- Tag Input -->
                    <div class="tag-input-wrapper">
                        
                        <!-- Select2 for tags -->
                        <select id="tags-select" class="form-control mtop10" multiple="multiple">
                            <!-- Tags loaded here -->
                        </select>
                    </div>
                    
                    <div class="popular-tags mtop15">
                        <label><?= _l('popular_tags'); ?></label>
                        <p class="text-muted small"><?= _l('click_to_add_tags'); ?></p>
                        <div id="popular-tags-list">
                            <!-- Popular tags loaded here -->
                            <div class="loading-tags hide">
                                <i class="fa fa-spinner fa-spin"></i> <?= _l('loading_tags'); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>