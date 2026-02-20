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
        $userRelationFields = ['group_type', 'small_group'];
        foreach ($fieldsToUpdate as $fieldName => $value) {
            // Fields stored in separate tables — handled specially
            if ($entity === 'rehearsal' && in_array($fieldName, ['groups', 'schedule_items', 'infos'])) {
                if (empty($_SESSION['current_permissions']['can_manage_rehearsals'])) {
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
            if ($entity === 'rehearsal' && in_array($fieldName, ['groups', 'schedule_items', 'infos'])) continue;

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
        if ($saved) {
            // Update session on username change
            if ($entity === 'user' && isset($fieldsToUpdate['username'])) {
                $_SESSION['username'] = $fieldsToUpdate['username'];
            }
            if ($entity === 'orchestra' && isset($fieldsToUpdate['name'])) {
                $_SESSION['current_orchestra_name'] = $fieldsToUpdate['name'];
            }

            $this->json([
                'success'  => true,
                'saved_at' => date('c'),
                'fields'   => array_keys($fieldsToUpdate),
            ]);
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

    private function persistUpdate(string $entity, int $entityId, array $data, array $context): bool
    {
        switch ($entity) {
            case 'orchestra':
                $model = new \App\Models\Orchestra();
                return $model->update($entityId, $data);

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
                if (array_key_exists('small_group', $data)) {
                    $val = filter_var($data['small_group'], FILTER_VALIDATE_BOOLEAN);
                    $userOrchestraModel->updateUserSmallGroupStatus($entityId, $orchestraId, $val);
                    unset($data['small_group']);
                }

                if (empty($data)) return true;
                $result = $model->updateProfile($entityId, $data);
                return $result === true || (is_bool($result) && $result);

            case 'rehearsal':
                $model = new \App\Models\Rehearsal();

                // Handle groups separately (stored in rehearsal_groups table)
                if (isset($data['groups'])) {
                    $groups = json_decode($data['groups'], true) ?: [];
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


                if (empty($data)) return true;
                $data['updated_at'] = date('Y-m-d H:i:s');
                return $model->update($entityId, $data);

            default:
                return false;
        }
    }

    private function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}
