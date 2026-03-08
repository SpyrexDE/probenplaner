<?php $this->layout('layouts/default', ['title' => 'Ungültiger Link', 'currentPage' => 'invite_invalid']) ?>

<?php
$renderComponent = false;
include __DIR__ . '/../components/form-input.php';

ob_start();
?>

<div class="login-form" style="text-align: center;">
    <?php include __DIR__ . '/../components/logo.php'; ?>

    <div style="font-size: 3rem; margin-bottom: var(--space-3);">🔗</div>
    <div style="font-size: var(--font-size-xl); font-weight: 600; margin-bottom: var(--space-2);">Ungültiger Link</div>
    <div style="font-size: var(--font-size-sm); color: var(--color-text-secondary); margin-bottom: var(--space-5);">
        Dieser Einladungslink ist ungültig oder abgelaufen.
    </div>

    <a href="/orchestras/select" class="login-button" style="display: flex; align-items: center; justify-content: center; text-decoration: none;">
        Zur Ensemble-Auswahl
    </a>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../components/centered-card.php';
?>