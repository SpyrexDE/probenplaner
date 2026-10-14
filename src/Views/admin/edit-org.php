<?php $this->layout('layouts/default', ['title' => 'Organisation bearbeiten', 'currentPage' => 'admin_panel', 'isFluid' => true]) ?>

<?php
$renderComponent = false;
include __DIR__ . '/../components/form-input.php';
$renderComponent = true;
// Raw slug for inline JS (json_encode with HEX flags is safe inside a script block)
$orgSlugJs = json_encode((string)($org['slug'] ?? ''), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
?>

<?php ob_start(); ?>

<h1 class="text-heading" style="font-size: var(--font-size-xl); margin-bottom: var(--space-5);">
    <?= htmlspecialchars($org['name']) ?>
</h1>

<!-- Settings -->
<?php ob_start(); ?>
<div class="form-group-modern">
    <label class="form-label-modern" for="orgNameInput">Name</label>
    <input type="text" id="orgNameInput" value="<?= htmlspecialchars($org['name']) ?>"
        data-entity="organization" data-entity-id="<?= (int)$org['id'] ?>" data-field="name"
        class="autosave-field form-input-modern">
</div>
<div class="form-group-modern">
    <label class="form-label-modern" for="orgSlugInput">Slug</label>
    <input type="text" id="orgSlugInput" value="<?= htmlspecialchars($org['slug']) ?>"
        data-entity="organization" data-entity-id="<?= (int)$org['id'] ?>" data-field="slug"
        class="autosave-field form-input-modern"
        pattern="[a-z0-9_\-]+" maxlength="58" required
        autocomplete="off" autocapitalize="none" spellcheck="false"
        title="Nur Kleinbuchstaben, Zahlen, Binde- und Unterstriche"
        aria-describedby="orgSlugError">
    <div class="slug-field-error" id="orgSlugError" aria-live="polite"></div>
</div>
<?php
$sectionContent = ob_get_clean();
$sectionTitle = 'Allgemein (auto-save)';
$sectionIcon = 'fa-cog';
$sectionVariant = 'default';
include __DIR__ . '/../components/panel-section.php';
?>

<!-- Danger zone -->
<?php ob_start(); ?>
<div class="danger-zone-content">
    <div class="danger-zone-info">
        <div class="text-heading mb-2">Organisation dauerhaft löschen</div>
        <div class="text-muted mb-4" style="font-size: var(--font-size-sm);">Alle Ensembles, Mitglieder und Daten werden unwiderruflich gelöscht.</div>
    </div>
    <form method="POST" action="/admin/orgs/<?= htmlspecialchars($org['slug']) ?>/delete" id="deleteOrgForm"
        onsubmit="return confirm('Organisation wirklich löschen? Alle Ensembles und Nutzer werden gelöscht.')">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
        <button type="submit" class="btn-modern btn-danger">
            <?= icon('trash', 'btn-icon') ?> Organisation löschen
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
$panelTitle = 'Admin Panel';
$panelBadge = 'Admin';
$panelVariant = 'admin';
$panelMaxWidth = '600px';
$panelBackUrl = '/admin/dashboard';
$panelLogoutUrl = '/admin/logout';
include __DIR__ . '/../components/panel-shell.php';
?>

<style>
    .slug-field-error {
        font-size: var(--font-size-xs);
        color: var(--color-error);
        margin-top: var(--space-1);
    }

    /* Stays rendered while empty (no display:none) so screen readers announce the aria-live text */
    .slug-field-error:empty {
        margin-top: 0;
    }
</style>

<script>
    (function() {
        var SLUG_PATTERN = /^[a-z0-9_-]+$/;
        // The org account's login e-mail is {slug}-admin@probenplaner.local; its local part
        // may have at most 64 characters, so the slug is limited to 64 - 6 = 58.
        var SLUG_MAX_LENGTH = 58;
        var SLUG_PATH_PREFIX = '/admin/orgs/';
        // Current org slug. It changes when the "Slug" field is saved, so every request
        // builds its URL from this variable, never from the page-load slug.
        var currentSlug = <?= $orgSlugJs ?>;
        // Every request to /admin/orgs/{slug}/... runs through this queue, one after
        // another, so none of them goes out under a slug that is being renamed.
        var requestQueue = Promise.resolve();

        function orgUrl(suffix) {
            return SLUG_PATH_PREFIX + encodeURIComponent(currentSlug) + suffix;
        }

        /**
         * Run task() once all earlier queued requests (including a pending slug rename)
         * have finished. task must build its URL with orgUrl() when it runs.
         */
        function whenIdle(task) {
            var run = requestQueue.then(task);
            requestQueue = run.catch(function() {});
            return run;
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

        /**
         * POST one field to /admin/orgs/{currentSlug}/update.
         * Resolves with the JSON response, rejects with an Error carrying the server message.
         * A successful slug save switches currentSlug before the next queued request runs.
         */
        function saveField(field, value) {
            return whenIdle(function() {
                return fetch(orgUrl('/update'), {
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

        /** Rewrite /admin/orgs/{old}/... in the address bar so reloads keep working. */
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
            var deleteForm = document.getElementById('deleteOrgForm');
            if (deleteForm) deleteForm.setAttribute('action', orgUrl('/delete'));
            replaceSlugInLocation(oldSlug, newSlug);
        }

        function validateSlug(value) {
            if (value === '') return 'Bitte einen Slug angeben.';
            if (value.length > SLUG_MAX_LENGTH) return 'Slug darf höchstens ' + SLUG_MAX_LENGTH + ' Zeichen lang sein.';
            if (!SLUG_PATTERN.test(value)) return 'Nur Kleinbuchstaben, Zahlen, Binde- und Unterstriche erlaubt.';
            return '';
        }

        // Name: auto-save shortly after typing stops
        var nameInput = document.getElementById('orgNameInput');
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

        // Slug: validate while typing, save only on change (blur / Enter) when valid.
        var slugInput = document.getElementById('orgSlugInput');
        var slugError = document.getElementById('orgSlugError');
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
                    // when no newer slug was requested meanwhile.
                    if (requestedSlug === value) {
                        requestedSlug = currentSlug;
                        if (document.activeElement !== slugInput) slugInput.value = currentSlug;
                    }
                    showSuccess('Gespeichert');
                }).catch(function(err) {
                    var message = err.message || 'Speichern fehlgeschlagen';
                    if (requestedSlug === value) {
                        requestedSlug = currentSlug;
                        setSlugError(message);
                    }
                    showError('Speichern fehlgeschlagen: ' + message);
                });
            });
        }

        // Delete: the inline onsubmit confirm() runs first. Afterwards wait for queued requests
        // (e.g. a slug rename started by leaving the field) and post to the current slug.
        var deleteForm = document.getElementById('deleteOrgForm');
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
                    deleteForm.setAttribute('action', orgUrl('/delete'));
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
