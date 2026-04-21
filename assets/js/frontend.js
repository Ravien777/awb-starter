/* ═══════════════════════════════════════════════════
   GLOBAL JS – Scroll reveal & counter animation
   ═══════════════════════════════════════════════════ */
(function () {
  // Scroll reveal
  const revealObs = new IntersectionObserver(
    (entries) => {
      entries.forEach((e) => {
        if (e.isIntersecting) {
          e.target.classList.add("visible");
          revealObs.unobserve(e.target);
        }
      });
    },
    { threshold: 0.1 },
  );
  document.querySelectorAll(".reveal").forEach((el) => revealObs.observe(el));

  // Counter animation
  const countObs = new IntersectionObserver(
    (entries) => {
      entries.forEach((e) => {
        if (!e.isIntersecting) return;
        const el = e.target;
        const target = parseInt(el.dataset.target);
        const suffix = el.dataset.suffix || "";
        const prefix = el.dataset.prefix || "";
        const dur = 1800;
        const start = performance.now();
        const tick = (now) => {
          const p = Math.min((now - start) / dur, 1);
          const val = Math.floor(p * p * target);
          el.textContent = prefix + val.toLocaleString("nl-NL") + suffix;
          if (p < 1) requestAnimationFrame(tick);
          else
            el.textContent = prefix + target.toLocaleString("nl-NL") + suffix;
        };
        requestAnimationFrame(tick);
        countObs.unobserve(el);
      });
    },
    { threshold: 0.5 },
  );
  document
    .querySelectorAll(".g-stat-num[data-target]")
    .forEach((c) => countObs.observe(c));

  // Global FAQ accordion
  document.querySelectorAll(".g-faq-q").forEach((btn) => {
    btn.addEventListener("click", () => {
      const expanded = btn.getAttribute("aria-expanded") === "true";
      // Close all in same parent container
      btn
        .closest(".g-faq-item")
        .parentElement.querySelectorAll(".g-faq-q")
        .forEach((b) => {
          b.setAttribute("aria-expanded", "false");
          b.nextElementSibling.style.maxHeight = "0";
        });
      if (!expanded) {
        btn.setAttribute("aria-expanded", "true");
        btn.nextElementSibling.style.maxHeight =
          btn.nextElementSibling.scrollHeight + "px";
      }
    });
  });
})();
