<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<?php
if (empty($table)) {
?>
    <?php init_tail(); ?>
<?php
}
?>
<div id="wrapper">
    <div class="content">
        <?php echo $subview ?>
    </div>
</div>

<?php
if (!empty($table)) {
?>
    <?php init_tail(); ?>

    <script type="text/javascript">
        (function() {
            "use strict";
            initDataTable('#DataTables', list);
        })(jQuery);
    </script>
<?php
}
?>

<?php $this->load->view('deals/_layout_modal'); ?>
<?php $this->load->view('deals/_layout_modal_xl'); ?>