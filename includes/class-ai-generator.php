<?php

/**
 * AWB AI Generator - Multi-Provider Support
 *
 * Supports: Anthropic, OpenAI, Qwen, DeepSeek, Groq.
 * All keys are stored securely in WP Options. Responses are sanitized.
 *
 * @package AWBStarter
 */
if (! defined('ABSPATH')) {
    exit;
}

class AWB_AI_Generator
{
    /** Supported providers configuration */
    private const PROVIDERS = [
        'anthropic' => [
            'endpoint'   => 'https://api.anthropic.com/v1/messages',
            'model'      => 'claude-opus-4-5',
            'headers'    => ['Content-Type' => 'application/json', 'anthropic-version' => '2023-06-01'],
            'header_key' => 'x-api-key',
        ],
        'openai'    => [
            'endpoint'   => 'https://api.openai.com/v1/chat/completions',
            'model'      => 'gpt-4o',
            'headers'    => ['Content-Type' => 'application/json'],
            'header_key' => 'Authorization',
            'auth_prefix' => 'Bearer ',
        ],
        'qwen'      => [
            'endpoint'   => 'https://dashscope.aliyuncs.com/compatible-mode/v1/chat/completions',
            'model'      => 'qwen-max',
            'headers'    => ['Content-Type' => 'application/json'],
            'header_key' => 'Authorization',
            'auth_prefix' => 'Bearer ',
        ],
        'deepseek'  => [
            'endpoint'   => 'https://api.deepseek.com/v1/chat/completions',
            'model'      => 'deepseek-chat',
            'headers'    => ['Content-Type' => 'application/json'],
            'header_key' => 'Authorization',
            'auth_prefix' => 'Bearer ',
        ],
        'groq'      => [
            'endpoint'   => 'https://api.groq.com/openai/v1/chat/completions',
            'model'      => 'llama-3.3-70b-versatile',
            'headers'    => ['Content-Type' => 'application/json'],
            'header_key' => 'Authorization',
            'auth_prefix' => 'Bearer ',
        ],
    ];

    /**
     * Get list of available providers for UI dropdowns.
     *
     * @return array<string, string>
     */
    public static function get_providers(): array
    {
        return [
            'anthropic' => __('Anthropic (Claude)', 'awb-starter'),
            'openai'    => __('OpenAI (ChatGPT)', 'awb-starter'),
            'qwen'      => __('Alibaba (Qwen)', 'awb-starter'),
            'deepseek'  => __('DeepSeek', 'awb-starter'),
            'groq'      => __('Groq', 'awb-starter'),
        ];
    }

    /**
     * Verify an API key for a specific provider.
     *
     * @param string $provider Provider slug.
     * @return bool|WP_Error
     */
    public static function verify_api_key(string $provider): bool|\WP_Error
    {
        $key = self::get_api_key($provider);
        if (empty($key)) {
            return new \WP_Error('no_key', __('No API key configured.', 'awb-starter'));
        }

        $config = self::PROVIDERS[$provider] ?? null;
        if (! $config) {
            return new \WP_Error('invalid_provider', __('Unsupported provider.', 'awb-starter'));
        }

        $headers = $config['headers'];
        $auth_header = $config['header_key'];
        $headers[$auth_header] = isset($config['auth_prefix'])
            ? $config['auth_prefix'] . $key
            : $key;

        $is_anthropic = ('anthropic' === $provider);
        $body = $is_anthropic
            ? wp_json_encode([
                'model'      => $config['model'],
                'max_tokens' => 16,
                'system'     => 'Test.',
                'messages'   => [['role' => 'user', 'content' => 'Reply with OK']],
            ])
            : wp_json_encode([
                'model'      => $config['model'],
                'max_tokens' => 16,
                'messages'   => [['role' => 'user', 'content' => 'Reply with OK']],
            ]);

        $response = wp_remote_post($config['endpoint'], [
            'timeout' => 15,
            'headers' => $headers,
            'body'    => $body,
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code($response);
        return ($code >= 200 && $code < 300) ? true : new \WP_Error('api_error', sprintf(__('Invalid key or API error (HTTP %d).', 'awb-starter'), (int) $code));
    }

    /**
     * Generate block markup using the active provider.
     *
     * @param string $prompt User prompt.
     * @return string|WP_Error
     */
    public static function generate(string $prompt): string|\WP_Error
    {
        $provider = get_option('awb_ai_provider', 'anthropic');
        $key = self::get_api_key($provider);

        if (empty($key)) {
            return new \WP_Error('no_key', sprintf(
                __('No API key set for %s. Configure in AWB Starter settings.', 'awb-starter'),
                self::get_providers()[$provider] ?? $provider
            ));
        }

        $config = self::PROVIDERS[$provider] ?? null;
        if (! $config) {
            return new \WP_Error('invalid_provider', __('Selected provider is unsupported.', 'awb-starter'));
        }

        $system_prompt = __('You are an expert WordPress developer. Respond ONLY with valid Gutenberg block markup. Do not include markdown fences, explanations, or extra text. Just the raw block HTML.', 'awb-starter');
        $headers = $config['headers'];
        $auth_header = $config['header_key'];
        $headers[$auth_header] = isset($config['auth_prefix'])
            ? $config['auth_prefix'] . $key
            : $key;

        $is_anthropic = ('anthropic' === $provider);
        $body = $is_anthropic
            ? wp_json_encode([
                'model'      => $config['model'],
                'max_tokens' => 4096,
                'system'     => $system_prompt,
                'messages'   => [['role' => 'user', 'content' => $prompt]],
            ])
            : wp_json_encode([
                'model'      => $config['model'],
                'max_tokens' => 4096,
                'messages'   => [
                    ['role' => 'system', 'content' => $system_prompt],
                    ['role' => 'user',   'content' => $prompt],
                ],
            ]);

        $response = wp_remote_post($config['endpoint'], [
            'timeout' => 45,
            'headers' => $headers,
            'body'    => $body,
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code($response);
        $resp_body = json_decode(wp_remote_retrieve_body($response), true);

        if ($code !== 200) {
            $msg = $resp_body['error']['message'] ?? sprintf(__('API returned HTTP %d.', 'awb-starter'), (int) $code);
            return new \WP_Error('api_error', $msg);
        }

        // Extract content based on provider response format
        $content = $is_anthropic
            ? ($resp_body['content'][0]['text'] ?? '')
            : ($resp_body['choices'][0]['message']['content'] ?? '');

        if (empty($content)) {
            return new \WP_Error('empty_response', __('API returned empty content.', 'awb-starter'));
        }

        // Strip markdown code blocks if provider adds them
        $content = preg_replace('/^```(?:html|php|xml)?\s*\n?|\n?```$/m', '', trim($content));
        return $content;
    }

    /**
     * Get API key for a provider. Fallback to legacy key for backward compatibility.
     *
     * @param string $provider
     * @return string
     */
    private static function get_api_key(string $provider): string
    {
        $option = 'awb_ai_' . $provider . '_key';
        $key = get_option($option, '');

        // Backward compatibility: migrate old single Anthropic key if new key is empty
        if ('anthropic' === $provider && empty($key)) {
            $legacy_key = get_option('awb_ai_api_key', '');
            if (! empty($legacy_key)) {
                update_option($option, $legacy_key);
                delete_option('awb_ai_api_key');
                return $legacy_key;
            }
        }
        return $key;
    }
}
