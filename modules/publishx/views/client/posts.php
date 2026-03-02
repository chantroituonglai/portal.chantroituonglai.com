<style>
    .card-img-top {
        height: 200px;
        object-fit: cover;
    }
</style>
<div class="panel_s">
    <div class="panel-body">

        <div class="row mb-9">
            <div class="col">
                <button type="button" class="btn btn-primary category-btn active" data-category="all">All</button>
                <?php
                foreach ($categories_list as $category) {
                    ?>
                    <button type="button" class="btn btn-primary category-btn"
                            data-category="<?php echo $category['id']; ?>"><?php echo $category['category_name']; ?></button>
                    <?php
                }
                ?>
            </div>
        </div>
        <br>
        <br>
        <div class="row">
            <?php
            foreach ($posts_list as $post) {
                ?>
                <div class="col-md-4 col-sm-6 mb-4 project-item <?php echo $post['category_id']; ?>">
                    <div class="card">
                        <a href="<?php echo site_url('publishx/blog/post/' . $post['post_slug']) ?>">
                            <img style="width: 100%;max-height: 35rem"
                                 src="<?php echo substr(module_dir_url('publishx/uploads/posts/' . $post['id'] . '/' . $post['featured_image']), 0, -1); ?>"
                                 alt="Image 1" class="card-img-top">
                        </a>
                        <div class="card-body">
                            <h3 class="card-title"><a
                                        href="<?php echo site_url('publishx/blog/post/' . $post['post_slug']) ?>"><?php echo $post['post_title']; ?></a>
                            </h3>
                        </div>
                    </div>
                </div>
                <?php
            }
            ?>
        </div>
    </div>
</div>
<script>
    $(document).ready(function () {
        $('.category-btn').click(function () {
            var category = $(this).data('category');
            if (category === 'all') {
                $('.project-item').show();
            } else {
                $('.project-item').hide();
                $('.project-item.' + category).show();
            }
            $('.category-btn').removeClass('active');
            $(this).addClass('active');
        });
    });
</script>
