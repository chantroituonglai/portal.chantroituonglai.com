<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <link rel="apple-touch-icon" sizes="76x76"
          href="<?php echo module_dir_url('publishx', 'views/themes/clean_blog/css/styles.css') ?>">
    <link rel="icon" type="image/png"
          href="<?php echo module_dir_url('publishx', 'views/themes/clean_blog/css/styles.css') ?>">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"/>
    <title><?php echo $post->meta_title ?? $post->post_title; ?></title>
    <meta name="description" content="<?php echo $post->meta_description ?? $post->short_content; ?>">
    <meta name="keywords" content="<?php echo $post->meta_keywords; ?>"/>
    <meta content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0, shrink-to-fit=no'
          name='viewport'/>
    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css?family=Playfair+Display:400,700|Source+Sans+Pro:400,700"
          rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.3.1/css/all.css"
          integrity="sha384-mzrmE5qonljUremFsqc01SB46JvROS7bZs3IO2EmfFsd15uHvIt+Y8vEf7N7fWAU" crossorigin="anonymous">
    <!-- Main CSS -->
    <link href="<?php echo module_dir_url('publishx', 'views/themes/mundana/assets/css/main.css') ?>" rel="stylesheet"/>
</head>
<body>
<!--------------------------------------
NAVBAR
--------------------------------------->
<nav class="topnav navbar navbar-expand-lg navbar-light bg-white fixed-top">
    <div class="container">
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
               href="<?php echo site_url('publishx/blog') ?>"><strong><?php echo get_option('publishx_blog_title'); ?></strong></a>
            <?php
        }
        ?>
        <button class="navbar-toggler collapsed" type="button" data-toggle="collapse" data-target="#navbarColor02"
                aria-controls="navbarColor02" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="navbar-collapse collapse" id="navbarColor02" style="">
            <ul class="navbar-nav mr-auto d-flex align-items-center">
                <?php
                foreach ($post_categories as $category) {
                    ?>
                    <li class="nav-item">
                        <a class="nav-link"
                           href="<?php echo site_url('publishx/blog/?category=' . $category['id']) ?>"><?php echo $category['category_name'] ?></a>
                    </li>
                    <?php
                }
                ?>
            </ul>
        </div>
    </div>
</nav>
<!-- End Navbar -->

<!--------------------------------------
HEADER
--------------------------------------->
<div class="container">
    <div class="jumbotron jumbotron-fluid mb-3 pl-0 pt-0 pb-0 bg-white position-relative">
        <div class="h-100 tofront">
            <div class="row justify-content-between">
                <div class="col-md-6 pt-6 pb-6 pr-6 align-self-center">
                    <h1 class="display-4 secondfont mb-3 font-weight-bold"><?php echo $post->post_title; ?></h1>
                    <p class="mb-3"><?php echo $post->short_content; ?></p>
                    <div class="d-flex align-items-center">
                        <?php
                        if (get_option('publishx_display_on_post_author') == '1') {
                            ?>
                            <small class="ml-2"><?php echo get_staff_full_name($post->author_id); ?> <span
                                        class="text-muted d-block"><?php echo $post->created_at; ?></span>
                            </small>
                            <?php
                        } else {
                            ?>
                            <small class="ml-2"><span class="text-muted d-block"><?php echo $post->created_at; ?></span>
                            </small>
                            <?php
                        }
                        ?>
                    </div>
                </div>
                <div class="col-md-6 pr-0">
                    <img src="<?php echo substr(module_dir_url('publishx/uploads/posts/' . $post->id . '/' . $post->featured_image), 0, -1); ?>">
                </div>
            </div>
        </div>
    </div>
</div>
<!-- End Header -->

<!--------------------------------------
MAIN
--------------------------------------->
<div class="container pt-4 pb-4">
    <div class="row justify-content-center">
        <div class="col-lg-2 pr-4 mb-4 col-md-12">
        </div>
        <div class="col-md-12 col-lg-8">
            <article class="article-post">
                <?php echo $post->full_content; ?>
            </article>
        </div>
    </div>
</div>
<!-- End Main -->

<!--------------------------------------
FOOTER
--------------------------------------->
<div class="container mt-5">
    <footer class="bg-white border-top p-3 text-muted small">
        <div class="row align-items-center justify-content-center">
            <div>
                <span class="navbar-brand mr-2"><strong><?php echo get_option('publishx_blog_title'); ?></strong></span>
                Copyright &copy;
                <script>document.write(new Date().getFullYear())</script>
                . All rights reserved.
            </div>
        </div>
    </footer>
</div>
<!-- End Footer -->
<?php echo get_option('publishx_google_analytics_code'); ?>
<!--------------------------------------
JAVASCRIPTS
--------------------------------------->
<script src="<?php echo module_dir_url('publishx', 'views/themes/mundana/assets/js/vendor/jquery.min.js') ?>"
        type="text/javascript"></script>
<script src="<?php echo module_dir_url('publishx', 'views/themes/mundana/assets/js/vendor/popper.min.js') ?>"
        type="text/javascript"></script>
<script src="<?php echo module_dir_url('publishx', 'views/themes/mundana/assets/js/vendor/bootstrap.min.js') ?>"
        type="text/javascript"></script>
<script src="<?php echo module_dir_url('publishx', 'views/themes/mundana/assets/js/functions.js') ?>"
        type="text/javascript"></script>
</body>
</html>