<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>

<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <div class="_buttons">
                            <?php if (has_permission('topics', '', 'create')) { ?>
                                <a href="<?php echo admin_url('topics/topic_master/target_create'); ?>" class="btn btn-info pull-left display-block">
                                    <?php echo _l('new_topic_target'); ?>
                                </a>
                            <?php } ?>
                        </div>
                        <div class="clearfix"></div>
                        <hr class="hr-panel-heading" />
                        <div class="clearfix"></div>
                        <?php render_datatable([
                            _l('id'),
                            _l('title'),
                            _l('target_type'),
                            _l('status'),
                            _l('datecreated'),
                            _l('options')
                        ], 'topic-targets'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php init_tail(); ?>

<script>
$(function() {
    var tAPI = initDataTable('.table-topic-targets', 
        '<?php echo admin_url("topics/topic_master/target_table"); ?>', 
        [0, 1, 2, 3, 4], // Sortable columns
        [0, 1, 2, 3, 4], // Searchable columns
        undefined, 
        [0, 'desc']
    );
});
</script> 