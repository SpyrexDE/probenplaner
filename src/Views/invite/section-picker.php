<?php $this->layout('layouts/default', ['title' => 'Beitreten', 'currentPage' => 'invite_section_picker']) ?>

<?php
$renderComponent = false;
include __DIR__ . '/../components/form-input.php';

ob_start();
?>

<form method="POST" action="/invite/join" class="login-form">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

    <?php include __DIR__ . '/../components/logo.php'; ?>

    <h2 style="text-align: center; color: var(--color-text-primary); margin-bottom: var(--space-6); font-size: var(--font-size-base); font-weight: 700;">
        <?= htmlspecialchars($orchestra['name']) ?> beitreten
    </h2>

    <?php
    $selectedType = '';
    $selectName   = 'section';
    $selectId     = 'section';
    $selectClass  = 'login-input';
    $orchestraId  = (int) $orchestra['id'];
    include __DIR__ . '/../components/instrument-select.php';
    ?>

    <button class="login-button" type="submit">Beitreten</button>

    <?php
    $links = [
        ['url' => '/orchestras/select', 'icon' => 'fa-chevron-left', 'text' => 'Zurück']
    ];
    include __DIR__ . '/../components/auth-footer.php';
    ?>
</form>

<?php
$content = ob_get_clean();
include __DIR__ . '/../components/centered-card.php';
?>