<?php
namespace App\Models;

use App\Core\Model;
use App\Core\ErrorHandler;
use App\Models\Orchestra;
use App\Models\UserPromise;
use App\Models\UserOrchestra;

/**
 * User Model
 * Handles user-related database operations
 */
class User extends Model
{
    /**
     * @var string
     */
    protected $table = 'users';
    
    /**
     * Find user by username (orchestra-independent)
     * 
     * @param string $username
     * @return array|null
     */
    public function findByUsername(string $username): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE username = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('s', $username);
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $user = null;
        if ($result && $result instanceof \mysqli_result) {
            $user = $result->fetch_assoc();
        }
        
        $stmt->close();
        return $user;
    }
    
    /**
     * Get user orchestras (delegated to UserOrchestra model)
     * 
     * @param int $userId
     * @return array
     */
    public function getUserOrchestras(int $userId): array
    {
        $userOrchestraModel = new UserOrchestra();
        return $userOrchestraModel->getUserOrchestras($userId);
    }
    
    /**
     * Authenticate user (orchestra-independent)
     * 
     * @param string $username
     * @param string $password
     * @return array|null
     */
    public function authenticate(string $username, string $password): ?array
    {
        $user = $this->findByUsername($username);
        
        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }
        
        return null;
    }
    
    /**
     * Register a new user (orchestra-independent)
     * 
     * @param string $username
     * @param string $password
     * @return int|array Inserted user ID on success, array with error info on failure
     */
    public function register(string $username, string $password)
    {
        // Validate input
        $validation = $this->validateUserInput($username, $password);
        if (!$validation['valid']) {
            error_log("Registration failed: " . implode(', ', $validation['errors']));
            return ['error' => true, 'message' => implode(', ', $validation['errors'])];
        }
        
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert new user
        $userData = [
            'username' => $username,
            'password' => $hashedPassword
        ];
        
        error_log("Registering user: " . json_encode($userData));
        
        // Insert and return the result
        try {
            // Get table schema first
            $tableSchema = [];
            $describeResult = $this->db->query("DESCRIBE users");
            if ($describeResult) {
                while ($row = $describeResult->fetch_assoc()) {
                    $tableSchema[$row['Field']] = [
                        'type' => $row['Type'],
                        'null' => $row['Null'],
                        'key' => $row['Key'],
                        'default' => $row['Default']
                    ];
                }
            }

            // Check required columns
            $missingColumns = [];
            foreach (['username', 'password'] as $requiredCol) {
                if (!isset($tableSchema[$requiredCol])) {
                    $missingColumns[] = $requiredCol;
                }
            }

            // Check for old schema columns
            $oldColumns = [];
            foreach (['orchestra_id', 'type', 'role'] as $oldCol) {
                if (isset($tableSchema[$oldCol])) {
                    $oldColumns[] = $oldCol;
                }
            }

            if (!empty($missingColumns)) {
                return [
                    'error' => true,
                    'message' => 'Datenbank-Schema-Fehler', 
                    'details' => 'Fehlende Spalten: ' . implode(', ', $missingColumns)
                ];
            }

            if (!empty($oldColumns)) {
                return [
                    'error' => true,
                    'message' => 'Altes Datenbankschema erkannt', 
                    'details' => 'Die Migration wurde nicht vollständig angewendet. Alte Spalten gefunden: ' . implode(', ', $oldColumns)
                ];
            }

            $result = $this->insert($userData);
            
            if ($result === false) {
                $mysqli = $this->db->getConnection();
                $errorCode = $mysqli->errno;
                $errorMsg = $mysqli->error;
                
                return [
                    'error' => true,
                    'message' => 'Datenbankfehler #' . $errorCode, 
                    'details' => $errorMsg
                ];
            }
            
            return $result;
        } catch (\Exception $e) {
            return [
                'error' => true,
                'message' => 'Bei der Registrierung ist ein Fehler aufgetreten.', 
                'details' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Update user profile
     * 
     * @param int $id
     * @param array $data
     * @return bool|array True on success, error details array on failure
     */
    public function updateProfile(int $id, array $data)
    {
        try {
            // Validate data before updating
            $validationErrors = [];
            
                // Validate username if it's being updated
            if (isset($data['username'])) {
                $user = $this->findById($id);
                if (!$user) {
                    return ['error' => true, 'message' => 'Benutzer nicht gefunden.'];
                }
                
                $validation = $this->validateUserInput($data['username'], null, $id);
                if (!$validation['valid']) {
                    $validationErrors = array_merge($validationErrors, $validation['errors']);
                }
            }
            
            // Validate password if it's being updated
            if (isset($data['password'])) {
                // Use consistent validation through Validator class
                $passwordValidation = \App\Core\Validator::validatePassword($data['password']);
                if (!$passwordValidation['valid']) {
                    $validationErrors = array_merge($validationErrors, $passwordValidation['errors']);
                }
                
                // Hash the password before updating
                $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            }
            
            // Return errors if validation failed
            if (!empty($validationErrors)) {
                error_log("User profile update failed - Validation errors: " . implode(', ', $validationErrors));
                return ['error' => true, 'message' => implode(', ', $validationErrors)];
            }
            
            // Debug log
            error_log("Updating user profile. ID: $id, Data: " . json_encode($data));
            
            $result = $this->update($id, $data);
            
            if ($result === false) {
                $error = $this->db->getLastError();
                error_log("User profile update failed - Database error: " . $error);
                
                // Check for specific error types
                if (strpos($error, '1062') !== false) { // Duplicate entry
                    return ['error' => true, 'message' => 'Der Benutzername ist bereits vergeben.', 'details' => 'Ein Benutzer mit diesem Namen existiert bereits.'];
                } elseif (strpos($error, '1054') !== false) { // Unknown column
                    return ['error' => true, 'message' => 'Datenbank-Schema-Fehler', 'details' => 'Eine Spalte in der Datenbank fehlt. Bitte führen Sie alle Migrationen aus.'];
                } else {
                    return ['error' => true, 'message' => 'Bei der Aktualisierung ist ein Fehler aufgetreten.', 'details' => 'Technischer Fehler: ' . $error];
                }
            }
            
            return $result;
        } catch (\Exception $e) {
            return ErrorHandler::handleDatabaseError($e, 'User profile update');
        }
    }
    
    /**
     * Update user promise
     * 
     * @param int $userId
     * @param int $rehearsalId
     * @param bool $attending
     * @param string $note
     * @return array|bool Array with error info or true on success
     */
    public function updatePromise(int $userId, int $rehearsalId, bool $attending, string $note = '')
    {
        try {
            $promiseModel = new UserPromise();
            
            // Check if promise exists
            $existingPromise = $promiseModel->findByUserAndRehearsal($userId, $rehearsalId);
            
            // Check if rehearsal exists
            $rehearsalModel = new \App\Models\Rehearsal();
            $rehearsal = $rehearsalModel->findById($rehearsalId);
            if (!$rehearsal) {
                error_log("Failed to update promise: Rehearsal not found (ID: $rehearsalId)");
                return ['error' => true, 'message' => 'Die Probe wurde nicht gefunden.', 'details' => 'Die angegebene Probe existiert nicht mehr.'];
            }
            
            // Convert boolean attending to status enum
            $status = $attending ? 'yes' : 'no';
            
            if ($existingPromise) {
                // Update existing promise
                $result = $promiseModel->update($existingPromise['id'], [
                    'status' => $status,
                    'note' => $note,
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
            } else {
                // Insert new promise
                $result = $promiseModel->insert([
                    'user_id' => $userId,
                    'rehearsal_id' => $rehearsalId,
                    'status' => $status,
                    'note' => $note,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
            }
            
            if ($result === false) {
                $error = $this->db->getLastError();
                error_log("Failed to update promise - Database error: " . $error);
                
                // Check for specific error types
                if (strpos($error, '1062') !== false) { // Duplicate entry
                    return ['error' => true, 'message' => 'Doppelter Eintrag.', 'details' => 'Es existiert bereits eine Zusage für diese Probe.'];
                } elseif (strpos($error, '1452') !== false) { // Foreign key constraint
                    return ['error' => true, 'message' => 'Ungültige Referenz.', 'details' => 'Die Probe oder der Benutzer existiert nicht mehr.'];
                } else {
                    return ['error' => true, 'message' => 'Bei der Aktualisierung ist ein Fehler aufgetreten.', 'details' => 'Technischer Fehler: ' . $error];
                }
            }
            
            return true;
        } catch (\Exception $e) {
            return ErrorHandler::handleDatabaseError($e, 'User promise update');
        }
    }
    
    /**
     * Get user promises
     * 
     * @param int $userId
     * @return array
     */
    public function getPromises(int $userId): array
    {
        $userId = (int)$userId;
        
        $sql = "SELECT up.*, r.date, r.start_time, r.end_time, r.location, r.color, r.is_small_group
                FROM user_promises up
                JOIN rehearsals r ON up.rehearsal_id = r.id
                WHERE up.user_id = {$userId}
                ORDER BY r.date, r.start_time";
                
        $result = $this->db->query($sql);
        
        $promises = [];
        while ($row = $result->fetch_assoc()) {
            // Convert status to attending boolean for backward compatibility
            $row['attending'] = ($row['status'] === 'yes');
            $promises[] = $row;
        }
        
        return $promises;
    }
    
    /**
     * Join orchestra (delegated to UserOrchestra model)
     * 
     * @param int $userId
     * @param int $orchestraId
     * @param string $type Instrument/section
     * @param string $role User role
     * @return int|array Relationship ID on success, error array on failure
     */
    public function joinOrchestra(int $userId, int $orchestraId, string $type, string $role = 'member')
    {
        $userOrchestraModel = new UserOrchestra();
        return $userOrchestraModel->joinOrchestra($userId, $orchestraId, $type, $role);
    }
    
    /**
     * Check if user has specific role in an orchestra
     * 
     * @param int $userId
     * @param int $orchestraId
     * @param string $role
     * @return bool
     */
    public function hasRoleInOrchestra(int $userId, int $orchestraId, string $role): bool
    {
        $userOrchestraModel = new UserOrchestra();
        return $userOrchestraModel->hasRole($userId, $orchestraId, $role);
    }
    
    /**
     * Validate user input for registration or profile updates
     * 
     * @param string $username Username to validate
     * @param string $password Password to validate (if provided)
     * @param int|null $excludeUserId User ID to exclude from duplicate check (for updates)
     * @param string|null $passwordConfirm Confirmation password to check (if provided)
     * @return array Array with 'valid' => bool and 'errors' => array
     */
    public function validateUserInput(?string $username = null, ?string $password = null, ?int $excludeUserId = null, ?string $passwordConfirm = null): array
    {
        $errors = [];
        
        // Validate username only when provided (null means skip username validation)
        if ($username !== null) {
            if ($username === '') {
                $errors[] = "Benutzername fehlt";
            } elseif (strlen($username) < 3 || strlen($username) > 20) {
                $errors[] = "Der Benutzername muss zwischen 3 und 20 Zeichen haben";
            } else {
                // Check for duplicates if not updating own username
                $existingUser = $this->findByUsername($username);
                if ($existingUser && (!$excludeUserId || $existingUser['id'] != $excludeUserId)) {
                    $errors[] = "Dieser Benutzername ist bereits vergeben";
                }
            }
        }
        
        // Validate password if provided
        if ($password !== null) {
            // Use consistent validation through Validator class
            $passwordValidation = \App\Core\Validator::validatePassword($password, $passwordConfirm);
            if (!$passwordValidation['valid']) {
                $errors = array_merge($errors, $passwordValidation['errors']);
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Update user theme preference
     * 
     * @param int $userId User ID
     * @param string $theme Theme key
     * @return bool|array Success status or error array
     */
    public function updateTheme(int $userId, string $theme)
    {
        // Validate theme using ThemeManager
        $validation = \App\Core\ThemeManager::validateThemePreference($theme);
        if (!$validation['valid']) {
            error_log("Theme update failed - Validation errors: " . implode(', ', $validation['errors']));
            return ['error' => true, 'message' => implode(', ', $validation['errors'])];
        }
        
        // Update the theme preference
        $result = $this->update($userId, ['theme' => $theme]);
        
        if ($result === false) {
            $error = $this->db->getLastError();
            error_log("Theme update failed - Database error: " . $error);
            return ['error' => true, 'message' => 'Fehler beim Aktualisieren des Themes.', 'details' => $error];
        }
        
        return $result;
    }
    
    /**
     * Get user theme preference
     * 
     * @param int $userId User ID
     * @return string Theme key or default theme
     */
    public function getUserTheme(int $userId): string
    {
        $user = $this->findById($userId);
        
        if ($user && isset($user['theme'])) {
            // Validate that the theme still exists
            if (\App\Core\ThemeManager::themeExists($user['theme'])) {
                return $user['theme'];
            }
        }
        
        // Return default theme if user not found or invalid theme
        return \App\Core\ThemeManager::getDefaultTheme();
    }
    
    /**
     * Delete user account
     * 
     * @param int $userId
     * @return bool
     */
    public function delete(int $userId): bool
    {
        return parent::delete($userId);
    }

    /**
     * Find user by Keycloak ID
     * 
     * @param string $keycloakId
     * @return array|null
     */
    public function findByKeycloakId(string $keycloakId): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE keycloak_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('s', $keycloakId);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result ? $result->fetch_assoc() : null;
        $stmt->close();
        return $user;
    }

    /**
     * Find user by email
     * 
     * @param string $email
     * @return array|null
     */
    public function findByEmail(string $email): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE email = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result ? $result->fetch_assoc() : null;
        $stmt->close();
        return $user;
    }

    /**
     * Create or link Keycloak user
     * 
     * @param array $keycloakUserInfo User info from Keycloak
     * @return array User data or error array
     */
    public function createOrLinkKeycloakUser(array $keycloakUserInfo): array
    {
        $keycloakId = $keycloakUserInfo['sub'] ?? null;
        $email = $keycloakUserInfo['email'] ?? null;
        $username = $keycloakUserInfo['preferred_username'] ?? $email;
        
        if (!$keycloakId) {
            return ['error' => true, 'message' => 'Keycloak ID fehlt'];
        }
        
        // Check if user already exists by Keycloak ID
        $existingUser = $this->findByKeycloakId($keycloakId);
        if ($existingUser) {
            return $existingUser;
        }
        
        // Check if user exists by email/username
        $existingUser = $this->findByEmail($email) ?: $this->findByUsername($username);
        if ($existingUser) {
            // Link existing account to Keycloak
            $this->update($existingUser['id'], [
                'keycloak_id' => $keycloakId,
                'email' => $email,
                'auth_provider' => 'keycloak'
            ]);
            return $existingUser;
        }
        
        // Create new user
        $userData = [
            'username' => $username,
            'keycloak_id' => $keycloakId,
            'email' => $email,
            'auth_provider' => 'keycloak',
            'password' => null // No password for Keycloak users
        ];
        
        $userId = $this->insert($userData);
        return $userId ? $this->findById($userId) : ['error' => true, 'message' => 'Benutzer konnte nicht erstellt werden'];
    }
} 