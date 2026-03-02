/**
 * Draft Writer Module - Controllers JavaScript
 * Handles AJAX requests and UI interactions for the controllers
 */

// Helper function to get language string safely
function lang(key, default_text) {
    if (typeof app !== 'undefined' && app.lang && app.lang[key]) {
        return app.lang[key];
    }
    return default_text || key;
}

// Store the login config values on page load for edit page
var storedLoginValues = {};

/**
 * Preserve existing login values on page load for edit page
 */
function preserveExistingLoginValues() {
    // Only run this on edit page
    if ($('#controller-form input[name="id"]').length === 0) {
        return;
    }
    
    console.log('Preserving existing login values on edit page');
    
    // Store all existing login field values
    $('.login-field').each(function() {
        var name = $(this).attr('name');
        var matches = name.match(/login_config\[(.*?)\]/);
        if (matches && matches[1]) {
            storedLoginValues[matches[1]] = $(this).val();
            console.log('Stored value for ' + matches[1] + ': ' + $(this).val());
        }
    });
}

// A. Endpoints for Platform
/**
 * Get platform fields based on selected platform
 * @param {string} platform - The selected platform
 * @param {Object} existingValues - Optional existing login configuration values to preserve
 */
function getPlatformFields(platform, existingValues) {
    if (!platform) {
        return;
    }

    // If we're in edit mode, try to get existing values from fields
    if (!existingValues) {
        existingValues = {};
        // Check if any login fields already exist and collect their values
        $('.login-field').each(function() {
            var name = $(this).attr('name');
            var matches = name.match(/login_config\[(.*?)\]/);
            if (matches && matches[1]) {
                existingValues[matches[1]] = $(this).val();
            }
        });
    }

    // Show loading indicator
    $('#login_fields_container').html('<div class="text-center p-15"><div class="loading-spinner"></div> ' + lang('loading_platform_fields') + '...</div>');
    $('#login_fields_container').slideDown('fast');
    
    // Clear any previous connection status
    $('#connection_status_container').hide();

    $.ajax({
        url: admin_url + 'topics/controllers/get_platform_fields',
        type: 'POST',
        data: {
            platform: platform
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Tạo đối tượng data để chứa thông tin cần thiết cho renderLoginFields
                var data = {
                    name: response.platform_info.name,
                    description: response.platform_info.description || 'Enter login credentials for this platform',
                    fields: {}
                };
                
                // Tạo cấu trúc fields cần thiết cho renderLoginFields
                $.each(response.login_fields, function(index, field_key) {
                    data.fields[field_key] = {
                        type: field_key.indexOf('password') !== -1 ? 'password' : 'text',
                        label: field_key.replace(/_/g, ' ').replace(/\b\w/g, function(l) { return l.toUpperCase(); }),
                        placeholder: 'Enter ' + field_key.replace(/_/g, ' '),
                        help_text: '',
                        value: existingValues[field_key] || '' // Use existing value if available
                    };
                });
                
                renderLoginFields(data);
                
                // Add test connection button with icon and margin
                $('#login_fields_container').append(
                    '<div class="form-group">' +
                    '<button type="button" id="test_connection_btn" class="btn btn-info">' +
                    '<i class="fa fa-plug"></i> ' + lang('test_connection') + '</button>' +
                    '</div>' +
                    '<div id="connection_status_container" style="display:none;"></div>'
                );
                
                // Initialize tooltips
                $('[data-toggle="tooltip"]').tooltip();
                
                // Add event listener for test connection button
                $('#test_connection_btn').on('click', function() {
                    var formData = {};
                    $('.login-field').each(function() {
                        var name = $(this).attr('name');
                        // Extract the key from login_config[key]
                        var matches = name.match(/login_config\[(.*?)\]/);
                        if (matches && matches[1]) {
                            formData[matches[1]] = $(this).val();
                        }
                    });
                    formData.platform = platform;
                    testConnectionTemp(formData);
                });
                
                // Add event listeners for password toggle
                $('.toggle-password').on('click', function() {
                    var input = $($(this).data('target'));
                    if (input.attr('type') === 'password') {
                        input.attr('type', 'text');
                        $(this).html('<i class="fa fa-eye-slash"></i>');
                    } else {
                        input.attr('type', 'password');
                        $(this).html('<i class="fa fa-eye"></i>');
                    }
                });
            } else {
                $('#login_fields_container').html(
                    '<div class="alert alert-danger">' +
                    '<i class="fa fa-exclamation-circle"></i> ' +
                    response.message +
                    '</div>'
                );
            }
        },
        error: function() {
            $('#login_fields_container').html(
                '<div class="alert alert-danger">' +
                '<i class="fa fa-exclamation-circle"></i> ' +
                lang('error_loading_platform_fields') +
                '</div>'
            );
        }
    });
}

/**
 * Render login fields based on platform data
 * @param {Object} data - Platform fields data
 */
function renderLoginFields(data) {
    var html = '<div class="platform-info">';
    html += '<h4>' + data.name + ' ' + lang('login_configuration') + '</h4>';
    html += '<p>' + data.description + '</p>';
    html += '</div>';
    
    $.each(data.fields, function(key, field) {
        html += '<div class="form-group">';
        html += '<label for="' + key + '">' + field.label + '</label>';
        
        if (field.type === 'password') {
            html += '<div class="input-group">';
            html += '<input type="password" class="form-control login-field" id="' + key + '" name="login_config[' + key + ']" placeholder="' + field.placeholder + '" value="' + field.value + '">';
            html += '<span class="input-group-addon toggle-password" data-target="#' + key + '"><i class="fa fa-eye"></i></span>';
            html += '</div>';
        } else {
            html += '<input type="' + field.type + '" class="form-control login-field" id="' + key + '" name="login_config[' + key + ']" placeholder="' + field.placeholder + '" value="' + field.value + '">';
        }
        
        if (field.help_text) {
            html += '<p class="help-block">' + field.help_text + '</p>';
        }
        
        html += '</div>';
    });
    
    $('#login_fields_container').html(html);
}

/**
 * Test connection to platform
 * @param {number} controllerId - The controller ID
 */
function testConnection(controllerId) {
    // Show loading state
    $('#connection_status_container').html('<div class="connection-status warning"><div class="loading-spinner"></div> ' + lang('testing_connection', 'Testing connection...') + '...</div>');
    $('#connection_status_container').slideDown('fast');
    
    // Disable the button and show loading state
    $('#test_connection_edit_btn').prop('disabled', true).html('<div class="loading-spinner"></div> ' + lang('testing', 'Testing') + '...');
    
    $.ajax({
        url: admin_url + 'topics/controllers/test_connection/' + controllerId,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            // Re-enable the button
            $('#test_connection_edit_btn').prop('disabled', false).html('<i class="fa fa-plug"></i> ' + lang('test_connection', 'Test Connection'));
            
            if (response.success) {
                // Create success message
                var successHtml = '<div class="connection-status success">' +
                    '<i class="fa fa-check-circle"></i> ' +
                    '<strong>' + lang('connection_successful', 'Connection successful') + ':</strong> ' + response.message +
                    '</div>';
                
                // Create site information display if available
                if (response.site_info) {
                    // First try to get platform from response, then from hidden field, 
                    // then from the details table, and finally fallback to a default
                    var platform = response.platform || 
                                  $('#platform').val() || 
                                  $('input[name="platform"]').val() ||
                                  // Try to find the platform in the table by looking for the row with "Platform" label
                                  $('table.table-striped tr td.bold').filter(function() {
                                      return $(this).text().trim().toLowerCase() === 'platform';
                                  }).next('td').text().trim() ||
                                  'unknown';
                    
                    successHtml += displaySiteInfo(response.site_info, platform);
                    
                    // Update form field values if they were updated on the server
                    if (response.field_values) {
                        // Update slogan field
                        if (response.field_values.slogan) {
                            $('textarea[name="slogan"]').val(response.field_values.slogan);
                        }
                        
                        // Update logo_url field
                        if (response.field_values.logo_url) {
                            $('input[name="logo_url"]').val(response.field_values.logo_url);
                        }
                        
                        // Add a specific message about updated fields if provided
                        if (response.update_message) {
                            successHtml = '<div class="connection-status success">' +
                                '<i class="fa fa-check-circle"></i> ' +
                                '<strong>' + lang('connection_successful', 'Connection successful') + ':</strong> ' + 
                                response.update_message +
                                '</div>' + successHtml.substring(successHtml.indexOf('</div>') + 6);
                        }
                    }
                }
                
                // Add Quick Save Login button
                successHtml += '<div class="quick-save-container">' +
                    '<button type="button" id="quick_save_login_btn" class="btn btn-success" data-controller-id="' + controllerId + '">' +
                    '<i class="fa fa-save"></i> ' + lang('quick_save_login', 'Quick Save Login') +
                    '</button>' +
                    '<span class="quick-save-help">' + lang('save_login_credentials_help', 'Save login credentials without submitting the entire form') + '</span>' +
                    '</div>';
                
                $('#connection_status_container').html(successHtml);
                
                // If categories were returned, display them
                if (response.categories && response.categories.length > 0) {
                    displayCategories(response.categories);
                }
                
                // Add event listener for quick save button
                $('#quick_save_login_btn').on('click', function() {
                    var controllerId = $(this).data('controller-id');
                    quickSaveLogin(controllerId);
                });
            } else {
                $('#connection_status_container').html(
                    '<div class="connection-status danger">' +
                    '<i class="fa fa-times-circle"></i> ' +
                    '<strong>' + lang('connection_failed', 'Connection failed') + ':</strong> ' + response.message +
                    '</div>'
                );
            }
        },
        error: function() {
            // Re-enable the button
            $('#test_connection_edit_btn').prop('disabled', false).html('<i class="fa fa-plug"></i> ' + lang('test_connection', 'Test Connection'));
            
            $('#connection_status_container').html(
                '<div class="connection-status danger">' +
                '<i class="fa fa-times-circle"></i> ' +
                '<strong>' + lang('connection_failed', 'Connection failed') + ':</strong> ' + lang('unknown_error', 'An unknown error occurred') +
                '</div>'
            );
        }
    });
}

/**
 * Display categories from a successful connection test
 * @param {Array} categories - The categories returned from the platform
 */
function displayCategories(categories) {
    if (!categories || categories.length === 0) {
        return;
    }
    
    // Add search box at the top
    var searchHtml = '<div class="category-search-container mb-15">' +
        '<div class="input-group">' +
        '<input type="text" id="category_search" class="form-control" placeholder="' + lang('search_categories', 'Search categories...') + '">' +
        '<div class="input-group-addon"><i class="fa fa-search"></i></div>' +
        '</div>' +
        '</div>';
    
    // Build the category tree
    var categoriesTree = buildCategoryTree(categories);
    var categoriesHtml = searchHtml + '<div class="category-tree">';
    categoriesHtml += renderCategoryTree(categoriesTree);
    categoriesHtml += '</div>';
    
    // Update the categories container
    $('#categories_list').html(categoriesHtml);
    $('#categories_container').slideDown('fast');
    
    // Add event handlers for expand/collapse
    initCategoryTreeEvents();
    
    // Add search functionality
    initCategorySearch();
}

/**
 * Build a hierarchical tree from flat categories array
 * @param {Array} categories - Flat array of categories
 * @return {Array} - Tree structure of categories
 */
function buildCategoryTree(categories) {
    // First pass: create lookup object and add children array
    var lookup = {};
    var rootCategories = [];
    
    categories.forEach(function(category) {
        // Clone to avoid modifying original
        var cat = Object.assign({}, category);
        cat.children = [];
        lookup[cat.id] = cat;
        
        // If no parent or parent is 0, it's a root category
        if (!cat.parent || cat.parent === 0 || cat.parent === '0') {
            rootCategories.push(cat);
        }
    });
    
    // Second pass: build tree
    categories.forEach(function(category) {
        if (category.parent && category.parent !== 0 && category.parent !== '0') {
            // If parent exists in our lookup, add this category as its child
            if (lookup[category.parent]) {
                lookup[category.parent].children.push(lookup[category.id]);
            } else {
                // If parent doesn't exist, treat as root
                rootCategories.push(lookup[category.id]);
            }
        }
    });
    
    return rootCategories;
}

/**
 * Render category tree as HTML
 * @param {Array} categoryTree - Hierarchical tree of categories
 * @param {number} level - Nesting level (for indentation)
 * @return {string} - HTML for category tree
 */
function renderCategoryTree(categoryTree, level = 0) {
    if (!categoryTree || categoryTree.length === 0) {
        return '';
    }
    
    var html = '<ul class="category-tree-list' + (level === 0 ? ' root-list' : '') + '">';
    
    categoryTree.forEach(function(category) {
        var hasChildren = category.children && category.children.length > 0;
        var categoryId = category.id;
        var categoryName = category.name;
        var categoryCount = category.count || 0;
        
        html += '<li class="category-item" data-category-id="' + categoryId + '" data-category-name="' + categoryName.toLowerCase() + '">';
        
        // Add expand/collapse if has children
        if (hasChildren) {
            html += '<span class="toggle-category"><i class="fa fa-plus-square-o"></i></span>';
        } else {
            html += '<span class="toggle-category empty"><i class="fa fa-square-o"></i></span>';
        }
        
        // Add checkbox
        html += '<div class="checkbox checkbox-primary category-checkbox">' +
            '<input type="checkbox" id="category_' + categoryId + '" name="selected_categories[]" value="' + categoryId + '">' +
            '<label for="category_' + categoryId + '">' + categoryName + 
            ' <span class="category-count">(' + categoryCount + ')</span></label>' +
            '</div>';
        
        // Add children if any
        if (hasChildren) {
            html += '<div class="category-children" style="display:none;">';
            html += renderCategoryTree(category.children, level + 1);
            html += '</div>';
        }
        
        html += '</li>';
    });
    
    html += '</ul>';
    return html;
}

/**
 * Initialize category tree events (expand/collapse)
 */
function initCategoryTreeEvents() {
    // Toggle category children when clicking the toggle button
    $('.toggle-category').not('.empty').on('click', function() {
        var $this = $(this);
        var $icon = $this.find('i');
        var $categoryItem = $this.closest('.category-item');
        var $children = $categoryItem.find('> .category-children');
        
        if ($children.is(':visible')) {
            $children.slideUp('fast');
            $icon.removeClass('fa-minus-square-o').addClass('fa-plus-square-o');
        } else {
            $children.slideDown('fast');
            $icon.removeClass('fa-plus-square-o').addClass('fa-minus-square-o');
        }
    });
    
    // Check/uncheck all children when parent is checked/unchecked
    $('.category-checkbox input[type="checkbox"]').on('change', function() {
        var $this = $(this);
        var checked = $this.prop('checked');
        var $categoryItem = $this.closest('.category-item');
        
        // Check/uncheck all children
        $categoryItem.find('.category-children input[type="checkbox"]').prop('checked', checked);
    });
}

/**
 * Initialize category search functionality
 */
function initCategorySearch() {
    $('#category_search').on('keyup', function() {
        var searchText = $(this).val().toLowerCase();
        
        if (searchText.length === 0) {
            // Show all categories if search is empty
            $('.category-item').show();
            $('.category-children').hide(); // Collapse all
            $('.toggle-category i.fa-minus-square-o').removeClass('fa-minus-square-o').addClass('fa-plus-square-o');
            return;
        }
        
        // Hide all categories first
        $('.category-item').hide();
        
        // Find matching categories
        $('.category-item').each(function() {
            var $item = $(this);
            var categoryName = $item.data('category-name');
            
            if (categoryName && categoryName.indexOf(searchText) !== -1) {
                // Show this category
                $item.show();
                
                // Show all parents of this category
                var $parent = $item.parent().closest('.category-item');
                while ($parent.length) {
                    $parent.show();
                    // Expand parent
                    var $parentChildren = $parent.find('> .category-children');
                    $parentChildren.show();
                    $parent.find('> .toggle-category i.fa-plus-square-o')
                        .removeClass('fa-plus-square-o')
                        .addClass('fa-minus-square-o');
                    
                    // Move up the tree
                    $parent = $parent.parent().closest('.category-item');
                }
                
                // Show children if any
                var $children = $item.find('> .category-children');
                if ($children.length) {
                    $children.show();
                    $item.find('> .toggle-category i.fa-plus-square-o')
                        .removeClass('fa-plus-square-o')
                        .addClass('fa-minus-square-o');
                }
            }
        });
    });
}

/**
 * Temporary test connection function for create form
 * @param {Object} formData - Platform login form data
 */
function testConnectionTemp(formData) {
    // Show loading state
    $('#connection_status_container').html('<div class="connection-status warning"><div class="loading-spinner"></div> ' + lang('testing_connection', 'Testing connection...') + '</div>');
    $('#connection_status_container').slideDown('fast');
    
    // Disable the button during the test
    $('#test_connection_btn').prop('disabled', true).html('<div class="loading-spinner"></div> ' + lang('testing', 'Testing') + '...');
    
    $.ajax({
        url: admin_url + 'topics/controllers/test_connection',
        type: 'POST',
        data: formData,
        dataType: 'json',
        success: function(response) {
            // Re-enable the button
            $('#test_connection_btn').prop('disabled', false).html('<i class="fa fa-plug"></i> ' + lang('test_connection', 'Test Connection'));
            
            if (response.success) {
                // Get platform from form
                var platform = $('#platform').val() || 'unknown';
                
                // Create success message
                var successHtml = '<div class="connection-status success">' +
                    '<i class="fa fa-check-circle"></i> ' +
                    '<strong>' + lang('connection_successful', 'Connection successful') + ':</strong> ' + response.message +
                    '</div>';
                
                // Add site information if available
                if (response.site_info) {
                    successHtml += displaySiteInfo(response.site_info, platform);
                }
                
                $('#connection_status_container').html(successHtml);
                
                // If categories were returned, display them
                if (response.categories && response.categories.length > 0) {
                    displayCategories(response.categories);
                }
                
                // Add "Create Controller Now" button for convenience
                var form = $('#controller-form');
                if (form.length) {
                    $('#connection_status_container').append(
                        '<div class="form-group mtop10">' +
                        '<button type="button" id="create_now_btn" class="btn btn-success">' +
                        '<i class="fa fa-check"></i> ' + lang('create_controller_now', 'Create Controller Now') +
                        '</button>' +
                        '</div>'
                    );
                    
                    // Add event listener for "Create Controller Now" button
                    $('#create_now_btn').on('click', function() {
                        form.submit();
                    });
                }
            } else {
                $('#connection_status_container').html(
                    '<div class="connection-status danger">' +
                    '<i class="fa fa-times-circle"></i> ' +
                    '<strong>' + lang('connection_failed', 'Connection failed') + ':</strong> ' + response.message +
                    '</div>'
                );
            }
        },
        error: function() {
            // Re-enable the button
            $('#test_connection_btn').prop('disabled', false).html('<i class="fa fa-plug"></i> ' + lang('test_connection', 'Test Connection'));
            
            $('#connection_status_container').html(
                '<div class="connection-status danger">' +
                '<i class="fa fa-times-circle"></i> ' +
                '<strong>' + lang('connection_failed', 'Connection failed') + ':</strong> ' + lang('unknown_error', 'An unknown error occurred') +
                '</div>'
            );
        }
    });
}

/**
 * Quick save login credentials without submitting the entire form
 * @param {number} controllerId - The controller ID
 */
function quickSaveLogin(controllerId) {
    // Collect all login fields
    var loginConfig = {};
    $('.login-field').each(function() {
        var name = $(this).attr('name');
        // Extract the key from login_config[key]
        var matches = name.match(/login_config\[(.*?)\]/);
        if (matches && matches[1]) {
            loginConfig[matches[1]] = $(this).val();
        }
    });

    // Get platform
    var platform = $('#platform').val() || 
                   $('input[name="platform"]').val() ||
                   // Try to find the platform in the table by looking for the row with "Platform" label
                   $('table.table-striped tr td.bold').filter(function() {
                       return $(this).text().trim().toLowerCase() === 'platform';
                   }).next('td').text().trim() ||
                   'unknown';
    
    // Show loading state
    $('#quick_save_login_btn').prop('disabled', true).html('<div class="loading-spinner"></div> Saving...');
    
    $.ajax({
        url: admin_url + 'topics/controllers/quick_save_login/' + controllerId,
        type: 'POST',
        data: {
            login_config: loginConfig,
            platform: platform
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Show success message
                var $container = $('#quick_save_login_btn').closest('.quick-save-container');
                $container.html(
                    '<div class="alert alert-success">' +
                    '<i class="fa fa-check-circle"></i> ' +
                    'Login credentials saved successfully!' +
                    '</div>'
                );
                
                // Fade out the message after 3 seconds
                setTimeout(function() {
                    $container.fadeOut('slow', function() {
                        $container.html(
                            '<button type="button" id="quick_save_login_btn" class="btn btn-success" data-controller-id="' + controllerId + '">' +
                            '<i class="fa fa-save"></i> Quick Save Login' +
                            '</button>' +
                            '<span class="quick-save-help">Save login credentials without submitting the entire form</span>'
                        ).fadeIn('fast');
                        
                        // Re-add event listener
                        $('#quick_save_login_btn').on('click', function() {
                            quickSaveLogin(controllerId);
                        });
                    });
                }, 3000);
            } else {
                // Show error message
                var $container = $('#quick_save_login_btn').closest('.quick-save-container');
                $container.html(
                    '<div class="alert alert-danger">' +
                    '<i class="fa fa-times-circle"></i> ' +
                    'Error: ' + (response.message || 'Failed to save login credentials') +
                    '</div>'
                );
                
                // Fade out the message after 3 seconds
                setTimeout(function() {
                    $container.fadeOut('slow', function() {
                        $container.html(
                            '<button type="button" id="quick_save_login_btn" class="btn btn-success" data-controller-id="' + controllerId + '">' +
                            '<i class="fa fa-save"></i> Quick Save Login' +
                            '</button>' +
                            '<span class="quick-save-help">Save login credentials without submitting the entire form</span>'
                        ).fadeIn('fast');
                        
                        // Re-add event listener
                        $('#quick_save_login_btn').on('click', function() {
                            quickSaveLogin(controllerId);
                        });
                    });
                }, 3000);
            }
        },
        error: function() {
            // Show error message
            var $container = $('#quick_save_login_btn').closest('.quick-save-container');
            $container.html(
                '<div class="alert alert-danger">' +
                '<i class="fa fa-times-circle"></i> ' +
                'Error: Failed to save login credentials due to a server error' +
                '</div>'
            );
            
            // Fade out the message after 3 seconds
            setTimeout(function() {
                $container.fadeOut('slow', function() {
                    $container.html(
                        '<button type="button" id="quick_save_login_btn" class="btn btn-success" data-controller-id="' + controllerId + '">' +
                        '<i class="fa fa-save"></i> Quick Save Login' +
                        '</button>' +
                        '<span class="quick-save-help">Save login credentials without submitting the entire form</span>'
                    ).fadeIn('fast');
                    
                    // Re-add event listener
                    $('#quick_save_login_btn').on('click', function() {
                        quickSaveLogin(controllerId);
                    });
                });
            }, 3000);
        }
    });
}

/**
 * Display site information in an attractive way
 * @param {Object} siteInfo - Information about the site
 * @param {string} platform - Platform name
 * @return {string} HTML for displaying site information
 */
function displaySiteInfo(siteInfo, platform) {
    if (!siteInfo) return '';
    
    var platformIcon = '';
    var platformClass = '';
    var platformTitle = '';
    
    // Set platform specific icons and classes
    if (platform && typeof platform === 'string') {
        switch (platform.toLowerCase()) {
            case 'wordpress':
                platformIcon = 'fa-wordpress';
                platformClass = 'wordpress-info';
                platformTitle = lang('wordpress_info', 'WordPress Information');
                break;
            case 'haravan':
                platformIcon = 'fa-shopping-cart';
                platformClass = 'haravan-info';
                platformTitle = lang('haravan_info', 'Haravan Information');
                break;
            default:
                platformIcon = 'fa-globe';
                platformClass = 'platform-info';
                platformTitle = lang('site_information', 'Site Information');
        }
    } else {
        // Default values if platform is undefined
        platformIcon = 'fa-globe';
        platformClass = 'platform-info';
        platformTitle = lang('site_information', 'Site Information');
    }
    
    var html = '<div class="site-info-container ' + platformClass + '">';
    
    // Site header with title & logo if available
    html += '<div class="site-info-header">';
    html += '<h4><i class="fa ' + platformIcon + '"></i> ' + (siteInfo.title || siteInfo.name || platformTitle) + '</h4>';
    html += '</div>';
    
    // Site details
    html += '<div class="site-info-body">';
    html += '<table class="table table-bordered">';
    
    // Always show site URL if available
    if (siteInfo.url) {
        html += '<tr><td><strong>' + lang('site_url', 'Site URL') + ':</strong></td><td><a href="' + siteInfo.url + '" target="_blank">' + siteInfo.url + '</a></td></tr>';
    }
    
    // Always show description if available
    if (siteInfo.description) {
        html += '<tr><td><strong>' + lang('description', 'Description') + ':</strong></td><td>' + siteInfo.description + '</td></tr>';
    }
    
    // Create different displays based on platform
    if (platform.toLowerCase() === 'wordpress') {
        // WordPress specific display
        if (siteInfo.categories_count) {
            html += '<tr><td><strong>' + lang('categories', 'Categories') + ':</strong></td><td>' + siteInfo.categories_count + '</td></tr>';
        }
        if (siteInfo.posts_count) {
            html += '<tr><td><strong>' + lang('posts', 'Posts') + ':</strong></td><td>' + siteInfo.posts_count + '</td></tr>';
        }
        if (siteInfo.pages_count) {
            html += '<tr><td><strong>' + lang('pages', 'Pages') + ':</strong></td><td>' + siteInfo.pages_count + '</td></tr>';
        }
    } else {
        // Generic display for other platforms
        for (var key in siteInfo) {
            if (siteInfo.hasOwnProperty(key) && siteInfo[key] && 
                typeof siteInfo[key] !== 'object' && 
                key !== 'url' && key !== 'description' && 
                key !== 'title' && key !== 'name') {
                
                var label = lang(key, key.charAt(0).toUpperCase() + key.slice(1).replace(/_/g, ' '));
                html += '<tr><td><strong>' + label + ':</strong></td><td>' + siteInfo[key] + '</td></tr>';
            }
        }
    }
    
    html += '</table>';
    html += '</div>'; // .site-info-body
    html += '</div>'; // .site-info-container
    
    return html;
}

/**
 * Get platform categories
 * @param {number} controllerId - The controller ID
 */
function getPlatformCategories(controllerId) {
    $.ajax({
        url: admin_url + 'topics/controllers/get_platform_categories/' + controllerId,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Handle categories data
                console.log(response.data);
            } else {
                alert(response.message);
            }
        },
        error: function() {
            alert(lang('error_loading_categories'));
        }
    });
}

// B. Endpoints for Writing Style
/**
 * Get writing styles
 */
function getWritingStyles() {
    $.ajax({
        url: admin_url + 'topics/controllers/get_writing_styles',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Populate writing style dropdown
                var options = '';
                $.each(response.data, function(key, name) {
                    options += '<option value="' + key + '">' + name + '</option>';
                });
                $('#writing_style').html(options);
                $('#writing_style').selectpicker('refresh');
            } else {
                console.error(response.message);
            }
        },
        error: function() {
            console.error(lang('error_loading_writing_styles'));
        }
    });
}

/**
 * Get writing tones
 */
function getWritingTones() {
    $.ajax({
        url: admin_url + 'topics/controllers/get_writing_tones',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Populate writing tone dropdown
                var options = '';
                $.each(response.data, function(key, name) {
                    options += '<option value="' + key + '">' + name + '</option>';
                });
                $('#writing_tone').html(options);
                $('#writing_tone').selectpicker('refresh');
            } else {
                console.error(response.message);
            }
        },
        error: function() {
            console.error(lang('error_loading_writing_tones'));
        }
    });
}

/**
 * Get writing criteria
 */
function getWritingCriteria() {
    $.ajax({
        url: admin_url + 'topics/controllers/get_writing_criteria',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Populate writing criteria checkboxes
                var html = '';
                $.each(response.data, function(key, name) {
                    html += '<div class="checkbox checkbox-primary">';
                    html += '<input type="checkbox" id="criterion_' + key + '" name="writing_style_options[criteria][]" value="' + key + '">';
                    html += '<label for="criterion_' + key + '">' + name + '</label>';
                    html += '</div>';
                });
                $('#writing_criteria_container').html(html);
            } else {
                console.error(response.message);
            }
        },
        error: function() {
            console.error(lang('error_loading_writing_criteria'));
        }
    });
}

/**
 * Save categories expanded state
 * @param {number} controllerId - ID of the controller
 */
function saveCategoriesState(controllerId) {
    if (!controllerId) {
        console.error('Controller ID is required to save categories state');
        return;
    }
    
    // Get all expanded categories
    var expandedCategories = [];
    jQuery('.category-item.expanded').each(function() {
        expandedCategories.push(jQuery(this).data('category-id'));
    });
    
    // Show loading state on button
    jQuery('#save_categories_state').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> ' + lang('saving', 'Đang lưu...'));
    
    // Call AJAX to save state
    jQuery.ajax({
        url: admin_url + 'topics/controllers/save_categories_state/' + controllerId,
        type: 'POST',
        data: {
            expanded_categories: expandedCategories
        },
        dataType: 'json',
        success: function(response) {
            // Reset button state
            jQuery('#save_categories_state').prop('disabled', false).html('<i class="fa fa-save"></i> ' + lang('controller_save_state', 'Lưu Trạng Thái'));
            
            // Show alert
            if (response.success) {
                alert_float('success', response.message);
            } else {
                alert_float('danger', response.message);
            }
        },
        error: function() {
            // Reset button state
            jQuery('#save_categories_state').prop('disabled', false).html('<i class="fa fa-save"></i> ' + lang('controller_save_state', 'Lưu Trạng Thái'));
            
            // Show error
            alert_float('danger', lang('error_saving', 'Lỗi khi lưu dữ liệu'));
        }
    });
}

/**
 * Initialize categories with saved state
 * @param {number} controllerId - ID of the controller
 * @param {Array} expandedCategories - List of category IDs that should be expanded
 */
function initCategoriesWithSavedState(controllerId, expandedCategories) {
    if (!expandedCategories || !Array.isArray(expandedCategories)) {
        console.log('No saved expansion state or invalid format');
        return;
    }
    
    console.log('Initializing categories with saved state', expandedCategories);
    
    // Expand saved categories
    expandedCategories.forEach(function(categoryId) {
        var $category = jQuery('.category-item[data-category-id="' + categoryId + '"]');
        if ($category.length) {
            $category.addClass('expanded');
            $category.find('> .subcategories').slideDown('fast');
            $category.find('> .category-header .toggle-icon').html('<i class="fa fa-minus-square-o"></i>');
        }
    });
}

// C. Event Handlers
function waitForJQuery(callback) {
    if (typeof jQuery !== 'undefined') {
        callback();
    } else {
        setTimeout(function() {
            waitForJQuery(callback);
        }, 100); // Đợi 100ms rồi kiểm tra lại
    }
}

// Sử dụng hàm waitForJQuery
waitForJQuery(function() {
    $(document).ready(function() {
        // First, preserve any existing login values
    preserveExistingLoginValues();
    
    // Initialize writing style dropdowns and criteria on page load
    if ($('#writing_style').length) {
        getWritingStyles();
    }
    
    if ($('#writing_tone').length) {
        getWritingTones();
    }
    
    if ($('#writing_criteria_container').length) {
        getWritingCriteria();
    }
    
    // Add debug logging for form submission
    $('#controller-form').on('submit', function(e) {
        console.log('Form is being submitted...');
        
        // Log all form fields
        var formData = {};
        $(this).serializeArray().forEach(function(item) {
            formData[item.name] = item.value;
        });
        
        console.log('Form data:', formData);
        
        // Specifically check login config fields
        var loginConfig = {};
        $('input[name^="login_config"]').each(function() {
            var field = $(this).attr('name');
            loginConfig[field] = $(this).val();
            console.log(field + ' = ' + $(this).val());
        });
        
        console.log('Login config fields:', loginConfig);
        
        // Don't prevent form submission, just log
        return true;
    });
    
    // Handle platform change
    $('#platform').on('change', function() {
        var platform = $(this).val();
        if (platform) {
            // Use stored values if available
            getPlatformFields(platform, storedLoginValues);
        } else {
            $('#login_fields_container').slideUp('fast');
            $('#connection_status_container').slideUp('fast');
        }
    });
    
    // Handle test connection button on edit page
    $('#test_connection_edit_btn').on('click', function() {
        var controllerId = $(this).data('controller-id');
        testConnection(controllerId);
    });
    
    // Handle test connection button on detail page
    $('#test_connection_btn').on('click', function() {
        var controllerId = $(this).data('controller-id');
        
        // Show loading state
        $('#connection_status_container').html('<div class="connection-status warning"><div class="loading-spinner"></div> ' + lang('testing_connection', 'Testing connection...') + '...</div>');
        $('#connection_status_container').slideDown('fast');
        
        // Disable the button and show loading state
        $(this).prop('disabled', true).html('<div class="loading-spinner"></div> ' + lang('testing', 'Testing') + '...');
        
        $.ajax({
            url: admin_url + 'topics/controllers/test_connection/' + controllerId,
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                // Re-enable the button
                $('#test_connection_btn').prop('disabled', false).html('<i class="fa fa-plug"></i> ' + lang('test_connection', 'Test Connection'));
                
                if (response.success) {
                    // Create success message
                    var successHtml = '<div class="connection-status success">' +
                        '<i class="fa fa-check-circle"></i> ' +
                        '<strong>' + lang('connection_successful', 'Connection successful') + ':</strong> ' + response.message +
                        '</div>';
                    
                    // Create site information display if available
                    if (response.site_info) {
                        // First try to get platform from response, then from hidden field, 
                        // then from the details table, and finally fallback to a default
                        var platform = response.platform || 
                                      $('#platform').val() || 
                                      $('input[name="platform"]').val() ||
                                      // Try to find the platform in the table by looking for the row with "Platform" label
                                      $('table.table-striped tr td.bold').filter(function() {
                                          return $(this).text().trim().toLowerCase() === 'platform';
                                      }).next('td').text().trim() ||
                                      'unknown';
                        
                        successHtml += displaySiteInfo(response.site_info, platform);
                    }
                    
                    $('#connection_status_container').html(successHtml);
                } else {
                    $('#connection_status_container').html(
                        '<div class="connection-status danger">' +
                        '<i class="fa fa-times-circle"></i> ' +
                        '<strong>' + lang('connection_failed', 'Connection failed') + ':</strong> ' + response.message +
                        '</div>'
                    );
                }
            },
            error: function() {
                // Re-enable the button
                $('#test_connection_btn').prop('disabled', false).html('<i class="fa fa-plug"></i> ' + lang('test_connection', 'Test Connection'));
                
                $('#connection_status_container').html(
                    '<div class="connection-status danger">' +
                    '<i class="fa fa-exclamation-circle"></i> ' +
                    lang('error_testing_connection', 'Error testing connection. Please try again.') +
                    '</div>'
                );
            }
        });
    });
    
    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();
    
    // Initialize password toggles
    $('.toggle-password').on('click', function() {
        var input = $($(this).data('target'));
        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
            $(this).html('<i class="fa fa-eye-slash"></i>');
        } else {
            input.attr('type', 'password');
            $(this).html('<i class="fa fa-eye"></i>');
        }
    });
    
    // Check if we're on edit page with existing login fields
    var isEditPage = $('#controller-form input[name="id"]').length > 0;
    var hasExistingLoginFields = $('.login-field').length > 0;
    
    // Only trigger platform change if:
    // 1. We're not on an edit page, OR
    // 2. We're on an edit page but have no existing login fields
    if ($('#platform').val() && (!isEditPage || !hasExistingLoginFields)) {
        console.log('Auto-triggering platform change');
        $('#platform').trigger('change');
    } else if (isEditPage && hasExistingLoginFields) {
        console.log('Skipping auto-trigger of platform change to preserve existing values');
    }
    
    // Add animation to form elements
    $('.form-group').addClass('animated fadeIn');
    
    // Add smooth scrolling to form sections
    $('a[href^="#"]').on('click', function(event) {
        var target = $(this.getAttribute('href'));
        if (target.length) {
            event.preventDefault();
            $('html, body').stop().animate({
                scrollTop: target.offset().top - 100
            }, 800);
        }
    });
    });
});
