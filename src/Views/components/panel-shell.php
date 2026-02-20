<?php

/**
 * Panel Shell Component — Full-page standalone layout for admin/orga panels.
 *
 * Props:
 * - $panelTitle     (string)  Title shown in topbar
 * - $panelBadge     (string)  Badge label, e.g. 'Admin' or 'Orga'
 * - $panelVariant   (string)  'admin' | 'orga' — controls badge color
 * - $panelLogoutUrl (string)  Optional logout link URL
 * - $panelMaxWidth  (string)  Body max-width, default '900px'
 * - $panelBackUrl   (string)  Optional back-link URL
 * - $panelContent   (string)  Body content (use ob_start/ob_get_clean)
 */

$panelTitle     = $panelTitle     ?? 'Probenplaner';
$panelBadge     = $panelBadge     ?? '';
$panelVariant   = $panelVariant   ?? 'admin';
$panelLogoutUrl = $panelLogoutUrl ?? '/logout';
$panelMaxWidth  = $panelMaxWidth  ?? '900px';
$panelBackUrl   = $panelBackUrl   ?? null;
$panelContent   = $panelContent   ?? '';
?>

<style>
    .panel-shell {
        display: flex;
        flex-direction: column;
        min-height: 100vh;
        background: var(--color-bg-secondary);
    }

    .panel-topbar {
        background: var(--color-bg-primary);
        border-bottom: 1px solid var(--color-border);
        padding: var(--space-3) var(--space-6);
        display: flex;
        align-items: center;
        justify-content: space-between;
        position: sticky;
        top: 0;
        z-index: var(--z-fixed, 50);
    }

    .panel-topbar-logo {
        height: 28px;
    }

    .panel-badge--admin {
        background: var(--color-primary-100, #dbeafe);
        color: var(--color-primary-700, #1d4ed8);
    }

    .panel-badge--orga {
        background: var(--color-success-100, #d1fae5);
        color: var(--color-success-700, #047857);
    }

    .panel-body {
        flex: 1;
        padding: var(--space-6);
        max-width: <?= htmlspecialchars($panelMaxWidth) ?>;
        margin: 0 auto;
        width: 100%;
    }
</style>

<?php if (!empty($csrf_token)): ?>
    <meta name="csrf-token" content="<?= htmlspecialchars($csrf_token) ?>">
<?php endif; ?>

<div class="panel-shell">
    <div class="panel-topbar">
        <div class="flex-start gap-3">
            <img src="/assets/icons/branding/Probenplaner Icon Dark.svg" alt="" class="panel-topbar-logo">
            <span class="text-heading" style="font-size: var(--font-size-lg);"><?= htmlspecialchars($panelTitle) ?></span>
            <?php if ($panelBadge): ?>
                <span class="panel-badge--<?= $panelVariant ?>" style="font-size: var(--font-size-xs); padding: 2px 8px; border-radius: var(--radius-full); font-weight: var(--font-weight-semibold);">
                    <?= htmlspecialchars($panelBadge) ?>
                </span>
            <?php endif; ?>
        </div>
        <?php if ($panelLogoutUrl): ?>
            <a href="<?= htmlspecialchars($panelLogoutUrl) ?>" class="btn-secondary btn-sm" style="font-size: var(--font-size-sm);">
                <i class="fas fa-sign-out-alt"></i> Abmelden
            </a>
        <?php endif; ?>
    </div>

    <div class="panel-body">
        <?php if ($panelBackUrl): ?>
            <div style="margin-bottom: var(--space-4);">
                <a href="<?= htmlspecialchars($panelBackUrl) ?>" class="back-link">
                    <i class="fas fa-arrow-left"></i> Zurück
                </a>
            </div>
        <?php endif; ?>

        <?= $panelContent ?>
    </div>
</div>