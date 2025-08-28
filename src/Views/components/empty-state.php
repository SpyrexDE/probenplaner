<?php
// Reusable empty state component
// Expected variables (set before include):
// - $title (string)
// - $message (string)
// - $actionHref (string, optional)
// - $actionLabel (string, optional)
?>
<div class="flex justify-center">
    <div class="w-full max-w-2xl mx-4">
        <div class="empty-state">
            <h2 class="empty-state-title"><?= htmlspecialchars($title) ?></h2>
            <p class="empty-state-message"><?= htmlspecialchars($message) ?></p>
            <?php if (!empty($actionHref) && !empty($actionLabel)): ?>
            <div class="text-center mt-4">
                <a href="<?= htmlspecialchars($actionHref) ?>" class="btn btn-primary">
                    <?= htmlspecialchars($actionLabel) ?>
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

