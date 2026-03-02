<?php
$itemType = 'deals';
$sales_info = get_deals_result('tbl_deals_items', array('deals_id' => $id));
$all_tax_info = array();
foreach ($sales_info as $item) {
    $tax_info = json_decode($item->item_tax_name);
    $tax_total_cost = 0;
    if (!empty($tax_info)) {
        foreach ($tax_info as $key => $v_tax_name) {
            $i_tax_name = explode('|', $v_tax_name);
            $taxName = $i_tax_name[0];
            $taxPercentage = $i_tax_name[1];
            $tax_total_cost = $item->total_cost / 100 * $taxPercentage;
            $all_tax_info[$taxName][] = $tax_total_cost;
        }
    }
}
if (!empty($all_tax_info)) {
    $all_tax_info = array_map('array_sum', $all_tax_info);
}
?>
<div class="table-responsive mb-lg">
    <table class="table items invoice-items-preview" >
        <thead class="bg-items">
        <tr>
            <th>#</th>
            <th><?= lang('items') ?></th>
            <th><?= lang('qty') ?></th>
            <th class="col-sm-1"><?= lang('price') ?></th>
            <th class="col-sm-2"><?= lang('tax') ?></th>
            <th class="col-sm-1"><?= lang('total') ?></th>
            <th><?= lang('action') ?></th>
        </tr>
        </thead>
        <tbody>
        <?php
        $sub_total = 0;
        $total = 0;
        if ($sales_info) {
            foreach ($sales_info as $key => $v_item) :
                $item_tax_name = json_decode($v_item->item_tax_name);
                $sub_total += $v_item->total_cost;
                ?>
                <tr class="sortable item" data-item-id="" id="<?= $v_item->items_id ?>">
                    <td class="item_no dragger pl-lg"><?= $key + 1 ?></td>
                    <td><strong class="block"><?= $v_item->item_name ?></td>
                    <td><?= $v_item->quantity . '   &nbsp' . $v_item->unit ?></td>
                    <td><?= deals_display_money($v_item->unit_cost) ?></td>
                    <td><?php
                        $tax_total_cost = array();
                        if (!empty($item_tax_name)) {
                            foreach ($item_tax_name as $v_tax_name) {
                                $i_tax_name = explode('|', $v_tax_name);
                                $tax_total_cost = $v_item->total_cost / 100 * $i_tax_name[1];
                                echo '<small class="pr-sm">' . $i_tax_name[0] . ' (' . $i_tax_name[1] . ' %)' . '</small>' . deals_display_money($tax_total_cost) . ' <br>';
                            }
                        }
                        ?></td>
                    <td><?= deals_display_money($v_item->total_cost) ?></td>
                    <td>
                        <a href="<?= base_url('admin/deals/manuallyItems/' . $id . '/' . $v_item->items_id) ?>" class="btn btn-xs btn-primary" data-placement="top" data-toggle="modal" data-target="#myModal">
                            <i class="fa fa-edit"></i></a>
                        <a href="<?= base_url('admin/deals/delete_items/' . $v_item->items_id . '/' . $id) ?>" class="btn btn-xs btn-danger">
                            <i class="fa fa-remove"></i></a>


                    </td>
                </tr>
            <?php endforeach;
        } else { ?>
            <tr>
                <td colspan="8"><?= lang('nothing_to_display') ?></td>
            </tr>
        <?php } ?>

        </tbody>
    </table>
</div>

<div id="itemDelete">
    <div class="row" style="margin-top: 35px">
        <div class="col-xs-7">

        </div>
        <div class="col-sm-4 pv">
            <div class="clearfix">
                <p class="pull-left"><?= lang('sub_total') ?></p>
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
                    <p class="pull-left"><?= lang('total') . ' ' . lang('tax') ?></p>
                    <p class="pull-right mr">
                        <?= deals_display_money($tax_total); ?>
                    </p>
                </div>
                <?php
                $total += $tax_total;
            endif ?>
            <div class="clearfix">
                <p class="pull-left"><?= lang('total') ?></p>
                <p class="pull-right mr">
                    <?= deals_display_money($total, deals_default_currency()) ?>
                </p>
            </div>
        </div>
    </div>
</div>