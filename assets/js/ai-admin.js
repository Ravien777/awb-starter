/**
 * AWB Starter – AI Generator Admin JS
 * Handles provider switching, key visibility, testing, and generation.
 *
 * @package AWBStarter
 */
(function () {
  "use strict";

  document.addEventListener("DOMContentLoaded", function () {
    initProviderSwitch();
    initKeyActions();
    initKeyTesting();
    initGeneration();
  });

  function initProviderSwitch() {
    const select = document.getElementById("awb_ai_provider");
    if (!select) return;

    select.addEventListener("change", function () {
      document.querySelectorAll(".awb-api-key-row").forEach(function (row) {
        row.hidden = row.dataset.provider !== select.value;
      });
    });
    // Trigger once to set initial state
    select.dispatchEvent(new Event("change"));
  }

  function initKeyActions() {
    document.querySelectorAll(".awb-editor-btn").forEach(function (btn) {
      btn.addEventListener("click", function () {
        const action = btn.dataset.action;
        const target = document.getElementById(btn.dataset.target);
        if (!target) return;

        if (action === "clear") {
          target.value = "";
          target.type = "password";
          btn
            .closest(".awb-api-status")
            .querySelector(".awb-api-status__badge")
            ?.remove();
        }
        if (action === "toggle-visibility") {
          target.type = target.type === "password" ? "text" : "password";
          btn.textContent = target.type === "password" ? "Show" : "Hide";
        }
        if (action === "copy") {
          navigator.clipboard.writeText(target.value).then(function () {
            const original = btn.textContent;
            btn.textContent = "Copied!";
            setTimeout(() => (btn.textContent = original), 1500);
          });
        }
      });
    });
  }

  function initKeyTesting() {
    document.querySelectorAll(".awb-test-api-key").forEach(function (btn) {
      btn.addEventListener("click", function () {
        const provider = btn.dataset.provider;
        const nonce = btn.dataset.nonce;
        const statusEl = document.querySelector(
          `.awb-api-status__result[data-provider="${provider}"]`,
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
              ? "✓ Verified"
              : "✗ " + (data.data?.message || "Failed");
            statusEl.style.color = data.success
              ? "var(--awb-c-success, #2e7d32)"
              : "var(--awb-c-error, #b00020)";
          })
          .catch(() => {
            statusEl.textContent = "✗ Network error";
            statusEl.style.color = "var(--awb-c-error, #b00020)";
          })
          .finally(() => {
            btn.disabled = false;
            btn.textContent = "Test connection";
          });
      });
    });
  }

  function initGeneration() {
    const genBtn = document.getElementById("awb-ai-generate");
    if (!genBtn) return;

    genBtn.addEventListener("click", async function () {
      const prompt = document.getElementById("awb-ai-prompt")?.value.trim();
      const status = document.getElementById("awb-ai-status-label");
      const output = document.getElementById("awb-ai-output");
      const copyBtn = document.getElementById("awb-ai-copy-output");

      if (!prompt) {
        status.textContent = "Please enter a prompt.";
        status.style.color = "#c62828";
        return;
      }

      genBtn.disabled = true;
      genBtn.textContent = "Generating…";
      status.textContent = "Calling AI…";
      status.style.color = "#666";
      output.value = "";
      copyBtn.disabled = true;

      try {
        const fd = new FormData();
        fd.append("action", "awb_generate");
        fd.append("nonce", window.AWB?.nonce || "");
        fd.append("prompt", prompt);

        const res = await fetch(ajaxurl || "/wp-admin/admin-ajax.php", {
          method: "POST",
          body: fd,
        });
        const json = await res.json();

        if (json.success) {
          output.value = json.data.blocks;
          copyBtn.disabled = false;
          status.textContent = "Done!";
          status.style.color = "var(--awb-c-success, #2e7d32)";
        } else {
          status.textContent =
            "Error: " + (json.data?.message || "Unknown error");
          status.style.color = "var(--awb-c-error, #b00020)";
        }
      } catch (err) {
        status.textContent = "Network error: " + err.message;
        status.style.color = "var(--awb-c-error, #b00020)";
      } finally {
        genBtn.disabled = false;
        genBtn.textContent = "Generate";
      }
    });

    const copyBtn = document.getElementById("awb-ai-copy-output");
    if (copyBtn) {
      copyBtn.addEventListener("click", function () {
        const output = document.getElementById("awb-ai-output");
        navigator.clipboard.writeText(output.value).then(() => {
          copyBtn.textContent = "Copied!";
          setTimeout(() => (copyBtn.textContent = "Copy"), 1500);
        });
      });
    }
  }
})();
