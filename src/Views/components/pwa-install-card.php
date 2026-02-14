<?php

/**
 * PWA Install Card Component
 * Sidebar card to promote Progressive Web App installation
 * 
 * Usage:
 * <?php 
 * $title = 'App installieren';
 * $subtitle = 'Für bessere Performance';
 * $onclick = 'installPWA()';
 * include __DIR__ . '/pwa-install-card.php'; 
 * ?>
 * 
 * Or styles-only mode:
 * <?php 
 * $renderComponent = false; // Just load styles
 * include __DIR__ . '/pwa-install-card.php'; 
 * ?>
 */
?>

<style>
    /* PWA INSTALL CARD */
    .sidebar-install-card {
        margin: var(--space-2) var(--space-3) var(--space-3) var(--space-3);
        background: linear-gradient(135deg, var(--color-primary-50) 0%, var(--color-primary-100) 100%);
        border: 1px solid var(--color-primary-200);
        border-radius: var(--radius-md);
        cursor: pointer;
        transition: all var(--transition-base);
        margin-top: auto;
        position: relative;
        overflow: hidden;
    }

    .sidebar-install-card::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.1), transparent);
        transform: rotate(45deg);
        transition: transform 0.6s;
        pointer-events: none;
    }

    .sidebar-install-card:hover {
        background: linear-gradient(135deg, var(--color-primary-100) 0%, var(--color-primary-200) 100%);
        border-color: var(--color-primary-300);
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(71, 140, 244, 0.15);
    }

    .sidebar-install-card:hover::before {
        transform: rotate(45deg) translate(100%, 100%);
    }

    .sidebar-install-card:active {
        transform: translateY(0);
    }

    .sidebar-install-content {
        padding: var(--space-3);
        display: flex;
        align-items: center;
        gap: var(--space-3);
        position: relative;
        z-index: 1;
    }

    .sidebar-install-icon {
        font-size: 16px;
        color: var(--color-primary-600);
        width: 18px;
        text-align: center;
        transition: all var(--transition-base);
    }

    .sidebar-install-card:hover .sidebar-install-icon {
        color: var(--color-primary-700);
        transform: scale(1.1);
    }

    .sidebar-install-text {
        flex: 1;
        min-width: 0;
    }

    .sidebar-install-title {
        font-size: var(--font-size-sm);
        font-weight: var(--font-weight-medium);
        line-height: var(--line-height-tight);
        color: var(--color-primary-700);
        margin-bottom: var(--space-1);
        transition: color var(--transition-base);
    }

    .sidebar-install-card:hover .sidebar-install-title {
        color: var(--color-primary-800);
    }

    .sidebar-install-subtitle {
        font-size: var(--font-size-xs);
        color: var(--color-primary-600);
        line-height: var(--line-height-tight);
        opacity: 0.8;
        transition: all var(--transition-base);
    }

    .sidebar-install-card:hover .sidebar-install-subtitle {
        opacity: 1;
        color: var(--color-primary-700);
    }

    /* Hidden state for when PWA is already installed */
    .sidebar-install-card.hidden {
        display: none;
    }

    /* Mobile adjustments */
    @media (max-width: 768px) {
        .sidebar-install-card {
            margin: var(--space-2);
        }

        .sidebar-install-content {
            padding: var(--space-2);
            gap: var(--space-2);
        }

        .sidebar-install-icon {
            font-size: 14px;
            width: 16px;
        }
    }
</style>

<?php
// Styles-only mode check
$renderComponent = $renderComponent ?? true;

if (!$renderComponent) {
    // Styles-only mode: just load the styles and exit
    return;
}

// Component defaults
$title = $title ?? 'App installieren';
$subtitle = $subtitle ?? 'Für bessere Performance';
$icon = $icon ?? 'download';
$onclick = $onclick ?? '';
$hidden = $hidden ?? false;

$classes = ['sidebar-install-card'];
if ($hidden) $classes[] = 'hidden';

$classString = implode(' ', $classes);

$attributes = '';
if ($onclick) {
    $attributes .= ' onclick="' . htmlspecialchars($onclick) . '"';
}
?>

<div class="<?= $classString ?>" id="pwa-install-card" <?= $attributes ?>>
    <div class="sidebar-install-content">
        <i class="fas fa-<?= htmlspecialchars($icon) ?> sidebar-install-icon"></i>
        <div class="sidebar-install-text">
            <div class="sidebar-install-title"><?= htmlspecialchars($title) ?></div>
            <div class="sidebar-install-subtitle"><?= htmlspecialchars($subtitle) ?></div>
        </div>
    </div>
</div>

<?php
/**
 * Usage Examples:
 * 
 * <!-- Basic PWA install card -->
 * <?php 
 * $title = 'Install App';
 * $subtitle = 'Get faster access';
 * $onclick = 'installPWA()';
 * include __DIR__ . '/pwa-install-card.php'; 
 * ?>
 * 
 * <!-- Custom icon and text -->
 * <?php 
 * $title = 'Offline verfügbar';
 * $subtitle = 'Jetzt installieren';
 * $icon = 'mobile-alt';
 * $onclick = 'promptInstall()';
 * include __DIR__ . '/pwa-install-card.php'; 
 * ?>
 * 
 * <!-- Hidden state -->
 * <?php 
 * $hidden = true;
 * include __DIR__ . '/pwa-install-card.php'; 
 * ?>
 * 
 * <!-- Just load styles -->
 * <?php 
 * $renderComponent = false;
 * include __DIR__ . '/pwa-install-card.php'; 
 * ?>
 */
?>