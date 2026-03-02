<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">

        <div class="row">
            <div class="col-md-12">
                <h4 class="tw-mt-0 tw-font-semibold tw-text-lg tw-text-neutral-700">
                    <?php echo _l('importsync'); ?> - <?php echo _l('importsync_column_mapping'); ?>
                </h4>
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title">Create Mapping For Base CSV</h3>
                    </div>
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-4">
                                <label class="form-label">Data type</label>
                                <select id="export_csv_type" class="form-control">
                                    <option value="">Select</option>
                                    <?php
                                    foreach (importsync_supported_csvs() as $item) {
                                        ?>
                                        <option value="<?php echo $item['value']; ?>"><?php echo $item['name']; ?></option>
                                        <?php
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">Filters</label>
                                <div id="export_filters_container"></div>
                                <button type="button" id="add_export_filter" class="btn btn-default btn-sm mtop10">Add filter</button>
                            </div>
                        </div>
                        <div class="row mtop15">
                            <div class="col-md-12 text-right">
                                <button type="button" id="export_base_csv" class="btn btn-success">
                                    <i class="fa fa-download"></i> Export CSV (UTF-8)
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title"><?php echo _l('importsync_upload_csv_file'); ?></h3>
                    </div>
                    <div class="panel-body">
                        <form id="uploadForm" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-12">
                                    <label for="ImportCsv"
                                           class="form-label"><?php echo _l('importsync_select_csv_file_to_map'); ?>
                                        :</label>
                                    <div class=" mb-3">
                                        <input type="file"
                                               accept=".csv,.xlsx"
                                               name="csvFile" id="csvFile" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-12 mtop20">
                                    <label for="radioOption"
                                           class="form-label"><?php echo _l('importsync_check_base_csv'); ?>
                                        :</label><br>
                                    <?php
                                    foreach (importsync_supported_csvs() as $item) {
                                        ?>
                                        <label><input type="radio" name="csv_type"
                                                      value="<?php echo $item['value']; ?>"> <?php echo $item['name']; ?>
                                        </label>
                                        <?php
                                    }
                                    ?>
                                </div>
                            </div>
                            <br>
                            <div class="row text-right">
                                <div class="col-md-12">
                                    <button type="submit" id="uploadButton"
                                            class="btn btn-primary transcript-btn"><?php echo _l('importsync_start_mapping'); ?></button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div id="mappingResult" class="panel panel-default hide">
                    <div class="panel-heading">
                        <h3 class="panel-title">Result</h3>
                    </div>
                    <div class="panel-body">
                        <div id="mappingResultBody"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="mappingModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title"><?php echo _l('importsync_column_mapping'); ?></h4>
                </div>
                <div class="modal-body">
                <div class="progress mtop10">
                    <div id="mappingProgressBar" class="progress-bar progress-bar-success" role="progressbar" style="width:0%">0%</div>
                </div>
                <p class="text-muted mtop5" id="mappingProgressText">0/0 mapped</p>
                <div id="mappingTypeFixedContainer" class="form-group hide">
                    <label for="mapping_type_fixed">Mapping Type (fixed)</label>
                    <select id="mapping_type_fixed" class="form-control">
                        <option value="">Select mapping type</option>
                        <option value="fast_barco">Fast Barco</option>
                        <option value="aeon_sku">AEON SKU</option>
                        <option value="emart">Emart</option>
                        <option value="emart_sku">Emart SKU</option>
                        <option value="woo">WooCommerce</option>
                        <option value="shopify">Shopify</option>
                        <option value="magento">Magento</option>
                        <option value="amazon">Amazon</option>
                        <option value="ebay">eBay</option>
                        <option value="other">Other</option>
                    </select>
                    <p class="text-muted small mtop5">Use fixed mapping type if your CSV doesn't have a column for it.</p>
                </div>
                <?php
                foreach (supportedCsvImports() as $supported_csv) {
                    $supportedImportCsvData = renderCsvTypeColumns($supported_csv);
                    ?>
                    <div class="col-md-12 <?php echo $supported_csv; ?>_csv csv-pane hide">
                        <div class="panel_s">
                            <div class="panel-body panel-table-full">
                                <div class="">
                                    <div class="mapping-container">
                                        <h3><?php echo _l($supported_csv); ?> CSV
                                            - <?php echo _l('importsync_column_mapping'); ?></h3>
                                        <div class="alert alert-info">
                                            <?php echo $supportedImportCsvData['import_guidelines']; ?>
                                        </div>
                                        <?php echo $supportedImportCsvData['sample_table_html']; ?>
                                        <table class="table table-hover tw-text-sm mapping-table">
                                            <thead>
                                            <tr>
                                                <th><?php echo _l('importsync_base_csv_columns'); ?></th>
                                                <th></th>
                                                <th><?php echo _l('importsync_uploaded_csv_columns'); ?></th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            <?php
                                            foreach ($supportedImportCsvData['import_fields'] as $field) {
                                                ?>
                                                <tr>
                                                    <td><?php echo $field; ?></td>
                                                    <td>-></td>
                                                    <td>
                                                        <select class="form-control csv-dropdown"
                                                                data-main-column="<?php echo $field; ?>"></select>
                                                    </td>
                                                </tr>
                                                <?php
                                            }
                                            ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="btn-bottom-toolbar text-right">
                                    <button class="btn-tr btn btn-default mright5 text-right syncButton">
                                        <?php echo _l('importsync_download_synced_csv'); ?></button>
                                    <button type="button"
                                            class="btn-tr btn btn-primary syncButtonDownload"><?php echo _l('importsync_start_importing'); ?></button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php
                }
                ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
                <button type="button" class="btn btn-info syncButton"><?php echo _l('importsync_download_synced_csv'); ?></button>
                <button type="button" class="btn btn-primary syncButtonDownload"><?php echo _l('importsync_start_importing'); ?></button>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
<script>
    $(document).ready(function () {
        "use strict"; // Enable strict mode

        var columnMappings = {};
        var selectedCSVType = null;
        var exportColumns = [];

        $(document).on("change", ".csv-dropdown", function () {
            var mainColumn = $(this).data("main-column");
            var csvColumn = $(this).val();

            columnMappings[mainColumn] = csvColumn;
            updateProgress();
        });

        $("#mapping_type_fixed").change(function () {
            updateProgress();
        });

        $(".syncButton").click(function () {
            var formData = new FormData();

            var fileInput = document.getElementById('csvFile');
            var selectedCSVTypeLocal = $("input[name='csv_type']:checked").val();

            if (fileInput.files.length > 0) {
                formData.append('csvFile', fileInput.files[0]);
            }

            formData.append('mappings', JSON.stringify(columnMappings));
            formData.append('csv_type', selectedCSVTypeLocal);
            formData.append('mapping_type_fixed', $('#mapping_type_fixed').val());

            if (typeof csrfData !== "undefined") {
                formData.append(csrfData["token_name"], csrfData["hash"]);
            }

            $.ajax({
                url: '<?php echo admin_url('importsync/map_csv') ?>',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (data) {
                    var parsedData = JSON.parse(data);

                    if (selectedCSVTypeLocal === 'external_products') {
                        renderMappingResult(parsedData);
                        return;
                    }

                    var mappedCsvUrl = parsedData.mapped_csv_url;
                    if (mappedCsvUrl) {
                        window.open(mappedCsvUrl, '_blank');
                    }
                }
            });
        });

        $(".syncButtonDownload").click(function () {
            var formData = new FormData();

            var fileInput = document.getElementById('csvFile');
            var selectedCSVTypeLocal = $("input[name='csv_type']:checked").val();

            if (fileInput.files.length > 0) {
                formData.append('csvFile', fileInput.files[0]);
            }

            formData.append('mappings', JSON.stringify(columnMappings));
            formData.append('csv_type', selectedCSVTypeLocal);
            formData.append('mapping_type_fixed', $('#mapping_type_fixed').val());

            if (typeof csrfData !== "undefined") {
                formData.append(csrfData["token_name"], csrfData["hash"]);
            }

            $.ajax({
                url: '<?php echo admin_url('importsync/map_csv') ?>',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (data) {
                    var parsedData = JSON.parse(data);

                    if (selectedCSVTypeLocal === 'external_products') {
                        renderMappingResult(parsedData);
                        return;
                    }

                    var mappedCsvUrl = parsedData.mapped_csv_url;
                    var redirectUrl = parsedData.redirect_url;

                    if (mappedCsvUrl) {
                        window.open(mappedCsvUrl, '_blank');
                    }
                    if (redirectUrl) {
                        window.location.replace(redirectUrl);
                    }
                }
            });
        });

        $("#uploadForm").submit(function (e) {
            e.preventDefault();

            var formData = new FormData(this);
            selectedCSVType = $("input[name='csv_type']:checked").val();

            if (typeof csrfData !== "undefined") {
                formData.append(csrfData["token_name"], csrfData["hash"]);
            }

            $.ajax({
                url: '<?php echo admin_url('importsync/get_csv_columns') ?>',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (data) {
                    var csvColumns = JSON.parse(data);
                    columnMappings = {};
                    populateCsvDropdowns(csvColumns);

                    $('.csv-pane').addClass('hide');
                    $('.' + selectedCSVType + '_csv').removeClass('hide');
                    toggleMappingTypeFixed(selectedCSVType);
                    updateProgress();
                    $('#mappingModal').modal('show');
                }
            });
        });

        function toggleMappingTypeFixed(type) {
            if (type === 'external_products') {
                $('#mappingTypeFixedContainer').removeClass('hide');
            } else {
                $('#mappingTypeFixedContainer').addClass('hide');
                $('#mapping_type_fixed').val('');
            }
        }

        function updateProgress() {
            if (!selectedCSVType) {
                return;
            }
            var $pane = $('.' + selectedCSVType + '_csv');
            var $dropdowns = $pane.find('.csv-dropdown');
            var total = $dropdowns.length;
            var completed = 0;

            $dropdowns.each(function () {
                if ($(this).val()) {
                    completed++;
                }
            });

            if (selectedCSVType === 'external_products') {
                if (!columnMappings['Mapping Type'] && $('#mapping_type_fixed').val()) {
                    completed++;
                }
            }

            var percent = total ? Math.round((completed / total) * 100) : 0;
            $('#mappingProgressBar').css('width', percent + '%').text(percent + '%');
            $('#mappingProgressText').text(completed + '/' + total + ' mapped');
        }

        function renderMappingResult(data) {
            var resultHtml = '';
            var importedCount = data.imported_count || 0;
            var skippedCount = data.skipped_count || 0;
            var fileDuplicates = data.duplicates_file || [];
            var dbDuplicates = data.duplicates_db || [];

            resultHtml += '<p><strong>Imported:</strong> ' + importedCount + '</p>';
            resultHtml += '<p><strong>Skipped:</strong> ' + skippedCount + '</p>';

            if (fileDuplicates.length > 0) {
                resultHtml += '<h5>Duplicates in CSV</h5><ul>';
                fileDuplicates.forEach(function (item) {
                    resultHtml += '<li>' + item.mapping_id + ' (' + item.mapping_type + ') - ' + item.count + ' rows</li>';
                });
                resultHtml += '</ul>';
            }

            if (dbDuplicates.length > 0) {
                resultHtml += '<h5>Duplicates in Database</h5><ul>';
                dbDuplicates.forEach(function (item) {
                    resultHtml += '<li>' + item.mapping_id + ' (' + item.mapping_type + ') - existing</li>';
                });
                resultHtml += '</ul>';
            }

            if (fileDuplicates.length === 0 && dbDuplicates.length === 0) {
                resultHtml += '<p>No duplicates found.</p>';
            }

            $('#mappingResultBody').html(resultHtml);
            $('#mappingResult').removeClass('hide');
            $('#mappingModal').modal('hide');
        }

        function buildExportFilterRow() {
            var $row = $('<div class="row mtop5 export-filter-row"></div>');
            var $colSelect = $('<div class="col-md-5"><select class="form-control export-filter-column"></select></div>');
            var $opSelect = $('<div class="col-md-3"><select class="form-control export-filter-operator"></select></div>');
            var $valInput = $('<div class="col-md-3"><input type="text" class="form-control export-filter-value" placeholder="Value"></div>');
            var $remove = $('<div class="col-md-1"><button type="button" class="btn btn-danger btn-sm remove-export-filter">&times;</button></div>');

            var $columnSelect = $colSelect.find('select');
            $columnSelect.append('<option value="">Select column</option>');
            exportColumns.forEach(function (col) {
                $columnSelect.append('<option value="' + col.value + '">' + col.label + '</option>');
            });

            var operators = [
                { value: '=', label: '=' },
                { value: '!=', label: '!=' },
                { value: 'like', label: 'LIKE' },
                { value: '>', label: '>' },
                { value: '>=', label: '>=' },
                { value: '<', label: '<' },
                { value: '<=', label: '<=' }
            ];

            operators.forEach(function (op) {
                $opSelect.find('select').append('<option value="' + op.value + '">' + op.label + '</option>');
            });

            $row.append($colSelect, $opSelect, $valInput, $remove);
            return $row;
        }

        $('#export_csv_type').on('change', function () {
            var type = $(this).val();
            exportColumns = [];
            $('#export_filters_container').empty();
            if (!type) {
                return;
            }

            $.get('<?php echo admin_url('importsync/get_export_filters'); ?>', { csv_type: type }).done(function (response) {
                var data = [];
                try { data = JSON.parse(response); } catch (e) { data = []; }
                exportColumns = data;
                $('#export_filters_container').append(buildExportFilterRow());
            });
        });

        $('#add_export_filter').on('click', function () {
            if (exportColumns.length === 0) {
                return;
            }
            $('#export_filters_container').append(buildExportFilterRow());
        });

        $(document).on('click', '.remove-export-filter', function () {
            $(this).closest('.export-filter-row').remove();
        });

        $('#export_base_csv').on('click', function () {
            var type = $('#export_csv_type').val();
            if (!type) {
                alert_float('warning', 'Select data type');
                return;
            }

            var filters = [];
            $('#export_filters_container .export-filter-row').each(function () {
                var column = $(this).find('.export-filter-column').val();
                var operator = $(this).find('.export-filter-operator').val();
                var value = $(this).find('.export-filter-value').val();
                if (column && operator && value !== '') {
                    filters.push({ column: column, operator: operator, value: value });
                }
            });

            var $form = $('<form>', {
                method: 'POST',
                action: '<?php echo admin_url('importsync/export_base_csv'); ?>'
            });

            $form.append($('<input>', { type: 'hidden', name: 'csv_type', value: type }));
            $form.append($('<input>', { type: 'hidden', name: 'filters', value: JSON.stringify(filters) }));
            if (typeof csrfData !== "undefined") {
                $form.append($('<input>', { type: 'hidden', name: csrfData["token_name"], value: csrfData["hash"] }));
            }

            $('body').append($form);
            $form.trigger('submit');
            $form.remove();
        });
    });

    function populateCsvDropdowns(csvColumns) {
        "use strict"; // Enable strict mode
        $(".csv-dropdown").empty().append("<option value=''>Select</option>");
        $.each(csvColumns, function (index, csvColumn) {
            $(".csv-dropdown").append("<option value='" + csvColumn + "'>" + csvColumn + "</option>");
        });
    }

</script>
</body>

</html>
