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
        add_action('admin_post_awb_save_design_tokens', [$this, 'save_design_tokens']);
        add_action('admin_post_awb_save_font_settings', [$this, 'handle_font_upload']);
        add_action('wp_ajax_awb_delete_font', [$this, 'delete_font_file']);
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
            __('Dashboard', 'awb-starter'),
            __('Dashboard', 'awb-starter'),
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
        echo '<h1>' . esc_html__('AWB – Dashboard', 'awb-starter') . '</h1>';
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

        // Design token settings
        $token_settings = [
            // Colors
            'awb_token_color_primary',
            'awb_token_color_secondary',
            'awb_token_color_accent',
            'awb_token_color_text',
            'awb_token_color_bg',
            // Typography
            'awb_token_font_heading',
            'awb_token_font_body',
            'awb_token_font_mono',
            // Custom Fonts
            'awb_custom_font_regular',
            'awb_custom_font_medium',
            'awb_custom_font_bold',
            // Spacing
            'awb_token_space_xs',
            'awb_token_space_sm',
            'awb_token_space_md',
            'awb_token_space_lg',
            'awb_token_space_xl',
            // Borders & Radius
            'awb_token_radius_sm',
            'awb_token_radius_md',
            'awb_token_radius_lg',
        ];

        foreach ($token_settings as $setting) {
            register_setting($group, $setting, [
                'sanitize_callback' => 'sanitize_text_field',
                'default'           => '',
            ]);
        }
    }

    public function save_design_tokens(): void
    {
        if (! current_user_can('manage_options') || ! wp_verify_nonce($_POST['_wpnonce'] ?? '', 'awb_save_design_tokens_nonce')) {
            wp_die(__('Security check failed', 'awb-starter'));
        }

        $token_settings = [
            // Colors
            'awb_token_color_primary',
            'awb_token_color_secondary',
            'awb_token_color_accent',
            'awb_token_color_text',
            'awb_token_color_bg',
            // Typography
            'awb_token_font_heading',
            'awb_token_font_body',
            'awb_token_font_mono',
            // Spacing
            'awb_token_space_xs',
            'awb_token_space_sm',
            'awb_token_space_md',
            'awb_token_space_lg',
            'awb_token_space_xl',
            // Borders & Radius
            'awb_token_radius_sm',
            'awb_token_radius_md',
            'awb_token_radius_lg',
        ];

        foreach ($token_settings as $setting) {
            if (isset($_POST[$setting])) {
                update_option($setting, sanitize_text_field($_POST[$setting]));
            }
        }

        wp_redirect(add_query_arg([
            'page' => 'awb-starter',
            'tab' => 'tokens',
            'updated' => 'true'
        ], admin_url('admin.php')));
        exit;
    }

    public function delete_font_file(): void
    {
        if (! current_user_can('manage_options') || ! wp_verify_nonce($_POST['nonce'] ?? '', 'awb_delete_font_nonce')) {
            wp_send_json_error(['message' => __('Security check failed', 'awb-starter')]);
        }

        $font_type = sanitize_text_field($_POST['font_type'] ?? '');
        $allowed_types = ['regular', 'medium', 'bold'];

        if (! in_array($font_type, $allowed_types)) {
            wp_send_json_error(['message' => __('Invalid font type', 'awb-starter')]);
        }

        $option_name = 'awb_custom_font_' . $font_type;
        $font_url = get_option($option_name);

        if ($font_url) {
            // Get the attachment ID from URL
            $attachment_id = attachment_url_to_postid($font_url);
            if ($attachment_id) {
                wp_delete_attachment($attachment_id, true);
            }
            delete_option($option_name);
        }

        wp_send_json_success(['message' => __('Font deleted successfully', 'awb-starter')]);
    }

    public function handle_font_upload(): void
    {
        if (! current_user_can('manage_options') || ! wp_verify_nonce($_POST['_wpnonce'] ?? '', 'awb_font_upload_nonce')) {
            wp_die(__('Security check failed', 'awb-starter'));
        }

        $font_types = ['regular', 'medium', 'bold'];
        $uploaded_fonts = [];

        foreach ($font_types as $type) {
            $file_key = 'awb_custom_font_' . $type;

            if (! empty($_FILES[$file_key]['name'])) {
                // Validate file type
                $allowed_types = ['ttf', 'otf', 'woff', 'woff2'];
                $file_info = pathinfo($_FILES[$file_key]['name']);
                $file_ext = strtolower($file_info['extension'] ?? '');

                if (! in_array($file_ext, $allowed_types)) {
                    wp_die(__('Invalid file type. Only TTF, OTF, WOFF, and WOFF2 files are allowed.', 'awb-starter'));
                }

                // Handle upload
                if (! function_exists('wp_handle_upload')) {
                    require_once(ABSPATH . 'wp-admin/includes/file.php');
                }

                $upload_overrides = [
                    'test_form' => false,
                    'upload_error_handler' => function ($file, $message) {
                        wp_die(__('File upload error: ', 'awb-starter') . $message);
                    }
                ];

                $uploaded_file = wp_handle_upload($_FILES[$file_key], $upload_overrides);

                if (isset($uploaded_file['error'])) {
                    wp_die(__('Upload error: ', 'awb-starter') . $uploaded_file['error']);
                }

                // Create attachment
                $attachment = [
                    'guid'           => $uploaded_file['url'],
                    'post_mime_type' => $uploaded_file['type'],
                    'post_title'     => sanitize_file_name($file_info['filename']),
                    'post_content'   => '',
                    'post_status'    => 'inherit'
                ];

                $attachment_id = wp_insert_attachment($attachment, $uploaded_file['file']);

                if (! is_wp_error($attachment_id)) {
                    require_once(ABSPATH . 'wp-admin/includes/image.php');
                    $attachment_data = wp_generate_attachment_metadata($attachment_id, $uploaded_file['file']);
                    wp_update_attachment_metadata($attachment_id, $attachment_data);

                    update_option('awb_custom_font_' . $type, $uploaded_file['url']);
                    $uploaded_fonts[] = ucfirst($type) . ' font uploaded successfully';
                }
            }
        }

        // Redirect back with success message
        $redirect_url = add_query_arg([
            'page' => 'awb-starter',
            'tab' => 'tokens',
            'fonts_updated' => 'true'
        ], admin_url('admin.php'));

        if (! empty($uploaded_fonts)) {
            $redirect_url = add_query_arg('messages', urlencode(implode(', ', $uploaded_fonts)), $redirect_url);
        }

        wp_redirect($redirect_url);
        exit;
    }
}
