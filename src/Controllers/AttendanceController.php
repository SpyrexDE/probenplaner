<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Attendance;
use App\Models\Rehearsal;
use App\Models\UserPromise;

/**
 * Attendance Controller
 *
 * Single entry-point for the attendance panel. Permission scoping:
 *   - can_manage_attendance_all: sees all members
 *   - can_manage_attendance_parent_section: sees parent section (e.g. all woodwinds)
 *   - can_manage_attendance_own_section: sees only own instrument group
 *
 * Every endpoint is scoped to the orchestra from the URL ($ctx['orchestra_id']):
 * rehearsal IDs from the request must belong to that orchestra, and member data
 * (attendance, promises, comments) is only returned/written for members inside
 * the caller's attendance scope.
 */
class AttendanceController extends Controller
{
    private const ALLOWED_ACTIONS = ['toggle', 'delete', 'comment'];

    private Attendance $attendanceModel;
    private Rehearsal $rehearsalModel;

    public function __construct()
    {
        parent::__construct();
        $this->attendanceModel = new Attendance();
        $this->rehearsalModel = new Rehearsal();
    }

    /**
     * Main attendance page.
     */
    public function index($params = [])
    {
        $ctx = $this->validateOrchestraContext($params);
        if (!$ctx) return;

        if (!$this->requireAnyPermission('can_manage_attendance_own_section', 'can_manage_attendance_parent_section', 'can_manage_attendance_all')) {
            return;
        }

        $orchestraId = $ctx['orchestra_id'];
        $scope = $this->getAttendanceScope();

        // Load rehearsals (past + current, newest first for initial display)
        $rehearsals = $this->rehearsalModel->getUpcoming($orchestraId, true);

        // Members scoped by permission
        $members = $this->loadScopedMembers($orchestraId, $scope, $this->leaderType($ctx));
        $memberIds = $this->memberIdSet($members);

        // Batch-load promises (only for members in scope — the map is embedded in the page)
        $promiseModel = new UserPromise();
        $allPromises = array_intersect_key($promiseModel->getAllForOrchestra($orchestraId), $memberIds);

        // Which rehearsals already have attendance documented
        $documentedIds = $this->attendanceModel->getDocumentedRehearsalIds($orchestraId);

        // Find initial rehearsal (most recent past, or current)
        $initialRehearsalId = $this->findInitialRehearsal($rehearsals);

        // Load attendance for initial rehearsal
        $attendanceRecords = [];
        if ($initialRehearsalId) {
            $attendanceRecords = array_intersect_key(
                $this->attendanceModel->getForRehearsal($initialRehearsalId),
                $memberIds
            );
        }

        $this->render('attendance/index', [
            'currentPage'        => 'attendance',
            'rehearsals'         => $rehearsals,
            'members'            => $members,
            'allPromises'        => $allPromises,
            'attendanceRecords'  => $attendanceRecords,
            'documentedIds'      => $documentedIds,
            'initialRehearsalId' => $initialRehearsalId,
            'scope'              => $scope,
            'csrfToken'          => $this->getCSRFToken(),
        ]);
    }

    /**
     * AJAX: Update a single attendance record.
     */
    public function update($params = [])
    {
        $ctx = $this->validateOrchestraContext($params);
        if (!$ctx) return;

        $this->protectCSRF();

        if (!$this->requireAnyPermission('can_manage_attendance_own_section', 'can_manage_attendance_parent_section', 'can_manage_attendance_all')) {
            return;
        }

        $rehearsalId = (int)($_POST['rehearsal_id'] ?? 0);
        $userId = (int)($_POST['user_id'] ?? 0);
        $action = $_POST['action'] ?? 'toggle';

        header('Content-Type: application/json');

        if (!$rehearsalId || !$userId) {
            $this->jsonError(400, 'Fehlende Parameter');
            return;
        }

        if (!is_string($action) || !in_array($action, self::ALLOWED_ACTIONS, true)) {
            $this->jsonError(400, 'Unbekannte Aktion');
            return;
        }

        $orchestraId = $ctx['orchestra_id'];

        // The rehearsal must belong to the orchestra from the URL
        if (!$this->attendanceModel->rehearsalBelongsToOrchestra($rehearsalId, $orchestraId)) {
            $this->jsonError(404, 'Probe nicht gefunden');
            return;
        }

        // The target must be an active member of this orchestra inside the caller's
        // scope (all / parent section / own section) — same set the UI renders.
        if (!$this->isUserInScope($userId, $orchestraId, $this->leaderType($ctx))) {
            $this->jsonError(403, 'Keine Berechtigung für dieses Mitglied');
            return;
        }

        $recordedBy = (int)$_SESSION['user_id'];

        if ($action === 'delete') {
            if (!$this->attendanceModel->deleteRecord($rehearsalId, $userId)) {
                $this->jsonError(500, 'Speichern fehlgeschlagen');
                return;
            }
            echo json_encode(['success' => true, 'status' => 'unset']);
            return;
        }

        if ($action === 'comment') {
            $comment = is_string($_POST['comment'] ?? null) ? trim($_POST['comment']) : '';
            $existing = $this->attendanceModel->getForRehearsalAndUser($rehearsalId, $userId);
            $present = $existing ? (bool)$existing['present'] : null;

            // `present` is NOT NULL in the schema, so without a record the note is saved
            // together with the status the client chose. An existing status is never
            // overwritten by this action.
            if ($present === null) {
                $requested = $_POST['status'] ?? '';
                if ($requested === 'present') {
                    $present = true;
                } elseif ($requested === 'absent') {
                    $present = false;
                }
            }

            if ($present === null && $comment === '') {
                // Nothing stored and nothing to store
                echo json_encode(['success' => true, 'comment' => '', 'status' => 'unset']);
                return;
            }

            if ($present === null) {
                $this->jsonError(422, 'Noch keine Anwesenheit erfasst – bitte Status wählen.', ['needs_status' => true]);
                return;
            }

            $saved = $this->attendanceModel->upsert(
                $rehearsalId, $userId,
                $present,
                $comment !== '' ? $comment : null,
                $recordedBy
            );
            if (!$saved) {
                $this->jsonError(500, 'Speichern fehlgeschlagen');
                return;
            }
            echo json_encode(['success' => true, 'comment' => $comment, 'status' => $present ? 'present' : 'absent']);
            return;
        }

        // Toggle: cycle through present → absent → delete
        $existing = $this->attendanceModel->getForRehearsalAndUser($rehearsalId, $userId);

        if (!$existing) {
            // No record → set present
            $ok = $this->attendanceModel->upsert($rehearsalId, $userId, true, null, $recordedBy);
            $status = 'present';
        } elseif ($existing['present']) {
            // Present → absent
            $ok = $this->attendanceModel->upsert(
                $rehearsalId, $userId, false,
                $existing['comment'], $recordedBy
            );
            $status = 'absent';
        } else {
            // Absent → delete (back to undocumented)
            $ok = $this->attendanceModel->deleteRecord($rehearsalId, $userId);
            $status = 'unset';
        }

        if (!$ok) {
            $this->jsonError(500, 'Speichern fehlgeschlagen');
            return;
        }
        echo json_encode(['success' => true, 'status' => $status]);
    }

    /**
     * AJAX: Bulk-confirm attendance based on promises.
     */
    public function bulkConfirm($params = [])
    {
        $ctx = $this->validateOrchestraContext($params);
        if (!$ctx) return;

        $this->protectCSRF();

        if (!$this->requireAnyPermission('can_manage_attendance_own_section', 'can_manage_attendance_parent_section', 'can_manage_attendance_all')) {
            return;
        }

        $rehearsalId = (int)($_POST['rehearsal_id'] ?? 0);
        header('Content-Type: application/json');

        if (!$rehearsalId) {
            $this->jsonError(400, 'Fehlende Probe-ID');
            return;
        }

        $orchestraId = $ctx['orchestra_id'];

        if (!$this->attendanceModel->rehearsalBelongsToOrchestra($rehearsalId, $orchestraId)) {
            $this->jsonError(404, 'Probe nicht gefunden');
            return;
        }

        $scope = $this->getAttendanceScope();
        $members = $this->loadScopedMembers($orchestraId, $scope, $this->leaderType($ctx));

        $promiseModel = new UserPromise();
        $allPromises = $promiseModel->getAllForOrchestra($orchestraId);

        // Build promise map for this rehearsal
        $promisesByUser = [];
        foreach ($allPromises as $uid => $rehearsalPromises) {
            if (isset($rehearsalPromises[$rehearsalId])) {
                $promisesByUser[$uid] = $rehearsalPromises[$rehearsalId];
            }
        }

        $recordedBy = (int)$_SESSION['user_id'];
        $count = $this->attendanceModel->bulkConfirm($rehearsalId, $members, $promisesByUser, $recordedBy);

        // Return updated records (only members in scope)
        $records = array_intersect_key(
            $this->attendanceModel->getForRehearsal($rehearsalId),
            $this->memberIdSet($members)
        );

        echo json_encode([
            'success' => true,
            'confirmed' => $count,
            'records' => (object)$records,
        ]);
    }

    /**
     * AJAX: Load attendance data for a specific rehearsal.
     */
    public function loadRehearsal($params = [])
    {
        $ctx = $this->validateOrchestraContext($params);
        if (!$ctx) return;

        if (!$this->requireAnyPermission('can_manage_attendance_own_section', 'can_manage_attendance_parent_section', 'can_manage_attendance_all')) {
            return;
        }

        $rehearsalId = (int)($_GET['rehearsal_id'] ?? 0);
        header('Content-Type: application/json');

        if (!$rehearsalId) {
            $this->jsonError(400, 'Fehlende Probe-ID');
            return;
        }

        $orchestraId = $ctx['orchestra_id'];

        if (!$this->attendanceModel->rehearsalBelongsToOrchestra($rehearsalId, $orchestraId)) {
            $this->jsonError(404, 'Probe nicht gefunden');
            return;
        }

        // Only records (incl. notes) of members the caller may see
        $members = $this->loadScopedMembers($orchestraId, $this->getAttendanceScope(), $this->leaderType($ctx));
        $records = array_intersect_key(
            $this->attendanceModel->getForRehearsal($rehearsalId),
            $this->memberIdSet($members)
        );
        echo json_encode(['success' => true, 'records' => (object)$records]);
    }

    /**
     * AJAX: Return all data needed for the dense table view.
     */
    public function tableData($params = [])
    {
        $ctx = $this->validateOrchestraContext($params);
        if (!$ctx) return;

        if (!$this->requireAnyPermission('can_manage_attendance_own_section', 'can_manage_attendance_parent_section', 'can_manage_attendance_all')) {
            return;
        }

        $orchestraId = $ctx['orchestra_id'];
        $scope = $this->getAttendanceScope();

        $rehearsals = $this->rehearsalModel->getUpcoming($orchestraId, true);
        $now = new \DateTime();

        $pastRehearsals = array_values(array_filter($rehearsals, function ($r) use ($now) {
            return new \DateTime($r['start'] ?? $r['date'] ?? '') <= $now;
        }));
        usort($pastRehearsals, fn($a, $b) =>
            strtotime($a['start'] ?? $a['date'] ?? '') - strtotime($b['start'] ?? $b['date'] ?? '')
        );

        $members = $this->loadScopedMembers($orchestraId, $scope, $this->leaderType($ctx));
        $memberIds = $this->memberIdSet($members);

        // Only data of members in scope is sent to the client
        $allAttendance = array_intersect_key($this->attendanceModel->getAllForOrchestra($orchestraId), $memberIds);
        $promiseModel = new UserPromise();
        $allPromises = array_intersect_key($promiseModel->getAllForOrchestra($orchestraId), $memberIds);

        $groupManager = \App\Core\GroupManager::getInstance();

        // Section-grouped members (same logic as the card view)
        $sections = [];
        if ($scope === 'all') {
            $flatSections = $groupManager->getFlattenedSections();
            $membersByType = [];
            foreach ($members as $m) {
                $membersByType[$groupManager->resolveAlias($m['type'] ?? '')][] = $m;
            }

            foreach ($flatSections as $parentId => $instrumentIds) {
                $sectionMembers = [];
                foreach ($instrumentIds as $instrId) {
                    foreach ($membersByType[$instrId] ?? [] as $m) {
                        $sectionMembers[] = [
                            'id'   => (int)($m['user_id'] ?? $m['id']),
                            'name' => $m['display_name'] ?? '',
                            'type' => $m['type'] ?? '',
                        ];
                    }
                }
                if (!empty($sectionMembers)) {
                    $label = $parentId ? $groupManager->getDisplayName($parentId) : '';
                    $sections[] = ['label' => $label, 'members' => $sectionMembers];
                }
            }

            $placed = array_merge(...array_values($flatSections));
            $otherMembers = [];
            foreach ($membersByType as $type => $ms) {
                if (!in_array($type, $placed)) {
                    foreach ($ms as $m) {
                        $otherMembers[] = [
                            'id'   => (int)($m['user_id'] ?? $m['id']),
                            'name' => $m['display_name'] ?? '',
                            'type' => $m['type'] ?? '',
                        ];
                    }
                }
            }
            if (!empty($otherMembers)) {
                $sections[] = ['label' => 'Sonstige', 'members' => $otherMembers];
            }
        } else {
            $byType = [];
            foreach ($members as $m) {
                $type = $groupManager->resolveAlias($m['type'] ?? '');
                $byType[$type]['label'] = $groupManager->getPluralName($type);
                $byType[$type]['members'][] = [
                    'id'   => (int)($m['user_id'] ?? $m['id']),
                    'name' => $m['display_name'] ?? '',
                    'type' => $m['type'] ?? '',
                ];
            }
            $sections = array_values($byType);
        }

        // Rehearsal list with full date for filtering and the range picker
        $rehearsalList = array_map(function ($r) {
            $start = new \DateTime($r['start'] ?? $r['date'] ?? '');
            return [
                'id'       => (int)$r['id'],
                'date'     => $start->format('d.m'),
                'dateFull' => $start->format('Y-m-d'),
                'time'     => $start->format('H:i'),
                'weekday'  => ['So','Mo','Di','Mi','Do','Fr','Sa'][$start->format('w')],
                'color'    => $r['color'] ?? null,
                'type'     => (string)($r['type'] ?? ''),
                'location' => (string)($r['location'] ?? ''),
            ];
        }, $pastRehearsals);

        // Only past rehearsals are rendered as columns
        $pastIds = array_fill_keys(array_column($rehearsalList, 'id'), true);

        // Attendance map
        $attMap = [];
        foreach ($allAttendance as $uid => $byRehearsal) {
            foreach ($byRehearsal as $rid => $rec) {
                if (!isset($pastIds[$rid])) continue;
                $attMap[$uid][$rid] = (int)(bool)$rec['present'];
            }
        }

        // Promise map
        $promMap = [];
        foreach ($allPromises as $uid => $byRehearsal) {
            foreach ($byRehearsal as $rid => $rec) {
                if (!isset($pastIds[$rid])) continue;
                $promMap[$uid][$rid] = $rec['status'];
            }
        }

        header('Content-Type: application/json');
        echo json_encode([
            'success'    => true,
            'rehearsals' => $rehearsalList,
            'sections'   => $sections,
            'attendance' => (object)$attMap,
            'promises'   => (object)$promMap,
        ]);
    }

    /**
     * @return string 'all', 'parent_section', or 'own_section'
     */
    private function getAttendanceScope(): string
    {
        if ($this->hasPermission('can_manage_attendance_all')) return 'all';
        if ($this->hasPermission('can_manage_attendance_parent_section')) return 'parent_section';
        return 'own_section';
    }

    /**
     * Instrument type of the current user in THIS orchestra.
     *
     * Only taken from the membership row validateOrchestraContext() just loaded from
     * the DB — never from $_SESSION['current_type'], which can be stale or even belong
     * to another user (e.g. after an admin edited someone else's group). An empty type
     * simply scopes a section leader to members without a type.
     */
    private function leaderType(array $ctx): string
    {
        return (string)($ctx['user_type'] ?? '');
    }

    /**
     * Load members scoped by permission level.
     *
     * Only active members of the orchestra (getOrchestraUsers is active-only) who may
     * attend rehearsals. Section scopes use the same rules as the promise dashboard.
     */
    private function loadScopedMembers(int $orchestraId, string $scope, string $leaderType): array
    {
        $userOrchestraModel = new \App\Models\UserOrchestra();
        $allMembers = $userOrchestraModel->getOrchestraUsers($orchestraId);

        if ($scope === 'all') {
            $members = $allMembers;
        } else {
            $groupManager = \App\Core\GroupManager::getInstance();
            $resolvedType = $groupManager->resolveAlias($leaderType);

            if ($scope === 'parent_section') {
                // Parent section = nearest "section" ancestor of the leader's instrument
                // (e.g. all woodwinds). Without one, fall back to the own instrument.
                $leaderSection = $groupManager->getSectionForInstrument($resolvedType);
                $members = array_filter($allMembers, function ($m) use ($groupManager, $resolvedType, $leaderSection) {
                    $memberType = $groupManager->resolveAlias((string)($m['type'] ?? ''));
                    if ($leaderSection) {
                        return $groupManager->getSectionForInstrument($memberType) === $leaderSection;
                    }
                    return $memberType === $resolvedType;
                });
            } else {
                // own_section: exact instrument type match
                $members = array_filter($allMembers, function ($m) use ($groupManager, $resolvedType) {
                    return $groupManager->resolveAlias((string)($m['type'] ?? '')) === $resolvedType;
                });
            }
            $members = array_values($members);
        }

        return array_values(array_filter($members, fn($m) => !empty($m['can_attend_rehearsals'])));
    }

    /**
     * @return array<int, true> user_id => true for the given member rows
     */
    private function memberIdSet(array $members): array
    {
        $ids = [];
        foreach ($members as $m) {
            $ids[(int)($m['user_id'] ?? $m['id'])] = true;
        }
        return $ids;
    }

    /**
     * Check if a target user is an active member of the orchestra within the
     * current user's attendance scope (applies to all three scopes).
     */
    private function isUserInScope(int $targetUserId, int $orchestraId, string $leaderType): bool
    {
        $scopedMembers = $this->loadScopedMembers($orchestraId, $this->getAttendanceScope(), $leaderType);
        return isset($this->memberIdSet($scopedMembers)[$targetUserId]);
    }

    private function jsonError(int $status, string $message, array $extra = []): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => $message] + $extra);
    }

    /**
     * Find the most relevant rehearsal to show initially (most recent past or today's).
     */
    private function findInitialRehearsal(array $rehearsals): ?int
    {
        $now = new \DateTime();
        $bestId = null;
        $bestDate = null;

        foreach ($rehearsals as $r) {
            $date = new \DateTime($r['start'] ?? $r['date'] ?? '');
            if ($date <= $now) {
                if (!$bestDate || $date > $bestDate) {
                    $bestDate = $date;
                    $bestId = (int)$r['id'];
                }
            }
        }

        return $bestId;
    }
}
