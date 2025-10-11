<?php

declare(strict_types=1);

/*
 * UserFrosting OAuth Sprinkle (https://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-oauth
 * @copyright Copyright (c) 2024 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-oauth/blob/master/LICENSE (MIT License)
 */

namespace UserFrosting\Sprinkle\OAuth\Tests\Controller;

use UserFrosting\Sprinkle\OAuth\Tests\OAuthTestCase;
use UserFrosting\Sprinkle\OAuth\Controller\OAuthController;

/**
 * Integration tests for OAuthController
 * 
 * Tests the controller's ability to handle OAuth authentication flows
 */
class OAuthControllerTest extends OAuthTestCase
{
    /**
     * Set up test environment
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Set up OAuth configuration
        $this->ci->get('config')->set('oauth.google', [
            'clientId' => 'test-google-client-id',
            'clientSecret' => 'test-google-client-secret',
        ]);
    }

    /**
     * Test that the OAuth controller can be retrieved from the container
     */
    public function testControllerCanBeRetrievedFromContainer(): void
    {
        $controller = $this->ci->get(OAuthController::class);
        
        $this->assertInstanceOf(OAuthController::class, $controller);
    }

    /**
     * Test redirect to OAuth provider
     * 
     * This test verifies that the redirect endpoint generates a proper
     * redirect response to the OAuth provider's authorization URL
     */
    public function testRedirectToOAuthProvider(): void
    {
        $request = $this->createRequest('GET', '/oauth/google');
        
        $response = $this->handleRequest($request);

        // Should redirect to Google OAuth
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertStringContainsString('accounts.google.com', $response->getHeaderLine('Location'));
    }

    /**
     * Test redirect with invalid provider
     * 
     * This test verifies that requests for unconfigured providers
     * are handled gracefully with proper error handling
     */
    public function testRedirectWithInvalidProvider(): void
    {
        $request = $this->createRequest('GET', '/oauth/invalid-provider');
        
        $response = $this->handleRequest($request);

        // Should redirect to login page with error
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertStringContainsString('/login', $response->getHeaderLine('Location'));
    }

    /**
     * Test OAuth callback with missing state
     * 
     * This test verifies CSRF protection by checking that callbacks
     * without valid state are rejected
     */
    public function testCallbackWithMissingState(): void
    {
        $request = $this->createRequest('GET', '/oauth/google/callback')
            ->withQueryParams([
                'code' => 'test-authorization-code',
            ]);
        
        $response = $this->handleRequest($request);

        // Should redirect with error due to missing/invalid state
        $this->assertEquals(302, $response->getStatusCode());
    }

    /**
     * Test login page shows OAuth providers
     * 
     * This test verifies that the login page displays buttons
     * for all configured OAuth providers
     */
    public function testLoginPageShowsOAuthProviders(): void
    {
        $request = $this->createRequest('GET', '/oauth/login');
        
        $response = $this->handleRequest($request);

        $this->assertEquals(200, $response->getStatusCode());
        
        $body = (string) $response->getBody();
        $this->assertStringContainsString('Sign in with Google', $body);
    }

    /**
     * Test that OAuth routes are properly registered
     * 
     * This test verifies that all expected OAuth routes exist
     * and are accessible
     */
    public function testOAuthRoutesAreRegistered(): void
    {
        $router = $this->ci->get('router');
        $routes = $router->getRoutes();
        
        $routePatterns = array_map(fn($route) => $route->getPattern(), $routes);
        
        // Verify OAuth routes exist
        $expectedRoutes = [
            '/oauth/login',
            '/oauth/{provider}',
            '/oauth/{provider}/callback',
        ];
        
        foreach ($expectedRoutes as $expectedRoute) {
            $this->assertTrue(
                in_array($expectedRoute, $routePatterns),
                "Route {$expectedRoute} should be registered"
            );
        }
    }
}
