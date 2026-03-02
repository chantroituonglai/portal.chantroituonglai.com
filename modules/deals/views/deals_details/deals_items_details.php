<?php
$itemType = 'deals';
?>

<style type="text/css">
    .dragger {
        cursor: pointer;
    }

    .table > tbody > tr > td {
        vertical-align: initial;
    }
</style>

<div class="form-group">
    <div class="input-group">
        <div class="input-group-addon" title="<?= _l('search_product_by_name_code') ?>">
            <i class="fa fa-barcode"></i>
        </div>
        <input type="text" placeholder="<?= _l('search_product_by_name_code'); ?>" id="<?= $itemType ?>_item"
               class="form-control">
        <div class="input-group-addon" title="<?= _l('add') . ' ' . _l('manual') ?>" data-toggle="tooltip"
             data-placement="top">

            <a href="<?= base_url('admin/deals/manuallyItems/' . $id) ?>"
               data-placement="top" data-toggle="modal" data-target="#myModal">
                <i class="fa fa-plus"></i></a>
        </div>
    </div>
</div>
<div id="dealItems">
    <?php $this->load->view('deals/deals_details/dealItems') ?>
</div>


<script type="text/javascript">
    'use strict';
    let forID = 'deals';
    let pStore = forID + 'Items'
    let forItem = forID + '_item'
    $("#" + forItem).autocomplete({
        source: function (request, response) {
            $.ajax({
                url: '<?= base_url('admin/deals/itemsSuggestions') ?>',
                dataType: "json",
                data: {
                    term: request.term,
                    for: forItem,
                },
                success: function (data) {
                    response(data);
                }
            });
        },
        minLength: 1,
        autoFocus: false,
        delay: 200,
        response: function (event, ui) {
            if ($(this).val().length >= 16 && ui.content[0].item_id == 0) {
                $(this).val('');
            } else if (ui.content.length == 1 && ui.content[0].item_id != 0) {
                ui.item = ui.content[0];
                $(this).data('ui-autocomplete')._trigger('select', 'autocompleteselect', ui);
                $(this).autocomplete('close');
                $(this).val('');
            } else if (ui.content.length == 1 && ui.content[0].item_id == 0) {
                $(this).val('');
            }
        },
        select: function (event, ui) {
            event.preventDefault();
            if (ui.item.item_id !== 0) {
                var row = addItem(ui.item.row);
                if (row) {
                    $(this).val('');
                }
            } else {
                alert('<?php echo _l('no_result_found'); ?>');
                $(this).val('');
            }
        }
    });
    $("#" + forItem).bind('keypress', function (e) {
        if (e.keyCode == 13) {
            e.preventDefault();
            $(this).autocomplete("search");
        }
    });

    function addItem(data, manual = false) {
        var item_id = data.item_id;
        console.log('item_id', item_id)
        console.log('data', data)
        var deal_id = '<?= $id ?>';
        $.ajax({
            type: 'post',
            url: "<?= admin_url('deals/add_insert_items/') ?>" + deal_id,
            dataType: "json",
            data: {
                item_id: item_id,
            },
            success: function (data) {
                $('#dealItems').html(data.subview);
                $('#deals_item').val('');
            }
        });

    }

    $("body").on('click', '.itemManualy', function (e) {
        e.preventDefault();
        var form = $(this).closest('form');
        var data = form.serialize();
        $.ajax({
            type: 'post',
            url: '<?= base_url() ?>admin/deals/itemAddedManualy/',
            data: data,
            dataType: "json",
            success: function (data) {
                if (data !== null) {
                    $('#dealItems').html(data.subview);
                    form[0].reset();
                } else {
                    alert('<?php echo _l('no_result_found'); ?>');
                }
                $('#myModal_lg').modal('hide');
            }
        });
    });
    $(".deleteBtn").click(function (e) {
        var href = $(this).attr('href');
        e.preventDefault();
        // ajax delete items using href
        $.ajax({
            url: href,
            type: 'GET',
            dataType: "json",
            success: function (data) {
                if (data.type == 'success') {
                    set_alert('success', data.msg);
                    $('#dealItems').html(data.subview);
                } else {
                    alert('There was a problem with AJAX');
                }
            }
        });

    });
</script>