<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">

            <?php echo form_open_multipart(admin_url('publishx/settings')); ?>
            <div class="col-md-12">
                <h4 class="tw-mt-0 tw-font-semibold tw-text-lg tw-text-neutral-700">
                    <?php echo $title; ?>
                </h4>
                <div class="panel_s">
                    <div class="panel-body">

                        <?php $company_logo = get_option('publishx_blog_logo'); ?>
                        <?php if ($company_logo != '') { ?>
                            <div class="row">
                                <div class="col-md-9">
                                    <img src="<?php echo substr(module_dir_url('publishx/uploads/' . $company_logo), 0, -1); ?>"
                                         class="img img-responsive">
                                </div>
                                <div class="col-md-3 text-right">
                                    <a href="<?php echo admin_url('publishx/remove_blog_logo'); ?>"
                                       class="_delete text-danger"><i class="fa fa-remove"></i></a>
                                </div>
                            </div>
                            <div class="clearfix"></div>
                        <?php } else { ?>
                            <div class="form-group">
                                <label for="company_logo"
                                       class="control-label"><?php echo _l('publishx_blog_logo'); ?></label>
                                <input type="file" name="blog_logo" class="form-control">
                            </div>
                        <?php } ?>
                        <hr/>
                        <?php $company_logo = get_option('publishx_blog_favicon_logo'); ?>
                        <?php if ($company_logo != '') { ?>
                            <div class="row">
                                <div class="col-md-9">
                                    <img src="<?php echo substr(module_dir_url('publishx/uploads/' . $company_logo), 0, -1); ?>"
                                         class="img img-responsive">
                                </div>
                                <div class="col-md-3 text-right">
                                    <a href="<?php echo admin_url('publishx/remove_blog_favicon_logo'); ?>"
                                       class="_delete text-danger"><i class="fa fa-remove"></i></a>
                                </div>
                            </div>
                            <div class="clearfix"></div>
                        <?php } else { ?>
                            <div class="form-group">
                                <label for="company_logo"
                                       class="control-label"><?php echo _l('publishx_favicon_logo'); ?></label>
                                <input type="file" name="favicon_logo" class="form-control">
                            </div>
                        <?php } ?>
                        <hr/>

                        <div class="col-md-12">
                            <?php echo render_input('settings[publishx_blog_title]', 'publishx_blog_title', get_option('publishx_blog_title')); ?>
                        </div>

                        <div class="col-md-12">
                            <?php echo render_input('settings[publishx_blog_description]', 'publishx_blog_description', get_option('publishx_blog_description')); ?>
                        </div>

                        <div class="col-md-12">
                            <?php echo render_input('settings[publishx_openai_key]', 'publishx_openai_key', get_option('publishx_openai_key'), 'password'); ?>
                        </div>

                        <div class="col-md-6">
                            <?php echo render_input('settings[publishx_posts_per_page]', 'publishx_posts_per_page', get_option('publishx_posts_per_page')); ?>
                        </div>

                        <div class="col-md-6">
                            <?php echo render_input('settings[publishx_google_analytics_code]', 'publishx_google_analytics_code', get_option('publishx_google_analytics_code')); ?>
                        </div>

                        <div class="col-md-6">
                            <?php echo render_yes_no_option('publishx_show_on_client_side', 'publishx_show_on_client_side'); ?>
                        </div>

                        <div class="col-md-6">
                            <?php echo render_yes_no_option('publishx_display_on_post_author', 'publishx_display_on_post_author'); ?>
                        </div>

                        <div class="col-md-3">
                            <?php echo render_input('settings[publishx_facebook_social_media_url]', 'publishx_facebook_social_media_url', get_option('publishx_facebook_social_media_url')); ?>
                        </div>

                        <div class="col-md-3">
                            <?php echo render_input('settings[publishx_instagram_social_media_url]', 'publishx_instagram_social_media_url', get_option('publishx_instagram_social_media_url')); ?>
                        </div>

                        <div class="col-md-3">
                            <?php echo render_input('settings[publishx_x_social_media_url]', 'publishx_x_social_media_url', get_option('publishx_x_social_media_url')); ?>
                        </div>

                        <div class="col-md-3">
                            <?php echo render_input('settings[publishx_youtube_social_media_url]', 'publishx_youtube_social_media_url', get_option('publishx_youtube_social_media_url')); ?>
                        </div>

                        <div class="btn-bottom-toolbar text-right">
                            <button type="submit" class="btn btn-primary"><?php echo _l('save'); ?></button>
                        </div>
                    </div>
                </div>
            </div>
            <?php echo form_close(); ?>
        </div>
    </div>
</div>
<?php init_tail(); ?>

