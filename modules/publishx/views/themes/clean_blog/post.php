<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no"/>
    <meta name="description" content="<?php echo $post->meta_description ?? $post->short_content; ?>"/>
    <meta name="keywords" content="<?php echo $post->meta_keywords; ?>"/>
    <title><?php echo $post->meta_title ?? $post->post_title; ?></title>
    <link rel="icon" type="image/x-icon"
          href="<?php echo substr(module_dir_url('publishx/uploads/' . get_option('publishx_blog_favicon_logo')), 0, -1); ?>"/>
    <!-- Font Awesome icons (free version)-->
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <!-- Google fonts-->
    <link href="https://fonts.googleapis.com/css?family=Lora:400,700,400italic,700italic" rel="stylesheet"
          type="text/css"/>
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300italic,400italic,600italic,700italic,800italic,400,300,600,700,800"
          rel="stylesheet" type="text/css"/>
    <!-- Core theme CSS (includes Bootstrap)-->
    <link href="<?php echo module_dir_url('publishx', 'views/themes/clean_blog/css/styles.css') ?>" rel="stylesheet"/>
</head>
<body>
<!-- Navigation-->
<nav class="navbar navbar-expand-lg navbar-light" id="mainNav">
    <div class="container px-4 px-lg-5">
        <?php
        if (!empty(get_option('publishx_blog_logo'))) {
            ?>
            <a class="navbar-brand" href="<?php echo site_url('publishx/blog') ?>"><img
                        src="<?php echo substr(module_dir_url('publishx/uploads/' . get_option('publishx_blog_logo')), 0, -1); ?>"
                        class="logo-navbar"></a>
            <?php
        } else {
            ?>
            <a class="navbar-brand"
               href="<?php echo site_url('publishx/blog') ?>"><?php echo get_option('publishx_blog_title'); ?></a>
            <?php
        }
        ?>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarResponsive"
                aria-controls="navbarResponsive" aria-expanded="false" aria-label="Toggle navigation">
            Menu
            <i class="fas fa-bars"></i>
        </button>
        <div class="collapse navbar-collapse" id="navbarResponsive">
            <ul class="navbar-nav ms-auto py-4 py-lg-0">
                <?php
                foreach ($post_categories as $category) {
                    ?>
                    <li class="nav-item"><a class="nav-link px-lg-3 py-3 py-lg-4"
                                            href="<?php echo site_url('publishx/blog/?category=' . $category['id']) ?>"><?php echo $category['category_name'] ?></a>
                    </li>
                    <?php
                }
                ?>
            </ul>
        </div>
    </div>
</nav>
<!-- Page Header-->
<header class="masthead post-masthead"
        style="background-image: url('<?php echo substr(module_dir_url('publishx/uploads/posts/' . $post->id . '/' . $post->featured_image), 0, -1); ?>')">
    <div class="container position-relative px-4 px-lg-5">
        <div class="row gx-4 gx-lg-5 justify-content-center">
            <div class="col-md-10 col-lg-8 col-xl-7">
                <div class="post-heading">
                    <h1><?php echo $post->post_title; ?></h1>
                    <h2 class="subheading"><?php echo $post->short_content; ?></h2>
                    <?php
                    if (get_option('publishx_display_on_post_author') == '1') {
                        ?>
                        <span class="meta">
                            Posted by
                            <a href="#!"><?php echo get_staff_full_name($post->author_id); ?></a>
                            on <?php echo $post->created_at; ?>
                            </span>
                        <?php
                    } else {
                        ?>
                        <span class="meta">
                        Posted on
                         <?php echo $post->created_at; ?>
                        </span>
                        <?php
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</header>
<!-- Post Content-->
<article class="mb-4">
    <div class="container px-4 px-lg-5">
        <div class="row gx-4 gx-lg-5 justify-content-center">
            <div class="col-md-10 col-lg-8 col-xl-7">
                <?php echo $post->full_content; ?>
            </div>
        </div>
    </div>
</article>
<!-- Footer-->
<footer class="border-top">
    <div class="container px-4 px-lg-5">
        <div class="row gx-4 gx-lg-5 justify-content-center">
            <div class="col-md-10 col-lg-8 col-xl-7">
                <ul class="list-inline text-center">
                    <li class="list-inline-item">
                        <a href="<?php echo get_option('publishx_x_social_media_url'); ?>">
                                    <span class="fa-stack fa-lg">
                                        <i class="fas fa-circle fa-stack-2x"></i>
                                        <i class="fab fa-twitter fa-stack-1x fa-inverse"></i>
                                    </span>
                        </a>
                    </li>
                    <li class="list-inline-item">
                        <a href="<?php echo get_option('publishx_facebook_social_media_url'); ?>">
                                    <span class="fa-stack fa-lg">
                                        <i class="fas fa-circle fa-stack-2x"></i>
                                        <i class="fab fa-facebook-f fa-stack-1x fa-inverse"></i>
                                    </span>
                        </a>
                    </li>
                    <li class="list-inline-item">
                        <a href="<?php echo get_option('publishx_instagram_social_media_url'); ?>">
                                    <span class="fa-stack fa-lg">
                                        <i class="fas fa-circle fa-stack-2x"></i>
                                        <i class="fab fa-instagram fa-stack-1x fa-inverse"></i>
                                    </span>
                        </a>
                    </li>
                    <li class="list-inline-item">
                        <a href="<?php echo get_option('publishx_youtube_social_media_url'); ?>">
                                    <span class="fa-stack fa-lg">
                                        <i class="fas fa-circle fa-stack-2x"></i>
                                        <i class="fab fa-youtube fa-stack-1x fa-inverse"></i>
                                    </span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</footer>
<!-- Bootstrap core JS-->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Core theme JS-->
<script src="<?php echo module_dir_url('publishx', 'views/themes/clean_blog/js/scripts.js') ?>"></script>
<?php echo get_option('publishx_google_analytics_code'); ?>
</body>
</html>
