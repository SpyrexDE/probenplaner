<?php $this->layout('layouts/default', ['title' => 'Orchester beitreten', 'currentPage' => 'join_orchestra']) ?>

<?php 
// Load form styles 
$renderComponent = false; // Just load styles, don't render component
include __DIR__ . '/../components/form-input.php'; 
?>

<div class="login-container">
    <form method="POST" action="/orchestras/join" class="login-form">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
        
        <div class="login-logo">
            <img src="/assets/img/Logo.png" alt="Logo"/>
        </div>

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

        <div class="auth-links">
            <a href="/orchestras/select" class="auth-link">
                <i class="fas fa-chevron-left" style="margin-right: 0.25rem;"></i>
                Zurück
            </a>
        </div>
    </form>
</div>

<?php
// Clear form data from session
if (isset($_SESSION['form_data'])) {
    unset($_SESSION['form_data']);
}
?>
