<?php

/**
 * Title: Vakman Header
 * Slug: vakman-header
 * Categories: awb-headers
 * Keywords: header, vakman
 * Description: Header for Vakman-style projects.
 * CSS: assets/css/headers/vakman-header.css
 * JS:  assets/js/headers/vakman-header.js
 *
 */
?>

<!-- wp:html -->
<div class="topbar">
    <i class="fas fa-tools" style="margin-right: 8px; opacity:0.8;"></i>
    Ben jij een vakman die op zoek is naar opdrachten?
    <a href="#" id="wordLidTopbarBtn">Word gratis lid <i class="fas fa-arrow-right" style="font-size:0.75rem;"></i></a>
</div>

<div class="site-header" id="siteHeader">
    <div class="container">
        <div class="header">
            <a href="#" class="logo">Vakmannen<span class="logo-dot">.</span>vinden</a>
            <nav class="nav-links">
                <?php
                echo wp_nav_menu(array('theme_location' => 'primary'));
                ?>
                <a href="#" class="btn-nav-cta" id="plaatsKlusNavBtn"><i class="fas fa-pen-ruler"></i> Plaats uw klus</a>
            </nav>
            <div class="hamburger" id="hamburger">
                <span></span><span></span><span></span>
            </div>
        </div>
    </div>
    <nav class="mobile-nav" id="mobileNav">
        <?php
        echo wp_nav_menu(array('theme_location' => 'primary'));
        ?>
        <a href="#" id="plaatsKlusMobile" style="color: var(--primary); font-weight: 700;">Plaats uw klus</a>
    </nav>
</div>
<!-- /wp:html -->