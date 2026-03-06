<?php $this->layout('layouts/default', ['title' => 'Registerstruktur', 'currentPage' => $currentPage, 'isFluid' => true]) ?>

<style>
    .settings-save-indicator {
        position: fixed;
        bottom: var(--space-4);
        right: var(--space-4);
        padding: var(--space-2) var(--space-4);
        border-radius: var(--radius-lg);
        font-size: var(--font-size-sm);
        font-weight: var(--font-weight-medium);
        display: none;
        align-items: center;
        gap: var(--space-2);
        z-index: 1000;
        box-shadow: var(--shadow-lg);
        transition: all var(--transition-base);
    }

    .settings-save-indicator.saving {
        background: var(--color-primary-100);
        color: var(--color-primary-700);
    }

    .settings-save-indicator.success {
        background: var(--color-success-100, #d1fae5);
        color: var(--color-success-700, #047857);
    }

    .settings-save-indicator.error {
        background: var(--color-error-100, #fee2e2);
        color: var(--color-error-700, #b91c1c);
    }
</style>

<div class="max-w-3xl mx-auto px-4 py-6">
    <a href="/<?= ($_SESSION['current_org_slug'] ?? '') . '/' . ($_SESSION['current_orchestra_slug'] ?? '') ?>/orchestras/settings"
        class="inline-flex items-center gap-2 text-sm font-medium mb-4"
        style="color: var(--color-text-secondary);">
        <?= icon('arrow-left', 'text-xs') ?> Zurück zu Einstellungen
    </a>

    <?php
    $sectionConfigApiUrl = sprintf(
        '/%s/api/settings/orchestra/%d',
        htmlspecialchars(
            ($_SESSION['current_org_slug'] ?? '') . '/' . ($_SESSION['current_orchestra_slug'] ?? '')
        ),
        $orchestra['id']
    );
    $editorConfig  = $currentConfig ?? $defaultConfig;
    $isCustom      = $currentConfig !== null;
    include __DIR__ . '/../components/section-config-editor.php';
    ?>

    <div id="settingsSaveIndicator" class="settings-save-indicator">
        <span class="indicator-text"></span>
    </div>
    <script src="/assets/js/settings-engine.js"></script>
</div>