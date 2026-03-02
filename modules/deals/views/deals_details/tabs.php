<?php
if (!empty($global)) {
    echo $global;
}
?>

<div class="row tw-mt-7">
 
    <div class="col-sm-3">
        <ul class="nav navbar-pills navbar-pills-flat nav-tabs nav-stacked">
            <?php

            if (!empty($all_tabs)) {
                foreach ($all_tabs as $key => $v_tab) {
                    ?>
                    <li class="<?php
                    if ($active == $key) {
                        echo 'active';
                    }
                    ?>">
                        <a href="<?= base_url($v_tab['url']) ?>">
                            <?php if (!empty($v_tab['icon'])) { ?>
                                <i class="<?= $v_tab['icon'] ?> menu-icon"></i>
                            <?php } ?>
                            <?= _l($v_tab['name']) ?>
                            <strong class="pull-right">
                                <?php
                                if (!empty($v_tab['count'])) {
                                    echo '<span class="badge">' . $v_tab['count'] . '</span>';
                                }
                                ?>
                            </strong>
                        </a>
                    </li>
                    <?php
                }
            }
            ?>

        </ul>
    </div>

    <div class="col-sm-9 ">
        <div class="tab-content" style="border: 0;padding:0;">
            <!-- Task Details tab Starts -->
            <div class="panel_s">
                <div class="panel-body">
                    <?php
                    $view = tab_load_view_deals($all_tabs, $active);
                    $this->load->view($view);
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>