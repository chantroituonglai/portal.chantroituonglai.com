<?php
$edited = has_permission('deals', '', 'edit');
?>

<table class="table table-hover pt-3" id="">
    <thead>
    <tr>

        <th style="width: 200px;"><?= _l('tasks_dt_name') ?></th>
        <th><?= _l('task_status') ?></th>
        <th style="width: 100px;"><?= _l('tasks_dt_datestart') ?></th>
        <th style="width: 100px;" class="duedate"><?= _l('task_duedate') ?></th>
        <th><?= _l('tags') ?></th>
        <th><?= _l('tasks_list_priority') ?></th>
    </tr>
    </thead>
    <tbody>
    <?php
    $all_task_info = join_data(db_prefix() . 'tasks', db_prefix() . 'tasks.*,tbl_deals.title,(SELECT GROUP_CONCAT(name SEPARATOR ",") FROM ' . db_prefix() . 'taggables JOIN ' . db_prefix() . 'tags ON ' . db_prefix() . 'taggables.tag_id = ' . db_prefix() . 'tags.id WHERE rel_id = ' . db_prefix() . 'tasks.id and rel_type="task" ORDER by tag_order ASC) as tags', array(db_prefix() . 'tasks.rel_type' => 'deals', db_prefix() . 'tasks.rel_id' => $deals_details->id,), ['tbl_deals' => 'tbl_deals.id = ' . db_prefix() . 'tasks.rel_id AND ' . db_prefix() . 'tasks.rel_type = "deals"'], 'object');
    $hasPermissionEdit = has_permission('tasks', '', 'edit');
    $hasPermissionDelete = has_permission('tasks', '', 'delete');
    $tasksPriorities = get_tasks_priorities();
    $task_statuses = $this->tasks_model->get_statuses();
    if (!empty($all_task_info)) {

        foreach ($all_task_info as $key => $v_task) {

            $outputName = '';
            $outputName .= '<a href="' . admin_url('tasks/view/' . $v_task->id) . '" class="display-block main-tasks-table-href-name' . (!empty($v_task->rel_id) ? ' mbot5' : '') . '" onclick="init_task_modal(' . $v_task->id . '); return false;">' . $v_task->name . '</a>';
            if (!empty($v_task->rel_id)) {
                $link = admin_url('deals/details/' . $v_task->rel_id);
                $outputName .= '<span class="hide"> - </span><a class="tw-text-neutral-700 task-table-related tw-text-sm" data-toggle="tooltip" title="' . _l('task_related_to') . '" href="' . $link . '">' . $v_task->title . '</a>';
            }
            if (!empty($v_task->recurring)) {
                $outputName .= '<br /><span class="label label-primary inline-block mtop4"> ' . _l('recurring_task') . '</span>';
            }
            $outputName .= '<div class="row-options">';

            $class = 'text-success bold';
            $style = '';
            $tooltip = '';
            if ($v_task->billed == 1 || $v_task->status == Tasks_model::STATUS_COMPLETE) {
                $class = 'text-dark disabled';
                $style = 'style="opacity:0.6;cursor: not-allowed;"';
                if ($v_task->status == Tasks_model::STATUS_COMPLETE) {
                    $tooltip = ' data-toggle="tooltip" data-title="' . format_task_status($v_task->status, false, true) . '"';
                } elseif ($v_task->billed == 1) {
                    $tooltip = ' data-toggle="tooltip" data-title="' . _l('task_billed_cant_start_timer') . '"';
                } elseif (!$v_task->is_assigned) {
                    $tooltip = ' data-toggle="tooltip" data-title="' . _l('task_start_timer_only_assignee') . '"';
                }
            }
            $outputName .= '<span' . $tooltip . ' ' . $style . '><a href="#" class="' . $class . ' tasks-table-start-timer" onclick="timer_action(this,' . $v_task->id . '); return false;">' . _l('task_start_timer') . '</a></span>';
            if ($hasPermissionEdit) {
                $outputName .= ' | <a href="#" onclick="edit_task(' . $v_task->id . '); return false;">' . _l('edit') . '</a>';
            }
            if ($hasPermissionDelete) {
                $outputName .= '<span class="tw-text-neutral-300"> | </span><a href="' . admin_url('tasks/delete_task/' . $v_task->id) . '" class="text-danger _delete task-delete">' . _l('delete') . '</a>';
            }
            $outputName .= '</div>';

            $status = get_task_status_by_id($v_task->status);
            $canChangeStatus = has_permission('tasks', '', 'edit');
            $outputStatus = '';
            $outputStatus .= '<span class="label" style="color:' . $status['color'] . ';border:1px solid ' . adjust_hex_brightness($status['color'], 0.4) . ';background: ' . adjust_hex_brightness($status['color'], 0.04) . ';" task-status-table="' . $v_task->status . '">';

            $outputStatus .= $status['name'];

            if ($canChangeStatus) {
                $outputStatus .= '<div class="dropdown inline-block mleft5 table-export-exclude">';
                $outputStatus .= '<a href="#" style="font-size:14px;vertical-align:middle;" class="dropdown-toggle text-dark" id="tableTaskStatus-' . $v_task->id . '" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">';
                $outputStatus .= '<span data-toggle="tooltip" title="' . _l('ticket_single_change_status') . '"><i class="fa-solid fa-chevron-down tw-opacity-70"></i></span>';
                $outputStatus .= '</a>';

                $outputStatus .= '<ul class="dropdown-menu dropdown-menu-right" aria-labelledby="tableTaskStatus-' . $v_task->id . '">';
                foreach ($task_statuses as $taskChangeStatus) {
                    if ($v_task->status != $taskChangeStatus['id']) {
                        $outputStatus .= '<li>
                  <a href="#" onclick="task_mark_as(' . $taskChangeStatus['id'] . ',' . $v_task->id . '); return false;">
                     ' . _l('task_mark_as', $taskChangeStatus['name']) . '
                  </a>
               </li>';
                    }
                }
                $outputStatus .= '</ul>';
                $outputStatus .= '</div>';
            }

            $outputStatus .= '</span>';

            $outputPriority = '<span style="color:' . task_priority_color($v_task->priority) . ';" class="inline-block">' . task_priority($v_task->priority);

            if (has_permission('tasks', '', 'edit') && $v_task->status != Tasks_model::STATUS_COMPLETE) {
                $outputPriority .= '<div class="dropdown inline-block mleft5 table-export-exclude">';
                $outputPriority .= '<a href="#" style="font-size:14px;vertical-align:middle;" class="dropdown-toggle text-dark" id="tableTaskPriority-' . $v_task->id . '" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">';
                $outputPriority .= '<span data-toggle="tooltip" title="' . _l('task_single_priority') . '"><i class="fa-solid fa-chevron-down tw-opacity-70"></i></span>';
                $outputPriority .= '</a>';

                $outputPriority .= '<ul class="dropdown-menu dropdown-menu-right" aria-labelledby="tableTaskPriority-' . $v_task->id . '">';
                foreach ($tasksPriorities as $priority) {
                    if ($v_task->priority != $priority['id']) {
                        $outputPriority .= '<li>
                  <a href="#" onclick="task_change_priority(' . $priority['id'] . ',' . $v_task->id . '); return false;">
                     ' . $priority['name'] . '
                  </a>
               </li>';
                    }
                }
                $outputPriority .= '</ul>';
                $outputPriority .= '</div>';
            }

            $outputPriority .= '</span>';

            ?>
            <tr id="deal_tasks_<?= $v_task->id ?>">

                <td>
                    <?= $outputName ?>
                </td>
                <td>
                    <?= $outputStatus ?>
                </td>
                <td>
                    <?= _d($v_task->startdate) ?>
                </td>
                <td>
                    <?= _d($v_task->duedate) ?>
                </td>
                <td>
                    <?= render_tags($v_task->tags); ?>
                </td>
                <td>
                    <?= $outputPriority; ?>
                </td>
            </tr>
        <?php }
    }; ?>
    </tbody>
</table>