<?php
/**
 * Title: Plaats uw Klus
 * Slug: plaats-uw-klus
 * Categories: awb-pages
 * Keywords: klus, plaatsen, aanvraag, offerte, formulier
 * Description: Multi-step form page for homeowners to submit a job request and receive quotes.
 * CSS: assets/css/general/plaats-uw-klus.css
 * JS:  assets/js/general/plaats-uw-klus.js
 */
?>

<!-- wp:html -->
<div class="plaats-klus-pattern">

    <!-- ── Hero / intro ─────────────────────────────────────────── -->
    <section class="pk-hero">
        <div class="pk-hero-bg"></div>
        <div class="pk-hero-grid-bg"></div>
        <div class="container pk-hero-content">
            <div class="pk-hero-eyebrow">
                <div class="pk-hero-eyebrow-dot"></div>
                Gratis en vrijblijvend — geen account vereist
            </div>
            <h1>Plaats uw klus,<br><span class="pk-highlight">ontvang offertes</span> van lokale vakmannen</h1>
            <p class="pk-hero-sub">Vul in 2 minuten het formulier in en ontvang binnen 24 uur meerdere transparante offertes van gecertificeerde vakmannen bij u in de buurt.</p>
            <div class="pk-trust-row">
                <span class="pk-trust-item"><i class="fas fa-check-circle"></i> 100% gratis</span>
                <span class="pk-trust-item"><i class="fas fa-shield-alt"></i> Geen verplichtingen</span>
                <span class="pk-trust-item"><i class="fas fa-star pk-star-icon"></i> 4.8/5 beoordeling</span>
                <span class="pk-trust-item"><i class="fas fa-clock"></i> Reactie binnen 24 uur</span>
            </div>
        </div>
    </section>

    <!-- ── Multi-step form ──────────────────────────────────────── -->
    <div class="container pk-form-outer">
        <div class="pk-form-wrapper">

            <!-- Progress bar -->
            <div class="pk-progress" aria-label="Stap voortgang">
                <div class="pk-progress-bar" id="pkProgressBar"></div>
            </div>
            <div class="pk-steps-indicator" id="pkStepsIndicator">
                <div class="pk-step-dot active" data-step="1"><span>1</span><label>Klus</label></div>
                <div class="pk-step-dot" data-step="2"><span>2</span><label>Details</label></div>
                <div class="pk-step-dot" data-step="3"><span>3</span><label>Locatie</label></div>
                <div class="pk-step-dot" data-step="4"><span>4</span><label>Contact</label></div>
            </div>

            <form id="pkForm" novalidate>

                <!-- ── Stap 1: Klustype ─────────────────────────── -->
                <div class="pk-step" id="pkStep1" data-step="1">
                    <h2 class="pk-step-title">Welke klus wilt u laten uitvoeren?</h2>
                    <p class="pk-step-sub">Kies de categorie die het beste bij uw klus past.</p>
                    <div class="pk-cat-grid" id="pkCatGrid">
                        <button type="button" class="pk-cat-btn" data-value="Loodgieter / CV">
                            <i class="fas fa-wrench"></i>
                            <span>Loodgieter / CV</span>
                            <small>Sanitair, verwarming, lekkages</small>
                        </button>
                        <button type="button" class="pk-cat-btn" data-value="Elektricien">
                            <i class="fas fa-bolt"></i>
                            <span>Elektricien</span>
                            <small>Installaties, meterkast, bedrading</small>
                        </button>
                        <button type="button" class="pk-cat-btn" data-value="Schilder / Stukadoor">
                            <i class="fas fa-paint-roller"></i>
                            <span>Schilder / Stukadoor</span>
                            <small>Binnen, buiten, plafond, behang</small>
                        </button>
                        <button type="button" class="pk-cat-btn" data-value="Timmerman">
                            <i class="fas fa-hammer"></i>
                            <span>Timmerman</span>
                            <small>Vloeren, kozijnen, meubels</small>
                        </button>
                        <button type="button" class="pk-cat-btn" data-value="Dakdekker">
                            <i class="fas fa-home"></i>
                            <span>Dakdekker</span>
                            <small>Reparatie, goten, lekkage</small>
                        </button>
                        <button type="button" class="pk-cat-btn" data-value="Hovenier">
                            <i class="fas fa-tree"></i>
                            <span>Hovenier</span>
                            <small>Tuin, aanleg, snoeien</small>
                        </button>
                        <button type="button" class="pk-cat-btn" data-value="Klusjesman / Renovatie">
                            <i class="fas fa-hard-hat"></i>
                            <span>Klusjesman</span>
                            <small>Verbouw, reparaties, algemeen</small>
                        </button>
                        <button type="button" class="pk-cat-btn" data-value="Anders">
                            <i class="fas fa-ellipsis-h"></i>
                            <span>Anders</span>
                            <small>Overige klus omschrijven</small>
                        </button>
                    </div>
                    <input type="hidden" id="pkKlustype" name="klustype">
                    <div class="pk-nav pk-nav--right">
                        <button type="button" class="pk-btn-primary" id="pkStep1Next" disabled>
                            Volgende stap <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </div>

                <!-- ── Stap 2: Klusbeschrijving ─────────────────── -->
                <div class="pk-step pk-step--hidden" id="pkStep2" data-step="2">
                    <h2 class="pk-step-title">Beschrijf uw klus</h2>
                    <p class="pk-step-sub">Hoe meer detail, hoe nauwkeuriger de offertes.</p>
                    <div class="pk-form-group">
                        <label for="pkTitel">Geef uw klus een korte titel *</label>
                        <input type="text" id="pkTitel" name="titel" placeholder="Bijv. 'Lekkende kraan repareren in badkamer'" maxlength="80" required>
                        <div class="pk-char-count"><span id="pkTitelCount">0</span>/80</div>
                    </div>
                    <div class="pk-form-group">
                        <label for="pkOmschrijving">Omschrijving van de klus *</label>
                        <textarea id="pkOmschrijving" name="omschrijving" rows="5"
                            placeholder="Beschrijf zo nauwkeurig mogelijk: wat is er kapot, wanneer is het begonnen, welke materialen zijn aanwezig, eventuele bijzonderheden…" required></textarea>
                    </div>
                    <div class="pk-form-row">
                        <div class="pk-form-group">
                            <label for="pkWanneer">Wanneer moet de klus gedaan worden?</label>
                            <select id="pkWanneer" name="wanneer">
                                <option value="">Geen voorkeur</option>
                                <option>Zo snel mogelijk (spoed)</option>
                                <option>Binnen een week</option>
                                <option>Binnen een maand</option>
                                <option>Flexibel / nader te bepalen</option>
                            </select>
                        </div>
                        <div class="pk-form-group">
                            <label for="pkBudget">Indicatief budget</label>
                            <select id="pkBudget" name="budget">
                                <option value="">Weet ik niet / geen voorkeur</option>
                                <option>Minder dan €250</option>
                                <option>€250 – €750</option>
                                <option>€750 – €2.000</option>
                                <option>€2.000 – €5.000</option>
                                <option>Meer dan €5.000</option>
                            </select>
                        </div>
                    </div>
                    <div class="pk-nav">
                        <button type="button" class="pk-btn-secondary" data-goto="1"><i class="fas fa-arrow-left"></i> Terug</button>
                        <button type="button" class="pk-btn-primary" id="pkStep2Next">Volgende stap <i class="fas fa-arrow-right"></i></button>
                    </div>
                </div>

                <!-- ── Stap 3: Locatie ───────────────────────────── -->
                <div class="pk-step pk-step--hidden" id="pkStep3" data-step="3">
                    <h2 class="pk-step-title">Waar moet de klus plaatsvinden?</h2>
                    <p class="pk-step-sub">Uw locatie bepaalt welke vakmannen bij u in de buurt zijn.</p>
                    <div class="pk-form-row">
                        <div class="pk-form-group">
                            <label for="pkPostcode">Postcode *</label>
                            <input type="text" id="pkPostcode" name="postcode" placeholder="1012 AB" maxlength="7" required>
                        </div>
                        <div class="pk-form-group">
                            <label for="pkPlaats">Plaats *</label>
                            <input type="text" id="pkPlaats" name="plaats" placeholder="Amsterdam" required>
                        </div>
                    </div>
                    <div class="pk-form-group">
                        <label for="pkAdres">Adres (optioneel)</label>
                        <input type="text" id="pkAdres" name="adres" placeholder="Straatnaam en huisnummer">
                    </div>
                    <div class="pk-form-group">
                        <label>Type locatie</label>
                        <div class="pk-radio-group" id="pkLocatieType">
                            <label class="pk-radio-btn">
                                <input type="radio" name="locatietype" value="Koopwoning"> <span>Koopwoning</span>
                            </label>
                            <label class="pk-radio-btn">
                                <input type="radio" name="locatietype" value="Huurwoning"> <span>Huurwoning</span>
                            </label>
                            <label class="pk-radio-btn">
                                <input type="radio" name="locatietype" value="Bedrijfspand"> <span>Bedrijfspand</span>
                            </label>
                            <label class="pk-radio-btn">
                                <input type="radio" name="locatietype" value="Anders"> <span>Anders</span>
                            </label>
                        </div>
                    </div>
                    <div class="pk-nav">
                        <button type="button" class="pk-btn-secondary" data-goto="2"><i class="fas fa-arrow-left"></i> Terug</button>
                        <button type="button" class="pk-btn-primary" id="pkStep3Next">Volgende stap <i class="fas fa-arrow-right"></i></button>
                    </div>
                </div>

                <!-- ── Stap 4: Contactgegevens ───────────────────── -->
                <div class="pk-step pk-step--hidden" id="pkStep4" data-step="4">
                    <h2 class="pk-step-title">Hoe kunnen vakmannen u bereiken?</h2>
                    <p class="pk-step-sub">Uw gegevens worden alleen gedeeld met vakmannen die uw offerte aanvragen.</p>
                    <div class="pk-form-row">
                        <div class="pk-form-group">
                            <label for="pkNaam">Uw naam *</label>
                            <input type="text" id="pkNaam" name="naam" placeholder="Voornaam en achternaam" required>
                        </div>
                        <div class="pk-form-group">
                            <label for="pkTelefoon">Telefoonnummer *</label>
                            <input type="tel" id="pkTelefoon" name="telefoon" placeholder="06 12 34 56 78" required>
                        </div>
                    </div>
                    <div class="pk-form-group">
                        <label for="pkEmail">E-mailadres *</label>
                        <input type="email" id="pkEmail" name="email" placeholder="u@voorbeeld.nl" required>
                    </div>
                    <div class="pk-form-group">
                        <label>Voorkeur voor contact</label>
                        <div class="pk-radio-group">
                            <label class="pk-radio-btn">
                                <input type="radio" name="contactvoorkeur" value="Telefoon"> <span>Telefoon</span>
                            </label>
                            <label class="pk-radio-btn">
                                <input type="radio" name="contactvoorkeur" value="E-mail" checked> <span>E-mail</span>
                            </label>
                            <label class="pk-radio-btn">
                                <input type="radio" name="contactvoorkeur" value="Beide"> <span>Beide</span>
                            </label>
                        </div>
                    </div>
                    <div class="pk-form-check">
                        <input type="checkbox" id="pkAkkoord" required>
                        <label for="pkAkkoord">Ik ga akkoord met de <a href="/voorwaarden">algemene voorwaarden</a> en het <a href="/privacy">privacybeleid</a>. Mijn gegevens worden alleen gedeeld met maximaal 3 vakmannen.</label>
                    </div>
                    <div class="pk-nav">
                        <button type="button" class="pk-btn-secondary" data-goto="3"><i class="fas fa-arrow-left"></i> Terug</button>
                        <button type="submit" class="pk-btn-primary pk-btn-submit" id="pkSubmitBtn">
                            <i class="fas fa-paper-plane"></i> Klus plaatsen & offertes ontvangen
                        </button>
                    </div>
                </div>

                <!-- ── Succes ─────────────────────────────────────── -->
                <div class="pk-step pk-step--hidden pk-success" id="pkSuccess">
                    <div class="pk-success-icon"><i class="fas fa-check-circle"></i></div>
                    <h2>Uw klus is geplaatst!</h2>
                    <p>Wij sturen uw aanvraag direct naar gecertificeerde vakmannen bij u in de buurt. U ontvangt binnen 24 uur offertes op uw e-mailadres.</p>
                    <div class="pk-success-steps">
                        <div class="pk-success-step"><i class="fas fa-search"></i> Vakmannen bekijken uw klus</div>
                        <div class="pk-success-step"><i class="fas fa-envelope"></i> U ontvangt offertes per e-mail</div>
                        <div class="pk-success-step"><i class="fas fa-handshake"></i> Kies de beste vakman</div>
                    </div>
                    <button type="button" class="pk-btn-secondary" id="pkNewKlus">Nog een klus plaatsen</button>
                </div>

            </form>
        </div>

        <!-- Side trust block -->
        <aside class="pk-aside">
            <div class="pk-aside-block">
                <h3><i class="fas fa-shield-alt"></i> Uw aanvraag is veilig</h3>
                <ul>
                    <li><i class="fas fa-check"></i> Gegevens alleen voor geselecteerde vakmannen</li>
                    <li><i class="fas fa-check"></i> Maximaal 3 offertes per klus</li>
                    <li><i class="fas fa-check"></i> Geen spam, geen verplichtingen</li>
                    <li><i class="fas fa-check"></i> Gratis annuleren op elk moment</li>
                </ul>
            </div>
            <div class="pk-aside-block">
                <h3><i class="fas fa-star"></i> Waarom ons platform?</h3>
                <div class="pk-aside-stat"><strong>4.8/5</strong><span>Gemiddelde beoordeling</span></div>
                <div class="pk-aside-stat"><strong>12.400+</strong><span>Klussen afgerond</span></div>
                <div class="pk-aside-stat"><strong>&lt; 24u</strong><span>Gemiddelde reactietijd</span></div>
            </div>
            <div class="pk-aside-block pk-aside-review">
                <div class="pk-aside-stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
                <p>"In één dag drie offertes ontvangen. Uiteindelijk voor de helft van wat ik dacht betaald."</p>
                <strong>— Marieke, Utrecht</strong>
            </div>
        </aside>
    </div>

    <!-- ── Reassurance section ───────────────────────────────────── -->
    <div style="background: #f8f8f6; border-top: 1px solid #f0f0ee; margin-top: 4rem;">
        <div class="container section-sm">
            <div class="pk-reassurance-grid">
                <div class="pk-reassurance-item reveal reveal-delay-1">
                    <div class="pk-reassurance-icon"><i class="fas fa-user-check"></i></div>
                    <h4>Gecontroleerde vakmannen</h4>
                    <p>Elke vakman wordt geverifieerd op achtergrond, certificaten en beoordelingen van eerdere klanten.</p>
                </div>
                <div class="pk-reassurance-item reveal reveal-delay-2">
                    <div class="pk-reassurance-icon"><i class="fas fa-file-invoice-dollar"></i></div>
                    <h4>Transparante offertes</h4>
                    <p>Ontvang gedetailleerde offertes zonder verborgen kosten. Alles inzichtelijk vóór u beslist.</p>
                </div>
                <div class="pk-reassurance-item reveal reveal-delay-3">
                    <div class="pk-reassurance-icon"><i class="fas fa-headset"></i></div>
                    <h4>Wij staan voor u klaar</h4>
                    <p>Loopt er iets mis? Ons team is bereikbaar via telefoon en chat om u te helpen.</p>
                </div>
            </div>
        </div>
    </div>

</div>
<!-- /wp:html -->
