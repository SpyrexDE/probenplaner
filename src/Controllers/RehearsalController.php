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
    public function index($params = [])
    {
        $this->validateOrchestraContext($params);

        $this->requireRole('conductor');

        if (isset($_GET['ajax']) && $_GET['pastOnly']) {
            $this->handlePastRehearsalsAjax();
            return;
        }

        $showOld = isset($_GET['showOld']);

        $rehearsals = $this->rehearsalModel->getUpcoming($_SESSION['current_orchestra_id'], $showOld);
        $hasPastRehearsals = $this->rehearsalModel->hasPastRehearsals($_SESSION['current_orchestra_id']);

        $this->render('rehearsals/index', [
            'currentPage' => 'rehearsals',
            'rehearsals' => $rehearsals,
            'showOld' => $showOld,
            'hasPastRehearsals' => $hasPastRehearsals
        ]);
    }

    /**
     * Display rehearsal creation form
     * 
     * @param array $params Route parameters containing orchestra_id
     * @return void
     */
    public function create($params = [])
    {
        $this->validateOrchestraContext($params);

        $this->requireRole('conductor');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Sanitize form data
            $start = \App\Core\Validator::sanitizeUtf8($_POST['start'] ?? '');
            $end = \App\Core\Validator::sanitizeUtf8($_POST['end'] ?? '');
            $location = \App\Core\Validator::sanitizeUtf8($_POST['location'] ?? '');
            $color = \App\Core\Validator::sanitizeUtf8($_POST['color'] ?? '');

            // Handle group assignments
            $rehearsalType = \App\Core\Validator::sanitizeUtf8($_POST['rehearsal_type'] ?? '');
            $finalGroups = \App\Core\RehearsalGroupProcessor::processGroups($_POST);
            $groupValidationErrors = \App\Core\RehearsalGroupProcessor::validateGroups($finalGroups);

            $isSmallGroup = isset($_POST['is_small_group']) && $_POST['is_small_group'] === (string)\App\Core\RehearsalTypeManager::SMALL_GROUP_ENABLED;

            $requiredValidation = \App\Core\Validator::validateRequired([
                'start' => $start,
                'end' => $end
            ], ['start', 'end']);

            $startValidation = \App\Core\Validator::validateDateTime($start);
            $endValidation = \App\Core\Validator::validateDateTime($end);

            // Check if end time is after start time
            $timeOrderErrors = [];
            if (!empty($start) && !empty($end) && strtotime($end) <= strtotime($start)) {
                $timeOrderErrors[] = 'Die Endzeit muss nach der Startzeit liegen';
            }

            $validation = \App\Core\Validator::mergeResults([
                $requiredValidation,
                $startValidation,
                $endValidation,
                ['valid' => empty($timeOrderErrors), 'errors' => $timeOrderErrors],
                ['valid' => empty($groupValidationErrors), 'errors' => $groupValidationErrors]
            ]);

            $errors = $validation['errors'];

            if (empty($errors)) {
                // Save rehearsal
                $rehearsalData = [
                    'start' => $start,
                    'end' => $end,
                    'type' => $rehearsalType,
                    'location' => $location,
                    'orchestra_id' => (int)$_SESSION['current_orchestra_id'],
                    'is_small_group' => $isSmallGroup ? 1 : 0
                ];

                // Only add color if it was submitted and if the field exists in the database
                if (!empty($color)) {
                    $rehearsalData['color'] = $color;
                }

                $result = $this->rehearsalModel->create($rehearsalData, $finalGroups);

                if ($result && !is_array($result)) {
                    // Save schedule items if provided
                    $scheduleJson = $_POST['schedule_items'] ?? '';
                    if (!empty($scheduleJson)) {
                        $scheduleItems = json_decode($scheduleJson, true) ?: [];
                        $this->rehearsalModel->saveScheduleItems($result, $scheduleItems);
                    }

                    // Save infos if provided
                    $infosJson = $_POST['infos'] ?? '';
                    if (!empty($infosJson)) {
                        $infosItems = json_decode($infosJson, true) ?: [];
                        $this->rehearsalModel->saveInfos($result, $infosItems);
                    }


                    $this->setFlash('success', 'Probe wurde erfolgreich erstellt.');
                    $this->redirect('/' . $_SESSION['current_orchestra_id'] . '/rehearsals');
                    return;
                } else {
                    $errorMessage = is_array($result) && isset($result['message'])
                        ? 'Probe konnte nicht erstellt werden: ' . $result['message']
                        : 'Probe konnte nicht erstellt werden';

                    $errorDetails = is_array($result) ? ($result['details'] ?? $result['data'] ?? null) : null;
                    if (is_array($errorDetails)) {
                        $errorDetails = json_encode($errorDetails, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                    }
                    if (empty($errorDetails)) {
                        $errorDetails = $errorMessage;
                    }
                    $this->addAlert('Fehler!', $errorMessage, 'error', $errorDetails);

                    // Don't add to errors array to avoid duplicate notification (addAlert handles it with details)
                }
            }

            // If we get here, there were errors
            $this->render('rehearsals/create', [
                'currentPage' => 'rehearsals',
                'errors' => $errors,
                'formData' => [
                    'start' => $start,
                    'end' => $end,
                    'location' => $location,
                    'color' => $color,
                    'rehearsal_type' => $rehearsalType,
                    'groups' => $finalGroups,
                    'is_small_group' => $isSmallGroup
                ]
            ]);
        } else {
            $this->render('rehearsals/create', [
                'currentPage' => 'rehearsals',
                'errors' => [],
                'formData' => [
                    'start' => '',
                    'end' => '',
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
        $this->validateOrchestraContext($params);

        $this->requireRole('conductor');

        // Get rehearsal ID from route parameters
        $rehearsalId = isset($params['id']) ? intval($params['id']) : 0;

        if ($rehearsalId <= 0) {
            $this->redirect('/' . $_SESSION['current_orchestra_id'] . '/rehearsals');
            return;
        }

        $rehearsal = $this->rehearsalModel->findById($rehearsalId);

        if (!$rehearsal) {
            $this->setFlash('error', 'Probe nicht gefunden');
            $this->redirect('/' . $_SESSION['current_orchestra_id'] . '/rehearsals');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Sanitize form data
            $start = \App\Core\Validator::sanitizeUtf8($_POST['start'] ?? '');
            $end = \App\Core\Validator::sanitizeUtf8($_POST['end'] ?? '');
            $location = \App\Core\Validator::sanitizeUtf8($_POST['location'] ?? '');
            $color = \App\Core\Validator::sanitizeUtf8($_POST['color'] ?? '');

            // Handle group assignments
            $rehearsalType = \App\Core\Validator::sanitizeUtf8($_POST['rehearsal_type'] ?? '');
            $finalGroups = \App\Core\RehearsalGroupProcessor::processGroups($_POST);
            $groupValidationErrors = \App\Core\RehearsalGroupProcessor::validateGroups($finalGroups);

            $isSmallGroup = isset($_POST['is_small_group']) && $_POST['is_small_group'] === (string)\App\Core\RehearsalTypeManager::SMALL_GROUP_ENABLED;

            $requiredValidation = \App\Core\Validator::validateRequired([
                'start' => $start,
                'end' => $end
            ], ['start', 'end']);

            $startValidation = \App\Core\Validator::validateDateTime($start);
            $endValidation = \App\Core\Validator::validateDateTime($end);

            // Check if end time is after start time
            $timeOrderErrors = [];
            if (!empty($start) && !empty($end) && strtotime($end) <= strtotime($start)) {
                $timeOrderErrors[] = 'Die Endzeit muss nach der Startzeit liegen';
            }

            $validation = \App\Core\Validator::mergeResults([
                $requiredValidation,
                $startValidation,
                $endValidation,
                ['valid' => empty($timeOrderErrors), 'errors' => $timeOrderErrors],
                ['valid' => empty($groupValidationErrors), 'errors' => $groupValidationErrors]
            ]);

            $errors = $validation['errors'];

            if (empty($errors)) {
                // Update rehearsal
                $updateData = [
                    'start' => $start,
                    'end' => $end,
                    'type' => $rehearsalType,
                    'location' => $location,
                    'is_small_group' => $isSmallGroup ? 1 : 0
                ];

                if (!empty($color)) {
                    $updateData['color'] = $color;
                }

                $result = $this->rehearsalModel->updateRehearsal($rehearsalId, $updateData, $finalGroups);

                if ($result === true) {
                    $this->setFlash('success', 'Probe wurde erfolgreich aktualisiert.');
                    $this->redirect('/' . $_SESSION['current_orchestra_id'] . '/rehearsals');
                    return;
                } else {
                    $errorMessage = is_array($result) && isset($result['message'])
                        ? 'Probe konnte nicht aktualisiert werden: ' . $result['message']
                        : 'Probe konnte nicht aktualisiert werden';

                    $errorDetails = is_array($result) ? ($result['details'] ?? $result['data'] ?? null) : null;
                    if (is_array($errorDetails)) {
                        $errorDetails = json_encode($errorDetails, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                    }
                    if (empty($errorDetails)) {
                        $errorDetails = $errorMessage;
                    }
                    $this->addAlert('Fehler!', $errorMessage, 'error', $errorDetails);
                }
            }

            // If we get here, there were errors
            $this->render('rehearsals/edit', [
                'currentPage' => 'rehearsals',
                'rehearsal' => $rehearsal,
                'errors' => $errors,
                'formData' => [
                    'start' => $start,
                    'end' => $end,
                    'location' => $location,
                    'color' => $color,
                    'rehearsal_type' => $rehearsalType,
                    'groups' => $finalGroups,
                    'is_small_group' => $isSmallGroup
                ]
            ]);
        } else {
            // Get rehearsal type from the new type field
            $rehearsalType = $rehearsal['type'] ?? '';
            $groups = $rehearsal['groups'] ?? [];

            // Use the proper form data generation to handle tutti-with-exclusions
            $formData = \App\Core\RehearsalGroupProcessor::generateFormData($groups);

            // Display the form
            $this->render('rehearsals/edit', [
                'currentPage' => 'rehearsals',
                'rehearsal' => $rehearsal,
                'errors' => [],
                'formData' => [
                    'start' => $rehearsal['start'] ?? '',
                    'end' => $rehearsal['end'] ?? '',
                    'location' => $rehearsal['location'],
                    'color' => $rehearsal['color'] ?? '',
                    'rehearsal_type' => $rehearsalType,
                    'groups' => $formData['groups'],
                    'is_small_group' => $rehearsal['is_small_group'] ?? false,
                    'schedule_items' => $rehearsal['schedule_items'] ?? [],
                    'infos' => $rehearsal['infos'] ?? []
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
        // Validate orchestra context and set session variables
        try {
            $this->validateOrchestraContext($params);
            // Check if user is a conductor
            $this->requireRole('conductor');
        } catch (\Exception $e) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                echo json_encode(['success' => false, 'message' => 'Nicht berechtigt']);
                exit;
            }
            $this->redirect('/login');
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
                echo json_encode(['success' => false, 'message' => 'Ungültige Proben-ID']);
                exit;
            }
            $this->redirect('/' . $_SESSION['current_orchestra_id'] . '/rehearsals');
            return;
        }

        // Delete rehearsal immediately, no confirmation needed
        try {
            $result = $this->rehearsalModel->delete($rehearsalId);

            if ($result) {
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true]);
                    exit;
                }
                $this->setFlash('success', 'Probe wurde erfolgreich gelöscht.');
            } else {
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'message' => 'Probe konnte nicht gelöscht werden',
                        'debug_message' => 'Database delete operation returned false'
                    ]);
                    exit;
                }
                $this->setFlash('error', 'Probe konnte nicht gelöscht werden');
            }
        } catch (\Exception $e) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Fehler beim Löschen der Probe',
                    'debug_message' => $e->getMessage() . "\n" . $e->getTraceAsString()
                ]);
                exit;
            }
            $this->setFlash('error', 'Fehler beim Löschen: ' . $e->getMessage());
        }

        $this->redirect('/' . $_SESSION['current_orchestra_id'] . '/rehearsals');
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

            $html = '';
            foreach ($paginatedRehearsals as $rehearsal) {
                // Set options for the rehearsal card component
                $context = 'rehearsals';
                $options = [
                    'showButtons' => true,
                    // Pass other necessary options
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
