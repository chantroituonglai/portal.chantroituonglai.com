(function ($) {
  "use strict";

  var autosaveNamespace = ".sales-draft-autosave";
  var debounceMs = 5000;

  function status($form, message, state) {
    var $status = $form.find(".sales-draft-autosave-status");
    if (!$status.length) {
      $status = $(".sales-draft-autosave-status").first();
    }
    $status
      .removeClass("text-success text-danger text-muted")
      .addClass(state || "text-muted")
      .text(message || "");
  }

  function formPayload($form) {
    if (typeof calculate_total === "function") {
      calculate_total();
    }

    var data = $form.serializeArray();
    data.push({ name: "autosave_draft", value: "1" });
    return $.param(data);
  }

  function attachAutosave($form) {
    var url = $form.data("autosave-url");
    if (!url) {
      return;
    }

    var dirty = false;
    var saving = false;
    var timer = null;
    var submitted = false;
    var lastPayload = "";

    function markDirty() {
      dirty = true;
      status($form, "Unsaved changes", "text-muted");
      clearTimeout(timer);
      timer = setTimeout(save, debounceMs);
    }

    function save(options) {
      options = options || {};

      if (submitted || (!dirty && !options.force)) {
        return;
      }

      var payload = formPayload($form);
      if (!options.force && payload === lastPayload) {
        dirty = false;
        return;
      }

      lastPayload = payload;

      if (options.beacon && navigator.sendBeacon) {
        var blob = new Blob([payload], {
          type: "application/x-www-form-urlencoded; charset=UTF-8",
        });
        navigator.sendBeacon(url, blob);
        dirty = false;
        return;
      }

      saving = true;
      status($form, "Saving...", "text-muted");

      $.ajax({
        url: url,
        type: "POST",
        data: payload,
        dataType: "json",
      })
        .done(function (response) {
          if (response && response.success) {
            dirty = false;
            status($form, "Saved at " + response.saved_at, "text-success");
          } else {
            status($form, "Autosave failed", "text-danger");
          }
        })
        .fail(function () {
          status($form, "Autosave failed", "text-danger");
        })
        .always(function () {
          saving = false;
        });
    }

    $form.on(
      "input" + autosaveNamespace + " change" + autosaveNamespace,
      "input, select, textarea",
      function () {
        if ($(this).is(":file") || $(this).attr("name") === "autosave_draft") {
          return;
        }
        markDirty();
      }
    );

    $form.on("submit" + autosaveNamespace, function () {
      submitted = true;
      clearTimeout(timer);
    });

    $form.find(".transaction-submit").on("click" + autosaveNamespace, function () {
      submitted = true;
      clearTimeout(timer);
    });

    $(window).on("beforeunload" + autosaveNamespace, function (event) {
      if (submitted) {
        return undefined;
      }

      if (dirty) {
        save({ force: true, beacon: true });
      }

      if (dirty || saving) {
        event.preventDefault();
        event.returnValue = "";
        return "";
      }

      return undefined;
    });
  }

  $(function () {
    $("form[data-autosave-enabled='1']").each(function () {
      attachAutosave($(this));
    });
  });
})(jQuery);
