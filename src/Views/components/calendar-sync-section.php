<?php
/**
 * Calendar Sync Section
 *
 * Body content for the Kalender-Sync card on the profile page.
 * Presents two sync modes:
 *   1. Subscribe (iCal) — read-only, works everywhere
 *   2. CalDAV account  — bidirectional, supported platforms only
 *
 * Usage:
 *   $calendarCsrfToken = \App\Core\CSRF::getToken();
 *   include __DIR__ . '/calendar-sync-section.php';
 */
$calendarCsrfToken = $calendarCsrfToken ?? \App\Core\CSRF::getToken();
?>

<style>
/* CALENDAR SYNC SECTION */

/* Option cards */
.calsync-options {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: var(--space-3);
}

@media (max-width: 540px) {
    .calsync-options {
        grid-template-columns: 1fr;
    }
    #calsync-opt-subscribe   { order: 1; }
    #calsync-panel-subscribe { order: 2; }
    #calsync-opt-caldav      { order: 3; }
    #calsync-panel-caldav    { order: 4; }

    .calsync-input-row {
        flex-wrap: wrap;
    }
    #calsync-webcal-link {
        flex: 1 1 100%;
        justify-content: center;
    }
}

.calsync-option {
    border: 1.5px solid var(--color-border, rgba(0,0,0,.10));
    border-radius: var(--radius-xl, 16px);
    padding: var(--space-4);
    display: flex;
    flex-direction: column;
    gap: var(--space-3);
    background: var(--color-surface, #fff);
    transition: border-color .15s, box-shadow .15s;
    cursor: pointer;
    text-align: left;
}

.calsync-option:hover {
    border-color: var(--color-primary, #478cf4);
    box-shadow: 0 0 0 3px rgba(71,140,244,.08);
}

.calsync-option.active {
    border-color: var(--color-primary, #478cf4);
    background: linear-gradient(135deg, rgba(71,140,244,.04) 0%, rgba(123,95,230,.04) 100%);
}

/* Recommended badge on option card */
.calsync-option-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--space-2);
    margin-bottom: var(--space-1);
}

.calsync-recommended-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 10px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .04em;
    padding: 2px 7px;
    border-radius: var(--radius-full, 9999px);
    background: linear-gradient(135deg, #478cf4 0%, #7b5fe6 100%);
    color: #fff;
    white-space: nowrap;
    flex-shrink: 0;
}

.calsync-option-top {
    display: flex;
    align-items: flex-start;
    gap: var(--space-3);
}

.calsync-option-icon-wrap {
    width: 36px;
    height: 36px;
    border-radius: var(--radius-lg, 12px);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
}

.calsync-option-icon-wrap.subscribe {
    background: rgba(16,185,129,.12);
    color: #059669;
}

.calsync-option-icon-wrap.caldav {
    background: rgba(71,140,244,.12);
    color: var(--color-primary, #478cf4);
}

.calsync-option-label {
    font-size: var(--font-size-sm, .875rem);
    font-weight: 600;
    color: var(--color-text, #1a1a1a);
    line-height: 1.3;
}

.calsync-option-desc {
    font-size: var(--font-size-xs, .75rem);
    color: var(--color-gray-500, #6b7280);
    line-height: 1.4;
    margin-top: 2px;
}

/* Platform pill row */
.calsync-platforms {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-top: auto;
}

.calsync-platform-pill {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 11px;
    font-weight: 500;
    padding: 3px 8px;
    border-radius: var(--radius-full, 9999px);
    background: var(--color-gray-100, #f3f4f6);
    color: var(--color-gray-600, #4b5563);
    white-space: nowrap;
}

.calsync-platform-pill img {
    width: 14px;
    height: 14px;
    object-fit: contain;
    flex-shrink: 0;
}

/* Panel that expands when an option is chosen */
.calsync-panel {
    grid-column: 1 / -1;
    display: none;
    flex-direction: column;
    gap: var(--space-4);
    margin-top: var(--space-4);
    padding: var(--space-4);
    background: var(--color-gray-50, #f9fafb);
    border: 1px solid var(--color-border, rgba(0,0,0,.08));
    border-radius: var(--radius-xl, 16px);
    animation: calsync-fade-in .18s ease both;
}

.calsync-panel.visible {
    display: flex;
}

@keyframes calsync-fade-in {
    from { opacity: 0; transform: translateY(6px); }
    to   { opacity: 1; transform: translateY(0); }
}

.calsync-panel-title {
    font-size: var(--font-size-sm, .875rem);
    font-weight: 600;
    color: var(--color-text, #1a1a1a);
    display: flex;
    align-items: center;
    gap: var(--space-2);
}

/* Generate / loading state */
.calsync-generate-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--space-3);
}

.calsync-generate-hint {
    font-size: var(--font-size-sm, .875rem);
    color: var(--color-gray-500, #6b7280);
}

/* Input rows */
.calsync-field-label {
    font-size: var(--font-size-xs, .75rem);
    font-weight: 600;
    color: var(--color-gray-500, #6b7280);
    text-transform: uppercase;
    letter-spacing: .04em;
    margin-bottom: var(--space-1);
}

.calsync-input-row {
    display: flex;
    gap: var(--space-2);
    align-items: stretch;
}

.calsync-input-row input[type="text"] {
    flex: 1;
    min-width: 0;
    border: 1px solid var(--color-border, rgba(0,0,0,.12));
    border-radius: var(--radius-lg, 12px);
    padding: var(--space-2) var(--space-3);
    font-size: var(--font-size-sm, .875rem);
    background: var(--color-surface, #fff);
    color: var(--color-text, #1a1a1a);
    font-family: monospace;
}

.calsync-input-row input[type="text"]:focus {
    outline: 2px solid var(--color-primary, #478cf4);
    outline-offset: -1px;
}

/* Buttons */
.calsync-btn {
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
    line-height: 1.4;
}

.calsync-btn-primary {
    background: var(--color-primary, #478cf4);
    color: #fff;
}
.calsync-btn-primary:hover { background: #3a7bd5; color: #fff; }

.calsync-btn-secondary {
    background: var(--color-gray-100, #f3f4f6);
    color: var(--color-gray-700, #374151);
}
.calsync-btn-secondary:hover { background: var(--color-gray-200, #e5e7eb); }

.calsync-btn-ghost {
    background: transparent;
    color: var(--color-gray-500, #6b7280);
    border: 1px solid var(--color-border, rgba(0,0,0,.12));
}
.calsync-btn-ghost:hover { background: var(--color-gray-50, #f9fafb); }

.calsync-btn-danger {
    background: transparent;
    color: var(--color-error, #ef4444);
    border: 1px solid rgba(239,68,68,.25);
    font-size: var(--font-size-xs, .75rem);
    padding: var(--space-1) var(--space-3);
}
.calsync-btn-danger:hover { background: rgba(239,68,68,.06); }

.calsync-copied {
    font-size: var(--font-size-xs, .75rem);
    color: #059669;
    opacity: 0;
    transition: opacity .2s;
    display: block;
    height: 1em;
    margin-top: 2px;
}
.calsync-copied.show { opacity: 1; }

.calsync-note {
    font-size: var(--font-size-xs, .75rem);
    color: var(--color-gray-400, #9ca3af);
    line-height: 1.4;
}

.calsync-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: var(--space-2);
    padding-top: var(--space-3);
    border-top: 1px solid var(--color-border, rgba(0,0,0,.07));
}

.calsync-divider {
    border: none;
    border-top: 1px solid var(--color-border, rgba(0,0,0,.07));
    margin: 0;
}

.calsync-credential-group {
    display: flex;
    flex-direction: column;
    gap: var(--space-3);
}

.calsync-compat-hints {
    display: flex;
    flex-direction: column;
    gap: var(--space-1);
    margin-top: var(--space-2);
    font-size: var(--font-size-xs, .75rem);
    color: var(--color-gray-500, #6b7280);
}

.calsync-compat-hint {
    display: flex;
    align-items: center;
    gap: 5px;
}

.calsync-compat-hint a {
    color: var(--color-primary, #478cf4);
    text-decoration: none;
    font-weight: 500;
}

.calsync-compat-hint a:hover { text-decoration: underline; }

.calsync-compat-note {
    color: var(--color-gray-400, #9ca3af);
}
</style>

<div class="calsync-section">

    <div class="calsync-options">
        <!-- Option 1: iCal subscribe -->
        <button type="button" class="calsync-option" id="calsync-opt-subscribe" onclick="calsyncSelectOption('subscribe')">
            <div class="calsync-option-top">
                <div class="calsync-option-icon-wrap subscribe">
                    <i class="fa-solid fa-rss"></i>
                </div>
                <div style="flex:1;min-width:0;">
                    <div class="calsync-option-label">Nur Termine sehen</div>
                    <div class="calsync-option-desc">Proben <strong>dieses Orchesters</strong> im Kalender anzeigen &mdash; Zu-/Absagen nur in der App</div>
                </div>
            </div>
            <div class="calsync-platforms">
                <span class="calsync-platform-pill">
                    <img src="/assets/icons/brands/apple.svg" style="width:14px;height:14px;object-fit:contain;" alt="Apple">
                    Apple
                </span>
                <span class="calsync-platform-pill">
                    <img src="/assets/icons/brands/google-calendar.svg" style="width:14px;height:14px;object-fit:contain;" alt="Google">
                    Google
                </span>
                <span class="calsync-platform-pill">
                    <img src="/assets/icons/brands/thunderbird.svg" style="width:14px;height:14px;object-fit:contain;" alt="Thunderbird">
                    Thunderbird
                </span>
                <span class="calsync-platform-pill">
                    <img src="/assets/icons/brands/outlook.svg" style="width:14px;height:14px;object-fit:contain;" alt="Outlook">
                    Outlook
                </span>
                <span class="calsync-platform-pill">
                    <img src="/assets/icons/brands/android.svg" style="width:14px;height:14px;object-fit:contain;" alt="Android">
                    Android
                </span>
                <span class="calsync-platform-pill" style="color:var(--color-gray-400);">&amp; mehr</span>
            </div>
        </button>

        <!-- Option 2: CalDAV -->
        <button type="button" class="calsync-option" id="calsync-opt-caldav" onclick="calsyncSelectOption('caldav')">
            <div class="calsync-option-top">
                <div class="calsync-option-icon-wrap caldav">
                    <i class="fa-solid fa-arrows-rotate"></i>
                </div>
                <div style="flex:1;min-width:0;">
                    <div class="calsync-option-header">
                        <span class="calsync-option-label">Vollständig einbinden</span>
                    </div>
                    <div class="calsync-option-desc"><strong>Alle deine Orchester</strong> &mdash; Termine sehen <em>und</em> Zu-/Absagen direkt im Kalender</div>
                </div>
            </div>
            <div class="calsync-platforms">
                <span class="calsync-platform-pill">
                    <img src="/assets/icons/brands/apple.svg" style="width:14px;height:14px;object-fit:contain;" alt="Apple">
                    Apple
                </span>
                <span class="calsync-platform-pill">
                    <img src="/assets/icons/brands/thunderbird.svg" style="width:14px;height:14px;object-fit:contain;" alt="Thunderbird">
                    Thunderbird
                </span>
                <span class="calsync-platform-pill" style="opacity:.5;">
                    <img src="/assets/icons/brands/android.svg" style="width:14px;height:14px;object-fit:contain;" alt="Android">
                    Android <span style="font-size:10px;">(App nötig)</span>
                </span>
                <span class="calsync-platform-pill" style="opacity:.5;">
                    <img src="/assets/icons/brands/outlook.svg" style="width:14px;height:14px;object-fit:contain;" alt="Outlook">
                    Outlook <span style="font-size:10px;">(Plugin)</span>
                </span>
            </div>
        </button>

        <!-- Subscribe panel — inside grid so mobile order works -->
        <div class="calsync-panel" id="calsync-panel-subscribe">
            <div class="calsync-panel-title">
                <i class="fa-solid fa-rss" style="color:var(--color-primary,#478cf4)"></i>
                Kalender-Link
            </div>

            <div id="calsync-sub-placeholder" class="calsync-generate-row">
                <span class="calsync-generate-hint">Erstelle deinen persönlichen Kalender-Link.</span>
                <button type="button" class="calsync-btn calsync-btn-primary" onclick="calsyncGenerate()">
                    <i class="fa-solid fa-wand-magic-sparkles"></i> Erstellen
                </button>
            </div>

            <div id="calsync-sub-loaded" style="display:none;flex-direction:column;gap:var(--space-3);">
                <div>
                    <div class="calsync-input-row">
                        <input type="text" id="calsync-ical-url" readonly placeholder="Wird geladen…">
                        <button type="button" class="calsync-btn calsync-btn-secondary"
                                onclick="calsyncCopy('calsync-ical-url','calsync-ical-copied')" title="Kopieren">
                            <i class="fa-solid fa-copy"></i>
                        </button>
                        <a id="calsync-webcal-link" class="calsync-btn calsync-btn-primary" href="#" title="In Kalender-App öffnen">
                            <i class="fa-solid fa-arrow-up-right-from-square"></i> Importieren
                        </a>
                    </div>
                    <span id="calsync-ical-copied" class="calsync-copied">✓ Kopiert!</span>
                </div>
                <div class="calsync-footer">
                    <span class="calsync-note"><i class="fa-solid fa-lock" style="margin-right:4px;"></i>Dein persönlicher Link — gib ihn nicht weiter.</span>
                    <button type="button" class="calsync-btn calsync-btn-danger" onclick="calsyncRevoke()">
                        <i class="fa-solid fa-rotate"></i> Zurücksetzen
                    </button>
                </div>
            </div>
        </div>

        <!-- CalDAV panel — inside grid so mobile order works -->
        <div class="calsync-panel" id="calsync-panel-caldav">
            <div class="calsync-panel-title">
                <i class="fa-solid fa-arrows-rotate" style="color:var(--color-primary,#478cf4)"></i>
                Probenplaner-Verknüpfung (CalDAV)
            </div>

            <div class="calsync-compat-hints">
                <span class="calsync-compat-hint">
                    <img src="/assets/icons/brands/android.svg" style="width:12px;height:12px;object-fit:contain;" alt="Android">
                    Android: <a href="https://www.davx5.com/" target="_blank" rel="noopener">DAVx⁵</a>
                    <span class="calsync-compat-note">(kostenlos via <a href="https://f-droid.org/packages/at.bitfire.davdroid/" target="_blank" rel="noopener">F-Droid</a>)</span>
                </span>
                <span class="calsync-compat-hint">
                    <img src="/assets/icons/brands/outlook.svg" style="width:12px;height:12px;object-fit:contain;" alt="Outlook">
                    Outlook: <a href="https://caldavsynchronizer.org/" target="_blank" rel="noopener">CalDav Synchronizer</a>
                    <span class="calsync-compat-note">(kostenloses Plugin)</span>
                </span>
            </div>

            <div id="calsync-caldav-placeholder" class="calsync-generate-row">
                <span class="calsync-generate-hint">Erstelle deine Zugangsdaten für den Kalender-Login.</span>
                <button type="button" class="calsync-btn calsync-btn-primary" onclick="calsyncGenerate()">
                    <i class="fa-solid fa-wand-magic-sparkles"></i> Erstellen
                </button>
            </div>

            <div id="calsync-caldav-loaded" style="display:none;flex-direction:column;gap:var(--space-3);">
                <div class="calsync-credential-group">
                    <div>
                        <div class="calsync-field-label">Server-URL</div>
                        <div class="calsync-input-row">
                            <input type="text" id="calsync-caldav-url" readonly placeholder="Wird geladen…">
                            <button type="button" class="calsync-btn calsync-btn-secondary"
                                    onclick="calsyncCopy('calsync-caldav-url','calsync-caldav-url-copied')" title="Kopieren">
                                <i class="fa-solid fa-copy"></i>
                            </button>
                        </div>
                        <span id="calsync-caldav-url-copied" class="calsync-copied">✓ Kopiert!</span>
                    </div>
                    <div>
                        <div class="calsync-field-label">Benutzername</div>
                        <div class="calsync-input-row">
                            <input type="text" id="calsync-caldav-user" readonly placeholder="Wird geladen…">
                            <button type="button" class="calsync-btn calsync-btn-secondary"
                                    onclick="calsyncCopy('calsync-caldav-user','calsync-caldav-user-copied')" title="Kopieren">
                                <i class="fa-solid fa-copy"></i>
                            </button>
                        </div>
                        <span id="calsync-caldav-user-copied" class="calsync-copied">✓ Kopiert!</span>
                    </div>
                    <div>
                        <div class="calsync-field-label">Kalender-Passwort</div>
                        <div class="calsync-input-row">
                            <input type="text" id="calsync-caldav-token" readonly placeholder="Wird geladen…">
                            <button type="button" class="calsync-btn calsync-btn-secondary"
                                    onclick="calsyncCopy('calsync-caldav-token','calsync-caldav-token-copied')" title="Kopieren">
                                <i class="fa-solid fa-copy"></i>
                            </button>
                        </div>
                        <span id="calsync-caldav-token-copied" class="calsync-copied">✓ Kopiert!</span>
                    </div>
                </div>
                <p class="calsync-note">
                    ⚠️ Das Kalender-Passwort ist <strong>nicht</strong> dein normales Probenplaner-Passwort.
                </p>
                <div class="calsync-footer">
                    <span class="calsync-note"><i class="fa-solid fa-lock" style="margin-right:4px;"></i>Gib diese Zugangsdaten nicht weiter.</span>
                    <button type="button" class="calsync-btn calsync-btn-danger" onclick="calsyncRevoke()">
                        <i class="fa-solid fa-rotate"></i> Zurücksetzen
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>


<script>
(function () {
    const CSRF = '<?= htmlspecialchars($calendarCsrfToken) ?>';
    let activeOption = null;
    let tokensLoaded = false;

    window.calsyncSelectOption = function (type) {
        // Toggle off if already active
        if (activeOption === type) {
            activeOption = null;
            document.getElementById('calsync-opt-subscribe').classList.remove('active');
            document.getElementById('calsync-opt-caldav').classList.remove('active');
            document.getElementById('calsync-panel-subscribe').classList.remove('visible');
            document.getElementById('calsync-panel-caldav').classList.remove('visible');
            return;
        }

        activeOption = type;
        document.getElementById('calsync-opt-subscribe').classList.toggle('active', type === 'subscribe');
        document.getElementById('calsync-opt-caldav').classList.toggle('active', type === 'caldav');
        document.getElementById('calsync-panel-subscribe').classList.toggle('visible', type === 'subscribe');
        document.getElementById('calsync-panel-caldav').classList.toggle('visible', type === 'caldav');

        // Auto-load if tokens already exist
        if (!tokensLoaded) {
            fetch('/calendar/tokens/status', { headers: { 'Accept': 'application/json' } })
                .then(r => r.json())
                .then(data => { if (data.has_tokens) calsyncGenerate(false); })
                .catch(() => {});
        }
    };

    window.calsyncGenerate = function (showLoading = true) {
        if (showLoading) {
            ['calsync-sub-placeholder', 'calsync-caldav-placeholder'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.innerHTML = '<span class="calsync-generate-hint"><i class="fa-solid fa-spinner fa-spin" style="margin-right:8px;color:var(--color-primary)"></i>Erstelle…</span>';
            });
        }

        fetch('/calendar/tokens/generate', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json' },
            body: 'csrf_token=' + encodeURIComponent(CSRF),
        })
            .then(r => r.json())
            .then(data => {
                if (!data.success) throw new Error(data.error || 'Unbekannter Fehler');
                fillCalsync(data);
            })
            .catch(err => {
                if (typeof window.notifyError === 'function') window.notifyError('Kalenderlink konnte nicht erstellt werden: ' + err.message);
            });
    };

    function fillCalsync(data) {
        tokensLoaded = true;

        let icalUrl = data.ical_url;
        let webcalUrl = data.webcal_url;

        // Localhost workaround for Apple Calendar
        if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
            icalUrl = icalUrl.replace('localhost', '127.0.0.1');
            webcalUrl = webcalUrl.replace('localhost', '127.0.0.1');
        }

        const caldavUrl = data.caldav_url.replace('localhost', '127.0.0.1');

        document.getElementById('calsync-ical-url').value    = icalUrl;
        document.getElementById('calsync-webcal-link').href  = webcalUrl;
        document.getElementById('calsync-caldav-url').value  = caldavUrl;
        document.getElementById('calsync-caldav-user').value = data.caldav_user;
        document.getElementById('calsync-caldav-token').value= data.caldav_token;

        // Show loaded state, hide placeholder in both panels
        document.getElementById('calsync-sub-placeholder').style.display    = 'none';
        document.getElementById('calsync-sub-loaded').style.display         = 'flex';
        document.getElementById('calsync-caldav-placeholder').style.display = 'none';
        document.getElementById('calsync-caldav-loaded').style.display      = 'flex';
    }

    window.calsyncRevoke = function () {
        if (!confirm('Bist du sicher? Dein bisheriger Link wird ungültig und alle Kalender-Apps müssen neu verbunden werden.')) return;

        fetch('/calendar/tokens/revoke', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json' },
            body: 'csrf_token=' + encodeURIComponent(CSRF),
        })
            .then(r => r.json())
            .then(() => {
                tokensLoaded = false;
                const resetHtml = '<span class="calsync-generate-hint" style="color:var(--color-error)">Link zurückgesetzt.</span>' +
                    '<button type="button" class="calsync-btn calsync-btn-primary" onclick="calsyncGenerate()">' +
                    '<i class="fa-solid fa-wand-magic-sparkles"></i> Neu erstellen</button>';

                document.getElementById('calsync-sub-loaded').style.display         = 'none';
                document.getElementById('calsync-sub-placeholder').innerHTML        = resetHtml;
                document.getElementById('calsync-sub-placeholder').style.display    = '';
                document.getElementById('calsync-caldav-loaded').style.display      = 'none';
                document.getElementById('calsync-caldav-placeholder').innerHTML     = resetHtml;
                document.getElementById('calsync-caldav-placeholder').style.display = '';

                if (typeof window.notifySuccess === 'function') window.notifySuccess('Kalenderlink zurückgesetzt');
            })
            .catch(() => {
                if (typeof window.notifyError === 'function') window.notifyError('Fehler beim Zurücksetzen');
            });
    };

    window.calsyncCopy = function (inputId, feedbackId) {
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
