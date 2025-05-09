<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Rehearsal;
use App\Core\Helpers;

/**
 * Rehearsal Controller
 * Handles rehearsal management
 */
class RehearsalController extends Controller
{
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
        $this->rehearsalModel = new Rehearsal();
    }
    
    /**
     * Display rehearsal list
     * 
     * @return void
     */
    public function index()
    {
        // Check if user is logged in and is a director
        if (!$this->isLoggedIn()) {
            $this->redirect('/login');
            return;
        }

        if ($_SESSION['type'] !== 'Dirigent') {
            $this->redirect('/promises');
            return;
        }
        
        // Get show old parameter
        $showOld = isset($_GET['showOld']);
        
        // Get all rehearsals
        $rehearsals = $this->rehearsalModel->getUpcoming($_SESSION['orchestra_id'], $showOld);
        
        // Render view
        $this->render('rehearsals/index', [
            'currentPage' => 'rehearsals',
            'rehearsals' => $rehearsals,
            'showOld' => $showOld
        ]);
    }
    
    /**
     * Display rehearsal creation form
     * 
     * @return void
     */
    public function create()
    {
        // Check if user is logged in and is a director
        if (!$this->isLoggedIn()) {
            $this->redirect('/login');
            return;
        }

        if ($_SESSION['type'] !== 'Dirigent') {
            $this->redirect('/promises');
            return;
        }
        
        // Process form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Get form data
            $date = $_POST['date'] ?? '';
            $time = $_POST['time'] ?? '';
            $location = $_POST['location'] ?? '';
            $color = $_POST['color'] ?? '';
            
            // Date is already in Y-m-d format from the date input field
            // No need to convert
            
            // Process groups data
            $groups = [];
            $rehearsalType = $_POST['rehearsal_type'] ?? '';
            $groupsSelected = $_POST['groups'] ?? [];
            
            if (!empty($rehearsalType)) {
                $groups[$rehearsalType] = 0;
            }
            
            // Add selected groups
            foreach ($groupsSelected as $group) {
                $groups[$group] = 0;
            }
            
            // Check if it's a small group rehearsal
            $isSmallGroup = isset($_POST['is_small_group']) && $_POST['is_small_group'] === '1';
            
            // Validate input
            $errors = [];
            
            if (empty($date)) {
                $errors[] = 'Date is required';
            }
            
            if (empty($time)) {
                $errors[] = 'Time is required';
            }
            
            if (empty($location)) {
                $errors[] = 'Location is required';
            }
            
            if (empty($groups)) {
                $errors[] = 'At least one group must be selected';
            }
            
            if (empty($errors)) {
                // Save rehearsal
                $rehearsalData = [
                    'date' => $date,
                    'time' => $time,
                    'location' => $location,
                    'orchestra_id' => (int)$_SESSION['orchestra_id'],
                    'is_small_group' => $isSmallGroup ? 1 : 0
                ];
                
                // Only add color if it was submitted and if the field exists in the database
                if (!empty($color)) {
                    $rehearsalData['color'] = $color;
                }
                
                $result = $this->rehearsalModel->create($rehearsalData, array_keys($groups));
                
                if ($result && !is_array($result)) {
                    $this->setFlash('success', 'Rehearsal created successfully');
                    $this->redirect('/rehearsals');
                    return;
                } else {
                    $errorMessage = is_array($result) && isset($result['message']) 
                        ? 'Failed to create rehearsal: ' . $result['message']
                        : 'Failed to create rehearsal';
                    
                    // Add detailed error information
                    $this->addAlert('Fehler!', $errorMessage, 'error', 
                        is_array($result) && isset($result['details']) ? $result['details'] : null);
                    
                    $errors[] = $errorMessage;
                }
            }
            
            // If we get here, there were errors
            $this->render('rehearsals/create', [
                'currentPage' => 'rehearsals',
                'errors' => $errors,
                'formData' => [
                    'date' => $date, // HTML date input expects Y-m-d format
                    'time' => $time,
                    'location' => $location,
                    'color' => $color,
                    'rehearsal_type' => $rehearsalType,
                    'groups' => $groupsSelected,
                    'is_small_group' => $isSmallGroup
                ]
            ]);
        } else {
            // Display the form
            $this->render('rehearsals/create', [
                'currentPage' => 'rehearsals',
                'errors' => [],
                'formData' => [
                    'date' => '',
                    'time' => '',
                    'location' => '',
                    'color' => 'white',
                    'rehearsal_type' => '',
                    'groups' => [],
                    'is_small_group' => false
                ]
            ]);
        }
    }
    
    /**
     * Display rehearsal edit form
     * 
     * @param array $params Route parameters
     * @return void
     */
    public function edit($params)
    {
        // Check if user is logged in and is a director
        if (!$this->isLoggedIn()) {
            $this->redirect('/login');
            return;
        }

        if ($_SESSION['type'] !== 'Dirigent') {
            $this->redirect('/promises');
            return;
        }
        
        // Get rehearsal ID from route parameters
        $rehearsalId = isset($params['id']) ? intval($params['id']) : 0;
        
        if ($rehearsalId <= 0) {
            $this->redirect('/rehearsals');
            return;
        }
        
        // Get rehearsal data
        $rehearsal = $this->rehearsalModel->findById($rehearsalId);
        
        if (!$rehearsal) {
            $this->setFlash('error', 'Rehearsal not found');
            $this->redirect('/rehearsals');
            return;
        }
        
        // Process form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Get form data
            $date = $_POST['date'] ?? '';
            $time = $_POST['time'] ?? '';
            $location = $_POST['location'] ?? '';
            $color = $_POST['color'] ?? '';
            
            // Date is already in Y-m-d format from the date input field
            // No need to convert
            
            // Process groups data
            $groups = [];
            $rehearsalType = $_POST['rehearsal_type'] ?? '';
            $groupsSelected = $_POST['groups'] ?? [];
            
            if (!empty($rehearsalType)) {
                $groups[$rehearsalType] = 0;
            }
            
            // Add selected groups
            foreach ($groupsSelected as $group) {
                $groups[$group] = 0;
            }
            
            // Check if it's a small group rehearsal
            $isSmallGroup = isset($_POST['is_small_group']) && $_POST['is_small_group'] === '1';
            
            // Validate input
            $errors = [];
            
            if (empty($date)) {
                $errors[] = 'Date is required';
            }
            
            if (empty($time)) {
                $errors[] = 'Time is required';
            }
            
            if (empty($location)) {
                $errors[] = 'Location is required';
            }
            
            if (empty($groups)) {
                $errors[] = 'At least one group must be selected';
            }
            
            if (empty($errors)) {
                // Update rehearsal
                $updateData = [
                    'date' => $date,
                    'time' => $time,
                    'location' => $location,
                    'is_small_group' => $isSmallGroup ? 1 : 0
                ];
                
                if (!empty($color)) {
                    $updateData['color'] = $color;
                }
                
                $result = $this->rehearsalModel->updateRehearsal($rehearsalId, $updateData, array_keys($groups));
                
                if ($result === true) {
                    $this->setFlash('success', 'Rehearsal updated successfully');
                    $this->redirect('/rehearsals');
                    return;
                } else {
                    $errorMessage = is_array($result) && isset($result['message']) 
                        ? 'Failed to update rehearsal: ' . $result['message']
                        : 'Failed to update rehearsal';
                    $errors[] = $errorMessage;
                }
            }
            
            // If we get here, there were errors
            $this->render('rehearsals/edit', [
                'currentPage' => 'rehearsals',
                'rehearsal' => $rehearsal,
                'errors' => $errors,
                'formData' => [
                    'date' => $date,
                    'time' => $time,
                    'location' => $location,
                    'color' => $color,
                    'rehearsal_type' => $rehearsalType,
                    'groups' => $groupsSelected,
                    'is_small_group' => $isSmallGroup
                ]
            ]);
        } else {
            // Convert date from Y-m-d to dd.mm.yyyy format for display
            $displayDate = '';
            if (!empty($rehearsal['date'])) {
                // For HTML date input, we need Y-m-d format
                // The date from the database is likely in Y-m-d format already
                // But if it's been formatted to dd.mm.yyyy by the model, convert it back
                $displayDate = Helpers::formatDateForDb($rehearsal['date']);
            }
            
            // Check if any of the rehearsal types is in the groups
            $rehearsalType = '';
            $rehearsalTypes = ['Konzertreise', 'Konzert', 'Generalprobe', 'Registerprobe', 'Tutti'];
            $groups = $rehearsal['groups'] ?? [];
            
            foreach ($rehearsalTypes as $type) {
                if (in_array($type, $groups)) {
                    $rehearsalType = $type;
                    break;
                }
            }
            
            // Display the form
            $this->render('rehearsals/edit', [
                'currentPage' => 'rehearsals',
                'rehearsal' => $rehearsal,
                'errors' => [],
                'formData' => [
                    'date' => $displayDate,
                    'time' => $rehearsal['time'],
                    'location' => $rehearsal['location'],
                    'color' => $rehearsal['color'] ?? '',
                    'rehearsal_type' => $rehearsalType,
                    'groups' => $rehearsal['groups'] ?? [],
                    'is_small_group' => $rehearsal['is_small_group'] ?? false
                ]
            ]);
        }
    }
    
    /**
     * Delete rehearsal
     * 
     * @param array $params Route parameters
     * @return void
     */
    public function delete($params)
    {
        // Check if user is logged in and is a director
        if (!$this->isLoggedIn()) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                echo json_encode(['success' => false, 'message' => 'Unauthorized']);
                exit;
            }
            $this->redirect('/login');
            return;
        }

        if ($_SESSION['type'] !== 'Dirigent') {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                echo json_encode(['success' => false, 'message' => 'Unauthorized']);
                exit;
            }
            $this->redirect('/promises');
            return;
        }
        
        // Get rehearsal ID from route parameters or POST data
        $rehearsalId = 0;
        if (isset($params['id'])) {
            $rehearsalId = intval($params['id']);
        } else if (isset($_POST['id'])) {
            $rehearsalId = intval($_POST['id']);
        }
        
        if ($rehearsalId <= 0) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                echo json_encode(['success' => false, 'message' => 'Invalid rehearsal ID']);
                exit;
            }
            $this->redirect('/rehearsals');
            return;
        }
        
        // Delete rehearsal immediately, no confirmation needed
        $result = $this->rehearsalModel->delete($rehearsalId);
        
        if ($result) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                echo json_encode(['success' => true]);
                exit;
            }
            $this->setFlash('success', 'Rehearsal deleted successfully');
        } else {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                echo json_encode(['success' => false, 'message' => 'Failed to delete rehearsal']);
                exit;
            }
            $this->setFlash('error', 'Failed to delete rehearsal');
        }
        
        $this->redirect('/rehearsals');
    }
} 