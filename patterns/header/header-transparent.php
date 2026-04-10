<?php

/**
 * Title: Header — Transparent
 * Slug: header-transparent
 * Categories: awb-headers
 * Keywords: header, transparent, overlay, hero, landing
 * Description: Transparent header that overlays a hero image. Becomes solid on scroll via JS.
 * CSS: assets/css/headers/header-transparent.css
 * JS:  assets/js/headers/header-transparent.js
 */
?>
<!-- wp:group {"align":"full","className":"awb-header awb-header--transparent","style":{"spacing":{"padding":{"top":"0","bottom":"0","left":"32px","right":"32px"}},"position":{"type":"sticky","top":"0px"},"zIndex":100},"layout":{"type":"flex","justifyContent":"space-between","alignItems":"center"}} -->
<div class="wp-block-group alignfull awb-header awb-header--transparent" style="padding-left:32px;padding-right:32px;min-height:64px;position:sticky;top:0;z-index:100;background:transparent;border-bottom:1px solid rgba(255,255,255,0.12)">

    <!-- wp:site-title {"style":{"typography":{"fontWeight":"700","fontSize":"1rem"},"color":{"text":"#ffffff"}}} /-->

    <!-- wp:navigation {"layout":{"type":"flex","justifyContent":"right"},"style":{"typography":{"fontSize":".875rem","fontWeight":"500"},"color":{"text":"rgba(255,255,255,0.85)"}}} /-->

</div>
<!-- /wp:group -->