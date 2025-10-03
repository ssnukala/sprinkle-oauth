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
use UserFrosting\Routes\RouteDefinitionInterface;
use UserFrosting\Sprinkle\OAuth\Controller\OAuthController;

/**
 * Routes for OAuth operations.
 */
class OAuthRoutes implements RouteDefinitionInterface
{
    public function register(App $app): void
    {
        // OAuth login page
        $app->get('/oauth/login', [OAuthController::class, 'loginPage'])
            ->setName('oauth.login');

        // OAuth provider redirect
        $app->get('/oauth/{provider}', [OAuthController::class, 'redirect'])
            ->setName('oauth.redirect');

        // OAuth callback
        $app->get('/oauth/{provider}/callback', [OAuthController::class, 'callback'])
            ->setName('oauth.callback');

        // Link OAuth provider to existing account
        $app->get('/oauth/link/{provider}', [OAuthController::class, 'linkProvider'])
            ->setName('oauth.link');

        // Disconnect OAuth provider from account
        $app->post('/oauth/disconnect/{provider}', [OAuthController::class, 'disconnect'])
            ->setName('oauth.disconnect');
    }
}
