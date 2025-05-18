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
     * @return void
     */
    public function index()
    {
        // Check if user is logged in
        if (!$this->isLoggedIn()) {
            $this->redirect('/login');
            return;
        }
        
        // Get personalized view parameter
        $personalized = isset($_GET['personalized']) && $_GET['personalized'] === '1';
        
        // Get show old parameter
        $showOld = isset($_GET['showOld']) && $_GET['showOld'] === '1';
        
        // Get rehearsals
        if ($personalized) {
            // Get only rehearsals relevant to the user
            $userType = $_SESSION['type'] ?? '';
            $userGroups = $_SESSION['groups'] ?? [];
            
            $rehearsals = $this->rehearsalModel->getRelevantForUser(
                $_SESSION['orchestra_id'],
                $userType,
                $userGroups,
                $showOld
            );
        } else {
            // Get all rehearsals
            $rehearsals = $this->rehearsalModel->getUpcoming($_SESSION['orchestra_id'], $showOld);
        }
        
        // Get day abbreviations for each rehearsal
        $days = [];
        foreach ($rehearsals as $rehearsal) {
            // Get day abbreviation in German (Mon, Tue, etc.)
            $date = new \DateTime($rehearsal['date']);
            $days[] = Utilities::getGermanDayAbbreviation($date);
        }
        
        // Render view
        $this->render('probenplan/index', [
            'currentPage' => 'probenplan',
            'rehearsals' => $rehearsals,
            'days' => $days,
            'personalized' => $personalized,
            'showOld' => $showOld
        ]);
    }
} 