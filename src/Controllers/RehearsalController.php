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

    // AI import (preview + import share one normalization pipeline)
    private const AI_IMPORT_MAX_ROWS = 300;
    private const AI_IMPORT_DEFAULT_COLOR = '#e5e7eb';
    private const AI_IMPORT_DEFAULT_LOCATION = 'Probenraum';
    private const AI_IMPORT_DEFAULT_EMOJI = '❗';
    private const AI_IMPORT_DEFAULT_DURATION = 9000; // 2.5 h in seconds
    // Column limits (rehearsals.type/location, rehearsal_tags.name, rehearsal_schedule_items.label, rehearsal_infos.emoji)
    private const AI_IMPORT_MAX_TYPE = 100;
    private const AI_IMPORT_MAX_LOCATION = 255;
    private const AI_IMPORT_MAX_TAG = 100;
    private const AI_IMPORT_MAX_LABEL = 255;
    private const AI_IMPORT_MAX_EMOJI = 16;
    // ISO weekday => accepted spellings (lower case, no trailing dot); the first one is displayed
    private const AI_IMPORT_WEEKDAYS = [
        1 => ['mo', 'mon', 'montag', 'monday'],
        2 => ['di', 'tu', 'tue', 'tues', 'dienstag', 'tuesday'],
        3 => ['mi', 'we', 'wed', 'mittwoch', 'wednesday'],
        4 => ['do', 'th', 'thu', 'thur', 'thurs', 'donnerstag', 'thursday'],
        5 => ['fr', 'fri', 'freitag', 'friday'],
        6 => ['sa', 'sat', 'samstag', 'sonnabend', 'saturday'],
        7 => ['so', 'su', 'sun', 'sonntag', 'sunday'],
    ];
    private const AI_IMPORT_WEEKDAY_NAMES = [
        1 => 'Montag', 2 => 'Dienstag', 3 => 'Mittwoch', 4 => 'Donnerstag',
        5 => 'Freitag', 6 => 'Samstag', 7 => 'Sonntag',
    ];

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
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Import-Funktion ist deaktiviert']);
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
     * AJAX: Dry run of the AI import.
     *
     * Runs exactly the same normalization/validation as processAiImport() and
     * returns the result per row (normalized values + errors/warnings/notes),
     * but writes nothing.
     */
    public function previewAiImport($params = [])
    {
        $orchestraId = $this->beginAiImportRequest($params);
        $rows = $this->extractAiImportRows($this->readAiImportBody());
        $context = $this->buildAiImportContext($orchestraId);
        $preview = $this->normalizeAiImportRows($rows, $context);

        $this->aiImportRespond(200, [
            'success'    => true,
            'rows'       => $this->withAiImportCards($preview['rows']),
            'summary'    => $preview['summary'],
            'import_tag' => $context['import_tag'],
        ]);
    }

    /**
     * AJAX: Import rehearsals from the JSON produced by the AI prompt.
     *
     * Body: {"rehearsals": [...], "selected": [0, 2, 5]}
     * The rows are re-validated server-side with the same pipeline as the preview.
     * Only the selected rows are imported; if "selected" is missing (legacy clients)
     * every row without errors is imported. If a selected row is (now) invalid the
     * whole request is rejected with the fresh preview so the client can re-render.
     */
    public function processAiImport($params = [])
    {
        $orchestraId = $this->beginAiImportRequest($params);
        $body = $this->readAiImportBody();
        $rows = $this->extractAiImportRows($body);
        $context = $this->buildAiImportContext($orchestraId);
        $preview = $this->normalizeAiImportRows($rows, $context);
        $previewRows = $preview['rows'];

        if (array_key_exists('selected', $body)) {
            if (!is_array($body['selected'])) {
                $this->aiImportFail(422, 'Ungültige Auswahl.');
            }
            $selected = [];
            foreach ($body['selected'] as $idx) {
                $isIndex = is_int($idx) || (is_string($idx) && ctype_digit($idx));
                if (!$isIndex || !isset($previewRows[(int)$idx])) {
                    $this->aiImportFail(422, 'Ungültige Auswahl.');
                }
                $selected[(int)$idx] = true;
            }
            $selected = array_keys($selected);
            sort($selected);
        } else {
            $selected = [];
            foreach ($previewRows as $idx => $row) {
                if ($row['status'] !== 'error') {
                    $selected[] = $idx;
                }
            }
        }

        if (empty($selected)) {
            $this->aiImportFail(422, 'Keine gültigen Termine zum Importieren ausgewählt.', [
                'rows'       => $this->withAiImportCards($previewRows),
                'summary'    => $preview['summary'],
                'import_tag' => $context['import_tag'],
            ]);
        }

        // Never trust the client: a selected row that does not validate (anymore) aborts the import
        $invalid = array_values(array_filter($selected, fn($idx) => $previewRows[$idx]['status'] === 'error'));
        if (!empty($invalid)) {
            $this->aiImportFail(409, 'Einige ausgewählte Termine sind nicht (mehr) gültig. Bitte prüfe die aktualisierte Vorschau.', [
                'code'       => 'revalidation_failed',
                'invalid'    => $invalid,
                'rows'       => $this->withAiImportCards($previewRows),
                'summary'    => $preview['summary'],
                'import_tag' => $context['import_tag'],
            ]);
        }

        // The Rehearsal model commits its own transaction per call (create, saveTags, ...),
        // so the batch cannot be wrapped in one transaction. Each row is all-or-nothing
        // instead, and failures are reported per row.
        $importTag = $context['import_tag'];
        $roleModel = new Role();
        $createdIds = [];
        $failed = [];

        foreach ($selected as $idx) {
            $result = $this->createAiImportedRehearsal($previewRows[$idx]['data'], $orchestraId, $importTag, $roleModel);
            if ($result['ok']) {
                $createdIds[] = $result['id'];
            } else {
                $failed[] = ['index' => $idx, 'message' => $result['message']];
            }
        }

        $createdCount = count($createdIds);
        $this->aiImportRespond($createdCount > 0 ? 200 : 500, [
            'success'    => $createdCount > 0,
            'count'      => $createdCount,
            'tag'        => $importTag,
            'created'    => $createdIds,
            'failed'     => $failed,
            'message'    => $createdCount > 0 ? null : 'Es konnte kein Termin importiert werden.',
        ]);
    }

    private function withAiImportCards(array $rows): array
    {
        foreach ($rows as &$row) {
            $row['html'] = $row['data']['start'] !== null ? $this->renderAiImportCard($row) : null;
        }
        unset($row);
        return $rows;
    }

    /**
     * The row as the normal rehearsal card (rehearsal-card.php), so the preview
     * looks exactly like the rehearsal will look after the import.
     */
    private function renderAiImportCard(array $row): string
    {
        $data = $row['data'];
        $rehearsal = [
            'id'             => 'ai' . (int)$row['index'],
            'start'          => $data['start'],
            'end'            => $data['end'],
            'location'       => $data['location'],
            'type'           => $data['type'],
            'color'          => $data['color'],
            'groups'         => $data['group_ids'],
            'roles'          => array_map(fn($r) => ['id' => $r['id'], 'name' => $r['name'], 'tag_color' => $r['color']], $data['roles']),
            'infos'          => $data['infos'],
            'schedule_items' => $data['schedule_items'],
            'tags'           => $data['tags'],
        ];
        $context = 'preview';
        $options = ['showButtons' => false];

        // The modal already loads the card styles
        if (!defined('REHEARSAL_CARD_STYLES_LOADED')) {
            define('REHEARSAL_CARD_STYLES_LOADED', true);
        }

        ob_start();
        include __DIR__ . '/../Views/components/rehearsal-card.php';
        return (string)ob_get_clean();
    }

    /**
     * Shared request guard for the AI import endpoints (preview + import).
     *
     * @return int Current orchestra ID
     */
    private function beginAiImportRequest(array $params): int
    {
        $this->validateOrchestraContext($params);
        $this->requirePermission('can_manage_rehearsals');

        header('Content-Type: application/json');
        $orchestraId = (int)$_SESSION['current_orchestra_id'];

        $orchestraModel = new \App\Models\Orchestra();
        $orchestra = $orchestraModel->findById($orchestraId);
        if (empty($orchestra['allow_rehearsal_import'])) {
            $this->aiImportFail(403, 'Import-Funktion ist deaktiviert');
        }

        return $orchestraId;
    }

    private function readAiImportBody(): array
    {
        $body = json_decode(file_get_contents('php://input') ?: '', true);
        if (!is_array($body)) {
            $this->aiImportFail(400, 'Ungültiges JSON.');
        }
        return $body;
    }

    /**
     * Accepts {"rehearsals": [...]} or a bare list of rehearsals.
     */
    private function extractAiImportRows(array $body): array
    {
        if (array_key_exists('rehearsals', $body)) {
            $rows = $body['rehearsals'];
        } elseif ($this->isAiImportList($body)) {
            $rows = $body;
        } else {
            $rows = null;
        }

        if (!is_array($rows) || empty($rows)) {
            $this->aiImportFail(422, 'Keine Proben im JSON gefunden (erwartet: {"rehearsals": [ … ]}).');
        }
        if (count($rows) > self::AI_IMPORT_MAX_ROWS) {
            $this->aiImportFail(422, 'Zu viele Termine auf einmal (maximal ' . self::AI_IMPORT_MAX_ROWS . '). Bitte teile den Import auf.');
        }

        return array_values($rows);
    }

    /**
     * Everything the normalization needs from the database, loaded once per request.
     */
    private function buildAiImportContext(int $orchestraId): array
    {
        $roleModel = new Role();
        $roleMap = [];
        foreach ($roleModel->getByOrchestra($orchestraId) as $role) {
            $key = $this->aiImportKey((string)($role['name'] ?? ''));
            if ($key === '' || isset($roleMap[$key])) continue;
            $roleMap[$key] = [
                'id'    => (int)$role['id'],
                'name'  => (string)$role['name'],
                'color' => (string)($role['tag_color'] ?? '#478cf4'),
            ];
        }

        $groupManager = \App\Core\GroupManager::getInstance();

        $rootGroups = [];
        foreach ($groupManager->getConfig() as $node) {
            if (is_array($node) && isset($node['id'])) {
                $rootGroups[] = $node['id'];
            }
        }

        // Sections (what the prompt offers) win; instruments/aliases only fill gaps
        $groupMap = [];
        $secondary = [];
        $groupNames = [];
        foreach ($groupManager->getAllGroups() as $id => $group) {
            $id = (string)$id;
            $groupNames[$id] = is_scalar($group['display_name'] ?? null) ? (string)$group['display_name'] : $id;
            $isSection = !isset($group['type']) || $group['type'] === '' || $group['type'] === 'section';
            $names = [$group['display_name'] ?? $id, $id];
            if (!empty($group['plural'])) $names[] = $group['plural'];
            if (!empty($group['aliases']) && is_array($group['aliases'])) {
                $names = array_merge($names, $group['aliases']);
            }
            foreach ($names as $name) {
                if (!is_scalar($name)) continue;
                $key = $this->aiImportKey((string)$name);
                if ($key === '') continue;
                if ($isSection) {
                    $groupMap[$key] = $groupMap[$key] ?? $id;
                } else {
                    $secondary[$key] = $secondary[$key] ?? $id;
                }
            }
        }
        $groupMap += $secondary;

        $existingStarts = [];
        foreach ($this->rehearsalModel->findBy('orchestra_id', $orchestraId) as $row) {
            if (!empty($row['start'])) {
                $existingStarts[substr((string)$row['start'], 0, 16)] = true;
            }
        }

        $maxImportId = 0;
        foreach ($this->rehearsalModel->getOrchestraTags($orchestraId) as $tag) {
            if (preg_match('/^Importiert-(\d+)$/i', (string)$tag, $m)) {
                $maxImportId = max($maxImportId, (int)$m[1]);
            }
        }

        return [
            'role_map'        => $roleMap,
            'group_map'       => $groupMap,
            'group_names'     => $groupNames,
            'root_groups'     => $rootGroups,
            'existing_starts' => $existingStarts,
            'import_tag'      => 'Importiert-' . ($maxImportId + 1),
            'now'             => time(),
        ];
    }

    /**
     * Normalize + validate all rows. Pure apart from $context (no DB access), so the
     * preview and the real import produce identical results.
     *
     * @return array{rows: array, summary: array}
     */
    private function normalizeAiImportRows(array $rows, array $context): array
    {
        $normalized = [];
        $startsInImport = [];
        $smartDisplay = null;
        $groupLabels = [];

        foreach (array_values($rows) as $idx => $raw) {
            $row = $this->normalizeAiImportRow($raw, $context);

            // Group description as the rehearsal card renders it ("Tutti", "Streicher (ohne …)");
            // memoized because most rows of an import share the same groups.
            $groupIds = $row['data']['group_ids'];
            if (!empty($groupIds)) {
                $labelKey = implode("\0", $groupIds);
                if (!array_key_exists($labelKey, $groupLabels)) {
                    $groupLabels[$labelKey] = '';
                    try {
                        $smartDisplay = $smartDisplay ?? new \App\Core\SmartGroupDisplay();
                        $groupLabels[$labelKey] = (string)$smartDisplay->generateDescription($groupIds);
                    } catch (\Throwable $e) {
                        // keep the plain list
                    }
                }
                if ($groupLabels[$labelKey] !== '') {
                    $row['data']['groups_label'] = $groupLabels[$labelKey];
                }
            }

            if ($row['data']['start'] !== null) {
                $startsInImport[substr($row['data']['start'], 0, 16)][] = $idx;
            }
            $normalized[$idx] = $row;
        }

        // Possible duplicates: same start as an existing rehearsal or as another row of this import.
        // Every row of an in-import pair is flagged; the cards are adjacent after the date sort.
        foreach ($startsInImport as $key => $indexes) {
            foreach ($indexes as $idx) {
                if (isset($context['existing_starts'][$key])) {
                    $normalized[$idx]['warnings'][] = 'Termin existiert schon';
                    $normalized[$idx]['duplicate'] = true;
                } elseif (count($indexes) > 1) {
                    $normalized[$idx]['warnings'][] = 'Doppelt im Import';
                    $normalized[$idx]['duplicate'] = true;
                }
            }
        }

        $result = [];
        $summary = ['total' => 0, 'ok' => 0, 'warning' => 0, 'error' => 0];
        foreach ($normalized as $idx => $row) {
            $row['status'] = !empty($row['errors']) ? 'error' : (!empty($row['warnings']) ? 'warning' : 'ok');
            $row['duplicate'] = !empty($row['duplicate']);
            $row = ['index' => $idx] + $row;

            $summary['total']++;
            $summary[$row['status']]++;
            $result[] = $row;
        }

        return ['rows' => $result, 'summary' => $summary];
    }

    /**
     * Normalize a single AI row. Silent fallbacks of the old import are now reported:
     * errors block the row, warnings and notes are informational.
     */
    private function normalizeAiImportRow($raw, array $context): array
    {
        $errors = [];
        $warnings = [];
        $notes = [];
        $weekdayMismatch = null;

        $data = [
            'start'           => null,
            'end'             => null,
            'location'        => mb_strtoupper(self::AI_IMPORT_DEFAULT_LOCATION, 'UTF-8'),
            'type'            => '',
            'color'           => self::AI_IMPORT_DEFAULT_COLOR,
            'group_ids'       => [],
            'groups'          => [],
            'groups_label'    => '',
            'role_ids'        => [],
            'roles'           => [],
            'tags'            => [],
            'schedule_items'  => [],
            'infos'           => [],
            'is_past'         => false,
        ];

        if (!is_array($raw) || ($raw !== [] && $this->isAiImportList($raw))) {
            return [
                'errors'           => ['Eintrag ist kein gültiges Termin-Objekt.'],
                'warnings'         => [],
                'notes'            => [],
                'weekday_mismatch' => null,
                'data'             => $data,
            ];
        }

        // ── Start / end ──
        $startRaw = $raw['start'] ?? null;
        $start = $this->parseAiImportDateTime($startRaw);
        if ($start === null) {
            if ($this->isAiImportBlank($startRaw)) {
                $errors[] = 'Startzeit fehlt';
            } elseif (is_string($startRaw) && preg_match('/^\s*\d{4}-\d{1,2}-\d{1,2}\s*$/', $startRaw)) {
                $errors[] = 'Uhrzeit fehlt';
            } else {
                $errors[] = 'Startzeit ungültig';
            }
        }

        $end = null;
        if ($start !== null) {
            $endRaw = $raw['end'] ?? null;
            $end = $this->parseAiImportDateTime($endRaw);
            if ($end === null && is_string($endRaw)) {
                // Lenient: "22:00" means "same day as the start"
                $time = $this->parseAiImportTime($endRaw, true);
                if ($time !== null) {
                    $end = substr($start, 0, 10) . ' ' . $time;
                }
            }

            $startTs = strtotime($start);
            if ($end === null) {
                $end = date('Y-m-d H:i:s', $startTs + self::AI_IMPORT_DEFAULT_DURATION);
                $endLabel = $this->formatAiImportMoment($end, $start);
                if ($this->isAiImportBlank($endRaw)) {
                    $warnings[] = 'Ende fehlt, ' . $endLabel . ' angenommen';
                } else {
                    $warnings[] = 'Ende ungültig, ' . $endLabel . ' angenommen';
                }
            } else {
                $endTs = strtotime($end);
                if ($endTs < $startTs) {
                    $errors[] = 'Ende vor Beginn';
                } elseif ($endTs === $startTs) {
                    $warnings[] = 'Beginn gleich Ende';
                } elseif (substr($end, 0, 10) !== substr($start, 0, 10)) {
                    $notes[] = 'Mehrtägiger Termin bis ' . date('d.m.Y', $endTs) . '.';
                }
            }

            $data['start'] = $start;
            $data['end'] = $end;
            $data['is_past'] = strtotime($end) < $context['now'];
            if ($data['is_past']) {
                $warnings[] = 'Liegt in der Vergangenheit';
            }

            // Weekday as written in the plan: a mismatch usually means a wrong date
            $weekday = $this->parseAiImportWeekday($raw['weekday'] ?? null);
            if ($weekday !== null && $weekday !== (int)date('N', $startTs)) {
                $weekdayMismatch = 'Im Plan steht ' . self::AI_IMPORT_WEEKDAY_NAMES[$weekday]
                    . ', der ' . date('d.m.', $startTs) . ' ist aber ein ' . self::AI_IMPORT_WEEKDAY_NAMES[(int)date('N', $startTs)];
                $warnings[] = $weekdayMismatch;
            }
        }

        // ── Location ──
        $location = $raw['location'] ?? null;
        if ($location !== null && !is_scalar($location)) {
            $warnings[] = 'Ort ungültig';
            $location = null;
        }
        $location = $this->cleanAiImportText((string)$location);
        if ($location === '') {
            $location = self::AI_IMPORT_DEFAULT_LOCATION;
        }
        if (mb_strlen($location, 'UTF-8') > self::AI_IMPORT_MAX_LOCATION) {
            $location = mb_substr($location, 0, self::AI_IMPORT_MAX_LOCATION, 'UTF-8');
            $warnings[] = 'Ort gekürzt';
        }
        // Same casing the model applies on create, so the preview shows what gets stored
        $data['location'] = mb_strtoupper($location, 'UTF-8');

        // ── Type ──
        $type = $raw['type'] ?? '';
        if ($type !== null && !is_scalar($type)) {
            $warnings[] = 'Typ ungültig';
            $type = '';
        }
        $type = $this->cleanAiImportText((string)$type);
        if (mb_strlen($type, 'UTF-8') > self::AI_IMPORT_MAX_TYPE) {
            $type = mb_substr($type, 0, self::AI_IMPORT_MAX_TYPE, 'UTF-8');
            $warnings[] = 'Typ gekürzt';
        }
        $data['type'] = mb_strtoupper($type, 'UTF-8');

        // ── Color ──
        $colorRaw = $raw['color'] ?? null;
        if (!$this->isAiImportBlank($colorRaw)) {
            $color = is_string($colorRaw) ? strtolower(trim($colorRaw)) : '';
            if (preg_match('/^#?([0-9a-f]{6})$/', $color, $m)) {
                $data['color'] = '#' . $m[1];
            } elseif (preg_match('/^#?([0-9a-f])([0-9a-f])([0-9a-f])$/', $color, $m)) {
                $data['color'] = '#' . $m[1] . $m[1] . $m[2] . $m[2] . $m[3] . $m[3];
            } else {
                $warnings[] = 'Farbe ungültig';
            }
        }

        // ── Groups ──
        $groupsRaw = $raw['groups'] ?? [];
        if (is_string($groupsRaw)) {
            $groupsRaw = trim($groupsRaw) === '' ? [] : [$groupsRaw];
        }
        if (!is_array($groupsRaw)) {
            $warnings[] = 'Gruppen ungültig';
            $groupsRaw = [];
        }
        $groupIds = [];
        $unknownGroups = [];
        foreach ($groupsRaw as $groupName) {
            // Like the old import: null/false/empty entries are skipped silently
            if ($groupName === false || $this->isAiImportBlank($groupName)) continue;
            if (!is_scalar($groupName)) {
                $unknownGroups[] = $this->aiImportExcerpt($groupName);
                continue;
            }
            $label = $this->cleanAiImportText((string)$groupName);
            if ($label === '') continue;
            $key = $this->aiImportKey($label);
            if (isset($context['group_map'][$key])) {
                $id = $context['group_map'][$key];
                if (!in_array($id, $groupIds, true)) {
                    $groupIds[] = $id;
                }
            } else {
                $unknownGroups[] = $label;
            }
        }
        $unknownGroups = array_values(array_unique($unknownGroups));
        if (empty($groupIds)) {
            $groupIds = $context['root_groups'];
            // An empty list is the prompt's convention for Tutti (shown as group chip); only a
            // fallback caused by unknown names is worth a warning.
            if (!empty($unknownGroups)) {
                $warnings[] = 'Unbekannt: ' . implode(', ', $unknownGroups) . ', daher Tutti';
            }
            if (empty($groupIds)) {
                $warnings[] = 'Keine Register eingerichtet';
            }
        } elseif (!empty($unknownGroups)) {
            $warnings[] = 'Unbekannt: ' . implode(', ', $unknownGroups);
        }
        $data['group_ids'] = $groupIds;
        $data['groups'] = array_map(fn($id) => [
            'id'   => $id,
            'name' => $context['group_names'][$id] ?? $id,
        ], $groupIds);
        // Plain list; normalizeAiImportRows() replaces it with the smart description
        $data['groups_label'] = implode(', ', array_column($data['groups'], 'name'));

        // ── Roles (restrict visibility, so anything unclear blocks the row) ──
        $rolesRaw = $raw['roles'] ?? [];
        if (is_string($rolesRaw)) {
            $rolesRaw = trim($rolesRaw) === '' ? [] : [$rolesRaw];
        }
        if (!is_array($rolesRaw)) {
            $errors[] = 'Rollen ungültig';
            $rolesRaw = [];
        }
        $unknownRoles = [];
        foreach ($rolesRaw as $roleName) {
            if ($roleName === false || $this->isAiImportBlank($roleName)) continue;
            if (!is_scalar($roleName)) {
                $unknownRoles[] = $this->aiImportExcerpt($roleName);
                continue;
            }
            $label = $this->cleanAiImportText((string)$roleName);
            if ($label === '') continue;
            $key = $this->aiImportKey($label);
            if (isset($context['role_map'][$key])) {
                $role = $context['role_map'][$key];
                if (!in_array($role['id'], $data['role_ids'], true)) {
                    $data['role_ids'][] = $role['id'];
                    $data['roles'][] = $role;
                }
            } else {
                $unknownRoles[] = $label;
            }
        }
        $unknownRoles = array_values(array_unique($unknownRoles));
        if (!empty($unknownRoles)) {
            $errors[] = 'Rolle unbekannt: ' . implode(', ', $unknownRoles);
        }

        // ── Tags ──
        $tagsRaw = $raw['tags'] ?? [];
        if (is_string($tagsRaw)) {
            $tagsRaw = trim($tagsRaw) === '' ? [] : [$tagsRaw];
        }
        if (!is_array($tagsRaw)) {
            $warnings[] = 'Tags ungültig';
            $tagsRaw = [];
        }
        $seenTags = [];
        foreach ($tagsRaw as $tag) {
            if (!is_scalar($tag)) continue;
            $tag = $this->cleanAiImportText(ltrim(trim((string)$tag), '#'));
            if ($tag === '') continue;
            if (mb_strlen($tag, 'UTF-8') > self::AI_IMPORT_MAX_TAG) {
                $tag = mb_substr($tag, 0, self::AI_IMPORT_MAX_TAG, 'UTF-8');
                $warnings[] = 'Tag gekürzt';
            }
            $key = mb_strtolower($tag, 'UTF-8');
            if (isset($seenTags[$key])) continue;
            $seenTags[$key] = true;
            $data['tags'][] = $tag;
        }

        // ── Schedule items ──
        $itemsRaw = $raw['schedule_items'] ?? [];
        if ($itemsRaw === null || $itemsRaw === '') {
            $itemsRaw = [];
        }
        if (!is_array($itemsRaw)) {
            $warnings[] = 'Ablauf ungültig';
            $itemsRaw = [];
        }
        foreach ($itemsRaw as $item) {
            $timeRaw = null;
            $label = '';
            if (is_array($item)) {
                $timeRaw = $item['time'] ?? null;
                $label = is_scalar($item['label'] ?? null) ? $this->cleanAiImportText((string)$item['label']) : '';
            } elseif (is_string($item) && preg_match('/^\s*(\d{1,2}[:.]\d{2})\s*(?:Uhr)?\s*[-–:]?\s*(.+)$/u', $item, $m)) {
                $timeRaw = $m[1];
                $label = $this->cleanAiImportText($m[2]);
            } elseif (is_string($item)) {
                $label = $this->cleanAiImportText($item);
            }

            $time = $this->parseAiImportTime($timeRaw, false);
            if ($label === '') {
                $warnings[] = 'Ablaufpunkt ohne Text übersprungen';
                continue;
            }
            if ($time === null) {
                $warnings[] = 'Ablaufpunkt ohne Uhrzeit übersprungen';
                continue;
            }
            if (mb_strlen($label, 'UTF-8') > self::AI_IMPORT_MAX_LABEL) {
                $label = mb_substr($label, 0, self::AI_IMPORT_MAX_LABEL, 'UTF-8');
                $warnings[] = 'Ablaufpunkt gekürzt';
            }
            $data['schedule_items'][] = ['time' => $time, 'label' => $label];
        }

        // ── Infos ──
        $infosRaw = $raw['infos'] ?? [];
        if ($infosRaw === null || $infosRaw === '') {
            $infosRaw = [];
        }
        if (is_string($infosRaw)) {
            $infosRaw = [$infosRaw];
        }
        if (!is_array($infosRaw)) {
            $warnings[] = 'Infos ungültig';
            $infosRaw = [];
        }
        $emptyInfos = 0;
        foreach ($infosRaw as $info) {
            $emoji = '';
            $text = '';
            if (is_array($info)) {
                $emoji = is_scalar($info['emoji'] ?? null) ? trim((string)$info['emoji']) : '';
                $text = is_scalar($info['text'] ?? null) ? trim((string)$info['text']) : '';
            } elseif (is_scalar($info)) {
                $text = trim((string)$info);
            }
            if ($text === '') {
                $emptyInfos++;
                continue;
            }
            if ($emoji === '' || mb_strlen($emoji, 'UTF-8') > self::AI_IMPORT_MAX_EMOJI) {
                $emoji = self::AI_IMPORT_DEFAULT_EMOJI;
            }
            $data['infos'][] = ['emoji' => $emoji, 'text' => $text];
        }
        if ($emptyInfos > 0) {
            $warnings[] = 'Leere Infos übersprungen';
        }

        return [
            'errors'           => $errors,
            'warnings'         => $warnings,
            'notes'            => $notes,
            'weekday_mismatch' => $weekdayMismatch,
            'data'             => $data,
        ];
    }

    /**
     * Create one normalized row. All-or-nothing: if a follow-up save fails, the
     * freshly created rehearsal is removed again.
     *
     * @return array{ok: bool, id?: int, message?: string}
     */
    private function createAiImportedRehearsal(array $row, int $orchestraId, string $importTag, Role $roleModel): array
    {
        $rehearsalId = 0;
        try {
            $created = $this->rehearsalModel->create([
                'start'        => $row['start'],
                'end'          => $row['end'],
                'location'     => $row['location'],
                'type'         => $row['type'],
                'color'        => $row['color'],
                'orchestra_id' => $orchestraId,
            ], $row['group_ids']);

            if (!$created || is_array($created)) {
                $message = (is_array($created) && !empty($created['message'])) ? $created['message'] : 'Termin konnte nicht gespeichert werden.';
                return ['ok' => false, 'message' => $message];
            }
            $rehearsalId = (int)$created;

            $tags = [];
            foreach (array_merge($row['tags'], [$importTag]) as $tag) {
                $tags[mb_strtolower($tag, 'UTF-8')] = $tag;
            }
            $ok = $this->rehearsalModel->saveTags($rehearsalId, $orchestraId, array_values($tags));

            if ($ok && !empty($row['schedule_items'])) {
                $ok = $this->rehearsalModel->saveScheduleItems($rehearsalId, $row['schedule_items']);
            }
            if ($ok && !empty($row['infos'])) {
                $ok = $this->rehearsalModel->saveInfos($rehearsalId, $row['infos']);
            }
            if ($ok && !empty($row['role_ids'])) {
                $roleModel->setRehearsalRoles($rehearsalId, $row['role_ids']);
            }

            // mysqli does not throw here (report mode off) and the save helpers report success
            // even when single inserts fail, so check what actually landed. Missing roles would
            // make a restricted rehearsal visible to everyone.
            if ($ok && !$this->aiImportedRehearsalIsComplete($rehearsalId, $row, array_keys($tags))) {
                error_log('AI import row incomplete after save, rolling back rehearsal ' . $rehearsalId);
                $ok = false;
            }

            if (!$ok) {
                $this->rollbackAiImportedRehearsal($rehearsalId);
                return ['ok' => false, 'message' => 'Rollen, Tags, Ablauf oder Infos konnten nicht gespeichert werden – Termin wurde nicht angelegt.'];
            }

            return ['ok' => true, 'id' => $rehearsalId];
        } catch (\Throwable $e) {
            error_log('AI import row failed: ' . $e->getMessage());
            if ($rehearsalId > 0) {
                $this->rollbackAiImportedRehearsal($rehearsalId);
            }
            return ['ok' => false, 'message' => 'Termin konnte nicht gespeichert werden.'];
        }
    }

    /**
     * Compare the stored roles, tags, schedule items and infos with the normalized row.
     *
     * @param string[] $expectedTags Lower-cased tag names (incl. the import tag)
     */
    private function aiImportedRehearsalIsComplete(int $rehearsalId, array $row, array $expectedTags): bool
    {
        $storedRoles = $this->rehearsalModel->getRehearsalRoleIds($rehearsalId);
        $expectedRoles = array_map('intval', $row['role_ids']);
        sort($storedRoles);
        sort($expectedRoles);
        if ($storedRoles !== $expectedRoles) {
            return false;
        }

        $storedTags = array_map(fn($t) => mb_strtolower((string)$t, 'UTF-8'), $this->rehearsalModel->getTags($rehearsalId));
        if (array_diff($expectedTags, $storedTags)) {
            return false;
        }

        // The model skips entries with an empty() label/text, mirror that when counting
        if (!empty($row['schedule_items'])) {
            $expected = count(array_filter($row['schedule_items'], fn($i) => !empty($i['label'])));
            if (count($this->rehearsalModel->getScheduleItems($rehearsalId)) !== $expected) {
                return false;
            }
        }
        if (!empty($row['infos'])) {
            $expected = count(array_filter($row['infos'], fn($i) => !empty($i['text'])));
            if (count($this->rehearsalModel->getInfos($rehearsalId)) !== $expected) {
                return false;
            }
        }

        return true;
    }

    /**
     * Remove a half-imported rehearsal (groups, tags, roles, schedule and infos cascade).
     */
    private function rollbackAiImportedRehearsal(int $rehearsalId): void
    {
        try {
            if (!$this->rehearsalModel->delete($rehearsalId)) {
                error_log('AI import rollback failed for rehearsal ' . $rehearsalId);
            }
        } catch (\Throwable $e) {
            error_log('AI import rollback failed for rehearsal ' . $rehearsalId . ': ' . $e->getMessage());
        }
    }

    /**
     * Parse "YYYY-MM-DD HH:MM[:SS]" (also with "T") into "Y-m-d H:i:s", or null.
     */
    private function parseAiImportDateTime($value): ?string
    {
        if (!is_string($value)) return null;
        if (!preg_match('/^\s*(\d{4})-(\d{1,2})-(\d{1,2})[ T](\d{1,2}):(\d{2})(?::(\d{2})(?:\.\d+)?)?\s*$/', $value, $m)) {
            return null;
        }
        [$year, $month, $day, $hour, $minute] = [(int)$m[1], (int)$m[2], (int)$m[3], (int)$m[4], (int)$m[5]];
        $second = isset($m[6]) && $m[6] !== '' ? (int)$m[6] : 0;
        if (!checkdate($month, $day, $year) || $hour > 23 || $minute > 59 || $second > 59) {
            return null;
        }
        return sprintf('%04d-%02d-%02d %02d:%02d:%02d', $year, $month, $day, $hour, $minute, $second);
    }

    /**
     * Parse "H:MM", "HH:MM", "HH:MM:SS" or "HH.MM" into "HH:MM" (or "HH:MM:SS" with $withSeconds).
     */
    private function parseAiImportTime($value, bool $withSeconds): ?string
    {
        if (!is_string($value)) return null;
        if (!preg_match('/^\s*(\d{1,2})[:.](\d{2})(?::(\d{2}))?\s*(?:Uhr)?\s*$/u', $value, $m)) {
            return null;
        }
        $hour = (int)$m[1];
        $minute = (int)$m[2];
        $second = isset($m[3]) && $m[3] !== '' ? (int)$m[3] : 0;
        if ($hour > 23 || $minute > 59 || $second > 59) {
            return null;
        }
        return $withSeconds
            ? sprintf('%02d:%02d:%02d', $hour, $minute, $second)
            : sprintf('%02d:%02d', $hour, $minute);
    }

    /**
     * "Sa", "Sa.", "Samstag", "Sat", "saturday" … into the ISO weekday (1 = Monday), or null.
     */
    private function parseAiImportWeekday($value): ?int
    {
        if (!is_string($value)) return null;
        $key = rtrim(mb_strtolower(trim($value), 'UTF-8'), '. ');
        foreach (self::AI_IMPORT_WEEKDAYS as $number => $names) {
            if (in_array($key, $names, true)) return $number;
        }
        return null;
    }

    /**
     * "19:30 Uhr" when on the reference day, otherwise "06.10.2026, 01:00 Uhr".
     */
    private function formatAiImportMoment(string $dateTime, string $reference): string
    {
        $ts = strtotime($dateTime);
        $time = date('H:i', $ts) . ' Uhr';
        return substr($dateTime, 0, 10) === substr($reference, 0, 10) ? $time : date('d.m.Y', $ts) . ', ' . $time;
    }

    private function cleanAiImportText(string $value): string
    {
        $clean = preg_replace('/\s+/u', ' ', $value);
        return trim($clean === null ? $value : $clean);
    }

    private function aiImportKey(string $value): string
    {
        return mb_strtolower($this->cleanAiImportText($value), 'UTF-8');
    }

    private function isAiImportBlank($value): bool
    {
        return $value === null || (is_string($value) && trim($value) === '') || $value === [];
    }

    private function isAiImportList(array $value): bool
    {
        return $value === [] || array_keys($value) === range(0, count($value) - 1);
    }

    /**
     * Short, single-line representation of a raw value for messages.
     */
    private function aiImportExcerpt($value): string
    {
        if (is_bool($value)) {
            $text = $value ? 'true' : 'false';
        } elseif (is_scalar($value)) {
            $text = (string)$value;
        } else {
            $text = (string)json_encode($value, JSON_UNESCAPED_UNICODE);
        }
        $text = $this->cleanAiImportText($text);
        return mb_strlen($text, 'UTF-8') > 40 ? mb_substr($text, 0, 39, 'UTF-8') . '…' : $text;
    }

    private function aiImportRespond(int $status, array $payload): void
    {
        http_response_code($status);
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }

    private function aiImportFail(int $status, string $message, array $extra = []): void
    {
        $this->aiImportRespond($status, ['success' => false, 'message' => $message, 'error' => $message] + $extra);
    }

    /**
     * AJAX: Return rendered card HTML for a single rehearsal.
     */
    public function getCardHtml($params = [])
    {
        $ctx = $this->validateOrchestraContext($params);
        $this->requirePermission('can_manage_rehearsals');

        $rehearsalId = isset($params['id']) ? (int)$params['id'] : 0;
        if ($rehearsalId <= 0) {
            http_response_code(400);
            echo '';
            exit;
        }

        $rehearsal = $this->requireRehearsalInContext($rehearsalId, $ctx, 'html');

        $orchestraId = (int)$ctx['orchestra_id'];
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
        $ctx = $this->validateOrchestraContext($params);
        $this->requirePermission('can_manage_rehearsals');

        header('Content-Type: application/json');

        $rehearsalId = (int)($params['id'] ?? 0);
        $offsetDays = (int)($_POST['offset_days'] ?? 7);
        if ($rehearsalId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Ungültige Proben-ID']);
            exit;
        }

        $source = $this->requireRehearsalInContext($rehearsalId, $ctx, 'json');

        $orchestraId = (int)$ctx['orchestra_id'];
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

            // Role scoping (only roles of this orchestra)
            $roleIds = $this->filterOwnRoleIds($_POST['role_ids'] ?? [], $availableRoles);

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
        $ctx = $this->validateOrchestraContext($params);

        $this->requirePermission('can_manage_rehearsals');

        $rehearsalId = isset($params['id']) ? intval($params['id']) : 0;

        if ($rehearsalId <= 0) {
            $this->redirect($this->orchestraUrl('/rehearsals'));
            return;
        }

        $rehearsal = $this->requireRehearsalInContext($rehearsalId, $ctx, 'page');

        $orchestraId = (int)$ctx['orchestra_id'];
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

            // Role scoping (only roles of this orchestra)
            $roleIds = $this->filterOwnRoleIds($_POST['role_ids'] ?? [], $availableRoles);

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
        // Deleting is POST-only (all callers POST via fetch); a GET link must never delete
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            http_response_code(405);
            header('Allow: POST');
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Methode nicht erlaubt', 'error' => 'Methode nicht erlaubt']);
            exit;
        }

        // Validate orchestra context and set session variables
        try {
            $ctx = $this->validateOrchestraContext($params);
            // Check if user is a conductor
            $this->requirePermission('can_manage_rehearsals');
        } catch (\Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Nicht berechtigt']);
            exit;
        }

        // Get rehearsal ID from route parameters or POST data
        $rehearsalId = 0;
        if (isset($params['id'])) {
            $rehearsalId = intval($params['id']);
        } else if (isset($_POST['id'])) {
            $rehearsalId = intval($_POST['id']);
        }

        if ($rehearsalId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Ungültige Proben-ID']);
            exit;
        }

        // Only rehearsals of the orchestra from the URL can be deleted
        $this->requireRehearsalInContext($rehearsalId, $ctx, 'json');

        // Delete rehearsal immediately, no confirmation needed
        header('Content-Type: application/json');
        try {
            if ($this->rehearsalModel->delete($rehearsalId)) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Probe konnte nicht gelöscht werden',
                    'debug_message' => 'Database delete operation returned false'
                ]);
            }
        } catch (\Exception $e) {
            // Trace (paths, SQL) only goes to the server log, never to the client
            error_log('Rehearsal delete failed for rehearsal ' . $rehearsalId . ': ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            $response = [
                'success' => false,
                'message' => 'Fehler beim Löschen der Probe',
            ];
            if (defined('APP_ENV') && APP_ENV === 'development') {
                $response['debug_message'] = $e->getMessage();
            }
            echo json_encode($response);
        }
        exit;
    }

    /**
     * Load a rehearsal by ID and make sure it belongs to the orchestra from the URL
     * context. A rehearsal of another orchestra is answered exactly like a missing
     * one, so the response does not reveal that the ID exists elsewhere.
     *
     * @param array  $ctx          Result of validateOrchestraContext()
     * @param string $responseType 'json' (AJAX), 'html' (card partial) or 'page' (full-page form)
     * @return array The rehearsal; otherwise the request ends with 404 (json/html) or
     *               a flash + redirect to the rehearsal list (page, non-AJAX)
     */
    private function requireRehearsalInContext(int $rehearsalId, array $ctx, string $responseType): array
    {
        $rehearsal = $rehearsalId > 0 ? $this->rehearsalModel->findById($rehearsalId) : null;
        if ($rehearsal && (int)($rehearsal['orchestra_id'] ?? 0) === (int)$ctx['orchestra_id']) {
            return $rehearsal;
        }

        if ($responseType === 'page' && !$this->isJsonRequest()) {
            $this->setFlash('error', 'Probe nicht gefunden');
            $this->redirect($this->orchestraUrl('/rehearsals'));
            exit;
        }

        http_response_code(404);
        if ($responseType === 'html') {
            echo '';
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Probe nicht gefunden', 'error' => 'Probe nicht gefunden']);
        }
        exit;
    }

    /**
     * Role IDs from a form (role_ids[] or JSON string), restricted to roles of the
     * current orchestra. Foreign or unknown IDs are dropped silently.
     *
     * @param mixed $raw            $_POST['role_ids']
     * @param array $availableRoles Role::getByOrchestra() of the current orchestra
     * @return int[]
     */
    private function filterOwnRoleIds($raw, array $availableRoles): array
    {
        if (is_string($raw)) {
            $raw = json_decode($raw, true);
        }
        if (!is_array($raw)) {
            return [];
        }

        $allowed = [];
        foreach ($availableRoles as $role) {
            $allowed[(int)$role['id']] = true;
        }

        $roleIds = [];
        foreach ($raw as $id) {
            if (!is_scalar($id)) continue;
            $id = (int)$id;
            if ($id > 0 && isset($allowed[$id])) {
                $roleIds[$id] = $id;
            }
        }
        return array_values($roleIds);
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
