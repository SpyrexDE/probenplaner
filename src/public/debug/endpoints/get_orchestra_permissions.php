<?php
require_once '../../../bootstrap.php';

header('Content-Type: text/html; charset=utf-8');

// Ensure db connection
try {
    $db = \App\Core\Database::getInstance();
} catch (Exception $e) {
    http_response_code(500);
    echo '<div class="message error">Database connection failed.</div>';
    exit;
}

if (!isset($_GET['user_id']) || !isset($_GET['orchestra_id'])) {
    http_response_code(400);
    echo '<div class="message error">Missing required parameters.</div>';
    exit;
}

$userId = (int)$_GET['user_id'];
$orchId = (int)$_GET['orchestra_id'];
$orgIdStr = urlencode($_GET['org_id'] ?? '');

$userOrchModel = new \App\Models\UserOrchestra();
$userPermissions = $userOrchModel->getPermissions($userId, $orchId);
$allPermissions = $userOrchModel->getAllPermissionNames('ensemble');
?>
<div style="padding: 15px; margin: 0; border-top: 1px solid #ccc; background: #fff;">
    <form method="post" action="?module=orchestra&org_id=<?= $orgIdStr ?>&orchestra_id=<?= $orchId ?>" style="margin: 0;">
        <input type="hidden" name="user_id" value="<?= $userId ?>">
        <input type="hidden" name="orchestra_id" value="<?= $orchId ?>">
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 10px; margin-bottom: 15px;">
            <?php foreach ($allPermissions as $permName): ?>
            <label style="display: flex; align-items: center; gap: 8px; padding: 5px; background: #f8f9fa; border-radius: 4px; border: 1px solid #e9ecef;">
                <input type="checkbox" name="permissions[]" value="<?= htmlspecialchars($permName) ?>" <?= isset($userPermissions[$permName]) && $userPermissions[$permName] ? 'checked' : '' ?>>
                <?= htmlspecialchars($permName) ?>
            </label>
            <?php endforeach; ?>
        </div>
        <button type="submit" name="action" value="update_permissions" class="btn-base btn-primary">Save Permissions</button>
    </form>
</div>
