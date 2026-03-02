<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<style>
    .theme-cards {
        margin: 10px 0 10px 0;
    }

    .theme-cards .card {
        height: 100%;
        border: 1px solid #e5e5e5;
        border-radius: 5px;
    }

    .theme-cards .img-wrapper {
        padding-top: 56.25%; /* 16:9 aspect ratio (height / width) */
        position: relative;
        overflow: hidden;
    }

    .theme-cards .img-wrapper img {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
    }

    .theme-cards .card-body {
        padding: 15px;
    }

    .theme-cards .card-title {
        font-size: 18px;
        margin-bottom: 10px;
    }
</style>
<div id="wrapper">
    <div class="content">
        <h2><?php echo _l('publishx') . ' ' . _l('publishx_themes'); ?></h2>
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body panel-table-full">
                        <div class="row  mt-2 mb-2">
                            <?php
                            foreach (publishx_supported_blog_themes() as $theme) {
                                ?>
                                <div class="col-md-4 theme-cards">
                                    <div class="card">
                                        <div class="img-wrapper">
                                            <img class="card-img-top img-fluid" src="<?php echo $theme['thumbnail']; ?>"
                                                 alt="Theme Image">
                                        </div>
                                        <div class="card-body">
                                            <h5 class="card-title"><?php echo $theme['title']; ?></h5>
                                            <?php
                                            if (get_option('publishx_selected_blog_theme') == $theme['id']) {
                                                ?>
                                                <a href="#"
                                                   class="btn btn-success"><?php echo _l('publishx_activated'); ?></a>
                                                <a target="_blank" href="<?php echo site_url('publishx/blog'); ?>"
                                                   class="btn btn-info">
                                                    <i class="fa-regular fa-eye fa-lg"></i>
                                                </a>
                                                <?php
                                            } else {
                                                ?>
                                                <a href="<?php echo admin_url('publishx/activate_theme/' . $theme['id']) ?>"
                                                   class="btn btn-primary"><?php echo _l('publishx_activate'); ?></a>
                                                <?php
                                            }
                                            ?>
                                        </div>
                                    </div>
                                </div>
                                <?php
                            }
                            ?>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
</body>

</html>