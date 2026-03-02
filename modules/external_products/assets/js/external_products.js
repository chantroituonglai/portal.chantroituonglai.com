/**
 * External Products Management Module JavaScript
 */

var externalProductsConfig = {
    mappingUrl: typeof admin_url !== 'undefined' ? admin_url + 'external_products/mapping' : '',
    bulkActionUrl: typeof admin_url !== 'undefined' ? admin_url + 'external_products/bulk_action' : ''
};

var externalProductsLang = {
    processing: (typeof appLang !== 'undefined' && appLang.processing) ? appLang.processing : 'Processing...',
    submit: (typeof appLang !== 'undefined' && appLang.submit) ? appLang.submit : 'Submit'
};


// Initialize when the window is fully loaded
window.onload = function() {
    // Initialize external products functionality
    initExternalProducts();
};

function initExternalProducts() {
    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();
    
    // Initialize select all functionality
    initSelectAll();
    
    // Initialize bulk actions
    initBulkActions();
    
    // Initialize form validation
    initFormValidation();
}

function initSelectAll() {
    $('#select_all').on('change', function() {
        var isChecked = $(this).is(':checked');
        $('tbody input[type="checkbox"]').prop('checked', isChecked);
        toggleBulkActions();
    });
    
    // Individual checkbox change
    $(document).on('change', 'tbody input[type="checkbox"]', function() {
        var totalCheckboxes = $('tbody input[type="checkbox"]').length;
        var checkedCheckboxes = $('tbody input[type="checkbox"]:checked').length;
        
        $('#select_all').prop('checked', totalCheckboxes === checkedCheckboxes);
        toggleBulkActions();
    });
}

function initBulkActions() {
    // Bulk delete
    $('#bulk_delete').on('click', function() {
        var selectedIds = getSelectedIds();
        
        if (selectedIds.length === 0) {
            alert_float('warning', 'Please select items to delete');
            return;
        }
        
        if (confirm('Are you sure you want to delete selected items?')) {
            performBulkAction('delete', selectedIds);
        }
    });
    
    // Bulk sync
    $('#bulk_sync').on('click', function() {
        var selectedIds = getSelectedIds();
        
        if (selectedIds.length === 0) {
            alert_float('warning', 'Please select items to sync');
            return;
        }
        
        performBulkAction('sync', selectedIds);
    });
}

function initFormValidation() {
    // Add mapping form validation
    $('#add_mapping_form').on('submit', function(e) {
        e.preventDefault();
        
        if (validateMappingForm()) {
            submitMappingForm($(this));
        }
    });
    
    // Edit mapping form validation
    $('#edit_mapping_form').on('submit', function(e) {
        e.preventDefault();
        
        if (validateMappingForm()) {
            submitMappingForm($(this));
        }
    });
}

function validateMappingForm() {
    var isValid = true;
    var errors = [];
    
    // Validate SKU
    var sku = $('#sku').val().trim();
    if (!sku) {
        errors.push('SKU is required');
        isValid = false;
    }
    
    // Validate Mapping ID
    var mappingId = $('#mapping_id').val().trim();
    if (!mappingId) {
        errors.push('Mapping ID is required');
        isValid = false;
    }
    
    // Validate Mapping Type
    var mappingType = $('#mapping_type').val();
    if (!mappingType) {
        errors.push('Mapping Type is required');
        isValid = false;
    }
    
    if (!isValid) {
        alert_float('danger', errors.join('<br>'));
    }
    
    return isValid;
}

function submitMappingForm(form) {
    var formData = form.serialize();
    var url = form.attr('action');
    
    $.ajax({
        url: url,
        type: 'POST',
        data: formData,
        beforeSend: function() {
            $('button[type="submit"]').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> ' + externalProductsLang.processing);
        },
        success: function(response) {
            try {
                var result = JSON.parse(response);
                if (result.success) {
                    alert_float('success', result.message);
                    setTimeout(function() {
                        if (externalProductsConfig.mappingUrl) {
                            window.location.href = externalProductsConfig.mappingUrl;
                        }
                    }, 1500);
                } else {
                    alert_float('danger', result.message);
                }
            } catch (e) {
                alert_float('danger', 'An error occurred while processing your request');
            }
        },
        error: function() {
            alert_float('danger', 'An error occurred while processing your request');
        },
        complete: function() {
            $('button[type="submit"]').prop('disabled', false).html(externalProductsLang.submit);
        }
    });
}

function getSelectedIds() {
    var selectedIds = [];
    $('tbody input[type="checkbox"]:checked').each(function() {
        selectedIds.push($(this).val());
    });
    return selectedIds;
}

function toggleBulkActions() {
    var checkedCount = $('tbody input[type="checkbox"]:checked').length;
    if (checkedCount > 0) {
        $('.bulk-actions').addClass('show');
    } else {
        $('.bulk-actions').removeClass('show');
    }
}

function performBulkAction(action, ids) {
    $.ajax({
        url: externalProductsConfig.bulkActionUrl,
        type: 'POST',
        data: {
            action: action,
            ids: ids
        },
        beforeSend: function() {
            $('.bulk-actions button').prop('disabled', true);
        },
        success: function(response) {
            try {
                var result = JSON.parse(response);
                if (result.success) {
                    alert_float('success', result.message);
                    // Reload the table
                    if (typeof table !== 'undefined' && table.ajax) {
                        table.ajax.reload();
                    } else {
                        location.reload();
                    }
                } else {
                    alert_float('danger', result.message);
                }
            } catch (e) {
                alert_float('danger', 'An error occurred while processing your request');
            }
        },
        error: function() {
            alert_float('danger', 'An error occurred while processing your request');
        },
        complete: function() {
            $('.bulk-actions button').prop('disabled', false);
        }
    });
}

// Utility functions
function formatMappingType(type) {
    var types = {
        'fast_barco': 'Fast Barco',
        'aeon_sku': 'AEON SKU',
        'emart': 'Emart',
        'emart_sku': 'Emart SKU',
        'woo': 'WooCommerce',
        'shopify': 'Shopify',
        'magento': 'Magento',
        'amazon': 'Amazon',
        'ebay': 'eBay',
        'other': 'Other'
    };
    return types[type] || type;
}

function formatMappingStatus(status) {
    var statuses = {
        'active': '<span class="mapping-status active">Active</span>',
        'inactive': '<span class="mapping-status inactive">Inactive</span>',
        'pending': '<span class="mapping-status pending">Pending</span>'
    };
    return statuses[status] || status;
}

function formatSyncStatus(status) {
    var statuses = {
        'synced': '<span class="sync-status synced">Synced</span>',
        'pending': '<span class="sync-status pending">Pending</span>',
        'failed': '<span class="sync-status failed">Failed</span>'
    };
    return statuses[status] || status;
}

// Export functions for global use
window.ExternalProducts = {
    init: initExternalProducts,
    validateForm: validateMappingForm,
    submitForm: submitMappingForm,
    getSelectedIds: getSelectedIds,
    performBulkAction: performBulkAction,
    formatMappingType: formatMappingType,
    formatMappingStatus: formatMappingStatus,
    formatSyncStatus: formatSyncStatus
};
