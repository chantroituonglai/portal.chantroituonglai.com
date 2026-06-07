<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$estimateUrl = site_url('estimate/' . $estimate->id . '/' . $estimate->hash);
$companyLogo = pdf_logo_url();
$statusText = format_estimate_status($estimate->status, '', false);
$statusClass = estimate_status_color_class($estimate->status);
$statusClassMap = [
    'success' => 'is-success',
    'warning' => 'is-warning',
    'danger'  => 'is-danger',
    'info'    => 'is-info',
    'default' => 'is-default',
    'muted'   => 'is-default',
];
$statusBadgeClass = $statusClassMap[$statusClass] ?? 'is-default';
$items = is_array($estimate->items ?? null) ? $estimate->items : [];
$taxSummary = get_items_table_data($estimate, 'estimate', 'html', true)->taxes();
$qrCodeDataUri = '';
$clientDisplayName = trim((string) get_company_name($estimate->clientid, true));
$signatureImageFile = (string) get_option('signature_image');
$signatureImagePath = get_upload_path_by_type('company') . $signatureImageFile;
$signatureImageUrl = '';

if (get_option('show_pdf_signature_estimate') == 1
    && $signatureImageFile !== ''
    && is_file($signatureImagePath)) {
    $signatureImageUrl = base_url('uploads/company/' . $signatureImageFile);
}

if ($clientDisplayName === '') {
    $clientDisplayName = trim(($estimate->client->firstname ?? '') . ' ' . ($estimate->client->lastname ?? ''));
}

if (class_exists('\BaconQrCode\Writer') && class_exists('\BaconQrCode\Renderer\ImageRenderer')) {
    try {
        $renderer = new \BaconQrCode\Renderer\ImageRenderer(
            new \BaconQrCode\Renderer\RendererStyle\RendererStyle(160),
            new \BaconQrCode\Renderer\Image\SvgImageBackEnd()
        );
        $writer = new \BaconQrCode\Writer($renderer);
        $svg = $writer->writeString($estimateUrl);
        $qrCodeDataUri = 'data:image/svg+xml;base64,' . base64_encode($svg);
    } catch (\Throwable $e) {
        $qrCodeDataUri = '';
    }
}
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(_l('estimate_pdf_heading') . ' ' . $estimate_number); ?></title>
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
            --danger-bg: #ffd9d6;
            --danger-text: #8c211f;
            --info-bg: #dbeafe;
            --info-text: #1d4f91;
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
        .estimate-shell {
            width: 100%;
            max-width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            background: var(--paper);
            border-radius: 8px;
            box-shadow: 0 18px 60px rgba(15, 23, 42, 0.10);
            padding: 30px 32px 32px;
        }
        .estimate-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 24px;
            margin-bottom: 26px;
        }
        .estimate-qr {
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
        .estimate-qr img {
            width: 46px;
            height: 46px;
            display: block;
        }
        .estimate-qr-fallback {
            color: var(--primary);
            font-size: 28px;
            font-weight: 700;
        }
        .estimate-heading {
            text-align: right;
            margin-left: auto;
        }
        .estimate-heading h1 {
            margin: 0 0 2px;
            font-size: 34px;
            line-height: 1;
        }
        .estimate-number {
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
        .status-badge.is-info { background: var(--info-bg); color: var(--info-text); }
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
            margin-bottom: 26px;
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
            margin-bottom: 26px;
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
            justify-content: flex-end;
            align-items: flex-start;
            gap: 32px;
            margin-bottom: 24px;
        }
        .totals-card {
            width: 38%;
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
            white-space: nowrap;
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
        .content-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 220px;
            gap: 24px;
            border-top: 1px solid var(--border);
            padding-top: 20px;
        }
        .terms-block {
            font-size: 12px;
            color: var(--muted);
        }
        .terms-block + .terms-block {
            margin-top: 18px;
        }
        .terms-block p {
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
            width: auto !important;
            height: auto !important;
            max-width: 180px;
            max-height: 70px;
            display: block;
            object-fit: contain;
            margin-bottom: 10px;
        }
        @media print {
            @page { size: A4; margin: 5mm; }
            body {
                background: #fff;
                padding: 0;
                font-size: 10px;
                line-height: 1.25;
            }
            .estimate-shell {
                width: auto;
                max-width: none;
                min-height: auto;
                margin: 0;
                padding: 0;
                box-shadow: none;
                border-radius: 0;
            }
            .estimate-top {
                gap: 12px;
                margin-bottom: 10px;
            }
            .estimate-qr {
                width: 42px;
                height: 42px;
                border-radius: 6px;
                flex-basis: 42px;
            }
            .estimate-qr img {
                width: 32px;
                height: 32px;
            }
            .estimate-heading h1 {
                font-size: 24px;
            }
            .estimate-number {
                margin-bottom: 6px;
                font-size: 9px;
            }
            .status-badge {
                border-radius: 4px;
                padding: 2px 7px;
                font-size: 8px;
            }
            .estimate-top,
            .summary-bar,
            .totals-section {
                flex-direction: row !important;
            }
            .entity-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
                gap: 20px;
                margin-bottom: 12px;
            }
            .estimate-heading,
            .summary-dates,
            .signature {
                text-align: right !important;
            }
            .meta-label {
                margin-bottom: 3px;
                font-size: 8px;
                letter-spacing: 0.08em;
            }
            .entity-title {
                margin-bottom: 4px;
                font-size: 12px;
            }
            .entity-body {
                font-size: 9.5px;
                line-height: 1.25;
            }
            .company-logo img {
                width: auto !important;
                height: auto !important;
                max-width: 100px;
                max-height: 42px;
                object-fit: contain;
                margin-bottom: 5px;
            }
            .summary-bar {
                border-radius: 7px;
                gap: 16px;
                padding: 8px 10px;
                margin-bottom: 12px;
            }
            .summary-dates {
                gap: 16px;
            }
            .summary-project .value,
            .summary-date .value {
                font-size: 9.5px;
            }
            .items-table {
                margin-bottom: 12px;
            }
            .items-table th,
            .items-table td {
                padding: 5px 7px;
                font-size: 9.2px;
                line-height: 1.25;
            }
            .items-table th {
                font-size: 8px;
                letter-spacing: 0.05em;
            }
            .items-table th.num,
            .items-table td.num {
                width: 28px;
            }
            .item-description {
                margin-top: 2px;
                font-size: 8.8px;
                line-height: 1.25;
            }
            .totals-section {
                gap: 16px;
                margin-bottom: 10px;
            }
            .totals-card {
                width: 34% !important;
            }
            .totals-row {
                gap: 10px;
                padding: 4px 0;
                font-size: 9.5px;
            }
            .totals-row.total {
                font-size: 13px;
                padding-top: 6px;
                padding-bottom: 5px;
            }
            .content-grid {
                grid-template-columns: minmax(0, 1fr) 205px !important;
                gap: 18px;
                padding-top: 10px;
            }
            .terms-block {
                font-size: 8.8px;
                line-height: 1.25;
            }
            .terms-block + .terms-block {
                margin-top: 8px;
            }
            .signature-line {
                width: 160px;
                height: 58px;
                margin-right: auto;
                margin-left: auto;
                margin-bottom: 7px;
            }
            .signature-image {
                max-width: 160px;
                max-height: 58px;
                margin-right: auto;
                margin-left: auto;
                margin-bottom: 7px;
            }
            .signature strong {
                font-size: 11px;
                line-height: 1.25;
            }
            .signature span {
                font-size: 9px;
            }
            .items-table,
            .totals-section,
            .summary-bar,
            .content-grid {
                break-inside: avoid;
                page-break-inside: avoid;
            }
            .signature {
                break-inside: avoid;
                page-break-inside: avoid;
            }
        }
        @media screen and (max-width: 900px) {
            .estimate-shell { padding: 24px; }
            .entity-grid,
            .content-grid {
                grid-template-columns: 1fr;
            }
            .summary-bar,
            .totals-section,
            .estimate-top {
                flex-direction: column;
            }
            .summary-dates,
            .estimate-heading,
            .signature {
                text-align: left;
            }
            .totals-card {
                width: 100%;
            }
            .signature-line,
            .signature-image {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <div class="estimate-shell">
        <div class="estimate-top">
            <div class="estimate-qr">
                <?php if ($qrCodeDataUri !== '') { ?>
                    <img src="<?= e($qrCodeDataUri); ?>" alt="Estimate QR">
                <?php } else { ?>
                    <div class="estimate-qr-fallback">#</div>
                <?php } ?>
            </div>
            <div class="estimate-heading">
                <h1><?= e(mb_strtoupper(_l('estimate_pdf_heading'))); ?></h1>
                <p class="estimate-number"># <?= e($estimate_number); ?></p>
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
                    <?= format_customer_info($estimate, 'estimate', 'billing'); ?>
                </div>
            </div>
        </div>

        <div class="summary-bar">
            <div class="summary-project">
                <p class="meta-label"><?= e(_l('project')); ?></p>
                <div class="value">
                    <?= e($estimate->project_id ? get_project_name_by_id($estimate->project_id) : $estimate_number); ?>
                </div>
            </div>
            <div class="summary-dates">
                <div class="summary-date">
                    <p class="meta-label"><?= e(_l('estimate_data_date')); ?></p>
                    <div class="value"><?= e(_d($estimate->date)); ?></div>
                </div>
                <div class="summary-date">
                    <p class="meta-label"><?= e(_l('estimate_data_expiry_date')); ?></p>
                    <div class="value"><?= e($estimate->expirydate ? _d($estimate->expirydate) : '--'); ?></div>
                </div>
                <?php if ($estimate->sale_agent != 0 && get_option('show_sale_agent_on_estimates') == 1) { ?>
                    <div class="summary-date">
                        <p class="meta-label"><?= e(_l('sale_agent_string')); ?></p>
                        <div class="value"><?= e(get_staff_full_name($estimate->sale_agent)); ?></div>
                    </div>
                <?php } ?>
            </div>
        </div>

        <table class="items-table">
            <thead>
                <tr>
                    <th class="num">#</th>
                    <th><?= e(_l('estimate_table_item_heading')); ?></th>
                    <th class="align-right"><?= e(_l('estimate_table_quantity_heading')); ?></th>
                    <th class="align-right"><?= e(_l('estimate_table_rate_heading')); ?></th>
                    <th class="align-right"><?= e(_l('estimate_table_tax_heading')); ?></th>
                    <th class="align-right"><?= e(_l('estimate_table_amount_heading')); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $index => $item) {
                    $itemTaxes = get_estimate_item_taxes($item['id']);
                    $itemTaxLabels = [];
                    foreach ($itemTaxes as $tax) {
                        $itemTaxLabels[] = app_format_number($tax['taxrate']) . '%';
                    }
                    $itemSubtotal = (float) $item['qty'] * (float) $item['rate'];
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
                        <td class="align-right"><?= e(app_format_money($item['rate'], $estimate->currency_name)); ?></td>
                        <td class="align-right"><?= e($itemTaxLabels ? implode(', ', $itemTaxLabels) : '0%'); ?></td>
                        <td class="align-right"><strong><?= e(app_format_money($itemSubtotal, $estimate->currency_name)); ?></strong></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>

        <div class="totals-section">
            <div class="totals-card">
                <div class="totals-row">
                    <span><?= e(_l('estimate_subtotal')); ?></span>
                    <strong><?= e(app_format_money($estimate->subtotal, $estimate->currency_name)); ?></strong>
                </div>
                <?php if (is_sale_discount_applied($estimate)) { ?>
                    <div class="totals-row">
                        <span><?= e(_l('estimate_discount')); ?></span>
                        <strong>-<?= e(app_format_money($estimate->discount_total, $estimate->currency_name)); ?></strong>
                    </div>
                <?php } ?>
                <?php foreach ($taxSummary as $tax) { ?>
                    <div class="totals-row">
                        <span><?= e($tax['taxname'] . ' (' . app_format_number($tax['taxrate']) . '%)'); ?></span>
                        <strong><?= e(app_format_money($tax['total_tax'], $estimate->currency_name)); ?></strong>
                    </div>
                <?php } ?>
                <?php if ((int) $estimate->adjustment != 0) { ?>
                    <div class="totals-row">
                        <span><?= e(_l('estimate_adjustment')); ?></span>
                        <strong><?= e(app_format_money($estimate->adjustment, $estimate->currency_name)); ?></strong>
                    </div>
                <?php } ?>
                <div class="totals-row total">
                    <span><?= e(_l('estimate_total')); ?></span>
                    <strong><?= e(app_format_money($estimate->total, $estimate->currency_name)); ?></strong>
                </div>
            </div>
        </div>

        <div class="content-grid">
            <div>
                <?php if (!empty($estimate->clientnote)) { ?>
                    <div class="terms-block">
                        <p class="meta-label"><?= e(_l('estimate_note')); ?></p>
                        <div><?= process_text_content_for_display($estimate->clientnote); ?></div>
                    </div>
                <?php } ?>
                <?php if (!empty($estimate->terms)) { ?>
                    <div class="terms-block">
                        <p class="meta-label"><?= e(_l('terms_and_conditions')); ?></p>
                        <div><?= process_text_content_for_display($estimate->terms); ?></div>
                    </div>
                <?php } ?>
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
