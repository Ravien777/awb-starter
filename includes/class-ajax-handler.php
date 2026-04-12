<?php

/**
 * AJAX endpoint registration.
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
        add_action('wp_ajax_awb_generate', [$this, 'handle_generate']);

        add_action('wp_ajax_awb_save_header_footer', array($this, 'save_header_footer'));
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

    /**
     * AJAX: Save header/footer switcher settings.
     *
     * POST params:
     *   nonce        string  WordPress nonce (action: awb_save_header_footer)
     *   header_type  string  'none' | 'pattern' | 'block'
     *   header_value string  Pattern slug or reusable-block post ID
     *   footer_type  string  'none' | 'pattern' | 'block'
     *   footer_value string  Pattern slug or reusable-block post ID
     */
    public function save_header_footer()
    {
        // 1. Capability check.
        if (! current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions.', 'awb-starter'), 403);
        }

        // 2. Nonce verification.
        $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
        if (! wp_verify_nonce($nonce, 'awb_save_header_footer')) {
            wp_send_json_error(__('Security check failed.', 'awb-starter'), 403);
        }

        // 3. Delegate sanitization and persistence to the switcher class.
        $result = AWB_Header_Switcher::save_settings($_POST);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success(__('Settings saved.', 'awb-starter'));
    }
}
