<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>

<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="tw-mb-2 sm:tw-mb-4">
                    <a href="<?php echo admin_url('topics/topic_master/create'); ?>" class="btn btn-primary">
                        <i class="fa fa-plus tw-mr-1"></i>
                        <?php echo _l('new_topic_master'); ?>
                    </a>
                </div>

                <div class="panel_s">
                    <div class="panel-body panel-table-full">
                        <?php render_datatable([
                            'ID',
                            _l('topic_id'),
                            _l('title'),
                            _l('status'),
                            _l('created_date'), 
                            _l('updated_date'),
                            _l('options')
                        ], 'topic-master'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php init_tail(); ?>

<script>
$(function() {
    initDataTable('.table-topic-master', 
        window.location.href + '/table', 
        undefined, 
        undefined, 
        undefined,
        [4, 'desc']
    );
});
</script> 