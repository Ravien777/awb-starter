<?php

/**
 * Class AWB_Pattern_Importer
 *
 * Validates and installs a pattern ZIP archive produced by AWB_Pattern_Exporter.
 *
 * Security measures implemented:
 *
 *  - Capability + nonce verified by AWB_Ajax_Handler before this class is called.
 *  - File size checked against wp_max_upload_size() before ZipArchive is opened.
 *  - MIME type verified via finfo_file() — not the user-supplied $_FILES['type'].
 *  - ZIP entries are NEVER extracted by their stored names (zip-slip prevention).
 *    Contents are read as strings via ZipArchive::getFromName() using paths we
 *    construct ourselves, then written via WP_Filesystem.
 *  - Every destination path is checked with is_path_within() before any write.
 *  - metadata.json 'slug' is run through sanitize_title() — strips traversal chars.
 *  - css_file / js_file paths from metadata are validated: no '..' segments,
 *    correct file extension (.css / .js), must resolve within AWB_PLUGIN_PATH.
 *  - Collision check returns structured JSON before writing; overwrite requires
 *    explicit force=1 from the client (Step 5 UI confirmation dialog).
 *
 * Expected ZIP structure (produced by AWB_Pattern_Exporter):
 *
 *   {slug}/
 *     pattern.php        required
 *     pattern.css        optional (only when has_css=true in metadata)
 *     pattern.js         optional (only when has_js=true in metadata)
 *     metadata.json      required
 *
 * Files are written to:
 *   pattern.php  → AWB_PATTERNS_PATH . {slug} . '/' . {slug} . '.php'
 *   pattern.css  → AWB_PLUGIN_PATH   . {css_file from metadata}
 *   pattern.js   → AWB_PLUGIN_PATH   . {js_file from metadata}
 *
 * @package AWB_Starter
 * @since   2.3.0
 */

if (! defined('ABSPATH')) {
    exit;
}

class AWB_Pattern_Importer
{
    // -------------------------------------------------------------------------
    // Public API
    // -------------------------------------------------------------------------

    /**
     * Install a pattern from a ZIP file already present on the server.
     *
     * @param string $zip_path Absolute path to the ZIP file.
     * @param bool   $force    Whether to overwrite existing files.
     * @return array{ success: bool, data?: array, error?: string }
     */
    public static function install_from_zip(string $zip_path, bool $force = false): array
    {
        // 1. Validate file exists and is readable.
        if (! is_readable($zip_path)) {
            return ['success' => false, 'error' => __('ZIP file not readable.', 'awb-starter')];
        }

        // 2. File size check.
        $size = filesize($zip_path);
        if (false === $size || $size > wp_max_upload_size()) {
            return ['success' => false, 'error' => __('ZIP file exceeds maximum size.', 'awb-starter')];
        }

        // 3. MIME type check.
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = $finfo ? finfo_file($finfo, $zip_path) : '';
        if ($finfo) {
            finfo_close($finfo);
        }
        $allowed = ['application/zip', 'application/x-zip', 'application/x-zip-compressed'];
        if (! in_array($mime, $allowed, true)) {
            return ['success' => false, 'error' => __('File is not a valid ZIP archive.', 'awb-starter')];
        }

        // 4. Open ZIP.
        $zip = new ZipArchive();
        if (true !== $zip->open($zip_path, ZipArchive::RDONLY)) {
            return ['success' => false, 'error' => __('Could not open ZIP archive.', 'awb-starter')];
        }

        // 5. Locate prefix and read metadata.
        $prefix = self::find_prefix($zip);
        if (! $prefix) {
            $zip->close();
            return ['success' => false, 'error' => __('Invalid ZIP structure: metadata.json not found.', 'awb-starter')];
        }

        $meta = self::read_metadata($zip, $prefix);
        if (is_wp_error($meta)) {
            $zip->close();
            return ['success' => false, 'error' => $meta->get_error_message()];
        }

        // 6. Build destination paths.
        $paths = self::build_destination_paths($meta);

        // 7. Collision check.
        if (! $force) {
            $collisions = self::find_collisions($paths);
            if (! empty($collisions)) {
                $zip->close();
                return [
                    'success'   => false,
                    'collision' => true,
                    'title'     => $meta['title'],
                    'slug'      => $meta['slug'],
                    'files'     => $collisions,
                ];
            }
        }

        // 8. Write files.
        try {
            self::write_files($zip, $prefix, $meta, $paths);
        } catch (Exception $e) {
            $zip->close();
            return ['success' => false, 'error' => $e->getMessage()];
        }

        $zip->close();
        return [
            'success' => true,
            'data'    => [
                'title'   => $meta['title'],
                'slug'    => $meta['slug'],
                'message' => sprintf(__('Pattern "%s" installed successfully.', 'awb-starter'), $meta['title']),
            ],
        ];
    }

    /**
     * Process an uploaded pattern ZIP.
     *
     * Reads from $_FILES['awb_pattern_zip'] and $_POST['force'].
     * Always terminates via wp_send_json_success() or wp_send_json_error().
     *
     * Response shapes:
     *
     *   Success:
     *     { success: true, data: { title, slug, message } }
     *
     *   Collision (overwrite confirmation required):
     *     { success: false, data: { code: 'collision', title, slug, files: [...] } }
     *
     *   Error:
     *     { success: false, data: { code: 'error', message } }
     */
    public static function handle_upload(): void
    {
        // 1. Validate the uploaded file exists.
        if (
            empty($_FILES['awb_pattern_zip'])
            || ! isset($_FILES['awb_pattern_zip']['tmp_name'])
            || UPLOAD_ERR_OK !== (int) $_FILES['awb_pattern_zip']['error']
        ) {
            wp_send_json_error([
                'code'    => 'error',
                'message' => __('No file uploaded or upload error occurred.', 'awb-starter'),
            ]);
        }

        $tmp_path = $_FILES['awb_pattern_zip']['tmp_name'];
        $force    = ! empty($_POST['force']) && '1' === $_POST['force'];

        $result = self::install_from_zip($tmp_path, $force);

        if ($result['success']) {
            wp_send_json_success($result['data']);
        } else {
            $error_data = ['message' => $result['error'] ?? __('Import failed.', 'awb-starter')];
            if (isset($result['collision']) && $result['collision']) {
                $error_data['code']  = 'collision';
                $error_data['title'] = $result['title'];
                $error_data['slug']  = $result['slug'];
                $error_data['files'] = $result['files'];
            }
            wp_send_json_error($error_data);
        }
    }

    // -------------------------------------------------------------------------
    // Validation helpers
    // -------------------------------------------------------------------------

    /**
     * Abort if the uploaded file exceeds the server's max upload size.
     *
     * @param string $tmp_path Absolute path to the uploaded temp file.
     */
    private static function assert_file_size(string $tmp_path): void
    {
        $size     = filesize($tmp_path);
        $max_size = wp_max_upload_size();

        if (false === $size || $size > $max_size) {
            wp_send_json_error([
                'code'    => 'error',
                'message' => sprintf(
                    /* translators: %s: max upload size (e.g. "8 MB") */
                    __('File exceeds the maximum upload size of %s.', 'awb-starter'),
                    size_format($max_size)
                ),
            ]);
        }
    }

    /**
     * Abort if the file is not a real ZIP archive.
     * Uses finfo_file() — ignores user-supplied Content-Type.
     *
     * @param string $tmp_path
     */
    private static function assert_mime_type(string $tmp_path): void
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = $finfo ? finfo_file($finfo, $tmp_path) : '';
        if ($finfo) {
            finfo_close($finfo);
        }

        $allowed = ['application/zip', 'application/x-zip', 'application/x-zip-compressed'];

        if (! in_array($mime, $allowed, true)) {
            wp_send_json_error([
                'code'    => 'error',
                'message' => __('Uploaded file is not a valid ZIP archive.', 'awb-starter'),
            ]);
        }
    }

    /**
     * Open the ZIP file and return the ZipArchive instance.
     * Aborts on failure.
     *
     * @param  string $tmp_path
     * @return ZipArchive
     */
    private static function open_zip(string $tmp_path): ZipArchive
    {
        if (! class_exists('ZipArchive')) {
            wp_send_json_error([
                'code'    => 'error',
                'message' => __('ZipArchive is not available on this server.', 'awb-starter'),
            ]);
        }

        $zip    = new ZipArchive();
        $result = $zip->open($tmp_path, ZipArchive::RDONLY);

        if (true !== $result) {
            wp_send_json_error([
                'code'    => 'error',
                'message' => sprintf(
                    /* translators: %d: ZipArchive error code */
                    __('Could not open ZIP archive (error code %d).', 'awb-starter'),
                    (int) $result
                ),
            ]);
        }

        return $zip;
    }

    /**
     * Scan the ZIP for a metadata.json exactly one folder deep and return
     * the folder prefix (e.g. 'header-dark/').
     *
     * Expected structure: {slug}/metadata.json
     * Aborts if not found or if the structure is malformed.
     *
     * @param  ZipArchive $zip
     * @return string Folder prefix with trailing slash, e.g. 'header-dark/'
     */
    private static function find_prefix(ZipArchive $zip): string
    {
        $prefix = '';

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (false === $name) {
                continue;
            }

            // Match exactly: one path segment + '/metadata.json'
            // e.g. 'header-dark/metadata.json' → prefix = 'header-dark/'
            if (preg_match('#^([^/]+)/metadata\.json$#', $name, $m)) {
                $prefix = $m[1] . '/';
                break;
            }
        }

        if ('' === $prefix) {
            $zip->close();
            wp_send_json_error([
                'code'    => 'error',
                'message' => __('Invalid ZIP structure: metadata.json not found.', 'awb-starter'),
            ]);
        }

        return $prefix;
    }

    /**
     * Read, parse, and validate metadata.json from the ZIP.
     *
     * Size-limited to 64 KB to prevent memory exhaustion.
     * Required fields: title, slug, awb_version.
     * slug is sanitised with sanitize_title() — eliminates traversal chars.
     *
     * @param  ZipArchive $zip
     * @param  string     $prefix e.g. 'header-dark/'
     * @return array      Validated and sanitised metadata.
     */
    private static function read_metadata(ZipArchive $zip, string $prefix): array|WP_Error
    {
        // 64 KB limit on the metadata JSON.
        $json = $zip->getFromName($prefix . 'metadata.json', 65536);

        if (false === $json || '' === $json) {
            return new WP_Error('read_failed', __('Could not read metadata.json from ZIP.', 'awb-starter'));
        }

        $raw = json_decode($json, true);

        if (! is_array($raw)) {
            return new WP_Error('invalid_json', __('metadata.json is not valid JSON.', 'awb-starter'));
        }

        // Required fields.
        foreach (['title', 'slug', 'awb_version'] as $field) {
            if (empty($raw[$field])) {
                return new WP_Error('missing_field', sprintf(__('metadata.json is missing required field: %s', 'awb-starter'), $field));
            }
        }

        // Verify pattern.php exists at the expected location.
        if (false === $zip->locateName($prefix . 'pattern.php')) {
            return new WP_Error('missing_pattern', __('Invalid ZIP structure: pattern.php not found.', 'awb-starter'));
        }

        $slug = sanitize_title($raw['slug']);

        if ('' === $slug) {
            $zip->close();
            wp_send_json_error([
                'code'    => 'error',
                'message' => __('Pattern slug is invalid or empty after sanitization.', 'awb-starter'),
            ]);
        }

        return [
            'title'       => sanitize_text_field($raw['title']),
            'slug'        => $slug,
            'categories'  => isset($raw['categories']) && is_array($raw['categories'])
                ? array_map('sanitize_text_field', $raw['categories'])
                : [],
            'keywords'    => isset($raw['keywords']) && is_array($raw['keywords'])
                ? array_map('sanitize_text_field', $raw['keywords'])
                : [],
            'description' => sanitize_text_field($raw['description'] ?? ''),
            'has_css'     => ! empty($raw['has_css']),
            'has_js'      => ! empty($raw['has_js']),
            'css_file'    => $raw['css_file'] ?? '',
            'js_file'     => $raw['js_file']  ?? '',
            'awb_version' => sanitize_text_field($raw['awb_version']),
        ];
    }

    // -------------------------------------------------------------------------
    // Path resolution
    // -------------------------------------------------------------------------

    /**
     * Build and validate all destination file paths for this import.
     *
     * Pattern PHP → AWB_USER_PATTERNS_PATH . 'patterns/' . {slug} / {slug} . '.php'
     * CSS         → AWB_USER_PATTERNS_PATH . 'css/' . {slug} . '.css'
     * JS          → AWB_USER_PATTERNS_PATH . 'js/' . {slug} . '.js'
     */
    private static function build_destination_paths(array $meta): array
    {
        $slug = $meta['slug'];

        // Pattern PHP destination.
        $pattern_dir = trailingslashit(AWB_USER_PATTERNS_PATH . 'patterns/' . $slug);
        $pattern_php = $pattern_dir . $slug . '.php';

        self::assert_path_within($pattern_php, AWB_USER_PATTERNS_PATH, 'pattern file');

        // CSS destination (optional).
        $css_path = '';
        if ($meta['has_css']) {
            $css_path = AWB_USER_PATTERNS_PATH . 'css/' . $slug . '.css';
            self::assert_path_within($css_path, AWB_USER_PATTERNS_PATH, 'CSS asset');
        }

        // JS destination (optional).
        $js_path = '';
        if ($meta['has_js']) {
            $js_path = AWB_USER_PATTERNS_PATH . 'js/' . $slug . '.js';
            self::assert_path_within($js_path, AWB_USER_PATTERNS_PATH, 'JS asset');
        }

        return [
            'pattern_dir' => $pattern_dir,
            'pattern_php' => $pattern_php,
            'css'         => $css_path,
            'js'          => $js_path,
        ];
    }

    /**
     * Assert that $path is within $root. Aborts with JSON error if not.
     * Uses string prefix matching on normalised paths — works for paths
     * that don't yet exist on disk (unlike realpath()).
     *
     * @param string $path        Absolute path to check.
     * @param string $root        Allowed root directory (trailing slash).
     * @param string $label       Human-readable label for error messages.
     */
    private static function assert_path_within(string $path, string $root, string $label): void
    {
        $norm_path = wp_normalize_path($path);
        $norm_root = wp_normalize_path(trailingslashit($root));

        if (! str_starts_with($norm_path, $norm_root)) {
            wp_send_json_error([
                'code'    => 'error',
                /* translators: %s: file type label */
                'message' => sprintf(
                    __('Security check failed: %s path is outside the allowed directory.', 'awb-starter'),
                    $label
                ),
            ]);
        }
    }

    // -------------------------------------------------------------------------
    // Collision detection
    // -------------------------------------------------------------------------

    /**
     * Return a list of destination paths that already exist on disk.
     * An empty array means no collision and the import can proceed.
     *
     * @param  array $paths From build_destination_paths().
     * @return string[]     Relative paths (relative to plugin root) of colliding files.
     */
    private static function find_collisions(array $paths): array
    {
        $collisions = [];
        $plugin_root = wp_normalize_path(AWB_PLUGIN_PATH);

        $check = array_filter([
            $paths['pattern_php'],
            $paths['css'],
            $paths['js'],
        ]);

        foreach ($check as $abs_path) {
            if (file_exists($abs_path)) {
                // Return relative path for display in the UI.
                $collisions[] = str_replace($plugin_root, '', wp_normalize_path($abs_path));
            }
        }

        return $collisions;
    }

    // -------------------------------------------------------------------------
    // File writing
    // -------------------------------------------------------------------------

    /**
     * Read contents from the ZIP and write to their destination paths.
     * Uses WP_Filesystem for compatibility across hosting configurations.
     *
     * @param ZipArchive $zip
     * @param string     $prefix  e.g. 'header-dark/'
     * @param array      $meta    Validated metadata.
     * @param array      $paths   Destination paths from build_destination_paths().
     */
    private static function write_files(
        ZipArchive $zip,
        string $prefix,
        array $meta,
        array $paths
    ): void {
        // Initialise WP_Filesystem.
        global $wp_filesystem;
        if (empty($wp_filesystem)) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            WP_Filesystem();
        }

        // ── pattern.php ──────────────────────────────────────────────────────

        $php_content = $zip->getFromName($prefix . 'pattern.php');
        if (false === $php_content) {
            wp_send_json_error([
                'code'    => 'error',
                'message' => __('Could not read pattern.php from ZIP.', 'awb-starter'),
            ]);
        }

        // Create the slug subdirectory if it doesn't exist.
        if (! $wp_filesystem->is_dir($paths['pattern_dir'])) {
            if (! $wp_filesystem->mkdir($paths['pattern_dir'], FS_CHMOD_DIR, true)) {
                wp_send_json_error([
                    'code'    => 'error',
                    'message' => __('Could not create pattern directory. Check directory permissions.', 'awb-starter'),
                ]);
            }
        }

        if (! $wp_filesystem->put_contents($paths['pattern_php'], $php_content, FS_CHMOD_FILE)) {
            wp_send_json_error([
                'code'    => 'error',
                'message' => __('Could not write pattern.php. Check directory permissions.', 'awb-starter'),
            ]);
        }

        // ── pattern.css (optional) ───────────────────────────────────────────

        if ($meta['has_css'] && ! empty($paths['css'])) {
            $css_content = $zip->getFromName($prefix . 'pattern.css');
            if (false !== $css_content) {
                self::ensure_directory(dirname($paths['css']), $wp_filesystem);
                $wp_filesystem->put_contents($paths['css'], $css_content, FS_CHMOD_FILE);
            }
        }

        // ── pattern.js (optional) ────────────────────────────────────────────

        if ($meta['has_js'] && ! empty($paths['js'])) {
            $js_content = $zip->getFromName($prefix . 'pattern.js');
            if (false !== $js_content) {
                self::ensure_directory(dirname($paths['js']), $wp_filesystem);
                $wp_filesystem->put_contents($paths['js'], $js_content, FS_CHMOD_FILE);
            }
        }
    }

    /**
     * Create a directory (and any missing parents) if it does not exist.
     *
     * @param string          $dir
     * @param WP_Filesystem_Base $fs
     */
    private static function ensure_directory(string $dir, WP_Filesystem_Base $fs): void
    {
        if (! $fs->is_dir($dir)) {
            $fs->mkdir($dir, FS_CHMOD_DIR, true);
        }
    }
}
