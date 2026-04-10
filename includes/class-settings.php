<?php

/**
 * Admin pages and settings registration.
 *
 * @package AWBStarter
 */

if (! defined('ABSPATH')) {
    exit;
}

class AWB_Settings
{
    public function __construct()
    {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
    }

    public function add_admin_menu(): void
    {
        add_menu_page(
            __('AWB Starter', 'awb-starter'),
            'AWB Starter',
            'manage_options',
            'awb-starter',
            [$this, 'render_settings_page'],
            'dashicons-layout',
            60
        );

        add_submenu_page(
            'awb-starter',
            __('Custom CSS & JS', 'awb-starter'),
            __('Custom CSS & JS', 'awb-starter'),
            'manage_options',
            'awb-starter',
            [$this, 'render_settings_page']
        );

        add_submenu_page(
            'awb-starter',
            __('Pattern Library', 'awb-starter'),
            __('Pattern Library', 'awb-starter'),
            'manage_options',
            'awb-starter-library',
            [$this, 'render_library_page']
        );
    }

    public function render_settings_page(): void
    {
        if (! current_user_can('manage_options')) {
            return;
        }

        $tpl = AWB_PLUGIN_PATH . 'templates/admin-settings.php';
        if (file_exists($tpl)) {
            include $tpl;
            return;
        }

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('AWB – Custom CSS & JS', 'awb-starter') . '</h1>';
        echo '<form method="post" action="options.php">';
        settings_fields('awb_starter_group');
        do_settings_sections('awb_starter_group');
        echo '<table class="form-table" role="presentation">';
        echo '<tr><th scope="row"><label for="awb_ai_api_key">' . esc_html__('Anthropic API Key', 'awb-starter') . '</label></th><td><input type="password" name="awb_ai_api_key" id="awb_ai_api_key" class="regular-text" value="' . esc_attr(get_option('awb_ai_api_key', '')) . '" /><p class="description">' . esc_html__('Your Anthropic API key for AI content generation', 'awb-starter') . '</p></td></tr>';
        echo '<tr><th scope="row"><label for="awb_custom_css">' . esc_html__('Custom CSS', 'awb-starter') . '</label></th><td><textarea name="awb_custom_css" id="awb_custom_css" rows="12" class="large-text code">' . esc_textarea(get_option('awb_custom_css', '')) . '</textarea></td></tr>';
        echo '<tr><th scope="row"><label for="awb_custom_js">' . esc_html__('Custom JS', 'awb-starter') . '</label></th><td><textarea name="awb_custom_js" id="awb_custom_js" rows="12" class="large-text code">' . esc_textarea(get_option('awb_custom_js', '')) . '</textarea></td></tr>';
        echo '</table>';
        submit_button();
        echo '</form>';
        echo '</div>';
    }

    public function render_library_page(): void
    {
        if (! current_user_can('manage_options')) {
            return;
        }

        $tpl = AWB_PLUGIN_PATH . 'templates/admin-library.php';
        if (file_exists($tpl)) {
            include $tpl;
            return;
        }

        echo '<div class="wrap"><h1>' . esc_html__('Pattern Library — coming soon', 'awb-starter') . '</h1></div>';
    }

    public function register_settings(): void
    {
        $group = 'awb_starter_group';

        register_setting($group, 'awb_custom_css', [
            'sanitize_callback' => 'wp_strip_all_tags',
            'default'           => '',
        ]);
        register_setting($group, 'awb_custom_js', [
            'sanitize_callback' => 'wp_strip_all_tags',
            'default'           => '',
        ]);
        register_setting($group, 'awb_ai_api_key', [
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '',
        ]);
    }
}
