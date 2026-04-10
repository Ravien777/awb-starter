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
    initColorTokenSync();
    initTokenPreview();
    initLibrarySearch();
    initLibraryFilter();
    initViewToggle();
    initPatternActions();
    initModal();
    initScaffold();
    initFontDeletion();
  }

  /* ── Editor toolbar (Copy / Clear buttons) ────────────────────────────── */

  function initEditorToolbar() {
    document.querySelectorAll(".awb-editor-btn").forEach(function (btn) {
      btn.addEventListener("click", function () {
        const action = btn.dataset.action;
        const target = document.getElementById(btn.dataset.target);
        if (!target) return;

        if (action === "clear") {
          if (target.value.trim() === "") return;
          if (window.confirm("Clear all content in this editor?")) {
            target.value = "";
            target.dispatchEvent(new Event("input"));
          }
        }

        if (action === "copy") {
          navigator.clipboard.writeText(target.value).then(function () {
            const original = btn.textContent;
            btn.textContent = "Copied!";
            setTimeout(function () {
              btn.textContent = original;
            }, 1600);
          });
        }
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

  /* ── Library: search ─────────────────────────────────────────────────── */

  function initLibrarySearch() {
    const search = document.getElementById("awb-search");
    if (!search) return;

    let debounceTimer;

    search.addEventListener("input", function () {
      clearTimeout(debounceTimer);
      debounceTimer = setTimeout(applyFilters, 120);
    });
  }

  /* ── Library: category filter ────────────────────────────────────────── */

  let activeFilter = "all";

  function initLibraryFilter() {
    document.querySelectorAll(".awb-filter-btn").forEach(function (btn) {
      btn.addEventListener("click", function () {
        document.querySelectorAll(".awb-filter-btn").forEach(function (b) {
          b.classList.remove("is-active");
          b.setAttribute("aria-pressed", "false");
        });
        btn.classList.add("is-active");
        btn.setAttribute("aria-pressed", "true");
        activeFilter = btn.dataset.filter;
        applyFilters();
      });
    });
  }

  function applyFilters() {
    const query = (document.getElementById("awb-search")?.value || "")
      .toLowerCase()
      .trim();
    const cards = document.querySelectorAll(".awb-pattern-card");
    const noResults = document.getElementById("awb-no-results");
    let visibleCount = 0;

    cards.forEach(function (card) {
      const cats = card.dataset.categories || "";
      const keywords = (card.dataset.keywords || "").toLowerCase();

      const matchesCat = activeFilter === "all" || cats.includes(activeFilter);
      const matchesSearch = !query || keywords.includes(query);

      if (matchesCat && matchesSearch) {
        card.classList.remove("is-hidden");
        visibleCount++;
      } else {
        card.classList.add("is-hidden");
      }
    });

    if (noResults) {
      noResults.hidden = visibleCount > 0;
    }

    const countEl = document.getElementById("awb-pattern-count");
    if (countEl) {
      countEl.textContent =
        visibleCount + " pattern" + (visibleCount !== 1 ? "s" : "");
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

    // Preview (open modal).
    document.addEventListener("click", function (e) {
      const btn = e.target.closest(".awb-insert-pattern");
      if (!btn) return;

      const card = btn.closest(".awb-pattern-card");
      const title =
        card?.querySelector(".awb-pattern-card__title")?.textContent ||
        "Pattern preview";
      const copy = card?.querySelector(".awb-copy-pattern");

      openModal(title, "", copy?.dataset.content || "");
    });
  }

  /* ── Modal ────────────────────────────────────────────────────────────── */

  let currentModalContent = "";

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

    // Close on Escape.
    document.addEventListener("keydown", function (e) {
      if (e.key === "Escape") closeModal();
    });
  }

  function openModal(title, description, content) {
    const modal = document.getElementById("awb-preview-modal");
    const titleEl = document.getElementById("awb-modal-title");
    const body = document.getElementById("awb-modal-body");
    if (!modal) return;

    currentModalContent = content;
    if (titleEl) titleEl.textContent = title;

    if (body) {
      // Render the raw block markup in an iframe-like container.
      body.innerHTML = '<div class="awb-modal-render">' + content + "</div>";
    }

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
  }

  /* ── Scaffold (stub — fires AJAX when wired up in PHP) ────────────────── */

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

        // AJAX call — PHP handler at wp_ajax_awb_scaffold (add to plugin).
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
                response.data?.message ||
                  "Scaffold handler not yet implemented — add wp_ajax_awb_scaffold to the plugin.",
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
