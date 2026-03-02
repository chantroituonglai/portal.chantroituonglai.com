<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <?php if (has_permission('publishx_posts', '', 'create')) { ?>
                    <div class="tw-mb-2 sm:tw-mb-4">
                        <a href="<?php echo admin_url('publishx/post'); ?>" class="btn btn-primary">
                            <i class="fa-regular fa-plus tw-mr-1"></i>
                            <?php echo _l('publishx_create_post'); ?>
                        </a>
                    </div>
                <?php } ?>
                <div class="panel_s">
                    <div class="panel-body panel-table-full">
                        <?php render_datatable([
                            _l('id'),
                            _l('publishx_tbl_featured_image'),
                            _l('publishx_tbl_category'),
                            _l('publishx_tbl_post_title'),
                            _l('publishx_tbl_post_short_content'),
                            _l('publishx_tbl_views'),
                            _l('publishx_tbl_language'),
                            _l('publishx_tbl_publication_date'),
                            _l('publishx_tbl_status'),
                            _l('publishx_tbl_author'),
                            _l('options'),
                        ], 'publishx-posts'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
<script>
    $(function () {
        initDataTable('.table-publishx-posts', window.location.href, [7], [7], [], [7, 'desc']);
    });
</script>
</body>

</html>