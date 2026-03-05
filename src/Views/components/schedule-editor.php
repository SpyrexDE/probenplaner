<?php

/**
 * SCHEDULE EDITOR (inline WYSIWYG timeline editor)
 *
 * Include in create.php / edit.php forms.
 * Serializes items to a hidden input `schedule_items` as JSON.
 * On edit pages, supports auto-save via the settings API.
 *
 * @param array $formData['schedule_items']  Existing items (optional)
 * @param bool  $autoSave                   Whether to auto-save (edit page)
 * @param string $apiUrl                    API URL for auto-save (edit page)
 */

$existingItems = $formData['schedule_items'] ?? [];
$autoSave = $autoSave ?? false;
$apiUrl = $apiUrl ?? '';
$editorId = 'schedule-editor-' . uniqid();
?>

<style>
    .schedule-editor {
        display: flex;
        flex-direction: column;
        gap: 0;
    }

    .schedule-editor-item {
        display: flex;
        align-items: center;
        gap: var(--space-2);
        position: relative;
    }

    .schedule-editor-dot-col {
        display: flex;
        flex-direction: column;
        align-items: center;
        width: 12px;
        flex-shrink: 0;
        align-self: stretch;
        position: relative;
        margin-top: 2px;
    }

    .schedule-editor-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: var(--color-primary);
        flex-shrink: 0;
        margin-top: 13px;
        /* Center with input height approx */
        z-index: 2;
        position: relative;
    }

    .schedule-editor-line {
        position: absolute;
        top: 0;
        bottom: -15px;
        left: 50%;
        width: 2px;
        background: var(--color-border);
        transform: translateX(-50%);
        z-index: 1;
    }

    .schedule-editor-item:first-child .schedule-editor-line {
        top: 13px;
        /* Start from dot */
    }

    .schedule-editor-item:last-child .schedule-editor-line {
        bottom: auto;
        height: 13px;
        /* End at dot */
    }

    /* Special case: only one item - no line */
    .schedule-editor-item:only-child .schedule-editor-line {
        display: none;
    }

    .schedule-editor-fields {
        display: flex;
        align-items: center;
        gap: var(--space-2);
        flex: 1;
        min-width: 0;
        padding: 4px 0;
    }

    .schedule-editor-time {
        width: 80px;
        flex-shrink: 0;
        font-size: 13px;
        font-family: 'Kantumruy Pro', 'SF Mono', monospace;
        padding: var(--space-1) var(--space-2);
        border: 1px solid var(--color-border);
        border-radius: var(--radius-sm);
        background: var(--color-bg-primary);
        color: var(--color-text-primary);
        text-align: center;
    }

    .schedule-editor-time:focus {
        outline: none;
        border-color: var(--color-primary);
        box-shadow: 0 0 0 2px rgba(71, 140, 244, 0.15);
    }

    .schedule-editor-label {
        flex: 1;
        min-width: 0;
        font-size: 13px;
        padding: var(--space-1) var(--space-2);
        border: 1px solid var(--color-border);
        border-radius: var(--radius-sm);
        background: var(--color-bg-primary);
        color: var(--color-text-primary);
    }

    .schedule-editor-label:focus {
        outline: none;
        border-color: var(--color-primary);
        box-shadow: 0 0 0 2px rgba(71, 140, 244, 0.15);
    }

    .schedule-editor-remove {
        width: 24px;
        height: 24px;
        border: none;
        background: none;
        color: var(--color-text-muted);
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: var(--radius-sm);
        font-size: 12px;
        flex-shrink: 0;
        opacity: 0.4;
        transition: opacity 0.15s ease, color 0.15s ease;
    }

    .schedule-editor-remove:hover {
        opacity: 1;
        color: var(--color-error);
    }

    .schedule-editor-add {
        display: flex;
        align-items: center;
        gap: var(--space-2);
        padding: var(--space-2) 0;
        cursor: pointer;
        color: var(--color-text-muted);
        font-size: 12px;
        border: none;
        background: none;
        transition: color 0.15s ease;
        margin-left: 20px;
    }

    .schedule-editor-add:hover {
        color: var(--color-primary);
    }

    .schedule-editor-add i {
        width: 8px;
        height: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
</style>

<div class="form-section">
    <h3 class="form-section-title">Ablauf</h3>

    <div class="schedule-editor" id="<?= $editorId ?>">
        <!-- Items rendered by JS -->
    </div>

    <input type="hidden" name="schedule_items" id="<?= $editorId ?>-hidden">
</div>

<script>
    (function() {
        const editorId = '<?= $editorId ?>';
        const container = document.getElementById(editorId);
        const hiddenInput = document.getElementById(editorId + '-hidden');
        const autoSave = <?= $autoSave ? 'true' : 'false' ?>;
        const apiUrl = '<?= htmlspecialchars($apiUrl) ?>';

        let items = <?= json_encode(array_map(function ($item) {
                        return ['time' => substr($item['time'] ?? $item['time_formatted'] ?? '00:00', 0, 5), 'label' => $item['label'] ?? ''];
                    }, $existingItems)) ?>;

        if (items.length === 0) {
            items = [];
        }

        function render() {
            let html = '';
            items.forEach((item, i) => {
                const isLast = i === items.length - 1;
                html += `
                <div class="schedule-editor-item" data-index="${i}">
                    <div class="schedule-editor-dot-col">
                        <div class="schedule-editor-dot"></div>
                        ${!isLast ? '<div class="schedule-editor-line"></div>' : ''}
                    </div>
                    <div class="schedule-editor-fields">
                        <input type="time" class="schedule-editor-time" value="${escapeHtml(item.time)}" data-field="time" data-index="${i}">
                        <input type="text" class="schedule-editor-label" value="${escapeHtml(item.label)}" placeholder="Beschreibung" data-field="label" data-index="${i}" maxlength="255">
                        <button type="button" class="schedule-editor-remove" data-index="${i}" title="Entfernen">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            `;
            });

            html += `
            <button type="button" class="schedule-editor-add" id="${editorId}-add">
                <i class="fas fa-plus"></i>
                <span>Eintrag hinzufügen</span>
            </button>
        `;

            container.innerHTML = html;
            serialize();
        }

        function serialize() {
            hiddenInput.value = JSON.stringify(items);
        }

        function escapeHtml(str) {
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML.replace(/"/g, '&quot;');
        }

        function triggerAutoSave() {
            if (!autoSave || !apiUrl) return;
            serialize();

            if (window.SettingsEngine && window.SettingsEngine.showSaveState) {
                window.SettingsEngine.showSaveState('saving');
            }

            fetch(apiUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        field: 'schedule_items',
                        value: JSON.stringify(items)
                    })
                })
                .then(r => r.json())
                .then(data => {
                    if (window.SettingsEngine && window.SettingsEngine.showSaveState) {
                        window.SettingsEngine.showSaveState(data.success ? 'success' : 'error');
                    }
                    if (!data.success && window.notifyError) {
                        window.notifyError(data.error || 'Fehler beim Speichern des Ablaufs');
                    }
                })
                .catch(err => {
                    if (window.SettingsEngine && window.SettingsEngine.showSaveState) {
                        window.SettingsEngine.showSaveState('error');
                    }
                });
        }

        let saveTimeout = null;

        function debouncedSave() {
            clearTimeout(saveTimeout);
            saveTimeout = setTimeout(triggerAutoSave, 600);
        }

        // Event delegation
        container.addEventListener('input', function(e) {
            const idx = parseInt(e.target.dataset.index);
            const field = e.target.dataset.field;
            if (isNaN(idx) || !field) return;
            items[idx][field] = e.target.value;
            serialize();
            if (autoSave) debouncedSave();
        });

        container.addEventListener('click', function(e) {
            // Remove button
            const removeBtn = e.target.closest('.schedule-editor-remove');
            if (removeBtn) {
                e.preventDefault();
                const idx = parseInt(removeBtn.dataset.index);
                items.splice(idx, 1);
                render();
                if (autoSave) triggerAutoSave();
                return;
            }

            // Add button
            if (e.target.closest('.schedule-editor-add')) {
                e.preventDefault();
                const lastTime = items.length > 0 ? items[items.length - 1].time : '19:00';
                // Increment last time by 15 min
                const [h, m] = lastTime.split(':').map(Number);
                const totalMin = h * 60 + m + 15;
                const newH = String(Math.floor(totalMin / 60) % 24).padStart(2, '0');
                const newM = String(totalMin % 60).padStart(2, '0');
                items.push({
                    time: newH + ':' + newM,
                    label: ''
                });
                render();

                // Focus the new label input
                const newLabel = container.querySelector(`[data-index="${items.length - 1}"][data-field="label"]`);
                if (newLabel) newLabel.focus();
            }
        });

        render();
    })();
</script>