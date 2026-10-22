<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\FieldRegistry;
use App\Core\JmdPush;
use App\Core\Validator;

/**
 * Generic API controller for field-level settings updates.
 *
 * Handles: POST /{orchestra_id}/api/settings/{entity}/{id}
 */
class SettingsApiController extends Controller
{
    /**
     * Fields with special handling per entity (stored in relation tables or
     * decoded separately) — skipped by the generic Validator.
     */
    private const SPECIAL_FIELDS = [
        'rehearsal' => ['groups', 'schedule_items', 'infos', 'role_ids', 'tags'],
        'orchestra' => ['section_config'],
        'user'      => ['group_type', 'role_ids'],
    ];

    /**
     * Special fields that are no columns of the entity's own table (persisted via
     * relation tables in persistUpdate) — never passed to Model::update().
     */
    private const RELATION_FIELDS = [
        'rehearsal' => ['groups', 'schedule_items', 'infos', 'role_ids', 'tags'],
        'user'      => ['group_type', 'role_ids'],
    ];

    /** FieldRegistry 'permission' level → session permission key (null = any member). */
    private const PERMISSION_MAP = [
        'ensemble'  => 'can_manage_ensemble',
        'conductor' => 'can_manage_rehearsals',
        'leader'    => 'can_view_own_section_stats',
        'member'    => null,
    ];

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

        // Parse input (JSON body only — CSRF hardening, exits with 415 otherwise)
        $this->requireJsonBody();
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

        // Whitelist + permission check per field (exits with JSON on failure)
        $this->authorizeFields($entity, $fieldsToUpdate);

        // Ownership / access check
        if (!$this->canAccessEntity($entity, $entityId, $context)) {
            $this->json(['success' => false, 'error' => 'Zugriff verweigert'], 403);
            return;
        }

        // Validate, transform toggles, sanitize strings (exits with 422 on failure)
        $fieldsToUpdate = $this->validateAndNormaliseFields($entity, $fieldsToUpdate);

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

    private function isSpecialField(string $entity, string $fieldName): bool
    {
        return in_array($fieldName, self::SPECIAL_FIELDS[$entity] ?? [], true);
    }

    /**
     * Reject every field that is not whitelisted for the entity (FieldRegistry
     * or its special fields) or that the current user may not change.
     * Responds with JSON and exits on failure.
     */
    private function authorizeFields(string $entity, array $fields): void
    {
        foreach (array_keys($fields) as $fieldName) {
            $fieldName = (string) $fieldName;

            // Fields stored in separate tables — handled specially
            if ($entity === 'rehearsal' && $this->isSpecialField($entity, $fieldName)) {
                if (empty($_SESSION['current_permissions']['can_manage_rehearsals'])) {
                    $this->json(['success' => false, 'error' => 'Keine Berechtigung'], 403);
                }
                continue;
            }

            if ($entity === 'orchestra' && $fieldName === 'section_config') {
                if (empty($_SESSION['current_permissions']['can_manage_ensemble'])) {
                    $this->json(['success' => false, 'error' => 'Keine Berechtigung'], 403);
                }
                continue;
            }

            if ($entity === 'user' && $this->isSpecialField($entity, $fieldName)) {
                continue;
            }

            $fieldDef = FieldRegistry::getField($entity, $fieldName);
            if (!$fieldDef) {
                $this->json(['success' => false, 'error' => "Unbekanntes Feld: {$fieldName}"], 400);
            }

            $required = $fieldDef['permission'] ?? 'member';
            $perm = self::PERMISSION_MAP[$required] ?? null;
            if ($perm && empty($_SESSION['current_permissions'][$perm])) {
                $this->json(['success' => false, 'error' => 'Keine Berechtigung'], 403);
            }

            if ($fieldName === 'push_reminder_minutes' && !JmdPush::isEnabled()) {
                $this->json(['success' => false, 'error' => 'Push-Benachrichtigungen sind nicht eingerichtet'], 404);
            }
        }
    }

    /**
     * Validate fields against FieldRegistry rules, cast toggles and sanitize strings.
     * Responds with 422 JSON and exits on validation errors.
     */
    private function validateAndNormaliseFields(string $entity, array $fields): array
    {
        $allErrors = [];
        foreach ($fields as $fieldName => $value) {
            if ($this->isSpecialField($entity, (string) $fieldName)) continue;

            $result = Validator::validateField($entity, (string) $fieldName, $value);
            if (!$result['valid']) {
                $allErrors[$fieldName] = $result['errors'];
                continue;
            }

            // Lead times: '' = off, otherwise 00:01 to 23:59
            $fieldDef = FieldRegistry::getField($entity, (string) $fieldName);
            if ($fieldDef['type'] === 'minutes' && $value !== '' && (!is_numeric($value) || (int) $value < 1 || (int) $value > 1439)) {
                $allErrors[$fieldName] = ["{$fieldDef['label']}: zwischen 00:01 und 23:59"];
            }
        }

        if (!empty($allErrors)) {
            $this->json(['success' => false, 'errors' => $allErrors], 422);
        }

        // Transform toggle values
        foreach ($fields as $fieldName => &$value) {
            $fieldDef = FieldRegistry::getField($entity, (string) $fieldName);
            if ($fieldDef && $fieldDef['type'] === 'toggle') {
                $value = filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
            }
            if ($fieldDef && $fieldDef['type'] === 'minutes') {
                $value = $value === '' ? null : (int) $value;
            }
            // Sanitize strings
            if (is_string($value)) {
                $value = Validator::sanitizeUtf8($value);
            }
        }
        unset($value);

        return $fields;
    }

    /**
     * Defense in depth: only FieldRegistry-defined columns may reach Model::update()
     * (column names are interpolated into the SQL there). Relation fields that were
     * not consumed by persistUpdate (e.g. sent as null) are dropped as well.
     */
    private function onlyRegistryFields(string $entity, array $data): array
    {
        $columns = array_diff(
            FieldRegistry::getAllowedFieldNames($entity),
            self::RELATION_FIELDS[$entity] ?? []
        );
        return array_intersect_key($data, array_flip($columns));
    }

    /**
     * CSRF hardening for the state-changing endpoints: only accept a JSON body
     * (Content-Type essence exactly application/json). Cross-site HTML forms cannot
     * send this type, and cross-origin fetch() with it needs a CORS preflight, which
     * this app never grants. All in-app callers send JSON.
     * Responds with 415 JSON and exits otherwise.
     */
    private function requireJsonBody(): void
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
        $essence = strtolower(trim(explode(';', (string) $contentType, 2)[0]));

        if ($essence !== 'application/json') {
            $this->json(['success' => false, 'error' => 'Ungültiges Anfrageformat (JSON erwartet)'], 415);
        }
    }

    private function parseInput(): array
    {
        $data = json_decode(file_get_contents('php://input'), true);
        return is_array($data) ? $data : [];
    }

    /** Normalise { field, value } or { fields: {...} } into flat map. */
    private function normaliseInput(array $input): array
    {
        if (isset($input['fields']) && is_array($input['fields'])) {
            return $input['fields'];
        }

        if (isset($input['field']) && isset($input['value'])) {
            // A non-string field name can't be a registry field; let validation reject it
            return is_string($input['field']) ? [$input['field'] => $input['value']] : [];
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

                $data = $this->onlyRegistryFields('orchestra', $data);
                if (empty($data)) return false;
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

                $data = $this->onlyRegistryFields('user', $data);
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

                $data = $this->onlyRegistryFields('rehearsal', $data);
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

        $this->requireJsonBody();
        $body = $this->parseInput();
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

        $this->requireJsonBody();
        $body = $this->parseInput();
        $ids = is_array($body['ids'] ?? null) ? array_map('intval', $body['ids']) : [];
        $fields = is_array($body['fields'] ?? null) ? $body['fields'] : [];

        if (empty($ids) || empty($fields)) {
            $this->json(['success' => false, 'error' => 'Keine IDs oder Felder angegeben'], 400);
            return;
        }

        // Same whitelist, permission and validation rules as single-field updates
        $this->authorizeFields('rehearsal', $fields);
        $fields = $this->validateAndNormaliseFields('rehearsal', $fields);

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
