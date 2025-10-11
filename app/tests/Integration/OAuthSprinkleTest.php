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
use UserFrosting\Sprinkle\OAuth\OAuth;

/**
 * Basic integration test to verify the OAuth sprinkle loads correctly
 * 
 * This test verifies the fundamental integration with UserFrosting:
 * - The sprinkle can be instantiated
 * - Dependencies are properly configured
 * - The test infrastructure is working
 */
class OAuthSprinkleTest extends OAuthTestCase
{
    /**
     * Test that the OAuth sprinkle is properly loaded
     */
    public function testOAuthSprinkleIsLoaded(): void
    {
        $sprinkle = new OAuth();
        
        $this->assertInstanceOf(OAuth::class, $sprinkle);
        $this->assertEquals('OAuth Sprinkle', $sprinkle->getName());
    }

    /**
     * Test that the dependency injection container is available
     */
    public function testContainerIsAvailable(): void
    {
        $this->assertNotNull($this->ci);
        $this->assertTrue($this->ci->has('config'));
    }

    /**
     * Test that configuration can be accessed
     */
    public function testConfigurationIsAccessible(): void
    {
        $config = $this->ci->get('config');
        
        $this->assertNotNull($config);
        
        // Set a test value
        $config->set('oauth.test', ['value' => 'test']);
        
        $this->assertEquals('test', $config->get('oauth.test.value'));
    }

    /**
     * Test that OAuth routes are registered
     */
    public function testOAuthRoutesAreRegistered(): void
    {
        $sprinkle = new OAuth();
        $routes = $sprinkle->getRoutes();
        
        $this->assertIsArray($routes);
        $this->assertNotEmpty($routes);
    }

    /**
     * Test that OAuth service providers are registered
     */
    public function testOAuthServiceProvidersAreRegistered(): void
    {
        $sprinkle = new OAuth();
        $services = $sprinkle->getServices();
        
        $this->assertIsArray($services);
        $this->assertNotEmpty($services);
    }

    /**
     * Test that the sprinkle has correct dependencies
     */
    public function testSprinkleDependencies(): void
    {
        $sprinkle = new OAuth();
        $dependencies = $sprinkle->getSprinkles();
        
        $this->assertIsArray($dependencies);
        $this->assertContains(\UserFrosting\Sprinkle\Core\Core::class, $dependencies);
    }
}
