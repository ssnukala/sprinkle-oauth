<?php

declare(strict_types=1);

/*
 * UserFrosting OAuth Sprinkle (https://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-oauth
 * @copyright Copyright (c) 2024 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-oauth/blob/master/LICENSE (MIT License)
 */

namespace UserFrosting\Sprinkle\OAuth\Routes;

use Slim\App;
use Slim\Routing\RouteCollectorProxy;
use UserFrosting\Routes\RouteDefinitionInterface;
use UserFrosting\Sprinkle\Account\Authenticate\AuthGuard;
use UserFrosting\Sprinkle\OAuth\Controller\GoogleSheetsController;
use UserFrosting\Sprinkle\OAuth\Controller\OAuthController;
use UserFrosting\Sprinkle\Core\Middlewares\NoCache;

/**
 * OAuth Routes Definition.
 *
 * Defines RESTful routes for OAuth authentication and provider management.
 * Implements UserFrosting 6 RouteDefinitionInterface pattern.
 *
 * Route Structure:
 * - `/api/oauth/{provider}` - Redirect to OAuth provider (supports ?popup=1 for popup flow)
 * - `/api/oauth/{provider}/callback` - OAuth callback (supports popup postMessage)
 * - `/api/oauth/connections` - Get current user's OAuth connections (JSON)
 * - `/api/oauth/link/{provider}` - Link provider to authenticated user (supports ?popup=1)
 * - `/api/oauth/disconnect/{provider}` - Disconnect provider from user
 * - `/api/oauth/sheets/read` - Read Google Sheets data
 * - `/api/oauth/sheets/append` - Append rows to Google Sheets
 * - `/oauth/login` - OAuth login page (fallback for server-side rendering)
 *
 * All routes use NoCache middleware to prevent caching of OAuth responses.
 */
class OAuthRoutes implements RouteDefinitionInterface
{
    /**
     * {@inheritDoc}
     */
    public function register(App $app): void
    {
        // OAuth API routes - Backend endpoints
        $app->group('/api/oauth', function (RouteCollectorProxy $group) {
            // Google Sheets API endpoints (require authentication)
            $group->get('/sheets/read', [GoogleSheetsController::class, 'read'])
                ->setName('api.oauth.sheets.read')
                ->add(AuthGuard::class);

            $group->post('/sheets/append', [GoogleSheetsController::class, 'append'])
                ->setName('api.oauth.sheets.append')
                ->add(AuthGuard::class);

            // Get current user's OAuth connections (authenticated, JSON)
            $group->get('/connections', [OAuthController::class, 'connections'])
                ->setName('api.oauth.connections')
                ->add(AuthGuard::class);

            // Link OAuth provider to existing account (authenticated)
            $group->get('/link/{provider}', [OAuthController::class, 'linkProvider'])
                ->setName('api.oauth.link');

            // Disconnect OAuth provider from account (authenticated)
            $group->post('/disconnect/{provider}', [OAuthController::class, 'disconnect'])
                ->setName('api.oauth.disconnect');

            // OAuth provider redirect - Initiates OAuth flow
            $group->get('/{provider}', [OAuthController::class, 'redirect'])
                ->setName('api.oauth.redirect');

            // OAuth callback - Handles provider callback
            $group->get('/{provider}/callback', [OAuthController::class, 'callback'])
                ->setName('api.oauth.callback');
        })->add(NoCache::class);

        // Frontend page route (for legacy/direct access)
        // Note: The main OAuth login page is rendered by the Vue router at /oauth/login
        // This route serves as a fallback for server-side rendering if needed
        $app->get('/oauth/login', [OAuthController::class, 'loginPage'])
            ->setName('oauth.login');
    }
}
