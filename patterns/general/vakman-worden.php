<?php
/**
 * Title: Vakman Worden
 * Slug: vakman-worden
 * Categories: awb-pages
 * Keywords: vakman, registratie, worden, aanmelden, professional
 * Description: Landing page for tradespeople who want to join the platform.
 * CSS: assets/css/general/vakman-worden.css
 * JS:  assets/js/general/vakman-worden.js
 */
?>

<!-- wp:html -->
<div class="vakman-worden-pattern">

    <!-- ── Hero ─────────────────────────────────────────────────────── -->
    <section class="vw-hero">
        <div class="vw-hero-bg"></div>
        <div class="vw-hero-grid-bg"></div>
        <div class="container vw-hero-content">
            <div class="vw-hero-eyebrow">
                <div class="vw-hero-eyebrow-dot"></div>
                Meer dan 4.800 vakmannen gingen u voor
            </div>
            <h1>Groei uw bedrijf met<br><span class="vw-highlight">nieuwe klanten</span> via ons platform</h1>
            <p class="vw-hero-sub">Meld u gratis aan als vakman en ontvang direct relevante klusopdrachten uit uw regio. Geen abonnement — u betaalt alleen voor leads die u kiest.</p>
            <div class="vw-hero-ctas">
                <button class="vw-btn-primary" id="vwHeroRegisterBtn">
                    <i class="fas fa-user-plus"></i> Gratis aanmelden
                </button>
                <button class="vw-btn-secondary" id="vwHeroMoreBtn">
                    Meer weten <i class="fas fa-arrow-down"></i>
                </button>
            </div>
            <div class="vw-trust-row">
                <span class="vw-trust-item"><i class="fas fa-check-circle"></i> Geen maandelijks abonnement</span>
                <span class="vw-trust-item"><i class="fas fa-map-marker-alt"></i> Alleen klussen in uw regio</span>
                <span class="vw-trust-item"><i class="fas fa-star vw-star-icon"></i> 4.8/5 – vakmansbeoordeling</span>
                <span class="vw-trust-item"><i class="fas fa-bolt"></i> Direct zichtbaar voor klanten</span>
            </div>
        </div>
    </section>

    <!-- ── Marquee ───────────────────────────────────────────────────── -->
    <div class="vw-marquee-section">
        <div class="vw-marquee-track">
            <span class="vw-marquee-item"><i class="fas fa-wrench"></i> Loodgieters <span class="vw-marquee-sep">—</span></span>
            <span class="vw-marquee-item"><i class="fas fa-bolt"></i> Elektriciens <span class="vw-marquee-sep">—</span></span>
            <span class="vw-marquee-item"><i class="fas fa-paint-roller"></i> Schilders <span class="vw-marquee-sep">—</span></span>
            <span class="vw-marquee-item"><i class="fas fa-hammer"></i> Timmerlieden <span class="vw-marquee-sep">—</span></span>
            <span class="vw-marquee-item"><i class="fas fa-tree"></i> Hoveniers <span class="vw-marquee-sep">—</span></span>
            <span class="vw-marquee-item"><i class="fas fa-hard-hat"></i> Klusjeslieden <span class="vw-marquee-sep">—</span></span>
            <span class="vw-marquee-item"><i class="fas fa-home"></i> Dakdekkers <span class="vw-marquee-sep">—</span></span>
            <span class="vw-marquee-item"><i class="fas fa-door-open"></i> Keukenmonteurs <span class="vw-marquee-sep">—</span></span>
            <!-- duplicate for seamless loop -->
            <span class="vw-marquee-item"><i class="fas fa-wrench"></i> Loodgieters <span class="vw-marquee-sep">—</span></span>
            <span class="vw-marquee-item"><i class="fas fa-bolt"></i> Elektriciens <span class="vw-marquee-sep">—</span></span>
            <span class="vw-marquee-item"><i class="fas fa-paint-roller"></i> Schilders <span class="vw-marquee-sep">—</span></span>
            <span class="vw-marquee-item"><i class="fas fa-hammer"></i> Timmerlieden <span class="vw-marquee-sep">—</span></span>
            <span class="vw-marquee-item"><i class="fas fa-tree"></i> Hoveniers <span class="vw-marquee-sep">—</span></span>
            <span class="vw-marquee-item"><i class="fas fa-hard-hat"></i> Klusjeslieden <span class="vw-marquee-sep">—</span></span>
            <span class="vw-marquee-item"><i class="fas fa-home"></i> Dakdekkers <span class="vw-marquee-sep">—</span></span>
            <span class="vw-marquee-item"><i class="fas fa-door-open"></i> Keukenmonteurs <span class="vw-marquee-sep">—</span></span>
        </div>
    </div>

    <!-- ── Stats bar ─────────────────────────────────────────────────── -->
    <div class="container section-sm">
        <div class="vw-stats-bar reveal" id="vwStatsBar">
            <div class="vw-stat-item">
                <div class="vw-stat-num" data-target="4800">0</div>
                <div class="vw-stat-label">Actieve vakmannen</div>
            </div>
            <div class="vw-stat-item">
                <div class="vw-stat-num" data-target="12400">0</div>
                <div class="vw-stat-label">Klussen dit jaar</div>
            </div>
            <div class="vw-stat-item">
                <div class="vw-stat-num" data-prefix="€" data-target="3200">0</div>
                <div class="vw-stat-label">Gem. omzet per vakman/mnd</div>
            </div>
            <div class="vw-stat-item">
                <div class="vw-stat-num" data-target="92" data-suffix="%">0</div>
                <div class="vw-stat-label">Tevreden vakmannen</div>
            </div>
        </div>
    </div>

    <!-- ── Voordelen ─────────────────────────────────────────────────── -->
    <div class="container section">
        <div style="text-align:center;">
            <span class="vw-section-label reveal">Waarom ons platform?</span>
            <h2 class="vw-section-title reveal">Meer klanten, minder gedoe</h2>
            <p class="vw-section-sub reveal">Wij regelen de marketing — u focust op het vakwerk.</p>
        </div>
        <div class="vw-value-grid">
            <div class="vw-value-card reveal reveal-delay-1">
                <div class="vw-value-icon"><i class="fas fa-euro-sign"></i></div>
                <h3>Geen abonnement</h3>
                <p>U betaalt alleen voor de leads die u zelf accepteert. Geen verborgen kosten, geen maandelijkse vaste lasten.</p>
            </div>
            <div class="vw-value-card reveal reveal-delay-2">
                <div class="vw-value-icon"><i class="fas fa-map-marker-alt"></i></div>
                <h3>Lokale klussen</h3>
                <p>Ontvang alleen opdrachten in de postcodegebieden die u zelf instelt. Nooit meer onnodig ver rijden.</p>
            </div>
            <div class="vw-value-card reveal reveal-delay-3">
                <div class="vw-value-icon"><i class="fas fa-star"></i></div>
                <h3>Bouw uw reputatie</h3>
                <p>Verzamel reviews van echte klanten en word zichtbaarder voor nieuwe opdrachten in uw vakgebied.</p>
            </div>
            <div class="vw-value-card reveal reveal-delay-4">
                <div class="vw-value-icon"><i class="fas fa-headset"></i></div>
                <h3>Persoonlijke support</h3>
                <p>Ons team staat voor u klaar via telefoon, chat of e-mail. U staat er niet alleen voor.</p>
            </div>
        </div>
    </div>

    <!-- ── Hoe werkt aanmelden ───────────────────────────────────────── -->
    <div style="background: var(--awb-color-bg, #fff); border-top: 1px solid #f0f0f0; border-bottom: 1px solid #f0f0f0;">
        <div class="container section">
            <div style="text-align:center;">
                <span class="vw-section-label reveal">Stappenplan</span>
                <h2 class="vw-section-title reveal">Aanmelden in 3 stappen</h2>
                <p class="vw-section-sub reveal">Van aanmelding tot eerste klus in minder dan een dag.</p>
            </div>
            <div class="vw-steps-grid">
                <div class="vw-step-card reveal reveal-delay-1">
                    <div class="vw-step-number">1</div>
                    <h3>Maak een profiel</h3>
                    <p>Vul uw vakgebied, werkgebied en ervaring in. Upload uw KvK-nummer en eventuele certificaten. Duurt 5 minuten.</p>
                </div>
                <div class="vw-step-card reveal reveal-delay-2">
                    <div class="vw-step-number">2</div>
                    <h3>Ontvang klusopdrachten</h3>
                    <p>Zodra uw profiel is goedgekeurd (binnen 24u) ontvangt u direct relevante aanvragen uit uw regio.</p>
                </div>
                <div class="vw-step-card reveal reveal-delay-3">
                    <div class="vw-step-number">3</div>
                    <h3>Stuur een offerte & werk</h3>
                    <p>Kies de klussen die bij u passen, stuur een offerte en ga aan de slag. Na afloop bouwt u reviews op.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Prijzen / Lead model ─────────────────────────────────────── -->
    <div class="container section-sm">
        <div style="text-align:center;">
            <span class="vw-section-label reveal">Transparante prijzen</span>
            <h2 class="vw-section-title reveal">U betaalt alleen voor wat u kiest</h2>
            <p class="vw-section-sub reveal">Geen abonnement. Geen verrassing. Alleen resultaat.</p>
        </div>
        <div class="vw-pricing-grid">
            <div class="vw-pricing-card reveal reveal-delay-1">
                <div class="vw-pricing-badge">Gratis</div>
                <h3>Profiel aanmaken</h3>
                <div class="vw-pricing-price">€0</div>
                <ul class="vw-pricing-list">
                    <li><i class="fas fa-check"></i> Profiel zichtbaar voor klanten</li>
                    <li><i class="fas fa-check"></i> Reviews ontvangen</li>
                    <li><i class="fas fa-check"></i> Werkgebied instellen</li>
                    <li><i class="fas fa-check"></i> Onbeperkt browsen</li>
                </ul>
            </div>
            <div class="vw-pricing-card vw-pricing-card--featured reveal reveal-delay-2">
                <div class="vw-pricing-badge vw-pricing-badge--accent">Meest gekozen</div>
                <h3>Lead accepteren</h3>
                <div class="vw-pricing-price">€4 – €12 <span>per lead</span></div>
                <p class="vw-pricing-note">Afhankelijk van klus­type en regio. U ziet de prijs altijd vóór acceptatie.</p>
                <ul class="vw-pricing-list">
                    <li><i class="fas fa-check"></i> Volledige klantgegevens</li>
                    <li><i class="fas fa-check"></i> Directe chat met klant</li>
                    <li><i class="fas fa-check"></i> Max. 3 vakmannen per lead</li>
                    <li><i class="fas fa-check"></i> Geen abonnement nodig</li>
                </ul>
                <button class="vw-btn-primary" style="width:100%; margin-top: 1.5rem;" id="vwPricingRegisterBtn">
                    Nu aanmelden <i class="fas fa-arrow-right"></i>
                </button>
            </div>
            <div class="vw-pricing-card reveal reveal-delay-3">
                <div class="vw-pricing-badge">Pro</div>
                <h3>Pro abonnement</h3>
                <div class="vw-pricing-price">€49 <span>/mnd</span></div>
                <ul class="vw-pricing-list">
                    <li><i class="fas fa-check"></i> Onbeperkte leads</li>
                    <li><i class="fas fa-check"></i> Uitgelicht profiel</li>
                    <li><i class="fas fa-check"></i> Prioriteit in zoekresultaten</li>
                    <li><i class="fas fa-check"></i> Maandelijkse rapportage</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- ── Vakman reviews ─────────────────────────────────────────────── -->
    <div class="vw-reviews-bg">
        <div class="container section-sm">
            <div style="text-align:center;">
                <span class="vw-section-label reveal">Ervaringen</span>
                <h2 class="vw-section-title reveal">Wat vakmannen zeggen</h2>
                <p class="vw-section-sub reveal">Echte verhalen van vakmannen die al via ons platform werken.</p>
            </div>
            <div class="vw-reviews-grid">
                <div class="vw-review-card reveal reveal-delay-1">
                    <div class="vw-stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
                    <p class="vw-review-text">"Via dit platform krijg ik wekelijks nieuwe klanten in mijn regio. De leads zijn gericht en van goede kwaliteit – ik hoef zelf bijna geen acquisitie meer te doen."</p>
                    <div class="vw-reviewer">
                        <div class="vw-reviewer-avatar">R</div>
                        <div>
                            <div class="vw-reviewer-name">Roel de Vries</div>
                            <div class="vw-reviewer-location">Loodgieter · Amsterdam</div>
                        </div>
                    </div>
                </div>
                <div class="vw-review-card reveal reveal-delay-2">
                    <div class="vw-stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
                    <p class="vw-review-text">"Ik was sceptisch over betalen per lead, maar het verdient zich altijd terug. Één klus levert al snel €200+ op. De app is ook super overzichtelijk."</p>
                    <div class="vw-reviewer">
                        <div class="vw-reviewer-avatar">F</div>
                        <div>
                            <div class="vw-reviewer-name">Fatima El Azzaoui</div>
                            <div class="vw-reviewer-location">Elektricien · Den Haag</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Registratie formulier ─────────────────────────────────────── -->
    <div class="container section-sm" id="vwRegister">
        <div class="vw-form-section reveal">
            <div class="vw-form-header">
                <span class="vw-section-label" style="justify-content:center;">Aanmelden</span>
                <h2>Start vandaag nog gratis</h2>
                <p>Vul uw gegevens in en wij nemen binnen 24 uur contact met u op voor verificatie.</p>
            </div>
            <form class="vw-register-form" id="vwRegisterForm">
                <div class="vw-form-row">
                    <div class="vw-form-group">
                        <label for="vwNaam">Volledige naam *</label>
                        <input type="text" id="vwNaam" placeholder="Jan de Boer" required>
                    </div>
                    <div class="vw-form-group">
                        <label for="vwEmail">E-mailadres *</label>
                        <input type="email" id="vwEmail" placeholder="jan@mijnbedrijf.nl" required>
                    </div>
                </div>
                <div class="vw-form-row">
                    <div class="vw-form-group">
                        <label for="vwTelefoon">Telefoonnummer *</label>
                        <input type="tel" id="vwTelefoon" placeholder="06 12 34 56 78" required>
                    </div>
                    <div class="vw-form-group">
                        <label for="vwVakgebied">Vakgebied *</label>
                        <select id="vwVakgebied" required>
                            <option value="">Kies uw specialisatie</option>
                            <option>Loodgieter / CV-monteur</option>
                            <option>Elektricien</option>
                            <option>Schilder / Stukadoor</option>
                            <option>Timmerman / Meubelmaker</option>
                            <option>Dakdekker</option>
                            <option>Hovenier</option>
                            <option>Klusjesman / Renovatie</option>
                            <option>Overig</option>
                        </select>
                    </div>
                </div>
                <div class="vw-form-row">
                    <div class="vw-form-group">
                        <label for="vwPostcode">Werkpostcode (basis) *</label>
                        <input type="text" id="vwPostcode" placeholder="1012 AB" required>
                    </div>
                    <div class="vw-form-group">
                        <label for="vwKvk">KvK-nummer</label>
                        <input type="text" id="vwKvk" placeholder="12345678">
                    </div>
                </div>
                <div class="vw-form-group vw-form-group--full">
                    <label for="vwBericht">Korte omschrijving van uw bedrijf</label>
                    <textarea id="vwBericht" rows="3" placeholder="Beschrijf uw ervaring, specialisaties en werkgebied…"></textarea>
                </div>
                <div class="vw-form-check">
                    <input type="checkbox" id="vwAkkoord" required>
                    <label for="vwAkkoord">Ik ga akkoord met de <a href="/voorwaarden">algemene voorwaarden</a> en het <a href="/privacy">privacybeleid</a>.</label>
                </div>
                <button type="submit" class="vw-btn-primary vw-btn-submit" id="vwSubmitBtn">
                    <i class="fas fa-paper-plane"></i> Aanmelding versturen
                </button>
                <div class="vw-form-success" id="vwFormSuccess" style="display:none;">
                    <i class="fas fa-check-circle"></i> Bedankt! Wij nemen binnen 24 uur contact met u op.
                </div>
            </form>
        </div>
    </div>

    <!-- ── FAQ ──────────────────────────────────────────────────────── -->
    <div style="background: #f8f8f6; border-top: 1px solid #f0f0f0;">
        <div class="container section-sm">
            <div style="text-align:center;">
                <span class="vw-section-label reveal">Veelgestelde vragen</span>
                <h2 class="vw-section-title reveal">Alles wat u wilt weten</h2>
            </div>
            <div class="vw-faq" id="vwFaq">
                <div class="vw-faq-item reveal reveal-delay-1">
                    <button class="vw-faq-q" aria-expanded="false">Moet ik een KvK-nummer hebben?</button>
                    <div class="vw-faq-a"><p>Een KvK-nummer is geen harde vereiste om u aan te melden, maar vergroot wel uw geloofwaardigheid bij klanten. Vakmannen met een KvK-nummer worden sneller goedgekeurd en krijgen een verificatiebadge op hun profiel.</p></div>
                </div>
                <div class="vw-faq-item reveal reveal-delay-2">
                    <button class="vw-faq-q" aria-expanded="false">Hoe snel ontvang ik mijn eerste lead?</button>
                    <div class="vw-faq-a"><p>Na goedkeuring van uw profiel (gemiddeld binnen 24 uur) begint u direct klusopdrachten te ontvangen die overeenkomen met uw vakgebied en werkgebied. In drukke regio's kunnen dit er meerdere per dag zijn.</p></div>
                </div>
                <div class="vw-faq-item reveal reveal-delay-3">
                    <button class="vw-faq-q" aria-expanded="false">Wat als een lead van slechte kwaliteit is?</button>
                    <div class="vw-faq-a"><p>Als een lead aantoonbaar incorrect of onbereikbaar is, kunt u dit melden. Wij beoordelen elke melding en storten het leadbedrag terug als de klacht gegrond is. Uw tevredenheid staat voorop.</p></div>
                </div>
                <div class="vw-faq-item reveal reveal-delay-4">
                    <button class="vw-faq-q" aria-expanded="false">Kan ik mijn werkgebied aanpassen?</button>
                    <div class="vw-faq-a"><p>Ja, u kunt op elk moment uw werkgebied uitbreiden of verkleinen via uw profielinstellingen. U kiest zelf op postcodeniveau waar u wilt werken.</p></div>
                </div>
            </div>
        </div>
    </div>

</div>
<!-- /wp:html -->
