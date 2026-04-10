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
}
