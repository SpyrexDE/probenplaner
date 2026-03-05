<?php $this->layout('layouts/default', ['title' => 'Organisation bearbeiten', 'currentPage' => 'admin_panel', 'isFluid' => true]) ?>

<?php
$renderComponent = false;
include __DIR__ . '/../components/form-input.php';
$renderComponent = true;
?>

<?php ob_start(); ?>

<h1 class="text-heading" style="font-size: var(--font-size-xl); margin-bottom: var(--space-5);">
    <?= htmlspecialchars($org['name']) ?>
</h1>

<!-- Settings -->
<?php ob_start(); ?>
<div class="form-group-modern">
    <label class="form-label-modern">Name</label>
    <input type="text" value="<?= htmlspecialchars($org['name']) ?>"
        data-entity="organization" data-entity-id="<?= (int)$org['id'] ?>" data-field="name"
        class="autosave-field form-input-modern">
</div>
<div class="form-group-modern">
    <label class="form-label-modern">Slug</label>
    <input type="text" value="<?= htmlspecialchars($org['slug']) ?>"
        data-entity="organization" data-entity-id="<?= (int)$org['id'] ?>" data-field="slug"
        class="autosave-field form-input-modern"
        pattern="[a-z0-9-]+" title="Nur Kleinbuchstaben, Zahlen und Bindestriche">
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
    <form method="POST" action="/admin/orgs/<?= htmlspecialchars($org['slug']) ?>/delete"
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

<script>
    document.querySelectorAll('.autosave-field').forEach(function(input) {
        var timeout;
        input.addEventListener('input', function() {
            clearTimeout(timeout);
            timeout = setTimeout(function() {
                var entityId = input.dataset.entityId;
                var field = input.dataset.field;
                var value = input.value;
                var csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

                fetch('/admin/orgs/' + '<?= htmlspecialchars($org['slug']) ?>' + '/update', {
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