<?php

namespace App\Models;

use App\Core\Model;

/**
 * InviteLink Model
 *
 * @method array|null findById(int $id)
 */
class InviteLink extends Model
{
    protected $table = 'invite_links';

    public const TYPE_MEMBER = 'member';
    public const TYPE_CONDUCTOR = 'conductor';

    private const CONDUCTOR_EXPIRY_DAYS = 14;
    private const CONDUCTOR_PERMISSIONS = [
        'can_view_own_section_stats',
        'can_view_all_section_stats',
        'can_view_members',
        'can_manage_rehearsals',
        'can_manage_members',
        'can_manage_permissions',
        'can_manage_ensemble',
    ];

    protected function getAllowedFields(): array
    {
        return ['token', 'orchestra_id', 'email', 'default_permissions', 'expires_at', 'used_at', 'created_by'];
    }

    /**
     * Generate a new invite link.
     *
     * @param string $type 'member' or 'conductor' — conductor links expire after 14 days
     * @return array The created invite link row
     */
    public function generate(int $orchestraId, string $type = self::TYPE_MEMBER, ?int $createdBy = null): array
    {
        $token = $this->generateToken();
        $data = [
            'token' => $token,
            'orchestra_id' => $orchestraId,
            'created_by' => $createdBy,
        ];

        if ($type === self::TYPE_CONDUCTOR) {
            $data['default_permissions'] = json_encode(self::CONDUCTOR_PERMISSIONS);
            $data['expires_at'] = date('Y-m-d H:i:s', strtotime('+' . self::CONDUCTOR_EXPIRY_DAYS . ' days'));
        }

        $this->insert($data);
        return $this->findActiveByToken($token);
    }

    /**
     * Expire old links of a given type for an orchestra and create a new one.
     *
     * @return array The new invite link row
     */
    public function regenerate(int $orchestraId, string $type = self::TYPE_MEMBER, ?int $createdBy = null): array
    {
        if ($type === self::TYPE_CONDUCTOR) {
            $sql = "UPDATE invite_links SET used_at = NOW()
                    WHERE orchestra_id = ? AND default_permissions IS NOT NULL AND used_at IS NULL";
        } else {
            $sql = "UPDATE invite_links SET used_at = NOW()
                    WHERE orchestra_id = ? AND default_permissions IS NULL AND used_at IS NULL";
        }
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $orchestraId);
        $stmt->execute();
        $stmt->close();

        return $this->generate($orchestraId, $type, $createdBy);
    }

    /**
     * Find an active (unused, non-expired) invite link by token.
     */
    public function findActiveByToken(string $token): ?array
    {
        $sql = "SELECT il.*, o.name AS orchestra_name, o.organization_id,
                       org.name AS organization_name, org.slug AS organization_slug
                FROM invite_links il
                JOIN orchestras o ON o.id = il.orchestra_id
                LEFT JOIN organizations org ON org.id = o.organization_id
                WHERE il.token = ? AND il.used_at IS NULL
                  AND (il.expires_at IS NULL OR il.expires_at > NOW())
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('s', $token);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    /**
     * Mark a single-use invite link as used. Conductor links stay active (multi-use).
     */
    public function redeem(int $linkId): bool
    {
        $link = $this->findById($linkId);
        if (!$link) return false;

        // Conductor links are multi-use — don't mark as used
        if (!empty($link['default_permissions'])) {
            return true;
        }

        $sql = "UPDATE invite_links SET used_at = NOW() WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $linkId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    /**
     * Get all active invite links for an orchestra, grouped by type.
     */
    public function getActiveLinksForOrchestra(int $orchestraId): array
    {
        $sql = "SELECT * FROM invite_links
                WHERE orchestra_id = ? AND used_at IS NULL
                  AND (expires_at IS NULL OR expires_at > NOW())
                ORDER BY created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $orchestraId);
        $stmt->execute();
        $result = $stmt->get_result();
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        $stmt->close();
        return $rows;
    }

    /**
     * Get the active member invite link for an orchestra.
     */
    public function getActiveMemberLink(int $orchestraId): ?array
    {
        $sql = "SELECT * FROM invite_links
                WHERE orchestra_id = ? AND default_permissions IS NULL
                  AND used_at IS NULL AND (expires_at IS NULL OR expires_at > NOW())
                ORDER BY created_at DESC LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $orchestraId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    /**
     * Get the active conductor invite link for an orchestra.
     */
    public function getActiveConductorLink(int $orchestraId): ?array
    {
        $sql = "SELECT * FROM invite_links
                WHERE orchestra_id = ? AND default_permissions IS NOT NULL
                  AND used_at IS NULL AND (expires_at IS NULL OR expires_at > NOW())
                ORDER BY created_at DESC LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $orchestraId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    /**
     * Determine the link type from a link row.
     */
    public static function getLinkType(array $link): string
    {
        return !empty($link['default_permissions']) ? self::TYPE_CONDUCTOR : self::TYPE_MEMBER;
    }

    private function generateToken(): string
    {
        return substr(bin2hex(random_bytes(4)), 0, 8);
    }
}
