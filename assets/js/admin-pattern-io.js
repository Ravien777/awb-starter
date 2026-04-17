/**
 * AWB Starter — Pattern Import / Export / Edit / Delete UI
 *
 * @package AWB_Starter
 * @since   2.3.0
 */
(function () {
  "use strict";
  var editorInstances = {};

  document.addEventListener("DOMContentLoaded", function () {
    initExport();
    initImport();
    initDuplicate();
    initEdit();
    initDelete();
  });

  /* ── Shared config ─────────────────────────────────────────────────────── */
  function cfg() {
    return window.awbPatternIO || {};
  }
  function i18n(key, fallback) {
    return (cfg().i18n || {})[key] || fallback || "";
  }

  /* =========================================================================
   EXPORT
   ========================================================================= */
  function initExport() {
    document.addEventListener("click", function (e) {
      var btn = e.target.closest(".awb-export-pattern");
      if (!btn) return;
      handleExportClick(btn);
    });
  }
  function handleExportClick(btn) {
    var patternName = btn.dataset.pattern || "";
    if (!patternName) return;
    var ajaxUrl =
      typeof ajaxurl !== "undefined" ? ajaxurl : "/wp-admin/admin-ajax.php";
    var url = new URL(ajaxUrl, window.location.origin);
    url.searchParams.set("action", "awb_export_pattern");
    url.searchParams.set("pattern", patternName);
    url.searchParams.set("nonce", cfg().nonce || "");
    var original = btn.textContent;
    btn.disabled = true;
    btn.textContent = i18n("exporting", "Exporting…");
    btn.classList.add("awb-export-pattern--busy");
    window.location.href = url.toString();
    setTimeout(function () {
      btn.disabled = false;
      btn.textContent = original;
      btn.classList.remove("awb-export-pattern--busy");
    }, 2500);
  }

  /* =========================================================================
   IMPORT
   ========================================================================= */
  function initImport() {
    var fileInput = document.getElementById("awb-import-zip");
    var importBtn = document.getElementById("awb-import-btn");
    var statusEl = document.getElementById("awb-import-status");
    var collisionEl = document.getElementById("awb-import-collision");
    var overwriteBtn = document.getElementById("awb-import-overwrite");
    var cancelBtn = document.getElementById("awb-import-cancel");
    if (!fileInput || !importBtn) return;
    fileInput.addEventListener("change", function () {
      importBtn.disabled = !fileInput.files.length;
      hideStatus(statusEl);
      hideCollision(collisionEl);
    });
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
    if (overwriteBtn)
      overwriteBtn.addEventListener("click", function () {
        doImport(fileInput, importBtn, statusEl, collisionEl, true);
      });
    if (cancelBtn)
      cancelBtn.addEventListener("click", function () {
        hideCollision(collisionEl);
        hideStatus(statusEl);
        importBtn.disabled = !fileInput.files.length;
        fileInput.disabled = false;
      });
  }
  function doImport(fileInput, importBtn, statusEl, collisionEl, force) {
    var file = fileInput.files[0];
    if (!file) return;
    hideStatus(statusEl);
    hideCollision(collisionEl);
    importBtn.disabled = true;
    importBtn.textContent = i18n("importing", "Importing…");
    fileInput.disabled = true;
    var fd = new FormData();
    fd.append("action", "awb_import_pattern");
    fd.append("nonce", cfg().importNonce || "");
    fd.append("awb_pattern_zip", file);
    fd.append("force", force ? "1" : "0");
    var ajaxUrl =
      typeof ajaxurl !== "undefined" ? ajaxurl : "/wp-admin/admin-ajax.php";
    fetch(ajaxUrl, { method: "POST", body: fd })
      .then(function (response) {
        if (!response.ok) throw new Error("HTTP " + response.status);
        return response.json();
      })
      .then(function (data) {
        handleImportResponse(data, fileInput, importBtn, statusEl, collisionEl);
      })
      .catch(function () {
        restoreImportIdle(fileInput, importBtn);
        showStatus(statusEl, "error", i18n("networkError", "Network error."));
      });
  }
  function handleImportResponse(
    data,
    fileInput,
    importBtn,
    statusEl,
    collisionEl,
  ) {
    if (data.success) {
      showStatus(
        statusEl,
        "success",
        (data.data?.message ||
          i18n("importSuccess", "Pattern imported successfully.")) +
          " " +
          i18n("reloadNotice", "Reload to see it."),
      );
      resetImportForm(fileInput, importBtn);
      return;
    }
    var payload = data.data || {};
    if (payload.code === "collision") {
      restoreImportIdle(fileInput, importBtn);
      showCollision(collisionEl, payload);
      return;
    }
    restoreImportIdle(fileInput, importBtn);
    showStatus(
      statusEl,
      "error",
      payload.message || i18n("unknownError", "An unknown error occurred."),
    );
  }
  function restoreImportIdle(fileInput, importBtn) {
    fileInput.disabled = false;
    importBtn.disabled = !fileInput.files.length;
    importBtn.textContent = i18n("import", "Import");
  }
  function resetImportForm(fileInput, importBtn) {
    fileInput.value = "";
    fileInput.disabled = false;
    importBtn.disabled = true;
    importBtn.textContent = i18n("import", "Import");
  }
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
  function showCollision(el, payload) {
    if (!el) return;
    var msgEl = el.querySelector(".awb-import-collision__msg");
    var listEl = el.querySelector(".awb-import-collision__files");
    if (msgEl)
      msgEl.textContent = i18n(
        "overwritePrompt",
        "The following files already exist and will be overwritten:",
      );
    if (listEl) {
      listEl.innerHTML = "";
      (Array.isArray(payload.files) ? payload.files : []).forEach(function (f) {
        var li = document.createElement("li");
        li.textContent = f;
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
   ========================================================================= */
  function initDuplicate() {
    document.addEventListener("click", function (e) {
      var btn = e.target.closest(".awb-duplicate-pattern");
      if (!btn) return;
      handleDuplicateClick(btn);
    });
  }
  function handleDuplicateClick(btn) {
    var patternName = btn.dataset.pattern || "";
    if (!patternName) return;
    var originalLabel = btn.textContent;
    btn.disabled = true;
    btn.textContent = i18n("duplicating", "Cloning…");
    btn.classList.add("awb-duplicate-pattern--busy");
    var card = btn.closest(".awb-pattern-card");
    var existing = card?.querySelector(".awb-duplicate-status");
    if (existing) existing.remove();
    var fd = new FormData();
    fd.append("action", "awb_duplicate_pattern");
    fd.append("nonce", cfg().duplicateNonce || "");
    fd.append("pattern", patternName);
    var ajaxUrl =
      typeof ajaxurl !== "undefined" ? ajaxurl : "/wp-admin/admin-ajax.php";
    fetch(ajaxUrl, { method: "POST", body: fd })
      .then(function (r) {
        if (!r.ok) throw new Error("HTTP");
        return r.json();
      })
      .then(function (data) {
        data.success
          ? showDuplicateSuccess(btn, card, originalLabel, data.data)
          : showDuplicateError(
              btn,
              originalLabel,
              data.data?.message || i18n("duplicateError", "Could not clone."),
            );
      })
      .catch(function () {
        showDuplicateError(
          btn,
          originalLabel,
          i18n("networkError", "Network error."),
        );
      });
  }
  function showDuplicateSuccess(btn, card, originalLabel, data) {
    btn.textContent = "Cloned ✓";
    btn.classList.remove("awb-duplicate-pattern--busy");
    btn.classList.add("awb-duplicate-pattern--done");
    setTimeout(function () {
      btn.disabled = false;
      btn.textContent = originalLabel;
      btn.classList.remove("awb-duplicate-pattern--done");
    }, 3000);
    if (card) {
      var s = document.createElement("p");
      s.className = "awb-duplicate-status awb-duplicate-status--success";
      s.textContent = data.message || i18n("reloadNotice", "Reload to see it.");
      card.appendChild(s);
    }
  }
  function showDuplicateError(btn, originalLabel, msg) {
    btn.disabled = false;
    btn.textContent = originalLabel;
    btn.classList.remove("awb-duplicate-pattern--busy");
    window.alert(msg);
  }

  /* =========================================================================
   EDIT PATTERN
   ========================================================================= */
  function initEdit() {
    document.addEventListener("click", function (e) {
      var btn = e.target.closest(".awb-edit-pattern");
      if (!btn) return;
      handleEditClick(btn);
    });
  }
  function handleEditClick(btn) {
    var patternName = btn.dataset.pattern;
    if (!patternName) return;
    var nonce = cfg().editNonce;
    if (!nonce) {
      window.alert(i18n("editNonceMissing", "Edit nonce missing."));
      return;
    }
    var originalLabel = btn.textContent;
    btn.disabled = true;
    btn.textContent = i18n("loading", "Loading…");
    var ajaxUrl =
      typeof ajaxurl !== "undefined" ? ajaxurl : "/wp-admin/admin-ajax.php";
    var url = new URL(ajaxUrl, window.location.origin);
    url.searchParams.set("action", "awb_get_pattern_source");
    url.searchParams.set("pattern", patternName);
    url.searchParams.set("nonce", nonce);
    fetch(url)
      .then(function (r) {
        return r.json();
      })
      .then(function (data) {
        btn.disabled = false;
        btn.textContent = originalLabel;
        if (data.success) {
          openEditModal(patternName, data.data.files);
        } else {
          window.alert(data.data?.message || "Could not load pattern.");
        }
      })
      .catch(function () {
        btn.disabled = false;
        btn.textContent = originalLabel;
        window.alert(i18n("networkError", "Network error."));
      });
  }
  function createEditModal() {
    var modal = document.createElement("div");
    modal.id = "awb-edit-modal";
    modal.className = "awb-modal";
    modal.setAttribute("role", "dialog");
    modal.setAttribute("aria-modal", "true");
    modal.setAttribute("aria-labelledby", "awb-edit-modal-title");
    modal.innerHTML =
      '<div class="awb-modal__backdrop"></div><div class="awb-modal__panel awb-modal__panel--large"><header class="awb-modal__header"><h2 class="awb-modal__title" id="awb-edit-modal-title">Edit Pattern</h2><button class="awb-modal__close" aria-label="Close">✕</button></header><div class="awb-edit-modal__tabs" role="tablist"></div><div class="awb-modal__body"></div><footer class="awb-modal__footer"><button class="awb-btn awb-btn--outline awb-edit-modal__cancel">Cancel</button><button class="awb-btn awb-btn--primary awb-edit-modal__save">Save Changes</button></footer></div>';
    return modal;
  }
  function openEditModal(patternName, files) {
    var modal = document.getElementById("awb-edit-modal") || createEditModal();
    if (!document.getElementById("awb-edit-modal"))
      document.body.appendChild(modal);
    var tabsContainer = modal.querySelector(".awb-edit-modal__tabs");
    var body = modal.querySelector(".awb-modal__body");
    tabsContainer.innerHTML = "";
    body.querySelectorAll(".awb-editor-container").forEach(function (el) {
      el.remove();
    });
    for (var type in editorInstances) {
      if (editorInstances[type].toTextArea) editorInstances[type].toTextArea();
    }
    editorInstances = {};
    var fileTypes = Object.keys(files);
    if (!fileTypes.length) {
      window.alert(
        "No editable files found. Ensure your pattern declares CSS:/JS: in its header and files exist at the specified paths.",
      );
      return;
    }
    fileTypes.forEach(function (type, index) {
      var tab = document.createElement("button");
      tab.className = "awb-edit-modal__tab" + (index === 0 ? " is-active" : "");
      tab.type = "button";
      tab.textContent = files[type].label;
      tab.dataset.type = type;
      tab.addEventListener("click", function () {
        switchTab(modal, type, files[type]);
      });
      tabsContainer.appendChild(tab);
    });
    var container = document.createElement("div");
    container.className = "awb-editor-container";
    body.appendChild(container);
    switchTab(modal, fileTypes[0], files[fileTypes[0]]);
    modal.dataset.pattern = patternName;
    modal.hidden = false;
    document.body.style.overflow = "hidden";
    modal.querySelector(".awb-edit-modal__save").onclick = function () {
      var allContent = {};
      for (var t in editorInstances)
        allContent[t] = editorInstances[t].getValue
          ? editorInstances[t].getValue()
          : "";
      savePattern(modal, patternName, allContent);
    };
    var close = function () {
      modal.hidden = true;
      document.body.style.overflow = "";
      for (var type in editorInstances) {
        if (editorInstances[type].toTextArea)
          editorInstances[type].toTextArea();
      }
      editorInstances = {};
    };
    modal.querySelector(".awb-modal__close").onclick = close;
    modal.querySelector(".awb-edit-modal__cancel").onclick = close;
    modal.querySelector(".awb-modal__backdrop").onclick = close;
    var escHandler = function (e) {
      if (e.key === "Escape") {
        close();
        document.removeEventListener("keydown", escHandler);
      }
    };
    document.addEventListener("keydown", escHandler);
  }
  function switchTab(modal, type, fileInfo) {
    modal.querySelectorAll(".awb-edit-modal__tab").forEach(function (tab) {
      tab.classList.toggle("is-active", tab.dataset.type === type);
    });
    var container = modal.querySelector(".awb-editor-container");
    container.innerHTML = '<div class="awb-loading">Loading editor…</div>';
    setTimeout(function () {
      container.innerHTML = "";
      if (editorInstances[type]) {
        container.appendChild(editorInstances[type].getWrapperElement());
        editorInstances[type].refresh();
        return;
      }
      var textarea = document.createElement("textarea");
      textarea.className = "awb-edit-modal__textarea";
      textarea.value = fileInfo.content || "";
      container.appendChild(textarea);
      if (
        typeof wp !== "undefined" &&
        wp.codeEditor &&
        wp.codeEditor.defaultSettings
      ) {
        var base = wp.codeEditor.defaultSettings || {};
        var overrides = {
          mode: fileInfo.mode || "text/plain",
          lineNumbers: true,
          indentUnit: 4,
          tabSize: 4,
          lineWrapping: true,
          autoCloseBrackets: true,
          matchBrackets: true,
        };
        var editor = wp.codeEditor.initialize(textarea, {
          codemirror: Object.assign({}, base.codemirror, overrides),
        });
        editorInstances[type] = editor.codemirror;
      } else {
        editorInstances[type] = {
          getValue: function () {
            return textarea.value;
          },
          getWrapperElement: function () {
            return textarea;
          },
          refresh: function () {},
          toTextArea: function () {
            textarea.remove();
          },
        };
      }
    }, 50);
  }
  function savePattern(modal, patternName, filesContent) {
    var saveBtn = modal.querySelector(".awb-edit-modal__save");
    saveBtn.disabled = true;
    saveBtn.textContent = "Saving…";
    var formData = new FormData();
    formData.append("action", "awb_save_pattern_source");
    formData.append("nonce", cfg().editNonce);
    formData.append("pattern", patternName);
    for (var type in filesContent)
      formData.append("files[" + type + "]", filesContent[type]);
    var ajaxUrl =
      typeof ajaxurl !== "undefined" ? ajaxurl : "/wp-admin/admin-ajax.php";
    fetch(ajaxUrl, { method: "POST", body: formData })
      .then(function (r) {
        return r.json();
      })
      .then(function (data) {
        saveBtn.disabled = false;
        saveBtn.textContent = "Save Changes";
        if (data.success) {
          window.alert(i18n("saveSuccess", "Pattern files saved."));
          modal.querySelector(".awb-modal__close").click();
          location.reload();
        } else {
          window.alert(data.data?.message || "Save failed.");
        }
      })
      .catch(function () {
        saveBtn.disabled = false;
        saveBtn.textContent = "Save Changes";
        window.alert(i18n("networkError", "Network error."));
      });
  }

  /* =========================================================================
   DELETE PATTERN (New Feature)
   ========================================================================= */
  function initDelete() {
    document.addEventListener("click", function (e) {
      var btn = e.target.closest(".awb-delete-pattern");
      if (!btn) return;
      handleDeleteClick(btn);
    });
  }
  function handleDeleteClick(btn) {
    var patternName = btn.dataset.pattern || "";
    if (!patternName) return;
    if (
      !confirm(
        i18n(
          "deleteConfirm",
          "Are you sure you want to delete this pattern and its associated assets? This cannot be undone.",
        ),
      )
    ) {
      return;
    }
    var originalLabel = btn.textContent;
    btn.disabled = true;
    btn.textContent = i18n("deleting", "Deleting…");
    var fd = new FormData();
    fd.append("action", "awb_delete_pattern");
    fd.append("nonce", cfg().deleteNonce || "");
    fd.append("pattern", patternName);
    var ajaxUrl =
      typeof ajaxurl !== "undefined" ? ajaxurl : "/wp-admin/admin-ajax.php";
    fetch(ajaxUrl, { method: "POST", body: fd })
      .then(function (r) {
        return r.json();
      })
      .then(function (data) {
        btn.disabled = false;
        btn.textContent = originalLabel;
        if (data.success) {
          var card = btn.closest(".awb-pattern-card");
          if (card) card.remove();
          var badge = document.querySelector(
            ".awb-patterns__toolbar .awb-badge",
          );
          if (badge) {
            var count = parseInt(badge.textContent) - 1;
            badge.textContent = count + " pattern" + (count !== 1 ? "s" : "");
          }
        } else {
          window.alert(
            data.data?.message ||
              i18n("deleteError", "Failed to delete pattern."),
          );
        }
      })
      .catch(function () {
        btn.disabled = false;
        btn.textContent = originalLabel;
        window.alert(i18n("networkError", "Network error."));
      });
  }
})();
