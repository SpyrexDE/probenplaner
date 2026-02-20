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
        // Validate orchestra context and set session variables
        $this->validateOrchestraContext($params);

        // Get personalized view parameter
        $personalized = isset($_GET['personalized']) && $_GET['personalized'] === '1';

        // Get show old parameter
        $showOld = isset($_GET['showOld']) && $_GET['showOld'] === '1';

        // Get rehearsals
        if ($personalized) {
            // Get only rehearsals relevant to the user
            $userType = $_SESSION['current_type'] ?? '';

            // Get small group status from user_orchestras table
            $isSmallGroup = false;
            $userId = $_SESSION['user_id'] ?? null;
            $orchestraId = $_SESSION['current_orchestra_id'] ?? null;
            if ($userId && $orchestraId) {
                $userOrchestraModel = new \App\Models\UserOrchestra();
                $isSmallGroup = $userOrchestraModel->isUserInSmallGroup((int)$userId, (int)$orchestraId);
            }

            $rehearsals = $this->rehearsalModel->getRelevantForUser(
                $_SESSION['current_orchestra_id'],
                $userType,
                $showOld,
                $isSmallGroup
            );
        } else {
            // Get all rehearsals
            $rehearsals = $this->rehearsalModel->getUpcoming($_SESSION['current_orchestra_id'], $showOld);
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
            'showOld' => $showOld,
            'canManageRehearsals' => $canManageRehearsals,
            'hasPastRehearsals' => $hasPastRehearsals
        ]);
    }
}
