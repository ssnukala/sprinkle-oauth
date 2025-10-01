<?php

declare(strict_types=1);

/*
 * UserFrosting OAuth Sprinkle (https://www.userfrosting.com)
 *
 * @link      https://github.com/ssnukala/sprinkle-oauth
 * @copyright Copyright (c) 2024 Srinivas Nukala
 * @license   https://github.com/ssnukala/sprinkle-oauth/blob/master/LICENSE (MIT License)
 */

namespace UserFrosting\Sprinkle\OAuth\ServicesProvider;

use Psr\Container\ContainerInterface;
use UserFrosting\Sprinkle\OAuth\Controller\OAuthController;
use UserFrosting\Sprinkle\OAuth\Service\OAuthService;
use UserFrosting\Sprinkle\OAuth\Service\OAuthAuthenticationService;
use UserFrosting\ServicesProvider\ServicesProviderInterface;
use Slim\Views\Twig;

/**
 * OAuth Controller Provider
 * 
 * Registers OAuth controllers in the DI container
 */
class OAuthControllerProvider implements ServicesProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(ContainerInterface $container): void
    {
        // OAuth Controller
        $container->set(OAuthController::class, function (ContainerInterface $c) {
            return new OAuthController(
                $c->get(OAuthService::class),
                $c->get(OAuthAuthenticationService::class),
                $c->get(Twig::class)
            );
        });
    }
}
