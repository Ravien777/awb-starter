<?php
/**
 * Title: Hoe het Werkt
 * Slug: hoe-het-werkt
 * Categories: awb-pages
 * Keywords: uitleg, stappenplan, hoe werkt het, proces, werking
 * Description: Detailed explanation page of how the platform works, for both homeowners and tradespeople.
 * CSS: assets/css/general/hoe-het-werkt.css
 * JS:  assets/js/general/hoe-het-werkt.js
 */
?>

<!-- wp:html -->
<div class="hoe-het-werkt-pattern">

    <!-- ── Hero ─────────────────────────────────────────────────── -->
    <section class="hhw-hero">
        <div class="hhw-hero-bg"></div>
        <div class="hhw-hero-grid-bg"></div>
        <div class="container hhw-hero-content">
            <div class="hhw-hero-eyebrow">
                <div class="hhw-hero-eyebrow-dot"></div>
                Transparant, snel en eenvoudig
            </div>
            <h1>Hoe werkt<br><span class="hhw-highlight">Vakmannen Vinden?</span></h1>
            <p class="hhw-hero-sub">In drie stappen van klus naar klaar. Ontdek hoe ons platform zowel huiseigenaren als vakmannen snel en eerlijk met elkaar verbindt.</p>
            <div class="hhw-tab-switcher" id="hhwTabSwitcher">
                <button class="hhw-tab active" data-tab="klant">Ik zoek een vakman</button>
                <button class="hhw-tab" data-tab="vakman">Ik ben een vakman</button>
            </div>
        </div>
    </section>

    <!-- ── Klant-flow ─────────────────────────────────────────── -->
    <div class="hhw-tab-panel" id="hhwPanelKlant">

        <!-- Numbered steps -->
        <div class="container section">
            <div class="hhw-steps">

                <div class="hhw-step reveal">
                    <div class="hhw-step-aside">
                        <div class="hhw-step-num">01</div>
                        <div class="hhw-step-line"></div>
                    </div>
                    <div class="hhw-step-body">
                        <span class="hhw-step-tag">Stap 1</span>
                        <h2>Beschrijf uw klus</h2>
                        <p>Vul gratis uw postcode en een korte beschrijving van de klus in. Geen account nodig — het kost u minder dan 2 minuten. Vermeld bij voorkeur: wat is er precies aan de hand, hoe spoedeisend is het, en heeft u een indicatief budget?</p>
                        <div class="hhw-step-tips">
                            <div class="hhw-tip"><i class="fas fa-lightbulb"></i> <span>Hoe specifieker uw beschrijving, hoe nauwkeuriger de offertes.</span></div>
                            <div class="hhw-tip"><i class="fas fa-lightbulb"></i> <span>Foto's toevoegen (optioneel) helpen de vakman direct inschatten wat er nodig is.</span></div>
                        </div>
                        <a href="/plaats-uw-klus" class="hhw-btn-primary">Klus plaatsen <i class="fas fa-arrow-right"></i></a>
                    </div>
                    <div class="hhw-step-visual hhw-step-visual--1">
                        <div class="hhw-mockup">
                            <div class="hhw-mockup-bar">
                                <span></span><span></span><span></span>
                            </div>
                            <div class="hhw-mockup-content">
                                <div class="hhw-mockup-label">Postcode</div>
                                <div class="hhw-mockup-input">1012 AB</div>
                                <div class="hhw-mockup-label" style="margin-top:.75rem">Klustype</div>
                                <div class="hhw-mockup-select">Loodgieter / CV <i class="fas fa-chevron-down"></i></div>
                                <div class="hhw-mockup-label" style="margin-top:.75rem">Omschrijving</div>
                                <div class="hhw-mockup-textarea">Lekkende kraan in de badkamer, al drie dagen…</div>
                                <div class="hhw-mockup-btn"><i class="fas fa-paper-plane"></i> Klus plaatsen</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="hhw-step hhw-step--reverse reveal">
                    <div class="hhw-step-aside">
                        <div class="hhw-step-num">02</div>
                        <div class="hhw-step-line"></div>
                    </div>
                    <div class="hhw-step-body">
                        <span class="hhw-step-tag">Stap 2</span>
                        <h2>Ontvang meerdere offertes</h2>
                        <p>Gecertificeerde vakmannen uit uw regio bekijken uw aanvraag en sturen een vrijblijvende offerte. U ontvangt maximaal 3 offertes, zodat vergelijken eenvoudig is — zonder overweldigende inbox.</p>
                        <div class="hhw-step-tips">
                            <div class="hhw-tip"><i class="fas fa-clock"></i> <span>Gemiddelde reactietijd: minder dan 24 uur.</span></div>
                            <div class="hhw-tip"><i class="fas fa-shield-alt"></i> <span>Maximaal 3 vakmannen per klus — gericht en overzichtelijk.</span></div>
                        </div>
                    </div>
                    <div class="hhw-step-visual hhw-step-visual--2">
                        <div class="hhw-offer-cards">
                            <div class="hhw-offer-card hhw-offer-card--best">
                                <div class="hhw-offer-badge">Beste match</div>
                                <div class="hhw-offer-avatar">R</div>
                                <div class="hhw-offer-info">
                                    <strong>Roel de Vries</strong>
                                    <span>Loodgieter · Amsterdam</span>
                                    <div class="hhw-offer-stars">★★★★★ <small>4.9</small></div>
                                </div>
                                <div class="hhw-offer-price">€ 95</div>
                            </div>
                            <div class="hhw-offer-card">
                                <div class="hhw-offer-avatar" style="background:#555">J</div>
                                <div class="hhw-offer-info">
                                    <strong>Jan Bakker</strong>
                                    <span>Loodgieter · Amsterdam</span>
                                    <div class="hhw-offer-stars">★★★★☆ <small>4.4</small></div>
                                </div>
                                <div class="hhw-offer-price">€ 120</div>
                            </div>
                            <div class="hhw-offer-card">
                                <div class="hhw-offer-avatar" style="background:#888">S</div>
                                <div class="hhw-offer-info">
                                    <strong>Sofia Meijer</strong>
                                    <span>Loodgieter · Amstelveen</span>
                                    <div class="hhw-offer-stars">★★★★★ <small>4.7</small></div>
                                </div>
                                <div class="hhw-offer-price">€ 110</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="hhw-step reveal">
                    <div class="hhw-step-aside">
                        <div class="hhw-step-num">03</div>
                    </div>
                    <div class="hhw-step-body">
                        <span class="hhw-step-tag">Stap 3</span>
                        <h2>Kies & laat de klus uitvoeren</h2>
                        <p>Vergelijk de offertes, lees de reviews en kies de vakman die het beste bij uw situatie en budget past. U maakt zelf de afspraak. Na afloop kunt u de vakman beoordelen zodat anderen ook van uw ervaring kunnen profiteren.</p>
                        <div class="hhw-step-tips">
                            <div class="hhw-tip"><i class="fas fa-star"></i> <span>Beoordelingen zijn altijd gebaseerd op echte, geverifieerde klussen.</span></div>
                            <div class="hhw-tip"><i class="fas fa-euro-sign"></i> <span>U betaalt de vakman zelf — geen extra commissie voor ons.</span></div>
                        </div>
                        <a href="/plaats-uw-klus" class="hhw-btn-primary">Nu beginnen <i class="fas fa-arrow-right"></i></a>
                    </div>
                    <div class="hhw-step-visual hhw-step-visual--3">
                        <div class="hhw-success-visual">
                            <div class="hhw-success-check"><i class="fas fa-check"></i></div>
                            <strong>Klus afgerond!</strong>
                            <p>Beoordeel uw vakman</p>
                            <div class="hhw-rating-stars">
                                <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

    </div><!-- /hhwPanelKlant -->

    <!-- ── Vakman-flow ────────────────────────────────────────────── -->
    <div class="hhw-tab-panel hhw-tab-panel--hidden" id="hhwPanelVakman">

        <div class="container section">
            <div class="hhw-steps">

                <div class="hhw-step reveal">
                    <div class="hhw-step-aside">
                        <div class="hhw-step-num">01</div>
                        <div class="hhw-step-line"></div>
                    </div>
                    <div class="hhw-step-body">
                        <span class="hhw-step-tag">Stap 1</span>
                        <h2>Maak een profiel aan</h2>
                        <p>Registreer uw bedrijf gratis. Voeg uw vakgebied, werkgebied, KvK-nummer en eventuele certificaten toe. Na verificatie (gemiddeld 24 uur) bent u direct zichtbaar voor klanten in uw regio.</p>
                        <div class="hhw-step-tips">
                            <div class="hhw-tip"><i class="fas fa-user-check"></i> <span>Een volledig profiel trekt 3× meer klanten dan een leeg profiel.</span></div>
                            <div class="hhw-tip"><i class="fas fa-image"></i> <span>Voeg foto's van eerder werk toe voor meer vertrouwen.</span></div>
                        </div>
                        <a href="/vakman-worden" class="hhw-btn-primary">Gratis aanmelden <i class="fas fa-arrow-right"></i></a>
                    </div>
                    <div class="hhw-step-visual">
                        <div class="hhw-profile-card">
                            <div class="hhw-profile-avatar">R</div>
                            <strong>Roel de Vries</strong>
                            <span>Loodgieter · Amsterdam</span>
                            <div class="hhw-profile-badges">
                                <span><i class="fas fa-check-circle"></i> Geverifieerd</span>
                                <span><i class="fas fa-medal"></i> KvK gecontroleerd</span>
                            </div>
                            <div class="hhw-profile-stats">
                                <div><strong>4.9</strong><small>Beoordeling</small></div>
                                <div><strong>47</strong><small>Klussen</small></div>
                                <div><strong>98%</strong><small>Reactie</small></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="hhw-step hhw-step--reverse reveal">
                    <div class="hhw-step-aside">
                        <div class="hhw-step-num">02</div>
                        <div class="hhw-step-line"></div>
                    </div>
                    <div class="hhw-step-body">
                        <span class="hhw-step-tag">Stap 2</span>
                        <h2>Ontvang relevante klusopdrachten</h2>
                        <p>Zodra een klant een aanvraag plaatst die past bij uw vakgebied en werkgebied, ontvangt u een melding. U beslist zelf of u een offerte wilt sturen. U betaalt alleen voor leads die u accepteert.</p>
                        <div class="hhw-step-tips">
                            <div class="hhw-tip"><i class="fas fa-euro-sign"></i> <span>Lead-prijs: €4 – €12, afhankelijk van klustype en regio.</span></div>
                            <div class="hhw-tip"><i class="fas fa-bell"></i> <span>Stel meldingen in per app, e-mail of sms.</span></div>
                        </div>
                    </div>
                    <div class="hhw-step-visual">
                        <div class="hhw-notif-stack">
                            <div class="hhw-notif hhw-notif--new">
                                <i class="fas fa-wrench"></i>
                                <div>
                                    <strong>Nieuwe klus in Amsterdam</strong>
                                    <span>Loodgieter / lekkage badkamer</span>
                                    <div class="hhw-notif-meta"><i class="fas fa-map-marker-alt"></i> 2,3 km · <i class="fas fa-clock"></i> 5 min. geleden</div>
                                </div>
                                <div class="hhw-notif-price">€7</div>
                            </div>
                            <div class="hhw-notif">
                                <i class="fas fa-bolt"></i>
                                <div>
                                    <strong>Nieuwe klus in Amstelveen</strong>
                                    <span>Elektricien / storingen meterkast</span>
                                    <div class="hhw-notif-meta"><i class="fas fa-map-marker-alt"></i> 4,1 km · <i class="fas fa-clock"></i> 22 min. geleden</div>
                                </div>
                                <div class="hhw-notif-price">€9</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="hhw-step reveal">
                    <div class="hhw-step-aside">
                        <div class="hhw-step-num">03</div>
                    </div>
                    <div class="hhw-step-body">
                        <span class="hhw-step-tag">Stap 3</span>
                        <h2>Stuur een offerte & bouw uw reputatie op</h2>
                        <p>Stuur een transparante offerte direct via het platform. Na de klus beoordeelt de klant uw werk. Meer positieve reviews = meer zichtbaarheid = meer klussen. Zo groeit uw bedrijf vanzelf.</p>
                        <div class="hhw-step-tips">
                            <div class="hhw-tip"><i class="fas fa-chart-line"></i> <span>Vakmannen met 10+ reviews ontvangen gemiddeld 2× meer aanvragen.</span></div>
                            <div class="hhw-tip"><i class="fas fa-headset"></i> <span>Ons team helpt u bij vragen over leads of betalingen.</span></div>
                        </div>
                        <a href="/vakman-worden" class="hhw-btn-primary">Aanmelden als vakman <i class="fas fa-arrow-right"></i></a>
                    </div>
                    <div class="hhw-step-visual">
                        <div class="hhw-growth-card">
                            <div class="hhw-growth-header"><i class="fas fa-chart-line"></i> Uw groei op het platform</div>
                            <div class="hhw-growth-bars">
                                <div class="hhw-bar-item"><span>Week 1</span><div class="hhw-bar" style="width:20%"></div><strong>2 klussen</strong></div>
                                <div class="hhw-bar-item"><span>Week 2</span><div class="hhw-bar" style="width:40%"></div><strong>5 klussen</strong></div>
                                <div class="hhw-bar-item"><span>Week 3</span><div class="hhw-bar" style="width:65%"></div><strong>8 klussen</strong></div>
                                <div class="hhw-bar-item"><span>Week 4</span><div class="hhw-bar" style="width:90%"></div><strong>12 klussen</strong></div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

    </div><!-- /hhwPanelVakman -->

    <!-- ── FAQ ──────────────────────────────────────────────────── -->
    <div style="background: #f8f8f6; border-top: 1px solid #f0f0ee;">
        <div class="container section-sm">
            <div style="text-align:center;">
                <span class="hhw-section-label reveal">Veelgestelde vragen</span>
                <h2 class="hhw-section-title reveal">Heeft u nog vragen?</h2>
            </div>
            <div class="hhw-faq-grid">
                <div class="hhw-faq" id="hhwFaqKlant">
                    <h3><i class="fas fa-home"></i> Voor huiseigenaren</h3>
                    <div class="hhw-faq-item reveal reveal-delay-1">
                        <button class="hhw-faq-q" aria-expanded="false">Kost het mij iets om een klus te plaatsen?</button>
                        <div class="hhw-faq-a"><p>Nee, het plaatsen van een klus is volledig gratis en vrijblijvend. U betaalt de vakman zelf na afronding van de klus. Wij vragen geen commissie.</p></div>
                    </div>
                    <div class="hhw-faq-item reveal reveal-delay-2">
                        <button class="hhw-faq-q" aria-expanded="false">Hoe weet ik dat de vakman betrouwbaar is?</button>
                        <div class="hhw-faq-a"><p>Elke vakman wordt door ons geverifieerd op identiteit, KvK-registratie en certificaten. Bovendien ziet u de beoordelingen van eerdere klanten op het profiel.</p></div>
                    </div>
                    <div class="hhw-faq-item reveal reveal-delay-3">
                        <button class="hhw-faq-q" aria-expanded="false">Wat als ik niet tevreden ben met de offerte?</button>
                        <div class="hhw-faq-a"><p>U bent nergens toe verplicht. Offertes zijn altijd vrijblijvend. U kiest zelf of en met wie u in zee gaat.</p></div>
                    </div>
                </div>
                <div class="hhw-faq" id="hhwFaqVakman">
                    <h3><i class="fas fa-hard-hat"></i> Voor vakmannen</h3>
                    <div class="hhw-faq-item reveal reveal-delay-1">
                        <button class="hhw-faq-q" aria-expanded="false">Betaal ik voor elke lead die ik zie?</button>
                        <div class="hhw-faq-a"><p>Nee. U betaalt alleen voor leads die u zelf actief accepteert. U kunt alle klusopdrachten bekijken — inclusief omschrijving en locatie — vóór u besluit te betalen.</p></div>
                    </div>
                    <div class="hhw-faq-item reveal reveal-delay-2">
                        <button class="hhw-faq-q" aria-expanded="false">Hoeveel concurrenten krijgen dezelfde lead?</button>
                        <div class="hhw-faq-a"><p>Maximaal 3 vakmannen per klus. Dat betekent minder concurrentie en een reële kans op de opdracht — anders dan bij platformen met 10+ vakmannen per lead.</p></div>
                    </div>
                    <div class="hhw-faq-item reveal reveal-delay-3">
                        <button class="hhw-faq-q" aria-expanded="false">Hoe snel word ik goedgekeurd?</button>
                        <div class="hhw-faq-a"><p>Gemiddeld binnen 24 uur na het invullen van uw profiel. Zorg dat uw KvK-nummer en eventuele certificaten correct zijn ingevuld om het proces te versnellen.</p></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── CTA block ──────────────────────────────────────────────── -->
    <div class="hhw-cta-block">
        <div class="container">
            <div class="hhw-cta-inner">
                <div class="hhw-cta-text">
                    <h2>Klaar om te beginnen?</h2>
                    <p>Of u nu een vakman zoekt of klanten wilt vinden — het duurt minder dan 2 minuten.</p>
                </div>
                <div class="hhw-cta-btns">
                    <a href="/plaats-uw-klus" class="hhw-btn-primary"><i class="fas fa-pencil-alt"></i> Klus plaatsen</a>
                    <a href="/vakman-worden" class="hhw-btn-outline"><i class="fas fa-user-plus"></i> Vakman worden</a>
                </div>
            </div>
        </div>
    </div>

</div>
<!-- /wp:html -->
