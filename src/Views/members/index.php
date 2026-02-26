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

    .member-badge-leader {
        background: linear-gradient(135deg, var(--color-primary-100), var(--color-primary-200));
        color: var(--color-primary-dark);
    }

    .member-badge-section-leader {
        background: linear-gradient(135deg, var(--color-success-100), var(--color-success-200));
        color: var(--color-success-dark);
    }

    .member-badge-small-group {
        background: var(--color-warning-100);
        color: var(--color-warning-dark);
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

    .swal-member-username {
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
                            $displayName = $member['display_name'] ?? $member['username'];
                            $initial = strtoupper(substr($displayName, 0, 1));
                            $isLeader = !empty($member['can_manage_ensemble']);
                            $isSectionLeader = !empty($member['can_view_own_section_stats']) && empty($member['can_manage_ensemble']);
                            $isSmallGroup = !empty($member['is_small_group']);
                        ?>
                            <div class="member-item" data-member-name="<?= htmlspecialchars(strtolower($displayName)) ?>">
                                <div class="member-info">
                                    <div class="member-avatar"><?= $initial ?></div>
                                    <div class="member-details">
                                        <div class="member-name"><?= htmlspecialchars($displayName) ?></div>
                                        <div class="member-meta">
                                            <?php if ($isLeader): ?>
                                                <span class="member-badge member-badge-leader">Leitung</span>
                                            <?php elseif ($isSectionLeader): ?>
                                                <span class="member-badge member-badge-section-leader">Reg.leitung</span>
                                            <?php endif; ?>
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
</script>