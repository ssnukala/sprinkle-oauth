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
use UserFrosting\Sprinkle\Account\Database\Models\Interfaces\UserInterface;
use UserFrosting\Sprinkle\OAuth\Authenticator\OAuthAuthenticator;
use UserFrosting\Sprinkle\OAuth\Factory\OAuthProviderFactory;
use UserFrosting\Sprinkle\OAuth\Repository\OAuthConnectionRepository;
use UserFrosting\Sprinkle\OAuth\Services\GoogleSheetsService;
use UserFrosting\ServicesProvider\ServicesProviderInterface;

/**
 * OAuth Services Provider.
 *
 * Registers OAuth-related services in the dependency injection container.
 * Follows UserFrosting 6 service provider pattern.
 */
class OAuthServicesProvider implements ServicesProviderInterface
{
    /**
     * {@inheritDoc}
     */
    public function register(): array
    {
        return [
            // OAuth Connection Repository
            OAuthConnectionRepository::class => \DI\autowire(),

            // OAuth Provider Factory
            // Injects Config and site base URL for provider initialization
            OAuthProviderFactory::class => \DI\autowire()
                ->constructorParameter('config', \DI\get(Config::class))
                ->constructorParameter('baseUrl', \DI\string('{site.uri.public}')),

            // OAuth Authenticator
            // Injects repository and User model class for authentication flow
            OAuthAuthenticator::class => \DI\autowire()
                ->constructorParameter('userModel', \DI\get(UserInterface::class)),

            // Google Sheets Service
            GoogleSheetsService::class => \DI\autowire(),
        ];
    }
}
