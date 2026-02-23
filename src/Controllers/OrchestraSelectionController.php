<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Validator;
use App\Models\User;
use App\Models\Orchestra;
use App\Models\UserOrchestra;

/**
 * Orchestra Selection Controller
 * Handles orchestra selection and joining flow
 */
class OrchestraSelectionController extends Controller
{
    /**
     * @var User
     */
    private $userModel;

    /**
     * @var Orchestra
     */
    private $orchestraModel;

    /**
     * @var UserOrchestra
     */
    private $userOrchestraModel;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->userModel = new User();
        $this->orchestraModel = new Orchestra();
        $this->userOrchestraModel = new UserOrchestra();
    }

    public function select()
    {
        if (!$this->isLoggedIn()) {
            $this->redirect('/login');
            return;
        }

        // No redirect - show orchestra selection

        $userOrchestras = $this->userOrchestraModel->getUserOrchestras($_SESSION['user_id']);

        $_SESSION['user_orchestras_count'] = count($userOrchestras);

        $this->render('orchestras/select', [
            'currentPage' => 'orchestra_select',
            'orchestras' => $userOrchestras,
            'csrf_token' => $this->getCSRFToken()
        ]);
    }

    /**
     * Process orchestra selection
     * 
     * @return void
     */
    public function setCurrentOrchestra()
    {
        if (!$this->isLoggedIn()) {
            $this->redirect('/login');
            return;
        }

        try {
            $this->protectCSRF();
        } catch (\Exception $e) {
            $this->addAlert('Sicherheitsfehler!', $e->getMessage(), 'error');
            $this->redirect('/orchestras/select');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/orchestras/select');
            return;
        }

        $orchestraId = (int)($_POST['orchestra_id'] ?? 0);

        if (!$orchestraId) {
            $this->addAlert('Fehler!', 'Bitte wählen Sie ein Orchester aus.', 'error');
            $this->redirect('/orchestras/select');
            return;
        }

        $relation = $this->userOrchestraModel->getUserOrchestraRelation($_SESSION['user_id'], $orchestraId, true);

        if (!$relation) {
            $this->addAlert('Fehler!', 'Sie sind nicht Mitglied dieses Orchesters.', 'error');
            $this->redirect('/orchestras/select');
            return;
        }

        $orchestra = $this->orchestraModel->findById($orchestraId);

        if (!$orchestra) {
            $this->addAlert('Fehler!', 'Das Orchester wurde nicht gefunden.', 'error');
            $this->redirect('/orchestras/select');
            return;
        }

        $_SESSION['current_orchestra_id'] = $orchestraId;
        $_SESSION['current_orchestra_slug'] = $orchestra['slug'];
        $_SESSION['current_orchestra_name'] = $orchestra['name'];
        $_SESSION['current_type'] = $relation['type'];
        $_SESSION['current_permissions'] = $this->userOrchestraModel->getPermissions($_SESSION['user_id'], $orchestraId);

        // Resolve org slug
        $orgModel = new \App\Models\Organization();
        $org = $orgModel->findById((int)($orchestra['organization_id'] ?? 0));
        $orgSlug = $org['slug'] ?? '';
        $_SESSION['current_org_slug'] = $orgSlug;

        $this->setFlash('success', 'Orchester ausgewählt: ' . $orchestra['name']);

        $slug = $orchestra['slug'];
        if (!empty($_SESSION['current_permissions']['can_manage_ensemble'])) {
            $this->redirect('/' . $orgSlug . '/' . $slug . '/promises/admin');
        } else {
            $this->redirect('/' . $orgSlug . '/' . $slug . '/promises');
        }
    }

    /**
     * Show join orchestra form
     * 
     * @return void
     */
    public function showJoinForm()
    {
        if (!$this->isLoggedIn()) {
            $this->redirect('/login');
            return;
        }

        // Display join form with type structure
        $this->render('orchestras/join', [
            'currentPage' => 'join_orchestra',
            'typeStructure' => $this->getTypeStructure(),
            'csrf_token' => $this->getCSRFToken()
        ]);
    }

    /**
     * Process joining orchestra - Step 1: Validate token
     * 
     * @return void
     */
    public function join()
    {
        if (!$this->isLoggedIn()) {
            $this->redirect('/login');
            return;
        }

        try {
            $this->protectCSRF();
        } catch (\Exception $e) {
            $this->addAlert('Sicherheitsfehler!', $e->getMessage(), 'error');
            $this->redirect('/orchestras/join');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/orchestras/join');
            return;
        }

        $token = Validator::sanitizeUtf8($_POST['token'] ?? '');

        $requiredValidation = Validator::validateRequired([
            'token' => $token
        ], ['token']);

        if (!$requiredValidation['valid']) {
            $this->addAlert(
                'Fehler!',
                implode(' ', $requiredValidation['errors']),
                'error'
            );
            $this->redirect('/orchestras/join');
            return;
        }

        $tokenValidation = Validator::validateToken($token);
        if (!$tokenValidation['valid']) {
            $this->addAlert(
                'Fehler!',
                implode(', ', $tokenValidation['errors']),
                'error'
            );
            $this->redirect('/orchestras/join');
            return;
        }

        $orchestra = $this->orchestraModel->findBySlug($token);

        if (!$orchestra) {
            $this->addAlert(
                'Fehler!',
                'Der eingegebene Orchester-Token ist ungültig.',
                'error',
                'Der Token wurde nicht gefunden. Bitte überprüfen Sie den Token oder kontaktieren Sie Ihren Dirigenten für den korrekten Token.'
            );
            $this->redirect('/orchestras/join');
            return;
        }

        $existingRelation = $this->userOrchestraModel->getUserOrchestraRelation($_SESSION['user_id'], $orchestra['id'], true);
        if ($existingRelation) {
            $this->addAlert(
                'Info!',
                'Sie sind bereits Mitglied dieses Orchesters.',
                'info'
            );
            $this->redirect('/orchestras/select');
            return;
        }

        $_SESSION['join_orchestra'] = $orchestra;

        $this->redirect('/orchestras/select-section');
    }

    /**
     * Show section selection for joining orchestra - Step 2
     * 
     * @return void
     */
    public function showSectionSelection()
    {
        if (!$this->isLoggedIn()) {
            $this->redirect('/login');
            return;
        }

        // Must have orchestra in session from step 1
        if (!isset($_SESSION['join_orchestra'])) {
            $this->addAlert('Fehler!', 'Session abgelaufen. Bitte beginnen Sie erneut.', 'error');
            $this->redirect('/orchestras/join');
            return;
        }

        $orchestra = $_SESSION['join_orchestra'];

        $this->render('orchestras/select-section', [
            'currentPage' => 'select_section',
            'orchestra' => $orchestra,
            'typeStructure' => $this->getTypeStructure(),
            'csrf_token' => $this->getCSRFToken()
        ]);
    }

    /**
     * Complete joining orchestra - Step 2: Join with selected section
     * 
     * @return void
     */
    public function completeJoin()
    {
        if (!$this->isLoggedIn()) {
            $this->redirect('/login');
            return;
        }

        // Must have orchestra in session from step 1
        if (!isset($_SESSION['join_orchestra'])) {
            $this->addAlert('Fehler!', 'Session abgelaufen. Bitte beginnen Sie erneut.', 'error');
            $this->redirect('/orchestras/join');
            return;
        }

        try {
            $this->protectCSRF();
        } catch (\Exception $e) {
            $this->addAlert('Sicherheitsfehler!', $e->getMessage(), 'error');
            $this->redirect('/orchestras/select-section');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/orchestras/select-section');
            return;
        }

        $orchestra = $_SESSION['join_orchestra'];
        $orchestraId = (int)$orchestra['id'];

        $type = Validator::sanitizeUtf8($_POST['type'] ?? '');

        $requiredValidation = Validator::validateRequired([
            'type' => $type
        ], ['type']);

        if (!$requiredValidation['valid']) {
            $this->addAlert(
                'Fehler!',
                implode(' ', $requiredValidation['errors']),
                'error'
            );
            $this->redirect('/orchestras/select-section');
            return;
        }

        $result = $this->userOrchestraModel->joinOrchestra($_SESSION['user_id'], $orchestraId, $type);

        if (is_array($result) && isset($result['error'])) {
            $this->addAlert(
                'Fehler!',
                $result['message'],
                'error'
            );
            $this->redirect('/orchestras/select-section');
            return;
        }

        if ($result) {
            unset($_SESSION['join_orchestra']);

            $this->setFlash('success', 'Sie sind dem Orchester "' . $orchestra['name'] . '" erfolgreich beigetreten.');

            $_SESSION['current_orchestra_id'] = $orchestraId;
            $_SESSION['current_orchestra_slug'] = $orchestra['slug'] ?? '';
            $_SESSION['current_orchestra_name'] = $orchestra['name'];
            $_SESSION['current_type'] = $type;
            $_SESSION['current_permissions'] = $this->userOrchestraModel->getPermissions($_SESSION['user_id'], $orchestraId);

            // Resolve org slug
            $orgModel = new \App\Models\Organization();
            $org = $orgModel->findById((int)($orchestra['organization_id'] ?? 0));
            $orgSlug = $org['slug'] ?? '';
            $_SESSION['current_org_slug'] = $orgSlug;

            $slug = $orchestra['slug'] ?? $orchestraId;
            $this->redirect('/' . $orgSlug . '/' . $slug . '/promises');
        } else {
            $this->addAlert(
                'Fehler!',
                'Beim Beitreten zum Orchester ist ein Fehler aufgetreten.',
                'error'
            );
            $this->redirect('/orchestras/select-section');
        }
    }

    /**
     * Switch current orchestra (for users in multiple orchestras)
     * 
     * @param array $params Route parameters
     * @return void
     */
    public function switchOrchestra($params = [])
    {
        if (!$this->isLoggedIn()) {
            $this->redirect('/login');
            return;
        }

        $param = $params['orchestra_id'] ?? '';

        // Resolve by slug first, fall back to numeric ID (from select form)
        $orchestra = null;
        if (!is_numeric($param)) {
            $orchestra = $this->orchestraModel->findBySlug($param);
        }
        if (!$orchestra && is_numeric($param)) {
            $orchestra = $this->orchestraModel->findById((int)$param);
        }

        if (!$orchestra) {
            $this->addAlert('Fehler!', 'Orchester nicht gefunden.', 'error');
            $this->redirect('/orchestras/select');
            return;
        }

        $orchestraId = (int)$orchestra['id'];

        $relation = $this->userOrchestraModel->getUserOrchestraRelation($_SESSION['user_id'], $orchestraId, true);

        if (!$relation) {
            $this->addAlert('Fehler!', 'Sie sind nicht Mitglied dieses Orchesters.', 'error');
            $this->redirect('/orchestras/select');
            return;
        }



        $_SESSION['current_orchestra_id'] = $orchestraId;
        $_SESSION['current_orchestra_slug'] = $orchestra['slug'];
        $_SESSION['current_orchestra_name'] = $orchestra['name'];
        $_SESSION['current_type'] = $relation['type'];
        $_SESSION['current_permissions'] = $this->userOrchestraModel->getPermissions($_SESSION['user_id'], $orchestraId);

        // Resolve org slug
        $orgModel = new \App\Models\Organization();
        $org = $orgModel->findById((int)($orchestra['organization_id'] ?? 0));
        $orgSlug = $org['slug'] ?? '';
        $_SESSION['current_org_slug'] = $orgSlug;

        $this->setFlash('success', 'Gewechselt zu: ' . $orchestra['name']);

        $slug = $orchestra['slug'];
        if (!empty($_SESSION['current_permissions']['can_manage_ensemble'])) {
            $this->redirect('/' . $orgSlug . '/' . $slug . '/promises/admin');
        } else {
            $this->redirect('/' . $orgSlug . '/' . $slug . '/promises');
        }
    }

    /**
     * Get instrument/section type structure dynamically
     * 
     * @return array
     */
    private function getTypeStructure()
    {
        $groupManager = new \App\Core\GroupManager();
        $config = $groupManager->getConfig();

        if (isset($config['tutti']['children'])) {
            $structure = [];

            foreach ($config['tutti']['children'] as $sectionKey => $section) {
                if ($section['type'] === 'section') {
                    $sectionInstruments = [];

                    if (isset($section['children'])) {
                        foreach ($section['children'] as $childKey => $child) {
                            if ($child['type'] === 'instrument') {
                                $sectionInstruments[] = $child['id'];
                            } elseif ($child['type'] === 'section' && isset($child['children'])) {
                                // Flatten nested sections for the form
                                foreach ($child['children'] as $instrumentKey => $instrument) {
                                    if ($instrument['type'] === 'instrument') {
                                        $sectionInstruments[] = $instrument['id'];
                                    }
                                }
                            }
                        }
                    } else {
                        // Simple sections like Schlagwerk - add the section ID as an instrument
                        $sectionInstruments[] = $section['id'];
                    }

                    if (!empty($sectionInstruments)) {
                        $structure[$section['id']] = $sectionInstruments;
                    }
                }
            }

            return $structure;
        }

        // Configuration is malformed
        throw new \Exception("Orchestra groups configuration is malformed or missing 'tutti' section. Please check src/config/orchestra_groups.php.");
    }
}
