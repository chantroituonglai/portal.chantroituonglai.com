<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head();
?>

<script src="<?php echo module_dir_url('topics', 'assets/js/processess.js'); ?>"></script>

<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="no-margin"><?php echo _l('process_data'); ?></h4>
                        <hr class="hr-panel-heading" />
                        
                        <?php 
                        // Hiển thị thông tin backup gần nhất nếu có
                        $latest_backup = $Topics_model->get_latest_backup($topic->id);
                        if ($latest_backup && true == false): 
                            $backup_data = json_decode($latest_backup->log);
                        ?>
                        <div class="alert alert-info">
                            <p><strong><?php echo _l('last_backup'); ?>:</strong> 
                               <?php echo date('d/m/Y H:i:s', strtotime($backup_data->backup_date)); ?>
                            </p>
                        </div>
                        <?php endif; ?>
                        
                        <?php echo form_open(admin_url('topics/process_data/'.$topic->id.'/'.$topic->action_type_code)); ?>
                      
                        <?php echo $processor->render_form($topic->id, $topic_items); ?>
                        
                        <div class="btn-bottom-toolbar text-right btn-toolbar-container-out">
                            <button type="button" class="btn btn-default" onclick="window.history.back();">
                                <i class="fa fa-angle-left"></i> <?php echo _l('back'); ?>
                            </button>
                            <button type="button" class="btn btn-info mleft5" onclick="showSortModal()">
                                <i class="fa fa-sort"></i> <?php echo _l('sort_items'); ?>
                            </button>
                        </div>
                        <?php echo form_close(); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
<script>
$(function(){
    // Initialize TinyMCE
    init_editor('.tinymce');
    
    // Initialize sortable table
    $('.table-sortable tbody').sortable({
        helper: fixHelperTableRow,
        update: function(event, ui) {
            var i = 1;
            $(this).find('tr').each(function(){
                $(this).find('input[name*="[position]"]').val(i);
                i++;
            });
        }
    }).disableSelection();
});

function fixHelperTableRow(e, ui) {
    ui.children().each(function() {
        $(this).width($(this).width());
    });
    return ui;
}

function showSortModal() {
    var items = [];
    $('.table-sortable tbody tr').each(function() {
        var $row = $(this);
        items.push({
            topic_id: $row.data('topic-id'),
            position: $row.find('input[name*="[position]"]').val(),
            title: $row.find('input[name*="[title]"]').val()
        });
    });

    // Sắp xếp items theo position hiện tại
    items.sort((a, b) => parseInt(a.position) - parseInt(b.position));

    // Tạo danh sách trong modal
    var $sortableList = $('#sortable-items').empty();
    items.forEach(function(item) {
        $sortableList.append(`
            <li class="list-group-item" data-topic-id="${item.topic_id}">
                <span class="badge">${item.position}</span>
                <i class="fa fa-bars handle mright10"></i>
                ${item.title}
            </li>
        `);
    });

    // Khởi tạo sortable
    $('#sortable-items').sortable({
        handle: '.handle',
        axis: 'y'
    });

    $('#sortItemsModal').modal('show');
}

function saveSortOrder() {
    var newPositions = [];
    var position = 1;

    // Lấy thứ tự mới từ danh sách đã sắp xếp
    $('#sortable-items li').each(function() {
        var topicId = $(this).data('topic-id');
        newPositions.push({
            topic_id: topicId,
            position: position++
        });
    });

    // Cập nhật position trong form
    newPositions.forEach(function(item) {
        var $row = $(`.table-sortable tbody tr[data-topic-id="${item.topic_id}"]`);
        $row.find('input[name*="[position]"]').val(item.position);
    });

    // Cập nhật vị trí trên server
    $.ajax({
        url: admin_url + 'topics/update_positions',
        type: 'POST',
        data: {
            positions: newPositions
        },
        success: function(response) {
            try {
                response = typeof response === "string" ? JSON.parse(response) : response;
                if (response.success) {
                    // Đóng modal trước
                $('#sortItemsModal').modal('hide');
                // Hiển thị thông báo thành công
                alert_float('success', response.message || 'Positions saved successfully');
                // Đợi 1 giây rồi mới reload để người dùng kịp thấy thông báo
                setTimeout(function() {
                    location.reload();
                    }, 1000);
                } else {
                    alert_float('danger', response.message || 'Error saving positions');
                }
            } catch(e) {
                console.error('Error processing response:', e);
                alert_float('danger', 'Error saving positions');
            }
        },
        error: function() {
            alert_float('danger', 'Error saving positions');
        }
    });
}

// Vô hiệu hóa sắp xếp trực tiếp trên bảng
$('.table-sortable tbody').sortable('destroy');
</script>
<style>
.handle {
    cursor: move;
    color: #777;
}
#sortable-items .list-group-item {
    display: flex;
    align-items: center;
}
#sortable-items .badge {
    margin-right: 10px;
}
</style>
<div class="modal fade" id="sortItemsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><?php echo _l('sort_items_title'); ?></h4>
            </div>
            <div class="modal-body">
                <ul id="sortable-items" class="list-group">
                    <!-- Items sẽ được thêm vào đây bằng JavaScript -->
                </ul>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
                <button type="button" class="btn btn-primary" onclick="saveSortOrder()"><?php echo _l('save_positions'); ?></button>
            </div>
        </div>
    </div>
</div>