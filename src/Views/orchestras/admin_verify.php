<?php $this->layout('layouts/default', ['title' => 'Admin Verifizierung', 'currentPage' => $currentPage]) ?>

<?php
$renderComponent = false;
include __DIR__ . '/../components/form-input.php';

ob_start();
?>

<form method="post" action="/orchestras/create" class="login-form">
    <?php include __DIR__ . '/../components/csrf-input.php'; ?>
    
    <?php include __DIR__ . '/../components/logo.php'; ?>

    <h2 class="verify-title">Admin Verifizierung</h2>
    <p class="verify-subtitle">Um ein neues Orchester anlegen zu können, benötigen Sie das Admin-Passwort</p>

    <input class="login-input"
           type="password"
           name="admin_password"
           placeholder="Admin-Passwort"
           required>

    <button class="login-button" type="submit">
        Verifizieren
    </button>
</form>

<?php
$content = ob_get_clean();
$backLink = ['url' => '/', 'text' => 'Zurück', 'icon' => 'fa-arrow-left'];
include __DIR__ . '/../components/centered-card.php';
?>