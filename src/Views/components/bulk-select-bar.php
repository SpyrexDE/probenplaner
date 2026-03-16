<?php
/**
 * BULK SELECT BAR
 *
 * Self-contained component: search/filter toolbar + multi-select mode
 * with sticky floating action bar for bulk editing rehearsals.
 *
 * Include once at top of rehearsals/index.php before the rehearsal list.
 * Requires: window.IEM (Inline Edit Manager), COLORS, ROLES, BASE constants.
 */
?>
<style>
    /* ═══ SEARCH & FILTER TOOLBAR ═══ */
    .bulk-toolbar-sticky {
        position: sticky;
        top: var(--navbar-height, 64px);
        z-index: 50;
        padding: var(--space-3) 0 var(--space-2);
        background: inherit;
    }
    .bulk-toolbar {
        background: var(--color-white, var(--color-bg-primary));
        border: 1px solid var(--color-border);
        border-radius: var(--radius-xl);
        box-shadow: 0 4px 16px -2px rgba(0,0,0,0.1);
        padding: var(--space-3) var(--space-4);
        margin-bottom: var(--space-4);
        display: flex;
        flex-direction: column;
        gap: var(--space-3);
        min-width: 0;
    }

    .bulk-toolbar-row {
        display: flex;
        align-items: center;
        gap: var(--space-2);
        min-width: 0;
    }

    .bulk-toolbar-actions {
        display: flex;
        align-items: center;
        gap: var(--space-2);
        flex-shrink: 0;
    }

    .bulk-search-wrapper {
        position: relative;
        flex: 1;
        min-width: 0;
    }
    .bulk-search-icon {
        position: absolute;
        left: var(--space-3);
        top: 50%;
        transform: translateY(-50%);
        color: var(--color-text-muted);
        font-size: var(--font-size-xs);
        pointer-events: none;
        transition: color var(--transition-base);
    }
    .bulk-search {
        width: 100%;
        padding: var(--space-2) var(--space-3) var(--space-2) calc(var(--space-8) + 2px);
        border: 1.5px solid var(--color-border);
        border-radius: var(--radius-full);
        font-size: var(--font-size-sm);
        color: var(--color-text-primary);
        background: var(--color-bg-secondary);
        transition: all var(--transition-base);
    }
    .bulk-search:focus {
        outline: none;
        border-color: var(--color-primary);
        box-shadow: 0 0 0 3px rgba(71, 140, 244, 0.1);
    }
    .bulk-search:focus ~ .bulk-search-icon {
        color: var(--color-primary);
    }

    .bulk-filter-row {
        display: flex;
        gap: 6px;
        overflow-x: auto;
        scrollbar-width: none;
        -webkit-overflow-scrolling: touch;
        padding-bottom: 2px;
        width: 100%;
        min-width: 0;
    }
    .bulk-filter-row::-webkit-scrollbar { display: none; }

    .bulk-filter-chip {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 5px 10px;
        border-radius: var(--radius-full);
        font-size: 11px;
        font-weight: var(--font-weight-semibold);
        border: 1px solid var(--color-border);
        background: var(--color-bg-primary);
        color: var(--color-text-secondary);
        cursor: pointer;
        white-space: nowrap;
        transition: all 0.15s ease;
        user-select: none;
        flex-shrink: 0;
    }
    .bulk-filter-chip:hover {
        border-color: var(--color-primary-200);
        background: var(--color-primary-50);
    }
    .bulk-filter-chip.active {
        border-color: var(--color-primary);
        background: var(--color-primary-50);
        color: var(--color-primary);
    }
    .bulk-filter-chip .chip-clear {
        font-size: 12px;
        line-height: 1;
        opacity: 0.6;
        cursor: pointer;
    }
    .bulk-filter-chip .chip-clear:hover { opacity: 1; }

    .bulk-series-btn {
        margin-left: auto;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 5px 14px;
        border: 1px solid var(--color-primary);
        border-radius: var(--radius-md);
        background: transparent;
        color: var(--color-primary);
        font-size: 12px;
        font-weight: var(--font-weight-semibold);
        cursor: pointer;
        white-space: nowrap;
        transition: all 0.15s ease;
    }
    .bulk-series-btn:hover {
        background: var(--color-primary);
        color: #fff;
    }

    .bulk-filter-dropdown {
        position: fixed;
        z-index: 110;
        background: var(--color-bg-primary);
        border: 1px solid var(--color-border);
        border-radius: var(--radius-lg);
        padding: var(--space-3);
        box-shadow: var(--shadow-xl);
        max-height: 280px;
        overflow-y: auto;
        min-width: 200px;
        max-width: 320px;
        animation: filterDropIn 0.15s ease;
    }
    .bulk-filter-dropdown label {
        display: block;
        font-size: 12px;
        font-weight: var(--font-weight-semibold);
        color: var(--color-text-secondary);
        margin-bottom: 4px;
    }
    .bulk-filter-dropdown label + label {
        margin-top: var(--space-2);
    }
    .bulk-filter-dropdown input[type="date"] {
        width: 100%;
        padding: var(--space-2) var(--space-3);
        border: 1px solid var(--color-border);
        border-radius: var(--radius-md);
        font-size: var(--font-size-sm);
        color: var(--color-text-primary);
        background: var(--color-bg-primary);
        transition: border-color 0.15s;
    }
    .bulk-filter-dropdown input[type="date"]:focus {
        outline: none;
        border-color: var(--color-primary);
        box-shadow: 0 0 0 2px rgba(71, 140, 244, 0.1);
    }
    .bulk-filter-option {
        padding: 8px 12px;
        border-radius: var(--radius-sm);
        cursor: pointer;
        font-size: var(--font-size-sm);
        transition: all 0.1s ease;
        display: flex;
        align-items: center;
        gap: var(--space-2);
    }
    .bulk-filter-option:hover { background: var(--color-bg-secondary); }
    .bulk-filter-option.selected { background: var(--color-primary-50); color: var(--color-primary); font-weight: 600; }

    /* Select mode toggle */
    .bulk-select-toggle {
        width: 36px;
        height: 36px;
        border-radius: var(--radius-full);
        border: 2px solid var(--color-border);
        background: var(--color-bg-primary);
        color: var(--color-text-muted);
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        transition: all 0.2s ease;
        flex-shrink: 0;
    }
    .bulk-select-toggle:hover {
        border-color: var(--color-primary-200);
        color: var(--color-primary);
    }
    .bulk-select-toggle.active {
        border-color: var(--color-primary);
        background: var(--color-primary);
        color: #fff;
        box-shadow: 0 2px 8px rgba(71, 140, 244, 0.35);
    }

    /* ═══ CARD SELECTION STATES ═══ */
    .ie-card.bulk-selectable {
        cursor: pointer;
        transition: all 0.25s ease;
        user-select: none;
        -webkit-user-select: none;
    }

    /* Edit toggle → selection indicator in bulk mode */
    .ie-card.bulk-selectable .ie-edit-toggle {
        width: 28px;
        height: 28px;
        border: 2px solid var(--color-border);
        border-radius: 50%;
        background: var(--color-bg-primary);
        color: transparent;
        pointer-events: none;
        transition: all 0.2s ease;
    }
    .ie-card.bulk-selectable .ie-edit-toggle .fa-pen::before {
        content: '' !important;
    }
    .ie-card.bulk-selected .ie-edit-toggle {
        border-color: var(--color-primary);
        background: var(--color-primary);
        color: #fff;
    }
    .ie-card.bulk-selected .ie-edit-toggle .fa-pen::before {
        content: '\f00c' !important;
    }

    /* Inset text effect: text looks stamped into the card surface */
    .ie-card.bulk-selectable .rehearsal-weekday {
        background-color: #888;
        background-image: none;
        -webkit-background-clip: text;
        background-clip: text;
        -webkit-text-fill-color: transparent;
        color: transparent;
        text-shadow: rgba(255,255,255,0.45) 0px 2px 3px;
        transform: scale(1);
        transition: background-color 0.25s ease, transform 0.25s ease;
    }
    .ie-card.bulk-selectable .rehearsal-weekday::after { opacity: 0; }

    /* Dim unselected cards for contrast */
    .ie-card.bulk-selectable:not(.bulk-selected) {
        opacity: 0.5;
        filter: grayscale(0.3);
        transform: scale(0.97);
        transition: opacity 0.25s ease, filter 0.25s ease, transform 0.25s ease;
    }

    .ie-card.bulk-selected {
        background: color-mix(in srgb, var(--color-primary) 5%, var(--color-bg-primary));
        box-shadow: 0 4px 20px rgba(71, 140, 244, 0.12);
    }
    /* Inflated 3D text — vibrant gradient staying close to primary */
    .ie-card.bulk-selected .rehearsal-weekday {
        background: radial-gradient(
            ellipse at 50% 35%,
            color-mix(in srgb, var(--color-primary) 70%, #fff) 0%,
            var(--color-primary) 45%,
            color-mix(in srgb, var(--color-primary) 75%, #000) 100%
        );
        -webkit-background-clip: text;
        background-clip: text;
        -webkit-text-fill-color: transparent;
        color: transparent;
        text-shadow: none;
        filter:
            drop-shadow(0 1px 1px rgba(0,0,0,0.3))
            drop-shadow(0 3px 5px rgba(0,0,0,0.15))
            drop-shadow(0 0 10px color-mix(in srgb, var(--color-primary) 30%, transparent));
        transform: scale(1.1);
    }

    /* ═══ STICKY FLOATING ACTION BAR ═══ */
    .bulk-action-bar {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        z-index: 100;
        transform: translateY(100%);
        transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        pointer-events: none;
    }
    .bulk-action-bar.visible {
        transform: translateY(0);
        pointer-events: auto;
    }
    .bulk-action-inner {
        max-width: 600px;
        margin: 0 auto;
        padding: 0 var(--space-3) calc(var(--space-3) + env(safe-area-inset-bottom, 0px));
    }
    .bulk-action-panel {
        background: color-mix(in srgb, var(--color-bg-primary) 85%, transparent);
        backdrop-filter: blur(16px) saturate(180%);
        -webkit-backdrop-filter: blur(16px) saturate(180%);
        border: 1px solid var(--color-border);
        border-radius: var(--radius-xl);
        box-shadow: 0 -4px 24px rgba(0,0,0,0.12), 0 0 0 1px rgba(255,255,255,0.05);
        padding: var(--space-3);
        display: flex;
        flex-direction: column;
        gap: var(--space-2);
        min-width: 0;
    }

    .bulk-action-header {
        display: flex;
        align-items: center;
        gap: var(--space-2);
        min-width: 0;
    }
    .bulk-action-count {
        font-size: var(--font-size-sm);
        font-weight: var(--font-weight-bold);
        color: var(--color-primary);
        margin-right: auto;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .bulk-header-btn {
        background: none;
        border: none;
        cursor: pointer;
        font-size: 14px;
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: var(--radius-md);
        transition: all 0.15s ease;
        flex-shrink: 0;
    }
    .bulk-header-btn--duplicate {
        color: var(--color-text-muted);
    }
    .bulk-header-btn--duplicate:hover {
        color: var(--color-primary);
        background: var(--color-primary-50);
    }
    .bulk-header-btn--delete {
        color: var(--color-text-muted);
    }
    .bulk-header-btn--delete:hover {
        color: var(--color-danger, #ef4444);
        background: color-mix(in srgb, var(--color-danger, #ef4444) 10%, transparent);
    }
    .bulk-action-close {
        background: none;
        border: none;
        cursor: pointer;
        color: var(--color-text-muted);
        font-size: 16px;
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: var(--radius-md);
        transition: all 0.15s ease;
        flex-shrink: 0;
    }
    .bulk-action-close:hover {
        color: var(--color-text-primary);
        background: var(--color-bg-secondary);
    }

    .bulk-action-buttons {
        display: flex;
        gap: 6px;
        overflow-x: auto;
        scrollbar-width: none;
        -webkit-overflow-scrolling: touch;
        padding-bottom: 2px;
        width: 100%;
        min-width: 0;
    }
    .bulk-action-buttons::-webkit-scrollbar { display: none; }

    .bulk-action-btn {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 8px 14px;
        border-radius: var(--radius-full);
        font-size: 12px;
        font-weight: var(--font-weight-semibold);
        border: 1px solid var(--color-border);
        background: var(--color-bg-primary);
        color: var(--color-text-secondary);
        cursor: pointer;
        white-space: nowrap;
        transition: all 0.15s ease;
        flex-shrink: 0;
    }
    .bulk-action-btn:hover {
        border-color: var(--color-primary-200);
        background: var(--color-primary-50);
        color: var(--color-primary);
    }
    .bulk-action-btn:active {
        transform: scale(0.97);
    }
    .bulk-action-btn i {
        font-size: 11px;
        opacity: 0.7;
    }

    /* Bulk action popovers */
    .bulk-popover {
        position: fixed;
        z-index: 110;
        background: var(--color-bg-primary);
        border: 1px solid var(--color-border);
        border-radius: var(--radius-lg);
        padding: var(--space-3);
        box-shadow: var(--shadow-xl);
        min-width: 200px;
        max-width: 320px;
        animation: bulkPopIn 0.15s ease;
    }
    @keyframes bulkPopIn {
        from { opacity: 0; transform: translateX(-50%) translateY(8px) scale(0.97); }
        to { opacity: 1; transform: translateX(-50%) translateY(0) scale(1); }
    }
    @keyframes filterDropIn {
        from { opacity: 0; transform: translateY(8px) scale(0.97); }
        to { opacity: 1; transform: translateY(0) scale(1); }
    }
    .bulk-popover-title {
        font-size: var(--font-size-xs);
        font-weight: var(--font-weight-bold);
        color: var(--color-text-muted);
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: var(--space-2);
    }
    .bulk-popover-input {
        width: 100%;
        padding: var(--space-2) var(--space-3);
        border: 1px solid var(--color-border);
        border-radius: var(--radius-md);
        font-size: var(--font-size-sm);
        color: var(--color-text-primary);
        background: var(--color-bg-primary);
        transition: border-color 0.15s;
    }
    .bulk-popover-input:focus {
        outline: none;
        border-color: var(--color-primary);
        box-shadow: 0 0 0 2px rgba(71, 140, 244, 0.1);
    }
    .bulk-popover-apply {
        margin-top: var(--space-2);
        width: 100%;
        padding: var(--space-2);
        border: none;
        border-radius: var(--radius-md);
        background: var(--color-primary);
        color: #fff;
        font-size: var(--font-size-sm);
        font-weight: var(--font-weight-semibold);
        cursor: pointer;
        transition: opacity 0.15s;
    }
    .bulk-popover-apply:hover { opacity: 0.9; }

    .bulk-color-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        justify-content: center;
    }
    .bulk-color-swatch {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        border: 2px solid var(--color-border);
        cursor: pointer;
        transition: transform 0.1s, border-color 0.15s;
    }
    .bulk-color-swatch:hover { transform: scale(1.15); }

    .bulk-time-row {
        display: flex;
        gap: var(--space-2);
        align-items: center;
    }
    .bulk-time-row input[type="time"] {
        flex: 1;
        padding: var(--space-2);
        border: 1px solid var(--color-border);
        border-radius: var(--radius-md);
        font-size: var(--font-size-sm);
        color: var(--color-text-primary);
        background: var(--color-bg-primary);
    }
    .bulk-time-row input[type="time"]:focus {
        outline: none;
        border-color: var(--color-primary);
    }
    .bulk-time-row span {
        color: var(--color-text-muted);
        font-size: var(--font-size-sm);
    }

    .bulk-type-list {
        display: flex;
        flex-direction: column;
        gap: 2px;
        max-height: 200px;
        overflow-y: auto;
    }
    .bulk-type-option {
        padding: 8px 10px;
        border-radius: var(--radius-sm);
        cursor: pointer;
        font-size: var(--font-size-sm);
        font-weight: var(--font-weight-semibold);
        text-transform: uppercase;
        letter-spacing: 0.03em;
        transition: background 0.1s;
    }
    .bulk-type-option:hover { background: var(--color-bg-secondary); }

    .bulk-note-row {
        display: flex;
        gap: var(--space-2);
        align-items: flex-start;
    }
    .bulk-emoji-pick {
        width: 42px;
        height: 42px;
        border: 1px solid var(--color-border);
        border-radius: var(--radius-md);
        background: var(--color-bg-primary);
        font-size: 20px;
        text-align: center;
        cursor: pointer;
        flex-shrink: 0;
    }

    /* Backdrop for popovers */
    .bulk-backdrop {
        position: fixed;
        inset: 0;
        z-index: 105;
    }

    /* No search results */
    .bulk-no-results {
        text-align: center;
        padding: var(--space-6) var(--space-4);
        color: var(--color-text-muted);
        display: none;
    }
    .bulk-no-results i {
        font-size: var(--font-size-xl);
        margin-bottom: var(--space-2);
        display: block;
    }

    @media (max-width: 480px) {
        .bulk-toolbar { gap: var(--space-2); }
        .bulk-action-btn { padding: 7px 11px; font-size: 11px; }
    }
</style>

<!-- Search & filter toolbar -->
<div class="bulk-toolbar-sticky" id="bulkToolbarSticky">
    <div class="bulk-toolbar" id="bulkToolbar">
        <div class="bulk-toolbar-row">
            <div class="bulk-search-wrapper">
                <i class="fas fa-search bulk-search-icon"></i>
                <input type="text" class="bulk-search" id="bulkSearch" placeholder="Termine durchsuchen…" autocomplete="off">
            </div>
            <button type="button" class="bulk-select-toggle" id="bulkQuickAdd" title="Neue Probe">
                <i class="fas fa-plus"></i>
            </button>
            <button type="button" class="bulk-select-toggle" id="bulkSelectToggle" title="Mehrfachauswahl">
                <i class="fas fa-check-double"></i>
            </button>
        </div>

        <div class="bulk-filter-row" id="bulkFilterRow">
            <button type="button" class="bulk-filter-chip" data-filter="type"><i class="fas fa-tag"></i> Typ</button>
            <button type="button" class="bulk-filter-chip" data-filter="location"><i class="fas fa-map-marker-alt"></i> Ort</button>
            <button type="button" class="bulk-filter-chip" data-filter="color"><i class="fas fa-palette"></i> Farbe</button>
            <button type="button" class="bulk-filter-chip" data-filter="tags"><i class="fas fa-hashtag"></i> Tags</button>
            <button type="button" class="bulk-filter-chip" data-filter="dateRange"><i class="fas fa-calendar"></i> Zeitraum</button>
            <button type="button" class="bulk-series-btn" id="recurringOpen"><i class="fas fa-layer-group"></i> Serie erstellen</button>
        </div>
    </div>
</div>


<div class="bulk-no-results" id="bulkNoResults">
    <i class="fas fa-search"></i>
    <div>Keine Termine gefunden</div>
</div>

<!-- Sticky floating action bar -->
<div class="bulk-action-bar" id="bulkActionBar">
    <div class="bulk-action-inner">
        <div class="bulk-action-panel">
            <div class="bulk-action-header">
                <span class="bulk-action-count" id="bulkCount">0 ausgewählt</span>
                <button type="button" class="bulk-header-btn bulk-header-btn--duplicate" data-bulk="duplicate" title="Duplizieren">
                    <i class="fas fa-copy"></i>
                </button>
                <button type="button" class="bulk-header-btn bulk-header-btn--delete" data-bulk="delete" title="Löschen">
                    <i class="fas fa-trash-alt"></i>
                </button>
                <button type="button" class="bulk-action-close" id="bulkDeselectAll" title="Auswahl aufheben">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="bulk-action-buttons">
                <button type="button" class="bulk-action-btn" data-bulk="type"><i class="fas fa-tag"></i> Typ</button>
                <button type="button" class="bulk-action-btn" data-bulk="location"><i class="fas fa-map-marker-alt"></i> Ort</button>
                <button type="button" class="bulk-action-btn" data-bulk="groups"><i class="fas fa-users"></i> Gruppen</button>
                <button type="button" class="bulk-action-btn" data-bulk="color"><i class="fas fa-palette"></i> Farbe</button>
                <button type="button" class="bulk-action-btn" data-bulk="time"><i class="fas fa-clock"></i> Uhrzeit</button>
                <button type="button" class="bulk-action-btn" data-bulk="tag"><i class="fas fa-hashtag"></i> Tag</button>
                <button type="button" class="bulk-action-btn" data-bulk="note"><i class="fas fa-sticky-note"></i> Notiz</button>

            </div>
        </div>
    </div>
</div>
