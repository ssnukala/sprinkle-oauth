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
use UserFrosting\Sprinkle\OAuth\Controller\OAuthController;
use UserFrosting\Sprinkle\Core\Middlewares\NoCache;

/**
 * Routes for OAuth operations.
 * 
 * Following UserFrosting 6 conventions:
 * - Backend API routes use /api prefix
 * - Frontend routes are defined in app/assets/routes/index.ts
 */
class OAuthRoutes implements RouteDefinitionInterface
{
    public function register(App $app): void
    {
        // OAuth API routes - Backend endpoints
        $app->group('/api/oauth', function (RouteCollectorProxy $group) {
            // OAuth provider redirect - Initiates OAuth flow
            $group->get('/{provider}', [OAuthController::class, 'redirect'])
                ->setName('api.oauth.redirect');

            // OAuth callback - Handles provider callback
            $group->get('/{provider}/callback', [OAuthController::class, 'callback'])
                ->setName('api.oauth.callback');

            // Link OAuth provider to existing account (authenticated)
            $group->get('/link/{provider}', [OAuthController::class, 'linkProvider'])
                ->setName('api.oauth.link');

            // Disconnect OAuth provider from account (authenticated)
            $group->post('/disconnect/{provider}', [OAuthController::class, 'disconnect'])
                ->setName('api.oauth.disconnect');
        })->add(NoCache::class);

        // Frontend page route (for legacy/direct access)
        // Note: The main OAuth login page is rendered by the Vue router at /oauth/login
        // This route serves as a fallback for server-side rendering if needed
        $app->get('/oauth/login', [OAuthController::class, 'loginPage'])
            ->setName('oauth.login');
    }
}
