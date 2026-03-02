<?php defined('BASEPATH') or exit('No direct script access allowed');

try {
    $aColumns = [
        'id',
        'site',
        'platform',
        'status',
        'datecreated'
    ];

    $sIndexColumn = 'id';
    $sTable = db_prefix() . 'topic_controllers';

    $result = data_tables_init($aColumns, $sIndexColumn, $sTable, [], [], [
        'id',
        'site',
        'platform',
        'status',
        'datecreated'
    ]);

    $output  = $result['output'];
    $rResult = $result['rResult'];

    $output['data'] = [];
    unset($output['aaData']);
    foreach ($rResult as $aRow) {
        $row = [];
        
        // ID
        $row[] = $aRow['id'];
        
        // Site with link
        $row[] = '<a href="' . admin_url('topics/controllers/view/' . $aRow['id']) . '">' . html_escape($aRow['site']) . '</a>';
        
        // Platform
        $row[] = html_escape($aRow['platform']);
        

        // Status
        $status_label = $aRow['status'] == 1 ? 'success' : 'danger';
        $status_text = $aRow['status'] == 1 ? _l('active') : _l('inactive');
        $row[] = '<span class="label label-' . $status_label . '">' . $status_text . '</span>';
        
        // Date Created
        $row[] = _dt($aRow['datecreated']);
        
        // Options
        $options = icon_btn('topics/controllers/edit/' . $aRow['id'], 'pencil-square fa fa-pen-to-square fa-lg', 'btn-default', [
            'title' => _l('edit')
        ]);
        
        // Add clone button
        if (has_permission('topics', '', 'create')) {
            $options .= icon_btn('topics/controllers/clone/' . $aRow['id'], 'copy fa fa-copy fa-lg', 'btn-info', [
                'title' => _l('clone')
            ]);
        }
        
        if (has_permission('topics', '', 'delete')) {
            // $options .= icon_btn('topics/controllers/delete/' . $aRow['id'], 'remove', 'btn-danger _delete');
        }
        $row[] = $options;
        
        $output['data'][] = $row;
    }

    header('Content-Type: application/json');
    echo json_encode($output);
    die();

} catch (Exception $e) {
    // Log the error
    log_activity("Error in controllers_table: " . $e->getMessage());
    
    // Prepare error response matching DataTables format
    $output = [
        'draw' => isset($_POST['draw']) ? intval($_POST['draw']) : 1,
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => [[
            '0', // ID
            '<span class="text-danger"><i class="fa fa-exclamation-triangle"></i></span>', // Site
            '', // Platform
            '', // API Token
            '<span class="label label-danger">Error</span>', // Status
            _dt(date('Y-m-d H:i:s')), // Date
            '' // Options
        ]],
        'error' => 'Error loading data: ' . $e->getMessage()
    ];
    
    header('Content-Type: application/json');
    echo json_encode($output);
    die();
} 