<?php

/**
 * Admin Pattern Library Template
 *
 * Loaded by AWB_Starter::render_library_page().
 * Displays all registered AWB patterns in a searchable, filterable visual grid.
 *
 * @package AWBStarter
 */

if (! defined('ABSPATH')) exit;

// Gather all registered AWB patterns.
$all_patterns = WP_Block_Patterns_Registry::get_instance()->get_all_registered();
$awb_patterns = array_filter($all_patterns, fn($p) => str_starts_with($p['name'], 'awb/'));

// Build category list from registered AWB patterns.
$all_cats = [];
foreach ($awb_patterns as $pattern) {
    foreach (($pattern['categories'] ?? []) as $cat) {
        $all_cats[$cat] = true;
    }
}
ksort($all_cats);

// Category labels (matches what register_block_pattern_categories sets).
$cat_labels = [
    'awb-headers'  => 'Headers',
    'awb-footers'  => 'Footers',
    'awb-pages'    => 'Pages',
    'awb-sections' => 'Sections',
];
?>

<div class="awb-library-wrap">

    <header class="awb-library-header">
        <div class="awb-library-header__title">
            <span class="awb-logo-mark">AWB</span>
            <h1>Pattern Library</h1>
            <span class="awb-library-count" id="awb-pattern-count">
                <?php echo count($awb_patterns); ?> pattern<?php echo count($awb_patterns) !== 1 ? 's' : ''; ?>
            </span>
        </div>
        <div class="awb-library-header__controls">
            <div class="awb-search-wrap">
                <span class="awb-search-icon" aria-hidden="true">⌕</span>
                <input type="search"
                    id="awb-search"
                    class="awb-search"
                    placeholder="Search patterns…"
                    aria-label="Search patterns">
            </div>
            <div class="awb-view-toggle" role="group" aria-label="View mode">
                <button class="awb-view-btn is-active" data-view="grid" aria-pressed="true" title="Grid view">
                    <svg width="14" height="14" viewBox="0 0 14 14" fill="none" aria-hidden="true">
                        <rect x="0" y="0" width="6" height="6" rx="1.5" fill="currentColor" />
                        <rect x="8" y="0" width="6" height="6" rx="1.5" fill="currentColor" />
                        <rect x="0" y="8" width="6" height="6" rx="1.5" fill="currentColor" />
                        <rect x="8" y="8" width="6" height="6" rx="1.5" fill="currentColor" />
                    </svg>
                </button>
                <button class="awb-view-btn" data-view="list" aria-pressed="false" title="List view">
                    <svg width="14" height="14" viewBox="0 0 14 14" fill="none" aria-hidden="true">
                        <rect x="0" y="1" width="14" height="2" rx="1" fill="currentColor" />
                        <rect x="0" y="6" width="14" height="2" rx="1" fill="currentColor" />
                        <rect x="0" y="11" width="14" height="2" rx="1" fill="currentColor" />
                    </svg>
                </button>
            </div>
        </div>
    </header>

    <div class="awb-library-layout">

        <aside class="awb-library-sidebar">
            <nav aria-label="Filter by category">
                <button class="awb-filter-btn is-active" data-filter="all">
                    All <span class="awb-filter-count"><?php echo count($awb_patterns); ?></span>
                </button>
                <?php foreach ($all_cats as $cat => $_) :
                    $cat_count = count(array_filter($awb_patterns, fn($p) => in_array($cat, $p['categories'] ?? [], true)));
                    if ($cat_count === 0) continue;
                ?>
                    <button class="awb-filter-btn" data-filter="<?php echo esc_attr($cat); ?>">
                        <?php echo esc_html($cat_labels[$cat] ?? ucwords(str_replace(['awb-', '-'], ['', ' '], $cat))); ?>
                        <span class="awb-filter-count"><?php echo $cat_count; ?></span>
                    </button>
                <?php endforeach; ?>
            </nav>

            <div class="awb-sidebar-divider"></div>

            <a href="<?php echo esc_url(admin_url('admin.php?page=awb-starter')); ?>" class="awb-sidebar-link">
                ← Settings
            </a>
            <a href="<?php echo esc_url(admin_url('site-editor.php')); ?>" class="awb-sidebar-link" target="_blank" rel="noopener">
                Site Editor ↗
            </a>
        </aside>

        <main class="awb-library-main" id="awb-library-main">

            <?php if (empty($awb_patterns)) : ?>
                <div class="awb-library-empty">
                    <span class="awb-library-empty__icon">◫</span>
                    <h2>No patterns yet</h2>
                    <p>Add <code>.php</code> files to the <code>patterns/</code> folder with an <code>AWB</code> category to see them here.</p>
                </div>
            <?php else : ?>

                <div class="awb-pattern-grid" id="awb-pattern-grid" data-view="grid">
                    <?php foreach ($awb_patterns as $pattern) :
                        $slug       = $pattern['name'];
                        $short_slug = str_replace('awb/', '', $slug);
                        $cats       = $pattern['categories'] ?? [];
                        $keywords   = $pattern['keywords']   ?? [];
                        $has_css    = isset(AWB_Starter::$pattern_assets[$slug]['css']) && AWB_Starter::$pattern_assets[$slug]['css'];
                        $has_js     = isset(AWB_Starter::$pattern_assets[$slug]['js']) && AWB_Starter::$pattern_assets[$slug]['js'];
                    ?>
                        <article class="awb-pattern-card"
                            data-slug="<?php echo esc_attr($short_slug); ?>"
                            data-categories="<?php echo esc_attr(implode(' ', $cats)); ?>"
                            data-keywords="<?php echo esc_attr($pattern['title'] . ' ' . implode(' ', $keywords) . ' ' . implode(' ', $cats)); ?>">

                            <div class="awb-pattern-card__preview" aria-hidden="true">
                                <?php
                                // Render a live scaled preview of the pattern content.
                                // We show just the first 400 chars to keep the card small.
                                $preview_content = wp_kses_post($pattern['content']);
                                echo '<div class="awb-pattern-card__inner">' . $preview_content . '</div>';
                                ?>
                                <div class="awb-pattern-card__overlay">
                                    <button class="awb-pattern-card__action awb-copy-pattern"
                                        data-content="<?php echo esc_attr($pattern['content']); ?>"
                                        aria-label="Copy pattern markup for <?php echo esc_attr($pattern['title']); ?>">
                                        Copy markup
                                    </button>
                                    <button class="awb-pattern-card__action awb-insert-pattern"
                                        data-slug="<?php echo esc_attr($slug); ?>"
                                        aria-label="Preview <?php echo esc_attr($pattern['title']); ?>">
                                        Preview
                                    </button>
                                </div>
                            </div>

                            <div class="awb-pattern-card__meta">
                                <h3 class="awb-pattern-card__title"><?php echo esc_html($pattern['title']); ?></h3>
                                <div class="awb-pattern-card__tags">
                                    <?php foreach ($cats as $cat) : ?>
                                        <span class="awb-tag"><?php echo esc_html($cat_labels[$cat] ?? $cat); ?></span>
                                    <?php endforeach; ?>
                                    <?php if ($has_css) : ?><span class="awb-tag awb-tag--asset">CSS</span><?php endif; ?>
                                    <?php if ($has_js) : ?><span class="awb-tag awb-tag--asset">JS</span><?php endif; ?>
                                </div>
                                <?php if (! empty($pattern['description'])) : ?>
                                    <p class="awb-pattern-card__desc"><?php echo esc_html($pattern['description']); ?></p>
                                <?php endif; ?>
                            </div>

                        </article>
                    <?php endforeach; ?>
                </div>

                <p class="awb-no-results" id="awb-no-results" hidden>
                    No patterns match your search.
                </p>

            <?php endif; ?>

        </main>
    </div><!-- .awb-library-layout -->

</div><!-- .awb-library-wrap -->

<!-- Pattern preview modal -->
<div class="awb-modal" id="awb-preview-modal" role="dialog" aria-modal="true" aria-labelledby="awb-modal-title" hidden>
    <div class="awb-modal__backdrop" id="awb-modal-backdrop"></div>
    <div class="awb-modal__panel">
        <header class="awb-modal__header">
            <h2 class="awb-modal__title" id="awb-modal-title">Pattern preview</h2>
            <button class="awb-modal__close" id="awb-modal-close" aria-label="Close preview">✕</button>
        </header>
        <div class="awb-modal__body" id="awb-modal-body">
            <!-- Rendered via JS -->
        </div>
        <footer class="awb-modal__footer">
            <button class="awb-btn awb-btn--outline" id="awb-modal-copy">Copy markup</button>
            <button class="awb-btn awb-btn--primary" id="awb-modal-close-btn">Done</button>
        </footer>
    </div>
</div>