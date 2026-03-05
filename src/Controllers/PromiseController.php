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

        $isSmallGroup = false;
        $userId = $_SESSION['user_id'] ?? null;
        $orchestraId = $_SESSION['current_orchestra_id'] ?? null;

        $orchestra = $this->orchestraModel->findById($_SESSION['current_orchestra_id']);
        $forceDeclineReason = !empty($orchestra['force_decline_reason']);
        $allowAttendanceReset = !isset($orchestra['allow_attendance_reset']) || !empty($orchestra['allow_attendance_reset']);

        if ($userId && $orchestraId) {
            $userOrchestraModel = new \App\Models\UserOrchestra();
            $isSmallGroup = $userOrchestraModel->isUserInSmallGroup((int)$userId, (int)$orchestraId);
        }
        $user = ['is_small_group' => $isSmallGroup];
        $rehearsals = $this->rehearsalModel->getForUser($userType, $_SESSION['current_orchestra_id'], $showOld, $isSmallGroup);

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

        $this->requireAnyPermission('can_view_own_section_stats', 'can_view_all_section_stats');

        $showOld = isset($_GET['showOld']);

        $userType = $_SESSION['current_type'];

        // Clean up section name for database queries
        $sectionName = str_replace(' ', '_', $userType);

        // Get small group status from user_orchestras table
        $isSmallGroup = false;
        $userId = $_SESSION['user_id'] ?? null;
        $orchestraId = $_SESSION['current_orchestra_id'] ?? null;
        if ($userId && $orchestraId) {
            $userOrchestraModel = new \App\Models\UserOrchestra();
            $isSmallGroup = $userOrchestraModel->isUserInSmallGroup((int)$userId, (int)$orchestraId);
        }
        $user = ['is_small_group' => $isSmallGroup];
        $rehearsals = $this->rehearsalModel->getForUser($sectionName, $_SESSION['current_orchestra_id'], $showOld, $isSmallGroup);

        $orchestraModel = new \App\Models\Orchestra();
        $orchestra = $orchestraModel->findById($_SESSION['current_orchestra_id']);
        $canViewAllSections = !empty($_SESSION['current_permissions']['can_view_all_section_stats']);
        $viewAllSections = $canViewAllSections && isset($_GET['viewAll']) && $_GET['viewAll'] === '1';


        $userOrchestraModel = new \App\Models\UserOrchestra();
        if ($viewAllSections) {
            $members = $userOrchestraModel->getOrchestraUsers($_SESSION['current_orchestra_id']);
        } else {
            $members = $userOrchestraModel->getUsersByType($sectionName, $_SESSION['current_orchestra_id']);

            if (empty($members)) {
                $members = $userOrchestraModel->getUsersByType($userType, $_SESSION['current_orchestra_id']);
            }

            if (empty($members)) {
                $groupManager = new \App\Core\GroupManager();
                $resolvedType = $groupManager->resolveAlias($userType);
                $allMembers = $userOrchestraModel->getOrchestraUsers($_SESSION['current_orchestra_id']);

                // Filter members that belong to the leader's section/group
                $members = array_filter($allMembers, function ($member) use ($groupManager, $resolvedType, $userType) {
                    $memberType = $groupManager->resolveAlias($member['type']);
                    $leaderSectionInfo = $groupManager->getSectionForInstrument($resolvedType);
                    $memberSectionInfo = $groupManager->getSectionForInstrument($memberType);

                    if ($leaderSectionInfo && $memberSectionInfo) {
                        return $leaderSectionInfo === $memberSectionInfo;
                    }

                    // Fallback: check if the member type matches the leader type
                    return $memberType === $resolvedType || $member['type'] === $userType;
                });

                if (!empty($members)) {
                    $memberTypes = array_map(function ($m) {
                        return $m['type'];
                    }, $members);
                }
            }
        }

        if (!is_array($members)) {
            $members = [];
        }

        $members = array_values(array_filter($members, function ($m) {
            return !empty($m['can_attend_rehearsals']);
        }));

        // Initialize GroupManager for dynamic section handling
        $groupManager = new \App\Core\GroupManager();
        $allSections = $groupManager->getAllSections();

        $stats = [];
        $membersBySection = [];

        // Get leader's section information for data processing
        $leaderResolvedType = $groupManager->resolveAlias($userType);
        $leaderSectionInfo = $groupManager->getSectionForInstrument($leaderResolvedType);
        $leaderSectionId = $leaderSectionInfo ?? $leaderResolvedType;

        // For leaders viewing only their section, structure data differently
        foreach ($rehearsals as $rehearsal) {
            $rehearsalId = $rehearsal['id'];
            $stats[$rehearsalId] = [
                'attending' => 0,
                'not_attending' => 0,
                'no_response' => 0
            ];

            // For leaders viewing only their section, structure data differently
            if (!$viewAllSections) {
                // Only include the leader's section in the data structure
                $membersBySection[$rehearsalId] = ['all' => []];
                $membersBySection[$rehearsalId][$leaderSectionId] = [];
            } else {
                // Initialize all sections for full view
                $membersBySection[$rehearsalId] = ['all' => []];
                foreach ($allSections as $sectionId => $sectionData) {
                    $membersBySection[$rehearsalId][$sectionId] = [];
                }
            }

            // Only process members if we found any
            if (!empty($members)) {
                // Determine which users apply to this rehearsal
                $groups = $this->rehearsalModel->getGroupsAsAssoc($rehearsal['id']);
                $rehearsalIsSmallGroup = \App\Core\RehearsalTypeManager::isSmallGroupRehearsal($rehearsal);

                foreach ($members as $member) {
                    // Get small-group membership from user_orchestras relation
                    $isSmallGroup = isset($member['is_small_group']) && (int)$member['is_small_group'] === 1;

                    if ($this->rehearsalModel->isUserInRehearsalGroup($member['type'], $isSmallGroup, $groups, $rehearsalIsSmallGroup)) {
                        // Use user_id from the user_orchestras relation
                        $userPromises = $this->userModel->getPromises((int)$member['user_id']);
                        $found = false;
                        $status = 'no_response';
                        $note = '';

                        foreach ($userPromises as $promise) {
                            if ($promise['rehearsal_id'] == $rehearsalId) {
                                $status = ($promise['status'] === 'yes') ? 'attending' : 'not_attending';
                                $note = $promise['note'];
                                $found = true;
                                break;
                            }
                        }

                        // For leader view (not viewing all), only count members from leader's section
                        if (!$viewAllSections) {
                            // Verify this member belongs to leader's section
                            $memberResolvedType = $groupManager->resolveAlias($member['type']);
                            $memberSectionInfo = $groupManager->getSectionForInstrument($memberResolvedType);
                            $leaderSectionInfo = $groupManager->getSectionForInstrument($leaderResolvedType);

                            $belongsToLeaderSection = false;
                            if ($leaderSectionInfo && $memberSectionInfo) {
                                $belongsToLeaderSection = $leaderSectionInfo === $memberSectionInfo;
                            } else {
                                $belongsToLeaderSection = $memberResolvedType === $leaderResolvedType || $member['type'] === $userType;
                            }

                            if (!$belongsToLeaderSection) {
                                continue; // Skip members not in leader's section
                            }
                        }

                        // Update statistics (now only counts relevant members)
                        $stats[$rehearsalId][$status]++;

                        // Add user to the appropriate section
                        $memberInfo = [
                            'display_name' => $member['display_name'] ?? $member['email'] ?? '',
                            'type' => $member['type'],
                            'status' => $status,
                            'note' => $note,
                            'permissions' => $member['permissions'] ?? [],
                            'is_small_group' => $isSmallGroup ? \App\Core\RehearsalTypeManager::SMALL_GROUP_ENABLED : \App\Core\RehearsalTypeManager::SMALL_GROUP_DISABLED,
                            'id' => $member['user_id'] // Use 'id' not 'user_id' for badge lookup
                        ];

                        $membersBySection[$rehearsalId]['all'][] = $memberInfo;

                        // Add to sections based on view mode
                        if (!$viewAllSections) {
                            // For leader view, add to their specific section
                            $membersBySection[$rehearsalId][$leaderSectionId][] = $memberInfo;
                        } else {
                            // For full view, add to all applicable sections
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
        }

        // Group promises by rehearsal
        $memberPromises = [];

        foreach ($rehearsals as $rehearsal) {
            $rehearsalId = $rehearsal['id'];
            $memberPromises[$rehearsalId] = [
                'attending' => [],
                'not_attending' => [],
                'no_response' => []
            ];

            // Only process if we have members
            if (!empty($members)) {
                foreach ($members as $member) {
                    $promises = $this->userModel->getPromises($member['id']);
                    $found = false;

                    foreach ($promises as $promise) {
                        if ($promise['rehearsal_id'] == $rehearsalId) {
                            $category = ($promise['status'] === 'yes') ? 'attending' : 'not_attending';
                            $memberPromises[$rehearsalId][$category][] = [
                                'display_name' => $member['display_name'] ?? $member['email'] ?? '',
                                'type' => $member['type'],
                                'note' => $promise['note']
                            ];
                            $found = true;
                            break;
                        }
                    }

                    if (!$found) {
                        $memberPromises[$rehearsalId]['no_response'][] = [
                            'display_name' => $member['display_name'] ?? $member['email'] ?? '',
                            'type' => $member['type']
                        ];
                    }
                }
            }
        }

        // Get all possible section names for better matching (already defined above)
        $leaderSectionNames = [];
        if ($leaderSectionId) {
            $leaderSectionNames[] = $leaderSectionId;
            $leaderSectionNames[] = $groupManager->getDisplayName($leaderSectionId);
        }
        if ($leaderResolvedType !== $leaderSectionId) {
            $leaderSectionNames[] = $leaderResolvedType;
            $leaderSectionNames[] = $groupManager->getDisplayName($leaderResolvedType);
        }

        if (!empty($rehearsals)) {
            $firstRehearsal = $rehearsals[0];
            $firstRehearsalId = $firstRehearsal['id'];
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
            'leaderSection' => $leaderSectionId, // Pass the leader's section ID for filtering
            'leaderSectionDisplayName' => $groupManager->getDisplayName($leaderSectionId), // Pass display name for better matching
            'leaderSectionNames' => $leaderSectionNames, // Pass all possible names for matching
            'isLeaderOnlyView' => !$viewAllSections, // Flag to indicate leader-only view (when toggle is OFF)
            'leaderResolvedType' => $leaderResolvedType // Pass resolved type for frontend use
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

        // Check for force decline reason setting
        if ($status === false && empty($note)) {
            $orchestraModel = new \App\Models\Orchestra();
            $orchestra = $orchestraModel->findById($_SESSION['current_orchestra_id']);

            if (!empty($orchestra['force_decline_reason'])) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Begründung erforderlich', 'details' => 'Bitte geben Sie einen Grund für Ihre Absage an.']);
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

        // Get all rehearsals
        $rehearsals = $this->rehearsalModel->getUpcoming($_SESSION['current_orchestra_id'], $showOld);

        // Get all users in the current orchestra
        $userOrchestraModel = new \App\Models\UserOrchestra();
        $users = $userOrchestraModel->getOrchestraUsers($_SESSION['current_orchestra_id']);

        // Get orchestra settings
        $orchestraModel = new \App\Models\Orchestra();
        $orchestra = $orchestraModel->findById($_SESSION['current_orchestra_id']);
        $showRehearsalInsights = !empty($orchestra['show_rehearsal_insights']);

        // Initialize GroupManager for dynamic section handling
        $groupManager = new \App\Core\GroupManager();
        $allSections = $groupManager->getAllSections();

        $stats = [];
        $membersBySection = [];

        foreach ($rehearsals as $rehearsal) {
            $rehearsalId = $rehearsal['id'];
            $stats[$rehearsalId] = [
                'attending' => 0,
                'not_attending' => 0,
                'no_response' => 0
            ];

            // Initialize sections dynamically from configuration
            $membersBySection[$rehearsalId] = ['all' => []];
            foreach ($allSections as $sectionId => $sectionData) {
                $membersBySection[$rehearsalId][$sectionId] = [];
            }

            // Determine which users apply to this rehearsal
            $groups = $this->rehearsalModel->getGroupsAsAssoc($rehearsal['id']);
            $rehearsalIsSmallGroup = \App\Core\RehearsalTypeManager::isSmallGroupRehearsal($rehearsal);

            foreach ($users as $user) {
                if (empty($user['can_attend_rehearsals'])) {
                    continue;
                }

                $isSmallGroup = isset($user['is_small_group']) && (int)$user['is_small_group'] === 1;

                if ($this->rehearsalModel->isUserInRehearsalGroup($user['type'], $isSmallGroup, $groups, $rehearsalIsSmallGroup)) {
                    // Use users.id (available as user_id in relation row)
                    $userPromises = $this->userModel->getPromises((int)$user['user_id']);
                    $found = false;
                    $status = 'no_response';
                    $note = '';

                    foreach ($userPromises as $promise) {
                        if ($promise['rehearsal_id'] == $rehearsalId) {
                            $status = ($promise['status'] === 'yes') ? 'attending' : 'not_attending';
                            $note = $promise['note'];
                            $found = true;
                            break;
                        }
                    }

                    // Update statistics
                    $stats[$rehearsalId][$status]++;

                    // Add user to the appropriate section
                    $memberInfo = [
                        'display_name' => $user['display_name'] ?? $user['email'] ?? '',
                        'type' => $user['type'],
                        'status' => $status,
                        'note' => $note,
                        'permissions' => $user['permissions'] ?? [],
                        'is_small_group' => $isSmallGroup ? \App\Core\RehearsalTypeManager::SMALL_GROUP_ENABLED : \App\Core\RehearsalTypeManager::SMALL_GROUP_DISABLED,
                        'id' => $user['user_id'] // Use 'id' not 'user_id' for badge lookup
                    ];

                    $membersBySection[$rehearsalId]['all'][] = $memberInfo;

                    // Dynamically determine which sections this user belongs to
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

        $this->render('promises/admin', [
            'currentPage' => 'admin',
            'rehearsals' => $rehearsals,
            'stats' => $stats,
            'membersBySection' => $membersBySection,
            'showOld' => $showOld,
            'showRehearsalInsights' => $showRehearsalInsights,
            'hasPastRehearsals' => $hasPastRehearsals,
            'sidebarStats' => !empty($rehearsals) ? array_merge(
                $stats[$rehearsals[0]['id']] ?? ['attending' => 0, 'not_attending' => 0, 'no_response' => 0],
                [
                    'next_rehearsal' => [
                        'id' => $rehearsals[0]['id'],
                        'date' => $rehearsals[0]['date'],
                        'date_formatted' => $rehearsals[0]['date_formatted'],
                        'type' => $rehearsals[0]['type'] ?? \App\Core\RehearsalTypeManager::TYPE_REHEARSAL,
                        'location' => $rehearsals[0]['location'] ?? '',
                        'is_small_group' => $rehearsals[0]['is_small_group'] ?? false
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

            $allPastRehearsals = $this->rehearsalModel->getUpcoming($_SESSION['current_orchestra_id'], true);

            $today = date('Y-m-d');
            $pastRehearsals = array_filter($allPastRehearsals, function ($rehearsal) use ($today) {
                return $rehearsal['date'] < $today;
            });

            usort($pastRehearsals, function ($a, $b) {
                return strtotime($b['date']) - strtotime($a['date']);
            });

            $totalPastRehearsals = count($pastRehearsals);
            $paginatedRehearsals = array_slice($pastRehearsals, $offset, $limit);
            $hasMore = ($offset + $limit) < $totalPastRehearsals;

            // Get promises for these rehearsals
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

                // Get group information
                $groupArray = $rehearsal['groups'] ?? [];

                // Generate smart display text with integrated Kleingruppe handling
                $smartDisplay = new \App\Core\SmartGroupDisplay();
                $groupsText = $smartDisplay->generateDescription(
                    $groupArray,
                    $rehearsal,
                    false // Not admin view
                );

                $context = 'promises';
                $options = [
                    'status' => $status,
                    'note' => $note,
                    'showButtons' => true
                ];

                // Capture output
                ob_start();
                include __DIR__ . '/../Views/components/rehearsal-card.php';
                $html .= ob_get_clean();
            }

            // Return JSON response
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
