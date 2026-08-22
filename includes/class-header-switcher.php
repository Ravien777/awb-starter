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
 *  2. Fallback for any other theme: uses output buffering on `get_header`
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

    const OPTION_HEADER_TYPE  = 'awb_header_type';   // 'none' | 'pattern' | 'block'
    const OPTION_HEADER_VALUE = 'awb_header_value';  // registered pattern name OR post ID
    const OPTION_FOOTER_TYPE  = 'awb_footer_type';
    const OPTION_FOOTER_VALUE = 'awb_footer_value';

    /**
     * Category slugs used to scope patterns to header / footer.
     * Must match the Categories declared in pattern file headers.
     */
    const CATEGORY_HEADER = 'awb-headers';
    const CATEGORY_FOOTER = 'awb-footers';

    // ---------------------------------------------------------------------------
    // Boot
    // ---------------------------------------------------------------------------

    public function __construct()
    {
        add_action('init', array($this, 'register_hooks'));
    }

    /**
     * Register front-end hooks after init so conditional theme checks are safe.
     * Bails early on admin screens — nothing to hook there.
     */
    public function register_hooks(): void
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

    private function hook_header(): void
    {
        if ($this->is_generatepress()) {
            // Hook at priority 1 to remove other callbacks before they run.
            add_action('generate_header', array($this, 'suppress_generatepress_header'), 1);
            // No need to add a separate action for render_custom_header.
        } else {
            add_action('get_header', array($this, 'start_header_buffer'));
            add_filter('wp_head', array($this, 'flush_header_buffer'), 999);
        }
    }

    public function suppress_generatepress_header(): void
    {
        // Remove all other callbacks from this hook.
        remove_all_actions('generate_header');

        // Directly render the custom header now.
        $this->render_custom_header();
    }

    public function render_custom_header(): void
    {
        $type  = get_option(self::OPTION_HEADER_TYPE, 'none');
        $value = get_option(self::OPTION_HEADER_VALUE, '');

        echo $this->get_rendered_content($type, $value); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    // ---------------------------------------------------------------------------
    // Footer hooks
    // ---------------------------------------------------------------------------

    private function hook_footer(): void
    {
        if ($this->is_generatepress()) {
            add_action('generate_footer', array($this, 'suppress_generatepress_footer'), 1);
        } else {
            add_action('get_footer', array($this, 'start_footer_buffer'));
            add_action('wp_footer', array($this, 'flush_footer_buffer'), 999);
        }
    }

    public function suppress_generatepress_footer(): void
    {
        remove_all_actions('generate_footer');
        $this->render_custom_footer();
    }

    public function render_custom_footer(): void
    {
        $type  = get_option(self::OPTION_FOOTER_TYPE, 'none');
        $value = get_option(self::OPTION_FOOTER_VALUE, '');
        echo $this->get_rendered_content($type, $value); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    // ---------------------------------------------------------------------------
    // Generic output-buffer fallback (non-GP themes)
    // ---------------------------------------------------------------------------

    private bool $header_buffer_started = false;
    private bool $footer_buffer_started = false;

    public function start_header_buffer(): void
    {
        ob_start();
        $this->header_buffer_started = true;
    }

    public function flush_header_buffer(): void
    {
        if ($this->header_buffer_started) {
            ob_end_clean();
            $this->render_custom_header();
            $this->header_buffer_started = false;
        }
    }

    public function start_footer_buffer(): void
    {
        ob_start();
        $this->footer_buffer_started = true;
    }

    public function flush_footer_buffer(): void
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
     * Try to resolve a pattern name even if the prefix 'awb/' is missing.
     *
     * @param string $value The raw stored value.
     * @return string Full registered pattern name, or empty string if unresolvable.
     */
    private function resolve_pattern_name(string $value): string
    {
        $registry = WP_Block_Patterns_Registry::get_instance();
        $candidates = [
            $value,                           // exact stored value
            'awb/' . ltrim($value, 'awb/'),   // ensure 'awb/' prefix
        ];

        foreach ($candidates as $candidate) {
            if ($registry->is_registered($candidate)) {
                return $candidate;
            }
        }

        // Optionally log the failure for debugging.
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf('AWB Header Switcher: Pattern "%s" not registered.', $value));
        }

        return '';
    }

    /**
     * Resolve and render the stored header/footer content.
     *
     * @param string $type  'pattern' or 'block'.
     * @param string $value Registered pattern name (e.g. 'awb/header-dark') or reusable-block post ID.
     * @return string Rendered HTML (block-parsed).
     */
    private function get_rendered_content(string $type, string $value): string
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
            // Show a hidden HTML comment in debug mode to help identify empty output.
            if (defined('WP_DEBUG') && WP_DEBUG) {
                return '<!-- AWB Header Switcher: No content found for type "' . esc_html($type) . '" with value "' . esc_html($value) . '" -->';
            }
            return '';
        }

        return do_blocks($raw);
    }

    /**
     * Retrieve the raw block markup for a registered pattern by its full name.
     *
     * WordPress stores patterns under their registered name (the first argument
     * passed to register_block_pattern), accessible via the 'name' key in the
     * pattern array — NOT a 'slug' key. AWB registers patterns as 'awb/{slug}',
     * so the value stored in the DB must be that full name, e.g. 'awb/header-dark'.
     *
     * @param string $pattern_name Full registered pattern name, e.g. 'awb/header-dark'.
     * @return string Raw block content or empty string.
     */
    private function get_pattern_content(string $pattern_name): string
    {
        $resolved = $this->resolve_pattern_name($pattern_name);
        if (empty($resolved)) {
            return '';
        }

        $pattern = WP_Block_Patterns_Registry::get_instance()->get_registered($resolved);
        return $pattern['content'] ?? '';
    }

    // ---------------------------------------------------------------------------
    // Utilities
    // ---------------------------------------------------------------------------

    /**
     * Detect whether GeneratePress (free or Pro) is the active theme.
     */
    private function is_generatepress(): bool
    {
        $theme = wp_get_theme();
        $template = $theme->get_template();

        return 'generatepress' === $template;
    }

    // ---------------------------------------------------------------------------
    // Static helpers used by the admin UI and AJAX handler
    // ---------------------------------------------------------------------------

    /**
     * Return all AWB block patterns filtered by header or footer category.
     *
     * WordPress stores each registered pattern with a 'name' key (the value
     * passed as the first argument to register_block_pattern). This is what
     * must be saved to the DB and passed back to get_pattern_content().
     *
     * The pattern file's "Slug:" header comment is only used by AWB_Pattern_Loader
     * to *construct* the registered name ('awb/' . slug). It is NOT stored as a
     * separate 'slug' key in the WP pattern registry — using $pattern['slug']
     * returns null for every pattern, which is the root cause of the empty-value
     * bug this method previously had.
     *
     * @param string $group 'header' | 'footer' | 'all'
     * @return array[] Array of pattern arrays, each with at least 'name' and 'title'.
     */
    public static function get_awb_patterns(string $group = 'all'): array
    {
        $registry = WP_Block_Patterns_Registry::get_instance();
        $all      = $registry->get_all_registered();
        $patterns = array();

        // Build the expected category slug for exact matching.
        // 'header' → 'awb-headers', 'footer' → 'awb-footers', 'all' → no filter.
        $target_category = '';
        if ('header' === $group) {
            $target_category = self::CATEGORY_HEADER;
        } elseif ('footer' === $group) {
            $target_category = self::CATEGORY_FOOTER;
        }

        foreach ($all as $pattern) {
            // Skip patterns not registered by this plugin.
            if (empty($pattern['name']) || 0 !== strpos($pattern['name'], 'awb/')) {
                continue;
            }

            if ('all' === $group) {
                $patterns[] = $pattern;
                continue;
            }

            $categories = $pattern['categories'] ?? array();
            if (in_array($target_category, $categories, true)) {
                $patterns[] = $pattern;
            }
        }

        return $patterns;
    }

    /**
     * Return all published wp_block posts (reusable blocks / synced patterns).
     *
     * @return WP_Post[]
     */
    public static function get_reusable_blocks(): array
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
     * Called from AWB_Ajax_Handler; nonce is verified by the caller.
     *
     * The 'header_value' / 'footer_value' for patterns must be the full
     * registered pattern name (e.g. 'awb/header-dark'), which is what the
     * admin UI now sends via the $pattern['name'] option values.
     *
     * @param array $data Raw POST data (already nonce-verified by caller).
     * @return true|WP_Error
     */
    public static function save_settings(array $data): true|WP_Error
    {
        $allowed_types = array('none', 'pattern', 'block');

        $header_type = (isset($data['header_type']) && in_array($data['header_type'], $allowed_types, true))
            ? $data['header_type'] : 'none';
        $header_value = isset($data['header_value'])
            ? sanitize_text_field(wp_unslash($data['header_value'])) : '';

        $footer_type = (isset($data['footer_type']) && in_array($data['footer_type'], $allowed_types, true))
            ? $data['footer_type'] : 'none';
        $footer_value = isset($data['footer_value'])
            ? sanitize_text_field(wp_unslash($data['footer_value'])) : '';

        // For 'block' type: value must be a numeric post ID.
        if ('block' === $header_type && ! is_numeric($header_value)) {
            return new WP_Error('invalid_header_value', __('Invalid header block ID.', 'awb-starter'));
        }
        if ('block' === $footer_type && ! is_numeric($footer_value)) {
            return new WP_Error('invalid_footer_value', __('Invalid footer block ID.', 'awb-starter'));
        }

        // For 'pattern' type: value must match a registered AWB pattern name.
        if ('pattern' === $header_type && ! empty($header_value)) {
            $registry = WP_Block_Patterns_Registry::get_instance();
            if (! $registry->is_registered($header_value)) {
                return new WP_Error('invalid_header_pattern', __('Selected header pattern is not registered.', 'awb-starter'));
            }
        }
        if ('pattern' === $footer_type && ! empty($footer_value)) {
            $registry = WP_Block_Patterns_Registry::get_instance();
            if (! $registry->is_registered($footer_value)) {
                return new WP_Error('invalid_footer_pattern', __('Selected footer pattern is not registered.', 'awb-starter'));
            }
        }

        update_option(self::OPTION_HEADER_TYPE,  $header_type);
        update_option(self::OPTION_HEADER_VALUE, $header_value);
        update_option(self::OPTION_FOOTER_TYPE,  $footer_type);
        update_option(self::OPTION_FOOTER_VALUE, $footer_value);

        return true;
    }
}
