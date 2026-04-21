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
     * Build an enriched prompt from structured UI inputs.
     *
     * @param array<string, mixed> $inputs User inputs including section_type, style, color_scheme, etc.
     * @return string The compiled prompt.
     */
    public static function build_prompt(array $inputs): string
    {
        $section_type = sanitize_text_field($inputs['section_type'] ?? 'hero');
        $style        = sanitize_text_field($inputs['style'] ?? 'modern');
        $color_scheme = sanitize_text_field($inputs['color_scheme'] ?? 'light');
        $description  = sanitize_textarea_field($inputs['description'] ?? '');
        $has_cta      = ! empty($inputs['has_cta']);
        $columns      = intval($inputs['columns'] ?? 1);
        $brand_colors = sanitize_text_field($inputs['brand_colors'] ?? '');

        $prompt = "Create a {$style} WordPress Gutenberg section of type: {$section_type}.\n";
        $prompt .= "Color scheme: {$color_scheme}.\n";

        if ($columns > 1) {
            $prompt .= "Use a {$columns}-column layout.\n";
        }

        if ($has_cta) {
            $prompt .= "Include a prominent Call-To-Action button.\n";
        }

        if (! empty($brand_colors)) {
            $prompt .= "Brand colors to use: {$brand_colors}.\n";
        }

        if (! empty($description)) {
            $prompt .= "Additional details: {$description}\n";
        }

        $prompt .= "Make it visually polished, production-ready, and professional.";

        return $prompt;
    }

    /**
     * Generate with a two-pass refinement flow.
     *
     * Pass 1: Generate initial draft. Pass 2: Refine for polish and quality.
     *
     * @param string $prompt Initial user prompt.
     * @return string|WP_Error
     */
    public static function generate_with_refinement(string $prompt): string|\WP_Error
    {
        // Pass 1: Generate initial draft
        $draft = self::generate($prompt);
        if (is_wp_error($draft)) {
            return $draft;
        }

        // Pass 2: Refine the draft
        $refine_prompt = "Here is a Gutenberg block section:\n\n{$draft}\n\n"
            . "Improve it: enhance spacing, visual hierarchy, and styling. "
            . "Make it more polished and production-ready. "
            . "Return ONLY the improved block markup.";

        return self::generate($refine_prompt);
    }

    /**
     * Get plugin design tokens for the AI model.
     *
     * @return string Plugin design tokens information for the AI model.
     */
    private static function get_theme_context(): string
    {
        $tokens = [
            '--awb-color-primary' => get_option('awb_token_color_primary', '#1a1a2e'),
            '--awb-color-secondary' => get_option('awb_token_color_secondary', '#16213e'),
            '--awb-color-accent' => get_option('awb_token_color_accent', '#e94560'),
            '--awb-color-text' => get_option('awb_token_color_text', '#1a1a1a'),
            '--awb-color-bg' => get_option('awb_token_color_bg', '#ffffff'),
            '--awb-color-border' => 'color-mix(in srgb, ' . get_option('awb_token_color_bg', '#ffffff') . ' 80%, ' . get_option('awb_token_color_text', '#1a1a1a') . ')',
            '--awb-font-heading' => self::get_font_stack('heading'),
            '--awb-font-body' => self::get_font_stack('body'),
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

        $formatted_tokens = array_map(
            function ($key, $value) {
                return "{$key}: {$value}";
            },
            array_keys($tokens),
            $tokens
        );

        return "Available plugin design tokens:\n" . implode(', ', $formatted_tokens) . "\n"
            . "Prefer these CSS variables over hardcoded values for colors, fonts, spacing, and border radius.\n";
    }

    /**
     * Get font stack for headings or body text.
     *
     * @param string $type Either 'heading' or 'body'.
     * @return string Font stack.
     */
    private static function get_font_stack(string $type): string
    {
        $has_custom_font = get_option('awb_custom_font_regular', '') ||
            get_option('awb_custom_font_medium', '') ||
            get_option('awb_custom_font_bold', '');

        $fallback = 'heading' === $type
            ? get_option('awb_token_font_heading', 'Georgia, serif')
            : get_option('awb_token_font_body', 'system-ui, sans-serif');

        return $has_custom_font ? "'AWB Custom Font', {$fallback}" : $fallback;
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

        $theme_context = self::get_theme_context();
        $system_prompt = $theme_context . __("You are an expert WordPress developer and UI/UX designer specializing in Gutenberg block markup.

        RULES:
        - Respond ONLY with valid Gutenberg block HTML. No markdown fences, no explanations.
        - Use WordPress core blocks where possible (wp:group, wp:columns, wp:cover, wp:buttons, etc.)
        - Always produce COMPLETE, visually polished sections — never placeholder text like \"Lorem Ipsum\".
        - Apply inline styles for spacing, typography, and color to ensure the output looks good out of the box.
        - Use a modern, clean aesthetic: generous padding, clear visual hierarchy, readable font sizes.
        - Sections must be mobile-responsive using Gutenberg's built-in layout system.
        - Use semantic HTML inside blocks (h1-h3 for headings, p for paragraphs, etc.)
        - When using wp:cover or hero sections, always include an overlay and legible text contrast.

        STYLE GUIDELINES:
        - Padding: sections should have at minimum 60px top/bottom padding
        - Font sizes: headings ≥ 2rem, body text ≥ 1rem
        - Colors: use CSS variables like var(--wp--preset--color--primary) where applicable
        - Buttons: always style with background color, padding, border-radius
        - Columns: max 3 columns on desktop, stack on mobile

        OUTPUT FORMAT:
        Raw Gutenberg block comment markup only. Start directly with <!-- wp: ...", 'awb-starter');
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
                'temperature' => 0.7,
                'system'     => $system_prompt,
                'messages'   => [['role' => 'user', 'content' => $prompt]],
            ])
            : wp_json_encode([
                'model'      => $config['model'],
                'max_tokens' => 4096,
                'temperature' => 0.7,
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
