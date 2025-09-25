<?php $this->layout('layouts/default', ['title' => 'Admin Verifizierung', 'currentPage' => $currentPage]) ?>

<?php 
// Load form and card styles (form-input component used for styles only)
$renderComponent = false; // Just load styles, don't render component
include __DIR__ . '/../components/form-input.php'; 
?>

<div class="login-container">
    <div class="admin-verify-back">
        <a href="/" class="back-link">
            <i class="fas fa-arrow-left"></i>
            Zurück
        </a>
    </div>
    
    <form method="post" action="/orchestras/create" class="login-form">
        <?php if (isset($csrf_token)): ?>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
        <?php endif; ?>
        
        <div class="login-logo">
            <img src="/assets/img/Logo.png" alt="Logo"/>
        </div>

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
</div> 