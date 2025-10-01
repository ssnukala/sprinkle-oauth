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

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Google;
use League\OAuth2\Client\Provider\Facebook;
use League\OAuth2\Client\Provider\LinkedIn;
use Stevenmaguire\OAuth2\Client\Provider\Microsoft;
use Psr\Http\Message\ServerRequestInterface;

/**
 * OAuth Service
 * 
 * Factory service for creating OAuth providers
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
     * @param array $config OAuth configuration
     * @param string $baseUrl Base URL for the application
     */
    public function __construct(array $config, string $baseUrl)
    {
        $this->config = $config;
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    /**
     * Get OAuth provider instance
     *
     * @param string $providerName Provider name (google, facebook, microsoft, linkedin)
     * @return AbstractProvider
     * @throws \InvalidArgumentException If provider is not configured or invalid
     */
    public function getProvider(string $providerName): AbstractProvider
    {
        if (!isset($this->config[$providerName])) {
            throw new \InvalidArgumentException("OAuth provider '{$providerName}' is not configured.");
        }

        $providerConfig = $this->config[$providerName];
        
        // Add redirect URI
        $providerConfig['redirectUri'] = $this->baseUrl . '/oauth/' . $providerName . '/callback';

        switch ($providerName) {
            case 'google':
                return new Google($providerConfig);
            
            case 'facebook':
                return new Facebook($providerConfig);
            
            case 'linkedin':
                return new LinkedIn($providerConfig);
            
            case 'microsoft':
                return new Microsoft($providerConfig);
            
            default:
                throw new \InvalidArgumentException("Unsupported OAuth provider: {$providerName}");
        }
    }

    /**
     * Get authorization URL for a provider
     *
     * @param string $providerName Provider name
     * @param array $options Additional options
     * @return string Authorization URL
     */
    public function getAuthorizationUrl(string $providerName, array $options = []): string
    {
        $provider = $this->getProvider($providerName);
        
        // Add provider-specific scopes
        if (!isset($options['scope'])) {
            $options['scope'] = $this->getDefaultScopes($providerName);
        }
        
        return $provider->getAuthorizationUrl($options);
    }

    /**
     * Get OAuth state for CSRF protection
     *
     * @param string $providerName Provider name
     * @return string State value
     */
    public function getState(string $providerName): string
    {
        $provider = $this->getProvider($providerName);
        return $provider->getState();
    }

    /**
     * Get default scopes for a provider
     *
     * @param string $providerName Provider name
     * @return array Default scopes
     */
    protected function getDefaultScopes(string $providerName): array
    {
        $scopes = [
            'google' => ['openid', 'email', 'profile'],
            'facebook' => ['email', 'public_profile'],
            'linkedin' => ['r_liteprofile', 'r_emailaddress'],
            'microsoft' => ['openid', 'email', 'profile'],
        ];

        return $scopes[$providerName] ?? [];
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
