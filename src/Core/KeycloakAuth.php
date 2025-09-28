<?php
namespace App\Core;

/**
 * Keycloak Authentication Service
 * Handles OAuth2 Authorization Code flow with Keycloak
 */
class KeycloakAuth
{
    private $baseUrl;
    private $realm;
    private $clientId;
    private $clientSecret;
    private $redirectUri;

    public function __construct()
    {
        $this->baseUrl = KEYCLOAK_BASE_URL;
        $this->realm = KEYCLOAK_REALM;
        $this->clientId = KEYCLOAK_CLIENT_ID;
        $this->clientSecret = KEYCLOAK_CLIENT_SECRET;
        $this->redirectUri = KEYCLOAK_REDIRECT_URI;
    }

    /**
     * Generate authorization URL for OAuth2 flow
     * 
     * @return string
     */
    public function getAuthUrl(): string
    {
        $params = [
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $this->generateState()
        ];
        
        return $this->baseUrl . '/realms/' . $this->realm . '/protocol/openid-connect/auth?' . http_build_query($params);
    }

    /**
     * Exchange authorization code for access token
     * 
     * @param string $code Authorization code from Keycloak
     * @return array|null Token data or null on failure
     */
    public function exchangeCodeForToken(string $code): ?array
    {
        $data = [
            'grant_type' => 'authorization_code',
            'client_id' => $this->clientId,
            'code' => $code,
            'redirect_uri' => $this->redirectUri
        ];

        // Only add client_secret if it's configured (confidential client)
        if (!empty($this->clientSecret)) {
            $data['client_secret'] = $this->clientSecret;
        }

        $response = $this->makeRequest('/realms/' . $this->realm . '/protocol/openid-connect/token', $data);
        return $response ? json_decode($response, true) : null;
    }

    /**
     * Get user information from access token
     * 
     * @param string $accessToken Access token from Keycloak
     * @return array|null User info or null on failure
     */
    public function getUserInfo(string $accessToken): ?array
    {
        $headers = ['Authorization: Bearer ' . $accessToken];
        $response = $this->makeRequest('/realms/' . $this->realm . '/protocol/openid-connect/userinfo', [], $headers);
        return $response ? json_decode($response, true) : null;
    }

    /**
     * Validate access token
     * 
     * @param string $accessToken Access token to validate
     * @return bool True if valid, false otherwise
     */
    public function validateToken(string $accessToken): bool
    {
        $userInfo = $this->getUserInfo($accessToken);
        return $userInfo !== null;
    }

    /**
     * Process login with provided access token (for mobile app integration)
     * 
     * @param string $accessToken Access token from mobile app
     * @return array|null User info or null on failure
     */
    public function loginWithToken(string $accessToken): ?array
    {
        error_log("KeycloakAuth: Starting token validation");
        
        // For production, we should validate JWT signature
        // For now, we'll skip it since getUserInfo already validates the token with Keycloak
        // The getUserInfo call to Keycloak will fail if the token is invalid
        
        // First validate the JWT signature
        // if (!$this->validateJwtSignature($accessToken)) {
        //     error_log("KeycloakAuth: JWT signature validation failed");
        //     return null;
        // }

        error_log("KeycloakAuth: Skipping JWT signature validation - relying on Keycloak API validation");

        // Then validate token and get user info
        $userInfo = $this->getUserInfo($accessToken);
        if (!$userInfo) {
            error_log("KeycloakAuth: getUserInfo failed");
            return null;
        }

        error_log("KeycloakAuth: getUserInfo successful: " . json_encode($userInfo));
        return $userInfo;
    }

    /**
     * Validate JWT signature using Keycloak's public keys
     * 
     * @param string $jwt JWT token to validate
     * @return bool True if signature is valid, false otherwise
     */
    private function validateJwtSignature(string $jwt): bool
    {
        try {
            // Parse JWT
            $parts = explode('.', $jwt);
            if (count($parts) !== 3) {
                return false;
            }

            $header = json_decode(base64_decode($parts[0]), true);
            $payload = json_decode(base64_decode($parts[1]), true);
            $signature = base64_decode(strtr($parts[2], '-_', '+/'));

            // Check issuer
            $expectedIssuer = $this->baseUrl . '/realms/' . $this->realm;
            if ($payload['iss'] !== $expectedIssuer) {
                return false;
            }

            // Check expiration
            if (isset($payload['exp']) && $payload['exp'] < time()) {
                return false;
            }

            // Get public key for signature verification
            $publicKey = $this->getPublicKey($header['kid'] ?? null);
            if (!$publicKey) {
                return false;
            }

            // Verify signature
            $data = $parts[0] . '.' . $parts[1];
            $result = openssl_verify($data, $signature, $publicKey, OPENSSL_ALGO_SHA256);
            
            return $result === 1;

        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get public key from Keycloak for JWT verification
     * 
     * @param string|null $kid Key ID from JWT header
     * @return mixed Public key resource or null on failure
     */
    private function getPublicKey(?string $kid = null)
    {
        try {
            // Get Keycloak's public keys
            $response = $this->makeRequest('/realms/' . $this->realm . '/protocol/openid-connect/certs');
            if (!$response) {
                return null;
            }

            $keys = json_decode($response, true);
            if (!$keys || !isset($keys['keys'])) {
                return null;
            }

            // Find the correct key
            foreach ($keys['keys'] as $key) {
                if ($kid && $key['kid'] !== $kid) {
                    continue;
                }

                if ($key['kty'] === 'RSA' && isset($key['x5c'][0])) {
                    // Convert X.509 certificate to PEM format
                    $cert = "-----BEGIN CERTIFICATE-----\n" . 
                            chunk_split($key['x5c'][0], 64, "\n") . 
                            "-----END CERTIFICATE-----";
                    
                    // Extract public key from certificate
                    $certResource = openssl_x509_read($cert);
                    if ($certResource) {
                        $publicKey = openssl_pkey_get_public($certResource);
                        return $publicKey;
                    }
                }
            }

            return null;

        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Generate cryptographically secure state parameter
     * 
     * @return string State parameter
     */
    private function generateState(): string
    {
        $state = bin2hex(random_bytes(16));
        $_SESSION['keycloak_state'] = $state;
        return $state;
    }

    /**
     * Make HTTP request to Keycloak
     * 
     * @param string $endpoint API endpoint
     * @param array $data POST data (if any)
     * @param array $headers Additional headers
     * @return string|null Response body or null on failure
     */
    private function makeRequest(string $endpoint, array $data = [], array $headers = []): ?string
    {
        $url = $this->baseUrl . $endpoint;
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        if (!empty($data)) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        }
        
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            error_log("Keycloak request error: " . $error);
            return null;
        }
        
        if ($httpCode !== 200) {
            error_log("Keycloak request failed with HTTP code: " . $httpCode . ", Response: " . $response);
            return null;
        }
        
        return $response;
    }
}
