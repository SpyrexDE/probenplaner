<?php
/**
 * REHEARSAL CARD STYLES
 *
 * Shared styles for rehearsal-card.php and recurring-dialog.php.
 * Safe to include multiple times — guarded by REHEARSAL_CARD_STYLES_LOADED.
 */
?>
<?php if (!defined('REHEARSAL_CARD_STYLES_LOADED')): define('REHEARSAL_CARD_STYLES_LOADED', true); ?>
<style>
    /* === Base card === */
    .rehearsal-card {
        background-color: var(--color-bg-primary);
        border-color: var(--color-border);
        border-left-color: var(--color-gray-300);
        box-shadow: var(--shadow-sm);
        position: relative;
    }
    .rehearsal-card.status-pending { border-left-color: var(--color-gray-400); }
    .rehearsal-card.status-attending { border-left-color: var(--color-success); }
    .rehearsal-card.status-not-attending { border-left-color: var(--color-error); }

    .rehearsal-weekday {
        font-size: 24px;
        font-weight: 900;
        line-height: 1;
        min-width: 40px;
        text-align: center;
        background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-dark) 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: var(--space-3);
        transition: font-size 0.25s ease, min-width 0.25s ease;
        flex-shrink: 0;
        /* Force own compositing layer to avoid invisible-until-tap bug on old iOS */
        -webkit-transform: translateZ(0);
        transform: translateZ(0);
    }
    .rehearsal-weekday::after {
        content: '';
        position: absolute;
        bottom: -4px;
        left: 50%;
        transform: translateX(-50%);
        width: 80%;
        height: 2px;
        background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-dark) 100%);
        border-radius: 1px;
        opacity: 0.6;
    }

    .rehearsal-grid { display: flex; flex-direction: column; gap: var(--space-4); }

    /* === Promise buttons === */
    .action-btn {
        background-color: var(--color-bg-primary);
        border-color: var(--color-border);
        transition: all var(--transition-base);
    }
    .action-btn:hover { border-color: var(--color-primary); background-color: var(--color-primary-50); box-shadow: var(--shadow-md); }
    .action-btn:active { transform: translateY(-1px); }
    .action-btn.deselected { opacity: 0.4; background-color: var(--color-bg-tertiary); border-color: var(--color-border); box-shadow: none; }
    .action-btn.deselected:hover { opacity: 0.7; }
    .action-btn.deselected i { filter: grayscale(100%) brightness(0.7); }

    .checkBtn { color: var(--color-success); }
    .checkBtn i { color: var(--color-success-icon); }
    .checkBtn:not(.deselected) { border-color: var(--color-success); background: linear-gradient(135deg, var(--color-success-50) 0%, var(--color-success-100) 100%); box-shadow: 0 4px 14px rgba(16, 185, 129, 0.25); }
    .checkBtn.deselected { border-color: var(--color-border); background-color: var(--color-bg-tertiary); }
    .checkBtn.deselected i { color: var(--color-text-muted); }

    .crossBtn { color: var(--color-error); }
    .crossBtn i { color: var(--color-error-icon); }
    .crossBtn:not(.deselected) { border-color: var(--color-error); background: linear-gradient(135deg, var(--color-error-50) 0%, var(--color-error-100) 100%); box-shadow: 0 4px 14px rgba(239, 68, 68, 0.25); }
    .crossBtn.deselected { border-color: var(--color-border); background-color: var(--color-bg-tertiary); }
    .crossBtn.deselected i { color: var(--color-text-muted); }

    /* === Badges === */
    .rehearsal-type-badge { color: #7c3aed; background-color: rgba(124, 58, 237, 0.15); border: 1px solid rgba(124, 58, 237, 0.25); }
    .rehearsal-section-badge, .rehearsal-location-badge { color: var(--color-text-secondary); background-color: var(--color-bg-tertiary); border: 1px solid var(--color-border); }
    .rehearsal-note-icon, .rehearsal-note-text { color: var(--color-text-muted); }

    /* ══════════════ INLINE EDIT MODE ══════════════ */
    .rehearsal-card.ie-card {
        cursor: pointer;
        transition: box-shadow 0.25s ease, padding 0.3s ease, border-color 0.3s ease;
    }

    @media (hover: hover) {
        .rehearsal-card.ie-card:not(.ie-expanded):hover {
            box-shadow: var(--shadow-md);
        }
        .rehearsal-card.ie-card:not(.ie-expanded):hover .ie-chevron {
            opacity: 0.8;
            transform: translateX(2px);
        }
    }

    /* ── Edit toggle button ── */
    .ie-edit-toggle {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        border-radius: var(--radius-full, 50%);
        color: var(--color-text-muted);
        font-size: 14px;
        flex-shrink: 0;
        transition: all 0.2s ease;
        cursor: pointer;
        border: none;
        background: none;
        padding: 0;
    }
    @media (hover: hover) {
        .ie-edit-toggle:hover {
            color: var(--color-primary);
            background: rgba(71, 140, 244, 0.08);
        }
    }
    .ie-expanded .ie-edit-toggle {
        color: var(--color-primary);
        background: rgba(71, 140, 244, 0.12);
    }
    .ie-expanded .ie-edit-toggle .fa-pen::before {
        content: '\f00c';
    }

    /* Hide empty/placeholder fields on non-expanded cards */
    .ie-card:not(.ie-expanded) .ie-editable[style*="opacity: 0.4"] { display: none !important; }
    .ie-card:not(.ie-expanded) .ie-editable[style*="dashed"] { display: none !important; }
    .ie-card:not(.ie-expanded) [data-ie-color] { display: none !important; }
    .ie-card:not(.ie-expanded) .ie-role-add { display: none !important; }
    .ie-card:not(.ie-expanded) .ie-role-remove { display: none !important; }

    /* ── EXPANDED (edit mode) ── */
    .rehearsal-card.ie-expanded {
        box-shadow: 0 0 0 2px rgba(71, 140, 244, 0.3), 0 0 12px rgba(71, 140, 244, 0.15), 0 0 24px rgba(71, 140, 244, 0.08);
        cursor: default;
        padding: var(--space-4) var(--space-5) !important;
        border-left-width: 5px;
    }
    .ie-expanded .rehearsal-weekday { font-size: 30px; min-width: 48px; }
    .ie-expanded .ie-date-text { font-size: var(--font-size-xl) !important; }
    .ie-expanded .ie-time-text { font-size: var(--font-size-base) !important; }

    /* Badges swell */
    .ie-expanded .ie-editable {
        padding: 6px 12px !important;
        font-size: 12px !important;
        min-height: 32px;
        display: inline-flex !important;
        align-items: center;
        border: 1px solid var(--color-border);
        border-radius: var(--radius-md);
        cursor: pointer;
        transition: all 0.2s ease;
    }
    @media (hover: hover) {
        .ie-expanded .ie-editable:hover {
            border-color: var(--color-primary-200);
            background-color: var(--color-bg-secondary);
        }
    }
    .ie-expanded .ie-editable:active {
        background-color: var(--color-primary-50);
        border-color: var(--color-primary-300);
    }
    .ie-editable.ie-editing {
        border-color: var(--color-primary) !important;
        box-shadow: 0 0 0 2px rgba(71, 140, 244, 0.15);
    }
    .ie-expanded .rehearsal-badges {
        gap: var(--space-2) !important;
        margin-top: var(--space-2) !important;
    }
    .ie-expanded .ie-info-tag { display: none !important; }
    .ie-editable.ie-editing { opacity: 1 !important; border-style: solid !important; }

    /* ── Inline input ── */
    .ie-inline-input {
        font: inherit;
        color: inherit;
        background: transparent;
        border: none;
        outline: none;
        padding: 0;
        margin: 0;
        width: 100%;
        min-width: 60px;
    }
    .ie-inline-input::placeholder { color: var(--color-text-muted); font-style: italic; }

    /* Suppress focus ring on invisible date/time overlay inputs */
    [data-ie-field="datetime"] input[type="date"],
    [data-ie-field="datetime"] input[type="time"] { outline: none; }

    /* ── Popover ── */
    .ie-popover {
        position: absolute;
        z-index: 50;
        background: var(--color-bg-primary);
        border: 1px solid var(--color-border);
        border-radius: var(--radius-md);
        padding: var(--space-2);
        box-shadow: var(--shadow-lg);
        margin-top: var(--space-1);
        max-height: 300px;
        overflow-y: auto;
    }
    .ie-popover::-webkit-scrollbar { width: 8px; }
    .ie-popover::-webkit-scrollbar-track { background: var(--color-bg-secondary); border-radius: var(--radius-sm); }
    .ie-popover::-webkit-scrollbar-thumb { background: var(--color-border); border-radius: var(--radius-sm); }
    .ie-popover::-webkit-scrollbar-thumb:hover { background: var(--color-text-muted); }

    .ie-popover label { min-height: 36px; padding: var(--space-1) var(--space-2); }

    /* ── Inline role tags ── */
    .ie-role-tag { position: relative; }
    .ie-role-remove {
        background: none;
        border: none;
        cursor: pointer;
        -webkit-text-fill-color: var(--role-color);
        color: var(--role-color);
        font-size: 14px;
        font-weight: 700;
        line-height: 1;
        padding: 0 0 0 4px;
        opacity: 0.7;
        transition: opacity 0.15s ease;
    }
    .ie-role-remove:hover { opacity: 1; }
    .ie-role-add {
        -webkit-text-fill-color: var(--color-text-muted) !important;
        -webkit-background-clip: unset !important;
        background-clip: unset !important;
        background: none !important;
        color: var(--color-text-muted) !important;
        border: 1px dashed var(--color-border) !important;
        cursor: pointer;
        transition: background 0.15s ease;
    }
    .ie-role-add::before { display: none !important; }
    .ie-role-add:hover {
        background: color-mix(in srgb, var(--color-text-muted) 10%, transparent) !important;
    }

    /* ── Expanded sections ── */
    .ie-section {
        margin-top: var(--space-3);
        padding-top: var(--space-3);
        animation: ie-fade-in 0.3s ease;
    }
    .ie-expanded .ie-section { display: block !important; }
    @keyframes ie-fade-in {
        from { opacity: 0; transform: translateY(-4px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* ── Groups dialog ── */
    .ie-groups-dialog {
        border: none;
        padding: 0;
        background: transparent;
        max-width: 480px;
        width: calc(100% - var(--space-6));
        max-height: 80vh;
        border-radius: var(--radius-lg);
        overflow: visible;
    }
    .ie-groups-dialog::backdrop {
        background: rgba(0, 0, 0, 0.4);
        animation: ie-fade-in 0.15s ease;
    }
    .ie-groups-dialog .ie-groups-panel {
        background: var(--color-bg-primary);
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-xl, 0 20px 60px rgba(0,0,0,0.3));
        display: flex;
        flex-direction: column;
        max-height: 80vh;
        animation: ie-scale-in 0.2s ease;
    }
    @keyframes ie-scale-in {
        from { transform: scale(0.95); opacity: 0; }
        to { transform: scale(1); opacity: 1; }
    }
    @keyframes ie-shake {
        0%, 100% { transform: translateX(0); }
        20% { transform: translateX(-6px); }
        40% { transform: translateX(6px); }
        60% { transform: translateX(-4px); }
        80% { transform: translateX(4px); }
    }
    .ie-groups-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: var(--space-4);
        border-bottom: 1px solid var(--color-border);
        flex-shrink: 0;
    }
    .ie-groups-header h3 {
        margin: 0;
        font-size: var(--font-size-lg);
        font-weight: var(--font-weight-semibold);
        color: var(--color-text-primary);
    }
    .ie-groups-close {
        background: none;
        border: none;
        cursor: pointer;
        padding: var(--space-2);
        border-radius: var(--radius-sm);
        color: var(--color-text-muted);
        font-size: 18px;
        line-height: 1;
        transition: color 0.15s ease;
    }
    .ie-groups-close:hover { color: var(--color-text-primary); }
    .ie-groups-body {
        padding: var(--space-4);
        overflow-y: auto;
        flex: 1;
    }

    /* ── Footer actions ── */
    .ie-footer {
        display: none;
        justify-content: space-between;
        align-items: center;
        margin-top: var(--space-3);
        padding-top: var(--space-2);
        border-top: 1px solid var(--color-border);
    }
    .ie-expanded .ie-footer { display: flex; }

    .ie-footer-btn {
        background: none;
        border: none;
        cursor: pointer;
        padding: var(--space-2) var(--space-3);
        border-radius: var(--radius-sm);
        font-size: var(--font-size-sm);
        transition: opacity 0.15s ease, color 0.15s ease, background 0.15s ease;
        min-height: 36px;
        display: inline-flex;
        align-items: center;
        gap: var(--space-1);
    }
    .ie-footer-edit {
        color: var(--color-text-secondary);
        opacity: 0.6;
    }
    .ie-footer-edit:hover, .ie-footer-edit:active { opacity: 1; color: var(--color-primary); }
    .ie-footer-delete {
        color: var(--color-text-secondary);
        opacity: 0.6;
    }
    .ie-footer-delete:hover, .ie-footer-delete:active { opacity: 1; color: var(--color-error); }

    /* ── Tags ── */
    .ie-tags {
        display: flex;
        align-items: center;
        gap: 6px;
        flex-wrap: wrap;
        flex: 1;
        min-width: 0;
    }
    .ie-tag {
        display: inline-flex;
        align-items: center;
        gap: 2px;
        font-size: 11px;
        padding: 2px 8px;
        border-radius: var(--radius-full, 99px);
        background: var(--color-bg-tertiary, #f3f4f6);
        color: var(--color-text-secondary);
        white-space: nowrap;
        transition: all 0.15s ease;
    }
    .ie-tag-remove {
        display: none;
        background: none;
        border: none;
        cursor: pointer;
        font-size: 12px;
        color: var(--color-text-muted);
        padding: 0;
        margin-left: 2px;
        line-height: 1;
    }
    .ie-tag-remove:hover { color: var(--color-error); }
    .ie-expanded .ie-tag-remove { display: inline; }
    .ie-tag-add {
        display: none;
        background: none;
        border: 1px dashed var(--color-border);
        cursor: pointer;
        font-size: 11px;
        padding: 2px 8px;
        border-radius: var(--radius-full, 99px);
        color: var(--color-text-muted);
        transition: all 0.15s ease;
    }
    .ie-tag-add:hover { border-color: var(--color-text-secondary); color: var(--color-text-secondary); }
    .ie-expanded .ie-tag-add { display: inline-flex; }
    .ie-tag-input {
        font-size: 11px;
        padding: 2px 8px;
        border-radius: var(--radius-full, 99px);
        border: 1px solid var(--color-primary);
        background: var(--color-bg-primary);
        color: var(--color-text-primary);
        outline: none;
        min-width: 80px;
        max-width: 150px;
    }

    /* ── Mobile ── */
    @media (max-width: 480px) {
        .rehearsal-card.ie-expanded { padding: var(--space-3) var(--space-3) !important; }
        .ie-expanded .ie-editable { padding: 8px 12px !important; font-size: 13px !important; min-height: 36px; }
        .ie-expanded .rehearsal-weekday { font-size: 26px; min-width: 42px; }
    }
</style>
<?php endif; ?>
