<?php
/**
 * Push Reminder Setting
 *
 * Profile card: push reminder before rehearsals (JOinJMD) on/off and lead time (hours:minutes).
 * Saves users.push_reminder_minutes through the settings API ('' = off); PushReminderJob sends.
 *
 * Usage (only when JmdPush::isEnabled() and the user has a Keycloak ID):
 *   $user = users row;
 *   include __DIR__ . '/push-reminder-setting.php';
 */
$pushMinutes = $user['push_reminder_minutes'] ?? null;
$pushLead = $pushMinutes !== null
    ? sprintf('%02d:%02d', intdiv((int)$pushMinutes, 60), (int)$pushMinutes % 60)
    : '01:00';
$pushApiUrl = '/' . ($_SESSION['current_org_slug'] ?? '') . '/' . ($_SESSION['current_orchestra_slug'] ?? '')
    . '/api/settings/user/' . (int)$user['id'];
?>

<div class="modern-card mb-6">
    <div class="modern-card-header">
        <div class="flex items-center">
            <div class="w-8 h-8 bg-amber-100 rounded-lg flex items-center justify-center mr-3">
                <?= icon('bell', 'text-amber-600 text-sm') ?>
            </div>
            <h2 class="text-xl font-semibold text-gray-900">Push-Erinnerung</h2>
        </div>
    </div>
    <div class="modern-card-body">
        <div class="modern-checkbox-group">
            <div class="flex items-start">
                <input type="checkbox" id="push_reminder_enabled" class="modern-checkbox"
                    <?= $pushMinutes !== null ? 'checked' : '' ?>>
                <div class="ml-3 flex-1">
                    <label for="push_reminder_enabled" class="modern-checkbox-label">Vor Proben erinnern</label>
                </div>
            </div>
        </div>

        <div id="push_reminder_lead_row" <?= $pushMinutes === null ? 'hidden' : '' ?>>
            <label for="push_reminder_lead" class="form-label-modern">
                <?= icon('clock', 'form-label-icon') ?> Wie lange vorher (Std:Min)
            </label>
            <input type="time" class="form-input-modern" id="push_reminder_lead" min="00:01" required
                value="<?= $pushLead ?>" style="max-width: 10rem;">
        </div>
    </div>
</div>

<script>
    (function() {
        const toggle = document.getElementById('push_reminder_enabled');
        const lead = document.getElementById('push_reminder_lead');
        const leadRow = document.getElementById('push_reminder_lead_row');
        const url = <?= json_encode($pushApiUrl) ?>;

        // Chrome fires change after every typed segment: intermediate values like 00:00 are
        // skipped silently there, reportValidity() would reset the segment being typed
        function save(report) {
            let value = '';
            if (toggle.checked) {
                if (!(report ? lead.reportValidity() : lead.checkValidity())) return;
                const [h, m] = lead.value.split(':').map(Number);
                value = String(h * 60 + m);
            }

            window.SettingsEngine?.showSaveState('saving');
            fetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ field: 'push_reminder_minutes', value: value })
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        window.SettingsEngine?.showSaveState('success');
                        return;
                    }
                    window.SettingsEngine?.showSaveState('error');
                    const fieldErrors = Object.values(data.errors || {}).flat();
                    window.notifyError?.(data.error || fieldErrors[0] || 'Fehler beim Speichern');
                })
                .catch(err => {
                    window.SettingsEngine?.showSaveState('error');
                    window.notifyError?.('Netzwerkfehler: ' + (err.message || 'Verbindung fehlgeschlagen'));
                });
        }

        toggle.addEventListener('change', () => {
            leadRow.hidden = !toggle.checked;
            save(true);
        });
        lead.addEventListener('change', () => save(false));
        lead.addEventListener('blur', () => lead.reportValidity());
    })();
</script>
