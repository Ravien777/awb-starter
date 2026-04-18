<?php

/**
 * Asset loading for frontend, admin, and editor screens.
 *
 * @package AWBStarter
 */
if (! defined('ABSPATH')) {
    exit;
}

class AWB_Asset_Loader
{
    public function __construct()
    {
        add_action('wp_enqueue_scripts',    [$this, 'enqueue_frontend_assets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('enqueue_block_assets',  [$this, 'enqueue_editor_assets']);
    }

    private function enqueue_style(string $handle, string $rel_path, array $deps = []): bool
    {
        $abs = AWB_PLUGIN_PATH . $rel_path;
        if (! file_exists($abs)) {
            return false;
        }
        wp_enqueue_style($handle, AWB_PLUGIN_URL . $rel_path, $deps, filemtime($abs));
        return true;
    }

    private function enqueue_script(string $handle, string $rel_path, array $deps = [], bool $in_footer = true): bool
    {
        $abs = AWB_PLUGIN_PATH . $rel_path;
        if (! file_exists($abs)) {
            return false;
        }
        wp_enqueue_script($handle, AWB_PLUGIN_URL . $rel_path, $deps, filemtime($abs), $in_footer);
        return true;
    }

    public function enqueue_frontend_assets(): void
    {
        $style_enqueued  = $this->enqueue_style('awb-starter', 'assets/css/frontend.css');
        $script_enqueued = $this->enqueue_script('awb-starter', 'assets/js/frontend.js');
        if ($style_enqueued) {
            $tokens_css = $this->generate_design_tokens_css();
            if ($tokens_css) wp_add_inline_style('awb-starter', $tokens_css);
            $custom_css = get_option('awb_custom_css', '');
            if ($custom_css) wp_add_inline_style('awb-starter', wp_strip_all_tags($custom_css));
        }
        if ($script_enqueued) {
            $custom_js = get_option('awb_custom_js', '');
            if ($custom_js) wp_add_inline_script('awb-starter', wp_strip_all_tags($custom_js));
        }
        $this->enqueue_pattern_assets();
    }

    private function enqueue_pattern_assets(): void
    {
        if (empty(AWB_Pattern_Loader::$pattern_assets)) return;
        $post    = get_post();
        $content = $post ? $post->post_content : '';
        foreach (AWB_Pattern_Loader::$pattern_assets as $slug => $data) {
            $short_slug = str_replace('awb/', '', $slug);
            if ($content && strpos($content, $short_slug) === false) continue;
            $source    = $data['source'] ?? 'core';
            $base_url  = ('user' === $source) ? AWB_USER_PATTERNS_URL : AWB_PLUGIN_URL;
            $base_path = ('user' === $source) ? AWB_USER_PATTERNS_PATH : AWB_PLUGIN_PATH;
            if (! empty($data['css'])) {
                $rel = $data['css'];
                $abs = $base_path . $rel;
                if (file_exists($abs)) wp_enqueue_style('awb-pattern-' . $short_slug . '-style', $base_url . $rel, ['awb-starter'], filemtime($abs));
            }
            if (! empty($data['js'])) {
                $rel = $data['js'];
                $abs = $base_path . $rel;
                if (file_exists($abs)) wp_enqueue_script('awb-pattern-' . $short_slug . '-script', $base_url . $rel, ['awb-starter'], filemtime($abs), true);
            }
        }
    }

    public function enqueue_admin_assets(string $hook): void
    {
        $is_awb_page    = str_starts_with($hook, 'toplevel_page_awb') || str_contains($hook, 'awb-starter');
        $is_editor_page = in_array($hook, ['post.php', 'post-new.php'], true);
        if ($is_awb_page || $is_editor_page) {
            $this->enqueue_style('awb-starter-admin', 'assets/css/admin.css');
            $this->enqueue_script('awb-starter-admin', 'assets/js/admin.js');
        }
        if ($is_awb_page) {
            $this->enqueue_style('awb-admin-header-footer', 'assets/css/admin-header-footer.css', ['awb-starter-admin']);
            $this->enqueue_script('awb-admin-header-footer', 'assets/js/admin-header-footer.js', ['jquery', 'awb-starter-admin']);
            wp_localize_script('awb-admin-header-footer', 'awbHeaderFooter', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('awb_save_header_footer'),
                'i18n'    => ['saving' => __('Saving…', 'awb-starter'), 'saved' => __('Saved!', 'awb-starter'), 'error' => __('Something went wrong. Please try again.', 'awb-starter')]
            ]);
            $this->enqueue_style('awb-pattern-io', 'assets/css/admin-pattern-io.css', ['awb-starter-admin']);
            $this->enqueue_script('awb-pattern-io', 'assets/js/admin-pattern-io.js', ['awb-starter-admin']);
            wp_localize_script('awb-pattern-io', 'awbPatternIO', [
                'nonce'          => wp_create_nonce('awb_export_pattern'),
                'importNonce'    => wp_create_nonce('awb_import_pattern'),
                'duplicateNonce' => wp_create_nonce('awb_duplicate_pattern'),
                'editNonce'      => wp_create_nonce('awb_edit_pattern'),
                'deleteNonce'    => wp_create_nonce('awb_delete_pattern'), // <-- NEW
                'i18n'           => [
                    'export' => __('Export', 'awb-starter'),
                    'exporting' => __('Exporting…', 'awb-starter'),
                    'import' => __('Import', 'awb-starter'),
                    'importing' => __('Importing…', 'awb-starter'),
                    'importSuccess' => __('Pattern imported successfully.', 'awb-starter'),
                    'reloadNotice' => __('Reload the page to see it in the library.', 'awb-starter'),
                    'overwritePrompt' => __('The following files already exist and will be overwritten:', 'awb-starter'),
                    'networkError' => __('Network error. Please try again.', 'awb-starter'),
                    'unknownError' => __('An unknown error occurred.', 'awb-starter'),
                    'noFile' => __('Please select a ZIP file first.', 'awb-starter'),
                    'duplicate' => __('Clone', 'awb-starter'),
                    'duplicating' => __('Cloning…', 'awb-starter'),
                    'duplicateError' => __('Could not clone pattern.', 'awb-starter'),
                    'edit' => __('Edit', 'awb-starter'),
                    'loading' => __('Loading…', 'awb-starter'),
                    'deleteConfirm' => __('Are you sure you want to delete this pattern and its associated assets? This cannot be undone.', 'awb-starter'),
                    'deleting' => __('Deleting…', 'awb-starter'),
                    'deleteError' => __('Failed to delete pattern.', 'awb-starter')
                ]
            ]);
            wp_enqueue_code_editor(['type' => 'application/x-httpd-php']);
            wp_enqueue_script('wp-theme-plugin-editor');
            wp_enqueue_style('wp-codemirror');
            if (isset($_GET['tab']) && 'store' === $_GET['tab']) {
                $this->enqueue_style('awb-admin-store', 'assets/css/admin-store.css', ['awb-starter-admin']);
                $this->enqueue_script('awb-admin-store', 'assets/js/admin-store.js', ['awb-starter-admin']);
                wp_localize_script('awb-admin-store', 'awbStore', ['nonce' => wp_create_nonce('awb_install_remote_pattern')]);
            }
        }
        if ($is_editor_page) {
            $this->enqueue_script('awb-starter-ai-admin', 'assets/js/ai-admin.js', ['awb-starter-admin']);
            if (wp_script_is('awb-starter-ai-admin', 'enqueued')) {
                wp_localize_script('awb-starter-ai-admin', 'AWB', [
                    'ajaxUrl' => admin_url('admin-ajax.php'),
                    'nonce'   => wp_create_nonce('awb_generate_nonce'),
                ]);
            }
        }
        wp_localize_script(
            'awb-starter-ai-admin',
            'awbAiSettings',
            [
                'testNonce' => wp_create_nonce('awb_test_ai_api'),
            ]
        );
    }

    public function enqueue_editor_assets(): void
    {
        $this->enqueue_style('awb-starter-editor', 'assets/css/editor.css');
    }

    private function generate_design_tokens_css(): string
    {
        $css = '';
        $font_faces = $this->generate_font_faces_css();
        if ($font_faces) $css .= $font_faces . "\n";
        $tokens = [
            '--awb-color-primary' => get_option('awb_token_color_primary', '#1a1a2e'),
            '--awb-color-secondary' => get_option('awb_token_color_secondary', '#16213e'),
            '--awb-color-accent' => get_option('awb_token_color_accent', '#e94560'),
            '--awb-color-text' => get_option('awb_token_color_text', '#1a1a1a'),
            '--awb-color-bg' => get_option('awb_token_color_bg', '#ffffff'),
            '--awb-color-border' => 'color-mix(in srgb, ' . get_option('awb_token_color_bg', '#ffffff') . ' 80%, ' . get_option('awb_token_color_text', '#1a1a1a') . ')',
            '--awb-font-heading' => $this->get_font_stack('heading'),
            '--awb-font-body' => $this->get_font_stack('body'),
            '--awb-font-mono' => get_option('awb_token_font_mono', 'monospace'),
            '--awb-space-xs' => get_option('awb_token_space_xs', '0.25rem'),
            '--awb-space-sm' => get_option('awb_token_space_sm', '0.5rem'),
            '--awb-space-md' => get_option('awb_token_space_md', '1rem'),
            '--awb-space-lg' => get_option('awb_token_space_lg', '2rem'),
            '--awb-space-xl' => get_option('awb_token_space_xl', '4rem'),
            '--awb-radius-sm' => get_option('awb_token_radius_sm', '4px'),
            '--awb-radius-md' => get_option('awb_token_radius_md', '8px'),
            '--awb-radius-lg' => get_option('awb_token_radius_lg', '16px'),
        ];
        $css .= ":root {\n";
        foreach ($tokens as $p => $v) {
            $css .= "  {$p}: {$v};\n";
        }
        $css .= "}\n";
        return $css;
    }
    private function generate_font_faces_css(): string
    {
        $ff = '';
        $cf = ['regular' => get_option('awb_custom_font_regular', ''), 'medium' => get_option('awb_custom_font_medium', ''), 'bold' => get_option('awb_custom_font_bold', '')];
        $fw = ['regular' => '400', 'medium' => '500', 'bold' => '700'];
        foreach ($cf as $t => $u) {
            if (!$u) continue;
            $ff .= "@font-face {\n  font-family: 'AWB Custom Font';\n  font-weight: {$fw[$t]};\n  font-style: normal;\n  src: url('{$u}') format('" . $this->get_font_format($u) . "');\n}\n";
        }
        return $ff;
    }
    private function get_font_format(string $u): string
    {
        $f = ['woff' => 'woff', 'woff2' => 'woff2', 'ttf' => 'truetype', 'otf' => 'opentype'];
        return $f[strtolower(pathinfo($u, PATHINFO_EXTENSION))] ?? 'woff';
    }
    private function get_font_stack(string $t): string
    {
        $hc = get_option('awb_custom_font_regular', '') || get_option('awb_custom_font_medium', '') || get_option('awb_custom_font_bold', '');
        $fb = 'heading' === $t ? get_option('awb_token_font_heading', 'Georgia, serif') : get_option('awb_token_font_body', 'system-ui, sans-serif');
        return $hc ? "'AWB Custom Font', {$fb}" : $fb;
    }
}
