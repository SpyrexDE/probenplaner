<?php $this->layout('layouts/default', ['title' => 'Ensemble bearbeiten', 'currentPage' => 'orga_panel', 'isFluid' => true]) ?>

<?php
$renderComponent = false;
include __DIR__ . '/../components/form-input.php';
$renderComponent = true;
$orgSlug = htmlspecialchars($org['slug'] ?? '');
$ensembleId = (int)$ensemble['id'];
$ensembleSlug = htmlspecialchars($ensemble['slug'] ?? '');
?>

<?php ob_start(); ?>

<h1 class="text-heading" style="font-size: var(--font-size-xl); margin-bottom: var(--space-5);">
    <?= htmlspecialchars($ensemble['name']) ?> bearbeiten
</h1>

<!-- Settings (auto-save) -->
<?php ob_start(); ?>
<div class="form-group-modern">
    <label class="form-label-modern">Name</label>
    <input type="text" value="<?= htmlspecialchars($ensemble['name']) ?>"
        data-entity="orchestra" data-entity-id="<?= $ensembleId ?>" data-field="name"
        class="autosave-field form-input-modern">
</div>
<div class="form-group-modern">
    <label class="form-label-modern">Kürzel</label>
    <input type="text" value="<?= htmlspecialchars($ensemble['slug'] ?? '') ?>"
        data-entity="orchestra" data-entity-id="<?= $ensembleId ?>" data-field="slug"
        class="autosave-field form-input-modern" pattern="[a-z0-9\-]+" title="Nur Kleinbuchstaben, Zahlen und Bindestriche">
    <div class="text-subtle" id="slugPreview" style="font-size: var(--font-size-xs); margin-top: var(--space-1);">
        → /<?= $orgSlug ?>/<?= htmlspecialchars($ensemble['slug'] ?? '') ?>
    </div>
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
                <button type="button" class="btn-base btn-xs btn-ghost text-danger" onclick="removeConductor('<?= $ensembleSlug ?>', <?= (int)$conductor['id'] ?>, this)">
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
                <button type="button" class="btn-base btn-xs btn-ghost" onclick="regenerateLink('<?= $ensembleSlug ?>', 'conductor', this)" title="Neu generieren">
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
        <button type="button" class="btn-base btn-sm btn-ghost" onclick="generateLink('<?= $ensembleSlug ?>', 'conductor', this)">
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
            <button type="button" class="btn-base btn-xs btn-ghost" onclick="regenerateLink('<?= $ensembleSlug ?>', 'member', this)" title="Neu generieren">
                <i class="fas fa-sync-alt"></i>
            </button>
        </div>
    </div>
<?php else: ?>
    <button type="button" class="btn-base btn-sm btn-ghost" onclick="generateLink('<?= $ensembleSlug ?>', 'member', this)">
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
    <form method="POST" action="/orga/ensembles/<?= $ensembleSlug ?>/delete"
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
$panelTitle = htmlspecialchars($org['name'] ?? 'Organisation');
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
</style>

<script>
    function copyInviteLink(btn, token) {
        var url = window.location.origin + '/invite/' + token;
        navigator.clipboard.writeText(url).then(function() {
            var orig = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-check"></i> Kopiert!';
            setTimeout(function() {
                btn.innerHTML = orig;
            }, 1500);
        });
    }

    function generateLink(ensembleId, type, btn) {
        var csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        fetch('/orga/ensembles/' + ensembleId + '/generate-link', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: 'csrf_token=' + encodeURIComponent(csrf) + '&type=' + encodeURIComponent(type)
            })
            .then(function(r) {
                return r.json();
            })
            .then(function(data) {
                if (data.success) location.reload();
                else if (window.notifyError) window.notifyError(data.error || 'Link konnte nicht generiert werden');
            })
            .catch(function(e) {
                if (window.notifyError) window.notifyError('Netzwerkfehler: ' + (e.message || 'Verbindung fehlgeschlagen'));
            })
            .finally(function() {
                btn.disabled = false;
            });
    }

    function regenerateLink(ensembleId, type, btn) {
        if (!confirm('Alten Link ungültig machen und neuen generieren?')) return;
        var csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
        btn.disabled = true;
        fetch('/orga/ensembles/' + ensembleId + '/regenerate-link', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: 'csrf_token=' + encodeURIComponent(csrf) + '&type=' + encodeURIComponent(type)
            })
            .then(function(r) {
                return r.json();
            })
            .then(function(data) {
                if (data.success) location.reload();
                else if (window.notifyError) window.notifyError(data.error || 'Link konnte nicht neu generiert werden');
            })
            .catch(function(e) {
                if (window.notifyError) window.notifyError('Netzwerkfehler: ' + (e.message || 'Verbindung fehlgeschlagen'));
            })
            .finally(function() {
                btn.disabled = false;
            });
    }

    function removeConductor(ensembleId, userId, btn) {
        if (!confirm('Leitung wirklich entfernen?')) return;
        var csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
        btn.disabled = true;
        fetch('/orga/ensembles/' + ensembleId + '/remove-conductor', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: 'csrf_token=' + encodeURIComponent(csrf) + '&user_id=' + userId
            })
            .then(function(r) {
                return r.json();
            })
            .then(function(data) {
                if (data.success) {
                    btn.closest('.conductor-row').remove();
                    if (!document.querySelector('.conductor-row')) {
                        document.getElementById('conductorList').innerHTML =
                            '<div class="text-subtle" style="font-size: var(--font-size-sm);">Noch keine Leitung zugewiesen.</div>';
                    }
                    if (window.notifySuccess) window.notifySuccess('Leitung entfernt');
                } else {
                    window.notifyError(data.error || 'Leitung konnte nicht entfernt werden');
                }
            })
            .catch(function(e) {
                if (window.notifyError) window.notifyError('Netzwerkfehler: ' + (e.message || 'Verbindung fehlgeschlagen'));
            })
            .finally(function() {
                btn.disabled = false;
            });
    }

    document.querySelectorAll('.autosave-field').forEach(function(input) {
        var timeout;
        input.addEventListener('input', function() {
            clearTimeout(timeout);
            timeout = setTimeout(function() {
                var entityId = input.dataset.entityId;
                var field = input.dataset.field;
                var value = input.value;
                var csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

                fetch('/orga/ensembles/' + '<?= $ensembleSlug ?>' + '/update', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: 'csrf_token=' + encodeURIComponent(csrfToken) + '&field=' + encodeURIComponent(field) + '&value=' + encodeURIComponent(value),
                    })
                    .then(function(r) {
                        if (!r.ok) return r.text().then(function(t) {
                            throw new Error(t);
                        });
                        return r.json();
                    })
                    .then(function(data) {
                        if (data.success) {
                            if (window.notifySuccess) window.notifySuccess('Gespeichert');
                            // Live-update slug preview
                            if (field === 'slug') {
                                var preview = document.getElementById('slugPreview');
                                if (preview) preview.textContent = '→ /<?= $orgSlug ?>/' + value;
                            }
                        } else if (data.error) {
                            window.notifyError(data.error || 'Speichern fehlgeschlagen');
                        }
                    })
                    .catch(function(err) {
                        window.notifyError('Speichern fehlgeschlagen: ' + (err.message || 'Verbindung fehlgeschlagen'));
                    });
            }, 500);
        });
    });
</script>