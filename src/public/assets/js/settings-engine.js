/**
 * SettingsEngine — auto-save / inline-edit controller.
 *
 * Auto-binds to elements with [data-field][data-save-mode="auto"].
 * Sends POST to /api/settings/{entity}/{entityId} per field change.
 */
(function () {
    'use strict';

    const DEBOUNCE_TEXT_MS = 500;

    // ── Init ──────────────────────────────────────────────────────

    function initSettingsEngine() {
        document.querySelectorAll('[data-field][data-save-mode="auto"]').forEach(bindField);
    }

    function bindField(el) {
        const fieldType = el.dataset.fieldType || detectType(el);

        if (fieldType === 'toggle') {
            el.addEventListener('change', () => saveField(el));
        } else if (fieldType === 'color' || fieldType === 'select' || fieldType === 'theme') {
            el.addEventListener('change', () => saveField(el));
        } else {
            // text / secret / textarea — debounced blur
            let timer;
            el.addEventListener('input', () => {
                clearTimeout(timer);
                timer = setTimeout(() => saveField(el), DEBOUNCE_TEXT_MS);
            });
            el.addEventListener('blur', () => {
                clearTimeout(timer);
                saveField(el);
            });
        }
    }

    // ── Save ──────────────────────────────────────────────────────

    let pendingSaves = new Map();

    function saveField(el) {
        const field = el.dataset.field;
        const entity = el.dataset.entity;
        const entityId = el.dataset.entityId;

        if (!field || !entity || !entityId) return;

        let value;
        if (el.type === 'checkbox') {
            value = el.checked ? '1' : '0';
        } else {
            value = el.value;
        }

        // Skip if value unchanged
        const prevKey = `${entity}:${entityId}:${field}`;
        if (pendingSaves.get(prevKey) === value && el._settingsLastSaved === value) return;
        pendingSaves.set(prevKey, value);

        // Client-side required check
        const required = el.hasAttribute('required') || el.dataset.required === 'true';
        if (required && value === '') {
            showFieldError(el, 'Dieses Feld ist erforderlich');
            return;
        }

        clearFieldError(el);
        showSaveState('saving');

        const orchestraId = el.dataset.orchestraId
            || document.querySelector('[data-orchestra-id]')?.dataset.orchestraId
            || window.ORCHESTRA_ID
            || '';

        const url = `/${orchestraId}/api/settings/${entity}/${entityId}`;

        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ field, value }),
        })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    el._settingsLastSaved = value;
                    showSaveState('success');
                } else {
                    const msg = data.errors?.[field]?.[0] || data.error || 'Fehler beim Speichern';
                    showFieldError(el, msg);
                    showSaveState('error');
                    if (window.notifyError) {
                        window.notifyError(data.error || 'Fehler beim Speichern');
                    }
                }
            })
            .catch(err => {
                showSaveState('error');
                if (window.notifyError) {
                    window.notifyError('Netzwerkfehler: ' + (err.message || 'Verbindung fehlgeschlagen'));
                }
            });
    }

    // ── UI Feedback ──────────────────────────────────────────────

    let stateTimer;

    function showSaveState(state) {
        clearTimeout(stateTimer);

        const indicator = document.getElementById('settingsSaveIndicator');
        if (!indicator) return;

        // Set text content based on state
        let text = '';
        if (state === 'saving') {
            text = 'Speichert...';
        } else if (state === 'success') {
            text = 'Gespeichert ✓';
        } else if (state === 'error') {
            text = 'Fehler beim Speichern';
        }

        // Update text content safely
        // specific structure check for settings-renderer vs simple div
        const textSpan = indicator.querySelector('.indicator-text');
        if (textSpan) {
            textSpan.textContent = text;
        } else {
            indicator.textContent = text;
        }

        indicator.className = 'settings-save-indicator ' + state;
        indicator.style.display = 'flex';

        if (state === 'success') {
            stateTimer = setTimeout(() => {
                indicator.style.display = 'none';
            }, 1500);
        } else if (state === 'error') {
            stateTimer = setTimeout(() => {
                indicator.style.display = 'none';
            }, 3000);
        }
    }

    function showFieldError(el, message) {
        clearFieldError(el);
        const wrapper = el.closest('.form-group-modern') || el.parentElement;
        if (!wrapper) return;
        const err = document.createElement('div');
        err.className = 'settings-field-error';
        err.textContent = message;
        wrapper.appendChild(err);
        el.classList.add('error');
    }

    function clearFieldError(el) {
        el.classList.remove('error');
        const wrapper = el.closest('.form-group-modern') || el.parentElement;
        if (!wrapper) return;
        wrapper.querySelectorAll('.settings-field-error').forEach(e => e.remove());
    }

    function detectType(el) {
        if (el.type === 'checkbox') return 'toggle';
        if (el.type === 'color') return 'color';
        if (el.tagName === 'SELECT') return 'select';
        return 'text';
    }

    // ── Bootstrap ────────────────────────────────────────────────

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSettingsEngine);
    } else {
        initSettingsEngine();
    }

    // Expose for manual re-init after dynamic content and manual save state control
    window.SettingsEngine = { init: initSettingsEngine, saveField, showSaveState };
})();
