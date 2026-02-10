<?php
namespace App\Core;

/**
 * Error Handler Class
 * Standardizes error handling throughout the application
 */
class ErrorHandler
{
    const ERROR_SUCCESS = 'success';
    const ERROR_WARNING = 'warning';
    const ERROR_ERROR = 'error';
    const ERROR_INFO = 'info';
    
    /**
     * Create a standardized error response
     * 
     * @param bool $success Whether the operation succeeded
     * @param string|array $message Error/success message(s)
     * @param array $data Additional data (optional)
     * @param string $type Error type (error, warning, success, info)
     * @return array Standardized response array
     */
    public static function createResponse($success, $message, $data = [], $type = null)
    {
        // Normalize messages to array
        if (is_string($message)) {
            $messages = [$message];
        } else {
            $messages = (array)$message;
        }
        
        // Determine type if not specified
        if ($type === null) {
            $type = $success ? self::ERROR_SUCCESS : self::ERROR_ERROR;
        }
        
        return [
            'success' => $success,
            'messages' => $messages,
            'data' => $data,
            'type' => $type
        ];
    }
    
    /**
     * Create success response
     * 
     * @param string $message Success message
     * @param array $data Additional data
     * @return array Success response
     */
    public static function success($message, $data = [])
    {
        return self::createResponse(true, $message, $data, self::ERROR_SUCCESS);
    }
    
    /**
     * Create error response
     * 
     * @param string|array $message Error message(s)
     * @param array $data Additional data
     * @return array Error response
     */
    public static function error($message, $data = [])
    {
        return self::createResponse(false, $message, $data, self::ERROR_ERROR);
    }
    
    /**
     * Create warning response
     * 
     * @param string $message Warning message
     * @param array $data Additional data
     * @return array Warning response
     */
    public static function warning($message, $data = [])
    {
        return self::createResponse(true, $message, $data, self::ERROR_WARNING);
    }
    
    /**
     * Handle database errors with consistent formatting
     * 
     * @param \Exception $e Exception from database operation
     * @param string $operation Description of the operation that failed
     * @return array Formatted error response
     */
    public static function handleDatabaseError(\Exception $e, $operation = 'Database operation')
    {
        error_log("Database Error in {$operation}: " . $e->getMessage());
        
        $code = $e->getCode();
        $technicalDetails = $e->getMessage();
        
        $userMessage = null;
        switch ($code) {
            case 1062:
                $userMessage = "Dieser Eintrag existiert bereits.";
                break;
            case 1452:
                $userMessage = "Ungültige Referenz - der referenzierte Datensatz existiert nicht.";
                break;
            case 1054:
                $userMessage = "Datenbankstruktur veraltet. Bitte führen Sie Migrationen aus.";
                break;
            case 2002:
                $userMessage = "Datenbankverbindung fehlgeschlagen. Bitte versuchen Sie es später erneut.";
                break;
            default:
                $userMessage = "Es ist ein Datenbankfehler aufgetreten. Bitte versuchen Sie es später erneut.";
        }
        
        return [
            'error' => true,
            'message' => $userMessage,
            'details' => $technicalDetails
        ];
    }
    
    /**
     * Handle validation errors with consistent formatting
     * 
     * @param array $validationResult Result from Validator class
     * @return array Formatted error response
     */
    public static function handleValidationError($validationResult)
    {
        if (!isset($validationResult['valid']) || !isset($validationResult['errors'])) {
            return self::error("Ungültiges Validierungsergebnis.");
        }
        
        if ($validationResult['valid']) {
            return self::success("Validierung erfolgreich.");
        }
        
        return self::error($validationResult['errors']);
    }
    
    /**
     * Log error with consistent format
     * 
     * @param string $message Error message
     * @param array $context Additional context
     * @param string $level Error level (error, warning, info)
     */
    public static function log($message, $context = [], $level = 'error')
    {
        $contextStr = empty($context) ? '' : ' | Context: ' . json_encode($context);
        $logMessage = "[{$level}] {$message}{$contextStr}";
        
        error_log($logMessage);
    }
    
    /**
     * Handle exceptions with user-friendly messages
     * 
     * @param \Exception $e The exception
     * @param string $userMessage User-friendly message
     * @param array $context Additional context for logging
     * @return array Error response
     */
    public static function handleException(\Exception $e, $userMessage = null, $context = [])
    {
        // Log the full exception
        self::log($e->getMessage(), array_merge($context, [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]));
        
        // Return user-friendly message
        if ($userMessage === null) {
            $userMessage = "Es ist ein unerwarteter Fehler aufgetreten. Bitte versuchen Sie es später erneut.";
        }
        
        return self::error($userMessage);
    }
}
