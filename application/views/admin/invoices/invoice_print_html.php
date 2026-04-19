<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$invoiceUrl = site_url('invoice/' . $invoice->id . '/' . $invoice->hash);
$companyLogo = pdf_logo_url();
$statusText = format_invoice_status($invoice->status, '', false);
$statusClass = get_invoice_status_label($invoice->status);
$statusClassMap = [
    'success' => 'is-success',
    'warning' => 'is-warning',
    'danger'  => 'is-danger',
    'default' => 'is-default',
];
$statusBadgeClass = $statusClassMap[$statusClass] ?? 'is-default';
$items = is_array($invoice->items ?? null) ? $invoice->items : [];
$taxSummary = get_items_table_data($invoice, 'invoice', 'html', true)->taxes();
$offlinePaymentModes = [];

foreach ($payment_modes as $mode) {
    if (!isset($mode['show_on_pdf']) || (int) $mode['show_on_pdf'] !== 1) {
        continue;
    }

    $modeId = is_array($mode) ? $mode['id'] : $mode->id;
    if (is_numeric($modeId) && !is_payment_mode_allowed_for_invoice($modeId, $invoice->id)) {
        continue;
    }

    $offlinePaymentModes[] = is_array($mode) ? (object) $mode : $mode;
}

$paymentModePrimary = $offlinePaymentModes[0] ?? null;
$qrCodeDataUri = '';
$clientDisplayName = trim((string) get_company_name($invoice->clientid, true));
$signatureImageFile = (string) get_option('signature_image');
$signatureImagePath = get_upload_path_by_type('company') . $signatureImageFile;
$signatureImageUrl = '';

if (get_option('show_pdf_signature_invoice') == 1
    && $signatureImageFile !== ''
    && is_file($signatureImagePath)) {
    $signatureImageUrl = base_url('uploads/company/' . $signatureImageFile);
}

if ($clientDisplayName === '') {
    $clientDisplayName = trim(($invoice->client->firstname ?? '') . ' ' . ($invoice->client->lastname ?? ''));
}

if (class_exists('\BaconQrCode\Writer') && class_exists('\BaconQrCode\Renderer\ImageRenderer')) {
    try {
        $renderer = new \BaconQrCode\Renderer\ImageRenderer(
            new \BaconQrCode\Renderer\RendererStyle\RendererStyle(160),
            new \BaconQrCode\Renderer\Image\SvgImageBackEnd()
        );
        $writer = new \BaconQrCode\Writer($renderer);
        $svg = $writer->writeString($invoiceUrl);
        $qrCodeDataUri = 'data:image/svg+xml;base64,' . base64_encode($svg);
    } catch (\Throwable $e) {
        $qrCodeDataUri = '';
    }
}

$cleanPaymentDescription = static function ($description) {
    $description = trim(strip_tags((string) $description, '<br><br/><strong><b><em><i>'));
    $parts = preg_split('/<br\s*\/?>/i', $description);

    return array_values(array_filter(array_map(static function ($part) {
        return trim(strip_tags($part));
    }, $parts), static function ($part) {
        return $part !== '';
    }));
};
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(_l('invoice_pdf_heading') . ' ' . $invoice_number); ?></title>
    <style>
        :root {
            --page-bg: #efede8;
            --paper: #ffffff;
            --paper-soft: #f3f4f3;
            --paper-muted: #eceeed;
            --text: #1f2328;
            --muted: #646b73;
            --border: #d8dcdf;
            --border-soft: #e8ebee;
            --primary: #0e67b7;
            --primary-soft: #e9f3ff;
            --danger-bg: #ffd9d6;
            --danger-text: #8c211f;
            --warning-bg: #ffe9b8;
            --warning-text: #8b5b00;
            --success-bg: #d8f3dd;
            --success-text: #23683d;
            --default-bg: #eceeed;
            --default-text: #51575d;
        }
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; }
        body {
            min-height: 100vh;
            background: var(--page-bg);
            color: var(--text);
            font-family: Inter, "Segoe UI", Arial, sans-serif;
            line-height: 1.45;
            padding: 40px 16px;
        }
        .invoice-shell {
            width: 100%;
            max-width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            background: var(--paper);
            border-radius: 8px;
            box-shadow: 0 18px 60px rgba(15, 23, 42, 0.10);
            padding: 32px;
        }
        .invoice-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 24px;
            margin-bottom: 28px;
        }
        .invoice-qr {
            width: 64px;
            height: 64px;
            border-radius: 8px;
            background: var(--paper-soft);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            flex: 0 0 64px;
        }
        .invoice-qr img {
            width: 46px;
            height: 46px;
            display: block;
        }
        .invoice-qr-fallback {
            color: var(--primary);
            font-size: 28px;
            font-weight: 700;
        }
        .invoice-heading {
            text-align: right;
            margin-left: auto;
        }
        .invoice-heading h1 {
            margin: 0 0 2px;
            font-size: 34px;
            line-height: 1;
            letter-spacing: -0.04em;
        }
        .invoice-number {
            margin: 0 0 12px;
            color: var(--muted);
            font-size: 12px;
        }
        .status-badge {
            display: inline-flex;
            align-items: center;
            border-radius: 6px;
            padding: 4px 10px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .status-badge.is-danger { background: var(--danger-bg); color: var(--danger-text); }
        .status-badge.is-warning { background: var(--warning-bg); color: var(--warning-text); }
        .status-badge.is-success { background: var(--success-bg); color: var(--success-text); }
        .status-badge.is-default { background: var(--default-bg); color: var(--default-text); }
        .entity-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 32px;
            margin-bottom: 24px;
        }
        .meta-label {
            margin: 0 0 6px;
            color: var(--muted);
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }
        .entity-title {
            margin: 0 0 8px;
            font-size: 17px;
            font-weight: 800;
            line-height: 1.3;
        }
        .entity-body {
            color: var(--muted);
            font-size: 13px;
        }
        .entity-body p {
            margin: 0 0 4px;
        }
        .summary-bar {
            display: flex;
            justify-content: space-between;
            gap: 24px;
            background: var(--paper-muted);
            border-radius: 12px;
            padding: 18px 20px;
            margin-bottom: 28px;
        }
        .summary-project {
            flex: 1 1 auto;
        }
        .summary-project .value,
        .summary-date .value {
            font-size: 13px;
            font-weight: 600;
        }
        .summary-dates {
            display: flex;
            gap: 28px;
            text-align: right;
            flex: 0 0 auto;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 28px;
        }
        .items-table th,
        .items-table td {
            border-bottom: 1px solid var(--border-soft);
            padding: 12px 10px;
            vertical-align: top;
            font-size: 13px;
        }
        .items-table th {
            color: var(--muted);
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }
        .items-table th.num,
        .items-table td.num {
            width: 44px;
        }
        .items-table th.align-right,
        .items-table td.align-right {
            text-align: right;
            white-space: nowrap;
        }
        .item-name {
            font-weight: 700;
            color: var(--text);
        }
        .item-description {
            color: var(--muted);
            margin-top: 4px;
            font-size: 12px;
        }
        .totals-section {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 32px;
            margin-bottom: 24px;
        }
        .payment-panel {
            width: 48%;
        }
        .payment-title {
            margin: 0 0 10px;
            font-size: 15px;
            font-weight: 800;
        }
        .payment-panel p {
            margin: 0 0 6px;
            color: var(--muted);
            font-size: 13px;
        }
        .payment-panel strong {
            color: var(--text);
        }
        .totals-card {
            width: 34%;
            margin-left: auto;
        }
        .totals-row {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            padding: 8px 0;
            border-bottom: 1px solid var(--border-soft);
            font-size: 13px;
        }
        .totals-row span:first-child {
            color: var(--muted);
        }
        .totals-row strong {
            color: var(--text);
        }
        .totals-row.total {
            font-size: 18px;
            font-weight: 800;
            border-bottom: 0;
            padding-top: 12px;
            padding-bottom: 10px;
        }
        .totals-row.total strong {
            color: var(--primary);
        }
        .totals-row.balance {
            margin-top: 4px;
            border-bottom: 0;
            background: var(--paper-soft);
            border-radius: 8px;
            padding: 10px 12px;
            font-weight: 700;
        }
        .invoice-footer {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 220px;
            gap: 24px;
            border-top: 1px solid var(--border);
            padding-top: 20px;
            margin-top: auto;
        }
        .footer-note {
            font-size: 12px;
            color: var(--muted);
        }
        .footer-note p {
            margin: 0;
        }
        .signature {
            text-align: right;
        }
        .signature-line {
            width: 160px;
            height: 68px;
            border-bottom: 2px solid var(--border);
            margin-left: auto;
            margin-bottom: 8px;
        }
        .signature-image {
            max-width: 160px;
            max-height: 68px;
            display: block;
            margin-left: auto;
            margin-bottom: 8px;
            object-fit: contain;
        }
        .signature strong {
            display: block;
            font-size: 13px;
        }
        .signature span {
            color: var(--muted);
            font-size: 11px;
        }
        .company-logo img {
            max-width: 180px;
            max-height: 70px;
            display: block;
            margin-bottom: 10px;
        }
        .company-logo + .entity-title {
            margin-top: 0;
        }
        @media print {
            @page { size: A4; margin: 0; }
            body {
                background: #fff;
                padding: 0;
            }
            .invoice-shell {
                max-width: 210mm;
                min-height: 297mm;
                box-shadow: none;
                border-radius: 0;
            }
        }
        @media (max-width: 900px) {
            .invoice-shell { padding: 24px; }
            .entity-grid,
            .invoice-footer {
                grid-template-columns: 1fr;
            }
            .summary-bar,
            .totals-section,
            .invoice-top {
                flex-direction: column;
            }
            .summary-dates,
            .invoice-heading,
            .signature {
                text-align: left;
            }
            .payment-panel,
            .totals-card {
                width: 100%;
            }
            .signature-line {
                margin-left: 0;
            }
            .signature-image {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <div class="invoice-shell">
        <div class="invoice-top">
            <div class="invoice-qr">
                <?php if ($qrCodeDataUri !== '') { ?>
                    <img src="<?= e($qrCodeDataUri); ?>" alt="Invoice QR">
                <?php } else { ?>
                    <div class="invoice-qr-fallback">#</div>
                <?php } ?>
            </div>
            <div class="invoice-heading">
                <h1><?= e(mb_strtoupper(_l('invoice_pdf_heading'))); ?></h1>
                <p class="invoice-number"># <?= e($invoice_number); ?></p>
                <span class="status-badge <?= e($statusBadgeClass); ?>"><?= e($statusText); ?></span>
            </div>
        </div>

        <div class="entity-grid">
            <div>
                <p class="meta-label">TỪ</p>
                <?php if ($companyLogo !== '') { ?>
                    <div class="company-logo"><?= $companyLogo; ?></div>
                <?php } ?>
                <div class="entity-body">
                    <?= format_organization_info(); ?>
                </div>
            </div>
            <div>
                <p class="meta-label">ĐẾN</p>
                <h2 class="entity-title"><?= e($clientDisplayName); ?></h2>
                <div class="entity-body">
                    <?= format_customer_info($invoice, 'invoice', 'billing'); ?>
                </div>
            </div>
        </div>

        <div class="summary-bar">
            <div class="summary-project">
                <p class="meta-label"><?= e(_l('project')); ?></p>
                <div class="value">
                    <?= e($invoice->project_id ? get_project_name_by_id($invoice->project_id) : $invoice_number); ?>
                </div>
            </div>
            <div class="summary-dates">
                <div class="summary-date">
                    <p class="meta-label"><?= e(_l('invoice_data_date')); ?></p>
                    <div class="value"><?= e(_d($invoice->date)); ?></div>
                </div>
                <div class="summary-date">
                    <p class="meta-label"><?= e(_l('invoice_data_duedate')); ?></p>
                    <div class="value"><?= e($invoice->duedate ? _d($invoice->duedate) : '--'); ?></div>
                </div>
            </div>
        </div>

        <table class="items-table">
            <thead>
                <tr>
                    <th class="num">#</th>
                    <th><?= e(_l('invoice_table_item_heading')); ?></th>
                    <th class="align-right"><?= e(_l('invoice_table_quantity_heading')); ?></th>
                    <th class="align-right"><?= e(_l('invoice_table_rate_heading')); ?></th>
                    <th class="align-right"><?= e(_l('invoice_table_tax_heading')); ?></th>
                    <th class="align-right"><?= e(_l('invoice_table_amount_heading')); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $index => $item) {
                    $itemTaxes = get_invoice_item_taxes($item['id']);
                    $itemTaxLabels = [];
                    $itemTaxTotalRate = 0;
                    foreach ($itemTaxes as $tax) {
                        $itemTaxLabels[] = app_format_number($tax['taxrate']) . '%';
                        $itemTaxTotalRate += (float) $tax['taxrate'];
                    }
                    $itemSubtotal = (float) $item['qty'] * (float) $item['rate'];
                    $itemTotal = $itemSubtotal + (($itemSubtotal * $itemTaxTotalRate) / 100);
                    ?>
                    <tr>
                        <td class="num"><?= e($index + 1); ?></td>
                        <td>
                            <div class="item-name"><?= e($item['description']); ?></div>
                            <?php if (!empty($item['long_description'])) { ?>
                                <div class="item-description"><?= process_text_content_for_display($item['long_description']); ?></div>
                            <?php } ?>
                        </td>
                        <td class="align-right"><?= e(app_format_number($item['qty'], true)); ?><?= $item['unit'] ? ' ' . e($item['unit']) : ''; ?></td>
                        <td class="align-right"><?= e(app_format_money($item['rate'], $invoice->currency_name)); ?></td>
                        <td class="align-right"><?= e($itemTaxLabels ? implode(', ', $itemTaxLabels) : '0%'); ?></td>
                        <td class="align-right"><strong><?= e(app_format_money($itemTotal, $invoice->currency_name)); ?></strong></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>

        <div class="totals-section">
            <div class="payment-panel">
                <?php if ($paymentModePrimary) { ?>
                    <h3 class="payment-title"><?= e($paymentModePrimary->name); ?></h3>
                    <?php foreach ($cleanPaymentDescription($paymentModePrimary->description ?? '') as $line) { ?>
                        <p><?= e($line); ?></p>
                    <?php } ?>
                <?php } else { ?>
                    <h3 class="payment-title"><?= e(_l('invoice_html_offline_payment')); ?></h3>
                    <p><strong><?= e(_l('view_invoice_pdf_link_pay')); ?>:</strong> <a href="<?= e($invoiceUrl); ?>"><?= e($invoiceUrl); ?></a></p>
                <?php } ?>
            </div>

            <div class="totals-card">
                <div class="totals-row">
                    <span><?= e(_l('invoice_subtotal')); ?></span>
                    <strong><?= e(app_format_money($invoice->subtotal, $invoice->currency_name)); ?></strong>
                </div>
                <?php if (is_sale_discount_applied($invoice)) { ?>
                    <div class="totals-row">
                        <span><?= e(_l('invoice_discount')); ?></span>
                        <strong>-<?= e(app_format_money($invoice->discount_total, $invoice->currency_name)); ?></strong>
                    </div>
                <?php } ?>
                <?php foreach ($taxSummary as $tax) { ?>
                    <div class="totals-row">
                        <span><?= e($tax['taxname'] . ' (' . app_format_number($tax['taxrate']) . '%)'); ?></span>
                        <strong><?= e(app_format_money($tax['total_tax'], $invoice->currency_name)); ?></strong>
                    </div>
                <?php } ?>
                <div class="totals-row total">
                    <span><?= e(_l('invoice_total')); ?></span>
                    <strong><?= e(app_format_money($invoice->total, $invoice->currency_name)); ?></strong>
                </div>
                <?php if ($invoice->status != Invoices_model::STATUS_CANCELLED) { ?>
                    <div class="totals-row balance">
                        <span><?= e(_l('invoice_amount_due')); ?></span>
                        <strong><?= e(app_format_money($invoice->total_left_to_pay, $invoice->currency_name)); ?></strong>
                    </div>
                <?php } ?>
            </div>
        </div>

        <div class="invoice-footer">
            <div class="footer-note">
                <p class="meta-label"><?= e(_l('invoice_note')); ?></p>
                <div><?= !empty($invoice->clientnote) ? process_text_content_for_display($invoice->clientnote) : e(_l('terms_and_conditions')); ?></div>
            </div>
            <div class="signature">
                <?php if ($signatureImageUrl !== '') { ?>
                    <img class="signature-image" src="<?= e($signatureImageUrl); ?>" alt="<?= e(_l('signature_image')); ?>">
                <?php } else { ?>
                    <div class="signature-line"></div>
                <?php } ?>
                <strong>Chữ ký được ủy quyền</strong>
                <span><?= e(get_option('companyname')); ?></span>
            </div>
        </div>
    </div>
    <?php if (!empty($auto_print)) { ?>
    <script>
        window.addEventListener('load', function () {
            window.print();
        });
    </script>
    <?php } ?>
</body>
</html>
