// Global variables
var selectedTopics = new Set();

// Helper functions
function handleDataTableError(table, settings) {
    table.parent().find('.datatable-error-message').remove();

    if (settings.json && settings.json.hasError) {
        var errorHtml = '<div class="datatable-error-message alert alert-danger">' +
            '<button type="button" class="close" data-dismiss="alert" aria-label="Close">' +
            '<span aria-hidden="true">&times;</span></button>' +
            '<h4><i class="fa fa-exclamation-triangle"></i> ' + app.lang.error + '</h4>';

        if (settings.json.message) {
            errorHtml += '<p>' + settings.json.message + '</p>';
        }

        if (app.isAdmin && settings.json.query) {
            errorHtml += '<small class="text-muted query-container">' +
                'Query: <span class="query-text">' + settings.json.query + '</span>' +
                '<button class="btn btn-xs btn-default copy-query" data-query="' + settings.json.query + '">' +
                '<i class="fa fa-copy"></i></button></small>';
        }

        errorHtml += '</div>';
        table.parent().append(errorHtml);
        table.hide();
        return true;
    }

    table.show();
    return false;
}

function copyToClipboard(text) {
    return navigator.clipboard.writeText(text)
        .then(() => alert_float('success', 'Query copied to clipboard'))
        .catch(() => {
            const textarea = document.createElement('textarea');
            textarea.value = text;
            document.body.appendChild(textarea);
            textarea.select();
            try {
                document.execCommand('copy');
                alert_float('success', 'Query copied to clipboard');
            } catch (err) {
                alert_float('danger', 'Failed to copy query');
            }
            document.body.removeChild(textarea);
        });
}

function updateSelectedCount() {
    const count = selectedTopics.size;
    const text = count + ' ' + (count === 1 ? app.lang.topic_selected : app.lang.topics_selected);
    $('.selected-topics-count .label').text(text);
    $('#saveTopicsBtn').prop('disabled', count === 0);
}

function toggleRemoveButton() {
    const hasChecked = $('.related-topic-select:checked').length > 0;
    $('#remove-selected-topics').toggle(hasChecked);
}

// DataTable initialization functions
function initRelatedTopicsTable() {
    const existingTable = $('.table-related-topics').DataTable();
    if (existingTable) {
        existingTable.destroy();
    }

    try {
        return initDataTable('.table-related-topics', 
            admin_url + 'topics/controllers/get_related_topics/' + controllerId,
            [0], // sortable columns
            [0], // searchable columns
            undefined,
            [1, 'asc'],
            {
                processing: true,
                serverSide: true,
                ajax: {
                    data: function(d) {
                        d.controller_id = controllerId;
                        return d;
                    }
                },
                columns: [
                    {
                        targets: 0,
                        data: null,
                        orderable: false,
                        render: function(data, type, row) {
                            return row[0];
                        }
                    },
                    {
                        targets: 1,
                        data: 'topicid',
                        render: function(data, type, row) {
                            return '<div class="tw-truncate">' + row[1] + '</div>';
                        }
                    },
                    {
                        targets: 2,
                        data: 'topictitle',
                        render: function(data, type, row) {
                            return '<div class="tw-truncate" data-toggle="tooltip" title="' + row[2] + '">' + row[2] + '</div>';
                        }
                    },
                    {
                        targets: 3,
                        data: 'status',
                        render: function(data, type, row) {
                            return row[3];
                        }
                    },
                    {
                        targets: 4,
                        data: 'assigned_date',
                        render: function(data, type, row) {
                            return '<div class="text-nowrap">' + row[4] + '</div>';
                        }
                    }
                ],
                
                fnDrawCallback: function(settings) {
                    const table = $(this);
                    
                    // Handle error display
                    if (settings.json && settings.json.hasError) {
                        table.parent().find('.datatable-error-message').remove();
                        const errorHtml = `
                            <div class="datatable-error-message alert alert-danger">
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                                <h4><i class="fa fa-exclamation-triangle"></i> ${app.lang.error}</h4>
                                <div class="tw-max-w-[300px] tw-truncate text-danger">${settings.json.data[0][2]}</div>
                            </div>
                        `;
                        table.parent().append(errorHtml);
                        table.hide();
                        return;
                    }
                    
                    table.show();
                    
                    // Show no data message if needed
                    if (settings.json && settings.json.recordsTotal == 0) {
                        if (!table.parent().find('.no-data-message').length) {
                            table.parent().append('<p class="no-data-message no-margin">' + app.lang.no_related_topics + '</p>');
                        }
                    } else {
                        table.parent().find('.no-data-message').remove();
                    }
                }
            }
        );
    } catch (error) {
        console.error('Failed to initialize related topics table:', error);
        return null;
    }
}

function initTopicsSelectionTable() {
    return $('.table-topics-selection').DataTable({
        processing: true,
        serverSide: true,
        autoWidth: false,
        ajax: {
            url: admin_url + 'topics/controllers/get_available_topics/' + controllerId,
            type: 'POST',
            data: function (d) {
                d.selected_topics = Array.from(selectedTopics);
                return d;
            },
            beforeSend: function () {
                $('.table-topics-selection').addClass('dt-table-loading');
                $('.dataTables_processing').addClass('dt-loader');
            },
            complete: function () {
                $('.table-topics-selection').removeClass('dt-table-loading');
                $('.dataTables_processing').removeClass('dt-loader');
                $('.dataTables_wrapper').removeClass('table-loading');
            },
            dataSrc: function (json) {
                if (json.hasError) {
                    $('#saveTopicsBtn, #select_all_topics').prop('disabled', true);
                    return json.data;
                }
                return json.data;
            }
        },
        columns: [
            {
                data: null,
                orderable: false,
                render: function(data, type, row) {
                    return row[0];
                }
            },
            {
                data: 'topicid',
                render: function(data, type, row) {
                    return row[1];
                }
            },
            {
                data: 'topictitle',
                render: function(data, type, row) {
                    return row[2];
                }
            },
            {
                data: 'status',
                render: function(data, type, row) {
                    return row[3];
                }
            },
            {
                data: 'datecreated',
                render: function(data, type, row) {
                    return row[4];
                }
            }
        ],
        drawCallback: function () {
            $('.topic-select').each(function () {
                $(this).prop('checked', selectedTopics.has($(this).val()));
            });
            updateSelectedCount();
        }
    });
}

// Event handlers setup
function setupEventHandlers(relatedTopicsTable, topicsTable) {
    // Select all handlers
    $('#select_all_related_topics').change(function () {
        $('.related-topic-select').prop('checked', $(this).prop('checked'));
        toggleRemoveButton();
    });

    $('#select_all_topics').change(function () {
        const checked = $(this).prop('checked');
        $('.topic-select').each(function () {
            $(this).prop('checked', checked);
            checked ? selectedTopics.add($(this).val()) : selectedTopics.delete($(this).val());
        });
        updateSelectedCount();
    });

    // Individual selection handlers
    $('.table-related-topics').on('change', '.related-topic-select', toggleRemoveButton);
    $('.table-topics-selection').on('change', '.topic-select', function () {
        $(this).is(':checked') ? selectedTopics.add($(this).val()) : selectedTopics.delete($(this).val());
        updateSelectedCount();
    });

    // Remove topics handler
    $('#remove-selected-topics').click(function () {
        const selectedIds = $('.related-topic-select:checked').map(function () {
            return $(this).val();
        }).get();

        if (!selectedIds.length) return;

        confirm_dialog({
            title: app.lang.confirm_title || 'Confirm',
            message: app.lang.confirm_remove_topics || 'Are you sure you want to remove selected topics?',
            yes_callback: () => {
                $.post(admin_url + 'topics/controllers/remove_topics/' + controllerId, {
                    topic_ids: selectedIds
                }).done(response => {
                    response = JSON.parse(response);
                    if (response.success) {
                        alert_float('success', response.message);
                        $('.table-related-topics').DataTable().ajax.reload();
                        $('#remove-selected-topics').hide();
                    } else {
                        alert_float('danger', response.message);
                    }
                });
            }
        });
    });

    // Copy query handler
    $(document).on('click', '.copy-query', function (e) {
        e.preventDefault();
        copyToClipboard($(this).data('query'));
        return false;
    });

    // Search handler
    $('#topics-search').on('keypress', function (e) {
        if (e.which == 13) {
            topicsTable.search($(this).val()).draw();
        }
    });
}

document.addEventListener('DOMContentLoaded', function () {
    // Initialize everything when document is ready
    $(function () {
        const relatedTopicsTable = initRelatedTopicsTable();
        const topicsTable = initTopicsSelectionTable();

        // Chỉ setup event handlers nếu cả 2 table đều khởi tạo thành công
        if (relatedTopicsTable && topicsTable) {
            setupEventHandlers(relatedTopicsTable, topicsTable);
        }
    });
});

// Modal functions
function showAddTopicsModal() {
    selectedTopics.clear();
    updateSelectedCount();
    $('#select_all_topics').prop('checked', false);
    $('.table-topics-selection').DataTable().ajax.reload();
    $('#addTopicsModal').modal('show');
}

function saveSelectedTopics() {
    confirm_dialog({
        title: app.lang.confirm_title || 'Confirm',
        message: app.lang.confirm_add_topics || 'Are you sure you want to add selected topics?',
        yes_callback: () => {
            $.post(admin_url + 'topics/controllers/add_topics/' + controllerId, {
                topic_ids: Array.from(selectedTopics)
            }).done(response => {
                response = JSON.parse(response);
                if (response.success) {
                    alert_float('success', response.message);
                    $('.table-related-topics').DataTable().ajax.reload();
                    $('#addTopicsModal').modal('hide');
                } else {
                    alert_float('danger', response.message);
                }
            });
        }
    });
}

// Helper functions
function confirm_dialog(params) {
    // Set default values
    const defaults = {
        message: app.lang.are_you_sure || 'Are you sure?',
        title: app.lang.confirm_title || 'Confirm',
        yes_callback: () => {},
        no_callback: () => {},
        size: 'md' // sm, md, lg
    };

    // Merge defaults with provided params
    params = Object.assign({}, defaults, params);

    // Create modal HTML with language strings
    const modalHtml = `
        <div class="modal fade" id="confirmModal" role="dialog">
            <div class="modal-dialog modal-${params.size}">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                        <h4 class="modal-title">${params.title}</h4>
                    </div>
                    <div class="modal-body">
                        <p>${params.message}</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">
                            ${app.lang.no || 'No'}
                        </button>
                        <button type="button" class="btn btn-primary confirm-yes">
                            ${app.lang.yes || 'Yes'}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;

    // Remove existing modal if any
    $('#confirmModal').remove();

    // Add new modal to body
    $('body').append(modalHtml);

    // Get modal reference
    const $modal = $('#confirmModal');

    // Setup event handlers
    $modal.find('.confirm-yes').click(function() {
        params.yes_callback();
        $modal.modal('hide');
    });

    $modal.on('hidden.bs.modal', function() {
        params.no_callback();
        $(this).remove();
    });

    // Show modal
    $modal.modal('show');
}
