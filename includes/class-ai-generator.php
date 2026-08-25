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
     * @param string $prompt  User prompt.
     * @param array{mode?: string, tone?: string, template?: string} $options Structured UI options.
     * @return string|WP_Error
     */
    public static function generate(string $prompt, array $options = []): string|\WP_Error
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
        $system_prompt = $theme_context . self::base_rules() . self::mode_instruction($options['mode'] ?? 'blocks');

        $user_content = self::tone_instruction($options['tone'] ?? '') . self::template_context($options['template'] ?? '') . $prompt;
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
                'messages'   => [['role' => 'user', 'content' => $user_content]],
            ])
            : wp_json_encode([
                'model'      => $config['model'],
                'max_tokens' => 4096,
                'temperature' => 0.7,
                'messages'   => [
                    ['role' => 'system', 'content' => $system_prompt],
                    ['role' => 'user',   'content' => $user_content],
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
     * Generate content and create a draft page with it.
     *
     * @param string $prompt  User prompt.
     * @param array  $options Structured UI options (mode, tone, template).
     * @return array|WP_Error Array with edit_url and post_id on success, WP_Error on failure.
     */
    public static function create_draft_page(string $prompt, array $options = []): array|\WP_Error
    {
        $content = self::generate($prompt, $options);
        if (is_wp_error($content)) {
            return $content;
        }
        // Block markup survives kses; raw scripts/styles are stripped for non-privileged users.
        $content = wp_kses_post($content);
        if (empty(trim($content))) {
            return new \WP_Error('empty_content', __('No generated content to insert.', 'awb-starter'));
        }
        $post_id = wp_insert_post([
            'post_title'   => __('AI Generated Section', 'awb-starter'),
            'post_content' => $content,
            'post_status'  => 'draft',
            'post_type'    => 'page',
        ]);
        if (is_wp_error($post_id)) {
            return $post_id;
        }
        return [
            'edit_url' => (string) get_edit_post_link((int) $post_id, 'raw'),
            'post_id'  => (int) $post_id,
        ];
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

    /**
     * Baseline system rules shared by every generation request.
     */
    private static function base_rules(): string
    {
        return __("You are an expert WordPress developer and UI/UX designer specializing in Gutenberg block markup.

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
        - Columns: max 3 columns on desktop, stack on mobile", 'awb-starter');
    }

    /**
     * Output-mode instruction appended to the system prompt.
     *
     * @param string $mode blocks|html|copy
     */
    private static function mode_instruction(string $mode): string
    {
        return match ($mode) {
            'html'  => "\n\nOUTPUT FORMAT:\nPlain semantic HTML markup only — NO Gutenberg block comments (no <!-- wp: ... -->), no markdown fences, no explanations.",
            'copy'  => "\n\nOUTPUT FORMAT:\nText copy only. Provide the headings and body text as plain text lines, one block of copy per line. No HTML tags, no block comments, no markdown.",
            default => "\n\nOUTPUT FORMAT:\nRaw Gutenberg block comment markup only. Start directly with <!-- wp: ...",
        };
    }

    /**
     * Tone instruction prepended to the user prompt.
     */
    private static function tone_instruction(string $tone): string
    {
        $tones = [
            'professional' => __('Write all copy in a professional, confident tone.', 'awb-starter'),
            'friendly'     => __('Write all copy in a friendly, approachable tone.', 'awb-starter'),
            'bold'         => __('Write all copy in a bold, punchy, high-energy tone.', 'awb-starter'),
            'minimal'      => __('Keep all copy minimal and concise — short headlines, brief supporting text.', 'awb-starter'),
        ];
        return isset($tones[$tone]) ? $tones[$tone] . "\n" : '';
    }

    /**
     * Structural context from a bundled block template, prepended to the user prompt.
     *
     * @param string $template Template filename without extension.
     */
    private static function template_context(string $template): string
    {
        if ('' === $template || ! preg_match('/^[a-z0-9_-]+$/', $template)) {
            return '';
        }
        $path = AWB_PLUGIN_PATH . 'block-templates/' . $template . '.html';
        if (! is_readable($path)) {
            return '';
        }
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
        $structure = file_get_contents($path);
        if (empty($structure)) {
            return '';
        }
        return sprintf(
            "Use the following block structure as the base for your output. Keep its overall layout but adapt and enrich the content to fulfil the request:\n\n%s\n\n",
            $structure
        );
    }
}
