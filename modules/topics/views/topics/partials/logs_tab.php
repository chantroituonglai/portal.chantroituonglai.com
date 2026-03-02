<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="row">
    <div class="col-md-12">
        <div class="tw-flex tw-justify-between tw-items-center tw-mb-4">
            <h4 class="tw-font-semibold tw-m-0">
                <?php echo _l('log'); ?>
            </h4>
            <button type="button" class="btn btn-primary" id="refresh-history">
                <i class="fa fa-refresh tw-mr-1"></i> <?php echo _l('refresh'); ?>
            </button>
        </div>
        <div id="last-update" class="tw-text-sm tw-text-gray-600">
            <span id="connection-status-icon" class="tw-mr-2" style="color: black;">●</span>
            Last Update: <span id="last-update-time">Never</span>
        </div>
        <div class="table-responsive">
            <?php
            $this->load->view('topics/includes/topic_history_table', [
                'topic' => $topic,
                'topic_history' => $topic_history
            ]);
            ?>
        </div>
    </div>
</div>

<script>
    window.onload = function() {
    $(function() {
        var initialLogCount = $('#topic-history-table tbody tr').length;
        var newLogCount = 0;

        function updateLogCountBadge(count) {
            var badge = $('#new-log-count');
            if (count > 0) {
                badge.text(count).show();
            } else {
                badge.hide();
            }
        }

        // Initial log count
        updateLogCountBadge(0);

        // Giữ nguyên các event handlers hiện có
        $(document).on('click', '.show-log-popup', function(e) {
            e.preventDefault();
            var btn = $(this);
            var modal = $('#logModal');
            var topicid = btn.data('topicid');
            var id = btn.data('id');
            
            // Show loading
            modal.find('.loading').show();
            modal.find('.log-content').hide();
            
            // Get log data
            $.ajax({
                url: admin_url + 'topics/get_log_data',
                type: 'POST',
                data: {
                    topicid: topicid,
                    id: id
                },
                success: function(response) {
                    try {
                        var data = JSON.parse(response);
                        if (data.success) {
                            var content = `
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th width="33%">#1</th>
                                                <th width="67%">#2</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td width="150"><strong><?php echo _l('topic_id'); ?>:</strong></td>
                                                <td>${data.data.topicid}</td>
                                            </tr>
                                            <tr>
                                                <td><strong><?php echo _l('topic_title'); ?>:</strong></td>
                                                <td>${data.data.topictitle || ''}</td>
                                            </tr>
                                            <tr>
                                                <td><strong><?php echo _l('log'); ?>:</strong></td>
                                                <td class="p-0">
                                                    <div class="log-code-container">
                                                        <pre class="line-numbers"><code class="language-auto">${formatLogContent(data.data.log || '')}</code></pre>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td><strong><?php echo _l('updated_date'); ?>:</strong></td>
                                                <td>${moment(data.data.dateupdated).format('DD/MM/YYYY HH:mm')}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            `;
                            modal.find('.log-content').html(content).show();
                            Prism.highlightAll();
                        } else {
                            modal.find('.log-content').html('<div class="alert alert-warning">' + data.message + '</div>').show();
                        }
                    } catch (e) {
                        console.error('Error parsing response:', e);
                        modal.find('.log-content').html('<div class="alert alert-danger">Error loading log data</div>').show();
                    }
                    modal.find('.loading').hide();
                },
                error: function(xhr, status, error) {
                    console.error('Failed to load log:', error);
                    modal.find('.loading').hide();
                    modal.find('.log-content').html('<div class="alert alert-danger">Failed to load log data</div>').show();
                }
            });
        });

        $(document).on('click', '.progress-step .show-log-popup', function(e) {
            e.preventDefault();
            var modal = $('#logModal');
            var loading = modal.find('.loading');
            var content = modal.find('.log-content');
            
            loading.show();
            content.hide();
            
            $.ajax({
                url: admin_url + 'topics/get_topic_history_ajax',
                type: 'POST',
                data: {
                    topicid: $(this).data('topicid'),
                    action_type: $(this).data('action-type')
                },
                success: function(response) {
                    try {
                        var data = JSON.parse(response);
                        if (data.success) {
                            var table = '<div><?php echo _l('topic_histories'); ?> </div><div class="table-responsive"><table class="table table-striped">';
                            data.data.forEach(function(item) {
                                table += `
                                    <tr>
                                        <td width="150">${moment(item.dateupdated).format('DD/MM/YYYY HH:mm')}</td>
                                        <td width="150">
                                            <span class="label" style="background-color: ${item.state_color || '#777'}">
                                                ${item.action_state_name || ''}
                                            </span>
                                        </td>
                                        <td class="p-0">
                                            <div class="log-code-container">
                                                <pre class="line-numbers">
                                                    <code class="language-json">
                                                        ${formatLogContent(item.log || '')}
                                                    </code>
                                                </pre>
                                            </div>
                                        </td>
                                    </tr>
                                `;
                            });
                            table += '</table></div>';
                            content.html(table);
                            
                            // Highlight code
                            Prism.highlightAll();
                        }
                    } catch (e) {
                        console.error('Error parsing response:', e);
                        content.html('<div class="alert alert-danger">Error loading log data</div>');
                    }
                    loading.hide();
                    content.show();
                },
                error: function() {
                    loading.hide();
                    content.html('<div class="alert alert-danger">Error loading log data</div>').show();
                }
            });
        });

        // Refresh history with log counting
        $('#refresh-history').on('click', function() {
            var btn = $(this);
            var beforeCount = $('#topic-history-table tbody tr').length;
            
            btn.prop('disabled', true);
            btn.find('i').addClass('fa-spin');
            
            $.ajax({
                url: admin_url + 'topics/get_topic_history_ajax',
                type: 'POST',
                data: {
                    topicid: '<?php echo $topic->topicid; ?>'
                },
                success: function(response) {
                    try {
                        var data = JSON.parse(response);
                        if (data.success) {
                            var tbody = '';
                            data.data.forEach(function(item) {
                                tbody += `
                                    <tr>
                                        <td>${item.action_type_name} - <span style="font-size: 9px;">${item.action_type_code}</span></td>
                                        <td>
                                            <span class="label" style="background-color: ${item.state_color}">
                                                ${item.action_state_name}
                                            </span>
                                            <span style="font-size: 9px;">${item.action_state_code}</span>
                                        </td>
                                        <td class="action-links">
                                            <a href="#" class="show-log-popup" 
                                               data-toggle="modal" 
                                               data-target="#logModal"
                                               data-topicid="${item.topicid}"
                                               data-id="${item.id}">
                                                <i class="fa fa-file-text-o"></i> <?php echo _l('view_log'); ?>
                                            </a>
                                            ${item.valid_data == 1 ? `
                                            <a href="#" class="show-processed-data ml-2" 
                                               data-toggle="modal" 
                                               data-target="#processedDataModal"
                                               data-topicid="${item.topicid}"
                                               data-id="${item.id}">
                                                <i class="fa fa-database"></i> <?php echo _l('view_processed_data'); ?>
                                            </a>
                                            ` : ''}
                                            ${item.execution_html ? item.execution_html : ''}
                                        </td>
                                        <td>${moment(item.dateupdated).format('DD/MM/YYYY HH:mm')}</td>
                                    </tr>
                                `;
                            });
                            $('#topic-history-table tbody').html(tbody);

                            // Update log count after refresh
                            var afterCount = data.data.length;
                            if (afterCount > beforeCount) {
                                newLogCount += (afterCount - beforeCount);
                                updateLogCountBadge(newLogCount);
                            }
                            
                            // Update last refresh time
                            updateLastUpdateTime();

                            if (typeof updateButtonsFromHistory === 'function') {
                                console.log('Updating buttons from history...');
                                updateButtonsFromHistory(data.data);
                            } else {
                                console.log('updateButtonsFromHistory function not available');
                            }
                        }
                    } catch (e) {
                        console.error('Error parsing response:', e);
                    }
                    btn.prop('disabled', false);
                    btn.find('i').removeClass('fa-spin');
                },
                error: function(xhr, status, error) {
                    console.error('Failed to refresh history:', error);
                    btn.prop('disabled', false);
                    btn.find('i').removeClass('fa-spin');
                    alert('Failed to refresh history. Please try again.');
                }
            });
        });

        // Reset log count when clicking on the Logs tab
        $('a[href="#logs"]').on('click', function() {
            newLogCount = 0;
            initialLogCount = $('#topic-history-table tbody tr').length;
            updateLogCountBadge(newLogCount);
        });

        // Function to update the last update time
        function updateLastUpdateTime() {
            var now = new Date();
            var formattedTime = moment(now).format('DD/MM/YYYY HH:mm:ss');
            document.getElementById('last-update-time').textContent = formattedTime;
        }
    });
    }
</script> 