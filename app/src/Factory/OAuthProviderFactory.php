<?php

declare(strict_types=1);

/*
 * UserFrosting OAuth Sprinkle (https://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-oauth
 * @copyright Copyright (c) 2024 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-oauth/blob/master/LICENSE (MIT License)
 */

namespace UserFrosting\Sprinkle\OAuth\Factory;

use Google\Client as GoogleClient;
use League\OAuth2\Client\Provider\Facebook;
use Microsoft\Graph\Graph;
use GuzzleHttp\Client as GuzzleClient;
use UserFrosting\Config\Config;

/**
 * OAuth Provider Factory
 *
 * Factory for creating OAuth provider instances using official vendor SDKs.
 * Supports PKCE (Proof Key for Code Exchange) as defense-in-depth per OAuth 2.1.
 */
class OAuthProviderFactory
{
    /**
     * @var array OAuth provider configurations
     */
    protected array $config;

    /**
     * @var string Base URL for redirects
     */
    protected string $baseUrl;

    /**
     * Constructor
     *
     * @param Config $config Configuration instance
     * @param string $baseUrl Base URL for the application
     */
    public function __construct(Config $config, string $baseUrl)
    {
        $this->config = $config->get('oauth', []);
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    // ─── PKCE (Proof Key for Code Exchange) ────────────────────────────

    /**
     * Generate a PKCE code verifier and challenge pair.
     *
     * The code_verifier is a cryptographically random string (43-128 chars, URL-safe).
     * The code_challenge is the Base64-URL-encoded SHA-256 hash of the verifier.
     *
     * @return array{code_verifier: string, code_challenge: string, code_challenge_method: string}
     */
    public function generatePkce(): array
    {
        // Generate 32 random bytes → 43-char base64url string
        $verifier = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');

        // S256: SHA-256 hash of the verifier, base64url-encoded
        $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');

        return [
            'code_verifier' => $verifier,
            'code_challenge' => $challenge,
            'code_challenge_method' => 'S256',
        ];
    }

    /**
     * Get Google Client
     *
     * @return GoogleClient
     * @throws \InvalidArgumentException If provider is not configured
     */
    public function getGoogleClient(): GoogleClient
    {
        if (!isset($this->config['google'])) {
            throw new \InvalidArgumentException("Google OAuth provider is not configured.");
        }

        $client = new GoogleClient();
        $client->setClientId($this->config['google']['clientId']);
        $client->setClientSecret($this->config['google']['clientSecret']);
        $client->setRedirectUri($this->baseUrl . '/api/oauth/google/callback');
        $client->addScope('email');
        $client->addScope('profile');
        $client->addScope('openid');

        // Add configured additional scopes (e.g., spreadsheets for Google Sheets)
        $additionalScopes = $this->config['google']['scopes'] ?? [];
        foreach ($additionalScopes as $scope) {
            $client->addScope($scope);
        }

        // Set access type for refresh tokens (needed for offline access like Sheets)
        $accessType = $this->config['google']['access_type'] ?? 'online';
        $client->setAccessType($accessType);
        if ($accessType === 'offline') {
            $client->setPrompt('consent');
        }

        return $client;
    }

    /**
     * Get Facebook OAuth2 Provider
     *
     * @return Facebook
     * @throws \InvalidArgumentException If provider is not configured
     */
    public function getFacebookClient(): Facebook
    {
        if (!isset($this->config['facebook'])) {
            throw new \InvalidArgumentException("Facebook OAuth provider is not configured.");
        }

        return new Facebook([
            'clientId' => $this->config['facebook']['clientId'],
            'clientSecret' => $this->config['facebook']['clientSecret'],
            'redirectUri' => $this->baseUrl . '/api/oauth/facebook/callback',
            'graphApiVersion' => $this->config['facebook']['graphApiVersion'] ?? 'v18.0',
        ]);
    }

    /**
     * Get Microsoft Graph client
     *
     * @return array Configuration for Microsoft OAuth
     * @throws \InvalidArgumentException If provider is not configured
     */
    public function getMicrosoftConfig(): array
    {
        if (!isset($this->config['microsoft'])) {
            throw new \InvalidArgumentException("Microsoft OAuth provider is not configured.");
        }

        return [
            'clientId' => $this->config['microsoft']['clientId'],
            'clientSecret' => $this->config['microsoft']['clientSecret'],
            'redirectUri' => $this->baseUrl . '/api/oauth/microsoft/callback',
            'tenant' => $this->config['microsoft']['tenant'] ?? 'common',
            'scopes' => ['openid', 'email', 'profile', 'User.Read'],
        ];
    }

    /**
     * Get LinkedIn configuration (LinkedIn doesn't have an official PHP SDK, using Guzzle)
     *
     * @return array Configuration for LinkedIn OAuth
     * @throws \InvalidArgumentException If provider is not configured
     */
    public function getLinkedInConfig(): array
    {
        if (!isset($this->config['linkedin'])) {
            throw new \InvalidArgumentException("LinkedIn OAuth provider is not configured.");
        }

        return [
            'clientId' => $this->config['linkedin']['clientId'],
            'clientSecret' => $this->config['linkedin']['clientSecret'],
            'redirectUri' => $this->baseUrl . '/api/oauth/linkedin/callback',
            'scopes' => ['openid', 'email', 'profile'],
        ];
    }

    /**
     * Get authorization URL for a provider, with optional PKCE challenge.
     *
     * When PKCE parameters are provided, the code_challenge and code_challenge_method
     * are appended to the authorization URL. This adds defense-in-depth on top of
     * the server-side client_secret flow, per OAuth 2.1 recommendations.
     *
     * @param string $providerName Provider name
     * @param array  $pkce         Optional PKCE params from generatePkce() (code_challenge, code_challenge_method)
     * @return string Authorization URL
     * @throws \InvalidArgumentException If provider is not supported
     */
    public function getAuthorizationUrl(string $providerName, array $pkce = []): string
    {
        $codeChallenge = $pkce['code_challenge'] ?? null;
        $codeChallengeMethod = $pkce['code_challenge_method'] ?? 'S256';

        switch ($providerName) {
            case 'google':
                $client = $this->getGoogleClient();
                $authUrl = $client->createAuthUrl();
                // Google Client SDK doesn't have native setCodeChallenge — append PKCE params
                if ($codeChallenge) {
                    $authUrl .= '&code_challenge=' . urlencode($codeChallenge)
                             . '&code_challenge_method=' . urlencode($codeChallengeMethod);
                }
                return $authUrl;

            case 'facebook':
                $provider = $this->getFacebookClient();
                $options = ['scope' => ['email', 'public_profile']];
                if ($codeChallenge) {
                    $options['code_challenge'] = $codeChallenge;
                    $options['code_challenge_method'] = $codeChallengeMethod;
                }
                return $provider->getAuthorizationUrl($options);

            case 'microsoft':
                $config = $this->getMicrosoftConfig();
                $authorizeUrl = 'https://login.microsoftonline.com/' . $config['tenant'] . '/oauth2/v2.0/authorize';
                $params = [
                    'client_id' => $config['clientId'],
                    'response_type' => 'code',
                    'redirect_uri' => $config['redirectUri'],
                    'scope' => implode(' ', $config['scopes']),
                    'response_mode' => 'query',
                ];
                if ($codeChallenge) {
                    $params['code_challenge'] = $codeChallenge;
                    $params['code_challenge_method'] = $codeChallengeMethod;
                }
                return $authorizeUrl . '?' . http_build_query($params);

            case 'linkedin':
                $config = $this->getLinkedInConfig();
                $authorizeUrl = 'https://www.linkedin.com/oauth/v2/authorization';
                $params = [
                    'client_id' => $config['clientId'],
                    'response_type' => 'code',
                    'redirect_uri' => $config['redirectUri'],
                    'scope' => implode(' ', $config['scopes']),
                ];
                if ($codeChallenge) {
                    $params['code_challenge'] = $codeChallenge;
                    $params['code_challenge_method'] = $codeChallengeMethod;
                }
                return $authorizeUrl . '?' . http_build_query($params);

            default:
                throw new \InvalidArgumentException("Unsupported OAuth provider: {$providerName}");
        }
    }

    /**
     * Get OAuth state for CSRF protection
     *
     * @param string $providerName Provider name
     * @return string State value
     */
    public function getState(string $providerName): string
    {
        // Generate a random state for CSRF protection
        return bin2hex(random_bytes(16));
    }

    /**
     * Exchange authorization code for access token, with optional PKCE code_verifier.
     *
     * When a code_verifier is provided, it is included in the token exchange request.
     * The provider validates that SHA-256(code_verifier) matches the code_challenge
     * sent during authorization, preventing authorization code interception attacks.
     *
     * @param string      $providerName Provider name
     * @param string      $code         Authorization code
     * @param string|null $codeVerifier PKCE code_verifier (stored in session during redirect)
     * @return array Token data
     * @throws \Exception If token exchange fails
     */
    public function getAccessToken(string $providerName, string $code, ?string $codeVerifier = null): array
    {
        switch ($providerName) {
            case 'google':
                $client = $this->getGoogleClient();
                // Google Client SDK supports code_verifier via setHttpClient or direct param
                if ($codeVerifier) {
                    $client->setHttpClient(new GuzzleClient());
                    $token = $client->fetchAccessTokenWithAuthCode($code, $codeVerifier);
                } else {
                    $token = $client->fetchAccessTokenWithAuthCode($code);
                }

                if (isset($token['error'])) {
                    throw new \Exception('Google token exchange failed: ' . $token['error']);
                }

                return [
                    'access_token' => $token['access_token'],
                    'refresh_token' => $token['refresh_token'] ?? null,
                    'expires' => isset($token['expires_in']) ? time() + $token['expires_in'] : null,
                ];

            case 'facebook':
                $provider = $this->getFacebookClient();

                try {
                    $options = ['code' => $code];
                    if ($codeVerifier) {
                        $options['code_verifier'] = $codeVerifier;
                    }

                    $accessToken = $provider->getAccessToken('authorization_code', $options);

                    return [
                        'access_token' => $accessToken->getToken(),
                        'refresh_token' => $accessToken->getRefreshToken(),
                        'expires' => $accessToken->getExpires(),
                    ];
                } catch (\Exception $e) {
                    throw new \Exception('Facebook token exchange failed: ' . $e->getMessage());
                }

            case 'microsoft':
                $config = $this->getMicrosoftConfig();
                $tokenUrl = 'https://login.microsoftonline.com/' . $config['tenant'] . '/oauth2/v2.0/token';

                $formParams = [
                    'client_id' => $config['clientId'],
                    'client_secret' => $config['clientSecret'],
                    'code' => $code,
                    'redirect_uri' => $config['redirectUri'],
                    'grant_type' => 'authorization_code',
                ];
                if ($codeVerifier) {
                    $formParams['code_verifier'] = $codeVerifier;
                }

                $client = new GuzzleClient();
                $response = $client->post($tokenUrl, [
                    'form_params' => $formParams,
                ]);

                $data = json_decode($response->getBody()->getContents(), true);

                return [
                    'access_token' => $data['access_token'],
                    'refresh_token' => $data['refresh_token'] ?? null,
                    'expires' => isset($data['expires_in']) ? time() + $data['expires_in'] : null,
                ];

            case 'linkedin':
                $config = $this->getLinkedInConfig();
                $tokenUrl = 'https://www.linkedin.com/oauth/v2/accessToken';

                $formParams = [
                    'grant_type' => 'authorization_code',
                    'code' => $code,
                    'client_id' => $config['clientId'],
                    'client_secret' => $config['clientSecret'],
                    'redirect_uri' => $config['redirectUri'],
                ];
                if ($codeVerifier) {
                    $formParams['code_verifier'] = $codeVerifier;
                }

                $client = new GuzzleClient();
                $response = $client->post($tokenUrl, [
                    'form_params' => $formParams,
                ]);

                $data = json_decode($response->getBody()->getContents(), true);

                return [
                    'access_token' => $data['access_token'],
                    'refresh_token' => $data['refresh_token'] ?? null,
                    'expires' => isset($data['expires_in']) ? time() + $data['expires_in'] : null,
                ];

            default:
                throw new \InvalidArgumentException("Unsupported OAuth provider: {$providerName}");
        }
    }

    /**
     * Get user information from OAuth provider
     *
     * @param string $providerName Provider name
     * @param string $accessToken Access token
     * @return array User data
     * @throws \Exception If fetching user data fails
     */
    public function getUserInfo(string $providerName, string $accessToken): array
    {
        switch ($providerName) {
            case 'google':
                $client = $this->getGoogleClient();
                $client->setAccessToken($accessToken);
                
                $oauth2 = new \Google\Service\Oauth2($client);
                $userInfo = $oauth2->userinfo->get();
                
                return [
                    'id' => $userInfo->getId(),
                    'email' => $userInfo->getEmail(),
                    'given_name' => $userInfo->getGivenName(),
                    'family_name' => $userInfo->getFamilyName(),
                    'name' => $userInfo->getName(),
                    'picture' => $userInfo->getPicture(),
                    'verified_email' => $userInfo->getVerifiedEmail(),
                ];
            
            case 'facebook':
                $provider = $this->getFacebookClient();
                
                try {
                    // Create an access token object from the string
                    $token = new \League\OAuth2\Client\Token\AccessToken([
                        'access_token' => $accessToken
                    ]);
                    
                    $user = $provider->getResourceOwner($token);
                    
                    return [
                        'id' => $user->getId(),
                        'email' => $user->getEmail(),
                        'first_name' => $user->getFirstName(),
                        'last_name' => $user->getLastName(),
                        'name' => $user->getName(),
                        'picture' => $user->getPictureUrl(),
                    ];
                } catch (\Exception $e) {
                    throw new \Exception('Facebook user info failed: ' . $e->getMessage());
                }
            
            case 'microsoft':
                $graph = new Graph();
                $graph->setAccessToken($accessToken);
                
                try {
                    $user = $graph->createRequest('GET', '/me')
                        ->setReturnType(\Microsoft\Graph\Model\User::class)
                        ->execute();
                    
                    return [
                        'id' => $user->getId(),
                        'email' => $user->getMail() ?? $user->getUserPrincipalName(),
                        'given_name' => $user->getGivenName(),
                        'family_name' => $user->getSurname(),
                        'name' => $user->getDisplayName(),
                    ];
                } catch (\Exception $e) {
                    throw new \Exception('Microsoft user info failed: ' . $e->getMessage());
                }
            
            case 'linkedin':
                $client = new GuzzleClient();
                
                // Get user profile
                $response = $client->get('https://api.linkedin.com/v2/userinfo', [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $accessToken,
                    ],
                ]);
                
                $data = json_decode($response->getBody()->getContents(), true);
                
                return [
                    'id' => $data['sub'],
                    'email' => $data['email'],
                    'given_name' => $data['given_name'] ?? '',
                    'family_name' => $data['family_name'] ?? '',
                    'name' => $data['name'] ?? '',
                    'picture' => $data['picture'] ?? null,
                ];
            
            default:
                throw new \InvalidArgumentException("Unsupported OAuth provider: {$providerName}");
        }
    }

    /**
     * Get list of enabled providers
     *
     * @return array List of enabled provider names
     */
    public function getEnabledProviders(): array
    {
        return array_keys(array_filter($this->config, function($config) {
            return !empty($config['clientId']) && !empty($config['clientSecret']);
        }));
    }
}
