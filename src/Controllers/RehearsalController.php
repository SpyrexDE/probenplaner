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
    private const INITIAL_LIMIT = 5;
    private const LAZY_BATCH_SIZE = 10;

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
     * Display rehearsal list with initial batch, lazy-loading the rest.
     */
    public function index($params = [])
    {
        $this->validateOrchestraContext($params);

        $this->requirePermission('can_manage_rehearsals');

        if (isset($_GET['ajax']) && $_GET['pastOnly']) {
            $this->handlePastRehearsalsAjax();
            return;
        }

        $orchestraId = (int)$_SESSION['current_orchestra_id'];
        $allRehearsals = $this->rehearsalModel->getUpcoming($orchestraId, false);
        $totalRehearsals = count($allRehearsals);
        $hasMore = $totalRehearsals > self::INITIAL_LIMIT;

        $rehearsals = $hasMore ? array_slice($allRehearsals, 0, self::INITIAL_LIMIT) : $allRehearsals;
        $hasPastRehearsals = $this->rehearsalModel->hasPastRehearsals($orchestraId);

        $roleModel = new Role();
        $availableRoles = $roleModel->getByOrchestra($orchestraId);

        $groupManager = \App\Core\GroupManager::getInstance();
        $groupConfig = $groupManager->getConfig();

        $orchestraModel = new \App\Models\Orchestra();
        $orchestra = $orchestraModel->findById($orchestraId);

        $this->render('rehearsals/index', [
            'currentPage' => 'rehearsals',
            'rehearsals' => $rehearsals,
            'hasPastRehearsals' => $hasPastRehearsals,
            'availableRoles' => $availableRoles,
            'groupConfig' => $groupConfig,
            'hasMoreRehearsals' => $hasMore,
            'totalRehearsals' => $totalRehearsals,
            'allowRehearsalImport' => !empty($orchestra['allow_rehearsal_import']),
        ]);
    }

    /**
     * AJAX endpoint: returns a batch of rehearsal cards as HTML partial.
     */
    public function indexLazy($params = [])
    {
        $this->validateOrchestraContext($params);
        $this->requirePermission('can_manage_rehearsals');

        $offset = max(0, (int)($_GET['offset'] ?? self::INITIAL_LIMIT));
        $orchestraId = (int)$_SESSION['current_orchestra_id'];
        $allRehearsals = $this->rehearsalModel->getUpcoming($orchestraId, false);
        $remaining = array_slice($allRehearsals, $offset);
        $rehearsals = array_slice($remaining, 0, self::LAZY_BATCH_SIZE);
        $hasMore = count($remaining) > self::LAZY_BATCH_SIZE;
        $nextOffset = $offset + count($rehearsals);

        if (empty($rehearsals)) {
            echo '';
            return;
        }

        $context = 'inline-edit';
        $options = ['showButtons' => false];
        $today = date('Y-m-d');

        foreach ($rehearsals as $rehearsal) {
            include APP_ROOT . '/Views/components/rehearsal-card.php';
        }

        if ($hasMore) {
            $base = '/' . ($_SESSION['current_org_slug'] ?? '') . '/' . ($_SESSION['current_orchestra_slug'] ?? '');
            $nextUrl = htmlspecialchars($base . '/rehearsals/lazy?offset=' . $nextOffset);
            echo '<div data-lazy-next-url="' . $nextUrl . '" style="display:none"></div>';
        }
    }

    /**
     * AJAX endpoint: returns a batch of past rehearsal cards as HTML partial.
     */
    public function indexPast($params = [])
    {
        $this->validateOrchestraContext($params);
        $this->requirePermission('can_manage_rehearsals');

        $offset = max(0, (int)($_GET['offset'] ?? 0));
        $orchestraId = (int)$_SESSION['current_orchestra_id'];
        $result = $this->rehearsalModel->getPastPaginated($orchestraId, $offset, 5);

        $rehearsals = array_reverse($result['rows']);
        if (empty($rehearsals)) {
            echo '';
            return;
        }

        $context = 'inline-edit';
        $options = ['showButtons' => false];
        $today = date('Y-m-d');

        foreach ($rehearsals as $rehearsal) {
            include APP_ROOT . '/Views/components/rehearsal-card.php';
        }

        $nextOffset = $offset + count($rehearsals);
        if ($nextOffset < $result['total']) {
            $base = '/' . ($_SESSION['current_org_slug'] ?? '') . '/' . ($_SESSION['current_orchestra_slug'] ?? '');
            $nextUrl = htmlspecialchars($base . '/rehearsals/past?offset=' . $nextOffset);
            echo '<div data-lazy-next-url="' . $nextUrl . '" style="display:none"></div>';
        }
    }

    /**
     * AJAX: Create rehearsal with sensible defaults, return JSON with card HTML.
     */
    public function createAjax($params = [])
    {
        $this->validateOrchestraContext($params);
        $this->requirePermission('can_manage_rehearsals');

        header('Content-Type: application/json');

        $orchestraId = (int)$_SESSION['current_orchestra_id'];

        // Accept optional date from JSON body
        $body = json_decode(file_get_contents('php://input'), true) ?: [];
        $requestedDate = $body['date'] ?? null;

        if ($requestedDate && preg_match('/^\d{4}-\d{2}-\d{2}$/', $requestedDate)) {
            $nextDate = $requestedDate;
        } else {
            $upcoming = $this->rehearsalModel->getUpcoming($orchestraId, false);
            if (!empty($upcoming)) {
                $lastDate = end($upcoming)['start'] ?? null;
                $next = new \DateTime($lastDate ?? 'tomorrow');
                $next->modify('+1 day');
            } else {
                $next = new \DateTime('tomorrow');
            }
            $nextDate = $next->format('Y-m-d');
        }

        $data = [
            'start' => $nextDate . ' 18:00:00',
            'end' => $nextDate . ' 20:00:00',
            'location' => 'Probenraum',
            'type' => '',
            'orchestra_id' => $orchestraId,
        ];

        $groupManager = \App\Core\GroupManager::getInstance();
        $rootGroups = array_map(fn($g) => $g['id'], $groupManager->getConfig());

        $result = $this->rehearsalModel->create($data, $rootGroups);

        if (!$result || is_array($result)) {
            $msg = (is_array($result) && !empty($result['message'])) ? $result['message'] : 'Probe konnte nicht erstellt werden';
            echo json_encode(['success' => false, 'message' => $msg]);
            exit;
        }

        $rehearsal = $this->rehearsalModel->findById($result);

        $roleModel = new Role();
        $context = 'inline-edit';
        $options = ['showButtons' => false, 'expanded' => true];
        $availableRoles = $roleModel->getByOrchestra($orchestraId);
        $smartDisplay = new \App\Core\SmartGroupDisplay();

        ob_start();
        include APP_ROOT . '/Views/components/rehearsal-card.php';
        $html = ob_get_clean();

        echo json_encode(['success' => true, 'id' => $result, 'html' => $html]);
        exit;
    }

    /**
     * AJAX: Batch-create rehearsals from a list of dates with shared properties.
     */
    public function batchCreateAjax($params = [])
    {
        $this->validateOrchestraContext($params);
        $this->requirePermission('can_manage_rehearsals');
        header('Content-Type: application/json');

        $orchestraId = (int)$_SESSION['current_orchestra_id'];
        $body = json_decode(file_get_contents('php://input'), true) ?: [];

        
        $dates         = $body['dates'] ?? [];
        $startTime     = substr($body['start_time'] ?? '18:00', 0, 5);
        $endTime       = substr($body['end_time'] ?? '20:00', 0, 5);
        $type          = $body['type'] ?? '';
        $location      = $body['location'] ?? 'Probenraum';
        $color         = $body['color'] ?? '#e5e7eb';
        $tags          = $body['tags'] ?? [];
        $groups        = $body['groups'] ?? null;
        $scheduleItems = $body['schedule_items'] ?? null;
        $infos         = $body['infos'] ?? null;
        


        if (empty($dates) || !is_array($dates)) {
            echo json_encode(['success' => false, 'message' => 'Keine Termine angegeben']);
            exit;
        }

        // Default groups: all root groups
        if ($groups === null) {
            $groupManager = \App\Core\GroupManager::getInstance();
            $groups = array_map(fn($g) => $g['id'], $groupManager->getConfig());
        }

        $created = 0;
        $lastError = null;
        foreach ($dates as $date) {
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) continue;

            $data = [
                'start'        => $date . ' ' . $startTime . ':00',
                'end'          => $date . ' ' . $endTime . ':00',
                'location'     => $location,
                'type'         => $type,
                'color'        => $color,
                'orchestra_id' => $orchestraId,
            ];

            $rehearsalId = $this->rehearsalModel->create($data, $groups);
            if (!$rehearsalId || is_array($rehearsalId)) {
                if (is_array($rehearsalId) && !empty($rehearsalId['message'])) {
                    $lastError = $rehearsalId['message'];
                }
                continue;
            }

            if (!empty($tags)) {
                $this->rehearsalModel->saveTags($rehearsalId, $orchestraId, $tags);
            }

            // Save Schedule Items / Infos
            if ($scheduleItems !== null) {
                $this->rehearsalModel->saveScheduleItems($rehearsalId, $scheduleItems);
            }
            if ($infos !== null) {
                $this->rehearsalModel->saveInfos($rehearsalId, $infos);
            }

            $created++;
        }

        if ($created === 0 && $lastError) {
            echo json_encode(['success' => false, 'message' => $lastError]);
        } else {
            echo json_encode(['success' => true, 'count' => $created]);
        }
        exit;
    }

    /**
     * AJAX: Get personalized AI prompt for rehearsal import
     */
    public function getAiImportPrompt($params = [])
    {
        $this->validateOrchestraContext($params);
        $this->requirePermission('can_manage_rehearsals');

        $orchestraId = (int)$_SESSION['current_orchestra_id'];

        $orchestraModel = new \App\Models\Orchestra();
        $orchestra = $orchestraModel->findById($orchestraId);
        if (empty($orchestra['allow_rehearsal_import'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Import-Funktion ist deaktiviert']);
            http_response_code(403);
            exit;
        }
        
        $roleModel = new Role();
        $roles = $roleModel->getByOrchestra($orchestraId);
        $rolesFiltered = array_filter($roles, fn($r) => strcasecmp($r['name'], 'Mitglied') !== 0);
        $rolesList = implode(", ", array_map(fn($r) => '"' . $r['name'] . '"', $rolesFiltered));
        
        // Types and locations can be fetched from existing rehearsals
        $rehearsals = $this->rehearsalModel->getUpcoming($orchestraId, true);
        $types = array_filter(array_unique(array_column($rehearsals, 'type')));
        $typesList = implode(", ", array_map(fn($t) => '"' . $t . '"', $types));
        
        $locations = array_filter(array_unique(array_column($rehearsals, 'location')));
        $locationsList = implode(", ", array_map(fn($l) => '"' . $l . '"', $locations));
        
        $groupManager = \App\Core\GroupManager::getInstance();
        $allGroups = $groupManager->getAllGroups();
        $sections = [];
        foreach ($allGroups as $id => $g) {
            if ((isset($g['type']) && $g['type'] === 'section') || empty($g['type'])) {
                $sections[] = $g['display_name'] ?? $id;
            }
        }
        $groupsList = implode(", ", array_map(fn($s) => '"' . $s . '"', $sections));
        
        $promptPath = APP_ROOT . '/Docs/ai_rehearsal_import_prompt.md';
        $promptText = file_exists($promptPath) ? file_get_contents($promptPath) : 'Prompt-Vorlage nicht gefunden.';
        
        $promptText = str_replace(
            ['{{ROLES_LIST}}', '{{LOCATIONS_LIST}}', '{{TYPES_LIST}}', '{{GROUPS_LIST}}'],
            [$rolesList, $locationsList, $typesList, $groupsList],
            $promptText
        );
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'prompt' => $promptText]);
        exit;
    }

    /**
     * AJAX: Process JSON parsed by AI and import rehearsals
     */
    public function processAiImport($params = [])
    {
        $this->validateOrchestraContext($params);
        $this->requirePermission('can_manage_rehearsals');
        
        header('Content-Type: application/json');
        $orchestraId = (int)$_SESSION['current_orchestra_id'];

        $orchestraModel = new \App\Models\Orchestra();
        $orchestra = $orchestraModel->findById($orchestraId);
        if (empty($orchestra['allow_rehearsal_import'])) {
            echo json_encode(['success' => false, 'error' => 'Import-Funktion ist deaktiviert']);
            http_response_code(403);
            exit;
        }
        
        $body = json_decode(file_get_contents('php://input'), true) ?: [];
        $rehearsals = $body['rehearsals'] ?? [];
        
        if (empty($rehearsals) || !is_array($rehearsals)) {
            echo json_encode(['success' => false, 'message' => 'Keine Proben im JSON gefunden']);
            exit;
        }

        $roleModel = new Role();
        $existingRoles = $roleModel->getByOrchestra($orchestraId);
        
        // Build map of lowercased role name to role ID
        $roleMap = [];
        foreach ($existingRoles as $r) {
            $roleMap[strtolower(trim($r['name']))] = (int)$r['id'];
        }

        // Validate roles first
        $missingRoles = [];
        foreach ($rehearsals as $rehearsal) {
            $roles = $rehearsal['roles'] ?? [];
            if (!is_array($roles)) continue;
            foreach ($roles as $rName) {
                if (empty($rName)) continue;
                $key = strtolower(trim($rName));
                if (!isset($roleMap[$key])) {
                    $missingRoles[$rName] = true;
                }
            }
        }

        if (!empty($missingRoles)) {
            $missingList = implode(", ", array_keys($missingRoles));
            echo json_encode([
                'success' => false, 
                'message' => 'Folgende Rollen existieren nicht: ' . $missingList . '. Bitte erstelle sie vor dem Import, oder weise die KI an, nur existierende Rollen zu verwenden.'
            ]);
            exit;
        }

        $groupManager = \App\Core\GroupManager::getInstance();
        $rootGroups = array_map(fn($g) => $g['id'], $groupManager->getConfig());
        
        $allGroups = $groupManager->getAllGroups();
        $sectionMap = [];
        foreach ($allGroups as $id => $g) {
            if ((isset($g['type']) && $g['type'] === 'section') || empty($g['type'])) {
                $name = strtolower(trim($g['display_name'] ?? $id));
                $sectionMap[$name] = $id;
            }
        }

        $createdCount = 0;

        foreach ($rehearsals as $i => $r) {
            // Provide sensible defaults for timestamps if parsing fails
            if (empty($r['start']) || !preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $r['start'])) {
                $r['start'] = date('Y-m-d 19:30:00');
            }
            if (empty($r['end']) || !preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $r['end'])) {
                // Determine sensible end: 2.5h after start
                $startTs = strtotime($r['start']);
                $r['end'] = date('Y-m-d H:i:s', $startTs + (2.5 * 3600));
            }

            $data = [
                'start' => $r['start'],
                'end' => $r['end'],
                'location' => $r['location'] ?? 'Probenraum',
                'type' => $r['type'] ?? '',
                'color' => $r['color'] ?? '#e5e7eb',
                'orchestra_id' => $orchestraId
            ];
            
            // Map groups
            $groupIDsToAssign = [];
            $extractedGroups = $r['groups'] ?? [];
            if (!empty($extractedGroups) && is_array($extractedGroups)) {
                foreach ($extractedGroups as $gn) {
                    $key = strtolower(trim($gn));
                    if (isset($sectionMap[$key])) {
                        $groupIDsToAssign[] = $sectionMap[$key];
                    }
                }
            }
            // Fallback to Tutti if empty
            if (empty($groupIDsToAssign)) {
                $groupIDsToAssign = $rootGroups;
            }

            $rehearsalId = $this->rehearsalModel->create($data, $groupIDsToAssign);
            if (!$rehearsalId || is_array($rehearsalId)) continue;
            
            // Handle tags
            $tags = $r['tags'] ?? [];
            if (!is_array($tags)) $tags = [];
            $tags[] = 'importiert';
            $this->rehearsalModel->saveTags($rehearsalId, $orchestraId, array_unique($tags));

            // Handle schedule
            $schedule = $r['schedule_items'] ?? [];
            if (!empty($schedule) && is_array($schedule)) {
                $this->rehearsalModel->saveScheduleItems($rehearsalId, $schedule);
            }

            // Handle infos
            $infos = $r['infos'] ?? [];
            if (!empty($infos) && is_array($infos)) {
                $this->rehearsalModel->saveInfos($rehearsalId, $infos);
            }

            // Handle roles scoping
            $roleIds = [];
            $roles = $r['roles'] ?? [];
            if (is_array($roles)) {
                foreach ($roles as $rName) {
                    if (empty($rName)) continue;
                    $key = strtolower(trim($rName));
                    if (isset($roleMap[$key])) {
                        $roleIds[] = $roleMap[$key];
                    }
                }
            }
            if (!empty($roleIds)) {
                $roleModel->setRehearsalRoles($rehearsalId, $roleIds);
            }

            $createdCount++;
        }

        echo json_encode(['success' => true, 'count' => $createdCount]);
        exit;
    }

    /**
     * AJAX: Return rendered card HTML for a single rehearsal.
     */
    public function getCardHtml($params = [])
    {
        $this->validateOrchestraContext($params);
        $this->requirePermission('can_manage_rehearsals');

        $rehearsalId = isset($params['id']) ? (int)$params['id'] : 0;
        if ($rehearsalId <= 0) {
            http_response_code(400);
            echo '';
            exit;
        }

        $rehearsal = $this->rehearsalModel->findById($rehearsalId);
        if (!$rehearsal) {
            http_response_code(404);
            echo '';
            exit;
        }

        $orchestraId = (int)$_SESSION['current_orchestra_id'];
        $roleModel = new Role();
        $availableRoles = $roleModel->getByOrchestra($orchestraId);

        $context = 'inline-edit';
        $options = ['showButtons' => false];
        $smartDisplay = new \App\Core\SmartGroupDisplay();

        ob_start();
        include APP_ROOT . '/Views/components/rehearsal-card.php';
        echo ob_get_clean();
        exit;
    }

    /**
     * AJAX: Return all distinct tag names for the current orchestra.
     */
    public function tagsAutocomplete($params = [])
    {
        $this->validateOrchestraContext($params);
        $this->requirePermission('can_manage_rehearsals');

        $orchestraId = (int)$_SESSION['current_orchestra_id'];
        $tags = $this->rehearsalModel->getOrchestraTags($orchestraId);

        header('Content-Type: application/json');
        echo json_encode($tags);
        exit;
    }

    /**
     * AJAX: Duplicate a rehearsal with date shifted +N days.
     */
    public function duplicateAjax($params = [])
    {
        $this->validateOrchestraContext($params);
        $this->requirePermission('can_manage_rehearsals');

        header('Content-Type: application/json');

        $rehearsalId = (int)($params['id'] ?? 0);
        $offsetDays = (int)($_POST['offset_days'] ?? 7);
        if ($rehearsalId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Ungültige Proben-ID']);
            exit;
        }

        $source = $this->rehearsalModel->findById($rehearsalId);
        if (!$source) {
            echo json_encode(['success' => false, 'message' => 'Probe nicht gefunden']);
            exit;
        }

        $orchestraId = (int)$_SESSION['current_orchestra_id'];
        $startDt = new \DateTime($source['start']);
        $endDt = new \DateTime($source['end']);
        $startDt->modify("+{$offsetDays} days");
        $endDt->modify("+{$offsetDays} days");

        $data = [
            'start'        => $startDt->format('Y-m-d H:i:s'),
            'end'          => $endDt->format('Y-m-d H:i:s'),
            'location'     => $source['location'] ?? '',
            'type'         => $source['type'] ?? '',
            'color'        => $source['color'] ?? '',
            'orchestra_id' => $orchestraId,
        ];

        $groups = array_map(fn($g) => $g['id'] ?? $g, $source['groups'] ?? []);
        $newId = $this->rehearsalModel->create($data, $groups);

        if (!$newId || is_array($newId)) {
            echo json_encode(['success' => false, 'message' => 'Duplizieren fehlgeschlagen']);
            exit;
        }

        // Copy tags
        $tags = $source['tags'] ?? [];
        if (!empty($tags)) {
            $this->rehearsalModel->saveTags($newId, $orchestraId, $tags);
        }

        // Copy role scoping
        $roleModel = new Role();
        $existingRoles = $roleModel->getRehearsalRoles($rehearsalId);
        if (!empty($existingRoles)) {
            $roleModel->setRehearsalRoles($newId, array_map(fn($r) => (int)$r['id'], $existingRoles));
        }

        // Copy schedule items and infos
        if (!empty($source['schedule_items'])) {
            $this->rehearsalModel->saveScheduleItems($newId, $source['schedule_items']);
        }
        if (!empty($source['infos'])) {
            $this->rehearsalModel->saveInfos($newId, $source['infos']);
        }

        $rehearsal = $this->rehearsalModel->findById($newId);
        $context = 'inline-edit';
        $options = ['showButtons' => false];
        $availableRoles = $roleModel->getByOrchestra($orchestraId);
        $smartDisplay = new \App\Core\SmartGroupDisplay();

        ob_start();
        include APP_ROOT . '/Views/components/rehearsal-card.php';
        $html = ob_get_clean();

        echo json_encode(['success' => true, 'id' => $newId, 'html' => $html]);
        exit;
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
                    $this->addAlert('Fehler!', $errorMessage, 'error', $errorDetails ?: null);
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
                    $this->addAlert('Fehler!', $errorMessage, 'error', $errorDetails ?: null);
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

            $orchestraId = (int)$_SESSION['current_orchestra_id'];
            $roleModel = new Role();
            $availableRoles = $roleModel->getByOrchestra($orchestraId);
            $smartDisplay = new \App\Core\SmartGroupDisplay();

            $html = '';
            foreach ($paginatedRehearsals as $rehearsal) {
                // Set options for the rehearsal card component
                $context = 'inline-edit';
                $options = [
                    'showButtons' => false,
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
