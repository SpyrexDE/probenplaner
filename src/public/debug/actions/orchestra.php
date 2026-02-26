<?php
/**
 * Orchestra Module Actions
 */

$action = $_POST['action'] ?? '';
$message = '';
$messageType = 'success';
$data = [];

$userOrchestraModel = new \App\Models\UserOrchestra();

try {
    switch ($action) {
        case 'update_permissions':
            $userId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
            $orchestraId = isset($_POST['orchestra_id']) ? (int)$_POST['orchestra_id'] : 0;
            $permissions = isset($_POST['permissions']) ? $_POST['permissions'] : [];
            
            // Ensure permissions is an array of strings
            $permissions = array_filter(array_map('strval', $permissions));
            
            if (!$userId || !$orchestraId) {
                throw new \Exception("User ID or Orchestra ID missing.");
            }
            
            // Validate if user exists in the orchestra
            $relation = $userOrchestraModel->getUserOrchestraRelation($userId, $orchestraId, false);
            if (!$relation) {
                throw new \Exception("User is not a member of this orchestra.");
            }
            
            // Set permissions
            $success = $userOrchestraModel->setPermissions($userId, $orchestraId, $permissions);
            
            if ($success) {
                $message = "Successfully updated permissions for user #$userId in orchestra #$orchestraId. Assigned " . count($permissions) . " permissions.";
            } else {
                throw new \Exception("Failed to update permissions.");
            }
            break;
            
        case 'toggle_active':
            $userId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
            $orchestraId = isset($_POST['orchestra_id']) ? (int)$_POST['orchestra_id'] : 0;
            $isActive = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 0;
            
            if (!$userId || !$orchestraId) {
                throw new \Exception("User ID or Orchestra ID missing.");
            }
            
            // Need to update the active status via raw query or model update if it doesn't support it directly
            $relation = $userOrchestraModel->getUserOrchestraRelation($userId, $orchestraId, false);
            if (!$relation) {
                throw new \Exception("User is not a member of this orchestra.");
            }
            
            $db = \App\Core\Database::getInstance();
            $stmt = $db->getConnection()->prepare("UPDATE user_orchestras SET is_active = ? WHERE user_id = ? AND orchestra_id = ?");
            $stmt->bind_param("iii", $isActive, $userId, $orchestraId);
            $stmt->execute();
            
            if ($stmt->affected_rows > 0 || $stmt->errno === 0) { // errno 0 means success even if no rows updated (same value)
                $statusStr = $isActive ? "active" : "inactive";
                $message = "Successfully marked user #$userId $statusStr in orchestra #$orchestraId.";
            } else {
                throw new \Exception("Failed to update active status. Error: " . $stmt->error);
            }
            break;

        default:
            throw new \Exception("Unknown action: " . htmlspecialchars($action));
    }
} catch (\Exception $e) {
    $message = $e->getMessage();
    $messageType = 'error';
}

return [
    'message' => $message,
    'messageType' => $messageType,
    'data' => $data
];
