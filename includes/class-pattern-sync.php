<?php

/**
 * Pattern sync engine.
 *
 * Manages per-pattern sync flags and page conversion to core/pattern
 * reference blocks.  When a pattern is synced, any page that uses it stores
 * a reference block instead of a detached copy, so edits to the pattern
 * source automatically reflect in the editor and on the frontend.
 *
 * @package AWBStarter
 */
if (! defined('ABSPATH')) {
    exit;
}

class AWB_Pattern_Sync
{
    private const OPTION_KEY = 'awb_synced_patterns';

    /**
     * Get all synced pattern registered names.
     *
     * @return string[] e.g. ['awb/homepage']
     */
    public static function get_synced(): array
    {
        return (array) get_option(self::OPTION_KEY, []);
    }

    /**
     * Check if a pattern is synced.
     *
     * @param string $name e.g. awb/homepage
     */
    public static function is_synced(string $name): bool
    {
        return in_array($name, self::get_synced(), true);
    }

    /**
     * Enable or disable sync for a pattern.
     *
     * @param string $name e.g. awb/homepage
     * @param bool   $on   true = enable, false = disable
     */
    public static function set_synced(string $name, bool $on): void
    {
        $synced = self::get_synced();
        if ($on && ! in_array($name, $synced, true)) {
            $synced[] = $name;
        } elseif (! $on) {
            $synced = array_values(array_diff($synced, [$name]));
        }
        update_option(self::OPTION_KEY, $synced);
    }

    /**
     * Compute diff between a pattern's rendered output and a page's stored content.
     *
     * Returns the number of meaningful block-level differences or 0 if in sync.
     * Uses wp_text_diff on extracted text (stripped of HTML) for a quick check.
     *
     * @param string $name    e.g. awb/homepage
     * @param int    $post_id The page/post ID.
     * @return array{drifted: bool, diff_summary: string}
     */
    public static function page_drift(string $name, int $post_id): array
    {
        $post = get_post($post_id);
        if (! $post) {
            return ['drifted' => false, 'diff_summary' => ''];
        }

        $page_content = $post->post_content;

        // Already synced reference → no drift possible
        $ref = self::reference_markup($name);
        if (strpos($page_content, $ref) !== false) {
            return ['drifted' => false, 'diff_summary' => ''];
        }

        // Render the pattern's current source
        $pattern = WP_Block_Patterns_Registry::get_instance()->get_registered($name);
        if (! is_array($pattern) || empty($pattern['content'])) {
            return ['drifted' => false, 'diff_summary' => ''];
        }

        // Strip the outer wp:html wrappers from both to compare inner content
        $strip = function ($c) {
            $c = preg_replace('/^<!-- wp:html -->\s*/m', '', $c);
            $c = preg_replace('/\s*<!-- \/wp:html -->$/m', '', $c);
            return trim($c);
        };

        $pattern_inner = $strip($pattern['content']);
        $page_inner    = $strip($page_content);

        // Normalise: literal WLM+ (pattern evaluation) vs dynamic PHP in source
        // The pattern content is already evaluated; the page may have WLM+ as literal text.
        $page_inner = str_replace('WLM+', get_bloginfo('name'), $page_inner);

        if ($pattern_inner === $page_inner) {
            return ['drifted' => false, 'diff_summary' => ''];
        }

        // Quick diff: strip tags for text-only comparison
        $pat_text = wp_strip_all_tags($pattern_inner);
        $page_text = wp_strip_all_tags($page_inner);

        $diff = wp_text_diff($pat_text, $page_text, ['show_divs' => false]);
        if (empty($diff)) {
            return ['drifted' => false, 'diff_summary' => ''];
        }

        // Count diff lines to give a rough severity
        $additions = substr_count($diff, '<ins>');
        $deletions = substr_count($diff, '<del>');
        $summary  = sprintf('%d change%s detected', $additions + $deletions, ($additions + $deletions) === 1 ? '' : 's');

        return ['drifted' => true, 'diff_summary' => $summary];
    }

    /**
     * Build the core/pattern reference block markup for a pattern.
     *
     * @param string $name e.g. awb/homepage
     * @return string e.g. <!-- wp:pattern {"slug":"awb/homepage"} /-->
     */
    public static function reference_markup(string $name): string
    {
        return '<!-- wp:pattern {"slug":"' . esc_attr($name) . '"} /-->';
    }

    /**
     * Detect pages whose content references a pattern.
     *
     * Returns an array of post objects (ID, title, post_name).
     * Detection is best-effort: uses LIKE on post_content for the short slug.
     * The caller should confirm before converting.
     *
     * @param string $name e.g. awb/homepage
     * @return object[] Array of {ID, post_title, post_name}
     */
    public static function get_usage_posts(string $name): array
    {
        global $wpdb;

        $short = str_replace('awb/', '', $name);
        $like  = '%' . $wpdb->esc_like($short) . '%';

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT ID, post_title, post_name FROM {$wpdb->posts}
                 WHERE post_status = 'publish'
                   AND post_type IN ('page','post')
                   AND post_content LIKE %s",
                $like
            )
        );

        return is_array($results) ? $results : [];
    }

    /**
     * Convert a page's content to a synced reference block.
     *
     * Replaces the entire post_content with the core/pattern reference
     * markup. A revision backup is created before overwriting.
     *
     * @param string $name    e.g. awb/homepage
     * @param int    $post_id The page/post ID.
     * @return true|\WP_Error
     */
    public static function convert_page(string $name, int $post_id)
    {
        if (! current_user_can('edit_post', $post_id)) {
            return new \WP_Error('cannot_edit', __('You do not have permission to edit this post.', 'awb-starter'));
        }

        $post = get_post($post_id);
        if (! $post) {
            return new \WP_Error('not_found', __('Post not found.', 'awb-starter'));
        }

        $reference = self::reference_markup($name);

        // If already synced, no-op.
        if (strpos($post->post_content, $reference) !== false) {
            return new \WP_Error('already_synced', __('This page is already synced to this pattern.', 'awb-starter'));
        }

        // Create a revision backup.
        if (function_exists('wp_save_post_revision')) {
            wp_save_post_revision($post_id);
        }

        // Replace content with reference block.
        $update = wp_update_post([
            'ID'           => $post_id,
            'post_content' => $reference,
        ], true);

        if (is_wp_error($update)) {
            return $update;
        }

        return true;
    }
}
