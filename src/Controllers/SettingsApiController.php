<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\FieldRegistry;
use App\Core\Validator;

/**
 * Generic API controller for field-level settings updates.
 *
 * Handles: POST /{orchestra_id}/api/settings/{entity}/{id}
 */
class SettingsApiController extends Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Update one or more fields on an entity.
     *
     * Expects JSON body: { "field": "value" } or { "fields": { "field1": "v1", ... } }
     */
    public function update(array $params = []): void
    {
        $context = $this->validateOrchestraContext($params);
        if (!$context) {
            $this->json(['success' => false, 'error' => 'Kein Orchesterkontext'], 403);
            return;
        }

        $entity   = $params['entity'] ?? '';
        $entityId = isset($params['entity_id']) ? (int) $params['entity_id'] : 0;

        if (!$entity || !$entityId) {
            $this->json(['success' => false, 'error' => 'Ungültige Parameter'], 400);
            return;
        }

        // Verify entity type is known
        if (!FieldRegistry::getModelClass($entity)) {
            $this->json(['success' => false, 'error' => 'Unbekannte Entität'], 400);
            return;
        }

        // Parse input (support both form-encoded and JSON body)
        $input = $this->parseInput();
        if (empty($input)) {
            $this->json(['success' => false, 'error' => 'Keine Daten empfangen'], 400);
            return;
        }

        // Normalise to field => value map
        $fieldsToUpdate = $this->normaliseInput($input);
        if (empty($fieldsToUpdate)) {
            $this->json(['success' => false, 'error' => 'Keine gültigen Felder'], 400);
            return;
        }

        // Permission check per field
        $userRelationFields = ['group_type', 'role_ids'];
        foreach ($fieldsToUpdate as $fieldName => $value) {
            // Fields stored in separate tables — handled specially
            if ($entity === 'rehearsal' && in_array($fieldName, ['groups', 'schedule_items', 'infos', 'role_ids', 'tags'])) {
                if (empty($_SESSION['current_permissions']['can_manage_rehearsals'])) {
                    $this->json(['success' => false, 'error' => 'Keine Berechtigung'], 403);
                    return;
                }
                continue;
            }

            if ($entity === 'orchestra' && $fieldName === 'section_config') {
                if (empty($_SESSION['current_permissions']['can_manage_ensemble'])) {
                    $this->json(['success' => false, 'error' => 'Keine Berechtigung'], 403);
                    return;
                }
                continue;
            }

            if ($entity === 'user' && in_array($fieldName, $userRelationFields)) {
                continue;
            }

            $fieldDef = FieldRegistry::getField($entity, $fieldName);
            if (!$fieldDef) {
                $this->json(['success' => false, 'error' => "Unbekanntes Feld: {$fieldName}"], 400);
                return;
            }

            $required = $fieldDef['permission'] ?? 'member';
            $permMap = [
                'conductor' => 'can_manage_rehearsals',
                'leader' => 'can_view_own_section_stats',
                'member' => null,
            ];
            $perm = $permMap[$required] ?? null;
            if ($perm && empty($_SESSION['current_permissions'][$perm])) {
                $this->json(['success' => false, 'error' => 'Keine Berechtigung'], 403);
                return;
            }
        }

        // Ownership / access check
        if (!$this->canAccessEntity($entity, $entityId, $context)) {
            $this->json(['success' => false, 'error' => 'Zugriff verweigert'], 403);
            return;
        }

        // Validate all fields
        $allErrors = [];
        foreach ($fieldsToUpdate as $fieldName => $value) {
            if ($entity === 'rehearsal' && in_array($fieldName, ['groups', 'schedule_items', 'infos', 'role_ids', 'tags'])) continue;

            if ($entity === 'orchestra' && $fieldName === 'section_config') continue;

            if ($entity === 'user' && in_array($fieldName, $userRelationFields)) continue;
            $result = Validator::validateField($entity, $fieldName, $value);
            if (!$result['valid']) {
                $allErrors[$fieldName] = $result['errors'];
            }
        }

        if (!empty($allErrors)) {
            $this->json(['success' => false, 'errors' => $allErrors], 422);
            return;
        }

        // Transform toggle values
        foreach ($fieldsToUpdate as $fieldName => &$value) {
            $fieldDef = FieldRegistry::getField($entity, $fieldName);
            if ($fieldDef && $fieldDef['type'] === 'toggle') {
                $value = filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
            }
            // Sanitize strings
            if (is_string($value)) {
                $value = Validator::sanitizeUtf8($value);
            }
        }
        unset($value);

        // Persist
        $saved = $this->persistUpdate($entity, $entityId, $fieldsToUpdate, $context);
        if ($saved === true) {
            // Sync session on email/display_name change
            if ($entity === 'user' && isset($fieldsToUpdate['email'])) {
                $_SESSION['email'] = $fieldsToUpdate['email'];
            }
            if ($entity === 'user' && isset($fieldsToUpdate['display_name'])) {
                $_SESSION['display_name'] = $fieldsToUpdate['display_name'];
            }
            if ($entity === 'orchestra' && isset($fieldsToUpdate['name'])) {
                $_SESSION['current_orchestra_name'] = $fieldsToUpdate['name'];
            }

            $response = [
                'success'  => true,
                'saved_at' => date('c'),
                'fields'   => array_keys($fieldsToUpdate),
            ];

            // Return SmartGroupDisplay text when groups are saved
            if ($entity === 'rehearsal' && isset($fieldsToUpdate['groups'])) {
                $rehearsalModel = new \App\Models\Rehearsal();
                $rehearsal = $rehearsalModel->findById($entityId);
                if ($rehearsal) {
                    $smartDisplay = new \App\Core\SmartGroupDisplay();
                    $response['groups_display'] = $smartDisplay->generateDescription(
                        $rehearsal['groups'] ?? [], $rehearsal, false
                    );
                }
            }

            $this->json($response);
        } elseif (is_array($saved) && isset($saved['error'])) {
            $this->json(['success' => false, 'error' => $saved['message'] ?? 'Speichern fehlgeschlagen'], 422);
        } else {
            $this->json(['success' => false, 'error' => 'Speichern fehlgeschlagen'], 500);
        }
    }

    // ── Helpers ────────────────────────────────────────────────

    private function parseInput(): array
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

        if (str_contains($contentType, 'application/json')) {
            $raw = file_get_contents('php://input');
            return json_decode($raw, true) ?: [];
        }

        return $_POST;
    }

    /** Normalise { field, value } or { fields: {...} } into flat map. */
    private function normaliseInput(array $input): array
    {
        if (isset($input['fields']) && is_array($input['fields'])) {
            return $input['fields'];
        }

        if (isset($input['field']) && isset($input['value'])) {
            return [$input['field'] => $input['value']];
        }

        // Filter out non-field keys
        $allowed = array_flip(array_merge(
            FieldRegistry::getAllowedFieldNames('orchestra'),
            FieldRegistry::getAllowedFieldNames('user'),
            FieldRegistry::getAllowedFieldNames('rehearsal')
        ));

        return array_intersect_key($input, $allowed);
    }


    /** Verify entity belongs to current orchestra / user. */
    private function canAccessEntity(string $entity, int $entityId, array $context): bool
    {
        $orchestraId = $context['orchestra_id'];

        switch ($entity) {
            case 'orchestra':
                return $entityId === (int) $orchestraId;

            case 'user':
                return $entityId === (int) ($_SESSION['user_id'] ?? 0);

            case 'rehearsal':
                $model = new \App\Models\Rehearsal();
                $rehearsal = $model->findById($entityId);
                return $rehearsal && (int) ($rehearsal['orchestra_id'] ?? 0) === (int) $orchestraId;

            default:
                return false;
        }
    }

    private function persistUpdate(string $entity, int $entityId, array $data, array $context)
    {
        switch ($entity) {
            case 'orchestra':
                $model = new \App\Models\Orchestra();

                if (isset($data['section_config'])) {
                    $config = $data['section_config'];
                    if ($config === '' || $config === 'null') {
                        $data['section_config'] = null;
                    } else {
                        $decoded = is_string($config) ? json_decode($config, true) : $config;
                        if (!is_array($decoded)) {
                            $this->json(['success' => false, 'error' => 'Ungültige Registerstruktur'], 422);
                            return false;
                        }
                        $data['section_config'] = json_encode($decoded);
                    }
                }

                $result = $model->update($entityId, $data);
                if ($result && isset($data['section_config'])) {
                    \App\Core\GroupManager::resetInstance($entityId);
                }
                return $result;

            case 'user':
                $model = new \App\Models\User();
                $orchestraId = (int)($context['orchestra_id'] ?? $_SESSION['current_orchestra_id'] ?? 0);
                $userOrchestraModel = new \App\Models\UserOrchestra();

                // Handle relation fields (stored in user_orchestras)
                if (isset($data['group_type'])) {
                    $userOrchestraModel->updateUserType($entityId, $orchestraId, $data['group_type']);
                    $_SESSION['current_type'] = $data['group_type'];
                    unset($data['group_type']);
                }

                if (isset($data['role_ids'])) {
                    $submittedIds = json_decode($data['role_ids'], true) ?: [];
                    $submittedIds = array_map('intval', $submittedIds);

                    // Preserve non-self-assignable roles, only replace self-assignable
                    $roleModel = new \App\Models\Role();
                    $selfAssignable = $roleModel->getSelfAssignableRoles($orchestraId);
                    $selfAssignableIds = array_map(fn($r) => (int)$r['id'], $selfAssignable);

                    $currentRoles = $userOrchestraModel->getUserRoles($entityId, $orchestraId);
                    $currentRoleIds = array_map(fn($r) => (int)$r['id'], $currentRoles);

                    $preserved = array_values(array_diff($currentRoleIds, $selfAssignableIds));
                    $newSelfAssigned = array_values(array_intersect($submittedIds, $selfAssignableIds));
                    $finalRoleIds = array_values(array_unique(array_merge($preserved, $newSelfAssigned)));

                    $userOrchestraModel->setRoles($entityId, $orchestraId, $finalRoleIds);

                    // Refresh session so sidebar reflects changes immediately
                    $updatedRoles = $userOrchestraModel->getUserRoles($entityId, $orchestraId);
                    $_SESSION['current_roles'] = array_map(fn($r) => [
                        'id' => $r['id'],
                        'name' => $r['name'],
                        'tag_color' => $r['tag_color'] ?? '#478cf4',
                        'is_default' => $r['is_default'] ?? 0,
                        'is_system' => $r['is_system'] ?? 0,
                    ], $updatedRoles);

                    unset($data['role_ids']);
                }

                if (empty($data)) return true;
                $result = $model->updateProfile($entityId, $data);
                if (is_array($result) && !empty($result['error'])) {
                    return $result;
                }
                return $result === true || (is_bool($result) && $result);

            case 'rehearsal':
                $model = new \App\Models\Rehearsal();

                // Handle groups separately (stored in rehearsal_groups table)
                if (isset($data['groups'])) {
                    $groups = json_decode($data['groups'], true) ?: [];
                    if (empty($groups)) {
                        $groupManager = \App\Core\GroupManager::getInstance();
                        $groups = array_map(fn($g) => $g['id'], $groupManager->getConfig());
                    }
                    unset($data['groups']);
                    $model->updateGroups($entityId, $groups);
                }

                // Handle schedule items (stored in rehearsal_schedule_items table)
                if (isset($data['schedule_items'])) {
                    $items = json_decode($data['schedule_items'], true) ?: [];
                    unset($data['schedule_items']);
                    $model->saveScheduleItems($entityId, $items);
                }

                // Handle infos (stored in rehearsal_infos table)
                if (isset($data['infos'])) {
                    $items = json_decode($data['infos'], true) ?: [];
                    unset($data['infos']);
                    $model->saveInfos($entityId, $items);
                }

                // Handle role_ids (stored in rehearsal_roles table)
                if (isset($data['role_ids'])) {
                    $roleIds = json_decode($data['role_ids'], true) ?: [];
                    $roleIds = array_map('intval', $roleIds);
                    unset($data['role_ids']);
                    $roleModel = new \App\Models\Role();
                    $roleModel->setRehearsalRoles($entityId, $roleIds);
                }

                if (isset($data['tags'])) {
                    $tags = json_decode($data['tags'], true) ?: [];
                    unset($data['tags']);
                    $model->saveTags($entityId, (int)$context['orchestra_id'], $tags);
                }

                if (empty($data)) return true;
                $data['updated_at'] = date('Y-m-d H:i:s');
                return $model->update($entityId, $data);

            default:
                return false;
        }
    }

    /**
     * Return members whose type matches any of the given group IDs.
     * POST body: { "types": ["Violine_1", "Flöte"] }
     */
    public function sectionMembers(array $params = []): void
    {
        $context = $this->validateOrchestraContext($params);
        if (!$context) {
            $this->json(['success' => false, 'error' => 'Kein Orchesterkontext'], 403);
            return;
        }
        if (empty($_SESSION['current_permissions']['can_manage_ensemble'])) {
            $this->json(['success' => false, 'error' => 'Keine Berechtigung'], 403);
            return;
        }

        $body = json_decode(file_get_contents('php://input'), true) ?: [];
        $types = $body['types'] ?? [];
        if (empty($types) || !is_array($types)) {
            $this->json(['success' => true, 'members' => []]);
            return;
        }

        $orchestraId = (int) $context['orchestra_id'];
        $uoModel = new \App\Models\UserOrchestra();
        $members = [];
        foreach ($types as $type) {
            foreach ($uoModel->getUsersByType($type, $orchestraId) as $m) {
                $members[] = [
                    'user_id'      => (int) $m['user_id'],
                    'display_name' => $m['orchestra_display_name'] ?? $m['display_name'] ?? $m['email'],
                    'type'         => $m['type'],
                ];
            }
        }

        $this->json(['success' => true, 'members' => $members]);
    }

    /**
     * Bulk-reassign member types.
     * POST body: { "moves": [ { "user_id": 1, "new_type": "Bratsche" }, ... ] }
     */
    public function reassignMembers(array $params = []): void
    {
        $context = $this->validateOrchestraContext($params);
        if (!$context) {
            $this->json(['success' => false, 'error' => 'Kein Orchesterkontext'], 403);
            return;
        }
        if (empty($_SESSION['current_permissions']['can_manage_ensemble'])) {
            $this->json(['success' => false, 'error' => 'Keine Berechtigung'], 403);
            return;
        }

        $body = json_decode(file_get_contents('php://input'), true) ?: [];
        $moves = $body['moves'] ?? [];
        if (empty($moves) || !is_array($moves)) {
            $this->json(['success' => false, 'error' => 'Keine Zuweisungen angegeben'], 400);
            return;
        }

        $orchestraId = (int) $context['orchestra_id'];
        $uoModel = new \App\Models\UserOrchestra();
        $errors = 0;
        foreach ($moves as $move) {
            $userId  = (int) ($move['user_id'] ?? 0);
            $newType = $move['new_type'] ?? '';
            if (!$userId || !$newType) {
                $errors++;
                continue;
            }
            if (!$uoModel->updateUserType($userId, $orchestraId, $newType)) {
                $errors++;
            }
        }

        $this->json(['success' => $errors === 0, 'errors' => $errors]);
    }

    /**
     * Bulk-update multiple rehearsals with the same field values.
     *
     * POST body: { "ids": [1,2,3], "fields": { "type": "Konzert", ... } }
     */
    public function bulkUpdateRehearsals(array $params = []): void
    {
        $context = $this->validateOrchestraContext($params);
        if (!$context) {
            $this->json(['success' => false, 'error' => 'Kein Orchesterkontext'], 403);
            return;
        }

        if (empty($_SESSION['current_permissions']['can_manage_rehearsals'])) {
            $this->json(['success' => false, 'error' => 'Keine Berechtigung'], 403);
            return;
        }

        $body = json_decode(file_get_contents('php://input'), true) ?: [];
        $ids = array_map('intval', $body['ids'] ?? []);
        $fields = $body['fields'] ?? [];

        if (empty($ids) || empty($fields)) {
            $this->json(['success' => false, 'error' => 'Keine IDs oder Felder angegeben'], 400);
            return;
        }

        $orchestraId = (int) $context['orchestra_id'];
        $model = new \App\Models\Rehearsal();
        $results = [];
        $errorCount = 0;

        foreach ($ids as $id) {
            $rehearsal = $model->findById($id);
            if (!$rehearsal || (int)($rehearsal['orchestra_id'] ?? 0) !== $orchestraId) {
                $results[$id] = ['success' => false, 'error' => 'Nicht gefunden'];
                $errorCount++;
                continue;
            }

            $ok = $this->persistUpdate('rehearsal', $id, $fields, $context);
            $entry = ['success' => $ok];

            if ($ok && isset($fields['groups'])) {
                $updated = $model->findById($id);
                if ($updated) {
                    $smartDisplay = new \App\Core\SmartGroupDisplay();
                    $entry['groups_display'] = $smartDisplay->generateDescription(
                        $updated['groups'] ?? [], $updated, false
                    );
                }
            }

            if (!$ok) $errorCount++;
            $results[$id] = $entry;
        }

        $this->json([
            'success' => $errorCount === 0,
            'results' => $results,
            'updated' => count($ids) - $errorCount,
            'errors'  => $errorCount,
        ]);
    }

    private function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}
