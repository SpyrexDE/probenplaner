<?php $this->layout('layouts/default', ['title' => 'Einladung', 'currentPage' => 'invite_landing']) ?>

<?php
ob_start();
?>

<style>
    .invite-card {
        background: white;
        border-radius: var(--radius-xl);
        box-shadow: var(--shadow-lg);
        padding: var(--space-8);
        width: 100%;
        max-width: 400px;
        text-align: center;
    }

    .invite-title {
        font-size: var(--font-size-lg);
        color: var(--color-gray-600);
        margin-bottom: var(--space-4);
    }

    .invite-orchestra-card {
        background: var(--color-gray-50);
        border: 1px solid var(--color-gray-200);
        border-radius: var(--radius-lg);
        padding: var(--space-4);
        margin-bottom: var(--space-6);
    }

    .invite-orchestra-name {
        font-size: var(--font-size-xl);
        font-weight: 600;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: var(--space-2);
    }

    .invite-org-name {
        font-size: var(--font-size-sm);
        color: var(--color-gray-500);
        margin-top: var(--space-1);
    }

    .invite-divider {
        display: flex;
        align-items: center;
        gap: var(--space-3);
        margin: var(--space-4) 0;
        color: var(--color-gray-400);
        font-size: var(--font-size-sm);
    }

    .invite-divider::before,
    .invite-divider::after {
        content: '';
        flex: 1;
        height: 1px;
        background: var(--color-gray-200);
    }

    .invite-btn {
        display: block;
        width: 100%;
        padding: var(--space-3);
        border-radius: var(--radius-lg);
        font-weight: 500;
        text-align: center;
        text-decoration: none;
        transition: all var(--transition-base);
        font-size: var(--font-size-sm);
    }

    .invite-btn-primary {
        background: var(--color-primary);
        color: white;
    }

    .invite-btn-primary:hover {
        background: var(--color-primary-600);
    }

    .invite-btn-secondary {
        background: white;
        color: var(--color-gray-700);
        border: 1px solid var(--color-gray-300);
    }

    .invite-btn-secondary:hover {
        background: var(--color-gray-50);
    }
</style>

<?php
$isConductor = ($linkType ?? '') === 'conductor';
$content = '<div class="invite-card">';
$content .= '<div class="invite-title">Du wurdest eingeladen' . ($isConductor ? ' als <strong>Leitung</strong>' : '') . ' zum</div>';
$content .= '<div class="invite-orchestra-card">';
$content .= '<div class="invite-orchestra-name">🎵 ' . htmlspecialchars($orchestra['name']) . '</div>';
if (!empty($orgName)) {
    $content .= '<div class="invite-org-name">' . htmlspecialchars($orgName) . '</div>';
}
$content .= '</div>';

$content .= '<a href="/auth/keycloak/login" class="invite-btn invite-btn-primary" style="margin-bottom: var(--space-3)">Mit JMD-Account anmelden</a>';
$content .= '<div class="invite-divider">oder</div>';
$content .= '<a href="/login" class="invite-btn invite-btn-secondary">Anmelden / Registrieren</a>';
$content .= '</div>';

$headerContent = '<img src="/assets/img/Logo.png" alt="Probenplaner" style="height:64px">';
include __DIR__ . '/../components/centered-card.php';
?>