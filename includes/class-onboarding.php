<?php

/**
 * First-run onboarding checklist.
 *
 * Computes setup progress from real plugin state and renders a dismissible
 * checklist at the top of every AWB Starter admin page until completed
 * (or dismissed by the user).
 *
 * @package AWBStarter
 */
if (! defined('ABSPATH')) {
	exit;
}

class AWB_Onboarding
{
	/**
	 * Setup steps derived from actual plugin configuration.
	 *
	 * @return array<int, array{id: string, label: string, desc: string, url: string, done: bool}>
	 */
	public static function steps(): array
	{
		$base = admin_url('admin.php?page=awb-starter');

		$has_ai_key = false;
		if (class_exists('AWB_AI_Generator')) {
			foreach (array_keys(AWB_AI_Generator::get_providers()) as $slug) {
				if ('' !== get_option('awb_ai_' . $slug . '_key', '')) {
					$has_ai_key = true;
					break;
				}
			}
		}

		$header_set = '' !== get_option(AWB_Header_Switcher::OPTION_HEADER_TYPE, '')
			&& 'none' !== get_option(AWB_Header_Switcher::OPTION_HEADER_TYPE, '');
		$footer_set = '' !== get_option(AWB_Header_Switcher::OPTION_FOOTER_TYPE, '')
			&& 'none' !== get_option(AWB_Header_Switcher::OPTION_FOOTER_TYPE, '');

		return [
			[
				'id'   => 'tokens',
				'label' => __('Set your design tokens', 'awb-starter'),
				'desc' => __('Brand colors, fonts and spacing used across all patterns.', 'awb-starter'),
				'url'  => $base . '&tab=tokens',
				'done' => '' !== get_option('awb_token_color_primary', ''),
			],
			[
				'id'   => 'ai',
				'label' => __('Add an AI API key', 'awb-starter'),
				'desc' => __('Optional — enables the AI content generator.', 'awb-starter'),
				'url'  => $base . '&tab=css-js',
				'done' => $has_ai_key,
			],
			[
				'id'   => 'header-footer',
				'label' => __('Pick a header & footer pattern', 'awb-starter'),
				'desc' => __('Applied site-wide on the frontend.', 'awb-starter'),
				'url'  => $base . '&tab=header-footer',
				'done' => $header_set || $footer_set,
			],
			[
				'id'   => 'scaffold',
				'label' => __('Run a site scaffold', 'awb-starter'),
				'desc' => __('One click creates standard pages and navigation.', 'awb-starter'),
				'url'  => $base . '&tab=scaffold',
				'done' => (int) get_option('page_on_front', 0) > 0 || '' !== get_option('awb_scaffold_completed', ''),
			],
			[
				'id'   => 'library',
				'label' => __('Browse the Pattern Library', 'awb-starter'),
				'desc' => __('Preview, clone and insert ready-made sections.', 'awb-starter'),
				'url'  => $base . '&tab=patterns',
				'done' => false,
			],
		];
	}

	/**
	 * Whether the checklist should be rendered.
	 */
	public static function should_show(): bool
	{
		if (! current_user_can('manage_options')) {
			return false;
		}
		if ('1' === get_option('awb_onboarding_dismissed', '')) {
			return false;
		}
		foreach (self::steps() as $step) {
			if (! $step['done']) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Render the checklist card.
	 */
	public static function render(): void
	{
		if (! self::should_show()) {
			return;
		}
		$steps     = self::steps();
		$total     = count($steps);
		$completed = count(array_filter($steps, fn($s) => $s['done']));
		?>
		<div class="awb-onboarding" id="awb-onboarding">
			<div class="awb-onboarding__head">
				<h3><?php esc_html_e('Get started with AWB Starter', 'awb-starter'); ?></h3>
				<span class="awb-onboarding__progress"><?php printf(esc_html__('%d of %d done', 'awb-starter'), $completed, $total); ?></span>
				<button type="button" class="awb-onboarding__dismiss" id="awb-onboarding-dismiss"
					data-nonce="<?php echo esc_attr(wp_create_nonce('awb_dismiss_onboarding')); ?>"
					aria-label="<?php esc_attr_e('Dismiss setup checklist', 'awb-starter'); ?>">&#10005;</button>
			</div>
			<ol class="awb-onboarding__list">
				<?php foreach ($steps as $step) : ?>
					<li class="awb-onboarding-step <?php echo $step['done'] ? 'is-done' : ''; ?>">
						<span class="awb-onboarding-step__check" aria-hidden="true"><?php echo $step['done'] ? '&#10003;' : ''; ?></span>
						<a href="<?php echo esc_url($step['url']); ?>" class="awb-onboarding-step__label">
							<strong><?php echo esc_html($step['label']); ?></strong>
							<span><?php echo esc_html($step['desc']); ?></span>
						</a>
					</li>
				<?php endforeach; ?>
			</ol>
		</div>
		<?php
	}

	/**
	 * Persist dismissal (invoked via AJAX by AWB_Ajax_Handler).
	 */
	public static function dismiss(): void
	{
		update_option('awb_onboarding_dismissed', '1');
	}
}
