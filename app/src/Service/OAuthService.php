<?php

declare(strict_types=1);

/*
 * UserFrosting OAuth Sprinkle (https://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-oauth
 * @copyright Copyright (c) 2024 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-oauth/blob/master/LICENSE (MIT License)
 */

namespace UserFrosting\Sprinkle\OAuth\Service;

use Google\Client as GoogleClient;
use League\OAuth2\Client\Provider\Facebook;
use Microsoft\Graph\Graph;
use GuzzleHttp\Client as GuzzleClient;
use UserFrosting\Config\Config;

/**
 * OAuth Service
 * 
 * Factory service for creating OAuth providers using official vendor SDKs
 */
class OAuthService
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
        $client->setRedirectUri($this->baseUrl . '/oauth/google/callback');
        $client->addScope('email');
        $client->addScope('profile');
        $client->addScope('openid');
        
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
            'redirectUri' => $this->baseUrl . '/oauth/facebook/callback',
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
            'redirectUri' => $this->baseUrl . '/oauth/microsoft/callback',
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
            'redirectUri' => $this->baseUrl . '/oauth/linkedin/callback',
            'scopes' => ['openid', 'email', 'profile'],
        ];
    }

    /**
     * Get authorization URL for a provider
     *
     * @param string $providerName Provider name
     * @return string Authorization URL
     * @throws \InvalidArgumentException If provider is not supported
     */
    public function getAuthorizationUrl(string $providerName): string
    {
        switch ($providerName) {
            case 'google':
                $client = $this->getGoogleClient();
                return $client->createAuthUrl();
            
            case 'facebook':
                $provider = $this->getFacebookClient();
                return $provider->getAuthorizationUrl([
                    'scope' => ['email', 'public_profile']
                ]);
            
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
     * Exchange authorization code for access token
     *
     * @param string $providerName Provider name
     * @param string $code Authorization code
     * @return array Token data
     * @throws \Exception If token exchange fails
     */
    public function getAccessToken(string $providerName, string $code): array
    {
        switch ($providerName) {
            case 'google':
                $client = $this->getGoogleClient();
                $token = $client->fetchAccessTokenWithAuthCode($code);
                
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
                    $accessToken = $provider->getAccessToken('authorization_code', [
                        'code' => $code
                    ]);
                    
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
                
                $client = new GuzzleClient();
                $response = $client->post($tokenUrl, [
                    'form_params' => [
                        'client_id' => $config['clientId'],
                        'client_secret' => $config['clientSecret'],
                        'code' => $code,
                        'redirect_uri' => $config['redirectUri'],
                        'grant_type' => 'authorization_code',
                    ],
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
                
                $client = new GuzzleClient();
                $response = $client->post($tokenUrl, [
                    'form_params' => [
                        'grant_type' => 'authorization_code',
                        'code' => $code,
                        'client_id' => $config['clientId'],
                        'client_secret' => $config['clientSecret'],
                        'redirect_uri' => $config['redirectUri'],
                    ],
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
