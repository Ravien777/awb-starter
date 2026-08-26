<?php

/**
 * REST API layer for AWB Starter.
 *
 * Registers /awb/v1/* routes that mirror the AJAX capabilities exposed
 * in class-ajax-handler.php. Every endpoint delegates to the underlying
 * domain class and returns a standard WP_REST_Response — no wp_send_json
 * die. Designed for MCP / agentic tool access.
 *
 * Authentication: Standard WP cookie / application password auth.
 * Capability checks mirror the AJAX handlers: manage_options for most
 * write operations, edit_posts for AI generate, edit_pages for AI draft.
 *
 * @package AWBStarter
 * @since   2.3.0
 */
if (! defined('ABSPATH')) {
	exit;
}

class AWB_REST
{
	private const NAMESPACE = 'awb/v1';

	public function __construct()
	{
		add_action('rest_api_init', [$this, 'register_routes']);
	}

	public function register_routes(): void
	{
		// ── Options ──────────────────────────────────────────────────────
		register_rest_route(self::NAMESPACE, '/options', [
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [$this, 'get_options'],
				'permission_callback' => [$this, 'can_manage_options'],
			],
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [$this, 'update_options'],
				'permission_callback' => [$this, 'can_manage_options'],
				'args'                => $this->get_options_args(),
			],
		]);

		// ── Patterns ─────────────────────────────────────────────────────
		register_rest_route(self::NAMESPACE, '/patterns', [
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => [$this, 'list_patterns'],
			'permission_callback' => [$this, 'can_manage_options'],
		]);

		register_rest_route(self::NAMESPACE, '/patterns', [
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => [$this, 'create_pattern'],
			'permission_callback' => [$this, 'can_manage_options'],
			'args'                => $this->create_pattern_args(),
		]);

		// ── Single Pattern ───────────────────────────────────────────────
		register_rest_route(self::NAMESPACE, '/patterns/(?P<name>awb/[\w-]+)', [
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [$this, 'get_pattern'],
				'permission_callback' => [$this, 'can_manage_options'],
			],
			[
				'methods'             => 'PUT',
				'callback'            => [$this, 'update_pattern'],
				'permission_callback' => [$this, 'can_manage_options'],
				'args'                => $this->update_pattern_args(),
			],
			[
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => [$this, 'delete_pattern'],
				'permission_callback' => [$this, 'can_manage_options'],
			],
		]);

		// ── Duplicate ────────────────────────────────────────────────────
		register_rest_route(self::NAMESPACE, '/patterns/(?P<name>awb/[\w-]+)/duplicate', [
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => [$this, 'duplicate_pattern'],
			'permission_callback' => [$this, 'can_manage_options'],
		]);

		// ── Export ────────────────────────────────────────────────────────
		register_rest_route(self::NAMESPACE, '/patterns/(?P<name>awb/[\w-]+)/export', [
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => [$this, 'export_pattern'],
			'permission_callback' => [$this, 'can_manage_options'],
		]);

		// ── Preview ───────────────────────────────────────────────────────
		register_rest_route(self::NAMESPACE, '/patterns/(?P<name>awb/[\w-]+)/preview', [
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => [$this, 'preview_pattern'],
			'permission_callback' => [$this, 'can_manage_options'],
		]);

		// ── Import ────────────────────────────────────────────────────────
		register_rest_route(self::NAMESPACE, '/patterns/import', [
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => [$this, 'import_pattern'],
			'permission_callback' => [$this, 'can_manage_options'],
		]);

		// ── Scaffold ──────────────────────────────────────────────────────
		register_rest_route(self::NAMESPACE, '/scaffold', [
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => [$this, 'run_scaffold'],
			'permission_callback' => [$this, 'can_manage_options'],
			'args'                => $this->scaffold_args(),
		]);

		// ── AI ────────────────────────────────────────────────────────────
		register_rest_route(self::NAMESPACE, '/ai/generate', [
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => [$this, 'ai_generate'],
			'permission_callback' => [$this, 'can_edit_posts'],
			'args'                => $this->ai_generate_args(),
		]);

		register_rest_route(self::NAMESPACE, '/ai/draft', [
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => [$this, 'ai_draft'],
			'permission_callback' => [$this, 'can_edit_pages'],
			'args'                => $this->ai_generate_args(),
		]);

		// ── Store ─────────────────────────────────────────────────────────
		register_rest_route(self::NAMESPACE, '/store/manifest', [
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => [$this, 'get_store_manifest'],
			'permission_callback' => [$this, 'can_manage_options'],
		]);

		register_rest_route(self::NAMESPACE, '/store/install', [
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => [$this, 'install_store_pattern'],
			'permission_callback' => [$this, 'can_manage_options'],
			'args'                => $this->store_install_args(),
		]);

		// ── Header / Footer ──────────────────────────────────────────────
		register_rest_route(self::NAMESPACE, '/header-footer', [
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [$this, 'get_header_footer'],
				'permission_callback' => [$this, 'can_manage_options'],
			],
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [$this, 'save_header_footer'],
				'permission_callback' => [$this, 'can_manage_options'],
				'args'                => $this->header_footer_args(),
			],
		]);

		// ── Onboarding ────────────────────────────────────────────────────
		register_rest_route(self::NAMESPACE, '/onboarding/dismiss', [
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => [$this, 'dismiss_onboarding'],
			'permission_callback' => [$this, 'can_manage_options'],
		]);

		// ── Pattern Sync ────────────────────────────────────────────────
		register_rest_route(self::NAMESPACE, '/patterns/(?P<name>awb/[\w-]+)/sync', [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [$this, 'set_pattern_sync'],
				'permission_callback' => [$this, 'can_manage_options'],
				'args'                => [
					'synced' => ['type' => 'boolean', 'required' => true],
				],
			],
		]);

		register_rest_route(self::NAMESPACE, '/patterns/(?P<name>awb/[\w-]+)/sync/usages', [
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => [$this, 'get_sync_usages'],
			'permission_callback' => [$this, 'can_manage_options'],
		]);

		register_rest_route(self::NAMESPACE, '/patterns/(?P<name>awb/[\w-]+)/sync/convert', [
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => [$this, 'convert_sync_page'],
			'permission_callback' => [$this, 'can_manage_options'],
			'args'                => [
				'post_id' => ['type' => 'integer', 'required' => true],
			],
		]);
	}

	// =========================================================================
	// Permission callbacks
	// =========================================================================

	public function can_manage_options(): bool
	{
		return current_user_can('manage_options');
	}

	public function can_edit_posts(): bool
	{
		return current_user_can('edit_posts');
	}

	public function can_edit_pages(): bool
	{
		return current_user_can('edit_pages');
	}

	// =========================================================================
	// Options
	// =========================================================================

	public function get_options(WP_REST_Request $request): WP_REST_Response
	{
		$keys = [
			'awb_token_color_primary', 'awb_token_color_secondary', 'awb_token_color_accent',
			'awb_token_color_text', 'awb_token_color_bg',
			'awb_token_font_heading', 'awb_token_font_body', 'awb_token_font_mono',
			'awb_token_space_xs', 'awb_token_space_sm', 'awb_token_space_md',
			'awb_token_space_lg', 'awb_token_space_xl',
			'awb_token_radius_sm', 'awb_token_radius_md', 'awb_token_radius_lg',
			'awb_custom_font_regular', 'awb_custom_font_medium', 'awb_custom_font_bold',
			'awb_custom_css', 'awb_custom_js',
			'awb_ai_provider',
			'awb_ai_anthropic_key', 'awb_ai_openai_key', 'awb_ai_qwen_key',
			'awb_ai_deepseek_key', 'awb_ai_groq_key',
			'awb_ai_business_name', 'awb_ai_business_desc',
			'awb_header_type', 'awb_header_value',
			'awb_footer_type', 'awb_footer_value',
			AWB_Store::OPTION_MANIFEST_URL,
		];

		$options = [];
		foreach ($keys as $key) {
			$options[$key] = get_option($key, '');
		}
		$options['awb_scaffold_completed'] = get_option('awb_scaffold_completed', '');
		$options['awb_onboarding_dismissed'] = get_option('awb_onboarding_dismissed', '');
		$options['awb_synced_patterns'] = AWB_Pattern_Sync::get_synced();

		return new WP_REST_Response($options, 200);
	}

	public function update_options(WP_REST_Request $request): WP_REST_Response
	{
		$params = $request->get_json_params();
		if (! is_array($params)) {
			return new WP_REST_Response(['message' => 'Invalid JSON body.'], 400);
		}

		$allowed = [
			'awb_token_color_primary', 'awb_token_color_secondary', 'awb_token_color_accent',
			'awb_token_color_text', 'awb_token_color_bg',
			'awb_token_font_heading', 'awb_token_font_body', 'awb_token_font_mono',
			'awb_token_space_xs', 'awb_token_space_sm', 'awb_token_space_md',
			'awb_token_space_lg', 'awb_token_space_xl',
			'awb_token_radius_sm', 'awb_token_radius_md', 'awb_token_radius_lg',
			'awb_custom_font_regular', 'awb_custom_font_medium', 'awb_custom_font_bold',
			'awb_custom_css', 'awb_custom_js',
			'awb_ai_provider',
			'awb_ai_anthropic_key', 'awb_ai_openai_key', 'awb_ai_qwen_key',
			'awb_ai_deepseek_key', 'awb_ai_groq_key',
			'awb_ai_business_name', 'awb_ai_business_desc',
			AWB_Store::OPTION_MANIFEST_URL,
		];

		$saved = 0;
		foreach ($params as $key => $value) {
			if (! in_array($key, $allowed, true)) {
				continue;
			}
			if (str_ends_with($key, '_key') || str_ends_with($key, '_css') || str_ends_with($key, '_js')) {
				$value = wp_strip_all_tags($value);
			} else {
				$value = sanitize_text_field($value);
			}
			update_option($key, $value);
			$saved++;
		}

		return new WP_REST_Response(['saved' => $saved], 200);
	}

	// =========================================================================
	// Patterns — list
	// =========================================================================

	public function list_patterns(WP_REST_Request $request): WP_REST_Response
	{
		$registry = WP_Block_Patterns_Registry::get_instance();
		$all      = $registry->get_all_registered();
		$patterns = [];

		foreach ($all as $pattern) {
			if (empty($pattern['name']) || 0 !== strpos($pattern['name'], 'awb/')) {
				continue;
			}
			$slug     = str_replace('awb/', '', $pattern['name']);
			$source   = AWB_Pattern_Loader::$pattern_source[$pattern['name']] ?? 'core';
			$has_css  = ! empty(AWB_Pattern_Loader::$pattern_assets[$pattern['name']]['css']);
			$has_js   = ! empty(AWB_Pattern_Loader::$pattern_assets[$pattern['name']]['js']);

			$patterns[] = [
				'name'        => $pattern['name'],
				'title'       => $pattern['title'] ?? '',
				'description' => $pattern['description'] ?? '',
				'categories'  => $pattern['categories'] ?? [],
				'keywords'    => $pattern['keywords'] ?? [],
				'source'      => $source,
				'has_css'     => $has_css,
				'has_js'      => $has_js,
				'synced'      => AWB_Pattern_Sync::is_synced($pattern['name']),
				'usage_count' => AWB_Pattern_Loader::get_usage_count($pattern['name']),
			];
		}

		return new WP_REST_Response($patterns, 200);
	}

	// =========================================================================
	// Patterns — create
	// =========================================================================

	public function create_pattern(WP_REST_Request $request): WP_REST_Response
	{
		$params  = $request->get_json_params();
		$slug    = sanitize_title($params['slug'] ?? '');
		$content = $params['content'] ?? '';
		$meta    = [
			'title'       => $params['title'] ?? $slug,
			'description' => $params['description'] ?? '',
			'categories'  => $params['categories'] ?? '',
			'keywords'    => $params['keywords'] ?? '',
		];

		if (empty($slug) || empty($content)) {
			return new WP_REST_Response(['message' => 'Slug and content are required.'], 400);
		}

		$result = AWB_Pattern_Loader::create_user_pattern($slug, $meta, $content, false);
		if (is_wp_error($result)) {
			return new WP_REST_Response(['message' => $result->get_error_message()], 400);
		}

		return new WP_REST_Response($result, 201);
	}

	// =========================================================================
	// Patterns — get single
	// =========================================================================

	public function get_pattern(WP_REST_Request $request): WP_REST_Response
	{
		$name = $request->get_param('name');
		if (empty($name) || 0 !== strpos($name, 'awb/')) {
			return new WP_REST_Response(['message' => 'Invalid pattern name.'], 400);
		}

		$files  = AWB_Pattern_Loader::$pattern_files;
		$source = AWB_Pattern_Loader::$pattern_source[$name] ?? null;

		if (empty($files[$name])) {
			return new WP_REST_Response(['message' => 'Pattern not found.'], 404);
		}

		$filepath = $files[$name];
		$meta     = get_file_data($filepath, [
			'title'       => 'Title',
			'slug'        => 'Slug',
			'categories'  => 'Categories',
			'keywords'    => 'Keywords',
			'description' => 'Description',
		]);

		$content = '';
		if (is_readable($filepath)) {
			$content = file_get_contents($filepath); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		}

		$asset_entry = AWB_Pattern_Loader::$pattern_assets[$name] ?? [];
		$css_rel     = $asset_entry['css'] ?? '';
		$js_rel      = $asset_entry['js'] ?? '';

		$css_content = '';
		if ($css_rel) {
			$base = ('user' === $source) ? AWB_USER_PATTERNS_PATH : AWB_PLUGIN_PATH;
			$abs  = $base . ltrim($css_rel, '/');
			if (is_readable($abs)) {
				$css_content = file_get_contents($abs); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			}
		}

		$js_content = '';
		if ($js_rel) {
			$base = ('user' === $source) ? AWB_USER_PATTERNS_PATH : AWB_PLUGIN_PATH;
			$abs  = $base . ltrim($js_rel, '/');
			if (is_readable($abs)) {
				$js_content = file_get_contents($abs); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			}
		}

		return new WP_REST_Response([
			'name'        => $name,
			'title'       => sanitize_text_field($meta['title'] ?? ''),
			'slug'        => sanitize_title($meta['slug'] ?? ''),
			'categories'  => array_map('trim', explode(',', $meta['categories'] ?? '')),
			'keywords'    => array_map('trim', explode(',', $meta['keywords'] ?? '')),
			'description' => sanitize_text_field($meta['description'] ?? ''),
			'source'      => $source,
			'content'     => $content,
			'css'         => $css_content,
			'js'          => $js_content,
			'synced'      => AWB_Pattern_Sync::is_synced($name),
			'usage_count' => AWB_Pattern_Loader::get_usage_count($name),
		], 200);
	}

	// =========================================================================
	// Patterns — update
	// =========================================================================

	public function update_pattern(WP_REST_Request $request): WP_REST_Response
	{
		$name   = $request->get_param('name');
		$params = $request->get_json_params();

		if (empty($name) || 0 !== strpos($name, 'awb/')) {
			return new WP_REST_Response(['message' => 'Invalid pattern name.'], 400);
		}

		$source = AWB_Pattern_Loader::$pattern_source[$name] ?? '';
		if ('user' !== $source) {
			return new WP_REST_Response(['message' => 'Only user patterns can be modified.'], 403);
		}

		$files = [];
		if (isset($params['content'])) {
			$files['php'] = $params['content'];
		}
		if (isset($params['css'])) {
			$files['css'] = $params['css'];
		}
		if (isset($params['js'])) {
			$files['js'] = $params['js'];
		}

		if (empty($files)) {
			return new WP_REST_Response(['message' => 'No file content provided.'], 400);
		}

		$result = AWB_Pattern_Loader::write_user_pattern_files($name, $files);
		if (is_wp_error($result)) {
			return new WP_REST_Response(['message' => $result->get_error_message()], 500);
		}

		return new WP_REST_Response(['message' => 'Pattern files saved.'], 200);
	}

	// =========================================================================
	// Patterns — delete
	// =========================================================================

	public function delete_pattern(WP_REST_Request $request): WP_REST_Response
	{
		$name = $request->get_param('name');
		if (empty($name) || 0 !== strpos($name, 'awb/')) {
			return new WP_REST_Response(['message' => 'Invalid pattern name.'], 400);
		}

		$source = AWB_Pattern_Loader::$pattern_source[$name] ?? '';
		if ('user' !== $source) {
			return new WP_REST_Response(['message' => 'Only user patterns can be deleted.'], 403);
		}

		$php_path = AWB_Pattern_Loader::$pattern_files[$name] ?? '';
		if (! $php_path || ! AWB_Pattern_Loader::is_path_within($php_path, AWB_USER_PATTERNS_PATH)) {
			return new WP_REST_Response(['message' => 'Pattern file not found or path invalid.'], 404);
		}

		global $wp_filesystem;
		$fs_ok = false;
		if (! empty($wp_filesystem) && is_object($wp_filesystem)) {
			$fs_ok = true;
		} else {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			$fs_ok = WP_Filesystem();
		}

		$deleted  = 0;
		$del_file = function ($path) use ($wp_filesystem, $fs_ok) {
			return $fs_ok
				? ($wp_filesystem->exists($path) && $wp_filesystem->delete($path))
				: (file_exists($path) && unlink($path));
		};

		if ($del_file($php_path)) {
			$deleted++;
		}
		$meta      = get_file_data($php_path, ['css' => 'CSS', 'js' => 'JS']);
		$base_path = trailingslashit(AWB_USER_PATTERNS_PATH);
		if (! empty($meta['css'])) {
			$css_path = $base_path . ltrim($meta['css'], '/');
			if (AWB_Pattern_Loader::is_path_within($css_path, AWB_USER_PATTERNS_PATH) && $del_file($css_path)) {
				$deleted++;
			}
		}
		if (! empty($meta['js'])) {
			$js_path = $base_path . ltrim($meta['js'], '/');
			if (AWB_Pattern_Loader::is_path_within($js_path, AWB_USER_PATTERNS_PATH) && $del_file($js_path)) {
				$deleted++;
			}
		}

		if ($deleted === 0) {
			return new WP_REST_Response(['message' => 'Failed to delete pattern files.'], 500);
		}

		return new WP_REST_Response(['message' => 'Pattern deleted successfully.'], 200);
	}

	// =========================================================================
	// Patterns — duplicate
	// =========================================================================

	public function duplicate_pattern(WP_REST_Request $request): WP_REST_Response
	{
		$name = $request->get_param('name');
		if (empty($name) || 0 !== strpos($name, 'awb/')) {
			return new WP_REST_Response(['message' => 'Invalid pattern name.'], 400);
		}

		$result = AWB_Pattern_Duplicator::duplicate($name);
		if (is_wp_error($result)) {
			return new WP_REST_Response(['message' => $result->get_error_message()], 500);
		}

		return new WP_REST_Response([
			'new_registered_name' => $result['new_registered_name'],
			'new_slug'            => $result['new_slug'],
			'new_title'           => $result['new_title'],
			'message'             => sprintf(__('Pattern duplicated as "%s".', 'awb-starter'), $result['new_title']),
		], 201);
	}

	// =========================================================================
	// Patterns — export (base64-encoded zip)
	// =========================================================================

	public function export_pattern(WP_REST_Request $request): WP_REST_Response
	{
		$name = $request->get_param('name');
		if (empty($name) || 0 !== strpos($name, 'awb/')) {
			return new WP_REST_Response(['message' => 'Invalid pattern name.'], 400);
		}

		$zip_path = AWB_Pattern_Exporter::build($name);
		if (is_wp_error($zip_path)) {
			return new WP_REST_Response(['message' => $zip_path->get_error_message()], 500);
		}

		$slug = str_replace('awb/', '', $name);
		$raw  = file_get_contents($zip_path); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		@unlink($zip_path); // phpcs:ignore WordPress.PHP.NoSilencedErrors

		if (false === $raw) {
			return new WP_REST_Response(['message' => 'Failed to read ZIP archive.'], 500);
		}

		return new WP_REST_Response([
			'filename' => 'awb-pattern-' . $slug . '.zip',
			'size'     => strlen($raw),
			'base64'   => base64_encode($raw),
		], 200);
	}

	// =========================================================================
	// Patterns — preview
	// =========================================================================

	public function preview_pattern(WP_REST_Request $request): WP_REST_Response
	{
		$name = $request->get_param('name');
		if (empty($name) || 0 !== strpos($name, 'awb/')) {
			return new WP_REST_Response(['message' => 'Invalid pattern name.'], 400);
		}

		$pattern = WP_Block_Patterns_Registry::get_instance()->get_registered($name);
		if (! is_array($pattern) || empty($pattern['content'])) {
			return new WP_REST_Response(['message' => 'Pattern content not found.'], 404);
		}

		$assets   = AWB_Pattern_Loader::$pattern_assets[$name] ?? [];
		$source   = $assets['source'] ?? 'core';
		$base_url = ('user' === $source) ? AWB_USER_PATTERNS_URL : AWB_PLUGIN_URL;
		$base_path = ('user' === $source) ? AWB_USER_PATTERNS_PATH : AWB_PLUGIN_PATH;

		$css_urls = [];
		$frontend_css = AWB_PLUGIN_URL . 'assets/css/frontend.css';
		if (file_exists(AWB_PLUGIN_PATH . 'assets/css/frontend.css')) {
			$css_urls[] = $frontend_css;
		}
		if (! empty($assets['css'])) {
			$abs = $base_path . ltrim($assets['css'], '/');
			if (file_exists($abs)) {
				$css_urls[] = $base_url . ltrim($assets['css'], '/');
			}
		}

		return new WP_REST_Response([
			'title'   => $pattern['title'] ?? '',
			'content' => $pattern['content'],
			'css'     => $css_urls,
			'tokens'  => AWB_Asset_Loader::generate_design_tokens_css(),
		], 200);
	}

	// =========================================================================
	// Patterns — import
	// =========================================================================

	public function import_pattern(WP_REST_Request $request): WP_REST_Response
	{
		$files = $request->get_file_params();
		if (empty($files['awb_pattern_zip']) || ! isset($files['awb_pattern_zip']['tmp_name'])) {
			return new WP_REST_Response(['message' => 'No file uploaded.'], 400);
		}

		$tmp_path = $files['awb_pattern_zip']['tmp_name'];
		$force    = ! empty($request->get_param('force'));
		$result   = AWB_Pattern_Importer::install_from_zip($tmp_path, $force);

		if ($result['success']) {
			return new WP_REST_Response($result['data'], 201);
		}

		$error_data = ['message' => $result['error'] ?? __('Import failed.', 'awb-starter')];
		$status     = 500;
		if (isset($result['collision']) && $result['collision']) {
			$status         = 409;
			$error_data['code']  = 'collision';
			$error_data['title'] = $result['title'];
			$error_data['slug']  = $result['slug'];
			$error_data['files'] = $result['files'];
		}

		return new WP_REST_Response($error_data, $status);
	}

	// =========================================================================
	// Scaffold
	// =========================================================================

	public function run_scaffold(WP_REST_Request $request): WP_REST_Response
	{
		$type = sanitize_key($request->get_param('type') ?? '');
		if (! array_key_exists($type, AWB_Scaffold::definitions())) {
			return new WP_REST_Response(['message' => 'Unknown scaffold type.'], 400);
		}

		$log = AWB_Scaffold::run($type);
		return new WP_REST_Response(['log' => $log], 200);
	}

	// =========================================================================
	// AI — generate
	// =========================================================================

	public function ai_generate(WP_REST_Request $request): WP_REST_Response
	{
		$prompt = sanitize_textarea_field($request->get_param('prompt') ?? '');
		if (empty($prompt)) {
			return new WP_REST_Response(['message' => 'No prompt provided.'], 400);
		}

		$options = [
			'mode'     => sanitize_key($request->get_param('mode') ?? 'blocks'),
			'tone'     => sanitize_key($request->get_param('tone') ?? ''),
			'template' => sanitize_title($request->get_param('template') ?? ''),
		];

		$result = AWB_AI_Generator::generate($prompt, $options);
		if (is_wp_error($result)) {
			return new WP_REST_Response(['message' => $result->get_error_message()], 500);
		}

		return new WP_REST_Response(['blocks' => $result], 200);
	}

	// =========================================================================
	// AI — draft
	// =========================================================================

	public function ai_draft(WP_REST_Request $request): WP_REST_Response
	{
		$prompt = sanitize_textarea_field($request->get_param('prompt') ?? '');
		if (empty($prompt)) {
			return new WP_REST_Response(['message' => 'No prompt provided.'], 400);
		}

		$options = [
			'mode'     => sanitize_key($request->get_param('mode') ?? 'blocks'),
			'tone'     => sanitize_key($request->get_param('tone') ?? ''),
			'template' => sanitize_title($request->get_param('template') ?? ''),
		];

		$result = AWB_AI_Generator::create_draft_page($prompt, $options);
		if (is_wp_error($result)) {
			return new WP_REST_Response(['message' => $result->get_error_message()], 500);
		}

		return new WP_REST_Response($result, 201);
	}

	// =========================================================================
	// Store — manifest
	// =========================================================================

	public function get_store_manifest(WP_REST_Request $request): WP_REST_Response
	{
		$patterns = AWB_Store::fetch_manifest();
		if (is_wp_error($patterns)) {
			$status = 'not_configured' === $patterns->get_error_code() ? 404 : 500;
			return new WP_REST_Response(['message' => $patterns->get_error_message()], $status);
		}

		return new WP_REST_Response(['patterns' => $patterns], 200);
	}

	// =========================================================================
	// Store — install
	// =========================================================================

	public function install_store_pattern(WP_REST_Request $request): WP_REST_Response
	{
		$url = esc_url_raw($request->get_param('url') ?? '');
		if (empty($url)) {
			return new WP_REST_Response(['message' => 'No URL provided.'], 400);
		}

		$result = AWB_Store::install($url);
		if (is_wp_error($result)) {
			$data = ['message' => $result->get_error_message()];
			$code = $result->get_error_code();
			if ('collision' === $code || 'install_failed' === $code) {
				$error_data = $result->get_error_data();
				if (is_array($error_data)) {
					$data = array_merge($data, $error_data);
				}
			}
			$status = 'host_not_allowed' === $code ? 403 : 500;
			if ('not_configured' === $code || 'no_url' === $code) {
				$status = 400;
			}
			return new WP_REST_Response($data, $status);
		}

		return new WP_REST_Response($result, 201);
	}

	// =========================================================================
	// Header / Footer
	// =========================================================================

	public function get_header_footer(WP_REST_Request $request): WP_REST_Response
	{
		return new WP_REST_Response(AWB_Header_Switcher::get_settings(), 200);
	}

	public function save_header_footer(WP_REST_Request $request): WP_REST_Response
	{
		$params = $request->get_json_params();
		if (! is_array($params)) {
			return new WP_REST_Response(['message' => 'Invalid JSON body.'], 400);
		}

		$result = AWB_Header_Switcher::save_settings($params);
		if (is_wp_error($result)) {
			return new WP_REST_Response(['message' => $result->get_error_message()], 400);
		}

		return new WP_REST_Response(['message' => 'Settings saved.'], 200);
	}

	// =========================================================================
	// Onboarding
	// =========================================================================

	public function dismiss_onboarding(WP_REST_Request $request): WP_REST_Response
	{
		AWB_Onboarding::dismiss();
		return new WP_REST_Response(['message' => 'Onboarding dismissed.'], 200);
	}

	// =========================================================================
	// Pattern Sync
	// =========================================================================

	public function set_pattern_sync(WP_REST_Request $request): WP_REST_Response
	{
		$name = $request->get_param('name');
		if (empty($name) || 0 !== strpos($name, 'awb/')) {
			return new WP_REST_Response(['message' => 'Invalid pattern name.'], 400);
		}

		$synced = (bool) $request->get_param('synced');
		AWB_Pattern_Sync::set_synced($name, $synced);

		return new WP_REST_Response([
			'synced'  => $synced,
			'message' => $synced
				? __('Pattern is now synced.', 'awb-starter')
				: __('Pattern sync disabled.', 'awb-starter'),
		], 200);
	}

	public function get_sync_usages(WP_REST_Request $request): WP_REST_Response
	{
		$name = $request->get_param('name');
		if (empty($name) || 0 !== strpos($name, 'awb/')) {
			return new WP_REST_Response(['message' => 'Invalid pattern name.'], 400);
		}

		$posts = AWB_Pattern_Sync::get_usage_posts($name);
		$ref   = AWB_Pattern_Sync::reference_markup($name);

		$usages = [];
		foreach ($posts as $post) {
			$content = get_post_field('post_content', $post->ID);
			$synced  = strpos($content, $ref) !== false;
			$usage   = [
				'id'             => (int) $post->ID,
				'title'          => $post->post_title,
				'name'           => $post->post_name,
				'already_synced' => $synced,
				'drifted'        => false,
				'diff_summary'   => '',
			];

			if (! $synced) {
				$drift = AWB_Pattern_Sync::page_drift($name, $post->ID);
				$usage['drifted']      = $drift['drifted'];
				$usage['diff_summary'] = $drift['diff_summary'];
			}

			$usages[] = $usage;
		}

		return new WP_REST_Response(['usages' => $usages], 200);
	}

	public function convert_sync_page(WP_REST_Request $request): WP_REST_Response
	{
		$name    = $request->get_param('name');
		$post_id = (int) $request->get_param('post_id');

		if (empty($name) || 0 !== strpos($name, 'awb/')) {
			return new WP_REST_Response(['message' => 'Invalid pattern name.'], 400);
		}
		if ($post_id <= 0) {
			return new WP_REST_Response(['message' => 'Invalid post ID.'], 400);
		}

		$result = AWB_Pattern_Sync::convert_page($name, $post_id);
		if (is_wp_error($result)) {
			$status = 'already_synced' === $result->get_error_code() ? 409 : 500;
			return new WP_REST_Response(['message' => $result->get_error_message()], $status);
		}

		return new WP_REST_Response([
			'message' => __('Page synced successfully.', 'awb-starter'),
			'reference' => AWB_Pattern_Sync::reference_markup($name),
		], 200);
	}

	// =========================================================================
	// Argument schemas
	// =========================================================================

	private function get_options_args(): array
	{
		return [
			'required' => true,
			'type'     => 'object',
		];
	}

	private function create_pattern_args(): array
	{
		return [
			'required' => true,
			'type'     => 'object',
			'properties' => [
				'slug'    => ['type' => 'string', 'required' => true],
				'content' => ['type' => 'string', 'required' => true],
				'title'   => ['type' => 'string'],
				'description' => ['type' => 'string'],
				'categories'  => ['type' => 'string'],
				'keywords'    => ['type' => 'string'],
			],
		];
	}

	private function update_pattern_args(): array
	{
		return [
			'required' => true,
			'type'     => 'object',
			'properties' => [
				'content' => ['type' => 'string'],
				'css'     => ['type' => 'string'],
				'js'      => ['type' => 'string'],
			],
		];
	}

	private function scaffold_args(): array
	{
		return [
			'required' => true,
			'type'     => 'object',
			'properties' => [
				'type' => [
					'required'  => true,
					'type'      => 'string',
					'enum'      => array_keys(AWB_Scaffold::definitions()),
					'sanitize_callback' => 'sanitize_key',
				],
			],
		];
	}

	private function ai_generate_args(): array
	{
		return [
			'required' => true,
			'type'     => 'object',
			'properties' => [
				'prompt'   => ['type' => 'string', 'required' => true],
				'mode'     => ['type' => 'string', 'enum' => ['blocks', 'html', 'copy'], 'default' => 'blocks'],
				'tone'     => ['type' => 'string', 'enum' => ['professional', 'friendly', 'bold', 'minimal']],
				'template' => ['type' => 'string'],
			],
		];
	}

	private function store_install_args(): array
	{
		return [
			'required' => true,
			'type'     => 'object',
			'properties' => [
				'url' => ['type' => 'string', 'required' => true, 'format' => 'uri'],
			],
		];
	}

	private function header_footer_args(): array
	{
		return [
			'required' => true,
			'type'     => 'object',
			'properties' => [
				'header_type'  => ['type' => 'string', 'enum' => ['none', 'pattern', 'block']],
				'header_value' => ['type' => 'string'],
				'footer_type'  => ['type' => 'string', 'enum' => ['none', 'pattern', 'block']],
				'footer_value' => ['type' => 'string'],
			],
		];
	}
}
