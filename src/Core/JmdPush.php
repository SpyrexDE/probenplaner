<?php

namespace App\Core;

/**
 * Push notifications through the JOinJMD push API.
 *
 * Recipients are Keycloak IDs (sub). The JOinJMD app opens the link in the Probenplaner
 * tile and appends the user's access token as jmd_accesstoken.
 */
class JmdPush
{
    private const TIMEOUT = 5;

    /** Push is only available on JMD instances with a configured endpoint. */
    public static function isEnabled(): bool
    {
        return KEYCLOAK_ENABLED && JMD_PUSH_URL !== '';
    }

    /**
     * @param string[] $keycloakSubs Recipients
     * @return bool True when the push API answered 2xx
     */
    public static function send(array $keycloakSubs, string $title, string $body, string $link): bool
    {
        if (!self::isEnabled() || empty($keycloakSubs)) {
            return false;
        }

        // Request body of the JOinJMD push API (field names follow Ruwen's spec)
        $payload = [
            'user_ids' => array_values(array_unique(array_map('strval', $keycloakSubs))),
            'title' => $title,
            'body' => $body,
            'link' => $link,
        ];

        $headers = ['Content-Type: application/json', 'Accept: application/json'];
        if (JMD_PUSH_TOKEN !== '') {
            $headers[] = 'Authorization: Bearer ' . JMD_PUSH_TOKEN;
        }

        $ch = curl_init(JMD_PUSH_URL);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, self::TIMEOUT);
        curl_setopt($ch, CURLOPT_TIMEOUT, self::TIMEOUT);

        curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error !== '' || $httpCode < 200 || $httpCode >= 300) {
            error_log('JOinJMD push failed: HTTP ' . $httpCode . ($error !== '' ? ', ' . $error : '') . ', ' . count($payload['user_ids']) . ' recipient(s)');
            return false;
        }

        return true;
    }

    /**
     * Absolute Probenplaner link for a push. The origin comes from KEYCLOAK_REDIRECT_URI, never
     * from the request's Host: the JOinJMD app appends the member's access token to this link.
     */
    public static function link(string $path): string
    {
        $url = parse_url(KEYCLOAK_REDIRECT_URI);
        return ($url['scheme'] ?? 'https') . '://' . ($url['host'] ?? '')
            . (isset($url['port']) ? ':' . $url['port'] : '') . $path;
    }

    /**
     * Title and body of the reminder for members who have not answered yet,
     * e.g. "Probe am Sa 17.10. um 10:00" / "Kammerensemble: Du hast noch nicht zu- oder abgesagt."
     *
     * @return array{title: string, body: string}
     */
    public static function missingResponseText(array $rehearsal, string $orchestraName): array
    {
        $weekdays = ['So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa'];
        $start = new \DateTime($rehearsal['start']);
        $type = trim((string)($rehearsal['type'] ?? ''));

        return [
            'title' => ($type !== '' ? $type : RehearsalTypeManager::TYPE_REHEARSAL)
                . ' am ' . $weekdays[(int)$start->format('w')] . ' ' . $start->format('d.m.')
                . ' um ' . $start->format('H:i'),
            'body' => $orchestraName . ': Du hast noch nicht zu- oder abgesagt.',
        ];
    }
}
