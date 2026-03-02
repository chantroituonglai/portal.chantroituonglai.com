
$(document).on('click', '.copy-text', function() {
    var text = jQuery(this).data("copy");
    navigator.clipboard.writeText(text).then(function() {
        alert_float("success", "' . _l('copied_to_clipboard') . '");
    });
});
