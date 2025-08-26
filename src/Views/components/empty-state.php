<?php
// Reusable empty state component
// Expected variables (set before include):
// - $title (string)
// - $message (string)
// - $actionHref (string, optional)
// - $actionLabel (string, optional)
?>
<div class="row justify-content-center">
    <div class="col-md-10 col-lg-8">
        <div style="background-color: #e8f7fc; padding: 40px 20px; border-radius: 10px; box-shadow: 0px 0px 10px rgba(0,0,0,0.1);">
            <h2 style="color: #006064; text-align: center; font-size: 2.0rem; font-weight: 600; margin-bottom: 12px;"><?= htmlspecialchars($title) ?></h2>
            <p style="text-align: center; font-size: 1.05rem; margin-bottom: 20px;"><?= htmlspecialchars($message) ?></p>
            <?php if (!empty($actionHref) && !empty($actionLabel)): ?>
            <div style="text-align: center; margin-top: 10px;">
                <a href="<?= htmlspecialchars($actionHref) ?>" class="btn btn-primary" style="background-color: #478cf4;">
                    <?= htmlspecialchars($actionLabel) ?>
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

