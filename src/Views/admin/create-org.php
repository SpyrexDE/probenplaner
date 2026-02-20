<?php $this->layout('layouts/default', ['title' => 'Organisation erstellen', 'currentPage' => 'admin_panel', 'isFluid' => true]) ?>

<?php
$renderComponent = false;
include __DIR__ . '/../components/form-input.php';
$renderComponent = true;
?>

<style>
    .admin-success-box {
        background: var(--color-success-100);
        border: 1px solid var(--color-success-200);
        border-radius: var(--radius-lg);
        padding: var(--space-5);
    }

    .admin-success-title {
        font-size: var(--font-size-lg);
        font-weight: var(--font-weight-semibold);
        color: var(--color-success-700);
        margin-bottom: var(--space-4);
    }

    .admin-credential-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--space-3);
        padding: var(--space-3);
        background: var(--color-white);
        border-radius: var(--radius-md);
        margin-bottom: var(--space-2);
        font-size: var(--font-size-sm);
    }

    .admin-credential-row code {
        font-family: monospace;
        font-weight: var(--font-weight-bold);
        color: var(--color-text-primary);
        font-size: var(--font-size-base);
    }

    .admin-warning {
        display: flex;
        align-items: center;
        gap: var(--space-2);
        margin-top: var(--space-4);
        padding: var(--space-3);
        background: var(--color-warning-100);
        border-radius: var(--radius-md);
        font-size: var(--font-size-sm);
        font-weight: var(--font-weight-medium);
        color: var(--color-warning-700);
    }
</style>

<?php ob_start(); ?>

<?php if (!empty($created)): ?>
    <?php ob_start(); ?>
    <div class="admin-success-box">
        <div class="admin-success-title">✅ Organisation „<?= htmlspecialchars($created['org_name']) ?>" erstellt</div>

        <div class="admin-credential-row">
            <span>Benutzer:</span>
            <code><?= htmlspecialchars($created['username']) ?></code>
        </div>
        <div class="admin-credential-row">
            <span>Passwort:</span>
            <code id="created-pw"><?= htmlspecialchars($created['password']) ?></code>
            <button type="button" class="btn-base btn-xs btn-ghost" onclick="copyCreatedPw()">📋</button>
        </div>

        <div class="admin-warning">
            <i class="fas fa-exclamation-triangle"></i>
            Passwort jetzt notieren! Es kann danach nicht mehr angezeigt werden.
        </div>
    </div>

    <div style="margin-top: var(--space-4);">
        <a href="/admin/dashboard" class="btn-modern btn-primary" style="width: 100%; justify-content: center;">
            Zum Dashboard
        </a>
    </div>
    <?php
    $sectionContent = ob_get_clean();
    $sectionTitle = 'Zugangsdaten';
    $sectionIcon = 'fa-check-circle';
    $sectionVariant = 'default';
    include __DIR__ . '/../components/panel-section.php';
    ?>

<?php else: ?>
    <?php ob_start(); ?>
    <form method="POST" action="/admin/orgs/store">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

        <div class="form-group-modern">
            <label class="form-label-modern">Name</label>
            <input type="text" name="name" required autofocus class="form-input-modern"
                placeholder="z.B. Jeunesses Musicales Deutschland">
        </div>

        <div class="form-group-modern">
            <label class="form-label-modern">Slug</label>
            <input type="text" name="slug" required class="form-input-modern"
                placeholder="z.B. jmd" pattern="[a-z0-9-]+"
                title="Nur Kleinbuchstaben, Zahlen und Bindestriche">
        </div>

        <div class="form-help-text mb-4">
            Ein Orga-Account wird automatisch erstellt (Benutzername: {slug}-admin).
        </div>

        <button type="submit" class="btn-base btn-md btn-primary" style="width: 100%;">Erstellen</button>
    </form>
    <?php
    $sectionContent = ob_get_clean();
    $sectionTitle = 'Neue Organisation';
    $sectionIcon = 'fa-building';
    $sectionVariant = 'default';
    include __DIR__ . '/../components/panel-section.php';
    ?>
<?php endif; ?>

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
    function copyCreatedPw() {
        var pw = document.getElementById('created-pw').textContent;
        navigator.clipboard.writeText(pw).then(function() {
            if (window.notifySuccess) window.notifySuccess('Passwort kopiert');
        });
    }
</script>