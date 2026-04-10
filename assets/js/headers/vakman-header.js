// ─── STICKY HEADER ───
const header = document.getElementById("siteHeader");
window.addEventListener("scroll", () => {
  header.classList.toggle("scrolled", window.scrollY > 20);
});

// ─── HAMBURGER ───
const ham = document.getElementById("hamburger");
const mobileNav = document.getElementById("mobileNav");
ham.addEventListener("click", () => {
  ham.classList.toggle("open");
  mobileNav.classList.toggle("open");
});
