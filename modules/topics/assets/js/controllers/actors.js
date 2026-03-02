/**
 * Topic Controller Actors JavaScript
 * Handles AJAX requests and UI interactions for controller actors
 */

// Initialize actors management
$(document).ready(function() {
    // If we're on the edit page, load actors
    if ($('#controller-form').length && $('.panel_s input[name="id"]').length) {
        const controllerId = $('.panel_s input[name="id"]').val();
        if (controllerId) {
            loadActors(controllerId);
        }
    }
    
    // Initialize sortable for actors
    initActorsSortable();
    
    // Bind click handlers for actor actions
    bindActorEvents();
    
    // Initialize modal for actor add/edit
    initActorModal();
});

/**
 * Initialize sortable functionality for actors
 */
function initActorsSortable() {
    if ($('#actors_list').length) {
        var el = document.getElementById('actors_list');
        if (el) {
            Sortable.create(el, {
                handle: '.move-actor',
                animation: 150,
                onEnd: function() {
                    // Update priorities after drag & drop
                    updateActorPriorities();
                }
            });
        }
    }
}

/**
 * Bind click events for actor actions
 */
function bindActorEvents() {
    // Add actor button
    $(document).on('click', '#add_actor_btn', function(e) {
        e.preventDefault();
        openActorModal();
    });
    
    // Edit actor button
    $(document).on('click', '.edit-actor', function(e) {
        e.preventDefault();
        const actorId = $(this).data('id');
        openActorModal(actorId);
    });
    
    // Delete actor button
    $(document).on('click', '.delete-actor', function(e) {
        e.preventDefault();
        const actorId = $(this).data('id');
        deleteActor(actorId);
    });
    
    // Actor status toggle
    $(document).on('change', '.actor-status', function() {
        const actorId = $(this).data('id');
        const isActive = $(this).prop('checked') ? 1 : 0;
        updateActorStatus(actorId, isActive);
    });
    
    // Save actor button in modal
    $(document).on('click', '#saveActorBtn', function() {
        saveActor();
    });
}

/**
 * Initialize the actor modal
 */
function initActorModal() {
    // Make the modal draggable
    if ($.fn.draggable) {
        $('#actorModal .modal-dialog').draggable({
            handle: '.modal-header'
        });
    }
    
    // Reset form when modal is closed
    $('#actorModal').on('hidden.bs.modal', function() {
        resetActorForm();
    });
    
    // Initialize TinyMCE when modal is shown
    $('#actorModal').on('shown.bs.modal', function() {
        initTinyMCE();
    });
    
    // Handle enter key in modal inputs (except in TinyMCE)
    $('#actor_form input').keypress(function(e) {
        if (e.which === 13) {
            e.preventDefault();
            saveActor();
        }
    });
}

/**
 * Initialize TinyMCE for actor description
 */
function initTinyMCE() {
    // First check if TinyMCE is already initialized
    if (typeof tinymce !== 'undefined') {
        // Destroy existing instance if it exists
        if (tinymce.get('actor_description')) {
            tinymce.get('actor_description').destroy();
        }
        
        // Initialize TinyMCE
        tinymce.init({
            selector: '#actor_description',
            height: 200,
            menubar: false,
            plugins: [
                'advlist autolink lists link image charmap print preview anchor',
                'searchreplace visualblocks code fullscreen',
                'insertdatetime media table paste code help wordcount'
            ],
            toolbar: 'undo redo | formatselect | ' +
                'bold italic backcolor | alignleft aligncenter ' +
                'alignright alignjustify | bullist numlist outdent indent | ' +
                'removeformat | help',
            content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:14px }',
            setup: function(editor) {
                editor.on('Change', function() {
                    editor.save(); // Update the textarea value
                });
                // Set content after editor is initialized
                editor.on('init', function() {
                    // If we have pending description content, set it now
                    if (window.pendingActorDescription) {
                        editor.setContent(window.pendingActorDescription);
                    }
                });
            }
        });
    }
}

/**
 * Load actors for a controller
 * @param {number} controllerId Controller ID
 */
function loadActors(controllerId) {
    $.ajax({
        url: admin_url + 'topics/controllers/get_actors',
        type: 'GET',
        data: {
            controller_id: controllerId
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#actors_list').html(response.html);
                if (response.count > 0) {
                    $('#no-actors-message').hide();
                } else {
                    $('#no-actors-message').show();
                }
                initActorsSortable();
            } else {
                alert_float('danger', response.message || 'Error loading actors');
            }
        },
        error: function() {
            alert_float('danger', 'Error connecting to server');
        }
    });
}

/**
 * Open the actor modal for add or edit
 * @param {number|null} actorId Actor ID for edit mode, null for add mode
 */
function openActorModal(actorId = null) {
    resetActorForm();
    
    // Set the controller ID
    const controllerId = $('.panel_s input[name="id"]').val();
    $('#actor_controller_id').val(controllerId);
    
    if (actorId) {
        // Edit mode
        $('#actorModalLabel').text(app.lang.edit_actor);
        
        // Store actor ID for later use
        window.currentEditActorId = actorId;
        
        // Fetch actor data
        $.ajax({
            url: admin_url + 'topics/controllers/get_actor',
            type: 'GET',
            data: {
                id: actorId
            },
            dataType: 'json',
            success: function(response) {
                if (response.success && response.data) {
                    const actor = response.data;
                    $('#actor_id').val(actor.id);
                    $('#actor_name').val(actor.name);
                    
                    // Store the description to be set after TinyMCE initialization
                    window.pendingActorDescription = actor.description;
                    
                    // Set the textarea value in case TinyMCE isn't loaded yet
                    $('#actor_description').val(actor.description);
                    
                    // If TinyMCE is already initialized, set content now
                    if (typeof tinymce !== 'undefined' && tinymce.get('actor_description')) {
                        tinymce.get('actor_description').setContent(actor.description);
                    }
                    
                    $('#actor_priority').val(actor.priority);
                    $('#actor_active').prop('checked', actor.active == 1);
                    
                    $('#actorModal').modal('show');
                } else {
                    alert_float('danger', response.message || 'Error loading actor data');
                }
            },
            error: function() {
                alert_float('danger', 'Error connecting to server');
            }
        });
    } else {
        // Add mode
        $('#actorModalLabel').text(app.lang.add_actor);
        window.currentEditActorId = null;
        window.pendingActorDescription = '';
        $('#actorModal').modal('show');
    }
}

/**
 * Save actor data
 */
function saveActor() {
    // Validate required fields
    if (!$('#actor_name').val().trim()) {
        alert_float('warning', app.lang.name_required);
        $('#actor_name').focus();
        return;
    }
    
    // Update TinyMCE content before submitting
    if (typeof tinymce !== 'undefined' && tinymce.get('actor_description')) {
        tinymce.get('actor_description').save();
    }
    
    const form = $('#actor_form');
    const formData = form.serialize();
    
    // Disable save button
    $('#saveActorBtn').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> ' + app.lang.saving);
    
    $.ajax({
        url: admin_url + 'topics/controllers/save_actor',
        type: 'POST',
        data: formData,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#actorModal').modal('hide');
                alert_float('success', response.message);
                loadActors($('#actor_controller_id').val());
            } else {
                alert_float('danger', response.message || 'Error saving actor');
            }
        },
        error: function() {
            alert_float('danger', 'Error connecting to server');
        },
        complete: function() {
            // Re-enable save button
            $('#saveActorBtn').prop('disabled', false).html(app.lang.save);
        }
    });
}

/**
 * Delete an actor
 * @param {number} actorId Actor ID
 */
function deleteActor(actorId) {
    if (confirm(app.lang.confirm_action_prompt)) {
        $.ajax({
            url: admin_url + 'topics/controllers/delete_actor',
            type: 'POST',
            data: {
                id: actorId
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert_float('success', response.message);
                    loadActors($('.panel_s input[name="id"]').val());
                } else {
                    alert_float('danger', response.message || 'Error deleting actor');
                }
            },
            error: function() {
                alert_float('danger', 'Error connecting to server');
            }
        });
    }
}

/**
 * Update actor active status
 * @param {number} actorId Actor ID
 * @param {number} isActive Active status (1 or 0)
 */
function updateActorStatus(actorId, isActive) {
    $.ajax({
        url: admin_url + 'topics/controllers/update_actor_status',
        type: 'POST',
        data: {
            id: actorId,
            active: isActive ? 1 : 0
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                alert_float('success', response.message);
            } else {
                // Revert the checkbox if the update failed
                $('#actor_status_' + actorId).prop('checked', !isActive);
                alert_float('danger', response.message || 'Error updating status');
            }
        },
        error: function(xhr, status, error) {
            // Revert the checkbox on error
            $('#actor_status_' + actorId).prop('checked', !isActive);
            console.error('Error updating status:', error);
            alert_float('danger', 'Error connecting to server: ' + error);
        }
    });
}

/**
 * Update actor priorities based on current order
 */
function updateActorPriorities() {
    const actors = [];
    const controllerId = $('.panel_s input[name="id"]').val();
    
    if (!controllerId) {
        console.error('Controller ID not found');
        return;
    }
    
    // Collect all actor IDs in current order
    $('#actors_list .actor-container').each(function(index) {
        actors.push($(this).data('id'));
    });
    
    if (actors.length === 0) {
        return;
    }
    
    $.ajax({
        url: admin_url + 'topics/controllers/update_actor_priorities',
        type: 'POST',
        data: {
            actors: JSON.stringify(actors)
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                alert_float('success', response.message);
                loadActors(controllerId);
            } else {
                alert_float('danger', response.message || 'Error updating priorities');
            }
        },
        error: function() {
            alert_float('danger', 'Error connecting to server');
        }
    });
}

/**
 * Reset the actor form
 */
function resetActorForm() {
    $('#actor_id').val('');
    $('#actor_name').val('');
    
    // Reset TinyMCE content
    if (typeof tinymce !== 'undefined' && tinymce.get('actor_description')) {
        tinymce.get('actor_description').setContent('');
    } else {
        $('#actor_description').val('');
    }
    
    $('#actor_priority').val(0);
    $('#actor_active').prop('checked', true);
    $('#actor_response').html('');
} 