/**
 * AWB – Vakman Worden page JS
 * Handles: scroll reveal, counter animation, FAQ accordion, form submit.
 * Scoped to .vakman-worden-pattern so it never conflicts with other pages.
 */

( function () {
    'use strict';

    const ROOT = document.querySelector( '.vakman-worden-pattern' );
    if ( ! ROOT ) return;

    // ── Scroll reveal ────────────────────────────────────────────────
    const revealEls = ROOT.querySelectorAll( '.reveal' );
    const revealObs = new IntersectionObserver(
        ( entries ) => entries.forEach( e => { if ( e.isIntersecting ) { e.target.classList.add( 'visible' ); revealObs.unobserve( e.target ); } } ),
        { threshold: 0.12 }
    );
    revealEls.forEach( el => revealObs.observe( el ) );

    // ── Counter animation ────────────────────────────────────────────
    function animateCounter( el ) {
        const target  = parseInt( el.dataset.target || el.textContent, 10 );
        const suffix  = el.dataset.suffix  || '';
        const prefix  = el.dataset.prefix  || '';
        const isFloat = el.dataset.float !== undefined;
        if ( isNaN( target ) ) return;

        const duration = 1800;
        const start    = performance.now();

        function step( now ) {
            const progress = Math.min( ( now - start ) / duration, 1 );
            const eased    = 1 - Math.pow( 1 - progress, 3 );
            const current  = Math.round( eased * target );
            el.textContent = prefix + current.toLocaleString( 'nl-NL' ) + suffix;
            if ( progress < 1 ) requestAnimationFrame( step );
        }
        requestAnimationFrame( step );
    }

    const statsBar = ROOT.querySelector( '#vwStatsBar' );
    if ( statsBar ) {
        let counted = false;
        const statsObs = new IntersectionObserver(
            ( entries ) => {
                if ( entries[0].isIntersecting && ! counted ) {
                    counted = true;
                    ROOT.querySelectorAll( '.vw-stat-num[data-target]' ).forEach( animateCounter );
                }
            },
            { threshold: 0.4 }
        );
        statsObs.observe( statsBar );
    }

    // ── FAQ accordion ────────────────────────────────────────────────
    ROOT.querySelectorAll( '.vw-faq-q' ).forEach( btn => {
        btn.addEventListener( 'click', () => {
            const answer   = btn.nextElementSibling;
            const expanded = btn.getAttribute( 'aria-expanded' ) === 'true';

            // Close all others first
            ROOT.querySelectorAll( '.vw-faq-q' ).forEach( b => {
                b.setAttribute( 'aria-expanded', 'false' );
                b.nextElementSibling.classList.remove( 'open' );
            } );

            if ( ! expanded ) {
                btn.setAttribute( 'aria-expanded', 'true' );
                answer.classList.add( 'open' );
            }
        } );
    } );

    // ── Scroll to register ───────────────────────────────────────────
    [ 'vwHeroRegisterBtn', 'vwPricingRegisterBtn' ].forEach( id => {
        const btn = ROOT.querySelector( '#' + id );
        if ( btn ) {
            btn.addEventListener( 'click', () => {
                const target = ROOT.querySelector( '#vwRegister' );
                if ( target ) target.scrollIntoView( { behavior: 'smooth', block: 'start' } );
            } );
        }
    } );

    const moreBtn = ROOT.querySelector( '#vwHeroMoreBtn' );
    if ( moreBtn ) {
        moreBtn.addEventListener( 'click', () => {
            window.scrollBy( { top: window.innerHeight * 0.8, behavior: 'smooth' } );
        } );
    }

    // ── Registration form ────────────────────────────────────────────
    const form = ROOT.querySelector( '#vwRegisterForm' );
    if ( form ) {
        form.addEventListener( 'submit', async function ( e ) {
            e.preventDefault();
            const btn     = ROOT.querySelector( '#vwSubmitBtn' );
            const success = ROOT.querySelector( '#vwFormSuccess' );

            btn.disabled    = true;
            btn.textContent = 'Versturen…';

            // Collect form data — wire this up to your backend / CRM / WP form handler.
            const data = new FormData( form );

            // Simulate async submission (replace with real fetch to your endpoint).
            await new Promise( r => setTimeout( r, 1200 ) );

            btn.style.display     = 'none';
            success.style.display = 'block';
            form.querySelectorAll( 'input, select, textarea' ).forEach( el => { el.disabled = true; } );
        } );
    }

} )();
