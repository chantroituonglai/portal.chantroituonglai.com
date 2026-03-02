<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Importsync extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('importsync_model');
        hooks()->do_action('importsync_init');
    }

    public function index()
    {
        show_404();
    }

    public function manage_mappings()
    {
        if (!has_permission('importsync', '', 'view')) {
            access_denied('importsync');
        }

        if ($this->input->is_ajax_request()) {
            $this->app->get_table_data(module_views_path('importsync', 'tables/mappings'));
        }

        $data['title'] = _l('importsync') . ' - ' . _l('importsync_sync_csv');
        $this->load->view('manage', $data);
    }

    public function csv_mappings()
    {
        if (!has_permission('importsync_sync_csv', '', 'create')) {
            access_denied('importsync');
        }

        $data['title'] = _l('importsync') . ' - ' . _l('importsync_create_mapping');
        $this->load->view('sync_csv', $data);
    }

    public function get_csv_columns()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ($_FILES['csvFile']['error'] === UPLOAD_ERR_OK) {
                $csvFilePath = $_FILES['csvFile']['tmp_name'];
                $csvData = $this->read_spreadsheet_rows($csvFilePath, $_FILES['csvFile']['name'] ?? '', $_FILES['csvFile']['type'] ?? '');
                $columns = isset($csvData[0]) ? $csvData[0] : [];

                echo json_encode($columns);
            } else {
                echo json_encode([]);
            }
        }
    }

    public function get_export_filters()
    {
        $csvType = $this->input->get('csv_type');
        if (!$csvType) {
            echo json_encode([]);
            return;
        }

        $columns = $this->get_export_columns($csvType);
        echo json_encode($columns['filters']);
    }

    public function export_base_csv()
    {
        $csvType = $this->input->post('csv_type');
        $filters = $this->input->post('filters');
        $filters = $filters ? json_decode($filters, true) : [];

        $columns = $this->get_export_columns($csvType);
        if (!$columns) {
            show_error('Invalid CSV type');
        }

        $exportConfig = $this->get_export_query_config($csvType, $columns['fields']);

        $this->db->select($exportConfig['select'], false);
        $this->db->from($exportConfig['from']);

        if (!empty($exportConfig['joins'])) {
            foreach ($exportConfig['joins'] as $join) {
                $this->db->join($join['table'], $join['on'], $join['type']);
            }
        }

        if (!empty($filters)) {
            foreach ($filters as $filter) {
                if (empty($filter['column']) || !isset($exportConfig['filter_map'][$filter['column']])) {
                    continue;
                }
                $columnExpr = $exportConfig['filter_map'][$filter['column']];
                $operator = strtoupper(trim($filter['operator']));
                $value = $filter['value'];
                if ($operator === 'LIKE') {
                    $this->db->like($columnExpr, $value);
                } else {
                    if ($operator === '=') {
                        $this->db->where($columnExpr, $value);
                    } else {
                        $this->db->where($columnExpr . ' ' . $operator, $value);
                    }
                }
            }
        }

        $query = $this->db->get();
        $rows = $query->result_array();

        if ($csvType === 'leads') {
            $this->load->helper('tags');
        }

        $headings = $columns['headings'];
        $dataRows = [];

        foreach ($rows as $row) {
            $line = [];
            foreach ($columns['fields'] as $field) {
                if ($csvType === 'leads' && $field === 'tags') {
                    $leadId = $row['_lead_id'] ?? null;
                    $value = $leadId ? prep_tags_input(get_tags_in($leadId, 'lead')) : '';
                } else {
                    $value = $row[$field] ?? '';
                }
                $line[] = (string)$value;
            }
            $dataRows[] = $line;
        }

        $filename = $csvType . '_export_' . date('Ymd_His') . '.xlsx';
        $this->output_xlsx($filename, $csvType, $headings, $dataRows);
        exit;
    }

    public function map_csv()
    {
        // Define a mapping array for column synchronization
        $mappings = isset($_POST['mappings']) ? $_POST['mappings'] : [];
        $csvType = $this->input->post('csv_type');
        $mappingTypeFixed = $this->input->post('mapping_type_fixed');

        // Define a mapping array for column synchronization
        $columnMapping = [];

        // Update the mapping based on the received data
        $mappings = json_decode($mappings, true);
        if (is_array($mappings)) {
            foreach ($mappings as $mainColumn => $csvColumn) {
                if (!empty($mainColumn) && !empty($csvColumn)) {
                    $columnMapping[$mainColumn] = $csvColumn;
                }
            }
        }

        if ($csvType === 'external_products') {
            if (!isset($_FILES['csvFile']['tmp_name']) || $_FILES['csvFile']['tmp_name'] === '') {
                echo json_encode(['status' => false, 'message' => 'CSV file missing.']);
                die;
            }

            $sourceCsvFilePath = $_FILES['csvFile']['tmp_name'];
            $sourceCsvData = $this->read_spreadsheet_rows($sourceCsvFilePath, $_FILES['csvFile']['name'] ?? '', $_FILES['csvFile']['type'] ?? '');
            $sourceHeader = isset($sourceCsvData[0]) ? $sourceCsvData[0] : [];

            $this->load->model('external_products/external_products_model');

            $rows = [];
            foreach ($sourceCsvData as $index => $sourceRow) {
                if ($index === 0) {
                    continue;
                }

                $sku = '';
                $mappingId = '';
                $mappingType = '';

                if (isset($columnMapping['SKU'])) {
                    $colIndex = array_search($columnMapping['SKU'], $sourceHeader);
                    if ($colIndex !== false && isset($sourceRow[$colIndex])) {
                        $sku = trim($sourceRow[$colIndex]);
                    }
                }

                if (isset($columnMapping['Mapping ID'])) {
                    $colIndex = array_search($columnMapping['Mapping ID'], $sourceHeader);
                    if ($colIndex !== false && isset($sourceRow[$colIndex])) {
                        $mappingId = trim($sourceRow[$colIndex]);
                    }
                }

                if (isset($columnMapping['Mapping Type'])) {
                    $colIndex = array_search($columnMapping['Mapping Type'], $sourceHeader);
                    if ($colIndex !== false && isset($sourceRow[$colIndex])) {
                        $mappingType = trim($sourceRow[$colIndex]);
                    }
                }

                if ($mappingType === '' && !empty($mappingTypeFixed)) {
                    $mappingType = $mappingTypeFixed;
                }

                if ($sku === '' && $mappingId === '' && $mappingType === '') {
                    continue;
                }

                $rows[] = [
                    'sku' => $sku,
                    'mapping_id' => $mappingId,
                    'mapping_type' => $mappingType,
                ];
            }

            $duplicateCounts = [];
            foreach ($rows as $row) {
                if ($row['mapping_id'] === '' || $row['mapping_type'] === '') {
                    continue;
                }
                $key = $row['mapping_id'] . '||' . $row['mapping_type'];
                if (!isset($duplicateCounts[$key])) {
                    $duplicateCounts[$key] = 0;
                }
                $duplicateCounts[$key]++;
            }

            $duplicatesFile = [];
            $duplicateKeys = [];
            foreach ($duplicateCounts as $key => $count) {
                if ($count > 1) {
                    $parts = explode('||', $key);
                    $duplicatesFile[] = [
                        'mapping_id' => $parts[0],
                        'mapping_type' => $parts[1],
                        'count' => $count,
                    ];
                    $duplicateKeys[$key] = true;
                }
            }

            $duplicatesDb = [];
            $duplicatesDbKeys = [];
            foreach ($rows as $row) {
                if ($row['mapping_id'] === '' || $row['mapping_type'] === '') {
                    continue;
                }
                $key = $row['mapping_id'] . '||' . $row['mapping_type'];
                if (isset($duplicatesDbKeys[$key])) {
                    continue;
                }
                $existing = $this->external_products_model->get_external_product_mapping_by_mapping_id($row['mapping_id'], $row['mapping_type']);
                if ($existing) {
                    $duplicatesDb[] = [
                        'mapping_id' => $row['mapping_id'],
                        'mapping_type' => $row['mapping_type'],
                    ];
                    $duplicatesDbKeys[$key] = true;
                }
            }

            $imported = 0;
            $skipped = 0;

            foreach ($rows as $row) {
                if ($row['sku'] === '' || $row['mapping_id'] === '' || $row['mapping_type'] === '') {
                    $skipped++;
                    continue;
                }
                $key = $row['mapping_id'] . '||' . $row['mapping_type'];
                if (isset($duplicateKeys[$key])) {
                    $skipped++;
                    continue;
                }
                if (isset($duplicatesDbKeys[$key])) {
                    $skipped++;
                    continue;
                }

                $result = $this->external_products_model->add_external_product_mapping([
                    'sku' => $row['sku'],
                    'mapping_id' => $row['mapping_id'],
                    'mapping_type' => $row['mapping_type'],
                ]);
                if ($result) {
                    $imported++;
                } else {
                    $skipped++;
                }
            }

            echo json_encode([
                'status' => true,
                'imported_count' => $imported,
                'skipped_count' => $skipped,
                'duplicates_file' => $duplicatesFile,
                'duplicates_db' => $duplicatesDb,
            ]);
            die;
        }

        $sourceCsvFilePath = $_FILES['csvFile']['tmp_name']; // Path to the second CSV file
        $sourceCsvData = $this->read_spreadsheet_rows($sourceCsvFilePath, $_FILES['csvFile']['name'] ?? '', $_FILES['csvFile']['type'] ?? '');

        // Load the target CSV file
        $targetCsvFilePath = FCPATH . 'modules/importsync/uploads/standard_csv/' . $csvType . '_import_file.csv';
        $targetCsvData = array_map('str_getcsv', file($targetCsvFilePath));

        // Create an array to hold the extracted data based on mapping
        $extractedData = [];

        // Extract data from the source CSV based on the mapping
        foreach ($sourceCsvData as $sourceRow) {
            $extractedRow = [];
            foreach ($columnMapping as $targetColumn => $sourceColumn) {
                $sourceColumnIndex = array_search($sourceColumn, $sourceCsvData[0]);
                $extractedRow[$targetColumn] = $sourceRow[$sourceColumnIndex];
            }
            $extractedData[] = $extractedRow;
        }

        unset($extractedData[0]); //Remove unnecessary columns

        // Match extracted data to the target CSV based on index
        foreach ($extractedData as $index => $extractedRow) {
            if (isset($targetCsvData[$index + 1])) {
                foreach ($columnMapping as $targetColumn => $sourceColumn) {
                    if (isset($extractedRow[$targetColumn])) {
                        $targetCsvData[$index + 1][array_search($targetColumn, $targetCsvData[0])] = $extractedRow[$targetColumn];
                    }
                }
            } else {
                // If there's no corresponding row, create a new row with extracted data
                $newRow = array_fill(0, count($targetCsvData[0]), ''); // Create an empty row with the same number of columns
                foreach ($columnMapping as $targetColumn => $sourceColumn) {
                    if (isset($extractedRow[$targetColumn])) {
                        $newRow[array_search($targetColumn, $targetCsvData[0])] = $extractedRow[$targetColumn];
                    }
                }
                $targetCsvData[] = $newRow;
            }
        }

        // Write the updated data back to the target CSV
        $combinedCsvContent = implode("\n", array_map(function ($row) {
            return implode(",", $row);
        }, $targetCsvData));

        $fileName = $csvType.'-Mapped-CSV.csv';
        $csvData = [
            'mapped_by' => get_staff_user_id(),
            'csv_type' => $csvType,
            'csv_filename' => $fileName,
            'created_at' => date('Y-m-d H:i:s')
        ];
        $mappedCsvID = $this->importsync_model->addMappedCsv($csvData);

        $path = FCPATH . 'modules/importsync/uploads/mapped_csv/' . $mappedCsvID . '/';
        _maybe_create_upload_path($path);

        file_put_contents($path . $fileName, $combinedCsvContent);
        $fileUrl = substr(module_dir_url('importsync/uploads/mapped_csv/' . $mappedCsvID . '/' .$fileName), 0, -1);

        $redirectUrl = '';
        switch ($csvType) {
            case 'leads':
                $redirectUrl = admin_url('leads/import');
                break;
            case 'customers':
                $redirectUrl = admin_url('clients/import');
                break;
            case 'expenses':
                $redirectUrl = admin_url('expenses/import');
                break;
            case 'items':
                $redirectUrl = admin_url('invoice_items/import');
                break;
            case 'staff':
                $redirectUrl = admin_url('importsync/import_staff');
                break;
        }

        echo json_encode([
            'status' => true,
            'mapped_csv_url' => $fileUrl,
            'redirect_url' => $redirectUrl
        ]);
        die;
    }

    private function get_export_columns($csvType)
    {
        $csvType = trim((string)$csvType);
        if ($csvType === '') {
            return null;
        }

        if ($csvType === 'external_products') {
            $fields = ['sku', 'mapping_id', 'mapping_type'];
            return [
                'fields' => $fields,
                'headings' => ['SKU', 'Mapping ID', 'Mapping Type'],
                'filters' => [
                    ['value' => 'sku', 'label' => 'SKU'],
                    ['value' => 'mapping_id', 'label' => 'Mapping ID'],
                    ['value' => 'mapping_type', 'label' => 'Mapping Type'],
                ],
            ];
        }

        $importer = $this->get_import_instance($csvType);
        if (!$importer) {
            return null;
        }

        $fields = $importer->getImportableDatabaseFieldsList();
        $headings = [];
        foreach ($fields as $field) {
            $headings[] = $importer->formatFieldNameForHeading($field);
        }

        $filterFields = $fields;
        if ($csvType === 'leads') {
            $filterFields = array_values(array_diff($filterFields, ['tags']));
        }

        $filters = [];
        foreach ($filterFields as $field) {
            $filters[] = [
                'value' => $field,
                'label' => $importer->formatFieldNameForHeading($field),
            ];
        }

        return [
            'fields' => $fields,
            'headings' => $headings,
            'filters' => $filters,
        ];
    }

    private function get_import_instance($csvType)
    {
        if ($csvType === 'customers') {
            $dbFields = $this->db->list_fields(db_prefix() . 'contacts');
            foreach ($dbFields as $key => $contactField) {
                if ($contactField == 'phonenumber') {
                    $dbFields[$key] = 'contact_phonenumber';
                }
            }
            $dbFields = array_merge($dbFields, $this->db->list_fields(db_prefix() . 'clients'));
            $this->load->library('import/import_customers', [], 'customers_import');
            $this->customers_import->setDatabaseFields($dbFields);
            return $this->customers_import;
        }

        if ($csvType === 'leads') {
            $dbFields = $this->db->list_fields(db_prefix() . 'leads');
            array_push($dbFields, 'tags');
            $this->load->library('import/import_leads', [], 'leads_import');
            $this->leads_import->setDatabaseFields($dbFields);
            return $this->leads_import;
        }

        if ($csvType === 'items') {
            $this->load->library('import/import_items', [], 'items_import');
            $this->items_import->setDatabaseFields($this->db->list_fields(db_prefix() . 'items'));
            return $this->items_import;
        }

        if ($csvType === 'expenses') {
            $this->load->library('import/import_expenses', [], 'expenses_import');
            $this->expenses_import->setDatabaseFields($this->db->list_fields(db_prefix() . 'expenses'));
            return $this->expenses_import;
        }

        if ($csvType === 'staff') {
            $this->load->library('importsync/Import_staff');
            $staffImporter = new Import_staff();
            $staffImporter->setDatabaseFields($this->db->list_fields(db_prefix() . 'staff'));
            return $staffImporter;
        }

        return null;
    }

    private function get_export_query_config($csvType, array $fields)
    {
        if ($csvType === 'customers') {
            $contactFields = $this->db->list_fields(db_prefix() . 'contacts');
            $clientFields = $this->db->list_fields(db_prefix() . 'clients');

            $selects = [];
            $filterMap = [];
            foreach ($fields as $field) {
                if ($field === 'contact_phonenumber') {
                    $selects[] = 'c.phonenumber AS contact_phonenumber';
                    $filterMap[$field] = 'c.phonenumber';
                    continue;
                }
                if (in_array($field, $contactFields)) {
                    $selects[] = 'c.' . $field . ' AS ' . $field;
                    $filterMap[$field] = 'c.' . $field;
                } else {
                    $selects[] = 'cl.' . $field . ' AS ' . $field;
                    $filterMap[$field] = 'cl.' . $field;
                }
            }

            return [
                'select' => implode(',', $selects),
                'from' => db_prefix() . 'clients cl',
                'joins' => [
                    ['table' => db_prefix() . 'contacts c', 'on' => 'c.userid = cl.userid AND c.is_primary = 1', 'type' => 'left'],
                ],
                'filter_map' => $filterMap,
            ];
        }

        if ($csvType === 'leads') {
            $selects = [];
            $filterMap = [];
            foreach ($fields as $field) {
                if ($field === 'tags') {
                    continue;
                }
                $selects[] = 'l.' . $field;
                $filterMap[$field] = 'l.' . $field;
            }
            $selects[] = 'l.id AS _lead_id';

            return [
                'select' => implode(',', $selects),
                'from' => db_prefix() . 'leads l',
                'joins' => [],
                'filter_map' => $filterMap,
            ];
        }

        if ($csvType === 'items') {
            $filterMap = [];
            foreach ($fields as $field) {
                $filterMap[$field] = 'i.' . $field;
            }
            return [
                'select' => implode(',', array_map(function ($f) { return 'i.' . $f; }, $fields)),
                'from' => db_prefix() . 'items i',
                'joins' => [],
                'filter_map' => $filterMap,
            ];
        }

        if ($csvType === 'expenses') {
            $filterMap = [];
            foreach ($fields as $field) {
                $filterMap[$field] = 'e.' . $field;
            }
            return [
                'select' => implode(',', array_map(function ($f) { return 'e.' . $f; }, $fields)),
                'from' => db_prefix() . 'expenses e',
                'joins' => [],
                'filter_map' => $filterMap,
            ];
        }

        if ($csvType === 'staff') {
            $filterMap = [];
            foreach ($fields as $field) {
                $filterMap[$field] = 's.' . $field;
            }
            return [
                'select' => implode(',', array_map(function ($f) { return 's.' . $f; }, $fields)),
                'from' => db_prefix() . 'staff s',
                'joins' => [],
                'filter_map' => $filterMap,
            ];
        }

        if ($csvType === 'external_products') {
            $filterMap = [];
            foreach ($fields as $field) {
                $filterMap[$field] = 'epm.' . $field;
            }
            return [
                'select' => implode(',', array_map(function ($f) { return 'epm.' . $f; }, $fields)),
                'from' => db_prefix() . 'external_products_mapping epm',
                'joins' => [],
                'filter_map' => $filterMap,
            ];
        }

        return [
            'select' => '*',
            'from' => db_prefix() . $csvType,
            'joins' => [],
            'filter_map' => [],
        ];
    }

    private function read_spreadsheet_rows($filePath, $originalName = '', $mimeType = '')
    {
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if ($ext === 'xlsx') {
            require_once APPPATH . 'vendor/nuovo/spreadsheet-reader/SpreadsheetReader.php';
            $reader = new SpreadsheetReader($filePath, $originalName, $mimeType);
            $rows = [];
            foreach ($reader as $row) {
                $rows[] = $row;
            }
            return $rows;
        }

        return array_map('str_getcsv', file($filePath));
    }

    private function output_xlsx($filename, $sheetName, array $headings, array $rows)
    {
        if (!class_exists('ZipArchive')) {
            show_error('ZipArchive is required to export XLSX');
        }

        $sharedStrings = [];
        $sharedIndex = [];
        $sheetRowsXml = '';

        $rowNum = 1;
        $sheetRowsXml .= $this->xlsx_row_xml($rowNum, $headings, $sharedStrings, $sharedIndex);
        $rowNum++;

        foreach ($rows as $row) {
            $sheetRowsXml .= $this->xlsx_row_xml($rowNum, $row, $sharedStrings, $sharedIndex);
            $rowNum++;
        }

        $sharedStringsXml = $this->xlsx_shared_strings_xml($sharedStrings);
        $sheetXml = $this->xlsx_sheet_xml($sheetRowsXml, count($sharedStrings));
        $workbookXml = $this->xlsx_workbook_xml($sheetName);
        $workbookRelsXml = $this->xlsx_workbook_rels_xml();
        $relsXml = $this->xlsx_root_rels_xml();
        $contentTypesXml = $this->xlsx_content_types_xml();
        $stylesXml = $this->xlsx_styles_xml();

        $tmpFile = tempnam(sys_get_temp_dir(), 'xlsx');
        $zip = new ZipArchive();
        $zip->open($tmpFile, ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', $contentTypesXml);
        $zip->addFromString('_rels/.rels', $relsXml);
        $zip->addFromString('xl/workbook.xml', $workbookXml);
        $zip->addFromString('xl/_rels/workbook.xml.rels', $workbookRelsXml);
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);
        $zip->addFromString('xl/sharedStrings.xml', $sharedStringsXml);
        $zip->addFromString('xl/styles.xml', $stylesXml);
        $zip->close();

        header('Pragma: public');
        header('Expires: 0');
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '";');
        header('Content-Transfer-Encoding: binary');
        header('Content-Length: ' . filesize($tmpFile));
        readfile($tmpFile);
        @unlink($tmpFile);
    }

    private function xlsx_row_xml($rowNum, array $values, array &$sharedStrings, array &$sharedIndex)
    {
        $cellsXml = '';
        foreach ($values as $colIndex => $value) {
            $cellRef = $this->xlsx_col_letter($colIndex) . $rowNum;
            $sIndex = $this->xlsx_shared_index((string)$value, $sharedStrings, $sharedIndex);
            $cellsXml .= '<c r="' . $cellRef . '" t="s"><v>' . $sIndex . '</v></c>';
        }
        return '<row r="' . $rowNum . '">' . $cellsXml . '</row>';
    }

    private function xlsx_shared_index($value, array &$sharedStrings, array &$sharedIndex)
    {
        if (!array_key_exists($value, $sharedIndex)) {
            $sharedIndex[$value] = count($sharedStrings);
            $sharedStrings[] = $value;
        }
        return $sharedIndex[$value];
    }

    private function xlsx_shared_strings_xml(array $sharedStrings)
    {
        $count = count($sharedStrings);
        $items = '';
        foreach ($sharedStrings as $str) {
            $items .= '<si><t xml:space="preserve">' . $this->xlsx_escape($str) . '</t></si>';
        }
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="' . $count . '" uniqueCount="' . $count . '">'
            . $items
            . '</sst>';
    }

    private function xlsx_sheet_xml($rowsXml, $sharedCount)
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<dimension ref="A1"/>'
            . '<sheetViews><sheetView workbookViewId="0"/></sheetViews>'
            . '<sheetFormatPr defaultRowHeight="15"/>'
            . '<sheetData>' . $rowsXml . '</sheetData>'
            . '</worksheet>';
    }

    private function xlsx_workbook_xml($sheetName)
    {
        $safeName = substr($sheetName, 0, 31);
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="' . $this->xlsx_escape($safeName) . '" sheetId="1" r:id="rId1"/></sheets>'
            . '</workbook>';
    }

    private function xlsx_workbook_rels_xml()
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/>'
            . '</Relationships>';
    }

    private function xlsx_root_rels_xml()
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';
    }

    private function xlsx_content_types_xml()
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '</Types>';
    }

    private function xlsx_styles_xml()
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="1"><font><sz val="11"/><color theme="1"/><name val="Calibri"/><family val="2"/></font></fonts>'
            . '<fills count="1"><fill><patternFill patternType="none"/></fill></fills>'
            . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="1"><xf numFmtId="49" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/></cellXfs>'
            . '</styleSheet>';
    }

    private function xlsx_escape($value)
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private function xlsx_col_letter($index)
    {
        $index = (int)$index;
        $letter = '';
        while ($index >= 0) {
            $letter = chr($index % 26 + 65) . $letter;
            $index = intdiv($index, 26) - 1;
        }
        return $letter;
    }

    public function delete_mapping($id='')
    {
        if (!has_permission('importsync', '', 'delete')) {
            access_denied('importsync');
        }

        if (!$id) {
            redirect(admin_url('importsync/manage_mappings'));
        }

        $response = $this->importsync_model->deleteMappedCsv($id);

        if ($response == true) {
            set_alert('success', _l('deleted', _l('importsync')));
        } else {
            set_alert('warning', _l('problem_deleting', _l('importsync')));
        }

        redirect(admin_url('importsync/manage_mappings'));
    }

    public function import_staff()
    {
        if (!staff_can('import_staff', 'importsync')) {
            access_denied('importsync');
        }

        $staffImporter = new Import_staff();

        $staffImporter->setDatabaseFields($this->db->list_fields(db_prefix() . 'staff'))
            ->setCustomFields(get_custom_fields('staff'));

        if ($this->input->post('download_sample') === 'true') {
            $staffImporter->downloadSample();
        }

        if (
            $this->input->post()
            && isset($_FILES['file_csv']['name']) && $_FILES['file_csv']['name'] != ''
        ) {
            $staffImporter->setSimulation($this->input->post('simulate'))
                ->setTemporaryFileLocation($_FILES['file_csv']['tmp_name'])
                ->setFilename($_FILES['file_csv']['name'])
                ->perform();

            $data['total_rows_post'] = $staffImporter->totalRows();

            if (!$staffImporter->isSimulation()) {
                set_alert('success', _l('import_total_imported', $staffImporter->totalImported()));
            }
        }

        $data['title'] = _l('importsync_import_staff');
        $data['importInstance'] = $staffImporter;
        $this->load->view('import_staff', $data);
    }
}
