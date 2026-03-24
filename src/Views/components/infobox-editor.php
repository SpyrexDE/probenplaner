<?php

/**
 * INFOBOX EDITOR (inline WYSIWYG editor)
 *
 * Include in create.php / edit.php forms.
 * Serializes items to a hidden input `infos` as JSON.
 * On edit pages, supports auto-save via the settings API.
 *
 * @param array $formData['infos']  Existing items (optional)
 * @param bool  $autoSave           Whether to auto-save (edit page)
 * @param string $apiUrl            API URL for auto-save (edit page)
 */

$existingInfos = $formData['infos'] ?? [];
$autoSave = $autoSave ?? false;
$apiUrl = $apiUrl ?? '';
$editorId = $editorId ?? ('infobox-editor-' . uniqid());
?>

<?php if (!defined('INFOBOX_EDITOR_STYLES_LOADED')): define('INFOBOX_EDITOR_STYLES_LOADED', true); ?>
<!-- Picmo Emoji Picker Assets -->
<script>
    if (!window.picmo) {
        var s = document.createElement('script');
        s.src = 'https://cdn.jsdelivr.net/npm/picmo@5.8.5/dist/umd/index.min.js';
        document.head.appendChild(s);
    }
</script>
<style>
    .infobox-editor {
        display: flex;
        flex-direction: column;
        gap: var(--space-2);
    }

    .infobox-editor-item {
        display: flex;
        align-items: flex-start;
        gap: var(--space-2);
        position: relative;
    }

    .infobox-editor-emoji-btn {
        width: 40px;
        height: 40px;
        flex-shrink: 0;
        font-size: 20px;
        border: 1px solid var(--color-border);
        border-radius: var(--radius-md);
        background: var(--color-bg-primary);
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s;
    }

    .infobox-editor-emoji-btn:hover {
        border-color: var(--color-primary);
        background: var(--color-bg-secondary);
    }

    .infobox-editor-text {
        flex: 1;
        min-width: 0;
        font-size: 14px;
        padding: var(--space-2) var(--space-3);
        border: 1px solid var(--color-border);
        border-radius: var(--radius-md);
        background: var(--color-bg-primary);
        color: var(--color-text-primary);
        height: 40px;
        /* Match button height */
    }

    .infobox-editor-text:focus {
        outline: none;
        border-color: var(--color-primary);
        box-shadow: 0 0 0 2px rgba(71, 140, 244, 0.15);
    }

    .infobox-editor-remove {
        width: 30px;
        height: 40px;
        /* Match input height */
        border: none;
        background: none;
        color: var(--color-text-muted);
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: var(--radius-sm);
        font-size: 14px;
        opacity: 0.5;
        transition: all 0.15s ease;
    }

    .infobox-editor-remove:hover {
        opacity: 1;
        color: var(--color-error);
        background: var(--color-error-50);
    }

    .infobox-editor-add {
        display: flex;
        align-items: center;
        gap: var(--space-2);
        padding: var(--space-2) 0;
        cursor: pointer;
        color: var(--color-text-muted);
        font-size: 13px;
        border: none;
        background: none;
        transition: color 0.15s ease;
        width: fit-content;
    }

    .infobox-editor-add:hover {
        color: var(--color-primary);
    }

    /* Emoji Picker Styling Override */
    .picmo__picker {
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-xl);
        border: 1px solid var(--color-border);
        z-index: 9999;
    }
</style>
<?php endif; ?>

<div class="form-section">
    <h3 class="form-section-title">Hinweise</h3>

    <div class="infobox-editor" id="<?= $editorId ?>">
        <!-- Items rendered by JS -->
    </div>

    <input type="hidden" name="infos" id="<?= $editorId ?>-hidden">
</div>

<script>
    (function() {
        const editorId = '<?= $editorId ?>';
        const container = document.getElementById(editorId);
        const hiddenInput = document.getElementById(editorId + '-hidden');
        const autoSave = <?= $autoSave ? 'true' : 'false' ?>;
        const apiUrl = '<?= htmlspecialchars($apiUrl) ?>';

        let items = <?= json_encode(array_map(function ($item) {
                        return [
                            'emoji' => $item['emoji'] ?? '❗',
                            'text' => $item['text'] ?? ''
                        ];
                    }, $existingInfos)) ?>;

        if (!items || items.length === 0) {
            items = [];
        }

        // Emoji Picker State
        let picker = null;
        let pcontainer = null;
        let currentEmojiTargetIndex = -1;

        function initPicker() {
            if (picker) return;

            pcontainer = document.createElement('div');
            pcontainer.style.position = 'absolute';
            pcontainer.style.zIndex = '1000';
            pcontainer.style.display = 'none'; // Hidden by default
            document.body.appendChild(pcontainer);

            picker = picmo.createPicker({
                rootElement: pcontainer,
                showPreview: false,
                autoFocus: 'search',
                messages: {
                    searchPlaceholder: 'Suchen...',
                    noEmojisFound: 'Keine Emojis gefunden',
                    category_smileys_people: 'Smileys & Personen',
                    category_animals_nature: 'Tiere & Natur',
                    category_food_drink: 'Essen & Trinken',
                    category_travel_places: 'Reisen & Orte',
                    category_activities: 'Aktivitäten',
                    category_objects: 'Objekte',
                    category_symbols: 'Symbole',
                    category_flags: 'Flaggen',
                    recents: 'Zuletzt verwendet'
                }
            });

            picker.addEventListener('emoji:select', event => {
                if (currentEmojiTargetIndex >= 0) {
                    items[currentEmojiTargetIndex].emoji = event.emoji;
                    render();
                    serialize();
                    if (autoSave) triggerAutoSave();
                    hidePicker();
                }
            });

            // Close when clicking outside
            document.addEventListener('click', (e) => {
                if (pcontainer.style.display === 'block' &&
                    !pcontainer.contains(e.target) &&
                    !e.target.closest('.infobox-editor-emoji-btn')) {
                    hidePicker();
                }
            });
        }

        function showPicker(index, btnElement) {
            initPicker(); // Ensure initialized
            currentEmojiTargetIndex = index;

            // Positioning
            const rect = btnElement.getBoundingClientRect();
            pcontainer.style.top = (rect.bottom + window.scrollY + 5) + 'px';
            pcontainer.style.left = (rect.left + window.scrollX) + 'px';
            pcontainer.style.display = 'block';
        }

        function hidePicker() {
            if (pcontainer) {
                pcontainer.style.display = 'none';
            }
            currentEmojiTargetIndex = -1;
        }

        function render() {
            let html = '';
            items.forEach((item, i) => {
                html += `
                <div class="infobox-editor-item" data-index="${i}">
                    <button type="button" class="infobox-editor-emoji-btn" data-index="${i}" title="Emoji ändern">
                        ${item.emoji}
                    </button>
                    <input type="text" class="infobox-editor-text" value="${escapeHtml(item.text)}" placeholder="Text für Hinweis" data-field="text" data-index="${i}" maxlength="500">
                    <button type="button" class="infobox-editor-remove" data-index="${i}" title="Entfernen">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
            });

            html += `
            <button type="button" class="infobox-editor-add" id="${editorId}-add">
                <i class="fas fa-plus"></i>
                <span>Hinweis hinzufügen</span>
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
                        field: 'infos',
                        value: JSON.stringify(items)
                    })
                })
                .then(r => r.json())
                .then(data => {
                    if (window.SettingsEngine && window.SettingsEngine.showSaveState) {
                        window.SettingsEngine.showSaveState(data.success ? 'success' : 'error');
                    }
                    if (!data.success && window.notifyError) {
                        window.notifyError(data.error || 'Fehler beim Speichern der Hinweise');
                    }
                })
                .catch(err => {
                    if (window.SettingsEngine && window.SettingsEngine.showSaveState) {
                        window.SettingsEngine.showSaveState('error');
                    }
                    window.notifyError?.('Netzwerkfehler – Hinweise nicht gespeichert');
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
            if (isNaN(idx) || !field || !items[idx]) return;
            items[idx][field] = e.target.value;
            serialize();
            if (autoSave) debouncedSave();
        });

        container.addEventListener('click', function(e) {
            // Remove button
            const removeBtn = e.target.closest('.infobox-editor-remove');
            if (removeBtn) {
                e.preventDefault();
                const idx = parseInt(removeBtn.dataset.index);
                items.splice(idx, 1);
                render();
                if (autoSave) triggerAutoSave();
                return;
            }

            // Emoji button
            const emojiBtn = e.target.closest('.infobox-editor-emoji-btn');
            if (emojiBtn) {
                e.preventDefault();
                const idx = parseInt(emojiBtn.dataset.index);
                showPicker(idx, emojiBtn);
                return;
            }

            // Add button
            if (e.target.closest('.infobox-editor-add')) {
                e.preventDefault();
                items.push({
                    emoji: '❗',
                    text: ''
                });
                render();

                // Focus the new text input
                const newText = container.querySelector(`[data-index="${items.length - 1}"][data-field="text"]`);
                if (newText) newText.focus();
            }
        });

        render();

        // Public API for external callers (e.g. bulk actions)
        container.addInfoItem = (emoji, text) => {
            items.push({ emoji, text });
            render();
            if (autoSave) triggerAutoSave();
        };
    })();
</script>