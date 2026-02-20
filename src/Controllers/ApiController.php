<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\UserPromise;
use App\Models\Rehearsal;
use App\Core\Utilities;

class ApiController extends Controller
{
    private $userPromiseModel;
    private $rehearsalModel;

    public function __construct()
    {
        parent::__construct();
        $this->userPromiseModel = new UserPromise();
        $this->rehearsalModel = new Rehearsal();
    }

    /**
     * Get user promise statistics for sidebar display
     */
    public function getUserStats()
    {
        // Ensure user is logged in and has orchestra context
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['current_orchestra_id'])) {
            $this->jsonResponse(['success' => false, 'error' => 'Nicht angemeldet oder kein Orchester ausgewählt'], 401);
            return;
        }

        $userId = $_SESSION['user_id'];
        $orchestraId = $_SESSION['current_orchestra_id'];

        // If user is a conductor, get upcoming rehearsal stats instead of personal stats
        if (!empty($_SESSION['current_permissions']['can_manage_rehearsals'])) {
            $this->getConductorStats($orchestraId);
            return;
        }

        try {
            // Get all future rehearsals for this orchestra
            $rehearsals = $this->rehearsalModel->getUpcoming($orchestraId, false);

            $stats = [
                'attending' => 0,
                'not_attending' => 0,
                'no_response' => 0,
                'total' => 0
            ];

            foreach ($rehearsals as $rehearsal) {
                // Check if user is relevant for this rehearsal
                $groups = $this->rehearsalModel->getGroupsAsAssoc($rehearsal['id']);

                // Get small group status from user_orchestras table
                $isSmallGroup = false;
                $userId = $_SESSION['user_id'] ?? null;
                $orchestraId = $_SESSION['current_orchestra_id'] ?? null;
                if ($userId && $orchestraId) {
                    $userOrchestraModel = new \App\Models\UserOrchestra();
                    $isSmallGroup = $userOrchestraModel->isUserInSmallGroup((int)$userId, (int)$orchestraId);
                }
                $user = ['is_small_group' => $isSmallGroup];
                $rehearsalIsSmallGroup = \App\Core\RehearsalTypeManager::isSmallGroupRehearsal($rehearsal);

                if ($this->rehearsalModel->isUserInRehearsalGroup($_SESSION['current_type'] ?? '', $isSmallGroup, $groups, $rehearsalIsSmallGroup)) {
                    $stats['total']++;

                    // Check user's promise for this rehearsal
                    $promise = $this->userPromiseModel->findByUserAndRehearsal($userId, $rehearsal['id']);

                    // Log retrieval result
                    error_log("Rehearsal {$rehearsal['id']}: Promise found: " . ($promise ? 'yes' : 'no'));
                    if ($promise) {
                        error_log("Promise data: " . json_encode($promise));
                        error_log("Promise status value: " . ($promise['status'] ?? 'undefined'));
                    }

                    if ($promise && isset($promise['status'])) {
                        if ($promise['status'] === 'yes') {
                            $stats['attending']++;
                            error_log("Counted as attending (status: yes)");
                        } elseif ($promise['status'] === 'no') {
                            $stats['not_attending']++;
                            error_log("Counted as not attending (status: no)");
                        } else {
                            // 'maybe' or any other status counts as no response
                            $stats['no_response']++;
                            error_log("Counted as no response (status: " . $promise['status'] . ")");
                        }
                    } else {
                        $stats['no_response']++;
                        error_log("Counted as no response (no promise found)");
                    }
                }
            }

            $this->jsonResponse(['success' => true, 'stats' => $stats]);
        } catch (\Exception $e) {
            $this->jsonResponse(['success' => false, 'error' => 'Statistiken konnten nicht geladen werden'], 500);
        }
    }

    /**
     * Get upcoming rehearsal statistics for conductors
     */
    private function getConductorStats(int $orchestraId)
    {
        try {
            // Get the next upcoming rehearsal
            $rehearsals = $this->rehearsalModel->getUpcoming($orchestraId, false);

            if (empty($rehearsals)) {
                // No upcoming rehearsals
                $this->jsonResponse([
                    'success' => true,
                    'stats' => [
                        'attending' => 0,
                        'not_attending' => 0,
                        'no_response' => 0,
                        'total' => 0
                    ],
                    'next_rehearsal' => null
                ]);
                return;
            }

            $nextRehearsal = $rehearsals[0]; // First rehearsal is the next one

            // Get statistics for this rehearsal
            $stats = $this->userPromiseModel->getPromiseStats($nextRehearsal['id'], $orchestraId);


            // Add rehearsal info to the response
            $stats['next_rehearsal'] = [
                'id' => $nextRehearsal['id'],
                'date' => $nextRehearsal['date'],
                'date_formatted' => $nextRehearsal['date_formatted'],
                'start_time' => $nextRehearsal['start_time'],
                'end_time' => $nextRehearsal['end_time'],
                'type' => $nextRehearsal['type'] ?? \App\Core\RehearsalTypeManager::TYPE_REHEARSAL
            ];

            $this->jsonResponse(['success' => true, 'stats' => $stats]);
        } catch (\Exception $e) {
            $this->jsonResponse(['success' => false, 'error' => 'Dirigenten-Statistiken konnten nicht geladen werden'], 500);
        }
    }

    /**
     * Test endpoint for debugging
     */
    public function test()
    {
        $this->jsonResponse(['success' => true, 'message' => 'API funktioniert']);
    }

    /**
     * Helper method to send JSON response
     */
    private function jsonResponse($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}
