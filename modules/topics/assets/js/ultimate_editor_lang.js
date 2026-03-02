/**
 * Ultimate Editor Language Strings
 * Provides localized strings for editor functionality
 */

var UltimateEditorLang = {
    // Error messages
    editor_not_available: 'Editor is not available. Please refresh the page and try again.',
    editor_recovery_attempt: 'Attempting to recover editor. Please wait a moment and try again.',
    unsaved_changes_warning: 'You have unsaved changes. Are you sure you want to load new content and lose your changes?',
    
    // Success messages
    draft_saved: 'Draft saved successfully',
    draft_loaded: 'Draft loaded successfully',
    content_loaded: 'Content loaded successfully',
    
    // Form validation
    please_enter_draft_title: 'Please enter a draft title',
    please_enter_draft_description: 'Please enter a draft description',
    please_enter_content: 'Please enter some content',
    
    // Workflow related
    workflow_failed: 'Workflow execution failed',
    no_content_returned: 'No content was returned from the workflow',
    error_processing_workflow_response: 'Error processing workflow response',
    
    // Loading messages
    loading_draft: 'Loading draft...',
    creating_new_draft: 'Creating new draft...',
    saving_draft: 'Saving draft...',
    loading_content: 'Loading content...',
    executing_workflow: 'Executing workflow...',
    processing_content: 'Processing content...'
};

/**
 * Get a language string by key
 * @param {string} key - The language key
 * @param {string} defaultValue - Default value if key not found
 * @returns {string} The translated string or default value
 */
function _l(key, defaultValue) {
    if (UltimateEditorLang && UltimateEditorLang[key]) {
        return UltimateEditorLang[key];
    }
    
    return defaultValue || key;
} 