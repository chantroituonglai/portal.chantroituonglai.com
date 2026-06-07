<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content estimates-outlook-content">
        <div class="row">
            <div class="panel-table-full">
                <div id="vueApp">
                    <div class="col-md-12 tw-mb-3">
                        <h4 class="tw-my-0 tw-font-bold tw-text-xl"><?= _l('estimates'); ?></h4>
                        <a href="#" 
							class="estimates-total tw-text-neutral-500 hover:tw-text-neutral-700 focus:tw-text-neutral-700"
							onclick="slideToggle('#stats-top'); init_estimates_total(true); return false;">
								<?= _l('view_financial_stats'); ?>
						</a>
                    </div>                  
                    <div class="col-md-12">
                        <?php $this->load->view('admin/estimates/quick_stats'); ?>
                    </div>
                    <?php $this->load->view('admin/estimates/list_template'); ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $this->load->view('admin/includes/modals/sales_attach_file'); ?>
<script>
var hidden_columns = [2, 5, 6, 8, 9];
</script>
<style>
.estimates-outlook-content .panel-table-full {
    width: 100%;
}

.estimates-outlook-content #small-table .panel_s,
.estimates-outlook-content #small-table .panel-body {
    height: 100%;
}

.estimates-outlook-content body.small-table {
    overflow-x: hidden;
}

.estimates-outlook-content .estimates-outlook-split {
    --estimate-preview-width: 560px;
}

body.small-table .estimates-outlook-content .estimates-outlook-split {
    display: flex;
    align-items: stretch;
    gap: 0;
    overflow: hidden;
    min-height: 560px;
}

body.small-table .estimates-outlook-content #small-table {
    float: none;
    flex: 1 1 auto;
    width: auto;
    max-width: none;
    min-width: 560px;
    padding-right: 0;
    height: 100%;
    overflow: hidden;
}

body.small-table .estimates-outlook-content .small-table-right-col {
    float: none;
    flex: 0 0 var(--estimate-preview-width);
    width: var(--estimate-preview-width);
    max-width: 760px;
    min-width: 420px;
    padding-left: 0;
    height: 100%;
    overflow: hidden;
}

body.small-table .estimates-outlook-content .estimates-outlook-split.is-preview-full .small-table-right-col {
    flex: 1 1 100%;
    width: 100%;
    max-width: none;
    min-width: 0;
}

body.small-table .estimates-outlook-content .estimates-outlook-split.is-preview-full .estimates-outlook-resizer {
    display: none;
}

.estimates-outlook-resizer {
    display: none;
    flex: 0 0 9px;
    width: 9px;
    cursor: col-resize;
    background: #edf3f8;
    border-left: 1px solid #d6dde6;
    border-right: 1px solid #d6dde6;
    position: relative;
    z-index: 3;
}

body.small-table .estimates-outlook-resizer {
    display: block;
}

.estimates-outlook-resizer:before {
    content: "";
    position: absolute;
    top: 50%;
    left: 50%;
    width: 3px;
    height: 54px;
    border-radius: 999px;
    background: #9fb0c3;
    transform: translate(-50%, -50%);
}

.estimates-outlook-resizer:hover,
.estimates-outlook-resizer.is-dragging {
    background: #e6f3fb;
}

body.small-table .estimates-outlook-content #small-table .panel_s,
body.small-table .estimates-outlook-content #estimate > .col-md-12 > .panel_s {
    border-radius: 8px;
    height: 100%;
}

body.small-table .estimates-outlook-content #small-table .panel-body {
    overflow: auto;
}

body.small-table .estimates-outlook-content #estimate {
    height: 100%;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

body.small-table .estimates-outlook-content #estimate > .col-md-12,
body.small-table .estimates-outlook-content #estimate > .col-md-12 > .panel_s,
body.small-table .estimates-outlook-content #estimate > .col-md-12 > .panel_s > .panel-body {
    min-height: 0;
}

body.small-table .estimates-outlook-content #estimate > .alert,
body.small-table .estimates-outlook-content #estimate > .mergeable-estimates {
    flex: 0 0 auto;
}

body.small-table .estimates-outlook-content #estimate > .col-md-12 {
    flex: 1 1 auto;
    height: auto;
    overflow: hidden;
}

body.small-table .estimates-outlook-content #estimate > .col-md-12 > .panel_s,
body.small-table .estimates-outlook-content #estimate > .col-md-12 > .panel_s > .panel-body {
    height: 100%;
}

body.small-table .estimates-outlook-content #estimate > .col-md-12 > .panel_s > .panel-body {
    overflow: auto;
}

body.small-table .estimates-outlook-content #estimate-preview .table-responsive {
    overflow-x: auto;
    overflow-y: visible;
    width: 100%;
}

body.small-table .estimates-outlook-content #estimate-preview table.items {
    width: max-content;
    min-width: 920px;
    table-layout: auto;
}

body.small-table .estimates-outlook-content #estimate-preview table.items th,
body.small-table .estimates-outlook-content #estimate-preview table.items td {
    white-space: nowrap;
    vertical-align: top;
}

body.small-table .estimates-outlook-content #estimate-preview table.items th:last-child,
body.small-table .estimates-outlook-content #estimate-preview table.items td:last-child {
    position: static;
    min-width: 150px;
    background: #fff;
    box-shadow: none;
    pointer-events: auto;
}

body.small-table .estimates-outlook-content #estimate-preview table.items th:last-child {
    background: #f3f4f6;
}

body.small-table .estimates-outlook-content #estimate-preview table.items .description,
body.small-table .estimates-outlook-content #estimate-preview table.items .long_description,
body.small-table .estimates-outlook-content #estimate-preview table.items td:nth-child(2) {
    white-space: nowrap;
    min-width: 360px;
}

body.small-table .estimates-outlook-content #estimate-preview table.items td:nth-child(2) br,
body.small-table .estimates-outlook-content #estimate-preview table.items td:nth-child(2) p:empty {
    display: none;
}

body.small-table .estimates-outlook-content #estimate-preview table.items td:nth-child(2) p,
body.small-table .estimates-outlook-content #estimate-preview table.items td:nth-child(2) div {
    display: inline;
    margin: 0;
}

body.small-table .estimates-outlook-content #estimate-preview table.items td:nth-child(2) p:after,
body.small-table .estimates-outlook-content #estimate-preview table.items td:nth-child(2) div:after {
    content: " ";
}

.estimates-outlook-content .table-estimates tbody tr.is-preview-selected > td {
    background: #ebf5ff !important;
}

.estimates-outlook-content .table-estimates tbody tr.is-preview-selected > td:first-child {
    box-shadow: inset 3px 0 0 #0b6fa4;
}

.estimate-selected-pin {
    display: none;
    position: fixed;
    bottom: 0;
    z-index: 1040;
    overflow: hidden;
    background: #ebf5ff;
    border-top: 1px solid #c7dced;
    border-right: 1px solid #c7dced;
    box-shadow: 0 -12px 28px rgba(15, 23, 42, 0.14);
}

body.small-table .estimate-selected-pin.is-visible {
    display: block;
}

.estimate-selected-pin-card {
    min-height: 76px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    padding: 12px 16px;
    background: #ebf5ff;
    box-shadow: inset 3px 0 0 #0b6fa4;
}

.estimate-selected-pin-main {
    min-width: 0;
    display: flex;
    align-items: center;
    gap: 12px;
}

.estimate-selected-pin-title {
    min-width: 0;
}

.estimate-selected-pin-title a {
    display: block;
    color: #0b6fa4;
    font-weight: 700;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.estimate-selected-pin-title span {
    display: block;
    color: #64748b;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    font-size: 13px;
}

.estimate-selected-pin-meta {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-shrink: 0;
    color: #475569;
}

.estimate-selected-pin-meta strong {
    color: #1f2937;
    white-space: nowrap;
}

.estimate-selected-pin-status .label,
.estimate-selected-pin-status span {
    margin-bottom: 0;
    white-space: nowrap;
}

.estimate-selected-pin-clear {
    border: 1px solid #cbd5e1;
    background: #fff;
    color: #64748b;
    width: 30px;
    height: 30px;
    border-radius: 999px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    line-height: 1;
    flex-shrink: 0;
}

.estimate-selected-pin-clear:hover,
.estimate-selected-pin-clear:focus {
    color: #b91c1c;
    border-color: #fca5a5;
    background: #fff5f5;
    outline: none;
}

.sales-preview-empty-state {
    height: 100%;
    min-height: 420px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 32px;
    background: #fff;
}

.sales-preview-empty-state-inner {
    width: min(420px, 100%);
    text-align: center;
    color: #64748b;
}

.sales-preview-empty-state-icon {
    width: 52px;
    height: 52px;
    border-radius: 999px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 14px;
    color: #0b6fa4;
    background: #e6f3fb;
}

.sales-preview-empty-state-title {
    margin: 0 0 6px;
    color: #1f2937;
    font-weight: 600;
    font-size: 16px;
}

.sales-preview-empty-state-text {
    margin: 0 0 22px;
    font-size: 13px;
}

.sales-preview-empty-skeleton {
    display: grid;
    gap: 10px;
}

.sales-preview-empty-skeleton span {
    display: block;
    height: 12px;
    border-radius: 999px;
    background: linear-gradient(90deg, #edf2f7 0%, #f8fafc 45%, #edf2f7 90%);
    background-size: 220% 100%;
    animation: salesPreviewShimmer 1.35s ease-in-out infinite;
}

.sales-preview-empty-skeleton span:nth-child(2) {
    width: 82%;
    margin: 0 auto;
}

.sales-preview-empty-skeleton span:nth-child(3) {
    width: 64%;
    margin: 0 auto;
}

@keyframes salesPreviewShimmer {
    from { background-position: 120% 0; }
    to { background-position: -120% 0; }
}

.estimates-outlook-content .estimate-row-select {
    display: inline-block;
    margin-right: 8px;
    vertical-align: middle;
}

@media (max-width: 1199px) {
    body.small-table .estimates-outlook-content .estimates-outlook-split {
        display: block;
    }

    body.small-table .estimates-outlook-content #small-table,
    body.small-table .estimates-outlook-content .small-table-right-col {
        min-width: 0;
        width: 100%;
        max-width: none;
    }

    .estimates-outlook-resizer {
        display: none !important;
    }
}
</style>
<?php init_tail(); ?>
<script>
$(function() {
    init_estimate();
    init_estimates_outlook_layout();
});

function init_estimates_outlook_layout() {
    var $root = $('.estimates-outlook-split');
    var $table = $('.table-estimates');
    var $resizer = $('.estimates-outlook-resizer');
    var $pin = $('<div class="estimate-selected-pin" aria-live="polite"></div>').appendTo('body');
    var storedWidth = localStorage.getItem('estimate_preview_width');
    var dragging = false;

    if (storedWidth) {
        $root[0].style.setProperty('--estimate-preview-width', storedWidth + 'px');
    }

    syncEstimateSplitHeight();

    $resizer.on('mousedown', function(e) {
        dragging = true;
        $resizer.addClass('is-dragging');
        $('body').css({
            cursor: 'col-resize',
            userSelect: 'none'
        });
        e.preventDefault();
    });

    $(document).on('mousemove.estimatesOutlook', function(e) {
        if (!dragging || !$root.length) {
            return;
        }

        var rect = $root[0].getBoundingClientRect();
        var width = Math.min(Math.max(rect.right - e.clientX, 420), 760);
        $root[0].style.setProperty('--estimate-preview-width', width + 'px');
        localStorage.setItem('estimate_preview_width', width);
        updateEstimateSelectedPin();
    });

    $(document).on('mouseup.estimatesOutlook', function() {
        dragging = false;
        $resizer.removeClass('is-dragging');
        $('body').css({
            cursor: '',
            userSelect: ''
        });
    });

    $('body').on('click', '.table-estimates tbody tr', function(e) {
        if ($(e.target).is('input[type="checkbox"], label')) {
            return;
        }
        $table.find('tbody tr').removeClass('is-preview-selected');
        $(this).addClass('is-preview-selected');
        showEstimatePreviewLoading();
        updateEstimateSelectedPin();
    });

    $('body').on('click', '.estimate-selected-pin-clear', function(e) {
        e.preventDefault();
        $table.find('tbody tr').removeClass('is-preview-selected');
        $pin.removeClass('is-visible').empty();
        $('input[name="estimateid"]').val('');
        showEstimatePreviewPlaceholder();
        if (window.history && window.history.replaceState) {
            window.history.replaceState(null, document.title, window.location.pathname + window.location.search);
        }
    });

    $table.on('draw.dt', function() {
        updateEstimateSelectedPin();
    });

    var originalSmallTableFullView = window.small_table_full_view;
    window.small_table_full_view = function() {
        if (!$('.estimates-outlook-split').length) {
            if (typeof originalSmallTableFullView === 'function') {
                return originalSmallTableFullView();
            }
            return;
        }

        $('#small-table').toggleClass('hide');
        $('.small-table-right-col').toggleClass('col-md-12 col-md-7');
        $('.estimates-outlook-split').toggleClass('is-preview-full', $('#small-table').hasClass('hide'));
        $('.toggle_view i').toggleClass('fa-expand fa-compress');
        syncEstimateSplitHeight();
        $(window).trigger('resize');
        updateEstimateSelectedPin();
    };

    $(window).on('resize.estimatesOutlook', function() {
        syncEstimateSplitHeight();
        updateEstimateSelectedPin();
    });
    $(window).on('scroll.estimatesOutlook', updateEstimateSelectedPin);
    setTimeout(updateEstimateSelectedPin, 500);

    function syncEstimateSplitHeight() {
        if (!$root.length || !$('body').hasClass('small-table')) {
            $root.css('height', '');
            return;
        }

        var top = $root[0].getBoundingClientRect().top;
        var height = Math.max(window.innerHeight - top - 18, 560);
        $root.css('height', height + 'px');
    }

    function showEstimatePreviewPlaceholder() {
        $('#estimate')
            .removeClass('hide')
            .html(
                '<div class="sales-preview-empty-state">'
                + '  <div class="sales-preview-empty-state-inner">'
                + '    <div class="sales-preview-empty-state-icon"><i class="fa-regular fa-file-lines"></i></div>'
                + '    <p class="sales-preview-empty-state-title">Chưa chọn estimate</p>'
                + '    <p class="sales-preview-empty-state-text">Chọn một dòng trong danh sách để xem preview và thao tác nhanh.</p>'
                + '    <div class="sales-preview-empty-skeleton" aria-hidden="true"><span></span><span></span><span></span></div>'
                + '  </div>'
                + '</div>'
            );
    }

    function showEstimatePreviewLoading() {
        $('#estimate')
            .removeClass('hide')
            .html(
                '<div class="sales-preview-empty-state">'
                + '  <div class="sales-preview-empty-state-inner">'
                + '    <div class="sales-preview-empty-state-icon"><i class="fa fa-spinner fa-spin"></i></div>'
                + '    <p class="sales-preview-empty-state-title">Đang tải preview</p>'
                + '    <p class="sales-preview-empty-state-text">Đang lấy dữ liệu estimate và dựng bản xem trước.</p>'
                + '    <div class="sales-preview-empty-skeleton" aria-hidden="true"><span></span><span></span><span></span></div>'
                + '  </div>'
                + '</div>'
            );
    }

    function updateEstimateSelectedPin() {
        var $selected = $('.table-estimates tbody tr.is-preview-selected:first');
        var smallTable = document.getElementById('small-table');
        var fullPreview = $('.estimates-outlook-split').hasClass('is-preview-full');

        if (!$('body').hasClass('small-table') || !$selected.length || !smallTable || $('#small-table').hasClass('hide') || fullPreview) {
            $pin.removeClass('is-visible').empty();
            return;
        }

        var rect = smallTable.getBoundingClientRect();
        if (rect.width <= 0 || rect.bottom <= 0 || rect.top >= window.innerHeight) {
            $pin.removeClass('is-visible').empty();
            return;
        }

        var rowData = [];
        try {
            rowData = $('.table-estimates').DataTable().row($selected).data() || [];
        } catch (e) {
            rowData = [];
        }

        var estimateLink = $selected.find('td:first a[onclick*="init_estimate"]').first();
        var estimateNumber = $.trim(estimateLink.text()) || cleanCell(rowData[0]);
        var estimateHref = estimateLink.attr('href') || '#';
        var estimateOnclick = estimateLink.attr('onclick') || '';
        var estimateId = extractEstimateId(estimateHref, estimateOnclick, estimateNumber);
        var $cells = $selected.children('td');
        var amount = cleanCell($cells.eq(1).html() || rowData[1]);
        var customer = cleanCell($cells.eq(2).html() || rowData[4]);
        var date = cleanCell($cells.eq(3).html() || rowData[7]);
        var statusHtml = $cells.eq(4).html() || rowData[10] || '';
        var checked = $selected.find('td:first input[type="checkbox"]').prop('checked') ? ' checked' : '';
        var card = ''
            + '<div class="estimate-selected-pin-card">'
            + '  <div class="estimate-selected-pin-main">'
            + '    <div class="checkbox no-mbot"><input type="checkbox" disabled' + checked + '><label></label></div>'
            + '    <div class="estimate-selected-pin-title">'
            + '      <a href="' + escapeAttr(estimateHref) + '" onclick="init_estimate(' + estimateId + '); return false;">' + escapeHtml(estimateNumber) + '</a>'
            + '      <span>' + escapeHtml(customer || 'No customer') + '</span>'
            + '    </div>'
            + '  </div>'
            + '  <div class="estimate-selected-pin-meta">'
            + '    <strong>' + escapeHtml(amount) + '</strong>'
            + '    <span>' + escapeHtml(date) + '</span>'
            + '    <span class="estimate-selected-pin-status">' + statusHtml + '</span>'
            + '    <button type="button" class="estimate-selected-pin-clear" title="Clear selection" aria-label="Clear selected estimate"><i class="fa fa-times"></i></button>'
            + '  </div>'
            + '</div>';

        $pin
            .empty()
            .append(card)
            .css({
                left: Math.max(rect.left, 0) + 'px',
                width: Math.max(rect.width, 280) + 'px'
            })
            .addClass('is-visible');
    }

    function cleanCell(value) {
        return $.trim($('<div>').html(value || '').text().replace(/\s+/g, ' '));
    }

    function escapeHtml(value) {
        return $('<div>').text(value || '').html();
    }

    function escapeAttr(value) {
        return escapeHtml(value).replace(/"/g, '&quot;');
    }

    function extractEstimateId(href, onclick, fallbackText) {
        var match = String(href || '').match(/list_estimates\/(\d+)/)
            || String(onclick || '').match(/init_estimate\((\d+)\)/)
            || String(fallbackText || '').match(/(\d+)/);
        return match ? match[1] : "''";
    }
}
</script>
</body>

</html>
