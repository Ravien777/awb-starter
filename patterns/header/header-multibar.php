<?php

/**
 * Title: Header — Multibar
 * Slug: header-multibar
 * Categories: awb-headers
 * Keywords: header, multibar, shop, ecommerce, woocommerce, utility bar, three row
 * Description: Three-tier header: top utility bar (contact/social), main logo + nav + icons, bottom category bar. Great for shops.
 */
?>
<!-- wp:group {"align":"full","className":"awb-header awb-header--multibar","layout":{"type":"flex","orientation":"vertical","alignItems":"stretch"}} -->
<div class="wp-block-group alignfull awb-header awb-header--multibar">

    <!-- Top utility bar -->
    <!-- wp:group {"align":"full","style":{"color":{"background":"#2e2e2a","text":"#d1d1cc"},"spacing":{"padding":{"top":"0","bottom":"0","left":"32px","right":"32px"}}},"layout":{"type":"flex","justifyContent":"space-between","alignItems":"center"}} -->
    <div class="wp-block-group alignfull has-background has-text-color" style="background:#2e2e2a;color:#d1d1cc;padding-left:32px;padding-right:32px;min-height:36px">
        <!-- wp:paragraph {"style":{"typography":{"fontSize":".75rem"}}} -->
        <p style="font-size:.75rem;margin:0">📞 +1 234 567 890 &nbsp;|&nbsp; ✉ info@example.com</p>
        <!-- /wp:paragraph -->
        <!-- wp:social-links {"size":"has-small-icon-size","style":{"spacing":{"blockGap":{"left":"8px"}}}} -->
        <ul class="wp-block-social-links has-small-icon-size">
            <!-- wp:social-link {"url":"#","service":"facebook"} /-->
            <!-- wp:social-link {"url":"#","service":"instagram"} /-->
            <!-- wp:social-link {"url":"#","service":"x"} /-->
        </ul>
        <!-- /wp:social-links -->
    </div>
    <!-- /wp:group -->

    <!-- Main bar: logo + nav + icons -->
    <!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"0","bottom":"0","left":"32px","right":"32px"}},"border":{"bottom":{"color":"#e8e8e5","width":"1px"}}},"backgroundColor":"white","layout":{"type":"flex","justifyContent":"space-between","alignItems":"center"}} -->
    <div class="wp-block-group alignfull has-white-background-color has-background" style="padding-left:32px;padding-right:32px;border-bottom:1px solid #e8e8e5;min-height:64px">

        <!-- wp:site-title {"style":{"typography":{"fontWeight":"700","fontSize":"1rem"}}} /-->

        <!-- wp:navigation {"layout":{"type":"flex","justifyContent":"center"},"style":{"typography":{"fontSize":".875rem","fontWeight":"500"}}} /-->

        <!-- wp:group {"layout":{"type":"flex","alignItems":"center","flexWrap":"nowrap"},"style":{"spacing":{"blockGap":"4px"}}} -->
        <div class="wp-block-group" style="display:flex;align-items:center;gap:4px">
            <!-- wp:search {"label":"Search","buttonText":"Go","buttonUseIcon":true,"showLabel":false,"style":{"border":{"radius":"4px"}}} /-->
        </div>
        <!-- /wp:group -->

    </div>
    <!-- /wp:group -->

    <!-- Bottom category bar -->
    <!-- wp:group {"align":"full","style":{"color":{"background":"#f8f8f6"},"spacing":{"padding":{"top":"0","bottom":"0","left":"32px","right":"32px"}},"border":{"bottom":{"color":"#e8e8e5","width":"1px"}}},"layout":{"type":"flex","alignItems":"center"}} -->
    <div class="wp-block-group alignfull has-background" style="background:#f8f8f6;padding-left:32px;padding-right:32px;border-bottom:1px solid #e8e8e5;min-height:40px">
        <!-- wp:navigation {"menuSlug":"secondary","layout":{"type":"flex","justifyContent":"left"},"style":{"typography":{"fontSize":".78rem","fontWeight":"500","letterSpacing":".04em"}}} /-->
    </div>
    <!-- /wp:group -->

</div>
<!-- /wp:group -->