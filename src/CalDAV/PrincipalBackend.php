<?php

namespace App\CalDAV;

use App\Models\User;
use Sabre\DAVACL\PrincipalBackend\AbstractBackend;

/**
 * Maps Probenplaner users to CalDAV principals.
 * Each user gets a principal at principals/{email}.
 */
class PrincipalBackend extends AbstractBackend
{
    private User $userModel;

    private function getCaldavEmail(int $userId): string
    {
        $host = isset($_SERVER['HTTP_HOST']) ? preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST']) : (getenv('DOMAIN') ?: 'localhost');
        return $userId . '@' . $host;
    }

    public function __construct()
    {
        $this->userModel = new User();
    }

    public function getPrincipalsByPrefix($prefixPath): array
    {
        // Only the authenticated user is relevant in practice
        return [];
    }

    public function getPrincipalByPath($path): array|false
    {
        $prefix = 'principals/';
        if (!str_starts_with($path, $prefix)) {
            return false;
        }
        $email = urldecode(substr($path, strlen($prefix)));
        $user  = null;
        if (preg_match('/^(\d+)(@|$)/i', $email, $matches)) {
            $user = $this->userModel->findById((int)$matches[1]);
        } else {
            $user = $this->userModel->findByEmail($email);
        }
        
        if (!$user) {
            return false;
        }
        return $this->buildPrincipal($user, $email);
    }

    public function getGroupMemberSet($principal): array
    {
        return [];
    }

    public function getGroupMembership($principal): array
    {
        return [];
    }

    public function setGroupMemberSet($principal, array $members): void {}

    public function updatePrincipal($path, \Sabre\DAV\PropPatch $propPatch): int
    {
        return 0;
    }

    public function searchPrincipals($prefixPath, array $searchProperties, $test = 'allof'): array
    {
        return [];
    }

    private function buildPrincipal(array $user, string $requestedEmail): array
    {
        return [
            'uri'                                    => 'principals/' . $requestedEmail,
            '{DAV:}displayname'                      => $user['display_name'] ?? 'Probenplaner Nutzer',
            '{http://sabredav.org/ns}email-address'  => $this->getCaldavEmail((int)$user['id']),
            '{urn:ietf:params:xml:ns:caldav}calendar-user-address-set' => new \Sabre\DAV\Xml\Property\Href(['mailto:' . $this->getCaldavEmail((int)$user['id'])]),
        ];
    }
}
