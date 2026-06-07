<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content proposals-outlook-content">
        <div class="row">
            <div class="panel-table-full">
                <?php $this->load->view('admin/proposals/list_template'); ?>
            </div>
        </div>
    </div>
</div>
<?php $this->load->view('admin/includes/modals/sales_attach_file'); ?>
<script>
    var hidden_columns = [4, 5, 6, 7, 8];
</script>
<?php init_tail(); ?>
<style>
.proposals-outlook-content .panel-table-full {
    width: 100%;
}

.proposals-outlook-content #small-table .panel_s,
.proposals-outlook-content #small-table .panel-body {
    height: 100%;
}

.proposals-outlook-content .proposals-outlook-split {
    --proposal-preview-width: 560px;
}

body.small-table .proposals-outlook-content .proposals-outlook-split {
    display: flex;
    align-items: stretch;
    gap: 0;
    overflow: hidden;
    min-height: 560px;
}

body.small-table .proposals-outlook-content #small-table {
    float: none;
    flex: 1 1 auto;
    width: auto;
    max-width: none;
    min-width: 560px;
    padding-right: 0;
    height: 100%;
    overflow: hidden;
}

body.small-table .proposals-outlook-content .small-table-right-col {
    float: none;
    flex: 0 0 var(--proposal-preview-width);
    width: var(--proposal-preview-width);
    max-width: 760px;
    min-width: 420px;
    padding-left: 0;
    height: 100%;
    overflow: hidden;
    min-inline-size: 0;
}

body.small-table .proposals-outlook-content .proposals-outlook-split.is-preview-full .small-table-right-col {
    flex: 1 1 100%;
    width: 100%;
    max-width: none;
    min-width: 0;
}

body.small-table .proposals-outlook-content .proposals-outlook-split.is-preview-full .proposals-outlook-resizer {
    display: none;
}

.proposals-outlook-resizer {
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

body.small-table .proposals-outlook-resizer {
    display: block;
}

.proposals-outlook-resizer:before {
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

.proposals-outlook-resizer:hover,
.proposals-outlook-resizer.is-dragging {
    background: #e6f3fb;
}

body.small-table .proposals-outlook-content #small-table .panel_s,
body.small-table .proposals-outlook-content #proposal > .panel_s,
body.small-table .proposals-outlook-content #proposal > .col-md-12 > .panel_s {
    border-radius: 8px;
    height: 100%;
}

body.small-table .proposals-outlook-content #small-table .panel-body {
    overflow: auto;
}

body.small-table .proposals-outlook-content #proposal {
    height: 100%;
    overflow: hidden;
    min-width: 0;
    max-width: 100%;
    display: flex;
    flex-direction: column;
}

body.small-table .proposals-outlook-content #proposal > .panel_s,
body.small-table .proposals-outlook-content #proposal > .col-md-12,
body.small-table .proposals-outlook-content #proposal > .col-md-12 > .panel_s,
body.small-table .proposals-outlook-content #proposal > .col-md-12 > .panel_s > .panel-body {
    height: 100%;
    min-width: 0;
    max-width: 100%;
    min-height: 0;
}

body.small-table .proposals-outlook-content #proposal > .panel_s {
    flex: 1 1 auto;
    margin-bottom: 0;
}

body.small-table .proposals-outlook-content #proposal > .panel_s > .panel-body {
    height: 100%;
    min-height: 0;
    overflow-y: auto;
    overflow-x: hidden;
}

body.small-table .proposals-outlook-content #proposal > .col-md-12 > .panel_s > .panel-body,
body.small-table .proposals-outlook-content #proposal .panel_s > .panel-body {
    overflow-y: auto;
    overflow-x: hidden;
}

body.small-table .proposals-outlook-content #tab_proposal {
    min-width: 0 !important;
    width: 100%;
    max-width: 100%;
    overflow-x: hidden;
}

body.small-table .proposals-outlook-content #proposal_content_area {
    width: 100%;
    max-width: 100%;
    min-width: 0;
    overflow-x: visible;
    overflow-y: visible;
}

body.small-table .proposals-outlook-content #proposal_content_area .proposal-items-viewer {
    width: 100%;
    max-width: 100%;
    min-width: 0;
    overflow-x: auto;
    overflow-y: visible;
    -webkit-overflow-scrolling: touch;
}

body.small-table .proposals-outlook-content #proposal_content_area .table-responsive {
    display: block;
    position: relative;
    max-width: 100%;
    overflow-x: auto;
    overflow-y: visible;
    width: 100%;
    -webkit-overflow-scrolling: touch;
}

body.small-table .proposals-outlook-content #proposal_content_area .proposal-items-scroll {
    display: block;
    position: relative;
    width: 100%;
    max-width: 100%;
    overflow-x: auto !important;
    overflow-y: visible;
    -webkit-overflow-scrolling: touch;
}

body.small-table .proposals-outlook-content #proposal_content_area table.items.items-preview,
body.small-table .proposals-outlook-content #proposal_content_area table.proposal-items-preview {
    width: max-content;
    min-width: 980px;
    table-layout: auto;
}

body.small-table .proposals-outlook-content #proposal_content_area table.items.items-preview th,
body.small-table .proposals-outlook-content #proposal_content_area table.items.items-preview td,
body.small-table .proposals-outlook-content #proposal_content_area table.proposal-items-preview th,
body.small-table .proposals-outlook-content #proposal_content_area table.proposal-items-preview td {
    white-space: nowrap;
    vertical-align: top;
}

body.small-table .proposals-outlook-content #proposal_content_area table.items.items-preview th:last-child,
body.small-table .proposals-outlook-content #proposal_content_area table.items.items-preview td:last-child,
body.small-table .proposals-outlook-content #proposal_content_area table.proposal-items-preview th:last-child,
body.small-table .proposals-outlook-content #proposal_content_area table.proposal-items-preview td:last-child {
    position: static;
    min-width: 150px;
    background: #fff;
    box-shadow: none;
    pointer-events: auto;
}

body.small-table .proposals-outlook-content #proposal_content_area table.items.items-preview th:last-child,
body.small-table .proposals-outlook-content #proposal_content_area table.proposal-items-preview th:last-child {
    background: #f3f4f6;
}

body.small-table .proposals-outlook-content #proposal_content_area table.items.items-preview .description,
body.small-table .proposals-outlook-content #proposal_content_area table.items.items-preview .long_description,
body.small-table .proposals-outlook-content #proposal_content_area table.items.items-preview td:nth-child(2),
body.small-table .proposals-outlook-content #proposal_content_area table.proposal-items-preview .description,
body.small-table .proposals-outlook-content #proposal_content_area table.proposal-items-preview .long_description,
body.small-table .proposals-outlook-content #proposal_content_area table.proposal-items-preview td:nth-child(2) {
    white-space: nowrap;
    min-width: 360px;
}

body.small-table .proposals-outlook-content #proposal_content_area table.items.items-preview td:nth-child(2) br,
body.small-table .proposals-outlook-content #proposal_content_area table.items.items-preview td:nth-child(2) p:empty,
body.small-table .proposals-outlook-content #proposal_content_area table.proposal-items-preview td:nth-child(2) br,
body.small-table .proposals-outlook-content #proposal_content_area table.proposal-items-preview td:nth-child(2) p:empty {
    display: none;
}

body.small-table .proposals-outlook-content #proposal_content_area table.items.items-preview td:nth-child(2) p,
body.small-table .proposals-outlook-content #proposal_content_area table.items.items-preview td:nth-child(2) div,
body.small-table .proposals-outlook-content #proposal_content_area table.proposal-items-preview td:nth-child(2) p,
body.small-table .proposals-outlook-content #proposal_content_area table.proposal-items-preview td:nth-child(2) div {
    display: inline;
    margin: 0;
}

body.small-table .proposals-outlook-content #proposal_content_area table.items.items-preview td:nth-child(2) p:after,
body.small-table .proposals-outlook-content #proposal_content_area table.items.items-preview td:nth-child(2) div:after,
body.small-table .proposals-outlook-content #proposal_content_area table.proposal-items-preview td:nth-child(2) p:after,
body.small-table .proposals-outlook-content #proposal_content_area table.proposal-items-preview td:nth-child(2) div:after {
    content: " ";
}

.proposals-outlook-content .table-proposals tbody tr.is-preview-selected > td {
    background: #ebf5ff !important;
}

.proposals-outlook-content .table-proposals tbody tr.is-preview-selected > td:first-child {
    box-shadow: inset 3px 0 0 #0b6fa4;
}

.proposal-selected-pin {
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

body.small-table .proposal-selected-pin.is-visible {
    display: block;
}

.proposal-selected-pin-card {
    min-height: 76px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    padding: 12px 16px;
    background: #ebf5ff;
    box-shadow: inset 3px 0 0 #0b6fa4;
}

.proposal-selected-pin-main {
    min-width: 0;
    display: flex;
    align-items: center;
    gap: 12px;
}

.proposal-selected-pin-title {
    min-width: 0;
}

.proposal-selected-pin-title a {
    display: block;
    color: #0b6fa4;
    font-weight: 700;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.proposal-selected-pin-title span {
    display: block;
    color: #64748b;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    font-size: 13px;
}

.proposal-selected-pin-meta {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-shrink: 0;
    color: #475569;
}

.proposal-selected-pin-meta strong {
    color: #1f2937;
    white-space: nowrap;
}

.proposal-selected-pin-status .label,
.proposal-selected-pin-status span {
    margin-bottom: 0;
    white-space: nowrap;
}

.proposal-selected-pin-clear {
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

.proposal-selected-pin-clear:hover,
.proposal-selected-pin-clear:focus {
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

.proposals-outlook-content .proposal-row-select {
    display: inline-block;
    margin-right: 8px;
    vertical-align: middle;
}

@media (max-width: 1199px) {
    body.small-table .proposals-outlook-content .proposals-outlook-split {
        display: block;
    }

    body.small-table .proposals-outlook-content #small-table,
    body.small-table .proposals-outlook-content .small-table-right-col {
        min-width: 0;
        width: 100%;
        max-width: none;
    }

    .proposals-outlook-resizer {
        display: none !important;
    }
}
</style>
<div id="convert_helper"></div>
<script>
    var proposal_id;
    $(function() {
        var Proposals_ServerParams = {};
        $.each($('._hidden_inputs._filters input'), function() {
            Proposals_ServerParams[$(this).attr('name')] = '[name="' + $(this).attr('name') + '"]';
        });
        initDataTable('.table-proposals', admin_url + 'proposals/table', ['undefined'], ['undefined'],
            Proposals_ServerParams, [8, 'desc']);
        init_proposal();
        init_proposals_outlook_layout();
    });

    function init_proposals_outlook_layout() {
        var $root = $('.proposals-outlook-split');
        if (!$root.length) {
            return;
        }

        var $table = $('.table-proposals');
        var $resizer = $('.proposals-outlook-resizer');
        var $pin = $('<div class="proposal-selected-pin" aria-live="polite"></div>').appendTo('body');
        var storedWidth = localStorage.getItem('proposal_preview_width');
        var dragging = false;

        if (storedWidth) {
            $root[0].style.setProperty('--proposal-preview-width', storedWidth + 'px');
        }

        syncProposalSplitHeight();

        $resizer.on('mousedown', function(e) {
            dragging = true;
            $resizer.addClass('is-dragging');
            $('body').css({ cursor: 'col-resize', userSelect: 'none' });
            e.preventDefault();
        });

        $(document).on('mousemove.proposalsOutlook', function(e) {
            if (!dragging || !$root.length) {
                return;
            }

            var rect = $root[0].getBoundingClientRect();
            var width = Math.min(Math.max(rect.right - e.clientX, 420), 760);
            $root[0].style.setProperty('--proposal-preview-width', width + 'px');
            localStorage.setItem('proposal_preview_width', width);
            updateProposalSelectedPin();
        });

        $(document).on('mouseup.proposalsOutlook', function() {
            dragging = false;
            $resizer.removeClass('is-dragging');
            $('body').css({ cursor: '', userSelect: '' });
        });

        $('body').on('click', '.table-proposals tbody tr', function(e) {
            if ($(e.target).is('input[type="checkbox"], label')) {
                return;
            }
            $table.find('tbody tr').removeClass('is-preview-selected');
            $(this).addClass('is-preview-selected');
            showProposalPreviewLoading();
            updateProposalSelectedPin();
        });

        $('body').on('click', '.proposal-selected-pin-clear', function(e) {
            e.preventDefault();
            $table.find('tbody tr').removeClass('is-preview-selected');
            $pin.removeClass('is-visible').empty();
            $('input[name="proposal_id"]').val('');
            showProposalPreviewPlaceholder();
            if (window.history && window.history.replaceState) {
                window.history.replaceState(null, document.title, window.location.pathname + window.location.search);
            }
        });

        $table.on('draw.dt', function() {
            updateProposalSelectedPin();
        });

        var originalSmallTableFullView = window.small_table_full_view;
        window.small_table_full_view = function() {
            if (!$('.proposals-outlook-split').length) {
                if (typeof originalSmallTableFullView === 'function') {
                    return originalSmallTableFullView();
                }
                return;
            }

            $('#small-table').toggleClass('hide');
            $('.small-table-right-col').toggleClass('col-md-12 col-md-7');
            $('.proposals-outlook-split').toggleClass('is-preview-full', $('#small-table').hasClass('hide'));
            $('.toggle_view i, a[onclick*="small_table_full_view"] i').toggleClass('fa-expand fa-compress');
            syncProposalSplitHeight();
            $(window).trigger('resize');
            updateProposalSelectedPin();
        };

        $(window).on('resize.proposalsOutlook', function() {
            syncProposalSplitHeight();
            updateProposalSelectedPin();
        });
        $(window).on('scroll.proposalsOutlook', updateProposalSelectedPin);
        $(document).ajaxComplete(function() {
            normalizeProposalPreviewTables();
        });
        if (document.getElementById('proposal_content_area') && window.MutationObserver) {
            var proposalPreviewObserver = new MutationObserver(function() {
                normalizeProposalPreviewTables();
            });
            proposalPreviewObserver.observe(document.getElementById('proposal_content_area'), {
                childList: true,
                subtree: true
            });
        }
        normalizeProposalPreviewTables();
        setTimeout(updateProposalSelectedPin, 500);

        function normalizeProposalPreviewTables() {
            $('#proposal .panel_s > .panel-body').first().scrollLeft(0);

            $('#proposal_content_area table.items.items-preview, #proposal_content_area table.proposal-items-preview').each(function() {
                var $itemsTable = $(this);
                var $parent = $itemsTable.parent();
                if (!$parent.hasClass('table-responsive')) {
                    $itemsTable.wrap('<div class="table-responsive proposal-items-scroll"></div>');
                    $parent = $itemsTable.parent();
                } else {
                    $parent.addClass('proposal-items-scroll');
                }

                var $panelBody = $('#proposal > .col-md-12 > .panel_s > .panel-body');
                if (!$panelBody.length) {
                    $panelBody = $('#proposal .panel_s > .panel-body').first();
                }
                var availableWidth = Math.floor(($panelBody.innerWidth() || $('#proposal').innerWidth() || $parent.parent().innerWidth() || 640) - 2);
                availableWidth = Math.max(availableWidth, 320);

                $parent.css({
                    display: 'block',
                    position: 'relative',
                    width: availableWidth + 'px',
                    maxWidth: availableWidth + 'px',
                    overflowX: 'auto',
                    overflowY: 'visible',
                    clear: 'both'
                });

                $itemsTable.css({
                    width: 'max-content',
                    minWidth: '920px',
                    tableLayout: 'auto'
                });

                $itemsTable.find('th:last-child,td:last-child').css({
                    position: 'static',
                    right: 'auto',
                    boxShadow: 'none',
                    pointerEvents: 'auto'
                });

                $parent.prev('.proposal-items-scroll-top').remove();
            });
        }

        function syncProposalSplitHeight() {
            if (!$root.length || !$('body').hasClass('small-table')) {
                $root.css('height', '');
                return;
            }

            var top = $root[0].getBoundingClientRect().top;
            var height = Math.max(window.innerHeight - top - 18, 560);
            $root.css('height', height + 'px');
        }

        function showProposalPreviewPlaceholder() {
            $('#proposal')
                .removeClass('hide')
                .html(
                    '<div class="sales-preview-empty-state">'
                    + '  <div class="sales-preview-empty-state-inner">'
                    + '    <div class="sales-preview-empty-state-icon"><i class="fa-regular fa-file-lines"></i></div>'
                    + '    <p class="sales-preview-empty-state-title">Chưa chọn proposal</p>'
                    + '    <p class="sales-preview-empty-state-text">Chọn một dòng trong danh sách để xem preview và thao tác nhanh.</p>'
                    + '    <div class="sales-preview-empty-skeleton" aria-hidden="true"><span></span><span></span><span></span></div>'
                    + '  </div>'
                    + '</div>'
                );
        }

        function showProposalPreviewLoading() {
            $('#proposal')
                .removeClass('hide')
                .html(
                    '<div class="sales-preview-empty-state">'
                    + '  <div class="sales-preview-empty-state-inner">'
                    + '    <div class="sales-preview-empty-state-icon"><i class="fa fa-spinner fa-spin"></i></div>'
                    + '    <p class="sales-preview-empty-state-title">Đang tải preview</p>'
                    + '    <p class="sales-preview-empty-state-text">Đang lấy dữ liệu proposal và dựng bản xem trước.</p>'
                    + '    <div class="sales-preview-empty-skeleton" aria-hidden="true"><span></span><span></span><span></span></div>'
                    + '  </div>'
                    + '</div>'
                );
        }

        function updateProposalSelectedPin() {
            var $selected = $('.table-proposals tbody tr.is-preview-selected:first');
            var smallTable = document.getElementById('small-table');
            var fullPreview = $('.proposals-outlook-split').hasClass('is-preview-full');

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
                rowData = $('.table-proposals').DataTable().row($selected).data() || [];
            } catch (e) {
                rowData = [];
            }

            var proposalLink = $selected.find('td:first a[onclick*="init_proposal"]').first();
            var proposalNumber = $.trim(proposalLink.text()) || cleanCell(rowData[0]);
            var proposalHref = proposalLink.attr('href') || '#';
            var proposalOnclick = proposalLink.attr('onclick') || '';
            var proposalId = extractProposalId(proposalHref, proposalOnclick, proposalNumber);
            var $cells = $selected.children('td');
            var subject = cleanCell($cells.eq(1).html() || rowData[1]);
            var recipient = cleanCell($cells.eq(2).html() || rowData[2]);
            var total = cleanCell($cells.eq(3).html() || rowData[3]);
            var statusHtml = $cells.last().html() || rowData[9] || '';
            var checked = $selected.find('td:first input[type="checkbox"]').prop('checked') ? ' checked' : '';
            var card = ''
                + '<div class="proposal-selected-pin-card">'
                + '  <div class="proposal-selected-pin-main">'
                + '    <div class="checkbox no-mbot"><input type="checkbox" disabled' + checked + '><label></label></div>'
                + '    <div class="proposal-selected-pin-title">'
                + '      <a href="' + escapeAttr(proposalHref) + '" onclick="init_proposal(' + proposalId + '); return false;">' + escapeHtml(proposalNumber) + '</a>'
                + '      <span>' + escapeHtml([subject, recipient].filter(Boolean).join(' · ') || 'No recipient') + '</span>'
                + '    </div>'
                + '  </div>'
                + '  <div class="proposal-selected-pin-meta">'
                + '    <strong>' + escapeHtml(total) + '</strong>'
                + '    <span class="proposal-selected-pin-status">' + statusHtml + '</span>'
                + '    <button type="button" class="proposal-selected-pin-clear" title="Clear selection" aria-label="Clear selected proposal"><i class="fa fa-times"></i></button>'
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

        function extractProposalId(href, onclick, fallbackText) {
            var match = String(href || '').match(/list_proposals\/(\d+)/)
                || String(onclick || '').match(/init_proposal\((\d+)\)/)
                || String(fallbackText || '').match(/(\d+)/);
            return match ? match[1] : "''";
        }
    }
</script>
</body>

</html>
