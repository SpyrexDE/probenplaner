<?php

/**
 * PWA Install Card Component
 * Sidebar card to promote Progressive Web App installation.
 *
 * The card is rendered hidden by default. service-worker.php reveals it only
 * when an installation is actually possible (Chrome/Edge fired
 * beforeinstallprompt, or iOS/iPadOS Safari where a step-by-step guide is
 * shown). It is never visible when the app already runs installed
 * (display-mode standalone/fullscreen/minimal-ui).
 *
 * Usage:
 * <?php
 * $title = 'App installieren';
 * $subtitle = 'Direkt vom Startbildschirm öffnen';
 * $onclick = 'installPWA()';
 * $hidden = true;
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
        flex-shrink: 0;
        position: relative;
        overflow: hidden;
        animation: sidebar-install-card-in 0.3s ease-out;
    }

    @keyframes sidebar-install-card-in {
        from {
            opacity: 0;
            transform: translateY(6px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
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
        box-shadow: 0 4px 8px var(--color-primary-200);
    }

    .sidebar-install-card:hover::before {
        transform: rotate(45deg) translate(100%, 100%);
    }

    .sidebar-install-card:active {
        transform: translateY(0);
    }

    /* Keyboard focus ring. focus-removal.css strips outline AND box-shadow from
       [tabindex]:focus-visible with !important (specificity 0,2,0), so this rule
       needs !important plus a higher specificity (0,3,0) to win regardless of order.
       Background + border change as an additional, non-shadow cue. */
    .sidebar-install-card[role="button"]:focus-visible {
        background: linear-gradient(135deg, var(--color-primary-100) 0%, var(--color-primary-200) 100%);
        border-color: var(--color-primary);
        box-shadow: 0 0 0 3px var(--color-primary-300) !important;
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
        color: var(--color-primary);
        width: 18px;
        text-align: center;
        transition: all var(--transition-base);
    }

    .sidebar-install-card:hover .sidebar-install-icon {
        color: var(--color-primary-dark);
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
        /* text-primary on the tinted card: ~13:1 (primary-dark was only ~3.8:1) */
        color: var(--color-text-primary);
        margin-bottom: var(--space-1);
        transition: color var(--transition-base);
    }

    .sidebar-install-subtitle {
        font-size: var(--font-size-xs);
        /* gray-600 (theme-aware, also remapped in dark mode): ~6.9:1 on the tinted
           card; text-secondary would only reach ~4.3:1 there */
        color: var(--color-gray-600);
        line-height: var(--line-height-tight);
        transition: all var(--transition-base);
    }

    .sidebar-install-card:hover .sidebar-install-subtitle {
        color: var(--color-text-primary);
    }

    /* Hidden state (default until JS confirms that installing is possible) */
    .sidebar-install-card.hidden {
        display: none;
    }

    /* Never offer installation inside the installed app */
    @media (display-mode: standalone),
    (display-mode: fullscreen),
    (display-mode: minimal-ui),
    (display-mode: window-controls-overlay) {
        .sidebar-install-card {
            display: none !important;
        }
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

    @media (prefers-reduced-motion: reduce) {

        .sidebar-install-card,
        .sidebar-install-card::before,
        .sidebar-install-icon,
        .sidebar-install-title,
        .sidebar-install-subtitle {
            animation: none;
            transition: none;
        }

        .sidebar-install-card:hover,
        .sidebar-install-card:hover .sidebar-install-icon {
            transform: none;
        }

        .sidebar-install-card:hover::before {
            transform: rotate(45deg);
        }
    }

    /* INSTALL GUIDE MODAL (SweetAlert2, opened by installPWA() in service-worker.php) */
    .swal2-popup.pwa-guide-popup {
        background: var(--color-bg-primary);
        color: var(--color-text-primary);
        border-radius: var(--radius-xl);
        padding-bottom: var(--space-6);
    }

    .swal2-popup.pwa-guide-popup .swal2-title {
        color: var(--color-text-primary);
        font-size: var(--font-size-xl);
        font-weight: var(--font-weight-semibold);
        padding-top: var(--space-2);
    }

    .swal2-popup.pwa-guide-popup .swal2-html-container {
        color: var(--color-text-secondary);
        margin: var(--space-3) var(--space-5) 0;
        text-align: left;
    }

    .swal2-popup.pwa-guide-popup .swal2-close {
        color: var(--color-text-secondary);
    }

    .swal2-popup.pwa-guide-popup .swal2-close:hover {
        color: var(--color-text-primary);
    }

    .pwa-guide-app {
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto var(--space-2);
    }

    .pwa-guide-app img {
        width: 64px;
        height: 64px;
        border-radius: var(--radius-lg);
        box-shadow: 0 6px 16px var(--color-primary-200);
    }

    .pwa-guide-intro {
        font-size: var(--font-size-sm);
        line-height: var(--line-height-normal);
        text-align: center;
        margin: 0 0 var(--space-4);
    }

    .pwa-guide-steps {
        list-style: none;
        margin: 0;
        padding: 0;
        counter-reset: pwa-step;
        display: flex;
        flex-direction: column;
        gap: var(--space-2);
    }

    .pwa-guide-step {
        counter-increment: pwa-step;
        display: flex;
        align-items: center;
        gap: var(--space-3);
        padding: var(--space-3);
        border-radius: var(--radius-md);
        background: linear-gradient(135deg, var(--color-bg-secondary), var(--color-bg-tertiary));
        border: 1px solid var(--color-border-light);
        font-size: var(--font-size-sm);
        line-height: var(--line-height-normal);
        color: var(--color-text-primary);
    }

    .pwa-guide-step::before {
        content: counter(pwa-step);
        flex-shrink: 0;
        width: 24px;
        height: 24px;
        border-radius: var(--radius-full);
        /* White digits need a darker brand tone for >= 4.5:1
           (default theme: primary 3.3:1, primary-dark 4.2:1, mixed ~6.1:1).
           First line is the fallback for browsers without color-mix(). */
        background: var(--color-primary-dark);
        background: color-mix(in srgb, var(--color-primary-dark) 80%, #000);
        color: #ffffff;
        font-size: var(--font-size-xs);
        font-weight: var(--font-weight-semibold);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .pwa-guide-step-icon {
        flex-shrink: 0;
        width: 32px;
        height: 32px;
        border-radius: var(--radius-base);
        background: var(--color-primary-100);
        color: var(--color-primary);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: var(--font-size-base);
    }

    .pwa-guide-step-text {
        flex: 1;
        min-width: 0;
    }

    .pwa-guide-step-text strong {
        font-weight: var(--font-weight-semibold);
        color: var(--color-text-primary);
    }

    .pwa-guide-hint {
        display: block;
        font-size: var(--font-size-xs);
        /* text-muted would be ~2.4:1 on the step background */
        color: var(--color-gray-600);
        margin-top: 2px;
    }

    @media (max-width: 480px) {
        .swal2-popup.pwa-guide-popup .swal2-html-container {
            margin: var(--space-3) var(--space-3) 0;
        }

        .pwa-guide-step {
            padding: var(--space-2) var(--space-3);
            gap: var(--space-2);
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
$subtitle = $subtitle ?? 'Direkt vom Startbildschirm öffnen';
$icon = $icon ?? 'download';
$onclick = $onclick ?? '';
$hidden = $hidden ?? true;

$classes = ['sidebar-install-card'];
if ($hidden) $classes[] = 'hidden';

$classString = implode(' ', $classes);

$attributes = '';
if ($onclick) {
    $attributes .= ' onclick="' . htmlspecialchars($onclick) . '"';
    // Keyboard access: behaves like a button (Enter / Space)
    $attributes .= ' role="button" tabindex="0"';
    $attributes .= ' aria-label="' . htmlspecialchars($title . ' – ' . $subtitle) . '"';
}
?>

<div class="<?= $classString ?>" id="pwa-install-card" <?= $attributes ?>>
    <div class="sidebar-install-content">
        <i class="fas fa-<?= htmlspecialchars($icon) ?> sidebar-install-icon" aria-hidden="true"></i>
        <div class="sidebar-install-text">
            <div class="sidebar-install-title"><?= htmlspecialchars($title) ?></div>
            <div class="sidebar-install-subtitle"><?= htmlspecialchars($subtitle) ?></div>
        </div>
    </div>
</div>

<?php if ($onclick): ?>
    <script>
        (function() {
            var card = document.getElementById('pwa-install-card');
            if (!card) return;
            card.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ' || e.key === 'Spacebar') {
                    e.preventDefault();
                    card.click();
                }
            });
        })();
    </script>
<?php endif; ?>

<?php
/**
 * Usage Examples:
 *
 * <!-- Basic PWA install card (hidden until service-worker.php reveals it) -->
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
 * <!-- Initially visible (not recommended: only show it when installing is possible) -->
 * <?php
 * $hidden = false;
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
