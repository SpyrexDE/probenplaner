<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Rehearsal;
use App\Core\Utilities;

/**
 * Probenplan Controller
 * Handles the rehearsal plan view
 */
class ProbenplanController extends Controller
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
     * Display rehearsal plan
     * 
     * @param array $params Route parameters containing orchestra_id
     * @return void
     */
    public function index($params = []): void
    {
        $this->validateOrchestraContext($params);
        $this->requirePermission('can_view_schedule');

        // Get personalized view parameter
        $personalized = isset($_GET['personalized']) && $_GET['personalized'] === '1';

        // Get rehearsals
        if ($personalized) {
            $userType = $_SESSION['current_type'] ?? '';

            $userRoleIds = [];
            $userId = $_SESSION['user_id'] ?? null;
            $orchestraId = $_SESSION['current_orchestra_id'] ?? null;
            if ($userId && $orchestraId) {
                $userOrchestraModel = new \App\Models\UserOrchestra();
                $userRoles = $userOrchestraModel->getUserRoles((int)$userId, (int)$orchestraId);
                $userRoleIds = array_map(fn($r) => (int)$r['id'], $userRoles);
            }

            $rehearsals = $this->rehearsalModel->getRelevantForUser(
                $_SESSION['current_orchestra_id'],
                $userType,
                false,
                $userRoleIds
            );
        } else {
            $rehearsals = $this->rehearsalModel->getUpcoming($_SESSION['current_orchestra_id'], false);
        }

        // Get day abbreviations for each rehearsal
        $days = [];
        foreach ($rehearsals as $rehearsal) {
            // Get day abbreviation in German (Mon, Tue, etc.)
            $date = new \DateTime($rehearsal['date']);
            $days[] = Utilities::getGermanDayAbbreviation($date);
        }

        $canManageRehearsals = !empty($_SESSION['current_permissions']['can_manage_rehearsals']);

        $hasPastRehearsals = $this->rehearsalModel->hasPastRehearsals($_SESSION['current_orchestra_id']);

        $this->render('probenplan/index', [
            'currentPage' => 'probenplan',
            'rehearsals' => $rehearsals,
            'days' => $days,
            'personalized' => $personalized,
            'canManageRehearsals' => $canManageRehearsals,
            'hasPastRehearsals' => $hasPastRehearsals
        ]);
    }

    /**
     * AJAX endpoint: returns past rehearsal table rows as HTML.
     */
    public function indexPast($params = []): void
    {
        $this->validateOrchestraContext($params);
        $this->requirePermission('can_view_schedule');

        $personalized = isset($_GET['personalized']) && $_GET['personalized'] === '1';
        $orchestraId = (int)$_SESSION['current_orchestra_id'];

        if ($personalized) {
            $userType = $_SESSION['current_type'] ?? '';
            $userRoleIds = [];
            $userId = $_SESSION['user_id'] ?? null;
            if ($userId) {
                $userOrchestraModel = new \App\Models\UserOrchestra();
                $userRoles = $userOrchestraModel->getUserRoles((int)$userId, $orchestraId);
                $userRoleIds = array_map(fn($r) => (int)$r['id'], $userRoles);
            }
            $rehearsals = $this->rehearsalModel->getRelevantForUser($orchestraId, $userType, true, $userRoleIds);
        } else {
            $rehearsals = $this->rehearsalModel->getUpcoming($orchestraId, true);
        }

        // Filter to only past rehearsals
        $today = date('Y-m-d');
        $pastRehearsals = array_filter($rehearsals, fn($r) => $r['date'] < $today);

        $groupManager = \App\Core\GroupManager::getInstance();

        foreach ($pastRehearsals as $rehearsal) {
            $start_time = substr($rehearsal['start_time'], 0, 5);
            $end_time = substr($rehearsal['end_time'], 0, 5);
            $time_display = $start_time . ' - ' . $end_time;
            $date = new \DateTime($rehearsal['date']);
            $dayAbbr = Utilities::getGermanDayAbbreviation($date);

            $rehearsalType = \App\Core\RehearsalTypeManager::getRehearsalType($rehearsal);
            $typeDisplay = '';
            if (\App\Core\RehearsalTypeManager::shouldDisplayType($rehearsalType)) {
                $typeDisplay = htmlspecialchars($rehearsalType);
            } elseif ($rehearsalType === \App\Core\RehearsalTypeManager::TYPE_REHEARSAL) {
                $typeDisplay = \App\Core\RehearsalTypeManager::TYPE_REHEARSAL;
            }

            $groupsDisplay = '';
            if (isset($rehearsal['groups']) && is_array($rehearsal['groups'])) {
                $smartDisplay = new \App\Core\SmartGroupDisplay();
                $groupsDisplay = htmlspecialchars($smartDisplay->generateDescription($rehearsal['groups'], $rehearsal, false));
            }
            if (!empty($rehearsal['roles'])) {
                foreach ($rehearsal['roles'] as $role) {
                    $groupsDisplay .= ' ' . \App\Core\Utilities::renderRoleTag($role);
                }
            }

            $colorStyle = !empty($rehearsal['color']) ? 'border-left: 4px solid ' . $rehearsal['color'] . ';' : '';

            echo '<tr style="opacity:0.7">';
            echo '<td style="' . $colorStyle . '">' . htmlspecialchars($dayAbbr) . '</td>';
            echo '<td>' . ($rehearsal['date_formatted'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($time_display) . '</td>';
            echo '<td>' . ($rehearsal['location'] ?? '') . '</td>';
            echo '<td>' . $typeDisplay . '</td>';
            echo '<td>' . $groupsDisplay . '</td>';
            echo '</tr>';
        }
    }
}
