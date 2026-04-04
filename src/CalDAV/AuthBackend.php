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
            file_put_contents($logFile, "AUTH FAIL: User has no caldav_token for username $username\n", FILE_APPEND);
            return false;
        }
        
        $match = hash_equals($user['caldav_token'], $password);
        if (!$match) {
            file_put_contents($logFile, "AUTH FAIL: Token mismatch. Expected '{$user['caldav_token']}', got '$password'\n", FILE_APPEND);
        } else {
            file_put_contents($logFile, "AUTH SUCCESS for username $username\n", FILE_APPEND);
        }
        return $match;
    }
}
