<?php $this->layout('layouts/default', ['title' => 'Mitglieder', 'currentPage' => 'members']) ?>

<?php
$renderComponent = false;
include __DIR__ . '/../components/user-badge.php';
include __DIR__ . '/../components/form-input.php';
$renderComponent = true;
?>

<style>
    /* === MEMBERS DIRECTORY === */

    .members-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--space-3);
        margin-bottom: var(--space-5);
        flex-wrap: wrap;
    }

    .members-count {
        font-size: var(--font-size-2xl);
        color: var(--color-text-primary);
        font-weight: var(--font-weight-bold);
        letter-spacing: -0.01em;
    }

    .members-count span {
        color: var(--color-text-muted);
        font-weight: var(--font-weight-normal);
        font-size: var(--font-size-lg);
    }

    .toolbar-actions {
        display: flex;
        align-items: center;
        gap: var(--space-2);
    }

    /* Search */
    .members-search-wrapper {
        position: relative;
    }

    .members-search-icon {
        position: absolute;
        left: var(--space-3);
        top: 50%;
        transform: translateY(-50%);
        color: var(--color-text-muted);
        font-size: var(--font-size-xs);
        pointer-events: none;
        transition: color var(--transition-base);
    }

    .members-search {
        width: 100%;
        padding: var(--space-2) var(--space-3) var(--space-2) calc(var(--space-8) + 2px);
        border: 2px solid var(--color-border);
        border-radius: var(--radius-full);
        font-size: var(--font-size-sm);
        color: var(--color-text-primary);
        background: var(--color-bg-primary);
        transition: all var(--transition-base);
    }

    .members-search:focus {
        outline: none;
        border-color: var(--color-primary);
        box-shadow: 0 0 0 3px rgba(71, 140, 244, 0.1);
    }

    .members-search:focus~.members-search-icon {
        color: var(--color-primary);
    }

    /* === LAYOUT: Roster + Sidebar === */
    .members-layout {
        display: grid;
        grid-template-columns: 1fr;
        gap: var(--space-5);
    }

    .members-layout.has-sidebar {
        grid-template-columns: 1fr 280px;
    }

    /* Desktop: hide mobile toolbar items, show sidebar search */
    .toolbar-btn-mobile {
        display: none !important;
    }

    .search-mobile {
        display: none !important;
    }

    @media (max-width: 900px) {
        .members-layout.has-sidebar {
            grid-template-columns: 1fr;
        }

        .members-admin-panel {
            display: none !important;
        }

        .toolbar-btn-mobile {
            display: inline-flex !important;
        }

        .search-mobile {
            display: block !important;
        }
    }

    /* === SECTION BANDS === */
    .section-band {
        background: var(--color-white);
        border: 1px solid var(--color-border-light);
        border-radius: var(--radius-xl);
        box-shadow: var(--shadow-sm);
        padding: var(--space-4);
        margin-bottom: var(--space-4);
        transition: box-shadow var(--transition-base);
    }

    .section-band:hover {
        box-shadow: var(--shadow-md);
    }

    .section-band-header {
        display: flex;
        align-items: center;
        gap: var(--space-3);
        margin-bottom: var(--space-3);
        padding-bottom: var(--space-3);
        border-bottom: 1px solid color-mix(in srgb, var(--section-accent, var(--color-primary)) 15%, transparent);
    }

    .section-band-icon {
        width: 36px;
        height: 36px;
        border-radius: var(--radius-lg);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: 18px;
        line-height: 1;
        background: linear-gradient(135deg, var(--color-bg-tertiary), var(--color-bg-secondary)) !important;
        box-shadow: 0 4px 12px color-mix(in srgb, var(--color-bg-tertiary) 50%, transparent);
    }

    .section-band-name {
        font-size: var(--font-size-base);
        font-weight: var(--font-weight-bold);
        color: var(--color-text-primary);
        letter-spacing: 0.02em;
        flex: 1;
    }

    .section-band-count {
        font-size: var(--font-size-xs);
        color: var(--color-text-muted);
        font-weight: var(--font-weight-semibold);
        background: var(--color-bg-tertiary);
        padding: 3px 10px;
        border-radius: var(--radius-full);
        min-width: 28px;
        text-align: center;
    }

    /* === MEMBER ROWS === */
    .member-list {
        display: flex;
        flex-direction: column;
    }

    .member-row {
        display: flex;
        align-items: center;
        gap: var(--space-3);
        padding: var(--space-2) var(--space-1);
        border-radius: var(--radius-base);
        transition: background 0.15s ease;
        animation: rowReveal 0.3s ease-out both;
        animation-delay: calc(var(--row-i, 0) * 20ms);
        cursor: pointer;
    }

    @keyframes rowReveal {
        from {
            opacity: 0;
            transform: translateY(4px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .member-row:hover {
        background: var(--color-bg-secondary);
    }

    .member-row.dimmed {
        opacity: 0.12;
        pointer-events: none;
        filter: grayscale(1);
    }

    .member-row-avatar {
        width: 28px;
        height: 28px;
        border-radius: var(--radius-full);
        background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-dark) 100%);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: var(--font-weight-bold);
        font-size: 11px;
        flex-shrink: 0;
        box-shadow: 0 0 0 2px color-mix(in srgb, var(--row-role-color, transparent) 35%, transparent);
    }

    .member-row-info {
        flex: 1;
        min-width: 0;
        display: flex;
        align-items: center;
        gap: var(--space-2);
        flex-wrap: wrap;
    }

    .member-row-name {
        font-size: var(--font-size-sm);
        font-weight: var(--font-weight-medium);
        color: var(--color-text-primary);
        white-space: nowrap;
    }

    .member-row-instrument {
        font-size: var(--font-size-xs);
        color: var(--color-text-muted);
        white-space: nowrap;
    }

    .member-row-labels {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        margin-left: auto;
    }

    .member-row-labels .role-tag {
        font-size: 10px;
        padding: 1px 6px;
        line-height: 16px;
    }

    .member-row-labels .user-badge {
        width: 18px;
        height: 16px;
        font-size: 9px;
        margin-left: 0;
    }


    .popover-edit:hover {
        border-color: var(--color-primary);
        color: var(--color-primary);
        background: var(--color-primary-50);
    }

    /* === ADMIN SIDEBAR === */
    .members-admin-panel {
        position: sticky;
        top: calc(var(--navbar-height) + var(--space-4));
        align-self: start;
        max-height: calc(100vh - var(--navbar-height) - var(--space-8));
        overflow-y: auto;
    }

    .admin-section {
        background: var(--color-white);
        border: 1px solid var(--color-border);
        border-radius: var(--radius-xl);
        box-shadow: var(--shadow-md);
        overflow: hidden;
        margin-bottom: var(--space-4);
    }

    .admin-section-header {
        padding: var(--space-3) var(--space-4);
        background: linear-gradient(135deg, var(--color-bg-secondary) 0%, var(--color-bg-primary) 100%);
        border-bottom: 1px solid var(--color-border-light);
        font-size: var(--font-size-xs);
        font-weight: var(--font-weight-bold);
        color: var(--color-text-secondary);
        text-transform: uppercase;
        letter-spacing: 0.06em;
    }

    .admin-section-body {
        padding: var(--space-3);
    }

    /* Role rows in sidebar */
    .role-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: var(--space-2) 0;
        border-bottom: 1px solid var(--color-border-light);
    }

    .role-row:last-child {
        border-bottom: none;
    }

    .role-row-info {
        display: flex;
        align-items: center;
        gap: var(--space-2);
        min-width: 0;
        flex: 1;
    }

    .role-row-meta {
        font-size: 10px;
        color: var(--color-text-muted);
        line-height: 1;
        align-self: center;
    }

    .role-row-actions {
        display: flex;
        gap: 2px;
    }

    .role-row-actions button {
        width: 24px;
        height: 24px;
        border: none;
        background: none;
        cursor: pointer;
        border-radius: var(--radius-full);
        color: var(--color-text-muted);
        transition: all var(--transition-base);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 10px;
    }

    .role-row-actions button:hover {
        background: var(--color-bg-tertiary);
        color: var(--color-text-primary);
    }

    .role-row-actions button.role-delete-btn:hover {
        background: var(--color-error-50);
        color: var(--color-error);
    }

    .role-filter-tag {
        transition: opacity 0.15s;
    }

    .role-filter-tag.active-filter .role-tag {
        outline: 2px solid currentColor;
        outline-offset: 1px;
    }

    .role-filter-tag:hover {
        opacity: 0.8;
    }

    .admin-btn-new {
        width: 100%;
        padding: var(--space-2);
        border: 1px dashed var(--color-border);
        border-radius: var(--radius-base);
        background: none;
        color: var(--color-text-muted);
        font-size: var(--font-size-xs);
        cursor: pointer;
        transition: all var(--transition-base);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: var(--space-1);
        margin-top: var(--space-2);
    }

    .admin-btn-new:hover {
        border-color: var(--color-primary);
        color: var(--color-primary);
        background: var(--color-primary-50);
    }

    /* Invite section */
    .invite-link-box {
        background: var(--color-bg-tertiary);
        border-radius: var(--radius-base);
        padding: var(--space-2);
        font-family: var(--font-family-mono);
        font-size: 10px;
        word-break: break-all;
        color: var(--color-text-primary);
        border: 1px solid var(--color-border-light);
        line-height: 1.4;
        margin-bottom: var(--space-2);
    }

    .invite-meta {
        font-size: 10px;
        color: var(--color-text-muted);
        margin-bottom: var(--space-2);
    }


    /* No results state */
    .members-no-results {
        text-align: center;
        padding: var(--space-8) var(--space-4);
        color: var(--color-text-muted);
    }

    .members-no-results i {
        font-size: var(--font-size-2xl);
        margin-bottom: var(--space-2);
        display: block;
    }
</style>

<?php
$groupManager = \App\Core\GroupManager::getInstance();
$totalMembers = 0;
foreach ($grouped as $members) {
    $totalMembers += count($members);
}

// Map instrument → parent section
$sectionOrder = [];
foreach ($sections as $groupName => $groupSections) {
    foreach ($groupSections as $s) {
        $sectionOrder[$s] = $groupName;
    }
    // Section ID itself maps to its own group
    if ($groupName !== '') {
        $sectionOrder[$groupName] = $groupName;
    }
}

$ungrouped = [];
foreach ($grouped as $sectionName => $members) {
    if (isset($sectionOrder[$sectionName])) {
        $groupName = $sectionOrder[$sectionName];
    } elseif (isset($sections[$sectionName])) {
        $groupName = $sectionName;
    } else {
        $groupName = 'Sonstige';
    }
    $ungrouped[$groupName][$sectionName] = $members;
}

// Order sections per config
$configOrder = array_keys($sections);
$displayGroups = [];
foreach ($configOrder as $groupName) {
    if (isset($ungrouped[$groupName])) {
        $displayGroups[$groupName] = $ungrouped[$groupName];
        unset($ungrouped[$groupName]);
    }
}
foreach ($ungrouped as $groupName => $sectionMembers) {
    $displayGroups[$groupName] = $sectionMembers;
}

// Section visual metadata
$defaultMeta = ['emoji' => '🎶', 'bg' => 'background: var(--color-bg-tertiary);', 'tc' => 'color: var(--color-text-secondary);', 'accent' => 'var(--color-primary)'];
$sectionMeta = [];
foreach ($sections as $sectionId => $_instruments) {
    $group = $groupManager->getGroup($sectionId);
    if ($group) {
        $tc = $group['tc'] ?? $defaultMeta['tc'];
        preg_match('/color:\s*([^;]+)/', $tc, $m);
        $accent = trim($m[1] ?? 'var(--color-primary)');
        $bg = $group['bg'] ?? $defaultMeta['bg'];
        // Extract raw bg color for gradient icon
        preg_match('/background:\s*([^;]+)/', $bg, $bm);
        $sectionMeta[$sectionId] = [
            'emoji'  => $group['emoji'] ?? $defaultMeta['emoji'],
            'bg'     => $bg,
            'tc'     => $tc,
            'accent' => $accent,
        ];
    }
}

$isAdmin = $canManage || !empty($canManagePermissions);
?>

<div class="container-app">
    <!-- Toolbar -->
    <div class="members-toolbar">
        <div class="members-count">
            <?= $totalMembers ?> <span>Mitglieder</span>
        </div>

        <div class="toolbar-actions">
            <div class="members-search-wrapper search-mobile">
                <i class="fas fa-search members-search-icon"></i>
                <input type="text"
                    class="members-search"
                    placeholder="Mitglied suchen..."
                    oninput="filterMembers(this.value)">
            </div>

            <?php if (!empty($canManagePermissions)): ?>
                <button class="btn-modern btn-secondary toolbar-btn-mobile" onclick="openRolesModal()" style="white-space: nowrap; padding: var(--space-2) var(--space-4); font-size: var(--font-size-sm);">
                    <i class="fas fa-shield-alt" style="margin-right: var(--space-1);"></i> Rollen
                </button>
            <?php endif; ?>
            <?php if ($canManage): ?>
                <button class="btn-modern btn-primary toolbar-btn-mobile" onclick="openInviteModal()" style="white-space: nowrap; padding: var(--space-2) var(--space-4); font-size: var(--font-size-sm);">
                    <i class="fas fa-link" style="margin-right: var(--space-1);"></i> Einladen
                </button>
            <?php endif; ?>
        </div>
    </div>

    <?php if (empty($displayGroups)): ?>
        <?php
        $title = 'Keine Mitglieder';
        $message = 'Es gibt noch keine Mitglieder in diesem Ensemble.';
        include __DIR__ . '/../components/empty-state.php';
        ?>
    <?php else: ?>
        <div class="members-layout<?= $isAdmin ? ' has-sidebar' : '' ?>">
            <!-- ═══ ROSTER ═══ -->
            <div class="members-roster">
                <?php $chipIndex = 0; ?>
                <?php foreach ($displayGroups as $groupName => $sectionMembers): ?>
                    <?php
                    $meta = $sectionMeta[$groupName] ?? $defaultMeta;
                    $groupMemberCount = 0;
                    foreach ($sectionMembers as $members) {
                        $groupMemberCount += count($members);
                    }
                    ?>
                    <div class="section-band" data-group style="--section-accent: <?= $meta['accent'] ?>">
                        <div class="section-band-header">
                            <div class="section-band-icon" style="background: linear-gradient(135deg, <?= $meta['accent'] ?>, color-mix(in srgb, <?= $meta['accent'] ?> 70%, #000));">
                                <span><?= $meta['emoji'] ?></span>
                            </div>
                            <span class="section-band-name"><?= htmlspecialchars($groupManager->getDisplayName($groupName)) ?></span>
                            <span class="section-band-count"><?= $groupMemberCount ?></span>
                        </div>

                        <div class="member-list">
                            <?php $rowIndex = 0; ?>
                            <?php foreach ($sectionMembers as $sectionName => $members): ?>
                                <?php foreach ($members as $member):
                                    $displayName = $member['display_name'] ?? $member['email'];
                                    $initial = strtoupper(substr($displayName, 0, 1));
                                    $roleColor = $member['role_tag_color'] ?? '';
                                    $userLabels = \App\Core\Utilities::generateUserLabels($member);
                                    $instrumentName = (count($sectionMembers) > 1) ? $groupManager->getDisplayName($sectionName) : '';
                                    $memberRoleIds = [];
                                    if (!empty($member['roles'])) {
                                        $memberRoleIds = array_map(fn($r) => (int)$r['id'], $member['roles']);
                                    }
                                ?>
                                    <div class="member-row"
                                        data-member-name="<?= htmlspecialchars(strtolower($displayName)) ?>"
                                        data-user-id="<?= (int)$member['user_id'] ?>"
                                        data-role-ids="<?= htmlspecialchars(implode(',', $memberRoleIds)) ?>"
                                        style="--row-i: <?= $rowIndex ?>; --row-role-color: <?= $roleColor ?: 'transparent' ?>"
                                        onclick="openEditModal(<?= (int)$member['user_id'] ?>)">
                                        <div class="member-row-avatar"><?= $initial ?></div>
                                        <div class="member-row-info">
                                            <span class="member-row-name"><?= htmlspecialchars($displayName) ?></span>
                                            <?php if ($instrumentName): ?>
                                                <span class="member-row-instrument"><?= htmlspecialchars($instrumentName) ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($userLabels): ?>
                                            <span class="member-row-labels"><?= $userLabels ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <?php $rowIndex++; ?>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

                <!-- No search results -->
                <div class="members-no-results" id="noResults" style="display:none;">
                    <i class="fas fa-search"></i>
                    <div>Kein Mitglied gefunden</div>
                </div>
            </div>

            <!-- ═══ ADMIN SIDEBAR ═══ -->
            <?php if ($isAdmin): ?>
                <div class="members-admin-panel">
                    <!-- Search -->
                    <div class="members-search-wrapper" style="margin-bottom: var(--space-4);">
                        <i class="fas fa-search members-search-icon"></i>
                        <input type="text"
                            class="members-search"
                            placeholder="Mitglied suchen..."
                            oninput="filterMembers(this.value)">
                    </div>
                    <?php if (!empty($canManagePermissions)): ?>
                        <div class="admin-section">
                            <div class="admin-section-header"><i class="fas fa-shield-alt" style="margin-right: var(--space-1);"></i> Rollen</div>
                            <div class="admin-section-body">
                                <?php foreach ($roles as $role): ?>
                                    <div class="role-row">
                                        <div class="role-row-info">
                                            <span class="role-filter-tag" data-role-id="<?= (int)$role['id'] ?>" onclick="event.stopPropagation(); toggleRoleFilter(<?= (int)$role['id'] ?>)" title="Klicken zum Filtern" style="cursor:pointer">
                                                <?= \App\Core\Utilities::renderRoleTag($role) ?>
                                            </span>
                                            <span class="role-row-meta"><?= (int)$role['user_count'] ?></span>
                                        </div>
                                        <?php if (empty($role['is_system']) && empty($role['is_default'])): ?>
                                            <div class="role-row-actions">
                                                <button onclick="editRole(<?= (int)$role['id'] ?>, <?= htmlspecialchars(json_encode($role), ENT_QUOTES) ?>)" title="Bearbeiten"><i class="fas fa-pen"></i></button>
                                                <button class="role-delete-btn" onclick="deleteRole(<?= (int)$role['id'] ?>, '<?= htmlspecialchars($role['name'], ENT_QUOTES) ?>', <?= (int)$role['user_count'] ?>)" title="Löschen"><i class="fas fa-trash"></i></button>
                                            </div>
                                        <?php elseif (empty($role['is_system'])): ?>
                                            <div class="role-row-actions">
                                                <button onclick="editRole(<?= (int)$role['id'] ?>, <?= htmlspecialchars(json_encode($role), ENT_QUOTES) ?>)" title="Bearbeiten"><i class="fas fa-pen"></i></button>
                                            </div>
                                        <?php else: ?>
                                            <div class="role-row-actions" style="width: 24px; justify-content: center;">
                                                <span style="font-size: 10px; color: var(--color-text-muted);"><i class="fas fa-lock"></i></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                                <button class="admin-btn-new" onclick="createRole()"><i class="fas fa-plus"></i> Neue Rolle</button>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($canManage): ?>
                        <div class="admin-section">
                            <div class="admin-section-header"><i class="fas fa-link" style="margin-right: var(--space-1);"></i> Einladen</div>
                            <div class="admin-section-body">
                                <?php if ($inviteLink): ?>
                                    <?php $linkUrl = rtrim($_SERVER['HTTP_HOST'] ?? '', '/') . '/invite/' . $inviteLink['token']; ?>
                                    <div class="invite-link-box"><?= htmlspecialchars('https://' . $linkUrl) ?></div>
                                    <div class="invite-meta"><?= (int)($inviteLink['used_count'] ?? 0) ?>× verwendet</div>
                                    <button class="btn-base btn-sm btn-primary" style="width: 100%;" onclick="copyInviteLink()"><i class="fas fa-copy" style="margin-right: var(--space-1);"></i> Link kopieren</button>
                                    <div class="swal-perm-row" style="margin-top: var(--space-2);">
                                        <input type="checkbox" id="sidebarKeycloak" <?= !empty($inviteLink['keycloak_only']) ? 'checked' : '' ?> onchange="toggleKeycloak()">
                                        <label for="sidebarKeycloak" style="font-size: var(--font-size-xs);">Nur JMD-Accounts</label>
                                    </div>
                                <?php else: ?>
                                    <p style="font-size: var(--font-size-xs); color: var(--color-text-muted); margin-bottom: var(--space-2);">Noch kein Link vorhanden.</p>
                                    <button class="admin-btn-new" onclick="regenerateLink()"><i class="fas fa-plus"></i> Link erstellen</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php if (!empty($canManagePermissions)): ?>
    <script>
        let rolesData = <?= json_encode(array_map(function ($r) {
                            return [
                                'id' => (int)$r['id'],
                                'name' => $r['name'],
                                'tag_color' => $r['tag_color'],
                                'user_count' => (int)$r['user_count'],
                                'is_default' => !empty($r['is_default']),
                                'is_system' => !empty($r['is_system']),
                                'permissions' => $r['permissions'] ?? [],
                            ];
                        }, $roles)) ?>;
    </script>
<?php endif; ?>

<script>
    const orchestraBase = '<?= htmlspecialchars(($_SESSION['current_org_slug'] ?? '') . '/' . ($_SESSION['current_orchestra_slug'] ?? '')) ?>';
    const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.content || '';

    const activeRoleFilters = new Set();

    function filterMembers(query) {
        query = query.toLowerCase().trim();
        let visibleCount = 0;

        document.querySelectorAll('.member-row').forEach(row => {
            const nameMatch = !query || row.dataset.memberName.includes(query);
            const memberRoles = (row.dataset.roleIds || '').split(',');
            const roleMatch = activeRoleFilters.size === 0 || [...activeRoleFilters].every(id => memberRoles.includes(String(id)));
            const match = nameMatch && roleMatch;
            row.style.display = match ? '' : 'none';
            if (match) visibleCount++;
        });

        document.querySelectorAll('.section-band[data-group]').forEach(band => {
            const hasVisible = [...band.querySelectorAll('.member-row')].some(r => r.style.display !== 'none');
            band.style.display = hasVisible || (!query && activeRoleFilters.size === 0) ? '' : 'none';
        });

        document.getElementById('noResults').style.display = visibleCount === 0 && (query || activeRoleFilters.size > 0) ? '' : 'none';
    }

    function toggleRoleFilter(roleId) {
        if (activeRoleFilters.has(roleId)) {
            activeRoleFilters.delete(roleId);
        } else {
            activeRoleFilters.add(roleId);
        }
        document.querySelectorAll('.role-filter-tag').forEach(el => {
            el.classList.toggle('active-filter', activeRoleFilters.has(Number(el.dataset.roleId)));
        });
        filterMembers(document.querySelector('.members-search')?.value || '');
    }

    // Partial re-fetch: swap roster + sidebar without full reload
    function refreshMembersPage() {
        fetch(location.href, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(r => r.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');

                const newRoster = doc.querySelector('.members-roster');
                const oldRoster = document.querySelector('.members-roster');
                if (newRoster && oldRoster) oldRoster.innerHTML = newRoster.innerHTML;

                const newPanel = doc.querySelector('.members-admin-panel');
                const oldPanel = document.querySelector('.members-admin-panel');
                if (newPanel && oldPanel) oldPanel.innerHTML = newPanel.innerHTML;

                const newCount = doc.querySelector('.members-count');
                const oldCount = document.querySelector('.members-count');
                if (newCount && oldCount) oldCount.innerHTML = newCount.innerHTML;

                // Re-apply rolesData from the new page
                const scriptTag = doc.querySelector('script:not([src])');
                if (scriptTag && scriptTag.textContent.includes('rolesData')) {
                    const match = scriptTag.textContent.match(/let rolesData = (\[[\s\S]*?\]);/);
                    if (match) {
                        try {
                            rolesData = JSON.parse(match[1]);
                        } catch (e) {}
                    }
                }
            })
            .catch(err => {
                console.error('Refresh failed, falling back to reload', err);
                location.reload();
            });
    }

    // Copy invite link
    function copyInviteLink() {
        <?php if ($inviteLink ?? null): ?>
            const linkUrl = 'https://<?= htmlspecialchars(rtrim($_SERVER['HTTP_HOST'] ?? '', '/') . '/invite/' . $inviteLink['token']) ?>';
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(linkUrl)
                    .then(() => window.notifySuccess('Link kopiert'))
                    .catch(() => fallbackCopy(linkUrl));
            } else {
                fallbackCopy(linkUrl);
            }
        <?php endif; ?>
    }

    function fallbackCopy(text) {
        const ta = document.createElement('textarea');
        ta.value = text;
        ta.style.position = 'fixed';
        ta.style.opacity = '0';
        document.body.appendChild(ta);
        ta.select();
        document.execCommand('copy');
        document.body.removeChild(ta);
        window.notifySuccess('Link kopiert');
    }

    // Roles modal (mobile fallback)
    function openRolesModal() {
        if (typeof rolesData === 'undefined') return;

        let html = '<div style="text-align: left;">';
        rolesData.forEach(role => {
            const tagStyle = `--role-color: ${role.tag_color}`;
            const meta = `${role.user_count} Mitglieder${role.is_default ? ' · Standard' : ''}`;
            const actions = role.is_system ?
                `<div class="role-row-actions" style="width: 24px; justify-content: center;"><span style="font-size: var(--font-size-xs); color: var(--color-text-muted);"><i class="fas fa-lock"></i></span></div>` :
                role.is_default ?
                `<div class="role-row-actions">
                    <button onclick="Swal.close(); editRole(${role.id}, ${JSON.stringify(role).replace(/"/g, '&quot;')})" title="Bearbeiten"><i class="fas fa-pen"></i></button>
                   </div>` :
                `<div class="role-row-actions">
                    <button onclick="Swal.close(); editRole(${role.id}, ${JSON.stringify(role).replace(/"/g, '&quot;')})" title="Bearbeiten"><i class="fas fa-pen"></i></button>
                    <button class="role-delete-btn" onclick="Swal.close(); deleteRole(${role.id}, '${role.name.replace(/'/g, "\\'")}', ${role.user_count})" title="Löschen"><i class="fas fa-trash"></i></button>
                   </div>`;

            html += `<div class="role-row">
                <div class="role-row-info">
                    <span class="role-tag" style="${tagStyle}">${role.name}</span>
                    <span class="role-row-meta">${meta}</span>
                </div>
                ${actions}
            </div>`;
        });
        html += '</div>';

        Swal.fire({
            title: 'Rollen verwalten',
            html,
            showCancelButton: true,
            showConfirmButton: true,
            confirmButtonText: '<i class="fas fa-plus"></i> Neue Rolle',
            cancelButtonText: 'Schließen',
            confirmButtonColor: '#478cf4',
            cancelButtonColor: '#6b7280',
            reverseButtons: true,
            focusConfirm: false,
            width: 480,
        }).then(result => {
            if (result.isConfirmed) createRole();
        });
    }

    // Invite modal (mobile fallback)
    function openInviteModal() {
        <?php if ($inviteLink): ?>
            const linkUrl = 'https://<?= htmlspecialchars(rtrim($_SERVER['HTTP_HOST'] ?? '', '/') . '/invite/' . $inviteLink['token']) ?>';
            const usedCount = <?= (int)($inviteLink['used_count'] ?? 0) ?>;
            const keycloakOnly = <?= !empty($inviteLink['keycloak_only']) ? 'true' : 'false' ?>;

            Swal.fire({
                title: 'Einladungslink',
                html: `
                    <div style="text-align: left;">
                        <p style="font-size: var(--font-size-sm); color: var(--color-text-secondary); margin-bottom: var(--space-3);">
                            Teile diesen Link mit neuen Mitgliedern.
                        </p>
                        <div class="invite-link-box" id="swalInviteLink">${linkUrl}</div>
                        <div class="invite-meta">${usedCount}× genutzt</div>
                        <div class="swal-perm-row" style="margin-top: var(--space-3);">
                            <input type="checkbox" id="swalKeycloak" ${keycloakOnly ? 'checked' : ''} onchange="toggleKeycloak()">
                            <label for="swalKeycloak">Nur JMD-Accounts erlauben</label>
                        </div>
                    </div>
                `,
                showCancelButton: true,
                showDenyButton: true,
                confirmButtonText: '<i class="fas fa-copy"></i> Link kopieren',
                denyButtonText: '<i class="fas fa-sync-alt"></i> Neuen Link',
                cancelButtonText: 'Schließen',
                confirmButtonColor: '#478cf4',
                denyButtonColor: '#6b7280',
                cancelButtonColor: '#9ca3af',
                reverseButtons: true,
                focusConfirm: false,
            }).then(result => {
                if (result.isConfirmed) {
                    navigator.clipboard.writeText(linkUrl).then(() => {
                        window.notifySuccess('Link kopiert');
                    });
                } else if (result.isDenied) {
                    regenerateLink();
                }
            });
        <?php else: ?>
            Swal.fire({
                title: 'Einladungslink',
                text: 'Noch kein Einladungslink vorhanden. Jetzt einen generieren?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Link generieren',
                cancelButtonText: 'Abbrechen',
                confirmButtonColor: '#478cf4',
                cancelButtonColor: '#6b7280',
                reverseButtons: true,
            }).then(result => {
                if (result.isConfirmed) regenerateLink();
            });
        <?php endif; ?>
    }

    function regenerateLink() {
        Swal.fire({
            title: 'Neuen Link generieren?',
            text: 'Der alte Link wird sofort ungültig.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ja, neuen Link',
            cancelButtonText: 'Abbrechen',
            confirmButtonColor: '#478cf4',
            cancelButtonColor: '#6b7280',
            reverseButtons: true,
        }).then(result => {
            if (!result.isConfirmed) return;

            fetch('/' + orchestraBase + '/invite/regenerate', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: new URLSearchParams({
                        csrf_token: csrfToken()
                    }),
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success === false) {
                        window.notifyError(data.error || 'Link-Generierung fehlgeschlagen');
                    } else {
                        window.notifySuccess('Neuer Link generiert');
                        refreshMembersPage();
                    }
                })
                .catch(err => {
                    window.notifyError('Link-Generierung fehlgeschlagen: ' + (err.message || 'Verbindung fehlgeschlagen'));
                });
        });
    }

    function toggleKeycloak() {
        fetch('/' + orchestraBase + '/invite/toggle-keycloak', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: new URLSearchParams({
                    csrf_token: csrfToken()
                }),
            })
            .then(r => r.json())
            .then(data => {
                if (data.success === false) {
                    window.notifyError(data.error || 'Einstellung fehlgeschlagen');
                } else {
                    window.notifySuccess('Gespeichert');
                }
            })
            .catch(err => {
                window.notifyError('Einstellung fehlgeschlagen: ' + (err.message || 'Verbindung fehlgeschlagen'));
            });
    }

    // ── Role CRUD ──────────────────────────────────────────────

    const permissionLabels = <?= json_encode(\App\Models\Role::PERMISSION_LABELS) ?>;
    const permissionHierarchy = <?= json_encode(\App\Models\Role::PERMISSION_HIERARCHY) ?>;

    function buildPermissionCheckboxes(selected = []) {
        function renderNode(node, depth = 0) {
            const key = node.id;
            const label = permissionLabels[key] || key;
            const checked = selected.includes(key) ? 'checked' : '';
            const indent = depth > 0 ? `padding-left: ${depth * 24}px;` : '';
            let html = `<div class="swal-perm-row" style="${indent}">
                <input type="checkbox" id="rp_${key}" value="${key}" ${checked} data-depth="${depth}">
                <label for="rp_${key}">${label}</label>
            </div>`;
            if (node.children) {
                node.children.forEach(child => {
                    html += renderNode(child, depth + 1);
                });
            }
            return html;
        }
        return permissionHierarchy.map(node => renderNode(node)).join('');
    }

    function initPermissionHierarchy() {
        function getParentMap(nodes, parent) {
            const map = {};
            nodes.forEach(n => {
                if (parent) map[n.id] = parent;
                if (n.children) Object.assign(map, getParentMap(n.children, n.id));
            });
            return map;
        }

        function getChildIds(nodes, targetId) {
            for (const n of nodes) {
                if (n.id === targetId) return collectAll(n.children || []);
                if (n.children) {
                    const r = getChildIds(n.children, targetId);
                    if (r) return r;
                }
            }
            return null;
        }

        function collectAll(nodes) {
            return nodes.flatMap(n => [n.id, ...collectAll(n.children || [])]);
        }

        const parentMap = getParentMap(permissionHierarchy, null);
        document.querySelectorAll('[id^="rp_"]').forEach(cb => {
            cb.addEventListener('change', () => {
                const id = cb.value;
                if (cb.checked) {
                    let pid = parentMap[id];
                    while (pid) {
                        const pel = document.getElementById('rp_' + pid);
                        if (pel) pel.checked = true;
                        pid = parentMap[pid];
                    }
                } else {
                    const kids = getChildIds(permissionHierarchy, id) || [];
                    kids.forEach(kid => {
                        const el = document.getElementById('rp_' + kid);
                        if (el) el.checked = false;
                    });
                }
            });
        });
    }

    function showRoleForm(title, confirmText, defaults = {}) {
        const isDefault = defaults.is_default ? 'checked' : '';
        const isSelfAssignable = defaults.is_self_assignable ? 'checked' : '';
        const previewName = defaults.name || 'Vorschau';
        const previewColor = defaults.tag_color || '#478cf4';
        return Swal.fire({
            title,
            html: `
                <div class="swal-members-permissions">
                    <div class="swal-field-group">
                        <label class="swal-field-label">Name</label>
                        <input type="text" id="swalRoleName" class="swal-select-modern" value="${defaults.name || ''}" placeholder="z.B. Registerleitung">
                    </div>
                    <div class="swal-role-section">
                        <label class="swal-field-label">Farbe</label>
                        <div class="swal-role-color-row">
                            <input type="range" id="swalRoleHue" class="swal-role-hue-slider" min="0" max="360" value="0">
                        </div>
                        <div class="swal-role-preview" style="margin-top: var(--space-2);">
                            <span id="swalRolePreviewTag" class="role-tag" style="--role-color: ${previewColor}">${previewName}</span>
                        </div>
                        <input type="hidden" id="swalRoleColor" value="${previewColor}">
                    </div>
                    <div class="swal-field-group">
                        <div class="swal-role-toggles">
                            <label class="swal-role-toggle">
                                <input type="checkbox" id="swalRoleDefault" ${isDefault}>
                                <i class="fas fa-star"></i> Standardrolle
                            </label>
                            <label class="swal-role-toggle">
                                <input type="checkbox" id="swalRoleSelfAssignable" ${isSelfAssignable}>
                                <i class="fas fa-user-plus"></i> Selbstzuweisung
                            </label>
                        </div>
                    </div>
                    <div class="swal-perm-group">
                        <div class="swal-perm-title">Berechtigungen</div>
                        ${buildPermissionCheckboxes(defaults.permissions || [])}
                    </div>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: confirmText,
            cancelButtonText: 'Abbrechen',
            confirmButtonColor: '#478cf4',
            cancelButtonColor: '#6b7280',
            reverseButtons: true,
            focusConfirm: false,
            width: 480,
            didOpen: () => {
                initPermissionHierarchy();
                const hueSlider = document.getElementById('swalRoleHue');
                const colorHidden = document.getElementById('swalRoleColor');
                const nameInput = document.getElementById('swalRoleName');
                const previewTag = document.getElementById('swalRolePreviewTag');

                function hslToHex(h, s, l) {
                    l /= 100;
                    s /= 100;
                    const a = s * Math.min(l, 1 - l);
                    const f = n => {
                        const k = (n + h / 30) % 12;
                        return Math.round(255 * (l - a * Math.max(-1, Math.min(k - 3, 9 - k, 1))));
                    };
                    return '#' + [f(0), f(8), f(4)].map(x => x.toString(16).padStart(2, '0')).join('');
                }

                function hexToHue(hex) {
                    const r = parseInt(hex.slice(1, 3), 16) / 255,
                        g = parseInt(hex.slice(3, 5), 16) / 255,
                        b = parseInt(hex.slice(5, 7), 16) / 255;
                    const max = Math.max(r, g, b),
                        min = Math.min(r, g, b),
                        d = max - min;
                    if (d === 0) return 0;
                    let h = 0;
                    if (max === r) h = ((g - b) / d + 6) % 6;
                    else if (max === g) h = (b - r) / d + 2;
                    else h = (r - g) / d + 4;
                    return Math.round(h * 60);
                }

                hueSlider.value = hexToHue(colorHidden.value);

                function updatePreview() {
                    const hex = hslToHex(parseInt(hueSlider.value), 55, 55);
                    colorHidden.value = hex;
                    const n = nameInput.value.trim() || 'Vorschau';
                    previewTag.style.setProperty('--role-color', hex);
                    previewTag.textContent = n;
                }
                hueSlider.addEventListener('input', updatePreview);
                nameInput.addEventListener('input', () => {
                    previewTag.textContent = nameInput.value.trim() || 'Vorschau';
                });
            },
            preConfirm: () => {
                const name = document.getElementById('swalRoleName').value.trim();
                if (!name) {
                    Swal.showValidationMessage('Name darf nicht leer sein.');
                    return false;
                }
                const perms = [];
                document.querySelectorAll('[id^="rp_"]:checked').forEach(el => perms.push(el.value));
                return {
                    name,
                    tag_color: document.getElementById('swalRoleColor').value,
                    permissions: perms,
                    is_default: document.getElementById('swalRoleDefault').checked,
                    is_self_assignable: document.getElementById('swalRoleSelfAssignable').checked,
                };
            },
        });
    }

    function createRole() {
        showRoleForm('Neue Rolle', '<i class="fas fa-plus"></i> Erstellen').then(result => {
            if (!result.isConfirmed) return;
            const d = result.value;
            fetch('/' + orchestraBase + '/roles/create', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: new URLSearchParams({
                        csrf_token: csrfToken(),
                        name: d.name,
                        tag_color: d.tag_color,
                        permissions: JSON.stringify(d.permissions),
                        is_self_assignable: d.is_self_assignable ? '1' : '0',
                    }),
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        window.notifySuccess('Rolle erstellt');
                        refreshMembersPage();
                    } else {
                        window.notifyError(data.error || 'Rolle konnte nicht erstellt werden');
                    }
                })
                .catch(err => window.notifyError('Fehler: ' + (err.message || 'Verbindung fehlgeschlagen')));
        });
    }

    function editRole(roleId, role) {
        showRoleForm('Rolle bearbeiten', '<i class="fas fa-save"></i> Speichern', role).then(result => {
            if (!result.isConfirmed) return;
            const d = result.value;
            const params = new URLSearchParams({
                csrf_token: csrfToken(),
                name: d.name,
                tag_color: d.tag_color,
                permissions: JSON.stringify(d.permissions),
                is_self_assignable: d.is_self_assignable ? '1' : '0',
            });
            params.set('is_default', d.is_default ? '1' : '0');
            fetch('/' + orchestraBase + '/roles/' + roleId + '/update', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: params,
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        window.notifySuccess('Rolle aktualisiert');
                        refreshMembersPage();
                    } else {
                        window.notifyError(data.error || 'Rolle konnte nicht aktualisiert werden');
                    }
                })
                .catch(err => window.notifyError('Fehler: ' + (err.message || 'Verbindung fehlgeschlagen')));
        });
    }

    function deleteRole(roleId, roleName, userCount) {
        Swal.fire({
            title: 'Rolle löschen?',
            html: `<p>Die Rolle <strong>${roleName}</strong> wird gelöscht. Diese Aktion kann nicht rückgängig gemacht werden.</p>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ja, löschen',
            cancelButtonText: 'Abbrechen',
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6b7280',
            reverseButtons: true,
        }).then(result => {
            if (!result.isConfirmed) return;
            fetch('/' + orchestraBase + '/roles/' + roleId + '/delete', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: new URLSearchParams({
                        csrf_token: csrfToken()
                    }),
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        window.notifySuccess('Rolle gelöscht');
                        refreshMembersPage();
                    } else {
                        window.notifyError(data.error || 'Rolle konnte nicht gelöscht werden');
                    }
                })
                .catch(err => window.notifyError('Fehler: ' + (err.message || 'Verbindung fehlgeschlagen')));
        });
    }
</script>