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
    public function register(): array
    {
        return [
            // OAuth Connection Repository
            OAuthConnectionRepository::class => \DI\autowire(OAuthConnectionRepository::class),

            // OAuth Service
            OAuthService::class => \DI\factory(function (ContainerInterface $c) {
                $config = $c->get('config');
                $oauthConfig = $config['oauth'] ?? [];
                $baseUrl = $config['site.uri.public'] ?? '';
                
                return new OAuthService($oauthConfig, $baseUrl);
            }),

            // OAuth Authentication Service
            OAuthAuthenticationService::class => \DI\factory(function (ContainerInterface $c) {
                return new OAuthAuthenticationService(
                    $c->get(OAuthConnectionRepository::class)
                );
            }),
        ];
    }
}
