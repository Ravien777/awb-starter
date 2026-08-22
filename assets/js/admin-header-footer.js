/**
 * AWB Starter — Header & Footer Switcher Admin JS
 *
 * Handles:
 *  - Show/hide the correct picker row when the "source" <select> changes.
 *  - AJAX save with nonce (nonce token injected via wp_localize_script).
 *
 * Row IDs follow the pattern:  awb-{header|footer}-row-{pattern|block}
 * Select IDs follow the pattern: awb_{header|footer}_{pattern|block}_value
 *
 * Depends on: jQuery (wp-included), awbHeaderFooter (localised via wp_localize_script)
 *
 * @package AWB_Starter
 * @since   1.0.0
 */
(function ($, cfg) {
  "use strict";

  // -----------------------------------------------------------------------
  // Source <select> → show / hide the correct picker row
  // -----------------------------------------------------------------------

  /**
   * Toggle the pattern-row and block-row for a given section.
   *
   * @param {jQuery} $select  The source-type <select> (#awb_header_type or #awb_footer_type).
   */
  function togglePickerRows($select) {
    // Derive section name: "awb_header_type" → "header", "awb_footer_type" → "footer".
    var section = $select.attr("id").replace("awb_", "").replace("_type", "");
    var chosen = $select.val();

    $("#awb-" + section + "-row-pattern").prop("hidden", chosen !== "pattern");
    $("#awb-" + section + "-row-block").prop("hidden", chosen !== "block");
  }

  // Bind on change and run once on load to reflect pre-selected saved state.
  $("#awb_header_type, #awb_footer_type")
    .on("change", function () {
      togglePickerRows($(this));
    })
    .each(function () {
      togglePickerRows($(this));
    });

  // -----------------------------------------------------------------------
  // AJAX save
  // -----------------------------------------------------------------------

  $("#awb-save-header-footer").on("click", function () {
    var $btn = $(this);
    var $status = $("#awb-header-footer-status");

    var headerType = $("#awb_header_type").val();
    var headerValue = "";

    if ("pattern" === headerType) {
      headerValue = $("#awb_header_pattern_value").val();
    } else if ("block" === headerType) {
      headerValue = $("#awb_header_block_value").val();
    }

    var footerType = $("#awb_footer_type").val();
    var footerValue = "";

    if ("pattern" === footerType) {
      footerValue = $("#awb_footer_pattern_value").val();
    } else if ("block" === footerType) {
      footerValue = $("#awb_footer_block_value").val();
    }

    $btn.prop("disabled", true);
    $status
      .text(cfg.i18n.saving)
      .removeClass("awb-status--success awb-status--error");

    $.post(cfg.ajaxUrl, {
      action: "awb_save_header_footer",
      nonce: cfg.nonce,
      header_type: headerType,
      header_value: headerValue,
      footer_type: footerType,
      footer_value: footerValue,
    })
      .done(function (response) {
        if (response.success) {
          $status.text(cfg.i18n.saved).addClass("awb-status--success");
        } else {
          $status
            .text(
              response.data && response.data.message
                ? response.data.message
                : cfg.i18n.error,
            )
            .addClass("awb-status--error");
        }
      })
      .fail(function () {
        $status.text(cfg.i18n.error).addClass("awb-status--error");
      })
      .always(function () {
        $btn.prop("disabled", false);
      });
  });
})(jQuery, window.awbHeaderFooter || {});
