<?php
/**
 * Calendar Integration Modal Component
 *
 * Usage: include from promises/index.php after ensuring user is logged in.
 * Expects: $calendarCsrfToken (string)
 */
$calendarCsrfToken = $calendarCsrfToken ?? \App\Core\CSRF::getToken();
?>

<style>
/* CALENDAR INTEGRATION MODAL */
.cal-modal-backdrop {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.55);
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
    z-index: var(--z-modal, 1000);
    align-items: center;
    justify-content: center;
    padding: var(--space-4);
}
.cal-modal-backdrop.open {
    display: flex;
}

.cal-modal {
    background: var(--color-surface, #fff);
    border: 1px solid var(--color-border, rgba(0,0,0,.08));
    border-radius: var(--radius-2xl, 20px);
    box-shadow: 0 24px 60px rgba(0,0,0,.18), 0 4px 16px rgba(0,0,0,.08);
    width: 100%;
    max-width: 580px;
    max-height: 90dvh;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    animation: cal-slide-in .22s cubic-bezier(.34,1.56,.64,1) both;
}

@keyframes cal-slide-in {
    from { opacity: 0; transform: translateY(24px) scale(.96); }
    to   { opacity: 1; transform: translateY(0) scale(1); }
}

.cal-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: var(--space-5) var(--space-6) var(--space-4);
    border-bottom: 1px solid var(--color-border, rgba(0,0,0,.08));
    background: var(--color-surface, #fff);
    z-index: 2;
}
.cal-modal-title {
    display: flex;
    align-items: center;
    gap: var(--space-3);
    font-size: var(--font-size-lg, 1.125rem);
    font-weight: 600;
    color: var(--color-text, #1a1a1a);
}
.cal-modal-title .cal-icon {
    width: 36px;
    height: 36px;
    background: linear-gradient(135deg, #478cf4 0%, #7b5fe6 100%);
    border-radius: var(--radius-lg, 12px);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 1rem;
    flex-shrink: 0;
}
.cal-modal-close {
    background: none;
    border: none;
    cursor: pointer;
    color: var(--color-gray-400, #9ca3af);
    font-size: 1.25rem;
    padding: var(--space-1);
    border-radius: var(--radius-md, 8px);
    transition: color .15s, background .15s;
    line-height: 1;
}
.cal-modal-close:hover {
    color: var(--color-text, #1a1a1a);
    background: var(--color-gray-100, #f3f4f6);
}

.cal-modal-body {
    padding: var(--space-5) var(--space-6) var(--space-6);
    display: flex;
    flex-direction: column;
    gap: var(--space-6);
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
    flex: 1;
    background: var(--color-surface, #fff);
}

/* Platform table */
.cal-platform-table {
    border: 1px solid var(--color-border, rgba(0,0,0,.08));
    border-radius: var(--radius-xl, 16px);
    overflow: hidden;
    font-size: var(--font-size-sm, .875rem);
}
.cal-platform-table table {
    width: 100%;
    border-collapse: collapse;
}
.cal-platform-table thead th {
    background: var(--color-gray-50, #f9fafb);
    padding: var(--space-2) var(--space-3);
    text-align: left;
    font-weight: 600;
    font-size: var(--font-size-xs, .75rem);
    color: var(--color-gray-500, #6b7280);
    text-transform: uppercase;
    letter-spacing: .04em;
    border-bottom: 1px solid var(--color-border, rgba(0,0,0,.08));
}
.cal-platform-table tbody tr {
    border-bottom: 1px solid var(--color-border, rgba(0,0,0,.06));
}
.cal-platform-table tbody tr:last-child {
    border-bottom: none;
}
.cal-platform-table tbody td {
    padding: var(--space-3);
    vertical-align: middle;
}
.cal-platform-name {
    display: flex;
    align-items: center;
    gap: var(--space-2);
    font-weight: 500;
    color: var(--color-text, #1a1a1a);
}
.cal-platform-name .cal-platform-icon {
    font-size: 1.1rem;
    width: 22px;
    text-align: center;
}
.cal-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: var(--font-size-xs, .75rem);
    padding: 2px 8px;
    border-radius: var(--radius-full, 9999px);
    font-weight: 500;
    white-space: nowrap;
}
.cal-badge.yes  { background: rgba(16,185,129,.12); color: #059669; }
.cal-badge.kinda{ background: rgba(245,158,11,.12); color: #d97706; }
.cal-badge.no   { background: rgba(239,68,68,.1);  color: #dc2626; }

/* Section blocks */
.cal-section {
    display: flex;
    flex-direction: column;
    gap: var(--space-3);
}
.cal-section-title {
    font-size: var(--font-size-sm, .875rem);
    font-weight: 600;
    color: var(--color-text, #1a1a1a);
    display: flex;
    align-items: center;
    gap: var(--space-2);
}
.cal-section-title i {
    color: var(--color-primary, #478cf4);
}
.cal-section-desc {
    font-size: var(--font-size-sm, .875rem);
    color: var(--color-gray-500, #6b7280);
    line-height: 1.5;
    margin-top: var(--space-1);
}

.cal-input-row {
    display: flex;
    gap: var(--space-2);
    align-items: stretch;
}
.cal-input-row input[type=text] {
    flex: 1;
    border: 1px solid var(--color-border, rgba(0,0,0,.12));
    border-radius: var(--radius-lg, 12px);
    padding: var(--space-2) var(--space-3);
    font-size: var(--font-size-sm, .875rem);
    background: var(--color-gray-50, #f9fafb);
    color: var(--color-text, #1a1a1a);
    min-width: 0;
    font-family: monospace;
}
.cal-input-row input[type=text]:focus {
    outline: 2px solid var(--color-primary, #478cf4);
    outline-offset: -1px;
    background: var(--color-surface, #fff);
}

.cal-btn {
    display: inline-flex;
    align-items: center;
    gap: var(--space-2);
    padding: var(--space-2) var(--space-3);
    border-radius: var(--radius-lg, 12px);
    border: none;
    cursor: pointer;
    font-size: var(--font-size-sm, .875rem);
    font-weight: 500;
    transition: all .15s;
    white-space: nowrap;
    text-decoration: none;
}
.cal-btn-primary {
    background: var(--color-primary, #478cf4);
    color: #fff;
}
.cal-btn-primary:hover { background: #3a7bd5; color: #fff; }
.cal-btn-secondary {
    background: var(--color-gray-100, #f3f4f6);
    color: var(--color-gray-700, #374151);
}
.cal-btn-secondary:hover { background: var(--color-gray-200, #e5e7eb); }
.cal-btn-ghost {
    background: transparent;
    color: var(--color-gray-500, #6b7280);
    border: 1px solid var(--color-border, rgba(0,0,0,.12));
}
.cal-btn-ghost:hover { background: var(--color-gray-50, #f9fafb); }
.cal-btn-danger {
    background: transparent;
    color: var(--color-error, #ef4444);
    border: 1px solid rgba(239,68,68,.25);
    font-size: var(--font-size-xs, .75rem);
    padding: var(--space-1) var(--space-3);
}
.cal-btn-danger:hover { background: rgba(239,68,68,.06); }

.cal-credential-block {
    background: var(--color-gray-50, #f9fafb);
    border: 1px solid var(--color-border, rgba(0,0,0,.08));
    border-radius: var(--radius-xl, 16px);
    padding: var(--space-4);
    display: flex;
    flex-direction: column;
    gap: var(--space-3);
}
.cal-credential-row {
    display: flex;
    flex-direction: column;
    gap: var(--space-1);
}
.cal-credential-label {
    font-size: var(--font-size-xs, .75rem);
    font-weight: 600;
    color: var(--color-gray-500, #6b7280);
    text-transform: uppercase;
    letter-spacing: .04em;
}

/* Loading / generate state */
.cal-generate-hint-minimal {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--space-3);
    padding: var(--space-4);
    background: var(--color-gray-50, #f9fafb);
    border: 1px dashed var(--color-border, rgba(0,0,0,.15));
    border-radius: var(--radius-xl, 16px);
}
.cal-hint-text {
    font-size: var(--font-size-sm, .875rem);
    color: var(--color-gray-600, #4b5563);
    flex: 1;
}
.cal-generate-hint-minimal .cal-btn {
    white-space: nowrap;
    padding: var(--space-2) var(--space-3);
}

/* Fancy Top Sync Button */
.cal-top-sync-btn {
    background: linear-gradient(135deg, rgba(71,140,244,.12) 0%, rgba(123,95,230,.12) 100%) !important;
    color: var(--color-primary, #478cf4) !important;
    border: 1px solid rgba(71,140,244,.25) !important;
    margin-right: var(--space-2);
    font-size: 1rem !important;
    padding: 0 !important;
    width: 32px;
    height: 32px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 50% !important;
}
.cal-top-sync-btn:hover {
    background: linear-gradient(135deg, rgba(71,140,244,.2) 0%, rgba(123,95,230,.2) 100%) !important;
    border-color: rgba(71,140,244,.45) !important;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(71,140,244,.18);
}

.cal-divider {
    border: none;
    border-top: 1px solid var(--color-border, rgba(0,0,0,.07));
    margin: 0;
}

.cal-token-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: var(--space-2);
    padding-top: var(--space-2);
    border-top: 1px solid var(--color-border, rgba(0,0,0,.07));
}
.cal-token-footer-note {
    font-size: var(--font-size-xs, .75rem);
    color: var(--color-gray-400, #9ca3af);
}

/* copy feedback */
.cal-copied-tip {
    font-size: var(--font-size-xs, .75rem);
    color: #059669;
    opacity: 0;
    transition: opacity .2s;
}
.cal-copied-tip.show { opacity: 1; }

@media (max-width: 520px) {
    .cal-modal { border-radius: var(--radius-xl, 16px); }
    .cal-modal-header, .cal-modal-body { padding-left: var(--space-4); padding-right: var(--space-4); }
    
    /* Make table compact on mobile instead of hiding columns */
    .cal-platform-table {
        font-size: 0.75rem;
        overflow-x: auto;
    }
    .cal-platform-table thead th {
        padding: var(--space-2);
        font-size: 0.65rem;
        white-space: nowrap;
    }
    .cal-platform-table tbody td {
        padding: var(--space-2);
    }
    .cal-platform-name {
        flex-direction: column;
        align-items: flex-start;
        gap: 0;
    }
    .cal-platform-name small {
        margin-top: 2px;
        font-size: 0.65rem;
    }
    .cal-badge {
        font-size: 0.65rem;
        padding: 2px 5px;
        white-space: normal;
        text-align: center;
        line-height: 1.2;
    }
}
</style>

<!-- Calendar Integration Modal -->
<div id="calendar-integration-modal" class="cal-modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="cal-modal-title">
    <div class="cal-modal">
        <div class="cal-modal-header">
            <div class="cal-modal-title" id="cal-modal-title">
                <div class="cal-icon"><i class="fa-solid fa-calendar-plus"></i></div>
                Kalender-Anbindung
            </div>
            <button class="cal-modal-close" onclick="closeCalendarModal()" aria-label="Schließen">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="cal-modal-body">

            <!-- Platform overview -->
            <div class="cal-section">
                <div class="cal-section-title"><i class="fa-solid fa-circle-info"></i> Was geht mit welcher App?</div>
                <div class="cal-platform-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Kalender-App</th>
                                <th>Proben sehen</th>
                                <th>Ab-/Zusagen</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <div class="cal-platform-name">
                                        <span class="cal-platform-icon">🍎</span>
                                        Apple Kalender <small style="font-weight:400;color:var(--color-gray-400)">(iOS / Mac)</small>
                                    </div>
                                </td>
                                <td><span class="cal-badge yes">✓ Ja – Echtzeit</span></td>
                                <td><span class="cal-badge yes">✓ Voll synchron</span></td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="cal-platform-name">
                                        <span class="cal-platform-icon">🦅</span>
                                        Thunderbird
                                    </div>
                                </td>
                                <td><span class="cal-badge yes">✓ Ja – Echtzeit</span></td>
                                <td><span class="cal-badge yes">✓ Voll synchron</span></td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="cal-platform-name">
                                        <span class="cal-platform-icon">📆</span>
                                        Outlook <small style="font-weight:400;color:var(--color-gray-400)">(Desktop)</small>
                                    </div>
                                </td>
                                <td><span class="cal-badge yes">✓ Ja – Echtzeit</span></td>
                                <td><span class="cal-badge kinda">~ Plugin benötigt</span></td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="cal-platform-name">
                                        <span class="cal-platform-icon">🤖</span>
                                        Android <small style="font-weight:400;color:var(--color-gray-400)">(mit DAVx⁵)</small>
                                    </div>
                                </td>
                                <td><span class="cal-badge yes">✓ Ja – Echtzeit</span></td>
                                <td><span class="cal-badge yes">✓ Voll synchron</span></td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="cal-platform-name">
                                        <span class="cal-platform-icon">🌐</span>
                                        Google Kalender
                                    </div>
                                </td>
                                <td><span class="cal-badge kinda">⚠ Ja – bis 24h Verzögerung</span></td>
                                <td><span class="cal-badge no">✗ Nur in der App</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p class="cal-section-desc">
                    Für <strong>Google Kalender</strong>: Sync dauert bis zu 24h. Ab-/Zusagen nur direkt im Probenplaner.
                    <br>Für <strong>Android</strong>: Installiere <a href="https://play.google.com/store/apps/details?id=at.bitfire.davdroid" target="_blank" rel="noopener" style="color:var(--color-primary)">DAVx⁵</a>, dann klappt alles genauso wie auf Apple. Alternativ gibt es auch kostenlose Open-Source Sync-Apps (z.B. über F-Droid).
                    <br>Für <strong>Outlook Desktop</strong>: Um Ab- und Zusagen in Outlook zu machen, installiere das kostenlose Plugin <a href="https://caldavsynchronizer.org" target="_blank" rel="noopener" style="color:var(--color-primary)">CalDavSynchronizer</a>.
                </p>
            </div>

            <hr class="cal-divider">

            <!-- Generate / show tokens -->
            <div id="cal-content-placeholder" class="cal-generate-hint-minimal">
                <span class="cal-hint-text">Erstelle deinen Kalenderlink (nur für dich sichtbar).</span>
                <button class="cal-btn cal-btn-primary" onclick="generateCalendarTokens()">
                    <i class="fa-solid fa-wand-magic-sparkles"></i> Erstellen
                </button>
            </div>

            <div id="cal-content-loaded" style="display:none;flex-direction:column;gap:var(--space-5);">

                <!-- iCal section -->
                <div class="cal-section">
                    <div class="cal-section-title">
                        <i class="fa-solid fa-rss"></i> Kalender abonnieren
                        <span style="font-size:.75rem;font-weight:400;color:var(--color-gray-400)">– alle Clients</span>
                    </div>
                    <p class="cal-section-desc">
                        Füge diesen Link in deine Kalender-App ein.
                        Mit <strong>„Jetzt öffnen"</strong> startet deine Kalender-App direkt.
                    </p>
                    <div class="cal-input-row">
                        <input type="text" id="cal-ical-url" readonly placeholder="Wird geladen…">
                        <button class="cal-btn cal-btn-secondary" onclick="copyCalUrl('cal-ical-url', 'cal-ical-copied')" title="Kopieren">
                            <i class="fa-solid fa-copy"></i>
                        </button>
                        <a id="cal-webcal-link" class="cal-btn cal-btn-primary" href="#" title="In Kalender-App öffnen">
                            <i class="fa-solid fa-arrow-up-right-from-square"></i> Öffnen
                        </a>
                    </div>
                    <span id="cal-ical-copied" class="cal-copied-tip">✓ Kopiert!</span>
                </div>

                <hr class="cal-divider">

                <!-- CalDAV section -->
                <div class="cal-section">
                    <div class="cal-section-title">
                        <i class="fa-solid fa-arrows-rotate"></i> Bidirektionale Sync
                        <span style="font-size:.75rem;font-weight:400;color:var(--color-gray-400)">Apple / Thunderbird / Android</span>
                    </div>
                    <p class="cal-section-desc">
                        Füge in deiner Kalender-App ein <strong>CalDAV-Konto</strong> hinzu und gib diese Daten ein.
                        So werden Zu- und Absagen auch aus dem Kalender heraus direkt im Probenplaner gespeichert.
                    </p>
                    <div class="cal-credential-block">
                        <div class="cal-credential-row">
                            <span class="cal-credential-label">Server-URL</span>
                            <div class="cal-input-row">
                                <input type="text" id="cal-caldav-url" readonly placeholder="Wird geladen…">
                                <button class="cal-btn cal-btn-secondary" onclick="copyCalUrl('cal-caldav-url', 'cal-caldav-url-copied')" title="Kopieren">
                                    <i class="fa-solid fa-copy"></i>
                                </button>
                            </div>
                            <span id="cal-caldav-url-copied" class="cal-copied-tip">✓ Kopiert!</span>
                        </div>
                        <div class="cal-credential-row">
                            <span class="cal-credential-label">Benutzername</span>
                            <div class="cal-input-row">
                                <input type="text" id="cal-caldav-user" readonly placeholder="Wird geladen…">
                                <button class="cal-btn cal-btn-secondary" onclick="copyCalUrl('cal-caldav-user', 'cal-caldav-user-copied')" title="Kopieren">
                                    <i class="fa-solid fa-copy"></i>
                                </button>
                            </div>
                            <span id="cal-caldav-user-copied" class="cal-copied-tip">✓ Kopiert!</span>
                        </div>
                        <div class="cal-credential-row">
                            <span class="cal-credential-label">Kalender/App-Passwort</span>
                            <div class="cal-input-row">
                                <input type="text" id="cal-caldav-token" readonly placeholder="Wird geladen…">
                                <button class="cal-btn cal-btn-secondary" onclick="copyCalUrl('cal-caldav-token', 'cal-caldav-token-copied')" title="Kopieren">
                                    <i class="fa-solid fa-copy"></i>
                                </button>
                            </div>
                            <span id="cal-caldav-token-copied" class="cal-copied-tip">✓ Kopiert!</span>
                        </div>
                    </div>
                    <p class="cal-section-desc" style="font-size:.75rem;">
                        ⚠️ Das Kalender/App-Passwort ist <strong>nicht</strong> dein normales Probenplaner-Passwort – es ist ein separates Passwort nur für den Kalender-Zugang.
                    </p>
                </div>

                <hr class="cal-divider">

                <div class="cal-token-footer">
                    <span class="cal-token-footer-note">
                        <i class="fa-solid fa-lock" style="margin-right:4px;"></i>
                        Dein persönlicher Link — gib ihn nicht weiter.
                    </span>
                    <button class="cal-btn cal-btn-danger" onclick="revokeCalendarTokens()">
                        <i class="fa-solid fa-rotate"></i> Link zurücksetzen
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
(function () {
    const CSRF = '<?= htmlspecialchars($calendarCsrfToken) ?>';

    window.openCalendarModal = function () {
        document.getElementById('calendar-integration-modal').classList.add('open');
        // Auto-load tokens if user already has them
        fetch('/calendar/tokens/status', { headers: { 'Accept': 'application/json' } })
            .then(r => r.json())
            .then(data => {
                if (data.has_tokens) generateCalendarTokens(false);
            })
            .catch(() => {});
    };

    window.closeCalendarModal = function () {
        document.getElementById('calendar-integration-modal').classList.remove('open');
    };

    // Close on backdrop click
    document.getElementById('calendar-integration-modal').addEventListener('click', function (e) {
        if (e.target === this) closeCalendarModal();
    });

    window.generateCalendarTokens = function (showLoading = true) {
        if (showLoading) {
            document.getElementById('cal-content-placeholder').innerHTML =
                '<span class="cal-hint-text"><i class="fa-solid fa-spinner fa-spin" style="margin-right:8px;color:var(--color-primary)"></i>Erstelle Link…</span>';
        }

        fetch('/calendar/tokens/generate', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'Accept': 'application/json',
            },
            body: 'csrf_token=' + encodeURIComponent(CSRF),
        })
            .then(r => r.json())
            .then(data => {
                if (!data.success) throw new Error(data.error || 'Unbekannter Fehler');
                fillCalendarModal(data);
            })
            .catch(err => {
                if (typeof window.notifyError === 'function') {
                    window.notifyError('Kalenderlink konnte nicht erstellt werden: ' + err.message);
                }
            });
    };

    function fillCalendarModal(data) {
        let icalUrl = data.ical_url;
        let webcalUrl = data.webcal_url;
        
        // Anti-Apple-Sandbox-Localhost Workaround: Apple Calendar outright blocks ANY local network webcal://localhost 
        // string. We replace it with 127.0.0.1 locally to trick the sandbox for local testing.
        if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
            icalUrl = icalUrl.replace('localhost', '127.0.0.1');
            webcalUrl = webcalUrl.replace('localhost', '127.0.0.1');
        }

        document.getElementById('cal-ical-url').value    = icalUrl;
        document.getElementById('cal-webcal-link').href  = webcalUrl;
        document.getElementById('cal-caldav-url').value  = data.caldav_url.replace('localhost', '127.0.0.1');
        document.getElementById('cal-caldav-user').value = data.caldav_user;
        document.getElementById('cal-caldav-token').value= data.caldav_token;

        document.getElementById('cal-content-placeholder').style.display = 'none';
        document.getElementById('cal-content-loaded').style.display      = 'flex';
    }

    window.revokeCalendarTokens = function () {
        if (!confirm('Bist du sicher? Dein bisheriger Link wird ungültig und alle Kalender-Apps müssen neu verbunden werden.')) return;

        fetch('/calendar/tokens/revoke', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'Accept': 'application/json',
            },
            body: 'csrf_token=' + encodeURIComponent(CSRF),
        })
            .then(r => r.json())
            .then(() => {
                document.getElementById('cal-content-loaded').style.display      = 'none';
                document.getElementById('cal-content-placeholder').innerHTML =
                    '<span class="cal-hint-text" style="color:var(--color-error)">Link zurückgesetzt. Neuen erstellen?</span>' +
                    '<button class="cal-btn cal-btn-primary" onclick="generateCalendarTokens()">' +
                    '<i class="fa-solid fa-wand-magic-sparkles"></i> Erstellen</button>';
                document.getElementById('cal-content-placeholder').style.display = 'flex';
                if (typeof window.notifySuccess === 'function') {
                    window.notifySuccess('Kalenderlink zurückgesetzt');
                }
            })
            .catch(() => {
                if (typeof window.notifyError === 'function') {
                    window.notifyError('Fehler beim Zurücksetzen');
                }
            });
    };

    window.copyCalUrl = function (inputId, feedbackId) {
        const input = document.getElementById(inputId);
        if (!input?.value) return;
        navigator.clipboard.writeText(input.value).then(() => {
            const tip = document.getElementById(feedbackId);
            if (!tip) return;
            tip.classList.add('show');
            setTimeout(() => tip.classList.remove('show'), 2000);
        }).catch(() => {
            input.select();
            document.execCommand('copy');
        });
    };
}());
</script>
