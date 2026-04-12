<?php

/**
 * Class AWB_Header_Switcher
 *
 * Manages custom header and footer output, replacing the active theme's
 * default header/footer with a block pattern or a user-created reusable block.
 *
 * Strategy (theme-agnostic):
 *  1. For GeneratePress: hooks into `generate_header` / `generate_footer`
 *     action hooks that GP exposes and suppresses the default output.
 *  2. Fallback for any other theme: uses output buffering on `wp_body_open`
 *     / `get_footer` to intercept and replace markup.
 *
 * @package AWB_Starter
 * @since   1.0.0
 */

if (! defined('ABSPATH')) {
    exit;
}

class AWB_Header_Switcher
{

    // ---------------------------------------------------------------------------
    // Option keys
    // ---------------------------------------------------------------------------

    const OPTION_HEADER_TYPE   = 'awb_header_type';   // 'none' | 'pattern' | 'block'
    const OPTION_HEADER_VALUE  = 'awb_header_value';  // pattern slug OR post ID
    const OPTION_FOOTER_TYPE   = 'awb_footer_type';
    const OPTION_FOOTER_VALUE  = 'awb_footer_value';

    // ---------------------------------------------------------------------------
    // Boot
    // ---------------------------------------------------------------------------

    public function __construct()
    {
        add_action('init', array($this, 'register_hooks'));
    }

    /**
     * Register front-end hooks after init so conditional theme checks are safe.
     */
    public function register_hooks()
    {
        if (is_admin()) {
            return;
        }

        $header_type = get_option(self::OPTION_HEADER_TYPE, 'none');
        $footer_type = get_option(self::OPTION_FOOTER_TYPE, 'none');

        if ('none' !== $header_type) {
            $this->hook_header();
        }

        if ('none' !== $footer_type) {
            $this->hook_footer();
        }
    }

    // ---------------------------------------------------------------------------
    // Header hooks
    // ---------------------------------------------------------------------------

    private function hook_header()
    {
        if ($this->is_generatepress()) {
            // Remove GP's default header actions.
            add_action('generate_header', array($this, 'suppress_generatepress_header'), 1);
            // Output our custom header inside the same hook at default priority.
            add_action('generate_header', array($this, 'render_custom_header'), 10);
        } else {
            // Generic fallback: buffer the_header() and replace it.
            add_action('get_header', array($this, 'start_header_buffer'));
            add_filter('wp_head',    array($this, 'flush_header_buffer'), 999);
        }
    }

    /**
     * Remove GeneratePress's own header hooks so we get a clean slate.
     */
    public function suppress_generatepress_header()
    {
        remove_all_actions('generate_header');
        // Re-add our render so it still fires after we cleared the queue.
        add_action('generate_header', array($this, 'render_custom_header'), 10);
    }

    public function render_custom_header()
    {
        $type  = get_option(self::OPTION_HEADER_TYPE, 'none');
        $value = get_option(self::OPTION_HEADER_VALUE, '');
        echo $this->get_rendered_content($type, $value); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    // ---------------------------------------------------------------------------
    // Footer hooks
    // ---------------------------------------------------------------------------

    private function hook_footer()
    {
        if ($this->is_generatepress()) {
            add_action('generate_footer', array($this, 'suppress_generatepress_footer'), 1);
            add_action('generate_footer', array($this, 'render_custom_footer'), 10);
        } else {
            add_action('get_footer', array($this, 'start_footer_buffer'));
            add_action('wp_footer',  array($this, 'flush_footer_buffer'), 999);
        }
    }

    public function suppress_generatepress_footer()
    {
        remove_all_actions('generate_footer');
        add_action('generate_footer', array($this, 'render_custom_footer'), 10);
    }

    public function render_custom_footer()
    {
        $type  = get_option(self::OPTION_FOOTER_TYPE, 'none');
        $value = get_option(self::OPTION_FOOTER_VALUE, '');
        echo $this->get_rendered_content($type, $value); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    // ---------------------------------------------------------------------------
    // Generic output-buffer fallback (non-GP themes)
    // ---------------------------------------------------------------------------

    private $header_buffer_started = false;
    private $footer_buffer_started = false;

    public function start_header_buffer()
    {
        ob_start();
        $this->header_buffer_started = true;
    }

    public function flush_header_buffer()
    {
        if ($this->header_buffer_started) {
            ob_end_clean();
            $this->render_custom_header();
            $this->header_buffer_started = false;
        }
    }

    public function start_footer_buffer()
    {
        ob_start();
        $this->footer_buffer_started = true;
    }

    public function flush_footer_buffer()
    {
        if ($this->footer_buffer_started) {
            ob_end_clean();
            $this->render_custom_footer();
            $this->footer_buffer_started = false;
        }
    }

	// ---------------------------------------------------------------------------
	// Content rendering
	// ---------------------------------------------------------------------------

    /**
     * Resolve and render the stored header/footer content.
     *
     * @param string $type  'pattern' or 'block'.
     * @param string $value Pattern slug or reusable-block post ID.
     * @return string Rendered HTML (block-parsed).
     */
    private function get_rendered_content($type, $value)
    {
        if (empty($value)) {
            return '';
        }

        $raw = '';

        if ('pattern' === $type) {
            $raw = $this->get_pattern_content(sanitize_text_field($value));
        } elseif ('block' === $type) {
            $post_id = absint($value);
            $post    = get_post($post_id);
            if ($post && 'wp_block' === $post->post_type && 'publish' === $post->post_status) {
                $raw = $post->post_content;
            }
        }

        if (empty($raw)) {
            return '';
        }

        return do_blocks($raw);
    }

    /**
     * Retrieve the raw block markup for an AWB-registered pattern by slug.
     *
     * @param string $slug Pattern slug (e.g. 'awb-starter/header-default').
     * @return string Raw block content or empty string.
     */
    private function get_pattern_content($slug)
    {
        $registry = WP_Block_Patterns_Registry::get_instance();

        if ($registry->is_registered($slug)) {
            $pattern = $registry->get_registered($slug);
            return $pattern['content'] ?? '';
        }

        return '';
    }

	// ---------------------------------------------------------------------------
	// Utilities
	// ---------------------------------------------------------------------------

    /**
     * Detect whether GeneratePress (free or Pro) is the active theme.
     *
     * @return bool
     */
    private function is_generatepress()
    {
        $theme = wp_get_theme();
        $slug  = $theme->get_template(); // Works for child themes too.
        return 'generatepress' === $slug;
    }

	// ---------------------------------------------------------------------------
	// Static helpers used by the admin UI and AJAX handler
	// ---------------------------------------------------------------------------

    /**
     * Return all block patterns registered under AWB categories,
     * grouped by category slug, filtered to header / footer only.
     *
     * @param string $group 'header' | 'footer' | 'all'
     * @return array
     */
    public static function get_awb_patterns($group = 'all')
    {
        $registry  = WP_Block_Patterns_Registry::get_instance();
        $all       = $registry->get_all_registered();
        $patterns  = array();

        foreach ($all as $pattern) {
            $categories = $pattern['categories'] ?? array();

            foreach ($categories as $cat) {
                if ('all' === $group) {
                    $patterns[] = $pattern;
                    break;
                }
                if (false !== strpos($cat, $group)) {
                    $patterns[] = $pattern;
                    break;
                }
            }
        }

        return $patterns;
    }

    /**
     * Return all published wp_block posts (reusable blocks / synced patterns).
     *
     * @return WP_Post[]
     */
    public static function get_reusable_blocks()
    {
        return get_posts(
            array(
                'post_type'      => 'wp_block',
                'post_status'    => 'publish',
                'posts_per_page' => -1,
                'orderby'        => 'title',
                'order'          => 'ASC',
                'no_found_rows'  => true,
            )
        );
    }

    /**
     * Persist header/footer settings.
     * Called from AWB_Ajax_Handler; all sanitization happens here.
     *
     * @param array $data Raw POST data (already nonce-verified by caller).
     * @return true|WP_Error
     */
    public static function save_settings(array $data)
    {
        $allowed_types = array('none', 'pattern', 'block');

        $header_type  = isset($data['header_type']) && in_array($data['header_type'], $allowed_types, true)
            ? $data['header_type'] : 'none';
        $header_value = isset($data['header_value']) ? sanitize_text_field(wp_unslash($data['header_value'])) : '';

        $footer_type  = isset($data['footer_type']) && in_array($data['footer_type'], $allowed_types, true)
            ? $data['footer_type'] : 'none';
        $footer_value = isset($data['footer_value']) ? sanitize_text_field(wp_unslash($data['footer_value'])) : '';

        // Extra validation: if type is 'block', value must be numeric.
        if ('block' === $header_type && ! is_numeric($header_value)) {
            return new WP_Error('invalid_header_value', __('Invalid header block ID.', 'awb-starter'));
        }
        if ('block' === $footer_type && ! is_numeric($footer_value)) {
            return new WP_Error('invalid_footer_value', __('Invalid footer block ID.', 'awb-starter'));
        }

        update_option(self::OPTION_HEADER_TYPE,  $header_type);
        update_option(self::OPTION_HEADER_VALUE, $header_value);
        update_option(self::OPTION_FOOTER_TYPE,  $footer_type);
        update_option(self::OPTION_FOOTER_VALUE, $footer_value);

        return true;
    }
}
