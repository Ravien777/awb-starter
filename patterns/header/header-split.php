<?php

/**
 * Title: Header — Split
 * Slug: header-split
 * Categories: awb-headers
 * Keywords: header, split, centered logo, luxury, fine dining, restaurant
 * Description: Logo centered with navigation split left and right. Requires two nav menus assigned to Primary and Secondary locations.
 */
?>
<!-- wp:group {"align":"full","className":"awb-header awb-header--split","style":{"spacing":{"padding":{"top":"0","bottom":"0"}},"border":{"bottom":{"color":"#e8e8e5","width":"1px"}}},"backgroundColor":"white","layout":{"type":"flex","justifyContent":"space-between","alignItems":"center"}} -->
<div class="wp-block-group alignfull awb-header awb-header--split has-white-background-color has-background" style="border-bottom:1px solid #e8e8e5;min-height:72px">

    <!-- wp:group {"style":{"layout":{"selfStretch":"fill"},"spacing":{"padding":{"right":"32px"}}},"layout":{"type":"flex","justifyContent":"flex-end"}} -->
    <div class="wp-block-group" style="flex:1;padding-right:32px;display:flex;justify-content:flex-end;border-right:1px solid #e8e8e5">
        <!-- wp:navigation {"menuSlug":"primary","layout":{"type":"flex","justifyContent":"right"},"style":{"typography":{"fontSize":".78rem","fontWeight":"500","letterSpacing":".08em","textTransform":"uppercase"}}} /-->
    </div>
    <!-- /wp:group -->

    <!-- wp:group {"style":{"spacing":{"padding":{"left":"24px","right":"24px"}}},"layout":{"type":"flex","justifyContent":"center"}} -->
    <div class="wp-block-group" style="flex-shrink:0;padding:0 24px">
        <!-- wp:site-title {"style":{"typography":{"fontWeight":"700","fontSize":"1.3rem","fontFamily":"serif"}}} /-->
    </div>
    <!-- /wp:group -->

    <!-- wp:group {"style":{"layout":{"selfStretch":"fill"},"spacing":{"padding":{"left":"32px"}}},"layout":{"type":"flex","justifyContent":"flex-start"}} -->
    <div class="wp-block-group" style="flex:1;padding-left:32px;display:flex;justify-content:flex-start;border-left:1px solid #e8e8e5">
        <!-- wp:navigation {"menuSlug":"secondary","layout":{"type":"flex","justifyContent":"left"},"style":{"typography":{"fontSize":".78rem","fontWeight":"500","letterSpacing":".08em","textTransform":"uppercase"}}} /-->
    </div>
    <!-- /wp:group -->

</div>
<!-- /wp:group -->