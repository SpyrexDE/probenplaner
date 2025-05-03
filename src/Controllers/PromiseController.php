<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;
use App\Models\Rehearsal;

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
        
        // Get rehearsals for the user's type
        $userType = $_SESSION['type'];
        $rehearsals = $this->rehearsalModel->getForUser($userType, $_SESSION['orchestra_id'], $showOld);
        
        // Get user's promises
        $promises = [];
        
        // First try to get promises from session
        $promisesStr = $_SESSION['promises'] ?? '';
        
        // If session promises empty, get user from database
        if (empty($promisesStr) && isset($_SESSION['user_id'])) {
            $user = $this->userModel->findById($_SESSION['user_id']);
            if ($user && isset($user['promises'])) {
                $promisesStr = $user['promises'];
                // Update session
                $_SESSION['promises'] = $promisesStr;
            }
        }
        
        // Log the promises string from session for debugging
        error_log("Promises from session for user {$_SESSION['username']}: $promisesStr");
        
        if (!empty($promisesStr)) {
            $promisesArr = explode('|', $promisesStr);
            
            foreach ($promisesArr as $promise) {
                if (empty($promise)) {
                    continue;
                }
                
                $attending = true;
                $rehearsalId = $promise;
                $note = '';
                
                // Check if not attending
                if (strpos($promise, '!') === 0) {
                    $attending = false;
                    $rehearsalId = substr($promise, 1);
                }
                
                // Extract note if exists
                if (preg_match('/\((.*?)\)/', $promise, $matches)) {
                    $note = $matches[1];
                    $rehearsalId = preg_replace('/\((.*?)\)/', '', $rehearsalId);
                }
                
                $promises[$rehearsalId] = [
                    'attending' => $attending,
                    'note' => $note
                ];
            }
        }
        
        // As a backup, also get promises directly from database
        if (empty($promises)) {
            error_log("No promises found in session, fetching from database");
            $user = $this->userModel->findByUsername($_SESSION['username']);
            if ($user) {
                $userPromises = $this->userModel->getPromises($user['id']);
                foreach ($userPromises as $promise) {
                    $promises[$promise['rehearsal_id']] = [
                        'attending' => (bool)$promise['attending'],
                        'note' => $promise['note']
                    ];
                }
                
                // Update session with the latest promises
                $this->userModel->refreshPromises($user['id']);
                $updatedUser = $this->userModel->findById($user['id']);
                // Ensure updatedUser has promises field
                if ($updatedUser) {
                    $updatedUser = $this->userModel->ensurePromisesField($updatedUser);
                    $_SESSION['promises'] = $updatedUser['promises'];
                    error_log("Updated session promises: {$_SESSION['promises']}");
                }
            }
        }
        
        // Log the promises array for debugging
        error_log("Parsed promises array: " . json_encode($promises));
        
        // Final safety check - ensure session has promises
        if (!isset($_SESSION['promises'])) {
            $_SESSION['promises'] = '';
            error_log("WARNING: Set empty promises in session as final safety check");
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
        if (strpos($username, '♚') === false) {
            $this->redirect('/promises');
            return;
        }
        
        // Get show old parameter
        $showOld = isset($_GET['showOld']);
        
        // Get user type (section)
        $userType = $_SESSION['type'];

        // Clean up section name for database queries
        $sectionName = str_replace('♚', '', $userType);
        $sectionName = str_replace(' ', '_', $sectionName);
        
        // Get rehearsals for the section
        $rehearsals = $this->rehearsalModel->getForUser($sectionName, $_SESSION['orchestra_id'], $showOld);
        
        // Get all members of this section
        $members = $this->userModel->findByType($sectionName, $_SESSION['orchestra_id']);
        
        // Get promises for each member and organize by rehearsal
        $memberPromises = [];
        
        foreach ($rehearsals as $rehearsal) {
            $rehearsalId = $rehearsal['id'];
            $memberPromises[$rehearsalId] = [
                'attending' => [],
                'not_attending' => [],
                'no_response' => []
            ];
            
            foreach ($members as $member) {
                $promises = $this->userModel->getPromises($member['id']);
                $found = false;
                
                foreach ($promises as $promise) {
                    if ($promise['rehearsal_id'] == $rehearsalId) {
                        $category = $promise['attending'] ? 'attending' : 'not_attending';
                        $memberPromises[$rehearsalId][$category][] = [
                            'username' => $member['username'],
                            'note' => $promise['note']
                        ];
                        $found = true;
                        break;
                    }
                }
                
                if (!$found) {
                    $memberPromises[$rehearsalId]['no_response'][] = [
                        'username' => $member['username']
                    ];
                }
            }
        }
        
        // Render view
        $this->render('promises/leader', [
            'currentPage' => 'leader',
            'rehearsals' => $rehearsals,
            'memberPromises' => $memberPromises,
            'showOld' => $showOld
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
            echo json_encode(['success' => false, 'message' => 'Nicht eingeloggt']);
            return;
        }
        
        // Check if Ajax request
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Ungültige Anfrage']);
            return;
        }
        
        // Get parameters
        $rehearsalId = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $status = isset($_POST['status']) ? (bool)$_POST['status'] : false;
        
        if ($rehearsalId <= 0) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Ungültige Proben-ID']);
            return;
        }
        
        // Get user from session
        $username = $_SESSION['username'];
        $user = $this->userModel->findByUsername($username);
        
        if (!$user) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Benutzer nicht gefunden']);
            return;
        }
        
        // Ensure user has promises field initialized
        $user = $this->userModel->ensurePromisesField($user);
        
        // Get existing note if any
        $note = '';
        $promises = explode('|', $user['promises']);
        
        foreach ($promises as $promise) {
            if (empty($promise)) {
                continue;
            }
            
            $attending = strpos($promise, '!') !== 0;
            $promiseId = $attending ? $promise : substr($promise, 1);
            
            // Extract note if exists
            if (preg_match('/\((.*?)\)/', $promiseId, $matches)) {
                $noteCandidate = $matches[1];
                $promiseIdClean = preg_replace('/\((.*?)\)/', '', $promiseId);
                
                if ($promiseIdClean == $rehearsalId) {
                    $note = $noteCandidate;
                    break;
                }
            }
        }
        
        // Update promise
        $result = $this->userModel->updatePromise($user['id'], $rehearsalId, $status, $note);
        
        // Update session
        if ($result) {
            $updatedUser = $this->userModel->findById($user['id']);
            $_SESSION['promises'] = $updatedUser['promises'];
            
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Fehler beim Speichern']);
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
            echo json_encode(['success' => false, 'message' => 'Nicht eingeloggt']);
            return;
        }
        
        // Check if Ajax request
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Ungültige Anfrage']);
            return;
        }
        
        // Get parameters
        $rehearsalId = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $note = isset($_POST['note']) ? trim($_POST['note']) : '';
        
        if ($rehearsalId <= 0) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Ungültige Proben-ID']);
            return;
        }
        
        // Get user from session
        $username = $_SESSION['username'];
        $user = $this->userModel->findByUsername($username);
        
        if (!$user) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Benutzer nicht gefunden']);
            return;
        }
        
        // Ensure user has promises field initialized
        $user = $this->userModel->ensurePromisesField($user);
        
        // Get current promise status
        $status = false; // Default: not attending
        $promises = explode('|', $user['promises']);
        
        foreach ($promises as $promise) {
            if (empty($promise)) {
                continue;
            }
            
            $attending = strpos($promise, '!') !== 0;
            $promiseId = $attending ? $promise : substr($promise, 1);
            
            // Remove note if exists
            $promiseIdClean = preg_replace('/\((.*?)\)/', '', $promiseId);
            
            if ($promiseIdClean == $rehearsalId) {
                $status = $attending;
                break;
            }
        }
        
        // Update promise with note
        $result = $this->userModel->updatePromise($user['id'], $rehearsalId, $status, $note);
        
        // Update session
        if ($result) {
            $updatedUser = $this->userModel->findById($user['id']);
            $_SESSION['promises'] = $updatedUser['promises'];
            
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Fehler beim Speichern']);
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
            
            $membersBySection[$rehearsalId] = [
                'all' => [],
                'strings' => [],
                'woodwinds' => [],
                'brass' => [],
                'percussion' => []
            ];
            
            // Determine which users apply to this rehearsal
            $groups = json_decode($rehearsal['groups_data'] ?? '{}', true);
            
            foreach ($users as $user) {
                // Skip conductors - they shouldn't be displayed in the attendance list
                if ($user['role'] === 'conductor' || $user['type'] === 'Dirigent') {
                    continue;
                }
                
                if ($this->rehearsalModel->isUserInRehearsalGroup($user['type'], $groups)) {
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
                        'note' => $note
                    ];
                    
                    $membersBySection[$rehearsalId]['all'][] = $memberInfo;
                    
                    // Determine section
                    $userType = $user['type'];
                    if ($userType === 'Violine_1' || $userType === 'Violine_2' || $userType === 'Bratsche' || 
                        $userType === 'Cello' || $userType === 'Kontrabass') {
                        $membersBySection[$rehearsalId]['strings'][] = $memberInfo;
                    } elseif ($userType === 'Flöte' || $userType === 'Oboe' || 
                             $userType === 'Klarinette' || $userType === 'Fagott') {
                        $membersBySection[$rehearsalId]['woodwinds'][] = $memberInfo;
                    } elseif ($userType === 'Trompete' || $userType === 'Posaune' || 
                             $userType === 'Horn' || $userType === 'Tuba') {
                        $membersBySection[$rehearsalId]['brass'][] = $memberInfo;
                    } elseif ($userType === 'Schlagwerk' || $userType === 'Percussion' || 
                             $userType === 'Pauken') {
                        $membersBySection[$rehearsalId]['percussion'][] = $memberInfo;
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
            'showOld' => $showOld
        ]);
    }
} 