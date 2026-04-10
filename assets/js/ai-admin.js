/**
 * AWB Starter – Admin JS
 * Handles the AI content generator UI in the block editor.
 *
 * AWB (object) is localized by PHP via wp_localize_script:
 *   AWB.ajaxUrl  — WordPress admin-ajax.php URL
 *   AWB.nonce    — Security nonce for awb_generate_nonce
 */

(function () {
  "use strict";

  function injectAIPanel() {
    if (!document.querySelector(".block-editor")) return;

    const toolbar =
      document.querySelector(".edit-post-header__toolbar") ||
      document.querySelector(".editor-header__toolbar");
    if (!toolbar || document.getElementById("awb-ai-panel-btn")) return;

    const btn = document.createElement("button");
    btn.id = "awb-ai-panel-btn";
    btn.textContent = "✦ Generate";
    btn.title = "AWB AI Content Generator";
    btn.style.cssText = `
			margin-left: 8px; padding: 4px 14px; font-size: 13px; font-weight: 600;
			background: #1a1a1a; color: #fff; border: none; border-radius: 4px;
			cursor: pointer; line-height: 1;
		`;
    btn.addEventListener("click", openPanel);
    toolbar.appendChild(btn);
  }

  function openPanel() {
    if (document.getElementById("awb-ai-panel")) {
      document.getElementById("awb-ai-panel").remove();
      return;
    }

    const panel = document.createElement("div");
    panel.id = "awb-ai-panel";
    panel.style.cssText = `
			position: fixed; top: 56px; right: 20px; z-index: 99999;
			width: 320px; background: #fff; border: 1px solid #e0e0e0;
			border-radius: 8px; box-shadow: 0 8px 32px rgba(0,0,0,.14);
			font-family: -apple-system, sans-serif; overflow: hidden;
		`;
    panel.innerHTML = `
			<div style="background:#1a1a1a;color:#fff;padding:12px 16px;display:flex;align-items:center;justify-content:space-between">
				<strong style="font-size:13px">✦ AWB AI Generator</strong>
				<button id="awb-ai-close" style="background:none;border:none;color:#fff;cursor:pointer;font-size:16px;line-height:1">×</button>
			</div>
			<div style="padding:16px">
				<label style="display:block;font-size:12px;font-weight:600;margin-bottom:6px;color:#555">Describe what you need</label>
				<textarea id="awb-ai-prompt" rows="4" placeholder="e.g. A hero section for a plumber in Amsterdam with a CTA button" style="width:100%;padding:8px;font-size:13px;border:1px solid #ddd;border-radius:4px;resize:vertical;box-sizing:border-box"></textarea>
				<button id="awb-ai-generate" style="margin-top:10px;width:100%;padding:9px;background:#1a1a1a;color:#fff;border:none;border-radius:4px;font-size:13px;font-weight:600;cursor:pointer">
					Generate blocks
				</button>
				<div id="awb-ai-status" style="margin-top:10px;font-size:12px;color:#666;min-height:18px"></div>
				<div id="awb-ai-result" style="display:none;margin-top:10px">
					<label style="display:block;font-size:12px;font-weight:600;margin-bottom:6px;color:#555">Generated markup</label>
					<textarea id="awb-ai-output" rows="6" readonly style="width:100%;padding:8px;font-size:11px;font-family:monospace;border:1px solid #ddd;border-radius:4px;resize:vertical;box-sizing:border-box;background:#f9f9f9"></textarea>
					<button id="awb-ai-copy" style="margin-top:6px;width:100%;padding:7px;background:#f0f0f0;border:1px solid #ddd;border-radius:4px;font-size:12px;cursor:pointer">
						Copy to clipboard
					</button>
					<p style="font-size:11px;color:#888;margin-top:6px">Paste into the block editor Code Editor (⋮ → Code editor) or a Custom HTML block.</p>
				</div>
			</div>
		`;

    document.body.appendChild(panel);

    document
      .getElementById("awb-ai-close")
      .addEventListener("click", () => panel.remove());
    document
      .getElementById("awb-ai-generate")
      .addEventListener("click", runGenerate);
    document
      .getElementById("awb-ai-copy")
      .addEventListener("click", copyOutput);
  }

  async function runGenerate() {
    const prompt = document.getElementById("awb-ai-prompt").value.trim();
    if (!prompt) {
      setStatus("Please enter a prompt.", "error");
      return;
    }

    const btn = document.getElementById("awb-ai-generate");
    btn.disabled = true;
    btn.textContent = "Generating…";
    setStatus("Calling AI…", "info");
    document.getElementById("awb-ai-result").style.display = "none";

    try {
      const body = new FormData();
      body.append("action", "awb_generate");
      body.append("nonce", AWB.nonce);
      body.append("prompt", prompt);

      const res = await fetch(AWB.ajaxUrl, { method: "POST", body });
      const json = await res.json();

      if (json.success) {
        document.getElementById("awb-ai-output").value = json.data.blocks;
        document.getElementById("awb-ai-result").style.display = "block";
        setStatus("Done! Copy the markup below.", "success");
      } else {
        setStatus("Error: " + (json.data?.message || "Unknown error"), "error");
      }
    } catch (err) {
      setStatus("Network error: " + err.message, "error");
    } finally {
      btn.disabled = false;
      btn.textContent = "Generate blocks";
    }
  }

  function copyOutput() {
    const out = document.getElementById("awb-ai-output");
    navigator.clipboard.writeText(out.value).then(() => {
      const btn = document.getElementById("awb-ai-copy");
      btn.textContent = "Copied!";
      setTimeout(() => (btn.textContent = "Copy to clipboard"), 2000);
    });
  }

  function setStatus(msg, type) {
    const el = document.getElementById("awb-ai-status");
    if (!el) return;
    const colors = { info: "#888", success: "#2e7d32", error: "#c62828" };
    el.style.color = colors[type] || "#888";
    el.textContent = msg;
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", () =>
      setTimeout(injectAIPanel, 1500),
    );
  } else {
    setTimeout(injectAIPanel, 1500);
  }
})();
