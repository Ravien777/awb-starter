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
    'css-js'  => ['label' => 'CSS &amp; JS',      'icon' => '✦'],
    'tokens'  => ['label' => 'Design Tokens',     'icon' => '◈'],
    'scaffold' => ['label' => 'Site Scaffold',    'icon' => '⬡'],
    'info'    => ['label' => 'About',              'icon' => '◇'],
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

        <?php /* ── Tab: CSS & JS ──────────────────────────────────────── */ ?>
        <?php if ($active_tab === 'css-js') : ?>

            <form method="post" action="options.php" class="awb-form">
                <?php settings_fields('awb_starter_group'); ?>

                <div class="awb-form__section">
                    <div class="awb-form__section-header">
                        <h2>Anthropic API Key</h2>
                        <p>Your Anthropic API key for AI content generation. This will be stored securely and used only for AI requests.</p>
                    </div>
                    <div class="awb-editor-wrap">
                        <div class="awb-editor-toolbar">
                            <span class="awb-editor-lang">API</span>
                            <button type="button" class="awb-editor-btn" data-action="clear" data-target="awb_ai_api_key">Clear</button>
                            <button type="button" class="awb-editor-btn" data-action="copy" data-target="awb_ai_api_key">Copy</button>
                        </div>
                        <input type="password" name="awb_ai_api_key" id="awb_ai_api_key" class="awb-editor" value="<?php echo esc_attr(get_option('awb_ai_api_key', '')); ?>" placeholder="sk-ant-..." />
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
                        ],
                    ],
                    'typography' => [
                        'label'  => 'Typography',
                        'fields' => [
                            'awb_token_font_heading' => ['label' => 'Heading font',  'type' => 'text', 'default' => 'Georgia, serif',       'placeholder' => 'Georgia, serif'],
                            'awb_token_font_body'    => ['label' => 'Body font',     'type' => 'text', 'default' => 'system-ui, sans-serif', 'placeholder' => 'system-ui, sans-serif'],
                            'awb_token_font_mono'    => ['label' => 'Mono font',     'type' => 'text', 'default' => 'monospace',             'placeholder' => 'monospace'],
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
                            'awb_token_space_xs' => ['label' => 'XS',  'type' => 'text', 'default' => '0.25rem', 'placeholder' => '0.25rem'],
                            'awb_token_space_sm' => ['label' => 'SM',  'type' => 'text', 'default' => '0.5rem',  'placeholder' => '0.5rem'],
                            'awb_token_space_md' => ['label' => 'MD',  'type' => 'text', 'default' => '1rem',    'placeholder' => '1rem'],
                            'awb_token_space_lg' => ['label' => 'LG',  'type' => 'text', 'default' => '2rem',    'placeholder' => '2rem'],
                            'awb_token_space_xl' => ['label' => 'XL',  'type' => 'text', 'default' => '4rem',    'placeholder' => '4rem'],
                        ],
                    ],
                    'borders' => [
                        'label'  => 'Borders & Radius',
                        'fields' => [
                            'awb_token_radius_sm' => ['label' => 'Radius SM', 'type' => 'text', 'default' => '4px',  'placeholder' => '4px'],
                            'awb_token_radius_md' => ['label' => 'Radius MD', 'type' => 'text', 'default' => '8px',  'placeholder' => '8px'],
                            'awb_token_radius_lg' => ['label' => 'Radius LG', 'type' => 'text', 'default' => '16px', 'placeholder' => '16px'],
                        ],
                    ],
                ];
                ?>

                <?php foreach ($tokens as $group_key => $group) : ?>
                    <?php if ($group_key === 'custom-fonts') : ?>
    </div> <!-- Close previous form -->
</div> <!-- Close previous section -->

<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data" class="awb-form awb-form--fonts">
    <input type="hidden" name="action" value="awb_save_font_settings">
    <?php wp_nonce_field('awb_font_upload_nonce'); ?>

    <div class="awb-form__section">
        <div class="awb-form__section-header">
            <h2><?php echo esc_html($group['label']); ?></h2>
            <p>Upload custom font files to use in your design tokens. Supported formats: WOFF, WOFF2, TTF, OTF.</p>
        </div>
        <div class="awb-token-grid">
            <?php foreach ($group['fields'] as $option_name => $field) :
                            $value = get_option($option_name, $field['default']);
                            $css_var = str_replace(['awb_token_', '_'], ['--awb-', '-'], $option_name);
                            $font_type = str_replace('awb_custom_font_', '', $option_name);
            ?>
                <div class="awb-token-field">
                    <label for="<?php echo esc_attr($option_name); ?>">
                        <span class="awb-token-field__label"><?php echo esc_html($field['label']); ?></span>
                        <code class="awb-token-field__var"><?php echo esc_html($css_var); ?></code>
                    </label>
                    <div class="awb-file-input">
                        <input type="file"
                            name="<?php echo esc_attr($option_name); ?>"
                            id="<?php echo esc_attr($option_name); ?>"
                            accept="<?php echo esc_attr($field['accept'] ?? ''); ?>"
                            style="margin-bottom: 8px;">
                        <?php
                            $current_file = get_option($option_name, '');
                            if ($current_file) :
                        ?>
                            <div class="awb-current-file">
                                <strong>Current file:</strong>
                                <a href="<?php echo esc_url($current_file); ?>" target="_blank"><?php echo esc_html(basename($current_file)); ?></a>
                                <button type="button" class="awb-delete-font" data-font-type="<?php echo esc_attr($font_type); ?>" data-nonce="<?php echo wp_create_nonce('awb_delete_font_nonce'); ?>">
                                    Delete
                                </button>
                            </div>
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
                            $value = get_option($option_name, $field['default']);
                            $css_var = str_replace(['awb_token_', '_'], ['--awb-', '-'], $option_name);
            ?>
                <div class="awb-token-field">
                    <label for="<?php echo esc_attr($option_name); ?>">
                        <span class="awb-token-field__label"><?php echo esc_html($field['label']); ?></span>
                        <code class="awb-token-field__var"><?php echo esc_html($css_var); ?></code>
                    </label>
                    <?php if ($field['type'] === 'color') : ?>
                        <div class="awb-color-input">
                            <input type="color" id="<?php echo esc_attr($option_name); ?>_picker"
                                value="<?php echo esc_attr($value); ?>"
                                data-target="<?php echo esc_attr($option_name); ?>">
                            <input type="text" name="<?php echo esc_attr($option_name); ?>"
                                id="<?php echo esc_attr($option_name); ?>"
                                class="awb-color-hex"
                                value="<?php echo esc_attr($value); ?>"
                                maxlength="7"
                                pattern="#[0-9A-Fa-f]{6}">
                        </div>
                    <?php elseif ($field['type'] === 'file') : ?>
                        <div class="awb-file-input">
                            <input type="file"
                                name="<?php echo esc_attr($option_name); ?>"
                                id="<?php echo esc_attr($option_name); ?>"
                                accept="<?php echo esc_attr($field['accept'] ?? ''); ?>"
                                style="margin-bottom: 8px;">
                            <?php
                                $current_file = get_option($option_name, '');
                                if ($current_file) :
                                    $file_url = wp_get_attachment_url($current_file);
                                    if ($file_url) :
                            ?>
                                    <div class="awb-current-file">
                                        <strong>Current file:</strong>
                                        <a href="<?php echo esc_url($file_url); ?>" target="_blank"><?php echo esc_html(basename($file_url)); ?></a>
                                        <input type="hidden" name="<?php echo esc_attr($option_name); ?>_current" value="<?php echo esc_attr($current_file); ?>">
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
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

        <div class="awb-scaffold__log" id="awb-scaffold-log" hidden>
            <h3>Log</h3>
            <ul id="awb-scaffold-log-list"></ul>
        </div>
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
                <h3>Coming next</h3>
                <p>AI content generation — a prompt-to-blocks tool that uses the Anthropic API to draft page copy and output it directly as block markup, ready to drop into the editor.</p>
            </div>
        </div>
        <div class="awb-about__meta">
            <span>Made by <strong>WLM+</strong></span>
            <span>WordPress <?php echo esc_html(get_bloginfo('version')); ?></span>
            <span>PHP <?php echo esc_html(PHP_VERSION); ?></span>
        </div>
    </div>

<?php endif; ?>

</div><!-- .awb-settings-body -->

</div><!-- .awb-settings-wrap -->