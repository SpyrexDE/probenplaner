<?php $this->layout('layouts/default', ['title' => 'Orchester beitreten', 'currentPage' => 'join_orchestra']) ?>

<?php
$renderComponent = false;
include __DIR__ . '/../components/form-input.php';

ob_start();
?>

<form method="POST" action="/orchestras/join" class="login-form">
    <?php include __DIR__ . '/../components/csrf-input.php'; ?>
    
    <?php include __DIR__ . '/../components/logo.php'; ?>

    <h2 style="text-align: center; margin-bottom: 2rem; color: var(--color-text-primary);">
        Orchester beitreten
    </h2>

    <input 
        class="login-input" 
        type="text" 
        id="token" 
        name="token" 
        required 
        placeholder="Token (z.B. ORCHESTER2024)" 
        value="<?= isset($_SESSION['form_data']['token']) ? htmlspecialchars($_SESSION['form_data']['token']) : '' ?>"
    >

    <button class="login-button" type="submit">
        Weiter
    </button>

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

if (isset($_SESSION['form_data'])) {
    unset($_SESSION['form_data']);
}
?>
