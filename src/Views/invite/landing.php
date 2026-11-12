<?php $this->layout('layouts/default', ['title' => 'Einladung', 'currentPage' => 'invite_landing']) ?>

<?php
$renderComponent = false;
include __DIR__ . '/../components/form-input.php';

$isConductor = ($linkType ?? '') === 'conductor';

ob_start();
?>

<div class="login-form">
    <?php include __DIR__ . '/../components/logo.php'; ?>
    <style>
        .login-form .app-logo-wrap {
            margin-bottom: 0;
        }
    </style>

    <p style="text-align: center; color: var(--color-text-secondary); margin: 0 0 var(--space-3); font-size: var(--font-size-base);">
        Du wurdest eingeladen<?= $isConductor ? ' als <strong>Leitung</strong>' : '' ?> zum
    </p>

    <div style="background: var(--color-bg-secondary); border: 1px solid var(--color-border); border-radius: var(--radius-lg); padding: var(--space-4); margin-bottom: var(--space-6); text-align: center;">
        <div style="font-size: var(--font-size-xl); font-weight: 600; display: flex; align-items: center; justify-content: center; gap: var(--space-2);">
            🎵 <?= htmlspecialchars($orchestra['name']) ?>
        </div>
        <?php if (!empty($orgName)): ?>
            <div style="font-size: var(--font-size-sm); color: var(--color-text-secondary); margin-top: var(--space-1);">
                <?= htmlspecialchars($orgName) ?>
            </div>
        <?php endif; ?>
    </div>

    <?php if (KEYCLOAK_ENABLED): ?>
        <?php include __DIR__ . '/../components/jmd-login-button.php'; ?>
    <?php endif; ?>

    <?php if (empty($keycloakOnly)): ?>
        <?php
        $links = [
            ['url' => PASSWORD_LOGIN_URL, 'text' => 'Anmelden / ', 'primary' => 'Registrieren']
        ];
        include __DIR__ . '/../components/auth-footer.php';
        ?>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../components/centered-card.php';
?>