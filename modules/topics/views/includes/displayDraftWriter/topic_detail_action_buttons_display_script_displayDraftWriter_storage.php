<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<script>
// Draft Writer Storage Functionality
window.DraftWriter.storage = {
    saveInterval: null,
    autoSaveEnabled: true,
    lastSaved: null,
    
    // Storage methods
    saveDraft: function(content) {
        return this.saveDraftToLocalStorage(content);
    },
    loadDraft: function() {
        return this.loadDraftFromLocalStorage();
    },
    clearDraft: function() {
        return this.clearDraftFromLocalStorage();
    },
    
    // Local storage methods
    saveDraftToLocalStorage: function(content) {
        try {
            localStorage.setItem('draft_' + window.DraftWriter.topic_id, JSON.stringify(content));
            this.lastSaved = new Date();
            this.updateLastSavedStatus();
            return true;
        } catch (e) {
            console.error('Error saving draft to local storage:', e);
            return false;
        }
    },
    loadDraftFromLocalStorage: function() {
        try {
            const savedData = localStorage.getItem('draft_' + window.DraftWriter.topic_id);
            return savedData ? JSON.parse(savedData) : null;
        } catch (e) {
            console.error('Error loading draft from local storage:', e);
            return null;
        }
    },
    clearDraftFromLocalStorage: function() {
        try {
            localStorage.removeItem('draft_' + window.DraftWriter.topic_id);
            return true;
        } catch (e) {
            console.error('Error clearing draft from local storage:', e);
            return false;
        }
    },
    
    // Auto-save functionality
    startAutoSave: function() {
        if (this.saveInterval) {
            clearInterval(this.saveInterval);
        }
        
        this.saveInterval = setInterval(() => {
            if (this.autoSaveEnabled && window.DraftWriter.hasChanges) {
                this.saveDraft(this.getCurrentContent());
                window.DraftWriter.hasChanges = false;
            }
        }, 30000); // Auto-save every 30 seconds
    },
    stopAutoSave: function() {
        if (this.saveInterval) {
            clearInterval(this.saveInterval);
            this.saveInterval = null;
        }
    },
    toggleAutoSave: function() {
        this.autoSaveEnabled = !this.autoSaveEnabled;
        $('#auto-save-status').text(this.autoSaveEnabled ? 'ON' : 'OFF')
            .toggleClass('on', this.autoSaveEnabled)
            .toggleClass('off', !this.autoSaveEnabled);
            
        if (this.autoSaveEnabled) {
            this.startAutoSave();
        } else {
            this.stopAutoSave();
        }
    },
    
    // Helper methods
    getCurrentContent: function() {
        return {
            title: $('#draft-title').val(),
            description: $('#draft-description').val(),
            content: tinymce.get('draft-content') ? tinymce.get('draft-content').getContent() : '',
            tags: $('#draft-tags').val(),
            category: $('#draft-category').val(),
            featured_image: $('#featured-image').attr('src') || '',
            last_saved: new Date().toISOString()
        };
    },
    
    // Save and redirect
    saveAndRedirect: function(url) {
        // Save current content
        const content = this.getCurrentContent();
        const saved = this.saveDraft(content);
        
        if (saved) {
            // Stop auto-save before redirecting
            this.stopAutoSave();
            // Redirect to the specified URL
            window.location.href = url;
        } else {
            alert_float('danger', '<?php echo _l('error_saving_draft'); ?>');
        }
    },
    
    // Update last saved status
    updateLastSavedStatus: function() {
        if (this.lastSaved) {
            $('#last-saved-status').text('<?php echo _l('last_saved'); ?>: ' + this.lastSaved.toLocaleTimeString());
        }
    }
}; 
</script> 