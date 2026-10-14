<?php
// AI rehearsal import: paste JSON → preview (server dry run) → import selected rows.
// Opened via openAiImportModal() (bulk-select-bar.php). Expects $orchestraBase.
$aiImportBase = '/' . ($orchestraBase ?? (($_SESSION['current_org_slug'] ?? '') . '/' . ($_SESSION['current_orchestra_slug'] ?? '')));
?>
<?php include __DIR__ . '/rehearsal-card-styles.php'; ?>
<style>
/* ── AI Import Dialog ── */
.ai-import-dialog {
    border: none;
    padding: 0;
    background: transparent;
    max-width: 600px;
    width: calc(100% - var(--space-6));
    border-radius: var(--radius-lg);
    overflow: visible;
}
.ai-import-dialog::backdrop {
    background: rgba(0, 0, 0, 0.4);
    animation: ai-fade-in 0.15s ease;
}
.ai-import-panel {
    background: var(--color-bg-primary);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-xl, 0 20px 60px rgba(0,0,0,0.3));
    display: flex;
    flex-direction: column;
    max-height: 80vh;
    animation: ai-scale-in 0.2s ease;
}
@keyframes ai-scale-in {
    from { transform: scale(0.95); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
}
@keyframes ai-fade-in {
    from { opacity: 0; }
    to { opacity: 1; }
}
.ai-import-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: var(--space-4);
    border-bottom: 1px solid var(--color-border);
    flex-shrink: 0;
}
.ai-import-header h3 {
    margin: 0;
    font-size: var(--font-size-lg);
    font-weight: var(--font-weight-semibold);
    color: var(--color-text-primary);
}
.ai-import-close {
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
.ai-import-close:hover { color: var(--color-text-primary); }
.ai-import-body {
    padding: var(--space-4);
    overflow-y: auto;
    flex: 1;
}
.ai-import-footer {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    padding: var(--space-3) var(--space-4);
    border-top: 1px solid var(--color-border);
    flex-shrink: 0;
}
.ai-import-text {
    font-size: 14px;
    margin: 0 0 10px;
    color: var(--color-text-secondary);
}
.ai-import-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 14px;
    border: 1px solid var(--color-primary);
    border-radius: var(--radius-md);
    background: transparent;
    color: var(--color-primary);
    font-size: 13px;
    font-weight: var(--font-weight-semibold);
    cursor: pointer;
    white-space: nowrap;
    transition: all 0.15s ease;
}
.ai-import-btn:hover {
    background: var(--color-primary);
    color: #fff;
}
.ai-import-btn-primary {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    border: none;
    border-radius: var(--radius-md);
    background: var(--color-primary);
    color: white;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.15s ease;
}
.ai-import-btn-primary:hover:not(:disabled) {
    opacity: 0.9;
}
.ai-import-btn-primary:disabled {
    opacity: 0.5;
    cursor: default;
}
.ai-import-btn-secondary {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    background: transparent;
    color: var(--color-text-primary);
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.15s ease;
}
.ai-import-btn-secondary:hover {
    background: var(--color-bg-secondary);
}
.ai-import-textarea {
    width: 100%;
    padding: 12px;
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    font-family: 'SF Mono', 'Monaco', monospace;
    font-size: 12px;
    resize: vertical;
    color: var(--color-text-primary);
    transition: border-color 0.15s;
}
.ai-import-textarea:focus {
    outline: none;
    border-color: var(--color-primary);
    box-shadow: 0 0 0 3px rgba(71, 140, 244, 0.1);
}
.ai-import-textarea.prompt-box {
    height: 120px;
    background: var(--color-bg-secondary);
}
.ai-import-textarea.json-box {
    height: 200px;
    background: var(--color-bg-primary);
}
.ai-import-error {
    padding: 10px;
    border-radius: var(--radius-md);
    background: rgba(239, 68, 68, 0.1);
    border: 1px solid var(--color-danger, #ef4444);
    color: var(--color-danger, #ef4444);
    font-size: 13px;
    margin-bottom: 15px;
}

/* Preview list: checkbox + the normal rehearsal card */
.ai-preview-row {
    display: flex;
    gap: var(--space-2);
    align-items: center;
}
.ai-preview-check {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    min-height: 44px;
    flex-shrink: 0;
    cursor: pointer;
}
.ai-preview-check input { width: 18px; height: 18px; cursor: pointer; }
.ai-preview-check input:disabled { cursor: default; }
.ai-preview-card { flex: 1; min-width: 0; }
.ai-preview-row.is-off .ai-preview-card { opacity: 0.45; }
.ai-preview-msg {
    margin: -4px 0 var(--space-2) 44px;
    font-size: 13px;
    color: var(--color-text-secondary);
}
.ai-preview-msg.is-error { color: var(--color-danger, #ef4444); }
.ai-preview-missing { padding: var(--space-3) 0; color: var(--color-text-muted); }

@media (max-width: 640px) {
    .ai-import-dialog {
        width: 100%;
        max-width: none;
        height: 100vh;
        height: 100dvh;
        max-height: none;
        margin: 0;
        border-radius: 0;
    }
    .ai-import-panel {
        height: 100%;
        max-height: none;
        border-radius: 0;
    }
}
</style>

<dialog class="ai-import-dialog" id="aiImportModal" aria-labelledby="aiImportTitle">
    <div class="ai-import-panel">
        <div class="ai-import-header">
            <h3 id="aiImportTitle">KI Probenplan-Import</h3>
            <button type="button" class="ai-import-close" onclick="closeAiImportModal()" aria-label="Schließen">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="ai-import-body">
            <div id="aiImportError" class="ai-import-error" role="alert" hidden></div>

            <div id="aiImportInput">
                <p class="ai-import-text">1. Prompt kopieren und mit deinem Probenplan (PDF, Text) an einen KI-Chatbot schicken:</p>
                <div style="margin-bottom: 20px;">
                    <textarea id="aiPromptTextarea" readonly class="ai-import-textarea prompt-box"></textarea>
                    <div style="display: flex; justify-content: flex-end; margin-top: 5px;">
                        <button type="button" id="aiCopyBtn" onclick="copyAiPrompt()" class="ai-import-btn">
                            <i class="fas fa-copy"></i> Prompt kopieren
                        </button>
                    </div>
                </div>

                <p class="ai-import-text">2. Antwort der KI hier einfügen:</p>
                <textarea id="aiJsonTextarea" placeholder='{"rehearsals": [...]}' class="ai-import-textarea json-box"></textarea>
            </div>

            <div id="aiImportPreview" hidden>
                <p id="aiPreviewCount" class="ai-import-text"></p>
                <div id="aiPreviewList"></div>
            </div>
        </div>

        <div class="ai-import-footer">
            <button type="button" id="aiBackBtn" class="ai-import-btn-secondary">Abbrechen</button>
            <button type="button" id="aiImportBtn" class="ai-import-btn-primary" onclick="aiImportPrimary()">Vorschau</button>
        </div>
    </div>
</dialog>

<script>
(function () {
    const BASE = <?= json_encode($aiImportBase, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const UNDO_KEY = 'aiImportUndo';
    const UNDO_FAILED_KEY = 'aiImportUndoFailed';

    const modal = document.getElementById('aiImportModal');
    const errorBox = document.getElementById('aiImportError');
    const promptBox = document.getElementById('aiPromptTextarea');
    const jsonBox = document.getElementById('aiJsonTextarea');
    const inputView = document.getElementById('aiImportInput');
    const previewView = document.getElementById('aiImportPreview');
    const countLine = document.getElementById('aiPreviewCount');
    const list = document.getElementById('aiPreviewList');
    const backBtn = document.getElementById('aiBackBtn');
    const primaryBtn = document.getElementById('aiImportBtn');

    let payload = null; // parsed JSON of the current preview
    let busy = false;

    function esc(s) {
        const d = document.createElement('div');
        d.textContent = s == null ? '' : String(s);
        return d.innerHTML;
    }

    function showError(msg) {
        errorBox.textContent = msg;
        errorBox.hidden = !msg;
    }

    function parseJson(raw) {
        const fenced = raw.match(/```(?:json)?\s*([\s\S]*?)\s*```/);
        return JSON.parse(fenced ? fenced[1] : raw);
    }

    function post(url, body) {
        return fetch(BASE + url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify(body)
        }).then(r => r.json().catch(() => ({ success: false })));
    }

    function setView(preview) {
        inputView.hidden = preview;
        previewView.hidden = !preview;
        backBtn.textContent = preview ? 'Zurück' : 'Abbrechen';
        updatePrimary();
    }

    function selectedIndexes() {
        return Array.from(list.querySelectorAll('input:checked')).map(cb => Number(cb.value));
    }

    function updatePrimary() {
        if (previewView.hidden) {
            primaryBtn.textContent = 'Vorschau';
            primaryBtn.disabled = busy;
            return;
        }
        const n = selectedIndexes().length;
        primaryBtn.textContent = n === 1 ? '1 Termin importieren' : n + ' Termine importieren';
        primaryBtn.disabled = busy || n === 0;
    }

    function renderPreview(rows) {
        const sorted = rows.slice().sort((a, b) => {
            const sa = a.data.start || '9999', sb = b.data.start || '9999';
            return sa < sb ? -1 : sa > sb ? 1 : a.index - b.index;
        });
        countLine.textContent = rows.length === 1 ? '1 Termin' : rows.length + ' Termine';
        list.innerHTML = sorted.map(row => {
            const isError = row.status === 'error';
            const checked = !isError && !row.duplicate && !row.weekday_mismatch;
            const msg = isError ? row.errors.join(' · ') : [
                row.duplicate ? 'Schon vorhanden' : '',
                row.weekday_mismatch || ''
            ].filter(Boolean).join(' · ');
            const card = row.html || '<div class="ai-preview-missing">Eintrag ' + (row.index + 1) + '</div>';
            return '<div class="ai-preview-row' + (checked ? '' : ' is-off') + '">' +
                    '<label class="ai-preview-check"><input type="checkbox" value="' + row.index + '"' +
                        (isError ? ' disabled' : '') + (checked ? ' checked' : '') + ' aria-label="Übernehmen"></label>' +
                    '<div class="ai-preview-card">' + card + '</div>' +
                '</div>' +
                (msg ? '<div class="ai-preview-msg' + (isError ? ' is-error' : '') + '">' + esc(msg) + '</div>' : '');
        }).join('');
    }

    list.addEventListener('change', e => {
        const row = e.target.closest('.ai-preview-row');
        if (row) row.classList.toggle('is-off', !e.target.checked);
    });
    list.addEventListener('change', updatePrimary);

    function setBusy(on, label) {
        busy = on;
        updatePrimary();
        if (on) primaryBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> ' + label;
    }

    function preview() {
        const raw = jsonBox.value.trim();
        if (!raw) {
            showError('Bitte die Antwort der KI einfügen.');
            return;
        }
        try {
            payload = parseJson(raw);
        } catch (e) {
            showError('Das ist kein gültiges JSON.');
            return;
        }
        showError('');
        setBusy(true, 'Prüfe…');
        post('/rehearsals/ai-import/preview', payload).then(data => {
            setBusy(false);
            if (!data.success) {
                showError(data.message || 'Vorschau fehlgeschlagen.');
                return;
            }
            renderPreview(data.rows);
            setView(true);
        }).catch(() => {
            setBusy(false);
            showError('Netzwerkfehler.');
        });
    }

    function importSelected() {
        const selected = selectedIndexes();
        if (!selected.length) return;
        const body = Array.isArray(payload) ? { rehearsals: payload } : Object.assign({}, payload);
        body.selected = selected;
        showError('');
        setBusy(true, 'Importiere…');
        post('/rehearsals/ai-import', body).then(data => {
            if (!data.success) {
                if (data.rows) renderPreview(data.rows); // re-validated server-side
                setBusy(false);
                showError(data.message || 'Import fehlgeschlagen.');
                return;
            }
            try {
                sessionStorage.setItem(UNDO_KEY, JSON.stringify({ base: BASE, ids: data.created, total: selected.length }));
            } catch (e) {}
            window.location.reload();
        }).catch(() => {
            setBusy(false);
            showError('Netzwerkfehler.');
        });
    }

    window.aiImportPrimary = function () {
        if (busy) return;
        if (previewView.hidden) preview(); else importSelected();
    };

    backBtn.addEventListener('click', () => {
        if (previewView.hidden) {
            closeAiImportModal();
            return;
        }
        showError('');
        setView(false);
    });

    window.openAiImportModal = function () {
        showError('');
        jsonBox.value = '';
        payload = null;
        list.innerHTML = '';
        setView(false);
        promptBox.value = 'Lade Prompt…';
        fetch(BASE + '/rehearsals/ai-import-prompt')
            .then(r => r.json())
            .then(data => { promptBox.value = data.success ? data.prompt : 'Fehler beim Laden des Prompts.'; })
            .catch(() => { promptBox.value = 'Netzwerkfehler beim Laden des Prompts.'; });
        modal.showModal();
    };

    window.closeAiImportModal = function () {
        modal.close();
    };

    // Undo = the normal per-rehearsal delete, for exactly the ids the import created.
    // Always reloads, so no deleted card stays on the page; failures are reported after the reload.
    function undoImport(ids) {
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
        return Promise.all(ids.map(id =>
            fetch(BASE + '/rehearsals/delete/' + id, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json' },
                body: 'id=' + id + '&csrf_token=' + encodeURIComponent(csrf)
            }).then(r => r.json()).then(d => !!d.success).catch(() => false)
        )).then(results => {
            const failed = results.filter(ok => !ok).length;
            if (failed) {
                try {
                    sessionStorage.setItem(UNDO_FAILED_KEY, JSON.stringify({ base: BASE, failed: failed }));
                } catch (e) {}
            }
            window.location.reload();
            return new Promise(() => {}); // keep the toast busy until the page is gone
        });
    }

    function takeStored(key) {
        try {
            const raw = sessionStorage.getItem(key);
            sessionStorage.removeItem(key);
            return JSON.parse(raw || 'null');
        } catch (e) {
            return null;
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        const undoFailed = takeStored(UNDO_FAILED_KEY);
        const failed = undoFailed && undoFailed.base === BASE ? Number(undoFailed.failed) : 0;
        if (failed > 0) {
            window.notifyError?.(failed === 1 ? '1 Termin konnte nicht gelöscht werden' : failed + ' Termine konnten nicht gelöscht werden');
        }

        const undo = takeStored(UNDO_KEY);
        if (!undo || undo.base !== BASE || !Array.isArray(undo.ids) || !window.notifySuccess) return;
        const ids = undo.ids.map(Number).filter(id => id > 0);
        if (!ids.length) return;
        const n = ids.length;
        const title = n < undo.total ? n + ' von ' + undo.total + ' Terminen importiert'
            : (n === 1 ? '1 Termin importiert' : n + ' Termine importiert');
        window.notifySuccess(title, {
            timer: 8000,
            showConfirmButton: true,
            confirmButtonText: 'Rückgängig',
            confirmButtonColor: 'var(--color-primary)',
            showLoaderOnConfirm: true,
            // SweetAlert disables the button on click and keeps the toast open while this runs
            preConfirm: () => {
                window.Swal?.stopTimer();
                return undoImport(ids);
            }
        });
    });

    window.copyAiPrompt = function () {
        const btn = document.getElementById('aiCopyBtn');
        const done = () => {
            const original = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-check"></i> Kopiert';
            setTimeout(() => { btn.innerHTML = original; }, 2000);
        };
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(promptBox.value).then(done);
        } else {
            promptBox.select();
            document.execCommand('copy');
            done();
        }
    };
})();
</script>
