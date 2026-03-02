<?php defined('BASEPATH') or exit('No direct script access allowed');
init_head(); 
?>

<!-- CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />



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
                                <button id="create-from-workflow-btn" class="btn btn-info mleft5">
                                    <i class="fa fa-magic"></i> <?= _l('create_draft_from_workflow') ?>
                                </button>
                                <button id="save-draft-btn" class="save-draft-btn btn btn-info mleft5">
                                    <i class="fa fa-save"></i> <?= _l('save_draft') ?>
                                </button>
                                <button id="publish-draft-btn" class="publish-draft-btn btn btn-success mleft5">
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
                            <div class="col-md-8">
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
                                        <div class="input-group input-group-textarea">
                                            <textarea id="draft-description" name="draft_description" class="form-control" placeholder="<?= _l('enter_draft_description') ?>" rows="3"><?= isset($active_draft) ? ($active_draft->draft_description ?? '') : '' ?></textarea>
                                            <span class="input-group-btn">
                                                <button class="btn btn-info ai-edit-description-btn" type="button" title="<?= _l('ai_edit') ?>">
                                                    <i class="fa fa-magic"></i>
                                                </button>
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="draft-tags" class="control-label"><?= _l('draft_tags') ?></label>
                                        <div class="input-group">
                                            <input type="text" id="draft-tags" name="draft_tags" class="form-control" placeholder="<?= _l('enter_tags_comma_separated') ?>" value="<?= isset($active_draft) ? ($active_draft->draft_tags ?? '') : '' ?>">
                                            <span class="input-group-btn">
                                                <button class="btn btn-info" id="add-tag-btn" type="button">
                                                    <i class="fa fa-plus"></i> <?= _l('add') ?>
                                                </button>
                                            </span>
                                        </div>
                                        <div id="tags-container" class="mt-2"></div>
                                        <small class="text-muted"><?= _l('separate_tags_with_commas') ?></small>
                                    </div>
                                    
                                    <!-- Feature Image Section -->
                                    <div class="form-group">
                                        <label for="feature-image-container" class="control-label"><?= _l('feature_image') ?></label>
                                        <div id="feature-image-container" class="mb-2">
                                            <div class="feature-image-preview <?= isset($active_draft) && !empty($active_draft->feature_image) ? '' : 'hide' ?>">
                                                <img id="feature-image" src="<?= isset($active_draft) && !empty($active_draft->feature_image) ? $active_draft->feature_image : '' ?>" class="img-responsive" alt="Feature Image">
                                                <div class="feature-image-overlay">
                                                    <button type="button" class="btn btn-danger btn-sm remove-feature-image" onclick="window.removeFeatureImage()">
                                                        <i class="fa fa-trash"></i> <?= _l('remove') ?>
                                                    </button>
                                                </div>
                                            </div>
                                            
                                            <div class="feature-image-buttons">
                                                <button type="button" class="btn btn-info" id="select-feature-image-btn" onclick="window.selectFeatureImage()">
                                                    <i class="fa fa-image"></i> <?= _l('select_feature_image') ?>
                                                </button>
                                                <small class="text-muted ml-2"><?= _l('recommended_size') ?>: 1200x628 px</small>
                                            </div>
                                            
                                            <!-- Hidden input to store feature image URL -->
                                            <input type="hidden" id="feature-image-url" name="feature_image" value="<?= isset($active_draft) ? ($active_draft->feature_image ?? '') : '' ?>">
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="editor-content" class="control-label"><?= _l('draft_content') ?></label>
                                        <div id="editor-content-container">
                                            <div id="editor-content" class="tinymce-editor"><?= isset($active_draft) ? $active_draft->draft_content : '' ?></div>
                                        </div>
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
                            <div class="col-md-4">
                                <div class="tabs-container">
                                    <ul class="nav nav-tabs" role="tablist">
                                        <li role="presentation" class="active">
                                            <a href="#tab_draft_writer" aria-controls="tab_draft_writer" role="tab" data-toggle="tab">
                                                <i class="fa fa-file-text-o"></i> <span><?= _l('draft_writer') ?></span>
                                            </a>
                                        </li>
                                        <li role="presentation">
                                            <a href="#tab_seo_analysis" aria-controls="tab_seo_analysis" role="tab" data-toggle="tab">
                                                <i class="fa fa-line-chart"></i> <span><?= _l('seo_analysis') ?></span>
                                            </a>
                                        </li>
                                    </ul>

                                    <div class="tab-content">
                                        <!-- Draft Writer Tab -->
                                        <div role="tabpanel" class="tab-pane active" id="tab_draft_writer">
                                            <div class="panel panel-info">
                                                <div class="panel-heading">
                                                    <h3 class="panel-title"><?= _l('draft_writer_integration') ?></h3>
                                                </div>
                                                <div class="panel-body">
                                                    <button id="btn-import-draft-writer" class="btn btn-block btn-info">
                                                        <i class="fa fa-download"></i> <?= _l('import_from_draft_writer') ?>
                                                    </button>
                                                    <div class="text-muted small mt-2">
                                                        <?= _l('import_draft_writer_help_text') ?>
                                                    </div>
                                                    
                                                    <!-- Draft List Section -->
                                                    <div class="draft-list-section mt-4">
                                                        <h4 class="compact-heading"><?= _l('available_drafts') ?></h4>
                                                        <div class="drafts-container">
                                                            <div class="draft-filter-bar mb-2">
                                                                <div class="row">
                                                                    <div class="col-md-6">
                                                                        <select id="draft-sort-order" class="form-control input-sm">
                                                                            <option value="newest"><?= _l('newest_first') ?></option>
                                                                            <option value="oldest"><?= _l('oldest_first') ?></option>
                                                                        </select>
                                                                    </div>
                                                                    <div class="col-md-6 text-right">
                                                                        <button id="refresh-drafts-btn" class="btn btn-default btn-sm">
                                                                            <i class="fa fa-refresh"></i> <?= _l('refresh') ?>
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            
                                                            <div id="drafts-list" class="draft-list">
                                                                <!-- Drafts will be loaded here -->
                                                                <div class="text-center p-3">
                                                                    <i class="fa fa-spinner fa-spin fa-2x"></i>
                                                                    <p class="mt-2"><?= _l('loading_drafts') ?></p>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- SEO Analysis Tab -->
                                        <?php include('includes/tab_seo_analysis.php'); ?>
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
                <div class="col-md-7">
                    <div class="editor-toolbar-footer">
                        <div class="btn-group mleft5">
                            <button type="button" class="btn btn-primary ai-tool-btn"><i class="fa fa-magic"></i> <?= _l('ai_tools') ?></button>
                        </div>
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

<!-- Publish Modal -->
<div class="modal fade" id="publish-modal" tabindex="-1" role="dialog" aria-labelledby="publish-modal-label" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="publish-modal-label"><i class="fa fa-globe"></i> <?= _l('publish_content') ?></h4>
            </div>
            <div class="modal-body">
                <div id="publish-content-container">
                    <?php include('includes/tab_publish.php'); ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?= _l('close') ?></button>
            </div>
        </div>
    </div>
</div>

<!-- Main editor content ends here -->
</div>
<?php init_tail(); ?>

<!-- Load Chart.js library -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
<!-- JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<!-- Load Editor JavaScript Files -->
<script src="<?= module_dir_url(TOPICS_MODULE_NAME, 'assets/js/ultimate_editor_fn.js?v='.time()) ?>"></script>
<script src="<?= module_dir_url(TOPICS_MODULE_NAME, 'assets/js/ultimate_editor_exec.js?v='.time()) ?>"></script>
<script src="<?= module_dir_url(TOPICS_MODULE_NAME, 'assets/js/ultimate_editor_presents.js?v='.time()) ?>"></script>
<script src="<?= module_dir_url(TOPICS_MODULE_NAME, 'assets/js/ultimate_editor.js?v='.time()) ?>"></script>
<!-- Load publish functionality -->
<script src="<?= module_dir_url(TOPICS_MODULE_NAME, 'assets/js/ultimate_editor_publish.js?v='.time()) ?>"></script>
<script src="<?= module_dir_url(TOPICS_MODULE_NAME, 'assets/js/ultimate_editor_actionButtons.js?v='.time()) ?>"></script>

<!-- Load custom CSS files -->
<link href="<?= module_dir_url(TOPICS_MODULE_NAME, 'assets/css/publish_tab.css?v='.time()) ?>" rel="stylesheet">

<!-- Initialize app.lang for translations -->
<script>
    // Initialize app.lang object if it doesn't exist
    if (typeof app === 'undefined') {
        window.app = {};
    }
    if (!app.lang) {
        app.lang = {};
    }
    
    // Add language strings needed for JavaScript
    app.lang.preview_mode = '<?php echo _l('preview_mode'); ?>';
    app.lang.you_are_previewing = '<?php echo _l('you_are_previewing'); ?>';
    app.lang.use_this_draft = '<?php echo _l('use_this_draft'); ?>';
    app.lang.cancel_preview = '<?php echo _l('cancel_preview'); ?>';
    app.lang.preview_mode_canceled = '<?php echo _l('preview_mode_canceled'); ?>';
    app.lang.unsaved_changes_will_be_lost = '<?php echo _l('unsaved_changes_will_be_lost'); ?>';
    app.lang.loading_drafts = '<?php echo _l('loading_drafts'); ?>';
    app.lang.no_drafts_found = '<?php echo _l('no_drafts_found'); ?>';
    app.lang.error_loading_draft = '<?php echo _l('error_loading_draft'); ?>';
    app.lang.draft_saved_successfully = '<?php echo _l('draft_saved_successfully'); ?>';
    app.lang.draft_loaded_successfully = '<?php echo _l('draft_loaded_successfully'); ?>';
    app.lang.please_enter_draft_title = '<?php echo _l('please_enter_draft_title'); ?>';
    app.lang.please_enter_draft_description = '<?php echo _l('please_enter_draft_description'); ?>';
</script>

<!-- Required Hidden Fields -->
<input type="hidden" id="topicid" value="<?= isset($topic) ? $topic->id : (isset($topic_id) ? $topic_id : ''); ?>">
<input type="hidden" id="current-draft-id" value="<?= isset($active_draft) ? $active_draft->id : ''; ?>">
<input type="hidden" id="current-topic_id" value="<?= isset($topic) ? $topic->topicid : ''; ?>">
<!-- Hidden field to store full draft data for persistence across page refreshes -->
<input type="hidden" id="full-draft-data" value="<?= isset($active_draft) ? htmlspecialchars(json_encode($active_draft), ENT_QUOTES, 'UTF-8') : ''; ?>">

<!-- Editor Initialization Script -->
<script>

    // Workflow configuration from action button
    var workflowConfig = {
        workflow_id: 'f9ded49a-a546-44f8-9025-2c494397c35d',
        button_id: '15',
        target_type: 'BuildPostStructure',
        target_state: 'BuildPostStructure_B_Begin',
        action_command: 'WRITE_DRAFT'
    };
    
    var module_dir_url = '<?= module_dir_url('topics'); ?>';
    
    var admin_url = '<?= admin_url(); ?>';
    
    // Theo dõi đã khởi tạo chưa
    var formatButtonsInitialized = false;
        
    // Tags handling
    function renderTags() {
        const tagsInput = $('#draft-tags');
        const tagsContainer = $('#tags-container');
        
        if (!tagsInput.length || !tagsContainer.length) return;
        
        const tagsValue = tagsInput.val();
        const tags = tagsValue.split(',').map(tag => tag.trim()).filter(Boolean);
        
        // Clear the container
        tagsContainer.empty();
        
        // Add each tag as a clickable item
        tags.forEach(tag => {
            const tagItem = $(`
                <div class="tag-item" title="Click to use as SEO target keyword">
                    <span class="tag-text">${tag}</span>
                    <span class="tag-remove">&times;</span>
                </div>
            `);
            
            tagsContainer.append(tagItem);
        });
    }
    
    // Initialize editor on page load
    $(document).ready(function() {
        initializeEditor();
        
        // Bind handlers for tag removal
        $(document).on('click', '.tag-remove', function() {
            const tag = $(this).prev('.tag-text').text();
            removeTag(tag);
        });
        
        // Bind handlers for setting tag as SEO target keyword
        $(document).on('click', '.tag-text', function() {
            const tag = $(this).text();
            
            // Set this tag as the SEO target keyword
            $('#seo-target-keyword').val(tag);
            
            // Show notification
            alert_float('success', 'Tag "' + tag + '" set as SEO target keyword');
            
            // Switch to SEO analysis tab
            $('a[href="#tab_seo_analysis"]').tab('show');
            
            // Reset SEO checklist to loading state
            if (typeof resetSEOChecklist === 'function') {
                resetSEOChecklist();
            }
            
            // Trigger SEO analysis
            analyzeSEO();
        });
    });
</script>


<!-- Script Config -->
<script>
    // // Workflow configuration 
    // var workflowConfig = {
    //     workflow_id: '<?php echo $button->workflow_id; ?>',
    //     button_id: '<?php echo $button->id; ?>',
    //     target_type: '<?php echo $button->rel_type; ?>',
    //     target_state: '<?php echo $button->rel_state; ?>',
    //     action_command: '<?php echo $button->command; ?>'
    // };
    
    // // Helper function to safely get editor content
    // function safeGetEditorContent() {
    //     if (typeof tinymce !== 'undefined' && tinymce.get('editor-content')) {
    //         return tinymce.get('editor-content').getContent();
    //     }
    //     return '';
    // }
</script>