<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Validator;
use App\Core\ErrorHandler;
use App\Core\JmdPush;
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

        // Redirect to admin view only if they cannot attend rehearsals themselves
        if (empty($_SESSION['current_permissions']['can_attend_rehearsals']) && !empty($_SESSION['current_permissions']['can_manage_ensemble'])) {
            $this->redirect($this->orchestraUrl('/promises/admin'));
            return;
        }

        if (isset($_GET['ajax']) && $_GET['pastOnly']) {
            $this->handlePastRehearsalsAjax();
            return;
        }

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
        $rehearsals = $this->rehearsalModel->getForUser($userType, $_SESSION['current_orchestra_id'], false, $userRoleIds);

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

        // Deep link (?rehearsal=ID, e.g. from a push): render at least up to that rehearsal
        $initialCount = self::INITIAL_LIMIT;
        $targetIndex = array_search((int)($_GET['rehearsal'] ?? 0), array_map('intval', array_column($rehearsals, 'id')), true);
        if ($targetIndex !== false) {
            $initialCount = max($initialCount, $targetIndex + 1);
        }

        $totalRehearsals = count($rehearsals);
        $hasMore = $totalRehearsals > $initialCount;
        $rehearsalsSliced = $hasMore ? array_slice($rehearsals, 0, $initialCount) : $rehearsals;

        $this->render('promises/index', [
            'currentPage' => $currentPage,
            'user' => $user,
            'rehearsals' => $rehearsalsSliced,
            'hasMoreRehearsals' => $hasMore,
            'totalRehearsals' => $totalRehearsals,
            'promises' => $promises,
            'orchestra' => $orchestra,
            'forceDeclineReason' => $forceDeclineReason,
            'allowAttendanceReset' => $allowAttendanceReset,
            'allowPastEdit' => $allowPastEdit,
            'hasPastRehearsals' => $hasPastRehearsals
        ]);
    }

    /**
     * AJAX endpoint: returns a batch of rehearsal cards for the player view.
     */
    public function indexLazy($params = [])
    {
        $this->validateOrchestraContext($params);

        $userType = $_SESSION['current_type'];
        $userId = $_SESSION['user_id'] ?? null;
        $orchestraId = $_SESSION['current_orchestra_id'] ?? null;
        
        $userRoleIds = [];
        if ($userId && $orchestraId) {
            $userOrchestraModel = new \App\Models\UserOrchestra();
            $userRoles = $userOrchestraModel->getUserRoles((int)$userId, (int)$orchestraId);
            $userRoleIds = array_map(fn($r) => (int)$r['id'], $userRoles);
        }

        $offset = max(0, (int)($_GET['offset'] ?? self::INITIAL_LIMIT));
        $allRehearsals = $this->rehearsalModel->getForUser($userType, $orchestraId, false, $userRoleIds);
        
        $remaining = array_slice($allRehearsals, $offset);
        $rehearsals = array_slice($remaining, 0, self::LAZY_BATCH_SIZE);
        $hasMore = count($remaining) > self::LAZY_BATCH_SIZE;
        $nextOffset = $offset + count($rehearsals);

        if (empty($rehearsals)) {
            echo '';
            return;
        }

        $promises = [];
        if ($userId) {
            $userPromises = $this->userModel->getPromises($userId);
            foreach ($userPromises as $promise) {
                $promises[$promise['rehearsal_id']] = [
                    'attending' => ($promise['status'] === 'yes'),
                    'note' => $promise['note']
                ];
            }
        }

        $html = '';
        foreach ($rehearsals as $rehearsal) {
            $status = 'pending';
            $note = '';

            if (isset($promises[$rehearsal['id']])) {
                $status = $promises[$rehearsal['id']]['attending'] ? 'attending' : 'not_attending';
                $note = $promises[$rehearsal['id']]['note'];
            }

            $groupArray = $rehearsal['groups'] ?? [];
            $smartDisplay = new \App\Core\SmartGroupDisplay();
            $groupsText = $smartDisplay->generateDescription($groupArray, $rehearsal, false);

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

        echo $html;

        if ($hasMore) {
            $base = '/' . ($_SESSION['current_org_slug'] ?? '') . '/' . ($_SESSION['current_orchestra_slug'] ?? '');
            $nextUrl = htmlspecialchars($base . '/promises/index-lazy?offset=' . $nextOffset);
            echo '<div data-lazy-next-url="' . $nextUrl . '" style="display:none"></div>';
        }
    }

    /**
     * AJAX endpoint: returns a batch of past rehearsal cards for the player view.
     */
    public function indexPast($params = [])
    {
        $this->validateOrchestraContext($params);

        $userType = $_SESSION['current_type'];
        $userId = $_SESSION['user_id'] ?? null;
        $orchestraId = $_SESSION['current_orchestra_id'] ?? null;
        
        $userRoleIds = [];
        if ($userId && $orchestraId) {
            $userOrchestraModel = new \App\Models\UserOrchestra();
            $userRoles = $userOrchestraModel->getUserRoles((int)$userId, (int)$orchestraId);
            $userRoleIds = array_map(fn($r) => (int)$r['id'], $userRoles);
        }

        $offset = max(0, (int)($_GET['offset'] ?? 0));
        $allRehearsals = $this->rehearsalModel->getForUser($userType, $orchestraId, true, $userRoleIds);
        
        $today = date('Y-m-d');
        $pastRehearsals = array_filter($allRehearsals, fn($r) => $r['date'] < $today);
        usort($pastRehearsals, fn($a, $b) => strcmp($b['date'] . ' ' . $b['start'], $a['date'] . ' ' . $a['start']));
        
        $remaining = array_slice($pastRehearsals, $offset);
        $rehearsals = array_reverse(array_slice($remaining, 0, 5));
        $hasMore = count($remaining) > 5;
        $nextOffset = $offset + count($rehearsals);

        if (empty($rehearsals)) {
            echo '';
            return;
        }

        $promises = [];
        if ($userId) {
            $userPromises = $this->userModel->getPromises($userId);
            foreach ($userPromises as $promise) {
                $promises[$promise['rehearsal_id']] = [
                    'attending' => ($promise['status'] === 'yes'),
                    'note' => $promise['note']
                ];
            }
        }

        $html = '';
        foreach ($rehearsals as $rehearsal) {
            $status = 'pending';
            $note = '';

            if (isset($promises[$rehearsal['id']])) {
                $status = $promises[$rehearsal['id']]['attending'] ? 'attending' : 'not_attending';
                $note = $promises[$rehearsal['id']]['note'];
            }

            $groupArray = $rehearsal['groups'] ?? [];
            $smartDisplay = new \App\Core\SmartGroupDisplay();
            $groupsText = $smartDisplay->generateDescription($groupArray, $rehearsal, false);

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

        echo $html;

        if ($hasMore) {
            $base = '/' . ($_SESSION['current_org_slug'] ?? '') . '/' . ($_SESSION['current_orchestra_slug'] ?? '');
            $nextUrl = htmlspecialchars($base . '/promises/index-past?offset=' . $nextOffset);
            echo '<div data-lazy-next-url="' . $nextUrl . '" style="display:none"></div>';
        }
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

        $userType = $_SESSION['current_type'];
        $sectionName = str_replace(' ', '_', $userType);

        $userRoleIds = [];
        $userId = $_SESSION['user_id'] ?? null;
        $orchestraId = $_SESSION['current_orchestra_id'] ?? null;
        if ($userId && $orchestraId) {
            $userOrchestraModel = new \App\Models\UserOrchestra();
            $userRoles = $userOrchestraModel->getUserRoles((int)$userId, (int)$orchestraId);
            $userRoleIds = array_map(fn($r) => (int)$r['id'], $userRoles);
        }
        $allRehearsals = $this->rehearsalModel->getForUser($sectionName, $_SESSION['current_orchestra_id'], false, $userRoleIds);

        $totalRehearsals = count($allRehearsals);
        $hasMore = $totalRehearsals > self::INITIAL_LIMIT;
        $rehearsals = $hasMore ? array_slice($allRehearsals, 0, self::INITIAL_LIMIT) : $allRehearsals;

        // Analytics rehearsals (last 10, independent of lazy loading)
        $analyticsRehearsals = array_slice($allRehearsals, 0, 10);

        $orchestraModel = new \App\Models\Orchestra();
        $orchestra = $orchestraModel->findById($_SESSION['current_orchestra_id']);
        $canViewAllSections = !empty($_SESSION['current_permissions']['can_view_all_section_stats']);
        $viewAllSections = $canViewAllSections && isset($_GET['viewAll']) && $_GET['viewAll'] === '1';

        $members = $this->leaderScopedMembers($viewAllSections, $userType, (int)$_SESSION['current_orchestra_id']);

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
                        'roles' => $member['roles'] ?? [],
                        'push_sub' => self::pushSub($member)
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

        // Analytics stats (last 10, independent of lazy loading)
        [$analyticsStats] = $this->buildLeaderRehearsalData($analyticsRehearsals, $viewAllSections, $members, $userType, $orchestraId);

        $this->render('promises/leader', [
            'currentPage' => 'leader',
            'rehearsals' => $rehearsals,
            'stats' => $stats,
            'membersBySection' => $membersBySection,
            'memberPromises' => $memberPromises,
            'analyticsRehearsals' => $analyticsRehearsals,
            'analyticsStats' => $analyticsStats,
            'canViewAllSections' => $canViewAllSections,
            'currentlyViewingAll' => $viewAllSections,
            'leaderSection' => $leaderSectionId,
            'leaderSectionDisplayName' => $groupManager->getDisplayName($leaderSectionId),
            'leaderSectionNames' => $leaderSectionNames,
            'isLeaderOnlyView' => !$viewAllSections,
            'leaderResolvedType' => $leaderResolvedType,
            'hasPastRehearsals' => $hasPastRehearsals,
            'hasMoreRehearsals' => $hasMore,
            'totalRehearsals' => $totalRehearsals
        ]);
    }

    /**
     * AJAX endpoint: returns a batch of leader dashboard rehearsal cards as HTML partial.
     */
    public function leaderLazy($params = [])
    {
        $this->validateOrchestraContext($params);
        $this->requireAnyPermission('can_view_own_section_stats', 'can_view_parent_section_stats', 'can_view_all_section_stats');

        $userType = $_SESSION['current_type'];
        $sectionName = str_replace(' ', '_', $userType);

        $userRoleIds = [];
        $userId = $_SESSION['user_id'] ?? null;
        $orchestraId = $_SESSION['current_orchestra_id'] ?? null;
        if ($userId && $orchestraId) {
            $userOrchestraModel = new \App\Models\UserOrchestra();
            $userRoles = $userOrchestraModel->getUserRoles((int)$userId, (int)$orchestraId);
            $userRoleIds = array_map(fn($r) => (int)$r['id'], $userRoles);
        }

        $offset = max(0, (int)($_GET['offset'] ?? self::INITIAL_LIMIT));
        $allRehearsals = $this->rehearsalModel->getForUser($sectionName, $orchestraId, false, $userRoleIds);
        $remaining = array_slice($allRehearsals, $offset);
        $rehearsals = array_slice($remaining, 0, self::LAZY_BATCH_SIZE);
        $hasMore = count($remaining) > self::LAZY_BATCH_SIZE;
        $nextOffset = $offset + count($rehearsals);

        if (empty($rehearsals)) {
            echo '';
            return;
        }

        $canViewAllSections = !empty($_SESSION['current_permissions']['can_view_all_section_stats']);
        $viewAllSections = $canViewAllSections && isset($_GET['viewAll']) && $_GET['viewAll'] === '1';

        $groupManager = \App\Core\GroupManager::getInstance();
        $members = $this->leaderScopedMembers($viewAllSections, $userType, (int)$orchestraId);

        [$stats, $membersBySection, $memberPromises, $leaderSectionId, $leaderResolvedType, $leaderSectionNames] =
            $this->buildLeaderRehearsalData($rehearsals, $viewAllSections, $members, $userType, $orchestraId);

        $orchestraModel = new \App\Models\Orchestra();
        $orchestra = $orchestraModel->findById($_SESSION['current_orchestra_id']);
        $showRehearsalInsights = !empty($orchestra['show_rehearsal_insights']);

        $deviationData = $this->computeDeviationData($rehearsals, $stats, $membersBySection, $showRehearsalInsights);

        $this->renderPartial('components/promises-dashboard-wrapper', [
            'rehearsals' => $rehearsals,
            'stats' => $stats,
            'membersBySection' => $membersBySection,
            'memberPromises' => $memberPromises,
            'canViewAllSections' => $canViewAllSections,
            'currentlyViewingAll' => $viewAllSections,
            'leaderSection' => $leaderSectionId,
            'leaderSectionDisplayName' => $groupManager->getDisplayName($leaderSectionId),
            'leaderSectionNames' => $leaderSectionNames,
            'isLeaderOnlyView' => !$viewAllSections,
            'leaderResolvedType' => $leaderResolvedType,
            'showRehearsalInsights' => $showRehearsalInsights,
            'deviationData' => $deviationData,
            'isAdmin' => false,
            'isLeader' => true,
            'lazyPartial' => true,
        ]);

        if ($hasMore) {
            $base = '/' . ($_SESSION['current_org_slug'] ?? '') . '/' . ($_SESSION['current_orchestra_slug'] ?? '');
            $nextUrl = htmlspecialchars($base . '/promises/leader-lazy?offset=' . $nextOffset . ($viewAllSections ? '&viewAll=1' : ''));
            echo '<div data-lazy-next-url="' . $nextUrl . '" style="display:none"></div>';
        }
    }

    /**
     * AJAX endpoint: returns a batch of leader dashboard past rehearsal cards.
     */
    public function leaderPast($params = [])
    {
        $this->validateOrchestraContext($params);
        $this->requireAnyPermission('can_view_own_section_stats', 'can_view_parent_section_stats', 'can_view_all_section_stats');

        $userType = $_SESSION['current_type'];
        $sectionName = str_replace(' ', '_', $userType);
        $orchestraId = $_SESSION['current_orchestra_id'];

        $offset = max(0, (int)($_GET['offset'] ?? 0));
        $result = $this->rehearsalModel->getPastPaginated(
            (int)$orchestraId,
            $offset,
            5
        );

        $rehearsals = array_reverse($result['rows']);
        if (empty($rehearsals)) {
            echo '';
            return;
        }

        $canViewAllSections = !empty($_SESSION['current_permissions']['can_view_all_section_stats']);
        $viewAllSections = $canViewAllSections && isset($_GET['viewAll']) && $_GET['viewAll'] === '1';

        $groupManager = \App\Core\GroupManager::getInstance();
        $members = $this->leaderScopedMembers($viewAllSections, $userType, (int)$orchestraId);

        [$stats, $membersBySection, $memberPromises, $leaderSectionId, $leaderResolvedType, $leaderSectionNames] =
            $this->buildLeaderRehearsalData($rehearsals, $viewAllSections, $members, $userType, $orchestraId);

        $orchestraModel = new \App\Models\Orchestra();
        $orchestra = $orchestraModel->findById($orchestraId);
        $showRehearsalInsights = !empty($orchestra['show_rehearsal_insights']);
        $deviationData = $this->computeDeviationData($rehearsals, $stats, $membersBySection, $showRehearsalInsights);

        $this->renderPartial('components/promises-dashboard-wrapper', [
            'rehearsals' => $rehearsals,
            'stats' => $stats,
            'membersBySection' => $membersBySection,
            'memberPromises' => $memberPromises,
            'canViewAllSections' => $canViewAllSections,
            'currentlyViewingAll' => $viewAllSections,
            'leaderSection' => $leaderSectionId,
            'leaderSectionDisplayName' => $groupManager->getDisplayName($leaderSectionId) ?? '',
            'leaderSectionNames' => $leaderSectionNames,
            'isLeaderOnlyView' => !$viewAllSections,
            'leaderResolvedType' => $leaderResolvedType,
            'showRehearsalInsights' => $showRehearsalInsights,
            'deviationData' => $deviationData,
            'isAdmin' => false,
            'isLeader' => true,
            'lazyPartial' => true,
        ]);

        $nextOffset = $offset + count($rehearsals);
        if ($nextOffset < $result['total']) {
            $base = '/' . ($_SESSION['current_org_slug'] ?? '') . '/' . ($_SESSION['current_orchestra_slug'] ?? '');
            $nextUrl = htmlspecialchars($base . '/promises/leader-past?offset=' . $nextOffset . ($viewAllSections ? '&viewAll=1' : ''));
            echo '<div data-lazy-next-url="' . $nextUrl . '" style="display:none"></div>';
        }
    }

    /**
     * Members the leader dashboard covers: the whole orchestra (viewAll), the leader's
     * parent section (can_view_parent_section_stats) or the leader's own section.
     */
    private function leaderScopedMembers(bool $viewAllSections, string $userType, int $orchestraId): array
    {
        $userOrchestraModel = new \App\Models\UserOrchestra();
        $allMembers = $userOrchestraModel->getOrchestraUsers($orchestraId);
        if ($viewAllSections) {
            return $allMembers;
        }

        $groupManager = \App\Core\GroupManager::getInstance();
        $resolvedType = $groupManager->resolveAlias($userType);

        if (!empty($_SESSION['current_permissions']['can_view_parent_section_stats'])) {
            $leaderSection = $groupManager->getSectionForInstrument($resolvedType);
            return array_filter($allMembers, function ($m) use ($groupManager, $resolvedType, $leaderSection) {
                $memberType = $groupManager->resolveAlias($m['type']);
                if ($leaderSection) {
                    return $groupManager->getSectionForInstrument($memberType) === $leaderSection;
                }
                return $memberType === $resolvedType;
            });
        }

        return array_filter($allMembers, function ($m) use ($groupManager, $resolvedType) {
            return $groupManager->resolveAlias($m['type']) === $resolvedType;
        });
    }

    /**
     * Keycloak ID of a member who can get a JOinJMD push, or null. Members who never
     * opened the Probenplaner (last_login_at NULL) are not reminded.
     */
    private static function pushSub(array $member): ?string
    {
        return !empty($member['keycloak_id']) && !empty($member['last_login_at']) ? (string)$member['keycloak_id'] : null;
    }

    /**
     * Helper to build leader rehearsal data for charts/stats
     */
    private function buildLeaderRehearsalData(array $rehearsals, bool $viewAllSections, array $members, string $userType, int $orchestraId): array
    {
        if (!is_array($members)) $members = [];
        $members = array_values(array_filter($members, function ($m) { return !empty($m['can_attend_rehearsals']); }));

        $groupManager = \App\Core\GroupManager::getInstance();
        $allSections = $groupManager->getAllSections();
        if (empty($allSections)) {
            $allSections = array_filter($groupManager->getAllGroups(), fn($g) => ($g['type'] ?? '') !== 'special');
        }

        $promiseModel = new \App\Models\UserPromise();
        $allPromises = $promiseModel->getAllForOrchestra($orchestraId);

        $stats = [];
        $membersBySection = [];
        $memberPromises = [];

        $leaderResolvedType = $groupManager->resolveAlias($userType);
        $leaderSectionInfo = $groupManager->getSectionForInstrument($leaderResolvedType);
        $leaderSectionId = $leaderSectionInfo ?? $leaderResolvedType;

        $rehearsalIds = array_column($rehearsals, 'id');
        $groupsMap = [];
        $rolesMap = [];
        if (!empty($rehearsalIds)) {
            foreach ($rehearsals as $r) {
                $assoc = [];
                foreach ($r['groups'] ?? [] as $name) $assoc[$name] = 0;
                $groupsMap[$r['id']] = $assoc;
            }
            $rolesMap = $this->rehearsalModel->getBatchRehearsalRoleIds($rehearsalIds);
        }

        foreach ($rehearsals as $rehearsal) {
            $rehearsalId = $rehearsal['id'];
            $stats[$rehearsalId] = ['attending' => 0, 'not_attending' => 0, 'no_response' => 0];

            if (!$viewAllSections) {
                $membersBySection[$rehearsalId] = ['all' => []];
                $membersBySection[$rehearsalId][$leaderSectionId] = [];
            } else {
                $membersBySection[$rehearsalId] = ['all' => []];
                foreach ($allSections as $sectionId => $sectionData) $membersBySection[$rehearsalId][$sectionId] = [];
            }

            $memberPromises[$rehearsalId] = ['attending' => [], 'not_attending' => [], 'no_response' => []];

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

                    if (!$viewAllSections) {
                        $memberResolvedType = $groupManager->resolveAlias($member['type']);
                        $memberSectionInfo = $groupManager->getSectionForInstrument($memberResolvedType);
                        if ($leaderSectionInfo) {
                            $belongsToLeaderSection = $leaderSectionInfo === $memberSectionInfo;
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
                        'roles' => $member['roles'] ?? [],
                        'push_sub' => self::pushSub($member)
                    ];

                    $membersBySection[$rehearsalId]['all'][] = $memberInfo;

                    $category = $status === 'attending' ? 'attending' : ($status === 'not_attending' ? 'not_attending' : 'no_response');
                    $promiseEntry = [
                        'display_name' => $memberInfo['display_name'],
                        'type' => $memberInfo['type'],
                    ];
                    if ($status !== 'no_response') $promiseEntry['note'] = $note;
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

        return [$stats, $membersBySection, $memberPromises, $leaderSectionId, $leaderResolvedType, $leaderSectionNames];
    }


    /**
     * Update promise
     * 
     * @return void
     */
    public function update($params = [])
    {
        if (!$this->isLoggedIn()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Nicht eingeloggt', 'details' => 'Bitte melden Sie sich erneut an.']);
            return;
        }

        // Orchestra + permissions from the URL, not from whatever was opened last in the session
        $ctx = $this->validateOrchestraContext($params);

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

        if ($this->rejectInvalidCsrfToken()) {
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

        $rehearsal = $this->findRehearsalInContext($rehearsalId, $ctx);
        if (!$rehearsal) {
            $this->respondRehearsalNotFound();
            return;
        }

        $user = $this->userModel->findById((int)$_SESSION['user_id']);

        if (!$user) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Benutzer nicht gefunden', 'details' => 'Ihr Benutzerkonto wurde nicht gefunden. Bitte melden Sie sich erneut an.']);
            return;
        }

        $orchestraModel = new \App\Models\Orchestra();
        $orchestra = $orchestraModel->findById((int)$ctx['orchestra_id']);

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
            if ($rehearsal['date'] < date('Y-m-d')) {
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
    public function note($params = [])
    {
        if (!$this->isLoggedIn()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Nicht eingeloggt', 'details' => 'Bitte melden Sie sich erneut an.']);
            return;
        }

        // Orchestra + permissions from the URL, not from whatever was opened last in the session
        $ctx = $this->validateOrchestraContext($params);

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

        if ($this->rejectInvalidCsrfToken()) {
            return;
        }

        $rehearsalId = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $note = Validator::sanitizeUtf8($_POST['note'] ?? '');

        if ($rehearsalId <= 0) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Ungültige Proben-ID', 'details' => 'Die angegebene Proben-ID ist ungültig.']);
            return;
        }

        if (!$this->findRehearsalInContext($rehearsalId, $ctx)) {
            $this->respondRehearsalNotFound();
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
     * JOinJMD push to the members a dashboard card lists without response, limited to
     * members with a Keycloak ID who have opened the Probenplaner before.
     * view=admin: admin dashboard (whole orchestra); otherwise the leader dashboard scope
     * (own/parent section, whole orchestra with view_all=1).
     */
    public function remind($params = [])
    {
        $ctx = $this->validateOrchestraContext($params);
        $isAdminView = ($_POST['view'] ?? '') === 'admin';
        $allowed = $isAdminView
            ? $this->requirePermission('can_manage_rehearsals')
            : $this->requireAnyPermission('can_view_own_section_stats', 'can_view_parent_section_stats', 'can_view_all_section_stats');
        if (!$allowed) {
            return;
        }

        header('Content-Type: application/json');
        if (!JmdPush::isEnabled()) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Push-Benachrichtigungen sind nicht eingerichtet']);
            return;
        }

        $orchestraId = (int)$ctx['orchestra_id'];
        $rehearsalId = (int)($_POST['rehearsal_id'] ?? 0);
        $rehearsal = $isAdminView
            ? ($rehearsalId > 0 ? $this->rehearsalModel->findById($rehearsalId) : null)
            : $this->findRehearsalInContext($rehearsalId, $ctx);
        if (!$rehearsal || (int)$rehearsal['orchestra_id'] !== $orchestraId || strtotime($rehearsal['start']) <= time()) {
            echo json_encode(['success' => false, 'error' => 'Probe nicht gefunden']);
            return;
        }

        if ($isAdminView) {
            [, $membersBySection] = $this->buildAdminRehearsalData([$rehearsal]);
        } else {
            $userType = (string)($_SESSION['current_type'] ?? '');
            $viewAll = !empty($_SESSION['current_permissions']['can_view_all_section_stats']) && ($_POST['view_all'] ?? '') === '1';
            [, $membersBySection] = $this->buildLeaderRehearsalData(
                [$rehearsal], $viewAll, $this->leaderScopedMembers($viewAll, $userType, $orchestraId), $userType, $orchestraId
            );
        }

        $subs = [];
        foreach ($membersBySection[$rehearsal['id']]['all'] ?? [] as $member) {
            if ($member['status'] === 'no_response' && !empty($member['push_sub'])) {
                $subs[] = $member['push_sub'];
            }
        }
        $subs = array_values(array_unique($subs));

        if (empty($subs)) {
            echo json_encode(['success' => true, 'sent' => 0, 'total' => 0]);
            return;
        }

        $orchestra = $ctx['orchestra'];
        $text = JmdPush::missingResponseText($rehearsal, (string)$orchestra['name']);
        $sent = JmdPush::send($subs, $text['title'], $text['body'], JmdPush::rehearsalLink($orchestra, (int)$rehearsal['id']));
        if ($sent === null) {
            echo json_encode(['success' => false, 'error' => 'Erinnerung konnte nicht gesendet werden']);
            return;
        }

        echo json_encode(['success' => true, 'sent' => $sent, 'total' => count($subs)]);
    }

    /**
     * Load a rehearsal only if it belongs to the orchestra from the URL context and
     * is one the current user is invited to, using the same group + role targeting as
     * the promise list (Rehearsal::getForUser with the session's type and the user's
     * roles in this orchestra). Anything else is treated exactly like a missing rehearsal.
     *
     * @param array $ctx Result of validateOrchestraContext()
     */
    private function findRehearsalInContext(int $rehearsalId, array $ctx): ?array
    {
        $rehearsal = $rehearsalId > 0 ? $this->rehearsalModel->findById($rehearsalId) : null;
        $orchestraId = (int)$ctx['orchestra_id'];
        if (!$rehearsal || (int)($rehearsal['orchestra_id'] ?? 0) !== $orchestraId) {
            return null;
        }

        $userType = (string)($_SESSION['current_type'] ?? ($ctx['user_type'] ?? ''));
        $userOrchestraModel = new \App\Models\UserOrchestra();
        $userRoleIds = array_map(
            fn($r) => (int)$r['id'],
            $userOrchestraModel->getUserRoles((int)$_SESSION['user_id'], $orchestraId)
        );

        $isInvited = $this->rehearsalModel->isUserInRehearsalGroup(
            $userType,
            $this->rehearsalModel->getGroupsAsAssoc($rehearsalId),
            $this->rehearsalModel->getRehearsalRoleIds($rehearsalId),
            $userRoleIds
        );

        return $isInvited ? $rehearsal : null;
    }

    /**
     * Same JSON shape as the other promise errors (the client handles success:false;
     * a deleted rehearsal must not look like a network error).
     */
    private function respondRehearsalNotFound(): void
    {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Probe nicht gefunden', 'details' => 'Die Probe existiert nicht (mehr) oder ist nicht für Sie vorgesehen.']);
    }

    /**
     * Writes need the session's CSRF token (csrf_token field or X-CSRF-Token header).
     * On failure the answer uses this file's JSON style (success:false, readable 'error')
     * so the client shows the hint instead of a generic network error.
     *
     * @return bool True when the request was rejected (response already sent)
     */
    private function rejectInvalidCsrfToken(): bool
    {
        if (\App\Core\CSRF::validateRequest()) {
            return false;
        }

        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Sicherheitsprüfung fehlgeschlagen',
            'details' => 'Bitte laden Sie die Seite neu und versuchen Sie es erneut.',
            'error'   => 'Sicherheitsprüfung fehlgeschlagen. Bitte laden Sie die Seite neu.'
        ]);
        return true;
    }

    /**
     * Display admin view for directors
     * 
     * @return void
     */
    /**
     * Initial render limit for the dashboard.
     * Rehearsals beyond this count are lazy-loaded via adminLazy() or indexLazy()
     */
    private const INITIAL_LIMIT = 5;

    public function admin($params = [])
    {
        $this->validateOrchestraContext($params);
        $this->requirePermission('can_manage_rehearsals');

        $allRehearsals = $this->rehearsalModel->getUpcoming($_SESSION['current_orchestra_id'], false);
        $totalRehearsals = count($allRehearsals);
        $hasMore = $totalRehearsals > self::INITIAL_LIMIT;

        $rehearsals = $hasMore ? array_slice($allRehearsals, 0, self::INITIAL_LIMIT) : $allRehearsals;

        // Stats for displayed cards
        [$stats, $membersBySection] = $this->buildAdminRehearsalData($rehearsals);

        // Stats for analytics (last 10, independent of lazy loading)
        $analyticsRehearsals = array_slice($allRehearsals, 0, 10);
        [$analyticsStats] = $this->buildAdminRehearsalData($analyticsRehearsals);

        $orchestraModel = new \App\Models\Orchestra();
        $orchestra = $orchestraModel->findById($_SESSION['current_orchestra_id']);
        $showRehearsalInsights = !empty($orchestra['show_rehearsal_insights']);

        $hasPastRehearsals = $this->rehearsalModel->hasPastRehearsals($_SESSION['current_orchestra_id']);

        $deviationData = $this->computeDeviationData($rehearsals, $stats, $membersBySection, $showRehearsalInsights);

        $this->render('promises/admin', [
            'currentPage' => 'admin',
            'rehearsals' => $rehearsals,
            'stats' => $stats,
            'membersBySection' => $membersBySection,
            'analyticsRehearsals' => $analyticsRehearsals,
            'analyticsStats' => $analyticsStats,
            'showRehearsalInsights' => $showRehearsalInsights,
            'hasPastRehearsals' => $hasPastRehearsals,
            'deviationData' => $deviationData,
            'hasMoreRehearsals' => $hasMore,
            'totalRehearsals' => $totalRehearsals,
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
     * AJAX endpoint: returns a batch of admin rehearsal cards as HTML partial.
     * Supports progressive loading via offset + batch size.
     */
    private const LAZY_BATCH_SIZE = 10;

    public function adminLazy($params = [])
    {
        $this->validateOrchestraContext($params);
        $this->requireAnyPermission('can_view_all_section_stats', 'can_manage_ensemble');

        $offset = max(0, (int)($_GET['offset'] ?? self::INITIAL_LIMIT));
        $allRehearsals = $this->rehearsalModel->getUpcoming($_SESSION['current_orchestra_id'], false);
        $remaining = array_slice($allRehearsals, $offset);
        $rehearsals = array_slice($remaining, 0, self::LAZY_BATCH_SIZE);
        $hasMore = count($remaining) > self::LAZY_BATCH_SIZE;
        $nextOffset = $offset + count($rehearsals);

        if (empty($rehearsals)) {
            echo '';
            return;
        }

        [$stats, $membersBySection] = $this->buildAdminRehearsalData($rehearsals);

        $orchestraModel = new \App\Models\Orchestra();
        $orchestra = $orchestraModel->findById($_SESSION['current_orchestra_id']);
        $showRehearsalInsights = !empty($orchestra['show_rehearsal_insights']);

        $deviationData = $this->computeDeviationData($rehearsals, $stats, $membersBySection, $showRehearsalInsights);

        $this->renderPartial('components/promises-dashboard-wrapper', [
            'rehearsals' => $rehearsals,
            'stats' => $stats,
            'membersBySection' => $membersBySection,
            'showRehearsalInsights' => $showRehearsalInsights,
            'deviationData' => $deviationData,
            'isAdmin' => true,
            'lazyPartial' => true,
        ]);

        if ($hasMore) {
            $base = '/' . ($_SESSION['current_org_slug'] ?? '') . '/' . ($_SESSION['current_orchestra_slug'] ?? '');
            $nextUrl = htmlspecialchars($base . '/promises/admin-lazy?offset=' . $nextOffset);
            echo '<div data-lazy-next-url="' . $nextUrl . '" style="display:none"></div>';
        }
    }

    /**
     * AJAX endpoint: returns a batch of past rehearsal cards.
     */
    public function adminPast($params = [])
    {
        $this->validateOrchestraContext($params);
        $this->requireAnyPermission('can_view_all_section_stats', 'can_manage_ensemble');

        $offset = max(0, (int)($_GET['offset'] ?? 0));
        $result = $this->rehearsalModel->getPastPaginated(
            (int)$_SESSION['current_orchestra_id'],
            $offset,
            5
        );

        $rehearsals = array_reverse($result['rows']);
        if (empty($rehearsals)) {
            echo '';
            return;
        }

        [$stats, $membersBySection] = $this->buildAdminRehearsalData($rehearsals);

        $orchestraModel = new \App\Models\Orchestra();
        $orchestra = $orchestraModel->findById($_SESSION['current_orchestra_id']);
        $showRehearsalInsights = !empty($orchestra['show_rehearsal_insights']);

        $deviationData = $this->computeDeviationData($rehearsals, $stats, $membersBySection, $showRehearsalInsights);

        $this->renderPartial('components/promises-dashboard-wrapper', [
            'rehearsals' => $rehearsals,
            'stats' => $stats,
            'membersBySection' => $membersBySection,
            'showRehearsalInsights' => $showRehearsalInsights,
            'deviationData' => $deviationData,
            'isAdmin' => true,
            'lazyPartial' => true,
        ]);

        $nextOffset = $offset + count($rehearsals);
        if ($nextOffset < $result['total']) {
            $base = '/' . ($_SESSION['current_org_slug'] ?? '') . '/' . ($_SESSION['current_orchestra_slug'] ?? '');
            $nextUrl = htmlspecialchars($base . '/promises/admin-past?offset=' . $nextOffset);
            echo '<div data-lazy-button-url="' . $nextUrl . '" style="display:none"></div>';
        }
    }

    /**
     * Build stats and membersBySection arrays for a set of rehearsals.
     *
     * @return array{0: array, 1: array} [$stats, $membersBySection]
     */
    private function buildAdminRehearsalData(array $rehearsals): array
    {
        $userOrchestraModel = new \App\Models\UserOrchestra();
        $users = $userOrchestraModel->getOrchestraUsers($_SESSION['current_orchestra_id']);

        $groupManager = \App\Core\GroupManager::getInstance();
        $allSections = $groupManager->getAllSections();
        if (empty($allSections)) {
            $allSections = array_filter($groupManager->getAllGroups(), fn($g) => ($g['type'] ?? '') !== 'special');
        }

        $promiseModel = new \App\Models\UserPromise();
        $allPromises = $promiseModel->getAllForOrchestra($_SESSION['current_orchestra_id']);

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

        $stats = [];
        $membersBySection = [];

        foreach ($rehearsals as $rehearsal) {
            $rehearsalId = $rehearsal['id'];
            $stats[$rehearsalId] = ['attending' => 0, 'not_attending' => 0, 'no_response' => 0];

            $membersBySection[$rehearsalId] = ['all' => []];
            foreach ($allSections as $sectionId => $sectionData) {
                $membersBySection[$rehearsalId][$sectionId] = [];
            }

            $groups = $groupsMap[$rehearsalId] ?? [];
            $rehearsalRoleIds = $rolesMap[$rehearsalId] ?? [];

            foreach ($users as $user) {
                if (empty($user['can_attend_rehearsals'])) continue;

                $memberRoleIds = $user['role_ids'] ?? [];
                if (!$this->rehearsalModel->isUserInRehearsalGroup($user['type'], $groups, $rehearsalRoleIds, $memberRoleIds)) {
                    continue;
                }

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
                    'roles' => $user['roles'] ?? [],
                    'push_sub' => self::pushSub($user)
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

        return [$stats, $membersBySection];
    }

    /**
     * Pre-compute deviation analysis for a set of rehearsals.
     */
    private function computeDeviationData(array $rehearsals, array $stats, array $membersBySection, bool $showInsights): array
    {
        if (!$showInsights) return [];

        require_once __DIR__ . '/../Core/SmartDeviationDetector.php';
        $detector = new \SmartDeviationDetector(\App\Core\Database::getInstance());
        $data = [];
        foreach ($rehearsals as $rehearsal) {
            $rId = $rehearsal['id'];
            $data[$rId] = $detector->analyzeRehearsalFromData(
                $rehearsal,
                $stats[$rId] ?? ['attending' => 0, 'not_attending' => 0, 'no_response' => 0],
                $membersBySection[$rId]['all'] ?? []
            );
        }
        return $data;
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
            error_log('Loading past rehearsals failed: ' . $e->getMessage());
            header('Content-Type: application/json');
            http_response_code(500);
            $response = [
                'success' => false,
                'message' => 'Fehler beim Laden der Termine',
            ];
            if (defined('APP_ENV') && APP_ENV === 'development') {
                $response['debug_message'] = $e->getMessage();
            }
            echo json_encode($response);
            exit;
        }
    }
}
