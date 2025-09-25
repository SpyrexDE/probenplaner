<?php
// Reusable empty state component
// Expected variables (set before include):
// - $title (string)
// - $message (string)
// - $actionHref (string, optional)
// - $actionLabel (string, optional)
?>

<style>
/* EMPTY STATE COMPONENT - All styles colocated */
.empty-state {
    background-color: var(--color-info-100);
    color: var(--color-info-dark);
    padding: var(--space-10) var(--space-5);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-sm);
    text-align: center;
    margin: var(--space-8) 0;
    position: relative;
    overflow: hidden;
}

.empty-state::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
    animation: shimmer 3s infinite;
}

@keyframes shimmer {
    0%, 100% { left: -100%; }
    50% { left: 100%; }
}

.empty-state-title {
    font-size: var(--font-size-2xl);
    font-weight: var(--font-weight-semibold);
    margin: 0 0 var(--space-3) 0;
    color: var(--color-info-dark);
}

.empty-state-message {
    font-size: var(--font-size-lg);
    margin: 0 0 var(--space-5) 0;
    opacity: 0.9;
    color: var(--color-info-dark);
    line-height: 1.5;
}
</style>
<div class="flex justify-center">
    <div class="w-full max-w-2xl mx-4">
        <div class="empty-state">
            <h2 class="empty-state-title"><?= htmlspecialchars($title) ?></h2>
            <p class="empty-state-message"><?= htmlspecialchars($message) ?></p>
            <?php if (!empty($actionHref) && !empty($actionLabel)): ?>
            <div class="text-center mt-4">
                <a href="<?= htmlspecialchars($actionHref) ?>" class="btn-base btn-primary">
                    <?= htmlspecialchars($actionLabel) ?>
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

