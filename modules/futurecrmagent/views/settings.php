<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="com-md-12">
    <?php $CI = &get_instance(); ?>

    <div class="alert alert-info">
        FutureCRM Agent uses the FutureAgent LLM proxy settings. Configure base URL, API key and default model in
        <a href="<?php echo admin_url('futureagent/settings'); ?>">FutureAgent Settings</a>.
    </div>

    <ul class="nav nav-tabs" role="tablist">
        <li role="presentation" class="active"><a href="#futurecrmagent-sales" role="tab" data-toggle="tab">Sales Document Agent</a></li>
        <li role="presentation"><a href="#futurecrmagent-classification" role="tab" data-toggle="tab">Ticket Classification</a></li>
        <li role="presentation"><a href="#futurecrmagent-history" role="tab" data-toggle="tab">History</a></li>
    </ul>

    <div class="tab-content mtop20">
        <div role="tabpanel" class="tab-pane active" id="futurecrmagent-sales">
            <h4 class="mbot10">AI Sales Document Draft Builder</h4>
            <?php echo render_textarea(
                'settings[futurecrmagent_sales_doc_system_prompt]',
                'Sales document system prompt',
                get_option('futurecrmagent_sales_doc_system_prompt'),
                ['rows' => 5]
            ); ?>
            <p class="text-muted">
                The assistant is available on draft Estimate, Proposal and Invoice edit screens. It creates structured JSON and fills the current draft only.
            </p>
        </div>

        <div role="tabpanel" class="tab-pane" id="futurecrmagent-classification">
            <h4 class="mbot10">Ticket Classification</h4>
            <div class="checkbox checkbox-primary">
                <input type="checkbox" id="futurecrmagent_ticket_classify_enabled" name="settings[futurecrmagent_ticket_classify_enabled]" value="1" <?php echo get_option('futurecrmagent_ticket_classify_enabled') == '1' ? 'checked' : ''; ?>>
                <label for="futurecrmagent_ticket_classify_enabled">Enable classification on piped tickets</label>
            </div>

            <?php echo render_textarea('settings[futurecrmagent_ticket_prompt]', 'Classification prompt template', get_option('futurecrmagent_ticket_prompt'), ['rows' => 6]); ?>
            <p class="text-muted">Available placeholders: <code>{$subject}</code>, <code>{$body}</code>. The model must return strict JSON.</p>

            <h4 class="mtop20 mbot10">Department, Priority and Status Mapping</h4>
            <?php
            $departments = $CI->db->get(db_prefix() . 'departments')->result_array();
            $priorities = $CI->db->get(db_prefix() . 'tickets_priorities')->result_array();
            $statuses = $CI->db->get(db_prefix() . 'tickets_status')->result_array();

            foreach ([
                'technical_issue' => 'Technical Issue',
                'billing' => 'Billing',
                'sales' => 'Sales',
                'account' => 'Account',
                'feedback' => 'Feedback',
                'other' => 'Other',
            ] as $key => $label) {
                echo render_select('settings[futurecrmagent_map_dept_' . $key . ']', $departments, ['departmentid', 'name'], $label . ' -> Department', get_option('futurecrmagent_map_dept_' . $key));
                echo render_select('settings[futurecrmagent_map_status_' . $key . ']', $statuses, ['ticketstatusid', 'name'], $label . ' -> Status', get_option('futurecrmagent_map_status_' . $key));
            }

            foreach ([
                'low' => 'Low',
                'medium' => 'Medium',
                'high' => 'High',
                'urgent' => 'Urgent',
            ] as $key => $label) {
                echo render_select('settings[futurecrmagent_map_pri_' . $key . ']', $priorities, ['priorityid', 'name'], $label . ' -> Priority', get_option('futurecrmagent_map_pri_' . $key));
            }
            ?>
        </div>

        <div role="tabpanel" class="tab-pane" id="futurecrmagent-history">
            <h4 class="mtop20 mbot10">Ticket Classification History</h4>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>From</th>
                            <th>Subject</th>
                            <th>Classification</th>
                            <th>Score</th>
                            <th>Ticket</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $CI->load->model('futurecrmagent/Futurecrmagent_model');
                        foreach ($CI->Futurecrmagent_model->get_history_latest(50) as $log) { ?>
                            <tr>
                                <td><?php echo e(_dt($log->created_at)); ?></td>
                                <td><?php echo e($log->email_from); ?></td>
                                <td title="<?php echo e($log->subject); ?>"><?php echo e(mb_strimwidth($log->subject, 0, 60, '...')); ?></td>
                                <td><?php echo e($log->classification); ?></td>
                                <td><?php echo e($log->score); ?></td>
                                <td>
                                    <?php if ($log->ticket_id) { ?>
                                        <a href="<?php echo admin_url('tickets/ticket/' . $log->ticket_id); ?>" target="_blank">#<?php echo e($log->ticket_id); ?></a>
                                    <?php } else { echo '-'; } ?>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
