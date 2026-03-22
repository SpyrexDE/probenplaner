<?php $this->layout('layouts/default', ['title' => 'Orga-Panel', 'currentPage' => 'orga_panel', 'isFluid' => true]) ?>

<?php
$renderComponent = false;
include __DIR__ . '/../components/form-input.php';
$renderComponent = true;
$orgSlug = htmlspecialchars($org['slug'] ?? '');
?>

<?php ob_start(); ?>

<div class="flex-between mb-6">
    <div>
        <div class="text-heading" style="font-size: var(--font-size-lg);">🎵 Ensembles</div>
        <div class="text-muted" style="font-size: var(--font-size-sm); margin-top: 2px;">
            <?= count($ensembles) ?> Ensemble<?= count($ensembles) !== 1 ? 's' : '' ?>
        </div>
    </div>
    <a href="/orga/ensembles/create" class="btn-base btn-sm btn-primary">
        <i class="fas fa-plus"></i> Erstellen
    </a>
</div>

<?php if (!empty($ensembles)): ?>
<div class="ensemble-search-wrap mb-4">
    <div class="ensemble-search-input-wrap">
        <i class="fas fa-search ensemble-search-icon"></i>
        <input
            type="search"
            id="ensemble-search"
            class="ensemble-search-input"
            placeholder="Ensemble suchen…"
            autocomplete="off"
        >
    </div>
</div>
<?php endif; ?>

<?php if (empty($ensembles)): ?>
    <?php
    $title = 'Keine Ensembles';
    $message = 'Noch keine Ensembles vorhanden.';
    $actionHref = '/orga/ensembles/create';
    $actionLabel = 'Ensemble erstellen';
    include __DIR__ . '/../components/empty-state.php';
    ?>
<?php endif; ?>

<div id="ensemble-no-results" class="ensemble-no-results" style="display:none;">
    <i class="fas fa-search"></i> Keine Ensembles gefunden.
</div>

<?php foreach ($ensembles as $ensemble): ?>
    <?php
    $ensembleSlug = htmlspecialchars($ensemble['slug'] ?? '');
    $ensembleId = (int)$ensemble['id'];
    $memberLink = $ensemble['member_link'];
    $conductorLink = $ensemble['conductor_link'];
    ?>
    <div class="modern-card mb-3 ensemble-card" data-name="<?= strtolower(htmlspecialchars($ensemble['name'])) ?>">
        <div class="modern-card-body">
            <div class="flex-between" style="align-items: flex-start;">
                <div style="flex: 1; min-width: 0;">
                    <div class="text-heading" style="font-size: var(--font-size-lg);">🎵 <?= htmlspecialchars($ensemble['name']) ?></div>
                    <div class="ensemble-slug">/<?= $orgSlug ?>/<?= $ensembleSlug ?></div>
                    <div class="ensemble-stats">
                        <span class="ensemble-stat">
                            <i class="fas fa-users"></i> <?= (int)$ensemble['member_count'] ?> Mitglieder
                        </span>
                        <?php if (!empty($ensemble['conductors'])): ?>
                            <span class="ensemble-stat">
                                <i class="fas fa-music"></i> Leitung:
                                <?= implode(', ', array_map(fn($c) => htmlspecialchars($c['display_name'] ?? $c['email']), $ensemble['conductors'])) ?>
                            </span>
                        <?php else: ?>
                            <span class="ensemble-stat text-subtle">
                                <i class="fas fa-music"></i> Leitung: —
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="flex-end gap-2" style="flex-shrink: 0;">
                    <a href="/orga/ensembles/<?= $ensembleSlug ?>/edit" class="btn-base btn-xs btn-ghost">
                        <i class="fas fa-pen"></i> Bearbeiten
                    </a>
                    <form method="POST" action="/orga/ensembles/<?= $ensembleSlug ?>/delete"
                        onsubmit="return confirm('Ensemble wirklich löschen?')">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                        <button type="submit" class="btn-base btn-xs btn-danger">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                </div>
            </div>

            <div class="ensemble-links">
                <!-- Member link -->
                <div class="link-row">
                    <div class="link-label"><i class="fas fa-user-plus"></i> Mitglieder</div>
                    <?php if ($memberLink): ?>
                        <div class="invite-link-bar">
                            <code class="invite-link-url"><?= htmlspecialchars(rtrim($_SERVER['HTTP_HOST'] ?? 'probenplaner', '/')) ?>/invite/<?= htmlspecialchars($memberLink['token']) ?></code>
                            <div class="flex-end gap-1">
                                <button type="button" class="btn-base btn-xs btn-success" onclick="copyInviteLink(this, '<?= htmlspecialchars($memberLink['token']) ?>')">
                                    <i class="fas fa-copy"></i>
                                </button>
                                <button type="button" class="btn-base btn-xs btn-ghost" onclick="regenerateLink('<?= $ensembleSlug ?>', 'member', this)" title="Neu generieren">
                                    <i class="fas fa-sync-alt"></i>
                                </button>
                            </div>
                        </div>
                    <?php else: ?>
                        <button type="button" class="btn-base btn-xs btn-ghost" onclick="generateLink('<?= $ensembleSlug ?>', 'member', this)">
                            <i class="fas fa-plus"></i> Link generieren
                        </button>
                    <?php endif; ?>
                </div>

                <!-- Conductor link -->
                <div class="link-row">
                    <div class="link-label"><i class="fas fa-user-tie"></i> Leitung</div>
                    <?php if ($conductorLink): ?>
                        <div class="invite-link-bar invite-link-bar--conductor">
                            <code class="invite-link-url"><?= htmlspecialchars(rtrim($_SERVER['HTTP_HOST'] ?? 'probenplaner', '/')) ?>/invite/<?= htmlspecialchars($conductorLink['token']) ?></code>
                            <div class="flex-end gap-1">
                                <button type="button" class="btn-base btn-xs btn-success" onclick="copyInviteLink(this, '<?= htmlspecialchars($conductorLink['token']) ?>')">
                                    <i class="fas fa-copy"></i>
                                </button>
                                <button type="button" class="btn-base btn-xs btn-ghost" onclick="regenerateLink('<?= $ensembleSlug ?>', 'conductor', this)" title="Neu generieren">
                                    <i class="fas fa-sync-alt"></i>
                                </button>
                            </div>
                        </div>
                        <?php if (!empty($conductorLink['expires_at'])): ?>
                            <div class="link-expiry" style="margin-top: 2px; margin-left: 100px;">
                                <i class="fas fa-clock"></i> Gültig bis <?= date('d.m.Y', strtotime($conductorLink['expires_at'])) ?>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <button type="button" class="btn-base btn-xs btn-ghost" onclick="generateLink('<?= $ensembleSlug ?>', 'conductor', this)">
                            <i class="fas fa-plus"></i> Leitungs-Link generieren
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<style>
    .ensemble-search-wrap {
        max-width: 420px;
    }

    .ensemble-search-input-wrap {
        position: relative;
    }

    .ensemble-search-icon {
        position: absolute;
        left: var(--space-3);
        top: 50%;
        transform: translateY(-50%);
        color: var(--color-text-subtle);
        font-size: var(--font-size-sm);
        pointer-events: none;
    }

    .ensemble-search-input {
        width: 100%;
        padding: var(--space-2) var(--space-3) var(--space-2) calc(var(--space-3) * 2 + 0.875rem);
        font-size: var(--font-size-sm);
        border: 1px solid var(--color-border);
        border-radius: var(--radius-md);
        background: var(--color-surface);
        color: var(--color-text-primary);
        outline: none;
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
        box-sizing: border-box;
    }

    .ensemble-search-input:focus {
        border-color: var(--color-primary);
        box-shadow: 0 0 0 3px rgba(var(--color-primary-rgb, 99, 102, 241), 0.12);
    }

    .ensemble-no-results {
        color: var(--color-text-subtle);
        font-size: var(--font-size-sm);
        padding: var(--space-4) 0;
        display: flex;
        align-items: center;
        gap: var(--space-2);
    }

    .ensemble-card {
        transition: border-color 0.2s ease;
    }

    .ensemble-slug {
        font-family: var(--font-mono, monospace);
        font-size: var(--font-size-xs);
        color: var(--color-text-subtle);
        margin-top: var(--space-1);
    }

    .ensemble-stats {
        display: flex;
        gap: var(--space-4);
        flex-wrap: wrap;
        margin-top: var(--space-2);
        font-size: var(--font-size-sm);
        color: var(--color-text-subtle);
    }

    .ensemble-stat {
        display: inline-flex;
        align-items: center;
        gap: var(--space-1);
    }

    .ensemble-stat i {
        font-size: 0.75em;
        opacity: 0.7;
    }

    .ensemble-links {
        margin-top: var(--space-3);
        display: flex;
        flex-direction: column;
        gap: var(--space-2);
    }

    .link-row {
        display: flex;
        align-items: center;
        gap: var(--space-3);
        font-size: var(--font-size-sm);
    }

    .link-label {
        flex-shrink: 0;
        width: 100px;
        color: var(--color-text-subtle);
        display: flex;
        align-items: center;
        gap: var(--space-1);
        font-size: var(--font-size-xs);
        text-transform: uppercase;
        letter-spacing: 0.03em;
    }

    .invite-link-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--space-2);
        flex: 1;
        padding: var(--space-1) var(--space-2);
        border-radius: var(--radius-md);
        background: var(--color-success-50, rgba(16, 185, 129, 0.05));
        border: 1px solid var(--color-success-200, rgba(16, 185, 129, 0.15));
    }

    .invite-link-bar--conductor {
        background: rgba(99, 102, 241, 0.05);
        border-color: rgba(99, 102, 241, 0.15);
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

    @media (max-width: 600px) {
        .ensemble-card .flex-between {
            flex-direction: column;
            align-items: stretch !important;
            gap: var(--space-3);
        }

        .ensemble-card .flex-end {
            justify-content: flex-start;
        }

        .link-row {
            flex-direction: column;
            align-items: stretch;
            gap: var(--space-1);
        }

        .link-label {
            width: auto;
        }

        .invite-link-bar {
            flex-wrap: wrap;
            gap: var(--space-1);
        }

        .invite-link-url {
            min-width: 0;
            flex: 1;
        }

        .link-expiry {
            margin-left: 0 !important;
        }
    }
</style>

<script>
    (function () {
        var input = document.getElementById('ensemble-search');
        if (!input) return;
        var cards = document.querySelectorAll('.ensemble-card');
        var noResults = document.getElementById('ensemble-no-results');

        input.addEventListener('input', function () {
            var q = input.value.trim().toLowerCase();
            var visible = 0;
            cards.forEach(function (card) {
                var match = !q || card.dataset.name.includes(q);
                card.style.display = match ? '' : 'none';
                if (match) visible++;
            });
            noResults.style.display = visible === 0 ? 'flex' : 'none';
        });
    }());
</script>

<script>
    function copyInviteLink(btn, token) {
        var url = window.location.origin + '/invite/' + token;
        navigator.clipboard.writeText(url).then(function() {
            var orig = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-check"></i>';
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
</script>

<?php
$panelContent = ob_get_clean();
$panelTitle = htmlspecialchars($org['name']);
$panelBadge = 'Orga';
$panelVariant = 'orga';
$panelLogoutUrl = '/logout';
include __DIR__ . '/../components/panel-shell.php';
?>