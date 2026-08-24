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
        add_action('wp_ajax_awb_test_ai_api',            [$this, 'test_ai_api']);
        add_action('wp_ajax_awb_save_ai_context',        [$this, 'save_ai_context']);
        add_action('wp_ajax_awb_save_header_footer',     [$this, 'save_header_footer']);
        add_action('wp_ajax_awb_export_pattern',         [$this, 'export_pattern']);
        add_action('wp_ajax_awb_import_pattern',         [$this, 'import_pattern']);
        add_action('wp_ajax_awb_duplicate_pattern',      [$this, 'duplicate_pattern']);
        add_action('wp_ajax_awb_install_remote_pattern', [$this, 'install_remote_pattern']);
        add_action('wp_ajax_awb_get_pattern_source',     [$this, 'get_pattern_source']);
        add_action('wp_ajax_awb_save_pattern_source',    [$this, 'save_pattern_source']);
        add_action('wp_ajax_awb_delete_pattern',         [$this, 'delete_pattern']);
        add_action('wp_ajax_awb_scaffold',               [$this, 'handle_scaffold']);
        add_action('wp_ajax_awb_ai_draft',               [$this, 'create_ai_draft']);
        add_action('wp_ajax_awb_preview_pattern',        [$this, 'preview_pattern']);
        add_action('wp_ajax_awb_dismiss_onboarding',     [$this, 'dismiss_onboarding']);
        add_action('wp_ajax_awb_store_manifest',         [$this, 'store_manifest']);
    }

    public function dismiss_onboarding(): void
    {
        check_ajax_referer('awb_dismiss_onboarding', 'nonce');
        if (! current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Unauthorized', 'awb-starter')], 403);
        }
        AWB_Onboarding::dismiss();
        wp_send_json_success();
    }

    public function handle_generate(): void
    {
        check_ajax_referer('awb_generate_nonce', 'nonce');
        if (! current_user_can('edit_posts')) {
            wp_send_json_error(['message' => __('Unauthorized', 'awb-starter')], 403);
        }
        $prompt = sanitize_textarea_field(wp_unslash($_POST['prompt'] ?? ''));
        if (empty($prompt)) {
            wp_send_json_error(['message' => __('No prompt provided.', 'awb-starter')]);
        }
        $options = [
            'mode'     => sanitize_key(wp_unslash($_POST['mode'] ?? 'blocks')),
            'tone'     => sanitize_key(wp_unslash($_POST['tone'] ?? '')),
            'template' => sanitize_title(wp_unslash($_POST['template'] ?? '')),
        ];
        $result = AWB_AI_Generator::generate($prompt, $options);
        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }
        wp_send_json_success(['blocks' => $result]);
    }

    public function handle_scaffold(): void
    {
        check_ajax_referer('awb_scaffold_nonce', 'nonce');
        if (! current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Unauthorized', 'awb-starter')], 403);
        }
        $type = sanitize_key(wp_unslash($_POST['scaffold'] ?? ''));
        if (! array_key_exists($type, AWB_Scaffold::definitions())) {
            wp_send_json_error(['message' => __('Unknown scaffold type.', 'awb-starter')], 400);
        }
        wp_send_json_success(['log' => AWB_Scaffold::run($type)]);
    }

    public function create_ai_draft(): void
    {
        check_ajax_referer('awb_ai_draft', 'nonce');
        if (! current_user_can('edit_pages')) {
            wp_send_json_error(['message' => __('Unauthorized', 'awb-starter')], 403);
        }
        // Block markup survives kses; raw scripts/styles are stripped for non-privileged users.
        $content = wp_kses_post(wp_unslash($_POST['content'] ?? ''));
        if (empty(trim($content))) {
            wp_send_json_error(['message' => __('No generated content to insert.', 'awb-starter')], 400);
        }
        $post_id = wp_insert_post([
            'post_title'   => __('AI Generated Section', 'awb-starter'),
            'post_content' => $content,
            'post_status'  => 'draft',
            'post_type'    => 'page',
        ]);
        if (is_wp_error($post_id)) {
            wp_send_json_error(['message' => $post_id->get_error_message()]);
        }
        wp_send_json_success(['edit_link' => (string) get_edit_post_link((int) $post_id, 'raw')]);
    }

    public function test_ai_api(): void
    {
        if (! current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Unauthorized', 'awb-starter')], 403);
        }
        $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
        if (! wp_verify_nonce($nonce, 'awb_test_ai_api')) {
            wp_send_json_error(['message' => __('Security check failed.', 'awb-starter')], 403);
        }
        $provider = isset($_POST['provider']) ? sanitize_text_field(wp_unslash($_POST['provider'])) : '';
        $providers = array_keys(AWB_AI_Generator::get_providers());
        if (empty($provider) || ! in_array($provider, $providers, true)) {
            wp_send_json_error(['message' => __('Invalid provider.', 'awb-starter')], 400);
        }
        $result = AWB_AI_Generator::verify_api_key($provider);
        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }
        wp_send_json_success(['message' => __('API key verified successfully.', 'awb-starter')]);
    }

    public function save_ai_context(): void
    {
        if (! current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Unauthorized', 'awb-starter')], 403);
        }
        $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
        if (! wp_verify_nonce($nonce, 'awb_save_ai_context_nonce')) {
            wp_send_json_error(['message' => __('Security check failed.', 'awb-starter')], 403);
        }
        $name = sanitize_text_field(wp_unslash($_POST['business_name'] ?? ''));
        $desc = sanitize_text_field(wp_unslash($_POST['business_desc'] ?? ''));
        update_option('awb_ai_business_name', $name);
        update_option('awb_ai_business_desc', $desc);
        wp_send_json_success(['message' => __('Business context saved.', 'awb-starter')]);
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
        if (! AWB_Store::is_allowed_download($url)) {
            wp_send_json_error(['message' => __('Patterns can only be installed from hosts configured in the Store settings.', 'awb-starter')]);
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

    public function store_manifest(): void
    {
        if (! current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Insufficient permissions.', 'awb-starter')], 403);
        }
        $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
        if (! wp_verify_nonce($nonce, 'awb_store_manifest')) {
            wp_send_json_error(['message' => __('Security check failed.', 'awb-starter')], 403);
        }
        $url = AWB_Store::get_manifest_url();
        if ('' === $url) {
            wp_send_json_error(['code' => 'not_configured', 'message' => __('No Store manifest URL is configured yet.', 'awb-starter')]);
        }
        if (! AWB_Store::is_allowed_download($url)) {
            wp_send_json_error(['code' => 'host_not_allowed', 'message' => __('The configured manifest URL points to a host that is not allowed.', 'awb-starter')]);
        }
        $response = wp_remote_get($url, ['timeout' => 15]);
        if (is_wp_error($response)) {
            wp_send_json_error(['message' => __('Could not reach the pattern store: ', 'awb-starter') . $response->get_error_message()]);
        }
        $code = (int) wp_remote_retrieve_response_code($response);
        if ($code < 200 || $code >= 300) {
            wp_send_json_error(['message' => sprintf(__('Pattern store returned HTTP %d.', 'awb-starter'), $code)]);
        }
        $body  = json_decode(wp_remote_retrieve_body($response), true);
        $items = is_array($body['patterns'] ?? null) ? $body['patterns'] : [];
        // Only pass through safe scalar fields to the browser.
        $patterns = [];
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }
            $download = esc_url_raw((string) ($item['download_url'] ?? ''));
            if ('' === $download) {
                continue;
            }
            $patterns[] = [
                'title'        => sanitize_text_field((string) ($item['title'] ?? '')),
                'description'  => sanitize_text_field((string) ($item['description'] ?? '')),
                'version'      => sanitize_text_field((string) ($item['version'] ?? '')),
                'author'       => sanitize_text_field((string) ($item['author'] ?? '')),
                'thumbnail'    => esc_url_raw((string) ($item['thumbnail'] ?? '')),
                'download_url' => $download,
            ];
        }
        wp_send_json_success(['patterns' => $patterns]);
    }

    public function preview_pattern(): void
    {
        if (! current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Unauthorized', 'awb-starter')], 403);
        }
        $nonce = isset($_GET['nonce']) ? sanitize_text_field(wp_unslash($_GET['nonce'])) : '';
        if (! wp_verify_nonce($nonce, 'awb_edit_pattern')) {
            wp_send_json_error(['message' => __('Security check failed.', 'awb-starter')], 403);
        }
        $pattern_name = sanitize_text_field(wp_unslash($_GET['pattern'] ?? ''));
        if (empty($pattern_name) || strpos($pattern_name, 'awb/') !== 0) {
            wp_send_json_error(['message' => __('Invalid pattern', 'awb-starter')], 400);
        }
        if (! class_exists('WP_Block_Patterns_Registry')) {
            wp_send_json_error(['message' => __('Pattern registry unavailable.', 'awb-starter')], 500);
        }
        $pattern = WP_Block_Patterns_Registry::get_instance()->get_registered($pattern_name);
        if (! is_array($pattern) || empty($pattern['content'])) {
            wp_send_json_error(['message' => __('Pattern content not found.', 'awb-starter')], 404);
        }

        // Collect the stylesheets the frontend would load for this pattern.
        $css_urls = [];
        $frontend = AWB_PLUGIN_URL . 'assets/css/frontend.css';
        if (file_exists(AWB_PLUGIN_PATH . 'assets/css/frontend.css')) {
            $css_urls[] = $frontend;
        }
        $assets   = AWB_Pattern_Loader::$pattern_assets[$pattern_name] ?? [];
        $source   = $assets['source'] ?? 'core';
        $base_url = ('user' === $source) ? AWB_USER_PATTERNS_URL : AWB_PLUGIN_URL;
        $base_path = ('user' === $source) ? AWB_USER_PATTERNS_PATH : AWB_PLUGIN_PATH;
        foreach (['css'] as $key) {
            if (! empty($assets[$key])) {
                $abs = $base_path . ltrim($assets[$key], '/');
                if (file_exists($abs)) {
                    $css_urls[] = $base_url . ltrim($assets[$key], '/');
                }
            }
        }

        wp_send_json_success([
            'title'   => $pattern['title'] ?? '',
            'content' => $pattern['content'],
            'css'     => $css_urls,
            'tokens'  => AWB_Asset_Loader::generate_design_tokens_css(),
        ]);
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
        // Read asset paths directly from the pattern header to avoid URL string replacement issues.
        $meta = get_file_data($file_path, ['css' => 'CSS', 'js' => 'JS']);
        $base_path = trailingslashit(AWB_USER_PATTERNS_PATH);
        if (! empty($meta['css'])) {
            $css_path = $base_path . ltrim($meta['css'], '/');
            if (file_exists($css_path) && $this->is_path_within($css_path, AWB_USER_PATTERNS_PATH)) {
                $content = file_get_contents($css_path);
                if (false !== $content) {
                    $files['css'] = ['content' => $content, 'label' => 'CSS', 'mode' => 'text/css'];
                }
            }
        }
        if (! empty($meta['js'])) {
            $js_path = $base_path . ltrim($meta['js'], '/');
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
        // CRITICAL: Unslash to preserve quotes/apostrophes in code.
        $files_data = wp_unslash($_POST['files'] ?? []);
        if (! is_array($files_data)) {
            wp_send_json_error(['message' => __('Invalid files data.', 'awb-starter')], 400);
        }
        // Initialise WP_Filesystem; fall back to direct PHP if unavailable.
        global $wp_filesystem;
        $fs_ok = false;
        if (! empty($wp_filesystem) && is_object($wp_filesystem)) {
            $fs_ok = true;
        } else {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            $fs_ok = WP_Filesystem();
        }
        $php_path = AWB_Pattern_Loader::$pattern_files[$pattern_name] ?? '';
        // Read header to resolve asset paths safely without URL string replacement.
        $meta = get_file_data($php_path, ['css' => 'CSS', 'js' => 'JS']);
        $base_path = trailingslashit(AWB_USER_PATTERNS_PATH);
        $saved = 0;

        // Helper to write and invalidate OPcache.
        $write_file = function ($path, $content) use ($wp_filesystem, $fs_ok) {
            if (empty($path) || empty($content)) return false;
            $success = $fs_ok
                ? $wp_filesystem->put_contents($path, $content, FS_CHMOD_FILE)
                : (file_put_contents($path, $content) !== false);
            if ($success && function_exists('opcache_invalidate')) {
                opcache_invalidate($path, true);
            }
            return $success;
        };

        // Save PHP
        if (isset($files_data['php']) && $this->is_path_within($php_path, AWB_USER_PATTERNS_PATH)) {
            if ($write_file($php_path, $files_data['php'])) $saved++;
        }
        // Save CSS
        if (isset($files_data['css']) && ! empty($meta['css'])) {
            $css_path = $base_path . ltrim($meta['css'], '/');
            if ($this->is_path_within($css_path, AWB_USER_PATTERNS_PATH)) {
                if ($write_file($css_path, $files_data['css'])) $saved++;
            }
        }
        // Save JS
        if (isset($files_data['js']) && ! empty($meta['js'])) {
            $js_path = $base_path . ltrim($meta['js'], '/');
            if ($this->is_path_within($js_path, AWB_USER_PATTERNS_PATH)) {
                if ($write_file($js_path, $files_data['js'])) $saved++;
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
        // Initialise WP_Filesystem; fall back to direct PHP if unavailable.
        global $wp_filesystem;
        $fs_ok = false;
        if (! empty($wp_filesystem) && is_object($wp_filesystem)) {
            $fs_ok = true;
        } else {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            $fs_ok = WP_Filesystem();
        }
        $deleted = 0;
        $delete_file = function ($path) use ($wp_filesystem, $fs_ok) {
            return $fs_ok
                ? ($wp_filesystem->exists($path) && $wp_filesystem->delete($path))
                : (file_exists($path) && unlink($path));
        };
        if ($delete_file($php_path)) {
            $deleted++;
        }
        $meta = get_file_data($php_path, ['css' => 'CSS', 'js' => 'JS']);
        $base_path = trailingslashit(AWB_USER_PATTERNS_PATH);
        if (! empty($meta['css'])) {
            $css_path = $base_path . ltrim($meta['css'], '/');
            if ($this->is_path_within($css_path, AWB_USER_PATTERNS_PATH) && $delete_file($css_path)) {
                $deleted++;
            }
        }
        if (! empty($meta['js'])) {
            $js_path = $base_path . ltrim($meta['js'], '/');
            if ($this->is_path_within($js_path, AWB_USER_PATTERNS_PATH) && $delete_file($js_path)) {
                $deleted++;
            }
        }
        if ($deleted === 0) {
            wp_send_json_error(['message' => __('Failed to delete pattern files.', 'awb-starter')], 500);
        }
        wp_send_json_success(['message' => __('Pattern deleted successfully.', 'awb-starter')]);
    }
}
