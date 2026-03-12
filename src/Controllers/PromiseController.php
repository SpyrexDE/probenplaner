<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Validator;
use App\Core\ErrorHandler;
use App\Models\User;
use App\Models\Rehearsal;
use App\Models\UserPromise;

/**
 * Promise Controller
 * Handles user attendance promises
 */
class PromiseController extends Controller
{
    /**
     * @var User
     */
    private $userModel;


    /**
     * @var Rehearsal
     */
    private $rehearsalModel;

    /**
     * @var \App\Models\Orchestra
     */
    private $orchestraModel;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->userModel = new User();
        $this->rehearsalModel = new Rehearsal();
        $this->orchestraModel = new \App\Models\Orchestra();
    }

    /**
     * Display user promises (attendance)
     * 
     * @param array $params Route parameters containing orchestra_id
     * @return void
     */
    public function index($params = [])
    {
        $this->validateOrchestraContext($params);

        // Conductors don't give promises — redirect to admin view
        if (!empty($_SESSION['current_permissions']['can_manage_ensemble'])) {
            $this->redirect($this->orchestraUrl('/promises/admin'));
            return;
        }

        if (isset($_GET['ajax']) && $_GET['pastOnly']) {
            $this->handlePastRehearsalsAjax();
            return;
        }

        $showOld = isset($_GET['showOld']);

        $userType = $_SESSION['current_type'];

        $userId = $_SESSION['user_id'] ?? null;
        $orchestraId = $_SESSION['current_orchestra_id'] ?? null;

        $orchestra = $this->orchestraModel->findById($_SESSION['current_orchestra_id']);
        $forceDeclineReason = !empty($orchestra['force_decline_reason']);
        $allowAttendanceReset = !isset($orchestra['allow_attendance_reset']) || !empty($orchestra['allow_attendance_reset']);
        $allowPastEdit = !isset($orchestra['allow_past_edit']) || !empty($orchestra['allow_past_edit']);

        $userRoleIds = [];
        if ($userId && $orchestraId) {
            $userOrchestraModel = new \App\Models\UserOrchestra();
            $userRoles = $userOrchestraModel->getUserRoles((int)$userId, (int)$orchestraId);
            $userRoleIds = array_map(fn($r) => (int)$r['id'], $userRoles);
        }
        $rehearsals = $this->rehearsalModel->getForUser($userType, $_SESSION['current_orchestra_id'], $showOld, $userRoleIds);

        $promises = [];
        $user = $this->userModel->findById((int)$_SESSION['user_id']);

        if ($user) {
            $userPromises = $this->userModel->getPromises($user['id']);
            foreach ($userPromises as $promise) {
                $promises[$promise['rehearsal_id']] = [
                    'attending' => ($promise['status'] === 'yes'),
                    'note' => $promise['note']
                ];
            }
        }

        $currentPage = 'promises';
        if (strpos($_SERVER['REQUEST_URI'], '/rehearsals') === 0) {
            $currentPage = 'rehearsals';
        }

        $hasPastRehearsals = $this->rehearsalModel->hasPastRehearsals($_SESSION['current_orchestra_id']);

        $this->render('promises/index', [
            'currentPage' => $currentPage,
            'user' => $user,
            'rehearsals' => $rehearsals,
            'promises' => $promises,
            'showOld' => $showOld,
            'orchestra' => $orchestra,
            'forceDeclineReason' => $forceDeclineReason,
            'allowAttendanceReset' => $allowAttendanceReset,
            'allowPastEdit' => $allowPastEdit,
            'hasPastRehearsals' => $hasPastRehearsals
        ]);
    }

    /**
     * Display section leader view of member promises
     * 
     * @param array $params Route parameters containing orchestra_id
     * @return void
     */
    public function leader($params = [])
    {
        $this->validateOrchestraContext($params);

        $this->requireAnyPermission('can_view_own_section_stats', 'can_view_parent_section_stats', 'can_view_all_section_stats');

        $showOld = isset($_GET['showOld']);

        $userType = $_SESSION['current_type'];
        $sectionName = str_replace(' ', '_', $userType);

        // Get user role IDs for visibility filtering
        $userRoleIds = [];
        $userId = $_SESSION['user_id'] ?? null;
        $orchestraId = $_SESSION['current_orchestra_id'] ?? null;
        if ($userId && $orchestraId) {
            $userOrchestraModel = new \App\Models\UserOrchestra();
            $userRoles = $userOrchestraModel->getUserRoles((int)$userId, (int)$orchestraId);
            $userRoleIds = array_map(fn($r) => (int)$r['id'], $userRoles);
        }
        $rehearsals = $this->rehearsalModel->getForUser($sectionName, $_SESSION['current_orchestra_id'], $showOld, $userRoleIds);

        $orchestraModel = new \App\Models\Orchestra();
        $orchestra = $orchestraModel->findById($_SESSION['current_orchestra_id']);
        $canViewAllSections = !empty($_SESSION['current_permissions']['can_view_all_section_stats']);
        $viewAllSections = $canViewAllSections && isset($_GET['viewAll']) && $_GET['viewAll'] === '1';

        $userOrchestraModel = new \App\Models\UserOrchestra();
        if ($viewAllSections) {
            $members = $userOrchestraModel->getOrchestraUsers($_SESSION['current_orchestra_id']);
        } else {
            $groupManager = \App\Core\GroupManager::getInstance();
            $resolvedType = $groupManager->resolveAlias($userType);
            $canViewParent = !empty($_SESSION['current_permissions']['can_view_parent_section_stats']);

            if ($canViewParent) {
                $leaderSection = $groupManager->getSectionForInstrument($resolvedType);
                $allMembers = $userOrchestraModel->getOrchestraUsers($_SESSION['current_orchestra_id']);
                $members = array_filter($allMembers, function ($m) use ($groupManager, $resolvedType, $leaderSection) {
                    $memberType = $groupManager->resolveAlias($m['type']);
                    if ($leaderSection) {
                        return $groupManager->getSectionForInstrument($memberType) === $leaderSection;
                    }
                    return $memberType === $resolvedType;
                });
            } else {
                $allMembers = $userOrchestraModel->getOrchestraUsers($_SESSION['current_orchestra_id']);
                $members = array_filter($allMembers, function ($m) use ($groupManager, $resolvedType) {
                    return $groupManager->resolveAlias($m['type']) === $resolvedType;
                });
            }
        }

        if (!is_array($members)) {
            $members = [];
        }

        $members = array_values(array_filter($members, function ($m) {
            return !empty($m['can_attend_rehearsals']);
        }));

        $groupManager = \App\Core\GroupManager::getInstance();
        $allSections = $groupManager->getAllSections();

        // Flat config fallback: use all non-special groups
        if (empty($allSections)) {
            $allSections = array_filter($groupManager->getAllGroups(), fn($g) => ($g['type'] ?? '') !== 'special');
        }

        // Batch-load all promises for this orchestra
        $promiseModel = new \App\Models\UserPromise();
        $allPromises = $promiseModel->getAllForOrchestra($_SESSION['current_orchestra_id']);

        $stats = [];
        $membersBySection = [];
        $memberPromises = [];

        $leaderResolvedType = $groupManager->resolveAlias($userType);
        $leaderSectionInfo = $groupManager->getSectionForInstrument($leaderResolvedType);
        $leaderSectionId = $leaderSectionInfo ?? $leaderResolvedType;

        // Batch-load groups/roles for visibility filtering
        $rehearsalIds = array_column($rehearsals, 'id');
        $groupsMap = [];
        $rolesMap = [];
        if (!empty($rehearsalIds)) {
            foreach ($rehearsals as $r) {
                $assoc = [];
                foreach ($r['groups'] ?? [] as $name) {
                    $assoc[$name] = 0;
                }
                $groupsMap[$r['id']] = $assoc;
            }
            $rolesMap = $this->rehearsalModel->getBatchRehearsalRoleIds($rehearsalIds);
        }

        foreach ($rehearsals as $rehearsal) {
            $rehearsalId = $rehearsal['id'];
            $stats[$rehearsalId] = [
                'attending' => 0,
                'not_attending' => 0,
                'no_response' => 0
            ];

            if (!$viewAllSections) {
                $membersBySection[$rehearsalId] = ['all' => []];
                $membersBySection[$rehearsalId][$leaderSectionId] = [];
            } else {
                $membersBySection[$rehearsalId] = ['all' => []];
                foreach ($allSections as $sectionId => $sectionData) {
                    $membersBySection[$rehearsalId][$sectionId] = [];
                }
            }

            $memberPromises[$rehearsalId] = [
                'attending' => [],
                'not_attending' => [],
                'no_response' => []
            ];

            if (!empty($members)) {
                $groups = $groupsMap[$rehearsalId] ?? [];
                $rehearsalRoleIds = $rolesMap[$rehearsalId] ?? [];

                foreach ($members as $member) {
                    $memberRoleIds = $member['role_ids'] ?? [];

                    if (!$this->rehearsalModel->isUserInRehearsalGroup($member['type'], $groups, $rehearsalRoleIds, $memberRoleIds)) {
                        continue;
                    }

                    $promise = $allPromises[(int)$member['user_id']][$rehearsalId] ?? null;
                    $status = 'no_response';
                    $note = '';
                    if ($promise) {
                        $status = ($promise['status'] === 'yes') ? 'attending' : 'not_attending';
                        $note = $promise['note'];
                    }

                    // Section filtering for leader-only view
                    if (!$viewAllSections) {
                        $memberResolvedType = $groupManager->resolveAlias($member['type']);
                        $memberSectionInfo = $groupManager->getSectionForInstrument($memberResolvedType);
                        $leaderSInfo = $groupManager->getSectionForInstrument($leaderResolvedType);

                        $belongsToLeaderSection = false;
                        if ($leaderSInfo && $memberSectionInfo) {
                            $belongsToLeaderSection = $leaderSInfo === $memberSectionInfo;
                        } else {
                            $belongsToLeaderSection = $memberResolvedType === $leaderResolvedType || $member['type'] === $userType;
                        }

                        if (!$belongsToLeaderSection) continue;
                    }

                    $stats[$rehearsalId][$status]++;

                    $memberInfo = [
                        'display_name' => $member['display_name'] ?? $member['email'] ?? '',
                        'type' => $member['type'],
                        'status' => $status,
                        'note' => $note,
                        'permissions' => $member['permissions'] ?? [],
                        'id' => $member['user_id'],
                        'roles' => $member['roles'] ?? []
                    ];

                    $membersBySection[$rehearsalId]['all'][] = $memberInfo;

                    // Populate memberPromises (replacing the old duplicate loop)
                    $category = $status === 'attending' ? 'attending' : ($status === 'not_attending' ? 'not_attending' : 'no_response');
                    $promiseEntry = [
                        'display_name' => $memberInfo['display_name'],
                        'type' => $memberInfo['type'],
                    ];
                    if ($status !== 'no_response') {
                        $promiseEntry['note'] = $note;
                    }
                    $memberPromises[$rehearsalId][$category][] = $promiseEntry;

                    if (!$viewAllSections) {
                        $membersBySection[$rehearsalId][$leaderSectionId][] = $memberInfo;
                    } else {
                        $memberType = $groupManager->resolveAlias($member['type']);
                        foreach ($allSections as $sectionId => $sectionData) {
                            if ($groupManager->isUserInGroup($memberType, $sectionId)) {
                                $membersBySection[$rehearsalId][$sectionId][] = $memberInfo;
                            }
                        }
                    }
                }
            }
        }

        $leaderSectionNames = [];
        if ($leaderSectionId) {
            $leaderSectionNames[] = $leaderSectionId;
            $leaderSectionNames[] = $groupManager->getDisplayName($leaderSectionId);
        }
        if ($leaderResolvedType !== $leaderSectionId) {
            $leaderSectionNames[] = $leaderResolvedType;
            $leaderSectionNames[] = $groupManager->getDisplayName($leaderResolvedType);
        }

        $hasPastRehearsals = $this->rehearsalModel->hasPastRehearsals($_SESSION['current_orchestra_id']);

        $this->render('promises/leader', [
            'currentPage' => 'leader',
            'rehearsals' => $rehearsals,
            'stats' => $stats,
            'membersBySection' => $membersBySection,
            'memberPromises' => $memberPromises,
            'showOld' => $showOld,
            'canViewAllSections' => $canViewAllSections,
            'currentlyViewingAll' => $viewAllSections,
            'leaderSection' => $leaderSectionId,
            'leaderSectionDisplayName' => $groupManager->getDisplayName($leaderSectionId),
            'leaderSectionNames' => $leaderSectionNames,
            'isLeaderOnlyView' => !$viewAllSections,
            'leaderResolvedType' => $leaderResolvedType
        ]);
    }

    /**
     * Update promise
     * 
     * @return void
     */
    public function update()
    {
        if (!$this->isLoggedIn()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Nicht eingeloggt', 'details' => 'Bitte melden Sie sich erneut an.']);
            return;
        }

        if (empty($_SESSION['current_permissions']['can_attend_rehearsals'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Keine Berechtigung', 'details' => 'Sie haben keine Berechtigung, Rückmeldungen abzugeben.']);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Ungültige Anfrage', 'details' => 'Diese Aktion ist nur über AJAX erlaubt.']);
            return;
        }

        $rehearsalId = isset($_POST['id']) ? intval($_POST['id']) : 0;

        $statusParam = $_POST['status'] ?? null;
        if ($statusParam === 'reset') {
            $status = 'reset';
        } else {
            $status = (bool)$statusParam;
        }

        $note = isset($_POST['note']) ? trim($_POST['note']) : '';

        if ($rehearsalId <= 0) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Ungültige Proben-ID', 'details' => 'Die angegebene Proben-ID ist ungültig.']);
            return;
        }

        $user = $this->userModel->findById((int)$_SESSION['user_id']);

        if (!$user) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Benutzer nicht gefunden', 'details' => 'Ihr Benutzerkonto wurde nicht gefunden. Bitte melden Sie sich erneut an.']);
            return;
        }

        $orchestraModel = new \App\Models\Orchestra();
        $orchestra = $orchestraModel->findById($_SESSION['current_orchestra_id']);

        // Check for force decline reason setting
        if ($status === false && empty($note)) {
            if (!empty($orchestra['force_decline_reason'])) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Begründung erforderlich', 'details' => 'Bitte geben Sie einen Grund für Ihre Absage an.']);
                return;
            }
        }

        // Block edits for past rehearsals when disabled
        $allowPastEdit = !isset($orchestra['allow_past_edit']) || !empty($orchestra['allow_past_edit']);
        if (!$allowPastEdit) {
            $rehearsalModel = new \App\Models\Rehearsal();
            $rehearsal = $rehearsalModel->findById($rehearsalId);
            if ($rehearsal && $rehearsal['date'] < date('Y-m-d')) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Nicht erlaubt', 'details' => 'Nachträgliche Änderungen für vergangene Proben sind nicht erlaubt.']);
                return;
            }
        }

        $result = $this->userModel->updatePromise($user['id'], $rehearsalId, $status, $note);

        header('Content-Type: application/json');
        if ($result === true) {
            echo json_encode(['success' => true]);
        } elseif (is_array($result) && isset($result['error'])) {
            echo json_encode([
                'success' => false,
                'message' => $result['message'],
                'details' => $result['details']
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Fehler beim Speichern',
                'details' => 'Es ist ein unerwarteter Fehler aufgetreten.',
                'debug_message' => 'Promise update returned false or unexpected result'
            ]);
        }
    }

    /**
     * Add or update note
     * 
     * @return void
     */
    public function note()
    {
        if (!$this->isLoggedIn()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Nicht eingeloggt', 'details' => 'Bitte melden Sie sich erneut an.']);
            return;
        }

        if (empty($_SESSION['current_permissions']['can_attend_rehearsals'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Keine Berechtigung', 'details' => 'Sie haben keine Berechtigung, Rückmeldungen abzugeben.']);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Ungültige Anfrage', 'details' => 'Diese Aktion ist nur über AJAX erlaubt.']);
            return;
        }

        $rehearsalId = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $note = Validator::sanitizeUtf8($_POST['note'] ?? '');

        if ($rehearsalId <= 0) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Ungültige Proben-ID', 'details' => 'Die angegebene Proben-ID ist ungültig.']);
            return;
        }

        $user = $this->userModel->findById((int)$_SESSION['user_id']);

        if (!$user) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Benutzer nicht gefunden', 'details' => 'Ihr Benutzerkonto wurde nicht gefunden. Bitte melden Sie sich erneut an.']);
            return;
        }

        $promiseModel = new UserPromise();
        $existingPromise = $promiseModel->findByUserAndRehearsal($user['id'], $rehearsalId);
        $status = $existingPromise && isset($existingPromise['status']) ? ($existingPromise['status'] === 'yes') : false;

        // Update promise with note
        $result = $this->userModel->updatePromise($user['id'], $rehearsalId, $status, $note);

        header('Content-Type: application/json');
        if ($result === true) {
            echo json_encode(['success' => true]);
        } elseif (is_array($result) && isset($result['error'])) {
            echo json_encode([
                'success' => false,
                'message' => $result['message'],
                'details' => $result['details']
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Fehler beim Speichern der Anmerkung',
                'details' => 'Es ist ein unerwarteter Fehler aufgetreten.',
                'debug_message' => 'Promise note update returned false or unexpected result'
            ]);
        }
    }

    /**
     * Display admin view for directors
     * 
     * @return void
     */
    public function admin($params = [])
    {
        $this->validateOrchestraContext($params);

        $this->requirePermission('can_manage_rehearsals');


        $showOld = isset($_GET['showOld']);

        $rehearsals = $this->rehearsalModel->getUpcoming($_SESSION['current_orchestra_id'], $showOld);

        $userOrchestraModel = new \App\Models\UserOrchestra();
        $users = $userOrchestraModel->getOrchestraUsers($_SESSION['current_orchestra_id']);

        $orchestraModel = new \App\Models\Orchestra();
        $orchestra = $orchestraModel->findById($_SESSION['current_orchestra_id']);
        $showRehearsalInsights = !empty($orchestra['show_rehearsal_insights']);

        $groupManager = \App\Core\GroupManager::getInstance();
        $allSections = $groupManager->getAllSections();

        // Flat config fallback: use all non-special groups
        if (empty($allSections)) {
            $allSections = array_filter($groupManager->getAllGroups(), fn($g) => ($g['type'] ?? '') !== 'special');
        }

        // Batch-load all promises for this orchestra
        $promiseModel = new \App\Models\UserPromise();
        $allPromises = $promiseModel->getAllForOrchestra($_SESSION['current_orchestra_id']);

        // Batch-load groups and roles for visibility filtering
        $rehearsalIds = array_column($rehearsals, 'id');
        $groupsMap = [];
        $rolesMap = [];
        if (!empty($rehearsalIds)) {
            // Groups are already in each rehearsal row from enrichRows().
            // Build assoc map from the existing data.
            foreach ($rehearsals as $r) {
                $assoc = [];
                foreach ($r['groups'] ?? [] as $name) {
                    $assoc[$name] = 0;
                }
                $groupsMap[$r['id']] = $assoc;
            }
            // Role IDs need a batch load
            $rolesMap = $this->rehearsalModel->getBatchRehearsalRoleIds($rehearsalIds);
        }

        $stats = [];
        $membersBySection = [];

        foreach ($rehearsals as $rehearsal) {
            $rehearsalId = $rehearsal['id'];
            $stats[$rehearsalId] = [
                'attending' => 0,
                'not_attending' => 0,
                'no_response' => 0
            ];

            $membersBySection[$rehearsalId] = ['all' => []];
            foreach ($allSections as $sectionId => $sectionData) {
                $membersBySection[$rehearsalId][$sectionId] = [];
            }

            $groups = $groupsMap[$rehearsalId] ?? [];
            $rehearsalRoleIds = $rolesMap[$rehearsalId] ?? [];

            foreach ($users as $user) {
                if (empty($user['can_attend_rehearsals'])) continue;

                $memberRoleIds = $user['role_ids'] ?? [];

                if ($this->rehearsalModel->isUserInRehearsalGroup($user['type'], $groups, $rehearsalRoleIds, $memberRoleIds)) {
                    $promise = $allPromises[(int)$user['user_id']][$rehearsalId] ?? null;
                    $status = 'no_response';
                    $note = '';
                    if ($promise) {
                        $status = ($promise['status'] === 'yes') ? 'attending' : 'not_attending';
                        $note = $promise['note'];
                    }

                    $stats[$rehearsalId][$status]++;

                    $memberInfo = [
                        'display_name' => $user['display_name'] ?? $user['email'] ?? '',
                        'type' => $user['type'],
                        'status' => $status,
                        'note' => $note,
                        'permissions' => $user['permissions'] ?? [],
                        'id' => $user['user_id'],
                        'roles' => $user['roles'] ?? []
                    ];

                    $membersBySection[$rehearsalId]['all'][] = $memberInfo;

                    $userType = $groupManager->resolveAlias($user['type']);
                    foreach ($allSections as $sectionId => $sectionData) {
                        if ($groupManager->isUserInGroup($userType, $sectionId)) {
                            $membersBySection[$rehearsalId][$sectionId][] = $memberInfo;
                        }
                    }
                }
            }
        }

        $hasPastRehearsals = $this->rehearsalModel->hasPastRehearsals($_SESSION['current_orchestra_id']);

        // Pre-compute deviation analysis to avoid per-rehearsal DB queries in the view
        $deviationData = [];
        if ($showRehearsalInsights) {
            require_once __DIR__ . '/../Core/SmartDeviationDetector.php';
            $deviationDetector = new \SmartDeviationDetector(\App\Core\Database::getInstance());
            foreach ($rehearsals as $rehearsal) {
                $rId = $rehearsal['id'];
                $deviationData[$rId] = $deviationDetector->analyzeRehearsalFromData(
                    $rehearsal,
                    $stats[$rId] ?? ['attending' => 0, 'not_attending' => 0, 'no_response' => 0],
                    $membersBySection[$rId]['all'] ?? []
                );
            }
        }

        $this->render('promises/admin', [
            'currentPage' => 'admin',
            'rehearsals' => $rehearsals,
            'stats' => $stats,
            'membersBySection' => $membersBySection,
            'showOld' => $showOld,
            'showRehearsalInsights' => $showRehearsalInsights,
            'hasPastRehearsals' => $hasPastRehearsals,
            'deviationData' => $deviationData,
            'sidebarStats' => !empty($rehearsals) ? array_merge(
                $stats[$rehearsals[0]['id']] ?? ['attending' => 0, 'not_attending' => 0, 'no_response' => 0],
                [
                    'next_rehearsal' => [
                        'id' => $rehearsals[0]['id'],
                        'date' => $rehearsals[0]['date'],
                        'date_formatted' => $rehearsals[0]['date_formatted'],
                        'type' => $rehearsals[0]['type'] ?? \App\Core\RehearsalTypeManager::TYPE_REHEARSAL,
                        'location' => $rehearsals[0]['location'] ?? ''
                    ]
                ]
            ) : null
        ]);
    }

    /**
     * Handle AJAX request for past rehearsals with pagination
     * 
     * @return void
     */
    private function handlePastRehearsalsAjax()
    {
        try {
            $offset = (int)($_GET['offset'] ?? 0);
            $limit = (int)($_GET['limit'] ?? 10);

            $pageData = $this->rehearsalModel->getPastPaginated(
                $_SESSION['current_orchestra_id'],
                $offset,
                $limit
            );
            $paginatedRehearsals = $pageData['rows'];
            $totalPastRehearsals = $pageData['total'];
            $hasMore = ($offset + $limit) < $totalPastRehearsals;

            $rehearsalIds = array_column($paginatedRehearsals, 'id');
            $promises = [];
            if (!empty($rehearsalIds)) {
                $promiseModel = new \App\Models\UserPromise();
                $promises = $promiseModel->findPromisesForRehearsalsAndUser($rehearsalIds, $_SESSION['user_id']);
            }

            $html = '';
            foreach ($paginatedRehearsals as $rehearsal) {
                $status = 'pending';
                $note = '';

                if (isset($promises[$rehearsal['id']])) {
                    $status = $promises[$rehearsal['id']]['attending'] ? 'attending' : 'not_attending';
                    $note = $promises[$rehearsal['id']]['note'];
                }

                $groupArray = $rehearsal['groups'] ?? [];

                $smartDisplay = new \App\Core\SmartGroupDisplay();
                $groupsText = $smartDisplay->generateDescription(
                    $groupArray,
                    $rehearsal,
                    false
                );

                $context = 'promises';
                $options = [
                    'status' => $status,
                    'note' => $note,
                    'showButtons' => true
                ];

                ob_start();
                include __DIR__ . '/../Views/components/rehearsal-card.php';
                $html .= ob_get_clean();
            }

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'html' => $html,
                'hasMore' => $hasMore,
                'total' => $totalPastRehearsals,
                'loaded' => $offset + count($paginatedRehearsals)
            ]);
            exit;
        } catch (\Exception $e) {
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Fehler beim Laden der Termine',
                'debug_message' => $e->getMessage()
            ]);
            exit;
        }
    }
}
