<?php

/**
 * Class AWB_Pattern_Duplicator
 *
 * Clones a registered AWB PHP pattern file, generating a unique slug and
 * rewriting the file header comments (Title, Slug) in the clone.
 *
 * What changes in the clone:
 *   Title: {original} → {original} (Copy)
 *   Slug:  {original} → {original}-copy  (or -copy-2, -copy-3 … up to -copy-99)
 *
 * What is preserved verbatim:
 *   Categories, Keywords, Description, CSS, JS declarations, all block content.
 *
 * The clone is written to the same directory as the source file so the
 * folder → category relationship used by the Pattern Library tab is maintained.
 * AWB_Pattern_Loader uses RecursiveIteratorIterator, so it finds the new file
 * on the next page load regardless of which subdirectory it lives in.
 *
 * Only PHP patterns (present in AWB_Pattern_Loader::$pattern_files) can be
 * duplicated. HTML block-templates are explicitly excluded.
 *
 * Usage (called from AWB_Ajax_Handler):
 *   $result = AWB_Pattern_Duplicator::duplicate('awb/header-dark');
 *   // Returns array on success, WP_Error on failure.
 *
 * @package AWB_Starter
 * @since   2.3.0
 */

if (! defined('ABSPATH')) {
    exit;
}

class AWB_Pattern_Duplicator
{
    /**
     * Maximum number of -copy-N suffixes to try before giving up.
     */
    const MAX_COPY_ATTEMPTS = 99;

    // -------------------------------------------------------------------------
    // Public API
    // -------------------------------------------------------------------------

    /**
     * Duplicate a registered AWB pattern.
     *
     * @param string $registered_name Full registered pattern name, e.g. 'awb/header-dark'.
     * @return array{
     *     new_registered_name: string,
     *     new_slug:            string,
     *     new_title:           string,
     *     new_file:            string,
     * }|WP_Error
     */
    public static function duplicate(string $registered_name): array|WP_Error
    {
        // 1. Resolve source file path from the loader map.
        $source_path = self::resolve_source_file($registered_name);
        if (is_wp_error($source_path)) {
            return $source_path;
        }

        // 2. Read raw file content and parse existing metadata.
        $source_content = self::read_source($source_path);
        if (is_wp_error($source_content)) {
            return $source_content;
        }

        $meta = self::read_meta($source_path);

        // 3. Generate unique slug and title for the clone.
        //    Destination is always the user patterns directory.
        $dest_dir = trailingslashit(AWB_USER_PATTERNS_PATH . 'patterns/' . dirname($meta['slug'] ?? ''));
        // Ensure the destination directory exists.
        if (! wp_mkdir_p($dest_dir)) {
            return new WP_Error('mkdir_failed', __('Could not create destination directory.', 'awb-starter'));
        }

        $new_slug = self::generate_unique_slug($meta['slug'], $dest_dir);
        if (is_wp_error($new_slug)) {
            return $new_slug;
        }

        $new_title = self::generate_title($meta['title']);

        // 4. Rewrite headers.
        $clone_content = self::rewrite_headers($source_content, $new_title, $new_slug);
        if (is_wp_error($clone_content)) {
            return $clone_content;
        }

        // 5. Write the clone.
        $new_filepath = $dest_dir . $new_slug . '.php';

        $written = self::write_clone($new_filepath, $clone_content);
        if (is_wp_error($written)) {
            return $written;
        }

        return [
            'new_registered_name' => 'awb/' . $new_slug,
            'new_slug'            => $new_slug,
            'new_title'           => $new_title,
            'new_file'            => $new_filepath,
        ];
    }

    // -------------------------------------------------------------------------
    // Source resolution
    // -------------------------------------------------------------------------

    /**
     * Resolve the absolute path to the source pattern file.
     * Only PHP patterns present in AWB_Pattern_Loader::$pattern_files are valid.
     *
     * @param string $registered_name
     * @return string|WP_Error
     */
    private static function resolve_source_file(string $registered_name): string|WP_Error
    {
        // Enforce 'awb/' prefix — only this plugin's patterns are duplicatable.
        if (strpos($registered_name, 'awb/') !== 0) {
            return new WP_Error(
                'invalid_pattern',
                __('Only AWB patterns can be duplicated.', 'awb-starter')
            );
        }

        $files = AWB_Pattern_Loader::$pattern_files;

        if (empty($files[$registered_name])) {
            return new WP_Error(
                'pattern_not_found',
                __('Pattern not found in file map. HTML block-templates cannot be duplicated.', 'awb-starter')
            );
        }

        $path = $files[$registered_name];

        if (! is_readable($path)) {
            return new WP_Error(
                'file_not_readable',
                __('Pattern source file is not readable.', 'awb-starter')
            );
        }

        return $path;
    }

    // -------------------------------------------------------------------------
    // File I/O
    // -------------------------------------------------------------------------

    /**
     * Read the raw content of the source file.
     *
     * @param string $filepath
     * @return string|WP_Error
     */
    private static function read_source(string $filepath): string|WP_Error
    {
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
        $content = file_get_contents($filepath);

        if (false === $content) {
            return new WP_Error(
                'read_failed',
                __('Could not read pattern source file.', 'awb-starter')
            );
        }

        return $content;
    }

    /**
     * Write the clone content to disk using WP_Filesystem.
     *
     * @param string $filepath     Absolute destination path (already validated).
     * @param string $content      File content to write.
     * @return true|WP_Error
     */
    private static function write_clone(string $filepath, string $content): true|WP_Error
    {
        // Confirm the destination is within AWB_USER_PATTERNS_PATH before writing.
        $norm_dest = wp_normalize_path($filepath);
        $norm_root = wp_normalize_path(trailingslashit(AWB_USER_PATTERNS_PATH));

        if (! str_starts_with($norm_dest, $norm_root)) {
            return new WP_Error(
                'path_outside_root',
                __('Security check failed: clone destination is outside the patterns directory.', 'awb-starter')
            );
        }

        // Initialise WP_Filesystem.
        global $wp_filesystem;
        if (empty($wp_filesystem)) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            WP_Filesystem();
        }

        if (! $wp_filesystem->put_contents($filepath, $content, FS_CHMOD_FILE)) {
            return new WP_Error(
                'write_failed',
                __('Could not write clone file. Check directory permissions.', 'awb-starter')
            );
        }

        return true;
    }

    // -------------------------------------------------------------------------
    // Metadata helpers
    // -------------------------------------------------------------------------

    /**
     * Read title and slug from the pattern file header via get_file_data().
     *
     * @param string $filepath
     * @return array{ title: string, slug: string }
     */
    private static function read_meta(string $filepath): array
    {
        $raw = get_file_data($filepath, [
            'title' => 'Title',
            'slug'  => 'Slug',
        ]);

        return [
            'title' => sanitize_text_field($raw['title'] ?? ''),
            'slug'  => sanitize_title($raw['slug'] ?? ''),
        ];
    }

    // -------------------------------------------------------------------------
    // Slug generation
    // -------------------------------------------------------------------------

    /**
     * Generate a unique slug for the clone, checking both filesystem
     * and the combined pattern file map (core + user).
     *
     * @param string $source_slug
     * @param string $dir          Destination directory.
     * @return string|WP_Error
     */
    private static function generate_unique_slug(string $source_slug, string $dir): string|WP_Error
    {
        $base = preg_replace('/-copy(-\d+)?$/', '', $source_slug);

        // Combine core and user file maps.
        $existing = AWB_Pattern_Loader::$pattern_files;

        for ($i = 1; $i <= self::MAX_COPY_ATTEMPTS; $i++) {
            $candidate = (1 === $i)
                ? $base . '-copy'
                : $base . '-copy-' . $i;

            // Check filesystem (user patterns dir).
            if (file_exists($dir . $candidate . '.php')) {
                continue;
            }

            // Check registry for both core and user patterns.
            if (array_key_exists('awb/' . $candidate, $existing)) {
                continue;
            }

            return $candidate;
        }

        return new WP_Error(
            'slug_exhausted',
            sprintf(
                /* translators: %s: base pattern slug */
                __('Could not generate a unique slug for "%s". All copy variants up to -copy-99 are taken.', 'awb-starter'),
                $base
            )
        );
    }

    // -------------------------------------------------------------------------
    // Title generation
    // -------------------------------------------------------------------------

    /**
     * Generate a clone title by appending ' (Copy)'.
     *
     * Strips any existing ' (Copy)' or ' (Copy N)' suffix first so that
     * cloning a clone always produces '{base} (Copy)' rather than a chain.
     *
     * @param string $source_title
     * @return string
     */
    private static function generate_title(string $source_title): string
    {
        // Strip existing (Copy) or (Copy N) suffix — handles clone-of-clone.
        $base = preg_replace('/\s+\(Copy(?:\s+\d+)?\)$/u', '', $source_title);

        return trim($base) . ' (Copy)';
    }

    // -------------------------------------------------------------------------
    // Header rewriting
    // -------------------------------------------------------------------------

    /**
     * Rewrite the Title and Slug lines inside the first PHP docblock of the file.
     *
     * Strategy:
     *   1. Locate the first /** ... *\/ docblock with a non-greedy DOTALL regex.
     *   2. Inside that docblock only, replace the ' * Title:' and ' * Slug:' lines.
     *   3. Splice the rewritten docblock back into the full file content.
     *
     * This is safe because:
     *   - Only the first docblock is modified — block content comes after '?>'
     *     and is never inside a docblock.
     *   - WordPress block comments (<!-- wp:... -->) never start with ' * ',
     *     so even if the regex somehow reached them it would not match.
     *
     * @param string $content    Raw file content.
     * @param string $new_title  New title string.
     * @param string $new_slug   New slug string (without 'awb/' prefix).
     * @return string|WP_Error   Rewritten content, or WP_Error if no docblock found.
     */
    private static function rewrite_headers(
        string $content,
        string $new_title,
        string $new_slug
    ): string|WP_Error {
        // Match the first /** ... */ block (non-greedy, across newlines).
        if (! preg_match('#(/\*\*.*?\*/)#s', $content, $matches, PREG_OFFSET_CAPTURE)) {
            return new WP_Error(
                'no_docblock',
                __('Pattern file does not contain a PHP docblock. Cannot rewrite headers.', 'awb-starter')
            );
        }

        $original_docblock = $matches[0][0];
        $offset            = $matches[0][1];

        // Rewrite Title: and Slug: lines inside the isolated docblock.
        $new_docblock = preg_replace(
            '/( \* Title:).*$/m',
            '$1 ' . $new_title,
            $original_docblock
        );
        $new_docblock = preg_replace(
            '/( \* Slug:).*$/m',
            '$1 ' . $new_slug,
            $new_docblock
        );

        // Splice the rewritten docblock back into the full content.
        return substr($content, 0, $offset)
            . $new_docblock
            . substr($content, $offset + strlen($original_docblock));
    }
}
