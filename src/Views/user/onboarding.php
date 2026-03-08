<?php $this->layout('layouts/default', ['title' => 'Willkommen', 'currentPage' => 'onboarding', 'isFluid' => true]) ?>

<?php
$renderComponent = false;
include __DIR__ . '/../components/form-input.php';

ob_start();
?>

<form method="POST" action="/onboarding/save" class="login-form">
    <?php include __DIR__ . '/../components/csrf-input.php'; ?>
    <?php include __DIR__ . '/../components/logo.php'; ?>

    <h2 style="text-align: center; font-size: var(--font-size-2xl); font-weight: 600; margin-bottom: var(--space-2);">
        Willkommen! 👋
    </h2>
    <p style="text-align: center; font-size: var(--font-size-sm); color: var(--color-text-secondary); margin: 0 0 var(--space-5); line-height: 1.5;">
        Wie möchtest du in deinem Ensemble angezeigt werden?
    </p>

    <input class="login-input"
        type="text"
        name="display_name"
        autofocus required
        placeholder="z.B. Vera S."
        autocomplete="name">
    <div class="form-text" style="margin-top: calc(-1 * var(--space-2));">
        So erkennt dich dein Register und die Leitung.
    </div>

    <button class="login-button" type="submit">Weiter</button>
</form>

<?php
$content = ob_get_clean();
include __DIR__ . '/../components/centered-card.php';
?>