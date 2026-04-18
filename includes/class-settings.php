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
        $tpl = AWB_PLUGIN_PATH . 'admin/admin-settings.php';
        if (file_exists($tpl)) {
            include $tpl;
            return;
        }
        echo '<div class="wrap"><h1>' . esc_html__('AWB – Dashboard', 'awb-starter') . '</h1></div>';
    }

    public function render_library_page(): void
    {
        if (! current_user_can('manage_options')) {
            return;
        }
        $tpl = AWB_PLUGIN_PATH . 'admin/admin-library.php';
        if (file_exists($tpl)) {
            include $tpl;
            return;
        }
        echo '<div class="wrap"><h1>' . esc_html__('Pattern Library', 'awb-starter') . '</h1></div>';
    }

    public function register_settings(): void
    {
        $group = 'awb_starter_group';

        // Core settings
        register_setting($group, 'awb_custom_css', ['sanitize_callback' => 'wp_strip_all_tags', 'default' => '']);
        register_setting($group, 'awb_custom_js',  ['sanitize_callback' => 'wp_strip_all_tags', 'default' => '']);

        // AI Settings (Fixed: Now properly registered so options.php saves them)
        register_setting($group, 'awb_ai_provider', ['sanitize_callback' => 'sanitize_text_field', 'default' => 'anthropic']);
        if (class_exists('AWB_AI_Generator')) {
            foreach (AWB_AI_Generator::get_providers() as $slug => $_) {
                register_setting($group, 'awb_ai_' . $slug . '_key', [
                    'sanitize_callback' => 'sanitize_text_field',
                    'default'           => '',
                ]);
            }
        }

        // Design token settings
        $token_settings = [
            'awb_token_color_primary',
            'awb_token_color_secondary',
            'awb_token_color_accent',
            'awb_token_color_text',
            'awb_token_color_bg',
            'awb_token_color_muted',
            'awb_token_color_border',
            'awb_token_font_heading',
            'awb_token_font_body',
            'awb_token_font_mono',
            'awb_token_font_size_base',
            'awb_token_line_height',
            'awb_custom_font_regular',
            'awb_custom_font_medium',
            'awb_custom_font_bold',
            'awb_token_space_xs',
            'awb_token_space_sm',
            'awb_token_space_md',
            'awb_token_space_lg',
            'awb_token_space_xl',
            'awb_token_radius_sm',
            'awb_token_radius_md',
            'awb_token_radius_lg',
            'awb_token_radius_full',
            'awb_token_container_max',
            'awb_token_container_pad',
            'awb_token_transition',
            'awb_defer_js',
            'awb_minify_css',
            'awb_disable_frontend_css',
        ];
        foreach ($token_settings as $setting) {
            register_setting($group, $setting, ['sanitize_callback' => 'sanitize_text_field', 'default' => '']);
        }

        // Scaffold settings
        $scaffold_settings = ['awb_scaffold_set_homepage', 'awb_scaffold_create_menu', 'awb_scaffold_clean'];
        foreach ($scaffold_settings as $setting) {
            register_setting('awb_scaffold_group', $setting, ['sanitize_callback' => 'sanitize_text_field', 'default' => '']);
        }
    }

    public function save_design_tokens(): void
    {
        if (! current_user_can('manage_options') || ! wp_verify_nonce($_POST['_wpnonce'] ?? '', 'awb_save_design_tokens_nonce')) {
            wp_die(__('Security check failed', 'awb-starter'));
        }
        $token_settings = [
            'awb_token_color_primary',
            'awb_token_color_secondary',
            'awb_token_color_accent',
            'awb_token_color_text',
            'awb_token_color_bg',
            'awb_token_color_muted',
            'awb_token_color_border',
            'awb_token_font_heading',
            'awb_token_font_body',
            'awb_token_font_mono',
            'awb_token_font_size_base',
            'awb_token_line_height',
            'awb_token_space_xs',
            'awb_token_space_sm',
            'awb_token_space_md',
            'awb_token_space_lg',
            'awb_token_space_xl',
            'awb_token_radius_sm',
            'awb_token_radius_md',
            'awb_token_radius_lg',
            'awb_token_radius_full',
            'awb_token_container_max',
            'awb_token_container_pad',
            'awb_token_transition',
        ];
        foreach ($token_settings as $setting) {
            if (isset($_POST[$setting])) {
                update_option($setting, sanitize_text_field($_POST[$setting]));
            }
        }
        wp_redirect(add_query_arg(['page' => 'awb-starter', 'tab' => 'tokens', 'updated' => 'true'], admin_url('admin.php')));
        exit;
    }

    public function delete_font_file(): void
    {
        if (! current_user_can('manage_options') || ! wp_verify_nonce($_POST['nonce'] ?? '', 'awb_delete_font_nonce')) {
            wp_send_json_error(['message' => __('Security check failed', 'awb-starter')]);
        }
        $font_type = sanitize_text_field($_POST['font_type'] ?? '');
        if (! in_array($font_type, ['regular', 'medium', 'bold'], true)) {
            wp_send_json_error(['message' => __('Invalid font type', 'awb-starter')]);
        }
        $option_name = 'awb_custom_font_' . $font_type;
        $font_url = get_option($option_name);
        if ($font_url) {
            $attachment_id = attachment_url_to_postid($font_url);
            if ($attachment_id) wp_delete_attachment($attachment_id, true);
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
                $allowed_types = ['ttf', 'otf', 'woff', 'woff2'];
                $file_info = pathinfo($_FILES[$file_key]['name']);
                $file_ext = strtolower($file_info['extension'] ?? '');
                if (! in_array($file_ext, $allowed_types)) {
                    wp_die(__('Invalid file type. Only TTF, OTF, WOFF, and WOFF2 files are allowed.', 'awb-starter'));
                }
                if (! function_exists('wp_handle_upload')) {
                    require_once(ABSPATH . 'wp-admin/includes/file.php');
                }
                $uploaded_file = wp_handle_upload($_FILES[$file_key], ['test_form' => false]);
                if (isset($uploaded_file['error'])) {
                    wp_die(__('Upload error: ', 'awb-starter') . $uploaded_file['error']);
                }
                $attachment = [
                    'guid' => $uploaded_file['url'],
                    'post_mime_type' => $uploaded_file['type'],
                    'post_title' => sanitize_file_name($file_info['filename']),
                    'post_content' => '',
                    'post_status' => 'inherit'
                ];
                $attachment_id = wp_insert_attachment($attachment, $uploaded_file['file']);
                if (! is_wp_error($attachment_id)) {
                    require_once(ABSPATH . 'wp-admin/includes/image.php');
                    $attachment_data = wp_generate_attachment_metadata($attachment_id, $uploaded_file['file']);
                    wp_update_attachment_metadata($attachment_id, $attachment_data);
                    update_option('awb_custom_font_' . $type, $uploaded_file['url']);
                    $uploaded_fonts[] = ucfirst($type) . ' font uploaded';
                }
            }
        }
        $redirect_url = add_query_arg(['page' => 'awb-starter', 'tab' => 'tokens', 'fonts_updated' => 'true'], admin_url('admin.php'));
        if (! empty($uploaded_fonts)) {
            $redirect_url = add_query_arg('messages', urlencode(implode(', ', $uploaded_fonts)), $redirect_url);
        }
        wp_redirect($redirect_url);
        exit;
    }
}
