<?php $this->layout('layouts/default', ['title' => 'Mitglieder', 'currentPage' => 'members']) ?>

<?php
$renderComponent = false;
include __DIR__ . '/../components/user-badge.php';
include __DIR__ . '/../components/form-input.php';
$renderComponent = true;
?>

<style>
    /* === MEMBERS PAGE === */

    .members-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--space-3);
        margin-bottom: var(--space-6);
        flex-wrap: wrap;
    }

    .members-count {
        font-size: var(--font-size-sm);
        color: var(--color-text-secondary);
        font-weight: var(--font-weight-medium);
    }

    .members-count strong {
        color: var(--color-text-primary);
        font-weight: var(--font-weight-bold);
    }

    /* Search */
    .members-search-wrapper {
        position: relative;
        flex: 1;
        max-width: 320px;
        min-width: 200px;
    }

    .members-search-icon {
        position: absolute;
        left: var(--space-3);
        top: 50%;
        transform: translateY(-50%);
        color: var(--color-text-muted);
        font-size: var(--font-size-sm);
        pointer-events: none;
        transition: color var(--transition-base);
    }

    .members-search {
        width: 100%;
        padding: var(--space-2) var(--space-3) var(--space-2) var(--space-8);
        border: 2px solid var(--color-border);
        border-radius: var(--radius-lg);
        font-size: var(--font-size-sm);
        color: var(--color-text-primary);
        background: var(--color-bg-primary);
        transition: all var(--transition-base);
    }

    .members-search:hover {
        border-color: var(--color-primary-200);
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.06);
    }

    .members-search:focus {
        outline: none;
        border-color: var(--color-primary);
        box-shadow: 0 0 0 3px rgba(71, 140, 244, 0.1), 0 2px 6px rgba(0, 0, 0, 0.06);
        transform: translateY(-1px);
    }

    .members-search:focus~.members-search-icon {
        color: var(--color-primary);
    }

    /* Section cards */
    .section-card {
        margin-bottom: var(--space-4);
    }

    .section-card .modern-card-header {
        cursor: pointer;
        user-select: none;
        transition: background var(--transition-base);
    }

    .section-card .modern-card-header:hover {
        background: linear-gradient(135deg, var(--color-gray-100) 0%, var(--color-gray-50) 100%);
    }

    .section-header-content {
        display: flex;
        align-items: center;
        justify-content: space-between;
        width: 100%;
    }

    .section-header-left {
        display: flex;
        align-items: center;
        gap: var(--space-3);
    }

    .section-icon {
        width: 32px;
        height: 32px;
        border-radius: var(--radius-md);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .section-title {
        font-size: var(--font-size-base);
        font-weight: var(--font-weight-semibold);
        color: var(--color-text-primary);
    }

    .section-count {
        font-size: var(--font-size-xs);
        color: var(--color-text-secondary);
        font-weight: var(--font-weight-medium);
        background: var(--color-bg-tertiary);
        padding: 2px 8px;
        border-radius: var(--radius-full);
    }

    .section-chevron {
        color: var(--color-text-muted);
        font-size: var(--font-size-sm);
        transition: transform var(--transition-base);
    }

    .section-card.expanded .section-chevron {
        transform: rotate(90deg);
    }

    /* Subsection headers */
    .subsection-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: var(--space-2) var(--space-5);
        background: var(--color-bg-secondary);
        border-bottom: 1px solid var(--color-border-light);
    }

    .subsection-title {
        font-size: var(--font-size-xs);
        font-weight: var(--font-weight-bold);
        color: var(--color-text-secondary);
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .subsection-count {
        font-size: var(--font-size-xs);
        color: var(--color-text-muted);
    }

    /* Member items */
    .member-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: var(--space-3) var(--space-5);
        border-bottom: 1px solid var(--color-border-light);
        transition: all var(--transition-base);
    }

    .member-item:last-child {
        border-bottom: none;
    }

    .member-item:hover {
        background: linear-gradient(135deg, var(--color-bg-secondary), var(--color-bg-tertiary));
        transform: translateX(2px);
    }

    .member-info {
        display: flex;
        align-items: center;
        gap: var(--space-3);
        min-width: 0;
        flex: 1;
    }

    .member-avatar {
        width: 36px;
        height: 36px;
        border-radius: var(--radius-md);
        background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-dark) 100%);
        color: var(--color-white);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: var(--font-weight-bold);
        font-size: var(--font-size-sm);
        flex-shrink: 0;
        box-shadow: 0 2px 6px rgba(71, 140, 244, 0.15);
    }

    .member-details {
        min-width: 0;
    }

    .member-name {
        font-weight: var(--font-weight-semibold);
        font-size: var(--font-size-sm);
        color: var(--color-text-primary);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        line-height: 1.3;
    }

    .member-meta {
        display: flex;
        align-items: center;
        gap: var(--space-1);
        margin-top: 1px;
    }

    .member-badge {
        font-size: 10px;
        font-weight: var(--font-weight-semibold);
        padding: 1px 6px;
        border-radius: var(--radius-sm);
        letter-spacing: 0.02em;
        text-transform: uppercase;
    }

    .member-badge-small-group {
        background: var(--color-warning-100);
        color: var(--color-warning-dark);
    }

    /* Role tag pills */
    .role-tag {
        display: inline-flex;
        align-items: center;
        font-size: 10px;
        font-weight: var(--font-weight-bold);
        padding: 2px 8px;
        border-radius: var(--radius-full);
        letter-spacing: 0.02em;
        text-transform: uppercase;
        background: color-mix(in srgb, var(--role-color) 15%, transparent);
        color: var(--role-color);
        border: 1px solid color-mix(in srgb, var(--role-color) 30%, transparent);
        line-height: 1.4;
    }

    /* Role management panel */
    .role-manager {
        margin-top: var(--space-6);
    }

    .role-list-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: var(--space-3) var(--space-5);
        border-bottom: 1px solid var(--color-border-light);
        transition: background var(--transition-base);
    }

    .role-list-item:last-child {
        border-bottom: none;
    }

    .role-list-item:hover {
        background: var(--color-bg-secondary);
    }

    .role-list-info {
        display: flex;
        align-items: center;
        gap: var(--space-3);
        min-width: 0;
    }

    .role-list-meta {
        font-size: var(--font-size-xs);
        color: var(--color-text-muted);
    }

    .role-list-actions {
        display: flex;
        gap: var(--space-1);
    }

    .role-list-actions button {
        width: 28px;
        height: 28px;
        border: none;
        background: none;
        cursor: pointer;
        border-radius: var(--radius-base);
        color: var(--color-text-muted);
        transition: all var(--transition-base);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: var(--font-size-xs);
    }

    .role-list-actions button:hover {
        background: var(--color-bg-tertiary);
        color: var(--color-text-primary);
    }

    .role-list-actions button.role-delete-btn:hover {
        background: var(--color-error-50);
        color: var(--color-error);
    }

    .member-edit-btn {
        width: 32px;
        height: 32px;
        background: none;
        border: 1px solid transparent;
        cursor: pointer;
        color: var(--color-text-muted);
        border-radius: var(--radius-base);
        transition: all var(--transition-base);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .member-edit-btn:hover {
        color: var(--color-primary);
        background: var(--color-primary-50);
        border-color: var(--color-primary-200);
        transform: scale(1.05);
    }

    .member-edit-btn:active {
        transform: scale(0.95);
    }

    /* Section body collapse */
    .section-body {
        max-height: 2000px;
        overflow: hidden;
        transition: max-height var(--transition-slow);
    }

    .section-card:not(.expanded) .section-body {
        max-height: 0;
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

    /* SweetAlert2 members modal overrides */
    .swal-members-permissions {
        text-align: left;
    }

    .swal-perm-group {
        margin-bottom: var(--space-3);
    }

    .swal-perm-title {
        font-size: var(--font-size-xs);
        font-weight: var(--font-weight-bold);
        color: var(--color-text-secondary);
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: var(--space-2);
        padding-bottom: var(--space-1);
        border-bottom: 1px solid var(--color-border-light);
    }

    .swal-perm-row {
        display: flex;
        align-items: center;
        gap: var(--space-2);
        padding: var(--space-1) 0;
        font-size: var(--font-size-sm);
        color: var(--color-text-primary);
    }

    .swal-perm-row input[type="checkbox"] {
        width: 16px;
        height: 16px;
        accent-color: var(--color-primary);
        cursor: pointer;
    }

    .swal-perm-row label {
        cursor: pointer;
        flex: 1;
    }

    .swal-member-header {
        display: flex;
        align-items: center;
        gap: var(--space-3);
        margin-bottom: var(--space-4);
    }

    .swal-member-avatar {
        width: 48px;
        height: 48px;
        border-radius: var(--radius-lg);
        background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-dark) 100%);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: var(--font-weight-bold);
        font-size: var(--font-size-lg);
        flex-shrink: 0;
        box-shadow: 0 4px 10px rgba(71, 140, 244, 0.2);
    }

    .swal-member-info {
        text-align: left;
    }

    .swal-member-name {
        font-size: var(--font-size-lg);
        font-weight: var(--font-weight-bold);
        color: var(--color-text-primary);
    }

    .swal-member-name {
        font-size: var(--font-size-sm);
        color: var(--color-text-secondary);
    }

    .swal-select-modern {
        width: 100%;
        padding: var(--space-2) var(--space-3);
        border: 2px solid var(--color-border);
        border-radius: var(--radius-base);
        font-size: var(--font-size-sm);
        color: var(--color-text-primary);
        background: var(--color-bg-primary);
        transition: all var(--transition-base);
        cursor: pointer;
    }

    .swal-select-modern:focus {
        outline: none;
        border-color: var(--color-primary);
        box-shadow: 0 0 0 3px rgba(71, 140, 244, 0.1);
    }

    .swal-field-group {
        text-align: left;
        margin-bottom: var(--space-3);
    }

    .swal-field-label {
        display: block;
        font-size: var(--font-size-sm);
        font-weight: var(--font-weight-medium);
        color: var(--color-text-primary);
        margin-bottom: var(--space-1);
    }

    .swal-invite-link {
        background: var(--color-bg-tertiary);
        border-radius: var(--radius-base);
        padding: var(--space-3);
        font-family: var(--font-family-mono);
        font-size: var(--font-size-sm);
        word-break: break-all;
        color: var(--color-text-primary);
        border: 1px solid var(--color-border);
    }

    .swal-invite-stat {
        font-size: var(--font-size-xs);
        color: var(--color-text-muted);
        margin-top: var(--space-2);
    }
</style>

<?php
$groupManager = new \App\Core\GroupManager();
$totalMembers = 0;
foreach ($grouped as $members) {
    $totalMembers += count($members);
}

// Map instrument → top-level section using config
$sectionOrder = [];
foreach ($sections as $groupName => $groupSections) {
    foreach ($groupSections as $s) {
        $sectionOrder[$s] = $groupName;
    }
}

$ungrouped = [];
foreach ($grouped as $sectionName => $members) {
    // Skip types that aren't part of any configured instrument section
    if (!isset($sectionOrder[$sectionName]) && !isset($sections[$sectionName])) continue;
    $groupName = $sectionOrder[$sectionName] ?? $sectionName;
    $ungrouped[$groupName][$sectionName] = $members;
}

// Order sections to match orchestra_groups.php config hierarchy
$configOrder = array_keys($sections);
$displayGroups = [];
foreach ($configOrder as $groupName) {
    if (isset($ungrouped[$groupName])) {
        $displayGroups[$groupName] = $ungrouped[$groupName];
        unset($ungrouped[$groupName]);
    }
}
// Append any remaining groups not in config
foreach ($ungrouped as $groupName => $sectionMembers) {
    $displayGroups[$groupName] = $sectionMembers;
}

// Build section visual metadata dynamically from config
$defaultMeta = ['icon' => 'fas fa-users', 'bg' => 'background: var(--color-bg-tertiary);', 'tc' => 'color: var(--color-text-secondary);'];
$sectionMeta = [];
foreach ($sections as $sectionId => $_instruments) {
    $group = $groupManager->getGroup($sectionId);
    if ($group) {
        $sectionMeta[$sectionId] = [
            'icon' => $group['icon'] ?? $defaultMeta['icon'],
            'bg'   => $group['bg']   ?? $defaultMeta['bg'],
            'tc'   => $group['tc']   ?? $defaultMeta['tc'],
        ];
    }
}
?>

<div class="container-app">
    <!-- Toolbar -->
    <div class="members-toolbar">
        <div class="members-count">
            <strong><?= $totalMembers ?></strong> Mitglieder
        </div>

        <div class="flex items-center gap-3">
            <div class="members-search-wrapper">
                <i class="fas fa-search members-search-icon"></i>
                <input type="text"
                    class="members-search"
                    placeholder="Mitglied suchen..."
                    id="memberSearch"
                    oninput="filterMembers(this.value)">
            </div>

            <?php if ($canManage): ?>
                <button class="btn-modern btn-primary" onclick="openInviteModal()" style="white-space: nowrap; padding: var(--space-2) var(--space-4); font-size: var(--font-size-sm);">
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
        <!-- Section Cards -->
        <?php foreach ($displayGroups as $groupName => $sectionMembers): ?>
            <?php
            $meta = $sectionMeta[$groupName] ?? $defaultMeta;
            $groupMemberCount = 0;
            foreach ($sectionMembers as $members) {
                $groupMemberCount += count($members);
            }
            ?>
            <div class="modern-card section-card expanded mb-4" data-group>
                <div class="modern-card-header" onclick="toggleSection(this)">
                    <div class="section-header-content">
                        <div class="section-header-left">
                            <div class="section-icon" style="<?= $meta['bg'] ?>">
                                <i class="<?= $meta['icon'] ?>" style="<?= $meta['tc'] ?> font-size: var(--font-size-sm);"></i>
                            </div>
                            <div>
                                <div class="section-title"><?= htmlspecialchars($groupManager->getDisplayName($groupName)) ?></div>
                            </div>
                            <span class="section-count"><?= $groupMemberCount ?></span>
                        </div>
                        <i class="fas fa-chevron-right section-chevron"></i>
                    </div>
                </div>

                <div class="section-body">
                    <?php foreach ($sectionMembers as $sectionName => $members): ?>
                        <?php if (count($sectionMembers) > 1): ?>
                            <div class="subsection-header">
                                <span class="subsection-title"><?= htmlspecialchars($groupManager->getDisplayName($sectionName)) ?></span>
                                <span class="subsection-count"><?= count($members) ?></span>
                            </div>
                        <?php endif; ?>

                        <?php foreach ($members as $member):
                            $displayName = $member['display_name'] ?? $member['email'];
                            $initial = strtoupper(substr($displayName, 0, 1));
                            $isSmallGroup = !empty($member['is_small_group']);
                            $roleLabel = $member['role_tag_label'] ?? $member['role_name'] ?? '';
                            $roleColor = $member['role_tag_color'] ?? '#478cf4';
                        ?>
                            <div class="member-item" data-member-name="<?= htmlspecialchars(strtolower($displayName)) ?>">
                                <div class="member-info">
                                    <div class="member-avatar"><?= $initial ?></div>
                                    <div class="member-details">
                                        <div class="member-name">
                                            <?= htmlspecialchars($displayName) ?>
                                            <?php if ($roleLabel): ?>
                                                <span class="role-tag" style="--role-color: <?= htmlspecialchars($roleColor) ?>"><?= htmlspecialchars($roleLabel) ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="member-meta">
                                            <?php if ($isSmallGroup): ?>
                                                <span class="member-badge member-badge-small-group">KG</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <?php if ($canManage): ?>
                                    <button class="member-edit-btn" onclick="openEditModal(<?= (int)$member['user_id'] ?>)" title="Bearbeiten">
                                        <i class="fas fa-cog"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- No search results message -->
    <div class="members-no-results" id="noResults" style="display:none;">
        <i class="fas fa-search"></i>
        <div>Kein Mitglied gefunden</div>
    </div>

    <?php if (!empty($canManagePermissions)): ?>
        <!-- Role Management Panel -->
        <div class="role-manager">
            <div class="modern-card">
                <div class="modern-card-header">
                    <div class="section-header-content">
                        <div class="section-header-left">
                            <div class="section-icon" style="background: var(--color-primary-50);">
                                <i class="fas fa-shield-alt" style="color: var(--color-primary); font-size: var(--font-size-sm);"></i>
                            </div>
                            <div>
                                <div class="section-title">Rollen</div>
                            </div>
                            <span class="section-count"><?= count($roles) ?></span>
                        </div>
                    </div>
                </div>
                <div class="modern-card-body" style="padding: 0;">
                    <?php foreach ($roles as $role): ?>
                        <div class="role-list-item">
                            <div class="role-list-info">
                                <span class="role-tag" style="--role-color: <?= htmlspecialchars($role['tag_color']) ?>"><?= htmlspecialchars($role['name']) ?></span>
                                <span class="role-list-meta">
                                    <?= (int)$role['user_count'] ?> Mitglieder
                                    <?php if (!empty($role['is_default'])): ?>
                                        &middot; Standard
                                    <?php endif; ?>
                                </span>
                            </div>
                            <div class="role-list-actions">
                                <?php if (empty($role['is_system'])): ?>
                                    <button onclick="editRole(<?= (int)$role['id'] ?>, <?= htmlspecialchars(json_encode($role), ENT_QUOTES) ?>)" title="Bearbeiten"><i class="fas fa-pen"></i></button>
                                    <button class="role-delete-btn" onclick="deleteRole(<?= (int)$role['id'] ?>, '<?= htmlspecialchars($role['name']) ?>', <?= (int)$role['user_count'] ?>)" title="Löschen"><i class="fas fa-trash"></i></button>
                                <?php else: ?>
                                    <span style="font-size: var(--font-size-xs); color: var(--color-text-muted); padding: 0 var(--space-2);"><i class="fas fa-lock"></i></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="modern-card-footer" style="padding: var(--space-3) var(--space-5);">
                    <button class="btn-modern btn-primary" onclick="createRole()" style="width: 100%; padding: var(--space-2); font-size: var(--font-size-sm);">
                        <i class="fas fa-plus" style="margin-right: var(--space-1);"></i> Neue Rolle
                    </button>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
    const orchestraBase = '<?= htmlspecialchars(($_SESSION['current_org_slug'] ?? '') . '/' . ($_SESSION['current_orchestra_slug'] ?? '')) ?>';
    const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.content || '';

    // Section collapse
    function toggleSection(header) {
        const card = header.closest('.section-card');
        card.classList.toggle('expanded');
    }

    // Search filter
    function filterMembers(query) {
        query = query.toLowerCase().trim();
        let visibleCount = 0;

        document.querySelectorAll('[data-member-name]').forEach(el => {
            const match = !query || el.dataset.memberName.includes(query);
            el.style.display = match ? '' : 'none';
            if (match) visibleCount++;
        });

        // Hide empty section cards
        document.querySelectorAll('.section-card[data-group]').forEach(card => {
            const visibleItems = card.querySelectorAll('.member-item:not([style*="display: none"])');
            card.style.display = visibleItems.length === 0 ? 'none' : '';
        });

        // Toggle no-results
        document.getElementById('noResults').style.display = visibleCount === 0 && query ? '' : 'none';
    }



    // Invite modal
    function openInviteModal() {
        <?php if ($inviteLink): ?>
            const linkUrl = '<?= htmlspecialchars(rtrim($_SERVER['HTTP_HOST'] ?? '', '/') . '/invite/' . $inviteLink['token']) ?>';
            const usedCount = <?= (int)($inviteLink['used_count'] ?? 0) ?>;
            const keycloakOnly = <?= !empty($inviteLink['keycloak_only']) ? 'true' : 'false' ?>;

            Swal.fire({
                title: 'Einladungslink',
                html: `
                    <div style="text-align: left;">
                        <p style="font-size: var(--font-size-sm); color: var(--color-text-secondary); margin-bottom: var(--space-3);">
                            Teile diesen Link mit neuen Mitgliedern.
                        </p>
                        <div class="swal-invite-link" id="swalInviteLink">${linkUrl}</div>
                        <div class="swal-invite-stat">${usedCount}× genutzt</div>
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
                    navigator.clipboard.writeText('https://' + linkUrl).then(() => {
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
                        window.notifyErrorWithDetails('Link-Generierung fehlgeschlagen', data.debug_message || data.error || JSON.stringify(data));
                    } else {
                        window.notifySuccess('Neuer Link generiert');
                        setTimeout(() => location.reload(), 600);
                    }
                })
                .catch(err => {
                    window.notifyErrorWithDetails('Link-Generierung fehlgeschlagen', err.message || String(err));
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
                    window.notifyErrorWithDetails('Einstellung fehlgeschlagen', data.debug_message || data.error || JSON.stringify(data));
                }
            })
            .catch(err => {
                window.notifyErrorWithDetails('Einstellung fehlgeschlagen', err.message || String(err));
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

    /** Wire up parent↔child auto-check after SweetAlert renders. */
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
                    // Auto-check ancestors
                    let pid = parentMap[id];
                    while (pid) {
                        const pel = document.getElementById('rp_' + pid);
                        if (pel) pel.checked = true;
                        pid = parentMap[pid];
                    }
                } else {
                    // Auto-uncheck descendants
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
        return Swal.fire({
            title,
            html: `
                <div class="swal-members-permissions">
                    <div class="swal-field-group">
                        <label class="swal-field-label">Name</label>
                        <input type="text" id="swalRoleName" class="swal-select-modern" value="${defaults.name || ''}" placeholder="z.B. Registerleitung">
                    </div>
                    <div class="swal-field-group">
                        <label class="swal-field-label">Farbe</label>
                        <input type="color" id="swalRoleColor" value="${defaults.tag_color || '#478cf4'}" style="width: 100%; height: 36px; border: 2px solid var(--color-border); border-radius: var(--radius-base); cursor: pointer; padding: 2px;">
                    </div>
                    <div class="swal-perm-group" style="margin-top: var(--space-3);">
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
            didOpen: () => initPermissionHierarchy(),
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
                    }),
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        window.notifySuccess('Rolle erstellt');
                        setTimeout(() => location.reload(), 600);
                    } else {
                        window.notifyErrorWithDetails('Fehler', data.error || 'Unbekannter Fehler');
                    }
                })
                .catch(err => window.notifyErrorWithDetails('Fehler', err.message));
        });
    }

    function editRole(roleId, role) {
        showRoleForm('Rolle bearbeiten', '<i class="fas fa-save"></i> Speichern', role).then(result => {
            if (!result.isConfirmed) return;
            const d = result.value;
            fetch('/' + orchestraBase + '/roles/' + roleId + '/update', {
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
                    }),
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        window.notifySuccess('Rolle aktualisiert');
                        setTimeout(() => location.reload(), 600);
                    } else {
                        window.notifyErrorWithDetails('Fehler', data.error || 'Unbekannter Fehler');
                    }
                })
                .catch(err => window.notifyErrorWithDetails('Fehler', err.message));
        });
    }

    function deleteRole(roleId, roleName, userCount) {
        if (userCount > 0) {
            Swal.fire({
                title: 'Nicht möglich',
                html: `<p>Die Rolle <strong>${roleName}</strong> hat noch <strong>${userCount}</strong> zugewiesene Mitglieder. Weise diese Mitglieder zuerst einer anderen Rolle zu.</p>`,
                icon: 'warning',
                confirmButtonText: 'Verstanden',
                confirmButtonColor: '#478cf4',
            });
            return;
        }

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
                        setTimeout(() => location.reload(), 600);
                    } else {
                        window.notifyErrorWithDetails('Fehler', data.error || 'Unbekannter Fehler');
                    }
                })
                .catch(err => window.notifyErrorWithDetails('Fehler', err.message));
        });
    }
</script>