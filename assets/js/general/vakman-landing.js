// ─── SCROLL REVEAL ───
const reveals = document.querySelectorAll(".reveal");
const revealObs = new IntersectionObserver(
  (entries) => {
    entries.forEach((e) => {
      if (e.isIntersecting) {
        e.target.classList.add("visible");
        revealObs.unobserve(e.target);
      }
    });
  },
  { threshold: 0.12 },
);
reveals.forEach((el) => revealObs.observe(el));

// ─── COUNTER ANIMATION ───
const counters = document.querySelectorAll(".stat-num[data-target]");
const countObs = new IntersectionObserver(
  (entries) => {
    entries.forEach((e) => {
      if (!e.isIntersecting) return;
      const el = e.target;
      const target = parseInt(el.dataset.target);
      const suffix = el.dataset.suffix || "";
      const dur = 1800;
      const start = performance.now();
      const tick = (now) => {
        const p = Math.min((now - start) / dur, 1);
        const val = Math.floor(p * p * target);
        el.textContent = val.toLocaleString("nl-NL") + suffix;
        if (p < 1) requestAnimationFrame(tick);
        else el.textContent = target.toLocaleString("nl-NL") + suffix;
      };
      requestAnimationFrame(tick);
      countObs.unobserve(el);
    });
  },
  { threshold: 0.5 },
);
counters.forEach((c) => countObs.observe(c));

// ─── DEMO ALERTS ───
const demo = (msg) => alert(`📢 ${msg}`);
document.getElementById("heroZoekBtn").addEventListener("click", () => {
  const pc =
    document.getElementById("postcodeInput").value.trim() || "onbekend";
  const sel = document.getElementById("klusSelect");
  const klus = sel.options[sel.selectedIndex].text;
  demo(
    `Dank voor uw aanvraag! Voor postcode ${pc} en "${klus}" zoeken wij direct vakmannen in uw regio. Normaal ontvangt u binnen 24 uur 2-3 offertes.`,
  );
});
const plaatsKlus = () =>
  demo(
    "Plaats gratis uw klus: Vul eenvoudig uw klusgegevens in en ontvang vrijblijvend offertes van vakmannen uit uw buurt. (Demo functionaliteit)",
  );
const wordLid = () =>
  demo(
    "Word gratis lid als vakman! Meld u aan en ontvang klussen uit uw regio. Geen kosten, alleen matches. (Demo registratie)",
  );
document.getElementById("plaatsKlusNavBtn")?.addEventListener("click", (e) => {
  e.preventDefault();
  plaatsKlus();
});
document
  .getElementById("ctaPlaatsKlusBtn")
  ?.addEventListener("click", plaatsKlus);
document.getElementById("ctaVakmanBtn")?.addEventListener("click", wordLid);
document.getElementById("wordLidTopbarBtn")?.addEventListener("click", (e) => {
  e.preventDefault();
  wordLid();
});
document.getElementById("vakmanWordenNav")?.addEventListener("click", (e) => {
  e.preventDefault();
  wordLid();
});
document
  .getElementById("vakmanWordenMobile")
  ?.addEventListener("click", (e) => {
    e.preventDefault();
    wordLid();
  });
document.getElementById("plaatsKlusMobile")?.addEventListener("click", (e) => {
  e.preventDefault();
  plaatsKlus();
});
document.getElementById("contactNav")?.addEventListener("click", (e) => {
  e.preventDefault();
  demo(
    "Ons klantenteam is bereikbaar op werkdagen 9-17 uur via 088 123 4567 of info@vakmannenvinden.nl",
  );
});
document.getElementById("contactMobile")?.addEventListener("click", (e) => {
  e.preventDefault();
  demo(
    "Ons klantenteam is bereikbaar op werkdagen 9-17 uur via 088 123 4567 of info@vakmannenvinden.nl",
  );
});
