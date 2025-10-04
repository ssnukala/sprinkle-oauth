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

use UserFrosting\Config\Config;
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
            OAuthConnectionRepository::class => \DI\autowire(),

            // OAuth Service
            OAuthService::class => \DI\autowire()
                ->constructorParameter('config', \DI\get(Config::class))
                ->constructorParameter('baseUrl', \DI\string('{site.uri.public}')),

            // OAuth Authentication Service
            OAuthAuthenticationService::class => \DI\autowire(),
        ];
    }
}
