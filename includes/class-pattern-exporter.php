<?php

/**
 * Class AWB_Pattern_Exporter
 *
 * @package AWB_Starter
 * @since   2.3.0
 */
if (! defined('ABSPATH')) {
    exit;
}

class AWB_Pattern_Exporter
{
    /**
     * Build a ZIP archive for a pattern and return the temporary file path.
     *
     * @param string $registered_name The registered pattern name.
     * @return string|WP_Error Path to the ZIP on success, WP_Error on failure.
     */
    public static function build(string $registered_name): string|\WP_Error
    {
        if (! class_exists(\ZipArchive::class)) {
            return new \WP_Error('zip_unavailable', __('Export failed: ZipArchive is not available on this server. Please ask your host to enable the PHP zip extension.', 'awb-starter'));
        }
        $file_res = self::resolve_pattern_file($registered_name);
        if (is_wp_error($file_res)) {
            return $file_res;
        }
        $assets = self::resolve_assets($registered_name);
        $meta   = self::read_meta($file_res);
        return self::build_zip($registered_name, $file_res, $assets, $meta);
    }

    public static function stream(string $registered_name): void
    {
        $zip_res = self::build($registered_name);
        if (is_wp_error($zip_res)) {
            wp_die(
                esc_html__($zip_res->get_error_message(), 'awb-starter'),
                esc_html__('Export Error', 'awb-starter'),
                ['response' => $zip_res->get_error_data() ? 404 : 500]
            );
        }
        $meta = self::read_meta(self::resolve_pattern_file($registered_name));
        self::send_zip($zip_res, $meta['slug']);
    }

    /**
     * Resolve the pattern file path for export.
     *
     * @param string $registered_name The registered pattern name.
     * @return string|WP_Error The file path on success, WP_Error on failure.
     */
    private static function resolve_pattern_file(string $registered_name): string|\WP_Error
    {
        $files = AWB_Pattern_Loader::$pattern_files;
        if (empty($files[$registered_name])) {
            return new \WP_Error('pattern_not_found', __('Export failed: pattern not found in file map. It may be an HTML template which is not exportable.', 'awb-starter'));
        }
        $path = $files[$registered_name];
        if (! is_readable($path)) {
            return new \WP_Error('file_unreadable', __('Export failed: pattern file is not readable.', 'awb-starter'));
        }
        return $path;
    }

    private static function resolve_assets(string $registered_name): array
    {
        $asset_map = AWB_Pattern_Loader::$pattern_assets;
        $entry     = $asset_map[$registered_name] ?? [];
        $css_rel   = $entry['css'] ?? '';
        $js_rel    = $entry['js']  ?? '';
        $source    = $entry['source'] ?? 'core';
        $base_path = ('user' === $source) ? AWB_USER_PATTERNS_PATH : AWB_PLUGIN_PATH;

        return [
            'css_abs' => ($css_rel && file_exists($base_path . $css_rel)) ? $base_path . $css_rel : '',
            'css_rel' => $css_rel,
            'js_abs'  => ($js_rel && file_exists($base_path . $js_rel))  ? $base_path . $js_rel  : '',
            'js_rel'  => $js_rel,
        ];
    }

    private static function read_meta(string $filepath): array
    {
        $raw = get_file_data($filepath, [
            'title'       => 'Title',
            'slug'        => 'Slug',
            'categories'  => 'Categories',
            'keywords'    => 'Keywords',
            'description' => 'Description',
        ]);
        return [
            'title'       => sanitize_text_field($raw['title'] ?? ''),
            'slug'        => sanitize_title($raw['slug'] ?? ''),
            'categories'  => ! empty($raw['categories']) ? array_map('sanitize_text_field', array_map('trim', explode(',', $raw['categories']))) : [],
            'keywords'    => ! empty($raw['keywords']) ? array_map('sanitize_text_field', array_map('trim', explode(',', $raw['keywords']))) : [],
            'description' => sanitize_text_field($raw['description'] ?? ''),
        ];
    }

    private static function build_zip(string $registered_name, string $pattern_file, array $assets, array $meta): string|\WP_Error
    {
        $slug   = $meta['slug'] ?: sanitize_title(str_replace('awb/', '', $registered_name));
        $prefix = trailingslashit($slug);
        $tmp    = wp_tempnam($slug . '.zip');
        if (! $tmp) {
            return new \WP_Error('tempnam_failed', __('Export failed: could not create temporary file.', 'awb-starter'));
        }
        $zip    = new \ZipArchive();
        $opened = $zip->open($tmp, \ZipArchive::OVERWRITE);
        if (true !== $opened) {
            @unlink($tmp); // phpcs:ignore WordPress.PHP.NoSilencedErrors
            return new \WP_Error('zip_open_failed', sprintf(__('Export failed: could not create ZIP archive (error code %d).', 'awb-starter'), (int) $opened));
        }
        $zip->addFile($pattern_file, $prefix . 'pattern.php');
        $has_css = ! empty($assets['css_abs']);
        if ($has_css) {
            $zip->addFile($assets['css_abs'], $prefix . 'pattern.css');
        }
        $has_js = ! empty($assets['js_abs']);
        if ($has_js) {
            $zip->addFile($assets['js_abs'], $prefix . 'pattern.js');
        }
        $manifest = [
            'title'       => $meta['title'],
            'slug'        => $slug,
            'categories'  => $meta['categories'],
            'keywords'    => $meta['keywords'],
            'description' => $meta['description'],
            'has_css'     => $has_css,
            'has_js'      => $has_js,
            'css_file'    => $assets['css_rel'],
            'js_file'     => $assets['js_rel'],
            'awb_version' => AWB_VERSION,
            'exported_at' => gmdate('c'),
        ];
        $zip->addFromString($prefix . 'metadata.json', wp_json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $zip->close();
        return $tmp;
    }

    private static function send_zip(string $zip_path, string $slug): void
    {
        $filename = 'awb-pattern-' . $slug . '.zip';
        if (ob_get_level()) ob_end_clean();
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($zip_path));
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        readfile($zip_path); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
        @unlink($zip_path); // phpcs:ignore WordPress.PHP.NoSilencedErrors
        exit;
    }
}
