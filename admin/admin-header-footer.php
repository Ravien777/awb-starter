<?php

/**
 * Admin UI: Header & Footer Switcher Tab
 *
 * Rendered by admin-settings.php when the "header-footer" tab is active.
 * All output is escaped; nonce verification is handled by the AJAX handler.
 *
 * @package AWB_Starter
 * @since   1.0.0
 */

if (! defined('ABSPATH')) {
    exit;
}

// Current saved values.
$header_type  = get_option(AWB_Header_Switcher::OPTION_HEADER_TYPE,  'none');
$header_value = get_option(AWB_Header_Switcher::OPTION_HEADER_VALUE, '');
$footer_type  = get_option(AWB_Header_Switcher::OPTION_FOOTER_TYPE,  'none');
$footer_value = get_option(AWB_Header_Switcher::OPTION_FOOTER_VALUE, '');

// Available sources.
$header_patterns  = AWB_Header_Switcher::get_awb_patterns('header');
$footer_patterns  = AWB_Header_Switcher::get_awb_patterns('footer');
$reusable_blocks  = AWB_Header_Switcher::get_reusable_blocks();
?>

<div class="awb-section" id="awb-header-footer-section">
    <h2><?php esc_html_e('Header & Footer', 'awb-starter'); ?></h2>
    <p class="description">
        <?php esc_html_e('Replace your theme\'s default header and footer with a block pattern registered by this plugin, or any reusable block (synced pattern) you\'ve created in the Gutenberg editor.', 'awb-starter'); ?>
    </p>

    <?php wp_nonce_field('awb_save_header_footer', 'awb_header_footer_nonce'); ?>

    <!-- ======================================================
	     HEADER
	     ====================================================== -->
    <div class="awb-card">
        <h3><?php esc_html_e('Site Header', 'awb-starter'); ?></h3>

        <table class="form-table" role="presentation">
            <tr>
                <th scope="row">
                    <label for="awb_header_type"><?php esc_html_e('Source', 'awb-starter'); ?></label>
                </th>
                <td>
                    <select id="awb_header_type" name="awb_header_type" class="awb-switcher-type" data-target="awb-header-value-wrap">
                        <option value="none" <?php selected($header_type, 'none'); ?>><?php esc_html_e('— Use theme default —', 'awb-starter'); ?></option>
                        <option value="pattern" <?php selected($header_type, 'pattern'); ?>><?php esc_html_e('AWB Plugin Pattern', 'awb-starter'); ?></option>
                        <option value="block" <?php selected($header_type, 'block'); ?>><?php esc_html_e('My Reusable Block / Synced Pattern', 'awb-starter'); ?></option>
                    </select>
                </td>
            </tr>

            <!-- Pattern picker -->
            <tr id="awb-header-value-wrap-pattern" class="awb-value-row <?php echo ('pattern' !== $header_type) ? 'hidden' : ''; ?>">
                <th scope="row">
                    <label for="awb_header_pattern"><?php esc_html_e('Pattern', 'awb-starter'); ?></label>
                </th>
                <td>
                    <?php if (empty($header_patterns)) : ?>
                        <p class="description"><?php esc_html_e('No header patterns registered yet. Add PHP pattern files to patterns/header/.', 'awb-starter'); ?></p>
                    <?php else : ?>
                        <div class="awb-pattern-grid" id="awb-header-pattern-grid">
                            <?php foreach ($header_patterns as $pattern) :
                                $slug      = esc_attr($pattern['slug']);
                                $title     = esc_html($pattern['title']);
                                $selected  = ('pattern' === $header_type && $header_value === $pattern['slug']) ? 'awb-pattern-selected' : '';
                            ?>
                                <div class="awb-pattern-card <?php echo esc_attr($selected); ?>"
                                    data-slug="<?php echo $slug; ?>"
                                    role="radio"
                                    aria-checked="<?php echo $selected ? 'true' : 'false'; ?>"
                                    tabindex="0">
                                    <div class="awb-pattern-card__preview">
                                        <?php echo do_blocks($pattern['content']); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped 
                                        ?>
                                    </div>
                                    <span class="awb-pattern-card__title"><?php echo $title; ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <input type="hidden" id="awb_header_pattern" name="awb_header_pattern_value"
                            value="<?php echo esc_attr(('pattern' === $header_type) ? $header_value : ''); ?>">
                    <?php endif; ?>
                </td>
            </tr>

            <!-- Reusable block picker -->
            <tr id="awb-header-value-wrap-block" class="awb-value-row <?php echo ('block' !== $header_type) ? 'hidden' : ''; ?>">
                <th scope="row">
                    <label for="awb_header_block"><?php esc_html_e('Reusable Block', 'awb-starter'); ?></label>
                </th>
                <td>
                    <?php if (empty($reusable_blocks)) : ?>
                        <p class="description">
                            <?php
                            printf(
                                /* translators: %s: Gutenberg editor URL */
                                esc_html__('No reusable blocks found. %s to create one.', 'awb-starter'),
                                '<a href="' . esc_url(admin_url('edit.php?post_type=wp_block')) . '">' . esc_html__('Click here', 'awb-starter') . '</a>'
                            );
                            ?>
                        </p>
                    <?php else : ?>
                        <select id="awb_header_block" name="awb_header_block_value">
                            <option value=""><?php esc_html_e('— Select a block —', 'awb-starter'); ?></option>
                            <?php foreach ($reusable_blocks as $block) : ?>
                                <option value="<?php echo esc_attr($block->ID); ?>"
                                    <?php selected(('block' === $header_type) ? $header_value : '', (string) $block->ID); ?>>
                                    <?php echo esc_html($block->post_title); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">
                            <a href="<?php echo esc_url(admin_url('edit.php?post_type=wp_block')); ?>" target="_blank">
                                <?php esc_html_e('Manage reusable blocks →', 'awb-starter'); ?>
                            </a>
                        </p>
                    <?php endif; ?>
                </td>
            </tr>
        </table>
    </div><!-- /.awb-card -->

    <!-- ======================================================
	     FOOTER
	     ====================================================== -->
    <div class="awb-card">
        <h3><?php esc_html_e('Site Footer', 'awb-starter'); ?></h3>

        <table class="form-table" role="presentation">
            <tr>
                <th scope="row">
                    <label for="awb_footer_type"><?php esc_html_e('Source', 'awb-starter'); ?></label>
                </th>
                <td>
                    <select id="awb_footer_type" name="awb_footer_type" class="awb-switcher-type" data-target="awb-footer-value-wrap">
                        <option value="none" <?php selected($footer_type, 'none'); ?>><?php esc_html_e('— Use theme default —', 'awb-starter'); ?></option>
                        <option value="pattern" <?php selected($footer_type, 'pattern'); ?>><?php esc_html_e('AWB Plugin Pattern', 'awb-starter'); ?></option>
                        <option value="block" <?php selected($footer_type, 'block'); ?>><?php esc_html_e('My Reusable Block / Synced Pattern', 'awb-starter'); ?></option>
                    </select>
                </td>
            </tr>

            <!-- Pattern picker -->
            <tr id="awb-footer-value-wrap-pattern" class="awb-value-row <?php echo ('pattern' !== $footer_type) ? 'hidden' : ''; ?>">
                <th scope="row">
                    <label for="awb_footer_pattern"><?php esc_html_e('Pattern', 'awb-starter'); ?></label>
                </th>
                <td>
                    <?php if (empty($footer_patterns)) : ?>
                        <p class="description"><?php esc_html_e('No footer patterns registered yet. Add PHP pattern files to patterns/footer/.', 'awb-starter'); ?></p>
                    <?php else : ?>
                        <div class="awb-pattern-grid" id="awb-footer-pattern-grid">
                            <?php foreach ($footer_patterns as $pattern) :
                                $slug     = esc_attr($pattern['slug']);
                                $title    = esc_html($pattern['title']);
                                $selected = ('pattern' === $footer_type && $footer_value === $pattern['slug']) ? 'awb-pattern-selected' : '';
                            ?>
                                <div class="awb-pattern-card <?php echo esc_attr($selected); ?>"
                                    data-slug="<?php echo $slug; ?>"
                                    role="radio"
                                    aria-checked="<?php echo $selected ? 'true' : 'false'; ?>"
                                    tabindex="0">
                                    <div class="awb-pattern-card__preview">
                                        <?php echo do_blocks($pattern['content']); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped 
                                        ?>
                                    </div>
                                    <span class="awb-pattern-card__title"><?php echo $title; ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <input type="hidden" id="awb_footer_pattern" name="awb_footer_pattern_value"
                            value="<?php echo esc_attr(('pattern' === $footer_type) ? $footer_value : ''); ?>">
                    <?php endif; ?>
                </td>
            </tr>

            <!-- Reusable block picker -->
            <tr id="awb-footer-value-wrap-block" class="awb-value-row <?php echo ('block' !== $footer_type) ? 'hidden' : ''; ?>">
                <th scope="row">
                    <label for="awb_footer_block"><?php esc_html_e('Reusable Block', 'awb-starter'); ?></label>
                </th>
                <td>
                    <?php if (empty($reusable_blocks)) : ?>
                        <p class="description">
                            <?php
                            printf(
                                esc_html__('No reusable blocks found. %s to create one.', 'awb-starter'),
                                '<a href="' . esc_url(admin_url('edit.php?post_type=wp_block')) . '">' . esc_html__('Click here', 'awb-starter') . '</a>'
                            );
                            ?>
                        </p>
                    <?php else : ?>
                        <select id="awb_footer_block" name="awb_footer_block_value">
                            <option value=""><?php esc_html_e('— Select a block —', 'awb-starter'); ?></option>
                            <?php foreach ($reusable_blocks as $block) : ?>
                                <option value="<?php echo esc_attr($block->ID); ?>"
                                    <?php selected(('block' === $footer_type) ? $footer_value : '', (string) $block->ID); ?>>
                                    <?php echo esc_html($block->post_title); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">
                            <a href="<?php echo esc_url(admin_url('edit.php?post_type=wp_block')); ?>" target="_blank">
                                <?php esc_html_e('Manage reusable blocks →', 'awb-starter'); ?>
                            </a>
                        </p>
                    <?php endif; ?>
                </td>
            </tr>
        </table>
    </div><!-- /.awb-card -->

    <p class="submit">
        <button type="button" id="awb-save-header-footer" class="button button-primary">
            <?php esc_html_e('Save Header & Footer Settings', 'awb-starter'); ?>
        </button>
        <span id="awb-header-footer-status" class="awb-save-status" aria-live="polite"></span>
    </p>
</div><!-- /#awb-header-footer-section -->