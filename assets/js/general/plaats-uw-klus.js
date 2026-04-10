/**
 * AWB – Plaats uw Klus JS
 * Multi-step form with validation, progress bar, and reveal animations.
 */

( function () {
    'use strict';

    const ROOT = document.querySelector( '.plaats-klus-pattern' );
    if ( ! ROOT ) return;

    // ── Reveal ───────────────────────────────────────────────────────
    const obs = new IntersectionObserver(
        entries => entries.forEach( e => { if ( e.isIntersecting ) { e.target.classList.add( 'visible' ); obs.unobserve( e.target ); } } ),
        { threshold: 0.12 }
    );
    ROOT.querySelectorAll( '.reveal' ).forEach( el => obs.observe( el ) );

    // ── Multi-step state ─────────────────────────────────────────────
    let currentStep = 1;
    const totalSteps = 4;

    function showStep( step ) {
        ROOT.querySelectorAll( '.pk-step' ).forEach( el => el.classList.add( 'pk-step--hidden' ) );
        const el = ROOT.querySelector( '#pkStep' + step ) || ROOT.querySelector( '#pkSuccess' );
        if ( el ) el.classList.remove( 'pk-step--hidden' );

        // Update progress bar
        const bar = ROOT.querySelector( '#pkProgressBar' );
        if ( bar ) bar.style.width = ( step / totalSteps * 100 ) + '%';

        // Update dots
        ROOT.querySelectorAll( '.pk-step-dot' ).forEach( dot => {
            const s = parseInt( dot.dataset.step );
            dot.classList.remove( 'active', 'done' );
            if ( s === step )  dot.classList.add( 'active' );
            if ( s < step )    dot.classList.add( 'done' );
        } );

        currentStep = step;

        // Scroll form into view
        const wrapper = ROOT.querySelector( '.pk-form-wrapper' );
        if ( wrapper ) wrapper.scrollIntoView( { behavior: 'smooth', block: 'start' } );
    }

    // ── Category selection (step 1) ──────────────────────────────────
    const catBtns   = ROOT.querySelectorAll( '.pk-cat-btn' );
    const klusInput = ROOT.querySelector( '#pkKlustype' );
    const step1Next = ROOT.querySelector( '#pkStep1Next' );

    catBtns.forEach( btn => {
        btn.addEventListener( 'click', () => {
            catBtns.forEach( b => b.classList.remove( 'selected' ) );
            btn.classList.add( 'selected' );
            if ( klusInput ) klusInput.value = btn.dataset.value;
            if ( step1Next ) step1Next.disabled = false;
        } );
    } );

    if ( step1Next ) {
        step1Next.addEventListener( 'click', () => {
            if ( klusInput && klusInput.value ) showStep( 2 );
        } );
    }

    // ── Character counter for title ──────────────────────────────────
    const titleInput  = ROOT.querySelector( '#pkTitel' );
    const titleCount  = ROOT.querySelector( '#pkTitelCount' );
    if ( titleInput && titleCount ) {
        titleInput.addEventListener( 'input', () => { titleCount.textContent = titleInput.value.length; } );
    }

    // ── Step 2 next ──────────────────────────────────────────────────
    const step2Next = ROOT.querySelector( '#pkStep2Next' );
    if ( step2Next ) {
        step2Next.addEventListener( 'click', () => {
            const titel = ROOT.querySelector( '#pkTitel' );
            const omschr = ROOT.querySelector( '#pkOmschrijving' );
            let valid = true;
            [ titel, omschr ].forEach( el => {
                if ( el ) {
                    el.closest( '.pk-form-group' ).classList.toggle( 'pk-invalid', ! el.value.trim() );
                    if ( ! el.value.trim() ) valid = false;
                }
            } );
            if ( valid ) showStep( 3 );
        } );
    }

    // ── Step 3 next ──────────────────────────────────────────────────
    const step3Next = ROOT.querySelector( '#pkStep3Next' );
    if ( step3Next ) {
        step3Next.addEventListener( 'click', () => {
            const postcode = ROOT.querySelector( '#pkPostcode' );
            const plaats   = ROOT.querySelector( '#pkPlaats' );
            let valid = true;
            [ postcode, plaats ].forEach( el => {
                if ( el ) {
                    el.closest( '.pk-form-group' ).classList.toggle( 'pk-invalid', ! el.value.trim() );
                    if ( ! el.value.trim() ) valid = false;
                }
            } );
            if ( valid ) showStep( 4 );
        } );
    }

    // ── Back buttons ─────────────────────────────────────────────────
    ROOT.querySelectorAll( '[data-goto]' ).forEach( btn => {
        btn.addEventListener( 'click', () => showStep( parseInt( btn.dataset.goto ) ) );
    } );

    // ── Form submit ──────────────────────────────────────────────────
    const form = ROOT.querySelector( '#pkForm' );
    if ( form ) {
        form.addEventListener( 'submit', async function ( e ) {
            e.preventDefault();
            const submitBtn = ROOT.querySelector( '#pkSubmitBtn' );

            // Validate step 4 fields
            const naam     = ROOT.querySelector( '#pkNaam' );
            const telefoon = ROOT.querySelector( '#pkTelefoon' );
            const email    = ROOT.querySelector( '#pkEmail' );
            const akkoord  = ROOT.querySelector( '#pkAkkoord' );

            let valid = true;
            [ naam, telefoon, email ].forEach( el => {
                if ( el ) {
                    const empty = ! el.value.trim();
                    el.closest( '.pk-form-group' ).classList.toggle( 'pk-invalid', empty );
                    if ( empty ) valid = false;
                }
            } );
            if ( akkoord && ! akkoord.checked ) { valid = false; akkoord.focus(); }
            if ( ! valid ) return;

            submitBtn.disabled    = true;
            submitBtn.textContent = 'Versturen…';

            // Collect all form data — wire up to your WP REST API / backend here.
            const data = Object.fromEntries( new FormData( form ).entries() );
            data.klustype = klusInput ? klusInput.value : '';
            console.log( 'AWB Klus submission:', data ); // replace with real POST

            await new Promise( r => setTimeout( r, 1200 ) ); // simulate network

            // Show success
            ROOT.querySelector( '#pkProgressBar' ).style.width = '100%';
            ROOT.querySelectorAll( '.pk-step-dot' ).forEach( d => d.classList.add( 'done' ) );
            showStep( 'Success' );
        } );
    }

    // ── Reset form ───────────────────────────────────────────────────
    const newKlusBtn = ROOT.querySelector( '#pkNewKlus' );
    if ( newKlusBtn ) {
        newKlusBtn.addEventListener( 'click', () => {
            form.reset();
            catBtns.forEach( b => b.classList.remove( 'selected' ) );
            if ( klusInput ) klusInput.value = '';
            if ( step1Next ) step1Next.disabled = true;
            if ( titleCount ) titleCount.textContent = '0';
            ROOT.querySelectorAll( '.pk-invalid' ).forEach( el => el.classList.remove( 'pk-invalid' ) );
            showStep( 1 );
        } );
    }

} )();
