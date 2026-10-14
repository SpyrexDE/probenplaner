<?php

/**
 * Debug tools are for development instances only. No local-IP exception: behind the
 * reverse proxy / Docker port mapping every request can look local.
 * Include right after bootstrap.php.
 */
if (!defined('APP_ENV') || !in_array(strtolower(APP_ENV), ['development', 'local', 'dev', 'test'], true)) {
    http_response_code(404);
    exit;
}
