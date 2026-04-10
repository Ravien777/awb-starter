<?php

/**
 * Title: Vakman Landing Page
 * Slug: vakman-landingpage
 * Categories: awb-pages
 * Keywords: landing, vakman, hero
 * Description: Full landing page for Vakman-style projects.
 * CSS: assets/css/general/vakman-landing.css
 * JS:  assets/js/general/vakman-landing.js
 *
 * The CSS and JS lines above tell AWB Starter to load these files
 * ONLY on pages that use this pattern — not on every page.
 */
?>


<!-- wp:html -->
<div class="vakmannen-pattern">
    <!-- Hero -->
    <section class="hero">
        <div class="hero-bg"></div>
        <div class="hero-grid-bg"></div>
        <div class="container hero-content">
            <div class="hero-eyebrow">
                <div class="hero-eyebrow-dot"></div>
                Meer dan 1.200 vakmannen beschikbaar
            </div>
            <h1>Vind betrouwbare vakmannen <span class="highlight">bij u in de buurt</span></h1>
            <p class="hero-sub">Van loodgieter tot elektricien – wij verbinden u met vakkundige professionals uit uw eigen regio. Snel, eenvoudig en zonder gedoe.</p>

            <div class="search-pill">
                <input type="text" id="postcodeInput" placeholder="Uw postcode, bijv. 1012AB">
                <div class="search-divider"></div>
                <select id="klusSelect">
                    <option value="Loodgieter">Loodgieter / verstopping</option>
                    <option value="Elektricien">Elektricien / installatie</option>
                    <option value="Timmerman">Timmerman / meubelmaker</option>
                    <option value="Schilder">Schilder / behang</option>
                    <option value="Dakdekker">Dakdekker / reparatie</option>
                    <option value="Overig">Overige klus</option>
                </select>
                <button class="pill-btn" id="heroZoekBtn">Vind vakman <i class="fas fa-arrow-right"></i></button>
            </div>

            <div class="trust-row">
                <span class="trust-item"><i class="fas fa-check-circle"></i> Gratis offertes</span>
                <span class="trust-item"><i class="fas fa-shield-alt"></i> Gecontroleerde vakmannen</span>
                <span class="trust-item"><i class="fas fa-star star-icon"></i> 4.8/5 – 1.200+ reviews</span>
                <span class="trust-item"><i class="fas fa-clock"></i> Reactie binnen 24 uur</span>
            </div>
        </div>
    </section>

    <!-- Marquee -->
    <div class="marquee-section">
        <div class="marquee-track">
            <!-- repeated twice for infinite loop -->
            <span class="marquee-item"><i class="fas fa-wrench"></i> Loodgieters <span class="marquee-sep">—</span></span>
            <span class="marquee-item"><i class="fas fa-bolt"></i> Elektriciens <span class="marquee-sep">—</span></span>
            <span class="marquee-item"><i class="fas fa-paint-roller"></i> Schilders <span class="marquee-sep">—</span></span>
            <span class="marquee-item"><i class="fas fa-hammer"></i> Timmerlieden <span class="marquee-sep">—</span></span>
            <span class="marquee-item"><i class="fas fa-tree"></i> Hoveniers <span class="marquee-sep">—</span></span>
            <span class="marquee-item"><i class="fas fa-hard-hat"></i> Klusjeslieden <span class="marquee-sep">—</span></span>
            <span class="marquee-item"><i class="fas fa-home"></i> Dakdekkers <span class="marquee-sep">—</span></span>
            <span class="marquee-item"><i class="fas fa-door-open"></i> Keukenmonteurs <span class="marquee-sep">—</span></span>
            <!-- duplicate for seamless loop -->
            <span class="marquee-item"><i class="fas fa-wrench"></i> Loodgieters <span class="marquee-sep">—</span></span>
            <span class="marquee-item"><i class="fas fa-bolt"></i> Elektriciens <span class="marquee-sep">—</span></span>
            <span class="marquee-item"><i class="fas fa-paint-roller"></i> Schilders <span class="marquee-sep">—</span></span>
            <span class="marquee-item"><i class="fas fa-hammer"></i> Timmerlieden <span class="marquee-sep">—</span></span>
            <span class="marquee-item"><i class="fas fa-tree"></i> Hoveniers <span class="marquee-sep">—</span></span>
            <span class="marquee-item"><i class="fas fa-hard-hat"></i> Klusjeslieden <span class="marquee-sep">—</span></span>
            <span class="marquee-item"><i class="fas fa-home"></i> Dakdekkers <span class="marquee-sep">—</span></span>
            <span class="marquee-item"><i class="fas fa-door-open"></i> Keukenmonteurs <span class="marquee-sep">—</span></span>
        </div>
    </div>

    <!-- Stats -->
    <div class="container section-sm">
        <div class="stats-bar reveal" id="statsBar">
            <div class="stat-item">
                <div class="stat-num" data-target="4800">0</div>
                <div class="stat-label">Gecertificeerde vakmannen</div>
            </div>
            <div class="stat-item">
                <div class="stat-num" data-target="12400">0</div>
                <div class="stat-label">Klussen afgerond</div>
            </div>
            <div class="stat-item">
                <div class="stat-num" data-suffix="/5" data-float="4.8">4.8</div>
                <div class="stat-label">Gemiddelde beoordeling</div>
            </div>
            <div class="stat-item">
                <div class="stat-num" data-target="24" data-suffix="u">0</div>
                <div class="stat-label">Gemiddelde reactietijd</div>
            </div>
        </div>
    </div>

    <!-- Waarom ons -->
    <div class="container section">
        <div style="text-align:center;">
            <span class="section-label reveal">Onze voordelen</span>
            <h2 class="section-title reveal">Waarom voor ons kiezen?</h2>
            <p class="section-sub reveal">Wij maken klussen eenvoudig, lokaal en betrouwbaar.</p>
        </div>
        <div class="value-grid">
            <div class="value-card reveal reveal-delay-1">
                <div class="value-icon"><i class="fas fa-user-check"></i></div>
                <h3>Betrouwbaarheid</h3>
                <p>Elke vakman wordt door ons gecontroleerd op achtergrond, referenties en certificaten.</p>
            </div>
            <div class="value-card reveal reveal-delay-2">
                <div class="value-icon"><i class="fas fa-map-marker-alt"></i></div>
                <h3>Lokale verbinding</h3>
                <p>Werk met vakmannen uit uw eigen buurt – sneller en persoonlijker dan nationaal zoeken.</p>
            </div>
            <div class="value-card reveal reveal-delay-3">
                <div class="value-icon"><i class="fas fa-file-alt"></i></div>
                <h3>Eenvoud</h3>
                <p>Één formulier, meerdere offertes. Bespaar tijd en moeite – in 2 minuten geregeld.</p>
            </div>
            <div class="value-card reveal reveal-delay-4">
                <div class="value-icon"><i class="fas fa-medal"></i></div>
                <h3>Kwaliteit</h3>
                <p>Alleen ervaren vakmannen met aantoonbare expertise en bewezen trackrecord.</p>
            </div>
        </div>
    </div>

    <!-- Hoe het werkt -->
    <div id="hoehetwerkt" style="background: white;">
        <div class="container section">
            <div style="text-align:center;">
                <span class="section-label reveal">Stappenplan</span>
                <h2 class="section-title reveal">Hoe werkt het?</h2>
                <p class="section-sub reveal">In drie stappen klaar: uw klus, onze vakman.</p>
            </div>
            <div class="steps-grid">
                <div class="step-card reveal reveal-delay-1">
                    <div class="step-number">1</div>
                    <h3>Plaats uw klus</h3>
                    <p>Vul gratis uw postcode en klusomschrijving in. Binnen 2 minuten geregeld — geen account nodig.</p>
                </div>
                <div class="step-card reveal reveal-delay-2">
                    <div class="step-number">2</div>
                    <h3>Ontvang offertes</h3>
                    <p>Gekwalificeerde vakmannen uit uw regio sturen vrijblijvend en transparante offertes.</p>
                </div>
                <div class="step-card reveal reveal-delay-3">
                    <div class="step-number">3</div>
                    <h3>Kies & geniet</h3>
                    <p>Vergelijk, lees reviews en kies de vakman die het beste bij uw situatie past.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Over ons / Brand Story -->
    <div class="container section-sm" id="overons">
        <div class="story-section reveal">
            <div class="story-content">
                <div class="story-tag"><i class="fas fa-hammer"></i> Ons verhaal</div>
                <h3>Ontstaan uit frustratie, gebouwd met passie</h3>
                <p>Vakmannen Vinden is ontstaan uit een simpele frustratie: het is lastig om een goede loodgieter, elektricien of timmerman te vinden die snel kan komen en eerlijk is over prijzen.<br><br>Wij spraken met tientallen huiseigenaren en hoorden dezelfde verhalen: te veel gedoe, onduidelijke offertes, en soms zelfs oplichterij. Daarom bouwden wij een platform dat u direct verbindt met vakkundige vakmannen uit uw eigen buurt.</p>
            </div>
            <div class="story-cards">
                <div class="story-card"><strong><i class="fas fa-bullseye"></i> Onze missie</strong>
                    <p>Wij verbinden huiseigenaren met betrouwbare vakmannen in hun eigen regio – snel, eenvoudig en zonder gedoe.</p>
                </div>
                <div class="story-card"><strong><i class="fas fa-eye"></i> Onze visie</strong>
                    <p>Het meest vertrouwde platform voor klussen in huis, waar elke vakman en klant een perfecte match vindt.</p>
                </div>
                <div class="story-card"><strong><i class="fas fa-heart"></i> Onze waarden</strong>
                    <p>Transparantie, kwaliteit en lokale verbinding staan altijd centraal in alles wat wij doen.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Vakgebieden -->
    <div class="container section-sm">
        <div style="text-align:center;">
            <span class="section-label reveal">Specialisaties</span>
            <h2 class="section-title reveal">Populaire vakgebieden</h2>
            <p class="section-sub reveal">Vakmannen voor elke klus in en om uw huis.</p>
        </div>
        <div class="cat-grid">
            <div class="cat-card reveal reveal-delay-1">
                <div class="cat-icon"><i class="fas fa-wrench"></i></div>
                <div><span>Loodgieters & CV</span><small>Verwarming, sanitair, verstoppingen</small></div>
            </div>
            <div class="cat-card reveal reveal-delay-2">
                <div class="cat-icon"><i class="fas fa-bolt"></i></div>
                <div><span>Elektriciens</span><small>Installaties, meterkast, bedrading</small></div>
            </div>
            <div class="cat-card reveal reveal-delay-3">
                <div class="cat-icon"><i class="fas fa-paint-roller"></i></div>
                <div><span>Schilders & stukadoors</span><small>Binnen, buiten, behangen</small></div>
            </div>
            <div class="cat-card reveal reveal-delay-1">
                <div class="cat-icon"><i class="fas fa-tree"></i></div>
                <div><span>Tuin & Hovenier</span><small>Aanleg, onderhoud, snoeien</small></div>
            </div>
            <div class="cat-card reveal reveal-delay-2">
                <div class="cat-icon"><i class="fas fa-hard-hat"></i></div>
                <div><span>Klusjesman & renovatie</span><small>Verbouw, reparaties, algemeen</small></div>
            </div>
            <div class="cat-card reveal reveal-delay-3">
                <div class="cat-icon"><i class="fas fa-door-open"></i></div>
                <div><span>Timmerwerk & keuken</span><small>Deuren, meubels, keukenmontage</small></div>
            </div>
        </div>
    </div>

    <!-- Reviews -->
    <div class="reviews-bg">
        <div class="container section-sm">
            <div style="text-align:center;">
                <span class="section-label reveal">Beoordelingen</span>
                <h2 class="section-title reveal">Wat klanten zeggen</h2>
                <p class="section-sub reveal">Meer dan 1.200 tevreden klanten gingen u voor.</p>
            </div>
            <div class="reviews-grid">
                <div class="review-card reveal reveal-delay-1">
                    <div class="stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
                    <p class="review-text">"Binnen een dag een betrouwbare loodgieter gevonden via Vakmannen Vinden. Heldere offerte en keurig werk geleverd. Echt een aanrader voor iedereen!"</p>
                    <div class="reviewer">
                        <div class="reviewer-avatar">A</div>
                        <div>
                            <div class="reviewer-name">Annelies van den Berg</div>
                            <div class="reviewer-location">Utrecht</div>
                        </div>
                    </div>
                </div>
                <div class="review-card reveal reveal-delay-2">
                    <div class="stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star-half-alt"></i></div>
                    <p class="review-text">"Super ervaring! Geen gedoe met eindeloos zoeken, gewoon offertes ontvangen van lokale vakmannen. Mijn schilder is perfect – werk van topkwaliteit."</p>
                    <div class="reviewer">
                        <div class="reviewer-avatar">M</div>
                        <div>
                            <div class="reviewer-name">Marc Jansen</div>
                            <div class="reviewer-location">Rotterdam</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- CTA -->
    <div class="container section-sm">
        <div class="cta-section reveal">
            <div class="cta-content">
                <h2>Klaar om uw klus te klaren?</h2>
                <p>Plaats gratis uw klus en ontvang meerdere offertes van vakmannen bij u in de buurt.</p>
                <div class="cta-btns">
                    <button class="btn-cta-primary" id="ctaPlaatsKlusBtn"><i class="fas fa-pencil-alt"></i> Start nu – gratis offertes</button>
                    <button class="btn-cta-secondary" id="ctaVakmanBtn">Ik ben een vakman</button>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- /wp:html -->