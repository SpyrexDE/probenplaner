<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;
use App\Models\Rehearsal;
use App\Models\UserOrchestra;
use App\Models\UserPromise;
use Sabre\VObject\Component\VCalendar;

/**
 * CalendarController
 *
 * Handles token lifecycle and serves the personalized iCal feed at /ical/{token}.
 */
class CalendarController extends Controller
{
    private User $userModel;

    public function __construct()
    {
        parent::__construct();
        $this->userModel = new User();
    }

    /**
     * POST /calendar/tokens/generate
     * Generates (or regenerates) cal tokens for the logged-in user.
     * Returns JSON with the tokens and ready-to-use URLs.
     */
    public function generateTokens(): void
    {
        if (!$this->requireLogin()) return;
        $this->protectCSRF();

        $userId = (int)$_SESSION['user_id'];
        $user = $this->userModel->findById($userId);
        
        if (empty($user['ical_token']) || empty($user['caldav_token'])) {
            $tokens = $this->userModel->generateCalendarTokens($userId);
        } else {
            $tokens = [
                'ical_token' => $user['ical_token'],
                'caldav_token' => $user['caldav_token']
            ];
        }

        $base      = $this->appBaseUrl();
        $icalUrl   = $base . '/ical/' . $tokens['ical_token'];
        $webcalUrl = preg_replace('/^https?/', 'webcal', $icalUrl);
        $caldavUrl = $base . '/caldav/';
        $host = isset($_SERVER['HTTP_HOST']) ? preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST']) : 'probenplaner.app';
        $email = $userId . '@' . $host;
        header('Content-Type: application/json');
        echo json_encode([
            'success'      => true,
            'ical_token'   => $tokens['ical_token'],
            'caldav_token' => $tokens['caldav_token'],
            'ical_url'     => $icalUrl,
            'webcal_url'   => $webcalUrl,
            'caldav_url'   => $caldavUrl,
            'caldav_user'  => $email,
        ]);
    }

    /**
     * POST /calendar/tokens/revoke
     * Invalidates all calendar tokens for the logged-in user.
     */
    public function revokeTokens(): void
    {
        if (!$this->requireLogin()) return;
        $this->protectCSRF();

        $this->userModel->revokeCalendarTokens((int)$_SESSION['user_id']);

        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    }

    /**
     * GET /calendar/tokens/status
     * Returns whether the user currently has tokens (without exposing the tokens).
     */
    public function tokenStatus(): void
    {
        if (!$this->requireLogin()) return;

        $user = $this->userModel->findById((int)$_SESSION['user_id']);
        header('Content-Type: application/json');
        echo json_encode([
            'success'    => true,
            'has_tokens' => !empty($user['ical_token']),
        ]);
    }

    /**
     * GET /ical/{token}
     * Serves a personalized iCal feed for all orchestras the user belongs to.
     * Token acts as the only authentication — no session needed.
     */
    public function serveIcal(array $params): void
    {
        $token = $params['token'] ?? '';
        $user  = $this->userModel->findByIcalToken($token);

        if (!$user) {
            http_response_code(404);
            exit;
        }

        $userId            = (int)$user['id'];
        $userOrchestraModel = new UserOrchestra();
        $rehearsalModel    = new Rehearsal();
        $promiseModel      = new UserPromise();

        $memberships = $userOrchestraModel->getUserOrchestras($userId);

        $vcalendar = new VCalendar();
        $vcalendar->PRODID = '-//Probenplaner//DE';
        // VERSION and CALSCALE are already 2.0 and GREGORIAN by default in Sabre
        $vcalendar->METHOD = 'PUBLISH';
        
        $calName = 'Probenplaner – Meine Proben';
        if (count($memberships) === 1) {
            $calName = $memberships[0]['orchestra_name'] ?? 'Probenplaner';
        }
        $vcalendar->{'X-WR-CALNAME'} = $calName;
        $vcalendar->{'X-WR-TIMEZONE'} = 'Europe/Berlin';

        foreach ($memberships as $orch) {
            $orchId   = (int)$orch['orchestra_id'];
            $relation = $userOrchestraModel->getUserOrchestraRelation($userId, $orchId, true);
            if (!$relation) continue;

            $roles       = $userOrchestraModel->getUserRoles($userId, $orchId);
            $roleIds     = array_column($roles, 'id');
            $isConductor = false;
            $allPerms    = [];
            foreach ($roles as $role) {
                if (!empty($role['is_system']) && ($role['name'] ?? '') === 'Leitung') $isConductor = true;
                $perms = !empty($role['is_system'])
                    ? \App\Models\Role::getConductorPermissions()
                    : (json_decode($role['permissions'] ?? '[]', true) ?: []);
                $allPerms = array_merge($allPerms, $perms);
            }
            $canRsvp = !$isConductor && in_array('can_attend_rehearsals', $allPerms, true);

            $hasManagePerm = in_array('can_manage_rehearsals', $allPerms, true) || $isConductor;
            if ($hasManagePerm) {
                $rehearsals = $rehearsalModel->getUpcoming($orchId, true);
            } else {
                $rehearsals = $rehearsalModel->getForUser($relation['type'], $orchId, true, $roleIds);
            }

            if (empty($rehearsals)) continue;

            $rehearsalIds = array_column($rehearsals, 'id');
            $promises     = $canRsvp ? $promiseModel->findPromisesForRehearsalsAndUser($rehearsalIds, $userId) : [];

            foreach ($rehearsals as $r) {
                $promise  = $promises[$r['id']] ?? null;
                $orchName = $orch['name'] ?? '';

                $statusEmoji = '';
                if ($canRsvp) {
                    $isAttending  = false;
                    $hasResponded = false;
                    if ($promise) {
                        $hasResponded = true;
                        $isAttending  = isset($promise['status']) ? ($promise['status'] === 'yes') : ($promise['attending'] ?? false);
                    }
                    $partstat = $hasResponded ? ($isAttending ? 'ACCEPTED' : 'DECLINED') : 'NEEDS-ACTION';

                    if ($partstat === 'ACCEPTED')      { $statusEmoji = '✅ '; }
                    elseif ($partstat === 'DECLINED')  { $statusEmoji = '❌ '; }
                    else                               { $statusEmoji = '❓ '; }
                }

                $dtstart = new \DateTime($r['start'] ?? 'now', new \DateTimeZone('Europe/Berlin'));
                $dtend = new \DateTime($r['end'] ?? 'now', new \DateTimeZone('Europe/Berlin'));

                // RFC 5545 STRICT: DTEND MUST be >= DTSTART. Negative durations crash Apple Calendar!
                if ($dtend < $dtstart) {
                    $dtend = clone $dtstart;
                    $dtend->modify('+1 hour'); // Arbitrary fallback to prevent fatal parser crash
                }

                $smartDisplay = new \App\Core\SmartGroupDisplay();
                $groupStr = '';
                if (!empty($r['groups']) && is_array($r['groups'])) {
                    $groupStr = $smartDisplay->generateBaseDescription($r['groups']);
                }
                
                $typeLabel = !empty($r['type']) ? $r['type'] : 'Probe';
                $summary = $statusEmoji . ($groupStr ? $typeLabel . ' [' . $groupStr . ']' : $typeLabel);

                $host = isset($_SERVER['HTTP_HOST']) ? preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST']) : (getenv('DOMAIN') ?: 'localhost');
                $vevent = $vcalendar->add('VEVENT', [
                    'UID' => 'probenplaner-' . $r['id'] . '-' . md5($r['start']) . '@' . $host,
                    'DTSTART' => $dtstart,
                    'DTEND'   => $dtend,
                    'SUMMARY' => $summary,
                    'DESCRIPTION' => str_replace("\\n", "\n", $this->buildDescription($r))
                ]);

                
                
                $updatedAt = new \DateTime($r['updated_at'] ?? $r['created_at'] ?? 'now', new \DateTimeZone('Europe/Berlin'));
                $createdAt = new \DateTime($r['created_at'] ?? 'now', new \DateTimeZone('Europe/Berlin'));
                $updatedAt->setTimezone(new \DateTimeZone('UTC'));
                $createdAt->setTimezone(new \DateTimeZone('UTC'));

                $vevent->add('DTSTAMP', clone $updatedAt);
                $vevent->add('LAST-MODIFIED', clone $updatedAt);
                $vevent->add('CREATED', clone $createdAt);
                
                // Sequence increments every time the event resets updated_at
                $vevent->add('SEQUENCE', $updatedAt->getTimestamp());

                // Direct backlink to the app
                $appUrl = rtrim($this->appBaseUrl(), '/');
                $orgSlug = $orch['org_slug'] ?? 'default';
                $slug = $orch['orchestra_slug'] ?? $orchId;
                $vevent->add('URL', $appUrl . '/' . $orgSlug . '/' . $slug . '/promises?rehearsal=' . $r['id']);


                if (!empty($r['location'])) {
                    $vevent->add('LOCATION', $r['location']);
                }

                if (!empty($r['color'])) {
                    $vevent->add('COLOR', $r['color']);
                }

                $categories = [];
                if ($orchName) $categories[] = $orchName;
                if (!empty($r['tags'])) {
                    $tags = is_array($r['tags']) ? $r['tags'] : array_filter(array_map('trim', explode(',', $r['tags'])));
                    $categories = array_merge($categories, $tags);
                }
                if (!empty($categories)) {
                    $vevent->add('CATEGORIES', $categories);
                }

                
            }
        }

        $output = $vcalendar->serialize();

        header('Content-Type: text/calendar; charset=utf-8');
        header('Content-Length: ' . strlen($output));
        header('Content-Disposition: attachment; filename="probenplaner.ics"');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        echo $output;
        exit;
    }

    private function buildDescription(array $r): string
    {
        $parts = [];

        if (!empty($r['infos']) && is_array($r['infos'])) {
            $infoLines = [];
            foreach ($r['infos'] as $info) {
                $icon  = $info['emoji'] ?? '';
                $text  = $info['text'] ?? '';
                if ($text) {
                    $infoLines[] = trim($icon . ' ' . $text);
                }
            }
            if ($infoLines) $parts[] = implode("\n", $infoLines);
        }

        if (!empty($r['schedule_items']) && is_array($r['schedule_items'])) {
            $schedule = [];
            foreach ($r['schedule_items'] as $item) {
                $timeFormatted = '';
                if (!empty($item['time'])) {
                    // Extract HH:mm
                    $timeFormatted = substr($item['time'], 0, 5) . ' ';
                }
                $t = trim($timeFormatted . ($item['label'] ?? ''));
                if ($t) $schedule[] = $t;
            }
            if ($schedule) $parts[] = implode("\n", $schedule);
        }

        return implode("\n\n", $parts);
    }

    private function formatIcalDate(string $datetime): string
    {
        try {
            $dt = new \DateTime($datetime, new \DateTimeZone('Europe/Berlin'));
            $dt->setTimezone(new \DateTimeZone('UTC'));
            return $dt->format('Ymd\THis\Z');
        } catch (\Exception $e) {
            return gmdate('Ymd\THis\Z');
        }
    }

    /** Escape special iCal characters and fold long lines */
    private function icalEscape(string $value): string
    {
        $value = str_replace(['\\', ';', ',', "\n"], ['\\\\', '\\;', '\\,', '\\n'], $value);
        return $value;
    }

    private function appBaseUrl(): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $scheme . '://' . $host;
    }
}
