<?php
/**
 * Title: Feature Grid
 * Slug: feature-grid
 * Categories: general
 * Keywords: features, services, benefits, grid
 * Description: Three-column feature grid with accent icons, titles and supporting copy.
 */
?>
<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var(--awb-space-xl,4rem)","bottom":"var(--awb-space-xl,4rem)","left":"var(--awb-space-lg,2rem)","right":"var(--awb-space-lg,2rem)"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull" style="padding:var(--awb-space-xl,4rem) var(--awb-space-lg,2rem)">
	<!-- wp:heading {"textAlign":"center","style":{"typography":{"fontFamily":"var(--awb-font-heading, Georgia, serif)"}}} -->
	<h2 class="has-text-align-center" style="font-family:var(--awb-font-heading, Georgia, serif)">Why choose us</h2>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"align":"center","textColor":"black","style":{"spacing":{"margin":{"bottom":"var(--awb-space-lg,2rem)"}}}} -->
	<p class="has-text-align-center" style="margin-bottom:var(--awb-space-lg,2rem)">Three short reasons customers should trust you over the alternative.</p>
	<!-- /wp:paragraph -->

	<!-- wp:columns {"style":{"spacing":{"blockGap":{"top":"var(--awb-space-lg,2rem)","left":"var(--awb-space-md,1rem)"}}}} -->
	<div class="wp-block-columns">
		<?php foreach ([['&#9733;', 'Fast delivery', 'Projects shipped on schedule with clear milestones and weekly updates.'], ['&#9878;', 'Fair pricing', 'Transparent quotes with no hidden fees — you approve every cost upfront.'], ['&#9829;', 'Real support', 'A dedicated contact who answers quickly, during and after the build.']] as [$icon, $title, $text]) : ?>
			<!-- wp:column {"style":{"spacing":{"padding":{"top":"var(--awb-space-md,1rem)","bottom":"var(--awb-space-md,1rem)","left":"var(--awb-space-md,1rem)","right":"var(--awb-space-md,1rem)"}},"border":{"radius":"var(--awb-radius-md,8px)","width":"1px","color":"var(--awb-color-border,#e5e7eb)"}}} -->
			<div class="wp-block-column" style="border-color:var(--awb-color-border,#e5e7eb);border-width:1px;border-radius:var(--awb-radius-md,8px);padding:var(--awb-space-md,1rem)">
				<!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"2rem"},"color":{"text":"var(--awb-color-accent,#e94560)"}}} -->
				<p class="has-text-align-center" style="color:var(--awb-color-accent,#e94560);font-size:2rem"><?php echo $icon; // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
				<!-- /wp:paragraph -->

				<!-- wp:heading {"textAlign":"center","level":3,"style":{"typography":{"fontSize":"1.25rem"}}} -->
				<h3 class="has-text-align-center" style="font-size:1.25rem"><?php echo esc_html($title); ?></h3>
				<!-- /wp:heading -->

				<!-- wp:paragraph {"align":"center"} -->
				<p class="has-text-align-center"><?php echo esc_html($text); ?></p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:column -->
		<?php endforeach; ?>
	</div>
	<!-- /wp:columns -->
</div>
<!-- /wp:group -->
