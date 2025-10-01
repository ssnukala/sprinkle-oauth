<?php

declare(strict_types=1);

/*
 * UserFrosting OAuth Sprinkle (https://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-oauth
 * @copyright Copyright (c) 2024 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-oauth/blob/master/LICENSE (MIT License)
 */

use Slim\App;
use UserFrosting\Sprinkle\OAuth\Controller\OAuthController;

return function (App $app) {
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
};
