<?php
namespace App\Controllers;

use App\Core\Controller;
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
        
        // Get rehearsals for the user's type
        $userType = $_SESSION['type'];
        $isSmallGroup = isset($_SESSION['is_small_group']) && $_SESSION['is_small_group'];
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
        
        // Get rehearsals for the section
        $isSmallGroup = isset($_SESSION['is_small_group']) && $_SESSION['is_small_group'];
        $rehearsals = $this->rehearsalModel->getForUser($sectionName, $_SESSION['orchestra_id'], $showOld, $isSmallGroup);
        
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
        $note = isset($_POST['note']) ? trim($_POST['note']) : '';
        
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
                
                $isSmallGroup = isset($user['is_small_group']) && $user['is_small_group'];
                if ($this->rehearsalModel->isUserInRehearsalGroup($user['type'], $isSmallGroup, $groups)) {
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