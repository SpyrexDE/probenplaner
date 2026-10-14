<?php

namespace App\Controllers;

use App\Core\Controller;

/**
 * Home Controller
 * Handles the main page
 */
class HomeController extends Controller
{
    /**
     * Main page
     * 
     * @return void
     */
    public function index()
    {
        // Check for JMD access token first (JOinJMD tile, same login as /login)
        $jmdToken = \App\Core\JmdTokenLogin::requestToken();
        if ($jmdToken !== null) {
            (new AuthController())->processJmdTokenLogin($jmdToken);
            return;
        }

        // If logged in, redirect to orchestra selection or main app
        if ($this->isLoggedIn()) {
            // Admin → admin dashboard
            if (!empty($_SESSION['is_super_admin'])) {
                $this->redirect('/admin/dashboard');
                return;
            }

            // Orga-admin → orga panel
            if (!empty($_SESSION['is_org_admin'])) {
                $this->redirect('/orga');
                return;
            }

            // Onboarding only for brand-new users without any orchestra
            $userModel = new \App\Models\User();
            $user = $userModel->findById((int)$_SESSION['user_id']);
            if ($user && empty($user['display_name'])) {
                $uoModel = new \App\Models\UserOrchestra();
                $orchestras = $uoModel->getUserOrchestras((int)$_SESSION['user_id']);
                if (empty($orchestras)) {
                    $this->redirect('/onboarding');
                    return;
                }
            }

            if (isset($_SESSION['current_orchestra_slug'])) {
                if (!empty($_SESSION['current_permissions']['can_manage_ensemble'])) {
                    $this->redirect($this->orchestraUrl('/promises/admin'));
                } else {
                    $this->redirect($this->orchestraUrl('/promises'));
                }
            } else {
                $this->redirect('/orchestras/select');
            }
            return;
        }

        // Not logged in, show login page
        $this->redirect('/login');
    }
}
