<?php
/**
 * Title: Hero Split
 * Slug: hero-split
 * Categories: general, marketing
 * Keywords: hero, landing, headline, cta
 * Description: Two-column hero with bold headline, supporting copy and dual call-to-action buttons.
 */
?>
<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|80","bottom":"var:preset|spacing|80","left":"var(--awb-space-lg,2rem)","right":"var(--awb-space-lg,2rem)"}},"color":{"background":"var(--awb-color-bg,#ffffff)"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull" style="background-color:var(--awb-color-bg,#ffffff);padding:calc(var(--awb-space-xl,4rem) * 0.8) var(--awb-space-lg,2rem);">
	<!-- wp:columns {"verticalAlignment":"center"} -->
	<div class="wp-block-columns are-vertically-aligned-center">
		<!-- wp:column {"verticalAlignment":"center","width":"60%"} -->
		<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:60%">
			<!-- wp:heading {"level":1,"style":{"typography":{"fontSize":"clamp(2.5rem, 5vw, 4rem)","lineHeight":"1.1","fontFamily":"var(--awb-font-heading, Georgia, serif)"}}} -->
			<h1 style="font-family:var(--awb-font-heading, Georgia, serif);font-size:clamp(2.5rem, 5vw, 4rem);line-height:1.1">Build something people remember</h1>
			<!-- /wp:heading -->

			<!-- wp:paragraph {"style":{"typography":{"fontSize":"1.15rem"},"color":{"text":"var(--awb-color-text,#1a1a1a)"}},"className":"awb-hero-subtext"} -->
			<p style="color:var(--awb-color-text,#1a1a1a);font-size:1.15rem">A short, confident value proposition goes here. Explain what you do and why visitors should care — in one or two sentences.</p>
			<!-- /wp:paragraph -->

			<!-- wp:buttons {"style":{"spacing":{"margin":{"top":"var(--awb-space-md,1rem)"}}}} -->
			<div class="wp-block-buttons" style="margin-top:var(--awb-space-md,1rem)">
				<!-- wp:button {"style":{"color":{"background":"var(--awb-color-accent,#e94560)"},"border":{"radius":"var(--awb-radius-md,8px)"}},"textColor":"white"} -->
				<div class="wp-block-button"><a class="wp-block-button__link has-white-color has-background" href="#" style="border-radius:var(--awb-radius-md,8px);background-color:var(--awb-color-accent,#e94560)">Get started</a></div>
				<!-- /wp:button -->

				<!-- wp:button {"className":"is-style-outline"} -->
				<div class="wp-block-button is-style-outline"><a class="wp-block-button__link" href="#">Learn more</a></div>
				<!-- /wp:button -->
			</div>
			<!-- /wp:buttons -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"verticalAlignment":"center","width":"40%"} -->
		<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:40%">
			<!-- wp:group {"style":{"color":{"background":"var(--awb-color-secondary,#16213e)"},"border":{"radius":"var(--awb-radius-lg,16px)"},"spacing":{"padding":{"top":"var(--awb-space-lg,2rem)","bottom":"var(--awb-space-lg,2rem)","left":"var(--awb-space-lg,2rem)","right":"var(--awb-space-lg,2rem)"}}},"layout":{"type":"constrained"}} -->
			<div class="wp-block-group" style="border-radius:var(--awb-radius-lg,16px);background-color:var(--awb-color-secondary,#16213e);padding:var(--awb-space-lg,2rem)">
				<!-- wp:heading {"textAlign":"center","level":3,"textColor":"white"} -->
				<h3 class="has-white-color has-text-color has-text-align-center">Highlight card</h3>
				<!-- /wp:heading -->

				<!-- wp:paragraph {"align":"center","textColor":"white"} -->
				<p class="has-text-align-center has-white-color has-text-color">Swap this panel for an image, stat, or testimonial to give your hero visual weight.</p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->
</div>
<!-- /wp:group -->
