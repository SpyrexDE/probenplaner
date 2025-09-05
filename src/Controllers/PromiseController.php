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
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->userModel = new User();
        $this->rehearsalModel = new Rehearsal();
    }
    
    /**
     * Display user promises (attendance)
     * 
     * @return void
     */
    public function index()
    {
        // Check if user is logged in
        if (!$this->isLoggedIn()) {
            $this->redirect('/login');
            return;
        }
        
        // Redirect admins to the admin page
        if ($_SESSION['type'] === 'Dirigent') {
            $this->redirect('/promises/admin');
            return;
        }
        
        // Get show old parameter
        $showOld = isset($_GET['showOld']);
        
        // Get rehearsals for the user's type using modern system
        $userType = $_SESSION['type'];
        $user = ['is_small_group' => $_SESSION['is_small_group'] ?? \App\Core\RehearsalTypeManager::SMALL_GROUP_DISABLED];
        $isSmallGroup = \App\Core\RehearsalTypeManager::isUserInSmallGroup($user);
        $rehearsals = $this->rehearsalModel->getForUser($userType, $_SESSION['orchestra_id'], $showOld, $isSmallGroup);
        
        // Get user's promises from the user_promises table
        $promises = [];
        $user = $this->userModel->findByUsername($_SESSION['username']);
        
        if ($user) {
            $userPromises = $this->userModel->getPromises($user['id']);
            foreach ($userPromises as $promise) {
                $promises[$promise['rehearsal_id']] = [
                    'attending' => (bool)$promise['attending'],
                    'note' => $promise['note']
                ];
            }
        }
        
        // Determine current page based on URL
        $currentPage = 'promises';
        if (strpos($_SERVER['REQUEST_URI'], '/rehearsals') === 0) {
            $currentPage = 'rehearsals';
        }
        
        // Render view
        $this->render('promises/index', [
            'currentPage' => $currentPage,
            'rehearsals' => $rehearsals,
            'promises' => $promises,
            'showOld' => $showOld
        ]);
    }
    
    /**
     * Display section leader view of member promises
     * 
     * @return void
     */
    public function leader()
    {
        // Check if user is logged in and is a section leader
        if (!$this->isLoggedIn()) {
            $this->redirect('/login');
            return;
        }

        // Check if user is a section leader
        $username = $_SESSION['username'];
        if ($_SESSION['role'] !== 'leader') {
            $this->redirect('/promises');
            return;
        }
        
        // Get show old parameter
        $showOld = isset($_GET['showOld']);
        
        // Get user type (section)
        $userType = $_SESSION['type'];

        // Clean up section name for database queries
        $sectionName = str_replace(' ', '_', $userType);
        
        // Get rehearsals for the section using modern system
        $user = ['is_small_group' => $_SESSION['is_small_group'] ?? \App\Core\RehearsalTypeManager::SMALL_GROUP_DISABLED];
        $isSmallGroup = \App\Core\RehearsalTypeManager::isUserInSmallGroup($user);
        $rehearsals = $this->rehearsalModel->getForUser($sectionName, $_SESSION['orchestra_id'], $showOld, $isSmallGroup);
        
        // Get orchestra to check settings (leaders can view all sections?)
        $orchestraModel = new \App\Models\Orchestra();
        $orchestra = $orchestraModel->findById($_SESSION['orchestra_id']);
        $leadersCanViewAllEnabled = !empty($orchestra['leaders_can_view_all_sections']);
        
        // Check if user wants to view all sections (from URL parameter or toggle state)
        // Default to own section view when the feature is enabled
        $viewAllSections = isset($_GET['viewAll']) && $_GET['viewAll'] === '1';
        $leadersCanViewAll = $leadersCanViewAllEnabled && $viewAllSections;
        

        // Get members: either only own section or all sections based on setting
        if ($leadersCanViewAll) {
            $members = $this->userModel->getOrchestraMembers($_SESSION['orchestra_id']);
        } else {
            $members = $this->userModel->findByType($sectionName, $_SESSION['orchestra_id']);
            
            // If no exact match found, try with the original user type (without underscore replacement)
            if (empty($members)) {
                $members = $this->userModel->findByType($userType, $_SESSION['orchestra_id']);
            }
            
            // If still no exact match, search for similar section names using GroupManager
            if (empty($members)) {
                $groupManager = new \App\Core\GroupManager();
                $resolvedType = $groupManager->resolveAlias($userType);
                $allMembers = $this->userModel->getOrchestraMembers($_SESSION['orchestra_id']);
                
                // Filter members that belong to the leader's section/group
                $members = array_filter($allMembers, function($member) use ($groupManager, $resolvedType, $userType) {
                    $memberType = $groupManager->resolveAlias($member['type']);
                    $leaderSectionInfo = $groupManager->getSectionForInstrument($resolvedType);
                    $memberSectionInfo = $groupManager->getSectionForInstrument($memberType);
                    
                    // Check if they're in the same section
                    if ($leaderSectionInfo && $memberSectionInfo) {
                        return $leaderSectionInfo === $memberSectionInfo;
                    }
                    
                    // Fallback: check if the member type matches the leader type
                    return $memberType === $resolvedType || $member['type'] === $userType;
                });
                
                if (!empty($members)) {
                    $memberTypes = array_map(function($m) { return $m['type']; }, $members);
                }
            }
        }
        
        // Ensure we have an array
        if (!is_array($members)) {
            $members = [];
        }
        
        // Exclude conductors from leader view
        $members = array_values(array_filter($members, function($m) {
            $role = isset($m['role']) ? $m['role'] : '';
            return $role !== 'conductor';
        }));
        
        // Initialize GroupManager for dynamic section handling
        $groupManager = new \App\Core\GroupManager();
        $allSections = $groupManager->getAllSections();
        
        // Calculate statistics for each rehearsal (similar to admin view)
        $stats = [];
        $membersBySection = [];
        
        // Get leader's section information for data processing
        $leaderResolvedType = $groupManager->resolveAlias($userType);
        $leaderSectionInfo = $groupManager->getSectionForInstrument($leaderResolvedType);
        $leaderSectionId = $leaderSectionInfo ?? $leaderResolvedType;
        
        // Process rehearsals - filter by leader's section when not viewing all
        foreach ($rehearsals as $rehearsal) {
            $rehearsalId = $rehearsal['id'];
            $stats[$rehearsalId] = [
                'attending' => 0,
                'not_attending' => 0,
                'no_response' => 0
            ];
            
            // For leaders viewing only their section, structure data differently
            if (!$leadersCanViewAll) {
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
                // Determine which users apply to this rehearsal using modern system
                $groups = $this->rehearsalModel->getGroupsAsAssoc($rehearsal['id']);
                $rehearsalIsSmallGroup = \App\Core\RehearsalTypeManager::isSmallGroupRehearsal($rehearsal);
                
                foreach ($members as $member) {
                    $isSmallGroup = isset($member['is_small_group']) && $member['is_small_group'];
                    if ($this->rehearsalModel->isUserInRehearsalGroup($member['type'], $isSmallGroup, $groups, $rehearsalIsSmallGroup)) {
                        $userPromises = $this->userModel->getPromises($member['id']);
                        $found = false;
                        $status = 'no_response';
                        $note = '';
                        
                        foreach ($userPromises as $promise) {
                            if ($promise['rehearsal_id'] == $rehearsalId) {
                                $status = $promise['attending'] ? 'attending' : 'not_attending';
                                $note = $promise['note'];
                                $found = true;
                                break;
                            }
                        }
                        
                        // For leader view (not viewing all), only count members from leader's section
                        if (!$leadersCanViewAll) {
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
                            'username' => $member['username'],
                            'type' => $member['type'],
                            'status' => $status,
                            'note' => $note,
                            'role' => $member['role'] ?? null,
                            'is_small_group' => $member['is_small_group'] ?? false
                        ];
                        
                        $membersBySection[$rehearsalId]['all'][] = $memberInfo;
                        
                        // Add to sections based on view mode
                        if (!$leadersCanViewAll) {
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
        
        // Get promises for each member and organize by rehearsal (for backward compatibility)
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
                            $category = $promise['attending'] ? 'attending' : 'not_attending';
                            $memberPromises[$rehearsalId][$category][] = [
                                'username' => $member['username'],
                                'type' => $member['type'],
                                'note' => $promise['note']
                            ];
                            $found = true;
                            break;
                        }
                    }
                    
                    if (!$found) {
                        $memberPromises[$rehearsalId]['no_response'][] = [
                            'username' => $member['username'],
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
        
        // Debug logging for troubleshooting
        if (!empty($rehearsals)) {
            $firstRehearsal = $rehearsals[0];
            $firstRehearsalId = $firstRehearsal['id'];
        }
        
        // Render view
        $this->render('promises/leader', [
            'currentPage' => 'leader',
            'rehearsals' => $rehearsals,
            'stats' => $stats,
            'membersBySection' => $membersBySection,
            'memberPromises' => $memberPromises,
            'showOld' => $showOld,
            'leadersCanViewAllSections' => $leadersCanViewAllEnabled,
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
        // Check if user is logged in
        if (!$this->isLoggedIn()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Nicht eingeloggt', 'details' => 'Bitte melden Sie sich erneut an.']);
            return;
        }
        
        // Check if Ajax request
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Ungültige Anfrage', 'details' => 'Diese Aktion ist nur über AJAX erlaubt.']);
            return;
        }
        
        // Get parameters
        $rehearsalId = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $status = isset($_POST['status']) ? (bool)$_POST['status'] : false;
        
        if ($rehearsalId <= 0) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Ungültige Proben-ID', 'details' => 'Die angegebene Proben-ID ist ungültig.']);
            return;
        }
        
        // Get user from session
        $username = $_SESSION['username'];
        $user = $this->userModel->findByUsername($username);
        
        if (!$user) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Benutzer nicht gefunden', 'details' => 'Ihr Benutzerkonto wurde nicht gefunden. Bitte melden Sie sich erneut an.']);
            return;
        }
        
        // Get existing note if any from user_promises table
        $promiseModel = new UserPromise();
        $existingPromise = $promiseModel->findByUserAndRehearsal($user['id'], $rehearsalId);
        $note = $existingPromise ? $existingPromise['note'] : '';
        
        // Update promise
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
                'details' => 'Es ist ein unerwarteter Fehler aufgetreten. Bitte versuchen Sie es später erneut.'
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
        // Check if user is logged in
        if (!$this->isLoggedIn()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Nicht eingeloggt', 'details' => 'Bitte melden Sie sich erneut an.']);
            return;
        }
        
        // Check if Ajax request
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Ungültige Anfrage', 'details' => 'Diese Aktion ist nur über AJAX erlaubt.']);
            return;
        }
        
        // Get parameters
        $rehearsalId = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $note = Validator::sanitizeUtf8($_POST['note'] ?? '');
        
        if ($rehearsalId <= 0) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Ungültige Proben-ID', 'details' => 'Die angegebene Proben-ID ist ungültig.']);
            return;
        }
        
        // Get user from session
        $username = $_SESSION['username'];
        $user = $this->userModel->findByUsername($username);
        
        if (!$user) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Benutzer nicht gefunden', 'details' => 'Ihr Benutzerkonto wurde nicht gefunden. Bitte melden Sie sich erneut an.']);
            return;
        }
        
        // Get current promise status from user_promises table
        $promiseModel = new UserPromise();
        $existingPromise = $promiseModel->findByUserAndRehearsal($user['id'], $rehearsalId);
        $status = $existingPromise ? (bool)$existingPromise['attending'] : false;
        
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
                'details' => 'Es ist ein unerwarteter Fehler aufgetreten. Bitte versuchen Sie es später erneut.'
            ]);
        }
    }
    
    /**
     * Display admin view for directors
     * 
     * @return void
     */
    public function admin()
    {
        // Check if user is logged in and is a director
        if (!$this->isLoggedIn()) {
            $this->redirect('/login');
            return;
        }

        // Check if user is a director
        if ($_SESSION['type'] !== 'Dirigent') {
            $this->redirect('/promises');
            return;
        }
        
        // Get show old parameter
        $showOld = isset($_GET['showOld']);
        
        // Get all rehearsals
        $rehearsals = $this->rehearsalModel->getUpcoming($_SESSION['orchestra_id'], $showOld);
        
        // Get all users in the current orchestra
        $users = $this->userModel->getOrchestraMembers($_SESSION['orchestra_id']);
        
        // Get orchestra settings
        $orchestraModel = new \App\Models\Orchestra();
        $orchestra = $orchestraModel->findById($_SESSION['orchestra_id']);
        $showRehearsalInsights = !empty($orchestra['show_rehearsal_insights']);
        
        // Initialize GroupManager for dynamic section handling
        $groupManager = new \App\Core\GroupManager();
        $allSections = $groupManager->getAllSections();
        
        // Calculate statistics for each rehearsal
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
            
            // Determine which users apply to this rehearsal using modern system
            $groups = $this->rehearsalModel->getGroupsAsAssoc($rehearsal['id']);
            $rehearsalIsSmallGroup = \App\Core\RehearsalTypeManager::isSmallGroupRehearsal($rehearsal);
            
            foreach ($users as $user) {
                // Skip conductors - they shouldn't be displayed in the attendance list
                if ($user['role'] === 'conductor' || $user['type'] === 'Dirigent') {
                    continue;
                }
                
                $isSmallGroup = isset($user['is_small_group']) && $user['is_small_group'];
                if ($this->rehearsalModel->isUserInRehearsalGroup($user['type'], $isSmallGroup, $groups, $rehearsalIsSmallGroup)) {
                    $userPromises = $this->userModel->getPromises($user['id']);
                    $found = false;
                    $status = 'no_response';
                    $note = '';
                    
                    foreach ($userPromises as $promise) {
                        if ($promise['rehearsal_id'] == $rehearsalId) {
                            $status = $promise['attending'] ? 'attending' : 'not_attending';
                            $note = $promise['note'];
                            $found = true;
                            break;
                        }
                    }
                    
                    // Update statistics
                    $stats[$rehearsalId][$status]++;
                    
                    // Add user to the appropriate section
                    $memberInfo = [
                        'username' => $user['username'],
                        'type' => $user['type'],
                        'status' => $status,
                        'note' => $note,
                        'role' => $user['role'] ?? null,
                        'is_small_group' => $user['is_small_group'] ?? false
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
        
        // Render view
        $this->render('promises/admin', [
            'currentPage' => 'admin',
            'rehearsals' => $rehearsals,
            'stats' => $stats,
            'membersBySection' => $membersBySection,
            'showOld' => $showOld,
            'showRehearsalInsights' => $showRehearsalInsights
        ]);
    }
} 