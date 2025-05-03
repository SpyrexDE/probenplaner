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
        // If logged in, redirect to appropriate dashboard
        if ($this->isLoggedIn()) {
            if ($this->isAdmin()) {
                $this->redirect('/promises/admin');
            } else {
                $this->redirect('/promises');
            }
            return;
        }
        
        // Not logged in, show login page
        $this->redirect('/login');
    }
} 