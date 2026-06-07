(function ($) {
  "use strict";

  var currentPreviewSku = {
    item_master_id: "",
    item_sku: "",
  };

  function ensurePreviewInputs() {
    var previewArea = $(".main");
    if (previewArea.length === 0) {
      return previewArea;
    }

    var target = previewArea.find("td:first");
    if (target.length === 0) {
      target = previewArea;
    }

    if (previewArea.find('input[name="item_master_id"]').length === 0) {
      target.append('<input type="hidden" name="item_master_id" value="" disabled="disabled">');
    }
    if (previewArea.find('input[name="item_sku"]').length === 0) {
      target.append('<input type="hidden" name="item_sku" value="" disabled="disabled">');
    }
    previewArea.find('input[name="item_master_id"], input[name="item_sku"]').prop("disabled", true);

    return previewArea;
  }

  function setPreviewSku(item) {
    var previewArea = ensurePreviewInputs();
    if (previewArea.length === 0) {
      return;
    }

    currentPreviewSku = {
      item_master_id: (item && (item.itemid || item.id)) || "",
      item_sku: (item && (item.sku_code || item.commodity_code)) || "",
    };

    previewArea.find('input[name="item_master_id"]').val(currentPreviewSku.item_master_id);
    previewArea.find('input[name="item_sku"]').val(currentPreviewSku.item_sku);
  }

  function getRowItemKey(row) {
    var orderName = row.find("input.order").attr("name") || "";
    var match = orderName.match(/newitems\[(\d+)\]/);
    return match ? match[1] : "";
  }

  function appendRowSku(row, data) {
    if (!row.length || !data) {
      return;
    }

    var itemKey = getRowItemKey(row);
    if (!itemKey) {
      return;
    }

    var target = row.find("td:first");
    if (target.length === 0) {
      target = row;
    }

    data.item_master_id = data.item_master_id || currentPreviewSku.item_master_id || "";
    data.item_sku = data.item_sku || currentPreviewSku.item_sku || "";

    var masterInput = row.find('input[name="newitems[' + itemKey + '][item_master_id]"]');
    if (masterInput.length === 0) {
      target.append(
        '<input type="hidden" name="newitems[' +
          itemKey +
          '][item_master_id]" value="' +
          (data.item_master_id || "") +
          '">'
      );
    } else if (!masterInput.val()) {
      masterInput.val(data.item_master_id);
    }

    var skuInput = row.find('input[name="newitems[' + itemKey + '][item_sku]"]');
    if (skuInput.length === 0) {
      target.append(
        '<input type="hidden" name="newitems[' +
          itemKey +
          '][item_sku]" value="' +
          (data.item_sku || "") +
          '">'
      );
    } else if (!skuInput.val()) {
      skuInput.val(data.item_sku);
    }
  }

  var originalGetItemPreviewValues = window.get_item_preview_values;
  if (typeof originalGetItemPreviewValues === "function") {
    window.get_item_preview_values = function () {
      ensurePreviewInputs();
      var response = originalGetItemPreviewValues.apply(this, arguments);
      response.item_master_id = $('.main input[name="item_master_id"]').val();
      response.item_sku = $('.main input[name="item_sku"]').val();
      return response;
    };
  }

  var originalClearItemPreviewValues = window.clear_item_preview_values;
  if (typeof originalClearItemPreviewValues === "function") {
    window.clear_item_preview_values = function () {
      var result = originalClearItemPreviewValues.apply(this, arguments);
      setPreviewSku({});
      return result;
    };
  }

  $(document).on("item-added-to-preview", function (event) {
    if (event.item_type === "item") {
      setPreviewSku(event.item || {});
      return;
    }

    setPreviewSku({});
  });

  $(document).on("item-added-to-table", function (event) {
    var row = $("table.items tbody tr.item").last();
    appendRowSku(row, event.data || {});
  });

  window.ensure_sales_item_sku_preview_inputs =
    window.ensure_sales_item_sku_preview_inputs || ensurePreviewInputs;
})(jQuery);
