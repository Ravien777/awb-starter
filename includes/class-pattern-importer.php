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

        // 2. File size check.
        self::assert_file_size($tmp_path);

        // 3. MIME type check.
        self::assert_mime_type($tmp_path);

        // 4. Open ZIP and locate metadata.json.
        $zip    = self::open_zip($tmp_path);
        $prefix = self::find_prefix($zip);
        $meta   = self::read_metadata($zip, $prefix);

        // 5. Validate metadata and build destination paths.
        $paths  = self::build_destination_paths($meta);

        // 6. Collision check — before any write.
        if (! $force) {
            $collisions = self::find_collisions($paths);
            if (! empty($collisions)) {
                $zip->close();
                wp_send_json_error([
                    'code'  => 'collision',
                    'title' => $meta['title'],
                    'slug'  => $meta['slug'],
                    'files' => $collisions,
                ]);
            }
        }

        // 7. Write files.
        self::write_files($zip, $prefix, $meta, $paths);
        $zip->close();

        wp_send_json_success([
            'title'   => $meta['title'],
            'slug'    => $meta['slug'],
            'message' => sprintf(
                /* translators: %s: pattern title */
                __('Pattern "%s" imported successfully.', 'awb-starter'),
                $meta['title']
            ),
        ]);
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
    private static function read_metadata(ZipArchive $zip, string $prefix): array
    {
        // 64 KB limit on the metadata JSON.
        $json = $zip->getFromName($prefix . 'metadata.json', 65536);

        if (false === $json || '' === $json) {
            $zip->close();
            wp_send_json_error([
                'code'    => 'error',
                'message' => __('Could not read metadata.json from ZIP.', 'awb-starter'),
            ]);
        }

        $raw = json_decode($json, true);

        if (! is_array($raw)) {
            $zip->close();
            wp_send_json_error([
                'code'    => 'error',
                'message' => __('metadata.json is not valid JSON.', 'awb-starter'),
            ]);
        }

        // Required fields.
        foreach (['title', 'slug', 'awb_version'] as $field) {
            if (empty($raw[$field])) {
                $zip->close();
                wp_send_json_error([
                    'code'    => 'error',
                    /* translators: %s: field name */
                    'message' => sprintf(__('metadata.json is missing required field: %s', 'awb-starter'), $field),
                ]);
            }
        }

        // Verify pattern.php exists at the expected location.
        if (false === $zip->locateName($prefix . 'pattern.php')) {
            $zip->close();
            wp_send_json_error([
                'code'    => 'error',
                'message' => __('Invalid ZIP structure: pattern.php not found.', 'awb-starter'),
            ]);
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
     * Pattern PHP → AWB_PATTERNS_PATH . {slug} / {slug} . '.php'
     * CSS         → AWB_PLUGIN_PATH   . {css_file}   (validated)
     * JS          → AWB_PLUGIN_PATH   . {js_file}    (validated)
     *
     * Aborts with a JSON error if any path fails validation.
     *
     * @param  array $meta Sanitised metadata from read_metadata().
     * @return array{
     *     pattern_dir: string,
     *     pattern_php: string,
     *     css:         string,
     *     js:          string,
     * }
     */
    private static function build_destination_paths(array $meta): array
    {
        $slug = $meta['slug'];

        // Pattern PHP destination.
        $pattern_dir = trailingslashit(AWB_PATTERNS_PATH . $slug);
        $pattern_php = $pattern_dir . $slug . '.php';

        self::assert_path_within($pattern_php, AWB_PATTERNS_PATH, 'pattern file');

        // CSS destination (optional).
        $css_path = '';
        if ($meta['has_css'] && ! empty($meta['css_file'])) {
            $css_path = self::validate_asset_path($meta['css_file'], 'css');
        }

        // JS destination (optional).
        $js_path = '';
        if ($meta['has_js'] && ! empty($meta['js_file'])) {
            $js_path = self::validate_asset_path($meta['js_file'], 'js');
        }

        return [
            'pattern_dir' => $pattern_dir,
            'pattern_php' => $pattern_php,
            'css'         => $css_path,
            'js'          => $js_path,
        ];
    }

    /**
     * Validate a relative asset path from metadata (css_file / js_file).
     *
     * Rules:
     *  - No '..' path segments
     *  - Extension must match expected type (.css or .js)
     *  - Resolved absolute path must be within AWB_PLUGIN_PATH
     *
     * @param  string $relative_path  e.g. 'assets/css/headers/header-dark.css'
     * @param  string $type           'css' or 'js'
     * @return string Absolute path if valid.
     */
    private static function validate_asset_path(string $relative_path, string $type): string
    {
        $normalised = wp_normalize_path($relative_path);

        // Reject any '..' segments — covers traversal attempts.
        $segments = explode('/', $normalised);
        foreach ($segments as $segment) {
            if ('..' === $segment || '.' === $segment) {
                wp_send_json_error([
                    'code'    => 'error',
                    'message' => __('Invalid asset path in metadata.json.', 'awb-starter'),
                ]);
            }
        }

        // Extension must match the declared type.
        $ext = strtolower(pathinfo($normalised, PATHINFO_EXTENSION));
        if ($ext !== $type) {
            wp_send_json_error([
                'code'    => 'error',
                /* translators: 1: expected extension, 2: actual extension */
                'message' => sprintf(
                    __('Asset path has wrong extension: expected .%1$s, got .%2$s.', 'awb-starter'),
                    $type,
                    $ext
                ),
            ]);
        }

        $abs = AWB_PLUGIN_PATH . $normalised;
        self::assert_path_within($abs, AWB_PLUGIN_PATH, $type . ' asset');

        return $abs;
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
