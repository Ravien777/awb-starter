<?php

/**
* Plugin Name:       AWB Starter
* Plugin URI:        https://github.com/Ravien777/awb-starter
* Description:       Rapid-development starter plugin with block patterns, templates, and smart asset loading.
* Version:           2.3.2
* Author:            WLM+
* Text Domain:       awb-starter
* Requires PHP:      8.0
*
* @package AWBStarter 
*/

if (! defined('ABSPATH')) {
    exit;
}

// Plugin constants.
define('AWB_VERSION',     '2.3.2');
define('AWB_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('AWB_PLUGIN_URL',  plugin_dir_url(__FILE__));

/**
 * Absolute path to the patterns directory shipped with the plugin.
 */
define('AWB_PATTERNS_PATH', AWB_PLUGIN_PATH . 'patterns/');

/**
 * User‑writable location for imported/duplicated patterns.
 * Uses the WordPress uploads directory so content survives plugin updates.
 */
define('AWB_USER_PATTERNS_PATH', WP_CONTENT_DIR . '/uploads/awb-patterns/');
define('AWB_USER_PATTERNS_URL',  WP_CONTENT_URL . '/uploads/awb-patterns/');

/*
 * Auto-updates from GitHub releases via Plugin Update Checker (PUC).
 * Requires a published GitHub release whose asset zip extracts to awb-starter/.
 */
$awb_updater_loader = AWB_PLUGIN_PATH . 'lib/plugin-update-checker/plugin-update-checker.php';
if (file_exists($awb_updater_loader) && ! class_exists(\YahnisElsts\PluginUpdateChecker\v5\PucFactory::class)) {
    require_once $awb_updater_loader;

    if (class_exists(\YahnisElsts\PluginUpdateChecker\v5\PucFactory::class)) {
        $awb_update_checker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
            'https://github.com/Ravien777/awb-starter',
            __FILE__,
            'awb-starter'
        );

        // Serve the release asset zip instead of the source-code archive.
        $awb_update_checker->getVcsApi()->enableReleaseAssets('/\.zip($|[?&#])/i');

        // For a private repository, define AWB_GITHUB_TOKEN in wp-config.php.
        if (defined('AWB_GITHUB_TOKEN') && '' !== AWB_GITHUB_TOKEN) {
            $awb_update_checker->setAuthentication(AWB_GITHUB_TOKEN);
        }
    }
}
unset($awb_updater_loader);

// Autoload support for classes in /includes/.
spl_autoload_register(function ($class) {
    if (strpos($class, 'AWB_') !== 0) {
        return;
    }

    $file = AWB_PLUGIN_PATH . 'includes/class-' . strtolower(str_replace(['AWB_', '_'], ['', '-'], $class)) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

final class AWB_Starter
{
    private static ?AWB_Starter $instance = null;

    public static function instance(): self
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        $this->register_components();
    }

    private function register_components(): void
    {
        new AWB_Block_Categories();
        new AWB_Pattern_Loader();
        new AWB_Asset_Loader();

        /*
         * AWB_Header_Switcher must boot on every request — both admin and
         * frontend — so its init-hooked register_hooks() can attach to
         * generate_header / generate_footer on the frontend.
         *
         * The class itself returns early from register_hooks() when is_admin()
         * is true, so there is no duplicate execution risk.
         */
        new AWB_Header_Switcher();

        // REST API routes — loaded on every request so the REST server
        // can register /awb/v1/* endpoints regardless of context.
        if (class_exists('AWB_REST')) {
            new AWB_REST();
        }

        if (is_admin()) {
            new AWB_Settings();
            new AWB_Ajax_Handler();
        }
    }
}

AWB_Starter::instance();

/*
 * Abilities API — register AWB capabilities for MCP integration.
 * Requires WordPress 6.9+ (Abilities API in core) and the
 * wordpress/mcp-adapter plugin for MCP transport.
 * Silently no-ops when the Abilities API is not available.
 */
add_action('wp_abilities_api_categories_init', function (): void {
    if (function_exists('wp_register_ability_category')) {
        wp_register_ability_category('awb-starter', [
            'label'       => __('AWB Starter', 'awb-starter'),
            'description' => __('Block patterns, AI generation, and site scaffolding.', 'awb-starter'),
        ]);
    }
});
add_action('wp_abilities_api_init', function (): void {
    if (class_exists('AWB_Abilities')) {
        AWB_Abilities::register();
    }
});

// Create user-patterns directory structure on activation.
register_activation_hook(__FILE__, function (): void {
    $dirs = [
        AWB_USER_PATTERNS_PATH,
        AWB_USER_PATTERNS_PATH . 'patterns/',
        AWB_USER_PATTERNS_PATH . 'css/',
        AWB_USER_PATTERNS_PATH . 'js/',
    ];
    foreach ($dirs as $dir) {
        if (! is_dir($dir)) {
            wp_mkdir_p($dir);
        }
    }
});
