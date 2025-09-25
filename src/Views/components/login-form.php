<?php
/**
 * Login Form Component - Component-colocated styling
 * Migrated from components.css with sophisticated shadow and animation effects
 * 
 * Features:
 * - Centered full-screen layout
 * - Sophisticated card styling with shadows
 * - Responsive input styling
 * - Logo display
 * - CSRF protection
 * 
 * Usage:
 * <?php 
 * $csrf_token = 'token_value';
 * $action = '/login';
 * $logoPath = '/assets/img/Logo.png';
 * include __DIR__ . '/login-form.php'; 
 * ?>
 */
?>

<?php 
// Load sophisticated login and card styles from form-input component
$renderComponent = false; // Just load styles, don't render component
include __DIR__ . '/form-input.php'; 
?>

<style>
/* LOGIN FORM COMPONENT - Additional component-specific styles */

/* Logo styling */
.login-logo {
    text-align: center;
    margin-bottom: var(--space-8);
    position: relative;
    z-index: 2;
}

.login-logo img {
    width: 80px;
    height: 80px;
    object-fit: contain;
    margin: 0 auto;
    display: block;
    filter: drop-shadow(0 4px 6px rgba(0, 0, 0, 0.1));
}

/* Form text/helper text */
.form-text {
    font-size: var(--font-size-xs);
    color: var(--color-gray-600);
    margin-top: var(--space-1);
    margin-bottom: var(--space-4);
    text-align: center;
}

/* Auth links styling */
.auth-links {
    text-align: center;
    border-top: 1px solid var(--color-gray-200);
    padding-top: var(--space-4);
    margin-top: var(--space-2);
    position: relative;
    z-index: 2;
}

.auth-link {
    color: var(--color-text-secondary);
    text-decoration: none;
    transition: color var(--transition-base);
}

.auth-link:hover {
    color: var(--color-text-primary);
}

.auth-link-primary {
    color: var(--color-primary);
    font-weight: var(--font-weight-semibold);
}

.auth-link-secondary {
    display: block;
    margin-top: var(--space-2);
    font-size: var(--font-size-xs);
}
</style>

<?php
// Set defaults
$csrf_token = $csrf_token ?? '';
$action = $action ?? '/login';
$logoPath = $logoPath ?? '/assets/img/Logo.png';
$logoAlt = $logoAlt ?? 'Logo';
$title = $title ?? 'Einloggen';
$usernameLabel = $usernameLabel ?? 'Nutzername';
$passwordLabel = $passwordLabel ?? 'Passwort';
$submitText = $submitText ?? 'Einloggen';
$registerText = $registerText ?? 'Noch keinen Account?';
$registerLinkText = $registerLinkText ?? 'Registrieren';
$registerUrl = $registerUrl ?? '/register';
?>

<div class="login-container">
    <form method="post" action="<?= htmlspecialchars($action) ?>" class="login-form">
        <?php if ($csrf_token): ?>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
        <?php endif; ?>
        
        <?php if ($logoPath): ?>
        <div class="login-logo">
            <img src="<?= htmlspecialchars($logoPath) ?>" alt="<?= htmlspecialchars($logoAlt) ?>"/>
        </div>
        <?php endif; ?>

        <input class="login-input" 
               type="text" 
               name="username" 
               placeholder="<?= htmlspecialchars($usernameLabel) ?>" 
               required 
               minlength="2" 
               maxlength="20"
               autocomplete="username">

        <input class="login-input" 
               type="password" 
               name="password" 
               placeholder="<?= htmlspecialchars($passwordLabel) ?>" 
               required 
               minlength="4" 
               maxlength="20"
               autocomplete="current-password">

        <button class="login-button" type="submit">
            <?= htmlspecialchars($submitText) ?>
        </button>

        <?php if ($registerUrl): ?>
        <div class="auth-links">
            <a href="<?= htmlspecialchars($registerUrl) ?>" class="auth-link">
                <?= htmlspecialchars($registerText) ?> <span class="auth-link-primary"><?= htmlspecialchars($registerLinkText) ?></span>
            </a>
        </div>
        <?php endif; ?>
    </form>
</div>

<?php
/**
 * Usage Examples:
 * 
 * <!-- Basic login form -->
 * <?php 
 * $csrf_token = isset($csrf_token) ? $csrf_token : '';
 * include __DIR__ . '/components/login-form.php'; 
 * ?>
 * 
 * <!-- Custom login form -->
 * <?php 
 * $csrf_token = $csrf_token;
 * $action = '/custom-login';
 * $logoPath = '/custom-logo.png';
 * $title = 'Admin Login';
 * include __DIR__ . '/components/login-form.php'; 
 * ?>
 */
?>
