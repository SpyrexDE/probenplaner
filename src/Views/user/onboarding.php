<?php $this->layout('layouts/default', ['title' => 'Willkommen', 'currentPage' => 'onboarding', 'isFluid' => true]) ?>

<style>
    .onboarding-card {
        background: white;
        border-radius: var(--radius-xl);
        box-shadow: var(--shadow-lg);
        padding: var(--space-8);
        width: 100%;
        max-width: 400px;
        text-align: center;
    }

    .onboarding-title {
        font-size: var(--font-size-2xl);
        font-weight: 600;
        margin-bottom: var(--space-2);
    }

    .onboarding-subtitle {
        font-size: var(--font-size-sm);
        color: var(--color-gray-500);
        margin-bottom: var(--space-5);
        line-height: 1.5;
    }

    .onboarding-hint {
        font-size: var(--font-size-xs);
        color: var(--color-gray-400);
        margin-top: var(--space-2);
    }

    .onboarding-input {
        width: 100%;
        padding: var(--space-3);
        border: 1px solid var(--color-gray-300);
        border-radius: var(--radius-lg);
        font-size: var(--font-size-base);
        transition: all var(--transition-base);
        background: var(--color-gray-50);
    }

    .onboarding-input:focus {
        outline: none;
        border-color: var(--color-primary);
        box-shadow: 0 0 0 3px rgba(71, 140, 244, 0.15);
        background: white;
    }

    .onboarding-btn {
        width: 100%;
        padding: var(--space-3);
        background: var(--color-primary);
        color: white;
        border: none;
        border-radius: var(--radius-lg);
        font-size: var(--font-size-base);
        font-weight: 600;
        cursor: pointer;
        transition: all var(--transition-base);
        margin-top: var(--space-4);
    }

    .onboarding-btn:hover {
        background: var(--color-primary-600, #3a7bd5);
        transform: translateY(-1px);
        box-shadow: var(--shadow-md);
    }
</style>

<?php
$content = '<div class="onboarding-card">';
$content .= '<div class="onboarding-title">Willkommen! 👋</div>';
$content .= '<div class="onboarding-subtitle">Wie möchtest du in deinem Ensemble angezeigt werden?</div>';
$content .= '<form method="POST" action="/onboarding/save">';
$content .= '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($csrf_token) . '">';
$content .= '<div class="mb-4 text-left">';
$content .= '<input type="text" name="display_name" autofocus required placeholder="z.B. Vera S." class="onboarding-input">';
$content .= '<div class="onboarding-hint">So erkennt dich dein Register und die Leitung.</div>';
$content .= '</div>';
$content .= '<button type="submit" class="onboarding-btn">Weiter</button>';
$content .= '</form></div>';

$headerContent = '<img src="/assets/img/Logo.png" alt="Probenplaner" style="height:64px">';
include __DIR__ . '/../components/centered-card.php';
?>