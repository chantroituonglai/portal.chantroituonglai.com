<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<script>
    if (typeof(jQuery) != 'undefined') {
        init_item_js();
    } else {
        window.addEventListener('load', function() {
            var initItemsJsInterval = setInterval(function() {
                if (typeof(jQuery) != 'undefined') {
                    init_item_js();
                    clearInterval(initItemsJsInterval);
                }
            }, 1000);
        });
    }

    function init_item_js() {
        $("body").off('change.item_sku_manager', 'select[name="item_select"]');
        $("body").on('change.item_sku_manager', 'select[name="item_select"]', function() {
            var itemid = $(this).selectpicker('val');
            if (itemid != '') {
                add_item_to_preview(itemid);
            }
        });
    }
</script>
