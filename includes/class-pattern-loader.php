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
