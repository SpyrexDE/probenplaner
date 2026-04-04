<?php

namespace App\CalDAV;

use App\Models\Rehearsal;
use App\Models\UserOrchestra;
use App\Models\UserPromise;
use App\Models\User;
use Sabre\CalDAV\Backend\AbstractBackend;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Reader;

/**
 * CalDAV backend reading from the Probenplaner database.
 *
 * Each orchestra the user belongs to becomes a separate calendar.
 * Probes become VEVENTs. When a client writes back a DECLINED/ACCEPTED
 * PARTSTAT, the change is persisted to user_promises.
 */
class CalendarBackend extends AbstractBackend
{
    private Rehearsal $rehearsalModel;
    private UserPromise $promiseModel;
    private User $userModel;
    private UserOrchestra $userOrchestraModel;
    private array $userCache = [];

    /** In-memory map of calendarId → [userId, orchestraId] */
    private array $calendarMeta = [];

    // Helper to generate a fake email so Apple Calendar auto-discovery works seamlessly
    private function getCaldavEmail(int $userId): string
    {
        $host = isset($_SERVER['HTTP_HOST']) ? preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST']) : (getenv('DOMAIN') ?: 'localhost');
        return $userId . '@' . $host;
    }

    public function __construct()
    {
        $this->rehearsalModel     = new Rehearsal();
        $this->promiseModel       = new UserPromise();
        $this->userModel          = new User();
        $this->userOrchestraModel = new UserOrchestra();
    }

    // -------------------------------------------------------------------------
    // Calendars
    // -------------------------------------------------------------------------

    public function getCalendarsForUser($principalUri): array
    {
        $email = $this->emailFromPrincipal($principalUri);
        $user  = null;
        if (preg_match('/^(\d+)(@|$)/i', $email, $matches)) {
            $user = $this->userModel->findById((int)$matches[1]);
        } else {
            $user = $this->userModel->findByEmail($email);
        }
        
        if (!$user) return [];

        $userId      = (int)$user['id'];
        $memberships = $this->userOrchestraModel->getUserOrchestras($userId);
        $calendars   = [];

        foreach ($memberships as $row) {
            $orchId    = (int)$row['orchestra_id'];
            $calId     = "orchestra_{$orchId}_user_{$userId}";

            $this->calendarMeta[$calId] = ['user_id' => $userId, 'orchestra_id' => $orchId, 'user_type' => $row['type'] ?? ''];

            // Calculate a stable ctag based on the latest rehearsal update
            $userRoleIds = $this->getUserRoleIds($userId, $orchId);
            $rehearsals = $this->rehearsalModel->getForUser($row['type'] ?? '', $orchId, true, $userRoleIds);
            
            $maxUpdatedAt = 0;
            foreach ($rehearsals as $rehearsal) {
                $upd = strtotime($rehearsal['updated_at'] ?? $rehearsal['created_at'] ?? 'now');
                if ($upd > $maxUpdatedAt) {
                    $maxUpdatedAt = $upd;
                }
            }
            // Include user promises updates to ctag
            $promises = $this->getPromisesIndexed($userId, array_column($rehearsals, 'id'));
            foreach ($promises as $promise) {
                if (!empty($promise['updated_at'])) {
                    $upd = strtotime($promise['updated_at']);
                    if ($upd > $maxUpdatedAt) {
                        $maxUpdatedAt = $upd;
                    }
                }
            }
            $ctag = $maxUpdatedAt ? (string)$maxUpdatedAt : '1';

            $calendars[] = [
                'id'                                           => $calId,
                'uri'                                          => $calId,
                'principaluri'                                 => $principalUri,
                '{DAV:}displayname'                            => $row['orchestra_name'] ?? 'Ensemble',
                '{urn:ietf:params:xml:ns:caldav}calendar-description' => ($row['orchestra_name'] ?? 'Ensemble') . ' – Probenplaner',
                '{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set'
                    => new \Sabre\CalDAV\Xml\Property\SupportedCalendarComponentSet(['VEVENT']),
                '{http://apple.com/ns/ical/}calendar-color'   => '#478cf4',
                '{http://calendarserver.org/ns/}getctag'       => 'http://sabre.io/ns/sync/' . $ctag,
                '{urn:ietf:params:xml:ns:caldav}calendar-timezone' => null,
            ];
        }

        return $calendars;
    }

    public function createCalendar($principalUri, $calendarUri, array $properties): string
    {
        // Creating calendars from clients is not supported
        return '';
    }

    public function deleteCalendar($calendarId): void {}

    public function updateCalendar($calendarId, \Sabre\DAV\PropPatch $propPatch): void {}

    // -------------------------------------------------------------------------
    // Calendar Objects (VEVENTs)
    // -------------------------------------------------------------------------

    public function getCalendarObjects($calendarId): array
    {
        $meta = $this->resolveMeta($calendarId);
        if (!$meta) return [];

        $rehearsals = $this->rehearsalModel->getForUser(
            $meta['user_type'],
            $meta['orchestra_id'],
            true, // include past
            $this->getUserRoleIds($meta['user_id'], $meta['orchestra_id'])
        );

        $promises = $this->getPromisesIndexed($meta['user_id'], array_column($rehearsals, 'id'));

        $objects = [];
        foreach ($rehearsals as $rehearsal) {
            $uri = 'rehearsal-' . $rehearsal['id'] . '.ics';
            $rehearsalUpdated = strtotime($rehearsal['updated_at'] ?? $rehearsal['created_at'] ?? 'now');
            $promise = $promises[$rehearsal['id']] ?? null;
            $promiseUpdated = 0;
            if ($promise && !empty($promise['updated_at'])) {
                $promiseUpdated = strtotime($promise['updated_at']);
            }
            $latestUpdateTimestamp = max($rehearsalUpdated, $promiseUpdated);

            $icsData     = $this->buildVEvent($rehearsal, $meta['user_id'], $promise);
            $objects[]   = [
                'id'           => $rehearsal['id'],
                'uri'          => $uri,
                'calendarid'   => $calendarId,
                'calendardata' => $icsData,
                'lastmodified' => $latestUpdateTimestamp,
                'etag'         => '"' . md5($icsData) . '"',
                'size'         => strlen($icsData),
                'component'    => 'VEVENT',
            ];
        }

        return $objects;
    }

    public function getCalendarObject($calendarId, $objectUri): array|false
    {
        $meta       = $this->resolveMeta($calendarId);
        $rehearsalId = $this->uriToRehearsalId($objectUri);
        if (!$meta || !$rehearsalId) return false;

        $rehearsal = $this->rehearsalModel->findById($rehearsalId);
        if (!$rehearsal) return false;

        $promise = $this->promiseModel->findByUserAndRehearsal($meta['user_id'], $rehearsalId);

        $rehearsalUpdated = strtotime($rehearsal['updated_at'] ?? $rehearsal['created_at'] ?? 'now');
        $promiseUpdated = 0;
        if ($promise && !empty($promise['updated_at'])) {
            $promiseUpdated = strtotime($promise['updated_at']);
        }
        $latestUpdateTimestamp = max($rehearsalUpdated, $promiseUpdated);

        $icsData = $this->buildVEvent($rehearsal, $meta['user_id'], $promise);

        return [
            'id'           => $rehearsalId,
            'uri'          => $objectUri,
            'calendarid'   => $calendarId,
            'calendardata' => $icsData,
            'lastmodified' => $latestUpdateTimestamp,
            'etag'         => '"' . md5($icsData) . '"',
            'size'         => strlen($icsData),
            'component'    => 'VEVENT',
        ];
    }

    /**
     * Called when a client PUTs an updated event — syncs RSVP back to DB as fallback.
     * The primary interception happens in index.php before SabreDAV processes the request.
     */
    public function updateCalendarObject($calendarId, $objectUri, $calendarData): string
    {
        $meta        = $this->resolveMeta($calendarId);
        $rehearsalId = $this->uriToRehearsalId($objectUri)
            ?? $this->extractRehearsalIdFromData($calendarData);

        if (!$meta) {
            return '"' . md5($calendarData) . '"';
        }

        if ($rehearsalId) {
            $this->processRSVPUpdate($calendarData, $meta, $rehearsalId);
            $rehearsal  = $this->rehearsalModel->findById($rehearsalId);
            $promise    = $this->promiseModel->findByUserAndRehearsal($meta['user_id'], $rehearsalId);
            return '"' . md5($this->buildVEvent($rehearsal, $meta['user_id'], $promise)) . '"';
        }

        return '"' . md5($calendarData) . '"';
    }

    public function createCalendarObject($calendarId, $objectUri, $calendarData): string
    {
        $meta        = $this->resolveMeta($calendarId);
        $rehearsalId = $this->extractRehearsalIdFromData($calendarData);

        if ($meta && $rehearsalId) {
            $this->processRSVPUpdate($calendarData, $meta, $rehearsalId);
            return '"' . md5($calendarData) . '"';
        }

        throw new \Sabre\DAV\Exception\Forbidden('Das Erstellen von Proben direkt im Kalender wird nicht unterstützt. Bitte nutze die Probenplaner Web-App.');
    }

    private function extractRehearsalIdFromData(string $calendarData): ?int
    {
        try {
            $vcal = Reader::read($calendarData);
            if ($vcal->VEVENT) {
                // Try UID first
                if ($vcal->VEVENT->UID) {
                    $uid = (string)$vcal->VEVENT->UID;
                    if (preg_match('/probenplaner-rehearsal-(\d+)@/', $uid, $m)) {
                        return (int)$m[1];
                    }
                }
                // Try RELATED-TO (Apple Calendar replies)
                if ($vcal->VEVENT->{'RELATED-TO'}) {
                    $related = (string)$vcal->VEVENT->{'RELATED-TO'};
                    if (preg_match('/probenplaner-rehearsal-(\d+)@/', $related, $m)) {
                        return (int)$m[1];
                    }
                }
            }
        } catch (\Exception $e) {}
        return null;
    }

    private function processRSVPUpdate(string $calendarData, array $meta, int $rehearsalId): void
    {
        try {
            $vcal   = Reader::read($calendarData);
            $vevent = $vcal->VEVENT;
            if (!$vevent) return;

            $partstat = null;
            $comment  = '';
            $caldavEmail = strtolower($this->getCaldavEmail((int)$meta['user_id']));
            $user        = $this->userModel->findById($meta['user_id']);
            $realEmail   = strtolower($user['email'] ?? '');

            foreach ($vevent->ATTENDEE as $attendee) {
                $raw      = strtolower((string)$attendee);
                $attEmail = str_starts_with($raw, 'mailto:') ? substr($raw, 7) : $raw;

                $isOwnAttendee = $attEmail === $caldavEmail
                    || $attEmail === $realEmail
                    || str_contains($attEmail, (string)$meta['user_id']);
                if (!$isOwnAttendee) continue;

                $p = $attendee->offsetGet('PARTSTAT');
                if ($p) $partstat = strtoupper((string)$p);
                $c = $attendee->offsetGet('X-COMMENT');
                if ($c) $comment = (string)$c;

                if ($partstat) break;
            }

            if (!$comment && isset($vevent->COMMENT)) {
                $comment = (string)$vevent->COMMENT;
            }

            if ($partstat === 'ACCEPTED') {
                $this->userModel->updatePromise($meta['user_id'], $rehearsalId, true, $comment);
            } elseif ($partstat === 'DECLINED') {
                $this->userModel->updatePromise($meta['user_id'], $rehearsalId, false, $comment);
            }
        } catch (\Exception $e) {
            error_log('CalDAV RSVP parse error: ' . $e->getMessage());
        }
    }

    public function deleteCalendarObject($calendarId, $objectUri): void
    {
        // Deleting rehearsals from the calendar app is not supported
        throw new \Sabre\DAV\Exception\Forbidden('Das Löschen von Proben direkt im Kalender wird nicht unterstützt. Bitte nutze die Probenplaner Web-App.');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    public function buildVEvent(array $rehearsal, int $userId, ?array $promise): string
    {
        $vcal  = new VCalendar();
        $vevent = $vcal->createComponent('VEVENT');

        $vevent->UID     = 'probenplaner-rehearsal-' . $rehearsal['id'] . '@probenplaner';
        
        $smartDisplay = new \App\Core\SmartGroupDisplay();
        $groupStr = '';
        if (!empty($rehearsal['groups']) && is_array($rehearsal['groups'])) {
            $groupStr = $smartDisplay->generateBaseDescription($rehearsal['groups']);
        }
        
        $typeLabel = !empty($rehearsal['type']) ? $rehearsal['type'] : 'Probe';
        $titleMain = !empty($rehearsal['name']) ? $typeLabel . ' - ' . $rehearsal['name'] : $typeLabel;
        $vevent->SUMMARY = $groupStr ? $titleMain . ' [' . $groupStr . ']' : $titleMain;

        $vevent->DTSTART = new \DateTime($rehearsal['start'] ?? 'now');
        $vevent->DTEND   = new \DateTime($rehearsal['end'] ?? 'now');

        if (!empty($rehearsal['location'])) {
            $vevent->LOCATION = $rehearsal['location'];
        }

        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $appUrl = rtrim($scheme . '://' . $host, '/');
        
        $orch = (new \App\Models\Orchestra())->findById($rehearsal['orchestra_id']);
        $slug = $orch['slug'] ?? $rehearsal['orchestra_id'];
        
        $userTypeSlug = 'member';
        foreach ($this->calendarMeta as $meta) {
             if (isset($meta['user_id'], $meta['orchestra_id']) && $meta['user_id'] == $userId && $meta['orchestra_id'] == $rehearsal['orchestra_id']) {
                 $userTypeSlug = $meta['user_type'] ? mb_strtolower($meta['user_type']) : 'member';
                 break;
             }
        }
        
        $vevent->add('URL', $appUrl . '/' . $slug . '/' . $userTypeSlug . '/promises#rehearsal-' . $rehearsal['id']);

        $rehearsalUpdated = strtotime($rehearsal['updated_at'] ?? $rehearsal['created_at'] ?? 'now');
        $promiseUpdated = 0;
        if ($promise && !empty($promise['updated_at'])) {
            $promiseUpdated = strtotime($promise['updated_at']);
        }
        $latestUpdateTimestamp = max($rehearsalUpdated, $promiseUpdated);

        // Event-Level attributes strictly rely on organizer updates
        $rehearsalUpdatedAt = new \DateTime('@' . $rehearsalUpdated);
        $rehearsalUpdatedAt->setTimezone(new \DateTimeZone('UTC'));
        
        // DTSTAMP reflects when this specific object generation happened/changed
        $updatedAt = new \DateTime('@' . $latestUpdateTimestamp);
        $updatedAt->setTimezone(new \DateTimeZone('UTC'));
        
        $createdAt = new \DateTime($rehearsal['created_at'] ?? 'now', new \DateTimeZone('Europe/Berlin'));
        $createdAt->setTimezone(new \DateTimeZone('UTC'));

        $vevent->add('DTSTAMP', clone $updatedAt);
        $vevent->add('LAST-MODIFIED', clone $rehearsalUpdatedAt);
        $vevent->add('CREATED', clone $createdAt);
        
        // Sequence MUST ONLY increment when the organizer changes the event, not when attendee RSVPs.
        $vevent->add('SEQUENCE', $rehearsalUpdated);

        // Build rich description from available metadata
        $desc = $this->buildDescription($rehearsal);
        if ($desc) {
            $vevent->DESCRIPTION = $desc;
        }

        // Tags as CATEGORIES
        if (!empty($rehearsal['tags'])) {
            $tags = is_array($rehearsal['tags']) ? $rehearsal['tags'] : explode(',', $rehearsal['tags']);
            if ($tags) {
                $vevent->CATEGORIES = array_map('trim', $tags);
            }
        }

        // ATTENDEE with PARTSTAT reflecting current promise status
        if (!isset($this->userCache[$userId])) {
            $this->userCache[$userId] = $this->userModel->findById($userId);
        }
        $user  = $this->userCache[$userId];
        $email = $this->getCaldavEmail($userId); // Fake email for auto-discovery
        $name  = $user['display_name'] ?? ($user['email'] ?? $email);

        $partstat = 'NEEDS-ACTION';
        $comment  = '';
        if ($promise) {
            $isAttending = isset($promise['status']) ? ($promise['status'] === 'yes') : ($promise['attending'] ?? false);
            $partstat = $isAttending ? 'ACCEPTED' : 'DECLINED';
            $comment  = $promise['note'] ?? '';
        }

        // Apple Calendar NEEDS an ORGANIZER to allow RSVP replies from attendees
        $host = isset($_SERVER['HTTP_HOST']) ? preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST']) : (getenv('DOMAIN') ?: 'localhost');
        $vevent->add('ORGANIZER', 'mailto:no-reply@' . $host, [
            'CN' => 'Probenplaner'
        ]);

        $attendeeValue = 'mailto:' . $email;
        $attendee = $vcal->createProperty('ATTENDEE', $attendeeValue);
        $attendee->add('PARTSTAT', $partstat);
        $attendee->add('CN', $name);
        $attendee->add('ROLE', 'REQ-PARTICIPANT');
        $attendee->add('SCHEDULE-AGENT', 'SERVER'); // Crucial to prevent email popup!
        $attendee->add('RSVP', 'TRUE');

        if ($comment) {
            $attendee->add('X-COMMENT', $comment);
        }
        $vevent->add($attendee);

        if ($comment && $partstat === 'DECLINED') {
            $vevent->COMMENT = $comment;
        }

        $vcal->add($vevent);

        return $vcal->serialize();
    }

    private function buildDescription(array $rehearsal): string
    {
        $parts = [];

        if (!empty($rehearsal['infos']) && is_array($rehearsal['infos'])) {
            $infoLines = [];
            foreach ($rehearsal['infos'] as $info) {
                $icon  = $info['emoji'] ?? '';
                $text  = $info['text'] ?? '';
                if ($text) {
                    $infoLines[] = trim($icon . ' ' . $text);
                }
            }
            if ($infoLines) {
                $parts[] = implode("\n", $infoLines);
            }
        }

        if (!empty($rehearsal['schedule_items']) && is_array($rehearsal['schedule_items'])) {
            $schedule = [];
            foreach ($rehearsal['schedule_items'] as $item) {
                $time  = $item['time'] ?? '';
                if ($time) {
                    $time = substr($time, 0, 5) . ' ';
                }
                $label = $item['label'] ?? '';
                if ($time || $label) {
                    $schedule[] = trim($time . $label);
                }
            }
            if ($schedule) {
                $parts[] = implode("\n", $schedule);
            }
        }

        return implode("\n\n", $parts);
    }

    private function resolveMeta(string $calendarId): ?array
    {
        if (isset($this->calendarMeta[$calendarId])) {
            return $this->calendarMeta[$calendarId];
        }

        // Parse from ID pattern orchestra_{orchId}_user_{userId}
        if (preg_match('/^orchestra_(\d+)_user_(\d+)$/', $calendarId, $m)) {
            $orchId = (int)$m[1];
            $userId = (int)$m[2];
            $user   = $this->userModel->findById($userId);

            $relation = $this->userOrchestraModel->getUserOrchestraRelation($userId, $orchId, true);

            $meta = [
                'user_id'      => $userId,
                'orchestra_id' => $orchId,
                'user_type'    => $relation['type'] ?? '',
            ];
            $this->calendarMeta[$calendarId] = $meta;
            return $meta;
        }

        return null;
    }

    private function uriToRehearsalId(string $uri): ?int
    {
        // uri format: rehearsal-{id}.ics
        if (preg_match('/^rehearsal-(\d+)\.ics$/', $uri, $m)) {
            return (int)$m[1];
        }
        return null;
    }

    private function emailFromPrincipal(string $principalUri): string
    {
        return urldecode(str_replace('principals/', '', $principalUri));
    }

    private function getUserRoleIds(int $userId, int $orchestraId): array
    {
        $roles = $this->userOrchestraModel->getUserRoles($userId, $orchestraId);
        return array_column($roles, 'id');
    }

    private function getPromisesIndexed(int $userId, array $rehearsalIds): array
    {
        if (empty($rehearsalIds)) return [];
        return $this->promiseModel->findPromisesForRehearsalsAndUser($rehearsalIds, $userId);
    }
}
