<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\SectionMatcher;
use App\Models\Orchestra;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use App\Models\UserOrchestra;

/**
 * Partner API for the JOinJMD server (/api/v1, see docs/partner-api.md)
 *
 * JOinJMD keeps orchestras, their leaders and members in sync. Orchestras are named by the
 * JMD orchestra ID (= orchestras.slug, compared case-insensitively), people by their Keycloak
 * ID (sub = users.keycloak_id). Auth: "Authorization: Bearer PARTNER_API_TOKEN", no session
 * (CSRF-exempt). Off (every path 404) unless KEYCLOAK_ENABLED and PARTNER_API_TOKEN are set.
 */
class PartnerApiController extends Controller
{
    /** JMD orchestra IDs, as in JmdLaunch */
    private const ORCHESTRA_ID_PATTERN = '/^[A-Za-z0-9_-]{1,100}$/';
    /** users.keycloak_id is VARCHAR(255) */
    private const SUB_PATTERN = '/^\S{1,255}$/u';
    private const MAX_DISPLAY_NAME_LENGTH = 100; // users.display_name

    private Orchestra $orchestraModel;
    private User $userModel;
    private UserOrchestra $userOrchestraModel;

    public function __construct()
    {
        parent::__construct();
        $this->orchestraModel = new Orchestra();
        $this->userModel = new User();
        $this->userOrchestraModel = new UserOrchestra();
    }

    /**
     * PUT /api/v1/orchestras/{id} {"name"}: create the orchestra in PARTNER_API_ORG.
     * An existing one keeps its name (leaders may rename it here).
     */
    public function putOrchestra(array $params): void
    {
        $this->respond('/api/v1/orchestras/{id}', function () use ($params) {
            $id = self::param($params, 'id');
            if (!preg_match(self::ORCHESTRA_ID_PATTERN, $id)) {
                return self::invalidOrchestraId();
            }
            $fields = $this->fields(['name'], $error);
            if ($error) {
                return $error;
            }

            $orchestra = $this->findOrchestra($id);
            if ($orchestra) {
                if (!self::inPartnerOrg($orchestra)) {
                    return self::error(409, 'conflict', 'Orchestra ID is used by another organization');
                }
                return [200, ['id' => $orchestra['slug'], 'name' => $orchestra['name']]];
            }

            $name = $fields['name'];
            if ($name === '') {
                return self::error(422, 'invalid_name', 'name is required');
            }
            if (mb_strlen($name, 'UTF-8') > MAX_ORCHESTRA_NAME_LENGTH) {
                return self::error(422, 'invalid_name', 'name must be at most ' . MAX_ORCHESTRA_NAME_LENGTH . ' characters');
            }

            $org = PARTNER_API_ORG !== '' ? (new Organization())->findBySlug(PARTNER_API_ORG) : null;
            if (!$org) {
                return self::error(500, 'config_error', 'PARTNER_API_ORG does not name an existing organization');
            }

            // Like OrgaPanelController::storeEnsemble, without invite links
            $orchestraId = (int)$this->orchestraModel->createOrchestra([
                'name' => $name,
                'slug' => $id,
                'organization_id' => (int)$org['id'],
            ]);
            (new Role())->createDefaultRoles($orchestraId);

            return [201, ['id' => $id, 'name' => $name]];
        });
    }

    /**
     * DELETE /api/v1/orchestras/{id}: delete the orchestra with all its data.
     */
    public function deleteOrchestra(array $params): void
    {
        $this->respond('/api/v1/orchestras/{id}', function () use ($params) {
            $id = self::param($params, 'id');
            if (!preg_match(self::ORCHESTRA_ID_PATTERN, $id)) {
                return self::invalidOrchestraId();
            }

            $orchestra = $this->findOrchestra($id);
            if (!$orchestra) {
                return [204, null];
            }
            if (!self::inPartnerOrg($orchestra)) {
                return self::error(409, 'conflict', 'Orchestra ID is used by another organization');
            }

            // Same as OrgaPanelController::deleteEnsemble (the foreign keys cascade)
            if (!$this->orchestraModel->delete((int)$orchestra['id'])) {
                return self::error(500, 'internal_error', 'Orchestra could not be deleted');
            }
            return [204, null];
        });
    }

    /**
     * PUT /api/v1/orchestras/{id}/members/{sub} {"email","name","instrument","instrument2"}
     */
    public function putMember(array $params): void
    {
        $this->respond('/api/v1/orchestras/{id}/members/{sub}', function () use ($params) {
            $orchestra = $this->partnerOrchestra($params, $error);
            if ($error) {
                return $error;
            }
            $sub = $this->sub($params, $error);
            if ($error) {
                return $error;
            }
            $fields = $this->fields(['email', 'display_name', 'instrument', 'instrument2'], $error);
            if ($error) {
                return $error;
            }
            $user = $this->resolveUser($sub, $fields, $error);
            if ($error) {
                return $error;
            }

            $userId = (int)$user['id'];
            $orchestraId = (int)$orchestra['id'];
            $hasInstrument = $fields['instrument'] !== '' || $fields['instrument2'] !== '';

            $relation = $this->userOrchestraModel->getUserOrchestraRelation($userId, $orchestraId);
            if ($relation) {
                $groups = SectionMatcher::groupsFor($orchestra);
                $section = (string)$relation['type'];
                $confirmed = !empty($relation['section_confirmed']);
                $isMember = in_array($groups->resolveAlias($section), SectionMatcher::leafIds($groups), true);
                // A leader (type 'Leitung') gets a section; a guessed section follows the
                // instrument until the member confirms it
                if (!$isMember || (!$confirmed && $hasInstrument)) {
                    [$section, $confirmed] = SectionMatcher::match($groups, $fields['instrument'], $fields['instrument2']);
                    if ($section === null) {
                        return self::error(500, 'config_error', 'Orchestra has no sections');
                    }
                    $this->setSection((int)$relation['id'], $section, $confirmed);
                }
                if (!$isMember) {
                    // Default roles next to Leitung, as for a new member
                    foreach ((new Role())->getDefaultRoles($orchestraId) as $role) {
                        $this->userOrchestraModel->addRoleToRelation((int)$relation['id'], (int)$role['id']);
                    }
                }
                return [$isMember ? 200 : 201, self::memberBody($sub, $section, $confirmed)];
            }

            [$section, $confirmed] = SectionMatcher::match(SectionMatcher::groupsFor($orchestra), $fields['instrument'], $fields['instrument2']);
            if ($section === null) {
                return self::error(500, 'config_error', 'Orchestra has no sections');
            }
            // Default roles like joining via a member invite link
            $relationId = $this->userOrchestraModel->joinOrchestra($userId, $orchestraId, $section);
            if (is_array($relationId)) {
                throw new \RuntimeException('joinOrchestra failed');
            }
            $this->setSection((int)$relationId, $section, $confirmed);

            return [201, self::memberBody($sub, $section, $confirmed)];
        });
    }

    /**
     * DELETE /api/v1/orchestras/{id}/members/{sub}: end the membership; the account stays.
     * A leader stays as leader only.
     */
    public function deleteMember(array $params): void
    {
        $this->respond('/api/v1/orchestras/{id}/members/{sub}', function () use ($params) {
            $orchestra = $this->partnerOrchestra($params, $error, false);
            if ($error || !$orchestra) {
                return $error ?? [204, null];
            }
            $sub = $this->sub($params, $error);
            if ($error) {
                return $error;
            }

            $user = $this->userModel->findByKeycloakId($sub);
            if (!$user) {
                return [204, null];
            }
            $userId = (int)$user['id'];
            $orchestraId = (int)$orchestra['id'];

            $conductorRole = (new Role())->getConductorRole($orchestraId);
            $roleIds = array_map(fn($role) => (int)$role['id'], $this->userOrchestraModel->getUserRoles($userId, $orchestraId));
            $relation = $this->userOrchestraModel->getUserOrchestraRelation($userId, $orchestraId);
            if ($relation && $conductorRole && in_array((int)$conductorRole['id'], $roleIds, true)) {
                // Back to a leader-only membership like a conductor invite link (setRoles keeps Leitung)
                $this->userOrchestraModel->setRoles($userId, $orchestraId, []);
                $this->setSection((int)$relation['id'], 'Leitung', true);
            } else {
                // Same as MemberController::removeMember
                $this->userOrchestraModel->removeFromOrchestra($userId, $orchestraId);
            }
            return [204, null];
        });
    }

    /**
     * PUT /api/v1/orchestras/{id}/leaders/{sub} {"email","name"}: give the Leitung role.
     */
    public function putLeader(array $params): void
    {
        $this->respond('/api/v1/orchestras/{id}/leaders/{sub}', function () use ($params) {
            $orchestra = $this->partnerOrchestra($params, $error);
            if ($error) {
                return $error;
            }
            $sub = $this->sub($params, $error);
            if ($error) {
                return $error;
            }
            $fields = $this->fields(['email', 'display_name'], $error);
            if ($error) {
                return $error;
            }
            $user = $this->resolveUser($sub, $fields, $error);
            if ($error) {
                return $error;
            }

            $userId = (int)$user['id'];
            $orchestraId = (int)$orchestra['id'];
            $conductorRole = (new Role())->getConductorRole($orchestraId);
            if (!$conductorRole) {
                return self::error(500, 'config_error', 'Orchestra has no Leitung role');
            }

            $relation = $this->userOrchestraModel->getUserOrchestraRelation($userId, $orchestraId);
            if ($relation) {
                $added = $this->userOrchestraModel->addRoleToRelation((int)$relation['id'], (int)$conductorRole['id']);
            } else {
                // Like a conductor invite link: type 'Leitung' and only the Leitung role
                $relationId = $this->userOrchestraModel->joinOrchestra($userId, $orchestraId, 'Leitung', (int)$conductorRole['id']);
                if (is_array($relationId)) {
                    throw new \RuntimeException('joinOrchestra failed');
                }
                $added = true;
            }

            return [$added ? 201 : 200, ['sub' => $sub, 'role' => $conductorRole['name']]];
        });
    }

    /**
     * DELETE /api/v1/orchestras/{id}/leaders/{sub}: take the Leitung role; a member stays a member.
     */
    public function deleteLeader(array $params): void
    {
        $this->respond('/api/v1/orchestras/{id}/leaders/{sub}', function () use ($params) {
            $orchestra = $this->partnerOrchestra($params, $error, false);
            if ($error || !$orchestra) {
                return $error ?? [204, null];
            }
            $sub = $this->sub($params, $error);
            if ($error) {
                return $error;
            }

            $user = $this->userModel->findByKeycloakId($sub);
            $orchestraId = (int)$orchestra['id'];
            $relation = $user ? $this->userOrchestraModel->getUserOrchestraRelation((int)$user['id'], $orchestraId) : null;
            if (!$relation) {
                return [204, null];
            }
            $userId = (int)$user['id'];

            $conductorRole = (new Role())->getConductorRole($orchestraId);
            if ($conductorRole) {
                $this->userOrchestraModel->removeRoleFromRelation((int)$relation['id'], (int)$conductorRole['id']);
            }
            // Only a leader, not a member: the membership ends
            if (!$this->userOrchestraModel->getUserRoles($userId, $orchestraId)) {
                $this->userOrchestraModel->removeFromOrchestra($userId, $orchestraId);
                return [204, null];
            }

            // Leaders join with type 'Leitung'; as a member they need a section, which they confirm
            $groups = SectionMatcher::groupsFor($orchestra);
            if (!in_array($groups->resolveAlias((string)$relation['type']), SectionMatcher::leafIds($groups), true)) {
                [$section] = SectionMatcher::match($groups, null);
                if ($section !== null) {
                    $this->setSection((int)$relation['id'], $section, false);
                }
            }

            return [204, null];
        });
    }

    /**
     * Router fallback for /api/v1 paths without a route: 405 if the path exists with other
     * methods, otherwise 404.
     */
    public function unmatched(array $params): void
    {
        $allow = $params['allow'] ?? [];
        $this->respond('(unmatched)', function () use ($allow) {
            if (empty($allow)) {
                return self::error(404, 'not_found', 'Unknown API path');
            }
            header('Allow: ' . implode(', ', $allow));
            return self::error(405, 'method_not_allowed', 'Method not allowed, use ' . implode(', ', $allow));
        });
    }

    // ── Helpers ─────────────────────────────────────────────────

    /**
     * Run the handler if the API is on and the bearer token is right, send its
     * [status, body|null] as JSON and log one line (no personal data).
     */
    private function respond(string $route, callable $handler): void
    {
        if (!KEYCLOAK_ENABLED || PARTNER_API_TOKEN === '') {
            $response = self::error(404, 'not_found', 'Not found');
        } elseif (!self::authorized()) {
            header('WWW-Authenticate: Bearer');
            $response = self::error(401, 'unauthorized', 'Missing or invalid bearer token');
        } else {
            try {
                $response = $handler();
            } catch (\Throwable $e) {
                error_log('PartnerApi: ' . get_class($e) . ' in ' . basename($e->getFile()) . ':' . $e->getLine());
                $response = self::error(500, 'internal_error', 'Internal error');
            }
        }

        [$status, $body] = $response;
        error_log(sprintf('PartnerApi %s %s %d', $_SERVER['REQUEST_METHOD'] ?? '', $route, $status));

        http_response_code($status);
        if ($body !== null) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
    }

    private static function authorized(): bool
    {
        // mod_php passes the header as HTTP_AUTHORIZATION; after a rewrite it can arrive with
        // the REDIRECT_ prefix (SetEnvIf in .htaccess) or only via getallheaders()
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
        if ($header === '' && function_exists('getallheaders')) {
            foreach (getallheaders() as $name => $value) {
                if (strcasecmp($name, 'Authorization') === 0) {
                    $header = $value;
                }
            }
        }

        return preg_match('/^Bearer\s+(\S+)\s*$/i', (string)$header, $m) === 1
            && hash_equals(PARTNER_API_TOKEN, $m[1]);
    }

    private static function error(int $status, string $code, string $message): array
    {
        return [$status, ['error' => $code, 'message' => $message]];
    }

    private static function invalidOrchestraId(): array
    {
        return self::error(422, 'invalid_id', 'Orchestra ID must be 1-100 letters, digits, "_" or "-"');
    }

    private static function param(array $params, string $name): string
    {
        return rawurldecode((string)($params[$name] ?? ''));
    }

    /**
     * String fields of the JSON body, trimmed ('' if absent).
     *
     * @param array|null $error Set to the 400/422 response if the body is no JSON object
     *                          or a field is no string
     */
    private function fields(array $keys, ?array &$error): array
    {
        $error = null;
        $raw = (string)file_get_contents('php://input');
        $body = trim($raw) === '' ? [] : json_decode($raw, true);
        if (!is_array($body) || ($body && array_keys($body) === range(0, count($body) - 1))) {
            $error = self::error(400, 'invalid_json', 'Body must be a JSON object');
            return [];
        }

        $fields = [];
        foreach ($keys as $key) {
            $value = $body[$key] ?? '';
            if (!is_string($value)) {
                $error = self::error(422, 'invalid_field', $key . ' must be a string');
                return [];
            }
            $fields[$key] = trim($value);
        }
        return $fields;
    }

    /**
     * Orchestra by JMD orchestra ID, case-insensitive like JmdLaunch.
     */
    private function findOrchestra(string $id): ?array
    {
        $orchestra = $this->orchestraModel->findBySlugWithOrg($id);
        return $orchestra && strcasecmp((string)$orchestra['slug'], $id) === 0 ? $orchestra : null;
    }

    private static function inPartnerOrg(array $orchestra): bool
    {
        return PARTNER_API_ORG !== '' && strcasecmp((string)($orchestra['org_slug'] ?? ''), PARTNER_API_ORG) === 0;
    }

    /**
     * The {id} orchestra if it belongs to PARTNER_API_ORG.
     *
     * @param array|null $error Set to the 422/404 response; with $mustExist = false a missing
     *                          orchestra is no error and null is returned
     */
    private function partnerOrchestra(array $params, ?array &$error, bool $mustExist = true): ?array
    {
        $error = null;
        $id = self::param($params, 'id');
        if (!preg_match(self::ORCHESTRA_ID_PATTERN, $id)) {
            $error = self::invalidOrchestraId();
            return null;
        }

        $orchestra = $this->findOrchestra($id);
        if ($orchestra && self::inPartnerOrg($orchestra)) {
            return $orchestra;
        }
        if ($orchestra || $mustExist) {
            $error = self::error(404, 'orchestra_not_found', 'Orchestra not found');
        }
        return null;
    }

    private function sub(array $params, ?array &$error): string
    {
        $error = null;
        $sub = self::param($params, 'sub');
        if (!preg_match(self::SUB_PATTERN, $sub)) {
            $error = self::error(422, 'invalid_sub', 'Keycloak ID must be 1-255 characters without whitespace');
        }
        return $sub;
    }

    /**
     * Account for the Keycloak ID: by keycloak_id, else the account with this e-mail (linked to the
     * Keycloak ID like on a JOinJMD login), else a new account. Name and e-mail of existing
     * accounts stay: display_name is only the starting value, members change it in their profile.
     *
     * @param array|null $error Set to the 409/422 response
     */
    private function resolveUser(string $sub, array $fields, ?array &$error): ?array
    {
        $error = null;
        $user = $this->userModel->findByKeycloakId($sub);
        if ($user) {
            return $user;
        }

        $email = $fields['email'];
        $name = $fields['display_name'];
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = self::error(422, 'invalid_email', 'email is not a valid e-mail address');
            return null;
        }

        if ($email !== '') {
            $user = $this->userModel->findByEmail($email);
            // findByEmail() also matches accented variants; only the same address (ASCII case aside) is linked
            if ($user && empty($user['keycloak_id']) && strcasecmp((string)$user['email'], $email) === 0) {
                $linkData = \App\Models\User::keycloakLinkData($user, $sub);
                if (!$this->userModel->update((int)$user['id'], $linkData)) {
                    throw new \RuntimeException('Linking the account failed');
                }
                return array_merge($user, $linkData);
            }
            if ($user) {
                $error = self::error(409, 'email_conflict', 'email belongs to another account');
                return null;
            }
        }

        if ($email === '' || $name === '') {
            $error = self::error(422, 'missing_fields', 'email and display_name are required for a new account');
            return null;
        }
        if (mb_strlen($name, 'UTF-8') > self::MAX_DISPLAY_NAME_LENGTH) {
            $error = self::error(422, 'invalid_name', 'display_name must be at most ' . self::MAX_DISPLAY_NAME_LENGTH . ' characters');
            return null;
        }

        // Like a first JOinJMD login (User::createOrLinkKeycloakUser); last_login_at stays NULL
        $userId = $this->userModel->insert([
            'email' => $email,
            'display_name' => $name,
            'keycloak_id' => $sub,
            'auth_provider' => 'keycloak',
            'password' => null,
            'theme' => 'jeunesse',
        ]);
        if (!$userId) {
            throw new \RuntimeException('Creating the account failed');
        }
        return $this->userModel->findById((int)$userId);
    }

    private function setSection(int $relationId, string $section, bool $confirmed): void
    {
        if (!$this->userOrchestraModel->update($relationId, ['type' => $section, 'section_confirmed' => $confirmed ? 1 : 0])) {
            throw new \RuntimeException('Saving the section failed');
        }
    }

    private static function memberBody(string $sub, string $section, bool $confirmed): array
    {
        return ['sub' => $sub, 'section' => $section, 'section_confirmed' => $confirmed];
    }
}
