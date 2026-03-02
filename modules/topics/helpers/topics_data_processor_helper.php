<?php defined('BASEPATH') or exit('No direct script access allowed');

// Add this function before the interface definition
function sort_by_position($topics) {
    usort($topics, function($a, $b) {
        return ($a['position'] ?? 0) - ($b['position'] ?? 0);
    });
    return $topics;
}

interface DataProcessor {
    public function process($topic_id, $data);
    public function render_form($topic_id, $topic_items);
}

class BuildPostStructureProcessor implements DataProcessor {
    private const BACKUP_TARGET_ID = 4;

    private function create_backup($topic_id) {
        $CI = &get_instance();
        
        // Lấy topic hiện tại
        $CI->db->where('id', $topic_id);
        $current_topic = $CI->db->get(db_prefix() . 'topics')->row();
        
        if ($current_topic) {
            // Tạo bản backup từ topic hiện tại
            $backup_topic = (array)$current_topic;
            unset($backup_topic['id']); // Bỏ ID để tạo bản ghi mới
            
            // Set các thông tin backup
            $backup_topic['status'] = 0; // Set status = 0 cho bản backup
            $backup_topic['target_id'] = self::BACKUP_TARGET_ID; // Backup target ID
            $backup_topic['log'] = json_encode([
                'backup_date' => date('Y-m-d H:i:s'),
                'backup_reason' => 'process_data_change',
                'backup_from_id' => $topic_id
            ]);
            
            // Thêm bản backup vào database
            $CI->db->insert(db_prefix() . 'topics', $backup_topic);
            return $CI->db->insert_id();
        }
        return false;
    }

    public function process($topic_id, $data) {
        $CI = &get_instance();
        
        // Tạo backup trước khi xử lý
        $backup_id = $this->create_backup($topic_id);
        if (!$backup_id) {
            set_alert('warning', _l('backup_creation_failed'));
            return false;
        }

        // Kiểm tra dữ liệu đầu vào
        if (empty($data['items'])) {
            set_alert('warning', _l('no_data_to_process'));
            return false;
        }

        // Kiểm tra các trường bắt buộc
        foreach ($data['items'] as $item) {
            if (!isset($item['target_id']) || !isset($item['position']) || !isset($item['title'])) {
                set_alert('warning', _l('missing_required_fields'));
                return false;
            }
        }
        
        try {
            // Process and save the data
            foreach ($data['items'] as $item) {
                // Kiểm tra nếu cả 3 trường đều trống
                if (empty($item['position']) && empty($item['title']) && empty($item['content'])) {
                    // Không hiển thị mục này trong danh sách
                    continue; // Bỏ qua mục này
                }

                // $CI->db->where('id', $item['target_id']);
                // $CI->db->update(db_prefix() . 'topic_target', [
                //     'data' => json_encode([
                //         'position' => $item['position'],
                //         'title' => $item['title'],
                //         'content' => $item['content']
                //     ])
                // ]);
            }
            return true;
        } catch (Exception $e) {
            log_activity('Data processing failed: ' . $e->getMessage());
            return false;
        }
    }

    public function render_form($topic_id, $default_topic_items) {
        $CI = &get_instance();
        
        // Lấy thông tin topic từ bảng topics và topic_master
        $CI->db->select('tm.topictitle as title, tm.topicid');
        $CI->db->from(db_prefix() . 'topics t');
        $CI->db->join(db_prefix() . 'topic_master tm', 't.topicid = tm.topicid');
        $CI->db->where('t.id', $topic_id);
        $topic_info = $CI->db->get()->row();
        
        // Lọc topic_items theo điều kiện:
        // 1. Không phải backup target (target_id != 4)
        // 2. Có valid_data = 1 (đã được kiểm tra từ controller)
        // 3. Có data không rỗng
        $filtered_topics = array_filter($default_topic_items, function($topic_item) {
            return $topic_item['target_id'] != self::BACKUP_TARGET_ID 
                   && !empty($topic_item['data']);
        });

        // Xóa khỏi filtered_topics nếu không có các dữ liệu cần thiết
        $filtered_topics = array_filter($filtered_topics, function($topic_item) {
            $data = json_decode(repair_json($topic_item['data']), true);
            return !empty($data['Item_Position']) || !empty($data['Item_Title']) || !empty($data['Item_Content']);
        });

        if (empty($filtered_topics)) {
            return '<div class="alert alert-warning">'._l('no_data_available').'</div>';
        }

        // Xử lý hiển thị form
        $last_position = 0;
        foreach ($filtered_topics as &$topic_item) {
            if (isset($topic_item['data'])) {
                try {
                    
                    $fixedJson = repair_json($topic_item['data']);
                    $data = json_decode($fixedJson, true);
                    
              
                    // Kiểm tra nếu cả 3 trường đều trống
                    if (empty($data['Item_Position']) && empty($data['Item_Title']) && empty($data['Item_Content'])) {
                        continue; // Bỏ qua mục này
                    }

                    // Xử lý position
                    if (isset($topic_item['position'])) {
                        $topic_item['position'] = $topic_item['position'];
                    } else if (!empty($data['Item_Position'])) {
                        // Trích xuất số từ chuỗi (ví dụ: "Top 1" => 1)
                        preg_match('/\d+/', $data['Item_Position'], $matches);
                        $topic_item['position'] = !empty($matches) ? intval($matches[0]) : $last_position + 1;
                    } else {
                        // Nếu không có Item_Position, lấy vị trí tiếp theo
                        $topic_item['position'] = $last_position + 1;
                    }
                    
                    // Cập nhật last_position
                    $last_position = max($last_position, $topic_item['position']);
                    
                    $topic_item['title'] = $data['Item_Title'] ?? '';
                    $topic_item['content'] = $data['Item_Content'] ?? '';
                    $topic_item['Item_Position'] = $data['Item_Position'] ?? '';
                } catch (Exception $e) {
                    log_activity('JSON processing error for target ID ' . ($topic_item['topic_id'] ?? 'unknown') . ': ' . $e->getMessage() . ' - ' . $topic_item['data']);
                    continue;
                }
            }

            // $filtered_topics = sort_by_position($filtered_topics);
        }

        $html = '<div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <!-- Topic Detail Section -->
                        <div class="topic-detail-section">
                            <h4 class="bold">'._l('topic_detail').'</h4>
                            <hr />
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>'._l('topic_id').':</strong> <span class="copy-text" data-copy="'.($topic_info->topicid ?? '').'" style="cursor:pointer">'.($topic_info->topicid ?? 'N/A').' <i class="fa fa-copy"></i></span></p>
                                    <p><strong>'._l('topic_title').':</strong> '.($topic_info->title ?? 'N/A').'</p>
                                    <p><strong>'._l('total_targets').':</strong> '.count($filtered_topics).'</p>
                                </div>
                            </div>
                        </div>
                        <hr />
                        
                        <!-- Action Buttons -->
                        <div class="row mbot15">
                            <div class="col-md-12">
                                <a href="'.admin_url('topics/detail/'.$topic_id).'" class="btn btn-default pull-left">
                                    <i class="fa fa-arrow-left"></i> '._l('back_to_topic').'
                                </a>
                                <button type="button" class="btn btn-warning pull-right mright5" onclick="resetOriginalData('.$topic_id.')">
                                    <i class="fa fa-refresh"></i> '._l('reset_to_original').'
                                </button>
                            </div>
                        </div>

                        <!-- Form Content -->
                        <form id="post-structure-form">
                            <div class="table-responsive">
                                <table class="table table-striped table-sortable">
                                    <thead>
                                        <tr>
                                            <th>'._l('position').'</th>
                                            <th>'._l('title').'</th>
                                            <th>'._l('content').'</th>
                                            <th>'._l('status').'</th>
                                            <th>'._l('quick_save').'</th>
                                        </tr>
                                    </thead>
                                    <tbody>';
        
        foreach ($filtered_topics as $topic_item) {
            $html .= '<tr data-topic-id="'.$topic_item['topic_id'].'">
                <td>
                    <input type="number" name="items['.$topic_item['topic_id'].'][position]" value="'.($topic_item['position'] ?? '').'" class="form-control" required />
                    <input type="hidden" name="items['.$topic_item['topic_id'].'][Item_Position]" value="'.htmlspecialchars($topic_item['Item_Position'] ?? '').'" />
                    <input type="hidden" name="items['.$topic_item['topic_id'].'][original_data]" value="'.htmlspecialchars($topic_item['data'] ?? '').'" />
                </td>
                <td>
                    <input type="text" name="items['.$topic_item['topic_id'].'][title]" value="'.($topic_item['title'] ?? '').'" class="form-control" required />
                </td>
                <td>
                    <textarea id="content_' . $topic_item['topic_id'] . '" 
                             name="items[' . $topic_item['topic_id'] . '][content]" 
                             class="form-control tinymce">' . htmlspecialchars($topic_item['content'] ?? '') . '</textarea>
                
                </td>
                <td>
                    <div class="toggle-container">
                        <input type="checkbox" 
                               name="items['.$topic_item['topic_id'].'][status]" 
                               id="status_'.$topic_item['topic_id'].'" 
                               class="toggle-checkbox" 
                               '.($topic_item['status'] ? 'checked' : '').' 
                               value="1" />
                        <label class="toggle-label" for="status_'.$topic_item['topic_id'].'"></label>
                    </div>
                </td>
                <td>
                    <button type="button" class="btn btn-info btn-sm quick-save-btn" 
                            onclick="quickSaveItem('.$topic_item['topic_id'].')">
                        <i class="fa fa-save"></i> '._l('quick_save').'
                    </button>
                </td>
            </tr>';
        }
        
        $html .= '</tbody></table></div>
                        <div class="row">
                            <div class="col-md-12">
                                <button type="submit" class="btn btn-primary pull-right" onclick="return confirm(\''._l('confirm_process_data').'\');">
                                    <i class="fa fa-check"></i> '._l('save_changes').'
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>';
        
        $html .= '
        <script>
        
        // Define quickSaveItem in global scope
        window.quickSaveItem = function(targetId) {
            console.log("quickSaveItem", targetId);
            if (typeof jQuery === "undefined") {
                console.error("jQuery is not loaded");
                return;
            }
            
            var $row = jQuery("tr[data-topic-id=\"" + targetId + "\"]");
            var position = $row.find("input[name=\"items[" + targetId + "][position]\"]").val();
            var topicId = targetId;

            var formData = new FormData();
            
            var editor = tinymce.get("content_" + targetId);
            var content = editor ? editor.getContent() : "";
            
            // Get original data from hidden field
            var originalData = jQuery("input[name=\"items[" + targetId + "][original_data]\"]").val();
            var originalJson = {};
            try {
                originalJson = JSON.parse(originalData);
            } catch(e) {
                console.warn("Could not parse original data", e);
            }
            
            // Merge updated fields with original data
            var itemData = {
                ...originalJson, // Spread original data first
                Item_Position: jQuery("input[name=\"items[" + targetId + "][Item_Position]\"]").val(),
                Item_Title: jQuery("input[name=\"items[" + targetId + "][title]\"]").val(),
                Item_Content: content,
                status: jQuery("input[name=\"items[" + targetId + "][status]\"]").is(":checked") ? 1 : 0
            };
            
            // formData.append("target_id", targetId);
            formData.append("topic_id", topicId);
            formData.append("position", position);
            formData.append("data", JSON.stringify(itemData));
            
            jQuery.ajax({
                url: admin_url + "topics/quick_save_item",
                type: "POST",
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    try {
                        response = typeof response === "string" ? JSON.parse(response) : response;
                        if (response.success) {
                            alert_float("success", response.message || "Item saved successfully.");
                        } else {
                            alert_float("danger", response.message || "Save failed.");
                        }
                    } catch(e) {
                        console.error("Error processing response:", e);
                        alert_float("danger", "Save failed.");
                    }
                },
                error: function(xhr, status, error) {
                    console.error("Ajax error:", error);
                    alert_float("danger", "Save failed.");
                }
            });
        };
        </script>';
        
        $html .= '
        <style>
        .toggle-container {
            position: relative;
            width: 60px;
            height: 34px;
        }
        .toggle-checkbox {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .toggle-label {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            border-radius: 34px;
            transition: .4s;
        }
        .toggle-label:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            border-radius: 50%;
            transition: .4s;
        }
        .toggle-checkbox:checked + .toggle-label {
            background-color: #2196F3;
        }
        .toggle-checkbox:checked + .toggle-label:before {
            transform: translateX(26px);
        }
        </style>
        <script>
        function resetOriginalData(topicId) {
            if (confirm("'._l('confirm_reset_data').'")) {
                $.ajax({
                    url: admin_url + "topics/reset_process_data/" + topicId,
                    type: "POST",
                    success: function(response) {
                        if (response.success) {
                            alert_float("success", response.message);
                            location.reload();
                        } else {
                            alert_float("danger", response.message);
                        }
                    }
                });
            }
        }

        </script>
        ';
        
        return $html;
    }
}
