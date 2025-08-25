<?php
namespace App\Core;

/**
 * Validation Helper Class
 * Provides common validation methods to reduce code duplication
 */
class Validator
{
    /**
     * Validate required fields
     * 
     * @param array $data Data to validate
     * @param array $requiredFields List of required field names
     * @return array Array with 'valid' boolean and 'errors' array
     */
    public static function validateRequired(array $data, array $requiredFields): array
    {
        $errors = [];
        $missingFields = [];
        
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                $missingFields[] = self::getFieldDisplayName($field);
            }
        }
        
        if (!empty($missingFields)) {
            $errors[] = 'Bitte füllen Sie alle erforderlichen Felder aus: ' . implode(', ', $missingFields);
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'missing_fields' => $missingFields
        ];
    }
    
    /**
     * Validate password requirements
     * 
     * @param string $password Password to validate
     * @param string $confirmPassword Password confirmation (optional)
     * @return array Array with 'valid' boolean and 'errors' array
     */
    public static function validatePassword(string $password, ?string $confirmPassword = null): array
    {
        $errors = [];
        
        if (empty($password)) {
            $errors[] = "Passwort fehlt";
            return ['valid' => false, 'errors' => $errors];
        }
        
        // Password requirements
        $requirements = [];
        if (strlen($password) < PASSWORD_MIN_LENGTH) {
            $requirements[] = 'mindestens ' . PASSWORD_MIN_LENGTH . ' Zeichen';
        }
        if (PASSWORD_REQUIRE_UPPERCASE && !preg_match('/[A-Z]/', $password)) {
            $requirements[] = 'mindestens ein Großbuchstabe';
        }
        if (PASSWORD_REQUIRE_LOWERCASE && !preg_match('/[a-z]/', $password)) {
            $requirements[] = 'mindestens ein Kleinbuchstabe';
        }
        if (PASSWORD_REQUIRE_NUMBERS && !preg_match('/[0-9]/', $password)) {
            $requirements[] = 'mindestens eine Zahl';
        }
        if (PASSWORD_REQUIRE_SPECIAL && !preg_match('/[^A-Za-z0-9]/', $password)) {
            $requirements[] = 'mindestens ein Sonderzeichen';
        }
        
        if (!empty($requirements)) {
            $errors[] = "Das Passwort muss " . implode(', ', $requirements) . " enthalten";
        }
        
        // Check if passwords match (if confirmation is provided)
        if ($confirmPassword !== null && $password !== $confirmPassword) {
            $errors[] = "Die Passwörter stimmen nicht überein";
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Validate username requirements
     * 
     * @param string $username Username to validate
     * @return array Array with 'valid' boolean and 'errors' array
     */
    public static function validateUsername(string $username): array
    {
        $errors = [];
        
        if (empty($username)) {
            $errors[] = "Benutzername fehlt";
        } elseif (strlen($username) < USERNAME_MIN_LENGTH || strlen($username) > USERNAME_MAX_LENGTH) {
            $errors[] = "Der Benutzername muss zwischen " . USERNAME_MIN_LENGTH . " und " . USERNAME_MAX_LENGTH . " Zeichen haben";
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Validate token requirements
     * 
     * @param string $token Token to validate
     * @return array Array with 'valid' boolean and 'errors' array
     */
    public static function validateToken(string $token): array
    {
        $errors = [];
        
        if (empty($token)) {
            $errors[] = "Token fehlt";
        } elseif (strlen($token) < TOKEN_MIN_LENGTH) {
            $errors[] = "Token muss mindestens " . TOKEN_MIN_LENGTH . " Zeichen lang sein";
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Sanitize UTF-8 string
     * 
     * @param string $input Input string
     * @return string Sanitized string
     */
    public static function sanitizeUtf8(string $input): string
    {
        if (!is_string($input)) {
            return '';
        }
        
        // Remove null bytes and control characters
        $input = str_replace(chr(0), '', $input);
        $input = preg_replace('/[\x00-\x1F\x7F]/', '', $input);
        
        // Ensure valid UTF-8
        $input = mb_convert_encoding($input, 'UTF-8', 'UTF-8');
        
        return trim($input);
    }
    
    /**
     * Validate date format (Y-m-d)
     * 
     * @param string $date Date to validate
     * @return array Array with 'valid' boolean and 'errors' array
     */
    public static function validateDate(string $date): array
    {
        $errors = [];
        
        if (empty($date)) {
            $errors[] = "Datum fehlt";
        } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $errors[] = "Ungültiges Datumsformat";
        } else {
            $dateTime = \DateTime::createFromFormat('Y-m-d', $date);
            if (!$dateTime || $dateTime->format('Y-m-d') !== $date) {
                $errors[] = "Ungültiges Datum";
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Validate time format (H:i)
     * 
     * @param string $time Time to validate
     * @param string $fieldName Field name for error messages
     * @return array Array with 'valid' boolean and 'errors' array
     */
    public static function validateTime(string $time, string $fieldName = 'Zeit'): array
    {
        $errors = [];
        
        if (empty($time)) {
            $errors[] = "{$fieldName} fehlt";
        } elseif (!preg_match('/^\d{2}:\d{2}$/', $time)) {
            $errors[] = "Ungültiges {$fieldName}format";
        } else {
            $dateTime = \DateTime::createFromFormat('H:i', $time);
            if (!$dateTime || $dateTime->format('H:i') !== $time) {
                $errors[] = "Ungültige {$fieldName}";
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Get display name for field
     * 
     * @param string $fieldName Field name
     * @return string Display name
     */
    private static function getFieldDisplayName(string $fieldName): string
    {
        $displayNames = [
            'username' => 'Benutzername',
            'password' => 'Passwort',
            'password_confirm' => 'Passwort bestätigen',
            'type' => 'Instrument/Rolle',
            'token' => 'Orchester-Token',
            'name' => 'Name',
            'leader_password' => 'Stimmführer-Passwort',
            'conductor_username' => 'Dirigent Benutzername',
            'conductor_password' => 'Dirigent Passwort',
            'date' => 'Datum',
            'start_time' => 'Startzeit',
            'end_time' => 'Endzeit',
            'location' => 'Ort'
        ];
        
        return $displayNames[$fieldName] ?? $fieldName;
    }
    
    /**
     * Merge validation results
     * 
     * @param array $results Array of validation result arrays
     * @return array Combined validation result
     */
    public static function mergeResults(array $results): array
    {
        $allValid = true;
        $allErrors = [];
        
        foreach ($results as $result) {
            if (!$result['valid']) {
                $allValid = false;
            }
            $allErrors = array_merge($allErrors, $result['errors']);
        }
        
        return [
            'valid' => $allValid,
            'errors' => $allErrors
        ];
    }
}
