<?php

/**
 * Login Form Component
 * 
 * Usage:
 * <?php 
 * $csrf_token = 'token_value';
 * $action = '/login';
 * include __DIR__ . '/login-form.php'; 
 * ?>
 */

$renderComponent = false;
include __DIR__ . '/form-input.php';
?>

<style>
    .login-divider {
        text-align: center;
        margin: var(--space-6) 0 var(--space-4) 0;
        position: relative;
        display: flex;
        align-items: center;
    }

    .login-divider::before,
    .login-divider::after {
        content: '';
        flex: 1;
        height: 1px;
        background: var(--color-border);
    }

    .login-divider span {
        padding: 0 var(--space-4);
        color: var(--color-text-secondary);
        font-size: var(--font-size-sm);
        font-weight: var(--font-weight-medium);
        white-space: nowrap;
    }

    .jmd-login-section {
        margin-bottom: var(--space-4);
    }

    .jmd-login-btn {
        width: 100%;
        justify-content: flex-start;
        padding: var(--space-3) var(--space-4);
        gap: var(--space-3);
    }

    .jmd-logo {
        width: 24px;
        height: 24px;
        object-fit: contain;
        flex-shrink: 0;
    }
</style>

<?php
$action = $action ?? '/login';
$usernameLabel = $usernameLabel ?? 'E-Mail';
$passwordLabel = $passwordLabel ?? 'Passwort';
$submitText = $submitText ?? 'Einloggen';
$registerText = $registerText ?? 'Noch keinen Account?';
$registerLinkText = $registerLinkText ?? 'Registrieren';
$registerUrl = $registerUrl ?? '/register';

ob_start();
?>

<form method="post" action="<?= htmlspecialchars($action) ?>" class="login-form">
    <?php include __DIR__ . '/csrf-input.php'; ?>

    <?php include __DIR__ . '/logo.php'; ?>

    <input class="login-input"
        type="text"
        name="username"
        placeholder="<?= htmlspecialchars($usernameLabel) ?>"
        required
        minlength="2"
        maxlength="20"
        autocomplete="email">

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

    <?php if (KEYCLOAK_ENABLED): ?>
        <div class="login-divider">
            <span>oder</span>
        </div>

        <div class="jmd-login-section">
            <a href="/auth/keycloak/login" class="btn-modern btn-secondary jmd-login-btn">
                <img src="/assets/img/Logo.png" alt="JMD" class="jmd-logo">
                Mit JMD Account einloggen
            </a>
        </div>
    <?php endif; ?>

    <?php
    if ($registerUrl) {
        $links = [
            ['url' => $registerUrl, 'text' => $registerText . ' ', 'primary' => $registerLinkText]
        ];
        include __DIR__ . '/auth-footer.php';
    }
    ?>
</form>

<?php
$content = ob_get_clean();
include __DIR__ . '/centered-card.php';
?>