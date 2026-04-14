<?php

/**
 * Class AWB_Pattern_Exporter
 *
 * Builds a self-contained ZIP archive for a registered AWB pattern and
 * streams it to the browser as a file download.
 *
 * ZIP structure:
 *   {slug}/
 *     pattern.php        — the pattern source file (PHP or HTML)
 *     pattern.css        — only present when the pattern declares a CSS file
 *     pattern.js         — only present when the pattern declares a JS file
 *     metadata.json      — machine-readable manifest (required by Importer)
 *
 * CSS/JS files are renamed to pattern.css / pattern.js inside the archive so
 * the Importer does not need to guess filenames. Their original relative paths
 * (relative to plugin root) are preserved in metadata.json so the Importer
 * can reconstruct the correct file locations on install.
 *
 * Usage (called from AWB_Ajax_Handler):
 *   AWB_Pattern_Exporter::stream( 'awb/header-dark' );
 *   // → streams ZIP then exits; never returns normally.
 *
 * @package AWB_Starter
 * @since   2.3.0
 */

if (! defined('ABSPATH')) {
    exit;
}

class AWB_Pattern_Exporter
{
    // -------------------------------------------------------------------------
    // Public API
    // -------------------------------------------------------------------------

    /**
     * Build a ZIP for the given registered pattern name and stream it.
     *
     * Terminates execution via wp_die() on error or exit() after streaming.
     * Never returns normally — callers must treat this as a terminal operation.
     *
     * @param string $registered_name Full registered pattern name, e.g. 'awb/header-dark'.
     */
    public static function stream(string $registered_name): void
    {
        // 1. Validate ZipArchive is available.
        if (! class_exists('ZipArchive')) {
            wp_die(
                esc_html__('Export failed: ZipArchive is not available on this server. Please ask your host to enable the PHP zip extension.', 'awb-starter'),
                esc_html__('Export Error', 'awb-starter'),
                ['response' => 500]
            );
        }

        // 2. Resolve source files from the static maps built by AWB_Pattern_Loader.
        $pattern_file = self::resolve_pattern_file($registered_name);
        $assets       = self::resolve_assets($registered_name);

        // 3. Read and validate the pattern metadata.
        $meta = self::read_meta($pattern_file);

        // 4. Build the ZIP in a temporary file.
        $zip_path = self::build_zip($registered_name, $pattern_file, $assets, $meta);

        // 5. Stream the ZIP to the browser, then clean up.
        self::send_zip($zip_path, $meta['slug']);
        // send_zip() exits — execution never reaches here.
    }

    // -------------------------------------------------------------------------
    // Step internals
    // -------------------------------------------------------------------------

    /**
     * Resolve the absolute path to the pattern source file.
     *
     * @param string $registered_name
     * @return string Absolute path to the .php or .html file.
     */
    private static function resolve_pattern_file(string $registered_name): string
    {
        $files = AWB_Pattern_Loader::$pattern_files;

        if (empty($files[$registered_name])) {
            wp_die(
                esc_html__('Export failed: pattern not found in file map. It may be an HTML template which is not exportable.', 'awb-starter'),
                esc_html__('Export Error', 'awb-starter'),
                ['response' => 404]
            );
        }

        $path = $files[$registered_name];

        if (! is_readable($path)) {
            wp_die(
                esc_html__('Export failed: pattern file is not readable.', 'awb-starter'),
                esc_html__('Export Error', 'awb-starter'),
                ['response' => 500]
            );
        }

        return $path;
    }

    /**
     * Resolve asset paths (CSS/JS) for the pattern, returning absolute paths
     * and their original relative paths for inclusion in metadata.json.
     *
     * @param string $registered_name
     * @return array{
     *     css_abs: string,
     *     css_rel: string,
     *     js_abs:  string,
     *     js_rel:  string,
     * }
     */
    private static function resolve_assets(string $registered_name): array
    {
        $asset_map = AWB_Pattern_Loader::$pattern_assets;
        $entry     = $asset_map[$registered_name] ?? [];

        $css_rel = $entry['css'] ?? '';
        $js_rel  = $entry['js']  ?? '';

        return [
            'css_abs' => ($css_rel && file_exists(AWB_PLUGIN_PATH . $css_rel))
                ? AWB_PLUGIN_PATH . $css_rel
                : '',
            'css_rel' => $css_rel,
            'js_abs'  => ($js_rel && file_exists(AWB_PLUGIN_PATH . $js_rel))
                ? AWB_PLUGIN_PATH . $js_rel
                : '',
            'js_rel'  => $js_rel,
        ];
    }

    /**
     * Read pattern metadata from the file header via get_file_data().
     *
     * @param string $filepath Absolute path to the pattern PHP file.
     * @return array Normalised metadata array.
     */
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
            'categories'  => ! empty($raw['categories'])
                ? array_map('sanitize_text_field', array_map('trim', explode(',', $raw['categories'])))
                : [],
            'keywords'    => ! empty($raw['keywords'])
                ? array_map('sanitize_text_field', array_map('trim', explode(',', $raw['keywords'])))
                : [],
            'description' => sanitize_text_field($raw['description'] ?? ''),
        ];
    }

    /**
     * Build the ZIP archive and return the path to the temp file.
     *
     * @param string $registered_name  e.g. 'awb/header-dark'
     * @param string $pattern_file     Absolute path to the pattern source file.
     * @param array  $assets           Resolved asset paths from resolve_assets().
     * @param array  $meta             Pattern metadata from read_meta().
     * @return string Absolute path to the generated temp ZIP file.
     */
    private static function build_zip(
        string $registered_name,
        string $pattern_file,
        array $assets,
        array $meta
    ): string {
        // Use the slug as the subfolder name inside the ZIP.
        $slug   = $meta['slug'] ?: sanitize_title(str_replace('awb/', '', $registered_name));
        $prefix = trailingslashit($slug); // e.g. 'header-dark/'

        // Create a temp file with a .zip extension.
        $tmp = wp_tempnam($slug . '.zip');

        $zip = new ZipArchive();
        $opened = $zip->open($tmp, ZipArchive::OVERWRITE);

        if (true !== $opened) {
            @unlink($tmp); // phpcs:ignore WordPress.PHP.NoSilencedErrors
            wp_die(
                /* translators: %d: ZipArchive error code */
                sprintf(esc_html__('Export failed: could not create ZIP archive (error code %d).', 'awb-starter'), (int) $opened),
                esc_html__('Export Error', 'awb-starter'),
                ['response' => 500]
            );
        }

        // ── pattern.php ──────────────────────────────────────────────────────
        $zip->addFile($pattern_file, $prefix . 'pattern.php');

        // ── pattern.css (optional) ───────────────────────────────────────────
        $has_css = ! empty($assets['css_abs']);
        if ($has_css) {
            $zip->addFile($assets['css_abs'], $prefix . 'pattern.css');
        }

        // ── pattern.js (optional) ────────────────────────────────────────────
        $has_js = ! empty($assets['js_abs']);
        if ($has_js) {
            $zip->addFile($assets['js_abs'], $prefix . 'pattern.js');
        }

        // ── metadata.json ────────────────────────────────────────────────────
        $manifest = [
            'title'       => $meta['title'],
            'slug'        => $slug,
            'categories'  => $meta['categories'],
            'keywords'    => $meta['keywords'],
            'description' => $meta['description'],
            'has_css'     => $has_css,
            'has_js'      => $has_js,
            // Original relative paths preserved so Importer can reconstruct
            // the correct directory structure inside the plugin.
            'css_file'    => $assets['css_rel'],
            'js_file'     => $assets['js_rel'],
            'awb_version' => AWB_VERSION,
            'exported_at' => gmdate('c'), // ISO 8601 UTC
        ];

        $zip->addFromString(
            $prefix . 'metadata.json',
            wp_json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        $zip->close();

        return $tmp;
    }

    /**
     * Stream the ZIP file to the browser and clean up the temp file.
     *
     * Sets appropriate download headers, outputs file contents via readfile(),
     * deletes the temp file, then calls exit. Never returns.
     *
     * @param string $zip_path Absolute path to the temp ZIP file.
     * @param string $slug     Pattern slug, used to name the download.
     */
    private static function send_zip(string $zip_path, string $slug): void
    {
        $filename = 'awb-pattern-' . $slug . '.zip';

        // Prevent any buffered output from corrupting the binary stream.
        if (ob_get_level()) {
            ob_end_clean();
        }

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
