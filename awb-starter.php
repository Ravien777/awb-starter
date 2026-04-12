<?php

/**
 * Plugin Name:     AWB Starter
 * Plugin URI:      https://your-site.com/
 * Description:     Rapid-development starter plugin with block patterns, templates, and smart asset loading.
 * Version:         2.2.0
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
define('AWB_VERSION',     '2.2.0');
define('AWB_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('AWB_PLUGIN_URL',  plugin_dir_url(__FILE__));

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

        if (is_admin()) {
            new AWB_Settings();
            new AWB_Ajax_Handler();
            new AWB_Header_Switcher();
        }
    }
}

AWB_Starter::instance();
