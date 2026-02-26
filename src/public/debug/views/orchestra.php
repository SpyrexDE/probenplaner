<?php
/**
 * Orchestra Debug View
 */
$orgs = $moduleData['organizations'] ?? [];
$orchestras = $moduleData['orchestras'] ?? [];
$users = $moduleData['users'] ?? [];
$currentOrg = $moduleData['current_org'] ?? null;
$currentOrchestra = $moduleData['current_orchestra'] ?? null;
$currentUser = $moduleData['current_user'] ?? null;
$userPermissions = $moduleData['user_permissions'] ?? [];
$allPermissions = $moduleData['all_permissions'] ?? [];
$userRelation = $moduleData['user_relation'] ?? null;

// Build query params
$orgIdParam = isset($_GET['org_id']) ? '&org_id=' . urlencode($_GET['org_id']) : '';
$orchIdParam = isset($_GET['orchestra_id']) ? '&orchestra_id=' . urlencode($_GET['orchestra_id']) : '';
?>
<script>
async function togglePermissions(userId, orchId, orgId, element) {
    const contentId = 'permissions-content-' + userId;
    let contentDiv = document.getElementById(contentId);

    if (contentDiv) {
        const tr = contentDiv.closest('tr');
        tr.style.display = tr.style.display === 'none' ? 'table-row' : 'none';
        return;
    }

    contentDiv = document.createElement('div');
    contentDiv.id = contentId;
    contentDiv.className = 'permissions-content';
    contentDiv.innerHTML = '<div class="message info">Loading permissions...</div>';

    const row = element.closest('tr');
    const newRow = document.createElement('tr');
    const newCell = document.createElement('td');
    newCell.colSpan = 6;
    newCell.style.padding = '0';
    newCell.appendChild(contentDiv);
    newRow.appendChild(newCell);
    row.parentNode.insertBefore(newRow, row.nextSibling);

    try {
        const response = await fetch(`endpoints/get_orchestra_permissions.php?user_id=${userId}&orchestra_id=${orchId}&org_id=${orgId}`, {
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        });
        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
        const content = await response.text();
        contentDiv.innerHTML = content;
    } catch (error) {
        contentDiv.innerHTML = '<div class="message error">Failed to load permissions: ' + error.message + '</div>';
    }
}
</script>

<h2><?= $modules[$currentModule]['icon'] ?? '🎻' ?> <?= htmlspecialchars($modules[$currentModule]['name'] ?? 'Orchestra Management') ?></h2>
<p><?= htmlspecialchars($modules[$currentModule]['description'] ?? 'Manage orchestras and permissions') ?></p>

<div class="card <?php echo $currentOrg ? 'mt-4' : ''; ?>" style="margin-bottom: 20px;">
    <div class="card-header">Organizations</div>
    <div class="card-body">
        <?php if (empty($orgs)): ?>
            <p class="message warning">No organizations found.</p>
        <?php else: ?>
            <ul style="list-style: none; padding: 0; display: flex; flex-wrap: wrap; gap: 10px;">
                <?php foreach ($orgs as $org): ?>
                    <li>
                        <a href="?module=orchestra&org_id=<?= $org['id'] ?>" class="btn-base <?= $currentOrg && $currentOrg['id'] === $org['id'] ? 'btn-primary' : 'btn-secondary' ?>" style="text-decoration: none;">
                            <?= htmlspecialchars($org['name']) ?> (<?= htmlspecialchars($org['slug']) ?>)
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>

<?php if ($currentOrg): ?>
<div class="card" style="margin-bottom: 20px;">
    <div class="card-header">Orchestras in <?= htmlspecialchars($currentOrg['name']) ?></div>
    <div class="card-body">
        <?php if (empty($orchestras)): ?>
            <p class="message warning">No orchestras found for this organization.</p>
        <?php else: ?>
            <ul style="list-style: none; padding: 0; display: flex; flex-wrap: wrap; gap: 10px;">
                <?php foreach ($orchestras as $orch): ?>
                    <li>
                        <a href="?module=orchestra<?= $orgIdParam ?>&orchestra_id=<?= $orch['id'] ?>" class="btn-base <?= $currentOrchestra && $currentOrchestra['id'] === $orch['id'] ? 'btn-primary' : 'btn-secondary' ?>" style="text-decoration: none;">
                            <?= htmlspecialchars($orch['name']) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php if ($currentOrchestra): ?>
<div class="card" style="margin-bottom: 20px;">
    <div class="card-header">Users in <?= htmlspecialchars($currentOrchestra['name']) ?></div>
    <div class="card-body" style="overflow-x: auto;">
        <?php if (empty($users)): ?>
            <p class="message warning">No users found for this orchestra.</p>
        <?php else: ?>
            <table class="table table-striped" style="width: 100%; border-collapse: collapse; min-width: 600px;">
                <thead>
                    <tr>
                        <th style="padding: 8px; border-bottom: 1px solid #ddd; text-align: left;">ID</th>
                        <th style="padding: 8px; border-bottom: 1px solid #ddd; text-align: left;">Username</th>
                        <th style="padding: 8px; border-bottom: 1px solid #ddd; text-align: left;">Name</th>
                        <th style="padding: 8px; border-bottom: 1px solid #ddd; text-align: left;">Type</th>
                        <th style="padding: 8px; border-bottom: 1px solid #ddd; text-align: left;">Member Status</th>
                        <th style="padding: 8px; border-bottom: 1px solid #ddd; text-align: left;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr style="background-color: <?= $currentUser && $currentUser['id'] === $user['user_id'] ? '#eef2ff' : 'transparent' ?>;">
                        <td style="padding: 8px; border-bottom: 1px solid #ddd;"><?= $user['id'] ?></td>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd;"><?= htmlspecialchars($user['username']) ?></td>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd;"><?= htmlspecialchars($user['display_name'] ?? trim(($user['firstname'] ?? '') . ' ' . ($user['lastname'] ?? ''))) ?></td>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd;"><?= htmlspecialchars($user['type'] ?? '') ?></td>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd;">
                            <form method="post" action="?module=orchestra<?= $orgIdParam ?><?= $orchIdParam ?>&user_id=<?= $user['user_id'] ?>" style="display:inline; margin: 0;">
                                <input type="hidden" name="user_id" value="<?= $user['user_id'] ?>">
                                <input type="hidden" name="orchestra_id" value="<?= $currentOrchestra['id'] ?>">
                                <?php 
                                $isActive = isset($user['is_active']) && $user['is_active']; 
                                $newActiveVal = $isActive ? '0' : '1';
                                ?>
                                <input type="hidden" name="is_active" value="<?= $newActiveVal ?>">
                                <button type="submit" name="action" value="toggle_active" class="btn-base" style="padding: 2px 8px; font-size: 0.8em; background: <?= $isActive ? '#dc3545' : '#28a745' ?>; color: white;">
                                    <?= $isActive ? 'Remove Member' : 'Make Member' ?>
                                </button>
                            </form>
                        </td>
                        <td style="padding: 8px; border-bottom: 1px solid #ddd;">
                            <a class="btn-base <?= $currentUser && $currentUser['id'] === $user['user_id'] ? 'btn-primary' : 'btn-secondary' ?>" style="text-decoration: none; padding: 4px 8px; font-size: 0.8em; cursor: pointer;" onclick="togglePermissions(<?= $user['user_id'] ?>, <?= $currentOrchestra['id'] ?>, '<?= urlencode($_GET['org_id'] ?? '') ?>', this)">Manage Permissions</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>


