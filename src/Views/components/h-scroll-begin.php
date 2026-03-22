<?php
/**
 * H-SCROLL BEGIN
 *
 * Opens the horizontal scroll wrapper. Close with h-scroll-end.php.
 *
 * @param string $hScrollId    Unique DOM id for the inner scroll div (required)
 * @param int    $hScrollStep  Pixels per arrow click (default: 200)
 * @param string $hScrollClass Extra CSS classes on the scroll div (default: '')
 */
$hScrollStep  = $hScrollStep  ?? 200;
$hScrollClass = $hScrollClass ?? '';
?>

<?php if (empty($GLOBALS['_h_scroll_styled'])): $GLOBALS['_h_scroll_styled'] = true; ?>
<style>
.h-scroll-wrap {
    position: relative;
    min-width: 0;
}

.h-scroll {
    display: flex;
    overflow-x: auto;
    scrollbar-width: none;
    -webkit-overflow-scrolling: touch;
    scroll-behavior: smooth;
}

.h-scroll::-webkit-scrollbar {
    display: none;
}

.h-scroll-arrow {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    width: 28px;
    height: 28px;
    border-radius: var(--radius-full);
    border: 1px solid var(--color-border);
    background: var(--color-bg-primary);
    color: var(--color-text-secondary);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    z-index: 2;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15), 0 1px 3px rgba(0,0,0,0.1);
    transition: opacity var(--transition-base), background var(--transition-base), box-shadow var(--transition-base);
    font-size: 12px;
    opacity: 0;
    pointer-events: none;
}

.h-scroll-arrow.visible {
    opacity: 1;
    pointer-events: auto;
}

.h-scroll-arrow:hover {
    background: var(--color-gray-100);
    box-shadow: 0 4px 14px rgba(0,0,0,0.2), 0 2px 6px rgba(0,0,0,0.12);
}

.h-scroll-arrow.left  { left: 0; }
.h-scroll-arrow.right { right: 0; }
</style>
<?php endif; ?>

<?php if (empty($GLOBALS['_h_scroll_scripted'])): $GLOBALS['_h_scroll_scripted'] = true; ?>
<script>
window.initHScroll = function(scrollEl, step) {
    const wrap  = scrollEl.closest('.h-scroll-wrap');
    const left  = wrap.querySelector('.h-scroll-arrow.left');
    const right = wrap.querySelector('.h-scroll-arrow.right');

    function update() {
        const canScrollLeft  = scrollEl.scrollLeft > 5;
        const canScrollRight = scrollEl.scrollLeft + scrollEl.clientWidth < scrollEl.scrollWidth - 5;
        left.classList.toggle('visible', canScrollLeft);
        right.classList.toggle('visible', canScrollRight);
    }

    left.addEventListener('click',  () => { scrollEl.scrollLeft -= step; });
    right.addEventListener('click', () => { scrollEl.scrollLeft += step; });
    scrollEl.addEventListener('scroll', update, { passive: true });

    if (typeof ResizeObserver !== 'undefined') {
        new ResizeObserver(update).observe(scrollEl);
    }

    requestAnimationFrame(update);
};
</script>
<?php endif; ?>

<div class="h-scroll-wrap">
    <button type="button" class="h-scroll-arrow left" aria-hidden="true" tabindex="-1">
        <i class="fas fa-chevron-left"></i>
    </button>
    <div class="h-scroll <?= htmlspecialchars($hScrollClass) ?>" id="<?= htmlspecialchars($hScrollId) ?>">
