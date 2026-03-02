<?php defined('BASEPATH') or exit('No direct script access allowed');
echo theme_head_view();
get_template_part($navigationEnabled ? 'navigation' : '');
?>

<div class="container">
    <div class="row">
        <?php get_template_part('alerts'); ?>
    </div>
</div>

<?php hooks()->do_action('customers_content_container_start'); ?>

</body>
</html>
