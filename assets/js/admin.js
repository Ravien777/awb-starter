/**
 * AWB Starter — Admin JavaScript
 * Handles: editor toolbar, color-token sync, live CSS output preview,
 *          pattern library search/filter/view-toggle, modal, scaffold log.
 */

(function () {
  "use strict";

  /* ── DOM ready ────────────────────────────────────────────────────────── */

  document.addEventListener("DOMContentLoaded", init);

  function init() {
    initEditorToolbar();
    initProviderSwitch(); // New: handles AI provider card visibility
    initApiTesting(); // New: handles AI key test buttons
    initColorTokenSync();
    initTokenPreview();
    initLibraryFilters();
    initViewToggle();
    initPatternActions();
    initModal();
    initBulkActions();
    initOnboarding();
    initScaffold();
    initFontDeletion();
    initAITab(); // New: handles AI Generator tab interactions
  }

  /* ── Editor toolbar (Copy / Clear buttons) ────────────────────────────── */

  function initEditorToolbar() {
    // Handle both old .awb-editor-btn and new .awb-input-btn
    document
      .querySelectorAll(".awb-editor-btn, .awb-input-btn")
      .forEach(function (btn) {
        btn.addEventListener("click", function (e) {
          e.preventDefault(); // Critical: prevents accidental form submission
          const action = btn.dataset.action;
          const targetId = btn.dataset.target;
          if (!targetId) return;
          const target = document.getElementById(targetId);
          if (!target) return;

          if (action === "toggle-visibility") {
            const isPassword = target.type === "password";
            target.type = isPassword ? "text" : "password";
            const icon = btn.querySelector(".dashicons");
            if (icon) {
              icon.className = isPassword
                ? "dashicons dashicons-hidden"
                : "dashicons dashicons-visibility";
            }
          } else if (action === "clear") {
            if (target.value.trim() === "") return;
            if (window.confirm("Clear all content in this editor?")) {
              target.value = "";
              target.dispatchEvent(new Event("input"));
            }
          } else if (action === "copy") {
            navigator.clipboard.writeText(target.value).then(function () {
              const originalHTML = btn.innerHTML;
              btn.innerHTML = '<span class="dashicons dashicons-yes"></span>';
              setTimeout(function () {
                btn.innerHTML = originalHTML;
              }, 1500);
            });
          }
        });
      });
  }

  function initProviderSwitch() {
    const select = document.getElementById("awb_ai_provider");
    if (!select) return;

    function updateCards() {
      document.querySelectorAll(".awb-api-key-card").forEach(function (card) {
        if (card.dataset.provider === select.value) {
          card.classList.remove("is-inactive");
          card.classList.add("is-active");
        } else {
          card.classList.remove("is-active");
          card.classList.add("is-inactive");
        }
      });
    }
    select.addEventListener("change", updateCards);
    updateCards(); // Init state
  }

  function initApiTesting() {
    document.querySelectorAll(".awb-test-api-key").forEach(function (btn) {
      btn.addEventListener("click", function () {
        const provider = btn.dataset.provider;
        const nonce = btn.dataset.nonce;
        const statusEl = document.querySelector(
          `.awb-test-result[data-provider="${provider}"]`,
        );
        if (!statusEl) return;

        btn.disabled = true;
        btn.textContent = "Testing…";
        statusEl.textContent = "";

        const fd = new FormData();
        fd.append("action", "awb_test_ai_api");
        fd.append("nonce", nonce);
        fd.append("provider", provider);

        fetch(ajaxurl || "/wp-admin/admin-ajax.php", {
          method: "POST",
          body: fd,
        })
          .then((r) => r.json())
          .then((data) => {
            statusEl.textContent = data.success
              ? "✓ Key Valid"
              : "✗ " + (data.data?.message || "Failed");
            statusEl.style.color = data.success ? "#2e7d32" : "#b00020";
          })
          .catch(() => {
            statusEl.textContent = "✗ Network error";
            statusEl.style.color = "#b00020";
          })
          .finally(() => {
            btn.disabled = false;
            btn.textContent = "Test Connection";
          });
      });
    });
  }

  /* ── Design tokens: sync color picker ↔ hex input ────────────────────── */

  function initColorTokenSync() {
    document
      .querySelectorAll('input[type="color"][data-target]')
      .forEach(function (picker) {
        const hex = document.getElementById(picker.dataset.target);
        if (!hex) return;

        // Picker → hex text field
        picker.addEventListener("input", function () {
          hex.value = picker.value;
          hex.dispatchEvent(new Event("input"));
        });

        // Hex text field → picker
        hex.addEventListener("input", function () {
          if (/^#[0-9A-Fa-f]{6}$/.test(hex.value)) {
            picker.value = hex.value;
          }
          updateTokenPreview();
        });
      });
  }

  /* ── Design tokens: live :root preview block ──────────────────────────── */

  function initTokenPreview() {
    const output = document.getElementById("awb-token-output");
    if (!output) return;

    updateTokenPreview();

    // Re-render whenever any token field changes.
    document.querySelectorAll('[name^="awb_token_"]').forEach(function (field) {
      field.addEventListener("input", updateTokenPreview);
    });
  }

  function updateTokenPreview() {
    const output = document.getElementById("awb-token-output");
    if (!output) return;

    const fields = document.querySelectorAll('[name^="awb_token_"]');
    if (fields.length === 0) return;

    let lines = [":root {"];
    fields.forEach(function (field) {
      const cssVar = field.name
        .replace(/^awb_token_/, "--awb-")
        .replaceAll("_", "-");
      const val = field.value.trim() || field.placeholder || "";
      if (val) {
        lines.push("    " + cssVar + ": " + val + ";");
      }
    });
    lines.push("}");

    output.textContent = lines.join("\n");
  }

  /* ── Library: search, filters & sorting ──────────────────────────────── */

  const libraryState = {
    search: "",
    folder: "all",
    category: "all",
    source: "all",
    asset: "all",
    sort: "title-asc",
  };

  const librarySorters = {
    "title-asc": function (a, b) {
      return a.dataset.title.localeCompare(b.dataset.title);
    },
    "title-desc": function (a, b) {
      return b.dataset.title.localeCompare(a.dataset.title);
    },
    "folder-asc": function (a, b) {
      return (
        a.dataset.folder.localeCompare(b.dataset.folder) ||
        a.dataset.title.localeCompare(b.dataset.title)
      );
    },
    source: function (a, b) {
      const rank = function (card) {
        return card.dataset.source === "core" ? 0 : 1;
      };
      return (
        rank(a) - rank(b) || a.dataset.title.localeCompare(b.dataset.title)
      );
    },
    "has-assets": function (a, b) {
      const rank = function (card) {
        return card.dataset.hasCss === "1" || card.dataset.hasJs === "1"
          ? 0
          : 1;
      };
      return (
        rank(a) - rank(b) || a.dataset.title.localeCompare(b.dataset.title)
      );
    },
  };

  function initLibraryFilters() {
    const search = document.getElementById("awb-pattern-search");
    const sort = document.getElementById("awb-pattern-sort");
    if (!search && !sort && !document.querySelector(".awb-filter-btn")) return;

    let debounceTimer;

    if (search) {
      search.addEventListener("input", function () {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(function () {
          libraryState.search = search.value.toLowerCase().trim();
          renderLibrary();
        }, 120);
      });
    }

    if (sort) {
      libraryState.sort = sort.value || libraryState.sort;
      sort.addEventListener("change", function () {
        libraryState.sort = sort.value;
        renderLibrary();
      });
    }

    document
      .querySelectorAll(".awb-filter-btn[data-filter-type]")
      .forEach(function (btn) {
        btn.addEventListener("click", function () {
          const type = btn.dataset.filterType;
          if (!(type in libraryState)) return;

          // Toggle active state within this group only.
          document
            .querySelectorAll(
              '.awb-filter-btn[data-filter-type="' + type + '"]',
            )
            .forEach(function (sibling) {
              sibling.classList.toggle("is-active", sibling === btn);
              sibling.setAttribute(
                "aria-pressed",
                sibling === btn ? "true" : "false",
              );
            });

          libraryState[type] = btn.dataset.filterValue;
          renderLibrary();
        });
      });

    renderLibrary();
  }

  function renderLibrary() {
    const grid = document.getElementById("awb-patterns-grid");
    const cards = Array.from(document.querySelectorAll(".awb-pattern-card"));
    const noResults = document.getElementById("awb-no-results");
    if (!grid || cards.length === 0) return;

    const matches = cards.filter(function (card) {
      const d = card.dataset;
      const matchesSearch =
        !libraryState.search ||
        (d.keywords || "").includes(libraryState.search);
      const matchesFolder =
        libraryState.folder === "all" || d.folder === libraryState.folder;
      const matchesCategory =
        libraryState.category === "all" ||
        (d.categories || "").split(" ").includes(libraryState.category);
      const matchesSource =
        libraryState.source === "all" || d.source === libraryState.source;
      const matchesAsset =
        libraryState.asset === "all" ||
        (libraryState.asset === "css" ? d.hasCss === "1" : d.hasJs === "1");

      return (
        matchesSearch &&
        matchesFolder &&
        matchesCategory &&
        matchesSource &&
        matchesAsset
      );
    });

    const sorter = librarySorters[libraryState.sort] || librarySorters["title-asc"];
    matches.sort(sorter);

    matches.forEach(function (card) {
      card.classList.remove("is-hidden");
      grid.appendChild(card); // Delegated click handlers keep working after reorder.
    });
    cards.forEach(function (card) {
      if (!matches.includes(card)) {
        card.classList.add("is-hidden");
      }
    });

    if (noResults) {
      noResults.hidden = matches.length > 0;
    }

    const countEl = document.getElementById("awb-pattern-count");
    if (countEl) {
      countEl.textContent =
        matches.length +
        " pattern" +
        (matches.length !== 1 ? "s" : "") +
        (matches.length !== cards.length ? " of " + cards.length : "");
    }
  }

  /* ── Library: view toggle (grid / list) ──────────────────────────────── */

  function initViewToggle() {
    const grid = document.getElementById("awb-pattern-grid");
    if (!grid) return;

    document.querySelectorAll(".awb-view-btn").forEach(function (btn) {
      btn.addEventListener("click", function () {
        const view = btn.dataset.view;
        grid.dataset.view = view;

        document.querySelectorAll(".awb-view-btn").forEach(function (b) {
          b.classList.toggle("is-active", b === btn);
          b.setAttribute("aria-pressed", b === btn ? "true" : "false");
        });

        // Persist preference.
        try {
          localStorage.setItem("awb_library_view", view);
        } catch (e) {}
      });
    });

    // Restore preference.
    try {
      const saved = localStorage.getItem("awb_library_view");
      if (saved && grid) {
        const btn = document.querySelector(
          '.awb-view-btn[data-view="' + saved + '"]',
        );
        if (btn) btn.click();
      }
    } catch (e) {}
  }

  /* ── Library: copy markup + preview buttons ──────────────────────────── */

  function initPatternActions() {
    // Copy markup.
    document.addEventListener("click", function (e) {
      const btn = e.target.closest(".awb-copy-pattern");
      if (!btn) return;

      const content = btn.dataset.content || "";
      navigator.clipboard.writeText(content).then(function () {
        const original = btn.textContent;
        btn.textContent = "Copied!";
        btn.classList.add("awb-btn--copied");
        setTimeout(function () {
          btn.textContent = original;
          btn.classList.remove("awb-btn--copied");
        }, 1600);
      });
    });

    // Preview (open modal and fetch rendered pattern).
    document.addEventListener("click", function (e) {
      const btn = e.target.closest(".awb-preview-pattern");
      if (!btn) return;

      openModal(btn.dataset.title || "Pattern preview");
      fetchPreview(btn.dataset.pattern || "");
    });
  }

  /* ── Modal ────────────────────────────────────────────────────────────── */

  let currentModalContent = "";
  let currentPreviewData = null;
  let previewView = "visual";

  function initModal() {
    const backdrop = document.getElementById("awb-modal-backdrop");
    const closeBtn = document.getElementById("awb-modal-close");
    const closeFtr = document.getElementById("awb-modal-close-btn");
    const copyBtn = document.getElementById("awb-modal-copy");

    if (backdrop) backdrop.addEventListener("click", closeModal);
    if (closeBtn) closeBtn.addEventListener("click", closeModal);
    if (closeFtr) closeFtr.addEventListener("click", closeModal);

    if (copyBtn) {
      copyBtn.addEventListener("click", function () {
        navigator.clipboard.writeText(currentModalContent).then(function () {
          copyBtn.textContent = "Copied!";
          setTimeout(function () {
            copyBtn.textContent = "Copy markup";
          }, 1600);
        });
      });
    }

    // Visual / Markup view toggle.
    document.querySelectorAll("[data-preview-view]").forEach(function (btn) {
      btn.addEventListener("click", function () {
        previewView = btn.dataset.previewView;
        document.querySelectorAll("[data-preview-view]").forEach(function (b) {
          b.classList.toggle("is-active", b === btn);
          b.setAttribute("aria-pressed", b === btn ? "true" : "false");
        });
        renderModalBody();
      });
    });

    // Close on Escape.
    document.addEventListener("keydown", function (e) {
      if (e.key === "Escape") closeModal();
    });
  }

  function openModal(title) {
    const modal = document.getElementById("awb-preview-modal");
    const titleEl = document.getElementById("awb-preview-modal-title");
    const body = document.getElementById("awb-modal-body");
    if (!modal) return;

    currentModalContent = "";
    currentPreviewData = null;
    previewView = "visual";
    document.querySelectorAll("[data-preview-view]").forEach(function (b) {
      b.classList.toggle(
        "is-active",
        (b.dataset.previewView || "visual") === "visual",
      );
      b.setAttribute(
        "aria-pressed",
        (b.dataset.previewView || "visual") === "visual" ? "true" : "false",
      );
    });
    if (titleEl) titleEl.textContent = title;
    if (body)
      body.innerHTML =
        '<div class="awb-modal-loading">Loading preview…</div>';

    modal.hidden = false;
    document.body.style.overflow = "hidden";

    // Focus the close button for accessibility.
    document.getElementById("awb-modal-close")?.focus();
  }

  function closeModal() {
    const modal = document.getElementById("awb-preview-modal");
    if (modal) modal.hidden = true;
    document.body.style.overflow = "";
    currentModalContent = "";
    currentPreviewData = null;
  }

  function fetchPreview(patternName) {
    if (!patternName) return;
    const nonce = window.awbPatternIO?.editNonce || "";
    const base = window.ajaxurl || "/wp-admin/admin-ajax.php";
    const url =
      base +
      "?action=awb_preview_pattern&pattern=" +
      encodeURIComponent(patternName) +
      "&nonce=" +
      encodeURIComponent(nonce);

    fetch(url)
      .then(function (r) {
        return r.json();
      })
      .then(function (json) {
        if (!json.success) {
          renderModalError(json.data?.message || "Preview failed.");
          return;
        }
        currentPreviewData = json.data;
        currentModalContent = json.data.content || "";
        renderModalBody();
      })
      .catch(function (err) {
        renderModalError("Network error: " + err.message);
      });
  }

  function renderModalError(message) {
    const body = document.getElementById("awb-modal-body");
    if (body)
      body.innerHTML =
        '<div class="awb-modal-loading awb-modal-loading--error"></div>';
    const errEl = body?.querySelector(".awb-modal-loading--error");
    if (errEl) errEl.textContent = message;
  }

  function escapeHtml(str) {
    return String(str)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;");
  }

  function renderModalBody() {
    const body = document.getElementById("awb-modal-body");
    if (!body) return;

    if (previewView === "markup") {
      body.innerHTML =
        '<pre class="awb-modal-markup">' +
        escapeHtml(currentModalContent) +
        "</pre>";
      return;
    }
    if (!currentPreviewData) {
      body.innerHTML =
        '<div class="awb-modal-loading">Loading preview…</div>';
      return;
    }

    body.innerHTML =
      '<iframe class="awb-modal-frame" sandbox="allow-scripts" title="Pattern visual preview"></iframe>';
    const frame = body.querySelector(".awb-modal-frame");
    if (frame) frame.srcdoc = buildPreviewDoc();
  }

  function buildPreviewDoc() {
    const links = (currentPreviewData.css || [])
      .map(function (u) {
        return '<link rel="stylesheet" href="' + escapeHtml(u) + '">';
      })
      .join("");
    const tokens = currentPreviewData.tokens
      ? "<style>" + currentPreviewData.tokens + "</style>"
      : "";
    return (
      '<!doctype html><html><head><meta charset="utf-8">' +
      links +
      tokens +
      "<style>body{margin:0;padding:24px;font-family:system-ui,sans-serif;}" +
      "img{max-width:100%;height:auto;}</style></head><body>" +
      currentModalContent +
      "</body></html>"
    );
  }

  /* ── Library: bulk selection & actions ────────────────────────────────── */

  function initBulkActions() {
    const bar = document.getElementById("awb-bulk-bar");
    if (!bar) return;

    const countEl = document.getElementById("awb-bulk-count");
    const statusEl = document.getElementById("awb-bulk-status");
    const exportBtn = document.getElementById("awb-bulk-export");
    const duplicateBtn = document.getElementById("awb-bulk-duplicate");
    const deleteBtn = document.getElementById("awb-bulk-delete");
    const clearBtn = document.getElementById("awb-bulk-clear");

    const selectedChecks = () =>
      Array.from(document.querySelectorAll(".awb-pattern-check:checked"));

    function refresh() {
      const checks = selectedChecks();
      const actable = checks.filter((cb) => cb.dataset.actable === "1");
      bar.hidden = checks.length === 0;
      if (countEl) {
        countEl.textContent =
          checks.length + " pattern" + (checks.length !== 1 ? "s" : "") + " selected";
      }
      if (exportBtn) exportBtn.disabled = actable.length === 0;
      if (duplicateBtn) duplicateBtn.disabled = actable.length === 0;
      if (deleteBtn)
        deleteBtn.disabled =
          checks.filter(
            (cb) => cb.closest(".awb-pattern-card")?.dataset.source === "user",
          ).length === 0;
    }

    document.addEventListener("change", function (e) {
      if (e.target instanceof HTMLInputElement && e.target.classList.contains("awb-pattern-check")) {
        refresh();
      }
    });

    if (clearBtn) {
      clearBtn.addEventListener("click", function () {
        document
          .querySelectorAll(".awb-pattern-check:checked")
          .forEach(function (cb) {
            cb.checked = false;
          });
        refresh();
      });
    }

    if (exportBtn) {
      exportBtn.addEventListener("click", function () {
        const names = selectedChecks()
          .filter((cb) => cb.dataset.actable === "1")
          .map((cb) => cb.dataset.pattern);
        bulkExport(names, statusEl);
      });
    }

    if (duplicateBtn) {
      duplicateBtn.addEventListener("click", function () {
        const names = selectedChecks()
          .filter((cb) => cb.dataset.actable === "1")
          .map((cb) => cb.dataset.pattern);
        bulkPost(
          names,
          "awb_duplicate_pattern",
          window.awbPatternIO?.duplicateNonce || "",
          duplicateBtn,
          statusEl,
          "Cloning",
        );
      });
    }

    if (deleteBtn) {
      deleteBtn.addEventListener("click", function () {
        const checks = selectedChecks().filter(
          (cb) => cb.closest(".awb-pattern-card")?.dataset.source === "user",
        );
        const names = checks.map((cb) => cb.dataset.pattern);
        const usagePages = checks.reduce(function (sum, cb) {
          return sum + (parseInt(cb.closest(".awb-pattern-card")?.dataset.usage || "0", 10) || 0);
        }, 0);
        let warning =
          "Delete " + names.length + " selected user pattern" + (names.length !== 1 ? "s" : "") +
          "? This cannot be undone.";
        if (usagePages > 0) {
          warning +=
            "\n\nWARNING: these patterns appear on " + usagePages + " published page" +
            (usagePages !== 1 ? "s" : "") + ". Existing content will keep working but the pattern will disappear from the library.";
        }
        if (!window.confirm(warning)) return;
        bulkPost(names, "awb_delete_pattern", window.awbPatternIO?.deleteNonce || "", deleteBtn, statusEl, "Deleting", true);
      });
    }

    refresh();
  }

  function bulkExport(names, statusEl) {
    const base = window.ajaxurl || "/wp-admin/admin-ajax.php";
    const nonce = window.awbPatternIO?.nonce || "";
    if (statusEl) statusEl.textContent = "Preparing " + names.length + " download(s)…";
    names.forEach(function (name, i) {
      setTimeout(function () {
        const url =
          base +
          "?action=awb_export_pattern&pattern=" +
          encodeURIComponent(name) +
          "&nonce=" +
          encodeURIComponent(nonce);
        const frame = document.createElement("iframe");
        frame.style.display = "none";
        frame.src = url;
        frame.addEventListener("load", function () {
          setTimeout(function () {
            frame.remove();
          }, 5000);
        });
        document.body.appendChild(frame);
        if (statusEl && i === names.length - 1) {
          statusEl.textContent = "Downloads started.";
        }
      }, i * 500);
    });
  }

  function bulkPost(names, action, nonce, btn, statusEl, verb, reloadAfter) {
    if (!names.length) return;
    const original = btn.textContent;
    btn.disabled = true;
    let done = 0;
    let failed = 0;
    const base = window.ajaxurl || "/wp-admin/admin-ajax.php";

    function setStatus() {
      if (statusEl) {
        statusEl.textContent = verb + " " + done + " of " + names.length + "…";
      }
    }

    function next() {
      if (done + failed >= names.length) {
        btn.disabled = false;
        btn.textContent = original;
        if (statusEl) {
          statusEl.textContent =
            verb.replace(/ing$/, "ed") + " " + (done - failed >= 0 ? done : 0) +
            (failed ? ", " + failed + " failed." : ".");
        }
        if (reloadAfter) {
          setTimeout(function () {
            window.location.reload();
          }, 800);
        } else if (failed > 0) {
          window.alert(failed + " of " + names.length + " operations failed.");
        }
        return;
      }
      setStatus();
      const name = names[done];
      const fd = new FormData();
      fd.append("action", action);
      fd.append("nonce", nonce);
      fd.append("pattern", name);
      fetch(base, { method: "POST", body: fd })
        .then(function (r) {
          return r.json();
        })
        .then(function (json) {
          if (json.success) {
            done++;
          } else {
            failed++;
          }
        })
        .catch(function () {
          failed++;
        })
        .finally(next);
    }
    next();
  }

  /* ── Onboarding checklist dismissal ───────────────────────────────────── */

  function initOnboarding() {
    const dismissBtn = document.getElementById("awb-onboarding-dismiss");
    if (!dismissBtn) return;

    dismissBtn.addEventListener("click", function () {
      const card = document.getElementById("awb-onboarding");
      if (card) card.remove();

      const fd = new FormData();
      fd.append("action", "awb_dismiss_onboarding");
      fd.append("nonce", dismissBtn.dataset.nonce || "");
      fetch(ajaxurl || "/wp-admin/admin-ajax.php", { method: "POST", body: fd });
    });
  }

  /* ── Site Scaffold ────────────────────────────────────────────────────── */

  function initScaffold() {
    document.querySelectorAll(".awb-scaffold-trigger").forEach(function (btn) {
      btn.addEventListener("click", function () {
        const scaffold = btn.dataset.scaffold;
        const nonce = btn.dataset.nonce;
        const log = document.getElementById("awb-scaffold-log");
        const list = document.getElementById("awb-scaffold-log-list");

        if (!log || !list) return;

        log.hidden = false;
        btn.disabled = true;
        btn.textContent = "Creating…";

        appendLog(list, "info", 'Requesting "' + scaffold + '" scaffold…');

        const data = new FormData();
        data.append("action", "awb_scaffold");
        data.append("scaffold", scaffold);
        data.append("nonce", nonce);

        fetch(window.ajaxurl || "/wp-admin/admin-ajax.php", {
          method: "POST",
          body: data,
        })
          .then(function (r) {
            return r.json();
          })
          .then(function (response) {
            if (response.success && response.data) {
              (response.data.log || []).forEach(function (line) {
                appendLog(list, "success", line);
              });
              appendLog(list, "success", "Scaffold complete.");
            } else {
              appendLog(
                list,
                "error",
                response.data?.message || "Scaffold failed.",
              );
            }
          })
          .catch(function (err) {
            appendLog(list, "error", "Request failed: " + err.message);
          })
          .finally(function () {
            btn.disabled = false;
            btn.textContent = "Create scaffold";
          });
      });
    });
  }

  /* ── Font deletion ────────────────────────────────────────────────────── */

  function initFontDeletion() {
    document.querySelectorAll(".awb-delete-font").forEach(function (btn) {
      btn.addEventListener("click", function () {
        const fontType = btn.dataset.fontType;
        const nonce = btn.dataset.nonce;

        if (!confirm("Are you sure you want to delete this font file?")) {
          return;
        }

        const formData = new FormData();
        formData.append("action", "awb_delete_font");
        formData.append("font_type", fontType);
        formData.append("nonce", nonce);

        fetch(ajaxurl, {
          method: "POST",
          body: formData,
        })
          .then(function (response) {
            return response.json();
          })
          .then(function (data) {
            if (data.success) {
              location.reload();
            } else {
              alert("Error: " + (data.data?.message || "Unknown error"));
            }
          })
          .catch(function (err) {
            alert("Request failed: " + err.message);
          });
      });
    });
  }

  // =========================================================================
  // AI Generator Tab Handlers
  // =========================================================================
  function initAITab() {
    const saveCtxBtn = document.getElementById("awb-ai-save-context");
    const genBtn = document.getElementById("awb-ai-generate");
    const clearBtn = document.getElementById("awb-ai-clear");
    const copyBtn = document.getElementById("awb-ai-copy-output");
    const insertBtn = document.getElementById("awb-ai-insert-output");
    const modeEl = document.getElementById("awb-ai-mode");
    const toneEl = document.getElementById("awb-ai-tone");
    const templateEl = document.getElementById("awb-ai-template");
    const promptEl = document.getElementById("awb-ai-prompt");
    const outputEl = document.getElementById("awb-ai-output");
    const statusEl = document.getElementById("awb-ai-status-label");
    const nameEl = document.getElementById("awb-ai-business-name");
    const descEl = document.getElementById("awb-ai-business-desc");

    if (!genBtn) return; // Bail if not on AI tab

    // Save Context
    if (saveCtxBtn && nameEl && descEl) {
      saveCtxBtn.addEventListener("click", function () {
        const original = saveCtxBtn.textContent;
        saveCtxBtn.disabled = true;
        saveCtxBtn.textContent = "Saving…";
        const fd = new FormData();
        fd.append("action", "awb_save_ai_context");
        fd.append("nonce", saveCtxBtn.dataset.nonce);
        fd.append("business_name", nameEl.value.trim());
        fd.append("business_desc", descEl.value.trim());

        fetch(ajaxurl || "/wp-admin/admin-ajax.php", {
          method: "POST",
          body: fd,
        })
          .then((r) => r.json())
          .then((data) => {
            if (data.success) {
              saveCtxBtn.textContent = "Saved!";
              setTimeout(() => {
                saveCtxBtn.textContent = original;
                saveCtxBtn.disabled = false;
              }, 1500);
            } else {
              saveCtxBtn.textContent = "Failed";
              saveCtxBtn.disabled = false;
              if (statusEl) {
                statusEl.textContent =
                  "Context save failed: " + (data.data?.message || "");
                statusEl.style.color = "#c62828";
              }
            }
          })
          .catch(() => {
            saveCtxBtn.textContent = original;
            saveCtxBtn.disabled = false;
          });
      });
    }

    // Generate
    genBtn.addEventListener("click", async function () {
      const prompt = promptEl?.value.trim();
      if (!prompt) {
        if (statusEl) {
          statusEl.textContent = "Please enter a prompt.";
          statusEl.style.color = "#c62828";
        }
        return;
      }
      genBtn.disabled = true;
      genBtn.textContent = "Generating…";
      if (statusEl) {
        statusEl.textContent = "Calling AI…";
        statusEl.style.color = "#666";
      }
      outputEl.value = "";
      if (copyBtn) copyBtn.disabled = true;
      if (insertBtn) insertBtn.disabled = true;

      try {
        const fd = new FormData();
        fd.append("action", "awb_generate");
        fd.append("nonce", genBtn.dataset.nonce);
        fd.append("prompt", prompt);
        fd.append("mode", modeEl ? modeEl.value : "blocks");
        fd.append("tone", toneEl ? toneEl.value : "");
        fd.append("template", templateEl ? templateEl.value : "");

        const res = await fetch(ajaxurl || "/wp-admin/admin-ajax.php", {
          method: "POST",
          body: fd,
        });
        const json = await res.json();

        if (json.success) {
          outputEl.value = json.data.blocks;
          if (copyBtn) copyBtn.disabled = false;
          if (insertBtn && (!modeEl || modeEl.value !== "copy")) {
            insertBtn.disabled = false;
          }
          if (statusEl) {
            statusEl.textContent = "Done!";
            statusEl.style.color = "#2e7d32";
          }
        } else {
          if (statusEl) {
            statusEl.textContent =
              "Error: " + (json.data?.message || "Unknown error");
            statusEl.style.color = "#c62828";
          }
        }
      } catch (err) {
        if (statusEl) {
          statusEl.textContent = "Network error: " + err.message;
          statusEl.style.color = "#c62828";
        }
      } finally {
        genBtn.disabled = false;
        genBtn.textContent = "Generate";
      }
    });

    // Clear
    if (clearBtn) {
      clearBtn.addEventListener("click", function () {
        if (promptEl) promptEl.value = "";
        if (outputEl) outputEl.value = "";
        if (statusEl) statusEl.textContent = "";
        if (copyBtn) copyBtn.disabled = true;
        if (insertBtn) insertBtn.disabled = true;
      });
    }

    // Insert into editor — creates a draft page containing the generated
    // markup and opens it in the block editor.
    if (insertBtn) {
      insertBtn.addEventListener("click", function () {
        const content = outputEl?.value?.trim();
        if (!content) return;

        const orig = insertBtn.textContent;
        insertBtn.disabled = true;
        insertBtn.textContent = "Creating…";

        const fd = new FormData();
        fd.append("action", "awb_ai_draft");
        fd.append("nonce", insertBtn.dataset.nonce || "");
        fd.append("content", content);

        fetch(ajaxurl || "/wp-admin/admin-ajax.php", { method: "POST", body: fd })
          .then((r) => r.json())
          .then((data) => {
            if (data.success && data.data?.edit_link) {
              window.location.href = data.data.edit_link;
              return;
            }
            insertBtn.disabled = false;
            insertBtn.textContent = orig;
            if (statusEl) {
              statusEl.textContent =
                "Insert failed: " + (data.data?.message || "Unknown error");
              statusEl.style.color = "#c62828";
            }
          })
          .catch((err) => {
            insertBtn.disabled = false;
            insertBtn.textContent = orig;
            if (statusEl) {
              statusEl.textContent = "Insert failed: " + err.message;
              statusEl.style.color = "#c62828";
            }
          });
      });
    }

    // Copy Output
    if (copyBtn) {
      copyBtn.addEventListener("click", function () {
        if (outputEl?.value) {
          navigator.clipboard.writeText(outputEl.value).then(() => {
            const orig = copyBtn.textContent;
            copyBtn.textContent = "Copied!";
            setTimeout(() => {
              copyBtn.textContent = orig;
            }, 1500);
          });
        }
      });
    }
  }

  function appendLog(list, type, message) {
    const li = document.createElement("li");
    li.textContent =
      (type === "success" ? "✓ " : type === "error" ? "✗ " : "· ") + message;
    li.style.color =
      type === "success"
        ? "var(--awb-c-success)"
        : type === "error"
          ? "#b00020"
          : "var(--awb-c-ink-secondary)";
    list.appendChild(li);
    li.scrollIntoView({ behavior: "smooth", block: "nearest" });
  }
})();
