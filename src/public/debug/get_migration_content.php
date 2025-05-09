<?php
/**
 * Migration Content Endpoint
 * Returns raw SQL content of a migration file
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

// Output raw file content
echo file_get_contents($filePath); 