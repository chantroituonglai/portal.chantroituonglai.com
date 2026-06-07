<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
<style>
    .mautic-bridge-stat {
        border: 1px solid #e5e7eb;
        border-radius: 6px;
        padding: 14px;
        min-height: 92px;
        background: #fff;
    }
    .mautic-bridge-stat .stat-value {
        font-size: 24px;
        line-height: 1.2;
        font-weight: 600;
    }
    .mautic-bridge-toolbar {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        align-items: center;
        margin-bottom: 15px;
    }
    .mautic-bridge-toolbar .form-group {
        margin-bottom: 0;
    }
</style>
<script>
    $(function() {
        var dryRun = $('.mautic-bridge-manage').data('dry-run') === 1 || $('.mautic-bridge-manage').data('dry-run') === '1';
        var activeTab = '<?php echo html_escape($active_tab ?? ''); ?>';
        var bridgeTable = null;

        if (activeTab === 'queue') {
            bridgeTable = initDataTable('.table-mautic-bridge-queue', admin_url + 'mautic_bridge_manage/queue', [0, 14], [0, 14], {
                status: '[id="mautic-bridge-filter-status"]',
                direction: '[id="mautic-bridge-filter-direction"]'
            }, [1, 'desc']);
            $('#mautic-bridge-filter-status,#mautic-bridge-filter-direction').on('changed.bs.select change', function() {
                bridgeTable.ajax.reload();
            });
        } else if (activeTab === 'mappings') {
            bridgeTable = initDataTable('.table-mautic-bridge-mappings', admin_url + 'mautic_bridge_manage/mappings', [10], [10], {
                rel_type: '[id="mautic-bridge-filter-rel-type"]'
            }, [0, 'desc']);
            $('#mautic-bridge-filter-rel-type').on('changed.bs.select change', function() {
                bridgeTable.ajax.reload();
            });
        } else if (activeTab === 'campaigns') {
            bridgeTable = initDataTable('.table-mautic-bridge-campaigns', admin_url + 'mautic_bridge_manage/campaigns', false, false, {}, [0, 'desc']);
        } else if (activeTab === 'logs') {
            bridgeTable = initDataTable('.table-mautic-bridge-logs', admin_url + 'mautic_bridge_manage/logs', false, false, {
                level: '[id="mautic-bridge-filter-level"]'
            }, [0, 'desc']);
            $('#mautic-bridge-filter-level').on('changed.bs.select change', function() {
                bridgeTable.ajax.reload();
            });
        } else if (activeTab === 'google_import') {
            var googleImportJobId = 0;
            var googleImportRunning = false;

            function renderGoogleImportJob(payload) {
                if (!payload || payload.success !== true) {
                    alert_float('danger', payload && payload.message ? payload.message : 'Unable to load import job.');
                    return;
                }

                googleImportJobId = payload.job.id;
                $('#mautic-bridge-import-progress-panel,#mautic-bridge-import-mapping-panel,#mautic-bridge-import-preview-panel').removeClass('hide');
                $('#mautic-bridge-import-start').prop('disabled', !payload.defaults_ok || payload.job.pending_rows <= 0);

                var mappingHtml = '<div class="row">';
                $.each(payload.job.mapping || {}, function(target, source) {
                    mappingHtml += '<div class="col-md-4"><div class="form-group">';
                    mappingHtml += '<label>' + target + '</label>';
                    mappingHtml += '<input class="form-control" readonly value="' + $('<div>').text(source || '-').html() + '">';
                    mappingHtml += '</div></div>';
                });
                mappingHtml += '</div>';
                $('#mautic-bridge-import-mapping').html(mappingHtml);

                var rowsHtml = '';
                $.each(payload.preview || [], function(_, row) {
                    var label = row.status === 'pending' ? 'info' : (row.status === 'done' ? 'success' : (row.status === 'failed' ? 'danger' : 'default'));
                    rowsHtml += '<tr>';
                    rowsHtml += '<td>' + row.row_number + '</td>';
                    rowsHtml += '<td><span class="label label-' + label + '">' + row.status + '</span></td>';
                    rowsHtml += '<td>' + $('<div>').text(row.email || '').html() + '</td>';
                    rowsHtml += '<td>' + $('<div>').text(row.company || '').html() + '</td>';
                    rowsHtml += '<td>' + $('<div>').text(row.phone || '').html() + '</td>';
                    rowsHtml += '<td>' + $('<div>').text(row.city || '').html() + '</td>';
                    rowsHtml += '<td>' + $('<div>').text(row.message || '').html() + '</td>';
                    rowsHtml += '</tr>';
                });
                $('#mautic-bridge-import-preview').html(rowsHtml);
                updateGoogleImportProgress(payload.job);
            }

            function updateGoogleImportProgress(job) {
                var total = parseInt(job.total_rows, 10) || 0;
                var processed = parseInt(job.processed_rows, 10) || 0;
                var percent = total > 0 ? Math.round((processed / total) * 100) : 0;
                $('#mautic-bridge-import-progress-bar').css('width', percent + '%').text(percent + '%');
                $('#mautic-bridge-import-counters').html(
                    '<table class="table table-condensed no-mtop"><tbody>' +
                    '<tr><td>Total</td><td class="text-right">' + total + '</td></tr>' +
                    '<tr><td>Pending</td><td class="text-right">' + job.pending_rows + '</td></tr>' +
                    '<tr><td>Success</td><td class="text-right">' + job.success_rows + '</td></tr>' +
                    '<tr><td>Skipped</td><td class="text-right">' + job.skipped_rows + '</td></tr>' +
                    '<tr><td>Failed</td><td class="text-right">' + job.failed_rows + '</td></tr>' +
                    '</tbody></table>'
                );
            }

            $('#mautic-bridge-google-import-upload').on('submit', function(e) {
                e.preventDefault();
                var formData = new FormData(this);
                var $button = $(this).find('button[type="submit"]');
                $button.prop('disabled', true);
                $.ajax({
                    url: admin_url + 'mautic_bridge_manage/google_import_upload',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false
                }).done(function(response) {
                    if (response.success === true) {
                        alert_float('success', response.message || 'Import file parsed.');
                        renderGoogleImportJob(response);
                    } else {
                        alert_float('danger', response.message || 'Upload failed.');
                    }
                }).fail(function(xhr) {
                    alert_float('danger', xhr.responseText || xhr.statusText);
                }).always(function() {
                    $button.prop('disabled', false);
                });
            });

            function processGoogleImportBatch() {
                if (!googleImportRunning || googleImportJobId <= 0) {
                    return;
                }

                $.post(admin_url + 'mautic_bridge_manage/google_import_process', {
                    job_id: googleImportJobId,
                    limit: 10
                }).done(function(response) {
                    if (!response || response.success !== true) {
                        googleImportRunning = false;
                        $('#mautic-bridge-import-start').prop('disabled', false);
                        $('#mautic-bridge-import-stop').addClass('hide');
                        alert_float('danger', response && response.message ? response.message : 'Import batch failed.');
                        return;
                    }

                    updateGoogleImportProgress(response.job);
                    if (parseInt(response.job.pending_rows, 10) <= 0) {
                        googleImportRunning = false;
                        $('#mautic-bridge-import-start').prop('disabled', true);
                        $('#mautic-bridge-import-stop').addClass('hide');
                        alert_float('success', response.message || 'Import completed.');
                        $.get(admin_url + 'mautic_bridge_manage/google_import_job/' + googleImportJobId).done(renderGoogleImportJob);
                        return;
                    }

                    window.setTimeout(processGoogleImportBatch, 300);
                }).fail(function(xhr) {
                    googleImportRunning = false;
                    $('#mautic-bridge-import-start').prop('disabled', false);
                    $('#mautic-bridge-import-stop').addClass('hide');
                    alert_float('danger', xhr.responseText || xhr.statusText);
                });
            }

            $('#mautic-bridge-import-start').on('click', function() {
                if (!confirm('Start live import to Mautic and Perfex?')) {
                    return;
                }
                googleImportRunning = true;
                $(this).prop('disabled', true);
                $('#mautic-bridge-import-stop').removeClass('hide');
                processGoogleImportBatch();
            });

            $('#mautic-bridge-import-stop').on('click', function() {
                googleImportRunning = false;
                $(this).addClass('hide');
                $('#mautic-bridge-import-start').prop('disabled', false);
            });
        }

        function postBridgeAction(url, data, reloadTables, liveConfirm) {
            if (liveConfirm && !dryRun && !confirm('<?php echo _l('mautic_bridge_confirm_live_process'); ?>')) {
                return;
            }

            data = data || {};
            $.post(url, data)
                .done(function(response) {
                    var ok = response.success === true || response.ok === true || response.failed === 0;
                    var message = response.message || 'Done.';
                    if (typeof response.processed !== 'undefined') {
                        message += ' Processed: ' + response.processed + ', success: ' + response.success + ', failed: ' + response.failed + '.';
                    }
                    if (typeof response.queued !== 'undefined') {
                        message += ' Queued: ' + response.queued + '.';
                    }
                    alert_float(ok ? 'success' : 'danger', message);
                    if (reloadTables) {
                        if (bridgeTable) {
                            bridgeTable.ajax.reload(null, false);
                        }
                    }
                })
                .fail(function(xhr) {
                    alert_float('danger', xhr.responseText || xhr.statusText);
                });
        }

        if (!dryRun) {
            $('.mautic-bridge-dry-guard').prop('disabled', true).addClass('disabled').attr('title', '<?php echo _l('mautic_bridge_dry_run_required'); ?>');
        }

        $('body').on('click', '.mautic-bridge-row-action', function() {
            if ($(this).hasClass('mautic-bridge-dry-guard') && !dryRun) {
                alert_float('warning', '<?php echo _l('mautic_bridge_dry_run_required'); ?>');
                return;
            }

            var action = $(this).data('action');
            var id = $(this).data('id');
            postBridgeAction(admin_url + 'mautic_bridge_manage/' + action, {id: id}, true, false);
        });

        $('#mautic-bridge-process-selected').on('click', function() {
            var ids = $('.mautic-bridge-row-check:checked').map(function() {
                return $(this).val();
            }).get();
            postBridgeAction(admin_url + 'mautic_bridge_manage/process_queue', {ids: ids}, true, true);
        });

        $('#mautic-bridge-process-due').on('click', function() {
            postBridgeAction(admin_url + 'mautic_bridge_manage/process_queue', {limit: $('#mautic-bridge-process-limit').val() || 25}, true, true);
        });

        $('#mautic-bridge-sync-campaigns').on('click', function() {
            postBridgeAction(admin_url + 'mautic_bridge_manage/sync_campaigns', {}, true, false);
        });

        var stopBackfill = false;

        function setBackfillProgress(message, alertClass) {
            $('#mautic-bridge-backfill-progress')
                .removeClass('hide alert-info alert-success alert-danger alert-warning')
                .addClass(alertClass || 'alert-info')
                .html(message);
        }

        function enqueueBackfillPage(data) {
            return $.post(admin_url + 'mautic_bridge_manage/enqueue_backfill', data);
        }

        function updateAutoPagesVisibility() {
            var direction = $('[name="direction"]').val();
            $('#mautic-bridge-auto-pages-wrap').toggle(direction === 'inbound');
        }

        $('[name="direction"]').on('changed.bs.select change', updateAutoPagesVisibility);
        updateAutoPagesVisibility();

        $('#mautic-bridge-backfill-form').on('submit', function(e) {
            e.preventDefault();
            if (!dryRun) {
                alert_float('warning', '<?php echo _l('mautic_bridge_dry_run_required'); ?>');
                return;
            }

            var direction = $('[name="direction"]').val();
            var autoPages = direction === 'inbound' && $('#mautic_bridge_auto_pages').is(':checked');
            if (!autoPages) {
                postBridgeAction(admin_url + 'mautic_bridge_manage/enqueue_backfill', $(this).serialize(), false, false);
                return;
            }

            stopBackfill = false;
            var $submit = $(this).find('button[type="submit"]');
            var startPage = Math.max(1, parseInt($('[name="page"]').val(), 10) || 1);
            var limit = Math.max(1, Math.min(200, parseInt($('[name="limit"]').val(), 10) || 50));
            var page = startPage;
            var endPage = null;
            var totalQueued = 0;

            $submit.prop('disabled', true);
            $('#mautic-bridge-stop-backfill').removeClass('hide');
            setBackfillProgress('<?php echo _l('mautic_bridge_auto_backfill_starting'); ?>', 'alert-info');

            function runNextPage() {
                if (stopBackfill) {
                    $submit.prop('disabled', false);
                    $('#mautic-bridge-stop-backfill').addClass('hide');
                    setBackfillProgress('<?php echo _l('mautic_bridge_auto_backfill_stopped'); ?> ' + totalQueued + ' job(s) queued.', 'alert-warning');
                    return;
                }

                enqueueBackfillPage({
                    direction: direction,
                    limit: limit,
                    page: page
                }).done(function(response) {
                    if (!response || response.success !== true) {
                        $submit.prop('disabled', false);
                        $('#mautic-bridge-stop-backfill').addClass('hide');
                        setBackfillProgress((response && response.message) ? response.message : 'Backfill failed.', 'alert-danger');
                        return;
                    }

                    totalQueued += parseInt(response.queued, 10) || 0;
                    if (endPage === null) {
                        var total = parseInt(response.total, 10) || 0;
                        endPage = total > 0 ? Math.ceil(total / limit) : page;
                    }

                    var percent = endPage > 0 ? Math.min(100, Math.round(((page - startPage + 1) / (endPage - startPage + 1)) * 100)) : 100;
                    setBackfillProgress(
                        'Page ' + page + ' / ' + endPage + ' complete. Queued this page: ' + response.queued + '. Total queued: ' + totalQueued + '. ' + percent + '%',
                        'alert-info'
                    );

                    if (page >= endPage) {
                        $submit.prop('disabled', false);
                        $('#mautic-bridge-stop-backfill').addClass('hide');
                        setBackfillProgress('<?php echo _l('mautic_bridge_auto_backfill_done'); ?> ' + totalQueued + ' job(s) queued.', 'alert-success');
                        alert_float('success', '<?php echo _l('mautic_bridge_auto_backfill_done'); ?>');
                        return;
                    }

                    page++;
                    $('[name="page"]').val(page);
                    window.setTimeout(runNextPage, 250);
                }).fail(function(xhr) {
                    $submit.prop('disabled', false);
                    $('#mautic-bridge-stop-backfill').addClass('hide');
                    setBackfillProgress(xhr.responseText || xhr.statusText, 'alert-danger');
                });
            }

            runNextPage();
        });

        $('#mautic-bridge-stop-backfill').on('click', function() {
            stopBackfill = true;
        });
    });
</script>
</body>
</html>
