<?php

/**
 * Stand-in for the JOinJMD push API (JMD_PUSH_URL) for local tests.
 * POST: checks the bearer token and logs the payload. GET: the last 20 entries.
 */

require_once '../../bootstrap.php';

require_once __DIR__ . '/includes/guard.php';

header('Content-Type: application/json');

$logFile = sys_get_temp_dir() . '/probenplaner-push-mock.log';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
    $token = preg_match('/^Bearer\s+(.+)$/i', $auth, $m) ? trim($m[1]) : '';
    if (JMD_PUSH_TOKEN !== '' && !hash_equals(JMD_PUSH_TOKEN, $token)) {
        http_response_code(401);
        echo json_encode(['error' => 'unauthorized']);
        exit;
    }

    $payload = json_decode((string)file_get_contents('php://input'), true);
    if (!is_array($payload)) {
        http_response_code(400);
        echo json_encode(['error' => 'invalid json']);
        exit;
    }

    file_put_contents($logFile, json_encode(['time' => date('c'), 'payload' => $payload], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n", FILE_APPEND | LOCK_EX);
    echo json_encode(['ok' => true]);
    exit;
}

$lines = is_file($logFile) ? file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];
$entries = array_map(fn($line) => json_decode($line, true), array_slice($lines, -20));
echo json_encode(array_reverse($entries), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
