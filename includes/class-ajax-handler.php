<?php

/**
 * AJAX endpoint registration.
 *
 * All wp_ajax_awb_* actions are registered here. Each handler method is
 * responsible for its own capability check and nonce verification before
 * delegating to the appropriate domain class.
 *
 * @package AWBStarter
 */

if (! defined('ABSPATH')) {
    exit;
}

class AWB_Ajax_Handler
{
    public function __construct()
    {
        // AI content generation.
        add_action('wp_ajax_awb_generate', [$this, 'handle_generate']);

        // Header / footer switcher settings save.
        add_action('wp_ajax_awb_save_header_footer', [$this, 'save_header_footer']);

        // Pattern export — streams a ZIP download, must be a GET request.
        add_action('wp_ajax_awb_export_pattern', [$this, 'export_pattern']);

        // Pattern import — accepts a ZIP upload, validates, writes to disk.
        add_action('wp_ajax_awb_import_pattern', [$this, 'import_pattern']);

        // Pattern duplication — clones a pattern file with a new slug.
        add_action('wp_ajax_awb_duplicate_pattern', [$this, 'duplicate_pattern']);

        // Remote pattern installation from store.
        add_action('wp_ajax_awb_install_remote_pattern', [$this, 'install_remote_pattern']);

        // Pattern editing — get and save source.
        add_action('wp_ajax_awb_get_pattern_source', [$this, 'get_pattern_source']);
        add_action('wp_ajax_awb_save_pattern_source', [$this, 'save_pattern_source']);
    }

    // -------------------------------------------------------------------------
    // AI generator
    // -------------------------------------------------------------------------

    public function handle_generate(): void
    {
        check_ajax_referer('awb_generate_nonce', 'nonce');

        if (! current_user_can('edit_posts')) {
            wp_send_json_error(['message' => 'Unauthorized'], 403);
        }

        $prompt = sanitize_textarea_field(wp_unslash($_POST['prompt'] ?? ''));
        if (empty($prompt)) {
            wp_send_json_error(['message' => 'No prompt provided.']);
        }

        $result = AWB_AI_Generator::generate($prompt);
        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }

        wp_send_json_success(['blocks' => $result]);
    }

    // -------------------------------------------------------------------------
    // Header / footer switcher
    // -------------------------------------------------------------------------

    /**
     * AJAX: Save header/footer switcher settings.
     *
     * POST params:
     *   nonce        string  WordPress nonce (action: awb_save_header_footer)
     *   header_type  string  'none' | 'pattern' | 'block'
     *   header_value string  Pattern name or reusable-block post ID
     *   footer_type  string  'none' | 'pattern' | 'block'
     *   footer_value string  Pattern name or reusable-block post ID
     */
    public function save_header_footer(): void
    {
        if (! current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions.', 'awb-starter'), 403);
        }

        $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
        if (! wp_verify_nonce($nonce, 'awb_save_header_footer')) {
            wp_send_json_error(__('Security check failed.', 'awb-starter'), 403);
        }

        $result = AWB_Header_Switcher::save_settings($_POST);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success(__('Settings saved.', 'awb-starter'));
    }

    // -------------------------------------------------------------------------
    // Pattern import
    // -------------------------------------------------------------------------

    /**
     * AJAX: Import a pattern from an uploaded ZIP file.
     *
     * This is a multipart POST request (file upload). Capability and nonce
     * are checked here; all file validation and writing is delegated to
     * AWB_Pattern_Importer::handle_upload().
     *
     * POST fields:
     *   nonce           string  WordPress nonce (action: awb_import_pattern)
     *   awb_pattern_zip file    The ZIP archive to import
     *   force           string  '1' = confirmed overwrite of existing files
     */
    public function import_pattern(): void
    {
        // Capability check.
        if (! current_user_can('manage_options')) {
            wp_send_json_error([
                'code'    => 'error',
                'message' => __('You do not have permission to import patterns.', 'awb-starter'),
            ], 403);
        }

        // Nonce verification.
        $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
        if (! wp_verify_nonce($nonce, 'awb_import_pattern')) {
            wp_send_json_error([
                'code'    => 'error',
                'message' => __('Security check failed. Please refresh the page and try again.', 'awb-starter'),
            ], 403);
        }

        // Delegate all validation and file writing to the Importer.
        AWB_Pattern_Importer::handle_upload();
    }

    /**
     * AJAX: Export a single pattern as a ZIP download.
     *
     * This is a GET action — the browser navigates directly to the URL so it
     * receives the binary ZIP stream and triggers a Save As dialog.
     * wp_send_json_* must NOT be called; AWB_Pattern_Exporter::stream() exits.
     *
     * GET params:
     *   nonce    string  WordPress nonce (action: awb_export_pattern)
     *   pattern  string  Full registered pattern name, e.g. 'awb/header-dark'
     */
    public function export_pattern(): void
    {
        // Capability check.
        if (! current_user_can('manage_options')) {
            wp_die(
                esc_html__('You do not have permission to export patterns.', 'awb-starter'),
                esc_html__('Permission Denied', 'awb-starter'),
                ['response' => 403]
            );
        }

        // Nonce verification — passed as GET param because this is a direct navigation.
        $nonce = isset($_GET['nonce']) ? sanitize_text_field(wp_unslash($_GET['nonce'])) : '';
        if (! wp_verify_nonce($nonce, 'awb_export_pattern')) {
            wp_die(
                esc_html__('Security check failed. Please refresh the page and try again.', 'awb-starter'),
                esc_html__('Security Error', 'awb-starter'),
                ['response' => 403]
            );
        }

        // Sanitize and validate the pattern name.
        $raw_name = isset($_GET['pattern']) ? sanitize_text_field(wp_unslash($_GET['pattern'])) : '';

        if (empty($raw_name)) {
            wp_die(
                esc_html__('No pattern specified.', 'awb-starter'),
                esc_html__('Export Error', 'awb-starter'),
                ['response' => 400]
            );
        }

        // Only allow patterns registered by this plugin (must start with 'awb/').
        if (strpos($raw_name, 'awb/') !== 0) {
            wp_die(
                esc_html__('Only AWB patterns can be exported.', 'awb-starter'),
                esc_html__('Export Error', 'awb-starter'),
                ['response' => 400]
            );
        }

        // Delegate to the Exporter — this call never returns (exits after stream).
        AWB_Pattern_Exporter::stream($raw_name);
    }
    // -------------------------------------------------------------------------
    // Pattern duplication
    // -------------------------------------------------------------------------

    /**
     * AJAX: Duplicate a registered AWB pattern.
     *
     * Clones the pattern PHP file, generates a unique slug, and rewrites
     * the Title/Slug header comments in the clone. Returns the new pattern
     * details so the UI can display a confirmation message.
     *
     * POST params:
     *   nonce    string  WordPress nonce (action: awb_duplicate_pattern)
     *   pattern  string  Full registered pattern name, e.g. 'awb/header-dark'
     */
    public function duplicate_pattern(): void
    {
        // Capability check.
        if (! current_user_can('manage_options')) {
            wp_send_json_error([
                'message' => __('You do not have permission to duplicate patterns.', 'awb-starter'),
            ], 403);
        }

        // Nonce verification.
        $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
        if (! wp_verify_nonce($nonce, 'awb_duplicate_pattern')) {
            wp_send_json_error([
                'message' => __('Security check failed. Please refresh the page and try again.', 'awb-starter'),
            ], 403);
        }

        // Sanitize and validate the pattern name.
        $raw_name = isset($_POST['pattern']) ? sanitize_text_field(wp_unslash($_POST['pattern'])) : '';

        if (empty($raw_name)) {
            wp_send_json_error([
                'message' => __('No pattern specified.', 'awb-starter'),
            ], 400);
        }

        // Enforce 'awb/' prefix — guard against duplicating third-party patterns.
        if (strpos($raw_name, 'awb/') !== 0) {
            wp_send_json_error([
                'message' => __('Only AWB patterns can be duplicated.', 'awb-starter'),
            ], 400);
        }

        // Delegate to the Duplicator.
        $result = AWB_Pattern_Duplicator::duplicate($raw_name);

        if (is_wp_error($result)) {
            wp_send_json_error([
                'message' => $result->get_error_message(),
            ]);
        }

        wp_send_json_success([
            'new_registered_name' => $result['new_registered_name'],
            'new_slug'            => $result['new_slug'],
            'new_title'           => $result['new_title'],
            'message'             => sprintf(
                /* translators: %s: new pattern title */
                __('Pattern duplicated as "%s". Reload the page to see it in the library.', 'awb-starter'),
                $result['new_title']
            ),
        ]);
    }

    // -------------------------------------------------------------------------
    // Remote pattern installation
    // -------------------------------------------------------------------------

    /**
     * Download and install a pattern from a remote URL.
     *
     * POST params:
     *   nonce    string  WordPress nonce (action: awb_install_remote_pattern)
     *   url      string  URL to the pattern ZIP file.
     */
    public function install_remote_pattern(): void
    {
        // Capability check.
        if (! current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Insufficient permissions.', 'awb-starter')], 403);
        }

        // Nonce verification.
        $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
        if (! wp_verify_nonce($nonce, 'awb_install_remote_pattern')) {
            wp_send_json_error(['message' => __('Security check failed.', 'awb-starter')], 403);
        }

        $url = esc_url_raw(wp_unslash($_POST['url'] ?? ''));
        if (empty($url)) {
            wp_send_json_error(['message' => __('No URL provided.', 'awb-starter')]);
        }

        // Optional: restrict to trusted domain.
        $allowed_host = parse_url('https://your-trusted-domain.com', PHP_URL_HOST);
        if (parse_url($url, PHP_URL_HOST) !== $allowed_host) {
            wp_send_json_error(['message' => __('Patterns can only be installed from the official repository.', 'awb-starter')]);
        }

        // Download the ZIP to a temporary file.
        require_once ABSPATH . 'wp-admin/includes/file.php';
        $tmp_file = download_url($url, 30); // 30-second timeout.

        if (is_wp_error($tmp_file)) {
            wp_send_json_error(['message' => $tmp_file->get_error_message()]);
        }

        // Use the importer.
        $result = AWB_Pattern_Importer::install_from_zip($tmp_file, false);

        // Clean up temp file.
        @unlink($tmp_file);

        if ($result['success']) {
            wp_send_json_success($result['data']);
        } else {
            $error_data = ['message' => $result['error'] ?? __('Installation failed.', 'awb-starter')];
            if (isset($result['collision'])) {
                $error_data['code']  = 'collision';
                $error_data['title'] = $result['title'];
                $error_data['slug']  = $result['slug'];
                $error_data['files'] = $result['files'];
            }
            wp_send_json_error($error_data);
        }
    }

    // -------------------------------------------------------------------------
    // Pattern editing
    // -------------------------------------------------------------------------

    private function is_path_within(string $path, string $root): bool
    {
        $norm_path = wp_normalize_path($path);
        $norm_root = wp_normalize_path(trailingslashit($root));
        return str_starts_with($norm_path, $norm_root);
    }

    public function get_pattern_source(): void
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Unauthorized', 'awb-starter')], 403);
        }

        $nonce = $_GET['nonce'] ?? '';
        if (!wp_verify_nonce($nonce, 'awb_edit_pattern')) {
            wp_send_json_error(['message' => __('Security check failed', 'awb-starter')], 403);
        }

        $pattern_name = sanitize_text_field($_GET['pattern'] ?? '');
        if (empty($pattern_name) || strpos($pattern_name, 'awb/') !== 0) {
            wp_send_json_error(['message' => __('Invalid pattern', 'awb-starter')], 400);
        }

        $source = AWB_Pattern_Loader::$pattern_source[$pattern_name] ?? 'core';
        if ($source !== 'user') {
            wp_send_json_error(['message' => __('This pattern cannot be edited.', 'awb-starter')], 403);
        }

        $file_path = AWB_Pattern_Loader::$pattern_files[$pattern_name] ?? '';
        if (!$file_path || !file_exists($file_path)) {
            wp_send_json_error(['message' => __('Pattern file not found.', 'awb-starter')], 404);
        }

        if (!$this->is_path_within($file_path, AWB_USER_PATTERNS_PATH)) {
            wp_send_json_error(['message' => __('Security violation.', 'awb-starter')], 403);
        }

        $content = file_get_contents($file_path);
        if (false === $content) {
            wp_send_json_error(['message' => __('Could not read file.', 'awb-starter')], 500);
        }

        wp_send_json_success(['content' => $content]);
    }

    public function save_pattern_source(): void
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Unauthorized', 'awb-starter')], 403);
        }

        $nonce = $_POST['nonce'] ?? '';
        if (!wp_verify_nonce($nonce, 'awb_edit_pattern')) {
            wp_send_json_error(['message' => __('Security check failed', 'awb-starter')], 403);
        }

        $pattern_name = sanitize_text_field($_POST['pattern'] ?? '');
        $new_content  = $_POST['content'] ?? '';

        if (empty($pattern_name) || strpos($pattern_name, 'awb/') !== 0) {
            wp_send_json_error(['message' => __('Invalid pattern', 'awb-starter')], 400);
        }

        $source = AWB_Pattern_Loader::$pattern_source[$pattern_name] ?? 'core';
        if ($source !== 'user') {
            wp_send_json_error(['message' => __('This pattern cannot be edited.', 'awb-starter')], 403);
        }

        $file_path = AWB_Pattern_Loader::$pattern_files[$pattern_name] ?? '';
        if (!$file_path || !file_exists($file_path)) {
            wp_send_json_error(['message' => __('Pattern file not found.', 'awb-starter')], 404);
        }

        if (!$this->is_path_within($file_path, AWB_USER_PATTERNS_PATH)) {
            wp_send_json_error(['message' => __('Security violation.', 'awb-starter')], 403);
        }

        global $wp_filesystem;
        if (empty($wp_filesystem)) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            WP_Filesystem();
        }

        if (!$wp_filesystem->put_contents($file_path, $new_content, FS_CHMOD_FILE)) {
            wp_send_json_error(['message' => __('Could not write file.', 'awb-starter')], 500);
        }

        wp_send_json_success(['message' => __('Pattern saved.', 'awb-starter')]);
    }
}
