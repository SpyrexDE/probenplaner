<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;

/**
 * User Controller
 * Handles user profile management
 */
class UserController extends Controller
{
    /**
     * @var User
     */
    private $userModel;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->userModel = new User();
    }
    
    /**
     * Display user profile
     * 
     * @return void
     */
    public function profile()
    {
        // Check if user is logged in
        if (!$this->isLoggedIn()) {
            $this->redirect('/login');
            return;
        }
        
        // Get user data
        $username = $_SESSION['username'];
        $user = $this->userModel->findByUsername($username);
        
        if (!$user) {
            $this->addAlert('Fehler!', 'Benutzer nicht gefunden.', 'error');
            $this->redirect('/');
            return;
        }
        
        // Handle form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->processProfileEdit($user);
            return;
        }
        
        // Render profile view
        $this->render('user/profile', [
            'currentPage' => 'profile',
            'user' => $user,
            'typeStructure' => $this->getTypeStructure()
        ]);
    }
    
    /**
     * Display conductor profile
     * 
     * @return void
     */
    public function conductorProfile()
    {
        // Check if user is logged in and is a conductor
        if (!$this->isLoggedIn()) {
            $this->redirect('/login');
            return;
        }
        
        // Force conductor role check
        if ($_SESSION['role'] !== 'conductor') {
            $this->addAlert('Fehler!', 'Sie haben keine Berechtigung für diese Seite.', 'error');
            $this->redirect('/profile');
            return;
        }
        
        // Get user data
        $username = $_SESSION['username'];
        $user = $this->userModel->findByUsername($username);
        
        if (!$user) {
            $this->addAlert('Fehler!', 'Benutzer nicht gefunden.', 'error');
            $this->redirect('/');
            return;
        }
        
        // Handle form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->processConductorProfileEdit($user);
            return;
        }
        
        // Render conductor profile view
        $this->render('user/conductor_profile', [
            'currentPage' => 'conductor_profile',
            'user' => $user
        ]);
    }
    
    /**
     * Process conductor profile edit form
     * 
     * @param array $user Current user data
     * @return void
     */
    private function processConductorProfileEdit($user)
    {
        // Validate input
        $oldUsername = $user['username'];
        $newUsername = isset($_POST['username']) ? trim($_POST['username']) : '';
        $currentPassword = isset($_POST['current_password']) ? trim($_POST['current_password']) : '';
        $newPassword = isset($_POST['new_password']) ? trim($_POST['new_password']) : '';
        $confirmPassword = isset($_POST['confirm_password']) ? trim($_POST['confirm_password']) : '';
        
        $updateData = [];
        $usernameChanged = false;
        
        // Process username changes if provided
        if (!empty($newUsername) && $newUsername != $oldUsername) {
            // Check if username is too short/long
            if (strlen($newUsername) < 3 || strlen($newUsername) > 20) {
                $this->addAlert('Fehler!', 'Der Nutzername muss zwischen 3 und 20 Zeichen haben.', 'error');
                $this->redirect('/conductor/profile');
                return;
            }
            
            // Check if username is already taken
            $existingUser = $this->userModel->findByUsername($newUsername);
            if ($existingUser && $existingUser['id'] != $user['id']) {
                $this->addAlert('Fehler!', 'Dieser Nutzername ist bereits vergeben.', 'error');
                $this->redirect('/conductor/profile');
                return;
            }
            
            $updateData['username'] = $newUsername;
            $usernameChanged = true;
        }
        
        // Process password changes if provided
        if (!empty($newPassword)) {
            if (empty($currentPassword)) {
                $this->addAlert('Fehler!', 'Bitte geben Sie Ihr aktuelles Passwort ein.', 'error');
                $this->redirect('/conductor/profile');
                return;
            }
            
            // Verify current password
            if (!password_verify($currentPassword, $user['password'])) {
                $this->addAlert('Fehler!', 'Das aktuelle Passwort ist falsch.', 'error');
                $this->redirect('/conductor/profile');
                return;
            }
            
            // Check new password length
            if (strlen($newPassword) < 4 || strlen($newPassword) > 20) {
                $this->addAlert('Fehler!', 'Das neue Passwort muss mindestens 4 und darf maximal 20 Zeichen haben.', 'error');
                $this->redirect('/conductor/profile');
                return;
            }
            
            // Check passwords match
            if ($newPassword !== $confirmPassword) {
                $this->addAlert('Fehler!', 'Die neuen Passwörter stimmen nicht überein.', 'error');
                $this->redirect('/conductor/profile');
                return;
            }
            
            $updateData['password'] = $newPassword;
        }
        
        // If no changes were made
        if (empty($updateData)) {
            $this->addAlert('Info', 'Keine Änderungen vorgenommen.', 'info');
            $this->redirect('/conductor/profile');
            return;
        }
        
        // Update user profile
        $result = $this->userModel->updateProfile($user['id'], $updateData);
        
        if ($result) {
            if ($usernameChanged) {
                // If username changed, need to log out and back in
                $this->addAlert('Erfolg!', 'Profil aktualisiert. Bitte melden Sie sich erneut an.', 'success');
                $this->logout();
            } else {
                $this->addAlert('Erfolg!', 'Profil erfolgreich aktualisiert.', 'success');
                $this->redirect('/conductor/profile');
            }
        } else {
            $this->addAlert('Fehler!', 'Fehler beim Aktualisieren des Profils.', 'error');
            $this->redirect('/conductor/profile');
        }
    }
    
    /**
     * Process profile edit form
     * 
     * @param array $user Current user data
     * @return void
     */
    private function processProfileEdit($user)
    {
        // Validate input
        $oldUsername = $user['username'];
        $newUsername = isset($_POST['username']) ? trim($_POST['username']) : '';
        $currentPassword = isset($_POST['current_password']) ? trim($_POST['current_password']) : '';
        $newPassword = isset($_POST['new_password']) ? trim($_POST['new_password']) : '';
        $confirmPassword = isset($_POST['confirm_password']) ? trim($_POST['confirm_password']) : '';
        $groupType = isset($_POST['group_type']) ? trim($_POST['group_type']) : '';
        $smallGroup = isset($_POST['small_group']) ? true : false;
        $groupLeader = isset($_POST['group_leader']) ? true : false;
        $groupLeaderPw = isset($_POST['group_leader_pw']) ? trim($_POST['group_leader_pw']) : '';
        
        $updateData = [];
        $usernameChanged = false;
        
        // Process username changes if provided
        if (!empty($newUsername) && $newUsername != $oldUsername) {
            // Check if username is too short/long
            if (strlen($newUsername) < 3 || strlen($newUsername) > 20) {
                $this->addAlert('Fehler!', 'Der Nutzername muss zwischen 3 und 20 Zeichen haben.', 'error');
                $this->redirect('/profile');
                return;
            }
            
            // Check if username is already taken
            $existingUser = $this->userModel->findByUsername($newUsername);
            if ($existingUser) {
                $this->addAlert('Fehler!', 'Dieser Nutzername ist bereits vergeben.', 'error');
                $this->redirect('/profile');
                return;
            }
            
            $updateData['username'] = $newUsername;
            $usernameChanged = true;
        }
        
        // Process password changes if provided
        if (!empty($newPassword)) {
            if (empty($currentPassword)) {
                $this->addAlert('Fehler!', 'Bitte geben Sie Ihr aktuelles Passwort ein.', 'error');
                $this->redirect('/profile');
                return;
            }
            
            // Verify current password
            if (!password_verify($currentPassword, $user['password'])) {
                $this->addAlert('Fehler!', 'Das aktuelle Passwort ist falsch.', 'error');
                $this->redirect('/profile');
                return;
            }
            
            // Check new password length
            if (strlen($newPassword) < 4 || strlen($newPassword) > 20) {
                $this->addAlert('Fehler!', 'Das neue Passwort muss mindestens 4 und darf maximal 20 Zeichen haben.', 'error');
                $this->redirect('/profile');
                return;
            }
            
            // Check passwords match
            if ($newPassword !== $confirmPassword) {
                $this->addAlert('Fehler!', 'Die neuen Passwörter stimmen nicht überein.', 'error');
                $this->redirect('/profile');
                return;
            }
            
            $updateData['password'] = $newPassword;
        }
        
        // Process group type changes if provided
        if (!empty($groupType)) {
            $type = $groupType;
            
            // Add small group marker if selected
            if ($smallGroup) {
                if (strpos($type, '*') === false) {
                    $type .= '*';
                }
            } else {
                $type = str_replace('*', '', $type);
            }
            
            $updateData['type'] = $type;
        } else if ($smallGroup !== (strpos($user['type'], '*') !== false)) {
            // Only small group status changed
            $type = $user['type'];
            
            if ($smallGroup) {
                if (strpos($type, '*') === false) {
                    $type .= '*';
                }
            } else {
                $type = str_replace('*', '', $type);
            }
            
            $updateData['type'] = $type;
        }
        
        // Process group leader status
        $leaderSymbol = '♚';
        $hasLeaderSymbol = strpos($user['username'], $leaderSymbol) !== false;
        
        if ($groupLeader && !$hasLeaderSymbol) {
            // Check leader password
            $leaderPassword = $this->getLeaderPassword();
            // Use case-insensitive comparison
            if (strtolower($groupLeaderPw) !== strtolower($leaderPassword)) {
                $this->addAlert('Fehler!', 'Das Stimmführer-Passwort ist falsch.', 'error');
                $this->redirect('/profile');
                return;
            }
            
            // Add leader symbol to username
            $username = $updateData['username'] ?? $user['username'];
            $updateData['username'] = $username . $leaderSymbol;
            $usernameChanged = true;
        } else if (!$groupLeader && $hasLeaderSymbol) {
            // Remove leader symbol from username
            $username = $updateData['username'] ?? $user['username'];
            $updateData['username'] = str_replace($leaderSymbol, '', $username);
            $usernameChanged = true;
        }
        
        // If no changes were made
        if (empty($updateData)) {
            $this->addAlert('Info', 'Keine Änderungen vorgenommen.', 'info');
            $this->redirect('/profile');
            return;
        }
        
        // Update user profile
        $result = $this->userModel->updateProfile($user['id'], $updateData);
        
        if ($result) {
            if ($usernameChanged) {
                // If username changed, need to log out and back in
                $this->addAlert('Erfolg!', 'Profil aktualisiert. Bitte melden Sie sich erneut an.', 'success');
                $this->logout();
            } else {
                $this->addAlert('Erfolg!', 'Profil erfolgreich aktualisiert.', 'success');
                $this->redirect('/profile');
            }
        } else {
            $this->addAlert('Fehler!', 'Fehler beim Aktualisieren des Profils.', 'error');
            $this->redirect('/profile');
        }
    }
    
    /**
     * Check if leader password is valid
     * 
     * @return void
     */
    public function checkLeaderPassword()
    {
        // Check if user is logged in
        if (!$this->isLoggedIn()) {
            echo json_encode(['valid' => false]);
            return;
        }
        
        // Get submitted password
        $password = isset($_POST['password']) ? trim($_POST['password']) : '';
        
        // Get leader password from configuration
        $leaderPassword = $this->getLeaderPassword();
        
        // Check if the password matches (case-insensitive)
        $isValid = (strtolower($password) === strtolower($leaderPassword));
        
        // Return result
        echo json_encode(['valid' => $isValid]);
    }
    
    /**
     * Delete user account
     * 
     * @return void
     */
    public function delete()
    {
        // Check if user is logged in
        if (!$this->isLoggedIn()) {
            $this->redirect('/login');
            return;
        }
        
        // Get user data
        $username = $_SESSION['username'];
        $user = $this->userModel->findByUsername($username);
        
        if (!$user) {
            $this->addAlert('Fehler!', 'Benutzer nicht gefunden.', 'error');
            $this->redirect('/');
            return;
        }
        
        // Delete user account
        $result = $this->userModel->delete($user['id']);
        
        if ($result) {
            // Log out the user
            $this->logout();
            $this->addAlert('Erfolg!', 'Dein Account wurde erfolgreich gelöscht.', 'success');
        } else {
            $this->addAlert('Fehler!', 'Fehler beim Löschen des Accounts.', 'error');
            $this->redirect('/profile');
        }
    }
    
    /**
     * Get leader password from the current orchestra
     * 
     * @return string
     */
    private function getLeaderPassword()
    {
        // Get the current orchestra from session
        $orchestraId = $_SESSION['orchestra_id'] ?? null;
        
        if ($orchestraId) {
            $orchestraModel = new \App\Models\Orchestra();
            $orchestra = $orchestraModel->findById($orchestraId);
            
            if ($orchestra && isset($orchestra['leader_pw'])) {
                return $orchestra['leader_pw'];
            }
        }
        
        // Fallback default password
        return 'stimmfuehrer';
    }
    
    /**
     * Log out the user
     * 
     * @return void
     */
    private function logout()
    {
        // Clear session data
        $_SESSION = [];
        
        // Destroy the session
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        
        // Redirect to login page
        $this->redirect('/login');
    }
    
    /**
     * Get instrument/section type structure
     * 
     * @return array
     */
    private function getTypeStructure()
    {
        return [
            "Tutti" => [
                "Streicher" => [
                    "Violine_1",
                    "Violine_2",
                    "Bratsche",
                    "Cello",
                    "Kontrabass"
                ],
                "Bläser" => [
                    "Blechbläser" => [
                        "Trompete",
                        "Posaune",
                        "Tuba",
                        "Horn"
                    ],
                    "Holzbläser" => [
                        "Flöte",
                        "Oboe",
                        "Klarinette",
                        "Fagott"
                    ]
                ],
                "Schlagwerk",
                "Andere"
            ]
        ];
    }
    
    /**
     * Get user account details - API endpoint to fetch user information
     * for the AJAX requests in the promises admin view
     * 
     * @return void
     */
    public function getUserDetails()
    {
        // Check if user is authorized (either conductor or group leader)
        if (!$this->isLoggedIn() || 
            ($_SESSION['type'] != 'Dirigent' && strpos($_SESSION['username'], '♚') === false)) {
            http_response_code(403);
            echo json_encode(['error' => 'No permission']);
            return;
        }
        
        // Check if username parameter exists
        if (!isset($_GET['username'])) {
            http_response_code(400);
            echo json_encode(['error' => 'No username provided']);
            return;
        }
        
        $username = $_GET['username'];
        $user = $this->userModel->findByUsername($username);
        
        if (!$user) {
            http_response_code(404);
            echo json_encode(['error' => 'User not found']);
            return;
        }
        
        // Check which operation is requested
        if (isset($_GET['getLastLogin'])) {
            // Return the last login time
            $lastLogin = $user['last_login'] ?? 'N/A';
            echo json_encode(['last_login' => $lastLogin]);
            return;
        }
        
        // Default behavior - return full user details excluding password
        unset($user['password']);
        echo json_encode($user);
    }
    
    /**
     * Reset user password - API endpoint for resetting a user's password
     * 
     * @return void
     */
    public function resetPassword()
    {
        // Check if user is authorized (either conductor or group leader)
        if (!$this->isLoggedIn() || 
            ($_SESSION['type'] != 'Dirigent' && strpos($_SESSION['username'], '♚') === false)) {
            http_response_code(403);
            echo json_encode(['error' => 'No permission']);
            return;
        }
        
        // Check if username parameter exists
        if (!isset($_GET['username'])) {
            http_response_code(400);
            echo json_encode(['error' => 'No username provided']);
            return;
        }
        
        $username = $_GET['username'];
        $user = $this->userModel->findByUsername($username);
        
        if (!$user) {
            http_response_code(404);
            echo json_encode(['error' => 'User not found']);
            return;
        }
        
        // Reset password to default
        $newPassword = '12345';
        $result = $this->userModel->updateProfile($user['id'], ['password' => $newPassword]);
        
        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => "Das Passwort des Nutzers $username wurde auf 12345 zurückgesetzt."
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                'error' => "Fehler beim Zurücksetzen des Passworts."
            ]);
        }
    }
    
    /**
     * Delete user account - API endpoint for deleting a user account
     * 
     * @return void
     */
    public function deleteUser()
    {
        // Check if user is authorized (either conductor or group leader)
        if (!$this->isLoggedIn() || 
            ($_SESSION['type'] != 'Dirigent' && strpos($_SESSION['username'], '♚') === false)) {
            http_response_code(403);
            echo json_encode(['error' => 'No permission']);
            return;
        }
        
        // Check if username parameter exists
        if (!isset($_GET['username'])) {
            http_response_code(400);
            echo json_encode(['error' => 'No username provided']);
            return;
        }
        
        $username = $_GET['username'];
        $user = $this->userModel->findByUsername($username);
        
        if (!$user) {
            http_response_code(404);
            echo json_encode(['error' => 'User not found']);
            return;
        }
        
        // Delete the user account
        $result = $this->userModel->delete($user['id']);
        
        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => "Der Nutzer $username wurde erfolgreich gelöscht."
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                'error' => "Fehler beim Löschen des Accounts."
            ]);
        }
    }
} 