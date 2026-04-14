/**
 * AWB Starter — Pattern Import / Export UI
 *
 * Handles:
 *   Export (Step 3) — builds a direct-navigation download URL from the
 *                     pattern name stored in data-pattern on each card button.
 *
 *   Import (Step 5) — multipart POST upload, four UI states (idle / uploading
 *                     / success / error), inline collision confirmation dialog.
 *
 * Depends on:
 *   window.ajaxurl      — set by WordPress on all admin screens
 *   window.awbPatternIO — localised via wp_localize_script
 *     .nonce            — nonce for awb_export_pattern (GET)
 *     .importNonce      — nonce for awb_import_pattern (POST)
 *     .i18n             — all user-facing strings
 *
 * @package AWB_Starter
 * @since   2.3.0
 */
(function () {
  "use strict";

  document.addEventListener("DOMContentLoaded", function () {
    initExport();
    initImport();
    initDuplicate();
  });

  /* ── Shared config ─────────────────────────────────────────────────────── */

  function cfg() {
    return window.awbPatternIO || {};
  }

  function i18n(key, fallback) {
    var strings = cfg().i18n || {};
    return strings[key] || fallback || "";
  }

  /* =========================================================================
     EXPORT
     ========================================================================= */

  function initExport() {
    // Event delegation — covers all current and future export buttons.
    document.addEventListener("click", function (e) {
      var btn = e.target.closest(".awb-export-pattern");
      if (!btn) return;
      handleExportClick(btn);
    });
  }

  /**
   * Build the export download URL and trigger a direct browser navigation.
   * window.location.href is intentional — fetch() cannot trigger a Save As
   * dialog for binary streams. The browser navigates to the endpoint, receives
   * Content-Disposition: attachment, and saves the file without leaving the page.
   *
   * @param {HTMLButtonElement} btn
   */
  function handleExportClick(btn) {
    var patternName = btn.dataset.pattern || "";
    if (!patternName) return;

    var url =
      window.ajaxurl +
      "?action=awb_export_pattern" +
      "&pattern=" +
      encodeURIComponent(patternName) +
      "&nonce=" +
      encodeURIComponent(cfg().nonce || "");

    // Visual feedback — cannot detect download completion via window.location,
    // so a fixed 2.5 s reset is the standard approach (WC, WP core do the same).
    var original = btn.textContent;
    btn.disabled = true;
    btn.textContent = i18n("exporting", "Exporting\u2026");
    btn.classList.add("awb-export-pattern--busy");

    window.location.href = url;

    setTimeout(function () {
      btn.disabled = false;
      btn.textContent = original;
      btn.classList.remove("awb-export-pattern--busy");
    }, 2500);
  }

  /* =========================================================================
     IMPORT
     =========================================================================

     UI states
     ---------
     idle       File input empty or cleared. Import button disabled.
     uploading  fetch() in flight. Button shows "Importing…", disabled.
                File input disabled. Collision dialog hidden.
     success    Green status bar. Form reset to idle. Reload notice shown.
     error      Red status bar. Form re-enabled so user can try again.
     collision  Inline dialog shown listing files that would be overwritten.
                Import button re-enabled so user can cancel or confirm.

     The selected file is kept in the input across the collision round-trip
     so the confirmed re-submit sends the same file with force=1.
  ========================================================================= */

  function initImport() {
    var fileInput = document.getElementById("awb-import-zip");
    var importBtn = document.getElementById("awb-import-btn");
    var statusEl = document.getElementById("awb-import-status");
    var collisionEl = document.getElementById("awb-import-collision");
    var overwriteBtn = document.getElementById("awb-import-overwrite");
    var cancelBtn = document.getElementById("awb-import-cancel");

    // All elements are optional — import card only exists on the Patterns tab.
    if (!fileInput || !importBtn) return;

    // Enable button only when a file is chosen.
    fileInput.addEventListener("change", function () {
      importBtn.disabled = !fileInput.files.length;
      hideStatus(statusEl);
      hideCollision(collisionEl);
    });

    // Normal import (force = false).
    importBtn.addEventListener("click", function () {
      if (!fileInput.files.length) {
        showStatus(
          statusEl,
          "error",
          i18n("noFile", "Please select a ZIP file first."),
        );
        return;
      }
      doImport(fileInput, importBtn, statusEl, collisionEl, false);
    });

    // Confirmed overwrite after collision.
    if (overwriteBtn) {
      overwriteBtn.addEventListener("click", function () {
        doImport(fileInput, importBtn, statusEl, collisionEl, true);
      });
    }

    // Cancel collision — return to idle with file still selected.
    if (cancelBtn) {
      cancelBtn.addEventListener("click", function () {
        hideCollision(collisionEl);
        hideStatus(statusEl);
        importBtn.disabled = !fileInput.files.length;
        fileInput.disabled = false;
      });
    }
  }

  /**
   * Build FormData and POST to the import AJAX endpoint.
   *
   * @param {HTMLInputElement}  fileInput
   * @param {HTMLButtonElement} importBtn
   * @param {HTMLElement}       statusEl
   * @param {HTMLElement}       collisionEl
   * @param {boolean}           force  true = confirmed overwrite
   */
  function doImport(fileInput, importBtn, statusEl, collisionEl, force) {
    var file = fileInput.files[0];
    if (!file) return;

    // ── Uploading state ───────────────────────────────────────────────────
    hideStatus(statusEl);
    hideCollision(collisionEl);
    importBtn.disabled = true;
    importBtn.textContent = i18n("importing", "Importing\u2026");
    fileInput.disabled = true;

    var fd = new FormData();
    fd.append("action", "awb_import_pattern");
    fd.append("nonce", cfg().importNonce || "");
    fd.append("awb_pattern_zip", file);
    fd.append("force", force ? "1" : "0");

    fetch(window.ajaxurl, { method: "POST", body: fd })
      .then(function (response) {
        if (!response.ok) {
          throw new Error("HTTP " + response.status);
        }
        return response.json();
      })
      .then(function (data) {
        handleImportResponse(data, fileInput, importBtn, statusEl, collisionEl);
      })
      .catch(function () {
        restoreImportIdle(fileInput, importBtn);
        showStatus(
          statusEl,
          "error",
          i18n("networkError", "Network error. Please try again."),
        );
      });
  }

  /**
   * Route the AJAX response to the correct UI state.
   *
   * @param {Object}            data
   * @param {HTMLInputElement}  fileInput
   * @param {HTMLButtonElement} importBtn
   * @param {HTMLElement}       statusEl
   * @param {HTMLElement}       collisionEl
   */
  function handleImportResponse(
    data,
    fileInput,
    importBtn,
    statusEl,
    collisionEl,
  ) {
    // ── Success ───────────────────────────────────────────────────────────
    if (data.success) {
      var msg =
        (data.data && data.data.message
          ? data.data.message
          : i18n("importSuccess", "Pattern imported successfully.")) +
        " " +
        i18n("reloadNotice", "Reload the page to see it in the library.");

      showStatus(statusEl, "success", msg);
      resetImportForm(fileInput, importBtn);
      return;
    }

    var payload = data.data || {};

    // ── Collision ─────────────────────────────────────────────────────────
    if (payload.code === "collision") {
      restoreImportIdle(fileInput, importBtn);
      showCollision(collisionEl, payload);
      return;
    }

    // ── Error ─────────────────────────────────────────────────────────────
    restoreImportIdle(fileInput, importBtn);
    showStatus(
      statusEl,
      "error",
      payload.message || i18n("unknownError", "An unknown error occurred."),
    );
  }

  /* ── State helpers ─────────────────────────────────────────────────────── */

  /**
   * Re-enable controls after a non-fatal response (error or cancelled collision).
   * Keeps the selected file so the user can retry without re-choosing.
   */
  function restoreImportIdle(fileInput, importBtn) {
    fileInput.disabled = false;
    importBtn.disabled = !fileInput.files.length;
    importBtn.textContent = i18n("import", "Import");
  }

  /**
   * Clear the file input and disable the button — called after a successful import.
   */
  function resetImportForm(fileInput, importBtn) {
    fileInput.value = "";
    fileInput.disabled = false;
    importBtn.disabled = true;
    importBtn.textContent = i18n("import", "Import");
  }

  /* ── Status bar ────────────────────────────────────────────────────────── */

  /**
   * @param {HTMLElement}       el
   * @param {'success'|'error'} type
   * @param {string}            message
   */
  function showStatus(el, type, message) {
    if (!el) return;
    el.textContent = message;
    el.className = "awb-import-status awb-import-status--" + type;
    el.hidden = false;
  }

  function hideStatus(el) {
    if (!el) return;
    el.hidden = true;
    el.textContent = "";
    el.className = "awb-import-status";
  }

  /* ── Collision dialog ──────────────────────────────────────────────────── */

  /**
   * Populate and reveal the inline collision dialog.
   *
   * @param {HTMLElement} el       The collision container.
   * @param {Object}      payload  data.data from the collision response.
   *   payload.title  {string}    Pattern title
   *   payload.files  {string[]}  Relative paths that would be overwritten
   */
  function showCollision(el, payload) {
    if (!el) return;

    var msgEl = el.querySelector(".awb-import-collision__msg");
    var listEl = el.querySelector(".awb-import-collision__files");

    if (msgEl) {
      msgEl.textContent = i18n(
        "overwritePrompt",
        "The following files already exist and will be overwritten:",
      );
    }

    if (listEl) {
      listEl.innerHTML = "";
      var files = Array.isArray(payload.files) ? payload.files : [];
      files.forEach(function (filePath) {
        var li = document.createElement("li");
        li.textContent = filePath; // server-supplied relative path, display only
        listEl.appendChild(li);
      });
    }

    el.hidden = false;
  }

  function hideCollision(el) {
    if (!el) return;
    el.hidden = true;
  }

  /* =========================================================================
     DUPLICATE / CLONE
     =========================================================================

     UI behaviour
     ------------
     - Click "Clone" on a pattern card.
     - Button label → "Cloning…", disabled.
     - fetch() POST to awb_duplicate_pattern.
     - Success: button gets a green tick + success tooltip for 3 s, then resets.
       A small inline status message appears below the card footer with the
       new pattern name and a reload prompt.
     - Error: button resets, browser alert with the error message.

     Inline status is appended directly after the card footer so feedback is
     co-located with the action — no global status bar needed for duplication.
  ========================================================================= */

  function initDuplicate() {
    // Event delegation — covers all current and future Clone buttons.
    document.addEventListener("click", function (e) {
      var btn = e.target.closest(".awb-duplicate-pattern");
      if (!btn) return;
      handleDuplicateClick(btn);
    });
  }

  /**
   * POST to awb_duplicate_pattern and handle the response.
   *
   * @param {HTMLButtonElement} btn  The clicked Clone button.
   */
  function handleDuplicateClick(btn) {
    var patternName = btn.dataset.pattern || "";
    if (!patternName) return;

    // ── Busy state ────────────────────────────────────────────────────────
    var originalLabel = btn.textContent;
    btn.disabled = true;
    btn.textContent = i18n("duplicating", "Cloning\u2026");
    btn.classList.add("awb-duplicate-pattern--busy");

    // Remove any previous inline status on this card.
    var card = btn.closest(".awb-pattern-card");
    var existingStatus = card && card.querySelector(".awb-duplicate-status");
    if (existingStatus) {
      existingStatus.remove();
    }

    var fd = new FormData();
    fd.append("action", "awb_duplicate_pattern");
    fd.append("nonce", cfg().duplicateNonce || "");
    fd.append("pattern", patternName);

    fetch(window.ajaxurl, { method: "POST", body: fd })
      .then(function (response) {
        if (!response.ok) {
          throw new Error("HTTP " + response.status);
        }
        return response.json();
      })
      .then(function (data) {
        if (data.success) {
          showDuplicateSuccess(btn, card, originalLabel, data.data);
        } else {
          showDuplicateError(
            btn,
            originalLabel,
            (data.data && data.data.message) ||
              i18n("duplicateError", "Could not clone pattern."),
          );
        }
      })
      .catch(function () {
        showDuplicateError(
          btn,
          originalLabel,
          i18n("networkError", "Network error. Please try again."),
        );
      });
  }

  /**
   * Show success state: button gets a brief "Cloned ✓" label, then resets.
   * An inline message below the card footer shows the new title + reload notice.
   *
   * @param {HTMLButtonElement} btn
   * @param {HTMLElement|null}  card
   * @param {string}            originalLabel
   * @param {Object}            data    Response data from the server.
   *   data.new_title  {string}
   *   data.message    {string}
   */
  function showDuplicateSuccess(btn, card, originalLabel, data) {
    // Brief "Cloned ✓" label on the button, then reset.
    btn.textContent = "Cloned \u2713";
    btn.classList.remove("awb-duplicate-pattern--busy");
    btn.classList.add("awb-duplicate-pattern--done");

    setTimeout(function () {
      btn.disabled = false;
      btn.textContent = originalLabel;
      btn.classList.remove("awb-duplicate-pattern--done");
    }, 3000);

    // Inline status message below the card footer.
    if (card) {
      var status = document.createElement("p");
      status.className = "awb-duplicate-status awb-duplicate-status--success";
      status.textContent =
        data.message ||
        i18n("reloadNotice", "Reload the page to see it in the library.");
      card.appendChild(status);
    }
  }

  /**
   * Show error state: reset the button and display the error message.
   *
   * @param {HTMLButtonElement} btn
   * @param {string}            originalLabel
   * @param {string}            message
   */
  function showDuplicateError(btn, originalLabel, message) {
    btn.disabled = false;
    btn.textContent = originalLabel;
    btn.classList.remove("awb-duplicate-pattern--busy");

    // Use a browser alert for errors — duplication errors are rare and
    // an alert is less disruptive than a persistent card-level message.
    window.alert(message);
  }
})();
