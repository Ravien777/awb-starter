/**
 * AWB – Transparent header scroll behaviour.
 * Adds .is-scrolled to .awb-header--transparent when page is scrolled.
 * The CSS in header-transparent.css transitions it to a solid frosted-glass bar.
 */
(function () {
  const header = document.querySelector(".awb-header--transparent");
  if (!header) return;

  const THRESHOLD = 60;

  function update() {
    header.classList.toggle("is-scrolled", window.scrollY > THRESHOLD);
  }

  window.addEventListener("scroll", update, { passive: true });
  update();
})();
