/**
 * AWB – Hoe het Werkt JS
 * Tab switcher (klant / vakman), scroll reveal, FAQ accordion.
 */

( function () {
    'use strict';

    const ROOT = document.querySelector( '.hoe-het-werkt-pattern' );
    if ( ! ROOT ) return;

    // ── Scroll reveal ────────────────────────────────────────────────
    const revealObs = new IntersectionObserver(
        entries => entries.forEach( e => {
            if ( e.isIntersecting ) { e.target.classList.add( 'visible' ); revealObs.unobserve( e.target ); }
        } ),
        { threshold: 0.1 }
    );
    ROOT.querySelectorAll( '.reveal' ).forEach( el => revealObs.observe( el ) );

    // ── Tab switcher ─────────────────────────────────────────────────
    const tabs       = ROOT.querySelectorAll( '.hhw-tab' );
    const panelKlant = ROOT.querySelector( '#hhwPanelKlant' );
    const panelVakman= ROOT.querySelector( '#hhwPanelVakman' );

    tabs.forEach( tab => {
        tab.addEventListener( 'click', () => {
            tabs.forEach( t => t.classList.remove( 'active' ) );
            tab.classList.add( 'active' );

            const target = tab.dataset.tab;
            if ( target === 'klant' ) {
                panelKlant?.classList.remove( 'hhw-tab-panel--hidden' );
                panelVakman?.classList.add( 'hhw-tab-panel--hidden' );
            } else {
                panelVakman?.classList.remove( 'hhw-tab-panel--hidden' );
                panelKlant?.classList.add( 'hhw-tab-panel--hidden' );
            }

            // Re-trigger reveals in newly visible panel
            ROOT.querySelectorAll( '.reveal:not(.visible)' ).forEach( el => revealObs.observe( el ) );
        } );
    } );

    // ── FAQ accordions (both klant + vakman columns) ──────────────────
    ROOT.querySelectorAll( '.hhw-faq-q' ).forEach( btn => {
        btn.addEventListener( 'click', () => {
            // Find sibling faqs in the same .hhw-faq container
            const parent = btn.closest( '.hhw-faq' );
            const expanded = btn.getAttribute( 'aria-expanded' ) === 'true';

            parent.querySelectorAll( '.hhw-faq-q' ).forEach( b => {
                b.setAttribute( 'aria-expanded', 'false' );
                b.nextElementSibling.classList.remove( 'open' );
            } );

            if ( ! expanded ) {
                btn.setAttribute( 'aria-expanded', 'true' );
                btn.nextElementSibling.classList.add( 'open' );
            }
        } );
    } );

    // ── Animate growth bars when they enter view ─────────────────────
    const bars = ROOT.querySelectorAll( '.hhw-bar' );
    if ( bars.length ) {
        bars.forEach( bar => { bar._targetWidth = bar.style.width; bar.style.width = '0'; } );
        const barObs = new IntersectionObserver(
            entries => {
                if ( entries[0].isIntersecting ) {
                    bars.forEach( ( bar, i ) => {
                        setTimeout( () => {
                            bar.style.transition = 'width .7s ease';
                            bar.style.width = bar._targetWidth;
                        }, i * 120 );
                    } );
                    barObs.disconnect();
                }
            },
            { threshold: 0.3 }
        );
        const growthCard = ROOT.querySelector( '.hhw-growth-card' );
        if ( growthCard ) barObs.observe( growthCard );
    }

} )();
