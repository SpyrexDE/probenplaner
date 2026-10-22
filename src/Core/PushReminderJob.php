<?php

namespace App\Core;

use App\Models\Orchestra;
use App\Models\Rehearsal;
use App\Models\UserOrchestra;

/**
 * Push reminders before rehearsals (JOinJMD). Members choose the lead time in their profile
 * (users.push_reminder_minutes, NULL = off); docker/startup.sh runs this once a minute.
 *
 * A member is reminded of every rehearsal they are invited to and have not declined, the same
 * invitation rule as the response dashboards. Each member and rehearsal is reminded once
 * (push_reminders), marked before sending: the push API is not idempotent, so a failed call
 * is logged by JmdPush and not repeated.
 */
class PushReminderJob
{
    public static function run(): void
    {
        if (!JmdPush::isEnabled()) {
            return;
        }

        $db = Database::getInstance();
        $now = time();

        // user_id => lead time in seconds
        $leads = [];
        $result = $db->query("SELECT id, push_reminder_minutes FROM users WHERE push_reminder_minutes IS NOT NULL AND keycloak_id IS NOT NULL");
        while ($result && ($row = $result->fetch_assoc())) {
            $leads[(int)$row['id']] = (int)$row['push_reminder_minutes'] * 60;
        }
        if (empty($leads)) {
            return;
        }

        // Rehearsals starting within the longest lead time. Times are local (Europe/Berlin) like
        // rehearsals.start, so the bounds come from PHP, not from MySQL's NOW().
        $from = date('Y-m-d H:i:s', $now);
        $to = date('Y-m-d H:i:s', $now + max($leads));
        $stmt = $db->prepare("SELECT * FROM rehearsals WHERE start > ? AND start <= ? ORDER BY start");
        $stmt->bind_param('ss', $from, $to);
        $stmt->execute();
        $byOrchestra = [];
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $byOrchestra[(int)$row['orchestra_id']][] = $row;
        }
        $stmt->close();
        if (empty($byOrchestra)) {
            return;
        }

        $rehearsalIds = array_map(fn($r) => (int)$r['id'], array_merge(...array_values($byOrchestra)));
        $idList = implode(',', $rehearsalIds);
        $promises = self::pairs($db, "SELECT user_id, rehearsal_id, status FROM user_promises WHERE rehearsal_id IN ($idList)", 'status');
        $reminded = self::pairs($db, "SELECT user_id, rehearsal_id FROM push_reminders WHERE rehearsal_id IN ($idList)");

        $rehearsalModel = new Rehearsal();
        $groupsMap = $rehearsalModel->getBatchRehearsalGroupsAssoc($rehearsalIds);
        $rolesMap = $rehearsalModel->getBatchRehearsalRoleIds($rehearsalIds);
        $userOrchestraModel = new UserOrchestra();
        $orchestraModel = new Orchestra();

        foreach ($byOrchestra as $orchestraId => $rehearsals) {
            $members = array_filter(
                $userOrchestraModel->getOrchestraUsers($orchestraId),
                fn($m) => isset($leads[(int)$m['user_id']]) && !empty($m['can_attend_rehearsals'])
            );
            $orchestra = $members ? $orchestraModel->findByIdWithOrg($orchestraId) : null;
            if (!$orchestra) {
                continue;
            }

            // GroupManager reads the orchestra's own section config from the session
            $_SESSION['current_orchestra_id'] = $orchestraId;

            foreach ($rehearsals as $rehearsal) {
                $rehearsalId = (int)$rehearsal['id'];
                $start = strtotime($rehearsal['start']);
                $due = ['accepted' => [], 'open' => []];

                foreach ($members as $member) {
                    $userId = (int)$member['user_id'];
                    $status = $promises[$userId][$rehearsalId] ?? null;
                    if ($start - $leads[$userId] > $now || isset($reminded[$userId][$rehearsalId]) || $status === 'no') {
                        continue;
                    }
                    if (!$rehearsalModel->isUserInRehearsalGroup((string)$member['type'], $groupsMap[$rehearsalId] ?? [], $rolesMap[$rehearsalId] ?? [], $member['role_ids'] ?? [])) {
                        continue;
                    }
                    $due[$status === 'yes' ? 'accepted' : 'open'][$userId] = (string)$member['keycloak_id'];
                }

                foreach ($due as $kind => $subs) {
                    if (empty($subs)) {
                        continue;
                    }
                    // Unmarked, the next run would send it again
                    if (!self::markReminded($db, array_keys($subs), $rehearsalId)) {
                        error_log('PushReminders: marking rehearsal ' . $rehearsalId . ' failed, not sent');
                        continue;
                    }
                    $text = $kind === 'accepted'
                        ? JmdPush::acceptedText($rehearsal, (string)$orchestra['name'])
                        : JmdPush::missingResponseText($rehearsal, (string)$orchestra['name']);
                    JmdPush::send(array_values($subs), $text['title'], $text['body'], JmdPush::rehearsalLink($orchestra, $rehearsalId));
                }
            }
        }
    }

    /**
     * @return array<int, array<int, mixed>> user_id => rehearsal_id => $valueColumn (or true)
     */
    private static function pairs(Database $db, string $sql, ?string $valueColumn = null): array
    {
        $map = [];
        $result = $db->query($sql);
        while ($result && ($row = $result->fetch_assoc())) {
            $map[(int)$row['user_id']][(int)$row['rehearsal_id']] = $valueColumn ? $row[$valueColumn] : true;
        }
        return $map;
    }

    /** @param int[] $userIds */
    private static function markReminded(Database $db, array $userIds, int $rehearsalId): bool
    {
        $values = implode(',', array_map(fn($userId) => '(' . (int)$userId . ',' . $rehearsalId . ')', $userIds));
        return $db->query("INSERT IGNORE INTO push_reminders (user_id, rehearsal_id) VALUES $values") === true;
    }
}
