<?php
/**
 * Rehearsal Add Box
 *
 * Dashed placeholder box that creates a new rehearsal via AJAX on click.
 * Replaces the FAB button for a cleaner, inline creation flow.
 *
 * @param string $createUrl - AJAX endpoint for rehearsal creation
 */

$orchestraBase = ($_SESSION['current_org_slug'] ?? '') . '/' . ($_SESSION['current_orchestra_slug'] ?? '');
$createUrl = '/' . $orchestraBase . '/rehearsals/create-ajax';
?>

<style>
    .rehearsal-add-box {
        border: 2px dashed var(--color-border);
        border-radius: var(--radius-lg);
        padding: var(--space-5) var(--space-4);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: var(--space-2);
        cursor: pointer;
        color: var(--color-text-muted);
        font-size: var(--font-size-sm);
        font-weight: var(--font-weight-medium);
        transition: border-color 0.2s ease, color 0.2s ease, background-color 0.2s ease;
        margin-top: var(--space-3);
        user-select: none;
    }

    .rehearsal-add-box:hover {
        border-color: var(--color-primary-300);
        color: var(--color-primary);
        background-color: var(--color-primary-50);
    }

    .rehearsal-add-box:active {
        transform: scale(0.99);
    }

    .rehearsal-add-box.loading {
        pointer-events: none;
        opacity: 0.6;
    }

    .rehearsal-add-box i {
        font-size: var(--font-size-base);
    }
</style>

<div class="rehearsal-add-box" id="rehearsalAddBox"
     data-create-url="<?= htmlspecialchars($createUrl) ?>"
     onclick="window.IEM && window.IEM.createRehearsal(this)">
    <i class="fas fa-plus"></i>
    <span>Neue Probe</span>
</div>
