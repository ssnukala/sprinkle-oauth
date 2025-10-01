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
use UserFrosting\Sprinkle\OAuth\Service\OAuthService;
use UserFrosting\Sprinkle\OAuth\Service\OAuthAuthenticationService;
use UserFrosting\Sprinkle\OAuth\Repository\OAuthConnectionRepository;
use UserFrosting\ServicesProvider\ServicesProviderInterface;

/**
 * OAuth Services Provider
 * 
 * Registers OAuth services in the DI container
 */
class OAuthServicesProvider implements ServicesProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(ContainerInterface $container): void
    {
        // OAuth Connection Repository
        $container->set(OAuthConnectionRepository::class, function (ContainerInterface $c) {
            return new OAuthConnectionRepository();
        });

        // OAuth Service
        $container->set(OAuthService::class, function (ContainerInterface $c) {
            $config = $c->get('config');
            $oauthConfig = $config['oauth'] ?? [];
            $baseUrl = $config['site.uri.public'] ?? '';
            
            return new OAuthService($oauthConfig, $baseUrl);
        });

        // OAuth Authentication Service
        $container->set(OAuthAuthenticationService::class, function (ContainerInterface $c) {
            return new OAuthAuthenticationService(
                $c->get(OAuthConnectionRepository::class)
            );
        });
    }
}
