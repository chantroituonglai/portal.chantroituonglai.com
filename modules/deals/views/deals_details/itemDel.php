<div class="row" style="margin-top: 35px">
    <div class="col-xs-7">
        <p class="well well-sm mt">
            <!-- <?= $sales_info->notes ?> -->
        </p>
    </div>
    <div class="col-sm-4 pv">
        <div class="clearfix">
            <p class="pull-left"><?= _l('sub_total') ?></p>
            <p class="pull-right mr">
                <?= $sub_total ? deals_display_money($sub_total) : '0.00' ?>
            </p>
        </div>

        <?php
        $tax_total = 0;
        $total += $sub_total;
        if (!empty($all_tax_info)) {
            foreach ($all_tax_info as $t_name => $v_tax_info) {
                $tax_total += $v_tax_info;
                ?>
                <div class="clearfix">
                    <p class="pull-left"><?= $t_name ?></p>
                    <p class="pull-right mr">
                        <?= deals_display_money($v_tax_info); ?>
                    </p>
                </div>
            <?php }
        } ?>
        <?php if ($tax_total > 0) : ?>
            <div class="clearfix">
                <p class="pull-left"><?= _l('total') . ' ' . _l('tax') ?></p>
                <p class="pull-right mr">
                    <?= deals_display_money($tax_total); ?>
                </p>
            </div>
            <?php
            $total += $tax_total;
        endif ?>
        <div class="clearfix">
            <p class="pull-left"><?= _l('total') ?></p>
            <p class="pull-right mr">
                <?= deals_display_money($total, deals_default_currency()) ?>
            </p>
        </div>
    </div>
</div>