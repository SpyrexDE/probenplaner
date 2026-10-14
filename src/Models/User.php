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
     * Error code of createOrLinkKeycloakUser(): an account with this e-mail exists, but
     * Keycloak did not report the e-mail as verified, so it was not linked.
     */
    public const ERROR_KEYCLOAK_EMAIL_NOT_VERIFIED = 'keycloak_email_not_verified';

    /**
     * Error code of createOrLinkKeycloakUser(): the account found by e-mail has an address that
     * only matches through the accent-insensitive DB collation (e.g. "tëst" vs "test"), so it was
     * not linked.
     */
    public const ERROR_KEYCLOAK_EMAIL_MISMATCH = 'keycloak_email_mismatch';

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
     * Authenticate user by email.
     *
     * @return array|null User row on success
     */
    public function authenticate(string $email, string $password): ?array
    {
        $user = $this->findByEmail($email);

        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }

        return null;
    }

    /**
     * Register a new user.
     *
     * @return int|array Inserted user ID on success, error array on failure
     */
    public function register(string $email, string $displayName, string $password)
    {
        $validation = $this->validateUserInput($email, $password, null, null, $displayName);
        if (!$validation['valid']) {
            error_log("Registration failed: " . implode(', ', $validation['errors']));
            return ['error' => true, 'message' => implode(', ', $validation['errors'])];
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $userData = [
            'email' => $email,
            'display_name' => $displayName,
            'password' => $hashedPassword,
        ];

        try {
            $result = $this->insert($userData);

            if ($result === false) {
                $mysqli = $this->db->getConnection();
                $errorCode = $mysqli ? $mysqli->errno : 0;
                $errorMsg = $mysqli ? $mysqli->error : 'Unbekannter Datenbankfehler';

                return [
                    'error' => true,
                    'message' => 'Datenbankfehler #' . $errorCode,
                    'details' => $errorMsg,
                ];
            }

            return $result;
        } catch (\Exception $e) {
            return [
                'error' => true,
                'message' => 'Bei der Registrierung ist ein Fehler aufgetreten.',
                'details' => $e->getMessage(),
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
            $validationErrors = [];

            if (isset($data['email'])) {
                $validation = $this->validateUserInput($data['email'], null, $id);
                if (!$validation['valid']) {
                    $validationErrors = array_merge($validationErrors, $validation['errors']);
                }
            }

            if (isset($data['display_name'])) {
                $dnValidation = \App\Core\Validator::validateDisplayName($data['display_name']);
                if (!$dnValidation['valid']) {
                    $validationErrors = array_merge($validationErrors, $dnValidation['errors']);
                }
            }

            if (isset($data['password'])) {
                $passwordValidation = \App\Core\Validator::validatePassword($data['password']);
                if (!$passwordValidation['valid']) {
                    $validationErrors = array_merge($validationErrors, $passwordValidation['errors']);
                }
                $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            }

            if (!empty($validationErrors)) {
                error_log("User profile update failed - Validation errors: " . implode(', ', $validationErrors));
                return ['error' => true, 'message' => implode(', ', $validationErrors)];
            }

            error_log("Updating user profile. ID: $id, Fields: " . implode(', ', array_keys($data)));

            $result = $this->update($id, $data);

            if ($result === false) {
                $error = $this->db->getLastError();
                $error = is_string($error) ? $error : '';
                error_log("User profile update failed - Database error: " . $error);

                if (strpos($error, '1062') !== false) {
                    return ['error' => true, 'message' => 'Diese E-Mail-Adresse ist bereits vergeben.', 'details' => 'Ein Benutzer mit dieser E-Mail existiert bereits.'];
                } elseif (strpos($error, '1054') !== false) {
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
    public function updatePromise(int $userId, int $rehearsalId, $attending, string $note = '')
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

            // Convert boolean attending to status enum or handle reset
            $status = ($attending === 'reset' || $attending === null) ? null : ($attending ? 'yes' : 'no');

            if ($status === null) {
                // Delete promise (reset)
                if ($existingPromise) {
                    $result = $promiseModel->delete($existingPromise['id']);
                } else {
                    // Nothing to delete, consider it a success
                    $result = true;
                }
            } elseif ($existingPromise) {
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

        $sql = "SELECT up.*, DATE(r.start) AS date, TIME(r.start) AS start_time, TIME(r.end) AS end_time, r.location, r.color
                FROM user_promises up
                JOIN rehearsals r ON up.rehearsal_id = r.id
                WHERE up.user_id = {$userId}
                ORDER BY r.start";

        $result = $this->db->query($sql);
        if ($result === false) {
            return [];
        }

        $promises = [];
        while ($row = $result->fetch_assoc()) {
            $promises[] = $row;
        }

        return $promises;
    }

    /**
     * @param int|null $roleId Role to assign (null uses orchestra default)
     * @return int|array Relationship ID on success, error array on failure
     */
    public function joinOrchestra(int $userId, int $orchestraId, string $type, ?int $roleId = null)
    {
        $userOrchestraModel = new UserOrchestra();
        return $userOrchestraModel->joinOrchestra($userId, $orchestraId, $type, $roleId);
    }

    /**
     * Check if user has a specific permission in an orchestra.
     */
    public function hasPermissionInOrchestra(int $userId, int $orchestraId, string $permission): bool
    {
        $userOrchestraModel = new UserOrchestra();
        return $userOrchestraModel->hasPermission($userId, $orchestraId, $permission);
    }

    /**
     * Whether the user still needs to complete the display-name onboarding step.
     */
    public function needsOnboarding(?array $user = null, ?int $userId = null): bool
    {
        if (!$user && $userId) {
            $user = $this->findById($userId);
        }
        return $user && empty($user['display_name']);
    }

    /**
     * Create an org-admin account for an organization.
     *
     * @return array{user: array, password: string} Created user data and plaintext password
     */
    public function createOrgAccount(int $orgId, string $slug): array
    {
        $email = $slug . '-admin@probenplaner.local';
        $displayName = $slug . '-admin';
        $password = $this->generateSecurePassword();
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $userId = $this->insert([
            'email' => $email,
            'display_name' => $displayName,
            'password' => $hashedPassword,
            'is_org_admin' => 1,
            'organization_id' => $orgId,
        ]);

        return [
            'user' => $this->findById($userId),
            'password' => $password,
        ];
    }

    /**
     * Generate a cryptographically secure random password.
     */
    private function generateSecurePassword(int $length = 10): string
    {
        $chars = 'abcdefghijkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789!#$%';
        $password = '';
        $max = strlen($chars) - 1;
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, $max)];
        }
        return $password;
    }

    /**
     * Validate user input for registration or profile updates.
     *
     * @param string|null $email Email to validate (null = skip)
     * @param string|null $password Password to validate (null = skip)
     * @param int|null $excludeUserId User ID to exclude from uniqueness check
     * @param string|null $passwordConfirm Password confirmation
     * @param string|null $displayName Display name to validate (null = skip)
     * @return array{valid: bool, errors: string[]}
     */
    public function validateUserInput(?string $email = null, ?string $password = null, ?int $excludeUserId = null, ?string $passwordConfirm = null, ?string $displayName = null): array
    {
        $errors = [];

        if ($email !== null) {
            $emailValidation = \App\Core\Validator::validateEmail($email);
            if (!$emailValidation['valid']) {
                $errors = array_merge($errors, $emailValidation['errors']);
            } else {
                $existingUser = $this->findByEmail($email);
                if ($existingUser && (!$excludeUserId || $existingUser['id'] != $excludeUserId)) {
                    $errors[] = "Diese E-Mail-Adresse ist bereits vergeben";
                }
            }
        }

        if ($displayName !== null) {
            $dnValidation = \App\Core\Validator::validateDisplayName($displayName);
            if (!$dnValidation['valid']) {
                $errors = array_merge($errors, $dnValidation['errors']);
            }
        }

        if ($password !== null) {
            $passwordValidation = \App\Core\Validator::validatePassword($password, $passwordConfirm);
            if (!$passwordValidation['valid']) {
                $errors = array_merge($errors, $passwordValidation['errors']);
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
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
     * Whether linking an existing account by e-mail requires email_verified === true
     * (KEYCLOAK_LINK_REQUIRE_VERIFIED, default on).
     */
    private static function keycloakLinkRequiresVerifiedEmail(): bool
    {
        return !defined('KEYCLOAK_LINK_REQUIRE_VERIFIED') || KEYCLOAK_LINK_REQUIRE_VERIFIED !== false;
    }

    /**
     * Fields that hand an existing local account to a Keycloak identity.
     * /register never verifies the address, so whoever created this account may not own it:
     * drop the local password and replace the CalDAV token (it can write promises).
     * The read-only iCal token stays, so calendar subscriptions keep working.
     */
    public static function keycloakLinkData(array $user, string $keycloakId): array
    {
        $data = [
            'keycloak_id' => $keycloakId,
            'auth_provider' => 'keycloak',
            'password' => null,
        ];
        if (!empty($user['caldav_token'])) {
            $data['caldav_token'] = bin2hex(random_bytes(32));
        }
        return $data;
    }

    /**
     * Create or link Keycloak user
     *
     * Match order: keycloak_id (sub) -> existing account by e-mail (only if the stored address is
     * byte-identical apart from ASCII case and Keycloak reports email_verified === true, see
     * KEYCLOAK_LINK_REQUIRE_VERIFIED) -> new account.
     * Linking by e-mail removes the local password and replaces the CalDAV token; the iCal token stays.
     *
     * @param array $keycloakUserInfo User info from Keycloak
     * @param bool $isJmdToken Whether this is from a JMD token login
     * @return array User data or error array (['error' => true, 'message' => ..., 'code' => ...?]);
     *               an existing account that is not linked yields 'code' =>
     *               self::ERROR_KEYCLOAK_EMAIL_NOT_VERIFIED or self::ERROR_KEYCLOAK_EMAIL_MISMATCH
     */
    public function createOrLinkKeycloakUser(array $keycloakUserInfo, bool $isJmdToken = false): array
    {
        $keycloakId = $keycloakUserInfo['sub'] ?? null;
        $email = $keycloakUserInfo['email'] ?? null;
        $displayName = trim($keycloakUserInfo['name'] ?? '');
        if (empty($displayName)) {
            $displayName = trim(($keycloakUserInfo['given_name'] ?? '') . ' ' . ($keycloakUserInfo['family_name'] ?? ''));
        }
        if (empty($displayName) || filter_var($displayName, FILTER_VALIDATE_EMAIL)) {
            $pref = trim($keycloakUserInfo['preferred_username'] ?? '');
            if (!empty($pref) && !filter_var($pref, FILTER_VALIDATE_EMAIL)) {
                $displayName = $pref;
            }
        }
        if (empty($displayName)) {
            $displayName = $email;
        }

        if (!$keycloakId) {
            return ['error' => true, 'message' => 'Keycloak ID fehlt'];
        }

        $existingUser = $this->findByKeycloakId($keycloakId);
        if ($existingUser) {
            // Silently update display_name if it was an email and we now have a real name
            if (filter_var($existingUser['display_name'] ?? '', FILTER_VALIDATE_EMAIL) && !filter_var($displayName, FILTER_VALIDATE_EMAIL)) {
                $this->update($existingUser['id'], ['display_name' => $displayName]);
                $existingUser['display_name'] = $displayName;
            }
            return $existingUser;
        }

        if ($email) {
            $existingUser = $this->findByEmail($email);
            if ($existingUser) {
                // Linking by e-mail hands this account to whoever owns the Keycloak identity.
                // In every refusal below: no link and no insert (users.email is UNIQUE) - the login just fails.

                // findByEmail() matches via utf8mb4_unicode_ci, which ignores accents and width
                // ('jan@tëst.de' = 'jan@test.de'). Keycloak only verified its own spelling, so the
                // stored address must be the same string apart from ASCII case.
                if (strcasecmp((string)($existingUser['email'] ?? ''), (string)$email) !== 0) {
                    error_log("createOrLinkKeycloakUser: not linking existing user #" . (int)$existingUser['id'] . " by e-mail - address only matches via collation");
                    return [
                        'error' => true,
                        'code' => self::ERROR_KEYCLOAK_EMAIL_MISMATCH,
                        'message' => 'Für eine ähnlich geschriebene E-Mail-Adresse gibt es schon ein Probenplaner-Konto. Dein JMD-Account kann deshalb nicht automatisch verknüpft werden.',
                    ];
                }

                // Only trust the address if Keycloak has verified it; otherwise anyone who can
                // self-register in the realm with a victim's e-mail would get the victim's account.
                if (self::keycloakLinkRequiresVerifiedEmail() && ($keycloakUserInfo['email_verified'] ?? null) !== true) {
                    error_log("createOrLinkKeycloakUser: not linking existing user #" . (int)$existingUser['id'] . " by e-mail - email_verified is not true");
                    return [
                        'error' => true,
                        'code' => self::ERROR_KEYCLOAK_EMAIL_NOT_VERIFIED,
                        'message' => 'Für diese E-Mail gibt es schon ein Probenplaner-Konto. Bitte bestätige deine E-Mail-Adresse im JMD-Account oder melde dich mit deinem Probenplaner-Passwort an.',
                    ];
                }

                $updateData = self::keycloakLinkData($existingUser, $keycloakId);
                if (filter_var($existingUser['display_name'] ?? '', FILTER_VALIDATE_EMAIL) && !filter_var($displayName, FILTER_VALIDATE_EMAIL)) {
                    $updateData['display_name'] = $displayName;
                }
                if (!$this->update((int)$existingUser['id'], $updateData)) {
                    error_log("createOrLinkKeycloakUser: linking existing user #" . (int)$existingUser['id'] . " failed");
                    return ['error' => true, 'message' => 'Benutzerkonto konnte nicht verknüpft werden'];
                }
                return array_merge($existingUser, $updateData);
            }
        }

        $userData = [
            'email' => $email,
            'display_name' => $displayName,
            'keycloak_id' => $keycloakId,
            'auth_provider' => 'keycloak',
            'password' => null,
        ];

        if ($isJmdToken) {
            $userData['theme'] = 'jeunesse';
        }

        $userId = $this->insert($userData);
        return $userId ? $this->findById($userId) : ['error' => true, 'message' => 'Benutzer konnte nicht erstellt werden'];
    }

    /**
     * Generate or regenerate iCal and CalDAV tokens for a user.
     *
     * @return array{ical_token: string, caldav_token: string}
     */
    public function generateCalendarTokens(int $userId): array
    {
        $icalToken   = bin2hex(random_bytes(32));
        $caldavToken = bin2hex(random_bytes(32));

        $this->update($userId, [
            'ical_token'   => $icalToken,
            'caldav_token' => $caldavToken,
        ]);

        return ['ical_token' => $icalToken, 'caldav_token' => $caldavToken];
    }

    /**
     * @return array|null User row matching the iCal feed token
     */
    public function findByIcalToken(string $token): ?array
    {
        $sql  = "SELECT * FROM {$this->table} WHERE ical_token = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('s', $token);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    /**
     * @return array|null User row matching the CalDAV auth token
     */
    public function findByCaldavToken(string $token): ?array
    {
        $sql  = "SELECT * FROM {$this->table} WHERE caldav_token = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('s', $token);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    /**
     * Invalidate all calendar tokens for a user (forces re-generation).
     */
    public function revokeCalendarTokens(int $userId): void
    {
        $this->update($userId, ['ical_token' => null, 'caldav_token' => null]);
    }

    /**
     * Remember the last successful login (NULL = never opened the Probenplaner).
     */
    public function recordLogin(int $userId): void
    {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET last_login_at = NOW() WHERE id = ?");
        if (!$stmt) {
            return;
        }
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $stmt->close();
    }
}