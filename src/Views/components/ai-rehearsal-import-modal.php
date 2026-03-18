<!-- AI Rehearsal Import Modal -->
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
.ai-import-btn-primary:hover {
    opacity: 0.9;
    transform: translateY(-1px);
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
    font-family: 'Kantumruy Pro', 'SF Mono', 'Monaco', monospace;
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
</style>

<dialog class="ai-import-dialog" id="aiImportModal">
    <div class="ai-import-panel">
        <div class="ai-import-header">
            <h3>KI Probenplan-Import</h3>
            <button type="button" class="ai-import-close" onclick="closeAiImportModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="ai-import-body">
            <p style="font-size: 14px; margin-bottom: 10px; color: var(--color-text-secondary);">
                1. Kopiere diesen generierten Prompt und sende ihn zusammen mit deinem Probenplan (PDF, Text) an einen KI-Chatbot deiner Wahl:
            </p>
            
            <div style="position: relative; margin-bottom: 20px;">
                <textarea id="aiPromptTextarea" readonly class="ai-import-textarea prompt-box"></textarea>
                <div style="display: flex; justify-content: space-between; margin-top: 5px;">
                    <span id="aiPromptLoading" style="font-size: 12px; color: var(--color-primary); display: none;"><i class="fas fa-spinner fa-spin"></i> Lade Prompt...</span>
                    <button type="button" onclick="copyAiPrompt(event)" class="ai-import-btn" style="margin-left: auto;">
                        <i class="fas fa-copy"></i> Prompt kopieren
                    </button>
                </div>
            </div>

            <p style="font-size: 14px; margin-bottom: 10px; color: var(--color-text-secondary);">
                2. Füge hier das JSON ein, das du von der KI zurückbekommen hast:
            </p>
            
            <div style="margin-bottom: 20px;">
                <textarea id="aiJsonTextarea" placeholder='{"rehearsals": [...]}' class="ai-import-textarea json-box"></textarea>
            </div>

            <div id="aiImportError" style="display: none; padding: 10px; border-radius: var(--radius-md); background: rgba(239, 68, 68, 0.1); border: 1px solid var(--color-danger, #ef4444); color: var(--color-danger, #ef4444); font-size: 13px; margin-bottom: 15px;">
                <!-- Error text here -->
            </div>
            
            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" onclick="closeAiImportModal()" class="ai-import-btn-secondary">
                    Abbrechen
                </button>
                <button type="button" id="aiImportBtn" onclick="submitAiImport()" class="ai-import-btn-primary">
                    <i class="fas fa-cloud-upload-alt"></i> Importieren
                </button>
            </div>
        </div>
    </div>
</dialog>

<script>
function openAiImportModal() {
    const modal = document.getElementById('aiImportModal');
    if (!modal) return;
    
    document.getElementById('aiImportError').style.display = 'none';
    document.getElementById('aiJsonTextarea').value = '';
    document.getElementById('aiPromptTextarea').value = 'Lade Prompt...';
    document.getElementById('aiPromptLoading').style.display = 'inline-block';
    
    // Fetch personalized prompt
    fetch('/<?= $orchestraBase ?>/rehearsals/ai-import-prompt')
        .then(r => r.json())
        .then(data => {
            document.getElementById('aiPromptLoading').style.display = 'none';
            if (data.success) {
                document.getElementById('aiPromptTextarea').value = data.prompt;
            } else {
                document.getElementById('aiPromptTextarea').value = 'Fehler beim Laden der Vorlage.';
            }
        })
        .catch(e => {
            document.getElementById('aiPromptLoading').style.display = 'none';
            document.getElementById('aiPromptTextarea').value = 'Netzwerkfehler beim Laden der Vorlage.';
        });
        
    modal.showModal();
}

function closeAiImportModal() {
    const modal = document.getElementById('aiImportModal');
    if (modal) modal.close();
}

function copyAiPrompt(event) {
    const textarea = document.getElementById('aiPromptTextarea');
    textarea.select();
    document.execCommand('copy');
    
    // Visual feedback
    const btn = event.currentTarget;
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-check"></i> Kopiert!';
    setTimeout(() => { btn.innerHTML = originalText; }, 2000);
}

function submitAiImport() {
    const rawJson = document.getElementById('aiJsonTextarea').value;
    const errorBox = document.getElementById('aiImportError');
    const btn = document.getElementById('aiImportBtn');
    
    if (!rawJson.trim()) {
        errorBox.textContent = 'Bitte füge das JSON ein.';
        errorBox.style.display = 'block';
        return;
    }
    
    let parsedJson;
    try {
        // Find JSON part if markdown blocks are included
        let jsonStr = rawJson;
        const match = jsonStr.match(/```(?:json)?\s*([\s\S]*?)\s*```/);
        if (match) {
            jsonStr = match[1];
        }
        parsedJson = JSON.parse(jsonStr);
    } catch (e) {
        errorBox.textContent = 'Ungültiges JSON Format. Bitte kopiere exakt die JSON Antwort.';
        errorBox.style.display = 'block';
        return;
    }
    
    errorBox.style.display = 'none';
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verarbeite...';
    
    fetch('/<?= $orchestraBase ?>/rehearsals/ai-import', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(parsedJson)
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-cloud-upload-alt"></i> Importieren';
        
        if (data.success) {
            closeAiImportModal();
            
            // Show warning alert before reloading
            if (window.Swal) {
                Swal.fire({
                    icon: 'success',
                    title: data.count + ' Termine importiert!',
                    html: `
                        <p style="margin-bottom: 15px;">Die Termine wurden erfolgreich hinzugefügt.</p>
                        <div style="background: rgba(245, 158, 11, 0.1); border-left: 4px solid var(--color-warning, #f59e0b); padding: 12px; text-align: left; border-radius: 4px; font-size: 14px;">
                            <strong>Achtung:</strong> KI-Modelle können manchmal Eigenschaften wie Orte, Uhrzeiten oder Gruppen vermischen.<br><br>
                            Bitte überprüfe die importierten Termine auf Korrektheit. Du kannst dazu gezielt nach dem Tag <strong>#importiert</strong> filtern.
                        </div>
                    `,
                    confirmButtonText: 'Verstanden, Seite neu laden',
                    confirmButtonColor: 'var(--color-primary)',
                    allowOutsideClick: false
                }).then(() => {
                    window.location.reload();
                });
            } else {
                // Fallback if Swal is not loaded
                alert(data.count + ' Termine importiert!\n\nAchtung: KI-Modelle können manchmal Eigenschaften vermischen. Bitte überprüfe die importierten Termine auf Korrektheit. Du kannst dazu gezielt nach dem Tag #importiert filtern.');
                window.location.reload();
            }
        } else {
            errorBox.textContent = data.message || 'Fehler beim Import.';
            errorBox.style.display = 'block';
        }
    })
    .catch(e => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-cloud-upload-alt"></i> Importieren';
        errorBox.textContent = 'Netzwerkfehler beim Import.';
        errorBox.style.display = 'block';
    });
}
</script>
