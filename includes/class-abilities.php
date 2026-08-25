<?php

/**
 * Abilities API registration for MCP integration.
 *
 * Registers AWB Starter capabilities as WordPress Abilities (WP 6.9+)
 * which the official wordpress/mcp-adapter automatically exposes as MCP
 * tools. Each ability delegates to the underlying domain class.
 *
 * Requires: WordPress 6.9+ (Abilities API in core) and the
 * wordpress/mcp-adapter plugin for MCP transport.
 *
 * @package AWBStarter
 * @since   2.4.0
 */
if (! defined('ABSPATH')) {
	exit;
}

class AWB_Abilities
{
	private const NS = 'awb-starter';

	/**
	 * Register all AWB abilities with the Abilities API.
	 *
	 * Must be called from the wp_abilities_api_init action.
	 * Silently no-ops if the Abilities API is not available (WP < 6.9).
	 */
	public static function register(): void
	{
		if (! function_exists('wp_register_ability')) {
			return;
		}

		self::register_list_patterns();
		self::register_get_pattern();
		self::register_create_pattern();
		self::register_update_pattern();
		self::register_delete_pattern();
		self::register_duplicate_pattern();
		self::register_export_pattern();
		self::register_preview_pattern();
		self::register_import_pattern();
		self::register_fetch_store_manifest();
		self::register_install_from_store();
		self::register_run_scaffold();
		self::register_ai_generate();
		self::register_ai_draft_page();
		self::register_get_options();
		self::register_update_options();
		self::register_get_header_footer();
		self::register_save_header_footer();
		self::register_dismiss_onboarding();
	}

	// =========================================================================
	// Patterns
	// =========================================================================

	private static function register_list_patterns(): void
	{
		wp_register_ability(self::NS . '/list-patterns', [
			'label'       => __('List Patterns', 'awb-starter'),
			'description' => __('List all registered AWB block patterns with metadata, categories, source type, and usage counts.', 'awb-starter'),
			'category'    => self::NS,
			'input_schema' => null,
			'output_schema' => [
				'type'  => 'array',
				'items' => [
					'type'       => 'object',
					'properties' => [
						'name'        => ['type' => 'string', 'description' => 'Registered pattern name (e.g. awb/hero-cta).'],
						'title'       => ['type' => 'string'],
						'description' => ['type' => 'string'],
						'categories'  => ['type' => 'array', 'items' => ['type' => 'string']],
						'keywords'    => ['type' => 'array', 'items' => ['type' => 'string']],
						'source'      => ['type' => 'string', 'enum' => ['core', 'user']],
						'has_css'     => ['type' => 'boolean'],
						'has_js'      => ['type' => 'boolean'],
						'usage_count' => ['type' => 'integer'],
					],
				],
			],
			'execute_callback' => function () {
				$registry = WP_Block_Patterns_Registry::get_instance();
				$all      = $registry->get_all_registered();
				$patterns = [];
				foreach ($all as $pattern) {
					if (empty($pattern['name']) || 0 !== strpos($pattern['name'], 'awb/')) {
						continue;
					}
					$asset = AWB_Pattern_Loader::$pattern_assets[$pattern['name']] ?? [];
					$patterns[] = [
						'name'        => $pattern['name'],
						'title'       => $pattern['title'] ?? '',
						'description' => $pattern['description'] ?? '',
						'categories'  => $pattern['categories'] ?? [],
						'keywords'    => $pattern['keywords'] ?? [],
						'source'      => AWB_Pattern_Loader::$pattern_source[$pattern['name']] ?? 'core',
						'has_css'     => ! empty($asset['css']),
						'has_js'      => ! empty($asset['js']),
						'usage_count' => AWB_Pattern_Loader::get_usage_count($pattern['name']),
					];
				}
				return $patterns;
			},
			'permission_callback' => function () {
				return current_user_can('manage_options');
			},
			'meta' => [
				'public'      => true,
				'annotations' => [
					'readonly'    => true,
					'idempotent'  => true,
				],
			],
		]);
	}

	private static function register_get_pattern(): void
	{
		wp_register_ability(self::NS . '/get-pattern', [
			'label'       => __('Get Pattern', 'awb-starter'),
			'description' => __('Retrieve a single pattern\'s full content including PHP source, CSS, and JS.', 'awb-starter'),
			'category'    => self::NS,
			'input_schema' => [
				'type'       => 'object',
				'properties' => [
					'name' => [
						'type'        => 'string',
						'description' => 'Registered pattern name (e.g. awb/hero-cta).',
					],
				],
				'required' => ['name'],
			],
			'output_schema' => [
				'type'       => 'object',
				'properties' => [
					'name'        => ['type' => 'string'],
					'title'       => ['type' => 'string'],
					'slug'        => ['type' => 'string'],
					'categories'  => ['type' => 'array', 'items' => ['type' => 'string']],
					'keywords'    => ['type' => 'array', 'items' => ['type' => 'string']],
					'description' => ['type' => 'string'],
					'source'      => ['type' => 'string'],
					'content'     => ['type' => 'string', 'description' => 'PHP source file contents.'],
					'css'         => ['type' => 'string'],
					'js'          => ['type' => 'string'],
					'usage_count' => ['type' => 'integer'],
				],
			],
			'execute_callback' => function (array $input) {
				$name = sanitize_text_field($input['name']);
				if (empty($name) || 0 !== strpos($name, 'awb/')) {
					return new WP_Error('invalid_name', __('Invalid pattern name.', 'awb-starter'));
				}
				$files = AWB_Pattern_Loader::$pattern_files;
				if (empty($files[$name])) {
					return new WP_Error('not_found', __('Pattern not found.', 'awb-starter'));
				}
				$filepath = $files[$name];
				$source   = AWB_Pattern_Loader::$pattern_source[$name] ?? 'core';
				$meta     = get_file_data($filepath, [
					'title' => 'Title', 'slug' => 'Slug', 'categories' => 'Categories',
					'keywords' => 'Keywords', 'description' => 'Description',
				]);
				$content = is_readable($filepath)
					? file_get_contents($filepath) // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
					: '';
				$asset   = AWB_Pattern_Loader::$pattern_assets[$name] ?? [];
				$base    = ('user' === $source) ? AWB_USER_PATTERNS_PATH : AWB_PLUGIN_PATH;

				$css = '';
				if (! empty($asset['css'])) {
					$abs = $base . ltrim($asset['css'], '/');
					if (is_readable($abs)) {
						$css = file_get_contents($abs); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
					}
				}
				$js = '';
				if (! empty($asset['js'])) {
					$abs = $base . ltrim($asset['js'], '/');
					if (is_readable($abs)) {
						$js = file_get_contents($abs); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
					}
				}

				return [
					'name'        => $name,
					'title'       => sanitize_text_field($meta['title'] ?? ''),
					'slug'        => sanitize_title($meta['slug'] ?? ''),
					'categories'  => array_filter(array_map('trim', explode(',', $meta['categories'] ?? ''))),
					'keywords'    => array_filter(array_map('trim', explode(',', $meta['keywords'] ?? ''))),
					'description' => sanitize_text_field($meta['description'] ?? ''),
					'source'      => $source,
					'content'     => $content,
					'css'         => $css,
					'js'          => $js,
					'usage_count' => AWB_Pattern_Loader::get_usage_count($name),
				];
			},
			'permission_callback' => function () {
				return current_user_can('manage_options');
			},
			'meta' => [
				'public'      => true,
				'annotations' => [
					'readonly'    => true,
					'idempotent'  => true,
				],
			],
		]);
	}

	private static function register_create_pattern(): void
	{
		wp_register_ability(self::NS . '/create-pattern', [
			'label'       => __('Create Pattern', 'awb-starter'),
			'description' => __('Create a new user pattern with block markup content.', 'awb-starter'),
			'category'    => self::NS,
			'input_schema' => [
				'type'       => 'object',
				'properties' => [
					'slug'        => ['type' => 'string', 'description' => 'Pattern slug (will be sanitized).'],
					'content'     => ['type' => 'string', 'description' => 'Block markup content.'],
					'title'       => ['type' => 'string', 'description' => 'Human-readable title.'],
					'description' => ['type' => 'string'],
					'categories'  => ['type' => 'string', 'description' => 'Comma-separated category slugs.'],
					'keywords'    => ['type' => 'string', 'description' => 'Comma-separated keywords.'],
				],
				'required' => ['slug', 'content'],
			],
			'output_schema' => [
				'type'       => 'object',
				'properties' => [
					'registered_name' => ['type' => 'string'],
					'file'            => ['type' => 'string'],
				],
			],
			'execute_callback' => function (array $input) {
				$result = AWB_Pattern_Loader::create_user_pattern(
					sanitize_title($input['slug']),
					[
						'title'       => sanitize_text_field($input['title'] ?? $input['slug']),
						'description' => sanitize_text_field($input['description'] ?? ''),
						'categories'  => sanitize_text_field($input['categories'] ?? ''),
						'keywords'    => sanitize_text_field($input['keywords'] ?? ''),
					],
					$input['content'],
					false
				);
				return is_wp_error($result) ? $result : $result;
			},
			'permission_callback' => function () {
				return current_user_can('manage_options');
			},
			'meta' => [
				'public'      => true,
				'annotations' => ['readonly' => false, 'destructive' => false],
			],
		]);
	}

	private static function register_update_pattern(): void
	{
		wp_register_ability(self::NS . '/update-pattern', [
			'label'       => __('Update Pattern', 'awb-starter'),
			'description' => __('Update files of a user-created pattern. Only user patterns can be modified.', 'awb-starter'),
			'category'    => self::NS,
			'input_schema' => [
				'type'       => 'object',
				'properties' => [
					'name'    => ['type' => 'string', 'description' => 'Registered pattern name (e.g. awb/my-pattern).'],
					'content' => ['type' => 'string', 'description' => 'New PHP file content.'],
					'css'     => ['type' => 'string', 'description' => 'New CSS content.'],
					'js'      => ['type' => 'string', 'description' => 'New JS content.'],
				],
				'required' => ['name'],
			],
			'output_schema' => [
				'type'       => 'object',
				'properties' => [
					'message' => ['type' => 'string'],
				],
			],
			'execute_callback' => function (array $input) {
				$name = sanitize_text_field($input['name']);
				if (empty($name) || 0 !== strpos($name, 'awb/')) {
					return new WP_Error('invalid_name', __('Invalid pattern name.', 'awb-starter'));
				}
				$source = AWB_Pattern_Loader::$pattern_source[$name] ?? '';
				if ('user' !== $source) {
					return new WP_Error('not_user', __('Only user patterns can be modified.', 'awb-starter'));
				}
				$files = [];
				if (isset($input['content'])) {
					$files['php'] = $input['content'];
				}
				if (isset($input['css'])) {
					$files['css'] = $input['css'];
				}
				if (isset($input['js'])) {
					$files['js'] = $input['js'];
				}
				if (empty($files)) {
					return new WP_Error('no_content', __('No file content provided.', 'awb-starter'));
				}
				$result = AWB_Pattern_Loader::write_user_pattern_files($name, $files);
				if (is_wp_error($result)) {
					return $result;
				}
				return ['message' => __('Pattern files saved.', 'awb-starter')];
			},
			'permission_callback' => function () {
				return current_user_can('manage_options');
			},
			'meta' => [
				'public'      => true,
				'annotations' => ['readonly' => false, 'destructive' => false],
			],
		]);
	}

	private static function register_delete_pattern(): void
	{
		wp_register_ability(self::NS . '/delete-pattern', [
			'label'       => __('Delete Pattern', 'awb-starter'),
			'description' => __('Delete a user-created pattern and its associated CSS/JS assets. Cannot delete core (plugin-bundled) patterns.', 'awb-starter'),
			'category'    => self::NS,
			'input_schema' => [
				'type'       => 'object',
				'properties' => [
					'name' => ['type' => 'string', 'description' => 'Registered pattern name (e.g. awb/my-pattern).'],
				],
				'required' => ['name'],
			],
			'output_schema' => [
				'type'       => 'object',
				'properties' => [
					'message' => ['type' => 'string'],
				],
			],
			'execute_callback' => function (array $input) {
				$name = sanitize_text_field($input['name']);
				if (empty($name) || 0 !== strpos($name, 'awb/')) {
					return new WP_Error('invalid_name', __('Invalid pattern name.', 'awb-starter'));
				}
				$source = AWB_Pattern_Loader::$pattern_source[$name] ?? '';
				if ('user' !== $source) {
					return new WP_Error('not_user', __('Only user patterns can be deleted.', 'awb-starter'));
				}
				$php_path = AWB_Pattern_Loader::$pattern_files[$name] ?? '';
				if (! $php_path || ! AWB_Pattern_Loader::is_path_within($php_path, AWB_USER_PATTERNS_PATH)) {
					return new WP_Error('not_found', __('Pattern file not found.', 'awb-starter'));
				}
				global $wp_filesystem;
				$fs_ok = false;
				if (! empty($wp_filesystem) && is_object($wp_filesystem)) {
					$fs_ok = true;
				} else {
					require_once ABSPATH . 'wp-admin/includes/file.php';
					$fs_ok = WP_Filesystem();
				}
				$del = function ($path) use ($wp_filesystem, $fs_ok) {
					return $fs_ok
						? ($wp_filesystem->exists($path) && $wp_filesystem->delete($path))
						: (file_exists($path) && unlink($path));
				};
				$deleted = 0;
				if ($del($php_path)) {
					$deleted++;
				}
				$meta      = get_file_data($php_path, ['css' => 'CSS', 'js' => 'JS']);
				$base_path = trailingslashit(AWB_USER_PATTERNS_PATH);
				if (! empty($meta['css'])) {
					$p = $base_path . ltrim($meta['css'], '/');
					if (AWB_Pattern_Loader::is_path_within($p, AWB_USER_PATTERNS_PATH) && $del($p)) {
						$deleted++;
					}
				}
				if (! empty($meta['js'])) {
					$p = $base_path . ltrim($meta['js'], '/');
					if (AWB_Pattern_Loader::is_path_within($p, AWB_USER_PATTERNS_PATH) && $del($p)) {
						$deleted++;
					}
				}
				if ($deleted === 0) {
					return new WP_Error('delete_failed', __('Failed to delete pattern files.', 'awb-starter'));
				}
				return ['message' => __('Pattern deleted successfully.', 'awb-starter')];
			},
			'permission_callback' => function () {
				return current_user_can('manage_options');
			},
			'meta' => [
				'public'      => true,
				'annotations' => ['readonly' => false, 'destructive' => true],
			],
		]);
	}

	private static function register_duplicate_pattern(): void
	{
		wp_register_ability(self::NS . '/duplicate-pattern', [
			'label'       => __('Duplicate Pattern', 'awb-starter'),
			'description' => __('Clone an existing pattern into a new user pattern with a unique slug.', 'awb-starter'),
			'category'    => self::NS,
			'input_schema' => [
				'type'       => 'object',
				'properties' => [
					'name' => ['type' => 'string', 'description' => 'Source pattern name (e.g. awb/hero-cta).'],
				],
				'required' => ['name'],
			],
			'output_schema' => [
				'type'       => 'object',
				'properties' => [
					'new_registered_name' => ['type' => 'string'],
					'new_slug'            => ['type' => 'string'],
					'new_title'           => ['type' => 'string'],
					'message'             => ['type' => 'string'],
				],
			],
			'execute_callback' => function (array $input) {
				$name = sanitize_text_field($input['name']);
				if (empty($name) || 0 !== strpos($name, 'awb/')) {
					return new WP_Error('invalid_name', __('Invalid pattern name.', 'awb-starter'));
				}
				$result = AWB_Pattern_Duplicator::duplicate($name);
				if (is_wp_error($result)) {
					return $result;
				}
				return $result + ['message' => sprintf(__('Pattern duplicated as "%s".', 'awb-starter'), $result['new_title'])];
			},
			'permission_callback' => function () {
				return current_user_can('manage_options');
			},
			'meta' => [
				'public'      => true,
				'annotations' => ['readonly' => false, 'destructive' => false],
			],
		]);
	}

	private static function register_export_pattern(): void
	{
		wp_register_ability(self::NS . '/export-pattern', [
			'label'       => __('Export Pattern', 'awb-starter'),
			'description' => __('Export a pattern as a base64-encoded ZIP archive for download or transfer.', 'awb-starter'),
			'category'    => self::NS,
			'input_schema' => [
				'type'       => 'object',
				'properties' => [
					'name' => ['type' => 'string', 'description' => 'Pattern name to export (e.g. awb/hero-cta).'],
				],
				'required' => ['name'],
			],
			'output_schema' => [
				'type'       => 'object',
				'properties' => [
					'filename' => ['type' => 'string'],
					'size'     => ['type' => 'integer'],
					'base64'   => ['type' => 'string', 'description' => 'Base64-encoded ZIP content.'],
				],
			],
			'execute_callback' => function (array $input) {
				$name = sanitize_text_field($input['name']);
				if (empty($name) || 0 !== strpos($name, 'awb/')) {
					return new WP_Error('invalid_name', __('Invalid pattern name.', 'awb-starter'));
				}
				$zip_path = AWB_Pattern_Exporter::build($name);
				if (is_wp_error($zip_path)) {
					return $zip_path;
				}
				$slug = str_replace('awb/', '', $name);
				$raw = file_get_contents($zip_path); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
				@unlink($zip_path); // phpcs:ignore WordPress.PHP.NoSilencedErrors
				if (false === $raw) {
					return new WP_Error('read_failed', __('Failed to read ZIP archive.', 'awb-starter'));
				}
				return [
					'filename' => 'awb-pattern-' . $slug . '.zip',
					'size'     => strlen($raw),
					'base64'   => base64_encode($raw),
				];
			},
			'permission_callback' => function () {
				return current_user_can('manage_options');
			},
			'meta' => [
				'public'      => true,
				'annotations' => ['readonly' => true, 'idempotent' => true],
			],
		]);
	}

	private static function register_preview_pattern(): void
	{
		wp_register_ability(self::NS . '/preview-pattern', [
			'label'       => __('Preview Pattern', 'awb-starter'),
			'description' => __('Get rendered pattern content with associated CSS URLs and design tokens for browser preview.', 'awb-starter'),
			'category'    => self::NS,
			'input_schema' => [
				'type'       => 'object',
				'properties' => [
					'name' => ['type' => 'string', 'description' => 'Pattern name (e.g. awb/hero-cta).'],
				],
				'required' => ['name'],
			],
			'output_schema' => [
				'type'       => 'object',
				'properties' => [
					'title'   => ['type' => 'string'],
					'content' => ['type' => 'string'],
					'css'     => ['type' => 'array', 'items' => ['type' => 'string']],
					'tokens'  => ['type' => 'string'],
				],
			],
			'execute_callback' => function (array $input) {
				$name = sanitize_text_field($input['name']);
				if (empty($name) || 0 !== strpos($name, 'awb/')) {
					return new WP_Error('invalid_name', __('Invalid pattern name.', 'awb-starter'));
				}
				$pattern = WP_Block_Patterns_Registry::get_instance()->get_registered($name);
				if (! is_array($pattern) || empty($pattern['content'])) {
					return new WP_Error('not_found', __('Pattern content not found.', 'awb-starter'));
				}
				$assets   = AWB_Pattern_Loader::$pattern_assets[$name] ?? [];
				$source   = $assets['source'] ?? 'core';
				$base_url = ('user' === $source) ? AWB_USER_PATTERNS_URL : AWB_PLUGIN_URL;
				$base_path = ('user' === $source) ? AWB_USER_PATTERNS_PATH : AWB_PLUGIN_PATH;
				$css_urls = [];
				if (file_exists(AWB_PLUGIN_PATH . 'assets/css/frontend.css')) {
					$css_urls[] = AWB_PLUGIN_URL . 'assets/css/frontend.css';
				}
				if (! empty($assets['css'])) {
					$abs = $base_path . ltrim($assets['css'], '/');
					if (file_exists($abs)) {
						$css_urls[] = $base_url . ltrim($assets['css'], '/');
					}
				}
				return [
					'title'   => $pattern['title'] ?? '',
					'content' => $pattern['content'],
					'css'     => $css_urls,
					'tokens'  => AWB_Asset_Loader::generate_design_tokens_css(),
				];
			},
			'permission_callback' => function () {
				return current_user_can('manage_options');
			},
			'meta' => [
				'public'      => true,
				'annotations' => ['readonly' => true, 'idempotent' => true],
			],
		]);
	}

	private static function register_import_pattern(): void
	{
		wp_register_ability(self::NS . '/import-pattern', [
			'label'       => __('Import Pattern', 'awb-starter'),
			'description' => __('Import a pattern from a base64-encoded ZIP archive. Provide the ZIP as base64 and optionally set force=true to overwrite existing files.', 'awb-starter'),
			'category'    => self::NS,
			'input_schema' => [
				'type'       => 'object',
				'properties' => [
					'base64_zip' => ['type' => 'string', 'description' => 'Base64-encoded ZIP archive of the pattern.'],
					'force'      => ['type' => 'boolean', 'description' => 'Overwrite existing files.', 'default' => false],
				],
				'required' => ['base64_zip'],
			],
			'output_schema' => [
				'type'       => 'object',
				'properties' => [
					'title'   => ['type' => 'string'],
					'slug'    => ['type' => 'string'],
					'message' => ['type' => 'string'],
				],
			],
			'execute_callback' => function (array $input) {
				$zip_data = base64_decode($input['base64_zip'], true);
				if (false === $zip_data || empty($zip_data)) {
					return new WP_Error('invalid_base64', __('Invalid base64 data.', 'awb-starter'));
				}
				$tmp = wp_tempnam('awb-import.zip');
				if (! $tmp) {
					return new WP_Error('tempnam_failed', __('Could not create temporary file.', 'awb-starter'));
				}
				file_put_contents($tmp, $zip_data); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
				$force  = ! empty($input['force']);
				$result = AWB_Pattern_Importer::install_from_zip($tmp, $force);
				@unlink($tmp); // phpcs:ignore WordPress.PHP.NoSilencedErrors
				if ($result['success']) {
					return $result['data'];
				}
				return new WP_Error('import_failed', $result['error'] ?? __('Import failed.', 'awb-starter'));
			},
			'permission_callback' => function () {
				return current_user_can('manage_options');
			},
			'meta' => [
				'public'      => true,
				'annotations' => ['readonly' => false, 'destructive' => false],
			],
		]);
	}

	// =========================================================================
	// Store
	// =========================================================================

	private static function register_fetch_store_manifest(): void
	{
		wp_register_ability(self::NS . '/fetch-store-manifest', [
			'label'       => __('Fetch Store Manifest', 'awb-starter'),
			'description' => __('Fetch the remote pattern store manifest and return available patterns for installation.', 'awb-starter'),
			'category'    => self::NS,
			'input_schema' => null,
			'output_schema' => [
				'type'       => 'object',
				'properties' => [
					'patterns' => [
						'type'  => 'array',
						'items' => [
							'type'       => 'object',
							'properties' => [
								'title'        => ['type' => 'string'],
								'description'  => ['type' => 'string'],
								'version'      => ['type' => 'string'],
								'author'       => ['type' => 'string'],
								'thumbnail'    => ['type' => 'string'],
								'download_url' => ['type' => 'string'],
							],
						],
					],
				],
			],
			'execute_callback' => function () {
				$patterns = AWB_Store::fetch_manifest();
				if (is_wp_error($patterns)) {
					return $patterns;
				}
				return ['patterns' => $patterns];
			},
			'permission_callback' => function () {
				return current_user_can('manage_options');
			},
			'meta' => [
				'public'      => true,
				'annotations' => ['readonly' => true, 'idempotent' => true],
			],
		]);
	}

	private static function register_install_from_store(): void
	{
		wp_register_ability(self::NS . '/install-from-store', [
			'label'       => __('Install from Store', 'awb-starter'),
			'description' => __('Install a pattern package from an allowed store host URL.', 'awb-starter'),
			'category'    => self::NS,
			'input_schema' => [
				'type'       => 'object',
				'properties' => [
					'url' => ['type' => 'string', 'description' => 'Download URL from an allowed host.'],
				],
				'required' => ['url'],
			],
			'output_schema' => [
				'type'       => 'object',
				'properties' => [
					'title'   => ['type' => 'string'],
					'slug'    => ['type' => 'string'],
					'message' => ['type' => 'string'],
				],
			],
			'execute_callback' => function (array $input) {
				$url = esc_url_raw($input['url']);
				if (empty($url)) {
					return new WP_Error('no_url', __('No URL provided.', 'awb-starter'));
				}
				return AWB_Store::install($url);
			},
			'permission_callback' => function () {
				return current_user_can('manage_options');
			},
			'meta' => [
				'public'      => true,
				'annotations' => ['readonly' => false, 'destructive' => false],
			],
		]);
	}

	// =========================================================================
	// Scaffold
	// =========================================================================

	private static function register_run_scaffold(): void
	{
		wp_register_ability(self::NS . '/run-scaffold', [
			'label'       => __('Run Scaffold', 'awb-starter'),
			'description' => __('Create standard pages, navigation menu, and optionally set a static front page. Deletes default sample content.', 'awb-starter'),
			'category'    => self::NS,
			'input_schema' => [
				'type'       => 'object',
				'properties' => [
					'type' => [
						'type'        => 'string',
						'description' => 'Scaffold type.',
						'enum'        => ['business', 'portfolio', 'landing'],
					],
				],
				'required' => ['type'],
			],
			'output_schema' => [
				'type'       => 'object',
				'properties' => [
					'log' => [
						'type'  => 'array',
						'items' => ['type' => 'string'],
					],
				],
			],
			'execute_callback' => function (array $input) {
				$type = sanitize_key($input['type']);
				if (! array_key_exists($type, AWB_Scaffold::definitions())) {
					return new WP_Error('invalid_type', __('Unknown scaffold type.', 'awb-starter'));
				}
				return ['log' => AWB_Scaffold::run($type)];
			},
			'permission_callback' => function () {
				return current_user_can('manage_options');
			},
			'meta' => [
				'public'      => true,
				'annotations' => ['readonly' => false, 'destructive' => true],
			],
		]);
	}

	// =========================================================================
	// AI
	// =========================================================================

	private static function register_ai_generate(): void
	{
		wp_register_ability(self::NS . '/ai-generate', [
			'label'       => __('AI Generate', 'awb-starter'),
			'description' => __('Generate Gutenberg block markup, HTML, or text copy using the configured AI provider. Returns raw content.', 'awb-starter'),
			'category'    => self::NS,
			'input_schema' => [
				'type'       => 'object',
				'properties' => [
					'prompt'   => ['type' => 'string', 'description' => 'Description of the content to generate.'],
					'mode'     => [
						'type'    => 'string',
						'enum'    => ['blocks', 'html', 'copy'],
						'default' => 'blocks',
						'description' => 'Output format: blocks (Gutenberg markup), html (semantic HTML), or copy (plain text).',
					],
					'tone'     => [
						'type'    => 'string',
						'enum'    => ['professional', 'friendly', 'bold', 'minimal'],
						'description' => 'Tone of voice for generated text.',
					],
					'template' => [
						'type'        => 'string',
						'description' => 'Block template filename (without .html) to use as structural base.',
					],
				],
				'required' => ['prompt'],
			],
			'output_schema' => [
				'type'       => 'object',
				'properties' => [
					'blocks' => ['type' => 'string', 'description' => 'Generated content.'],
				],
			],
			'execute_callback' => function (array $input) {
				$prompt = sanitize_textarea_field($input['prompt']);
				if (empty($prompt)) {
					return new WP_Error('no_prompt', __('No prompt provided.', 'awb-starter'));
				}
				$options = [
					'mode'     => sanitize_key($input['mode'] ?? 'blocks'),
					'tone'     => sanitize_key($input['tone'] ?? ''),
					'template' => sanitize_title($input['template'] ?? ''),
				];
				$result = AWB_AI_Generator::generate($prompt, $options);
				if (is_wp_error($result)) {
					return $result;
				}
				return ['blocks' => $result];
			},
			'permission_callback' => function () {
				return current_user_can('edit_posts');
			},
			'meta' => [
				'public'      => true,
				'annotations' => ['readonly' => true, 'idempotent' => false],
			],
		]);
	}

	private static function register_ai_draft_page(): void
	{
		wp_register_ability(self::NS . '/ai-draft-page', [
			'label'       => __('AI Draft Page', 'awb-starter'),
			'description' => __('Generate content with AI and create a new draft page containing the result.', 'awb-starter'),
			'category'    => self::NS,
			'input_schema' => [
				'type'       => 'object',
				'properties' => [
					'prompt'   => ['type' => 'string', 'description' => 'Description of the page content to generate.'],
					'mode'     => [
						'type'    => 'string',
						'enum'    => ['blocks', 'html', 'copy'],
						'default' => 'blocks',
					],
					'tone'     => [
						'type'    => 'string',
						'enum'    => ['professional', 'friendly', 'bold', 'minimal'],
					],
					'template' => ['type' => 'string'],
				],
				'required' => ['prompt'],
			],
			'output_schema' => [
				'type'       => 'object',
				'properties' => [
					'edit_url' => ['type' => 'string'],
					'post_id'  => ['type' => 'integer'],
				],
			],
			'execute_callback' => function (array $input) {
				$prompt = sanitize_textarea_field($input['prompt']);
				if (empty($prompt)) {
					return new WP_Error('no_prompt', __('No prompt provided.', 'awb-starter'));
				}
				$options = [
					'mode'     => sanitize_key($input['mode'] ?? 'blocks'),
					'tone'     => sanitize_key($input['tone'] ?? ''),
					'template' => sanitize_title($input['template'] ?? ''),
				];
				return AWB_AI_Generator::create_draft_page($prompt, $options);
			},
			'permission_callback' => function () {
				return current_user_can('edit_pages');
			},
			'meta' => [
				'public'      => true,
				'annotations' => ['readonly' => false, 'destructive' => false],
			],
		]);
	}

	// =========================================================================
	// Options
	// =========================================================================

	private static function register_get_options(): void
	{
		wp_register_ability(self::NS . '/get-options', [
			'label'       => __('Get Options', 'awb-starter'),
			'description' => __('Retrieve current plugin configuration: design tokens, AI provider settings, header/footer config, and store URL.', 'awb-starter'),
			'category'    => self::NS,
			'input_schema' => null,
			'output_schema' => [
				'type'       => 'object',
				'properties' => [
					'awb_token_color_primary'   => ['type' => 'string'],
					'awb_token_color_secondary' => ['type' => 'string'],
					'awb_token_color_accent'    => ['type' => 'string'],
					'awb_token_color_text'      => ['type' => 'string'],
					'awb_token_color_bg'        => ['type' => 'string'],
					'awb_token_font_heading'    => ['type' => 'string'],
					'awb_token_font_body'       => ['type' => 'string'],
					'awb_token_font_mono'       => ['type' => 'string'],
					'awb_ai_provider'           => ['type' => 'string'],
					'awb_header_type'           => ['type' => 'string'],
					'awb_footer_type'           => ['type' => 'string'],
					'awb_store_manifest_url'    => ['type' => 'string'],
				],
			],
			'execute_callback' => function () {
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
				return $options;
			},
			'permission_callback' => function () {
				return current_user_can('manage_options');
			},
			'meta' => [
				'public'      => true,
				'annotations' => ['readonly' => true, 'idempotent' => true],
			],
		]);
	}

	private static function register_update_options(): void
	{
		wp_register_ability(self::NS . '/update-options', [
			'label'       => __('Update Options', 'awb-starter'),
			'description' => __('Update whitelisted plugin options: design tokens, AI keys, custom CSS/JS, store URL. Only known keys are accepted.', 'awb-starter'),
			'category'    => self::NS,
			'input_schema' => [
				'type'       => 'object',
				'properties' => [
					'awb_token_color_primary'   => ['type' => 'string'],
					'awb_token_color_secondary' => ['type' => 'string'],
					'awb_token_color_accent'    => ['type' => 'string'],
					'awb_token_color_text'      => ['type' => 'string'],
					'awb_token_color_bg'        => ['type' => 'string'],
					'awb_token_font_heading'    => ['type' => 'string'],
					'awb_token_font_body'       => ['type' => 'string'],
					'awb_token_font_mono'       => ['type' => 'string'],
					'awb_ai_provider'           => ['type' => 'string'],
					'awb_ai_anthropic_key'      => ['type' => 'string'],
					'awb_ai_openai_key'         => ['type' => 'string'],
					'awb_ai_qwen_key'           => ['type' => 'string'],
					'awb_ai_deepseek_key'       => ['type' => 'string'],
					'awb_ai_groq_key'           => ['type' => 'string'],
					'awb_custom_css'            => ['type' => 'string'],
					'awb_custom_js'             => ['type' => 'string'],
					'awb_store_manifest_url'    => ['type' => 'string'],
				],
				'required' => [],
			],
			'output_schema' => [
				'type'       => 'object',
				'properties' => [
					'saved' => ['type' => 'integer'],
				],
			],
			'execute_callback' => function (array $input) {
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
				foreach ($input as $key => $value) {
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
				return ['saved' => $saved];
			},
			'permission_callback' => function () {
				return current_user_can('manage_options');
			},
			'meta' => [
				'public'      => true,
				'annotations' => ['readonly' => false, 'destructive' => false, 'idempotent' => true],
			],
		]);
	}

	// =========================================================================
	// Header / Footer
	// =========================================================================

	private static function register_get_header_footer(): void
	{
		wp_register_ability(self::NS . '/get-header-footer', [
			'label'       => __('Get Header/Footer', 'awb-starter'),
			'description' => __('Retrieve the current header and footer pattern/block configuration.', 'awb-starter'),
			'category'    => self::NS,
			'input_schema' => null,
			'output_schema' => [
				'type'       => 'object',
				'properties' => [
					'header_type'  => ['type' => 'string'],
					'header_value' => ['type' => 'string'],
					'footer_type'  => ['type' => 'string'],
					'footer_value' => ['type' => 'string'],
				],
			],
			'execute_callback' => function () {
				return AWB_Header_Switcher::get_settings();
			},
			'permission_callback' => function () {
				return current_user_can('manage_options');
			},
			'meta' => [
				'public'      => true,
				'annotations' => ['readonly' => true, 'idempotent' => true],
			],
		]);
	}

	private static function register_save_header_footer(): void
	{
		wp_register_ability(self::NS . '/save-header-footer', [
			'label'       => __('Save Header/Footer', 'awb-starter'),
			'description' => __('Set the site-wide header and footer to use a block pattern, reusable block, or none.', 'awb-starter'),
			'category'    => self::NS,
			'input_schema' => [
				'type'       => 'object',
				'properties' => [
					'header_type'  => ['type' => 'string', 'enum' => ['none', 'pattern', 'block']],
					'header_value' => ['type' => 'string', 'description' => 'Pattern name (e.g. awb/header-dark) or block post ID.'],
					'footer_type'  => ['type' => 'string', 'enum' => ['none', 'pattern', 'block']],
					'footer_value' => ['type' => 'string', 'description' => 'Pattern name (e.g. awb/footer-dark) or block post ID.'],
				],
			],
			'output_schema' => [
				'type'       => 'object',
				'properties' => [
					'message' => ['type' => 'string'],
				],
			],
			'execute_callback' => function (array $input) {
				$result = AWB_Header_Switcher::save_settings($input);
				if (is_wp_error($result)) {
					return $result;
				}
				return ['message' => __('Settings saved.', 'awb-starter')];
			},
			'permission_callback' => function () {
				return current_user_can('manage_options');
			},
			'meta' => [
				'public'      => true,
				'annotations' => ['readonly' => false, 'destructive' => false],
			],
		]);
	}

	// =========================================================================
	// Onboarding
	// =========================================================================

	private static function register_dismiss_onboarding(): void
	{
		wp_register_ability(self::NS . '/dismiss-onboarding', [
			'label'       => __('Dismiss Onboarding', 'awb-starter'),
			'description' => __('Dismiss the first-run onboarding checklist so it no longer appears on admin pages.', 'awb-starter'),
			'category'    => self::NS,
			'input_schema' => null,
			'output_schema' => [
				'type'       => 'object',
				'properties' => [
					'message' => ['type' => 'string'],
				],
			],
			'execute_callback' => function () {
				AWB_Onboarding::dismiss();
				return ['message' => __('Onboarding dismissed.', 'awb-starter')];
			},
			'permission_callback' => function () {
				return current_user_can('manage_options');
			},
			'meta' => [
				'public'      => true,
				'annotations' => ['readonly' => false, 'destructive' => false],
			],
		]);
	}
}
