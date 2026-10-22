<?php

/**
 * One pass of the push reminders before rehearsals (App\Core\PushReminderJob).
 * docker/startup.sh runs it once a minute when JMD_PUSH_URL is set. CLI only.
 */

if (PHP_SAPI !== 'cli') {
    exit(1);
}

require_once __DIR__ . '/../bootstrap.php';

// stderr instead of bootstrap's /tmp file: the startup.sh loop passes it on to `docker logs`
ini_set('error_log', '');

try {
    \App\Core\PushReminderJob::run();
} catch (\Throwable $e) {
    error_log('PushReminders failed: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    exit(1);
}
