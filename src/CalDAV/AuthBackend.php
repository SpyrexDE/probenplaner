<?php

namespace App\CalDAV;

use App\Models\User;
use Sabre\DAV\Auth\Backend\AbstractBasic;

/**
 * CalDAV auth backend using email + caldav_token (app password).
 * Never exposes the user's main account password.
 */
class AuthBackend extends AbstractBasic
{
    private User $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    protected function validateUserPass($username, $password): bool
    {
        $user = null;
        $user = null;
        if (preg_match('/^(\d+)(@|$)/i', $username, $matches)) {
            $user = $this->userModel->findById((int)$matches[1]);
        } else {
            $user = $this->userModel->findByEmail($username);
        }
        
        $logFile = __DIR__ . '/../caldav_auth.log';
        if (!$user) {
            file_put_contents($logFile, "AUTH FAIL: User null for username $username\n", FILE_APPEND);
            return false;
        }
        if (empty($user['caldav_token'])) {
            return false;
        }
        
        return hash_equals($user['caldav_token'], $password);
    }
}
