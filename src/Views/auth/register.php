<?php $this->layout('layouts/default', ['title' => 'Registrierung', 'currentPage' => $currentPage]) ?>

<?php 
// Component styles
$renderComponent = false;
include __DIR__ . '/../components/form-input.php'; 
?>

<div class="login-container">
    <form method="post" action="/register" class="login-form">
        <?php if (isset($csrf_token)): ?>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
        <?php endif; ?>
        
        <div class="login-logo">
            <img src="/assets/img/Logo.png" alt="Logo"/>
        </div>

        <input class="login-input" 
               type="text" 
               name="username" 
               placeholder="Nutzername" 
               required 
               minlength="2" 
               maxlength="20"
               autocomplete="username">

        <input class="login-input" 
               type="password" 
               name="password" 
               placeholder="Passwort" 
               required 
               minlength="4" 
               maxlength="20"
               autocomplete="new-password">

        <input class="login-input" 
               type="password" 
               name="password_confirm" 
               placeholder="Passwort bestätigen" 
               required 
               minlength="4" 
               maxlength="20"
               autocomplete="new-password">
        
        <div class="form-text">Nach der Registrierung können Sie Orchestern beitreten</div>

        <button class="login-button" type="submit">
            Registrieren
        </button>

        <div class="auth-links">
            <a href="/login" class="auth-link">
                Bereits registriert? <span class="auth-link-primary">Einloggen</span>
            </a>
        </div>
    </form>
</div>

<?php
// Notifications
include __DIR__ . '/../components/notification-system.php';
?>