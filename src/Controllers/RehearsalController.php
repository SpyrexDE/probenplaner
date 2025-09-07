<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Validator;
use App\Models\Rehearsal;
use App\Core\Helpers;
use App\Core\Constants;

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
            // Sanitize form data
            $date = \App\Core\Validator::sanitizeUtf8($_POST['date'] ?? '');
            $start_time = \App\Core\Validator::sanitizeUtf8($_POST['start_time'] ?? '');
            $end_time = \App\Core\Validator::sanitizeUtf8($_POST['end_time'] ?? '');
            $location = \App\Core\Validator::sanitizeUtf8($_POST['location'] ?? '');
            $color = \App\Core\Validator::sanitizeUtf8($_POST['color'] ?? '');
            
            // Process groups data using the new processor
            $rehearsalType = \App\Core\Validator::sanitizeUtf8($_POST['rehearsal_type'] ?? '');
            $finalGroups = \App\Core\RehearsalGroupProcessor::processGroups($_POST);
            $groupValidationErrors = \App\Core\RehearsalGroupProcessor::validateGroups($finalGroups);
            
            // Check if it's a small group rehearsal
            $isSmallGroup = isset($_POST['is_small_group']) && $_POST['is_small_group'] === (string)\App\Core\RehearsalTypeManager::SMALL_GROUP_ENABLED;
            
            // Validate input using Validator class
            $requiredValidation = \App\Core\Validator::validateRequired([
                'date' => $date,
                'start_time' => $start_time,
                'end_time' => $end_time,
                'location' => $location
            ], ['date', 'start_time', 'end_time', 'location']);
            
            $dateValidation = \App\Core\Validator::validateDate($date);
            $startTimeValidation = \App\Core\Validator::validateTime($start_time, 'Startzeit');
            $endTimeValidation = \App\Core\Validator::validateTime($end_time, 'Endzeit');
            
            // Check if end time is after start time
            $timeOrderErrors = [];
            if (!empty($start_time) && !empty($end_time) && strtotime($end_time) <= strtotime($start_time)) {
                $timeOrderErrors[] = 'Die Endzeit muss nach der Startzeit liegen';
            }
            
            // Merge all validations
            $validation = \App\Core\Validator::mergeResults([
                $requiredValidation,
                $dateValidation,
                $startTimeValidation,
                $endTimeValidation,
                ['valid' => empty($timeOrderErrors), 'errors' => $timeOrderErrors],
                ['valid' => empty($groupValidationErrors), 'errors' => $groupValidationErrors]
            ]);
            
            $errors = $validation['errors'];
            
            if (empty($errors)) {
                // Save rehearsal
                $rehearsalData = [
                    'date' => $date,
                    'type' => !empty($rehearsalType) ? $rehearsalType : 'Probe',
                    'start_time' => $start_time,
                    'end_time' => $end_time,
                    'location' => $location,
                    'orchestra_id' => (int)$_SESSION['orchestra_id'],
                    'is_small_group' => $isSmallGroup ? 1 : 0
                ];
                
                // Only add color if it was submitted and if the field exists in the database
                if (!empty($color)) {
                    $rehearsalData['color'] = $color;
                }
                
                $result = $this->rehearsalModel->create($rehearsalData, $finalGroups);
                
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
                    'start_time' => $start_time,
                    'end_time' => $end_time,
                    'location' => $location,
                    'color' => $color,
                    'rehearsal_type' => $rehearsalType,
                    'groups' => $finalGroups,
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
                    'start_time' => '',
                    'end_time' => '',
                    'location' => '',
                    'color' => Constants::COLOR_WHITE,
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
            // Sanitize form data
            $date = \App\Core\Validator::sanitizeUtf8($_POST['date'] ?? '');
            $start_time = \App\Core\Validator::sanitizeUtf8($_POST['start_time'] ?? '');
            $end_time = \App\Core\Validator::sanitizeUtf8($_POST['end_time'] ?? '');
            $location = \App\Core\Validator::sanitizeUtf8($_POST['location'] ?? '');
            $color = \App\Core\Validator::sanitizeUtf8($_POST['color'] ?? '');
            
            // Process groups data using the new processor
            $rehearsalType = \App\Core\Validator::sanitizeUtf8($_POST['rehearsal_type'] ?? '');
            $finalGroups = \App\Core\RehearsalGroupProcessor::processGroups($_POST);
            $groupValidationErrors = \App\Core\RehearsalGroupProcessor::validateGroups($finalGroups);
            
            // Check if it's a small group rehearsal
            $isSmallGroup = isset($_POST['is_small_group']) && $_POST['is_small_group'] === (string)\App\Core\RehearsalTypeManager::SMALL_GROUP_ENABLED;
            
            // Validate input using Validator class
            $requiredValidation = \App\Core\Validator::validateRequired([
                'date' => $date,
                'start_time' => $start_time,
                'end_time' => $end_time,
                'location' => $location
            ], ['date', 'start_time', 'end_time', 'location']);
            
            $dateValidation = \App\Core\Validator::validateDate($date);
            $startTimeValidation = \App\Core\Validator::validateTime($start_time, 'Startzeit');
            $endTimeValidation = \App\Core\Validator::validateTime($end_time, 'Endzeit');
            
            // Check if end time is after start time
            $timeOrderErrors = [];
            if (!empty($start_time) && !empty($end_time) && strtotime($end_time) <= strtotime($start_time)) {
                $timeOrderErrors[] = 'Die Endzeit muss nach der Startzeit liegen';
            }
            
            // Merge all validations
            $validation = \App\Core\Validator::mergeResults([
                $requiredValidation,
                $dateValidation,
                $startTimeValidation,
                $endTimeValidation,
                ['valid' => empty($timeOrderErrors), 'errors' => $timeOrderErrors],
                ['valid' => empty($groupValidationErrors), 'errors' => $groupValidationErrors]
            ]);
            
            $errors = $validation['errors'];
            
            if (empty($errors)) {
                // Update rehearsal
                $updateData = [
                    'date' => $date,
                    'type' => !empty($rehearsalType) ? $rehearsalType : 'Probe',
                    'start_time' => $start_time,
                    'end_time' => $end_time,
                    'location' => $location,
                    'is_small_group' => $isSmallGroup ? 1 : 0
                ];
                
                if (!empty($color)) {
                    $updateData['color'] = $color;
                }
                
                $result = $this->rehearsalModel->updateRehearsal($rehearsalId, $updateData, $finalGroups);
                
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
                    'start_time' => $start_time,
                    'end_time' => $end_time,
                    'location' => $location,
                    'color' => $color,
                    'rehearsal_type' => $rehearsalType,
                    'groups' => $finalGroups,
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
                $displayDate = \App\Core\Utilities::formatDateForDb($rehearsal['date']);
            }
            
            // Get rehearsal type from the new type field
            $rehearsalType = $rehearsal['type'] ?? 'Probe';
            $groups = $rehearsal['groups'] ?? [];
            
            // Use the proper form data generation to handle tutti-with-exclusions
            $formData = \App\Core\RehearsalGroupProcessor::generateFormData($groups);
            
            // Display the form
            $this->render('rehearsals/edit', [
                'currentPage' => 'rehearsals',
                'rehearsal' => $rehearsal,
                'errors' => [],
                'formData' => [
                    'date' => $displayDate,
                    'start_time' => $rehearsal['start_time'] ?? '',
                    'end_time' => $rehearsal['end_time'] ?? '',
                    'location' => $rehearsal['location'],
                    'color' => $rehearsal['color'] ?? '',
                    'rehearsal_type' => $rehearsalType,
                    'groups' => $formData['groups'],
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