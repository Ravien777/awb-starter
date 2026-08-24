<?php

/**
 * One-click site scaffolding.
 *
 * Creates standard pages from the plugin's registered block templates,
 * optionally sets a static front page, builds a primary navigation menu
 * and removes default sample content.
 *
 * @package AWBStarter
 */
if (! defined('ABSPATH')) {
	exit;
}

class AWB_Scaffold
{
	/**
	 * Scaffold definitions shown in the admin UI.
	 *
	 * @return array<string, array{label: string, pages: array<int, string>}>
	 */
	public static function definitions(): array
	{
		return [
			'business'  => ['label' => __('Business', 'awb-starter'),  'pages' => ['Home', 'About', 'Services', 'Contact']],
			'portfolio' => ['label' => __('Portfolio', 'awb-starter'), 'pages' => ['Home', 'Work', 'About', 'Contact']],
			'landing'   => ['label' => __('Landing', 'awb-starter'),   'pages' => ['Home']],
		];
	}

	/**
	 * Run a scaffold and return human-readable log lines.
	 *
	 * @param string $type Scaffold key (business|portfolio|landing).
	 * @return array<int, string> Log lines describing each action taken.
	 */
	public static function run(string $type): array
	{
		$log      = [];
		$defs     = self::definitions();
		$titles   = $defs[$type]['pages'] ?? [];
		$page_ids = [];

		if (! empty(get_option('awb_scaffold_clean', '1'))) {
			$log = array_merge($log, self::delete_sample_content());
		}

		foreach ($titles as $title) {
			$content = self::page_content($type, $title);
			$existing = self::get_page_by_title($title);

			if ($existing) {
				wp_update_post([
					'ID'           => $existing->ID,
					'post_content' => $content,
					'post_status'  => 'publish',
				]);
				$page_ids[$title] = (int) $existing->ID;
				$log[] = sprintf(__('Updated existing page "%s" (ID %d).', 'awb-starter'), $title, $existing->ID);
				continue;
			}

			$page_id = wp_insert_post([
				'post_title'   => $title,
				'post_name'    => sanitize_title($title),
				'post_content' => $content,
				'post_status'  => 'publish',
				'post_type'    => 'page',
			]);

			if (is_wp_error($page_id) || 0 === $page_id) {
				$log[] = sprintf(__('Failed to create page "%s".', 'awb-starter'), $title);
				continue;
			}
			$page_ids[$title] = (int) $page_id;
			$log[] = sprintf(__('Created page "%s" (ID %d).', 'awb-starter'), $title, $page_id);
		}

		if (! empty($page_ids['Home']) && ! empty(get_option('awb_scaffold_set_homepage', '1'))) {
			update_option('show_on_front', 'page');
			update_option('page_on_front', $page_ids['Home']);
			$log[] = sprintf(__('Set "%s" as the static front page.', 'awb-starter'), 'Home');
		}

		if (! empty(get_option('awb_scaffold_create_menu', '1'))) {
			$log = array_merge($log, self::create_menu(array_values($page_ids), $defs[$type]['label'] ?? 'Primary'));
		}

		update_option('awb_scaffold_completed', (string) time());

		return $log;
	}

	/**
	 * Resolve page content by combining the plugin's block templates.
	 *
	 * @param string $type  Scaffold key.
	 * @param string $title Page title.
	 * @return string Gutenberg block markup.
	 */
	private static function page_content(string $type, string $title): string
	{
		$template_slugs = [
			'business-Home'     => ['awb/hero-cta', 'awb/services-3col', 'awb/testimonials-3col', 'awb/cta-banner'],
			'business-About'    => ['awb/about-split'],
			'business-Services' => ['awb/services-3col', 'awb/cta-banner'],
			'portfolio-Home'    => ['awb/hero-cta', 'awb/about-split', 'awb/cta-banner'],
			'portfolio-Work'    => ['awb/services-3col'],
			'landing-Home'      => ['awb/hero-cta', 'awb/about-split', 'awb/services-3col', 'awb/testimonials-3col', 'awb/cta-banner'],
		];

		$content = '';
		foreach ($template_slugs[$type . '-' . $title] ?? [] as $slug) {
			$content .= self::get_pattern_content($slug);
		}

		if ('Contact' === $title && '' === trim($content)) {
			$content = self::contact_fallback();
		}

		return $content;
	}

	/**
	 * Fetch rendered content of a registered plugin pattern.
	 *
	 * @param string $name Registered pattern name, e.g. awb/hero-cta.
	 */
	private static function get_pattern_content(string $name): string
	{
		if (! class_exists('WP_Block_Patterns_Registry')) {
			return '';
		}
		$pattern = WP_Block_Patterns_Registry::get_instance()->get_registered($name);
		return is_array($pattern) ? ($pattern['content'] ?? '') : '';
	}

	/**
	 * Minimal contact page markup when no template matches.
	 */
	private static function contact_fallback(): string
	{
		return '<!-- wp:heading {"level":1} --><h1>' . __('Contact us', 'awb-starter') . '</h1><!-- /wp:heading -->'
			. '<!-- wp:paragraph --><p>' . __('We would love to hear from you. Reach out and we will get back to you shortly.', 'awb-starter') . '</p><!-- /wp:paragraph -->';
	}

	/**
	 * Delete the WordPress default "Hello world!" post and "Sample Page".
	 *
	 * @return array<int, string> Log lines.
	 */
	private static function delete_sample_content(): array
	{
		$log = [];
		$targets = [
			['post_type' => 'post', 'slug' => 'hello-world', 'label' => '"Hello world!"'],
			['post_type' => 'page', 'slug' => 'sample-page', 'label' => '"Sample Page"'],
		];
		foreach ($targets as $t) {
			$found = get_posts([
				'post_type'      => $t['post_type'],
				'name'           => $t['slug'],
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
			]);
			foreach ($found as $id) {
				if (wp_delete_post((int) $id, true)) {
					$log[] = sprintf(__('Deleted sample content %s.', 'awb-starter'), $t['label']);
				}
			}
		}
		return $log;
	}

	/**
	 * Create (or reuse) a navigation menu containing the scaffolded pages.
	 *
	 * @param array<int, int> $page_ids Created/reused page IDs in menu order.
	 * @param string          $label    Menu name.
	 * @return array<int, string> Log lines.
	 */
	private static function create_menu(array $page_ids, string $label): array
	{
		$log       = [];
		$menu_name = sprintf(__('%s Menu', 'awb-starter'), $label);
		$menu      = wp_get_nav_menu_object($menu_name);

		if (! $menu) {
			$menu_id = wp_create_nav_menu($menu_name);
			if (is_wp_error($menu_id)) {
				return [sprintf(__('Could not create menu "%s": %s', 'awb-starter'), $menu_name, $menu_id->get_error_message())];
			}
			$log[] = sprintf(__('Created menu "%s".', 'awb-starter'), $menu_name);
		} else {
			$menu_id = (int) $menu->term_id;
			// Remove stale items so re-running the scaffold rebuilds cleanly.
			foreach (wp_get_nav_menu_items($menu_id) ?: [] as $item) {
				wp_delete_post((int) $item->ID, true);
			}
			$log[] = sprintf(__('Reusing existing menu "%s".', 'awb-starter'), $menu_name);
		}

		foreach ($page_ids as $page_id) {
			$item_id = wp_update_nav_menu_item($menu_id, 0, [
				'menu-item-title'     => get_the_title($page_id),
				'menu-item-object'    => 'page',
				'menu-item-object-id' => $page_id,
				'menu-item-type'      => 'post_type',
				'menu-item-status'    => 'publish',
			]);
			if (is_wp_error($item_id)) {
				$log[] = sprintf(__('Failed to add page ID %d to menu.', 'awb-starter'), $page_id);
			}
		}
		$log[] = __('Added scaffolded pages to the menu.', 'awb-starter');

		$locations = get_theme_mod('nav_menu_locations', []);
		if (is_array($locations)) {
			$registered   = get_registered_nav_menus();
			$assigned     = false;
			foreach (array_keys($registered) as $location) {
				if (false !== stripos($location, 'primary') || false !== stripos($registered[$location], 'primary') || false !== stripos($registered[$location], 'main')) {
					$locations[$location] = $menu_id;
					set_theme_mod('nav_menu_locations', $locations);
					$log[] = sprintf(__('Assigned menu to "%s" location (%s).', 'awb-starter'), $location, $registered[$location]);
					$assigned = true;
					break;
				}
			}
			if (! $assigned) {
				$log[] = __('Theme has no primary menu location; menu created but not assigned.', 'awb-starter');
			}
		}

		return $log;
	}

	/**
	 * Find a published page by exact title without the deprecated helper.
	 */
	private static function get_page_by_title(string $title): ?WP_Post
	{
		$posts = get_posts([
			'post_type'      => 'page',
			'title'          => $title,
			'post_status'    => 'any',
			'posts_per_page' => 1,
		]);
		return $posts[0] ?? null;
	}
}
