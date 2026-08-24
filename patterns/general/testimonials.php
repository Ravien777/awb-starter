<?php
/**
 * Title: Testimonials Row
 * Slug: testimonials-row
 * Categories: general, marketing
 * Keywords: testimonials, reviews, social proof
 * Description: Three testimonial cards with large quote marks, reviewer names and roles.
 */
?>
<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var(--awb-space-xl,4rem)","bottom":"var(--awb-space-xl,4rem)","left":"var(--awb-space-lg,2rem)","right":"var(--awb-space-lg,2rem)"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull" style="padding:var(--awb-space-xl,4rem) var(--awb-space-lg,2rem)">
	<!-- wp:heading {"textAlign":"center","style":{"typography":{"fontFamily":"var(--awb-font-heading, Georgia, serif)"}}} -->
	<h2 class="has-text-align-center" style="font-family:var(--awb-font-heading, Georgia, serif)">What clients say</h2>
	<!-- /wp:heading -->

	<!-- wp:columns {"style":{"spacing":{"blockGap":{"top":"var(--awb-space-lg,2rem)","left":"var(--awb-space-md,1rem)"},"margin":{"top":"var(--awb-space-lg,2rem)"}}}} -->
	<div class="wp-block-columns" style="margin-top:var(--awb-space-lg,2rem)">
		<?php foreach ([['Working with them was effortless. The site launched ahead of schedule and looks better than we imagined.', 'Sarah J.', 'Owner, Bloom Studio'], ['Clear communication the whole way through. Our traffic doubled within two months of launch.', 'Marcus T.', 'Founder, Trailhead Coffee'], ['They understood our brand instantly. The new site finally matches the quality of our work.', 'Elena R.', 'Director, Northside Clinic']] as [$quote, $name, $role]) : ?>
			<!-- wp:column -->
			<div class="wp-block-column">
				<!-- wp:group {"style":{"border":{"radius":"var(--awb-radius-md,8px)","width":"1px","color":"var(--awb-color-border,#e5e7eb)"},"spacing":{"padding":{"top":"var(--awb-space-md,1rem)","bottom":"var(--awb-space-md,1rem)","left":"var(--awb-space-md,1rem)","right":"var(--awb-space-md,1rem)"}}},"layout":{"type":"constrained"}} -->
				<div class="wp-block-group" style="border-color:var(--awb-color-border,#e5e7eb);border-width:1px;border-radius:var(--awb-radius-md,8px);padding:var(--awb-space-md,1rem)">
					<!-- wp:paragraph {"style":{"typography":{"fontSize":"2.5rem","lineHeight":"1"},"color":{"text":"var(--awb-color-accent,#e94560)"}}} -->
					<p style="color:var(--awb-color-accent,#e94560);font-size:2.5rem;line-height:1">&ldquo;</p>
					<!-- /wp:paragraph -->

					<!-- wp:paragraph -->
					<p><?php echo esc_html($quote); ?></p>
					<!-- /wp:paragraph -->

					<!-- wp:paragraph {"style":{"typography":{"fontWeight":"700","fontSize":"0.95rem"}}} -->
					<p style="font-weight:700;font-size:0.95rem"><?php echo esc_html($name); ?></p>
					<!-- /wp:paragraph -->

					<!-- wp:paragraph {"style":{"typography":{"fontSize":"0.85rem"},"color":{"text":"#646970"}}} -->
					<p style="color:#646970;font-size:0.85rem"><?php echo esc_html($role); ?></p>
					<!-- /wp:paragraph -->
				</div>
				<!-- /wp:group -->
			</div>
			<!-- /wp:column -->
		<?php endforeach; ?>
	</div>
	<!-- /wp:columns -->
</div>
<!-- /wp:group -->
