<?php
/**
 * Orchestra Module Data Provider
 */

// Initialize models
$orgModel = new \App\Models\Organization();
$orchestraModel = new \App\Models\Orchestra();
$userModel = new \App\Models\User();
$userOrchestraModel = new \App\Models\UserOrchestra();

$orgId = isset($_GET['org_id']) ? (int)$_GET['org_id'] : null;
$orchestraId = isset($_GET['orchestra_id']) ? (int)$_GET['orchestra_id'] : null;
$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;

$data = [
    'organizations' => [],
    'orchestras' => [],
    'users' => [],
    'current_org' => null,
    'current_orchestra' => null,
    'current_user' => null,
    'user_permissions' => [],
    'all_permissions' => [],
    'user_relation' => null
];

// Fetch all organizations
try {
    $data['organizations'] = $orgModel->findAll('name');
} catch (\Exception $e) {
    // If table doesn't exist or other err
}

if ($orgId) {
    $currentOrg = $orgModel->findById($orgId);
    if ($currentOrg) {
        $data['current_org'] = $currentOrg;
        $data['orchestras'] = $orgModel->getEnsembles($orgId);
    }
}

if ($orchestraId) {
    $currentOrchestra = $orchestraModel->findById($orchestraId);
    if ($currentOrchestra) {
        $data['current_orchestra'] = $currentOrchestra;
        $data['users'] = $userOrchestraModel->getOrchestraUsers($orchestraId, false); // Get all, even inactive
    }
}

if ($userId && $orchestraId) {
    $currentUser = $userModel->findById($userId);
    if ($currentUser) {
        $data['current_user'] = $currentUser;
        $data['user_permissions'] = $userOrchestraModel->getPermissions($userId, $orchestraId);
        $data['all_permissions'] = $userOrchestraModel->getAllPermissionNames('ensemble');
        $data['user_relation'] = $userOrchestraModel->getUserOrchestraRelation($userId, $orchestraId, false);
    }
}

return $data;
