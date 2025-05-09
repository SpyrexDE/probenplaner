<?php
/**
 * Migration Content Endpoint
 * Serves raw SQL content of migration files
 */

// Only allow AJAX requests
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] !== 'XMLHttpRequest') {
    http_response_code(400);
    echo "Bad Request: AJAX calls only";
    exit;
}

$filePath = isset($_GET['file']) ? $_GET['file'] : null;

if (!$filePath || !file_exists($filePath)) {
    http_response_code(404);
    echo "File not found";
    exit;
}

// Only allow reading .sql files from the migrations directory
$realPath = realpath($filePath);
$migrationsDir = realpath('/var/www/html/database/migrations');

if (!$realPath || !str_starts_with($realPath, $migrationsDir) || !str_ends_with($realPath, '.sql')) {
    http_response_code(403);
    echo "Access denied";
    exit;
}

// Set content type to plain text
header('Content-Type: text/plain; charset=utf-8');

// Disable output buffering
while (ob_get_level()) ob_end_clean();

// Output raw file content
readfile($filePath);
exit; 