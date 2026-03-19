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
 */
class AttendanceController extends Controller
{
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
        $members = $this->loadScopedMembers($orchestraId, $scope);

        // Batch-load promises
        $promiseModel = new UserPromise();
        $allPromises = $promiseModel->getAllForOrchestra($orchestraId);

        // Which rehearsals already have attendance documented
        $documentedIds = $this->attendanceModel->getDocumentedRehearsalIds($orchestraId);

        // Find initial rehearsal (most recent past, or current)
        $initialRehearsalId = $this->findInitialRehearsal($rehearsals);

        // Load attendance for initial rehearsal
        $attendanceRecords = [];
        if ($initialRehearsalId) {
            $attendanceRecords = $this->attendanceModel->getForRehearsal($initialRehearsalId);
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
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Fehlende Parameter']);
            return;
        }

        // Permission scope check for own-section-only users
        if ($this->getAttendanceScope() === 'own_section') {
            if (!$this->isUserInScope($userId, $ctx['orchestra_id'])) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Keine Berechtigung für dieses Mitglied']);
                return;
            }
        }

        $recordedBy = (int)$_SESSION['user_id'];

        if ($action === 'delete') {
            $this->attendanceModel->deleteRecord($rehearsalId, $userId);
            echo json_encode(['success' => true, 'status' => 'unset']);
            return;
        }

        if ($action === 'comment') {
            $comment = trim($_POST['comment'] ?? '');
            $existing = $this->attendanceModel->getForRehearsalAndUser($rehearsalId, $userId);
            // Allow comments even without existing attendance record
            $present = $existing ? (bool)$existing['present'] : null;
            $this->attendanceModel->upsert(
                $rehearsalId, $userId,
                $present,
                $comment ?: null,
                $recordedBy
            );
            echo json_encode(['success' => true, 'comment' => $comment, 'status' => $present === null ? 'unset' : ($present ? 'present' : 'absent')]);
            return;
        }

        // Toggle: cycle through present → absent → delete
        $existing = $this->attendanceModel->getForRehearsalAndUser($rehearsalId, $userId);

        if (!$existing) {
            // No record → set present
            $this->attendanceModel->upsert($rehearsalId, $userId, true, null, $recordedBy);
            echo json_encode(['success' => true, 'status' => 'present']);
        } elseif ($existing['present']) {
            // Present → absent
            $this->attendanceModel->upsert(
                $rehearsalId, $userId, false,
                $existing['comment'], $recordedBy
            );
            echo json_encode(['success' => true, 'status' => 'absent']);
        } else {
            // Absent → delete (back to undocumented)
            $this->attendanceModel->deleteRecord($rehearsalId, $userId);
            echo json_encode(['success' => true, 'status' => 'unset']);
        }
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
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Fehlende Probe-ID']);
            return;
        }

        $orchestraId = $ctx['orchestra_id'];
        $scope = $this->getAttendanceScope();
        $members = $this->loadScopedMembers($orchestraId, $scope);

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

        // Return updated records
        $records = $this->attendanceModel->getForRehearsal($rehearsalId);

        echo json_encode([
            'success' => true,
            'confirmed' => $count,
            'records' => $records,
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
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Fehlende Probe-ID']);
            return;
        }

        $records = $this->attendanceModel->getForRehearsal($rehearsalId);
        echo json_encode(['success' => true, 'records' => $records]);
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

        $members = $this->loadScopedMembers($orchestraId, $scope);

        $allAttendance = $this->attendanceModel->getAllForOrchestra($orchestraId);
        $promiseModel = new UserPromise();
        $allPromises = $promiseModel->getAllForOrchestra($orchestraId);

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

        // Rehearsal list with full date for filtering
        $rehearsalList = array_map(function ($r) {
            $start = new \DateTime($r['start'] ?? $r['date'] ?? '');
            return [
                'id'       => (int)$r['id'],
                'date'     => $start->format('d.m'),
                'dateFull' => $start->format('Y-m-d'),
                'weekday'  => ['So','Mo','Di','Mi','Do','Fr','Sa'][$start->format('w')],
            ];
        }, $pastRehearsals);

        // Attendance map
        $attMap = [];
        foreach ($allAttendance as $uid => $byRehearsal) {
            foreach ($byRehearsal as $rid => $rec) {
                $attMap[$uid][$rid] = (int)(bool)$rec['present'];
            }
        }

        // Promise map
        $promMap = [];
        foreach ($allPromises as $uid => $byRehearsal) {
            foreach ($byRehearsal as $rid => $rec) {
                $promMap[$uid][$rid] = $rec['status'];
            }
        }

        header('Content-Type: application/json');
        echo json_encode([
            'success'    => true,
            'rehearsals' => $rehearsalList,
            'sections'   => $sections,
            'attendance' => $attMap,
            'promises'   => $promMap,
        ]);
    }

    /**
     * Load members scoped by permission level.
     */
    /**
     * @return string 'all', 'parent_section', or 'own_section'
     */
    private function getAttendanceScope(): string
    {
        if ($this->hasPermission('can_manage_attendance_all')) return 'all';
        if ($this->hasPermission('can_manage_attendance_parent_section')) return 'parent_section';
        return 'own_section';
    }

    private function loadScopedMembers(int $orchestraId, string $scope): array
    {
        $userOrchestraModel = new \App\Models\UserOrchestra();
        $allMembers = $userOrchestraModel->getOrchestraUsers($orchestraId);

        if ($scope === 'all') {
            $members = $allMembers;
        } else {
            $groupManager = \App\Core\GroupManager::getInstance();
            $resolvedType = $groupManager->resolveAlias($_SESSION['current_type']);

            if ($scope === 'parent_section') {
                $leaderSection = $groupManager->getSectionForInstrument($resolvedType);
                $members = array_filter($allMembers, function ($m) use ($groupManager, $resolvedType, $leaderSection) {
                    $memberType = $groupManager->resolveAlias($m['type']);
                    if ($leaderSection) {
                        return $groupManager->getSectionForInstrument($memberType) === $leaderSection;
                    }
                    return $memberType === $resolvedType;
                });
            } else {
                // own_section: exact instrument type match
                $members = array_filter($allMembers, function ($m) use ($groupManager, $resolvedType) {
                    return $groupManager->resolveAlias($m['type']) === $resolvedType;
                });
            }
            $members = array_values($members);
        }

        return array_values(array_filter($members, fn($m) => !empty($m['can_attend_rehearsals'])));
    }

    /**
     * Check if a target user is within the current user's section scope.
     */
    private function isUserInScope(int $targetUserId, int $orchestraId): bool
    {
        $scopedMembers = $this->loadScopedMembers($orchestraId, $this->getAttendanceScope());
        foreach ($scopedMembers as $m) {
            if ((int)($m['user_id'] ?? $m['id']) === $targetUserId) return true;
        }
        return false;
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
