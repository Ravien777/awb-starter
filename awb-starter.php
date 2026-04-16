<?php

/**
 * Plugin Name:     AWB Starter
 * Plugin URI:      https://your-site.com/
 * Description:     Rapid-development starter plugin with block patterns, templates, and smart asset loading.
 * Version:         2.2.2
 * Author:          WLM+
 * Text Domain:     awb-starter
 * Requires PHP:    8.0
 *
 * @package AWBStarter
 */

if (! defined('ABSPATH')) {
    exit;
}

// Plugin constants.
define('AWB_VERSION',     '2.2.2');
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

        if (is_admin()) {
            new AWB_Settings();
            new AWB_Ajax_Handler();
        }
    }
}

AWB_Starter::instance();
