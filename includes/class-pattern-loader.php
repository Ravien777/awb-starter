<?php

/**
 * Block pattern loader and asset registry.
 *
 * @package AWBStarter
 */

if (! defined('ABSPATH')) {
    exit;
}

class AWB_Pattern_Loader
{
    public static array $pattern_assets = [];

    public function __construct()
    {
        add_action('init', [$this, 'register_patterns']);
    }

    public function register_patterns(): void
    {
        if (! function_exists('register_block_pattern')) {
            return;
        }

        // Register patterns from PHP files in patterns/ directory
        $this->register_patterns_from_dir(AWB_PLUGIN_PATH . 'patterns', 'php');

        // Register patterns from HTML files in block-templates/ directory
        $this->register_patterns_from_dir(AWB_PLUGIN_PATH . 'block-templates', 'html');
    }

    private function register_patterns_from_dir(string $dir, string $extension): void
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

            if ($extension === 'php') {
                $meta = get_file_data($file->getPathname(), [
                    'title'       => 'Title',
                    'slug'        => 'Slug',
                    'categories'  => 'Categories',
                    'keywords'    => 'Keywords',
                    'description' => 'Description',
                    'css'         => 'CSS',
                    'js'          => 'JS',
                ]);

                if (empty($meta['title']) || empty($meta['slug'])) {
                    continue;
                }

                $slug = 'awb/' . sanitize_title($meta['slug']);
                $content = $this->get_pattern_content($file->getPathname(), $extension);
            } else {
                // HTML files from block-templates
                $meta = get_file_data($file->getPathname(), [
                    'title'       => 'Title',
                    'description' => 'Description',
                    'categories'  => 'Categories',
                    'keywords'    => 'Keywords',
                ]);

                $filename = pathinfo($file->getFilename(), PATHINFO_FILENAME);
                $slug = 'awb/' . sanitize_title($filename);
                $title = ! empty($meta['title']) ? $meta['title'] : $this->format_title($filename);
                $description = ! empty($meta['description']) ? $meta['description'] : __('AWB Block Template', 'awb-starter');
                $content = $this->get_pattern_content($file->getPathname(), $extension);
            }

            if (empty($content)) {
                continue;
            }

            // Register per-pattern assets for PHP files only
            if ($extension === 'php' && (! empty($meta['css']) || ! empty($meta['js']))) {
                self::$pattern_assets[$slug] = [
                    'css' => $meta['css'] ?? '',
                    'js'  => $meta['js']  ?? '',
                ];
            }

            $categories = ! empty($meta['categories'])
                ? array_map('trim', explode(',', $meta['categories']))
                : ($extension === 'html' ? ['awb-pages'] : ['awb-sections']);

            register_block_pattern($slug, [
                'title'       => $extension === 'php' ? $meta['title'] : $title,
                'description' => $extension === 'php' ? $meta['description'] ?? '' : $description,
                'categories'  => $categories,
                'keywords'    => ! empty($meta['keywords'])
                    ? array_map('trim', explode(',', $meta['keywords']))
                    : [],
                'content'     => $content,
            ]);
        }
    }

    private function get_pattern_content(string $file_path, string $extension): string
    {
        if ($extension === 'php') {
            ob_start();
            include $file_path;
            return ob_get_clean();
        } else {
            return file_get_contents($file_path);
        }
    }
}
