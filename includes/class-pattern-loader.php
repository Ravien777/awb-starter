<?php

/**
 * Block pattern loader and asset registry.
 *
 * Registers all block patterns from PHP files in patterns/ and HTML files in
 * block-templates/. Builds static maps consumed by other classes:
 *
 *   $pattern_assets — slug → ['css' => rel_path, 'js' => rel_path, 'source' => 'core'|'user']
 *                     Used by AWB_Asset_Loader to enqueue per-pattern assets.
 *
 *   $pattern_files  — slug → absolute_file_path
 *                     Used by AWB_Pattern_Exporter to locate the source file.
 *
 * @package AWBStarter
 */
if (! defined('ABSPATH')) {
    exit;
}

class AWB_Pattern_Loader
{
    /**
     * Per-pattern asset paths, keyed by registered pattern name.
     * Stores relative paths and source for dynamic resolution.
     *
     * @var array<string, array{css: string, js: string, source: string}>
     */
    public static array $pattern_assets = [];

    /**
     * Absolute filesystem path for every registered AWB pattern file.
     *
     * @var array<string, string>
     */
    public static array $pattern_files = [];

    /**
     * Source indicator for each pattern: 'core' (plugin) or 'user' (uploads).
     *
     * @var array<string, string>
     */
    public static array $pattern_source = [];

    public function __construct()
    {
        add_action('init', [$this, 'register_patterns']);
    }

    /**
     * Count published posts/pages whose content references a pattern.
     *
     * Matches either the registered name (awb/slug) or the short slug,
     * mirroring how AWB_Asset_Loader detects in-use patterns.
     *
     * @param string $registered_name e.g. awb/hero-cta.
     */
public static function get_usage_count(string $registered_name): int
     {
         global $wpdb;
         $short = str_replace('awb/', '', $registered_name);
         $like  = '%' . $wpdb->esc_like($short) . '%';
         return (int) $wpdb->get_var(
             $wpdb->prepare(
                 "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = 'publish' AND post_type IN ('page','post') AND post_content LIKE %s",
                 $like
             )
         );
     }

    /**
     * Check if a path is within a given root directory.
     *
     * @param string $path The path to check.
     * @param string $root The root directory.
     * @return bool True if $path is within $root.
     */
    public static function is_path_within(string $path, string $root): bool
    {
        $norm_path = wp_normalize_path($path);
        $norm_root = wp_normalize_path(trailingslashit($root));
        return str_starts_with($norm_path, $norm_root);
    }

    /**
     * Initialize WP_Filesystem for direct file operations.
     *
     * @return bool True if filesystem is available.
     */
    private static function init_filesystem(): bool
    {
        global $wp_filesystem;
        if (! empty($wp_filesystem) && is_object($wp_filesystem)) {
            return true;
        }
        require_once ABSPATH . 'wp-admin/includes/file.php';
        return WP_Filesystem();
    }

    /**
     * Write PHP, CSS, and JS files for a user pattern.
     *
     * @param string $registered_name The registered pattern name (e.g. awb/my-pattern).
     * @param array  $files           Associative array with keys 'php', 'css', 'js' containing content.
     * @return true|WP_Error True on success, WP_Error on failure.
     */
    public static function write_user_pattern_files(string $registered_name, array $files): true|\WP_Error
    {
        if (strpos($registered_name, 'awb/') !== 0) {
            return new \WP_Error('invalid_name', __('Invalid pattern name.', 'awb-starter'));
        }
        $source = self::$pattern_source[$registered_name] ?? 'core';
        if ($source !== 'user') {
            return new \WP_Error('not_user_pattern', __('Only user patterns can be modified.', 'awb-starter'));
        }
        $php_path = self::$pattern_files[$registered_name] ?? '';
        if (! $php_path || ! self::is_path_within($php_path, AWB_USER_PATTERNS_PATH)) {
            return new \WP_Error('file_not_found', __('Pattern file not found or path invalid.', 'awb-starter'));
        }

        // Read header to resolve asset paths safely.
        $meta = get_file_data($php_path, ['css' => 'CSS', 'js' => 'JS']);
        $base_path = trailingslashit(AWB_USER_PATTERNS_PATH);

        $fs_ok = self::init_filesystem();
        $write_file = function ($path, $content) use ($fs_ok) {
            if (empty($path) || empty($content)) {
                return false;
            }
            global $wp_filesystem;
            $success = $fs_ok
                ? $wp_filesystem->put_contents($path, $content, FS_CHMOD_FILE)
                : (file_put_contents($path, $content) !== false);
            if ($success && function_exists('opcache_invalidate')) {
                opcache_invalidate($path, true);
            }
            return $success;
        };

        $saved = 0;
        // Save PHP
        if (isset($files['php']) && self::is_path_within($php_path, AWB_USER_PATTERNS_PATH)) {
            if ($write_file($php_path, $files['php'])) {
                $saved++;
            }
        }
        // Save CSS
        if (isset($files['css']) && ! empty($meta['css'])) {
            $css_path = $base_path . ltrim($meta['css'], '/');
            if (self::is_path_within($css_path, AWB_USER_PATTERNS_PATH)) {
                if ($write_file($css_path, $files['css'])) {
                    $saved++;
                }
            }
        }
        // Save JS
        if (isset($files['js']) && ! empty($meta['js'])) {
            $js_path = $base_path . ltrim($meta['js'], '/');
            if (self::is_path_within($js_path, AWB_USER_PATTERNS_PATH)) {
                if ($write_file($js_path, $files['js'])) {
                    $saved++;
                }
            }
        }
        if ($saved === 0) {
            return new \WP_Error('no_files_saved', __('No files were saved.', 'awb-starter'));
        }
        return true;
    }

    /**
     * Create a new user pattern file.
     *
     * @param string $slug        The pattern slug (will be sanitized).
     * @param array  $meta        Optional metadata: title, description, categories, keywords, css, js.
     * @param string $content     The block markup content.
     * @param bool   $overwrite   Whether to overwrite an existing file.
     * @return array|WP_Error Result with registered_name and file path, or WP_Error.
     */
    public static function create_user_pattern(string $slug, array $meta = [], string $content = '', bool $overwrite = false): array|\WP_Error
    {
        $slug = sanitize_title($slug);
        if (empty($slug)) {
            return new \WP_Error('invalid_slug', __('Invalid slug.', 'awb-starter'));
        }
        $registered_name = 'awb/' . $slug;
        if (! empty(self::$pattern_files[$registered_name]) && ! $overwrite) {
            return new \WP_Error('exists', __('A pattern with this slug already exists.', 'awb-starter'));
        }

        $title       = sanitize_text_field($meta['title'] ?? $slug);
        $description = sanitize_text_field($meta['description'] ?? '');
        $categories  = ! empty($meta['categories'])
            ? array_map('sanitize_text_field', array_map('trim', explode(',', (string) $meta['categories'])))
            : ['awb-sections'];
        $keywords    = ! empty($meta['keywords'])
            ? array_map('sanitize_text_field', array_map('trim', explode(',', (string) $meta['keywords'])))
            : [];
        $css_rel     = ! empty($meta['css']) ? ltrim(sanitize_text_field($meta['css']), '/') : '';
        $js_rel      = ! empty($meta['js']) ? ltrim(sanitize_text_field($meta['js']), '/') : '';

        // Ensure user patterns directory exists.
        $user_dir = trailingslashit(AWB_USER_PATTERNS_PATH) . 'patterns/';
        if (! is_dir($user_dir)) {
            wp_mkdir_p($user_dir);
        }
        $php_path = trailingslashit($user_dir) . $slug . '.php';
        if (! $overwrite && file_exists($php_path)) {
            return new \WP_Error('exists', __('A pattern with this slug already exists.', 'awb-starter'));
        }

        // Build file content with header.
        $header = "<?php\n";
        $header .= "/**\n";
        $header .= " * Title: " . str_replace('*/', '* /', $title) . "\n";
        $header .= " * Slug: " . $slug . "\n";
        $header .= " * Categories: " . implode(', ', $categories) . "\n";
        if ($keywords) {
            $header .= " * Keywords: " . implode(', ', $keywords) . "\n";
        }
        $header .= " * Description: " . str_replace('*/', '* /', $description) . "\n";
        if ($css_rel) {
            $header .= " * CSS: " . $css_rel . "\n";
        }
        if ($js_rel) {
            $header .= " * JS: " . $js_rel . "\n";
        }
        $header .= " */\n";
        $header .= "?>\n";
        $file_content = $header . $content;

        $fs_ok = self::init_filesystem();
        $success = $fs_ok
            ? (bool) $GLOBALS['wp_filesystem']->put_contents($php_path, $file_content, FS_CHMOD_FILE)
            : (file_put_contents($php_path, $file_content) !== false);
        if (! $success) {
            return new \WP_Error('write_failed', __('Failed to write pattern file.', 'awb-starter'));
        }
        if (function_exists('opcache_invalidate')) {
            opcache_invalidate($php_path, true);
        }

        // Register immediately so it's available in this request.
        self::$pattern_files[$registered_name]  = $php_path;
        self::$pattern_source[$registered_name] = 'user';
        if ($css_rel || $js_rel) {
            self::$pattern_assets[$registered_name] = [
                'css'    => $css_rel,
                'js'     => $js_rel,
                'source' => 'user',
            ];
        }

        return [
            'registered_name' => $registered_name,
            'file'            => $php_path,
        ];
    }

    public function register_patterns(): void
    {
        if (! function_exists('register_block_pattern')) {
            return;
        }
        $this->register_patterns_from_dir(AWB_PATTERNS_PATH, 'php', 'core');
        $this->register_patterns_from_dir(AWB_USER_PATTERNS_PATH . 'patterns/', 'php', 'user');
        $this->register_patterns_from_dir(AWB_PLUGIN_PATH . 'block-templates/', 'html', 'core');
    }

    private function register_patterns_from_dir(string $dir, string $extension, string $source = 'core'): void
    {
        if (! is_dir($dir)) {
            return;
        }
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->getExtension() !== $extension) {
                continue;
            }
            $filepath = $file->getPathname();
            if ('php' === $extension) {
                $this->register_php_pattern($filepath, $source);
            } else {
                $this->register_html_pattern($filepath);
            }
        }
    }

    private function register_php_pattern(string $filepath, string $source = 'core'): void
    {
        $meta = get_file_data($filepath, [
            'title'       => 'Title',
            'slug'        => 'Slug',
            'categories'  => 'Categories',
            'keywords'    => 'Keywords',
            'description' => 'Description',
            'css'         => 'CSS',
            'js'          => 'JS',
        ]);
        if (empty($meta['title']) || empty($meta['slug'])) {
            return;
        }
        $registered_name = 'awb/' . sanitize_title($meta['slug']);
        $content         = $this->get_php_pattern_content($filepath);
        if (empty($content)) {
            return;
        }
        self::$pattern_files[$registered_name]  = $filepath;
        self::$pattern_source[$registered_name] = $source;
        if (! empty($meta['css']) || ! empty($meta['js'])) {
            self::$pattern_assets[$registered_name] = [
                'css'    => ! empty($meta['css']) ? ltrim($meta['css'], '/') : '',
                'js'     => ! empty($meta['js'])  ? ltrim($meta['js'], '/')  : '',
                'source' => $source, // 'core' or 'user'
            ];
        }
        $categories = ! empty($meta['categories'])
            ? array_map('trim', explode(',', $meta['categories']))
            : ['awb-sections'];
        register_block_pattern($registered_name, [
            'title'       => $meta['title'],
            'description' => $meta['description'] ?? '',
            'categories'  => $categories,
            'keywords'    => ! empty($meta['keywords'])
                ? array_map('trim', explode(',', $meta['keywords']))
                : [],
            'content'     => $content,
        ]);
    }

    private function register_html_pattern(string $filepath): void
    {
        $meta = get_file_data($filepath, [
            'title'       => 'Title',
            'description' => 'Description',
            'categories'  => 'Categories',
            'keywords'    => 'Keywords',
        ]);
        $filename        = pathinfo($filepath, PATHINFO_FILENAME);
        $registered_name = 'awb/' . sanitize_title($filename);
        $title           = ! empty($meta['title']) ? $meta['title'] : $this->format_title($filename);
        $description     = ! empty($meta['description']) ? $meta['description'] : __('AWB Block Template', 'awb-starter');
        $content         = file_get_contents($filepath); // phpcs:ignore WordPress.WP.AlternativeFunctions
        if (empty($content)) {
            return;
        }
        $categories = ! empty($meta['categories'])
            ? array_map('trim', explode(',', $meta['categories']))
            : ['awb-pages'];
        register_block_pattern($registered_name, [
            'title'       => $title,
            'description' => $description,
            'categories'  => $categories,
            'keywords'    => ! empty($meta['keywords'])
                ? array_map('trim', explode(',', $meta['keywords']))
                : [],
            'content'     => $content,
        ]);
    }

    private function get_php_pattern_content(string $filepath): string
    {
        ob_start();
        include $filepath;
        return (string) ob_get_clean();
    }

    private function format_title(string $filename): string
    {
        return ucwords(str_replace(['-', '_'], ' ', $filename));
    }
}
