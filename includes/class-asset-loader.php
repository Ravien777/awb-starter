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
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('enqueue_block_assets', [$this, 'enqueue_editor_assets']);
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
            // Add design tokens as CSS custom properties
            $tokens_css = $this->generate_design_tokens_css();
            if ($tokens_css) {
                wp_add_inline_style('awb-starter', $tokens_css);
            }

            $custom_css = get_option('awb_custom_css', '');
            if ($custom_css) {
                wp_add_inline_style('awb-starter', wp_strip_all_tags($custom_css));
            }
        }

        if ($script_enqueued) {
            $custom_js = get_option('awb_custom_js', '');
            if ($custom_js) {
                wp_add_inline_script('awb-starter', wp_strip_all_tags($custom_js));
            }
        }

        $this->enqueue_pattern_assets();
    }

    private function enqueue_pattern_assets(): void
    {
        if (empty(AWB_Pattern_Loader::$pattern_assets)) {
            return;
        }

        $post    = get_post();
        $content = $post ? $post->post_content : '';

        foreach (AWB_Pattern_Loader::$pattern_assets as $slug => $files) {
            $short_slug = str_replace('awb/', '', $slug);
            if ($content && strpos($content, $short_slug) === false) {
                continue;
            }

            if (! empty($files['css'])) {
                $this->enqueue_style('awb-pattern-' . $short_slug . '-style', $files['css'], ['awb-starter']);
            }

            if (! empty($files['js'])) {
                $this->enqueue_script('awb-pattern-' . $short_slug . '-script', $files['js'], ['awb-starter']);
            }
        }
    }

    private function generate_design_tokens_css(): string
    {
        $css = '';

        // Generate @font-face declarations for custom fonts
        $font_faces = $this->generate_font_faces_css();
        if ($font_faces) {
            $css .= $font_faces . "\n";
        }

        $tokens = [
            // Colors
            '--awb-color-primary'    => get_option('awb_token_color_primary', '#1a1a2e'),
            '--awb-color-secondary'  => get_option('awb_token_color_secondary', '#16213e'),
            '--awb-color-accent'     => get_option('awb_token_color_accent', '#e94560'),
            '--awb-color-text'       => get_option('awb_token_color_text', '#1a1a1a'),
            '--awb-color-bg'         => get_option('awb_token_color_bg', '#ffffff'),
            '--awb-color-border'     => 'color-mix(in srgb, ' . get_option('awb_token_color_bg', '#ffffff') . ' 80%, ' . get_option('awb_token_color_text', '#1a1a1a') . ')',

            // Typography
            '--awb-font-heading'     => $this->get_font_stack('heading'),
            '--awb-font-body'        => $this->get_font_stack('body'),
            '--awb-font-mono'        => get_option('awb_token_font_mono', 'monospace'),

            // Spacing
            '--awb-space-xs'         => get_option('awb_token_space_xs', '0.25rem'),
            '--awb-space-sm'         => get_option('awb_token_space_sm', '0.5rem'),
            '--awb-space-md'         => get_option('awb_token_space_md', '1rem'),
            '--awb-space-lg'         => get_option('awb_token_space_lg', '2rem'),
            '--awb-space-xl'         => get_option('awb_token_space_xl', '4rem'),

            // Borders & Radius
            '--awb-radius-sm'        => get_option('awb_token_radius_sm', '4px'),
            '--awb-radius-md'        => get_option('awb_token_radius_md', '8px'),
            '--awb-radius-lg'        => get_option('awb_token_radius_lg', '16px'),
        ];

        $css .= ":root {\n";
        foreach ($tokens as $property => $value) {
            $css .= "  {$property}: {$value};\n";
        }
        $css .= "}\n";

        return $css;
    }

    private function generate_font_faces_css(): string
    {
        $font_faces = '';
        $custom_fonts = [
            'regular' => get_option('awb_custom_font_regular', ''),
            'medium'  => get_option('awb_custom_font_medium', ''),
            'bold'    => get_option('awb_custom_font_bold', ''),
        ];

        $font_weights = [
            'regular' => '400',
            'medium'  => '500',
            'bold'    => '700',
        ];

        foreach ($custom_fonts as $type => $font_url) {
            if ($font_url) {
                $font_faces .= "@font-face {\n";
                $font_faces .= "  font-family: 'AWB Custom Font';\n";
                $font_faces .= "  font-weight: {$font_weights[$type]};\n";
                $font_faces .= "  font-style: normal;\n";
                $font_faces .= "  src: url('{$font_url}') format('" . $this->get_font_format($font_url) . "');\n";
                $font_faces .= "}\n\n";
            }
        }

        return $font_faces;
    }

    private function get_font_format(string $font_url): string
    {
        $extension = strtolower(pathinfo($font_url, PATHINFO_EXTENSION));
        $formats = [
            'woff'  => 'woff',
            'woff2' => 'woff2',
            'ttf'   => 'truetype',
            'otf'   => 'opentype',
        ];

        return $formats[$extension] ?? 'woff';
    }

    private function get_font_stack(string $type): string
    {
        $has_custom_font = get_option('awb_custom_font_regular', '') ||
            get_option('awb_custom_font_medium', '') ||
            get_option('awb_custom_font_bold', '');

        if ($has_custom_font) {
            $fallback = $type === 'heading' ?
                get_option('awb_token_font_heading', 'Georgia, serif') :
                get_option('awb_token_font_body', 'system-ui, sans-serif');
            return "'AWB Custom Font', {$fallback}";
        }

        return $type === 'heading' ?
            get_option('awb_token_font_heading', 'Georgia, serif') :
            get_option('awb_token_font_body', 'system-ui, sans-serif');
    }

    public function enqueue_admin_assets(string $hook): void
    {
        $is_awb_page    = str_starts_with($hook, 'toplevel_page_awb') || str_contains($hook, 'awb-starter');
        $is_editor_page = in_array($hook, ['post.php', 'post-new.php'], true);

        // ── Shared admin base assets (all AWB admin screens + editor) ──────────
        if ($is_awb_page || $is_editor_page) {
            $this->enqueue_style('awb-starter-admin', 'assets/css/admin.css');
            $this->enqueue_script('awb-starter-admin', 'assets/js/admin.js');
        }

        // ── AWB settings page only ─────────────────────────────────────────────
        if ($is_awb_page) {
            // Header & Footer switcher tab assets.
            $this->enqueue_style('awb-admin-header-footer', 'assets/css/admin-header-footer.css', ['awb-starter-admin']);
            $this->enqueue_script('awb-admin-header-footer', 'assets/js/admin-header-footer.js', ['jquery', 'awb-starter-admin']);

            wp_localize_script(
                'awb-admin-header-footer',
                'awbHeaderFooter',
                [
                    'ajaxUrl' => admin_url('admin-ajax.php'),
                    'nonce'   => wp_create_nonce('awb_save_header_footer'),
                    'i18n'    => [
                        'saving' => __('Saving…', 'awb-starter'),
                        'saved'  => __('Saved!',  'awb-starter'),
                        'error'  => __('Something went wrong. Please try again.', 'awb-starter'),
                    ],
                ]
            );
        }

        // ── Block editor (post.php / post-new.php) only ────────────────────────
        if ($is_editor_page) {
            $this->enqueue_script('awb-starter-ai-admin', 'assets/js/ai-admin.js', ['awb-starter-admin']);

            if (wp_script_is('awb-starter-ai-admin', 'enqueued')) {
                wp_localize_script('awb-starter-ai-admin', 'AWB', [
                    'ajaxUrl' => admin_url('admin-ajax.php'),
                    'nonce'   => wp_create_nonce('awb_generate_nonce'),
                ]);
            }
        }
    }

    public function enqueue_editor_assets(): void
    {
        $this->enqueue_style('awb-starter-editor', 'assets/css/editor.css');
    }
}
