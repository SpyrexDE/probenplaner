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
            'conductor_username' => 'Dirigent*in Benutzername',
            'conductor_password' => 'Dirigent*in Passwort',
            'date' => 'Datum',
            'start' => 'Anfang',
            'end' => 'Ende',
            'location' => 'Ort'
        ];

        return $displayNames[$fieldName] ?? $fieldName;
    }

    /**
     * Validate a single field value against its FieldRegistry rules.
     *
     * @param string $entity Entity key (orchestra, user, rehearsal)
     * @param string $fieldName Field name from registry
     * @param mixed $value Value to validate
     * @return array{valid: bool, errors: string[]}
     */
    public static function validateField(string $entity, string $fieldName, $value): array
    {
        $fieldDef = FieldRegistry::getField($entity, $fieldName);
        if (!$fieldDef) {
            return ['valid' => false, 'errors' => ["Unbekanntes Feld: {$fieldName}"]];
        }

        $rules = $fieldDef['validation'] ?? [];
        $errors = [];
        $label = $fieldDef['label'] ?? $fieldName;

        foreach ($rules as $rule) {
            if ($rule === 'required' && (is_null($value) || $value === '')) {
                $errors[] = "{$label} ist erforderlich";
                break; // skip further rules if empty
            }

            if (str_starts_with($rule, 'min:')) {
                $min = (int) substr($rule, 4);
                if (is_string($value) && mb_strlen($value) < $min) {
                    $errors[] = "{$label} muss mindestens {$min} Zeichen lang sein";
                }
            }

            if (str_starts_with($rule, 'max:')) {
                $max = (int) substr($rule, 4);
                if (is_string($value) && mb_strlen($value) > $max) {
                    $errors[] = "{$label} darf maximal {$max} Zeichen lang sein";
                }
            }

            if (str_starts_with($rule, 'pattern:')) {
                $pattern = substr($rule, 8);
                if (is_string($value) && $value !== '' && !preg_match($pattern, $value)) {
                    $errors[] = "{$label} hat ein ungültiges Format";
                }
            }
        }

        return ['valid' => empty($errors), 'errors' => $errors];
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

    /**
     * Validate datetime format (Y-m-d\TH:i)
     * 
     * @param string $datetime Datetime to validate
     * @return array Array with 'valid' boolean and 'errors' array
     */
    public static function validateDateTime(string $datetime): array
    {
        $errors = [];

        if (empty($datetime)) {
            $errors[] = "Datum und Zeit fehlen";
        } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $datetime) && !preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $datetime)) {
            // Support both HTML5 datetime-local (T) and MySQL format (space)
            $errors[] = "Ungültiges Datumsformat";
        } else {
            try {
                $dt = new \DateTime($datetime);
                // Check if it's a valid date (e.g. not 2023-02-30)
                // strict check is logically tricky with different separators, so rely on DateTime throwing or checking last errors
                $lastErrors = \DateTime::getLastErrors();
                if ($lastErrors['warning_count'] > 0 || $lastErrors['error_count'] > 0) {
                    $errors[] = "Ungültiges Datum oder Zeit";
                }
            } catch (\Exception $e) {
                $errors[] = "Ungültiges Datum oder Zeit";
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
}
