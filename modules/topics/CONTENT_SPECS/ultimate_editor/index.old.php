<?php defined('BASEPATH') or exit('No direct script access allowed');
init_head(); 
?>
    <div id="wrapper">
    <div class="content">
                <div class="row">
                    <div class="col-md-12">
                        <h1 class="tw-font-medium tw-text-xl tw-mt-0 tw-mb-4">
                            <?php echo isset($topic) ? _l('edit_topic').': '.$topic->topictitle : _l('create_new_topic'); ?>
                        </h1>
                    </div>
                </div>

        <!-- Header buttons -->
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body _buttons">
                        <div class="row">
                            <div class="col-md-8">
                                <!-- Primary buttons -->
                                <button id="load-workflow-btn" class="btn btn-primary">
                                    <i class="fa fa-download"></i> <?= _l('load_content_via_workflow') ?>
                                </button>
                                <button id="save-draft-btn" class="btn btn-info mleft5">
                                    <i class="fa fa-save"></i> <?= _l('save_draft') ?>
                                </button>
                                <button id="publish-draft-btn" class="btn btn-success mleft5">
                                    <i class="fa fa-globe"></i> <?= _l('publish') ?>
                                </button>
                            </div>
                            <div class="col-md-4 text-right">
                                <!-- Secondary buttons -->
                                <div class="btn-group mleft5">
                                    <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        <i class="fa fa-magic"></i> <?= _l('ai_tools') ?> <span class="caret"></span>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-right">
                                        <li><a href="#" id="ai-improve-btn"><i class="fa fa-arrow-up"></i> <?= _l('improve_content') ?></a></li>
                                        <li><a href="#" id="ai-rewrite-btn"><i class="fa fa-refresh"></i> <?= _l('rewrite_content') ?></a></li>
                                        <li><a href="#" id="ai-expand-btn"><i class="fa fa-expand"></i> <?= _l('expand_content') ?></a></li>
                                        <li class="divider"></li>
                                        <li><a href="#" id="ai-seo-btn"><i class="fa fa-line-chart"></i> <?= _l('optimize_seo') ?></a></li>
                                    </ul>
                                </div>
                                <button id="editor-settings-btn" class="btn btn-default mleft5">
                                    <i class="fa fa-cog"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Editor Content -->
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <!-- Draft info and auto-save status -->
                        <div class="editor-status-bar mb-2">
                            <div class="d-flex justify-content-between">
                                <div class="draft-info">
                                    <span class="text-muted"><?= _l('current_draft') ?>: </span>
                                    <span id="current-draft-name" class="font-weight-bold"><?= isset($active_draft) ? $active_draft->draft_title : _l('no_draft_selected') ?></span>
                                    <span class="badge badge-info ml-2" id="draft-version"><?= isset($active_draft) ? 'v'.$active_draft->version : '' ?></span>
                                    <span class="badge badge-<?= isset($active_draft) && $active_draft->status == 'final' ? 'success' : 'warning' ?> ml-1" id="draft-status">
                                        <?= isset($active_draft) ? $active_draft->status : '' ?>
                                    </span>
                                </div>
                                <div class="save-status">
                                    <span id="autosave-status" class="text-muted">
                                        <i class="fa fa-circle text-success mr-1 d-none" id="saving-indicator"></i> 
                                        <?= _l('autosaved') ?>: <span id="last-saved-time"><?= _l('never') ?></span>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Main Editor Content -->
                        <div class="row">
                            <!-- Editor Column -->
                            <div class="col-md-9">
                                <div class="editor-main">
                                    <div class="form-group">
                                        <label for="draft-title" class="control-label"><?= _l('draft_title') ?> <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <input type="text" id="draft-title" name="draft_title" class="form-control" placeholder="<?= _l('enter_draft_title') ?>" value="<?= isset($active_draft) ? $active_draft->draft_title : '' ?>">
                                            <span class="input-group-btn">
                                                <button class="btn btn-info ai-edit-title-btn" type="button" title="<?= _l('ai_edit') ?>">
                                                    <i class="fa fa-magic"></i>
                                                </button>
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="draft-description" class="control-label"><?= _l('draft_description') ?></label>
                                        <div class="input-group">
                                            <textarea id="draft-description" name="draft_description" class="form-control" placeholder="<?= _l('enter_draft_description') ?>" rows="3"><?= isset($active_draft) ? $active_draft->draft_description : '' ?></textarea>
                                            <span class="input-group-btn">
                                                <button class="btn btn-info ai-edit-description-btn" type="button" title="<?= _l('ai_edit') ?>">
                                                    <i class="fa fa-magic"></i>
                                                </button>
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="draft-tags" class="control-label"><?= _l('draft_tags') ?></label>
                                        <input type="text" id="draft-tags" name="draft_tags" class="form-control" placeholder="<?= _l('enter_tags_comma_separated') ?>" value="<?= isset($active_draft) ? $active_draft->draft_tags : '' ?>">
                                        <small class="text-muted"><?= _l('separate_tags_with_commas') ?></small>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="draft-content" class="control-label"><?= _l('draft_content') ?></label>
                                        <textarea id="draft-content" name="draft_content" class="form-control tinymce-editor"><?= isset($active_draft) ? $active_draft->draft_content : '' ?></textarea>
                                    </div>
                                    
                                    <div id="editor-content-container" class="d-none">
                                        <div id="editor-content" style="min-height:300px; border:1px solid #ccc; margin-top:10px; padding:10px;"></div>
                                    </div>
                                    
                                    <div id="word-count-container" class="editor-stats mt-3">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="editor-stats-item">
                                                    <i class="fa fa-file-text-o"></i> <?= _l('word_count') ?>: <span id="word-count">0</span>
                                                </div>
                                                <div class="editor-stats-item ml-3">
                                                    <i class="fa fa-calculator"></i> <?= _l('character_count') ?>: <span id="char-count">0</span>
                                                </div>
                                            </div>
                                            <div class="col-md-6 text-right">
                                                <div class="editor-stats-item">
                                                    <i class="fa fa-clock-o"></i> <?= _l('reading_time') ?>: <span id="reading-time">0 min</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Analysis Column -->
                            <div class="col-md-3">
                                <div class="tabs-container">
                                    <ul class="nav nav-tabs" role="tablist">
                                        <li role="presentation" class="active">
                                            <a href="#tab_drafts" aria-controls="tab_drafts" role="tab" data-toggle="tab">
                                                <i class="fa fa-file-text-o"></i> <?= _l('drafts') ?>
                                            </a>
                                        </li>
                                        <li role="presentation">
                                            <a href="#tab_seo_analysis" aria-controls="tab_seo_analysis" role="tab" data-toggle="tab">
                                                <i class="fa fa-line-chart"></i> <?= _l('seo_analysis') ?>
                                            </a>
                                        </li>
                                        <li role="presentation">
                                            <a href="#tab_keyword_analysis" aria-controls="tab_keyword_analysis" role="tab" data-toggle="tab">
                                                <i class="fa fa-key"></i> <?= _l('keyword_analysis') ?>
                                            </a>
                                        </li>
                                    </ul>
                                    
                                    <div class="tab-content">
                                        <!-- Tab contents remain the same -->
                                        <?php include('includes/tab_drafts.php'); ?>
                                        <?php include('includes/tab_seo_analysis.php'); ?>
                                        <?php include('includes/tab_keyword_analysis.php'); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer buttons -->
        <div class="btn-bottom-pusher"></div>
        <div class="btn-bottom-toolbar">
            <div class="row">
                <div class="col-md-7 editor-toolbar-footer">
                    <div class="btn-group">
                        <button type="button" class="btn btn-default" data-format="bold"><i class="fa fa-bold"></i></button>
                        <button type="button" class="btn btn-default" data-format="italic"><i class="fa fa-italic"></i></button>
                        <button type="button" class="btn btn-default" data-format="underline"><i class="fa fa-underline"></i></button>
                    </div>
                    <div class="btn-group mleft5">
                        <button type="button" class="btn btn-default" data-format="alignleft"><i class="fa fa-align-left"></i></button>
                        <button type="button" class="btn btn-default" data-format="aligncenter"><i class="fa fa-align-center"></i></button>
                        <button type="button" class="btn btn-default" data-format="alignright"><i class="fa fa-align-right"></i></button>
                    </div>
                    <div class="btn-group mleft5">
                        <button type="button" class="btn btn-default" data-format="bullist"><i class="fa fa-list-ul"></i></button>
                        <button type="button" class="btn btn-default" data-format="numlist"><i class="fa fa-list-ol"></i></button>
                    </div>
                    <div class="btn-group mleft5">
                        <button type="button" class="btn btn-default" data-format="link"><i class="fa fa-link"></i></button>
                        <button type="button" class="btn btn-default" data-format="image"><i class="fa fa-image"></i></button>
                    </div>
                    <div class="btn-group mleft5">
                        <button type="button" class="btn btn-primary ai-tool-btn"><i class="fa fa-magic"></i> <?= _l('ai_tools') ?></button>
                    </div>
                </div>
                <div class="col-md-5 text-right">
                    <button type="button" class="btn btn-default" onclick="window.history.back();">
                        <i class="fa fa-angle-left"></i> <?= _l('back') ?>
                    </button>
                    <button type="button" class="btn btn-info save-draft-btn">
                        <i class="fa fa-save"></i> <?= _l('save_draft') ?>
                    </button>
                    <button type="button" class="btn btn-success publish-draft-btn">
                        <i class="fa fa-globe"></i> <?= _l('publish') ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php init_tail(); ?>

<!-- Required Hidden Fields -->
    <input type="hidden" id="topicid" value="<?= isset($topic) ? $topic->id : (isset($topic_id) ? $topic_id : '') ?>">
    <input type="hidden" id="current-draft-id" value="<?= isset($active_draft) ? $active_draft->id : '' ?>">

<!-- Workflow Configuration -->
<script>
    // Workflow configuration from action button
    var workflowConfig = {
        workflow_id: 'f9ded49a-a546-44f8-9025-2c494397c35d',
        button_id: '15',
        target_type: 'BuildPostStructure',
        target_state: 'BuildPostStructure_B_Begin',
        action_command: 'WRITE_DRAFT'
    };
</script>

<!-- Load Editor Scripts -->
<?php
$version = '?v=' . time();
?>
<script src="<?= module_dir_url(TOPICS_MODULE_NAME, 'assets/js/jsonrepair.min.js') . $version ?>"></script>
<script src="<?= module_dir_url(TOPICS_MODULE_NAME, 'assets/js/ultimate_editor.js?v=' . time()) ?>"></script>

<style>
    /* Add your styles here */
    .editor-toolbar-footer {
        display: flex;
        align-items: center;
    }
    
    .btn-bottom-toolbar {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        z-index: 100;
        padding: 10px 20px;
        background-color: #fff;
        border-top: 1px solid #dce1ef;
        box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
    }
    
    .btn-bottom-pusher {
        height: 70px;
    }
    
    /* AI Tool dropdown */
    .ai-tool-btn {
        position: relative;
    }
    
    .ai-tool-dropdown {
        display: none;
        position: absolute;
        top: -175px;
        left: 0;
        min-width: 200px;
        background-color: #fff;
        box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        border-radius: 4px;
        z-index: 1000;
    }
    
    .ai-tool-btn:hover .ai-tool-dropdown {
        display: block;
    }
    
    .ai-tool-dropdown a {
        display: block;
        padding: 8px 15px;
        color: #777;
        text-decoration: none;
    }
    
    .ai-tool-dropdown a:hover {
        background-color: #f6f8fa;
        color: #0056b3;
    }
</style>

<!-- JS for AI Tool dropdown -->
<script>
    $(document).ready(function() {
        $('.ai-tool-btn').click(function(e) {
            e.preventDefault();
            // Show dropdown menu for AI tools
            var dropdown = $('<div class="ai-tool-dropdown">' +
                '<a href="#" id="ai-improve-btn"><i class="fa fa-arrow-up"></i> <?= _l('improve_content') ?></a>' +
                '<a href="#" id="ai-rewrite-btn"><i class="fa fa-refresh"></i> <?= _l('rewrite_content') ?></a>' +
                '<a href="#" id="ai-expand-btn"><i class="fa fa-expand"></i> <?= _l('expand_content') ?></a>' +
                '<a href="#" id="ai-summarize-btn"><i class="fa fa-compress"></i> <?= _l('summarize_content') ?></a>' +
                '<a href="#" id="ai-seo-btn"><i class="fa fa-line-chart"></i> <?= _l('optimize_seo') ?></a>' +
            '</div>');
            
            // Remove any existing dropdown
            $('.ai-tool-dropdown').remove();
            
            // Add new dropdown
            $(this).after(dropdown);
            
            // Close when clicking outside
            $(document).one('click', function() {
                dropdown.remove();
            });
            
            return false;
        });
        
        // Prevent dropdown from closing when clicking inside it
        $(document).on('click', '.ai-tool-dropdown', function(e) {
            e.stopPropagation();
        });
    });
</script>