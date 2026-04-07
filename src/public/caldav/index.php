<?php

/**
 * CalDAV Server Entry Point
 *
 * Served at /caldav/ — bypasses the main router.
 * Apache routes this directory directly to this file (AllowOverride None).
 */

require_once dirname(__DIR__, 2) . '/bootstrap.php';

use App\CalDAV\AuthBackend;
use App\CalDAV\PrincipalBackend;
use App\CalDAV\CalendarBackend;
use Sabre\DAV;
use Sabre\CalDAV;
use Sabre\DAVACL;

$authBackend      = new AuthBackend();
$principalBackend = new PrincipalBackend();
$calendarBackend  = new CalendarBackend();

$tree = [
    new DAVACL\PrincipalCollection($principalBackend),
    new CalDAV\CalendarRoot($principalBackend, $calendarBackend),
];

$server = new DAV\Server($tree);
$server->setBaseUri('/caldav/');

$server->addPlugin(new DAV\Auth\Plugin($authBackend));
$server->addPlugin(new DAVACL\Plugin());
$server->addPlugin(new CalDAV\Plugin());
// Schedule Plugin exposes the Outbox so Apple Calendar can POST RSVP replies
$server->addPlugin(new CalDAV\Schedule\Plugin());
$server->addPlugin(new DAV\Browser\Plugin());

/**
 * Intercept POST to Outbox — Apple Calendar uses this for RSVP replies.
 * Priority 1 runs before SabreDAV's own scheduling handler.
 */
$server->on('beforeMethod:POST', function ($request, $response) {
    $path = $request->getPath();
    if (!str_ends_with($path, 'outbox') && !str_ends_with($path, 'outbox/')) {
        return true;
    }

    try {
        $body = $request->getBodyAsString();
        if (empty($body)) return true;
        $request->setBody($body);

        $vcal = \Sabre\VObject\Reader::read($body);
        if (!$vcal->VEVENT) return true;

        $uid = (string)$vcal->VEVENT->UID;
        if (!preg_match('/probenplaner-rehearsal-(\d+)@/', $uid, $m)) return true;

        $rehearsalId = (int)$m[1];
        if (!preg_match('/principals\/(\d+)/', $path, $m)) return true;

        $userId   = (int)$m[1];

        // Conductors have no RSVPs
        $userOrchestraModel = new \App\Models\UserOrchestra();
        $rehearsal = (new \App\Models\Rehearsal())->findById($rehearsalId);
        if ($rehearsal) {
            $roles = $userOrchestraModel->getUserRoles($userId, (int)$rehearsal['orchestra_id']);
            $isConductor = !empty(array_filter($roles, fn($r) => !empty($r['is_system']) && ($r['name'] ?? '') === 'Leitung'));
            if ($isConductor) return true;
        }
        $partstat = 'NEEDS-ACTION';
        $comment  = '';

        if ($vcal->VEVENT->ATTENDEE) {
            $attendee = $vcal->VEVENT->ATTENDEE;
            $p = $attendee->offsetGet('PARTSTAT');
            if ($p) $partstat = strtoupper((string)$p);
            $c = $attendee->offsetGet('X-COMMENT');
            if ($c) $comment = (string)$c;
        }
        if (!$comment && isset($vcal->VEVENT->COMMENT)) {
            $comment = (string)$vcal->VEVENT->COMMENT;
        }

        if ($partstat !== 'ACCEPTED' && $partstat !== 'DECLINED') return true;

        (new \App\Models\User())->updatePromise($userId, $rehearsalId, $partstat === 'ACCEPTED', $comment);

        $response->setStatus(200);
        return false;
    } catch (\Exception $e) {
        error_log('CalDAV Outbox RSVP error: ' . $e->getMessage());
    }
    return true;
}, 1);

/**
 * Intercept PUT — Apple Calendar sends RSVP updates by writing back the .ics directly.
 * We extract PARTSTAT and persist it before SabreDAV processes the write.
 * Priority 1 runs early; we return false to short-circuit after our own 204.
 */
$server->on('beforeMethod:PUT', function ($request, $response) {
    $path = $request->getPath();
    if (!str_ends_with($path, '.ics')) return true;

    try {
        $body = $request->getBodyAsString();
        if (empty($body)) return true;
        $request->setBody($body);

        $vcal   = \Sabre\VObject\Reader::read($body);
        $vevent = $vcal->VEVENT;
        if (!$vevent) return true;

        $partstat = null;
        $comment  = '';
        if (isset($vevent->ATTENDEE)) {
            foreach ($vevent->ATTENDEE as $attendee) {
                $p = $attendee->offsetGet('PARTSTAT');
                if ($p) $partstat = strtoupper((string)$p);
                $c = $attendee->offsetGet('X-COMMENT');
                if ($c) $comment = (string)$c;
            }
        }
        if (!$comment && isset($vevent->COMMENT)) {
            $comment = (string)$vevent->COMMENT;
        }

        if ($partstat !== 'ACCEPTED' && $partstat !== 'DECLINED') return true;

        // Resolve rehearsal ID from UID or filename
        $rehearsalId = null;
        $uid = (string)$vevent->UID;
        if (preg_match('/probenplaner-rehearsal-(\d+)@/', $uid, $m)) {
            $rehearsalId = (int)$m[1];
        } elseif (preg_match('/rehearsal-(\d+)\.ics$/', $path, $m)) {
            $rehearsalId = (int)$m[1];
        }
        if (!$rehearsalId) return true;

        if (!preg_match('/orchestra_(\d+)_user_(\d+)/', $path, $m)) return true;
        $orchId = (int)$m[1];
        $userId = (int)$m[2];

        // Conductors have no RSVPs
        $userOrchestraModel = new \App\Models\UserOrchestra();
        $roles = $userOrchestraModel->getUserRoles($userId, $orchId);
        $isConductor = !empty(array_filter($roles, fn($r) => !empty($r['is_system']) && ($r['name'] ?? '') === 'Leitung'));
        if ($isConductor) return true;

        (new \App\Models\User())->updatePromise($userId, $rehearsalId, $partstat === 'ACCEPTED', $comment);

        $rehearsal = (new \App\Models\Rehearsal())->findById($rehearsalId);
        $promise   = (new \App\Models\UserPromise())->findByUserAndRehearsal($userId, $rehearsalId);
        $newIcs    = (new CalendarBackend())->buildVEvent($rehearsal, $userId, $promise, false);

        $response->setStatus(204);
        $response->setHeader('ETag', '"' . md5($newIcs) . '"');
        return false;
    } catch (\Exception $e) {
        error_log('CalDAV PUT RSVP error: ' . $e->getMessage());
    }
    return true;
}, 1);

$server->on('exception', function ($e) {
    error_log('CalDAV exception: ' . $e->getMessage());
});

$server->exec();
