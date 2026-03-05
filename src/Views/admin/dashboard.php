<?php $this->layout('layouts/default', ['title' => 'Admin Panel', 'currentPage' => 'admin_panel', 'isFluid' => true]) ?>

<?php
$renderComponent = false;
include __DIR__ . '/../components/form-input.php';
$renderComponent = true;
?>

<style>
    .admin-stats-bar {
        display: flex;
        gap: var(--space-6);
        padding: var(--space-4) var(--space-5);
        background: var(--color-white);
        border: 1px solid var(--color-border);
        border-radius: var(--radius-xl);
        box-shadow: var(--shadow-sm);
        margin-bottom: var(--space-5);
    }

    .admin-stat {
        display: flex;
        align-items: baseline;
        gap: var(--space-2);
    }

    .admin-stat-value {
        font-size: var(--font-size-xl);
        font-weight: var(--font-weight-bold);
        color: var(--color-text-primary);
    }

    .admin-stat-label {
        font-size: var(--font-size-sm);
        color: var(--color-text-secondary);
    }

    .org-card-meta {
        display: flex;
        flex-wrap: wrap;
        gap: var(--space-1) var(--space-4);
        font-size: var(--font-size-sm);
        color: var(--color-text-secondary);
    }

    .org-card-credential {
        display: flex;
        align-items: center;
        gap: var(--space-2);
        margin-top: var(--space-3);
        padding: var(--space-3);
        background: var(--color-bg-secondary);
        border-radius: var(--radius-md);
        font-size: var(--font-size-sm);
    }

    .org-card-credential code {
        font-family: monospace;
        font-weight: var(--font-weight-semibold);
        color: var(--color-text-primary);
    }

    .org-card-pw-result {
        display: none;
        margin-top: var(--space-2);
        padding: var(--space-3);
        background: var(--color-success-100);
        border-radius: var(--radius-md);
        font-size: var(--font-size-sm);
        color: var(--color-success-700);
        word-break: break-all;
    }

    .org-card-pw-result.visible {
        display: flex;
        align-items: center;
        gap: var(--space-2);
    }

    .org-card-actions {
        display: flex;
        align-items: center;
        gap: var(--space-2);
        margin-top: var(--space-4);
        padding-top: var(--space-3);
        border-top: 1px solid var(--color-border);
    }

    @media (max-width: 600px) {
        .admin-stats-bar {
            flex-direction: column;
            gap: var(--space-3);
        }
    }
</style>

<?php ob_start(); ?>

<?php
$totalEnsembles = 0;
$totalUsers = 0;
foreach ($organizations as $org) {
    $totalEnsembles += (int)($org['ensemble_count'] ?? 0);
    $totalUsers += (int)($org['user_count'] ?? 0);
}
?>

<div class="flex-between mb-6" style="flex-wrap: wrap; gap: var(--space-3);">
    <div class="text-heading" style="font-size: var(--font-size-xl);">Organisationen</div>
    <a href="/admin/orgs/create" class="btn-base btn-sm btn-primary">
        <i class="fas fa-plus"></i> Erstellen
    </a>
</div>

<div class="admin-stats-bar">
    <div class="admin-stat">
        <span class="admin-stat-value"><?= count($organizations) ?></span>
        <span class="admin-stat-label">Organisation<?= count($organizations) !== 1 ? 'en' : '' ?></span>
    </div>
    <div class="admin-stat">
        <span class="admin-stat-value"><?= $totalEnsembles ?></span>
        <span class="admin-stat-label">Ensembles</span>
    </div>
    <div class="admin-stat">
        <span class="admin-stat-value"><?= $totalUsers ?></span>
        <span class="admin-stat-label">Nutzer</span>
    </div>
</div>

<?php if (empty($organizations)): ?>
    <?php
    $title = 'Keine Organisationen';
    $message = 'Noch keine Organisationen vorhanden.';
    $actionHref = '/admin/orgs/create';
    $actionLabel = 'Organisation erstellen';
    include __DIR__ . '/../components/empty-state.php';
    ?>
<?php endif; ?>

<?php foreach ($organizations as $org): ?>
    <div class="modern-card mb-4">
        <div class="modern-card-header">
            <div class="flex-between">
                <div class="flex-start gap-3">
                    <i class="fas fa-building"></i>
                    <span><?= htmlspecialchars($org['name']) ?></span>
                </div>
                <div class="org-card-meta" style="margin: 0;">
                    <span><?= (int)($org['ensemble_count'] ?? 0) ?> Ensembles</span>
                    <span><?= (int)($org['user_count'] ?? 0) ?> Nutzer</span>
                </div>
            </div>
        </div>
        <div class="modern-card-body">
            <div class="org-card-meta mb-3">
                <span><i class="fas fa-tag text-muted"></i> <?= htmlspecialchars($org['slug']) ?></span>
            </div>

            <?php if (!empty($org['org_account'])): ?>
                <div class="org-card-credential">
                    <i class="fas fa-user-shield text-muted"></i>
                    <span>Orga-Account:</span>
                    <code><?= htmlspecialchars($org['org_account']['email']) ?></code>
                    <button type="button" class="btn-base btn-xs btn-ghost" style="margin-left: auto;"
                        onclick="regeneratePassword('<?= htmlspecialchars($org['slug']) ?>', this)">
                        <i class="fas fa-rotate"></i> PW neu
                    </button>
                </div>
                <div class="org-card-pw-result" id="pw-result-<?= htmlspecialchars($org['slug']) ?>">
                    <i class="fas fa-key"></i>
                    <span>Neues PW:</span>
                    <code class="pw-value"></code>
                    <button type="button" class="btn-base btn-xs btn-ghost" onclick="copyPassword(this)">
                        <i class="fas fa-copy"></i>
                    </button>
                </div>
            <?php endif; ?>

            <div class="org-card-actions">
                <a href="/admin/orgs/<?= htmlspecialchars($org['slug']) ?>/edit" class="btn-base btn-sm btn-outline">
                    <i class="fas fa-pen"></i> Bearbeiten
                </a>
                <form method="POST" action="/admin/orgs/<?= htmlspecialchars($org['slug']) ?>/delete" style="margin-left: auto;"
                    onsubmit="return confirm('Organisation wirklich löschen? Alle Ensembles und Nutzer werden gelöscht.')">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                    <button type="submit" class="btn-base btn-sm btn-danger">
                        <i class="fas fa-trash"></i> Löschen
                    </button>
                </form>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<?php
$panelContent = ob_get_clean();
$panelTitle = 'Admin Panel';
$panelBadge = 'Admin';
$panelVariant = 'admin';
$panelLogoutUrl = '/admin/logout';
include __DIR__ . '/../components/panel-shell.php';
?>

<script>
    function regeneratePassword(orgId, btn) {
        if (!confirm('Neues Passwort generieren? Das alte wird ungültig.')) return;

        btn.disabled = true;
        var csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

        fetch('/admin/orgs/' + orgId + '/regenerate-password', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: 'csrf_token=' + encodeURIComponent(csrfToken),
            })
            .then(function(r) {
                if (!r.ok) return r.text().then(function(t) {
                    throw new Error(t);
                });
                return r.json();
            })
            .then(function(data) {
                btn.disabled = false;
                if (data.password) {
                    var result = document.getElementById('pw-result-' + orgId);
                    result.querySelector('.pw-value').textContent = data.password;
                    result.classList.add('visible');
                    if (window.notifySuccess) window.notifySuccess('Passwort generiert');
                } else if (data.error) {
                    window.notifyError(data.error || 'Passwort konnte nicht generiert werden');
                }
            })
            .catch(function(err) {
                btn.disabled = false;
                window.notifyError('Fehler beim Generieren: ' + (err.message || 'Verbindung fehlgeschlagen'));
            });
    }

    function copyPassword(btn) {
        var pw = btn.closest('.org-card-pw-result').querySelector('.pw-value').textContent;
        navigator.clipboard.writeText(pw).then(function() {
            if (window.notifySuccess) window.notifySuccess('Passwort kopiert');
        });
    }
</script>