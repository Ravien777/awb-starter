<?php

/**
 * Block pattern categories registration.
 *
 * @package AWBStarter
 */

if (! defined('ABSPATH')) {
    exit;
}

class AWB_Block_Categories
{
    private array $categories = [
        'awb-headers'  => 'AWB – Headers',
        'awb-footers'  => 'AWB – Footers',
        'awb-pages'    => 'AWB – Pages',
        'awb-sections' => 'AWB – Sections',
    ];

    public function __construct()
    {
        add_action('init', [$this, 'register']);
    }

    public function register(): void
    {
        if (! function_exists('register_block_pattern_category')) {
            return;
        }

        foreach ($this->categories as $slug => $label) {
            register_block_pattern_category($slug, ['label' => __($label, 'awb-starter')]);
        }
    }
}
