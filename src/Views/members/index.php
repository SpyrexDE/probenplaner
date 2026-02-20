<?php $this->layout('layouts/default', ['title' => 'Mitglieder', 'currentPage' => 'members']) ?>

<?php
$renderComponent = false;
include __DIR__ . '/../components/user-badge.php';
$renderComponent = true;
?>

<style>
    .members-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: var(--space-4);
    }

    .members-search {
        width: 100%;
        max-width: 300px;
        padding: var(--space-2) var(--space-3);
        border: 1px solid var(--color-gray-300);
        border-radius: var(--radius-lg);
        font-size: var(--font-size-sm);
    }

    .members-search:focus {
        outline: none;
        border-color: var(--color-primary);
        box-shadow: 0 0 0 2px rgba(71, 140, 244, 0.15);
    }

    .section-group-card {
        background: white;
        border: 1px solid var(--color-gray-200);
        border-radius: var(--radius-xl);
        margin-bottom: var(--space-4);
        overflow: hidden;
    }

    .section-group-title {
        font-size: var(--font-size-sm);
        font-weight: 600;
        color: var(--color-gray-500);
        text-transform: uppercase;
        letter-spacing: 0.05em;
        padding: var(--space-3) var(--space-4);
        border-bottom: 1px solid var(--color-gray-100);
        background: var(--color-gray-50);
    }

    .member-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: var(--space-3) var(--space-4);
        border-bottom: 1px solid var(--color-gray-50);
        transition: background var(--transition-base);
    }

    .member-item:last-child {
        border-bottom: none;
    }

    .member-item:hover {
        background: var(--color-gray-50);
    }

    .member-info {
        display: flex;
        align-items: center;
        gap: var(--space-3);
    }

    .member-avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: var(--color-primary-100);
        color: var(--color-primary);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: var(--font-size-sm);
        flex-shrink: 0;
    }

    .member-name {
        font-weight: 500;
        font-size: var(--font-size-sm);
    }

    .member-badges {
        display: flex;
        gap: var(--space-1);
        align-items: center;
    }

    .member-badge {
        font-size: var(--font-size-xs);
        padding: 1px 6px;
        border-radius: var(--radius-md);
        font-weight: 500;
    }

    .member-badge-leader {
        background: var(--color-primary-100);
        color: var(--color-primary);
    }

    .member-badge-section-leader {
        background: var(--color-success-100);
        color: var(--color-success-700);
    }

    .member-edit-btn {
        background: none;
        border: none;
        cursor: pointer;
        color: var(--color-gray-400);
        padding: var(--space-1);
        border-radius: var(--radius-md);
        transition: all var(--transition-base);
    }

    .member-edit-btn:hover {
        color: var(--color-gray-600);
        background: var(--color-gray-100);
    }

    .invite-btn-header {
        padding: var(--space-2) var(--space-3);
        border-radius: var(--radius-lg);
        font-size: var(--font-size-sm);
        font-weight: 500;
        background: white;
        border: 1px solid var(--color-gray-300);
        cursor: pointer;
        transition: all var(--transition-base);
        display: flex;
        align-items: center;
        gap: var(--space-1);
    }

    .invite-btn-header:hover {
        border-color: var(--color-primary);
        color: var(--color-primary);
    }

    /* Edit modal */
    .member-modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.4);
        z-index: 1000;
        display: none;
        align-items: center;
        justify-content: center;
    }

    .member-modal-overlay.active {
        display: flex;
    }

    .member-modal {
        background: white;
        border-radius: var(--radius-xl);
        box-shadow: var(--shadow-xl);
        width: 100%;
        max-width: 480px;
        max-height: 90vh;
        overflow-y: auto;
        padding: var(--space-6);
        position: relative;
    }

    .member-modal-close {
        position: absolute;
        top: var(--space-4);
        right: var(--space-4);
        background: none;
        border: none;
        cursor: pointer;
        color: var(--color-gray-400);
        font-size: var(--font-size-lg);
    }

    .member-modal-close:hover {
        color: var(--color-gray-600);
    }

    .member-modal-title {
        font-size: var(--font-size-lg);
        font-weight: 600;
        margin-bottom: var(--space-1);
    }

    .member-modal-email {
        font-size: var(--font-size-sm);
        color: var(--color-gray-500);
        margin-bottom: var(--space-4);
    }

    .modal-section-title {
        font-size: var(--font-size-sm);
        font-weight: 500;
        color: var(--color-gray-400);
        margin: var(--space-4) 0 var(--space-2);
        padding-bottom: var(--space-1);
        border-bottom: 1px solid var(--color-gray-100);
    }

    .modal-perm-row {
        display: flex;
        align-items: center;
        gap: var(--space-2);
        padding: var(--space-1) 0;
        font-size: var(--font-size-sm);
    }

    .modal-perm-row input[type="checkbox"] {
        accent-color: var(--color-primary);
    }

    .modal-danger-section {
        margin-top: var(--space-4);
        padding-top: var(--space-3);
        border-top: 1px solid var(--color-gray-100);
    }

    /* Invite modal */
    .invite-modal-link {
        background: var(--color-gray-50);
        border-radius: var(--radius-lg);
        padding: var(--space-3);
        font-family: monospace;
        font-size: var(--font-size-sm);
        word-break: break-all;
        margin-bottom: var(--space-3);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--space-2);
    }
</style>

<div class="members-header">
    <h1 class="text-xl font-bold flex items-center gap-2">👥 Mitglieder</h1>
    <div class="flex items-center gap-3">
        <?php if ($canManage): ?>
            <button class="invite-btn-header" onclick="openInviteModal()">🔗 Einladen</button>
        <?php endif; ?>
    </div>
</div>

<input type="text" class="members-search mb-4" placeholder="🔍 Suchen..." id="memberSearch" oninput="filterMembers(this.value)">

<?php
// Group into display-friendly sections
$sectionOrder = [];
foreach ($sections as $groupName => $groupSections) {
    foreach ($groupSections as $s) {
        $sectionOrder[$s] = $groupName;
    }
}

$displayGroups = [];
foreach ($grouped as $sectionName => $members) {
    $groupName = $sectionOrder[$sectionName] ?? 'Sonstige';
    $displayGroups[$groupName][$sectionName] = $members;
}
?>

<?php foreach ($displayGroups as $groupName => $sectionMembers): ?>
    <div class="section-group-card" data-group>
        <div class="section-group-title"><?= htmlspecialchars($groupName) ?></div>
        <?php foreach ($sectionMembers as $sectionName => $members): ?>
            <div class="section-group-title" style="font-size:var(--font-size-xs);padding:var(--space-2) var(--space-4);background:transparent">
                <?= htmlspecialchars($sectionName) ?> (<?= count($members) ?>)
            </div>
            <?php foreach ($members as $member):
                $displayName = $member['display_name'] ?? $member['username'];
                $initial = strtoupper(substr($displayName, 0, 1));
                $isLeader = !empty($member['can_manage_ensemble']);
                $isSectionLeader = !empty($member['can_view_own_section_stats']) && empty($member['can_manage_ensemble']);
            ?>
                <div class="member-item" data-member-name="<?= htmlspecialchars(strtolower($displayName)) ?>">
                    <div class="member-info">
                        <div class="member-avatar"><?= $initial ?></div>
                        <div>
                            <div class="member-name"><?= htmlspecialchars($displayName) ?></div>
                            <div class="member-badges">
                                <?php if ($isLeader): ?>
                                    <span class="member-badge member-badge-leader">Leitung</span>
                                <?php elseif ($isSectionLeader): ?>
                                    <span class="member-badge member-badge-section-leader">Reg.leitung</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php if ($canManage): ?>
                        <button class="member-edit-btn" onclick="openEditModal(<?= (int)$member['user_id'] ?>)" title="Bearbeiten">
                            ⚙️
                        </button>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </div>
<?php endforeach; ?>

<!-- Edit Member Modal -->
<div class="member-modal-overlay" id="editModal">
    <div class="member-modal">
        <button class="member-modal-close" onclick="closeEditModal()">✕</button>
        <div class="member-modal-title" id="modalName"></div>
        <div class="member-modal-email" id="modalEmail"></div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Register</label>
            <select id="modalType" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                <?php foreach ($sections as $group => $items): ?>
                    <optgroup label="<?= htmlspecialchars($group) ?>">
                        <?php foreach ($items as $item): ?>
                            <option value="<?= htmlspecialchars($item) ?>"><?= htmlspecialchars($item) ?></option>
                        <?php endforeach; ?>
                    </optgroup>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mt-3">
            <label class="modal-perm-row">
                <input type="checkbox" id="modalSmallGroup">
                Kleingruppe
            </label>
        </div>

        <div class="modal-section-title">Berechtigungen</div>

        <div class="modal-perm-row"><input type="checkbox" id="perm_view_own_section"> Eigenes Register sehen</div>
        <div class="modal-perm-row"><input type="checkbox" id="perm_view_all_section"> Alle Register-Statistiken sehen</div>
        <div class="modal-perm-row"><input type="checkbox" id="perm_view_members"> Mitgliederliste sehen</div>
        <div class="modal-perm-row"><input type="checkbox" id="perm_manage_rehearsals"> Proben verwalten</div>
        <div class="modal-perm-row"><input type="checkbox" id="perm_manage_members"> Mitglieder verwalten</div>
        <div class="modal-perm-row"><input type="checkbox" id="perm_manage_permissions"> Berechtigungen vergeben</div>

        <div class="modal-danger-section">
            <div class="modal-section-title" style="color:var(--color-error)">Gefährliche Aktionen</div>
            <button class="px-3 py-1 border border-red-300 text-red-600 rounded-lg text-sm hover:bg-red-50 transition-colors"
                id="removeMemberBtn">
                Aus Ensemble entfernen
            </button>
        </div>

        <button class="w-full mt-4 px-4 py-2 bg-primary text-white rounded-lg font-medium hover:bg-primary-600 transition-colors"
            onclick="saveEditModal()">
            Speichern
        </button>
    </div>
</div>

<!-- Invite Link Modal -->
<div class="member-modal-overlay" id="inviteModal">
    <div class="member-modal" style="max-width:420px">
        <button class="member-modal-close" onclick="closeInviteModal()">✕</button>
        <div class="member-modal-title" style="display:flex;align-items:center;gap:var(--space-2);margin-bottom:var(--space-3)">🔗 Einladungslink</div>

        <p class="text-sm text-gray-500 mb-3">Teile diesen Link mit neuen Mitgliedern.</p>

        <?php if ($inviteLink): ?>
            <div class="invite-modal-link" id="inviteLinkText">
                <span><?= htmlspecialchars(rtrim($_SERVER['HTTP_HOST'] ?? '', '/') . '/invite/' . $inviteLink['token']) ?></span>
                <button onclick="copyLink()" class="text-gray-400 hover:text-gray-600 cursor-pointer" style="background:none;border:none">📋</button>
            </div>
            <div class="text-sm text-gray-500 mb-3">
                Bisher genutzt: <?= (int)($inviteLink['used_count'] ?? 0) ?>×
            </div>
            <label class="modal-perm-row mb-3">
                <input type="checkbox" id="keycloakOnlyToggle" <?= !empty($inviteLink['keycloak_only']) ? 'checked' : '' ?>
                    onchange="toggleKeycloak()">
                Nur JMD-Accounts erlauben
            </label>
            <button class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-50 transition-colors"
                onclick="regenerateLink()">
                🔄 Neuen Link generieren
            </button>
            <div class="text-xs text-gray-400 mt-1">Alter Link wird sofort ungültig.</div>
        <?php else: ?>
            <button class="w-full px-3 py-2 bg-primary text-white rounded-lg text-sm"
                onclick="regenerateLink()">
                Link generieren
            </button>
        <?php endif; ?>
    </div>
</div>

<script>
    const orchestraBase = '<?= htmlspecialchars(($_SESSION['current_org_slug'] ?? '') . '/' . ($_SESSION['current_orchestra_slug'] ?? '')) ?>';
    let currentMemberId = null;

    function filterMembers(query) {
        query = query.toLowerCase();
        document.querySelectorAll('[data-member-name]').forEach(function(el) {
            el.style.display = el.dataset.memberName.includes(query) ? '' : 'none';
        });
    }

    function openEditModal(userId) {
        currentMemberId = userId;
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        fetch('/' + orchestraBase + '/members/' + userId + '/details', {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(r => r.json())
            .then(data => {
                document.getElementById('modalName').textContent = data.display_name;
                document.getElementById('modalEmail').textContent = data.username;
                document.getElementById('modalType').value = data.type || '';
                document.getElementById('modalSmallGroup').checked = data.is_small_group;

                const permMap = {
                    'perm_view_own_section': 'can_view_own_section_stats',
                    'perm_view_all_section': 'can_view_all_section_stats',
                    'perm_view_members': 'can_view_members',
                    'perm_manage_rehearsals': 'can_manage_rehearsals',
                    'perm_manage_members': 'can_manage_members',
                    'perm_manage_permissions': 'can_manage_permissions',
                };
                Object.entries(permMap).forEach(function([elId, perm]) {
                    document.getElementById(elId).checked = data.permissions[perm] || false;
                });

                document.getElementById('removeMemberBtn').onclick = function() {
                    removeMember(userId);
                };
                document.getElementById('editModal').classList.add('active');
            })
            .catch(function(err) {
                window.notifyErrorWithDetails('Fehler beim Laden der Mitgliederdaten', err.message || String(err));
            });
    }

    function closeEditModal() {
        document.getElementById('editModal').classList.remove('active');
        currentMemberId = null;
    }

    function saveEditModal() {
        if (!currentMemberId) return;
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const perms = [];
        const permMap = {
            'perm_view_own_section': 'can_view_own_section_stats',
            'perm_view_all_section': 'can_view_all_section_stats',
            'perm_view_members': 'can_view_members',
            'perm_manage_rehearsals': 'can_manage_rehearsals',
            'perm_manage_members': 'can_manage_members',
            'perm_manage_permissions': 'can_manage_permissions',
        };
        Object.entries(permMap).forEach(function([elId, perm]) {
            if (document.getElementById(elId).checked) perms.push(perm);
        });

        const body = new URLSearchParams({
            csrf_token: csrfToken,
            type: document.getElementById('modalType').value,
            is_small_group: document.getElementById('modalSmallGroup').checked ? '1' : '0',
            permissions: JSON.stringify(perms),
        });

        fetch('/' + orchestraBase + '/members/' + currentMemberId + '/update', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: body,
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    if (window.notifySuccess) window.notifySuccess('Gespeichert');
                    closeEditModal();
                    location.reload();
                } else {
                    window.notifyErrorWithDetails('Speichern fehlgeschlagen', data.debug_message || data.error || JSON.stringify(data));
                }
            })
            .catch(function(err) {
                window.notifyErrorWithDetails('Speichern fehlgeschlagen', err.message || String(err));
            });
    }

    function removeMember(userId) {
        if (!confirm('Mitglied wirklich aus dem Ensemble entfernen?')) return;
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        fetch('/' + orchestraBase + '/members/' + userId + '/remove', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: new URLSearchParams({
                    csrf_token: csrfToken
                }),
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    if (window.notifySuccess) window.notifySuccess('Mitglied entfernt');
                    closeEditModal();
                    location.reload();
                } else {
                    window.notifyErrorWithDetails('Entfernen fehlgeschlagen', data.debug_message || data.error || JSON.stringify(data));
                }
            })
            .catch(function(err) {
                window.notifyErrorWithDetails('Entfernen fehlgeschlagen', err.message || String(err));
            });
    }

    function openInviteModal() {
        document.getElementById('inviteModal').classList.add('active');
    }

    function closeInviteModal() {
        document.getElementById('inviteModal').classList.remove('active');
    }

    function copyLink() {
        const text = document.querySelector('#inviteLinkText span')?.textContent?.trim();
        if (text) {
            navigator.clipboard.writeText('https://' + text).then(function() {
                if (window.notifySuccess) window.notifySuccess('Link kopiert');
            });
        }
    }

    function regenerateLink() {
        if (!confirm('Neuen Link generieren? Alter Link wird ungültig.')) return;
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        fetch('/' + orchestraBase + '/invite/regenerate', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: new URLSearchParams({
                    csrf_token: csrfToken
                }),
            })
            .then(r => r.json())
            .then(data => {
                if (data.success === false) {
                    window.notifyErrorWithDetails('Link-Generierung fehlgeschlagen', data.debug_message || data.error || JSON.stringify(data));
                } else {
                    location.reload();
                }
            })
            .catch(function(err) {
                window.notifyErrorWithDetails('Link-Generierung fehlgeschlagen', err.message || String(err));
            });
    }

    function toggleKeycloak() {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        fetch('/' + orchestraBase + '/invite/toggle-keycloak', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: new URLSearchParams({
                    csrf_token: csrfToken
                }),
            })
            .then(r => r.json())
            .then(data => {
                if (data.success === false) {
                    window.notifyErrorWithDetails('Einstellung fehlgeschlagen', data.debug_message || data.error || JSON.stringify(data));
                }
            })
            .catch(function(err) {
                window.notifyErrorWithDetails('Einstellung fehlgeschlagen', err.message || String(err));
            });
    }

    // Close modals on overlay click
    document.querySelectorAll('.member-modal-overlay').forEach(function(overlay) {
        overlay.addEventListener('click', function(e) {
            if (e.target === overlay) {
                overlay.classList.remove('active');
            }
        });
    });
</script>