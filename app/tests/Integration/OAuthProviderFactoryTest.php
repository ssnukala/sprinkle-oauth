<?php

declare(strict_types=1);

/*
 * UserFrosting OAuth Sprinkle (https://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-oauth
 * @copyright Copyright (c) 2024 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-oauth/blob/master/LICENSE (MIT License)
 */

namespace UserFrosting\Sprinkle\OAuth\Tests\Integration;

use UserFrosting\Sprinkle\OAuth\Tests\OAuthTestCase;
use UserFrosting\Sprinkle\OAuth\Factory\OAuthProviderFactory;
use Google\Client as GoogleClient;
use League\OAuth2\Client\Provider\Facebook;

/**
 * Integration tests for OAuthProviderFactory
 * 
 * Tests the factory's ability to create OAuth provider instances
 * with proper configuration from the UserFrosting config system
 */
class OAuthProviderFactoryTest extends OAuthTestCase
{
    /**
     * Test that the factory can be retrieved from the container
     */
    public function testFactoryCanBeRetrievedFromContainer(): void
    {
        $factory = $this->ci->get(OAuthProviderFactory::class);
        
        $this->assertInstanceOf(OAuthProviderFactory::class, $factory);
    }

    /**
     * Test that Google provider can be created when configured
     */
    public function testCanCreateGoogleProviderWhenConfigured(): void
    {
        // Set up Google OAuth configuration
        $this->ci->get('config')->set('oauth.google', [
            'clientId' => 'test-google-client-id',
            'clientSecret' => 'test-google-client-secret',
        ]);

        $factory = $this->ci->get(OAuthProviderFactory::class);
        $provider = $factory->create('google');
        
        $this->assertInstanceOf(GoogleClient::class, $provider);
    }

    /**
     * Test that Facebook provider can be created when configured
     */
    public function testCanCreateFacebookProviderWhenConfigured(): void
    {
        // Set up Facebook OAuth configuration
        $this->ci->get('config')->set('oauth.facebook', [
            'clientId' => 'test-facebook-client-id',
            'clientSecret' => 'test-facebook-client-secret',
        ]);

        $factory = $this->ci->get(OAuthProviderFactory::class);
        $provider = $factory->create('facebook');
        
        $this->assertInstanceOf(Facebook::class, $provider);
    }

    /**
     * Test that exception is thrown for unconfigured provider
     */
    public function testThrowsExceptionForUnconfiguredProvider(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('OAuth provider "invalid" is not configured');

        $factory = $this->ci->get(OAuthProviderFactory::class);
        $factory->create('invalid');
    }

    /**
     * Test that enabled providers list can be retrieved
     */
    public function testCanGetEnabledProviders(): void
    {
        // Configure multiple providers
        $this->ci->get('config')->set('oauth.google', [
            'clientId' => 'test-google-client-id',
            'clientSecret' => 'test-google-client-secret',
        ]);
        $this->ci->get('config')->set('oauth.facebook', [
            'clientId' => 'test-facebook-client-id',
            'clientSecret' => 'test-facebook-client-secret',
        ]);

        $factory = $this->ci->get(OAuthProviderFactory::class);
        $enabled = $factory->getEnabledProviders();
        
        $this->assertIsArray($enabled);
        $this->assertContains('google', $enabled);
        $this->assertContains('facebook', $enabled);
    }
}
