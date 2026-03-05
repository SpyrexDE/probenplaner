<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Validator;
use App\Models\Rehearsal;
use App\Models\Role;
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

        $this->requirePermission('can_manage_rehearsals');

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

        $this->requirePermission('can_manage_rehearsals');

        $orchestraId = (int)$_SESSION['current_orchestra_id'];
        $roleModel = new Role();
        $availableRoles = $roleModel->getByOrchestra($orchestraId);
        $defaultRoles = $roleModel->getDefaultRoles($orchestraId);
        $defaultRoleIds = array_map(fn($r) => (int)$r['id'], $defaultRoles);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $start = \App\Core\Validator::sanitizeUtf8($_POST['start'] ?? '');
            $end = \App\Core\Validator::sanitizeUtf8($_POST['end'] ?? '');
            $location = \App\Core\Validator::sanitizeUtf8($_POST['location'] ?? '');
            $color = \App\Core\Validator::sanitizeUtf8($_POST['color'] ?? '');

            $rehearsalType = \App\Core\Validator::sanitizeUtf8($_POST['rehearsal_type'] ?? '');
            $finalGroups = \App\Core\RehearsalGroupProcessor::processGroups($_POST);
            $groupValidationErrors = \App\Core\RehearsalGroupProcessor::validateGroups($finalGroups);

            // Role scoping
            $roleIds = $_POST['role_ids'] ?? [];
            if (is_string($roleIds)) $roleIds = json_decode($roleIds, true) ?: [];
            $roleIds = array_map('intval', array_filter($roleIds));

            $requiredValidation = \App\Core\Validator::validateRequired([
                'start' => $start,
                'end' => $end
            ], ['start', 'end']);

            $startValidation = \App\Core\Validator::validateDateTime($start);
            $endValidation = \App\Core\Validator::validateDateTime($end);

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
                $rehearsalData = [
                    'start' => $start,
                    'end' => $end,
                    'type' => $rehearsalType,
                    'location' => $location,
                    'orchestra_id' => $orchestraId,
                ];

                if (!empty($color)) {
                    $rehearsalData['color'] = $color;
                }

                $result = $this->rehearsalModel->create($rehearsalData, $finalGroups);

                if ($result && !is_array($result)) {
                    // Save role scoping
                    if (!empty($roleIds)) {
                        $roleModel->setRehearsalRoles($result, $roleIds);
                    }

                    $scheduleJson = $_POST['schedule_items'] ?? '';
                    if (!empty($scheduleJson)) {
                        $scheduleItems = json_decode($scheduleJson, true) ?: [];
                        $this->rehearsalModel->saveScheduleItems($result, $scheduleItems);
                    }

                    $infosJson = $_POST['infos'] ?? '';
                    if (!empty($infosJson)) {
                        $infosItems = json_decode($infosJson, true) ?: [];
                        $this->rehearsalModel->saveInfos($result, $infosItems);
                    }

                    $this->setFlash('success', 'Probe wurde erfolgreich erstellt.');
                    $this->redirect($this->orchestraUrl('/rehearsals'));
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
                }
            }

            $this->render('rehearsals/create', [
                'currentPage' => 'rehearsals',
                'errors' => $errors,
                'availableRoles' => $availableRoles,
                'formData' => [
                    'start' => $start,
                    'end' => $end,
                    'location' => $location,
                    'color' => $color,
                    'rehearsal_type' => $rehearsalType,
                    'groups' => $finalGroups,
                    'role_ids' => $roleIds,
                ]
            ]);
        } else {
            $this->render('rehearsals/create', [
                'currentPage' => 'rehearsals',
                'errors' => [],
                'availableRoles' => $availableRoles,
                'formData' => [
                    'start' => '',
                    'end' => '',
                    'location' => '',
                    'color' => Constants::COLOR_WHITE,
                    'rehearsal_type' => '',
                    'groups' => [],
                    'role_ids' => $defaultRoleIds,
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

        $this->requirePermission('can_manage_rehearsals');

        $rehearsalId = isset($params['id']) ? intval($params['id']) : 0;

        if ($rehearsalId <= 0) {
            $this->redirect($this->orchestraUrl('/rehearsals'));
            return;
        }

        $rehearsal = $this->rehearsalModel->findById($rehearsalId);

        if (!$rehearsal) {
            $this->setFlash('error', 'Probe nicht gefunden');
            $this->redirect($this->orchestraUrl('/rehearsals'));
            return;
        }

        $orchestraId = (int)$_SESSION['current_orchestra_id'];
        $roleModel = new Role();
        $availableRoles = $roleModel->getByOrchestra($orchestraId);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $start = \App\Core\Validator::sanitizeUtf8($_POST['start'] ?? '');
            $end = \App\Core\Validator::sanitizeUtf8($_POST['end'] ?? '');
            $location = \App\Core\Validator::sanitizeUtf8($_POST['location'] ?? '');
            $color = \App\Core\Validator::sanitizeUtf8($_POST['color'] ?? '');

            $rehearsalType = \App\Core\Validator::sanitizeUtf8($_POST['rehearsal_type'] ?? '');
            $finalGroups = \App\Core\RehearsalGroupProcessor::processGroups($_POST);
            $groupValidationErrors = \App\Core\RehearsalGroupProcessor::validateGroups($finalGroups);

            // Role scoping
            $roleIds = $_POST['role_ids'] ?? [];
            if (is_string($roleIds)) $roleIds = json_decode($roleIds, true) ?: [];
            $roleIds = array_map('intval', array_filter($roleIds));

            $requiredValidation = \App\Core\Validator::validateRequired([
                'start' => $start,
                'end' => $end
            ], ['start', 'end']);

            $startValidation = \App\Core\Validator::validateDateTime($start);
            $endValidation = \App\Core\Validator::validateDateTime($end);

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
                $updateData = [
                    'start' => $start,
                    'end' => $end,
                    'type' => $rehearsalType,
                    'location' => $location,
                ];

                if (!empty($color)) {
                    $updateData['color'] = $color;
                }

                $result = $this->rehearsalModel->updateRehearsal($rehearsalId, $updateData, $finalGroups);

                if ($result === true) {
                    // Update role scoping
                    $roleModel->setRehearsalRoles($rehearsalId, $roleIds);

                    $this->setFlash('success', 'Probe wurde erfolgreich aktualisiert.');
                    $this->redirect($this->orchestraUrl('/rehearsals'));
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

            $this->render('rehearsals/edit', [
                'currentPage' => 'rehearsals',
                'rehearsal' => $rehearsal,
                'errors' => $errors,
                'availableRoles' => $availableRoles,
                'formData' => [
                    'start' => $start,
                    'end' => $end,
                    'location' => $location,
                    'color' => $color,
                    'rehearsal_type' => $rehearsalType,
                    'groups' => $finalGroups,
                    'role_ids' => $roleIds,
                ]
            ]);
        } else {
            $rehearsalType = $rehearsal['type'] ?? '';
            $groups = $rehearsal['groups'] ?? [];
            $formData = \App\Core\RehearsalGroupProcessor::generateFormData($groups);

            // Load existing role scoping
            $existingRoles = $roleModel->getRehearsalRoles($rehearsalId);
            $existingRoleIds = array_map(fn($r) => (int)$r['id'], $existingRoles);

            $this->render('rehearsals/edit', [
                'currentPage' => 'rehearsals',
                'rehearsal' => $rehearsal,
                'errors' => [],
                'availableRoles' => $availableRoles,
                'formData' => [
                    'start' => $rehearsal['start'] ?? '',
                    'end' => $rehearsal['end'] ?? '',
                    'location' => $rehearsal['location'],
                    'color' => $rehearsal['color'] ?? '',
                    'rehearsal_type' => $rehearsalType,
                    'groups' => $formData['groups'],
                    'role_ids' => $existingRoleIds,
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
            $this->requirePermission('can_manage_rehearsals');
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
            $this->redirect($this->orchestraUrl('/rehearsals'));
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
                $this->setFlash('error', 'Probe konnte nicht gelöscht werden', 'Database delete returned false');
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
            $this->setFlash('error', 'Fehler beim Löschen: ' . $e->getMessage(), $e->getMessage() . "\n" . $e->getTraceAsString());
        }

        $this->redirect($this->orchestraUrl('/rehearsals'));
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
