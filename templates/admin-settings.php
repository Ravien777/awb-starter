<?php

/**
 * Admin Settings Page Template
 *
 * Loaded by AWB_Starter::render_settings_page().
 * $this is the AWB_Starter instance.
 *
 * @package AWBStarter
 */

if (! defined('ABSPATH')) exit;

$active_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'css-js';
$base_url   = admin_url('admin.php?page=awb-starter');

$tabs = [
    'css-js'    => ['label' => 'CSS &amp; JS',      'icon' => '✦'],
    'tokens'    => ['label' => 'Design Tokens',     'icon' => '◈'],
    'scaffold'  => ['label' => 'Site Scaffold',     'icon' => '⬡'],
    'ai'        => ['label' => 'AI Generator',      'icon' => '◎'],
    'patterns'  => ['label' => 'Pattern Library',   'icon' => '▦'],
    'info'      => ['label' => 'About',              'icon' => '◇'],
];
?>

<div class="awb-settings-wrap">

    <header class="awb-settings-header">
        <div class="awb-settings-header__logo">
            <span class="awb-logo-mark">AWB</span>
            <span class="awb-logo-sub">Starter <em>v<?php echo esc_html(AWB_VERSION); ?></em></span>
        </div>
        <p class="awb-settings-header__tagline">Rapid website creation — patterns, tokens, and tools in one place.</p>
    </header>

    <nav class="awb-settings-nav" aria-label="Settings sections">
        <?php foreach ($tabs as $slug => $tab) : ?>
            <a href="<?php echo esc_url($base_url . '&tab=' . $slug); ?>"
                class="awb-settings-nav__item <?php echo $active_tab === $slug ? 'is-active' : ''; ?>"
                aria-current="<?php echo $active_tab === $slug ? 'page' : 'false'; ?>">
                <span class="awb-nav-icon" aria-hidden="true"><?php echo $tab['icon']; ?></span>
                <?php echo $tab['label']; ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="awb-settings-body">

        <?php if (isset($_GET['settings-updated'])) : ?>
            <div class="awb-notice awb-notice--success" role="status">
                <span>&#10003;</span> Settings saved.
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['fonts-updated'])) : ?>
            <div class="awb-notice awb-notice--success" role="status">
                <span>&#10003;</span> Fonts uploaded successfully.
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])) : ?>
            <div class="awb-notice awb-notice--error" role="alert">
                <span>&#9888;</span> <?php echo esc_html(urldecode($_GET['error'])); ?>
            </div>
        <?php endif; ?>

        <?php /* ── Tab: CSS & JS ──────────────────────────────────────── */ ?>
        <?php if ($active_tab === 'css-js') : ?>

            <form method="post" action="options.php" class="awb-form">
                <?php settings_fields('awb_starter_group'); ?>

                <div class="awb-form__section">
                    <div class="awb-form__section-header">
                        <h2>Anthropic API Key</h2>
                        <p>Your Anthropic API key for AI content generation. Stored securely and used only for AI requests on this site.</p>
                    </div>
                    <div class="awb-editor-wrap">
                        <div class="awb-editor-toolbar">
                            <span class="awb-editor-lang">API</span>
                            <button type="button" class="awb-editor-btn" data-action="toggle-visibility" data-target="awb_ai_api_key">Show</button>
                            <button type="button" class="awb-editor-btn" data-action="clear" data-target="awb_ai_api_key">Clear</button>
                            <button type="button" class="awb-editor-btn" data-action="copy" data-target="awb_ai_api_key">Copy</button>
                        </div>
                        <div class="awb-api-key-wrap">
                            <input type="password" name="awb_ai_api_key" id="awb_ai_api_key" class="awb-editor awb-api-input" value="<?php echo esc_attr(get_option('awb_ai_api_key', '')); ?>" placeholder="sk-ant-..." autocomplete="off" />
                            <div class="awb-api-status" id="awb-api-status">
                                <?php
                                $saved_key = get_option('awb_ai_api_key', '');
                                if ($saved_key) :
                                ?>
                                    <span class="awb-api-status__badge awb-api-status__badge--saved">Key saved</span>
                                    <button type="button" class="awb-btn awb-btn--ghost awb-btn--sm" id="awb-test-api-key"
                                        data-nonce="<?php echo esc_attr(wp_create_nonce('awb_test_api_key_nonce')); ?>">
                                        Test connection
                                    </button>
                                    <span class="awb-api-status__result" id="awb-api-test-result"></span>
                                <?php else : ?>
                                    <span class="awb-api-status__badge awb-api-status__badge--empty">No key saved</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="awb-form__section">
                    <div class="awb-form__section-header">
                        <h2>Custom CSS</h2>
                        <p>Injected sitewide on the frontend after the plugin stylesheet. Supports all valid CSS including custom properties.</p>
                    </div>
                    <div class="awb-editor-wrap" data-lang="css">
                        <div class="awb-editor-toolbar">
                            <span class="awb-editor-lang">CSS</span>
                            <span class="awb-editor-char-count" id="awb-css-count">0 chars</span>
                            <button type="button" class="awb-editor-btn" data-action="format" data-target="awb_custom_css" data-lang="css">Format</button>
                            <button type="button" class="awb-editor-btn" data-action="clear" data-target="awb_custom_css">Clear</button>
                            <button type="button" class="awb-editor-btn" data-action="copy" data-target="awb_custom_css">Copy</button>
                        </div>
                        <textarea
                            name="awb_custom_css"
                            id="awb_custom_css"
                            class="awb-editor"
                            rows="16"
                            spellcheck="false"
                            autocomplete="off"
                            placeholder="/* Your custom CSS here */"><?php echo esc_textarea(get_option('awb_custom_css', '')); ?></textarea>
                    </div>
                </div>

                <div class="awb-form__section">
                    <div class="awb-form__section-header">
                        <h2>Custom JavaScript</h2>
                        <p>Injected sitewide on the frontend, loaded in the footer. Avoid re-declaring functions already handled by frontend.js.</p>
                    </div>
                    <div class="awb-editor-wrap" data-lang="js">
                        <div class="awb-editor-toolbar">
                            <span class="awb-editor-lang">JS</span>
                            <span class="awb-editor-char-count" id="awb-js-count">0 chars</span>
                            <button type="button" class="awb-editor-btn" data-action="clear" data-target="awb_custom_js">Clear</button>
                            <button type="button" class="awb-editor-btn" data-action="copy" data-target="awb_custom_js">Copy</button>
                        </div>
                        <textarea
                            name="awb_custom_js"
                            id="awb_custom_js"
                            class="awb-editor"
                            rows="16"
                            spellcheck="false"
                            autocomplete="off"
                            placeholder="// Your custom JavaScript here"><?php echo esc_textarea(get_option('awb_custom_js', '')); ?></textarea>
                    </div>
                </div>

                <div class="awb-form__section">
                    <div class="awb-form__section-header">
                        <h2>Asset Loading Options</h2>
                        <p>Control how the plugin loads assets globally.</p>
                    </div>
                    <div class="awb-toggle-grid">
                        <label class="awb-toggle-field">
                            <div class="awb-toggle-field__info">
                                <span class="awb-toggle-field__label">Defer custom JS</span>
                                <span class="awb-toggle-field__desc">Adds <code>defer</code> attribute to the custom JS script tag.</span>
                            </div>
                            <div class="awb-toggle-switch">
                                <input type="hidden" name="awb_defer_js" value="0">
                                <input type="checkbox" name="awb_defer_js" id="awb_defer_js" value="1" <?php checked(get_option('awb_defer_js', 0), 1); ?>>
                                <span class="awb-toggle-switch__track"></span>
                            </div>
                        </label>
                        <label class="awb-toggle-field">
                            <div class="awb-toggle-field__info">
                                <span class="awb-toggle-field__label">Minify CSS output</span>
                                <span class="awb-toggle-field__desc">Strips comments and whitespace before injecting custom CSS inline.</span>
                            </div>
                            <div class="awb-toggle-switch">
                                <input type="hidden" name="awb_minify_css" value="0">
                                <input type="checkbox" name="awb_minify_css" id="awb_minify_css" value="1" <?php checked(get_option('awb_minify_css', 0), 1); ?>>
                                <span class="awb-toggle-switch__track"></span>
                            </div>
                        </label>
                        <label class="awb-toggle-field">
                            <div class="awb-toggle-field__info">
                                <span class="awb-toggle-field__label">Disable frontend stylesheet</span>
                                <span class="awb-toggle-field__desc">Skips loading <code>frontend.css</code>. Use only if you're fully replacing plugin styles.</span>
                            </div>
                            <div class="awb-toggle-switch">
                                <input type="hidden" name="awb_disable_frontend_css" value="0">
                                <input type="checkbox" name="awb_disable_frontend_css" id="awb_disable_frontend_css" value="1" <?php checked(get_option('awb_disable_frontend_css', 0), 1); ?>>
                                <span class="awb-toggle-switch__track"></span>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="awb-form__actions">
                    <?php submit_button('Save Changes', 'primary', 'submit', false, ['class' => 'awb-btn awb-btn--primary']); ?>
                </div>
            </form>

            <?php /* ── Tab: Design Tokens ──────────────────────────────────── */ ?>
        <?php elseif ($active_tab === 'tokens') : ?>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data" class="awb-form">
                <input type="hidden" name="action" value="awb_save_design_tokens">
                <?php wp_nonce_field('awb_save_design_tokens_nonce'); ?>

                <div class="awb-tokens-intro">
                    <p>These values are output as <code>:root</code> CSS custom properties on every page. Set them once per project — your patterns and CSS reference them automatically.</p>
                </div>

                <?php
                $tokens = [
                    'colors' => [
                        'label'  => 'Colors',
                        'fields' => [
                            'awb_token_color_primary'    => ['label' => 'Primary',    'type' => 'color', 'default' => '#1a1a2e'],
                            'awb_token_color_secondary'  => ['label' => 'Secondary',  'type' => 'color', 'default' => '#16213e'],
                            'awb_token_color_accent'     => ['label' => 'Accent',     'type' => 'color', 'default' => '#e94560'],
                            'awb_token_color_text'       => ['label' => 'Text',       'type' => 'color', 'default' => '#1a1a1a'],
                            'awb_token_color_bg'         => ['label' => 'Background', 'type' => 'color', 'default' => '#ffffff'],
                            'awb_token_color_muted'      => ['label' => 'Muted',      'type' => 'color', 'default' => '#6b7280'],
                            'awb_token_color_border'     => ['label' => 'Border',     'type' => 'color', 'default' => '#e5e7eb'],
                        ],
                    ],
                    'typography' => [
                        'label'  => 'Typography',
                        'fields' => [
                            'awb_token_font_heading'  => ['label' => 'Heading font',  'type' => 'text', 'default' => 'Georgia, serif',       'placeholder' => 'Georgia, serif'],
                            'awb_token_font_body'     => ['label' => 'Body font',     'type' => 'text', 'default' => 'system-ui, sans-serif', 'placeholder' => 'system-ui, sans-serif'],
                            'awb_token_font_mono'     => ['label' => 'Mono font',     'type' => 'text', 'default' => 'monospace',             'placeholder' => 'monospace'],
                            'awb_token_font_size_base' => ['label' => 'Base font size', 'type' => 'text', 'default' => '16px',                  'placeholder' => '16px'],
                            'awb_token_line_height'   => ['label' => 'Line height',   'type' => 'text', 'default' => '1.6',                   'placeholder' => '1.6'],
                        ],
                    ],
                    'custom-fonts' => [
                        'label'  => 'Custom Fonts',
                        'fields' => [
                            'awb_custom_font_regular' => ['label' => 'Regular (400)', 'type' => 'file', 'accept' => '.woff,.woff2,.ttf,.otf'],
                            'awb_custom_font_medium'  => ['label' => 'Medium (500)',  'type' => 'file', 'accept' => '.woff,.woff2,.ttf,.otf'],
                            'awb_custom_font_bold'    => ['label' => 'Bold (700)',    'type' => 'file', 'accept' => '.woff,.woff2,.ttf,.otf'],
                        ],
                    ],
                    'spacing' => [
                        'label'  => 'Spacing',
                        'fields' => [
                            'awb_token_space_xs'  => ['label' => 'XS',  'type' => 'text', 'default' => '0.25rem', 'placeholder' => '0.25rem'],
                            'awb_token_space_sm'  => ['label' => 'SM',  'type' => 'text', 'default' => '0.5rem',  'placeholder' => '0.5rem'],
                            'awb_token_space_md'  => ['label' => 'MD',  'type' => 'text', 'default' => '1rem',    'placeholder' => '1rem'],
                            'awb_token_space_lg'  => ['label' => 'LG',  'type' => 'text', 'default' => '2rem',    'placeholder' => '2rem'],
                            'awb_token_space_xl'  => ['label' => 'XL',  'type' => 'text', 'default' => '4rem',    'placeholder' => '4rem'],
                        ],
                    ],
                    'borders' => [
                        'label'  => 'Borders & Radius',
                        'fields' => [
                            'awb_token_radius_sm'   => ['label' => 'Radius SM',   'type' => 'text', 'default' => '4px',   'placeholder' => '4px'],
                            'awb_token_radius_md'   => ['label' => 'Radius MD',   'type' => 'text', 'default' => '8px',   'placeholder' => '8px'],
                            'awb_token_radius_lg'   => ['label' => 'Radius LG',   'type' => 'text', 'default' => '16px',  'placeholder' => '16px'],
                            'awb_token_radius_full' => ['label' => 'Radius Full', 'type' => 'text', 'default' => '9999px', 'placeholder' => '9999px'],
                        ],
                    ],
                    'layout' => [
                        'label'  => 'Layout',
                        'fields' => [
                            'awb_token_container_max'  => ['label' => 'Container max-width', 'type' => 'text', 'default' => '1200px', 'placeholder' => '1200px'],
                            'awb_token_container_pad'  => ['label' => 'Container padding',   'type' => 'text', 'default' => '1.5rem', 'placeholder' => '1.5rem'],
                            'awb_token_transition'     => ['label' => 'Transition default',  'type' => 'text', 'default' => '0.2s ease', 'placeholder' => '0.2s ease'],
                        ],
                    ],
                ];
                ?>

                <?php foreach ($tokens as $group_key => $group) : ?>
                    <?php if ($group_key === 'custom-fonts') : ?>
    </div><!-- .awb-form__section -->
    </form>

    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data" class="awb-form awb-form--fonts">
        <input type="hidden" name="action" value="awb_save_font_settings">
        <?php wp_nonce_field('awb_font_upload_nonce'); ?>

        <div class="awb-form__section">
            <div class="awb-form__section-header">
                <h2><?php echo esc_html($group['label']); ?></h2>
                <p>Upload custom font files. Supported formats: WOFF, WOFF2, TTF, OTF. Fonts are referenced via <code>--awb-font-custom</code>.</p>
            </div>
            <div class="awb-token-grid">
                <?php foreach ($group['fields'] as $option_name => $field) :
                            $font_type = str_replace('awb_custom_font_', '', $option_name);
                            $css_var   = '--awb-font-custom-' . $font_type;
                            $current   = get_option($option_name, '');
                ?>
                    <div class="awb-token-field awb-token-field--file">
                        <label for="<?php echo esc_attr($option_name); ?>">
                            <span class="awb-token-field__label"><?php echo esc_html($field['label']); ?></span>
                            <code class="awb-token-field__var"><?php echo esc_html($css_var); ?></code>
                        </label>
                        <div class="awb-file-input">
                            <input type="file"
                                name="<?php echo esc_attr($option_name); ?>"
                                id="<?php echo esc_attr($option_name); ?>"
                                accept="<?php echo esc_attr($field['accept'] ?? ''); ?>">
                            <?php if ($current) : ?>
                                <div class="awb-current-file">
                                    <span class="awb-current-file__icon">◎</span>
                                    <a href="<?php echo esc_url($current); ?>" target="_blank"><?php echo esc_html(basename($current)); ?></a>
                                    <button type="button" class="awb-btn awb-btn--danger awb-btn--sm awb-delete-font"
                                        data-font-type="<?php echo esc_attr($font_type); ?>"
                                        data-nonce="<?php echo wp_create_nonce('awb_delete_font_nonce'); ?>">Delete</button>
                                </div>
                            <?php else : ?>
                                <span class="awb-current-file awb-current-file--empty">No file uploaded</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="awb-form__actions">
                <?php submit_button('Upload Fonts', 'primary', 'submit', false, ['class' => 'awb-btn awb-btn--primary']); ?>
            </div>
        </div>
    </form>

    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data" class="awb-form">
        <input type="hidden" name="action" value="awb_save_design_tokens">
        <?php wp_nonce_field('awb_save_design_tokens_nonce'); ?>

    <?php else : ?>
        <div class="awb-form__section">
            <div class="awb-form__section-header">
                <h2><?php echo esc_html($group['label']); ?></h2>
            </div>
            <div class="awb-token-grid">
                <?php foreach ($group['fields'] as $option_name => $field) :
                            $value   = get_option($option_name, $field['default'] ?? '');
                            $css_var = str_replace(['awb_token_', '_'], ['--awb-', '-'], $option_name);
                ?>
                    <div class="awb-token-field">
                        <label for="<?php echo esc_attr($option_name); ?>">
                            <span class="awb-token-field__label"><?php echo esc_html($field['label']); ?></span>
                            <code class="awb-token-field__var"><?php echo esc_html($css_var); ?></code>
                        </label>
                        <?php if ($field['type'] === 'color') : ?>
                            <div class="awb-color-input">
                                <input type="color"
                                    id="<?php echo esc_attr($option_name); ?>_picker"
                                    value="<?php echo esc_attr($value); ?>"
                                    data-target="<?php echo esc_attr($option_name); ?>">
                                <input type="text"
                                    name="<?php echo esc_attr($option_name); ?>"
                                    id="<?php echo esc_attr($option_name); ?>"
                                    class="awb-color-hex"
                                    value="<?php echo esc_attr($value); ?>"
                                    maxlength="7"
                                    pattern="#[0-9A-Fa-f]{6}">
                            </div>
                        <?php else : ?>
                            <input type="text"
                                name="<?php echo esc_attr($option_name); ?>"
                                id="<?php echo esc_attr($option_name); ?>"
                                value="<?php echo esc_attr($value); ?>"
                                placeholder="<?php echo esc_attr($field['placeholder'] ?? ''); ?>">
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
<?php endforeach; ?>

<div class="awb-form__section awb-form__section--preview">
    <div class="awb-form__section-header">
        <h2>Generated output</h2>
        <p>This block is added to every frontend page automatically.</p>
    </div>
    <div class="awb-token-preview" id="awb-token-preview">
        <pre id="awb-token-output"><!-- generated by JS --></pre>
    </div>
</div>

<div class="awb-form__actions">
    <?php submit_button('Save Tokens', 'primary', 'submit', false, ['class' => 'awb-btn awb-btn--primary']); ?>
</div>
    </form>

    <?php /* ── Tab: Site Scaffold ───────────────────────────────────── */ ?>
<?php elseif ($active_tab === 'scaffold') : ?>

    <div class="awb-scaffold">
        <div class="awb-scaffold__intro">
            <h2>One-click site scaffold</h2>
            <p>Creates standard pages, assigns your preferred header/footer patterns, and sets up navigation — turning a blank WordPress install into a structured site instantly.</p>
        </div>

        <div class="awb-scaffold__grid">
            <?php
            $scaffolds = [
                [
                    'id'    => 'business',
                    'icon'  => '⬡',
                    'label' => 'Business',
                    'pages' => ['Home', 'About', 'Services', 'Contact'],
                    'desc'  => '4 pages, contact form pattern, services section',
                ],
                [
                    'id'    => 'portfolio',
                    'icon'  => '◈',
                    'label' => 'Portfolio',
                    'pages' => ['Home', 'Work', 'About', 'Contact'],
                    'desc'  => '4 pages, project grid pattern, hero section',
                ],
                [
                    'id'    => 'landing',
                    'icon'  => '✦',
                    'label' => 'Landing',
                    'pages' => ['Home'],
                    'desc'  => '1 full-page layout, all sections stacked',
                ],
            ];
            ?>
            <?php foreach ($scaffolds as $s) : ?>
                <div class="awb-scaffold-card">
                    <div class="awb-scaffold-card__icon"><?php echo $s['icon']; ?></div>
                    <h3><?php echo esc_html($s['label']); ?></h3>
                    <p><?php echo esc_html($s['desc']); ?></p>
                    <ul class="awb-scaffold-card__pages">
                        <?php foreach ($s['pages'] as $page) : ?>
                            <li><?php echo esc_html($page); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <button type="button"
                        class="awb-btn awb-btn--outline awb-scaffold-trigger"
                        data-scaffold="<?php echo esc_attr($s['id']); ?>"
                        data-nonce="<?php echo esc_attr(wp_create_nonce('awb_scaffold_nonce')); ?>">
                        Create scaffold
                    </button>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="awb-scaffold__options">
            <h3>Scaffold options</h3>
            <form method="post" action="options.php" class="awb-form awb-form--inline">
                <?php settings_fields('awb_scaffold_group'); ?>
                <div class="awb-toggle-grid">
                    <label class="awb-toggle-field">
                        <div class="awb-toggle-field__info">
                            <span class="awb-toggle-field__label">Set home page automatically</span>
                            <span class="awb-toggle-field__desc">Assigns the created Home page as the static front page in Settings → Reading.</span>
                        </div>
                        <div class="awb-toggle-switch">
                            <input type="hidden" name="awb_scaffold_set_homepage" value="0">
                            <input type="checkbox" name="awb_scaffold_set_homepage" value="1" <?php checked(get_option('awb_scaffold_set_homepage', 1), 1); ?>>
                            <span class="awb-toggle-switch__track"></span>
                        </div>
                    </label>
                    <label class="awb-toggle-field">
                        <div class="awb-toggle-field__info">
                            <span class="awb-toggle-field__label">Create navigation menu</span>
                            <span class="awb-toggle-field__desc">Builds a primary menu from the scaffolded pages and registers it in the Primary Menu location.</span>
                        </div>
                        <div class="awb-toggle-switch">
                            <input type="hidden" name="awb_scaffold_create_menu" value="0">
                            <input type="checkbox" name="awb_scaffold_create_menu" value="1" <?php checked(get_option('awb_scaffold_create_menu', 1), 1); ?>>
                            <span class="awb-toggle-switch__track"></span>
                        </div>
                    </label>
                    <label class="awb-toggle-field">
                        <div class="awb-toggle-field__info">
                            <span class="awb-toggle-field__label">Delete sample content</span>
                            <span class="awb-toggle-field__desc">Removes the default "Hello world!" post and "Sample Page" before scaffolding.</span>
                        </div>
                        <div class="awb-toggle-switch">
                            <input type="hidden" name="awb_scaffold_clean" value="0">
                            <input type="checkbox" name="awb_scaffold_clean" value="1" <?php checked(get_option('awb_scaffold_clean', 1), 1); ?>>
                            <span class="awb-toggle-switch__track"></span>
                        </div>
                    </label>
                </div>
                <?php submit_button('Save options', 'secondary', 'submit', false, ['class' => 'awb-btn awb-btn--outline']); ?>
            </form>
        </div>

        <div class="awb-scaffold__log" id="awb-scaffold-log" hidden>
            <h3>Log</h3>
            <ul id="awb-scaffold-log-list"></ul>
        </div>
    </div>

    <?php /* ── Tab: AI Generator ──────────────────────────────────────── */ ?>
<?php elseif ($active_tab === 'ai') : ?>

    <?php
            $has_api_key = !empty(get_option('awb_ai_api_key', ''));
    ?>

    <?php if (!$has_api_key) : ?>
        <div class="awb-notice awb-notice--warning">
            <span>&#9888;</span> No API key found. <a href="<?php echo esc_url($base_url . '&tab=css-js'); ?>">Add your Anthropic API key</a> in the CSS &amp; JS tab to use AI generation.
        </div>
    <?php endif; ?>

    <div class="awb-ai <?php echo !$has_api_key ? 'awb-ai--locked' : ''; ?>">
        <div class="awb-ai__intro">
            <h2>AI Content Generator</h2>
            <p>Describe the page or section you need. Claude will generate WordPress block markup — ready to paste into the editor.</p>
        </div>

        <div class="awb-ai__layout">
            <div class="awb-ai__controls">
                <div class="awb-form__section">
                    <div class="awb-form__section-header">
                        <h3>Business context <span class="awb-badge">Improves output</span></h3>
                        <p>Optional. Provide your business name and what you do — Claude uses this for realistic placeholder copy.</p>
                    </div>
                    <div class="awb-ai-context">
                        <div class="awb-field">
                            <label for="awb-ai-business-name">Business name</label>
                            <input type="text" id="awb-ai-business-name" placeholder="Vakman Builders" value="<?php echo esc_attr(get_option('awb_ai_business_name', '')); ?>" />
                        </div>
                        <div class="awb-field">
                            <label for="awb-ai-business-desc">What you do</label>
                            <input type="text" id="awb-ai-business-desc" placeholder="Residential construction and renovations in Paramaribo" value="<?php echo esc_attr(get_option('awb_ai_business_desc', '')); ?>" />
                        </div>
                        <button type="button" class="awb-btn awb-btn--ghost awb-btn--sm" id="awb-ai-save-context"
                            data-nonce="<?php echo esc_attr(wp_create_nonce('awb_save_ai_context_nonce')); ?>">
                            Save context
                        </button>
                    </div>
                </div>

                <div class="awb-form__section">
                    <div class="awb-form__section-header">
                        <h3>Generate content</h3>
                    </div>

                    <div class="awb-field">
                        <label for="awb-ai-prompt">Describe what you need</label>
                        <textarea id="awb-ai-prompt" class="awb-editor" rows="5" placeholder="A hero section for a construction company with a bold headline, short subtext, and a 'Get a quote' button"></textarea>
                    </div>

                    <div class="awb-ai-options">
                        <div class="awb-field">
                            <label for="awb-ai-mode">Output mode</label>
                            <select id="awb-ai-mode">
                                <option value="blocks">WordPress block markup</option>
                                <option value="html">Raw HTML</option>
                                <option value="copy">Copy only (no markup)</option>
                            </select>
                        </div>
                        <div class="awb-field">
                            <label for="awb-ai-tone">Tone</label>
                            <select id="awb-ai-tone">
                                <option value="professional">Professional</option>
                                <option value="friendly">Friendly &amp; approachable</option>
                                <option value="bold">Bold &amp; confident</option>
                                <option value="minimal">Minimal &amp; clean</option>
                            </select>
                        </div>
                        <div class="awb-field">
                            <label for="awb-ai-template">Use block template</label>
                            <select id="awb-ai-template">
                                <option value="">— None —</option>
                                <?php
                                $templates_dir = plugin_dir_path(dirname(__FILE__)) . 'block-templates/';
                                if (is_dir($templates_dir)) {
                                    foreach (glob($templates_dir . '*.html') as $tpl) {
                                        $name = basename($tpl, '.html');
                                        echo '<option value="' . esc_attr($name) . '">' . esc_html(ucwords(str_replace('-', ' ', $name))) . '</option>';
                                    }
                                }
                                ?>
                            </select>
                        </div>
                    </div>

                    <div class="awb-ai__actions">
                        <button type="button" class="awb-btn awb-btn--primary" id="awb-ai-generate"
                            data-nonce="<?php echo esc_attr(wp_create_nonce('awb_ai_generate_nonce')); ?>"
                            <?php echo !$has_api_key ? 'disabled' : ''; ?>>
                            <span class="awb-ai-generate__icon">◎</span>
                            Generate
                        </button>
                        <button type="button" class="awb-btn awb-btn--ghost" id="awb-ai-clear">Clear</button>
                    </div>
                </div>
            </div>

            <div class="awb-ai__output">
                <div class="awb-editor-wrap">
                    <div class="awb-editor-toolbar">
                        <span class="awb-editor-lang">Output</span>
                        <span class="awb-ai-status" id="awb-ai-status-label"></span>
                        <button type="button" class="awb-editor-btn" id="awb-ai-copy-output" disabled>Copy</button>
                        <button type="button" class="awb-editor-btn awb-editor-btn--primary" id="awb-ai-insert-output" disabled>Insert into editor</button>
                    </div>
                    <textarea id="awb-ai-output" class="awb-editor awb-editor--output" rows="24" readonly placeholder="Generated block markup will appear here..."></textarea>
                </div>

                <div class="awb-ai__history" id="awb-ai-history">
                    <h4>Recent generations <button type="button" class="awb-btn awb-btn--ghost awb-btn--xs" id="awb-ai-clear-history">Clear all</button></h4>
                    <ul id="awb-ai-history-list">
                        <li class="awb-ai-history__empty">No generations yet this session.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <?php /* ── Tab: Pattern Library ───────────────────────────────────── */ ?>
<?php elseif ($active_tab === 'patterns') : ?>

    <?php
            $patterns_base = plugin_dir_path(dirname(__FILE__)) . 'patterns/';
            $all_patterns  = [];

            if (is_dir($patterns_base)) {
                $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($patterns_base));
                foreach ($iterator as $file) {
                    if ($file->getExtension() !== 'php') continue;
                    $content = file_get_contents($file->getPathname());
                    preg_match('/Title:\s*(.+)/i', $content, $title_match);
                    preg_match('/Slug:\s*(.+)/i', $content, $slug_match);
                    preg_match('/Categories:\s*(.+)/i', $content, $cat_match);
                    preg_match('/CSS:\s*(.+)/i', $content, $css_match);
                    preg_match('/JS:\s*(.+)/i', $content, $js_match);
                    preg_match('/Description:\s*(.+)/i', $content, $desc_match);

                    $relative = str_replace($patterns_base, '', $file->getPathname());
                    $folder   = dirname($relative);

                    $all_patterns[] = [
                        'file'       => $file->getPathname(),
                        'relative'   => $relative,
                        'folder'     => $folder === '.' ? 'root' : $folder,
                        'title'      => isset($title_match[1]) ? trim($title_match[1]) : basename($file->getPathname(), '.php'),
                        'slug'       => isset($slug_match[1]) ? trim($slug_match[1]) : '',
                        'categories' => isset($cat_match[1]) ? array_map('trim', explode(',', $cat_match[1])) : [],
                        'has_css'    => !empty($css_match[1]),
                        'has_js'     => !empty($js_match[1]),
                        'description' => isset($desc_match[1]) ? trim($desc_match[1]) : '',
                    ];
                }
            }

            $folders = array_unique(array_column($all_patterns, 'folder'));
            sort($folders);
    ?>

    <div class="awb-patterns">
        <div class="awb-patterns__toolbar">
            <h2>Pattern Library <span class="awb-badge"><?php echo count($all_patterns); ?> patterns</span></h2>
            <div class="awb-patterns__filter">
                <input type="search" id="awb-pattern-search" placeholder="Search patterns…" class="awb-search-input">
                <div class="awb-patterns__filter-groups">
                    <button type="button" class="awb-filter-btn is-active" data-filter="all">All</button>
                    <?php foreach ($folders as $folder) : ?>
                        <button type="button" class="awb-filter-btn" data-filter="<?php echo esc_attr($folder); ?>">
                            <?php echo esc_html(ucfirst($folder)); ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <?php if (empty($all_patterns)) : ?>
            <div class="awb-empty-state">
                <span class="awb-empty-state__icon">▦</span>
                <p>No patterns found in <code>patterns/</code>. Drop a <code>.php</code> file there to get started.</p>
            </div>
        <?php else : ?>
            <div class="awb-patterns__grid" id="awb-patterns-grid">
                <?php foreach ($all_patterns as $pattern) : ?>
                    <div class="awb-pattern-card" data-folder="<?php echo esc_attr($pattern['folder']); ?>" data-title="<?php echo esc_attr(strtolower($pattern['title'])); ?>">
                        <div class="awb-pattern-card__header">
                            <span class="awb-pattern-card__folder"><?php echo esc_html($pattern['folder']); ?></span>
                            <div class="awb-pattern-card__badges">
                                <?php if ($pattern['has_css']) : ?><span class="awb-tag awb-tag--css">CSS</span><?php endif; ?>
                                <?php if ($pattern['has_js']) : ?><span class="awb-tag awb-tag--js">JS</span><?php endif; ?>
                            </div>
                        </div>
                        <h3 class="awb-pattern-card__title"><?php echo esc_html($pattern['title']); ?></h3>
                        <?php if ($pattern['description']) : ?>
                            <p class="awb-pattern-card__desc"><?php echo esc_html($pattern['description']); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($pattern['categories'])) : ?>
                            <div class="awb-pattern-card__cats">
                                <?php foreach ($pattern['categories'] as $cat) : ?>
                                    <span class="awb-tag"><?php echo esc_html($cat); ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <div class="awb-pattern-card__footer">
                            <code class="awb-pattern-card__file"><?php echo esc_html($pattern['relative']); ?></code>
                            <?php if ($pattern['slug']) : ?>
                                <button type="button" class="awb-btn awb-btn--ghost awb-btn--sm awb-copy-slug"
                                    data-slug="<?php echo esc_attr($pattern['slug']); ?>">Copy slug</button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php /* ── Tab: About ───────────────────────────────────────────── */ ?>
<?php elseif ($active_tab === 'info') : ?>

    <div class="awb-about">
        <div class="awb-about__hero">
            <span class="awb-about__wordmark">AWB Starter</span>
            <span class="awb-about__version">v<?php echo esc_html(AWB_VERSION); ?></span>
        </div>
        <div class="awb-about__grid">
            <div class="awb-about__card">
                <h3>What it does</h3>
                <p>AWB Starter gives you a library of block patterns, design tokens, and asset loading that only fires on pages that need it — so you can build complete websites in hours, not days.</p>
            </div>
            <div class="awb-about__card">
                <h3>Adding patterns</h3>
                <p>Drop a <code>.php</code> file into <code>patterns/</code>. Set <code>Title</code>, <code>Slug</code>, and <code>Categories</code> in the file header. Declare <code>CSS:</code> and <code>JS:</code> paths to load assets only when that pattern is in use.</p>
            </div>
            <div class="awb-about__card">
                <h3>Design tokens</h3>
                <p>Set brand colors, fonts, and spacing once in the Tokens tab. The plugin outputs them as <code>--awb-*</code> CSS custom properties on every page, so all your patterns stay in sync.</p>
            </div>
            <div class="awb-about__card">
                <h3>AI Generator</h3>
                <p>Add your Anthropic API key in the CSS &amp; JS tab, then use the AI Generator tab to turn a plain-English description into WordPress block markup — with business context for realistic copy.</p>
            </div>
            <div class="awb-about__card">
                <h3>Block templates</h3>
                <p>HTML scaffolds in <code>block-templates/</code> are selectable in the AI generator as a structural base, letting Claude fill in copy without inventing layout structure.</p>
            </div>
            <div class="awb-about__card">
                <h3>Pattern Library tab</h3>
                <p>Browses all registered patterns, shows which ones load CSS/JS, and lets you filter by folder or search by name — handy for larger plugin setups with many patterns.</p>
            </div>
        </div>
        <div class="awb-about__meta">
            <span>Made by <strong>WLM+</strong></span>
            <span>WordPress <?php echo esc_html(get_bloginfo('version')); ?></span>
            <span>PHP <?php echo esc_html(PHP_VERSION); ?></span>
            <span>AWB v<?php echo esc_html(AWB_VERSION); ?></span>
        </div>

        <div class="awb-about__system">
            <h3>System info</h3>
            <div class="awb-system-grid">
                <?php
                $sys_checks = [
                    ['label' => 'PHP version', 'value' => PHP_VERSION, 'ok' => version_compare(PHP_VERSION, '8.0', '>='), 'req' => '≥ 8.0'],
                    ['label' => 'WordPress', 'value' => get_bloginfo('version'), 'ok' => version_compare(get_bloginfo('version'), '6.0', '>='), 'req' => '≥ 6.0'],
                    ['label' => 'Anthropic API key', 'value' => get_option('awb_ai_api_key') ? 'Configured' : 'Missing', 'ok' => !empty(get_option('awb_ai_api_key')), 'req' => 'Required for AI'],
                    ['label' => 'patterns/ writable', 'value' => is_writable(plugin_dir_path(dirname(__FILE__)) . 'patterns/') ? 'Writable' : 'Not writable', 'ok' => is_writable(plugin_dir_path(dirname(__FILE__)) . 'patterns/'), 'req' => 'For scaffolding'],
                    ['label' => 'uploads/ writable', 'value' => wp_is_writable(wp_upload_dir()['basedir']) ? 'Writable' : 'Not writable', 'ok' => wp_is_writable(wp_upload_dir()['basedir']), 'req' => 'For font uploads'],
                ];
                foreach ($sys_checks as $check) :
                ?>
                    <div class="awb-system-row">
                        <span class="awb-system-row__status <?php echo $check['ok'] ? 'awb-system-row__status--ok' : 'awb-system-row__status--warn'; ?>">
                            <?php echo $check['ok'] ? '✓' : '⚠'; ?>
                        </span>
                        <span class="awb-system-row__label"><?php echo esc_html($check['label']); ?></span>
                        <span class="awb-system-row__value"><?php echo esc_html($check['value']); ?></span>
                        <span class="awb-system-row__req"><?php echo esc_html($check['req']); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

<?php endif; ?>

</div><!-- .awb-settings-body -->

</div><!-- .awb-settings-wrap -->