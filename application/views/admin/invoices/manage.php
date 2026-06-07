<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
	<div class="content invoices-outlook-content">
		<div id="vueApp">
			<div class="row">
				<div class="col-md-12 tw-mb-3 md:tw-mb-6">
					<div class="md:tw-flex md:tw-items-center">
						<div class="tw-grow">
							<h4 class="tw-my-0 tw-font-bold tw-text-xl">
								<?= _l('invoices'); ?>
							</h4>
							<?php if (! isset($project)) { ?>
							<a href="<?= admin_url('invoices/recurring'); ?>"
								class="tw-mr-4">
								<?= _l('invoices_list_recurring'); ?>
								&rarr;
							</a>
							<?php } ?>
						</div>

						<div id="invoices_total" data-type="badge"
							class="tw-self-start tw-mt-2 md:tw-mt-0 empty:tw-min-h-[60px]"></div>
					</div>

				</div>
				<div class="col-md-12">
					<?php $this->load->view('admin/invoices/quick_stats'); ?>
				</div>
				<?php include_once APPPATH . 'views/admin/invoices/filter_params.php'; ?>
				<?php $this->load->view('admin/invoices/list_template'); ?>
			</div>
		</div>
	</div>
</div>
<?php $this->load->view('admin/includes/modals/sales_attach_file'); ?>
<div id="modal-wrapper"></div>
<script>
	var hidden_columns = [2, 6, 7, 8];
</script>
<?php init_tail(); ?>
<style>
.invoices-outlook-content .panel-table-full {
    width: 100%;
}

.invoices-outlook-content #small-table .panel_s,
.invoices-outlook-content #small-table .panel-body {
    height: 100%;
}

.invoices-outlook-content .invoices-outlook-split {
    --invoice-preview-width: 560px;
}

body.small-table .invoices-outlook-content .invoices-outlook-split {
    display: flex;
    align-items: stretch;
    gap: 0;
    overflow: hidden;
    min-height: 560px;
}

body.small-table .invoices-outlook-content #small-table {
    float: none;
    flex: 1 1 auto;
    width: auto;
    max-width: none;
    min-width: 560px;
    padding-right: 0;
    height: 100%;
    overflow: hidden;
}

body.small-table .invoices-outlook-content .small-table-right-col {
    float: none;
    flex: 0 0 var(--invoice-preview-width);
    width: var(--invoice-preview-width);
    max-width: 760px;
    min-width: 420px;
    padding-left: 0;
    height: 100%;
    overflow: hidden;
}

body.small-table .invoices-outlook-content .invoices-outlook-split.is-preview-full .small-table-right-col {
    flex: 1 1 100%;
    width: 100%;
    max-width: none;
    min-width: 0;
}

body.small-table .invoices-outlook-content .invoices-outlook-split.is-preview-full .invoices-outlook-resizer {
    display: none;
}

.invoices-outlook-resizer {
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

body.small-table .invoices-outlook-resizer {
    display: block;
}

.invoices-outlook-resizer:before {
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

.invoices-outlook-resizer:hover,
.invoices-outlook-resizer.is-dragging {
    background: #e6f3fb;
}

body.small-table .invoices-outlook-content #small-table .panel_s,
body.small-table .invoices-outlook-content #invoice > .col-md-12 > .panel_s {
    border-radius: 8px;
    height: 100%;
}

body.small-table .invoices-outlook-content #small-table .panel-body {
    overflow: auto;
}

body.small-table .invoices-outlook-content #invoice {
    height: 100%;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

body.small-table .invoices-outlook-content #invoice > .col-md-12,
body.small-table .invoices-outlook-content #invoice > .col-md-12 > .panel_s,
body.small-table .invoices-outlook-content #invoice > .col-md-12 > .panel_s > .panel-body {
    min-height: 0;
}

body.small-table .invoices-outlook-content #invoice > .alert,
body.small-table .invoices-outlook-content #invoice > .mergeable-invoices {
    flex: 0 0 auto;
}

body.small-table .invoices-outlook-content #invoice > .col-md-12 {
    flex: 1 1 auto;
    height: auto;
    overflow: hidden;
}

body.small-table .invoices-outlook-content #invoice > .col-md-12 > .panel_s,
body.small-table .invoices-outlook-content #invoice > .col-md-12 > .panel_s > .panel-body {
    height: 100%;
}

body.small-table .invoices-outlook-content #invoice > .col-md-12 > .panel_s > .panel-body {
    overflow: auto;
}

body.small-table .invoices-outlook-content #invoice-preview .table-responsive {
    overflow-x: auto;
    overflow-y: visible;
    width: 100%;
}

body.small-table .invoices-outlook-content #invoice-preview table.items {
    width: max-content;
    min-width: 920px;
    table-layout: auto;
}

body.small-table .invoices-outlook-content #invoice-preview table.items th,
body.small-table .invoices-outlook-content #invoice-preview table.items td {
    white-space: nowrap;
    vertical-align: top;
}

body.small-table .invoices-outlook-content #invoice-preview table.items th:last-child,
body.small-table .invoices-outlook-content #invoice-preview table.items td:last-child {
    position: static;
    min-width: 150px;
    background: #fff;
    box-shadow: none;
    pointer-events: auto;
}

body.small-table .invoices-outlook-content #invoice-preview table.items th:last-child {
    background: #f3f4f6;
}

body.small-table .invoices-outlook-content #invoice-preview table.items .description,
body.small-table .invoices-outlook-content #invoice-preview table.items .long_description,
body.small-table .invoices-outlook-content #invoice-preview table.items td:nth-child(2) {
    white-space: nowrap;
    min-width: 360px;
}

body.small-table .invoices-outlook-content #invoice-preview table.items td:nth-child(2) br,
body.small-table .invoices-outlook-content #invoice-preview table.items td:nth-child(2) p:empty {
    display: none;
}

body.small-table .invoices-outlook-content #invoice-preview table.items td:nth-child(2) p,
body.small-table .invoices-outlook-content #invoice-preview table.items td:nth-child(2) div {
    display: inline;
    margin: 0;
}

body.small-table .invoices-outlook-content #invoice-preview table.items td:nth-child(2) p:after,
body.small-table .invoices-outlook-content #invoice-preview table.items td:nth-child(2) div:after {
    content: " ";
}

.invoices-outlook-content .table-invoices tbody tr.is-preview-selected > td {
    background: #ebf5ff !important;
}

.invoices-outlook-content .table-invoices tbody tr.is-preview-selected > td:first-child {
    box-shadow: inset 3px 0 0 #0b6fa4;
}

.invoice-selected-pin {
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

body.small-table .invoice-selected-pin.is-visible {
    display: block;
}

.invoice-selected-pin-card {
    min-height: 76px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    padding: 12px 16px;
    background: #ebf5ff;
    box-shadow: inset 3px 0 0 #0b6fa4;
}

.invoice-selected-pin-main {
    min-width: 0;
    display: flex;
    align-items: center;
    gap: 12px;
}

.invoice-selected-pin-title {
    min-width: 0;
}

.invoice-selected-pin-title a {
    display: block;
    color: #0b6fa4;
    font-weight: 700;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.invoice-selected-pin-title span {
    display: block;
    color: #64748b;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    font-size: 13px;
}

.invoice-selected-pin-meta {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-shrink: 0;
    color: #475569;
}

.invoice-selected-pin-meta strong {
    color: #1f2937;
    white-space: nowrap;
}

.invoice-selected-pin-status .label,
.invoice-selected-pin-status span {
    margin-bottom: 0;
    white-space: nowrap;
}

.invoice-selected-pin-clear {
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

.invoice-selected-pin-clear:hover,
.invoice-selected-pin-clear:focus {
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

.invoices-outlook-content .invoice-row-select {
    display: inline-block;
    margin-right: 8px;
    vertical-align: middle;
}

@media (max-width: 1199px) {
    body.small-table .invoices-outlook-content .invoices-outlook-split {
        display: block;
    }

    body.small-table .invoices-outlook-content #small-table,
    body.small-table .invoices-outlook-content .small-table-right-col {
        min-width: 0;
        width: 100%;
        max-width: none;
    }

    .invoices-outlook-resizer {
        display: none !important;
    }
}
</style>
<script>
	$(function() {
		init_invoice();
        init_invoices_outlook_layout();
	});

    function init_invoices_outlook_layout() {
        var $root = $('.invoices-outlook-split');
        if (!$root.length) {
            return;
        }

        var $table = $('.table-invoices');
        var $resizer = $('.invoices-outlook-resizer');
        var $pin = $('<div class="invoice-selected-pin" aria-live="polite"></div>').appendTo('body');
        var storedWidth = localStorage.getItem('invoice_preview_width');
        var dragging = false;

        if (storedWidth) {
            $root[0].style.setProperty('--invoice-preview-width', storedWidth + 'px');
        }

        syncInvoiceSplitHeight();

        $resizer.on('mousedown', function(e) {
            dragging = true;
            $resizer.addClass('is-dragging');
            $('body').css({ cursor: 'col-resize', userSelect: 'none' });
            e.preventDefault();
        });

        $(document).on('mousemove.invoicesOutlook', function(e) {
            if (!dragging || !$root.length) {
                return;
            }

            var rect = $root[0].getBoundingClientRect();
            var width = Math.min(Math.max(rect.right - e.clientX, 420), 760);
            $root[0].style.setProperty('--invoice-preview-width', width + 'px');
            localStorage.setItem('invoice_preview_width', width);
            updateInvoiceSelectedPin();
        });

        $(document).on('mouseup.invoicesOutlook', function() {
            dragging = false;
            $resizer.removeClass('is-dragging');
            $('body').css({ cursor: '', userSelect: '' });
        });

        $('body').on('click', '.table-invoices tbody tr', function(e) {
            if ($(e.target).is('input[type="checkbox"], label')) {
                return;
            }
            $table.find('tbody tr').removeClass('is-preview-selected');
            $(this).addClass('is-preview-selected');
            showInvoicePreviewLoading();
            updateInvoiceSelectedPin();
        });

        $('body').on('click', '.invoice-selected-pin-clear', function(e) {
            e.preventDefault();
            $table.find('tbody tr').removeClass('is-preview-selected');
            $pin.removeClass('is-visible').empty();
            $('input[name="invoiceid"]').val('');
            showInvoicePreviewPlaceholder();
            if (window.history && window.history.replaceState) {
                window.history.replaceState(null, document.title, window.location.pathname + window.location.search);
            }
        });

        $table.on('draw.dt', function() {
            updateInvoiceSelectedPin();
        });

        var originalSmallTableFullView = window.small_table_full_view;
        window.small_table_full_view = function() {
            if (!$('.invoices-outlook-split').length) {
                if (typeof originalSmallTableFullView === 'function') {
                    return originalSmallTableFullView();
                }
                return;
            }

            $('#small-table').toggleClass('hide');
            $('.small-table-right-col').toggleClass('col-md-12 col-md-7');
            $('.invoices-outlook-split').toggleClass('is-preview-full', $('#small-table').hasClass('hide'));
            $('.toggle_view i, a[onclick*="small_table_full_view"] i').toggleClass('fa-expand fa-compress');
            syncInvoiceSplitHeight();
            $(window).trigger('resize');
            updateInvoiceSelectedPin();
        };

        $(window).on('resize.invoicesOutlook', function() {
            syncInvoiceSplitHeight();
            updateInvoiceSelectedPin();
        });
        $(window).on('scroll.invoicesOutlook', updateInvoiceSelectedPin);
        setTimeout(updateInvoiceSelectedPin, 500);

        function syncInvoiceSplitHeight() {
            if (!$root.length || !$('body').hasClass('small-table')) {
                $root.css('height', '');
                return;
            }

            var top = $root[0].getBoundingClientRect().top;
            var height = Math.max(window.innerHeight - top - 18, 560);
            $root.css('height', height + 'px');
        }

        function showInvoicePreviewPlaceholder() {
            $('#invoice')
                .removeClass('hide')
                .html(
                    '<div class="sales-preview-empty-state">'
                    + '  <div class="sales-preview-empty-state-inner">'
                    + '    <div class="sales-preview-empty-state-icon"><i class="fa-regular fa-file-lines"></i></div>'
                    + '    <p class="sales-preview-empty-state-title">Chưa chọn invoice</p>'
                    + '    <p class="sales-preview-empty-state-text">Chọn một dòng trong danh sách để xem preview và thao tác nhanh.</p>'
                    + '    <div class="sales-preview-empty-skeleton" aria-hidden="true"><span></span><span></span><span></span></div>'
                    + '  </div>'
                    + '</div>'
                );
        }

        function showInvoicePreviewLoading() {
            $('#invoice')
                .removeClass('hide')
                .html(
                    '<div class="sales-preview-empty-state">'
                    + '  <div class="sales-preview-empty-state-inner">'
                    + '    <div class="sales-preview-empty-state-icon"><i class="fa fa-spinner fa-spin"></i></div>'
                    + '    <p class="sales-preview-empty-state-title">Đang tải preview</p>'
                    + '    <p class="sales-preview-empty-state-text">Đang lấy dữ liệu invoice và dựng bản xem trước.</p>'
                    + '    <div class="sales-preview-empty-skeleton" aria-hidden="true"><span></span><span></span><span></span></div>'
                    + '  </div>'
                    + '</div>'
                );
        }

        function updateInvoiceSelectedPin() {
            var $selected = $('.table-invoices tbody tr.is-preview-selected:first');
            var smallTable = document.getElementById('small-table');
            var fullPreview = $('.invoices-outlook-split').hasClass('is-preview-full');

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
                rowData = $('.table-invoices').DataTable().row($selected).data() || [];
            } catch (e) {
                rowData = [];
            }

            var invoiceLink = $selected.find('td:first a[onclick*="init_invoice"]').first();
            var invoiceNumber = $.trim(invoiceLink.text()) || cleanCell(rowData[0]);
            var invoiceHref = invoiceLink.attr('href') || '#';
            var invoiceOnclick = invoiceLink.attr('onclick') || '';
            var invoiceId = extractInvoiceId(invoiceHref, invoiceOnclick, invoiceNumber);
            var $cells = $selected.children('td');
            var total = cleanCell($cells.eq(1).html() || rowData[1]);
            var date = cleanCell($cells.eq(3).html() || rowData[4]);
            var customer = cleanCell($cells.eq(4).html() || rowData[5]);
            var statusHtml = $cells.last().html() || rowData[9] || '';
            var checked = $selected.find('td:first input[type="checkbox"]').prop('checked') ? ' checked' : '';
            var card = ''
                + '<div class="invoice-selected-pin-card">'
                + '  <div class="invoice-selected-pin-main">'
                + '    <div class="checkbox no-mbot"><input type="checkbox" disabled' + checked + '><label></label></div>'
                + '    <div class="invoice-selected-pin-title">'
                + '      <a href="' + escapeAttr(invoiceHref) + '" onclick="init_invoice(' + invoiceId + '); return false;">' + escapeHtml(invoiceNumber) + '</a>'
                + '      <span>' + escapeHtml(customer || 'No customer') + '</span>'
                + '    </div>'
                + '  </div>'
                + '  <div class="invoice-selected-pin-meta">'
                + '    <strong>' + escapeHtml(total) + '</strong>'
                + '    <span>' + escapeHtml(date) + '</span>'
                + '    <span class="invoice-selected-pin-status">' + statusHtml + '</span>'
                + '    <button type="button" class="invoice-selected-pin-clear" title="Clear selection" aria-label="Clear selected invoice"><i class="fa fa-times"></i></button>'
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

        function extractInvoiceId(href, onclick, fallbackText) {
            var match = String(href || '').match(/list_invoices\/(\d+)/)
                || String(onclick || '').match(/init_invoice\((\d+)\)/)
                || String(fallbackText || '').match(/(\d+)/);
            return match ? match[1] : "''";
        }
    }
</script>
</body>

</html>
