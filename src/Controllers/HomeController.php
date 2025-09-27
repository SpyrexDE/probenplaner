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
        // If logged in, redirect to orchestra selection or main app
        if ($this->isLoggedIn()) {
            // Check if user has selected an orchestra
            if (isset($_SESSION['current_orchestra_id'])) {
                // Redirect based on role in current orchestra
                if ($_SESSION['current_role'] === 'conductor') {
                    $this->redirect('/' . $_SESSION['current_orchestra_id'] . '/promises/admin');
                } else {
                    $this->redirect('/' . $_SESSION['current_orchestra_id'] . '/promises');
                }
            } else {
                // No orchestra selected, go to orchestra selection
                $this->redirect('/orchestras/select');
            }
            return;
        }
        
        // Not logged in, show login page
        $this->redirect('/login');
    }
} 