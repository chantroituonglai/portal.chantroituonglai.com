<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <h4 class="tw-mt-0 tw-font-semibold tw-text-neutral-800"><?= e($title); ?></h4>
                <div class="row">
                    <div class="col-md-3">
                        <div class="panel_s">
                            <div class="panel-body">
                                <h5 class="tw-mt-0">Master items</h5>
                                <div class="tw-text-2xl tw-font-semibold" data-summary="master_items"><?= (int) $summary['master_items']; ?></div>
                                <span class="text-muted">Without SKU: <span data-summary="master_items_no_sku"><?= (int) $summary['master_items_no_sku']; ?></span></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="panel_s">
                            <div class="panel-body">
                                <h5 class="tw-mt-0">Sales lines</h5>
                                <div class="tw-text-2xl tw-font-semibold" data-summary="sales_lines"><?= (int) $summary['sales_lines']; ?></div>
                                <span class="text-muted">Current itemable rows</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="panel_s">
                            <div class="panel-body">
                                <h5 class="tw-mt-0">Mapped</h5>
                                <div class="tw-text-2xl tw-font-semibold text-success" data-summary="sales_lines_mapped"><?= (int) $summary['sales_lines_mapped']; ?></div>
                                <span class="text-muted">Have stable SKU metadata</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="panel_s">
                            <div class="panel-body">
                                <h5 class="tw-mt-0">Pending</h5>
                                <div class="tw-text-2xl tw-font-semibold text-danger" data-summary="sales_lines_pending"><?= (int) $summary['sales_lines_pending']; ?></div>
                                <span class="text-muted">Need backfill/mapping</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="panel_s">
                    <div class="panel-heading">
                        <h4 class="panel-title">SKU Backfill</h4>
                    </div>
                    <div class="panel-body">
                        <div class="alert alert-info">
                            Backfill only writes SKU metadata columns on <code>tblitemable</code>: SKU, master item id, snapshot JSON/hash, matched time, confidence and source. It does not update description, long description, rate, quantity, unit or taxes on existing sales records.
                        </div>
                        <div class="row">
                            <div class="col-md-3">
                                <?= render_input('sku_backfill_limit', 'Batch limit', 100, 'number', ['min' => 1, 'max' => 500]); ?>
                            </div>
                            <div class="col-md-3">
                                <label for="sku_backfill_force" class="control-label">Force remap</label>
                                <div class="checkbox checkbox-primary">
                                    <input type="checkbox" id="sku_backfill_force" value="1">
                                    <label for="sku_backfill_force">Include already mapped lines</label>
                                </div>
                            </div>
                        </div>
                        <div class="btn-group">
                            <button type="button" class="btn btn-default" id="sku-preview">
                                <i class="fa fa-search"></i> Preview dry-run
                            </button>
                            <button type="button" class="btn btn-primary" id="sku-write">
                                <i class="fa fa-barcode"></i> Write SKU metadata
                            </button>
                        </div>
                        <div class="progress mtop20 hide" id="sku-progress-wrap">
                            <div class="progress-bar progress-bar-info progress-bar-striped active" id="sku-progress" role="progressbar" style="width:0%">0%</div>
                        </div>
                        <pre class="mtop20" id="sku-output" style="max-height:260px;overflow:auto;background:#111827;color:#d1d5db;border:0;border-radius:6px;"></pre>
                    </div>
                </div>

                <div class="panel_s">
                    <div class="panel-heading">
                        <h4 class="panel-title">Recent Backfill Logs</h4>
                    </div>
                    <div class="panel-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Sales Line</th>
                                        <th>Rel</th>
                                        <th>Status</th>
                                        <th>SKU</th>
                                        <th>Confidence</th>
                                        <th>Message</th>
                                        <th>Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($logs as $log) { ?>
                                    <tr>
                                        <td><?= (int) $log['id']; ?></td>
                                        <td><?= (int) $log['itemable_id']; ?></td>
                                        <td><?= e($log['rel_type']); ?> #<?= (int) $log['rel_id']; ?></td>
                                        <td><?= e($log['status']); ?></td>
                                        <td><?= e($log['matched_sku']); ?></td>
                                        <td><?= (int) $log['confidence']; ?></td>
                                        <td><?= e($log['message']); ?></td>
                                        <td><?= e($log['created_at']); ?></td>
                                    </tr>
                                <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
<script>
(function() {
    var running = false;
    var output = $('#sku-output');
    var progressWrap = $('#sku-progress-wrap');
    var progress = $('#sku-progress');

    function line(message) {
        output.append(message + "\n");
        output.scrollTop(output[0].scrollHeight);
    }

    function refreshSummary() {
        $.getJSON(admin_url + 'item_sku_manager/summary', function(res) {
            if (!res || !res.success) {
                return;
            }
            $.each(res.summary, function(key, value) {
                $('[data-summary="' + key + '"]').text(value);
            });
        });
    }

    function run(mode) {
        if (running) {
            return;
        }

        if (mode === 'write' && !confirm('Write SKU metadata to historical sales lines? Existing description, price, quantity, unit and taxes will not be changed.')) {
            return;
        }

        running = true;
        output.text('');
        progressWrap.removeClass('hide');
        progress.css('width', '0%').text('0%');

        var offset = 0;
        var limit = parseInt($('#sku_backfill_limit').val(), 10) || 100;
        var force = $('#sku_backfill_force').is(':checked') ? 1 : 0;

        function step() {
            $.post(admin_url + 'item_sku_manager/run_backfill', {
                mode: mode,
                limit: limit,
                offset: offset,
                force: force,
                confirm: mode === 'write' ? 'WRITE_SKU_METADATA' : ''
            }).done(function(res) {
                if (!res || !res.success) {
                    line('ERROR: ' + ((res && res.message) ? res.message : 'Unknown error'));
                    running = false;
                    return;
                }

                var total = parseInt(res.total_pending, 10) || 0;
                var processed = parseInt(res.next_offset, 10) || 0;
                var percent = total > 0 ? Math.min(100, Math.round((processed / total) * 100)) : 100;
                progress.css('width', percent + '%').text(percent + '%');

                line('Batch offset ' + res.offset + ': processed=' + res.processed + ', mapped=' + res.mapped + ', unmatched=' + res.unmatched + ', written=' + res.written);
                if (res.preview && res.preview.length) {
                    $.each(res.preview.slice(0, 5), function(_, row) {
                        line('  #' + row.itemable_id + ' ' + row.rel_type + '/' + row.rel_id + ' -> ' + (row.sku || 'UNMATCHED') + ' (' + row.source + ', ' + row.confidence + ') ' + row.description);
                    });
                }

                if (res.done || mode === 'dry_run') {
                    progress.css('width', '100%').text('100%');
                    line('Done.');
                    refreshSummary();
                    running = false;
                    return;
                }

                offset = res.next_offset;
                step();
            }).fail(function(xhr) {
                line('HTTP ERROR: ' + xhr.status + ' ' + xhr.statusText);
                running = false;
            });
        }

        step();
    }

    $('#sku-preview').on('click', function() {
        run('dry_run');
    });
    $('#sku-write').on('click', function() {
        run('write');
    });
})();
</script>
</body>
</html>
