<?php $this->layout('layouts/default', ['title' => 'Ensemble bearbeiten', 'currentPage' => 'orga_panel', 'isFluid' => true]) ?>

<?php
$renderComponent = false;
include __DIR__ . '/../components/form-input.php';
$renderComponent = true;
$orgSlug = htmlspecialchars($org['slug'] ?? '');
$ensembleId = (int)$ensemble['id'];
$ensembleSlug = htmlspecialchars($ensemble['slug'] ?? '');
// Raw values for inline JS (json_encode with HEX flags is safe inside a script block)
$jsJsonFlags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
$orgSlugJs = json_encode((string)($org['slug'] ?? ''), $jsJsonFlags);
$ensembleSlugJs = json_encode((string)($ensemble['slug'] ?? ''), $jsJsonFlags);
?>

<?php ob_start(); ?>

<h1 class="text-heading" style="font-size: var(--font-size-xl); margin-bottom: var(--space-5);">
    <?= htmlspecialchars($ensemble['name']) ?> bearbeiten
</h1>

<!-- Settings (auto-save) -->
<?php ob_start(); ?>
<div class="form-group-modern">
    <label class="form-label-modern" for="ensembleNameInput">Name</label>
    <input type="text" id="ensembleNameInput" value="<?= htmlspecialchars($ensemble['name']) ?>"
        data-entity="orchestra" data-entity-id="<?= $ensembleId ?>" data-field="name"
        class="autosave-field form-input-modern">
</div>
<div class="form-group-modern">
    <label class="form-label-modern" for="ensembleSlugInput">Kürzel</label>
    <input type="text" id="ensembleSlugInput" value="<?= htmlspecialchars($ensemble['slug'] ?? '') ?>"
        data-entity="orchestra" data-entity-id="<?= $ensembleId ?>" data-field="slug"
        class="autosave-field form-input-modern" pattern="[a-z0-9_\-]+" maxlength="100" required
        autocomplete="off" autocapitalize="none" spellcheck="false"
        title="Nur Kleinbuchstaben, Zahlen, Binde- und Unterstriche"
        aria-describedby="slugPreview slugError">
    <div class="text-subtle" id="slugPreview" style="font-size: var(--font-size-xs); margin-top: var(--space-1);">
        → /<?= $orgSlug ?>/<?= htmlspecialchars($ensemble['slug'] ?? '') ?>
    </div>
    <div class="slug-field-error" id="slugError" aria-live="polite"></div>
</div>
<?php
$sectionContent = ob_get_clean();
$sectionTitle = 'Allgemein';
$sectionIcon = 'fa-cog';
$sectionVariant = 'default';
include __DIR__ . '/../components/panel-section.php';
?>

<!-- Leitung -->
<?php ob_start(); ?>
<div class="conductor-list" id="conductorList">
    <?php if (!empty($conductors)): ?>
        <?php foreach ($conductors as $conductor): ?>
            <div class="conductor-row" data-user-id="<?= (int)$conductor['id'] ?>">
                <div class="flex-start gap-2">
                    <span class="conductor-avatar"><?= mb_strtoupper(mb_substr($conductor['display_name'] ?? $conductor['email'], 0, 1)) ?></span>
                    <span><?= htmlspecialchars($conductor['display_name'] ?? $conductor['email']) ?></span>
                </div>
                <button type="button" class="btn-base btn-xs btn-ghost text-danger" onclick="removeConductor(<?= (int)$conductor['id'] ?>, this)">
                    Entfernen
                </button>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="text-subtle" style="font-size: var(--font-size-sm);" id="noConductorsMsg">Noch keine Leitung zugewiesen.</div>
    <?php endif; ?>
</div>

<div style="margin-top: var(--space-3);">
    <?php if ($conductorLink): ?>
        <div class="invite-link-bar invite-link-bar--conductor">
            <div class="invite-link-token" style="color: var(--color-primary);">
                <i class="fas fa-link"></i>
                <code class="invite-link-url"><?= htmlspecialchars(rtrim($_SERVER['HTTP_HOST'] ?? 'probenplaner', '/')) ?>/invite/<?= htmlspecialchars($conductorLink['token']) ?></code>
            </div>
            <div class="flex-end gap-1">
                <button type="button" class="btn-base btn-xs btn-success" onclick="copyInviteLink(this, '<?= htmlspecialchars($conductorLink['token']) ?>')">
                    <i class="fas fa-copy"></i> Kopieren
                </button>
                <button type="button" class="btn-base btn-xs btn-ghost" onclick="regenerateLink('conductor', this)" title="Neu generieren">
                    <i class="fas fa-sync-alt"></i>
                </button>
            </div>
        </div>
        <?php if (!empty($conductorLink['expires_at'])): ?>
            <div class="link-expiry" style="margin-top: var(--space-1);">
                <i class="fas fa-clock"></i> Gültig bis <?= date('d.m.Y', strtotime($conductorLink['expires_at'])) ?>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <button type="button" class="btn-base btn-sm btn-ghost" onclick="generateLink('conductor', this)">
            <i class="fas fa-link"></i> Leitungs-Link generieren
        </button>
    <?php endif; ?>
</div>
<?php
$sectionContent = ob_get_clean();
$sectionTitle = 'Leitung';
$sectionIcon = 'fa-user-tie';
$sectionVariant = 'default';
include __DIR__ . '/../components/panel-section.php';
?>

<!-- Einladungslink (Mitglieder) -->
<?php ob_start(); ?>
<?php if ($memberLink): ?>
    <div class="invite-link-bar">
        <div class="invite-link-token">
            <i class="fas fa-link"></i>
            <code class="invite-link-url"><?= htmlspecialchars(rtrim($_SERVER['HTTP_HOST'] ?? 'probenplaner', '/')) ?>/invite/<?= htmlspecialchars($memberLink['token']) ?></code>
        </div>
        <div class="flex-end gap-1">
            <button type="button" class="btn-base btn-xs btn-success" onclick="copyInviteLink(this, '<?= htmlspecialchars($memberLink['token']) ?>')">
                <i class="fas fa-copy"></i> Kopieren
            </button>
            <button type="button" class="btn-base btn-xs btn-ghost" onclick="regenerateLink('member', this)" title="Neu generieren">
                <i class="fas fa-sync-alt"></i>
            </button>
        </div>
    </div>
<?php else: ?>
    <button type="button" class="btn-base btn-sm btn-ghost" onclick="generateLink('member', this)">
        <i class="fas fa-plus"></i> Mitglieder-Link generieren
    </button>
<?php endif; ?>
<?php
$sectionContent = ob_get_clean();
$sectionTitle = 'Einladungslink';
$sectionIcon = 'fa-link';
$sectionVariant = 'default';
include __DIR__ . '/../components/panel-section.php';
?>

<!-- Statistiken -->
<?php ob_start(); ?>
<div class="ensemble-stats-grid">
    <div class="stat-item">
        <div class="stat-value"><?= (int)$memberCount ?></div>
        <div class="stat-label">Mitglieder</div>
    </div>
</div>
<?php
$sectionContent = ob_get_clean();
$sectionTitle = 'Statistiken';
$sectionIcon = 'fa-chart-bar';
$sectionVariant = 'default';
include __DIR__ . '/../components/panel-section.php';
?>

<!-- Danger zone -->
<?php ob_start(); ?>
<div class="danger-zone-content">
    <div class="danger-zone-info">
        <div class="text-heading mb-2">Ensemble dauerhaft löschen</div>
        <div class="text-muted mb-4" style="font-size: var(--font-size-sm);">Alle Mitglieder und Proben werden unwiderruflich gelöscht.</div>
    </div>
    <form method="POST" action="/orga/ensembles/<?= $ensembleSlug ?>/delete" id="deleteEnsembleForm"
        onsubmit="return confirm('Ensemble wirklich löschen?')">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
        <button type="submit" class="btn-modern btn-danger">
            <?= icon('trash', 'btn-icon') ?> Ensemble löschen
        </button>
    </form>
</div>
<?php
$sectionContent = ob_get_clean();
$sectionTitle = 'Gefährliche Aktionen';
$sectionIcon = 'fa-exclamation-triangle';
$sectionVariant = 'danger';
include __DIR__ . '/../components/panel-section.php';
?>

<?php
$panelContent = ob_get_clean();
$panelTitle = $org['name'] ?? 'Organisation'; // panel-shell escapes it
$panelBadge = 'Orga';
$panelVariant = 'orga';
$panelMaxWidth = '600px';
$panelBackUrl = '/orga/dashboard';
include __DIR__ . '/../components/panel-shell.php';
?>

<style>
    .conductor-list {
        display: flex;
        flex-direction: column;
        gap: var(--space-2);
    }

    .conductor-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: var(--space-2);
        border-radius: var(--radius-md);
        background: var(--color-bg-secondary);
        font-size: var(--font-size-sm);
    }

    .conductor-avatar {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        background: var(--color-primary);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: var(--font-size-xs);
        font-weight: 600;
    }

    .text-danger {
        color: var(--color-danger) !important;
    }

    .slug-field-error {
        font-size: var(--font-size-xs);
        color: var(--color-error);
        margin-top: var(--space-1);
    }

    /* Stays rendered while empty (no display:none) so screen readers announce the aria-live text */
    .slug-field-error:empty {
        margin-top: 0;
    }

    .invite-link-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--space-2);
        padding: var(--space-2) var(--space-3);
        border-radius: var(--radius-md);
        background: var(--color-success-50, rgba(16, 185, 129, 0.05));
        border: 1px solid var(--color-success-200, rgba(16, 185, 129, 0.15));
    }

    .invite-link-bar--conductor {
        background: rgba(99, 102, 241, 0.05);
        border-color: rgba(99, 102, 241, 0.15);
    }

    .invite-link-token {
        display: flex;
        align-items: center;
        gap: var(--space-2);
        min-width: 0;
        color: var(--color-success);
        font-size: var(--font-size-sm);
    }

    .invite-link-url {
        font-size: var(--font-size-xs);
        color: var(--color-text-secondary);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .link-expiry {
        font-size: 10px;
        color: var(--color-text-subtle);
        margin-top: 2px;
    }

    .btn-success {
        background: var(--color-success);
        color: #fff;
        border: none;
    }

    .btn-success:hover {
        background: var(--color-success-dark);
    }

    .ensemble-stats-grid {
        display: flex;
        gap: var(--space-4);
    }

    .stat-item {
        text-align: center;
    }

    .stat-value {
        font-size: var(--font-size-xl);
        font-weight: 700;
        color: var(--color-text-primary);
    }

    .stat-label {
        font-size: var(--font-size-xs);
        color: var(--color-text-subtle);
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    @media (max-width: 600px) {
        .invite-link-bar {
            flex-direction: column;
            align-items: stretch;
            gap: var(--space-2);
        }

        .invite-link-bar .flex-end {
            justify-content: flex-start;
        }

        .conductor-row {
            flex-wrap: wrap;
            gap: var(--space-2);
        }
    }
</style>

<script>
    (function() {
        var SLUG_PATTERN = /^[a-z0-9_-]+$/;
        var SLUG_MAX_LENGTH = 100;
        var SLUG_PATH_PREFIX = '/orga/ensembles/';
        var orgSlug = <?= $orgSlugJs ?>;
        // Current ensemble slug. It changes when the "Kürzel" field is saved, so every
        // request builds its URL from this variable, never from the page-load slug.
        var currentSlug = <?= $ensembleSlugJs ?>;
        // Every request to /orga/ensembles/{slug}/... runs through this queue, one after
        // another, so none of them goes out under a slug that is being renamed.
        var requestQueue = Promise.resolve();

        function ensembleUrl(suffix) {
            return SLUG_PATH_PREFIX + encodeURIComponent(currentSlug) + suffix;
        }

        /**
         * Run task() once all earlier queued requests (including a pending Kürzel rename)
         * have finished. task must build its URL with ensembleUrl() when it runs.
         */
        function whenIdle(task) {
            var run = requestQueue.then(task);
            requestQueue = run.catch(function() {});
            return run;
        }

        function postForm(suffix, body) {
            return fetch(ensembleUrl(suffix), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: 'csrf_token=' + encodeURIComponent(csrfToken()) + '&' + body
            }).then(function(r) {
                return r.json();
            });
        }

        function csrfToken() {
            var meta = document.querySelector('meta[name="csrf-token"]');
            return meta ? meta.content : '';
        }

        function showError(message) {
            if (window.notifyError) window.notifyError(message);
        }

        function showSuccess(message) {
            if (window.notifySuccess) window.notifySuccess(message);
        }

        function networkMessage(err) {
            return 'Netzwerkfehler: ' + ((err && err.message) || 'Verbindung fehlgeschlagen');
        }

        window.copyInviteLink = function(btn, token) {
            var url = window.location.origin + '/invite/' + token;
            navigator.clipboard.writeText(url).then(function() {
                var orig = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-check"></i> Kopiert!';
                setTimeout(function() {
                    btn.innerHTML = orig;
                }, 1500);
            });
        };

        window.generateLink = function(type, btn) {
            var orig = btn.innerHTML;
            var reloading = false;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            whenIdle(function() {
                    return postForm('/generate-link', 'type=' + encodeURIComponent(type));
                })
                .then(function(data) {
                    if (data.success) {
                        reloading = true;
                        location.reload();
                    } else {
                        showError(data.error || 'Link konnte nicht generiert werden');
                    }
                })
                .catch(function(e) {
                    showError(networkMessage(e));
                })
                .finally(function() {
                    btn.disabled = false;
                    if (!reloading) btn.innerHTML = orig;
                });
        };

        window.regenerateLink = function(type, btn) {
            if (!confirm('Alten Link ungültig machen und neuen generieren?')) return;
            btn.disabled = true;
            whenIdle(function() {
                    return postForm('/regenerate-link', 'type=' + encodeURIComponent(type));
                })
                .then(function(data) {
                    if (data.success) location.reload();
                    else showError(data.error || 'Link konnte nicht neu generiert werden');
                })
                .catch(function(e) {
                    showError(networkMessage(e));
                })
                .finally(function() {
                    btn.disabled = false;
                });
        };

        window.removeConductor = function(userId, btn) {
            if (!confirm('Leitung wirklich entfernen?')) return;
            btn.disabled = true;
            whenIdle(function() {
                    return postForm('/remove-conductor', 'user_id=' + encodeURIComponent(userId));
                })
                .then(function(data) {
                    if (data.success) {
                        btn.closest('.conductor-row').remove();
                        if (!document.querySelector('.conductor-row')) {
                            document.getElementById('conductorList').innerHTML =
                                '<div class="text-subtle" style="font-size: var(--font-size-sm);">Noch keine Leitung zugewiesen.</div>';
                        }
                        showSuccess('Leitung entfernt');
                    } else {
                        showError(data.error || 'Leitung konnte nicht entfernt werden');
                    }
                })
                .catch(function(e) {
                    showError(networkMessage(e));
                })
                .finally(function() {
                    btn.disabled = false;
                });
        };

        /**
         * POST one field to /orga/ensembles/{currentSlug}/update.
         * Resolves with the JSON response, rejects with an Error carrying the server message.
         * A successful slug save switches currentSlug before the next queued request runs.
         */
        function saveField(field, value) {
            return whenIdle(function() {
                return fetch(ensembleUrl('/update'), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: 'csrf_token=' + encodeURIComponent(csrfToken()) + '&field=' + encodeURIComponent(field) + '&value=' + encodeURIComponent(value),
                }).then(function(r) {
                    return r.text().then(function(text) {
                        var data = null;
                        try {
                            data = JSON.parse(text);
                        } catch (e) {
                            data = null;
                        }
                        if (!r.ok || !data || !data.success) {
                            throw new Error((data && data.error) || ('Serverfehler (' + r.status + ')'));
                        }
                        if (field === 'slug') {
                            var oldSlug = currentSlug;
                            currentSlug = (typeof data.slug === 'string' && data.slug !== '') ? data.slug : value;
                            applySlugChange(oldSlug, currentSlug);
                        }
                        return data;
                    });
                });
            });
        }

        /** Rewrite /orga/ensembles/{old}/... in the address bar so reloads keep working. */
        function replaceSlugInLocation(oldSlug, newSlug) {
            var path = window.location.pathname;
            if (path.indexOf(SLUG_PATH_PREFIX) !== 0 || !window.history || !history.replaceState) return;
            var rest = path.slice(SLUG_PATH_PREFIX.length);
            var slash = rest.indexOf('/');
            var segment = slash === -1 ? rest : rest.slice(0, slash);
            var decoded = segment;
            try {
                decoded = decodeURIComponent(segment);
            } catch (e) {
                decoded = segment;
            }
            if (decoded !== oldSlug) return;
            var newPath = SLUG_PATH_PREFIX + encodeURIComponent(newSlug) + (slash === -1 ? '' : rest.slice(slash));
            history.replaceState(history.state, '', newPath + window.location.search + window.location.hash);
        }

        function applySlugChange(oldSlug, newSlug) {
            var preview = document.getElementById('slugPreview');
            if (preview) preview.textContent = '→ /' + orgSlug + '/' + newSlug;
            var deleteForm = document.getElementById('deleteEnsembleForm');
            if (deleteForm) deleteForm.setAttribute('action', ensembleUrl('/delete'));
            replaceSlugInLocation(oldSlug, newSlug);
        }

        function validateSlug(value) {
            if (value === '') return 'Bitte ein Kürzel angeben.';
            if (value.length > SLUG_MAX_LENGTH) return 'Kürzel darf höchstens ' + SLUG_MAX_LENGTH + ' Zeichen lang sein.';
            if (!SLUG_PATTERN.test(value)) return 'Nur Kleinbuchstaben, Zahlen, Binde- und Unterstriche erlaubt.';
            return '';
        }

        // Name: auto-save shortly after typing stops
        var nameInput = document.getElementById('ensembleNameInput');
        if (nameInput) {
            var nameTimeout;
            nameInput.addEventListener('input', function() {
                clearTimeout(nameTimeout);
                nameTimeout = setTimeout(function() {
                    saveField('name', nameInput.value).then(function() {
                        showSuccess('Gespeichert');
                    }).catch(function(err) {
                        showError('Speichern fehlgeschlagen: ' + (err.message || 'Verbindung fehlgeschlagen'));
                    });
                }, 500);
            });
        }

        // Kürzel: validate while typing, save only on change (blur / Enter) when valid.
        var slugInput = document.getElementById('ensembleSlugInput');
        var slugError = document.getElementById('slugError');
        if (slugInput) {
            var slugGroup = slugInput.closest('.form-group-modern');
            // Slug of the most recent queued save (equals currentSlug when nothing is pending)
            var requestedSlug = currentSlug;

            var setSlugError = function(message) {
                if (slugGroup) slugGroup.classList.toggle('error', !!message);
                if (message) slugInput.setAttribute('aria-invalid', 'true');
                else slugInput.removeAttribute('aria-invalid');
                if (slugError) slugError.textContent = message;
            };

            slugInput.addEventListener('input', function() {
                var value = slugInput.value.trim();
                setSlugError(value === '' ? '' : validateSlug(value));
            });

            slugInput.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    // Discard only what was typed since the last save request. While a rename is
                    // in flight that is the slug being saved, so the field never shows an old
                    // slug that the server is about to replace.
                    slugInput.value = requestedSlug;
                    setSlugError('');
                }
            });

            slugInput.addEventListener('change', function() {
                var value = slugInput.value.trim();
                if (value !== slugInput.value) slugInput.value = value;
                if (value === requestedSlug) {
                    setSlugError('');
                    return;
                }
                var message = validateSlug(value);
                setSlugError(message);
                if (message) return;

                requestedSlug = value;
                saveField('slug', value).then(function() {
                    // currentSlug was already switched inside saveField(). Only touch the field
                    // when no newer Kürzel was requested meanwhile.
                    if (requestedSlug === value) {
                        requestedSlug = currentSlug;
                        if (document.activeElement !== slugInput) slugInput.value = currentSlug;
                    }
                    showSuccess('Gespeichert');
                }).catch(function(err) {
                    if (requestedSlug === value) {
                        requestedSlug = currentSlug;
                        setSlugError(err.message || 'Speichern fehlgeschlagen');
                    }
                });
            });
        }

        // Delete: the inline onsubmit confirm() runs first. Afterwards wait for queued requests
        // (e.g. a Kürzel rename started by leaving the field) and post to the current slug.
        var deleteForm = document.getElementById('deleteEnsembleForm');
        if (deleteForm) {
            var deleteSubmitting = false;
            var deleteButton = deleteForm.querySelector('[type="submit"]');
            deleteForm.addEventListener('submit', function(e) {
                if (e.defaultPrevented) return; // cancelled in the confirm dialog
                e.preventDefault();
                if (deleteSubmitting) return;
                deleteSubmitting = true;
                if (deleteButton) deleteButton.disabled = true;
                whenIdle(function() {
                    deleteForm.setAttribute('action', ensembleUrl('/delete'));
                    deleteForm.submit(); // form.submit() does not fire 'submit' again
                }).catch(function() {
                    deleteSubmitting = false;
                    if (deleteButton) deleteButton.disabled = false;
                });
            });
            // Page restored from the back/forward cache: make the button usable again
            window.addEventListener('pageshow', function(e) {
                if (!e.persisted) return;
                deleteSubmitting = false;
                if (deleteButton) deleteButton.disabled = false;
            });
        }
    })();
</script>
