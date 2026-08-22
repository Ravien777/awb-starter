/**
 * AWB Starter – Pattern Store UI
 */
(function () {
  "use strict";

  const STORE_MANIFEST_URL = "https://your-site.com/awb-patterns/manifest.json"; // Update with your actual URL

  document.addEventListener("DOMContentLoaded", function () {
    const grid = document.getElementById("awb-store-grid");
    if (!grid) return;

    fetchPatterns(grid);
  });

  function fetchPatterns(grid) {
    fetch(STORE_MANIFEST_URL)
      .then((response) => {
        if (!response.ok) throw new Error("Network response was not ok");
        return response.json();
      })
      .then((data) => {
        if (data.patterns && Array.isArray(data.patterns)) {
          renderPatterns(grid, data.patterns);
        } else {
          grid.innerHTML = "<p>No patterns available.</p>";
        }
      })
      .catch((error) => {
        grid.innerHTML =
          "<p>Failed to load patterns. Please try again later.</p>";
        console.error(error);
      });
  }

  function renderPatterns(grid, patterns) {
    const template = document.getElementById("awb-store-card-template");
    grid.innerHTML = "";

    patterns.forEach((pattern) => {
      const card = template.content.cloneNode(true);
      const img = card.querySelector(".awb-store-card__image img");
      img.src = pattern.thumbnail || "";
      img.alt = pattern.title;
      card.querySelector(".awb-store-card__title").textContent = pattern.title;
      card.querySelector(".awb-store-card__desc").textContent =
        pattern.description || "";
      card.querySelector(".awb-store-card__version").textContent =
        "v" + (pattern.version || "1.0.0");
      card.querySelector(".awb-store-card__author").textContent =
        pattern.author || "AWB Team";
      const installBtn = card.querySelector(".awb-install-pattern");
      installBtn.dataset.url = pattern.download_url;

      grid.appendChild(card);
    });

    // Attach install handlers
    grid.addEventListener("click", function (e) {
      const btn = e.target.closest(".awb-install-pattern");
      if (!btn) return;
      handleInstall(btn);
    });
  }

  function handleInstall(btn) {
    const url = btn.dataset.url;
    const card = btn.closest(".awb-store-card");
    const statusEl = card.querySelector(".awb-install-status");

    btn.disabled = true;
    btn.textContent = "Installing…";
    statusEl.textContent = "";

    const formData = new FormData();
    formData.append("action", "awb_install_remote_pattern");
    formData.append("nonce", awbStore.nonce); // to be localized
    formData.append("url", url);

    fetch(ajaxurl, {
      method: "POST",
      body: formData,
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          statusEl.textContent = "✓ Installed!";
          statusEl.style.color = "green";
          btn.textContent = "Installed";
          btn.disabled = true;
        } else {
          let errorMsg = data.data?.message || "Installation failed.";
          if (data.data?.code === "collision") {
            errorMsg = "Pattern already exists.";
          }
          statusEl.textContent = "✗ " + errorMsg;
          statusEl.style.color = "red";
          btn.disabled = false;
          btn.textContent = "Install";
        }
      })
      .catch((error) => {
        statusEl.textContent = "✗ Network error.";
        statusEl.style.color = "red";
        btn.disabled = false;
        btn.textContent = "Install";
        console.error(error);
      });
  }
})();
