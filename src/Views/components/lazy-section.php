<?php
/**
 * Lazy Section Component
 *
 * Renders a skeleton placeholder that loads content via AJAX on scroll.
 *
 * Required variables:
 *  - $lazyUrl    (string) — AJAX endpoint returning HTML
 *  - $lazyType   (string) — Skeleton type: 'cards' | 'rows' | 'sections'
 *
 * Optional variables:
 *  - $lazyId     (string) — Unique identifier for LazySection.reload()
 *  - $lazyCount  (int)    — Number of skeleton placeholders (default: 3)
 */

$lazyId    = $lazyId ?? '';
$lazyCount = $lazyCount ?? 3;
$lazyType  = $lazyType ?? 'cards';
?>

<div class="lazy-section"
     data-lazy-section
     data-lazy-url="<?= htmlspecialchars($lazyUrl) ?>"
     data-lazy-id="<?= htmlspecialchars($lazyId) ?>"
     data-lazy-skeleton-type="<?= htmlspecialchars($lazyType) ?>"
     data-lazy-skeleton-count="<?= (int)$lazyCount ?>">
    <div class="lazy-skeleton">
        <?php for ($i = 0; $i < $lazyCount; $i++): ?>
            <?php if ($lazyType === 'rows'): ?>
                <div class="lazy-skeleton-row">
                    <div class="lazy-skeleton-avatar"></div>
                    <div class="lazy-skeleton-lines">
                        <div class="lazy-skeleton-bar bar-subtitle"></div>
                        <div class="lazy-skeleton-bar bar-short"></div>
                    </div>
                </div>
            <?php elseif ($lazyType === 'sections'): ?>
                <div class="lazy-skeleton-section">
                    <div class="lazy-skeleton-section-header">
                        <div class="lazy-skeleton-section-icon"></div>
                        <div class="lazy-skeleton-bar bar-title" style="margin:0"></div>
                    </div>
                    <div class="lazy-skeleton-row">
                        <div class="lazy-skeleton-avatar"></div>
                        <div class="lazy-skeleton-lines">
                            <div class="lazy-skeleton-bar bar-subtitle"></div>
                            <div class="lazy-skeleton-bar bar-short"></div>
                        </div>
                    </div>
                    <div class="lazy-skeleton-row">
                        <div class="lazy-skeleton-avatar"></div>
                        <div class="lazy-skeleton-lines">
                            <div class="lazy-skeleton-bar bar-text"></div>
                            <div class="lazy-skeleton-bar bar-short"></div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="lazy-skeleton-card">
                    <div class="lazy-skeleton-bar bar-title"></div>
                    <div class="lazy-skeleton-bar bar-text"></div>
                    <div class="lazy-skeleton-bar bar-subtitle"></div>
                    <div class="lazy-skeleton-bar bar-short"></div>
                </div>
            <?php endif; ?>
        <?php endfor; ?>
    </div>
</div>
