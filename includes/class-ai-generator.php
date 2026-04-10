<?php

/**
 * AWB AI Generator
 *
 * Handles server-side AI content generation via the Anthropic API.
 * Drop your key in WP Options (never hardcode it).
 *
 * To store your key: go to AWB Starter → Settings → AI Settings,
 * or run once in your browser console:
 *   fetch('/wp-admin/admin-ajax.php') — no, just set it via the settings page.
 *
 * @package AWBStarter
 */

if (! defined('ABSPATH')) {
    exit;
}

class AWB_AI_Generator
{

    /** Anthropic API endpoint */
    private const API_URL = 'https://api.anthropic.com/v1/messages';

    /** Model to use */
    private const MODEL = 'claude-opus-4-5';

    /**
     * Generate content from a plain-language prompt.
     * Returns ready-to-paste WordPress block markup (HTML comment syntax).
     *
     * @param  string $prompt  E.g. "Write a hero section for a plumber in Amsterdam"
     * @return string|WP_Error  Block HTML or a WP_Error on failure.
     */
    public static function generate(string $prompt): string|\WP_Error
    {

        $api_key = get_option('awb_ai_api_key', '');
        if (empty($api_key)) {
            return new \WP_Error('no_key', __('No Anthropic API key set. Go to AWB Starter → AI Settings.', 'awb-starter'));
        }

        $system_prompt = <<<SYSTEM
You are an expert WordPress developer. When given a content request, you respond
ONLY with valid WordPress block editor (Gutenberg) HTML markup — the kind you
would paste directly into the Code Editor. Use standard core blocks (wp:heading,
wp:paragraph, wp:group, wp:columns, wp:button, wp:image with placeholder src).
Do not include any explanation, markdown fences, or extra text — just the block markup.
SYSTEM;

        $response = wp_remote_post(self::API_URL, [
            'timeout' => 30,
            'headers' => [
                'Content-Type'      => 'application/json',
                'x-api-key'         => $api_key,
                'anthropic-version' => '2023-06-01',
            ],
            'body' => wp_json_encode([
                'model'      => self::MODEL,
                'max_tokens' => 1024,
                'system'     => $system_prompt,
                'messages'   => [
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]),
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($code !== 200) {
            $msg = $body['error']['message'] ?? "API returned HTTP $code";
            return new \WP_Error('api_error', $msg);
        }

        $content = $body['content'][0]['text'] ?? '';
        if (empty($content)) {
            return new \WP_Error('empty_response', __('API returned an empty response.', 'awb-starter'));
        }

        return $content;
    }
}
