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
        add_action('wp_ajax_awb_generate',               [$this, 'handle_generate']);
        add_action('wp_ajax_awb_save_header_footer',     [$this, 'save_header_footer']);
        add_action('wp_ajax_awb_export_pattern',         [$this, 'export_pattern']);
        add_action('wp_ajax_awb_import_pattern',         [$this, 'import_pattern']);
        add_action('wp_ajax_awb_duplicate_pattern',      [$this, 'duplicate_pattern']);
        add_action('wp_ajax_awb_install_remote_pattern', [$this, 'install_remote_pattern']);
        add_action('wp_ajax_awb_get_pattern_source',     [$this, 'get_pattern_source']);
        add_action('wp_ajax_awb_save_pattern_source',    [$this, 'save_pattern_source']);
        add_action('wp_ajax_awb_delete_pattern',         [$this, 'delete_pattern']);
    }

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

    public function import_pattern(): void
    {
        if (! current_user_can('manage_options')) {
            wp_send_json_error(['code' => 'error', 'message' => __('You do not have permission to import patterns.', 'awb-starter')], 403);
        }
        $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
        if (! wp_verify_nonce($nonce, 'awb_import_pattern')) {
            wp_send_json_error(['code' => 'error', 'message' => __('Security check failed. Please refresh the page and try again.', 'awb-starter')], 403);
        }
        AWB_Pattern_Importer::handle_upload();
    }

    public function export_pattern(): void
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to export patterns.', 'awb-starter'), esc_html__('Permission Denied', 'awb-starter'), ['response' => 403]);
        }
        $nonce = isset($_GET['nonce']) ? sanitize_text_field(wp_unslash($_GET['nonce'])) : '';
        if (! wp_verify_nonce($nonce, 'awb_export_pattern')) {
            wp_die(esc_html__('Security check failed. Please refresh the page and try again.', 'awb-starter'), esc_html__('Security Error', 'awb-starter'), ['response' => 403]);
        }
        $raw_name = isset($_GET['pattern']) ? sanitize_text_field(wp_unslash($_GET['pattern'])) : '';
        if (empty($raw_name)) {
            wp_die(esc_html__('No pattern specified.', 'awb-starter'), esc_html__('Export Error', 'awb-starter'), ['response' => 400]);
        }
        if (strpos($raw_name, 'awb/') !== 0) {
            wp_die(esc_html__('Only AWB patterns can be exported.', 'awb-starter'), esc_html__('Export Error', 'awb-starter'), ['response' => 400]);
        }
        AWB_Pattern_Exporter::stream($raw_name);
    }

    public function duplicate_pattern(): void
    {
        if (! current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('You do not have permission to duplicate patterns.', 'awb-starter')], 403);
        }
        $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
        if (! wp_verify_nonce($nonce, 'awb_duplicate_pattern')) {
            wp_send_json_error(['message' => __('Security check failed. Please refresh the page and try again.', 'awb-starter')], 403);
        }
        $raw_name = isset($_POST['pattern']) ? sanitize_text_field(wp_unslash($_POST['pattern'])) : '';
        if (empty($raw_name)) {
            wp_send_json_error(['message' => __('No pattern specified.', 'awb-starter')], 400);
        }
        if (strpos($raw_name, 'awb/') !== 0) {
            wp_send_json_error(['message' => __('Only AWB patterns can be duplicated.', 'awb-starter')], 400);
        }
        $result = AWB_Pattern_Duplicator::duplicate($raw_name);
        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }
        wp_send_json_success([
            'new_registered_name' => $result['new_registered_name'],
            'new_slug'            => $result['new_slug'],
            'new_title'           => $result['new_title'],
            'message'             => sprintf(__('Pattern duplicated as "%s". Reload the page to see it in the library.', 'awb-starter'), $result['new_title']),
        ]);
    }

    public function install_remote_pattern(): void
    {
        if (! current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Insufficient permissions.', 'awb-starter')], 403);
        }
        $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
        if (! wp_verify_nonce($nonce, 'awb_install_remote_pattern')) {
            wp_send_json_error(['message' => __('Security check failed.', 'awb-starter')], 403);
        }
        $url = esc_url_raw(wp_unslash($_POST['url'] ?? ''));
        if (empty($url)) {
            wp_send_json_error(['message' => __('No URL provided.', 'awb-starter')]);
        }
        $allowed_host = parse_url('https://your-trusted-domain.com', PHP_URL_HOST);
        if (parse_url($url, PHP_URL_HOST) !== $allowed_host) {
            wp_send_json_error(['message' => __('Patterns can only be installed from the official repository.', 'awb-starter')]);
        }
        require_once ABSPATH . 'wp-admin/includes/file.php';
        $tmp_file = download_url($url, 30);
        if (is_wp_error($tmp_file)) {
            wp_send_json_error(['message' => $tmp_file->get_error_message()]);
        }
        $result = AWB_Pattern_Importer::install_from_zip($tmp_file, false);
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

    private function is_path_within(string $path, string $root): bool
    {
        $norm_path = wp_normalize_path($path);
        $norm_root = wp_normalize_path(trailingslashit($root));
        return str_starts_with($norm_path, $norm_root);
    }

    public function get_pattern_source(): void
    {
        if (! current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Unauthorized', 'awb-starter')], 403);
        }
        $nonce = isset($_GET['nonce']) ? wp_unslash($_GET['nonce']) : '';
        if (! wp_verify_nonce($nonce, 'awb_edit_pattern')) {
            wp_send_json_error(['message' => __('Security check failed', 'awb-starter')], 403);
        }
        $pattern_name = sanitize_text_field(isset($_GET['pattern']) ? wp_unslash($_GET['pattern']) : '');
        if (empty($pattern_name) || strpos($pattern_name, 'awb/') !== 0) {
            wp_send_json_error(['message' => __('Invalid pattern', 'awb-starter')], 400);
        }
        $source = AWB_Pattern_Loader::$pattern_source[$pattern_name] ?? 'core';
        if ($source !== 'user') {
            wp_send_json_error(['message' => __('This pattern cannot be edited.', 'awb-starter')], 403);
        }
        $files = [];
        $file_path = AWB_Pattern_Loader::$pattern_files[$pattern_name] ?? '';
        if ($file_path && file_exists($file_path) && $this->is_path_within($file_path, AWB_USER_PATTERNS_PATH)) {
            $content = file_get_contents($file_path);
            if (false !== $content) {
                $files['php'] = ['content' => $content, 'label' => 'PHP', 'mode' => 'application/x-httpd-php'];
            }
        }
        $assets    = AWB_Pattern_Loader::$pattern_assets[$pattern_name] ?? [];
        $base_path = trailingslashit(AWB_USER_PATTERNS_PATH);
        if (! empty($assets['css'])) {
            $css_path = $base_path . ltrim($assets['css'], '/');
            if (file_exists($css_path) && $this->is_path_within($css_path, AWB_USER_PATTERNS_PATH)) {
                $content = file_get_contents($css_path);
                if (false !== $content) {
                    $files['css'] = ['content' => $content, 'label' => 'CSS', 'mode' => 'text/css'];
                }
            }
        }
        if (! empty($assets['js'])) {
            $js_path = $base_path . ltrim($assets['js'], '/');
            if (file_exists($js_path) && $this->is_path_within($js_path, AWB_USER_PATTERNS_PATH)) {
                $content = file_get_contents($js_path);
                if (false !== $content) {
                    $files['js'] = ['content' => $content, 'label' => 'JavaScript', 'mode' => 'text/javascript'];
                }
            }
        }
        if (empty($files)) {
            wp_send_json_error(['message' => __('No editable files found.', 'awb-starter')], 404);
        }
        wp_send_json_success(['files' => $files]);
    }

    public function save_pattern_source(): void
    {
        if (! current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Unauthorized', 'awb-starter')], 403);
        }
        $nonce = isset($_POST['nonce']) ? wp_unslash($_POST['nonce']) : '';
        if (! wp_verify_nonce($nonce, 'awb_edit_pattern')) {
            wp_send_json_error(['message' => __('Security check failed', 'awb-starter')], 403);
        }
        $pattern_name = sanitize_text_field(isset($_POST['pattern']) ? wp_unslash($_POST['pattern']) : '');
        if (empty($pattern_name) || strpos($pattern_name, 'awb/') !== 0) {
            wp_send_json_error(['message' => __('Invalid pattern', 'awb-starter')], 400);
        }
        $source = AWB_Pattern_Loader::$pattern_source[$pattern_name] ?? 'core';
        if ($source !== 'user') {
            wp_send_json_error(['message' => __('This pattern cannot be edited.', 'awb-starter')], 403);
        }
        $files_data = isset($_POST['files']) ? wp_unslash($_POST['files']) : [];
        if (! is_array($files_data)) {
            wp_send_json_error(['message' => __('Invalid files data.', 'awb-starter')], 400);
        }
        global $wp_filesystem;
        if (empty($wp_filesystem)) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            WP_Filesystem();
        }
        $base_path = trailingslashit(AWB_USER_PATTERNS_PATH);
        $assets    = AWB_Pattern_Loader::$pattern_assets[$pattern_name] ?? [];
        $saved     = 0;
        if (isset($files_data['php'])) {
            $php_path = AWB_Pattern_Loader::$pattern_files[$pattern_name] ?? '';
            if ($php_path && $this->is_path_within($php_path, AWB_USER_PATTERNS_PATH)) {
                if ($wp_filesystem->put_contents($php_path, $files_data['php'], FS_CHMOD_FILE)) $saved++;
            }
        }
        if (isset($files_data['css']) && ! empty($assets['css'])) {
            $css_path = $base_path . ltrim($assets['css'], '/');
            if ($this->is_path_within($css_path, AWB_USER_PATTERNS_PATH)) {
                if ($wp_filesystem->put_contents($css_path, $files_data['css'], FS_CHMOD_FILE)) $saved++;
            }
        }
        if (isset($files_data['js']) && ! empty($assets['js'])) {
            $js_path = $base_path . ltrim($assets['js'], '/');
            if ($this->is_path_within($js_path, AWB_USER_PATTERNS_PATH)) {
                if ($wp_filesystem->put_contents($js_path, $files_data['js'], FS_CHMOD_FILE)) $saved++;
            }
        }
        if ($saved === 0) {
            wp_send_json_error(['message' => __('No files were saved.', 'awb-starter')], 500);
        }
        wp_send_json_success(['message' => __('Pattern files saved.', 'awb-starter')]);
    }

    public function delete_pattern(): void
    {
        if (! current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Unauthorized', 'awb-starter')], 403);
        }
        $nonce = isset($_POST['nonce']) ? wp_unslash($_POST['nonce']) : '';
        if (! wp_verify_nonce($nonce, 'awb_delete_pattern')) {
            wp_send_json_error(['message' => __('Security check failed', 'awb-starter')], 403);
        }
        $pattern_name = sanitize_text_field(isset($_POST['pattern']) ? wp_unslash($_POST['pattern']) : '');
        if (empty($pattern_name) || strpos($pattern_name, 'awb/') !== 0) {
            wp_send_json_error(['message' => __('Invalid pattern', 'awb-starter')], 400);
        }
        $source = AWB_Pattern_Loader::$pattern_source[$pattern_name] ?? 'core';
        if ($source !== 'user') {
            wp_send_json_error(['message' => __('Only user patterns can be deleted.', 'awb-starter')], 403);
        }
        $php_path = AWB_Pattern_Loader::$pattern_files[$pattern_name] ?? '';
        if (! $php_path || ! $this->is_path_within($php_path, AWB_USER_PATTERNS_PATH)) {
            wp_send_json_error(['message' => __('Pattern file not found or path invalid.', 'awb-starter')], 404);
        }
        global $wp_filesystem;
        if (empty($wp_filesystem)) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            WP_Filesystem();
        }
        $deleted = 0;
        if ($wp_filesystem->exists($php_path) && $wp_filesystem->delete($php_path)) {
            $deleted++;
        }
        $assets    = AWB_Pattern_Loader::$pattern_assets[$pattern_name] ?? [];
        $base_path = trailingslashit(AWB_USER_PATTERNS_PATH);
        if (! empty($assets['css'])) {
            $css_path = $base_path . ltrim($assets['css'], '/');
            if ($this->is_path_within($css_path, AWB_USER_PATTERNS_PATH) && $wp_filesystem->exists($css_path) && $wp_filesystem->delete($css_path)) {
                $deleted++;
            }
        }
        if (! empty($assets['js'])) {
            $js_path = $base_path . ltrim($assets['js'], '/');
            if ($this->is_path_within($js_path, AWB_USER_PATTERNS_PATH) && $wp_filesystem->exists($js_path) && $wp_filesystem->delete($js_path)) {
                $deleted++;
            }
        }
        if ($deleted === 0) {
            wp_send_json_error(['message' => __('Failed to delete pattern files.', 'awb-starter')], 500);
        }
        wp_send_json_success(['message' => __('Pattern deleted successfully.', 'awb-starter')]);
    }
}
