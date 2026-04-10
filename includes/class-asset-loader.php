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

    public function enqueue_admin_assets(string $hook): void
    {
        if (str_starts_with($hook, 'toplevel_page_awb') || str_contains($hook, 'awb-starter')) {
            $this->enqueue_style('awb-starter-admin', 'assets/css/admin.css');
            $this->enqueue_script('awb-starter-admin', 'assets/js/admin.js');
            return;
        }

        if (! in_array($hook, ['post.php', 'post-new.php'], true)) {
            return;
        }

        $this->enqueue_style('awb-starter-admin', 'assets/css/admin.css');
        $this->enqueue_script('awb-starter-admin', 'assets/js/admin.js');
        $this->enqueue_script('awb-starter-ai-admin', 'assets/js/ai-admin.js', ['awb-starter-admin']);

        if (wp_script_is('awb-starter-ai-admin', 'enqueued')) {
            wp_localize_script('awb-starter-ai-admin', 'AWB', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce'   => wp_create_nonce('awb_generate_nonce'),
            ]);
        }
    }

    public function enqueue_editor_assets(): void
    {
        $this->enqueue_style('awb-starter-editor', 'assets/css/editor.css');
    }
}
